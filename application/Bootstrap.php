<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    protected function _initRequest()
    {
        $this->bootstrap('frontController');
        $front = $this->getResource('frontController');

        $router = $front->getRouter();
        $action = 'route';

        $route = new Zend_Controller_Router_Route(
            ':controller/*',
            array('module' => 'default', 'action' => $action)
        );
        $router->addRoute('default', $route);

        $route = new Zend_Controller_Router_Route(
            ':controller/:identifier',
            array('module' => 'default', 'action' => $action)
        );
        $router->addRoute('with_identifier', $route);

        $route = new Zend_Controller_Router_Route(
            ':controller/:identifier/:link',
            array('module' => 'default', 'action' => $action)
        );
        $router->addRoute('linked', $route);

        $route = new Zend_Controller_Router_Route(
            ':controller/:identifier/:link/:link_identifier',
            array('module' => 'default', 'action' => $action)
        );
        $router->addRoute('linked_identifier', $route);

        return;

    }


    protected function _initAutoload()
    {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Sso_');
        return $autoloader;
    }

    protected function _initHelpers()
    {
        Zend_Controller_Action_HelperBroker::addPath(APPLICATION_PATH .'/controllers/helpers');
    }

    protected function _initConfig()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
    	Sso_Model_Base::setConfig($config);
    	try {
    		$writer = $this->_logFileWriter($config->log->sso, 'sso.log');
    	} catch (Zend_Log_Exception $e) {
    		// @todo send email to alert logging has failed
    		$writer = new Zend_Log_Writer_Null();
    	}
        Zend_Registry::set('log', new Zend_Log($writer));
        return $config;
    }

    /**
     * $_logFileWriter - try and find a folder to writer
     * 
     * @param  string $path
     * @param  string $fileName
     * @return Zend_Log_Writer_Abstract
     */
    private function _logFileWriter($path, $fileName)
    {
    	try {
    		$writer = new Zend_Log_Writer_Stream($path . DIRECTORY_SEPARATOR . $fileName);
    	} catch (Zend_Log_Exception $e) {
    		try {
    			if (isset($_SERVER['TEMP'])) {
	    			$writer = new Zend_Log_Writer_Stream($_SERVER['TEMP'] . DIRECTORY_SEPARATOR . $fileName);
    			} elseif ($tmp = getenv('TEMP')) {
    				$writer = new Zend_Log_Writer_Stream($tmp . DIRECTORY_SEPARATOR . $fileName);
    			} else {
    				throw new Zend_Log_Exception('TEMP not found');
    			}
    		} catch (Zend_Log_Exception $e) {
    			try {
    				if (isset($_SERVER['TMP'])) {
	    				$writer = new Zend_Log_Writer_Stream($_SERVER['TMP'] . DIRECTORY_SEPARATOR . $fileName);
    				} elseif ($tmp = getenv('TMP')) {
	    				$writer = new Zend_Log_Writer_Stream($tmp . DIRECTORY_SEPARATOR . $fileName);
    				} else {
    					throw new Zend_Log_Exception('TMP not found');
    				}
    			} catch (Zend_Log_Exception $e) {
   					$writer = new Zend_Log_Writer_Stream('/tmp/' . $fileName);
    			}
    		}
    	}
    	return $writer;
    }
}

