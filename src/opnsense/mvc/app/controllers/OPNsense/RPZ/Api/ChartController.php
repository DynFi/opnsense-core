<?php

/**
 *    Copyright (C) 2022 DynFi
 *
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace OPNsense\RPZ\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\Core\Backend;
use OPNsense\Core\Config;

/**
 * @package OPNsense\RPZ
 */
class ChartController extends ApiControllerBase
{
    public function getCategoriesChartDataAction() {
        $result = [ 'categories' => [], 'top_sites' => [], 'top_offenders' => [] ];

        $counted = [];
        $top_sites = [];
        $top_offenders = [];
        $total = 0;

        $data = $this->_prepareData();
        foreach ($data as $d) {
            extract($d);
            if (!isset($counted[$category]))
                $counted[$category] = 0;
            $counted[$category] += $number;
            $total += $number;

            if (!isset($top_sites[$category]))
                $top_sites[$category] = [];
            if (!isset($top_sites[$category][$domain]))
                $top_sites[$category][$domain] = 0;
            $top_sites[$category][$domain] += $number;

            if (!isset($top_offenders[$category]))
                $top_offenders[$category] = [];
            if (!isset($top_offenders[$category][$ip]))
                $top_offenders[$category][$ip] = 0;
            $top_offenders[$category][$ip] += $number;
        }

        foreach ($counted as $label => $value) {
            $result['categories'][] = [ 'label' => $label, 'value' => $value, 'total' => $total ];
        }

        $maxnr = 10;

        foreach ($top_sites as $category => $_domains) {
            $result['top_sites'][$category] = [];
            arsort($_domains);
            $domains = array_slice($_domains, 0, $maxnr, true);
            $other = array_slice($_domains, $maxnr, null, true);
            foreach ($domains as $label => $value) {
                $result['top_sites'][$category][] = [ 'label' => $label, 'value' => $value ];
            }
            if (!empty($other)) {
                $cnt = 0;
                foreach ($other as $label => $value) {
                    $cnt += $value;
                }
                $result['top_sites'][$category][] = [ 'label' => 'other', 'value' => $cnt ];
            }
        }

        foreach ($top_offenders as $category => $_ips) {
            $result['top_offenders'][$category] = [];
            arsort($_ips);
            $ips = array_slice($_ips, 0, $maxnr, true);
            $other = array_slice($_ips, $maxnr, null, true);
            foreach ($ips as $label => $value) {
                $result['top_offenders'][$category][] = [ 'label' => $label, 'value' => $value ];
            }
            if (!empty($other)) {
                $cnt = 0;
                foreach ($other as $label => $value) {
                    $cnt += $value;
                }
                $result['top_offenders'][$category][] = [ 'label' => 'other', 'value' => $cnt ];
            }
        }

        return $result;
    }


    public function getTableDataSitesAction($category) {
        $result = [];

        $data = $this->_prepareData();
        $sites = [];
        $total = 0;
        foreach ($data as $d) {
            if ($d['category'] != $category)
                continue;
            if (!isset($sites[$d['domain']]))
                $sites[$d['domain']] = 0;
            $sites[$d['domain']] += $d['number'];
            $total += $d['number'];
        }

        arsort($sites);
        foreach ($sites as $domain => $number) {
            $result[] = array('domain' => $domain, 'number' => $number, 'percent' => round(100.0 * $number / $total, 2));
        }

        return $result;
    }


    public function getTableDataOffendersAction($category) {
        $result = [];

        $data = $this->_prepareData();
        $offs = [];
        $total = 0;
        foreach ($data as $d) {
            if ($d['category'] != $category)
                continue;
            $k = $d['domain'].'|'.$d['ip'];
            if (!isset($offs[$k]))
                $offs[$k] = 0;
            $offs[$k] += $d['number'];
        }

        arsort($offs);
        foreach ($offs as $k => $number) {
            $arr = explode('|', $k);
            $result[] = array('domain' => $arr[0], 'ip' => $arr[1], 'number' => $number);
        }

        return $result;
    }


    private function _prepareData() {
        if (isset($_SESSION['rpz-chart-cache'])) {
            if ($_SESSION['rpz-chart-cache']['timestamp'] > (time() - 30))
                return $_SESSION['rpz-chart-cache']['data'];
        }

        $data = [];
        $backend = new Backend();

        $stats = array_filter(explode("\n", $backend->configdpRun("rpz stats")));
        foreach ($stats as $row) {
            $arr = explode(" ", $row);
            $c_arr = explode("-", $arr[1]);
            $data[] = array(
                'number' => intval($arr[0]),
                'category' => array_shift($c_arr),
                'domain' => $arr[2],
                'ip' => $arr[3]
            );
        }

        $_SESSION['rpz-chart-cache'] = array(
            'timestamp' => time(),
            'data' => $data
        );

        return $data;
    }
}
