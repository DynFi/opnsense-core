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

    public function reconfigureAction()
    {
        $repoconf = new \OPNsense\Enterprise\Enterprise();
        $certificate = $repoconf->getNodes()['repo']['certificate'];

        if (empty($certificate))
            return array("status" => "failed", "message" => "Certificate is empty");

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

        return array("status" => "ok", "message" => "");

        $backend = new Backend();
        $res = trim($backend->configdRun('enterprise synccerts'));

        if ($res == "OK") {
            return array("status" => "ok", "message" => "");
        }

        return array("status" => "failed", "message" => $res);
    }
}
