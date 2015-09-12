<?php

/*
 *  This file is part of open source system FreenetIS
 *  and it is release under GPLv3 licence.
 *
 *  More info about licence can be found:
 *  http://www.gnu.org/licenses/gpl-3.0.html
 *
 *  More info about project can be found:
 *  http://www.freenetis.org/
 */

/**
 * Abstract integration test case that allows to test parts of FreenetIS with
 * up-to-date database schema and provides access to database connection
 * and services.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
abstract class AbstractItCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var Database
     */
    protected static $connection;

    /**
     * @var ServiceFactory
     */
    protected static $services;

    /**
     * Reset URL setting to given values.
     *
     * @param string $domain installed domain name
     * @param string $path installed directory sub-path
     */
    private static function reset_url_settings($domain, $path)
    {
        // set base domain
        Settings::set('domain', $domain);
        // set subdirectory
        Settings::set('suffix', $path);
    }
    
    /**
     * Reset URL setting to values in test configuration.
     */
    protected static function reset_url_settings_to_current()
    {
        $domain = TestConfig::get('url.domain', 'localhost');
        $path = TestConfig::get('url.path', '/freenetis/');
        self::reset_url_settings($domain, $path);
    }

    /**
     * Overridden setup before class in order to init/update databse schema
     * and setup provided service factory and DB connection.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        // service factory
        self::$services = new ServiceFactory();
        // init DB schema if not already
        $lck_file = server::base_dir() . '/upload/mutex';
        self::$services->injectCoreDatabaseInit()->make($lck_file, function ()
        {
            self::reset_url_settings_to_current();
        });
        // get DB connection
        self::$connection = Database::instance();
    }

}
