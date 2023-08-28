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

// Create required Suricata directories if they don't exist
$suricata_dirs = array($suricatadir, $suricatacfgdir, "{$suricatacfgdir}/rules", "{$suricatalogdir}suricata_{$realif}");

foreach ($suricata_dirs as $dir) {
    if (!is_dir($dir))
        mkdir($dir, 0777, true);
}

$config_files = array("classification.config", "reference.config", "gen-msg.map", "unicode.map");
foreach ($config_files as $file) {
    if (file_exists("{$suricatadir}{$file}"))
        @copy("{$suricatadir}{$file}", "{$suricatacfgdir}/{$file}");
}


// Read the configuration parameters for the passed interface
// and construct appropriate string variables for use in the
// suricata.yaml template include file.

// Set HOME_NET and EXTERNAL_NET for the interface
$home_net_list = suricata_build_list($suricatacfg, $suricatacfg['homelistname']);
if (!is_array($home_net_list))
    $home_net_list = [ $home_net_list ];
$home_net = implode(", ", $home_net_list);
$home_net = trim($home_net);
$external_net = "";
if (!empty($suricatacfg['externallistname']) && $suricatacfg['externallistname'] != 'default') {
    $external_net_list = suricata_build_list($suricatacfg, $suricatacfg['externallistname'], false, true);
    $external_net = implode(", ", $external_net_list);
    $external_net = "[" . trim($external_net) . "]";
}
else {
    $external_net = "[";
    foreach ($home_net_list as $ip)
        $external_net .= "!{$ip}, ";
    $external_net = trim($external_net, ', ') . "]";
}

// Set the PASS LIST and write its contents to disk,
// but only if using Legacy Mode blocking. Otherwise,
// just create an empty placeholder file.
if (file_exists("{$suricatacfgdir}/rules/passlist.rules"))
    unlink("{$suricatacfgdir}/rules/passlist.rules");
$suri_passlist = "{$suricatacfgdir}/passlist";
if ($suricatacfg['ipsmode'] == 'legacy' && $suricatacfg['blockoffenders'] == '1' && $suricatacfg['passlistname'] != 'none') {
	$plist = suricata_build_list($suricatacfg, $suricatacfg['passlistname'], true);
	@file_put_contents("{$suricatacfgdir}/passlist", implode("\n", $plist));
}
else {
	file_put_contents("{$suricatacfgdir}/passlist", '');
}

// Set default and user-defined variables for SERVER_VARS and PORT_VARS
$suricata_servers = array (
	"dns_servers" => "\$HOME_NET", "smtp_servers" => "\$HOME_NET", "http_servers" => "\$HOME_NET",
	"sql_servers" => "\$HOME_NET", "telnet_servers" => "\$HOME_NET", "dnp3_server" => "\$HOME_NET",
	"dnp3_client" => "\$HOME_NET", "modbus_server" => "\$HOME_NET", "modbus_client" => "\$HOME_NET",
	"enip_server" => "\$HOME_NET", "enip_client" => "\$HOME_NET", "ftp_servers" => "\$HOME_NET", "ssh_servers" => "\$HOME_NET",
	"aim_servers" => "64.12.24.0/23, 64.12.28.0/23, 64.12.161.0/24, 64.12.163.0/24, 64.12.200.0/24, 205.188.3.0/24, 205.188.5.0/24, 205.188.7.0/24, 205.188.9.0/24, 205.188.153.0/24, 205.188.179.0/24, 205.188.248.0/24",
	"sip_servers" => "\$HOME_NET"
);
$addr_vars = "";
	foreach ($suricata_servers as $alias => $avalue) {
		if (!empty($suricatacfg["def_{$alias}"]) && is_alias($suricatacfg["def_{$alias}"])) {
			$avalue = trim(filter_expand_alias($suricatacfg["def_{$alias}"]));
			$avalue = preg_replace('/\s+/', ', ', trim($avalue));
		}
		$addr_vars .= "    " . strtoupper($alias) . ": \"{$avalue}\"\n";
	}
$addr_vars = trim($addr_vars);
if(is_array($config['system']['ssh']) && isset($config['system']['ssh']['port']))
        $ssh_port = $config['system']['ssh']['port'];
else
        $ssh_port = "22";
$suricata_ports = array(
	"ftp_ports" => "21",
	"http_ports" => "80",
	"oracle_ports" => "1521",
	"ssh_ports" => $ssh_port,
	"shellcode_ports" => "!80",
	"DNP3_PORTS" => "20000",
	"file_data_ports" => "\$HTTP_PORTS, 110, 143",
	"sip_ports" => "5060, 5061, 5600"
);
$port_vars = "";
	foreach ($suricata_ports as $alias => $avalue) {
		if (!empty($suricatacfg["def_{$alias}"]) && is_alias($suricatacfg["def_{$alias}"])) {
			$avalue = trim(filter_expand_alias($suricatacfg["def_{$alias}"]));
			$avalue = preg_replace('/\s+/', ', ', trim($avalue));
		}
		$port_vars .= "    " . strtoupper($alias) . ": \"{$avalue}\"\n";
	}
$port_vars = trim($port_vars);

// Define a Suppress List (Threshold) if one is configured
$suppress = suricata_find_list($suricatacfg['suppresslistname'], 'suppress');
if (!empty($suppress)) {
	$suppress_data = str_replace("\r", "", base64_decode($suppress['suppresspassthru']));
	@file_put_contents("{$suricatacfgdir}/threshold.config", $suppress_data);
}
else
	@file_put_contents("{$suricatacfgdir}/threshold.config", "");

// Add interface-specific performance and detection engine settings
if (!empty($suricatacfg['runmode']))
	$runmode = $suricatacfg['runmode'];
else
	$runmode = "autofp";
if (!empty($suricatacfg['autofpscheduler']))
	$autofp_scheduler = $suricatacfg['autofpscheduler'];
else
	$autofp_scheduler = "hash";
if (!empty($suricatacfg['maxpendingpackets']))
	$max_pend_pkts = $suricatacfg['maxpendingpackets'];
