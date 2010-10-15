<?php

class LdifReader {
	private $fh = null;
	private $prevLine = false;
	
	public function __construct($ldifFile) {
		$this->fh = fopen($ldifFile, 'r');
		if(!$this->fh) {
			throw new Exception("Could not open input file");
		}
	}
	
	public function __destruct() {
		if($this->fh) {
			fclose($this->fh);
		}
	}
	
	public function getDNs() {
		$this->reset();
		$dns = array(); 
		while($entry = $this->getNextEntry()) {
			$dns[$entry['dn'][0]] = $entry['offset'];
		}
		uksort($dns, array($this, 'cmpByLength'));
		return $dns;
	}
	
	public function getAttributeTree($baseDN, $attribute) {
		$this->reset();
		$tree = array(); 
		while($entry = $this->getNextEntry()) {
			// don't want exact matches, just children
			if(strpos($entry['dn'][0], $baseDN) > 0) {
				$parts = explode(",", str_replace(',' . $baseDN, '', $entry['dn'][0]));
				$parts = array_reverse($parts);
				$pos =& $tree;
				
				foreach($parts as $part) {
					if(!isset($pos['children'])) {
						$pos['children'] = array();
					}
					if(!isset($pos['children'][$part])) {
						$pos['children'][$part] = array();
					}
					
					$pos =& $pos['children'][$part];
				}
				
				if(isset($entry[$attribute])) {
					$pos[$attribute] = $entry[$attribute];
				}
				$pos['dn'] = $entry['dn'];
			}
		}
		return $tree;
	}
	
	public function getTreeClass($baseDN, $class) {
		$this->reset();
		$tree = array(); 
		while($entry = $this->getNextEntry()) {
			// don't want exact matches, just children
			if(strpos($entry['dn'][0], $baseDN) > 0) {
				$parts = explode(",", str_replace(',' . $baseDN, '', $entry['dn'][0]));
				$parts = array_reverse($parts);
				$pos =& $tree;
				
				foreach($parts as $part) {
					if(!isset($pos['children'])) {
						$pos['children'] = array();
					}
					if(!isset($pos['children'][$part])) {
						$pos['children'][$part] = array();
					}
					
					$pos =& $pos['children'][$part];
				}
				
				if($entry['objectClass'][0] == $class) {
					foreach($entry as $key => $value) {
						if(is_array($value)) {
							$pos[$key] = $value;
						}
					}
				}
			}
		}
		return $tree;
	}
	
	public function printTree($tree, $attribute, $space = '') {
		// boot strap the recursion for the root node
		if(count($tree) == 1 && isset($tree['children'])) {
			$this->printTree($tree['children'], $attribute);
		}
		
		foreach($tree as $key => $val) {
			if($key != 'children') {
				echo $space  . $key . "\n";
				if($attribute && isset($val[$attribute])) {
					foreach($val[$attribute] as $attrib) {
						echo $space . " (" . $attribute . ": " . $attrib . ")\n";
					}
				}
			} 
			if(isset($val['children'])) {
				$this->printTree($val['children'], $attribute, $space . "----");
			}
		}
	}
	
	public function cmpByLength($a, $b) {
		return strlen($a) - strlen($b);
	}
	
	public function rCmpByLength($a, $b) {
		return strlen($b) - strlen($a);
	}
	
	public function getDnAtOffset($offset) {
		fseek($this->fh, $offset);
		return $this->getNextEntry();
	}
	
	public function getNextEntry() {
		$entry = array();
		$buffer = array();
		$seenDN = false;
		$i = 0;
		$wasComment = false;
		while($line = fgets($this->fh)) {
			if(strpos($line, 'dn: ') === 0) {
				$point = ftell($this->fh) - strlen($line);
				if(!$seenDN) {
					$entry['offset'] = $point;
					$seenDN = true;
				} else {
					// rewind file pointer
					fseek($this->fh, $point);
					break;
				}
			}
			
			if($line[0] == " ") {
				// merge in split lines
				if(!$wasComment) {
					$buffer[$i - 1] .= trim($line);
				}
			} else if(strpos($line, 'search: ') === 0 || strpos($line, 'result: ') === 0) {
				// skip search result info
			} else if($line[0] == '#') {
				// skip coments, and handle line splitting
				$wasComment = true;
				continue;
			} else if(strlen(trim($line)) == 0) {
				// skip spaces
			} else {
				$buffer[$i++] = trim($line);
			}
			$wasComment = false;
		}
		
		foreach($buffer as $line) {
			list($key, $val) = explode(': ', $line);
			if(strlen($key) && strlen($val)) {
				if(!isset($entry[$key])) {
					$entry[$key] = array();
				}
				$entry[$key][] = $val;
			} 
		}
		
		unset($buffer);
		
		if(!count($entry)) {
			return false;
		} else {
			return $entry;
		}
	}
	
