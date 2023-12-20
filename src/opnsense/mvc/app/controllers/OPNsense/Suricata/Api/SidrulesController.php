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
class SidrulesController extends ApiControllerBase
{

    public function searchItemAction($uuid, $currentruleset)
    {
        require_once("plugins.inc.d/suricata.inc");

        $suricatacfg = $this->getSuricataConfig($uuid);

        $suricatadir = SURICATADIR;
        $suricata_rules_dir = SURICATA_RULES_DIR;
        $flowbit_rules_file = FLOWBITS_FILENAME;

        $filter = $_POST['searchPhrase'];
        $curPage = intval($_POST['current']);
        $rowCount = intval($_POST['rowCount']);
        $pstart = ($rowCount >= 0) ? (($curPage - 1) * $rowCount) : 0;

        $sortField = null;
        $sortDir = null;
        foreach ($_POST as $k => $v) {
            if ($k == 'sort') {
                $_k = array_keys($v);
                $_v = array_values($v);
                $sortField = $_k[0];
                $sortDir = $_v[0];
            }
        }

        $ifaces = $this->getInterfaceNames();
        $if_real = $ifaces[strtolower($suricatacfg['iface'])];

        $suricatacfgdir = "{$suricatadir}suricata_{$if_real}";

        $result = array();

        $currentruleset = urldecode($currentruleset);

        $rules_map = $this->getRulesMap($uuid, $currentruleset);

        /* Process the current category rules through any auto SID MGMT changes if enabled */
        suricata_auto_sid_mgmt($rules_map, $suricatacfg, FALSE);

        /* Load up our enablesid and disablesid arrays with manually enabled or disabled SIDs */
        $enablesid = suricata_load_sid_mods($suricatacfg['rulesidon']);
        $disablesid = suricata_load_sid_mods($suricatacfg['rulesidoff']);
        suricata_modify_sids($rules_map, $suricatacfg);

        /* Load up our rule action arrays with manually changed SID actions */
        $alertsid = suricata_load_sid_mods($suricatacfg['rulesidforcealert']);
        $dropsid = suricata_load_sid_mods($suricatacfg['rulesidforcedrop']);
        $rejectsid = suricata_load_sid_mods($suricatacfg['rulesidforcereject']);
        suricata_modify_sids_action($rules_map, $suricatacfg);

        foreach ($rules_map as $k1 => $rulem) {
            if (!is_array($rulem)) {
                $rulem = array();
            }
            foreach ($rulem as $k2 => $v) {
                $sid = $k2;
                $gid = $k1;
                $ruleset = $currentruleset;

                $textss = '';
                $textse = '';
                $iconb_class = '';
                $title = '';

                if ($v['managed'] == 1) {
                    if ($v['disabled'] == 1 && $v['state_toggled'] == 1) {
                        $textss = '<span class="text-muted">';
                        $textse = '</span>';
                        $iconb_class = 'class="fa fa-adn text-danger text-left"';
                        $title = gettext("Auto-disabled by settings on SID Mgmt tab");
                    }
                    elseif ($v['disabled'] == 0 && $v['state_toggled'] == 1) {
                        $textss = $textse = "";
                        $iconb_class = 'class="fa fa-adn text-success text-left"';
                        $title = gettext("Auto-enabled by settings on SID Mgmt tab");
                    }
                    $managed_count++;
                }
                // See if the rule is in our list of user-disabled overrides
                if (isset($disablesid[$gid][$sid])) {
                    $textss = "<span class=\"text-muted\">";
                    $textse = "</span>";
                    $disable_cnt++;
                    $user_disable_cnt++;
                    $iconb_class = 'class="fa fa-times-circle text-danger text-left"';
                    $title = gettext("Disabled by user. Click to change rule state");
                }
                // See if the rule is in our list of user-enabled overrides
                elseif (isset($enablesid[$gid][$sid])) {
                    $textss = $textse = "";
                    $enable_cnt++;
                    $user_enable_cnt++;
                    $iconb_class = 'class="fa fa-check-circle text-success text-left"';
                    $title = gettext("Enabled by user. Click to change rules state");
                }

                // These last two checks handle normal cases of default-enabled or default disabled rules
                // with no user overrides.
                elseif (($v['disabled'] == 1) && ($v['state_toggled'] == 0) && (!isset($enablesid[$gid][$sid]))) {
                    $textss = "<span class=\"text-muted\">";
                    $textse = "</span>";
                    $disable_cnt++;
                    $iconb_class = 'class="fa fa-times-circle-o text-danger text-left"';
                    $title = gettext("Disabled by default. Click to change rule state");
                }
                elseif ($v['disabled'] == 0 && $v['state_toggled'] == 0) {
                    $textss = $textse = "";
                    $enable_cnt++;
                    $iconb_class = 'class="fa fa-check-circle-o text-success text-left"';
                    $title = gettext("Enabled by default.");
                }

                // Determine which icon to display in the second column for rule action.
                // Default to ALERT icon.
                $textss = $textse = "";
                $iconact_class = 'class="fa fa-exclamation-triangle text-warning text-center"';
                $title_act = gettext("Rule will alert on traffic when triggered.");
                if ($v['action'] == 'drop' && $suricatacfg['blockoffenders'] == '1') {
                    $iconact_class = 'class="fa fa-thumbs-down text-danger text-center"';
                    $title_act = gettext("Rule will drop traffic when triggered.");
                }
                elseif ($v['action'] == 'reject' && $suricatacfg['ipsmode'] == 'inline' && $suricatacfg['blockoffenders'] == '1') {
                    $iconact_class = 'class="fa fa-hand-stop-o text-warning text-center"';
                    $title_act = gettext("Rule will reject traffic when triggered.");
                }
                if ($suricatacfg['blockoffenders'] == 'on') {
                    $title_act .= gettext("  Click to change rule action.");
                }

                // Rules with "noalert;" option enabled get special treatment
                if ($v['noalert'] == 1) {
                    $iconact_class = 'class="fa fa-exclamation-triangle text-success text-center"';
                    $title_act = gettext("Rule contains the 'noalert;' and/or 'flowbits:noalert;' options.");
                }

                $tmp = substr($v['rule'], 0, strpos($v['rule'], "("));
                $tmp = trim(preg_replace('/^\s*#+\s*/', '', $tmp));
                $rule_content = preg_split('/[\s]+/', $tmp);

                // Create custom <span> tags for the fields we truncate so we can
                // have a "title" attribute for tooltips to show the full string.
                $srcspan = $this->addTitleAttribute($textss, $rule_content[2]);
                $srcprtspan = $this->addTitleAttribute($textss, $rule_content[3]);
                $dstspan = $this->addTitleAttribute($textss, $rule_content[5]);
                $dstprtspan = $this->addTitleAttribute($textss, $rule_content[6]);

                $protocol = $rule_content[1];         //protocol field
                $source = $rule_content[2];           //source field
                $source_port = $rule_content[3];      //source port field
                $destination = $rule_content[5];      //destination field
                $destination_port = $rule_content[6]; //destination port field
                $message = suricata_get_msg($v['rule']); // description field
                $sid_tooltip = gettext("View the raw text for this rule");

                // Show text of "noalert;" flagged rules in Bootstrap SUCCESS color
                if ($v['noalert'] == 1) {
                    $tag_class = ' class="text-success" ';
                } else {
                    $tag_class = "";
                }

                $add = true;
                if (!empty($filter)) {
                    $haystack = strtolower("$sid $protocol $source $destination $message");
                    $add = (strpos($haystack, strtolower($filter)) !== false);
                }

                if ($add) {
                    $result[] = array(
                        'id' => "rule_".$gid."_".$sid,
                        'sid' => "<a href='javascript:;' onclick='showRule($sid, $gid);'>$sid</a>",
                        'gid' => $gid,
                        'state' => "$textss<span $iconb_class></span>$textse",
                        'action' => "$textss<span $iconact_class></span>$textse",
                        'proto' => "$textss $protocol $textse",
                        'source' => "$textss $source $textse",
                        'sport' => "$textss $source_port $textse",
                        'destination' => "$textss $destination $textse",
                        'dport' => "$textss $destination_port $textse",
                        'message' => "$textss $message $textse"
                    );
                }
            }
        }

        if (($sortField) && ($sortDir)) {
            usort($result, function ($a, $b) use ($sortField, $sortDir) {
                $res = 0;
                if ($a[$sortField] > $b[$sortField])
                    $res = 1;
                else if ($a[$sortField] < $b[$sortField])
                    $res = -1;
                if ($sortDir == 'desc')
                    $res *= -1;
                return $res;
            });
        }

        $rows = ($rowCount >= 0) ? array_slice($result, $pstart, $rowCount) : $result;

        return array(
            'current' => $curPage,
            'rowCount' => count($rows),
            'total' => count($result),
            'rows' => $rows
        );
    }


