<?php

/*
    Copyright (C) 2014-2016 Deciso B.V.
    Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
    Copyright (C) 2010 Seth Mos <seth.mos@dds.nl>.
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
require_once("services.inc");
require_once("interfaces.inc");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_GET['if']) && !empty($config['interfaces'][$_GET['if']])) {
        $if = $_GET['if'];
    } else {
        $savemsg = gettext(
            'Router Advertisements can only be enabled on interfaces configured with static ' .
            'IP addresses. Only interfaces configured with a static IP will be shown.'
         );
         foreach (legacy_config_get_interfaces(array("virtual" => false)) as $if_id => $intf) {
             if (!empty($intf['enable']) && is_ipaddrv6($intf['ipaddrv6']) && !is_linklocal($oc['ipaddrv6'])) {
                 $if = $if_id;
                 break;
             }
         }
    }
    $pconfig = array();
    $config_copy_fieldsnames = array('ramode', 'rapriority', 'rainterface', 'ramininterval', 'ramaxinterval', 'radomainsearchlist', 'subnets');
    foreach ($config_copy_fieldsnames as $fieldname) {
        if (isset($config['dhcpdv6'][$if][$fieldname])) {
            $pconfig[$fieldname] = $config['dhcpdv6'][$if][$fieldname];
        } else {
            $pconfig[$fieldname] = null;
        }
    }
    // boolean
    $pconfig['rasamednsasdhcp6'] = isset($config['dhcpdv6'][$if]['rasamednsasdhcp6']);
    if (empty($config['dhcpdv6'][$if]['ranosend'])) {
        $pconfig['rasend'] = true;
    }

    // defaults
    if (empty($pconfig['ramininterval'])) {
        $pconfig['ramininterval'] = 200;
    }
    if (empty($pconfig['ramaxinterval'])) {
        $pconfig['ramaxinterval'] = 600;
    }

    // arrays
    $pconfig['radns1'] = !empty($config['dhcpdv6'][$if]['radnsserver'][0]) ? $config['dhcpdv6'][$if]['radnsserver'][0] : null;
    $pconfig['radns2'] = !empty($config['dhcpdv6'][$if]['radnsserver'][1]) ? $config['dhcpdv6'][$if]['radnsserver'][1] : null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['if']) && !empty($config['interfaces'][$_POST['if']])) {
        $if = $_POST['if'];
    }
    $input_errors = array();
    $pconfig = $_POST;
    /* input validation */

    // validate and copy subnets
    $pconfig['subnets'] = array("item" => array());
    foreach ($pconfig['subnet_address'] as $ids => $address) {
        if (!empty($address)) {
            if (is_alias($address)) {
                $pconfig['subnets']['item'][] = $address;
            } else {
                $pconfig['subnets']['item'][] = $address . "/" . $pconfig['subnet_bits'][$idx];
                if (!is_ipaddrv6($address)) {
                    $input_errors[] = sprintf(gettext("An invalid subnet or alias was specified. [%s/%s]"), $address, $bits);
                }
            }
        }
    }

    if ((!empty($pconfig['radns1']) && !is_ipaddrv6($pconfig['radns1'])) || ($pconfig['radns2'] && !is_ipaddrv6($pconfig['radns2']))) {
        $input_errors[] = gettext("A valid IPv6 address must be specified for the primary/secondary DNS servers.");
    }
    if (!empty($pconfig['radomainsearchlist'])) {
        $domain_array=preg_split("/[ ;]+/",$pconfig['radomainsearchlist']);
        foreach ($domain_array as $curdomain) {
            if (!is_domain($curdomain)) {
                $input_errors[] = gettext("A valid domain search list must be specified.");
                break;
            }
        }
    }

    if (!is_numericint($pconfig['ramaxinterval']) || $pconfig['ramaxinterval'] < 4 || $pconfig['ramaxinterval'] > 1800) {
        $input_errors[] = sprintf(gettext('Maximum interval must be between %s and %s seconds.'), 4, 1800);
    // chain this validation, we use the former value for calculation */
    } elseif (!is_numericint($pconfig['ramininterval']) || $pconfig['ramininterval'] < 3 || $pconfig['ramininterval'] > intval($pconfig['ramaxinterval'] * 0.75)) {
        $input_errors[] = sprintf(gettext('Minimum interval must be between %s and %s seconds.'), 3, intval($pconfig['ramaxinterval'] * 0.75));
    }

    if (count($input_errors) == 0) {
        if (!is_array($config['dhcpdv6'][$if])) {
            $config['dhcpdv6'][$if] = array();
        }

        $config['dhcpdv6'][$if]['ramode'] = $pconfig['ramode'];
        $config['dhcpdv6'][$if]['rapriority'] = $pconfig['rapriority'];
        $config['dhcpdv6'][$if]['rainterface'] = $pconfig['rainterface'];
        $config['dhcpdv6'][$if]['ramininterval'] = $pconfig['ramininterval'];
        $config['dhcpdv6'][$if]['ramaxinterval'] = $pconfig['ramaxinterval'];

        # flipped in GUI on purpose
        if (empty($pconfig['rasend'])) {
            $config['dhcpdv6'][$if]['ranosend'] = true;
        } elseif (isset($config['dhcpdv6'][$if]['ranosend'])) {
            unset($config['dhcpdv6'][$if]['ranosend']);
        }

        $config['dhcpdv6'][$if]['radomainsearchlist'] = $pconfig['radomainsearchlist'];
        $config['dhcpdv6'][$if]['radnsserver'] = array();
        if (!empty($pconfig['radns1'])) {
            $config['dhcpdv6'][$if]['radnsserver'][] = $pconfig['radns1'];
        }
        if ($pconfig['radns2']) {
            $config['dhcpdv6'][$if]['radnsserver'][] = $pconfig['radns2'];
        }
        $config['dhcpdv6'][$if]['rasamednsasdhcp6'] = !empty($pconfig['rasamednsasdhcp6']);

        if (count($pconfig['subnets']['item'])) {
            $config['dhcpdv6'][$if]['subnets'] = $pconfig['subnets'];
        } else {
            $config['dhcpdv6'][$if]['subnets'] = array();
        }

        write_config();
        services_radvd_configure();
        $savemsg = get_std_save_message();
    }
}

