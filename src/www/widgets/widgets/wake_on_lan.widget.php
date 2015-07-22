<?php

/*
	Copyright (C) 2014 Deciso B.V.
	Copyright (C) 2010 Yehuda Katz

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

$nocsrf = true;

require_once("guiconfig.inc");
require_once("widgets/include/wake_on_lan.inc");
require_once("interfaces.inc");

if (isset($config['wol']['wolentry'])) {
    $wolcomputers = $config['wol']['wolentry'];
} else {
    $wolcomputers = array();
}

?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="wol status">
	<tr>
		<?php
        echo '<td class="widgetsubheader" align="center">' . gettext("Computer / Device") . '</td>';
        echo '<td class="widgetsubheader" align="center">' . gettext("Interface") . '</td>';
        echo '<td class="widgetsubheader" align="center">' . gettext("Status") . '</td>';
        ?>
		<td class="widgetsubheader">&nbsp;</td>
	</tr>
<?php

if (count($wolcomputers) > 0) {
    foreach ($wolcomputers as $wolent) {
        echo '<tr><td class="listlr">' . $wolent['descr'] . '<br />' . $wolent['mac'] . '</td>' . "\n";
        echo '<td class="listr">' . convert_friendly_interface_to_friendly_descr($wolent['interface']) . '</td>' . "\n";

        $is_active = exec("/usr/sbin/arp -an |/usr/bin/grep {$wolent['mac']}| /usr/bin/wc -l|/usr/bin/awk '{print $1;}'");
        if ($is_active == 1) {
            echo '<td class="listr" align="center">' . "\n";
            echo "<span class=\"glyphicon glyphicon-play text-success\" alt=\"pass\" ></span> " . gettext("Online") . "</td>\n";
        } else {
            echo '<td class="listbg" align="center">' . "\n";
            echo "<span class=\"glyphicon glyphicon-remove text-danger\" alt=\"block\" ></span> " . gettext("Offline") . "</td>\n";
        }
        echo '<td valign="middle" class="list nowrap">';
        /*if($is_active) { */
            /* Will always show wake-up button even if the code thinks it is awake */
        /* } else { */
            echo "<a href='services_wol.php?mac={$wolent['mac']}&amp;if={$wolent['interface']}'> ";
            echo "<span class='glyphicon glyphicon-flash' title='" . gettext("Wake Up") . "' border='0' alt='wol' ></span></a>\n";
        /* } */
        echo "</td></tr>\n";
    }
} else {
    echo "<tr><td colspan=\"4\" align=\"center\">" . gettext("No saved WoL addresses") . ".</td></tr>\n";
}
?>
</table>
<center><a href="status_dhcp_leases.php" class="navlink"><?= gettext('DHCP Leases Status') ?></a></center>
