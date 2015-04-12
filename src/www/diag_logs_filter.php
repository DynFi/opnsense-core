<?php

/*
	Copyright (C) 2014 Deciso B.V.
	Copyright (C) Jim Pingle jim@pingle.org
	Copyright (C) 2004-2009 Scott Ullrich
	Copyright (C) 2003-2009 Manuel Kasper <mk@neon1.net>,
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
require_once("filter_log.inc");

# --- AJAX RESOLVE ---
if (isset($_POST['resolve'])) {
	$ip = strtolower($_POST['resolve']);
	$res = (is_ipaddr($ip) ? gethostbyaddr($ip) : '');

	if ($res && $res != $ip)
		$response = array('resolve_ip' => $ip, 'resolve_text' => $res);
	else
		$response = array('resolve_ip' => $ip, 'resolve_text' => gettext("Cannot resolve"));

	echo json_encode(str_replace("\\","\\\\", $response)); // single escape chars can break JSON decode
	exit;
}

function getGETPOSTsettingvalue($settingname, $default)
{
	$settingvalue = $default;
	if($_GET[$settingname])
		$settingvalue = $_GET[$settingname];
	if($_POST[$settingname])
		$settingvalue = $_POST[$settingname];
	return $settingvalue;
}

$rulenum = getGETPOSTsettingvalue('getrulenum', null);
if($rulenum) {
	list($rulenum, $type) = explode(',', $rulenum);
	$rule = find_rule_by_number($rulenum,  $type);
	echo gettext("The rule that triggered this action is") . ":\n\n{$rule}";
	exit;
}

$filtersubmit = getGETPOSTsettingvalue('filtersubmit', null);
if ($filtersubmit) {
	$interfacefilter = getGETPOSTsettingvalue('interface', null);
	$filtertext = getGETPOSTsettingvalue('filtertext', "");
	$filterlogentries_qty = getGETPOSTsettingvalue('filterlogentries_qty', null);

	$filterfieldsarray = array();

	$actpass = getGETPOSTsettingvalue('actpass', null);
	$actblock = getGETPOSTsettingvalue('actblock', null);

	$filterfieldsarray['act'] = str_replace("  ", " ", trim($actpass . " " . $actblock));
	$filterfieldsarray['act'] = $filterfieldsarray['act'] != "" ? $filterfieldsarray['act'] : 'All';
	$filterfieldsarray['time'] = getGETPOSTsettingvalue('filterlogentries_time', null);
	$filterfieldsarray['interface'] = getGETPOSTsettingvalue('filterlogentries_interfaces', null);
	$filterfieldsarray['srcip'] = getGETPOSTsettingvalue('filterlogentries_sourceipaddress', null);
	$filterfieldsarray['srcport'] = getGETPOSTsettingvalue('filterlogentries_sourceport', null);
	$filterfieldsarray['dstip'] = getGETPOSTsettingvalue('filterlogentries_destinationipaddress', null);
	$filterfieldsarray['dstport'] = getGETPOSTsettingvalue('filterlogentries_destinationport', null);
	$filterfieldsarray['proto'] = getGETPOSTsettingvalue('filterlogentries_protocol', null);
	$filterfieldsarray['tcpflags'] = getGETPOSTsettingvalue('filterlogentries_protocolflags', null);
	$filterlogentries_qty = getGETPOSTsettingvalue('filterlogentries_qty', null);
}

$filter_logfile = '/var/log/filter.log';

$nentries = $config['syslog']['nentries'];

# Override Display Quantity
if ($filterlogentries_qty) {
	$nentries = $filterlogentries_qty;
}

if (!$nentries) {
	$nentries = 50;
}

if ($_POST['clear']) {
	clear_log_file($filter_logfile);
}

$pgtitle = array(gettext("Status"),gettext("System logs"),gettext("Firewall"));
$shortcut_section = "firewall";
include("head.inc");

?>

<script src="/javascript/filter_log.js" type="text/javascript"></script>


<body>

<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if ($input_errors) print_input_errors($input_errors); ?>

			    <section class="col-xs-12">

				<? $active_tab = "/diag_logs_filter.php"; include('diag_logs_tabs.php'); ?>

					<div class="tab-content content-box col-xs-12">
				    <div class="container-fluid">


							<? $tab_group = 'firewall'; include('diag_logs_pills.php'); ?>

							<form id="filterlogentries" name="filterlogentries" action="diag_logs_filter.php" method="post">
							<?php
								$Include_Act = explode(",", str_replace(" ", ",", $filterfieldsarray['act']));
								if ($filterfieldsarray['interface'] == "All") $interface = "";
							?>
							<div class="table-responsive widgetconfigdiv" id="filterlogentries_show"  style="<?=(!isset($config['syslog']['rawfilter']))?"":"display:none"?>">
                                <table class="table table-striped">
					      <thead>
					        <tr>
					          <th>Action</th>
					          <th>Time and interface</th>
					          <th>Source and destination IP Address</th>
					          <th>Source and destination port</th>
					          <th>Protocol</th>
					          <th>Protocol</th>
					        </tr>
					      </thead>
					      <tbody>
					        <tr>
					          <td>
						          <label class="__nowrap">
                                            <input id="actpass"   name="actpass"   type="checkbox" value="Pass"   <?php if (in_arrayi('Pass',   $Include_Act)) echo "checked=\"checked\""; ?> />&nbsp;&nbsp;Pass
                                          </label>
                                      </td>
					          <td><input type="text" class="form-control" placeholder="Time" id="filterlogentries_time" name="filterlogentries_time" value="<?= $filterfieldsarray['time'] ?>"></td>
					          <td><input type="text" class="form-control" placeholder="Source IP Address" id="filterlogentries_sourceipaddress" name="filterlogentries_sourceipaddress" value="<?= $filterfieldsarray['srcip'] ?>"></td>
					          <td><input type="text" class="form-control" placeholder="Source Port" id="filterlogentries_sourceport" name="filterlogentries_sourceport" value="<?= $filterfieldsarray['srcport'] ?>"></td>
					          <td><input type="text" class="form-control" placeholder="Protocol" id="filterlogentries_protocol" name="filterlogentries_protocol" value="<?= $filterfieldsarray['proto'] ?>"></td>
					          <td><input type="text" class="form-control" placeholder="Quantity" id="filterlogentries_qty" name="filterlogentries_qty" value="<?= $filterlogentries_qty ?>"></td>
					        </tr>
					        <tr>
					          <td>
						          <label class="__nowrap">
                                            <input id="actblock"  name="actblock"  type="checkbox" value="Block"  <?php if (in_arrayi('Block',  $Include_Act)) echo "checked=\"checked\""; ?> /> &nbsp;&nbsp;Block
                                          </label>
                                      </td>
					          <td><input type="text" class="form-control" placeholder="Interface" id="filterlogentries_interfaces" name="filterlogentries_interfaces" value="<?= $filterfieldsarray['interface'] ?>"></td>
					          <td><input type="text" class="form-control" placeholder="Destination IP Address" id="filterlogentries_destinationipaddress" name="filterlogentries_destinationipaddress" value="<?= $filterfieldsarray['dstip'] ?>"></td>
					          <td><input type="text" class="form-control" placeholder="Destination Port" id="filterlogentries_destinationport" name="filterlogentries_destinationport" value="<?= $filterfieldsarray['dstport'] ?>"></td>
					          <td><input type="text" class="form-control" placeholder="Protocol Flags" id="filterlogentries_protocolflags" name="filterlogentries_protocolflags" value="<?= $filterfieldsarray['tcpflags'] ?>"></td>
					          <td><input id="filtersubmit" name="filtersubmit" type="submit" class="btn btn-primary" style="vertical-align:top;" value="<?=gettext("Filter");?>" /></td>
					        </tr>
					      </tbody>
					    </table>
                            </div>

							</form>
				    </div>
					</div>
			    </section>

			    <!--
			     <section class="col-xs-12">

					<div class="tab-content content-box col-xs-12">
				    <div class="container-fluid">


							<div id="filterform_show" class="widgetconfigdiv" style="<?=(!isset($config['syslog']['rawfilter']))?"display:none":""?>">
								<form id="filterform" name="filterform" action="diag_logs_filter.php" method="post">
								<table width="0%" border="0" cellpadding="0" cellspacing="0" summary="firewall log">
								<tr>
									<td>
										<div align="center" style="vertical-align:top;"><?=gettext("Interface");?></div>
										<div align="center" style="vertical-align:top;">
										<select name="interface" onchange="dst_change(this.value,iface_old,document.iform.dsttype.value);iface_old = document.iform.interface.value;typesel_change();">
										<option value="" <?=$interfacefilter?"":"selected=\"selected\""?>>*Any interface</option>
										<?php
										$iflist = get_configured_interface_with_descr(false, true);
										foreach ($iflist as $if => $ifdesc)
											$interfaces[$if] = $ifdesc;

										if ($config['l2tp']['mode'] == "server")
											$interfaces['l2tp'] = "L2TP VPN";

										if ($config['pptpd']['mode'] == "server")
											$interfaces['pptp'] = "PPTP VPN";

										if (is_pppoe_server_enabled() && have_ruleint_access("pppoe"))
											$interfaces['pppoe'] = "PPPoE VPN";

										/* add ipsec interfaces */
										if (isset($config['ipsec']['enable']) || isset($config['ipsec']['client']['enable']))
											$interfaces["enc0"] = "IPsec";

										/* add openvpn/tun interfaces */
										if (isset($config['openvpn']['openvpn-server']) || isset($config['openvpn']['openvpn-client'])) {
											$interfaces['openvpn'] = 'OpenVPN';
										}

										foreach ($interfaces as $iface => $ifacename): ?>
										<option value="<?=$iface;?>" <?=($iface==$interfacefilter)?"selected=\"selected\"":"";?>><?=htmlspecialchars($ifacename);?></option>
										<?php endforeach; ?>
										</select>
										</div>
									</td>
									<td>
										<div align="center" style="vertical-align:top;"><?=gettext("Filter expression");?></div>
										<div align="center" style="vertical-align:top;"><input id="filtertext" name="filtertext" class="formfld search" style="vertical-align:top;" type="text" size="35" value="<?=$filtertext?>" /></div>
									</td>
									<td>
										<div align="center" style="vertical-align:top;"><?=gettext("Quantity");?></div>
										<div align="center" style="vertical-align:top;"><input id="filterlogentries_qty" name="filterlogentries_qty" class="" style="vertical-align:top;" type="text" size="6" value="<?= $filterlogentries_qty ?>" /></div>
									</td>
									<td>
										<div align="center" style="vertical-align:top;">&nbsp;</div>
										<div align="center" style="vertical-align:top;"><input id="filtersubmit" name="filtersubmit" type="submit" class="formbtn" style="vertical-align:top;" value="<?=gettext("Filter");?>" /></div>
									</td>
								</tr>
								<tr>
									<td></td>
									<td colspan="2">
										<?printf(gettext('Matches %1$s regular expression%2$s.'), '<a target="_blank" href="http://www.php.net/manual/en/book.pcre.php">', '</a>');?>&nbsp;&nbsp;
									</td>
								</tr>
								</table>
								</form>

								<div style="float: right; vertical-align:middle">
									<br />
									<?php if (!isset($config['syslog']['rawfilter']) && (isset($config['syslog']['filterdescriptions']) && $config['syslog']['filterdescriptions'] === "2")):?>
									<a href="#" onclick="toggleListDescriptions()">Show/hide rule descriptions</a>
									<?php endif;?>
								</div>

							</div>
				    </div>
					</div>
			     </section>
			     -->


			     <section class="col-xs-12">

					<div class="tab-content content-box col-xs-12">
				    <div class="container-fluid">

							<div class="table-responsive">
								<table class="table table-striped table-sort">


						<?php if (!isset($config['syslog']['rawfilter'])):
							$iflist = get_configured_interface_with_descr(false, true);
							if ($iflist[$interfacefilter])
								$interfacefilter = $iflist[$interfacefilter];
							if ($filtersubmit)
								$filterlog = conv_log_filter($filter_logfile, $nentries, $nentries + 100, $filterfieldsarray);
							else
								$filterlog = conv_log_filter($filter_logfile, $nentries, $nentries + 100, $filtertext, $interfacefilter);

						?>
									<tr>
									  <td colspan="<?=$config['syslog']['filterdescriptions']==="1"?7:6?>" class="listtopic">
										<?php if ( (!$filtertext) && (!$filterfieldsarray) )
											printf(gettext("Last %s firewall log entries."),count($filterlog));
										else
											echo count($filterlog). ' ' . gettext("matched log entries.");
									    printf(gettext("Max(%s)"),$nentries);?>
									  </td>
									</tr>
									<tr class="sortableHeaderRowIdentifier">
									  <td width="50" class="listhdrr"><?=gettext("Act");?></td>
									  <td class="listhdrr"><?=gettext("Time");?></td>
									  <td class="listhdrr"><?=gettext("If");?></td>
									  <?php if ($config['syslog']['filterdescriptions'] === "1"):?>
										<td width="10%" class="listhdrr"><?=gettext("Rule");?></td>
									  <?php endif;?>
									  <td class="listhdrr"><?=gettext("Source");?></td>
									  <td class="listhdrr"><?=gettext("Destination");?></td>
									  <td class="listhdrr"><?=gettext("Proto");?></td>
									</tr>
									<?php
									if ($config['syslog']['filterdescriptions'])
										buffer_rules_load();
									$rowIndex = 0;
									foreach ($filterlog as $filterent):
									$evenRowClass = $rowIndex % 2 ? " listMReven" : " listMRodd";
									$rowIndex++;?>
									<tr class="<?=$evenRowClass?>">
									  <td class="listMRlr nowrap" align="center" sorttable_customkey="<?=$filterent['act']?>">
									  <a onclick="javascript:getURL('diag_logs_filter.php?getrulenum=<?php echo "{$filterent['rulenum']},{$filterent['act']}"; ?>', outputrule);" title="<?php echo $filterent['act'] .'/';?>"><span class="glyphicon glyphicon-remove"></span></a></td>
									  <?php if ($filterent['count']) echo $filterent['count'];?></a></center></td>
									  <td class="listMRr nowrap"><?php echo htmlspecialchars($filterent['time']);?></td>
									  <td class="listMRr nowrap">
										<?php if ($filterent['direction'] == "out"): ?>
										<span class="glyphicon glyphicon-cloud-download" alt="Direction=OUT" title="Direction=OUT"></span>
										<?php endif; ?>
										<?php echo htmlspecialchars($filterent['interface']);?></td>
									  <?php
									  if ($config['syslog']['filterdescriptions'] === "1")
										echo("<td class=\"listMRr nowrap\">".find_rule_by_number_buffer($filterent['rulenum'],$filterent['act'])."</td>");

									  $int = strtolower($filterent['interface']);
									  $proto = strtolower($filterent['proto']);
									  if($filterent['version'] == '6') {
										$ipproto = "inet6";
										$filterent['srcip'] = "[{$filterent['srcip']}]";
										$filterent['dstip'] = "[{$filterent['dstip']}]";
									  } else {
									        $ipproto = "inet";
									  }

									  $srcstr = $filterent['srcip'] . get_port_with_service($filterent['srcport'], $proto);
									  $src_htmlclass = str_replace(array('.', ':'), '-', $filterent['srcip']);
									  $dststr = $filterent['dstip'] . get_port_with_service($filterent['dstport'], $proto);
									  $dst_htmlclass = str_replace(array('.', ':'), '-', $filterent['dstip']);
									  ?>
									  <td class="listMRr nowrap">
										<span onclick="javascript:resolve_with_ajax('<?php echo "{$filterent['srcip']}"; ?>');" title="<?=gettext("Click to resolve");?>" class="ICON-<?= $src_htmlclass; ?>" alt="Icon Reverse Resolve with DNS"><span class="btn btn-default btn-xs glyphicon glyphicon-info-sign"></span></span>
										<a class="btn btn-danger btn-xs" href="easyrule.php?<?php echo "action=block&amp;int={$int}&amp;src={$filterent['srcip']}&amp;ipproto={$ipproto}"; ?>" title="<?=gettext("Easy Rule: Add to Block List");?>" onclick="return confirm('<?=gettext("Do you really want to add this BLOCK rule?")."\n\n".gettext("Easy Rule is still experimental.")."\n".gettext("Continue at risk of your own peril.")."\n".gettext("Backups are also nice.")?>')">
										<span class="glyphicon glyphicon-remove" alt="Icon Easy Rule: Add to Block List"></span></a>
										<?php echo $srcstr . '<span class="RESOLVE-' . $src_htmlclass . '"></span>';?>
									  </td>
									  <td class="listMRr nowrap">
										<span onclick="javascript:resolve_with_ajax('<?php echo "{$filterent['dstip']}"; ?>');" title="<?=gettext("Click to resolve");?>" class="ICON-<?= $dst_htmlclass; ?>" alt="Icon Reverse Resolve with DNS"><span class="btn btn-default btn-xs  glyphicon glyphicon-info-sign"></span></span>
										<a class="btn btn-success btn-xs" href="easyrule.php?<?php echo "action=pass&amp;int={$int}&amp;proto={$proto}&amp;src={$filterent['srcip']}&amp;dst={$filterent['dstip']}&amp;dstport={$filterent['dstport']}&amp;ipproto={$ipproto}"; ?>" title="<?=gettext("Easy Rule: Pass this traffic");?>" onclick="return confirm('<?=gettext("Do you really want to add this PASS rule?")."\n\n".gettext("Easy Rule is still experimental.")."\n".gettext("Continue at risk of your own peril.")."\n".gettext("Backups are also nice.");?>')">
										<span  class="glyphicon glyphicon-play" alt="Icon Easy Rule: Pass this traffic"></span></a>
										<?php echo $dststr . '<span class="RESOLVE-' . $dst_htmlclass . '"></span>';?>
									  </td>
									  <?php
										if ($filterent['proto'] == "TCP")
											$filterent['proto'] .= ":{$filterent['tcpflags']}";
									  ?>
									  <td class="listMRr nowrap"><?php echo htmlspecialchars($filterent['proto']);?></td>
									</tr>
									<?php if (isset($config['syslog']['filterdescriptions']) && $config['syslog']['filterdescriptions'] === "2"):?>
									<tr class="<?=$evenRowClass?>">
									  <td colspan="2" class="listMRDescriptionL listMRlr" />
									  <td colspan="4" class="listMRDescriptionR listMRr nowrap"><?=find_rule_by_number_buffer($filterent['rulenum'],$filterent['act']);?></td>
									</tr>
									<?php endif;
									endforeach;
									buffer_rules_clear(); ?>
						<?php else: ?>
								  <tr>
									<td colspan="2" class="listtopic">
									  <?php printf(gettext("Last %s firewall log entries"),$nentries);?></td>
								  </tr>
								  <?php
									if($filtertext)
										dump_clog($filter_logfile, $nentries, true, array("$filtertext"));
									else
										dump_clog($filter_logfile, $nentries);
								  ?>
						<?php endif; ?>

								</table>
								</div>
							</td>
						  </tr>
						</table>


						<form id="clearform" name="clearform" action="diag_logs_filter.php" method="post" style="margin-top: 14px;">
							<input id="submit" name="clear" type="submit" class="btn btn-primary" value="<?=gettext("Clear log");?>" />
						</form>

						<p><span class="vexpl"><a href="http://en.wikipedia.org/wiki/Transmission_Control_Protocol">TCP Flags</a>: F - FIN, S - SYN, A or . - ACK, R - RST, P - PSH, U - URG, E - ECE, W - CWR</span></p>


						</div>
				    </div>
			</section>
			</div>
		</div>
	</section>


