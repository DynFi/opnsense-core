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


require_once("interfaces.inc");
require_once("system.inc");
require_once("util.inc");
require_once("ipsec.inc");


if (!defined('SURICATA_PBI_BASEDIR')) {
    define('SURICATA_PBI_BASEDIR', '/usr/local/');
}

if (!defined('SURICATA_PBI_BINDIR'))
    define('SURICATA_PBI_BINDIR', SURICATA_PBI_BASEDIR . 'bin/');

if (!defined("SURICATA_BIN_VERSION")) {
    $suricatabindir = SURICATA_PBI_BINDIR;
    $suricataver = exec_command("{$suricatabindir}suricata -V | /usr/bin/tail -n 1 | /usr/bin/cut -c26-");
    if (!empty($suricataver))
        define("SURICATA_BIN_VERSION", $suricataver);
    else
        define("SURICATA_BIN_VERSION", "");
}

if (!defined('SURICATA_PF_TABLE'))
    define('SURICATA_PF_TABLE', 'snort2c');

if (!defined('SURICATADIR'))
    define('SURICATADIR', SURICATA_PBI_BASEDIR . 'etc/suricata/');
if (!defined('SURICATALOGDIR'))
    define('SURICATALOGDIR', "/var/log/suricata/");
if (!defined('SURICATA_RULES_DIR'))
    define('SURICATA_RULES_DIR', "/usr/local/share/suricata/rules/");
if (!defined('SURICATA_RULES_UPD_LOGFILE'))
    define('SURICATA_RULES_UPD_LOGFILE', SURICATALOGDIR . 'suricata_rules_update.log');
if (!defined('SURICATA_SID_MODS_PATH'))
    define('SURICATA_SID_MODS_PATH', "/var/db/suricata/sidmods/");
if (!defined('SURICATA_IPREP_PATH'))
    define('SURICATA_IPREP_PATH', "/var/db/suricata/iprep/");

if (!defined("VRT_DNLD_URL"))
    define("VRT_DNLD_URL", "https://www.snort.org/rules/");
if (!defined("ET_VERSION"))
    define("ET_VERSION", "2.9.0");
if (!defined("ET_BASE_DNLD_URL"))
    define("ET_BASE_DNLD_URL", "https://rules.emergingthreats.net/");
if (!defined("ETPRO_BASE_DNLD_URL"))
    define("ETPRO_BASE_DNLD_URL", "https://rules.emergingthreatspro.com/");
if (!defined("ET_DNLD_FILENAME"))
    define("ET_DNLD_FILENAME", "emerging.rules.tar.gz");
if (!defined("ETPRO_DNLD_FILENAME"))
    define("ETPRO_DNLD_FILENAME", "etpro.rules.tar.gz");
if (!defined("ET_IQRISK_DNLD_URL"))
    define("ET_IQRISK_DNLD_URL", "https://rules.emergingthreatspro.com/_xxx_/reputation/");
if (!defined("GPLV2_DNLD_FILENAME"))
    define("GPLV2_DNLD_FILENAME", "community-rules.tar.gz");
if (!defined("GPLV2_DNLD_URL"))
    define("GPLV2_DNLD_URL", "https://www.snort.org/downloads/community/");

if (!defined("FEODO_TRACKER_DNLD_FILENAME"))
    define("FEODO_TRACKER_DNLD_FILENAME", "feodotracker.tar.gz");
if (!defined("FEODO_TRACKER_DNLD_URL"))
    define("FEODO_TRACKER_DNLD_URL", "https://feodotracker.abuse.ch/downloads/");
if (!defined("ABUSE_SSLBL_DNLD_FILENAME"))
    define("ABUSE_SSLBL_DNLD_FILENAME", "sslblacklist_tls_cert.tar.gz");
if (!defined("ABUSE_SSLBL_DNLD_URL"))
    define("ABUSE_SSLBL_DNLD_URL", "https://sslbl.abuse.ch/blacklist/");

if (!defined("VRT_FILE_PREFIX"))
    define("VRT_FILE_PREFIX", "snort_");
if (!defined("GPL_FILE_PREFIX"))
    define("GPL_FILE_PREFIX", "GPLv2_");
if (!defined("ET_OPEN_FILE_PREFIX"))
    define("ET_OPEN_FILE_PREFIX", "emerging-");
if (!defined("ET_PRO_FILE_PREFIX"))
    define("ET_PRO_FILE_PREFIX", "etpro-");
if (!defined("EXTRARULE_FILE_PREFIX"))
    define("EXTRARULE_FILE_PREFIX", "extrarule-");
if (!defined('SURICATA_ENFORCING_RULES_FILENAME'))
    define('SURICATA_ENFORCING_RULES_FILENAME', 'suricata.rules');
if (!defined('FLOWBITS_FILENAME'))
    define('FLOWBITS_FILENAME', 'flowbit-required.rules');



function suricata_services()
{
    global $config;

    $services = array();

    foreach ($config['OPNsense']['Suricata']['interfaces'] as $suricatacfg) {
        if (isset($suricatacfg['enabled']) && $suricatacfg['enabled'] == 1) {
            $services[] = array(
                'description' => gettext('Suricata ('.$suricatacfg['iface'].')'),
                'configd' => array(
                    'restart' => array('suricata restart '.$suricatacfg['iface']),
                    'start' => array('suricata start '.$suricatacfg['iface']),
                    'stop' => array('suricata stop '.$suricatacfg['iface']),
                ),
                'name' => 'suricata_'.$suricatacfg['iface'],
            );
        }
    }

    return $services;
}


function suricata_configure() {
    return array(
        'bootup' => array('suricata_configure_do'),
        'local' => array('suricata_configure_do')
    );
}


function suricata_syslog()
{
    $logfacilities = array();

    $logfacilities['suricata'] = array('facility' => array('suricata'));

    return $logfacilities;
}


function suricata_xmlrpc_sync()
{
    $result = array();

    $result[] = array(
        'description' => gettext('Suricata'),
        'section' => 'OPNsense.Suricata',
        'id' => 'suricata',
        'services' => ["suricata"],
    );

    return $result;
}


