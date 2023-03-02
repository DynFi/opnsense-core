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

#rpzfilestats { padding: 5px }
#rpzfilestats .alert { margin-bottom: 5px }
</style>

<script>

var _rpzc = [];

function checkRpzFilesStatus() {
    ajaxGet("/api/rpz/service/rpzFileStats", {}, function(data, status) {
        if (Object.keys(data).length) {
            for (var c in data) {
                if (data[c] == '1') {
                    if (_rpzc.includes(c)) {
                        $('#rpzs-' + c).remove();
                        const ind = _rpzc.indexOf(c);
                        _rpzc.splice(ind, 1);
                        $('#rpzfilestats').append('<div class="alert alert-info alert-dismissible show">Category <b>' + c + '</b> downloaded <i class="fa fa-check"></i><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                    }
                } else {
                    if (!_rpzc.includes(c)) {
                        $('#rpzs-' + c).remove();
                        _rpzc.push(c);
                        $('#rpzfilestats').append('<div class="alert alert-info" id="rpzs-' + c + '">Downloading category <b>' + c + '</b> <i class="reload_progress fa fa-spinner fa-pulse"></i></div>');
                    }
                }
            }
        }
        _rpzc.forEach(function (c) {
            if (!(c in data)) {
                $('#rpzs-' + c).remove();
            }
        });
    });
}


function checkRpzServiceStatus() {
    updateServiceControlUI('rpz');
}


$(document).ready(function() {

    $("#grid-lists").UIBootgrid({
        search:'/api/rpz/list/searchItem',
        get:'/api/rpz/list/getItem/',
        set:'/api/rpz/list/setItem/',
        add:'/api/rpz/list/addItem/',
        del:'/api/rpz/list/delItem/',
        toggle:'/api/rpz/list/toggleItem/'
    });

    if ("{{selected_list}}" !== "") {
        setTimeout(function() {
            ajaxGet("/api/rpz/list/getListUUID/{{selected_list}}", {}, function(data, status) {
                if (data.uuid !== undefined) {
                    var edit_item = $(".command-edit:eq(0)").clone(true);
                    edit_item.data('row-id', data.uuid).click();
                }
            });
        }, 100);
    } else {
        setTimeout(function() {
            var data_get_map = {'frm_List': "/api/rpz/list/getItem"};
            mapDataToFormUI(data_get_map).done(function () {
                formatTokenizersUI();
                updateServiceControlUI('rpz');
            });
        }, 100);
    }

    $("#reconfigureAct").SimpleActionButton();

    checkRpzFilesStatus();
    setInterval(checkRpzFilesStatus, 5000);
    setInterval(checkRpzServiceStatus, 5000);
});
</script>

<div class="tab-content content-box">
    <div id="rpz">
        <div class="row">
            <section class="col-xs-12">
                <div class="content-box">
                    <div id="rpzfilestats"></div>
                    <table id="grid-lists" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="DialogList" data-editAlert="listChangeMessage" data-store-selection="true">
                        <thead>
                            <tr>
                                <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                                <th data-column-id="enabled" data-width="6em" data-type="string" data-formatter="rowtoggle">{{ lang._('Enabled') }}</th>
                                <th data-column-id="name" data-type="string">{{ lang._('Name') }}</th>
                                <th data-column-id="categories" data-type="string">{{ lang._('Categories') }}</th>
                                <th data-column-id="apply_to" data-type="string">{{ lang._('Apply to') }}</th>
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
<section class="page-content-main">
  <div class="content-box">
    <div class="col-md-12">
        <br/>
        <div id="listChangeMessage" class="alert alert-info" style="display: none" role="alert">
            {{ lang._('After changing settings, please remember to apply them with the button below') }}
        </div>
        <button class="btn btn-primary" id="reconfigureAct"
                data-endpoint='/api/rpz/service/reconfigure'
                data-label="{{ lang._('Apply') }}"
                data-error-title="{{ lang._('Error reconfiguring RPZ') }}"
                type="button"
        >Apply</button>
        <br/><br/>
    </div>
  </div>
</section>

{{ partial("layout_partials/base_dialog",['fields':formList,'id':'DialogList','label':lang._('Edit filtering list')])}}
