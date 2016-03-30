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
    returns the aggregated output of flowctl (netflow)
"""
import tempfile
import subprocess
import os
import sys
import ujson

if __name__ == '__main__':
    result = dict()
    netflow_nodes = list()
    with tempfile.NamedTemporaryFile() as output_stream:
        subprocess.call(['/usr/sbin/ngctl', 'list'], stdout=output_stream, stderr=open(os.devnull, 'wb'))
        output_stream.seek(0)
        for line in output_stream.read().split('\n'):
            if line.find('netflow_') > -1:
                netflow_nodes.append(line.split()[1])

    for netflow_node in netflow_nodes:
        node_stats = {'SrcIPaddress': list(), 'DstIPaddress': list(), 'Pkts': 0}
        with tempfile.NamedTemporaryFile() as output_stream:
            subprocess.call(['/usr/sbin/flowctl', '%s:' % netflow_node, 'show'],
                            stdout=output_stream, stderr=open(os.devnull, 'wb'))
            output_stream.seek(0)
            for line in output_stream.read().split('\n'):
                fields = line.split()
                if len(fields) >= 8 and fields[0] != 'SrcIf':
                    node_stats['Pkts'] += int(fields[7])
                    if fields[1] not in node_stats['SrcIPaddress']:
                        node_stats['SrcIPaddress'].append(fields[1])
                    if fields[3] not in node_stats['DstIPaddress']:
                        node_stats['DstIPaddress'].append(fields[3])
        result[netflow_node] = {'Pkts': node_stats['Pkts'],
                                'if': netflow_node[8:],
                                'SrcIPaddresses': len(node_stats['SrcIPaddress']),
                                'DstIPaddresses': len(node_stats['DstIPaddress'])}

    # handle command line argument (type selection)
    if len(sys.argv) > 1 and 'json' in sys.argv:
        print(ujson.dumps(result))
    else:
        print ('[contents of netflow cache]')
        for netflow_node in result:
            print ('node : %s' % netflow_node)
            print ('  #source addresses        : %d' % result[netflow_node]['SrcIPaddresses'])
            print ('  #destination addresses   : %d' % result[netflow_node]['DstIPaddresses'])
            print ('  #packets                 : %d' % result[netflow_node]['Pkts'])
