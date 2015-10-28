#!/usr/local/bin/python2.7

"""
    Copyright (c) 2015 Deciso B.V. - Ad Schellevis
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
    fetch template files as base64 encoded zipfile
"""
import os
import sys
import ujson
import binascii
import zipfile
import StringIO
from lib import OPNSenseConfig

response = dict()
source_directory = '/usr/local/opnsense/scripts/OPNsense/CaptivePortal/htdocs_default'

output_data = StringIO.StringIO()

with zipfile.ZipFile(output_data, mode='w', compression=zipfile.ZIP_DEFLATED) as zf:
    # overlay user template data
    user_filenames = list()
    if len(sys.argv) > 1:
        # Search for user template, using fileid
        # In this case, we must use the config.xml to retrieve the latest content.
        # When using the generated config, the user experience will be a bit odd (old content after upload)
        cnf = OPNSenseConfig()
        template_content = cnf.get_template(sys.argv[1])
        if template_content is not None:
            try:
                input_data = StringIO.StringIO(template_content.decode('base64'))
                with zipfile.ZipFile(input_data, mode='r', compression=zipfile.ZIP_DEFLATED) as zf_in:
                    for zf_info in zf_in.infolist():
                        user_filenames.append(zf_info.filename)
                        zf.writestr(zf_info.filename, zf_in.read(zf_info.filename))
            except zipfile.BadZipfile:
                # not in zip format
                response['error'] = 'internal xml data not in zip format, user data discarded'
            except binascii.Error:
                # not base64 encoded
                response['error'] = 'internal xml data not in base64 format, user data discarded'

    # read standard template from disk
    for root, dirs, files in os.walk(source_directory):
        for filename in files:
            filename = '%s/%s' % (root, filename)
            output_filename = filename[len(source_directory)+1:]
            if output_filename not in user_filenames:
                zf.writestr(output_filename, open(filename, 'rb').read())

response['payload'] = output_data.getvalue().encode('base64')
response['size'] = len(response['payload'])
print(ujson.dumps(response))
