<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2005-2007 Scott Ullrich
	Copyright (C) 2008 Shrew Soft Inc
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
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
require_once("filter.inc");
require_once("services.inc");
require_once("system.inc");

function default_table_entries_size()
{
        $current = `pfctl -sm | grep table-entries | awk '{print $4};'`;

        return $current;
}


$pconfig['disablefilter'] = $config['system']['disablefilter'];
$pconfig['rfc959workaround'] = $config['system']['rfc959workaround'];
$pconfig['scrubnodf'] = $config['system']['scrubnodf'];
$pconfig['scrubrnid'] = $config['system']['scrubrnid'];
$pconfig['tcpidletimeout'] = $config['filter']['tcpidletimeout'];
$pconfig['optimization'] = $config['filter']['optimization'];
$pconfig['adaptivestart'] = $config['system']['adaptivestart'];
$pconfig['adaptiveend'] = $config['system']['adaptiveend'];
$pconfig['maximumstates'] = $config['system']['maximumstates'];
$pconfig['aliasesresolveinterval'] = $config['system']['aliasesresolveinterval'];
$old_aliasesresolveinterval = $config['system']['aliasesresolveinterval'];
$pconfig['checkaliasesurlcert'] = isset($config['system']['checkaliasesurlcert']);
$pconfig['maximumtableentries'] = $config['system']['maximumtableentries'];
$pconfig['disablereplyto'] = isset($config['system']['disablereplyto']);
$pconfig['disablenegate'] = isset($config['system']['disablenegate']);
$pconfig['bogonsinterval'] = $config['system']['bogons']['interval'];
$pconfig['disablenatreflection'] = $config['system']['disablenatreflection'];
$pconfig['enablebinatreflection'] = $config['system']['enablebinatreflection'];
$pconfig['reflectiontimeout'] = $config['system']['reflectiontimeout'];
$pconfig['bypassstaticroutes'] = isset($config['filter']['bypassstaticroutes']);
$pconfig['disablescrub'] = isset($config['system']['disablescrub']);
$pconfig['ftp-proxy-client'] = isset($config['system']['ftp-proxy']['client']);
$pconfig['disablevpnrules'] = isset($config['system']['disablevpnrules']);

