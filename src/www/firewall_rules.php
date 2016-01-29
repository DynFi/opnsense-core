<?php

/*
    Copyright (C) 2014-2015 Deciso B.V.
    Copyright (C) 2005 Scott Ullrich <sullrich@gmail.com>
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
require_once("filter.inc");
require_once("pfsense-utils.inc");


if (!isset($config['filter']['rule'])) {
    $config['filter']['rule'] = array();
}

$a_filter = &$config['filter']['rule'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['if'])) {
        $current_if = htmlspecialchars($_GET['if']);
    } else {
        $current_if = "FloatingRules";
    }
    $pconfig = $_POST;
    if (isset($pconfig['id']) && isset($a_filter[$pconfig['id']])) {
        // id found and valid
        $id = $pconfig['id'];
    }
    if (isset($pconfig['apply'])) {
        filter_configure();
        clear_subsystem_dirty('filter');
        $savemsg = sprintf(
            gettext(
                'The settings have been applied and the rules are now reloading ' .
                'in the background. You can monitor the reload progress %shere%s.'
            ),
            '<a href="status_filter_reload.php">',
            '</a>'
        );
    } elseif (isset($pconfig['act']) && $pconfig['act'] == 'del' && isset($id)) {
        // delete single item
        if (!empty($a_filter[$id]['associated-rule-id'])) {
            // unlink nat entry
            if (isset($config['nat']['rule'])) {
                $a_nat = &$config['nat']['rule'];
                foreach ($a_nat as &$natent) {
                    if ($natent['associated-rule-id'] == $a_filter[$id]['associated-rule-id']) {
                        $natent['associated-rule-id'] = '';
                    }
                }
            }
        }
        unset($a_filter[$id]);
        if (write_config()) {
            mark_subsystem_dirty('filter');
        }
        header("Location: firewall_rules.php?if=" . htmlspecialchars($current_if));
        exit;
    } elseif (isset($pconfig['act']) && $pconfig['act'] == 'del_x' && isset($pconfig['rule']) && count($pconfig['rule']) > 0) {
        // delete selected rules
        foreach ($pconfig['rule'] as $rulei) {
            // unlink nat entry
            if (isset($config['nat']['rule'])) {
                $a_nat = &$config['nat']['rule'];
                foreach ($a_nat as &$natent) {
                    if ($natent['associated-rule-id'] == $a_filter[$rulei]['associated-rule-id']) {
                        $natent['associated-rule-id'] = '';
                    }
                }
            }
            unset($a_filter[$rulei]);
        }
        if (write_config()) {
            mark_subsystem_dirty('filter');
        }
        header("Location: firewall_rules.php?if=" . htmlspecialchars($current_if));
        exit;
    } elseif ( isset($pconfig['act']) && $pconfig['act'] == 'move' && isset($pconfig['rule']) && count($pconfig['rule']) > 0) {
        // move selected rules
        if (!isset($id)) {
            // if rule not set/found, move to end
            $id = count($a_filter);
        }
        $a_filter = legacy_move_config_list_items($a_filter, $id,  $pconfig['rule']);
        if (write_config()) {
            mark_subsystem_dirty('filter');
        }
        header("Location: firewall_rules.php?if=" . htmlspecialchars($current_if));
        exit;

    } elseif (isset($pconfig['act']) && $pconfig['act'] == 'toggle' && isset($id)) {
        // toggle item
        if(isset($a_filter[$id]['disabled'])) {
            unset($a_filter[$id]['disabled']);
        } else {
            $a_filter[$id]['disabled'] = true;
        }
        if (write_config()) {
            mark_subsystem_dirty('filter');
        }
        header("Location: firewall_rules.php?if=" . htmlspecialchars($current_if));
        exit;
    }
}

if (isset($_GET['if'])) {
    $selected_if = htmlspecialchars($_GET['if']);
} else {
    $selected_if = "FloatingRules";
}

include("head.inc");

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
        title: "<?= gettext("Rules");?>",
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
        title: "<?= gettext("Rules");?>",
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
  $(".act_move").click(function(){
    var id = $(this).attr("id").split('_').pop(-1);
    $("#id").val(id);
    $("#action").val("move");
    $("#iform").submit();
  });

  // link toggle buttons
  $(".act_toggle").click(function(){
    var id = $(this).attr("id").split('_').pop(-1);
    $("#id").val(id);
    $("#action").val("toggle");
    $("#iform").submit();
  });


  // link category select/search
  $("#fw_category").change(function(){
      var stripe_color = 'transparent';
      var selected_value = $(this).val();
      $(".rule").each(function(){
          // save zebra color
          if ( $(this).children(0).css("background-color") != 'transparent') {
              $("#fw_category").data('stripe_color', $(this).children(0).css("background-color"));
          }
          if ($(this).data('category') != selected_value && selected_value != "") {
              $(this).hide();
          } else {
              $(this).show();
          }
      });

      $("#rules").removeClass("table-striped");
      // add stripes again
      $(".rule:visible").each(function (index) {
        $(this).css("background-color", "inherit");
        if ( index % 2 == 0) {
          $(this).css("background-color", $("#fw_category").data('stripe_color'));
        }
      });
  });

  // hide category search when not used
  if ($("#fw_category > option").length == 1) {
      $("#fw_category").addClass('hidden');
  }

});
</script>

<?php include("fbegin.inc"); ?>
  <section class="page-content-main">
    <div class="container-fluid">
      <div class="row">
        <?php print_service_banner('firewall'); ?>
        <?php if (isset($savemsg)) print_info_box($savemsg); ?>
        <?php if (is_subsystem_dirty('filter')): ?><p>
        <?php print_info_box_apply(gettext("The firewall rule configuration has been changed.<br />You must apply the changes in order for them to take effect."));?>
        <?php endif; ?>
        <section class="col-xs-12">
<?php
           // create tabs per interface + floating
           $iflist_tabs = array();
           $iflist_tabs['FloatingRules'] = 'Floating';
           if (isset($config['ifgroups']['ifgroupentry']))
             foreach($config['ifgroups']['ifgroupentry'] as $ifgen)
                 $iflist_tabs[$ifgen['ifname']] = $ifgen['ifname'];

           foreach (get_configured_interface_with_descr() as $ifent => $ifdesc)
               $iflist_tabs[$ifent] = $ifdesc;

           if (isset($config['l2tp']['mode']) && $config['l2tp']['mode'] == "server")
               $iflist_tabs['l2tp'] = "L2TP VPN";

           if (isset($config['pptpd']['mode']) && $config['pptpd']['mode'] == "server")
               $iflist_tabs['pptp'] = "PPTP VPN";

           if (isset($config['pppoes']['pppoe'])) {
             foreach ($config['pppoes']['pppoe'] as $pppoes) {
               if (($pppoes['mode'] == 'server')) {
                 $iflist_tabs['pppoe'] = "PPPoE Server";
               }
             }
           }

           /* add ipsec interfaces */
           if (isset($config['ipsec']['enable']) || isset($config['ipsec']['client']['enable'])) {
               $iflist_tabs['enc0'] = 'IPsec';
           }

           /* add openvpn/tun interfaces */
           if (isset($config['openvpn']['openvpn-server']) || isset($config['openvpn']['openvpn-client'])) {
             $iflist_tabs['openvpn'] = 'OpenVPN';
           }

          $tab_array = array();
          foreach ($iflist_tabs as $ifent => $ifname) {
            $active = false;
            // mark active if selected or mark floating active when none is selected
            if ($ifent == $selected_if) {
                $active = true;
            }
            $tab_array[] = array($ifname, $active, "firewall_rules.php?if={$ifent}");
          }
          display_top_tabs($tab_array);
