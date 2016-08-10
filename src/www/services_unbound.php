<?php

/*
    Copyright (C) 2014-2016 Deciso B.V.
    Copyright (C) 2014 Warren Baker <warren@pfsense.org>
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
       this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
*/

require_once("guiconfig.inc");
require_once("unbound.inc");
require_once("services.inc");
require_once("system.inc");
require_once("interfaces.inc");

if (empty($config['unbound']) || !is_array($config['unbound'])) {
    $config['unbound'] = array();
}
$a_unboundcfg =& $config['unbound'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pconfig = array();
    // boolean values
    $pconfig['enable'] = isset($a_unboundcfg['enable']);
    $pconfig['dnssec'] = isset($a_unboundcfg['dnssec']);
    $pconfig['forwarding'] = isset($a_unboundcfg['forwarding']);
    $pconfig['regdhcp'] = isset($a_unboundcfg['regdhcp']);
    $pconfig['regdhcpstatic'] = isset($a_unboundcfg['regdhcpstatic']);
    $pconfig['txtsupport'] = isset($a_unboundcfg['txtsupport']);
    // text values
    $pconfig['port'] = !empty($a_unboundcfg['port']) ? $a_unboundcfg['port'] : null;
    $pconfig['custom_options'] = !empty($a_unboundcfg['custom_options']) ? $a_unboundcfg['custom_options'] : null;
    // array types
    $pconfig['active_interface'] = !empty($a_unboundcfg['active_interface']) ? explode(",", $a_unboundcfg['active_interface']) : array();
    $pconfig['outgoing_interface'] = !empty($a_unboundcfg['outgoing_interface']) ? explode(",", $a_unboundcfg['outgoing_interface']) : array();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_errors = array();
    $pconfig = $_POST;

    if (!empty($pconfig['apply'])) {
        services_unbound_configure();
        clear_subsystem_dirty('unbound');
        /* Update resolv.conf in case the interface bindings exclude localhost. */
        system_resolvconf_generate();
        header("Location: services_unbound.php");
        exit;
    } else {
        // perform validations
        if (isset($pconfig['enable']) && isset($config['dnsmasq']['enable']) && (empty($pconfig['port']) || $pconfig['port'] == '53')) {
            $input_errors[] = gettext("The DNS Forwarder is still active. Disable it before enabling the DNS Resolver.");
        }
        if (empty($pconfig['active_interface'])) {
            $input_errors[] = gettext("A single network interface needs to be selected for the DNS Resolver to bind to.");
        }
        if (empty($pconfig['outgoing_interface'])) {
            $input_errors[] = gettext("A single outgoing network interface needs to be selected for the DNS Resolver to use for outgoing DNS requests.");
        }
        if (!empty($pconfig['port']) && !is_port($pconfig['port'])) {
            $input_errors[] = gettext("You must specify a valid port number.");
        }

        if (count($input_errors) == 0) {
            // save form data
            // text types
            if (!empty($pconfig['port'])) {
                $a_unboundcfg['port'] = $pconfig['port'];
            } elseif  (isset($a_unboundcfg['port'])) {
                unset($a_unboundcfg['port']);
            }
            $a_unboundcfg['custom_options'] = !empty($pconfig['custom_options']) ? str_replace("\r\n", "\n", $pconfig['custom_options']) : null;
            // boolean values
            $a_unboundcfg['enable'] = !empty($pconfig['enable']);
            $a_unboundcfg['dnssec'] = !empty($pconfig['dnssec']);
            $a_unboundcfg['forwarding'] = !empty($pconfig['forwarding']);
            $a_unboundcfg['regdhcp'] = !empty($pconfig['regdhcp']);
            $a_unboundcfg['regdhcpstatic'] = !empty($pconfig['regdhcpstatic']);
            $a_unboundcfg['txtsupport'] = !empty($pconfig['txtsupport']);

            // array types
            $a_unboundcfg['active_interface'] = !empty( $pconfig['active_interface']) ? implode(",", $pconfig['active_interface']) : array();
            $a_unboundcfg['outgoing_interface'] = !empty( $pconfig['outgoing_interface']) ? implode(",", $pconfig['outgoing_interface']) : array();

            write_config("DNS Resolver configured.");
            mark_subsystem_dirty('unbound');
            header("Location: services_unbound.php");
            exit;
        }
    }
}


$service_hook = 'unbound';
legacy_html_escape_form_data($pconfig);
include_once("head.inc");
?>

