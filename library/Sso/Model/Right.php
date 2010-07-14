<?php

class Sso_Model_Right extends Sso_Model_Base {
    /**
     * getRightFromEntry 
     * 
     * @param Sso_Ldap_Entry $entry  the ldap record for this right
     * @static
     * @access public
     * @return Sso_Model_Right The model object representing this record
     */
    static public function getRightFromEntry($entry) {
        $right = new Sso_Model_Right();
        $right->resource   = Sso_Model_Resource::getIdFromDn($entry['ssoResource'][0]);
        $right->right = $entry['ssoDescription'][0];
        $right->grant = $entry['ssoGrant'][0];
        return $right;
    }

    /**
     * getRightChildrenFromLdapEntry
     *
     * return child rights of this Sso_Ldap_Entry object
     *
     * @param Sso_Ldap_Entry $entry
     *
     * @return array Containing Sso_Model_Right objects
     */
    public static function getRightChildrenFromLdapEntry(Sso_Ldap_Entry $entry) {
        $retval = array();
		$rights  = new Sso_Ldap_FilterIterator_ObjectClass($entry->getChildren(), 'ssoRight');
		foreach ($rights as $e) {
			$retval[] = Sso_Model_Right::getRightFromEntry($e);
        }
        return $retval;
    }

	/**
	 * Merge rights on resources.
	 *
	 * @param array $highPriority
	 * @param array $lowPriority
	 *
	 * @return array
	 */
	static public function mergeRights(array $highPriority, array $lowPriority) {
		foreach ($highPriority as $hi) {
			foreach ($lowPriority as $key => $lo) {
				if ($hi->resource == $lo->resource && $hi->right == $lo->right) {
					unset($lowPriority[$key]);
					break;
				}
			}
		}
		return array_merge($highPriority, $lowPriority);
	}
}
