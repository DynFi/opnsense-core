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

    foreach ($config['OPNsense']['Suricata']['interfaces'] as $iface) {
        if (isset($iface['enabled']) && $iface['enabled'] == 1) {
            $services[] = array(
                'description' => gettext('Suricata ('.$iface['iface'].')'),
                'configd' => array(
                    'restart' => array('suricata restart '.$iface['iface']),
                    'start' => array('suricata start '.$iface['iface']),
                    'stop' => array('suricata stop '.$iface['iface']),
                ),
                'name' => 'suricata_'.$iface['iface'],
            );
        }
    }

    return $services;
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


function suricata_get_interface_config($iface) {
    global $config;

    $iface = strtolower($iface);

    foreach ($config['OPNsense']['Suricata']['interfaces'] as $if) {
        if ($if['iface'] == $iface) {
            $if['uuid'] = $if['@attributes']['uuid'];
            return $if;
        }
    }
    return null;
}


function file_put_contents_with_mkdir($dir, $contents){
    $parts = explode('/', $dir);
    $file = array_pop($parts);
    $dir = '';
    foreach($parts as $part)
        if(!is_dir($dir .= "/$part")) mkdir($dir);
    file_put_contents("$dir/$file", $contents);
}


function suricata_start($iface) {
    global $config;

    $suricatacfg = suricata_get_interface_config($iface);
    if (empty($suricatacfg))
        return;

    $suricatadir = SURICATADIR;
    $suricatalogdir = SURICATALOGDIR . "suricata_{$iface}";
    $suricatabindir = SURICATA_PBI_BINDIR;

    if (isset($suricatacfg['enabled']) && (intval($suricatacfg['enabled']) == 1) && ($iface != '')) {
        file_put_contents_with_mkdir("{$suricatalogdir}/suricata.log", '');
        $run_mode = $suricatacfg['ipsmode'] == 'inline' && $suricatacfg['blockoffenders'] == 'on' ? '--netmap' : '-i ' . $iface;
        $verbose_logging = $suricatacfg['enableverboselogging'] == 'on' ? "-vv" : "";
        syslog(LOG_NOTICE, "[Suricata] Suricata START for {$suricatacfg['descr']}({$iface})...");
        // echo "{$suricatabindir}suricata {$run_mode} -D -c {$suricatadir}suricata_{$iface}/suricata.yaml --pidfile /var/run/suricata_{$iface}.pid {$verbose_logging}";
        mwexec_bg("{$suricatabindir}suricata {$run_mode} -D -c {$suricatadir}suricata_{$iface}/suricata.yaml --pidfile /var/run/suricata_{$iface}.pid {$verbose_logging}");
    } else {
        return;
    }
}
