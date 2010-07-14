<?php

require_once 'TestHelper.php';

class controllers_TokenControllerTest extends ControllerTestCase
{
    /**
     * testDeleteNoToken 
     * 
     * @access public
     * @group token
     */
    public function testDeleteNoToken() {
        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/token');

        // 400 expected
        $this->assertResponseCode('400');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType,'error');
        $this->assertEquals($response->errorCode, 'SSO400');
    }

    /**
     * testDeleteNoAuth
     * 
     * @access public
     * @group token
     */
    public function testDeleteNoAuth() {
        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $this->dispatch('/token/12345');

        // 401 expected
        $this->assertResponseCode('401');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType,'error');
        $this->assertEquals($response->errorCode,'SSO401');
    }

    /**
     * testDeleteExpected 
     * 
     * @access public
     * @group token
     */
    public function testDeleteExpected() {
        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/token/'.$this->token);
        // check its a 200 OK
        $this->assertResponseCode('200');

        // check delete returns true
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('token', $response->contentType);
		$this->assertEquals('true',  $response->data[0]);

		// try doing an operation with the deleted token
		$request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/role');

		$this->assertResponseCode('401');

		$response = json_decode($this->getResponse()->getBody());
		$this->assertEquals('error',  $response->contentType);
        $this->assertEquals('SSO401', $response->errorCode);
    }

    /**
     * testPostMissingParams 
     * 
     * @access public
     * @group token
     */
    public function testPostMissingParams() {
		$this->_clearScope();
		
        $request = $this->getRequest();
        $request->setMethod('POST');
        $this->dispatch('/token');

        // 400 expected
        $this->assertResponseCode('400');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType,'error');
        $this->assertEquals($response->errorCode,'SSO400');
    }

    /**
     * testPostMissingPassword 
     * 
     * @access public
     * @group token
     */
    public function testPostMissingPassword() {
		$this->_clearScope();
		
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'username' => 'fred'
        ));
        $this->dispatch('/token');

        // 400 expected
        $this->assertResponseCode('400');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType,'error');
        $this->assertEquals($response->errorCode,'SSO400');
    }

    /**
     * testPostMissingUsername 
     * 
     * @access public
     * @group token
     */
    public function testPostMissingUsername() {
		$this->_clearScope();
		
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'password' => 'fred'
            ));
        $this->dispatch('/token');

        // 400 expected
        $this->assertResponseCode('400');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType,'error');
        $this->assertEquals($response->errorCode,'SSO400');
    }

    /**
     * testPostExpected 
     * // REMOVED - this test causes a 500 because there isn't cookie support in the response object yet
     * 
     * @access public
     * @group token
    public function testPostExpected() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'username' => 'fred',
            'password' => 'fred'
            ));
        $this->dispatch('/token');

        var_dump($this->getResponse()->getBody());
        // check its a 200 OK
        $this->assertResponseCode('200');

        // check it sent a token to us
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('token', $response->contentType);
        $this->assertEquals(strlen($response->token), 64);
    }
     */

    /**
     * testGetExpected 
     * 
     * @access public
     * @group token
     */
    public function testGetExpected() {
        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/token/'.$this->token);

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check token fetch returns true
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals('token', $response->contentType);
    }

    /**
     * testNonMethod 
     * 
     * @access public
     * @group token
    public function testNonMethod() {
        $this->dispatch('/token/rubbish');

        // should be 405
        $this->assertResponseCode('405');
    }
     */
}
