<?php

/*
 * This file is a part of PHPAX-RS framework, released under terms of GPL-3.0
 * licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights reserved.
 */

namespace phpaxrs\common;

/**
 * The "HttpUtil" class that cotain utility methods related to HTTP.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
class HttpUtil {
    
    /**
     * List of all HTTP status messages.
     *
     * @var Array
     */
    private static $messages = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );
    
    /**
     * Gets message for the given HTTP status code.
     * 
     * @param int $status
     * @return null|string
     */
    public static function status_message($status) {
        if (array_key_exists($status, self::$messages)) {
            return self::$messages[$status];
        }
        return NULL;
    }
    
    /**
     * Parses HTTP accept header and returned ordered by height.
     * 
     * @param string $accepts HTTP accept header string
     * @return array array of MIME types
     */
    public static function parse_accept_header($accepts) {
        if (empty($accepts)) {
            return array();
        }
        // parse parts of accepts
        $accepts_parts = array_map('trim', explode(',', $accepts));
        $accepts_parsed = array();
        // parse
        foreach ($accepts_parts as $accepts_part) {
            $subparts = array_map('trim', explode(';', $accepts_part));
            $weight = 1.0;
            if (count($subparts) > 1 && strncmp($subparts[1], 'q=', 2) == 0) {
                $weight = floatval(substr($subparts[1], 2));
            }
            $accepts_parsed[$subparts[0]] = $weight;
        }
        // sort by weight (we need stable algorithm!)
        array_multisort($accepts_parsed, SORT_DESC, SORT_NUMERIC,
                range(1, count($accepts_parsed)), SORT_ASC, SORT_NUMERIC);
        // return sorted accepts MIMEs
        return array_keys($accepts_parsed);
    }

    /**
     * Checks whether given accept MIME that may contain * match given full
     * MIME.
     * 
     * @param string $accept_mime MIME type that may contain * (e.g. text/*)
     * @param string $mime full MIME (e.g. application/json)
     * @return boolean match?
     */
    public static function accept_match($accept_mime, $mime) {
        // invalid MIME
        if (!preg_match('/^([*]|[-\w]+)\/([*]|[-+\w]+)$/', $accept_mime) ||
                !preg_match('/^[-\w]+\/[-+\w]+$/', $mime)) {
            return FALSE;
        }
        // string match
        if (strpos($accept_mime, '*') === FALSE) {
            return (strcmp($accept_mime, $mime) == 0); 
        }
        // build regex
        $am_regex = '|' . str_replace('*', '([-\w]+)', $accept_mime) . '|';
        // match?
        return !!preg_match($am_regex, $mime);
    }

}
