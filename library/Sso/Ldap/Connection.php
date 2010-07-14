<?php
/**
 * @package sso
 */

/**
 * Sso_Ldap_Connection
 *  
 * Handles connecting to the LDAP server and storage and retrieval of data.
 *
 * @package sso
 */
class Sso_Ldap_Connection
{
	/**
	 * @var array
	 */
	private $_options = array();

	/**
	 * @var Sso_Ldap_Wrapper
	 */
	private $_wrapper;

	/**
	 * @var resource
	 */
	private $_conn;

	/**
	 * @var string
	 */
	private $_url;

	/**
	 * @var boolean
	 */
	private $_bind;

	/**
	 * @var string
	 */
	private $_bind_dn;

	/**
	 * @var string
	 */
	private $_bind_password;

	/**
	 * @var string
	 */
	private $_base_dn;

	/**
	 * @var Sso_Ldap_Entry
	 */
	private $_base;

	/**
	 * @var string
	 */
	private $_schema;
	
	/**
	 * @var Sso_Cache_Base
	 */
	private $_cache;

	/**
	 * @var integer
	 */
	private $_child_cache = 360;

	/**
	 * @var integer
	 */
	private $_search_cache = 360;

	/**
	 * @var integer
	 */
	private $_schema_cache = 86400;
	
	/**
	 * __construct
	 * 
	 * Connect to the LDAP server $url specified in the format ldap://hostname and provide a base DN.
	 * 
	 * @param string $url
	 * @param string $base_dn
	 */
	public function __construct($url, $base_dn) {
		$this->_wrapper = new Sso_Ldap_Wrapper();
		$this->_url     = $url;
		$this->_base_dn = $base_dn;
	}

	/**
	 * Flush all the cache (unless it's a session token change,
	 * in which case we can ignore it)
	 *
	 * @param string $dn DN to be checked for
	 */
	protected function _clearCache($dn = '') {
		if ($this->_cache instanceof Sso_Cache_Base) {
			$this->_cache->flush();
		}
	}
	
	/**
	 * getConn
	 * 
	 * Return the LDAP connection resource
	 * 
	 * @return resource
	 */
	public function getConn() {
		return $this->_conn;
	}
	
	/**
	 * setWrapper
	 * 
	 * Override the default wrapper wit one of your own (normally used for testing).
	 * 
	 * @param object $wrapper
	 */
	public function setWrapper($wrapper) {
		$this->_wrapper = $wrapper;
	}
	
	/**
	 * setChildCacheTimeout
	 * 
	 * Set the cache timeout of child nodes in seconds. Child nodes are the result of the getEntryChildren call.
	 * 
	 * @param integer $timeout
	 *
	 * @return Sso_Ldap_Connection
	 */
	public function setChildCacheTimeout($timeout) {
		$this->_child_cache = $timeout;
		return $this;
	}
	
	/**
	 * setSearchCacheTimeout
	 * 
	 * Set the cache timeout of calls to search in seconds.
	 * 
	 * @param integer $timeout
	 *
	 * @return Sso_Ldap_Connection
	 */
	public function setSearchCacheTimeout($timeout) {
		$this->_search_cache = $timeout;
		return $this;
	}
	
	/**
	 * addCacheBackend
	 * 
	 * Add and enable a cache backend.
	 * 
	 * @param Sso_Cache_Base $cache
	 *
	 * @return Sso_Ldap_Connection
	 */
	public function addCacheBackend(Sso_Cache_Base $cache) {
		$this->_cache = $cache;
		return $this;
	}

	/**
	 * setBind
	 * 
	 * Set the LDAP bind details; the bind $dn and password $pw.
	 *
	 * @param string $dn
	 * @param string $pw
	 *
	 * @return Sso_Ldap_Connection
	 */
	public function setBind($dn, $pw) {
		$this->_bind_dn       = $dn;
		$this->_bind_password = $pw;
		return $this;
	}

	/**
	 * setLdapOption
	 * 
	 * Sets an option on the LDAP connection.
	 * 
	 * @see http://php.net/manual/en/function.ldap-set-option.php
	 * 
	 * @param int $constant
	 * @param mixed $value
	 * @return Sso_Ldap_Connection
	 */
	public function setLdapOption($constant, $value) {
		$this->_options[$constant] = $value;
		return $this;
	}
	
	/**
	 * connect
	 * 
	 * Connect to the LDAP data store. 
	 */
	public function connect() {
		if (!is_resource($this->_conn)) {
			$this->_conn = $this->_wrapper->connect($this->_url);
			$this->setLdapOption(LDAP_OPT_PROTOCOL_VERSION, 3);
			$this->setLdapOption(LDAP_OPT_REFERRALS, 0);
			foreach ($this->_options as $opt => $val) {
				$this->_wrapper->set_option($this->_conn, $opt, $val);
			}
		}
		
		if (!$this->_bind) {
			$this->bind($this->_bind_dn, $this->_bind_password);
		}
	}