<!-- AJAXY STUFF -->
<script type="text/javascript">
//<![CDATA[
function resolve_with_ajax(ip_to_resolve) {
	var url = "/diag_logs_filter.php";

	jQuery.ajax(
		url,
		{
			type: 'post',
			dataType: 'json',
			data: {
				resolve: ip_to_resolve,
				},
			complete: resolve_ip_callback
		});

}

function resolve_ip_callback(transport) {
	var response = jQuery.parseJSON(transport.responseText);
	var resolve_class = htmlspecialchars(response.resolve_ip.replace(/[.:]/g, '-'));
	var resolve_text = '<small><br />' + htmlspecialchars(response.resolve_text) + '<\/small>';

	jQuery('span.RESOLVE-' + resolve_class).html(resolve_text);
	jQuery('img.ICON-' + resolve_class).removeAttr('title');
	jQuery('img.ICON-' + resolve_class).removeAttr('alt');
	jQuery('img.ICON-' + resolve_class).attr('src', '/themes/<?= $g['theme']; ?>/images/icons/icon_log_d.gif');
	jQuery('img.ICON-' + resolve_class).prop('onclick', null);
	  // jQuery cautions that "removeAttr('onclick')" fails in some versions of IE
}

// From http://stackoverflow.com/questions/5499078/fastest-method-to-escape-html-tags-as-html-entities
function htmlspecialchars(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
}
//]]>
</script>

<?php include("foot.inc"); ?>
