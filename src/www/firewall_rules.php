<?php

/*
 * Copyright (C) 2014-2015 Deciso B.V.
 * Copyright (C) 2005 Scott Ullrich <sullrich@gmail.com>
 * Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>
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
require_once("filter.inc");
require_once("system.inc");

/***********************************************************************************************************
 * format functions for this page
 ***********************************************************************************************************/
function firewall_rule_item_proto($filterent)
{
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
    $icmptypes = array(
      "" => gettext("any"),
      "echoreq" => gettext("Echo Request"),
      "echorep" => gettext("Echo Reply"),
      "unreach" => gettext("Destination Unreachable"),
      "squench" => gettext("Source Quench (Deprecated)"),
      "redir" => gettext("Redirect"),
      "althost" => gettext("Alternate Host Address (Deprecated)"),
      "routeradv" => gettext("Router Advertisement"),
      "routersol" => gettext("Router Solicitation"),
      "timex" => gettext("Time Exceeded"),
      "paramprob" => gettext("Parameter Problem"),
      "timereq" => gettext("Timestamp"),
      "timerep" => gettext("Timestamp Reply"),
      "inforeq" => gettext("Information Request (Deprecated)"),
      "inforep" => gettext("Information Reply (Deprecated)"),
      "maskreq" => gettext("Address Mask Request (Deprecated)"),
      "maskrep" => gettext("Address Mask Reply (Deprecated)")
    );
    $icmp6types = array(
      "" => gettext("any"),
      "unreach" => gettext("Destination unreachable"),
      "toobig" => gettext("Packet too big"),
      "timex" => gettext("Time exceeded"),
      "paramprob" => gettext("Invalid IPv6 header"),
      "echoreq" => gettext("Echo service request"),
      "echorep" => gettext("Echo service reply"),
      "groupqry" => gettext("Group membership query"),
      "listqry" => gettext("Multicast listener query"),
      "grouprep" => gettext("Group membership report"),
      "listenrep" => gettext("Multicast listener report"),
      "groupterm" => gettext("Group membership termination"),
      "listendone" => gettext("Multicast listener done"),
      "routersol" => gettext("Router solicitation"),
      "routeradv" => gettext("Router advertisement"),
      "neighbrsol" => gettext("Neighbor solicitation"),
      "neighbradv" => gettext("Neighbor advertisement"),
      "redir" => gettext("Shorter route exists"),
      "routrrenum" => gettext("Route renumbering"),
      "fqdnreq" => gettext("FQDN query"),
      "niqry" => gettext("Node information query"),
      "wrureq" => gettext("Who-are-you request"),
      "fqdnrep" => gettext("FQDN reply"),
      "nirep" => gettext("Node information reply"),
      "wrurep" => gettext("Who-are-you reply"),
      "mtraceresp" => gettext("mtrace response"),
      "mtrace" => gettext("mtrace messages")
    );
    if (isset($filterent['protocol']) && $filterent['protocol'] == "icmp" && !empty($filterent['icmptype'])) {
        $result = $record_ipprotocol;
        $result .= sprintf(
          "<span data-toggle=\"tooltip\" title=\"ICMP type: %s \"> %s </span>",
          html_safe($icmptypes[$filterent['icmptype']]),
          isset($filterent['protocol']) ? strtoupper($filterent['protocol']) : "*"
        );
        return $result;
    } elseif (isset($filterent['protocol']) && !empty($filterent['icmp6-type'])) {
        $result = $record_ipprotocol;
        $result .= sprintf(
          "<span data-toggle=\"tooltip\" title=\"ICMP6 type: %s \"> %s </span>",
          html_safe($icmp6types[$filterent['icmp6-type']]),
          isset($filterent['protocol']) ? strtoupper($filterent['protocol']) : "*"
        );
        return $result;
    } else {
        return $record_ipprotocol . (isset($filterent['protocol']) ? strtoupper($filterent['protocol']) : "*");
    }
}


