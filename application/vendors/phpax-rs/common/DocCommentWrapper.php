<?php

/*
 * This file is a part of PHPAX-RS framework, released under terms of GPL-3.0
 * licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights reserved.
 */

namespace phpaxrs\common;

/**
 * The "DocCommentWrapper" class that provides access to annotations that are
 * present in PHP DOC comment tht is passed via contructor.
 * 
 * Annotations should have following synax:
 * 
 *  annotation ::= '@' annotationName annotationArgumentList
 *  annotation ::= '@' annotationName
 *  annotationName ::= [a-zA-Z]+
 *  annotationArgumentList ::= '(' annotationArgument ')'
 *  annotationArgument ::= [^(]+
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
class DocCommentWrapper {
    
    /**
     * Regex for annotations according to annotation syntax
     */
    const ANNOTATION_REGEX = '/[@]([A-Za-z]+)([(]([^)]+)[)])?/';
    
    /**
     * Parsed annotations with their attributes.
     *
     * @var Array
     */
    private $annotations;
    
    /**
     * Create wrapper for the given DOC comment.
     * 
     * @param string $doc_comment
     * @throws \InvalidArgumentException on invalid 
     */
    public function __construct($doc_comment) {
        $this->annotations = $this->parse($doc_comment);
    }
    
    /**
     * Parse annotations from given DOC comment string.
     * 
     * @param string $dc DOC comment
     * @return array with key as annotations names and list of arguments of all
     *               same annotations as value
     */
    public static function parse($dc) {
        $annotations = array();
        $matches = NULL;
        // find all annotations
        preg_match_all(self::ANNOTATION_REGEX, $dc, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (!array_key_exists($match[1], $annotations)) {
                $annotations[$match[1]] = array();
            }
            if (!empty($match[3])) {
                $annotations[$match[1]][] = $match[3];
            }
        }
        return $annotations;
    }
    
    /**
     * Is annotation with the given name available?
     * 
     * @param string $annotation_name
     */
    public function is_present($annotation_name) {
        return array_key_exists($annotation_name, $this->annotations);
    }
    
    /**
     * Gets first attribute of annotations with the given name.
     * 
     * @param string|null $default default value [optional]
     * @return string|null attribute string value or null if attribute not 
     *                      present
     */
    public function get_first_value($annotation_name, $default = NULL) {
        if ($this->is_present($annotation_name) &&
                count($this->annotations[$annotation_name])) {
            return reset($this->annotations[$annotation_name]);
        }
        return $default;
    }
    
    /**
     * Gets attributes of annotations with the given name.
     * 
     * @param string $annotation_name
     * @param array $default default value [optional]
     * @return array list of annotations attributes
     */
    public function get_values($annotation_name, $default = array()) {
        if ($this->is_present($annotation_name)) {
            return $this->annotations[$annotation_name];
        }
        return $default;
    }
    
}