$service_hook = 'radvd';

legacy_html_escape_form_data($pconfig);
include("head.inc");
?>

<body>
<?php include("fbegin.inc"); ?>

<script type="text/javascript">
  $( document ).ready(function() {
    /**
     * Additional BOOTP/DHCP Options extenable table
     */
    function removeRow() {
        if ( $('#maintable > tbody > tr').length == 1 ) {
            $('#maintable > tbody > tr:last > td > input').each(function(){
              $(this).val("");
            });
        } else {
            $(this).parent().parent().remove();
        }
    }
    // add new detail record
    $("#addNew").click(function(){
        // copy last row and reset values
        $('#maintable > tbody').append('<tr>'+$('#maintable > tbody > tr:last').html()+'</tr>');
        $('#maintable > tbody > tr:last > td > input').each(function(){
          $(this).val("");
        });
        $(".act-removerow").click(removeRow);
    });
    $(".act-removerow").click(removeRow);
});
</script>

  <section class="page-content-main">
    <div class="container-fluid">
      <div class="row">
        <?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
        <?php if (isset($savemsg)) print_info_box($savemsg); ?>
        <section class="col-xs-12">
<?php
          /* active tabs */
          $tab_array = array();
          foreach (legacy_config_get_interfaces(array("virtual" => false)) as $if_id => $intf) {
              if (!empty($intf['enable']) && is_ipaddrv6($intf['ipaddrv6'])) {
                  $ifname = !empty($intf['descr']) ? htmlspecialchars($intf['descr']) : strtoupper($if_id);
                  $tab_array[] = array($ifname, $if_id == $if, "services_router_advertisements.php?if={$if_id}");
              }
          }

          display_top_tabs($tab_array);
          ?>
          <div class="tab-content content-box col-xs-12">
            <form method="post" name="iform" id="iform">
            <?php if (count($tab_array) == 0):?>
            <?php print_content_box(gettext('No interfaces found with a static IPv6 address.')); ?>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <tr>
                    <td width="22%"><a id="help_for_ramode" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Router Advertisements");?></td>
                    <td width="78%">
                      <select name="ramode">
                        <option value="disabled" <?=$pconfig['ramode'] == "disabled" ? "selected=\"selected\"" : ""; ?> >
                          <?=gettext("Disabled");?>
                        </option>
                        <option value="router" <?=$pconfig['ramode'] == "router" ? "selected=\"selected\"" : ""; ?> >
                          <?=gettext("Router Only");?>
                        </option>
                        <option value="unmanaged" <?=$pconfig['ramode'] == "unmanaged" ? "selected=\"selected\"" : ""; ?> >
                          <?=gettext("Unmanaged");?>
                        </option>
                        <option value="managed" <?=$pconfig['ramode'] == "managed" ? "selected=\"selected\"" : ""; ?> >
                          <?=gettext("Managed");?>
                        </option>
                        <option value="assist" <?=$pconfig['ramode'] == "assist" ? "selected=\"selected\"" : ""; ?> >
                          <?=gettext("Assisted");?>
                        </option>
                      </select>
                      <div class="hidden" for="help_for_ramode">
                        <strong><?= sprintf(gettext("Select the Operating Mode for the Router Advertisement (RA) Daemon."))?></strong>
                        <?= sprintf(gettext("Use \"Router Only\" to only advertise this router, \"Unmanaged\" for Router Advertising with Stateless Autoconfig, \"Managed\" for assignment through (a) DHCPv6 Server, \"Assisted\" for DHCPv6 Server assignment combined with Stateless Autoconfig"));?>
                        <?= sprintf(gettext("It is not required to activate this DHCPv6 server when set to \"Managed\", this can be another host on the network")); ?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_rapriority" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Router Priority");?></td>
                    <td>
                      <select name="rapriority" id="rapriority">
                        <option value="low" <?= $pconfig['rapriority'] == "low" ? "selected=\"selected\"" : ""; ?> >
                          <?=gettext("Low");?>
                        </option>
                        <option value="medium" <?= empty($pconfig['rapriority']) || $pconfig['rapriority'] == "medium" ? "selected=\"selected\"" : ""; ?>>
                          <?=gettext("Normal");?>
                        </option>
                        <option value="high" <?= $pconfig['rapriority'] == "high" ? "selected=\"selected\"" : ""; ?>>
                          <?=gettext("High");?>
                        </option>
                      </select>
                      <div class="hidden" for="help_for_rapriority">
                        <?= sprintf(gettext("Select the Priority for the Router Advertisement (RA) Daemon."))?>
                      </div>
                    </td>
                  </tr>
