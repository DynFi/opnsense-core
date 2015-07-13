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

require_once("guiconfig.inc");
require_once("certs.inc");
require_once('openvpn.inc');

function openvpn_refresh_crls() {
	global $g, $config;

	openvpn_create_dirs();

	if (isset($config['openvpn']['openvpn-server']) && is_array($config['openvpn']['openvpn-server'])) {
		foreach ($config['openvpn']['openvpn-server'] as $settings) {
			if (empty($settings))
				continue;
			if (isset($settings['disable']))
				continue;
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
	if (!is_crl_internal($crl))
		return false;
	foreach ($crl['cert'] as $id => $rcert) {
		if (($rcert['refid'] == $cert['refid']) || ($rcert['descr'] == $cert['descr'])) {
			unset($crl['cert'][$id]);
			if (count($crl['cert']) == 0) {
				// Protect against accidentally switching the type to imported, for older CRLs
				if (!isset($crl['method']))
					$crl['method'] = "internal";
				crl_update($crl);
			} else
				crl_update($crl);
			return true;
		}
	}
	return false;
}

// Keep this general to allow for future expansion. See cert_in_use() above.
function crl_in_use($crlref) {
	return (is_openvpn_server_crl($crlref));
}


global $openssl_crl_status;

$pgtitle = array(gettext("System"), gettext("Certificate Revocation List Manager"));

$crl_methods = array(
    "internal" => gettext("Create an internal Certificate Revocation List"),
    "existing" => gettext("Import an existing Certificate Revocation List"));

if (isset($_GET['id']) && ctype_alnum($_GET['id'])) {
    $id = $_GET['id'];
} elseif (isset($_POST['id']) && ctype_alnum($_POST['id'])) {
    $id = $_POST['id'];
}

if (!is_array($config['ca'])) {
    $config['ca'] = array();
}

$a_ca =& $config['ca'];

if (!is_array($config['cert'])) {
    $config['cert'] = array();
}

$a_cert =& $config['cert'];

if (!isset($config['crl']) || !is_array($config['crl'])) {
    $config['crl'] = array();
}

$a_crl =& $config['crl'];

foreach ($a_crl as $cid => $acrl) {
    if (!isset($acrl['refid'])) {
        unset ($a_crl[$cid]);
    }
}

$act=null;
if (isset($_GET['act'])) {
	$act = $_GET['act'];
} elseif (isset($_POST['act'])) {
	$act = $_POST['act'];
}

if (!empty($id)) {
    $thiscrl =& lookup_crl($id);
}

// If we were given an invalid crlref in the id, no sense in continuing as it would only cause errors.
if (!isset($thiscrl) && (($act != "") && ($act != "new"))) {
    redirectHeader("system_crlmanager.php");
    $act="";
    $savemsg = gettext("Invalid CRL reference.");
}

if ($act == "del") {
    $name = $thiscrl['descr'];
    if (crl_in_use($id)) {
        $savemsg = sprintf(gettext("Certificate Revocation List %s is in use and cannot be deleted"), $name) . "<br />";
    } else {
        foreach ($a_crl as $cid => $acrl) {
            if ($acrl['refid'] == $thiscrl['refid']) {
                unset($a_crl[$cid]);
            }
        }
        write_config("Deleted CRL {$name}.");
        $savemsg = sprintf(gettext("Certificate Revocation List %s successfully deleted"), $name) . "<br />";
    }
}

if ($act == "new") {
    if (isset($_GET['method'])) {
        $pconfig['method'] = $_GET['method'];
    } else {
        $pconfig['method'] = null;
    }
    if (isset($_GET['caref'])) {
        $pconfig['caref'] = $_GET['caref'];
    } else {
        $pconfig['caref'] = null;
    }
    $pconfig['lifetime'] = "9999";
    $pconfig['serial'] = "0";
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
}

if ($act == "addcert") {
    if ($_POST) {
        $input_errors = array();
        $pconfig = $_POST;

        if (!$pconfig['crlref'] || !$pconfig['certref']) {
            redirectHeader("system_crlmanager.php");
            exit;
        }

        // certref, crlref
        $crl =& lookup_crl($pconfig['crlref']);
        $cert = lookup_cert($pconfig['certref']);

        if (!$crl['caref'] || !$cert['caref']) {
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
            redirectHeader("system_crlmanager.php");
            exit;
        }
    }
}

if ($act == "delcert") {
    if (!is_array($thiscrl['cert'])) {
        redirectHeader("system_crlmanager.php");
        exit;
    }
    $found = false;
    foreach ($thiscrl['cert'] as $acert) {
        if ($acert['refid'] == $_GET['certref']) {
            $found = true;
            $thiscert = $acert;
        }
    }
    if (!$found) {
        redirectHeader("system_crlmanager.php");
        exit;
    }
    $name = $thiscert['descr'];
    if (cert_unrevoke($thiscert, $thiscrl)) {
        $savemsg = sprintf(gettext("Deleted Certificate %s from CRL %s"), $name, $thiscrl['descr']) . "<br />";
        openvpn_refresh_crls();
        write_config(sprintf(gettext("Deleted Certificate %s from CRL %s"), $name, $thiscrl['descr']));
    } else {
        $savemsg = sprintf(gettext("Failed to delete Certificate %s from CRL %s"), $name, $thiscrl['descr']) . "<br />";
    }
    $act="edit";
}

if ($_POST) {
    unset($input_errors);
    $pconfig = $_POST;

    /* input validation */
    if (($pconfig['method'] == "existing") || ($act == "editimported")) {
        $reqdfields = explode(" ", "descr crltext");
        $reqdfieldsn = array(
                gettext("Descriptive name"),
                gettext("Certificate Revocation List data"));
    }
    if ($pconfig['method'] == "internal") {
        $reqdfields = explode(
            " ",
            "descr caref"
        );
        $reqdfieldsn = array(
                gettext("Descriptive name"),
                gettext("Certificate Authority"));
    }

    do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

    /* if this is an AJAX caller then handle via JSON */
    if (isAjax() && is_array($input_errors)) {
        input_errors2Ajax($input_errors);
        exit;
    }

    /* save modifications */
    if (!$input_errors) {
        $result = false;

        if (isset($thiscrl)) {
            $crl =& $thiscrl;
        } else {
            $crl = array();
            $crl['refid'] = uniqid();
        }

        $crl['descr'] = $pconfig['descr'];
        if ($act != "editimported") {
            $crl['caref'] = $pconfig['caref'];
            $crl['method'] = $pconfig['method'];
        }

        if (($pconfig['method'] == "existing") || ($act == "editimported")) {
            $crl['text'] = base64_encode($pconfig['crltext']);
        }

        if ($pconfig['method'] == "internal") {
            $crl['serial'] = empty($pconfig['serial']) ? 9999 : $pconfig['serial'];
            $crl['lifetime'] = empty($pconfig['lifetime']) ? 9999 : $pconfig['lifetime'];
            $crl['cert'] = array();
        }

        if (!isset($thiscrl)) {
            $a_crl[] = $crl;
        }

        write_config("Saved CRL {$crl['descr']}");
        openvpn_refresh_crls();
        redirectHeader("system_crlmanager.php");
    }
}

include("head.inc");
?>

<body>
    <?php include("fbegin.inc"); ?>
    <script type="text/javascript">
    //<![CDATA[

    function method_change() {

	method = document.iform.method.value;

	switch (method) {
		case "internal":
			document.getElementById("existing").style.display="none";
			document.getElementById("internal").style.display="";
			break;
		case "existing":
			document.getElementById("existing").style.display="";
			document.getElementById("internal").style.display="none";
			break;
	}
    }

    //]]>
    </script>


<!-- row -->
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

                <? include('system_certificates_tabs.inc'); ?>

                <div class="content-box tab-content">

				<?php if ($act == "new" || $act == gettext("Save") || (isset($input_errors) && count($input_errors)) ) :
?>

				<form action="system_crlmanager.php" method="post" name="iform" id="iform">
					<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="main area" class="table table-striped">
						<?php if (!isset($id)) :
?>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Method");?></td>
							<td width="78%" class="vtable">
								<select name='method' id='method' class="formselect" onchange='method_change()'>
								<?php
                                    $rowIndex = 0;
                                foreach ($crl_methods as $method => $desc) :
                                    if (isset($_GET['importonly']) && ($_GET['importonly'] == "yes") && ($method != "existing")) {
                                        continue;
                                    }
                                    $selected = "";
                                    if (isset($pconfig['method']) && $pconfig['method'] == $method) {
                                        $selected = "selected=\"selected\"";
                                    }
                                    $rowIndex++;
                                ?>
                                <option value="<?=$method;
?>" <?=$selected;
?>><?=$desc;?></option>
								<?php
                                endforeach;
                                if ($rowIndex == 0) {
                                    echo "<option></option>";
                                }
                                ?>
								</select>
							</td>
						</tr>
						<?php
endif; ?>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Descriptive name");?></td>
							<td width="78%" class="vtable">
								<input name="descr" type="text" class="formfld unknown" id="descr" size="20" value="<?php if (isset($pconfig['descr'])) echo htmlspecialchars($pconfig['descr']);?>"/>
							</td>
						</tr>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Certificate Authority");?></td>
							<td width="78%" class="vtable">
								<select name='caref' id='caref' class="formselect">
								<?php
                                    $rowIndex = 0;
                                foreach ($a_ca as $ca) :
                                    $selected = "";
                                    if ($pconfig['caref'] == $ca['refid']) {
                                        $selected = "selected=\"selected\"";
                                    }
                                    $rowIndex++;
                                ?>
                                <option value="<?=$ca['refid'];
?>" <?=$selected;
?>><?=$ca['descr'];?></option>
								<?php
                                endforeach;
                                if ($rowIndex == 0) {
                                    echo "<option></option>";
                                }
                                ?>
								</select>
							</td>
						</tr>
					</table>

					<table width="100%" border="0" cellpadding="6" cellspacing="0" id="existing" summary="existing" class="table table-striped">
						<thead>
							<tr>
								<th colspan="2" valign="top" class="listtopic"><?=gettext("Existing Certificate Revocation List");?></th>
							</tr>
						</thead>

						<tbody>

							<tr>
								<td width="22%" valign="top" class="vncellreq"><?=gettext("CRL data");?></td>
								<td width="78%" class="vtable">
									<textarea name="crltext" id="crltext" cols="65" rows="7" class="formfld_crl"><?php if (isset($pconfig['crltext'])) echo $pconfig['crltext'];?></textarea>
									<br />
									<?=gettext("Paste a Certificate Revocation List in X.509 CRL format here.");?>
								</td>
							</tr>

						</tbody>
					</table>

					<table width="100%" border="0" cellpadding="6" cellspacing="0" id="internal" summary="internal" class="table table-striped">
                            <thead>
							<tr>
								<th colspan="2" valign="top" class="listtopic"><?=gettext("Internal Certificate Revocation List");?></th>
							</tr>
                            </thead>

                            <tbody>
                                <tr>
								<td width="22%" valign="top" class="vncellreq"><?=gettext("Lifetime");?></td>
								<td width="78%" class="vtable">
									<input name="lifetime" type="text" class="formfld unknown" id="lifetime" size="5" value="<?=htmlspecialchars($pconfig['lifetime']);?>"/>
									<?=gettext("days");?><br />
									<?=gettext("Default: 9999");?>
								</td>
							</tr>
							<tr>
								<td width="22%" valign="top" class="vncellreq"><?=gettext("Serial");?></td>
								<td width="78%" class="vtable">
									<input name="serial" type="text" class="formfld unknown" id="serial" size="5" value="<?=htmlspecialchars($pconfig['serial']);?>"/>
									<br />
									<?=gettext("Default: 0");?>
								</td>
							</tr>
					    </tbody>
					</table>

					<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="save" class="table table-striped">
						<tr>
							<td width="22%" valign="top">&nbsp;</td>
							<td width="78%">
								<input id="submit" name="save" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" />
								<?php if (isset($id) && $thiscrl) :
?>
								<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>" />
								<?php
endif;?>
							</td>
						</tr>
					</table>
				</form>
				<?php
elseif ($act == "editimported") :
?>

				<?php	$crl = $thiscrl; ?>
				<form action="system_crlmanager.php" method="post" name="iform" id="iform" class="table table-striped">
					<table width="100%" border="0" cellpadding="6" cellspacing="0" id="editimported" summary="import">
						<tr>
							<td colspan="2" valign="top" class="listtopic"><?=gettext("Edit Imported Certificate Revocation List");?></td>
						</tr>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Descriptive name");?></td>
							<td width="78%" class="vtable">
								<input name="descr" type="text" class="formfld unknown" id="descr" size="20" value="<?=htmlspecialchars($crl['descr']);?>"/>
							</td>
						</tr>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("CRL data");?></td>
							<td width="78%" class="vtable">
								<textarea name="crltext" id="crltext" cols="65" rows="7" class="formfld_crl"><?=base64_decode($crl['text']);?></textarea>
								<br />
								<?=gettext("Paste a Certificate Revocation List in X.509 CRL format here.");?></td>
							</td>
						</tr>
						<tr>
							<td width="22%" valign="top">&nbsp;</td>
							<td width="78%">
								<input id="submit" name="save" type="submit" class="formbtn" value="<?=gettext("Save"); ?>" />
								<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>" />
								<input name="act" type="hidden" value="editimported" />
							</td>
						</tr>
					</table>
				</form>

				<?php
elseif ($act == "edit") :
?>

				<?php	$crl = $thiscrl; ?>
				<form action="system_crlmanager.php" method="post" name="iform" id="iform">
				<table summary="revoke" class="table table-striped">
					<thead>
					<tr>
						<th colspan="4"><b><?php echo gettext("Currently Revoked Certificates for CRL") . ': ' . $crl['descr']; ?></b></th>
					</tr>
					<tr>
						<th><b><?php echo gettext("Certificate Name")?></b></th>
						<th><b><?php echo gettext("Revocation Reason")?></b></th>
						<th><b><?php echo gettext("Revoked At")?></b></th>
						<th></th>
					</tr>
					</thead>
					<tbody>
                        <?php /* List Certs on CRL */
                        if (!isset($crl['cert']) || !is_array($crl['cert']) || (count($crl['cert']) == 0)) :
?>
                        <tr>
                            <td colspan="4">
                                <?php echo gettext("No Certificates Found for this CRL."); ?>
                            </td>
                        </tr>
                            <?php
                        else :
                            foreach ($crl['cert'] as $i => $cert) :
                                $name = htmlspecialchars($cert['descr']);
                                ?>
                            <tr>
                                <td>
                                    <?php echo $name; ?>
						</td>
						<td>
            <?php echo $openssl_crl_status[$cert["reason"]]; ?>
						</td>
						<td>
            <?php echo date("D M j G:i:s T Y", $cert["revoke_time"]); ?>
						</td>
						<td>
            <a href="system_crlmanager.php?act=delcert&amp;id=<?php echo $crl['refid']; ?>&amp;certref=<?php echo $cert['refid'];
                        ?>" data-toggle="tooltip" data-placement="left" title="<?=gettext("Delete this certificate from the CRL ");
?>" onclick="return confirm('<?=gettext("Do you really want to delete this Certificate from the CRL?");?>')" class="btn btn-default btn-xs">
                <span class="glyphicon glyphicon-remove"></span>
            </a>
						</td>
					</tr>
					<?php
                            endforeach;
                        endif;
                    ?>
				<?php /* Drop-down with other certs from this CA. */
                    // Map Certs to CAs in one pass
                    $ca_certs = array();
                foreach ($a_cert as $cert) {
                    if (isset($cert['caref']) && isset($crl['caref'])  && $cert['caref'] == $crl['caref']) {
                        $ca_certs[] = $cert;
                    }
                }
                if (count($ca_certs) == 0) :
?>
					<tr>
						<td colspan="4">
							<?php echo gettext("No Certificates Found for this CA."); ?>
						</td>
					</tr>
                        <?php
                else :
?>
                    <tr>
                    <th colspan="4">
			<?=gettext("Revoke a Certificate"); ?>
			</th>
			</tr>
					<tr>
						<td>
							<b><?php echo gettext("Choose a Certificate to Revoke"); ?></b>:
						</td>
						<td colspan="3" align="left">
							<select name='certref' id='certref' class="selectpicker" data-style="btn-default" data-live-search="true">
                                <?php $rowIndex = 0;
                                foreach ($ca_certs as $cert) :
                                    $rowIndex++; ?>
                                    <option value="<?=$cert['refid'];?>"><?=htmlspecialchars($cert['descr'])?></option>
                                        <?php
                                endforeach;
                                if ($rowIndex == 0) {
                                    echo "<option></option>";
                                } ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo gettext("Reason");?></b>:
						</td>
						<td colspan="3" align="left">
							<select name='crlreason' id='crlreason' class="selectpicker" data-style="btn-default">
				<?php	$rowIndex = 0;
                foreach ($openssl_crl_status as $code => $reason) :
                    $rowIndex++; ?>
							<option value="<?= $code ?>"><?= htmlspecialchars($reason) ?></option>
                            <?php
                endforeach;
                if ($rowIndex == 0) {
                    echo "<option></option>";
                } ?>
							</select>
						</td>
					</tr>
					<tr>
						<td></td>
						<td colspan="3" align="left">
							<input name="act" type="hidden" value="addcert" />
							<input name="crlref" type="hidden" value="<?=$crl['refid'];?>" />
							<input name="id" type="hidden" value="<?=$crl['refid'];?>" />
							<input id="submit" name="add" type="submit" class="formbtn btn btn-primary" value="<?=gettext("Add"); ?>" />
						</td>
					</tr>
                        <?php
                endif; ?>
					</tbody>
				</table>
				</form>

				<?php
else :
?>

				<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="ocpms" class="table table-striped">
					<thead>
					<tr>
						<td width="35%" class="listhdrr"><?=gettext("Name");?></td>
						<td width="10%" class="listhdrr"><?=gettext("Internal");?></td>
						<td width="35%" class="listhdrr"><?=gettext("Certificates");?></td>
						<td width="10%" class="listhdrr"><?=gettext("In Use");?></td>
						<td width="10%" class="list"></td>
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
					</tfoot>					<tbody>
					<?php
                        $caimg = "/themes/{$g['theme']}/images/icons/icon_frmfld_cert.png";
                        // Map CRLs to CAs in one pass
                        $ca_crl_map = array();
                    foreach ($a_crl as $crl) {
                        $ca_crl_map[$crl['caref']][] = $crl['refid'];
                    }

                        $i = 0;
                    foreach ($a_ca as $ca) :
                        $name = htmlspecialchars($ca['descr']);

                        if ($ca['prv']) {
                            $cainternal = "YES";
                        } else {
                            $cainternal = "NO";
                        }
                    ?>
					<tr>
                    <td class="listlr" colspan="4">
                        <?=$name;?>
                    </td>
                    <td class="list">
                    <?php if ($cainternal == "YES") :
?>
							<a href="system_crlmanager.php?act=new&amp;caref=<?php echo $ca['refid']; ?>" data-toggle="tooltip" data-placement="left" title="<?php printf(gettext("Add or Import CRL for %s"), $ca['descr']);?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-plus"></span></a>
						<?php
else :
?>
							<a href="system_crlmanager.php?act=new&amp;caref=<?php echo $ca['refid']; ?>&amp;importonly=yes" data-toggle="tooltip" data-placement="left" title="<?php printf(gettext("Import CRL for %s"), $ca['descr']);?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-plus"></span></a>
						<?php
endif; ?>
                    </td>
					</tr>

                    <?php
                    if (isset($ca_crl_map[$ca['refid']]) && is_array($ca_crl_map[$ca['refid']])) :
                        foreach ($ca_crl_map[$ca['refid']] as $crl) :
                            $tmpcrl = lookup_crl($crl);
                            $internal = is_crl_internal($tmpcrl);
                            $inuse = crl_in_use($tmpcrl['refid']);
                        ?>
					<tr>
						<td class="listlr"><?php echo $tmpcrl['descr']; ?></td>
						<td class="listr"><?php echo ($internal) ? "YES" : "NO"; ?></td>
						<td class="listr"><?php echo ($internal) ? (isset($tmpcrl['cert']) && count($tmpcrl['cert'])) : "Unknown (imported)"; ?></td>
						<td class="listr"><?php echo ($inuse) ? "YES" : "NO"; ?></td>
						<td valign="middle" class="list nowrap">
                        <a href="system_crlmanager.php?act=exp&amp;id=<?=$tmpcrl['refid'];?>" class="btn btn-default btn-xs">
                            <span class="glyphicon glyphicon-export" data-toggle="tooltip" data-placement="left" title="<?=gettext("Export CRL") . " " . htmlspecialchars($tmpcrl['descr']);?>"></span>
                        </a>
                        <?php if ($internal) :
?>
							<a href="system_crlmanager.php?act=edit&amp;id=<?=$tmpcrl['refid'];?>" class="btn btn-default btn-xs">
								<span class="glyphicon glyphicon-edit" data-toggle="tooltip" data-placement="left" title="<?=gettext("Edit CRL") . " " . htmlspecialchars($tmpcrl['descr']);?>"></span>
				            </a>
							<?php
else :
?>
							<a href="system_crlmanager.php?act=editimported&amp;id=<?=$tmpcrl['refid'];?>" class="btn btn-default btn-xs">
								<span class="glyphicon glyphicon-edit" data-toggle="tooltip" data-placement="left" title="<?=gettext("Edit CRL") . " " . htmlspecialchars($tmpcrl['descr']);?>"></span>
                                </a>
							<?php
endif; ?>
                        <?php if (!$inuse) :
?>
							<a href="system_crlmanager.php?act=del&amp;id=<?=$tmpcrl['refid'];
?>" onclick="return confirm('<?=gettext("Do you really want to delete this Certificate Revocation List?") . ' (' . htmlspecialchars($tmpcrl['descr']) . ')';?>')"  class="btn btn-default btn-xs">
								<span class="glyphicon glyphicon-remove" data-toggle="tooltip" data-placement="left" title="<?=gettext("Delete CRL") . " " . htmlspecialchars($tmpcrl['descr']);?>"></span>
							</a>
							<?php
endif; ?>
						</td>
					</tr>
						<?php $i++;

                        endforeach;

                    endif; ?>
					<tr><td colspan="5">&nbsp;</td></tr>
					<?php $i++;

                    endforeach; ?>
					</tbody>
				</table>

                <?php
endif; ?>

                </div>
            </section>
        </div>
	</div>
</section>

<script type="text/javascript">
//<![CDATA[
method_change();
//]]>
</script>

<?php include("foot.inc");
