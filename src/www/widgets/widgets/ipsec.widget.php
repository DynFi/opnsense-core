<?php

/*
    Copyright (C) 2014-2016 Deciso B.V.
    Copyright (C) 2007 Scott Dale
    Copyright (C) 2004-2005 T. Lechat <dev@lechat.org>, Manuel Kasper <mk@neon1.net>
    and Jonathan Watt <jwatt@jwatt.org>.
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

$ipsec_detail_array = array();
$ipsec_tunnels = array();
$ipsec_leases = array();

if (isset($config['ipsec']['phase1'])) {
    echo "<div>&nbsp;</div>\n";

    $tab_array = array();
    $tab_array[] = array(gettext("Overview"), true, "ipsec-Overview");
    $tab_array[] = array(gettext("Tunnels"), false, "ipsec-tunnel");
    $tab_array[] = array(gettext("Mobile"), false, "ipsec-mobile");

    echo "<div id=\"tabs\">";

    $tabscounter = 0;
    foreach ($tab_array as $ta) {
        $dashpos = strpos($ta[2],'-');
        $tabname = $ta[2] . "-tab";
        $tabclass = substr($ta[2],0,$dashpos);
        $tabclass = $tabclass . "-class";
        if ($ta[1] == true) {
            $tabActive = "table-cell";
            $tabNonActive = "none";
        } else {
            $tabActive = "none";
            $tabNonActive = "table-cell";
        }
        echo "<div id=\"{$ta[2]}-active\" class=\"{$tabclass}-tabactive\" style=\"display:{$tabActive}; background-color:#EEEEEE; color:black;\">";
        echo "<b>&nbsp;&nbsp;&nbsp;{$ta[0]}";
        echo "&nbsp;&nbsp;&nbsp;</b>";
        echo "</div>";

        echo "<div id=\"{$ta[2]}-deactive\" class=\"{$tabclass}-tabdeactive\" style=\"display:{$tabNonActive}; background-color:#777777; color:white; cursor: pointer;\" onclick=\"return changeTabDIV('{$ta[2]}')\">";
        echo "<b>&nbsp;&nbsp;&nbsp;{$ta[0]}";
        echo "&nbsp;&nbsp;&nbsp;</b>";
        echo "</div>";
    }
    echo "</div>";

    $ipsec_leases = json_decode(configd_run("ipsec list leases"), true);
    if ($ipsec_leases == null) {
        $ipsec_leases = array();
    }

    $ipsec_status = json_decode(configd_run("ipsec list status"), true);
    if ($ipsec_status == null) {
        $ipsec_status = array();
    }

    // parse configured tunnels
    $activetunnels = 0;

    foreach ($ipsec_status as $status_key => $status_value) {
        if (isset($status_value['children'])) {
          foreach($status_value['children'] as $child_status_key => $child_status_value) {
              $ipsec_tunnels[$child_status_key] = array('active' => false,
                                                        'local-addrs' => $status_value['local-addrs'],
                                                        'remote-addrs' => $status_value['remote-addrs'],
                                                      );
              $ipsec_tunnels[$child_status_key]['local-ts'] = implode(',', $child_status_value['local-ts']);
              $ipsec_tunnels[$child_status_key]['remote-ts'] = implode(',', $child_status_value['remote-ts']);
          }
        }
        foreach ($status_value['sas'] as $sas_key => $sas_value) {
            foreach ($sas_value['child-sas'] as $child_sa_key => $child_sa_value) {
                $ipsec_tunnels[$child_sa_key]['active'] = true;
                $activetunnels++;
            }
        }
    }
}

if (isset($config['ipsec']['phase2'])) {
?>
<script type="text/javascript">
  function changeTabDIV(selectedDiv){
    var dashpos = selectedDiv.indexOf("-");
    var tabclass = selectedDiv.substring(0,dashpos);
    d = document;
    //get deactive tabs first
    tabclass = tabclass + "-class-tabdeactive";
    var tabs = document.getElementsByClassName(tabclass);
    var incTabSelected = selectedDiv + "-deactive";
    for (i=0; i<tabs.length; i++){
      var tab = tabs[i].id;
      dashpos = tab.lastIndexOf("-");
      var tab2 = tab.substring(0,dashpos) + "-deactive";
      if (tab2 == incTabSelected){
        tablink = d.getElementById(tab2);
        tablink.style.display = "none";
        tab2 = tab.substring(0,dashpos) + "-active";
        tablink = d.getElementById(tab2);
        tablink.style.display = "table-cell";
        //now show main div associated with link clicked
        tabmain = d.getElementById(selectedDiv);
        tabmain.style.display = "block";
      }
      else
      {
        tab2 = tab.substring(0,dashpos) + "-deactive";
        tablink = d.getElementById(tab2);
        tablink.style.display = "table-cell";
        tab2 = tab.substring(0,dashpos) + "-active";
        tablink = d.getElementById(tab2);
        tablink.style.display = "none";
        //hide sections we don't want to see
        tab2 = tab.substring(0,dashpos);
        tabmain = d.getElementById(tab2);
        tabmain.style.display = "none";
      }
    }
  }
</script>
<div id="ipsec-Overview" style="display:block;background-color:#EEEEEE;">
  <table class="table table-striped">
    <thead>
      <tr>
        <th><?= gettext('Active Tunnels');?></th>
        <th><?= gettext('Inactive Tunnels');?></th>
        <th><?= gettext('Mobile Users');?></th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?= $activetunnels; ?></td>
        <td><?= (count($ipsec_tunnels) - $activetunnels); ?></td>
        <td>
<?php
        // count active mobile users
        $mobile_users = 0;
        foreach ($ipsec_leases as $pool => $pool_details) {
            foreach ($pool_details['items'] as $lease) {
                if ($lease['status'] == 'online') {
                    ++$mobile_users;
                }
            }
        }
?>
          <?=$mobile_users;?>
        </td>
      </tr>
    </tbody>
  </table>
</div>

<div id="ipsec-tunnel" style="display:none;background-color:#EEEEEE;">
  <table class="table table-striped">
    <thead>
      <tr>
        <th><?= gettext('Connection');?></th>
        <th><?= gettext('Source');?></th>
        <th><?= gettext('Destination');?></th>
        <th><?= gettext('Status');?></th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($ipsec_tunnels as $ipsec_key => $ipsec) :
?>
      <tr>
          <td>
            <?=$ipsec['local-addrs'];?> <br/>
            (<?=$ipsec['remote-addrs'];?>)
          </td>
          <td><?=$ipsec['local-ts'];?></td>
          <td><?=$ipsec['remote-ts'];?></td>
          <td>
          <?php if($ipsec['active']):
?>
              <span class='glyphicon glyphicon-transfer text-success' alt='Tunnel status'></span>
          <?php else:
?>
            <span class='glyphicon glyphicon-transfer text-danger' alt='Tunnel status'></span>
          <?php endif;
?>
          </td>
      </tr>
<?php endforeach;
?>
    </tbody>
  </table>
</div>
<div id="ipsec-mobile" style="display:none;background-color:#EEEEEE;">
  <table class="table table-striped">
    <thead>
      <tr>
        <th><?= gettext('User');?></th>
        <th><?= gettext('IP');?></th>
        <th><?= gettext('Status');?></th>
      </tr>
    </thead>
    <tbody>
<?php
    foreach ($ipsec_leases as $pool => $pool_details):
      foreach ($pool_details['items'] as $lease): ?>
      <tr>
        <td><?=htmlspecialchars($lease['user']);?></td>
        <td><?=htmlspecialchars($lease['address']);?></td>
        <td>
          <span class='glyphicon glyphicon-transfer text-<?=$lease['status'] == 'online' ?  "success" : "danger";?>'></span>
        </td>
      </tr>

<?php
      endforeach;
    endforeach;?>
    </tbody>
  </table>
</div>
<?php //end ipsec tunnel
} //end if tunnels are configured, else show code below
else {
?>
<div style="display:block">
   <table class="table table-striped" width="100%" border="0" cellpadding="0" cellspacing="0" summary="note">
    <tr>
      <td colspan="4">
          <span class="vexpl">
            <span class="red">
              <strong>
                <?= gettext('Note: There are no configured IPsec Tunnels') ?><br />
              </strong>
            </span>
            <?= sprintf(gettext('You can configure your IPsec %shere%s.'), '<a href="vpn_ipsec.php">', '</a>'); ?>
          </span>
    </td>
    </tr>
  </table>
</div>
<?php
}
