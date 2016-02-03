<?php

/*
    Copyright (C) 2014-2015 Deciso B.V.
    Copyright (C) 2010 Jim Pingle
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

require_once('guiconfig.inc');
require_once('openvpn.inc');

function openvpn_refresh_crls()
{
    global $config;

    openvpn_create_dirs();

    if (isset($config['openvpn']['openvpn-server']) && is_array($config['openvpn']['openvpn-server'])) {
        foreach ($config['openvpn']['openvpn-server'] as $settings) {
            if (empty($settings) || isset($settings['disable'])) {
                continue;
            }
            // Write the settings for the keys
            switch($settings['mode']) {
                case 'p2p_tls':
                case 'server_tls':
                case 'server_tls_user':
                case 'server_user':
                    if (!empty($settings['crlref'])) {
                        $crl = lookup_crl($settings['crlref']);
                        crl_update($crl);
                        $fpath = "/var/etc/openvpn/server{$settings['vpnid']}.crl-verify";
                        file_put_contents($fpath, base64_decode($crl['text']));
                        @chmod($fpath, 0644);
                    }
                    break;
            }
        }
    }
}



function cert_unrevoke($cert, & $crl) {
    global $config;
    if (!is_crl_internal($crl)) {
        return false;
    }

    foreach ($crl['cert'] as $id => $rcert) {
        if (($rcert['refid'] == $cert['refid']) || ($rcert['descr'] == $cert['descr'])) {
            unset($crl['cert'][$id]);
            if (count($crl['cert']) == 0) {
                // Protect against accidentally switching the type to imported, for older CRLs
                if (!isset($crl['method'])) {
                    $crl['method'] = "internal";
                }
                crl_update($crl);
            } else {
                crl_update($crl);
            }
            return true;
        }
    }
    return false;
}
// openssl_crl_status messages from certs.inc
global $openssl_crl_status;

// prepare config types
if (!isset($config['ca']) || !is_array($config['ca'])) {
    $config['ca'] = array();
}
if (!isset($config['cert']) || !is_array($config['cert'])) {
    $config['cert'] = array();
}
if (!isset($config['crl']) || !is_array($config['crl'])) {
    $config['crl'] = array();
}
$a_crl =& $config['crl'];


$thiscrl = false;
$act=null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // locate cert by refid, returns false when not found
    if (isset($_GET['id'])) {
        $thiscrl =& lookup_crl($_GET['id']);
        if ($thiscrl !== false) {
            $id = $_GET['id'];
        }
    }
    if (isset($_GET['act'])) {
        $act = $_GET['act'];
    }

    if ($act == "exp") {
        crl_update($thiscrl);
        $exp_name = urlencode("{$thiscrl['descr']}.crl");
        $exp_data = base64_decode($thiscrl['text']);
        $exp_size = strlen($exp_data);

        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename={$exp_name}");
        header("Content-Length: $exp_size");
        echo $exp_data;
        exit;
    } elseif ($act == "new") {
        $pconfig = array();
        $pconfig['descr'] = null;
        $pconfig['crltext'] = null;
        $pconfig['crlmethod'] = !empty($_GET['method']) ? $_GET['method'] : null;
        $pconfig['caref'] = !empty($_GET['caref']) ? $_GET['caref'] : null;
        $pconfig['lifetime'] = "9999";
        $pconfig['serial'] = "0";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pconfig = $_POST;
    // locate cert by refid, returns false when not found
    if (isset($_POST['id'])) {
        $thiscrl =& lookup_crl($_POST['id']);
        if ($thiscrl !== false) {
            $id = $_POST['id'];
        }
    }
    if (isset($_POST['act'])) {
        $act = $_POST['act'];
    }

    if ($act == "del" && isset($id)) {
        $name = $thiscrl['descr'];
        if (is_openvpn_server_crl($id)) {
            $savemsg = sprintf(gettext("Certificate Revocation List %s is in use and cannot be deleted"), $name) . "<br />";
        } else {
            foreach ($a_crl as $cid => $acrl) {
                if ($acrl['refid'] == $thiscrl['refid']) {
                    unset($a_crl[$cid]);
                }
            }
            write_config("Deleted CRL {$name}.");
            header("Location: system_crlmanager.php");
            exit;
        }
    } elseif ($act == "delcert" && isset($id)) {
        if (!isset($thiscrl['cert']) || !is_array($thiscrl['cert'])) {
            header("Location: system_crlmanager.php");
            exit;
        }
        $found = false;
        foreach ($thiscrl['cert'] as $acert) {
            if ($acert['refid'] == $pconfig['certref']) {
                $found = true;
                $thiscert = $acert;
            }
        }
        if (!$found) {
            header("Location: system_crlmanager.php");
            exit;
        }
        $name = $thiscert['descr'];
        if (cert_unrevoke($thiscert, $thiscrl)) {
            openvpn_refresh_crls();
            write_config(sprintf(gettext("Deleted Certificate %s from CRL %s"), $name, $thiscrl['descr']));
            header("Location: system_crlmanager.php");
            exit;
        } else {
            $savemsg = sprintf(gettext("Failed to delete Certificate %s from CRL %s"), $name, $thiscrl['descr']) . "<br />";
        }
        $act="edit";
    } elseif ($act == "addcert") {
        $input_errors = array();
        if (!isset($id)) {
            header("Location: system_crlmanager.php");
            exit;
        }

        // certref, crlref
        $crl =& lookup_crl($id);
        $cert = lookup_cert($pconfig['certref']);

        if (empty($crl['caref']) || empty($cert['caref'])) {
            $input_errors[] = gettext("Both the Certificate and CRL must be specified.");
        }

        if ($crl['caref'] != $cert['caref']) {
            $input_errors[] = gettext("CA mismatch between the Certificate and CRL. Unable to Revoke.");
        }
        if (!is_crl_internal($crl)) {
            $input_errors[] = gettext("Cannot revoke certificates for an imported/external CRL.");
        }

        if (!count($input_errors)) {
            $reason = (empty($pconfig['crlreason'])) ? OCSP_REVOKED_STATUS_UNSPECIFIED : $pconfig['crlreason'];
            cert_revoke($cert, $crl, $reason);
            openvpn_refresh_crls();
            write_config("Revoked cert {$cert['descr']} in CRL {$crl['descr']}.");
            header("Location: system_crlmanager.php");
            exit;
        }
    } else {
        $input_errors = array();
        $pconfig = $_POST;

        /* input validation */
        if (($pconfig['crlmethod'] == "existing") || ($act == "editimported")) {
            $reqdfields = explode(" ", "descr crltext");
            $reqdfieldsn = array(
                    gettext("Descriptive name"),
                    gettext("Certificate Revocation List data"));
        } elseif ($pconfig['crlmethod'] == "internal") {
            $reqdfields = explode(
                " ",
                "descr caref"
            );
            $reqdfieldsn = array(
                    gettext("Descriptive name"),
                    gettext("Certificate Authority"));
        }

        do_input_validation($pconfig, $reqdfields, $reqdfieldsn, $input_errors);

        /* save modifications */
        if (count($input_errors) == 0) {
            if (isset($id)) {
                $crl =& $thiscrl;
            } else {
                $crl = array();
                $crl['refid'] = uniqid();
            }

            foreach (array("descr", "caref", "crlmethod") as $fieldname) {
                if (isset($pconfig[$fieldname])) {
                    $crl[$fieldname] = $pconfig[$fieldname];
                }
            }

            if (($pconfig['crlmethod'] == "existing") || ($act == "editimported")) {
                $crl['text'] = base64_encode($pconfig['crltext']);
            }

            if ($pconfig['crlmethod'] == "internal") {
                $crl['serial'] = empty($pconfig['serial']) ? 9999 : $pconfig['serial'];
                $crl['lifetime'] = empty($pconfig['lifetime']) ? 9999 : $pconfig['lifetime'];
                $crl['cert'] = array();
            }

            if (!isset($id)) {
                $a_crl[] = $crl;
            }

            write_config("Saved CRL {$crl['descr']}");
            openvpn_refresh_crls();
            header("Location: system_crlmanager.php");
            exit;
        }
    }

}

