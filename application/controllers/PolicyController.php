<?php

class PolicyController extends Sso_Controller_Action
{
    /**
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_BadRequest
     */
    public function postAction() {
        $this->_requireValidToken();
        $name         = $this->_request->getParam('name');
        $organisation = $this->_request->getParam('organisation');
        $description  = $this->_request->getParam('description');

        if (empty($name)) {
            throw new Sso_Exception_BadRequest('Missing Parameter.  Expected: name');
        }

        // create policy or fail
        $policy = Sso_Model_Policy::add($name, $organisation, $description);
        if (!($policy instanceOf Sso_Model_Policy)) {
            throw new Sso_Exception_BadRequest('Invalid policy');
        }

        $content_type = 'policy';
        $result = (array) $policy;
        
        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_BadRequest
     */
    public function deleteAction() {
        $this->_requireValidToken();
        $policy = $this->_request->identifier;

        if (empty($policy)) {
            throw new Sso_Exception_BadRequest('Missing Parameter.  Expected: policy');
        }

        // delete the token, ignore any failure
        $content_type = 'policy';
        $policy_object = Sso_Model_Policy::fetch($policy);
        $result = (array) $policy_object->delete();

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_NotFound
     */
    public function getAction() {
        $this->_requireValidToken();
        $policy = $this->_request->identifier;

        if (!empty($policy)) {
            // return one policy item, or 404 if not exist
            $content_type = 'policy';
            $policy_object = Sso_Model_Policy::fetch($policy);
            if (!($policy_object instanceof Sso_Model_Policy)) {
                throw new Sso_Exception_NotFound('Policy not found');
            }
            $content_type = 'policy';
            $result = (array) $policy_object;
        } else {
            // get all available policys
            $content_type = 'policy';
            $result = (array) Sso_Model_Policy::fetch();
        }

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     *
     * @throws Sso_Exception_InvalidAuth
     */
    public function putAction() {
        $this->_requireValidToken();
        $name = $this->_request->getParam('name');
        $policy = $this->_request->identifier;

        $policy_object = Sso_Model_Policy::fetch($policy);
        $policy_object->setName();
        $policy_object->setDescription();
        $policy_object->setOrganisation();

        $content_type = 'policy';
        $result = (array) $policy_object;
        
        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

}

