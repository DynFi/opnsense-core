<?php

/*
    Copyright (C) 2014-2016 Deciso B.V.
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
require_once("services.inc");
require_once("interfaces.inc");

function staticmapcmp($a, $b)
{
    return ipcmp($a['ipaddr'], $b['ipaddr']);
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // handle identifiers and action
    if (!empty($_GET['if']) && !empty($config['interfaces'][$_GET['if']])) {
        $if = $_GET['if'];
    } else {
        header("Location: services_dhcp.php");
        exit;
    }
    if (isset($if) && isset($_GET['id']) && !empty($config['dhcpd'][$if]['staticmap'][$_GET['id']])) {
        $id = $_GET['id'];
    }

    // read form data
    $pconfig = array();
    $config_copy_fieldnames = array('mac', 'cid', 'hostname', 'filename', 'rootpath', 'descr', 'arp_table_static_entry',
      'defaultleasetime', 'maxleasetime', 'gateway', 'domain', 'domainsearchlist', 'wins1', 'wins2', 'dns1', 'dns2', 'ddnsdomain',
      'ddnsdomainprimary', 'ddnsdomainkeyname', 'ddnsdomainkey', 'ddnsupdate', 'ntp1', 'ntp2', 'tftp', 'ipaddr',
      'winsserver', 'dnsserver');
    foreach ($config_copy_fieldnames as $fieldname) {
        if (isset($if) && isset($id) && isset($config['dhcpd'][$if]['staticmap'][$id][$fieldname])) {
            $pconfig[$fieldname] = $config['dhcpd'][$if]['staticmap'][$id][$fieldname];
        } elseif (isset($_GET[$fieldname])) {
            $pconfig[$fieldname] = $_GET[$fieldname];
        } else {
            $pconfig[$fieldname] = null;
        }
    }

    // handle array types
    if (isset($pconfig['winsserver'][0])) {
        $pconfig['wins1'] = $pconfig['winsserver'][0];
    }
    if (isset($pconfig['winsserver'][1])) {
        $pconfig['wins2'] = $pconfig['winsserver'][1];
    }
    if (isset($pconfig['dnsserver'][0])) {
        $pconfig['dns1'] = $pconfig['dnsserver'][0];
    }
    if (isset($pconfig['dnsserver'][1])) {
        $pconfig['dns2'] = $pconfig['dnsserver'][1];
    }
    if (isset($pconfig['ntpserver'][0])) {
        $pconfig['ntp1'] = $pconfig['ntpserver'][0];
    }
    if (isset($pconfig['ntpserver'][1])) {
        $pconfig['ntp2'] = $pconfig['ntpserver'][1];
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pconfig = $_POST;
    // handle identifiers and actions
    if (!empty($pconfig['if']) && !empty($config['interfaces'][$pconfig['if']])) {
        $if = $pconfig['if'];
    }
    if (!empty($config['dhcpd'][$if]['staticmap'][$pconfig['id']])) {
        $id = $pconfig['id'];
    }
    if (empty($config['dhcpd'])) {
        $config['dhcpd'] = array();
    }
    if (empty($config['dhcpd'][$if])) {
        $config['dhcpd'][$if] = array();
    }
    if (empty($config['dhcpd'][$if]['staticmap'])) {
        $config['dhcpd'][$if]['staticmap'] = array();
    }
    $a_maps = &$config['dhcpd'][$if]['staticmap'];
    $input_errors = array();

    /* input validation */
    $reqdfields = array();
    $reqdfieldsn = array();
    do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

    /* either MAC or Client-ID must be specified */
    if (empty($pconfig['mac']) && empty($pconfig['cid'])) {
        $input_errors[] = gettext("Either MAC address or Client identifier must be specified");
    }

    /* normalize MAC addresses - lowercase and convert Windows-ized hyphenated MACs to colon delimited */
    $pconfig['mac'] = strtolower(str_replace("-", ":", $pconfig['mac']));

    if (!empty($pconfig['hostname'])) {
        preg_match("/\-\$/", $pconfig['hostname'], $matches);
        if ($matches) {
            $input_errors[] = gettext("The hostname cannot end with a hyphen according to RFC952");
        }
        if (!is_hostname($pconfig['hostname'])) {
            $input_errors[] = gettext("The hostname can only contain the characters A-Z, 0-9 and '-'.");
        } elseif (strpos($pconfig['hostname'],'.')) {
            $input_errors[] = gettext("A valid hostname is specified, but the domain name part should be omitted");
        }
    }
    if (!empty($pconfig['ipaddr']) && !is_ipaddr($_POST['ipaddr'])) {
        $input_errors[] = gettext("A valid IP address must be specified.");
    }
    if (!empty($pconfig['mac']) && !is_macaddr($pconfig['mac'])) {
        $input_errors[] = gettext("A valid MAC address must be specified.");
    }
    if (isset($config['dhcpd'][$if]['staticarp']) && empty($pconfig['ipaddr'])) {
        $input_errors[] = gettext("Static ARP is enabled.  You must specify an IP address.");
    }

    /* check for overlaps */
    foreach ($a_maps as $mapent) {
        if (isset($id) && ($a_maps[$id] === $mapent)) {
            continue;
        }
        if ((($mapent['hostname'] == $pconfig['hostname']) && $mapent['hostname'])  ||
            (($mapent['mac'] == $pconfig['mac']) && $mapent['mac']) ||
            (($mapent['ipaddr'] == $pconfig['ipaddr']) && $mapent['ipaddr'] ) ||
            (($mapent['cid'] == $pconfig['cid']) && $mapent['cid'])) {
            $input_errors[] = gettext("This Hostname, IP, MAC address or Client identifier already exists.");
            break;
        }
    }

    /* make sure it's not within the dynamic subnet */
    if (!empty($pconfig['ipaddr'])) {
        $dynsubnet_start = ip2ulong($config['dhcpd'][$if]['range']['from']);
        $dynsubnet_end = ip2ulong($config['dhcpd'][$if]['range']['to']);
        if (ip2ulong($pconfig['ipaddr']) >= $dynsubnet_start && ip2ulong($pconfig['ipaddr']) <= $dynsubnet_end) {
            $input_errors[] = sprintf(gettext("The IP address must not be within the DHCP range for this interface."));
        }

        if (!empty($config['dhcpd'][$if]['pool'])) {
            foreach ($config['dhcpd'][$if]['pool'] as $pidx => $p) {
                if (is_inrange_v4($pconfig['ipaddr'], $p['range']['from'], $p['range']['to'])) {
                    $input_errors[] = gettext("The IP address must not be within the range configured on a DHCP pool for this interface.");
                    break;
                }
            }
        }

        $ifcfgip = get_interface_ip($if);
        $ifcfgsn = get_interface_subnet($if);
        $ifcfgdescr = convert_friendly_interface_to_friendly_descr($if);
        $lansubnet_start = ip2ulong(long2ip32(ip2long($ifcfgip) & gen_subnet_mask_long($ifcfgsn)));
        $lansubnet_end = ip2ulong(long2ip32(ip2long($ifcfgip) | (~gen_subnet_mask_long($ifcfgsn))));
        if (ip2ulong($pconfig['ipaddr']) < $lansubnet_start || ip2ulong($pconfig['ipaddr']) > $lansubnet_end) {
            $input_errors[] = sprintf(gettext("The IP address must lie in the %s subnet."),$ifcfgdescr);
        }
    }

    if (!empty($pconfig['gateway']) && !is_ipaddrv4($pconfig['gateway'])) {
        $input_errors[] = gettext("A valid IP address must be specified for the gateway.");
    }
    if ((!empty($pconfig['wins1']) && !is_ipaddrv4($pconfig['wins1'])) ||
      (!empty($pconfig['wins2']) && !is_ipaddrv4($pconfig['wins2']))) {
        $input_errors[] = gettext("A valid IP address must be specified for the primary/secondary WINS servers.");
    }

    $parent_ip = get_interface_ip($pconfig['if']);
    if (is_ipaddrv4($parent_ip) && $pconfig['gateway']) {
        $parent_sn = get_interface_subnet($pconfig['if']);
        if (!ip_in_subnet($pconfig['gateway'], gen_subnet($parent_ip, $parent_sn) . "/" . $parent_sn) && !ip_in_interface_alias_subnet($pconfig['if'], $pconfig['gateway'])) {
            $input_errors[] = sprintf(gettext("The gateway address %s does not lie within the chosen interface's subnet."), $_POST['gateway']);
        }
    }
    if ((!empty($pconfig['dns1']) && !is_ipaddrv4($pconfig['dns1'])) || (!empty($pconfig['dns2']) && !is_ipaddrv4($pconfig['dns2']))) {
        $input_errors[] = gettext("A valid IP address must be specified for the primary/secondary DNS servers.");
    }

    if (!empty($pconfig['defaultleasetime']) && (!is_numeric($pconfig['defaultleasetime']) || ($pconfig['defaultleasetime'] < 60))) {
        $input_errors[] = gettext("The default lease time must be at least 60 seconds.");
    }
    if (!empty($pconfig['maxleasetime']) && (!is_numeric($pconfig['maxleasetime']) || ($pconfig['maxleasetime'] < 60) || ($pconfig['maxleasetime'] <= $pconfig['defaultleasetime']))) {
        $input_errors[] = gettext("The maximum lease time must be at least 60 seconds and higher than the default lease time.");
    }
    if (!empty($pconfig['ddnsdomain']) && !is_domain($pconfig['ddnsdomain'])) {
        $input_errors[] = gettext("A valid domain name must be specified for the dynamic DNS registration.");
    }
    if (!empty($pconfig['ddnsdomain']) && !is_ipaddrv4($pconfig['ddnsdomainprimary'])) {
        $input_errors[] = gettext("A valid primary domain name server IP address must be specified for the dynamic domain name.");
    }
    if ((!empty($pconfig['ddnsdomainkey']) && empty($pconfig['ddnsdomainkeyname'])) ||
      (!empty($pconfig['ddnsdomainkeyname']) && empty($pconfig['ddnsdomainkey']))) {
        $input_errors[] = gettext("You must specify both a valid domain key and key name.");
    }
    if (!empty($pconfig['domainsearchlist'])) {
        $domain_array=preg_split("/[ ;]+/", $pconfig['domainsearchlist']);
        foreach ($domain_array as $curdomain) {
            if (!is_domain($curdomain)) {
                $input_errors[] = gettext("A valid domain search list must be specified.");
                break;
            }
        }
    }

    if ((!empty($pconfig['ntp1']) && !is_ipaddrv4($pconfig['ntp1'])) || (!empty($pconfig['ntp2']) && !is_ipaddrv4($pconfig['ntp2']))) {
        $input_errors[] = gettext("A valid IP address must be specified for the primary/secondary NTP servers.");
    }
    if (!empty($pconfig['tftp']) && !is_ipaddrv4($pconfig['tftp']) && !is_domain($pconfig['tftp']) && !is_URL($pconfig['tftp'])) {
        $input_errors[] = gettext("A valid IP address or hostname must be specified for the TFTP server.");
    }
    if ((!empty($pconfig['nextserver']) && !is_ipaddrv4($pconfig['nextserver']))) {
        $input_errors[] = gettext("A valid IP address must be specified for the network boot server.");
    }

    if (count($input_errors) == 0){
        $mapent = array();
        $config_copy_fieldnames = array('mac', 'cid', 'ipaddr', 'hostname', 'descr', 'filename', 'rootpath',
          'arp_table_static_entry', 'defaultleasetime', 'maxleasetime', 'gateway', 'domain', 'domainsearchlist',
          'ddnsdomain', 'ddnsdomainprimary', 'ddnsdomainkeyname', 'ddnsdomainkey', 'ddnsupdate', 'tftp',
          'ldap', 'winsserver', 'dnsserver');

        foreach ($config_copy_fieldnames as $fieldname) {
            if (!empty($pconfig[$fieldname])) {
                $mapent[$fieldname] = $pconfig[$fieldname];
            }
        }
        // boolean
        $mapent['arp_table_static_entry'] = !empty($mapent['arp_table_static_entry']);
        $mapent['ddnsupdate'] = !empty($pconfig['ddnsupdate']) ? true : false;

        // arrays
        $mapent['winsserver'] = array();
        if (!empty($pconfig['wins1'])) {
            $mapent['winsserver'][] = $pconfig['wins1'];
        }
        if (!empty($pconfig['wins2'])) {
            $mapent['winsserver'][] = $pconfig['wins2'];
        }

        $mapent['dnsserver'] = array();
        if (!empty($pconfig['dns1'])) {
            $mapent['dnsserver'][] = $_POST['dns1'];
        }
        if (!empty($pconfig['dns2'])) {
            $mapent['dnsserver'][] = $_POST['dns2'];
        }

        $mapent['ntpserver'] = array();
        if (!empty($pconfig['ntp1'])) {
            $mapent['ntpserver'][] = $pconfig['ntp1'];
        }
        if (!empty($pconfig['ntp2'])) {
            $mapent['ntpserver'][] = $pconfig['ntp2'];
        }

        if (isset($id)) {
            $a_maps[$id] = $mapent;
        } else {
            $a_maps[] = $mapent;
        }
        // sort before save
        usort($config['dhcpd'][$if]['staticmap'], "staticmapcmp");

        write_config();

        if (isset($config['dhcpd'][$if]['enable'])) {
          mark_subsystem_dirty('staticmaps');
          if (isset($config['dnsmasq']['enable']) && isset($config['dnsmasq']['regdhcpstatic']))
            mark_subsystem_dirty('hosts');
          if (isset($config['unbound']['enable']) && isset($config['unbound']['regdhcpstatic']))
            mark_subsystem_dirty('unbound');
        }

        header("Location: services_dhcp.php?if={$if}");
        exit;
    }
}



