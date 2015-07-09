<?php

/*
    Copyright (C) 2014-2015 Deciso B.V.
    Copyright (C) 2008 Shrew Soft Inc.
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
require_once('certs.inc');

function ca_import(& $ca, $str, $key="", $serial=0) {
	global $config;

	$ca['crt'] = base64_encode($str);
	if (!empty($key))
		$ca['prv'] = base64_encode($key);
	if (!empty($serial))
		$ca['serial'] = $serial;
	$subject = cert_get_subject($str, false);
	$issuer = cert_get_issuer($str, false);

	// Find my issuer unless self-signed
	if($issuer <> $subject) {
		$issuer_crt =& lookup_ca_by_subject($issuer);
		if($issuer_crt)
			$ca['caref'] = $issuer_crt['refid'];
	}

	/* Correct if child certificate was loaded first */
	if (is_array($config['ca']))
		foreach ($config['ca'] as & $oca)
		{
			$issuer = cert_get_issuer($oca['crt']);
			if($ca['refid']<>$oca['refid'] && $issuer==$subject)
				$oca['caref'] = $ca['refid'];
		}
	if (is_array($config['cert']))
		foreach ($config['cert'] as & $cert)
		{
			$issuer = cert_get_issuer($cert['crt']);
			if($issuer==$subject)
				$cert['caref'] = $ca['refid'];
		}
	return true;
}

function ca_inter_create(& $ca, $keylen, $lifetime, $dn, $caref, $digest_alg = "sha256") {
	// Create Intermediate Certificate Authority
	$signing_ca =& lookup_ca($caref);
	if (!$signing_ca)
		return false;

	$signing_ca_res_crt = openssl_x509_read(base64_decode($signing_ca['crt']));
	$signing_ca_res_key = openssl_pkey_get_private(array(0 => base64_decode($signing_ca['prv']) , 1 => ""));
	if (!$signing_ca_res_crt || !$signing_ca_res_key) return false;
	$signing_ca_serial = ++$signing_ca['serial'];

	$args = array(
		"x509_extensions" => "v3_ca",
		"digest_alg" => $digest_alg,
		"private_key_bits" => (int)$keylen,
		"private_key_type" => OPENSSL_KEYTYPE_RSA,
		"encrypt_key" => false);

	// generate a new key pair
	$res_key = openssl_pkey_new($args);
	if (!$res_key) return false;

	// generate a certificate signing request
	$res_csr = openssl_csr_new($dn, $res_key, $args);
	if (!$res_csr) return false;

	// Sign the certificate
	$res_crt = openssl_csr_sign($res_csr, $signing_ca_res_crt, $signing_ca_res_key, $lifetime, $args, $signing_ca_serial);
	if (!$res_crt) return false;

	// export our certificate data
	if (!openssl_pkey_export($res_key, $str_key) ||
	    !openssl_x509_export($res_crt, $str_crt))
		return false;

	// return our ca information
	$ca['crt'] = base64_encode($str_crt);
	$ca['prv'] = base64_encode($str_key);
	$ca['serial'] = 0;

	return true;
}


$ca_methods = array(
    "existing" => gettext("Import an existing Certificate Authority"),
    "internal" => gettext("Create an internal Certificate Authority"),
    "intermediate" => gettext("Create an intermediate Certificate Authority"));

$ca_keylens = array( "512", "1024", "2048", "4096");
$openssl_digest_algs = array("sha1", "sha224", "sha256", "sha384", "sha512");

$pgtitle = array(gettext("System"), gettext("Certificate Authority Manager"));

if (isset($_GET['id']) && is_numericint($_GET['id'])) {
    $id = $_GET['id'];
}
if (isset($_POST['id']) && is_numericint($_POST['id'])) {
    $id = $_POST['id'];
}

