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
require_once("interfaces.inc");


$a_bridges = &config_read_array('bridges', 'bridged') ;

function bridge_inuse($bridge_if) {
    foreach (legacy_config_get_interfaces() as $if => $intf) {
        if ($intf['if'] == $bridge_if) {
            return true;
        }
    }
    return false;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($a_bridges[$_POST['id']])) {
        $id = $_POST['id'];
    }

    if (!empty($_POST['action']) && $_POST['action'] == "del" && isset($id)) {
        if (bridge_inuse($a_bridges[$id]['bridgeif'])) {
            $input_errors[] = gettext("This bridge cannot be deleted because it is assigned as an interface.");
        } else {
            if (!does_interface_exist($a_bridges[$id]['bridgeif'])) {
                log_error("Bridge interface does not exist, skipping ifconfig destroy.");
            } else {
                mwexec("/sbin/ifconfig " . escapeshellarg($a_bridges[$id]['bridgeif']) . " destroy");
            }
            unset($a_bridges[$id]);
            write_config();
            header(url_safe('Location: /interfaces_bridge.php'));
            exit;
        }
    }
}

include("head.inc");
legacy_html_escape_form_data($a_bridges);
$main_buttons = array(
  array('href'=>'interfaces_bridge_edit.php', 'label'=>gettext('Add')),
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
        title: "<?= gettext("Bridge");?>",
        message: "<?=gettext("Do you really want to delete this bridge?");?>",
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
                        <th><?=gettext("Members");?></th>
                        <th><?=gettext("Description");?></th>
                        <th>&nbsp;</th>
                      </tr>
                    </thead>
                    <tbody>
<?php
                    $i = 0;
                    $ifdescrs = get_configured_interface_with_descr();
                    foreach ($a_bridges as $bridge): ?>
                      <tr>
                        <td><?=strtoupper($bridge['bridgeif']);?></td>
                        <td>
<?php
                        $members = explode(',', $bridge['members']);
                        $j = 0;
                        foreach ($members as $member) {
                            if (isset($ifdescrs[$member])) {
                                echo htmlspecialchars($ifdescrs[$member]);
                                $j++;
                            }
                            if ($j > 0 && $j < count($members)) {
                                echo ", ";
                            }
                        }?>
                        </td>
                        <td><?=$bridge['descr'];?></td>
                        <td>
                          <a href="interfaces_bridge_edit.php?id=<?=$i;?>" class="btn btn-xs btn-default" data-toggle="tooltip" title="<?=gettext("edit bridge");?>">
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
                </table>
              </div>
            </form>
          </div>
        </section>
      </div>
    </div>
  </section>
<?php include("foot.inc"); ?>
