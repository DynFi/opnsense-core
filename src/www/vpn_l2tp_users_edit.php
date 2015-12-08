<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2006 Scott Ullrich (sullrich@gmail.com)
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

function l2tpusercmp($a, $b)
{
    return  strcasecmp($a['name'], $b['name']);
}

function l2tp_users_sort()
{
        global  $config;

    if (!is_array($config['l2tp']['user'])) {
            return;
    }

        usort($config['l2tp']['user'], "l2tpusercmp");
}

require_once("guiconfig.inc");
require_once("vpn.inc");

$referer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/vpn_l2tp_users.php');

if (!is_array($config['l2tp']['user'])) {
    $config['l2tp']['user'] = array();
}
$a_secret = &$config['l2tp']['user'];

if (is_numericint($_GET['id'])) {
    $id = $_GET['id'];
}
if (isset($_POST['id']) && is_numericint($_POST['id'])) {
    $id = $_POST['id'];
}

if (isset($id) && $a_secret[$id]) {
    $pconfig['usernamefld'] = $a_secret[$id]['name'];
    $pconfig['ip'] = $a_secret[$id]['ip'];
}

if ($_POST) {
    unset($input_errors);
    $pconfig = $_POST;

    /* input validation */
    if (isset($id) && ($a_secret[$id])) {
        $reqdfields = explode(" ", "usernamefld");
        $reqdfieldsn = array(gettext("Username"));
    } else {
        $reqdfields = explode(" ", "usernamefld passwordfld");
        $reqdfieldsn = array(gettext("Username"),gettext("Password"));
    }

    do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

    if (preg_match("/[^a-zA-Z0-9\.\-_]/", $_POST['usernamefld'])) {
        $input_errors[] = gettext("The username contains invalid characters.");
    }

    if (preg_match("/[^a-zA-Z0-9\.\-_]/", $_POST['passwordfld'])) {
        $input_errors[] = gettext("The password contains invalid characters.");
    }

    if (($_POST['passwordfld']) && ($_POST['passwordfld'] != $_POST['password2'])) {
        $input_errors[] = gettext("The passwords do not match.");
    }
    if (($_POST['ip'] && !is_ipaddr($_POST['ip']))) {
        $input_errors[] = gettext("The IP address entered is not valid.");
    }

    if (!$input_errors && !(isset($id) && $a_secret[$id])) {
        /* make sure there are no dupes */
        foreach ($a_secret as $secretent) {
            if ($secretent['name'] == $_POST['usernamefld']) {
                $input_errors[] = gettext("Another entry with the same username already exists.");
                break;
            }
        }
    }

    if (!$input_errors) {
        if (isset($id) && $a_secret[$id]) {
            $secretent = $a_secret[$id];
        }

        $secretent['name'] = $_POST['usernamefld'];
        $secretent['ip'] = $_POST['ip'];

        if ($_POST['passwordfld']) {
            $secretent['password'] = $_POST['passwordfld'];
        }

        if (isset($id) && $a_secret[$id]) {
            $a_secret[$id] = $secretent;
        } else {
            $a_secret[] = $secretent;
        }
        l2tp_users_sort();

        write_config();

        $retval = vpn_l2tp_configure();

        header("Location: vpn_l2tp_users.php");

        exit;
    }
}

include("head.inc");
?>

<body>
<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) {
                    print_input_errors($input_errors);
} ?>

				<div id="inputerrors"></div>


			    <section class="col-xs-12">

					<div class="tab-content content-box col-xs-12">

							<form action="vpn_l2tp_users_edit.php" method="post" name="iform" id="iform">

							 <div class="table-responsive">
								<table class="table table-striped table-sort">
									<tr>
					                  <td width="22%" valign="top" class="vncellreq"><?=gettext("Username");?></td>
					                  <td width="78%" class="vtable">
										<input name="usernamefld" type="text" class="form-control user" id="usernamefld" size="20" value="<?=htmlspecialchars($pconfig['usernamefld']);?>" />
					                  </td>
					                </tr>
					                <tr>
					                  <td width="22%" valign="top" class="vncellreq"><?=gettext("Password");?></td>
					                  <td width="78%" class="vtable">
					                    <input name="passwordfld" type="password" class="form-control pwd" id="passwordfld" size="20" />
					                    <br /><input name="password2" type="password" class="form-control pwd" id="password2" size="20" />
					                    &nbsp;(<?=gettext("confirmation");?>)<?php if (isset($id) && $a_secret[$id]) :
?><br />
					                    <p class="text-muted"><em><small><?=gettext("If you want to change the users password, enter it here twice.");?></small></em></p>
					                    <?php
endif; ?></td>
					                </tr>
					                <tr>
					                  <td width="22%" valign="top" class="vncell"><?=gettext("IP address");?></td>
					                  <td width="78%" class="vtable">
					                    <input name="ip" type="text" class="form-control unknown" id="ip" size="20" value="<?=htmlspecialchars($pconfig['ip']);?>" />
					                    <p class="text-muted"><em><small><?=gettext("If you want the user to be assigned a specific IP address, enter it here.");?></small></em></p></td>
					                </tr>
					                <tr>
					                  <td width="22%" valign="top">&nbsp;</td>
					                  <td width="78%">
					                    <input id="submit" name="Submit" type="submit" class="btn btn-primary" value="<?=gettext('Save');?>" />
					                    <input type="button" class="btn btn-default" value="<?=gettext("Cancel");
?>" onclick="window.location.href='<?=$referer;?>'" />
					                    <?php if (isset($id) && $a_secret[$id]) :
?>
					                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>" />
					                    <?php
endif; ?>
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
