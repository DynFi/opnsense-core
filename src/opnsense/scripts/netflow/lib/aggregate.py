"""
    Copyright (c) 2016 Ad Schellevis
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
     this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
     notice, this list of conditions and the following disclaimer in the
     documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.

    --------------------------------------------------------------------------------------
    aggregate flow data (format in parse.py) into sqlite structured container per type/resolution.
    Implementations are collected in lib\aggregates\
"""
import os
import datetime
import sqlite3

def convert_timestamp(val):
    """ convert timestamps from string (internal sqlite type) or seconds since epoch
    """
    if val.find('-') > -1:
        # formatted date/time
        if val.find(" ") > -1:
            datepart, timepart = val.split(" ")
        else:
            datepart = val
            timepart = "0:0:0,0"
        year, month, day = map(int, datepart.split("-"))
        timepart_full = timepart.split(".")
        hours, minutes, seconds = map(int, timepart_full[0].split(":"))
        if len(timepart_full) == 2:
            microseconds = int('{:0<6.6}'.format(timepart_full[1].decode()))
        else:
            microseconds = 0

        val = datetime.datetime(year, month, day, hours, minutes, seconds, microseconds)
    else:
        # timestamp stored as seconds since epoch, convert to utc
        val = datetime.datetime.utcfromtimestamp(float(val))

    return val

sqlite3.register_converter('timestamp', convert_timestamp)

class AggMetadata(object):
    """ store some metadata needed to keep track of parse progress
    """
    def __init__(self):
        self._filename = '/var/netflow/metadata.sqlite'
        # make sure the target directory exists
        target_path = os.path.dirname(self._filename)
        if not os.path.isdir(target_path):
            os.makedirs(target_path)
        # open sqlite database and cursor
        self._db_connection = sqlite3.connect(self._filename,
                                              detect_types=sqlite3.PARSE_DECLTYPES|sqlite3.PARSE_COLNAMES)
        self._db_cursor = self._db_connection.cursor()
        # known tables
        self._tables = list()
        # cache known tables
        self._update_known_tables()

    def __del__(self):
        """ close database on destruct
        :return: None
        """
        if self._db_connection is not None:
            self._db_connection.close()

    def _update_known_tables(self):
        """ request known tables
        """
        result = list()
        self._db_cursor.execute('SELECT name FROM sqlite_master')
        for record in self._db_cursor.fetchall():
            self._tables.append(record[0])

    def update_sync_time(self, timestamp):
        """ update (last) sync timestamp
        """
        if 'sync_timestamp' not in self._tables:
            self._db_cursor.execute('create table sync_timestamp(mtime timestamp)')
            self._db_cursor.execute('insert into sync_timestamp(mtime) values(0)')
            self._db_connection.commit()
            self._update_known_tables()
        # update last sync timestamp, if this date > timestamp
        self._db_cursor.execute('update sync_timestamp set mtime = :mtime where mtime < :mtime', {'mtime': timestamp})
        self._db_connection.commit()

    def last_sync(self):
        if 'sync_timestamp' not in self._tables:
            return 0.0
        else:
            self._db_cursor.execute('select max(mtime) from sync_timestamp')
            return self._db_cursor.fetchall()[0][0]