	/**
	 *
	 * @param string $dn
	 * @param string $pass
	 */
	private function bind($dn = null, $pass = null) {
		if (null !== $dn) {
			$this->_bind = $this->_wrapper->bind($this->_conn, $dn, $pass);
		}  else {
			$this->_bind = $this->_wrapper->bind($this->_conn, $dn, $pass);
		}	
	}
	
	/**
	 * getBase
	 * 
	 * Get the base LDAP entry.
	 * 
	 * @return Sso_Ldap_Entry
	 */
	public function getBase() {
		if (!($this->_base instanceof Sso_Ldap_Entry)) {
			$this->_base = new Sso_Ldap_Entry($this, $this->_base_dn);
		}
		return $this->_base;
	} 
	
	/**
	 * getEntryAttributes
	 * 
	 * Return the attributes for a given $dn as an array.
	 * 
	 * @param string $dn
	 *
	 * @return array
	 */
	public function getEntryAttributes($dn) {
		$attributes = $this->getAvailableEntryAttributes($dn);
				
		$out = array();
		foreach ($attributes as $attrib) {
			if (($val = $this->getEntryAttribute($dn, $attrib))) {
				$out[$attrib] = $val;
			}  
		}
		
		return $out;
	}
	
	/**
	 * getAvailableEntryAttributes
	 * 
	 * Get the available attributes for the given entry $dn as an array.
	 * 
	 * @param string $dn
	 *
	 * @return array
	 */
	public function getAvailableEntryAttributes($dn) {
		$oc = $this->getEntryAttribute($dn, 'objectClass');
		$oc = $this->getSchemaObjectClass($oc[0]);
		
		$attributes = array_merge($oc['attributes']['must'], $oc['attributes']['may']);
		
		$new = array();
		foreach ($attributes as $attribute) {
			$attributes = array_merge($attributes, $this->getSchemaAttributeAliases($attribute));
		}
		$attributes = array_merge(array_unique($attributes));
		return $attributes;
	}
	
	/**
	 * getEntryAttribute
	 * 
	 * Get the value of $attribute of the entry $dn.
	 * 
	 * @param string $dn
	 * @param string $attribute
	 *
	 * @return array
	 */
	public function getEntryAttribute($dn, $attribute) {
		if ($this->_cache instanceof Sso_Cache_Base) {
			$hash = md5($dn);
			$key = "{$hash}.$attribute";
			if (($data = $this->_cache->get($key))) {
				return $data;
			}
		}
		
		$this->connect();
		$entries = $this->_wrapper->read($this->_conn, $dn, '(objectClass=*)', array($attribute));
		$entry   = $this->_wrapper->first_entry($this->_conn, $entries);
		$result  = $this->_wrapper->get_attributes($this->_conn, $entry);
		
		if (!isset($result[$attribute])) {
			return false;
		}
				
		unset($result[$attribute]['count']);
		
		if ($this->_cache instanceof Sso_Cache_Base) {
			$this->_cache->set($key, $result[$attribute], $this->_child_cache);
		}
		
		return $result[$attribute];
	}
	
