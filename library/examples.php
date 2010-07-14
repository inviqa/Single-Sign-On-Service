<?php
/*
function __autoload($class)
{
	$path = dirname(__FILE__).'/'.str_replace('_','/',$class).'.php';
	include $path;
}


// Connecting
$c = new Sso_Ldap_Connection('ldap://localhost','dc=sso');
$c->setBind('cn=admin,dc=sso','user');

    $m = new Sso_Cache_Backend_Memcache();
    $m->addServer('localhost');
$cache = new Sso_Cache_Base($m);

$c->addCacheBackend($cache);
$token_string = "A213233";
$obj = $c->getBase()->search('ou=Sessions')->get(0);
if(!$obj instanceOf Sso_Ldap_Entry) {
     die('LDAP session root not found');
}

// Create Token
try {
	$token = $obj->addChild('ssoToken','sso='.$token_string);

    $token['ssoName'] = $token_string;
	$token['ssoUser'] = "sso=john@cw,sso=CW,ou=Users,ou=SSO,dc=sso";
	$token['ssoTimestamp'] = time();
	$token->save();
} catch(Exception $e) {
	print 'Failed adding token ' . $token_string . ': ' . $e->getMessage();
}

echo "fetch 1 \n";
// Test Fetch
try{
	$token_ldap = $c->getBase()->find('sso='.$token_string.',ou=Sessions,ou=SSO');
	if($token_ldap instanceOf Sso_Ldap_Entry) {
		$token = new Sso_Model_Token();
		$token->token = $token_ldap['sso'][0];
		$token->user = Sso_Model_User::getNameFromDn($token_ldap['ssoUser'][0]);
		$token->accessTime = $token_ldap['ssoTimestamp'][0];
		var_dump($token);
	}
} catch(Exception $e) {
	print '1. Error fetching ' . $token_string. ': '. $e->getMessage();
}

echo "fetch 2 \n";

// Test Fetch
try{
	$token_ldap = $c->getBase()->find('sso='.$token_string.',ou=Sessions,ou=SSO');
	if($token_ldap instanceOf Sso_Ldap_Entry) {
		$token = new Sso_Model_Token();
		$token->token = $token_ldap['sso'][0];
		$token->user = Sso_Model_User::getNameFromDn($token_ldap['ssoUser'][0]);
		$token->accessTime = $token_ldap['ssoTimestamp'][0];
		var_dump($token);
	}
} catch(Exception $e) {
	print '1.5. Error fetching ' . $token_string. ': '. $e->getMessage();
}


// Delete
$search_string ='sso='.$token_string.',ou=Sessions,ou=SSO'; 
try{
	echo "Deleting $token_string \n";
	$token_ldap = $c->getBase()->find($search_string);
	if($token_ldap instanceOf Sso_Ldap_Entry) {
		$token_ldap->delete();
	}
} catch (Exception $e) {
	print '2. Error deleting ' . $token_string. ': '. $e->getMessage();
}

echo "fetch 3 \n";
// Test Fetch again
try{
	$token_ldap = $c->getBase()->find('sso='.$token_string.',ou=Sessions,ou=SSO');
	if($token_ldap instanceOf Sso_Ldap_Entry) {
		$token = new Sso_Model_Token();
		$token->token = $token_ldap['sso'][0];
		$token->user = Sso_Model_User::getNameFromDn($token_ldap['ssoUser'][0]);
		$token->accessTime = $token_ldap['ssoTimestamp'][0];
		var_dump($token);
	}
} catch(Exception $e) {
	print '3. Error fetching ' . $token_string. ': '. $e->getMessage();
}
// Pause for cache to expire
sleep(20);
echo "fetch 4 \n";
// Try again
try{
	$token_ldap = $c->getBase()->find('sso='.$token_string.',ou=Sessions,ou=SSO');
	if($token_ldap instanceOf Sso_Ldap_Entry) {
		$token = new Sso_Model_Token();
		$token->token = $token_ldap['sso'][0];
		$token->user = Sso_Model_User::getNameFromDn($token_ldap['ssoUser'][0]);
		$token->accessTime = $token_ldap['ssoTimestamp'][0];
		var_dump($token);
	}
} catch(Exception $e) {
	print '4. Error fetching ' . $token_string. ': '. $e->getMessage();
}

/*
// Get all orgs
$org = $c->getBase()->search('ou=Users')->get(0);

// add org child
$child = $org->addChild('ssoOrganisation','sso=MyOrg');
$child['ssoName'] = 'MyOrg';
$child->save();
var_dump($child->getDn(),$child->getAttributes());

// get child org
$myorg = $org->find('sso=MyOrg');
$myorg->debug();


// get all tokens
$tokens = $c->getBase()->search('objectClass=ssoToken');
foreach($tokens as $t) {
    $t->debug();
}


$root = $c->getBase()->find('ou=Users,ou=SSO');
$root->debug();


// get a token
$token = $c->getBase()->find('sso=e24fe45588a1dec7b300a3cc70250b12aa68c34381d3691b4d50c251358913bc,ou=Sessions,ou=SSO');
$token->debug();


// add token
$top = $c->getBase()->search('ou=Sessions')->get(0);
// $top->debug();

$token = $top->addChild('ssoToken','sso=b0daafd9b0c176361f839e1cb68baaba593dec86138a98390349f77de787386a');
$token['ssoName'] = 'b0daafd9b0c176361f839e1cb68baaba593dec86138a98390349f77de787386a';
$token['ssoUser'] = 'sso=john@cw,sso=CW,ou=Users,ou=SSO,dc=sso';
$token['ssoTimestamp'] = '1249375621';
$token->save();
$token->debug();

// add user
$myorg = $c->getBase()->find('sso=MyOrg,ou=Users,ou=SSO');
$myorg->debug();
$me = $myorg->addChild('ssoUser','sso=sarah@ib');
$me['ssoUsername'] = 'lorna@ib';
$me['ssoPassword'] = 'password';
$me->save();
$me->debug();
$attrib = $me->addChild('ssoAttribute','sso=foo');
$attrib['ssoValue'] = 'bar';
$attrib->save();

*/
/*
$thing = $c->getBase()->find('sso=MyOrg,ou=Users,ou=SSO');

$string = 'sso=desc,sso=MyOrg,ou=Users,ou=SSO';
$sub = $c->getBase()->find($string);
echo get_class($sub);
var_dump($sub['sso']);
$string = 'sso=foo,sso=MyOrg,ou=Users,ou=SSO';
$sub = $c->getBase()->find($string);
echo get_class($sub);
var_dump($sub['sso']);
*/
/*
// two ways to get a user
$user = $c->getBase()->find('sso=sarah@ib,sso=MyOrg,ou=Users,ou=SSO');
$children = $user->search('objectClass=ssoAttribute,sso=sarah@ib,sso=MyOrg,ou=Users,ou=SSO');
$user->debug();
foreach($children as $child) {
        $child->debug();
}


// add an attribute
$attrib = $user->addChild('ssoAttribute','sso=foo');
$attrib['ssoValue'] = 'bar';
$attrib->save();


// search users
foreach ($c->getBase()->search('objectClass=ssoUser')->sort(array('sso' => 'desc')) as $user) {
    $user->debug();
}

// Get all users
foreach ($c->getBase()->search('objectClass=ssoUser') as $user) {
	echo $user['ssoUsername'][0];
}

// Get a specific user
$user = $c->getBase()->search('sso=john@cw')->get(0);

// Get attributes
$array = $user->getAttribute('attribute');
echo $array[0];

// Get attributes array syntax
$user['atribute'][0];

// Filter a resultset
foreach ($c->getBase()->search('objectClass=ssoUser')->includeFilter(array('sso'=>'john@cw')) as $user) {
	echo $user['ssoUsername'][0];
}

// Wildcard filter
foreach ($c->getBase()->search('objectClass=ssoUser')->includeFilter(array('sso'=>'*@cw')) as $user) {
	echo $user['ssoUsername'][0];
}

// Fuzzy filter
foreach ($c->getBase()->search('objectClass=ssoUser')->includeFilter(array('sso'=>'john@c'),true) as $user) {
	echo $user['ssoUsername'][0];
}

// Filter out a specific user 
foreach ($c->getBase()->search('objectClass=ssoUser')->excludeFilter(array('sso'=>'john@cw')) as $user) {
	echo $user['ssoUsername'][0];
}

// Sort a resultset
foreach ($c->getBase()->search('objectClass=ssoUser')->sort(array('sso')) as $user) {
	echo $user['ssoUsername'][0];
}

// Sort a resultset decending
foreach ($c->getBase()->search('objectClass=ssoUser')->sort(array('sso' => 'desc')) as $user) {
	echo $user['ssoUsername'][0];
}

// Sort a resultset by multiple columns
foreach ($c->getBase()->search('objectClass=ssoUser')->sort(array('ssoUsername','ssoPassword')) as $user) {
	echo $user['ssoUsername'][0];
}
*/