?>
          <div class="content-box">
            <form action="firewall_rules.php?if=<?=$selected_if;?>" method="post" name="iform" id="iform">
              <input type="hidden" id="id" name="id" value="" />
              <input type="hidden" id="action" name="act" value="" />
              <div class="table-responsive" >
                <table class="table table-striped table-hover" id="rules">
                  <thead>
                    <tr>
                      <th>&nbsp;</th>
                      <th>&nbsp;</th>
                      <th><?=gettext("Proto");?></th>
                      <th><?=gettext("Source");?></th>
                      <th class="hidden-xs hidden-sm"><?=gettext("Port");?></th>
                      <th class="hidden-xs hidden-sm"><?=gettext("Destination");?></th>
                      <th class="hidden-xs hidden-sm"><?=gettext("Port");?></th>
                      <th class="hidden-xs hidden-sm"><?=gettext("Gateway");?></th>
                      <th class="hidden-xs hidden-sm"><?=gettext("Schedule");?></th>
                      <th><?=gettext("Description");?></th>
                      <th></th>
                  </tr>
                </thead>
                <tbody>
<?php
                // Show floating block IPv6 rule if IPv6 is globally disabled in system settings
                if (!isset($config['system']['ipv6allow']) &&
                        ($selected_if == 'FloatingRules')):
