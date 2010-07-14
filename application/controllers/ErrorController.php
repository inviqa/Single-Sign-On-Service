<?php

class ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        $errorCode    = $errors->exception->getCode();
        $errorMessage = $errors->exception->getMessage();

        if ($errors->exception instanceof Sso_Exception_BadRequest) {
            $errorCode = 400;
        } elseif ($errors->exception instanceof Sso_Exception_InvalidAuth) {
            $errorCode = 401;
        } elseif ($errors->exception instanceof Sso_Exception_NotFound) {
            $errorCode = 404;
        } elseif ($errors->exception instanceof Sso_Exception_MethodNotAllowed) {
            $errorCode = 405;
        } elseif ($errors->exception instanceof Sso_Exception_AlreadyExists) {
            $errorCode = 409;
        } else {
	        switch ($errors->type) { 
	            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
	            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
	                // 404 error -- controller or action not found
                    $errorCode = 404;
	                break;
	            default:
	                // application error 
                    $errorCode = 500;
	                break;
            }
        }
        
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'application/json')
	         ->setHttpResponseCode($errorCode)
	         ->setBody(json_encode(array(
			'contentType'  => 'error',
			'errorCode'    => 'SSO' . $errorCode,
			'errorMessage' => $errorMessage
		 )));
    }
}
