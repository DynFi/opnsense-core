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
require_once("vpn.inc");
require_once("vslb.inc");
require_once("system.inc");
require_once("pfsense-utils.inc");
require_once("services.inc");
require_once("interfaces.inc");

$pconfig['proxyurl'] = $config['system']['proxyurl'];
$pconfig['proxyport'] = $config['system']['proxyport'];
$pconfig['proxyuser'] = $config['system']['proxyuser'];
$pconfig['proxypass'] = $config['system']['proxypass'];
$pconfig['harddiskstandby'] = $config['system']['harddiskstandby'];
$pconfig['lb_use_sticky'] = isset($config['system']['lb_use_sticky']);
$pconfig['srctrack'] = $config['system']['srctrack'];
$pconfig['gw_switch_default'] = isset($config['system']['gw_switch_default']);
$pconfig['powerd_enable'] = isset($config['system']['powerd_enable']);
$pconfig['crypto_hardware'] = $config['system']['crypto_hardware'];
$pconfig['thermal_hardware'] = $config['system']['thermal_hardware'];
$pconfig['schedule_states'] = isset($config['system']['schedule_states']);
$pconfig['kill_states'] = isset($config['system']['kill_states']);
$pconfig['skip_rules_gw_down'] = isset($config['system']['skip_rules_gw_down']);
$pconfig['use_mfs_tmpvar'] = isset($config['system']['use_mfs_tmpvar']);

$pconfig['powerd_ac_mode'] = "hadp";
if (!empty($config['system']['powerd_ac_mode'])) {
    $pconfig['powerd_ac_mode'] = $config['system']['powerd_ac_mode'];
}

$pconfig['powerd_battery_mode'] = "hadp";
if (!empty($config['system']['powerd_battery_mode'])) {
    $pconfig['powerd_battery_mode'] = $config['system']['powerd_battery_mode'];
}

$crypto_modules = array('glxsb' => gettext("AMD Geode LX Security Block"),
            'aesni' => gettext("AES-NI CPU-based Acceleration"));

$thermal_hardware_modules = array(  'coretemp' => gettext("Intel Core* CPU on-die thermal sensor"),
                    'amdtemp' => gettext("AMD K8, K10 and K11 CPU on-die thermal sensor"));

