<?php
/**
 *    Copyright (C) 2015 Deciso B.V. - J. Schellevis
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


namespace OPNsense\SystemHealth\Api;

use \OPNsense\Base\ApiControllerBase;
use \OPNsense\Core\Backend;
/**
 * Class ServiceController
 * @package OPNsense\SystemHealth
 */
class ServiceController extends ApiControllerBase
{

    /**
     * retrieve Available RRD data
     * @return array
     */
    public function getRRDlistAction()
    {
        # Suurce of data: filelisting of /var/db/rrd/*.rrd
        $result = array();

        $output = [
            "system" => ["processor","states", "mbuf"],
            "traffic" => ["lan", "wan", "ipsec"],
            "packets" => ["wan", "lan", "ipsec"],
            "quality" => ["GW_WAN"]

        ];
        $result["result"] = "ok";
        $result["data"] = $output;

        // Category => Items
        return $result;
    }


    private function getRRDdetails($rrd = "")
    {
        # Source of data: xml fields of corresponding .xml metadata
        $result = array();
        if ($rrd == "system-processor") {
            $backend = new Backend();
            $response = $backend->configdpRun("systemhealth query details"); #, array(1, 0, "filepos/".$id, $fileid));
            // $response = $backend->configdRun("configd actions");
            $xml=simplexml_load_string($response);
            $json = json_encode($xml);
            $output= json_decode($json, true);
//            $output = [
//                "title" => "System Information - Utilization and Processes",
//                "x-axis_label" => "[U]tilization ,  [#]Number",
//                "field_units" => [
//                    "user" => "[U]",
//                    "nice" => "[U]",
//                    "system" => "[U]",
//                    "interrupt" => "[#]",
//                    "processes" => "[#]"
//                ]
//            ];
            $result["result"] = "ok";
        } else {
            $result["result"] = "not found";
            $output=["title"=>"","x-axis_label"=>"","field_units"=>[]]; // always return a valid (empty) data set
        }

        $result["data"] = $output;

        return $result;
    }
    /**
     * retrieve SystemHealth Data (previously called RRD Graphs)
     * @param string $rrd
     * @param int $from
     * @param int $to
     * @param int $max_values
     * @param bool $inverse
     * @param int $detail
     * @return array
     */
    public function getSystemHealthAction(
        $rrd = "",
        $from = 0,
        $to = 0,
        $max_values = 120,
        $inverse = false,
        $detail = -1
    ) {
        /**
         * $rrd = rrd filename without extension
         * $from = from timestamp (0=min)
         * $to = to timestamp (0=max)
         * $max_values = limit datapoint as close as possible to this number (or twice if detail (zoom) + overview )
         * $inverse = Inverse every odd row (multiply by -1)
         * $detail = limits processing of dataSets to max given (-1 = all ; 1 = 0,1 ; 2 = 0,1,2 ; etc)
         */

        $rrd_details=$this->getRRDdetails($rrd)["data"];

        $rrd = $rrd . ".xml"; // Test data
        $xml = $this->getXMLdata($rrd);


        $data_sets_full = $this->getDataSetInfo($xml); // get dataSet information to include in answer

        if ($inverse == 'true') {
            $inverse = true;
        } else {
            $inverse = false;
        }

        if ((int)$detail >= 0) {
            for ($count = count($xml->rra); $count > $detail; $count--) {
                unset($xml->rra[$count]);
            }
        }

        // determine available dataSets within range and how to handle them
        $selected_archives = $this->getSelection($this->getDataSetInfo($xml), $from, $to, $max_values);
        // get condensed dataSets and translate them to d3 usable data
        $result = $this->translateD3(
            $this->getCondensedArchive($xml, $selected_archives),
            $inverse,
            $rrd_details["field_units"]
        );

        return ["sets" => $data_sets_full,
            "d3" => $result,
            "title"=>$rrd_details["title"],
            "x-axis_label"=>$rrd_details["x-axis_label"]
        ]; // return details and d3 data
    }

