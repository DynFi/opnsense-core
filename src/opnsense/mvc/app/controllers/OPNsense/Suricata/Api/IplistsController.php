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
class IplistsController extends ApiMutableModelControllerBase
{

    protected static $internalModelName = 'iplist';
    protected static $internalModelClass = 'OPNsense\Suricata\Suricata';


    public function searchItemAction()
    {
        $result = $this->searchBase(
            "iplists.iplist",
            array('name', 'content'),
            "iplist",
            null
        );

        require_once("plugins.inc.d/suricata.inc");
        $iprep_path = SURICATA_IPREP_PATH;

        foreach ($result['rows'] as &$row) {
            $file = $row['name'];
            if (!file_exists("{$iprep_path}{$file}")) {
                $data = str_replace("\r\n", "\n", $row['content']);
                file_put_contents_with_mkdir("{$iprep_path}{$file}", $data);
            }
            $row['modified'] = date('M-d Y g:i a', filemtime("{$iprep_path}{$file}"));
            $row['size'] = format_bytes(filesize("{$iprep_path}{$file}"));
        }

        return $result;
    }

    public function setItemAction($uuid)
    {
        $res = $this->setBase("iplist", "iplists.iplist", $uuid);
        $item = $this->getBase("iplist", "iplists.iplist", $uuid);
        if ($item) {
            require_once("plugins.inc.d/suricata.inc");
            $iprep_path = SURICATA_IPREP_PATH;
            $filename = $item['iplist']['name'];
            $data = str_replace("\r\n", "\n", $item['iplist']['content']);
            file_put_contents_with_mkdir($iprep_path.$filename, $data);
        }
        return $res;
    }

    public function addItemAction()
    {
        return $this->addBase("iplist", "iplists.iplist");
    }

    public function getItemAction($uuid = null)
    {
        return $this->getBase("iplist", "iplists.iplist", $uuid);
    }

    public function getListUUIDAction($iplist)
    {
        $node = $this->getModel();
        foreach ($node->iplists->iplist->iterateItems() as $key => $list) {
            if ((string)$list->name == $iplist) {
                return array('uuid' => $key);
            }
        }
        return array();
    }

    public function delItemAction($uuid)
    {
        $item = $this->getBase("iplist", "iplists.iplist", $uuid);
        if ($item) {
            require_once("plugins.inc.d/suricata.inc");
            $iprep_path = SURICATA_IPREP_PATH;
            $filename = $item['iplist']['name'];
            if (file_exists($iprep_path.$filename)) {
                unlink($iprep_path.$filename);
            }
        }
        Config::getInstance()->lock();
        return $this->delBase("iplists.iplist", $uuid);
    }
}
