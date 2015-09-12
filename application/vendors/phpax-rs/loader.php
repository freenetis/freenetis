<?php

/* 
 * This file is a part of PHPAX-RS framework, released under terms of GPL-3.0
 * licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights reserved.
 */

if (PHP_VERSION_ID < 50301) {
    throw new Exception('PHPAX-RS requires PHP 5.3.1 or newer.');
}

spl_autoload_register(function ($class) {
    static $ds = DIRECTORY_SEPARATOR;
    $class_path = explode('\\', ltrim($class, '\\'));
    if (array_shift($class_path) != 'phpaxrs') {
        return FALSE;
    }
    $file_path = __DIR__ . $ds . implode($ds, $class_path) . '.php';
    require_once $file_path;
    return class_exists($class);
});
