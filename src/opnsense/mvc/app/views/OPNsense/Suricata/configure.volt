{#
 # Copyright (C) 2023 DynFi
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
window.onload = function() {
    var url = document.location.toString();
    if (url.match('#')) {
        $('.nav-tabs a[href="#' + url.split('#')[1] + '"]').tab('show');
    } else {
        $('.nav-tabs a[href="#categories"]').tab('show');
    }
    $('.nav-tabs a[href="#' + url.split('#')[1] + '"]').on('shown', function (e) {
        window.location.hash = e.target.hash;
    });
}

$(document).ready(function() {
    var data_get_map_flow = { 'formFlow': "/api/suricata/interfaces/getItem/{{ uuid }}" };
    var data_get_map_parsers = { 'formParsers': "/api/suricata/interfaces/getItem/{{ uuid }}" };
    var data_get_map_variables = { 'formVariables': "/api/suricata/interfaces/getItem/{{ uuid }}" };

    $('#btnSaveSettings1').unbind('click').click(function() {
        $("#btnSaveSettingsProgress1").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/suricata/interfaces/setItem/{{ uuid }}", 'formFlow', function() {
            $("#btnSaveSettingsProgress1").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings1").blur();
        }, true, function (data, status) {
            $("#btnSaveSettingsProgress1").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings1").blur();
        });
    });

    $('#btnSaveSettings2').unbind('click').click(function() {
        $("#btnSaveSettingsProgress2").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/suricata/interfaces/setItem/{{ uuid }}", 'formParsers', function() {
            $("#btnSaveSettingsProgress2").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings2").blur();
        }, true, function (data, status) {
            $("#btnSaveSettingsProgress2").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings2").blur();
        });
    });

    $('#btnSaveSettings3').unbind('click').click(function() {
        $("#btnSaveSettingsProgress3").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/suricata/interfaces/setItem/{{ uuid }}", 'formVariables', function() {
            $("#btnSaveSettingsProgress3").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings3").blur();
        }, true, function (data, status) {
            $("#btnSaveSettingsProgress3").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings3").blur();
        });
    });

    mapDataToFormUI(data_get_map_flow).done(function () { formatTokenizersUI(); $('.selectpicker').selectpicker('refresh'); });
    mapDataToFormUI(data_get_map_parsers).done(function () { formatTokenizersUI(); $('.selectpicker').selectpicker('refresh'); });
    mapDataToFormUI(data_get_map_variables).done(function () { formatTokenizersUI(); $('.selectpicker').selectpicker('refresh'); });
});
</script>

<ul class="nav nav-tabs" data-tabs="tabs" id="maintabs">
    <li><a data-toggle="tab" href="#categories">{{ lang._('Categories') }}</a></li>
    <li><a data-toggle="tab" href="#rules">{{ lang._('Rules') }}</a></li>
    <li><a data-toggle="tab" href="#flow">{{ lang._('Flow/Stream') }}</a></li>
    <li><a data-toggle="tab" href="#parsers">{{ lang._('App Parsers') }}</a></li>
    <li><a data-toggle="tab" href="#variables">{{ lang._('Server and Port Variables') }}</a></li>
</ul>
<div class="tab-content content-box tab-content">
    <div id="categories" class="tab-pane fade in">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("OPNsense/Suricata/categories") }}
        </div>
    </div>
    <div id="rules" class="tab-pane fade in">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("OPNsense/Suricata/rules") }}
        </div>
    </div>
    <div id="flow" class="tab-pane fade in">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("layout_partials/base_form",['fields':formFlow,'id':'formFlow'])}}
            <table class="table table-striped opnsense_standard_table_form">
                <tbody>
                    <tr><td>
                        <button class="btn btn-primary" id="btnSaveSettings1" type="button">
                            <b>{{ lang._('Save') }}</b> <i id="btnSaveSettingsProgress1"></i>
                        </button>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div id="parsers" class="tab-pane fade in">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("layout_partials/base_form",['fields':formParsers,'id':'formParsers'])}}
            <table class="table table-striped opnsense_standard_table_form">
                <tbody>
                    <tr><td>
                        <button class="btn btn-primary" id="btnSaveSettings2" type="button">
                            <b>{{ lang._('Save') }}</b> <i id="btnSaveSettingsProgress2"></i>
                        </button>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div id="variables" class="tab-pane fade in">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("layout_partials/base_form",['fields':formVariables,'id':'formVariables'])}}
            <table class="table table-striped opnsense_standard_table_form">
                <tbody>
                    <tr><td>
                        <button class="btn btn-primary" id="btnSaveSettings3" type="button">
                            <b>{{ lang._('Save') }}</b> <i id="btnSaveSettingsProgress3"></i>
                        </button>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