function firewall_rule_item_icons($filterent)
{
    $result = "";
    if (!empty($filterent['direction']) && $filterent['direction'] == "in") {
        $result .= sprintf(
            "<i class=\"fa fa-long-arrow-right text-info\" data-toggle=\"tooltip\" title=\"%s\"></i>",
            gettext("in")
        );
    } elseif (!empty($filterent['direction']) && $filterent['direction'] == "out") {
        $result .= sprintf(
            "<i class=\"fa fa-long-arrow-left\" data-toggle=\"tooltip\" title=\"%s\"></i>",
            gettext("out")
        );
    }
    if (!empty($filterent['floating'])) {
        if (isset($filterent['quick']) && $filterent['quick'] === 'yes') {
            $result .= sprintf(
                "<i class=\"fa fa-flash text-warning\" data-toggle=\"tooltip\" title=\"%s\"></i>",
                gettext('first match')
            );
        } else {
          $result .= sprintf(
              "<i class=\"fa fa-flash text-muted\" data-toggle=\"tooltip\" title=\"%s\"></i>",
              gettext('last match')
          );
        }
    }
    if (isset($filterent['log'])) {
          $result .= sprintf(
              "<i class=\"fa fa-info-circle %s\"></i>",
              !empty($filterent['disabled']) ? 'text-muted' : 'text-info'
          );
    }

    return $result;
}

function firewall_rule_item_action($filterent)
{
    if ($filterent['type'] == "block" && empty($filterent['disabled'])) {
        return "fa fa-times text-danger";
    } elseif ($filterent['type'] == "block" && !empty($filterent['disabled'])) {
        return "fa fa-times text-muted";
    }  elseif ($filterent['type'] == "reject" && empty($filterent['disabled'])) {
        return "fa fa-times-circle text-danger";
    }  elseif ($filterent['type'] == "reject" && !empty($filterent['disabled'])) {
        return "fa fa-times-circle text-muted";
    } elseif (empty($filterent['disabled'])) {
        return "fa fa-play text-success";
    } else {
        return "fa fa-play text-muted";
    }
}
/***********************************************************************************************************
 *
 ***********************************************************************************************************/

$a_filter = &config_read_array('filter', 'rule');

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
        system_cron_configure();
        filter_configure();
        clear_subsystem_dirty('filter');
        $savemsg = gettext('The settings have been applied and the rules are now reloading in the background.');
    } elseif (isset($pconfig['act']) && $pconfig['act'] == 'del' && isset($id)) {
        // delete single item
        if (!empty($a_filter[$id]['associated-rule-id'])) {
            // unlink nat entry
            if (isset($config['nat']['rule'])) {
                $a_nat = &config_read_array('nat', 'rule');
                foreach ($a_nat as &$natent) {
                    if ($natent['associated-rule-id'] == $a_filter[$id]['associated-rule-id']) {
                        $natent['associated-rule-id'] = '';
                    }
                }
            }
        }
        unset($a_filter[$id]);
        write_config();
        mark_subsystem_dirty('filter');
        header(url_safe('Location: /firewall_rules.php?if=%s', array($current_if)));
        exit;
    } elseif (isset($pconfig['act']) && $pconfig['act'] == 'del_x' && isset($pconfig['rule']) && count($pconfig['rule']) > 0) {
        // delete selected rules
        foreach ($pconfig['rule'] as $rulei) {
            // unlink nat entry
            if (isset($config['nat']['rule'])) {
                $a_nat = &config_read_array('nat', 'rule');
                foreach ($a_nat as &$natent) {
                    if ($natent['associated-rule-id'] == $a_filter[$rulei]['associated-rule-id']) {
                        $natent['associated-rule-id'] = '';
                    }
                }
            }
            unset($a_filter[$rulei]);
        }
        write_config();
        mark_subsystem_dirty('filter');
        header(url_safe('Location: /firewall_rules.php?if=%s', array($current_if)));
        exit;
    } elseif (isset($pconfig['act']) && in_array($pconfig['act'], array('toggle_enable', 'toggle_disable')) && isset($pconfig['rule']) && count($pconfig['rule']) > 0) {
        foreach ($pconfig['rule'] as $rulei) {
            $a_filter[$rulei]['disabled'] = $pconfig['act'] == 'toggle_disable';
        }
        write_config();
        mark_subsystem_dirty('filter');
        header(url_safe('Location: /firewall_rules.php?if=%s', array($current_if)));
        exit;
    } elseif ( isset($pconfig['act']) && $pconfig['act'] == 'move' && isset($pconfig['rule']) && count($pconfig['rule']) > 0) {
        // move selected rules
        if (!isset($id)) {
            // if rule not set/found, move to end
            $id = count($a_filter);
        }
        $a_filter = legacy_move_config_list_items($a_filter, $id,  $pconfig['rule']);
        write_config();
        mark_subsystem_dirty('filter');
        header(url_safe('Location: /firewall_rules.php?if=%s', array($current_if)));
        exit;
    } elseif (isset($pconfig['act']) && $pconfig['act'] == 'toggle' && isset($id)) {
        // toggle item
        if(isset($a_filter[$id]['disabled'])) {
            unset($a_filter[$id]['disabled']);
        } else {
            $a_filter[$id]['disabled'] = true;
        }
        write_config();
        mark_subsystem_dirty('filter');
        header(url_safe('Location: /firewall_rules.php?if=%s', array($current_if)));
        exit;
    }
}

