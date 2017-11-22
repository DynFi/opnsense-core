<?php

/**
 *    Copyright (C) 2015 Deciso B.V.
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
namespace OPNsense\Base\Menu;

use OPNsense\Core\Config;

/**
 * Class MenuSystem
 * @package OPNsense\Base\Menu
 */
class MenuSystem
{
    /**
     * @var null|MenuItem root node
     */
    private $root = null;

    /**
     * add menu structure to root
     * @param string $filename menu xml filename
     * @throws MenuInitException unloadable menu xml
     */
    private function addXML($filename)
    {
        // load and validate menu xml
        if (!file_exists($filename)) {
            throw new MenuInitException('Menu xml '.$filename.' missing');
        }
        $menuXml = simplexml_load_file($filename);
        if ($menuXml === false) {
            throw new MenuInitException('Menu xml '.$filename.' not valid');
        }
        if ($menuXml->getName() != "menu") {
            throw new MenuInitException('Menu xml '.$filename.' seems to be of wrong type');
        }

        // traverse items
        foreach ($menuXml as $key => $node) {
            $this->root->addXmlNode($node);
        }
    }

    /**
     * append menu item to existing root
     * @param string $root xpath expression
     * @param string $id item if (tag name)
     * @param array $properties properties
     * @return null|MenuItem
     */
    public function appendItem($root, $id, $properties)
    {
        $node = $this->root;
        foreach (explode(".", $root) as $key) {
            $node = $node->findNodeById($key);
            if ($node == null) {
                return null;
            }
        }

        return $node->append($id, $properties);
    }

