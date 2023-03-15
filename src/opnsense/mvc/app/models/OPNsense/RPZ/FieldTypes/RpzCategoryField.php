<?php

/*
 * Copyright (C) 2022 DynFi
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

namespace OPNsense\RPZ\FieldTypes;

use OPNsense\Base\FieldTypes\BaseListField;

class LessStrictJson
{
    protected $index = -1;
    protected $inStr = false;
    protected $comment = 0;
    protected $commaPos = -1;

    public function strip(string $json): string
    {
        if (!\preg_match('%\/(\/|\*)%', $json) && !\preg_match('/,\s*(\}|\])/', $json)) {
            return $json;
        }

        $this->reset();

        return $this->doStrip($json);
    }

    protected function reset()
    {
        $this->index   = -1;
        $this->inStr   = false;
        $this->comment = 0;
    }

    protected function doStrip(string $json): string
    {
        $return = '';
        $crlf   = ["\n" => '\n', "\r" => '\r'];

        while (isset($json[++$this->index])) {
            $oldprev                  = $prev ?? '';
            list($prev, $char, $next) = $this->getSegments($json);

            $return = $this->checkTrail($char, $return);

            if ($this->inStringOrCommentEnd($prev, $char, $char . $next, $oldprev)) {
                $return .= $this->inStr && isset($crlf[$char]) ? $crlf[$char] : $char;

                continue;
            }

            $wasSingle = 1 === $this->comment;
            if ($this->hasCommentEnded($char, $char . $next) && $wasSingle) {
                $return = \rtrim($return) . $char;
            }

            $this->index += $char . $next === '*/' ? 1 : 0;
        }

        return $return;
    }

    protected function getSegments(string $json): array
    {
        return [
            $json[$this->index - 1] ?? '',
            $json[$this->index],
            $json[$this->index + 1] ?? '',
        ];
    }

    protected function checkTrail(string $char, string $json): string
    {
        if ($char === ',' || $this->commaPos === -1) {
            $this->commaPos = $this->commaPos + ($char === ',' ? 1 : 0);

            return $json;
        }

        if (\ctype_digit($char) || \strpbrk($char, '"tfn{[')) {
            $this->commaPos = -1;
        } elseif ($char === ']' || $char === '}') {
            $pos  = \strlen($json) - $this->commaPos - 1;
            $json = \substr($json, 0, $pos) . \ltrim(\substr($json, $pos), ',');

            $this->commaPos = -1;
        } else {
            $this->commaPos += 1;
        }

        return $json;
    }

    protected function inStringOrCommentEnd(string $prev, string $char, string $next, string $oldprev): bool
    {
        return $this->inString($char, $prev, $next, $oldprev) || $this->inCommentEnd($next);
    }

    protected function inString(string $char, string $prev, string $next, string $oldprev): bool
    {
        if (0 === $this->comment && $char === '"' && $prev !== '\\') {
            return $this->inStr = !$this->inStr;
        }

        if ($this->inStr && \in_array($next, ['":', '",', '"]', '"}'], true)) {
            $this->inStr = "$oldprev$prev" !== '\\\\';
        }

        return $this->inStr;
    }

    protected function inCommentEnd(string $next): bool
    {
        if (!$this->inStr && 0 === $this->comment) {
            $this->comment = $next === '//' ? 1 : ($next === '/*' ? 2 : 0);
        }

        return 0 === $this->comment;
    }

    protected function hasCommentEnded(string $char, string $next): bool
    {
        $singleEnded = $this->comment === 1 && $char == "\n";
        $multiEnded  = $this->comment === 2 && $next == '*/';

        if ($singleEnded || $multiEnded) {
            $this->comment = 0;

            return true;
        }

        return false;
    }

    public function decode(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        $decoded = \json_decode($this->strip($json), $assoc, $depth, $options);

        if (\JSON_ERROR_NONE !== $err = \json_last_error()) {
            $msg = 'JSON decode failed';

            if (\function_exists('json_last_error_msg')) {
                $msg .= ': ' . \json_last_error_msg();
            }

            throw new \RuntimeException($msg, $err);
        }

        return $decoded;
    }

    public static function parse(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        static $parser;

        if (!$parser) {
            $parser = new static;
        }

        return $parser->decode($json, $assoc, $depth, $options);
    }

    public static function parseFromFile(string $file, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException($file . ' does not exist or is not a file');
        }

        $json = \file_get_contents($file);

        return static::parse(\trim($json), $assoc, $depth, $options);
    }
}


class RpzCategoryField extends BaseListField
{
    const RPZ_FILES_DIR = '/usr/local/share/dynfi/rpz';
    const RPZ_STATS_FILE = '/usr/local/share/dynfi/rpz/stats.json';
    const RPZ_STATS_URL = 'http://packages.dynfi.com/rpz/stats.json';
    const RPZ_STATS_INTV = 30 * 86400; // 1 month
    const RAM_PER_B = 40;

    private static $internalStaticOptionList = array();

    protected function actionPostLoadingEvent()
    {
        $rpzStats = array();
        if ((!file_exists(self::RPZ_STATS_FILE)) || (time() - filemtime(self::RPZ_STATS_FILE) > self::RPZ_STATS_INTV)) {
            if (file_exists(self::RPZ_STATS_FILE))
                unlink(self::RPZ_STATS_FILE);
            $fc = @file_get_contents(self::RPZ_STATS_URL);
            if (!empty($fc)) {
                file_put_contents(self::RPZ_STATS_FILE, $fc);
                $rpzStats = (new LessStrictJson)->decode($fc, TRUE);
            }
        } else {
            $rpzStats = (new LessStrictJson)->decode(file_get_contents(self::RPZ_STATS_FILE), TRUE);
        }

        if (empty(self::$internalStaticOptionList)) {
            $rpzFiles = scandir(self::RPZ_FILES_DIR);
            if ($rpzFiles) {
                $rpzFiles = array_diff($rpzFiles, array('.', '..', 'stats.json'));
                foreach ($rpzFiles as &$fname) {
                    $c = str_replace('.conf', '', $fname);
                    $cn = $c;
                    if (isset($rpzStats[$c])) {
                        // $ramusage = $rpzStats[$c]['size'] * self::RAM_PER_B;
                        // $cn = $c.' ('.$this->shortNum($rpzStats[$c]['lines']).' entries, '.$this->shortNum($rpzStats[$c]['size']).'B, needs '.$this->shortNum($ramusage).'B of RAM)';
                        $cn = $c.' ('.$this->shortNum($rpzStats[$c]['lines']).' entries, '.$this->shortNum($rpzStats[$c]['size']).'B)';
                    }
                    self::$internalStaticOptionList[$c] = $cn;
                }
            }
            natcasesort(self::$internalStaticOptionList);
        }
        $this->internalOptionList = self::$internalStaticOptionList;
    }

    private function shortNum($number, $precision = 0) {
        $divisors = array(
            pow(1000, 0) => '',
            pow(1000, 1) => 'K',
            pow(1000, 2) => 'M',
            pow(1000, 3) => 'G',
            pow(1000, 4) => 'T'
        );
        foreach ($divisors as $divisor => $shorthand) {
            if (abs($number) < ($divisor * 1000)) {
                break;
            }
        }
        return number_format($number / $divisor, $precision) . $shorthand;
    }
}
