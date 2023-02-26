#!/usr/local/bin/python3

"""
    Copyright (C) 2023 DynFi
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

import os
import xml.etree.ElementTree

configTree = xml.etree.ElementTree.parse('/conf/config.xml')
configRoot = configTree.getroot()

RPZ_FILES_DIR = '/var/unbound/'
RPZ_FILES_EXT = '.rpz.dynfi'

for node in configRoot.find('./OPNsense/RPZ/FilteringList/lists'):
    if node.find('./enabled').text == '1':
        categories = node.find('./categories').text.split(',')
        for category in categories:
            rpz_file_path = RPZ_FILES_DIR + category + RPZ_FILES_EXT
            exists = os.path.isfile(rpz_file_path)
            print (category + ':' + ('1' if exists else '0'))

