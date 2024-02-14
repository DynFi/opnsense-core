<?php

/*
 * Copyright (C) 2023 Deciso B.V.
 * Copyright (C) 2015 Jos Schellevis <jos@opnsense.org>
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

namespace OPNsense\Diagnostics\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\Core\Backend;
use OPNsense\Core\Config;

/**
 * Class SystemhealthController
 * @package OPNsense\SystemHealth
 */
class SystemhealthController extends ApiControllerBase
{
    /**
     * retrieve descriptive details of rrd
     * @param string $rrd rrd category - item
     * @return array result status and data
     */
    private function getRRDdetails($rrd)
    {
        # Source of data: xml fields of corresponding .xml metadata
<<<<<<< HEAD
        $result = array();
        $backend = new Backend();
        $response = $backend->configdRun('health list');
=======
        $result = [];
        $response = (new Backend())->configdRun('health list');
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
        $healthList = json_decode($response, true);
        // search by topic and name, return array with filename
        if (is_array($healthList)) {
            foreach ($healthList as $filename => $healthItem) {
                if ($healthItem['itemName'] . '-' . $healthItem['topic'] == $rrd) {
                    $result["result"] = "ok";
                    $healthItem['filename'] = $filename;
                    $result["data"] = $healthItem;
                    return $result;
                }
            }
        }

        // always return a valid (empty) data set
        $result["result"] = "not found";
        $result["data"] = ["title" => "","y-axis_label" => "","field_units" => [], "itemName" => "", "filename" => ""];
        return $result;
    }


    /**
     * retrieve Available RRD data
     * @return array
     */
    public function getRRDlistAction()
    {
        # Source of data: filelisting of /var/db/rrd/*.rrd
<<<<<<< HEAD
        $result = array();
        $backend = new Backend();
        $response = $backend->configdRun('health list');
        $healthList = json_decode($response, true);

        $result['data'] = array();
=======
        $result = ['data' => []];
        $healthList = json_decode((new Backend())->configdRun('health list'), true);
        $interfaces = $this->getInterfacesAction();
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
        if (is_array($healthList)) {
            foreach ($healthList as $healthItem => $details) {
                if (!array_key_exists($details['topic'], $result['data'])) {
                    $result['data'][$details['topic']] = [];
                }
                if (in_array($details['topic'], ['packets', 'traffic'])) {
                    if (isset($interfaces[$details['itemName']])) {
                        $desc = $interfaces[$details['itemName']]['descr'];
                    } else {
                        $desc =  $details['itemName'];
                    }
                    $result['data'][$details['topic']][$details['itemName']] = $desc;
                } else {
                    $result['data'][$details['topic']][] = $details['itemName'];
                }
            }
        }
        foreach (['packets', 'traffic'] as $key) {
            if (isset($result['data'][$key])) {
                natcasesort($result['data'][$key]);
                $result['data'][$key] = array_keys($result['data'][$key]);
            }
        }

        ksort($result['data']);

        $result["interfaces"] = $interfaces;
        $result["result"] = "ok";

        // Category => Items
        return $result;
    }

    /**
     * retrieve SystemHealth Data (previously called RRD Graphs)
     * @param string $rrd
     * @param bool $inverse
     * @param int $detail
     * @return array
     */
    public function getSystemHealthAction($rrd = "", $inverse = 0, $detail = -1)
    {
        $rrd_details = $this->getRRDdetails($rrd)["data"];
<<<<<<< HEAD
        $xml = false;
        if ($rrd_details['filename'] != "") {
            $backend = new Backend();
            $response = $backend->configdpRun('health fetch', [$rrd_details['filename']]);
            if ($response != null) {
                $xml = @simplexml_load_string($response);
            }
        }

        if ($xml !== false) {
            // we only use the average databases in any RRD, remove the rest to avoid strange behaviour.
            for ($count = count($xml->rra) - 1; $count >= 0; $count--) {
                if (trim((string)$xml->rra[$count]->cf) != "AVERAGE") {
                    unset($xml->rra[$count]);
=======
        $response = (new Backend())->configdpRun('health fetch', [$rrd_details['filename']]);
        $response = json_decode($response ?? '', true);
        if (!empty($response)) {
            $response['set'] = ['count' => 0, 'data' => [], 'step_size' => 0];
            if (isset($response['sets'][$detail])) {
                $records = $response['sets'][$detail]['ds'];
                $response['set']['count'] = count($records);
                $response['set']['step_size'] = $response['sets'][$detail]['step_size'];
                foreach ($records as $key => $record) {
                    $record['area'] = true;
                    if ($inverse && $key % 2 != 0) {
                        foreach ($record['values'] as &$value) {
                            $value[1] = $value[1] * -1;
                        }
                    }
                    $response['set']['data'][] = $record;
>>>>>>> b9317ee4e6376c6b547e0621d45f2ece81d05423
                }
            }
            for ($i = 0; $i < count($response['sets']); $i++) {
                unset($response['sets'][$detail]['ds']);
            }
            if (!empty($rrd_details["title"])) {
                $response['title'] = $rrd_details["title"] . " | " . ucfirst($rrd_details['itemName']);
            } else {
                $response['title'] = ucfirst($rrd_details['itemName']);
            }
            $response['y-axis_label'] = $rrd_details["y-axis_label"];
            return $response;
        } else {
            return ["sets" => [], "set" => [], "title" => "error", "y-axis_label" => ""];
        }
    }

    /**
     * Retrieve network interfaces by key (lan, wan, opt1,..)
     * @return array
     */
    public function getInterfacesAction()
    {
        // collect interface names
        $intfmap = [];
        $config = Config::getInstance()->object();
        if ($config->interfaces->count() > 0) {
            foreach ($config->interfaces->children() as $key => $node) {
                $intfmap[(string)$key] = ["descr" => !empty((string)$node->descr) ? (string)$node->descr : $key];
            }
        }
        return $intfmap;
    }
}
