<?php

/*
 * Copyright (C) 2023 DynFi
 * Copyright (C) 2019 Deciso B.V.
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


/**
 * @inherit
 */
class AlertsController extends IndexController
{
   public function indexAction() {
        $interfacesNames = $this->getInterfaceNames();
        $ifaces = array();

        $selected = $_GET['if'];
        $uuid = null;

        require_once("plugins.inc.d/suricata.inc");
        $suricataConfigs = suricata_get_configs();

        foreach ($suricataConfigs as $suricatacfg) {
            $iface = $suricatacfg['iface'];
            if (isset($interfacesNames[strtolower($iface)])) {
                $realif = $interfacesNames[strtolower($iface)];
                if ($selected == null) {
                    $selected = $realif;
                    $uuid = $suricatacfg["@attributes"]['uuid'];
                } else if ($selected == $realif) {
                    $uuid = $suricatacfg["@attributes"]['uuid'];
                }
                $ifaces[$iface] = $realif;
            }
        }

        $this->view->iface = $selected;
        $this->view->uuid = $uuid;
        $this->view->ifaces = $ifaces;
        $this->view->pick('OPNsense/Suricata/alerts');
    }

    public function downloadAction() {
        $interfacesNames = $this->getInterfaceNames();
        $ifaces = array();

        require_once("plugins.inc.d/suricata.inc");
        $suricatalogdir = SURICATALOGDIR;

        $selected = $_GET['if'];
        $uuid = null;

        $suricataConfigs = $suricataConfigs = suricata_get_configs();

        foreach ($suricataConfigs as $suricatacfg) {
            $iface = $suricatacfg['iface'];
            if (isset($interfacesNames[strtolower($iface)])) {
                $realif = $interfacesNames[strtolower($iface)];
                if ($selected == null) {
                    $selected = $realif;
                    $uuid = $suricatacfg["@attributes"]['uuid'];
                } else if ($selected == $realif) {
                    $uuid = $suricatacfg["@attributes"]['uuid'];
                }
                $ifaces[$iface] = $realif;
            }
        }

        $save_date = date("Y-m-d-H-i-s");
        $file_name = "suricata_logs_{$save_date}_{$selected}.tar.gz";
        exec("cd {$suricatalogdir}suricata_{$selected} && /usr/bin/tar -czf /tmp/{$file_name} alert*");

        if (file_exists("/tmp/{$file_name}")) {
            ob_start();
            if (isset($_SERVER['HTTPS'])) {
                header('Pragma: ');
                header('Cache-Control: ');
            } else {
                header("Pragma: private");
                header("Cache-Control: private, must-revalidate");
            }
            header("Content-Type: application/octet-stream");
            header("Content-disposition: attachment; filename = {$file_name}");
            ob_end_clean();
            readfile("/tmp/{$file_name}");

            if (file_exists("/tmp/{$file_name}"))
                unlink("/tmp/{$file_name}");
            exit;
        }
    }

    private function getInterfaceNames()
    {
        $intfmap = array();
        $config = Config::getInstance()->object();
        if ($config->interfaces->count() > 0) {
            foreach ($config->interfaces->children() as $key => $node) {
                $intfmap[strtolower($key)] = (string)$node->if;
                if (!empty((string)$node->descr))
                    $intfmap[strtolower((string)$node->descr)] = (string)$node->if;
            }
        }
        return $intfmap;
    }
}
