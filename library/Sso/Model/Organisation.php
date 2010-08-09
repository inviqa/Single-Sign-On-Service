<?php
/**
 * Sso_Model_Organisation
 *
 * Organisation model
 *
 * @category  Sso_Service
 * @package   Sso
 * @copyright Copyright (c) 2009 Cable&Wireless
 * @author
 */
class Sso_Model_Organisation extends Sso_Model_Base
{
    /**
     * fetch 
     * 
     * @param integer $id the record to retrieve or leave blank for all top-level records
     * @param string $order the sort order for records
     * @param string $direction which way to sort records
     *
     * @return Sso_Model_Organisation|array The organisation or array of them
     * @throws Sso_Exception_NotFound
     */
    public static function fetch($id = null, $order = null, $direction = null) {
    	$log = Zend_Registry::get('log');
    	$log->info("in organisation fetch");
        $connection = Sso_Model_Base::getConnection();
        $log->info("in organisation fetch - got connection");
        if ($id) {
            // fetch this specific record
            $log->info("in organisation fetch - in if");
            $entry = $connection->getBase()->find(self::getDnFromId($id));
            if (!($entry instanceOf Sso_Ldap_Entry)) {
                throw new Sso_Exception_NotFound('Unknown Organisation "'.$id.'"');
            }
            try {
                $retval = Sso_Model_Organisation::getOrgFromEntry($entry);
            } catch (Sso_Ldap_Exception $e) {
                throw new Sso_Exception_NotFound('Unknown Organisation "'.$id.'"');
            }
        } else {
            // root org node
            $log->info("in organisation fetch - in else");
            $top = $connection->getBase()->find('ou=Users,ou=SSO');
            $log->info("in organisation fetch - got top");
            $retval = Sso_Model_Organisation::getOrgChildrenFromLdapEntry($top, $order, $direction);
        $log->info("in organisation fetch - got children");
        }
        return $retval;
    }

    /**
     * getOrgChildrenFromLdapEntry 
     *
     * return child organisations of this Sso_Ldap_Entry object
     * 
     * @param Sso_Ldap_Entry $entry
     * @param string $order the sort order for records
     * @param string $direction which way to sort records
     *
     * @return array Containing Sso_Model_Organisation objects
     */
    public static function getOrgChildrenFromLdapEntry(Sso_Ldap_Entry $entry, $order, $direction) {
        // handle sort vars
        $log = Zend_Registry::get('log');
        $log->info("In getOrgChildrenFromLdapEntry");
        $order = 'sso'; // only one option!

        if(strtolower($direction) == 'desc') {
            $direction = 'desc';
        } else {
            $direction = 'asc';
        }
$log->info("In getOrgChildrenFromLdapEntry - about to get children");
        $list = $entry->getChildren()->includeFilter(array("objectClass" => "ssoOrganisation"))->sort(array($order => $direction));
$log->info("In getOrgChildrenFromLdapEntry - got children");
        $retval = array();
        if ($list) {
            foreach ($list as $e) {
                $retval[] = Sso_Model_Organisation::getOrgFromEntry($e);
            }
        }
        $log->info("In getOrgChildrenFromLdapEntry about to return");
        return $retval;
    }

    /**
     * getUsersFromLdapEntry 
     *
     * return users belonging to this Organisation Sso_Ldap_Entry object
     * 
     * @param Sso_Ldap_Entry $entry
     *
     * @return array of Sso_Model_Organisation objects
     * @static
     */
    static public function getUsersFromLdapEntry(Sso_Ldap_Entry $entry, $order, $direction) {
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

        if(strtolower($direction) == 'desc') {
            $direction = 'desc';
        } else {
            $direction = 'asc';
        }

        $list = $entry->getChildren()->includeFilter(array("objectClass" => "ssoUser"))->sort(array($order => $direction));
        $retval = array();
        if ($list) {
            foreach ($list as $e) {
                $retval[] = Sso_Model_User::getUserFromEntry($e);
            }
        }
        return $retval;
    }
	
    /**
     * getIdFromDn
     *
     * get the identifier we'll use for this organisation from the dn of the object
     *
     * @param string $dn
     *
     * @return string the identifier
     * @static
     */
    static public function getIdFromDn($dn) {
//        Zend_Registry::get('log')->info($dn);
        $parts = split(',', $dn);
        $identifiers = array();
        foreach ($parts as $p) {
            if (substr($p,0,3) == 'sso') {
                $identifiers[] = substr($p, 4);
            }
        }
        $id = implode(':', $identifiers);
//        Zend_Registry::get('log')->info($id);
        return $id;
    }

