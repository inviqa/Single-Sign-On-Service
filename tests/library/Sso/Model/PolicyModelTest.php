<?php

require_once('PHPUnit/Framework.php');

class Test_PolicyModel extends PHPUnit_Framework_TestCase {
    /**
     * testFetchMany 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testFetchMany() {
        $result = Sso_Model_Policy::fetch();

        $this->assertType('array',$result);
    }

    /**
     * testFetchOne 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testFetchOne() {
        $result = Sso_Model_Policy::fetch(77);

        $this->assertTrue($result instanceof Sso_Model_Policy);
        $this->assertEquals(77, $result->id);
    }

    /**
     * testAddExpected 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testAddExpected() {
        $result = Sso_Model_Policy::add('Policy',4, 'Longer sentence of words');

        $this->assertTrue($result instanceof Sso_Model_Policy);
        $this->assertEquals('Policy', $result->name);
        $this->assertEquals(4, $result->organisation);
        $this->assertEquals('Longer sentence of words', $result->description);
        
    }

    /**
     * testAddNoName 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testAddNoName() {
        $result = Sso_Model_Policy::add(null,4, 'Longer sentence of words');

        $this->assertType('array', $result);
        $this->assertArrayHasKey('messages',$result);
    }

    /**
     * testAddNoOrganisation 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testAddNoOrganisation() {
        $result = Sso_Model_Policy::add('Policy', null, 'Longer sentence of words');

        $this->assertType('array', $result);
        $this->assertArrayHasKey('messages',$result);
    }

    /**
     * testSetOrganisation 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testSetOrganisation() {
        $policy = new Sso_Model_Policy();

        $policy->setOrganisation('Red');
        $this->assertEquals('Red', $policy->organisation);
    }

    /**
     * testSetDescription 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testSetDescription() {
        $policy = new Sso_Model_Policy();

        $policy->setDescription('Red');
        $this->assertEquals('Red', $policy->description);
    }

    /**
     * testSetName 
     * 
     * @access public
     * @group policy
     * @return void
     */
    public function testSetName() {
        $policy = new Sso_Model_Policy();

        $policy->setName('Red');
        $this->assertEquals('Red', $policy->name);
    }
}

