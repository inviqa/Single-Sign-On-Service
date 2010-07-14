<?php

class UserController extends Sso_Controller_Action
{
    /**
     *
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_BadRequest
     */
    public function postAction() {
        $this->_requireValidToken();
        $user = $this->_request->identifier;

        $link = $this->getRequest()->getParam('link');
        //$user_object = Sso_Model_User::fetch($user);

        // deal with a subrequest
        if (!empty($link)) {
            switch($link) {
                default:
                    throw new Sso_Exception_BadRequest('Unknown user subrequest');
                    break;
            }
        } else {
			$user_object = Sso_Model_User::create($this->getRequest()->getParams());
            $content_type = 'user';
            $result = (array) $user_object;
        }

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     *
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_BadRequest
     * @throws Sso_Exception_NotFound
     */
    public function deleteAction() {
        $this->_requireValidToken();
        $user = $this->_request->identifier;

        // get the subrequest and the primary key of that
        $link    = $this->getRequest()->getParam('link');
        $link_id = $this->getRequest()->getParam('link_identifier');
        if (!empty($link)) {
            $user_obj = Sso_Model_User::fetch($user);
            switch($link) {
                case 'role':
                    // delete the relationship
					$user_obj->deleteRole($link_id);
                    $content_type = 'role';
                    $result = array('true');
                    break;
                default:
                    throw new Sso_Exception_BadRequest('Unknown user subrequest');
            }
        } else {
            // delete the user, ignore any failure
            $content_type = 'user';
            try {
                $user_obj = Sso_Model_User::fetch($user);
                $user_obj->delete();
            } catch(Sso_Exception_NotFound $e) {
                // this is fine - we present success in any event
            }
            $result = array('true');
        }
        
        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     *
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_BadRequest
     */
    public function getAction() {
        $this->_requireValidToken();
        $username = $this->_request->identifier;


        $order = $this->getRequest()->getParam('order');
        $direction = $this->getRequest()->getParam('direction');

        $user = Sso_Model_User::fetch($username, $order, $direction);
		$link = $this->getRequest()->getParam('link');
        if (!empty($link)) {
            switch($link) {
				case 'right':
					$content_type = 'right';
					$link_identifier = $this->getRequest()->getParam('link_identifier');
					$result = (array)$user->getRights($link_identifier);
                    break;
                case 'role':
                    // return the roles of this user
                    $content_type = 'role';

					// get roles
					$roles = Sso_Model_Role::getRoleFromDn(Sso_Model_User::getDnFromName($user->username));

					// Load the real roles
					$result = array();
					foreach ($roles as $key => $role) {
						$role = Sso_Model_Role::fetch($role);
						$_permissions = array();
						if (isset($role->permissions)) {
							$permissions = $role->permissions;
	
							foreach ($permissions as $perm) {
								$_permissions[] = (array) $perm;
							}							
						}
						$result[$key] = (array) $role;
						$result[$key]['permissions'] = $_permissions;
					}
					break;
                default:
                    throw new Sso_Exception_BadRequest('Unknown user subrequest');
                    break;
            }
        } else {
            $result = $user;
            $content_type = 'user';
            $result = (array) $result;
        }

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     *
     *
     * @throws Sso_Exception_InvalidAuth
     */
    public function putAction() {
        $this->_requireValidToken();
        $user = $this->_request->identifier;

        $user_object = Sso_Model_User::fetch($user);
        $user_object->update($this->getRequest()->getParams());
        $content_type = 'user';
        $result = (array) Sso_Model_User::fetch($user);
        
        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }
}