else
	$max_pend_pkts = 1024;

if (!empty($suricatacfg['detectengprofile']))
	$detect_eng_profile = $suricatacfg['detectengprofile'];
else
	$detect_eng_profile = "medium";

if (!empty($suricatacfg['sghmpmcontext']))
	$sgh_mpm_ctx = $suricatacfg['sghmpmcontext'];
else
	$sgh_mpm_ctx = "auto";

if (!empty($suricatacfg['mpmalgo']))
	$mpm_algo = $suricatacfg['mpmalgo'];
else
	$mpm_algo = "auto";

if (!empty($suricatacfg['inspectrecursionlimit']) || $suricatacfg['inspectrecursionlimit'] == '0')
	$inspection_recursion_limit = $suricatacfg['inspectrecursionlimit'];
else
	$inspection_recursion_limit = "";

if ($suricatacfg['delayeddetect'] == '1')
	$delayed_detect = "yes";
else
	$delayed_detect = "no";

if ($suricatacfg['intfpromiscmode'] == '1')
	$intf_promisc_mode = "yes";
else
	$intf_promisc_mode = "no";

if (!empty($suricatacfg['intfsnaplen'])) {
	$intf_snaplen = $suricatacfg['intfsnaplen'];
}
else {
	$intf_snaplen = "1518";
}

// Add interface-specific blocking settings
if ($suricatacfg['blockoffenders'] == '1' && $suricatacfg['ipsmode'] == 'legacy')
	$suri_blockoffenders = "yes";
else
	$suri_blockoffenders = "no";

if ($suricatacfg['blockoffenderskill'] == '1')
	$suri_killstates = "yes";
else
	$suri_killstates = "no";

if ($suricatacfg['blockdropsonly'] == '1')
	$suri_blockdrops = "yes";
else
	$suri_blockdrops = "no";

if ($suricatacfg['blockoffendersip'] == 'src')
	$suri_blockip = 'SRC';
elseif ($suricatacfg['blockoffendersip'] == 'dst')
	$suri_blockip = 'DST';
else
	$suri_blockip = 'BOTH';

$suri_pf_table = SURICATA_PF_TABLE;

// Add interface-specific logging settings
if ($suricatacfg['alertsystemlog'] == '1')
	$alert_syslog = "yes";
else
	$alert_syslog = "no";

if (!empty($suricatacfg['alertsystemlogfacility']))
	$alert_syslog_facility = $suricatacfg['alertsystemlogfacility'];
else
	$alert_syslog_facility = "local5";

if (!empty($suricatacfg['alertsystemlogpriority']))
	$alert_syslog_priority = $suricatacfg['alertsystemlogpriority'];
else
	$alert_syslog_priority = "Info";

/****************************************/
/* Begin stats collection configuration */
/****************************************/
if ($suricatacfg['enablestatscollection'] == '1')
	$stats_collection_enabled = "yes";
else
	$stats_collection_enabled = "no";

if ($suricatacfg['enablestatscollection'] == '1' && $suricatacfg['enabletelegrafstats'] == '1' && !empty(base64_decode($suricatacfg['suricatatelegrafunixsocketname']))) {
	$enable_telegraf_eve = "yes";
	$telegraf_eve_sockname = base64_decode($suricatacfg['suricatatelegrafunixsocketname']);
}
else {
	$enable_telegraf_eve = "no";
	$telegraf_eve_sockname = "";
}

if (!empty($suricatacfg['statsupdinterval']))
	$stats_upd_interval = $suricatacfg['statsupdinterval'];
else
	$stats_upd_interval = "10";

if ($suricatacfg['appendstatslog'] == '1')
	$stats_log_append = "yes";
else
	$stats_log_append = "no";

if ($suricatacfg['enablestatscollection'] == '1' && $suricatacfg['enablestatslog'] == '1') {
	$stats_log_enabled = "yes";
}
else {
	$stats_log_enabled = "no";
}
/****************************************/
/* End stats collection configuration   */
/****************************************/

if ($suricatacfg['enablehttplog'] == '1')
	$http_log_enabled = "yes";
else
	$http_log_enabled = "no";

if ($suricatacfg['appendhttplog'] == '1')
	$http_log_append = "yes";
else
	$http_log_append = "no";

if ($suricatacfg['httplogextended'] == '1')
	$http_log_extended = "yes";
else
	$http_log_extended = "no";

if ($suricatacfg['enabletlslog'] == '1')
	$tls_log_enabled = "yes";
else
	$tls_log_enabled = "no";

if ($suricatacfg['enabletlsstore'] == '1')
	$tls_store_enabled = "yes";
else
	$tls_store_enabled = "no";

if ($suricatacfg['tlslogextended'] == '1')
	$tls_log_extended = "yes";
else
	$tls_log_extended = "no";

if ($suricatacfg['enablefilestore'] == '1') {
	$file_store_enabled = "yes";
	if (!empty($suricatacfg['filestoreloggingdir'])) {
		$file_store_logdir = base64_decode($suricatacfg['filestoreloggingdir']);
	}
	else {
		$file_store_logdir = "filestore";
	}
}
else {
	$file_store_enabled = "no";
	$file_store_logdir = "filestore";
}

if ($suricatacfg['enablepcaplog'] == '1')
	$pcap_log_enabled = "yes";
else
	$pcap_log_enabled = "no";

if (!empty($suricatacfg['maxpcaplogsize']))
	$pcap_log_limit_size = $suricatacfg['maxpcaplogsize'];
else
	$pcap_log_limit_size = "32";

if (!empty($suricatacfg['maxpcaplogfiles']))
	$pcap_log_max_files = $suricatacfg['maxpcaplogfiles'];
else
	$pcap_log_max_files = "1000";

