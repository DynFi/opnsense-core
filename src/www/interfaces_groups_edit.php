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
require_once("plugins.inc");

if (!isset($config['ifgroups']) || !is_array($config['ifgroups'])) {
    $config['ifgroups'] = array();
}
if (!isset($config['ifgroups']['ifgroupentry']) || !is_array($config['ifgroups']['ifgroupentry'])) {
    $config['ifgroups']['ifgroupentry'] = array();
}

$a_ifgroups = &$config['ifgroups']['ifgroupentry'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // read form data
    if (!empty($a_ifgroups[$_GET['id']])) {
        $id = $_GET['id'];
    }
    $pconfig = array();
    $pconfig['ifname'] = isset($a_ifgroups[$id]['ifname']) ? $a_ifgroups[$id]['ifname'] : null;
    $pconfig['descr'] = isset($a_ifgroups[$id]['descr']) ?  $a_ifgroups[$id]['descr'] : null;
    $pconfig['members'] = isset($a_ifgroups[$id]['members']) ? explode(' ', $a_ifgroups[$id]['members']) : array();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // validate and save form data
    if (!empty($a_ifgroups[$_POST['id']])) {
        $id = $_POST['id'];
    }
    $input_errors = array();
    $pconfig = $_POST;

    if (!isset($id)) {
        foreach ($a_ifgroups as $groupentry) {
            if ($groupentry['ifname'] == $pconfig['ifname']) {
                $input_errors[] = gettext("Group name already exists!");
            }
        }
    }
    if (preg_match("/([^a-zA-Z])+/", $pconfig['ifname'], $match)) {
        $input_errors[] = gettext("Only letters A-Z are allowed as the group name.");
    }

    foreach (get_configured_interface_with_descr() as $gif => $gdescr) {
        if ($gdescr == $pconfig['ifname'] || $gif == $pconfig['ifname']) {
            $input_errors[] = gettext("The specified group name is already used by an interface. Please choose another name.");
        }
    }

    if (empty($pconfig['members'])) {
        $input_errors[] = gettext("At least one group member must be specified.");
        $pconfig['members'] = array();
    }

    if (count($input_errors) == 0) {
      $ifgroupentry = array();
      $ifgroupentry['members'] = implode(' ', $pconfig['members']);
      $ifgroupentry['descr'] = $pconfig['descr'];
      $ifgroupentry['ifname'] = $pconfig['ifname'];

      if (isset($id)) {
          // rename interface group
          if ($pconfig['ifname'] != $a_ifgroups[$id]['ifname']) {
              if (!empty($config['filter']) && is_array($config['filter']['rule'])) {
                  foreach ($config['filter']['rule'] as &$rule) {
                        $rule_ifs = explode(",", $rule['interface']);
                        if (in_array($a_ifgroups[$id]['ifname'], $rule_ifs)) {
                            // replace interface
                            $rule_ifs[array_search($a_ifgroups[$id]['ifname'], $rule_ifs)] = $ifgroupentry['ifname'];
                            $rule['interface'] = implode(",", $rule_ifs);
                        }
                    }
              }
              if (!empty($config['nat']) && is_array($config['nat']['rule'])) {
                  foreach ($config['nat']['rule'] as &$rule) {
                      $rule_ifs = explode(",", $rule['interface']);
                      if (in_array($a_ifgroups[$id]['ifname'], $rule_ifs)) {
                          // replace interface
                          $rule_ifs[array_search($a_ifgroups[$id]['ifname'], $rule_ifs)] = $ifgroupentry['ifname'];
                          $rule['interface'] = implode(",", $rule_ifs);
                      }
                  }
              }
          }
          // remove group members
          foreach (explode(" ", $a_ifgroups[$id]['members']) as $old_member) {
              if (!in_array($old_member, $pconfig['members'])) {
                  $realif = get_real_interface($old_member);
                  if (!empty($realif)) {
                      mwexec("/sbin/ifconfig {$realif} -group " . escapeshellarg($a_ifgroups[$id]['ifname']));
                  }
              }
          }
          // update item
          $a_ifgroups[$id] = $ifgroupentry;
      } else {
          // add new item
          $a_ifgroups[] = $ifgroupentry;
      }
      plugins_interfaces(false);
      write_config();
      interface_group_setup($ifgroupentry);
      header("Location: interfaces_groups.php");
      exit;
    }
}


include("head.inc");
legacy_html_escape_form_data($pconfig);
?>

<body>
<?php include("fbegin.inc"); ?>

<section class="page-content-main">
  <div class="container-fluid">
    <div class="row">
      <?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
      <section class="col-xs-12">
        <div class="content-box">
          <div class="table-responsive">
            <form method="post" name="iform" id="iform">
              <table class="table table-striped opnsense_standard_table_form">
                <thead>
                  <tr>
                    <td width="22%"><strong><?=gettext("Interface Groups Edit");?></strong></td>
                    <td width="78%" align="right">
                      <small><?=gettext("full help"); ?> </small>
                      <i class="fa fa-toggle-off text-danger"  style="cursor: pointer;" id="show_all_help_page" type="button"></i>
                      &nbsp;
                    </td>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><a id="help_for_ifname" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Group Name");?></td>
                    <td>
                      <input type="text" name="ifname" value="<?=$pconfig['ifname'];?>" />
                      <div class="hidden" for="help_for_ifname">
                        <?=gettext("No numbers or spaces are allowed. Only characters in a-zA-Z");?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_descr" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Description"); ?></td>
                    <td>
                      <input name="descr" type="text" value="<?=$pconfig['descr'];?>" />
                      <div class="hidden" for="help_for_descr">
                        <?=gettext("You may enter a description here " .
                        "for your reference (not parsed)."); ?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_members" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Member (s)");?></td>
                    <td>
                        <select name="members[]" multiple="multiple" class="selectpicker" data-size="5" data-live-search="true">
<?php
                        foreach (get_configured_interface_with_descr() as $ifn => $ifinfo):?>
                            <option value="<?=$ifn;?>" <?=in_array($ifn, $pconfig['members']) ? "selected=\"selected\"" : "";?>>
                                <?=$ifinfo;?>
                            </option>
<?php
                        endforeach;?>
                        </select>
                      <div class="hidden" for="help_for_members">
                      <strong><?= gettext('NOTE:') ?></strong>
                      <?= gettext('Rules for WAN type interfaces in groups do not contain the reply-to mechanism upon which Multi-WAN typically relies.') ?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td>
                      <input name="submit" type="submit" class="btn btn-primary" value="<?=gettext("Save");?>" />
                      <input type="button" class="btn btn-default" value="<?=gettext("Cancel");?>" onclick="window.location.href='<?=(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/interfaces_groups.php');?>'" />
                      <?php if (isset($id)): ?>
                      <input name="id" type="hidden" value="<?=$id;?>" />
                      <?php endif; ?>
                    </td>
                  </tr>
                </tbody>
              </table>
            </form>
          </div>
        </div>
      </section>
    </div>
  </div>
</section>
<?php
include("foot.inc");
