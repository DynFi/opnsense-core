<?php
/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2004-2010 Scott Ullrich <sullrich@gmail.com>
	Copyright (C) 2005 Colin Smith
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

ini_set('max_execution_time', '0');

require("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("pkg-utils.inc");

global $static_output;

$static_output = "";
$static_status = "";
$sendto = "output";

$pgtitle = array(gettext("System"),gettext("Package Manager"),gettext("Install Package"));
include("head.inc");

if ($_POST) {
	if (isset($_POST['pkgcancel']) || (empty($_POST['id']) && $_POST['mode'] != 'reinstallall')) {
		header("Location: pkg_mgr_installed.php");
		return;
	}
} else if ($_GET) {
	switch ($_GET['mode']) {
	case 'reinstallall':
	case 'showlog':
		break;
	case 'installedinfo':
	case 'reinstallxml':
        case 'reinstallpkg':
	case 'delete':
		if (empty($_GET['pkg'])) {
			header("Location: pkg_mgr_installed.php");
			return;
		}
		break;
	default:
		if (empty($_GET['id'])) {
			header("Location: pkg_mgr_installed.php");
			return;
		}
		break;
	}
}

?>

<body>
<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php
				/* Print package server mismatch warning. See https://redmine.pfsense.org/issues/484 */
				/*if (!verify_all_package_servers())
					print_info_box(package_server_mismatch_message());*/

				/* Print package server SSL warning. See https://redmine.pfsense.org/issues/484 */
				if (check_package_server_ssl() === false)
					print_info_box(package_server_ssl_failure_message()); ?>

			    <section class="col-xs-12">


					<?php
							$tab_array = array();
							$tab_array[] = array(gettext("Available packages"), false, "pkg_mgr.php");
							$tab_array[] = array(gettext("Installed packages"), false, "pkg_mgr_installed.php");
							$tab_array[] = array(gettext("Package Installer"), true, "");
							display_top_tabs($tab_array);
					?>

					<div class="tab-content content-box col-xs-12">

				    <div class="container-fluid">
						<form action="pkg_mgr_install.php" method="post" name="iform">
		                        <div class="table-responsive">
			                        <table class="table table-striped table-sort">


<?php if ((empty($_GET['mode']) && $_GET['id']) || (!empty($_GET['mode']) && (!empty($_GET['pkg']) || $_GET['mode'] == 'reinstallall') && ($_GET['mode'] != 'installedinfo' && $_GET['mode'] != 'showlog'))):
	if (empty($_GET['mode']) && $_GET['id']) {
		$pkgname = str_replace(array("<", ">", ";", "&", "'", '"', '.', '/'), "", htmlspecialchars_decode($_GET['id'], ENT_QUOTES | ENT_HTML401));
		$pkgmode = 'installed';
	} else if (!empty($_GET['mode']) && !empty($_GET['pkg'])) {
		$pkgname = str_replace(array("<", ">", ";", "&", "'", '"', '.', '/'), "", htmlspecialchars_decode($_GET['pkg'], ENT_QUOTES | ENT_HTML401));
		$pkgmode = str_replace(array("<", ">", ";", "&", "'", '"', '.', '/'), "", htmlspecialchars_decode($_GET['mode'], ENT_QUOTES | ENT_HTML401));
	} else if ($_GET['mode'] == 'reinstallall') {
		$pkgmode = 'reinstallall';
	}
	switch ($pkgmode) {
	case 'reinstallall':
		$pkgname = 'All packages';
		$pkgtxt = 'reinstalled';
		break;
	case 'reinstallxml':
	case 'reinstallpkg':
		$pkgtxt = 'reinstalled';
		break;
	case 'delete':
		$pkgtxt = 'deleted';
		break;
	default:
		$pkgtxt = $pkgmode;
		break;
	}
?>
									<tr>
										<td class="tabcont" align="center">
											<table style="height:15;colspacing:0" width="420" border="0" cellpadding="0" cellspacing="0" summary="images">
												<tr>
													<td class="tabcont" align="center">Package: <b><?=$pkgname;?></b> will be <?=$pkgtxt;?>.<br/>
													Please confirm the action.<br/>
													</td>
													<td class="tabcont" align="center">
														<input type="hidden" name="id" value="<?=$pkgname;?>" />
														<input type="hidden" name="mode" value="<?=$pkgmode;?>" />
														<input type="submit" name="pkgconfirm" id="pkgconfirm" value="Confirm" class="btn btn-primary"/>
														<input type="submit" name="pkgcancel" id="pkgcancel" value="Cancel" class="btn btn-default"/>
													</td>
												</tr>
											</table>
										</td>
									</tr>
					<?php endif; if (!empty($_POST['id']) || $_GET['mode'] == 'showlog' || ($_GET['mode'] == 'installedinfo' && !empty($_GET['pkg']))): ?>
									<tr>
										<td class="tabcont" align="center">

											<div class="progress">
											  <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 5%;">
											    <span class="sr-only">5% Complete</span>
											  </div>
											</div>


											<br />
											<!-- status box -->
											<textarea cols="80" rows="1" name="status" id="status" wrap="hard"><?=gettext("Beginning package installation.");?></textarea>
											<br />
											<!-- command output box -->
											<textarea cols="80" rows="35" name="output" id="output" wrap="hard"></textarea>
										</td>
									</tr>
					<?php endif; ?>
								</table>
		                        </div>
						</form>
				    </div>
					</div>
			    </section>
			</div>
		</div>
	</section>


<?php

ob_flush();

if ($_GET) {
	$pkgname = str_replace(array("<", ">", ";", "&", "'", '"', '.', '/'), "", htmlspecialchars_decode($_GET['pkg'], ENT_QUOTES | ENT_HTML401));
	switch($_GET['mode']) {
	case 'showlog':
		if (strpos($pkgname, ".")) {
			update_output_window(gettext("Something is wrong on the request."));
		} else if (file_exists("/tmp/pkg_mgr_{$pkgname}.log"))
			update_output_window(@file_get_contents("/tmp/pkg_mgr_{$pkgname}.log"));
		else
			update_output_window(gettext("Log was not retrievable."));
		break;
	case 'installedinfo':
		if (file_exists("/tmp/{$pkgname}.info")) {
			$status = @file_get_contents("/tmp/{$pkgname}.info");
			update_status("{$pkgname} " . gettext("installation completed."));
			update_output_window($status);
		} else
			update_output_window(sprintf(gettext("Could not find %s."), $pkgname));
		break;
	default:
		break;
	}
} else if ($_POST) {
	$pkgid = str_replace(array("<", ">", ";", "&", "'", '"', '.', '/'), "", htmlspecialchars_decode($_POST['id'], ENT_QUOTES | ENT_HTML401));

	/* Write out configuration to create a backup prior to pkg install. */
	write_config(gettext("Creating restore point before package installation."));

	switch ($_POST['mode']) {
		case 'delete':
			uninstall_package($pkgid);
			update_status(gettext("Package deleted."));
			$static_output .= "\n" . gettext("Package deleted.");
			update_output_window($static_output);
			filter_configure();
			break;
		case 'reinstallxml':
			pkg_fetch_config_file($pkgid);
			pkg_fetch_additional_files($pkgid);
		case 'reinstallpkg':
			delete_package_xml($pkgid);
			if (install_package($pkgid) < 0) {
				update_status(gettext("Package reinstallation failed."));
				$static_output .= "\n" . gettext("Package reinstallation failed.");
				update_output_window($static_output);
			} else {
				update_status(gettext("Package reinstalled."));
				$static_output .= "\n" . gettext("Package reinstalled.");
				update_output_window($static_output);
				filter_configure();
			}
			@file_put_contents("/tmp/{$pkgid}.info", $static_output);
			$pkgid = htmlspecialchars($pkgid);
			echo "<script type='text/javascript'>document.location=\"pkg_mgr_install.php?mode=installedinfo&pkg={$pkgid}\";</script>";
			break;
		case 'reinstallall':
			if (is_array($config['installedpackages']) && is_array($config['installedpackages']['package'])) {
				$todo = array();
				foreach($config['installedpackages']['package'] as $package)
					$todo[] = array('name' => $package['name'], 'version' => $package['version']);
				foreach($todo as $pkgtodo) {
					$static_output = "";
					if($pkgtodo['name']) {
						update_output_window($static_output);
						uninstall_package($pkgtodo['name']);
						install_package($pkgtodo['name']);
					}
				}
				update_status(gettext("All packages reinstalled."));
				$static_output .= "\n" . gettext("All packages reinstalled.");
				update_output_window($static_output);
				filter_configure();
			} else
				update_output_window(gettext("No packages are installed."));
			break;
		case 'installed':
		default:
			$status = install_package($pkgid);
			if($status == -1) {
				update_status(gettext("Installation of") . " {$pkgid} " . gettext("FAILED!"));
				$static_output .= "\n" . gettext("Installation halted.");
				update_output_window($static_output);
			} else {
				$status_a = gettext(sprintf("Installation of %s completed.", $pkgid));
				update_status($status_a);
				$status = get_after_install_info($pkgid);
				if($status)
					$static_output .= "\n" . gettext("Installation completed.") . "\n{$pkgid} " . gettext("setup instructions") . ":\n{$status}";
				else
					$static_output .= "\n" . gettext("Installation completed.   Please check to make sure that the package is configured from the respective menu then start the package.");

				@file_put_contents("/tmp/{$pkgid}.info", $static_output);
				echo "<script type='text/javascript'>document.location=\"pkg_mgr_install.php?mode=installedinfo&pkg={$pkgid}\";</script>";
			}
			filter_configure();
			break;
	}

	// Delete all temporary package tarballs and staging areas.
	unlink_if_exists("/tmp/apkg_*");
	rmdir_recursive("/var/tmp/instmp*");

	// close log
	if($fd_log)
		fclose($fd_log);

}
?>


<?php include("foot.inc"); ?>