if ($_POST) {
    unset($input_errors);
    $pconfig = $_POST;

    /* input validation */
    if ((empty($_POST['adaptivestart']) && !empty($_POST['adaptiveend'])) || (!empty($_POST['adaptivestart']) && empty($_POST['adaptiveend']))) {
        $input_errors[] = gettext("The Firewall Adaptive values must be set together.");
    }
    if (!empty($_POST['adaptivestart']) && !is_numericint($_POST['adaptivestart'])) {
        $input_errors[] = gettext("The Firewall Adaptive Start value must be an integer.");
    }
    if (!empty($_POST['adaptiveend']) && !is_numericint($_POST['adaptiveend'])) {
        $input_errors[] = gettext("The Firewall Adaptive End value must be an integer.");
    }
    if ($_POST['maximumstates'] && !is_numericint($_POST['maximumstates'])) {
        $input_errors[] = gettext("The Firewall Maximum States value must be an integer.");
    }
    if ($_POST['aliasesresolveinterval'] && !is_numericint($_POST['aliasesresolveinterval'])) {
        $input_errors[] = gettext("The Aliases Hostname Resolve Interval value must be an integer.");
    }
    if ($_POST['maximumtableentries'] && !is_numericint($_POST['maximumtableentries'])) {
        $input_errors[] = gettext("The Firewall Maximum Table Entries value must be an integer.");
    }
    if ($_POST['tcpidletimeout'] && !is_numericint($_POST['tcpidletimeout'])) {
        $input_errors[] = gettext("The TCP idle timeout must be an integer.");
    }
    if ($_POST['reflectiontimeout'] && !is_numericint($_POST['reflectiontimeout'])) {
        $input_errors[] = gettext("The Reflection timeout must be an integer.");
    }

    ob_flush();
    flush();

    if (!$input_errors) {
        if ($_POST['disablefilter'] == "yes") {
            $config['system']['disablefilter'] = "enabled";
        } else {
            unset($config['system']['disablefilter']);
        }

        if ($_POST['disablevpnrules'] == "yes") {
            $config['system']['disablevpnrules'] = true;
        } else {
            unset($config['system']['disablevpnrules']);
        }
        if ($_POST['rfc959workaround'] == "yes") {
            $config['system']['rfc959workaround'] = "enabled";
        } else {
            unset($config['system']['rfc959workaround']);
        }

        if ($_POST['scrubnodf'] == "yes") {
            $config['system']['scrubnodf'] = "enabled";
        } else {
            unset($config['system']['scrubnodf']);
        }

        if ($_POST['scrubrnid'] == "yes") {
            $config['system']['scrubrnid'] = "enabled";
        } else {
            unset($config['system']['scrubrnid']);
        }

        if (!empty($_POST['adaptiveend'])) {
            $config['system']['adaptiveend'] = $_POST['adaptiveend'];
        } else {
            unset($config['system']['adaptiveend']);
        }
        if (!empty($_POST['adaptivestart'])) {
            $config['system']['adaptivestart'] = $_POST['adaptivestart'];
        } else {
            unset($config['system']['adaptivestart']);
        }

        if ($_POST['checkaliasesurlcert'] == "yes") {
            $config['system']['checkaliasesurlcert'] = true;
        } else {
            unset($config['system']['checkaliasesurlcert']);
        }

        $config['system']['optimization'] = $_POST['optimization'];
        $config['system']['maximumstates'] = $_POST['maximumstates'];
        $config['system']['aliasesresolveinterval'] = $_POST['aliasesresolveinterval'];
        $config['system']['maximumtableentries'] = $_POST['maximumtableentries'];

        if ($_POST['natreflection'] == "proxy") {
            unset($config['system']['disablenatreflection']);
            unset($config['system']['enablenatreflectionpurenat']);
        } elseif ($_POST['natreflection'] == "purenat") {
            unset($config['system']['disablenatreflection']);
            $config['system']['enablenatreflectionpurenat'] = "yes";
        } else {
            $config['system']['disablenatreflection'] = "yes";
            unset($config['system']['enablenatreflectionpurenat']);
        }

        if ($_POST['enablebinatreflection'] == "yes") {
            $config['system']['enablebinatreflection'] = "yes";
        } else {
            unset($config['system']['enablebinatreflection']);
        }

        if ($_POST['disablereplyto'] == "yes") {
            $config['system']['disablereplyto'] = $_POST['disablereplyto'];
        } else {
            unset($config['system']['disablereplyto']);
        }

        if ($_POST['disablenegate'] == "yes") {
            $config['system']['disablenegate'] = $_POST['disablenegate'];
        } else {
            unset($config['system']['disablenegate']);
        }

        if ($_POST['enablenatreflectionhelper'] == "yes") {
            $config['system']['enablenatreflectionhelper'] = "yes";
        } else {
            unset($config['system']['enablenatreflectionhelper']);
        }

        $config['system']['reflectiontimeout'] = $_POST['reflectiontimeout'];

        if ($_POST['bypassstaticroutes'] == "yes") {
            $config['filter']['bypassstaticroutes'] = $_POST['bypassstaticroutes'];
        } elseif (isset($config['filter']['bypassstaticroutes']))
            unset($config['filter']['bypassstaticroutes']);

        if ($_POST['disablescrub'] == "yes") {
            $config['system']['disablescrub'] = $_POST['disablescrub'];
        } else {
            unset($config['system']['disablescrub']);
        }

        if ($_POST['ftp-proxy-client'] == 'yes') {
            $config['system']['ftp-proxy']['client'] = true;
        } else {
            unset($config['system']['ftp-proxy']['client']);
        }

        if ($_POST['bogonsinterval'] != $config['system']['bogons']['interval']) {
            switch ($_POST['bogonsinterval']) {
                case 'daily':
                    install_cron_job("/usr/local/etc/rc.update_bogons", true, "1", "3", "*", "*", "*");
                    break;
                case 'weekly':
                    install_cron_job("/usr/local/etc/rc.update_bogons", true, "1", "3", "*", "*", "0");
                    break;
                case 'monthly':
                    // fall through
                default:
                    install_cron_job("/usr/local/etc/rc.update_bogons", true, "1", "3", "1", "*", "*");
            }
            $config['system']['bogons']['interval'] = $_POST['bogonsinterval'];
        }

        write_config();

        // Kill filterdns when value changes, filter_configure() will restart it
        if ($old_aliasesresolveinterval != $config['system']['aliasesresolveinterval']) {
            killbypid('/var/run/filterdns.pid');
        }

        $retval = 0;
        $retval = filter_configure();
        if (stristr($retval, "error") <> true) {
            $savemsg = get_std_save_message();
        } else {
            $savemsg = $retval;
        }
    }
}

