<?PHp

require_once 'TestHelper.php';

class controllers_PolicyControllerTest extends ControllerTestCase {

    /**
     * testPostExpected 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testPostExpected() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name' => 'new policy',
            'organisation' => 72
        ));
        $request->setCookie('token', $this->token);
        $this->dispatch('/policy');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check it sent a token to us
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'policy');

    }

    /**
     * testPostMissingToken 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testPostMissingToken() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPost(array(
            'name' => 'new policy'
        ));
        $this->dispatch('/policy');

        // 401 expected
        $this->assertResponseCode('401');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType,'error');
        $this->assertEquals($response->errorCode,'SSO401');
    }

    /**
     * testPostMissingName 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testPostMissingName() {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setCookie('token', $this->token);
        $this->dispatch('/policy');

        // 400 expected
        $this->assertResponseCode('400');

        // error 
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType,'error');
        $this->assertEquals($response->errorCode,'SSO400');
    }

    /**
     * testDeleteNoToken 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testDeleteNoToken() {
        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $this->dispatch('/policy');
		
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
     * @group policy
     * @return void
     */
    public function testDeleteExpected() {
        $request = $this->getRequest();
        $request->setMethod('DELETE');
        $request->setCookie('token', $this->token);
        $this->dispatch('/policy/1');

        // check its a 200 OK
        $this->assertResponseCode('200');

        // check delete returns true
        $response = json_decode($this->getResponse()->getBody());
        $this->assertEquals($response->contentType, 'policy');
    }

    /**
     * testNonMethod 
     * 
     * @access public
     * @group policy
     * @return void
    public function testNonMethod() {
        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->setCookie('token', $this->token);
        $this->dispatch('/policy/rubbish3');

		var_dump($this->getResponse()->getBody());exit;

		// should be 405
        $this->assertResponseCode('405');
    }
     */
}
