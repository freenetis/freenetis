<?php

/*
 * This file is a part of PHPAX-RS framework, released under terms of GPL-3.0
 * licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights reserved.
 */

namespace phpaxrs\common;

use \InvalidArgumentException;

/**
 * The "PathUtil" class that provides tools for working with URL paths.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
class PathUtil {
    
    /**
     * Checks validity of given URL path.
     * 
     * @param string $path
     * @return bool
     */
    public static function is_valid($path) {
        return preg_match('/^(\/[a-zA-Z0-9_\-.]*)+$/', $path) == 1;
    }

    /**
     * Checks validity of given URL path template.
     * 
     * @param string $template
     * @return bool
     */
    public static function is_valid_template($template) {
        $regex = '/^(\/([a-zA-Z0-9_\-.]|\{[a-zA-Z]+(:[^}.]+)?\})*)+$/';
        return preg_match($regex, $template) == 1;
    }
    
    /**
     * Normalizes path which means that it adds "/" to the start or to the end
     * of path if the are missing.
     * 
     * @param string $path Path to be nomalized
     * @return string normalized path 
     */
    public static function normalize($path) {
        return '/' . ltrim(rtrim($path, '/') . '/', '/');
    }
    
    /**
     * Gets relative path from absolute path by removing path prefix that is
     * given.
     * 
     * @param string $prefix_path
     * @param string $absolute_path
     * @return null|string null if absolute path does not start with prefix
     *                     relative path otherwise
     */
    public static function relative($prefix_path, $absolute_path) {
        $prefix_path = self::normalize($prefix_path);
        $na_path = self::normalize($absolute_path);
        if (strncmp($na_path, $prefix_path, mb_strlen($prefix_path)) != 0) {
            return NULL;
        }
        return self::normalize(substr($na_path, mb_strlen($prefix_path)));
    }
    
    /**
     * Tries to match path template agains a path and gets attributes values
     * from path. Path template is a path string that may contain arguments that 
     * are denoted by {argumentName}.
     * 
     * @example path "/abs/23/34-a" will be matched agains template 
     *          "/abs/{id}/{sid}-a" and arguments array('23', '34') will
     *          be retrieved
     * 
     * @param string $path_template path teplate that may contain arguments
     * @param string $path real path that will be matched agains template
     * @param array $args reference for passing arguments from path using template
     * @return bool|array FALSE on no match otherwise array of gained arguments
     * @throws InvalidArgumentException on invalid path template
     */
    public static function match($path_template, $path) {
        // check input
        if (!self::is_valid($path)) {
            throw new InvalidArgumentException('invalid path passed');
        }
        if (!self::is_valid_template($path_template)) {
            throw new InvalidArgumentException('invalid path template');
        }
        // normalize paths
        $path = self::normalize($path);
        $path_template = self::normalize($path_template);
        // replace / . and arguments in path teplate and transform them 
        // for their usage in regex (order of patterns is important!)
        $transformations = array(
            '/\{[a-zA-Z]+:([^}]+)\}/' => '($1)',     // 1: arguments with regex
            '/{[^}]+}/'               => '([^\/]+)', // 2: simple arguments
            '/[.]/'                   => '[.]'       // 3: dot in path
        );
        $path_regex = preg_replace(array_keys($transformations),
                array_values($transformations), $path_template);
        if ($path_regex === NULL) {
            throw new InvalidArgumentException('invalid @Path arg.');
        }
        // wrap regex with delimiters | that is not present in URL
        $path_regex = '|^' . $path_regex . '$|';
        // check path
        $args = array();
        if (($rm = preg_match($path_regex, $path, $args)) === FALSE) {
            throw new InvalidArgumentException('invalid @Path arg.');
        }
        // matched?
        if ($rm > 0) {
            if (empty($args) || !count($args)) {
                return array();
            }
            // shift matched string
            array_shift($args);
            return $args;
        }
        // no match
        return FALSE;
    }

}
