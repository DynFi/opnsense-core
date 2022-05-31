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

namespace OPNsense\RPZ;

use \OPNsense\Core\Config;
use \OPNsense\Firewall\Alias;
use \OPNsense\RPZ\FilteringList;


class IndexController extends \OPNsense\Base\IndexController
{
    public function indexAction($selected = null) {
        $this->populateCategoriesIfNeeded();
        $this->populateAliasesIfNeeded();

        $this->view->selected_list = $selected;
        $this->view->formList = $this->getForm("list");
        $this->view->pick('OPNsense/RPZ/index');
    }

    private function populateCategoriesIfNeeded() { # TODO fetch real categories from somewhere
        $filteringList = new \OPNsense\RPZ\FilteringList();
        $categories = $filteringList->getNodes()['category'];
        if (empty($categories)) {
            $filteringList->setNodes(array(
                'category' => array(
                    array('name' => 'adult'),
                    array('name' => 'scam'),
                    array('name' => 'other')
                )
            ));
            $filteringList->serializeToConfig();
            Config::getInstance()->save(null, false);
        }
    }

    private function populateAliasesIfNeeded() { # TODO do not save config if aliases did not change
        $aliasModel = new \OPNsense\Firewall\Alias();
        $aliases = array();
        foreach ($aliasModel->getNodes()['aliases']['alias'] as $uuid => $alias) {
            if (($alias['type']['host']['selected']) || ($alias['type']['network']['selected'])) {
                $aliases[] = array(
                    'name' => $alias['name'],
                    'uuid' => $uuid
                );
            }
        }

        $filteringList = new \OPNsense\RPZ\FilteringList();
        $filteringList->setNodes(array('alias' => $aliases));

        $filteringList->serializeToConfig();
        Config::getInstance()->save(null, false);
    }
}
