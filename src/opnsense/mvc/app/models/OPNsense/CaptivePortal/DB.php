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
namespace OPNsense\CaptivePortal;

use \Phalcon\DI\FactoryDefault;
use \Phalcon\Db\Adapter\Pdo\Sqlite;
use \Phalcon\Logger\Adapter\Syslog;

/**
 * Class DB, provides access to the captiveportal internal administration ( one sqllite db per zone )
 * @package CaptivePortal
 */
class DB
{

    /**
     * zone name
     * @var string
     */
    private $zone = null;

    /**
     * database handle
     * @var SQLite3
     */
    private $handle = null;

    /**
     * datatypes for captive portal table
     * @var array
     */
    private $captiveportal_types = array(
        "allow_time" => \PDO::PARAM_INT,
        "pipeno_in" => \PDO::PARAM_INT,
        "pipeno_out" => \PDO::PARAM_INT,
        "ip" => \PDO::PARAM_STR,
        "mac" => \PDO::PARAM_STR,
        "username" => \PDO::PARAM_STR,
        "sessionid" => \PDO::PARAM_STR,
        "bpassword" => \PDO::PARAM_STR,
        "session_timeout" => \PDO::PARAM_INT,
        "idle_timeout" => \PDO::PARAM_INT,
        "session_terminate_time" => \PDO::PARAM_INT,
        "interim_interval" => \PDO::PARAM_INT,
        "radiusctx" => \PDO::PARAM_STR
    );

    /**
     * datatypes for captive portal mac table
     * @var array
     */
    private $captiveportal_mac_types = array(
        "mac" => \PDO::PARAM_STR,
        "ip" => \PDO::PARAM_STR,
        "pipeno_in" => \PDO::PARAM_INT,
        "pipeno_out" => \PDO::PARAM_INT,
        "last_checked" => \PDO::PARAM_INT
    );

    /**
     * datatypes for captive portal ip table
     * @var array
     */
    private $captiveportal_ip_types = array(
        "ip" => \PDO::PARAM_STR,
        "pipeno_in" => \PDO::PARAM_INT,
        "pipeno_out" => \PDO::PARAM_INT,
        "last_checked" => \PDO::PARAM_INT
    );

    /**
     * open / create new captive portal database for zone
     * @param $zone zone name
     */
    public function __construct($zone)
    {
        $this->zone = $zone;
        $this->open();
    }

    /**
     * destruct, close sessions
     */
    public function __destruct()
    {
        if ($this->handle != null) {
            $this->handle->close();
        }
    }

    /**
     * open database, on failure send message tot syslog
     * creates structure needed for this captiveportal zone
     * @return SQLite3
     */
    public function open()
    {
        // open database
        $db_path = "/var/db/captiveportal{$this->zone}.db";

        try {
            $this->handle = new Sqlite(array("dbname" => $db_path));
            $sql = array();

            // create structure on new database
            $sql[] = "CREATE TABLE IF NOT EXISTS captiveportal (" .# table used for authenticated users
                "allow_time INTEGER, pipeno_in INTEGER, pipeno_out INTEGER, ip TEXT, mac TEXT, username TEXT, " .
                "sessionid TEXT, bpassword TEXT, session_timeout INTEGER, idle_timeout INTEGER, " .
                "session_terminate_time INTEGER, interim_interval INTEGER, radiusctx TEXT)";
            $sql[] = "CREATE UNIQUE INDEX IF NOT EXISTS idx_active ON captiveportal (sessionid, username)";
            $sql[] = "CREATE INDEX IF NOT EXISTS user ON captiveportal (username)";
            $sql[] = "CREATE INDEX IF NOT EXISTS ip ON captiveportal (ip)";
            $sql[] = "CREATE INDEX IF NOT EXISTS starttime ON captiveportal (allow_time)";
            $sql[] = "CREATE TABLE IF NOT EXISTS captiveportal_mac (" .   # table used for static mac's
                "mac TEXT, ip TEXT,pipeno_in INTEGER, pipeno_out INTEGER, last_checked INTEGER )";
            $sql[] = "CREATE UNIQUE INDEX IF NOT EXISTS idx_mac ON captiveportal_mac (mac)";
            $sql[] = "CREATE TABLE IF NOT EXISTS captiveportal_ip (" .    # table used for static ip's
                "ip TEXT,pipeno_in INTEGER, pipeno_out INTEGER, last_checked INTEGER )";
            $sql[] = "CREATE UNIQUE INDEX IF NOT EXISTS idx_ip ON captiveportal_ip (ip)";

            foreach ($sql as $cmd) {
                if (!$this->handle->execute($cmd)) {
                    $logger = new Syslog("logportalauth", array(
                        'option' => LOG_PID,
                        'facility' => LOG_LOCAL4
                    ));
                    $msg = "Error during table {$this->zone} creation. Error message: {$this->handle->lastErrorMsg()}";
                    $logger->error($msg);
                    $this->handle = null;
                    break;
                }
            }
        } catch (\Exception $e) {
            $logger = new Syslog("logportalauth", array(
                'option' => LOG_PID,
                'facility' => LOG_LOCAL4
            ));
            $logger->error("Error opening database for zone " . $this->zone . " : " . $e->getMessage() . " ");
            $this->handle = null;
        }

        return $this->handle;
    }

