<?php

/**
 *    Copyright (C) 2016-2017 Deciso B.V.
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

/**
 * Class FilterRule
 * @package OPNsense\Firewall
 */
class FilterRule
{
    private $rule = array();
    private $interfaceMapping = array();
    private $gatewayMapping = array();

    private $procorder = array(
        'disabled' => 'parseIsComment',
        'type' => 'parseType',
        'direction' => 'parseReplaceSimple,any:|:in',
        'log' => 'parseBool,log',
        'quick' => 'parseBool,quick',
        'interface' => 'parseInterface',
        'gateway' => 'parseRoute',
        'reply' =>  'parsePlain',
        'ipprotocol' => 'parsePlain',
        'protocol' => 'parseReplaceSimple,tcp/udp:{tcp udp},proto ',
        'from' => 'parsePlainCurly,from ',
        'from_port' => 'parsePlainCurly, port ',
        'os' => 'parsePlain, os {","}',
        'to' => 'parsePlainCurly,to ',
        'to_port' => 'parsePlainCurly, port ',
        'icmp-type' => 'parsePlain,icmp-type {,}',
        'icmp6-type' => 'parsePlain,icmp6-type {,}',
        'flags' => 'parsePlain, flags ',
        'state' => 'parseState',
        'set-prio' => 'parsePlain, set prio ',
        'prio' => 'parsePlain, prio ',
        'tag' => 'parsePlain, tag ',
        'tagged' => 'parsePlain, tagged ',
        'allowopts' => 'parseBool,allow-opts',
        'label' => 'parsePlain,label ",",63'
    );

    /**
     * output parsing
     * @param string $value field value
     * @return string
     */
    private function parseIsComment($value)
    {
        return !empty($value) ? "#" : "";
    }

    /**
     * parse plain data
     * @param string $value field value
     * @param string $prefix prefix when $value is provided
     * @param string $suffix suffix when $value is provided
     * @param int $maxsize maximum size, cut when longer
     * @return string
     */
    private function parsePlain($value, $prefix = "", $suffix = "", $maxsize = null)
    {
        if (!empty($maxsize) && strlen($value) > $maxsize) {
            $value = substr($value, 0, $maxsize);
        }
        return $value == null || $value === '' ? '' : $prefix . $value . $suffix . ' ';
    }

    /**
     * parse plain data
     * @param string $value field value
     * @param string $prefix prefix when $value is provided
     * @return string
     */
    private function parsePlainCurly($value, $prefix = "")
    {
        $suffix = "";
        if (strpos($value, '$') === false) {
            // don't wrap aliases in curly brackets
            $prefix = $prefix . "{";
            $suffix = "}";
        }
        return $value == null || $value === '' ? '' : $prefix . $value . $suffix . ' ';
    }

    /**
     * parse data, use replace map
     * @param string $value field value
     * @param string $map
     * @return string
     */
    private function parseReplaceSimple($value, $map, $prefix = "", $suffix = "")
    {
        $retval = $value;
        foreach (explode('|', $map) as $item) {
            $tmp = explode(':', $item);
            if ($tmp[0] == $value) {
                $retval = $tmp[1] . " ";
                break;
            }
        }
        if (!empty($retval)) {
            return $prefix . $retval . $suffix . " ";
        } else {
            return "";
        }
    }

    /**
     * parse type
     * @param string $value field value
     * @return string
     */
    private function parseType($value)
    {
        switch ($value) {
            case 'reject':
                $type = 'block return';
                break;
            default:
                $type = $value;
        }
        return empty($type) ? "pass " : $type . " ";
    }

    /**
     * parse interface (name to interface)
     * @param string $value field value
     * @return string
     */
    private function parseInterface($value)
    {
        if (empty($value)) {
            return "";
        } elseif (empty($this->interfaceMapping[$value]['if'])) {
            return "on ##{$value}## ";
        } else {
            return "on ". $this->interfaceMapping[$value]['if']." ";
        }
    }

    /**
     * parse gateway (route-to)
     * @param string $value field value
     * @return string
     */
    private function parseRoute($value)
    {
        if (!empty($this->gatewayMapping[$value]['logic'])) {
            return " " . $this->gatewayMapping[$value]['logic'] . " ";
        } else {
            return "";
        }
    }

    /**
     * parse boolean, return text from $valueTrue / $valueFalse
     * @param string $value field value
     * @return string
     */
    private function parseBool($value, $valueTrue, $valueFalse = "")
    {
        if (!empty($value)) {
            return !empty($valueTrue) ? $valueTrue . " " : "";
        } else {
            return !empty($valueFalse) ? $valueFalse . " " : "";
        }
    }

    /**
     * parse state settings
     * @param array $value state option
     * @return string
     */
    private function parseState($value)
    {
        $retval = "";
        if (!empty($value)) {
            $retval .= $value['type'] . " state ";
            if (count($value['options'])) {
                $retval .= "( " . implode(' ', $value['options']) .  " ) ";
            }
        }
        return $retval;
    }

