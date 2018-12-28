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
 * Settings are cached except passwords because of security.
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
	 * When turned ON no DB queries are performed. Useful for non-setup
	 * environment.
	 *
	 * @var bool
	 */
	private static $offline_mode = FALSE;
	
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
		/**
		 * ALLOWED SUBNETS SETTINGS
		 */
		// default count of allowed subnets
		'allowed_subnets_default_count'		=> 1,
		// allowed subnets is enabled by default
		'allowed_subnets_enabled'			=> 1,
		// interval of updating of allowed subnets, default 60s
		'allowed_subnets_update_interval'	=> 60,
		// time of last update of allowed subnets
		'allowed_subnets_update_last'		=> 0,
		
		// if self member registration is enabled then this value gives
		// a day count after which the application that is connected and
		// he have not submit his member registration
		// is bannet from using of this connection (redirected)
		'applicant_connection_test_duration' => 14,
		
		/**
		 * CGI SCRIPTS SETTINGS
		 */
		// URL for ARP table
		'cgi_arp_url' => 'http://{GATEWAY_IP_ADDRESS}/cgi-bin/arp.cgi?ip_address={IP_ADDRESS}',
		
		/**
		 * CONNECTION REQUESTS SETTINGS
		 */
		// are connection requests enabled?
		'connection_request_enable'			=> 0,
		
		/**
		 * FINANCE SETTINGS
		 */
		// default currency is Czech crown
		'currency'							=> 'CZK',
		
		// deduct day (1-31) (#332)
		// default value is 15 because this constant was used before FreenetIS 1.1
		'deduct_day'						=> 15,
		
		/**
		 * DATABASE SETTINGS
		 */
		// DB schema version starts from zero
		'db_schema_version'					=> 0,
		// if a deadlock came in, whole transaction may be executed again
		// this property sets the max count of repeats of execution. (#284)
		'db_trans_deadlock_repeats_count'	=> 4,
		// if repeats (previous variable) are set as greater than 1, this
		// timeout in ms defines time to next repeat of execution. (#284)
		'db_trans_deadlock_repeats_timeout'	=> 100,
		
		// time theshold in seconds, before DHCP server is out of date
		'dhcp_server_reload_timeout'		=> 1800,
		
		/**
		 * E-MAIL SETTINGS
		 */
		// defaul email address
		'email_default_email'				=> 'no-reply@freenetis.org',
		// default email driver
		'email_driver'						=> 'native',
		// default email port
		'email_port'						=> 25,
		// default email connection encryption
		'email_encryption'					=> 'none',
		// default value for prefix of subject of notification
		// e-mails to members
		'email_subject_prefix'				=> 'FreenetIS',
		
		// ID of bank account that is shown on registration (if null random is used)
		'export_header_bank_account'		=> NULL,
		
		// finance is enabled by default
		'finance_enabled'					=> TRUE,
		
		// enable/disable removing of member's devices on member leaving day (#738)
		'former_member_auto_device_remove'	=> FALSE,
		
		// whether hide grid on its first load (for optimalization) (#442)
		'grid_hide_on_first_load'			=> FALSE,
		
		// display index.php in URL
		'index_page'						=> 1,
		
		/**
		 * INITIAL IMMUNITY SETTINGS
		 */
		// count of days in which new members will not be blocked
		// and notificated as debtor, default 35
		'initial_debtor_immunity'			=> 35,
		// count of days in which new members will not be notificated
		// to pay, default 14
		'initial_immunity'					=> 14,
		
		// IP adresses states interval
		'ip_addresses_states_interval'		=> 60,
		
		// date of last deduct of device fees
		'last_deduct_device_fees'			=> '0000-00-00',
		
		/**
		 * LOCAL SUBNETS SETTINGS
		 */
		// variables for local subnets update
		'local_subnets_update_interval'		=> 86400,
		'local_subnets_update_last'			=> 0,
		
		// minimal membership interrupt period is 1 month
		'membership_interrupt_minimum'		=> 1,
		
		// time threshold in minutes, before module is shown as inactive
		'module_status_timeout'				=> 2,
		
		/**
		 * MONITORING SETTINGS
		 */
		// interval of notication for host down
		'monitoring_notification_down_host_interval'	=> 10,
		// interval of monitoring notification
		'monitoring_notification_interval'	=> 1,
		// interval of notication for host up
		'monitoring_notification_up_host_interval'		=> 5,
		
		// networks is enabled by default
		'networks_enabled'					=> TRUE,
		
		// notificatuion is enabled by default
		'notification_enabled'				=> TRUE,
		// allows to enable/disable putting notification message as the e-mail
		// message subject suffix
		'notification_email_message_name_in_subject' => TRUE,
		
		// password check also for MD5 algorithm (not only fo SHA1)
		// this is here because of posibility of transformation from old data
		// structures (another IS). I (Ondrej Fibich) made this due to
		// import of passwords from old IS of PVFREE association (2013-02-15)
		'pasword_check_for_md5'				=> FALSE,
		
		/**
		 * QOS SETTINGS
		 */
		// qos is enabled
		'qos_enabled'						=> 0,
		// speed for active
		'qos_active_speed'					=> '1M/2M',
		
		/**
		 * REDIRECTION SETTINGS
		 */
		// redirection is enabled by default
		'redirection_enabled'				=> TRUE,

		// minimal membership interrupt period is 1 month
		'membership_interrupt_minimum'		=> 1,

		/**
		 * VTIGER SETTINGS
		 */
		// vtiger field names - members
		'vtiger_member_fields'				=> '{"name":"accountname","acc_type":"accountype","entrance_date":"",
												"organization_identifier":"","var_sym":"","type":"",
												"street":"bill_street","town":"bill_city","country":"bill_country",
												"zip_code":"bill_code","phone1":"phone","phone2":"","phone3":"",
												"email1":"email1","email2":"","email3":"","employees":"employees",
												"do_not_send_emails":"emailoptout","notify_owner":"notify_owner",
												"comment":"description","id":""}',
		// vtiger field names - users
		'vtiger_user_fields'				=> '{"name":"firstname","middle_name":"","surname":"lastname",
												"pre_title":"","post_title":"","member_id":"account_id",
												"street":"mailingstreet","town":"mailingcity",
												"country":"mailingcountry","zip_code":"mailingzip","phone1":"phone",
												"phone2":"","phone3":"","email1":"email1","email2":"","email3":"",
												"birthday":"birthday","do_not_call":"donotcall",
												"do_not_send_emails":"emailoptout","notify_owner":"notify_owner",
												"comment":"description","id":""}',

		'redirection_port_self_cancel'		=> 80,
		
		/**
		 * SECURITY SETTINGS
		 */
		// default minimal password length
		'security_password_length'			=> 8,
		// default minimal password level is good
		'security_password_level'			=> 3,
		
		// default text for self cancel of a cancellable redirection
		'self_cancel_text'					=> 'OK, I am aware',
		
		// self applicant registration is enabled by default
		'self_registration'					=> 1,
		'self_registration_enable_approval_without_registration' => 1,
		'self_registration_enable_additional_payment' => 1,
		
		// default title of system
		'title'								=> 'FreenetIS',
		
		/**
		 * ULOGD SETTINGS
		 */
		// count of the most traffic-active members to find, default 10% of members
		'ulogd_active_count'				=> '10%',
		// type of traffic of members to find, default download traffic
		'ulogd_active_type'					=> 'download',
		// ulogd settings
		'ulogd_enabled'						=> 1,
		// interval of updating of ulogd (in seconds), default 1800s' => 30 minutes
		'ulogd_update_interval'				=> 1800,
		// time of last update of ulogd
		'ulogd_update_last'					=> 0,
		
		/**
		 * UPLOAD SETTINGS
		 */
		// upload can create directorie by default
		'upload_create_directories'			=> 1,
		// default upload directory is upload
		'upload_directory'					=> 'upload',
		// upload can remove spaces by default
		'upload_remove_spaces'				=> 1,
		
		// javascript is enabled by default
		'use_javascript'					=> 1,

        // contact duplicities
        'user_email_duplicities_enabled'    => FALSE,
        'user_phone_duplicities_enabled'    => FALSE,

		// user birthday
		'users_birthday_empty_enabled'		=> FALSE,
		
		// username regex #360
		'username_regex'					=> '/^[a-z][a-z0-9_]{4,}$/',

		// former member delete limit
		'member_former_limit_years'			=> 5,
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
			if (self::$offline_mode)
			{
				return FALSE;
			}

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

		$value = '';

		// try if query return exception, for example config table doesn't exist
		try
		{
			if (!self::$offline_mode)
			{
				$value = self::$config_model->get_value_from_name($key);
			}
		}
		catch (Kohana_Database_Exception $e)
		{
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
		if (self::$offline_mode)
		{
			self::cache_value_set($key, $value);
			return FALSE;
		}

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
	
	/**
	 * Enable/disable DB queries on get/set.
	 *
	 * @param bool $flag
	 */
	public static function set_offline_mode($flag)
	{
		self::$offline_mode = $flag;
	}

}
