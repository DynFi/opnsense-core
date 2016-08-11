<?php

/*
    Copyright (C) 2014-2015 Deciso B.V.
    Copyright (C) Jim McBeath
    Copyright (C) 2003-2005 Manuel Kasper <mk@neon1.net>
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
require_once("rrd.inc");
require_once("system.inc");
require_once("interfaces.inc");
require_once("ipsec.inc");
require_once("openvpn.inc");
require_once("services.inc");
require_once("unbound.inc");

function list_interfaces() {
    global $config;
    $interfaces  = array();

    // define config sections to fetch interfaces from.
    $config_sections = array();
    $config_sections['wireless.clone'] = array('descr' => 'cloneif,descr', 'key' => 'cloneif', 'format' => '%s (%s)');
    $config_sections['vlans.vlan'] = array('descr' => 'tag,if,descr', 'format' => gettext('vlan %s on %s') . ' (%s)', 'key' => 'vlanif');
    $config_sections['bridges.bridged'] = array('descr' => 'bridgeif, descr', 'key' => 'bridgeif', 'format' => '%s (%s)');
    $config_sections['gifs.gif'] = array('descr' => 'remote-addr,descr', 'key' => 'gifif', 'format' => 'gif %s (%s)');
    $config_sections['gres.gre'] = array('descr' => 'remote-addr,descr', 'key' => 'greif', 'format' => 'gre %s (%s)');
    $config_sections['laggs.lagg'] = array('descr' => 'laggif,descr', 'key' => 'laggif', 'format' => '%s (%s)', 'fields' => 'members');
    $config_sections['ppps.ppp'] = array('descr' => 'if,ports,descr,username', 'key' => 'if','format' => '%s (%s) - %s %s', 'fields' => 'type');

    // add physical network interfaces
    foreach (get_interface_list() as $key => $intf_item) {
        if (match_wireless_interface($key)) {
            continue;
        }
        $interfaces[$key] = array('descr' => $key . ' (' . $intf_item['mac'] . ')', 'section' => 'interfaces');
    }
    // collect interfaces from defined config sections
    foreach ($config_sections as $key => $value) {
        $cnf_location = explode(".", $key);
        if (!empty($config[$cnf_location[0]][$cnf_location[1]])) {
            foreach ($config[$cnf_location[0]][$cnf_location[1]] as $cnf_item) {
                $interface_item = array("section" => $key);
                // construct item description
                $descr = array();
                foreach (explode(',', $value['descr']) as $fieldname) {
                    if (isset($cnf_item[trim($fieldname)])) {
                        $descr[] = $cnf_item[trim($fieldname)];
                    } else {
                        $descr[] = "";
                    }
                }
                if (!empty($value['format'])) {
                    $interface_item['descr'] = vsprintf($value['format'], $descr);
                } else {
                    $interface_item['descr'] = implode(" ", $descr);
                }
                // copy requested additional fields into temp structure
                if (isset($value['fields'])) {
                    foreach (explode(',', $value['fields']) as $fieldname) {
                        if (isset($cnf_item[$fieldname])) {
                            $interface_item[$fieldname] = $cnf_item[$fieldname];
                        }
                    }
                }
                $interfaces[$cnf_item[$value['key']]] = $interface_item;
            }
        }
    }
    /* QinQ interfaces can't be directly extracted from config without additional logic */
    if (isset($config['qinqs']['qinqentry'])) {
        foreach ($config['qinqs']['qinqentry'] as $qinq) {
            $interfaces["vlan{$qinq['tag']}"]= array('descr' => "VLAN {$qinq['tag']}");
            foreach (explode(' ', $qinq['members']) as $qinqif) { // QinQ members
                $interfaces["vlan{$qinq['tag']}_{$qinqif}"] = array( 'descr' => "QinQ {$qinqif}");
            }
        }
    }

    // enforce constraints
    foreach ($interfaces as $intf_id => $intf_details) {
        // LAGG members cannot be assigned
        if (isset($intf_details['members']) && $intf_details['section'] == 'laggs.lagg') {
            foreach (explode(',', ($intf_details['members'])) as $intf) {
                if (isset($interfaces[trim($intf)])) {
                    unset($interfaces[trim($intf)]);
                }
            }
        }
    }

    /* add tun0 interface (required for sixxs-aiccu) */
    //   This is a temporary solution to allow using sixxs-aiccu without manual code change
    //   until the aiccu service is correctly included in the web interface.
    //   (to avoid additional temporary code in the interface_assign_description function).
    $tunfound = "";
    exec("/sbin/ifconfig | /usr/bin/grep -c '^tun0'", $tunfound);
    if (intval($tunfound[0]) > 0) {
        $interfaces['tun0'] = array('descr' => 'sixxs-aiccu');
    }

    return $interfaces;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_errors = array();
    if (isset($_POST['add_x']) && isset($_POST['if_add'])) {
        // ** Add new **
        // if interface is already used, redirect.
        foreach (legacy_config_get_interfaces() as $ifname => $ifdata) {
            if ($ifdata['if'] == $_POST['if_add']) {
                header("Location: interfaces_assign.php");
                exit;
            }
        }

        /* find next free optional interface number */
        if(empty($config['interfaces']['lan'])) {
            $newifname = gettext("lan");
            $descr = gettext("LAN");
        } else {
            for ($i = 1; $i <= count($config['interfaces']); $i++) {
                if (empty($config['interfaces']["opt{$i}"])) {
                    break;
                }
            }
            $newifname = 'opt' . $i;
            $descr = "OPT" . $i;
        }

        $config['interfaces'][$newifname] = array();
        $config['interfaces'][$newifname]['descr'] = $descr;
        $config['interfaces'][$newifname]['if'] = $_POST['if_add'];
        $interfaces = list_interfaces();
        if ($interfaces[$_POST['if_add']]['section'] == 'ppps.ppp') {
            $config['interfaces'][$newifname]['ipaddr'] = $interfaces[$_POST['if_add']]['type'];
        }
        if (match_wireless_interface($_POST['if_add'])) {
            $config['interfaces'][$newifname]['wireless'] = array();
            interface_sync_wireless_clones($config['interfaces'][$newifname], false);
        }

        write_config();
        header("Location: interfaces_assign.php");
        exit;
    } elseif (!empty($_POST['id']) && !empty($_POST['action']) && $_POST['action'] == 'del' & !empty($config['interfaces'][$_POST['id']]) ) {
        // ** Delete interface **
        $id = $_POST['id'];
        if (link_interface_to_group($id)) {
            $input_errors[] = gettext("The interface is part of a group. Please remove it from the group to continue");
        } else if (link_interface_to_bridge($id)) {
            $input_errors[] = gettext("The interface is part of a bridge. Please remove it from the bridge to continue");
        } else if (link_interface_to_gre($id)) {
            $input_errors[] = gettext("The interface is part of a gre tunnel. Please delete the tunnel to continue");
        } else if (link_interface_to_gif($id)) {
            $input_errors[] = gettext("The interface is part of a gif tunnel. Please delete the tunnel to continue");
        } else {
            // no validation errors, delete entry
            unset($config['interfaces'][$id]['enable']);
            $realid = get_real_interface($id);
            interface_bring_down($id, true);   /* down the interface */

            unset($config['interfaces'][$id]);  /* delete the specified OPTn or LAN*/

            if (isset($config['dhcpd'][$id])) {
                unset($config['dhcpd'][$id]);
                services_dhcpd_configure();
            }
            if (isset($config['filter']['rule'])) {
                foreach ($config['filter']['rule'] as $x => $rule) {
                    if ($rule['interface'] == $id) {
                        unset($config['filter']['rule'][$x]);
                    }
                }
            }
            if (isset($config['nat']['rule'])) {
                foreach ($config['nat']['rule'] as $x => $rule) {
                    if ($rule['interface'] == $id) {
                        unset($config['nat']['rule'][$x]['interface']);
                    }
                }
            }

            write_config();

            /* If we are in firewall/routing mode (not single interface)
             * then ensure that we are not running DHCP on the wan which
             * will make a lot of ISP's unhappy.
             */
            if(!empty($config['interfaces']['lan']) && !empty($config['dhcpd']['wan']) && !empty($config['dhcpd']['wan']) ) {
                unset($config['dhcpd']['wan']);
            }
            link_interface_to_vlans($realid, "update");
            // redirect
            header("Location: interfaces_assign.php");
            exit;
        }
    } elseif (isset($_POST['Submit'])) {
        // ** Change interface **
        /* input validation */
        /* Build a list of the port names so we can see how the interfaces map */
        $portifmap = array();
        $interfaces = list_interfaces();
        foreach ($interfaces as $portname => $portinfo) {
            $portifmap[$portname] = array();
        }

        /* Go through the list of ports selected by the user,
        build a list of port-to-interface mappings in portifmap */
        foreach ($_POST as $ifname => $ifport) {
            if ($ifname == 'lan' || $ifname == 'wan' || substr($ifname, 0, 3) == 'opt') {
                $portifmap[$ifport][] = strtoupper($ifname);
            }
        }

        /* Deliver error message for any port with more than one assignment */
        foreach ($portifmap as $portname => $ifnames) {
            if (count($ifnames) > 1) {
              $errstr = sprintf(gettext('Port %s was assigned to %d interfaces:'), $portname, count($ifnames));
              foreach ($portifmap[$portname] as $ifn) {
                  $errstr .= " " . $ifn;
              }
              $input_errors[] = $errstr;
            } elseif (count($ifnames) == 1 && preg_match('/^bridge[0-9]/', $portname) && isset($config['bridges']['bridged'])) {
                foreach ($config['bridges']['bridged'] as $bridge) {
                    if ($bridge['bridgeif'] != $portname) {
                        continue;
                    }

                    $members = explode(",", strtoupper($bridge['members']));
                    foreach ($members as $member) {
                        if ($member == $ifnames[0]) {
                            $input_errors[] = sprintf(gettext("You cannot set port %s to interface %s because this interface is a member of %s."), $portname, $member, $portname);
                            break;
                        }
                    }
                }
            }
        }

        if (isset($config['vlans']['vlan'])) {
            foreach ($config['vlans']['vlan'] as $vlan) {
                if (!does_interface_exist($vlan['if'])) {
                    $input_errors[] = sprintf(gettext("VLAN parent interface %s does not exist."), $vlan['if']);
                }
            }
        }

        if (count($input_errors) == 0) {
          /* No errors detected, so update the config */
          $changes = 0;
          foreach ($_POST as $ifname => $ifport) {
              if (!is_array($ifport) && ($ifname == 'lan' || $ifname == 'wan' || substr($ifname, 0, 3) == 'opt')) {
                  $reloadif = false;
                  if (!empty($config['interfaces'][$ifname]['if']) && $config['interfaces'][$ifname]['if'] <> $ifport) {
                      interface_bring_down($ifname, true);
                      /* Mark this to be reconfigured in any case. */
                      $reloadif = true;
                  }
                  $config['interfaces'][$ifname]['if'] = $ifport;
                  if ($interfaces[$ifport]['section'] == 'ppps.ppp') {
                      $config['interfaces'][$ifname]['ipaddr'] = $interfaces[$ifport]['type'];
                  }

                  if (substr($ifport, 0, 3) == 'gre' || substr($ifport, 0, 3) == 'gif') {
                      unset($config['interfaces'][$ifname]['ipaddr']);
                      unset($config['interfaces'][$ifname]['subnet']);
                      unset($config['interfaces'][$ifname]['ipaddrv6']);
                      unset($config['interfaces'][$ifname]['subnetv6']);
                  }

                  /* check for wireless interfaces, set or clear ['wireless'] */
                  if (match_wireless_interface($ifport)) {
                      if (empty($config['interfaces'][$ifname]['wireless'])) {
                          $config['interfaces'][$ifname]['wireless'] = array();
                      }
                  } elseif (isset($config['interfaces'][$ifname]['wireless'])) {
                      unset($config['interfaces'][$ifname]['wireless']);
                  }

                  /* make sure there is a descr for all interfaces */
                  if (!isset($config['interfaces'][$ifname]['descr'])) {
                      $config['interfaces'][$ifname]['descr'] = strtoupper($ifname);
                  }


                  if ($reloadif) {
                      if (match_wireless_interface($ifport)) {
                          interface_sync_wireless_clones($config['interfaces'][$ifname], false);
                      }
                      /* Reload all for the interface. */
                      interface_configure($ifname, true);
                      // count changes
                      $changes++;
                  }
              }
          }
          write_config();
          if ($changes > 0) {
              // reload filter, rrd when interfaces have changed (original from apply action)
              filter_configure();
              enable_rrd_graphing();
          }
          // redirect
          header("Location: interfaces_assign.php");
          exit;
        }
    }
}

