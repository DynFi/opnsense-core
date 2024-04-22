#!/usr/local/bin/php

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

require_once("config.inc");
require_once("util.inc");
require_once("plugins.inc.d/suricata.inc");

global $config;


function suricata_folderSize($dir) {

    /********************************************************
        * This function returns the size, in bytes, of the     *
        * content (including any sub-folders) stored under the *
        * passed directory.                                    *
        ********************************************************/

    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : suricata_folderSize($each);
    }

    return $size;
}


function suricata_check_dir_size_limit($suricataloglimitsize) {

    /********************************************************
    * This function checks the total size of the Suricata  *
    * logging sub-directory structure and prunes the files *
    * for all Suricata interfaces if the size exceeds the  *
    * passed limit. Pruning stops when the directory size  *
    * is below the configured limit.                       *
    *                                                      *
    * On Entry: $surictaaloglimitsize = dir size limit     *
    *                                   in megabytes       *
    ********************************************************/

    global $config;

    // Convert Log Limit Size setting from MB to KB
    $suricataloglimitsizeKB = round($suricataloglimitsize * 1024);
    $suricatalogdirsizeKB = suricata_Getdirsize(SURICATALOGDIR);

    if ($suricatalogdirsizeKB > 0 && $suricatalogdirsizeKB > $suricataloglimitsizeKB) {
        syslog(LOG_NOTICE, gettext("[Suricata] Log directory size exceeds configured limit of " . number_format($suricataloglimitsize) . " MB set on Global Settings tab. Starting cleanup of Suricata logs."));

        // Initialize an array of the log files we want to prune
        $logs = array ( "alerts.log", "block.log", "dns.log", "eve.json", "http.log", "sid_changes.log", "stats.log", "tls.log" );

        // Clean-up the rotated logs for each configured Suricata instance
        foreach ($config['OPNsense']['Suricata']['interfaces'] as $value) {
            $if_real = get_real_interface($value['iface']);

            // Skip instances where pfSense physical interface
            // has been removed.
            if ($if_real == "") {
                continue;
            }
            $suricata_uuid = $value['uuid'];
            $suricata_log_dir = SURICATALOGDIR . "suricata_{$if_real}{$suricata_uuid}";
            syslog(LOG_NOTICE, gettext("[Suricata] Cleaning logs for {$value['descr']} ({$if_real})..."));

            // Clean-up packet capture files if any exist
            $filelist = glob("{$suricata_log_dir}/log.pcap.*");

            // Keep most recent file
            unset($filelist[count($filelist) - 1]);
            foreach ($filelist as $file) {
                unlink_if_exists($file);
            }
            unset($filelist);

            // Cleanup any rotated logs
            foreach ($logs as $file) {
                syslog(LOG_NOTICE, gettext("[Suricata] Deleting rotated log files except last for {$value['descr']} ({$if_real}) $file..."));
                $filelist = glob("{$suricata_log_dir}/{$file}.*");
                // Keep most recent file
                unset($filelist[count($filelist) - 1]);
                foreach ($filelist as $file) {
                    unlink_if_exists($file);
                }
                unset($filelist);
            }

            // Check for any captured stored files and clean them up
            unlink_if_exists("{$suricata_log_dir}/filestore/*");

            // Check for any captured stored TLS certs and clean them up
            unlink_if_exists("{$suricata_log_dir}/certs/*");
        }

        // If we are now below the configured Directory Size Limit, then skip
        // deleting the active logs and just exit.
        if (suricata_Getdirsize(SURICATALOGDIR) < $suricataloglimitsizeKB) {
            goto cleanupExit;
        }

        // Continue by cleaning up any other rotated logs not handled above
        syslog(LOG_NOTICE, gettext("[Suricata] Deleting any additional rotated log files..."));
        unlink_if_exists("{$suricata_log_dir}/suricata_*/*.log.*");
        unlink_if_exists("{$suricata_log_dir}/suricata_*/*.json.*");

        // If we are now below the configured Directory Size Limit, then skip
        // deleting the active logs and just exit.
        if (suricata_Getdirsize(SURICATALOGDIR) < $suricataloglimitsizeKB) {
            goto cleanupExit;
        }

        // Clean-up active logs for each configured Suricata instance
        // until we get below the configured Directory Size Limit.
        foreach ($config['OPNsense']['Suricata']['interfaces'] as $value) {
            $if_real = get_real_interface($value['iface']);

            // Skip instances where pfSense physical interface
            // has been removed.
            if ($if_real == "") {
                continue;
            }
            $suricata_uuid = $value['uuid'];
            $suricata_log_dir = SURICATALOGDIR . "suricata_{$if_real}{$suricata_uuid}";
            foreach ($logs as $file) {
                // Truncate the log file if it exists
                if (file_exists("{$suricata_log_dir}/{$file}")) {
                    try {
                        fclose(fopen("{$suricata_log_dir}/{$file}", 'w'));
                    } catch (Exception $e) {
                        syslog(LOG_ERR, "[Suricata] ERROR: Failed to truncate file '{$suricata_log_dir}/{$file}' -- error was {$e->getMessage()}");
                    }
                }

                // Stop deleting logs when directory size is below configured limit
                if (suricata_Getdirsize(SURICATALOGDIR) < $suricataloglimitsizeKB) {
                    break;
                }
            }

            // Signal Suricata on this interface that log files have been truncated
            suricata_reload_config($value, "SIGHUP");

            // Stop deleting logs when directory size is below configured limit
            if (suricata_Getdirsize(SURICATALOGDIR) < $suricataloglimitsizeKB) {
                break;
            }
        }

        // Truncate the Rules Update Log file if it exists
        if (file_exists(SURICATA_RULES_UPD_LOGFILE)) {
            syslog(LOG_NOTICE, gettext("[Suricata] Truncating the Rules Update Log file..."));
            try {
                fclose(fopen(SURICATA_RULES_UPD_LOGFILE, 'w'));
            } catch (Exception $e) {
                syslog(LOG_ERR, "[Suricata] ERROR: Failed to truncate Rules Update Log '" . SURICATA_RULES_UPD_LOGFILE . "' -- error was {$e->getMessage()}");
            }
        }

        cleanupExit:
        syslog(LOG_NOTICE, gettext("[Suricata] Automatic clean-up of Suricata logs completed."));
    }
}