    public function getRuleAction($uuid, $currentruleset) {
        $rule_text = 'Invalid rule signature - no matching rule was found!';
        $rule_link = '';

        $currentruleset = urldecode($currentruleset);

        $rules_map = $this->getRulesMap($uuid, $currentruleset);

        if (isset($_POST['gid']) && isset($_POST['sid'])) {
            $gid = $_POST['gid'];
            $sid = $_POST['sid'];
            $rule_text = $rules_map[$gid][$sid]['rule'];
        }
        if (strpos($currentruleset, 'snort_') !== false) {
            $rule_link = "https://www.snort.org/rule_docs/{$gid}-{$sid}";
        }

        return array(
            'ruletext' => base64_encode($rule_text),
            'rulelink' => $rule_link,
            'ruleset' => $currentruleset
        );
    }


    public function getAnyRuleAction($uuid) {
        $rule_text = 'Invalid rule signature - no matching rule was found!';
        $rule_link = '';

        $currentruleset = '';

        require_once("plugins.inc.d/suricata.inc");

        $suricatacfg = $this->getSuricataConfig($uuid);

        $suricatadir = SURICATADIR;
        $suricata_rules_dir = SURICATA_RULES_DIR;
        $flowbit_rules_file = FLOWBITS_FILENAME;

        $gid = $_POST['gid'];
        $sid = $_POST['sid'];

        $ifaces = $this->getInterfaceNames();
        $if_real = $ifaces[strtolower($suricatacfg['iface'])];

        $suricatacfgdir = "{$suricatadir}suricata_{$if_real}";

        $rules = array_merge(glob(SURICATA_RULES_DIR . "/*.rules"), array("{$suricatadir}suricata_{$if_real}/rules/custom.rules", "{$suricatadir}suricata_{$if_real}/rules/flowbit-required.rules"));
        foreach ($rules as $rule) {
            $rules_map = suricata_load_rules_map($rule);
            if ($rules_map[$gid][$sid]['rule']) {
                $rule_text = base64_encode($rules_map[$gid][$sid]['rule']);
                $currentruleset = basename($rule);
                break;
            }
        }

        if (strpos($currentruleset, 'snort_') !== false) {
            $rule_link = "https://www.snort.org/rule_docs/{$gid}-{$sid}";
        }

        return array(
            'ruletext' => $rule_text,
            'rulelink' => $rule_link,
            'ruleset' => $currentruleset
        );
    }


