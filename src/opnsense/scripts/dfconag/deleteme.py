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
import base64
import json
import logging

logging.basicConfig(filename='/var/log/dfconag.log', level=logging.DEBUG, format='%(asctime)s %(name)s: %(message)s', datefmt='%Y/%m/%d %H:%M:%S')
logger = logging.getLogger('dfconag')

configTree = xml.etree.ElementTree.parse('/conf/config.xml')
configRoot = configTree.getroot()

dfmHost = configRoot.find('./OPNsense/DFConAg/settings/dfmHost').text
dfmSshPort = configRoot.find('./OPNsense/DFConAg/settings/dfmSshPort').text
deviceId = configRoot.find('./OPNsense/DFConAg/settings/deviceId').text

logger.info('Deleting device %s from %s:%s' % (deviceId, dfmHost, dfmSshPort))

inputJson = { 'deviceId': deviceId }
inputData = json.dumps(inputJson).encode('utf-8')

cmd = 'ssh -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p %s register@%s delete-me' % (dfmSshPort, dfmHost)
subprocess.Popen(cmd, shell=True, stdin=subprocess.PIPE).communicate(input=inputData)
