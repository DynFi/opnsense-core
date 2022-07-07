<?php

/**
 *    Copyright (C) 2022 DynFi
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

namespace OPNsense\RPZ\Api;

use OPNsense\Base\ApiMutableModelControllerBase;
use OPNsense\Base\UserException;
use OPNsense\Core\Backend;
use OPNsense\Core\Config;

/**
 * @package OPNsense\RPZ
 */
class WhiteController extends ApiMutableModelControllerBase
{

    protected static $internalModelName = 'entry';
    protected static $internalModelClass = 'OPNsense\RPZ\WhiteList';


    public function searchItemAction()
    {
        return $this->searchBase(
            "entries.entry",
            array('domain'),
            "domain",
            null
        );
    }

    public function setItemAction($uuid)
    {
        return $this->setBase("entry", "entries.entry", $uuid);
    }

    public function addItemAction()
    {
        return $this->addBase("entry", "entries.entry");
    }

    public function getItemAction($uuid = null)
    {
        return $this->getBase("entry", "entries.entry", $uuid);
    }

    public function getEntryUUIDAction($name)
    {
        $node = $this->getModel();
        foreach ($node->entries->entry->iterateItems() as $key => $entry) {
            if ((string)$entry->name == $name) {
                return array('uuid' => $key);
            }
        }
        return array();
    }

    public function delItemAction($uuid)
    {
        Config::getInstance()->lock();
        return $this->delBase("entries.entry", $uuid);
    }

    public function toggleItemAction($uuid, $enabled = null)
    {
        return $this->toggleBase("entries.entry", $uuid, $enabled);
    }
}
