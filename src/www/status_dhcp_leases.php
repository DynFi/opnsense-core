<?php

/*
    Copyright (C) 2014-2016 Deciso B.V.
    Copyright (C) 2004-2009 Scott Ullrich <sullrich@gmail.com>
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
require_once("config.inc");
require_once("services.inc");
require_once("pfsense-utils.inc");
require_once("interfaces.inc");

function leasecmp($a, $b)
{
    return strcmp($a[$_GET['order']], $b[$_GET['order']]);
}

function adjust_gmt($dt)
{
    global $config;

    $dhcpd = array();
    if (isset($config['dhcpd'])) {
        $dhcpd = $config['dhcpd'];
    }

    foreach ($dhcpd as $dhcpditem) {
        $dhcpleaseinlocaltime = $dhcpditem['dhcpleaseinlocaltime'];
        if ($dhcpleaseinlocaltime == "yes") {
            break;
        }
    }

    if ($dhcpleaseinlocaltime == "yes") {
        $ts = strtotime($dt . " GMT");
        return strftime("%Y/%m/%d %I:%M:%S%p", $ts);
    }

    return $dt;
}

function remove_duplicate($array, $field)
{
    foreach ($array as $sub) {
        $cmp[] = $sub[$field];
    }
    $unique = array_unique(array_reverse($cmp,true));
    foreach ($unique as $k => $rien) {
        $new[] = $array[$k];
    }
    return $new;
}

$leasesfile = services_dhcpd_leasesfile();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $awk = "/usr/bin/awk";
    /* this pattern sticks comments into a single array item */
    $cleanpattern = "'{ gsub(\"#.*\", \"\");} { gsub(\";\", \"\"); print;}'";
    /* We then split the leases file by } */
    $splitpattern = "'BEGIN { RS=\"}\";} {for (i=1; i<=NF; i++) printf \"%s \", \$i; printf \"}\\n\";}'";

    /* stuff the leases file in a proper format into a array by line */
    exec("/bin/cat {$leasesfile} | {$awk} {$cleanpattern} | {$awk} {$splitpattern}", $leases_content);
    $leases_count = count($leases_content);
    exec("/usr/sbin/arp -an", $rawdata);
    $arpdata_ip = array();
    $arpdata_mac = array();
    foreach ($rawdata as $line) {
        $elements = explode(' ',$line);
        if ($elements[3] != "(incomplete)") {
            $arpent = array();
            $arpdata_ip[] = trim(str_replace(array('(',')'),'',$elements[1]));
            $arpdata_mac[] = strtolower(trim($elements[3]));
        }
    }
    unset($rawdata);
    $pools = array();
    $leases = array();
    $i = 0;
    $l = 0;
    $p = 0;

    // Put everything together again
    foreach($leases_content as $lease) {
        /* split the line by space */
        $data = explode(" ", $lease);
        /* walk the fields */
        $f = 0;
        $fcount = count($data);
        /* with less then 20 fields there is nothing useful */
        if($fcount < 20) {
            $i++;
            continue;
        }
        while($f < $fcount) {
            switch($data[$f]) {
                case "failover":
                    $pools[$p]['name'] = trim($data[$f+2], '"');
                    $pools[$p]['name'] = "{$pools[$p]['name']} (" . convert_friendly_interface_to_friendly_descr(substr($pools[$p]['name'], 5)) . ")";
                    $pools[$p]['mystate'] = $data[$f+7];
                    $pools[$p]['peerstate'] = $data[$f+14];
                    $pools[$p]['mydate'] = $data[$f+10];
                    $pools[$p]['mydate'] .= " " . $data[$f+11];
                    $pools[$p]['peerdate'] = $data[$f+17];
                    $pools[$p]['peerdate'] .= " " . $data[$f+18];
                    $p++;
                    $i++;
                    continue 3;
                case "lease":
                    $leases[$l]['ip'] = $data[$f+1];
                    $leases[$l]['type'] = "dynamic";
                    $f = $f+2;
                    break;
                case "starts":
                    $leases[$l]['start'] = $data[$f+2];
                    $leases[$l]['start'] .= " " . $data[$f+3];
                    $f = $f+3;
                    break;
                case "ends":
                    $leases[$l]['end'] = $data[$f+2];
                    $leases[$l]['end'] .= " " . $data[$f+3];
                    $f = $f+3;
                    break;
                case "tstp":
                    $f = $f+3;
                    break;
                case "tsfp":
                    $f = $f+3;
                    break;
                case "atsfp":
                    $f = $f+3;
                    break;
                case "cltt":
                    $f = $f+3;
                    break;
                case "binding":
                    switch($data[$f+2]) {
                        case "active":
                            $leases[$l]['act'] = "active";
                            break;
                        case "free":
                            $leases[$l]['act'] = "expired";
                            $leases[$l]['online'] = "offline";
                            break;
                        case "backup":
                            $leases[$l]['act'] = "reserved";
                            $leases[$l]['online'] = "offline";
                            break;
                    }
                    $f = $f+1;
                    break;
                case "next":
                    /* skip the next binding statement */
                    $f = $f+3;
                    break;
                case "rewind":
                    /* skip the rewind binding statement */
                    $f = $f+3;
                    break;
                case "hardware":
                    $leases[$l]['mac'] = $data[$f+2];
                    /* check if it's online and the lease is active */
                    if (in_array($leases[$l]['ip'], $arpdata_ip)) {
                        $leases[$l]['online'] = 'online';
                    } else {
                        $leases[$l]['online'] = 'offline';
                    }
                    $f = $f+2;
                    break;
                case "client-hostname":
                    if($data[$f+1] <> "") {
                        $leases[$l]['hostname'] = preg_replace('/"/','',$data[$f+1]);
                    } else {
                        $hostname = gethostbyaddr($leases[$l]['ip']);
                        if ($hostname <> "") {
                            $leases[$l]['hostname'] = $hostname;
                        }
                    }
                    $f = $f+1;
                    break;
                case "uid":
                    $f = $f+1;
                    break;
          }
          $f++;
        }
        $l++;
        $i++;
        /* slowly chisel away at the source array */
        array_shift($leases_content);
    }
    /* remove the old array */
    unset($lease_content);

    /* remove duplicate items by mac address */
    if(count($leases) > 0) {
        $leases = remove_duplicate($leases,"ip");
    }

    if(count($pools) > 0) {
        $pools = remove_duplicate($pools,"name");
        asort($pools);
    }

    foreach($config['interfaces'] as $ifname => $ifarr) {
        if (isset($config['dhcpd'][$ifname]['staticmap'])) {
            foreach($config['dhcpd'][$ifname]['staticmap'] as $static) {
                $slease = array();
                $slease['ip'] = $static['ipaddr'];
                $slease['type'] = "static";
                $slease['mac'] = $static['mac'];
                $slease['start'] = "";
                $slease['end'] = "";
                $slease['hostname'] = htmlentities($static['hostname']);
                $slease['descr'] = htmlentities($static['descr']);
                $slease['act'] = "static";
                $slease['online'] = in_array(strtolower($slease['mac']), $arpdata_mac) ? 'online' : 'offline';
                $leases[] = $slease;
            }
        }
    }

    if ($_GET['order']) {
        usort($leases, "leasecmp");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['deleteip']) && is_ipaddr($_POST['deleteip'])) {
        // delete dhcp lease
        /* Stop DHCPD */
        killbyname("dhcpd");
        $fin = @fopen($leasesfile, "r");
        $fout = @fopen($leasesfile.".new", "w");
        if ($fin) {
            $ip_to_remove = $_POST['deleteip'];
            $lease = "";
            while (($line = fgets($fin, 4096)) !== false) {
                $fields = explode(' ', $line);
                if ($fields[0] == 'lease') {
                    // lease segment, record ip
                    $lease = trim($fields[1]);
                }

                if ($lease != $ip_to_remove) {
                    fputs($fout, $line);
                }

                if ($line == "}\n") {
                    // end of segment
                    $lease = "";
                }
            }
            fclose($fin);
            fclose($fout);
            @unlink($leasesfile);
            @rename($leasesfile.".new", $leasesfile);
            /* Restart DHCP Service */
            services_dhcpd_configure();
        }
    }
    exit;
}



