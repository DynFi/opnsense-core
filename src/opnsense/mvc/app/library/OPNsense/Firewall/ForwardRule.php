<?php

/**
 *    Copyright (C) 2017 Deciso B.V.
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
 * Class ForwardRule, (pf rdr type rule, optionally combined with nat rules for reflection)
 * @package OPNsense\Firewall
 */
class ForwardRule extends Rule
{
    private $procorder = array(
        'rdr' => array(
            'disabled' => 'parseIsComment',
            'nordr' => 'parseBool,no rdr,rdr',
            'pass' => 'parseBool,pass ',
            'interface' => 'parseInterface',
            'ipprotocol' => 'parsePlain',
            'protocol' => 'parseReplaceSimple,tcp/udp:{tcp udp},proto ',
            'from' => 'parsePlainCurly,from ',
            'from_port' => 'parsePlainCurly, port ',
            'to' => 'parsePlainCurly,to ',
            'to_port' => 'parsePlainCurly, port ',
            'tag' => 'parsePlain, tag ',
            'tagged' => 'parsePlain, tagged ',
            'target' => 'parsePlain, -> ',
            'localport' => 'parsePlain, port ',
            'poolopts' => 'parsePlain',
            'descr' => 'parseComment'
        ),
        'rdr_nat' => array(
            'disabled' => 'parseIsComment',
            'nat' => 'parseStaticText,nat ',
            'interface' => 'parseInterface',
            'ipprotocol' => 'parsePlain',
            'protocol' => 'parseReplaceSimple,tcp/udp:{tcp udp},proto ',
            'interface.from' => 'parseInterface, from ,:network',
            'target.to' => 'parsePlainCurly,to ',
            'localport' => 'parsePlainCurly,port ',
            'interface.to' => 'parseInterface, -> ',
            'staticnatport' => 'parseBool,  static-port , port 1024:65535 ',
            'descr' => 'parseComment'
        )
    );

    /**
     * output parsing
     * @param string $value field value
     * @return string
     */
    protected function parseIsComment($value)
    {
        return !empty($value) ? "#" : "";
    }

    /**
     * parse comment
     * @param string $value field value
     * @return string
     */
    protected function parseComment($value)
    {
        return !empty($value) ? "# " . $value : "";
    }

    /**
     * search interfaces without a gateway other then the one provided
     * @param $interface
     * @return list of interfaces
     */
    private function reflectionInterfaces($interface)
    {
        $result = array();
        foreach ($this->interfaceMapping as $intfk => $intf) {
            if (empty($intf['gateway']) && empty($intf['gatewayv6']) && $interface != $intfk
              && !in_array($intf['if'], $result) && $intfk != 'loopback') {
                $result[] = $intfk;
            }
        }
        return $result;
    }

    /**
     * preprocess internal rule data to detail level of actual ruleset
     * handles shortcuts, like inet46 and multiple interfaces
     * @return array
     */
    private function parseRdrRules()
    {
        foreach ($this->reader() as $tmp) {
            $tmp['rule_types'] = array("rdr");
            $tmp['nordr'] = !empty($tmp['nordr']);
            if (!empty($tmp['associated-rule-id']) && $tmp['associated-rule-id'] == "pass") {
                $tmp['pass'] = empty($tmp['nordr']);
            }
            // target address, when invalid, disable rule
            if (!empty($tmp['target'])) {
                if (Util::isAlias($tmp['target'])) {
                    $tmp['target'] = "\${$tmp['target']}";
                } elseif (!Util::isIpAddress($tmp['target']) && !Util::isSubnet($tmp['target'])) {
                    $tmp['disabled'] = true;
                }
            }
            // parse our local port
            if (!empty($tmp['local-port']) && !empty($tmp['protocol'])
                  && in_array($tmp['protocol'], array('tcp/udp', 'udp', 'tcp'))) {
                if (Util::isAlias($tmp['local-port'])) {
                    // We will keep this for backwards compatibility, although the alias use is very confusing.
                    // Because the target can only be one address or range, we will just use the first one found
                    // in the alias.... confusing.
                    $tmp_port = Util::getPortAlias($tmp['local-port']);
                    if (!empty($tmp_port)) {
                        $tmp['localport'] = $tmp_port[0];
                    }
                } elseif (Util::isPort($tmp['local-port'])) {
                    $tmp['localport'] = $tmp['local-port'];
                } else {
                    $tmp['disabled'] = true;
                }
            }

            // When reflection is enabled our ruleset should cover all
            $interflist = array($tmp['interface']);
            if (!$tmp['disabled'] && !$tmp['nordr'] && in_array($tmp['natreflection'], array("purenat", "enable"))) {
                $interflist = array_merge($interflist, $this->reflectionInterfaces($tmp['interface']));
            }
            foreach ($interflist as $interf) {
                $rule = $tmp;
                // automatically generate nat rule when enablenatreflectionhelper is set
                if (!$rule['disabled'] && empty($rule['nordr']) && !empty($rule['enablenatreflectionhelper'])) {
                    // Only add nat rules when the selected interface has an address configured
                    if (!empty($this->interfaceMapping[$interf])) {
                        if (($this->isIpV4($rule) && !empty($this->interfaceMapping[$interf]['ifconfig']['ipv4'])) ||
                            (!$this->isIpV4($rule) && !empty($this->interfaceMapping[$interf]['ifconfig']['ipv6']))
                        ) {
                            $rule['rule_types'][] = "rdr_nat";
                            $rule['staticnatport'] = !empty($rule['staticnatport']);
                        }
                    }
                }
                $rule['interface'] = $interf;
                yield $rule;
            }
        }
    }

    /**
     * output rule as string
     * @return string ruleset
     */
    public function __toString()
    {
        $ruleTxt = '';
        foreach ($this->parseRdrRules() as $rule) {
            foreach ($rule['rule_types'] as $rule_type) {
                $ruleTxt .= $this->ruleToText($this->procorder[$rule_type], $rule). "\n";
            }
        }
        return $ruleTxt;
    }
}