if ($_POST) {
    unset($input_errors);
    $pconfig = $_POST;

    ob_flush();
    flush();

    if (!empty($_POST['crypto_hardware']) && !array_key_exists($_POST['crypto_hardware'], $crypto_modules)) {
        $input_errors[] = gettext("Please select a valid Cryptographic Accelerator.");
    }

    if (!empty($_POST['thermal_hardware']) && !array_key_exists($_POST['thermal_hardware'], $thermal_hardware_modules)) {
        $input_errors[] = gettext("Please select a valid Thermal Hardware Sensor.");
    }

    if (!$input_errors) {
        if ($_POST['harddiskstandby'] <> "") {
            $config['system']['harddiskstandby'] = $_POST['harddiskstandby'];
            system_set_harddisk_standby();
        } else {
            unset($config['system']['harddiskstandby']);
        }

        if ($_POST['proxyurl'] <> "") {
            $config['system']['proxyurl'] = $_POST['proxyurl'];
        } else {
            unset($config['system']['proxyurl']);
        }

        if ($_POST['proxyport'] <> "") {
            $config['system']['proxyport'] = $_POST['proxyport'];
        } else {
            unset($config['system']['proxyport']);
        }

        if ($_POST['proxyuser'] <> "") {
            $config['system']['proxyuser'] = $_POST['proxyuser'];
        } else {
            unset($config['system']['proxyuser']);
        }

        if ($_POST['proxypass'] <> "") {
            $config['system']['proxypass'] = $_POST['proxypass'];
        } else {
            unset($config['system']['proxypass']);
        }

        $need_relayd_restart = false;
        if ($_POST['lb_use_sticky'] == "yes") {
            if (!isset($config['system']['lb_use_sticky'])) {
                $config['system']['lb_use_sticky'] = true;
                $config['system']['srctrack'] = $_POST['srctrack'];
                $need_relayd_restart = true;
            }
        } else {
            if (isset($config['system']['lb_use_sticky'])) {
                unset($config['system']['lb_use_sticky']);
                $need_relayd_restart = true;
            }
        }

        if ($_POST['gw_switch_default'] == "yes") {
            $config['system']['gw_switch_default'] = true;
        } else {
            unset($config['system']['gw_switch_default']);
        }

        if ($_POST['powerd_enable'] == "yes") {
            $config['system']['powerd_enable'] = true;
        } else {
            unset($config['system']['powerd_enable']);
        }

        $config['system']['powerd_ac_mode'] = $_POST['powerd_ac_mode'];
        $config['system']['powerd_battery_mode'] = $_POST['powerd_battery_mode'];

        if ($_POST['crypto_hardware']) {
            $config['system']['crypto_hardware'] = $_POST['crypto_hardware'];
        } else {
            unset($config['system']['crypto_hardware']);
        }

        if ($_POST['thermal_hardware']) {
            $config['system']['thermal_hardware'] = $_POST['thermal_hardware'];
        } else {
            unset($config['system']['thermal_hardware']);
        }

        if ($_POST['schedule_states'] == "yes") {
            $config['system']['schedule_states'] = true;
        } else {
            unset($config['system']['schedule_states']);
        }

        if ($_POST['kill_states'] == "yes") {
            $config['system']['kill_states'] = true;
        } else {
            unset($config['system']['kill_states']);
        }

        if ($_POST['skip_rules_gw_down'] == "yes") {
            $config['system']['skip_rules_gw_down'] = true;
        } else {
            unset($config['system']['skip_rules_gw_down']);
        }

        if ($_POST['use_mfs_tmpvar'] == "yes") {
            $config['system']['use_mfs_tmpvar'] = true;
        } else {
            unset($config['system']['use_mfs_tmpvar']);
        }

        if (isset($_POST['rrdbackup'])) {
            $config['system']['rrdbackup'] = $_POST['rrdbackup'];
            install_cron_job("/usr/local/etc/rc.backup_rrd", ($config['system']['rrdbackup'] > 0), $minute = "0", "*/{$config['system']['rrdbackup']}");
        }
        if (isset($_POST['dhcpbackup'])) {
            $config['system']['dhcpbackup'] = $_POST['dhcpbackup'];
            install_cron_job("/usr/local/etc/rc.backup_dhcpleases", ($config['system']['dhcpbackup'] > 0), $minute = "0", "*/{$config['system']['dhcpbackup']}");
        }

        write_config();

        $retval = 0;
        system_resolvconf_generate(true);
        $retval = filter_configure();
        if (stristr($retval, "error") <> true) {
            $savemsg = get_std_save_message(gettext($retval));
        } else {
            $savemsg = gettext($retval);
        }

        activate_powerd();
        load_crypto();
        load_thermal_hardware();
        if ($need_relayd_restart) {
            relayd_configure();
        }
    }
}

$pgtitle = array(gettext("System"),gettext("Settings"),gettext("Miscellaneous"));
include("head.inc");

?>

