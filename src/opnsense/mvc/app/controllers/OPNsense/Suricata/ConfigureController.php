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


class ConfigureController extends IndexController
{
    public function renderPage($uuid) {

        $suricatacfg = $this->getSuricataConfig($uuid);

        if (!$suricatacfg) {
            die('No suricata config found');
        }

        $this->view->iface = $suricatacfg['iface'];
        //$this->view->formCategories = $this->getForm("categories");
        $this->view->pick('OPNsense/Suricata/configure');

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
}
