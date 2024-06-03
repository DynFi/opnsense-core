<?php

/**
 *    Copyright (C) 2023 DynFi
 *
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace OPNsense\Suricata\Api;

use OPNsense\Base\ApiMutableModelControllerBase;
use OPNsense\Base\UserException;
use OPNsense\Core\Backend;
use OPNsense\Core\Config;

/**
 * @package OPNsense\Suricata
 */
class SidassignController extends ApiMutableModelControllerBase
{

    protected static $internalModelName = 'interface';
    protected static $internalModelClass = 'OPNsense\Suricata\Suricata';


    public function searchItemAction()
    {
        $result = $this->searchBase(
            "interfaces.interface",
            array('iface', 'sidstateorder', 'enablesidfile', 'disablesidfile', 'modifysidfile', 'dropsidfile', 'rejectsidfile'),
            "interface",
            null
        );
        return $result;
    }

    public function setItemAction($uuid)
    {
        return $this->setBase("interface", "interfaces.interface", $uuid);
    }

    public function addItemAction()
    {
        return false;
    }

    public function getItemAction($uuid = null)
    {
        return $this->getBase("interface", "interfaces.interface", $uuid);
    }

    public function getInterfaceUUIDAction($interface)
    {
        $node = $this->getModel();
        foreach ($node->interfaces->interface->iterateItems() as $uuid => $iface) {
            if ((string)$iface->iface == $interface) {
                return array('uuid' => $uuid);
            }
        }
        return array();
    }

    public function delItemAction($uuid)
    {
        return false;
    }

    protected function validate($node = null, $prefix = null, $validateFullModel = false) {
        $result = parent::validate($node, $prefix, $validateFullModel);

        $data = $_POST['interface'];
        $curr_iface = $data['iface'];
        $curr_uuid = $node->getAttribute('uuid') ?: "";

        $exists = false;
        foreach ($this->getModel()->interfaces->interface->iterateItems() as $uuid => $iface) {
            if (((string)$iface->iface == $curr_iface) && ($uuid != $curr_uuid)) {
                $exists = true;
                break;
            }
        }
        if ($exists) {
            $result["validations"]['interface.iface'][] = 'Interface cannot be changed here';
            $result["result"] = 'failed';
        }

        return $result;
    }
}