// Unified2 X-Forwarded-For logging options
if ($suricatacfg['barnyardxfflogging'] == '1') {
	$unified2_xff_output = "xff:";
	$unified2_xff_output .= "\n        enabled: yes";
	if (!empty($suricatacfg['barnyard_xff_mode']))
		$unified2_xff_output .= "\n        mode: {$suricatacfg['barnyardxffmode']}";
	else
		$unified2_xff_output .= "\n        mode: extra-data";
	if (!empty($suricatacfg['barnyard_xff_deployment']))
		$unified2_xff_output .= "\n        deployment: {$suricatacfg['barnyardxffdeployment']}";
	else
		$unified2_xff_output .= "\n        deployment: reverse";
	if (!empty($suricatacfg['barnyard_xff_header']))
		$unified2_xff_output .= "\n        header: {$suricatacfg['barnyardxffheader']}";
	else
		$unified2_xff_output .= "\n        header: X-Forwarded-For";
}
else {
	$unified2_xff_output = "xff:";
	$unified2_xff_output .= "\n        enabled: no";
}

// EVE JSON log output settings
if ($suricatacfg['enableevelog'] == '1')
	$enable_eve_log = "yes";
else
	$enable_eve_log = "no";

if (!empty($suricatacfg['eveoutputtype']))
	$eve_output_type = $suricatacfg['eveoutputtype'];
else
	$eve_output_type = "regular";

// EVE SYSLOG output settings
if (!empty($suricatacfg['evesystemlogfacility']))
	$eve_systemlog_facility = $suricatacfg['evesystemlogfacility'];
else
	$eve_systemlog_facility = "local1";

if (!empty($suricatacfg['evesystemlogpriority']))
	$eve_systemlog_priority = $suricatacfg['evesystemlogpriority'];
else
	$eve_systemlog_priority = "info";

// EVE REDIS output settings
if (!empty($suricatacfg['everedisserver']))
	$eve_redis_output = "\n        server: ". $suricatacfg['everedisserver'];
else
	$eve_redis_output = "\n        server: 127.0.0.1";

if (!empty($suricatacfg['everedisport']))
	$eve_redis_output .= "\n        port: " . $suricatacfg['everedisport'];

if (!empty($suricatacfg['everedismode']))
	$eve_redis_output .= "\n        mode: " . $suricatacfg['everedismode'];

if (!empty($suricatacfg['everediskey']))
	$eve_redis_output .= "\n        key: \"" . $suricatacfg['everediskey'] ."\"";

// EVE X-Forwarded-For settings
if ($suricatacfg['evelogalertsxff'] == '1'){
	$eve_xff_enabled = "yes";
	$eve_xff_mode = $suricatacfg['evelogalertsxffmode'];
	$eve_xff_deployment = $suricatacfg['evelogalertsxffdeployment'];
	$eve_xff_header = $suricatacfg['evelogalertsxffheader'];
}
else {
	$eve_xff_enabled = "no";
	$eve_xff_mode = $suricatacfg['evelogalertsxffmode'];
	$eve_xff_deployment = $suricatacfg['evelogalertsxffdeployment'];
	$eve_xff_header = $suricatacfg['evelogalertsxffheader'];
}

// EVE log output included information
$eve_out_types = "";

if ($suricatacfg['evelogalerts'] == '1') {
	$eve_out_types .= "\n        - alert:";
	$eve_out_types .= "\n            payload: ".(($suricatacfg['evelogalertspayload'] == '1' || $suricatacfg['evelogalertspayload'] == 'onlybase64') ?'yes':'no ')."              # enable dumping payload in Base64";
	$eve_out_types .= "\n            payload-buffer-size: 4kb  # max size of payload buffer to output in eve-log";
	$eve_out_types .= "\n            payload-printable: ".(($suricatacfg['evelogalertspayload'] == '1' || $suricatacfg['evelogalertspayload'] == 'onlyprintable') ?'yes':'no ')."    # enable dumping payload in printable (lossy) format";
	$eve_out_types .= "\n            packet: ".(($suricatacfg['evelogalertspacket'] == '1')?'yes':'no ')."               # enable dumping of packet (without stream segments)";
	$eve_out_types .= "\n            http-body: ".(($suricatacfg['evelogalertspayload'] == '1' || $suricatacfg['evelogalertspayload'] == 'onlybase64') ?'yes':'no ')."            # enable dumping of http body in Base64";
	$eve_out_types .= "\n            http-body-printable: ".(($suricatacfg['evelogalertspayload'] == '1' || $suricatacfg['evelogalertspayload'] == 'onlyprintable') ?'yes':'no ')."  # enable dumping of http body in printable format";
	$eve_out_types .= "\n            metadata: ".(($suricatacfg['evelogalertsmetadata'] == '1')?'yes':'no ')."             # enable inclusion of app layer metadata with alert";
	$eve_out_types .= "\n            tagged-packets: yes       # enable logging of tagged packets for rules using the 'tag' keyword";
}

if (($suricatacfg['eveloganomaly'] == '1')) {
	$eve_out_types .= "\n        - anomaly:";
	$eve_out_types .= "\n            enabled: yes";
	$eve_out_types .= "\n            types:";
	if ($suricatacfg['eveloganomalytypedecode'] == '1') {
		$eve_out_types .= "\n              decode: yes";
	}
	else {
		$eve_out_types .= "\n              decode: no";
	}
	if ($suricatacfg['eveloganomalytypestream'] == '1') {
		$eve_out_types .= "\n              stream: yes";
	}
	else {
		$eve_out_types .= "\n              stream: no";
	}
	if ($suricatacfg['eveloganomalytypeapplayer'] == '1') {
		$eve_out_types .= "\n              applayer: yes";
	}
	else {
		$eve_out_types .= "\n              applayer: no";
	}
	if ($suricatacfg['eveloganomalypackethdr'] == '1') {
		$eve_out_types .= "\n            packethdr: yes";
	}
	else {
		$eve_out_types .= "\n            packethdr: no";
	}
}

if ($suricatacfg['eveloghttp'] == '1') {
	$eve_out_types .= "\n        - http:";
	if ($suricatacfg['eveloghttpextended'] == '1') {
		$eve_out_types .= "\n            extended: yes";
		if ($suricatacfg['eveloghttpextendedheaders'] != "")
			$eve_out_types .= "\n            custom: [".$suricatacfg['eveloghttpextendedheaders']."]";
         } else {
                $eve_out_types .= "\n            extended: no";
         }
}

