<?php
/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2011 Ermal Luci
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
require_once("functions.inc");
require_once("filter.inc");
require_once("captiveportal.inc");

$pgtitle = array(gettext("Services"),gettext("Captive portal"),gettext("Edit Zones"));
$shortcut_section = "captiveportal";

if (!is_array($config['captiveportal'])) {
    $config['captiveportal'] = array();
}
$a_cp =& $config['captiveportal'];

if ($_POST) {
    unset($input_errors);
    $pconfig = $_POST;

    /* input validation */
    $reqdfields = explode(" ", "zone");
    $reqdfieldsn = array(gettext("Zone name"));

    do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

    if (preg_match('/[^A-Za-z0-9_]/', $_POST['zone'])) {
        $input_errors[] = gettext("The zone name can only contain letters, digits, and underscores (_).");
    }

    foreach ($a_cp as $cpkey => $cpent) {
        if ($cpent['zone'] == $_POST['zone']) {
            $input_errors[] = sprintf("[%s] %s.", $_POST['zone'], gettext("already exists"));
            break;
        }
    }

    if (!$input_errors) {
        $cpzone = strtolower($_POST['zone']);
        $a_cp[$cpzone] = array();
        $a_cp[$cpzone]['zone'] = str_replace(" ", "", $_POST['zone']);
        $a_cp[$cpzone]['descr'] = $_POST['descr'];
        $a_cp[$cpzone]['localauth_priv'] = true;
        write_config();

        header("Location: services_captiveportal.php?zone={$cpzone}");
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

            <section class="col-xs-12">

                <div class="content-box">
				    <header class="content-box-head container-fluid">
				        <h3><?=gettext("Edit Captiveportal Zones");?></h3>
				    </header>

				    <div class="content-box-main">

						<div class="table-responsive">

							<form action="services_captiveportal_zones_edit.php" method="post" name="iform" id="iform">
							<table class="table table-striped table-sort">
								<tr>
									<td width="22%" valign="top" class="vncellreq"><?=gettext("Zone name"); ?></td>
									<td width="78%" class="vtable">
										<input name="zone" type="text" class="form-control unknown" id="zone" size="64" />
										<br />
										<span class="vexpl"><?=gettext("Zone name. Can only contain letters, digits, and underscores (_)."); ?></span>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top" class="vncell"><?=gettext("Description"); ?></td>
									<td width="78%" class="vtable">
										<input name="descr" type="text" class="form-control unknown" id="descr" size="40" />
										<br />
										<span class="vexpl"><?=gettext("You may enter a description here for your reference (not parsed)"); ?>.</span>
									</td>
								</tr>
								<tr>
									<td width="22%" valign="top">&nbsp;</td>
									<td width="78%">
										<input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Continue"); ?>" />
									</td>
								</tr>
							</table>
						</form>
						</div>
				    </div>
				</div>
			</section>
		</div>
	</div>
</section>

<?php include("foot.inc");
