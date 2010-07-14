<?php
/**
 * @package sso
 */

/**
 * Sso_Ldap_Entry
 * 
 * Provides an interface to an individual LDAP entry.
 *
 * @package sso
 */
final class Sso_Ldap_Entry implements ArrayAccess	
{
	/**
	 * @var Sso_Ldap_Connection
	 */
	private $_conn;

    /**
     * @var string
     */
	private $_dn;

	/**
	 * @var Sso_Ldap_Entry
	 */
	private $_parent;
	
	/**
	 * @var Sso_Ldap_EntryIterator
	 */
	private $_children;

    /**
     * @var array
     */
	private $_search_cache = array();

	/**
	 * New object?
	 * @var boolean
	 */
	private $_new = false;

	/**
     * @var array
     */
	private $_attributes = array();
	
	/**
	 * __construct
	 * 
	 * Create a new Sso_Ldap_Entry instance providing an Sso_Ldap_Connection and $dn. If you are creating a new
	 * entry pass boolean true as the third parameter.
	 * 
	 * @param Sso_Ldap_Connection $conn
	 * @param string  $dn
	 * @param boolean $new
	 */
	public function __construct(Sso_Ldap_Connection $conn, $dn = null, $new = false) {
		$this->_conn = $conn;
		if (isset($dn)) {
            $this->setDn($dn);
        }
		$this->_new = $new;
	}
	
	/**
	 * Clear the cache after an update action
	 */
	private function _clearCache() {
		$this->_search_cache = array();
	}

	/**
	 * addChild
	 * 
	 * Create a new entry beneath the current one specifying the object $class (ie. 'ssoUser') and the 
	 * $id of the new entry (ie. 'sso=myname'). Will return the new node so you can add attributes and
	 * you can then save this by calling $node->save(). 
	 * 
	 * @param string $class
	 * @param string $id
     *
	 * @return Sso_Ldap_Entry
	 */
	public function addChild($class, $id) {
		if ($this->_new) {
            return false;
        }
		$dn = "{$id},{$this->_dn}";
		$e = new Sso_Ldap_Entry($this->_conn, $dn, true);
		$e->setAttribute('objectClass', $class);
		$this->_clearCache();
		return $e; 
	}
	
	/**
	 * getDn
	 * 
	 * Return the DN of the current entry.
	 * 
	 * @return string
	 */
	public function getDn() {
		return $this->_dn;
	}
	
	/**
	 * setDn
	 * 
	 * Set the dn of the current entry. Effectively points the object at the provided $dn.
	 * 
	 * @param string $dn
     *
	 * @return Sso_Ldap_Entry
	 */
	public function setDn($dn) {
		$this->_parent       = null;
		$this->_attributes   = array();
		$this->_children     = null;
		$this->_search_cache = array();

		$this->_dn = implode(',', array_unique(explode(',', $dn)));
		
		return $this;
	}
	
	/**
	 * hasAttribute
	 * 
	 * Returns true or false if the current entry contains the $attribute. Can also use array syntax with isset($entry['attribute']);
	 * 
	 * @param string $name
     *
	 * @return boolean
	 */
	public function hasAttribute($attribute) {
		if ($this->_new) {
			return isset($this->_attributes[$attribute]);	
		}
		return $this->_conn->hasEntryAttribute($this->_dn, $attribute);
	}
	
	/**
	 * getAttributes
	 * 
	 * Return all the attributes for an entry as an array.
	 * 
	 * @return array
	 */
	public function getAttributes() {
		if ($this->_new) {
			return $this->_attributes;
		}
		
		return $this->_conn->getEntryAttributes($this->_dn);
	}
	
	/**
	 * getAvailableAttributes
	 * 
	 * Return the available attributes in this entry as an array.
	 * 
	 * @return array
	 */
	public function getAvailableAttributes() {
		if ($this->_new) {
			return array_keys($this->_attributes);
		}
		
		return $this->_conn->getAvailableEntryAttributes($this->_dn);
	}
	
	/**
	 * getAttribute
	 * 
	 * Returns the value(s) of the specified $attribute as an array. Can also use array syntax $entry['attribute'];
	 * 
	 * @param string $name
     *
	 * @return array
	 */
	public function getAttribute($attribute) {
		if ($this->_new) {
			return isset($this->_attributes[$attribute]) ? $this->_attributes[$attribute] : false;
		}
		return $this->_conn->getEntryAttribute($this->_dn, $attribute);
	}
	
	/**
	 * setAttribute
	 * 
	 * Set the $value of the specified $attribute.
	 * Can also use array syntax $entry['attribute'] = 'value'.
	 * Using this syntax will replace other attributes of the same name.
	 * To add a new attribute with that name use $entry['attribute:'] = 'value';
	 * note the trailing ':' in the attribute name.
	 * 
	 * @param string $name
	 * @param string $value
     *
	 * @return boolean Returns true on success and false on failure.
	 */
	public function setAttribute($name, $value) {
		if ($this->_new) {
			if (substr($name, -1,1) == ':') {
				$name = substr($name, 0, -1);
				if (isset($this->_attributes[$name]) && !is_array($this->_attributes[$name])) {
					$this->_attributes[$name] = array($this->_attributes[$name]);
				}
				if (is_array($value)) {
					foreach ($value as $v) {
						$this->_attributes[$name][] = $v;
					}
				} else {
					$this->_attributes[$name][] = $value;
				}
			} else {
				if (!is_array($value)) {
					$value = array($value);
				}
				$this->_attributes[$name] = $value;
			}
			return true;
		}
		$ret = $this->_conn->setEntryAttribute($this->_dn, $name, $value);
		$this->_clearCache();
		return $ret;
	}
	
