<?php

require_once 'TestHelper.php';

class controllers_ResourceControllerTest extends ControllerTestCase
{
	/**
	 * New resource name used for testing
	 * @var string
	 */
	private $newName = 'new resource';

	/**
	 * New child resource name used for testing
	 * @var string
	 */
	private $childName = 'childres';

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
        $this->dispatch('/resource/'.urlencode($id));
	}

    /**
     * testPostExpected 
     * 
     * @access public
     * @group resource
     */
    public function testPostExpected() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name'        => $this->newName,
			'description' => 'res desc',
        ));
        $request->setCookie('token', $this->token);
        $this->dispatch('/resource');
		
        // check its a 200 OK
        $this->assertResponseCode('200');

        // check it sent a token to us
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'resource');

    }

    /**
     * testPostExpectedAndParent 
     * 
     * @access public
     * @group resource
     */
    public function testPostExpectedAndParent() {
		// add parent
		$this->testPostExpected();

		// add child
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name'   => $this->childName,
            'parent' => $this->newName
        ));
        $request->setCookie('token', $this->token);
        $this->dispatch('/resource/'.urlencode($this->newName));

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check it sent a token to us
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'resource');
    }

    /**
     * testPostMissingToken 
     * 
     * @access public
     * @group resource
     */
    public function testPostMissingToken() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name' => $this->newName,
        ));
        $this->dispatch('/resource');

        // 401 expected
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
     * @group resource
     */
    public function testPostMissingName() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setCookie('token', $this->token);
        $this->dispatch('/resource');

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
     * @group resource
     */
    public function testDeleteNoToken() {
        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $this->dispatch('/resource');

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
     * @group resource
     */
    public function testDeleteExpected() {
        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/resource/1');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check delete returns true
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'resource');
    }

    /**
     * testNonMethod 
     * 
     * @access public
     * @group resource
     */
    public function testNonMethod() {
        $this->dispatch('/resource/rubbish');

        // should be 405
        $this->assertResponseCode('405');
    }
}
