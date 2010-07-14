<?php
/**
 * Sso_Model_User
 *
 * The User model
 *
 * @category  Sso_Service
 * @package   Sso
 * @copyright Copyright (c) 2009 Cable&Wireless
 * @author
 */
class Sso_Model_User extends Sso_Model_Base
{
	const USERNAME_MAXLENGTH = 255;
	
    /**
     * fetch
     *
     * @param integer $id the record to retrieve or leave blank for all
     * @param string $order the field to order by
     * @param string $direction the direction to order the field in
     *
     * @return Sso_Model_User|array The resource or array of them
     * @throws Sso_Exception_NotFound
     */
    public static function fetch($id = null, $order = null, $direction = null) {
        $connection = Sso_Model_Base::getConnection();

        if ($id) {
            // fetch this specific record
            $entry = $connection->getBase()->search('sso='.$id)->get(0);
			if (!($entry instanceOf Sso_Ldap_Entry)) {
                throw new Sso_Exception_NotFound('User not found');
            }
            try {
                $retval = Sso_Model_User::getUserFromEntry($entry);
            } catch (Sso_Ldap_Exception $e) {
                throw new Sso_Exception_NotFound('User not found');
            }
        } else {
            // all users
            
            // handle sort vars
            switch($order) {
                case 'username':
                    $order = 'ssoUsername';
                    break;
                case 'createdOn':
                    $order = 'ssoCreated';
                    break;
                default:
                    $order = 'ssoUsername';
                    break;
            }

            if (strtolower($direction) == 'desc') {
                $direction = 'desc';
            } else {
                $direction = 'asc';
            }

            $list = $connection->getBase()->search('objectClass=ssoUser')->sort(array($order => $direction));
            $retval = array();
            if ($list) {
                foreach ($list as $entry) {
                    $retval[] = Sso_Model_User::getUserFromEntry($entry);
                }
            }
        }
        return $retval;
    }

    /**
     * delete
     *
     * delete the user and all its children
     *
     * @return boolean always true
     */
    public function delete() {
        $connection = Sso_Model_Base::getConnection();
        try {
            $user = $connection->getBase()->search('sso='.$this->username)->get(0);
            if ($user instanceOf Sso_Ldap_Entry) {
                $user->delete(true, true);
            }
        } catch(Exception $e) {
            // this case doesn't matter, we don't care
            Zend_Registry::get('log')->info('Delete Error ' . $e->getMessage());
        }
        return true;

    }

    /**
     * getNameFromDn
     *
     * @param string $dn the distinguished name of this entry
     *
     * @return string The name to use for this entry
     * @static
     */
    static public function getNameFromDn($dn) {
        $parts = split(',', $dn);
        // just grab the first bit without the sso= on the front
        return substr($parts[0], 4);
    }

    /**
     * getDnFromName
     *
     * @param string $id The name we use to address this entry
     *
     * @return string The full dn of the entry
     * @static
     */
    static public function getDnFromName($id) {
		if (empty($id)) {
			throw new Sso_Exception_NotFound('User not found (empty identifier)');
		}
		$connection = Sso_Model_Base::getConnection();
		$entry = $connection->getBase()->search('sso='.$id, 'ou=Users,ou=SSO')->get(0);
		if (!($entry instanceOf Sso_Ldap_Entry)) {
			throw new Sso_Exception_NotFound('User not found ('.$id.')');
		}
		return $entry->getDn();
    }

