#!/usr/local/bin/php
<?php

/*
<<<<<<< HEAD
 * Copyright (c) 2021-2022 Franco Fichtner <franco@opnsense.org> 
 * Copyright (c) 2022 DynFi
=======
 * Copyright (c) 2021-2023 Franco Fichtner <franco@opnsense.org>
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
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

require_once 'util.inc';

$metafile = '/usr/local/opnsense/version/core';
$licensefile = $metafile . '.license';

$ret = json_decode(@file_get_contents($metafile), true);
if ($ret != null) {
<<<<<<< HEAD
    $ret['product_version'] = trim(shell_exec('dynfi-version'));
    $ret['product_crypto'] = trim(shell_exec('opnsense-version -f'));
    $ret['product_mirror'] = preg_replace('/\/[a-z0-9]{8}(-[a-z0-9]{4}){3}-[a-z0-9]{12}\//i', '/${SUBSCRIPTION}/', trim(shell_exec('opnsense-update -M')));
    $ret['product_time'] = date('D M j H:i:s T Y', filemtime('/usr/local/opnsense/www/index.php'));
    $files = explode("\n", trim(shell_exec('egrep -l "enabled: yes" /usr/local/etc/pkg/repos/*.conf')));
    $repos = [];
    foreach ($files as $f) {
        $repos[] = trim(shell_exec('sed -n \'s/^\([^:]*\):[[:space:]]{$/\1/p\' '.$f));
    }
=======
    $ret['product_latest'] = shell_safe('/usr/local/opnsense/scripts/firmware/latest.php');
    $ret['product_mirror'] = preg_replace('/\/[a-z0-9]{8}(-[a-z0-9]{4}){3}-[a-z0-9]{12}\//i', '/${SUBSCRIPTION}/', shell_safe('opnsense-update -M'));
    $ret['product_time'] = date('D M j H:i:s T Y', filemtime('/usr/local/opnsense/www/index.php'));
    $repos = explode("\n", shell_safe('opnsense-verify -l'));
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
    sort($repos);
    $ret['product_log'] = empty(shell_safe('opnsense-update -G')) ? 0 : 1;
    $ret['product_repos'] = implode(', ', $repos);
    $ret['product_check'] = json_decode(@file_get_contents('/tmp/pkg_upgrade.json'), true);
<<<<<<< HEAD
    $ret['product_log'] = empty(trim(shell_exec('opnsense-update -G'))) ? 0 : 1;
    $ret['product_latest'] = trim(shell_exec('/usr/local/opnsense/scripts/firmware/latest.php'));
=======
    $ret['product_license'] = [];
    /* for business editions, collect license information */
    if (file_exists($licensefile)) {
        $payload = file_get_contents($licensefile);
        $payload = $payload !== false ? json_decode($payload, true) : null;
        if (is_array($payload)) {
            foreach ($payload as $key => $val) {
                $ret['product_license'][$key] = $val;
            }
        }
    }
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
    ksort($ret);
} else {
    $ret = [];
}

echo json_encode($ret, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
