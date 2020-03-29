<?php

/*
 * Copyright (C) 2020 Dawid Kujawa <dawid.kujawa@dynfi.com>
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

define('HEADER_BUTTON_DEFS', [
    'System' => [
        'Access' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'General', 'url' => '/diag_logs.php'] ]
        ],
        'Firmware' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'Firmware', 'url' => '/diag_logs_pkg.php'] ]
        ],
        'Gateways' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'Gateways', 'url' => '/diag_logs_gateways.php'] ]
        ],
        'High Availability' => [
            'name' => 'Status',
            'iconClass' => 'icon glyphicon glyphicon-dashboard',
            'buttons' => [ ['name' => 'Status', 'url' => '/status_habackup.php'] ]
        ],
        'Routes' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'Routes', 'url' => '/diag_logs_routing.php'] ]
        ],
        'Settings' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [
                ['name' => 'Backend', 'url' => '/configd_logs.php'],
                ['name' => 'General', 'url' => '/diag_logs.php'],
                ['name' => 'Web GUI', 'url' => '/httpd_logs.php']
            ]
        ],
        'Trust' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'General', 'url' => '/diag_logs.php'] ]
        ],
        'Diagnostics' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'General', 'url' => '/diag_logs.php'] ]
        ]
    ],
    'Interfaces' => [
        'Wireless' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'Wireless', 'url' => '/diag_logs_wireless.php'] ]
        ],
        'Point-to-Point' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'Wireless', 'url' => '/diag_logs_ppp.php'] ]
        ],
    ],
    'Firewall' => [
        'Aliases' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [
                ['name' => 'Live', 'url' => '/ui/diagnostics/firewall/log'],
                ['name' => 'Plain', 'url' => '/diag_logs_filter_plain.php'],
                ['name' => 'Overview', 'url' => '/diag_logs_filter_summary.php']
            ]
        ],
        'Rules' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [
                ['name' => 'Live', 'url' => '/ui/diagnostics/firewall/log'],
                ['name' => 'Plain', 'url' => '/diag_logs_filter_plain.php'],
                ['name' => 'Overview', 'url' => '/diag_logs_filter_summary.php']
            ]
        ],
        'NAT' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [
                ['name' => 'Live', 'url' => '/ui/diagnostics/firewall/log'],
                ['name' => 'Plain', 'url' => '/diag_logs_filter_plain.php'],
                ['name' => 'Overview', 'url' => '/diag_logs_filter_summary.php']
            ]
        ],
        'Groups' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [
                ['name' => 'Live', 'url' => '/ui/diagnostics/firewall/log'],
                ['name' => 'Plain', 'url' => '/diag_logs_filter_plain.php'],
                ['name' => 'Overview', 'url' => '/diag_logs_filter_summary.php']
            ]
        ],
        'Shaper' => [
            'name' => 'Status',
            'iconClass' => 'icon glyphicon glyphicon-dashboard',
            'buttons' => [ ['name' => 'Status', 'url' => '/diag_limiter_info.php'] ]
        ],
        'Virtual IPs' => [
            'name' => 'Status',
            'iconClass' => 'icon glyphicon glyphicon-dashboard',
            'buttons' => [ ['name' => 'Status', 'url' => '/carp_status.php'] ]
        ]
    ],
    'VPN' => [
        'IPsec' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'IPsec', 'url' => '/diag_logs_ipsec.php'] ]
        ],
        'OpenVPN' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'OpenVPN', 'url' => '/diag_logs_openvpn.php'] ]
        ]
    ],
    'Services' => [
        'DHCPv4' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'DHCPv4', 'url' => '/diag_logs_dhcp.php'] ]
        ],
        'DHCPv6' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'DHCPv6', 'url' => '/diag_logs_dhcp.php'] ]
        ],
        'Captive Portal' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'Captive Portal', 'url' => '/diag_logs_auth.php'] ]
        ],
        'Dnsmasq DNS' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'Dnsmasq DNS', 'url' => '/diag_logs_dnsmasq.php'] ]
        ],
        'Intrusion Detection' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'Intrusion Detection', 'url' => '/diag_logs_suricata.php'] ]
        ],
        'Monit' => [
            'name' => 'Status',
            'iconClass' => 'icon glyphicon glyphicon-dashboard',
            'buttons' => [ ['name' => 'Status', 'url' => '/ui/monit/status'] ]
        ],
        'Network Time' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'Network Time', 'url' => '/diag_logs_ntpd.php'] ]
        ],
        'Unbound DNS' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'Unbound DNS', 'url' => '/diag_logs_resolver.php'] ]
        ],
        'Web Proxy' => [
            'name' => 'Log',
            'iconClass' => 'icon glyphicon glyphicon-list',
            'buttons' => [ ['name' => 'Web Proxy', 'url' => '/diag_logs_proxy.php?type=cache'] ]
        ]
    ]
]);

function getHeaderButtons($breadcrumbs) {
  if (count($breadcrumbs) >= 2) {
    $main = $breadcrumbs[0]['name'];
    $sub = $breadcrumbs[1]['name'];
    if (isset(HEADER_BUTTON_DEFS[$main])) {
        if (isset(HEADER_BUTTON_DEFS[$main]['name'])) {
            return HEADER_BUTTON_DEFS[$main];
        }

        if (isset(HEADER_BUTTON_DEFS[$main][$sub])) {
            return HEADER_BUTTON_DEFS[$main][$sub];
        }
      }
  }
  return [];
}