legacy_html_escape_form_data($pconfig);
legacy_html_escape_form_data($thiscrl);
include("head.inc");
?>

<body>
  <script type="text/javascript">

  $( document ).ready(function() {
    // delete cert revocation list
    $(".act_delete").click(function(event){
      event.preventDefault();
      var id = $(this).data('id');
      var descr = $(this).data('descr');
      BootstrapDialog.show({
        type:BootstrapDialog.TYPE_DANGER,
        title: "<?=gettext("Certificates");?>",
        message: "<?=gettext("Do you really want to delete this Certificate Revocation List?");?> (" + descr + ")" ,
        buttons: [{
                  label: "<?=gettext("No");?>",
                  action: function(dialogRef) {
                    dialogRef.close();
                  }}, {
                  label: "<?=gettext("Yes");?>",
                  action: function(dialogRef) {
                    $("#id").val(id);
                    $("#action").val("del");
                    $("#iform").submit();
                }
              }]
      });
    });

    // Delete certificate from CRL
    $(".act_delete_cert").click(function(event){
      event.preventDefault();
      var id = $(this).data('id');
      var certref = $(this).data('certref');
      BootstrapDialog.show({
        type:BootstrapDialog.TYPE_DANGER,
        title: "<?=gettext("Certificates");?>",
        message: "<?=gettext("Delete this certificate from the CRL ");?>",
        buttons: [{
                  label: "<?=gettext("No");?>",
                  action: function(dialogRef) {
                    dialogRef.close();
                  }}, {
                  label: "<?=gettext("Yes");?>",
                  action: function(dialogRef) {
                    $("#id").val(id);
                    $("#certref").val(certref);
                    $("#action").val("delcert");
                    $("#iform").submit();
                }
              }]
      });
    });

    $("#crlmethod").change(function(){
        $("#existing").addClass("hidden");
        $("#internal").addClass("hidden");
        if ($("#crlmethod").val() == "internal") {
            $("#internal").removeClass("hidden");
        } else {
            $("#existing").removeClass("hidden");
        };
    });
    $("#crlmethod").change();
  });
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
        <div class="content-box tab-content">