$service_hook = 'dhcpd';
legacy_html_escape_form_data($pconfig);
include("head.inc");
?>
<body>
<script type="text/javascript">
//<![CDATA[
  function show_ddns_config() {
    $("#showddnsbox").hide();
    $("#showddns").show();
  }

  function show_ntp_config() {
    $("#showntpbox").hide();
    $("#showntp").show();
  }

  function show_tftp_config() {
    $("#showtftpbox").hide();
    $("#showtftp").show();
  }
//]]>
</script>
<?php include("fbegin.inc"); ?>
<section class="page-content-main">
  <div class="container-fluid">
    <div class="row">
      <?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
      <section class="col-xs-12">
        <div class="content-box">
          <form method="post" name="iform" id="iform">
            <div class="table-responsive">
              <table class="table table-striped">
                <tr>
                  <td width="22%" valign="top"><strong><?=gettext("Static DHCP Mapping");?></strong></td>
                  <td width="78%" align="right">
                    <small><?=gettext("full help"); ?> </small>
                    <i class="fa fa-toggle-off text-danger"  style="cursor: pointer;" id="show_all_help_page" type="button"></i>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_mac" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("MAC address");?></td>
                  <td>
                    <input name="mac" id="mac" type="text" value="<?=$pconfig['mac'];?>" />
