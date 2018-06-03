<?php

/*
    Copyright (C) 2014 Deciso B.V.
    Copyright (C) 2005 Colin Smith <ethethlay@gmail.com>
    Copyright (C) 2010 Jim Pingle <jimp@pfsense.org>
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cnf = OPNsense\Core\Config::getInstance();
    $confvers = $cnf->getBackups(true);
    if (isset($config['system']['backupcount'])) {
        $pconfig['backupcount'] = $config['system']['backupcount'];
    } else {
        # XXX fallback value for older configs
        $pconfig['backupcount'] = 60;
    }
    if (!empty($_GET['getcfg'])) {
        foreach ($confvers as $filename => $revision) {
            if ($revision['time'] == $_GET['getcfg']) {
                $exp_name = urlencode("config-{$config['system']['hostname']}.{$config['system']['domain']}-{$_GET['getcfg']}.xml");
                $exp_data = file_get_contents($filename);
                $exp_size = strlen($exp_data);

                header("Content-Type: application/octet-stream");
                header("Content-Disposition: attachment; filename={$exp_name}");
                header("Content-Length: $exp_size");
                echo $exp_data;
                exit;
            }
        }
    } elseif (!empty($_GET['diff']) && isset($_GET['oldtime']) && isset($_GET['newtime'])
          && is_numeric($_GET['oldtime']) && (is_numeric($_GET['newtime']) || ($_GET['newtime'] == 'current'))) {
        $oldfile = '';
        $newfile = '';
        // search filenames to compare
        foreach ($confvers as $filename => $revision) {
            if ($revision['time'] == $_GET['oldtime']) {
                $oldfile = $filename;
            }
            if ($revision['time'] == $_GET['newtime']) {
                $newfile = $filename;
            }
        }

        $diff = '';

        $oldtime = $_GET['oldtime'];
        $oldcheck = $oldtime;

        if ($_GET['newtime'] == 'current') {
            $newfile = '/conf/config.xml';
            $newtime = $config['revision']['time'];
        } else {
            $newtime = $_GET['newtime'];
            $newcheck = $newtime;
        }

        if (file_exists($oldfile) && file_exists($newfile)) {
            exec("/usr/bin/diff -u " . escapeshellarg($oldfile) . " " . escapeshellarg($newfile), $diff);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_errors = array();
    $pconfig = $_POST;

    if (!empty($pconfig['save'])) {
        if (!isset($pconfig['backupcount']) || !is_numeric($pconfig['backupcount']) || $pconfig['backupcount'] <= 0) {
            $input_errors[] = gettext('Backup count must be greater than zero.');
        }
        if (count($input_errors) == 0) {
            $config['system']['backupcount'] = $pconfig['backupcount'];
            write_config('Changed backup revision count');
            $savemsg = get_std_save_message();
        }
    }

    $cnf = OPNsense\Core\Config::getInstance();
    $confvers = $cnf->getBackups(true);

    if (!empty($_POST['act']) && $_POST['act'] == "revert") {
        foreach ($confvers as $filename => $revision) {
            if (isset($revision['time']) && $revision['time'] == $_POST['time']) {
                if (config_restore($filename) == 0) {
                    $savemsg = sprintf(gettext('Successfully reverted to timestamp %s with description "%s".'), date(gettext("n/j/y H:i:s"), $_POST['id']), $revision['description']);
                } else {
                    $savemsg = gettext("Unable to revert to the selected configuration.");
                }
                break;
            }
        }
    } elseif (!empty($_POST['act']) && $_POST['act'] == "delete") {
        foreach ($confvers as $filename => $revision) {
            if (isset($revision['time']) && $revision['time'] == $_POST['time']) {
                if (file_exists($filename)) {
                    @unlink($filename);
                    $savemsg = sprintf(gettext('Deleted backup with timestamp %s and description "%s".'), date(gettext("n/j/y H:i:s"), $revision['time']),$revision['description']);
                } else {
                    $savemsg = gettext("Unable to delete the selected configuration.");
                }
                unset($confvers[$filename]);
                break;
            }
        }
    }
}

include("head.inc");
?>

<script>
//<![CDATA[
$( document ).ready(function() {
    // revert config dialog
    $(".act_revert").click(function(){
        var id = $(this).data('id');
        BootstrapDialog.show({
          type:BootstrapDialog.TYPE_INFO,
          title: "<?= gettext("Action");?>",
          message: "<?=gettext("Restore from Configuration Backup");?> <br/> <?=gettext('Version');?>: " + id,
          buttons: [{
                    label: "<?= gettext("No");?>",
                    action: function(dialogRef) {
                        dialogRef.close();
                    }}, {
                    label: "<?= gettext("Yes");?>",
                    action: function(dialogRef) {
                      $("#time").val(id);
                      $("#action").val("revert");
                      $("#iform").submit()
                  }
                }]
        });
    });

    // delete backup dialog
    $(".act_delete").click(function(){
        var id = $(this).data('id');
        BootstrapDialog.show({
          type:BootstrapDialog.TYPE_DANGER,
          title: "<?= gettext("Action");?>",
          message: "<?=gettext("Remove Configuration Backup");?> <br/> <?=gettext('Version');?>: " + id,
          buttons: [{
                    label: "<?= gettext("No");?>",
                    action: function(dialogRef) {
                        dialogRef.close();
                    }}, {
                    label: "<?= gettext("Yes");?>",
                    action: function(dialogRef) {
                      $("#time").val(id);
                      $("#action").val("delete");
                      $("#iform").submit()
                  }
                }]
        });
    });

});
//]]>
</script>


<body>
  <?php
    include("fbegin.inc");
    if (isset($input_errors) && count($input_errors) > 0) {
        print_input_errors($input_errors);
    }
    if ($savemsg) {
      print_info_box($savemsg);
    }
  ?>
  <section class="page-content-main">
    <div class="container-fluid">
      <div class="row">
        <section class="col-xs-12">
          <form method="post" id="iform">
            <input type="hidden" id="time" name="time" value="" />
            <input type="hidden" id="action" name="act" value="" />
            <div class="content-box tab-content table-responsive __mb">
              <table class="table table-striped">
                <tr>
                  <th colspan="2"><?= gettext('Backup Count') ?></th>
                </tr>
                <tr>
                  <td><input name="backupcount" type="text" class="formfld unknown" size="5" value="<?=htmlspecialchars($pconfig['backupcount']);?>"/></td>
                  <td><?= gettext("Enter the number of older configurations to keep in the local backup cache."); ?></td>
                </tr>
                <tr>
                  <td>
                    <input name="save" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" />
                  </td>
                  <td>
                    <?= gettext('Be aware of how much space is consumed by backups before adjusting this value.'); ?>
                    <?php if (count($confvers)) {
                      print gettext('Current space used:') . ' ' . exec("/usr/bin/du -sh /conf/backup | /usr/bin/awk '{print $1;}'");
                    } ?>
                  </td>
                </tr>
              </table>
            </div>
          </form>
          <?php if ($diff): ?>
          <div class="content-box tab-content table-responsive __mb" style="overflow: scroll;">
            <table class="table table-striped">
              <tr>
                <th colspan="2">
                  <?= sprintf(
                        gettext('Configuration diff from %s to %s'),
                        date(gettext('n/j/y H:i:s'), $oldtime),
                        date(gettext('n/j/y H:i:s'), $newtime)
                      ); ?>
	        </th>
              </tr>
              <tr>
                <td>
<?php
                  foreach ($diff as $line):
                    switch (substr($line, 0, 1)) {
                      case '+':
                        $color = '#3bbb33';
                        break;
                      case '-':
                        $color = '#c13928';
                        break;
                      case '@':
                        $color = '#3bb9c3';
                        break;
                      default:
                        $color = '#000000';
                    } ?>
                  <span style="color: <?=$color;?>; white-space: pre-wrap; font-family: monospace;"><?=htmlentities($line);?></span><br/>
<?php endforeach; ?>
                </td>
              </tr>
            </table>
          </div>
          <?php endif; ?>
          <?php if (count($confvers)): ?>
          <form method="get">
          <section>
            <div class="content-box tab-content table-responsive">
              <table class="table table-striped">
                <tr>
                  <th colspan="2"><?= gettext('History') ?></th>
                </tr>
                <tr>
                  <td>
                    <button type="submit" name="diff" class="btn btn-primary pull-left" value="Diff">
                      <?=gettext('View differences');?>
                    </button>
                  </td>
                  <td>
                    <?= gettext("To view the differences between an older configuration and a newer configuration, select the older configuration using the left column of radio options and select the newer configuration in the right column, then press the button."); ?>
                  </td>
                </tr>
              </table>
              <table class="table table-striped">
                <tbody>
                  <tr>
                    <th colspan="2"><?=gettext("Diff");?></th>
                    <th><?=gettext("Date");?></th>
                    <th><?=gettext("Size");?></th>
                    <th><?=gettext("Configuration Change");?></th>
                    <th class="text-nowrap"></th>
                  </tr>
                  <tr>
                    <td></td>
                    <td>
                      <input type="radio" name="newtime" value="current" <?= !isset($newcheck) || $newcheck == 'current' ? 'checked="checked"' : '' ?>/>
                    </td>
                    <td><?=date(gettext("n/j/y H:i:s"), $config['revision']['time']) ?></td>
                    <td><?=format_bytes(filesize("/conf/config.xml")) ?></td>
                    <td><?="{$config['revision']['username']}: {$config['revision']['description']}" ?></td>
                    <td class="text-nowrap"><strong><?=gettext("Current");?></strong></td>
                  </tr>
<?php
                $last = count($confvers);
                $curr = 1;
                foreach($confvers as $version):?>
                  <tr>
                    <td>
                      <input type="radio" name="oldtime" value="<?=$version['time'];?>" <?= (!isset($oldcheck) && $curr == 1)  || (isset($oldcheck) && $oldcheck == $version['time']) ? 'checked="checked"' : '' ?>/>
                    </td>
                    <td>
                      <?php if ($curr != $last): ?>
                      <input type="radio" name="newtime" value="<?=$version['time'];?>" <?= isset($newcheck) && $newcheck == $version['time'] ? 'checked="checked"' : ''?>/>
                      <?php endif ?>
                    </td>
                    <td><?= date(gettext("n/j/y H:i:s"), $version['time']) ?></td>
                    <td><?= format_bytes($version['filesize']) ?></td>
                    <td><?= "{$version['username']}: {$version['description']}" ?></td>
                    <td class="text-nowrap">
                      <a data-id="<?=$version['time'];?>" href="#" class="act_revert btn btn-default btn-xs" data-toggle="tooltip" title="<?=gettext("Revert to this configuration");?>">
                         <i class="fa fa-sign-in fa-fw"></i>
                       </a>
                       <a data-id="<?=$version['time'];?>" href="#" class="act_delete btn btn-default btn-xs" data-toggle="tooltip" title="<?=gettext("Remove this backup");?>">
                         <i class="fa fa-trash fa-fw"></i>
                       </a>
                       <a href="diag_confbak.php?getcfg=<?=$version['time'];?>" class="btn btn-default btn-xs" data-toggle="tooltip" title="<?=gettext("Download this backup");?>">
                         <i class="fa fa-download fa-fw"></i>
                     </a>
                    </td>
                  </tr>
<?php
                $curr++;
                endforeach;?>
                </tbody>
              </table>
            </div>
          </section>
        </form>
        <?php endif ?>
      </section>
    </div>
  </div>
</section>
<?php include("foot.inc"); ?>
