<?php

/*
    Copyright (C) 2014-2015 Deciso B.V.
    Copyright (C) 2007 Seth Mos <seth.mos@dds.nl>
    Copyright (C) 2004-2009 Scott Ullrich
    Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>
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
require_once("rrd.inc");
require_once("filter.inc");
require_once("system.inc");
require_once("pfsense-utils.inc");
require_once("services.inc");


function clear_all_log_files()
{
    killbyname('syslogd');

    $clog_files = array('dhcpd', 'filter', 'gateways', 'ipsec', 'l2tps', 'lighttpd', 'ntpd', 'openvpn', 'poes',
                        'portalauth', 'ppps', 'pptps', 'relayd', 'resolver', 'routing', 'system','vpn','wireless');
    $log_files = array('squid/access', 'squid/cache', 'squid/store');

    foreach ($clog_files as $lfile) {
        clear_clog("/var/log/{$lfile}.log", false);
    }


    foreach ($log_files as $lfile) {
        clear_log("/var/log/{$lfile}.log", false);
    }

    system_syslogd_start();
    killbyname('dhcpd');
    services_dhcpd_configure();
}

function is_valid_syslog_server($target) {
    return (is_ipaddr($target)
      || is_ipaddrwithport($target)
      || is_hostname($target)
      || is_hostnamewithport($target));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pconfig = array();
    $pconfig['rrdenable'] = isset($config['rrd']['enable']);
    $pconfig['reverse'] = isset($config['syslog']['reverse']);
    $pconfig['nentries'] = !empty($config['syslog']['nentries']) ? $config['syslog']['nentries'] : 50;
    $pconfig['remoteserver'] = !empty($config['syslog']['remoteserver']) ? $config['syslog']['remoteserver'] : null;
    $pconfig['remoteserver2'] = !empty($config['syslog']['remoteserver2']) ? $config['syslog']['remoteserver2'] : null;
    $pconfig['remoteserver3'] = !empty($config['syslog']['remoteserver3']) ? $config['syslog']['remoteserver3'] : null;
    $pconfig['sourceip'] = !empty($config['syslog']['sourceip']) ? $config['syslog']['sourceip'] : null;
    $pconfig['ipproto'] = !empty($config['syslog']['ipproto']) ? $config['syslog']['ipproto'] : null;
    $pconfig['filter'] = isset($config['syslog']['filter']);
    $pconfig['dhcp'] = isset($config['syslog']['dhcp']);
    $pconfig['portalauth'] = isset($config['syslog']['portalauth']);
    $pconfig['vpn'] = isset($config['syslog']['vpn']);
    $pconfig['apinger'] = isset($config['syslog']['apinger']);
    $pconfig['relayd'] = isset($config['syslog']['relayd']);
    $pconfig['hostapd'] = isset($config['syslog']['hostapd']);
    $pconfig['logall'] = isset($config['syslog']['logall']);
    $pconfig['system'] = isset($config['syslog']['system']);
    $pconfig['enable'] = isset($config['syslog']['enable']);
    $pconfig['logdefaultblock'] = empty($config['syslog']['nologdefaultblock']);
    $pconfig['logdefaultpass'] = empty($config['syslog']['nologdefaultpass']);
    $pconfig['logbogons'] = empty($config['syslog']['nologbogons']);
    $pconfig['logprivatenets'] = empty($config['syslog']['nologprivatenets']);
    $pconfig['loglighttpd'] = empty($config['syslog']['nologlighttpd']);
    $pconfig['filterdescriptions'] = $config['syslog']['filterdescriptions'];
    $pconfig['disablelocallogging'] = isset($config['syslog']['disablelocallogging']);
    $pconfig['logfilesize'] =  !empty($config['syslog']['logfilesize']) ? $config['syslog']['logfilesize'] : null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['action']) && $_POST['action'] == "resetlogs") {
        clear_all_log_files();
        $savemsg = gettext("The log files have been reset.");
    } elseif (!empty($_POST['action']) && $_POST['action'] == "ResetRRD") {
        $savemsg = gettext('RRD data has been cleared.');
        mwexec('/bin/rm /var/db/rrd/*');
        enable_rrd_graphing();
        setup_gateways_monitor();
    } else {
        $input_errors = array();
        $pconfig = $_POST;

        /* input validation */
        if (!empty($pconfig['enable']) && !is_valid_syslog_server($pconfig['remoteserver'])) {
            $input_errors[] = gettext("A valid IP address/hostname or IP/hostname:port must be specified for remote syslog server #1.");
        }
        if (!empty($pconfig['enable']) && !empty($pconfig['remoteserver2']) && !is_valid_syslog_server($pconfig['remoteserver2'])) {
            $input_errors[] = gettext("A valid IP address/hostname or IP/hostname:port must be specified for remote syslog server #2.");
        }
        if (!empty($pconfig['enable']) && !empty($pconfig['remoteserver3']) && !is_valid_syslog_server($_POST['remoteserver3'])) {
            $input_errors[] = gettext("A valid IP address/hostname or IP/hostname:port must be specified for remote syslog server #3.");
        }

        if (($pconfig['nentries'] < 5) || ($pconfig['nentries'] > 2000)) {
            $input_errors[] = gettext("Number of log entries to show must be between 5 and 2000.");
        }

        if (!empty($pconfig['logfilesize']) && (strlen($pconfig['logfilesize']) > 0)) {
            if (!is_numeric($pconfig['logfilesize']) || ($pconfig['logfilesize'] < 5120)) {
                $input_errors[] = gettext("Log file size must be a positive integer greater than 5120.");
            }
        }
        if (count($input_errors) == 0) {
            $config['syslog']['reverse'] = !empty($pconfig['reverse']) ? true : false;
            $config['syslog']['nentries'] = (int)$pconfig['nentries'];
            if (isset($_POST['logfilesize']) && (strlen($pconfig['logfilesize']) > 0)) {
                $config['syslog']['logfilesize'] = (int)$pconfig['logfilesize'];
            } elseif (isset($config['syslog']['logfilesize'])) {
                unset($config['syslog']['logfilesize']);
            }
            $config['syslog']['remoteserver'] = $pconfig['remoteserver'];
            $config['syslog']['remoteserver2'] = $pconfig['remoteserver2'];
            $config['syslog']['remoteserver3'] = $pconfig['remoteserver3'];
            $config['syslog']['sourceip'] = $pconfig['sourceip'];
            $config['syslog']['ipproto'] = $pconfig['ipproto'];
            $config['syslog']['filter'] = !empty($pconfig['filter']);
            $config['syslog']['dhcp'] = !empty($pconfig['dhcp']);
            $config['syslog']['portalauth'] = !empty($pconfig['portalauth']);
            $config['syslog']['vpn'] = !empty($pconfig['vpn']);
            $config['syslog']['apinger'] = !empty($pconfig['apinger']);
            $config['syslog']['relayd'] = !empty($pconfig['relayd']);
            $config['syslog']['hostapd'] = !empty($pconfig['hostapd']);
            $config['syslog']['logall'] = !empty($pconfig['logall']);
            $config['syslog']['system'] = !empty($pconfig['system']);
            $config['syslog']['disablelocallogging'] = !empty($pconfig['disablelocallogging']);
            $config['syslog']['enable'] = !empty($pconfig['enable']);
            $oldnologdefaultblock = isset($config['syslog']['nologdefaultblock']);
            $oldnologdefaultpass = isset($config['syslog']['nologdefaultpass']);
            $oldnologbogons = isset($config['syslog']['nologbogons']);
            $oldnologprivatenets = isset($config['syslog']['nologprivatenets']);
            $oldnologlighttpd = isset($config['syslog']['nologlighttpd']);
            $config['syslog']['nologdefaultblock'] = empty($pconfig['logdefaultblock']);
            $config['syslog']['nologdefaultpass'] = empty($pconfig['logdefaultpass']);
            $config['syslog']['nologbogons'] = empty($pconfig['logbogons']);
            $config['syslog']['nologprivatenets'] = empty($pconfig['logprivatenets']);
            $config['syslog']['nologlighttpd'] = empty($pconfig['loglighttpd']);
            if (is_numeric($pconfig['filterdescriptions']) && $pconfig['filterdescriptions'] > 0)
                $config['syslog']['filterdescriptions'] = $pconfig['filterdescriptions'];
            elseif (isset($config['syslog']['filterdescriptions'])) {
                unset($config['syslog']['filterdescriptions']);
            }

            $config['rrd']['enable'] = !empty($pconfig['rrdenable']);

            write_config();

            $retval = 0;
            $retval = system_syslogd_start();
            if (($oldnologdefaultblock !== isset($config['syslog']['nologdefaultblock']))
              || ($oldnologdefaultpass !== isset($config['syslog']['nologdefaultpass']))
              || ($oldnologbogons !== isset($config['syslog']['nologbogons']))
              || ($oldnologprivatenets !== isset($config['syslog']['nologprivatenets'])))
              $retval |= filter_configure();

            $savemsg = get_std_save_message();

            if ($oldnologlighttpd !== isset($config['syslog']['nologlighttpd'])) {
              log_error(gettext("webConfigurator configuration has changed. Restarting webConfigurator."));
              mwexec_bg('/usr/local/etc/rc.restart_webgui 2');
              $savemsg .= "<br />" . gettext("WebGUI process is restarting.");
            }

            filter_pflog_start();
            enable_rrd_graphing();
            setup_gateways_monitor();
        }
    }
}

