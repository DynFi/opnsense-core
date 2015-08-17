<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2003-2004 Bob Zoller <bob@kludgebox.com> and Manuel Kasper <mk@neon1.net>.
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
require_once("interfaces.inc");
require_once("filter.inc");
require_once("services.inc");
require_once("system.inc");
require_once("pfsense-utils.inc");
require_once("unbound.inc");

$pconfig['enable'] = isset($config['dnsmasq']['enable']);
$pconfig['regdhcp'] = isset($config['dnsmasq']['regdhcp']);
$pconfig['regdhcpstatic'] = isset($config['dnsmasq']['regdhcpstatic']);
$pconfig['dhcpfirst'] = isset($config['dnsmasq']['dhcpfirst']);
$pconfig['strict_order'] = isset($config['dnsmasq']['strict_order']);
$pconfig['domain_needed'] = isset($config['dnsmasq']['domain_needed']);
$pconfig['no_private_reverse'] = isset($config['dnsmasq']['no_private_reverse']);
$pconfig['port'] = $config['dnsmasq']['port'];
$pconfig['custom_options'] = $config['dnsmasq']['custom_options'];

$pconfig['strictbind'] = isset($config['dnsmasq']['strictbind']);
if (!empty($config['dnsmasq']['interface']))
	$pconfig['interface'] = explode(",", $config['dnsmasq']['interface']);
else
	$pconfig['interface'] = array();

if (!is_array($config['dnsmasq']['hosts']))
	$config['dnsmasq']['hosts'] = array();

if (!is_array($config['dnsmasq']['domainoverrides']))
	$config['dnsmasq']['domainoverrides'] = array();


$a_hosts = &$config['dnsmasq']['hosts'];
$a_domainOverrides = &$config['dnsmasq']['domainoverrides'];

if ($_POST && isset($_POST['submit'])) {

	$pconfig = $_POST;
	unset($input_errors);

	$config['dnsmasq']['enable'] = ($_POST['enable']) ? true : false;
	$config['dnsmasq']['regdhcp'] = ($_POST['regdhcp']) ? true : false;
	$config['dnsmasq']['regdhcpstatic'] = ($_POST['regdhcpstatic']) ? true : false;
	$config['dnsmasq']['dhcpfirst'] = ($_POST['dhcpfirst']) ? true : false;
	$config['dnsmasq']['strict_order'] = ($_POST['strict_order']) ? true : false;
	$config['dnsmasq']['domain_needed'] = ($_POST['domain_needed']) ? true : false;
	$config['dnsmasq']['no_private_reverse'] = ($_POST['no_private_reverse']) ? true : false;
	$config['dnsmasq']['custom_options'] = str_replace("\r\n", "\n", $_POST['custom_options']);
	$config['dnsmasq']['strictbind'] = ($_POST['strictbind']) ? true : false;

	if ($_POST['port'])
		if(is_port($_POST['port']))
			$config['dnsmasq']['port'] = $_POST['port'];
		else
			$input_errors[] = gettext("You must specify a valid port number");
	else if (isset($config['dnsmasq']['port']))
		unset($config['dnsmasq']['port']);

	if (is_array($_POST['interface']))
		$config['dnsmasq']['interface'] = implode(",", $_POST['interface']);
	elseif (isset($config['dnsmasq']['interface']))
		unset($config['dnsmasq']['interface']);

	if ($config['dnsmasq']['custom_options']) {
		$args = '';
		foreach (preg_split('/\s+/', $config['dnsmasq']['custom_options']) as $c)
			$args .= escapeshellarg("--{$c}") . " ";
		exec("/usr/local/sbin/dnsmasq --test $args", $output, $rc);
		if ($rc != 0)
			$input_errors[] = gettext("Invalid custom options");
	}

	if (!$input_errors) {
		write_config();

		$retval = services_dnsmasq_configure();
		$savemsg = get_std_save_message();
	}
} elseif ($_POST && isset($_POST['apply']) ) {

		// Reload filter (we might need to sync to CARP hosts)
		filter_configure();
		/* Update resolv.conf in case the interface bindings exclude localhost. */
		system_resolvconf_generate();
		system_hosts_generate();
		$retval = services_dnsmasq_configure();
		$savemsg = get_std_save_message();
		if ($retval == 0)
			clear_subsystem_dirty('hosts');

}

