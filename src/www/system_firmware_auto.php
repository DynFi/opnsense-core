<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2005, 2008 Scott Ullrich <sullrich@gmail.com>
	Copyright (C) 2003-2004 Manuel Kasper
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

$nocsrf = true;

require_once('guiconfig.inc');
require_once('pfsense-utils.inc');

$curcfg = $config['system']['firmware'];

if(isset($curcfg['alturl']['enable']))
	$updater_url = "{$config['system']['firmware']['alturl']['firmwareurl']}";
else
	$updater_url = $g['update_url'];

if($_POST['backupbeforeupgrade'])
	touch("/tmp/perform_full_backup.txt");

$closehead = false;
$pgtitle = array(gettext("Diagnostics"),gettext("Firmware"),gettext("Auto Update"));
include("head.inc");

?>

<body>

<?php include("fbegin.inc"); ?>

<!-- row -->
<section class="page-content-main">
	<div class="container-fluid">

        <div class="row">
            <?php
		if ($input_errors) print_input_errors($input_errors);
		if ($savemsg) print_info_box($savemsg);
            ?>
            <section class="col-xs-12">

                <? include('system_firmware_tabs.php'); ?>

                <div class="content-box tab-content">

                    <form action="system_firmware_auto.php" method="post">

				<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="outer">
					<tr>
						<td class="tabcont">
							<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="inner">
								<tr>
									<td align="center">
										<table width="420" border="0" cellpadding="0" cellspacing="0" summary="images">

											<tr>
												<td style="background:url('./themes/<?=$g['theme'];?>/images/misc/bar_left.gif')" height="15" width="5">	</td>
												<td>
												<table id="progholder" style="height:15;colspacing:0" width="410" border="0" cellpadding="0" cellspacing="0" summary="">
													<tr><td style="background:url('./themes/<?=$g['theme'];?>/images/misc/bar_gray.gif')" valign="top" align="left">
													<img src="./themes/<?=$g['theme'];?>/images/misc/bar_blue.gif" width="0" height="15" name="progressbar" id="progressbar" alt="" />
													</td></tr>
												</table>
												</td>
												<td style="background:url('./themes/<?=$g['theme'];?>/images/misc/bar_right.gif')" height="15" width="5"></td>
											</tr>
										</table>
										<br />
										<script type="text/javascript">
										//<![CDATA[
										window.onload=function(){
											document.getElementById("status").wrap='hard';
											document.getElementById("output").wrap='hard';
										}
										//]]>
										</script>
										<!-- status box -->
										<textarea cols="90" rows="1" name="status" id="status"><?=gettext("Beginning firmware upgrade"); ?>.</textarea>
										<br />
										<!-- command output box -->
										<textarea cols="90" rows="25" name="output" id="output"></textarea>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
                    </form>

                </div>
            </section>
        </div>
	</div>
</section>


<?php include("foot.inc"); ?>

<?php

update_status(gettext("Downloading current version information") . "...");
$nanosize = "";
if ($g['platform'] == "nanobsd") {
	if (file_exists("/etc/nano_use_vga.txt"))
		$nanosize = "-nanobsd-vga-";
	else
		$nanosize = "-nanobsd-";

	$nanosize .= strtolower(trim(file_get_contents("/etc/nanosize.txt")));
}

@unlink("/tmp/{$g['product_name']}_version");
download_file_with_progress_bar("{$updater_url}/version{$nanosize}", "/tmp/{$g['product_name']}_version");
$latest_version = str_replace("\n", "", @file_get_contents("/tmp/{$g['product_name']}_version"));
if(!$latest_version) {
	update_output_window(gettext("Unable to check for updates."));
	require("fend.inc");
	exit;
} else {
	$current_installed_buildtime = '';	/* XXX zap */
	$current_installed_version = trim(file_get_contents('/usr/local/opnsense/version/opnsense'));
	$latest_version = trim(@file_get_contents("/tmp/{$g['product_name']}_version"));
	$latest_version_pfsense = strtotime($latest_version);
	if(!$latest_version) {
		update_output_window(gettext("Unable to check for updates."));
		require("fend.inc");
		exit;
	} else {
		if (pfs_version_compare($current_installed_buildtime, $current_installed_version, $latest_version) == -1) {
			update_status(gettext("Downloading updates") . "...");
			if ($g['platform'] == "nanobsd") {
				$update_filename = "latest{$nanosize}.img.gz";
			} else {
				$update_filename = "latest.tgz";
			}
			$status = download_file_with_progress_bar("{$updater_url}/{$update_filename}", "{$g['upload_path']}/latest.tgz", "read_body_firmware");
			$status = download_file_with_progress_bar("{$updater_url}/{$update_filename}.sha256", "{$g['upload_path']}/latest.tgz.sha256");
			update_output_window("{$g['product_name']} " . gettext("download complete."));
		} else {
			update_output_window(gettext("You are on the latest version."));
			require("fend.inc");
			exit;
		}
	}
}

