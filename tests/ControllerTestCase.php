<?php
require_once 'Zend/Loader/Autoloader.php';
require_once 'Zend/Application.php';
require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';

abstract class ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase
{
	/**
	 * @var Zend_Application
	 */
    public $application;

	/**
	 * @var string valid authentication token
	 */
	public $token;

	/**
	 * @var string valid authentication username
	 */
	public $username = 'admin@myorg';

	/**
	 * @var string valid authentication password
	 */
	public $password = 'password';

    public function setUp() {
        $this->application = new Zend_Application(
            APPLICATION_ENV,
            APPLICATION_PATH . '/configs/application.ini'
        );

        $this->bootstrap = array($this, 'appBootstrap');
        parent::setUp();

		$this->login();
    }

    public function appBootstrap() {
        $this->frontController->addControllerDirectory(APPLICATION_PATH . '/controllers');
        $this->frontController->setParam('bootstrap', $this->application->getBootstrap());
        $this->frontController->setParam('noViewRenderer', true);
        $this->application->bootstrap();
		set_include_path(FIXED_INCLUDE_PATH);
    }

	protected function login() {
		$request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'username' => $this->username,
			'password' => $this->password,
		));
		$_SERVER['REMOTE_ADDR'] = 'localhost';
        $this->dispatch('/token');
		$response = json_decode($this->getResponse()->getBody());
		$this->assertEquals('token', $response->contentType, 'Response was: '.$this->getResponse()->getBody());
		$this->token = $response->data->token;
	}

	/**
	 * Clear the scope
	 */
	protected function _clearScope() {
		$this->resetRequest();
		$this->appBootstrap();
		$_COOKIE = array();
		$_GET    = array();
		$_POST   = array();
	}

	/**
	 * Create a new organisation
	 *
	 * @param string $name
	 */
	protected function createOrg($name) {
		$this->_clearScope();
		$request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name'        => $name,
			'description' => 'test org',
		));
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation');
	}

	/**
	 * Delete an organisation
	 *
	 * @param string $name
	 */
	protected function deleteOrg($name) {
		$this->_clearScope();
		$request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($name));
	}

	/**
	 * Create a new role
	 *
	 * @param string $name
	 */
	protected function createRole($name) {
		$this->_clearScope();
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name'        => $name,
			'description' => 'test role',
        ));
        $request->setCookie('token', $this->token);
        $this->dispatch('/role');
	}

	/**
	 * Delete a role
	 *
	 * @param string $name
	 */
	protected function deleteRole($name) {
		$this->_clearScope();
		$request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/role/'.urlencode($name));
	}
}