	public function reset() {
		rewind($this->fh);
	}
		
	protected function getVal($string) {
		list(, $result) = explode(': ', $string);
		return $result;
	}
		
}

class LdifDiff {
	private $old;
	private $new;
	
	public function __construct(LdifReader $old, LdifReader $new) {
		$this->old = $old;
		$this->new = $new;
	}
	
	public function getCreates() {
		$mismatches = $this->getMismatches($this->old->getDns(), $this->new->getDns());
		$ldif = "";
		foreach($mismatches as $dn => $offset) {
			$entry = $this->new->getDnAtOffset($offset);
			$ldif .= $this->formatEntry($entry);
		}
		return $ldif;
	}
	
	public function getDeletes() {
		$mismatches = $this->getMismatches($this->new->getDns(), $this->old->getDns());
		$ldif = "";
		uksort($mismatches, array($this->new, 'rCmpByLength'));
		foreach($mismatches as $dn => $offset) {
			$entry = $this->old->getDnAtOffset($offset);
			$ldif .= $this->formatDeleteEntry($entry);
		}
		return $ldif;
	}
	
	private function formatDeleteEntry($entry) {
		$string = "dn: " . $entry['dn'][0] . "\n";
		$string .= "changetype: delete\n\n";
		return $string;
	}
	
	private function formatEntry($entry) {
		$string = "";
		$string = "dn: " . $entry['dn'][0] . "\n";
		$string .= "changetype: add\n";
		foreach($entry as $key => $vals) {
			if(!is_array($vals) || $key == 'dn') {
				continue;
			} else {
				foreach($vals as $val) {
					$string .= $key . ": " . $val . "\n";
				}
			}
		}
		$string .= "\n";
		return $string;
	}
	
	private function getMismatches($oldDns, $newDns) {
		$mismatches = array();
		foreach($newDns as $dn => $offset) {
			if(!isset($oldDns[$dn])) {
				$mismatches[$dn] = $offset;
			}
		}
		return $mismatches;
	}
}

class UserManager {
	private $perms;
	private $roleRightCache = array();
	private $rightsTree;
	private $rightsToRoles;
	private $mapping = array(
		"sso=incident,sso=reseller,sso=portal,sso=myorg,ou=Resources,ou=SSO" => "sso=incident,sso=enterprise,sso=portal,sso=myorg,ou=Resources,ou=SSO",
		"sso=incident,sso=portal,sso=myorg,ou=Resources,ou=SSO" => "sso=incident,sso=enterprise,sso=portal,sso=myorg,ou=Resources,ou=SSO",
	);
	
	public function __construct($perms) {
		$this->perms = $perms;
	}
	
	// retrieve all the rights for a role 
	private function getRightsListForRole($role) {
		if(isset($this->roleRightCache[$role])) {
			return $this->roleRightCache[$role];
		}
		if(!$this->rightsTree) {
			$this->rightsTree = $this->perms->getTreeClass('ou=Permissions,ou=SSO,dc=sso', 'ssoRight');
		}
	
		$rights = $this->getRightsFromTree($this->rightsTree['children'], $role, 'ou=Permissions,ou=SSO,dc=sso');
		$this->roleRightCache[$role] = $rights ? $rights : array();
		return $this->roleRightCache[$role];
	}
	
	private function getRightsFromTree($tree, $target, $base) {
		foreach($tree as $key => $val) {
			if(($key . "," . $base) == $target) {
				return $this->getRights($val);
			} else if(isset($val['children']) ){
				if($rights = $this->getRightsFromTree($val['children'], $target, $key . "," . $base)) {
					return array_merge($rights, $this->getRights($val));
				}
			}
		}
		return false;
	}
	
	private function getRights($val) {
		$rights = array();
		if(isset($val['children'])) {
			foreach($val['children'] as $vkey => $vval) {
				if(isset($vval['objectClass']) && $vval['objectClass'][0] == 'ssoRight') {
					$rights[] = $vval;
				}
			}
		}
		return $rights;
	}

