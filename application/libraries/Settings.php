<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is released under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Settings of whole FreenetIS.
 * Settings are casched except passwords because of security.
 */
class Settings
{
	/**
	 * Config model for getting values from database
	 * 
	 * @var Config_Model
	 */
	private static $config_model = NULL;
	
	/**
	 * Variable for cache
	 * 
	 * @var array
	 */
	private static $cache = array();
	
	/**
	 * Default values of settings
	 * 
	 * @var array
	 */
	private static $default_values = array
	(
		// default title of system
		'title'								=> 'FreenetIS',
		// DB schema version starts from zero
		'db_schema_version'					=> 0,
		// default currency is Czech crown
		'currency'							=> 'CZK',
		// self applicant registration is enabled by default
		'self_registration'					=> 1,
		// javascript is enabled by default
		'use_javascript'					=> 1,
		// display index.php in URL
		'index_page'						=> 1,
		// defaul email address
		'email_default_email'				=> 'no-reply@freenetis.org',
		// default upload directory is upload
		'upload_directory'					=> 'upload',
		// upload can remove spaces by default
		'upload_remove_spaces'				=> 1,
		// upload can create directorie by default
		'upload_create_directories'			=> 1,
		// default email driver
		'email_driver'						=> 'native',
		// default email port
		'email_port'						=> 25,
		// ulogd settings
		'ulogd_enabled'						=> 1,
		// time of last update of ulogd
		'ulogd_update_last'					=> 0,
		// interval of updating of ulogd (in seconds), default 1800s' => 30 minutes
		'ulogd_update_interval'				=> 1800,
		// count of the most traffic-active members to find, default 10% of members
		'ulogd_active_count'				=> '10%',
		// type of traffic of members to find, default download traffic
		'ulogd_active_type'					=> 'download',
		// allowed subnets is enabled by default
		'allowed_subnets_enabled'			=> 1,
		// time of last update of allowed subnets
		'allowed_subnets_update_last'		=> 0,
		// interval of updating of allowed subnets, default 60s
		'allowed_subnets_update_interval'	=> 60,
		// default count of allowed subnets
		'allowed_subnets_default_count'		=> 1,
		// default value for prefix of subject of notification
		// e-mails to members
		'email_subject_prefix'				=> 'FreenetIS',
		// IP adresses states interval
		'ip_addresses_states_interval'		=> 60,
		// count of days in which new members will not be notificated
		// to pay, default 14
		'initial_immunity'					=> 14,
		// count of days in which new members will not be blocked
		// and notificated as debtor, default 35
		'initial_debtor_immunity'			=> 35,
		'redirection_port_self_cancel'		=> 80,
		'qos_enabled'						=> 0,
		'qos_active_speed'					=> '1M/2M',
		// variables for local subnets update
		'local_subnets_update_last'			=> 0,
		'local_subnets_update_interval'		=> 86400,
		// time threshold in minutes, before module is shown as inactive
		'module_status_timeout'				=> 2
	);
	
	/**
	 * Sets cache item if key of item does not contains word 'pass',
	 * because of security.
	 *
	 * @param string $key
	 * @param mixed $value 
	 */
	private static function cache_value_set($key, $value)
	{
		if (strstr($key, 'pass') === FALSE)
		{
			self::$cache[$key] = $value;
		}
	}
	
	/**
	 * Inits settings
	 * 
	 * @return boolean
	 */
	private static function init()
	{
		// not connected? connect!
		if (!self::$config_model)
		{
			try
			{
				// create config model
				self::$config_model = new Config_Model();
			
				// get whole config table to memory
				self::$cache = self::$config_model->get_all_values();
			}
			catch (Kohana_Database_Exception $e)
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}

	/**
	 * Function to get value from settings by given key
	 * 
	 * @author Michal Kliment
	 * @param string $key Key of settings to find
	 * @return string Value from settings
	 */
	public static function get($key, $cache = TRUE)
	{
		// init
		self::init();
		
		// if cache is enabled, return it from it
		if ($cache && isset(self::$cache[$key]))
		{
			return self::$cache[$key];
		}

		// try if query return exception, for example config table doesn't exist
		try
		{
			$value = self::$config_model->get_value_from_name($key);
		}
		catch (Kohana_Database_Exception $e)
		{
			$value = '';
		}

		// if we find not-null value, return it
		if (!empty($value))
		{
			self::cache_value_set($key, $value);
			return $value;
		}
		// else return default value
		else if (isset(self::$default_values[$key]))
		{
			self::cache_value_set($key, self::$default_values[$key]);
			return self::$default_values[$key];
		}
		else
		// in worst return value from config (from config.php)
		{
			$value = Config::get($key);
			self::cache_value_set($key, $value);
			return $value;
		}
	}

	/**
	 * Function to set up given value to given key
	 * 
	 * @author Michal Kliment
	 * @param string $key Key to set up
	 * @param string $value Value to set up to key
	 * @return boolean
	 */
	public static function set($key, $value)
	{
		// init
		self::init();

		// try if query return exception, for example config table doesn't exist
		try
		{
			$exists = self::$config_model->check_exist_variable($key);

			// key already exists, update it
			if ($exists && self::$config_model->update_variable($key, $value))
			{
				self::cache_value_set($key, $value);
				return TRUE;
			}
			// key doesn't exist, create it
			else if (self::$config_model->insert_variable($key, $value))
			{
				self::cache_value_set($key, $value);
				return TRUE;
			}
		}
		catch (Kohana_Database_Exception $e)
		{
			// database error, end
			return FALSE;
		}
		
		return FALSE;
	}

}
