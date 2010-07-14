<?php

require_once 'TestHelper.php';

class controllers_RoleControllerTest extends ControllerTestCase
{
	/**
	 * New role name used for testing
	 * @var string
	 */
	private $newName = 'new role';

	/**
	 * New child role name used for testing
	 * @var string
	 */
	private $childName = 'childrole';

	/**
	 * @var string role description
	 */
	private $roleDesc = 'role description';

	public function setUp() {
		parent::setUp();
		$this->deleteIfExists($this->newName);
		$this->deleteIfExists($this->childName);

		$this->_clearScope();
	}

	/**
	 * @param string $id
	 */
	private function deleteIfExists($id) {
		$request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/role/'.urlencode($id));
	}

    /**
     * testPostExpected 
     * 
     * @access public
     * @group role
     * @return void
     */
    public function testPostExpected() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name'        => $this->newName,
			'description' => $this->roleDesc,
        ));
        $request->setCookie('token', $this->token);
        $this->dispatch('/role');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check it sent a token to us
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('role', $response->contentType);
        $this->assertEquals($this->newName,  $response->data->name);
        $this->assertEquals($this->newName,  $response->data->id);
		$this->assertEquals($this->roleDesc, $response->data->description);
		$this->assertTrue(empty($response->parent));
    }

    /**
     * testPostExpectedAndParent 
     * 
     * @access public
     * @group role
     * @return void
     */
    public function testPostExpectedAndParent() {
		$parent = 'fraggle';
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name'   => $this->newName,
            'parent' => $parent,
        ));
        $request->setCookie('token', $this->token);
        $this->dispatch('/role');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check it sent a token to us
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('role', $response->contentType);
        $this->assertEquals($this->newName, $response->data->name);
        $this->assertEquals($parent,        $response->data->parent);
    }

    /**
     * testPostMissingToken 
     * 
     * @access public
     * @group role
     * @return void
     */
    public function testPostMissingToken() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name' => $this->newName
        ));
        $this->dispatch('/role');

        // 400 expected
        $this->assertResponseCode('401');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'error');
        $this->assertEquals($response->errorCode, 'SSO401');
    }

    /**
     * testPostMissingName 
     * 
     * @access public
     * @group role
     * @return void
     */
    public function testPostMissingName() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setCookie('token', $this->token);
        $this->dispatch('/role');

        // 400 expected
        $this->assertResponseCode('400');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'error');
        $this->assertEquals($response->errorCode, 'SSO400');
    }

    /**
     * testDeleteNoToken 
     * 
     * @access public
     * @group role
     * @return void
     */
    public function testDeleteNoToken() {
        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $this->dispatch('/role');

        // 401 expected
        $this->assertResponseCode('401');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'error');
        $this->assertEquals($response->errorCode, 'SSO401');
    }

    /**
     * testDeleteExpected 
     * 
     * @access public
     * @group role
     * @return void
     */
    public function testDeleteExpected() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/role/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check delete returns true
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('role', $response->contentType);
    }

    /**
     * testNonMethod 
     * 
     * @access public
     * @group role
     * @return void
     */
    public function testNonMethod() {
        $this->dispatch('/role/rubbish');

        // should be 405
        $this->assertResponseCode('405');
    }

    /**
     * testGetAllExpected 
     * 
     * @access public
     * @group role
     * @return void
     */
    public function testGetAllExpected() {
        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/role');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('role', $response->contentType);
    }

    /**
     * testGetOneExpected 
     * 
     * @access public
     * @group role
     * @return void
     */
    public function testGetOneExpected() {
		$this->testPostExpected();
		
        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/role/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('role', $response->contentType);
        $this->assertEquals($this->newName, $response->data->name);
    }

    /**
     * testGetChildren 
     * 
     * @access public
     * @group role
     * @return void
     */
    public function testGetChildren() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/role/'.urlencode($this->newName).'/children');

        // check its a 200 OK
        $this->assertResponseCode('200');

		// check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('role', $response->contentType);
		$this->assertType('array',  $response->data);
    }

    /**
     * testGetRight 
     * 
     * @access public
     * @group role
     * @return void
     */
    public function testGetRight() {
		$this->testPostExpected();
		
        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/role/'.urlencode($this->newName).'/right');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check we get a list
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'right');
    }

    /**
     * testPutRight 
     * 
     * @access public
     * @group role
     * @return void
    public function testPutRight() {
		$this->testPostExpected();

        $request = $this->getRequest();
        $request->setMethod('PUT');
        $request->setPost(array(
            array('resource' => 127, 'foo' => 'whisper'),
            array('resource' => 632, 'bar' => 'raspberry')
        ));
        $request->setCookie('token', $this->token);
        $this->dispatch('/role/'.urlencode($this->newName).'/right');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check it sent a token to us
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'right');
    }
     */
}
