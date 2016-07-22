<?php

/*
    Copyright (C) 2015 Manuel Faux <mfaux@conf.at>
    Copyright (C) 2014-2016 Deciso B.V.
    Copyright (C) 2014 Warren Baker <warren@pfsense.org>
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
require_once("unbound.inc");
require_once("services.inc");
require_once("system.inc");
require_once("interfaces.inc");

if (empty($config['unbound']) || !is_array($config['unbound'])) {
    $config['unbound'] = array();
}

if (empty($config['unbound']['hosts']) || !is_array($config['unbound']['hosts'])) {
    $config['unbound']['hosts'] = array();
}
$a_hosts =& $config['unbound']['hosts'];
/* Backwards compatibility for records created before introducing RR types. */
foreach ($a_hosts as $i => $hostent) {
    if (!isset($hostent['rr'])) {
        $a_hosts[$i]['rr'] = is_ipaddrv6($hostent['ip']) ? 'AAAA' : 'A';
    }
}

if (empty($config['unbound']['domainoverrides']) || !is_array($config['unbound']['domainoverrides'])) {
    $config['unbound']['domainoverrides'] = array();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pconfig = $_POST;
    if (!empty($pconfig['apply'])) {
        services_unbound_configure();
        clear_subsystem_dirty('unbound');
        /* Update resolv.conf in case the interface bindings exclude localhost. */
        system_resolvconf_generate();
        header("Location: services_unbound_overrides.php");
        exit;
    } elseif (!empty($pconfig['act']) && $pconfig['act'] == 'del') {
        if (isset($pconfig['id']) && !empty($a_hosts[$pconfig['id']])) {
            unset($a_hosts[$pconfig['id']]);
            write_config();
            mark_subsystem_dirty('unbound');
            exit;
        }
    } elseif (!empty($pconfig['act']) && $pconfig['act'] == 'doverride') {
        $a_domainOverrides = &$config['unbound']['domainoverrides'];
        if (isset($pconfig['id']) && !empty($a_domainOverrides[$pconfig['id']])) {
            unset($a_domainOverrides[$pconfig['id']]);
            write_config();
            mark_subsystem_dirty('unbound');
            exit;
        }
    }
}

$service_hook = 'unbound';
legacy_html_escape_form_data($a_hosts);
include_once("head.inc");
?>

<body>

  <script type="text/javascript">
  $( document ).ready(function() {
    // delete host action
    $(".act_delete_host").click(function(event){
      event.preventDefault();
      var id = $(this).data("id");
      // delete single
      BootstrapDialog.show({
        type:BootstrapDialog.TYPE_DANGER,
        title: "<?= gettext("DNS Resolver");?>",
        message: "<?=gettext("Do you really want to delete this host?");?>",
        buttons: [{
                  label: "<?= gettext("No");?>",
                  action: function(dialogRef) {
                      dialogRef.close();
                  }}, {
                  label: "<?= gettext("Yes");?>",
                  action: function(dialogRef) {
                    $.post(window.location, {act: 'del', id:id}, function(data) {
                        location.reload();
                    });
                }
              }]
      });
    });

    $(".act_delete_override").click(function(event){
      event.preventDefault();
      var id = $(this).data("id");
      // delete single
      BootstrapDialog.show({
        type:BootstrapDialog.TYPE_DANGER,
        title: "<?= gettext("DNS Resolver");?>",
        message: "<?=gettext("Do you really want to delete this domain override?");?>",
        buttons: [{
                  label: "<?= gettext("No");?>",
                  action: function(dialogRef) {
                      dialogRef.close();
                  }}, {
                  label: "<?= gettext("Yes");?>",
                  action: function(dialogRef) {
                    $.post(window.location, {act: 'doverride', id:id}, function(data) {
                        location.reload();
                    });
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
        <?php if (isset($savemsg)) print_info_box($savemsg); ?>
        <?php if (is_subsystem_dirty('unbound')): ?><br/>
        <?php print_info_box_apply(gettext("The configuration for the DNS Resolver, has been changed") . ".<br />" . gettext("You must apply the changes in order for them to take effect."));?><br />
        <?php endif; ?>
        <form method="post" name="iform" id="iform">
          <section class="col-xs-12">
            <div class="content-box">
              <div class="content-box-main col-xs-12">
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th colspan="6"><?=gettext("Host Overrides");?></th>
                      </tr>
                      <tr>
                        <th><?=gettext("Host");?></th>
                        <th><?=gettext("Domain");?></th>
                        <th><?=gettext("Type");?></th>
                        <th><?=gettext("Value");?></th>
                        <th><?=gettext("Description");?></th>
                        <th>
                          <a href="services_unbound_host_edit.php" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-plus"></span></a>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
<?php
                      $i = 0;
                      foreach ($a_hosts as $hostent): ?>
                      <tr>
                        <td><?=strtolower($hostent['host']);?></td>
                        <td><?=strtolower($hostent['domain']);?></td>
                        <td><?=strtoupper($hostent['rr']);?></td>
                        <td>
<?php
                          /* Presentation of DNS value differs between chosen RR type. */
                          switch ($hostent['rr']) {
                              case 'A':
                              case 'AAAA':
                                  print $hostent['ip'];
                                  break;
                              case 'MX':
                                  print $hostent['mxprio'] . " " . $hostent['mx'];
                                  break;
                              default:
                                  print '&nbsp;';
                                  break;
                          }?>
                        </td>
                        <td><?=$hostent['descr'];?></td>
                        <td>
                          <a href="services_unbound_host_edit.php?id=<?=$i;?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                          <a href="#" data-id="<?=$i;?>" class="act_delete_host"><button type="button" class="btn btn-xs btn-default"><span class="fa fa-trash text-muted"></span></button></a>
                        </td>
                      </tr>
<?php
                        $i++;
                      endforeach; ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <td colspan="6">
                          <?=gettext("Entries in this section override individual results from the forwarders.");?>
                          <?=gettext("Use these for changing DNS results or for adding custom DNS records.");?>
                          <?=gettext("Keep in mind that all resource record types (i.e. A, AAAA, MX, etc. records) of a specified host below are being overwritten.");?>
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </section>
         <section class="col-xs-12">
            <div class="content-box">
              <div class="content-box-main col-xs-12">
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th colspan="4">
                          <?=gettext("Domain Overrides");?>
                        </th>
                      </tr>
                      <tr>
                        <th><?=gettext("Domain");?></th>
                        <th><?=gettext("IP");?></th>
                        <th><?=gettext("Description");?></th>
                        <th>
                          <a href="services_unbound_domainoverride_edit.php" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-plus"></span></a>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
<?php
                    $i = 0;
                    foreach ($config['unbound']['domainoverrides'] as $doment): ?>
                      <tr>
                        <td><?=strtolower(htmlspecialchars($doment['domain']));?></td>
                        <td><?=htmlspecialchars($doment['ip']);?></td>
                        <td><?=htmlspecialchars($doment['descr']);?></td>
                        <td>
                          <a href="services_unbound_domainoverride_edit.php?id=<?=$i;?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                          <a href="#" data-id="<?=$i;?>" class="act_delete_override"><button type="button" class="btn btn-xs btn-default"><span class="fa fa-trash text-muted"></span></button></a>
                        </td>
                      </tr>
<?php
                      $i++;
                    endforeach; ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <td colspan="4">
                          <?=gettext("Entries in this area override an entire domain by specifying an"." authoritative DNS server to be queried for that domain.");?>
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </section>
        </form>
      </div>
    </div>
  </section>
<?php include("foot.inc"); ?>
