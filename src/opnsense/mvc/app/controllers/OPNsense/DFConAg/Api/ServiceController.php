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

    public function pretestAction() {
        if ($this->request->isPost()) {
            $result = $this->configdRun('dfconag pretest');

            if (empty($result))
                return array("status" => "failed", "message" => "pre test failed");

            return array("status" => "ok", "message" => $result);
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
    }

    public function connectAction()
    {
        global $config;

        if ($this->request->isPost()) {
            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $settings = $dfconag->getNodes()['settings'];

            if (empty($settings['dfmHost']))
                return array("status" => "failed", "message" => "Please provide DFM host");

            if (empty($settings['dfmSshPort']))
                return array("status" => "failed", "message" => "Please provide DFM SSH port");

            $keyscanresult = $this->configdRun('dfconag keyscan '.$settings['dfmSshPort'].' '.$settings['dfmHost']);
            if (empty($keyscanresult))
                return array("status" => "failed", "message" => "SSH key scan failed");

            $sshPort = (!empty($config['system']['ssh']['port'])) ? $config['system']['ssh']['port'] : 22;
            $dvPort = (!empty($config['system']['webgui']['port'])) ? $config['system']['webgui']['port'] : ($config['system']['webgui']['protocol'] == 'https' ? 443 : 80);

            $dfconag->setNodes(array(
                'settings' => array(
                    'enabled' => '1',
                    'remoteSshPort' => $sshPort,
                    'remoteDvPort' => $dvPort
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            return array("status" => "ok", "message" => $keyscanresult);
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
    }

    public function acceptKeyAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("key")) {
            if (!$this->checkPrivateKey())
                return array("status" => "failed", "message" => "SSH private key does not exist");

            $key = trim($this->request->getPost("key"));

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag->setNodes(array(
                'settings' => array(
                    'knownHosts' => $key
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            if (!file_exists('/var/dfconag/known_hosts')) {
                file_put_contents('/var/dfconag/known_hosts', $key);
            }

            return array("status" => "ok", "message" => "");
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
    }

    public function getAddOptionsAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("username") && $this->request->hasPost("password")) {

            $username = $this->request->getPost("username");
            $password = $this->request->getPost("password");

            file_put_contents('/var/run/dfconag.username', $username);
            file_put_contents('/var/run/dfconag.password', $password);

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $_dfconag = $dfconag->getNodes();
            $settings = $_dfconag['settings'];

            $params = array(
                $settings['dfmSshPort'],
                $settings['dfmHost'],
                $username,
                $password
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
                $username,
                $password,
                $mainTunnelPort,
                $dvTunnelPort
            );
            $portsResp = $this->configdRun('dfconag reserveports '.implode(' ', $params));

            if (strpos($portsResp, 'Ports reserved successfully') === false)
                return array("status" => "failed", "message" => "reserve-ports failed");

            return array("status" => "ok", "message" => $optionsJson);
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
    }


    public function registerDeviceAction() {
        if ($this->request->isPost() && $this->request->hasPost("groupId") && $this->request->hasPost("userPass")) {
            if (!$this->checkPrivateKey())
                return array("status" => "failed", "message" => "SSH private key does not exist");

            $groupId = trim($this->request->getPost("groupId"));
            $userPass = trim($this->request->getPost("userPass"));

            $username = file_get_contents('/var/run/dfconag.username');
            $password = file_get_contents('/var/run/dfconag.password');

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag = $dfconag->getNodes();
            $settings = $dfconag['settings'];

            $backend = new Backend();
            $params = array(
                $settings['dfmSshPort'],
                $settings['dfmHost'],
                $username,
                $password,
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

            if (file_exists('/var/run/dfconag.username'))
                unlink('/var/run/dfconag.username');
            if (file_exists('/var/run/dfconag.password'))
                unlink('/var/run/dfconag.password');

            $this->configdRun('dfconag restart');

            return array("status" => "ok", "message" => $obj['id']);
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
    }


    public function disconnectAction()
    {
        if ($this->request->isPost()) {
            $this->configdRun('dfconag stop');

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag->setNodes(array(
                'settings' => array(
                    'enabled' => '0',
                    'mainTunnelPort' => null,
                    'dvTunnelPort' => null,
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            if (file_exists('/var/run/dfconag.username'))
                unlink('/var/run/dfconag.username');
            if (file_exists('/var/run/dfconag.password'))
                unlink('/var/run/dfconag.password');

            $this->configdRun('template reload OPNsense/DFConAg');

            return array("status" => "ok", "message" => "");
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
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
        $dfconag = new \OPNsense\DFConAg\DFConAg();
        $settings = $dfconag->getNodes()['settings'];
        if (file_exists('/var/dfconag/key')) {
            if ((empty($settings['sshPrivateKey'])) || (empty($settings['sshPublicKey']))) {
                $dfconag->setNodes(array(
                    'settings' => array(
                        'sshPrivateKey' => file_get_contents('/var/dfconag/key'),
                        'sshPublicKey' => file_get_contents('/var/dfconag/key.pub')
                    )
                ));
                $dfconag->serializeToConfig();
                Config::getInstance()->save();
            }
        } else {
            if ((empty($settings['sshPrivateKey'])) || (empty($settings['sshPublicKey']))) {
                $this->configdRun('dfconag generatekey');
                if (!file_exists('/var/dfconag/key'))
                    return false;
                $dfconag->setNodes(array(
                    'settings' => array(
                        'sshPrivateKey' => file_get_contents('/var/dfconag/key'),
                        'sshPublicKey' => file_get_contents('/var/dfconag/key.pub')
                    )
                ));
                $dfconag->serializeToConfig();
                Config::getInstance()->save();
            } else {
                file_put_contents('/var/dfconag/key', $settings['sshPrivateKey']);
                file_put_contents('/var/dfconag/key.pub', $settings['sshPublicKey']);
                chmod('/var/dfconag/key', 0600);
            }
        }
        return (file_exists('/var/dfconag/key'));
    }
}
