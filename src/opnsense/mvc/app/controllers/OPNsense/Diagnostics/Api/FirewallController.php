<?php

/*
 * Copyright (C) 2017-2024 Deciso B.V.
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
use OPNsense\Core\SanitizeFilter;
use OPNsense\Firewall\Util;

/**
 * Class FirewallController
 * @package OPNsense\Diagnostics\Api
 */
class FirewallController extends ApiControllerBase
{
    /**
     * retrieve firewall log
     * @return array
     */
    public function logAction()
    {
        if ($this->request->isGet()) {
            $digest = empty($this->request->get('digest')) ? "" : $this->request->get('digest');
            $limit = empty($this->request->get('limit')) ? 1000 : $this->request->get('limit');
            $backend = new Backend();
            $response = $backend->configdpRun("filter read log", array($limit, $digest));
            $logoutput = json_decode($response, true);
            return $logoutput;
        } else {
            return null;
        }
    }

    public function streamLogAction()
    {
        return $this->configdStream(
            'filter stream log',
            [],
            [
                'Content-Type: text/event-stream',
                'Cache-Control: no-cache'
            ],
            60
        );
    }

    /**
     * retrieve firewall log filter choices
     * @return array
     */
    public function logFiltersAction()
    {
        $config = Config::getInstance()->object();
        $interfaces = [];
        if ($config->interfaces->count() > 0) {
            foreach ($config->interfaces->children() as $key => $node) {
                // XXX: Omit group types since they don't link to actual interfaces.
                if (isset($node->type) && (string)$node->type == 'group') {
                    continue;
                } elseif ((string)$node->if == 'openvpn') {
                    continue;
                }
                $interfaces[] = !empty((string)$node->descr) ? (string)$node->descr : $key;
            }
        }
        sort($interfaces, SORT_NATURAL | SORT_FLAG_CASE);
        return [
            'action' => ['pass', 'block', 'rdr', 'nat', 'binat'],
            'interface_name' => $interfaces,
            'dir' => ['in', 'out'],
        ];
    }

    /**
     * retrieve firewall stats
     * @return array
     */
    public function statsAction()
    {
        if ($this->request->isGet()) {
            $limit = empty($this->request->get('limit')) ? 5000 : $this->request->get('limit');
            $group_by = empty($this->request->get('group_by')) ? "interface" : $this->request->get('group_by');
            $records = json_decode((new Backend())->configdpRun("filter read log", array($limit)), true);
            $response = array();
            if (!empty($records)) {
                $tmp_stats = array();
                foreach ($records as $record) {
                    if (isset($record[$group_by])) {
                        if (!isset($tmp_stats[$record[$group_by]])) {
                            $tmp_stats[$record[$group_by]] = 0;
                        }
                        $tmp_stats[$record[$group_by]]++;
                    }
                }
                arsort($tmp_stats);
                $label_map = array();
                switch ($group_by) {
                    case 'interface':
                        $label_map["lo0"] = gettext("loopback");
                        if (Config::getInstance()->object()->interfaces->count() > 0) {
                            foreach (Config::getInstance()->object()->interfaces->children() as $k => $n) {
                                $label_map[(string)$n->if] = !empty((string)$n->descr) ? (string)$n->descr : $k;
                            }
                        }
                        break;
                    case 'proto':
                      // proto
                        break;
                }
                $recno = $top_cnt = 0;
                foreach ($tmp_stats as $key => $value) {
                    // top 10 + other
                    if ($recno < 10) {
                        $response[] = [
                            "label" => !empty($label_map[$key]) ? $label_map[$key] : $key,
                            "value" => $value
                        ];
                        $top_cnt += $value;
                    } else {
                        $response[] = ["label" => gettext("other"), "value" => count($records) - $top_cnt];
                        break;
                    }
                    $recno++;
                }
            }
            return $response;
        } else {
            return null;
        }
    }

    /**
     * query pf states
     */
    public function queryStatesAction()
    {
        if ($this->request->isPost()) {
            $ifnames = [];
            $ifnames["lo0"] = gettext("loopback");
            if (Config::getInstance()->object()->interfaces->count() > 0) {
                foreach (Config::getInstance()->object()->interfaces->children() as $k => $n) {
                    $ifnames[(string)$n->if] = !empty((string)$n->descr) ? (string)$n->descr : $k;
                }
            }

            $filter = new SanitizeFilter();
            $searchPhrase = '';
            $ruleId = '';
            $sortBy = '';
            $itemsPerPage = $this->request->getPost('rowCount', 'int', 9999);
            $currentPage = $this->request->getPost('current', 'int', 1);

            if ($this->request->getPost('ruleid', 'string', '') != '') {
                $ruleId = $filter->sanitize($this->request->getPost('ruleid'), 'query');
            }

            if ($this->request->getPost('searchPhrase', 'string', '') != '') {
                $searchPhrase = $filter->sanitize($this->request->getPost('searchPhrase'), 'query');
            }
            if (
                $this->request->has('sort') &&
                is_array($this->request->getPost("sort")) &&
                !empty($this->request->getPost("sort"))
            ) {
                $tmp = array_keys($this->request->getPost("sort"));
                $sortBy = $tmp[0] . " " . $this->request->getPost("sort")[$tmp[0]];
            }

            $response = (new Backend())->configdpRun('filter list states', [$searchPhrase, $itemsPerPage,
                ($currentPage - 1) * $itemsPerPage, $ruleId, $sortBy]);
            $response = json_decode($response, true);
            if ($response != null) {
                foreach ($response['details'] as &$row) {
                    $isipv4 = strpos($row['src_addr'], ':') === false;
                    $row['interface'] = !empty($ifnames[$row['iface']]) ? $ifnames[$row['iface']] : $row['iface'];
                }
                return [
                    'rows' => $response['details'],
                    'rowCount' => count($response['details']),
                    'total' => $response['total_entries'],
                    'current' => (int)$currentPage
                ];
            }
        }
        return [
            'rows' => [],
            'rowCount' => 0,
            'total' => 0,
            'current' => 0
        ];
    }

