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
require_once("system.inc");
require_once("interfaces.inc");
require_once("pfsense-utils.inc");
require_once("openvpn.inc");
require_once("unbound.inc");
require_once("services.inc");

$referer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/interfaces_lagg.php');

if (!isset($config['laggs']['lagg'])) {
	$config['laggs']['lagg'] = array();
}

$a_laggs = &$config['laggs']['lagg'];

$portlist = get_interface_list();

$realifchecklist = array();
/* add LAGG interfaces */
if (is_array($config['laggs']['lagg']) && count($config['laggs']['lagg'])) {
	foreach ($config['laggs']['lagg'] as $lagg) {
		unset($portlist[$lagg['laggif']]);
		$laggiflist = explode(",", $lagg['members']);
		foreach ($laggiflist as $tmpif)
			$realifchecklist[get_real_interface($tmpif)] = $tmpif;
	}
}

$checklist = get_configured_interface_list(false, true);
foreach ($checklist as $tmpif)
	$realifchecklist[get_real_interface($tmpif)] = $tmpif;

$laggprotos = array("none", "lacp", "failover", "fec", "loadbalance", "roundrobin");

if (is_numericint($_GET['id']))
	$id = $_GET['id'];
if (isset($_POST['id']) && is_numericint($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_laggs[$id]) {
	$pconfig['laggif'] = $a_laggs[$id]['laggif'];
	$pconfig['members'] = $a_laggs[$id]['members'];
	$laggiflist = explode(",", $a_laggs[$id]['members']);
	foreach ($laggiflist as $tmpif)
		unset($realifchecklist[get_real_interface($tmpif)]);
	$pconfig['proto'] = $a_laggs[$id]['proto'];
	$pconfig['descr'] = $a_laggs[$id]['descr'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "members proto");
	$reqdfieldsn = array(gettext("Member interfaces"), gettext("Lagg protocol"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if (is_array($_POST['members'])) {
		foreach ($_POST['members'] as $member) {
			if (!does_interface_exist($member))
				$input_errors[] = gettext("Interface supplied as member is invalid");
		}
	} else if (!does_interface_exist($_POST['members']))
		$input_errors[] = gettext("Interface supplied as member is invalid");

	if (!in_array($_POST['proto'], $laggprotos))
		$input_errors[] = gettext("Protocol supplied is invalid");

	if (!$input_errors) {
		$lagg = array();
		$lagg['members'] = implode(',', $_POST['members']);
		$lagg['descr'] = $_POST['descr'];
		$lagg['laggif'] = $_POST['laggif'];
		$lagg['proto'] = $_POST['proto'];
		if (isset($id) && $a_laggs[$id])
			$lagg['laggif'] = $a_laggs[$id]['laggif'];

		$lagg['laggif'] = interface_lagg_configure($lagg);
		if ($lagg['laggif'] == "" || !stristr($lagg['laggif'], "lagg"))
			$input_errors[] = gettext("Error occurred creating interface, please retry.");
		else {
			if (isset($id) && $a_laggs[$id])
				$a_laggs[$id] = $lagg;
			else
				$a_laggs[] = $lagg;

			write_config();

			$confif = convert_real_interface_to_friendly_interface_name($lagg['laggif']);
			if ($confif <> "")
				interface_configure($confif);

			header("Location: interfaces_lagg.php");
			exit;
		}
	}
}

$pgtitle = array(gettext("Interfaces"),gettext("LAGG"),gettext("Edit"));
$shortcut_section = "interfaces";
include("head.inc");

?>

<body>
<?php include("fbegin.inc"); ?>


	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
				<div id="inputerrors"></div>


			    <section class="col-xs-12">

				<div class="content-box">

					 <header class="content-box-head container-fluid">
				        <h3><?=gettext("LAGG configuration");?></h3>
				    </header>

				    <div class="content-box-main">

						<form action="interfaces_lagg_edit.php" method="post" name="iform" id="iform">
			                        <table class="table table-striped table-sort">
										<tr>
						                  <td width="22%" valign="top" class="vncellreq"><?=gettext("Parent interface"); ?></td>
						                  <td width="78%" class="vtable">
						                    <select name="members[]" multiple="multiple" size="4" class="selectpicker">
						                      <?php
												foreach ($portlist as $ifn => $ifinfo) {
													if (array_key_exists($ifn, $realifchecklist))
														continue;
													echo "<option value=\"{$ifn}\"";
													if (stristr($pconfig['members'], $ifn))
														echo " selected=\"selected\"";
													echo ">". $ifn ."(".$ifinfo['mac'] .")</option>";
												}
										?>
						                    </select>
									<br />
									<span class="vexpl"><?=gettext("Choose the members that will be used for the link aggregation"); ?>.</span></td>
						                </tr>
								<tr>
						                  <td valign="top" class="vncellreq"><?=gettext("Lag proto"); ?></td>
						                  <td class="vtable">
						                    <select name="proto" class="selectpicker" id="proto">
								<?php
								foreach ($laggprotos as $proto) {
									echo "<option value=\"{$proto}\"";
									if ($proto == $pconfig['proto'])
										echo " selected=\"selected\"";
									echo ">".strtoupper($proto)."</option>";
								}
								?>
						                    </select>
						                    <br />
								   <ul class="vexpl">
								<li>
								    <b><?=gettext("failover"); ?></b><br />
									<?=gettext("Sends and receives traffic only through the master port.  If " .
						                  "the master port becomes unavailable, the next active port is " .
						                  "used.  The first interface added is the master port; any " .
						                  "interfaces added after that are used as failover devices."); ?>
								</li><li>
						     <b><?=gettext("fec"); ?></b><br />          <?=gettext("Supports Cisco EtherChannel.  This is a static setup and " .
						                  "does not negotiate aggregation with the peer or exchange " .
						                  "frames to monitor the link."); ?>
								</li><li>
						     <b><?=gettext("lacp"); ?></b><br />         <?=gettext("Supports the IEEE 802.3ad Link Aggregation Control Protocol " .
						                  "(LACP) and the Marker Protocol.  LACP will negotiate a set " .
						                  "of aggregable links with the peer in to one or more Link " .
						                  "Aggregated Groups.  Each LAG is composed of ports of the " .
						                  "same speed, set to full-duplex operation.  The traffic will " .
						                  "be balanced across the ports in the LAG with the greatest " .
						                  "total speed, in most cases there will only be one LAG which " .
						                  "contains all ports.  In the event of changes in physical " .
						                  "connectivity, Link Aggregation will quickly converge to a " .
						                  "new configuration."); ?>
								</li><li>
						     <b><?=gettext("loadbalance"); ?></b><br />  <?=gettext("Balances outgoing traffic across the active ports based on " .
						                  "hashed protocol header information and accepts incoming " .
						                  "traffic from any active port.  This is a static setup and " .
						                  "does not negotiate aggregation with the peer or exchange " .
						                  "frames to monitor the link.  The hash includes the Ethernet " .
						                  "source and destination address, and, if available, the VLAN " .
						                  "tag, and the IP source and destination address") ?>.
								</li><li>
						     <b><?=gettext("roundrobin"); ?></b><br />   <?=gettext("Distributes outgoing traffic using a round-robin scheduler " .
						                  "through all active ports and accepts incoming traffic from " .
						                  "any active port"); ?>.
								</li><li>
						     <b><?=gettext("none"); ?></b><br />         <?=gettext("This protocol is intended to do nothing: it disables any " .
						                  "traffic without disabling the lagg interface itself"); ?>.
								</li>
							</ul>
							          </td>
							    </tr>
								<tr>
						                  <td width="22%" valign="top" class="vncell"><?=gettext("Description"); ?></td>
						                  <td width="78%" class="vtable">
						                    <input name="descr" type="text" class="form-control unknown" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>" />
						                    <br /> <span class="vexpl"><?=gettext("You may enter a description here " .
						                    "for your reference (not parsed)"); ?>.</span></td>
						                </tr>
						                <tr>
						                  <td width="22%" valign="top">&nbsp;</td>
						                  <td width="78%">
										    <input type="hidden" name="laggif" value="<?=htmlspecialchars($pconfig['laggif']); ?>" />
						                    <input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" />
						                    <input type="button" class="btn btn-default" value="<?=gettext("Cancel");?>" onclick="window.location.href='<?=$referer;?>'" />
						                    <?php if (isset($id) && $a_laggs[$id]): ?>
						                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>" />
						                    <?php endif; ?>
						                  </td>
						                </tr>
						              </table>
						</form>
				    </div>
				</div>
			    </section>
			</div>
		</div>
	</section>

<?php include("foot.inc"); ?>