	/**
	 * hasEntryAttribute
	 * 
	 * Returns true/false if the specified $dn contains the given $attribute.
	 * 
	 * @param string $dn
	 * @param string $attribute
	 *
	 * @return boolean
	 */
	public function hasEntryAttribute($dn, $attribute) {
		if ($this->_cache instanceof Sso_Cache_Base) {
			$hash = md5($dn);
			$key = "{$hash}.$attribute";
			if (($data = $this->_cache->has($key))) {
				return true;
			}
		}
		
		$this->connect();

		$entries = $this->_wrapper->read($this->_conn, $dn, '(objectClass=*)', array($attribute), true);
		$entry   = $this->_wrapper->first_entry($this->_conn, $entries);
		$result  = $this->_wrapper->get_attributes($this->_conn, $entry);
		
		if (!isset($result[$attribute])) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * setEntryAttrbiute
	 * 
	 * Set the $value of an $attribute contained in the entry located at $dn.
	 * 
	 * @param string $dn
	 * @param string $attribute
	 * @param mixed  $value
	 *
	 * @return boolean Returns true on success and false on error.
	 */
	public function setEntryAttribute($dn, $attribute, $value) {
		$this->connect();
		if (substr($attribute, -1, 1) == ':') {
			$attribute = substr($attribute, 0, -1);
			$ret = $this->_wrapper->mod_add($this->_conn, $dn, array($attribute => $value));
			$this->_clearCache($dn);
			return $ret;
		}
		$ret = $this->_wrapper->modify($this->_conn, $dn, array($attribute => $value));
		$this->_clearCache($dn);
		return $ret;
	}
	
	/**
	 * delEntryAttribute
	 * 
	 * Removes the $attribute contained in the entry located at $dn. Optionally providing a $value to remove a specific attribute
	 * when there are multiple attributes of that type.
	 * 
	 * @param string $dn
	 * @param string $attribute
	 * @param string $value
	 *
	 * @return boolean Returns true on success and false on failure.
	 */
	public function delEntryAttribute($dn, $attribute, $value = null) {
		$this->connect();
		if (stristr($attribute, ':')) {
			list($attribute, $value) = explode(':', $attribute);
		}
		$attr = array($attribute => $value);
		$ret = $this->_wrapper->mod_del($this->_conn, $dn, $attr);
		$this->_clearCache($dn);
		return $ret;
	}
	
	/**
	 * addEntry
	 * 
	 * Save an $entry at the location specified by $dn. $entry should be an associative array of attributes and values. To create multiple
	 * attributes of the same name provide an array of values for that attribute. 
	 * 
	 * @param string $dn
	 * @param array  $entry
	 *
	 * @return boolean Returns true on success and false on failure.
	 */
	public function addEntry($dn, $entry) {
		$this->connect();
		$ret = $this->_wrapper->add($this->_conn, $dn, $entry);
		$this->_clearCache($dn);
		return $ret;
	}
	
	/**
	 * delEntry
	 * 
	 * Delete an entry specified by the $dn.
	 * 
	 * @param string $dn
	 * @param array $entry
	 *
	 * @return boolean Returns true on success and false on failure.
	 */
	public function delEntry($dn) {
		$this->connect();
		$ret = $this->_wrapper->del($this->_conn, $dn);
		$this->_clearCache($dn);
		return $ret;
	}
	
	/**
	 * getEntryChildren
	 * 
	 * Return the children of the specified entry as an Sso_Ldap_EntryIterator.
	 * 
	 * @param string $dn
	 *
	 * @return Sso_Ldap_EntryIterator
	 */
	public function getEntryChildren($dn) {
		if ($this->_cache instanceof Sso_Cache_Base) {
			$hash = md5($dn);
			$key = "{$hash}._children";
			if (($data = $this->_cache->get($key))) {
				return new Sso_Ldap_EntryIterator($data, $this);
			}
		}
		
		$this->connect();
		try {
			$result   = $this->_wrapper->children($this->_conn, $dn, '(objectClass=*)', array('dn'));
			$children = $this->_wrapper->get_entries($this->_conn, $result);
			$dns      = $this->getDnsFromEntries($children);
		} catch (Sso_Ldap_Exception $e) {
			if ($e->getCode() == Sso_Ldap_Wrapper::ERROR_NO_SUCH_OBJECT) {
				// no children: that's ok
				$dns = array();
			} else {
				throw $e;
			}
		}
		if ($this->_cache instanceof Sso_Cache_Base) {
			$data = $this->_cache->set($key, $dns, $this->_child_cache);
		}
		
		return new Sso_Ldap_EntryIterator($dns, $this);
	}
	
	/**
	 * search
	 * 
	 * Search the repository from the specified $base using the provided filter returning the results as an
	 * Sso_Ldap_EntryIterator.
	 * 
	 * @param string $base
	 * @param string $filter
	 *
	 * @return Sso_Ldap_EntryIterator
	 */
	public function search($base, $filter) {
		if ($this->_cache instanceof Sso_Cache_Base) {
			$hash = md5($base);
			$fhash = md5(serialize($filter));
			$key = "{$hash}.{$fhash}";
			if (($data = $this->_cache->get($key))) {
				return new Sso_Ldap_EntryIterator($data, $this);
			}
		}
		
		$this->connect();
		
		$result  = $this->_wrapper->search($this->_conn, $base, $filter, array('dn'));
		$entries = $this->_wrapper->get_entries($this->_conn, $result);
		
		$dns = $this->getDnsFromEntries($entries);
		
		if ($this->_cache instanceof Sso_Cache_Base) {
			$this->_cache->set($key, $dns, $this->_search_cache);
		}

		return new Sso_Ldap_EntryIterator($dns, $this);
	}

	/**
	 *
	 */
	private function getSchema() {
		if ($this->_cache instanceof Sso_Cache_Base) {
			$hash = md5($this->_url);
			$key = "{$hash}._schema";
			if (($schema = $this->_cache->get($key))) {
				$this->_schema = $schema;
			}
		}
		
		$this->connect();
		
		$result = $this->_wrapper->read($this->_conn, $this->_base_dn, '(objectClass=*)', array('subschemaSubentry'), 0, 0, 0, LDAP_DEREF_NEVER);

		$e = $this->_wrapper->get_entries($this->_conn, $result);
		$schema_dn = $e[0]['subschemasubentry'][0];
		
		$this->_schema['classes']    = $this->getClassSchema($schema_dn);
		$this->_schema['attributes'] = $this->getAttributeSchema($schema_dn);
		
		if ($this->_cache instanceof Sso_Cache_Base) {
			$this->_cache->set($key, $this->_schema, $this->_schema_cache);
		}
	}

	/**
	 *
	 * @param string $schema_dn
	 *
	 * @return array
	 */
	private function getClassSchema($schema_dn) {
		$result = $this->_wrapper->read($this->_conn, $schema_dn, '(objectClass=subschema)', array('objectclasses'), 0, 0, 0, LDAP_DEREF_NEVER);
		$e = $this->_wrapper->get_entries($this->_conn, $result);
		unset($e[0]['objectclasses']['count']);
		$classes = array();
		foreach ($e[0]['objectclasses'] as $class) {
			$class = $this->parseObjectClass($class);
			$classes[$class['name']] = $class;
		}
		return $classes;
	}

	/**
	 *
	 * @param string $schema_dn
	 *
	 * @return array
	 */
	private function getAttributeSchema($schema_dn) {
		$result = $this->_wrapper->read($this->_conn, $schema_dn, '(objectClass=subschema)', array('attributetypes'), 0, 0, 0, LDAP_DEREF_NEVER);
		$e = $this->_wrapper->get_entries($this->_conn, $result);
		unset($e[0]['attributetypes']['count']);

		$attrs = array();
				
		foreach ($e[0]['attributetypes'] as $attribute) {			
			preg_match("/NAME\s+((?:\'[a-zA-Z0-9]+\')|(?:\([a-zA-Z0-9'\s]*?\)))/", $attribute, $match);
			$names = explode(' ', trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $match[1])));
						
			preg_match('/SUP\s+([a-zA-Z0-9]+)\s+/', $attribute, $match);
			$sup = isset($match[1]) ? $match[1] : false;
			
			foreach ($names as $name) {
				$attrs[$name] = $names;
				if ($sup) {
					$attrs[$name] = array_merge($attrs[$name], $attrs[$sup]);
				}
			}
		} 
		