    /**
     * convert source/destination address entries as used by the gui
     * @param array $rule rule
     */
    private function convertAddress(&$rule)
    {
        $fields = array();
        $fields['source'] = 'from';
        $fields['destination'] = 'to';
        $interfaces = $this->interfaceMapping;
        foreach ($fields as $tag => $target) {
            if (!empty($rule[$tag])) {
                if (isset($rule[$tag]['any'])) {
                    $rule[$target] = 'any';
                } elseif (!empty($rule[$tag]['network'])) {
                    $network_name = $rule[$tag]['network'];
                    $matches = "";
                    if ($network_name == '(self)') {
                        $rule[$target] = "(self)";
                    } elseif (preg_match("/^(wan|lan|opt[0-9]+)ip$/", $network_name, $matches)) {
                        if (!empty($interfaces[$matches[1]]['if'])) {
                            $rule[$target] = "({$interfaces["{$matches[1]}"]['if']})";
                        }
                    } else {
                        if (!empty($interfaces[$network_name]['if'])) {
                            $rule[$target] = "({$interfaces[$network_name]['if']}:network)";
                        }
                    }
                } elseif (!empty($rule[$tag]['address'])) {
                    if (Util::isIpAddress($rule[$tag]['address']) || Util::isSubnet($rule[$tag]['address']) ||
                      Util::isPort($rule[$tag]['address'])
                    ) {
                        $rule[$target] = $rule[$tag]['address'];
                    } elseif (Util::isAlias($rule[$tag]['address'])) {
                        $rule[$target] = '$'.$rule[$tag]['address'];
                    }
                }
                if (!empty($rule[$target]) && $rule[$target] != 'any' && isset($rule[$tag]['not'])) {
                    $rule[$target] = "!" . $rule[$target];
                }
                if (isset($rule['protocol']) && in_array(strtolower($rule['protocol']), array("tcp","udp","tcp/udp"))) {
                    $port = str_replace('-', ':', $rule[$tag]['port']);
                    if (Util::isPort($port)) {
                        $rule[$target."_port"] = $port;
                    } elseif (Util::isAlias($port)) {
                        $rule[$target."_port"] = '$'.$port;
                    }
                }
                if (!isset($rule[$target])) {
                    // couldn't convert address, disable rule
                    // dump all tag contents in target (from/to) for reference
                    $rule['disabled'] = true;
                    $rule[$target] = json_encode($rule[$tag]);
                }
            }
        }
    }

    /**
     * add reply-to tag when applicable
     * @param array $rule rule
     */
    private function convertReplyTo(&$rule)
    {
        if (!isset($rule['disablereplyto']) && $rule['direction'] != 'any') {
            $proto = $rule['ipprotocol'];
            if (!empty($this->interfaceMapping[$rule['interface']]['if']) && empty($rule['gateway'])) {
                $if = $this->interfaceMapping[$rule['interface']]['if'];
                switch ($proto) {
                    case "inet6":
                        if (!empty($this->interfaceMapping[$rule['interface']]['gatewayv6'])
                           && Util::isIpAddress($this->interfaceMapping[$rule['interface']]['gatewayv6'])) {
                            $gw = $this->interfaceMapping[$rule['interface']]['gatewayv6'];
                            $rule['reply'] = "reply-to ( {$if} {$gw} ) ";
                        }
                        break;
                    default:
                        if (!empty($this->interfaceMapping[$rule['interface']]['gateway'])
                           && Util::isIpAddress($this->interfaceMapping[$rule['interface']]['gateway'])) {
                            $gw = $this->interfaceMapping[$rule['interface']]['gateway'];
                            $rule['reply'] = "reply-to ( {$if} {$gw} ) ";
                        }
                        break;
                }
            }
        }
    }


