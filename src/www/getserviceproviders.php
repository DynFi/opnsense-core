<?php

/*
    Copyright (C) 2014-2015 Deciso B.V.
    Copyright (C) 2010 Vinicius Coque <vinicius.coque@bluepex.com>
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
       this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
*/

require_once("guiconfig.inc");
require_once("services.inc");
require_once("system.inc");

$serviceproviders_xml = "/usr/local/opnsense/contrib/mobile-broadband-provider-info/serviceproviders.xml";
$serviceproviders_contents = file_get_contents($serviceproviders_xml);
$serviceproviders_attr = xml2array($serviceproviders_contents,1,"attr");
$serviceproviders = &$serviceproviders_attr['serviceproviders']['country'];

function get_country_providers($country)
{
    global $serviceproviders;
    foreach($serviceproviders as $sp) {
        if ($sp['attr']['code'] == strtolower($country)) {
            return is_array($sp['provider'][0]) ? $sp['provider'] : array($sp['provider']);
        }
    }
    return array();
}

function country_list()
{
    global $serviceproviders;
    $country_list = get_country_codes();
    foreach($serviceproviders as $sp) {
        foreach($country_list as $code => $country) {
            if (strtoupper($sp['attr']['code']) == $code) {
                echo $country . ":" . $code . "\n";
            }
        }
    }
}

function providers_list($country)
{
    $serviceproviders = get_country_providers($country);
    foreach($serviceproviders as $sp) {
        echo $sp['name']['value'] . "\n";
    }
}

function provider_plan_data($country,$provider,$connection) {
    header("Content-type: application/xml;");
    echo "<?xml version=\"1.0\" ?>\n";
    echo "<connection>\n";
    $serviceproviders = get_country_providers($country);
    foreach($serviceproviders as $sp) {
        if (strtolower($sp['name']['value']) == strtolower($provider)) {
            if (strtoupper($connection) == "CDMA") {
                $conndata = $sp['cdma'];
            } else {
                if (!is_array($sp['gsm']['apn'][0])) {
                    $conndata = $sp['gsm']['apn'];
                } else {
                    foreach($sp['gsm']['apn'] as $apn) {
                        if ($apn['attr']['value'] == $connection) {
                            $conndata = $apn;
                            break;
                        }
                    }
                }
            }
            if (is_array($conndata)) {
                echo "<apn>" . $connection . "</apn>\n";
                echo "<username>" . $conndata['username']['value'] . "</username>\n";
                echo "<password>" . $conndata['password']['value'] . "</password>\n";

                $dns_arr = is_array($conndata['dns'][0]) ? $conndata['dns'] : array( $conndata['dns'] );
                foreach($dns_arr as $dns) {
                    echo '<dns>' . $dns['value'] . "</dns>\n";
                }
            }
            break;
        }
    }
    echo "</connection>";
}

function provider_plans_list($country,$provider) {
    $serviceproviders = get_country_providers($country);
    foreach($serviceproviders as $sp) {
        if (strtolower($sp['name']['value']) == strtolower($provider)) {
            if (array_key_exists('gsm',$sp)) {
                if (array_key_exists('attr',$sp['gsm']['apn'])) {
                    $name = ($sp['gsm']['apn']['name'] ? $sp['gsm']['apn']['name'] : $sp['name']['value']);
                    echo $name . ":" . $sp['gsm']['apn']['attr']['value'];
                } else {
                    foreach($sp['gsm']['apn'] as $apn_info) {
                        $name = ($apn_info['name']['value'] ? $apn_info['name']['value'] : $apn_info['gsm']['apn']['name']);
                        echo $name . ":" . $apn_info['attr']['value'] . "\n";
                    }
                }
            }
            if (array_key_exists('cdma',$sp)) {
                $name = $sp['cdma']['name']['value'] ? $sp['cdma']['name']['value']:$sp['name']['value'];
                echo $name . ":" . "CDMA";
            }
        }
    }
}

if (isset($_REQUEST['country']) && !isset($_REQUEST['provider'])) {
    providers_list($_REQUEST['country']);
} elseif (isset($_REQUEST['country']) && isset($_REQUEST['provider'])) {
    if (isset($_REQUEST['plan'])) {
        provider_plan_data($_REQUEST['country'],$_REQUEST['provider'],$_REQUEST['plan']);
    } else {
        provider_plans_list($_REQUEST['country'],$_REQUEST['provider']);
    }
} else {
    country_list();
}
?>
