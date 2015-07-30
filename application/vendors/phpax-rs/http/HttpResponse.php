<?php

/*
 * This file is a part of PHPAX-RS framework, released under terms of GPL-3.0
 * licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights reserved.
 */

namespace phpaxrs\http;

/**
 * The "HttpResponse" class that represent a HTTP response with wrapped headers,
 * status code and response body.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
class HttpResponse {
    
    /**
     * HTTP response headers.
     *
     * @var Array
     */
    private $headers = array();
    
    /**
     * Response HTTP status code.
     *
     * @var int
     */
    private $status;
    
    /**
     * Response body (string or object).
     *
     * @var mixed
     */
    private $body;
    
    
    /**
     * Creates a new response
     * 
     * @param int $status HTTP status code [optional: default 200 OK]
     * @param string $body body of response [optional: default empty]
     */
    public function __construct($status = 200, $body = NULL) {
        $this->set_status($status);
        $this->set_body($body);
    }
    
    /**
     * Set response HTTP header.
     * 
     * @param string $name
     * @param string $value
     */
    public function add_header($name, $value) {
        $this->headers[$name] = $value;
    }
    
    /**
     * Checks whether the header with the given name is present.
     * 
     * @param string $name
     * @return boolean
     */
    public function has_header($name) {
        return array_key_exists($name, $this->headers);
    }
    
    /**
     * Gets all headers.
     * 
     * @return Array key is header name value is header value
     */
    public function get_headers() {
        return $this->headers;
    }
    
    /**
     * Sets response body.
     * 
     * @param mixed $body
     */
    public function set_body($body) {
        $this->body = $body;
    }
    
    /**
     * Gets response body.
     * 
     * @return mixed
     */
    public function get_body() {
        return $this->body;
    }

    /**
     * Sets HTTP status.
     * 
     * @param int $status
     * @throws InvalidArgumentException on invalid status
     */
    protected function set_status($status) {
        $status = intval($status);
        if ($status < 200 || $status > 599) {
            throw new \InvalidArgumentException('invalid HTTP status: '.$status);
        }
        $this->status = $status;
    }
    
    /**
     * Gets HTTP status.
     * 
     * @return int HTTP status code
     */
    public function get_status() {
        return $this->status;
    }

}