if (!isset($config['ca']) || !is_array($config['ca'])) {
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

$act=null;
if (isset($_GET['act'])) {
    $act = $_GET['act'];
} elseif (isset($_POST['act'])) {
    $act = $_POST['act'];
}

if ($act == "del") {
    if (!isset($a_ca[$id])) {
        redirectHeader("system_camanager.php");
        exit;
    }

    $index = count($a_cert) - 1;
    for (; $index >=0; $index--) {
        if (isset($a_cert[$index]['caref']) && isset($a_ca[$id]['refid']) && $a_cert[$index]['caref'] == $a_ca[$id]['refid']) {
            unset($a_cert[$index]);
        }
    }

    $index = count($a_crl) - 1;
    for (; $index >=0; $index--) {
        if ($a_crl[$index]['caref'] == $a_ca[$id]['refid']) {
            unset($a_crl[$index]);
        }
    }

    $name = $a_ca[$id]['descr'];
    unset($a_ca[$id]);
    write_config();
    $savemsg = sprintf(gettext("Certificate Authority %s and its CRLs (if any) successfully deleted"), $name) . "<br />";
    redirectHeader("system_camanager.php");
    exit;
}

if ($act == "edit") {
    if (!isset($a_ca[$id])) {
        redirectHeader("system_camanager.php");
        exit;
    }
    $pconfig['descr']  = $a_ca[$id]['descr'];
    $pconfig['refid']  = $a_ca[$id]['refid'];
    $pconfig['cert']   = base64_decode($a_ca[$id]['crt']);
    $pconfig['serial'] = $a_ca[$id]['serial'];
    if (!empty($a_ca[$id]['prv'])) {
        $pconfig['key'] = base64_decode($a_ca[$id]['prv']);
    }
}

if ($act == "new") {
    if (isset($_GET['method'])) {
        $pconfig['method'] = $_GET['method'];
    } else {
        $pconfig['method'] = null ;
    }
    $pconfig['keylen'] = "2048";
    $pconfig['digest_alg'] = "sha256";
    $pconfig['lifetime'] = "365";
    $pconfig['dn_commonname'] = "internal-ca";
}

if ($act == "exp") {
    if (!$a_ca[$id]) {
        redirectHeader("system_camanager.php");
        exit;
    }

    $exp_name = urlencode("{$a_ca[$id]['descr']}.crt");
    $exp_data = base64_decode($a_ca[$id]['crt']);
    $exp_size = strlen($exp_data);

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename={$exp_name}");
    header("Content-Length: $exp_size");
    echo $exp_data;
    exit;
}

if ($act == "expkey") {
    if (!$a_ca[$id]) {
        redirectHeader("system_camanager.php");
        exit;
    }

    $exp_name = urlencode("{$a_ca[$id]['descr']}.key");
    $exp_data = base64_decode($a_ca[$id]['prv']);
    $exp_size = strlen($exp_data);

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename={$exp_name}");
    header("Content-Length: $exp_size");
    echo $exp_data;
    exit;
}

if ($_POST) {
    unset($input_errors);
    $input_errors = array();
    $pconfig = $_POST;

    /* input validation */
    if ($pconfig['method'] == "existing") {
        $reqdfields = explode(" ", "descr cert");
        $reqdfieldsn = array(
                gettext("Descriptive name"),
                gettext("Certificate data"));
        if ($_POST['cert'] && (!strstr($_POST['cert'], "BEGIN CERTIFICATE") || !strstr($_POST['cert'], "END CERTIFICATE"))) {
            $input_errors[] = gettext("This certificate does not appear to be valid.");
        }
        if ($_POST['key'] && strstr($_POST['key'], "ENCRYPTED")) {
            $input_errors[] = gettext("Encrypted private keys are not yet supported.");
        }
    }
    if ($pconfig['method'] == "internal") {
        $reqdfields = explode(
            " ",
            "descr keylen lifetime dn_country dn_state dn_city ".
            "dn_organization dn_email dn_commonname"
        );
        $reqdfieldsn = array(
                gettext("Descriptive name"),
                gettext("Key length"),
                gettext("Lifetime"),
                gettext("Distinguished name Country Code"),
                gettext("Distinguished name State or Province"),
                gettext("Distinguished name City"),
                gettext("Distinguished name Organization"),
                gettext("Distinguished name Email Address"),
                gettext("Distinguished name Common Name"));
    }
    if ($pconfig['method'] == "intermediate") {
        $reqdfields = explode(
            " ",
            "descr caref keylen lifetime dn_country dn_state dn_city ".
            "dn_organization dn_email dn_commonname"
        );
        $reqdfieldsn = array(
                gettext("Descriptive name"),
                gettext("Signing Certificate Authority"),
                gettext("Key length"),
                gettext("Lifetime"),
                gettext("Distinguished name Country Code"),
                gettext("Distinguished name State or Province"),
                gettext("Distinguished name City"),
                gettext("Distinguished name Organization"),
                gettext("Distinguished name Email Address"),
                gettext("Distinguished name Common Name"));
    }

    do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);
    if ($pconfig['method'] != "existing") {
        /* Make sure we do not have invalid characters in the fields for the certificate */
        for ($i = 0; $i < count($reqdfields); $i++) {
            if ($reqdfields[$i] == 'dn_email') {
                if (preg_match("/[\!\#\$\%\^\(\)\~\?\>\<\&\/\\\,\"\']/", $_POST["dn_email"])) {
                    array_push($input_errors, "The field 'Distinguished name Email Address' contains invalid characters.");
                }
            } elseif ($reqdfields[$i] == 'dn_commonname') {
                if (preg_match("/[\!\@\#\$\%\^\(\)\~\?\>\<\&\/\\\,\"\']/", $_POST["dn_commonname"])) {
                    array_push($input_errors, "The field 'Distinguished name Common Name' contains invalid characters.");
                }
            } elseif (($reqdfields[$i] != "descr") && preg_match("/[\!\@\#\$\%\^\(\)\~\?\>\<\&\/\\\,\.\"\']/", $_POST["$reqdfields[$i]"])) {
                array_push($input_errors, "The field '" . $reqdfieldsn[$i] . "' contains invalid characters.");
            }
        }
        if (!in_array($_POST["keylen"], $ca_keylens)) {
            array_push($input_errors, gettext("Please select a valid Key Length."));
        }
        if (!in_array($_POST["digest_alg"], $openssl_digest_algs)) {
            array_push($input_errors, gettext("Please select a valid Digest Algorithm."));
        }
    }

    /* if this is an AJAX caller then handle via JSON */
    if (isAjax() && is_array($input_errors)) {
        input_errors2Ajax($input_errors);
        exit;
    }

    /* save modifications */
    if (!$input_errors) {
        $ca = array();
        if (!isset($pconfig['refid']) || empty($pconfig['refid'])) {
            $ca['refid'] = uniqid();
        } else {
            $ca['refid'] = $pconfig['refid'];
        }

        if (isset($id) && $a_ca[$id]) {
            $ca = $a_ca[$id];
        }

        if (isset($pconfig['descr'])) {
            $ca['descr'] = $pconfig['descr'];
        } else {
            $ca['descr'] = null;
        }

        if (isset($_POST['edit']) && $_POST['edit'] == "edit") {
            $ca['descr']  = $pconfig['descr'];
            $ca['refid']  = $pconfig['refid'];
            $ca['serial'] = $pconfig['serial'];
            $ca['crt']    = base64_encode($pconfig['cert']);
            if (!empty($pconfig['key'])) {
                $ca['prv']    = base64_encode($pconfig['key']);
            }
        } else {
            $old_err_level = error_reporting(0); /* otherwise openssl_ functions throw warings directly to a page screwing menu tab */
            if ($pconfig['method'] == "existing") {
                ca_import($ca, $pconfig['cert'], $pconfig['key'], $pconfig['serial']);
            } elseif ($pconfig['method'] == "internal") {
                $dn = array(
                    'countryName' => $pconfig['dn_country'],
                    'stateOrProvinceName' => $pconfig['dn_state'],
                    'localityName' => $pconfig['dn_city'],
                    'organizationName' => $pconfig['dn_organization'],
                    'emailAddress' => $pconfig['dn_email'],
                    'commonName' => $pconfig['dn_commonname']);
                if (!ca_create($ca, $pconfig['keylen'], $pconfig['lifetime'], $dn, $pconfig['digest_alg'])) {
                    while ($ssl_err = openssl_error_string()) {
                        $input_errors = array();
                        array_push($input_errors, "openssl library returns: " . $ssl_err);
                    }
                }
            } elseif ($pconfig['method'] == "intermediate") {
                $dn = array(
                    'countryName' => $pconfig['dn_country'],
                    'stateOrProvinceName' => $pconfig['dn_state'],
                    'localityName' => $pconfig['dn_city'],
                    'organizationName' => $pconfig['dn_organization'],
                    'emailAddress' => $pconfig['dn_email'],
                    'commonName' => $pconfig['dn_commonname']);
                if (!ca_inter_create($ca, $pconfig['keylen'], $pconfig['lifetime'], $dn, $pconfig['caref'], $pconfig['digest_alg'])) {
                    while ($ssl_err = openssl_error_string()) {
                        $input_errors = array();
                        array_push($input_errors, "openssl library returns: " . $ssl_err);
                    }
                }
            }
            error_reporting($old_err_level);
        }

        if (isset($id) && $a_ca[$id]) {
            $a_ca[$id] = $ca;
        } else {
            $a_ca[] = $ca;
        }

        if (!$input_errors) {
            write_config();
            unset($input_errors);
        }

//		redirectHeader("system_camanager.php");
    }
}
include("head.inc");

