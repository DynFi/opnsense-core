<?php

/**
 *    Copyright (c) 2015-2017 Franco Fichtner <franco@opnsense.org>
 *    Copyright (c) 2015-2016 Deciso B.V.
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

namespace OPNsense\Core\Api;

use \OPNsense\Base\ApiControllerBase;
use \OPNsense\Core\Backend;
use \OPNsense\Core\Config;

/**
 * Class FirmwareController
 * @package OPNsense\Core
 */
class FirmwareController extends ApiControllerBase
{
    /**
     * retrieve available updates
     * @return array
     */
    public function statusAction()
    {
        $this->sessionClose(); // long running action, close session
        $backend = new Backend();
        $response = json_decode(trim($backend->configdRun('firmware check')), true);

        if ($response != null) {
            if (array_key_exists('connection', $response) && $response['connection'] == 'error') {
                $response['status_msg'] = gettext('Connection error.');
                $response['status'] = 'error';
            } elseif (array_key_exists('repository', $response) && $response['repository'] == 'error') {
                $response['status_msg'] = gettext('Could not find the repository on the selected mirror.');
                $response['status'] = 'error';
            } elseif (array_key_exists('updates', $response) && $response['updates'] == 0) {
                $response['status_msg'] = gettext('There are no updates available on the selected mirror.');
                $response['status'] = 'none';
            } elseif ((array_key_exists(0, $response['upgrade_packages']) &&
                $response['upgrade_packages'][0]['name'] == 'pkg') ||
                (array_key_exists(0, $response['reinstall_packages']) &&
                $response['reinstall_packages'][0]['name'] == 'pkg')) {
                $response['status_upgrade_action'] = 'pkg';
                $response['status'] = 'ok';
                $response['status_msg'] = gettext('There is a mandatory update for the package manager available.');
            } elseif (array_key_exists('updates', $response)) {
                $response['status_upgrade_action'] = 'all';
                $response['status'] = 'ok';
                if ($response['updates'] == 1) {
                    /* keep this dynamic for template translation even though %s is always '1' */
                    $response['status_msg'] = sprintf(
                        gettext('There is %s update available, total download size is %s.'),
                        $response['updates'],
                        $response['download_size']
                    );
                } else {
                    $response['status_msg'] = sprintf(
                        gettext('There are %s updates available, total download size is %s.'),
                        $response['updates'],
                        $response['download_size']
                    );
                }
                if ($response['upgrade_needs_reboot'] == 1) {
                    $response['status_msg'] = sprintf(
                        '%s %s',
                        $response['status_msg'],
                        gettext('This update requires a reboot.')
                    );
                }
            }

            $sorted = array();

            /*
             * new_packages: array with { name: <package_name>, version: <package_version> }
             * reinstall_packages: array with { name: <package_name>, version: <package_version> }
             * upgrade_packages: array with { name: <package_name>,
             *     current_version: <current_version>, new_version: <new_version> }
             */
            foreach (array('new_packages', 'reinstall_packages', 'upgrade_packages') as $pkg_type) {
                if (isset($response[$pkg_type])) {
                    foreach ($response[$pkg_type] as $value) {
                        switch ($pkg_type) {
                            case 'new_packages':
                                $sorted[$value['name']] = array(
                                    'new' => $value['version'],
                                    'reason' => gettext('new'),
                                    'name' => $value['name'],
                                    'old' => gettext('N/A'),
                                );
                                break;
                            case 'reinstall_packages':
                                $sorted[$value['name']] = array(
                                    'reason' => gettext('reinstall'),
                                    'new' => $value['version'],
                                    'old' => $value['version'],
                                    'name' => $value['name'],
                                );
                                break;
                            case 'upgrade_packages':
                                $sorted[$value['name']] = array(
                                    'reason' => gettext('update'),
                                    'old' => $value['current_version'],
                                    'new' => $value['new_version'],
                                    'name' => $value['name'],
                                );
                                break;
                            default:
                                /* undefined */
                                break;
                        }
                    }
                }
            }

            uksort($sorted, function ($a, $b) {
                return strnatcasecmp($a, $b);
            });

            $response['all_packages'] = $sorted;
        } else {
            $response = array(
                'status_msg' => gettext('Firmware status check was aborted internally. Please try again.'),
                'status' => 'unknown'
            );
        }

        return $response;
    }

