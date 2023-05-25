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


function suricata_configure_do($verbose = false, $unused = '') {
    global $config;

    service_log('Generating Suricata configs...', $verbose);

    foreach ($config['OPNsense']['Suricata']['interfaces'] as $iface) {

        // suricata_stop($iface['iface']);

        if (isset($iface['enabled']) && $iface['enabled'] == 1) {
            service_log('Generating suricata.yaml file for ['.$iface['iface'].']', $verbose);
            suricata_generate_yaml($iface);

            service_log('Starting Suricata for ['.$iface['iface'].']', $verbose);
            suricata_start($iface['iface']);
        }
    }
}


function suricata_generate_yaml($suricatacfg) {
    global $config, $g;

    $suricatadir = SURICATADIR;
    $suricatalogdir = SURICATALOGDIR;
    $flowbit_rules_file = FLOWBITS_FILENAME;
    $suricata_enforcing_rules_file = SURICATA_ENFORCING_RULES_FILENAME;
    $if_real = $suricatacfg['iface'];
    $suricata_uuid = $suricatacfg['uuid'];

    $suricatacfgdir = SURICATADIR . "suricata_{$if_real}";

    include("/usr/local/opnsense/scripts/suricata/control/suricata_generate_yaml.php");
    include("/usr/local/opnsense/scripts/suricata/control/suricata_yaml_template.inc");
    @file_put_contents("{$suricatacfgdir}/suricata.yaml", $suricata_conf_text);
    unset($suricata_conf_text);
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


function suricata_find_list($find_name, $type = 'passlists') {
    global $config;

    $suricataglob = $config['OPNsense']['Suricata'][$type];
    if (!is_array($suricataglob[$type])) {
        return "";
    }


    foreach ($config['OPNsense']['Suricata'][$type] as $value) {
        if ($value['name'] == $find_name) {
            return $value;
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
    init_config_arr(array('openvpn', 'openvpn-csc'));
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
        foreach ($int_array as $int) {
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
        $ip = get_interface_linklocal("wan");
        if (!empty($ip)) {
            // Trim off the interface designation (e.g., %em1) if present
            if (strpos($ip, "%") !== FALSE) {
                $ip = substr($ip, 0, strpos($ip, "%"));
            }
            if (!in_array($ip . "/128", $home_net)) {
                $home_net[] = $ip . "/128";
            }
        }
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
        $gw = $gateways->getInterfaceGateway($suricatacfg['interface'], 'inet')
        if (is_ipaddrv4($gw) && !in_array($gw . "/32", $home_net)) {
            $home_net[] = $gw . "/32";
        }
        $gw = $gateways->getInterfaceGateway($suricatacfg['interface'], 'inet6')
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
