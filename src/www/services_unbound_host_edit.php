<?php

/*
    Copyright (C) 2015 Manuel Faux <mfaux@conf.at>
    Copyright (C) 2014-2016 Deciso B.V.
    Copyright (C) 2014 Warren Baker <warren@decoy.co.za>
    Copyright (C) 2003-2004 Bob Zoller <bob@kludgebox.com> and Manuel Kasper <mk@neon1.net>.
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


function hostcmp($a, $b)
{
    return strcasecmp($a['host'], $b['host']);
}

if (empty($config['unbound']['hosts']) || !is_array($config['unbound']['hosts'])) {
    $config['unbound']['hosts'] = array();
}
$a_hosts = &$config['unbound']['hosts'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id']) && !empty($a_hosts[$_GET['id']])) {
        $id = $_GET['id'];
    }
    $pconfig = array();
    foreach (array('rr', 'host', 'domain', 'ip', 'mxprio', 'mx', 'descr') as $fieldname) {
        if (isset($id) && !empty($a_hosts[$id][$fieldname])) {
            $pconfig[$fieldname] = $a_hosts[$id][$fieldname];
        } else {
            $pconfig[$fieldname] = null;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && !empty($a_hosts[$_POST['id']])) {
        $id = $_POST['id'];
    }

    $input_errors = array();
    $pconfig = $_POST;

    /* input validation */
    $reqdfields = explode(" ", "domain rr");
    $reqdfieldsn = array(gettext("Domain"),gettext("Type"));

    do_input_validation($pconfig, $reqdfields, $reqdfieldsn, $input_errors);

    if (!empty($pconfig['host']) && !is_hostname($pconfig['host'])) {
        $input_errors[] = gettext("The hostname can only contain the characters A-Z, 0-9 and '-'.");
    }

    if (!empty($pconfig['domain']) && !is_domain($pconfig['domain'])) {
        $input_errors[] = gettext("A valid domain must be specified.");
    }

    switch ($pconfig['rr']) {
        case 'A': /* also: AAAA */
            $reqdfields = explode(" ", "ip");
            $reqdfieldsn = array(gettext("IP address"));
            do_input_validation($pconfig, $reqdfields, $reqdfieldsn, $input_errors);

            if (!empty($pconfig['ip']) && !is_ipaddr($pconfig['ip'])) {
                $input_errors[] = gettext("A valid IP address must be specified.");
            }
            break;
        case 'MX':
            $reqdfields = explode(" ", "mxprio mx");
            $reqdfieldsn = array(gettext("MX Priority"), gettext("MX Host"));
            do_input_validation($pconfig, $reqdfields, $reqdfieldsn, $input_errors);

            if (!empty($pconfig['mxprio']) && !is_numericint($pconfig['mxprio'])) {
                $input_errors[] = gettext("A valid MX priority must be specified.");
            }

            if (!empty($pconfig['mx']) && !is_domain($pconfig['mx'])) {
                $input_errors[] = gettext("A valid MX host must be specified.");
            }
            break;
        default:
            $input_errors[] = gettext("A valid resource record type must be specified.");
            break;
    }
    if (count($input_errors) == 0) {
        $hostent = array();
        $hostent['host'] = $pconfig['host'];
        $hostent['domain'] = $pconfig['domain'];
        /* Destinguish between A and AAAA by parsing the passed IP address */
        $hostent['rr'] = $pconfig['rr'] == "A" && is_ipaddrv6($pconfig['ip']) ? "AAA" : $pconfig['rr'];
        $hostent['ip'] = $pconfig['ip'];
        $hostent['mxprio'] = $pconfig['mxprio'];
        $hostent['mx'] = $pconfig['mx'];
        $hostent['descr'] = $pconfig['descr'];

        if (isset($id)) {
            $a_hosts[$id] = $hostent;
        } else {
            $a_hosts[] = $hostent;
        }

        usort($a_hosts, "hostcmp");
        mark_subsystem_dirty('unbound');
        write_config();
        header("Location: services_unbound_overrides.php");
        exit;
    }

}

$service_hook = 'unbound';
legacy_html_escape_form_data($pconfig);
include("head.inc");
?>

