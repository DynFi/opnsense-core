<?php

/*
 * Copyright (C) 2023 DynFi
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

namespace OPNsense\Suricata;

use OPNsense\Base\IndexController;
use OPNsense\Core\Config;

use OPNsense\Suricata\Suricata;


class ConfigureController extends IndexController
{
    public function renderPage($uuid) {

        $suricatacfg = $this->getSuricataConfig($uuid);

        if (!$suricatacfg) {
            die('No suricata config found');
        }

        $input = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : null;

        $config = Config::getInstance()->toArray();

        $this->view->suricatacfg = $this->prepareCategoriesPage($uuid, $config, $suricatacfg, $input);
        $this->view->pconfig = $config['OPNsense']['Suricata']['global'];
        $this->view->iface = $suricatacfg['iface'];
        $this->view->blockoffenders = $suricatacfg['blockoffenders'];
        $this->view->ipsmode = $suricatacfg['ipsmode'];
        $this->view->uuid = $uuid;
        $this->view->pick('OPNsense/Suricata/configure');

        $this->view->customrules = (!empty($suricatacfg['customrules'])) ? base64_decode($suricatacfg['customrules']) : '';

        $this->prepareRulesPage($uuid, $config, $suricatacfg);

        $this->view->menuBreadcrumbs = array(
            array('name' => 'Services'),
            array('name' => 'Suricata'),
            array('name' => 'Configure'),
            array('name' => $suricatacfg['iface'])
        );
        $output = array();
        foreach ($this->view->menuBreadcrumbs as $crumb) {
            $output[] = gettext($crumb['name']);
        }
        $this->view->title = join(': ', $output);
        $output = array();
        foreach (array_reverse($this->view->menuBreadcrumbs) as $crumb) {
            $output[] = gettext($crumb['name']);
        }
        $this->view->headTitle = join(' | ', $output);
        $this->view->headerButtons = array(
            array(
                "id" => "Back",
                "name" => "",
                "iconClass" => "icon glyphicon glyphicon-chevron-left",
                "buttons" => array(
                    array(
                        "id" => "Back",
                        "name" => "",
                        "url" => "/ui/suricata"
                    )
                )
            )
        );

        $this->view->formFlow = $this->getForm("flow");
        $this->view->formParsers = $this->getForm("parsers");
        $this->view->formVariables = $this->getForm("variables");
        $this->view->formAction = $this->getForm("rule_action");
    }


    public function __call($name, $arguments)
    {
        $this->renderPage($arguments[0]);
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


    private function prepareCategoriesPage($uuid, $config, $suricatacfg, $input = null) {
        require_once("plugins.inc.d/suricata.inc");

        $suricatadir = SURICATADIR;
        $suricata_rules_dir = SURICATA_RULES_DIR;
        $flowbit_rules_file = FLOWBITS_FILENAME;

        $snortdownload = $config['OPNsense']['Suricata']['global']['enablevrtrules'] == '1';
        $emergingdownload = $config['OPNsense']['Suricata']['global']['enableetopenrules'] == '1';
        $etpro = $config['OPNsense']['Suricata']['global']['enableetprorules'] == '1';
        $snortcommunitydownload = $config['OPNsense']['Suricata']['global']['snortcommunityrules'] == '1';
        $feodotrackerdownload = $config['OPNsense']['Suricata']['global']['enablefeodobotnetc2rules'] == '1';
        $sslbldownload = $config['OPNsense']['Suricata']['global']['enableabusesslblacklistrules'] == '1';
        $enableextrarules = $config['OPNsense']['Suricata']['global']['enableextrarules'] == "1";
        $extrarules = $config['OPNsense']['Suricata']['global']['extrarules']['rule'];

        $no_community_files = (!file_exists("{$suricata_rules_dir}".GPL_FILE_PREFIX."community.rules"));
        $no_feodotracker_files = (!file_exists("{$suricata_rules_dir}"."feodotracker.rules"));
        $no_sslbl_files = (!file_exists("{$suricata_rules_dir}"."sslblacklist_tls_cert.rules"));

        $isrulesfolderempty = glob("{$suricata_rules_dir}*.rules");

        $default_rules = array( "app-layer-events.rules", "decoder-events.rules", "dhcp-events.rules", "dnp3-events.rules", "dns-events.rules", "files.rules", "http-events.rules", "http2-events.rules", "ipsec-events.rules", "kerberos-events.rules", "modbus-events.rules", "mqtt-events.rules", "nfs-events.rules", "ntp-events.rules", "smb-events.rules", "smtp-events.rules", "stream-events.rules", "tls-events.rules" );

        $ifaces = $this->getInterfaceNames();
        $if_real = $ifaces[strtolower($suricatacfg['iface'])];

        $realconfig = new \OPNsense\Suricata\Suricata();

        if (($input) && (!empty($input['submit_categories']))) {
            $enabled_items = implode("||", $default_rules);
            if (isset($input['unselectall'])) {
                $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.rulesets', $enabled_items);
            } else if (isset($input['selectall'])) {
                if ($snortcommunitydownload)
                    $enabled_items .= "||" . GPL_FILE_PREFIX."community.rules";
                if ($feodotrackerdownload)
                    $enabled_items .= "||" . "feodotracker.rules";
                if ($sslbldownload)
                    $enabled_items .= "||" . "sslblacklist_tls_cert.rules";
                $emergingrules = array();
                $snortrules = array();

                $dh = (empty($isrulesfolderempty)) ? opendir("{$suricatadir}suricata_{$if_real}/rules/") : opendir("{$suricata_rules_dir}");

                while (false !== ($filename = readdir($dh))) {
                    $filename = basename($filename);
                    if (substr($filename, -5) != "rules")
                        continue;
                    if (strstr($filename, ET_OPEN_FILE_PREFIX) && $emergingdownload)
                        $emergingrules[] = $filename;
                    else if (strstr($filename, ET_PRO_FILE_PREFIX) && $etpro)
                        $emergingrules[] = $filename;
                    else if (strstr($filename, VRT_FILE_PREFIX) && $snortdownload) {
                        $snortrules[] = $filename;
                    }
                }

                sort($emergingrules);
                sort($snortrules);

                $enabled_items .= "||" . implode("||", $emergingrules);
                $enabled_items .= "||" . implode("||", $snortrules);

                $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.rulesets', $enabled_items);

            } else {
                if (is_array($input['toenable']))
                    $enabled_items .= "||" . implode("||", $input['toenable']);
                else
                    $enabled_items .=  "||{$input['toenable']}";
                $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.rulesets', $enabled_items);
            }

            foreach (array('autoflowbits', 'ipspolicyenable') as $reqfld) {
                if (!isset($input[$reqfld]))
                    $input[$reqfld] = '0';
            }
            foreach (array('autoflowbits', 'ipspolicyenable', 'ipspolicy') as $k) {
                $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.'.$k, $input[$k]);
            }
            $realconfig->serializeToConfig();
            Config::getInstance()->save(null, false);

            $suricatacfg = $this->getSuricataConfig($uuid);
        }

        $cat_mods = suricata_sid_mgmt_auto_categories($suricatacfg, false);
        $enabled_rulesets_array = explode("||", $suricatacfg['rulesets']);

        $com_rules = array();
        if ($snortcommunitydownload) {
            $community_rules_file = GPL_FILE_PREFIX."community.rules";
            if (!isset($cat_mods[$community_rules_file])) {
                $com_rules[] = array(
                    'file' => $community_rules_file,
                    'name' => "Snort GPLv2 Community Rules (Talos-certified)",
                    'enabled' => in_array($community_rules_file, $enabled_rulesets_array)
                );
            } else {
                $com_rules[] = array(
                    'file' => $community_rules_file,
                    'name' => "Snort GPLv2 Community Rules (Talos-certified)",
                    'autoenabled' => ($cat_mods[$community_rules_file] == 'enabled'),
                    'autodisabled' => ($cat_mods[$community_rules_file] != 'enabled')
                );
            }
        }
        if ($feodotrackerdownload) {
            $feodotracker_rules_file = "feodotracker.rules";
            if (!isset($cat_mods[$feodotracker_rules_file])) {
                $com_rules[] = array(
                    'file' => $feodotracker_rules_file,
                    'name' => "Feodo Tracker Botnet C2 IP Rules",
                    'enabled' => in_array($feodotracker_rules_file, $enabled_rulesets_array)
                );
            } else {
                $com_rules[] = array(
                    'file' => $feodotracker_rules_file,
                    'name' => "Feodo Tracker Botnet C2 IP Rules",
                    'autoenabled' => ($cat_mods[$feodotracker_rules_file] == 'enabled'),
                    'autodisabled' => ($cat_mods[$feodotracker_rules_file] != 'enabled')
                );
            }
        }
        if ($sslbldownload) {
            $sslbl_rules_file = "sslblacklist_tls_cert.rules";
            if (!isset($cat_mods[$sslbl_rules_file])) {
                $com_rules[] = array(
                    'file' => $sslbl_rules_file,
                    'name' => "ABUSE.ch SSL Blacklist Rules",
                    'enabled' => in_array($sslbl_rules_file, $enabled_rulesets_array)
                );
            } else {
                $com_rules[] = array(
                    'file' => $sslbl_rules_file,
                    'name' => "ABUSE.ch SSL Blacklist Rules",
                    'autoenabled' => ($cat_mods[$sslbl_rules_file] == 'enabled'),
                    'autodisabled' => ($cat_mods[$sslbl_rules_file] != 'enabled')
                );
            }
        }
        $this->view->com_rules = $com_rules;

        $emergingrules = array();
        $snortrules = array();

        $dh = (empty($isrulesfolderempty)) ? opendir("{$suricatadir}suricata_{$if_real}/rules/") : opendir("{$suricata_rules_dir}");

        while (false !== ($filename = readdir($dh))) {
            $filename = basename($filename);
            if (substr($filename, -5) != "rules")
                continue;
            if (strstr($filename, ET_OPEN_FILE_PREFIX) && $emergingdownload)
                $emergingrules[] = $filename;
            else if (strstr($filename, ET_PRO_FILE_PREFIX) && $etpro)
                $emergingrules[] = $filename;
            else if (strstr($filename, VRT_FILE_PREFIX) && $snortdownload) {
                $snortrules[] = $filename;
            }
        }

        sort($emergingrules);
        sort($snortrules);

        $cnt = max(count($emergingrules), count($snortrules));
        $oth_rules = array();

        for ($i = 0; $i < $cnt; $i++) {
            $obj = array();
            if ($i < count($emergingrules)) {
                if (!isset($cat_mods[$emergingrules[$i]])) {
                    $obj['emerging'] = array(
                        'file' => $emergingrules[$i],
                        'name' => $emergingrules[$i],
                        'enabled' => in_array($emergingrules[$i], $enabled_rulesets_array)
                    );
                } else {
                    $obj['emerging'] = array(
                        'file' => $emergingrules[$i],
                        'name' => $emergingrules[$i],
                        'autoenabled' => ($cat_mods[$emergingrules[$i]] == 'enabled'),
                        'autodisabled' => ($cat_mods[$emergingrules[$i]] != 'enabled')
                    );
                }
            }
            if ($i < count($snortrules)) {
                if (!isset($cat_mods[$snortrules[$i]])) {
                    $obj['snort'] = array(
                        'file' => $snortrules[$i],
                        'name' => $snortrules[$i],
                        'enabled' => in_array($snortrules[$i], $enabled_rulesets_array)
                    );
                } else {
                    $obj['snort'] = array(
                        'file' => $snortrules[$i],
                        'name' => $snortrules[$i],
                        'autoenabled' => ($cat_mods[$snortrules[$i]] == 'enabled'),
                        'autodisabled' => ($cat_mods[$snortrules[$i]] != 'enabled')
                    );
                }
            }
            $oth_rules[] = $obj;
        }
        $this->view->oth_rules = $oth_rules;

        return $suricatacfg;
    }


    private function prepareRulesPage($uuid, $config, $suricatacfg) {
        require_once("plugins.inc.d/suricata.inc");

        $suricatadir = SURICATADIR;
        $suricata_rules_dir = SURICATA_RULES_DIR;
        $flowbit_rules_file = FLOWBITS_FILENAME;

        $ifaces = $this->getInterfaceNames();
        $if_real = $ifaces[strtolower($suricatacfg['iface'])];

        $suricatacfgdir = "{$suricatadir}suricata_{$if_real}";

        $snortdownload = $config['OPNsense']['Suricata']['global']['enablevrtrules'] == '1';
        $emergingdownload = $config['OPNsense']['Suricata']['global']['enableetopenrules'] == '1';
        $etpro = $config['OPNsense']['Suricata']['global']['enableetprorules'] == '1';

        $categories = explode("||", $suricatacfg['rulesets']);
        $categories[] = "User Forced Enabled Rules";
        $categories[] = "User Forced Disabled Rules";

        if ($suricatacfg['blockoffenders'] == '1') {
            $categories[] = "User Forced ALERT Action Rules";
            if ($suricatacfg['blockdropsonly'] == '1' || $suricatacfg['ipsmode'] == 'inline') {
                $categories[] = "User Forced DROP Action Rules";
            }
        }
        if ($suricatacfg['ipsmode'] == 'inline' && $suricatacfg['blockoffenders'] == '1') {
            $categories[] = "User Forced REJECT Action Rules";
        }
        $categories[] = "Active Rules";

        if ($_GET['openruleset'])
            $currentruleset = htmlspecialchars($_GET['openruleset'], ENT_QUOTES | ENT_HTML401);
        elseif ($_POST['selectbox'])
            $currentruleset = $_POST['selectbox'];
        elseif ($_POST['openruleset']) {
            if ($_POST['openruleset'] == 'savecustom') {
                $currentruleset = 'custom.rules';
                $this->view->customrules = $_POST['customrules'];
                $realconfig = new \OPNsense\Suricata\Suricata();
                $realconfig->setNodeByReference('interfaces.interface.'.$uuid.'.customrules', base64_encode($_POST['customrules']));
                $realconfig->serializeToConfig();
                Config::getInstance()->save(null, false);
            } else {
                $currentruleset = $_POST['openruleset'];
            }
        } else
            $currentruleset = $categories[0];

        if (empty($categories[0]) && ($currentruleset != "custom.rules") && ($currentruleset != "Auto-Flowbit Rules")) {
            if (!empty($suricatacfg['ipspolicy']))
                $currentruleset = "IPS Policy - " . ucfirst($suricatacfg['ipspolicy']);
            else
                $currentruleset = "custom.rules";
        }

        $tmp = glob("{$suricata_rules_dir}*.rules");
        if (empty($tmp))
            $currentruleset = "custom.rules";

        $this->view->categories = $this->buildCategoryList($categories, $suricatacfg, $snortdownload, $emergingdownload, $etpro);
        $this->view->currentruleset = $currentruleset;

    }


    private function buildCategoryList($categories, $suricatacfg, $snortdownload, $emergingdownload, $etpro) {
        require_once("plugins.inc.d/suricata.inc");

        $list = array();

        $files = $categories;

        if ($suricatacfg['ipspolicyenable'] == '1')
            $files[] = "IPS Policy - " . ucfirst($suricatacfg['ipspolicy']);

        if ($suricatacfg['autoflowbits'] == '1')
            $files[] = "Auto-Flowbit Rules";

        natcasesort($files);

        foreach ($files as $value) {
            if ($snortdownload != 'on' && substr($value, 0, mb_strlen(VRT_FILE_PREFIX)) == VRT_FILE_PREFIX)
                continue;
            if ($emergingdownload != 'on' && substr($value, 0, mb_strlen(ET_OPEN_FILE_PREFIX)) == ET_OPEN_FILE_PREFIX)
                continue;
            if ($etpro != 'on' && substr($value, 0, mb_strlen(ET_PRO_FILE_PREFIX)) == ET_PRO_FILE_PREFIX)
                continue;
            if (empty($value))
                continue;

            $list[$value] = $value;
        }

        return(['custom.rules' => 'custom.rules'] + $list);
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
}
