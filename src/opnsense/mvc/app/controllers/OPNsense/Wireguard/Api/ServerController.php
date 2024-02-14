<?php

/*
<<<<<<< HEAD
=======
 * Copyright (C) 2023 Deciso B.V.
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
 * Copyright (C) 2018 Michael Muenz <m.muenz@gmail.com>
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

namespace OPNsense\Wireguard\Api;

use OPNsense\Base\ApiMutableModelControllerBase;
use OPNsense\Core\Backend;

class ServerController extends ApiMutableModelControllerBase
{
    protected static $internalModelName = 'server';
    protected static $internalModelClass = '\OPNsense\Wireguard\Server';

<<<<<<< HEAD
    public function searchServerAction()
    {
        $search = $this->searchBase('servers.server', array("enabled", "instance", "peers", "name", "networks", "pubkey", "port", "tunneladdress"));
        // prepend "wg" to all instance IDs to use as interface name
        foreach ($search["rows"] as $key => $server) {
            $search["rows"][$key]["interface"] = "wg" . $server["instance"];
        }
=======
    public function keyPairAction()
    {
        return json_decode((new Backend())->configdRun('wireguard gen_keypair'), true);
    }

    public function searchServerAction()
    {
        $search = $this->searchBase(
            'servers.server',
            ["enabled", "instance", "peers", "name", "networks", "pubkey", "port", "tunneladdress", 'interface']
        );
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
        return $search;
    }

    public function getServerAction($uuid = null)
    {
<<<<<<< HEAD
        $this->sessionClose();
=======
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
        return $this->getBase('server', 'servers.server', $uuid);
    }

    public function addServerAction($uuid = null)
    {
<<<<<<< HEAD
        if ($this->request->isPost() && $this->request->hasPost("server")) {
            if ($uuid != null) {
                $node = $this->getModel()->getNodeByReference('servers.server.' . $uuid);
            } else {
                $node = $this->getModel()->servers->server->Add();
            }
            $node->setNodes($this->request->getPost("server"));
            if (empty((string)$node->pubkey) && empty((string)$node->privkey)) {
                // generate new keypair
                $backend = new Backend();
                $keyspriv = $backend->configdpRun("wireguard genkey", 'private');
                $keyspub = $backend->configdpRun("wireguard genkey", 'public');
                $node->privkey = trim($keyspriv);
                $node->pubkey = trim($keyspub);
            }
            return $this->validateAndSave($node, 'server');
        }
        return array("result" => "failed");
=======
        return $this->addBase('server', 'servers.server', $uuid);
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
    }

    public function delServerAction($uuid)
    {
        return $this->delBase('servers.server', $uuid);
    }

    public function setServerAction($uuid = null)
    {
<<<<<<< HEAD
        if ($this->request->isPost() && $this->request->hasPost("server")) {
            if ($uuid != null) {
                $node = $this->getModel()->getNodeByReference('servers.server.' . $uuid);
            } else {
                $node = $this->getModel()->servers->server->Add();
            }
            $node->setNodes($this->request->getPost("server"));
            if (empty((string)$node->pubkey) && empty((string)$node->privkey)) {
                // generate new keypair
                $backend = new Backend();
                $keyspriv = $backend->configdpRun("wireguard genkey", 'private');
                $keyspub = $backend->configdpRun("wireguard genkey", 'public');
                $node->privkey = trim($keyspriv);
                $node->pubkey = trim($keyspub);
            }
            return $this->validateAndSave($node, 'server');
        }
        return array("result" => "failed");
=======
        return $this->setBase('server', 'servers.server', $uuid);
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
    }

    public function toggleServerAction($uuid)
    {
        return $this->toggleBase('servers.server', $uuid);
    }
}
