<?php

/**
 * Filter iterator to filter attributes by a certain objectClass
 *
 * @package sso
 * @author Lorenzo Alberton <lorenzo@ibuildings.com>
 */
class Sso_Ldap_FilterIterator_ObjectClass extends FilterIterator
{
	/**
	 * @var string
	 */
	private $_objectClass;

	/**
	 * @param Sso_Ldap_EntryIterator $it
	 * @param string $objectClass
	 */
    public function __construct(Sso_Ldap_EntryIterator $it, $objectClass) {
		parent::__construct($it);
        $this->_objectClass = $objectClass;
    }

    /**
     * @return boolean
     */
    public function accept() {
		$entry = $this->current();
		if (!empty($entry['objectClass'][0]) && $entry['objectClass'][0] === $this->_objectClass) {
			return true;
		}
        return false;
    }
}