?>
                  <tr valign="top">
                    <td>&nbsp;</td>
                    <td><span class="glyphicon glyphicon-remove text-danger"></span></td>
                    <td>IPv6 *</td>
                    <td>*</td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">&nbsp;</td>
                    <td><?=gettext("Block all IPv6 traffic");?></td>
                    <td>
                      <a href="system_advanced_network.php" data-toggle="tooltip" title="<?=gettext("change configuration");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                    </td>
                  </tr>
<?php
                endif; ?>
<?php
                // Show the anti-lockout rule if it's enabled, and we are on LAN with an if count > 1, or WAN with an if count of 1.
                if (!isset($config['system']['webgui']['noantilockout']) &&
                        (((count($config['interfaces']) > 1) && ($selected_if == 'lan'))
                        || ((count($config['interfaces']) == 1) && ($selected_if == 'wan')))):
                        $alports = implode('<br />', filter_get_antilockout_ports(true));
?>
                  <tr valign="top">
                    <td>&nbsp;</td>
                    <td><span class="glyphicon glyphicon-play text-success"></span></td>
                    <td>*</td>
                    <td>*</td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm"><?=htmlspecialchars(convert_friendly_interface_to_friendly_descr($selected_if));?> Address</td>
                    <td class="hidden-xs hidden-sm"><?=$alports;?></td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">&nbsp;</td>
                    <td><?=gettext("Anti-Lockout Rule");?></td>
                    <td>
                      <a href="system_advanced_admin.php" data-toggle="tooltip" title="<?=gettext("change configuration");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                    </td>
                  </tr>
<?php
                endif; ?>
<?php
                if (isset($config['interfaces'][$selected_if]['blockpriv'])): ?>
                  <tr>
                    <td>&nbsp;</td>
                    <td>
                      <span class="glyphicon glyphicon-remove text-danger"></span>
<?php
                      if (!isset($config['syslog']['nologprivatenets'])):?>
                      <span class="glyphicon glyphicon-info-sign"></span>
<?php
                      endif; ?>
                    </td>
                    <td>*</td>
                    <td><?=gettext("RFC 1918 networks");?></td>
                    <td>*</td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">&nbsp;</td>
                    <td class="hidden-xs hidden-sm"><?=gettext("Block private networks");?></td>
                    <td valign="middle" class="list nowrap">
                        <a href="interfaces.php?if=<?=$selected_if?>#rfc1918" data-toggle="tooltip" title="<?=gettext("change configuration");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                    </td>
                  </tr>
<?php
              endif;
              if (isset($config['interfaces'][$selected_if]['blockbogons'])): ?>
                  <tr valign="top" id="frrfc1918">
                    <td>&nbsp;</td>
                    <td>
                      <span class="glyphicon glyphicon-remove text-danger"></span>
<?php
                      if (!isset($config['syslog']['nologbogons'])):?>
                      <span class="glyphicon glyphicon-info-sign"></span>
<?php
                      endif; ?>
                    </td>
                    <td>*</td>
                    <td><?=gettext("Reserved/not assigned by IANA");?></td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">*</td>
                    <td class="hidden-xs hidden-sm">&nbsp;</td>
                    <td><?=gettext("Block bogon networks");?></td>
                    <td>
                      <a href="interfaces.php?if=<?=$selected_if?>#rfc1918" data-toggle="tooltip" title="<?=gettext("change configuration");?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                    </td>
                  </tr>
<?php
                endif; ?>
