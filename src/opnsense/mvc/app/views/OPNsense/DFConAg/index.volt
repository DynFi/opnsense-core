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

var __currentStatus = {};

function registerDevice(options) {
    var deviceGroups = options.availableDeviceGroups;
    var usernames = options.usernames;
    var gOptions = [];
    for (var i = 0; i < deviceGroups.length; i++) {
        gOptions.push('<option value="' + deviceGroups[i].id + '">' + deviceGroups[i].name + '</option>');
    }
    var uOptions = [];
    for (var i = 0; i < usernames.length; i++) {
        uOptions.push('<option value="' + usernames[i] + '">' + usernames[i] + '</option>');
    }
    BootstrapDialog.show({
        title: "{{ lang._('Register device to DynFi Manager') }}",
        message: '<table class="table table-striped table-condensed"><tbody>' +
            '<tr><td><div class="control-label"><b>{{ lang._('Device group') }}</b></div></td><td><select id="device-group-sel">' + gOptions.join('') + '</select></td></tr>' +
            '<tr><td><div class="control-label"><b>{{ lang._('SSH user') }}</b></div></td><td><select id="user-name">' + uOptions.join('') + '</select></td></tr>' +
            '<tr><td><div class="control-label"><b>{{ lang._('SSH password') }}</b></div></td><td><input type="password" id="user-pass" value="" />' +
            '<small>{{ lang._('Leave this field empty for key-based authentication') }}</small></td></tr>' +
            '</tbody></table>',
        draggable: true,
        closable: false,
        buttons: [{
            label: '{{ lang._('Cancel') }}',
            action: function(dialog) {
                dialog.close();
                __disconnect(false);
            }
        }, {
            label: '{{ lang._('Continue') }}',
            action: function(dialog) {
                var groupId = $('#device-group-sel').val();
                var userName = $('#user-name').val();
                var userPass = $('#user-pass').val();
                dialog.close();
                ajaxCall("/api/dfconag/service/registerDevice", { groupId: groupId, userName: userName, userPass: userPass }, function(data, status) {
                    var result_status = ((status == "success") && (data['status'].toLowerCase().trim() == "ok"));
                    if (result_status) {
                        $('#btnConnect').html("{{ lang._('Connect') }}");
                        $('#btnConnect').prop('disabled', null);
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_SUCCESS,
                            title: "{{ lang._('Registered in DynFi Manager') }}",
                            message: "{{ lang._('Successfully registered this device to DynFi Manager. Assigned device UUID is ') }}" + data['message'],
                            draggable: true
                        });
                        checkStatus();
                    } else {
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_WARNING,
                            title: "{{ lang._('Error connecting to DynFi Manager') }}",
                            message: data['message'],
                            draggable: true
                        });
                        __disconnect(false);
                    }
                });
            }
        }]
    });
}


