<?php
/**
 * Sso_Model_Resource
 *
 * Resource model
 *
 * @category  Sso_Service
 * @package   Sso
 * @copyright Copyright (c) 2009 Cable&Wireless
 * @author
 */
class Sso_Model_Resource extends Sso_Model_Base
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * create 
     *
     * takes input parameters and attempts to create ldap records and return an object
     *
     * @param string $parentRes
     * @param array  $params The parameters from the request
     *
     * @return Sso_Model_Resource representing the created ldap record
     * @static
     */
    static public function create($parentRes, $params) {
		//die('create '.$parent.' '.implode(', ', $params));
        if (empty($params['name'])) {
            throw new Sso_Exception_BadRequest('Missing Parameter.  Expected: name');
        }

        $connection = Sso_Model_Base::getConnection();

        // try to get the parent
		if (!empty($parentRes)) {
			try {
				$parent_obj = $connection->getBase()->find(self::getDnFromId($parentRes));
				$dummy_var = $parent_obj['sso'];
			} catch (Sso_Ldap_Exception $e) {
				if ($e->getCode() == Sso_Ldap_Wrapper::ERROR_NO_SUCH_OBJECT) {
					throw new Sso_Exception_NotFound('Parent resource "'.$parentRes.'" not found');
				} else {
					throw $e;
				}
			}
		} else {
			// get the root of all resources
			$parent_obj = $connection->getBase()->search('ou=Resources')->get(0);
		}

        $resource_entry = $parent_obj->addChild('ssoResource', 'sso='.$params['name']);
        $resource_entry['ssoName'] = $params['name'];

        try {
            $resource_entry->save();
        } catch(Sso_Ldap_Exception $e) {
            Zend_Registry::get('log')->warn('Resource Save: '.$e->getMessage());
			if ($e->getCode() == Sso_Ldap_Wrapper::ERROR_ALREADY_EXISTS) {
				throw new Sso_Exception_AlreadyExists('Cannot Save Resource: already exists.');
			}
            $resource_entry->delete(true, true);
            throw new Sso_Exception_BadRequest('Cannot Save Resource: '.$e->getMessage(). ' ('.$e->getCode().')');
        }
        $retval = self::fetch(self::getIdFromDn($resource_entry->getDn()), null, null);
        return $retval;
    }

    /**
     * fetch 
     * 
     * @param int $id the record to retrieve or leave blank for all
     *
     * @return Sso_Model_Resource|array The resource or array of them
     */
    static public function fetch($id = null, $order = null, $direction = null) {
        $connection = Sso_Model_Base::getConnection();

        if ($id) {
            // fetch this specifc record
            $entry = $connection->getBase()->find(self::getDnFromId($id));
            if (!($entry instanceOf Sso_Ldap_Entry)) {
                throw new Sso_Exception_NotFound('Unknown resource');
            }
			try {
				$resource = self::getResourceFromEntry($entry);
			} catch (Sso_Ldap_Exception $e) {
				if ($e->getCode() == Sso_Ldap_Wrapper::ERROR_NO_SUCH_OBJECT) {
					throw new Sso_Exception_NotFound($id);
				} else {
					throw $e;
				}
			}
            
            $retval = $resource;
        } else {
            // root org node
            $top = $connection->getBase()->find('ou=Resources,ou=SSO');
            $retval = self::getResourceChildrenFromLdapEntry($top, $order, $direction);
        }
        return $retval;
    }

    /**
     * getIdFromDn 
     * 
     * get the identifier we'll use for this resource from the dn of the object
     *
     * @param string $dn 
     * @static
     * @access public
     * @return string the identifier
     */
    static public function getIdFromDn($dn) {
        $parts = split(',', $dn);
        $identifiers = array();
        foreach ($parts as $p) {
            if (substr($p,0,3) == 'sso') {
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
        $parts = split(':', $id);
        $dn = '';
        foreach ($parts as $p) {
            $dn .= 'sso=' . $p .',';
        }
        $dn .= 'ou=Resources,ou=SSO';
        return $dn;
    }

    /**
     * getResourceChildrenFromLdapEntry 
     *
     * Find and return child resources 
     * 
     * @param Sso_Ldap_Entry $entry 
     * @param string $order What to sort these results by
     * @param string $direction which way to sort
     * @static
     * @access public
     * @return array of Sso_Resource_Models of the results found
     */
    static public function getResourceChildrenFromLdapEntry(Sso_Ldap_Entry $entry, $order, $direction) {
        // handle sort vars
        $order = 'sso'; // only one option

        if (strtolower($direction) == 'desc') {
            $direction = 'desc';
        } else {
            $direction = 'asc';
        }

        $list = $entry->getChildren()->includeFilter(array('objectClass' => 'ssoResource'))->sort(array($order => $direction));
        $retval = array();
        if ($list) {
            foreach ($list as $e) {
                $retval[] = self::getResourcefromEntry($e);
            }
        }
        return $retval;
    }

    /**
     * getChildren 
     *
     * grab the ldap entry and the call getresourcechildrenfromldapentry method to get children
     * 
     * @access public
     * @param string $order What to sort these results by
     * @param string $direction which way to sort
     * @return array of Sso_Resource_Models
     */
    public function getChildren($order, $direction) {
        $connection = Sso_Model_Base::getConnection();

        $entry = $connection->getBase()->find(self::getDnFromId($this->id));
        if ($entry instanceOf Sso_Ldap_Entry) {
            return self::getResourceChildrenFromLdapEntry($entry, $order, $direction);
        }
        throw new Sso_Exception_NotFound('Resource not known');
    }

    /**
     * delete 
     *
     * delete this resource and all its children
     * 
     * @access public
     * @return boolean always true
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
            Zend_Registry::get('log')->info('Resource delete exception: '.$e->getMessage());
        }
        return true;

    }

    /**
     * update 
     * 
     * @param array $params the parameters that came in with the request
     * 
     * @return Sso_Model_Resource the resource model for the changed object
     */
    public function update($params) {
        // these are the fields to ignore - ZF stuff plus name because it can't be changed
        $ignore_fields = parent::$ignore_fields;
        $ignore_fields[] = 'name';

        // get the LDAP link to the object
        $connection = Sso_Model_Base::getConnection();
        $dn         = self::getDnFromId($this->id);
        $entry = $connection->getBase()->find($dn);

        // first delete all attribute children
        foreach ($entry->getChildren() as $child) {
            // only if its an ssoAttribute
            try {
                if (isset($child['ssoValue'])) {
                    $child->delete();
                    $this->$child['sso'][0] = null;
                }
            } catch (Sso_Ldap_Exception $e) {
                // this will happen if we got some other kind of object
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
                        Zend_Registry::get('log')->warn('Resource attribute save failed: '.$e->getMessage());
                        throw new Sso_Exception_BadRequest('Cannot add resource attribute '.$key);
                    }
                    break;
            }
        }
    }

    /**
     * getResourceFromEntry 
     *
     * Get the Sso_Model_Resource object from this ldap entry
     * 
     * @param Sso_LdapEntry $entry
     *
     * @return Sso_Model_Resource
     * @static
     */
    static public function getResourceFromEntry($entry) {
        $resource = new Sso_Model_Resource;
        $resource->name   = $entry['sso'][0];
        $resource->id     = self::getIdFromDn($entry->getDn());
        $resource->parent = self::getIdFromDn($entry->getParent()->getDn());

        // get attributes
        $attributes = $entry->getChildren()->includeFilter(array("objectClass" => "ssoAttribute"));
        if ($attributes) {
            foreach ($attributes as $a) {
                $resource->{$a['sso'][0]} = $a['ssoValue'][0];
            }

        }

        return $resource;

    }
}
