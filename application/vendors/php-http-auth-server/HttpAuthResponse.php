<?php

/* 
 * This file is a part of PHP-HTTP-Auth-server library, released under terms 
 * of GPL-3.0 licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights 
 * reserved.
 */

namespace phphttpauthserver;

/**
 * Reprezentation of authentification response that can be used for creating
 * responses to client requests with HTTP digest auth.
 * 
 * @author Ondřej Fibich
 */
class HttpAuthResponse {

    /**
     * User pass HTTP auth? If he does than no errors or headers are returned
     * from their getters.
     *
     * @var boolean
     */
    private $passed = TRUE;
    
    /**
     * Username of auth user.
     *
     * @var string
     */
    private $username = NULL;
    
    /**
     * List of response headers as asociative array.
     *
     * @var array
     */
    private $headers = array();
    
    /**
     * List of response error messages.
     *
     * @var array
     */
    private $errors = array();
    
    /**
     * Get user pass HTTP auth flag.
     * 
     * @return boolean
     */
    public function isPassed() {
        return $this->passed;
    }
    
    /**
     * Set user pass HTTP auth flag.
     * 
     * @param boolean $passed
     * @return HttpAuthResponse chainable reference
     * @throws \InvalidArgumentException if cannot be changed
     */
    public function setPassed($passed) {
        if (!$this->passed && !empty($this->errors) && $passed) {
            throw new \InvalidArgumentException('errors occured');
        }
        $this->passed = $passed;
        return $this;
    }
    
    /**
     * Gets username of auth user.
     * 
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Sets username of auth user.
     * 
     * @param string $username
     * @return HttpAuthResponse chainable reference
     */
    public function setUsername($username) {
        $this->username = $username;
        return $this;
    }
        
    /**
     * Adds error message and set user pass HTTP digest auth flag to FALSE.
     * 
     * @param string $message error message
     * @return HttpAuthResponse chainable reference
     */
    public function addError($message) {
        $this->errors[] = $message;
        $this->passed = FALSE;
        return $this;
    }
    
    /**
     * Add or replace response error header.
     * 
     * @param string $key header key
     * @param string $value header value
     * @return HttpAuthResponse chainable reference
     */
    public function addHeader($key, $value) {
        $this->headers[$key] = $value;
        return $this;
    }
    
    /**
     * Gets all error messages if user pass HTTP auth flag is set to FALSE.
     * 
     * @return array|null
     */
    public function getErrors() {
        if ($this->passed) {
            return NULL;
        }
        return $this->errors;
    }
    
    /**
     * Gets all HTTP headers.
     * 
     * @return array|null
     */
    public function getHeaders() {
        return $this->headers;
    }
    
}