function suricata_cron()
{
    global $config;

    $jobs = array();

    if (empty($config['OPNsense']['Suricata']['interfaces']))
        return $jobs;

    // suricata_check_cron_misc.inc
    $jobs[]['autocron'] = array('/usr/local/sbin/configctl -d suricata limitlogs', '*/5');

    // suricata_check_for_rule_updates
    $suricata_rules_up_info_ck = $config['OPNsense']['Suricata']['global']['autoruleupdate'];
    if ($suricata_rules_up_info_ck != "upnever") {
        $suricata_rules_upd_time = "00:" . str_pad(strval(random_int(0,59)), 2, "00", STR_PAD_LEFT);
        if (!empty($config['OPNsense']['Suricata']['global']['autoruleupdatetime'])) {
            $suricata_rules_upd_time = $config['OPNsense']['Suricata']['global']['autoruleupdatetime'];
        }

        $suricata_rules_up_min = intval(substr($suricata_rules_upd_time, -2));
        $hour = intval(substr($suricata_rules_upd_time, 0, 2));
        $suricata_rules_up_hr = strval($hour);
        for ($i=0; $i<3; $i++) {
            $hour += 6;
            if ($hour > 24) {
                $hour -= 24;
            }
            $suricata_rules_up_hr .= "," . strval($hour);
        }
        $suricata_rules_up_mday = "*";
        $suricata_rules_up_month = "*";
        $suricata_rules_up_wday = "*";
        if ($suricata_rules_up_info_ck == "up12h") {
            $suricata_rules_up_min = intval(substr($suricata_rules_upd_time, -2));
            $hour = intval(substr($suricata_rules_upd_time, 0, 2));
            $suricata_rules_up_hr = strval($hour) . ",";
            $hour += 12;
            if ($hour > 24) {
                $hour -= 24;
            }
            $suricata_rules_up_hr .= strval($hour);
            $suricata_rules_up_mday = "*";
            $suricata_rules_up_month = "*";
            $suricata_rules_up_wday = "*";
        }
        if ($suricata_rules_up_info_ck == "up1d") {
            $suricata_rules_up_min = intval(substr($suricata_rules_upd_time, -2));
            $suricata_rules_up_hr = intval(substr($suricata_rules_upd_time, 0, 2));
            $suricata_rules_up_mday = "*/1";
            $suricata_rules_up_month = "*";
            $suricata_rules_up_wday = "*";
        }
        if ($suricata_rules_up_info_ck == "up4d") {
            $suricata_rules_up_min = intval(substr($suricata_rules_upd_time, -2));
            $suricata_rules_up_hr = intval(substr($suricata_rules_upd_time, 0, 2));
            $suricata_rules_up_mday = "*/4";
            $suricata_rules_up_month = "*";
            $suricata_rules_up_wday = "*";
        }
        if ($suricata_rules_up_info_ck == "up7d") {
            $suricata_rules_up_min = intval(substr($suricata_rules_upd_time, -2));
            $suricata_rules_up_hr = intval(substr($suricata_rules_upd_time, 0, 2));
            $suricata_rules_up_mday = "*/7";
            $suricata_rules_up_month = "*";
            $suricata_rules_up_wday = "*";
        }
        if ($suricata_rules_up_info_ck == "up28d") {
            $suricata_rules_up_min = intval(substr($suricata_rules_upd_time, -2));
            $suricata_rules_up_hr = intval(substr($suricata_rules_upd_time, 0, 2));
            $suricata_rules_up_mday = "*/28";
            $suricata_rules_up_month = "*";
            $suricata_rules_up_wday = "*";
        }

        $jobs[]['autocron'] = array('/usr/local/sbin/configctl -d suricata updaterules', $suricata_rules_up_min, $suricata_rules_up_hr, $suricata_rules_up_mday, $suricata_rules_up_month, $suricata_rules_up_wday);
    }

    $suricata_rm_blocked_info_ck = $config['OPNsense']['Suricata']['global']['rmblocked'];
    if ($suricata_rm_blocked_info_ck != "bnever") {
        $suri_pf_table = SURICATA_PF_TABLE;

        $suricata_rm_blocked_min = "*/1";
        $suricata_rm_blocked_hr = "*";
        $suricata_rm_blocked_mday = "*";
        $suricata_rm_blocked_month = "*";
        $suricata_rm_blocked_wday = "*";
        $suricata_rm_blocked_expire = "900";
        if ($suricata_rm_blocked_info_ck == "b30m") {
            $suricata_rm_blocked_min = "*/5";
            $suricata_rm_blocked_hr = "*";
            $suricata_rm_blocked_mday = "*";
            $suricata_rm_blocked_month = "*";
            $suricata_rm_blocked_wday = "*";
            $suricata_rm_blocked_expire = "1800";
        }
        if ($suricata_rm_blocked_info_ck == "b1h") {
            $suricata_rm_blocked_min = "*/5";
            $suricata_rm_blocked_hr = "*";
            $suricata_rm_blocked_mday = "*";
            $suricata_rm_blocked_month = "*";
            $suricata_rm_blocked_wday = "*";
            $suricata_rm_blocked_expire = "3600";
        }
        if ($suricata_rm_blocked_info_ck == "b3h") {
            $suricata_rm_blocked_min = "*/5";
            $suricata_rm_blocked_hr = "*";
            $suricata_rm_blocked_mday = "*";
            $suricata_rm_blocked_month = "*";
            $suricata_rm_blocked_wday = "*";
            $suricata_rm_blocked_expire = "10800";
        }
        if ($suricata_rm_blocked_info_ck == "b6h") {
            $suricata_rm_blocked_min = "*/5";
            $suricata_rm_blocked_hr = "*";
            $suricata_rm_blocked_mday = "*";
            $suricata_rm_blocked_month = "*";
            $suricata_rm_blocked_wday = "*";
            $suricata_rm_blocked_expire = "21600";
        }
        if ($suricata_rm_blocked_info_ck == "b12h") {
            $suricata_rm_blocked_min = "*/5";
            $suricata_rm_blocked_hr = "*";
            $suricata_rm_blocked_mday = "*";
            $suricata_rm_blocked_month = "*";
            $suricata_rm_blocked_wday = "*";
            $suricata_rm_blocked_expire = "43200";
        }
        if ($suricata_rm_blocked_info_ck == "b1d") {
            $suricata_rm_blocked_min = "*/5";
            $suricata_rm_blocked_hr = "*";
            $suricata_rm_blocked_mday = "*";
            $suricata_rm_blocked_month = "*";
            $suricata_rm_blocked_wday = "*";
            $suricata_rm_blocked_expire = "86400";
        }
        if ($suricata_rm_blocked_info_ck == "b4d") {
            $suricata_rm_blocked_min = "*/5";
            $suricata_rm_blocked_hr = "*";
            $suricata_rm_blocked_mday = "*";
            $suricata_rm_blocked_month = "*";
            $suricata_rm_blocked_wday = "*";
            $suricata_rm_blocked_expire = "345600";
        }
        if ($suricata_rm_blocked_info_ck == "b7d") {
            $suricata_rm_blocked_min = "*/5";
            $suricata_rm_blocked_hr = "*";
            $suricata_rm_blocked_mday = "*";
            $suricata_rm_blocked_month = "*";
            $suricata_rm_blocked_wday = "*";
            $suricata_rm_blocked_expire = "604800";
        }
        if ($suricata_rm_blocked_info_ck == "b28d") {
            $suricata_rm_blocked_min = "*/5";
            $suricata_rm_blocked_hr = "*";
            $suricata_rm_blocked_mday = "*";
            $suricata_rm_blocked_month = "*";
            $suricata_rm_blocked_wday = "*";
            $suricata_rm_blocked_expire = "2419200";
        }

        $command = "/usr/bin/nice -n20 /sbin/pfctl -q -t {$suri_pf_table} -T expire {$suricata_rm_blocked_expire}";
        $jobs[]['autocron'] = array($command, $suricata_rm_blocked_min, $suricata_rm_blocked_hr, $suricata_rm_blocked_mday, $suricata_rm_blocked_month, $suricata_rm_blocked_wday);
    }

    return $jobs;
}


function suricata_configure_do($verbose = false, $unused = '') {
    global $config;

    if (empty($config['OPNsense']['Suricata']['interfaces'])) {
        service_log('No Suricata interfaces defined, exiting...', $verbose);
        return;
    }

    service_log('Generating Suricata configs...', $verbose);

    foreach ($config['OPNsense']['Suricata']['interfaces'] as $suricatacfg) {

        suricata_stop($suricatacfg['iface']);

        if (isset($suricatacfg['enabled']) && $suricatacfg['enabled'] == 1) {
            service_log('Generating suricata.yaml file for ['.$suricatacfg['iface'].']', $verbose);
            suricata_generate_yaml($suricatacfg);

            service_log('Starting Suricata for ['.$suricatacfg['iface'].']', $verbose);
            suricata_start($suricatacfg['iface'], $verbose);
        }
    }
}


function suricata_generate_yaml($suricatacfg) {
    global $config, $g;

    $suricatadir = SURICATADIR;
    $suricatalogdir = SURICATALOGDIR;
    $flowbit_rules_file = FLOWBITS_FILENAME;
    $suricata_enforcing_rules_file = SURICATA_ENFORCING_RULES_FILENAME;

    $realif = get_real_interface($suricatacfg['iface']);

    $suricatacfgdir = SURICATADIR . "suricata_{$realif}";

    include("/usr/local/opnsense/scripts/suricata/control/suricata_generate_yaml.php");
    include("/usr/local/opnsense/scripts/suricata/control/suricata_yaml_template.inc");
    @file_put_contents("{$suricatacfgdir}/suricata.yaml", $suricata_conf_text);
    unset($suricata_conf_text);
}


function suricata_get_interface_config($iface) {
    global $config;

    $iface = strtolower($iface);

    foreach ($config['OPNsense']['Suricata']['interfaces'] as $suricatacfg) {
        if ($suricatacfg['iface'] == $iface) {
            $suricatacfg['uuid'] = $suricatacfg['@attributes']['uuid'];
            return $suricatacfg;
        }
    }
    return null;
}


function file_put_contents_with_mkdir($dir, $contents) {
    $parts = explode('/', $dir);
    $file = array_pop($parts);
    $dir = '';
    foreach($parts as $part)
        if(!is_dir($dir .= "/$part")) mkdir($dir);
    file_put_contents("$dir/$file", $contents);
}


