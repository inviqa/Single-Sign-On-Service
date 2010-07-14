<?php

require_once 'TestHelper.php';

class controllers_OrganisationControllerTest extends ControllerTestCase
{
	/**
	 * New org name used for testing
	 * @var string
	 */
	private $newName = 'new organisation';

	/**
	 * New child org name used for testing
	 * @var string
	 */
	private $childName = 'childorg';

	/**
	 * @var string
	 */
	private $roleName = 'test role';

	public function setUp() {
		parent::setUp();
		$this->deleteIfExists($this->newName);
		$this->deleteIfExists($this->childName);
		$this->deleteRole($this->roleName);
		
		$this->_clearScope();
	}

	/**
	 * @param string $org
	 */
	private function deleteIfExists($org) {
		$request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($org));
	}

	/**
     * testPostExpected 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testPostExpected() {
		$this->createRole($this->roleName);
		$this->_clearScope();

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name'        => $this->newName,
			'description' => 'test org',
			'role'        => array($this->roleName),
		));
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check it sent a token to us
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('organisation', $response->contentType);
        $this->assertEquals($response->data->name, $this->newName);
		$this->assertEquals($response->data->id,   $this->newName);
		$this->assertType('array', $response->data->roles);
		$this->assertContains($this->roleName, $response->data->roles);
    }

    /**
     * testPostExpectedAndParent 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testPostExpectedAndParent() {
		//add parent
		$this->testPostExpected();

		//add child
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
			'name' => $this->childName,
        ));
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName)); //parent

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check it's a valid org object 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('organisation', $response->contentType);
        $this->assertEquals($response->data->name, $this->childName);
		$this->assertContains($this->newName,   $response->data->id);
		$this->assertContains($this->childName, $response->data->id);
    }

    /**
     * testPostMissingToken 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testPostMissingToken() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name' => $this->newName
        ));
        $this->dispatch('/organisation');

        // 400 expected
        $this->assertResponseCode('401');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType,'error');
        $this->assertEquals($response->errorCode, 'SSO401');
    }

    /**
     * testPostMissingName 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testPostMissingName() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation');

        // 400 expected
        $this->assertResponseCode('400');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType,'error');
        $this->assertEquals($response->errorCode, 'SSO400');
    }

    /**
     * testDeleteNoToken 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testDeleteNoToken() {
        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $this->dispatch('/organisation/1');

        // 401 expected
        $this->assertResponseCode('401');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType,'error');
        $this->assertEquals($response->errorCode, 'SSO401');
    }

    /**
     * testDeleteExpected 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testDeleteExpected() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check delete returns 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('organisation', $response->contentType);
		$this->assertEquals('true', $response->data[0]);

		// check the org doesn't exist anymore
		$request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('404');

        // error
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'error');
        $this->assertEquals($response->errorCode, 'SSO404');
    }

    /**
     * testNotFound
     *
     * @access public
     * @group organisation
     * @return void
     */
    public function testNotFound() {
        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/rubbish');

        // should be 404
        $this->assertResponseCode('404');
    }

    /**
     * testNonMethod 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testNonMethod() {
		//add parent
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName).'/rubbish');

        // should be 405
        $this->assertResponseCode('405');
    }

     /**
     * testGetAllExpected 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testGetAllExpected() {
        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
		$this->dispatch('/organisation');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('organisation', $response->contentType);
    }

    /**
     * testGetOneExpected 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testGetOneExpected() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get an org
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('organisation', $response->contentType);
        $this->assertEquals($response->data->id, $this->newName);
    }

    /**
     * testGetMissingToken
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testGetMissingToken() {
        $request = $this->getRequest();
        $request->setMethod('GET');
        $this->dispatch('/organisation');

        // 400 expected
        $this->assertResponseCode('401');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'error');
        $this->assertEquals($response->errorCode, 'SSO401');
    }

    /**
     * testGetUsersExpected 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testGetUsersExpected() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName).'/user');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get users
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'user');
    }

    /**
     * testPutExpected 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testPutExpected() {
		$this->testPostExpected();

		$updatedDesc = 'updated description';
        $request = $this->getRequest();
        $request->setMethod('PUT');
        $params = array('description' => $updatedDesc);
        $request->setRawBody(http_build_query($params));
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('organisation', $response->contentType);
        $this->assertEquals($response->data->description, $updatedDesc);
        $this->assertEquals($response->data->name, $this->newName);
    }

    /**
     * testGetUser
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testGetUser() {
		$this->testPostExpected();
		
        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName).'/user');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'user');
    }

    /**
     * testGetChildren 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testGetChildren() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName).'/children');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('organisation', $response->contentType);
    }

    /**
     * testGetRole 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testGetRole() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName).'/role');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'role');
    }

    /**
     * testPostRole 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testPostRole() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setCookie('token', $this->token);
        $request->setPost(array(
            'id' => 14
            ));
        $this->dispatch('/organisation/'.urlencode($this->newName).'/role');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'role');
    }

    /**
     * testDeleteRoleExpected 
     * 
     * @access public
     * @group organisation
     * @return void
     */
    public function testDeleteRoleExpected() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName).'/role/'.$this->roleName);

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check delete returns 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'role');
		$this->assertEquals('true', $response->data[0]);

		$this->_clearScope();

		// fetch it again and make sure the role isn't there anymore
		$request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/organisation/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('organisation', $response->contentType);
		$this->assertType('array', $response->data->roles);
		$this->assertNotContains($this->roleName, $response->data->roles);
    }

}