if ($suricatacfg['evelogdns'] == '1') {
	$eve_out_types .= "\n        - dns:";
	$eve_out_types .= "\n            version: 2";
	$eve_out_types .= "\n            query: yes";
	$eve_out_types .= "\n            answer: yes";
}

if ($suricatacfg['evelogtls'] == '1') {
	$eve_out_types .= "\n        - tls:";
	if ($suricatacfg['evelogtlsextended'] == '1')
		$eve_out_types .= "\n            extended: yes";
	else
		$eve_out_types .= "\n            extended: no";
	if($suricatacfg['evelogtlsextendedfields'] != "")
		$eve_out_types .= "\n            custom: [".$suricatacfg['evelogtlsextendedfields']."]";
}

if ($suricatacfg['evelogdhcp'] == '1') {
	$eve_out_types .= "\n        - dhcp:";
	if ($suricatacfg['evelogdhcpextended'] == '1')
		$eve_out_types .= "\n            extended: yes";
	else
		$eve_out_types .= "\n            extended: no";
}

if ($suricatacfg['evelogfiles'] == '1') {
	$eve_out_types .= "\n        - files:";
	if ($suricatacfg['evelogfilesmagic'] == '1')
		$eve_out_types .= "\n            force-magic: yes";
	else
		$eve_out_types .= "\n            force-magic: no";
	if ($suricatacfg['evelogfileshash'] != 'none') {
		$eve_out_types .= "\n            force-hash: {$suricatacfg['evelogfileshash']}";
	}
}

$eveloggedinfo = array_merge(explode(',', $suricatacfg['eveloggedtraffic']), explode(',', $suricatacfg['eveloggedinfo']));

if (in_array('ssh',$eveloggedinfo)) {
	$eve_out_types .= "\n        - ssh";
}

if (in_array('nfs',$eveloggedinfo)) {
	$eve_out_types .= "\n        - nfs";
}

if (in_array('smb',$eveloggedinfo)) {
	$eve_out_types .= "\n        - smb";
}

if (in_array('kerberos',$eveloggedinfo)) {
	$eve_out_types .= "\n        - krb5";
}

if (in_array('ikev2',$eveloggedinfo)) {
	$eve_out_types .= "\n        - ikev2";
}

if (in_array('tftp',$eveloggedinfo)) {
	$eve_out_types .= "\n        - tftp";
}

if (in_array('rdp',$eveloggedinfo)) {
	$eve_out_types .= "\n        - rdp";
}

if (in_array('sip',$eveloggedinfo)) {
	$eve_out_types .= "\n        - sip";
}

if (in_array('snmp',$eveloggedinfo)) {
	$eve_out_types .= "\n        - snmp";
}

if (in_array('ftp',$eveloggedinfo)) {
	$eve_out_types .= "\n        - ftp";
}

if (in_array('http2',$eveloggedinfo)) {
	$eve_out_types .= "\n        - http2";
}

if (in_array('rfb',$eveloggedinfo)) {
	$eve_out_types .= "\n        - rfb";
}

if (in_array('mqtt',$eveloggedinfo)) {
	$eve_out_types .= "\n        - mqtt";
}

if (in_array('smtp',$eveloggedinfo)) {
	$eve_out_types .= "\n        - smtp:";
	if (!empty($suricatacfg['evelogsmtpextendedfields']))
		$eve_out_types .= "\n            extended: yes";
	else
		$eve_out_types .= "\n            extended: no";
	if($suricatacfg['evelogsmtpextendedfields'] != "")
		$eve_out_types .= "\n            custom: [".$suricatacfg['evelogsmtpextendedfields']."]";

	$eve_out_types .= "\n            md5: [subject]";
}

if ($suricatacfg['evelogdrop'] == '1' && $suricatacfg['ipsmode'] == "inline") {
	$eve_out_types .= "\n        - drop:";
	$eve_out_types .= "\n            alerts: yes";
	$eve_out_types .= "\n            flows: all";
}

if ($suricatacfg['evelogstats'] == '1'){
	$eve_out_types .= "\n        - stats:";
	$eve_out_types .= "\n            totals: ".($suricatacfg['evelogstatstotals'] == '1'?'yes':'no');
	$eve_out_types .= "\n            deltas: ".($suricatacfg['evelogstatsdeltas'] == '1'?'yes':'no');
	$eve_out_types .= "\n            threads: ".($suricatacfg['evelogstatsthreads'] == '1'?'yes':'no');
}

if (in_array('flow',$eveloggedinfo)) {
	$eve_out_types .= "\n        - flow                        # Bi-directional flows";
}

if (in_array('netflow',$eveloggedinfo)) {
	$eve_out_types .= "\n        - netflow                     # Uni-directional flows";
}

// Add interface-specific IP defrag settings
if (!empty($suricatacfg['fragmemcap']))
	$frag_memcap = $suricatacfg['fragmemcap'];
else
	$frag_memcap = "33554432";

if (!empty($suricatacfg['ipmaxtrackers']))
	$ip_max_trackers = $suricatacfg['ipmaxtrackers'];
else
	$ip_max_trackers = "65535";

if (!empty($suricatacfg['ipmaxfrags']))
	$ip_max_frags = $suricatacfg['ipmaxfrags'];
else
	$ip_max_frags = "65535";

if (!empty($suricatacfg['fraghashsize']))
	$frag_hash_size = $suricatacfg['fraghashsize'];
else
	$frag_hash_size = "65536";

if (!empty($suricatacfg['ipfragtimeout']))
	$ip_frag_timeout = $suricatacfg['ipfragtimeout'];
else
	$ip_frag_timeout = "60";

// Add interface-specific flow manager setttings
if (!empty($suricatacfg['flowmemcap']))
	$flow_memcap = $suricatacfg['flowmemcap'];
else
	$flow_memcap = "33554432";

