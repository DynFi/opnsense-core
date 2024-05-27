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
    $suricataver = shell_safe("{$suricatabindir}suricata -V | /usr/bin/tail -n 1 | /usr/bin/cut -c26-");
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


function suricata_get_configs() {
    $config = OPNsense\Core\Config::getInstance()->toArray(array('interface' => true));
    return (is_array($config['OPNsense']['Suricata']['interfaces']) ? $config['OPNsense']['Suricata']['interfaces']['interface'] : array());
}


function suricata_get_sidmods() {
    $config = OPNsense\Core\Config::getInstance()->toArray(array('sidmod' => true));
    return (is_array($config['OPNsense']['Suricata']['sidmods']) ? $config['OPNsense']['Suricata']['sidmods']['sidmod'] : array());
}


function suricata_get_suppress() {
    $config = OPNsense\Core\Config::getInstance()->toArray(array('suppressionlist' => true));
    return (is_array($config['OPNsense']['Suricata']['suppress']) ? $config['OPNsense']['Suricata']['suppress']['suppressionlist'] : array());
}


function suricata_services()
{
    global $config;

    $services = array();

    foreach (suricata_get_configs() as $suricatacfg) {
        if (isset($suricatacfg['enabled']) && $suricatacfg['enabled'] == 1) {
            $realif = get_real_interface($suricatacfg['iface']);
            $services[] = array(
                'name' => 'suricata_'.$suricatacfg['iface'],
                'description' => gettext('Suricata: '.$suricatacfg['iface']),
                'configd' => array(
                    'restart' => array('suricata restart '.$suricatacfg['iface']),
                    'start' => array('suricata start '.$suricatacfg['iface']),
                    'stop' => array('suricata stop '.$suricatacfg['iface']),
                ),
                'pidfile' => '/var/run/suricata_'.$realif.'.pid'
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


function suricata_cron()
{
    global $config;

    $jobs = array();

    if (empty(suricata_get_configs()))
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

    system_cron_configure();

    $configs = suricata_get_configs();

    if (empty($configs)) {
        $sidmods = suricata_get_sidmods();
        if (empty($sidmods)) {
            service_log('Creating sample SID files', $verbose);
            exec('/usr/local/sbin/configctl suricata createsamplesidlists');
        }
        service_log('No Suricata interfaces defined, exiting...', $verbose);
        return;
    }

    service_log('Generating Suricata configs...', $verbose);

    $errors = array();

    foreach ($configs as $suricatacfg) {

        suricata_stop($suricatacfg['iface']);

        if (isset($suricatacfg['enabled']) && $suricatacfg['enabled'] == 1) {
            service_log('Generating suricata.yaml file for ['.$suricatacfg['iface'].']', $verbose);
            try {
                suricata_generate_yaml($suricatacfg);
            } catch (Exception $e) {
                $errors[] = 'Generating Suricata configuration file failed for '.$suricatacfg['iface'];
            }

            service_log('Starting Suricata for ['.$suricatacfg['iface'].']', $verbose);
            if (!suricata_start($suricatacfg['iface'], $verbose)) {
                $errors[] = 'Starting Suricata service failed for '.$suricatacfg['iface'];
            }
        }
    }

    return $errors;
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

    foreach (suricata_get_configs() as $suricatacfg) {
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


function suricata_parse_sidconf_file($sidconf_file, $split_lines = TRUE) {

    /**********************************************/
    /* This function loads and processes the list */
    /* specified by '$sidconf_file'.  The list is */
    /* assumed to contain valid instructions for  */
    /* matching rule SIDs as supported by the     */
    /* Oinkmaster and PulledPork utilities.       */
    /*                                            */
    /*  $sidconf_file ==> name of SID Mgmt        */
    /*                    list to process         */
    /*                                            */
    /*  $split_lines ==> determines whether lines */
    /*                   should be split at       */
    /*                   commas into multiple     */
    /*                   sid modification	      */
    /*                                            */
    /*        Returns ==> an array containing     */
    /*                    SID modifier tokens     */
    /**********************************************/

    $buf = "";
    $sid_mods = array();
    $list = array();

    foreach (suricata_get_sidmods() as $sidmod) {
        if ($sidmod['name'] == $sidconf_file) {
             $list = $item;
             break;
        }
    }

    // Decode the list contents into a PHP temp buffer
    // we can read like a normal file.
    $fd = fopen("php://temp", "r+");
    if ($fd == FALSE) {
        syslog(LOG_ERR, "[Suricata] Failed to open SID MGMT list '{$sidconf_file}' for processing.");
        return $sid_mods;
    }
    fwrite($fd, base64_decode($list['content']));
    rewind($fd);

    // Read and parse the conf list line-by-line
    while (($buf = fgets($fd)) !== FALSE) {
        $line = array();

        // Skip any lines that may be just spaces.
        if (trim($buf, " \r\n") == "") {
            continue;
        }

        // Skip line with leading "#" since it's a comment
        if (preg_match('/^\s*#/', $buf)) {
            continue;
        }

        // Trim off any trailing comment
        $line = explode("#", $buf);

        // Trim leading and trailing spaces plus newline and any carriage returns
        $buf = trim($line[0], ' \r\n');

        if ($split_lines) {
            // If split mode split the SID mod arguments at the commas, if more than one
            // per line, and add to our $sid_mods array.
            $line = explode(",", $buf);
            foreach ($line as $ent) {
                $sid_mods[] = trim($ent);
            }
        }
        else{
            //Otherwise add 1 line as 1 modification to the $sid_mods array
            $sid_mods[] = $buf;
        }
    }

    // Close the file, release unneeded memory and return
    // the array of SID mod tokens parsed from the file.
    fclose($fd);
    unset($list, $line, $buf);
    return $sid_mods;
}


function suricata_sid_mgmt_list_exist($sid_mgmt_list) {

    /****************************************************/
    /* This function tests whether or not the passed    */
    /* automatic SID MGMT list exists in the config     */
    /* file for the firewall.                           */
    /*                                                  */
    /*   $sid_mgmt_list ==> name of SID Mgmt List       */
    /*                                                  */
    /*          Returns ==> TRUE if list exists, or     */
    /*                      FALSE if not found          */
    /*                                                  */
    /****************************************************/

    foreach (suricata_get_sidmods() as $list) {
        if ($list['name'] == $sid_mgmt_list) {
            return TRUE;
        }
    }

    return FALSE;
}


function suricata_get_auto_category_mods($categories, $sid_mods, $action, $log_results = FALSE, $log_file = NULL) {

    /****************************************************/
    /* This function parses the provided SID mod tokens */
    /* in $sid_mods and returns an array of category    */
    /* files that must be added ('enabled') or removed  */
    /* ('disabled') from the provided $categories list  */
    /* of enabled rule categories as determined by the  */
    /* content of the SID Mgmt tokens in $sid_mods.     */
    /*                                                  */
    /* The returned array shows which files should be   */
    /* removed and which should be added to the list    */
    /* used when building the enforcing ruleset.        */
    /*                                                  */
    /*   $categories ==> array of currently enabled     */
    /*                   ruleset categories             */
    /*     $sid_mods ==> array of SID modification      */
    /*                   tokens                         */
    /*       $action ==> modification action for        */
    /*                   matching category targets:     */
    /*                   'enable' or 'disable'          */
    /*  $log_results ==> [optional] 'yes' to log        */
    /*                   results to $log_file           */
    /*     $log_file ==> full path and filename of log  */
    /*                   file to write to               */
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

    $all_cats = array();
    $changes = array();
    $counter = 0;
    $matchcount = 0;

    // Get a list of all possible categories by loading all rules files
    $files = glob(SURICATA_RULES_DIR . "*.rules");
    foreach ($files as $file) {
        $all_cats[] = basename($file);
    }

    // Walk the SID mod tokens and decode looking for rule
    // category enable/disable changes.
    foreach ($sid_mods as $tok) {
        $matches = array();
        // Test the SID token for a GID:SID range and skip if true
        if (preg_match('/^(\d+):(\d+)-\1:(\d+)/', $tok)) {
            continue;
        // Test the token for a single GID:SID and skip if true
        } elseif (preg_match('/^(\d+):(\d+)$/', $tok)) {
            continue;
        // Test the token for the PCRE: keyword and skip if true
        } elseif (preg_match('/(^pcre\:)(.+)/i', $tok)) {
            continue;
        // Test the token for the MS reference keyword and skip if true
        } elseif (preg_match('/^MS\d+-.+/i', $tok)) {
            continue;
        // Test the token for other keywords delimited with a colon and skip if true
        } elseif (preg_match('/^[a-xA-X]+\:.+/', $tok)) {
            continue;
        // Test the SID token for a rule category name.  Anything that
        // failed to match above is considered a potential category name.
        } elseif (preg_match('/[a-xA-X]+(-|\w).*/', $tok, $matches)) {
            $counter++;
            $regex = "/" . preg_quote(trim($matches[0]), '/') . "/i";
            // Search through the $all_cats array for any matches to the regex
            $matches = preg_grep($regex, $all_cats);

            // See if any matches are in the $categories array
            foreach ($matches as $cat) {
                switch ($action) {
                    case 'enable':
                        if (!isset($changes[$cat])) {
                            $changes[$cat] = 'enabled';
                            if ($log_results == TRUE && !empty($log_file)) {
                                error_log(gettext("    Enabled rule category: {$cat}\n"), 3, $log_file);
                            }
                            $matchcount++;
                        }
                        break;

                    case 'disable':
                        if (!isset($changes[$cat])) {
                            $changes[$cat] = 'disabled';
                            if ($log_results == TRUE && !empty($log_file)) {
                                error_log(gettext("    Disabled rule category: {$cat}\n"), 3, $log_file);
                            }
                            $matchcount++;
                        }
                        break;

                    default:
                        break;
                }
            }
        }
        else {
            if ($log_results == TRUE && !empty($log_file)) {
                error_log(gettext("WARNING: unrecognized token '{$tok}' encountered while processing an automatic SID MGMT file.\n"), 3, $log_file);
            }
        }
    }

    if ($log_results == TRUE && !empty($log_file)) {
        error_log(gettext("    Parsed {$counter} potential Rule Categories to match from the list of tokens.\n"), 3, $log_file);
        error_log(gettext("    " . ucfirst($action) . "d {$matchcount} matching Rule Categories.\n"), 3, $log_file);
    }

    // Release memory no longer needed
    unset($all_cats, $matches);

    // Return array of rule category file changes
    return $changes;
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
        $log_file = SURICATALOGDIR . "suricata_" . get_real_interface($suricatacfg['interface']) . "/sid_changes.log";
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


function suricata_process_enablesid(&$rule_map, $suricatacfg, $log_results = FALSE, $log_file = NULL) {

    /**********************************************/
    /* This function loads and processes the list */
    /* specified by 'enable_sid_file' for the     */
    /* interface.  The list is assumed to be a    */
    /* valid enablesid.conf list containing       */
    /* instructions for enabling matching rule    */
    /* SIDs.                                      */
    /*                                            */
    /*     $rule_map ==> reference to array of    */
    /*                   current rules            */
    /*  $suricatacfg ==> interface config params  */
    /*  $log_results ==> [optional] 'yes' to log  */
    /*                   results to $log_file     */
    /*     $log_file ==> full path and filename   */
    /*                   of log file to write to  */
    /*                                            */
    /*     On Return ==> suitably modified        */
    /*                   $rule_map array          */
    /**********************************************/

    $suricatalogdir = SURICATALOGDIR;
    $sid_mods = array();

    // If no rules in $rule_map, then nothing to do
    if (empty($rule_map)) {
        return;
    }

    // Verify the 'enable_sid' list for the interface exists
    if (!suricata_sid_mgmt_list_exist($suricatacfg['enablesidfile'])) {
        syslog(LOG_ERR, gettext("[Suricata] ERROR: unable to find enable_sid list \"{$suricatacfg['enablesidfile']}\" specified for " . $suricatacfg['iface']));
        return;
    }
    else
        $sid_mods = suricata_parse_sidconf_file($suricatacfg['enablesidfile']);

    if (!empty($sid_mods))
        suricata_modify_sid_state($rule_map, $sid_mods, "enable", $log_results, $log_file);
    elseif ($log_results == TRUE && !empty($log_file)) {
        error_log(gettext("WARNING: no valid SID match tokens found in list \"{$suricatacfg['enablesidfile']}\".\n"), 3, $log_file);
    }

    unset($sid_mods);
}

function suricata_process_disablesid(&$rule_map, $suricatacfg, $log_results = FALSE, $log_file = NULL) {

    /**********************************************/
    /* This function loads and processes the list */
    /* specified by 'disable_sid_file' for the    */
    /* interface.  The list is assumed to be a    */
    /* valid disablesid.conf list containing      */
    /* instructions for disabling matching rule   */
    /* SIDs.                                      */
    /*                                            */
    /*     $rule_map ==> reference to array of    */
    /*                   current rules            */
    /*  $suricatacfg ==> interface config params  */
    /*  $log_results ==> [optional] 'yes' to log  */
    /*                   results to $log_file     */
    /*     $log_file ==> full path and filename   */
    /*                   of log file to write to  */
    /*                                            */
    /*     On Return ==> suitably modified        */
    /*                   $rule_map array          */
    /**********************************************/

    $suricatalogdir = SURICATALOGDIR;
    $sid_mods = array();

    // If no rules in $rule_map, then nothing to do
    if (empty($rule_map)) {
        return;
    }

    // Verify the 'disable_sid' list for the interface exists
    if (!suricata_sid_mgmt_list_exist($suricatacfg['disablesidfile'])) {
        syslog(LOG_ERR, gettext("[Suricata] ERROR: unable to find disable_sid list \"{$suricatacfg['disablesidfile']}\" specified for " . $suricatacfg['iface']));
        return;
    } else {
        $sid_mods = suricata_parse_sidconf_file($suricatacfg['disablesidfile']);
    }

    if (!empty($sid_mods)) {
        suricata_modify_sid_state($rule_map, $sid_mods, "disable", $log_results, $log_file);
    } elseif ($log_results == TRUE && !empty($log_file)) {
        error_log(gettext("WARNING: no valid SID match tokens found in list \"{$suricatacfg['disablesidfile']}\".\n"), 3, $log_file);
    }

    unset($sid_mods);
}

function suricata_process_modifysid(&$rule_map, $suricatacfg, $log_results = FALSE, $log_file = NULL) {

    /**********************************************/
    /* This function loads and processes the list */
    /* specified by 'modify_sid_file' for the     */
    /* interface.  The list is assumed to be a    */
    /* valid modifysid.conf list containing       */
    /* instructions for modifying matching rule   */
    /* SIDs.                                      */
    /*                                            */
    /*     $rule_map ==> reference to array of    */
    /*                   current rules            */
    /*  $suricatacfg ==> interface config params  */
    /*  $log_results ==> [optional] 'yes' to log  */
    /*                   results to $log_file     */
    /*     $log_file ==> full path and filename   */
    /*                   of log file to write to  */
    /*                                            */
    /*     On Return ==> suitably modified        */
    /*                   $rule_map array          */
    /**********************************************/

    $suricatalogdir = SURICATALOGDIR;
    $sid_mods = array();

    // If no rules in $rule_map, then nothing to do
    if (empty($rule_map)) {
        return;
    }

    // Verify the 'modify_sid' list for the interface exists
    if (!suricata_sid_mgmt_list_exist($suricatacfg['modifysidfile'])) {
        syslog(LOG_ERR, gettext("[Suricata] ERROR: unable to find modify_sid list \"{$suricatacfg['modifysidfile']}\" specified for " . $suricatacfg['iface']));
        return;
    } else {
        $sid_mods = suricata_parse_sidconf_file($suricatacfg['modifysidfile'],FALSE);
    }

    if (!empty($sid_mods)) {
        suricata_modify_sid_content($rule_map, $sid_mods, $log_results, $log_file);
    } elseif ($log_results == TRUE && !empty($log_file)) {
        error_log(gettext("WARNING: no valid SID match tokens found in list \"{$suricatacfg['modifysidfile']}\".\n"), 3, $log_file);
    }

    unset($sid_mods);
}

function suricata_process_dropsid(&$rule_map, $suricatacfg, $log_results = FALSE, $log_file = NULL) {

    /**********************************************/
    /* This function loads and processes the list */
    /* specified by 'drop_sid_file' for the       */
    /* interface.  The list is assumed to be a    */
    /* valid dropsid.conf list containing         */
    /* instructions for modifying the action for  */
    /* matching rule SIDs.                        */
    /*                                            */
    /*     $rule_map ==> reference to array of    */
    /*                   current rules            */
    /*  $suricatacfg ==> interface config params  */
    /*  $log_results ==> [optional] 'yes' to log  */
    /*                   results to $log_file     */
    /*     $log_file ==> full path and filename   */
    /*                   of log file to write to  */
    /*                                            */
    /*     On Return ==> suitably modified        */
    /*                   $rule_map array          */
    /**********************************************/

    $suricatalogdir = SURICATALOGDIR;
    $sid_mods = array();

    // If no rules in $rule_map, then nothing to do
    if (empty($rule_map)) {
        return;
    }

    // Verify the 'drop_sid' list for the interface exists
    if (!suricata_sid_mgmt_list_exist($suricatacfg['dropsidfile'])) {
        syslog(LOG_ERR, gettext("[Suricata] ERROR: unable to find drop_sid list \"{$suricatacfg['dropsidfile']}\" specified for " . $suricatacfg['iface']));
        return;
    } else {
        $sid_mods = suricata_parse_sidconf_file($suricatacfg['dropsidfile']);
    }

    if (!empty($sid_mods)) {
        suricata_modify_sid_state($rule_map, $sid_mods, "drop", $log_results, $log_file);
    } elseif ($log_results == TRUE && !empty($log_file)) {
        error_log(gettext("WARNING: no valid SID match tokens found in list \"{$suricatacfg['dropsidfile']}\".\n"), 3, $log_file);
    }

    unset($sid_mods);
}

function suricata_process_rejectsid(&$rule_map, $suricatacfg, $log_results = FALSE, $log_file = NULL) {

    /**********************************************/
    /* This function loads and processes the list */
    /* specified by 'reject_sid_file' for the     */
    /* interface.  The list is assumed to be a    */
    /* valid rejectsid.conf list containing       */
    /* instructions for modifying the action for  */
    /* matching rule SIDs.                        */
    /*                                            */
    /*     $rule_map ==> reference to array of    */
    /*                   current rules            */
    /*  $suricatacfg ==> interface config params  */
    /*  $log_results ==> [optional] 'yes' to log  */
    /*                   results to $log_file     */
    /*     $log_file ==> full path and filename   */
    /*                   of log file to write to  */
    /*                                            */
    /*     On Return ==> suitably modified        */
    /*                   $rule_map array          */
    /**********************************************/

    $suricatalogdir = SURICATALOGDIR;
    $sid_mods = array();

    // If no rules in $rule_map, then nothing to do
    if (empty($rule_map)) {
        return;
    }

    // Verify the 'reject_sid' list for the interface exists
    if (!suricata_sid_mgmt_list_exist($suricatacfg['rejectsidfile'])) {
        syslog(LOG_ERR, gettext("[Suricata] ERROR: unable to find reject_sid list \"{$suricatacfg['rejectsidfile']}\" specified for " . $suricatacfg['iface']));
        return;
    } else {
        $sid_mods = suricata_parse_sidconf_file($suricatacfg['rejectsidfile']);
    }

    if (!empty($sid_mods)) {
        suricata_modify_sid_state($rule_map, $sid_mods, "reject", $log_results, $log_file);
    } elseif ($log_results == TRUE && !empty($log_file)) {
        error_log(gettext("WARNING: no valid SID match tokens found in list \"{$suricatacfg['rejectsidfile']}\".\n"), 3, $log_file);
    }

    unset($sid_mods);
}


function suricata_modify_sid_state(&$rule_map, $sid_mods, $action, $log_results = FALSE, $log_file = NULL) {

    /**********************************************/
    /* This function walks the provided array of  */
    /* SID modification tokens and locates the    */
    /* target SID or SIDs in the $rule_map array. */
    /* It then performs the change specified by   */
    /* $action on the target SID or SIDs.         */
    /*                                            */
    /*    $rule_map ==> reference to array of     */
    /*                  current rules             */
    /*    $sid_mods ==> array of SID modification */
    /*                  tokens                    */
    /*      $action ==> modification action for   */
    /*                  matching SID targets:     */
    /*                  'enable', 'disable',      */
    /*		    'drop' or 'reject'.       */
    /* $log_results ==> [optional] 'yes' to log   */
    /*                   results to $log_file     */
    /*    $log_file ==> full path and filename    */
    /*                  of log file to write to   */
    /*                                            */
    /*    On Return ==> $rule_map array modified  */
    /*                  by changing state for     */
    /*                  matching SIDs.            */
    /*                                            */
    /*                  Returns a two-dimension   */
    /*                  array of matching GID:SID */
    /*                  pairs.                    */
    /**********************************************/

    $sids = array();
    $log_action = '';
    $log_what = '';

    // If no rules in $rule_map or mods in $sid_mods,
    // then nothing to do.
    if (empty($rule_map) || empty($sid_mods)) {
        return $sids;
    }

    // Validate the action keyword as we only accept
    // 'enable', 'disable' and 'drop' as valid.
    switch ($action) {

        case "enable":
            $log_what = 'state';
            $log_action = 'enabled';
            break;

        case "disable":
            $log_what = 'state';
            $log_action = 'disabled';
            break;

        case "drop":
            $log_what = 'action';
            $log_action = 'drop';
            break;

        case "reject":
            $log_what = 'action';
            $log_action = 'reject';
            break;

        default:
            syslog(LOG_WARNING, gettext("[Suricata] WARNING - unknown action '{$action}' supplied to suricata_modify_sid_state() function...no SIDs modified."));
            return $sids;
    }

    // Walk the SID mod tokens and decode each one.
    // Place all matching GID:SID combos along with
    // the requested $action into our $sids[] array.
    foreach ($sid_mods as $tok) {
        $matches = array();
        // Test the SID token for a GID:SID range
        if (preg_match('/^(\d+):(\d+)-\1:(\d+)/', $tok, $matches)) {
            // It was a range, so find all the intervening SIDs
            $gid = trim($matches[1]);
            $lsid = trim($matches[2]);
            $usid = trim($matches[3]);
            $sids[$gid][$lsid] = $action;
            while ($lsid < $usid) {
                $lsid++;
                $sids[$gid][$lsid] = $action;
            }
        }
        // Test the SID token for a single GID:SID
        elseif (preg_match('/^(\d+):(\d+)$/', $tok, $matches)) {
            // It's a single GID:SID, so grab it
            $sids[$matches[1]][$matches[2]] = $action;
        }
        // Test the SID token for the PCRE: keyword
        elseif (preg_match('/(^pcre\:)(.+)/i', $tok, $matches)) {
            $regex = '/' . preg_quote($matches[2], '/') . '/i';

            // Now search through the $rule_map in the 'rule'
            // element for any matches to the regex and get
            // the GID:SID.
            foreach ($rule_map as $k1 => $rulem) {
                foreach ($rulem as $k2 => $v) {
                    if (preg_match($regex, $v['rule'])) {
                        $sids[$k1][$k2] = $action;
                    }
                }
            }
        }
        // Test the SID token for the MS reference keyword
        elseif (preg_match('/^MS\d+-.+/i', $tok, $matches)) {
            $regex = "/" . $matches[0] . "/i";

            // Now search through the $rule_map in the 'rule'
            // element for any matches to the regex and get
            // the GID:SID.
            foreach ($rule_map as $k1 => $rulem) {
                foreach ($rulem as $k2 => $v) {
                    if (preg_match($regex, $v['rule'])) {
                        $sids[$k1][$k2] = $action;
                    }
                }
            }
        }
        // Test the SID token for other keywords delimited with a colon
        elseif (preg_match('/^[a-xA-X]+\:.+/', $tok, $matches)) {
            $regex = "/" . str_replace(':', ",", preg_quote($matches[0], '/')) . "/i";

            // Now search through the $rule_map in the 'rule'
            // element for any matches to the regex and get
            // the GID:SID.
            foreach ($rule_map as $k1 => $rulem) {
                foreach ($rulem as $k2 => $v) {
                    if (preg_match($regex, $v['rule'])) {
                        $sids[$k1][$k2] = $action;
                    }
                }
            }
        }
        // Test the SID token for a rule category name.  Anything that
        // failed to match above is considered a potential category name.
        elseif (preg_match('/[a-xA-X]+(-|\w).*/', $tok, $matches)) {
            $regex = "/" . preg_quote(trim($matches[0]), '/') . "/i";
            // Now search through the $rule_map in the 'category'
            // element for any matches to the regex and get
            // the GID:SID.
            foreach ($rule_map as $k1 => $rulem) {
                foreach ($rulem as $k2 => $v) {
                    if (preg_match($regex, $v['category'] . ".rules")) {
                        $sids[$k1][$k2] = $action;
                    }
                }
            }
        }
        else {
            if ($log_results == TRUE && !empty($log_file)) {
                error_log(gettext("WARNING: unrecognized token '{$tok}' encountered while processing an automatic SID MGMT file.\n"), 3, $log_file);
            }
        }
    }

    // Now walk our $sids[] array populated above and change
    // the state or action of all the matching GID:SID pairs
    // in the $rule_map array passed to us.
    $modcount = $changecount = 0;
    $counter = count($sids, COUNT_RECURSIVE) - count($sids);

    if ($log_results == TRUE && !empty($log_file)) {
        error_log(gettext("    Parsed {$counter} potential SIDs to match from the provided list of tokens.\n"), 3, $log_file);
    }

    foreach (array_keys($sids) as $k1) {
        foreach (array_keys($sids[$k1]) as $k2) {
            if (isset($rule_map[$k1][$k2])) {
                if ($action == 'enable' && $rule_map[$k1][$k2]['disabled'] == 1) {
                    $rule_map[$k1][$k2]['rule'] = ltrim($rule_map[$k1][$k2]['rule'], " \t#");
                    $rule_map[$k1][$k2]['disabled'] = 0;
                    $rule_map[$k1][$k2]['managed'] = 1;
                    $rule_map[$k1][$k2]['state_toggled'] = 1;
                    $changecount++;
                    $modcount++;
                }
                elseif ($action == 'disable' && $rule_map[$k1][$k2]['disabled'] == 0) {
                    $rule_map[$k1][$k2]['rule'] = "# " . $rule_map[$k1][$k2]['rule'];
                    $rule_map[$k1][$k2]['disabled'] = 1;
                    $rule_map[$k1][$k2]['managed'] = 1;
                    $rule_map[$k1][$k2]['state_toggled'] = 1;
                    $changecount++;
                    $modcount++;
                }
                elseif ($action == 'drop' && $rule_map[$k1][$k2]['action'] != 'drop' && !in_array('noalert', $rule_map[$k1][$k2]['flowbits']) && $rule_map[$k1][$k2]['noalert'] == 0) {
                    if ($tmp = preg_replace('/\s*alert\s*/', 'drop ', $rule_map[$k1][$k2]['rule'], 1)) {
                        $rule_map[$k1][$k2]['rule'] = $tmp;
                        $rule_map[$k1][$k2]['action'] = 'drop';
                        $rule_map[$k1][$k2]['managed'] = 1;
                        $rule_map[$k1][$k2]['modified'] = 1;
                        $changecount++;
                        $modcount++;
                    }
                }
                elseif ($action == 'reject' && $rule_map[$k1][$k2]['action'] != 'reject' && !in_array('noalert', $rule_map[$k1][$k2]['flowbits']) && $rule_map[$k1][$k2]['noalert'] == 0) {
                    if ($tmp = preg_replace('/\s*alert\s*/', 'reject ', $rule_map[$k1][$k2]['rule'], 1)) {
                        $rule_map[$k1][$k2]['rule'] = $tmp;
                        $rule_map[$k1][$k2]['action'] = 'reject';
                        $rule_map[$k1][$k2]['managed'] = 1;
                        $rule_map[$k1][$k2]['modified'] = 1;
                        $changecount++;
                        $modcount++;
                    }
                }
            }
        }
    }

    if ($log_results == TRUE && !empty($log_file)) {
        error_log(gettext("    Found {$modcount} matching SIDs in the active rules.\n"), 3, $log_file);
        error_log(gettext("    Changed {$log_what} for {$changecount} SIDs to '{$log_action}'.\n"), 3, $log_file);
    }

    // Return the array of matching SIDs
    return $sids;
}


function suricata_modify_sid_content(&$rule_map, $sid_mods, $log_results = FALSE, $log_file = NULL) {

    /************************************************/
    /* This function walks the provided array of    */
    /* SID modification tokens and locates the      */
    /* target SID or SIDs in the $rule_map array.   */
    /* It then modifies the content of the target   */
    /* SID or SIDs. Modifications are only valid    */
    /* for normal GID=1 text rules.                 */
    /*                                              */
    /*     $rule_map ==> reference to array of      */
    /*                   current rules              */
    /*     $sid_mods ==> array of SID modification  */
    /*                   tokens                     */
    /*  $log_results ==> [optional] 'yes' to log    */
    /*                   results to $log_file       */
    /*     $log_file ==> full path and filename     */
    /*                   of log file to write to    */
    /*                                              */
    /*     On Return ==> $rule_map array modified   */
    /*                   by changing content for    */
    /*                   matching SIDs.             */
    /*                                              */
    /*                   Returns a two-dimension    */
    /*                   array of matching          */
    /*                   GID:SID pairs.             */
    /************************************************/

    $sids = array();
    $tokencounter = $modcount = $modifiedcount = 0;

    // If no rules in $rule_map or mods in $sid_mods,
    // then nothing to do.
    if (empty($rule_map) || empty($sid_mods)) {
        return $sids;
    }

    // Walk the SID mod tokens and decode each one
    foreach ($sid_mods as $tok) {
        $matches = array();
        if (preg_match('/((?<sid_category_list> (?<sid_category_plus_comma>[\da-z-_*]+,)* (?<last_sid_cageory>[\da-z-_*]+)) \s+)? "(?<from>.*)" \s+ "(?<to>.*)"/x', $tok, $matches)) {
            // If a valid config token is found. A valid token is:
            // An optional group of a comma separated list(sid_category_list) of SIDs(numbers) or categories(lowercase chars,numbers,- or _) plus whitespaces followed by
            // a search term(from) framed by double quotes followed
            // whitespaces followed by
            // a replacement(to) framed by double quotes

            $tokencounter++;
            $from = '/' . preg_quote($matches['from'], '/') . '/';
            $to = $matches['to'];
            $count = 0;

            if ($matches['sid_category_list'] == "") {
                // If the sidlist is empty it's the same as a wildcard, therefore create a list with just one wildcard item
                $sidlist = array("*");
            }
            else {
                // Otherwise split the list
                $sidlist = explode(",", $matches['sid_category_list']);
            }


            // Walk over all sids
            foreach ($sidlist as $sid) {
                if (isset($rule_map[1][$sid])) {
                    // If sid is present change modify it
                    $modcount++;
                    $rule_map[1][$sid]['rule'] = preg_replace($from, $to, $rule_map[1][$sid]['rule'], -1, $count);
                    if ($count > 0) {
                        $rule_map[1][$sid]['managed'] = 1;
                        $rule_map[1][$sid]['modified'] = 1;
                        $sids[1][$sid] = 'modify';
                        $modifiedcount++;
                    }
                }
                else {
                    // Otherwise it's a wildcard or category modification
                    if (is_array($rule_map)) {
                        // If rule_map is an array walk over it to find all rules which need be be modified
                        foreach ($rule_map[1] as $k2 => $v) {
                            if ($sid == "*" || $v['category'] == $sid) {
                                // If a wildcard changed is handled or the category is matching the rule needs to get modified
                                $modcount++;
                                $rule_map[1][$k2]['rule'] = preg_replace($from, $to, $v['rule'], -1, $count);
                                if ($count > 0) {
                                    $rule_map[1][$k2]['managed'] = 1;
                                    $rule_map[1][$k2]['modified'] = 1;
                                    $sids[1][$k2] = 'modify';
                                    $modifiedcount++;
                                }
                            }
                        }
                    }
                }
            }
        }
        else {
            if ($log_results == TRUE && !empty($log_file))
                error_log(gettext("WARNING: unrecognized token '{$tok}' encountered while processing an automatic SID MGMT file.\n"), 3, $log_file);
        }
    }

    if ($log_results == TRUE && !empty($log_file)) {
        error_log(gettext("    Parsed {$tokencounter} potential SIDs to match from the provided list of tokens.\n"), 3, $log_file);
        error_log(gettext("    Found {$modcount} matching SIDs in the active rules.\n"), 3, $log_file);
        error_log(gettext("    Modified rule text for {$modifiedcount} SIDs.\n"), 3, $log_file);
    }

    // Return the array of matching SIDs
    return $sids;
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
        $log_file = SURICATALOGDIR . "suricata_" . $suricatacfg['interface'] . "/sid_changes.log";
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


function suricata_load_sid_mods($sids) {

    /*****************************************/
    /* This function parses the string of    */
    /* SID values in $sids and returns an    */
    /* array with the SID as the key and     */
    /* value.  The SID values in $sids are   */
    /* assumed to be delimited by "||".      */
    /*                                       */
    /* $sids ==> string of SID values from   */
    /*           saved config file.          */
    /*                                       */
    /* Returns ==> a multidimensional array  */
    /*             with GID and SID as the   */
    /*             keys ($result[GID][SID])  */
    /*****************************************/

    $result = array();
    if (empty($sids)) {
        return $result;
    }
    $tmp = explode("||", $sids);
    foreach ($tmp as $v) {
        if (preg_match('/(\d+)\s*:\s*(\d+)/', $v, $match)) {
            if (!is_array($result[$match[1]])) {
                $result[$match[1]] = array();
            }
            $result[$match[1]][$match[2]] = "{$match[1]}:{$match[2]}";
        }
    }
    unset($tmp);

    return $result;
}


function suricata_modify_sids(&$rule_map, $suricatacfg) {

    /***********************************************/
    /* This function modifies the rules in the     */
    /* passed rules_map array based on values in   */
    /* the enablesid/disablesid configuration      */
    /* parameters for the interface.               */
    /*                                             */
    /*  $rule_map = array of current rules         */
    /*  $suricatacfg = interface config settings   */
    /***********************************************/

    if (!isset($suricatacfg['rulesidon']) &&
        !isset($suricatacfg['rulesidoff'])) {
        return;
    }

    // Load up our enablesid and disablesid
    // arrays with lists of modified SIDs.
    $enablesid = suricata_load_sid_mods($suricatacfg['rulesidon'], "enablesid");
    $disablesid = suricata_load_sid_mods($suricatacfg['rulesidoff'], "disablesid");

    /* Turn on any rules that need to be     */
    /* forced "on" with enablesid mods.      */
    if (!empty($enablesid)) {
        foreach ($rule_map as $k1 => $rulem) {
            foreach ($rulem as $k2 => $v) {
                if (isset($enablesid[$k1][$k2]) && $v['disabled'] == 1) {
                    $rule_map[$k1][$k2]['rule'] = ltrim($v['rule'], " \t#");
                    $rule_map[$k1][$k2]['disabled'] = 0;
                }
            }
        }
    }

    /* Turn off any rules that need to be    */
    /* forced "off" with disablesid mods.    */
    if (!empty($disablesid)) {
        foreach ($rule_map as $k1 => $rulem) {
            foreach ($rulem as $k2 => $v) {
                if (isset($disablesid[$k1][$k2]) && $v['disabled'] == 0) {
                    $rule_map[$k1][$k2]['rule'] = "# " . $v['rule'];
                    $rule_map[$k1][$k2]['disabled'] = 1;
                }
            }
        }
    }
    unset($enablesid, $disablesid);
}


function suricata_modify_sids_action(&$rule_map, $suricatacfg) {

    /***********************************************/
    /* This function modifies the rules in the     */
    /* passed rules_map array based on values in   */
    /* the alertsid/dropsid configuration          */
    /* parameters for the interface.               */
    /*                                             */
    /*  $rule_map = array of current rules         */
    /*  $suricatacfg = interface config settings   */
    /***********************************************/

    if (!isset($suricatacfg['rulesidforcealert']) &&
        !isset($suricatacfg['rulesidforcedrop']) &&
        !isset($suricatacfg['rulesidforcereject'])) {
        return;
    }

    /* Load up our SID forced action arrays with manually changed SID actions */
    $alertsid = suricata_load_sid_mods($suricatacfg['rulesidforcealert']);

    /* DROP is only applicable when blocking offenders is enabled and when    */
    /* 'block drops only' is enabled, or when using Inline IPS Mode.          */
    if ($suricatacfg['blockoffenders'] == '1' && ($suricatacfg['blockdropsonly'] == '1' || $suricatacfg['ipsmode'] == 'inline')) {
        $dropsid = suricata_load_sid_mods($suricatacfg['rulesidforcedrop']);
    }
    else {
        $dropsid = array();
    }

    /* REJECT is only applicable when blocking offenders is enabled and when  */
    /* Inline IPS Mode is also enabled.                                       */
    if ($suricatacfg['blockoffenders'] == '1' && $suricatacfg['ipsmode'] == 'inline') {
        $rejectsid = suricata_load_sid_mods($suricatacfg['rulesidforcereject']);
    }
    else {
        $rejectsid = array();
    }

    /* Change action for any rules that need to be */
    /* forced to "alert" with alertsid mods.       */
    if (!empty($alertsid)) {
        foreach ($rule_map as $k1 => $rulem) {
            foreach ($rulem as $k2 => $v) {
                if (isset($alertsid[$k1][$k2]) && $v['action'] != 'alert') {
                    $matches = array();
                    if (preg_match('/^\s*#*\s*(drop|pass|reject)/i', $v['rule'], $matches)) {
                        $txt_regx = '/^\s*' . "{$matches[1]}" . '\s/';
                        if ($tmp = preg_replace($txt_regx, 'alert ', $v['rule'], 1)) {
                            $rule_map[$k1][$k2]['rule'] = $tmp;
                            $rule_map[$k1][$k2]['action'] = 'alert';
                        }
                    }
                }
            }
        }
    }

    /* Change action for any rules that need to be */
    /* forced to "drop" with dropsid mods.         */
    if (!empty($dropsid)) {
        foreach ($rule_map as $k1 => $rulem) {
            foreach ($rulem as $k2 => $v) {
                if (isset($dropsid[$k1][$k2]) && $v['action'] != 'drop') {
                    $matches = array();
                    if (preg_match('/^\s*#*\s*(alert|pass|reject)/i', $v['rule'], $matches)) {
                        $txt_regx = '/^\s*' . "{$matches[1]}" . '\s/';
                        if ($tmp = preg_replace($txt_regx, 'drop ', $v['rule'], 1)) {
                            $rule_map[$k1][$k2]['rule'] = $tmp;
                            $rule_map[$k1][$k2]['action'] = 'drop';
                        }
                    }
                }
            }
        }
    }

    /* Change action for any rules that need to be */
    /* forced to "reject" with rejectsid mods.     */
    if (!empty($rejectsid)) {
        foreach ($rule_map as $k1 => $rulem) {
            foreach ($rulem as $k2 => $v) {
                if (isset($rejectsid[$k1][$k2]) && $v['action'] != 'reject') {
                    $matches = array();
                    if (preg_match('/^\s*#*\s*(alert|pass|drop)/i', $v['rule'], $matches)) {
                        $txt_regx = '/^\s*' . "{$matches[1]}" . '\s/';
                        if ($tmp = preg_replace($txt_regx, 'reject ', $v['rule'], 1)) {
                            $rule_map[$k1][$k2]['rule'] = $tmp;
                            $rule_map[$k1][$k2]['action'] = 'reject';
                        }
                    }
                }
            }
        }
    }

    unset($alertsid, $dropsid, $rejectsid);
}


function suricata_get_sid($rule) {

    /***************************************************************/
    /* If a sid is defined, then return it, else default to an     */
    /* empty value.                                                */
    /***************************************************************/

    if (preg_match('/\bsid\s*:\s*(\d+)\s*;/i', $rule, $matches)) {
        return trim($matches[1]);
    } else {
        return "";
    }
}


function suricata_get_gid($rule) {

    /****************************************************************/
    /* If a gid is defined, then return it, else default to "1" for */
    /* general text rules match.                                    */
    /****************************************************************/

    if (preg_match('/\bgid\s*:\s*(\d+)\s*;/i', $rule, $matches)) {
        return trim($matches[1]);
    } else {
        return "1";
    }
}


function suricata_get_msg($rule) {

    /**************************************************************/
    /* Return the MSG section of the passed rule as a string.     */
    /**************************************************************/

    $msg = "";
    if (preg_match('/\bmsg\s*:\s*"(.+?)"\s*;/i', $rule, $matches)) {
        $msg = trim($matches[1]);
    }
    return $msg;
}


function suricata_get_flowbits($rule) {

    /*************************************************************/
    /* This will pull out "flowbits:" options from the rule text */
    /* and return them in an array (minus the "flowbits:" part). */
    /*************************************************************/

    $flowbits = array();

    // Grab any "flowbits:set, setx, unset, isset or toggle" options first.
    // Examine flowbits targets for logical operators to capture all targets.
    if (preg_match_all('/flowbits\b\s*:\s*(set|setx|unset|toggle|isset|isnotset)\s*,([^;]+)/i', $rule, $matches)) {
        $i = -1;
        while (++$i < count($matches[1])) {
            $action = trim($matches[1][$i]);
            $target = preg_split('/[&|]/', $matches[2][$i]);
            foreach ($target as $t) {
                $flowbits[] = "{$action}," . trim($t);
            }
        }
    }

    // Include the "flowbits:noalert or reset" options, if present.
    if (preg_match_all('/flowbits\b\s*:\s*(noalert|reset)\b/i', $rule, $matches)) {
        $i = -1;
        while (++$i < count($matches[1])) {
            $flowbits[] = trim($matches[1][$i]);
        }
    }

    return $flowbits;
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

    global $config;

    $flowbit_rules_file = FLOWBITS_FILENAME;
    $suricata_enforcing_rules_file = SURICATA_ENFORCING_RULES_FILENAME;
    $enabled_rules = array();
    $enabled_files = array();
    $all_rules = array();
    $cat_mods = array();
    $no_rules_defined = true;

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


function suricata_find_list($uuid, $type = 'passlists') {
    global $config;

    if ($uuid) {

        $itemname = substr($type, 0, -1);

        if (isset($config['OPNsense']['Suricata'][$type][$itemname])) {

            $items = isset($config['OPNsense']['Suricata'][$type][$itemname]['name']) ? [ $config['OPNsense']['Suricata'][$type][$itemname] ] : $config['OPNsense']['Suricata'][$type][$itemname];

            foreach ($items as $value) {

                if ($value['@attributes']['uuid'] == $uuid) {
                    $value['address'] = array('item' => array());
                    foreach (explode(',', $value['addresses']) as $a) {
                        $value['address']['item'][] = $a;
                    }
                    return $value;
                }
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
    if (isset($config['ipsec']) && is_array($config['ipsec'])) {
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
    }

    /* OpenVPN */
    if (isset($config['openvpn']) && is_array($config['openvpn'])) {
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
    }

    /* PPPoE Server */
    if (isset($config['pppoes']) && is_array($config['pppoes'])) {
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
    }

    /* L2TP Server */
    if (isset($config['l2tp']) && is_array($config['l2tp'])) {
        if ($config['l2tp']['mode'] == "server") {
            $l2tp_net = "{$config['l2tp']['remoteip']}/{$config['l2tp']['l2tp_subnet']}";
            if (is_subnetv4($l2tp_net)) {
                $vpns_arr[] = $l2tp_net;
            }
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



function suricata_expand_alias($alias_name) {
    $aliasObject = new \OPNsense\Firewall\Alias();
    foreach ($aliasObject->aliasIterator() as $aliased) {
        if ($alias_name == $aliased['name']) {
            return implode(',', $aliased['content']);
        }
    }
    return "";
}


function suricata_alias_get_type(&$config, $name) {
    $aliases = $config['OPNsense']['Firewall']['Alias']['aliases']['alias'];
    foreach ($aliases as $alias) {
        if ($name == $alias['name']) {
            return $alias['type'];
        }
    }
    return "";
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
                    if (is_alias($addr) && (suricata_alias_get_type($config, $addr) == "host" || suricata_alias_get_type($config, $addr) == "network")) {
                        $home_net = array_merge($home_net, explode(" ", trim(suricata_expand_alias($addr))));
                    } elseif (is_ipaddr($addr) || is_subnet($addr)) {
                        $home_net[] = $addr;
                    }
                } elseif ($passlist) {
                    if (is_alias($addr) && (suricata_alias_get_type($config, $addr) == "host" || suricata_alias_get_type($config, $addr) == "network")) {
                        $tmp = trim(suricata_expand_alias($addr));
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
}


function suricata_is_valid_address(&$aliases_config, $addr) {
    if (is_alias($addr) && (suricata_alias_get_type($aliases_config, $addr) == "host" || suricata_alias_get_type($aliases_config, $addr) == "network"))
        return true;
    if (is_ipaddr($addr) || is_subnet($addr))
        return true;
    return false;
}


function suricata_reload_config($suricatacfg, $signal = "SIGUSR2") {

    /**************************************************************/
    /* This function sends the passed SIGNAL to the Suricata      */
    /* instance on the passed interface to cause Suricata to      */
    /* reload and parse the running configuration without         */
    /* impacting packet processing.  It also executes the reload  */
    /* as a background process and returns control immediately    */
    /* to the caller.                                             */
    /*                                                            */
    /*  $signal = SIGUSR2 (default) parses and reloads config.    */
    /**************************************************************/

    $if_real = get_real_interface($suricatacfg['iface']);

    /******************************************************/
    /* Skip disabled Suricata instances or instances      */
    /* whose pfSense physical interface has been removed. */
    /******************************************************/
    if (($suricatacfg['enable'] != 'on') || ($if_real == "")) {
        return;
    }

    /******************************************************/
    /* Only send the SIGNAL if Suricata is running and    */
    /* we can find a valid PID for the process.           */
    /******************************************************/
    if (isvalidpid("{$g['varrun_path']}/suricata_{$if_real}.pid")) {

        // Send the SIGNAL to the Suricata process
        mwexec_bg("/bin/pkill -{$signal} -F {$g['varrun_path']}/suricata_{$if_real}.pid");

        // Log what we did
        switch ($signal) {
            case SIGHUP:
                syslog(LOG_NOTICE, "[Suricata] Suricata LOGS ROTATION initiated for {$suricatacfg['descr']} ({$if_real})...");
                break;

            case SIGUSR2:
                syslog(LOG_NOTICE, "[Suricata] Suricata LIVE RULE RELOAD initiated for {$suricatacfg['descr']} ({$if_real})...");
                break;

            case SIGTERM:
            case SIGINT:
                syslog(LOG_NOTICE, "[Suricata] Suricata SHUTDOWN initiated for {$suricatacfg['descr']} ({$if_real})...");
                break;

            default:
                syslog(LOG_NOTICE, "[Suricata] Suricata signalled with {$signal} for {$suricatacfg['descr']} ({$if_real})...");
        }
    }
}


function suricata_start($iface, $verbose = false) {
    global $config;

    $suricatacfg = suricata_get_interface_config($iface);

    if (empty($suricatacfg))
        return 0;

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
        return 1;
    } else {
        return 0;
    }
}

function suricata_stop($iface, $verbose = false) {
    $suricatacfg = suricata_get_interface_config($iface);
    if (empty($suricatacfg))
        return 0;

    $realif = get_real_interface($iface);

    if (isvalidpid("/var/run/suricata_{$realif}.pid")) {
        syslog(LOG_NOTICE, "[Suricata] Suricata STOP for {$suricatacfg['descr']}({$realif})...");
        killbypid("/var/run/suricata_{$realif}.pid");
    }

    sleep(10);

    if (file_exists("/var/run/suricata_{$realif}.pid"))
        unlink("/var/run/suricata_{$realif}.pid");

    return 1;
}


function suricata_is_running($realif, $t = 'suricata') {
    return isvalidpid("/var/run/{$t}_{$realif}.pid");
}


function suricata_merge_reference_configs($cfg_in, $cfg_out) {

    /***********************************************************/
    /* This function takes a list of "reference.config" files  */
    /* in the $cfg_in array and merges them into a single      */
    /* file specified by $cfg_out.  The merging is done so     */
    /* no duplication of lines occurs in the output file.      */
    /***********************************************************/

    $outMap = array();
    foreach ($cfg_in as $file) {
        if (!file_exists($file)) {
            continue;
        }
        $in = file($file, FILE_SKIP_EMPTY_LINES);
        foreach ($in as $line) {
            /* Skip comment lines  */
            if (preg_match('/^\s*#/', $line)) {
                continue;
            }
            if (preg_match('/(\:)\s*(\w+)\s*(.*)/', $line, $matches)) {
                if (!empty($matches[2]) && !empty($matches[3])) {
                    $matches[2] = trim($matches[2]);
                    if (!array_key_exists($matches[2], $outMap)) {
                        if (!is_array($outMap[$matches[2]])) {
                            $outMap[$matches[2]] = array();
                        }
                        $outMap[$matches[2]] = trim($matches[3]);
                    }
                }
            }
        }
    }
    // Sort the new reference map.
    uksort($outMap,'strnatcasecmp');

    // Do NOT write an empty references.config file, just
    // exit instead.
    if (empty($outMap)) {
        return false;
    }

    // Format and write it to the supplied output file.
    $format = "config reference: %-12s %s\n";
    foreach ($outMap as $key=>$value) {
        $outMap[$key] = sprintf($format, $key, $value);
    }
    @file_put_contents($cfg_out, array_values($outMap));
    return true;
}


function suricata_merge_classification_configs($cfg_in, $cfg_out) {

    /************************************************************/
    /* This function takes a list of "classification.config"    */
    /* files in the $cfg_in array and merges them into a        */
    /* single file specified by $cfg_out.  The merging is done  */
    /* so no duplication of lines occurs in the output file.    */
    /************************************************************/

    $outMap = array();
    foreach ($cfg_in as $file) {
        if (!file_exists($file)) {
            continue;
        }
        $in = file($file, FILE_SKIP_EMPTY_LINES);
        foreach ($in as $line) {
            if (preg_match('/(.*:)(\s*.*),(.*),(.*)/', $line, $matches)) {
                /* Skip comment lines  */
                if (preg_match('/^\s*#/', $line)) {
                    continue;
                }
                if (!empty($matches[2]) && !empty($matches[3]) && !empty($matches[4])) {
                    $matches[2] = trim($matches[2]);
                    if (!array_key_exists($matches[2], $outMap)) {
                        if (!is_array($outMap[$matches[2]])) {
                            $outMap[$matches[2]] = array();
                        }
                        $outMap[$matches[2]] = trim($matches[3]) . "," . trim($matches[4]);
                    }
                }
            }
        }
    }
    // Sort the new classification map.
    uksort($outMap,'strnatcasecmp');

    // Do NOT write an empty classification.config file, just
    // exit instead.
    if (empty($outMap)) {
        return false;
    }

    // Format and write it to the supplied output file.
    $format = "config classification: %s,%s\n";
    foreach ($outMap as $key=>$value) {
        $outMap[$key] = sprintf($format, $key, $value);
    }
    @file_put_contents($cfg_out, array_values($outMap));
    return true;
}


function suricata_write_enforcing_rules_file($rule_map, $rule_path) {

    /************************************************/
    /* This function takes a rules map array of     */
    /* the rules chosen for the active rule set     */
    /* and writes them out to the passed path.      */
    /*                                              */
    /*  $rule_map --> Rules Map array of rules to   */
    /*                write to disk.                */
    /*                                              */
    /* $rule_path --> filename or directory where   */
    /*                rules file will be written.   */
    /************************************************/

    $rule_file = "/" . SURICATA_ENFORCING_RULES_FILENAME;

    // See if we were passed a directory or full
    // filename to write the rules to, and adjust
    // the destination argument accordingly.
    if (is_dir($rule_path)) {
        $rule_file = rtrim($rule_path, '/').$rule_file;
    } else {
        $rule_file = $rule_path;
    }

    // If the $rule_map array is empty, then exit.
    if (empty($rule_map)) {
        file_put_contents($rule_file, "");
        return;
    }

    $fp = fopen($rule_file, "w");
    if ($fp) {
        @fwrite($fp, "# These rules are your current set of enforced rules for the protected\n");
        @fwrite($fp, "# interface.  This list was compiled from the categories selected on the\n");
        @fwrite($fp, "# CATEGORIES tab of the Suricata configuration for the interface and/or any\n");
        @fwrite($fp, "# chosen Snort VRT pre-defined IPS Policy.\n#\n");
        @fwrite($fp, "# Any enablesid or disablesid customizations you made have been applied\n");
        @fwrite($fp, "# to the rules in this file.\n\n");
        foreach ($rule_map as $rulem) {
            foreach ($rulem as $rulem2) {
                /* No reason to write disabled rules to enforcing file, so skip them. */
                if ($rulem2['disabled'] == 1) {
                    continue;
                }
                @fwrite($fp, $rulem2['rule']);
            }
        }
        fclose($fp);
    }
}



function suricata_write_flowbit_rules_file($flowbit_rules, $rule_file) {

    /************************************************/
    /* This function takes an array of rules in the */
    /* rules_map format and writes them to the file */
    /* given.                                       */
    /*                                              */
    /* $flowbit_rules --> array of flowbit-required */
    /*                    rules.                    */
    /*                                              */
    /*     $rule_file --> filename to write the     */
    /*                    flowbit-required rules    */
    /*                    to.                       */
    /************************************************/

    $flowbit_rules_file = FLOWBITS_FILENAME;

    // See if we were passed a directory or full
    // filename to write the rules to, and adjust
    // the destination argument accordingly.
    if (is_dir($rule_file)) {
        $rule_file = rtrim($rule_file, '/')."/{$flowbit_rules_file}";
    }

    if (empty($flowbit_rules)) {
        @file_put_contents($rule_file, "");
        return;
    }

    $fp = fopen($rule_file, "w");
    if ($fp) {
        @fwrite($fp, "# These rules set flowbits checked by your other enabled rules.  If the\n");
        @fwrite($fp, "# dependent flowbits are not set, then some of your chosen rules may\n");
        @fwrite($fp, "# not fire.  Enabling all rules that set these dependent flowbits ensures\n");
        @fwrite($fp, "# your chosen rules fire as intended.\n#\n");
        @fwrite($fp, "# If you wish to prevent alerts from any of these rules, add the GID:SID\n");
        @fwrite($fp, "# of the rule to the Suppression List for the interface.\n");
        foreach ($flowbit_rules as $k1 => $rule) {
            foreach ($rule as $k2 => $rule2) {
                @fwrite($fp, "\n# Category: {$rule2['category']}");
                @fwrite($fp, "   GID:{$k1}  SID:{$k2}\n");
                @fwrite($fp, $rule2['rule']);
            }
        }
        fclose($fp);
    }
}


function suricata_build_sid_msg_map($rules_path, $sid_file) {

    /*************************************************************/
    /* This function reads all the rules file in the passed      */
    /* $rules_path variable and produces a properly formatted    */
    /* sid-msg.map v2 file for use by Suricata.                  */
    /*                                                           */
    /* This function produces the new v2 format sid-msg.map      */
    /* with the field layout as follows:                         */
    /*                                                           */
    /*  GID || SID || REV || CLASSTYPE || PRI || MSG || REF ...  */
    /*                                                           */
    /*  On Entry: $rules_path --> array or directory of files    */
    /*                            or a single file containing    */
    /*                            the rules to read.             */
    /*              $sid_file --> the complete destination path  */
    /*                            and filename for the output    */
    /*                            sid-msg.map file.              */
    /*************************************************************/

    $sidMap = array();
    $rule_files = array();

    // First check if we were passed a directory, a single file
    // or an array of filenames to read. Set our $rule_files
    // variable accordingly. If we can't figure it out, return
    // and don't write a sid-msg.map file.
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
        return;
    }

    // Read the rule files into an array, then iterate the list
    foreach ($rule_files as $file) {

        // Don't process files with "deleted" in the filename
        if (stristr($file, "deleted")) {
            continue;
        }

        // Read the file into an array, skipping missing files.
        if (!file_exists($file)) {
            continue;
        }

        $rules_array = file($file, FILE_SKIP_EMPTY_LINES);
        $record = "";
        $b_Multiline = false;

        // Read and process each line from the rules in the current file
        foreach ($rules_array as $rule) {

            // Skip any non-rule lines unless we're in multiline mode.
            if (!preg_match('/^\s*#*\s*(alert|drop|pass)/i', $rule) && !$b_Multiline) {
                continue;
            }

            // Test for a multi-line rule, and reassemble the
            // pieces back into a single line.
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
            $b_Multiline = false;
            $record = "";

            // Parse the rule to find sid and any references.
            $gid = '1';             // default to 1 for regular rules
            $sid = '';
            $rev = '';
            $classtype = 'NOCLASS'; // required default for v2 format
            $priority = '0';        // required default for v2 format
            $msg = '';
            $matches = '';
            $sidEntry = '';
            if (preg_match('/\bmsg\s*:\s*"(.+?)"\s*;/i', $rule, $matches)) {
                $msg = trim($matches[1]);
            }
            if (preg_match('/\bsid\s*:\s*(\d+)\s*;/i', $rule, $matches)) {
                $sid = trim($matches[1]);
            }
            if (preg_match('/\bgid\s*:\s*(\d+)\s*;/i', $rule, $matches)) {
                $gid = trim($matches[1]);
            }
            if (preg_match('/\brev\s*:\s*([^\;]+)/i', $rule, $matches)) {
                $rev = trim($matches[1]);
            }
            if (preg_match('/\bclasstype\s*:\s*([^\;]+)/i', $rule, $matches)) {
                $classtype = trim($matches[1]);
            }
            if (preg_match('/\bpriority\s*:\s*([^\;]+)/i', $rule, $matches)) {
                $priority = trim($matches[1]);
            }

            if (!empty($gid) && !empty($sid) && !empty($msg)) {
                $sidEntry = $gid . ' || ' . $sid . ' || ' . $rev . ' || ' . $classtype . ' || ';
                $sidEntry .= $priority . ' || ' . $msg;
                preg_match_all('/\breference\s*:\s*([^\;]+)/i', $rule, $matches);
                foreach ($matches[1] as $ref) {
                    $sidEntry .= " || " . trim($ref);
                }
                $sidEntry .= "\n";
                $sidMap[] = $sidEntry;
            }
        }
        }
    // Sort the generated sid-msg map
    natcasesort($sidMap);

    // Now print the result to the supplied file
    @file_put_contents($sid_file, "#v2\n# sid-msg.map file auto-generated by Suricata.\n\n");
    @file_put_contents($sid_file, array_values($sidMap), FILE_APPEND);
}


function suricata_load_suppress_sigs($suricatacfg, $track_by = false) {

    global $config;

    /**********************************************************/
    /* This function loads the GEN_ID and SIG_ID for all the  */
    /* suppressed alert entries from the Suppression List of  */
    /* the passed Suricata interface.  The results are        */
    /* returned in an array with GEN_ID and SIG_ID as the     */
    /* primary keys.  Any "track by_src" or "track by_dst"    */
    /* entries in the Suppression List are tacked on as       */
    /* additional keys in the array along with the IP address */
    /* in either IPv4 or IPv6 format when $track_by is passed */
    /* as true.                                               */
    /*                                                        */
    /* Sample returned array:                                 */
    /*  $suppress[1][2069] = "suppress"                       */
    /*  $suppress[1][2070]['by_src']['10.1.1.5'] = "suppress" */
    /*  $suppress[1][2070]['by_dst']['10.1.1.6'] = "suppress" */
    /*                                                        */
    /**********************************************************/

    $suppress = array();

    if (!is_array($config['OPNsense']['Suricata'])) {
        return;
    }
    if (!is_array($config['OPNsense']['Suricata']['suppress'])) {
        return;
    }
    if (!is_array($config['OPNsense']['Suricata']['suppress']['suppressionlist'])) {
        return;
    }

    foreach ($config['OPNsense']['Suricata']['suppress']['suppressionlist'] as $alist) {
        if ($alist['name'] == $suricatacfg['suppresslistname']) {
            if (!empty($alist['suppresspassthru'])) {
                $tmplist = str_replace("\r", "", base64_decode($alist['suppresspassthru']));
                $tmp = explode("\n", $tmplist);
                foreach ($tmp as $line) {
                    // Skip any blank lines
                    if (trim($line, " \n") == "") {
                        continue;
                    }
                    // Skip any comment lines
                    if (preg_match('/^\s*#/', $line)) {
                        continue;
                    }
                    /* See if entry suppresses GID:SID for all hosts */
                    if (preg_match('/\s*suppress\s*gen_id\b\s*(\d+),\s*sig_id\b\s*(\d+)\s*$/i', $line, $matches)) {
                        $genid = $matches[1];
                        $sigid = $matches[2];
                        if (!empty($genid) && !empty($sigid)) {
                            if (!is_array($suppress[$genid])) {
                                $suppress[$genid] = array();
                            }
                            if (!is_array($suppress[$genid][$sigid])) {
                                $suppress[$genid][$sigid] = array();
                            }
                            $suppress[$genid][$sigid] = "suppress";
                        }
                    }

                    /* Get "track by IP" entries if requested */
                    if ($track_by) {
                        /* See if entry suppresses only by SRC or DST IPv4 address */
                        if (preg_match('/\s*suppress\s*gen_id\b\s*(\d+),\s*sig_id\b\s*(\d+),\s*track\s*(by_src|by_dst),\s*ip\s*(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s*$/i', $line, $matches)) {
                            $genid = $matches[1];
                            $sigid = $matches[2];
                            $whichip = trim($matches[3]);
                            $ip = $matches[4];
                            if (!empty($genid) && !empty($sigid) && !empty($whichip) && !empty($ip)) {
                                if (!is_array($suppress[$genid])) {
                                    $suppress[$genid] = array();
                                }
                                if (!is_array($suppress[$genid][$sigid])) {
                                    $suppress[$genid][$sigid] = array();
                                }
                                if (!is_array($suppress[$genid][$sigid][$whichip])) {
                                    $suppress[$genid][$sigid][$whichip] = array();
                                }
                                if (!is_array($suppress[$genid][$sigid][$whichip][$ip])) {
                                    $suppress[$genid][$sigid][$whichip][$ip] = array();
                                }
                                $suppress[$genid][$sigid][$whichip][$ip] = "suppress";
                            }
                        }
                        /* See if entry suppresses only by SRC or DST IPv6 address */
                        if (preg_match('/\s*suppress\s*gen_id\b\s*(\d+),\s*sig_id\b\s*(\d+),\s*track\s*(by_src|by_dst),\s*ip\s*([0-9a-f\.:]+)\s*$/i', $line, $matches)) {
                            $genid = $matches[1];
                            $sigid = $matches[2];
                            $whichip = trim($matches[3]);
                            $ip = trim($matches[4]);
                            if (!empty($genid) && !empty($sigid) && !empty($whichip) && !empty($ip)) {
                                if (!is_array($suppress[$genid])) {
                                    $suppress[$genid] = array();
                                }
                                if (!is_array($suppress[$genid][$sigid])) {
                                    $suppress[$genid][$sigid] = array();
                                }
                                if (!is_array($suppress[$genid][$sigid][$whichip])) {
                                    $suppress[$genid][$sigid][$whichip] = array();
                                }
                                if (!is_array($suppress[$genid][$sigid][$whichip][$ip])) {
                                    $suppress[$genid][$sigid][$whichip][$ip] = array();
                                }
                                $suppress[$genid][$sigid][$whichip][$ip] = "suppress";
                            }
                        }
                    }
                }
                unset($tmp);
            }
            break;
        }
    }
    unset($alist);
    return $suppress;
}


function suricata_get_blocked_ips() {

    $suri_pf_table = SURICATA_PF_TABLE;
    $blocked_ips = "";

    exec("/sbin/pfctl -t {$suri_pf_table} -T show", $blocked_ips);
    $blocked_ips_array = array();
    if (!empty($blocked_ips)) {
        if (is_array($blocked_ips)) {
            foreach ($blocked_ips as $blocked_ip) {
                if (empty($blocked_ip)) {
                    continue;
                }
                $blocked_ips_array[] = trim($blocked_ip, " \n\t");
            }
        }
    }

    return $blocked_ips_array;
}


function suricata_xmlrpc_sync()
{
    $result = array(
        'description' => gettext('Suricata'),
        'section' => 'OPNsense.suricata',
        'id' => 'suricata',
        'services' => [],
    );

    foreach (suricata_get_configs() as $suricatacfg) {
        $result['services'][] = 'suricata_'.$suricatacfg['iface'];
    }

    return [$result];
}


function suricata_escape_filter_regex($filtertext) {
    return str_replace('/', '\/', str_replace('\/', '/', $filtertext));
}


function suricata_match_filter_field($flent, $fields, $exact_match = FALSE) {
    foreach ($fields as $key => $field) {
        if ($field == null)
            continue;

        // Only match whole field string when
        // performing an exact match.
        if ($exact_match) {
            if ($flent[$key] == $field) {
                return true;
            }
            else {
                return false;
            }
        }

        if ((strpos($field, '!') === 0)) {
            $field = substr($field, 1);
            $field_regex = suricata_escape_filter_regex($field);
            if (@preg_match("/{$field_regex}/i", $flent[$key]))
                return false;
        }
        else {
            $field_regex = suricata_escape_filter_regex($field);
            if (!@preg_match("/{$field_regex}/i", $flent[$key]))
                return false;
        }
    }
    return true;
}


function suricata_is_alert_globally_suppressed($list, $gid, $sid) {

    /************************************************/
    /* Checks the passed $gid:$sid to see if it has */
    /* been globally suppressed.  If true, then any */
    /* "track by_src" or "track by_dst" options are */
    /* disabled since they are overridden by the    */
    /* global suppression of the $gid:$sid.         */
    /************************************************/

    /* If entry has a child array, then it's by src or dst ip. */
    /* So if there is a child array or the keys are not set,   */
    /* then this gid:sid is not globally suppressed.           */
    if (is_array($list[$gid][$sid]))
        return false;
    elseif (!isset($list[$gid][$sid]))
        return false;
    else
        return true;
}


function suricata_add_supplist_entry($suppress, $suricatacfg) {

    /************************************************/
    /* Adds the passed entry to the Suppress List   */
    /* for the active interface.  If a Suppress     */
    /* List is defined for the interface, it is     */
    /* used.  If no list is defined, a new default  */
    /* list is created using the interface name.    */
    /*                                              */
    /* On Entry:                                    */
    /*   $suppress --> suppression entry text       */
    /*                                              */
    /* Returns:                                     */
    /*   TRUE if successful or FALSE on failure     */
    /************************************************/

    $model = new \OPNsense\Suricata\Suricata();
    $uuid = $suricatacfg['@attributes']['uuid'];

    if (empty($suricatacfg['suppresslistname'])) {

        $listname = $suricatacfg['iface']."_suppress";

        $supp = $model->suppress->suppressionlist->add();
        $supp->name = $listname;
        $supp->descr = "Auto-generated list for Alert suppression";
        $supp->suppresspassthru = $suppress;

        $model->serializeToConfig();

        $cnf = OPNsense\Core\Config::getInstance();
        $cnf->save("Suricata pkg: added Suppress List " . $listname . " from ALERTS tab.");

        $found_list = false;

        $suppresslists = suricata_get_suppress();
        foreach ($suppresslists as $alist) {
            if ($alist['name'] == $listname) {
                $found_list = true;
                $model->setNodeByReference('interfaces.interface.'.$uuid.'.suppresslistname', $alist['@attributes']['uuid']);
            }
        }

        if ($found_list) {
            $model->serializeToConfig();
            $cnf = OPNsense\Core\Config::getInstance();
            $cnf->save("Suricata pkg: saved change to Suppress List " . $listname . " from ALERTS tab.");
            return true;
        }

        return false;
    }

    $found_list = false;
    $listuuid = $suricatacfg['suppresslistname'];

    $suppresslists = suricata_get_suppress();
    foreach ($suppresslists as $alist) {
        $uuid = $alist['@attributes']['uuid'];
        if ($uuid == $listuuid) {
            $found_list = true;
            if (!empty($alist['suppresspassthru'])) {
                $model->setNodeByReference('suppress.suppressionlist.'.$uuid.'.suppresspassthru', $alist['suppresspassthru']."\n".$suppress);
            } else {
                $model->setNodeByReference('suppress.suppressionlist.'.$uuid.'.suppresspassthru', $suppress);
            }
        }
    }

    if ($found_list) {
        $model->serializeToConfig();
        $cnf = OPNsense\Core\Config::getInstance();
        $cnf->save("Suricata pkg: saved change to Suppress List " . $listname . " from ALERTS tab.");
        return true;
    }

    return false;
}
