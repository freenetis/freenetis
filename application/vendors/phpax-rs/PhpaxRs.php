<?php

/*
 * This file is a part of PHPAX-RS framework, released under terms of GPL-3.0
 * licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights reserved.
 */

namespace phpaxrs;

use \InvalidArgumentException;
use \phpaxrs\common\Annotations;
use \phpaxrs\common\DocCommentWrapper;
use \phpaxrs\common\PathUtil;

/**
 * The "PhpaxRs" class encases functionality of the PHPAX-RS framework.
 * It allows to specify end point classes and map them to URL paths,
 * register serializers for implementing of many types of transport formats,
 * and serve and render requests using matched end points.
 * 
 * Written for PHP 5.3 and higher.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
class PhpaxRs {
    
    /**
     * PHPAX-RS version.
     */
    const VERSION = '0.1.0';
    
    /**
     * Serializators for (un)marchalling of object from request and responses.
     * 
     * Stored in associative array where key is accepted MIME and value is
     * name of serializator class.
     *
     * @var Array
     */
    private $serializators = array();
    
    /**
     * End points for serving requests on specified path.
     * 
     * Stored in associative array where key is base path that is managed
     * by endpoint and value is name of end point class.
     *
     * @var Array 
     */
    private $end_points = array();
    
    /**
     * Base path to REST api. E.g. /rest-api
     *
     * @var string
     */
    private $base_path;

    /**
     * Creates PHPAX-RS API on given base path.
     * 
     * @param string $base_path base path from document root
     */
    public function __construct($base_path = '/') {
        $this->base_path = PathUtil::normalize($base_path);
    }
    
    /**
     * Adds end point for serving requests on given base path.
     * 
     * @param string $base_ep_path end point base path
     * @param string $end_point_cn class name
     * @throws InvalidArgumentException on invalid or duplicate base path
     */
    public function add_endpoint($base_ep_path, $end_point_cn) {
        if (!PathUtil::is_valid($base_ep_path)) {
            throw new InvalidArgumentException('invalid end point path');
        }
        $normalized_base_ep_path = PathUtil::normalize($base_ep_path);
        if (array_key_exists($normalized_base_ep_path, $this->end_points)) {
            $m = 'path ' . $normalized_base_ep_path . ' already registered';
            throw new InvalidArgumentException($m);
        }
        $this->end_points[$normalized_base_ep_path] = $end_point_cn;
    }
    
    /**
     * Adds serializator for (un)marchalling of object with given MIME type.
     * 
     * @param string $mime MINE (e.g. application/json)
     * @param string $serializator_cn class name
     * @throws InvalidArgumentException on invalid arguments or existing MIME
     */
    public function add_serializator($mime, $serializator_cn) {
        if (empty($serializator_cn)) {
            throw new InvalidArgumentException('invalid serializator');
        }
        $tl_mime = mb_strtolower(trim($mime));
        if (empty($tl_mime)) {
            throw new InvalidArgumentException('invalid mime: ' . $mime);
        }
        if (array_key_exists($tl_mime, $this->serializators)) {
            $m = 'serializator for mime ' . $mime . ' already exist';
            throw new InvalidArgumentException($m);
        }
        $this->serializators[$tl_mime] = $serializator_cn;
    }

    /**
     * Creates serializator for the given MIME type.
     * 
     * @param string $mime MIME of serializator
     * @return null|serializator\ISerializator null if not founded or instance
     * @throws \ErrorException on invalid founded serializator (invalid sub class)
     */
    public function create_serializer($mime) {
        $consumer = $this->find_serializator($mime);
        if ($consumer === NULL) {
            return NULL;
        }
        $r_consumer = new \ReflectionClass($consumer);
        if (!$r_consumer->isSubclassOf('phpaxrs\serializator\ISerializator')) {
            throw new \ErrorException('Invalid serializator');
        }
        return $r_consumer->newInstanceArgs();
    }
    
    /**
     * Serve given URL with available end points with HTTP method and headers
     * obtained from PHP runtime.
     * 
     * This method can be run only if PHP is hosted by Apache server.
     * 
     * @param string $url
     * @return http\HttpResponse
     * @throws Exception on invalid or non existing server side scripts
     *                   (end points or serializers)
     */
    public function serve($url) {
        return $this->serve_request(new http\HttpRequest(
                $url, filter_input(INPUT_SERVER, 'REQUEST_METHOD'),
                file_get_contents('php://input'),
                getallheaders() // available only on Apache
        ));
    }
    
    /**
     * Serve given HTTP request with available end points.
     * 
     * @param http\HttpRequest $request
     * @return http\HttpResponse
     * @throws Exception on invalid or non existing server side scripts
     *                   (end points or serializers)
     */
    public function serve_request($request) {
        /* FIND END POINT FOR REQUEST */
        
        // get base api path
        $full_path = $request->get_url()->get_path();
        $path = PathUtil::relative($this->base_path, $full_path);
        if ($path == NULL) {
            return http\ResponseBuilder::not_found('invalid path');
        }
        // find end point
        if (($ep = $this->find_end_point($path)) == NULL) {
            return http\ResponseBuilder::not_found('no end point founded');
        }
        // remove matched part of the path => path for method
        $path_method = PathUtil::relative($ep['base_path'], $path);
        if ($path_method == NULL) {
            return http\ResponseBuilder::not_found();
        }
        // init end point metaclass
        try {
            $r_ep = new \ReflectionClass($ep['class_name']);
        } catch (\ReflectionException $ex) {
            throw new \Exception('end point class not found', NULL, $ex);
        }
        
        /* FIND METHODS THAT ARE CALLED WITH HTTP REQUEST METHOD */
        
        $methods = self::get_endpoint_methods($r_ep, $request->get_method());
        
        /* FILTER FOUND METHODS BY THEIR SUB PATH PATTERN AND FILL C/P INFO */
        
        $fmethods = self::filter_endpoint_methods_by_path($methods, $path_method);
        
        if (!count($fmethods)) {
            return http\ResponseBuilder::not_found();
        }
        
        $dc_ep = new DocCommentWrapper($r_ep->getDocComment());
        
        $cfmethods = self::fill_in_consume_info($fmethods, $request, 
                $dc_ep->get_values(Annotations::CONSUME_MINES));
        
        if (!count($cfmethods)) {
            return http\ResponseBuilder::unsupported_media();
        }
                        
        $pcfmethods = self::fill_in_produce_info($cfmethods, $request, 
                $dc_ep->get_values(Annotations::PRODUCE_MINES));
        
        if (!count($pcfmethods)) {
            return http\ResponseBuilder::not_acceptable();
        }
        
        /* CHOSE BEST METHOD FOR SERVING THE REQUEST */
        
        // we have more methods we must do some additional comparison
        uasort($pcfmethods, function ($m1, $m2) {
            // less count of arguments means better match
            $cmp = count($m1['args']) - count($m2['args']);
            if ($cmp == 0) {
                // choose method with most priority accept content-type
                $cmp = $m1['produces_rating'] - $m2['produces_rating'];
            }
            return $cmp;
        });
        // take first sorted item
        $serve_method = reset($pcfmethods);
        
        /* SERVE REQUEST */
        
        $args = $serve_method['args'];
        
        // unmarshall input
        if ($request->has_body()) {
            // bad request if POST or PUT and does not have body
            if ($request->get_body() === NULL) {
                return http\ResponseBuilder::bad_request();
            }
            if (!count($serve_method['consumes'])) {
                if (!count($request->get_content_type_header())) {
                    // CT header missing
                    return http\ResponseBuilder::bad_request();
                }
                throw new \ErrorException('@Consumes not defined');
            }
            $mime = reset($serve_method['consumes']);
            $consumer = $this->create_serializer($mime);
            if ($consumer == NULL) {
                return http\ResponseBuilder::unsupported_media();
            }
            // add unmarshalled body as fist argument for serve method
            try {
                $obj = $consumer->unmarshall($request->get_body());
                $args = array($obj) + $args;
            } catch (serializator\SerializationException $ex) {
                return http\ResponseBuilder::server_error($ex);
            }
            
        }
        // call method 
        try {
            $ep_instance = $r_ep->newInstanceArgs();
            // handle PHP errors
            set_error_handler(function($severenity, $message, $file, $line) {
                throw new \Exception('Error during processing: ' . $message);
            });
            // handle PHP fatal errors
            register_shutdown_function(function () {
                static $one_time_only = TRUE; // protection before loop
                $error = error_get_last();
                if ($one_time_only && $error['type'] == E_ERROR) {
                    if (!headers_sent()) {
                        $status_text = common\HttpUtil::status_message(500);
                        header('HTTP/1.0 500 ' . $status_text);
                        header('Content-Type: text/plain');
                    }
                    die("PHP fatal error: " . print_r($error, TRUE));
                }
            });
            $output = $serve_method['rm']->invokeArgs($ep_instance, $args);
            restore_error_handler();
        } catch (\Exception $ex) {
            restore_error_handler();
            return http\ResponseBuilder::server_error($ex);
        }
        
        // data returned right from method? Wpar them!
        if (!($output instanceof http\HttpResponse)) {
            $output = http\ResponseBuilder::ok($output);
        }
        
        // no output? or output already serialized (error case)
        if ($output->get_body() !== NULL &&
                !$output->has_header('Content-Type')) {
            // serialize body
            $producer = NULL;
            $content_type = NULL;
            // find first available serializer
            while ($producer == NULL && !empty($serve_method['produces'])) {
                $content_type = array_shift($serve_method['produces']);
                $producer = $this->create_serializer($content_type);
            }
            if ($producer == NULL) {
                return http\ResponseBuilder::not_acceptable();
            }
            // marshall body and set CT header
            $output->add_header('Content-Type', $content_type);
            try {
                $output->set_body($producer->marshall($output->get_body()));
            } catch (serializator\SerializationException $ex) {
                return http\ResponseBuilder::server_error($ex);
            }
        }
        // return result
        return $output;
    }
    
    /**
     * Renders given response to standart output.
     * 
     * @param \phpaxrs\http\HttpResponse $response
     * @throws InvalidArgumentException on invalid response
     * @throws \ErrorException if render cannot be performed
     */
    public function render($response) {
        // check input
        if (empty($response) || !($response instanceof http\HttpResponse)) {
            throw new InvalidArgumentException('invalid response passed');
        }
        // check state
        if (headers_sent()) {
            throw new \ErrorException('headers were already sended');
        }
        // render status header
        $status_code = $response->get_status();
        $text = common\HttpUtil::status_message($status_code);
        $protocol = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL');
        if (empty($protocol)) {
            $protocol = 'HTTP/1.0';
        }
        header($protocol . ' ' . $status_code . ' ' . $text);
        // render headers
        foreach ($response->get_headers() as $name => $value) {
            header($name . ': ' . $value);
        }
        // render body
        echo $response->get_body();
    }
    
    /**
     * Finds end poin for the given request URL path.
     * 
     * @param string $request_path
     * @return array|null array with key base_path and class_name
     */
    protected function find_end_point($request_path) {
        $found = null;
        $found_lenght = -1;
        // / at the end of path required
        $n_request_path = PathUtil::normalize($request_path);
        // search for match in path of each end point
        foreach ($this->end_points as $ep_bp => $ep) {
            if (mb_strlen($ep_bp) > $found_lenght &&
                    strncmp($n_request_path, $ep_bp, mb_strlen($ep_bp)) === 0) {
                $found = $ep_bp;
                $found_lenght = mb_strlen($found);
            }
        }
        // founded?
        if ($found) {
            return array(
                'base_path'  => $found,
                'class_name' => $this->end_points[$found]
            );
        }
        // not found
        return NULL;
    }
    
    /**
     * Finds serializator that is able to serialize one of given MIME types.
     * 
     * @param string $mime accepted MIME
     * @return string|null class name of serializator or null
     * @throws InvalidArgumentException on invalid MIMEs
     */
    protected function find_serializator($mime) {
        if (empty($mime)) {
            throw new InvalidArgumentException('invalid MIME passed');
        }
        if (array_key_exists($mime, $this->serializators)) {
            return $this->serializators[$mime];
        }
        return NULL;
    }

    /**
     * Gets public implemented non-static methods of a end point that can handle
     * given HTTP method.
     * 
     * @param \ReflectionClass $rep reflection end point metaclass
     * @param string $http_method HTTP method
     * @return Array
     */
    protected static function get_endpoint_methods($rep, $http_method) {
        $l_http_method = strtoupper($http_method);
        $public_methods = array_diff(
                $rep->getMethods(\ReflectionMethod::IS_PUBLIC),
                $rep->getMethods(\ReflectionMethod::IS_STATIC),
                $rep->getMethods(\ReflectionMethod::IS_ABSTRACT)
        );
        $methods = array();
        // filter methods that handles the same HTTP method
        foreach ($public_methods as $method) {
            $r_method = new \ReflectionMethod($rep->getName(), $method->name);
            $dc_method = new DocCommentWrapper($r_method->getDocComment());
            // check if @<method> annotation is present
            if ($dc_method->is_present($l_http_method)) {
                $methods[] = array('rm' => $r_method, 'dc' => $dc_method);
            }
        }
        return $methods;
    }

    /**
     * Filters methods with @Path annotation attribute that do not match the
     * given path.
     * 
     * @param type $methods list of methods
     * @param string $path path to be matched 
     * @return array filtered methods with appended arguments
     */
    protected static function filter_endpoint_methods_by_path($methods, $path) {
        // iterate throught each method and remove those who do not match
        foreach ($methods as $i => $method) {
            // get path template from @Path annotation if no @Path anntation
            // is present than path is same as for whole end point so use "/"
            $templ = $method['dc']->get_first_value(Annotations::URL_PATH, '/');
            if (($args = PathUtil::match($templ, $path)) !== FALSE) {
                $methods[$i]['args'] = $args;
            } else {
                unset($methods[$i]); // filter out
            }
        }
        return $methods;
    }
    
    /**
     * Fills information about consuming MIME types that are supported
     * by given end point methods and filter out methods that are not
     * capable of consuming the request.
     * 
     * @param array $methods list of methods for fill in
     * @param http\HttpRequest $request request for obtaining header info
     * @param array $cdf default consume MIME type
     * @return array filtered methods with appended consumes information
     */
    protected static function fill_in_consume_info(&$methods, $request, $cdf) {
        if (empty($cdf)) {
            $cdf = array();
        }
        // iterate throught each method and fill consume/produce info field
        foreach ($methods as $i => $method) {
            $dc = $method['dc'];
            $ct_mime = $request->get_content_type_header();
            if ($ct_mime != NULL) { // CT header not present?
                $mimes = $dc->get_values(Annotations::CONSUME_MINES, $cdf);
                $consumes = array_intersect(array($ct_mime), $mimes);
                if (!count($consumes)) {
                    unset($methods[$i]); // filter out
                    continue;
                }
                $methods[$i]['consumes'] = $consumes;
            } else {
                $methods[$i]['consumes'] = array();
            }
        }
        return $methods;
    }
    
    /**
     * Fills information about producing MIME types that are supported
     * by given end point methods and filter out methods that are not
     * capable of produce accepted response.
     * 
     * @param array $methods list of methods for fill in
     * @param http\HttpRequest $request request for obtaining header info
     * @param array $pdf default array of produce MIME types
     * @return array filtered methods with appended produce information
     */
    protected static function fill_in_produce_info(&$methods, $request, $pdf) {
        if (empty($pdf)) {
            $pdf = array();
        }
        // iterate throught each method and fill consume/produce info field
        foreach ($methods as $i => $method) {
            $dc = $method['dc'];
            $accepted_mimes = $request->get_accept_header();
            $produced_mimes = $dc->get_values(Annotations::PRODUCE_MINES, $pdf);
            $methods[$i]['produces_rating'] = PHP_INT_MAX;
            // Accept header not present => accept everything
            if (!count($accepted_mimes)) {
                $methods[$i]['produces'] = $produced_mimes;
            } else {
                $methods[$i]['produces'] = array();
                $rating = 0;
                foreach ($accepted_mimes as $amime) {
                    foreach ($produced_mimes as $pmime) {
                        if (common\HttpUtil::accept_match($amime, $pmime)) {
                            if (!count($methods[$i]['produces'])) {
                                $methods[$i]['produces_rating'] = $rating;
                            }
                            $methods[$i]['produces'][] = $pmime;
                        }
                    }
                    $rating++;
                }
                // filter out method on no match
                if (!count($methods[$i]['produces'])) {
                    unset($methods[$i]);
                    continue;
                }
            }
        }
        return $methods;
    }

}
