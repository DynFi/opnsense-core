<?php

/*
<<<<<<< HEAD
=======
 * Copyright (C) 2023 Deciso B.V.
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
 * Copyright (C) 2018 Michael Muenz <m.muenz@gmail.com>
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

function wireguard_enabled()
{
<<<<<<< HEAD
    $model = new \OPNsense\Wireguard\General();
    return (string)$model->enabled == '1';
=======
    return (string)(new \OPNsense\Wireguard\General())->enabled == '1';
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
}

function wireguard_services()
{
    $services = [];

    if (!wireguard_enabled()) {
        return $services;
    }

<<<<<<< HEAD
    $service = [
        'description' => gettext('WireGuard VPN'),
        'configd' => [
            'restart' => ['wireguard restart'],
            'start' => ['wireguard start'],
            'stop' => ['wireguard stop'],
        ],
        'name' => 'wireguard-go',
    ];

    if (file_exists('/boot/modules/if_wg.ko') || file_exists('/boot/kernel/if_wg.ko')) {
        $service['name'] = 'wireguard';
        $service['nocheck'] = true;
    }

    $services[] = $service;

    return $services;
}

=======
    foreach ((new OPNsense\Wireguard\Server())->servers->server->iterateItems() as $key => $node) {
        if (!empty((string)$node->enabled)) {
            $services[] = [
                'description' => 'WireGuard ' . htmlspecialchars($node->name),
                'configd' => [
                    'start' => ["wireguard start {$key}"],
                    'restart' => ["wireguard restart {$key}"],
                    'stop' => ["wireguard stop {$key}"],
                ],
                'nocheck' => true, /* no daemon to check */
                'id' => $key,
                'name' => "wireguard"
            ];
        }
    }

    return $services;
}

function wireguard_syslog()
{
    return [
        'wireguard' => ['facility' => ['wireguard']]
    ];
}

>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
function wireguard_interfaces()
{
    $interfaces = [];

    if (!wireguard_enabled()) {
        return $interfaces;
    }

    $interfaces['wireguard'] = [
        'descr' => gettext('WireGuard (Group)'),
        'if' => 'wireguard',
        'virtual' => true,
        'enable' => true,
        'type' => 'group',
        'networks' => [],
    ];

    return $interfaces;
}

function wireguard_xmlrpc_sync()
{
    $result = [];

    $result['id'] = 'wireguard';
    $result['section'] = 'OPNsense.wireguard';
    $result['description'] = gettext('WireGuard');
<<<<<<< HEAD
    $result['services'] = ['wireguard-go'];

    if (file_exists('/boot/modules/if_wg.ko') || file_exists('/boot/kernel/if_wg.ko')) {
        $result['services'] = ['wireguard'];
    }
=======
    $result['services'] = ['wireguard'];
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423

    return [$result];
}

function wireguard_devices()
{
<<<<<<< HEAD
    return [['pattern' => '^wg', 'volatile' => true]];
=======
    $names = [];
    foreach ((new OPNsense\Wireguard\Server())->servers->server->iterateItems() as $key => $node) {
        if (!empty((string)$node->enabled)) {
            $names[(string)$node->interface] = [
                'descr' => sprintf('%s (WireGuard - %s)', (string)$node->interface, (string)$node->name),
                'ifdescr' => (string)$node->name,
                'name' => (string)$node->interface
            ];
        }
    }
    return [[
        'function' => 'wireguard_prepare', /* XXX only (empty) device creation */
        'configurable' => false,
        'pattern' => '^wg',
        'type' => 'wireguard',
        'volatile' => true,
        'names' => $names,
    ]];
}

function wireguard_prepare($device)
{
    foreach ((new OPNsense\Wireguard\Server())->servers->server->iterateItems() as $node) {
        if ($device != (string)$node->interface) {
            continue;
        }

        /* deleting the stat file marks the interface for eventual reconfiguration */
        @unlink((string)$node->statFilename);

        if (!does_interface_exist($device)) {
            mwexecf('/sbin/ifconfig wg create name %s', $device);
            mwexecf('/sbin/ifconfig %s group wireguard', $device);
        }

        return $device;
    }

    return null;
}

function wireguard_configure()
{
    return [
        'newwanip' => ['wireguard_sync:2'],
        'vpn' => ['wireguard_configure_do:2'],
    ];
}

function wireguard_configure_do($verbose = false, $unused = '')
{
    if (!wireguard_enabled()) {
        return;
    }

    service_log('Configuring WireGuard VPN...', $verbose);

    configd_run('wireguard configure');

    service_log("done.\n", $verbose);
}

function wireguard_sync($verbose = false, $unused = '')
{
    if (!wireguard_enabled()) {
        return;
    }

    $instances = [];
    foreach ((new OPNsense\Wireguard\Server())->servers->server->iterateItems() as $node) {
        if (!empty((string)$node->enabled)) {
            $instances[(string)$node->interface] = (string)$node->cnfFilename;
        }
    }

    if (!count($instances)) {
        return;
    }

    service_log('Synchronizing WireGuard VPN...', $verbose);

    openlog('wireguard', LOG_ODELAY, LOG_AUTH);

    foreach ($instances as $device => $config) {
        mwexecf('/usr/bin/wg syncconf %s %s', [$device, $config]);
    }

    closelog();
    reopenlog();

    service_log("done.\n", $verbose);
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
}
