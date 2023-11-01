<?php

/*
 * Copyright (C) 2023 DynFi
 * Copyright (C) 2019 Deciso B.V.
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

namespace OPNsense\Suricata;

use OPNsense\Base\IndexController;
use OPNsense\Core\Config;


/**
 * @inherit
 */
class BlocksController extends IndexController
{
    public function indexAction() {
        $this->view->pick('OPNsense/Suricata/blocks');
    }

    public function downloadAction() {

        require_once("plugins.inc.d/suricata.inc");
        $suricatalogdir = SURICATALOGDIR;
        $suri_pf_table = SURICATA_PF_TABLE;

        $blocked_ips_array_save = "";
        exec("/sbin/pfctl -t {$suri_pf_table} -T show", $blocked_ips_array_save);

        if (is_array($blocked_ips_array_save) && count($blocked_ips_array_save) > 0) {
            $save_date = date("Y-m-d-H-i-s");
            $file_name = "suricata_blocked_{$save_date}.tar.gz";
            safe_mkdir("/tmp/suricata_blocked");
            file_put_contents("/tmp/suricata_blocked/suricata_block.pf", "");
            foreach($blocked_ips_array_save as $counter => $fileline) {
                if (empty($fileline))
                    continue;
                $fileline = trim($fileline, " \n\t");
                file_put_contents("/tmp/suricata_blocked/suricata_block.pf", "{$fileline}\n", FILE_APPEND);
            }

            exec("/usr/bin/tar -czf /tmp/{$file_name} -C/tmp/suricata_blocked suricata_block.pf");

            if (file_exists("/tmp/{$file_name}")) {
                ob_start();
                if (isset($_SERVER['HTTPS'])) {
                    header('Pragma: ');
                    header('Cache-Control: ');
                } else {
                    header("Pragma: private");
                    header("Cache-Control: private, must-revalidate");
                }
                header("Content-Type: application/octet-stream");
                header("Content-disposition: attachment; filename = {$file_name}");
                ob_end_clean();
                readfile("/tmp/{$file_name}");

                if (file_exists("/tmp/{$file_name}"))
                    unlink("/tmp/{$file_name}");
                rmdir_recursive("/tmp/suricata_blocked");
                exit;
            }
        }
    }
}