	/**
	 * delAttribute
	 * 
	 * Delete the attribute specified by $name, and optionally remove the attribute with that name and a specific $value.
	 * Can also use array syntax, unset($entry['attribute']); and unset($entry['attribute:value']);
	 * 
	 * @param string $name
	 * @param string $value
     *
	 * @return boolean Returns true on success and false on failure.
	 */
	public function delAttribute($name, $value=null) {
		if ($this->_new) {
			if (stristr($name, ':')) {
				list($name, $value) = explode(':', $name);
			}
			if (!isset($this->_attributes[$name])) {
                return false;
            }
			
			if (is_array($this->_attributes[$name]) && isset($value)) {
				unset($this->_attributes[$name][array_search($value)]);
			} else {
				unset($this->_attributes[$name]);
			}
			return true;
		}
		$res = $this->_conn->delEntryAttribute($this->_dn, $name, $value);
		$this->_clearCache();
		return $res;
	}	
	
	/**
	 * getChildren
	 * 
	 * Return the children of the current entry as an Sso_Ldap_EntryIterator
	 * 
	 * @return Sso_Ldap_EntryIterator
	 */
	public function getChildren() {
		if ($this->_new) {
            return false;
        }
		if (!($this->_children instanceof Sso_Ldap_EntryIterator)) {
			$this->_children = $this->_conn->getEntryChildren($this->_dn);
		}
		return clone $this->_children;
	}
	
	/**
	 * getParent
	 * 
	 * Return the parent of the current entry.
	 * 
	 * @return Sso_Ldap_Entry
	 */
	public function getParent() {
		if (!($this->_parent instanceof Sso_Ldap_Entry)) {
			$dn = explode(',', $this->_dn);
			array_shift($dn);
			if (!count($dn)) {
                return false;
            }
			$dn = implode(',', $dn);
			$this->_parent = new Sso_Ldap_Entry($this->_conn, $dn);
		}
		return $this->_parent;
	}
	
	/**
	 * getSiblings
	 * 
	 * Return the siblings of the current entry as an Sso_Ldap_EntryIterator
	 * 
	 * @return Sso_Ldap_EntryIterator
	 */
	public function getSiblings() {
		return $this->getParent()->getChildren();
	}
	
	/**
	 * find
	 * 
	 * Find an item by its RDN.
	 * 
	 * @param $rdn
	 */
	public function find($rdn) {
		return new Sso_Ldap_Entry($this->_conn, "{$rdn},{$this->_dn}");
	}
	
	/**
	 * search
	 * 
	 * Search the decendants of the current entry using the provided filter. By default this is '(objectClass=*)' and so returns
	 * all entries. Entries are returned as an Sso_Ldap_EntryIterator.
	 * 
	 * @param string $filter
	 * @param string $baseDN [optional] extra base DN prepended to the application base DN
     *
	 * @return Sso_Ldap_EntryIterator
	 */
	public function search($filter = '(objectClass=*)', $baseDN = '') {
		if ($this->_new) {
            return false;
        }
		$hash = md5($filter.$baseDN);
		$dn = (empty($baseDN) ? '' : $baseDN.',').$this->_dn;
		if (!isset($this->_search_cache[$hash]) || !($this->_search_cache[$hash] instanceof Sso_Ldap_EntryIterator)) {
			$this->_search_cache[$hash] = $this->_conn->search($dn, $filter);
		}
		return $this->_search_cache[$hash];
	}

    /**
     *
     * @param string $offset
     *
     * @return mixed
     */
	public function offsetGet($offset) {
		switch ($offset) {
			case '_parent': 
                return $this->getParent();
            default:
                return $this->getAttribute($offset);
		}
	}

    /**
     *
     * @param string $offset
     * @param mixed  $value
     *
     * @return boolean
     */
	public function offsetSet($offset, $value) {
		return $this->setAttribute($offset, $value);
	}

    /**
     *
     * @param string $offset
     *
     * @return boolean
     */
	public function offsetExists($offset) {
		return $this->hasAttribute($offset);	
	}

    /**
     *
     * @param string $offset
     *
     * @return boolean
     */
	public function offsetUnset($offset) {
		return $this->delAttribute($offset);
	}
	
	/**
	 * save
	 * 
	 * Add this new entry to the LDAP store.
	 * 
	 * @return boolean Returns true on success and false on failure.
	 */
	public function save() {
		if (!$this->_new) {
            return false;
        }
		foreach ($this->_attributes as $k => $attr) {
			if (is_array($attr)) {
                $this->_attributes[$k] = array_unique($attr);
            }
		}
		$ret = $this->_conn->addEntry($this->_dn, $this->_attributes);
		$this->_new = false;
		$this->_attributes = array();
		$this->_clearCache();
		return $ret;
	}

	/**
	 * delete
	 * 
	 * Delete this entry from the LDAP store and optionally delete all children, optionally recursing.
	 * 
	 * @return boolean Returns true on success and false on failure.
	 */
	public function delete($children = false, $recurse = false) {
		if ($this->_new) {
            return false;
        }
		if ($children) {
			foreach ($this->getChildren() as $child) {
				if ($recurse) {
					$child->delete(true, true);
				} else {
					$child->delete(false);
				}
			}
		}
		$ret = $this->_conn->delEntry($this->_dn);
		$this->_clearCache();
		return $ret;
	}

    /**
     * Utility method to print the DN and the attributes
     */
	public function debug() {
		 var_dump($this->getDn(), $this->getAttributes());
	}
}