$service_hook = 'dhcpd';

include("head.inc");?>

<body>
  <script>
  $( document ).ready(function() {
      $(".act_delete").click(function(){
          $.post(window.location, {deleteip: $(this).data('deleteip')}, function(data) {
              location.reload();
          });
      });
      // keep sorting in place.
      $(".act_sort").click(function(){
          var all = <?=!empty($_GET['all']) ? 1 : 0;?> ;
          document.location = document.location.origin + window.location.pathname +"?all="+all+"&order="+$(this).data('field');
      });
  });

  </script>
<?php include("fbegin.inc"); ?>

<section class="page-content-main">
  <div class="container-fluid">
    <div class="row">
<?php
      /* only print pool status when we have one */
      legacy_html_escape_form_data($pools);
      if(count($pools) > 0):?>
      <section class="col-xs-12">
        <div class="content-box">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th><?=gettext("Failover Group"); ?></th>
                  <th><?=gettext("My State"); ?></th>
                  <th><?=gettext("Since"); ?></th>
                  <th><?=gettext("Peer State"); ?></th>
                  <th><?=gettext("Since"); ?></th>
                </tr>
              </thead>
              <tbody>
<?php
              foreach ($pools as $data):?>
                <tr>
                    <td><?=$data['name'];?></td>
                    <td><?=$data['mystate'];?></td>
                    <td><?=adjust_gmt($data['mydate']);?></td>
                    <td><?=$data['peerstate'];?></td>
                    <td><?=adjust_gmt($data['peerdate']);?></td>
                </tr>