<?php
        if ($act == "new") :?>
          <form method="post" name="iform" id="iform">
            <input type="hidden" name="act" id="action" value="<?=$act;?>"/>
            <table class="table table-striped">
<?php
              if (!isset($id)) :?>
              <tr>
                <td width="22%"><i class="fa fa-info-circle text-muted"></i> <?=gettext("Method");?></td>
                <td width="78%">
                  <select name="crlmethod" id='crlmethod' class="formselect">
                    <option value="internal" <?=$pconfig['crlmethod'] == "internal" ? "selected=\"selected\"" : "";?>><?=gettext("Create an internal Certificate Revocation List");?></option>
                    <option value="existing" <?=$pconfig['crlmethod'] == "existing" ? "selected=\"selected\"" : "";?>><?=gettext("Import an existing Certificate Revocation List");?></option>
                  </select>
                </td>
              </tr>
<?php
              endif; ?>
              <tr>
                <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("Descriptive name");?></td>
                <td>
                  <input name="descr" type="text" id="descr" size="20" value="<?=$pconfig['descr'];?>"/>
                </td>
              </tr>
              <tr>
                <td><i class="fa fa-info-circle text-muted"></i> <?=gettext("Certificate Authority");?></td>
                <td>
                  <select name='caref' id='caref' class="selectpicker">
