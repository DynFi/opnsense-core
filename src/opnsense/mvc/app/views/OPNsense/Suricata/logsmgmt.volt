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

function showSaveAlert(id) {
    $(id).slideDown(1000, function() {
        setTimeout(function() {
            $(id).slideUp(2000);
        }, 2000);
    });
}

$(document).ready(function() {
    var interface_descriptions = {};
    var data_get_map_lmgeneral = { 'formGeneral': "/api/suricata/settings/get" };
    var data_get_map_lmdirsizelimit = { 'formDirSizeLimit': "/api/suricata/settings/get" };
    var data_get_map_lmsizeretentionlimit = { 'formSizeRetentionLimit': "/api/suricata/settings/get" };

    $('#btnSaveSettings1').unbind('click').click(function(){
        $("#btnSaveSettingsProgress1").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/suricata/settings/set", 'formGeneral', function() {
            $("#btnSaveSettingsProgress1").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings1").blur();
            showSaveAlert('#listChangeMessage');
        }, true, function (data, status) {
            $("#btnSaveSettingsProgress1").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings1").blur();
        });
    });

    $('#btnSaveSettings2').unbind('click').click(function(){
        $("#btnSaveSettingsProgress2").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/suricata/settings/set", 'formDirSizeLimit', function() {
            $("#btnSaveSettingsProgress2").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings2").blur();
            showSaveAlert('#listChangeMessage');
        }, true, function (data, status) {
            $("#btnSaveSettingsProgress2").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings2").blur();
        });
    });

    $('#btnSaveSettings3').unbind('click').click(function(){
        $("#btnSaveSettingsProgress3").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/suricata/settings/set", 'formSizeRetentionLimit', function() {
            $("#btnSaveSettingsProgress3").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings3").blur();
            showSaveAlert('#listChangeMessage');
        }, true, function (data, status) {
            $("#btnSaveSettingsProgress3").removeClass("fa fa-spinner fa-pulse");
            $("#btnSaveSettings3").blur();
        });
    });

    mapDataToFormUI(data_get_map_lmgeneral).done(function () { formatTokenizersUI(); $('.selectpicker').selectpicker('refresh'); });
    mapDataToFormUI(data_get_map_lmdirsizelimit).done(function () { formatTokenizersUI(); $('.selectpicker').selectpicker('refresh'); });
    mapDataToFormUI(data_get_map_lmsizeretentionlimit).done(function () { formatTokenizersUI(); $('.selectpicker').selectpicker('refresh'); });

    $("#reconfigureAct").SimpleActionButton();
});

</script>


<ul class="nav nav-tabs" data-tabs="tabs" id="maintabs">
    <li class="active"><a data-toggle="tab" href="#lmgeneral">{{ lang._('General Settings') }}</a></li>
    <li><a data-toggle="tab" href="#lmdirsizelimit">{{ lang._('Log Directory Size Limit') }}</a></li>
    <li><a data-toggle="tab" href="#lmsizeretentionlimit">{{ lang._('Log Size and Retention Limits') }}</a></li>
</ul>
<div class="tab-content content-box tab-content">
    <div id="lmgeneral" class="tab-pane fade in active">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("layout_partials/base_form",['fields':formGeneral,'id':'formGeneral'])}}
            <div class="col-md-12">
                <hr />
                <button class="btn btn-primary" id="btnSaveSettings1" type="button">
                    <b>{{ lang._('Save') }}</b> <i id="btnSaveSettingsProgress1"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="lmdirsizelimit" class="tab-pane fade in">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("layout_partials/base_form",['fields':formDirSizeLimit,'id':'formDirSizeLimit'])}}
            <div class="col-md-12">
                <hr />
                <button class="btn btn-primary" id="btnSaveSettings2" type="button">
                    <b>{{ lang._('Save') }}</b> <i id="btnSaveSettingsProgress2"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="lmsizeretentionlimit" class="tab-pane fade in">
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("layout_partials/base_form",['fields':formSizeRetentionLimit,'id':'formSizeRetentionLimit'])}}
            <div class="col-md-12">
                <hr />
                <button class="btn btn-primary" id="btnSaveSettings3" type="button">
                    <b>{{ lang._('Save') }}</b> <i id="btnSaveSettingsProgress3"></i>
                </button>
            </div>
        </div>
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
