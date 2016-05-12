<?php

/*
    Copyright (C) 2014 Deciso B.V.
    Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
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
require_once("filter.inc");

if (!isset($config['nat']['onetoone'])) {
    $config['nat']['onetoone'] = array();
}
$a_1to1 = &$config['nat']['onetoone'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pconfig = $_POST;
    if (isset($pconfig['id']) && isset($a_1to1[$pconfig['id']])) {
        // id found and valid
        $id = $pconfig['id'];
    }

    if (isset($pconfig['apply'])) {
        filter_configure();
        $savemsg = get_std_save_message();
        clear_subsystem_dirty('natconf');
        clear_subsystem_dirty('filter');
    } elseif (isset($pconfig['action']) && $pconfig['action'] == 'del' && isset($id)) {
        // delete single entry
        unset($a_1to1[$id]);
        if (write_config()) {
            mark_subsystem_dirty('natconf');
        }
        header("Location: firewall_nat_1to1.php");
        exit;
    } elseif (isset($pconfig['action']) && $pconfig['action'] == 'del_x' && isset($pconfig['rule']) && count($pconfig['rule']) > 0) {
        // delete selected
        foreach ($pconfig['rule'] as $rulei) {
            unset($a_1to1[$rulei]);
        }
        if (write_config()) {
            mark_subsystem_dirty('natconf');
        }
        header("Location: firewall_nat_1to1.php");
        exit;
    } elseif (isset($pconfig['action']) && $pconfig['action'] == 'move') {
        // move selected
        if (isset($pconfig['rule']) && count($pconfig['rule']) > 0) {
            // if rule not set/found, move to end
            if (!isset($id)) {
                $id = count($a_1to1);
            }
            $a_1to1 = legacy_move_config_list_items($a_1to1, $id,  $pconfig['rule']);

            if (write_config()) {
                mark_subsystem_dirty('natconf');
            }
            header("Location: firewall_nat_1to1.php");
            exit;
        }
    } elseif (isset($pconfig['action']) && $pconfig['action'] == 'toggle' && isset($id)) {
        // toggle item
        if(isset($a_1to1[$id]['disabled'])) {
            unset($a_1to1[$id]['disabled']);
        } else {
            $a_1to1[$id]['disabled'] = true;
        }
        if (write_config(gettext('Toggled NAT rule'))) {
            mark_subsystem_dirty('natconf');
        }
        header("Location: firewall_nat_1to1.php");
        exit;
    }
}

legacy_html_escape_form_data($a_1to1);

include("head.inc");

$main_buttons = array(
    array('label'=>gettext("add rule"), 'href'=>'firewall_nat_1to1_edit.php'),
);

?>
<body>
  <script type="text/javascript">
  $( document ).ready(function() {
    // link delete buttons
    $(".act_delete").click(function(){
      var id = $(this).attr("id").split('_').pop(-1);
      if (id != 'x') {
        // delete single
        BootstrapDialog.show({
            type:BootstrapDialog.TYPE_DANGER,
            title: "<?= gettext("1:1");?>",
            message: "<?=gettext("Do you really want to delete this rule?");?>",
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
      } else {
        // delete selected
        BootstrapDialog.show({
            type:BootstrapDialog.TYPE_DANGER,
            title: "<?= gettext("1:1");?>",
            message: "<?=gettext("Do you really want to delete the selected rules?");?>",
            buttons: [{
                    label: "<?= gettext("No");?>",
                    action: function(dialogRef) {
                        dialogRef.close();
                    }}, {
                      label: "<?= gettext("Yes");?>",
                      action: function(dialogRef) {
                        $("#id").val("");
                        $("#action").val("del_x");
                        $("#iform").submit()
                    }
                }]
        });
      }
    });

    // link move buttons
    $(".act_move").click(function(event){
        event.preventDefault();
        var id = $(this).attr("id").split('_').pop(-1);
        $("#id").val(id);
        $("#action").val("move");
        $("#iform").submit();
    });

    // link toggle buttons
    $(".act_toggle").click(function(event){
        event.preventDefault();
        var id = $(this).attr("id").split('_').pop(-1);
        $("#id").val(id);
        $("#action").val("toggle");
        $("#iform").submit();
    });
    // watch scroll position and set to last known on page load
    watchScrollPosition();

  });
  </script>


<?php include("fbegin.inc"); ?>
  <section class="page-content-main">
    <div class="container-fluid">
      <div class="row">
<?php
        print_service_banner('firewall');
        if (isset($savemsg))
          print_info_box($savemsg);
        if (is_subsystem_dirty('natconf'))
          print_info_box_apply(gettext("The NAT configuration has been changed.") .
            "<br />" .
            gettext("You must apply the changes in order for them to take effect."));
?>
          <section class="col-xs-12">
          <div class="content-box">
            <form method="post" name="iform" id="iform">
              <input type="hidden" id="id" name="id" value="" />
              <input type="hidden" id="action" name="action" value="" />
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                    <th><?=gettext("Interface"); ?></th>
                    <th><?=gettext("External IP"); ?></th>
                    <th><?=gettext("Internal IP"); ?></th>
                    <th><?=gettext("Destination IP"); ?></th>
                    <th><?=gettext("Description"); ?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
<?php
                $i = 0;
                foreach ($a_1to1 as $natent):
?>
                  <tr <?=isset($natent['disabled'])?"class=\"text-muted\"":"";?> valign="top" ondblclick="document.location='firewall_nat_1to1_edit.php?id=<?=$i;?>';">
                    <td>
                      <input type="checkbox" name="rule[]" value="<?=$i;?>" />
                    </td>
                    <td>
                      <a href="#" type="submit" id="toggle_<?=$i;?>" data-toggle="tooltip" title="<?=(!isset($natent['disabled'])) ? gettext("disable rule") : gettext("enable rule");?>" class="act_toggle">
<?php                   if(isset($natent['disabled'])):?>
                          <span class="glyphicon glyphicon-play text-muted"></span>
<?php                   else:?>
                          <span class="glyphicon glyphicon-play text-success"></span>
<?php                   endif; ?>
                      </a>
                    </td>
                    <td>
                      <?=htmlspecialchars(convert_friendly_interface_to_friendly_descr(isset($natent['interface']) ? $natent['interface'] : "wan"));?>
                    </td>
                    <td>
                      <?=isset($natent['external']) ? $natent['external'] : "";?><?=isset($natent['source']) ? strstr(pprint_address($natent['source']), '/') : "";?>
<?php                 if (isset($natent['external']['address']) && is_alias($natent['external']['address'])): ?>
                      &nbsp;<a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($natent['external']['address']);?>"><i class="fa fa-list"></i> </a>
<?php                 endif; ?>
                    </td>
                    <td>
<?php                 if (isset($natent['source']['address']) && is_alias($natent['source']['address'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($natent['source']['address']));?>" data-toggle="tooltip">
                          <?=htmlspecialchars(pprint_address($natent['source']));?>&nbsp;
                        </span>
                        <a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($natent['source']['address']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=htmlspecialchars(pprint_address($natent['source']));?>
<?php                 endif; ?>
                    </td>
                    <td>
<?php                 if (isset($natent['destination']['address']) && is_alias($natent['destination']['address'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($natent['destination']['address']));?>" data-toggle="tooltip">
                          <?=htmlspecialchars(pprint_address($natent['destination']));?>&nbsp;
                        </span>
                        <a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($natent['destination']['address']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=htmlspecialchars(pprint_address($natent['destination']));?>
<?php                 endif; ?>
                    </td>
                    <td>
                      <?=$natent['descr'];?> &nbsp;
                    </td>
                    <td>
                      <a type="submit" id="move_<?=$i;?>" name="move_<?=$i;?>_x" data-toggle="tooltip" title="<?=gettext("move selected rules before this rule");?>" class="act_move btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-arrow-left"></span>
                      </a>
                      <a href="firewall_nat_1to1_edit.php?id=<?=$i;?>" class="btn btn-default btn-xs" data-toggle="tooltip" title="<?=gettext("edit rule ");?>">
                        <span class="glyphicon glyphicon-pencil"></span>
                      </a>
                      <a id="del_<?=$i;?>" title="<?=gettext("delete rule"); ?>" data-toggle="tooltip"  class="act_delete btn btn-default btn-xs">
                        <span class="fa fa-trash text-muted"></span>
                      </a>
                      <a href="firewall_nat_1to1_edit.php?dup=<?=$i;?>" data-toggle="tooltip" title="<?=gettext("clone rule");?>" class="btn btn-default btn-xs">
                        <span class="fa fa-clone text-muted"></span>
                      </a>
                    </td>
                  </tr>
<?php
                  $i++;
                endforeach;
?>
                  <tr>
                    <td colspan="7"></td>
                    <td>
<?php               if ($i == 0):
?>
                      <span title="<?=gettext("move selected rules to end");?>" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-arrow-left">
                        </span>
                      </span>
<?php               else:
?>
                      <a type="submit" id="move_<?=$i;?>" name="move_<?=$i;?>_x" data-toggle="tooltip" title="<?=gettext("move selected rules to end");?>" class="act_move btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-arrow-left"></span>
                      </a>
<?php               endif;
?>
<?php               if ($i == 0):
?>
                      <span title="<?=gettext("delete selected rules"); ?>" data-toggle="tooltip" class="btn btn-default btn-xs">
                        <span class="fa fa-trash text-muted"></span>
                      </span>
<?php               else:
?>
                      <a id="del_x" title="<?=gettext("delete selected rules"); ?>" data-toggle="tooltip"  class="act_delete btn btn-default btn-xs">
                        <span class="fa fa-trash text-muted"></span>
                      </a>
<?php               endif;
?>
                      <a href="firewall_nat_1to1_edit.php" data-toggle="tooltip" title="<?=gettext("add new rule");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-plus"></span></a>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="9">&nbsp;</td>
                  </tr>
                  <tr>
                    <td width="16"><span class="glyphicon glyphicon-play text-success"></span></td>
                    <td colspan="8"><?=gettext("Enabled rule"); ?></td>
                  </tr>
                  <tr>
                    <td><span class="glyphicon glyphicon-play text-muted"></span></td>
                    <td colspan="8"><?=gettext("Disabled rule"); ?></td>
                  </tr>
                  <tr>
                    <td><a><i class="fa fa-list"></i></a></td>
                    <td colspan="8"><?=gettext("Alias (click to view/edit)");?></td>
                  </tr>
                  <tr>
                    <td colspan="9">
                      <?=gettext("If you add a 1:1 NAT entry for any of the interface IPs on this system, " .
                        "it will make this system inaccessible on that IP address. i.e. if " .
                        "you use your WAN IP address, any services on this system (IPsec, OpenVPN server, etc.) " .
                        "using the WAN IP address will no longer function."); ?>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </form>
          </div>
        </section>
      </div>
    </div>
  </section>

<?php include("foot.inc"); ?>