if ($_GET['act'] == "del") {
	if ($_GET['type'] == 'host') {
		if ($a_hosts[$_GET['id']]) {
			unset($a_hosts[$_GET['id']]);
			write_config();
			mark_subsystem_dirty('hosts');
			header("Location: services_dnsmasq.php");
			exit;
		}
	}
	elseif ($_GET['type'] == 'doverride') {
		if ($a_domainOverrides[$_GET['id']]) {
			unset($a_domainOverrides[$_GET['id']]);
			write_config();
			mark_subsystem_dirty('hosts');
			header("Location: services_dnsmasq.php");
			exit;
		}
	}
}

$closehead = false;
$pgtitle = array(gettext("Services"),gettext("DNS forwarder"));
$shortcut_section = "forwarder";
include("head.inc");

?>

<body>

<script type="text/javascript">
//<![CDATA[
function enable_change(enable_over) {
	var endis;
	endis = !(document.iform.enable.checked || enable_over);
	document.iform.regdhcp.disabled = endis;
	document.iform.regdhcpstatic.disabled = endis;
	document.iform.dhcpfirst.disabled = endis;
}
function show_advanced_dns() {
	document.getElementById("showadvbox").innerHTML='';
	aodiv = document.getElementById('showadv');
	aodiv.style.display = "block";
}
//]]>
</script>

