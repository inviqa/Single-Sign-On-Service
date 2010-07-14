<?php
/**
 * Sso_Model_Base
 *
 * The class for models to inherit from
 *
 * @category  Sso_Service
 * @package   Sso
 * @copyright Copyright (c) 2009 Cable&Wireless
 * @author
 */
class Sso_Model_Base
{
	/**
	 * Configuration
	 *
	 * @var Zend_Config
	 */
	static private $config;

    /**
     * Ignore Fields
     *
     * The fields which have special meanings and shouldn't be evaluated as arbitrary attributes
     *
     * @var array
     */
    static protected $ignore_fields = array(
		'controller',
		'identifier',
		'action',
		'module',
		'offset',
		'limit',
		'order',
		'direction',
	);

    /**
     * delete 
     *
     * Over-ridden by child classes, but here for all the mocks to save duplication
     * 
     * @return boolean always returns true for idempotency
     */
    public function delete() {
        // delete the record, return true regardless
        return true;
    }
    
    /**
     * Statically store the config object 
     *
     * @param Zend_Config_Abstract $config
     */
    static public function setConfig(Zend_Config $config) {
    	self::$config = $config;
    }

    /**
     * Statically return the config object
     *
     */
    static public function getConfig() {
        return self::$config;
    }

    /**
     * connect to the data store
     *
     * @return Sso_Ldap_Connection
     * @access protected
     */
    static protected function getConnection() {
    	$connection = new Sso_Ldap_Connection(self::$config->ldap->host, self::$config->ldap->dc);
        $connection->setBind(self::$config->ldap->user, self::$config->ldap->pass);

        if (isset(self::$config->memcache)) {
	        $m = new Sso_Cache_Backend_Memcache();
	        foreach (self::$config->memcache->host as $host) {
	        	$m->addServer($host);
	        }
	        $cache = new Sso_Cache_Base($m);
	        $connection->addCacheBackend($cache);
        }
        
        return $connection;
    }

	/**
	 * Delete all the attributes for the given organisation entry
	 *
	 * @param Sso_Ldap_Entry $entry
	 */
	protected function _deleteAttributes(Sso_Ldap_Entry $entry) {
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
	}

	/**
	 * Delete all the roles for this organisation/user
	 *
	 * @param Sso_Ldap_Entry $entry
	 *
	 * @return boolean
	 */
	protected function _deleteRoles(Sso_Ldap_Entry $entry) {
		if ($entry->hasAttribute('ssoRole')) {
			$entry['ssoRole'] = array();
			return $entry->save();
		}
		return true;
	}

	/**
	 * Delete a specific role for this organisation/user
	 *
	 * @param Sso_Ldap_Entry $entry
	 * @param string         $rolename
	 *
	 * @return boolean
	 * @throws Sso_Exception_NotFound
	 */
	protected function _deleteRole(Sso_Ldap_Entry $entry, $rolename) {
		if ($entry->hasAttribute('ssoRole')) {
			$roles = $entry->getAttribute('ssoRole');
			if ($roles) {
				$idx = array_search(Sso_Model_Role::getDnFromId($rolename), $roles);
				if (false === $idx) {
					throw new Sso_Exception_NotFound('Role "'.$rolename.'" not found for this entry');
				}
				unset($roles[$idx]);
				$entry['ssoRole'] = array_values($roles);
				return $entry->save();
			}
		}
		return true;
	}
}
