<?php

/*
    Copyright (C) 2014-2016 Deciso B.V.
    Copyright (C) 2003-2005 Bob Zoller <bob@kludgebox.com> and Manuel Kasper <mk@neon1.net>.
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
require_once("system.inc");
require_once("services.inc");
require_once("interfaces.inc");
require_once("pfsense-utils.inc");

$referer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/services_dnsmasq.php');

if (empty($config['dnsmasq']['domainoverrides']) || !is_array($config['dnsmasq']['domainoverrides'])) {
    $config['dnsmasq']['domainoverrides'] = array();
}
$a_domainOverrides = &$config['dnsmasq']['domainoverrides'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id']) && !empty($a_domainOverrides[$_GET['id']])) {
        $id = $_GET['id'];
    }
    $pconfig =  array();

    $pconfig['domain'] = isset($id) && !empty($a_domainOverrides[$id]['domain']) ? $a_domainOverrides[$id]['domain'] : null;
    $pconfig['descr'] = !empty($a_domainOverrides[$id]['descr']) ? $a_domainOverrides[$id]['descr'] : null;
    if (!isset($id) || empty($a_domainOverrides[$id]['ip'])) {
        $pconfig['ip'] = null;
        $pconfig['dnssrcip'] = null;
    } elseif (!empty($a_domainOverrides[$id]['ip']) && is_ipaddr($a_domainOverrides[$id]['ip']) && ($a_domainOverrides[$id]['ip'] != '#')) {
         $pconfig['ip'] = $a_domainOverrides[$id]['ip'];
         $pconfig['dnssrcip'] = null;
    } else {
        $dnsmasqpieces = explode('@', $a_domainOverrides[$id]['ip'], 2);
        $pconfig['ip'] = !empty($dnsmasqpieces[0]) ? $dnsmasqpieces[0] : null;
        $pconfig['dnssrcip'] = !empty($dnsmasqpieces[1]) ? $dnsmasqpieces[1] : null;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['id']) && !empty($a_domainOverrides[$_POST['id']])) {
        $id = $_POST['id'];
    }
    $input_errors= array();
    $pconfig = $_POST;

    /* input validation */
    $reqdfields = explode(" ", "domain ip");
    $reqdfieldsn = array(gettext("Domain"),gettext("IP address"));

    do_input_validation($pconfig, $reqdfields, $reqdfieldsn, $input_errors);

    if (!empty($pconfig['domain']) && substr($pconfig['domain'], 0, 6) == '_msdcs') {
        $subdomainstr = substr($pconfig['domain'], 7);
        if ($subdomainstr && !is_domain($subdomainstr)) {
            $input_errors[] = gettext("A valid domain must be specified after _msdcs.");
        }
    } elseif (!empty($pconfig['domain']) && !is_domain($_POST['domain'])) {
        $input_errors[] = gettext("A valid domain must be specified.");
    }
    if (!empty($pconfig['ip']) && !is_ipaddr($pconfig['ip']) && ($pconfig['ip'] != '#') && ($pconfig['ip'] != '!')) {
        $input_errors[] = gettext("A valid IP address must be specified, or # for an exclusion or ! to not forward at all.");
    }
    if (!empty($pconfig['dnssrcip']) && !in_array($pconfig['dnssrcip'], get_configured_ip_addresses())) {
        $input_errors[] = gettext("An interface IP address must be specified for the DNS query source.");
    }
    if (count($input_errors) == 0) {
        $doment = array();
        $doment['domain'] = $pconfig['domain'];
        if (empty($pconfig['dnssrcip'])) {
            $doment['ip'] = $pconfig['ip'];
        } else {
            $doment['ip'] = $pconfig['ip'] . "@" . $pconfig['dnssrcip'];
        }
        $doment['descr'] = $pconfig['descr'];

        if (isset($id)) {
            $a_domainOverrides[$id] = $doment;
        } else {
            $a_domainOverrides[] = $doment;
        }
        services_dnsmasq_configure();
        write_config();
        header("Location: services_dnsmasq.php");
        exit;
    }
}


$service_hook = 'dnsmasq';
legacy_html_escape_form_data($pconfig);
include("head.inc");
?>
<body>
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
                      <td width="22%"><strong><?=gettext("Edit Domain Override entry");?></strong></td>
                      <td width="78%" align="right">
                        <small><?=gettext("full help"); ?> </small>
                        <i class="fa fa-toggle-off text-danger"  style="cursor: pointer;" id="show_all_help_page" type="button"></i>
                      </td>
                    </tr>
                    <tr>
                      <td width="22%"><a id="help_for_domain" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Domain");?></td>
                      <td width="78%">
                        <input name="domain" type="text" value="<?=$pconfig['domain'];?>" />
                        <div class="hidden" for="help_for_domain">
                          <?=gettext("Domain to override (NOTE: this does not have to be a valid TLD!)"); ?><br />
                          <?=gettext("e.g."); ?> <em><?=gettext("test"); ?></em> <?=gettext("or"); ?> <em>mycompany.localdomain</em> <?=gettext("or"); ?> <em>1.168.192.in-addr.arpa</em>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><a id="help_for_ip" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("IP address");?></td>
                      <td>
                        <input name="ip" type="text" value="<?=$pconfig['ip'];?>" />
                        <div class="hidden" for="help_for_ip">
                          <?=gettext("IP address of the authoritative DNS server for this domain"); ?><br />
                          <?=gettext("e.g."); ?> <em>192.168.100.100</em><br /><?=gettext("Or enter # for an exclusion to pass through this host/subdomain to standard nameservers instead of a previous override."); ?><br /><?=gettext("Or enter ! for lookups for this host/subdomain to NOT be forwarded anywhere."); ?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><a id="help_for_dnssrcip" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Source IP");?></td>
                      <td>
                        <input name="dnssrcip" type="text" value="<?=$pconfig['dnssrcip'];?>" />
                        <div class="hidden" for="help_for_dnssrcip">
                          <?=gettext("Source IP address for queries to the DNS server for the override domain."); ?><br />
                          <?=gettext("Leave blank unless your DNS server is accessed through a VPN tunnel."); ?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><a id="help_for_descr" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Description");?></td>
                      <td>
                        <input name="descr" type="text" value="<?=$pconfig['descr'];?>" />
                        <div class="hidden" for="help_for_descr">
                          <?=gettext("You may enter a description here"." for your reference (not parsed).");?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td>
                        <input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Save");?>" />
                        <input type="button" class="btn btn-default" value="<?=gettext("Cancel");?>" onclick="window.location.href='<?=$referer;?>'" />
                        <?php if (isset($id)): ?>
                        <input name="id" type="hidden" value="<?=$id;?>" />
                        <?php endif; ?>
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
