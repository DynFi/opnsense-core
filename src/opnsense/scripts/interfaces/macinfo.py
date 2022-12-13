#!/usr/local/bin/python3

"""
    Copyright (c) 2022 Ad Schellevis <ad@opnsense.org>
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

import argparse
import glob
import subprocess
import os
import sys
import ujson
import netaddr
import netaddr.core
from datetime import datetime


if __name__ == '__main__':
    result = dict()
    parser = argparse.ArgumentParser()
    parser.add_argument('mac', help='mac address')
    cmd_args = parser.parse_args()

    mac = None
    try:
        mac = netaddr.EUI(cmd_args.mac)
    except netaddr.core.AddrFormatError:
        result['status'] = 'failed'
        result['message'] = 'invalid mac'

    if mac:
        result['status'] = 'ok'
        result['ip'] = []
        result['ip6'] = []
        result['org'] = "***"
        with open("%s/eui/oui.txt" % os.path.dirname(netaddr.__file__)) as fh_macdb:
            for line in fh_macdb:
                if line.startswith(str(mac)[0:8]):
                    result['org'] = line[18:].strip()
                    break

        for cmd in ['/usr/sbin/arp', '/usr/sbin/ndp']:
            args = [cmd, '-na']
            for line in subprocess.run(args, capture_output=True, text=True).stdout.split('\n'):
                if line.upper().replace(':', '-').find(str(mac)) > -1:
                    parts = line.split()
                    if cmd.endswith('ndp'):
                        result['ip6'].append(parts[0])
                    else:
                        result['ip'].append(parts[1].strip('()'))

    print (ujson.dumps(result))
