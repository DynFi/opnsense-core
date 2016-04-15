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
    data aggregator type
"""
from lib.aggregate import BaseFlowAggregator

class FlowDstPortTotals(BaseFlowAggregator):
    """ collect interface totals
    """
    target_filename = '/var/netflow/dst_port_%06d.sqlite'
    agg_fields = ['if', 'protocol', 'dst_port']

    @classmethod
    def resolutions(cls):
        """
        :return: list of sample resolutions
        """
        return  [30, 300, 3600, 86400]

    @classmethod
    def history_per_resolution(cls):
        """
        :return: dict sample resolution / expire time (seconds)
        """
        # only save daily totals for a longer period of time, we probably only want to answer questions like
        # "top usage over the last 30 seconds, 5 minutes, etc.."
        return  {30: 300,
                 300: 3600,
                 3600: 86400,
                 86400: cls.seconds_per_day(365)
                 }

    def __init__(self, resolution):
        """
        :param resolution: sample resultion (seconds)
        :return: None
        """
        super(FlowDstPortTotals, self).__init__(resolution)

    def add(self, flow):
        # most likely service (destination) port
        flow['dst_port'] = min(flow['dst_port'], flow['src_port'])
        flow['if'] = flow['if_in']
        super(FlowDstPortTotals, self).add(flow)
        flow['if'] = flow['if_out']
        super(FlowDstPortTotals, self).add(flow)
