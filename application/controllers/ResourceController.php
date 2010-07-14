<?php

class ResourceController extends Sso_Controller_Action
{
    /**
     *
     *
     * @throws Sso_Exception_InvalidAuth
     */
    public function postAction() {
        $this->_requireValidToken();
        $parent = $this->_request->identifier;

		if (!empty($parent)) {
			// also check if the parent exists, or 404
            $parent_object = Sso_Model_Resource::fetch($parent);
		}
		$resource = Sso_Model_Resource::create($parent, $this->getRequest()->getParams());
		$content_type = 'resource';
        $result = (array) $resource;

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
        $resource = $this->_request->identifier;

        // delete the resource and children, ignore any failure
        try {
            $resource_object = Sso_Model_Resource::fetch($resource, null, null);
            $resource_object->delete();
        } catch(Sso_Ldap_Exception $e) {
        } catch(Sso_Exception_NotFound $e) {
			// that's OK, we don't care if we found it or deleted it or what
		}
        $content_type = 'resource';
        $result = array("true");

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
        $resource = $this->_request->identifier;
        $order = $this->getRequest()->getParam('order');
        $direction = $this->getRequest()->getParam('direction');

        $resource_object = Sso_Model_Resource::fetch($resource, $order, $direction);
        $link = $this->getRequest()->getParam('link');
        if (!empty($link)) {
            // this is a subrequest
            switch($link) {
                case 'children':
                    // return the child resources of this resource
                    $content_type = 'resource';
                    $result = (array)$resource_object->getChildren($order, $direction);
                    break;
                default:
                    throw new Sso_Exception_NotFound('Unknown subrequest ' . $link);
                }
        } else {
            $content_type = 'resource';
            $result = (array) $resource_object;
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
        $resource = $this->_request->identifier;

        $resource_object = Sso_Model_Resource::fetch($resource);
        $resource_object->update($this->getRequest()->getParams());

        $content_type = 'resource';
        $result = (array) $resource_object;
        
        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

}
