<?php
/**
 * @package sso
 */

/**
 * Sso_Ldap_EntryIterator
 * 
 * Allows you to iterate over collections of LDAP entries.
 * Also provides local filtering and sort functionality.
 *  
 * @package sso 
 */
final class Sso_Ldap_EntryIterator implements Iterator, Countable, ArrayAccess
{
    /**
     * @var array
     */
	private $_entries = array();

    /**
     * @var Sso_Ldap_Entry
     */
	private $_instance;

    /**
     * @var boolean
     */
	private $_valid = false;

    /**
     * @var integer
     */
	private $_tolerance = 60;
	
	/**
	 * Create a new Sso_Ldap_EntryIterator providing an array of DNs to iterate
     * over and a Sso_Ldap_Connection object.
     *
	 * @param array               $entries
	 * @param Sso_Ldap_Connection $conn
	 */
	public function __construct($entries, Sso_Ldap_Connection $conn) {
		$this->_entries  = $entries;
		$this->_instance = new Sso_Ldap_Entry($conn);
	}

    /**
     *
     */
	public function rewind() {
		reset($this->_entries);
		$this->_valid = count($this->_entries) ? true : false;
	}

    /**
     * @return Sso_Ldap_Entry
     */
	public function current() {
		return $this->_instance->setDn(current($this->_entries));
	}

    /**
     * @return integer
     */
	public function key() {
		return current($this->_entries);
	}

    /**
     *
     */
	public function next() {
		$this->_valid = next($this->_entries);
	}

    /**
     * @return boolean
     */
	public function valid() {
		return $this->_valid;
	}

    /**
     * @return integer
     */
	public function count() {
		return count($this->_entries);
	} 
	
	/**
	 * Set the fuzzy search tollerance used when using fuzzy filtering.
     * Is an integer specifying the lowest similarity % that will cause a match.
     * Default is 60.
	 * 
	 * @param int $tolerance
     *
	 * @return Sso_Ldap_EntryIterator
	 */
	public function setFuzzyTolerance($tolerance) {
		$tolerance = ($tolerance < 1) ? 1 : $tolerance;
		$this->_tolerance = (int) $tolerance;
		
		return $this;
	}

    /**
     *
     * @param string $attr
     *
     * @return boolean
     */
	private function filterFuzzyMatch($attr) {
		similar_text($attr, $this->_filter_pattern, $perc);
		return ($perc >= $this->_tolerance);
	}

    /**
     *
     * @param string $attr
     *
     * @return boolean
     */
	private function filterPartialMatch($attr) {
		return fnmatch($this->_filter_pattern, $attr);
	}

    /**
     *
     * @param string $attr
     *
     * @return boolean
     */
	private function filterFullMatch($attr) {
		return ($attr == $this->_filter_pattern);
	}

    /**
     *
     * @param string  $attr
     * @param string  $pattern
     * @param boolean $fuzzy
     *
     * @return boolean
     */
	private function filterMatch($attr, $pattern, $fuzzy) {
		$this->_filter_pattern = $pattern;
		
		if ($fuzzy) {
			$attr = array_filter($attr, array($this, 'filterFuzzyMatch'));
		} elseif (stristr($pattern, '*') || stristr($pattern, '?')) {
			$attr = array_filter($attr, array($this, 'filterPartialMatch'));
		} else {
			$attr = array_filter($attr, array($this, 'filterFullMatch'));
		}
		
		$this->_filter_pattern = null;
		
		return count($attr) ? true : false;
	}
	
	/**
	 * Filter the contained entries excluding matched items. Provide an array of attribute => value
	 * matches. Can also handle partial matches using * and ? as wildcard characters. And you can
	 * optionally provide the second parameter to enable fuzzy matching. 
	 * 
	 * @param array   $filters
	 * @param boolean $fuzzy
     *
	 * @return Sso_Ldap_EntryIterator Returns the filtered iterator.
	 */
	public function excludeFilter(array $filters, $fuzzy = false) {
		$exclude = array();
		foreach ($this as $dn => $entry) {
			foreach ($filters as $name => $value) {
				if (isset($entry[$name])) {
					if ($this->filterMatch($entry[$name], $value, $fuzzy)) {
						$exclude[$dn] = $dn;
					}
				}	
			}
		}
		
		array_map(array($this, 'removeDn'), $exclude);
		
		return $this;
	}
	