    /**
     * query pftop
     */
    public function queryPfTopAction()
    {
        if ($this->request->isPost()) {
            $pftop = json_decode((new Backend())->configdpRun('filter diag top') ?? '', true) ?? [];

            $clauses = [];
            $networks = [];
            foreach (preg_split('/\s+/', (string)$this->request->getPost('searchPhrase', null, '')) as $item) {
                if (empty($item)) {
                    continue;
                } elseif (Util::isSubnet($item)) {
                    $networks[] = $item;
                } elseif (Util::isIpAddress($item)) {
                    $networks[] = $item . "/" . (strpos($item, ':') ? '128' : '32');
                } else {
                    $clauses[] = $item;
                }
            }

            $ruleid = $this->request->getPost('ruleid', 'string', '');
            $labels = $pftop['metadata']['labels'];
            $filter_funct = function (&$row) use ($networks, $labels, $ruleid) {
                /* update record */
                if (isset($labels[$row['rule']])) {
                    $row['label'] = $labels[$row['rule']]['rid'];
                    $row['descr'] = $labels[$row['rule']]['descr'];
                }

                if (!empty($ruleid) && trim($row['label']) != $ruleid) {
                    return false;
                }
                /* filter using network clauses*/
                if (empty($networks)) {
                    return true;
                }
                foreach (['dst_addr', 'src_addr', 'gw_addr'] as $addr) {
                    foreach ($networks as $net) {
                        if (Util::isIPInCIDR($row[$addr] ?? '', $net)) {
                            return true;
                        }
                    }
                }
                return false;
            };

            return $this->searchRecordsetBase(
                $pftop['details'],
                null,
                null,
                $filter_funct,
                SORT_NATURAL | SORT_FLAG_CASE,
                $clauses
            );
        }
    }

    /**
     * delete / drop a specific state by state+creator id
     */
    public function delStateAction($stateid, $creatorid)
    {
        if ($this->request->isPost()) {
            $filter = new SanitizeFilter();
            $response = (new Backend())->configdpRun("filter kill state", [
                $filter->sanitize($stateid, "hexval"),
                $filter->sanitize($creatorid, "hexval")
            ]);
            return [
                'result' => $response
            ];
        }
        return ['result' => ""];
    }

    /**
     * drop pf states by filter and/or rule id
     */
    public function killStatesAction()
    {
        if ($this->request->isPost()) {
            $filter = new SanitizeFilter();
            $ruleid = null;
            $filterString = null;
            if (!empty($this->request->getPost('filter'))) {
                $filterString = $filter->sanitize($this->request->getPost('filter'), 'query');
            }
            if (!empty($this->request->getPost('ruleid'))) {
                $ruleid = $filter->sanitize($this->request->getPost('ruleid'), 'hexval');
            }
            if ($filterString != null || $ruleid != null) {
                $response = (new Backend())->configdpRun("filter kill states", [$filterString, $ruleid]);
                $response = json_decode($response, true);
                if ($response != null) {
                    return ["result" => "ok", "dropped_states" => $response['dropped_states']];
                }
            }
        }
        return ["result" => "failed"];
    }

    /**
     * return rule'ids and descriptions from running config
     */
    public function listRuleIdsAction()
    {
        if ($this->request->isGet()) {
            $response = json_decode((new Backend())->configdpRun("filter list rule_ids"), true);
            if ($response != null) {
                return ["items" => $response];
            }
        }
        return ["items" => []];
    }

    /**
     * flush all pf states
     */
    public function flushStatesAction()
    {
        if ($this->request->isPost()) {
            (new Backend())->configdRun("filter flush states");
            return ["result" => "ok"];
        }
        return ["result" => "failed"];
    }

    /**
     * flush pf source tracking
     */
    public function flushSourcesAction()
    {
        if ($this->request->isPost()) {
            (new Backend())->configdRun("filter flush sources");
            return ["result" => "ok"];
        }
        return ["result" => "failed"];
    }

    /**
     * retrieve various pf statistics
     * @return mixed
     */
    public function pfStatisticsAction($section = null)
    {
        return json_decode((new Backend())->configdpRun('filter diag info', [$section]), true);
    }

    /**
     * retrieve pf state amount and states limit
     */
    public function pfStatesAction()
    {
        $response = trim((new Backend())->configdRun("filter diag state_size"));
        if (!empty($response)) {
            $response = explode(PHP_EOL, $response);
            return [
                'current' => explode(' ', $response[0])[1],
                'limit' => explode(' ', $response[1])[1]
            ];
        }
        return ["result" => "failed"];
    }
}