function suricata_sid_mgmt_auto_categories($suricatacfg, $log_results = FALSE) {

    /****************************************************/
    /* This function parses any auto-SID conf lists     */
    /* configured for the interface and returns an      */
    /* array of rule categories adjusted from the       */
    /* ['enabled_rulesets'] element in the config for   */
    /* the interface in accordance with the contents    */
    /* of the SID Mgmt conf lists.                      */
    /*                                                  */
    /* The returned array shows which files should be   */
    /* removed and which should be added to the list    */
    /* used when building the enforcing ruleset.        */
    /*                                                  */
    /*  $suricatacfg ==> pointer to interface           */
    /*                   configuration info             */
    /*  $log_results ==> [optional] log results to      */
    /*                   'sid_changes.log' in the       */
    /*                   interface directory in         */
    /*                   /var/log/suricata when TRUE    */
    /*                                                  */
    /*       Returns ==> array of category file names   */
    /*                   for the interface.  The keys   */
    /*                   are category file names and    */
    /*                   the corresponding values show  */
    /*                   if the file should be added    */
    /*                   or removed from the enabled    */
    /*                   rulesets list.                 */
    /*                                                  */
    /*                    Example -                     */
    /*                      $changes[file] = 'enabled'  */
    /*                                                  */
    /****************************************************/

    global $config;
    $sid_mods = array();
    $enables = array();
    $disables = array();
    $drops = array();

    // Check if auto-mgmt of SIDs is enabled, exit if not
    if ($config['OPNsense']['Suricata']['global']['automanagesids'] != '1') {
        return array();
    }
    if (empty($suricatacfg['disablesidfile']) && empty($suricatacfg['enablesidfile']) && empty($suricatacfg['dropsidfile'])) {
        return array();
    }

    // Configure the interface's logging subdirectory if log results is enabled
    if ($log_results == TRUE) {
        $log_file = SURICATALOGDIR . $suricatalogdir . "suricata_" . get_real_interface($suricatacfg['interface']) . "/sid_changes.log";
    } else {
        $log_file = NULL;
    }

    // Get the list of currently enabled categories for the interface
    if (!empty($suricatacfg['rulesets'])) {
        $enabled_cats = explode("||", $suricatacfg['rulesets']);
    }

    if ($log_results == TRUE) {
        error_log(gettext("********************************************************\n"), 3, $log_file);
        error_log(gettext("Starting auto RULE CATEGORY management for " . $suricatacfg['interface'] . "\n"), 3, $log_file);
        error_log(gettext("Start Time: " . date("Y-m-d H:i:s") . "\n"), 3, $log_file);
    }

    switch ($suricatacfg['sidstateorder']) {
        case "disableenable":
            if (!empty($suricatacfg['disablesidfile'])) {
                if ($log_results == TRUE) {
                    error_log(gettext("Processing disable_sid list: {$suricatacfg['disablesidfile']}\n"), 3, $log_file);
                }

                // Attempt to open the 'disablesidfile' for the interface
                // Verify the assigned SID Mgmt List still exists in the firewall configuration
                if (!suricata_sid_mgmt_list_exist($suricatacfg['disablesidfile'])) {
                    syslog(LOG_ERR, gettext("[Suricata] ERROR: unable to open disable_sid list \"{$suricatacfg['disablesidfile']}\" specified for " . $suricatacfg['interface']));
                    if ($log_results == TRUE) {
                        error_log(gettext("Unable to find disable_sid list \"{$suricatacfg['disablesidfile']}\".\n"), 3, $log_file);
                    }
                } else {
                    $sid_mods = suricata_parse_sidconf_file($suricatacfg['disablesidfile']);
                }

                if (!empty($sid_mods)) {
                    $disables = suricata_get_auto_category_mods($enabled_cats, $sid_mods, "disable", $log_results, $log_file);
                } elseif ($log_results == TRUE && !empty($log_file)) {
                    error_log(gettext("WARNING: no valid SID match tokens found in list \"{$suricatacfg['disablesidfile']}\".\n"), 3, $log_file);
                }
            }
            if (!empty($suricatacfg['enablesidfile'])) {
                if ($log_results == TRUE) {
                    error_log(gettext("Processing enable_sid list: {$suricatacfg['enablesidfile']}\n"), 3, $log_file);
                }

                // Attempt to open the 'enablesidfile' for the interface
                if (!suricata_sid_mgmt_list_exist($suricatacfg['enablesidfile'])) {
                    syslog(LOG_ERR, gettext("[Suricata] ERROR: unable to open enable_sid list \"{$suricatacfg['enablesidfile']}\" specified for " . $suricatacfg['interface']));
                    if ($log_results == TRUE) {
                        error_log(gettext("Unable to find enable_sid list \"{$suricatacfg['enablesidfile']}\".\n"), 3, $log_file);
                    }
                } else {
                    $sid_mods = suricata_parse_sidconf_file($suricatacfg['enablesidfile']);
                }

                if (!empty($sid_mods)) {
                    $enables = suricata_get_auto_category_mods($enabled_cats, $sid_mods, "enable", $log_results, $log_file);
                } elseif ($log_results == TRUE && !empty($log_file)) {
                    error_log(gettext("WARNING: no valid SID match tokens found in list \"{$suricatacfg['enablesidfile']}\".\n"), 3, $log_file);
                }
            }
            break;

        case "enabledisable":
            if (!empty($suricatacfg['enablesidfile'])) {
                if ($log_results == TRUE) {
                    error_log(gettext("Processing enable_sid list: {$suricatacfg['enablesidfile']}\n"), 3, $log_file);
                }

                // Attempt to open the 'enablesidfile' for the interface
                if (!suricata_sid_mgmt_list_exist($suricatacfg['enablesidfile'])) {
                    syslog(LOG_ERR, gettext("[Suricata] ERROR: unable to find enable_sid list \"{$suricatacfg['enablesidfile']}\" specified for " . $suricatacfg['interface']));
                    if ($log_results == TRUE) {
                        error_log(gettext("Unable to open enable_sid list \"{$suricatacfg['enablesidfile']}\".\n"), 3, $log_file);
                    }
                } else {
                    $sid_mods = suricata_parse_sidconf_file($suricatacfg['enablesidfile']);
                }

                if (!empty($sid_mods)) {
                    $enables = suricata_get_auto_category_mods($enabled_cats, $sid_mods, "enable", $log_results, $log_file);
                } elseif ($log_results == TRUE && !empty($log_file)) {
                    error_log(gettext("WARNING: no valid SID match tokens found in list \"{$suricatacfg['enablesidfile']}\".\n"), 3, $log_file);
                }
            }
            if (!empty($suricatacfg['disablesidfile'])) {
                if ($log_results == TRUE) {
                    error_log(gettext("Processing disable_sid list: {$suricatacfg['disablesidfile']}\n"), 3, $log_file);
                }

                // Attempt to open the 'disablesidfile' for the interface
                if (!suricata_sid_mgmt_list_exist($suricatacfg['disablesidfile'])) {
                    syslog(LOG_ERR, gettext("[Suricata] ERROR: unable to open disable_sid list \"{$suricatacfg['disablesidfile']}\" specified for " . $suricatacfg['interface']));
                    if ($log_results == TRUE) {
                        error_log(gettext("Unable to find disable_sid list \"{$suricatacfg['disablesidfile']}\".\n"), 3, $log_file);
                    }
                }
                else {
                    $sid_mods = suricata_parse_sidconf_file($suricatacfg['disablesidfile']);
                }

                if (!empty($sid_mods)) {
                    $disables = suricata_get_auto_category_mods($enabled_cats, $sid_mods, "disable", $log_results, $log_file);
                } elseif ($log_results == TRUE && !empty($log_file)) {
                    error_log(gettext("WARNING: no valid SID match tokens found in list \"{$suricatacfg['disablesidfile']}\".\n"), 3, $log_file);
                }
            }
            break;

        default:
            syslog(LOG_ERR, gettext("[Suricata] Unrecognized 'sid_state_order' value.  Skipping auto CATEGORY mgmt step for " . $suricatacfg['interface']));
            if ($log_results == TRUE) {
                error_log(gettext("ERROR: unrecognized 'sid_state_order' value.  Skipping auto CATEGORY mgmt step for ") . $suricatacfg['interface']. ".\n", 3, $log_file);
            }
    }

    if ($log_results == TRUE) {
        error_log(gettext("End Time: " . date("Y-m-d H:i:s") . "\n"), 3, $log_file);
        error_log(gettext("********************************************************\n\n"), 3, $log_file);
    }

    // Return the required rule category modifications as an array;
    return array_merge($enables, $disables);
}


