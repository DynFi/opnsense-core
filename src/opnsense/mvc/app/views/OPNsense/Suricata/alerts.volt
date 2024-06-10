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
}

#buttons {
    text-align: right;
    margin-bottom: 1em;
}

#ifaceselection select {
    min-width: 18em;
    width: auto;
    display: inline-block;
    margin-left: 10px;
}
.fa-times-circle::before {
    color: #ffcc00;
}
#grid-alerts-header { display: none }
#grid-alerts-footer { display: none }
#grid-alerts td .fa { cursor: pointer }
</style>

<script>
$(document).ready(function() {

    $("#grid-alerts").UIBootgrid({
        search: '/api/suricata/alerts/searchItem/{{ uuid }}/',
        selection: false,
        rowCount: -1
    });

    function changeDisplayedIface() {
        window.location = '/ui/suricata/alerts?if=' + $('#ifaceselection select').val();
    }

    $('#ifaceselection select').on('change', changeDisplayedIface);
});

function showRule(sid, gid) {
    ajaxCall("/api/suricata/sidrules/getAnyRule/{{ uuid }}/", {'sid': sid, 'gid': gid}, function(data, status) {
        $('#ruleContent .modal-header h4').text(data.ruleset);
        $('#ruleContent .modal-body textarea').val(atob(data.ruletext));
        $('#ruleContent .modal-body .rulelink').text(data.rulelink);
        $('#ruleContent').modal('show');
    });
}

function encRuleSig(rulegid, rulesid, srcip, ruledescr) {
    if (typeof srcip === 'undefined') {
        var srcip = '';
    }
    if (typeof ruledescr === 'undefined'){
        var ruledescr = '';
    }
    $('#sidid').val(rulesid);
    $('#gen_id').val(rulegid);
    $('#ip').val(srcip);
    $('#descr').val(ruledescr);
}

function submitChanges() {
    var data = {};
    $('#formalert input').each(function () {
        data[this.name] = $(this).val();
    });
    ajaxCall(url="/api/suricata/alerts/update/{{ uuid }}/", sendData=data, callback=function(data, status) {
        if ('message' in data) {
            $('#messageregion').html('<div class="alert alert-info alert-dismissible show" role="alert">' + data.message + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
        }
        $('#grid-alerts').bootgrid('reload');
    });
}

function ajaxResolve(ip_to_resolve) {
    ajaxCall(url="/api/suricata/alerts/resolve/", sendData={'resolve': ip_to_resolve}, callback=function(data, status) {
        if ('message' in data) {
            BootstrapDialog.show({
                title: ip_to_resolve,
                message: '<br />' + data.message + '<br /><br />',
                draggable: true,
                closable: false,
                buttons: [{
                    label: '{{ lang._('Close') }}',
                    action: function(dialog) {
                        dialog.close();
                    }
                }]
            });
        }
    });
}

function ajaxGeoIP(ip_to_check) {
    ajaxCall(url="/api/suricata/alerts/resolve/", sendData={'geoip': ip_to_check}, callback=function(data, status) {
        if ('message' in data) {
            BootstrapDialog.show({
                title: ip_to_check,
                message: '<br />' + data.message + '<br /><br />',
                draggable: true,
                closable: false,
                buttons: [{
                    label: '{{ lang._('Close') }}',
                    action: function(dialog) {
                        dialog.close();
                    }
                }]
            });
        }
    });
}

function clearLogs() {
    BootstrapDialog.show({
        title: "{{ lang._('Please confirm') }}",
        message: 'Are you sure you want to clear Alert Log for {{ iface }}?',
        draggable: true,
        closable: false,
        buttons: [{
            label: '{{ lang._('Cancel') }}',
            action: function(dialog) {
                dialog.close();
            }
        }, {
            label: '{{ lang._('Confirm') }}',
            action: function(dialog) {
                dialog.close();
                ajaxCall(url="/api/suricata/alerts/clear/", sendData={'uuid': '{{ uuid }}'}, callback=function(data, status) {
                    $('#grid-alerts').bootgrid('reload');
                });
            }
        }]
    });
}
</script>

{% if uuid %}

<div id="formalert">
    <input type="hidden" name="{{ csrf_tokenKey }}" value="{{ csrf_token }}" autocomplete="new-password" />
    <input type="hidden" id="mode" name="mode" value="" />
    <input type="hidden" id="sidid" name="sidid" value="" />
    <input type="hidden" id="gen_id" name="gen_id" value="" />
    <input type="hidden" id="ip" name="ip" value="" />
    <input type="hidden" id="descr" name="descr" value="" />
</div>

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
                <div id="buttons">
                    <button class="btn btn-default" type="button" title="Refresh" onclick="$('#grid-alerts').bootgrid('reload');">
                        <span class="icon glyphicon glyphicon-refresh"></span>
                    </button>
                    <a href="/ui/suricata/alerts/download?if={{ iface }}" class="btn btn-default"><i class="fa fa-download"></i> Download logs</a>
                    <a onclick="clearLogs()" class="btn btn-default"><i class="fa fa-trash"></i> Clear logs</a>
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
                <br />
            </div>
        </div>
    </div>
</div>

<div id="ruleContent" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <textarea readonly="readonly" style="width: 100%; max-width: none; height: 15em; background: white"></textarea>
                <div class="rulelink"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ lang._('Close') }}</button>
            </div>
        </div>
    </div>
</div>

{% else %}

<div class="alert alert-warning" role="alert">
    {{ lang._('No Suricata instances defined') }}
</div>

{% endif %}