$pgtitle = array(gettext("System"),gettext("Settings"),gettext("Firewall and NAT"));
include("head.inc");

?>

<body>

    <?php include("fbegin.inc"); ?>

    <script type="text/javascript">
    //<![CDATA[
	var descs=new Array(5);
	descs[0]="<?=gettext("as the name says, it is the normal optimization algorithm");?>";
	descs[1]="<?=gettext("used for high latency links, such as satellite links.  Expires idle connections later than default");?>";
	descs[2]="<?=gettext("expires idle connections quicker. More efficient use of CPU and memory but can drop legitimate idle connections");?>";
	descs[3]="<?=gettext("tries to avoid dropping any legitimate idle connections at the expense of increased memory usage and CPU utilization.");?>";

	function update_description(itemnum) {
	document.iform.info.value=descs[itemnum];

	}
	//]]>
    </script>

<!-- row -->
<section class="page-content-main">
	<div class="container-fluid">

        <div class="row">
            <?php
            if (isset($input_errors) && count($input_errors) > 0) {
                print_input_errors($input_errors);
            }
            if (isset($savemsg)) {
                print_info_box($savemsg);
            }
            ?>
            <section class="col-xs-12">
                <? include('system_advanced_tabs.inc'); ?>
	                <div class="content-box tab-content">

					<form action="system_advanced_firewall.php" method="post" name="iform" id="iform">

						<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="main area" class="table table-striped ">
							<thead>
								<tr>
									<th colspan="2" valign="top" class="listtopic"><?=gettext("Firewall Advanced");?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("IP Do-Not-Fragment compatibility");?></td>
									<td width="78%" class="vtable">
										<input name="scrubnodf" type="checkbox" id="scrubnodf" value="yes" <?php if (isset($config['system']['scrubnodf'])) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Clear invalid DF bits instead of dropping the packets");?></strong><br />
										<?=gettext("This allows for communications with hosts that generate fragmented " .
                                        "packets with the don't fragment (DF) bit set. Linux NFS is known to " .
                                        "do this. This will cause the filter to not drop such packets but " .
                                        "instead clear the don't fragment bit.");?>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("IP Random id generation");?></td>
									<td width="78%" class="vtable">
										<input name="scrubrnid" type="checkbox" id="scrubrnid" value="yes" <?php if (isset($config['system']['scrubrnid'])) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Insert a stronger id into IP header of packets passing through the filter.");?></strong><br />
										<?=gettext("Replaces the IP identification field of packets with random values to " .
                                        "compensate for operating systems that use predictable values. " .
                                        "This option only applies to packets that are not fragmented after the " .
                                        "optional packet reassembly.");?>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Firewall Optimization Options");?></td>
									<td width="78%" class="vtable">
										<select onchange="update_description(this.selectedIndex);" name="optimization" id="optimization" class="selectpicker" data-style="btn-default">
											<option value="normal"<?php if ($config['system']['optimization']=="normal") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("normal");?></option>
											<option value="high-latency"<?php if ($config['system']['optimization']=="high-latency") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("high-latency");?></option>
											<option value="aggressive"<?php if ($config['system']['optimization']=="aggressive") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("aggressive");?></option>
											<option value="conservative"<?php if ($config['system']['optimization']=="conservative") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("conservative");?></option>
										</select>
										<br />
										<textarea readonly="readonly" cols="60" rows="2" id="info" name="info"></textarea>
										<script type="text/javascript">
										//<![CDATA[
											update_description(document.iform.optimization.selectedIndex);
										//]]>
										</script>
										<br />
										<?=gettext("Select the type of state table optimization to use");?>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Disable Firewall");?></td>
									<td width="78%" class="vtable">
										<input name="disablefilter" type="checkbox" id="disablefilter" value="yes" <?php if (isset($config['system']['disablefilter'])) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Disable all packet filtering.");?></strong>
										<br />
										<span class="vexpl"><?php printf(gettext("Warning: This converts %s into a routing only platform!"), $g['product_name']);?><br />
											<?=gettext("Warning: This will also turn off NAT!");?><br />
											<?=sprintf(
												gettext('If you only want to disable NAT, and not firewall rules, visit the %sOutbound NAT%s page.'),
												'<a href="/firewall_nat_out.php">', '</a>'
											)?>
										</span>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Disable Firewall Scrub");?></td>
									<td width="78%" class="vtable">
										<input name="disablescrub" type="checkbox" id="disablescrub" value="yes" <?php if (isset($config['system']['disablescrub'])) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Disables the PF scrubbing option which can sometimes interfere with NFS and PPTP traffic.");?></strong>
										<br />
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Firewall Adaptive Timeouts");?></td>
									<td width="78%" class="vtable">
										<strong><?=gettext("Timeouts for states can be scaled adaptively as the number of state table entries grows.");?></strong>
										<br />
										<input name="adaptivestart" type="text" id="adaptivestart" value="<?php echo $pconfig['adaptivestart']; ?>" />
										<br /><?=gettext("When the number of state entries exceeds this value, adaptive scaling begins.  All timeout values are scaled linearly with factor (adaptive.end - number of states) / (adaptive.end - adaptive.start).");?>

										<br />
										<input name="adaptiveend" type="text" id="adaptiveend" value="<?php echo $pconfig['adaptiveend']; ?>" />
										<br /><?=gettext("When reaching this number of state entries, all timeout values become zero, effectively purging all state entries immediately.  This value is used to define the scale factor, it should not actually be reached (set a lower state limit, see below).");?>
										<br />
										<span class="vexpl"><?=gettext("Note:  Leave this blank for the default(0).");?></span>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Firewall Maximum States");?></td>
									<td width="78%" class="vtable">
										<input name="maximumstates" type="text" id="maximumstates" value="<?php echo $pconfig['maximumstates']; ?>" />
										<br />
										<strong><?=gettext("Maximum number of connections to hold in the firewall state table.");?></strong>
										<br />
										<span class="vexpl"><?=gettext("Note:  Leave this blank for the default.  On your system the default size is:");?> <?= default_state_size() ?></span>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Firewall Maximum Table Entries");?></td>
									<td width="78%" class="vtable">
										<input name="maximumtableentries" type="text" id="maximumtableentries" value="<?php echo $pconfig['maximumtableentries']; ?>" />
										<br />
										<strong><?=gettext("Maximum number of table entries for systems such as aliases, sshlockout, snort, etc, combined.");?></strong>
										<br />
										<span class="vexpl">
											<?=gettext("Note:  Leave this blank for the default.");?>
											<?php if (empty($pconfig['maximumtableentries'])) :
