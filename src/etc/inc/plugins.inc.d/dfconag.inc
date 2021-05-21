<?php

/*
    Copyright (C) 2020 DynFi
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


use \OPNsense\Core\Config;

function dfconag_services()
{
    global $config;

    $services = array();

    if ((isset($config['OPNsense']['DFConAg']['settings']['enabled'])) && ($config['OPNsense']['DFConAg']['settings']['enabled'] == 1)) {
        $services[] = array(
            'description' => gettext('DynFi Connection Agent').' ('.$config['OPNsense']['DFConAg']['settings']['dfmHost'].':'.$config['OPNsense']['DFConAg']['settings']['dfmSshPort'].')',
            'configd' => array(
                'restart' => array('template reload OPNsense/DFConAg', 'dfconag restart'),
                'start' => array('template reload OPNsense/DFConAg', 'dfconag start'),
                'stop' => array('dfconag stop', 'template reload OPNsense/DFConAg'),
            ),
            'name' => 'dfconag',
            'pidfile' => '/var/run/dfconag.pid'
        );
    }

    return $services;
}


function dfconag_configure() {
    return array(
        'webgui' => array('dfconag_regenerate_config')
    );
}


function dfconag_log($message) {
  $message = date("Y/m/d H:i:s")." dfconag: ".str_replace(array("\n", "\r"), "", $message);
  file_put_contents('/var/log/dfconag.log', $message.PHP_EOL , FILE_APPEND | LOCK_EX);
}


function dfconag_firewall($fw) {
    global $config;

    $settings = dfconag_regenerate_config();

    if (intval($settings['enabled'])) {
        if ((!empty($settings['dfmHost'])) && (!empty($settings['dfmSshPort']))) {
            dfconag_create_fw_rule($fw, $settings['dfmHost'], $settings['dfmSshPort'], $settings['interfaces'], 'DynFi Connection Agent SSH', 'ui/dfconag');
        }
        if ((!empty($settings['mainTunnelPort'])) && (!empty($settings['dvTunnelPort']))) {
            dfconag_create_fw_rule($fw, $settings['dfmHost'], $settings['mainTunnelPort'], $settings['interfaces'], 'DynFi Connection Agent main tunnel', 'ui/dfconag');
            dfconag_create_fw_rule($fw, $settings['dfmHost'], $settings['dvTunnelPort'], $settings['interfaces'], ' DynFi Connection Agent DirectView tunnel', 'ui/dfconag');
        }
    }
}


function dfconag_regenerate_config() {
    global $config;

    $dfconag = new \OPNsense\DFConAg\DFConAg();
    $_dfconag = $dfconag->getNodes();
    $settings = $_dfconag['settings'];

    $needs_restart = false;
    $localSshPort = (!empty($config['system']['ssh']['port'])) ? $config['system']['ssh']['port'] : 22;
    $localDvPort = (!empty($config['system']['webgui']['port'])) ? $config['system']['webgui']['port'] : ($config['system']['webgui']['protocol'] == 'https' ? 443 : 80);

    if (($localSshPort != $settings['localSshPort']) || ($localDvPort != $settings['localDvPort'])) {
        $dfconag->setNodes(array(
            'settings' => array(
                'localSshPort' => $localSshPort,
                'localDvPort' => $localDvPort,
            )
        ));
        $dfconag->serializeToConfig();
        Config::getInstance()->save(null, false);
        $settings['localSshPort'] = $localSshPort;
        $settings['localDvPort'] = $localDvPort;
        if (intval($settings['enabled']))
            $needs_restart = true;
    }

    if ($needs_restart) {
        dfconag_log("Restarting agent...");
        mwexec('/usr/local/sbin/configctl template reload OPNsense/DFConAg');
        mwexec('/usr/local/sbin/configctl dfconag restart');
    }

    return $settings;
}


function dfconag_create_fw_rule(&$fw, $ip, $port, $ifaces, $descr, $ref) {
    foreach ($fw->getInterfaceMapping() as $iface => $intfinfo) {
        if (isset($ifaces[$iface]) && (intval($ifaces[$iface]['selected']))) {
            dfconag_log("Generating firewall rule for $ip:$port ($iface)");
            $addressMap = array();
            foreach (interfaces_addresses($iface, true, $fw->getIfconfigDetails()) as $address => $info) {
                $addressMap[$info['family']] = $address;
            }
            $family = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'inet6' : 'inet';
            $rule = array(
                'interface' => $iface,
                'from' => '(self)',
                'to' => $ip,
                'to_port' => $port,
                'direction' => 'out',
                'ipprotocol' => $family,
                'protocol' => 'tcp',
                '#ref' => $ref,
                'descr' => $descr
            );
            if (isset($addressMap[$family])) {
                $rule['from'] = substr($addressMap[$family], 0, strpos($addressMap[$family], '/'));
            }
            $fw->registerFilterRule(200000, $rule,
                array(
                    'type' => 'pass',
                    'log' => true,
                    'disablereplyto' => 1
                )
            );
        }
    }
}
