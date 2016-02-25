<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2013	Dagorlad
	Copyright (C) 2012	Jim Pingle
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
require_once("rrd.inc");
require_once("services.inc");
require_once("system.inc");
require_once("interfaces.inc");

if (!isset($config['ntpd']) || !is_array($config['ntpd']))
	$config['ntpd'] = array();

if (empty($config['ntpd']['interface'])) {
	$pconfig['interface'] = array();
} else {
	$pconfig['interface'] = explode(",", $config['ntpd']['interface']);
}

if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;

	if (isset($_POST['interface']) && is_array($_POST['interface']))
		$config['ntpd']['interface'] = implode(",", $_POST['interface']);
	elseif (isset($config['ntpd']['interface']))
		unset($config['ntpd']['interface']);

	unset($config['ntpd']['prefer']);
	unset($config['ntpd']['noselect']);
	$timeservers = '';
	for ($i = 0; $i < 10; $i++) {
		$tserver = trim($_POST["server{$i}"]);
		if (!empty($tserver)) {
			$timeservers .= "{$tserver} ";
			if (!empty($_POST["servprefer{$i}"])) $config['ntpd']['prefer'] .= "{$tserver} ";
			if (!empty($_POST["servselect{$i}"])) $config['ntpd']['noselect'].= "{$tserver} ";
		}
	}
	$config['system']['timeservers'] = trim($timeservers);
	if (empty($config['system']['timeservers'])) {
	    unset($config['system']['timeservers']);
        }

	if (!empty($_POST['ntporphan']) && ($_POST['ntporphan'] < 17) && ($_POST['ntporphan'] != '12'))
		$config['ntpd']['orphan'] = $_POST['ntporphan'];
	elseif (isset($config['ntpd']['orphan']))
		unset($config['ntpd']['orphan']);

	if (!empty($_POST['logpeer']))
		$config['ntpd']['logpeer'] = $_POST['logpeer'];
	elseif (isset($config['ntpd']['logpeer']))
		unset($config['ntpd']['logpeer']);

	if (!empty($_POST['logsys']))
		$config['ntpd']['logsys'] = $_POST['logsys'];
	elseif (isset($config['ntpd']['logsys']))
		unset($config['ntpd']['logsys']);

	if (!empty($_POST['clockstats']))
		$config['ntpd']['clockstats'] = $_POST['clockstats'];
	elseif (isset($config['ntpd']['clockstats']))
		unset($config['ntpd']['clockstats']);

	if (!empty($_POST['loopstats']))
		$config['ntpd']['loopstats'] = $_POST['loopstats'];
	elseif (isset($config['ntpd']['loopstats']))
		unset($config['ntpd']['loopstats']);

	if (!empty($_POST['peerstats']))
		$config['ntpd']['peerstats'] = $_POST['peerstats'];
	elseif (isset($config['ntpd']['peerstats']))
		unset($config['ntpd']['peerstats']);

	if (empty($_POST['kod']))
		$config['ntpd']['kod'] = 'on';
	elseif (isset($config['ntpd']['kod']))
		unset($config['ntpd']['kod']);

	if (empty($_POST['nomodify']))
		$config['ntpd']['nomodify'] = 'on';
	elseif (isset($config['ntpd']['nomodify']))
		unset($config['ntpd']['nomodify']);

	if (!empty($_POST['noquery']))
		$config['ntpd']['noquery'] = $_POST['noquery'];
	elseif (isset($config['ntpd']['noquery']))
		unset($config['ntpd']['noquery']);

	if (!empty($_POST['noserve']))
		$config['ntpd']['noserve'] = $_POST['noserve'];
	elseif (isset($config['ntpd']['noserve']))
		unset($config['ntpd']['noserve']);

	if (empty($_POST['nopeer']))
		$config['ntpd']['nopeer'] = 'on';
	elseif (isset($config['ntpd']['nopeer']))
		unset($config['ntpd']['nopeer']);

	if (empty($_POST['notrap']))
		$config['ntpd']['notrap'] = 'on';
	elseif (isset($config['ntpd']['notrap']))
		unset($config['ntpd']['notrap']);

	if ((empty($_POST['statsgraph'])) != (isset($config['ntpd']['statsgraph'])));
		enable_rrd_graphing();
	if (!empty($_POST['statsgraph']))
		$config['ntpd']['statsgraph'] = $_POST['statsgraph'];
	elseif (isset($config['ntpd']['statsgraph']))
		unset($config['ntpd']['statsgraph']);

	if (!empty($_POST['leaptxt']))
		$config['ntpd']['leapsec'] = base64_encode($_POST['leaptxt']);
	elseif (isset($config['ntpd']['leapsec']))
		unset($config['ntpd']['leapsec']);

	if (is_uploaded_file($_FILES['leapfile']['tmp_name']))
		$config['ntpd']['leapsec'] = base64_encode(file_get_contents($_FILES['leapfile']['tmp_name']));

	write_config("Updated NTP Server Settings");

	$retval = 0;
	$retval = system_ntp_configure();
	$savemsg = get_std_save_message();

}