    /**
     * Retrieve specific changelog in text and html format
     * @param string $version changelog to retrieve
     * @return array correspondng changelog in both formats
     * @throws \Exception
     */
    public function changelogAction($version)
    {
        $this->sessionClose(); // long running action, close session
        $backend = new Backend();
        $response = array();

        if ($this->request->isPost()) {
            // sanitize package name
            $filter = new \Phalcon\Filter();
            $filter->add('version', function ($value) {
                return preg_replace('/[^0-9a-zA-Z\.]/', '', $value);
            });
            $version = $filter->sanitize($version, 'version');
            $text = trim($backend->configdRun(sprintf('firmware changelog text %s', $version)));
            $html = trim($backend->configdRun(sprintf('firmware changelog html %s', $version)));
            if (!empty($text)) {
                $response['text'] = $text;
            }
            if (!empty($html)) {
                $response['html'] = $html;
            }
        }

        return $response;
    }

    /**
     * Retrieve specific license for package in text format
     * @param string $package package to retrieve
     * @return array with all possible licenses
     * @throws \Exception
     */
    public function licenseAction($package)
    {
        $this->sessionClose(); // long running action, close session
        $backend = new Backend();
        $response = array();

        if ($this->request->isPost()) {
            // sanitize package name
            $filter = new \Phalcon\Filter();
            $filter->add('scrub', function ($value) {
                return preg_replace('/[^0-9a-zA-Z\-]/', '', $value);
            });
            $package = $filter->sanitize($package, 'scrub');
            $text = trim($backend->configdRun(sprintf('firmware license %s', $package)));
            if (!empty($text)) {
                $response['license'] = $text;
            }
        }

        return $response;
    }

    /**
     * perform reboot
     * @return array status
     * @throws \Exception
     */
    public function rebootAction()
    {
        $backend = new Backend();
        $response = array();
        if ($this->request->isPost()) {
            $response['status'] = 'ok';
            $response['msg_uuid'] = trim($backend->configdRun('firmware reboot', true));
        } else {
            $response['status'] = 'failure';
        }

        return $response;
    }

    /**
     * perform poweroff
     * @return array status
     * @throws \Exception
     */
    public function poweroffAction()
    {
        $backend = new Backend();
        $response = array();
        if ($this->request->isPost()) {
            $response['status'] = 'ok';
            $response['msg_uuid'] = trim($backend->configdRun('firmware poweroff', true));
        } else {
            $response['status'] = 'failure';
        }

        return $response;
    }

    /**
     * perform actual upgrade
     * @return array status
     * @throws \Exception
     */
    public function upgradeAction()
    {
        $backend = new Backend();
        $response = array();
        if ($this->request->hasPost("upgrade")) {
            $response['status'] = 'ok';
            if ($this->request->getPost("upgrade") == "pkg") {
                $action = "firmware upgrade pkg";
            } else {
                $action = "firmware upgrade all";
            }
            $response['msg_uuid'] = trim($backend->configdRun($action, true));
        } else {
            $response['status'] = 'failure';
        }

        return $response;
    }

    /**
     * run a security audit
     * @return array status
     * @throws \Exception
     */
    public function auditAction()
    {
        $backend = new Backend();
        $response = array();

        if ($this->request->isPost()) {
            $response['status'] = 'ok';
            $response['msg_uuid'] = trim($backend->configdRun("firmware audit", true));
        } else {
            $response['status'] = 'failure';
        }

        return $response;
    }