<?php
                $interface_has_rules = false;
                foreach ($a_filter as $i => $filterent):
                if ((!isset($filterent['floating']) && $selected_if == $filterent['interface']) ||
                     (isset($filterent['floating']) && $selected_if == 'FloatingRules')):
                  $interface_has_rules = true;

                  if (!isset($filterent['type'])) {
                      // not very nice.... associated NAT rules don't have a type...
                      // NAT rules shouldn't leak into the rules screen, breaks on edit
                      continue;
                  }

                  // select icon
                  if ($filterent['type'] == "block" && empty($filterent['disabled'])) {
                      $iconfn = "glyphicon-remove text-danger";
                  } elseif ($filterent['type'] == "block" && !empty($filterent['disabled'])) {
                      $iconfn = "glyphicon-remove text-muted";
                  }  elseif ($filterent['type'] == "reject" && empty($filterent['disabled'])) {
                      $iconfn = "glyphicon-remove  text-warning";
                  }  elseif ($filterent['type'] == "reject" && !empty($filterent['disabled'])) {
                      $iconfn = "glyphicon-remove  text-muted";
                  } else if ($filterent['type'] == "match" && empty($filterent['disabled'])) {
                      $iconfn = "glyphicon-ok";
                  } else if ($filterent['type'] == "match" && !empty($filterent['disabled'])) {
                      $iconfn = "glyphicon-ok text-muted";
                  } elseif (empty($filterent['disabled'])) {
                      $iconfn = "glyphicon-play text-success";
                  } else {
                      $iconfn = "glyphicon-play text-muted";
                  }

                  // construct line ipprotocol
                  if (isset($filterent['ipprotocol'])) {
                      switch($filterent['ipprotocol']) {
                          case "inet":
                              $record_ipprotocol = "IPv4 ";
                              break;
                          case "inet6":
                              $record_ipprotocol = "IPv6 ";
                              break;
                          case "inet46":
                              $record_ipprotocol = "IPv4+6 ";
                              break;
                      }
                  } else {
                      $record_ipprotocol = "IPv4 ";
                  }


?>
                  <tr class="rule" data-category="<?=!empty($filterent['category']) ? $filterent['category'] : "";?>">
                    <td>
                      <input type="checkbox" name="rule[]" value="<?=$i;?>"  />
                    </td>
                    <td>
                      <a href="#" class="act_toggle" id="toggle_<?=$i;?>" data-toggle="tooltip" title="<?=(empty($filterent['disabled'])) ? gettext("disable rule") : gettext("enable rule");?>"><span class="glyphicon <?=$iconfn;?>"></span></a>
<?php
                      if (!empty($filterent['direction']) && $filterent['direction'] == "in"):?>
                        <i class="fa fa-long-arrow-right" data-toggle="tooltip" title="<?=gettext("in");?>"></i>
<?php
                      elseif (!empty($filterent['direction']) && $filterent['direction'] == "out"):?>
                        <i class="fa fa-long-arrow-left" data-toggle="tooltip" title="<?=gettext("out");?>"></i>
<?php
                      elseif (!empty($filterent['direction']) && $filterent['direction'] == "any"):?>
                        <i class="fa fa-arrows-h" data-toggle="tooltip" title="<?=gettext("any");?>"></i>
<?php                 endif;?>
<?php                 if ($selected_if != 'FloatingRules'):
                        ; // interfaces are always quick
                      elseif (isset($filterent['quick']) && $filterent['quick'] === 'yes'): ?>
                        <i class="fa fa-flash text-warning" data-toggle="tooltip" title="<?= gettext('first match') ?>"></i>
<?php                 else: ?>
                        <i class="fa fa-flash text-muted" data-toggle="tooltip" title="<?= gettext('last match') ?>"></i>
<?php                 endif; ?>
<?php                 if (isset($filterent['log'])):?>
                      <span class="glyphicon glyphicon-info-sign <?=!empty($filterent['disabled']) ? "text-muted" :""?>"></span>
<?php                 endif; ?>
                    </td>

                    <td>
                        <?=$record_ipprotocol;?>
