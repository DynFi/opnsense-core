<?php

/*
 * Copyright (C) 2020 Dawid Kujawa <dawid.kujawa@dynfi.com>
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

namespace OPNsense\DFConAg\Api;

use \OPNsense\Base\ApiMutableServiceControllerBase;
use \OPNsense\Core\Backend;
use \OPNsense\Core\Config;
use \OPNsense\DFConAg\DFConAg;

require_once('config.inc');


/**
 * Class ServiceController
 * @package OPNsense\DFConAg
 */
class ServiceController extends ApiMutableServiceControllerBase
{
    protected static $internalServiceClass = '\OPNsense\DFConAg\DFConAg';
    protected static $internalServiceEnabled = 'settings.enabled';
    protected static $internalServiceTemplate = 'OPNsense/DFConAg';
    protected static $internalServiceName = 'dfconag';

    private $backend = null;

    public function reconfigureAction()
    {
        $status = "failed";
        $message = "Only POST requests allowed";
        if ($this->request->isPost()) {
            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag->setNodes(array(
                'settings' => array(
                    'enabled' => '1'
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            $settings = $dfconag->getNodes()['settings'];

            if (empty($settings['dfmHost']))
                return array("status" => "failed", "message" => "Please provide DFM host");

            if (empty($settings['dfmSshPort']))
                return array("status" => "failed", "message" => "Please provide DFM SSH port");

            if (empty($settings['dfmUsername']))
                return array("status" => "failed", "message" => "Please provide DFM username");

            if (empty($settings['dfmPassword']))
                return array("status" => "failed", "message" => "Please provide DFM password");

            $keyscanresult = $this->configdRun('dfconag keyscan '.$settings['dfmSshPort'].' '.$settings['dfmHost']);

            if (empty($keyscanresult))
                return array("status" => "failed", "message" => "SSH key scan failed");

            return array("status" => "ok", "message" => $keyscanresult);
        }
        return array("status" => $status, "message" => $message);
    }

    public function acceptKeyAction()
    {
        $status = "failed";
        $message = "Only POST requests allowed";
        if ($this->request->isPost() && $this->request->hasPost("key")) {
            if (!$this->checkPrivateKey())
                return array("status" => "failed", "message" => "SSH private key does not exist");

            $key = trim($this->request->getPost("key"));

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag->setNodes(array(
                'settings' => array(
                    'sshKey' => $key
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            if (!file_exists('/var/run/dfconag/known_hosts')) {
                file_put_contents('/var/run/dfconag/known_hosts', $key);
            }

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $_dfconag = $dfconag->getNodes();
            $settings = $_dfconag['settings'];

            $params = array(
                $settings['dfmSshPort'],
                $settings['dfmHost'],
                $settings['dfmUsername'],
                $settings['dfmPassword']
            );
            $optionsJson = $this->configdRun('dfconag getaddoptions '.implode(' ', $params));

            if (empty($optionsJson))
                return array("status" => "failed", "message" => "get-add-options failed");

            $options = json_decode($optionsJson, true);
            $mainTunnelPort = intval($options['nextTunnelPort']);
            $dvTunnelPort = $mainTunnelPort + 1;

            $dfconag->setNodes(array(
                'settings' => array(
                    'mainTunnelPort' => $mainTunnelPort,
                    'dvTunnelPort' => $dvTunnelPort,
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            $params = array(
                $settings['dfmSshPort'],
                $settings['dfmHost'],
                $settings['dfmUsername'],
                $settings['dfmPassword'],
                $mainTunnelPort,
                $dvTunnelPort
            );
            $portsResp = $this->configdRun('dfconag reserveports '.implode(' ', $params));

            if (strpos($portsResp, 'Ports reserved successfully') === false)
                return array("status" => "failed", "message" => "reserve-ports failed");

            return array("status" => "ok", "message" => $optionsJson);
        }
        return array("status" => $status, "message" => $message);
    }


    public function registerDeviceAction() {
        $status = "failed";
        $message = "Only POST requests allowed";
        if ($this->request->isPost() && $this->request->hasPost("groupId") && $this->request->hasPost("userPass")) {
            if (!$this->checkPrivateKey())
                return array("status" => "failed", "message" => "SSH private key does not exist");

            $groupId = trim($this->request->getPost("groupId"));
            $userPass = trim($this->request->getPost("userPass"));

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag = $dfconag->getNodes();
            $settings = $dfconag['settings'];

            $backend = new Backend();
            $params = array(
                $settings['dfmSshPort'],
                $settings['dfmHost'],
                $settings['dfmUsername'],
                $settings['dfmPassword'],
                $settings['mainTunnelPort'],
                $settings['dvTunnelPort'],
                $settings['remoteSshPort'],
                $settings['remoteDvPort'],
                $groupId,
                $userPass
            );
            $addResp = $this->configdRun('dfconag addme '.implode(' ', $params));

            if (empty($addResp))
                return array("status" => "failed", "message" => "add-me failed");

            $obj = json_decode($addResp, true);

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag->setNodes(array(
                'settings' => array(
                    'deviceId' => $obj['id']
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            $this->configdRun('template reload OPNsense/DFConAg');

            return array("status" => "ok", "message" => $obj['id']);
        }
        return array("status" => $status, "message" => $message);
    }


    public function rejectKeyAction()
    {
        $status = "failed";
        $message = "Only POST requests allowed";
        if ($this->request->isPost()) {
            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag->setNodes(array(
                'settings' => array(
                    'enabled' => '0',
                    'sshKey' => null,
                    'mainTunnelPort' => null,
                    'dvTunnelPort' => null,
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            $this->configdRun('template reload OPNsense/DFConAg');

            return array("status" => "ok", "message" => "");
        }
        return array("status" => $status, "message" => $message);
    }


    public function connectionAction() {
        if ($this->request->isPost()) {
            if (!$this->checkPrivateKey())
                return array("status" => "failed", "message" => "SSH private key does not exist");

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $settings = $dfconag->getNodes()['settings'];

            if (!intval($settings['enabled']))
                return array("status" => "ok", "message" => "");

            $backend = new Backend();
            $params = array(
                $settings['dfmSshPort'],
                $settings['dfmHost']
            );
            $whoResp = $this->configdRun('dfconag whoami '.implode(' ', $params));

            if (empty($whoResp))
                return array("status" => "failed", "message" => "who-am-i failed");

            $obj = json_decode($whoResp, true);
            if (empty($obj))
                return array("status" => "ok", "message" => "");

            if ($settings['deviceId'] != $obj['id']) {
                $dfconag->setNodes(array(
                    'settings' => array(
                        'deviceId' => $obj['id']
                    )
                ));
                $dfconag->serializeToConfig();
                Config::getInstance()->save();
                $settings = $dfconag->getNodes()['settings'];
            }

            return array("status" => "ok", "message" => json_encode($settings));
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
    }


    private function configdRun($cmd) {
        if (!$this->backend)
            $this->backend = new Backend();
        return trim($this->backend->configdRun($cmd));
    }


    private function checkPrivateKey() {
        if (!file_exists('/var/run/dfconag/key'))
            $this->configdRun('dfconag generatekey');
        return (file_exists('/var/run/dfconag/key'));
    }
}