/* collect (unused) interfaces */
$interfaces = list_interfaces();
legacy_html_escape_form_data($interfaces);
$unused_interfaces= array();
foreach ($interfaces as $portname => $portinfo) {
    $portused = false;
    foreach (legacy_config_get_interfaces() as $ifname => $ifdata) {
        if ($ifdata['if'] == $portname) {
            $portused = true;
            break;
        }
    }
    if (!$portused) {
        $unused_interfaces[$portname] = $portinfo;
    }
}

include("head.inc");
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
        title: "<?= gettext("Interfaces");?>",
        message: "<?=gettext("Do you really want to delete this interface?"); ?>",
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
<?php
      if (isset($input_errors) && count($input_errors) > 0) {
          print_input_errors($input_errors);
      }?>
        <section class="col-xs-12">
          <div class="tab-content content-box col-xs-12">
            <form  method="post" name="iform" id="iform">
              <input type="hidden" id="action" name="action" value="">
              <input type="hidden" id="id" name="id" value="">

              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th><?=gettext("Interface"); ?></th>
                      <th><?=gettext("Network port"); ?></th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
<?php
                  foreach (legacy_config_get_interfaces(array("virtual" => false)) as $ifname => $iface):?>
                      <tr>
                        <td>
                          <strong><u><span onclick="location.href='/interfaces.php?if=<?=$ifname;?>'" style="cursor: pointer;"><?=$iface['descr'];?></span></u></strong>
                        </td>
                        <td>
                          <select name="<?=$ifname;?>" id="<?=$ifname;?>">
