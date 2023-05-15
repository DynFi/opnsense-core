<?php

/*
 * Copyright (C) 2019 Michael Muenz <m.muenz@gmail.com>
 * Copyright (C) 2020 Deciso B.V.
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

namespace OPNsense\Unbound\Api;

use OPNsense\Base\ApiMutableServiceControllerBase;
use OPNsense\Core\Backend;

class ServiceController extends ApiMutableServiceControllerBase
{
    protected static $internalServiceClass = '\OPNsense\Unbound\Unbound';
    protected static $internalServiceTemplate = 'OPNsense/Unbound/*';
    protected static $internalServiceEnabled = 'service_enabled';
    protected static $internalServiceName = 'unbound';

    public function dnsblAction()
    {
        $this->sessionClose();
        $backend = new Backend();
        $backend->configdRun('template reload ' . escapeshellarg(static::$internalServiceTemplate));
        $response = $backend->configdRun(static::$internalServiceName . ' dnsbl');
        return array('status' => $response);
    }

    public function reconfigureAction() {
        if ($this->request->isPost()) {
            $this->sessionClose();

            $model = $this->getModel();
            $backend = new Backend();

            if ((string)$model->getNodeByReference(static::$internalServiceEnabled) != '1' || $this->reconfigureForceRestart()) {
                $backend->configdRun(escapeshellarg(static::$internalServiceName) . ' stop');
            }

            $bckresult = trim($backend->configdRun('template reload OPNsense/Unbound'));
            if ($bckresult != "OK") {
                return array("status" => "failed", "message" => "generating config files failed");
            }

            require_once("util.inc");
            require_once("plugins.inc.d/unbound.inc");
            unbound_configure_do();

            if ((string)$model->getNodeByReference(static::$internalServiceEnabled) == '1') {
                $runStatus = $this->statusAction();
                if ($runStatus['status'] != 'running') {
                    $backend->configdRun(escapeshellarg(static::$internalServiceName) . ' start');
                } else {
                    $backend->configdRun(escapeshellarg(static::$internalServiceName) . ' reload');
                }
            }

            return array("status" => "ok");
        } else {
            return array('status' => 'failed');
        }
    }
}