<body>
    <?php
    include("fbegin.inc");
    ?>
    <script type="text/javascript">
    //<![CDATA[
    function sticky_checked(obj) {
	if (obj.checked)
		jQuery('#srctrack').attr('disabled',false);
	else
		jQuery('#srctrack').attr('disabled','true');
    }
    function tmpvar_checked(obj) {
	if (obj.checked) {
		jQuery('#rrdbackup').attr('disabled',false);
		jQuery('#dhcpbackup').attr('disabled',false);
		jQuery('#rrdbackup').selectpicker('refresh');
		jQuery('#dhcpbackup').selectpicker('refresh');
	} else {
		jQuery('#rrdbackup').attr('disabled','true');
		jQuery('#dhcpbackup').attr('disabled','true');
		jQuery('#rrdbackup').selectpicker('refresh');
		jQuery('#dhcpbackup').selectpicker('refresh');
	}
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
					<form action="system_advanced_misc.php" method="post" name="iform" id="iform">
						<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="main area" class="table table-striped">
							<thead>
							<tr>
								<th colspan="2" valign="top" class="listtopic"><?=gettext("Proxy support"); ?></th>
							</tr>
							</thead>

							<tbody>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Proxy URL"); ?></td>
									<td width="78%" class="vtable">
										<input name="proxyurl" id="proxyurl" type="text" value="<?php if ($pconfig['proxyurl'] <> "") {
                                            echo $pconfig['proxyurl'];
} ?>" class="formfld unknown" />
										<br />
										<?php printf(gettext("Proxy url for allowing %s to use this proxy to connect outside."), $g['product']); ?>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Proxy Port"); ?></td>
									<td width="78%" class="vtable">
										<input name="proxyport" id="proxyport" type="text" value="<?php if ($pconfig['proxyport'] <> "") {
                                            echo $pconfig['proxyport'];
} ?>" class="formfld unknown" />
										<br />
										<?php printf(gettext("Proxy port to use when %s connects to the proxy URL configured above. Default is 8080 for http protocol or 443 for ssl."), $g['product']); ?>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Proxy Username"); ?></td>
									<td width="78%" class="vtable">
										<input name="proxyuser" id="proxyuser" type="text" value="<?php if ($pconfig['proxyuser'] <> "") {
                                            echo $pconfig['proxyuser'];
} ?>" class="formfld unknown" />
										<br />
										<?php printf(gettext("Proxy username for allowing %s to use this proxy to connect outside"), $g['product']); ?>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Proxy Pass"); ?></td>
									<td width="78%" class="vtable">
										<input type="password" name="proxypass" id="proxypass" value="<?php if ($pconfig['proxypass'] <> "") {
                                            echo $pconfig['proxypass'];
} ?>" class="formfld unknown" />
										<br />
										<?php printf(gettext("Proxy password for allowing %s to use this proxy to connect outside"), $g['product']); ?>
									</td>
								</tr>

								<tr>
									<th colspan="2" valign="top" class="listtopic"><?=gettext("Load Balancing"); ?></th>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"></td>
									<td width="78%" class="vtable">
										<input name="gw_switch_default" type="checkbox" id="gw_switch_default" value="yes" <?php if ($pconfig['gw_switch_default']) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Allow default gateway switching"); ?></strong><br />
										<?=gettext("If the link where the default gateway resides fails " .
                                        "switch the default gateway to another available one."); ?>
										<br />
										<br />
										<input name="lb_use_sticky" type="checkbox" id="lb_use_sticky" value="yes" <?php if ($pconfig['lb_use_sticky']) {
                                            echo "checked=\"checked\"";
} ?> onclick="sticky_checked(this)" />
										<strong><?=gettext("Use sticky connections"); ?></strong><br />
										<?=gettext("Successive connections will be redirected to the servers " .
                                        "in a round-robin manner with connections from the same " .
                                        "source being sent to the same web server. This 'sticky " .
                                        "connection' will exist as long as there are states that " .
                                        "refer to this connection. Once the states expire, so will " .
                                        "the sticky connection. Further connections from that host " .
                                        "will be redirected to the next web server in the round " .
                                        "robin. Changing this option will restart the Load Balancing service."); ?>
										<br />
										<br />
										<input name="srctrack" id="srctrack" type="text" value="<?php if ($pconfig['srctrack'] <> "") {
                                            echo $pconfig['srctrack'];

} else {
    "1400";
} ?>" class="formfld unknown" <?php if ($pconfig['lb_use_sticky'] == false) {
    echo "disabled=\"disabled\"";
} ?> />
										<?=gettext("Set the source tracking timeout for sticky connections. " .
                                        "By default this is 0, so source tracking is removed as soon as the state expires. " .
                                        "Setting this timeout higher will cause the source/destination relationship to persist for longer periods of time."); ?>
									</td>
								</tr>
								<tr>
									<th colspan="2" valign="top" class="listtopic"><?=gettext("Power savings"); ?></th>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"></td>
									<td width="78%" class="vtable">
										<input name="powerd_enable" type="checkbox" id="powerd_enable" value="yes" <?php if ($pconfig['powerd_enable']) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Use PowerD"); ?></strong><br />
										<br />
										<?=gettext("On AC Power Mode"); ?>&nbsp;:&nbsp;
										<select name="powerd_ac_mode" id="powerd_ac_mode" class="selectpicker" data-style="btn-default" data-width="auto">
											<option value="hadp"<?php if ($pconfig['powerd_ac_mode']=="hadp") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("Hiadaptive");?></option>
											<option value="adp"<?php if ($pconfig['powerd_ac_mode']=="adp") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("Adaptive");?></option>
											<option value="min"<?php if ($pconfig['powerd_ac_mode']=="min") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("Minimum");?></option>
											<option value="max"<?php if ($pconfig['powerd_ac_mode']=="max") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("Maximum");?></option>
										</select>
										&nbsp;&nbsp;
										<?=gettext("On Battery Power Mode"); ?>&nbsp;:&nbsp;
										<select name="powerd_battery_mode" id="powerd_battery_mode" class="selectpicker" data-style="btn-default" data-width="auto">
											<option value="hadp"<?php if ($pconfig['powerd_battery_mode']=="hadp") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("Hiadaptive");?></option>
											<option value="adp"<?php if ($pconfig['powerd_battery_mode']=="adp") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("Adaptive");?></option>
											<option value="min"<?php if ($pconfig['powerd_battery_mode']=="min") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("Minimum");?></option>
											<option value="max"<?php if ($pconfig['powerd_battery_mode']=="max") {
                                                echo " selected=\"selected\"";

} ?>><?=gettext("Maximum");?></option>
										</select>
										<br /><br />
										<?=gettext("The powerd utility monitors the system state and sets various power control " .
                                        "options accordingly.  It offers four modes (maximum, minimum, adaptive " .
                                        "and hiadaptive) that can be individually selected while on AC power or batteries. " .
                                        "The modes maximum, minimum, adaptive and hiadaptive may be abbreviated max, " .
                                        "min, adp, hadp.  Maximum mode chooses the highest performance values.  Minimum " .
                                        "mode selects the lowest performance values to get the most power savings. " .
                                        "Adaptive mode attempts to strike a balance by degrading performance when " .
                                        "the system appears idle and increasing it when the system is busy.  It " .
                                        "offers a good balance between a small performance loss for greatly " .
                                        "increased power savings.  Hiadaptive mode is alike adaptive mode, but " .
                                        "tuned for systems where performance and interactivity are more important " .
                                        "than power consumption.  It raises frequency faster, drops slower and " .
                                        "keeps twice lower CPU load."); ?>
									</td>
								</tr>
								<tr>
									<th colspan="2" valign="top" class="listtopic"><?=gettext("Cryptographic Hardware Acceleration"); ?></th>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"></td>
									<td width="78%" class="vtable">
										<select name="crypto_hardware" id="crypto_hardware" class="selectpicker" data-style="btn-default">
											<option value=""><?php echo gettext("None"); ?></option>
											<?php foreach ($crypto_modules as $cryptomod_name => $cryptomod_descr) :