    /**
     * reinstall package
     * @param string $pkg_name package name to reinstall
     * @return array status
     * @throws \Exception
     */
    public function reinstallAction($pkg_name)
    {
        $backend = new Backend();
        $response = array();

        if ($this->request->isPost()) {
            $response['status'] = 'ok';
            // sanitize package name
            $filter = new \Phalcon\Filter();
            $filter->add('pkgname', function ($value) {
                return preg_replace('/[^0-9a-zA-Z-_]/', '', $value);
            });
            $pkg_name = $filter->sanitize($pkg_name, "pkgname");
            // execute action
            $response['msg_uuid'] = trim($backend->configdpRun("firmware reinstall", array($pkg_name), true));
        } else {
            $response['status'] = 'failure';
        }

        return $response;
    }

    /**
     * install package
     * @param string $pkg_name package name to install
     * @return array status
     * @throws \Exception
     */
    public function installAction($pkg_name)
    {
        $backend = new Backend();
        $response = array();

        if ($this->request->isPost()) {
            $response['status'] = 'ok';
            // sanitize package name
            $filter = new \Phalcon\Filter();
            $filter->add('pkgname', function ($value) {
                return preg_replace('/[^0-9a-zA-Z-_]/', '', $value);
            });
            $pkg_name = $filter->sanitize($pkg_name, "pkgname");
            // execute action
            $response['msg_uuid'] = trim($backend->configdpRun("firmware install", array($pkg_name), true));
        } else {
            $response['status'] = 'failure';
        }

        return $response;
    }

    /**
     * remove package
     * @param string $pkg_name package name to remove
     * @return array status
     * @throws \Exception
     */
    public function removeAction($pkg_name)
    {
        $backend = new Backend();
        $response = array();

        if ($this->request->isPost()) {
            $response['status'] = 'ok';
            // sanitize package name
            $filter = new \Phalcon\Filter();
            $filter->add('pkgname', function ($value) {
                return preg_replace('/[^0-9a-zA-Z-_]/', '', $value);
            });
            $pkg_name = $filter->sanitize($pkg_name, "pkgname");
            // execute action
            $response['msg_uuid'] = trim($backend->configdpRun("firmware remove", array($pkg_name), true));
        } else {
            $response['status'] = 'failure';
        }

        return $response;
    }

    /**
     * lock package
     * @param string $pkg_name package name to lock
     * @return array status
     * @throws \Exception
     */
    public function lockAction($pkg_name)
    {
        $backend = new Backend();
        $response = array();

        if ($this->request->isPost()) {
            $response['status'] = 'ok';
            // sanitize package name
            $filter = new \Phalcon\Filter();
            $filter->add('pkgname', function ($value) {
                return preg_replace('/[^0-9a-zA-Z-_]/', '', $value);
            });
            $pkg_name = $filter->sanitize($pkg_name, "pkgname");
            // execute action
            $response['msg_uuid'] = trim($backend->configdpRun("firmware lock", array($pkg_name), true));
        } else {
            $response['status'] = 'failure';
        }

        return $response;
    }

    /**
     * unlock package
     * @param string $pkg_name package name to unlock
     * @return array status
     * @throws \Exception
     */
    public function unlockAction($pkg_name)
    {
        $backend = new Backend();
        $response = array();

        if ($this->request->isPost()) {
            $response['status'] = 'ok';
            // sanitize package name
            $filter = new \Phalcon\Filter();
            $filter->add('pkgname', function ($value) {
                return preg_replace('/[^0-9a-zA-Z-_]/', '', $value);
            });
            $pkg_name = $filter->sanitize($pkg_name, "pkgname");
            // execute action
            $response['msg_uuid'] = trim($backend->configdpRun("firmware unlock", array($pkg_name), true));
        } else {
            $response['status'] = 'failure';
        }

        return $response;
    }

    /**
     * retrieve exectution status
     */
    public function runningAction()
    {
        $backend = new Backend();

        $result = array(
            'status' => trim($backend->configdRun('firmware running'))
        );

        return $result;
    }
    /**
     * retrieve upgrade status (and log file of current process)
     */
    public function upgradestatusAction()
    {
        $backend = new Backend();
        $result = array('status' => 'running');
        $cmd_result = trim($backend->configdRun('firmware status'));

        $result['log'] = $cmd_result;

        if (trim($cmd_result) == 'Execute error') {
            $result['status'] = 'error';
        } elseif (strpos($cmd_result, '***DONE***') !== false) {
            $result['status'] = 'done';
        } elseif (strpos($cmd_result, '***REBOOT***') !== false) {
            $result['status'] = 'reboot';
        }

        return $result;
    }

