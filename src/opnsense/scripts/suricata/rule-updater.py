#!/usr/local/bin/python2.7

"""
    Copyright (c) 2015-2018 Ad Schellevis <ad@opnsense.org>
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

    update/download suricata rules
"""

import os
import sys
import syslog
import fcntl
from ConfigParser import ConfigParser
from lib import metadata
from lib import downloader
from lib import rule_source_directory

# check for a running update process, this may take a while so it's better to check...
try:
    lck = open('/tmp/suricata-rule-updater.py', 'w+')
    fcntl.flock(lck, fcntl.LOCK_EX | fcntl.LOCK_NB)
except IOError:
    # already running, exit status 99
    sys.exit(99)

if __name__ == '__main__':
    # load list of configured rules from generated config
    enabled_rulefiles = dict()
    rule_properties = dict()
    updater_conf = '/usr/local/etc/suricata/rule-updater.config'
    if os.path.exists(updater_conf):
        cnf = ConfigParser()
        cnf.read(updater_conf)
        for section in cnf.sections():
            if section == '__properties__':
                # special section, rule properties (extend url's etc.)
                for item in cnf.items(section):
                    rule_properties[item[0]] = item[1]
            elif cnf.has_option(section, 'enabled') and cnf.getint(section, 'enabled') == 1:
                enabled_rulefiles[section.strip()] = {}
                # input filter
                if cnf.has_option(section, 'filter'):
                    enabled_rulefiles[section.strip()]['filter'] = cnf.get(section, 'filter').strip()
                else:
                    enabled_rulefiles[section.strip()]['filter'] = ""

    # download / remove rules
    md = metadata.Metadata()
    dl = downloader.Downloader(target_dir=rule_source_directory)
    for rule in md.list_rules(rule_properties):
        if 'url' in rule['source']:
            download_proto = str(rule['source']['url']).split(':')[0].lower()
            if dl.is_supported(url=rule['source']['url']):
                if rule['filename'] not in enabled_rulefiles:
                    try:
                        # remove configurable but unselected file
                        os.remove(('%s/%s' % (rule_source_directory, rule['filename'])).replace('//', '/'))
                    except OSError:
                        pass
                else:
                    input_filter = enabled_rulefiles[rule['filename']]['filter']
                    if ('username' in rule['source'] and 'password' in rule['source']):
                        auth = (rule['source']['username'], rule['source']['password'])
                    else:
                        auth = None
                    # when metadata supports versioning, check if either version or settings changed before download
                    remote_hash = dl.fetch_version_hash(check_url=rule['version_url'], input_filter=input_filter,
                                                        auth=auth, headers=rule['http_headers'])
                    local_hash = dl.installed_file_hash(rule['filename'])
                    if remote_hash is None or remote_hash != local_hash:
                        dl.download(url=rule['url'], url_filename=rule['url_filename'],
                                    filename=rule['filename'], input_filter=input_filter, auth=auth,
                                    headers=rule['http_headers'], version=remote_hash)
                    else:
                        syslog.syslog(syslog.LOG_INFO, 'download skipped %s, same version' % rule['filename'])
