<?php
/*
    Copyright (C) 2014-2015 Deciso B.V.
    Copyright (C) 2004, 2005 Scott Ullrich
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
       this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INClUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
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
require_once("service-utils.inc");
require_once("shortcuts.inc");

$service_name = '';
if (isset($_GET['service']))
	$service_name = htmlspecialchars($_GET['service']);

if (!empty($service_name)) {
	switch ($_GET['mode']) {
		case "restartservice":
			$savemsg = service_control_restart($service_name, $_GET);
			break;
		case "startservice":
			$savemsg = service_control_start($service_name, $_GET);
			break;
		case "stopservice":
			$savemsg = service_control_stop($service_name, $_GET);
			break;
	}
	sleep(5);
}

$pgtitle = array(gettext("Status"),gettext("Services"));
include("head.inc");

?>

<body>
<?php
include("fbegin.inc");
?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($savemsg)) print_info_box($savemsg); ?>

			    <section class="col-xs-12">

					<div class="content-box">
						<form action="status_services.php" method="post">

						<div class="table-responsive">
							<table class="table table-striped table-sort">
							<thead>
							<tr>
								<td><?=gettext("Service");?></td>
								<td><?=gettext("Description");?></td>
								<td><?=gettext("Status");?></td>
							</tr>
							</thead>
							<tbody>
						<?php

						$services = get_services();

						if (count($services) > 0) {
							uasort($services, "service_name_compare");
							foreach($services as $service) {
								if (empty($service['name']))
									continue;
								echo "<tr><td class=\"listlr\" >" . $service['name'] . "</td>\n";
								echo "<td class=\"listr\">" . $service['description'] . "</td>\n";
								// if service is running then listr else listbg
								$bgclass = null;
								if (get_service_status($service))
									$bgclass = "listr";
								else
									$bgclass = "listbg";
								echo "<td class=\"" . $bgclass . "\">" . get_service_status_icon($service, true, true) . "</td>\n";
								echo "<td valign=\"middle\" class=\"list nowrap\">" . get_service_control_links($service);
								$scut = get_shortcut_by_service_name($service['name']);
								if (!empty($scut)) {
									echo get_shortcut_main_link($scut, true, $service);
									echo get_shortcut_status_link($scut, true, $service);
									echo get_shortcut_log_link($scut, true);
								}
								echo "</td></tr>\n";
							}
						} else {
							echo "<tr><td colspan=\"3\">" . gettext("No services found") . " . </td></tr>\n";
						}

						?>
						</tbody>
						</table>
						</div>
						</form>
					</div>
			    </section>
			</div>
		</div>
	</section>

<?php include("foot.inc"); ?>
