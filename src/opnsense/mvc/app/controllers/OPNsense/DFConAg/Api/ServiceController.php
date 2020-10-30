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

require_once('auth.inc');
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
        if ($this->request->isPost()) {
            $dfmHost = trim($this->request->getPost("dfmHost"));
            $dfmSshPort = intval($this->request->getPost("dfmPort"));
            $dfmJwt = trim($this->request->getPost("dfmJwt"), " \n\r");

            if (!empty($dfmJwt)) {
                $jwtData = $this->decodeJwt($dfmJwt);
                if (!$jwtData)
                    return array("status" => "failed", "message" => "JWT decoding failed: ".$dfmJwt);

                $dfmHost = $jwtData['serverAddress'];
                $dfmSshPort = $jwtData['serverPort'];
            }

            if (empty($dfmHost))
                return array("status" => "failed", "message" => "Please provide DynFi Manager host address");

            if (!$dfmSshPort)
                return array("status" => "failed", "message" => "Please provide DynFi Manager SSH port");

            if (!$this->checkPrivateKey())
                return array("status" => "failed", "message" => "SSH private key does not exist");

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $settings = $dfconag->getNodes()['settings'];

            if (($settings['dfmHost'] == $dfmHost) && ($settings['dfmSshPort'] == $dfmSshPort) && (!empty($settings['mainTunnelPort'])) && (!empty($settings['dvTunnelPort']))) {

                $params = array(
                    $settings['dfmSshPort'],
                    $settings['dfmHost']
                );
                $whoResp = $this->configdRun('dfconag whoami '.implode(' ', $params));

                if (empty($whoResp))
                    return array("status" => "failed", "message" => "who-am-i failed");

                $obj = json_decode($whoResp, true);
                if (!empty($obj)) {
                    $dfconag->setNodes(array(
                        'settings' => array(
                            'enabled' => '1',
                            'deviceId' => $obj['id']
                        )
                    ));
                    $dfconag->serializeToConfig();
                    Config::getInstance()->save();

                    $this->configdRun('dfconag restart');

                    return array("status" => "ok", "message" => 'RECONNECTED;'.$obj['id']);
                }
            }

            $keyscanresult = trim($this->configdRun('dfconag keyscan '.$dfmSshPort.' '.$dfmHost));
            if (empty($keyscanresult))
                return array("status" => "failed", "message" => "SSH key scan failed");

            $keyscanArray = explode("#hashed#", $keyscanresult);
            if ((empty($keyscanArray[0])) || (count($keyscanArray) == 0))
                return array("status" => "failed", "message" => "SSH key scan failed: ".$keyscanresult);

            $dfconag->setNodes(array(
                'settings' => array(
                    'enabled' => '1',
                    'dfmHost' => $dfmHost,
                    'dfmSshPort' => $dfmSshPort
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            $keyscanArray[0] = trim($keyscanArray[0], " \t\n\r");
            $keyscanArray[1] = trim($keyscanArray[1], " \t\n\r");

            if ((isset($settings['knownHostsNotHashed'])) && ($keyscanArray[0] == trim($settings['knownHostsNotHashed']))) {
                if (!file_exists('/var/dfconag/known_hosts')) {
                    file_put_contents('/var/dfconag/known_hosts', $keyscanArray[1]);
                }
                return array("status" => "ok", "message" => 'CONFIRMED');
            }

            $this->session->set("dfmKnownHostsNotHashed", $keyscanArray[0]);
            $this->session->set("dfmKnownHosts", $keyscanArray[1]);

            return array("status" => "ok", "message" => $keyscanArray[0]);
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
    }

    public function acceptKeyAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("key")) {
            if (!$this->checkPrivateKey())
                return array("status" => "failed", "message" => "SSH private key does not exist");

            $knownHosts = $this->session->get("dfmKnownHosts");
            $knownHostsNotHashed = $this->session->get("dfmKnownHostsNotHashed");

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag->setNodes(array(
                'settings' => array(
                    'knownHosts' => $knownHosts,
                    'knownHostsNotHashed' => $knownHostsNotHashed,
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            if (!file_exists('/var/dfconag/known_hosts')) {
                file_put_contents('/var/dfconag/known_hosts', $knownHosts);
            }

            $this->session->remove("dfmKnownHosts");
            $this->session->remove("dfmKnownHostsNotHashed");

            return array("status" => "ok", "message" => "");
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
    }

    public function getAddOptionsAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("username") && $this->request->hasPost("password")) {

            $username = $this->request->getPost("username");
            $password = $this->request->getPost("password");

            $this->session->set("dfmUsername", $username);
            $this->session->set("dfmPassword", $password);

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
                return array("status" => "failed", "message" => "getaddoptions failed");

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
                return array("status" => "failed", "message" => "reserveports failed: ".$portsResp);

            $options['usernames'] = array();
            foreach (config_read_array('system', 'user') as &$u) {
                $g = local_user_get_groups($u);
                if (in_array('admins', $g))
                    $options['usernames'][] = $u['name'];
            }
            $optionsJson = json_encode($options);

            return array("status" => "ok", "message" => $optionsJson);
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
    }


    public function registerDeviceAction() {
        global $config;

        if ($this->request->isPost() && $this->request->hasPost("groupId") && $this->request->hasPost("userName") && $this->request->hasPost("userPass")) {
            if (!$this->checkPrivateKey())
                return array("status" => "failed", "message" => "SSH private key does not exist");

            $groupId = trim($this->request->getPost("groupId"));
            $userName = trim($this->request->getPost("userName"));
            $secret = trim($this->request->getPost("userPass"));
            $authType = 'password';

            $username = $this->session->get("dfmUsername");
            $password = $this->session->get("dfmPassword");

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag = $dfconag->getNodes();
            $settings = $dfconag['settings'];

            $publicKey = null;
            if (empty($secret)) {
                exec("ssh-keygen -m PEM -q -t rsa -N '' -C \"dfconag@`hostname`\" -f /tmp/tmpkey");
                if ((!file_exists('/tmp/tmpkey')) || (!file_exists('/tmp/tmpkey.pub')))
                    return array("status" => "failed", "message" => "SSH keys generation failed");
                $authType = 'key';
                $secret = file_get_contents('/tmp/tmpkey');
                $publicKey = file_get_contents('/tmp/tmpkey.pub');
                unlink('/tmp/tmpkey');
                unlink('/tmp/tmpkey.pub');
            }

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            if (!empty($publicKey)) {
                $dfconag->setNodes(array(
                    'settings' => array(
                        'authorizedUser' => $userName,
                        'authorizedKey' => $publicKey
                    )
                ));
                $dfconag->serializeToConfig();
                Config::getInstance()->save();

                $this->checkAuthorizedKeys($userName, $publicKey);
            }

            $jsondata = array(
                'username' => $username,
                'password' => $password,
                'deviceGroup' => $groupId,
                'sshConfig' => array(
                    'username' => $userName,
                    'authType' => $authType,
                    'secret' => $secret
                )
            );

            $addResp = $this->configdRun('dfconag addme '.base64_encode(json_encode($jsondata)));

            if (empty($addResp))
                return array("status" => "failed", "message" => "add-me failed");

            $obj = json_decode($addResp, true);
            if (empty($obj) || empty($obj['id']))
                return array("status" => "failed", "message" => "add-me failed");

            $dfconag->setNodes(array(
                'settings' => array(
                    'deviceId' => $obj['id']
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            $this->session->remove("dfmUsername");
            $this->session->remove("dfmPassword");

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
                    'authorizedUser' => '',
                    'authorizedKey' => ''
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            if (file_exists('/var/dfconag/known_hosts'))
                unlink('/var/dfconag/known_hosts');

            $this->configdRun('dfconag stop');

            return array("status" => "ok", "message" => "");
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
    }


    public function checkStatusAction() {
        global $config;
        if ($this->request->isPost()) {

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $settings = $dfconag->getNodes()['settings'];

            $settings['remoteSshPort'] = (!empty($config['system']['ssh']['port'])) ? $config['system']['ssh']['port'] : 22;
            $settings['remoteDvPort'] = (!empty($config['system']['webgui']['port'])) ? $config['system']['webgui']['port'] : ($config['system']['webgui']['protocol'] == 'https' ? 443 : 80);

            return array("status" => "ok", "message" => json_encode($settings));
        }
        return array("status" => "failed", "message" => "Only POST requests allowed");
    }


    public function resetAction() {
        if ($this->request->isPost()) {

            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag->setNodes(array(
                'settings' => array(
                    'enabled' => '0',
                    'dfmHost' => '',
                    'dfmSshPort' => '',
                    'knownHosts' => '',
                    'authorizedUser' => '',
                    'authorizedKey' => '',
                    'mainTunnelPort' => '',
                    'dvTunnelPort' => '',
                    'deviceId' => '',
                    'sshPrivateKey' => '',
                    'sshPublicKey' => ''
                )
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            if (file_exists('/var/dfconag/known_hosts'))
                unlink('/var/dfconag/known_hosts');
            if (file_exists('/var/dfconag/key'))
                unlink('/var/dfconag/key');
            if (file_exists('/var/dfconag/key.pub'))
                unlink('/var/dfconag/key.pub');

             $this->configdRun('dfconag stop');

            return array("status" => "ok", "message" => "");
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


    private function checkAuthorizedKeys($username, $key) {
        global $config;
        if (is_array($config['system']['user'])) {
            foreach ($config['system']['user'] as &$user) {
                if ($user['name'] == $username) {
                    $keys = (isset($user['authorizedkeys'])) ? base64_decode($user['authorizedkeys']) : '';
                    if (strpos($keys, $key) === false) {
                        $olines = explode("\n", $keys);
                        $nlines = array();
                        foreach ($olines as $line) {
                            if (strpos($line, "dfconag@") === false) {
                                $nlines[] = trim($line);
                            }
                        }
                        $nlines[] = trim($key);
                        $user['authorizedkeys'] = base64_encode(implode("\r\n", $nlines));
                        local_user_set($user);
                        write_config();
                    }
                    break;
                }
            }
        }
    }


    private function decodeJwt($jwt) {
        $arr = explode('.', $jwt);
        if (count($arr) != 3)
            return null;

        // $head = $this->_decodeJwtSegment($arr[0]);
        $payload = $this->_decodeJwtSegment($arr[1]);
        // $crypto = $this->_decodeJwtSegment($arr[2]);

        return $payload;
    }


    function _decodeJwtSegment($dataEnc) {
        $r = strlen($dataEnc) % 4;
        if ($r) {
            $dataEnc .= str_repeat('=', (4 - $r));
        }
        $data = base64_decode(strtr($dataEnc, '-_', '+/'));
        return json_decode($data, true);

    }
}