<?php
                        $icmptypes = array(
                          "" => gettext("any"),
                          "echoreq" => gettext("Echo request"),
                          "echorep" => gettext("Echo reply"),
                          "unreach" => gettext("Destination unreachable"),
                          "squench" => gettext("Source quench"),
                          "redir" => gettext("Redirect"),
                          "althost" => gettext("Alternate Host"),
                          "routeradv" => gettext("Router advertisement"),
                          "routersol" => gettext("Router solicitation"),
                          "timex" => gettext("Time exceeded"),
                          "paramprob" => gettext("Invalid IP header"),
                          "timereq" => gettext("Timestamp"),
                          "timerep" => gettext("Timestamp reply"),
                          "inforeq" => gettext("Information request"),
                          "inforep" => gettext("Information reply"),
                          "maskreq" => gettext("Address mask request"),
                          "maskrep" => gettext("Address mask reply")
                        );
                        if (isset($filterent['protocol']) && $filterent['protocol'] == "icmp" && !empty($filterent['icmptype'])):
?>
                        <span data-toggle="tooltip" title="ICMP type: <?=$icmptypes[$filterent['icmptype']];?> ">
                            <?= isset($filterent['protocol']) ? strtoupper($filterent['protocol']) : "*";?>
                        </span>
<?php
                        else:?>
                        <?= isset($filterent['protocol']) ? strtoupper($filterent['protocol']) : "*";?>
<?php
                        endif;?>
                    </td>

                    <td>