function suricata_check_filestore_limit_size($store_path, $store_limit) {

    /***********************************************************
    * This recursive function checks the passed File Store    *
    * path to see if it is consuming more than the configured *
    * disk space limit. If it is, stored captured files are   *
    * are purged to reduce the storage below the limit.       *
    *                                                         *
    * On Entry: $store_path   -> full pathname to check       *
    *           $store_limit  -> size limit in MB             *
    *                                                         *
    * On Exit:  returns number of removed files               *
    ***********************************************************/

    $removed = 0;

    if (!is_dir($store_path)) {
        return $removed;
    }

    if (intval(suricata_folderSize($store_path)/1000000) >= $store_limit) {
        $msg = sprintf("[Suricata] Automatic prune of File Store path '%s' started due to exceeding configured Captured Files storage size limit of %d Megabytes.", $store_path, $store_limit);
        syslog(LOG_NOTICE, gettext($msg));

        // Get captured file names into array and sort by
        // modified date/time ascending.
        $files = glob(rtrim($store_path, '/').'/*');
        array_multisort(array_map('filemtime', $files), SORT_NUMERIC, SORT_ASC, $files);

        // Start deleting older files until we are
        // under the passed limit by at least 10%.
        for ($file_index = 0; ($file_index < count($files)) && (intval(suricata_folderSize($store_path)/1000000) > intval($store_limit * 0.9)); $file_index++) {
            if (is_dir($files[$file_index])) {
                $removed += suricata_check_filestore_limit_size($files[$file_index], $store_limit);
            }
            else {
                unlink_if_exists($files[$file_index]);
                $removed++;
            }
            $file_index--;
        }

        $msg = sprintf("[Suricata] Automatic prune of File Store path '%s' completed. Removed %d captured files.", $store_path, $removed);
        syslog(LOG_NOTICE, gettext($msg));
    }
    return $removed;
}


function suricata_check_prune_filestore_files($store_path, $retention = 0) {

    /********************************************************
    * This recursive function checks the passed File Store *
    * path to see if any stored captured files are         *
    * exceedubg the retention limit. Files exceeding the   *
    * limit are removed.                                   *
    *                                                      *
    * On Entry: $store_path -> full pathname to check      *
    *           $retention  -> retention period in hours   *
    *                          for captured files. Zero    *
    *                          means never remove.         *
    *                                                      *
    * On Exit:  returns number of pruned files             *
    ********************************************************/

    $prune_count = 0;

    if ($retention < 1) {
        return $prune_count;
    }

    // Get captured files names into array.
    $files = glob(rtrim($store_path, '/').'/*');
    $now = time();
    foreach ($files as $f) {

        // If this is a directory, execute
        // a recursive call to enumerate and
        // prune its contents.
        if (is_dir($f)) {
            $prune_count += suricata_check_prune_filestore_files($f, $retention);
        }
        else {
            if (($now - filemtime($f)) > ($retention * 3600)) {
                $prune_count++;
                unlink_if_exists($f);
            }
        }
    }

    unset($files);
    return $prune_count;
}


