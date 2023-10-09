{#
 # Copyright (C) 2022 DynFi
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without modification,
 # are permitted provided that the following conditions are met:
 #
 # 1. Redistributions of source code must retain the above copyright notice,
 #    this list of conditions and the following disclaimer.
 #
 # 2. Redistributions in binary form must reproduce the above copyright notice,
 #    this list of conditions and the following disclaimer in the documentation
 #    and/or other materials provided with the distribution.
 #
 # THIS SOFTWARE IS PROVIDED “AS IS” AND ANY EXPRESS OR IMPLIED WARRANTIES,
 # INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 # AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 # AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 # OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 # SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 # INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 # CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 # ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 # POSSIBILITY OF SUCH DAMAGE.
 #}


<style>
#ifaceselection {
    display: inline-block;
    float: left;
}

#ifaceselection select {
    min-width: 18em;
    width: auto;
    display: inline-block;
    margin-left: 10px;
}
</style>

<script>
$(document).ready(function() {

    $("#grid-alerts").UIBootgrid({
        search: '/api/suricata/alerts/searchItem/{{ uuid }}/',
        selection: false,
        rowCount: -1
    });

    function changeDisplayedIface() {
        window.location = '/ui/suricata/alerts/?if=' + $('#ifaceselection select').val();
    }

    $('#ifaceselection select').on('change', changeDisplayedIface);
});
</script>


<div class="content-box">
    <div class="content-box-main">
        <div class="table-responsive">
            <div  class="col-sm-12">
                <div id="ifaceselection">
                        <label for="iface">{{ lang._('Instance') }}</label>
                        <select name="iface" id="iface">
                            {% for ifn, realif in ifaces %}
                                <option value="{{ realif }}" {% if iface==realif %}selected="selected"{% endif %}>{{ ifn }}</option>
                            {% endfor %}
                        </select>
                    </div>
                <table id="grid-alerts" class="table table-condensed table-hover table-striped table-responsive" data-store-selection="true">
                    <thead>
                        <tr>
                            <th data-column-id="id" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                            <th data-column-id="date" data-type="string">{{ lang._('Date') }}</th>
                            <th data-column-id="action" data-formatter="html">{{ lang._('Action') }}</th>
                            <th data-column-id="pri" data-formatter="html">{{ lang._('Pri') }}</th>
                            <th data-column-id="proto" data-formatter="html">{{ lang._('Proto') }}</th>
                            <th data-column-id="class" data-formatter="html">{{ lang._('Class') }}</th>
                            <th data-column-id="src" data-formatter="html">{{ lang._('Src') }}</th>
                            <th data-column-id="sport" data-formatter="html">{{ lang._('SPort') }}</th>
                            <th data-column-id="dst" data-formatter="html">{{ lang._('dst') }}</th>
                            <th data-column-id="dport" data-formatter="html">{{ lang._('DPort') }}</th>
                            <th data-column-id="gidsid" data-formatter="html">{{ lang._('GID:SID') }}</th>
                            <th data-column-id="description" data-formatter="html">{{ lang._('Description') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

