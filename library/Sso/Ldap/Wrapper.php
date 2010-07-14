<?php
/**
 * @package sso
 */

/**
 * Sso_Ldap_Wrapper
 *  
 * Simply wraps all PHP LDAP extension functions and also adds some error handling.
 *
 * @package sso
 */
class Sso_Ldap_Wrapper
{
	/**
	 * @see http://www.php.net/manual/en/function.ldap-errno.php
	 */
	const ERROR_CONNECTION_FAILURE  = 0x5b;
	const ERROR_ALREADY_EXISTS      = 0x44;
	const ERROR_INVALID_SYNTAX      = 0x15;
	const ERROR_NO_SUCH_OBJECT      = 0x20;
	const ERROR_INVALID_DN_SYNTAX   = 0x22;
	const ERROR_INAPPROPRIATE_AUTH  = 0x30;
	const ERROR_INVALID_CREDENTIALS = 0x31;

	/**
     *
     * @param string  $hostname
     * @param integer $port
     *
     * @return resource|false
     */
	public function connect($hostname, $port=null) {
		$ret = ldap_connect($hostname, $port);
		if (!is_resource($ret)) {
            throw new Sso_Ldap_Exception('Wrapper error: Failed to connect.', self::ERROR_CONNECTION_FAILURE);
        }
        ldap_set_option($ret, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ret, LDAP_OPT_REFERRALS, 0);
		return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     * @param string   $option
     * @param string   $newval
     *
     * @return boolean
     */
	public function set_option($link_identifier, $option, $newval)  {
        $this->_checkLink($link_identifier);
		$ret = @ldap_set_option($link_identifier, $option, $newval);
        $this->_checkError($link_identifier);
		return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     * @param string   $bind_rdn
     * @param string   $bind_password
     *
     * @return boolean
     */
	public function bind($link_identifier, $bind_rdn=null, $bind_password=null) {
		$this->_checkLink($link_identifier);
		$ret = @ldap_bind($link_identifier, $bind_rdn, $bind_password);
		$this->_checkError($link_identifier);
		return $ret;
	}	

    /**
     *
     * @param resource $link_identifier
     * @param string   $base_dn
     * @param string   $filter
     * @param array    $attributes
     * @param integer  $attrsonly
     * @param integer  $sizelimit
     * @param integer  $timelimit
     * @param integer  $deref
     *
     * @return resource|false
     */
	public function read($link_identifier, $base_dn, $filter, $attributes=null, $attrsonly=null, $sizelimit=null, $timelimit=null, $deref=null) {
		$this->_checkLink($link_identifier);
        $ret = @ldap_read($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
		$this->_checkError($link_identifier);
        return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     * @param string   $dn
     * @param array    $entry
     *
     * @return boolean
     */
	public function mod_add($link_identifier, $dn, $entry) {
		$this->_checkLink($link_identifier);
		$ret = @ldap_mod_add($link_identifier, $dn, $entry);
		$this->_checkError($link_identifier);
		return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     * @param string   $dn
     * @param array    $entry
     *
     * @return boolean
     */
	public function modify($link_identifier, $dn, $entry) {
		$this->_checkLink($link_identifier);
		$ret = @ldap_modify($link_identifier, $dn, $entry);
		$this->_checkError($link_identifier);
		return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     * @param string   $dn
     * @param array    $entry
     *
     * @return boolean
     */
	public function mod_del($link_identifier, $dn, $entry) {
		$this->_checkLink($link_identifier);
		$ret = @ldap_mod_del($link_identifier, $dn, $entry);
		$this->_checkError($link_identifier);
		return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     *
     * @param string $dn
     *
     * @return boolean
     */
	public function del($link_identifier, $dn) {
		$this->_checkLink($link_identifier);
		$ret = @ldap_delete($link_identifier, $dn);
		$this->_checkError($link_identifier);
		return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     * @param string   $dn
     * @param array    $entry
     *
     * @return boolean
     */
	public function add($link_identifier, $dn, $entry) {
		$this->_checkLink($link_identifier);
		$ret = @ldap_add($link_identifier, $dn, $entry);
		$this->_checkError($link_identifier);
		return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     * @param string   $base_dn
     * @param string   $filter
     * @param array    $attributes
     * @param integer  $attrsonly
     * @param integer  $sizelimit
     * @param integer  $timelimit
     * @param integer  $deref
     *
     * @return resource|false
     */
	public function search($link_identifier, $base_dn, $filter, $attributes=null, $attrsonly=null, $sizelimit=null, $timelimit=null, $deref=null) {
		$this->_checkLink($link_identifier);
		$ret = @ldap_search($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
		$this->_checkError($link_identifier);
		return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     * @param string   $base_dn
     * @param string   $filter
     * @param array    $attributes
     * @param integer  $attrsonly
     * @param integer  $sizelimit
     * @param integer  $timelimit
     * @param integer  $deref
     * 
     * @return resource|false
     */
	public function children($link_identifier, $base_dn, $filter, $attributes=null, $attrsonly=null, $sizelimit=null, $timelimit=null, $deref=null) {
		$this->_checkLink($link_identifier);
		$ret = @ldap_list($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
		$this->_checkError($link_identifier);
		return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     * @param resource $result_identifier
     *
     * @return array|false
     */
	public function get_entries($link_identifier, $result_identifier) {
		$this->_checkLink($link_identifier);
		$ret = @ldap_get_entries($link_identifier, $result_identifier);
		$this->_checkError($link_identifier);
		if(is_null($ret)) {
			$ret = array();
		}
		return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     * @param resource $result_identifier
     *
     * @return resource|false
     */
	public function first_entry($link_identifier, $result_identifier) {
		$this->_checkLink($link_identifier);
		$ret = @ldap_first_entry($link_identifier, $result_identifier);
		$this->_checkError($link_identifier);
		return $ret;
	}

    /**
     *
     * @param resource $link_identifier
     * @param resource $result_identifier
     *
     * @return array|false
     */
	public function get_attributes($link_identifier, $result_identifier) {
		$this->_checkLink($link_identifier);
		$ret = @ldap_get_attributes($link_identifier, $result_identifier);
		$this->_checkError($link_identifier);
		return $ret;
	}

    /**
     * Some error occurred: throw Sso_Ldap_Exception
     *
     * @param resource $link
     *
     * @throws Sso_Ldap_Exception
     */
	public function error($link) {
		$this->_checkLink($link);
		throw new Sso_Ldap_Exception(ldap_error($link), ldap_errno($link));
	}

    /**
     * Check if we have a valid ldap link identifier
     *
     * @param resource $link_identifier
     * 
     * @throws Sso_Ldap_Exception
     */
    private function _checkLink($link_identifier) {
        if (!is_resource($link_identifier)) {
            throw new Sso_Ldap_Exception('Wrapper error: No valid link identifier.', 999);
        }
    }

    /**
     * Check if the last ldap operation failed
     *
     * @param resource $link_identifier
     *
     * @throws Sso_Ldap_Exception
     */
    private function _checkError($link_identifier) {
		$error_no = ldap_errno($link_identifier);
        if ($error_no != 0) {
			throw new Sso_Ldap_Exception('Wrapper error: '.ldap_error($link_identifier), $error_no);
        }
    }
}
