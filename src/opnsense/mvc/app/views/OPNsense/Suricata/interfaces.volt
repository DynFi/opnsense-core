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
    #DialogInterface > .modal-dialog {
        width: 90%;
        max-width:1200px;
    }
}

#grid-ifaces i.fa {
    font-size: 125%;
}

.progress td {
  -webkit-animation: blinker 1s infinite;
  -moz-animation: blinker 1s infinite;
  -o-animation: blinker 1s infinite;
  animation: blinker 1s infinite;
}

@keyframes blinker {
    0% {
        background-color: #f2fafe;
    }

    50% {
        background-color: #f2fad0;
    }

    100% {
        background-color: #f2fafe;
    }
}

.icon-pointer { cursor: pointer }

</style>

<script>

var tout;

function suricataIfaceToggle(action, uuid) {
    $('tr[data-row-id="' + uuid + '"]').addClass('progress');
    ajaxGet('/api/suricata/interfaces/toggle/' + action + '/' + uuid, {}, function(data, status) {
        clearTimeout(tout);
        tout = setTimeout(checkRunning, 4000);
    });
}

function checkRunning() {
    ajaxGet('/api/suricata/interfaces/checkRunning', {}, function(data, status) {
        Object.keys(data).forEach(function (uuid) {
            $('tr[data-row-id="' + uuid + '"]').removeClass('progress');
            var status_html;
            if (data[uuid] == 1) {
                status_html = '<i class="fa fa-check-circle text-success icon-primary" title="{{ lang._('suricata is running on this interface') }}"></i>&nbsp;<i class="fa fa-refresh icon-pointer icon-primary text-info" onclick="suricataIfaceToggle(\'restart\', \'' + uuid + '\')" title="Restart suricata on this interface"></i>&nbsp;<i class="fa fa-stop-circle-o icon-pointer icon-primary text-info" onclick="suricataIfaceToggle(\'stop\', \'' + uuid + '\')" title="Stop suricata on this interface"></i>';
            } else {
                status_html = '<i class="fa fa-times-circle text-danger icon-primary" title="{{ lang._('suricata is stopped on this interface') }}"></i>&nbsp;<i class="fa fa-play-circle icon-pointer icon-primary text-info" onclick="suricataIfaceToggle(\'start\', \'' + uuid + '\')" title="Start suricata on this interface"></i>';
            }
            $('tr[data-row-id="' + uuid + '"] td:nth-child(4)').html(status_html);
        });
        clearTimeout(tout);
        tout = setTimeout(checkRunning, 5000);
    });
}

$(document).ready(function() {
    var interface_descriptions = {};

    ajaxGet('/api/diagnostics/interface/getInterfaceNames', {}, function(data, status) {
        interface_descriptions = data;
    });

    $("#grid-ifaces").UIBootgrid({
        search:'/api/suricata/interfaces/searchItem',
        get:'/api/suricata/interfaces/getItem/',
        set:'/api/suricata/interfaces/setItem/',
        add:'/api/suricata/interfaces/addItem/',
        del:'/api/suricata/interfaces/delItem/',
        toggle:'/api/suricata/interfaces/toggleItem/',
        options: {
            formatters: {
                "commands": function (column, row) {
                    return '<button type="button" class="btn btn-xs btn-default command-log bootgrid-tooltip" data-row-logurl="/ui/suricata/log/suricata_' + row.realif + '/suricata"><span class="fa fa-fw fa-list"></span></button> ' +
                        '<button type="button" class="btn btn-xs btn-default command-edit bootgrid-tooltip" data-row-id="' + row.uuid + '"><span class="fa fa-fw fa-pencil"></span></button> ' +
                        '<button type="button" class="btn btn-xs btn-default command-configure bootgrid-tooltip" data-row-configurl="/ui/suricata/configure/iface/' + row.uuid + '"><span class="fa fa-fw fa-cog"></span></button> ' +
                        '<button type="button" class="btn btn-xs btn-default command-copy bootgrid-tooltip" data-row-id="' + row.uuid + '"><span class="fa fa-fw fa-clone"></span></button> ' +
                        '<button type="button" class="btn btn-xs btn-default command-delete bootgrid-tooltip" data-row-id="' + row.uuid + '"><span class="fa fa-fw fa-trash-o"></span></button>';
                }
            }
        }
    });

    if ("{{selected_iface}}" !== "") {
        setTimeout(function() {
            ajaxGet("/api/suricata/interfaces/getInterfaceUUID/{{selected_iface}}", {}, function(data, status) {
                if (data.uuid !== undefined) {
                    var edit_item = $(".command-edit:eq(0)").clone(true);
                    edit_item.data('row-id', data.uuid).click();
                }
            });
        }, 100);
    } else {
        setTimeout(function() {
            var data_get_map = {'frm_interface': "/api/suricata/interfaces/getItem"};
            mapDataToFormUI(data_get_map).done(function () {
                formatTokenizersUI();
                updateServiceControlUI('ids');
                checkRunning();
            });
        }, 100);
    }

    $("#reconfigureAct").SimpleActionButton();
});
</script>

<div class="tab-content content-box">
    <div id="rpz">
        <div class="row">
            <section class="col-xs-12">
                <div class="content-box">
                    <table id="grid-ifaces" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="DialogInterface" data-editAlert="ifaceChangeMessage" data-store-selection="true">
                        <thead>
                            <tr>
                                <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                                <th data-column-id="enabled" data-width="6em" data-type="string" data-formatter="rowtoggle">{{ lang._('Enabled') }}</th>
                                <th data-column-id="iface" data-type="string">{{ lang._('Interface') }}</th>
                                <th data-column-id="status" data-type="string">{{ lang._('Suricata Status') }}</th>
                                <th data-column-id="pmatch" data-type="string">{{ lang._('Pattern Match') }}</th>
                                <th data-column-id="blmode" data-type="string">{{ lang._('Blocking Mode') }}</th>
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
            </section>
        </div>
    </div>
</div>

{{ partial("layout_partials/base_dialog",['fields':formIface,'id':'DialogInterface','label':lang._('Edit interface settings')])}}