<?php
                    $carplist = get_configured_carp_interface_list();
                    $carplistif = array();
                    if(count($carplist) > 0) {
                      foreach($carplist as $ifname => $vip) {
                        if((preg_match("/^{$if}_/", $ifname)) && (is_ipaddrv6($vip)))
                          $carplistif[$ifname] = $vip;
                      }
                    }
                    if (count($carplistif) > 0):?>
                  <tr>
                    <td><a id="help_for_rainterface" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("RA Interface");?></td>
                    <td>
                      <select name="rainterface" id="rainterface">
<?php
                      foreach($carplistif as $ifname => $vip): ?>
                        <option value="interface" <?php if ($pconfig['rainterface'] == "interface") echo "selected=\"selected\""; ?> > <?=strtoupper($if); ?></option>
                        <option value="<?=$ifname ?>" <?php if ($pconfig['rainterface'] == $ifname) echo "selected=\"selected\""; ?> > <?="$ifname - $vip"; ?></option>
<?php
                      endforeach;?>
                      </select>
                      <div class="hidden" for="help_for_rainterface">
                        <?= sprintf(gettext("Select the Interface for the Router Advertisement (RA) Daemon."))?>
                      </div>
                    </td>
                  </tr>
<?php
                  endif;?>

                  <tr>
                    <td><a id="help_for_subnets" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("RA Subnet(s)");?></td>
                    <td>
                      <table class="table table-striped table-condensed" id="maintable">
                        <thead>
                          <tr>
                            <th></th>
                            <th id="detailsHeading1"><?=gettext("Address"); ?></th>
                            <th id="detailsHeading3"><?=gettext("Bits"); ?></th>
                          </tr>
                        </thead>
                        <tbody>
<?php
                        if (empty($pconfig['subnets']['item'][0])) {
                            // add initial row as reference
                            $pconfig['subnets'] = array("item" => array(""));
                        }
                        foreach($pconfig['subnets']['item'] as $item):
                          $parts = explode('/', $item);
                          if (count($parts) > 1) {
                              $sn_bits = $parts[1];
                          } else {
                              $sn_bits = null;
                          }
                          $sn_address = $parts[0];
                          ?>
                          <tr>
                            <td>
                              <div style="cursor:pointer;" class="act-removerow btn btn-default btn-xs" alt="remove"><span class="glyphicon glyphicon-minus"></span></div>
                            </td>
                            <td>
                              <input name="subnet_address[]" type="text" value="<?=$sn_address;?>" />
                            </td>
                            <td>
                              <select name="subnet_bits[]">
