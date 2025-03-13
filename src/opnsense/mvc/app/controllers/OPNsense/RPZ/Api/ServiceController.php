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

namespace OPNsense\RPZ\Api;

use OPNsense\Base\ApiMutableServiceControllerBase;
use OPNsense\Core\Backend;

class ServiceController extends ApiMutableServiceControllerBase
{
    protected static $internalServiceClass = '\OPNsense\Unbound\Unbound';
    protected static $internalServiceTemplate = 'OPNsense/Unbound';
    protected static $internalServiceEnabled = 'general.enabled';
    protected static $internalServiceName = 'unbound';

    public function reconfigureAction() {
        $this->sessionClose();

        $backend = new Backend();
        $backend->configdRun('dns reload');

        if ($this->request->isPost()) {
            $restart = $this->reconfigureForceRestart();
            $enabled = $this->serviceEnabled();
            $backend = new Backend();

            if ($restart || !$enabled) {
                $backend->configdRun(escapeshellarg(static::$internalServiceName) . ' stop');
            }

            if ($this->invokeInterfaceRegistration()) {
                $backend->configdRun('interface invoke registration');
            }

            if (!empty(static::$internalServiceTemplate)) {
                $result = trim($backend->configdpRun('template reload', [static::$internalServiceTemplate]) ?? '');
                if ($result !== 'OK') {
                    throw new UserException(sprintf(
                        gettext('Template generation failed for internal service "%s". See backend log for details.'),
                        static::$internalServiceName
                    ), gettext('Configuration exception'));
                }
            }

            if ($enabled) {
                if ($restart || $this->statusAction()['status'] != 'running') {
                    $backend->configdRun(escapeshellarg(static::$internalServiceName) . ' start');
                } else {
                    $backend->configdRun(escapeshellarg(static::$internalServiceName) . ' reload');
                }
            }

            $backend->configdRun('dhcpd restart');

            return ['status' => 'ok'];
        }

        return ['status' => 'failed'];
    }

    public function rpzFileStatsAction() {
        $backend = new Backend();
        $result = trim($backend->configdRun('rpz rpzfilestats'));
        $ret = array();
        foreach (preg_split("/\r\n|\n|\r/", $result) as $l) {
            $arr = explode(':', $l);
            if (count($arr) == 2) {
                $ret[$arr[0]] = $arr[1];
            }
        }
        return $ret;
    }
}
