<?php

/*
    Copyright (C) 2016 Deciso B.V.
    Copyright (C) 2013 Jim Pingle <jimp@pfsense.org>
    Copyright (C) 2003-2005 Bob Zoller <bob@kludgebox.com>
    Copyright (C) 2003-2005 Manuel Kasper <mk@neon1.net>
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
require_once("interfaces.inc");

$cmd_output = false;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // set form defaults
    $pconfig = array();
    $pconfig['ipprotocol'] = 'ipv4';
    $pconfig['host'] = null;
    $pconfig['port'] = null;
    $pconfig['showtext'] = null;
    $pconfig['sourceip'] = null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pconfig = $_POST;
    $input_errors = array();

    /* input validation */
    $reqdfields = explode(" ", "host port");
    $reqdfieldsn = array(gettext("Host"),gettext("Port"));
    do_input_validation($pconfig, $reqdfields, $reqdfieldsn, $input_errors);

    if (!is_ipaddr($pconfig['host']) && !is_hostname($pconfig['host'])) {
        $input_errors[] = gettext("Please enter a valid IP or hostname.");
    }

    if (!is_port($pconfig['port'])) {
        $input_errors[] = gettext("Please enter a valid port number.");
    }

    if (($pconfig['srcport'] != "") && (!is_numeric($pconfig['srcport']) || !is_port($pconfig['srcport']))) {
        $input_errors[] = gettext("Please enter a valid source port number, or leave the field blank.");
    }

    if (is_ipaddrv4($pconfig['host']) && ($pconfig['ipprotocol'] == "ipv6")) {
        $input_errors[] = gettext("You cannot connect to an IPv4 address using IPv6.");
    }
    if (is_ipaddrv6($pconfig['host']) && ($pconfig['ipprotocol'] == "ipv4")) {
        $input_errors[] = gettext("You cannot connect to an IPv6 address using IPv4.");
    }

    if (count($input_errors) == 0) {
        $nc_args = "-w 10" ;
        if (empty($pconfig['showtext'])) {
            $nc_args .= " -z ";
        }
        if (!empty($pconfig['srcport'])) {
            $nc_args .= " -p " . escapeshellarg($pconfig['srcport']) . " ";
        }
        switch ($pconfig['ipprotocol']) {
            case "ipv4":
                $ifaddr = ($pconfig['sourceip'] == "any") ? "" : get_interface_ip($pconfig['sourceip']);
                $nc_args .= " -4";
                break;
            case "ipv6":
                $ifaddr = (is_linklocal($pconfig['sourceip']) ? $pconfig['sourceip'] : get_interface_ipv6($pconfig['sourceip']));
                $nc_args .= " -6";
                break;
        }
        if (!empty($ifaddr)) {
            $nc_args .= " -s " . escapeshellarg($ifaddr) . " ";
            $scope = get_ll_scope($ifaddr);
            if (!empty($scope) && !strstr($host, "%")) {
                $host .= "%{$scope}";
            }
        }

        $cmd_action = "/usr/bin/nc {$nc_args} " . escapeshellarg($pconfig['host']) . " " . escapeshellarg($pconfig['port']) . " 2>&1";
        $process = proc_open($cmd_action, array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w")), $pipes);
        if (is_resource($process)) {
             $cmd_output = stream_get_contents($pipes[1]);
             $cmd_output .= stream_get_contents($pipes[2]);
        }
    }
}

legacy_html_escape_form_data($pconfig);
include("head.inc"); ?>
<body>
<?php include("fbegin.inc"); ?>

