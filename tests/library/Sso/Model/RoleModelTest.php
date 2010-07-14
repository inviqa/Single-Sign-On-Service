<?php

require_once('PHPUnit/Framework.php');

class Test_RoleModel extends PHPUnit_Framework_TestCase
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

	public function setUp() {
		parent::setUp();
		$this->deleteIfExists($this->newName);
		$this->deleteIfExists($this->childName);
	}

	/**
	 * @param string $id role id
	 */
	private function deleteIfExists($id) {
		try {
			$role = Sso_Model_Role::fetch($id, null, null);
			$role->delete();
		} catch (Sso_Exception_NotFound $e) {
			// ignore
		} catch (Exception $e) {
			$this->fail($e->getMessage());
		}
	}

	/**
	 *
	 * @param Sso_Model_Role $res
	 */
	private function assertIsRole($res) {
		$this->assertTrue($res instanceof Sso_Model_Role, 'Not a Sso_Model_Role');
		$this->assertObjectHasAttribute('id',     $res);
		$this->assertObjectHasAttribute('name',   $res);
		$this->assertObjectHasAttribute('parent', $res);
		$this->assertTrue(!empty($res->id));
		$this->assertTrue(!empty($res->name));
	}

    /**
     * testAddExpected
     *
     * @access public
     * @group role
     * @return void
     */
    public function testAddExpected() {
		$params = array(
			'name'        => $this->newName,
			'description' => 'res desc',
		);
        $result = Sso_Model_Role::create(null, $params);

		$this->assertIsRole($result);
        $this->assertEquals($this->newName, $result->name);
        $this->assertTrue(empty($result->parent));
    }

    /**
     * testAddExpectedWithParent
     *
     * @access public
     * @group role
     * @return void
     */
    public function testAddExpectedWithParent() {
		$this->testAddExpected();

		$params = array(
			'name'        => $this->childName,
			'description' => 'res desc',
		);
		try {
			$result = Sso_Model_Role::create($this->newName, $params);
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}

		$this->assertIsRole($result);
        $this->assertEquals($this->childName, $result->name);
        $this->assertEquals($this->newName,   $result->parent);
        $this->assertContains($this->newName,   $result->id);
		$this->assertContains($this->childName, $result->id);
    }

    /**
     * testAddExpectedWithParent
     *
     * @access public
     * @group role
     * @return void
     */
    public function testAddExpectedWithInvalidParent() {
		$params = array(
			'name'        => $this->childName,
			'description' => 'res desc',
		);
		$invalidParent = 'Invalid Parent Role NAME';
		try {
			$result = Sso_Model_Role::create($invalidParent, $params);
			$this->fail('The above call should throw an exception');
		} catch (Sso_Exception_NotFound $e) {
			$this->assertContains('Parent role', $e->getMessage());
			$this->assertContains('not found', $e->getMessage());
			$this->assertContains($invalidParent, $e->getMessage());
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}
    }

    /**
     * testFetchMany
     *
     * @access public
     * @group role
     * @return void
     */
    public function testFetchMany() {
		$this->testAddExpected();

        $result = Sso_Model_Role::fetch(null, null, null);

        $this->assertType('array', $result);
		foreach ($result as $role) {
			$this->assertIsRole($role);
		}
    }

    /**
     * testFetchOne
     *
     * @access public
     * @group role
     * @return void
     */
    public function testFetchOne() {
		$this->testAddExpected();

        $result = Sso_Model_Role::fetch($this->newName, null, null);

        $this->assertEquals($result->id, $this->newName);
		$this->assertEquals($result->name, $this->newName);
    }

    /**
     * testAddNoName
     *
     * @access public
     * @group role
     * @return void
     */
    public function testAddNoName() {
		try {
			$result = Sso_Model_Role::create(null, null);
			$this->fail('The above call should throw an exception');
		} catch (Sso_Exception_BadRequest $e) {
			$msg = $e->getMessage();
			$this->assertContains('Missing', $msg);
			$this->assertContains('name', $msg);
		}
    }

    /**
     * testDeleteExpected
     *
     * @access public
     * @group role
     * @return void
     */
    public function testDeleteExpected() {
		$this->testAddExpected();

		try {
			$role = Sso_Model_Role::fetch($this->newName, null, null);
			$result = $role->delete();
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}
        $this->assertTrue($result);
    }

    /**
     * testUpdate
     *
     * @access public
     * @group role
     * @return void
     */
    public function testUpdate() {
		$this->testAddExpected();
		
		try {
			$role = Sso_Model_Role::fetch($this->newName, null, null);
			$result = $role->update(array('right' => array('read', 'read'),
										'resource' => array('mycw', 'portal:mycw'),
										'grant' => array('true', 'false')
			));
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}
		$this->assertEquals(2, count($role->getRights));

		try {
			$role = Sso_Model_Role::fetch($this->newName, null, null);
			$result = $role->update(array('right' => array('read'),
										'resource' => array('mycw'),
										'grant' => array('true')
			));
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}
		$this->assertEquals(1, count($role->getRights));

		$newDesc = 'Fraggle';
		try {
			$role = Sso_Model_Role::fetch($this->newName, null, null);
			$result = $role->update(array('description' => $newDesc));
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}
        $this->assertEquals($newDesc, $role->description);
		$this->assertEquals(1, count($role->getRights));
    }

}

