<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2007 Marcel Wiget <mwiget@mac.com>.
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
require_once("voucher.inc");

/* return how many vouchers are marked used on a roll */
function voucher_used_count($roll) {
    global $g, $cpzone;

    $bitstring = voucher_read_used_db($roll);
    $max = strlen($bitstring) * 8;
    $used = 0;
    for ($i = 1; $i <= $max; $i++) {
        // check if ticket already used or not.
        $pos = $i >> 3;            // divide by 8 -> octet
        $mask = 1 << ($i % 8);  // mask to test bit in octet
        if (ord($bitstring[$pos]) & $mask)
            $used++;
    }
    unset($bitstring);

    return $used;
}



$cpzone = $_GET['zone'];
if (isset($_POST['zone'])) {
    $cpzone = $_POST['zone'];
}

if (empty($cpzone)) {
    header("Location: status_captiveportal.php");
    exit;
}

if (!is_array($config['captiveportal'])) {
    $config['captiveportal'] = array();
}
$a_cp =& $config['captiveportal'];
$pgtitle = array(gettext("Status"), gettext("Captive portal"), gettext("Voucher Rolls"), $a_cp[$cpzone]['zone']);
$shortcut_section = "captiveportal-vouchers";

if (!is_array($config['voucher'][$cpzone]['roll'])) {
    $config['voucher'][$cpzone]['roll'] = array();
}
$a_roll = &$config['voucher'][$cpzone]['roll'];

include("head.inc");
?>


<body>
<?php include("fbegin.inc"); ?>


	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

			    <section class="col-xs-12">

					<?php
                        $tab_array = array();
                        $tab_array[] = array(gettext("Active Users"), false, "status_captiveportal.php?zone={$cpzone}");
                        $tab_array[] = array(gettext("Active Vouchers"), false, "status_captiveportal_vouchers.php?zone={$cpzone}");
                        $tab_array[] = array(gettext("Voucher Rolls"), true, "status_captiveportal_voucher_rolls.php?zone={$cpzone}");
                        $tab_array[] = array(gettext("Test Vouchers"), false, "status_captiveportal_test.php?zone={$cpzone}");
                        $tab_array[] = array(gettext("Expire Vouchers"), false, "status_captiveportal_expire.php?zone={$cpzone}");
                        display_top_tabs($tab_array);
                    ?>

					<div class="tab-content content-box col-xs-12">

				    <div class="container-fluid">

	                        <form action="status_captiveportal_voucher_rolls.php" method="post" enctype="multipart/form-data" name="iform" id="iform">

					<div class="table-responsive">
						<table class="table table-striped table-sort">
										<tr>
											<td class="listhdrr"><?=gettext("Roll#"); ?></td>
											<td class="listhdrr"><?=gettext("Minutes/Ticket"); ?></td>
											<td class="listhdrr"><?=gettext("# of Tickets"); ?></td>
											<td class="listhdrr"><?=gettext("Comment"); ?></td>
											<td class="listhdrr"><?=gettext("used"); ?></td>
											<td class="listhdrr"><?=gettext("active"); ?></td>
											<td class="listhdr"><?=gettext("ready"); ?></td>
										</tr>
										<?php
                                            $voucherlck = lock("vouche{$cpzone}r");
                                            $i = 0; foreach ($a_roll as $rollent) :
                                            $used = voucher_used_count($rollent['number']);
                                            $active = count(voucher_read_active_db($rollent['number']), $rollent['minutes']);
                                            $ready = $rollent['count'] - $used;
                                            /* used also count active vouchers, remove them */
                                            $used = $used - $active;
                                        ?>
										<tr>
											<td class="listlr">
												<?=htmlspecialchars($rollent['number']); ?>&nbsp;
											</td>
											<td class="listr">
												<?=htmlspecialchars($rollent['minutes']);?>&nbsp;
											</td>
											<td class="listr">
												<?=htmlspecialchars($rollent['count']);?>&nbsp;
											</td>
											<td class="listr">
												<?=htmlspecialchars($rollent['comment']); ?>&nbsp;
											</td>
											<td class="listr">
												<?=htmlspecialchars($used); ?>&nbsp;
											</td>
											<td class="listr">
												<?=htmlspecialchars($active); ?>&nbsp;
											</td>
											<td class="listr">
												<?=htmlspecialchars($ready); ?>&nbsp;
											</td>
										</tr>
										<?php $i++;

                                            endforeach;
                                            unlock($voucherlck); ?>
									</table>
					</div>
	                        </form>
				    </div>
					</div>
			    </section>
			</div>
		</div>
	</section>

<?php include("foot.inc");