    /**
     * remove session(s) from database
     * @param $sessionids session ids ( or id )
     */
    public function removeSession($sessionids)
    {
        if ($this->handle != null) {
            if (is_array($sessionids)) {
                $tmpids = $sessionids;
            } else {
                $tmpids = array($sessionids);
            }

            $this->handle->begin();
            $stmt = $this->handle->prepare('DELETE FROM captiveportal WHERE sessionid = :sessionid');
            foreach ($tmpids as $session) {
                $this->handle->executePrepared(
                    $stmt,
                    array('sessionid' => $session),
                    array("sessionid" => \PDO::PARAM_STR)
                );
                $stmt->execute();
            }
            $this->handle->commit();

        }
    }

    /**
     *
     * @param string $sessionid session id
     * @param Array() $content data to alter ( fields from "captiveportal")
     */
    public function updateSession($sessionid, $content)
    {
        if ($this->handle != null) {
            $query = "update captiveportal set ";
            $bind_values = array("sessionid" => $sessionid);
            foreach ($content as $fieldname => $fieldvalue) {
                // you may not alter data not described in $this->captiveportal_types
                if (array_key_exists($fieldname, $this->captiveportal_types)) {
                    if (sizeof($bind_values) > 1) {
                        $query .= " , ";
                    }
                    $query .= $fieldname . " = " . " :" . $fieldname . "  ";
                    $bind_values[$fieldname] = $fieldvalue;
                }
            }
            $query .= " where sessionid = :sessionid ";
            try {
                $this->handle->execute($query, $bind_values, $this->captiveportal_types);
            } catch (\Exception $e) {
                $logger = new Syslog("logportalauth", array(
                    'option' => LOG_PID,
                    'facility' => LOG_LOCAL4
                ));
                $msg = "Trying to modify DB returned error (zone =  " . $this->zone . " ) : " . $e->getMessage() . " ";
                $logger->error($msg);
            }
        }
    }

    /**
     * insert new session information into this zone's database
     *
     * @param string $sessionid unique session id
     * @param Array() field content ( defined fields in "captiveportal")
     */
    public function insertSession($sessionid, $content)
    {
        if ($this->handle != null) {
            // construct insert query, using placeholders for bind variables
            $bind_values = array("sessionid" => $sessionid);
            $query = "insert into captiveportal (sessionid ";
            $query_values = "values (:sessionid ";
            foreach ($content as $fieldname => $fieldvalue) {
                // you may not alter data not described in $this->captiveportal_types
                if (array_key_exists($fieldname, $this->captiveportal_types)) {
                    $query .= "," . $fieldname . " ";
                    $query_values .= ", :" . $fieldname;
                    $bind_values[$fieldname] = $fieldvalue;
                }
            }
            $query .= " ) " . $query_values . ") ";
            try {
                $this->handle->execute($query, $bind_values, $this->captiveportal_types);
            } catch (\Exception $e) {
                $logger = new Syslog("logportalauth", array(
                    'option' => LOG_PID,
                    'facility' => LOG_LOCAL4
                ));
                $msg = "Trying to modify DB returned error (zone =  " . $this->zone . " ) : " . $e->getMessage() . " ";
                $logger->error($msg);
            }
        }
    }

    /**
     * get captive portal clients
     * @param array() $qryargs query filters (named array)
     * @param string $operator choose and/or
     * @param string $order_by sort order
     */
    public function listClients($qryargs, $operator = "and", $order_by = null)
    {
        // construct query, only parse fields defined by $this->captiveportal_types
        $qry_tag = "where ";
        $query = "select * from captiveportal ";
        $query_order_by = "";
        foreach ($qryargs as $fieldname => $fieldvalue) {
            if (array_key_exists($fieldname, $this->captiveportal_types)) {
                $query .= $qry_tag . $fieldname . " = " . " :" . $fieldname . "  ";
                $qry_tag = " " . $operator . " ";
            }
        }

        // apply ordering to result, validate fields
        if (is_array($order_by)) {
            foreach ($order_by as $fieldname) {
                if (is_array($order_by) && in_array($fieldname, $order_by)) {
                    if ($query_order_by != "") {
                        $query_order_by .= " , ";
                    }
                    $query_order_by .= $fieldname;
                }

            }
        }
        if ($query_order_by != "") {
            $query .= " order by " . $query_order_by;
        }


        $resultset = $this->handle->query($query, $qryargs, $this->captiveportal_types);
        $resultset->setFetchMode(\Phalcon\Db::FETCH_OBJ);

        return $resultset->fetchAll();
    }


