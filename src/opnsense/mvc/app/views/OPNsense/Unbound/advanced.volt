{#
 # Copyright (c) 2022 Deciso B.V.
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
 # THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
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
<<<<<<< HEAD
    function checkUnboundServiceStatus() {
        updateServiceControlUI('unbound');
    }

=======
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
    $( document ).ready(function() {
        function row_toggle() {
            $parent_row = $('.serveexpired_child').closest('tr');
            $parent_row.find('div:first').css('padding-left', '20px');
            $('.serveexpired_parent').is(':checked') ? $parent_row.removeClass("hidden") : $parent_row.addClass("hidden");
<<<<<<< HEAD

=======
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
        }

        var data_get_map = {'frm_AdvancedSettings':"/api/unbound/settings/get"};
        mapDataToFormUI(data_get_map).done(function(data) {
            formatTokenizersUI();
            $('.selectpicker').selectpicker('refresh');
            row_toggle();
        });

        $("#reconfigureAct").SimpleActionButton({
            onPreAction: function() {
              const dfObj = new $.Deferred();
<<<<<<< HEAD
              saveFormToEndpoint("/api/unbound/settings/set", 'frm_AdvancedSettings', function(){
                  dfObj.resolve();
              });
=======
              saveFormToEndpoint("/api/unbound/settings/set", 'frm_AdvancedSettings', function () { dfObj.resolve(); }, true , function () { dfObj.reject(); });
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
              return dfObj;
            }
        });

        $('.serveexpired_parent').click(function(){
            row_toggle();
        });

<<<<<<< HEAD
        checkUnboundServiceStatus();
        setInterval(checkUnboundServiceStatus, 5000);
=======
        updateServiceControlUI('unbound');
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
    });
</script>

<div class="content-box" style="padding-bottom: 1.5em;">
{{ partial("layout_partials/base_form",['fields':advancedForm,'id':'frm_AdvancedSettings'])}}
<<<<<<< HEAD
    <div class="col-md-12">
        <hr/>
        <button class="btn btn-primary" id="reconfigureAct"
                data-endpoint='/api/unbound/service/reconfigure'
                data-label="{{ lang._('Apply') }}"
=======
    <div class="col-md-12 __mt">
        <button class="btn btn-primary" id="reconfigureAct"
                data-endpoint='/api/unbound/service/reconfigure'
                data-label="{{ lang._('Apply') }}"
                data-service-widget="unbound"
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
                data-error-title="{{ lang._('Error reconfiguring unbound') }}"
                type="button">
        </button>
    </div>
</div>