function suricata_check_rotate_log($log_file, $log_limit, $retention) {

    /********************************************************
    * This function checks the passed log file against     *
    * the passed size limit and rotates the log file if    *
    * necessary.  It also checks the age of previously     *
    * rotated logs and removes those older than the        *
    * rentention  parameter.                               *
    *                                                      *
    * On Entry: $log_file  -> full pathname/filename of    *
    *                         log file to check            *
    *           $log_limit -> size of file in bytes to     *
    *                         trigger rotation. Zero       *
    *                         means no rotation.           *
    *           $retention -> retention period in hours    *
    *                         for rotated logs. Zero       *
    *                         means never remove.          *
    *                                                      *
    * On Exit:  returns number of rotated files            *
    ********************************************************/

    $rotated_count = 0;

    // Check the current log to see if it needs rotating.
    // If it does, rotate it and put the current time
    // on the end of the filename as UNIX timestamp.
    if (!file_exists($log_file))
        return $rotated_count;
    if (($log_limit > 0) && (filesize($log_file) >= $log_limit)) {
        $newfile = $log_file . "." . date('Y_md_Hi');
        try {
            rename($log_file, $newfile);
            touch($log_file);
            $rotated_count++;
        } catch (Exception $e) {
            syslog(LOG_ERR, "[Suricata] ERROR: Failed to rotate file '{$log_file}' -- error was {$e->getMessage()}");
        }
    }

    // Check previously rotated logs to see if time to
    // delete any older than the retention period.
    // Rotated logs have a UNIX timestamp appended to
    // filename.
    if ($retention > 0) {
        $now = time();
        $rotated_files = glob("{$log_file}.*");
        foreach ($rotated_files as $file) {
            if (($now - filemtime($file)) > ($retention * 3600))
                unlink_if_exists($file);
        }
        unset($rotated_files);
    }

    return $rotated_count;
}

/*************************
 * Start of main code    *
 *************************/


// If firewall is booting, do nothing
if (product::getInstance()->booting())
    return;

// If no interfaces defined, there is nothing to clean up
if (empty($config['OPNsense']['Suricata']['interfaces']))
    return;

$logs = array ();

// Build an arry of files to check and limits to check them against from our saved configuration
$logs['alerts.log']['limit'] = $config['OPNsense']['Suricata']['global']['alertloglimitsize'];
$logs['alerts.log']['retention'] = $config['OPNsense']['Suricata']['global']['alertlogretention'];
$logs['block.log']['limit'] = $config['OPNsense']['Suricata']['global']['blockloglimitsize'];
$logs['block.log']['retention'] = $config['OPNsense']['Suricata']['global']['blocklogretention'];
$logs['eve.json']['limit'] = $config['OPNsense']['Suricata']['global']['eveloglimitsize'];
$logs['eve.json']['retention'] = $config['OPNsense']['Suricata']['global']['evelogretention'];
$logs['http.log']['limit'] = $config['OPNsense']['Suricata']['global']['httploglimitsize'];
$logs['http.log']['retention'] = $config['OPNsense']['Suricata']['global']['httplogretention'];
$logs['sid_changes.log']['limit'] = $config['OPNsense']['Suricata']['global']['sidchangesloglimitsize'];
$logs['sid_changes.log']['retention'] = $config['OPNsense']['Suricata']['global']['sidchangeslogretention'];
$logs['stats.log']['limit'] = $config['OPNsense']['Suricata']['global']['statsloglimitsize'];
$logs['stats.log']['retention'] = $config['OPNsense']['Suricata']['global']['statslogretention'];
$logs['tls.log']['limit'] = $config['OPNsense']['Suricata']['global']['tlsloglimitsize'];
$logs['tls.log']['retention'] = $config['OPNsense']['Suricata']['global']['tlslogretention'];