<?php
                  foreach ($config['ca'] as $ca):?>
                    <option value="<?=$ca['refid'];?>" <?=$pconfig['caref'] == $ca['refid'] ? "selected=\"selected\"" : "";?>>
                      <?=htmlentities($ca['descr']);?>
                    </option>
<?php
                  endforeach;?>
                  </select>
                </td>
              </tr>
            </table>
            <!-- import existing -->
            <table id="existing" class="table table-striped">
              <thead>
                <tr>
                  <th colspan="2"><?=gettext("Existing Certificate Revocation List");?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td width="22%"><a id="help_for_crltext" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("CRL data");?></td>
                  <td width="78%">
                    <textarea name="crltext" id="crltext" cols="65" rows="7" class="formfld_crl"><?=$pconfig['crltext'];?></textarea>
                    <div class="hidden" for="help_for_crltext">
                      <?=gettext("Paste a Certificate Revocation List in X.509 CRL format here.");?>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            <!-- create internal -->
            <table id="internal" class="table table-striped">
              <thead>
                <tr>
                  <th colspan="2"><?=gettext("Internal Certificate Revocation List");?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td width="22%"><a id="help_for_lifetime" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Lifetime");?> (<?=gettext("days");?>)</td>
                  <td width="78%">
                    <input name="lifetime" type="text" id="lifetime" size="5" value="<?=$pconfig['lifetime'];?>"/>
                    <div class="hidden" for="help_for_lifetime">
                      <?=gettext("Default: 9999");?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><a id="help_for_serial" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Serial");?></td>
                  <td>
                    <input name="serial" type="text" id="serial" size="5" value="<?=$pconfig['serial'];?>"/>
                    <div class="hidden" for="help_for_serial">
                      <?=gettext("Default: 0");?>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <table class="table table-striped">
              <tr>
                <td width="22%">&nbsp;</td>
                <td width="78%">
                  <input id="submit" name="save" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" />
<?php
                  if (isset($id)) :?>
                  <input name="id" type="hidden" value="<?=$id;?>" />
<?php
                  endif;?>
                </td>
              </tr>
            </table>
          </form>
<?php
          elseif ($act == "editimported") :?>
          <form method="post" name="iform" id="iform">
            <table id="editimported" class="table table-striped">
              <tr>
                <th colspan="2"><?=gettext("Edit Imported Certificate Revocation List");?></th>
              </tr>
              <tr>
                <td width="22%"><i class="fa fa-info-circle text-muted"></i> <?=gettext("Descriptive name");?></td>
                <td width="78%">
                  <input name="descr" type="text" id="descr" size="20" value="<?=$thiscrl['descr'];?>"/>
                </td>
              </tr>
              <tr>
                <td><a id="help_for_crltext" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("CRL data");?></td>
                <td>
                  <textarea name="crltext" id="crltext" cols="65" rows="7" class="formfld_crl"><?=$thiscrl['text'];?></textarea>
                  <div class="hidden" for="help_for_crltext">
                    <?=gettext("Paste a Certificate Revocation List in X.509 CRL format here.");?>
                  </div>
                </td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>
                  <input id="submit" name="save" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" />
                  <input name="id" type="hidden" value="<?=$id;?>" />
                  <input name="act" type="hidden" value="<?=$act;?>" />
                </td>
              </tr>
            </table>
          </form>
<?php
          elseif ($act == "edit") :?>
          <form method="post" name="iform" id="iform">
            <input type="hidden" name="id" id="id" value=""/>
            <input type="hidden" name="certref" id="certref" value=""/>
            <input type="hidden" name="act" id="action" value=""/>
          </form>
          <form method="post">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th colspan="4"><?=gettext("Currently Revoked Certificates for CRL");?> : <?=$thiscrl['descr'];?></th>
                </tr>
                <tr>
                  <th><?=gettext("Certificate Name")?></th>
                  <th><?=gettext("Revocation Reason")?></th>
                  <th><?=gettext("Revoked At")?></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
