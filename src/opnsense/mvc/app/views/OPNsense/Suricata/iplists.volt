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

<style>
@media (min-width: 768px) {
    #DialogIplist > .modal-dialog {
        width: 90%;
        max-width:1200px;
    }
}
</style>

<script>

$(document).ready(function() {

    $("#grid-lists").UIBootgrid({
        search:'/api/suricata/iplists/searchItem',
        get:'/api/suricata/iplists/getItem/',
        set:'/api/suricata/iplists/setItem/',
        add:'/api/suricata/iplists/addItem/',
        del:'/api/suricata/iplists/delItem/'
    });

    if ("{{selected_list}}" !== "") {
        setTimeout(function() {
            ajaxGet("/api/suricata/iplists/getListUUID/{{selected_list}}", {}, function(data, status) {
                if (data.uuid !== undefined) {
                    var edit_item = $(".command-edit:eq(0)").clone(true);
                    edit_item.data('row-id', data.uuid).click();
                }
            });
        }, 100);
    } else {
        setTimeout(function() {
            var data_get_map = {'frm_passlist': "/api/suricata/iplists/getItem"};
            mapDataToFormUI(data_get_map).done(function () {
                formatTokenizersUI();
            });
        }, 100);
    }

    $("#reconfigureAct").SimpleActionButton();
});
</script>

<div class="tab-content content-box">
    <div id="iplists">
        <div class="row">
            <section class="col-xs-12">
                <div class="content-box">
                    <table id="grid-lists" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="DialogIplist" data-editAlert="listChangeMessage" data-store-selection="true">
                        <thead>
                            <tr>
                                <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                                <th data-column-id="name" data-type="string">{{ lang._('IP List File Name') }}</th>
                                <th data-column-id="modified" data-type="string">{{ lang._('Last Modified Time') }}</th>
                                <th data-column-id="size" data-type="string">{{ lang._('File Size') }}</th>
                                <th data-column-id="commands" data-width="7em" data-formatter="commands" data-sortable="false">{{ lang._('Commands') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td></td>
                                <td>
                                    <button data-action="add" type="button" class="btn btn-xs btn-primary"><span class="fa fa-fw fa-plus"></span></button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
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

{{ partial("layout_partials/base_dialog",['fields':formLists,'id':'DialogIplist','label':lang._('Edit IP list')])}}