?>
												<option value="<?php echo $cryptomod_name; ?>" <?php if ($pconfig['crypto_hardware'] == $cryptomod_name) {
                                                    echo " selected=\"selected\"";
} ?>><?php echo "{$cryptomod_descr} ({$cryptomod_name})"; ?></option>
											<?php
endforeach; ?>
										</select>
										<br />
										<?=gettext("A cryptographic accelerator module will use hardware support to speed up some " .
                                            "cryptographic functions on systems which have the chip. Do not enable this " .
                                            "option if you have a Hifn cryptographic acceleration card, as this will take " .
                                            "precedence and the Hifn card will not be used. Acceleration should be automatic " .
                                            "for IPsec when using a cipher supported by your chip, such as AES-128. OpenVPN " .
                                            "should be set for AES-128-CBC and have cryptodev enabled for hardware " .
                                            "acceleration."); ?>
										<br /><br />
										<?=gettext("If you do not have a crypto chip in your system, this option will have no " .
                                        "effect. To unload the selected module, set this option to 'none' and then reboot."); ?>
									</td>
								</tr>
								<tr>
									<th colspan="2" valign="top" class="listtopic"><?=gettext("Thermal Sensors"); ?></th>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"></td>
									<td width="78%" class="vtable">
									<select name="thermal_hardware" id="thermal_hardware" class="selectpicker" data-style="btn-default">
										<option value=""><?php echo gettext("None/ACPI"); ?></option>
										<?php foreach ($thermal_hardware_modules as $themalmod_name => $themalmod_descr) :
?>
											<option value="<?php echo $themalmod_name; ?>" <?php if ($pconfig['thermal_hardware'] == $themalmod_name) {
                                                echo " selected=\"selected\"";
} ?>><?php echo "{$themalmod_descr} ({$themalmod_name})"; ?></option>
										<?php