    /**
     * construct a new menu
     * @throws MenuInitException
     */
    public function __construct()
    {
        $this->root = new MenuItem("root");
        // crawl all vendors and modules and add menu definitions
        foreach (glob(__DIR__.'/../../../*') as $vendor) {
            foreach (glob($vendor.'/*') as $module) {
                $menu_cfg_xml = $module.'/Menu/Menu.xml';
                if (file_exists($menu_cfg_xml)) {
                    $this->addXML($menu_cfg_xml);
                }
            }
        }

        $config = Config::getInstance()->object();

        // collect interfaces for dynamic (interface) menu tabs...
        $iftargets = array("if" => array(), "wl" => array(), "fw" => array(), "dhcp4" => array(), "dhcp6" => array());
        if ($config->interfaces->count() > 0) {
            foreach ($config->interfaces->children() as $key => $node) {
                // Interfaces tab
                if (empty($node->virtual)) {
                    $iftargets['if'][$key] = !empty($node->descr) ? (string)$node->descr : strtoupper($key);
                }
                // Wireless status tab
                if (!empty($node->wireless)) {
                    $iftargets['wl'][$key] = !empty($node->descr) ? (string)$node->descr : strtoupper($key);
                }
                // "Firewall: Rules" menu tab...
                if (isset($node->enable)) {
                    $iftargets['fw'][$key] = !empty($node->descr) ? (string)$node->descr : strtoupper($key);
                }
                // "Services: DHCPv[46]" menu tab:
                if (empty($node->virtual) && isset($node->enable)) {
                    if (!empty(filter_var($node->ipaddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))) {
                        $iftargets['dhcp4'][$key] = !empty($node->descr) ? (string)$node->descr : strtoupper($key);
                    }
                    if (!empty(filter_var($node->ipaddrv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))) {
                        $iftargets['dhcp6'][$key] = !empty($node->descr) ? (string)$node->descr : strtoupper($key);
                    }
                }
            }
        }

        foreach (array_keys($iftargets) as $tab) {
            natcasesort($iftargets[$tab]);
        }

        // add interfaces to "Interfaces" menu tab...
        $ordid = 0;
        foreach ($iftargets['if'] as $key => $descr) {
            $this->appendItem('Interfaces', $key, array(
                'url' => '/interfaces.php?if='. $key,
                'visiblename' => '[' . $descr . ']',
                'cssclass' => 'fa fa-sitemap',
                'order' => $ordid++,
            ));
        }

        $ordid = 100;
        foreach ($iftargets['wl'] as $key => $descr) {
            $this->appendItem('Interfaces.Wireless', $key, array(
                'visiblename' => sprintf(gettext('%s Status'), $descr),
                'url' => '/status_wireless.php?if='. $key,
                'order' => $ordid++,
            ));
        }

        // add interfaces to "Firewall: Rules" menu tab...
        $iftargets['fw'] = array_merge(array('FloatingRules' => gettext('Floating')), $iftargets['fw']);
        $ordid = 0;
        foreach ($iftargets['fw'] as $key => $descr) {
            $this->appendItem('Firewall.Rules', $key, array(
                'url' => '/firewall_rules.php?if='. $key,
                'visiblename' => $descr,
                'order' => $ordid++,
            ));
            if ($key == 'FloatingRules') {
                $this->appendItem('Firewall.Rules.' . $key, 'Top' . $key, array(
                    'url' => '/firewall_rules.php',
                    'visibility' => 'hidden',
                ));
            }
            $this->appendItem('Firewall.Rules.' . $key, 'Add' . $key, array(
                'url' => '/firewall_rules_edit.php?if='. $key,
                'visibility' => 'hidden',
            ));
            $this->appendItem('Firewall.Rules.' . $key, 'Edit' . $key, array(
                'url' => '/firewall_rules_edit.php?if=' . $key . '&*',
                'visibility' => 'hidden',
            ));
        }

        // add interfaces to "Services: DHCPv[46]" menu tab:
        $ordid = 0;
        foreach ($iftargets['dhcp4'] as $key => $descr) {
            $this->appendItem('Services.DHCPv4', $key, array(
                'url' => '/services_dhcp.php?if='. $key,
                'visiblename' => "[$descr]",
                'order' => $ordid++,
            ));
            $this->appendItem('Services.DHCPv4.' . $key, 'Add' . $key, array(
                'url' => '/services_dhcp_edit.php?if='. $key,
                'visibility' => 'hidden',
            ));
            $this->appendItem('Services.DHCP4.' . $key, 'Edit' . $key, array(
                'url' => '/services_dhcp_edit.php?if='. $key . '&*',
                'visibility' => 'hidden',
            ));
        }
        $ordid = 0;
        foreach ($iftargets['dhcp6'] as $key => $descr) {
            $this->appendItem('Services.DHCPv6', $key, array(
                'url' => '/services_dhcpv6.php?if='. $key,
                'visiblename' => "[$descr]",
                'order' => $ordid++,
            ));
            $this->appendItem('Services.DHCPv6.' . $key, 'Add' . $key, array(
                'url' => '/services_dhcpv6_edit.php?if='. $key,
                'visibility' => 'hidden',
            ));
            $this->appendItem('Services.DHCPv6.' . $key, 'Edit' . $key, array(
                'url' => '/services_dhcpv6_edit.php?if='. $key . '&*',
                'visibility' => 'hidden',
            ));
            $this->appendItem('Services.RouterAdv', $key, array(
                'url' => '/services_router_advertisements.php?if='. $key,
                'visiblename' => "[$descr]",
                'order' => $ordid++,
            ));
        }
    }

    /**
     * return full menu system including selected items
     * @param string $url current location
     * @return array
     */
    public function getItems($url)
    {
        $this->root->toggleSelected($url);
        $menu = $this->root->getChildren();

        return $menu;
    }

    /**
     * return the currently selected page's breadcrumbs
     * @return array
     */
    public function getBreadcrumbs()
    {
        $nodes = $this->root->getChildren();
        $breadcrumbs = array();

        while ($nodes != null) {
            $next = null;
            foreach ($nodes as $node) {
                if ($node->Selected) {
                    /* ignore client-side anchor in breadcrumb */
                    if (!empty($node->Url) && strpos($node->Url, '#') !== false) {
                        $next = null;
                        break;
                    }
                    $breadcrumbs[] = array('name' => $node->VisibleName);
                    /* only go as far as the first reachable URL */
                    $next = empty($node->Url) ? $node->Children : null;
                    break;
                }
            }
            $nodes = $next;
        }

        return $breadcrumbs;
    }
}