    /**
     * Return XML data dump for given rrd
     * @param $rrd
     * @return \SimpleXMLElement
     */
    private function getXMLdata($rrd)
    {
        # Source: rrdtool dump filename.rrd
        $xml = simplexml_load_file(__DIR__ . '/../../../../../../../../../../opnsense_gui/test/conf/' . $rrd);

        return $xml;
    }

    /**
     * Return full archive information
     * @param array $xml
     * @return array
     */
    private function getDataSetInfo($xml)
    {
        $info = array();
        if (isset($xml)) {
            $step = intval($xml->step);
            $lastUpdate = intval($xml->lastupdate);
            foreach ($xml->rra as $key => $value) {
                $step_size = (int)$value->pdp_per_row * $step;
                $first = floor(($lastUpdate / $step_size)) * $step_size -
                    ($step_size * (count($value->database->children()) - 1));
                $last = floor(($lastUpdate / $step_size)) * $step_size;
                $firstValue_rowNumber = (int)$this->findFirstValue($value);
                $firstValue_timestamp = (int)$first + ((int)$firstValue_rowNumber * $step_size);
                array_push($info, [
                    "step" => $step,
                    "pdp_per_row" => (int)$value->pdp_per_row,
                    "rowCount" => $this->countRows($value),
                    "first_timestamp" => (int)$first,
                    "last_timestamp" => (int)$last,
                    "firstValue_rowNumber" => $firstValue_rowNumber,
                    "firstValue_timestamp" => $firstValue_timestamp,
                    "available_rows" => ($this->countRows($value) - $firstValue_rowNumber),
                    "full_step" => ($step * (int)$value->pdp_per_row),
                    "recorded_time" => ($step * (int)$value->pdp_per_row) *
                        ($this->countRows($value) - $firstValue_rowNumber)
                ]);

            }
        }
        return ($info);
    }

    /**
     * Returns row number of first row with values other than 'NaN'
     * @param array $data
     * @return int
     */
    private function findFirstValue($data = array())
    {

        $rowNumber = 0;
        $containsValues = false; // used to break foreach on first row with collected data

        foreach ($data->database->row as $item => $row) {
            foreach ($row as $rowKey => $rowVal) {
                if (trim($rowVal) != "NaN") {
                    $containsValues = true;
                }
            }
            if ($containsValues == true) {
                break;
            }
            $rowNumber++;
        }

        return $rowNumber;
    }

    /**
     * Return total number of rows in rra
     * @param array $data
     * @return int
     */
    private function countRows($data = array())
    {
        $rowCount = 0;
        foreach ($data->database->row as $item => $row) {
            $rowCount++;
        }

        return $rowCount;
    }

