<?php

/*
 *    Copyright (C) 2020 Dawid Kujawa <dawid.kujawa@dynfi.com>
 *    Copyright (C) 2015 Deciso B.V.
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
 */

namespace OPNsense\Base\Menu;

use OPNsense\Core\Config;
use Phalcon\DI\FactoryDefault;

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
     * @var string location to store merged menu xml
     */
    private $menuCacheFilename = null;

    /**
     * @var int time to live for merged menu xml
     */
    private $menuCacheTTL = 3600;

    /**
     * @var null|array root node for buttons
     */
    private $buttons = null;

    /**
     * add menu structure to root
     * @param string $filename menu xml filename
     * @return \SimpleXMLElement
     * @throws MenuInitException unloadable menu xml
     */
    private function addXML($filename)
    {
        // load and validate menu xml
        if (!file_exists($filename)) {
            throw new MenuInitException('Menu xml ' . $filename . ' missing');
        }
        $menuXml = simplexml_load_file($filename);
        if ($menuXml === false) {
            throw new MenuInitException('Menu xml ' . $filename . ' not valid');
        }
        if ($menuXml->getName() != "menu") {
            throw new MenuInitException('Menu xml ' . $filename . ' seems to be of wrong type');
        }

        return $menuXml;
    }

    /**
     * add buttons structure to root
     * @param string $filename buttons xml filename
     * @return \SimpleXMLElement
     * @throws MenuInitException unloadable menu xml
     */
    private function addButtonsXML($filename)
    {
        // load and validate menu xml
        if (!file_exists($filename)) {
            throw new MenuInitException('Buttons xml ' . $filename . ' missing');
        }
        $buttonsXml = simplexml_load_file($filename);
        if ($buttonsXml === false) {
            throw new MenuInitException('Buttons xml ' . $filename . ' not valid');
        }
        if ($buttonsXml->getName() != "buttons") {
            throw new MenuInitException('Buttons xml ' . $filename . ' seems to be of wrong type');
        }

        return $buttonsXml;
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
     * invalidate cache, removes cache file from disk if available, which forces the next request to persist() again
     */
    public function invalidateCache()
    {
        @unlink($this->menuCacheFilename);
    }

    /**
     * Load and persist Menu configuration to disk.
     * @param bool $nowait when the cache is locked, skip waiting for it to become available.
     * @return SimpleXMLElement
     * @throws MenuInitException
     */
    public function persist($nowait = true)
    {
        // fetch our model locations
        if (!empty(FactoryDefault::getDefault()->get('config')->application->modelsDir)) {
            $modelDirs = FactoryDefault::getDefault()->get('config')->application->modelsDir;
            if (!is_array($modelDirs) && !is_object($modelDirs)) {
                $modelDirs = array($modelDirs);
            }
        } else {
            // failsafe, if we don't have a Phalcon Dependency Injector object, use our relative location
            $modelDirs = array("__DIR__.'/../../../");
        }

        // collect all XML menu definitions into a single file
        $menuXml = new \DOMDocument('1.0');
        $root = $menuXml->createElement('menu');
        $menuXml->appendChild($root);
        // crawl all vendors and modules and add menu definitions
        foreach ($modelDirs as $modelDir) {
            foreach (glob(preg_replace('#/+#', '/', "{$modelDir}/*")) as $vendor) {
                foreach (glob($vendor . '/*') as $module) {
                    $menu_cfg_xml = $module . '/Menu/Menu.xml';
                    if (file_exists($menu_cfg_xml)) {
                        $domNode = dom_import_simplexml($this->addXML($menu_cfg_xml));
                        $domNode = $root->ownerDocument->importNode($domNode, true);
                        $root->appendChild($domNode);
                    }
                }
            }
        }

        // flush to disk
        $fp = fopen($this->menuCacheFilename, file_exists($this->menuCacheFilename) ? "r+" : "w+");
        $lockMode = $nowait ? LOCK_EX | LOCK_NB : LOCK_EX;
        if (flock($fp, $lockMode)) {
            ftruncate($fp, 0);
            fwrite($fp, $menuXml->saveXML());
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            chmod($this->menuCacheFilename, 0660);
        }

        // return generated xml
        return simplexml_import_dom($root);
    }

    /**
     * Load and persist Buttons configuration to disk.
     * @param bool $nowait when the cache is locked, skip waiting for it to become available.
     * @return SimpleXMLElement
     * @throws MenuInitException
     */
    public function persistButtons($nowait = true)
    {
        // fetch our model locations
        if (!empty(FactoryDefault::getDefault()->get('config')->application->modelsDir)) {
            $modelDirs = FactoryDefault::getDefault()->get('config')->application->modelsDir;
            if (!is_array($modelDirs) && !is_object($modelDirs)) {
                $modelDirs = array($modelDirs);
            }
        } else {
            // failsafe, if we don't have a Phalcon Dependency Injector object, use our relative location
            $modelDirs = array("__DIR__.'/../../../");
        }

        // collect all XML buttons definitions into a single file
        $buttonsXml = new \DOMDocument('1.0');
        $root = $buttonsXml->createElement('buttons');
        $buttonsXml->appendChild($root);
        // crawl all vendors and modules and add buttons definitions
        foreach ($modelDirs as $modelDir) {
            foreach (glob(preg_replace('#/+#', '/', "{$modelDir}/*")) as $vendor) {
                foreach (glob($vendor . '/*') as $module) {
                    $buttons_cfg_xml = $module . '/Menu/Buttons.xml';
                    if (file_exists($buttons_cfg_xml)) {
                        $domNode = dom_import_simplexml($this->addButtonsXML($buttons_cfg_xml));
                        $domNode = $root->ownerDocument->importNode($domNode, true);
                        $root->appendChild($domNode);
                    }
                }
            }
        }

        // flush to disk
        $fp = fopen($this->buttonsCacheFilename, file_exists($this->buttonsCacheFilename) ? "r+" : "w+");
        $lockMode = $nowait ? LOCK_EX | LOCK_NB : LOCK_EX;
        if (flock($fp, $lockMode)) {
            ftruncate($fp, 0);
            fwrite($fp, $buttonsXml->saveXML());
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            chmod($this->buttonsCacheFilename, 0660);
        }

        // return generated xml
        return simplexml_import_dom($root);
    }

    /**
     * check if stored menu's are expired
     * @return bool is expired
     */
    public function isExpired()
    {
        if (file_exists($this->menuCacheFilename)) {
            $fstat = stat($this->menuCacheFilename);
            return $this->menuCacheTTL < (time() - $fstat['mtime']);
        }
        return true;
    }

    /**
     * construct a new menu
     * @throws MenuInitException
     */
    public function __construct()
    {
        // set cache location
        $this->menuCacheFilename = sys_get_temp_dir() . "/opnsense_menu_cache.xml";
        $this->buttonsCacheFilename = sys_get_temp_dir() . "/opnsense_buttons_cache.xml";

        // load menu and buttons xml's
        $menuxml = null;
        $buttonsxml = null;
        if (!$this->isExpired()) {
            $menuxml = @simplexml_load_file($this->menuCacheFilename);
            $buttonsxml = @simplexml_load_file($this->buttonsCacheFilename);
        }
        if ($menuxml == null) {
            $menuxml = $this->persist();
        }
        if ($buttonsxml == null) {
            $buttonsxml = $this->persistButtons();
        }

        // load menu xml's
        $this->root = new MenuItem("root");
        foreach ($menuxml as $menu) {
            foreach ($menu as $node) {
                $this->root->addXmlNode($node);
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
                    if (!empty(filter_var($node->ipaddrv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) || !empty($node->dhcpd6track6allowoverride)) {
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
                'url' => '/interfaces.php?if=' . $key,
                'visiblename' => '[' . $descr . ']',
                'cssclass' => 'fa fa-sitemap',
                'order' => $ordid++,
            ));
        }

        $ordid = 100;
        foreach ($iftargets['wl'] as $key => $descr) {
            $this->appendItem('Interfaces.Wireless', $key, array(
                'visiblename' => sprintf(gettext('%s Status'), $descr),
                'url' => '/status_wireless.php?if=' . $key,
                'order' => $ordid++,
            ));
        }

        // add interfaces to "Firewall: Rules" menu tab...
        $iftargets['fw'] = array_merge(array('FloatingRules' => gettext('Floating')), $iftargets['fw']);
        $ordid = 0;
        foreach ($iftargets['fw'] as $key => $descr) {
            $this->appendItem('Firewall.Rules', $key, array(
                'url' => '/firewall_rules.php?if=' . $key,
                'visiblename' => $descr,
                'order' => $ordid++,
            ));
            $this->appendItem('Firewall.Rules.' . $key, 'Select' . $key, array(
                'url' => '/firewall_rules.php?if=' . $key . '&*',
                'visibility' => 'hidden',
            ));
            if ($key == 'FloatingRules') {
                $this->appendItem('Firewall.Rules.' . $key, 'Top' . $key, array(
                    'url' => '/firewall_rules.php',
                    'visibility' => 'hidden',
                ));
            }
            $this->appendItem('Firewall.Rules.' . $key, 'Add' . $key, array(
                'url' => '/firewall_rules_edit.php?if=' . $key,
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
                'url' => '/services_dhcp.php?if=' . $key,
                'visiblename' => "[$descr]",
                'order' => $ordid++,
            ));
            $this->appendItem('Services.DHCPv4.' . $key, 'Edit' . $key, array(
                'url' => '/services_dhcp.php?if=' . $key . '&*',
                'visibility' => 'hidden',
            ));
            $this->appendItem('Services.DHCPv4.' . $key, 'AddStatic' . $key, array(
                'url' => '/services_dhcp_edit.php?if=' . $key,
                'visibility' => 'hidden',
            ));
            $this->appendItem('Services.DHCPv4.' . $key, 'EditStatic' . $key, array(
                'url' => '/services_dhcp_edit.php?if=' . $key . '&*',
                'visibility' => 'hidden',
            ));
        }
        $ordid = 0;
        foreach ($iftargets['dhcp6'] as $key => $descr) {
            $this->appendItem('Services.DHCPv6', $key, array(
                'url' => '/services_dhcpv6.php?if=' . $key,
                'visiblename' => "[$descr]",
                'order' => $ordid++,
            ));
            $this->appendItem('Services.DHCPv6.' . $key, 'Add' . $key, array(
                'url' => '/services_dhcpv6_edit.php?if=' . $key,
                'visibility' => 'hidden',
            ));
            $this->appendItem('Services.DHCPv6.' . $key, 'Edit' . $key, array(
                'url' => '/services_dhcpv6_edit.php?if=' . $key . '&*',
                'visibility' => 'hidden',
            ));
            $this->appendItem('Services.RouterAdv', $key, array(
                'url' => '/services_router_advertisements.php?if=' . $key,
                'visiblename' => "[$descr]",
                'order' => $ordid++,
            ));
        }

        // load config xml's
        $this->buttons = [];
        foreach ($buttonsxml as $buttonData) {
            foreach ((array)$buttonData as $bsection => $bnode) {
                if (!isset($this->buttons[$bsection]))
                    $this->buttons[$bsection] = [];
                foreach ($bnode as $bname => $blist) {
                    if (isset($blist->attributes()['visibleName']))
                        $bname = (string)$blist->attributes()['visibleName'];
                    if (!isset($this->buttons[$bsection][$bname]))
                        $this->buttons[$bsection][$bname] = [];
                    foreach ($blist as $bdef) {
                        $button = [
                            'name' => (string)$bdef->attributes()['visibleName'],
                            'iconClass' => (string)$bdef->attributes()['cssClass'],
                            'buttons' => []
                        ];
                        foreach ($bdef as $blink) {
                            $button['buttons'][] = [
                                'name' => (string)$blink->attributes()['visibleName'],
                                'url' => (string)$blink->attributes()['url']
                            ];
                        }
                        $this->buttons[$bsection][$bname][] = $button;
                    }
                }
            }
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
    public function getBreadcrumbs($url = null)
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

        if ((count($breadcrumbs) <= 2) && ($url)) {
            $_breadcrumbs = $this->getButtonBreadcrumbs($url);
            if ($_breadcrumbs) {
                $breadcrumbs = $_breadcrumbs;
            }
        }

        return $breadcrumbs;
    }

    /**
     * return the buttons-based breadcrumbs
     * @return array
     */
    function getButtonBreadcrumbs($url) {
        $map = array();
        foreach ($this->buttons as $name => $mdata) {
            foreach ($mdata as $data) {
                foreach ($data as $item) {
                    foreach ($item['buttons'] as $b) {
                        $map[$b['url']] = array(array('name' => $name), array('name' => $item['name']), array('name' => $b['name']));
                    }
                }
            }
        }
        return (isset($map[$url])) ? $map[$url] : null;
    }

    /**
     * return list of links for location-based log files
     * @param array $breadcrumbs current breadcrumbs
     * @return array
     */
    public function getHeaderButtons($breadcrumbs)
    {
        if (count($breadcrumbs) >= 2) {
            $main = $breadcrumbs[0]['name'];
            $sub = $breadcrumbs[1]['name'];
            if (isset($this->buttons[$main])) {
                if (isset($this->buttons[$main]['name'])) {
                    return $this->buttons[$main];
                }

                $subsub = (count($breadcrumbs) >= 3) ? $breadcrumbs[2]['name'] : null;
                if ($subsub) {
                    foreach ($this->buttons[$main] as $name => $data) {
                        foreach ($data as $item) {
                            if (($item['name'] == $sub) && ($name == $subsub)) {
                                $defs = [];
                                foreach ($this->buttons[$main][$subsub] as $arr) {
                                    if ($arr['name'] != $sub)
                                        $defs[] = $arr;
                                }
                                return $defs;
                            }
                        }
                    }
                }

                if (isset($this->buttons[$main][$sub])) {
                    return $this->buttons[$main][$sub];
                }
            }
        }
        return [];
    }
}
