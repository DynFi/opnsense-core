#!/usr/local/bin/php
<?php

/*
 *    Copyright (C) 2023 DynFi
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
 */


require_once("config.inc");
require_once("util.inc");
require_once("plugins.inc.d/suricata.inc");

use OPNsense\Core\Config;

$model = new \OPNsense\Suricata\Suricata();

$siddir = dirname(__FILE__).'/../data/samplesid';

$sidfiles = array_diff(scandir($siddir), array('..', '.'));

$existing = array();

foreach ($model->sidmods->sidmod->iterateItems() as $uuid => $sidmod) {
    $existing[] = (string)$sidmod->name;
}

$added = false;

foreach ($sidfiles as $filename) {
    if (in_array($filename, $existing))
        continue;

    $content = file_get_contents($siddir.'/'.$filename);
    $node = $model->sidmods->sidmod->add();
    $node->name = $filename;
    $node->content = $content;
    $node->modtime = time();

    $added = true;
}

if ($added) {
    $model->serializeToConfig();
    $cnf = Config::getInstance();
    $cnf->save();
}
