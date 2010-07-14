<?php
include('Zend/Config/Ini.php');


function __autoload($class)
{
	$path = dirname(dirname(__FILE__)).'/library/'.str_replace('_','/',$class).'.php';
	include $path;
}

$config = new Zend_Config_Ini(dirname(dirname(__FILE__)) . '/application/configs/application.ini', 'production');
Sso_Model_Base::setConfig($config);
$connection = new Sso_Ldap_Connection($config->ldap->host, $config->ldap->dc);
$connection->setBind($config->ldap->user, $config->ldap->pass);

// TODO get only expired tokens
$target_time = time() - (int)$config->token->time_to_live;
$tokens = $connection->getBase()->search("objectClass=ssoToken");
foreach($tokens as $t) {
    // if the timestamp on the token is before our target
    if((int)$t['ssoTimestamp'][0] <= $target_time) {
        $t->delete();
    }
}