function getAddOptions() {
    BootstrapDialog.show({
        title: "{{ lang._('Connect to DynFi Manager') }}",
        message: '<table class="table table-striped table-condensed"><tbody>' +
            '<tr><td><div class="control-label"><b>{{ lang._('DynFi Manager username') }}</b></div></td><td><input type="text" id="dfm-username" required="true" value="" /></td></tr>' +
            '<tr><td><div class="control-label"><b>{{ lang._('DynFi Manager password') }}</b></div></td><td><input type="password" id="dfm-password" required="true" value="" /></td></tr>' +
            '</tbody></table>',
        draggable: true,
        closable: false,
        buttons: [{
            label: '{{ lang._('Cancel') }}',
            action: function(dialog) {
                dialog.close();
                __disconnect(false);
            }
        }, {
            label: '{{ lang._('Continue') }}',
            action: function(dialog) {
                var username = $('#dfm-username').val();
                var password = $('#dfm-password').val();
                dialog.close();
                ajaxCall("/api/dfconag/service/getAddOptions", { username: username, password: password }, function(data, status) {
                    var result_status = ((status == "success") && (data['status'].toLowerCase().trim() == "ok"));
                    if (result_status) {
                        var obj = JSON.parse(data['message']);
                        if ((obj)
                            && ('availableDeviceGroups' in obj)
                            && (obj.availableDeviceGroups)
                            && (obj.availableDeviceGroups.length)
                            && ('usernames' in obj)
                            && (obj.usernames)
                            && (obj.usernames.length)
                        ) {
                                registerDevice(obj);
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
                        __disconnect(false);
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
                __disconnect(false);
            }
        }, {
            label: '{{ lang._('Confirm') }}',
            action: function(dialog) {
                dialog.close();
                ajaxCall("/api/dfconag/service/acceptKey", { key: key }, function(data, status) {
                    var result_status = ((status == "success") && (data['status'].toLowerCase().trim() == "ok"));
                    if (result_status) {
                        getAddOptions();
                    } else {
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_WARNING,
                            title: "{{ lang._('Error connecting to DynFi Manager') }}",
                            message: data['message'],
                            draggable: true
                        });
                        __disconnect(false);
                    }
                });
            }
        }]
    });
}


function updateStatus() {
    ajaxCall(url="/api/dfconag/service/status", sendData={}, callback=function(data, status) {
        updateServiceStatusUI(data['status']);
    });
}


function __disconnect(d) {
    $('#btnConnect').html("{{ lang._('Connect') }}");
    $('#btnConnect').prop('disabled', null);
    ajaxCall("/api/dfconag/service/disconnect", { 'delete': d }, function(data, status) {
        checkStatus();
    });
}


function disconnectDevice() {
    BootstrapDialog.show({
        title: "{{ lang._('Are you sure?') }}",
        message: '<div style="padding: 5px; overflow-wrap: break-word">{{ lang._('Please confirm disconnecting this device from DynFi Manager') }}<br /><br />'
            + '<input type="checkbox" id="deletealso" checked="checked" style="position: relative; top: 1px" />&nbsp;{{ lang._('Also delete this device from DynFi Manager') }}</div>',
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
                var checked = $('#deletealso').is(':checked');
                dialog.close();
                __disconnect(checked);
            }
        }]
    });
}


function resetAgent() {
    BootstrapDialog.show({
        title: "{{ lang._('Are you sure?') }}",
        message: '<div style="padding: 5px; overflow-wrap: break-word">{{ lang._('Please confirm resetting all Connection Agent settings. This will clear all agent configuration and SSH keys.') }}</div>',
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
                ajaxCall("/api/dfconag/service/reset", {}, function(data, status) {
                    var result_status = ((status == "success") && (data['status'].toLowerCase().trim() == "ok"));
                    if (result_status) {
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_SUCCESS,
                            title: "{{ lang._('Connection Agent reset') }}",
                            message: "{{ lang._('Connection Agent configuration was cleared') }}",
                            draggable: true
                        });
                    } else {
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_WARNING,
                            title: "{{ lang._('Error resetting Connection Agent configuration') }}",
                            message: data['message'],
                            draggable: true
                        });
                    }
                    checkStatus();
                });
            }
        }]
    });
}


function decodeToken() {
    var host = '';
    var port = '';
    var token = $('#dfm-jwt').val();
    if (!token.length) {
        $('#tokenmark').hide();
        var host = $('#dfm-host').val();
        var port = $('#dfm-host').val();
        if (!host) {
            host = ((__currentStatus) && ('dfmHost' in __currentStatus)) ? __currentStatus.dfmHost : '';
            $('#dfm-host').val(host);
        }
        if (!port) {
            port = ((__currentStatus) && ('dfmSshPort' in __currentStatus)) ? __currentStatus.dfmSshPort : '';
            $('#dfm-port').val(port);
        }
        checkConnectInputs();
        return;
    }
    var tArr = token.split('.');
    if (tArr.length == 3) {
        var base64 = tArr[1].replace(/-/g, '+').replace(/_/g, '/');
        try {
            var payloadJson = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            var payload = JSON.parse(payloadJson);
            host = payload.adr;
            port = payload.prt;
        } catch (e) {}
    }
    if ((host) && (port)) {
        $('#tokenmark').attr("class", "fa fa-check");
        $('#tokenmark').css("color", "#080");
    } else {
        $('#tokenmark').attr("class", "fa fa-times");
        $('#tokenmark').css("color", "#D00");
    }
    $('#tokenmark').show();
    $('#dfm-host').val(host);
    $('#dfm-port').val(port);
    checkConnectInputs();
}


