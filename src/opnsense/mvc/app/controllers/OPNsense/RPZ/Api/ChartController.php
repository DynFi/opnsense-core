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

        $backend = new Backend();

        $response = $backend->configdpRun("system diag log", array(0, 0, "applied", "core", "resolver"));

        $counted = [];
        $per_category = [];

        $data = json_decode($response, true);
        if ($data != null) {
            foreach ($data['rows'] as $row) {
                $matches = array();
                preg_match('/applied \[([^\\]]*)\]/', $row['line'], $matches);
                if (count($matches) > 1) {
                    $c = $matches[1];

                    $arr = explode('['.$c.'] ', $row['line']);
                    $_l = array_pop($arr);
                    $arr = explode(' ', $_l);
                    $domain = array_shift($arr);

                    $arr = explode('-', $c);
                    $category = array_shift($arr);

                    if (!isset($counted[$category]))
                        $counted[$category] = 0;
                    $counted[$category]++;

                    if (!isset($per_category[$category]))
                        $per_category[$category] = [];
                    if (!isset($per_category[$category][$domain]))
                        $per_category[$category][$domain] = 0;
                    $per_category[$category][$domain]++;
                }
            }
        }

        foreach ($counted as $label => $value) {
            $result['categories'][] = [ 'label' => $label, 'value' => $value ];
        }

        foreach ($per_category as $category => $domains) {
            $result['per_category'][$category] = [];
            foreach ($domains as $label => $value) {
                $result['per_category'][$category][] = [ 'label' => $label, 'value' => $value ];
            }
        }

        return $result;
    }
}
