<?php

/*
 * Copyright (C) 2020 Dawid Kujawa <dawid.kujawa@dynfi.com>
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



function getHeaderButtons($breadcrumbs) {
  if (count($breadcrumbs) >= 2) {
    $main = $breadcrumbs[0]['name'];
    $sub = $breadcrumbs[1]['name'];
    if (isset(HEADER_BUTTON_DEFS[$main])) {
        if (isset(HEADER_BUTTON_DEFS[$main]['name'])) {
            return HEADER_BUTTON_DEFS[$main];
        }

        $subsub = (count($breadcrumbs) >= 3) ? $breadcrumbs[2]['name'] : null;
        if ($subsub) {
            foreach (HEADER_BUTTON_DEFS[$main] as $name => $data) {
                foreach ($data as $item) {
                    if (($item['name'] == $sub) && ($name == $subsub)) {
                        $defs = [];
                        foreach (HEADER_BUTTON_DEFS[$main][$subsub] as $arr) {
                            if ($arr['name'] != $sub)
                                $defs[] = $arr;
                        }
                        return $defs;
                    }
                }
            }
        }

        if (isset(HEADER_BUTTON_DEFS[$main][$sub])) {
            return HEADER_BUTTON_DEFS[$main][$sub];
        }
      }
  }
  return [];
}


function getBreadcrumbsFromUrl($url) {
    $map = array();
    foreach (HEADER_BUTTON_DEFS as $name => $mdata) {
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