if (!empty($suricatacfg['flowhashsize']))
	$flow_hash_size = $suricatacfg['flowhashsize'];
else
	$flow_hash_size = "65536";

if (!empty($suricatacfg['flowprealloc']))
	$flow_prealloc = $suricatacfg['flowprealloc'];
else
	$flow_prealloc = "10000";

if (!empty($suricatacfg['flowemergrecovery']))
	$flow_emerg_recovery = $suricatacfg['flowemergrecovery'];
else
	$flow_emerg_recovery = "30";

if (!empty($suricatacfg['flowprune']))
	$flow_prune = $suricatacfg['flowprune'];
else
	$flow_prune = "5";

// Add interface-specific flow timeout setttings
if (!empty($suricatacfg['flowtcpnewtimeout']))
	$flow_tcp_new_timeout = $suricatacfg['flowtcpnewtimeout'];
else
	$flow_tcp_new_timeout = "60";

if (!empty($suricatacfg['flowtcpestablishedtimeout']))
	$flow_tcp_established_timeout = $suricatacfg['flowtcpestablishedtimeout'];
else
	$flow_tcp_established_timeout = "3600";

if (!empty($suricatacfg['flowtcpclosedtimeout']))
	$flow_tcp_closed_timeout = $suricatacfg['flowtcpclosedtimeout'];
else
	$flow_tcp_closed_timeout = "120";

if (!empty($suricatacfg['flowtcpemergnewtimeout']))
	$flow_tcp_emerg_new_timeout = $suricatacfg['flowtcpemergnewtimeout'];
else
	$flow_tcp_emerg_new_timeout = "10";

if (!empty($suricatacfg['flowtcpemergestablishedtimeout']))
	$flow_tcp_emerg_established_timeout = $suricatacfg['flowtcpemergestablishedtimeout'];
else
	$flow_tcp_emerg_established_timeout = "300";

if (!empty($suricatacfg['flowtcpemergclosedtimeout']))
	$flow_tcp_emerg_closed_timeout = $suricatacfg['flowtcpemergclosedtimeout'];
else
	$flow_tcp_emerg_closed_timeout = "20";

if (!empty($suricatacfg['flowudpnewtimeout']))
	$flow_udp_new_timeout = $suricatacfg['flowudpnewtimeout'];
else
	$flow_udp_new_timeout = "30";

if (!empty($suricatacfg['flowudpestablishedtimeout']))
	$flow_udp_established_timeout = $suricatacfg['flowudpestablishedtimeout'];
else
	$flow_udp_established_timeout = "300";

if (!empty($suricatacfg['flowudpemergnewtimeout']))
	$flow_udp_emerg_new_timeout = $suricatacfg['flowudpemergnewtimeout'];
else
	$flow_udp_emerg_new_timeout = "10";

if (!empty($suricatacfg['flowudpemergestablishedtimeout']))
	$flow_udp_emerg_established_timeout = $suricatacfg['flowudpemergestablishedtimeout'];
else
	$flow_udp_emerg_established_timeout = "100";

if (!empty($suricatacfg['flowicmpnewtimeout']))
	$flow_icmp_new_timeout = $suricatacfg['flowicmpnewtimeout'];
else
	$flow_icmp_new_timeout = "30";

if (!empty($suricatacfg['flowicmpestablishedtimeout']))
	$flow_icmp_established_timeout = $suricatacfg['flowicmpestablishedtimeout'];
else
	$flow_icmp_established_timeout = "300";

if (!empty($suricatacfg['flowicmpemergnewtimeout']))
	$flow_icmp_emerg_new_timeout = $suricatacfg['flowicmpemergnewtimeout'];
else
	$flow_icmp_emerg_new_timeout = "10";

if (!empty($suricatacfg['flowicmpemergestablishedtimeout']))
	$flow_icmp_emerg_established_timeout = $suricatacfg['flowicmpemergestablishedtimeout'];
else
	$flow_icmp_emerg_established_timeout = "100";

// Add interface-specific stream settings
if (!empty($suricatacfg['streammemcap']))
	$stream_memcap = $suricatacfg['streammemcap'];
else
	$stream_memcap = "131217728";

if (!empty($suricatacfg['streampreallocsessions']))
	$stream_prealloc_sessions = $suricatacfg['streampreallocsessions'];
else
	$stream_prealloc_sessions = "32768";

if (!empty($suricatacfg['reassemblymemcap']))
	$reassembly_memcap = $suricatacfg['reassemblymemcap'];
else
	$reassembly_memcap = "131217728";

if (!empty($suricatacfg['reassemblydepth']) || $suricatacfg['reassemblydepth'] == '0')
	$reassembly_depth = $suricatacfg['reassemblydepth'];
else
	$reassembly_depth = "1048576";

if (!empty($suricatacfg['reassemblytoserverchunk']))
	$reassembly_to_server_chunk = $suricatacfg['reassemblytoserverchunk'];
else
	$reassembly_to_server_chunk = "2560";

if (!empty($suricatacfg['reassemblytoclientchunk']))
	$reassembly_to_client_chunk = $suricatacfg['reassemblytoclientchunk'];
else
	$reassembly_to_client_chunk = "2560";

if (!empty($suricatacfg['maxsynackqueued']))
	$max_synack_queued = $suricatacfg['maxsynackqueued'];
else
	$max_synack_queued = "5";

if ($suricatacfg['enablemidstreamsessions'] == '1')
	$stream_enable_midstream = "true";
else
	$stream_enable_midstream = "false";

if ($suricatacfg['enableasyncsessions'] == '1')
	$stream_enable_async = "true";
else
	$stream_enable_async = "false";

if ($suricatacfg['streambypass'] == 'yes' || $suricatacfg['tlsencrypthandling'] == 'bypass')
	$stream_bypass_enable = "yes";
else
	$stream_bypass_enable = "no";

if ($suricatacfg['streamdropinvalid'] == 'yes')
	$stream_drop_invalid_enable = "yes";
else
	$stream_drop_invalid_enable = "no";

