<?php

/*
	Copyright (C) 2014 Deciso B.V.
	Copyright (C) 2009 Jim Pingle (jpingle@gmail.com)
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

$pgtitle = gettext("Status").": ".gettext("System logs").": ".gettext("Firewall Log Summary");
$shortcut_section = "firewall";
require_once("guiconfig.inc");
include_once("filter_log.inc");

$filter_logfile = '/var/log/filter.log';
$lines = 5000;
$entriesperblock = 5;

$filterlog = conv_log_filter($filter_logfile, $lines, $lines);
$gotlines = count($filterlog);
$fields = array(
	'act'       => gettext("Actions"),
	'interface' => gettext("Interfaces"),
	'proto'     => gettext("Protocols"),
	'srcip'     => gettext("Source IPs"),
	'dstip'     => gettext("Destination IPs"),
	'srcport'   => gettext("Source Ports"),
	'dstport'   => gettext("Destination Ports"));

$summary = array();
foreach (array_keys($fields) as $f) {
	$summary[$f]  = array();
}

$totals = array();

function cmp($a, $b) {
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? 1 : -1;
}

function stat_block($summary, $stat, $num) {
	global $g, $gotlines, $fields;
	uasort($summary[$stat] , 'cmp');
	print "<table width=\"200\" cellpadding=\"3\" cellspacing=\"0\" border=\"1\" summary=\"source destination ip\">";
	print "<tr><th colspan=\"2\">{$fields[$stat]} ".gettext("data")."</th></tr>";
	$k = array_keys($summary[$stat]);
	$total = 0;
	$numentries = 0;
	for ($i=0; $i < $num; $i++) {
		if (isset($k[$i])) {
			$total += $summary[$stat][$k[$i]];
			$numentries++;
			$outstr = $k[$i];
			if (is_ipaddr($outstr)) {
				$outstr = "<a href=\"diag_dns.php?host={$outstr}\" title=\"".gettext("Reverse Resolve with DNS")."\"><img border=\"0\" src=\"/themes/{$g['theme']}/images/icons/icon_log.gif\" alt=\"log\" /></a> {$outstr}";
			} elseif (substr_count($outstr, '/') == 1) {
				list($proto, $port) = explode('/', $outstr);
				$service = getservbyport($port, strtolower($proto));
				if ($service)
					$outstr .= ": {$service}";
			}
			print "<tr><td>{$outstr}</td><td width=\"50\" align=\"right\">{$summary[$stat][$k[$i]]}</td></tr>";
		}
	}
	$leftover = $gotlines - $total;
	if ($leftover > 0) {
		print "<tr><td>Other</td><td width=\"50\" align=\"right\">{$leftover}</td></tr>";
	}
	print "</table>";
}

function pie_block($summary, $stat, $num) {
	global $gotlines, $fields;
	uasort($summary[$stat] , 'cmp');
	$k = array_keys($summary[$stat]);
	$total = 0;
	$numentries = 0;
	print "\n<script type=\"text/javascript\">\n";
	print "//<![CDATA[\n";
	for ($i=0; $i < $num; $i++) {
		if (isset($k[$i])) {
			$total += $summary[$stat][$k[$i]];
			$numentries++;
			print "var d{$stat}{$i} = [];\n";
			print "d{$stat}{$i}.push([1, {$summary[$stat][$k[$i]]}]);\n";
		}
	}
	$leftover = $gotlines - $total;
	if ($leftover > 0) {
		print "var d{$stat}{$num} = [];\n";
		print "d{$stat}{$num}.push([1, {$leftover}]);\n";
	}

	print "Event.observe(window, 'load', function() {\n";
	print "	new Proto.Chart($('piechart{$stat}'),\n";
	print "	[\n";
	for ($i=0; $i < $num; $i++) {
		if (isset($k[$i])) {
			print "		{ data: d{$stat}{$i}, label: \"{$k[$i]}\"}";
			if (!(($i == ($numentries - 1)) && ($leftover <= 0)))
				print ",\n";
			else
				print "\n";
		}
	}
	if ($leftover > 0)
		print "		{ data: d{$stat}{$i}, label: \"Other\"}\n";
	print "	],\n";
	print "	{\n";
	print "		pies: {show: true, autoScale: true},\n";
	print "		legend: {show: true, labelFormatter: lblfmt}\n";
	print "	});\n";
	print "});\n";
	print "//]]>\n";
	print "</script>\n";
	print "<table cellpadding=\"3\" cellspacing=\"0\" border=\"0\" summary=\"pie chart\">";
	print "<tr><th><font size=\"+1\">{$fields[$stat]}</font></th></tr>";
	print "<tr><td><div id=\"piechart{$stat}\" style=\"width:450px;height:300px\"></div></td></tr>";
	print "</table>\n";
}

foreach ($filterlog as $fe) {
	foreach (array_keys($fields) as $field) {
		if (isset($fe[$field])) {
			if (!isset($summary[$field])) {
				$summary[$field] = array();
			}
			if (!isset($summary[$field][$fe[$field]])) {
				$summary[$field][$fe[$field]] = 0;
			}
			$summary[$field][$fe[$field]]++;
		}
	}
}

include("head.inc"); ?>
<body>
<script src="/javascript/filter_log.js" type="text/javascript"></script>
<script type="text/javascript" src="/protochart/prototype.js"></script>
<script type="text/javascript" src="/protochart/ProtoChart.js"></script>
<!--[if IE]>
<script type="text/javascript" src="/protochart/excanvas.js">
</script>
<![endif]-->
<script type="text/javascript">
//<![CDATA[
	function lblfmt(lbl) {
		return '<font size=\"-2\">' + lbl + '<\/font>'
	}
//]]>
</script>

<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>

			    <section class="col-xs-12">

				<? $active_tab = "/diag_logs_filter.php"; include('diag_logs_tabs.inc'); ?>

					<div class="tab-content content-box col-xs-12">
				    <div class="container-fluid">


							<? $tab_group = 'firewall'; include('diag_logs_pills.php'); ?>

							<p><?php printf (gettext('This is a firewall log summary, of the last %1$s lines of the firewall log (Max %2$s).'), $gotlines, $lines)?><br />
<?=gettext("NOTE: IE8 users must enable compatibility view.")?></p>

							<pre>
							<?php
							foreach(array_keys($fields) as $field) {
								pie_block($summary, $field , $entriesperblock);
								echo "<br /><br />";
								stat_block($summary, $field , $entriesperblock);
								echo "<br /><br />";
							}
							?>
							</pre>
				    </div>
					</div>
			    </section>
			</div>
		</div>
	</section>
<?php include("foot.inc"); ?>
