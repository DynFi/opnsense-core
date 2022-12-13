#!/usr/local/bin/python3

"""
    Copyright (c) 2017-2019 Ad Schellevis <ad@opnsense.org>
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
    read filter log, limit by number of records or last received digest (md5 hash of row)
"""
import os
import sys
import re
import glob
from hashlib import md5
import argparse
import ujson
import subprocess
sys.path.insert(0, "/usr/local/opnsense/site-python")
from log_helper import reverse_log_reader
from params import update_params


# define log layouts, every endpoint contains all options
# source : https://github.com/opnsense/ports/blob/master/opnsense/filterlog/files/description.txt
fields_general = 'rulenr,subrulenr,anchorname,rid,interface,reason,action,dir,ipversion'.split(',')

fields_ipv4 = fields_general + 'tos,ecn,ttl,id,offset,ipflags,protonum,protoname,length,src,dst'.split(',')
fields_ipv4_udp = fields_ipv4 + 'srcport,dstport,datalen'.split(',')
fields_ipv4_tcp = fields_ipv4 + 'srcport,dstport,datalen,tcpflags,seq,ack,urp,tcpopts'.split(',')
fields_ipv4_carp = fields_ipv4 + 'type,ttl,vhid,version,advskew,advbase'.split(',')

fields_ipv6 = fields_general + 'class,flow,hoplimit,protoname,protonum,length,src,dst'.split(',')
fields_ipv6_udp = fields_ipv6 + 'srcport,dstport,datalen'.split(',')
fields_ipv6_tcp = fields_ipv6 + 'srcport,dstport,datalen,tcpflags,seq,ack,urp,tcpopts'.split(',')
fields_ipv6_carp = fields_ipv6 + 'type,hoplimit,vhid,version,advskew,advbase'.split(',')

# define hex digits
HEX_DIGITS = set("0123456789abcdef")

def update_rule(target, metadata_target, ruleparts, spec):
    """ update target rule with parts in spec
        :param target: target rule
        :param metadata_target: collected metadata
        :param ruleparts: list of rule items
        :param spec: full rule specification, depending on protocol and version
    """
    while len(target) < len(spec) and len(ruleparts) > 0:
        target[spec[len(target)]] = ruleparts.pop(0)
    # full spec
    metadata_target['__spec__'] = spec

def fetch_rule_details():
    """ Fetch rule descriptions from the current running config if available
        :return : rule details per line number
    """
    line_id_map = dict()
    if os.path.isfile('/tmp/rules.debug'):
        # parse running config, fetch all md5 hashed labels
        rule_map = dict()
        with open('/tmp/rules.debug', "rt", encoding="utf-8") as f_in:
            for line in f_in:
                if line.find(' label ') > -1:
                    lbl = line.split(' label ')[-1]
                    if lbl.count('"') >= 2:
                        rule_md5 = lbl.split('"')[1]
                        if len(rule_md5) == 32 and set(rule_md5).issubset(HEX_DIGITS):
                            rule_map[rule_md5] = ''.join(lbl.split('"')[2:]).strip().strip('# : ')

    return rule_map


if __name__ == '__main__':
    # read parameters
    parameters = {'limit': '0', 'digest': ''}
    update_params(parameters)
    parameters['limit'] = int(parameters['limit'])

    # parse current running config
    running_conf_descr = fetch_rule_details()

    result = list()
    filter_logs = []
    if os.path.isdir('/var/log/filter'):
        filter_logs = list(sorted(glob.glob("/var/log/filter/filter_*.log"), reverse=True))
    if os.path.isfile('/var/log/filter.log'):
        filter_logs.append('/var/log/filter.log')

    for filename in filter_logs:
        do_exit = False
        for record in reverse_log_reader(filename):
            if record['line'].find('filterlog') > -1:
                rule = dict()
                metadata = dict()
                # rule metadata (unique hash, hostname, timestamp)
                if re.search('filterlog\[\d*\]:', record['line']):
                    # rfc3164 format
                    log_ident = re.split('filterlog[^:]*:', record['line'])
                    tmp = log_ident[0].split()
                    metadata['__host__'] = tmp.pop()
                    metadata['__timestamp__'] = ' '.join(tmp)
                    rulep = log_ident[1].strip().split(',')
                else:
                    # rfc5424 format
                    tmp = record['line'].split()
                    metadata['__timestamp__'] = tmp[1].split('+')[0]
                    metadata['__host__'] = tmp[2]
                    rulep = tmp[-1].strip().split(',')

                metadata['__digest__'] = md5(record['line'].encode()).hexdigest()
                update_rule(rule, metadata, rulep, fields_general)

                if 'action' not in rule:
                    # not a filter log line, skip
                    continue
                elif 'ipversion' in rule:
                    if rule['ipversion'] == '4':
                        update_rule(rule, metadata, rulep, fields_ipv4)
                        if 'protonum' in rule:
                            if rule['protonum'] == '17': # UDP
                                update_rule(rule, metadata, rulep, fields_ipv4_udp)
                            elif rule['protonum'] == '6': # TCP
                                update_rule(rule, metadata, rulep, fields_ipv4_tcp)
                            elif rule['protonum'] == '112': # CARP
                                update_rule(rule, metadata, rulep, fields_ipv4_carp)
                    elif rule['ipversion'] == '6':
                        update_rule(rule, metadata, rulep, fields_ipv6)
                        if 'protonum' in rule:
                            if rule['protonum'] == '17': # UDP
                                update_rule(rule, metadata, rulep, fields_ipv6_udp)
                            elif rule['protonum'] == '6': # TCP
                                update_rule(rule, metadata, rulep, fields_ipv6_tcp)
                            elif rule['protonum'] == '112': # CARP
                                update_rule(rule, metadata, rulep, fields_ipv6_carp)

                rule.update(metadata)
                if rule['rid'] != '0':
                    # rule id in latest record format, don't use rule sequence number in that case
                    if rule['rid'] in running_conf_descr:
                        rule['label'] = running_conf_descr[rule['rid']]
                    # obsolete md5 in log record
                    else:
                        rule['label'] = ''
                elif rule['action'] not in ['pass', 'block']:
                    # no id for translation rules
                    rule['label'] = "%s rule" % rule['action']
                elif len(rulep) > 0 and len(rulep[-1]) == 32 and set(rulep[-1]).issubset(HEX_DIGITS):
                    # rule id appended in record format, don't use rule sequence number in that case either
                    rule['rid'] = rulep[-1]
                    if rulep[-1] in running_conf_descr:
                        rule['label'] = running_conf_descr[rulep[-1]]
                    # obsolete md5 in log record
                    else:
                        rule['label'] = ''

                result.append(rule)

                # handle exit criteria, row limit or last digest
                if parameters['limit'] != 0 and len(result) >= parameters['limit']:
                    do_exit = True
                elif parameters['digest'].strip() != '' and parameters['digest'] == rule['__digest__']:
                    do_exit = True
                if do_exit:
                    break
        if do_exit:
            break

    print (ujson.dumps(result))
