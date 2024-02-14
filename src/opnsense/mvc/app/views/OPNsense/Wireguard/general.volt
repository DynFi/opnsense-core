{#
<<<<<<< HEAD
 # OPNsense (c) 2014-2018 by Deciso B.V.
 # OPNsense (c) 2018 Michael Muenz <m.muenz@gmail.com>
=======
 # Copyright (c) 2014-2023 Deciso B.V.
 # Copyright (c) 2018 Michael Muenz <m.muenz@gmail.com>
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without modification,
 # are permitted provided that the following conditions are met:
 #
 # 1.  Redistributions of source code must retain the above copyright notice,
 #     this list of conditions and the following disclaimer.
 #
 # 2.  Redistributions in binary form must reproduce the above copyright notice,
 #     this list of conditions and the following disclaimer in the documentation
 #     and/or other materials provided with the distribution.
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

<<<<<<< HEAD
<!-- Navigation bar -->
<ul class="nav nav-tabs" data-tabs="tabs" id="maintabs">
    <li class="active"><a data-toggle="tab" href="#general">{{ lang._('General') }}</a></li>
    <li><a data-toggle="tab" href="#servers">{{ lang._('Local') }}</a></li>
    <li><a data-toggle="tab" href="#clients">{{ lang._('Endpoints') }}</a></li>
    <li><a data-toggle="tab" href="#showconf">{{ lang._('Status') }}</a></li>
    <li><a data-toggle="tab" href="#showhandshake">{{ lang._('Handshakes') }}</a></li>
=======
 <script>
    $( document ).ready(function() {
        var data_get_map = {'frm_general_settings':"/api/wireguard/general/get"};
        mapDataToFormUI(data_get_map).done(function(data){
            formatTokenizersUI();
            $('.selectpicker').selectpicker('refresh');
        });

        $("#grid-peers").UIBootgrid(
            {
                'search':'/api/wireguard/client/searchClient',
                'get':'/api/wireguard/client/getClient/',
                'set':'/api/wireguard/client/setClient/',
                'add':'/api/wireguard/client/addClient/',
                'del':'/api/wireguard/client/delClient/',
                'toggle':'/api/wireguard/client/toggleClient/'
            }
        );

        $("#grid-instances").UIBootgrid(
            {
                'search':'/api/wireguard/server/searchServer',
                'get':'/api/wireguard/server/getServer/',
                'set':'/api/wireguard/server/setServer/',
                'add':'/api/wireguard/server/addServer/',
                'del':'/api/wireguard/server/delServer/',
                'toggle':'/api/wireguard/server/toggleServer/'
            }
        );


        $("#reconfigureAct").SimpleActionButton({
            onPreAction: function() {
                const dfObj = new $.Deferred();
                saveFormToEndpoint("/api/wireguard/general/set", 'frm_general_settings', function(){
                    dfObj.resolve();
                });
                return dfObj;
            }
        });

        /**
         * Move keypair generation button inside the instance form and hook api event
         */
        $("#control_label_server\\.pubkey").append($("#keygen_div").detach().show());
        $("#keygen").click(function(){
            ajaxGet("/api/wireguard/server/key_pair", {}, function(data, status){
                if (data.status && data.status === 'ok') {
                    $("#server\\.pubkey").val(data.pubkey);
                    $("#server\\.privkey").val(data.privkey);
                }
            });
        })
        $("#control_label_client\\.psk").append($("#pskgen_div").detach().show());
        $("#pskgen").click(function(){
            ajaxGet("/api/wireguard/client/psk", {}, function(data, status){
                if (data.status && data.status === 'ok') {
                    $("#client\\.psk").val(data.psk);
                }
            });
        })
    });
</script>

<!-- Navigation bar -->
<ul class="nav nav-tabs" data-tabs="tabs" id="maintabs">
    <li class="active"><a data-toggle="tab" href="#general">{{ lang._('General') }}</a></li>
    <li><a data-toggle="tab" href="#instances">{{ lang._('Instances') }}</a></li>
    <li><a data-toggle="tab" href="#peers">{{ lang._('Peers') }}</a></li>
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
</ul>

<div class="tab-content content-box tab-content">
    <div id="general" class="tab-pane fade in active">
<<<<<<< HEAD
        <div class="content-box" style="padding-bottom: 1.5em;">
            {{ partial("layout_partials/base_form",['fields':generalForm,'id':'frm_general_settings'])}}
            <div class="col-md-12">
                <hr />
                <button class="btn btn-primary" id="saveAct" type="button"><b>{{ lang._('Apply') }}</b> <i id="saveAct_progress"></i></button>
            </div>
        </div>
    </div>
    <div id="clients" class="tab-pane fade in">
        <table id="grid-clients" class="table table-responsive" data-editDialog="dialogEditWireguardClient">
            <thead>
                <tr>
                    <th data-column-id="enabled" data-type="string" data-formatter="rowtoggle">{{ lang._('Enabled') }}</th>
                    <th data-column-id="name" data-type="string" data-visible="true">{{ lang._('Name') }}</th>
                    <th data-column-id="serveraddress" data-type="string" data-visible="true">{{ lang._('Endpoint Address') }}</th>
                    <th data-column-id="serverport" data-type="string" data-visible="true">{{ lang._('Endpoint Port') }}</th>
=======
        {{ partial("layout_partials/base_form",['fields':generalForm,'id':'frm_general_settings'])}}
    </div>
    <div id="peers" class="tab-pane fade in">
        <span id="pskgen_div" style="display:none" class="pull-right">
            <button id="pskgen" type="button" class="btn btn-secondary" title="{{ lang._('Generate new psk.') }}" data-toggle="tooltip">
              <i class="fa fa-fw fa-gear"></i>
            </button>
        </span>
        <table id="grid-peers" class="table table-condensed table-hover table-striped" data-editDialog="dialogEditWireguardClient">
            <thead>
                <tr>
                    <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                    <th data-column-id="enabled" data-type="string" data-formatter="rowtoggle">{{ lang._('Enabled') }}</th>
                    <th data-column-id="name" data-type="string" data-visible="true">{{ lang._('Name') }}</th>
                    <th data-column-id="serveraddress" data-type="string" data-visible="true">{{ lang._('Endpoint address') }}</th>
                    <th data-column-id="serverport" data-type="string" data-visible="true">{{ lang._('Endpoint port') }}</th>
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
                    <th data-column-id="tunneladdress" data-type="string" data-visible="true">{{ lang._('Allowed IPs') }}</th>
                    <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                    <th data-column-id="commands" data-formatter="commands" data-sortable="false">{{ lang._('Commands') }}</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6"></td>
                    <td>
                        <button data-action="add" type="button" class="btn btn-xs btn-default"><span class="fa fa-plus"></span></button>
<<<<<<< HEAD
=======
                        <button data-action="deleteSelected" type="button" class="btn btn-xs btn-default"><span class="fa fa-fw fa-trash-o"></span></button>
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
                    </td>
                </tr>
            </tfoot>
        </table>
<<<<<<< HEAD
        <div class="col-md-12">
            <hr />
            <button class="btn btn-primary" id="saveAct_client" type="button"><b>{{ lang._('Apply') }}</b> <i id="saveAct_client_progress"></i></button>
            <br /><br />
        </div>
    </div>
    <div id="servers" class="tab-pane fade in">
        <table id="grid-servers" class="table table-responsive" data-editDialog="dialogEditWireguardServer">
            <thead>
                <tr>
                    <th data-column-id="enabled" data-type="string" data-formatter="rowtoggle">{{ lang._('Enabled') }}</th>
                    <th data-column-id="name" data-type="string" data-visible="true">{{ lang._('Name') }}</th>
                    <th data-column-id="interface" data-type="string" data-visible="true">{{ lang._('Interface') }}</th>
                    <th data-column-id="tunneladdress" data-type="string" data-visible="true">{{ lang._('Tunnel Address') }}</th>
                    <th data-column-id="port" data-type="string" data-visible="true">{{ lang._('Port') }}</th>
                    <th data-column-id="peers" data-type="string" data-visible="true">{{ lang._('Endpoints') }}</th>
=======
    </div>
    <div id="instances" class="tab-pane fade in">
        <span id="keygen_div" style="display:none" class="pull-right">
            <button id="keygen" type="button" class="btn btn-secondary" title="{{ lang._('Generate new keypair.') }}" data-toggle="tooltip">
              <i class="fa fa-fw fa-gear"></i>
            </button>
        </span>
        <table id="grid-instances" class="table table-condensed table-hover table-striped" data-editDialog="dialogEditWireguardServer">
            <thead>
                <tr>
                    <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                    <th data-column-id="enabled" data-type="string" data-formatter="rowtoggle">{{ lang._('Enabled') }}</th>
                    <th data-column-id="name" data-type="string" data-visible="true">{{ lang._('Name') }}</th>
                    <th data-column-id="interface" data-type="string" data-visible="true">{{ lang._('Device') }}</th>
                    <th data-column-id="tunneladdress" data-type="string" data-visible="true">{{ lang._('Tunnel Address') }}</th>
                    <th data-column-id="port" data-type="string" data-visible="true">{{ lang._('Port') }}</th>
                    <th data-column-id="peers" data-type="string" data-visible="true">{{ lang._('Peers') }}</th>
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
                    <th data-column-id="uuid" data-type="string" data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                    <th data-column-id="commands" data-formatter="commands" data-sortable="false">{{ lang._('Commands') }}</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7"></td>
                    <td>
                        <button data-action="add" type="button" class="btn btn-xs btn-default"><span class="fa fa-plus"></span></button>
<<<<<<< HEAD
=======
                        <button data-action="deleteSelected" type="button" class="btn btn-xs btn-default"><span class="fa fa-fw fa-trash-o"></span></button>
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
                    </td>
                </tr>
            </tfoot>
        </table>
<<<<<<< HEAD
        <div class="col-md-12">
            <hr />
            <button class="btn btn-primary" id="saveAct_server" type="button"><b>{{ lang._('Apply') }}</b> <i id="saveAct_server_progress"></i></button>
            <br /><br />
        </div>
    </div>
    <div id="showconf" class="tab-pane fade in">
      <pre id="listshowconf"></pre>
    </div>
    <div id="showhandshake" class="tab-pane fade in">
      <pre id="listshowhandshake"></pre>
    </div>
</div>

{{ partial("layout_partials/base_dialog",['fields':formDialogEditWireguardClient,'id':'dialogEditWireguardClient','label':lang._('Edit Endpoint')])}}
{{ partial("layout_partials/base_dialog",['fields':formDialogEditWireguardServer,'id':'dialogEditWireguardServer','label':lang._('Edit Local Configuration')])}}

<script>

// Put API call into a function, needed for auto-refresh
function update_showconf() {
    ajaxCall(url="/api/wireguard/service/showconf", sendData={}, callback=function(data,status) {
        $("#listshowconf").text(data['response']);
    });
}

function update_showhandshake() {
    ajaxCall(url="/api/wireguard/service/showhandshake", sendData={}, callback=function(data,status) {
        $("#listshowhandshake").text(data['response']);
    });
}

$( document ).ready(function() {
    var data_get_map = {'frm_general_settings':"/api/wireguard/general/get"};
    mapDataToFormUI(data_get_map).done(function(data){
        formatTokenizersUI();
        $('.selectpicker').selectpicker('refresh');
    });

    $("#grid-clients").UIBootgrid(
        {
            'search':'/api/wireguard/client/searchClient',
            'get':'/api/wireguard/client/getClient/',
            'set':'/api/wireguard/client/setClient/',
            'add':'/api/wireguard/client/addClient/',
            'del':'/api/wireguard/client/delClient/',
            'toggle':'/api/wireguard/client/toggleClient/'
        }
    );

    $("#grid-servers").UIBootgrid(
        {
            'search':'/api/wireguard/server/searchServer',
            'get':'/api/wireguard/server/getServer/',
            'set':'/api/wireguard/server/setServer/',
            'add':'/api/wireguard/server/addServer/',
            'del':'/api/wireguard/server/delServer/',
            'toggle':'/api/wireguard/server/toggleServer/'
        }
    );

    // Call update funcs once when page loaded
    update_showconf();
    update_showhandshake();

    // Call function update_neighbor with a auto-refresh of 5 seconds
    setInterval(update_showconf, 5000);
    setInterval(update_showhandshake, 5000);

    $("#saveAct").click(function(){
        saveFormToEndpoint(url="/api/wireguard/general/set", formid='frm_general_settings',callback_ok=function(){
        $("#saveAct_progress").addClass("fa fa-spinner fa-pulse");
            ajaxCall(url="/api/wireguard/service/reconfigure", sendData={}, callback=function(data,status) {
                $("#saveAct_progress").removeClass("fa fa-spinner fa-pulse");
            });
        });
    });

    $("#saveAct_client").click(function(){
        saveFormToEndpoint(url="/api/wireguard/client/set", formid='frm_general_settings',callback_ok=function(){
        $("#saveAct_client_progress").addClass("fa fa-spinner fa-pulse");
            ajaxCall(url="/api/wireguard/service/reconfigure", sendData={}, callback=function(data,status) {
                $("#saveAct_client_progress").removeClass("fa fa-spinner fa-pulse");
            });
        });
    });

    $("#saveAct_server").click(function(){
        saveFormToEndpoint(url="/api/wireguard/server/set", formid='frm_general_settings',callback_ok=function(){
        $("#saveAct_server_progress").addClass("fa fa-spinner fa-pulse");
            ajaxCall(url="/api/wireguard/service/reconfigure", sendData={}, callback=function(data,status) {
                $("#saveAct_server_progress").removeClass("fa fa-spinner fa-pulse");
            });
        });
    });

});
</script>
=======
    </div>
</div>

<section class="page-content-main">
    <div class="content-box">
        <div class="col-md-12">
            <br/>
            <button class="btn btn-primary" id="reconfigureAct"
                    data-endpoint='/api/wireguard/service/reconfigure'
                    data-label="{{ lang._('Apply') }}"
                    data-error-title="{{ lang._('Error reconfiguring WireGuard') }}"
                    type="button"
            ></button>
            <br/><br/>
        </div>
    </div>
</section>

{{ partial("layout_partials/base_dialog",['fields':formDialogEditWireguardClient,'id':'dialogEditWireguardClient','label':lang._('Edit peer')])}}
{{ partial("layout_partials/base_dialog",['fields':formDialogEditWireguardServer,'id':'dialogEditWireguardServer','label':lang._('Edit instance')])}}
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
