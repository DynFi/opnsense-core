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
class LogController extends IndexController
{
    public function renderPage($module, $scope)
    {
        if (!str_contains($module, '_vlan'))
            $module = str_replace('vlan', '_vlan', $module);

        $this->view->pick('OPNsense/Suricata/log');
        $this->view->module = $module;
        $this->view->scope = $scope;
        $this->view->service = $module;
        $this->view->default_log_severity = 'Informational';
        $this->view->current_log = $module.'/'.$scope;

        $interfacesNames = $this->getInterfaceNames();
        $interfacesDescs = $this->getInterfaceDescs();

        $mname = $module;

        $logFiles = array();
        $config = Config::getInstance()->toArray();

        $suricataConfigs = (isset($config['OPNsense']['Suricata']['interfaces']['interface'][1])) ? $config['OPNsense']['Suricata']['interfaces']['interface'] : [ $config['OPNsense']['Suricata']['interfaces']['interface'] ];

        require_once("plugins.inc.d/suricata.inc");

        foreach ($suricataConfigs as $suricatacfg) {
            $iface = $suricatacfg['iface'];
            if (isset($interfacesNames[strtolower($iface)])) {
                $realif = $interfacesNames[strtolower($iface)];

                if (isset($interfacesDescs[strtolower($iface)]))
                    $logFiles[$interfacesDescs[strtolower($iface)]] = 'suricata_'.$realif.'/suricata';
                else
                    $logFiles[$iface] = 'suricata_'.$realif.'/suricata';

                if ($module == 'suricata_'.$realif) {
                    if (isset($interfacesDescs[strtolower($iface)]))
                        $mname = $interfacesDescs[strtolower($iface)];
                    else
                        $mname = $iface;
                }
                foreach (scandir(SURICATALOGDIR.'suricata_'.$realif) as $f) {
                    if (str_contains($f, '.log') && ($f != 'suricata.log')) {
                        $logName = str_replace('.log', '', $f);

                        if (isset($interfacesDescs[strtolower($iface)]))
                            $logFiles[$interfacesDescs[strtolower($iface)].': '.$logName] = 'suricata_'.$realif.'/'.$logName;
                        else
                            $logFiles[$iface.': '.$logName] = 'suricata_'.$realif.'/'.$logName;

                        if (($module == 'suricata_'.$realif) && ($scope == $logName)) {
                            if (isset($interfacesDescs[strtolower($iface)]))
                                $mname = $interfacesDescs[strtolower($iface)].': '.$logName;
                            else
                                $mname = $iface.': '.$logName;
                        }
                    }
                }
            }
        }
        $this->view->logFiles = $logFiles;

        $this->view->menuBreadcrumbs = array(
            array('name' => 'Services'),
            array('name' => 'Suricata'),
            array('name' => 'Log'),
            array('name' => $mname)
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
    }

    public function __call($name, $arguments)
    {
        if (substr($name, -6) == 'Action') {
            $scope = count($arguments) > 0 ? $arguments[0] : "core";
            $module = substr($name, 0, strlen($name) - 6);
            if (str_contains($module, 'suricata')) {
                $module = 'suricata_'.strtolower(str_replace('suricata', '', $module));
            }
            return $this->renderPage($module, $scope);
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

    private function getInterfaceDescs()
    {
        $intfmap = array();
        $config = Config::getInstance()->object();
        if ($config->interfaces->count() > 0) {
            foreach ($config->interfaces->children() as $key => $node) {
                if (!empty((string)$node->descr))
                    $intfmap[strtolower($key)] = (string)$node->descr;

            }
        }
        return $intfmap;
    }
}


