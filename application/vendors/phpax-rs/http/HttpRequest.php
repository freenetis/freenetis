<?php

/*
 * This file is a part of PHPAX-RS framework, released under terms of GPL-3.0
 * licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights reserved.
 */

namespace phpaxrs\http;

/**
 * The "Request" class that represens a HTTP request with wrapped method, URL,
 * headers and request content body.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
class HttpRequest {
    
    // HTTP methods name constants
    const GET = 'get';
    const POST = 'post';
    const PUT = 'put';
    const DELETE = 'delete';
    
    /**
     * Available HTTP methods constants.
     *
     * @var Array
     */
    private static $METHOD_TYPES = array(
        self::GET, self::POST, self::PUT, self::DELETE
    );

    /**
     * HTTP methods constants that may have content body.
     *
     * @var Array
     */
    private static $METHOD_TYPE_WITH_BODY = array(
        self::POST, self::PUT
    );
    
    /**
     * Requested URL.
     *
     * @var URL
     */
    private $url;
    
    /**
     * HTTP method of request, must be one of methods type constants.
     *
     * @var string
     */
    private $method;
    
    /**
     * Request content.
     *
     * @var mixed
     */
    private $body = NULL;

    /**
     * Headers array that were send with request (key is header name, value 
     * is its value).
     *
     * @var Array
     */
    private $headers = array();
    
    /**
     * Initilizes HTTP request from given URL and information about request
     * obtained from PHP runtime.
     * 
     * @param string $url request URL
     * @param strinf $method HTTP method
     * @param mixed $body Content of request
     * @param array $headers List of HTTP headers
     * @throws InvalidArgumentException on invalid HTTP method
     */
    public function __construct($url, $method, $body, $headers) {
        $this->url = new URL($url);
        $this->set_method($method);
        $this->set_body($body);
        $this->headers = !count($headers) ? array() : $headers;
    }

    /**
     * Sets HTTP method.
     * 
     * @param string $method HTTP method name
     * @throws \InvalidArgumentException on invalid method
     */
    private function set_method($method) {
        $method = strtolower($method);
        if (!in_array($method, self::$METHOD_TYPES)) {
            throw new \InvalidArgumentException('invalid method');
        }
        $this->method = $method;
    }
    
    /**
     * Set body if body enabled.
     * 
     * @param mixed $body
     */
    private function set_body($body) {
        if ($this->has_body()) {
            $this->body = $body;
        }
    }


    /**
     * Gets list of headers.
     * 
     * @return array
     */
    public function get_headers() {
        return $this->headers;
    }
    
    /**
     * Gets a header value.
     * 
     * @param string $name header name
     * @return string|null
     */
    public function get_header($name) {
        if (array_key_exists($name, $this->headers)) {
            return $this->headers[$name];
        }
        return NULL;
    }
    
    /**
     * Gets MIME types from accept header sorted by their weight.
     * 
     * @return null|array
     */
    public function get_accept_header() {
        $accepts = $this->get_header('Accept');
        if ($accepts != NULL) {
            return \phpaxrs\common\HttpUtil::parse_accept_header($accepts);
        }
        return NULL;
    }
    
    /**
     * Gets accept header if HTTP method supports body.
     * 
     * @return null|string
     */
    public function get_content_type_header() {
        if ($this->has_body()) {
            return $this->get_header('Content-Type');
        }
        return NULL;
    }
    
    /**
     * Gets URL of request.
     * 
     * @return URL
     */
    public function get_url() {
        return $this->url;
    }
    
    /**
     * Gets HTTP request method.
     * 
     * @return string
     */
    public function get_method() {
        return $this->method;
    }
    
    /**
     * Gets request body (for POST and PUT HTTP methods).
     * 
     * @return string|null
     */
    public function get_body() {
        if ($this->has_body()) {
            return $this->body;
        }
        return NULL;
    }
    
    /**
     * Has this request a content body?
     * 
     * @return bool
     */
    public function has_body() {
        return in_array($this->method, self::$METHOD_TYPE_WITH_BODY);
    }

}
