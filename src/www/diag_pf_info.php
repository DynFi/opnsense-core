<?php
/*
    Copyright (C) 2014 Deciso B.V.
    Copyright (C) 2010 Scott Ullrich
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

$pgtitle = gettext("Diagnostics: pfInfo");

if($_REQUEST['getactivity']) {
	$text = `/sbin/pfctl -vvsi`;
	$text .= "<p/>";
	$text .= `/sbin/pfctl -vvsm`;
	$text .= "<p/>";
	$text .= `/sbin/pfctl -vvst`;
	$text .= "<p/>";
	$text .= `/sbin/pfctl -vvsI`;
	echo $text;
	exit;
}

include("head.inc");

?>
<body>
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
jQuery(document).ready(function() {setTimeout('getpfinfo()', 5000);});
//<![CDATA[
	function getpfinfo() {
		jQuery.ajax({
			type: "POST",
			url: "/diag_pf_info.php",
			data: 'getactivity=yes',
			async: false,
			complete: activitycallback
		});
	}
	function activitycallback(transport) {
		jQuery('#pfactivitydiv').html('<font face="Courier" size="2"><pre style="text-align:left;">' + transport.responseText  + '<\/pre><\/font>');
		setTimeout('getpfinfo()', 2000);
	}
//]]>
</script>


<section class="page-content-main">
	<div class="container-fluid col-xs-12 col-sm-10 col-md-9">
		<div class="row">
		    <section class="col-xs-12">


			<?php
				if($savemsg) {
					echo "<div id=\"savemsg\">";
					print_info_box($savemsg);
					echo "</div>";
				}
				if (isset($input_errors) && count($input_errors) > 0)
					print_input_errors($input_errors);
			?>

			<div id="pfactivitydiv">
				<?=gettext("Gathering PF information, please wait...");?>
			</div>

		 </section>
		</div>
	</div>
</section>


<?php include("foot.inc"); ?>
