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
@media (min-width: 768px) {
    #DialogList > .modal-dialog {
        width: 90%;
        max-width:1200px;
    }
}
</style>

<script>
$(document).ready(function() {
    $("#grid-lists").UIBootgrid({
        search:'/api/rpz/list/searchItem',
        get:'/api/rpz/list/getItem/',
        set:'/api/rpz/list/setItem/',
        add:'/api/rpz/list/addItem/',
        del:'/api/rpz/list/delItem/',
        toggle:'/api/rpz/list/toggleItem/',
    });
});
</script>

<div class="tab-content content-box">
    <div id="rpz">
        <div class="row">
            <section class="col-xs-12">
                <div class="content-box">
                    <table id="grid-lists" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="#DialogList" data-editAlert="listChangeMessage" data-store-selection="true">
                        <thead>
                            <tr>
                                <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                                <th data-column-id="enabled" data-width="6em" data-type="string" data-formatter="rowtoggle">{{ lang._('Enabled') }}</th>
                                <th data-column-id="name" data-type="string">{{ lang._('Name') }}</th>
                                <th data-column-id="last_updated" data-type="string">{{ lang._('Last updated') }}</th>
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
                                    <button data-action="deleteSelected" type="button" class="btn btn-xs btn-default"><span class="fa fa-fw fa-trash-o"></span></button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

{# Edit dialog #}
<div class="modal fade" id="DialogList" tabindex="-1" role="dialog" aria-labelledby="DialogListLabel" aria-hidden="true">
    <div class="modal-backdrop fade in"></div>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="formDialogListLabel">{{lang._('Edit filtering list')}}</h4>
            </div>
            <div class="modal-body">
                <form id="frm_DialogList">
                    <div class="table-responsive">

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ lang._('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn_DialogList_save">{{ lang._('Save') }}
                    <i id="btn_formDialogLists_save_progress" class=""></i></button>
            </div>
        </div>
    </div>
</div>
