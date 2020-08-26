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
    protected static $internalServiceEnabled = 'general.enabled';
    protected static $internalServiceTemplate = 'OPNsense/DFConAg';
    protected static $internalServiceName = 'DFConAg';

    public function reconfigureAction()
    {
        $status = "failed";
        $message = "Only POST requests allowed";
        if ($this->request->isPost()) {
            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag = $dfconag->getNodes();
            $settings = $dfconag['settings'];

            if (!intval($settings['enabled']))
                return array("status" => "ok", "message" => "");

            if (empty($settings['dfmHost']))
                return array("status" => "failed", "message" => "Please provide DFM host");

            if (empty($settings['dfmSshPort']))
                return array("status" => "failed", "message" => "Please provide DFM SSH port");

            if (empty($settings['dfmUsername']))
                return array("status" => "failed", "message" => "Please provide DFM username");

            if (empty($settings['dfmPassword']))
                return array("status" => "failed", "message" => "Please provide DFM password");

            $backend = new Backend();
            $keyscanresult = trim($backend->configdRun('dfconag keyscan '.$settings['dfmSshPort'].' '.$settings['dfmHost']));

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
            $dfconag = new \OPNsense\DFConAg\DFConAg();
            $dfconag->setNodes(array(
                'sshKeys' => $this->request->getPost("key")
            ));
            $dfconag->serializeToConfig();
            Config::getInstance()->save();

            return array("status" => "ok", "message" => "");
        }
        return array("status" => $status, "message" => $message);
    }
}
