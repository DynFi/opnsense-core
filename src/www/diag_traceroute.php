<?php

/*
    Copyright (C) 2016 Deciso B.V.
    Copyright (C) 2005 Paul Taylor (paultaylor@winndixie.com) and Manuel Kasper <mk@neon1.net>.
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

define('MAX_TTL', 64);

$cmd_output = false;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // set form defaults
    $pconfig = array();
    $pconfig['ipproto'] = 'ipv4';
    $pconfig['sourceip'] = null;
    $pconfig['useicmp'] = null;
    $pconfig['resolve'] = null;
    $pconfig['ttl'] = isset($_GET['ttl']) ? $_GET['ttl'] : 18;
    $pconfig['host'] = isset($_GET['host']) ? $_GET['host'] : null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // validate input / execute traceroute
    $input_errors = array();
    $pconfig = $_POST;

    /* input validation */
    $reqdfields = explode(" ", "host ttl");
    $reqdfieldsn = array(gettext("Host"),gettext("ttl"));
    do_input_validation($pconfig, $reqdfields, $reqdfieldsn, $input_errors);

    if (!is_numeric($pconfig['ttl']) || $pconfig['ttl'] < 1 || $pconfig['ttl'] > MAX_TTL) {
        $input_errors[] = sprintf(gettext("Maximum number of hops must be between 1 and %s"), MAX_TTL);
    }
    $host = trim($pconfig['host']);
    $ipproto = $pconfig['ipproto'];
    if ($pconfig['ipproto'] == "ipv4" && is_ipaddrv6($host)) {
        $input_errors[] = gettext("When using IPv4, the target host must be an IPv4 address or hostname.");
    } elseif ($pconfig['ipproto'] == "ipv6" && is_ipaddrv4($host)) {
        $input_errors[] = gettext("When using IPv6, the target host must be an IPv6 address or hostname.");
    }
    if (count($input_errors) == 0) {
        $cmd_args = "-w 2";
        $cmd_args .= !empty($pconfig['useicmp']) ? " -I " : "";
        $cmd_args .= !empty($pconfig['resolve']) ? "" : " -n ";
        $cmd_args .= " -m " . escapeshellarg($pconfig['ttl']);

        $command = "/usr/sbin/traceroute";
        if ($pconfig['ipproto'] == "ipv6") {
            $command .= "6";
            $ifaddr = is_ipaddr($pconfig['sourceip']) ? $pconfig['sourceip'] : get_interface_ipv6($pconfig['sourceip']);
        } else {
            $ifaddr = is_ipaddr($pconfig['sourceip']) ? $pconfig['sourceip'] : get_interface_ip($pconfig['sourceip']);
        }

        if ($ifaddr && (is_ipaddr($host) || is_hostname($host))) {
            $cmd_args .= " -s " . escapeshellarg($ifaddr) . " ";
        }

        $cmd_action = "{$command} {$cmd_args} " . " " . escapeshellarg($host);
        $process = proc_open($cmd_action, array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w")), $pipes);
        if (is_resource($process)) {
             $cmd_output = stream_get_contents($pipes[2]);
             $cmd_output .= stream_get_contents($pipes[1]);
        }
    }
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
        <?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
        <div class="content-box">
          <header class="content-box-head container-fluid">
            <h3><?=gettext("Traceroute");?></h3>
          </header>
          <div class="content-box-main ">
            <form method="post" name="iform" id="iform">
              <div class="table-responsive">
                <table class="table table-striped">
                  <tbody>
                    <tr>
                      <td><?=gettext("Host"); ?></td>
                      <td><input name="host" type="text" class="form-control" id="host" value="<?=$pconfig['host'];?>" /></td>
                    </tr>
                    <tr>
                      <td><?=gettext("IP Protocol"); ?></td>
                      <td>
                        <select name="ipproto" class="form-control">
                          <option value="ipv4" <?=($pconfig['ipproto'] == "ipv4") ? "selected=\"selected\"" : "";?>><?= gettext('IPv4') ?></option>
                          <option value="ipv6" <?=($pconfig['ipproto'] == "ipv6") ? "selected=\"selected\"" : "";?>><?= gettext('IPv6') ?></option>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td><?=gettext("Source Address"); ?></td>
                      <td>
                        <select name="sourceip" class="selectpicker">
                          <option value=""><?= gettext('Any') ?></option>
<?php
                        foreach (get_possible_traffic_source_addresses(true) as $sip):?>
                          <option value="<?=$sip['value'];?>" <?=!link_interface_to_bridge($sip['value']) && ($sip['value'] == $pconfig['sourceip']) ? "selected=\"selected\"" : "";?>>
                            <?=htmlspecialchars($sip['name']);?>
                          </option>
<?php
                        endforeach; ?>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td><?=gettext("Maximum number of hops");?></td>
                      <td>
                        <select name="ttl" class="form-control" id="ttl">
<?php
                        for ($i = 1; $i <= MAX_TTL; $i++): ?>
                          <option value="<?=$i;?>" <?= $i == $pconfig['ttl'] ? "selected=\"selected\"" : "" ;?>>
                            <?=$i;?>
                          </option>
<?php
                        endfor; ?>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td><?=gettext("Reverse Address Lookup");?></td>
                      <td>
                        <input name="resolve" type="checkbox"<?=!empty($pconfig['resolve']) ? " checked=\"checked\"" : ""; ?> />
                      </td>
                    </tr>
                    <tr>
                      <td><?=gettext("Use ICMP");?></td>
                      <td>
                        <input name="useicmp" type="checkbox" <?=!empty($pconfig['useicmp']) ? " checked=\"checked\"" : ""; ?> />
                      </td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td>
                        <input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Traceroute"); ?>" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </form>
          </div>
        </div>
      </section>
<?php
      if ( $cmd_output !== false):?>
      <section class="col-xs-12">
        <div class="content-box">
          <header class="content-box-head container-fluid">
            <h3><?=gettext("Traceroute output"); ?></h3>
          </header>
          <div class="content-box-main col-xs-12">
            <pre><?=$cmd_output;?></pre>
          </div>
        </div>
      </section>
<?php
      endif;?>
    </div>
  </div>
</section>

<?php include("foot.inc"); ?>
