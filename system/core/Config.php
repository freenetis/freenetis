<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Loads configuration files and retrieves keys. This class is declared as final.
 * 
 * $Id: Config.php 1694 2008-01-10 04:21:56Z zombor $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
final class Config
{
	/**
	 * Entire configuration
	 *
	 * @var array
	 */
	private static $cache;
	
	/**
	 * Default variables
	 *
	 * @var array
	 */
	private static $default_variables = array
	(
		// default site lang is czech
		'lang'				=> 'cs',
		// default locale is czech
		'language'			=> 'cs_CZ',
		// allowed site loacles
		'allowed_locales'	=> array
		(
			'cs' => 'cs_CZ',
			'en' => 'en_US',
		),
		// allow to overide config properties
		'allow_config_set'	=> TRUE,
		// logging disabled by default
		'log_threshold'		=> 0,
	);
	
	/**
	 * Include paths
	 *
	 * @var array
	 */
	private static $include_paths = array();

	/**
	 * Get a config item or group.
	 *
	 * @param string $key
	 * @param boolean $slash
	 * @param boolean $required
	 * @return string
	 */
	public static function get($key, $slash = FALSE, $required = TRUE)
	{		
		// config.php loading
		if (self::$cache === NULL)
		{
			// Invalid config file
			if (file_exists('config' . EXT))
			{
				require('config' . EXT);
			}

			if (!isset($config))
			{
				$config = array();
			}

			// Load config into self
			self::$cache = $config;

			self::include_paths(TRUE);
		}

		// load from chache
		if (isset(self::$cache[$key]))
		{
			return self::$cache[$key];
		}

		// load from static variables
		if (isset(self::$default_variables[$key]))
		{
			return self::$default_variables[$key];
		}

		// not founded
		return '';
	}

	/**
	 * Sets a configuration item, if allowed.
	 *
	 * @param   string   config key string
	 * @param   string   config value
	 * @return  boolean
	 */
	public static function set($key, $value)
	{
		// Config setting must be enabled
		if (Config::get('allow_config_set') == FALSE)
		{
			Log::add('debug', 'Config::set was called, but your configuration file does not allow setting.');
			return FALSE;
		}

		// Empty keys and allow_set cannot be set
		if (empty($key) OR $key == 'allow_config_set')
			return FALSE;

		// Do this to make sure that the config array is already loaded
		Config::get($key);

		self::$cache[$key] = $value;

		return TRUE;
	}

	/**
	 * Get all include paths.
	 *
	 * @param   boolean  re-process the include paths
	 * @return  array    include paths, APPPATH first
	 */
	public static function include_paths($process = FALSE)
	{
		if ($process == TRUE)
		{
			// Start setting include paths, APPPATH first
			self::$include_paths = array(APPPATH);

			// Finish setting include paths by adding SYSPATH
			self::$include_paths[] = SYSPATH;
		}

		return self::$include_paths;
	}

}

// End Config