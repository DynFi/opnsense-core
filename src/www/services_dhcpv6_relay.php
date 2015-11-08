<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2003-2004 Justin Ellison <justin@techadvise.com>.
	Copyright (C) 2010	Ermal Luçi
	Copyright (C) 2010	Seth Mos
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
require_once("services.inc");

$pconfig['enable'] = isset($config['dhcrelay6']['enable']);
if (empty($config['dhcrelay6']['interface']))
	$pconfig['interface'] = array();
else
	$pconfig['interface'] = explode(",", $config['dhcrelay6']['interface']);
$pconfig['server'] = $config['dhcrelay6']['server'];
$pconfig['agentoption'] = isset($config['dhcrelay6']['agentoption']);

$iflist = get_configured_interface_with_descr();

/*   set the enabled flag which will tell us if DHCP server is enabled
 *   on any interface.   We will use this to disable dhcp-relay since
 *   the two are not compatible with each other.
 */
$dhcpd_enabled = false;
if (is_array($config['dhcpdv6'])) {
	foreach($config['dhcpdv6'] as $dhcp)
		if (isset($dhcp['enable']))
			$dhcpd_enabled = true;
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable']) {
		$reqdfields = explode(" ", "server interface");
		$reqdfieldsn = array(gettext("Destination Server"), gettext("Interface"));

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

		if ($_POST['server']) {
			$checksrv = explode(",", $_POST['server']);
			foreach ($checksrv as $srv) {
				if (!is_ipaddrv6($srv))
					$input_errors[] = gettext("A valid Destination Server IPv6 address  must be specified.");
			}
		}
	}

	if (!$input_errors) {
		$config['dhcrelay6']['enable'] = $_POST['enable'] ? true : false;
		$config['dhcrelay6']['interface'] = implode(",", $_POST['interface']);
		$config['dhcrelay6']['agentoption'] = $_POST['agentoption'] ? true : false;
		$config['dhcrelay6']['server'] = $_POST['server'];

		write_config();

		$retval = 0;
		$retval = services_dhcrelay6_configure();
		$savemsg = get_std_save_message();

	}
}

$closehead = false;
$pgtitle = array(gettext("Services"),gettext("DHCPv6 Relay"));
$shortcut_section = "dhcp6";
include("head.inc");

?>

<body>

<script type="text/javascript">
//<![CDATA[
function enable_change(enable_over) {
	if (document.iform.enable.checked || enable_over) {
		document.iform.server.disabled = 0;
		document.iform.interface.disabled = 0;
		document.iform.agentoption.disabled = 0;
	} else {
		document.iform.server.disabled = 1;
		document.iform.interface.disabled = 1;
		document.iform.agentoption.disabled = 1;
	}
}
//]]>
</script>

<?php include("fbegin.inc"); ?>

	<section class="page-content-main">

		<div class="container-fluid">

			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
				<?php if (isset($savemsg)) print_info_box($savemsg); ?>

			    <section class="col-xs-12">

				<div class="content-box">

                        <form action="services_dhcpv6_relay.php" method="post" name="iform" id="iform">

				<?php if ($dhcpd_enabled): ?>
                <p><?= gettext('DHCPv6 Server is currently enabled.  Cannot enable the DHCPv6 Relay service while the DHCPv6 Server is enabled on any interface.') ?></p>
							<?php else: ?>

							<header class="content-box-head container-fluid">
					        <h3><?=gettext("DHCPv6 Relay configuration"); ?></h3>
					    </header>

					    <div class="content-box-main ">
						  <div class="table-responsive">
					<table class="table table-striped table-sort">

									<tr>
                                      <td width="22%" valign="top" class="vncellreq"><?= gettext('Enable') ?></td>
							                        <td width="78%" class="vtable">
										<input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked=\"checked\""; ?> onclick="enable_change(false)" />
							                          <strong><?php printf(gettext("Enable DHCPv6 relay on interface"));?></strong>
										</td>
									</tr>
									<tr>
                                      <td width="22%" valign="top" class="vncellreq"><?= gettext('Interface(s)') ?></td>
							                        <td width="78%" class="vtable">
											<select id="interface" name="interface[]" multiple="multiple" class="formselect" size="3">
										<?php
							                                foreach ($iflist as $ifent => $ifdesc) {
												if (!is_ipaddrv6(get_interface_ipv6($ifent)))
													continue;
												echo "<option value=\"{$ifent}\"";
												if (!empty($pconfig['interface']) && in_array($ifent, $pconfig['interface']))
													echo " selected=\"selected\"";
												echo ">{$ifdesc}</option>\n";
											}
										?>
							                                </select>
											<br /><?=gettext("Interfaces without an IPv6 address will not be shown."); ?>
										</td>
									</tr>
									<tr>
								              <td width="22%" valign="top" class="vtable">&nbsp;</td>
							                      <td width="78%" class="vtable">
							<input name="agentoption" type="checkbox" value="yes" <?php if ($pconfig['agentoption']) echo "checked=\"checked\""; ?> />
							                      <strong><?=gettext("Append circuit ID and agent ID to requests"); ?></strong><br />
							                      <?php printf(gettext("If this is checked, the DHCPv6 relay will append the circuit ID (%s interface number) and the agent ID to the DHCPv6 request."), $g['product_name']); ?></td>
									</tr>
									<tr>
							                        <td width="22%" valign="top" class="vncell"><?=gettext("Destination server");?></td>
							                        <td width="78%" class="vtable">
							                          <input name="server" type="text" class="formfld unknown" id="server" size="20" value="<?=htmlspecialchars($pconfig['server']);?>" />
							                          <br />
										  <?=gettext("This is the IPv6 address of the server to which DHCPv6 requests are relayed. You can enter multiple server IPv6 addresses, separated by commas. ");?>
							                        </td>
									</tr>
									<tr>
							                        <td width="22%" valign="top">&nbsp;</td>
							                        <td width="78%">
							                          <input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Save");?>" onclick="enable_change(true)" />
							                        </td>
									</tr>
								</table>
								</div>
					    </div>
					    <?php endif; ?>
                        </form>
				</div>
			    </section>
			</div>
		</div>
	</section>

<script type="text/javascript">
//<![CDATA[
enable_change(false);
//]]>
</script>
<?php include("foot.inc"); ?>