function suricata_load_rules_map($rules_path) {

    /***************************************************************/
    /* This function loads and returns an array with all the rules */
    /* found in the *.rules files in the passed rules path.        */
    /*                                                             */
    /* $rules_path can be:                                         */
    /*      a directory (assumed to contain *.rules files)         */
    /*      a filename (identifying a specific *.rules file)       */
    /*      an array of filenames (identifying *.rules files)      */
    /***************************************************************/

    $map_ref = array();
    $rule_files = array();

    if (empty($rules_path)) {
        return $map_ref;
    }

    /************************************************************************************
        * Read all the rules into the map array.
        * The structure of the map array is:
        *
        *  map[gid][sid]['rule']['category']['action']['disabled']['managed']['noalert']
            *     ['default_state']['default_action']['state_toggled']['modified']['flowbits']
        *
        *  where:
        *   gid            = Generator ID from rule, or 1 if general text rule
        *   sid            = Signature ID from rule
            *   rule           = Complete rule text
        *   category       = File name of file containing the rule
        *   action         = alert, drop, reject or pass
        *   disabled       = 1 if rule is disabled (commented out), 0 if
        *                    rule is enabled
        *   managed        = 1 if rule is auto-managed by SID MGMT process,
        *                    0 if not auto-managed
        *   noalert        = 1 if rule contains "noalert" or "flowbits:noalert"
        *		        options
        *   default_state  = 1 if rule is default enabled, 0 if default disabled
        *   default_action = alert, drop, reject or pass
        *   state_toggled  = 1 if rule was toggled by SID MGMT process,
        *                    0 if not toggled
        *   modified       = 1 if rule action or content is modified by SID MGMT or
        *                      IPS Policy process,
        *                    0 if not modified
        *   flowbits       = Array of applicable flowbits if rule contains
        *                    flowbits options
        ************************************************************************************/

    // First check if we were passed a directory, a single file
    // or an array of filenames to read. Set our $rule_files
    // variable accordingly. If we can't figure it out, return
    // an empty rules map array.
    if (is_string($rules_path)) {
        if (is_dir($rules_path)) {
            $rule_files = glob($rules_path . "*.rules");
        } elseif (is_file($rules_path)) {
            $rule_files = (array)$rules_path;
        }
    }
    elseif (is_array($rules_path)) {
        $rule_files = $rules_path;
    } else {
        return $map_ref;
    }

    // Read the rule files into an array, then iterate the list
    // to process the rules from the files one-by-one.
    foreach ($rule_files as $file) {

        // Don't process files with "deleted" in the filename.
        if (stristr($file, "deleted")) {
            continue;
        }

        // Read the file contents into an array, skipping
        // missing files.
        if (!file_exists($file)) {
            continue;
        }

        $rules_array = file($file, FILE_SKIP_EMPTY_LINES);
        $record = "";
        $b_Multiline = false;

        // Read and process each line from the rules in the
        // current file into an array.
        foreach ($rules_array as $rule) {

            // Skip any lines that may be just spaces.
            if (trim($rule, " \n") == "") {
                continue;
            }

            // Skip any non-rule lines unless we're in
            // multiline mode.
            if (!preg_match('/^\s*#*\s*(alert|drop|pass|reject)/i', $rule) && !$b_Multiline) {
                continue;
            }

            // Test for a multi-line rule; loop and reassemble
            // the pieces back into a single line.
            if (preg_match('/\\\\s*[\n]$/m', $rule)) {
                $rule = substr($rule, 0, strrpos($rule, '\\'));
                $record .= $rule;
                $b_Multiline = true;
                continue;
            }
            // If the last segment of a multiline rule, then
            // append it onto the previous parts to form a
            // single-line rule for further processing below.
            elseif (!preg_match('/\\\\s*[\n]$/m', $rule) && $b_Multiline) {
                $record .= $rule;
                $rule = $record;
            }

            // We have an actual single-line rule, or else a
            // re-assembled multiline rule that is now a
            // single-line rule, so store it in our rules map.

            // Get and test the SID.  If we don't find one,
            // ignore and skip this rule as it is invalid.
            $sid = suricata_get_sid($rule);
            if (empty($sid)) {
                $b_Multiline = false;
                $record = "";
                continue;
            }

            $gid = suricata_get_gid($rule);
            if (!is_array($map_ref[$gid])) {
                $map_ref[$gid] = array();
            }
            if (!is_array($map_ref[$gid][$sid])) {
                $map_ref[$gid][$sid] = array();
            }
            $map_ref[$gid][$sid]['rule'] = $rule;
            $map_ref[$gid][$sid]['category'] = basename($file, ".rules");
            $map_ref[$gid][$sid]['state_toggled'] = 0;
            $map_ref[$gid][$sid]['modified'] = 0;
            $map_ref[$gid][$sid]['managed'] = 0;

            // Check for "noalert;" rule option
            if (strpos($rule, 'noalert;') !== FALSE) {
                $map_ref[$gid][$sid]['noalert'] = 1;
            } else {
                $map_ref[$gid][$sid]['noalert'] = 0;
            }

            // Grab the rule action
            $matches = array();
            if (preg_match('/^\s*#*\s*(alert|drop|pass|reject)/i', $rule, $matches)) {
                $map_ref[$gid][$sid]['action'] = $matches[1];
                $map_ref[$gid][$sid]['default_action'] = $matches[1];
            } else {
                $map_ref[$gid][$sid]['action'] = "";
            }

            // Determine if default state is "disabled"
            if (preg_match('/^\s*\#+/', $rule)) {
                $map_ref[$gid][$sid]['disabled'] = 1;
                $map_ref[$gid][$sid]['default_state'] = 0;
            } else {
                $map_ref[$gid][$sid]['disabled'] = 0;
                $map_ref[$gid][$sid]['default_state'] = 1;
            }

            // Grab any associated flowbits from the rule.
            $map_ref[$gid][$sid]['flowbits'] = suricata_get_flowbits($rule);

            // Reset our local flag and record variables
            // for the next rule in the set.
            $b_Multiline = false;
            $record = "";
        }

        // Zero out our processing array and get the next file.
        unset($rules_array);
    }
    return $map_ref;
}


function suricata_load_vrt_policy($policy, $mode = 'alert', $all_rules = null) {

    /************************************************/
    /* This function returns an array of all rules  */
    /* marked with the passed in $policy metadata.  */
    /*                                              */
    /*    $policy --> desired VRT security policy   */
    /*                  1. connectivity             */
    /*                  2. balanced                 */
    /*                  3. security                 */
    /*                                              */
    /*      $mode --> determines rule action        */
    /*                  1. alert = all rule alert   */
    /*                  2. policy = rule action     */
    /*                              set according   */
    /*                              policy spec.    */
    /*                                              */
    /* $all_rules --> optional Rules Map array of   */
    /*                rules to scan for policy.     */
    /*                If not provided, then an      */
    /*                array will be created.        */
    /************************************************/

    $suricatadir = SURICATADIR;
    $vrt_policy_rules = array();

    // Load a map of all the VRT rules if we were
    // not passed a pre-loaded one to use.
    if (is_null($all_rules)) {
        /* Since only Snort VRT rules have IPS Policy metadata, */
        /* limit our search to just those files.                */
        $suricata_file_pattern = VRT_FILE_PREFIX . "*.rules";
        $suricata_vrt_files = glob(SURICATA_RULES_DIR . "{$suricata_file_pattern}");
        $all_rules = suricata_load_rules_map($suricata_vrt_files);
    }

    // Now walk the rules list and find all those that are
    // defined as active for the chosen security policy.
    foreach ($all_rules as $k1 => $arulem) {
        foreach ($arulem as $k2 => $arulem2) {
            if (strripos($arulem2['rule'], "policy {$policy}-ips") !== false) {
                if (!preg_match('/flowbits\s*:\s*noalert/i', $arulem2['rule'])) {
                    if (!is_array($vrt_policy_rules[$k1])) {
                        $vrt_policy_rules[$k1] = array();
                    }
                    if (!is_array($vrt_policy_rules[$k1][$k2])) {
                        $vrt_policy_rules[$k1][$k2] = array();
                    }
                    $vrt_policy_rules[$k1][$k2] = $arulem2;

                    // Enable the policy rule if disabled
                    if ($arulem2['disabled'] == 1) {
                        $vrt_policy_rules[$k1][$k2]['rule'] = ltrim(substr($arulem2['rule'], strpos($arulem2['rule'], "#") + 1));
                        $vrt_policy_rules[$k1][$k2]['disabled'] = 0;
                    }

                    // If policy mode is enabled, grab the suggested action
                    // for this policy and set it as the rule action.
                    if ($mode == 'policy') {
                        $matches = array();
                        if (preg_match('/' . "policy {$policy}-ips" . '([^,|^;]*)/', $arulem2['rule'], $matches)) {
                            if ($tmp = preg_replace('/^\s*alert\s/', trim($matches[1]) . ' ', $vrt_policy_rules[$k1][$k2]['rule'], 1)) {
                                $vrt_policy_rules[$k1][$k2]['rule'] = $tmp;
                                $vrt_policy_rules[$k1][$k2]['action'] = trim($matches[1]);
                                $vrt_policy_rules[$k1][$k2]['modified'] = 1;
                            }
                        }
                    }
                }
            }
        }
    }

    // Release memory we no longer need.
    unset($arulem, $arulem2);

    // Return all the rules that match the policy.
    return $vrt_policy_rules;
}