?>
												<?= gettext("On your system the default size is:");
?> <?= default_table_entries_size(); ?>
											<?php
endif; ?>
										</span>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Static route filtering");?></td>
									<td width="78%" class="vtable">
										<input name="bypassstaticroutes" type="checkbox" id="bypassstaticroutes" value="yes" <?php if ($pconfig['bypassstaticroutes']) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Bypass firewall rules for traffic on the same interface");?></strong>
										<br />
										<?=gettext("This option only applies if you have defined one or more static routes. If it is enabled, traffic that enters and " .
                                        "leaves through the same interface will not be checked by the firewall. This may be desirable in some situations where " .
                                        "multiple subnets are connected to the same interface.");?>
										<br />
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell">Disable Auto-added VPN rules</td>
									<td width="78%" class="vtable">
										<input name="disablevpnrules" type="checkbox" id="disablevpnrules" value="yes" <?php if (isset($config['system']['disablevpnrules'])) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Disable all auto-added VPN rules.");?></strong>
										<br />
										<span class="vexpl"><?=gettext("Note: This disables automatically added rules for IPsec, PPTP.");?>
										</span>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell">Disable reply-to</td>
									<td width="78%" class="vtable">
										<input name="disablereplyto" type="checkbox" id="disablereplyto" value="yes" <?php if ($pconfig['disablereplyto']) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Disable reply-to on WAN rules");?></strong>
										<br />
										<?=gettext("With Multi-WAN you generally want to ensure traffic leaves the same interface it arrives on, hence reply-to is added automatically by default. " .
                                        "When using bridging, you must disable this behavior if the WAN gateway IP is different from the gateway IP of the hosts behind the bridged interface.");?>
										<br />
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell">Disable Negate rules</td>
									<td width="78%" class="vtable">
										<input name="disablenegate" type="checkbox" id="disablenegate" value="yes" <?php if ($pconfig['disablenegate']) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Disable Negate rule on policy routing rules");?></strong>
										<br />
										<?=gettext("With Multi-WAN you generally want to ensure traffic reaches directly connected networks and VPN networks when using policy routing. You can disable this for special purposes but it requires manually creating rules for these networks");?>
										<br />
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Aliases Hostnames Resolve Interval");?></td>
									<td width="78%" class="vtable">
										<input name="aliasesresolveinterval" type="text" id="aliasesresolveinterval" value="<?php echo $pconfig['aliasesresolveinterval']; ?>" />
										<br />
										<strong><?=gettext("Interval, in seconds, that will be used to resolve hostnames configured on aliases.");?></strong>
										<br />
										<span class="vexpl"><?=gettext("Note:  Leave this blank for the default (300s).");?></span>
									</td>
								</tr>
								<tr>
								    <td width="22%" valign="top" class="vncell"><?=gettext("Check certificate of aliases URLs");?></td>
									<td width="78%" class="vtable">
										<input name="checkaliasesurlcert" type="checkbox" id="checkaliasesurlcert" value="yes" <?php if ($pconfig['checkaliasesurlcert']) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Verify HTTPS certificates when downloading alias URLs");?></strong>
										<br />
										<?=gettext("Make sure the certificate is valid for all HTTPS addresses on aliases. If it's not valid or is revoked, do not download it.");?>
										<br />
									</td>
								</tr>

								<tr>
									<th colspan="2" valign="top" class="listtopic"><?=gettext("Bogon Networks");?></th>
								</tr>

								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Update Frequency");?></td>
									<td width="78%" class="vtable">
										<select name="bogonsinterval" class="formselect selectpicker" data-style="btn-default">
										<option value="monthly" <?php if (empty($pconfig['bogonsinterval']) || $pconfig['bogonsinterval'] == 'monthly') {
                                            echo "selected=\"selected\"";

} ?>><?=gettext("Monthly"); ?></option>
										<option value="weekly" <?php if ($pconfig['bogonsinterval'] == 'weekly') {
                                            echo "selected=\"selected\"";

} ?>><?=gettext("Weekly"); ?></option>
										<option value="daily" <?php if ($pconfig['bogonsinterval'] == 'daily') {
                                            echo "selected=\"selected\"";

} ?>><?=gettext("Daily"); ?></option>
										</select>
										<br />
										<?=gettext("The frequency of updating the lists of IP addresses that are reserved (but not RFC 1918) or not yet assigned by IANA.");?>
									</td>
								</tr>


								<?php if (count($config['interfaces']) > 1) :
