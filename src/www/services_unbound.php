<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
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
require_once("pfsense-utils.inc");
require_once("interfaces.inc");

if (!is_array($config['unbound']))
	$config['unbound'] = array();
$a_unboundcfg =& $config['unbound'];

if (isset($config['unbound']['enable']))
	$pconfig['enable'] = true;
if (isset($config['unbound']['dnssec']))
	$pconfig['dnssec'] = true;
if (isset($config['unbound']['forwarding']))
	$pconfig['forwarding'] = true;
if (isset($config['unbound']['regdhcp']))
	$pconfig['regdhcp'] = true;
if (isset($config['unbound']['regdhcpstatic']))
	$pconfig['regdhcpstatic'] = true;
if (isset($config['unbound']['txtsupport']))
	$pconfig['txtsupport'] = true;

$pconfig['port'] = $config['unbound']['port'];
$pconfig['custom_options'] = $config['unbound']['custom_options'];

if (empty($config['unbound']['active_interface']))
	$pconfig['active_interface'] = array();
else
	$pconfig['active_interface'] = explode(",", $config['unbound']['active_interface']);
if (empty($config['unbound']['outgoing_interface']))
	$pconfig['outgoing_interface'] = array();
else
	$pconfig['outgoing_interface'] = explode(",", $config['unbound']['outgoing_interface']);

if ($_POST) {
	unset($input_errors);

	if ($_POST['apply']) {
		$retval = services_unbound_configure();
		$savemsg = get_std_save_message();
		if ($retval == 0) {
			clear_subsystem_dirty('unbound');
		}
		/* Update resolv.conf in case the interface bindings exclude localhost. */
		system_resolvconf_generate();
	} else {
		$pconfig = $_POST;

		if (isset($_POST['enable']) && isset($config['dnsmasq']['enable']))
			$input_errors[] = gettext("The DNS Forwarder is still active. Disable it before enabling the DNS Resolver.");

		if (empty($_POST['active_interface']))
			$input_errors[] = gettext("A single network interface needs to be selected for the DNS Resolver to bind to.");

		if (empty($_POST['outgoing_interface']))
			$input_errors[] = gettext("A single outgoing network interface needs to be selected for the DNS Resolver to use for outgoing DNS requests.");

		if ($_POST['port'])
			if (is_port($_POST['port']))
				$a_unboundcfg['port'] = $_POST['port'];
			else
				$input_errors[] = gettext("You must specify a valid port number.");
		else if (isset($config['unbound']['port']))
			unset($config['unbound']['port']);

		if (isset($_POST['enable']))
			$a_unboundcfg['enable'] = true;
		else
			unset($a_unboundcfg['enable']);
		if (isset($_POST['dnssec']))
			$a_unboundcfg['dnssec'] = true;
		else
			unset($a_unboundcfg['dnssec']);
		if (isset($_POST['forwarding']))
			$a_unboundcfg['forwarding'] = true;
		else
			unset($a_unboundcfg['forwarding']);
		if (isset($_POST['regdhcp']))
			$a_unboundcfg['regdhcp'] = true;
		else
			unset($a_unboundcfg['regdhcp']);
		if (isset($_POST['regdhcpstatic']))
			$a_unboundcfg['regdhcpstatic'] = true;
		else
			unset($a_unboundcfg['regdhcpstatic']);
		if (isset($_POST['txtsupport']))
			$a_unboundcfg['txtsupport'] = true;
		else
			unset($a_unboundcfg['txtsupport']);
		if (is_array($_POST['active_interface']) && !empty($_POST['active_interface']))
			$a_unboundcfg['active_interface'] = implode(",", $_POST['active_interface']);

		if (is_array($_POST['outgoing_interface']) && !empty($_POST['outgoing_interface']))
			$a_unboundcfg['outgoing_interface'] = implode(",", $_POST['outgoing_interface']);

		$a_unboundcfg['custom_options'] = str_replace("\r\n", "\n", $_POST['custom_options']);

		if (!$input_errors) {
			write_config("DNS Resolver configured.");
			mark_subsystem_dirty('unbound');
		}
	}
}

$service_hook = 'unbound';

include_once("head.inc");

?>

<body>