function suricata_auto_sid_mgmt(&$rule_map, $suricatacfg, $log_results = FALSE) {

    /**************************************************/
    /* This function modifies the rules in the        */
    /* passed rule_map array based on values in the   */
    /* files 'enablesidfile', 'disablesidfile'    */
    /* 'modifysidfile' and 'dropsidfile' for      */
    /* the interface.                                 */
    /*                                                */
    /* If auto-mgmt of SIDs is enabled via the        */
    /* settings on the UPDATE RULES tab, then the     */
    /* rules are processed against these settings.    */
    /*                                                */
    /*     $rule_map ==> array of current rules       */
    /*  $suricatacfg ==> interface config settings    */
    /*  $log_results ==> [optional] log results to    */
    /*                   'sid_changes.log' in the     */
    /*                   interface directory in       */
    /*                   /var/log/suricata when TRUE  */
    /*                                                */
    /*       Returns ==> TRUE if rules were changed;  */
    /*                   otherwise FALSE              */
    /**************************************************/

    global $config;
    $result = FALSE;

    // Configure the interface's logging subdirectory if log results is enabled
    if ($log_results == TRUE) {
        $log_file = SURICATALOGDIR . $suricatalogdir . "suricata_" . $suricatacfg['interface'] . "/sid_changes.log";
    } else {
        $log_file = NULL;
    }

    if (!isset($suricatacfg['interface']))
        $suricatacfg['interface'] = $suricatacfg['iface'];

    // Check if auto-mgmt of SIDs is enabled and files are specified
    // for the interface.
    if ($config['OPNsense']['Suricata']['global']['automanagesids'] == '1' &&
        (!empty($suricatacfg['disablesidfile']) || !empty($suricatacfg['enablesidfile']) ||
        !empty($suricatacfg['modifysidfile']) || !empty($suricatacfg['dropsidfile']))) {
        if ($log_results == TRUE) {
            error_log(gettext("********************************************************\n"), 3, $log_file);
            error_log(gettext("Starting auto SID management for " . $suricatacfg['interface'] ."\n"), 3, $log_file);
            error_log(gettext("Start Time: " . date("Y-m-d H:i:s") . "\n"), 3, $log_file);
        }

        switch ($suricatacfg['sidstateorder']) {
            case "disableenable":
                if (!empty($suricatacfg['disablesidfile'])) {
                    if ($log_results == TRUE) {
                        error_log(gettext("Processing disable_sid list: {$suricatacfg['disablesidfile']}\n"), 3, $log_file);
                    }
                    suricata_process_disablesid($rule_map, $suricatacfg, $log_results, $log_file);
                }
                if (!empty($suricatacfg['enablesidfile'])) {
                    if ($log_results == TRUE) {
                        error_log(gettext("Processing enable_sid list: {$suricatacfg['enablesidfile']}\n"), 3, $log_file);
                    }
                    suricata_process_enablesid($rule_map, $suricatacfg, $log_results, $log_file);
                }
                if (!empty($suricatacfg['modifysidfile'])) {
                    if ($log_results == TRUE) {
                        error_log(gettext("Processing modify_sid list: {$suricatacfg['modifysidfile']}\n"), 3, $log_file);
                    }
                    suricata_process_modifysid($rule_map, $suricatacfg, $log_results, $log_file);
                }
                if (!empty($suricatacfg['dropsidfile']) && ($suricatacfg['blockoffenders'] == '1' && ($suricatacfg['block_drops_only'] == '1' || $suricatacfg['ipsmode'] == 'inline'))) {
                    if ($log_results == TRUE) {
                        error_log(gettext("Processing drop_sid list: {$suricatacfg['dropsidfile']}\n"), 3, $log_file);
                    }
                    suricata_process_dropsid($rule_map, $suricatacfg, $log_results, $log_file);
                }
                if (!empty($suricatacfg['rejectsidfile']) && $suricatacfg['blockoffenders'] == '1' && $suricatacfg['ipsmode'] == 'inline') {
                    if ($log_results == TRUE) {
                        error_log(gettext("Processing reject_sid list: {$suricatacfg['rejectsidfile']}\n"), 3, $log_file);
                    }
                    suricata_process_rejectsid($rule_map, $suricatacfg, $log_results, $log_file);
                }
                $result = TRUE;
                break;

            case "enabledisable":
                if (!empty($suricatacfg['enablesidfile'])) {
                    if ($log_results == TRUE) {
                        error_log(gettext("Processing enable_sid list: {$suricatacfg['enablesidfile']}\n"), 3, $log_file);
                    }
                    suricata_process_enablesid($rule_map, $suricatacfg, $log_results, $log_file);
                }
                if (!empty($suricatacfg['disablesidfile'])) {
                    if ($log_results == TRUE) {
                        error_log(gettext("Processing disable_sid list: {$suricatacfg['disablesidfile']}\n"), 3, $log_file);
                    }
                    suricata_process_disablesid($rule_map, $suricatacfg, $log_results, $log_file);
                }
                if (!empty($suricatacfg['modifysidfile'])) {
                    if ($log_results == TRUE) {
                        error_log(gettext("Processing modify_sid list: {$suricatacfg['modifysidfile']}\n"), 3, $log_file);
                    }
                    suricata_process_modifysid($rule_map, $suricatacfg, $log_results, $log_file);
                }
                if (!empty($suricatacfg['dropsidfile']) && ($suricatacfg['blockoffenders'] == '1' && ($suricatacfg['blockdropsonly'] == '1' || $suricatacfg['mode'] == 'inline'))) {
                    if ($log_results == TRUE) {
                        error_log(gettext("Processing drop_sid list: {$suricatacfg['dropsidfile']}\n"), 3, $log_file);
                    }
                    suricata_process_dropsid($rule_map, $suricatacfg, $log_results, $log_file);
                }
                if (!empty($suricatacfg['rejectsidfile']) && $suricatacfg['blockoffenders'] == '1' && $suricatacfg['ipsmode'] == 'inline') {
                    if ($log_results == TRUE) {
                        error_log(gettext("Processing reject_sid list: {$suricatacfg['reject_sid_file']}\n"), 3, $log_file);
                    }
                    suricata_process_rejectsid($rule_map, $suricatacfg, $log_results, $log_file);
                }
                $result = TRUE;
                break;

            default:
                syslog(LOG_WARNING, gettext("[Suricata] WARNING: Unrecognized 'sid_state_order' value.  Skipping auto SID mgmt step for " . $suricatacfg['interface']));
                if ($log_results == TRUE) {
                    error_log(gettext("WARNING: unrecognized 'sid_state_order' value.  Skipping auto SID mgmt step for ") . $suricatacfg['interface']. ".\n", 3, $log_file);
                }
                $result = FALSE;
        }

        if ($log_results == TRUE) {
            error_log(gettext("End Time: " . date("Y-m-d H:i:s") . "\n"), 3, $log_file);
            error_log(gettext("********************************************************\n\n"), 3, $log_file);
        }
    }
    return $result;
}