<?php
              endforeach;?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

<?php
      endif;?>

      <section class="col-xs-12">
        <div class="content-box">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                    <td class="act_sort" data-field="ip"><?=gettext("IP address"); ?></td>
                    <td class="act_sort" data-field="mac"><?=gettext("MAC address"); ?></td>
                    <td class="act_sort" data-field="hostname"><?=gettext("Hostname"); ?></td>
                    <td class="act_sort" data-field="desc"><?=gettext("Description"); ?></td>
                    <td class="act_sort" data-field="start"><?=gettext("Start"); ?></td>
                    <td class="act_sort" data-field="end"><?=gettext("End"); ?></td>
                    <td class="act_sort" data-field="status"><?=gettext("Status"); ?></td>
                    <td class="act_sort" data-field="type"><?=gettext("Lease type"); ?></td>
                    <td>&nbsp;</td>
                </tr>
              </thead>
              <tbody>
<?php
              // Load MAC-Manufacturer table
              $mac_man = load_mac_manufacturer_table();
              legacy_html_escape_form_data($leases);
              foreach ($leases as $data):
                  if (!($data['act'] == "active" || $data['act'] == "static" || $_GET['all'] == 1)) {
                      continue;
                  }
                  $dhcpd = array();
                  if (isset($config['dhcpd'])) {
                      $dhcpd = $config['dhcpd'];
                  }

                  $lip = ip2ulong($data['ip']);
                  if ($data['act'] == "static") {
                      foreach ($dhcpd as $dhcpif => $dhcpifconf) {
                          if(is_array($dhcpifconf['staticmap'])) {
                              foreach ($dhcpifconf['staticmap'] as $staticent) {
                                  if ($data['ip'] == $staticent['ipaddr']) {
                                      $data['if'] = $dhcpif;
                                      break;
                                  }
                              }
                          }
                          /* exit as soon as we have an interface */
                          if ($data['if'] != "") {
                              break;
                          }
                      }
                  } else {
                      foreach ($dhcpd as $dhcpif => $dhcpifconf) {
                          if (empty($dhcpifconf['range'])) {
                              continue;
                          }
                          if (($lip >= ip2ulong($dhcpifconf['range']['from'])) && ($lip <= ip2ulong($dhcpifconf['range']['to']))) {
                              $data['if'] = $dhcpif;
                              break;
                          }
                      }
                  }
                  $mac_hi = strtoupper($data['mac'][0] . $data['mac'][1] . $data['mac'][3] . $data['mac'][4] . $data['mac'][6] . $data['mac'][7]);
              ?>
              <tr>
                  <td><?=$data['ip'];?></td>
                  <td>
                      <a href="services_wol.php?if=<?=$data['if'];?>&amp;mac=<?=$data['mac'];?>" title="<?=gettext("send Wake on LAN packet to this MAC address");?>">
                        <?=$data['mac'];?>
                      </a>
                      <br />
                      <small><i><?=!empty($mac_man[$mac_hi]) ? $mac_man[$mac_hi] : "";?></i></small>
                  </td>
                  <td><?=$data['hostname'];?></td>
                  <td><?=$data['descr'];?></td>
                  <td><?=!empty($data['start']) ? adjust_gmt($data['start']) : "";?></td>
                  <td><?=!empty($data['end']) ? adjust_gmt($data['end']) : "";?></td>
                  <td><?=$data['online'];?></td>
                  <td><?=$data['act'];?></td>
                  <td>