	/**
	 * Filter the contained entries excluding items that do not match. Provide an array of attribute => value
	 * matches. Can also handle partial matches using * and ? as wildcard characters. And you can
	 * optionally provide the second parameter to enable fuzzy matching. 
	 * 
	 * @param array   $filters
	 * @param boolean $fuzzy
     *
	 * @return Sso_Ldap_EntryIterator Returns the filtered iterator.
	 */
	public function includeFilter($filters, $fuzzy=false) {
		$exclude = array();
		foreach ($this as $dn => $entry) {
			foreach ($filters as $name => $value) {
				if (isset($entry[$name])) {
					if (!$this->filterMatch($entry[$name], $value, $fuzzy)) {
						$exclude[$dn] = $dn;
					}
				}  else {
					$exclude[$dn] = $dn;
				}	
			}
		}
		
		array_map(array($this, 'removeDn'), $exclude);
		
		return $this;
	}
	
	/**
	 * Remove the specified $dn from the iterator.
     *
	 * @param string $dn
	 */
	public function removeDn($dn) {
		unset($this->_entries[$dn]);
	}
	
	/**
	 * Sort the entries in the collection by the provided $attributes, optionally only specifying a particular $class to sort (will
	 * ignore other objectClasses). The attributes should take the format of either array(column,column,...) or 
	 * array(column => direction). Direction is either asc or desc. Returns the sorted iterator.
	 * 
	 * @param array  $attributes
	 * @param string $class
     *
	 * @return Sso_Ldap_EntryIterator
	 */
	public function sort(array $attributes, $class = null)
	{
		if (!is_array($attributes)) {
            $attributes = array($attributes);
        }
    	foreach ($attributes as $name => $dir) {
		    if (is_numeric($name)) {
		    	$attributes[$dir] = 'asc';
		    	unset($attributes[$name]);
    		}
		}
		
		list($p, $table) = $this->transferToTempTable(array_keys($attributes), $class);
		foreach ($attributes as $k => $v) {
			$attributes[$k] = "`{$k}` $v";
		}
		$attributes = implode(',',$attributes);

		$qry = "select _data_id from `{$table}` order by {$attributes}";
				
		$this->_entries = array();
		foreach ($p->query($qry) as $row) {
			$this->_entries[] = $row['_data_id'];
		}
				
		$qry = "drop table `{$table}`;";
		$p->query($qry);

		return $this;
	}

    /**
     *
     * @param array  $attributes
     * @param string $class
     *
     * @return array
     */
    private function transferToTempTable($attributes, $class = null) {
        $p = new PDO('sqlite::memory:');
    	$p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    	$table = spl_object_hash($this);
    	$qry = 'create table `'.$table.'` (_data_id,'.implode(',',$attributes).');';
		$p->query($qry);		
		
		$qry = 'insert into `'.$table.'` (_data_id,'.implode(',',$attributes).') values ('.implode(',',array_fill(0,count($attributes)+1,'?')).')';
		$stm = $p->prepare($qry);
				
		foreach ($this as $dn => $entry) {
			if (null !== $class && !in_array($class, $entry['objectClass'])) {
                continue;
            }
			$data = array();
			foreach ($attributes as $name) {
				$data[$name] = $entry[$name][0];
			}
			$row = array_merge(array($dn), array_values($data));
			$stm->execute($row);
		}

		return array($p, $table);
    }
    
    /**
     * Return the collection as an associative array of DNs => attributes.
     * 
     * @return array
     */
    public function toArray() {
    	$out = array();
    	foreach ($this as $dn => $entry) {
    		$out[$dn] = $entry->getAttributes();
    	}
    	return $out;
    }

    /**
     *
     * @param string $offset
     *
     * @return Sso_Ldap_Entry|false
     */
    public function offsetGet($offset) {
    	$offset = (int) $offset;
    	$entries = array_values($this->_entries);
    	if (!isset($entries[$offset])) {
            return false;
        }
    	return $this->_instance->setDn($entries[$offset]);
    }

    /**
     *
     * @param string $offset
     *
     * @return boolean
     */
    public function offsetExists($offset) {
    	$offset = (int) $offset;
    	$entries = array_values($this->_entries);
    	return isset($entries[$offset]);    	
    }

    /**
     *
     * @param string $offset
     * @param mixed $value
     *
     * @return boolean
     */
    public function offsetSet($offset, $value) {
 		return false;   	
    }

    /**
     *
     * @param string $offset
     *
     * @return boolean
     */
    public function offsetUnset($offset) {
    	$offset  = (int) $offset;
    	$entries = array_values($this->_entries);
    	if (!isset($entries[$offset])) {
            return false;
        }
    	$this->removeDn($entries[$offset]);
        return true;
    }
    
    /**
     * get
     * 
     * Return an item in a collection specified by $offset.
     * Boolean false if offset does not exist.
     *
     * @param int $offset
     *
     * @return Sso_Ldap_Entry|false
     */
    public function get($offset) {
		return $this[$offset];
    }
}