<body>
<script type="text/javascript">
    $( document ).ready(function() {
        $("#show_advanced_dns").click(function(){
            $(this).parent().parent().hide();
            $(".showadv").show();
            $(window).trigger('resize');
        })
        // show advanced when option set
        if ($("#outgoing_interface").val() != "" || $("#custom_options").val() != "") {
            $("#show_advanced_dns").click();
        }
    });
</script>
<?php include("fbegin.inc"); ?>
  <section class="page-content-main">
    <div class="container-fluid">
      <div class="row">
        <?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
        <?php if (isset($savemsg)) print_info_box($savemsg); ?>
        <?php if (is_subsystem_dirty('unbound')): ?><br/>
        <?php print_info_box_apply(gettext("The configuration for the DNS Resolver, has been changed") . ".<br />" . gettext("You must apply the changes in order for them to take effect."));?><br />
        <?php endif; ?>
        <form method="post" name="iform" id="iform">
          <section class="col-xs-12">
            <div class="tab-content content-box col-xs-12">
                <div class="table-responsive">
                  <table class="table table-striped opnsense_standard_table_form">
                    <tbody>
                      <tr>
                        <td width="22%"><strong><?=gettext("General DNS Resolver Options");?></strong></td>
                        <td width="78%" align="right">
                          <small><?=gettext("full help"); ?> </small>
                          <i class="fa fa-toggle-off text-danger"  style="cursor: pointer;" id="show_all_help_page" type="button"></i>
                        </td>
                      </tr>
                      <tr>
                        <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("Enable");?></td>
                        <td>
                          <input name="enable" type="checkbox" value="yes" <?=!empty($pconfig['enable']) ? "checked=\"checked\"" : "";?> />
                          <strong><?=gettext("Enable DNS Resolver");?></strong>
                        </td>
                      </tr>
                      <tr>
                        <td><a id="help_for_port" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Listen Port");?></td>
                        <td>
                            <input name="port" type="text" id="port" size="6" value="<?=$pconfig['port'];?>" />
                            <div class="hidden" for="help_for_port">
                                <?=gettext("The port used for responding to DNS queries. It should normally be left blank unless another service needs to bind to TCP/UDP port 53.");?>
                            </div>
                        </td>
                      </tr>
                      <tr>
                        <td><a id="help_for_active_interface" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Network Interfaces"); ?></td>
                        <td>
                          <select name="active_interface[]" multiple="multiple" size="3" class="selectpicker" data-live-search="true">
                            <option value="" <?=empty($pconfig['active_interface'][0]) ? 'selected="selected"' : ""; ?>><?=gettext("All");?></option>
<?php
                            foreach (get_possible_listen_ips(false, false) as $laddr):?>
                            <option value="<?=$laddr['value'];?>" <?=in_array($laddr['value'], $pconfig['active_interface']) ? 'selected="selected"' : "";?>><?=htmlspecialchars($laddr['name']);?></option>
