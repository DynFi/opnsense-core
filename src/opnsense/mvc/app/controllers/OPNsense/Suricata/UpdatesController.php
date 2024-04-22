<?php

/*
 * Copyright (C) 2023 DynFi
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

use OPNsense\Core\Config;


class UpdatesController extends \OPNsense\Base\IndexController
{
    public function indexAction() {
        $config = Config::getInstance()->toArray();

        require_once("plugins.inc.d/suricata.inc");

        $rulesTable = array();

        $suricatadir = SURICATADIR;
        $suricata_rules_upd_log = SURICATA_RULES_UPD_LOGFILE;

        /* ET */

        $emergingthreats = ($config['OPNsense']['Suricata']['global']['enableetopenrules'] == '1');
        $etpro = ($config['OPNsense']['Suricata']['global']['enableetprorules'] == '1');

        if ($etpro) {
            $et_name = "Emerging Threats Pro Rules";
            if ($config['OPNsense']['Suricata']['global']['enableetprocustomurl'] == '1') {
                $emergingthreats_filename = trim(substr($config['OPNsense']['Suricata']['global']['etprocustomruleurl'], strrpos($config['OPNsense']['Suricata']['global']['etprocustomruleurl'], '/') + 1));
            }
            else {
                $emergingthreats_filename = ETPRO_DNLD_FILENAME;
            }
        } else {
            $et_name = "Emerging Threats Open Rules";
            if ($config['OPNsense']['Suricata']['global']['enableetopencustomurl'] == '1') {
                $emergingthreats_filename = trim(substr($config['OPNsense']['Suricata']['global']['enableetopencustomurl'], strrpos($config['OPNsense']['Suricata']['global']['enableetopencustomurl'], '/') + 1));
            }
            else {
                $emergingthreats_filename = ET_DNLD_FILENAME;
            }
        }
        if (($etpro) || ($emergingthreats)) {
            $emergingt_net_sig_chk_local = 'Not Downloaded';
            $emergingt_net_sig_date = 'Not Downloaded';
        }
        else {
            $emergingt_net_sig_chk_local = 'Not Enabled';
            $emergingt_net_sig_date = 'Not Enabled';
        }
        if ((($etpro) || ($emergingthreats)) && file_exists("{$suricatadir}{$emergingthreats_filename}.md5")) {
            $emergingt_net_sig_chk_local = file_get_contents("{$suricatadir}{$emergingthreats_filename}.md5");
            $emergingt_net_sig_date = date(DATE_RFC850, filemtime("{$suricatadir}{$emergingthreats_filename}.md5"));
        }

        $rulesTable[] = array(
            'name' => $et_name,
            'sighash' => $emergingt_net_sig_chk_local,
            'sigdate' => $emergingt_net_sig_date,
        );

        /* Snort Subscriber Rules */

        $snortdownload = ($config['OPNsense']['Suricata']['global']['enablevrtrules'] == '1');
        if ($config['OPNsense']['Suricata']['global']['enablesnortcustomurl'] == '1') {
            $snort_rules_file = trim(substr($config['OPNsense']['Suricata']['global']['snortcustomurl'], strrpos($config['OPNsense']['Suricata']['global']['snortcustomurl'], '/') + 1));
        } else {
            $snort_rules_file = $config['OPNsense']['Suricata']['global']['snortrulesfile'];
        }

        if ($snortdownload) {
            $snort_org_sig_chk_local = 'Not Downloaded';
            $snort_org_sig_date = 'Not Downloaded';
        } else {
            $snort_org_sig_chk_local = 'Not Enabled';
            $snort_org_sig_date = 'Not Enabled';
        }
        if ($snortdownload && file_exists("{$suricatadir}{$snort_rules_file}.md5")){
            $snort_org_sig_chk_local = file_get_contents("{$suricatadir}{$snort_rules_file}.md5");
            $snort_org_sig_date = date(DATE_RFC850, filemtime("{$suricatadir}{$snort_rules_file}.md5"));
        }

        $rulesTable[] = array(
            'name' => 'Snort Subscriber Rules',
            'sighash' => $snort_org_sig_chk_local,
            'sigdate' => $snort_org_sig_date,
        );

        /* Snort GPLv2 Community Rules */

        $snortcommunityrules = ($config['OPNsense']['Suricata']['global']['snortcommunityrules'] == '1');

        if ($snortcommunityrules) {
            $snort_community_sig_chk_local = 'Not Downloaded';
            $snort_community_sig_sig_date = 'Not Downloaded';
        } else {
            $snort_community_sig_chk_local = 'Not Enabled';
            $snort_community_sig_sig_date = 'Not Enabled';
        }

        if ($config['OPNsense']['Suricata']['global']['enablegplv2customurl'] == '1') {
            $snort_community_rules_filename = trim(substr($config['OPNsense']['Suricata']['global']['gplv2customurl'], strrpos($config['OPNsense']['Suricata']['global']['gplv2customurl'], '/') + 1));
        } else {
            $snort_community_rules_filename = GPLV2_DNLD_FILENAME;
        }

        if ($snortcommunityrules && file_exists("{$suricatadir}{$snort_community_rules_filename}.md5")) {
            $snort_community_sig_chk_local = file_get_contents("{$suricatadir}{$snort_community_rules_filename}.md5");
            $snort_community_sig_sig_date = date(DATE_RFC850, filemtime("{$suricatadir}{$snort_community_rules_filename}.md5"));
        }

        $rulesTable[] = array(
            'name' => 'Snort GPLv2 Community Rules',
            'sighash' => $snort_community_sig_chk_local,
            'sigdate' => $snort_community_sig_sig_date,
        );

        /* Feodo Tracker Botnet C2 IP Rules */

        $feodotracker_rules = ($config['OPNsense']['Suricata']['global']['enablefeodobotnetc2rules'] == '1');

        if ($feodotracker_rules) {
            $feodotracker_sig_chk_local = 'Not Downloaded';
            $feodotracker_sig_sig_date = 'Not Downloaded';
        } else {
            $feodotracker_sig_chk_local = 'Not Enabled';
            $feodotracker_sig_sig_date = 'Not Enabled';
        }
        $feodotracker_rules_filename = FEODO_TRACKER_DNLD_FILENAME;
        if ($feodotracker_rules && file_exists("{$suricatadir}{$feodotracker_rules_filename}.md5")) {
            $feodotracker_sig_chk_local = file_get_contents("{$suricatadir}{$feodotracker_rules_filename}.md5");
            $feodotracker_sig_sig_date = date(DATE_RFC850, filemtime("{$suricatadir}{$feodotracker_rules_filename}.md5"));
        }

        $rulesTable[] = array(
            'name' => 'Feodo Tracker Botnet C2 IP Rules',
            'sighash' => $feodotracker_sig_chk_local,
            'sigdate' => $feodotracker_sig_sig_date,
        );

        /* ABUSE.ch SSL Blacklist Rules */

        $sslbl_rules = ($config['OPNsense']['Suricata']['global']['enableabusesslblacklistrules'] == '1');

        if ($sslbl_rules) {
            $sslbl_sig_chk_local = 'Not Downloaded';
            $sslbl_sig_sig_date = 'Not Downloaded';
        } else {
            $sslbl_sig_chk_local = 'Not Enabled';
            $sslbl_sig_sig_date = 'Not Enabled';
        }
        $sslbl_rules_filename = ABUSE_SSLBL_DNLD_FILENAME;
        if ($sslbl_rules && file_exists("{$suricatadir}{$sslbl_rules_filename}.md5")) {
            $sslbl_sig_chk_local = file_get_contents("{$suricatadir}{$sslbl_rules_filename}.md5");
            $sslbl_sig_sig_date = date(DATE_RFC850, filemtime("{$suricatadir}{$sslbl_rules_filename}.md5"));
        }

        $rulesTable[] = array(
            'name' => 'ABUSE.ch SSL Blacklist Rules',
            'sighash' => $sslbl_sig_chk_local,
            'sigdate' => $sslbl_sig_sig_date,
        );

        $enable_extra_rules = ($config['OPNsense']['Suricata']['global']['enableextrarules'] == '1');

        /* Get last update information if available */

        if (file_exists(SURICATADIR."rulesupd_status")) {
            $status = explode("|", file_get_contents(SURICATADIR."rulesupd_status"));
            $last_rule_upd_time = date('M-d Y H:i', $status[0]);
            $last_rule_upd_status = gettext($status[1]);
        }
        else {
            $last_rule_upd_time = "Unknown";
            $last_rule_upd_status = "Unknown";
        }

        $updatesDisabled = ((!$snortdownload) && (!$emergingthreats) && (!$etpro) && (!$feodotracker_rules) && (!$sslbl_rules) && (!$enable_extra_rules));

        /* Log */
        $log = "";
        if (file_exists("{$suricata_rules_upd_log}")) {
            if (filesize("{$suricata_rules_upd_log}") > 0) {
                $log = file_get_contents($suricata_rules_upd_log);
            }
        }

        $this->view->rulesTable = $rulesTable;
        $this->view->updatesDisabled = $updatesDisabled;
        $this->view->last_rule_upd_time = $last_rule_upd_time;
        $this->view->last_rule_upd_status = $last_rule_upd_status;
        $this->view->log = $log;
        $this->view->pick('OPNsense/Suricata/updates');
    }
}
