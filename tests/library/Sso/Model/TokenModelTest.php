<?php

require_once('PHPUnit/Framework.php');

class Test_TokenModel extends PHPUnit_Framework_TestCase
{
	public $username = 'admin@myorg';
	public $password = 'password';

	public function setUp() {
		parent::setUp();
		$_SERVER['REMOTE_ADDR'] = 'localhost';
	}

    /**
     * @access public
     * @group token
     */
    public function testAuthoriseExpected() {
        $token = Sso_Model_Token::authorise($this->username, $this->password);

        $this->assertTrue($token instanceof Sso_Model_Token);
        $this->assertEquals(64, strlen($token->token));

		$this->assertTrue($token->isValid());
    }

	/**
	 * @expectedException Sso_Exception_NotFound
     * @access public
     * @group token
     */
    public function testAuthoriseInvalidCredentials() {
		$token = Sso_Model_Token::authorise('invaliduser', 'nonexistent');
		$this->fail('The above call should throw an exception');
    }

	/**
	 * @expectedException Sso_Exception_InvalidAuth
     * @access public
     * @group token
     */
    public function testAuthoriseInvalidPassword() {
		$token = Sso_Model_Token::authorise($this->username, $this->password.'rubbish');
		$this->fail('The above call should throw an exception');
    }

    /**
     * testAuthoriseNoUser 
     * 
     * @access public
     * @group token
     */
    public function testAuthoriseNoUser() {
		try {
			$token = Sso_Model_Token::authorise(null, 'fred');
			$this->fail('The above call should throw an exception');
		} catch (Sso_Exception_BadRequest $e) {
			$msg = $e->getMessage();
			$this->assertContains('Username', $msg);
			$this->assertContains('password', $msg);
			$this->assertContains('must be supplied', $msg);
		} catch (Exception $e) {
			$this->fail($e->getMessage());
		}
    }

    /**
     * testAuthoriseNoPass 
     * 
     * @access public
     * @group token
     */
    public function testAuthoriseNoPass() {
		try {
			$token = Sso_Model_Token::authorise('fred', null);
			$this->fail('The above call should throw an exception');
		} catch (Sso_Exception_BadRequest $e) {
			$msg = $e->getMessage();
			$this->assertContains('Username', $msg);
			$this->assertContains('password', $msg);
			$this->assertContains('must be supplied', $msg);
		} catch (Exception $e) {
			$this->fail($e->getMessage());
		}
    }
}