    /**
     * getDnFromId
     *
     * take the id we use to get an item and find its real dn
     *
     * @param string $id
     *
     * @return string the dn
     * @static
     */
    static public function getDnFromId($id) {
		if (empty($id)) {
			throw new Sso_Exception_NotFound('Organisation not found (empty identifier)');
		}
        $parts = split(':', $id);
        $dn = '';
        foreach ($parts as $p) {
            $dn .= 'sso=' . $p .',';
        }
        $dn .= 'ou=Users,ou=SSO';
        return $dn;
    }

    /**
     * getChildren 
     *
     * retrives the LDAP entry for this record, finds the children, then returns an array of them
     * 
     * @param string $order the sort order for records
     * @param string $direction which way to sort records
     *
     * @return array The Sso_Model_Organisation entries
     * @throws Sso_Exception_NotFound
     */
    public function getChildren($order, $direction) {
        $connection = Sso_Model_Base::getConnection();
        $entry = $connection->getBase()->find(self::getDnFromId($this->id));
        if ($entry instanceOf Sso_Ldap_Entry) {
            return self::getOrgChildrenFromLdapEntry($entry, $order, $direction);
        }
        throw new Sso_Exception_NotFound('Organisation not known');
    }

    /**
     * getUsers
     *
     * return users in this organisation
     *
     * @return array The Sso_Model_User objects which represent users in this organisation
     * @throws Sso_Exception_NotFound
     */
    public function getUsers($order, $direction) {
        $connection = Sso_Model_Base::getConnection();
        $entry = $connection->getBase()->find(self::getDnFromId($this->id));
        if ($entry instanceOf Sso_Ldap_Entry) {
            return self::getUsersFromLdapEntry($entry, $order, $direction);
        }
        throw new Sso_Exception_NotFound('Organisation not known');
    }

    /**
     * delete 
     *
     * removes this record and all children
     * 
     * @return boolean true always
     */
    public function delete() {
        $connection = Sso_Model_Base::getConnection();
        try {
            $user = $connection->getBase()->find(self::getDnFromId($this->id));
            if ($user instanceOf Sso_Ldap_Entry) {
                $user->delete(true, true);
            }
        } catch(Sso_Ldap_Exception $e) {
            // this case doesn't matter, we don't care
            Zend_Registry::get('log')->info('Organisation delete: '.$e->getMessage());
        }
        return true;
    }