		return $attrs;
	}

	/**
	 *
	 * @param string $class
	 *
	 * @return array|false
	 */
	private function getSchemaObjectClass($class) {
		$this->getSchema();
		
		if (!isset($this->_schema['classes'][$class])) {
			return false;
		}
								
		$out = $this->_schema['classes'][$class];
		$sup = $this->_schema['classes'][$class]['sup'];
		
		if ($sup == 'top') {
			return $out;
		}
		
		do {
			$out['attributes']['must'] = array_merge($this->_schema['classes'][$sup]['attributes']['must'], $out['attributes']['must']);
			$out['attributes']['may']  = array_merge($this->_schema['classes'][$sup]['attributes']['may'],  $out['attributes']['may']);
			$sup = $this->_schema['classes'][$sup]['sup'];
		} while ($sup != 'top');
		
		return $out;
	}

	/**
	 *
	 * @param string $attribute
	 *
	 * @return string|false
	 */
	private function getSchemaAttributeAliases($attribute) {
		$this->getSchema();
		return isset($this->_schema['attributes'][$attribute]) ? $this->_schema['attributes'][$attribute] : false;	
	}

	/**
	 *
	 * @param string $class
	 * @return <type>
	 */
	private function parseObjectClass($class) {
		if (!preg_match('/\(\s+([0-9\.]+)\s+NAME\s+\'([a-zA-Z]+)\'.+SUP\s+([a-zA-z]+).+(STRUCTURAL|AUXILIARY)/', $class, $match)) {
			return false;
		}
		
		$out = array();
		
		$name = $match[2];
		$out['name'] = $name;
		$out['oid']  = $match[1];
		$out['sup']  = $match[3];
		$out['type'] = strtolower($match[4]);
		
		preg_match_all('/((?:MAY)|(?:MUST))\s+((?:\([^\)]+\)|\S+))/', $class, $matches, PREG_SET_ORDER);
		
		$out['attributes']['must'] = array();
		$out['attributes']['may']  = array();
		
		if (count($matches)) {
			foreach ($matches as $match) {
				$type = strtolower($match[1]);
				if (!stristr($match[2], '$')) {
					$attrs = array($match[2]);
				} else {
					$attrs = array();
					preg_match_all('/[a-zA-Z]+/', $match[2], $attrs);
					$attrs = $attrs[0];
				}
				$out['attributes'][$type] = $attrs;
			}
		}
		return $out;
	}

	/**
	 *
	 * @param array $entries
	 *
	 * @return array
	 */
	private function getDnsFromEntries(array $entries) {
		$dns = array();
		foreach ($entries as $entry) {
			if (isset($entry['dn'])) {
				$dns[$entry['dn']] = $entry['dn'];
			}
		}
		return $dns;		
	}	
}
