#!/usr/local/bin/python3

"""
    Copyright (C) 2020 DynFi
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

logging.basicConfig(filename='/var/log/dfconag.log', level=logging.DEBUG, format='%(asctime)s %(name)s: %(message)s', datefmt='%Y/%m/%d %H:%M:%S')
logger = logging.getLogger('dfconag')

configTree = xml.etree.ElementTree.parse('/conf/config.xml')
configRoot = configTree.getroot()

dfmHost = configRoot.find('./OPNsense/DFConAg/settings/dfmHost').text
dfmSshPort = configRoot.find('./OPNsense/DFConAg/settings/dfmSshPort').text

logger.info('Checking connection to %s:%s' % (dfmHost, dfmSshPort))

cmd = '/usr/bin/nc -zv -w 10 %s %s' % (dfmHost, dfmSshPort)

p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
out, err = p.communicate()
out = out.decode("utf-8").strip()
err = err.decode("utf-8").strip()
resp = '%s%s' % (out, err)

print ('OK' if ("succeeded" in resp) else resp.split(':').pop().strip())