/* launch external upgrade helper */
$external_upgrade_helper_text = "/usr/local/etc/rc.firmware ";

if($g['platform'] == "nanobsd")
	$external_upgrade_helper_text .= "pfSenseNanoBSDupgrade ";
else
	$external_upgrade_helper_text .= "pfSenseupgrade ";

$external_upgrade_helper_text .= "{$g['upload_path']}/latest.tgz";

$downloaded_latest_tgz_sha256 = str_replace("\n", "", `/sbin/sha256 -q {$g['upload_path']}/latest.tgz`);
$upgrade_latest_tgz_sha256 = str_replace("\n", "", `/bin/cat {$g['upload_path']}/latest.tgz.sha256 | awk '{ print $4 }'`);

if ($exitstatus) {
	update_status($sig_warning);
	update_output_window(gettext("Update cannot continue.  You can disable this check on the Updater Settings tab."));
	require("fend.inc");
	exit;
}

if (!verify_gzip_file("{$g['upload_path']}/latest.tgz")) {
	update_status(gettext("The image file is corrupt."));
	update_output_window(gettext("Update cannot continue"));
	if (file_exists("{$g['upload_path']}/latest.tgz")) {
		unlink("{$g['upload_path']}/latest.tgz");
	}
	require("fend.inc");
	exit;
}

if($downloaded_latest_tgz_sha256 <> $upgrade_latest_tgz_sha256) {
	update_status(gettext("Downloading complete but sha256 does not match."));
	update_output_window(gettext("Auto upgrade aborted.") . "  \n\n" . gettext("Downloaded SHA256") . ": " . $downloaded_latest_tgz_sha256 . "\n\n" . gettext("Needed SHA256") . ": " . $upgrade_latest_tgz_sha256);
} else {
	update_output_window($g['product_name'] . " " . gettext("is now upgrading.") . "\\n\\n" . gettext("The firewall will reboot once the operation is completed."));
	echo "\n<script type=\"text/javascript\">";
	echo "\n//<![CDATA[";
	echo "\ndocument.progressbar.style.visibility='hidden';";
	echo "\n//]]>";
	echo "\n</script>";
	mwexec_bg($external_upgrade_helper_text);
}

/*
	Helper functions
*/

function read_body_firmware($ch, $string) {
	global $fout, $file_size, $downloaded, $counter, $version, $latest_version, $current_installed_version;
	$length = strlen($string);
	$downloaded += intval($length);
	$downloadProgress = round(100 * (1 - $downloaded / $file_size), 0);
	$downloadProgress = 100 - $downloadProgress;
	$a = $file_size;
	$b = $downloaded;
	$c = $downloadProgress;
	$text  = "  " . gettext("Auto Update Download Status") . "\\n";
	$text .= "----------------------------------------------------\\n";
	$text .= "  " . gettext("Current Version") . " : {$current_installed_version}\\n";
	$text .= "  " . gettext("Latest Version") . "  : {$latest_version}\\n";
	$text .= "  " . gettext("File size") . "       : {$a}\\n";
	$text .= "  " . gettext("Downloaded") . "      : {$b}\\n";
	$text .= "  " . gettext("Percent") . "         : {$c}%\\n";
	$text .= "----------------------------------------------------\\n";
	$counter++;
	if($counter > 150) {
		update_output_window($text);
		update_progress_bar($downloadProgress);
		$counter = 0;
	}
	fwrite($fout, $string);
	return $length;
}

?>