    public function setStateAction($uuid, $currentruleset, $ruleid) {
        require_once("plugins.inc.d/suricata.inc");

        $currentruleset = urldecode($currentruleset);

        $suricatacfg = $this->getSuricataConfig($uuid);

        $rules_map = $this->getRulesMap($uuid, $currentruleset);

        $arr = explode('_', $ruleid);
        $gid = $arr[1];
        $sid = $arr[2];
        $state = $_POST['state'];

        $enablesid = suricata_load_sid_mods($suricatacfg['rulesidon']);
        $disablesid = suricata_load_sid_mods($suricatacfg['rulesidoff']);
        suricata_modify_sids($rules_map, $suricatacfg);

        if ($state == 'default') {
            if (isset($enablesid[$gid][$sid])) {
                unset($enablesid[$gid][$sid]);
            }
            if (isset($disablesid[$gid][$sid])) {
                unset($disablesid[$gid][$sid]);
            }
            if (isset($rules_map[$gid][$sid])) {
                $rules_map[$gid][$sid]['disabled'] = !$rules_map[$gid][$sid]['default_state'];
            }
        } else if ($state == 'enabled') {
            if (isset($disablesid[$gid][$sid])) {
                unset($disablesid[$gid][$sid]);
            }
            $enablesid[$gid][$sid] = "enablesid";
        } else if ($state == 'disabled') {
            if (isset($enablesid[$gid][$sid])) {
                unset($enablesid[$gid][$sid]);
            }
            $disablesid[$gid][$sid] = "disablesid";
        }

        $tmp = "";
        foreach (array_keys($enablesid) as $k1) {
            foreach (array_keys($enablesid[$k1]) as $k2)
                $tmp .= "{$k1}:{$k2}||";
        }
        $tmp = rtrim($tmp, "||");
        if (!empty($tmp))
            $suricatacfg['rulesidon'] = $tmp;
        else
            $suricatacfg['rulesidon'] = null;

        $tmp = "";
        foreach (array_keys($disablesid) as $k1) {
            foreach (array_keys($disablesid[$k1]) as $k2)
                $tmp .= "{$k1}:{$k2}||";
        }
        $tmp = rtrim($tmp, "||");
        if (!empty($tmp))
            $suricatacfg['rulesidoff'] = $tmp;
        else
            $suricatacfg['rulesidoff'] = null;

        suricata_modify_sids($rules_map, $suricatacfg);

        $realconfig = new \OPNsense\Suricata\Suricata();
        $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.rulesidon', $suricatacfg['rulesidon']);
        $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.rulesidoff', $suricatacfg['rulesidoff']);
        $realconfig->serializeToConfig();
        Config::getInstance()->save("Suricata: modified state for rule {$gid}:{$sid} on {$suricatacfg['iface']}.", true);

        return array('success' => 1);
    }


