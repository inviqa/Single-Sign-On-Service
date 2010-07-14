<?php

require_once('PHPUnit/Framework.php');

class Test_OrganisationModel extends PHPUnit_Framework_TestCase
{
	/**
	 * New organisation name used for testing
	 * @var string
	 */
	private $newName = 'new organisation';

	/**
	 * New child organisation name used for testing
	 * @var string
	 */
	private $childName = 'childorganisation';

	public function setUp() {
		parent::setUp();
		$this->deleteIfExists($this->newName);
		$this->deleteIfExists($this->childName);
	}

	/**
	 * @param string $id organisation id
	 */
	private function deleteIfExists($id) {
		try {
			$organisation = Sso_Model_Organisation::fetch($id, null, null);
			$organisation->delete();
		} catch (Sso_Exception_NotFound $e) {
			// ignore
		} catch (Exception $e) {
			$this->fail($e->getMessage());
		}
	}

	/**
	 *
	 * @param Sso_Model_Organisation $res
	 */
	private function assertIsOrganisation($res) {
		$this->assertTrue($res instanceof Sso_Model_Organisation, 'Not a Sso_Model_Organisation');
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
     * @group organisation
     * @return void
     */
    public function testAddExpected() {
		$params = array(
			'name'        => $this->newName,
			'description' => 'res desc',
		);
        $result = Sso_Model_Organisation::create(null, $params);

		$this->assertIsOrganisation($result);
        $this->assertEquals($this->newName, $result->name);
        $this->assertTrue(empty($result->parent));
    }

    /**
     * testAddExpectedWithParent
     *
     * @access public
     * @group organisation
     * @return void
     */
    public function testAddExpectedWithParent() {
		$this->testAddExpected();

		$params = array(
			'name'        => $this->childName,
			'description' => 'res desc',
		);
		try {
			$result = Sso_Model_Organisation::create($this->newName, $params);
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}

		$this->assertIsOrganisation($result);
        $this->assertEquals($this->childName, $result->name);
        $this->assertEquals($this->newName,   $result->parent);
        $this->assertContains($this->newName,   $result->id);
		$this->assertContains($this->childName, $result->id);
    }

    /**
     * testAddExpectedWithParent
     *
     * @access public
     * @group organisation
     * @return void
     */
    public function testAddExpectedWithInvalidParent() {
		$params = array(
			'name'        => $this->childName,
			'description' => 'res desc',
		);
		$invalidParent = 'Invalid Parent Organisation NAME';
		try {
			$result = Sso_Model_Organisation::create($invalidParent, $params);
			$this->fail('The above call should throw an exception');
		} catch (Sso_Exception_NotFound $e) {
			$this->assertContains('Parent organisation', $e->getMessage());
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
     * @group organisation
     * @return void
     */
    public function testFetchMany() {
		$this->testAddExpected();

        $result = Sso_Model_Organisation::fetch(null, null, null);

        $this->assertType('array', $result);
		foreach ($result as $organisation) {
			$this->assertIsOrganisation($organisation);
		}
    }

    /**
     * testFetchOne
     *
     * @access public
     * @group organisation
     * @return void
     */
    public function testFetchOne() {
		$this->testAddExpected();

        $result = Sso_Model_Organisation::fetch($this->newName, null, null);

        $this->assertEquals($result->id, $this->newName);
		$this->assertEquals($result->name, $this->newName);
    }

    /**
     * testAddNoName
     *
     * @access public
     * @group organisation
     * @return void
     */
    public function testAddNoName() {
		try {
			$result = Sso_Model_Organisation::create(null, null);
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
     * @group organisation
     * @return void
     */
    public function testDeleteExpected() {
		$this->testAddExpected();

		try {
			$organisation = Sso_Model_Organisation::fetch($this->newName, null, null);
			$result = $organisation->delete();
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}
        $this->assertTrue($result);
    }

    /**
     * testUpdate
     *
     * @access public
     * @group organisation
     * @return void
     */
    public function testUpdate() {
		$this->testAddExpected();

		$newDesc = 'Fraggle';
		try {
			$organisation = Sso_Model_Organisation::fetch($this->newName, null, null);
			$result = $organisation->update(array('description' => $newDesc));
		} catch (Sso_Exception $e) {
			$this->fail($e->getMessage());
		}
        $this->assertEquals($newDesc, $organisation->description);
    }

}

