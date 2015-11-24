<?php

/*
	Copyright (C) 2006 Scott Ullrich
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

$pgtitle = array(gettext("Status"),gettext("Filter Reload Status"));
$shortcut_section = "firewall";

if($_GET['getstatus']) {
	$status = '';
	if (file_exists('/var/run/filter_reload_status')) {
		$status = file_get_contents('/var/run/filter_reload_status');
	}
	echo $status;
	exit;
}

if($_POST['reloadfilter']) {
	configd_run("filter reload");
	if ( isset($config['hasync']['synchronizetoip']) && trim($config['hasync']['synchronizetoip']) != "") {
	    // only try to sync when hasync is configured
            configd_run("filter sync reload");
        }
	header("Location: status_filter_reload.php");
	exit;
}
if($_POST['syncfilter']) {
	configd_run("filter sync");
	header("Location: status_filter_reload.php");
	exit;
}

include("head.inc");
?>

<body>

<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>

			    <section class="col-xs-12">

				    <div class="content-box ">
					 <div class="col-xs-12">
							<p><form action="status_filter_reload.php" method="post" name="filter">
							<input type="submit" value="Reload Filter" class="btn btn-primary" name="reloadfilter" id="reloadfilter" />
							<?php if ($config['hasync'] && $config['hasync']["synchronizetoip"] != ""): ?>
							<input type="submit" value="Force Config Sync" class="btn btn-primary" name="syncfilter" id="syncfilter" />
							<?php endif; ?>
							</form></p>
							<pre id="status"></pre>
					    </div>
				    </div>
			    </section>
			</div>
		</div>
	</section>


<script type="text/javascript">
//<![CDATA[
/* init update "thread */
function update_status_thread() {
	getURL('status_filter_reload.php?getstatus=true', update_data);
}
function update_data(obj) {
	var result_text = obj.content;
	jQuery('#status').html(result_text);
	window.setTimeout('update_status_thread()', 200);
}
//]]>
</script>

<script type="text/javascript">
//<![CDATA[
/*
 * getURL is a proprietary Adobe function, but it's simplicity has made it very
 * popular. If getURL is undefined we spin our own by wrapping XMLHttpRequest.
 */
if (typeof getURL == 'undefined') {
  getURL = function(url, callback) {
    if (!url)
      throw 'No URL for getURL';

    try {
      if (typeof callback.operationComplete == 'function')
        callback = callback.operationComplete;
    } catch (e) {}
    if (typeof callback != 'function')
      throw 'No callback function for getURL';

    var http_request = null;
    if (typeof XMLHttpRequest != 'undefined') {
      http_request = new XMLHttpRequest();
    }
    else if (typeof ActiveXObject != 'undefined') {
      try {
        http_request = new ActiveXObject('Msxml2.XMLHTTP');
      } catch (e) {
        try {
          http_request = new ActiveXObject('Microsoft.XMLHTTP');
        } catch (e) {}
      }
    }
    if (!http_request)
      throw 'Both getURL and XMLHttpRequest are undefined';

    http_request.onreadystatechange = function() {
      if (http_request.readyState == 4) {
        callback( { success : true,
                    content : http_request.responseText,
                    contentType : http_request.getResponseHeader("Content-Type") } );
      }
    }
    http_request.open('GET', url, true);
    http_request.send(null);
  }
}
update_status_thread();
//]]>
</script>

<?php include("foot.inc"); ?>
