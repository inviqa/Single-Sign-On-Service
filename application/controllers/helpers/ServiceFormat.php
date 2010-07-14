<?php

class Zend_Controller_Action_Helper_ServiceFormat extends Zend_Controller_Action_Helper_Abstract
{
    /**
     *
     * @param string $content_type
     * @param array  $content
     *
     * @return array
     */
    public function direct($content_type, $content) {
        if ($content_type == 'error') {
            $messages = $content;
            $status = $this->getResponse()->getHttpResponseCode();
            $result = array(
                'contentType'  => 'error',
                'errorCode'    => 'SSO'.$status,
                'errorMessage' => $messages,
            );
        } else {
            // is this an array of results?
            if(isset($content[0])) {
                // pagination
                $offset = (int)$this->getRequest()->getParam('offset');
                $limit = 0;
                
                if($this->getRequest()->getParam('limit') > 0) {
                    $limit = (int)$this->getRequest()->getParam('limit');
                    $paged_content = array_slice($content, $offset , $limit);
                } else {
                    $paged_content = array_slice($content, $offset);
                }
                $result = array(
                    'contentType' => $content_type,
                    'data'        => (array)$paged_content,
                    'offset'      => $offset,
                    'limit'       => $limit,
                    'total'       => count($content)
                );
            } else {

                $result = array(
                    'contentType' => $content_type,
                    'data'        => (array)$content,
                );
            }

        }
        return $result;
    }
}