<?php
                          foreach ($interfaces as $portname => $portinfo):?>
                            <option  value="<?=$portname;?>"  <?= $portname == $iface['if'] ? " selected=\"selected\"" : "";?>>
                              <?=$portinfo['descr'];?>
                            </option>
<?php
                          endforeach;?>
                          </select>
                        </td>
                        <td>
                          <button title="<?=gettext("delete interface");?>" data-toggle="tooltip" data-id="<?=$ifname;?>" class="btn btn-default act_delete" type="submit">
                            <span class="fa fa-trash text-muted"></span>
                          </button>
                        </td>
                      </tr>
<?php
                      endforeach;
                      if (count($unused_interfaces) > 0):?>
                      <tr>
                        <td><?= gettext('New interface:') ?></td>
                        <td>
                          <select name="if_add" id="if_add">
<?php
                          foreach ($unused_interfaces as $portname => $portinfo): ?>
                            <option  value="<?=$portname;?>"> <?=$portinfo['descr'];?></option>
<?php
                          endforeach; ?>
                          </select>
                        </td>
                        <td>
                          <button name="add_x" type="submit" value="<?=$portname;?>" class="btn btn-primary" title="<?=gettext("add selected interface");?>" data-toggle="tooltip">
                            <span class="glyphicon glyphicon-plus"></span>
                          </button>
                        </td>
                      </tr>
                      <tr>
                        <td colspan="2"></td>
                        <td>
                          <input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" />
                        </td>
                      </tr>
<?php
                      endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </form>
          </div>
        </section>
      </div>
    </div>
  </section>
<?php include("foot.inc"); ?>
