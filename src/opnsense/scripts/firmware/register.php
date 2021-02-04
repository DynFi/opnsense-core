#!/usr/local/bin/php
<?php

/*
 * Copyright (c) 2021 Franco Fichtner <franco@opnsense.org>
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

$action = $name = 'undefined';

if (count($argv) > 1) {
    $action = $argv[1];
}

if (count($argv) > 2) {
    $name = $argv[2];

    if (strpos($name, 'os-') !== 0) {
        /* not a plugin, don't care */
        exit();
    }
}

require_once('script/load_phalcon.php');

use OPNsense\Core\Config;

$config = Config::getInstance()->object();

function plugins_get($config)
{
    $plugins = [];

    if (!isset($config->system->firmware)) {
        $config->system->addChild('firmware');
    }

    if (!isset($config->system->firmware->plugins)) {
        $config->system->firmware->addChild('plugins');
    } else {
        $plugins = explode(',', (string)$config->system->firmware->plugins);
    }

    return array_flip($plugins);
}

function plugins_set($config, $plugins)
{
    $config->system->firmware->plugins = implode(',', array_keys($plugins));

    if (empty($config->system->firmware->plugins)) {
        unset($config->system->firmware->plugins);
    }

    if (!@count($config->system->firmware->children())) {
        unset($config->system->firmware);
    }

    Config::getInstance()->save();
}

function plugins_found($name)
{
    $bare = preg_replace('/^os-|-devel$/', '', $name);
    return file_exists('/usr/local/opnsense/version/' . $bare);
}

function plugins_remove_sibling($name, $plugins)
{
    $other = preg_replace('/-devel$/', '', $name);
    if ($other == $name) {
        $other .= '-devel';
    }

    if (isset($plugins[$other])) {
        unset($plugins[$other]);
    }

    return $plugins;
}

$plugins = plugins_get($config);

switch ($action) {
    case 'install':
        if (!plugins_found($name)) {
            return;
        }
        $plugins = plugins_remove_sibling($name, $plugins);
        $plugins[$name] = 'hello';
        break;
    case 'remove':
        if (plugins_found($name)) {
            return;
        }
        if (isset($plugins[$name])) {
            unset($plugins[$name]);
        }
        $plugins = plugins_remove_sibling($name, $plugins);
        break;
    case 'resync':
        $unknown = [];
        foreach (glob('/usr/local/opnsense/version/*') as $name) {
            $name = basename($name);
            if (strpos($name, 'base') === 0) {
                continue;
            }
            if (strpos($name, 'kernel') === 0) {
                continue;
            }
            if (strpos($name, 'core') === 0) {
                continue;
            }
            /* XXX when we have JSON data we can read the package name and pick it up */
            $unknown[] = "os-{$name}";
        }
        foreach (array_keys($plugins) as $name) {
            if (!plugins_found($name)) {
                echo "Unregistering plugin: $name" . PHP_EOL;
                unset($plugins[$name]);
            }
        }
        break;
    default:
        exit();
}

plugins_set($config, $plugins);
