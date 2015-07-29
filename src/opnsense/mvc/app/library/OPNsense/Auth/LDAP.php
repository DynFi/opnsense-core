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

/**
 * Class LDAP connector
 * @package OPNsense\Auth
 */
class LDAP
{
    /**
     * @var int ldap version to use
     */
    private $ldapVersion = 3 ;

    /**
     * @var null
     */
    private $baseSearchDN = null;

    /**
     * @var null bind internal reference
     */
    private $ldapHandle = null;

    /**
     * @var array list of attributes to return in searches
     */
    private $ldapSearchAttr = array();

    /**
     * close ldap handle if open
     */
    private function closeLDAPHandle()
    {
        if ($this->ldapHandle !== null) {
            @ldap_close($this->ldapHandle);
        }
    }

    /**
     * add additional query result attributes
     * @param $attrName string attribute to append to result list
     */
    private function addSearchAttribute($attrName)
    {
        if (!array_key_exists($attrName, $this->ldapSearchAttr)) {
            $this->ldapSearchAttr[] = $attrName;
        }

    }

    /**
     * search ldap tree
     * @param $filter ldap filter string to use
     * @param string $ldap_scope scope either one or tree
     * @return array|bool result list or false on errors
     */
    private function search($filter, $ldap_scope = "tree")
    {
        $result = false;
        if ($this->ldapHandle != null) {
            // if we're looking at multple dn's, split and combine output
            foreach (explode(";", $this->baseSearchDN) as $baseDN) {
                if ($ldap_scope == "one") {
                    $sr=@ldap_list($this->ldapHandle, $baseDN, $filter, $this->ldapSearchAttr);
                } else {
                    $sr=@ldap_search($this->ldapHandle, $baseDN, $filter, $this->ldapSearchAttr);
                }
                if ($sr !== false) {
                    $info = @ldap_get_entries($this->ldapHandle, $sr);
                    if ($info !== false) {
                        if ($result === false) {
                            $result = array();
                            $result['count'] = 0;
                        }
                        for ($i = 0; $i < $info["count"]; $i++) {
                            $result['count']++;
                            $result[] = $info[$i];
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param null $baseSearchDN setup base searchDN or list of DN's separated by ;
     * @param int $ldapVersion setup ldap version
     */
    public function __construct($baseSearchDN = null, $ldapVersion = 3)
    {
        $this->ldapVersion = $ldapVersion;
        $this->baseSearchDN = $baseSearchDN;
        // setup ldap general search list, list gets updated by requested data
        $this->addSearchAttribute("dn");
        $this->addSearchAttribute("name");
    }

    /**
     * close ldap handle on destruction
     */
    public function __destruct()
    {
        $this->closeLDAPHandle();
    }

    /**
     * @param $bind_url string url to use
     * @param null $userdn connect dn to use, leave empty for anonymous
     * @param null $password password
     * @return bool connect status (success/fail)
     */
    public function connect($bind_url, $userdn = null, $password = null)
    {
        $this->closeLDAPHandle();
        $this->ldapHandle = @ldap_connect($bind_url);

        if ($this->ldapHandle !== false) {
            ldap_set_option($this->ldapHandle, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($this->ldapHandle, LDAP_OPT_DEREF, LDAP_DEREF_SEARCHING);
            ldap_set_option($this->ldapHandle, LDAP_OPT_PROTOCOL_VERSION, (int)$this->ldapVersion);
            $bindResult = @ldap_bind($this->ldapHandle, $userdn, $password);
            if ($bindResult) {
                return true;
            }
        }

        $this->ldapHandle = null;
        return false;
    }

    /**
     * search user by name or expression
     * @param $username string username(s) to search
     * @param $userNameAttribute string ldap attribute to use for the search
     * @param $extendedQuery string|null additional search criteria (narrow down search)
     * @return array|bool
     */
    public function searchUsers($username, $userNameAttribute, $extendedQuery = null)
    {
        if ($this->ldapHandle !== false) {
            // on Active Directory sAMAccountName is returned as samaccountname
            $userNameAttribute = strtolower($userNameAttribute);
            // add $userNameAttribute to search results
            $this->addSearchAttribute($userNameAttribute);
            $result = array();
            if (empty($extendedQuery)) {
                $searchResults = $this->search("({$userNameAttribute}={$username})");
            } else {
                // add additional search phrases
                $searchResults = $this->search("(&({$userNameAttribute}={$username})({$extendedQuery}))");
            }
            if ($searchResults !== false) {
                for ($i = 0; $i < $searchResults["count"]; $i++) {
                    // fetch distinguished name and most likely username (try the search field first)
                    foreach (array($userNameAttribute, "name") as $ldapAttr) {
                        if (isset($searchResults[$i][$ldapAttr]) && $searchResults[$i][$ldapAttr]['count'] > 0) {
                            $result[] = array("name" => $searchResults[$i][$ldapAttr][0]
                                        , "fullname" => $searchResults[$i]['name'][0]
                                        , "dn" => $searchResults[$i]['dn']);
                            break;
                        }
                    }
                }
                return $result;
            }
        }
        return false;
    }

    /**
     * List organizational units
     * @return array|bool list of OUs or false on failure
     */
    public function listOUs()
    {
        $result = array();
        if ($this->ldapHandle !== false) {
            $searchResults = $this->search("(|(ou=*)(cn=Users))");
            if ($searchResults !== false) {
                for ($i = 0; $i < $searchResults["count"]; $i++) {
                    $result[] = $searchResults[$i]['dn'];
                }

                return $result;
            }
        }

        return false;
    }
}