<?php
                              for ($i = 128; $i >= 0; $i -= 1): ?>
                                <option value="<?= $i ?>" <?= $sn_bits === $i ? "selected='selected'" : "" ?>><?= $i ?></option>
<?php
                              endfor;?>
                              </select>
                            </td>
                          </tr>
<?php
                        endforeach;?>
                        </tbody>
                        <tfoot>
                          <tr>
                            <td colspan="4">
                              <div id="addNew" style="cursor:pointer;" class="btn btn-default btn-xs" alt="add"><span class="glyphicon glyphicon-plus"></span></div>
                            </td>
                          </tr>
                        </tfoot>
                      </table>
                      <div class="hidden" for="help_for_subnets">
                        <?=gettext("Subnets are specified in CIDR format.  " .
                              "Select the CIDR mask that pertains to each entry. " .
                              "/128 specifies a single IPv6 host; /64 specifies a normal IPv6 network; etc. " .
                              "If no subnets are specified here, the Router Advertisement (RA) Daemon will advertise to the subnet to which the router's interface is assigned.");?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_radns" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("DNS servers");?></td>
                    <td>
                      <input name="radns1" type="text" value="<?=$pconfig['radns1'];?>" /><br />
                      <input name="radns2" type="text" value="<?=$pconfig['radns2'];?>" />
                      <div class="hidden" for="help_for_radns">
                        <?=gettext("NOTE: leave blank to use the system default DNS servers - this interface's IP if DNS forwarder is enabled, otherwise the servers configured on the General page.");?>
                      </div>
                      <br />
                      <input id="rasamednsasdhcp6" name="rasamednsasdhcp6" type="checkbox" value="yes" <?=!empty($pconfig['rasamednsasdhcp6']) ? "checked='checked'" : "";?> />
                      <strong><?= gettext("Use the DNS settings of the DHCPv6 server"); ?></strong>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_radomainsearchlist" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext("Domain search list");?></td>
                    <td>
                      <input name="radomainsearchlist" type="text" id="radomainsearchlist" size="28" value="<?=$pconfig['radomainsearchlist'];?>" />
                      <div class="hidden" for="help_for_radomainsearchlist">
                        <?=gettext("The RA server can optionally provide a domain search list. Use the semicolon character as separator");?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_rasend" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?=gettext('RA Sending') ?></td>
                    <td>
                      <input id="rasend" name="rasend" type="checkbox" value="yes" <?= !empty($pconfig['rasend']) ? 'checked="checked"' : '' ?>/>
                      <div class="hidden" for="help_for_rasend">
                        <?= gettext('Enable the periodic sending of router advertisements and responding to router solicitations.') ?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_ramininterval" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?= gettext('Minimum Interval') ?></td>
                    <td>
                      <input name="ramininterval" type="text" id="ramininterval" size="28" value="<?=$pconfig['ramininterval'];?>" />
                      <div class="hidden" for="help_for_ramininterval">
                        <?= gettext('The minimum time allowed between sending unsolicited multicast router advertisements from the interface, in seconds.') ?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><a id="help_for_ramaxinterval" href="#" class="showhelp"><i class="fa fa-info-circle"></i></a> <?= gettext('Maximum Interval') ?></td>
                    <td>
                      <input name="ramaxinterval" type="text" id="ramaxinterval" size="28" value="<?=$pconfig['ramaxinterval'];?>" />
                      <div class="hidden" for="help_for_ramaxinterval">
                        <?= gettext('The maximum time allowed between sending unsolicited multicast router advertisements from the interface, in seconds.') ?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td>
                      <input name="if" type="hidden" value="<?=$if;?>" />
                      <input name="Submit" type="submit" class="formbtn btn btn-primary" value="<?=gettext("Save");?>" />
                    </td>
                  </tr>
                </table>
              </div>
<?php
            endif;?>
            </form>
          </div>
        </section>
      </div>
    </div>
  </section>
<?php include("foot.inc"); ?>
