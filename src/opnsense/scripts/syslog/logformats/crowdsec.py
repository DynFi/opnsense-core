"""
    Copyright (c) 2024 DynFi
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
import re
import datetime
from . import NewBaseLogFormat

crowdsec_timeformat = r'.*time="(\d{4}-\d{1,2}-\d{1,2}T\d{1,2}:\d{1,2}:\d{1,2})Z".*'
crowdsec_levelformat = r'.*level=(\w+) .*'
log_levels = {
    'fatal': 3,
    'warning': 4,
    'info': 6,
}

class CrowdsecLogFormat(NewBaseLogFormat):
    def __init__(self, filename):
        super(NewBaseLogFormat, self).__init__(filename)
        self._priority = 100

    def match(self, line):
        return 'crowdsec' in self._filename

    @property
    def timestamp(self):
        tmp = re.match(crowdsec_timeformat, self._line)
        return datetime.datetime.strptime(tmp.group(1).replace('T', ' '), "%Y-%m-%d %H:%M:%S").isoformat()

    @property
    def severity(self):
        tmp = re.match(crowdsec_levelformat, self._line)
        return log_levels.get(tmp.group(1), 0)

    @property
    def line(self):
        msg = self._line.split('msg=')[-1]
        if msg[0] == '"' and msg[-1] == '"':
            return msg[1:-2]
        return msg