legacy_html_escape_form_data($pconfig);

include("head.inc");
?>


<body>
<script type="text/javascript">
//<![CDATA[
function enable_change(enable_over) {
  if (document.iform.enable.checked || enable_over) {
    document.iform.remoteserver.disabled = 0;
    document.iform.remoteserver2.disabled = 0;
    document.iform.remoteserver3.disabled = 0;
    document.iform.filter.disabled = 0;
    document.iform.dhcp.disabled = 0;
    document.iform.portalauth.disabled = 0;
    document.iform.vpn.disabled = 0;
    document.iform.apinger.disabled = 0;
    document.iform.relayd.disabled = 0;
    document.iform.hostapd.disabled = 0;
    document.iform.system.disabled = 0;
    document.iform.logall.disabled = 0;
    check_everything();
  } else {
    document.iform.remoteserver.disabled = 1;
    document.iform.remoteserver2.disabled = 1;
    document.iform.remoteserver3.disabled = 1;
    document.iform.filter.disabled = 1;
    document.iform.dhcp.disabled = 1;
    document.iform.portalauth.disabled = 1;
    document.iform.vpn.disabled = 1;
    document.iform.apinger.disabled = 1;
    document.iform.relayd.disabled = 1;
    document.iform.hostapd.disabled = 1;
    document.iform.system.disabled = 1;
    document.iform.logall.disabled = 1;
  }
}
function check_everything() {
  if (document.iform.logall.checked) {
    document.iform.filter.disabled = 1;
    document.iform.filter.checked = false;
    document.iform.dhcp.disabled = 1;
    document.iform.dhcp.checked = false;
    document.iform.portalauth.disabled = 1;
    document.iform.portalauth.checked = false;
    document.iform.vpn.disabled = 1;
    document.iform.vpn.checked = false;
    document.iform.apinger.disabled = 1;
    document.iform.apinger.checked = false;
    document.iform.relayd.disabled = 1;
    document.iform.relayd.checked = false;
    document.iform.hostapd.disabled = 1;
    document.iform.hostapd.checked = false;
    document.iform.system.disabled = 1;
    document.iform.system.checked = false;
  } else {
    document.iform.filter.disabled = 0;
    document.iform.dhcp.disabled = 0;
    document.iform.portalauth.disabled = 0;
    document.iform.vpn.disabled = 0;
    document.iform.apinger.disabled = 0;
    document.iform.relayd.disabled = 0;
    document.iform.hostapd.disabled = 0;
    document.iform.system.disabled = 0;
  }
}