function suricata_prepare_rule_files($suricatacfg, $suricatacfgdir) {

    /***********************************************************/
    /* This function builds a new set of enforcing rules for   */
    /* Suricata and writes them to disk.                       */
    /*                                                         */
    /*    $suricatacfg --> pointer to applicable section of    */
    /*                  config.xml containing settings for     */
    /*                  the interface.                         */
    /*                                                         */
    /* $suricatacfgdir --> pointer to physical directory on    */
    /*                  disk where Suricata configuration is   */
    /*                  to be written.                         */
    /***********************************************************/

    global $config, $rebuild_rules;

    $flowbit_rules_file = FLOWBITS_FILENAME;
    $suricata_enforcing_rules_file = SURICATA_ENFORCING_RULES_FILENAME;
    $enabled_rules = array();
    $enabled_files = array();
    $all_rules = array();
    $cat_mods = array();
    $no_rules_defined = true;

    if (!$rebuild_rules)
        return;

    if (!isset($suricatacfg['interface']))
        $suricatacfg['interface'] = $suricatacfg['iface'];

    // Log a message for rules rebuild in progress
    syslog(LOG_NOTICE, gettext("[Suricata] Updating rules configuration for ".$suricatacfg['interface']."..."));

    // Get any automatic rule category enable/disable modifications
    // if auto-SID Mgmt is enabled and conf files exist for the interface.
    $cat_mods = suricata_sid_mgmt_auto_categories($suricatacfg, TRUE);

    // Load up all the rules into a Rules Map array.
    $all_rules = suricata_load_rules_map(SURICATA_RULES_DIR);

    // Only rebuild rules if some are selected or an IPS Policy is enabled
    if (!empty($suricatacfg['rulesets']) || $suricatacfg['ipspolicyenable'] == '1' || !empty($cat_mods)) {
        $no_rules_defined = false;

        // Create an array with the filenames of the enabled
        // rule category files if we have any.
        if (!empty($suricatacfg['rulesets']) || !empty($cat_mods)) {
            // First get all the user-enabled category files
            if (!empty($suricatacfg['rulesets'])) {
                foreach (explode("||", $suricatacfg['rulesets']) as $file){
                        $category = basename($file, ".rules");
                        if (!is_array($enabled_files[$category])) {
                            $enabled_files[$category] = array();
                        }
                        $enabled_files[$category] = $file;
                }
            }

            // Now adjust the list using any required changes as
            // determined by auto-SID Mgmt policy files.
            if (!empty($cat_mods)) {
                foreach ($cat_mods as $k => $action) {
                    $key = basename($k, ".rules");
                    switch ($action) {
                        case 'enabled':
                            if (!isset($enabled_files[$key])) {
                                $enabled_files[$key] = $k;
                            }
                            break;

                        case 'disabled':
                            if (isset($enabled_files[$key])) {
                                unset($enabled_files[$key]);
                            }
                            break;

                        default:
                            break;
                    }
                }
            }

            /****************************************************/
            /* Walk the ALL_RULES map array and copy the rules  */
            /* matching our selected file categories to the     */
            /* ENABLED_RULES map array.                         */
            /****************************************************/
            foreach ($all_rules as $k1 => $rulem) {
                foreach ($rulem as $k2 => $v) {
                    if (isset($enabled_files[$v['category']])) {
                        if (!is_array($enabled_rules[$k1])) {
                            $enabled_rules[$k1] = array();
                        }
                        if (!is_array($enabled_rules[$k1][$k2])) {
                            $enabled_rules[$k1][$k2] = array();
                        }
                        $enabled_rules[$k1][$k2]['rule'] = $v['rule'];
                        $enabled_rules[$k1][$k2]['category'] = $v['category'];
                        $enabled_rules[$k1][$k2]['disabled'] = $v['disabled'];
                        $enabled_rules[$k1][$k2]['action'] = $v['action'];
                        $enabled_rules[$k1][$k2]['flowbits'] = $v['flowbits'];
                        $enabled_rules[$k1][$k2]['managed'] = $v['managed'];
                        $enabled_rules[$k1][$k2]['default_state'] = $v['default_state'];
                        $enabled_rules[$k1][$k2]['default_action'] = $v['default_action'];
                        $enabled_rules[$k1][$k2]['state_toggled'] = $v['state_toggled'];
                        $enabled_rules[$k1][$k2]['noalert'] = $v['noalert'];
                        $enabled_rules[$k1][$k2]['modified'] = $v['modified'];
                    }
                }
            }

            // Release memory we no longer need.
            unset($enabled_files, $cat_mods, $rulem, $v);
        }

        // Check if a pre-defined Snort VRT policy is selected. If so,
        // add all the VRT policy rules to our enforcing rule set.
        if ($suricatacfg['ipspolicyenable'] == '1' && !empty($suricatacfg['ipspolicy'])) {
            if ($suricatacfg['blockoffenders'] == '1' && ($suricatacfg['ipsmode'] == 'inline' || $suricatacfg['blockdropsonly'] == '1')) {
                $policy_mode = $suricatacfg['ipspolicymode'];
            }
            else {
                $policy_mode = 'alert';
            }
            $policy_rules = suricata_load_vrt_policy($suricatacfg['ipspolicy'], $policy_mode, $all_rules);
            foreach ($policy_rules as $k1 => $policy) {
                foreach ($policy as $k2 => $p) {
                    if (!is_array($enabled_rules[$k1])) {
                        $enabled_rules[$k1] = array();
                    }
                    if (!is_array($enabled_rules[$k1][$k2])) {
                        $enabled_rules[$k1][$k2] = array();
                    }
                    $enabled_rules[$k1][$k2]['rule'] = $p['rule'];
                    $enabled_rules[$k1][$k2]['category'] = $p['category'];
                    $enabled_rules[$k1][$k2]['disabled'] = $p['disabled'];
                    $enabled_rules[$k1][$k2]['action'] = $p['action'];
                    $enabled_rules[$k1][$k2]['flowbits'] = $p['flowbits'];
                    $enabled_rules[$k1][$k2]['managed'] = $p['managed'];
                    $enabled_rules[$k1][$k2]['default_state'] = $p['default_state'];
                    $enabled_rules[$k1][$k2]['default_action'] = $p['default_action'];
                    $enabled_rules[$k1][$k2]['state_toggled'] = $p['state_toggled'];
                    $enabled_rules[$k1][$k2]['noalert'] = $p['noalert'];
                    $enabled_rules[$k1][$k2]['modified'] = $p['modified'];
                }
            }
            unset($policy_rules, $policy, $p);
        }

        // Process any enablesid, disablesid, alertsid or dropsid modifications for the selected rules.
        // Do the auto-SID managment first, if enabled, then do any manual SID state changes.
        suricata_auto_sid_mgmt($enabled_rules, $suricatacfg, TRUE);
        suricata_modify_sids($enabled_rules, $suricatacfg);
        suricata_modify_sids_action($enabled_rules, $suricatacfg);

        // Write the enforcing rules file to the Suricata interface's "rules" directory.
        suricata_write_enforcing_rules_file($enabled_rules, "{$suricatacfgdir}/rules/{$suricata_enforcing_rules_file}");

        // If auto-flowbit resolution is enabled, generate the dependent flowbits rules file.
        if ($suricatacfg['autoflowbitrules'] == '1') {
            syslog(LOG_NOTICE, '[Suricata] Enabling any flowbit-required rules for: ' . $suricatacfg['interface'] . '...');
            $fbits = suricata_resolve_flowbits($all_rules, $enabled_rules);

            // Check for and disable any flowbit-required rules the user has
            // manually forced to a disabled state.
            suricata_modify_sids($fbits, $suricatacfg);
            suricata_write_flowbit_rules_file($fbits, "{$suricatacfgdir}/rules/{$flowbit_rules_file}");
            unset($fbits);
        } else {
            // Just put an empty file to always have the file present
            suricata_write_flowbit_rules_file(array(), "{$suricatacfgdir}/rules/{$flowbit_rules_file}");
        }
    }
    // If no rule categories were enabled, then use auto-SID management if enabled, since it may enable some rules
    elseif ($config['OPNsense']['Suricata']['global']['automanagesids'] == '1' &&
        (!empty($suricatacfg['disablesidfile']) || !empty($suricatacfg['enablesidfile']) ||
        !empty($suricatacfg['modifysidfile']))) {

        suricata_auto_sid_mgmt($enabled_rules, $suricatacfg, TRUE);
        if (!empty($enabled_rules)) {
            // Auto-SID management generated some rules, so use them but
            // apply any user-specified overrides for state or action.
            $no_rules_defined = false;
            suricata_modify_sids($enabled_rules, $suricatacfg);
            suricata_modify_sids_action($enabled_rules, $suricatacfg);

            // Write the enforcing rules file to the Suricata interface's "rules" directory.
            suricata_write_enforcing_rules_file($enabled_rules, "{$suricatacfgdir}/rules/{$suricata_enforcing_rules_file}");

            // If auto-flowbit resolution is enabled, generate the dependent flowbits rules file.
            if ($suricatacfg['autoflowbitrules'] == '1') {
                syslog(LOG_NOTICE, '[Suricata] Enabling any flowbit-required rules for: ' . $suricatacfg['interface'] . '...');

                // Load up all rules into a Rules Map array for flowbits assessment
                $fbits = suricata_resolve_flowbits($all_rules, $enabled_rules);

                // Check for and disable any flowbit-required rules the
                // user has manually forced to a disabled state.
                suricata_modify_sids($fbits, $suricatacfg);
                suricata_write_flowbit_rules_file($fbits, "{$suricatacfgdir}/rules/{$flowbit_rules_file}");
                unset($fbits);
            } else {
                // Just put an empty file to always have the file present
                suricata_write_flowbit_rules_file(array(), "{$suricatacfgdir}/rules/{$flowbit_rules_file}");
            }
        }
        else {
            suricata_write_enforcing_rules_file(array(), "{$suricatacfgdir}/rules/{$suricata_enforcing_rules_file}");
            suricata_write_flowbit_rules_file(array(), "{$suricatacfgdir}/rules/{$flowbit_rules_file}");
        }
    }
        else {
        suricata_write_enforcing_rules_file(array(), "{$suricatacfgdir}/rules/{$suricata_enforcing_rules_file}");
        suricata_write_flowbit_rules_file(array(), "{$suricatacfgdir}/rules/{$flowbit_rules_file}");
    }

    unset($all_rules);

    if (!empty($suricatacfg['customrules'])) {
        @file_put_contents("{$suricatacfgdir}/rules/custom.rules", base64_decode($suricatacfg['customrules']));
        $no_rules_defined = false;
    }
    else {
        @file_put_contents("{$suricatacfgdir}/rules/custom.rules", "");
    }

    // Log a warning if the interface has no rules defined or enabled
    if ($no_rules_defined) {
        syslog(LOG_WARNING, gettext("[Suricata] WARNING: - no text rules selected for: " . $suricatacfg['interface'] . " ..."));
    }

    // Build a new sid-msg.map file from the enabled
    // rules and copy it to the interface directory.
    syslog(LOG_NOTICE, gettext("[Suricata] Building new sid-msg.map file for " . $suricatacfg['interface'] . "..."));
    suricata_build_sid_msg_map("{$suricatacfgdir}/rules/", "{$suricatacfgdir}/sid-msg.map");
}


function suricata_find_list($find_name, $type = 'passlists') {
    global $config;

    if ($find_name) {

        $suricataglob = $config['OPNsense']['Suricata'][$type];
        if (!is_array($suricataglob[$type])) {
            return "";
        }


        foreach ($config['OPNsense']['Suricata'][$type] as $value) {
            if ($value['name'] == $find_name) {
                return $value;
            }
        }
    }

    return array();
}


