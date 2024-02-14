<?php

/*
 * Copyright (C) 2023 Deciso B.V.
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

namespace OPNsense\Interfaces\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\Core\Backend;
use OPNsense\Core\Config;
use OPNsense\Firewall\Util;
use OPNsense\Routing\Gateways;

class OverviewController extends ApiControllerBase
{
    private static function translations()
    {
        return [
            'flags' => gettext('Flags'),
            'options' => gettext('Options'),
            'supported_media' => gettext('Supported Media'),
            'is_physical' => gettext('Physical'),
            'device' => gettext('Device'),
            'name' => gettext('Name'),
            'description' => gettext('Description'),
            'status' => gettext('Status'),
            'enabled' => gettext('Enabled'),
            'link_type' => gettext('Link Type'),
            'ipv4' => gettext('IPv4 Addresses'),
            'ipv6' => gettext('IPv6 Addresses'),
            'gateways' => gettext('Gateways'),
            'routes' => gettext('Routes'),
            'macaddr' => gettext('MAC Address'),
            'media_raw' => gettext('Media'),
            'mediaopt' => gettext('Media Options'),
            'capabilities' => gettext('Capabilities'),
            'identifier' => gettext('Identifier'),
            'ipaddr' => gettext('IP Address'),
            'subnetbits' => gettext('Subnet Bits'),
            'statistics' => gettext('Statistics'),
            'driver' => gettext('Driver'),
            'index' => gettext('Index'),
            'promiscuous listeners' => gettext('Promiscuous Listeners'),
            'send queue length' => gettext('Send Queue Length'),
            'send queue max length' => gettext('Send Queue Max Length'),
            'send queue drops' => gettext('Send Queue Drops'),
            'type' => gettext('Type'),
            'address length' => gettext('Address Length'),
            'header length' => gettext('Header Length'),
            'link state' => gettext('Link State'),
            'datalen' => gettext('Data Length'),
            'metric' => gettext('Metric'),
            'line rate' => gettext('Line Rate'),
            'packets received' => gettext('Packets Received'),
            'input errors' => gettext('Input Errors'),
            'packets transmitted' => gettext('Packets Transmitted'),
            'output errors' => gettext('Output Errors'),
            'collisions' => gettext('Collisions'),
            'bytes received' => gettext('Bytes Received'),
            'bytes transmitted' => gettext('Bytes Transmitted'),
            'multicasts received' => gettext('Multicasts Received'),
            'multicasts transmitted' => gettext('Multicasts Transmitted'),
            'input queue drops' => gettext('Input Queue Drops'),
            'packets for unknown protocol' => gettext('Packets for Unknown Protocol'),
            'HW offload capabilities' => gettext('Hardware Offload Capabilities'),
            'uptime at attach or stat reset' => gettext('Uptime at Attach or Statistics Reset'),
        ];
    }

    private function parseIfInfo($interface = null, $detailed = false)
    {
        $backend = new Backend();
        $gateways = new Gateways();
        $cfg = Config::getInstance()->object();
        $result = [];

        /* quick information */
        $ifinfo = json_decode($backend->configdpRun('interface list ifconfig', [$interface]), true);
        $routes = json_decode($backend->configdRun('interface routes list -n json'), true);

        /* detailed information */
        if ($detailed) {
            $stats = json_decode($backend->configdpRun('interface list stats', [$interface]), true);
            if (!$interface) {
                foreach ($ifinfo as $if => $info) {
                    if (array_key_exists($if, $stats)) {
                        $ifinfo[$if]['statistics'] = $stats[$if];
                    }
                }
            } else {
                $ifinfo[$interface]['statistics'] = $stats;
            }
        }

        /* map routes to interfaces */
        foreach ($routes as $route) {
            if (!empty($route['netif']) && !empty($ifinfo[$route['netif']])) {
                $ifinfo[$route['netif']]['routes'][] = $route['destination'];
            }
        }

        /* combine interfaces details with config */
        foreach ($cfg->interfaces->children() as $key => $node) {
            if (!empty((string)$node->if) && !empty($ifinfo[(string)$node->if])) {
                $props = [];
                foreach ($node->children() as $property) {
                    $props[$property->getName()] = (string)$property;
                }
                $ifinfo[(string)$node->if]['config'] = $props;
                $ifinfo[(string)$node->if]['config']['identifier'] = $key;
            }
        }

        /* format information */
        foreach ($ifinfo as $if => $details) {
            $tmp = $details;

            if ($if == 'pfsync0') {
                continue;
            }

            $tmp['status'] = (!empty($details['flags']) && in_array('up', $details['flags'])) ? 'up' : 'down';
            if (!empty($details['status'])) {
                if (!(in_array($details['status'], ['active', 'running']))) {
                    /* reflect current ifconfig status, such as 'no carrier' */
                    $tmp['status'] = $details['status'];
                }
            }

            if (empty($details['config'])) {
                $tmp['identifier'] = '';
                $tmp['description'] = gettext('Unassigned Interface');
                $result[] = $tmp;
                continue;
            }

            $config = $details['config'];

            $tmp['identifier'] = $config['identifier'];
            $tmp['description'] = !empty($config['descr']) ? $config['descr'] : strtoupper($config['identifier']);
            $tmp['enabled'] = !empty($config['enable']);
            $tmp['link_type'] = !empty($config['ipaddr']) ? $config['ipaddr'] : 'none';
            if (Util::isIpAddress($tmp['link_type'])) {
                $tmp['link_type'] = 'static';
            }

            /* parse IP configuration */
            unset($tmp['ipv4'], $tmp['ipv6']);
            foreach (['ipv4', 'ipv6'] as $ipproto) {
                if (!empty($details[$ipproto])) {
                    foreach ($details[$ipproto] as $ip) {
                        if (!empty($ip['ipaddr'])) {
                            $entry = [];
                            $entry['ipaddr'] = $ip['ipaddr'] . '/' . $ip['subnetbits'];

                            if (!empty($ip['vhid'])) {
                                $vhid = $ip['vhid'];
                                $entry['vhid'] = $vhid;

                                if (!empty($details['carp'])) {
                                    foreach ($details['carp'] as $carp) {
                                        if ($carp['vhid'] == $vhid) {
                                            $entry['status'] = $carp['status'];
                                            $entry['advbase'] = $carp['advbase'];
                                            $entry['advskew'] = $carp['advskew'];
                                        }
                                    }
                                }
                            }

                            $tmp[$ipproto][] = $entry;
                        }
                    }
                }
            }

            /* gateway(s) */
            $gatewayv4 =  $gateways->getInterfaceGateway($tmp['identifier'], 'inet');
            $gatewayv6 = $gateways->getInterfaceGateway($tmp['identifier'], 'inet6');
            $tmp['gateways'] = array_values(array_filter([$gatewayv4, $gatewayv6]));

            $result[] = $tmp;
        }

        return $result;
    }

    public function interfacesInfoAction($details = false)
    {
        $this->sessionClose();
        $result = $this->parseIfInfo(null, $details);
        return $this->searchRecordsetBase(
            $result,
            ['status', 'description', 'device', 'link_type', 'ipv4', 'ipv6', 'gateways', 'routes']
        );
    }

    public function getInterfaceAction($if = null)
    {
        $this->sessionClose();
        $result = ["message" => "failed"];
        if ($if != null) {
            $ifinfo = $this->parseIfInfo($if, true)[0] ?? [];
            if (!empty($ifinfo)) {
                if (!empty($ifinfo['macaddr'])) {
                    $macs = json_decode((new Backend())->configdRun('interface list macdb'), true);
                    $mac_hi = strtoupper(substr(str_replace(':', '', $ifinfo['macaddr']), 0, 6));
                    if (array_key_exists($mac_hi, $macs)) {
                        $ifinfo['macaddr'] = $ifinfo['macaddr'] . ' - ' . $macs[$mac_hi];
                    }
                }

                /* move statistics one level up */
                if (isset($ifinfo['statistics'])) {
                    $stats = $ifinfo['statistics'];
                    unset($ifinfo['statistics']);

                    $ifinfo = array_merge($ifinfo, $stats);
                }

                unset($ifinfo['config']);

                /* apply translations */
                foreach ($ifinfo as $key => $value) {
                    $ifinfo[$key] = [
                        'value' => $value,
                        'translation' => self::translations()[$key] ?? $key
                    ];
                }

                $result['message'] = $ifinfo;
            }
        }

        return $result;
    }

    public function reloadInterfaceAction($identifier = null)
    {
        $this->sessionClose();
        $result = ["message" => "failed"];

        if ($identifier != null) {
            $backend = new Backend();
            $result['message'] = $backend->configdpRun('interface reconfigure', [$identifier]);
        }

        return $result;
    }

    public function exportAction()
    {
        $this->sessionClose();
        $this->response->setRawHeader('Content-Type: application/json');
        $this->response->setRawHeader('Content-Disposition: attachment; filename=ifconfig.json');
        echo json_encode($this->parseIfInfo(null, true));
    }
}
