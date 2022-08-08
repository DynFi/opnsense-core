<?php

/*
 * Copyright (C) 2022 DynFi
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

namespace OPNsense\RPZ\FieldTypes;

use OPNsense\Base\FieldTypes\BaseListField;
use OPNsense\Core\Config;
use OPNsense\Firewall\Alias;

class RpzAliasField extends BaseListField
{
    private static $internalStaticOptionList = array();

    protected function actionPostLoadingEvent()
    {
        if (empty(self::$internalStaticOptionList)) {
            self::$internalStaticOptionList = array();
            $configObj = Config::getInstance()->object();
            foreach ($configObj->interfaces->children() as $ifname => $ifdetail) {
                $descr = htmlspecialchars(!empty($ifdetail->descr) ? $ifdetail->descr : strtoupper($ifname));
                if (ip2long($ifdetail->ipaddr) !== false)
                    self::$internalStaticOptionList[$ifname] = $descr;
            }
            $aliasMdl = new Alias();
            foreach ($aliasMdl->aliases->alias->iterateItems() as $alias) {
                if (strpos((string)$alias->type, "port") === false) {
                    self::$internalStaticOptionList[(string)$alias->name] = (string)$alias->name;
                }
            }
        }
        $this->internalOptionList = self::$internalStaticOptionList;
    }
}
