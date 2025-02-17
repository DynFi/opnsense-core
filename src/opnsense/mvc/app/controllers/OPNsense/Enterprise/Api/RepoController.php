<?php

/*
 * Copyright (C) 2025 DynFi
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

namespace OPNsense\Enterprise\Api;

use \OPNsense\Base\ApiMutableModelControllerBase;
use \OPNsense\Core\Backend;
use \OPNsense\Enterprise\Enterprise;
use \OPNsense\Core\Config;




/**
 * Class RepoController Handles Repo related API actions for the Enterprise module
 * @package Enterprise
 */
class RepoController extends ApiMutableModelControllerBase
{
    protected static $internalModelName = 'Enterprise';
    protected static $internalModelClass = 'OPNsense\Enterprise\Enterprise';


    public function getAction()
    {
        $result = parent::getAction();

        $backend = new Backend();
        $fwid = trim($backend->configdRun('enterprise fwid'));
        $result['Enterprise']['repo']['firewallId'] = $fwid;

        return $result;
    }

    public function setAction()
    {
        $result = parent::setAction();

        if ($result['result'] != 'failed') {
            $data = $this->request->getPost(static::$internalModelName);

            $url = $data['repo']['shopUrl'];
            $username = $data['repo']['shopUsername'];
            $password = $data['repo']['shopPassword'];
            $fwid = $data['repo']['firewallId'];

            if (!empty($url) && !empty($username) && !empty($password) && !empty($fwid))
                $result = $this->fetchCertsFromShop($url, $username, $password, $fwid);
        }

        return $result;
    }

    public function reconfigureAction()
    {
        $repoconf = new \OPNsense\Enterprise\Enterprise();
        $repo = $repoconf->getNodes()['repo'];
        $certificate = $repo['certificate'];
        $key = $repo['key'];

        if (empty($certificate))
            return array("status" => "failed", "message" => "Certificate is empty");

        if (empty($key))
            return array("status" => "failed", "message" => "Key is empty");

        $certdata = openssl_x509_parse($certificate);
        $subject = null;

        if ($certdata && $certdata['subject'])
            $subject = $certdata['subject']['CN'];

        if (empty($subject))
            return array("status" => "failed", "message" => "Certificate has no subject");

        $backend = new Backend();

        $fwid = trim($backend->configdRun('enterprise fwid'));
        $_fwid = array_shift(explode(".", $subject));
        if ($fwid != $_fwid)
            return array("status" => "failed", "message" => 'Certificate does not match this device');

        if (!is_dir('/usr/local/etc/pkg/keys/')) {
             mkdir('/usr/local/etc/pkg/keys/', 0755, true);
        }

        file_put_contents('/usr/local/etc/pkg/keys/enterprise.crt', $certificate);
        file_put_contents('/usr/local/etc/pkg/keys/enterprise.key', $key);

        $backend = new Backend();
        $fwid = trim($backend->configdRun('enterprise enablerepo'));

        return array("status" => "ok", "message" => "");
    }

    private function fetchCertsFromShop($url, $username, $password, $fwid) {
        $postdata = array(
            "username" => $username,
            "password" => $password,
            "fwid" => $fwid
        );
        $ch = curl_init(rtrim($url, '/').'/repo/getcert/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (!$response)
            return array('result'=> 'failed', 'message' => 'Can not connect to shop');
        $json = json_decode($response, true);
        if ($json['result'] == 'failed')
            return array('result'=> 'failed', 'message' => $json['message']);

        $repoconf = new \OPNsense\Enterprise\Enterprise();
        $repoconf->setNodes(array(
            'repo' => array(
                'certificate' => $json['certificate'],
                'key' => $json['key']
            )
        ));
        $repoconf->serializeToConfig();
        Config::getInstance()->save(null, false);

        return array('result'=> 'saved');
    }
}
