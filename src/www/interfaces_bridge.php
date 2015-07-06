<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2008 Ermal Luçi
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

if (!is_array($config['bridges'])) {
	$config['bridges'] = array();
}

if (!is_array($config['bridges']['bridged'])) {
	$config['bridges']['bridged'] = array();
}

$a_bridges = &$config['bridges']['bridged'] ;

function bridge_inuse($num) {
	global $config, $a_bridges;

	$iflist = get_configured_interface_list(false, true);
	foreach ($iflist as $if) {
		if ($config['interfaces'][$if]['if'] == $a_bridges[$num]['bridgeif'])
			return true;
	}

	return false;
}

if ($_GET['act'] == "del") {
	if (!isset($_GET['id']))
		$input_errors[] = getext("Wrong parameters supplied");
	else if (empty($a_bridges[$_GET['id']]))
		$input_errors[] = getext("Wrong index supplied");
	/* check if still in use */
	else if (bridge_inuse($_GET['id'])) {
		$input_errors[] = gettext("This bridge cannot be deleted because it is assigned as an interface.");
	} else {
		if (!does_interface_exist($a_bridges[$_GET['id']]['bridgeif'])) {
			log_error("Bridge interface does not exist, skipping ifconfig destroy.");
		} else {
			mwexec("/sbin/ifconfig " . $a_bridges[$_GET['id']]['bridgeif'] . " destroy");
		}

		unset($a_bridges[$_GET['id']]);

		write_config();

		header("Location: interfaces_bridge.php");
		exit;
	}
}


$pgtitle = array(gettext("Interfaces"),gettext("Bridge"));
$shortcut_section = "interfaces";
include("head.inc");

$main_buttons = array(
	array('href'=>'interfaces_bridge_edit.php', 'label'=>'Add'),
);

?>

<body>
<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>

			    <section class="col-xs-12">


					<?php
							$tab_array = array();
							$tab_array[0] = array(gettext("Interface assignments"), false, "interfaces_assign.php");
							$tab_array[1] = array(gettext("Interface Groups"), false, "interfaces_groups.php");
							$tab_array[2] = array(gettext("Wireless"), false, "interfaces_wireless.php");
							$tab_array[3] = array(gettext("VLANs"), false, "interfaces_vlan.php");
							$tab_array[4] = array(gettext("QinQs"), false, "interfaces_qinq.php");
							$tab_array[5] = array(gettext("PPPs"), false, "interfaces_ppps.php");
							$tab_array[6] = array(gettext("GRE"), false, "interfaces_gre.php");
							$tab_array[7] = array(gettext("GIF"), false, "interfaces_gif.php");
							$tab_array[8] = array(gettext("Bridges"), true, "interfaces_bridge.php");
							$tab_array[9] = array(gettext("LAGG"), false, "interfaces_lagg.php");
							display_top_tabs($tab_array);
						?>


						<div class="tab-content content-box col-xs-12">

		                        <form action="interfaces_bridge.php" method="post" name="iform" id="iform">

		                        <div class="table-responsive">
			                        <table class="table table-striped table-sort">

			                         <thead>
                                            <tr>
								<th width="20%" class="listtopic"><?=gettext("Interface");?></th>
								<th width="20%" class="listtopic"><?=gettext("Members");?></th>
								<th width="50%" class="listtopic"><?=gettext("Description");?></th>
								<th width="10%" class="listtopic">&nbsp;</th>
                                            </tr>
                                        </thead>

									<tbody>

									  <?php $i = 0; $ifdescrs = get_configured_interface_with_descr();
											foreach ($a_bridges as $bridge): ?>
						                <tr  ondblclick="document.location='interfaces_bridge_edit.php?id=<?=$i;?>'">
						                  <td class="listlr">
											<?=htmlspecialchars(strtoupper($bridge['bridgeif']));?>
						                  </td>
						                  <td class="listr">
											<?php $members = explode(',', $bridge['members']);
											$j = 0;
											foreach ($members as $member) {
												if (isset($ifdescrs[$member])) {
													echo $ifdescrs[$member];
													$j++;
												}
												if ($j > 0 && $j < count($members))
													echo ", ";
											}
											?>
						                  </td>
						                  <td class="listbg">
						                    <?=htmlspecialchars($bridge['descr']);?>&nbsp;
						                  </td>
						                  <td valign="middle" class="list nowrap">

							                   <a href="interfaces_bridge_edit.php?id=<?=$i;?>" class="btn btn-default" data-toggle="tooltip" data-placement="left" title="<?=gettext("edit group");?>"><span class="glyphicon glyphicon-edit"></span></a>

											   <a href="interfaces_bridge.php?act=del&amp;id=<?=$i;?>" class="btn btn-default" data-toggle="tooltip" data-placement="left" title="<?=gettext("delete group");?>" onclick="return confirm('<?=gettext("Do you really want to delete this bridge?");?>')"><span class="glyphicon glyphicon-remove"></span></a>

							               </td>
										</tr>
									  <?php $i++; endforeach; ?>

									</tbody>
						              </table>
							      </div>

							      <div class="container-fluid">
							      <p class="vexpl"><span class="text-danger"><strong>
									  <?=gettext("Note:"); ?><br />
									  </strong></span>
									  <?=gettext("Here you can configure bridging of interfaces."); ?>
								    </p>
							      </div>

		                        </form>

						</div>
			    </section>
			</div>
		</div>
	</section>

<?php include("foot.inc"); ?>
