<?php

/*
    Copyright (C) 2014-2015 Deciso B.V.
    Copyright (C) 2014 Electric Sheep Fencing, LLC
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
require_once("plugins.inc.d/ipsec.inc");
require_once("services.inc");
require_once("interfaces.inc");

if (!isset($config['ipsec']) || !is_array($config['ipsec'])) {
    $config['ipsec'] = array();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // fetch form data
    $pconfig  = array();
    $pconfig['noinstalllanspd'] = isset($config['system']['noinstalllanspd']);
    $pconfig['disablevpnrules'] = isset($config['system']['disablevpnrules']);
    $pconfig['preferoldsa_enable'] = isset($config['ipsec']['preferoldsa']);
    foreach ($ipsec_loglevels as $lkey => $ldescr) {
        if (!empty($config['ipsec']["ipsec_{$lkey}"])) {
            $pconfig["ipsec_{$lkey}"] = $config['ipsec']["ipsec_{$lkey}"];
        } else {
            $pconfig["ipsec_{$lkey}"] = null;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // save form data
    $pconfig = $_POST;
    if (isset($pconfig['noinstalllanspd']) && $pconfig['noinstalllanspd'] == "yes") {
        $config['system']['noinstalllanspd'] = true;
    } elseif (isset($config['system']['noinstalllanspd'])) {
        unset($config['system']['noinstalllanspd']);
    }
    if (!empty($pconfig['disablevpnrules'])) {
        $config['system']['disablevpnrules'] = true;
    }  elseif (isset($config['system']['disablevpnrules'])) {
        unset($config['system']['disablevpnrules']);
    }
    if (isset($pconfig['preferoldsa_enable']) && $pconfig['preferoldsa_enable'] == "yes") {
        $config['ipsec']['preferoldsa'] = true;
    } elseif (isset($config['ipsec']['preferoldsa'])) {
        unset($config['ipsec']['preferoldsa']);
    }
    if (isset($config['ipsec']) && is_array($config['ipsec'])) {
        foreach ($ipsec_loglevels as $lkey => $ldescr) {
            if (empty($_POST["ipsec_{$lkey}"])) {
                if (isset($config['ipsec']["ipsec_{$lkey}"])) {
                    unset($config['ipsec']["ipsec_{$lkey}"]);
                }
            } else {
                $config['ipsec']["ipsec_{$lkey}"] = $_POST["ipsec_{$lkey}"];
            }
        }
    }

    write_config();
    $savemsg = get_std_save_message();
    filter_configure();
    ipsec_configure_do();
}

$service_hook = 'ipsec';

include("head.inc");

?>

<body>
<?php include("fbegin.inc"); ?>
  <section class="page-content-main">
    <div class="container-fluid">
      <div class="row">

<?php
if (isset($savemsg)) {
    print_info_box($savemsg);
}
if (isset($input_errors) && count($input_errors) > 0) {
    print_input_errors($input_errors);
}
?>
        <section class="col-xs-12">
          <div class="tab-content content-box col-xs-12">
              <form method="post" name="iform" id="iform">
                <div class="table-responsive">
                  <table class="table table-striped opnsense_standard_table_form">
                    <tr>
                      <td ><strong><?=gettext("IPsec Advanced Settings"); ?></strong></td>
                      <td align="right">
                        <small><?=gettext("full help"); ?> </small>
                        <i class="fa fa-toggle-off text-danger"  style="cursor: pointer;" id="show_all_help_page" type="button"></i>
                      </td>
                    </tr>
                    <tr>
                      <td><a id="help_for_noinstalllanspd" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("LAN security associations"); ?></td>
                      <td>
                        <input name="noinstalllanspd" type="checkbox" id="noinstalllanspd" value="yes" <?=!empty($pconfig['noinstalllanspd']) ? "checked=\"checked\""  : "";?> />
                        <strong><?=gettext("Do not install LAN SPD"); ?></strong>
                        <div class="hidden" for="help_for_noinstalllanspd">
                            <?=gettext("By default, if IPsec is enabled negating SPD are inserted to provide protection. " .
                                                  "This behaviour can be changed by enabling this setting which will prevent installing these SPDs."); ?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><i class="fa fa-info-circle text-muted"></i> <?=gettext('Disable Auto-added VPN rules') ?></td>
                      <td>
                        <input name="disablevpnrules" type="checkbox" value="yes" <?=!empty($pconfig['disablevpnrules']) ? "checked=\"checked\"" :"";?> />
                        <strong><?=gettext("Disable all auto-added VPN rules.");?></strong>
                      </td>
                    </tr>
                    <tr>
                      <td><a id="help_for_preferoldsa_enable" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Security Associations"); ?></td>
                      <td width="78%" class="vtable">
                        <input name="preferoldsa_enable" type="checkbox" id="preferoldsa_enable" value="yes" <?= !empty($pconfig['preferoldsa_enable']) ? "checked=\"checked\"" : "";?> />
                        <strong><?=gettext("Prefer older IPsec SAs"); ?></strong>
                        <div class="hidden" for="help_for_preferoldsa_enable">
                            <?=gettext("By default, if several SAs match, the newest one is " .
                                                  "preferred if it's at least 30 seconds old. Select this " .
                                                  "option to always prefer old SAs over new ones."); ?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><a id="help_for_ipsec_debug" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("IPsec Debug"); ?></td>
                      <td>
                        <div class="hidden" for="help_for_ipsec_debug">
                                      <strong><?=gettext("Start IPsec in debug mode based on sections selected"); ?></strong> <br/>
                        </div>
<?php                   foreach ($ipsec_loglevels as $lkey => $ldescr) :
?>
                        <?=$ldescr?>
                        <select name="ipsec_<?=$lkey?>" id="ipsec_<?=$lkey?>">
<?php                   foreach (array("Silent", "Basic", "Audit", "Control", "Raw", "Highest") as $lidx => $lvalue) :
                          $lidx -= 1;
?>
                          <option value="<?=$lidx?>" <?= (isset($pconfig["ipsec_{$lkey}"]) && $pconfig["ipsec_{$lkey}"] == $lidx) || (!isset($pconfig["ipsec_{$lkey}"]) && $lidx == "0")  ? "selected=\"selected\"" : "";?> ?>
                                <?=$lvalue?>
                          </option>
<?php
endforeach; ?>
                        </select>
<?php
endforeach; ?>
                        <div class="hidden" for="help_for_ipsec_debug">
                        <?=gettext("Launch IPsec in debug mode so that more verbose logs will be generated to aid in troubleshooting."); ?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td>
                        <input name="submit" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" />
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
<?php include("foot.inc");
