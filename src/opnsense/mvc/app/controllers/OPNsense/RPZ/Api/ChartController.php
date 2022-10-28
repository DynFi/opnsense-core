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
        $result = [ 'categories' => [], 'per_category' => [] ];

        $counted = [];
        $per_category = [];
        $total = 0;

        $data = $this->_prepareData();
        foreach ($data as $d) {
            extract($d);
            if (!isset($counted[$category]))
                $counted[$category] = 0;
            $counted[$category] += $number;
            $total += $number;

            if (!isset($per_category[$category]))
                $per_category[$category] = [];
            if (!isset($per_category[$category][$domain]))
                $per_category[$category][$domain] = 0;
            $per_category[$category][$domain] += $number;
        }

        foreach ($counted as $label => $value) {
            $result['categories'][] = [ 'label' => $label, 'value' => $value, 'total' => $total ];
        }

        $maxnr = 10;

        foreach ($per_category as $category => $_domains) {
            $result['per_category'][$category] = [];
            arsort($_domains);
            $domains = array_slice($_domains, 0, $maxnr, true);
            $other = array_slice($_domains, $maxnr, null, true);
            foreach ($domains as $label => $value) {
                $result['per_category'][$category][] = [ 'label' => $label, 'value' => $value ];
            }
            if (!empty($other)) {
                $cnt = 0;
                foreach ($other as $label => $value) {
                    $cnt += $value;
                }
                $result['per_category'][$category][] = [ 'label' => 'other', 'value' => $cnt ];
            }
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
