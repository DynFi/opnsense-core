{#
 # Copyright (c) 2023 Deciso B.V.
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
    'use strict';

    $( document ).ready(function () {
        let grid_cso = $("#grid-cso").UIBootgrid({
            search:'/api/openvpn/client_overwrites/search/',
            get:'/api/openvpn/client_overwrites/get/',
            add:'/api/openvpn/client_overwrites/add/',
            set:'/api/openvpn/client_overwrites/set/',
            del:'/api/openvpn/client_overwrites/del/',
            toggle:'/api/openvpn/client_overwrites/toggle/',
            options:{
                selection: false,
                formatters:{
                    tunnel: function (column, row) {
                        let items = [];
                        if (row.tunnel_network) {
                            items.push(row.tunnel_network);
                        }
                        if (row.tunnel_networkv6) {
                            items.push(row.tunnel_networkv6);
                        }
                        return items.join('<br/>');
                    }
                }
            }
        });

    });

</script>


<ul class="nav nav-tabs" data-tabs="tabs" id="maintabs">
    <li class="active"><a data-toggle="tab" href="#cso">{{ lang._('Overwrites') }}</a></li>
</ul>
<div class="tab-content content-box">
    <div id="cso" class="tab-pane fade in active">
        <table id="grid-cso" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="DialogCSO">
            <thead>
                <tr>
                    <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                    <th data-column-id="enabled" data-width="6em" data-type="string" data-formatter="rowtoggle">{{ lang._('Enabled') }}</th>
                    <th data-column-id="common_name" data-type="string">{{ lang._('Common name') }}</th>
                    <th data-column-id="tunnel_network" data-type="string" data-formatter="tunnel">{{ lang._('Tunnel Network') }}</th>
                    <th data-column-id="description" data-type="string">{{ lang._('Description') }}</th>
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

{{ partial("layout_partials/base_dialog",['fields':formDialogCSO,'id':'DialogCSO','label':lang._('Edit CSO')])}}