    /**
     * @param array $qryargs query filters (named array)
     * @param string $operator and/or
     * @return mixed number of connected users/clients
     */
    public function countClients($qryargs = array(), $operator = "and")
    {
        $query = "select count(*) cnt from captiveportal ";
        $qry_tag = "where ";
        foreach ($qryargs as $fieldname => $fieldvalue) {
            if (array_key_exists($fieldname, $this->captiveportal_types)) {
                $query .= $qry_tag . $fieldname . " = " . " :" . $fieldname . "  ";
                $qry_tag = " " . $operator . " ";
            }
        }

        $resultset = $this->handle->query($query, $qryargs, $this->captiveportal_types);
        $resultset->setFetchMode(\Phalcon\Db::FETCH_OBJ);

        return $resultset->fetchAll()[0]->cnt;

    }

    /**
     * list all fixed ip addresses for this zone
     *
     * @return Array()
     */
    public function listFixedIPs()
    {
        $result = array();
        if ($this->handle != null) {
            $resultset = $this->handle->query("select ip,pipeno_in,pipeno_out,last_checked from captiveportal_ip");
            $resultset->setFetchMode(\Phalcon\Db::FETCH_OBJ);

            foreach ($resultset->fetchAll() as $record) {
                $result[$record->ip] = $record;
            }
        }

        return $result;
    }

    /**
     * insert new passthru mac address
     * @param $ip hosts ip address
     * @param int $pipeno_in
     * @param int $pipeno_out
     */
    public function upsertFixedIP($ip, $pipeno_in = null, $pipeno_out = null)
    {
        // perform an upsert to update the data for this physical host.
        // unfortunately this costs an extra write io for the first record, but provides cleaner code
        $params = array("ip" => $ip, "pipeno_in" => $pipeno_in, "pipeno_out" => $pipeno_out, "last_checked" => time());
        $this->handle->execute(
            "insert or ignore into captiveportal_ip(ip) values (:ip)",
            array("ip" => $ip),
            $this->captiveportal_ip_types
        );
        $sql ="update captiveportal_ip set ip=:ip, last_checked=:last_checked, ".
            "pipeno_in = :pipeno_in, pipeno_out = :pipeno_out where ip =:ip ";
        $this->handle->execute($sql, $params, $this->captiveportal_ip_types);
    }

    /**
     * drop address from administration (captiveportal_ip)
     * @param $ip ip address
     */
    public function dropFixedIP($ip)
    {
        $this->handle->execute(
            "delete from captiveportal_ip where ip =:ip ",
            array("ip" => $ip),
            $this->captiveportal_ip_types
        );
    }

    /**
     * list all passthru mac addresses for this zone
     *
     * @return Array()
     */
    public function listPassthruMacs()
    {
        $result = array();
        if ($this->handle != null) {
            $resultset = $this->handle->query("select mac,ip,last_checked,pipeno_in,pipeno_out from captiveportal_mac");
            $resultset->setFetchMode(\Phalcon\Db::FETCH_OBJ);

            foreach ($resultset->fetchAll() as $record) {
                $result[$record->mac] = $record;
            }
        }

        return $result;
    }

    /**
     * insert new passthru mac address
     * @param $mac physical address
     * @param $ip hosts ip address
     * @param null $pipeno_in
     * @param null $pipeno_out
     */
    public function upsertPassthruMAC($mac, $ip, $pipeno_in = null, $pipeno_out = null)
    {
        // perform an upsert to update the data for this physical host.
        // unfortunately this costs an extra write io for the first record, but provides cleaner code
        $params = array(
            "mac" => $mac,
            "ip" => $ip,
            "pipeno_in" => $pipeno_in,
            "pipeno_out" => $pipeno_out,
            "last_checked" => time()
        );
        $this->handle->execute(
            "insert or ignore into captiveportal_mac(mac) values (:mac)",
            array("mac" => $mac),
            $this->captiveportal_mac_types
        );
        $sql = "update captiveportal_mac set ip=:ip, last_checked=:last_checked, ".
            "pipeno_in = :pipeno_in, pipeno_out = :pipeno_out where mac =:mac " ;

        $this->handle->execute($sql, $params, $this->captiveportal_mac_types);
    }

    /**
     * drop address from administration (captiveportal_mac)
     * @param $mac physical address
     */
    public function dropPassthruMAC($mac)
    {
        $this->handle->execute(
            "delete from captiveportal_mac where mac =:mac ",
            array("mac" => $mac),
            $this->captiveportal_mac_types
        );
    }
}
