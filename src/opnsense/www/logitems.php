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

function getLogItems($breadcrumbs) {
  if (count($breadcrumbs) > 1) {
    if ($breadcrumbs[0]['name'] == 'System') {
        if ($breadcrumbs[1]['name'] == 'Settings') {
            return array(
                array('name' => 'Backend', 'url' => '/configd_logs.php'),
                array('name' => 'General', 'url' => '/diag_logs.php'),
                array('name' => 'Web GUI', 'url' => '/httpd_logs.php')
            );
        }
        if ($breadcrumbs[1]['name'] == 'Access') {
            return array(
                array('name' => 'Backend', 'url' => '/configd_logs.php'),
                array('name' => 'General', 'url' => '/diag_logs.php'),
                array('name' => 'Web GUI', 'url' => '/httpd_logs.php')
            );
        }
        if ($breadcrumbs[1]['name'] == 'Firmware') {
            return array(
                array('name' => 'Firmware', 'url' => '/diag_logs_pkg.php')
            );
        }
        if ($breadcrumbs[1]['name'] == 'Gateways') {
            return array(
                array('name' => 'Gateways', 'url' => '/diag_logs_gateways.php')
            );
        }
        if ($breadcrumbs[1]['name'] == 'Routes') {
            return array(
                array('name' => 'Routes', 'url' => '/diag_logs_routing.php')
            );
        }
    }
  }
  return array();
}
