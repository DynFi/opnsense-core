<?php

/*
 * Copyright (C) 2016 Deciso B.V.
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

function suricata_services()
{
    global $config;

    $services = array();

    if (
        isset($config['OPNsense']['IDS']['general']['enabled']) &&
        $config['OPNsense']['IDS']['general']['enabled'] == 1
    ) {
        $services[] = array(
            'description' => gettext('Intrusion Detection'),
            'configd' => array(
                'restart' => array('ids restart'),
                'start' => array('ids start'),
                'stop' => array('ids stop'),
            ),
            'name' => 'suricata',
        );
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
        'description' => gettext('Intrusion Detection'),
        'section' => 'OPNsense.IDS',
        'id' => 'suricata',
        'services' => ["suricata"],
    );

    return $result;
}


function suricata_start($iface) {
    global $config;

    $suricatadir = SURICATADIR;
    $suricata_uuid = $suricatacfg['uuid'];
    $suricatalogdir = SURICATALOGDIR . "suricata_{$if_real}{$suricata_uuid}";
    $suricatabindir = SURICATA_PBI_BINDIR;

    if ($suricatacfg['enable'] == 'on' && $if_real <> "") {
        file_put_contents("{$suricatalogdir}/suricata.log", '');
        $run_mode = $suricatacfg['ips_mode'] == 'ips_mode_inline' && $suricatacfg['blockoffenders'] == 'on' ? '--netmap' : '-i ' . $if_real;
        $verbose_logging = $suricatacfg['enable_verbose_logging'] == 'on' ? "-vv" : "";
        syslog(LOG_NOTICE, "[Suricata] Suricata START for {$suricatacfg['descr']}({$if_real})...");
        mwexec_bg("{$suricatabindir}suricata {$run_mode} -D -c {$suricatadir}suricata_{$suricata_uuid}_{$if_real}/suricata.yaml --pidfile {$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}.pid {$verbose_logging}");
    } else {
        return;
    }
}