	public function translateRights($users) {
		foreach($users as $key => $val) {
			if(isset($val['children'])) {
				$users[$key]['children'] = $this->translateRights($val['children']);
			} 
			
			if(isset($val['ssoRole'])) {
				foreach($val['ssoRole'] as $rkey => $role) {
					$rights = $this->getRightsListForRole($role);
					foreach($rights as $right) {
						if(!isset($users[$key]['ssoRight'][$right['sso'][0]])) {
							// do mapping of incident resources
							if(isset($this->mapping[$right['ssoResource'][0]])) {
								$right['ssoResource'][0] = $this->mapping[$right['ssoResource'][0]];
								$right['sso'][0] = substr(md5($right['ssoResource'][0].'++++++'.$right['ssoDescription'][0].'+++++'.$right['ssoGrant'][0]), 0, 6);
							}
							$users[$key]['ssoRight'][$right['sso'][0]] = $right;
						}
					}
				}
				unset($users[$key]['ssoRole']);
			} 
			
			if('children' == $key) {
				$users[$key] = $this->translateRights($val);
			}
		}
		return $users;
	}

	public function getLdif($tree) {
		$string = "";
		// boot strap the recursion for the root node
		if(count($tree) == 1 && isset($tree['children'])) {
			$string = $this->getLdif($tree['children']);
		} 
		
		foreach($tree as $key => $val) {
			if($key != 'children') {
				if(isset($val['dn'])) {
					$string .= "dn: " . $val['dn'][0] . "\n";
					$string .= "changetype: modify\n";
					$string .= "delete:ssoRole\n\n";
					if(isset($val['ssoRole'])) {
						$string .= "dn: " . $val['dn'][0] . "\n";
						$string .= "changetype: modify\n";
						$string .= "add:ssoRole\n";
						foreach($val['ssoRole'] as $attrib) {
							$string .= "ssoRole: " . $attrib ."\n";
						}
						$string .= "\n";
					}
				}
			} 
			if(isset($val['children'])) {
				$string .= $this->getLdif($val['children']);
			}
		}

		return $string;
	}
	
	private function getRightsToRoles() {
		if(isset($this->rightsToRoles)) {
			return $this->rightsToRoles;
		}
		$baseDN = 'ou=Permissions,ou=SSO,dc=sso';
		$rightsTree = $this->perms->getTreeClass($baseDN, 'ssoRight');
		$pairs = $this->getRightsRolesPairs($rightsTree['children']);
		$this->rightsToRoles = array();

		foreach($pairs as $pair) {
			$this->rightsToRoles[$pair[0]][] = $pair[1] . "," . $baseDN;
		}
		
		return $this->rightsToRoles;
	}
	
	// TODO: Fix this to include parents
	private function getRightsRolesPairs($rightsTree, $parent = array()) {
		$pairs = array();
		foreach($rightsTree as $key => $val) {
			if(isset($val['children'])) {
				array_unshift($parent, $key);
				$pairs = array_merge($this->getRightsRolesPairs($val['children'], $parent), $pairs);
				array_shift($parent);
			} 
			if(isset($val['objectClass'])) {
				$pairs[] = array($val['sso'][0], implode(",", $parent));
			}
		}
		return $pairs;
	}
	
	private function findRoleMatch($rights) {
		$index = $this->getRightsToRoles();
		$roles = array();

		while(count($rights) > 0) {
			$troles = array();
			// find the role which matches the most rights
			foreach($rights as $key => $right) {
				if(!isset($index[$right['sso'][0]])) {
					//var_dump("Ignoring " . $right['ssoResource'][0]);
					unset($rights[$key]);
				} else {					
					foreach($index[$right['sso'][0]] as $role) {
						if(!isset($troles[$role])) {
							$troles[$role] = 0;
						}
						$troles[$role]++;
					}
				}
			}
			if(!count($troles)) {
				throw new Exception("No matching roles found! \n\nRight: " . print_r($rights, true));
			}
			arsort($troles);
			// greedy algo
			$roles[] = key($troles);
			foreach($this->getRightsListForRole(key($troles)) as $right) {
				unset($rights[$right['sso'][0]]);
			}
		}
		return $roles;
	}

	public function findBestRolesFit($userTree) {
		foreach($userTree as $key => $val) {
			if(isset($val['children']) ) {
				$userTree[$key]['children'] = $this->findBestRolesFit($val['children']);
			} 
			
			if(isset($val['ssoRight'])) {
				try {
					$matches = $this->findRoleMatch($val['ssoRight']);
				} catch(Exception $e) {
					$matches = array();
					//var_dump("Match failed: " . $key);
					//die();
				}
				foreach($matches as $match) {
					$userTree[$key]['ssoRole'][] = $match;
				}
				unset($userTree[$key]['ssoRight']);
			}
		}
		
		return $userTree;
	}

	public function minimiseRights($users) {
		// for each level, see if there are duplicates
		$users = $this->removeRights($users, array());
		// for each level with children, see if all children share a common role
		$this->condenseRights($users);
		return $users;
	}

