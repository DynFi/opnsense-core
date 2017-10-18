<?php

/**
 *    Copyright (C) 2016 Deciso B.V.
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
namespace OPNsense\Firewall;

use \OPNsense\Core\Config;

/**
 * Class Plugin
 * @package OPNsense\Firewall
 */
class Plugin
{
    private $anchors = array();
    private $filterRules = array();
    private $interfaceMapping = array();
    private $gatewayMapping = array();
    private $systemDefaults = array();
    private $tables = array();

    /**
     * init firewall plugin component
     */
    public function __construct()
    {
        if (!empty(Config::getInstance()->object()->system->disablereplyto)) {
            $this->systemDefaults['disablereplyto'] = true;
        }
        if (!empty(Config::getInstance()->object()->system->skip_rules_gw_down)) {
            $this->systemDefaults['skip_rules_gw_down'] = true;
        }
    }

    /**
     * set interface mapping to use
     * @param array $mapping named array
     */
    public function setInterfaceMapping(&$mapping)
    {
        $this->interfaceMapping = array();
        $this->interfaceMapping['loopback'] = array('if' => 'lo0', 'descr' => 'loopback');
        $this->interfaceMapping = array_merge($this->interfaceMapping, $mapping);
    }

    /**
     * set defined gateways (route-to)
     * @param array $gateways named array
     */
    public function setGateways($gateways)
    {
        if (is_array($gateways)) {
            foreach ($gateways as $key => $gw) {
                if (Util::isIpAddress($gw['gateway']) && !empty($gw['interface'])) {
                    $this->gatewayMapping[$key] = array("logic" => "route-to ( {$gw['interface']} {$gw['gateway']} )",
                                                        "interface" => $gw['interface'],
                                                        "gateway" => $gw['gateway'],
                                                        "proto" => strstr($gw['gateway'], ':') ? "inet6" : "inet",
                                                        "type" => "gateway");
                }
            }
        }
    }

    /**
     * set defined gateway groups (route-to)
     * @param array $groups named array
     */
    public function setGatewayGroups($groups)
    {
        if (is_array($groups)) {
            foreach ($groups as $key => $gwgr) {
                $routeto = array();
                $proto = 'inet';
                foreach ($gwgr as $gw) {
                    if (Util::isIpAddress($gw['gwip']) && !empty($gw['int'])) {
                        $gwweight = empty($gw['weight']) ? 1 : $gw['weight'];
                        $routeto[] = str_repeat("( {$gw['int']} {$gw['gwip']} )", $gwweight);
                        if (strstr($gw['gwip'], ':')) {
                            $proto = 'inet6';
                        }
                    }
                }
                if (count($routeto) > 0) {
                    $routetologic = "route-to {".implode(' ', $routeto)."}";
                    if (count($routeto) > 1) {
                        $routetologic .= " round-robin ";
                    }
                    if (!empty(Config::getInstance()->object()->system->lb_use_sticky)) {
                        $routetologic .= " sticky-address ";
                    }
                    $this->gatewayMapping[$key] = array("logic" => $routetologic,
                                                        "proto" => $proto,
                                                        "type" => "group");
                }
            }
        }
    }

    /**
     * fetch gateway (names) for provided interface, would return both ipv4/ipv6
     * @param string $intf interface (e.g. em0, igb0,...)
     */
    public function getInterfaceGateways($intf)
    {
        $result = array();
        $protos_found = array();
        foreach ($this->gatewayMapping as $key => $gw) {
            if ($gw['type'] == 'gateway' && $gw['interface'] == $intf) {
                if (!in_array($gw['proto'], $protos_found)) {
                    $result[] = $key;
                    $protos_found[] = $gw['proto'];
                }
            }
        }
        return $result;
    }

    /**
     *  Fetch gateway
     *  @param string $gw gateway name
     */
    public function getGateway($gw)
    {
        return $this->gatewayMapping[$gw];
    }

    /**
     * @return array
     */
    public function getInterfaceMapping()
    {
        return $this->interfaceMapping;
    }

    /**
     * register anchor
     * @param string $name anchor name
     * @param string $type anchor type (fw for filter, other options are nat,rdr,binat)
     * @param string $priority sort order from low to high
     * @param string $placement placement head,tail
     * @return null
     */
    public function registerAnchor($name, $type = "fw", $priority = 0, $placement = "tail", $quick = false)
    {
        $anchorKey = sprintf("%s.%s.%08d.%08d", $type, $placement, $priority, count($this->anchors));
        $this->anchors[$anchorKey] = array('name' => $name, 'quick' => $quick);
        ksort($this->anchors);
    }

    /**
     * fetch anchors as text (pf ruleset part)
     * @param string $types anchor types (fw for filter, other options are nat,rdr,binat. comma seperated)
     * @param string $placement placement head,tail
     * @return string
     */
    public function anchorToText($types = "fw", $placement = "tail")
    {
        $result = "";
        foreach (explode(',', $types) as $type) {
            foreach ($this->anchors as $anchorKey => $anchor) {
                if (strpos($anchorKey, "{$type}.{$placement}") === 0) {
                    $result .= $type == "fw" ? "" : "{$type}-";
                    $result .= "anchor \"{$anchor['name']}\"";
                    if ($anchor['quick']) {
                        $result .= " quick";
                    }
                    $result .= "\n";
                }
            }
        }
        return $result;
    }

    /**
     * register a filter rule
     * @param int $prio priority
     * @param array $conf configuration
     * @param array $defaults merge these defaults when provided
     */
    public function registerFilterRule($prio, $conf, $defaults = null)
    {
        if (!empty($this->systemDefaults)) {
            $conf = array_merge($this->systemDefaults, $conf);
        }
        if ($defaults != null) {
            $conf = array_merge($defaults, $conf);
        }
        $rule = new FilterRule($this->interfaceMapping, $this->gatewayMapping, $conf);
        if (empty($this->filterRules[$prio])) {
            $this->filterRules[$prio] = array();
        }
        $this->filterRules[$prio][] = $rule;
    }

    /**
     * filter rules to text
     * @return string
     */
    public function outputFilterRules()
    {
        $output = "";
        ksort($this->filterRules);
        foreach ($this->filterRules as $prio => $ruleset) {
            foreach ($ruleset as $rule) {
                $output .= (string)$rule;
            }
        }
        return $output;
    }

    /**
     * register a pf table
     * @param string $name table name
     * @param boolean $persist persistent
     * @param string $file get table from file
     */
    public function registerTable($name, $persist = false, $file = null)
    {
        $this->tables[] = array('name' => $name, 'persist' => $persist, 'file' => $file);
    }

    /**
     * fetch tables as text (pf tables part)
     * @return string
     */
    public function tablesToText()
    {
        $result = "";
        foreach ($this->tables as $table) {
            $result .= "table <{$table['name']}>";
            if ($table['persist']) {
                $result .= " persist";
            }
            if (!empty($table['file'])) {
                $result .= " file \"{$table['file']}\"";
            }
            $result .= "\n";
        }
        return $result;
    }
}
