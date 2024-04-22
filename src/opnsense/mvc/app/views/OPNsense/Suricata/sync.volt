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
@media (min-width: 768px) {
    #DialogTarget > .modal-dialog {
        width: 90%;
        max-width:1200px;
    }
}
</style>


<script>

$(document).ready(function() {
    var data_get_map_sync = { 'formSync': "/api/suricata/settings/get" };

    $('#btnSaveSettings1').unbind('click').click(function(){
        $("#btnSaveSettingsProgress1").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/suricata/settings/set", 'formSync', function() {
            $("#btnSaveSettingsProgress1").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings1").blur();
        }, true, function (data, status) {
            $("#btnSaveSettingsProgress1").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings1").blur();
        });
    });

    mapDataToFormUI(data_get_map_sync).done(function () { formatTokenizersUI(); $('.selectpicker').selectpicker('refresh'); });

    $("#grid-targets").UIBootgrid({
        search:'/api/suricata/synctargets/searchItem',
        get:'/api/suricata/synctargets/getItem/',
        set:'/api/suricata/synctargets/setItem/',
        add:'/api/suricata/synctargets/addItem/',
        del:'/api/suricata/synctargets/delItem/'
    });

    setTimeout(function() {
        var data_get_map = {'frm_target': "/api/suricata/synctargets/getItem"};
        mapDataToFormUI(data_get_map).done(function () {
            formatTokenizersUI();
        });
    }, 100);
});

</script>


<ul class="nav nav-tabs" data-tabs="tabs" id="maintabs">
    <li class="active"><a data-toggle="tab" href="#settings">{{ lang._('Settings') }}</a></li>
    <li><a data-toggle="tab" href="#targets">{{ lang._('Targets') }}</a></li>
</ul>
<div class="tab-content content-box tab-content">
    <div id="settings" class="tab-pane fade in active">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("layout_partials/base_form",['fields':formSync,'id':'formSync'])}}
            <div class="col-md-12">
                <hr />
                <button class="btn btn-primary" id="btnSaveSettings1" type="button">
                    <b>{{ lang._('Save') }}</b> <i id="btnSaveSettingsProgress1"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="targets" class="tab-pane fade in">
        <div class="content-box" style="padding-bottom: 1.5em;">
            <table id="grid-targets" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="DialogTarget" data-editAlert="listChangeMessage" data-store-selection="true">
                <thead>
                    <tr>
                        <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                        <th data-column-id="varsyncdestinenable" data-width="6em" data-type="string" data-formatter="rowtoggle">{{ lang._('Enabled') }}</th>
                        <th data-column-id="varsyncprotocol" data-type="string">{{ lang._('Protocol') }}</th>
                        <th data-column-id="varsyncipaddress" data-type="string">{{ lang._('IP Address/Hostname') }}</th>
                        <th data-column-id="varsyncport" data-type="string">{{ lang._('Port') }}</th>
                        <th data-column-id="varsyncpassword" data-type="string">{{ lang._('Admin Password') }}</th>
                        <th data-column-id="varsyncsuricatastart" data-width="6em" data-type="string" data-formatter="rowtoggle">{{ lang._('Start Suricata') }}</th>
                        <th data-column-id="commands" data-width="7em" data-formatter="commands" data-sortable="false">{{ lang._('Commands') }}</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td>
                            <button data-action="add" type="button" class="btn btn-xs btn-primary"><span class="fa fa-fw fa-plus"></span></button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{ partial("layout_partials/base_dialog",['fields':formTarget,'id':'DialogTarget','label':lang._('Edit target')])}}