?>

									<tr>
										<th colspan="2" valign="top" class="listtopic"><?=gettext("Network Address Translation");?></th>
									</tr>

									<tr>
										<td width="22%" valign="top" class="vncell"><?=gettext("NAT Reflection mode for port forwards");?></td>
										<td width="78%" class="vtable">
											<select name="natreflection" class="formselect selectpicker" data-style="btn-default">
											<option value="disable" <?php if (isset($config['system']['disablenatreflection'])) {
                                                echo "selected=\"selected\"";

} ?>><?=gettext("Disable"); ?></option>
											<option value="proxy" <?php if (!isset($config['system']['disablenatreflection']) && !isset($config['system']['enablenatreflectionpurenat'])) {
                                                echo "selected=\"selected\"";

} ?>><?=gettext("Enable (NAT + Proxy)"); ?></option>
											<option value="purenat" <?php if (!isset($config['system']['disablenatreflection']) && isset($config['system']['enablenatreflectionpurenat'])) {
                                                echo "selected=\"selected\"";

} ?>><?=gettext("Enable (Pure NAT)"); ?></option>
											</select>
											<br />
											<strong><?=gettext("When enabled, this automatically creates additional NAT redirect rules for access to port forwards on your external IP addresses from within your internal networks.");?></strong>
											<br /><br />
											<?=gettext("The NAT + proxy mode uses a helper program to send packets to the target of the port forward.  It is useful in setups where the interface and/or gateway IP used for communication with the target cannot be accurately determined at the time the rules are loaded.  Reflection rules are not created for ranges larger than 500 ports and will not be used for more than 1000 ports total between all port forwards.  Only TCP and UDP protocols are supported.");?>
											<br /><br />
											<?=gettext("The pure NAT mode uses a set of NAT rules to direct packets to the target of the port forward.  It has better scalability, but it must be possible to accurately determine the interface and gateway IP used for communication with the target at the time the rules are loaded.  There are no inherent limits to the number of ports other than the limits of the protocols.  All protocols available for port forwards are supported.");?>
											<br /><br />
											<?=gettext("Individual rules may be configured to override this system setting on a per-rule basis.");?>
										</td>
									</tr>
									<tr>
										<td width="22%" valign="top" class="vncell"><?=gettext("Reflection Timeout");?></td>
										<td width="78%" class="vtable">
											<input name="reflectiontimeout" id="reflectiontimeout" type="text" value="<?php echo $config['system']['reflectiontimeout']; ?>" /><br />
											<strong><?=gettext("Enter value for Reflection timeout in seconds.");?></strong>
											<br /><br />
											<?=gettext("Note: Only applies to Reflection on port forwards in NAT + proxy mode.");?>
										</td>
									</tr>
									<tr>
										<td width="22%" valign="top" class="vncell"><?=gettext("Enable NAT Reflection for 1:1 NAT");?></td>
										<td width="78%" class="vtable">
											<input name="enablebinatreflection" type="checkbox" id="enablebinatreflection" value="yes" <?php if (isset($config['system']['enablebinatreflection'])) {
                                                echo "checked=\"checked\"";
} ?> />
											<strong><?=gettext("Enables the automatic creation of additional NAT redirect rules for access to 1:1 mappings of your external IP addresses from within your internal networks.");?></strong>
											<br /><br />
											<?=gettext("Note: Reflection on 1:1 mappings is only for the inbound component of the 1:1 mappings.  This functions the same as the pure NAT mode for port forwards.  For more details, refer to the pure NAT mode description above.");?>
											<br /><br />
											<?=gettext("Individual rules may be configured to override this system setting on a per-rule basis.");?>
										</td>
									</tr>
									<tr>
										<td width="22%" valign="top" class="vncell"><?=gettext("Enable automatic outbound NAT for Reflection");?></td>
										<td width="78%" class="vtable">
											<input name="enablenatreflectionhelper" type="checkbox" id="enablenatreflectionhelper" value="yes" <?php if (isset($config['system']['enablenatreflectionhelper'])) {
                                                echo "checked=\"checked\"";
} ?> />
											<strong><?=gettext("Automatically create outbound NAT rules which assist inbound NAT rules that direct traffic back out to the same subnet it originated from.");?></strong>
											<br />
											<?=gettext("Required for full functionality of the pure NAT mode of NAT Reflection for port forwards or NAT Reflection for 1:1 NAT.");?>
											<br /><br />
											<?=gettext("Note: This only works for assigned interfaces.  Other interfaces require manually creating the outbound NAT rules that direct the reply packets back through the router.");?>
										</td>
									</tr>
									<tr>
										<td width="22%" valign="top" class="vncell"><?=gettext("FTP Proxy");?></td>
										<td width="78%" class="vtable">
											<input name="ftp-proxy-client" type="checkbox" id="ftp-proxy-client" value="yes" <?php if (isset($config['system']['ftp-proxy']['client'])) {
                                                echo "checked=\"checked\"";
} ?> />
											<strong><?=gettext("Enable FTP proxy for clients");?></strong>
											<br />
											<?=gettext("Configures the FTP proxy to allow for client connections behind the firewall using active file transfer mode.");?>
										</td>
									</tr>

									<?php
endif; ?>
									<tr>
										<td width="22%" valign="top">&nbsp;</td>
										<td width="78%"><input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Save");?>" /></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
				</section>
			</div>
		</div>
</section>

<?php include("foot.inc");