<script type="text/javascript">
//<![CDATA[
function enable_change(enable_over) {
	var endis;
	endis = !(jQuery('#enable').is(":checked") || enable_over);
	jQuery("#active_interface,#outgoing_interface,#dnssec,#forwarding,#regdhcp,#regdhcpstatic,#dhcpfirst,#port,#txtsupport,#custom_options").prop('disabled', endis);
	jQuery("#active_interface,#outgoing_interface").selectpicker("refresh");
}
function show_advanced_dns() {
	jQuery("#showadv").show();
	jQuery("#showadvbox").hide();
}
//]]>
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

				<form action="services_unbound.php" method="post" name="iform" id="iform" onsubmit="presubmit()">

			    <section class="col-xs-12">

					<div class="tab-content content-box col-xs-12">

								<div class="table-responsive">
									<table class="table table-striped">

										<tbody>
											<tr>
												<td colspan="2" valign="top" class="listtopic"><strong><?=gettext("General DNS Resolver Options");?></strong></td>
											</tr>
											<tr>
												<td width="22%" valign="top" class="vncellreq"><?=gettext("Enable");?></td>
												<td width="78%" class="vtable"><p>
													<input name="enable" type="checkbox" id="enable" value="yes" <?php if (isset($pconfig['enable'])) echo "checked=\"checked\"";?> onclick="enable_change(false)" />
													<strong><?=gettext("Enable DNS Resolver");?><br />
													</strong></p>
												</td>
											</tr>
											<tr>
												<td width="22%" valign="top" class="vncellreq"><?=gettext("Listen Port");?></td>
												<td width="78%" class="vtable">
													<p>
														<input name="port" type="text" id="port" size="6" <?php if ($pconfig['port']) echo "value=\"{$pconfig['port']}\"";?> />
														<br /><br />
														<?=gettext("The port used for responding to DNS queries. It should normally be left blank unless another service needs to bind to TCP/UDP port 53.");?>
													</p>
												</td>
											</tr>
											<tr>
												<td width="22%" valign="top" class="vncellreq"><?=gettext("Network Interfaces"); ?></td>
												<td width="78%" class="vtable">
													<?php
														$interface_addresses = get_possible_listen_ips(true);
														$size=count($interface_addresses)+1;
													?>
													<?=gettext("Interface IPs used by the DNS Resolver for responding to queries from clients. If an interface has both IPv4 and IPv6 IPs, both are used. Queries to other interface IPs not selected below are discarded. The default behavior is to respond to queries on every available IPv4 and IPv6 address.");?>
													<br /><br />
													<select id="active_interface" name="active_interface[]" multiple="multiple" size="3" class="selectpicker" data-live-search="true">
														<option value="" <?php if (empty($pconfig['active_interface']) || empty($pconfig['active_interface'][0])) echo 'selected="selected"'; ?>>All</option>
														<?php
															foreach ($interface_addresses as $laddr):
																$selected = "";
																if (in_array($laddr['value'], $pconfig['active_interface']))
																	$selected = 'selected="selected"';
														?>
														<option value="<?=$laddr['value'];?>" <?=$selected;?>>
															<?=htmlspecialchars($laddr['name']);?>
														</option>
														<?php endforeach; ?>
													</select>
													<br /><br />
												</td>
											</tr>
											<tr>
												<td width="22%" valign="top" class="vncellreq"><?=gettext("Outgoing Network Interfaces"); ?></td>
												<td width="78%" class="vtable">
													<?php
														$interface_addresses = get_possible_listen_ips(true);
														$size=count($interface_addresses)+1;
													?>
													<?=gettext("Utilize different network interface(s) that the DNS Resolver will use to send queries to authoritative servers and receive their replies. By default all interfaces are used.");?>
													<br /><br />
													<select id="outgoing_interface" name="outgoing_interface[]" multiple="multiple" size="3" class="selectpicker" data-live-search="true">
														<option value="" <?php if (empty($pconfig['outgoing_interface']) || empty($pconfig['outgoing_interface'][0])) echo 'selected="selected"'; ?>>All</option>
														<?php
															foreach ($interface_addresses as $laddr):
																$selected = "";
																if (in_array($laddr['value'], $pconfig['outgoing_interface']))
																$selected = 'selected="selected"';
														?>
														<option value="<?=$laddr['value'];?>" <?=$selected;?>>
															<?=htmlspecialchars($laddr['name']);?>
														</option>
														<?php endforeach; ?>
													</select>
													<br /><br />
												</td>
											</tr>
											<tr>
												<td width="22%" valign="top" class="vncellreq"><?=gettext("DNSSEC");?></td>
												<td width="78%" class="vtable"><p>
													<input name="dnssec" type="checkbox" id="dnssec" value="yes" <?php echo (isset($pconfig['dnssec']) ? "checked=\"checked\"" : "");?> />
													<strong><?=gettext("Enable DNSSEC Support");?><br />
													</strong></p>
												</td>
											</tr>
											<tr>
												<td width="22%" valign="top" class="vncellreq"><?=gettext("DNS Query Forwarding");?></td>
												<td width="78%" class="vtable"><p>
													<input name="forwarding" type="checkbox" id="forwarding" value="yes" <?php echo (isset($pconfig['forwarding']) ? "checked=\"checked\"" : "");?> />
													<strong><?=gettext("Enable Forwarding Mode");?></strong><br /></p>
												</td>
											</tr>
											<tr>
												<td width="22%" valign="top" class="vncellreq"><?=gettext("DHCP Registration");?></td>
												<td width="78%" class="vtable"><p>
													<input name="regdhcp" type="checkbox" id="regdhcp" value="yes" <?php if (isset($pconfig['regdhcp'])) echo "checked=\"checked\"";?> />
													<strong><?=gettext("Register DHCP leases in the DNS Resolver");?><br />
													</strong><?php printf(gettext("If this option is set, then machines that specify".
													" their hostname when requesting a DHCP lease will be registered".
													" in the DNS Resolver, so that their name can be resolved.".
													" You should also set the domain in %sSystem:".
													" General setup%s to the proper value."),'<a href="system_general.php">','</a>')?></p>
												</td>
											</tr>
											<tr>
												<td width="22%" valign="top" class="vncellreq"><?=gettext("Static DHCP");?></td>
												<td width="78%" class="vtable"><p>
													<input name="regdhcpstatic" type="checkbox" id="regdhcpstatic" value="yes" <?php if (isset($pconfig['regdhcpstatic'])) echo "checked=\"checked\"";?> />
													<strong><?=gettext("Register DHCP static mappings in the DNS Resolver");?><br />
													</strong><?php printf(gettext("If this option is set, then DHCP static mappings will ".
															"be registered in the DNS Resolver, so that their name can be ".
															"resolved. You should also set the domain in %s".
															"System: General setup%s to the proper value."),'<a href="system_general.php">','</a>');?></p>
												</td>
											</tr>
											<tr>
												<td width="22%" valign="top" class="vncellreq"><?=gettext("TXT Comment Support");?></td>
												<td width="78%" class="vtable"><p>
													<input name="txtsupport" type="checkbox" id="txtsupport" value="yes" <?php echo (isset($pconfig['txtsupport']) ? "checked=\"checked\"" : "");?> />
													<strong><?=gettext("If this option is set, then any descriptions associated with Host entries and DHCP Static mappings will create a corresponding TXT record.");?><br />
													</strong></p>
												</td>
											</tr>
											<tr>
												<td width="22%" valign="top" class="vncellreq"><?=gettext("Advanced");?></td>
												<td width="78%" class="vtable">
													<div id="showadvbox" <?php if ($pconfig['custom_options']) echo "style='display:none'"; ?>>
														<input type="button" class="btn btn-xs btn-default" onclick="show_advanced_dns()" value="<?=gettext("Advanced"); ?>" /> - <?=gettext("Show advanced option");?>
													</div>
													<div id="showadv" <?php if (empty($pconfig['custom_options'])) echo "style='display:none'"; ?>>
														<strong><?=gettext("Advanced");?><br /></strong>
														<textarea rows="6" cols="78" name="custom_options" id="custom_options"><?=htmlspecialchars($pconfig['custom_options']);?></textarea><br />
														<?=gettext("Enter any additional options you would like to add to the DNS Resolver configuration here, separated by a space or newline"); ?><br />
													</div>
												</td>
											</tr>
											<tr>
												<td colspan="2">
													<input name="submit" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" onclick="enable_change(true)" />
												</td>
											</tr>
										</tbody>
									</table>
								</div>

					<div class="container-fluid">
                        <p><span class="vexpl"><span class="text-danger"><strong><?=gettext("Note:");?><br />
                        </strong></span><?php printf(gettext("If the DNS Resolver is enabled, the DHCP".
                        " service (if enabled) will automatically serve the LAN IP".
                        " address as a DNS server to DHCP clients so they will use".
                        " the DNS Resolver. If Forwarding, is enabled, the DNS Resolver will use the DNS servers".
                        " entered in %sSystem: General setup%s".
                        " or those obtained via DHCP or PPP on WAN if the &quot;Allow".
                        " DNS server list to be overridden by DHCP/PPP on WAN&quot;".
                        " is checked."),'<a href="system_general.php">','</a>');?><br />
                        </span></p>
					</div>

					</div>
			    </section>

			   </form>

			</div>
		</div>
	</section>


<script type="text/javascript">
//<![CDATA[
enable_change(false);
//]]>
</script>
<?php include("foot.inc"); ?>
