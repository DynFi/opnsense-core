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

class FlowInterfaceTotals(BaseFlowAggregator):
    """ collect interface totals
    """
    target_filename = '/var/netflow/interface_%06d.sqlite'
    agg_fields = ['if_in', 'if_out']

    @classmethod
    def resolutions(cls):
        """
        :return: list of sample resolutions
        """
        return  [60, 60*5, 60*60]

    @classmethod
    def history_per_resolution(cls):
        """
        :return: dict sample resolution / expire time (seconds)
        """
        return  {60: cls.seconds_per_day(1), 60*5: cls.seconds_per_day(31), 60*60: cls.seconds_per_day(365)}

    def __init__(self, resolution):
        """
        :param resolution: sample resultion (seconds)
        :return: None
        """
        super(FlowInterfaceTotals, self).__init__(resolution)
