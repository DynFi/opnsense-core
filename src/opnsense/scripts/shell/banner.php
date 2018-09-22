#!/usr/local/bin/php
<?php

/*
 * Copyright (C) 2005 Scott Ullrich <sullrich@gmail.com>
 * Copyright (C) 2005 Colin Smith <ethethlay@gmail.com>
 * Copyright (C) 2009 Ermal Luçi
 * All rights reserved
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
require_once("interfaces.inc");
require_once("util.inc");
require_once("plugins.inc.d/openssh.inc");

$hostname = $config['system']['hostname'];
$domain = $config['system']['domain'];
$flavour = $g['product_flavour'];
$version = $g['product_version'];
$product = $g['product_name'];
$machine = $g['product_arch'];

echo "\n*** {$hostname}.{$domain}: {$product} {$version} ({$machine}/${flavour}) ***\n";

$iflist = legacy_config_get_interfaces(array('virtual' => false));

if (!count($iflist)) {
    echo "\n\tNo network interfaces are assigned.\n";
    return;
}

foreach ($iflist as $ifname => $ifcfg) {
    /* point to this interface's config */
    $ifconf = $config['interfaces'][$ifname];
    /* look for 'special cases' */
    $class = "";
    if (isset($ifconf['ipaddr'])) {
        switch ($ifconf['ipaddr']) {
            case "dhcp":
                $class = "/DHCP4";
                break;
            case "pppoe":
                $class = "/PPPoE";
                break;
            case "pptp":
                $class = "/PPTP";
                break;
            case "l2tp":
                $class = "/L2TP";
                break;
        }
    }
    $class6 = null;
    if (isset($ifconf['ipaddrv6'])) {
        switch ($ifconf['ipaddrv6']) {
            case "dhcp6":
                $class6 = "/DHCP6";
                break;
            case "slaac":
                $class6 = "/SLAAC";
                break;
            case "6rd":
                $class6 = "/6RD";
                break;
            case "6to4":
                $class6 = "/6to4";
                break;
            case "track6":
                $class6 = "/t6";
                break;
        }
    }
    $ipaddr = get_interface_ip($ifname);
    $subnet = get_interface_subnet($ifname);
    $ipaddr6 = get_interface_ipv6($ifname);
    $subnet6 = get_interface_subnetv6($ifname);
    $realif = get_real_interface($ifname);
    $tobanner = "{$ifcfg['descr']} ({$realif})";

    printf("\n %-15s -> ", $tobanner);

    $v6first = false;
    if (!empty($ipaddr) && !empty($subnet)) {
        printf(
            "v4%s: %s/%s",
            $class,
            $ipaddr,
            $subnet
        );
    } else {
        $v6first = true;
    }
    if (!empty($ipaddr6) && !empty($subnet6)) {
        if (!$v6first) {
            printf("\n%s", str_repeat(" ", 20));
        }
        printf(
            "v6%s: %s/%s",
            $class6,
            $ipaddr6,
            $subnet6
        );
    }
}

echo PHP_EOL;

if (openssh_enabled() || $config['system']['webgui']['protocol'] == 'https') {
    echo PHP_EOL;
}

if ($config['system']['webgui']['protocol'] == 'https') {
    echo ' HTTPS: ';
    passthru('openssl x509 -in /var/etc/cert.pem -noout -fingerprint -sha256 | sed "s/Fingerprint=//" | tr ":" " " | sed -E "s/(^.{54})./\1,               /" | tr "," "\n"');
}

if (openssh_enabled()) {
    foreach (glob('/conf/sshd/ssh_host_*_key.pub') as $ssh_host_pub_key_file_path) {
        echo ' SSH:   ';
        passthru("ssh-keygen -l -f " . escapeshellarg($ssh_host_pub_key_file_path) . " | awk '{ print $2 \" \" $4 }' | sed 's/SHA256:/SHA256 /'");
    }
}
