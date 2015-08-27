#!/usr/local/bin/python2.7

"""
    Copyright (c) 2015 Ad Schellevis
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
    returns raw pf info (optional packed in a json container)
"""
import collections
import tempfile
import subprocess
import os
import sys
import ujson

result=collections.OrderedDict()
for stattype in ['info', 'memory', 'timeouts', 'Interfaces']:
    with tempfile.NamedTemporaryFile() as output_stream:
        subprocess.call(['/sbin/pfctl','-vvs'+stattype], stdout=output_stream, stderr=open(os.devnull, 'wb'))
        output_stream.seek(0)
        result[stattype] = output_stream.read().strip()

# handle command line argument (type selection)
if len(sys.argv) > 1 and sys.argv[1] == 'json':
    print(ujson.dumps(result))
else:
    # output plain
    for stattype in result:
        print ('------------------------- %s -------------------------' % (stattype) )
        print (result[stattype])