    /**
     * list local and remote packages
     * @return array
     */
    public function infoAction()
    {
        $this->sessionClose(); // long running action, close session

        $keys = array('name', 'version', 'comment', 'flatsize', 'locked', 'license');
        $backend = new Backend();
        $response = array();

        /* allows us to select UI features based on product state */
        $response['product_version'] = trim(file_get_contents('/usr/local/opnsense/version/opnsense'));
        $response['product_name'] = trim(file_get_contents('/usr/local/opnsense/version/opnsense.name'));

        $devel = explode('-', $response['product_name']);
        $devel = count($devel) == 2 ? $devel[1] == 'devel' : false;

        /* need both remote and local, create array earlier */
        $packages = array();
        $plugins = array();

        /* package infos are flat lists with 3 pipes as delimiter */
        foreach (array('remote', 'local') as $type) {
            $current = $backend->configdRun("firmware ${type}");
            $current = explode("\n", trim($current));

            foreach ($current as $line) {
                $expanded = explode('|||', $line);
                $translated = array();
                $index = 0;
                if (count($expanded) != count($keys)) {
                    continue;
                }
                foreach ($keys as $key) {
                    $translated[$key] = $expanded[$index++];
                }

                /* mark remote packages as "provided", local as "installed" */
                $translated['provided'] = $type == 'remote' ? "1" : "0";
                $translated['installed'] = $type == 'local' ? "1" : "0";
                if (isset($packages[$translated['name']])) {
                    /* local iteration, mark package provided */
                    $translated['provided'] = "1";
                }
                $packages[$translated['name']] = $translated;

                /* figure out local and remote plugins */
                $plugin = explode('-', $translated['name']);
                if (count($plugin)) {
                    if ($plugin[0] == 'os' || ($type == 'local' && $plugin[0] == 'ospriv') ||
                        ($devel && $type == 'remote' && $plugin[0] == 'ospriv')) {
                        if ($devel || (count($plugin) < 3 || end($plugin) != 'devel')) {
                            $plugins[$translated['name']] = $translated;
                        }
                    }
                }
            }
        }

        uksort($packages, function ($a, $b) {
            return strnatcasecmp($a, $b);
        });

        $response['package'] = array();
        foreach ($packages as $package) {
            $response['package'][] = $package;
        }

        uksort($plugins, function ($a, $b) {
            return strnatcasecmp($a, $b);
        });

        $response['plugin'] = array();
        foreach ($plugins as $plugin) {
            $response['plugin'][] = $plugin;
        }

        /* also pull in changelogs from here */
        $changelogs = json_decode(trim($backend->configdRun('firmware changelog list')), true);
        if ($changelogs == null) {
            $changelogs = array();
        } else {
            foreach ($changelogs as &$changelog) {
                /* rewrite dates as ISO */
                $date = date_parse($changelog['date']);
                $changelog['date'] = sprintf('%04d-%02d-%02d', $date['year'], $date['month'], $date['day']);
            }
            /* sort in reverse */
            usort($changelogs, function ($a, $b) {
                return strcmp($b['date'], $a['date']);
            });
        }

        $response['changelog'] = $changelogs;

        return $response;
    }