    /**
     * create
     *
     * @param array $params the incoming form parameters from the request
     *
     * @return Sso_Model_User the new object
     * @throws Sso_Exception_BadRequest
     * @static
     */
    static public function create($params) {
    	
        // these are the fields to ignore - ZF stuff plus special fields
        $ignore_fields = parent::$ignore_fields;
        $ignore_fields[] = 'username';
        $ignore_fields[] = 'password';

        // check we got name and Organisation and password
        $messages = array();
        foreach (array('username', 'password', 'organisation') as $required) {
            if (empty($params[$required])) {
                $messages[] = 'Missing parameter. Expected: '.$required;
            }
        }
        if (!empty($messages)) {
            throw new Sso_Exception_BadRequest(implode(';  ', $messages));
        }

        $connection = Sso_Model_Base::getConnection();

        // fetch the organisation
        try {
            $parent = $connection->getBase()->find(Sso_Model_Organisation::getDnFromId($params['organisation']));
            $dummy_var = $parent['sso'];
        } catch (Sso_Ldap_Exception $e) {
            Zend_Registry::get('log')->warn('User create: could not find organisation ' . $params['organisation']);
            throw new Sso_Exception_NotFound('Organisation not found');
        }

        if ( strlen($params['username']) > self::USERNAME_MAXLENGTH ) {
        	throw new Sso_Exception_NotFound('Please change your username. There is a maximum character limit of 255.');
        }

        $user = $parent->addChild('ssoUser', 'sso='.$params['username']);
        $user['ssoUsername'] = $params['username'];
        $user['ssoPassword'] = md5($params['password']);
        $user['ssoCreated']  = time();

		$saved = false;

        try {
            $saved = $user->save();
        } catch(Sso_Ldap_Exception $e) {
            Zend_Registry::get('log')->warn('User Save: '.$e->getMessage());
			if ($e->getCode() == Sso_Ldap_Wrapper::ERROR_ALREADY_EXISTS) {
				throw new Sso_Exception_AlreadyExists('Cannot Save User: already exists.');
			}
            throw new Sso_Exception_BadRequest('Cannot save user: '.$e->getMessage());
        }

        foreach ($params as $key => $value) {

            // ignore ZF routing vars
            if (in_array($key, $ignore_fields)) {
                continue;
            }

            // deal with expected vars and then attributes
            switch($key) {
				case 'role':
					// handle adding roles
					try {
						Sso_Model_Role::addRolesToEntry($user, $value);
					} catch (Sso_Ldap_Exception $e) {
                        Zend_Registry::get('log')->warn('User role add failed: '.$e->getMessage());
                        $user->delete(true, true);
                        throw new Sso_Exception_BadRequest('Cannot add user role: '.$e->getMessage());
                    }
                    break;
                default:
					// handle adding attributes
                    try {
                        $attribute = $user->addChild('ssoAttribute', 'sso='.$key);
                        $attribute['ssoValue'] = $value;
                        $attribute->save();
                    } catch (Sso_Ldap_Exception $e) {
                        Zend_Registry::get('log')->warn('User attribute add failed: '.$e->getMessage());
                        $user->delete(true, true);
                        throw new Sso_Exception_BadRequest('Cannot add user attribute '.$key);
                    }
                    break;
            }
        }
        $retval = Sso_Model_User::fetch($params['username']);
        return $retval;

    }

    /**
     * update
     *
     * @param array $params The parameters from the request
     *
     * @return a new version of the object
     * @throws Sso_Exception_BadRequest
     */
    public function update($params) {
        // these are the fields to ignore - ZF stuff plus name because it can't be changed
        $ignore_fields = parent::$ignore_fields;
        $ignore_fields[] = 'username';
	$ignore_fields[] = 'password';

        // get the LDAP link to the object
        $connection = Sso_Model_Base::getConnection();
        $entry = $connection->getBase()->search('sso='.$this->username)->get(0);
        $dn = $entry->getDn();

        // first delete all attribute and role children
        $this->_deleteAttributes($entry);

		if (!empty($params['password'])) {
			try {
				$entry['ssoPassword'] = md5($params['password']);
				$entry->save();
			} catch (Sso_Ldap_Exception $e) {
				Zend_Registry::get('log')->warn('User password save failed: '.$e->getMessage());
				throw new Sso_Exception_BadRequest('Cannot update password');
			}
		}

        // process the parameters
        foreach ($params as $key => $value) {
            // ignore the ZF routing stuff
            if (in_array($key, $ignore_fields)) {
                continue;
            }

            // ignore empty fields
            if (empty($value)) {
                continue;
            }

            // empty the var
            $attribute = false;

            // deal with parameters we are expecting, anything else falls through to the default clause
            switch($key) {
                case 'role':
		    try {
			$this->_deleteRoles($entry);
			$roles = Sso_Model_Role::addRolesToEntry($entry, $value);
		    } catch (Sso_Ldap_Exception $e) {
                        Zend_Registry::get('log')->warn('User role save failed: '.$e->getMessage());
                        throw new Sso_Exception_BadRequest('Cannot add user role: '.$e->getMessage());
                    }
			break;
		default:
                    // do we already have one of these?  If so, cool and if not, make one
                    try {
                        $search_string = 'sso='.$key.','.$dn;
                        $attribute = $connection->getBase()->find($search_string);

                        // is this real?  if not, following line throws exception
                        $dummy_var = $attribute['sso'];
                    } catch (Sso_Ldap_Exception $e) {
                        // no worries, this happens if the record doesn't exist, which it might not
                        $attribute = $entry->addChild('ssoAttribute', 'sso='.$key);
                    }

                    try {
                        $attribute['ssoValue'] = $value;
                        $attribute->save();
                        $this->$key = $value;
                    } catch (Sso_Ldap_Exception $e) {
                        Zend_Registry::get('log')->warn('User attribute save failed: '.$e->getMessage());
                        throw new Sso_Exception_BadRequest('Cannot add user attribute '.$key);
                    }
                    break;
            }
        }
    }

