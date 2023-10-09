<?php

/**
 *    Copyright (C) 2023 DynFi
 *
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace OPNsense\Suricata\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\Base\UserException;
use OPNsense\Core\Backend;
use OPNsense\Core\Config;

/**
 * @package OPNsense\Suricata
 */
class AlertsController extends ApiControllerBase
{

    public function searchItemAction($uuid)
    {
        $config = Config::getInstance()->toArray();

        $result = array();

        require_once("plugins.inc.d/suricata.inc");

        $suricatalogdir = SURICATALOGDIR;
        $suricatadir = SURICATADIR;

        $suricatacfg = $this->getSuricataConfig($uuid);

        $ifaces = $this->getInterfaceNames();
        $if_real = $ifaces[strtolower($suricatacfg['iface'])];

        $enablesid = suricata_load_sid_mods($suricatacfg['rulesidon']);
        $disablesid = suricata_load_sid_mods($suricatacfg['rulesidoff']);

        $rejectsid = array();

        if ($suricatacfg['blockoffenders'] == '1' && ($suricatacfg['ipsmode'] == 'inline' || $suricatacfg['blockdropsonly'] == '1')) {
            $alertsid = suricata_load_sid_mods($suricatacfg['rulesidforcealert']);
            $dropsid = suricata_load_sid_mods($suricatacfg['rulesidforcedrop']);
            if ($suricatacfg['ipsmode'] == 'inline' ) {
                $rejectsid = suricata_load_sid_mods($suricatacfg['rulesidforcereject']);
            }
        }

        $pconfig = array();
        if (is_array($config['OPNsense']['Suricata']['global']['alertsblocks'])) {
            $pconfig['arefresh'] = $config['OPNsense']['Suricata']['global']['alertsblocks']['arefresh'];
            $pconfig['alertnumber'] = $config['OPNsense']['Suricata']['global']['alertsblocks']['alertnumber'];
        }

        if (empty($pconfig['alertnumber']))
            $pconfig['alertnumber'] = 250;
        if (empty($pconfig['arefresh']))
            $pconfig['arefresh'] = '1';
        $anentries = $pconfig['alertnumber'];
        if (!is_numeric($anentries)) {
            $anentries = 250;
        }

        $supplist = suricata_load_suppress_sigs($suricatacfg, true);
        $tmpfile = "/tmp/alerts_suricata_{$if_real}";

        if (file_exists("$suricatalogdir/suricata_{$if_real}/alerts.log")) {
            exec("tail -{$anentries} -r {$suricatalogdir}/suricata_{$if_real}/alerts.log > $tmpfile");

            if (file_exists($tmpfile)) {
                $tmpblocked = array_flip(suricata_get_blocked_ips());
                $counter = 0;

                $fd = fopen("$tmpfile", "r");
                $buf = "";
                while (($buf = fgets($fd)) !== FALSE) {
                    $fields = array();
                    $tmp = array();
                    $decoder_event = FALSE;

                    $fields['time'] = substr($buf, 0, strpos($buf, '  '));

                    if (($suricatacfg['ipsmode'] == 'inline'  || $suricatacfg['blockdropsonly'] == '1') && preg_match('/\[([A-Z]+)\]\s/i', $buf, $tmp)) {
                        $fields['action'] = trim($tmp[1]);
                    } else {
                        $fields['action'] = null;
                    }

                    preg_match('/\[\*{2}\]\s\[((\d+):(\d+):(\d+))\]\s(.*)\[\*{2}\]\s\[Classification:\s(.*)\]\s\[Priority:\s(\d+)\]\s/', $buf, $tmp);
                    $fields['gid'] = trim($tmp[2]);
                    $fields['sid'] = trim($tmp[3]);
                    $fields['rev'] = trim($tmp[4]);
                    $fields['msg'] = trim($tmp[5]);
                    $fields['class'] = trim($tmp[6]);
                    $fields['priority'] = trim($tmp[7]);

                    if (preg_match('/\{(.*)\}\s(.*)\s->\s(.*)/', $buf, $tmp)) {
                        $fields['proto'] = trim($tmp[1]);
                        $fields['src'] = trim(substr($tmp[2], 0, strrpos($tmp[2], ':')));
                        if (is_ipaddrv6($fields['src']))
                            $fields['src'] = inet_ntop(inet_pton($fields['src']));
                        $fields['sport'] = trim(substr($tmp[2], strrpos($tmp[2], ':') + 1));
                        $fields['dst'] = trim(substr($tmp[3], 0, strrpos($tmp[3], ':')));
                        if (is_ipaddrv6($fields['dst']))
                            $fields['dst'] = inet_ntop(inet_pton($fields['dst']));
                        $fields['dport'] = trim(substr($tmp[3], strrpos($tmp[3], ':') + 1));
                    } else {
                        $decoder_event = TRUE;
                        $fields['proto'] = gettext("n/a");
                        $fields['sport'] = gettext("n/a");
                        $fields['dport'] = gettext("n/a");
                    }

                    $event_tm = date_create_from_format("m/d/Y-H:i:s.u", $fields['time']);

                    if ($fields['class'] == "(null)")
                        $fields['class'] = gettext("Not Assigned");

                    @$fields['time'] = date_format($event_tm, "m/d/Y") . " " . date_format($event_tm, "H:i:s");

                    if ($filterlogentries && !suricata_match_filter_field($fields, $filterfieldsarray, $filterlogentries_exact_match)) {
                        continue;
                    }

                    @$alert_time = date_format($event_tm, "H:i:s");
                    @$alert_date = date_format($event_tm, "m/d/Y");
                    $alert_descr = $fields['msg'];
                    $alert_descr_url = urlencode($fields['msg']);
                    $alert_priority = $fields['priority'];
                    $alert_proto = $fields['proto'];

                    if (isset($fields['action']) && $suricatacfg['blockoffenders'] == '1' && ($suricatacfg['ipsmode'] == 'inline' || $suricatacfg['blockdropsonly'] == '1')) {

                        switch ($fields['action']) {

                            case "Drop":
                            case "wDrop":
                                if (isset($dropsid[$fields['gid']][$fields['sid']])) {
                                    $alert_action = '<i class="fa fa-thumbs-down icon-pointer text-danger text-center" title="';
                                    $alert_action .= gettext("Rule action is User-Forced to DROP. Click to force a different action for this rule.");
                                } elseif ($suricatacfg['ipsmode'] == 'inline' && isset($rejectsid[$fields['gid']][$fields['sid']])) {
                                    $alert_action = '<i class="fa fa-hand-stop-o icon-pointer text-warning text-center" title="';
                                    $alert_action .= gettext("Rule action is User-Forced to REJECT. Click to force a different action for this rule.");
                                } else {
                                    $alert_action = '<i class="fa fa-thumbs-down icon-pointer text-danger text-center" title="';
                                    $alert_action .=  gettext("Rule action is DROP. Click to force a different action for this rule.");
                                }
                                break;

                            default:
                                $alert_action = '<i class="fa fa-question-circle icon-pointer text-danger text-center" title="' . gettext("Rule action is unrecognized!. Click to force a different action for this rule.");
                        }
                        $alert_action .= '" onClick="toggleAction(\'' . $fields['gid'] . '\', \'' . $fields['sid'] . '\');"</i>';
                    } else {
                        if ($suricatacfg['blockoffenders'] == '1' && ($suricatacfg['ipsmode'] == 'inline' || $suricatacfg['blockdropsonly'] == '1')) {
                            $alert_action = '<i class="fa fa-exclamation-triangle icon-pointer text-warning text-center" title="' . gettext("Rule action is ALERT.");
                            $alert_action .= '" onClick="toggleAction(\'' . $fields['gid'] . '\', \'' . $fields['sid'] . '\');"</i>';
                        } else {
                            $alert_action = '<i class="fa fa-exclamation-triangle text-warning text-center" title="' . gettext("Rule action is ALERT.") . '"</i>';
                        }
                    }

                    if ($decoder_event == FALSE) {
                        $alert_ip_src = $fields['src'];
                        $alert_ip_src = str_replace(":", ":&#8203;", $alert_ip_src);
                        $alert_ip_src .= '<br /><i class="fa fa-search" onclick="javascript:resolve_with_ajax(\'' . $fields['src'] . '\');" title="';
                        $alert_ip_src .= gettext("Resolve host via reverse DNS lookup") . "\"  alt=\"Icon Reverse Resolve with DNS\" ";
                        $alert_ip_src .= " style=\"cursor: pointer;\"></i>";

                        if (!is_private_ip($fields['src']) && (substr($fields['src'], 0, 2) != 'fc') &&
                            (substr($fields['src'], 0, 2) != 'fd')) {
                            $alert_ip_src .= '&nbsp;&nbsp;<i class="fa fa-globe" onclick="javascript:geoip_with_ajax(\'' . $fields['src'] . '\');" title="';
                            $alert_ip_src .= gettext("Check host GeoIP data") . "\"  alt=\"Icon Check host GeoIP\" ";
                            $alert_ip_src .= " style=\"cursor: pointer;\"></i>";
                        }

                        if (!suricata_is_alert_globally_suppressed($supplist, $fields['gid'], $fields['sid']) &&
                            !isset($supplist[$fields['gid']][$fields['sid']]['by_src'][$fields['src']])) {
                            $alert_ip_src .= "&nbsp;&nbsp;<i class=\"fa fa-plus-square-o icon-pointer\" title=\"" . gettext('Add this alert to the Suppress List and track by_src IP') . '"';
                            $alert_ip_src .= " onClick=\"encRuleSig('{$fields['gid']}','{$fields['sid']}','{$fields['src']}','{$alert_descr}');$('#mode').val('addsuppress_srcip');$('#formalert').submit();\"></i>";
                        } elseif (isset($supplist[$fields['gid']][$fields['sid']]['by_src'][$fields['src']])) {
                            $alert_ip_src .= '&nbsp;&nbsp;<i class="fa fa-info-circle" ';
                            $alert_ip_src .= 'title="' . gettext("This alert track by_src IP is already in the Suppress List") . '"></i>';
                        }

                        if (isset($tmpblocked[$fields['src']])) {
                            $alert_ip_src .= "&nbsp;&nbsp;<i class=\"fa fa-times icon-pointer text-danger\" onClick=\"$('#ip').val('{$fields['src']}');$('#mode').val('unblock');$('#formalert').submit();\"";
                            $alert_ip_src .= ' title="' . gettext("Remove host from Blocked Table") . '"></i>';
                        }
                    } else {
                        if (preg_match('/\s\[Raw pkt:(.*)\]/', $buf, $tmp))
                            $alert_ip_src = "<div title='[Raw pkt: {$tmp[1]}]'>" . gettext("Decoder Event") . "</div>";
                        else
                            $alert_ip_src = gettext("Decoder Event");
                    }

                    $alert_src_p = $fields['sport'];

                    if ($decoder_event == FALSE) {
                        $alert_ip_dst = $fields['dst'];
                        $alert_ip_dst = str_replace(":", ":&#8203;", $alert_ip_dst);
                        $alert_ip_dst .= "<br /><i class=\"fa fa-search\" onclick=\"javascript:resolve_with_ajax('{$fields['dst']}');\" title=\"";
                        $alert_ip_dst .= gettext("Resolve host via reverse DNS lookup") . "\" alt=\"Icon Reverse Resolve with DNS\" ";
                        $alert_ip_dst .= " style=\"cursor: pointer;\"></i>";

                        if (!is_private_ip($fields['dst']) && (substr($fields['dst'], 0, 2) != 'fc') &&
                            (substr($fields['dst'], 0, 2) != 'fd')) {
                            $alert_ip_dst .= '&nbsp;&nbsp;<i class="fa fa-globe" onclick="javascript:geoip_with_ajax(\'' . $fields['dst'] . '\');" title="';
                            $alert_ip_dst .= gettext("Check host GeoIP data") . "\"  alt=\"Icon Check host GeoIP\" ";
                            $alert_ip_dst .= " style=\"cursor: pointer;\"></i>";
                        }

                        if (!suricata_is_alert_globally_suppressed($supplist, $fields['gid'], $fields['sid']) &&
                            !isset($supplist[$fields['gid']][$fields['sid']]['by_dst'][$fields['dst']])) {
                            $alert_ip_dst .= "&nbsp;&nbsp;<i class=\"fa fa-plus-square-o icon-pointer\" onClick=\"encRuleSig('{$fields['gid']}','{$fields['sid']}','{$fields['dst']}','{$alert_descr}');$('#mode').val('addsuppress_dstip');$('#formalert').submit();\"";
                            $alert_ip_dst .= ' title="' . gettext("Add this alert to the Suppress List and track by_dst IP") . '"></i>';
                        } elseif (isset($supplist[$fields['gid']][$fields['sid']]['by_dst'][$fields['dst']])) {
                            $alert_ip_dst .= '&nbsp;<i class="fa fa-info-circle" ';
                            $alert_ip_dst .= 'title="' . gettext("This alert track by_dst IP is already in the Suppress List") . '"></i>';
                        }


                        if (isset($tmpblocked[$fields['dst']])) {
                            $alert_ip_dst .= '&nbsp;&nbsp;<i name="todelete[]" class="fa fa-times icon-pointer text-danger" onClick="$(\'#ip\').val(\'' . $fields['dst'] . '\');$(\'#mode\').val(\'unblock\');$(\'#formalert\').submit();" ';
                            $alert_ip_dst .= ' title="' . gettext("Remove host from Blocked Table") . '"></i>';
                        }
                    } else {
                        $alert_ip_dst = gettext("n/a");
                    }

                    $alert_dst_p = $fields['dport'];

                    $alert_sid_str = '<a onclick="javascript:showRule(\''.$fields['sid'].'\',\''.$fields['gid'].'\');" title="'.gettext("Show the rule").'" style="cursor: pointer;" >'.$fields['gid'].':'.$fields['sid'].'</a>';

                    if (!suricata_is_alert_globally_suppressed($supplist, $fields['gid'], $fields['sid'])) {
                        $sidsupplink = "<i class=\"fa fa-plus-square-o icon-pointer\" onClick=\"encRuleSig('{$fields['gid']}','{$fields['sid']}','','{$alert_descr}');$('#mode').val('addsuppress');$('#formalert').submit();\"";
                        $sidsupplink .= ' title="' . gettext("Add this alert to the Suppress List") . '"></i>';
                    } else {
                        $sidsupplink = '&nbsp;<i class="fa fa-info-circle" ';
                        $sidsupplink .= "title='" . gettext("This alert is already in the Suppress List") . "'></i>";
                    }

                    if (isset($disablesid[$fields['gid']][$fields['sid']])) {
                        $sid_dsbl_link = "<i class=\"fa fa-times-circle icon-pointer text-warning\" onClick=\"encRuleSig('{$fields['gid']}','{$fields['sid']}','','');$('#mode').val('togglesid');$('#formalert').submit();\"";
                        $sid_dsbl_link .= ' title="' . gettext("Rule is forced to a disabled state. Click to remove the force-disable action from this rule.") . '"></i>';
                    } else {
                        $sid_dsbl_link = "<i class=\"fa fa-times icon-pointer text-danger\" onClick=\"encRuleSig('{$fields['gid']}','{$fields['sid']}','','');$('#mode').val('togglesid');$('#formalert').submit();\"";
                        $sid_dsbl_link .= ' title="' . gettext("Force-disable this rule and remove it from current rules set.") . '"></i>';
                    }

                    if ($suricatacfg['blockoffenders'] == '1') {
                        if ($suricatacfg['block_drops_only'] == '1' || $suricatacfg['ipsmode'] == 'inline') {
                            $sid_action_link = "<i class=\"fa fa-pencil-square-o icon-pointer text-info\" onClick=\"toggleAction('{$fields['gid']}', '{$fields['sid']}');\"";
                            $sid_action_link .= ' title="' . gettext("Click to force a different action for this rule.") . '"></i>';
                            if (isset($alertsid[$fields['gid']][$fields['sid']])) {
                                $sid_action_link = "<i class=\"fa fa-exclamation-triangle icon-pointer text-warning\" onClick=\"toggleAction('{$fields['gid']}', '{$fields['sid']}');\"";
                                $sid_action_link .= ' title="' . gettext("Rule is forced to ALERT. Click to change the action for this rule.") . '"></i>';
                            }
                            if (isset($rejectsid[$fields['gid']][$fields['sid']])) {
                                $sid_action_link = "<i class=\"fa fa-hand-paper-o icon-pointer text-warning\" onClick=\"toggleAction('{$fields['gid']}', '{$fields['sid']}');\"";
                                $sid_action_link .= ' title="' . gettext("Rule is forced to REJECT. Click to change the action for this rule.") . '"></i>';
                            }
                            if (isset($dropsid[$fields['gid']][$fields['sid']])) {
                                $sid_action_link = "<i class=\"fa fa-thumbs-down icon-pointer text-danger\" onClick=\"toggleAction('{$fields['gid']}', '{$fields['sid']}');\"";
                                $sid_action_link .= ' title="' . gettext("Rule is forced to DROP. Click to change the action for this rule.") . '"></i>';
                            }
                        }
                    } else {
                        $sid_action_link = '';
                    }

                    $alert_class = $fields['class'];

                    $result[] = array(
                        'id' => "rule_".$gid."_".$sid,
                        'date' => $alert_date." ".$alert_time,
                        'action' => $alert_action,
                        'pri' => $alert_priority,
                        'proto' => $alert_proto,
                        'class' => $alert_class,
                        'src' => $alert_ip_src,
                        'sport' => $alert_src_p,
                        'dst' => $alert_ip_dst,
                        'dport' => $alert_dst_p,
                        'gidsid' => $alert_sid_str.'<br />'.$sidsupplink."&nbsp;&nbsp".$sid_dsbl_link."&nbsp;&nbsp;".$sid_action_link,
                        'description' => $alert_descr
                    );

                    $counter++;
                }

                unset($fields, $buf, $tmp);
                fclose($fd);
                if (file_exists($tmpfile))
                    unlink($tmpfile);
            }
        }

        return array(
            'current' => 1,
            'rowCount' => count($result),
            'total' => count($result),
            'rows' => $result
        );
    }


    private function getInterfaceNames()
    {
        $intfmap = array();
        $config = Config::getInstance()->object();
        if ($config->interfaces->count() > 0) {
            foreach ($config->interfaces->children() as $key => $node) {
                $intfmap[strtolower($key)] = (string)$node->if;
            }
        }
        return $intfmap;
    }


    private function getSuricataConfig($uuid) {

        $config = Config::getInstance()->toArray();

        $suricataConfigs = (isset($config['OPNsense']['Suricata']['interfaces']['interface'][1])) ? $config['OPNsense']['Suricata']['interfaces']['interface'] : [ $config['OPNsense']['Suricata']['interfaces']['interface'] ];

        foreach ($suricataConfigs as $suricatacfg) {

            if ($suricatacfg["@attributes"]['uuid'] == $uuid)
                return $suricatacfg;
        }

        return null;
    }

}