function checkConnectInputs() {
    var host = $('#dfm-host').val();
    var port = $('#dfm-host').val();
    if ((host) && (port)) {
        $('#connect-btn-connect').prop('disabled', false);
    } else {
        $('#connect-btn-connect').prop('disabled', true);
    }
}


function connectDevice() {
    $('#btnConnect').html("{{ lang._('Connecting...') }}");
    $('#btnConnect').prop('disabled', true);
    var dfmHost = ((__currentStatus) && ('dfmHost' in __currentStatus)) ? __currentStatus.dfmHost : '';
    var dfmPort = ((__currentStatus) && ('dfmSshPort' in __currentStatus)) ? __currentStatus.dfmSshPort : '';
    BootstrapDialog.show({
        title: "{{ lang._('Connect to DynFi Manager') }}",
        message: '<table class="table table-striped table-condensed"><tbody>' +
            '<tr><td><div class="control-label"><b>{{ lang._('JWT token') }} <i id="tokenmark" style="display: none"></i></b></div></td><td><textarea onchange="decodeToken()" onkeyup="decodeToken()" onmouseup="decodeToken()" id="dfm-jwt" style="width: 100%; height: 8em"></textarea></td></tr>' +
            '<tr><td><div class="control-label"><b>{{ lang._('DynFi Manager host') }}</b></div></td><td><input onchange="checkConnectInputs()" onkeyup="checkConnectInputs()" onmouseup="checkConnectInputs()" type="text" id="dfm-host" required="true" value="' + dfmHost + '" /></td></tr>' +
            '<tr><td><div class="control-label"><b>{{ lang._('DynFi Manager SSH port') }}</b></div></td><td><input onchange="checkConnectInputs()" onkeyup="checkConnectInputs()" onmouseup="checkConnectInputs()" type="number" min="1" max="65535" id="dfm-port" required="true" value="' + dfmPort + '" /></td></tr>' +
            '</tbody></table>',
        draggable: true,
        closable: false,
        buttons: [{
            label: '{{ lang._('Cancel') }}',
            action: function(dialog) {
                dialog.close();
                __disconnect(false);
            }
        }, {
            id: 'connect-btn-connect',
            label: '{{ lang._('Connect') }}',
            action: function(dialog) {
                var dfmHost = $('#dfm-host').val();
                var dfmPort = $('#dfm-port').val();
                var dfmJwt = $('#dfm-jwt').val();
                dialog.close();
                ajaxCall("/api/dfconag/service/connect", { dfmHost: dfmHost, dfmPort: dfmPort, dfmJwt: dfmJwt }, function(data, status) {
                    var result_status = ((status == "success") && (data['status'].toLowerCase().trim() == "ok"));
                    if (result_status) {
                        var arr = data.message.split(';');
                        if (arr[0] == 'RECONNECTED') {
                            BootstrapDialog.show({
                                type: BootstrapDialog.TYPE_SUCCESS,
                                title: "{{ lang._('Reconnected to DynFi Manager') }}",
                                message: "{{ lang._('Successfully reconnected to DynFi Manager. Assigned device UUID is ') }}" + arr[1],
                                draggable: true
                            });
                            checkStatus();
                        } else if (data['message'] == 'CONFIRMED') {
                            getAddOptions();
                        } else {
                            confirmKey(data['message']);
                        }
                    } else {
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_WARNING,
                            title: "{{ lang._('Error connecting to DynFi Manager') }}",
                            message: data['message'],
                            draggable: true
                        });
                        __disconnect(false);
                    }
                });
            }
        }]
    });
}


