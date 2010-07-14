<?php

require_once('PHPUnit/Framework.php');

class Test_ResourceModel extends PHPUnit_Framework_TestCase
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
	}

	/**
	 * @param string $id resource id
	 */
	private function deleteIfExists($id) {
		try {
			$resource = Sso_Model_Resource::fetch($id, null, null);
			$resource->delete();
		} catch (Sso_Exception_NotFound $e) {
			// ignore
		} catch (Exception $e) {
			$this->fail($e->getMessage());
		}
	}

	/**
	 *
	 * @param Sso_Model_Resource $res
	 */
	private function assertIsResource($res) {
		$this->assertTrue($res instanceof Sso_Model_Resource, 'Not a Sso_Model_Resource');
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
     * @group resource
     * @return void
     */
    public function testAddExpected() {
		$params = array(
			'name'        => $this->newName,
			'description' => 'res desc',
		);
        $result = Sso_Model_Resource::create(null, $params);

		$this->assertIsResource($result);
        $this->assertEquals($this->newName, $result->name);
        $this->assertTrue(empty($result->parent));
    }

    /**
     * testAddExpectedWithParent
     *
     * @access public
     * @group resource
     * @return void
     */
    public function testAddExpectedWithParent() {
		$this->testAddExpected();

		$params = array(
			'name'        => $this->childName,
			'description' => 'res desc',
		);
		try {
			$result = Sso_Model_Resource::create($this->newName, $params);
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}

		$this->assertIsResource($result);
        $this->assertEquals($this->childName, $result->name);
        $this->assertEquals($this->newName,   $result->parent);
        $this->assertContains($this->newName,   $result->id);
		$this->assertContains($this->childName, $result->id);
    }

    /**
     * testAddExpectedWithParent
     *
     * @access public
     * @group resource
     * @return void
     */
    public function testAddExpectedWithInvalidParent() {
		$params = array(
			'name'        => $this->childName,
			'description' => 'res desc',
		);
		$invalidParent = 'Invalid Parent Resource NAME';
		try {
			$result = Sso_Model_Resource::create($invalidParent, $params);
			$this->fail('The above call should throw an exception');
		} catch (Sso_Exception_NotFound $e) {
			$this->assertContains('Parent resource', $e->getMessage());
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
     * @group resource
     * @return void
     */
    public function testFetchMany() {
		$this->testAddExpected();

        $result = Sso_Model_Resource::fetch(null, null, null);

        $this->assertType('array', $result);
		foreach ($result as $resource) {
			$this->assertIsResource($resource);
		}
    }

    /**
     * testFetchOne 
     * 
     * @access public
     * @group resource
     * @return void
     */
    public function testFetchOne() {
		$this->testAddExpected();
		
        $result = Sso_Model_Resource::fetch($this->newName, null, null);

        $this->assertEquals($result->id, $this->newName);
		$this->assertEquals($result->name, $this->newName);
    }

    /**
     * testAddNoName 
     * 
     * @access public
     * @group resource
     * @return void
     */
    public function testAddNoName() {
		try {
			$result = Sso_Model_Resource::create(null, null);
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
     * @group resource
     * @return void
     */
    public function testDeleteExpected() {
		$this->testAddExpected();

		try {
			$resource = Sso_Model_Resource::fetch($this->newName, null, null);
			$result = $resource->delete();
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}
        $this->assertTrue($result);
    }

    /**
     * testUpdate
     * 
     * @access public
     * @group resource
     * @return void
     */
    public function testUpdate() {
		$this->testAddExpected();
		
		$newDesc = 'Fraggle';
		try {
			$resource = Sso_Model_Resource::fetch($this->newName, null, null);
			$result = $resource->update(array('description' => $newDesc));
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}
        $this->assertEquals($newDesc, $resource->description);
    }

}

