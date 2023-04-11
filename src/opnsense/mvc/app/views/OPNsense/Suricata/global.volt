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



<script>

$(document).ready(function() {
    var interface_descriptions = {};
    var data_get_map_rules = { 'formRules': "/api/suricata/settings/get" };
    var data_get_map_updates = { 'formUpdates': "/api/suricata/settings/get" };
    var data_get_map_general = { 'formGeneral': "/api/suricata/settings/get" };

    $('#btnSaveSettings1').unbind('click').click(function(){
        $("#btnSaveSettingsProgress1").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/suricata/settings/set", 'formRules', function() {
            $("#btnSaveSettingsProgress1").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings1").blur();
        }, true, function (data, status) {
            $("#btnSaveSettingsProgress1").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings1").blur();
        });
    });

    $('#btnSaveSettings2').unbind('click').click(function(){
        $("#btnSaveSettingsProgress2").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/suricata/settings/set", 'formUpdates', function() {
            $("#btnSaveSettingsProgress2").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings2").blur();
        }, true, function (data, status) {
            $("#btnSaveSettingsProgress2").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings2").blur();
        });
    });

    $('#btnSaveSettings3').unbind('click').click(function(){
        $("#btnSaveSettingsProgress3").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/suricata/settings/set", 'formGeneral', function() {
            $("#btnSaveSettingsProgress3").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings3").blur();
        }, true, function (data, status) {
            $("#btnSaveSettingsProgress3").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings3").blur();
        });
    });

    mapDataToFormUI(data_get_map_rules).done(function () { formatTokenizersUI(); $('.selectpicker').selectpicker('refresh'); });
    mapDataToFormUI(data_get_map_updates).done(function () { formatTokenizersUI(); $('.selectpicker').selectpicker('refresh'); });
    mapDataToFormUI(data_get_map_general).done(function () { formatTokenizersUI(); $('.selectpicker').selectpicker('refresh'); });
});

</script>


<ul class="nav nav-tabs" data-tabs="tabs" id="maintabs">
    <li class="active"><a data-toggle="tab" href="#rules">{{ lang._('Rules to download') }}</a></li>
    <li><a data-toggle="tab" href="#updates">{{ lang._('Rules update settings') }}</a></li>
    <li><a data-toggle="tab" href="#general">{{ lang._('General Settings') }}</a></li>
</ul>
<div class="tab-content content-box tab-content">
    <div id="rules" class="tab-pane fade in active">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("layout_partials/base_form",['fields':formRules,'id':'formRules'])}}
            <div class="col-md-12">
                <hr />
                <button class="btn btn-primary" id="btnSaveSettings1" type="button">
                    <b>{{ lang._('Save') }}</b> <i id="btnSaveSettingsProgress1"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="updates" class="tab-pane fade in">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("layout_partials/base_form",['fields':formUpdates,'id':'formUpdates'])}}
            <div class="col-md-12">
                <hr />
                <button class="btn btn-primary" id="btnSaveSettings2" type="button">
                    <b>{{ lang._('Save') }}</b> <i id="btnSaveSettingsProgress2"></i>
                </button>
            </div>
            </div>
        </div>
    </div>
    <div id="updates" class="tab-pane fade in">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("layout_partials/base_form",['fields':formGeneral,'id':'formGeneral'])}}
            <div class="col-md-12">
                <hr />
                <button class="btn btn-primary" id="btnSaveSettings3" type="button">
                    <b>{{ lang._('Save') }}</b> <i id="btnSaveSettingsProgress3"></i>
                </button>
            </div>
            </div>
        </div>
    </div>
</div>