if (isset($_GET['if'])) {
    $selected_if = htmlspecialchars($_GET['if']);
} else {
    $selected_if = "FloatingRules";
}
if (isset($_GET['category'])) {
    $selected_category = !is_array($_GET['category']) ? array($_GET['category']) : $_GET['category'];
} else {
    $selected_category = array();
}

include("head.inc");

$main_buttons = array(
    array('label' => gettext('Add'), 'href' => 'firewall_rules_edit.php?if=' . $selected_if),
);

legacy_html_escape_form_data($a_filter);
$all_rule_stats = json_decode(configd_run("filter rule stats"), true);
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

  // watch scroll position and set to last known on page load
  watchScrollPosition();

  // link category select/search
  $("#fw_category").change(function(){
      var stripe_color = 'transparent';
      var selected_values = [];
      $("#fw_category > option:selected").each(function(){
          if ($(this).val() != "") {
              selected_values.push($(this).val());
          } else {
              // select all when "Filter by category" is selected
              selected_values = [];
              return false;
          }
      });
      $(".rule").each(function(){
          // save zebra color
          if ( $(this).children(0).css("background-color") != 'transparent') {
              $("#fw_category").data('stripe_color', $(this).children(0).css("background-color"));
          }

          if (selected_values.indexOf($(this).data('category')) == -1 && selected_values.length > 0) {
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

      // hook into tab changes, keep selected category/categories when following link
      $("#Firewall_Rules > .menu-level-3-item").each(function(){
          var add_link = "";
          if (selected_values.length > 0) {
              add_link = "&" + $.param({'category': selected_values});
          }
          if ($(this).is('A')) {
              if ($(this).data('link') == undefined) {
                  // move link to data tag
                  $(this).data('link', $(this).attr('href'));
              }
              $(this).attr('href', $(this).data('link') + add_link);
          } else if ($(this).is('OPTION')) {
            if ($(this).data('link') == undefined) {
                // move link to data tag
                $(this).data('link', $(this).val());
            }
            $(this).val($(this).data('link') + add_link);
          }
      });
  });
  $("#fw_category").change();

  // hide category search when not used
  if ($("#fw_category > option").length == 0) {
      $("#fw_category").addClass('hidden');
  }

  // select All
  $("#selectAll").click(function(){
      $(".rule_select").prop("checked", $(this).prop("checked"));
  });

  // move category block
  $("#category_block").detach().appendTo($(".page-content-head > .container-fluid > .list-inline"));
  $("#category_block").addClass("pull-right");

  $("#btn_inspect").click(function(){
      let mode = $(this).data('mode');
      if (mode === 'stats') {
            $(".view-stats").hide();
            $(".view-info").show();
            $(this).removeClass('active');
            $(this).data('mode', 'info');
      } else {
            $(".view-stats").show();
            $(".view-info").hide();
            $(this).addClass('active');
            $(this).data('mode', 'stats');
      }
      $(this).blur();
  });

  // expand internal auto generated rules
  if ($("tr.internal-rule").length > 0) {
      $("#expand-internal-rules").show();
      $("#internal-rule-count").text($("tr.internal-rule").length);
  }

  $("#expand-internal").click(function(event){
      event.preventDefault();
      $(".internal-rule").toggle();
      $("#rules").removeClass("table-striped");
      $("#rules").addClass("table-striped");
  });


});
</script>
<style>
    .view-stats {
        display: none;
    }
    .button-th {
        width: 150px;
    }
</style>

<?php include("fbegin.inc"); ?>
  <div class="hidden">
    <div id="category_block" style="z-index:-100;">
        <div class="hidden-sm hidden-lg"><br/></div>
        <select class="selectpicker" data-live-search="true" data-size="5"  multiple placeholder="<?=gettext("Select category");?>" id="fw_category">
<?php
            // collect unique list of categories and append to option list
            $categories = array();
            foreach ($a_filter as $tmp_rule) {
              if (!empty($tmp_rule['category']) && !in_array($tmp_rule['category'], $categories)) {
                  $categories[] = $tmp_rule['category'];
              }
            }
            foreach ($categories as $category):?>
                <option value="<?=$category;?>" <?=in_array($category, $selected_category) ? "selected=\"selected\"" : "" ;?>><?=$category;?></option>
<?php
            endforeach;?>
        </select>
        <button id="btn_inspect" class="btn btn-default">
          <i class="fa fa-eye" aria-hidden="true"></i>
          <?=gettext("Inspect");?>
        </button>
    </div>
  </div>
  <section class="page-content-main">
    <div class="container-fluid">
      <div class="row">
        <?php print_service_banner('firewall'); ?>
        <?php if (isset($savemsg)) print_info_box($savemsg); ?>
        <?php if (is_subsystem_dirty('filter')): ?><p>
        <?php print_info_box_apply(gettext("The firewall rule configuration has been changed.<br />You must apply the changes in order for them to take effect."));?>
        <?php endif; ?>
        <section class="col-xs-12">
          <div class="content-box">
            <form action="firewall_rules.php?if=<?=$selected_if;?>" method="post" name="iform" id="iform">
              <input type="hidden" id="id" name="id" value="" />
              <input type="hidden" id="action" name="act" value="" />
              <div class="table-responsive" >
                <table class="table table-striped table-condensed table-hover" id="rules">
                  <thead>
                    <tr>
                      <th><input type="checkbox" id="selectAll"></th>
                      <th>&nbsp;</th>
                      <th class="view-info"><?=gettext("Proto");?></th>
                      <th class="view-info"><?=gettext("Source");?></th>
                      <th class="view-info hidden-xs hidden-sm"><?=gettext("Port");?></th>
                      <th class="view-info hidden-xs hidden-sm"><?=gettext("Destination");?></th>
                      <th class="view-info hidden-xs hidden-sm"><?=gettext("Port");?></th>
                      <th class="view-info hidden-xs hidden-sm"><?=gettext("Gateway");?></th>
                      <th class="view-info hidden-xs hidden-sm"><?=gettext("Schedule");?></th>
                      <th class="view-stats"><?=gettext("Evaluations");?></th>
                      <th class="view-stats"><?=gettext("Packets");?></th>
                      <th class="view-stats"><?=gettext("Bytes");?></th>
                      <th class="view-stats"><?=gettext("States");?></th>
                      <th class="text-nowrap">
                        <?=gettext("Description");?>
                        <i class="fa fa-question-circle" data-toggle="collapse" data-target=".rule_md5_hash" ></i>
                      </th>
                      <th class="button-th"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr id="expand-internal-rules" style="display: none;">
                      <td>&nbsp;</td>
                      <td>
                          <button class="btn btn-default btn-xs" id="expand-internal">
                            <i class="fa fa-chevron-circle-down" aria-hidden="true"></i>
                            <span class="badge">
                              <span id="internal-rule-count"><span>
                            </span>
                          </button>
                      </td>
                      <td class="view-info" colspan="2"> </td>
                      <td class="view-info hidden-xs hidden-sm" colspan="5"> </td>
                      <td class="view-stats"></td>
                      <td><?=gettext("Automatically generated");?> </td>
                      <td> </td>
                  </tr>
<?php
                $fw = filter_core_get_initialized_plugin_system();
                filter_core_bootstrap($fw);
                plugins_firewall($fw);
                foreach ($fw->iterateFilterRules() as $rule):
                    if ($rule->getInterface() == $selected_if && $rule->isEnabled()):
                        $filterent = $rule->getRawRule();
                        $rule_stats = !empty($rule->getLabel()) ? $all_rule_stats[$rule->getLabel()] : array();?>
                    <tr class="internal-rule" style="display: none;">
                      <td>&nbsp;</td>
                      <td>
                          <span class="<?=firewall_rule_item_action($filterent);?>"></span><?=firewall_rule_item_icons($filterent);?>
                      </td>
                      <td class="view-info">
                          <?=firewall_rule_item_proto($filterent);?>
                      </td>
                      <td class="view-info">
                          <?=!empty($filterent['from']) ? $filterent['from'] : "*";?>
                      </td>
                      <td class="view-info hidden-xs hidden-sm">
                          <?=!empty($filterent['from_port']) ? $filterent['from_port'] : "*";?>
                      </td>
                      <td class="view-info hidden-xs hidden-sm">
                          <?=!empty($filterent['to']) ? $filterent['to'] : "*";?>
                      </td>
                      <td class="view-info hidden-xs hidden-sm">
                          <?=!empty($filterent['to_port']) ? $filterent['to_port'] : "*";?>
                      </td>
                      <td class="view-info hidden-xs hidden-sm">
                        <?= !empty($filterent['gateway']) ? $filterent['gateway'] : "*";?>
                      </td>
                      <td class="view-info hidden-xs hidden-sm">&nbsp;</td>
                      <td class="view-stats"><?=!empty($rule_stats) ? $rule_stats['evaluations'] : "";?></td>
                      <td class="view-stats"><?=!empty($rule_stats) ? $rule_stats['packets'] : "";?></td>
                      <td class="view-stats"><?=!empty($rule_stats) ? format_bytes($rule_stats['bytes']) : "";?></td>
                      <td class="view-stats"><?=!empty($rule_stats) ? $rule_stats['states'] : "";?></td>
                      <td><?=$rule->getDescr();?></td>
                      <td>
<?php
                        if (!empty($rule->getRef())):?>
                          <a href="firewall_rule_lookup.php?rid=<?=html_safe($rule->getLabel());?>" class="btn btn-default btn-xs"><i class="fa fa-fw fa-search"></i></a>
<?php
                        endif;?>
                      </td>
                    </tr>
<?php
                    endif;
                endforeach;?>
<?php
                $interface_has_rules = false;
                foreach ($a_filter as $i => $filterent):
                if (
                    (!isset($filterent['floating']) && $selected_if == $filterent['interface']) ||
                     (
                        (isset($filterent['floating']) || empty($filterent['interface'])) &&
                        $selected_if == 'FloatingRules'
                     )
                ):
                  // calculate a hash so we can track these records in the ruleset, new style (mvc) code will
                  // automatically provide us with a uuid, this is a workaround to provide some help with tracking issues.
                  $rule_hash = OPNsense\Firewall\Util::calcRuleHash($filterent);
                  $interface_has_rules = true;
?>
                  <tr class="rule  <?=isset($filterent['disabled'])?"text-muted":"";?>" data-category="<?=!empty($filterent['category']) ? $filterent['category'] : "";?>">
                    <td>
                      <input class="rule_select" type="checkbox" name="rule[]" value="<?=$i;?>"  />
                    </td>
                    <td>
                      <a href="#" class="act_toggle" id="toggle_<?=$i;?>" data-toggle="tooltip" title="<?=(empty($filterent['disabled'])) ? gettext("Disable") : gettext("Enable");?>">
                        <span class="<?=firewall_rule_item_action($filterent);?>"></span>
                      </a>
                      <?=firewall_rule_item_icons($filterent);?>
                    </td>
                    <td class="view-info">
                        <?=firewall_rule_item_proto($filterent);?>
                    </td>
                    <td class="view-info">
<?php                 if (isset($filterent['source']['address']) && is_alias($filterent['source']['address'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($filterent['source']['address']));?>" data-toggle="tooltip"  data-html="true">
                          <?=htmlspecialchars(pprint_address($filterent['source']));?>&nbsp;
                        </span>
                        <a href="/ui/firewall/alias/index/<?=htmlspecialchars($filterent['source']['address']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=htmlspecialchars(pprint_address($filterent['source']));?>
<?php                 endif; ?>
                    </td>

                    <td class="view-info hidden-xs hidden-sm">
<?php                 if (isset($filterent['source']['port']) && is_alias($filterent['source']['port'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($filterent['source']['port']));?>" data-toggle="tooltip"  data-html="true">
                          <?=htmlspecialchars(pprint_port($filterent['source']['port'])); ?>&nbsp;
                        </span>
                        <a href="/ui/firewall/alias/index/<?=htmlspecialchars($filterent['source']['port']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=htmlspecialchars(pprint_port(isset($filterent['source']['port']) ? $filterent['source']['port'] : null)); ?>
<?php                 endif; ?>
                    </td>

                    <td class="view-info hidden-xs hidden-sm">
<?php                 if (isset($filterent['destination']['address']) && is_alias($filterent['destination']['address'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($filterent['destination']['address']));?>" data-toggle="tooltip"  data-html="true">
                          <?=htmlspecialchars(pprint_address($filterent['destination'])); ?>
                        </span>
                        <a href="/ui/firewall/alias/index/<?=htmlspecialchars($filterent['destination']['address']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=htmlspecialchars(pprint_address($filterent['destination'])); ?>
<?php                 endif; ?>
                    </td>

                    <td class="view-info hidden-xs hidden-sm">
<?php                 if (isset($filterent['destination']['port']) && is_alias($filterent['destination']['port'])): ?>
                        <span title="<?=htmlspecialchars(get_alias_description($filterent['destination']['port']));?>" data-toggle="tooltip"  data-html="true">
                          <?=htmlspecialchars(pprint_port($filterent['destination']['port'])); ?>&nbsp;
                        </span>
                        <a href="/ui/firewall/alias/index/<?=htmlspecialchars($filterent['destination']['port']);?>"
                            title="<?=gettext("edit alias");?>" data-toggle="tooltip">
                          <i class="fa fa-list"></i>
                        </a>
<?php                 else: ?>
                        <?=htmlspecialchars(pprint_port(isset($filterent['destination']['port']) ? $filterent['destination']['port'] : null)); ?>
<?php                 endif; ?>
                    </td>

                    <td class="view-info hidden-xs hidden-sm">
<?php
                       if (isset($filterent['gateway'])):?>
                      <?=isset($config['interfaces'][$filterent['gateway']]['descr']) ? htmlspecialchars($config['interfaces'][$filterent['gateway']]['descr']) : htmlspecialchars(pprint_port($filterent['gateway'])); ?>
<?php
                      else: ?>
                      *
<?php                 endif; ?>
                    </td>
                    <td class="view-info hidden-xs hidden-sm">
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
                                    break;
                                }
                            }
                        }
?>
                        <span title="<?=htmlspecialchars($schedule_descr);?>" data-toggle="tooltip">
                          <?=htmlspecialchars($filterent['sched']);?>&nbsp;
                        </span>
                        <a href="/firewall_schedule_edit.php?name=<?=htmlspecialchars($filterent['sched']);?>"
                            title="<?= html_safe(gettext('Edit')) ?>" data-toggle="tooltip">
<?php
                        if (filter_get_time_based_rule_status($schedule)):?>
                          <i class="fa fa-calendar text-success"></i>
<?php
                        else:?>
                          <i class="fa fa-calendar text-muted"></i>
<?php
                        endif;?>
                        </a>
<?php
                       endif;?>
                    </td>
                    <td class="view-stats"><?=!empty($all_rule_stats[$rule_hash]) ? $all_rule_stats[$rule_hash]['evaluations'] : "";?></td>
                    <td class="view-stats"><?=!empty($all_rule_stats[$rule_hash]) ? $all_rule_stats[$rule_hash]['packets'] : "";?></td>
                    <td class="view-stats"><?=!empty($all_rule_stats[$rule_hash]) ? format_bytes($all_rule_stats[$rule_hash]['bytes']) : "";?></td>
                    <td class="view-stats"><?=!empty($all_rule_stats[$rule_hash]) ? $all_rule_stats[$rule_hash]['states'] : "";?></td>
                    <td>
                      <?=$filterent['descr'];?>
                      <div class="collapse rule_md5_hash">
                          <small><?=$rule_hash;?></small>
                      </div>
                    </td>
                    <td>
                      <button id="move_<?=$i;?>" name="move_<?=$i;?>_x" data-toggle="tooltip" title="<?= html_safe(gettext("Move selected rules before this rule")) ?>" class="act_move btn btn-default btn-xs">
                        <i class="fa fa-arrow-left fa-fw"></i>
                      </button>
<?php
                      // not very nice.... associated NAT rules don't have a type...
                      // if for some reason (broken config) a rule is in there which doesn't have a related nat rule
                      // make sure we are able to delete it.
                      if (isset($filterent['type'])):?>
                      <a href="firewall_rules_edit.php?if=<?=$selected_if;?>&id=<?=$i;?>" data-toggle="tooltip" title="<?= html_safe(gettext('Edit')) ?>" class="btn btn-default btn-xs">
                        <i class="fa fa-pencil fa-fw"></i>
                      </a>
<?php
                      endif;?>
                      <a id="del_<?=$i;?>" title="<?= html_safe(gettext('Delete')) ?>" data-toggle="tooltip"  class="act_delete btn btn-default btn-xs">
                        <i class="fa fa-trash fa-fw"></i>
                      </a>
<?php
                      if (isset($filterent['type'])):?>
                      <a href="firewall_rules_edit.php?if=<?=$selected_if;?>&dup=<?=$i;?>" class="btn btn-default btn-xs" data-toggle="tooltip" title="<?= html_safe(gettext('Clone')) ?>">
                        <i class="fa fa-clone fa-fw"></i>
                      </a>
<?php
                      endif;?>
                    </td>
                  </tr>
<?php
                  endif;
                  endforeach;
                  $i++;
                  if (!$interface_has_rules):
?>
                  <tr>
                    <td colspan="11">
                    <span class="text-muted">
                <?php if ($selected_if == 'FloatingRules'): ?>
                      <?= gettext('No floating rules are currently defined. Floating rules are ' .
                        'not bound to a single interface and can therefore be used to span ' .
                        'policies over multiple networks at the same time.'); ?>
                <?php else: ?>
                      <?= gettext('No interfaces rules are currently defined. All incoming connections ' .
                        'on this interface will be blocked until you add a pass rule.') ?>
                <?php endif; ?>
                    </span>
                    </td>
                  </tr>
                <?php endif; ?>
                  <tr>
                    <td colspan="2"></td>
                    <td colspan="2" class="view-info"></td>
                    <td colspan="5" class="view-info hidden-xs hidden-sm"></td>
                    <td colspan="4" class="view-stats hidden-xs hidden-sm"></td>
                    <td></td>
                    <td>
                      <button id="move_<?=$i;?>" name="move_<?=$i;?>_x" data-toggle="tooltip" title="<?= html_safe(gettext('Move selected rules to end')) ?>" class="act_move btn btn-default btn-xs">
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
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="hidden-xs hidden-sm">
                    <td colspan="9">
                      <table style="width:100%; border:0;">
                        <tr>
                          <td style="width:16px"><span class="fa fa-play text-success"></span></td>
                          <td style="width:100px"><?=gettext("pass");?></td>
                          <td style="width:14px"></td>
                          <td style="width:16px"><span class="fa fa-times text-danger"></span></td>
                          <td style="width:100px"><?=gettext("block");?></td>
                          <td style="width:14px"></td>
                          <td style="width:16px"><span class="fa fa-times-circle text-danger"></span></td>
                          <td style="width:100px"><?=gettext("reject");?></td>
                          <td style="width:14px"></td>
                          <td style="width:16px"><span class="fa fa-info-circle text-info"></span></td>
                          <td style="width:100px"><?=gettext("log");?></td>
                          <td style="width:16px"><span class="fa fa-long-arrow-right text-info"></span></td>
                          <td style="width:100px"><?=gettext("in");?></td>
<?php                     if ($selected_if == 'FloatingRules'): ?>
                          <td style="width:16px"><span class="fa fa-flash text-warning"></span></td>
                          <td style="width:100px"><?=gettext("first match");?></td>
<?php                     endif; ?>
                        </tr>
                        <tr>
                          <td><span class="fa fa-play text-muted"></span></td>
                          <td class="nowrap"><?=gettext("pass (disabled)");?></td>
                          <td>&nbsp;</td>
                          <td><span class="fa fa-times text-muted"></span></td>
                          <td class="nowrap"><?=gettext("block (disabled)");?></td>
                          <td>&nbsp;</td>
                          <td><span class="fa fa-times-circle text-muted"></span></td>
                          <td class="nowrap"><?=gettext("reject (disabled)");?></td>
                          <td>&nbsp;</td>
                          <td style="width:16px"><span class="fa fa-info-circle text-muted"></span></td>
                          <td class="nowrap"><?=gettext("log (disabled)");?></td>
                          <td style="width:16px"><span class="fa fa-long-arrow-left"></span></td>
                          <td style="width:100px"><?=gettext("out");?></td>
<?php                     if ($selected_if == 'FloatingRules'): ?>
                          <td style="width:16px"><span class="fa fa-flash text-muted"></span></td>
                          <td style="width:100px"><?=gettext("last match");?></td>
<?php                     endif; ?>
                        </tr>
                      </table>
                    </td>
                    <td colspan="2" class="view-info"></td>
                  </tr>
                  <tr class="hidden-xs hidden-sm">
                    <td><i class="fa fa-list fa-fw text-primary"></i></td>
                    <td colspan="8"><?=gettext("Alias (click to view/edit)");?></td>
                    <td colspan="2" class="view-info"></td>
                  </tr>
                  <tr class="hidden-xs hidden-sm">
                    <td><i class="fa fa-calendar fa-fw text-success"></i><i class="fa fa-calendar fa-fw text-muted"></i></td>
                    <td colspan="8"><?=gettext("Active/Inactive Schedule (click to view/edit)");?></td>
                    <td colspan="2" class="view-info"></td>
                  </tr>
                  <tr class="hidden-xs hidden-sm">
                    <td colspan="8">
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
                    <td colspan="2" class="view-info"></td>
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
