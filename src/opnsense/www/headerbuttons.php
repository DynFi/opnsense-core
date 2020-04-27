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
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'General', 'url' => '/ui/diagnostics/log/core/system'] ]
            ]
        ],
        'Firmware' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'Firmware', 'url' => '/ui/diagnostics/log/core/pkg'] ]
            ]
        ],
        'Gateways' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'Gateways', 'url' => '/ui/diagnostics/log/core/gateways'] ]
            ]
        ],
        'High Availability' => [
            [
                'name' => 'Status',
                'iconClass' => 'icon glyphicon glyphicon-dashboard',
                'buttons' => [ ['name' => 'High Availability', 'url' => '/status_habackup.php'] ]
            ]
        ],
        'Routes' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'Routes', 'url' => '/ui/diagnostics/log/core/routing'] ]
            ]
        ],
        'Settings' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [
                    ['name' => 'Backend', 'url' => '/ui/diagnostics/log/core/configd'],
                    ['name' => 'General', 'url' => '/ui/diagnostics/log/core/system'],
                    ['name' => 'Web GUI', 'url' => '/ui/diagnostics/log/core/lighttpd']
                ]
            ]
        ],
        'Log' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [
                    ['name' => 'Backend', 'url' => '/ui/diagnostics/log/core/configd'],
                    ['name' => 'General', 'url' => '/ui/diagnostics/log/core/system'],
                    ['name' => 'Web GUI', 'url' => '/ui/diagnostics/log/core/lighttpd']
                ]
            ]
        ],
        'Trust' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'General', 'url' => '/ui/diagnostics/log/core/system'] ]
            ]
        ],
        'Diagnostics' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'General', 'url' => '/ui/diagnostics/log/core/system'] ]
            ]
        ]
    ],
    'Interfaces' => [
        'Wireless' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'Wireless', 'url' => '/ui/diagnostics/log/core/wireless'] ]
            ]
        ],
        'Point-to-Point' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'Point-to-Point', 'url' => '/ui/diagnostics/log/core/ppps'] ]
            ]
        ],
    ],
    'Firewall' => [
        'Aliases' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [
                    ['name' => 'Live', 'url' => '/ui/diagnostics/firewall/log'],
                    ['name' => 'Plain', 'url' => '/ui/diagnostics/log/core/filter'],
                    ['name' => 'Overview', 'url' => '/diag_logs_filter_summary.php']
                ]
            ]
        ],
        'Rules' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [
                    ['name' => 'Live', 'url' => '/ui/diagnostics/firewall/log'],
                    ['name' => 'Plain', 'url' => '/ui/diagnostics/log/core/filter'],
                    ['name' => 'Overview', 'url' => '/diag_logs_filter_summary.php']
                ]
            ]
        ],
        'NAT' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [
                    ['name' => 'Live', 'url' => '/ui/diagnostics/firewall/log'],
                    ['name' => 'Plain', 'url' => '/ui/diagnostics/log/core/filter'],
                    ['name' => 'Overview', 'url' => '/diag_logs_filter_summary.php']
                ]
            ]
        ],
        'Groups' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [
                    ['name' => 'Live', 'url' => '/ui/diagnostics/firewall/log'],
                    ['name' => 'Plain', 'url' => '/ui/diagnostics/log/core/filter'],
                    ['name' => 'Overview', 'url' => '/diag_logs_filter_summary.php']
                ]
            ]
        ],
        'Log' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [
                    ['name' => 'Live', 'url' => '/ui/diagnostics/firewall/log'],
                    ['name' => 'Plain', 'url' => '/ui/diagnostics/log/core/filter'],
                    ['name' => 'Overview', 'url' => '/diag_logs_filter_summary.php']
                ]
            ]
        ],
        'Shaper' => [
            [
                'name' => 'Status',
                'iconClass' => 'icon glyphicon glyphicon-dashboard',
                'buttons' => [ ['name' => 'Shaper', 'url' => '/ui/trafficshaper/service/statistics'] ]
            ]
        ],
        'Virtual IPs' => [
            [
                'name' => 'Status',
                'iconClass' => 'icon glyphicon glyphicon-dashboard',
                'buttons' => [ ['name' => 'Virtual IPs', 'url' => '/carp_status.php'] ]
            ]
        ]
    ],
    'VPN' => [
        'IPsec' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'IPsec', 'url' => '/ui/diagnostics/log/core/ipsec'] ]
            ], [
                'name' => 'Status',
                'iconClass' => 'icon glyphicon glyphicon-dashboard',
                'buttons' => [
                    ['name' => 'Overview', 'url' => '/diag_ipsec.php'],
                    ['name' => 'Lease', 'url' => '/diag_ipsec_leases.php'],
                    ['name' => 'Security Association Database', 'url' => '/diag_ipsec_sad.php'],
                    ['name' => 'Security Policy Database', 'url' => '/diag_ipsec_spd.php']
                ]
            ]
        ],
        'OpenVPN' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'OpenVPN', 'url' => '/ui/diagnostics/log/core/openvpn'] ]
            ], [
                'name' => 'Status',
                'iconClass' => 'icon glyphicon glyphicon-dashboard',
                'buttons' => [ ['name' => 'OpenVPN', 'url' => '/status_openvpn.php'] ]
            ]
        ]
    ],
    'Services' => [
        'DHCPv4' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'DHCPv4', 'url' => '/ui/diagnostics/log/core/dhcpd'] ]
            ], [
                'name' => 'Status',
                'iconClass' => 'icon glyphicon glyphicon-dashboard',
                'buttons' => [ ['name' => 'DHCPv4', 'url' => '/status_dhcp_leases.php'] ]
            ]
        ],
        'DHCPv6' => [
            [
                'name' => 'Status',
                'iconClass' => 'icon glyphicon glyphicon-dashboard',
                'buttons' => [ ['name' => 'DHCPv6', 'url' => '/status_dhcpv6_leases.php'] ]
            ]
        ],
        'Captive Portal' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'Captive Portal', 'url' => '/ui/diagnostics/log/core/portalauth'] ]
            ]
        ],
        'Dnsmasq DNS' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'Dnsmasq DNS', 'url' => '/ui/diagnostics/log/core/dnsmasq'] ]
            ]
        ],
        'Intrusion Detection' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'Intrusion Detection', 'url' => '/ui/diagnostics/log/core/suricata'] ]
            ]
        ],
        'Monit' => [
            [
                'name' => 'Status',
                'iconClass' => 'icon glyphicon glyphicon-dashboard',
                'buttons' => [ ['name' => 'Monit', 'url' => '/ui/monit/status'] ]
            ]
        ],
        'Network Time' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'Network Time', 'url' => '/ui/diagnostics/log/core/ntpd'] ]
            ], [
                'name' => 'Status',
                'iconClass' => 'icon glyphicon glyphicon-dashboard',
                'buttons' => [ ['name' => 'Network Time', 'url' => '/status_ntpd.php'] ]
            ]
        ],
        'Unbound DNS' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'Unbound DNS', 'url' => '/ui/diagnostics/log/core/resolver'] ]
            ], [
                'name' => 'Status',
                'iconClass' => 'icon glyphicon glyphicon-dashboard',
                'buttons' => [ ['name' => 'Unbound DNS', 'url' => '/ui/unbound/stats'] ]
            ]
        ],
        'Web Proxy' => [
            [
                'name' => 'Log',
                'iconClass' => 'icon glyphicon glyphicon-list',
                'buttons' => [ ['name' => 'Web Proxy', 'url' => '/ui/diagnostics/log/squid/cache'] ]
            ]
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

        $subsub = (count($breadcrumbs) >= 3) ? $breadcrumbs[2]['name'] : null;
        if ($subsub) {
            foreach (HEADER_BUTTON_DEFS[$main] as $name => $data) {
                foreach ($data as $item) {
                    if (($item['name'] == $sub) && ($name == $subsub)) {
                        $defs = [];
                        foreach (HEADER_BUTTON_DEFS[$main][$subsub] as $arr) {
                            if ($arr['name'] != $sub)
                                $defs[] = $arr;
                        }
                        return $defs;
                    }
                }
            }
        }

        if (isset(HEADER_BUTTON_DEFS[$main][$sub])) {
            return HEADER_BUTTON_DEFS[$main][$sub];
        }
      }
  }
  return [];
}


function getBreadcrumbsFromUrl($url) {
    $map = array();
    foreach (HEADER_BUTTON_DEFS as $name => $mdata) {
        foreach ($mdata as $data) {
            foreach ($data as $item) {
                foreach ($item['buttons'] as $b) {
                    $map[$b['url']] = array(array('name' => $name), array('name' => $item['name']), array('name' => $b['name']));
                }
            }
        }
    }
    return (isset($map[$url])) ? $map[$url] : null;
}
