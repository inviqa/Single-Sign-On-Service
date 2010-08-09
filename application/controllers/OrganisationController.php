<?php

class OrganisationController extends Sso_Controller_Action
{
    /**
     * Update record
     *
     * @throws Sso_Exception_InvalidAuth
     */
    public function putAction() {
        $this->_requireValidToken();
        $organisation = $this->_request->identifier;
        if (empty($organisation)) {
            throw new Sso_Exception_BadRequest('Missing parameter: organisation id');
        }

        $content_type = 'organisation';
        $org_object = Sso_Model_Organisation::fetch($organisation);
        $org_object->update($this->getRequest()->getParams());
        $result = (array)Sso_Model_Organisation::fetch($organisation);

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     * Create record
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_NotFound
     */
    public function postAction() {
        $this->_requireValidToken();
        $organisation = $this->_request->identifier;

        // check vars
        $link = $this->getRequest()->getParam('link');
        if (!empty($link)) {
            // also check if the organisation exists, or 404
            $org_object = Sso_Model_Organisation::fetch($organisation);

            switch($link) {
                case 'role':
                    // save this role against this organisation
                    // return the roles of this organisation
                    $content_type = 'role';
                    $result = array(
                        array('id' => 4, 'name' => 'admin'),
                        array('id' => 9, 'name' => 'editor')
                    );
                    break;
                default:
                    throw new Sso_Exception_NotFound('Unknown subrequest ' . $link);
            }
        } else {
            // if we get this far, try to create the record
            $org_obj = Sso_Model_Organisation::create($organisation, $this->getRequest()->getParams());
            $content_type = 'organisation';
            $result = (array)$org_obj;
        }

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     * Delete an organisation
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_BadRequest
     * @throws Sso_Exception_NotFound
     */
    public function deleteAction() {
        $this->_requireValidToken();
        $organisation = $this->_request->identifier;

        // get the subrequest and the primary key of that
        $link = $this->getRequest()->getParam('link');
        $link_id = $this->getRequest()->getParam('link_identifier');
        if (!empty($link)) {
            if (empty($organisation)) {
                throw new Sso_Exception_BadRequest('Missing or badly formatted parameter: organisation id');
            } 
            if (empty($link_id)) {
                throw new Sso_Exception_BadRequest('Missing or badly formatted parameter: secondary id');
            }
            // also check if the organisation exists, or 404
            switch($link) {
                case 'role':
                    // delete the relationship, ignoring failure
					$org_object = Sso_Model_Organisation::fetch($organisation);
					$org_object->deleteRole($link_id);
                    $content_type = 'role';
                    $result = array('true');
                    break;
                default:
                    throw new Sso_Exception_NotFound('Unknown subrequest ' . $link);
            }
        } else {
            // delete the organisation, ignore any failure
            try {
                $org_object = Sso_Model_Organisation::fetch($organisation);
                $org_object->delete();
            } catch(Exception $e) {
                // that's OK, we don't care if we found it or deleted it or what
            }
            $content_type = 'organisation';
            $result = array('true');
        }

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     * Get the organisation's details
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_NotFound
     */
    public function getAction() {
    	//$log = Zend_Registry::get('log');
        $this->_requireValidToken();
        $organisation = $this->_request->identifier;
        $order = $this->getRequest()->getParam('order');
        $direction = $this->getRequest()->getParam('direction');
		$org = Sso_Model_Organisation::fetch($organisation, $order, $direction);
		$link = $this->getRequest()->getParam('link');
        if (!empty($link)) {
            // this is a subrequest
            switch($link) {
                case 'children':
                    // return the child organisations of this organisation
                    $content_type = 'organisation';
                    $result = (array)$org->getChildren($order, $direction);
                    break;
                case 'user':
                    // return the users of this organisation
                    $content_type = 'user';
                    $result = (array)$org->getUsers($order, $direction);
                    break;
                case 'role':
                    // return the roles of this organisation
                    $content_type = 'role';
                    $result = (array)Sso_Model_Role::getRoleFromDn(Sso_Model_Organisation::getDnFromId($org->id));
                    break;
                case 'right':
                    // return the rights of this organisation
                    $content_type = 'right';
                    $result = (array)$org->getRights();
                    break;
                default:
                    throw new Sso_Exception_MethodNotAllowed('Unknown subrequest ' . $link);
            }
        } else {
            // get all available organisations
            $content_type = 'organisation';
            $result = (array)$org;
        }

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

}

