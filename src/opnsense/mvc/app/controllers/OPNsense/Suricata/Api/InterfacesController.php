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
class InterfacesController extends ApiMutableModelControllerBase
{

    protected static $internalModelName = 'interface';
    protected static $internalModelClass = 'OPNsense\Suricata\Suricata';


    public function searchItemAction()
    {
        $result = $this->searchBase(
            "interfaces.interface",
            array('enabled', 'iface'),
            "interface",
            null
        );

        require_once("interfaces.inc");

        foreach ($result['rows'] as &$row) {
            $row['realif'] = get_real_interface_from_config(Config::getInstance()->toArray(), strtolower($row['iface']));
        }
        return $result;
    }

    public function setItemAction($uuid)
    {
        return $this->setBase("interface", "interfaces.interface", $uuid);
    }

    public function addItemAction()
    {
        return $this->addBase("interface", "interfaces.interface");
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
        Config::getInstance()->lock();
        return $this->delBase("interfaces.interface", $uuid);
    }

    public function toggleItemAction($uuid, $enabled = null)
    {
        return $this->toggleBase("interfaces.interface", $uuid, $enabled);
    }

    public function checkRunningAction() {
        $result = array();
        $backend = new Backend();
        $node = $this->getModel();
        foreach ($node->interfaces->interface->iterateItems() as $uuid => $iface) {
            $interface = (string)$iface->iface;
            $result[$uuid] = intval(trim($backend->configdpRun("suricata isrunning $iface")));
        }
        return $result;
    }

    public function toggleAction($action, $uuid) {
        $result = array();
        $backend = new Backend();
        $interface = null;
        $node = $this->getModel();
        foreach ($node->interfaces->interface->iterateItems() as $uuid => $iface) {
            $interface = (string)$iface->iface;
            break;
        }
        if ($interface) {
            $result = trim($backend->configdpRun("suricata $action $interface"));
            if (intval($result) == 1) {
                return array('success' => 1);
            }
            return array('success' => 0, 'error' => 'Suricata toggle failed');
        }
        return array('success' => 0, 'error' => "Interface $uuid does not exists");
    }

    protected function validate($node = null, $prefix = null) {
        $result = parent::validate($node, $prefix);

        $data = $node->getNodes();
        $curr_iface = array_shift(array_keys($data['iface']));
        $curr_uuid = $node->getAttribute('uuid');

        $exists = false;
        foreach ($this->getModel()->interfaces->interface->iterateItems() as $uuid => $iface) {
            if (((string)$iface->iface == $curr_iface) && ($uuid != $curr_uuid)) {
                $exists = true;
                break;
            }
        }
        if ($exists) {
            $result["validations"]['interface.iface'][] = 'This interface is already assigned';
            $result["result"] = 'failed';
        }

        return $result;
    }

    public function reconfigureAction() {
        if ($this->request->isPost()) {
            $this->sessionClose();

            require_once("plugins.inc.d/suricata.inc");
            suricata_configure_do();

            return array("status" => "ok");
        } else {
            return array('status' => 'failed');
        }
    }
}
