<?php

/*
 * This file is a part of PHPAX-RS framework, released under terms of GPL-3.0
 * licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights reserved.
 */

namespace phpaxrs\serializator;

/**
 * The "JsonSerializator" class that implements ISerialization for JSON format
 * with MIME type "application/json".
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
class JsonSerializator implements ISerializator {
    
    public function marshall($object) {
        if (!is_array($object) && !is_object($object)) {
            $m = 'only arrays and objects may be marshalled to JSON: ' . $object;
            throw new SerializationException($m);
        }
        if (($json = json_encode($object)) === FALSE) {
            $message = 'cannot marshall object: ' . json_last_error_msg();
            throw new SerializationException($message);
        }
        return $json;
        
    }

    public function unmarshall($object_as_str) {
        if (!is_string($object_as_str)) {
            $m = 'only string may be unmarshalled from JSON to object';
            throw new SerializationException($m);
        }
        if (($object = json_decode($object_as_str, TRUE)) === NULL) {
            $message = 'cannot unmarshall JSON sring: ' . json_last_error_msg();
            throw new SerializationException($message);
        }
        return $object;
    }

}

if (!function_exists('json_last_error_msg')) {

    /**
     * @copyright http://php.net/manual/en/function.json-last-error-msg.php
     */
    function json_last_error_msg() {
        static $errors = array(
            JSON_ERROR_NONE => null,
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters'
        );
        $error = json_last_error();
        return array_key_exists($error, $errors) ? 
                $errors[$error] : 'Unknown error ({$error})';
    }

}