<?php /* List Certs on CRL */
                if (!isset($thiscrl['cert']) || !is_array($thiscrl['cert']) || (count($thiscrl['cert']) == 0)) :?>
                <tr>
                  <td colspan="4">
                      <?=gettext("No Certificates Found for this CRL."); ?>
                  </td>
                </tr>
<?php
              else :
                foreach ($thiscrl['cert'] as $i => $cert) :?>
                <tr>
                  <td><?=$cert['descr']; ?></td>
                  <td><?=$openssl_crl_status[$cert["reason"]]; ?></td>
                  <td><?=date("D M j G:i:s T Y", $cert["revoke_time"]); ?></td>
                  <td>
                    <a id="del_cert_<?=$thiscrl['refid'];?>" data-id="<?=$thiscrl['refid'];?>" data-certref="<?=$cert['refid'];?>" title="<?=gettext("Delete this certificate from the CRL ");?>" data-toggle="tooltip"  class="act_delete_cert btn btn-default btn-xs">
                      <span class="fa fa-trash text-muted"></span>
                    </a>
                  </td>
                </tr>
<?php
                endforeach;
              endif;
              $ca_certs = array();
              foreach ($config['cert'] as $cert) {
                  if (isset($cert['caref']) && isset($thiscrl['caref'])  && $cert['caref'] == $thiscrl['caref']) {
                      $ca_certs[] = $cert;
                  }
              }
              if (count($ca_certs) == 0) :?>
                <tr>
                  <td colspan="4"><?=gettext("No Certificates Found for this CA."); ?></td>
                </tr>
<?php
                else:?>
                <tr>
                  <th colspan="4"><?=gettext("Revoke a Certificate"); ?></th>
                </tr>
                <tr>
                  <td>
                    <b><?=gettext("Choose a Certificate to Revoke"); ?></b>:
                  </td>
                  <td colspan="3" align="left">
                    <select name='certref' id='certref' class="selectpicker" data-style="btn-default" data-live-search="true">
<?php
                  foreach ($ca_certs as $cert) :?>
                    <option value="<?=$cert['refid'];?>"><?=htmlspecialchars($cert['descr'])?></option>
<?php
                  endforeach;?>
                    </select>
                  </td>
                </tr>
                <tr>
                  <td>
                    <b><?=gettext("Reason");?></b>:
                  </td>
                  <td colspan="3" align="left">
                    <select name='crlreason' id='crlreason' class="selectpicker" data-style="btn-default">
<?php
                  foreach ($openssl_crl_status as $code => $reason) :?>
                    <option value="<?= $code ?>"><?=$reason?></option>
<?php
                  endforeach;?>
                    </select>
                  </td>
                </tr>
                <tr>
                  <td></td>
                  <td colspan="3" align="left">
                    <input name="act" type="hidden" value="addcert" />
                    <input name="id" type="hidden" value="<?=$thiscrl['refid'];?>" />
                    <input id="submit" name="add" type="submit" class="formbtn btn btn-primary" value="<?=gettext("Add"); ?>" />
                  </td>
                </tr>
<?php
                endif; ?>
              </tbody>
            </table>
          </form>
<?php
          else :?>
          <form method="post" id="iform" class="table table-striped">
            <input type="hidden" name="id" id="id" value=""/>
            <input type="hidden" name="act" id="action" value=""/>
            <table class="table table-striped">
              <thead>
                <tr>
                  <td><?=gettext("Name");?></td>
                  <td><?=gettext("Internal");?></td>
                  <td><?=gettext("Certificates");?></td>
                  <td><?=gettext("In Use");?></td>
                  <td></td>
                </tr>
              </thead>
              <tfoot>
                <tr>
                  <td colspan="5">
                    <p>
                      <?=gettext("Additional Certificate Revocation Lists can be added here.");?>
                    </p>
                  </td>
                </tr>
              </tfoot>
              <tbody>