$(document).ready(function() {
    enable_change(false);

    // messagebox, flush all rrd graphs
    $("#ResetRRD").click(function(event){
        event.preventDefault();
        BootstrapDialog.show({
            type:BootstrapDialog.TYPE_DANGER,
            title: "<?= gettext("Syslog");?>",
            message: "<?=gettext('Do you really want to reset the RRD graphs? This will erase all graph data.');?>",
            buttons: [{
                    label: "<?= gettext("No");?>",
                    action: function(dialogRef) {
                        dialogRef.close();
                    }}, {
                      label: "<?= gettext("Yes");?>",
                      action: function(dialogRef) {
                        $("#action").val("ResetRRD");
                        $("#iform").submit()
                    }
                }]
        });
    });

      // messagebox, flush all log files
      $("#resetlogs").click(function(event){
          event.preventDefault();
          BootstrapDialog.show({
              type:BootstrapDialog.TYPE_DANGER,
              title: "<?= gettext("Syslog");?>",
              message: "<?=gettext('Do you really want to reset the log files? This will erase all local log data.');?>",
              buttons: [{
                      label: "<?= gettext("No");?>",
                      action: function(dialogRef) {
                          dialogRef.close();
                      }}, {
                        label: "<?= gettext("Yes");?>",
                        action: function(dialogRef) {
                          $("#action").val("resetlogs");
                          $("#iform").submit()
                      }
                  }]
          });
      });

});

