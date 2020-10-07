{#
 # Copyright (C) 2020 Dawid Kujawa <dawid.kujawa@dynfi.com>
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

<script>

function registerDevice(deviceGroups) {
    var options = [];
    for (var i = 0; i < deviceGroups.length; i++) {
        options.push('<option value="' + deviceGroups[i].id + '">' + deviceGroups[i].name + '</option>');
    }
    BootstrapDialog.show({
        title: "{{ lang._('Register device to DynFi Manager') }}",
        message: '<label>{{ lang._('Device group') }}</label><br />' +
            '<select id="device-group-sel">' + options.join('') + '</select><br />' +
            '<label>{{ lang._('Root user password') }}</label><br />' +
            '<input type="password" id="user-pass" required="true" value="" />',
        draggable: true,
        closable: false,
        buttons: [{
            label: '{{ lang._('Cancel') }}',
            action: function(dialog) {
                dialog.close();
                ajaxCall("/api/dfconag/service/rejectKey", {}, function(data, status) {
                    reloadSettings();
                });
            }
        }, {
            label: '{{ lang._('Continue') }}',
            action: function(dialog) {
                var groupId = $('#device-group-sel').val();
                var userPass = $('#user-pass').val();
                dialog.close();
                ajaxCall("/api/dfconag/service/registerDevice", { groupId: groupId, userPass: userPass }, function(data, status) {
                    var result_status = ((status == "success") && (data['status'].toLowerCase().trim() == "ok"));
                    if (result_status) {
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_SUCCESS,
                            title: "{{ lang._('Registered in DynFi Manager') }}",
                            message: "{{ lang._('Successfully registered this device to DynFi Manager. Assigned device UUID is ') }}" + data['message'],
                            draggable: true
                        });
                        reloadSettings();
                    } else {
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_WARNING,
                            title: "{{ lang._('Error connecting to DynFi Manager') }}",
                            message: data['message'],
                            draggable: true
                        });
                        ajaxCall("/api/dfconag/service/rejectKey", {}, function(data, status) {
                            reloadSettings();
                        });
                    }
                });
            }
        }]
    });
}

function confirmKey(key) {
    BootstrapDialog.show({
        title: "{{ lang._('Please confirm SSH keys') }}",
        message: '<div style="padding: 5px; overflow-wrap: break-word">' + key + '</div>',
        draggable: true,
        closable: false,
        buttons: [{
            label: '{{ lang._('Reject') }}',
            action: function(dialog) {
                dialog.close();
                ajaxCall("/api/dfconag/service/rejectKey", {}, function(data, status) {
                    reloadSettings();
                });
            }
        }, {
            label: '{{ lang._('Confirm') }}',
            action: function(dialog) {
                dialog.close();
                ajaxCall("/api/dfconag/service/acceptKey", { key: key }, function(data, status) {
                    var result_status = ((status == "success") && (data['status'].toLowerCase().trim() == "ok"));
                    if (result_status) {
                        var obj = JSON.parse(data['message']);
                        if ((obj) && ('availableDeviceGroups' in obj) && (obj.availableDeviceGroups) && (obj.availableDeviceGroups.length)) {
                            registerDevice(obj.availableDeviceGroups);
                        } else {
                            BootstrapDialog.show({
                                type: BootstrapDialog.TYPE_WARNING,
                                title: "{{ lang._('Error connecting to DynFi Manager') }}",
                                message: 'Missing availableDeviceGroups',
                                draggable: true
                            });
                        }
                    } else {
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_WARNING,
                            title: "{{ lang._('Error connecting to DynFi Manager') }}",
                            message: data['message'],
                            draggable: true
                        });
                        ajaxCall("/api/dfconag/service/rejectKey", {}, function(data, status) {
                            reloadSettings();
                        });
                    }
                });
            }
        }]
    });
}

function reloadSettings() {
    var data_get_map = {'frm_Settings': "/api/dfconag/settings/get"};
    mapDataToFormUI(data_get_map).done(function () {
        formatTokenizersUI();
        updateServiceControlUI('dfconag');
        updateStatus();
    });
}

function updateStatus() {
    ajaxCall(url="/api/dfconag/service/status", sendData={}, callback=function(data, status) {
        updateServiceStatusUI(data['status']);
    });
}

function checkConnection() {
    ajaxCall(url="/api/dfconag/service/connection", sendData={}, callback=function(data, status) {
        $('#statustable tr').last().remove();
        if (data.message.length) {
            var obj = JSON.parse(data['message']);
            if (obj) {
                console.dir(obj);
                $('#statustable tbody')
                    .append('<tr><td>{{ lang._('Connected to') }}</td><td>' + obj.dfmHost + ':' + obj.dfmSshPort + '</td></tr>')
                    .append('<tr><td>{{ lang._('Device ID') }}</td><td>' + obj.deviceId + '</td></tr>')
                    .append('<tr><td>{{ lang._('Main tunnel') }}</td><td>' + obj.mainTunnelPort + ' &rarr; ' + obj.remoteSshPort + '</td></tr>')
                    .append('<tr><td>{{ lang._('DirectView tunnel') }}</td><td>' + obj.dvTunnelPort + ' &rarr; ' + obj.remoteDvPort + '</td></tr>');
            }
        } else {
            $('#statustable tbody').append('<tr><td colspan="2">{{ lang._('This device is not connected to any DynFi Manager') }}</td></tr>');
        }
    });
}


$(document).ready(function() {
    $('#btnSaveSettings').unbind('click').click(function() {
        $("#btnSaveSettingsProgress").addClass("fa fa-spinner fa-pulse");

        saveFormToEndpoint("/api/dfconag/settings/set", 'frm_Settings', function() {

            ajaxCall("/api/dfconag/service/reconfigure", {}, function(data, status) {
                var result_status = ((status == "success") && (data['status'].toLowerCase().trim() == "ok"));
                if (result_status) {
                    if (data['message'].length) {
                        confirmKey(data['message']);
                    }
                } else {
                    BootstrapDialog.show({
                        type: BootstrapDialog.TYPE_WARNING,
                        title: "{{ lang._('Error configuring connection agent') }}",
                        message: data['message'],
                        draggable: true
                    });
                    ajaxCall("/api/dfconag/service/rejectKey", {}, function(data, status) {
                        reloadSettings();
                    });
                }
                $("#btnSaveSettingsProgress").removeClass("fa fa-spinner fa-pulse");
                $("#btnSaveSettings").blur();
                updateServiceControlUI('dfconag');
                updateStatus();
            });

        });
    });

    reloadSettings();

    checkConnection();
});

</script>

<div class="content-box tab-content __mb">
    <div class="table-responsive">
        <table class="table table-striped opnsense_standard_table_form" id="statustable">
            <tbody>
                <tr>
                    <td colspan="2">
                        <strong>{{ lang._('DynFi Connection Agent Status') }}</strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">{{ lang._('Checking...') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-box">
    {{ partial("layout_partials/base_form",['fields':formSettings,'id':'frm_Settings'])}}
    <div class="table-responsive">
        <table class="table table-striped table-condensed table-responsive">
        <tr>
            <td>
                <button class="btn btn-primary" id="btnSaveSettings" type="button">
                    <b>{{ lang._('Apply') }}</b> <i id="btnSaveSettingsProgress"></i>
                </button>
            </td>
        </tr>
        </table>
    </div>
</div>
