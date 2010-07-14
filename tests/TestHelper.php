<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'testing'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

define("FIXED_INCLUDE_PATH", get_include_path());

// adding autoloader and zend application 
// to set up the environment to run the tests
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();

$application = new Zend_Application(
	APPLICATION_ENV,
	APPLICATION_PATH . '/configs/application.ini'
);

$bootstrap = $application->getBootstrap();
$bootstrap->bootstrap();

require_once 'ControllerTestCase.php';