<section class="page-content-main">
  <div class="container-fluid">
    <div class="row">
      <section class="col-xs-12">
        <div id="message" style="" class="alert alert-warning" role="alert">
          <?= gettext('This page allows you to perform a simple TCP connection test to determine if a host is up and accepting connections on a given port. This test does not function for UDP since there is no way to reliably determine if a UDP port accepts connections in this manner.') ?>
          <br /><br />
          <?= gettext('No data is transmitted to the remote host during this test, it will only attempt to open a connection and optionally display the data sent back from the server.') ?>
        </div>
        <div class="content-box">
          <div class="content-box-main ">
            <?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
            <form method="post" name="iform" id="iform">
              <div class="table-responsive">
                <table class="table table-striped opnsense_standard_table_form">
                  <thead>
                    <tr>
                      <td style="width:22%"><strong><?=gettext("Test Port"); ?></strong></td>
                      <td style="width:78%; text-align:right">
                        <small><?=gettext("full help"); ?> </small>
                        <i class="fa fa-toggle-off text-danger"  style="cursor: pointer;" id="show_all_help_page"></i>
                        &nbsp;
                      </td>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><a id="help_for_ipprotocol" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("IP Protocol"); ?></td>
                      <td>
                        <select name="ipprotocol" class="selectpicker">
                          <option value="ipv4" <?= $pconfig['ipprotocol'] == "ipv4" ? "selected=\"selected\"" : ""; ?>>
                            <?=gettext("IPv4");?>
                          </option>
                          <option value="ipv6" <?= $pconfig['ipprotocol'] == "ipv6" ? "selected=\"selected\"" : ""; ?>>
                            <?=gettext("IPv6");?>
                          </option>
                        </select>
                        <div class="hidden" data-for="help_for_ipprotocol">
                          <?=gettext("If you force IPv4 or IPv6 and use a hostname that does not contain a result using that protocol, it will result in an error. For example if you force IPv4 and use a hostname that only returns an AAAA IPv6 IP address, it will not work."); ?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("Host"); ?></td>
                      <td>
                        <input name="host" type="text" value="<?=$pconfig['host'];?>" />
                      </td>
                    </tr>
                    <tr>
                      <td><i class="fa fa-info-circle text-muted"></i> <?= gettext("Port"); ?></td>
                      <td>
                        <input name="port" type="text" value="<?=$pconfig['port'];?>" />
                      </td>
                    </tr>
                    <tr>
                      <td><a id="help_for_srcport" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?= gettext("Source Port"); ?></td>
                      <td>
                        <input name="srcport" type="text" value="<?=$pconfig['srcport'];?>" />
                        <div class="hidden" data-for="help_for_srcport">
                          <?=gettext("This should typically be left blank."); ?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><a id="help_for_showtext" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?= gettext("Show Remote Text"); ?></td>
                      <td>
                        <input name="showtext" type="checkbox" id="showtext" <?= !empty($pconfig['showtext']) ? "checked=\"checked\"" : "";?> />
                        <div class="hidden" data-for="help_for_showtext">
                          <?=gettext("Shows the text given by the server when connecting to the port. Will take 10+ seconds to display if checked."); ?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("Source Address"); ?></td>
                      <td>
                          <select name="sourceip" class="selectpicker" data-size="5" data-live-search="true">
                            <option value=""><?= gettext('Any') ?></option>
<?php foreach (get_possible_listen_ips() as $sip): ?>
                            <option value="<?=$sip['value'];?>" <?=!link_interface_to_bridge($sip['value']) && ($sip['value'] == $sourceip) ? "selected=\"selected\"" : "";?>>
                              <?=htmlspecialchars($sip['name']);?>
                            </option>
<?php endforeach ?>
                          </select>
                        </td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td><input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Test"); ?>" /></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </form>
          </div>
        </div>
      </section>
<?php
      if ($cmd_output !== false): ?>
      <section class="col-xs-12">
        <div class="content-box">
          <header class="content-box-head container-fluid">
            <h3><?=gettext("Port Test Results"); ?></h3>
          </header>
          <div class="content-box-main col-xs-12">
<?php
            if (empty($cmd_output) && !empty($pconfig['showtext'])):?>
            <pre><?= gettext("No output received, or connection failed. Try with \"Show Remote Text\" unchecked first.");?></pre>
<?php
            elseif (empty($cmd_output)):?>
            <pre><?=gettext("Connection failed (Refused/Timeout)");?></pre>
<?php
            else:?>
            <pre><?=$cmd_output;?></pre>
<?php
            endif;?>

          </div>
        </div>
      </section>
<?php
      endif;?>
    </div>
  </div>
</section>
<?php include('foot.inc'); ?>
