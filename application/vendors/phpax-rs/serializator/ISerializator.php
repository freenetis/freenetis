<?php

/*
 * This file is a part of PHPAX-RS framework, released under terms of GPL-3.0
 * licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights reserved.
 */

namespace phpaxrs\serializator;

/**
 * Interface that defines required methods for content serialization utilities.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
interface ISerializator {
    
    /**
     * Marhall given object to string reprezentation in serializer's format.
     * 
     * @param mixed $object
     * @return string stringified object
     * @throws SerializationException
     */
    public function marshall($object);
    
    /**
     * Unmarhall given from string reprezentation in serializer's format to 
     * object.
     * 
     * @param string $object_as_str stringified object
     * @return mixed object
     * @throws SerializationException
     */
    public function unmarshall($object_as_str);
    
}

/**
 * Exception that is triggered on unsucessfull serialization.
 */
class SerializationException extends \Exception {
    public function __construct($message = NULL, $code = NULL, $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