<?php
                            endforeach; ?>
                          </select>
                          <div class="hidden" for="help_for_active_interface">
                            <?=gettext("Interface IPs used by the DNS Resolver for responding to queries from clients. If an interface has both IPv4 and IPv6 IPs, both are used. Queries to other interface IPs not selected below are discarded. The default behavior is to respond to queries on every available IPv4 and IPv6 address.");?>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("DNSSEC");?></td>
                        <td>
                          <input name="dnssec" type="checkbox" value="yes" <?=!empty($pconfig['dnssec']) ? "checked=\"checked\"" : "";?> />
                          <strong><?=gettext("Enable DNSSEC Support");?></strong>
                        </td>
                      </tr>
                      <tr>
                        <td><a id="help_for_forwarding" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("DNS Query Forwarding");?></td>
                        <td>
                          <input name="forwarding" type="checkbox" value="yes" <?=!empty($pconfig['forwarding']) ? "checked=\"checked\"" : "";?> />
                          <strong><?=gettext("Enable Forwarding Mode");?></strong>
                          <div class="hidden" for="help_for_forwarding">
                            <?= gettext('The configured system nameservers will be used to forward queries to.') ?>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td><a id="help_for_regdhcp" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("DHCP Registration");?></td>
                        <td>
                          <input name="regdhcp" type="checkbox" id="regdhcp" value="yes" <?=!empty($pconfig['regdhcp']) ? "checked=\"checked\"" : "";?> />
                          <strong><?=gettext("Register DHCP leases in the DNS Resolver");?></strong>
                          <div class="hidden" for="help_for_regdhcp">
                            <?= sprintf(gettext("If this option is set, then machines that specify".
                            " their hostname when requesting a DHCP lease will be registered".
                            " in the DNS Resolver, so that their name can be resolved.".
                            " You should also set the domain in %sSystem:".
                            " General setup%s to the proper value."),'<a href="system_general.php">','</a>')?>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td><a id="help_for_regdhcpstatic" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Static DHCP");?></td>
                        <td>
                          <input name="regdhcpstatic" type="checkbox" id="regdhcpstatic" value="yes" <?=!empty($pconfig['regdhcpstatic']) ? "checked=\"checked\"" : "";?> />
                          <strong><?=gettext("Register DHCP static mappings in the DNS Resolver");?></strong>
                          <div class="hidden" for="help_for_regdhcpstatic">
                            <?= sprintf(gettext("If this option is set, then DHCP static mappings will ".
                                "be registered in the DNS Resolver, so that their name can be ".
                                "resolved. You should also set the domain in %s".
                                "System: General setup%s to the proper value."),'<a href="system_general.php">','</a>');?>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td><a id="help_for_txtsupport" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("TXT Comment Support");?></td>
                        <td>
                          <input name="txtsupport" type="checkbox" value="yes" <?=!empty($pconfig['txtsupport']) ? "checked=\"checked\"" : "";?> />
                          <div class="hidden" for="help_for_txtsupport">
                            <?=gettext("If this option is set, then any descriptions associated with Host entries and DHCP Static mappings will create a corresponding TXT record.");?><br />
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("Advanced");?></td>
                        <td>
                          <input id="show_advanced_dns" type="button" class="btn btn-xs btn-default"  value="<?=gettext("Advanced"); ?>" /> - <?=gettext("Show advanced option");?>
                        </td>
                      </tr>
                      <tr class="showadv" style="display:none">
                        <td><a id="help_for_custom_options" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?= gettext('Custom options') ?></td>
                        <td>
                          <textarea rows="6" cols="78" name="custom_options" id="custom_options"><?=$pconfig['custom_options'];?></textarea>
                          <div class="hidden" for="help_for_custom_options">
                            <?=gettext("Enter any additional options you would like to add to the DNS Resolver configuration here."); ?>
                          </div>
                        </td>
                      </tr>
                      <tr class="showadv" style="display:none">
                        <td><a id="help_for_outgoing_interface" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Outgoing Network Interfaces"); ?></td>
                        <td>
                          <select id="outgoing_interface" name="outgoing_interface[]" multiple="multiple" size="3" class="selectpicker" data-live-search="true">
                            <option value="" <?=empty($pconfig['outgoing_interface'][0]) ? 'selected="selected"' : ""; ?>><?=gettext("All");?></option>
<?php
                            foreach (get_possible_listen_ips(true) as $laddr):?>
                            <option value="<?=$laddr['value'];?>" <?=in_array($laddr['value'], $pconfig['outgoing_interface']) ? 'selected="selected"' : "";?>>
                              <?=htmlspecialchars($laddr['name']);?>
                            </option>
<?php
                            endforeach; ?>

                          </select>
                          <div class="hidden" for="help_for_outgoing_interface">
                            <?=gettext("Utilize different network interface(s) that the DNS Resolver will use to send queries to authoritative servers and receive their replies. By default all interfaces are used. Note that setting explicit outgoing interfaces only works when they are statically configured.");?>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td></td>
                        <td>
                          <input name="submit" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" />
                        </td>
                      </tr>
                      <tr>
                        <td colspan="2">
                          <?= sprintf(gettext("If the DNS Resolver is enabled, the DHCP".
                          " service (if enabled) will automatically serve the LAN IP".
                          " address as a DNS server to DHCP clients so they will use".
                          " the DNS Resolver. If Forwarding, is enabled, the DNS Resolver will use the DNS servers".
                          " entered in %sSystem: General setup%s".
                          " or those obtained via DHCP or PPP on WAN if the &quot;Allow".
                          " DNS server list to be overridden by DHCP/PPP on WAN&quot;".
                          " is checked."),'<a href="system_general.php">','</a>');?>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
          </section>
         </form>
      </div>
    </div>
  </section>
<?php include("foot.inc"); ?>
