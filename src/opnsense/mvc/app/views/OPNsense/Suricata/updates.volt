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

#logview {
    width: 100%;
    max-width: unset;
    height: 400px;
    background: #FFF;
    color: #444;
    cursor: text;
}

#logcontainer { display: none }
</style>


<script>

var tint = null;

function updateRules(force) {
    const mode = (force) ? 'force' : 'update';
    $('.update-btns button').prop('disabled', true);
    $('.update-btns').append("<span>{{ lang._('Updating rules, please wait...') }}</span>");
    $('#logcontainer').show();
    clearInterval(tint);
    tint = setInterval(checkLog, 1000);
    $.post("/api/suricata/updates/update/" + mode, function(data) {
        $('.update-btns button').prop('disabled', false);
        $('.update-btns span').remove();
        checkLog();
    });
}

function checkLog() {
    var lines = $('#logview').val().split('\n').length - 1;
    var logview = document.getElementById('logview');
    var bottom = false;
    if (logview.scrollHeight <= (logview.scrollTop + 400)) {
        bottom = true;
    }
    $.get("/api/suricata/updates/log/" + lines, function(data) {
        if (data.result.length) {
            $('#logcontainer').show();
        }
        $('#logview').append(data.result);
        if (bottom) {
            logview.scrollTop = logview.scrollHeight;
        }
    });
}


function clearLog() {
    $.post("/api/suricata/updates/clearlog", function(data) {
        $('#logview').val("");
    });
}


$(document).ready(function ($) {

    if ($('#logview').val().length) {
        $('#logcontainer').show();
        var logview = document.getElementById('logview');
        logview.scrollTop = logview.scrollHeight;
    }

    tint = setInterval(checkLog, 5000);
});
</script>

<div class="content-box">
    <header class="content-box-head container-fluid">
        {{ lang._('Installed rule set MD5 signatures') }}
    </header>
    <div class="content-box-main">
        <div class="table-responsive">
            <div  class="col-sm-12">
                <table class="table table-condensed table-hover table-striped table-responsive">
                    <thead>
                        <tr>
                            <th>{{ lang._('Rule Set Name/Publisher') }}</th>
                            <th>{{ lang._('MD5 Signature Hash') }}</th>
                            <th>{{ lang._('MD5 Signature Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for rt in rulesTable %}
                            <tr>
                                <td>{{ rt['name'] }}</td>
                                <td>{{ rt['sighash'] }}</td>
                                <td>{{ rt['sigdate'] }}</td>
                            </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="content-box">
    <header class="content-box-head container-fluid">
        {{ lang._('Update your rule set') }}
    </header>
    <div class="content-box-main">
        <div class="table-responsive">
            <div class="col-sm-12">
                <table class="table table-condensed table-hover table-striped table-responsive">
                    <tbody>
                        <tr><th>{{ lang._('Last update') }}</th><td>{{ last_rule_upd_time }}</td></tr>
                        <tr><th>{{ lang._('Result') }}</th><td>{{ last_rule_upd_status }}</td></tr>
                    </tbody>
                </table>
                <div class="update-btns" style="margin-top: 10px">
                    {% if updatesDisabled %}
                        <button class="btn btn-primary" disabled><i class="fa fa-check"></i> {{ lang._('Update') }}</button>
                        <button class="btn btn-warning" disabled><i class="fa fa-download"></i>{{ lang._('Force') }}</button>
                        <span class="text-danger">{{ lang._('No rule types have been selected for download.') }}</span>
                    {% else %}
                        <button onclick="updateRules(false)" class="btn btn-primary" title="{{ lang._('Check for and apply new update to enabled rule sets') }}"><i class="fa fa-check"></i> {{ lang._('Update') }}</button>
                        <button onclick="updateRules(true)" class="btn btn-warning" title="{{ lang._('Force an update of all enabled rule sets') }}"><i class="fa fa-download"></i>{{ lang._('Force') }}</button>
                    {% endif %}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-box" id="logcontainer">
    <header class="content-box-head container-fluid">
        {{ lang._('Rule set update log') }}
    </header>
    <div class="content-box-main">
        <div class="table-responsive">
            <div class="col-sm-12">
                <textarea id="logview" readonly>{{ log }}</textarea>
                <div class="update-btns" style="margin-top: 10px; text-align: right">
                    <button onclick="clearLog()" class="btn btn-warning"><i class="fa fa-trash"></i> {{ lang._('Clear log') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