<?php
                    $ip = getenv('REMOTE_ADDR');
                    $mac = `/usr/sbin/arp -an | grep {$ip} | /usr/bin/head -n1 | /usr/bin/cut -d" " -f4`;
                    $mac = str_replace("\n","",$mac);?>
                    <a onclick="$('#mac').val('<?=$mac?>');" href="#"><?=gettext("Copy my MAC address");?></a>
                    <div class="hidden" for="help_for_mac">
                      <?=gettext("Enter a MAC address in the following format: "."xx:xx:xx:xx:xx:xx");?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("Client identifier");?></td>
                  <td>
                    <input name="cid" type="text" value="<?=$pconfig['cid'];?>" />
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_ipaddr" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("IP address");?></td>
                  <td>
                    <input name="ipaddr" type="text" value="<?=$pconfig['ipaddr'];?>" />
                    <div class="hidden" for="help_for_ipaddr">
                      <?=gettext("If an IPv4 address is entered, the address must be outside of the pool.");?>
                      <br />
                      <?=gettext("If no IPv4 address is given, one will be dynamically allocated from the pool.");?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_hostname" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Hostname");?></td>
                  <td>
                    <input name="hostname" type="text" value="<?=$pconfig['hostname'];?>" />
                    <div class="hidden" for="help_for_hostname">
                      <?=gettext("Name of the host, without domain part.");?>
                    </div>
                  </td>
                </tr>
