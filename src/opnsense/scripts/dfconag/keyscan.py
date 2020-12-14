#!/usr/local/bin/python3

"""
    Copyright (C) 2020 Dawid Kujawa <dawid.kujawa@dynfi.com>
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
import sys
import os
import xml.etree.ElementTree
import subprocess
import logging

logging.basicConfig(filename='/var/log/dfconag.log', level=logging.DEBUG, format='%(asctime)s %(name)s: %(message)s', datefmt='%b %e %H:%M:%S')
logger = logging.getLogger('dfconag')

configTree = xml.etree.ElementTree.parse('/conf/config.xml')
configRoot = configTree.getroot()

dfmHost = configRoot.find('./OPNsense/DFConAg/settings/dfmHost').text
dfmSshPort = configRoot.find('./OPNsense/DFConAg/settings/dfmSshPort').text

logger.info('Scanning keys on %s:%s' % (dfmSshPort, dfmHost))

cmd1 = '/usr/local/bin/ssh-keyscan -p %s %s' % (dfmSshPort, dfmHost)
cmd2 = '/usr/local/bin/ssh-keyscan -H -p %s %s' % (dfmSshPort, dfmHost)
p1 = subprocess.Popen(cmd1, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
p2 = subprocess.Popen(cmd2, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
out1, err1 = p1.communicate()
out2, err2 = p2.communicate()

out1 = out1.decode("utf-8")
out2 = out2.decode("utf-8")
err1 = err1.decode("utf-8")
err2 = err2.decode("utf-8")

# logger.info(out1.replace('\n', ''))
# logger.info(err1.replace('\n', ''))
# logger.info(out2.replace('\n', ''))
# logger.info(err2.replace('\n', ''))

print ('%s#hashed#\n%s' % (out1, out2))
