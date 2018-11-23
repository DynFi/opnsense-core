#!/usr/local/bin/php
<?php

require_once 'util.inc';

$leases_file = "/var/dhcpd/var/db/dhcpd6.leases";
if (!file_exists($leases_file)) {
    exit(1);
}

$duid_arr = array();
foreach (file($leases_file) as $line) {
    if (preg_match("/^(ia-[np][ad])[ ]+\"(.*?)\"/i ", $line, $duidmatch)) {
        $type = $duidmatch[1];
        $duid = $duidmatch[2];
        continue;
    }

    /* is it active? otherwise just discard */
    if (preg_match("/binding state active/i", $line, $activematch)) {
        $active = true;
        continue;
    }

    if (preg_match("/iaaddr[ ]+([0-9a-f:]+)[ ]+/i", $line, $addressmatch)) {
        $ia_na = $addressmatch[1];
        continue;
    }

    if (preg_match("/iaprefix[ ]+([0-9a-f:\/]+)[ ]+/i", $line, $prefixmatch)) {
        $ia_pd = $prefixmatch[1];
        continue;
    }

    /* closing bracket */
    if (preg_match("/^}/i ", $line)) {
        switch ($type) {
            case "ia-na":
                $duid_arr[$duid][$type] = $ia_na;
                break;
            case "ia-pd":
                $duid_arr[$duid][$type] = $ia_pd;
                break;
        }
        unset($type);
        unset($duid);
        unset($active);
        unset($ia_na);
        unset($ia_pd);
        continue;
    }
}

$routes = array();
foreach ($duid_arr as $entry) {
    if ($entry['ia-pd'] != '') {
        $routes[$entry['ia-na']] = $entry['ia-pd'];
    }
    array_shift($duid_arr);
}

if (count($routes) > 0) {
    foreach ($routes as $address => $prefix) {
        mwexecf('/sbin/route delete -inet6 %s %s', array($prefix, $address), true);
        mwexecf('/sbin/route add -inet6 %s %s', array($prefix, $address));
    }
}

/* get clog from dhcpd */
$dhcpdlogfile = "/var/log/dhcpd.log";
$clog = array();
if (file_exists($dhcpdlogfile)) {
    exec("clog $dhcpdlogfile", $clog, $ret);
}

if ($ret > 0) {
    $clog = array();
}

$expires = array();
foreach ($clog as $line) {
    if (preg_match("/releases[ ]+prefix[ ]+([0-9a-f:]+\/[0-9]+)/i", $line, $expire)) {
        if (in_array($expire[1], $routes)) {
            continue;
        }
        $expires[$expire[1]] = $expire[1];
    }
    array_shift($clog);
}

if (count($expires) > 0) {
    foreach ($expires as $prefix) {
        if (isset($prefix['prefix'])) {
            mwexecf('/sbin/route delete -inet6 %s', array($prefix['prefix']), true);
        }
    }
}
