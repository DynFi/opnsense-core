<?php

/*
 * Copyright (C) 2021 A. Kulikov <kulikov.a@gmail.com>
 * Copyright (C) 2015 S. Linke <dev@devsash.de>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

require_once("guiconfig.inc");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_errors = array();
    if (is_numeric($_POST['systemlogfiltercount'])) {
        $config['widgets']['systemlogfiltercount'] = $_POST['systemlogfiltercount'];
    }
    if (is_numeric($_POST['systemlogentriesupdateinterval'])) {
        $config['widgets']['systemlogupdateinterval'] = $_POST['systemlogentriesupdateinterval'];
    }

    if (!empty($_POST['systemlogentriesfilter'])) {
        if (!preg_match('/^[0-9,a-z,A-Z *\-_.\#]*$/', $_POST['systemlogentriesfilter'])) {
        $input_errors[] = gettext("Query filter string is invalid");
        }
    }

    if (count($input_errors) == 0) {
      $config['widgets']['systemlogentriesfilter'] = $_POST['systemlogentriesfilter'];
      write_config("System Log Widget settings saved");
      header(url_safe('Location: /index.php'));
      exit;
    }

    for ($i = 0; $i < count($input_errors); $i++) {
      setcookie("inputerrors[$i]", $input_errors[$i], 0, '/');
    }

    header(url_safe('Location: /index.php'));
    exit;
}

$systemlogEntriesToFetch = isset($config['widgets']['systemlogfiltercount']) ? $config['widgets']['systemlogfiltercount'] : 20;
$systemlogupdateinterval = isset($config['widgets']['systemlogupdateinterval']) ? $config['widgets']['systemlogupdateinterval'] : 10;
$systemlogentriesfilter = isset($config['widgets']['systemlogentriesfilter']) ? $config['widgets']['systemlogentriesfilter'] : "";
if (isset($_COOKIE['inputerrors'])) {
    foreach ($_COOKIE['inputerrors'] as $i => $value) {
        $input_errors[] = $value;
        setcookie("inputerrors[$i]", "", time() - 3600);
    }
}

?>

<div id="system_log-settings" class="widgetconfigdiv" style="display:none;">

  <form action="/widgets/widgets/system_log.widget.php" method="post" name="iform">
    <table class="table table-striped">
      <tr>
        <td><?=gettext("Number of Log lines to display");?>:</td>
        <td>
          <select name="systemlogfiltercount" id="systemlogfiltercount">
            <?php for ($i = 1; $i <= 50; $i++) {?>
            <option value="<?= html_safe($i) ?>" <?php if ($systemlogEntriesToFetch == $i) { echo "selected=\"selected\"";}?>><?= html_safe($i) ?></option>
            <?php } ?>
          </select>
        </td>
        <td>
          <input id="submit_system_log_widget" name="submit_system_log_widget" type="submit" class="btn btn-primary formbtn" style="float: right;" value="<?= html_safe(gettext('Save')) ?>">
        </td>
      </tr>
      <tr>
        <td><?= gettext('Update interval in seconds:') ?><t/d>
        <td>
          <select id="systemlogentriesupdateinterval" name="systemlogentriesupdateinterval">
<?php for ($i = 5; $i <= 30; $i++): ?>
            <option value="<?= html_safe($i) ?>" <?= $systemlogupdateinterval == $i ? 'selected="selected"' : '' ?>><?= html_safe($i) ?></option>
<?php endfor ?>
          </select>
        </td>
        <td></td>
      </tr>
      <tr>
        <td><?= gettext('Log query filter:') ?><t/d>
        <td colspan="2">
         <input id="systemlogentriesfilter" name="systemlogentriesfilter" type="text" value="<?=$systemlogentriesfilter?>" placeholder="<?=$systemlogentriesfilter?>" />
        </td>
      </tr>
    </table>
  </form>
</div>
<div style="overflow: overlay;">
<?php
    if (isset($input_errors) && count($input_errors) > 0) {
        print_input_errors($input_errors);
    }
?>
</div>
<div id="system_log-widgets" class="content-box" style="overflow:scroll;">
<table id="system_log_table" class="table table-striped">
  <tbody></tbody>
</table>
</div>

<script>

            function fetch_system_log(rowCount, refresh_interval_ms) {
                //it is more correct to pass the filter value to the function (without searching the value every time). but this method allows to "live" test the filter value before saving
                let filterstring = "";
                if ($("#systemlogentriesfilter").val()) {
                    filterstring = $("#systemlogentriesfilter").val();
                }
                $.ajax({
                    url: 'api/diagnostics/log/core/system',
                    data: 'current=1&rowCount=' + rowCount + '&searchPhrase=' + filterstring,
                    type: 'POST'
                })
                    .done(function (data, status) {
                        $(".system_log_entry").remove();
                        let entry;
                        let system_log_tr = "";
                        if (typeof data.rows !== "undefined") {
                            if (data.rows.length == 0 && data.filters.length > 0 ) {
                                system_log_tr += '<tr class="system_log_entry"><td style="text-align: center;"><?=html_safe(gettext("No matching results!")); ?></td></tr>';
                            } else {
                                while ((entry = data.rows.shift())) {
                                system_log_tr += '<tr class="system_log_entry"><td style="white-space: nowrap;">' + entry['timestamp'] + '<br>' + entry['process_name'].split('[')[0] + '</td><td>' + entry['line'] + '</td></tr>';
                                }
                            }
                        } else {
                            system_log_tr += '<tr class="system_log_entry"><td style="text-align: center;"><?=html_safe(gettext("An empty response from the server.")); ?></td></tr>';
                        }
                        $("#system_log_table tbody").append(system_log_tr);
                        setTimeout(fetch_system_log, refresh_interval_ms, rowCount, refresh_interval_ms);
                    })
                    .fail(function (jqXHR, textStatus) {
                        console.log("Request failed: " + textStatus);
                        setTimeout(fetch_system_log, refresh_interval_ms, rowCount, refresh_interval_ms);
                    })
            }

            $("#dashboard_container").on("WidgetsReady", function () {
                // needed to display the widget settings menu
                $("#system_log-configure").removeClass("disabled");
                var rowCount = $("#systemlogfiltercount").val();
                var refresh_interval_ms = parseInt($("#systemlogentriesupdateinterval").val()) * 1000;
                refresh_interval_ms = (isNaN(refresh_interval_ms) || refresh_interval_ms < 5000 || refresh_interval_ms > 60000) ? 10000 : refresh_interval_ms;
                let filterstring = "";
                if ($("#systemlogentriesfilter").val()) {
                    filterstring = $("#systemlogentriesfilter").val();
                    $('section#system_log').find('h3').append('&nbsp;&nbsp;filtered with "' + filterstring + '"');
                }
                fetch_system_log(rowCount, refresh_interval_ms);
            })

</script>
