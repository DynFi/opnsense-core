<?php

/*
	Copyright (C) 2014 Deciso B.V.
	Copyright (C) 2009 Janne Enberg <janne.enberg@lietu.net>
	Copyright (C) 2004 Scott Ullrich
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>
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
require_once("interfaces.inc");
require_once("filter.inc");

/****f* legacy/have_natpfruleint_access
 * NAME
 *   have_natpfruleint_access
 * INPUTS
 *      none
 * RESULT
 *   returns true if user has access to edit a specific firewall nat port forward interface
 ******/
function have_natpfruleint_access($if) {
        $security_url = "firewall_nat_edit.php?if=". strtolower($if);
        if(isAllowedPage($security_url))
                return true;
        return false;
}

/****f* itemid/delete_id (duplicate to remove itemid.inc)
 * NAME
 *   delete_id - delete an item with ['id'] = $id from $array
 * INPUTS
 *   $id       - int: The ID to delete
 *   $array    - array to delete the item from
 * RESULT
 *   boolean   - true if item was found and deleted
 ******/
function delete_id($id, &$array){
	// Index to delete
	$delete_index = NULL;

	if (!is_array($array))
		return false;

	// Search for the item in the array
	foreach ($array as $key => $item){
		// If this item is the one we want to delete
		if(isset($item['associated-rule-id']) && $item['associated-rule-id']==$id ){
			$delete_index = $key;
			break;
		}
	}

	// If we found the item, unset it
	if( $delete_index!==NULL ){
		unset($array[$delete_index]);
		return true;
	} else {
		return false;
	}

}



if (!is_array($config['nat']['rule']))
	$config['nat']['rule'] = array();

$a_nat = &$config['nat']['rule'];

/* if a custom message has been passed along, lets process it */
if ($_GET['savemsg'])
	$savemsg = $_GET['savemsg'];

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {

		write_config();

		$retval = 0;

		$retval |= filter_configure();
		$savemsg = get_std_save_message($retval);

		if ($retval == 0) {
			clear_subsystem_dirty('natconf');
			clear_subsystem_dirty('filter');
		}

	}
}

if ($_GET['act'] == "del") {
	if ($a_nat[$_GET['id']]) {

		if (isset($a_nat[$_GET['id']]['associated-rule-id'])) {
			delete_id($a_nat[$_GET['id']]['associated-rule-id'], $config['filter']['rule']);
			$want_dirty_filter = true;
		}
		unset($a_nat[$_GET['id']]);

		if (write_config()) {
			mark_subsystem_dirty('natconf');
			if ($want_dirty_filter)
				mark_subsystem_dirty('filter');
		}
		header("Location: firewall_nat.php");
		exit;
	}
}

if (isset($_POST['del_x'])) {
    /* delete selected rules */
    if (is_array($_POST['rule']) && count($_POST['rule'])) {
	    foreach ($_POST['rule'] as $rulei) {
		$target = $rule['target'];
			// Check for filter rule associations
			if (isset($a_nat[$rulei]['associated-rule-id'])){
				delete_id($a_nat[$rulei]['associated-rule-id'], $config['filter']['rule']);

				mark_subsystem_dirty('filter');
			}
	        unset($a_nat[$rulei]);
	    }
		if (write_config())
			mark_subsystem_dirty('natconf');
		header("Location: firewall_nat.php");
		exit;
	}

} else {
        /* yuck - IE won't send value attributes for image buttons, while Mozilla does - so we use .x/.y to find move button clicks instead... */
        unset($movebtn);
        foreach ($_POST as $pn => $pd) {
                if (preg_match("/move_(\d+)_x/", $pn, $matches)) {
                        $movebtn = $matches[1];
                        break;
                }
        }
        /* move selected rules before this rule */
        if (isset($movebtn) && is_array($_POST['rule']) && count($_POST['rule'])) {
                $a_nat_new = array();

                /* copy all rules < $movebtn and not selected */
                for ($i = 0; $i < $movebtn; $i++) {
                        if (!in_array($i, $_POST['rule']))
                                $a_nat_new[] = $a_nat[$i];
                }

                /* copy all selected rules */
                for ($i = 0; $i < count($a_nat); $i++) {
                        if ($i == $movebtn)
                                continue;
                        if (in_array($i, $_POST['rule']))
                                $a_nat_new[] = $a_nat[$i];
                }

                /* copy $movebtn rule */
                if ($movebtn < count($a_nat))
                        $a_nat_new[] = $a_nat[$movebtn];

                /* copy all rules > $movebtn and not selected */
                for ($i = $movebtn+1; $i < count($a_nat); $i++) {
                        if (!in_array($i, $_POST['rule']))
                                $a_nat_new[] = $a_nat[$i];
                }
                $a_nat = $a_nat_new;
		if (write_config())
			mark_subsystem_dirty('natconf');
                header("Location: firewall_nat.php");
                exit;
        }
}