// Check log limits and retention in the interface logging directories if enabled
if ($config['OPNsense']['Suricata']['global']['enablelogmgmt'] == '1') {
    foreach ($config['OPNsense']['Suricata']['interfaces'] as $value) {
        $if_real = get_real_interface($value['iface']);

        // Skip instances where pfSense physical interface
        // has been removed.
        if ($if_real == "") {
            continue;
        }
        $suricata_log_dir = SURICATALOGDIR . "suricata_{$if_real}";
        $rotated = 0;
        foreach ($logs as $k => $p) {
            $limit = intval(substr($p['limit'], 1));
            $retention = intval(substr($p['retention'], 1));
            $rotated += suricata_check_rotate_log("{$suricata_log_dir}/{$k}", $limit * 1024, $retention);
        }
        if ($rotated > 0) {
            // Send the running Suricata instance on this interface a SIGHUP signal
            // so it will re-open the log files we rotated and truncated.
            suricata_reload_config($value, "SIGHUP");
            syslog(LOG_NOTICE, gettext("[Suricata] Logs Mgmt job rotated {$rotated} file(s) in '{$suricata_log_dir}/' ..."));
        }

        // Prune aged-out File Store captured files if any exist
        if (is_dir("{$suricata_log_dir}/filestore") &&
            $config['OPNsense']['Suricata']['global']['filestoreretention'] > 0) {

            $prune_count = suricata_check_prune_filestore_files("{$suricata_log_dir}/filestore", $config['OPNsense']['Suricata']['global']['filestoreretention']);
            if ($prune_count > 0)
                syslog(LOG_NOTICE, gettext("[Suricata] File Store captured files cleanup job removed {$prune_count} file(s) from '{$suricata_log_dir}/filestore/' path..."));
        }

        // Check File Store captured files storage limit and prune if necessary
        if (is_dir("{$suricata_log_dir}/filestore") &&
            $config['OPNsense']['Suricata']['global']['filestorelimitsize'] > 0 &&
            (intval(suricata_folderSize("{$suricata_log_dir}/filestore")/1000000) >= $config['OPNsense']['Suricata']['global']['filestorelimitsize'])) {
            suricata_check_filestore_limit_size("{$suricata_log_dir}/filestore", $config['OPNsense']['Suricata']['global']['filestorelimitsize']);
        }

        // If a user-customized file store directory is set, check it, too
        if (isset($value['filestorelogdir'])) {
            if (is_dir($value['filestorelogdir']) &&
                $config['OPNsense']['Suricata']['global']['filestorelimitsize'] > 0 &&
                (intval(suricata_folderSize($value['filestorelogdir'])/1000000) >= $config['OPNsense']['Suricata']['global']['filestorelimitsize'])) {
                suricata_check_filestore_limit_size($value['filestorelogdir'], $config['OPNsense']['Suricata']['global']['filestorelimitsize']);
            }
        }

        // Prune aged-out TLS Certs Store files if any exist
        if (is_dir("{$suricata_log_dir}/certs") &&
            $config['OPNsense']['Suricata']['global']['tlscertsstoreretention'] > 0) {
            $now = time();
            $files = glob("{$suricata_log_dir}/certs/*.*");
            $prune_count = 0;
            foreach ($files as $f) {
                if (($now - filemtime($f)) > ($config['OPNsense']['Suricata']['global']['tlscertsstoreretention'] * 3600)) {
                    $prune_count++;
                    unlink_if_exists($f);
                }
            }
            if ($prune_count > 0)
                syslog(LOG_NOTICE, gettext("[Suricata] TLS Certs Store cleanup job removed {$prune_count} file(s) from {$suricata_log_dir}/certs/..."));
            unset($files);
        }

        // Prune any pcap log files over configured limit
        $files = glob("{$suricata_log_dir}/log.pcap.*");
        if (count($files) > $value['maxpcaplogfiles']) {
            $over = count($files) - $value['maxpcaplogfiles'];
            $remove_files = array();
            while ($over > 0) {
                $remove_files[] = array_shift($files);
                $over--;
            }
            $prune_count = 0;
            foreach ($remove_files as $f) {
                $prune_count++;
                unlink_if_exists($f);
            }
            if ($prune_count > 0)
                syslog(LOG_NOTICE, gettext("[Suricata] Packet Capture log cleanup job removed {$prune_count} file(s) from {$suricata_log_dir}/..."));
            unset($files, $remove_files);
        }
    }
}

// Check the overall log directory limit (if enabled) and prune if necessary
if ($config['OPNsense']['Suricata']['global']['suricataloglimit'] == '1') {
    suricata_check_dir_size_limit($config['OPNsense']['Suricata']['global']['suricataloglimitsize']);
}

?>
