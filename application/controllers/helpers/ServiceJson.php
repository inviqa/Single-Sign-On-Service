<?php

class Zend_Controller_Action_Helper_ServiceJson extends Zend_Controller_Action_Helper_Abstract
{
   /**
    * Strategy pattern: call helper as helper broker method
    *
    * Sends a JSON-encoded response with the payload
    *
    * @param array $payload
    *
    * @return string|false
    */
    public function direct(array $payload) {
        $response = Zend_Controller_Front::getInstance()->getResponse();
        $response->setHeader('Content-Type', 'application/json');
        $json = json_encode($payload);
        $response->setBody($json);
        return $json;
    }
}
