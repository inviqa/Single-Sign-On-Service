<?php

class TokenController extends Sso_Controller_Action
{
    /**
     *
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_BadRequest
     */
    public function getAction() {
        $this->_requireValidToken();

        $token = Sso_Model_Token::fetch($this->_request->identifier);
        if (!$token || !$token->isValid()) {
            throw new Sso_Exception_BadRequest('Token invalid or not found');
        } else {
            $content_type = 'token';
            $result = (array) $token;
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
    public function postAction() {
        $username = $this->_request->getParam('username');
        $password = $this->_request->getParam('password');

        $token = Sso_Model_Token::authorise($username, $password);
        if (!($token instanceOf Sso_Model_Token)) {
            throw new Sso_Exception_BadRequest('Authentication failed.');
		}

        // drop the cookie
		if (!headers_sent()) {
			setcookie('token', $token->token);
		}

        $content_type = 'token';
        $result = (array) $token;

        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }

    /**
     *
     *
     * @throws Sso_Exception_InvalidAuth
     * @throws Sso_Exception_BadRequest
     */
    public function deleteAction() {
        $this->_requireValidToken();
        $token = Sso_Model_Token::fetch($this->_request->identifier);

        if (empty($this->_request->identifier)) {
            throw new Sso_Exception_BadRequest('Missing parameter. Expected: token');
        } else {
            $content_type = 'token';
            if ($token instanceOf Sso_Model_Token) {
                $token->delete();
            }
            $result = array('true');
        }
        $result = $this->_helper->serviceFormat($content_type, $result);
        $this->_helper->serviceJson($result);
    }
}