    /**
     * preprocess internal rule data to detail level of actual ruleset
     * handles shortcuts, like inet46 and multiple interfaces
     * @return array
     */
    private function fetchActualRules()
    {
        $result = array();
        $interfaces = empty($this->rule['interface']) ? array(null) : explode(',', $this->rule['interface']);
        foreach ($interfaces as $interface) {
            if (isset($this->rule['ipprotocol']) && $this->rule['ipprotocol'] == 'inet46') {
                $ipprotos = array('inet', 'inet6');
            } elseif (isset($this->rule['ipprotocol'])) {
                $ipprotos = array($this->rule['ipprotocol']);
            } else {
                $ipprotos = array(null);
            }

            foreach ($ipprotos as $ipproto) {
                $tmp = $this->rule;
                $tmp['interface'] = $interface;
                $tmp['ipprotocol'] = $ipproto;
                $this->convertAddress($tmp);
                $this->convertReplyTo($tmp);
                $tmp['from'] = empty($tmp['from']) ? "any" : $tmp['from'];
                $tmp['to'] = empty($tmp['to']) ? "any" : $tmp['to'];
                // disable rule when interface not found
                if (!empty($interface) && empty($this->interfaceMapping[$interface]['if'])) {
                    $tmp['disabled'] = true;
                }
                // disable rules when gateway is down and skip_rules_gw_down is set
                if (!empty($tmp['skip_rules_gw_down']) && !empty($tmp['gateway']) &&
                  empty($this->gatewayMapping[$tmp['gateway']])) {
                    $tmp['disabled'] = true;
                }
                if (!isset($tmp['quick'])) {
                    // all rules are quick by default except floating
                    $tmp['quick'] = !isset($tmp['floating']) ? true : false;
                }
                // restructure flags
                if (isset($tmp['protocol']) && $tmp['protocol'] == "tcp") {
                    if (isset($tmp['tcpflags_any'])) {
                        $tmp['flags'] = "any";
                    } elseif (!empty($tmp['tcpflags2'])) {
                        $tmp['flags'] = "";
                        foreach (array('tcpflags1', 'tcpflags2') as $flagtag) {
                            $tmp['flags'] .= $flagtag == 'tcpflags2' ? "/" : "";
                            if (!empty($tmp[$flagtag])) {
                                foreach (explode(",", strtoupper($tmp[$flagtag])) as $flag1) {
                                    // CWR flag needs special treatment
                                    $tmp['flags'] .= $flag1[0] == "C" ? "W" : $flag1[0];
                                }
                            }
                        }
                    }
                }
                // restructure state settings for easier output parsing
                if (!empty($tmp['statetype']) && $tmp['type'] == 'pass') {
                    $tmp['state'] = array('type' => 'keep', 'options' => array());
                    switch ($tmp['statetype']) {
                        case 'none':
                            $tmp['state']['type'] = 'no';
                            break;
                        case 'sloppy state':
                        case 'sloppy':
                            $tmp['state']['type'] = 'keep';
                            $tmp['state']['options'][] = "sloppy ";
                            break;
                        default:
                            $tmp['state']['type'] = explode(' ', $tmp['statetype'])[0];
                    }
                    if (!empty($tmp['nopfsync'])) {
                        $tmp['state']['options'][] = "no-sync ";
                    }
                    foreach (array('max', 'max-src-nodes', 'max-src-conn', 'max-src-states') as $state_tag) {
                        if (!empty($tmp[$state_tag])) {
                            $tmp['state']['options'][] = $state_tag . " " . $tmp[$state_tag];
                        }
                    }
                    if (!empty($tmp['statetimeout'])) {
                        $tmp['state']['options'][] = "tcp.established " . $tmp['statetimeout'];
                    }
                    if (!empty($tmp['max-src-conn-rate']) && !empty($tmp['max-src-conn-rates'])) {
                        $tmp['state']['options'][] = "max-src-conn-rate " . $tmp['max-src-conn-rate'] . " " .
                                              "/" . $tmp['max-src-conn-rates'] . ", overload <virusprot> flush global ";
                    }
                }
                // icmp-type switch (ipv4/ipv6)
                if ($tmp['protocol'] == "icmp" && !empty($tmp['icmptype'])) {
                    if ($ipproto == 'inet') {
                        $tmp['icmp-type'] = $tmp['icmptype'];
                    } elseif ($ipproto == 'inet6') {
                        $tmp['icmp6-type'] = $tmp['icmptype'];
                    }
                }
                // icmpv6
                if ($ipproto == 'inet6' && !empty($tmp['protocol']) && $tmp['protocol'] == "icmp") {
                    $tmp['protocol'] = 'ipv6-icmp';
                }
                // set prio
                if (isset($tmp['set-prio']) && $tmp['set-prio'] !== ""
                  && isset($tmp['set-prio-low']) && $tmp['set-prio-low'] !== "" ) {
                    $tmp['set-prio'] = "({$tmp['set-prio']}, {$tmp['set-prio-low']})";
                }
                $result[] = $tmp;
            }
        }
        return $result;
    }

    /**
     * init FilterRule
     * @param array $interfaceMapping internal interface mapping
     * @param array $gatewayMapping internal gateway mapping
     * @param array $conf rule configuration
     */
    public function __construct(&$interfaceMapping, &$gatewayMapping, $conf)
    {
        $this->interfaceMapping = $interfaceMapping;
        $this->gatewayMapping = $gatewayMapping;
        $this->rule = $conf;
    }

    /**
     * output rule as string
     * @return string ruleset
     */
    public function __toString()
    {
        $ruleTxt = '';
        foreach ($this->fetchActualRules() as $rule) {
            foreach ($this->procorder as $tag => $handle) {
                $tmp = explode(',', $handle);
                $method = $tmp[0];
                $args = array(isset($rule[$tag]) ? $rule[$tag] : null);
                if (count($tmp) > 1) {
                    array_shift($tmp);
                    $args = array_merge($args, $tmp);
                }
                $ruleTxt .= call_user_func_array(array($this,$method), $args);
            }
            $ruleTxt .= "\n";
        }
        return $ruleTxt;
    }
}
