<?php

/*
    Copyright (C) 2014-2016 Deciso B.V.
    Copyright (C) 2005-2009 Scott Ullrich <sullrich@gmail.com>
    Copyright (C) 2005 Colin Smith <ethethlay@gmail.com>
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

/* handle AJAX operations */
if(isset($_POST['action']) && $_POST['action'] == "remove") {
    if (isset($_POST['srcip']) && isset($_POST['dstip']) && is_ipaddr($_POST['srcip']) && is_ipaddr($_POST['dstip'])) {
        $retval = mwexec("/sbin/pfctl -k " . escapeshellarg($_POST['srcip']) . " -k " . escapeshellarg($_POST['dstip']));
        echo htmlentities("|{$_POST['srcip']}|{$_POST['dstip']}|{$retval}|");
    } else {
        echo gettext("invalid input");
    }
    exit;
}

if (isset($_POST['filter']) && isset($_POST['killfilter'])) {
    if (is_ipaddr($_POST['filter'])) {
        $tokill = escapeshellarg($_POST['filter'] . "/32");
    } elseif (is_subnet($_POST['filter'])) {
        $tokill = escapeshellarg($_POST['filter']);
    } else {
        // Invalid filter
        $tokill = "";
    }
    if (!empty($tokill)) {
        mwexec("/sbin/pfctl -k {$tokill} -k 0/0");
        mwexec("/sbin/pfctl -k 0.0.0.0/0 -k {$tokill}");
    }
}

include("head.inc");
?>

<body>
<?php include("fbegin.inc"); ?>
  <script type="text/javascript">
  $( document ).ready(function() {
    // delete state
    $(".act_del").click(function(event){
        event.preventDefault();
        var srcip = $(this).data('srcip');
        var dstip = $(this).data('dstip');
        var rowid = $(this).data('rowid');

        $.post(window.location, {action: 'remove', srcip: srcip, dstip: dstip}, function(data) {
            $("."+rowid).hide();
        });
    });
  });
</script>
  <section class="page-content-main">
    <div class="container-fluid">
      <div class="row">
        <section class="col-xs-12">
          <div class="content-box">
            <form  method="post" name="iform">
<?php
              if (empty($_POST['filter'])) {
                  $filter = "''";
              } else {
                  $filter = escapeshellarg(htmlspecialchars($_POST['filter']));
              }
              $states_info = json_decode(configd_run("filter list states {$filter} 10000"), true);?>
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th><?=gettext("Current total state count");?></th>
                    <th><?=gettext("Filter expression:");?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><?=$states_info['total_entries'];?></td>
                    <td>
                      <input type="text" name="filter" value="<?=!empty($_POST['filter']) ? htmlspecialchars($_POST['filter']) : "";?>"/>
                    </td>
                    <td>
                      <input type="submit" class="btn btn-primary" value="<?=gettext("Filter");?>" />
                      <?php if (isset($_POST['filter']) && (is_ipaddr($_POST['filter']) || is_subnet($_POST['filter']))): ?>
                      <input type="submit" class="btn btn-primary" name="killfilter" value="<?=gettext("Kill");?>" />
                      <?php endif; ?>
                    </td>
                  </tr>
                </tbody>
              </table>
            </form>
          </div>
        </section>
        <section class="col-xs-12">
          <div class="content-box">
            <div class="content-box-main">
              <div class="table-responsive">
                <table id="state_table" class="table table-condensed table-hover table-striped">
                  <thead>
                    <tr>
                      <th data-column-id="int"><?=gettext("Int");?></th>
                      <th data-column-id="proto"><?=gettext("Proto");?></th>
                      <th data-column-id="path"><?=gettext("Source -> Router -> Destination");?></th>
                      <th data-column-id="state"><?=gettext("State");?></th>
                    </tr>
                  </thead>
                  <tbody>
<?php
                  $intfdescr = array();
                  foreach (legacy_config_get_interfaces() as $intf) {
                      $intfdescr[$intf['if']] = $intf['descr'];
                  }
                  foreach ($states_info['details'] as $state):
                    // states can be deleted by source / dest combination, all matching records use the same class.
                    $rowid = str_replace(array('.', ':'), '_', $state['src_addr'].$state['dst_addr']);
                    // (re)construct info
                    $direction = ($state['direction'] == 'out' ? '->' : '<-');
                    $isipv4 = strpos($state['src_addr'], ':') === false;
                    $srcport = $isipv4 ? ":{$state['src_port']}" : "[{$state['src_port']}]";
                    $dstport = $isipv4 ? ":{$state['dst_port']}" : "[{$state['dst_port']}]";
                    $info = $state['src_addr'] . $srcport . " " . $direction . " ";
                    if (!empty($state['nat_addr'])) {
                        $natport = $isipv4 ? ":{$state['nat_port']}" : "[{$state['nat_port']}]";
                        $info .=$state['nat_addr'] . $natport;
                    }
                    $info .= $state['dst_addr'] . $dstport;
?>
                    <tr class="r<?=$rowid;?>">
                      <td><?= !empty($intfdescr[$state['iface']]) ? $intfdescr[$state['iface']] : $state['iface'] ?></td>
                      <td><?= $state['proto'];?></td>
                      <td><?= $info ?></td>
                      <td><?= $state['state'];?></td>
                      <td>
                        <a href="#" data-rowid="r<?=$rowid?>" data-srcip="<?=$state['src_addr']?>" data-dstip="<?=$state['dst_addr'];?>" class="act_del btn btn-default" title="<?= gettext('Remove all state entries from') ?> <?= $state['src_addr'] ?> <?= gettext('to') ?> <?= $state['dst_addr'] ?>"><span class="glyphicon glyphicon-remove"></span></a>
                      </td>
                    </tr>
<?php
                    $row++;
                  endforeach;
                  if ($states_info['total'] == 0): ?>
                    <tr>
                      <td colspan="5"><?= gettext("No states were found.") ?></td>
                    </tr>
<?php
                  endif;?>
                  </tbody>
                  <tfoot>
<?php
                    if (!empty($_POST['filter'])): ?>
                    <tr>
                      <td colspan="5"><?=gettext("States matching current filter")?>: <?= $states_info['total'] ?></td>
                    </tr>
<?php
                    endif;?>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </section>


<?php include('foot.inc');?>
