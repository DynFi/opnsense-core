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
.content-box-head {
    color: #FFF;
    background: #b4b7b9;
    font-weight: bold;
    padding: 10px 15px;
    font-size: 120%;
}

.content-box {
    margin-bottom: 20px;
    padding-bottom: 10px;
}

.content-box-main {
    padding-top: 0px;
}

@media (min-width: 768px) {
    #DialogModlist > .modal-dialog {
        width: 90%;
        max-width:1200px;
    }

    #DialogAssignment > .modal-dialog {
        width: 90%;
        max-width:1200px;
    }
}

#frm_DialogModlist .table > tbody > tr > td:nth-child(2) {
    width: 60%;
}

#frm_DialogModlist textarea {
    max-width: unset !important;
    width: 100%;
    min-height: 20em;
}
</style>

<script>

function showSaveAlert(id) {
    $(id).slideDown(1000, function() {
        setTimeout(function() {
            $(id).slideUp(2000);
        }, 2000);
    });
}

$(document).ready(function() {
    var data_get_map_general = { 'formGeneral': "/api/suricata/sidmanagement/get" };

    $('#btnSaveSettings').unbind('click').click(function(){
        $("#btnSaveSettingsProgress").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/suricata/sidmanagement/set", 'formGeneral', function() {
            $("#btnSaveSettingsProgress").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings").blur();
            showSaveAlert('#listChangeMessage');
        }, true, function (data, status) {
            $("#btnSaveSettingsProgress").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings").blur();
        });
    });

    mapDataToFormUI(data_get_map_general).done(function () { formatTokenizersUI(); $('.selectpicker').selectpicker('refresh'); });

    $("#grid-sidmods").UIBootgrid({
        search:'/api/suricata/sidmods/searchItem',
        get:'/api/suricata/sidmods/getItem/',
        set:'/api/suricata/sidmods/setItem/',
        add:'/api/suricata/sidmods/addItem/',
        del:'/api/suricata/sidmods/delItem/'
    });

    if ("{{selected_list}}" !== "") {
        setTimeout(function() {
            ajaxGet("/api/suricata/sidmods/getListUUID/{{selected_list}}", {}, function(data, status) {
                if (data.uuid !== undefined) {
                    var edit_item = $(".command-edit:eq(0)").clone(true);
                    edit_item.data('row-id', data.uuid).click();
                }
            });
        }, 100);
    } else {
        setTimeout(function() {
            var data_get_map = {'frm_passlist': "/api/suricata/sidmods/getItem"};
            mapDataToFormUI(data_get_map).done(function () {
                formatTokenizersUI();
            });
        }, 100);
    }

    $("#grid-sidass").UIBootgrid({
        search:'/api/suricata/sidassign/searchItem',
        get:'/api/suricata/sidassign/getItem/',
        set:'/api/suricata/sidassign/setItem/',
        options: {
            formatters: {
                "commands": function (column, row) {
                    return '<button type="button" class="btn btn-xs btn-default command-edit bootgrid-tooltip" data-row-id="' + row.uuid + '"><span class="fa fa-fw fa-pencil"></span></button> ' +
                    '<button type="button" class="btn btn-xs btn-default command-rebuild bootgrid-tooltip" data-row-url="/api/suricata/interfaces/rebuild/' + row.uuid + '"><span class="fa fa-fw fa-refresh"></span></button>';
                }
            }
        }
    });

    $("#reconfigureAct").SimpleActionButton();
});

</script>

<div class="content-box">
    <header class="content-box-head container-fluid">
        {{ lang._('General Settings') }}
    </header>
    <div class="content-box-main">
        {{ partial("layout_partials/base_form",['fields':formGeneral,'id':'formGeneral'])}}
        <div class="col-md-12">
            <hr />
            <button class="btn btn-primary" id="btnSaveSettings" type="button">
                <b>{{ lang._('Save') }}</b> <i id="btnSaveSettingsProgress"></i>
            </button>
        </div>
    </div>
</div>

<div class="content-box">
    <header class="content-box-head container-fluid">
        {{ lang._('SID Management Configuration Lists') }}
    </header>
    <div class="content-box-main">
        <table id="grid-sidmods" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="DialogModlist" data-editAlert="listChangeMessage" data-store-selection="true">
            <thead>
                <tr>
                    <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                    <th data-column-id="name" data-type="string">{{ lang._('SID Mods List Name') }}</th>
                    <th data-column-id="modtime" data-type="string">{{ lang._('Last Modified Time') }}</th>
                    <th data-column-id="commands" data-width="12em" data-formatter="commands" data-sortable="false">{{ lang._('Commands') }}</th>
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

<div class="content-box">
    <header class="content-box-head container-fluid">
        {{ lang._('Interface SID Management List Assignments') }}
    </header>
    <div class="content-box-main">
        <table id="grid-sidass" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="DialogAssignment" data-editAlert="listChangeMessage" data-store-selection="true">
            <thead>
                <tr>
                    <th data-column-id="iface" data-type="string">{{ lang._('Interface') }}</th>
                    <th data-column-id="sidstateorder" data-type="string">{{ lang._('SID State Order') }}</th>
                    <th data-column-id="enablesidfile" data-type="string">{{ lang._('Enable SID List') }}</th>
                    <th data-column-id="disablesidfile" data-type="string">{{ lang._('Disable SID List') }}</th>
                    <th data-column-id="modifysidfile" data-type="string">{{ lang._('Modify SID List') }}</th>
                    <th data-column-id="dropsidfile" data-type="string">{{ lang._('Drop SID List') }}</th>
                    <th data-column-id="rejectsidfile" data-type="string">{{ lang._('Reject SID List') }}</th>

                    <th data-column-id="commands" data-width="12em" data-formatter="commands" data-sortable="false">{{ lang._('Commands') }}</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<section class="page-content-main">
  <div class="content-box">
    <div class="col-md-12">
        <br/>
        <div id="listChangeMessage" class="alert alert-info" style="display: none" role="alert">
            {{ lang._('Please remember to apply changes with the button below to reconfigure Suricata services') }}
        </div>
        <button class="btn btn-primary" id="reconfigureAct"
                data-endpoint='/api/suricata/interfaces/reconfigure'
                data-label="{{ lang._('Apply') }}"
                data-error-title="{{ lang._('Error reconfiguring Suricata') }}"
                type="button"
        >Apply</button>
        <br/><br/>
    </div>
  </div>
</section>

{{ partial("layout_partials/base_dialog",['fields':formSidmods,'id':'DialogModlist','label':lang._('Edit SID Mods List')])}}

{{ partial("layout_partials/base_dialog",['fields':formSidassignments,'id':'DialogAssignment','label':lang._('Edit SID Assignment')])}}
