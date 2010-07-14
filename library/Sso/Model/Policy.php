<?php
/**
 * Sso_Model_Policy
 *
 * Policy model
 *
 * @category  Sso_Service
 * @package   Sso
 * @copyright Copyright (c) 2009 Cable&Wireless
 * @author
 */
class Sso_Model_Policy extends Sso_Model_Base
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $organisation;

    /**
     * fetch 
     * 
     * @param integer $id the record to retrieve or leave blank for all
     * 
     * @return Sso_Model_Policy|array The policy or array of them
     */
    static public function fetch($id = null) {
        if ($id) {
            // fetch this specifc record, look out for things that don't exist
            $policy = new Sso_Model_Policy();
            $policy->id = $id;
            $policy->name = 'Health and Safety Policy';
            $retval = $policy;
        } else {
            // return all policies
            for ($i = 7; $i < 13; $i++) {
                $policy= new Sso_Model_Policy();
                $policy->id = 2;
                $policy->name = 'A Policy';
                $retval[] = $policy;
            }
        }
        return $retval;
    }

    /**
     *
     * @param string $name
     * @param string $organisation
     * @param string $description
     *
     * @return Sso_Model_Policy|array of error messages
     */
    public static function add($name, $organisation, $description = null) {
        if (empty($name)) {
            $policy['messages'][] = 'Missing parameter.  Expected: name';
        } 
        if (empty($organisation)) {
            $policy['messages'][] = 'Missing parameter.  Expected: organisation';
        } 
        
        if (!isset($policy)) {
            // we're good, go ahead
            $policy = new Sso_Model_Policy();
            $policy->name = $name;
            $policy->organisation = $organisation;
            $policy->description = $description;
        }
        return $policy;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * @param string $organisation
     */
    public function setOrganisation($organisation) {
        $this->organisation = $organisation;
    }

}
