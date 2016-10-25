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

/**
 * Class FilterRule
 * @package OPNsense\Firewall
 */
class FilterRule
{
    private $rule = array();
    private $interfaceMapping = array();

    private $procorder = array(
        'disabled' => 'parseIsComment',
        'type' => 'parseType',
        'ipprotocol' => 'parsePlain',
        'interface' => 'parseInterface'
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
     * @return string
     */
    private function parsePlain($value)
    {
        return empty($value) ? "" : $value . " ";
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
        if (empty($this->interfaceMapping[$value]['if'])) {
            return "##{$value}##";
        } else {
            return $this->interfaceMapping[$value]['if']." ";
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
                if (empty($this->interfaceMapping[$interface]['if'])) {
                    // disable rule when interface not found
                    $tmp['disabled'] = true;
                }
                $result[] = $tmp;
            }
        }
        return $result;
    }

    /**
     * init FilterRule
     * @param array $interfaceMapping internal interface mapping
     * @param array $conf rule configuration
     */
    public function __construct(&$interfaceMapping, $conf)
    {
        $this->interfaceMapping = $interfaceMapping;
        $this->rule = $conf;
    }

    /**
     * output rule as string
     * @return string ruleset
     */
    public function  __toString()
    {
        $ruleTxt = '';
        foreach ($this->fetchActualRules() as $rule) {
            foreach ($this->procorder as $tag => $handle) {
                $ruleTxt .= $this->$handle(isset($rule[$tag]) ? $rule[$tag] : null);
            }
            $ruleTxt .= "\n";
        }
        return $ruleTxt;
    }
}
