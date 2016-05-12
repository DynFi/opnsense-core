<?php

/*
    Copyright (C) 2014-2015 Deciso B.V.
    Copyright (C) 2012 Darren Embry <dse@webonastick.com>
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

if (!isset($config['hasync']) || !is_array($config['hasync'])) {
    $config['hasync'] = array();
}
$a_hasync = &$config['hasync'];

$checkbox_names = array('pfsyncenabled', 'synchronizeusers', 'synchronizeauthservers', 'synchronizecerts',
                        'synchronizerules', 'synchronizeschedules', 'synchronizealiases', 'synchronizenat',
                        'synchronizeipsec', 'synchronizeopenvpn', 'synchronizedhcpd', 'synchronizewol',
                        'synchronizestaticroutes', 'synchronizelb', 'synchronizevirtualip',
                        'synchronizednsforwarder','synchronizednsresolver', 'synchronizeshaper', 'synchronizecaptiveportal'
);


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pconfig = array();
    foreach ($checkbox_names as $name) {
        if (isset($a_hasync[$name])) {
            $pconfig[$name] = $a_hasync[$name];
        } else {
            $pconfig[$name] = null;
        }
    }
    foreach (array('pfsyncpeerip','pfsyncinterface','synchronizetoip','username','password') as $tag) {
        if (isset($a_hasync[$tag])) {
            $pconfig[$tag] = $a_hasync[$tag];
        } else {
            $pconfig[$tag] = null;
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pconfig = $_POST;
    foreach ($checkbox_names as $name) {
        if (isset($pconfig[$name])) {
            $a_hasync[$name] = $pconfig[$name];
        } else {
            $a_hasync[$name] = false;
        }
    }
    $a_hasync['pfsyncpeerip']    = $pconfig['pfsyncpeerip'];
    $a_hasync['pfsyncinterface'] = $pconfig['pfsyncinterface'];
    $a_hasync['synchronizetoip'] = $pconfig['synchronizetoip'];
    $a_hasync['username']        = $pconfig['username'];
    $a_hasync['password']        = $pconfig['password'];
    write_config("Updated High Availability configuration");
    interfaces_carp_setup();
    header("Location: system_hasync.php");
    exit();
}

legacy_html_escape_form_data($pconfig);
include("head.inc");
?>

<body>
<?php include("fbegin.inc"); ?>
<section class="page-content-main">
  <div class="container-fluid">
    <div class="row">
      <section class="col-xs-12">
        <div class="content-box">
          <div class="table-responsive">
            <form method="post">
              <table class="table table-striped">
                <tr>
                  <td width="22%"><strong><?=gettext('State Synchronization') ?></strong></td>
                  <td  width="78%" align="right">
                    <small><?=gettext("full help"); ?> </small>
                    <i class="fa fa-toggle-off text-danger"  style="cursor: pointer;" id="show_all_help_page" type="button"></i>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_pfsyncenabled" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Synchronize States') ?></td>
                  <td>
                    <input type="checkbox" name="pfsyncenabled" value="on" <?= !empty($pconfig['pfsyncenabled']) ? "checked=\"checked\"" : "";?> />
                    <div class="hidden" for="help_for_pfsyncenabled">
                      <?= sprintf(gettext('pfsync transfers state insertion, update, and deletion messages between firewalls.%s' .
                        'Each firewall sends these messages out via multicast on a specified interface, using the PFSYNC protocol (%sIP Protocol 240%s).%s' .
                        'It also listens on that interface for similar messages from other firewalls, and imports them into the local state table.%s' .
                        'This setting should be enabled on all members of a failover group.'), '<br/>','<a href="http://www.openbsd.org/faq/pf/carp.html" target="_blank">','</a>','<br/>','<br/>') ?>
                      <div class="well well-sm" ><b><?=gettext('Clicking save will force a configuration sync if it is enabled! (see Configuration Synchronization Settings below)') ?></b></div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_pfsyncinterface" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Synchronize Interface') ?></td>
                  <td>
                    <select name="pfsyncinterface" class="selectpicker" data-style="btn-default" data-live-search="true" data-width="auto">
<?php
                    $ifaces = get_configured_interface_with_descr();
                    $ifaces["lo0"] = gettext("loopback");
                    foreach ($ifaces as $ifname => $iface):
?>
                      <option value="<?=htmlentities($ifname);?>" <?= ($pconfig['pfsyncinterface'] === $ifname) ? 'selected="selected"' : ''; ?>>
                        <?= htmlentities($iface); ?>
                      </option>
<?php
                    endforeach; ?>
                    </select>
                    <div class="hidden" for="help_for_pfsyncinterface">
                      <?=gettext('If Synchronize States is enabled, it will utilize this interface for communication.') ?><br/><br/>
                      <div class="well">
                        <lu>
                        <li><?=gettext('We recommend setting this to a interface other than LAN!  A dedicated interface works the best.') ?></li>
                        <li><?=gettext('You must define a IP on each machine participating in this failover group.') ?></li>
                        <li><?=gettext('You must have an IP assigned to the interface on any participating sync nodes.') ?></li>
                        </lu>
                      </div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_pfsyncpeerip" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Synchronize Peer IP') ?></td>
                  <td>
                    <input name="pfsyncpeerip" type="text" value="<?=$pconfig['pfsyncpeerip']; ?>" />
                    <div class="hidden" for="help_for_pfsyncpeerip">
                      <?=gettext('Setting this option will force pfsync to synchronize its state table to this IP address.  The default is directed multicast.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <th colspan="2" class="listtopic"><?=gettext('Configuration Synchronization Settings (XMLRPC Sync)') ?></th>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizetoip" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Synchronize Config to IP') ?></td>
                  <td>
                    <input name="synchronizetoip" type="text" value="<?=$pconfig['synchronizetoip']; ?>" />
                    <div class="hidden" for="help_for_synchronizetoip">
                      <?=gettext('Enter the IP address of the firewall to which the selected configuration sections should be synchronized.') ?><br />
                      <div class="well">
                        <lu>
                          <li><?=sprintf(gettext('When using XMLRPC sync to a backup machine running on another port/protocol please input the full url (example: %s)'), 'https://192.168.1.1:444/') ?></li>
                          <li><b><?=gettext('Do not use the Synchronize Config to IP and password option on backup cluster members!') ?></b></li>
                        </lu>
                      </div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_username" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Remote System Username') ?></td>
                  <td>
                    <input  name="username" type="text" value="<?=$pconfig['username'];?>" />
                    <div class="hidden" for="help_for_username">
                      <?=gettext('Enter the webConfigurator username of the system entered above for synchronizing your configuration.') ?><br />
                      <div class="well well-sm">
                        <b><?=gettext('Do not use the Synchronize Config to IP and username option on backup cluster members!') ?></b>
                      </div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_password" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Remote System Password') ?></td>
                  <td>
                    <input  type="password" name="password" value="<?=$pconfig['password']; ?>" />
                    <div class="hidden" for="help_for_password">
                      <?=gettext('Enter the webConfigurator password of the system entered above for synchronizing your configuration.') ?><br />
                      <div class="well well-sm">
                        <b><?=gettext('Do not use the Synchronize Config to IP and password option on backup cluster members!') ?></b>
                      </div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizeusers" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Users and Groups') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizeusers" value="on" <?=!empty($pconfig['synchronizeusers']) ? "checked=\"checked\"" : "";?> />
                    <div class="hidden" for="help_for_synchronizeusers">
                      <?=gettext('Automatically sync the users and groups over to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizeauthservers" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Auth Servers') ?></td>
                  <td>
                    <input type="checkbox" name='synchronizeauthservers' value="on" <?=!empty($pconfig['synchronizeauthservers']) ? "checked=\"checked\"" : "";?> />
                    <div class="hidden" for="help_for_synchronizeauthservers">
                      <?=gettext('Automatically sync the authentication servers (e.g. LDAP, RADIUS) over to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizecerts" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Certificates') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizecerts" value="on" <?=!empty($pconfig['synchronizecerts']) ? "checked=\"checked\"" : "";?> />
                    <div class="hidden" for="help_for_synchronizecerts">
                      <?=gettext('Automatically sync the Certificate Authorities, Certificates, and Certificate Revocation Lists over to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizerules" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Firewall Rules') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizerules" value="on" <?=!empty($pconfig['synchronizerules']) ? "checked=\"checked\"" : "";?> />
                    <div class="hidden" for="help_for_synchronizerules">
                      <?=gettext('Automatically sync the firewall rules to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizeschedules" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Firewall Schedules') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizeschedules" value="on" <?=!empty($pconfig['synchronizeschedules']) ? "checked=\"checked\"" :"";?> />
                    <div class="hidden" for="help_for_synchronizeschedules">
                      <?=gettext('Automatically sync the firewall schedules to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizealiases" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Aliases') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizealiases" value="on" <?=!empty($pconfig['synchronizealiases']) ? "checked=\"checked\"" : "";?>/>
                    <div class="hidden" for="help_for_synchronizealiases">
                      <?=gettext('Automatically sync the aliases over to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizenat" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('NAT') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizenat" value="on" <?=!empty($pconfig['synchronizenat']) ? "checked=\"checked\"" :"";?> />
                    <div class="hidden" for="help_for_synchronizenat">
                      <?=gettext('Automatically sync the NAT rules over to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizeipsec" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('IPsec') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizeipsec" value="on" <?=!empty($pconfig['synchronizeipsec']) ? "checked=\"checked\"" : "";?> />
                    <div class="hidden" for="help_for_synchronizeipsec">
                      <?=gettext('Automatically sync the IPsec configuration to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizeopenvpn" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('OpenVPN') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizeopenvpn" value="on" <?=!empty($pconfig['synchronizeopenvpn']) ? "checked=\"checked\"" :"";?> />
                    <div class="hidden" for="help_for_synchronizeopenvpn">
                      <?=gettext('Automatically sync the OpenVPN configuration to the other HA host when changes are made.') ?>
                      <div class="well well-sm"><b><?=gettext('Using this option implies "Synchronize Certificates" as they are required for OpenVPN.') ?></b></div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizedhcpd" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('DHCPD') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizedhcpd" value="on" <?=!empty($pconfig['synchronizedhcpd']) ? "checked=\"checked\"" : "";?> />
                    <div class="hidden" for="help_for_synchronizedhcpd">
                      <?=gettext('Automatically sync the DHCP Server settings over to the other HA host when changes are made. This only applies to DHCP for IPv4.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizewol" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Wake on LAN') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizewol" value="on" <?=!empty($pconfig['synchronizewol']) ? "checked=\"checked\"" : "";?> />
                    <div class="hidden" for="help_for_synchronizewol">
                      <?=gettext('Automatically sync the WoL configuration to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizestaticroutes" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Static Routes') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizestaticroutes" value="on" <?=!empty($pconfig['synchronizestaticroutes']) ? "checked=\"checked\"" :"";?> />
                    <div class="hidden" for="help_for_synchronizestaticroutes">
                      <?=gettext('Automatically sync the Static Route configuration to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizelb" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Load Balancer') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizelb" value="on" <?=!empty($pconfig['synchronizelb']) ? "checked=\"checked\"" :"";?> />
                    <div class="hidden" for="help_for_synchronizelb">
                      <?=gettext('Automatically sync the Load Balancer configuration to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizevirtualip" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Virtual IPs') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizevirtualip" value="on" <?=!empty($pconfig['synchronizevirtualip']) ? "checked=\"checked\"" : "";?> />
                    <div class="hidden" for="help_for_synchronizevirtualip">
                      <?=gettext('Automatically sync the CARP Virtual IPs to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizednsforwarder" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('DNS Forwarder') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizednsforwarder" value="on" <?=!empty($pconfig['synchronizednsforwarder']) ? "checked=\"checked\"" :"";?> />
                    <div class="hidden" for="help_for_synchronizednsforwarder">
                      <?=gettext('Automatically sync the DNS Forwarder configuration to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizednsresolver" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('DNS Resolver') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizednsresolver" value="on" <?=!empty($pconfig['synchronizednsresolver']) ? "checked=\"checked\"" :"";?> />
                    <div class="hidden" for="help_for_synchronizednsresolver">
                      <?=gettext('Automatically sync the DNS Resolver configuration to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizeshaper" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Traffic Shaper') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizeshaper" value="on" <?=!empty($pconfig['synchronizeshaper']) ? "checked=\"checked\"" :"";?> />
                    <div class="hidden" for="help_for_synchronizeshaper">
                      <?=gettext('Automatically sync the TrafficShaper configuration to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_synchronizecaptiveportal" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('Captive Portal') ?></td>
                  <td>
                    <input type="checkbox" name="synchronizecaptiveportal" value="on" <?=!empty($pconfig['synchronizecaptiveportal']) ? "checked=\"checked\"" :"";?> />
                    <div class="hidden" for="help_for_synchronizecaptiveportal">
                      <?=gettext('Automatically sync the Captive Portal configuration to the other HA host when changes are made.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td></td>
                  <td>
                    <input name="Submit" type="submit" class="btn btn-primary" value="Save" />
                    <input type="button" class="btn btn-default" value="<?=gettext("Cancel");?>" onclick="window.location.href='<?=(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/system_hasync.php');?>'" />
                  </td>
                </tr>
              </table>
            </form>
          </div>
        </div>
      </section>
    </div>
  </div>
</section>


<?php include("foot.inc");