// Add the OS-specific host policies if configured, otherwise
// just set default to BSD for all networks.
$host_os_policy = "";
if (!is_array($suricatacfg['hostospolicy']))
	$suricatacfg['hostospolicy'] = array();
if (!is_array($suricatacfg['hostospolicy']['item']))
	$suricatacfg['hostospolicy']['item'] = array();
if (count($suricatacfg['hostospolicy']['item']) < 1)
	$host_os_policy = "bsd: [0.0.0.0/0]";
else {
	foreach ($suricatacfg['hostospolicy']['item'] as $k => $v) {
		$engine = "{$v['policy']}: ";
		if ($v['bind_to'] <> "all") {
			$tmp = trim(filter_expand_alias($v['bind_to']));
			if (!empty($tmp)) {
				$engine .= "[";
				$tmp = preg_replace('/\s+/', ',', $tmp);
				$list = explode(',', $tmp);
				foreach ($list as $addr) {
					if (is_ipaddrv6($addr) || is_subnetv6($addr))
						$engine .= "\"{$addr}\", ";
					elseif (is_ipaddrv4($addr) || is_subnetv4($addr))
						$engine .= "{$addr}, ";
					else
						syslog(LOG_WARNING, "[suricata] WARNING: invalid IP address value '{$addr}' in Alias {$v['bind_to']} will be ignored.");
				}
				$engine = trim($engine, ' ,');
				$engine .= "]";
			}
			else {
				syslog(LOG_WARNING, "[suricata] WARNING: unable to resolve IP List Alias '{$v['bind_to']}' for Host OS Policy '{$v['name']}' ... ignoring this entry.");
				continue;
			}
		}
		else
			$engine .= "[0.0.0.0/0]";

		$host_os_policy .= "  {$engine}\n";
	}
	// Remove trailing newline
	$host_os_policy = trim($host_os_policy);
}

// Add the HTTP Server-specific policies if configured, otherwise
// just set default to IDS for all networks.
$http_hosts_policy = "";
$http_hosts_default_policy = "";
if (!is_array($suricatacfg['libhtppolicy']))
	$suricatacfg['libhtppolicy'] = array();
if (!is_array($suricatacfg['libhtppolicy']['item']))
	$suricatacfg['libhtppolicy']['item'] = array();
if (count($suricatacfg['libhtppolicy']['item']) < 1) {
	$http_hosts_default_policy = "personality: IDS\n     request-body-limit: 4096\n     response-body-limit: 4096\n";
	$http_hosts_default_policy .= "     double-decode-path: no\n     double-decode-query: no\n     uri-include-all: no\n";
}
else {
	foreach ($suricatacfg['libhtppolicy']['item'] as $k => $v) {
		if ($v['bind_to'] <> "all") {
			$engine = "server-config:\n     - {$v['name']}:\n";
			$tmp = trim(filter_expand_alias($v['bind_to']));
			if (!empty($tmp)) {
				$engine .= "         address: [";
				$tmp = preg_replace('/\s+/', ',', $tmp);
				$list = explode(',', $tmp);
				foreach ($list as $addr) {
					if (is_ipaddrv6($addr) || is_subnetv6($addr))
						$engine .= "\"{$addr}\", ";
					elseif (is_ipaddrv4($addr) || is_subnetv4($addr))
						$engine .= "{$addr}, ";
					else {
						syslog(LOG_WARNING, "[suricata] WARNING: invalid IP address value '{$addr}' in Alias {$v['bind_to']} will be ignored.");
						continue;
					}
				}
				$engine = trim($engine, ' ,');
				$engine .= "]\n";
				$engine .= "         personality: {$v['personality']}\n         request-body-limit: {$v['request-body-limit']}\n";
				$engine .= "         response-body-limit: {$v['response-body-limit']}\n";
				$engine .= "         meta-field-limit: " . (isset($v['meta-field-limit']) ? $v['meta-field-limit'] : "18432") . "\n";
				$engine .= "         double-decode-path: {$v['double-decode-path']}\n";
				$engine .= "         double-decode-query: {$v['double-decode-query']}\n";
				$engine .= "         uri-include-all: {$v['uri-include-all']}\n";
				$http_hosts_policy .= "   {$engine}\n";
			}
			else {
				syslog(LOG_WARNING, "[suricata] WARNING: unable to resolve IP List Alias '{$v['bind_to']}' for Host OS Policy '{$v['name']}' ... ignoring this entry.");
				continue;
			}
		}
		else {
			$http_hosts_default_policy = "personality: {$v['personality']}\n     request-body-limit: {$v['request-body-limit']}\n";
			$http_hosts_default_policy .= "     response-body-limit: {$v['response-body-limit']}\n";
			$http_hosts_default_policy .= "     meta-field-limit: " . (isset($v['meta-field-limit']) ? $v['meta-field-limit'] : "18432") . "\n";
			$http_hosts_default_policy .= "     double-decode-path: {$v['double-decode-path']}\n";
			$http_hosts_default_policy .= "     double-decode-query: {$v['double-decode-query']}\n";
			$http_hosts_default_policy .= "     uri-include-all: {$v['uri-include-all']}\n";
		}
	}
	// Remove any leading or trailing spaces and newline
	$http_hosts_default_policy = trim($http_hosts_default_policy);
	$http_hosts_policy = trim($http_hosts_policy);
}

// Configure ASN1 max frames value
if (!empty($suricatacfg['asn1maxframes']))
	$asn1_max_frames = $suricatacfg['asn1maxframes'];
else
	$asn1_max_frames = "256";

// Configure App-Layer Parsers/Detection
if (!empty($suricatacfg['dcerpcparser']))
	$dcerpc_parser = $suricatacfg['dcerpcparser'];
else
	$dcerpc_parser = "yes";
if (!empty($suricatacfg['ftpparser']))
	$ftp_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['ftpparser']);
else
	$ftp_parser = "yes";
if ($suricatacfg['ftpdataparser'] == '1')
	$ftp_data_parser = "yes";
else
	$ftp_data_parser = "no";