    /**
     * list firmware mirror and flavour options
     * @return array
     */
    public function getFirmwareOptionsAction()
    {
        // todo: we might want to move these into configuration files later
        $mirrors = array();
        $mirrors[''] = '(default)';
        $mirrors['https://opnsense.aivian.org'] = 'Aivian (Shaoxing, CN)';
        $mirrors['https://opnsense-update.deciso.com'] = 'Deciso (NL, Commercial)';
        $mirrors['https://mirror.auf-feindgebiet.de/opnsense'] = 'auf-feindgebiet.de (Cloudflare CDN)';
        $mirrors['https://opnsense.c0urier.net'] = 'c0urier.net (Lund, SE)';
        //$mirrors['https://fleximus.org/mirror/opnsense'] = 'Fleximus (Roubaix, FR)';
        $mirrors['https://fourdots.com/mirror/OPNSense'] = 'FourDots (Belgrade, RS)';
        $mirrors['http://mirror.ams1.nl.leaseweb.net/opnsense'] = 'LeaseWeb (Amsterdam, NL)';
        $mirrors['http://mirror.fra10.de.leaseweb.net/opnsense'] = 'LeaseWeb (Frankfurt, DE)';
        $mirrors['http://mirror.sfo12.us.leaseweb.net/opnsense'] = 'LeaseWeb (San Francisco, US)';
        $mirrors['http://mirror.wdc1.us.leaseweb.net/opnsense'] = 'LeaseWeb (Washington, D.C., US)';
        $mirrors['http://mirrors.nycbug.org/pub/opnsense'] = 'NYC*BUG (New York, US)';
        $mirrors['http://pkg.opnsense.org'] = 'OPNsense (Amsterdam, NL)';
        $mirrors['http://mirror.ragenetwork.de/opnsense'] = 'RageNetwork (Munich, DE)';
        $mirrors['http://mirror.wjcomms.co.uk/opnsense'] = 'WJComms (London, GB)';

        $has_subscription = array();
        $has_subscription[] = 'https://opnsense-update.deciso.com';

        $flavours = array();
        $flavours[''] = '(default)';
        $flavours['libressl'] = 'LibreSSL';
        $flavours['latest'] = 'OpenSSL';

        return array("mirrors"=>$mirrors, "flavours" => $flavours, 'has_subscription' => $has_subscription);
    }

    /**
     * retrieve current firmware configuration options
     * @return array
     */
    public function getFirmwareConfigAction()
    {
        $result = array();
        $result['mirror'] = '';
        $result['flavour'] = '';

        if (!empty(Config::getInstance()->object()->system->firmware->mirror)) {
            $result['mirror'] = (string)Config::getInstance()->object()->system->firmware->mirror;
        }
        if (!empty(Config::getInstance()->object()->system->firmware->flavour)) {
            $result['flavour'] = (string)Config::getInstance()->object()->system->firmware->flavour;
        }

        return $result;
    }

    /**
     * set firmware configuration options
     * @return array status
     */
    public function setFirmwareConfigAction()
    {
        $response = array("status" => "failure");

        if ($this->request->isPost()) {
            $response['status'] = 'ok';
            $selectedMirror = filter_var($this->request->getPost("mirror", null, ""), FILTER_SANITIZE_URL);
            $selectedFlavour = filter_var($this->request->getPost("flavour", null, ""), FILTER_SANITIZE_URL);
            $selSubscription = filter_var($this->request->getPost("subscription", null, ""), FILTER_SANITIZE_URL);

            // config data without model, prepare xml structure and write data
            if (!isset(Config::getInstance()->object()->system->firmware)) {
                Config::getInstance()->object()->system->addChild('firmware');
            }

            if (!isset(Config::getInstance()->object()->system->firmware->mirror)) {
                Config::getInstance()->object()->system->firmware->addChild('mirror');
            }

            if (empty($selSubscription)) {
                Config::getInstance()->object()->system->firmware->mirror = $selectedMirror;
            } else {
                // prepend subscription
                Config::getInstance()->object()->system->firmware->mirror = $selectedMirror . '/' . $selSubscription;
            }

            if (!isset(Config::getInstance()->object()->system->firmware->flavour)) {
                Config::getInstance()->object()->system->firmware->addChild('flavour');
            }
            Config::getInstance()->object()->system->firmware->flavour = $selectedFlavour;

            Config::getInstance()->save();

            $backend = new Backend();
            $backend->configdRun("firmware configure");
        }

        return $response;
    }
}
