<?php

/*
    Copyright (C) 2014-2015 Deciso B.V. - J. Schellevis
    Copyright (C) 2009 Jim Pingle <jpingle@gmail.com>
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
require_once("filter_log.inc");
require_once("system.inc");
require_once("interfaces.inc");

$filter_logfile = '/var/log/filter.log';
$lines = 5000; // Maximum number of log entries to fetch
$entriesperblock = 10; // Maximum elements to show individually

// flush log file
if (!empty($_POST['clear'])) {
    clear_clog($filter_logfile);
}

// Retrieve filter log data
$filterlog = conv_log_filter($filter_logfile, $lines, $lines);
// Set total retrieved line counter
$gotlines = count($filterlog);
// Set readable fieldnames
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

// Fill summary array with filterlog data
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

// Custom sorting function
function cmp($a, $b) {
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? 1 : -1;
}

// Setup full data array for pie and table
function d3pie_data($summary, $num) {
  $data=array();
  foreach (array_keys($summary) as $stat) {
    uasort($summary[$stat] , 'cmp');
      $other=0;
      foreach(array_keys($summary[$stat]) as $key) {

          if (!isset($data[$stat])) {
            $data[$stat] = array();
          }
          if ( count($data[$stat]) < $num ) {
          $data[$stat][] = array('label' => $key, 'value' => $summary[$stat][$key]);
        } else {
          $other+=$summary[$stat][$key];
        }
      }
      if ($other > 0) {
        $data[$stat][] = array('label' => gettext("other"), 'value' => $other);
      }
  }

  return $data;
}

include("head.inc"); ?>
<body>

<?php include("fbegin.inc"); ?>

  <section class="page-content-main">
    <div class="container-fluid">
      <div class="row">
        <?php print_service_banner('firewall'); ?>
        <?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
        <section class="col-xs-12">
          <div class="tab-content content-box col-xs-12">
            <div class="table-responsive">
              <table class="table table-striped">
                <tr>
                  <td>
                    <strong><?= sprintf(gettext('The following summaries have been collected from the last %s lines of the firewall log (maximum is %s).'), $gotlines, $lines)?></strong>
                  </td>
                  <td>
                    <form method="post">
                      <div class="pull-right">
                        <input name="clear" type="submit" class="btn" value="<?= gettext("Clear log");?>" />
                      </div>
                    </form>
                  </td>
                </tr>
              </table>
            </div>
          </div>
        </section>

        <section class="col-xs-12">
          <!-- retrieve full dataset for pie and table -->
          <?php $data=d3pie_data($summary, $entriesperblock) ?>
          <!-- iterate items and create pie placeholder + tabledata -->
          <?php foreach(array_keys($fields) as $field): ?>
          <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title"><?=$fields[$field]?></h3></div>
            <div class="panel-body">
              <div class="piechart" id="<?=$field?>">
                <svg></svg>
              </div>
              <table class="table table-striped table-bordered">
                <tr>
                  <th><?=$fields[$field]?></th>
                  <th><?=gettext("Count");?></th>
                </tr>
                <?php if (isset($data[$field])):?>
                <?php foreach(array_keys($data[$field]) as $row): ?>
                <tr>
                  <td>
                    <?php if (is_ipaddr($data[$field][$row]["label"])): ?>
                      <a href="diag_dns.php?host=<?=$data[$field][$row]["label"]?>" title="<?=gettext("Reverse Resolve with DNS");?>"><span class="glyphicon glyphicon-search"></span></a>
                    <?php endif ?>
                    <?=$data[$field][$row]["label"]?></td>
                  <td><?=$data[$field][$row]["value"]?></td>
                </tr>
              <?php endforeach ?>
              <?php endif; ?>
              </table>
            </div>
          </div>
          <?php endforeach ?>
        </section>
      </div>
    </div>
  </section>

<script type="text/javascript" >
    // Generate Donut charts

    nv.addGraph(function() {
      // Find all piechart classes to insert the chart
      $('div[class="piechart"]').each(function(){
        var selected_id = $(this).prop("id");
        var chart = nv.models.pieChart()
            .x(function(d) { return d.label })
            .y(function(d) { return d.value })
            .showLabels(true)     //Display pie labels
            .labelThreshold(.05)  //Configure the minimum slice size for labels to show up
            .labelType("percent") //Configure what type of data to show in the label. Can be "key", "value" or "percent"
            .donut(true)          //Turn on Donut mode. Makes pie chart look tasty!
            .donutRatio(0.2)     //Configure how big you want the donut hole size to be.
            ;

          d3.select("[id='"+ selected_id + "'].piechart svg")
              .datum(<? echo json_encode($data); ?>[selected_id])
              .transition().duration(350)
              .call(chart);

          // Update Chart after window resize
          nv.utils.windowResize(function(){ chart.update(); });

        return chart;
    });
});

</script>

<style>
  .piechart svg {
    height: 400px;
  }
</style>

<?php include("foot.inc"); ?>
