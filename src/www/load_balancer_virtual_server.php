<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2005-2008 Bill Marquette <bill.marquette@gmail.com>.
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
require_once("vslb.inc");
require_once("load_balancer_maintable.inc");

/* Cleanup relayd anchors that have been marked for cleanup. */
function cleanup_lb_marked()
{
	global $config;

	$filename = '/tmp/relayd_anchors_remove';
	$cleanup_anchors = array();

	/* Nothing to do! */
	if (!file_exists($filename)) {
		return;
	} else {
		$cleanup_anchors = explode("\n", file_get_contents($filename));
		/* Nothing to do! */
		if (empty($cleanup_anchors)) {
			return;
		}
	}

	/* Load current names so we can make sure we don't remove an anchor that is still in use. */
	$vs_a = $config['load_balancer']['virtual_server'];
	$active_vsnames = array();
	if (isset($vs_a)) {
		foreach ($vs_a as $vs) {
			$active_vsnames[] = $vs['name'];
		}
	}

	foreach ($cleanup_anchors as $anchor) {
		/* Only cleanup an anchor if it is not still active. */
		if (!in_array($anchor, $active_vsnames)) {
			cleanup_lb_anchor($anchor);
		}
	}

	@unlink($filename);
}


if (!is_array($config['load_balancer']['virtual_server'])) {
	$config['load_balancer']['virtual_server'] = array();
}
$a_vs = &$config['load_balancer']['virtual_server'];

if ($_POST) {
	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		$retval |= filter_configure();
		$retval |= relayd_configure();
		$savemsg = get_std_save_message($retval);
		/* Wipe out old relayd anchors no longer in use. */
		cleanup_lb_marked();
		clear_subsystem_dirty('loadbalancer');
	}
}

if ($_GET['act'] == "del") {
	if (array_key_exists($_GET['id'], $a_vs)) {

		if (!$input_errors) {
			cleanup_lb_mark_anchor($a_vs[$_GET['id']]['name']);
			unset($a_vs[$_GET['id']]);
			write_config();
			mark_subsystem_dirty('loadbalancer');
			header("Location: load_balancer_virtual_server.php");
			exit;
		}
	}
}

/* Index lbpool array for easy hyperlinking */
$poodex = array();
for ($i = 0; isset($config['load_balancer']['lbpool'][$i]); $i++) {
	$poodex[$config['load_balancer']['lbpool'][$i]['name']] = $i;
}
for ($i = 0; isset($config['load_balancer']['virtual_server'][$i]); $i++) {
	if($a_vs[$i]) {
		$a_vs[$i]['poolname'] = "<a href=\"/load_balancer_pool_edit.php?id={$poodex[$a_vs[$i]['poolname']]}\">{$a_vs[$i]['poolname']}</a>";
		if ($a_vs[$i]['sitedown'] != '') {
			$a_vs[$i]['sitedown'] = "<a href=\"/load_balancer_pool_edit.php?id={$poodex[$a_vs[$i]['sitedown']]}\">{$a_vs[$i]['sitedown']}</a>";
		} else {
			$a_vs[$i]['sitedown'] = 'none';
		}
	}
}

$pgtitle = array(gettext("Services"),gettext("Load Balancer"),gettext("Virtual Servers"));
$shortcut_section = "relayd-virtualservers";

include("head.inc");

$main_buttons = array(
	array('label'=>'Add', 'href'=>'load_balancer_virtual_server_edit.php'),
);

?>

<body>
<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
				<?php if (isset($savemsg)) print_info_box($savemsg); ?>
				<?php if (is_subsystem_dirty('loadbalancer')): ?><br/>
				<?php print_info_box_np(gettext("The virtual server configuration has been changed") . ".<br />" . gettext("You must apply the changes in order for them to take effect."));?><br />
				<?php endif; ?>

			    <section class="col-xs-12">

				<?php
				        /* active tabs */
				        $tab_array = array();
				        $tab_array[] = array(gettext("Pools"), false, "load_balancer_pool.php");
				        $tab_array[] = array(gettext("Virtual Servers"), true, "load_balancer_virtual_server.php");
				        $tab_array[] = array(gettext("Monitors"), false, "load_balancer_monitor.php");
				        $tab_array[] = array(gettext("Settings"), false, "load_balancer_setting.php");
				        display_top_tabs($tab_array);
					?>

					<div class="tab-content content-box col-xs-12">
					  <form action="load_balancer_virtual_server.php" method="post" name="iform" id="iform">

								<div class="table-responsive">

								<?php
											$t = new MainTable();
											$t->edit_uri('load_balancer_virtual_server_edit.php');
											$t->my_uri('load_balancer_virtual_server.php');
											$t->add_column(gettext('Name'),'name',10);
											$t->add_column(gettext('Protocol'),'relay_protocol',10);
											$t->add_column(gettext('IP Address'),'ipaddr',10);
											$t->add_column(gettext('Port'),'port',10);
											$t->add_column(gettext('Pool'),'poolname',10);
											$t->add_column(gettext('Fall Back Pool'),'sitedown',15);
											$t->add_column(gettext('Description'),'descr',25);
											$t->add_button('edit');
											$t->add_button('dup');
											$t->add_button('del');
											$t->add_content_array($a_vs);
											$t->display();
								?>
								</div>
					  </form>
					</div>
			    </section>
			</div>
		</div>
	</section>

<?php include("foot.inc"); ?>
