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
            exec("tail -{$anentries} -r {$suricatalogdir}/suricata/suricata_{$if_real}/alerts.log > $tmpfile");

            if (file_exists($tmpfile)) {
                $tmpblocked = array_flip(suricata_get_blocked_ips());
                $counter = 0;
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
