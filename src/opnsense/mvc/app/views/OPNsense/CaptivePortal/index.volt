{#

OPNsense® is Copyright © 2014 – 2015 by Deciso B.V.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

1.  Redistributions of source code must retain the above copyright notice,
this list of conditions and the following disclaimer.

2.  Redistributions in binary form must reproduce the above copyright notice,
this list of conditions and the following disclaimer in the documentation
and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED “AS IS” AND ANY EXPRESS OR IMPLIED WARRANTIES,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.

#}

<script type="text/javascript">

    $( document ).ready(function() {
        /*************************************************************************************************************
         * link grid actions
         *************************************************************************************************************/

        $("#grid-zones").UIBootgrid(
            {   search:'/api/captiveportal/settings/searchZones',
                get:'/api/captiveportal/settings/getZone/',
                set:'/api/captiveportal/settings/setZone/',
                add:'/api/captiveportal/settings/addZone/',
                del:'/api/captiveportal/settings/delZone/',
                toggle:'/api/captiveportal/settings/toggleZone/'
            }
        );

        var gridopt = {
            ajax: true,
            selection: true,
            multiSelect: true,
            rowCount:[7,14,20,-1],
            url: '/api/captiveportal/service/searchTemplates',
            formatters: {
                "commands": function (column, row) {
                    return  "<button type=\"button\" class=\"btn btn-xs btn-default command-download\" data-row-id=\"" + row.fileid + "\"><span class=\"fa fa-download\"></span></button> " +
                            "<button type=\"button\" class=\"btn btn-xs btn-default command-edit\" data-row-id=\"" + row.uuid + "\" data-row-name=\"" + row.name + "\"><span class=\"fa fa-pencil\"></span></button> " +
                            "<button type=\"button\" class=\"btn btn-xs btn-default command-delete\" data-row-id=\"" + row.uuid + "\"><span class=\"fa fa-trash-o\"></span></button>";
                }
            }
        };

        var grid_templates = $("#grid-templates").bootgrid(gridopt).on("loaded.rs.jquery.bootgrid", function (e)
        {
            // scale footer on resize
            $(this).find("tfoot td:first-child").attr('colspan',$(this).find("th").length - 1);
            $(this).find('tr[data-row-id]').each(function(){
                if ($(this).find('[class*="command-toggle"]').first().data("value") == "0") {
                    $(this).addClass("text-muted");
                }
            });

        });

        grid_templates.on("loaded.rs.jquery.bootgrid", function(){
            grid_templates.find(".command-edit").on("click", function(e) {
                $("#templateUUID").val($(this).data("row-id"));
                $("#templateName").val($(this).data("row-name"));
                $("#base64text_upload").val("");
                $('#DialogTemplate').modal({backdrop: 'static', keyboard: false});
            });
            grid_templates.find(".command-delete").on("click", function(e) {
                var uuid=$(this).data("row-id");
                stdDialogRemoveItem('Remove selected item?',function() {
                    ajaxCall(url="/api/captiveportal/service/delTemplate/" + uuid,
                            sendData={},callback=function(data,status){
                                // reload grid after delete
                                $("#grid-templates").bootgrid("reload");
                            });
                });
            });
            grid_templates.find(".command-download").on("click", function(e) {
                window.open('/api/captiveportal/service/getTemplate/'+$(this).data("row-id")+'/','downloadTemplate');
            });
        });

        /**
         * Open dialog to add new template
         */
        $("#addTemplateAct").click(function(){
            // clear dialog and open
            $("#templateUUID").val("");
            $("#templateName").val("");
            $("#base64text_upload").val("");
            $('#DialogTemplate').modal({backdrop: 'static', keyboard: false});
        });

        /**
         * download default template
         */
        $("#downloadTemplateAct").click(function(){
            window.open('/api/captiveportal/service/getTemplate/','downloadTemplate');
        });


        /*************************************************************************************************************
         * Commands
         *************************************************************************************************************/

        /**
         * Reconfigure
         */
        $("#reconfigureAct").click(function(){
            $("#reconfigureAct_progress").addClass("fa fa-spinner fa-pulse");
            ajaxCall(url="/api/captiveportal/service/reconfigure", sendData={}, callback=function(data,status) {
                // when done, disable progress animation.
                $("#reconfigureAct_progress").removeClass("fa fa-spinner fa-pulse");

                if (status != "success" || data['status'] != 'ok') {
                    BootstrapDialog.show({
                        type: BootstrapDialog.TYPE_WARNING,
                        title: "{{ lang._('Error reconfiguring captiveportal') }}",
                        message: data['status'],
                        draggable: true
                    });
                }
            });
        });

        /*************************************************************************************************************
         * File upload action, template dialog
         *************************************************************************************************************/
        // catch file select event and save content to textarea as base64 string
        $("#input_filename").change(function(evt) {
            if (evt.target.files[0]) {
                var reader = new FileReader();
                reader.onload = function(readerEvt) {
                    var binaryString = readerEvt.target.result;
                    $("#base64text_upload").val(btoa(binaryString));
                };
                reader.readAsBinaryString(evt.target.files[0]);
            }
        });
        $("#act_upload").click(function() {
            var requestData = {'name' : $("#templateName").val(), 'content': $("#base64text_upload").val()};
            if ($("#templateUUID").val() != "") {
                requestData['uuid'] = $("#templateUUID").val();
            }
            // save file content to server
            ajaxCall(url="/api/captiveportal/service/saveTemplate", sendData=requestData, callback=function(data,status) {
                if (data['error'] == undefined) {
                    // saved, flush form data and hide modal
                    $("#grid-templates").bootgrid("reload");
                    $("#DialogTemplate").modal('hide');
                } else {
                    // error saving
                    BootstrapDialog.show({
                        type: BootstrapDialog.TYPE_WARNING,
                        title: "{{ lang._('Error uploading template') }}",
                        message: data['error'],
                        draggable: true
                    });
                }
            });
        });
    });


</script>

<ul class="nav nav-tabs" data-tabs="tabs" id="maintabs">
    <li class="active"><a data-toggle="tab" href="#zones">{{ lang._('Zones') }}</a></li>
    <li><a data-toggle="tab" href="#template">{{ lang._('Templates') }}</a></li>
</ul>
<div class="tab-content content-box tab-content">
    <div id="zones" class="tab-pane fade in active">
        <!-- tab page "zones" -->
        <table id="grid-zones" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="DialogZone">
            <thead>
            <tr>
                <th data-column-id="enabled" data-width="6em" data-type="string" data-formatter="rowtoggle">{{ lang._('Enabled') }}</th>
                <th data-column-id="zoneid" data-type="number"  data-visible="false">{{ lang._('Zoneid') }}</th>
                <th data-column-id="description" data-type="string">{{ lang._('Description') }}</th>
                <th data-column-id="commands" data-width="7em" data-formatter="commands" data-sortable="false">{{ lang._('Commands') }}</th>
                <th data-column-id="uuid" data-type="string" data-identifier="true"  data-visible="false">{{ lang._('ID') }}</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
            <tr>
                <td></td>
                <td>
                    <button data-action="add" type="button" class="btn btn-xs btn-default"><span class="fa fa-plus"></span></button>
                    <button data-action="deleteSelected" type="button" class="btn btn-xs btn-default"><span class="fa fa-trash-o"></span></button>
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
    <div id="template" class="tab-pane fade in">
        <div class="col-md-12">
            <table id="grid-templates" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="DialogTemplate">
                <thead>
                <tr>
                    <th data-column-id="fileid" data-type="string"  data-visible="false">{{ lang._('Fileid') }}</th>
                    <th data-column-id="name" data-type="string">{{ lang._('Name') }}</th>
                    <th data-column-id="commands" data-width="7em" data-formatter="commands" data-sortable="false">{{ lang._('Commands') }}</th>
                    <th data-column-id="uuid" data-type="string" data-identifier="true"  data-visible="false">{{ lang._('ID') }}</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <td></td>
                    <td>
                        <button id="addTemplateAct" type="button" class="btn btn-xs btn-default"><span class="fa fa-plus"></span></button>
                        <button id="downloadTemplateAct" type="button" class="btn btn-xs btn-default"><span class="fa fa-download"></span></button>
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="col-md-12">
        <hr/>
        <button class="btn btn-primary"  id="reconfigureAct" type="button"><b>{{ lang._('Apply') }}</b><i id="reconfigureAct_progress" class=""></i></button>
        <br/><br/>
    </div>
</div>


{# include dialogs #}
{{ partial("layout_partials/base_dialog",['fields':formDialogZone,'id':'DialogZone','label':'Edit zone'])}}

<!-- upload (new) template content dialog -->
<div class="modal fade" id="DialogTemplate" tabindex="-1" role="dialog" aria-labelledby="formDialogTemplateLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="formDialogTemplateLabel">{{ lang._('Upload file') }}</h4>
            </div>
            <div class="modal-body">
                <form>
                    <input type="text" id="templateUUID" class="hidden">
                    <div class="form-group">
                        <label for="templateName">{{ lang._('Template name') }}</label>
                        <input type="text" class="form-control" id="templateName" placeholder="Name">
                    </div>
                    <div class="form-group">
                        <label for="input_filename">{{ lang._('File input') }}</label>
                        <input type="file" id="input_filename">
                    </div>
                    <textarea id="base64text_upload" class="hidden"></textarea>
                </form>
            </div>
            <div class="modal-footer">
                <button id="act_upload" type="button" class="btn btn-default">
                    {{ lang._('Upload') }}
                    <span class="fa fa-upload"></span>
                </button>
            </div>
        </div>
    </div>
</div>