if (!empty($suricatacfg['sshparser']))
	$ssh_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['sshparser']);
else
	$ssh_parser = "yes";
if (!empty($suricatacfg['imapparser']))
	$imap_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['imapparser']);
else
	$imap_parser = "detection-only";
if (!empty($suricatacfg['msnparser']))
	$msn_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['msnparser']);
else
	$msn_parser = "detection-only";
if (!empty($suricatacfg['smbparser']))
	$smb_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['smbparser']);
else
	$smb_parser = "yes";
if (!empty($suricatacfg['krb5parser']))
	$krb5_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['krb5parser']);
else
	$krb5_parser = "yes";
if (!empty($suricatacfg['ikev2parser']))
	$ikev2_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['ikev2parser']);
else
	$ikev2_parser = "yes";
if (!empty($suricatacfg['nfsparser']))
	$nfs_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['nfsparser']);
else
	$nfs_parser = "yes";
if (!empty($suricatacfg['tftpparser']))
	$tftp_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['tftpparser']);
else
	$tftp_parser = "yes";
if (!empty($suricatacfg['ntpparser']))
	$ntp_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['ntpparser']);
else
	$ntp_parser = "yes";
if (!empty($suricatacfg['dhcpparser']))
	$dhcp_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['dhcpparser']);
else
	$dhcp_parser = "yes";

if (!empty($suricatacfg['http2parser']))
	$http2_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['http2parser']);
else
	$http2_parser = "no";
if (!empty($suricatacfg['rfbparser']))
	$rfb_parser = str_replace('detectiononly', 'detection-only', $suricatacfg['rfbparser']);
else
	$rfb_parser = "yes";

/* DNS Parser */
if (!empty($suricatacfg['dnsparsertcp']))
	$dns_parser_tcp = $suricatacfg['dnsparsertcp'];
else
	$dns_parser_tcp = "yes";
if (!empty($suricatacfg['dnsparserudp']))
	$dns_parser_udp = $suricatacfg['dnsparserudp'];
else
	$dns_parser_udp = "yes";
if (!empty($suricatacfg['dnsparserudpports'])) {
	if (is_alias($suricatacfg['dnsparserudpports'])) {
		$dns_parser_udp_port = trim(filter_expand_alias($suricatacfg['dnsparserudpports']));
		$dns_parser_udp_port = preg_replace('/\s+/', ', ', trim($dns_parser_udp_port));
	}
	else {
		$dns_parser_udp_port = $suricatacfg['dnsparserudpports'];
	}
}
else {
	$dns_parser_udp_port = "443";
}
if (!empty($suricatacfg['dnsparsertcpports'])) {
	if (is_alias($suricatacfg['dnsparsertcpports'])) {
		$dns_parser_tcp_port = trim(filter_expand_alias($suricatacfg['dnsparsertcpports']));
		$dns_parser_tcp_port = preg_replace('/\s+/', ', ', trim($dns_parser_tcp_port));
	}
	else {
		$dns_parser_tcp_port = $suricatacfg['dnsparsertcpports'];
	}
}
else {
	$dns_parser_tcp_port = "443";
}
if (!empty($suricatacfg['dnsglobalmemcap']))
	$dns_global_memcap = $suricatacfg['dnsglobalmemcap'];
else
	$dns_global_memcap = "16777216";
if (!empty($suricatacfg['dnsstatememcap']))
	$dns_state_memcap = $suricatacfg['dnsstatememcap'];
else
	$dns_state_memcap = "524288";
if (!empty($suricatacfg['dnsrequestfloodlimit']))
	$dns_request_flood_limit = $suricatacfg['dnsrequestfloodlimit'];
else
	$dns_request_flood_limit = "500";

/* HTTP Parser */
if (!empty($suricatacfg['httpparser']))
	$http_parser = $suricatacfg['httpparser'];
else
	$http_parser = "yes";
if (!empty($suricatacfg['httpparsermemcap']))
	$http_parser_memcap = $suricatacfg['httpparsermemcap'];
else
	$http_parser_memcap = "67108864";

/* SMTP Parser */
if (!empty($suricatacfg['smtpparser'])) {
	$smtp_parser = $suricatacfg['smtpparser'];
}
else {
	$smtp_parser = "yes";
}
if ($suricatacfg['smtpparserdecodemime'] == "1") {
	$smtp_decode_mime = "yes";
}
else {
	$smtp_decode_mime = "no";
}
if ($suricatacfg['smtpparserdecodebase64'] == "1") {
	$smtp_decode_base64 = "yes";
}
else {
	$smtp_decode_base64 = "no";
}
if ($suricatacfg['smtpparserdecodequotedprintable'] == "1") {
	$smtp_decode_quoted_printable = "yes";
}
else {
	$smtp_decode_quoted_printable = "no";
}
if ($suricatacfg['smtpparserextracturls'] == "1") {
	$smtp_extract_urls = "yes";
}
else {
	$smtp_extract_urls = "no";
}
if ($suricatacfg['smtpparsercomputebodymd5'] == "1") {
	$smtp_body_md5 = "yes";
}
else {
	$smtp_body_md5 = "no";
}

/* TLS Parser */
if (!empty($suricatacfg['tlsparser'])) {
	$tls_parser = $suricatacfg['tlsparser'];
}
else {
	$tls_parser = "yes";
}
if (!empty($suricatacfg['tlsdetectports'])) {
	if (is_alias($suricatacfg['tlsdetectports'])) {
		$tls_detect_port = trim(filter_expand_alias($suricatacfg['tlsdetectports']));
		$tls_detect_port = preg_replace('/\s+/', ', ', trim($tls_detect_port));
	}
	else {
		$tls_detect_port = $suricatacfg['tlsdetectports'];
	}
}
else {
	$tls_detect_port = "443";
}
if (!empty($suricatacfg['tlsja3fingerprint'])) {
	$tls_ja3 = $suricatacfg['tlsja3fingerprint'];
}
else {
	$tls_ja3 = "auto";
}
if (!empty($suricatacfg['tlsencrypthandling'])) {
	$tls_encrypt_handling = $suricatacfg['tlsencrypthandling'];

	// If TLS encryption bypass is enabled, then stream bypass
	// must also be forced to "yes" for bypass to happen.
	if ($tls_encrypt_handling == "bypass") {
		$stream_bypass_enable = "yes";
	}
}
else {
	$tls_encrypt_handling = "default";
}

 /* RDP Parser */
