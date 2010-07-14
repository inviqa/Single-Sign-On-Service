<?php
/**
 * Sso_Model_Role
 *
 * Role model
 *
 * @category  Sso_Service
 * @package   Sso
 * @copyright Copyright (c) 2009 Cable&Wireless
 * @author
 */
class Sso_Model_Role extends Sso_Model_Base
{
    /**
     * fetch 
     * 
     * @param int $id the record to retrieve or leave blank for all top-level records
     * @param string $order which field to sort by
     * @param string $direction which way to sort the records
     *
     * @return Sso_Model_Role|array The role or array of them
     * @throws Sso_Exception_NotFound
     */
    static public function fetch($id = null, $order = null, $direction = null) {
        $connection = Sso_Model_Base::getConnection();
        if ($id) {
            // fetch this specifc record
			$entry = $connection->getBase()->find(Sso_Model_Role::getDnFromId($id));
			if (!($entry instanceOf Sso_Ldap_Entry)) {
                throw new Sso_Exception_NotFound('Unknown role "'.$id.'"');
            }
            try {
                $retval = Sso_Model_Role::getRoleFromEntry($entry);
            } catch (Sso_Ldap_Exception $e) {
                throw new Sso_Exception_NotFound('Unknown role "'.$id.'"');
            }
        } else {
            // root org node
            $top = $connection->getBase()->find('ou=Permissions,ou=SSO');
            $retval = Sso_Model_Role::getRoleChildrenFromLdapEntry($top, $order, $direction);
        }
        return $retval;
    }

	/**
	 * Add new roles to this organisation/user
	 *
	 * @param Sso_Ldap_Entry $entry parent object
	 * @param string|array   $roles role name(s)
	 *
	 * @static
	 * @throws Sso_Exception_NotFound
	 * @throws Sso_Ldap_Exception
	 */
	static public function addRolesToEntry($entry, $roles) {
		if (is_string($roles)) {
			$roles = array($roles);
		}
		foreach ($roles as $rolename) {
			// first, check if the role exists
			self::fetch($rolename);
			// then add it
			$entry['ssoRole:'] = Sso_Model_Role::getDnFromId($rolename);
		}
		return $entry->save();
	}

    /**
     * getRoleChildrenFromLdapEntry 
     *
     * return child roles of this Sso_Ldap_Entry object
     * 
     * @param Sso_Ldap_Entry $entry
     * @param string $order which field to sort by
     * @param string $direction which way to sort the records
     *
     * @return array Containing Sso_Model_Role objects
     */
    public static function getRoleChildrenFromLdapEntry(Sso_Ldap_Entry $entry, $order, $direction) {
        $retval = array();
            
        // handle sort vars
        $order = 'sso'; // only one option

        if(strtolower($direction) == 'desc') {
            $direction = 'desc';
        } else {
            $direction = 'asc';
        }

        $roles = $entry->getChildren()->includeFilter(array('objectClass' => 'ssoRole'))->sort(array($order => $direction));
		foreach ($roles as $e) {
			$retval[] = Sso_Model_Role::getRoleFromEntry($e);
        }
        return $retval;
    }

