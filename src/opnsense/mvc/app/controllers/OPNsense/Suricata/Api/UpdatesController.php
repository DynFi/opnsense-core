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
class UpdatesController extends ApiMutableModelControllerBase
{

    protected static $internalModelName = 'global';
    protected static $internalModelClass = 'OPNsense\Suricata\Suricata';

    public function updateAction($mode)
    {
        if ($mode == 'force') {
            require_once("plugins.inc.d/suricata.inc");
            $suricatadir = SURICATADIR;
            $this->unlinkIfExists("{$suricatadir}{$emergingthreats_filename}.md5");
            $this->unlinkIfExists("{$suricatadir}{$snort_community_rules_filename}.md5");
            $this->unlinkIfExists("{$suricatadir}{$snort_rules_file}.md5");
            $this->unlinkIfExists("{$suricatadir}{$feodotracker_rules_filename}.md5");
            $this->unlinkIfExists("{$suricatadir}{$sslbl_rules_filename}.md5");
            $this->unlinkIfExists("{$suricatadir}" . EXTRARULE_FILE_PREFIX . "*.md5");
        }

        $backend = new Backend();
        $result = $backend->configdpRun("suricata updaterules");

        return array('result' => $result);
    }

    public function logAction($startfrom)
    {
        require_once("plugins.inc.d/suricata.inc");
        $suricata_rules_upd_log = SURICATA_RULES_UPD_LOGFILE;

        $log = "";
        $result = "";
        if (file_exists("{$suricata_rules_upd_log}")) {
            if (filesize("{$suricata_rules_upd_log}") > 0) {
                $log = file_get_contents($suricata_rules_upd_log);
            }
        }
        if (!empty($log)) {
            $arr = explode("\n", $log);
            $narr = array_slice($arr, $startfrom);
            $result = implode("\n", $narr);
        }

        return array('result' => $result);
    }

    public function clearlogAction()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once("plugins.inc.d/suricata.inc");
            $suricata_rules_upd_log = SURICATA_RULES_UPD_LOGFILE;

            if (file_exists("{$suricata_rules_upd_log}")) {
                if (filesize("{$suricata_rules_upd_log}") > 0) {
                    file_put_contents(SURICATA_RULES_UPD_LOGFILE, "");
                }
            }

            return array('result' => 'success');
        }
        return array('result' => 'failed');
    }

    private function unlinkIfExists($path) {
        if (file_exists($path))
            unlink($path);
    }
}