$pconfig = &$config['ntpd'];
if (empty($pconfig['interface'])) {
	$pconfig['interface'] = array();
} else {
	$pconfig['interface'] = explode(",", $pconfig['interface']);
}

$service_hook = 'ntpd';

include("head.inc");

?>
<body>

<script type="text/javascript">
//<![CDATA[
	//Generic show an advanced option function
	function show_advanced(showboxID, configvalueID) {
		document.getElementById(showboxID).innerHTML='';
		aodiv = document.getElementById(configvalueID);
		aodiv.style.display = "block";
	}

	//Insure only one of two mutually exclusive options are checked
	function CheckOffOther(clicked, checkOff) {
		if (document.getElementById(clicked).checked) {
			document.getElementById(checkOff).checked=false;
		}
	}

	//Show another time server line, limited to 10 servers
	function NewTimeServer(add) {
		//If the last line has a value
		var CheckServer = 'server' + (add - 1);
		var LastId = document.getElementById(CheckServer);
		if (document.getElementById(CheckServer).value != '') {
			if (add < 10) {
				var TimeServerID = 'timeserver' + add;
				document.getElementById(TimeServerID).style.display = 'block';

				//then revise the add another server line
				if (add < 9) {
					var next = add + 1;
					var newdiv = '<a class="btn btn-default btn-xs" title="<?= gettext("Add another Time server");?>" onclick="NewTimeServer(' + next + ')" alt="add" ><span class="glyphicon glyphicon-plus"></span></a>\n';
					document.getElementById('addserver').innerHTML=newdiv;
				}else{
					document.getElementById('addserver').style.display = 'none';
				}
			}
		}
	}
//]]>
</script>