<?php
                    if ($data['type'] == "dynamic"):?>
                      <a class="btn btn-default btn-xs" href="services_dhcp_edit.php?if=<?=$data['if'];?>&amp;mac=<?=$data['mac'];?>&amp;hostname=<?=$data['hostname'];?>">
                        <span class="glyphicon glyphicon-plus" data-toggle="tooltip" title="<?=gettext("add a static mapping for this MAC address");?>" alt="add" ></span>
                      </a>
<?php
                    endif;?>
                    <a class="btn btn-default btn-xs" href="services_wol_edit.php?if=<?=$data['if'];?>&amp;mac=<?=$data['mac'];?>&amp;descr=<?=$data['hostname'];?>">
                      <span class="glyphicon glyphicon-flash" data-toggle="tooltip"  title="<?=gettext("add a Wake on LAN mapping for this MAC address");?>" alt="add"></span>
                    </a>
<?php
                    if (($data['type'] == "dynamic") && ($data['online'] != "online")):?>

                      <a class="act_delete btn btn-default btn-xs" href="#" data-deleteip="<?=$data['ip'];?>">
                        <span class="fa fa-trash text-muted" title="<?=gettext("delete this DHCP lease");?>" data-toggle="tooltip" alt="delete" ></span>
                      </a>
<?php
                    endif;?>

                  </td>
              </tr>
<?php
              endforeach;?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
      <section class="col-xs-12">
        <form method="get">
        <input type="hidden" name="order" value="<?=htmlspecialchars($_GET['order']);?>" />
<?php
        if (!empty($_GET['all'])): ?>
        <input type="hidden" name="all" value="0" />
        <input type="submit" class="btn btn-default" value="<?=gettext("Show active and static leases only"); ?>" />
<?php
        else: ?>
        <input type="hidden" name="all" value="1" />
        <input type="submit" class="btn btn-default" value="<?=gettext("Show all configured leases"); ?>" />
<?php
        endif; ?>
        </form>
<?php
        if($leases == 0): ?>
        <p><strong><?=gettext("No leases file found. Is the DHCP server active"); ?>?</strong></p>
<?php
        endif; ?>
      </section>
    </div>
  </div>
</section>

<?php include("foot.inc"); ?>
