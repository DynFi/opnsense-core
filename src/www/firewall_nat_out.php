<?php

/*
    Copyright (C) 2014 Deciso B.V.
    Copyright (C) 2004 Scott Ullrich <sullrich@gmail.com>
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
require_once("filter.inc");
require_once("interfaces.inc");


$a_out = &config_read_array('nat', 'outbound', 'rule');
if (!isset($config['nat']['outbound']['mode'])) {
    $config['nat']['outbound']['mode'] = "automatic";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pconfig = $_POST;
    if (isset($pconfig['id']) && isset($a_out[$pconfig['id']])) {
        // id found and valid
        $id = $pconfig['id'];
    }
    if (isset($pconfig['apply'])) {
        write_config();
        filter_configure();
        clear_subsystem_dirty('natconf');
        clear_subsystem_dirty('filter');
    } elseif (isset($pconfig['save']) && $pconfig['save'] == "Save") {
        $mode = $config['nat']['outbound']['mode'];
        $config['nat']['outbound']['mode'] = $pconfig['mode'];
        write_config();
        mark_subsystem_dirty('natconf');
        header(url_safe('Location: /firewall_nat_out.php'));
        exit;
    } elseif (isset($pconfig['act']) && $pconfig['act'] == 'del' && isset($id)) {
        // delete single record
        unset($a_out[$id]);
        write_config();
        mark_subsystem_dirty('natconf');
        header(url_safe('Location: /firewall_nat_out.php'));
        exit;
    } elseif (isset($pconfig['act']) && $pconfig['act'] == 'del_x' && isset($pconfig['rule']) && count($pconfig['rule']) > 0) {
        /* delete selected rules */
        foreach ($pconfig['rule'] as $rulei) {
            if (isset($a_out[$rulei])) {
                unset($a_out[$rulei]);
            }
        }
        write_config();
        mark_subsystem_dirty('natconf');
        header(url_safe('Location: /firewall_nat_out.php'));
        exit;
    } elseif (isset($pconfig['act']) && in_array($pconfig['act'], array('toggle_enable', 'toggle_disable')) && isset($pconfig['rule']) && count($pconfig['rule']) > 0) {
        foreach ($pconfig['rule'] as $rulei) {
            $a_out[$rulei]['disabled'] = $pconfig['act'] == 'toggle_disable';
        }
        write_config();
        mark_subsystem_dirty('filter');
        header(url_safe('Location: /firewall_nat_out.php'));
        exit;
    } elseif (isset($pconfig['act']) && $pconfig['act'] == 'move' && isset($pconfig['rule']) && count($pconfig['rule']) > 0) {
        // if rule not set/found, move to end
        if (!isset($id)) {
            $id = count($a_out);
        }
        $a_out = legacy_move_config_list_items($a_out, $id,  $pconfig['rule']);
        write_config();
        mark_subsystem_dirty('natconf');
        header(url_safe('Location: /firewall_nat_out.php'));
        exit;
    } elseif (isset($pconfig['act']) && $pconfig['act'] == 'toggle' && isset($id)) {
        // toggle item disabled / enabled
        if(isset($a_out[$id]['disabled'])) {
            unset($a_out[$id]['disabled']);
        } else {
            $a_out[$id]['disabled'] = true;
        }
        write_config('Firewall: NAT: Outbound, toggle NAT rule');
        mark_subsystem_dirty('natconf');
        header(url_safe('Location: /firewall_nat_out.php'));
        exit;
    }
}

$mode = $config['nat']['outbound']['mode'];

$interface_names= array();
// add this hosts ips
foreach ($config['interfaces'] as $intf => $intfdata) {
    if (isset($intfdata['ipaddr']) && $intfdata['ipaddr'] != 'dhcp') {
        $interface_names[$intfdata['ipaddr']] = sprintf(gettext('%s address'), !empty($intfdata['descr']) ? $intfdata['descr'] : $intf );
    }
}

if ($mode != 'disabled' && $mode != 'automatic') {
    $main_buttons = array(
        array('label' => gettext('Add'), 'href' => 'firewall_nat_out_edit.php'),
    );
}

include("head.inc");