<script type="text/javascript">
  $( document ).ready(function() {
    $("#rr").change(function(){
      switch ($(this).val()) {
        case 'A':
          $('#ip').prop('disabled', false);
          $('#mxprio').prop('disabled', true);
          $('#mx').prop('disabled', true);
          break;
        case 'MX':
          $('#ip').prop('disabled', true);
          $('#mxprio').prop('disabled', false);
          $('#mx').prop('disabled', false);
          break;
        default:
          $('#ip').prop('disabled', false);
          $('#mxprio').prop('disabled', false);
          $('#mx').prop('disabled', false);
      }
    });
    // trigger initial change
    $("#rr").change();
  });
</script>
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
                    <td width="22%"><strong><?=gettext("Edit DNS Resolver entry");?></strong></td>
                    <td width="78%" align="right">
                      <small><?=gettext("full help"); ?> </small>
                      <i class="fa fa-toggle-off text-danger"  style="cursor: pointer;" id="show_all_help_page" type="button"></i>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_host" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Host");?></td>
                    <td>
                      <input name="host" type="text" value="<?=$pconfig['host'];?>" />
                      <div class="hidden" for="help_for_host">
                        <?=gettext("Name of the host, without domain part"); ?>
                        <?=gettext("e.g."); ?> <em><?=gettext("myhost"); ?></em>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_domain" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Domain");?></td>
                    <td>
                      <input name="domain" type="text" value="<?=$pconfig['domain'];?>" />
                      <div class="hidden" for="help_for_domain">
                        <?=gettext("Domain of the host"); ?><br />
                        <?=gettext("e.g."); ?> <em><?=gettext("example.com"); ?></em>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_rr" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Type");?></td>
                    <td>
                      <select name="rr" id="rr" class="selectpicker">
<?php
                       $rrs = array("A" => gettext("A or AAAA (IPv4 or IPv6 address)"), "MX" => gettext("MX (Mail server)"));
                       foreach ($rrs as $rr => $name) :?>
                        <option value="<?=$rr;?>" <?=($rr == $pconfig['rr'] || ($rr == 'A' && $pconfig['rr'] == 'AAAA')) ? "selected=\"selected\"" : "";?> >
                          <?=$name;?>
                        </option>
<?php
                        endforeach; ?>
                      </select>
                      <div class="hidden" for="help_for_rr">
                        <?=gettext("Type of resource record"); ?>
                        <br />
                        <?=gettext("e.g."); ?> <em>A</em> <?=gettext("or"); ?> <em>AAAA</em> <?=gettext("for IPv4 or IPv6 addresses"); ?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_ip" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("IP");?></td>
                    <td>
                      <input name="ip" type="text" id="ip" value="<?=$pconfig['ip'];?>" />
                      <div class="hidden" for="help_for_ip">
                        <?=gettext("IP address of the host"); ?><br />
                        <?=gettext("e.g."); ?> <em>192.168.100.100</em> <?=gettext("or"); ?> <em>fd00:abcd::1</em>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_mxprio" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("MX Priority");?></td>
                    <td>
                      <input name="mxprio" type="text" id="mxprio" value="<?=$pconfig['mxprio'];?>" />
                      <div class="hidden" for="help_for_mxprio">
                        <?=gettext("Priority of MX record"); ?><br />
                        <?=gettext("e.g."); ?> <em>10</em>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_mx" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("MX Host");?></td>
                    <td>
                      <input name="mx" type="text" id="mx" size="6" value="<?=$pconfig['mx'];?>" />
                      <div class="hidden" for="help_for_mx">
                        <?=gettext("Host name of MX host"); ?><br />
                        <?=gettext("e.g."); ?> <em>mail.example.com</em>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_descr" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Description");?></td>
                    <td>
                      <input name="descr" type="text" id="descr" value="<?=$pconfig['descr'];?>" />
                      <div class="hidden" for="help_for_descr">
                        <?=gettext("You may enter a description here for your reference (not parsed).");?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td>
                      <input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Save");?>" />
                      <input type="button" class="btn btn-default" value="<?=gettext("Cancel");?>" onclick="window.location.href='<?=(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/services_unbound_overrides.php');?>'" />
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
