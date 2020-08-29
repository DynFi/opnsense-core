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

function confirmKey(key) {
    BootstrapDialog.show({
        title: "{{ lang._('Please confirm SSH keys') }}",
        message: key,
        draggable: true,
        buttons: [{
            label: '{{ lang._('Reject') }}',
            action: function(dialog) {
                dialog.close();
            }
        }, {
            label: '{{ lang._('Confirm') }}',
            action: function(dialog) {
                dialog.close();
                ajaxCall("/api/dfconag/service/acceptKey", { key: key }, function(data, status) {
                    console.dir(status);
                    console.dir(data);
                });
            }
        }]
    });
}

$(document).ready(function() {
    var data_get_map = {'frm_Settings': "/api/dfconag/settings/get"};

    $('#btnSaveSettings').unbind('click').click(function() {
        $("#btnSaveSettingsProgress").addClass("fa fa-spinner fa-pulse");

        saveFormToEndpoint("/api/dfconag/settings/set", 'frm_Settings', function() {

            ajaxCall("/api/dfconag/service/reconfigure", {}, function(data, status) {
                var result_status = ((status == "success") && (data['status'].toLowerCase().trim() == "ok"));
                if (result_status) {
                    confirmKey(data['message']);
                } else {
                    BootstrapDialog.show({
                        type: BootstrapDialog.TYPE_WARNING,
                        title: "{{ lang._('Error configuring connection agent') }}",
                        message: data['message'],
                        draggable: true
                    });
                }
                $("#btnSaveSettingsProgress").removeClass("fa fa-spinner fa-pulse");
                $("#btnSaveSettings").blur();
                updateServiceControlUI('dfconag');
            });

        });
    });

    mapDataToFormUI(data_get_map).done(function () {
        formatTokenizersUI();
        updateServiceControlUI('dfconag');
    });
});

</script>

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