?>
<body>
  <script>
  $( document ).ready(function() {
    // link delete buttons
    $(".act_delete").click(function(event){
      event.preventDefault();
      var id = $(this).attr("id").split('_').pop(-1);
      if (id != 'x') {
        // delete single
        BootstrapDialog.show({
          type:BootstrapDialog.TYPE_DANGER,
          title: "<?= gettext("Nat")." ".gettext("Outbound");?>",
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
                      event.preventDefault();
                  }
                }]
        });
      } else {
        // delete selected
        BootstrapDialog.show({
          type:BootstrapDialog.TYPE_DANGER,
          title: "<?=gettext("Nat")." ".gettext("Outbound");?>",
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
                      event.preventDefault();
                  }
                }]
        });
      }
    });

    // enable/disable selected
    $(".act_toggle_enable").click(function(event){
      event.preventDefault();
      BootstrapDialog.show({
        type:BootstrapDialog.TYPE_DANGER,
        title: "<?= gettext("Rules");?>",
        message: "<?=gettext("Enable selected rules?");?>",
        buttons: [{
                  label: "<?= gettext("No");?>",
                  action: function(dialogRef) {
                      dialogRef.close();
                  }}, {
                  label: "<?= gettext("Yes");?>",
                  action: function(dialogRef) {
                    $("#id").val("");
                    $("#action").val("toggle_enable");
                    $("#iform").submit()
                }
              }]
      });
    });
    $(".act_toggle_disable").click(function(event){
      event.preventDefault();
      BootstrapDialog.show({
        type:BootstrapDialog.TYPE_DANGER,
        title: "<?= gettext("Rules");?>",
        message: "<?=gettext("Disable selected rules?");?>",
        buttons: [{
                  label: "<?= gettext("No");?>",
                  action: function(dialogRef) {
                      dialogRef.close();
                  }}, {
                  label: "<?= gettext("Yes");?>",
                  action: function(dialogRef) {
                    $("#id").val("");
                    $("#action").val("toggle_disable");
                    $("#iform").submit()
                }
              }]
      });
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

    // select All
    $("#selectAll").click(function(){
        $(".rule_select").prop("checked", $(this).prop("checked"));
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
            print_info_box_apply(gettext("The NAT configuration has been changed.")."<br />".gettext("You must apply the changes in order for them to take effect."));
?>
        <form method="post" name="iform" id="iform">
          <input type="hidden" id="id" name="id" value="" />
          <input type="hidden" id="action" name="act" value="" />
          <section class="col-xs-12">
            <div class="content-box">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th colspan="4"><?=gettext("Mode"); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <input name="mode" type="radio" id="mode_automatic"
                          value="automatic" <?= $mode == "automatic" ? "checked=\"checked\"" : "";?> />
                    </td>
                    <td>
                      <label for="mode_automatic">
                        <strong>
                          <?=gettext("Automatic outbound NAT rule generation"); ?><br />
                          <?=gettext("(no manual rules can be used)");?>
                        </strong>
                      </label>
                    </td>
                    <td>
                      <input name="mode" type="radio" id="mode_hybrid"
                          value="hybrid" <?= $mode == "hybrid" ? "checked=\"checked\"" : "";?> />
                    </td>
                    <td>
                      <label for="mode_hybrid">
                        <strong>
                          <?=gettext("Hybrid outbound NAT rule generation"); ?><br />
                          <?=gettext("(automatically generated rules are applied after manual rules)");?>
                        </strong>
                      </label>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <input name="mode" type="radio" id="mode_advanced"
                          value="advanced" <?= $mode == "advanced" ? "checked=\"checked\"" : "";?> />
                    </td>
                    <td>
                      <label for="mode_advanced">
                        <strong>
                          <?=gettext("Manual outbound NAT rule generation"); ?><br />
                          <?=gettext("(no automatic rules are being generated)");?>
                        </strong>
                      </label>
                    </td>
                    <td>
                      <input name="mode" type="radio" id="mode_disabled"
                          value="disabled" <?= $mode == "disabled" ? "checked=\"checked\"" : "";?> />
                    </td>
                    <td>
                      <label for="mode_disabled">
                        <strong>
                          <?=gettext("Disable outbound NAT rule generation"); ?><br />
                          <?=gettext("(outbound NAT is disabled)");?>
                        </strong>
                      </label>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="4">
                      <button name="save" type="submit" class="btn btn-primary" value="Save"><?= gettext('Save') ?></button>
                    </td>
                  </tr>
                </tbody>
              </table>
          </div>
        </section>
<?php if ($mode == 'advanced' || $mode == 'hybrid'): ?>
        <section class="col-xs-12">
          <div class="__mb"></div>
          <div class="table-responsive content-box">
            <table class="table table-striped">
              <thead>
                <tr><th colspan="12"><?=gettext("Manual rules"); ?></th></tr>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>&nbsp;</th>
                    <th><?=gettext("Interface");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("Source");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("Source Port");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("Destination");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("Destination Port");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("NAT Address");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("NAT Port");?></th>
                    <th><?=gettext("Static Port");?></th>
                    <th><?=gettext("Description");?></th>
                    <th>&nbsp;</th>
                  </tr>
                </thead>
                <tbody>
<?php
                $i = 0;
                foreach ($a_out as $natent):
?>
                  <tr <?=$mode == "disabled" || $mode == "automatic" || isset($natent['disabled'])?"class=\"text-muted\"":"";?> ondblclick="document.location='firewall_nat_out_edit.php?id=<?=$i;?>';">
                    <td>
                      <input class="rule_select" type="checkbox" name="rule[]" value="<?=$i;?>"  />
                    </td>
                    <td>
<?php
                    if ($mode == "disabled" || $mode == "automatic"):
?>
                      <span data-toggle="tooltip" title="<?=gettext("All manual rules are being ignored");?>" class="fa fa-play <?=$mode == "disabled" || $mode == "automatic" || isset($natent['disabled']) ? "text-muted" : "text-success";?>"></span>
<?php
                    else:
?>
                      <a href="#" class="act_toggle" id="toggle_<?=$i;?>" data-toggle="tooltip" title="<?=(!isset($natent['disabled'])) ? gettext("Disable") : gettext("Enable");?>" class="btn btn-default btn-xs <?=isset($natent['disabled']) ? "text-muted" : "text-success";?>">
                        <span class="fa fa-play <?=isset($natent['disabled']) ? "text-muted" : "text-success";?>  "></span>
                      </a>
<?php
                    endif;
?>
                    </td>
                    <td>
                      <?=htmlspecialchars(convert_friendly_interface_to_friendly_descr($natent['interface'])); ?>
                    </td>
                    <td class="hidden-xs hidden-sm">
                      <?= isset($natent['source']['not']) ? '!' : '' ?>
<?php                 if (isset($natent['source']['network']) && is_alias($natent['source']['network'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($natent['source']['network']));?>" data-toggle="tooltip"  data-html="true">
                          <?=htmlspecialchars($natent['source']['network']);?>&nbsp;
                        </span>
                        <a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($natent['source']['network']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=$natent['source']['network'] == "(self)" ? gettext("This Firewall") : htmlspecialchars($natent['source']['network']); ?>&nbsp;
<?php                 endif; ?>
                    </td>
                    <td class="hidden-xs hidden-sm">
                      <?=!empty($natent['protocol']) ? $natent['protocol'] . '/' : "" ;?>
<?php
                      if (empty($natent['sourceport'])):?>
                      *
<?php
                      elseif (isset($natent['sourceport']) && is_alias($natent['sourceport'])):?>
                      <span title="<?=htmlspecialchars(get_alias_description($natent['sourceport']));?>" data-toggle="tooltip"  data-html="true">
                        <?=htmlspecialchars(pprint_port($natent['sourceport'])); ?>&nbsp;
                      </span>
                      <a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($natent['sourceport']);?>"
                          title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                        <i class="fa fa-list"></i>
                      </a>
<?php
                      else:?>
                      <?=htmlspecialchars($natent['sourceport'])?>
<?php
                      endif;?>
                    </td>
                    <td class="hidden-xs hidden-sm">
                      <?= isset($natent['destination']['not']) ? '!' : '' ?>
<?php                 if (isset($natent['destination']['address']) && is_alias($natent['destination']['address'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($natent['destination']['address']));?>" data-toggle="tooltip"  data-html="true">
                          <?=htmlspecialchars($natent['destination']['address']);?>&nbsp;
                        </span>
                        <a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($natent['destination']['address']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=isset($natent['destination']['any']) ? "*" : htmlspecialchars($natent['destination']['address']);?>
<?php                 endif; ?>
                    </td>
                    <td class="hidden-xs hidden-sm">
                      <?=!empty($natent['protocol']) ? $natent['protocol'] . '/' : "" ;?>
<?php
                      if (empty($natent['dstport'])):?>
                      *
<?php
                      elseif (isset($natent['dstport']) && is_alias($natent['dstport'])):?>
                      <span title="<?=htmlspecialchars(get_alias_description($natent['dstport']));?>" data-toggle="tooltip"  data-html="true">
                        <?=htmlspecialchars(pprint_port($natent['dstport'])); ?>&nbsp;
                      </span>
                      <a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($natent['dstport']);?>"
                          title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                        <i class="fa fa-list"></i>
                      </a>
<?php
                      else:?>
                      <?=htmlspecialchars($natent['dstport'])?>
<?php
                      endif;?>
                    </td>
                    <td class="hidden-xs hidden-sm">
<?php

                      if (isset($natent['nonat'])) {
                          $nat_address = '<I>NO NAT</I>';
                      } elseif (empty($natent['target'])) {
                          $nat_address = gettext("Interface address");
                      } elseif ($natent['target'] == "other-subnet") {
                          $nat_address = $natent['targetip'] . '/' . $natent['targetip_subnet'];
                      } else {
                          $nat_address = htmlspecialchars($natent['target']);
                      }
?>
<?php                 if (isset($natent['target']) && is_alias($natent['target'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($natent['target']));?>" data-toggle="tooltip" data-html="true">
                          <?=$nat_address;?>&nbsp;
                        </span>
                        <a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($natent['target']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 elseif (!empty($interface_names[$nat_address])): ?>
                        <?=$interface_names[$nat_address];?>
<?php                 else: ?>
                        <?=$nat_address;?>
<?php                 endif; ?>
                    </td>
                    <td class="hidden-xs hidden-sm">
                      <?=empty($natent['natport']) ? "*" : htmlspecialchars($natent['natport']);?>
                    </td>
                    <td>
                      <?=isset($natent['staticnatport']) ? gettext("YES") : gettext("NO");?>
                    </td>
                    <td>
                      <?=htmlspecialchars($natent['descr']);?>&nbsp;
                    </td>
                    <td>
                      <a type="submit" id="move_<?=$i;?>" name="move_<?=$i;?>_x" data-toggle="tooltip" title="<?= html_safe(gettext('move selected rules before this rule')) ?>" class="act_move btn btn-default btn-xs">
                        <i class="fa fa-arrow-left fa-fw"></i>
                      </a>
                      <a href="firewall_nat_out_edit.php?id=<?=$i;?>" data-toggle="tooltip" title="<?= html_safe(gettext('Edit')) ?>" class="btn btn-default btn-xs">
                        <i class="fa fa-pencil fa-fw"></i>
                      </a>
                      <a id="del_<?=$i;?>" title="<?= html_safe(gettext('Delete')) ?>" data-toggle="tooltip" class="act_delete btn btn-default btn-xs">
                        <i class="fa fa-trash fa-fw"></i>
                      </a>
                      <a href="firewall_nat_out_edit.php?dup=<?=$i;?>" class="btn btn-default btn-xs" data-toggle="tooltip" title="<?= html_safe(gettext('Clone')) ?>">
                        <i class="fa fa-clone fa-fw"></i>
                      </a>
                    </td>
                  </tr>
<?php
                  $i++;
                endforeach;
?>
<?php if ($i != 0): ?>
                <tr>
                  <td colspan="6" class="hidden-xs hidden-sm"></td>
                  <td colspan="5"></td>
                  <td>
                    <button id="move_<?=$i;?>" name="move_<?=$i;?>_x" data-toggle="tooltip" title="<?=html_safe(gettext('move selected rules to end'))?>" class="act_move btn btn-default btn-xs">
                      <i class="fa fa-arrow-left fa-fw"></i>
                    </button>
                    <button id="del_x" title="<?= html_safe(gettext('Delete selected')) ?>" data-toggle="tooltip" class="act_delete btn btn-default btn-xs">
                      <i class="fa fa-trash fa-fw"></i>
                    </button>
                    <button title="<?= html_safe(gettext('Enable selected')) ?>" data-toggle="tooltip" class="act_toggle_enable btn btn-default btn-xs">
                        <i class="fa fa-check-square-o fa-fw"></i>
                    </button>
                    <button title="<?= html_safe(gettext('Disable selected')) ?>" data-toggle="tooltip" class="act_toggle_disable btn btn-default btn-xs">
                        <i class="fa fa-square-o fa-fw"></i>
                    </button>
<?php endif ?>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <td style="width:16px"><span class="fa fa-play text-success"></span></td>
                  <td colspan="11"><?=gettext("Enabled rule"); ?></td>
                </tr>
                <tr>
                  <td><span class="fa fa-play text-muted"></span></td>
                  <td colspan="11"><?=gettext("Disabled rule"); ?></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </section>
<?php   endif; ?>
<?php
      // when automatic or hybrid, display "auto" table.
      if ($mode == "automatic" || $mode == "hybrid"):
        $fw = filter_core_get_initialized_plugin_system();
        $intfv4 = array();
        $intfnatv4 = array();
        foreach ($fw->getInterfaceMapping() as $intf => $intfcf) {
            if (!empty($intfcf['ifconfig']['ipv4']) && empty($intfcf['gateway'])) {
                $intfv4[] = sprintf(gettext('%s networks'), $intfcf['descr']);
            } elseif (substr($intfcf['if'], 0, 4) != 'ovpn' && !empty($intfcf['gateway'])) {
                $intfnatv4[] = $intfcf;
            }
        }
        $intfv4 = array_merge($intfv4, filter_core_get_default_nat_outbound_networks());
?>
        <section class="col-xs-12">
          <div class="__mb"></div>
          <div class="table-responsive content-box">
            <table class="table table-striped">
              <thead>
                  <tr>
                    <th colspan="11"><?=gettext("Automatic rules"); ?></th>
                  </tr>
                  <tr>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                    <th><?=gettext("Interface");?></th>
                    <th><?=gettext("Source Networks");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("Source Port");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("Destination");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("Destination Port");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("NAT Address");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("NAT Port");?></th>
                    <th class="hidden-xs hidden-sm"><?=gettext("Static Port");?></th>
                    <th><?=gettext("Description");?></th>
                  </tr>
              </thead>
              <tbody>
<?php
              foreach ($intfnatv4 as $natintf):
?>
                <tr>
                  <td>&nbsp;</td>
                  <td>
                    <span class="fa fa-play text-success" data-toggle="tooltip" title="<?=gettext("automatic outbound nat");?>"></span>
                  </td>
                  <td><?= htmlspecialchars($natintf['descr']); ?></td>
                  <td><?= implode(', ', $intfv4);?></td>
                  <td class="hidden-xs hidden-sm">*</td>
                  <td class="hidden-xs hidden-sm">*</td>
                  <td class="hidden-xs hidden-sm">500</td>
                  <td class="hidden-xs hidden-sm"><?= htmlspecialchars($natintf['descr']); ?></td>
                  <td class="hidden-xs hidden-sm">*</td>
                  <td class="hidden-xs hidden-sm"><?=gettext("YES");?></td>
                  <td><?=gettext('Auto created rule for ISAKMP');?></td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td>
                    <span class="fa fa-play text-success" data-toggle="tooltip" title="<?=gettext("automatic outbound nat");?>"></span>
                  </td>
                  <td><?= htmlspecialchars($natintf['descr']); ?></td>
                  <td><?= implode(', ', $intfv4);?></td>
                  <td class="hidden-xs hidden-sm">*</td>
                  <td class="hidden-xs hidden-sm">*</td>
                  <td class="hidden-xs hidden-sm">*</td>
                  <td class="hidden-xs hidden-sm"><?= htmlspecialchars($natintf['descr']); ?></td>
                  <td class="hidden-xs hidden-sm">*</td>
                  <td class="hidden-xs hidden-sm"><?=gettext("NO");?></td>
                  <td><?=gettext('Auto created rule');?></td>
                </tr>
<?php
        endforeach;
?>
              </table>
            </div>
          </section>
<?php
      endif;
?>
        </form>
      </div>
    </div>
  </section>

<?php include("foot.inc"); ?>