    /**
     * internal: retrieve selections within range (0-0=full range) and limit number of datapoints (max_values)
     * @param array $rra_info
     * @param int $from_timestamp
     * @param int $to_timestamp
     * @param $max_values
     * @return array
     */
    private function getSelection($rra_info = array(), $from_timestamp = 0, $to_timestamp = 0, $max_values = 120)
    {
        $full_range = false;
        if ($from_timestamp == 0 && $to_timestamp == 0) {
            $full_range = true;
            $from_timestamp = $this->getMaxRange($rra_info)["oldest_timestamp"];
            $to_timestamp = $this->getMaxRange($rra_info)["newest_timestamp"];
        }

        $archives = array();
        // find archive match
        foreach ($rra_info as $key => $value) {
            if ($from_timestamp >= $value['firstValue_timestamp'] && $to_timestamp <= ($value['last_timestamp'] +
                    $value['full_step'])) {
                // calculate number of rows in set
                $rowCount = ($to_timestamp - $from_timestamp) / $value['full_step'] + 1;

                // factor to be used to compress the data.
                // example if 2 then 2 values will be used to calculate one data point.
                $condense_factor = round($rowCount / $max_values);

                if ($condense_factor == 0) { // if rounded to 0 we will not condense the data
                    $condense_factor = 1; // and thus return the full set of data points
                }
                // actual number of rows after compressing/condensing the dataSet
                $condensed_rowCount = (int)($rowCount / $condense_factor);

                // count the number if rra's (sets), deduct 1 as we need the counter to start at 0
                $last_rra_key = count($rra_info) - 1;

                // dynamic (condensed) values for full overview to detail level
                $overview = round($rra_info[$last_rra_key]["available_rows"] / (int)$max_values);

                if ($full_range == false) { // JSC WIP removed: && count($rra_info)==1  // add detail when selected
                    array_push($archives, [
                        "key" => $key,
                        "condensed_rowCount" => $condensed_rowCount,
                        "condense_by" => (int)$condense_factor,
                        "type" => "detail"
                    ]);
                } else { // add condensed detail
                    array_push($archives, [
                        "key" => $key,
                        "condensed_rowCount" => (int)($condensed_rowCount / ($rra_info[$last_rra_key]["pdp_per_row"] /
                                $value["pdp_per_row"])),
                        "condense_by" => (int)$condense_factor * ($rra_info[$last_rra_key]["pdp_per_row"] /
                                $value["pdp_per_row"]),
                        "type" => "detail"
                    ]);
                }
                // search for last dataSet with actual values, used to exclude sets that do not contain data
                for ($count = $last_rra_key; $count > 0; $count--) {
                    if ($rra_info[$count]["available_rows"] > 0) {
                        // Found last rra set with values
                        $last_rra_key = $count;
                        break;
                    }
                }
                array_push($archives, [
                    "key" => $last_rra_key,
                    "condensed_rowCount" => (int)($rra_info[$last_rra_key]["available_rows"] / $overview),
                    "condense_by" => (int)$overview,
                    "type" => "overview"
                ]);
                break;
            }
        }

        return (["from" => $from_timestamp, "to" => $to_timestamp, "full_range" => $full_range, "data" => $archives]);

    }

    /**
     * internal: get full available range
     * @param array $rra_info
     * @return array
     */
    private function getMaxRange($rra_info = array())
    {
        // count the number if rra's (sets), deduct 1 as we need the counter to start at 0
        $last_rra_key = count($rra_info) - 1;
        for ($count = $last_rra_key; $count > 0; $count--) {
            if ($rra_info[$count]["available_rows"] > 0) {
                // Found last rra set with values
                $last_rra_key = $count;
                break;
            }
        }
        if (isset($rra_info[0])) {
            $last = $rra_info[0]["firstValue_timestamp"];
            $first = $rra_info[$last_rra_key]["firstValue_timestamp"] + $rra_info[$last_rra_key]["recorded_time"] -
                $rra_info[$last_rra_key]["full_step"];

        } else {
            $first = 0;
            $last = 0;
        }

        return ["newest_timestamp" => $first, "oldest_timestamp" => $last];
    }