<?php include("fbegin.inc"); ?>

	<section class="page-content-main">

		<div class="container-fluid">

			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
				<?php if (isset($savemsg)) print_info_box($savemsg); ?>
				<?php if (is_subsystem_dirty('hosts')): ?><br/>
				<?php print_info_box_np(gettext("The DNS forwarder configuration has been changed") . ".<br />" . gettext("You must apply the changes in order for them to take effect."));?><br />
				<?php endif; ?>

			    <section class="col-xs-12">

				<div class="content-box">

                        <form action="services_dnsmasq.php" method="post" name="iform" id="iform">

							<header class="content-box-head container-fluid">
					        <h3><?=gettext("General DNS Forwarder Options");?></h3>
					    </header>

					    <div class="content-box-main">
						  <div class="table-responsive">
					<table class="table table-striped table-sort">
									<tr>
										<td colspan="2" valign="top" class="listtopic"><?=gettext("General DNS Forwarder Options");?></td>
									</tr>
									<tr>
										<td width="22%" valign="top" class="vncellreq"><?=gettext("Enable");?></td>
										<td width="78%" class="vtable"><p>
											<input name="enable" type="checkbox" id="enable" value="yes" <?php if ($pconfig['enable'] == "yes") echo "checked=\"checked\"";?> onclick="enable_change(false)" />
											<strong><?=gettext("Enable DNS forwarder");?><br />
											</strong></p></td>
										</tr>
									<tr>
										<td width="22%" valign="top" class="vncellreq"><?=gettext("DHCP Registration");?></td>
										<td width="78%" class="vtable"><p>
											<input name="regdhcp" type="checkbox" id="regdhcp" value="yes" <?php if ($pconfig['regdhcp'] == "yes") echo "checked=\"checked\"";?> />
											<strong><?=gettext("Register DHCP leases in DNS forwarder");?><br />
											</strong><?php printf(gettext("If this option is set, then machines that specify".
											" their hostname when requesting a DHCP lease will be registered".
											" in the DNS forwarder, so that their name can be resolved.".
											" You should also set the domain in %sSystem:".
											" General setup%s to the proper value."),'<a href="system_general.php">','</a>')?></p>
										</td>
									</tr>
									<tr>
										<td width="22%" valign="top" class="vncellreq"><?=gettext("Static DHCP");?></td>
										<td width="78%" class="vtable"><p>
											<input name="regdhcpstatic" type="checkbox" id="regdhcpstatic" value="yes" <?php if ($pconfig['regdhcpstatic'] == "yes") echo "checked=\"checked\"";?> />
											<strong><?=gettext("Register DHCP static mappings in DNS forwarder");?><br />
											</strong><?php printf(gettext("If this option is set, then DHCP static mappings will ".
													"be registered in the DNS forwarder, so that their name can be ".
													"resolved. You should also set the domain in %s".
													"System: General setup%s to the proper value."),'<a href="system_general.php">','</a>');?></p>
										</td>
									</tr>
									<tr>
										<td width="22%" valign="top" class="vncellreq"><?=gettext("Prefer DHCP");?></td>
										<td width="78%" class="vtable"><p>
											<input name="dhcpfirst" type="checkbox" id="dhcpfirst" value="yes" <?php if ($pconfig['dhcpfirst'] == "yes") echo "checked=\"checked\"";?> />
											<strong><?=gettext("Resolve DHCP mappings first");?><br />
											</strong><?php printf(gettext("If this option is set, then DHCP mappings will ".
													"be resolved before the manual list of names below. This only ".
													"affects the name given for a reverse lookup (PTR)."));?></p>
										</td>
									</tr>
									<tr>
										<td rowspan="3" width="22%" valign="top" class="vncellreq"><?=gettext("DNS Query Forwarding");?></td>
										<td width="78%" class="vtable"><p>
											<input name="strict_order" type="checkbox" id="strict_order" value="yes" <?php if ($pconfig['strict_order'] == "yes") echo "checked=\"checked\"";?> />
											<strong><?=gettext("Query DNS servers sequentially");?><br />
											</strong><?php printf(gettext("If this option is set, %s DNS Forwarder (dnsmasq) will ".
													"query the DNS servers sequentially in the order specified (<i>System - General Setup - DNS Servers</i>), ".
													"rather than all at once in parallel. ".
													""), $g['product_name']); ?></p>
										</td>
									</tr>
									<tr>
										<td width="78%" class="vtable"><p>
											<input name="domain_needed" type="checkbox" id="domain_needed" value="yes" <?php if ($pconfig['domain_needed'] == "yes") echo "checked=\"checked\"";?> />
											<strong><?=gettext("Require domain");?><br />
											</strong><?php printf(gettext("If this option is set, %s DNS Forwarder (dnsmasq) will ".
													"not forward A or AAAA queries for plain names, without dots or domain parts, to upstream name servers.  ".
													"If the name is not known from /etc/hosts or DHCP then a \"not found\" answer is returned. ".
													""), $g['product_name']); ?></p>
										</td>
									</tr>
									<tr>
										<td width="78%" class="vtable"><p>
											<input name="no_private_reverse" type="checkbox" id="no_private_reverse" value="yes" <?php if ($pconfig['no_private_reverse'] == "yes") echo "checked=\"checked\"";?> />
											<strong><?=gettext("Do not forward private reverse lookups");?><br />
											</strong><?php printf(gettext("If this option is set, %s DNS Forwarder (dnsmasq) will ".
													"not forward reverse DNS lookups (PTR) for private addresses (RFC 1918) to upstream name servers.  ".
													"Any entries in the Domain Overrides section forwarding private \"n.n.n.in-addr.arpa\" names to a specific server are still forwarded. ".
													"If the IP to name is not known from /etc/hosts, DHCP or a specific domain override then a \"not found\" answer is immediately returned. ".
													""), $g['product_name']); ?></p>
										</td>
									</tr>
									<tr>
										<td width="22%" valign="top" class="vncellreq"><?=gettext("Listen Port");?></td>
										<td width="78%" class="vtable"><p>
											<input name="port" type="text" id="port" size="6" <?php if ($pconfig['port']) echo "value=\"{$pconfig['port']}\"";?> />
											<br /><br />
											<?=gettext("The port used for responding to DNS queries. It should normally be left blank unless another service needs to bind to TCP/UDP port 53.");?></p>
										</td>
									</tr>
									<tr>
										<td width="22%" valign="top" rowspan="2" class="vncellreq"><?=gettext("Interfaces"); ?></td>
										<td width="78%" class="vtable">
										<?php
											$interface_addresses = get_possible_listen_ips(true);
											$size=count($interface_addresses)+1;
										?>
											<?=gettext("Interface IPs used by the DNS Forwarder for responding to queries from clients. If an interface has both IPv4 and IPv6 IPs, both are used. Queries to other interface IPs not selected below are discarded. The default behavior is to respond to queries on every available IPv4 and IPv6 address.");?>
											<br /><br />
											<select id="interface" name="interface[]" multiple="multiple" class="formselect" size="<?php echo $size; ?>">
												<option value="" <?php if (empty($pconfig['interface']) || empty($pconfig['interface'][0])) echo 'selected="selected"'; ?>>All</option>
											<?php  foreach ($interface_addresses as $laddr):
													$selected = "";
													if (in_array($laddr['value'], $pconfig['interface']))
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
										<td width="78%" class="vtable"><p>
											<input name="strictbind" type="checkbox" id="strictbind" value="yes" <?php if ($pconfig['strictbind'] == "yes") echo "checked=\"checked\"";?> />
											<strong><?=gettext("Strict Interface Binding");?></strong>
											<br />
											<?= gettext("If this option is set, the DNS forwarder will only bind to the interfaces containing the IP addresses selected above, rather than binding to all interfaces and discarding queries to other addresses."); ?>
											<br /><br />
											<?= gettext("NOTE: This option does NOT work with IPv6. If set, dnsmasq will not bind to IPv6 addresses."); ?>
											</p>
										</td>
									</tr>
									<tr>
										<td width="22%" valign="top" class="vncellreq"><?=gettext("Advanced");?></td>
										<td width="78%" class="vtable">
											<div id="showadvbox" <?php if ($pconfig['custom_options']) echo "style='display:none'"; ?>>
												<input type="button" class="btn btn-default btn-xs" onclick="show_advanced_dns()" value="<?=gettext("Advanced"); ?>" /> - <?=gettext("Show advanced option");?>
											</div>
											<div id="showadv" <?php if (empty($pconfig['custom_options'])) echo "style='display:none'"; ?>>
												<strong><?=gettext("Advanced");?><br /></strong>
												<textarea rows="6" cols="78" name="custom_options" id="custom_options"><?=htmlspecialchars($pconfig['custom_options']);?></textarea><br />
												<?=gettext("Enter any additional options you would like to add to the dnsmasq configuration here, separated by a space or newline"); ?><br />
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<input name="submit" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" onclick="enable_change(true)" />
										</td>
									</tr>
								</table>
						  </div>

                            <div class="container-fluid">
							<p><span class="vexpl"><span class="text-danger"><strong><?=gettext("Note:");?><br />
							</strong></span><?php printf(gettext("If the DNS forwarder is enabled, the DHCP".
							" service (if enabled) will automatically serve the LAN IP".
							" address as a DNS server to DHCP clients so they will use".
							" the forwarder. The DNS forwarder will use the DNS servers".
							" entered in %sSystem: General setup%s".
							" or those obtained via DHCP or PPP on WAN if the &quot;Allow".
							" DNS server list to be overridden by DHCP/PPP on WAN&quot;".
							" is checked. If you don't use that option (or if you use".
							" a static IP address on WAN), you must manually specify at".
							" least one DNS server on the %sSystem: General setup%s page."),
							'<a href="system_general.php">','</a>','<a href="system_general.php">','</a>');?><br />
							</span></p>
                            </div>


							<div class="table-responsive">
					<table class="table table-striped table-sort">
						<tr>
							<td><?=gettext("Host Overrides");?></td>
						</tr>
									<tr>
										<td><br />
										<?=gettext("Entries in this section override individual results from the forwarders.");?>
										<?=gettext("Use these for changing DNS results or for adding custom DNS records.");?>
										</td>
									</tr>
									</table>
							</div>

							<div class="table-responsive">
					<table class="table table-striped table-sort">
									<thead>
									<tr>
										<td width="20%" class="listhdrr"><?=gettext("Host");?></td>
										<td width="25%" class="listhdrr"><?=gettext("Domain");?></td>
										<td width="20%" class="listhdrr"><?=gettext("IP");?></td>
										<td width="25%" class="listhdr"><?=gettext("Description");?></td>
										<td width="10%" class="list">
											<table border="0" cellspacing="0" cellpadding="1" summary="icons">
												<tr>
													<td width="17"></td>
													<td valign="middle"><a href="services_dnsmasq_edit.php" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-plus"></span></a></td>
												</tr>
											</table>
										</td>
									</tr>
									</thead>
									<tbody>
									<?php $i = 0; foreach ($a_hosts as $hostent): ?>
									<tr>
										<td class="listlr" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
											<?=strtolower($hostent['host']);?>&nbsp;
										</td>
										<td class="listr" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
											<?=strtolower($hostent['domain']);?>&nbsp;
										</td>
										<td class="listr" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
											<?=$hostent['ip'];?>&nbsp;
										</td>
										<td class="listbg" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
											<?=htmlspecialchars($hostent['descr']);?>&nbsp;
										</td>
										<td valign="middle" class="list nowrap">
											<table border="0" cellspacing="0" cellpadding="1" summary="icons">
												<tr>
													<td valign="middle"><a href="services_dnsmasq_edit.php?id=<?=$i;?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a></td>
													<td><a href="services_dnsmasq.php?type=host&amp;act=del&amp;id=<?=$i;?>" onclick="return confirm('<?=gettext("Do you really want to delete this host?");?>')" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-remove"></span></a></td>
												</tr>
											</table>
									</tr>
									<?php if (isset($hostent['aliases']['item'])): ?>
									<?php foreach ($hostent['aliases']['item'] as $alias): ?>
									<tr>
										<td class="listlr" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
											<?=strtolower($alias['host']);?>&nbsp;
										</td>
										<td class="listr" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
											<?=strtolower($alias['domain']);?>&nbsp;
										</td>
										<td class="listr" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
											Alias for <?=$hostent['host'] ? $hostent['host'] . '.' . $hostent['domain'] : $hostent['domain'];?>&nbsp;
										</td>
										<td class="listbg" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
											<?=htmlspecialchars($alias['description']);?>&nbsp;
										</td>
										<td valign="middle" class="list nowrap">
											<a href="services_dnsmasq_edit.php?id=<?=$i;?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
										</td>
									</tr>
									<?php endforeach; ?>
									<?php endif; ?>
									<?php $i++; endforeach; ?>
									<tr style="display:none"><td></td></tr>
									</tbody>
								</table>
							</div>

							<div class="table-responsive">
								<table class="table table-striped table-sort">
									<tr>
										<td colspan="5" valign="top" class="listtopic"><?=gettext("Domain Overrides");?></td>
									</tr>
									<tr>
										<td><p><?=gettext("Entries in this area override an entire domain, and subdomains, by specifying an".
										" authoritative DNS server to be queried for that domain.");?></p></td>
									</tr>
								</table>


								<table class="table table-striped table-sort">
									<thead>
									<tr>
										<td width="35%" class="listhdrr"><?=gettext("Domain");?></td>
										<td width="20%" class="listhdrr"><?=gettext("IP");?></td>
										<td width="35%" class="listhdr"><?=gettext("Description");?></td>
										<td width="10%" class="list">
											<table border="0" cellspacing="0" cellpadding="1" summary="add">
												<tr>
													<td width="17" height="17"></td>
													<td><a href="services_dnsmasq_domainoverride_edit.php" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-plus"></span></a></td>
												</tr>
											</table>
										</td>
									</tr>
									</thead>

									<tbody>
									<?php $i = 0; foreach ($a_domainOverrides as $doment): ?>
									<tr>
										<td class="listlr">
											<?=strtolower($doment['domain']);?>&nbsp;
										</td>
										<td class="listr">
											<?=$doment['ip'];?>&nbsp;
										</td>
										<td class="listbg">
											<?=htmlspecialchars($doment['descr']);?>&nbsp;
										</td>
										<td valign="middle" class="list nowrap"> <a href="services_dnsmasq_domainoverride_edit.php?id=<?=$i;?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
											&nbsp;<a href="services_dnsmasq.php?act=del&amp;type=doverride&amp;id=<?=$i;?>" onclick="return confirm('<?=gettext("Do you really want to delete this domain override?");?>')" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-remove"></span></a></td>
									</tr>
									<?php $i++; endforeach; ?>
									<tr style="display:none"><td></td></tr>
									</tbody>
								</table>
							</div>
							</div>
							</form>
							</section>
							</div>
							</div>
							</section>

<script type="text/javascript">
//<![CDATA[
enable_change(false);
//]]>
</script>
<?php include("foot.inc"); ?>
