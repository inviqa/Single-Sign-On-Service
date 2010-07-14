<?php
class Sso_Controller_Action extends Zend_Controller_Action
{
    /**
     * Forward the call to the REST action (put/post/get/delete) of the current controller
     */
	public function routeAction() {
        // handle grabbing PUT vars
        $request = $this->getRequest();
        if ($request->isPut()) {
            parse_str($request->getRawBody(), $params);
            foreach ($params as $key => $value) {
                $request->setParam($key, $value);
            }
        }
		$this->_forward(strtolower($request->getMethod()));
	}

    /**
     * Catch invalid requests, send 405 http status code and json error message
     *
     * @param string $name
     * @param array  $args
     */
    public function __call($name, $args) {
        throw new Sso_Exception_MethodNotAllowed($name." not supported");
    }

    /**
     * Throw an exception if the user doesn't have a valid token cookie
     *
     * @throws Sso_Exception_InvalidAuth
     */
    protected function _requireValidToken() {
        $token = Sso_Model_Token::fetch($this->getRequest()->getCookie('token'));
        if (empty($token) || !$token->isValid()) {
            throw new Sso_Exception_InvalidAuth('You must be authorised to perform this action');
        }
    }
}
