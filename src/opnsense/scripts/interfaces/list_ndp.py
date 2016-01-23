#!/usr/local/bin/python2.7

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
    list ndp table
"""
import tempfile
import subprocess
import os
import os.path
import sys
import ujson
import netaddr

if __name__ == '__main__':
    result = []

    # parse ndp output
    with tempfile.NamedTemporaryFile() as output_stream:
        subprocess.call(['/usr/sbin/ndp', '-an'], stdout=output_stream, stderr=open(os.devnull, 'wb'))
        output_stream.seek(0)
        data = output_stream.read().strip()
        for line in data.split('\n')[1:]:
            line_parts = line.split()
            if len(line_parts) > 3 and line_parts[1] != '(incomplete)':
                record = {'mac': line_parts[1],
                          'ip': line_parts[0],
                          'intf': line_parts[2],
                          'manufacturer': ''
                          }
                manufacturer_mac = netaddr.EUI(record['mac'])
                try:
                    record['manufacturer'] = manufacturer_mac.oui.registration().org
                except netaddr.NotRegisteredError:
                    pass
                result.append(record)

    # handle command line argument (type selection)
    if len(sys.argv) > 1 and sys.argv[1] == 'json':
        print(ujson.dumps(result))
    else:
        # output plain text (console)
        print ('%-40s %-20s %-10s %s' % ('ip', 'mac', 'intf', 'manufacturer'))
        for record in result:
            print ('%(ip)-40s %(mac)-20s %(intf)-10s %(manufacturer)s' % record)
