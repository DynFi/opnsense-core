<?php

/**
 *    Copyright (C) 2023 DynFi
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

namespace OPNsense\Suricata\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\Base\UserException;
use OPNsense\Core\Backend;
use OPNsense\Core\Config;

/**
 * @package OPNsense\Suricata
 */
class BlocksController extends ApiControllerBase
{

    public function searchItemAction()
    {
        $result = array();

        require_once("plugins.inc.d/suricata.inc");

        $suricatalogdir = SURICATALOGDIR;
        $suricatadir = SURICATADIR;

        $blocked_ips_array = suricata_get_blocked_ips();
        if (!empty($blocked_ips_array)) {
            foreach ($blocked_ips_array as &$ip) {
                $ip = inet_pton($ip);
            }

            $tmpblocked = array_flip($blocked_ips_array);
            $src_ip_list = array();

            foreach (glob("{$suricatalogdir}*/block.log*") as $alertfile) {
                $fd = fopen($alertfile, "r");
                if ($fd) {

                    $buf = "";
                    while (($buf = fgets($fd)) !== FALSE) {
                        $fields = array();
                        $tmp = array();

                        // Field 0 is the event timestamp
                        $fields['time'] = substr($buf, 0, strpos($buf, '  '));

                        // Field 1 is the action
                        if (strpos($buf, '[') !== FALSE && strpos($buf, ']') !== FALSE)
                            $fields['action'] = substr($buf, strpos($buf, '[') + 1, strpos($buf, ']') - strpos($buf, '[') - 1);
                        else
                            $fields['action'] = null;

                        preg_match('/\[\*{2}\]\s\[((\d+):(\d+):(\d+))\]\s(.*)\[\*{2}\]\s\[Classification:\s(.*)\]\s\[Priority:\s(\d+)\]\s/', $buf, $tmp);
                        $fields['gid'] = trim($tmp[2]);
                        $fields['sid'] = trim($tmp[3]);
                        $fields['rev'] = trim($tmp[4]);
                        $fields['msg'] = trim($tmp[5]);
                        $fields['class'] = trim($tmp[6]);
                        $fields['priority'] = trim($tmp[7]);

                        if (preg_match('/\{(.*)\}\s(.*)/', $buf, $tmp)) {
                            $fields['proto'] = trim($tmp[1]);
                            $fields['ip'] = trim(substr($tmp[2], 0, strrpos($tmp[2], ':')));
                            if (is_ipaddrv6($fields['ip']))
                                $fields['ip'] = inet_ntop(inet_pton($fields['ip']));
                            $fields['port'] = trim(substr($tmp[2], strrpos($tmp[2], ':') + 1));
                        }

                        if (empty($fields['ip']))
                            continue;
                        $fields['ip'] = inet_pton($fields['ip']);
                        if (isset($tmpblocked[$fields['ip']])) {
                            if (!is_array($src_ip_list[$fields['ip']]))
                                $src_ip_list[$fields['ip']] = array();
                            $src_ip_list[$fields['ip']][$fields['msg']] = "{$fields['msg']} - " . substr($fields['time'], 0, -7);
                        }
                    }
                    fclose($fd);
                }
            }

            foreach($blocked_ips_array as $blocked_ip) {
                if (is_ipaddr($blocked_ip) && !isset($src_ip_list[$blocked_ip])) {
                    $src_ip_list[$blocked_ip] = array("N\A\n");
                }
            }

            $counter = 0;
            foreach($src_ip_list as $blocked_ip => $blocked_msg) {
                $blocked_desc = implode("<br/>", $blocked_msg);
                if($counter > $bnentries)
                    break;
                else
                    $counter++;

                $block_ip_str = inet_ntop($blocked_ip);
                $tmp_ip = str_replace(":", ":&#8203;", $block_ip_str);
                $rdns_link = "";
                $rdns_link .= "<i class=\"fa fa-search icon-pointer\" onclick=\"javascript:ajaxResolve('{$block_ip_str}');\" title=\"";
                $rdns_link .= gettext("Resolve host via reverse DNS lookup") . "\" alt=\"Icon Reverse Resolve with DNS\"></i>";
                if (!is_private_ip($block_ip_str) && (substr($block_ip_str, 0, 2) != 'fc') &&
                    (substr($block_ip_str, 0, 2) != 'fd')) {
                    $rdns_link .= "&nbsp;&nbsp;<i class=\"fa fa-globe\" onclick=\"javascript:ajaxGeoIP('{$block_ip_str}');\" title=\"";
                    $rdns_link .= gettext("Check host GeoIP data") . "\" alt=\"Icon Check host GeoIP\"></i>";
                }

                $result[] = array(
                    'id' => "block_".$counter,
                    'ip' => $tmp_ip."<br />".$rdns_link,
                    'descr' => $blocked_desc,
                    'remove' => "<i class=\"fa fa-times icon-pointer text-danger\" onclick=\"javascript:remove('{$block_ip_str}');\" title=\"".gettext("Delete host from Blocked Table")."\"></i>"
                );
            }
        }

        return array(
            'current' => 1,
            'rowCount' => count($result),
            'total' => count($result),
            'rows' => $result
        );
    }


    function removeAction() {
        if (isset($_POST['ip'])) {
            require_once("plugins.inc.d/suricata.inc");
            $suri_pf_table = SURICATA_PF_TABLE;
            $ip = $_POST['ip'];
            if (is_ipaddr($ip)) {
                exec("/sbin/pfctl -t {$suri_pf_table} -T delete {$ip}");
                return array('status' => 'ok');
            }
        }
        return array('status' => 'failed');
    }


    function clearAction() {
        if (isset($_POST['clear'])) {
            require_once("plugins.inc.d/suricata.inc");
            $suri_pf_table = SURICATA_PF_TABLE;
            exec("/sbin/pfctl -t {$suri_pf_table} -T flush");
            return array('status' => 'ok');
        }
        return array('status' => 'failed');
    }
}
