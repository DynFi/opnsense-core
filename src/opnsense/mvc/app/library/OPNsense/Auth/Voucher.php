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

namespace OPNsense\Auth;

use OPNsense\Core\Config;

/**
 * Class Voucher user database connector
 * @package OPNsense\Auth
 */
class Voucher extends Base implements IAuthConnector
{
    /**
     * @var null reference id
     */
    private $refid = null;

    /**
     * @var null database handle
     */
    private $dbHandle = null;

    /**
     * @var int password length to use
     */
    private $passwordLength = 10;

    /**
     * @var int username length
     */
    private $usernameLength = 8;

    /**
     * @var bool use simple passwords (less secure)
     */
    private $simplePasswords = false;

    /**
     * @var array internal list of authentication properties (returned by radius auth)
     */
    private $lastAuthProperties = array();

    /**
     * type name in configuration
     * @return string
     */
    public static function getType()
    {
        return 'voucher';
    }

    /**
     * user friendly description of this authenticator
     * @return string
     */
    public function getDescription()
    {
        return gettext("Voucher");
    }

    /**
     * open database
     */
    private function openDatabase()
    {
        $db_path = '/conf/vouchers_' . $this->refid . '.db';
        $this->dbHandle = new \SQLite3($db_path);
        $this->dbHandle->busyTimeout(30000);
        $results = $this->dbHandle->query('select count(*) cnt from sqlite_master');
        $row = $results->fetchArray();
        if ($row['cnt'] == 0) {
            // new database, setup
            $sql_create = "
                create table vouchers (
                      username      varchar2  -- username
                    , password      varchar2  -- user password (crypted)
                    , vouchergroup  varchar2  -- group of vouchers
                    , validity      integer   -- voucher credits
                    , starttime     integer   -- voucher start at
                    , vouchertype   varchar2  -- (not implemented) voucher type
                    , primary key (username)
                );
                create index idx_voucher_group on vouchers(vouchergroup);
            ";
            $this->dbHandle->exec($sql_create);
        }
    }

    /**
     * check if username does already exist
     * @param string $username username
     * @return bool
     */
    private function userNameExists($username)
    {
        $stmt = $this->dbHandle->prepare('select count(*) cnt from vouchers where username = :username');
        $stmt->bindParam(':username', $username);
        $result = $stmt->execute();
        $row = $result->fetchArray();
        if ($row['cnt'] == 0) {
            return false;
        } else {
            return true;
        }
    }

    private function setStartTime($username, $starttime)
    {
        $stmt = $this->dbHandle->prepare('
                                update vouchers
                                set    starttime = :starttime
                                where username = :username
                                ');
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':starttime', $starttime);
        $stmt->execute();
    }

    /**
     * set connector properties
     * @param array $config connection properties
     */
    public function setProperties($config)
    {
        // fetch unique id for this authenticator
        if (array_key_exists('refid', $config)) {
            $this->refid = $config['refid'];
        } else {
            $this->refid = 'default';
        }
        // use simple passwords
        if (array_key_exists('simplePasswords', $config) && !empty($config['simplePasswords'])) {
            $this->simplePasswords = true;
        }
        // use predefined username and password length
        if (array_key_exists('usernameLength', $config) && is_numeric($config['usernameLength'])) {
            $this->usernameLength = (int)$config['usernameLength'];
        }
        if (array_key_exists('passwordLength', $config) && is_numeric($config['passwordLength'])) {
            $this->passwordLength = (int)$config['passwordLength'];
        }

        $this->openDatabase();
    }

