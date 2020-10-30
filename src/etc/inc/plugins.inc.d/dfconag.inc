<?php

/*
    Copyright (C) 2020 Dawid Kujawa <dawid.kujawa@dynfi.com>
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
       this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
*/

function dfconag_services()
{
    global $config;

    $services = array();

    if ((isset($config['OPNsense']['DFConAg']['settings']['enabled'])) && ($config['OPNsense']['DFConAg']['settings']['enabled'] == 1)) {
        $services[] = array(
            'description' => gettext('DynFi Connection Agent').' ('.$config['OPNsense']['DFConAg']['settings']['dfmHost'].':'.$config['OPNsense']['DFConAg']['settings']['dfmSshPort'].')',
            'configd' => array(
                'restart' => array('dfconag restart'),
                'start' => array('dfconag start'),
                'stop' => array('dfconag stop'),
            ),
            'name' => 'dfconag',
            'pidfile' => '/var/run/dfconag.pid'
        );
    }

    return $services;
}


function dfconag_configure() {
    return array(
        'webgui' => array('dfconag_regenerate_config')
    );
}


function dfconag_firewall($fw) {
    dfconag_regenerate_config();
}


function dfconag_regenerate_config() {
    mwexec('/usr/local/sbin/configctl dfconag restart');
}