function suricata_get_vpns_list() {
    global $config;

    $vpns = "";
    $vpns_arr = array();

    /* IPsec */
    if (!empty($config['ipsec']['enable']) || !empty($config['ipsec']['client']['enable'])) {
        if (is_array($config['ipsec']['client']) && isset($config['ipsec']['client']['enable'])) {
            /* Virtual Address Pool */
            if (isset($config['ipsec']['client']['pool_address']) &&
                isset($config['ipsec']['client']['pool_netbits'])) {
                $client_subnet = "{$config['ipsec']['client']['pool_address']}/{$config['ipsec']['client']['pool_netbits']}";
                if (is_subnetv4($client_subnet)) {
                        $vpns_arr[] = $client_subnet;
                }
            }
            /* Virtual IPv6 Address Pool */
            if (isset($config['ipsec']['client']['pool_address_v6']) &&
                isset($config['ipsec']['client']['pool_netbits_v6'])) {
                $client_subnet = "{$config['ipsec']['client']['pool_address_v6']}/{$config['ipsec']['client']['pool_netbits_v6']}";
                if (is_subnetv6($client_subnet)) {
                    $vpns_arr[] = text_to_compressed_ip6($client_subnet);
                }
            }
            /* Mobile warriors */
            if (isset($config['ipsec']['mobilekey'])) {
                foreach ($config['ipsec']['mobilekey'] as $key) {
                    if (!empty($key['pool_address']) &&
                        !empty($key['pool_netbits'])) {
                        $vpns_subnet = "{$key['pool_address']}/{$key['pool_netbits']}";
                        if (is_subnetv4($vpns_subnet)) {
                            $vpns_arr[] = $vpns_subnet;
                        }
                    }
                }
            }
        }
        /* Site-to-Site IPsec */
        if (is_array($config['ipsec']['phase2'])) {
            foreach ($config['ipsec']['phase2'] as $ph2ent) {
                if ((!$ph2ent['mobile']) && ($ph2ent['mode'] != 'transport') &&
                    !isset($ph2ent['disabled'])) {
                    if (!is_array($ph2ent['remoteid'])) {
                        continue;
                    }
                    $ph2ent['remoteid']['mode'] = $ph2ent['mode'];
                    $vpns_subnet = ipsec_idinfo_to_cidr($ph2ent['remoteid']);
                    if (is_subnetv4($vpns_subnet)) {
                        $vpns_arr[] = $vpns_subnet;
                    }
                    if (is_subnetv6($vpns_subnet)) {
                        $vpns_arr[] = text_to_compressed_ip6($vpns_subnet);
                    }
                }
            }
        }
    }

    /* OpenVPN */
    foreach (array('client', 'server') as $type) {
        if (is_array($config['openvpn']["openvpn-$type"])) {
            foreach ($config['openvpn']["openvpn-$type"] as $settings) {
                if (is_array($settings)) {
                    if (!isset($settings['disable'])) {
                        $remote_networks = explode(',', $settings['remote_network']);
                        foreach ($remote_networks as $remote_network) {
                            if (function_exists('openvpn_gen_tunnel_network')) {
                                $vpns_arr[] = implode('/', openvpn_gen_tunnel_network($remote_network));
                            } elseif (is_subnetv4($remote_network)) {
                                $vpns_arr[] = $remote_network;
                            }
                        }
                        if (function_exists('openvpn_gen_tunnel_network')) {
                            $vpns_arr[] = implode('/', openvpn_gen_tunnel_network($settings['tunnel_network']));
                        } elseif (is_subnetv4($settings['tunnel_network'])) {
                            $vpns_arr[] = $settings['tunnel_network'];
                        }
                        if (isset($settings['remote_networkv6'])) {
                            $remote_networks = explode(',', $settings['remote_networkv6']);
                            foreach ($remote_networks as $remote_network) {
                                if (function_exists('openvpn_gen_tunnel_network')) {
                                    $vpns_arr[] = implode('/', openvpn_gen_tunnel_network($remote_network));
                                } elseif (is_subnetv6($remote_network)) {
                                    $vpns_arr[] = text_to_compressed_ip6($remote_network);
                                }
                            }
                            if (function_exists('openvpn_gen_tunnel_network')) {
                                $vpns_arr[] = implode('/', openvpn_gen_tunnel_network($settings['tunnel_networkv6']));
                            } elseif (is_subnetv6($settings['tunnel_networkv6'])) {
                                $vpns_arr[] = text_to_compressed_ip6($settings['tunnel_networkv6']);
                            }
                        }
                    }
                }
            }
        }
    }

    // OpenVPN CSO
    foreach ($config['openvpn']['openvpn-csc'] as $ovpnent) {
        if (is_array($ovpnent) && !isset($ovpnent['disable'])) {
            if (!empty($ovpnent['tunnel_network'])) {
                if (function_exists('openvpn_gen_tunnel_network')) {
                    $vpns_arr[] = implode('/', openvpn_gen_tunnel_network($ovpnent['tunnel_network']));
                } else {
                    $vpns_arr[] = $settings['tunnel_network'];
                }
            }
            if (!empty($ovpnent['tunnel_networkv6'])) {
                if (function_exists('openvpn_gen_tunnel_network')) {
                    $vpns_arr[] = implode('/', openvpn_gen_tunnel_network($ovpnent['tunnel_networkv6']));
                } else {
                    $vpns_arr[] = $settings['tunnel_networkv6'];
                }
            }
        }
    }

    /* PPPoE Server */
    if (is_array($config['pppoes']['pppoe'])) {
        foreach ($config['pppoes']['pppoe'] as $pppoe) {
            if ($pppoe['mode'] == "server") {
                if (is_ipaddrv4($pppoe['remoteip'])) {
                    $pppoesub = gen_subnetv4($pppoe['remoteip'], $pppoe['pppoe_subnet']);
                    if (is_subnetv4($pppoesub)) {
                        $vpns_arr[] = $pppoesub;
                    }
                }
            }
        }
    }

    /* L2TP Server */
    if ($config['l2tp']['mode'] == "server") {
        $l2tp_net = "{$config['l2tp']['remoteip']}/{$config['l2tp']['l2tp_subnet']}";
        if (is_subnetv4($l2tp_net)) {
            $vpns_arr[] = $l2tp_net;
        }
    }

    /* WireGuard */
    /*if (function_exists('wg_get_tunnel_networks')) { TODO
        foreach (wg_get_tunnel_networks() as $wgn) {
            $vpns_arr[] = $wgn;
        }
    }*/

    if (!empty($vpns_arr)) {
        $vpns = implode(" ", array_diff($vpns_arr, array("0.0.0.0/0", "::/0")));
    }

    return $vpns;
}


