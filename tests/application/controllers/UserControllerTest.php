<?php

require_once 'TestHelper.php';

class controllers_UserControllerTest extends ControllerTestCase
{
	/**
	 * New user name used for testing
	 * @var string
	 */
	private $newName = 'new user';

	/**
	 * New child user name used for testing
	 * @var string
	 */
	private $childName = 'childuser';

	/**
	 * @var string
	 */
	private $orgName = 'test org';

	/**
	 * @var string
	 */
	private $roleName = 'test role';

	public function setUp() {
		parent::setUp();
		$this->deleteIfExists($this->newName);
		$this->deleteIfExists($this->childName);
		$this->deleteOrg($this->orgName);
		$this->deleteRole($this->roleName);
		$this->_clearScope();
	}

	public function tearDown() {
		//$this->deleteOrg($this->orgName);
		//$this->deleteRole($this->roleName);
	}

	/**
	 * @param string $username
	 */
	private function deleteIfExists($username) {
		$this->_clearScope();
		$request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/user/'.urlencode($username));
	}

    /**
     * testPostExpected 
     * 
     * @access public
     * @group user
     */
    public function testPostExpected() {
		$this->createRole($this->roleName);
		$this->createOrg($this->orgName);
		$this->_clearScope();

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'username'     => $this->newName,
			'password'     => 'somepass',
			'organisation' => $this->orgName,
			'role'         => array($this->roleName),
        ));
        $request->setCookie('token', $this->token);
        $this->dispatch('/user');
//var_dump('POST EXPECTED: '.$this->getResponse()->getBody());
        // check its a 200 OK
        $this->assertResponseCode('200');

        // check it sent a token to us
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('user', $response->contentType);
        $this->assertEquals($this->newName, $response->data->username);
        $this->assertEquals($this->orgName, $response->data->organisation);
		$this->assertType('array', $response->data->roles);
		$this->assertContains($this->roleName, $response->data->roles);
    }

    /**
     * testPostExpectedAndParent 
     * 
     * @access public
     * @group user
     */
    public function testPostExpectedAndParent() {
		$this->createOrg($this->orgName);
		$this->_clearScope();

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'username'     => $this->newName,
			'password'     => 'somepass',
			'organisation' => $this->orgName,
        ));
        $request->setCookie('token', $this->token);
        $this->dispatch('/user');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check it sent a token to us
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('user', $response->contentType);
        $this->assertEquals($this->newName, $response->data->username);
        $this->assertEquals($this->orgName, $response->data->organisation);
    }

    /**
     * testPostMissingToken 
     * 
     * @access public
     * @group user
     */
    public function testPostMissingToken() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'username'     => $this->newName,
        ));
        $this->dispatch('/user');

        // 400 expected
        $this->assertResponseCode('401');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('error',  $response->contentType);
        $this->assertEquals('SSO401', $response->errorCode);
    }

    /**
     * testPostMissingName 
     * 
     * @access public
     * @group user
     */
    public function testPostMissingName() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setCookie('token', $this->token);
        $this->dispatch('/user');

        // 400 expected
        $this->assertResponseCode('400');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('error',  $response->contentType);
        $this->assertEquals('SSO400', $response->errorCode);
    }

    /**
     * testPostMissingName
     *
     * @access public
     * @group user
     */
    public function testPostMissingParams() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'username' => $this->newName,
        ));
        $request->setCookie('token', $this->token);
        $this->dispatch('/user');

        // 400 expected
        $this->assertResponseCode('400');

        // error
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('error',  $response->contentType);
        $this->assertEquals('SSO400', $response->errorCode);
		$this->assertContains('Missing parameter',      $response->errorMessage);
		$this->assertContains('Expected: password',     $response->errorMessage);
		$this->assertContains('Expected: organisation', $response->errorMessage);

    }

    /**
     * testDeleteNoToken 
     * 
     * @access public
     * @group user
     */
    public function testDeleteNoToken() {
        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $this->dispatch('/user/1');

        // 401 expected
        $this->assertResponseCode('401');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('error',  $response->contentType);
        $this->assertEquals('SSO401', $response->errorCode);
    }

    /**
     * testDeleteExpected 
     * 
     * @access public
     * @group user
     */
    public function testDeleteExpected() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/user/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check delete returns 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('user', $response->contentType);
		$this->assertEquals('true', $response->data[0]);

		$this->_clearScope();

		$request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/user/'.urlencode($this->newName));

        // check it was deleted
        $this->assertResponseCode('404');
    }

    /**
     * testNonMethod 
     * 
     * @access public
     * @group user
    public function testNonMethod() {
        $this->dispatch('/user/rubbish');

        // should be 405
        $this->assertResponseCode('405');
    }
     */

    /**
     * testGetAllExpected 
     * 
     * @access public
     * @group user
     
    public function testGetAllExpected() {
        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/user');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('user', $response->contentType);
		$this->assertType('array', $response->data);
    }
	*/

    /**
     * testGetOneExpected 
     * 
     * @access public
     * @group user
     */
    public function testGetOneExpected() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/user/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('user', $response->contentType);
        $this->assertEquals($this->newName, $response->data->username);
    }

    /**
     * testGetMissingToken
     * 
     * @access public
     * @group user
     */
    public function testGetMissingToken() {
        $request = $this->getRequest();
        $request->setMethod('GET');
        $this->dispatch('/user');

        // 400 expected
        $this->assertResponseCode('401');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('error',  $response->contentType);
        $this->assertEquals('SSO401', $response->errorCode);
    }

    /**
     * testPutExpected 
     * 
     * @access public
     * @group user
     */
    public function testPutExpected() {
		$this->testPostExpected();

		$newDesc = 'Ginny Weasley';
        $request = $this->getRequest();
        $request->setMethod('PUT');
        $params = array('description' => $newDesc);
        $request->setRawBody(http_build_query($params));
        $request->setCookie('token', $this->token);
        $this->dispatch('/user/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('user',   $response->contentType);
        $this->assertEquals($newDesc, $response->data->description);
    }

    /**
     * testGetRole 
     * 
     * @access public
     * @group user
     */
    public function testGetRole() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/user/'.urlencode($this->newName).'/role');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('role', $response->contentType);
    }

    /**
     * testPostRole 
     * 
     * @access public
     * @group user
     */
    public function testPostRole() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setCookie('token', $this->token);
        $request->setPost(array(
            'id' => 14
        ));
        $this->dispatch('/user/'.urlencode($this->newName).'/role');

        // not supported anymore
        $this->assertResponseCode('400');
    }

    /**
     * testDeleteRoleExpected 
     * 
     * @access public
     * @group user
     */
    public function testDeleteRoleExpected() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/user/'.urlencode($this->newName).'/role/'.$this->roleName);

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check delete returns 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('role', $response->contentType);
		$this->assertEquals('true', $response->data[0]);

		$this->_clearScope();

		// fetch it again and make sure the role isn't there anymore
		$request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/user/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('user', $response->contentType);
		$this->assertType('array', $response->data->roles);
		$this->assertNotContains($this->roleName, $response->data->roles);
    }
}
