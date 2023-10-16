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
#buttons {
    text-align: right;
    margin-bottom: 1em;
}

.fa-times-circle::before {
    color: #ffcc00;
}
#grid-blocks-header { display: none }
#grid-blocks-footer { display: none }
#grid-blocks td .fa { cursor: pointer }
</style>

<script>
$(document).ready(function() {

    $("#grid-blocks").UIBootgrid({
        search: '/api/suricata/blocks/searchItem/',
        selection: false,
        rowCount: -1
    });

});


</script>


<div class="content-box">
    <div class="content-box-main">
        <div class="table-responsive">
            <div  class="col-sm-12">
                <div id="buttons">
                    <button class="btn btn-default" type="button" title="Refresh" onclick="$('#grid-blocks').bootgrid('reload');">
                        <span class="icon glyphicon glyphicon-refresh"></span>
                    </button>
                    <!--
                    <a href="/ui/suricata/blocks/download?if={{ iface }}" class="btn btn-default"><i class="fa fa-download"></i> Download logs</a>
                    <a onclick="clearLogs()" class="btn btn-default"><i class="fa fa-trash"></i> Clear logs</a>
                    -->
                </div>
                <table id="grid-blocks" class="table table-condensed table-hover table-striped table-responsive" data-store-selection="true">
                    <thead>
                        <tr>
                            <th data-column-id="id" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                            <th data-column-id="ip" data-type="string">{{ lang._('Blocked IP') }}</th>
                            <th data-column-id="descr" data-formatter="html">{{ lang._('Alert Description') }}</th>
                            <th data-column-id="remove" data-formatter="html">{{ lang._('Remove') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <br />
            </div>
        </div>
    </div>
</div>