    /**
     * getIdFromDn 
     * 
     * get the identifier we'll use for this role from the dn of the object
     *
     * @param string $dn
     *
     * @return string the identifier
     * @access static
     */
    static public function getIdFromDn($dn) {
        $parts = split(',', $dn);
        $identifiers = array();
        foreach ($parts as $p) {
            if (0 === strncmp($p, 'sso', 3)) {
                $identifiers[] = substr($p, 4);
            }
        }
        $id = implode(':', $identifiers);
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
			throw new Sso_Exception_NotFound('Role not found (empty identifier)');
		}
        $parts = split(':', $id);
        $dn = 'sso=' . array_shift($parts);
        $base = "";
        if(count($parts)) {
        	$baseParts = array();
	        foreach ($parts as $p) {
	            $baseParts[] = 'sso=' . $p;
	        }
	         $base = implode(',', $baseParts) . ',';
        }
		$connection = Sso_Model_Base::getConnection();
		$entry = $connection->getBase()->search($dn, $base . 'ou=Permissions,ou=SSO')->get(0);
		if (!($entry instanceOf Sso_Ldap_Entry)) {
			throw new Sso_Exception_NotFound('Role not found ('.$id.')');
		}
		return $entry->getDn();
    }

    /**
     * getChildren 
     *
     * retrives the LDAP entry for this record, finds the children of the same type,
	 * then returns an array of them
     * 
     * @param string $order which field to sort by
     * @param string $direction which way to sort the records
     *
     * @return array The Sso_Model_Role entries
     * @throws Sso_Exception_NotFound
     */
    public function getChildren($order, $direction) {
        $connection = Sso_Model_Base::getConnection();

        $entry = $connection->getBase()->find(Sso_Model_Role::getDnFromId($this->id));
        if ($entry instanceOf Sso_Ldap_Entry) {
            return Sso_Model_Role::getRoleChildrenFromLdapEntry($entry, $order, $direction);
        }

        throw new Sso_Exception_NotFound('Role not known');
    }

    /**
     * getRights
     *
     * return rights for this role
     *
     * @return array The Sso_Model_Right objects which represent rights for this role
     * @throws Sso_Exception_NotFound
     */
	public function getRights() {
		$connection = Sso_Model_Base::getConnection();
		$entry = $connection->getBase()->find(self::getDnFromId($this->id));
		if ($entry instanceOf Sso_Ldap_Entry) {
			$rights = array();
			try {
				$rights = $this->getParent()->getRights();
			} catch(Sso_Exception_NotFound $e) {}
			return array_merge($rights, Sso_Model_Right::getRightChildrenFromLdapEntry($entry));
		}
		throw new Sso_Exception_NotFound('Role not known');
	}

	/**
	 * getParent
	 *
	 * return parent for this role
	 *
	 * @return Sso_Model_Role The parent Sso_Model_Role object
	 * @throws Sso_Exception_NotFound
	 */
	public function getParent() {
		$parts = explode(':', $this->id);
		array_shift($parts);
		$parent = self::fetch(implode(':', $parts));
		if ($parent instanceof Sso_Model_Role) {
			return $parent;
		}
		throw new Sso_Exception_NotFound('Role not known');
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
            $role = $connection->getBase()->find(Sso_Model_Role::getDnFromId($this->id));
            if ($role instanceOf Sso_Ldap_Entry) {
                $role->delete(true, true);
            }
        } catch(Sso_Ldap_Exception $e) {
            // this case doesn't matter, we don't care
            Zend_Registry::get('log')->info('Role delete: '.$e->getMessage());
        }
        return true;
    }

    /**
     * update 
     *
     * store properties and attributes as needed.
     * Only supplied attributes will be included, any not supplied will be removed
     * if they already exist
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
	    $dn         = Sso_Model_Role::getDnFromId($this->id);
	    $entry = $connection->getBase()->find($dn);

	    $children = $entry->getChildren();

	    // first check we have matching elements
	    if (isset($params['right']) && isset($params['resource']) && count($params['right']) != count($params['resource'])) {
		    throw new Sso_Exception_BadRequest('Rights and Resources must be equal in size');
	    }

	    $objectClasses = array('ssoAttribute');
	    if(isset($params['right'])) {
		    $objectClasses[] = 'ssoRight';
	    }

	    // first delete all attribute and right children
	    foreach ($objectClasses as $objectClass) {
		    $filter = new Sso_Ldap_FilterIterator_ObjectClass($children, $objectClass);
		    $deleted = array();
		    //problem is that the child object is being left empty.
		    try {
			foreach ($filter as $child) {
				try {
				    // delete the actual object
				    $deleted[] = $child->getDn();
				    $ret = $child->delete();
			 	} catch (Sso_Ldap_Exception $e) {
				    // this will happen if the record isn't there but we would delete it anyway
				}
			}
		    } catch (Sso_Ldap_Exception $e) {
			if ($e->getCode() == Sso_Ldap_Wrapper::ERROR_NO_SUCH_OBJECT) {
			    // no worries... no children of this objectClass
			    continue;
			} else {
			    throw $e;
			}
		    }

		    /*
		     * Remove deleted entries from the iterator before further loops
		     */
		    foreach($deleted as $dn) {
			$children->removeDn($dn);
		    }
	    }

	    $messages = array();
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
			    case 'grant':
			    case 'resource':
				    // do nothing - these vars just match up with rights and we only want to do this once
				    break;
			    case 'right':
				    $size = count($params['right']);

				    for ($i=0; $i<$size; ++$i) {
					    $resourceException = null;
					    try {
						    $resource = Sso_Model_Resource::fetch($params['resource'][$i]);
					    } catch (Sso_Ldap_Exception $e) {
						    if ($e->getCode() == Sso_Ldap_Wrapper::ERROR_NO_SUCH_OBJECT) {
							    $resourceException = new Sso_Exception_NotFound('Unknown resource "'.$params['resource'][$i].'"');
						    } else {
							    $resourceException = $e;
						    }
					    }

					    try {
						    // Hack to avoid the nested exception which was triggering a bug with PHP 5.2.11
						    if($resourceException) {
							    throw $resourceException;
						    }
						    $ssoGrant       = (isset($params['grant'][$i]) && $params['grant'][$i] == 'true') ? 'true' : 'false';
						    $ssoResource    = Sso_Model_Resource::getDnFromId($resource->id);
						    $ssoDescription = $params['right'][$i];
						    $new_identifier = substr(md5($ssoResource.'++++++'.$ssoDescription.'+++++'.$ssoGrant), 0, 6);
						    $right = $entry->addChild('ssoRight', 'sso='.$new_identifier);

						    $right['ssoResource']    = $ssoResource;
						    $right['ssoDescription'] = $ssoDescription;
						    $right['ssoGrant']       = $ssoGrant;
						    $right->save();
					    } catch (Sso_Ldap_Exception $e) {
						    Zend_Registry::get('log')->warn('cannot save right '.$params['right'][$i].' for resource '.$params['resource'][$i] . ' with response ' . $e->getMessage());
						    $messages[] = $params['resource'][$i] . ' ('.$e->getMessage().')';
					    }
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
					    Zend_Registry::get('log')->warn('Role attribute save failed: '.$e->getMessage());
					    $messages[] = 'Cannot add role attribute ' . $key;
				    }
			    break;
		    }
	    }

	    if (count($messages) > 0) {
		    throw new Sso_Exception_BadRequest('Could not save role rights for ' . implode(',', $messages));
	    }
    }

    /**
     * create 
     * 
     * @param string $org    the identifier of the role object to make this a child of
     * @param array  $params the incoming form parameters from the request
     *
     * @return Sso_Model_Role the new object
     * @throws Sso_Exception_BadRequest
     * @static
     */
    static public function create($parentRole, $params) {
        // these are the fields to ignore
        $ignore_fields = parent::$ignore_fields;
        
        // check we got name at least
        if (empty($params['name'])) {
            throw new Sso_Exception_BadRequest('Missing parameter. Expected: name');
        }

        $connection = Sso_Model_Base::getConnection();

        // try to get the parent
		if (!empty($parentRole)) {
			try {
				$parent = $connection->getBase()->find(self::getDnFromId($parentRole));
				$dummy_var = $parent['sso'];
			} catch (Sso_Ldap_Exception $e) {
				if ($e->getCode() == Sso_Ldap_Wrapper::ERROR_NO_SUCH_OBJECT) {
					throw new Sso_Exception_NotFound('Parent role "'.$parentRole.'" not found');
				} else {
					throw $e;
				}
			} catch (Sso_Exception_NotFound $e) {
				throw new Sso_Exception_NotFound('Parent role "'.$parentRole.'" not found');
			}
		} else {
			// get the root of all resources
			$parent = $connection->getBase()->search('ou=Permissions')->get(0);
		}

        $role_entry = $parent->addChild('ssoRole', 'sso='.$params['name']);
        $role_entry['ssoName'] = $params['name'];

        try {
            $role_entry->save();
        } catch(Sso_Ldap_Exception $e) {
            Zend_Registry::get('log')->warn('Role Save: '.$e->getMessage());
			if ($e->getCode() == Sso_Ldap_Wrapper::ERROR_ALREADY_EXISTS) {
				throw new Sso_Exception_AlreadyExists('Cannot Save Role: already exists.');
			}
            throw new Sso_Exception_BadRequest('Cannot Save Role: '.$e->getMessage());
        }

        foreach ($params as $key => $value) {
            // ignore ZF routing vars
            if (in_array($key, $ignore_fields)) {
                continue;
            }

            // deal with expected vars and then attributes

	    //by pass any blank rights.
	    if ($key == 'right' && strlen($value) < 1) continue;

            switch($key) {
                case 'name':
                    $role_entry['ssoName'] = $value;
                    break;
                case 'resource':
                case 'grant':
                    // do nothing - this var just matches up with rights and we only want to do this once
                    break;
                case 'right':
                    // first check we have matching elements
                    if (count($params['right']) != count($params['resource'])) {
                        throw new Sso_Exception_BadRequest('Rights and Resources must be equal in size');
                    }
                    $size = count($params['right']);
                    for ($i=0; $i<$size; ++$i) {
                        $new_identifier = substr(md5(microtime() . $params['resource'][$i]),0,5);
                        try {
                            $right = $role_entry->addChild('ssoRight', 'sso='.$new_identifier);
                            $resource = Sso_Model_Resource::fetch($params['resource'][$i]);
                            $right['ssoResource']    = Sso_Model_Resource::getDnFromId($resource->id);
                            $right['ssoDescription'] = $params['right'][$i];
                            if (isset($params['grant'][$i]) && $params['grant'][$i] == 'true') {
                                $grant = 'true';
                            } else {
                                $grant = 'false';
                            }

                            $right['ssoGrant'] = $grant;
                            $right->save();
                        } catch (Sso_Ldap_Exception $e) {
                            Zend_Registry::get('log')->warn('cannot save right '.$params['right'][$i].' for resource '.$params['resource'][$i] . ' with response ' . $e->getMessage());
                            $messages[] = $params['resource'][$i];
                        }
                    }

                    if (isset($messages)) {
                        throw new Sso_Exception_BadRequest('Could not save role rights for ' . implode(',', $messages));
                    }
                    break;

                default:
                    // handle adding attributes
                    try {
                        $attribute = $role_entry->addChild('ssoAttribute','sso='.$key);
                        $attribute['ssoValue'] = $value;
                        $attribute->save();
                    } catch (Sso_Ldap_Exception $e) {
                        Zend_Registry::get('log')->warn('Role attribute add failed: '.$e->getMessage());
                        $role_entry->delete(true,true);
                        throw new Sso_Exception_BadRequest('Cannot add role attribute '.$key);
                    }
                    break;
            }
        }

        $retval = Sso_Model_Role::fetch($params['name']);
        return $retval;
    }

    /**
     * getRoleFromEntry 
     *
     * Get the Sso_Model_Role object from this ldap entry
     * 
     * @param Sso_LdapEntry $entry
     *
     * @return Sso_Model_Role
     * @static
     */
    static public function getRoleFromEntry($entry) {
        $role = new Sso_Model_Role();
        $role->name   = $entry['sso'][0];
        $role->id     = Sso_Model_Role::getIdFromDn($entry->getDn());
        $role->parent = Sso_Model_Role::getIdFromDn($entry->getParent()->getDn());

        // get attributes
		$attributes = new Sso_Ldap_FilterIterator_ObjectClass($entry->getChildren(), 'ssoAttribute');
        foreach ($attributes as $a) {
			$role->{$a['sso'][0]} = $a['ssoValue'][0];
        }

        // get permissions
        $permissions = new Sso_Ldap_FilterIterator_ObjectClass($entry->getChildren(), 'ssoRight');
		foreach ($permissions as $p) {
			$right = Sso_Model_Right::getRightFromEntry($p);
			$role->permissions[] = $right;
		}
        
        return $role;
    }

    /**
     * getRoleFromDn
     *
     * return roles for the current entry
     *
     * @return array The Sso_Model_Role objects which represent roles
	 *               in this organisation or for this user
     * @throws Sso_Exception_NotFound
     */
    static public function getRoleFromDn($dn) {
		$connection = Sso_Model_Base::getConnection();
		$entry = $connection->getBase()->find($dn);
		if ($entry instanceOf Sso_Ldap_Entry) {
			if ($entry->hasAttribute('ssoRole')) {
				$role_dns = $entry->getAttribute('ssoRole');
				$roles = array();
				foreach ($role_dns as $role_dn) {
					$roles[] = Sso_Model_Role::getIdFromDn($role_dn);
				}
				return $roles;
			}
			return array();
			//return Sso_Model_Role::getRoleChildrenFromLdapEntry($entry);
		}
		throw new Sso_Exception_NotFound('Entry not known');
    }
}