class BaseFlowAggregator(object):
    # target location ('/var/netflow/<store>.sqlite')
    target_filename = None
    # list of fields to use in this aggregate
    agg_fields = None

    @classmethod
    def resolutions(cls):
        """ sample resolutions for this aggregation
        :return: list of sample resolutions
        """
        return  list()

    @classmethod
    def history_per_resolution(cls):
        """ history to keep in seconds per sample resolution
        :return: dict sample resolution / expire time (seconds)
        """
        return dict()

    @classmethod
    def seconds_per_day(cls, days):
        """
        :param days: number of days
        :return: number of seconds
        """
        return 60*60*24*days

    def __init__(self, resolution):
        """ construct new flow sample class
        :return: None
        """
        self.resolution = resolution
        # target table name, data_<resolution in seconds>
        self._db_connection = None
        self._update_cur = None
        self._known_targets = list()
        # construct update and insert sql statements
        tmp = 'update timeserie set  last_seen = :flow_end, '
        tmp += 'octets = octets + :octets_consumed, packets = packets + :packets_consumed '
        tmp += 'where mtime = :mtime and %s '
        self._update_stmt = tmp % (' and '.join(map(lambda x: '%s = :%s' % (x, x), self.agg_fields)))
        tmp = 'insert into timeserie (mtime, last_seen, octets, packets, %s) '
        tmp += 'values (:mtime, :flow_end, :octets_consumed, :packets_consumed, %s)'
        self._insert_stmt = tmp % (','.join(self.agg_fields), ','.join(map(lambda x: ':%s' % x, self.agg_fields)))
        # open database
        self._open_db()
        self._fetch_known_targets()

    def __del__(self):
        """ close database on destruct
        :return: None
        """
        if self._db_connection is not None:
            self._db_connection.close()

    def _fetch_known_targets(self):
        """ read known target table names from the sqlite db
        :return: None
        """
        if self._db_connection is not None:
            self._known_targets = list()
            cur = self._db_connection.cursor()
            cur.execute('SELECT name FROM sqlite_master')
            for record in cur.fetchall():
                self._known_targets.append(record[0])
            cur.close()

    def _create_target_table(self):
        """ construct target aggregate table, using resulution and list of agg_fields
        :return: None
        """
        if self._db_connection is not None:
            # construct new aggregate table
            sql_text = list()
            sql_text.append('create table timeserie ( ')
            sql_text.append('   mtime timestamp')
            sql_text.append(',  last_seen timestamp')
            for agg_field in self.agg_fields:
                sql_text.append(', %s varchar(255)' % agg_field)
            sql_text.append(',  octets numeric')
            sql_text.append(',  packets numeric')
            sql_text.append(',  primary key(mtime, %s)' % ','.join(self.agg_fields))
            sql_text.append(')')
            cur = self._db_connection.cursor()
            cur.executescript('\n'.join(sql_text))
            cur.close()
            # read table names
            self._fetch_known_targets()

    def is_db_open(self):
        """ check if target database is open
        :return: database connected (True/False)
        """
        if self._db_connection is not None:
            return True
        else:
            return False

    def _open_db(self):
        """ open / create database
        :return: None
        """
        if self.target_filename is not None:
            # make sure the target directory exists
            target_path = os.path.dirname(self.target_filename % self.resolution)
            if not os.path.isdir(target_path):
                os.makedirs(target_path)
            # open sqlite database
            self._db_connection = sqlite3.connect(self.target_filename % self.resolution,
                                                  detect_types=sqlite3.PARSE_DECLTYPES|sqlite3.PARSE_COLNAMES)
            # open update/insert cursor
            self._update_cur = self._db_connection.cursor()

    def commit(self):
        """ commit data
        :return: None
        """
        if self._db_connection is not None:
            self._db_connection.commit()


    def add(self, flow):
        """ calculate timeslices per flow depending on sample resolution
        :param flow: flow data (from parse.py)
        :return: None
        """
        # make sure target exists
        if 'timeserie' not in self._known_targets:
            self._create_target_table()

        # push record(s) depending on resolution
        start_time = int(flow['flow_start'] / self.resolution) * self.resolution
        while start_time <= flow['flow_end']:
            consume_start_time = max(flow['flow_start'], start_time)
            consume_end_time = min(start_time + self.resolution, flow['flow_end'])
            if flow['duration_ms'] != 0:
                consume_perc = (consume_end_time - consume_start_time) / float(flow['duration_ms'] / 1000)
            else:
                consume_perc = 1
            if self.is_db_open():
                # upsert data
                flow['octets_consumed'] = consume_perc * flow['octets']
                flow['packets_consumed'] = consume_perc * flow['packets']
                flow['mtime'] = datetime.datetime.utcfromtimestamp(start_time)
                self._update_cur.execute(self._update_stmt, flow)
                if self._update_cur.rowcount == 0:
                    self._update_cur.execute(self._insert_stmt, flow)
            # next start time
            start_time += self.resolution

    def cleanup(self, do_vacuum = False):
        """ cleanup timeserie table
        :param expire: cleanup table, remove data older then [expire] seconds
        :param do_vacuum: vacuum database
        :return: None
        """
        if self.is_db_open() and 'timeserie' in self._known_targets \
          and self.resolution in self.history_per_resolution():
            self._update_cur.execute('select max(mtime) as "[timestamp]" from timeserie')
            last_timestamp = self._update_cur.fetchall()[0][0]
            if type(last_timestamp) == datetime.datetime:
                expire = self.history_per_resolution()[self.resolution]
                expire_timestamp = last_timestamp - datetime.timedelta(seconds=expire)
                self._update_cur.execute('delete from timeserie where mtime < :expire', {'expire': expire_timestamp})
                self.commit()
                if do_vacuum:
                    # vacuum database if requested
                    self._update_cur.execute('vacuum')

    def _parse_timestamp(self, timestamp):
        """ convert input to datetime.datetime or return if it already was of that type
        :param timestamp: timestamp to convert
        :return: datetime.datetime object
        """
        if type(timestamp) in (int, float):
            return datetime.datetime.utcfromtimestamp(timestamp)
        elif type(timestamp) != datetime.datetime:
            return datetime.datetime.utcfromtimestamp(0)
        else:
            return timestamp

    def _valid_fields(self, fields):
        """ cleanse fields (return only valid ones)
        :param fields: field list
        :return: list
        """
        # validate field list (can only select fields in self.agg_fields)
        select_fields = list()
        for field in fields:
            if field.strip() in self.agg_fields:
                select_fields.append(field.strip())

        return select_fields

    def get_timeserie_data(self, start_time, end_time, fields):
        """ fetch data from aggregation source, groups by mtime and selected fields
        :param start_time: start timestamp
        :param end_time: end timestamp
        :param fields: fields to retrieve
        :return: iterator returning dict records (start_time, end_time, [fields], octets, packets)
        """
        if self.is_db_open() and 'timeserie' in self._known_targets:
            # validate field list (can only select fields in self.agg_fields)
            select_fields = self._valid_fields(fields)
            if len(select_fields) == 0:
                # select "none", add static null as field
                select_fields.append('null')
            sql_select = 'select mtime as "start_time [timestamp]", %s' % ','.join(select_fields)
            sql_select += ', sum(octets) as octets, sum(packets) as packets\n'
            sql_select += 'from timeserie \n'
            sql_select += 'where mtime >= :start_time and mtime < :end_time\n'
            sql_select += 'group by mtime, %s\n'% ','.join(select_fields)

            # execute select query
            cur = self._db_connection.cursor()
            cur.execute(sql_select, {'start_time': self._parse_timestamp(start_time),
                                     'end_time': self._parse_timestamp(end_time)})
            #
            field_names = (map(lambda x:x[0], cur.description))
            for record in cur.fetchall():
                result_record = dict()
                for field_indx in range(len(field_names)):
                    if len(record) > field_indx:
                        result_record[field_names[field_indx]] = record[field_indx]
                if 'start_time' in result_record:
                    result_record['end_time'] = result_record['start_time'] + datetime.timedelta(seconds=self.resolution)
                    # send data
                    yield result_record
            # close cursor
            cur.close()

    def get_top_data(self, start_time, end_time, fields, value_field, data_filters=None, max_hits=100):
        """ Retrieve top (usage) from this aggregation.
            Fetch data from aggregation source, groups by selected fields, sorts by value_field descending
            use data_filter to filter before grouping.
        :param start_time: start timestamp
        :param end_time: end timestamp
        :param fields: fields to retrieve
        :param value_field: field to sum
        :param data_filter: filter data, use as field=value
        :param max_hits: maximum number of results, rest is summed into (other)
        :return: iterator returning dict records (start_time, end_time, [fields], octets, packets)
        """
        result = list()
        select_fields = self._valid_fields(fields)
        filter_fields = []
        query_params = {}
        if value_field == 'octets':
            value_sql = 'sum(octets)'
        elif value_field == 'packets':
            value_sql = 'sum(packets)'
        else:
            value_sql = '0'

        # query filters, correct start_time for resolution
        query_params['start_time'] = self._parse_timestamp((int(start_time/self.resolution))*self.resolution)
        query_params['end_time'] = self._parse_timestamp(end_time)
        if data_filters:
            for data_filter in data_filters.split(','):
                tmp = data_filter.split('=')[0].strip()
                if tmp in self.agg_fields and data_filter.find('=') > -1:
                    filter_fields.append(tmp)
                    query_params[tmp] = '='.join(data_filter.split('=')[1:])

        if len(select_fields) > 0:
            # construct sql query to filter and select data
            sql_select = 'select %s' % ','.join(select_fields)
            sql_select += ', %s as total, max(last_seen) last_seen \n' % value_sql
            sql_select += 'from timeserie \n'
            sql_select += 'where mtime >= :start_time and mtime < :end_time\n'
            for filter_field in filter_fields:
                sql_select += ' and %s = :%s \n' % (filter_field, filter_field)
            sql_select += 'group by %s\n'% ','.join(select_fields)
            sql_select += 'order by %s desc ' % value_sql

            # execute select query
            cur = self._db_connection.cursor()
            cur.execute(sql_select, query_params)

            # fetch all data, to a max of [max_hits] rows.
            field_names = (map(lambda x:x[0], cur.description))
            for record in cur.fetchall():
                result_record = dict()
                for field_indx in range(len(field_names)):
                    if len(record) > field_indx:
                        result_record[field_names[field_indx]] = record[field_indx]
                if len(result) < max_hits:
                    result.append(result_record)
                else:
                    if len(result) == max_hits:
                        # generate row for "rest of data"
                        result.append({'total': 0})
                        for key in result_record:
                            if key not in result[-1]:
                                result[-1][key] = ""
                    result[-1]['total'] += result_record['total']
            # close cursor
            cur.close()

        return result

    def get_data(self, start_time, end_time):
        """ get detail data
        :param start_time: start timestamp
        :param end_time: end timestamp
        :return: iterator
        """
        query_params = {}
        query_params['start_time'] = self._parse_timestamp((int(start_time/self.resolution))*self.resolution)
        query_params['end_time'] = self._parse_timestamp(end_time)
        sql_select = 'select mtime start_time, '
        sql_select += '%s, octets, packets, last_seen as "last_seen [timestamp]"  \n' % ','.join(self.agg_fields)
        sql_select += 'from timeserie \n'
        sql_select += 'where mtime >= :start_time and mtime < :end_time\n'
        cur = self._db_connection.cursor()
        cur.execute(sql_select, query_params)

        # fetch all data, to a max of [max_hits] rows.
        field_names = (map(lambda x:x[0], cur.description))
        while True:
            record = cur.fetchone()
            if record is None:
                break
            else:
                result_record=dict()
                for field_indx in range(len(field_names)):
                    if len(record) > field_indx:
                        result_record[field_names[field_indx]] = record[field_indx]
                yield result_record