<?php                 if (isset($filterent['source']['address']) && is_alias($filterent['source']['address'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($filterent['source']['address']));?>" data-toggle="tooltip">
                          <?=htmlspecialchars(pprint_address($filterent['source']));?>&nbsp;
                        </span>
                        <a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($filterent['source']['address']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=htmlspecialchars(pprint_address($filterent['source']));?>
<?php                 endif; ?>
                    </td>

                    <td class="hidden-xs hidden-sm">
<?php                 if (isset($filterent['source']['port']) && is_alias($filterent['source']['port'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($filterent['source']['port']));?>" data-toggle="tooltip">
                          <?=htmlspecialchars(pprint_port($filterent['source']['port'])); ?>&nbsp;
                        </span>
                        <a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($filterent['source']['port']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=htmlspecialchars(pprint_port(isset($filterent['source']['port']) ? $filterent['source']['port'] : null)); ?>
<?php                 endif; ?>
                    </td>

                    <td class="hidden-xs hidden-sm">
<?php                 if (isset($filterent['destination']['address']) && is_alias($filterent['destination']['address'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($filterent['destination']['address']));?>" data-toggle="tooltip">
                          <?=htmlspecialchars(pprint_address($filterent['destination'])); ?>
                        </span>
                        <a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($filterent['destination']['address']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=htmlspecialchars(pprint_address($filterent['destination'])); ?>
<?php                 endif; ?>
                    </td>

                    <td class="hidden-xs hidden-sm">
<?php                 if (isset($filterent['destination']['port']) && is_alias($filterent['destination']['port'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($filterent['destination']['port']));?>" data-toggle="tooltip">
                          <?=htmlspecialchars(pprint_port($filterent['destination']['port'])); ?>&nbsp;
                        </span>
                        <a href="/firewall_aliases_edit.php?name=<?=htmlspecialchars($filterent['destination']['port']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=htmlspecialchars(pprint_port(isset($filterent['destination']['port']) ? $filterent['destination']['port'] : null)); ?>
<?php                 endif; ?>
                    </td>

                    <td class="hidden-xs hidden-sm">
<?php
                       if (isset($filterent['gateway'])):?>
                      <?=isset($config['interfaces'][$filterent['gateway']]['descr']) ? htmlspecialchars($config['interfaces'][$filterent['gateway']]['descr']) : htmlspecialchars(pprint_port($filterent['gateway'])); ?>
<?php
                      else: ?>
                      *
<?php                 endif; ?>
                    </td>
                    <td class="hidden-xs hidden-sm">
<?php
                      if (!empty($filterent['sched'])):?>
<?php
                        $schedule_descr = "";
                        if (isset($config['schedules']['schedule']))
                        {
                            foreach ($config['schedules']['schedule'] as $schedule)
                            {
                                if ($schedule['name'] == $filterent['sched'])
                                {
                                    $schedule_descr = (isset($schedule['descr'])) ? $schedule['descr'] : "";
                                }
                            }
                        }
?>
                        <span title="<?=htmlspecialchars($schedule_descr);?>" data-toggle="tooltip">
                          <?=htmlspecialchars($filterent['sched']);?>&nbsp;
                        </span>
                        <a href="/firewall_schedule_edit.php?name=<?=htmlspecialchars($filterent['sched']);?>"
                            title="<?=gettext("edit schedule");?>" data-toggle="tooltip">
                          <i class="glyphicon glyphicon-calendar"></i>
                        </a>
<?php
                       endif;?>
                    </td>
                    <td>
                      <?=htmlspecialchars($filterent['descr']);?>
                    </td>
                    <td>
                      <a id="move_<?=$i;?>" name="move_<?=$i;?>_x" data-toggle="tooltip" title="<?=gettext("move selected rules before this rule");?>" class="act_move btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-arrow-left"></span>
                      </a>
                      <a href="firewall_rules_edit.php?id=<?=$i;?>" data-toggle="tooltip" title="<?=gettext("edit rule");?>" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-pencil"></span>
                      </a>
                      <a id="del_<?=$i;?>" title="<?=gettext("delete rule"); ?>" data-toggle="tooltip"  class="act_delete btn btn-default btn-xs">
                        <span class="fa fa-trash text-muted"></span>
                      </a>
                      <a href="firewall_rules_edit.php?dup=<?=$i;?>" class="btn btn-default btn-xs" data-toggle="tooltip" title="<?=gettext("clone rule");?>">
                        <span class="fa fa-clone text-muted"></span>
                      </a>
                    </td>
                  </tr>
<?php
                  endif;
                  endforeach;
                  if (!$interface_has_rules):
?>
                  <tr>
                    <td colspan="11" align="center" valign="middle">
                    <span class="gray">
                <?php if ($selected_if == "FloatingRules"): ?>
                      <?=gettext("No floating rules are currently defined."); ?><br /><br />
                <?php else: ?>
                      <?=gettext("No rules are currently defined for this interface"); ?><br />
                      <?=gettext("All incoming connections on this interface will be blocked until you add pass rules."); ?><br /><br />
                <?php endif; ?>
                      <?=gettext("Click the"); ?>
                      <a href="firewall_rules_edit.php?if=<?=$selected_if;?>"  class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-plus"></span>
                      </a>
                      <?=gettext(" button to add a new rule.");?></span>
                    </td>
                  </tr>
              <?php else: ?>
                  <tr>
                    <td colspan="5">
                      <select class="selectpicker" data-live-search="true" data-size="5"  placeholder="<?=gettext("select category");?>" id="fw_category">
                        <option value=""><?=gettext("Filter by category");?></value>
<?php
                        // collect unique list of categories and append to option list
                        $categories = array();
                        foreach ($a_filter as $tmp_rule) {
                            if (!empty($tmp_rule['category']) && !in_array($tmp_rule['category'], $categories)) {
                                $categories[] = $tmp_rule['category'];
                            }
                        }
                        foreach ($categories as $category):?>
                        <option value="<?=$category;?>"><?=$category;?></value>
<?php
                        endforeach;?>
                      </select>
                    </td>
                    <td colspan="5" class="hidden-xs hidden-sm"></td>
                    <td>
                      <a type="submit" id="move_<?=$i;?>" name="move_<?=$i;?>_x" data-toggle="tooltip" title="<?=gettext("move selected rules to end");?>" class="act_move btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-arrow-left"></span>
                      </a>
                      <a id="del_x" title="<?=gettext("delete selected rules"); ?>" data-toggle="tooltip"  class="act_delete btn btn-default btn-xs">
                        <span class="fa fa-trash text-muted"></span>
                      </a>
                      <a href="firewall_rules_edit.php?if=<?=$selected_if;?>" class="btn btn-default btn-xs" data-toggle="tooltip" title="<?=gettext("add new rule");?>">
                        <span class="glyphicon glyphicon-plus"></span>
                      </a>
                    </td>
                  </tr>
              <?php endif; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="11">&nbsp;</td>
                  </tr>
                  <tr class="hidden-xs hidden-sm">
                    <td colspan="11">
                      <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                          <td width="16"><span class="glyphicon glyphicon-play text-success"></span></td>
                          <td width="100"><?=gettext("pass");?></td>
                          <td width="14"></td>
                          <td width="16"><span class="glyphicon glyphicon-ok"></span></td>
                          <td width="100"><?=gettext("match");?></td>
                          <td width="14"></td>
                          <td width="16"><span class="glyphicon glyphicon-remove text-danger"></span></td>
                          <td width="100"><?=gettext("block");?></td>
                          <td width="14"></td>
                          <td width="16"><span class="glyphicon glyphicon-remove text-warning"></span></td>
                          <td width="100"><?=gettext("reject");?></td>
                          <td width="14"></td>
                          <td width="16"><span class="glyphicon glyphicon-info-sign"></span></td>
                          <td width="100"><?=gettext("log");?></td>
                          <td width="16"><span class="fa fa-long-arrow-right"></span></td>
                          <td width="100"><?=gettext("in");?></td>
<?php                     if ($selected_if == 'FloatingRules'): ?>
                          <td width="16"><span class="fa fa-flash text-warning"></span></td>
                          <td width="100"><?=gettext("first match");?></td>
<?php                     endif; ?>
                        </tr>
                        <tr>
                          <td><span class="glyphicon glyphicon-play text-muted"></span></td>
                          <td class="nowrap"><?=gettext("pass (disabled)");?></td>
                          <td>&nbsp;</td>
                          <td><span class="glyphicon glyphicon-ok text-muted"></span></td>
                          <td class="nowrap"><?=gettext("match (disabled)");?></td>
                          <td>&nbsp;</td>
                          <td><span class="glyphicon glyphicon-remove text-muted"></span></td>
                          <td class="nowrap"><?=gettext("block (disabled)");?></td>
                          <td>&nbsp;</td>
                          <td><span class="glyphicon glyphicon-remove text-muted"></span></td>
                          <td class="nowrap"><?=gettext("reject (disabled)");?></td>
                          <td>&nbsp;</td>
                          <td width="16"><span class="glyphicon glyphicon-info-sign text-muted"></span></td>
                          <td class="nowrap"><?=gettext("log (disabled)");?></td>
                          <td width="16"><span class="fa fa-long-arrow-left"></span></td>
                          <td width="100"><?=gettext("out");?></td>
<?php                     if ($selected_if == 'FloatingRules'): ?>
                          <td width="16"><span class="fa fa-flash text-muted"></span></td>
                          <td width="100"><?=gettext("last match");?></td>
<?php                     endif; ?>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr class="hidden-xs hidden-sm">
                    <td><a><i class="fa fa-list"></i></a></td>
                    <td colspan="10"><?=gettext("Alias (click to view/edit)");?></td>
                  </tr>
                  <tr class="hidden-xs hidden-sm">
                    <td><a><span class="glyphicon glyphicon-calendar"> </span></a></td>
                    <td colspan="10"><?=gettext("Schedule (click to view/edit)");?></td>
                  </tr>
                  <tr class="hidden-xs hidden-sm">
                    <td colspan="11">
                      <strong>
                        <span class="text-danger"><?=gettext("Hint:");?></span>
                      </strong>
                      <br />
                      <?php if ("FloatingRules" != $selected_if): ?>
                      <?=gettext("Rules are evaluated on a first-match basis (i.e. " .
                        "the action of the first rule to match a packet will be executed). " .
                        "This means that if you use block rules, you'll have to pay attention " .
                        "to the rule order. Everything that isn't explicitly passed is blocked " .
                        "by default. ");?>
                      <?php else: ?>
                        <?=gettext("Floating rules are evaluated on a first-match basis (i.e. " .
                        "the action of the first rule to match a packet will be executed) only " .
                        "if the 'quick' option is checked on a rule. Otherwise they will only apply if no " .
                        "other rules match. Pay close attention to the rule order and options " .
                        "chosen. If no rule here matches, the per-interface or default rules are used. ");?>
                      <?php endif; ?>
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