<?php
                if (isset($config['dhcpd'][$if]['netboot'])):?>
                <tr>
                  <td><a id="help_for_filename" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?= gettext('Netboot Filename') ?></td>
                  <td>
                    <input name="filename" type="text" id="filename" size="20" value="<?=$pconfig['filename'];?>" />
                    <div class="hidden" for="help_for_filename">
                      <?= gettext('Name of the file that should be loaded when this host boots off of the network, overrides setting on main page.') ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_rootpath" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?= gettext('Root Path') ?></td>
                  <td>
                    <input name="rootpath" type="text" value="<?=$pconfig['rootpath'];?>" />
                    <div class="hidden" for="help_for_rootpath">
                      <?= gettext("Enter the root-path-string, overrides setting on main page.") ?>
                    </div>
                  </td>
                </tr>
<?php
                endif;?>
                <tr>
                  <td><a id="help_for_descr" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Description");?></td>
                  <td>
                    <input name="descr" type="text" value="<?=$pconfig['descr'];?>" />
                    <div class="hidden" for="help_for_descr">
                      <?=gettext("You may enter a description here ". "for your reference (not parsed).");?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_arp_table_static_entry" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("ARP Table Static Entry");?></td>
                  <td>
                    <input name="arp_table_static_entry" id="arp_table_static_entry" type="checkbox" value="yes" <?=!empty($pconfig['arp_table_static_entry']) ? "checked=\"checked\"" : ""; ?> />
                    <div class="hidden" for="help_for_arp_table_static_entry">
                      <?=gettext('Create a static ARP table entry for this MAC and IP address pair.');?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("WINS servers");?></td>
                  <td>
                    <input name="wins1" type="text" value="<?=$pconfig['wins1'];?>" /><br />
                    <input name="wins2" type="text" value="<?=$pconfig['wins2'];?>" />
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_dns" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("DNS servers");?></td>
                  <td>
                    <input name="dns1" type="text" value="<?=$pconfig['dns1'];?>" /><br/>
                    <input name="dns2" type="text" value="<?=$pconfig['dns2'];?>" />
                    <div class="hidden" for="help_for_dns">
                      <?=gettext("NOTE: leave blank to use the system default DNS servers - this interface's IP if DNS forwarder is enabled, otherwise the servers configured on the General page.");?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_gateway" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Gateway");?></td>
                  <td>
                    <input name="gateway" type="text" value="<?=$pconfig['gateway'];?>" />
                    <div class="hidden" for="help_for_gateway">
                      <?=gettext("The default is to use the IP on this interface of the firewall as the gateway. Specify an alternate gateway here if this is not the correct gateway for your network.");?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_domain" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Domain name");?></td>
                  <td>
                    <input name="domain" type="text" value="<?=$pconfig['domain'];?>" />
                    <div class="hidden" for="help_for_domain">
                      <?=gettext("The default is to use the domain name of this system as the default domain name provided by DHCP. You may specify an alternate domain name here.");?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_domainsearchlist" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Domain search list");?></td>
                  <td>
                    <input name="domainsearchlist" type="text" id="domainsearchlist" size="20" value="<?=$pconfig['domainsearchlist'];?>" />
                    <div class="hidden" for="help_for_domainsearchlist">
                      <?=gettext("The DHCP server can optionally provide a domain search list. Use the semicolon character as separator ");?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_defaultleasetime" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Default lease time");?> (<?=gettext("seconds");?>)</td>
                  <td>
                    <input name="defaultleasetime" type="text" id="deftime" size="10" value="<?=$pconfig['defaultleasetime'];?>" />
                    <div class="hidden" for="help_for_defaultleasetime">
                      <?=gettext("This is used for clients that do not ask for a specific " ."expiration time."); ?><br />
                      <?=gettext("The default is 7200 seconds.");?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_maxleasetime" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Maximum lease time");?> (<?=gettext("seconds");?>)</td>
                  <td>
                    <input name="maxleasetime" type="text" value="<?=$pconfig['maxleasetime'];?>" />
                    <div class="hidden" for="help_for_maxleasetime">
                      <?=gettext("This is the maximum lease time for clients that ask"." for a specific expiration time."); ?><br />
                      <?=gettext("The default is 86400 seconds.");?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("Dynamic DNS");?></td>
                  <td>
                    <div id="showddnsbox">
                      <input type="button" onclick="show_ddns_config()" class="btn btn-xs btn-default" value="<?=gettext("Advanced");?>" /> - <?=gettext("Show Dynamic DNS");?>
                    </div>
                    <div id="showddns" style="display:none">
                      <input style="vertical-align:middle" type="checkbox" value="yes" name="ddnsupdate" id="ddnsupdate" <?=!empty($pconfig['ddnsupdate']) ? "checked=\"checked\"" : ""; ?> />
                      <b><?=gettext("Enable registration of DHCP client names in DNS.");?></b><br />
                      <?=gettext("Note: Leave blank to disable dynamic DNS registration.");?><br />
                      <?=gettext("Enter the dynamic DNS domain which will be used to register client names in the DNS server.");?>
                      <input name="ddnsdomain" type="text" id="ddnsdomain" size="20" value="<?=$pconfig['ddnsdomain'];?>" />
                      <?=gettext("Enter the primary domain name server IP address for the dynamic domain name.");?><br />
                      <input name="ddnsdomainprimary" type="text" id="ddnsdomainprimary" size="20" value="<?=$pconfig['ddnsdomainprimary'];?>" />
                      <?=gettext("Enter the dynamic DNS domain key name which will be used to register client names in the DNS server.");?>
                      <input name="ddnsdomainkeyname" type="text" id="ddnsdomainkeyname" size="20" value="<?=$pconfig['ddnsdomainkeyname'];?>" />
                      <?=gettext("Enter the dynamic DNS domain key secret which will be used to register client names in the DNS server.");?>
                      <input name="ddnsdomainkey" type="text" id="ddnsdomainkey" size="20" value="<?=$pconfig['ddnsdomainkey'];?>" />
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("NTP servers");?></td>
                  <td>
                    <div id="showntpbox">
                      <input type="button" onclick="show_ntp_config()" class="btn btn-xs btn-default" value="<?=gettext("Advanced");?>" /> - <?=gettext("Show NTP configuration");?>
                    </div>
                    <div id="showntp" style="display:none">
                      <input name="ntp1" type="text" id="ntp1" size="20" value="<?=$pconfig['ntp1'];?>" /><br />
                      <input name="ntp2" type="text" id="ntp2" size="20" value="<?=$pconfig['ntp2'];?>" />
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("TFTP server");?></td>
                  <td>
                    <div id="showtftpbox">
                      <input type="button" onclick="show_tftp_config()" class="btn btn-xs btn-default" value="<?=gettext("Advanced");?>" /> - <?=gettext("Show TFTP configuration");?>
                    </div>
                    <div id="showtftp" style="display:none">
                      <input name="tftp" type="text" id="tftp" size="50" value="<?=$pconfig['tftp'];?>" /><br />
                      <?=gettext("Leave blank to disable. Enter a full hostname or IP for the TFTP server.");?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td></td>
                  <td>
                    <input name="Submit" type="submit" class="formbtn btn btn-primary" value="<?=gettext("Save");?>" />
                    <input type="button" class="formbtn btn btn-default" value="<?=gettext("Cancel");?>" onclick="window.location.href='<?=(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/services_dhcp.php');?>'" />
<?php
                  if (isset($id)): ?>
                    <input name="id" type="hidden" value="<?=$id;?>" />
<?php
                  endif; ?>
                    <input name="if" type="hidden" value="<?=$if;?>" />
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
