<?php

/*
 * Copyright (C) 2020 Dawid Kujawa <dawid.kujawa@dynfi.com>
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

define('LOG_ITEM_DEFS', [
    'System' => [
        'Access' => [
            ['name' => 'General', 'url' => '/diag_logs.php']
        ],
        'Firmware' => [
            ['name' => 'Firmware', 'url' => '/diag_logs_pkg.php']
        ],
        'Gateways' => [
            ['name' => 'Gateways', 'url' => '/diag_logs_gateways.php']
        ],
        'High Availability' => [
            ['name' => 'General', 'url' => '/diag_logs.php']
        ],
        'Routes' => [
            ['name' => 'Routes', 'url' => '/diag_logs_routing.php']
        ]
        'Settings' => [
            ['name' => 'Backend', 'url' => '/configd_logs.php'],
            ['name' => 'General', 'url' => '/diag_logs.php'],
            ['name' => 'Web GUI', 'url' => '/httpd_logs.php']
        ],
        'Trust' => [
            ['name' => 'General', 'url' => '/diag_logs.php']
        ],
        'Diagnostics' => [
            ['name' => 'General', 'url' => '/diag_logs.php']
        ]
    ]
]);

function getLogItems($breadcrumbs) {
  if (count($breadcrumbs) >= 2) {
    $main = $breadcrumbs[0]['name'];
    $sub = $breadcrumbs[1]['name'];
    if (isset(LOG_ITEM_DEFS[$main])) {
      if (isset(LOG_ITEM_DEFS[$main][$sub])) {
        return LOG_ITEM_DEFS[$main][$sub];
      }
    }
  }
  return [];
}