	private function removeRights($users, $roles) {
		foreach($users as $key => $value) {
			$uroles = array();
			foreach($value as $vkey => $vval) {
				if($vkey == 'ssoRight') {
					foreach($vval as $idx => $right) {
						if(in_array($right['sso'][0], $roles)) {
							unset($users[$key][$vkey][$idx]);
							//echo "Removing {$right['sso'][0]} from $key \n";
						} else {
							$uroles[] = $right['sso'][0];
						}
					}
				}
			}
			if(isset($users[$key]['children'])) {
				$users[$key]['children'] = $this->removeRights($users[$key]['children'], array_merge($uroles, $roles));
			}
		}
		return $users;
	}

	private function condenseRights(&$users) {
		$roles = array();
		foreach($users as $key => $value) {
			if($key != 'children') {
				if(isset($users[$key]['children'])) {
					$sharedRights = $this->condenseRights($users[$key]['children']);
					$remRights = array();
					foreach($sharedRights as $right) {
						$remRights[] = $right['sso'][0];
					}
					$users[$key]['children'] = $this->removeRights($users[$key]['children'], $sharedRights);
					if(isset($users[$key]['ssoRight'])) {
						foreach($users[$key]['ssoRight'] as $right) {
							if(!in_array($right['sso'][0], $remRights)) {
								$users[$key]['ssoRight'][$right['sso'][0]] = $right;
								//echo "Promoting {$right['sso'][0]} to $key \n";
							}
						}
					}
				}	
				$userroles = isset($users[$key]['ssoRight']) ? $users[$key]['ssoRight'] : array();
				$roles = array_intersect($roles, $userroles);
			}
		}
		return $roles;
	}
}


/*
To fill the files: 
ldapsearch -x -b "ou=Permissions,ou=SSO,dc=sso" > roles.txt
ldapsearch -x -b "ou=Resources,ou=SSO,dc=sso" > resources.txt
ldapsearch -x -z0 -w h0tp0tat0 -D 'cn=admin,dc=sso' -b "ou=Users,ou=SSO,dc=sso" "ssoRole=*" > users.txt
*/

if(!file_exists('current/roles.txt')) {
	echo <<<EOF
	Run the following to generate the script:
	cd current
	ldapsearch -x -b "ou=Permissions,ou=SSO,dc=sso" > roles.txt
	ldapsearch -x -b "ou=Resources,ou=SSO,dc=sso" > resources.txt
	ldapsearch -x -z0 -w h0tp0tat0 -D 'cn=admin,dc=sso' -b "ou=Users,ou=SSO,dc=sso" "ssoRole=*" > users.txt
	cd .. \n
EOF;
	exit;
}

// CREATE ROLE DIFF
$live = new LdifReader('current/roles.txt');
$staging = new LdifReader('new/roles.txt');
$diff = new LdifDiff($live, $staging);
file_put_contents('change/rolecreate.ldif', $diff->getCreates());
file_put_contents('change/roledelete.ldif', $diff->getDeletes());
// CREATE RESOURCE DIFF
$rslive = new LdifReader('current/resources.txt');
$rsstaging = new LdifReader('new/resources.txt');
$rsdiff = new LdifDiff($rslive, $rsstaging);
file_put_contents('change/resourcecreate.ldif', $rsdiff->getCreates());
file_put_contents('change/resourcedelete.ldif', $rsdiff->getDeletes());
// CREATE USERS UPDATE
$um = new UserManager($live);
$users = new LdifReader('current/users.txt');
$roles = $users->getAttributeTree('ou=Users,ou=SSO,dc=sso', 'ssoRole');
$roles = $um->translateRights($roles);
$roles['children'] = $um->minimiseRights($roles['children']);
$um2 = new UserManager($staging); 
$roles['children'] = $um2->findBestRolesFit($roles['children']);
file_put_contents('change/users.ldif', $um2->getLdif($roles['children']));

?>
Run the following commands: 
ldapmodify -x -f change/resourcecreate.ldif -D 'cn=admin,dc=sso' -w h0tp0tat0
ldapmodify -x -c -f change/rolecreate.ldif -D 'cn=admin,dc=sso' -w h0tp0tat0
ldapmodify -x -c -f change/users.ldif -D 'cn=admin,dc=sso' -w h0tp0tat0

Now check the users are OK. If so, clean up the old roles:

ldapmodify -x -f change/roledelete.ldif -D 'cn=admin,dc=sso' -w h0tp0tat0
ldapmodify -x -f change/resourcedelete.ldif -D 'cn=admin,dc=sso' -w h0tp0tat0