$main_buttons = array(
    array('label'=>gettext("add or import ca"), 'href'=>'system_camanager.php?act=new'),
);


?>

<body>

<?php include("fbegin.inc"); ?>

<script type="text/javascript">
//<![CDATA[
function method_change() {

	method = document.iform.method.selectedIndex;

	switch (method) {
		case 0:
			document.getElementById("existing").style.display="";
			document.getElementById("internal").style.display="none";
			document.getElementById("intermediate").style.display="none";
			break;
		case 1:
			document.getElementById("existing").style.display="none";
			document.getElementById("internal").style.display="";
			document.getElementById("intermediate").style.display="none";
			break;
		case 2:
			document.getElementById("existing").style.display="none";
			document.getElementById("internal").style.display="";
			document.getElementById("intermediate").style.display="";
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

                <div class="content-box tab-content table-responsive" style="overflow: auto;">

				<?php if ($act == "new" || $act == "edit" || $act == gettext("Save") || isset($input_errors)) :
?>

				<form action="system_camanager.php" method="post" name="iform" id="iform" class="table table-striped">

					<?php if ($act == "edit") :
?>
					    <input type="hidden" name="edit" value="edit" id="edit" />
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>" id="id" />
                            <input type="hidden" name="refid" value="<?php echo $pconfig['refid']; ?>" id="refid" />
					<?php
endif; ?>

					<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="main area" class="table table-striped">
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Descriptive name");?></td>
							<td width="78%" class="vtable">
								<input name="descr" type="text" class="formfld unknown" id="descr" size="20" value="<?php if (isset($pconfig['descr'])) echo htmlspecialchars($pconfig['descr']);?>"/>
							</td>
						</tr>

						<?php if (!isset($id) || $act == "edit") :
?>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Method");?></td>
							<td width="78%" class="vtable">
								<select name='method' id='method' class="selectpicker" data-style="btn-default" onchange='method_change()'>
								<?php
                                foreach ($ca_methods as $method => $desc) :
                                    $selected = "";
                                    if (isset($pconfig['method']) && $pconfig['method'] == $method) {
                                        $selected = " selected=\"selected\"";
                                    }
                                ?>
                                <option value="<?=$method;
?>"<?=$selected;
?>><?=$desc;?></option>
								<?php
                                endforeach; ?>
								</select>
							</td>
						</tr>
						<?php
endif; ?>

					</table>

					<table width="100%" border="0" cellpadding="6" cellspacing="0" id="existing" summary="existing" class="table table-striped">
						<thead>
							<tr>
								<th colspan="2" valign="top" class="listtopic"><?=gettext("Existing Certificate Authority");?></th>
							</tr>
						</thead>

                            <tbody>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Certificate data");?></td>
							<td width="78%" class="vtable">
								<textarea name="cert" id="cert" cols="65" rows="7" class="formfld_cert"><?php if (isset($pconfig['cert'])) echo htmlspecialchars($pconfig['cert']);?></textarea>
								<br />
								<?=gettext("Paste a certificate in X.509 PEM format here.");?>
							</td>
						</tr>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Certificate Private Key");
?><br /><?=gettext("(optional)");?></td>
							<td width="78%" class="vtable">
								<textarea name="key" id="key" cols="65" rows="7" class="formfld_cert"><?php if (isset($pconfig['key'])) echo htmlspecialchars($pconfig['key']);?></textarea>
								<br />
								<?=gettext("Paste the private key for the above certificate here. This is optional in most cases, but required if you need to generate a Certificate Revocation List (CRL).");?>
							</td>
						</tr>

					<?php if (!isset($id) || $act == "edit") :
?>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Serial for next certificate");?></td>
							<td width="78%" class="vtable">
								<input name="serial" type="text" class="formfld unknown" id="serial" size="20" value="<?php if(isset($pconfig['serial'])) echo htmlspecialchars($pconfig['serial']);?>"/>
								<br /><?=gettext("Enter a decimal number to be used as the serial number for the next certificate to be created using this CA.");?>
							</td>
						</tr>
					<?php
endif; ?>

                            </tbody>

					</table>

					<table width="100%" border="0" cellpadding="6" cellspacing="0" id="internal" summary="internal" class="table table-striped">
						<thead>
							<tr>
								<th colspan="2" valign="top" class="listtopic"><?=gettext("Internal Certificate Authority");?></th>
							</tr>
						</thead>

						<tbody>
						<tr id='intermediate'>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Signing Certificate Authority");?></td>
							<td width="78%" class="vtable">
                                    <select name='caref' id='caref' class="selectpicker" onchange='internalca_change()'>
                                    <?php
                                    foreach ($a_ca as $ca) :
                                        if (!$ca['prv']) {
                                            continue;
                                        }
                                        $selected = "";
                                        if (isset($pconfig['caref']) && isset($ca['refid']) && $pconfig['caref'] == $ca['refid']) {
                                            $selected = " selected=\"selected\"";
                                        }
                                    ?>
                                    <option value="<?=$ca['refid'];
?>"<?=$selected;
?>><?=$ca['descr'];?></option>
                                    <?php
                                    endforeach; ?>
                                    </select>
							</td>
						</tr>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Key length");?></td>
							<td width="78%" class="vtable">
								<select name='keylen' id='keylen' class="selectpicker">
								<?php
                                foreach ($ca_keylens as $len) :
                                    $selected = "";
                                    if (isset($pconfig['keylen']) && $pconfig['keylen'] == $len) {
                                        $selected = " selected=\"selected\"";
                                    }
                                ?>
                                <option value="<?=$len;
?>"<?=$selected;
?>><?=$len;?></option>
								<?php
                                endforeach; ?>
								</select>
								<?=gettext("bits");?>
							</td>
						</tr>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Digest Algorithm");?></td>
							<td width="78%" class="vtable">
								<select name='digest_alg' id='digest_alg' class="selectpicker">
								<?php
                                foreach ($openssl_digest_algs as $digest_alg) :
                                    $selected = "";
                                    if (isset($pconfig['digest_alg']) && $pconfig['digest_alg'] == $digest_alg) {
                                        $selected = " selected=\"selected\"";
                                    }
                                ?>
                                <option value="<?=$digest_alg;
?>"<?=$selected;
?>><?=strtoupper($digest_alg);?></option>
								<?php
                                endforeach; ?>
								</select>
								<br /><?= gettext("NOTE: It is recommended to use an algorithm stronger than SHA1 when possible.") ?>
							</td>
						</tr>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Lifetime");?></td>
							<td width="78%" class="vtable">
								<input name="lifetime" type="text" class="formfld unknown" id="lifetime" size="5" value="<?php if (isset($pconfig['lifetime'])) echo htmlspecialchars($pconfig['lifetime']);?>"/>
								<?=gettext("days");?>
							</td>
						</tr>
						<tr>
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Distinguished name");?></td>
							<td width="78%" class="vtable">
								<table border="0" cellspacing="0" cellpadding="2" summary="name">
									<tr>
										<td align="right"><?=gettext("Country Code");?> : &nbsp;</td>
										<td align="left">
											<select name='dn_country' class="selectpicker">
											<?php
                                            $dn_cc = get_country_codes();
                                            foreach ($dn_cc as $cc => $cn) {
                                                $selected = '';
                                                if (isset($pconfig['dn_country']) && $pconfig['dn_country'] == $cc) {
                                                    $selected = ' selected="selected"';
                                                }
                                                print "<option value=\"$cc\"$selected>$cc ($cn)</option>";
                                            }
                                            ?>
											</select>
										</td>
									</tr>
									<tr>
										<td align="right"><?=gettext("State or Province");?> : &nbsp;</td>
										<td align="left">
											<input name="dn_state" type="text" class="formfld unknown" size="40" value="<?php if (isset($pconfig['dn_state'])) echo htmlspecialchars($pconfig['dn_state']);?>"/>
											&nbsp;
											<em><?=gettext("ex:");?></em>
											&nbsp;
											<?=gettext("Sachsen");?>
										</td>
									</tr>
									<tr>
										<td align="right"><?=gettext("City");?> : &nbsp;</td>
										<td align="left">
											<input name="dn_city" type="text" class="formfld unknown" size="40" value="<?php if (isset($pconfig['dn_city'])) echo htmlspecialchars($pconfig['dn_city']);?>"/>
											&nbsp;
											<em><?=gettext("ex:");?></em>
											&nbsp;
											<?=gettext("Leipzig");?>
										</td>
									</tr>
									<tr>
										<td align="right"><?=gettext("Organization");?> : &nbsp;</td>
										<td align="left">
											<input name="dn_organization" type="text" class="formfld unknown" size="40" value="<?php if (isset($pconfig['dn_organization'])) echo htmlspecialchars($pconfig['dn_organization']);?>"/>
											&nbsp;
											<em><?=gettext("ex:");?></em>
											&nbsp;
											<?=gettext("My Company Inc");?>
										</td>
									</tr>
									<tr>
										<td align="right"><?=gettext("Email Address");?> : &nbsp;</td>
										<td align="left">
											<input name="dn_email" type="text" class="formfld unknown" size="25" value="<?php if (isset($pconfig['dn_email'])) echo htmlspecialchars($pconfig['dn_email']);?>"/>
											&nbsp;
											<em><?=gettext("ex:");?></em>
											&nbsp;
											<?=gettext("admin@mycompany.com");?>
										</td>
									</tr>
									<tr>
										<td align="right"><?=gettext("Common Name");?> : &nbsp;</td>
										<td align="left">
											<input name="dn_commonname" type="text" class="formfld unknown" size="25" value="<?php if (isset($pconfig['dn_commonname'])) echo htmlspecialchars($pconfig['dn_commonname']);?>"/>
											&nbsp;
											<em><?=gettext("ex:");?></em>
											&nbsp;
											<?=gettext("internal-ca");?>
										</td>
									</tr>
								</table>
							</td>
						</tr>

						</tbody>
					</table>

					<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="save" class="table">
						<tr>
							<td width="22%" valign="top">&nbsp;</td>
							<td width="78%">
								<input id="submit" name="save" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" />
								<?php if (isset($id) && $a_ca[$id]) :
?>
								<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>" />
								<?php
endif;?>
							</td>
						</tr>
					</table>
				</form>

				<?php
else :
?>

				<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="" class="table table-striped">
					<thead>
						<tr>
							<th width="18%" class="listhdrr"><?=gettext("Name");?></th>
							<th width="10%" class="listhdrr"><?=gettext("Internal");?></th>
							<th width="10%" class="listhdrr"><?=gettext("Issuer");?></th>
							<th width="10%" class="listhdrr"><?=gettext("Certificates");?></th>
							<th width="40%" class="listhdrr"><?=gettext("Distinguished Name");?></th>
							<th width="12%" class="list"></th>
						</tr>
					</thead>

					<tbody>
					<?php
                        $i = 0;
                    foreach ($a_ca as $ca) :
                        $name = htmlspecialchars($ca['descr']);
                        $subj = cert_get_subject($ca['crt']);
                        $issuer = cert_get_issuer($ca['crt']);
                        list($startdate, $enddate) = cert_get_dates($ca['crt']);
                        if ($subj == $issuer) {
                            $issuer_name = "<em>" . gettext("self-signed") . "</em>";
                        } else {
                            $issuer_name = "<em>" . gettext("external") . "</em>";
                        }
                        $subj = htmlspecialchars($subj);
                        $issuer = htmlspecialchars($issuer);
                        $certcount = 0;

                        if (isset($ca['caref'])) {
                            $issuer_ca = lookup_ca($ca['caref']);
                            if ($issuer_ca) {
                                $issuer_name = $issuer_ca['descr'];
                            }
                            foreach ($a_cert as $cert) {
                                if ($cert['caref'] == $ca['refid']) {
                                    $certcount++;
                                }
                            }
                            foreach ($a_ca as $cert) {
                                if ($cert['caref'] == $ca['refid']) {
                                    $certcount++;
                                }
                            }
                        }

                        // TODO : Need gray certificate icon

                        if ($ca['prv']) {
                            $caimg = "/themes/{$g['theme']}/images/icons/icon_frmfld_cert.png";
                            $internal = "YES";

                        } else {
                            $caimg = "/themes/{$g['theme']}/images/icons/icon_frmfld_cert.png";
                            $internal = "NO";
                        }
                    ?>
					<tr>
                    <td class="listlr">
                        <?=$name;?>
                    </td>
                    <td class="listr"><?=$internal;?>&nbsp;</td>
                    <td class="listr"><?=$issuer_name;?>&nbsp;</td>
                    <td class="listr"><?=$certcount;?>&nbsp;</td>
                    <td class="listr"><?=$subj;?><br />
                        <table width="100%" style="font-size: 9px" summary="valid">
                            <tr>
                                <td width="10%">&nbsp;</td>
                                <td width="20%"><?=gettext("Valid From")?>:</td>
                                <td width="70%"><?= $startdate ?></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td><?=gettext("Valid Until")?>:</td>
                                <td><?= $enddate ?></td>
                            </tr>
                        </table>
                    </td>
                    <td valign="middle" class="list nowrap">
                        <a href="system_camanager.php?act=edit&amp;id=<?=$i;
?>" data-toggle="tooltip" data-placement="left" title="<?=gettext("edit CA");
?>" alt="<?=gettext("edit CA");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                        <a href="system_camanager.php?act=exp&amp;id=<?=$i;
?>" data-toggle="tooltip" data-placement="left" title="<?=gettext("export CA cert");
?>" alt="<?=gettext("export CA cert");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-download"></span></a>
                        <?php if ($ca['prv']) :
?>
							<a href="system_camanager.php?act=expkey&amp;id=<?=$i;
?>" data-toggle="tooltip" data-placement="left" title="<?=gettext("export CA private key");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-download"></span></a>
							<?php
endif; ?>
                        <a href="system_camanager.php?act=del&amp;id=<?=$i;
?>" data-toggle="tooltip" data-placement="left" onclick="return confirm('<?=gettext("Do you really want to delete this Certificate Authority and its CRLs, and unreference any associated certificates?");
?>')" title="<?=gettext("delete ca");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-remove"></span></a>
                    </td>
					</tr>
					<?php
                        $i++;
                    endforeach;
                    ?>


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
