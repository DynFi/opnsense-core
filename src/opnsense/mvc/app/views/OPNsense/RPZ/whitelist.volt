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
    var data_get_map = {'frm_Settings': "/api/rpz/whitelist/get"};

    $('#btnSaveSettings').unbind('click').click(function(){
        $("#btnSaveSettingsProgress").addClass("fa fa-spinner fa-pulse");
        saveFormToEndpoint("/api/rpz/whitelist/set", 'frm_Settings', function() {
            ajaxCall("/api/rpz/service/reconfigure", {}, function(data, status) {
                var result_status = ((status == "success") && (data['status'].toLowerCase().trim() == "ok"));
                if (!result_status) {
                    BootstrapDialog.show({
                        type: BootstrapDialog.TYPE_WARNING,
                        title: "{{ lang._('Error updating firewall rules') }}",
                        message: data['message'],
                        draggable: true
                    });
                }
                $("#btnSaveSettingsProgress").removeClass("fa fa-spinner fa-pulse");
                $("#btnSaveSettings").blur();
                updateServiceControlUI('rpz');
            });
        });
    });

    mapDataToFormUI(data_get_map).done(function () {
        formatTokenizersUI();
        updateServiceControlUI('dfm');
    });
});

</script>

<div class="content-box">
    {{ partial("layout_partials/base_form",['fields':form,'id':'frm_White'])}}
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
