<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2010 Ermal Luci
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
require_once("filter.inc");
require_once("vpn.inc");
require_once("interfaces.inc");

if (!is_array($config['pppoes'])) {
    $config['pppoes'] = array();
}

if (!is_array($config['pppoes']['pppoe'])) {
    $config['pppoes']['pppoe'] = array();
}

$a_pppoes = &$config['pppoes']['pppoe'];

if ($_POST) {
    $pconfig = $_POST;

    if ($_POST['apply']) {
        if (file_exists('/tmp/.vpn_pppoe.apply')) {
                $toapplylist = unserialize(file_get_contents('/tmp/.vpn_pppoe.apply'));
            foreach ($toapplylist as $pppoeid) {
                if (!is_numeric($pppoeid)) {
                    continue;
                }
                if (is_array($config['pppoes']['pppoe'])) {
                    foreach ($config['pppoes']['pppoe'] as $pppoe) {
                        if ($pppoe['pppoeid'] == $pppoeid) {
                            vpn_pppoe_configure($pppoe);
                            break;
                        }
                    }
                }
            }
            @unlink('/tmp/.vpn_pppoe.apply');
        }

        filter_configure();
        $savemsg = get_std_save_message();
        clear_subsystem_dirty('vpnpppoe');
    }
}

if ($_GET['act'] == "del") {
    if ($a_pppoes[$_GET['id']]) {
        killbypid("/var/run/pppoe{$a_pppoes[$_GET['id']]['pppoeid']}-vpn.pid");
        mwexecf('/bin/rm -r %s', "/var/etc/pppoe{$a_pppoes[$_GET['id']]['pppoeid']}");
        unset($a_pppoes[$_GET['id']]);
        write_config();
        header("Location: vpn_pppoe.php");
        exit;
    }
}

$pgtitle = array(gettext('Services'), gettext('PPPoE Server'));
$shortcut_section = "pppoes";
include("head.inc");

$main_buttons = array(
    array('label'=>gettext("add a new pppoe instance"), 'href'=>'vpn_pppoe_edit.php'),
);

?>

<body>
	<?php include("fbegin.inc"); ?>
	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($savemsg)) { print_info_box($savemsg); } ?>
				<?php if (is_subsystem_dirty('vpnpppoe')) : ?><br/>
				<?php print_info_box_apply(gettext("The PPPoE entry list has been changed") . ".<br />" . gettext("You must apply the changes in order for them to take effect."));?>
				<?php endif; ?>


			    <section class="col-xs-12">



					<div class="content-box col-xs-12">

							<form action="vpn_pppoe.php" method="post" name="iform" id="iform">

								<div class="table-responsive">
									<table class="table table-striped table-sort">
										<tr>
										  <td width="15%" class="listhdrr"><?=gettext("Interface");?></td>
										  <td width="10%" class="listhdrr"><?=gettext("Local IP");?></td>
										  <td width="20%" class="listhdrr"><?=gettext("Number of users");?></td>
										  <td width="25%" class="listhdr"><?=gettext("Description");?></td>
										  <td width="10%" class="list">
										  </td>
										</tr>
                                                <?php $i = 0; foreach ($a_pppoes as $pppoe) :
?>
										<tr>
										  <td class="listlr" ondblclick="document.location='vpn_pppoe_edit.php?id=<?=$i;?>';">
										    <?=htmlspecialchars(strtoupper($pppoe['interface']));?>
										  </td>
										  <td class="listlr" ondblclick="document.location='vpn_pppoe_edit.php?id=<?=$i;?>';">
										    <?=htmlspecialchars($pppoe['localip']);?>
										  </td>
										  <td class="listr" ondblclick="document.location='vpn_pppoe_edit.php?id=<?=$i;?>';">
                                                <?=htmlspecialchars($pppoe['n_pppoe_units']);?>
										  </td>
										  <td class="listbg" ondblclick="document.location='vpn_pppoe_edit.php?id=<?=$i;?>';">
										    <?=htmlspecialchars($pppoe['descr']);?>&nbsp;
										  </td>
										  <td valign="middle" class="list nowrap">
											<a href="vpn_pppoe_edit.php?id=<?=$i;
?>" title="<?=gettext("edit pppoe instance"); ?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>

											<a href="vpn_pppoe.php?act=del&amp;id=<?=$i;
?>" onclick="return confirm('<?=gettext("Do you really want to delete this entry? All elements that still use it will become invalid (e.g. filter rules)!");
?>')" title="<?=gettext("delete pppoe instance");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-remove"></span></a>
										  </td>
										</tr>
                                                <?php $i++;

endforeach; ?>

									</table>
								</div>
							</form>

					</div>
			    </section>
			</div>
		</div>
	</section>

<?php include("foot.inc");
