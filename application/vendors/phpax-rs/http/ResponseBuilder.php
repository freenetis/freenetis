<?php

/*
 * This file is a part of PHPAX-RS framework, released under terms of GPL-3.0
 * licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights reserved.
 */

namespace phpaxrs\http;

/**
 * The "ResponseBuilder" class for easy building of HTTP request.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
class ResponseBuilder {
    
    /**
     * Builded HTTP response object.
     *
     * @var HttpResponse
     */
    private $response;
    
    /**
     * Creates builder with response.
     * 
     * @param int $status_code
     */
    protected function __construct($status_code) {
        $this->response = new HttpResponse($status_code);
    }
    
    /**
     * Sets HTTP response body.
     * 
     * @param mixed $entity body entity
     * @return \phpaxrs\http\ResponseBuilder
     */
    public function body($entity) {
        $this->response->set_body($entity);
        return $this;
    }
    
    /**
     * Adds HTTP response header.
     * 
     * @param string $name
     * @param string $value
     * @return \phpaxrs\http\ResponseBuilder
     */
    public function header($name, $value) {
        $this->response->add_header($name, $value);
        return $this;
    }
    
    /**
     * Builds output response.
     * 
     * @return HttpResponse
     */
    public function build() {
        return $this->response;
    }

    // static creator methods

    /**
     * Creates a new response builder.
     * 
     * @param int $status response status code [optional]
     * @param mixed $entity response body [optional]
     * @return ResponseBuilder
     */
    public static function create($status = 200, $entity = NULL) {
        $rb = new ResponseBuilder($status);
        if ($entity !== NULL) {
            $rb->body($entity);
            if (is_string($entity) && $status >= 400) {
                $rb->header('Content-Type', 'text/plain');
            }
        }
        return $rb;
    }
    
    /**
     * Success.
     * 
     * @param mixed $entity response entity [optional]
     * @return \phpaxrs\http\HttpResponse
     */
    public static function ok($entity = NULL) {
        return self::create(200, $entity)->build();
    }
    
    /**
     * Created.
     * 
     * @param string $location_header location header of created [optional]
     * @return \phpaxrs\http\HttpResponse
     */
    public static function created($location_header = NULL) {
        $rb = self::create(201);
        if (!empty($location_header)) {
            $rb->header('Location', $location_header);
        }
        return $rb->build();
    }
    
    /**
     * Success but no content available.
     * 
     * @return \phpaxrs\http\HttpResponse
     */
    public static function no_content() {
        return self::create(204)->build();
    }

    /**
     * Invalid request.
     * 
     * @param string $message [optional]
     * @return \phpaxrs\http\HttpResponse
     */
    public static function bad_request($message = NULL) {
        return self::create(400, $message)->build();
    }
    
    /**
     * Not found.
     * 
     * @param string $message [optional]
     * @return \phpaxrs\http\HttpResponse
     */
    public static function not_found($message = NULL) {
        return self::create(404, $message)->build();
    }
    
    /**
     * REST API for serving exists but does not populate any of accepted MIME
     * types.
     * 
     * @return \phpaxrs\http\HttpResponse
     */
    public static function not_acceptable() {
        return self::create(406)->build();
    }
    
    /**
     * Unsupported content sended with request.
     * 
     * @return \phpaxrs\http\HttpResponse
     */
    public static function unsupported_media() {
        return self::create(415)->build();
    }
    
    /**
     * Server error response with message contained as response body.
     * 
     * @param \Exception|string $ex [optional]
     * @return \phpaxrs\http\HttpResponse
     */
    public static function server_error($ex = NULL) {
        $m = NULL;
        if ($ex !== NULL) {
            $m = ($ex instanceof \Exception) ? $ex->getMessage() : strval($ex);
        }
        return self::create(500, $m)->build();
    }

}