    public function setRuleActionAction($uuid, $currentruleset, $ruleid) {
        require_once("plugins.inc.d/suricata.inc");

        $currentruleset = urldecode($currentruleset);

        $suricatacfg = $this->getSuricataConfig($uuid);

        $rules_map = $this->getRulesMap($uuid, $currentruleset);

        $alertsid = suricata_load_sid_mods($suricatacfg['rulesidforcealert']);
        $dropsid = suricata_load_sid_mods($suricatacfg['rulesidforcedrop']);
        $rejectsid = suricata_load_sid_mods($suricatacfg['rulesidforcereject']);
        suricata_modify_sids_action($rules_map, $suricatacfg);

        $arr = explode('_', $ruleid);
        $gid = $arr[1];
        $sid = $arr[2];
        $action = $_POST['action'];

        switch ($action) {
            case "action_default":
                $rules_map[$gid][$sid]['action'] = $rules_map[$gid][$sid]['default_action'];
                if (isset($alertsid[$gid][$sid])) {
                    unset($alertsid[$gid][$sid]);
                }
                if (isset($dropsid[$gid][$sid])) {
                    unset($dropsid[$gid][$sid]);
                }
                if (isset($rejectsid[$gid][$sid])) {
                    unset($rejectsid[$gid][$sid]);
                }
                break;

            case "action_alert":
                $rules_map[$gid][$sid]['action'] = $rules_map[$gid][$sid]['alert'];
                if (!is_array($alertsid[$gid])) {
                    $alertsid[$gid] = array();
                }
                if (!is_array($alertsid[$gid][$sid])) {
                    $alertsid[$gid][$sid] = array();
                }
                $alertsid[$gid][$sid] = "alertsid";
                if (isset($dropsid[$gid][$sid])) {
                    unset($dropsid[$gid][$sid]);
                }
                if (isset($rejectsid[$gid][$sid])) {
                    unset($rejectsid[$gid][$sid]);
                }
                break;

            case "action_drop":
                $rules_map[$gid][$sid]['action'] = $rules_map[$gid][$sid]['drop'];
                if (!is_array($dropsid[$gid])) {
                    $dropsid[$gid] = array();
                }
                if (!is_array($dropsid[$gid][$sid])) {
                    $dropsid[$gid][$sid] = array();
                }
                $dropsid[$gid][$sid] = "dropsid";
                if (isset($alertsid[$gid][$sid])) {
                    unset($alertsid[$gid][$sid]);
                }
                if (isset($rejectsid[$gid][$sid])) {
                    unset($rejectsid[$gid][$sid]);
                }
                break;

            case "action_reject":
                $rules_map[$gid][$sid]['action'] = $rules_map[$gid][$sid]['reject'];
                if (!is_array($rejectsid[$gid])) {
                    $rejectsid[$gid] = array();
                }
                if (!is_array($rejectsid[$gid][$sid])) {
                    $rejectsid[$gid][$sid] = array();
                }
                $rejectsid[$gid][$sid] = "rejectsid";
                if (isset($alertsid[$gid][$sid])) {
                    unset($alertsid[$gid][$sid]);
                }
                if (isset($dropsid[$gid][$sid])) {
                    unset($dropsid[$gid][$sid]);
                }
                break;
            default:
                return array('success' => 0);
        }

        $realconfig = new \OPNsense\Suricata\Suricata();

        $tmp = "";
        foreach (array_keys($alertsid) as $k1) {
            foreach (array_keys($alertsid[$k1]) as $k2)
                $tmp .= "{$k1}:{$k2}||";
        }
        $tmp = rtrim($tmp, "||");

        if (!empty($tmp))
            $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.rulesidforcealert', $tmp);
        else
            $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.rulesidforcealert', null);

        $tmp = "";
        foreach (array_keys($dropsid) as $k1) {
            foreach (array_keys($dropsid[$k1]) as $k2)
                $tmp .= "{$k1}:{$k2}||";
        }
        $tmp = rtrim($tmp, "||");

        if (!empty($tmp))
            $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.rulesidforcedrop', $tmp);
        else
            $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.rulesidforcedrop', null);

        $tmp = "";
        foreach (array_keys($rejectsid) as $k1) {
            foreach (array_keys($rejectsid[$k1]) as $k2)
                $tmp .= "{$k1}:{$k2}||";
        }
        $tmp = rtrim($tmp, "||");

        if (!empty($tmp))
            $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.rulesidforcereject', $tmp);
        else
            $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.rulesidforcereject', null);

        $realconfig->serializeToConfig();
        Config::getInstance()->save("Suricata: modified action for rule {$gid}:{$sid} on {$suricatacfg['iface']}.", true);

        suricata_modify_sids($rules_map, $suricatacfg);

        return array('success' => 1);
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


    private function addTitleAttribute($tag, $title) {

        $result = "";
        if (empty($tag)) {
            // If passed an empty element tag, then
            // just create a <span> tag with title
            $result = "<span title=\"" . $title . "\">";
        }
        else {
            // Find the ending ">" for the element tag
            $pos = strpos($tag, ">");
            if ($pos !== false) {
                // We found the ">" delimter, so add "title"
                // attribute and close the element tag
                $result = substr($tag, 0, $pos) . " title=\"" . $title . "\">";
            }
            else {
                // We did not find the ">" delimiter, so
                // something is wrong, just return the
                // tag "as-is"
                $result = $tag;
            }
        }
        return $result;
    }


    private function getRulesMap($uuid, $currentruleset) {
        require_once("plugins.inc.d/suricata.inc");

        $suricatacfg = $this->getSuricataConfig($uuid);

        $suricatadir = SURICATADIR;
        $suricata_rules_dir = SURICATA_RULES_DIR;
        $flowbit_rules_file = FLOWBITS_FILENAME;

        $ifaces = $this->getInterfaceNames();
        $if_real = $ifaces[strtolower($suricatacfg['iface'])];

        $suricatacfgdir = "{$suricatadir}suricata_{$if_real}";

        $rules_map = array();
        $rulefile = "{$suricata_rules_dir}/{$currentruleset}";
        if ($currentruleset != 'custom.rules') {
            if ($currentruleset == "Auto-Flowbit Rules") {
                $rules_map = suricata_load_rules_map("{$suricatacfgdir}/rules/" . FLOWBITS_FILENAME);
            } elseif (substr($currentruleset, 0, 10) == "IPS Policy") {
                $rules_map = suricata_load_vrt_policy($suricatacfg['ipspolicy'], $suricatacfg['ipspolicymode']);
            } elseif ($currentruleset == "Active Rules") {
                $rules_map = suricata_load_rules_map("{$suricatacfgdir}/rules/");
            } elseif ($currentruleset == "User Forced Enabled Rules") {
                $rule_files = explode("||", $suricatacfg['rulesets']);

                foreach ($rule_files as $k => $v) {
                    $rule_files[$k] = $ruledir . "/" . $v;
                }
                $rule_files[] = "{$suricatacfgdir}/rules/" . FLOWBITS_FILENAME;
                $rule_files[] = "{$suricatacfgdir}/rules/custom.rules";
                $rules_map = suricata_get_filtered_rules($rule_files, suricata_load_sid_mods($suricatacfg['rulesidon']));
            } elseif ($currentruleset == "User Forced Disabled Rules") {
                $rule_files = explode("||", $suricatacfg['rulesets']);

                foreach ($rule_files as $k => $v) {
                    $rule_files[$k] = $ruledir . "/" . $v;
                }
                $rule_files[] = "{$suricatacfgdir}/rules/" . FLOWBITS_FILENAME;
                $rule_files[] = "{$suricatacfgdir}/rules/custom.rules";
                $rules_map = suricata_get_filtered_rules($rule_files, suricata_load_sid_mods($suricatacfg['rulesidoff']));
            } elseif ($currentruleset == "User Forced ALERT Action Rules") {
                $rules_map = suricata_get_filtered_rules("{$suricatacfgdir}/rules/", suricata_load_sid_mods($suricatacfg['rulesidforcealert']));
            } elseif ($currentruleset == "User Forced DROP Action Rules") {
                $rules_map = suricata_get_filtered_rules("{$suricatacfgdir}/rules/", suricata_load_sid_mods($suricatacfg['rulesidforcedrop']));
            }
            elseif ($currentruleset == "User Forced REJECT Action Rules") {
                $rules_map = suricata_get_filtered_rules("{$suricatacfgdir}/rules/", suricata_load_sid_mods($suricatacfg['rulesidforcereject']));
            } elseif (!file_exists($rulefile)) {
                $input_errors[] = gettext("{$currentruleset} seems to be missing!!! Please verify rules files have been downloaded, then go to the Categories tab and save the rule set again.");
            } else {
                $rules_map = suricata_load_rules_map($rulefile);
            }
        }

        return $rules_map;
    }
}