//]]>
</script>
<?php include("fbegin.inc"); ?>
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
          <form action="diag_logs_settings.php" method="post" name="iform" id="iform">
            <input type="hidden" id="action" name="action" value="" />
            <div class="tab-content content-box col-xs-12 __mb">
              <div class="table-responsive">
                <table class="table table-striped">
                  <tr>
                    <td colspan="2"><strong><?=gettext('Reporting Database Options');?></strong></td>
                  </tr>
                  <tr>
                    <td width="22%"><i class="fa fa-info-circle text-muted"></i> <?=gettext("Round-Robin-Database");?></td>
                    <td>
                      <input name="rrdenable" type="checkbox" id="rrdenable" value="yes" <?=!empty($pconfig['rrdenable']) ? "checked=\"checked\"" : ""?> onclick="enable_change(false)" />
                      &nbsp;<strong><?=gettext("Enables the RRD graphing backend.");?></strong>
                    </td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td>
                      <input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Save");?>" onclick="enable_change(true)" />
                      <input type="button" name="ResetRRD" id="ResetRRD" class="btn btn-default" value="<?=gettext("Reset RRD Data");?>" />
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">
                      <p>
                        <strong><span class="text-danger"><?=gettext("Note:");?></span></strong><br />
                            <?=gettext("Graphs will not be allowed to be recreated within a 1 minute interval, please " .
                            "take this into account after changing the style.");?>
                      </p>
                    </td>
                  </tr>
                </table>
              </div>
            </div>
             <div class="tab-content content-box col-xs-12 __mb">
              <div class="table-responsive">
                <table class="table table-striped">
                  <tr>
                    <td width="22%"><strong><?=gettext("Local Logging Options");?></strong></td>
                    <td  width="78%" align="right">
                      <small><?=gettext("full help"); ?> </small>
                      <i class="fa fa-toggle-off text-danger"  style="cursor: pointer;" id="show_all_help_page" type="button"></i></a>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_reverse" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Reverse Display') ?></td>
                    <td>
                      <input name="reverse" type="checkbox" id="reverse" value="yes" <?=!empty($pconfig['reverse']) ? "checked=\"checked\"" : ""; ?> />
                      <div class="hidden" for="help_for_reverse">
                        <?=gettext("Show log entries in reverse order (newest entries on top)");?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_nentries" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('GUI Log Entries to Display') ?></td>
                    <td>
                      <input name="nentries" type="text" value="<?=$pconfig['nentries'];?>" /><br />
                      <div class="hidden" for="help_for_nentries">
                        <?=gettext("Hint: This is only the number of log entries displayed in the GUI. It does not affect how many entries are contained in the actual log files.") ?>
                      </div>
                      </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_logfilesize" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Log File Size (Bytes)') ?></td>
                    <td>
                      <input name="logfilesize" type="text" value="<?=$pconfig['logfilesize'];?>" />
                      <div class="hidden" for="help_for_logfilesize">
                        <?=gettext("Logs are held in constant-size circular log files. This field controls how large each log file is, and thus how many entries may exist inside the log. By default this is approximately 500KB per log file, and there are nearly 20 such log files.") ?>
                        <br /><br />
                        <?=gettext("NOTE: Log sizes are changed the next time a log file is cleared or deleted. To immediately increase the size of the log files, you must first save the options to set the size, then clear all logs using the \"Reset Log Files\" option farther down this page. "); ?>
                        <?=gettext("Be aware that increasing this value increases every log file size, so disk usage will increase significantly."); ?>
                        <?=gettext("Disk space currently used by log files: ") ?><?= exec("/usr/bin/du -sh /var/log | /usr/bin/awk '{print $1;}'"); ?>.
                        <?=gettext("Remaining disk space for log files: ") ?><?= exec("/bin/df -h /var/log | /usr/bin/awk '{print $4;}'"); ?>.
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_logdefaultblock" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Log Firewall Default Blocks') ?></td>
                    <td>
                      <input name="logdefaultblock" type="checkbox" value="yes" <?=!empty($pconfig['logdefaultblock']) ? "checked=\"checked\"" : ""; ?> />
                      <strong><?=gettext("Log packets matched from the default block rules put in the ruleset");?></strong><br />
                      <div class="hidden" for="help_for_logdefaultblock">
                        <?=gettext("Hint: packets that are blocked by the implicit default block rule will not be logged if you uncheck this option. Per-rule logging options are still respected.");?>
                      </div>
                      <input name="logdefaultpass" type="checkbox" id="logdefaultpass" value="yes" <?=!empty($pconfig['logdefaultpass']) ? "checked=\"checked\"" :""; ?> />
                      <strong><?=gettext("Log packets matched from the default pass rules put in the ruleset");?></strong><br />
                      <div class="hidden" for="help_for_logdefaultblock">
                        <?=gettext("Hint: packets that are allowed by the implicit default pass rule will be logged if you check this option. Per-rule logging options are still respected.");?>
                      </div>
                      <input name="logbogons" type="checkbox" id="logbogons" value="yes" <?=!empty($pconfig['logbogons']) ? "checked=\"checked\"" : ""; ?> />
                      <strong><?=gettext("Log packets blocked by 'Block Bogon Networks' rules");?></strong><br />
                      <input name="logprivatenets" type="checkbox" id="logprivatenets" value="yes" <?php if ($pconfig['logprivatenets']) echo "checked=\"checked\""; ?> />
                      <strong><?=gettext("Log packets blocked by 'Block Private Networks' rules");?></strong><br />
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_loglighttpd" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Web Server Log') ?></td>
                    <td>
                      <input name="loglighttpd" type="checkbox" id="loglighttpd" value="yes" <?=!empty($pconfig['loglighttpd']) ? "checked=\"checked\"" :""; ?> />
                      <strong><?=gettext("Log errors from the web server process.");?></strong><br />
                      <div class="hidden" for="help_for_loglighttpd">
                        <?=gettext("Hint: If this is checked, errors from the lighttpd web server process for the GUI or Captive Portal will appear in the main system log.");?></td>
                      </div>
                  </tr>
                  <tr>
                      <td><a id="help_for_filterdescriptions" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Filter descriptions') ?></td>
                      <td>
                        <select name="filterdescriptions" id="filterdescriptions" class="form-control">
                          <option value="0"<?=!isset($pconfig['filterdescriptions'])?" selected=\"selected\"":""?>><?=gettext('Omit descriptions') ?></option>
                          <option value="1"<?=($pconfig['filterdescriptions'])==="1"?" selected=\"selected\"":""?>><?=gettext('Display as column') ?></option>
                          <option value="2"<?=($pconfig['filterdescriptions'])==="2"?" selected=\"selected\"":""?>><?=gettext('Display as second row') ?></option>
                        </select>
                        <div class="hidden" for="help_for_filterdescriptions">
                          <strong><?=gettext("Show the applied rule description below or in the firewall log rows.");?></strong>
                          <br />
                          <?=gettext("Displaying rule descriptions for all lines in the log might affect performance with large rule sets.");?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><i class="fa fa-info-circle text-muted"></i>  <?=gettext('Local Logging') ?></td>
                      <td> <input name="disablelocallogging" type="checkbox" id="disablelocallogging" value="yes" <?=!empty($pconfig['disablelocallogging']) ? "checked=\"checked\"" :""; ?> onclick="enable_change(false)" />
                      <strong><?=gettext("Disable writing log files to the local disk");?></strong></td>
                    </tr>
                    <tr>
                      <td><a id="help_for_resetlogs" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Reset Logs') ?></td>
                      <td>
                        <input name="resetlogs" id="resetlogs" type="submit" class="btn btn-default" value="<?=gettext("Reset Log Files"); ?>"/>
                        <div class="hidden" for="help_for_resetlogs">
                          <?= gettext("Note: Clears all local log files and reinitializes them as empty logs. This also restarts the DHCP daemon. Use the Save button first if you have made any setting changes."); ?>
                        </div>
                      </td>
                    </tr>
                  </table>
                </div>
              </div>

              <div class="tab-content content-box col-xs-12 __mb">
                <div class="table-responsive">
                  <table class="table table-striped">
                    <tr>
                      <td width="22%"><strong><?=gettext("Remote Logging Options");?></strong></td>
                      <td  width="78%" align="right">
                        <small><?=gettext("full help"); ?> </small>
                        <i class="fa fa-toggle-off text-danger"  style="cursor: pointer;" id="show_all_help_page" type="button"></i></a>
                      </td>
                    </tr>
                    <tr>
                      <td><a id="help_for_sourceip" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Source Address"); ?></td>
                      <td>
                        <select name="sourceip"  class="form-control">
                          <option value=""><?=gettext('Default (any)') ?></option>
