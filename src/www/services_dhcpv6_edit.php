<?php

/*
    Copyright (C) 2014-2016 Deciso B.V.
    Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
    Copyright (C) 2011 Seth Mos <seth.mos@dds.nl>.
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
require_once("services.inc");

function staticmapcmp($a, $b)
{
    return ipcmp($a['ipaddrv6'], $b['ipaddrv6']);
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // handle identifiers and action
    if (!empty($_GET['if']) && !empty($config['interfaces'][$_GET['if']])) {
        $if = $_GET['if'];
    } else {
        header("Location: services_dhcpv6.php");
        exit;
    }
    if (isset($if) && isset($_GET['id']) && !empty($config['dhcpdv6'][$if]['staticmap'][$_GET['id']])) {
        $id = $_GET['id'];
    }

    // read form data
    $pconfig = array();
    $config_copy_fieldnames = array('duid', 'hostname', 'ipaddrv6', 'filename' ,'rootpath' ,'descr');
    foreach ($config_copy_fieldnames as $fieldname) {
        if (isset($if) && isset($id) && isset($config['dhcpdv6'][$if]['staticmap'][$id][$fieldname])) {
            $pconfig[$fieldname] = $config['dhcpdv6'][$if]['staticmap'][$id][$fieldname];
        } elseif (isset($_GET[$fieldname])) {
            $pconfig[$fieldname] = $_GET[$fieldname];
        } else {
            $pconfig[$fieldname] = null;
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_errors = array();
    $pconfig = $_POST;
    // handle identifiers and actions
    if (!empty($pconfig['if']) && !empty($config['interfaces'][$pconfig['if']])) {
        $if = $pconfig['if'];
    }
    if (!empty($config['dhcpdv6'][$if]['staticmap'][$pconfig['id']])) {
        $id = $pconfig['id'];
    }
    if (empty($config['dhcpdv6']) || !is_array($config['dhcpdv6'])) {
        $config['dhcpdv6'] = array();
    }
    if (empty($config['dhcpdv6'][$if]) || !is_array($config['dhcpdv6'][$if])) {
        $config['dhcpdv6'][$if] = array();
    }
    if (empty($config['dhcpdv6'][$if]['staticmap']) || !is_array($config['dhcpdv6'][$if]['staticmap'])) {
        $config['dhcpdv6'][$if]['staticmap'] = array();
    }
    /* input validation */
    $reqdfields = explode(" ", "duid");
    $reqdfieldsn = array(gettext("DUID Identifier"));

    do_input_validation($pconfig, $reqdfields, $reqdfieldsn, $input_errors);

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
    if (!empty($pconfig['ipaddrv6']) && !is_ipaddrv6($pconfig['ipaddrv6'])) {
        $input_errors[] = gettext("A valid IPv6 address must be specified.");
    }
    if (empty($pconfig['duid'])) {
        $input_errors[] = gettext("A valid DUID Identifier must be specified.");
    }

    /* check for overlaps */
    $a_maps = &$config['dhcpdv6'][$if]['staticmap'];
    foreach ($a_maps as $mapent) {
        if (isset($id) && ($a_maps[$id] === $mapent)) {
            continue;
        }
        if ((($mapent['hostname'] == $pconfig['hostname']) && $mapent['hostname'])  || ($mapent['duid'] == $pconfig['duid'])) {
            $input_errors[] = gettext("This Hostname, IP or DUID Identifier already exists.");
            break;
        }
    }
    if (count($input_errors) == 0) {
        $mapent = array();
        $config_copy_fieldnames = array('duid', 'ipaddrv6', 'hostname', 'descr', 'filename', 'rootpath');
        foreach ($config_copy_fieldnames as $fieldname) {
            if (!empty($pconfig[$fieldname])) {
                $mapent[$fieldname] = $pconfig[$fieldname];
            }
        }

        if (isset($id)) {
            $config['dhcpdv6'][$if]['staticmap'][$id] = $mapent;
        } else {
            $config['dhcpdv6'][$if]['staticmap'][] = $mapent;
        }

        usort($config['dhcpdv6'][$if]['staticmap'], "staticmapcmp");

        write_config();

        if (isset($config['dhcpdv6'][$if]['enable'])) {
            mark_subsystem_dirty('staticmaps');
            if (isset($config['dnsmasq']['enable']) && isset($config['dnsmasq']['regdhcpstatic'])) {
                mark_subsystem_dirty('hosts');
            }
            if (isset($config['unbound']['enable']) && isset($config['unbound']['regdhcpstatic'])) {
                mark_subsystem_dirty('unbound');
            }
        }

        header("Location: services_dhcpv6.php?if={$if}");
        exit;
    }
}


$service_hook = 'dhcpd';
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
                    <td width="22%" valign="top"><strong><?=gettext("Static DHCPv6 Mapping");?></strong></td>
                    <td width="78%" align="right">
                      <small><?=gettext("full help"); ?> </small>
                      <i class="fa fa-toggle-off text-danger"  style="cursor: pointer;" id="show_all_help_page" type="button"></i>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_duid" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("DUID Identifier");?></td>
                    <td>
                      <input name="duid" type="text" value="<?=$pconfig['duid'];?>" />
                      <div class="hidden" for="help_for_duid">
                        <?=gettext("Enter a DUID Identifier in the following format: ");?><br />
                        "<?= gettext('DUID-LLT - ETH -- TIME --- ---- ADDR ----') ?>" <br />
                        "xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx"
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_ipaddrv6" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("IPv6 address");?></td>
                    <td>
                      <input name="ipaddrv6" type="text" value="<?=$pconfig['ipaddrv6'];?>" />
                      <div class="hidden" for="help_for_ipaddrv6">
                        <?=gettext("If an IPv6 address is entered, the address must be outside of the pool.");?>
                        <br />
                        <?=gettext("If no IPv6 address is given, one will be dynamically allocated from the pool.");?>
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
<?php if (isset($config['dhcpdv6'][$if]['netboot'])): ?>
                  <tr>
                    <td><a id="help_for_filename" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?= gettext('Netboot filename') ?></td>
                    <td>
                      <input name="filename" type="text" value="<?=$pconfig['filename'];?>" />
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
                        <?= gettext('Enter the root-path-string, overrides setting on main page.') ?>
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
                        <?=gettext("You may enter a description here "."for your reference (not parsed).");?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td></td>
                    <td>
                      <input name="Submit" type="submit" class="formbtn btn btn-primary" value="<?=gettext("Save");?>" />
                      <input type="button" class="formbtn btn btn-default" value="<?=gettext("Cancel");?>" onclick="window.location.href='<?=(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/services_dhcpv6.php');?>'" />
                      <?php if (isset($id)): ?>
                      <input name="id" type="hidden" value="<?=$id;?>" />
                      <?php endif; ?>
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
