<?php

require_once('PHPUnit/Framework.php');

class Test_UserModel extends PHPUnit_Framework_TestCase
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
	private $orgName = 'new organisation';

	public function setUp() {
		parent::setUp();
		$this->deleteIfExists($this->newName);
		$this->deleteIfExists($this->childName);
		$this->deleteOrganisation($this->orgName);
	}

	/**
	 * @param string $id user id
	 */
	private function deleteIfExists($id) {
		try {
			$user = Sso_Model_User::fetch($id, null, null);
			$user->delete();
		} catch (Sso_Exception_NotFound $e) {
			// ignore
		} catch (Exception $e) {
			$this->fail($e->getMessage());
		}
	}

	private function addOrganisation($name) {
		$params = array(
			'name'        => $name,
			'description' => 'res desc',
		);
        Sso_Model_Organisation::create(null, $params);
    }

	private function deleteOrganisation($name) {
		try {
			$organisation = Sso_Model_Organisation::fetch($name, null, null);
			$organisation->delete();
		} catch (Sso_Exception_NotFound $e) {
			// ignore
		} catch (Exception $e) {
			$this->fail($e->getMessage());
		}
	}

	/**
	 *
	 * @param Sso_Model_User $res
	 */
	private function assertIsUser($res) {
		$this->assertTrue($res instanceof Sso_Model_User, 'Not a Sso_Model_User');
		$this->assertObjectHasAttribute('id',     $res);
		$this->assertObjectHasAttribute('username',     $res);
		$this->assertObjectHasAttribute('organisation', $res);
		$this->assertTrue(!empty($res->id));
		$this->assertTrue(!empty($res->username));
	}

    /**
     * testAddExpected
     *
     * @access public
     * @group user
     * @return void
     */
    public function testAddExpected() {
		$this->addOrganisation($this->orgName);
		
		$params = array(
			'username'     => $this->newName,
			'description'  => 'res desc',
			'password'     => 'some passwd',
			'organisation' => $this->orgName,
		);
        $result = Sso_Model_User::create($params);

		$this->assertIsUser($result);
        $this->assertEquals($this->newName, $result->username);
        $this->assertTrue(empty($result->parent));
    }

    /**
     * testFetchMany
     *
     * @access public
     * @group user
     * @return void
     */
    public function testFetchMany() {
		$this->testAddExpected();

        $result = Sso_Model_User::fetch(null, null, null);

        $this->assertType('array', $result);
		foreach ($result as $user) {
			$this->assertIsUser($user);
		}
    }

    /**
     * testFetchOne
     *
     * @access public
     * @group user
     * @return void
     */
    public function testFetchOne() {
		$this->testAddExpected();

        $result = Sso_Model_User::fetch($this->newName, null, null);

        $this->assertEquals($result->id, $this->newName);
		$this->assertEquals($result->username, $this->newName);
    }

    /**
     * testAddNoName
     *
     * @access public
     * @group user
     * @return void
     */
    public function testAddNoName() {
		try {
			$result = Sso_Model_User::create(null, null);
			$this->fail('The above call should throw an exception');
		} catch (Sso_Exception_BadRequest $e) {
			$msg = $e->getMessage();
			$this->assertContains('Missing parameter', $msg);
			$this->assertContains('Expected:', $msg);
			$this->assertContains('username', $msg);
			$this->assertContains('password', $msg);
			$this->assertContains('organisation', $msg);
		}
    }

    /**
     * testDeleteExpected
     *
     * @access public
     * @group user
     * @return void
     */
    public function testDeleteExpected() {
		$this->testAddExpected();

		try {
			$user = Sso_Model_User::fetch($this->newName, null, null);
			$result = $user->delete();
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}
        $this->assertTrue($result);
    }

    /**
     * testUpdate
     *
     * @access public
     * @group user
     * @return void
     */
    public function testUpdate() {
		$this->testAddExpected();

		$newDesc = 'Fraggle';
		try {
			$user = Sso_Model_User::fetch($this->newName, null, null);
			$result = $user->update(array('description' => $newDesc));
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}
        $this->assertEquals($newDesc, $user->description);
    }

}