endforeach; ?>
									</select>
									<br />
									<?=gettext("If you have a supported CPU, selecting a themal sensor will load the appropriate " .
                                            "driver to read its temperature. Setting this to 'None' will attempt to read the " .
                                            "temperature from an ACPI-compliant motherboard sensor instead, if one is present."); ?>
									<br /><br />
									<?=gettext("If you do not have a supported thermal sensor chip in your system, this option will have no " .
                                        "effect. To unload the selected module, set this option to 'none' and then reboot."); ?>
									</td>
								</tr>
								<tr>
									<th colspan="2" valign="top" class="listtopic"><?=gettext("Schedules"); ?></th>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"></td>
									<td width="78%" class="vtable">
										<input name="schedule_states" type="checkbox" id="schedule_states" value="yes" <?php if ($pconfig['schedule_states']) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Schedule States"); ?></strong>
										<br />
										<?=gettext("By default schedules clear the states of existing connections when the expiration time has come. ".
                                        "This option overrides that behavior by not clearing states for existing connections."); ?>
									</td>
								</tr>
								<tr>
									<th colspan="2" valign="top" class="listtopic"><?=gettext("Gateway Monitoring"); ?></th>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"></td>
									<td width="78%" class="vtable">
										<input name="kill_states" type="checkbox" id="kill_states" value="yes" <?php if ($pconfig['kill_states']) {
                                            echo "checked=\"checked\"";
} ?> />
										</strong><?=gettext("State Killing on Gateway Failure"); ?></strong>
										<br />
										<?=gettext("The monitoring process will flush states for a gateway that goes down if this box is not checked. Check this box to disable this behavior."); ?>
										<br />
										<br />
										<input name="skip_rules_gw_down" type="checkbox" id="skip_rules_gw_down" value="yes" <?php if ($pconfig['skip_rules_gw_down']) {
                                            echo "checked=\"checked\"";
} ?> />
										<strong><?=gettext("Skip rules when gateway is down"); ?></strong>
										<br />
										<?=gettext("By default, when a rule has a specific gateway set, and this gateway is down, ".
                                        "rule is created and traffic is sent to default gateway.This option overrides that behavior ".
                                        "and the rule is not created when gateway is down"); ?>
									</td>
								</tr>
								<tr>
									<th colspan="2" valign="top" class="listtopic"><?=gettext("RAM Disk Settings (Reboot to Apply Changes)"); ?></th>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Use RAM Disks"); ?></td>
									<td width="78%" class="vtable">
										<input name="use_mfs_tmpvar" type="checkbox" id="use_mfs_tmpvar" value="yes" <?php if ($pconfig['use_mfs_tmpvar']) {
                                            echo "checked=\"checked\"";
} ?> onclick="tmpvar_checked(this)" />
										<strong><?=gettext("Use memory file system for /tmp and /var"); ?></strong><br />
										<?=gettext("Set this if you wish to use /tmp and /var as RAM disks (memory file system disks) on a full install " .
                                        "rather than use the hard disk. Setting this will cause the data in /tmp and /var to be lost at reboot, including log data. RRD and DHCP Leases will be retained."); ?>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Periodic RRD Backup");?></td>
									<td width="78%" class="vtable">
										<select name="rrdbackup" class="selectpicker" data-style="btn-default" id="rrdbackup" <?php if ($pconfig['use_mfs_tmpvar'] == false) {
                                            echo "disabled=\"disabled\"";
} ?> >
											<option value='0' <?php if (!isset($config['system']['rrdbackup']) || ($config['system']['rrdbackup'] == 0)) {
                                                echo "selected='selected'";

} ?>><?=gettext("Disable"); ?></option>
										<?php for ($x=1; $x<=24; $x++) {
?>
											<option value='<?= $x ?>' <?php if ($config['system']['rrdbackup'] == $x) {
                                                echo "selected='selected'";

} ?>><?= $x ?> <?=gettext("hour"); ?><?php if ($x>1) {
    echo "s";
} ?></option>
										<?php
} ?>
										</select>
										<br />
										<?=gettext("This will periodically backup the RRD data so it can be restored automatically on the next boot. Keep in mind that the more frequent the backup, the more writes will happen to your media.");?>
										<br />
										<br />
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Periodic DHCP Leases Backup");?></td>
									<td width="78%" class="vtable">
										<select name="dhcpbackup" class="selectpicker" data-style="btn-default" id="dhcpbackup" <?php if ($pconfig['use_mfs_tmpvar'] == false) {
                                            echo "disabled=\"disabled\"";
} ?> >
											<option value='0' <?php if (!isset($config['system']['dhcpbackup']) || ($config['system']['dhcpbackup'] == 0)) {
                                                echo "selected='selected'";

} ?>><?=gettext("Disable"); ?></option>
										<?php for ($x=1; $x<=24; $x++) {
?>
											<option value='<?= $x ?>' <?php if ($config['system']['dhcpbackup'] == $x) {
                                                echo "selected='selected'";

} ?>><?= $x ?> <?=gettext("hour"); ?><?php if ($x>1) {
    echo "s";
} ?></option>
										<?php
} ?>
										</select>
										<br />
										<?=gettext("This will periodically backup the DHCP leases data so it can be restored automatically on the next boot. Keep in mind that the more frequent the backup, the more writes will happen to your media.");?>
										<br />
										<br />
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top">&nbsp;</td>
									<td width="78%">
										<input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Save");?>" />
									</td>
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