<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
				<?php if (isset($savemsg)) print_info_box($savemsg); ?>

			    <section class="col-xs-12">

					<div class="tab-content content-box col-xs-12">

					   <form action="services_ntpd.php" method="post" name="iform" id="iform" enctype="multipart/form-data" accept-charset="utf-8">

								<div class="table-responsive">
									<table class="table table-striped">

										<tr>
											<td colspan="2" valign="top" class="listtopic"><?=gettext("NTP Server Configuration"); ?></td>
										</tr>
										<tr>
											<td width="22%" valign="top" class="vncellreq"><?=gettext('Interface(s)') ?></td>
											<td width="78%" class="vtable">
							<?php
								$interfaces = get_configured_interface_with_descr();
								$carplist = get_configured_carp_interface_list();
								foreach ($carplist as $cif => $carpip)
									$interfaces[$cif] = $carpip." (".get_vip_descr($carpip).")";
								$aliaslist = get_configured_ip_aliases_list();
								foreach ($aliaslist as $aliasip => $aliasif)
									$interfaces[$aliasip] = $aliasip." (".get_vip_descr($aliasip).")";
								$size = (count($interfaces) < 10) ? count($interfaces) : 10;
							?>
										<select id="interface" name="interface[]" multiple="multiple" class="formselect" size="<?= $size; ?>">
							<?php
								foreach ($interfaces as $iface => $ifacename) {
									if (!is_ipaddr(get_interface_ip($iface)) && !is_ipaddr($iface))
										continue;
									echo "<option value='{$iface}'";
									if (is_array($pconfig['interface']))
										if (in_array($iface, $pconfig['interface'])) echo " selected=\"selected\"";
									echo ">" . htmlspecialchars($ifacename) . "</option>\n";
								} ?>
												</select>
												<br />
												<br /><?= gettext("Interfaces without an IP address will not be shown."); ?>
												<br />
												<br /><?= gettext("Selecting no interfaces will listen on all interfaces with a wildcard."); ?>
												<br /><?= gettext("Selecting all interfaces will explicitly listen on only the interfaces/IPs specified."); ?>
											</td>
										</tr>
										<tr>
											<td width="22%" valign="top" class="vncellreq"><?=gettext('Time servers') ?></td>
											<td width="78%" class="vtable">
												<?php
												$timeservers = explode( ' ', $config['system']['timeservers']);
												for ($i = $j = 0; $i < 10; $i++){
													echo "<div id=\"timeserver{$i}\"";
													if ((isset($timeservers[$i])) || ($i < 3)) {
														$j++;
													}else{
														echo " style=\"display:none\"";
													}
													echo ">\n";
													if (!isset($timeservers[$i])) {
														$timeserverVal = null;
													} else {
														$timeserverVal =$timeservers[$i];
													}
													echo "<input name=\"server{$i}\" class=\"formfld unknown\" id=\"server{$i}\" size=\"30\" value=\"{$timeserverVal}\" type=\"text\" />&emsp;";
													echo "\n<input name=\"servprefer{$i}\" class=\"formcheckbox\" id=\"servprefer{$i}\" onclick=\"CheckOffOther('servprefer{$i}', 'servselect{$i}')\" type=\"checkbox\"";
													if (!empty($config['ntpd']['prefer']) && !empty($timeserverVal) && substr_count($config['ntpd']['prefer'], $timeserverVal)) echo " checked=\"checked\"";
													echo " />&nbsp;" . gettext('prefer this server') . "&emsp;";
													echo "\n<input name=\"servselect{$i}\" class=\"formcheckbox\" id=\"servselect{$i}\" onclick=\"CheckOffOther('servselect{$i}', 'servprefer{$i}')\" type=\"checkbox\"";
													if (!empty($config['ntpd']['noselect']) && !empty($timeserverVal) && substr_count($config['ntpd']['noselect'], $timeserverVal)) echo " checked=\"checked\"";
													echo " />&nbsp;" . gettext('do not use this server') . "\n<br />\n</div>\n";
												}
												?>
												<div id="addserver">
												<a class="btn btn-default btn-xs" title="<?= gettext("Add another Time server");?>" onclick="NewTimeServer(<?= $j;?>)" alt="add" ><span class="glyphicon glyphicon-plus"></span> </a>
												</div>
												<br />
												<?= gettext('For best results three to five servers should be configured here.'); ?>
												<br />
												<?= sprintf(gettext('The %sprefer%s option indicates that NTP should favor the use of this server more than all others.'),'<i>','</i>') ?>
												<br />
												<?= sprintf(gettext('The %snoselect%s option indicates that NTP should not use this server for time, but stats for this server will be collected and displayed.'),'<i>','</i>') ?>
											</td>
										</tr>
										<tr>
											<td width="22%" valign="top" class="vncellreq"><?=gettext('Orphan mode') ?></td>
											<td width="78%" class="vtable">
												<input name="ntporphan" type="text" class="formfld unknown" id="ntporphan" min="1" max="16" size="20" value="<?=htmlspecialchars(isset($pconfig['orphan']) ? $pconfig['orphan']:"");?>" /><?= gettext("(0-15)");?><br />
												<?= gettext("Orphan mode allows the system clock to be used when no other clocks are available. The number here specifies the stratum reported during orphan mode and should normally be set to a number high enough to insure that any other servers available to clients are preferred over this server. (default: 12)."); ?>
											</td>
										</tr>
										<tr>
											<td width="22%" valign="top" class="vncellreq"><?=gettext('NTP graphs') ?></td>
											<td width="78%" class="vtable">
												<input name="statsgraph" type="checkbox" class="formcheckbox" id="statsgraph" <?php if(!empty($pconfig['statsgraph'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Enable rrd graphs of NTP statistics (default: disabled)."); ?>
											</td>
										</tr>
										<tr>
											<td width="22%" valign="top" class="vncellreq"><?=gettext('Syslog logging') ?></td>
											<td width="78%" class="vtable">
												<?= gettext("These options enable additional messages from NTP to be written to the System Log");?> (<a href="diag_logs_ntpd.php"><?= gettext("Status > System Logs > NTP"); ?></a>).
												<br /><br />
												<input name="logpeer" type="checkbox" class="formcheckbox" id="logpeer"<?php if(!empty($pconfig['logpeer'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Enable logging of peer messages (default: disabled)."); ?>
												<br />
												<input name="logsys" type="checkbox" class="formcheckbox" id="logsys"<?php if(!empty($pconfig['logsys'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Enable logging of system messages (default: disabled)."); ?>
											</td>
										</tr>
										<tr>
											<td width="22%" valign="top" class="vncellreq"><?=gettext('Statistics logging') ?></td>
											<td width="78%" class="vtable">
												<div id="showstatisticsbox">
												<input class="btn btn-default btn-xs" type="button" onclick="show_advanced('showstatisticsbox', 'showstatistics')" value="<?=gettext("Advanced");?>" /> - <?=gettext("Show statistics logging options");?>
												</div>
												<div id="showstatistics" style="display:none">
												<strong><?= gettext("Warning: ")?></strong><?= gettext("these options will create persistant daily log files in /var/log/ntp."); ?>
												<br /><br />
												<input name="clockstats" type="checkbox" class="formcheckbox" id="clockstats"<?php if(!empty($pconfig['clockstats'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Enable logging of reference clock statistics (default: disabled)."); ?>
												<br />
												<input name="loopstats" type="checkbox" class="formcheckbox" id="loopstats"<?php if(!empty($pconfig['loopstats'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Enable logging of clock discipline statistics (default: disabled)."); ?>
												<br />
												<input name="peerstats" type="checkbox" class="formcheckbox" id="peerstats"<?php if(!empty($pconfig['peerstats'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Enable logging of NTP peer statistics (default: disabled)."); ?>
												</div>
											</td>
										</tr>
										<tr>
											<td width="22%" valign="top" class="vncellreq"><?=gettext('Access restrictions') ?></td>
											<td width="78%" class="vtable">
												<div id="showrestrictbox">
												<input type="button" class="btn btn-default btn-xs" onclick="show_advanced('showrestrictbox', 'showrestrict')" value="<?=gettext("Advanced");?>" /> - <?=gettext("Show access restriction options");?>
												</div>
												<div id="showrestrict" style="display:none">
												<?= gettext("these options control access to NTP from the WAN."); ?>
												<br /><br />
												<input name="kod" type="checkbox" class="formcheckbox" id="kod"<?php if(empty($pconfig['kod'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Enable Kiss-o'-death packets (default: enabled)."); ?>
												<br />
												<input name="nomodify" type="checkbox" class="formcheckbox" id="nomodify"<?php if(empty($pconfig['nomodify'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Deny state modifications (i.e. run time configuration) by ntpq and ntpdc (default: enabled)."); ?>
												<br />
												<input name="noquery" type="checkbox" class="formcheckbox" id="noquery"<?php if(!empty($pconfig['noquery'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Disable ntpq and ntpdc queries (default: disabled)."); ?>
												<br />
												<input name="noserve" type="checkbox" class="formcheckbox" id="noserve"<?php if(!empty($pconfig['noserve'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Disable all except ntpq and ntpdc queries (default: disabled)."); ?>
												<br />
												<input name="nopeer" type="checkbox" class="formcheckbox" id="nopeer"<?php if(empty($pconfig['nopeer'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Deny packets that attempt a peer association (default: enabled)."); ?>
												<br />
												<input name="notrap" type="checkbox" class="formcheckbox" id="notrap"<?php if(empty($pconfig['notrap'])) echo " checked=\"checked\""; ?> />
												<?= gettext("Deny mode 6 control message trap service (default: enabled)."); ?>
												</div>
											</td>
										</tr>
										<tr>
											<td width="22%" valign="top" class="vncellreq"><?=gettext('Leap seconds') ?></td>
											<td width="78%" class="vtable">
												<div id="showleapsecbox">
												<input type="button" class="btn btn-default btn-xs" onclick="show_advanced('showleapsecbox', 'showleapsec')" value="<?=gettext("Advanced");?>" /> - <?=gettext("Show Leap second configuration");?>
												</div>
												<div id="showleapsec" style="display:none">
												<?= gettext("A leap second file allows NTP to advertize an upcoming leap second addition or subtraction.");?>
												<?= gettext("Normally this is only useful if this server is a stratum 1 time server.");?>
												<br /><br />
												<?= gettext("Enter Leap second configuration as text:");?><br />
												<textarea name="leaptxt" class="formpre" id="leaptxt" cols="65" rows="7"><?php $text = base64_decode(chunk_split(isset($pconfig['leapsec'])?$pconfig['leapsec']:"")); echo $text;?></textarea><br />
												<strong><?= gettext("Or");?></strong>, <?= gettext("select a file to upload:");?>
												<input type="file" name="leapfile" class="formfld file" id="leapfile" />
												</div>
											</td>
										</tr>
										<tr>
											<td width="22%" valign="top">&nbsp;</td>
											<td width="78%">
											<input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Save");?>" />
											</td>
										</tr>
									</table>
								</div>
					   </form>

					</div>
			    </section>
			</div>
		</div>
	</section>
	<?php include("foot.inc"); ?>