<?php
                // Map CRLs to CAs
                $ca_crl_map = array();
                foreach ($a_crl as $crl) {
                    $ca_crl_map[$crl['caref']][] = $crl['refid'];
                }

                foreach ($config['ca'] as $ca) :?>
                <tr>
                  <td colspan="4"> <?=htmlspecialchars($ca['descr']);?></td>
                  <td>
<?php
                  if (!empty($ca['prv'])) :?>
                    <a href="system_crlmanager.php?act=new&amp;caref=<?=$ca['refid']; ?>" data-toggle="tooltip" title="<?php printf(gettext("Add or Import CRL for %s"), htmlspecialchars($ca['descr']));?>" class="btn btn-default btn-xs">
                      <span class="glyphicon glyphicon-plus"></span>
                    </a>
<?php
                  else :?>
                    <a href="system_crlmanager.php?act=new&amp;caref=<?=$ca['refid']; ?>&amp;importonly=yes" data-toggle="tooltip" title="<?php printf(gettext("Import CRL for %s"), htmlspecialchars($ca['descr']));?>" class="btn btn-default btn-xs">
                      <span class="glyphicon glyphicon-plus"></span>
                    </a>
<?php
                  endif;?>
                  </td>
                </tr>
<?php
                  if (isset($ca_crl_map[$ca['refid']]) && is_array($ca_crl_map[$ca['refid']])):
                    foreach ($ca_crl_map[$ca['refid']] as $crl):
                        $tmpcrl = lookup_crl($crl);
                        $internal = is_crl_internal($tmpcrl);
                        $inuse = is_openvpn_server_crl($tmpcrl['refid']);?>
                <tr>
                  <td><?=htmlspecialchars($tmpcrl['descr']); ?></td>
                  <td><?=$internal ? gettext("YES") : gettext("NO"); ?></td>
                  <td><?=$internal ? (isset($tmpcrl['cert']) && count($tmpcrl['cert'])) : gettext("Unknown (imported)"); ?></td>
                  <td><?=$inuse ? gettext("YES") : gettext("NO"); ?></td>
                  <td>
                    <a href="system_crlmanager.php?act=exp&amp;id=<?=$tmpcrl['refid'];?>" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-export" data-toggle="tooltip" title="<?=gettext("Export CRL") . " " . htmlspecialchars($tmpcrl['descr']);?>"></span>
                    </a>
<?php
                  if ($internal) :?>
                    <a href="system_crlmanager.php?act=edit&amp;id=<?=$tmpcrl['refid'];?>" class="btn btn-default btn-xs">
                      <span class="glyphicon glyphicon-edit" data-toggle="tooltip" title="<?=gettext("Edit CRL") . " " . htmlspecialchars($tmpcrl['descr']);?>"></span>
                    </a>
<?php
                  else :?>
                    <a href="system_crlmanager.php?act=editimported&amp;id=<?=$tmpcrl['refid'];?>" class="btn btn-default btn-xs">
                      <span class="glyphicon glyphicon-edit" data-toggle="tooltip" title="<?=gettext("Edit CRL") . " " . htmlspecialchars($tmpcrl['descr']);?>"></span>
                    </a>
<?php
                  endif; ?>
<?php
                  if (!$inuse) :?>
                    <a id="del_<?=$tmpcrl['refid'];?>" data-descr="<?=htmlspecialchars($tmpcrl['descr']);?>" data-id="<?=$tmpcrl['refid'];?>" title="<?=gettext("Delete CRL") . " " . htmlspecialchars($tmpcrl['descr']);?>" data-toggle="tooltip"  class="act_delete btn btn-default btn-xs">
                      <span class="fa fa-trash text-muted"></span>
                    </a>
<?php
                  endif; ?>
                  </td>
                </tr>
<?php
                    endforeach;
                  endif; ?>
                <tr><td colspan="5">&nbsp;</td></tr>
<?php
                endforeach; ?>
              </tbody>
            </table>
          </form>
<?php
        endif; ?>
        </div>
      </section>
    </div>
  </div>
</section>
<?php include("foot.inc");
