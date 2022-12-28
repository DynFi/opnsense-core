#!/usr/local/bin/python3

"""
    Copyright (c) 2020 Ad Schellevis <ad@opnsense.org>
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
"""

import os
import sys
import re
import syslog
import tempfile
import time
import fcntl
from configparser import ConfigParser
import requests
import json

def uri_reader(uri):
    req_opts = {
        'url': uri,
        'timeout': 5,
        'stream': True
    }
    try:
        req = requests.get(**req_opts)
    except Exception as e:
        syslog.syslog(syslog.LOG_ERR,'blocklist download : unable to download file from %s (error : %s)' % (uri, e))
        return

    if req.status_code >= 200 and req.status_code <= 299:
        req.raw.decode_content = True
        prev_chop  = ''
        while True:
            chop = req.raw.read(1024).decode()
            if not chop:
                if prev_chop:
                    yield prev_chop
                break
            else:
                parts = (prev_chop + chop).split('\n')
                if parts[-1] != "\n":
                    prev_chop = parts.pop()
                else:
                    prev_chop = ''
                for part in parts:
                    yield part
    else:
        syslog.syslog(syslog.LOG_ERR,
            'blocklist download : unable to download file from %s (status_code: %d)' % (uri, req.status_code)
        )



if __name__ == '__main__':
    # check for a running download process, this may take a while so it's better to check...
    try:
        lck = open('/tmp/unbound-download_blocklists.tmp', 'w+')
        fcntl.flock(lck, fcntl.LOCK_EX | fcntl.LOCK_NB)
    except IOError:
        # already running, exit status 99
        sys.exit(99)

    domain_pattern = re.compile(
        r'^(([\da-zA-Z_])([_\w-]{,62})\.){,127}(([\da-zA-Z])[_\w-]{,61})'
        r'?([\da-zA-Z]\.((xn\-\-[a-zA-Z\d]+)|([a-zA-Z\d]{2,})))$'
    )

    destination_address = '0.0.0.0'
    rcode = 'NOERROR'

    startup_time = time.time()
    syslog.openlog('unbound', logoption=syslog.LOG_DAEMON, facility=syslog.LOG_LOCAL4)
    blocklist_items = {
        'data': {},
        'config': {}
    }
    if os.path.exists('/tmp/unbound-blocklists.conf'):
        cnf = ConfigParser()
        cnf.read('/tmp/unbound-blocklists.conf')
        # exclude (white) lists, compile to regex to be used to filter blocklist entries
        if cnf.has_section('exclude'):
            exclude_list = set()
            for exclude_item in cnf['exclude']:
                try:
                    re.compile(cnf['exclude'][exclude_item], re.IGNORECASE)
                    exclude_list.add(cnf['exclude'][exclude_item])
                except re.error:
                    syslog.syslog(syslog.LOG_ERR,
                        'blocklist download : skip invalid whitelist exclude pattern "%s" (%s)' % (
                            exclude_item, cnf['exclude'][exclude_item]
                        )
                    )
            if not exclude_list:
                exclude_list.add('$^')

            wp = '|'.join(exclude_list)
            whitelist_pattern = re.compile(wp, re.IGNORECASE)
            syslog.syslog(syslog.LOG_NOTICE, 'blocklist download : exclude domains matching %s' % wp)

        # fetch all blocklists
        if cnf.has_section('settings'):
            if cnf.has_option('settings', 'address'):
                blocklist_items['config']['dst_addr'] = cnf.get('settings', 'address')
            if cnf.has_option('settings', 'rcode'):
                blocklist_items['config']['rcode'] = cnf.get('settings', 'rcode')
        if cnf.has_section('blocklists'):
            for blocklist in cnf['blocklists']:
                list_type = blocklist.split('_', 1)
                bl_shortcode = 'Custom' if list_type[0] == 'custom' else list_type[1]
                file_stats = {'uri': cnf['blocklists'][blocklist], 'skip' : 0, 'blocklist': 0, 'lines' :0}
                for line in uri_reader(cnf['blocklists'][blocklist]):
                    file_stats['lines'] += 1
                    # cut line into parts before comment marker (if any)
                    tmp = line.split('#')[0].split()
                    entry = None
                    while tmp:
                        entry = tmp.pop(-1)
                        if entry not in ['127.0.0.1', '0.0.0.0']:
                            break
                    if entry:
                        domain = entry.lower()
                        if whitelist_pattern.match(entry):
                            file_stats['skip'] += 1
                        else:
                            if domain_pattern.match(domain):
                                file_stats['blocklist'] += 1
                                blocklist_items['data'][entry] = {"bl": bl_shortcode}
                            else:
                                file_stats['skip'] += 1

                syslog.syslog(
                    syslog.LOG_NOTICE,
                    'blocklist download %(uri)s (lines: %(lines)d exclude: %(skip)d block: %(blocklist)d)' % file_stats
                )

    # write out results
    if not os.path.exists('/var/unbound/data'):
        os.makedirs('/var/unbound/data')
    with open("/var/unbound/data/dnsbl.json.new", 'w') as unbound_outf:
        if blocklist_items:
            json.dump(blocklist_items, unbound_outf)

    # atomically replace the current dnsbl so unbound can pick up on it
    os.replace('/var/unbound/data/dnsbl.json.new', '/var/unbound/data/dnsbl.json')

    syslog.syslog(syslog.LOG_NOTICE, "blocklist download done in %0.2f seconds (%d records)" % (
        time.time() - startup_time, len(blocklist_items['data'])
    ))