$closehead = false;
$pgtitle = array(gettext("Firewall"),gettext("NAT"),gettext("Port Forward"));
include("head.inc");


$main_buttons = array(
	array('label'=>'Add', 'href'=>'firewall_nat_edit.php?after=-1'),
);


?>

<body>

<script type="text/javascript" src="/themes/<?=$g['theme'];?>/assets/javascripts/jquery-sortable.js"></script>
<style type="text/css">
	body.dragging, body.dragging * {
	  cursor: move !important;
	}

	.dragged {
	  position: absolute;
	  opacity: 0.5;
	  z-index: 2000;
	}

	ol.example li.placeholder {
	  position: relative;
	  /** More li styles **/
	}
	ol.example li.placeholder:before {
	  position: absolute;
	  /** Define arrowhead **/
	}
</style>


<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">


				<?php if (isset($savemsg)) print_info_box($savemsg); ?>
				<?php if (is_subsystem_dirty('natconf')): ?>
				<?php print_info_box_apply(gettext("The NAT configuration has been changed") . ".<br />" . gettext("You must apply the changes in order for them to take effect."));?><br />
				<?php endif; ?>

			    <section class="col-xs-12">


					 <?php
							$tab_array = array();
							$tab_array[] = array(gettext("Port Forward"), true, "firewall_nat.php");
							$tab_array[] = array(gettext("1:1"), false, "firewall_nat_1to1.php");
							$tab_array[] = array(gettext("Outbound"), false, "firewall_nat_out.php");
							$tab_array[] = array(gettext("NPt"), false, "firewall_nat_npt.php");
							display_top_tabs($tab_array);
						?>

						<div class="tab-content content-box col-xs-12">

		                        <form action="firewall_nat.php" method="post" name="iform" id="iform">
						<input type="hidden" id="id" name="id" value="<?php echo htmlspecialchars($id); ?>" />

			                        <table class="table table-striped table-sort">
			                        <thead>
										<tr id="frheader">
										  <th width="2%" class="list">&nbsp;</th>
						                  <th width="2%" class="list">&nbsp;</th>
										  <th class="listhdrr"><?=gettext("If");?></th>
										  <th class="listhdrr"><?=gettext("Proto");?></th>
										  <th class="listhdrr nowrap"><?=gettext("Src. addr");?></th>
										  <th class="listhdrr nowrap"><?=gettext("Src. ports");?></th>
										  <th class="listhdrr nowrap"><?=gettext("Dest. addr");?></th>
										  <th class="listhdrr nowrap"><?=gettext("Dest. ports");?></th>
										  <th class="listhdrr nowrap"><?=gettext("NAT IP");?></th>
										  <th class="listhdrr nowrap"><?=gettext("NAT Ports");?></th>
										  <th class="listhdr"><?=gettext("Description");?></th>
										  <th class="list"></th>
										</tr>
			                        </thead>
			                        <tbody>

										<?php $nnats = $i = 0; foreach ($a_nat as $natent): ?>
										<?php

											//build Alias popup box
											$span_end = "</U></span>";

											if (isset($natent['disabled']))
												$textss = "<span class=\"text-muted\">";
											else
												$textss = "<span>";

											$textse = "</span>";

											/* if user does not have access to edit an interface skip on to the next record */
											if(!have_natpfruleint_access($natent['interface']))
												continue;
										?>
									                <tr valign="top" id="fr<?=$nnats;?>">
									                  <td class="listt"><input type="checkbox" id="frc<?=$nnats;?>" name="rule[]" value="<?=$i;?>" style="margin: 0; padding: 0; width: 15px; height: 15px;" /></td>
									                  <td class="listt" align="center">
														<?php if($natent['associated-rule-id'] == "pass"): ?>
														<?php if(isset($natent['disabled'])):?>
															<span class="glyphicon glyphicon-play text-muted"></span>
														<? else: ?>
															<span class="glyphicon glyphicon-play text-success"></span>
														<? endif;?>

														<?php elseif (!empty($natent['associated-rule-id'])): ?>
														<?php if(isset($natent['disabled'])):?>
															<span class="glyphicon glyphicon-resize-horizontal text-muted"></span>
														<? else:?>
															<span class="glyphicon glyphicon-resize-horizontal text-success"></span>
														<?php endif; ?>
														<?php endif; ?>
													  </td>
									                  <td class="listlr"  id="frd<?=$nnats;?>" ondblclick="document.location='firewall_nat_edit.php?id=<?=$nnats;?>';">
									                    <?=$textss;?>
											    <?php
												if (!$natent['interface'])
													echo htmlspecialchars(convert_friendly_interface_to_friendly_descr("wan"));
												else
													echo htmlspecialchars(convert_friendly_interface_to_friendly_descr($natent['interface']));
											    ?>
									                    <?=$textse;?>
									                  </td>

									                  <td class="listr"  id="frd<?=$nnats;?>" ondblclick="document.location='firewall_nat_edit.php?id=<?=$nnats;?>';">
														<?=$textss;?><?=strtoupper($natent['protocol']);?><?=$textse;?>
									                  </td>

									                  <td class="listr"  id="frd<?=$nnats;?>" ondblclick="document.location='firewall_nat_edit.php?id=<?=$nnats;?>';">
													    <?=$textss;?>
													    <?php
													    echo htmlspecialchars(pprint_address($natent['source']));
													    ?>
													    <?=$textse;?>
									                  </td>
									                  <td class="listr"  id="frd<?=$nnats;?>" ondblclick="document.location='firewall_nat_edit.php?id=<?=$nnats;?>';">
													    <?=$textss;?>
													    <?php
													    echo htmlspecialchars(pprint_port($natent['source']['port']));
													    ?><?=$textse;?>
									                  </td>

									                  <td class="listr"  id="frd<?=$nnats;?>" ondblclick="document.location='firewall_nat_edit.php?id=<?=$nnats;?>';">
													    <?=$textss;?>
													    <?php
													    echo htmlspecialchars(pprint_address($natent['destination']));
													    ?><?=$textse;?>
									                  </td>
									                  <td class="listr"  id="frd<?=$nnats;?>" ondblclick="document.location='firewall_nat_edit.php?id=<?=$nnats;?>';">
													    <?=$textss;?>
													    <?php
													    echo htmlspecialchars(pprint_port($natent['destination']['port']));
													    ?><?=$textse;?>
									                  </td>

									                  <td class="listr" id="frd<?=$nnats;?>" ondblclick="document.location='firewall_nat_edit.php?id=<?=$nnats;?>';">
													    <?=$textss;?>
													    <?php
													    echo htmlspecialchars($natent['target']);
													    ?><?=$textse;?>
									                  </td>
									                  <td class="listr"  id="frd<?=$nnats;?>" ondblclick="document.location='firewall_nat_edit.php?id=<?=$nnats;?>';">
														<?php
															$localport = $natent['local-port'];

															list($dstbeginport, $dstendport) = explode("-", $natent['destination']['port']);

															if ($dstendport) {
																$localendport = $natent['local-port'] + $dstendport - $dstbeginport;
																$localport   .= '-' . $localendport;
															}
														?>
													    <?=$textss;?><?php
													    echo htmlspecialchars(pprint_port($localport));
													    ?><?=$textse;?>
									                  </td>

									                  <td class="listbg"  ondblclick="document.location='firewall_nat_edit.php?id=<?=$nnats;?>';">
													  <?=$textss;?><?=htmlspecialchars($natent['descr']);?>&nbsp;<?=$textse;?>
									                  </td>
									                  <td valign="middle" class="list nowrap">


																<button type="submit"  name="move_<?=$i;?>_x" data-toggle="tooltip" data-placement="left" title="<?=gettext("move selected rules before this rule");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-arrow-left"></span></button>
																<a href="firewall_nat_edit.php?id=<?=$i;?>" data-toggle="tooltip" data-placement="left" title="<?=gettext("edit this rule");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
																<a href="firewall_nat.php?act=del&amp;id=<?=$i;?>"  data-toggle="tooltip" data-placement="left" title="<?=gettext("delete this rule");?>" onclick="return confirm('<?=gettext("Do you really want to delete this rule?");?>')" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-remove"></span></a>

																<a href="firewall_nat_edit.php?dup=<?=$i;?>" class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="left" title="<?=gettext("add new rule based on this one");?>"><span class="glyphicon glyphicon-plus"></span></a></td>
									                      </tr>
												<?php $i++; $nnats++; endforeach; ?>
								                <tr>
									                  <td class="list" colspan="8"></td>
									                  <td>&nbsp;</td>
									                  <td>&nbsp;</td>
									                  <td>&nbsp;</td>
									                  <td class="list nowrap" valign="middle">

														<?php if ($nnats == 0): ?><span class="btn btn-default btn-xs text-muted"><span class="glyphicon glyphicon-arrow-left"></span></span><?php else: ?><button name="move_<?=$i;?>_x" value="<?=$i;?>" type="submit" data-toggle="tooltip" data-placement="left" title="<?=gettext("move selected rules to end");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-arrow-left"></span></button><?php endif; ?>

														<?php if (count($a_nat) == 0): ?>

															<span class="btn btn-default btn-xs text-muted"  data-toggle="tooltip" data-placement="left" title="<?=gettext("delete selected rules");?>"><span class="glyphicon glyphicon-remove" ></span></span>
														<?php else: ?>
															<button name="del_x" type="submit" data-toggle="tooltip" data-placement="left" title="<?=gettext("delete selected rules"); ?>" onclick="return confirm('<?=gettext("Do you really want to delete the selected rules?");?>')" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-remove"></span></button>
														<?php endif; ?>
														 <a href="firewall_nat_edit.php" class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="left" title="<?=gettext("add new rule");?>"><span class="glyphicon glyphicon-plus"></span></a>
													</td>
												</tr>
											</tbody>
											<tfoot>
											<tr><td colspan="12">&nbsp;</td></tr>
									          <tr>
									            <td width="16"><span class="glyphicon glyphicon-play text-success"></span></td>
									            <td colspan="11"><?=gettext("pass"); ?></td>
												</tr>
											   <tr>
									            <td width="14"><span class="glyphicon glyphicon-resize-horizontal text-success"></span></td>
										    <td colspan="11"><?=gettext("linked rule");?></td>
									          </tr>

											</tfoot>

									    </table>

										<div class="container-fluid">
<input name="del_x" type="submit" data-toggle="tooltip" data-placement="left" title="<?=gettext("delete selected rules"); ?>" onclick="return confirm('<?=gettext("Do you really want to delete the selected rules?");?>')" class="btn btn-primary" value="Delete selected rules"/>
										</div>
									</form>

								</div>
							</section>
				        </div>
				    </div>
				</section>



<?php include("foot.inc"); ?>
