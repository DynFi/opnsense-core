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

namespace OPNsense\DFM\Api;

use \OPNsense\Base\ApiMutableServiceControllerBase;
use \OPNsense\Core\Backend;
use \OPNsense\DFM\DFM;

/**
 * Class ServiceController
 * @package OPNsense\IDS
 */
class ServiceController extends ApiMutableServiceControllerBase
{
    protected static $internalServiceClass = '\OPNsense\DFM\DFM';
    protected static $internalServiceEnabled = 'general.enabled';
    protected static $internalServiceTemplate = 'OPNsense/DFM';
    protected static $internalServiceName = 'dfm';

    /**
     * Reconfigure IDS
     * @return array result status
     * @throws \Exception when configd action fails
     * @throws \OPNsense\Base\ModelException when unable to construct model
     * @throws \Phalcon\Validation\Exception when one or more model validations fail
     */
    public function reconfigureAction()
    {
        $status = "failed";
        $message = "Unknown error";
        if ($this->request->isPost()) {
            $this->sessionClose();
            $fw = new \OPNsense\Firewall\Plugin();
            // TODO change to service
            foreach ($this->getModel()->settings->interfaces->getNodeData() as $iface => $info) {
                if (intval($info['selected'])) {
                    foreach ($this->getModel()->settings->managerIps->getNodeData() as $ip => $ipinfo) {
                        if (intval($ipinfo['selected'])) {
                            $fw->registerFilterRule(1, array(
                                    'interface' => $iface,
                                    'from' => $ip,
                                    'direction' => 'in',
                                    'ipprotocol' => 'inet',
                                    'descr' => "Allow DFM from ".$ip,
                                ),
                                array(
                                    'type' => 'pass',
                                    'log' => true,
                                    'disablereplyto' => 1
                                )
                            );
                        }
                    }
                }
            }
            $backend = new Backend();
            $bckresult = trim($backend->configdRun('filter reload'));
            if ($bckresult == "OK") {
                return array("status" => "ok", "message" => "");
            }
            return array("status" => "ok", "message" => "configd filter reload failed");
        }
        return array("status" => $status, "message" => $message);
    }
}