    /**
     * translate rrd data to usable format for d3 charts
     * @param array $data
     * @param boolean $applyInverse
     * @return array
     */
    private function translateD3($data = array(), $applyInverse = false, $field_units)
    {
        $d3_data = array();
        $from_timestamp = 0;
        $to_timestamp = 0;

        foreach ($data['archive'] as $row => $rowValues) {
            $timestamp = $rowValues['timestamp'] * 1000; // javascript works with milliseconds
            foreach ($data['columns'] as $key => $value) {
                $name = $value['name'];
                if ($value['type'] == "GAUGE") {
                    // return values as float
                    $value = $rowValues['condensed_values'][$key];
                } else {
                    // return values as int
                    if ((string)$rowValues['condensed_values'][$key] != "NaN") {
                        $value = (int)$rowValues['condensed_values'][$key];
                    } else {
                        $value = $rowValues['condensed_values'][$key];
                    }

                }
                if (!isset($d3_data[$key])) {
                    $d3_data[$key] = [];
                    $d3_data[$key]["area"] = true;
                    if (isset($field_units[$name])) {
                        $d3_data[$key]["key"] = $name . " " . $field_units[$name];
                    } else {
                        $d3_data[$key]["key"] = $name;
                    }
                    $d3_data[$key]["values"] = [];

                }

                if ($value == "NaN") {
                    // If first or the last NaN value in series then add a value of 0 for presentation purposes
                    $nan = false;
                    if (isset($data['archive'][$row - 1]['condensed_values'][$key]) &&
                        (string)$data['archive'][$row - 1]['condensed_values'][$key] != "NaN") {
                        // Translate NaN to 0 as d3chart can't render NaN - (first NaN item before value)
                        $value = 0;
                    } elseif (isset($data['archive'][$row + 1]['condensed_values'][$key]) &&
                        (string)$data['archive'][$row + 1]['condensed_values'][$key] != "NaN") {
                        $value = 0; // Translate NaN to 0 as d3chart can't render NaN - (last NaN item before value)
                    } else {
                        $nan = true; // suppress NaN item as we already drawn a line to 0
                    }
                } else {
                    $nan = false; // Not a NaN value, so add to list
                }
                if ($applyInverse == true) {
                    $check_value = $key / 2; // every odd row gets data inversed (* -1)
                    if ($check_value != (int)$check_value) {
                        $value = $value * -1;
                    }
                }
                if ($nan == false) {
                    if ($from_timestamp == 0 || $timestamp < $from_timestamp) {
                        $from_timestamp = $timestamp; // Actual from_timestamp after condensing and cleaning data
                    }
                    if ($to_timestamp == 0 || $timestamp > $to_timestamp) {
                        $to_timestamp = $timestamp; // Actual to_timestamp after condensing and cleaning data
                    }
                    array_push($d3_data[$key]["values"], [$timestamp, $value]);
                }
            }
        }


        // Sort value sets based on timestamp
        foreach ($d3_data as $key => $value) {
            usort($value["values"], array($this, "orderByTimestampASC"));
            $d3_data[$key]["values"] = $value["values"];
        }

        return [
            "stepSize" => $data['condensed_step'],
            "from_timestamp" => $from_timestamp,
            "to_timestamp" => $to_timestamp,
            "count" => count($d3_data[0]['values']),
            "data" => $d3_data
        ];
    }