function suricata_build_list($suricatacfg, $listname = "", $passlist = false, $externallist = false) {
    global $config, $g, $aliastable;
    $home_net = array();

    if (!isset($suricatacfg['interface']))
        $suricatacfg['interface'] = $suricatacfg['iface'];


    /***********************************************************/
    /* The default is to build a HOME_NET variable unless      */
    /* '$passlist' is set to 'true' when calling.              */
    /*                                                         */
    /* When '$passlist' is TRUE, a Pass List is built.         */
    /* When '$externalist' is TRUE, the EXTERNAL_NET variable  */
    /* is built.                                               */
    /***********************************************************/
    if (!$externallist && ($listname == 'default' || empty($listname))) {
        // When using inline IPS mode, exclude VPNs, VIPs,
        // locally-attached network segments and the WAN IP from
        // the default Pass List as this will kill all alerts
        // and allow all traffic to pass!  We do include
        // locally-attached networks, VPNs, VIPs and the WAN IP
        // when building only the HOME_NET variable.
        if ($suricatacfg['ipsmode'] == 'inline' && $suricatacfg['blockoffenders'] == '1' && $passlist == TRUE) {
            $localnet = 'no';
            $wanip = 'no';
            $vpns = 'no';
            $vips = 'no';
        }
        else {
            $localnet = 'yes';
            $wanip = 'yes';
            $vpns = 'yes';
            $vips = 'yes';
        }

        $wangw = 'yes'; $wandns = 'yes';
    } else {
        $list = suricata_find_list($listname);
        if (empty($list)) {
            return $list;
        }
        $localnet = $list['localnets'];
        $wanip = $list['wanips'];
        $wangw = $list['wangateips'];
        $wandns = $list['wandnsips'];
        $vips = $list['vips'];
        $vpns = $list['vpnips'];

        if (is_array($list['address']['item']) && count($list['address']['item']) > 0) {
            foreach ($list['address']['item'] as $addr) {
                if (!$passlist) {
                    if (is_alias($addr) && (alias_get_type($addr) == "host" || alias_get_type($addr) == "network")) {
                        $home_net = array_merge($home_net, explode(" ", trim(filter_expand_alias($addr))));
                    } elseif (is_ipaddr($addr) || is_subnet($addr)) {
                        $home_net[] = $addr;
                    }
                } elseif ($passlist) {
                    if (is_alias($addr) && (alias_get_type($addr) == "host" || alias_get_type($addr) == "network")) {
                        $tmp = trim(filter_expand_alias($addr));
                        if (strlen($tmp) > 0) {
                            $home_net = array_merge($home_net, explode(" ", $tmp));
                        } elseif (!in_array($addr, $home_net)) {
                            $home_net[] = $addr;
                        }
                    } elseif (is_ipaddr($addr) || is_subnet($addr)) {
                        $home_net[] = $addr;
                    }
                }
            }
        }
    }

    if (!$externallist) {
        if (!in_array("127.0.0.1/32", $home_net)) {
            $home_net[] = "127.0.0.1/32";
        }
        if (!in_array("::1/128", $home_net)) {
            $home_net[] = "::1/128";
        }
    }

    /********************************************************************/
    /* Always put the interface running Suricata in HOME_NET and        */
    /* pass list unless it's the WAN.  WAN options are handled further  */
    /* down.  If the user specifically chose not to include LOCAL_NETS  */
    /* in the PASS LIST, then do not include the Suricata interface     */
    /* subnet in the PASS LIST. We do include the actual LAN interface  */
    /* IP for Suricata, though, to prevent locking out the firewall.    */
    /********************************************************************/
    $suricataip = get_interface_ip($suricatacfg['interface']);
    if (($externallist && $localnet == 'yes') || (!$externallist && $passlist && ($localnet == 'yes' || empty($localnet)))) {
        if (is_ipaddrv4($suricataip)) {
            if ($suricatacfg['interface'] <> "wan") {
                if ($sn = get_interface_subnet($suricatacfg['interface'])) {
                    $ip = gen_subnet($suricataip, $sn) . "/{$sn}";
                    if (!in_array($ip, $home_net)) {
                        $home_net[] = $ip;
                    }
                }
            }
        }
    }
    elseif (!$externallist && $localnet != 'yes') {
        if (is_ipaddrv4($suricataip) && $suricatacfg['interface'] <> "wan") {
            if (!in_array($suricataip . "/32", $home_net)) {
                $home_net[] = $suricataip . "/32";
            }
        }
    }

    $suricataip = get_interface_ipv6($suricatacfg['interface']);
    if (strpos($suricataip, "%") !== FALSE) {
        $suricataip = substr($suricataip, 0, strpos($suricataip, "%"));
    }
    if (($externallist && $localnet == 'yes') || ($passlist && ($localnet == 'yes' || empty($localnet))) || (!$externallist && !$passlist && ($localnet == 'yes' || empty($localnet)))) {
        if (is_ipaddrv6($suricataip)) {
            if ($suricatacfg['interface'] <> "wan") {
                if ($sn = get_interface_subnetv6($suricatacfg['interface'])) {
                    $ip = gen_subnetv6($suricataip, $sn). "/{$sn}";
                    if (!in_array($ip, $home_net)) {
                        $home_net[] = $ip;
                    }
                }
            }
        }
    }
    elseif (!$externallist && $localnet != 'yes') {
        if (is_ipaddrv6($suricataip) && $suricatacfg['interface'] <> "wan") {
            if (!in_array($suricataip . "/128", $home_net)) {
                $home_net[] = $suricataip . "/128";
            }
        }
    }

    /*$suricataip = get_interface_linklocal($suricatacfg['interface']); TODO
    if (!empty($suricataip) && $localnet == 'yes') {
        if (strpos($suricataip, "%") !== FALSE) {
            $suricataip = substr($suricataip, 0, strpos($suricataip, "%"));
        }
        if (!in_array($suricataip . "/128", $home_net)) {
            $home_net[] = $suricataip . "/128";
        }
    }*/

    // Now find all the locally-attached network subnets and add them to the
    // list if user chose to include Local Networks.
    if (($externallist && $localnet == 'yes') || ($passlist && ($localnet == 'yes' || empty($localnet))) || (!$externallist && !$passlist && ($localnet == 'yes' || empty($localnet)))) {
        /*************************************************************************/
        /*  Iterate through the interface list and write out pass list items and */
        /*  also compile a HOME_NET list of all local interfaces for suricata.   */
        /*  Skip the WAN interface as we do not typically want that whole subnet */
        /*  whitelisted (just the i/f IP itself which was handled earlier).      */
        /*************************************************************************/
        $int_array = legacy_config_get_interfaces();
        foreach ($int_array as $int => $inf) {
            if ($int == "wan") {
                continue;
            }
            $subnet = get_interface_ip($int);
            if (is_ipaddrv4($subnet)) {
                if ($sn = get_interface_subnet($int)) {
                    $ip = gen_subnet($subnet, $sn) . "/{$sn}";
                    if (!in_array($ip, $home_net)) {
                        $home_net[] = $ip;
                    }
                }
            }

            $subnet = get_interface_ipv6($int);
            // Trim off the interface designation (e.g., %em1) if present
            if (strpos($subnet, "%") !== FALSE) {
                $subnet = substr($subnet, 0, strpos($subnet, "%"));
            }
            if (is_ipaddrv6($subnet)) {
                if ($sn = get_interface_subnetv6($int)) {
                    $ip = gen_subnetv6($subnet, $sn). "/{$sn}";
                    if (!in_array($ip, $home_net)) {
                        $home_net[] = $ip;
                    }
                }
            }

            // Add link-local address TODO
            /* $suricataip = get_interface_linklocal($int);
            if (!empty($suricataip)) {
                // Trim off the interface designation (e.g., %em1) if present
                if (strpos($suricataip, "%") !== FALSE) {
                    $suricataip = substr($suricataip, 0, strpos($suricataip, "%"));
                }
                if (!in_array($suricataip . "/128", $home_net)) {
                    $home_net[] = $suricataip . "/128";
                }
            }*/
        }
    }

    // If user chose to include the WAN IP, then do so.
    if ($wanip == 'yes') {
        $ip = get_interface_ip("wan");
        if (is_ipaddrv4($ip)) {
            if (!in_array($ip . "/32", $home_net)) {
                $home_net[] = $ip . "/32";
            }
        }
        $ip = get_interface_ipv6("wan");
        // Trim off the interface designation (e.g., %em1) if present
        if (strpos($ip, "%") !== FALSE) {
            $ip = substr($ip, 0, strpos($ip, "%"));
        }
        if (is_ipaddrv6($ip)) {
            if (!in_array($ip . "/128", $home_net)) {
                $home_net[] = $ip . "/128";
            }
        }
        // Explicitly grab the WAN Link-Local address
        /*$ip = get_interface_linklocal("wan"); TODO
        if (!empty($ip)) {
            // Trim off the interface designation (e.g., %em1) if present
            if (strpos($ip, "%") !== FALSE) {
                $ip = substr($ip, 0, strpos($ip, "%"));
            }
            if (!in_array($ip . "/128", $home_net)) {
                $home_net[] = $ip . "/128";
            }
        }*/
    }

    // If the user chose to include WAN gateways, then do so.
    if ($wangw == 'yes') {
        // Grab the default gateway if set
        $default_gw = exec("/sbin/route -n get default |grep 'gateway:' | /usr/bin/awk '{ print $2 }'");
        if (is_ipaddrv4($default_gw) && !in_array($default_gw . "/32", $home_net)) {
            $home_net[] = $default_gw . "/32";
        }
        if (is_ipaddrv6($default_gw) && !in_array($default_gw . "/128", $home_net)) {
            $home_net[] = $default_gw . "/128";
        }

        // Get any other interface gateway and put in $HOME_NET if not there already

        $gateways = new \OPNsense\Routing\Gateways(legacy_interfaces_details());
        $gw = $gateways->getInterfaceGateway($suricatacfg['interface'], 'inet');
        if (is_ipaddrv4($gw) && !in_array($gw . "/32", $home_net)) {
            $home_net[] = $gw . "/32";
        }
        $gw = $gateways->getInterfaceGateway($suricatacfg['interface'], 'inet6');
        // Trim off the interface designation (e.g., %em1) if present
        if (strpos($gw, "%") !== FALSE) {
            $gw = substr($gw, 0, strpos($gw, "%"));
        }
        if (is_ipaddrv6($gw) && !in_array($gw . "/128", $home_net)) {
            $home_net[] = $gw . "/128";
        }
    }

    // If the user chose to include WAN DNS servers, then do so.
    if ($wandns == 'yes') {
        // Add DNS server for WAN interface to Pass List
        $dns_servers = get_nameservers(false, true);
        foreach ($dns_servers as $dns) {
            if (is_ipaddrv4($dns)) {
                $dns .= "/32";
            } else {
                $dns .= "/128";
            }
            if (!in_array($dns, $home_net)) {
                $home_net[] = $dns;
            }
        }
    }

    // If the user chose to include Virtual IPs, then do so.
    if($vips == 'yes') {
        // iterate all vips and add to passlist
        if (is_array($config['virtualip']) && is_array($config['virtualip']['vip'])) {
            foreach($config['virtualip']['vip'] as $vip) {
                if (is_ipaddrv4($vip['subnet'])) {
                    $ip = gen_subnet($vip['subnet'], $vip['subnet_bits']) . "/{$vip['subnet_bits']}";
                } else {
                    $ip = gen_subnetv6($vip['subnet'], $vip['subnet_bits']) . "/{$vip['subnet_bits']}";
                }
                if (!in_array($ip, $home_net)) {
                    $home_net[] = $ip;
                }
            }
        }
    }

    // If the user chose to include VPNs, then grab a list of
    // vpns enabled and include them.  These come back as CIDR
    // mask networks.
    if ($vpns == 'yes') {
        $vpns_list = suricata_get_vpns_list();
        if (!empty($vpns_list)) {
            // Convert the returned space-delimited string to an array
            // and then add each VPN address to our HOME_NET array.
            $vpns = explode(" ", $vpns_list);
            foreach ($vpns as $vpn) {
                $home_net[] = trim($vpn);
            }
            unset($vpns, $vpns_list);
        }
    }

    // Validate the HOME_NET entries
    $valresult = array();
    foreach ($home_net as $vald) {
        if (empty($vald) || (!is_subnet($vald) && !is_ipaddr($vald) && !is_alias($vald))) {
            continue;
        }
        $vald = trim($vald);
        if (empty($valresult[$vald])) {
            $valresult[$vald] = $vald;
        }
    }

    // Release memory no longer required
    unset($home_net);

    // Sort the list and return it
    natsort($valresult);
    return $valresult;
    return array();
}


function suricata_start($iface, $verbose = false) {
    global $config;

    $suricatacfg = suricata_get_interface_config($iface);
    if (empty($suricatacfg))
        return;

    $realif = get_real_interface($iface);

    if (!file_exists("{$suricatadir}suricata_{$realif}/suricata.yaml")) {
        service_log('Generating suricata.yaml file for ['.$realif.']', $verbose);
        suricata_generate_yaml($suricatacfg);
    }

    $suricatadir = SURICATADIR;
    $suricatalogdir = SURICATALOGDIR . "suricata_{$realif}";
    $suricatabindir = SURICATA_PBI_BINDIR;

    if (isset($suricatacfg['enabled']) && (intval($suricatacfg['enabled']) == 1) && ($realif != '')) {
        file_put_contents_with_mkdir("{$suricatalogdir}/suricata.log", '');
        $run_mode = $suricatacfg['ipsmode'] == 'inline' && $suricatacfg['blockoffenders'] == '1' ? '--netmap' : '-i ' . $realif;
        $verbose_logging = $suricatacfg['enableverboselogging'] == '1' ? "-vv" : "";
        syslog(LOG_NOTICE, "[Suricata] Suricata START for {$suricatacfg['descr']}({$realif})...");
        mwexec_bg("{$suricatabindir}suricata {$run_mode} -D -c {$suricatadir}suricata_{$realif}/suricata.yaml --pidfile /var/run/suricata_{$realif}.pid {$verbose_logging}");
    } else {
        return;
    }
}

function suricata_stop($iface, $verbose = false) {
    $suricatacfg = suricata_get_interface_config($iface);
    if (empty($suricatacfg))
        return;

    $realif = get_real_interface($iface);

    if (isvalidpid("/var/run/suricata_{$realif}.pid")) {
        syslog(LOG_NOTICE, "[Suricata] Suricata STOP for {$suricatacfg['descr']}({$realif})...");
        killbypid("/var/run/suricata_{$realif}.pid");
    }

    sleep(10);

    if (file_exists("/var/run/suricata_{$realif}.pid"))
        unlink("/var/run/suricata_{$realif}.pid");
}