    /**
     * getUserFromEntry
     *
     * Get the Sso_Model_User object from this ldap entry
     *
     * @param Sso_Ldap_Entry $entry
     *
     * @return Sso_Model_User
     * @static
     */
    static public function getUserFromEntry(Sso_Ldap_Entry $entry) {
        $user = new Sso_Model_User;
        $user->id           = self::getNameFromDn($entry->getDn());
		$user->username     = $entry['ssoUsername'][0];
        $organisation       = $entry->getParent();
        $user->organisation = Sso_Model_Organisation::getIdFromDn($organisation->getDn());
        $user->createdOn    = $entry['ssoCreated'][0];

		// get attributes
		$attributes = new Sso_Ldap_FilterIterator_ObjectClass($entry->getChildren(), 'ssoAttribute');
		foreach ($attributes as $a) {
			$user->{$a['sso'][0]} = $a['ssoValue'][0];
		}

		// get roles
		$user->roles = (array)Sso_Model_Role::getRoleFromDn(Sso_Model_User::getDnFromName($user->username));

        return $user;
    }

    /**
     * getRights
     *
     * return resources/rights for this user (merged with the organisation's rights)
	 *
	 * @param string $resourceFilter Only return the rights for this user/resource [optional]
     *
     * @return array The Sso_Model_Right objects which represent rights for this user/company
	 * @throws Sso_Ldap_Exception
     */
    public function getRights($resourceFilter = null) {
		$rights = Sso_Model_Right::mergeRights(
			$this->getUserRights(),
			Sso_Model_Organisation::fetch($this->organisation)->getRights()
		);
		if (!empty($resourceFilter)) {
			foreach ($rights as $key => $right) {
				if ($right->resource != $resourceFilter) {
					unset($rights[$key]);
				}
			}
		}
		return $rights;
    }

	/**
     * getRUserights
     *
     * return resources/rights for this user (regardless the organisation's rights)
     *
	 * @return array The Sso_Model_Right objects which represent rights for this user
	 * @throws Sso_Ldap_Exception
	 */
	public function getUserRights() {
		$rights = array();
		foreach (Sso_Model_Role::getRoleFromDn(self::getDnFromName($this->username)) as $rolename) {
			$rights = array_merge($rights, Sso_Model_Role::fetch($rolename)->getRights());
		}
		return $rights;
	}

	/**
	 * Delete a single role from this user
	 *
	 * @param string $rolename
	 *
	 * @return boolean
	 * @throws Sso_Ldap_Exception
	 */
	public function deleteRole($rolename) {
		$entry = Sso_Model_Base::getConnection()->getBase()->search('sso='.$this->username)->get(0);
		return $this->_deleteRole($entry, $rolename);
	}
}
