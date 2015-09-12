<?php

/*
 * This file is a part of PHPAX-RS framework, released under terms of GPL-3.0
 * licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights reserved.
 */

namespace phpaxrs\http;

/**
 * The "URL" class that wraps URL and provides access to URL components.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
class URL {
    
    /**
     * Passed URL string
     *
     * @var str
     */
    private $url_str = null;
    
    /**
     * Parsed URL
     * 
     * @var Array
     */
    private $url;

    /**
     * Create with a passed URL.
     * 
     * @param string $url
     */
    public function __construct($url) {
        $this->set_url($url);
    }
    
    /**
     * Sets URL
     * 
     * @param string $url
     * @throws InvalidArgumentException on invalid or empty URL
     */
    protected function set_url($url) {
        if (empty($url)) {
            throw new \InvalidArgumentException('empty URL');
        }
        $purl = parse_url($url);
        if (!self::url_valid($purl)) {
            throw new \InvalidArgumentException('malformed url');
        }
        $this->url_str = $url;
        $this->url = $purl;
    }
    
    private static function url_valid($url) {
        if (!$url || !count($url)) {
            return FALSE;
        }
        return array_key_exists('scheme', $url) &&
            array_key_exists('host', $url);
    }
    
    private function get_url_path($name, $default = NULL) {
        if (array_key_exists($name, $this->url)) {
            return $this->url[$name];
        }
        return $default;
    }
    
    public function get_scheme() {
        return $this->get_url_path('scheme');
    }
    
    public function get_host() {
        return $this->get_url_path('host');
    }
    
    public function get_port() {
        return $this->get_url_path('port');
    }
    
    public function get_user() {
        return $this->get_url_path('user');
    }
    
    public function get_password() {
        return $this->get_url_path('pass');
    }
    
    public function get_path() {
        return $this->get_url_path('path', '/');
    }
    
    public function get_query_string() {
        return $this->get_url_path('query');
    }
    
    public function get_fragment() {
        return $this->get_url_path('fragment');
    }
    
    public function __toString() {
        return $this->url_str;
    }
    
}
