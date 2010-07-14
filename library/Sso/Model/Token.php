<?php
/**
 * Sso_Model_Token
 *
 * @uses      Sso_Model_Base
 * @category  Sso_Service
 * @package   Sso
 * @copyright Copyright (c) 2009 Cable&Wireless
 * @author    Lorna Mitchell <lorna@ibuildings.com>
 */
class Sso_Model_Token extends Sso_Model_Base
{
    /**
     * token
     *
     * @var string The sha key
     * @access public
     */
    public $token;

    /**
     * fetch
     *
     * locate a token record and instantiate an object with its details
     *
     * @param string $id The token string identifier
     *
     * @return Sso_Model_Token|boolean If the token exists, it is returned - otherwise false is returned
     * @static
     */
    public static function fetch($id) {
        $connection = Sso_Model_Base::getConnection();
		$id = self::filterToken($id);
		
        try {
            $token_ldap = $connection->getBase()->find('sso='.$id.',ou=Sessions,ou=SSO');
            if ($token_ldap instanceOf Sso_Ldap_Entry) {
                $token = Sso_Model_Token::getTokenFromEntry($token_ldap);
                return $token;
            }
        } catch(Exception $e) {
            $log = Zend_Registry::get('log');
            $log->warn('Error fetching ' . $id. ': '. $e->getMessage());
        }
        return false;
    }

    /**
     * is_valid
     *
     * Checks if the token exists, is current, and user exists
     *
     * @return boolean true if the user is authorised, false if not
     */
    public function isValid() {
        $config = parent::getConfig();
        $expiry = $this->accessTime + $config->token->time_to_live;
        
        if ($this->token && $expiry > time()) {
            return true;
        }
        return false;
    }

    /**
     * authorise
     *
     * take the username and password, look up the user, and create the token object
     *
     * @param string $username the user name or identifier
     * @param string $oassword the password for this user
     *
     * @return mixed SSO_Model_Token if the crednetials were OK, errors otherwise
     * @throws Sso_Exception_BadRequest
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_NotFound
     * @static
     */
    static public function authorise($username, $password) {
        // check credentials
        if (empty($username) || empty($password)) {
            throw new Sso_Exception_BadRequest('Username and password must be supplied');
        }

        $connection = Sso_Model_Base::getConnection();
        $log = Zend_Registry::get('log');

        // make a new token hash thing
        $token_string = hash('sha256', '12345' . uniqid(mt_rand(), true));

        // find the user
		$username = self::filterToken($username);
        $user = $connection->getBase()->search('sso='.$username)->get(0);
        if (!$user instanceOf Sso_Ldap_Entry) {
            throw new Sso_Exception_NotFound('User not found');
        }

        // check the password
        if ($user['ssoPassword'][0] != md5($password)) {
            throw new Sso_Exception_InvalidAuth('Incorrect username/password combination');
        }

        // get the parent session node
        $obj = $connection->getBase()->search('ou=Sessions')->get(0);
        if (!$obj instanceOf Sso_Ldap_Entry) {
            throw new Sso_Exception_NotFound('LDAP session root not found');
        }

        try {
            $token = $obj->addChild('ssoToken','sso='.$token_string);
            $token['ssoName']      = $token_string;
            $token['ssoUser']      = $user->getDn();
            $token['ssoTimestamp'] = time();
            $token->save();
        } catch(Exception $e) {
            $log->warn('Failed adding token ' . $token_string . ': ' . $e->getMessage());
        }

        $log->info('user ' . $username . ' logged in from ' . $_SERVER['REMOTE_ADDR']);

        // return the new token
        $retval = Sso_Model_Token::fetch($token_string);
        return $retval;
    }

    /**
     * delete
     *
     * Remove the record
     *
     * @access public
     * @return boolean
     */
    public function delete() {
        $connection = Sso_Model_Base::getConnection();

        $search_string ='sso='.$this->token.',ou=Sessions,ou=SSO';
        try {
            $token_ldap = $connection->getBase()->find($search_string);
            if ($token_ldap instanceOf Sso_Ldap_Entry) {
                $token_ldap->delete();
            }
        } catch(Exception $e) {
            // this case doesn't matter
        }
        return true;

    }

    /**
     * getTokenFromEntry
     *
     * Get the Sso_Model_Token object from this ldap entry
     *
     * @param Sso_LdapEntry $entry
     *
     * @return Sso_Model_Token
     * @static
     */
    static public function getTokenFromEntry($entry) {
        $token = new Sso_Model_Token();
        $token->token      = $entry['sso'][0];
        $token->username   = Sso_Model_User::getNameFromDn($entry['ssoUser'][0]);
        $token->accessTime = time();

        try{
          $entry['ssoTimestamp'] = $token->accessTime;
          $entry->save();
        } catch (Sso_Ldap_Exception $e) {
            Zend_Registry::get('log')->warn('cannot update timestamp for token ' . $token->token);
        }

        return $token;
    }

	/**
	 * Perform some replacements to filter bad token characters
	 * that could allow LDAP injection
	 * @param string $id - the token
	 * @return string - the filtered token
	 */
	protected static function filterToken($id) {
		return str_replace(array('\\', '*', '(', ')', "\0", 	',', '+', '"', '<', '>', ';'), 
						array('\\5c', '\\2a', '\\28', '\\29', '\\00','\\,', '\\+', '\\"', '\\<', '\\>', '\\;'), 
						$id);
	}
}