    /**
     * generate new vouchers and store in voucher database
     * @param string $vouchergroup voucher groupname
     * @param int $count number of vouchers to generate
     * @param int $validity time (in seconds)
     * @param int $starttime valid from
     * @return array list of generated vouchers
     */
    public function generateVouchers($vouchergroup, $count, $validity, $starttime = null)
    {
        $response = array();
        if ($this->dbHandle != null) {
            if ($this->simplePasswords) {
                // create a map of easy to read characters
                $characterMap = '';
                while (strlen($characterMap) < 256) {
                    $random_bytes = openssl_random_pseudo_bytes(10000);
                    for ($i = 0; $i < strlen($random_bytes); $i++) {
                        $chr_ord = ord($random_bytes[$i]);
                        if (($chr_ord >= 50 && $chr_ord <= 57) || // 2..9
                            ($chr_ord >= 65 && $chr_ord <= 78) || // A..N
                            ($chr_ord >= 80 && $chr_ord <= 90) || // P..Z
                            ($chr_ord >= 97 && $chr_ord <= 107) || // a..k
                            ($chr_ord >= 109 && $chr_ord <= 110) || // m..n
                            ($chr_ord >= 112 && $chr_ord <= 122)  // p..z
                        ) {
                            $characterMap .= $random_bytes[$i];
                        }
                    }
                }
            } else {
                // list of characters to skip for random generator
                $doNotUseChr = array('<', '>', '{', '}', '&', 'l' , 'O' ,'`', '\'', '|' ,'^', '"');

                // create map of random readable characters
                $characterMap = '';
                while (strlen($characterMap) < 256) {
                    $random_bytes = openssl_random_pseudo_bytes(10000);
                    for ($i = 0; $i < strlen($random_bytes); $i++) {
                        $chr_ord = ord($random_bytes[$i]);
                        if ($chr_ord >= 33 && $chr_ord <= 125 && !in_array($random_bytes[$i], $doNotUseChr)) {
                            $characterMap .= $random_bytes[$i];
                        }
                    }
                }
            }

            // generate new vouchers
            $vouchersGenerated = 0;
            while ($vouchersGenerated < $count) {
                $generatedUsername = '';
                $random_bytes = openssl_random_pseudo_bytes($this->usernameLength);
                for ($j=0; $j < strlen($random_bytes); $j++) {
                    $generatedUsername .= $characterMap[ord($random_bytes[$j])];
                }
                $generatedPassword = '';
                $random_bytes = openssl_random_pseudo_bytes($this->passwordLength);
                for ($j=0; $j < strlen($random_bytes); $j++) {
                    $generatedPassword .= $characterMap[ord($random_bytes[$j])];
                }

                if (!$this->userNameExists($generatedUsername)) {
                    $vouchersGenerated++;
                    // save user, hash password first
                    $generatedPasswordHash = crypt($generatedPassword, '$6$');
                    $stmt = $this->dbHandle->prepare('
                                insert into vouchers(username, password, vouchergroup, validity, starttime)
                                values (:username, :password, :vouchergroup, :validity, :starttime)
                                ');
                    $stmt->bindParam(':username', $generatedUsername);
                    $stmt->bindParam(':password', $generatedPasswordHash);
                    $stmt->bindParam(':vouchergroup', $vouchergroup);
                    $stmt->bindParam(':validity', $validity);
                    $stmt->bindParam(':starttime', $starttime);
                    $stmt->execute();

                    $row = array('username' => $generatedUsername,
                        'password' => $generatedPassword,
                        'vouchergroup' => $vouchergroup,
                        'validity' => $validity,
                        'starttime' => $starttime
                    );
                    $response[] = $row;
                }
            }
        }
        return $response;
    }

    /**
     * drop all vouchers from voucher a voucher group
     * @param string $vouchergroup group name
     */
    public function dropVoucherGroup($vouchergroup)
    {
        $stmt = $this->dbHandle->prepare('
                                delete
                                from vouchers
                                where vouchergroup = :vouchergroup
                                ');
        $stmt->bindParam(':vouchergroup', $vouchergroup);
        $stmt->execute();
    }

    /**
     * list all voucher groups
     * @return array
     */
    public function listVoucherGroups()
    {
        $response = array();
        $stmt = $this->dbHandle->prepare('select distinct vouchergroup from vouchers');
        $result = $stmt->execute();
        while ($row = $result->fetchArray()) {
            $response[] = $row['vouchergroup'];
        }
        return $response;
    }

    /**
     * list vouchers in group
     * @param $vouchergroup voucher group name
     * @return array
     */
    public function listVouchers($vouchergroup)
    {
        $response = array();
        $stmt = $this->dbHandle->prepare('
                  select username, validity, starttime, vouchergroup
                  from vouchers
                  where vouchergroup = :vouchergroup');
        $stmt->bindParam(':vouchergroup', $vouchergroup);
        $result = $stmt->execute();
        while ($row = $result->fetchArray()) {
            $record = array();
            $record['username'] = $row['username'];
            $record['validity'] = $row['validity'];
            # always calculate a starttime, if not registered yet, use now.
            $record['starttime'] = empty($row['starttime']) ? time() : $row['starttime'];
            $record['endtime'] = $record['starttime'] + $row['validity'];

            if (empty($row['starttime'])) {
                $record['state'] = 'unused';
            } elseif (time() < $record['endtime']) {
                $record['state'] = 'valid';
            } else {
                $record['state'] = 'expired';
            }

            $response[] = $record;
        }
        return $response;
    }

    /**
     * expire voucher
     * @param string $username username
     */
    public function expireVoucher($username)
    {
        if ($this->dbHandle != null) {
            $stmt = $this->dbHandle->prepare('
                                    update vouchers
                                    set validity = 0,
                                        starttime = :starttime
                                    where username = :username
                                    ');
            $stmt->bindParam(':username', $username);
            $starttime = time();
            $stmt->bindParam(':starttime', $starttime, SQLITE3_INTEGER);
            $stmt->execute();
        }
    }

    /**
     * drop expired vouchers in group
     * @param $vouchergroup voucher group name
     * @return int number of deleted vouchers
     */
    public function dropExpired($vouchergroup)
    {
        $stmt = $this->dbHandle->prepare('
                  delete
                  from vouchers
                  where vouchergroup = :vouchergroup
                  and starttime is not null
                  and starttime + validity < :endtime
                  ');
        $stmt->bindParam(':vouchergroup', $vouchergroup);
        $endtime = time();
        $stmt->bindParam(':endtime', $endtime, SQLITE3_INTEGER);
        $stmt->execute();

        return $this->dbHandle->changes();
    }

    /**
     * return session info
     * @return array mixed named list of authentication properties
     */
    public function getLastAuthProperties()
    {
        return $this->lastAuthProperties;
    }

    /**
     * authenticate user against voucher database
     * @param string $username username to authenticate
     * @param string $password user password
     * @return bool authentication status
     */
    public function authenticate($username, $password)
    {
        $stmt = $this->dbHandle->prepare('
            select username, password,validity, starttime
            from vouchers
            where username = :username
         ');
        $stmt->bindParam(':username', $username);
        $result = $stmt->execute();
        $row = $result->fetchArray();
        if ($row != null) {
            $passwd = crypt($password, (string)$row['password']);
            if ($passwd == (string)$row['password']) {
                // correct password, check validity
                if ($row['starttime'] == null) {
                    // initial login, set starttime for counter
                    $row['starttime'] = time();
                    $this->setStartTime($username, $row['starttime']);
                }
                if (time() - $row['starttime'] < $row['validity']) {
                    $this->lastAuthProperties['session_timeout'] = $row['validity'] - (time() - $row['starttime']);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * retrieve configuration options
     * @return array
     */
    public function getConfigurationOptions()
    {
        $fields = array();
        $fields["simplePasswords"] = array();
        $fields["simplePasswords"]["name"] = gettext("Use simple passwords (less secure)");
        $fields["simplePasswords"]["type"] = "checkbox";
        $fields["simplePasswords"]["help"] = gettext("Use simple (less secure) passwords, which are easier to read");
        $fields["simplePasswords"]["validate"] = function ($value) {
            return array();
        };
        $fields["usernameLength"] = array();
        $fields["usernameLength"]["name"] = gettext("Username length");
        $fields["usernameLength"]["type"] = "text";
        $fields["usernameLength"]["default"] = null;
        $fields["usernameLength"]["help"] = gettext("Specify alternative username length for generating vouchers");
        $fields["usernameLength"]["validate"] = function ($value) {
            if (!empty($value) && filter_var($value, FILTER_SANITIZE_NUMBER_INT) != $value) {
                return array(gettext("Username length must be a number or empty for default."));
            } else {
                return array();
            }
        };
        $fields["passwordLength"] = array();
        $fields["passwordLength"]["name"] = gettext("Password length");
        $fields["passwordLength"]["type"] = "text";
        $fields["passwordLength"]["default"] = null;
        $fields["passwordLength"]["help"] = gettext("Specify alternative password length for generating vouchers");
        $fields["passwordLength"]["validate"] = function ($value) {
            if (!empty($value) && filter_var($value, FILTER_SANITIZE_NUMBER_INT) != $value) {
                return array(gettext("Password length must be a number or empty for default."));
            } else {
                return array();
            }
        };

        return $fields;
    }

    /**
     * groups not supported
     * @param string $username username to check
     * @param string $gid group id
     * @return boolean
     */
    public function groupAllowed($username, $gid)
    {
        return false;
    }
}