function checkStatus() {
    $('#btnConnect').hide();
    $('#btnDisconnect').hide();
    $('#btnReset').hide();
    $('#statustable tbody').append('<tr class="dfcinf"><td colspan="2">{{ lang._('Checking status...') }}</td></tr>');
    ajaxCall(url="/api/dfconag/service/checkStatus", sendData={}, callback=function(data, status) {
        $('.dfcinf').remove();
        if (data.message.includes('{')) {
            __currentStatus = JSON.parse(data.message);
        } else {
            BootstrapDialog.show({
                type: BootstrapDialog.TYPE_WARNING,
                title: "{{ lang._('Error checking connection to DynFi Manager') }}",
                message: data.message,
                draggable: true
            });
        }
        if ((__currentStatus) && ('enabled' in __currentStatus) && (__currentStatus.enabled == '1')) {
            $('#statustable tbody')
                .append('<tr class="dfcinf"><td>{{ lang._('Connected to') }}</td><td>' + __currentStatus.dfmHost + ':' + __currentStatus.dfmSshPort + '</td></tr>')
                .append('<tr class="dfcinf"><td>{{ lang._('Device ID') }}</td><td>' + __currentStatus.deviceId + '</td></tr>')
                .append('<tr class="dfcinf"><td>{{ lang._('Main tunnel') }}</td><td>' + __currentStatus.mainTunnelPort + ' &rarr; ' + __currentStatus.remoteSshPort + '</td></tr>')
                .append('<tr class="dfcinf"><td>{{ lang._('DirectView tunnel') }}</td><td>' + __currentStatus.dvTunnelPort + ' &rarr; ' + __currentStatus.remoteDvPort + '</td></tr>');
            $('#btnDisconnect').show();
            $('#btnDisconnect').unbind('click').click(function() {
                disconnectDevice();
            });
        } else {
            $('#statustable tbody').append('<tr class="dfcinf"><td colspan="2">{{ lang._('This device is not connected to any DynFi Manager') }}</td></tr>');
            $('#btnConnect').show();
            $('#btnReset').show();
        }
        updateStatus();
    });
}


function runPreTest() {
    $('#statustable tbody').append('<tr class="dfcinf"><td colspan="2">{{ lang._('Checking system configuration...') }}</td></tr>');
    ajaxCall(url="/api/dfconag/service/pretest", sendData={}, callback=function(data, status) {
        $('.dfcinf').remove();
        if (data.message == 'OK') {
            checkStatus();
        } else {
            $('#btnConnect').hide();
            $('#btnDisconnect').hide();
            $('#buttons-table').hide();
            if (data.message == 'SSH_NOT_ENABLED') {
                $('#statustable tbody').append('<tr class="dfcinf"><td colspan="2">{{ lang._('DynFi Connection Agent requires SSH enabled') }}</td></tr>');
            }
            if (data.message == 'AUTOSSH_MISSING') {
                $('#statustable tbody').append('<tr class="dfcinf"><td colspan="2">{{ lang._('Can not use DynFi Connection Agent: autossh command not found. Please install autossh first.') }}</td></tr>');
            }
            ajaxCall("/api/dfconag/service/disconnect", {}, function(data, status) {
                checkStatus();
            });
        }
    });
}


$(document).ready(function() {
    $('#btnConnect').click(connectDevice);
    $('#btnDisconnect').click(disconnectDevice);
    $('#btnReset').click(resetAgent);
    runPreTest();
});

</script>

<div class="content-box tab-content __mb">
    <div class="table-responsive">
        <table class="table table-striped opnsense_standard_table_form" id="statustable">
            <tbody>
                <tr>
                    <td colspan="2">
                        <strong>{{ lang._('DynFi Connection Agent Status') }} <small style="color: #F88">alpha version</small></strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="table-responsive" id="buttons-table">
        <table class="table table-striped opnsense_standard_table_form">
            <tbody>
                <tr>
                    <td>
                        <button class="btn btn-primary" id="btnDisconnect" type="button" style="display: none">
                            {{ lang._('Disconnect') }}
                        </button>
                        <button class="btn btn-primary" id="btnConnect" type="button" style="display: none">
                            {{ lang._('Connect') }}
                        </button>
                        <button class="btn btn-warning" id="btnReset" type="button" style="display: none; float: right">
                            {{ lang._('Reset') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