    private function getCondensedArchive($xml = array(), $selection = array())
    {
        $key_counter = 0;
        $info = $this->getDataSetInfo($xml);
        $count_values = 0;
        $condensed_row_values = array();
        $condensed_archive = array();
        $condensed_step = 0;
        $skip_nan = false;
        $selected_archives = $selection["data"];

        foreach ($xml->rra as $key => $value) {
            $calculation_type = trim($value->cf);
            foreach ($value->database as $db_key => $db_value) {
                foreach ($selected_archives as $archKey => $archValue) {
                    if ($archValue['key'] == $key_counter) {
                        $rowCount = 0;
                        $condense_counter = 0;
                        $condense = $archValue['condense_by'];

                        foreach ($db_value as $rowKey => $rowValues) {
                            if ($rowCount >= $info[$key_counter]['firstValue_rowNumber']) {
                                $timestamp = $info[$key_counter]['first_timestamp'] +
                                    ($rowCount * $info[$key_counter]['step'] * $info[$key_counter]['pdp_per_row']);
                                if (($timestamp >= $selection["from"] && $timestamp <= $selection["to"] &&
                                        $archValue["type"] == "detail") || ($archValue["type"] == "overview" &&
                                        $timestamp <= $selection["from"]) || ($archValue["type"] == "overview" &&
                                        $timestamp >= $selection["to"])) {
                                    $condense_counter++;
                                    // Find smallest step in focus area = detail
                                    if ($archValue['type'] == "detail" && $selection["full_range"] == false) {
                                        // Set new calculated step size
                                        $condensed_step = ($info[$key_counter]['full_step'] * $condense);
                                    } else {
                                        if ($selection["full_range"] == true && $archValue['type'] == "overview") {
                                            $condensed_step = ($info[$key_counter]['full_step'] * $condense);
                                        }
                                    }
                                    $column_counter = 0;
                                    if (!isset($condensed_row_values[$count_values])) {
                                        $condensed_row_values[$count_values] = [];
                                    }

                                    foreach ($rowValues->v as $columnKey => $columnValue) {
                                        if (!isset($condensed_row_values[$count_values][$column_counter])) {
                                            $condensed_row_values[$count_values][$column_counter] = 0;
                                        }
                                        if (trim($columnValue) == "NaN") {
                                            // skip processing the rest of the values as this set has a NaN value
                                            $skip_nan = true;

                                            $condensed_row_values[$count_values][$column_counter] = "NaN";

                                        } elseif ($skip_nan == false) {
                                            if ($archValue["type"] == "overview") {
                                                // overwrite this values and skip averaging, looks better for overview
                                                $condensed_row_values[$count_values][$column_counter] =
                                                    ((float)$columnValue);
                                            } elseif ($calculation_type == "AVERAGE") {
                                                // For AVERAGE always add the values
                                                $condensed_row_values[$count_values][$column_counter] +=
                                                    (float)$columnValue;
                                            } elseif ($calculation_type == "MINIMUM" || $condense_counter == 1) {
                                                // For MINIMUM update value if smaller one found or first
                                                if ($condensed_row_values[$count_values][$column_counter] >
                                                    (float)$columnValue) {
                                                    $condensed_row_values[$count_values][$column_counter] =
                                                        (float)$columnValue;
                                                }
                                            } elseif ($calculation_type == "MAXIMUM" || $condense_counter == 1) {
                                                // For MAXIMUM update value if higher one found or first
                                                if ($condensed_row_values[$count_values][$column_counter] <
                                                    (float)$columnValue) {
                                                    $condensed_row_values[$count_values][$column_counter] =
                                                        (float)$columnValue;
                                                }
                                            }
                                        }

                                        $column_counter++;
                                    }

                                    if ($condense_counter == $condense) {
                                        foreach ($condensed_row_values[$count_values] as $crvKey => $crValue) {
                                            if ($condensed_row_values[$count_values][$crvKey] != "NaN" &&
                                                $calculation_type == "AVERAGE" && $archValue["type"] != "overview") {
                                                // For AVERAGE we need to calculate it,
                                                // dividing by the total number of values collected
                                                $condensed_row_values[$count_values][$crvKey] =
                                                    (float)$condensed_row_values[$count_values][$crvKey] / $condense;
                                            }
                                        }
                                        $skip_nan = false;
                                        if ($info[$key_counter]['available_rows'] > 0) {
                                            array_push($condensed_archive, [
                                                "timestamp" => $timestamp - ($info[$key_counter]['step'] *
                                                        $info[$key_counter]['pdp_per_row']),
                                                "condensed_values" => $condensed_row_values[$count_values]
                                            ]);
                                        }
                                        $count_values++;
                                        $condense_counter = 0;
                                    }
                                }
                            }
                            $rowCount++;

                        }
                    }

                }

            }

            $key_counter++;
        }

        // get value information to include in set
        $column_data = array();
        foreach ($xml->ds as $key => $value) {
            array_push($column_data, ["name" => trim($value->name), "type" => trim($value->type)]);
        }

        return ["condensed_step" => $condensed_step, "columns" => $column_data, "archive" => $condensed_archive];
    }

    /**
     * Custom Compare for usort
     * @param $a
     * @param $b
     * @return mixed
     */
    private function orderByTimestampASC($a, $b)
    {
        return $a[0] - $b[0];
    }
}
