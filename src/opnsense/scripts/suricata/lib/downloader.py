"""
    Copyright (c) 2015 Ad Schellevis
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

    rule downloader module (may need to be extended in the future, this version only processes http(s))
"""

import syslog
import requests


class Downloader(object):
    def __init__(self, target_dir):
        self._target_dir = target_dir

    def filter(self, in_data, filter_type):
        """ apply input filter to downloaded data
            :param in_data: raw input data (ruleset)
            :param filter_type: filter type to use on input data
            :return: ruleset data
        """
        if filter_type == "drop":
            return self.filter_drop(in_data)
        else:
            return in_data

    def filter_drop(self, in_data):
        """ change all alert rules to block
            :param in_data: raw input data (ruleset)
            :return: new ruleset
        """
        output = list()
        for line in in_data.split('\n'):
            if len(line) > 10:
                if line[0:5] == 'alert':
                    line = 'drop %s' % line[5:]
                elif line[0:6] == '#alert':
                    line = '#drop %s' % line[5:]
            output.append(line)
        return '\n'.join(output)

    def download(self, proto, url, filename, input_filter):
        """ download ruleset file
            :param proto: protocol (http,https)
            :param url: download url
            :param filename: target filename
            :param input_filter: filter to use on received data before save
        """
        if proto in ('http', 'https'):
            frm_url = url.replace('//', '/').replace(':/', '://')
            req = requests.get(url=frm_url)
            if req.status_code == 200:
                try:
                    target_filename = '%s/%s' % (self._target_dir, filename)
                    save_data = self.filter(req.text, input_filter)
                    open(target_filename, 'wb').write(save_data)
                except IOError:
                    syslog.syslog(syslog.LOG_ERR, 'cannot write to %s' % target_filename)
                    return None
                syslog.syslog(syslog.LOG_INFO, 'download completed for %s' % frm_url)
            else:
                syslog.syslog(syslog.LOG_ERR, 'download failed for %s' % frm_url)

    @staticmethod
    def is_supported(proto):
        """ check if protocol is supported
        :param proto:
        :return:
        """
        if proto in ['http', 'https']:
            return True
        else:
            return False
