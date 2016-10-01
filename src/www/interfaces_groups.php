<?php

/*
    Copyright (C) 2014-2015 Deciso B.V.
    Copyright (C) 2009 Ermal Luçi
    Copyright (C) 2004 Scott Ullrich
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

if (!isset($config['ifgroups']['ifgroupentry'])) {
    $a_ifgroups = array();
} else {
    $a_ifgroups = &$config['ifgroups']['ifgroupentry'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($a_ifgroups[$_POST['id']])) {
        $id = $_POST['id'];
    }

    if (!empty($_POST['action']) && $_POST['action'] == "del" && isset($id)) {
        $members = explode(" ", $a_ifgroups[$id]['members']);
        foreach ($members as $ifs) {
            $realif = get_real_interface($ifs);
            if (!empty($realif)) {
                mwexec("/sbin/ifconfig  " . escapeshellarg($realif) . " -group " . escapeshellarg($a_ifgroups[$id]['ifname']));
            }
        }
        unset($a_ifgroups[$id]);
        write_config();
        header("Location: interfaces_groups.php");
        exit;
    }
}

include("head.inc");
legacy_html_escape_form_data($a_ifgroups);
$main_buttons = array(
  array('href' => 'interfaces_groups_edit.php', 'label' => gettext('Add a new group')),
);
?>

<body>
  <script type="text/javascript">
  $( document ).ready(function() {
    // link delete buttons
    $(".act_delete").click(function(event){
      event.preventDefault();
      var id = $(this).data("id");
      // delete single
      BootstrapDialog.show({
        type:BootstrapDialog.TYPE_DANGER,
        title: "<?= gettext("Group");?>",
        message: "<?=gettext("Do you really want to delete this group? All elements that still use it will become invalid (e.g. filter rules)!");?>",
        buttons: [{
                  label: "<?= gettext("No");?>",
                  action: function(dialogRef) {
                      dialogRef.close();
                  }}, {
                  label: "<?= gettext("Yes");?>",
                  action: function(dialogRef) {
                    $("#id").val(id);
                    $("#action").val("del");
                    $("#iform").submit()
                }
              }]
      });
    });

  });
  </script>
<?php include("fbegin.inc"); ?>
  <section class="page-content-main">
    <div class="container-fluid">
      <div class="row">
        <section class="col-xs-12">
          <div class="tab-content content-box col-xs-12">
            <form  method="post" name="iform" id="iform">
              <input type="hidden" id="action" name="action" value="">
              <input type="hidden" id="id" name="id" value="">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th><?=gettext("Name");?></th>
                    <th><?=gettext("Members");?></th>
                    <th><?=gettext("Description");?></th>
                    <th>&nbsp;</th>
                  </tr>
                </thead>
                <tbody>
<?php
                $i = 0;
                foreach ($a_ifgroups as $ifgroupentry): ?>
                  <tr>
                    <td>
                      <a href="/firewall_rules.php?if=<?=$ifgroupentry['ifname'];?>"><?=$ifgroupentry['ifname'];?></a>
                    </td>
                    <td>
<?php
                    $iflist = get_configured_interface_with_descr(false, true);
                    foreach (explode(" ", $ifgroupentry['members']) as $id => $memb):?>
                      <?=$id > 0 ? "," : "";?>
                      <?=!empty($iflist[$memb]) ? $iflist[$memb] : $memb;?>
<?php
                    endforeach;?>
                    </td>
                    <td><?=$ifgroupentry['descr'];?></td>
                    <td>
                      <a href="interfaces_groups_edit.php?id=<?=$i;?>" class="btn btn-xs btn-default" data-toggle="tooltip" title="<?=gettext("Edit this group");?>">
                        <span class="glyphicon glyphicon-edit"></span>
                      </a>
                      <button title="<?=gettext("Remove this group");?>" data-toggle="tooltip" data-id="<?=$i;?>" class="btn btn-default btn-xs act_delete" type="submit">
                        <span class="fa fa-trash text-muted"></span>
                      </button>
                    </td>
                  </tr>
<?php
                $i++;
                endforeach; ?>
                  <tr>
                    <td colspan="4">
                      <?=gettext("Interface Groups allow you to create rules that apply to multiple interfaces without duplicating the rules. If you remove members from an interface group, the group rules no longer apply to that interface.");?>
                    </td>
                  </tr>
                </tbody>
              </table>
            </form>
          </div>
        </section>
      </div>
    </div>
  </section>

<?php include("foot.inc"); ?>