if (!empty($suricatacfg['rdpparser'])) {
	$rdp_parser = $suricatacfg['rdpparser'];
}
else {
	$rdp_parser = "yes";
}

/* SIP Parser */
if (!empty($suricatacfg['sipparser'])) {
	$sip_parser = $suricatacfg['sipparser'];
}
else {
	$sip_parser = "yes";
}

/* SNMP Parser */
if (!empty($suricatacfg['snmpparser'])) {
	$snmp_parser = $suricatacfg['snmpparser'];
}
else {
	$snmp_parser = "yes";
}

/* Configure the IP REP section */
$iprep_path = rtrim(SURICATA_IPREP_PATH, '/');
$iprep_config = "# IP Reputation\n";
if ($suricatacfg['enableiprep'] == "1") {
	$iprep_config .= "default-reputation-path: {$iprep_path}\n";
	$iprep_config .= "reputation-categories-file: {$iprep_path}/{$suricatacfg['iprep_catlist']}\n";
	$iprep_config .= "reputation-files:";

	if (!is_array($suricatacfg['iplistfiles']))
		$suricatacfg['iplistfiles'] = array();
	if (!is_array($suricatacfg['iplistfiles']['item']))
		$suricatacfg['iplistfiles']['item'] = array();

	foreach ($suricatacfg['iplistfiles']['item'] as $f)
		$iprep_config .= "\n  - $f";
}

/* Configure Host Table settings */
if (!empty($suricatacfg['hostmemcap']))
	$host_memcap = $suricatacfg['hostmemcap'];
else
	$host_memcap = "33554432";
if (!empty($suricatacfg['hosthashsize']))
	$host_hash_size = $suricatacfg['hosthashsize'];
else
	$host_hash_size = "4096";
if (!empty($suricatacfg['hostprealloc']))
	$host_prealloc = $suricatacfg['hostprealloc'];
else
	$host_prealloc = "1000";

// Create the rules files and save in the interface directory
suricata_prepare_rule_files($suricatacfg, $suricatacfgdir);

// Check and configure only non-empty rules files for the interface
$rules_files = "";
if (file_exists("{$suricatacfgdir}/rules/".SURICATA_ENFORCING_RULES_FILENAME)) {
	if (filesize("{$suricatacfgdir}/rules/".SURICATA_ENFORCING_RULES_FILENAME) > 0)
		$rules_files .= SURICATA_ENFORCING_RULES_FILENAME;
}
if (file_exists("{$suricatacfgdir}/rules/".FLOWBITS_FILENAME)) {
	if (filesize("{$suricatacfgdir}/rules/".FLOWBITS_FILENAME) > 0)
		$rules_files .= "\n - " . FLOWBITS_FILENAME;
}
if (file_exists("{$suricatacfgdir}/rules/custom.rules")) {
	if (filesize("{$suricatacfgdir}/rules/custom.rules") > 0)
		$rules_files .= "\n - custom.rules";
}
$rules_files = ltrim($rules_files, '\n -');

// Add the general logging settings to the configuration (non-interface specific)
if ($config['OPNsense']['suricata']['global']['logtosystemlog'] == '1')
	$suricata_use_syslog = "yes";
else
	$suricata_use_syslog = "no";

if (!empty($config['OPNsense']['suricata']['global']['logtosystemlogfacility']))
	$suricata_use_syslog_facility = $config['OPNsense']['suricata']['global']['logtosystemlogfacility'];
else
	$suricata_use_syslog_facility = "local1";

if (!empty($config['OPNsense']['suricata']['global']['logtosystemlogpriority']))
	$suricata_use_syslog_priority = $config['OPNsense']['suricata']['global']['logtosystemlogpriority'];
else
	$suricata_use_syslog_priority = "notice";

// Configure IPS operational mode
if ($suricatacfg['ipsmode'] == 'inline' && $suricatacfg['blockoffenders'] == '1') {
	// Get 'netmap_threads' parameter, if set
	$netmap_threads_param = 'auto';
	if (intval($suricatacfg['ipsnetmapthreads']) > 0) {
		$netmap_threads_param = $suricatacfg['ipsnetmapthreads'];
	}

	$if_netmap = $realif;

	// For VLAN interfaces, need to actually run Suricata
	// on the parent interface, so override interface name.
	if (interface_is_vlan($realif)) {
		$intf_list = get_parent_interface($realif);
		$if_netmap = $intf_list[0];
		syslog(LOG_WARNING, "[suricata] WARNING: interface '{$realif}' is a VLAN, so configuring Suricata to run on the parent interface, '{$if_netmap}', instead.");
	}

	// Note -- Netmap promiscuous mode logic is backwards from pcap
	$netmap_intf_promisc_mode = $intf_promisc_mode == 'yes' ? 'no' : 'yes';
	$suricata_ips_mode = <<<EOD
# Netmap
netmap:
 - interface: default
   threads: {$netmap_threads_param}
   copy-mode: ips
   disable-promisc: {$netmap_intf_promisc_mode}
   checksum-checks: auto
 - interface: {$if_netmap}
   threads: {$netmap_threads_param}
   copy-mode: ips
   copy-iface: {$if_netmap}^
 - interface: {$if_netmap}^
   threads: {$netmap_threads_param}
   copy-mode: ips
   copy-iface: {$if_netmap}
EOD;
}
else {
	$suricata_ips_mode = <<<EOD
# PCAP
pcap:
  - interface: {$realif}
    checksum-checks: auto
    promisc: {$intf_promisc_mode}
    snaplen: {$intf_snaplen}
EOD;
}

$suricata_config_pass_thru = base64_decode($suricatacfg['configpassthru']);

?>
