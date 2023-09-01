"""
    Copyright (c) 2023 DynFi
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

suricata_timeformat = r'^(\d{1,2}/\d{1,2}/\d{4}-\d{1,2}:\d{1,2}:\d{1,2}).*'

class SuricataAlertsLogFormat(NewBaseLogFormat):

    def __init__(self, filename):
        super(NewBaseLogFormat, self).__init__(filename)
        self._priority = 100

    def match(self, line):
        return ('alerts' in self._filename or 'http' in self._filename) and '[**]' in line

    def get_ts(self):
        tmp = re.match(suricata_timeformat, self._line)
        return tmp.group(1)

    @property
    def timestamp(self):
        return datetime.datetime.strptime(self.get_ts().replace('-', ' '), "%m/%d/%Y %H:%M:%S").isoformat()

    @property
    def line(self):
        arr = self._line.split(' ')
        arr.pop(0)
        return ' '.join(arr)
