<?php

class RoleController extends Sso_Controller_Action
{
    /**
     *
     *
     * @throws Sso_Exception_InvalidAuth
     */
    public function postAction() {
        $this->_requireValidToken();
        $role = $this->_request->identifier;

        $content_type = 'role';
        $role = Sso_Model_Role::create($role, $this->getRequest()->getParams());
        $result = (array) $role;
        
        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     *
     *
     * @throws Sso_Exception_InvalidAuth
     */
    public function deleteAction() {
        $this->_requireValidToken();
        $role = $this->_request->identifier;

        // delete the role, ignore any failure
        try {
            $role_object = Sso_Model_Role::fetch($role);
            $role_object->delete();
        } catch(Exception $e) {
            // that's OK, we don't care if we found it or deleted it or what
        }
        $content_type = 'role';
        $result = array('true');

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     *
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_NotFound
     */
    public function getAction() {
        $this->_requireValidToken();
        $role = $this->_request->identifier;
        $order = $this->getRequest()->getParam('order');
        $direction = $this->getRequest()->getParam('direction');

        $link = $this->getRequest()->getParam('link');
        $role = Sso_Model_Role::fetch($role, $order, $direction);

        if (!empty($link)) {
            // this is a subrequest

            switch($link) {
                case 'right':
                    // return the rights for this role
                    $content_type = 'right';
                    $result = (array) $role->getRights();
                    break;
                case 'children':
                    // return the child roles of this role
                    $content_type = 'role';
                    $result = (array) $role->getChildren($order, $direction);
                    break;
                default:
                    throw new Sso_Exception_NotFound('Unknown subrequest');
                    break;
            }
        } else {
            $content_type = 'role';
            $result = (array) $role;
        }

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     *
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_NotFound
     */
    public function putAction() {
        $this->_requireValidToken();
        $role = $this->_request->identifier;

        $link = $this->getRequest()->getParam('link');
        if (!empty($link)) {
            switch($link) {
                default:
                    throw new Sso_Exception_NotFound('Unknown subrequest');
                    break;
            }

        } else {
            // return the updated role data
            $content_type = 'role';
            $role_object = Sso_Model_Role::fetch($role);
            $role_object->update($this->getRequest()->getParams());
			$newName = $this->getRequest()->getParam('name');
			if (!empty($newName)) {
				$role = $newName;
			}
            $result = (array) Sso_Model_Role::fetch($role);
        }

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }
}

