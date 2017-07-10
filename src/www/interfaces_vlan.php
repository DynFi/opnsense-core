<?php

/*
    Copyright (C) 2014-2015 Deciso B.V.
    Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>
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


function vlan_inuse($vlan_intf) {
    global $config;
    foreach ($config['interfaces'] as $if => $intf) {
        if ($intf['if'] == $vlan_intf) {
            return $if;
        }
    }
    return false;
}

if (!isset($config['vlans']['vlan']) || !is_array($config['vlans']['vlan'])) {
    $a_vlans = array();
} else {
    $a_vlans = &$config['vlans']['vlan'] ;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($a_vlans[$_POST['id']])) {
        $id = $_POST['id'];
    }

    if (!empty($_POST['action']) && $_POST['action'] == "del" && isset($id)) {
        if (($ifid = vlan_inuse($a_vlans[$id]['vlanif'])) !== false) {
            $ifdescr = empty($config['interfaces'][$ifid]['descr']) ? $ifid : $config['interfaces'][$ifid]['descr'];
            $input_errors[] = sprintf(
              gettext("This VLAN cannot be deleted because it is still being used as an interface (%s).")
              , $ifdescr);
        } else {
            if (does_interface_exist($a_vlans[$id]['vlanif'])) {
                legacy_interface_destroy($a_vlans[$id]['vlanif']);
            }
            unset($a_vlans[$id]);
            write_config();
            header(url_safe('Location: /interfaces_vlan.php'));
            exit;
          }
    }
}

include("head.inc");
legacy_html_escape_form_data($a_vlans);
$main_buttons = array(
  array('href'=>'interfaces_vlan_edit.php', 'label'=>gettext('Add')),
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
        title: "<?= gettext("VLAN");?>",
        message: "<?=gettext("Do you really want to delete this VLAN?");?>",
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
        <?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
        <section class="col-xs-12">
          <div class="tab-content content-box col-xs-12">
            <form method="post" name="iform" id="iform">
              <input type="hidden" id="action" name="action" value="">
              <input type="hidden" id="id" name="id" value="">
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th><?=gettext("Interface");?></th>
                      <th><?=gettext("Tag");?></th>
                      <th><?=gettext("PCP");?></th>
                      <th><?=gettext("Description");?></th>
                      <th>&nbsp;</th>
                    </tr>
                  </thead>
                  <tbody>
<?php
                  $i = 0;
                  foreach ($a_vlans as $vlan): ?>
                    <tr>
                      <td><?=$vlan['if'];?></td>
                      <td><?=$vlan['tag'];?></td>
                      <td><?= isset($vlan['pcp']) ? $vlan['pcp'] : 0 ?></td>
                      <td><?=$vlan['descr'];?></td>
                      <td>
                        <a href="interfaces_vlan_edit.php?id=<?=$i;?>" class="btn btn-xs btn-default" data-toggle="tooltip" title="<?=gettext("edit interface");?>">
                          <span class="glyphicon glyphicon-edit"></span>
                        </a>
                         <button title="<?=gettext("delete interface");?>" data-toggle="tooltip" data-id="<?=$i;?>" class="btn btn-default btn-xs act_delete" type="submit">
                           <span class="fa fa-trash text-muted"></span>
                         </button>
                       </td>
                    </tr>
<?php
                  $i++;
                  endforeach; ?>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="5">
                        <?= gettext("Not all drivers/NICs support 802.1Q VLAN tagging properly. On cards that do not explicitly support it, VLAN tagging will still work, but the reduced MTU may cause problems.");?>
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </form>
          </div>
        </section>
      </div>
    </div>
  </section>
<?php include("foot.inc"); ?>