    /**
     * update 
     *
     * store properties and attributes as needed.
     * Only supplied attributes will be included, any not supplied will be
     * removed if they already exist
     * 
     * @param array $params the request parameteres
     *
     * @throws Sso_Exception_BadRequest
     */
    public function update($params) {
        // these are the fields to ignore - ZF stuff plus name because it can't be changed
        $ignore_fields = parent::$ignore_fields;
        $ignore_fields[] = 'name';

        // get the LDAP link to the object
        $connection = Sso_Model_Base::getConnection();
        $dn         = self::getDnFromId($this->id);
        $entry      = $connection->getBase()->find($dn);

        // first delete all attribute and role children
        $this->_deleteAttributes($entry);
		$this->_deleteRoles($entry);

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
						Sso_Model_Role::addRolesToEntry($entry, $value);
					} catch (Sso_Ldap_Exception $e) {
                        Zend_Registry::get('log')->warn('Organisation role save failed: '.$e->getMessage());
                        throw new Sso_Exception_BadRequest('Cannot add organisation role: '.$e->getMessage());
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
                        Zend_Registry::get('log')->warn('Organisation attribute save failed: '.$e->getMessage());
                        throw new Sso_Exception_BadRequest('Cannot add organisation attribute '.$key);
                    }
                    break;
            }
        }
    }

    /**
     * create 
     * 
     * @param string $parentOrg    the identifier of the organisation object to make this a child of
     * @param array  $params the incoming form parameters from the request
     *
     * @return Sso_Model_Organisation the new object
     * @static
     */
    static public function create($parentOrg, $params) {
        // these are the fields to ignore
        $ignore_fields = parent::$ignore_fields;
        
        // check we got name at least
        if (empty($params['name'])) {
            throw new Sso_Exception_BadRequest('Missing parameter. Expected: name');
        }

        $connection = Sso_Model_Base::getConnection();

		// try to get the parent
		if (!empty($parentOrg)) {
			try {
				$parent = $connection->getBase()->find(self::getDnFromId($parentOrg));
				$dummy_var = $parent['sso'];
			} catch (Sso_Ldap_Exception $e) {
				if ($e->getCode() == Sso_Ldap_Wrapper::ERROR_NO_SUCH_OBJECT) {
					throw new Sso_Exception_NotFound('Parent organisation "'.$parentOrg.'" not found');
				} else {
					throw $e;
				}
			} catch (Sso_Exception_NotFound $e) {
				throw new Sso_Exception_NotFound('Parent organisation "'.$parentOrg.'" not found');
			}
		} else {
			// get the root of all organisations
			$parent = $connection->getBase()->search('ou=Users')->get(0);
		}

        $organisation_entry = $parent->addChild('ssoOrganisation', 'sso='.$params['name']);
        $organisation_entry['ssoName'] = $params['name'];

        try {
            $organisation_entry->save();
        } catch(Sso_Ldap_Exception $e) {
            Zend_Registry::get('log')->warn('Organisation Save: '.$e->getMessage());
			if ($e->getCode() == Sso_Ldap_Wrapper::ERROR_ALREADY_EXISTS) {
				throw new Sso_Exception_AlreadyExists('Cannot Save Organisation "'.$params['name'].'": already exists.');
			}
            throw new Sso_Exception_BadRequest('Cannot Save Organisation: '.$e->getMessage());
        }

        foreach ($params as $key => $value) {
            // ignore ZF routing vars
            if (in_array($key, $ignore_fields)) {
                continue;
            }

            // deal with expected vars and then attributes
            switch($key) {
                case 'name':
                    $organisation_entry['ssoName'] = $value;
                    break;
				case 'role':
					// handle adding roles
                    try {
						Sso_Model_Role::addRolesToEntry($organisation_entry, $value);
                    } catch (Sso_Ldap_Exception $e) {
                        Zend_Registry::get('log')->warn('Organisation role add failed: '.$e->getMessage());
                        $organisation_entry->delete(true, true);
                        throw new Sso_Exception_BadRequest('Cannot add organisation role: '.$e->getMessage());
                    }
                    break;
                default:
                    // handle adding attributes
                    try {
                        $attribute = $organisation_entry->addChild('ssoAttribute', 'sso='.$key);
                        $attribute['ssoValue'] = $value;
                        $attribute->save();
                    } catch (Sso_Ldap_Exception $e) {
                        Zend_Registry::get('log')->warn('Organisation attribute add failed: '.$e->getMessage());
                        $organisation_entry->delete(true, true);
                        throw new Sso_Exception_BadRequest('Cannot add organisation attribute '.$key);
                    }
                    break;
            }
        }

        $retval = self::fetch(self::getIdFromDn($organisation_entry->getDn()));
        return $retval;

    }

    /**
     * getOrgFromEntry 
     *
     * Get the Sso_Model_Organisation object from this ldap entry
     * 
     * @param Sso_Ldap_Entry $entry
     *
     * @return Sso_Model_Organisation
     * @static
     */
    static public function getOrgFromEntry(Sso_Ldap_Entry $entry) {
        $organisation = new self;
        $organisation->name   = $entry['sso'][0];
        $organisation->id     = self::getIdFromDn($entry->getDn());
        $organisation->parent = self::getIdFromDn($entry->getParent()->getDn());

        // get attributes
        //$attributes = $entry->getChildren()->includeFilter(array('objectClass' => 'ssoAttribute'));
		$attributes = new Sso_Ldap_FilterIterator_ObjectClass($entry->getChildren(), 'ssoAttribute');
		foreach ($attributes as $a) {
			$organisation->{$a['sso'][0]} = $a['ssoValue'][0];
		}

		// get roles
		$organisation->roles = (array)Sso_Model_Role::getRoleFromDn(Sso_Model_Organisation::getDnFromId($organisation->id));
        
        return $organisation;
    }

	/**
	 * Get the rights for this organisation
	 *
	 * @return array
	 */
	public function getRights() {
		$rights = array();
		foreach (Sso_Model_Role::getRoleFromDn(Sso_Model_Organisation::getDnFromId($this->id)) as $rolename) {
			$roles = Sso_Model_Role::fetch($rolename);
			if (!($roles instanceof Sso_Model_Role)) {
				foreach($roles AS $role) {
					$rights = array_merge($rights, $role->getRights());
				}
			} else {
				$rights = array_merge($rights, Sso_Model_Role::fetch($rolename)->getRights());
			}
		}
		return $rights;
	}

	/**
	 * Delete a single role from this organisation
	 *
	 * @param string $rolename
	 *
	 * @return boolean
	 * @throws Sso_Ldap_Exception
	 */
	public function deleteRole($rolename) {
		$entry = Sso_Model_Base::getConnection()->getBase()->find(self::getDnFromId($this->id));
		return $this->_deleteRole($entry, $rolename);
	}
}
