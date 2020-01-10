#!/usr/local/bin/python3

"""
    Copyright (c) 2015 Ad Schellevis <ad@opnsense.org>
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
    list pfsync info
    - nodes (unique creator id's from states)
"""
import subprocess
import sys
import ujson

if __name__ == '__main__':
    result = {'nodes': []}
    sp = subprocess.run(['/sbin/pfctl', '-s', 'state', '-vv'], capture_output=True)
    data = sp.stdout.decode().strip()
    if data.count('\n') > 2:
        for line in data.split('\n'):
            if line.find('creatorid:') > -1:
                creatorid = line.split('creatorid:')[1].strip()
                if creatorid not in result['nodes']:
                    result['nodes'].append(creatorid)

    # handle command line argument (type selection)
    if len(sys.argv) > 1 and sys.argv[1] == 'json':
        print(ujson.dumps(result))
    else:
        # output plain
        print ('------------------------- pfsync nodes -------------------------')
        for ostype in result['nodes']:
            print (ostype)
