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
 * TestConfig provides access to test configuration options that are located
 * in configuration file that is automatically loaded within the class.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.2
 */
final class TestConfig
{
    /**
     * Test config file name.
     */
    const CONFIG_FILE = 'tests/config.ini';

    /**
     * @var array Configuration options array
     */
    private static $config = NULL;

    /**
     * Initilize test configuration from INI file.
     *
     * @param string $file INI configuration file relative path [optional]
     */
    public static function init($file = self::CONFIG_FILE)
    {
        if (self::$config == NULL)
        {
            self::load($file);
        }
    }

    /**
     * Get test configuration option that is located in configuration file that
     * is loaded lazily on first call of this method. Configuration can be
     * nested than the name may be defined using dots, e.g. "db.password".
     *
     * @param string $name option name that may be nested using dots
     * @param mixed $default_value default option value if not found[optional]
     * @return mixed option value
     */
    public static function get($name, $default_value = NULL)
    {
        self::init();
        // sub properties (nested)
        $name_parts = explode('.', $name);
        $current_config = self::$config;
        for ($i = 0; $i < count($name_parts) - 1; $i++)
        {
            if (array_key_exists($name_parts[$i], $current_config) &&
                is_array($current_config[$name_parts[$i]]))
            {
                $current_config = $current_config[$name_parts[$i]];
            }
            else
            {
                return $default_value; // not found
            }
        }
        // return value
        $p_name = $name_parts[count($name_parts) - 1];
        return array_key_exists($p_name, $current_config) ?
                $current_config[$p_name] : $default_value;
    }

    /**
     * Loads configuration file from the given path.
     *
     * @param string $file configuration file path
     * @throws Exception on error
     */
    private static function load($file)
    {
        $full_file = DOCROOT . DIRECTORY_SEPARATOR . $file;
        // load
        try
        {
            self::$config = self::read_ini_file($full_file);
        }
        catch (InvalidArgumentException $ex)
        {
            throw new Exception('Invalid test configuration', NULL, $ex);
        }
        // DB config apply
        if (isset(self::$config['db']))
        {
            try
            {
                self::set_db_config(self::$config['db']);
            }
            catch (InvalidArgumentException $ex)
            {
                throw new Exception('Invalid DB test configuration', NULL, $ex);
            }
        }
    }

    /**
     * Loads INI file and return its content.
     *
     * @param string $file file path
     * @return array content of INI file
     * @throws InvalidArgumentException on any error during reading
     */
    private static function read_ini_file($file)
    {
        if (!file_exists($file) || !is_readable($file))
        {
            throw new InvalidArgumentException('conf not readable: ' . $file);
        }
        $content = parse_ini_file($file, TRUE);
        if ($content === FALSE)
        {
            throw new InvalidArgumentException('conf file cannot be parsed');
        }
        return $content;
    }

    /**
     * Sets global application configuration options to one defined by passed
     * array.
     *
     * @param array $config configuration array
     * @throws InvalidArgumentException on invalid passed array
     */
    private static function set_db_config($config)
    {
        static $required_options = array
        (
            'host' => 'db_host',
            'database' => 'db_name',
            'user' => 'db_user',
            'pass' => 'db_password'
        );

        if (empty($config) || !is_array($config))
        {
            throw new InvalidArgumentException('invalid config array');
        }

        if (array_key_exists('type', $config))
        {
            Config::set('db_type', $config['type']);
        }

        foreach ($required_options as $option_name => $dest_option_name)
        {
            if (!array_key_exists($option_name, $config))
            {
                throw new InvalidArgumentException('option ' . $option_name
                        . 'is missing');
            }
            Config::set($dest_option_name, $config[$option_name]);
        }
    }

}
