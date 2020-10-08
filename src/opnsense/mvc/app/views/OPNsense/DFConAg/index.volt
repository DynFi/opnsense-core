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
                    checkConnection();
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
                        checkConnection();
                    } else {
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_WARNING,
                            title: "{{ lang._('Error connecting to DynFi Manager') }}",
                            message: data['message'],
                            draggable: true
                        });
                        ajaxCall("/api/dfconag/service/rejectKey", {}, function(data, status) {
                            reloadSettings();
                            checkConnection();
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
                    checkConnection();
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
                            checkConnection();
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

function disconnectDevice() {
    BootstrapDialog.show({
        title: "{{ lang._('Are you sure?') }}",
        message: '<div style="padding: 5px; overflow-wrap: break-word">{{ lang._('Please confirm disconnecting this device from DynFi Manager') }}</div>',
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
                ajaxCall("/api/dfconag/service/rejectKey", {}, function(data, status) {
                    reloadSettings();
                    checkConnection();
                });
            }
        }]
    });
}


function checkConnection() {
    $('#statustable tbody').append('<tr class="dfcinf"><td colspan="2">{{ lang._('Checking...') }}</td></tr>');
    $('#btnConnect').hide();
    $('#btnDisconnect').hide();
    ajaxCall(url="/api/dfconag/service/connection", sendData={}, callback=function(data, status) {
        $('.dfcinf').remove();
        var obj = null;
        if (data.message.length) {
            if (data.message.includes('{')) {
                obj = JSON.parse(data.message);
            } else {
                BootstrapDialog.show({
                    type: BootstrapDialog.TYPE_WARNING,
                    title: "{{ lang._('Error checking connection to DynFi Manager') }}",
                    message: data.message,
                    draggable: true
                });
            }
        }
        if (obj) {
            $('#statustable tbody')
                .append('<tr class="dfcinf"><td>{{ lang._('Connected to') }}</td><td>' + obj.dfmHost + ':' + obj.dfmSshPort + '</td></tr>')
                .append('<tr class="dfcinf"><td>{{ lang._('Device ID') }}</td><td>' + obj.deviceId + '</td></tr>')
                .append('<tr class="dfcinf"><td>{{ lang._('Main tunnel') }}</td><td>' + obj.mainTunnelPort + ' &rarr; ' + obj.remoteSshPort + '</td></tr>')
                .append('<tr class="dfcinf"><td>{{ lang._('DirectView tunnel') }}</td><td>' + obj.dvTunnelPort + ' &rarr; ' + obj.remoteDvPort + '</td></tr>');
            $('#btnDisconnect').show();
            $('#btnDisconnect').unbind('click').click(function() {
                disconnectDevice();
            });
        } else {
            $('#statustable tbody').append('<tr class="dfcinf"><td colspan="2">{{ lang._('This device is not connected to any DynFi Manager') }}</td></tr>');
            $('#btnConnect').show();
            $('#btn_Settings_save').unbind('click').click(function() {
                $('#Settings').modal('hide');

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
                                checkConnection();
                            });
                        }
                        updateServiceControlUI('dfconag');
                        updateStatus();
                    });

                });
            });
        }
    });
}


$(document).ready(function() {
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
            </tbody>
        </table>
    </div>
    <div class="table-responsive">
        <table class="table table-striped opnsense_standard_table_form">
            <tbody>
                <tr>
                    <td>
                        <button class="btn btn-primary" id="btnDisconnect" type="button" style="display: none">
                            {{ lang._('Disconnect') }}
                        </button>
                        <button class="btn btn-primary" id="btnConnect" type="button" style="display: none" data-toggle="modal" data-target="#Settings">
                            {{ lang._('Connect') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{ partial("layout_partials/base_dialog",['fields':formSettings,'id':'Settings','label':lang._('Setup connection')])}}