<?php
                          foreach (get_possible_traffic_source_addresses(false) as $sip):?>
                          <option value="<?=$sip['value'];?>" <?=!link_interface_to_bridge($sip['value']) && ($sip['value'] == $pconfig['sourceip']) ? "selected=\"selected\"" : "";?>>
                            <?=htmlspecialchars($sip['name']);?>
                          </option>
<?php
                          endforeach; ?>
                        </select>
                        <div class="hidden" for="help_for_sourceip">
                          <?= gettext("This option will allow the logging daemon to bind to a single IP address, rather than all IP addresses."); ?>
                          <?= gettext("If you pick a single IP, remote syslog severs must all be of that IP type. If you wish to mix IPv4 and IPv6 remote syslog servers, you must bind to all interfaces."); ?>
                          <br /><br />
                          <?= gettext("NOTE: If an IP address cannot be located on the chosen interface, the daemon will bind to all addresses."); ?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><a id="help_for_ipproto" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("IP Protocol"); ?></td>
                      <td>
                        <select name="ipproto" class="form-control">
                          <option value="ipv4" <?=$ipproto == "ipv4" ? 'selected="selected"' : "";?>><?=gettext("IPv4");?></option>
                          <option value="ipv6" <?=$ipproto == "ipv6" ? 'selected="selected"' : "";?>><?=gettext("IPv6");?></option>
                        </select>
                        <div class="hidden" for="help_for_ipproto">
                          <?= gettext("This option is only used when a non-default address is chosen as the source above. This option only expresses a preference; If an IP address of the selected type is not found on the chosen interface, the other type will be tried."); ?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("Enable Remote Logging");?></td>
                      <td>
                        <input name="enable" type="checkbox" id="enable" value="yes" <?= !empty($pconfig['enable']) ? "checked=\"checked\"" :""; ?> onclick="enable_change(false)" />
                        <strong><?=gettext("Send log messages to remote syslog server");?></strong>
                      </td>
                    </tr>
                    <tr>
                      <td><a id="help_for_remoteserver" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Remote Syslog Servers");?></td>
                      <td>
                        <table class="table table-condensed">
                          <tr>
                            <td><?=gettext("Server") . " 1";?></td>
                            <td><input name="remoteserver" id="remoteserver" type="text" class="form-control host" size="20" value="<?=htmlspecialchars($pconfig['remoteserver']);?>" /></td>
                          </tr>
                          <tr>
                            <td><?=gettext("Server") . " 2";?></td>
                            <td><input name="remoteserver2" id="remoteserver2" type="text" class="form-control host" size="20" value="<?=htmlspecialchars($pconfig['remoteserver2']);?>" /></td>
                          </tr>
                          <tr>
                            <td><?=gettext("Server") . " 3";?></td>
                            <td><input name="remoteserver3" id="remoteserver3" type="text" class="form-control host" size="20" value="<?=htmlspecialchars($pconfig['remoteserver3']);?>" /></td>
                          </tr>
                        </table>
                        <div class="hidden" for="help_for_remoteserver">
                          <?=gettext("IP addresses of remote syslog servers, or an IP:port.");?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("Remote Syslog Contents");?></td>
                      <td>
                        <input name="logall" id="logall" type="checkbox" value="yes" <?=!empty($pconfig['logall']) ? "checked=\"checked\"" : ""; ?> onclick="check_everything();" />
                        <?=gettext("Everything");?><br /><br />
                        <input name="system" id="system" type="checkbox" value="yes" onclick="enable_change(false)" <?=!empty($pconfig['system']) ? "checked=\"checked\"" : ""; ?> />
                        <?=gettext("System events");?><br />
                        <input name="filter" id="filter" type="checkbox" value="yes" <?=!empty($pconfig['filter']) ? "checked=\"checked\"" : ""; ?> />
                        <?=gettext("Firewall events");?><br />
                        <input name="dhcp" id="dhcp" type="checkbox" value="yes" <?=!empty($pconfig['dhcp']) ? "checked=\"checked\"" : ""; ?> />
                        <?=gettext("DHCP service events");?><br />
                        <input name="portalauth" id="portalauth" type="checkbox" value="yes" <?=!empty($pconfig['portalauth']) ? "checked=\"checked\"" : ""; ?> />
                        <?=gettext("Portal Auth events");?><br />
                        <input name="vpn" id="vpn" type="checkbox" value="yes" <?=!empty($pconfig['vpn']) ? "checked=\"checked\"" : ""; ?> />
                        <?=gettext("VPN (PPTP, IPsec, OpenVPN) events");?><br />
                        <input name="apinger" id="apinger" type="checkbox" value="yes" <?=!empty($pconfig['apinger']) ? "checked=\"checked\"" : ""; ?> />
                        <?=gettext("Gateway Monitor events");?><br />
                        <input name="relayd" id="relayd" type="checkbox" value="yes" <?=!empty($pconfig['relayd']) ? "checked=\"checked\"" : ""; ?> />
                        <?=gettext("Server Load Balancer events");?><br />
                        <input name="hostapd" id="hostapd" type="checkbox" value="yes" <?=!empty($pconfig['hostapd']) ? "checked=\"checked\"" : ""; ?> />
                        <?=gettext("Wireless events");?><br />
                      </td>
                    </tr>
                    <tr>
                      <td></td>
                      <td> <input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" onclick="enable_change(true)" />
                      </td>
                    </tr>
                    <tr>
                      <td  colspan="2"><strong><span class="text-danger"><?=gettext("Note:")?></span></strong><br />
                      <?=gettext("syslog sends UDP datagrams to port 514 on the specified " .
                      "remote syslog server, unless another port is specified. Be sure to set syslogd on the " .
                      "remote server to accept syslog messages from");?> <?=$g['product_name']?>.
                      </td>
                    </tr>
                  </table>
                </div>
            </div>
          </form>
        </section>
      </div>
    </div>
  </section>
<?php include("foot.inc"); ?>
