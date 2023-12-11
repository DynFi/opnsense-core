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
class PasslistsController extends ApiMutableModelControllerBase
{

    protected static $internalModelName = 'passlist';
    protected static $internalModelClass = 'OPNsense\Suricata\Suricata';


    public function searchItemAction()
    {
        $result = $this->searchBase(
            "passlists.passlist",
            array('name', 'assigned', 'descr'),
            "passlist",
            null
        );
        return $result;
    }

    public function setItemAction($uuid)
    {
        return $this->setBase("passlist", "passlists.passlist", $uuid);
    }

    public function addItemAction()
    {
        return $this->addBase("passlist", "passlists.passlist");
    }

    public function getItemAction($uuid = null)
    {
        return $this->getBase("passlist", "passlists.passlist", $uuid);
    }

    public function getListUUIDAction($passlist)
    {
        $node = $this->getModel();
        foreach ($node->passlists->passlist->iterateItems() as $key => $list) {
            if ((string)$list->name == $passlist) {
                return array('uuid' => $key);
            }
        }
        return array();
    }

    public function delItemAction($uuid)
    {
        Config::getInstance()->lock();
        return $this->delBase("passlists.passlist", $uuid);
    }

    protected function validate($node = null, $prefix = null) {
        $result = parent::validate($node, $prefix);

        $data = $_POST['passlist'];
        $addresses = explode(',', $data['addresses']);
        $aliases_config = Config::getInstance()->toArray(array('alias' => true));

        require_once("plugins.inc.d/suricata.inc");

        foreach ($addresses as $address) {
            if (!suricata_is_valid_address($aliases_config, $address)) {
                $result["validations"]['passlist.addresses'][] = $address.' is not a valid address or alias';
                $result["result"] = 'failed';
                break;
            }
        }

        return $result;
    }
}
