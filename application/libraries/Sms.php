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
 * Abstract class for SMS drivers.
 * 
 * @author Roman Sevcik, Ondrej Fibich
 */
abstract class Sms
{
	// prefix for drivers classes
	const DRIVER_CLASS_PREFIX = 'Sms_';
	
	// state of driver (these are used in database table config)
	const DRIVER_INACTIVE = 1;
	const DRIVER_ACTIVE = 2;
	
	// ids of drivers (these are used in database table sms_messages)
	const SOUNDWINV100 = 2;
	const KLIKNIAVOLEJ = 3;
	
	/**
	 * Array of availables drivers for factory method.
	 * Keys:
	 * 
	 *	id					ID used in databse
	 *  name				Name
	 *  description			Another info about driver for user
	 *  hostname			Hostname of gateway [optional]
	 *	help				Translatable help hint string [optional]
	 *  test_mode_enabled	Indicator of test mode (driver does not send anything)
	 *
	 * @var array
	 */
	private static $DRIVERS = array
	(
		'SOUNDWINV100' => array
		(
			'id'				=> self::SOUNDWINV100,
			'name'				=> 'Soundwin V100',
			'class'				=> 'Soudvinv100',
			'description'		=> 'GMS',
			'help'				=> 'sms_driver_soudvinv100',
			'test_mode_enabled'	=> FALSE,
		),
		'KLIKNIAVOLEJ' => array
		(
			'id'				=> self::KLIKNIAVOLEJ,
			'name'				=> 'KlikniaVolej.cz',
			'class'				=> 'Klikniavolej',
			'description'		=> 'SMS',
			'hostname'			=> 'kavremote.mobil.cz:80',
			'help'				=> 'sms_driver_klikniavolej',
			'test_mode_enabled'	=> TRUE,
		),
	);
	
	/**
	 * ID of loaded driver (loaded automaticly in factory method)
	 *
	 * @var integer
	 */
	private $driver = FALSE;
	
	/**
	 * Hostname of gate
	 *
	 * @var string
	 */
	protected $hostname;
	
	/**
	 * User for connecting to the gate
	 *
	 * @var string
	 */
	protected $user;
	
	/**
	 * Password for connecting to the gate
	 *
	 * @var string
	 */
	protected $password;
	
	/**
	 * Factory for SMS drivers
	 *
	 * @param mixed $driver	String index of driver or integer ID of driver.
	 * @return Sms			Sms instance or NULL if driver name or ID is incorect.
	 */
	public static function factory($driver)
	{
		$selected_driver = self::_get_driver_index($driver);
		
		if ($selected_driver)
		{
			$driver = self::$DRIVERS[$selected_driver];
			$class_name = self::DRIVER_CLASS_PREFIX . $driver['class'];
			
			if (Kohana::auto_load($class_name))
			{
				/* @var $sms_driver_instance Sms */
				$sms_driver_instance = new $class_name;
				$sms_driver_instance->driver = $driver['id'];
				$sms_driver_instance->hostname = @$driver['hostname'];
				
				return $sms_driver_instance;
			}
		}
			
		return NULL;
	}
	
	/**
	 * Gets index of driver
	 *
	 * @param mixed $driver	String index of driver or integer ID of driver.
	 * @return mixed		String key on success FALSE on error.
	 */
	private static function _get_driver_index($driver)
	{
		if (array_key_exists($driver, self::$DRIVERS))
		{
			return $driver;
		}
		else
		{
			foreach (self::$DRIVERS as $key => $available_driver)
			{
				if ($available_driver['id'] == $driver)
				{
					return $key;
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Gets name of driver
	 *
	 * @param integer $driver	String index of driver or integer ID of driver.
	 * @param boolean $with_help Should contain name help as well [optional]
	 * @return string
	 */
	public static function get_driver_name($driver, $with_help = FALSE)
	{
		$selected_driver = self::_get_driver_index($driver);
		
		if ($selected_driver)
		{
			$d = self::$DRIVERS[$selected_driver];
			
			$name = $d['description'] . ' ' . __('Gateway') . ' ' . $d['name'];
			
			// help available?
			if ($with_help && isset($d['help']))
			{
				$name .= ' ' . help::hint($d['help']);
			}
			
			return $name;
		}
		
		return __('Inactive');
	}
	
	/**
	 * Gets drivers array
	 *
	 * @return array
	 */
	public static function get_drivers()
	{
		return self::$DRIVERS;
	}
	
	/**
	 * Gets list of active drivers for selectboxes.
	 * Key is id of driver and value is name.
	 *
	 * @return array
	 */
	public static function get_active_drivers()
	{
		$available_drivers = self::get_drivers();
		$active_drivers = array();
		
		foreach ($available_drivers as $driver)
		{
			$key = $driver['id'];
			
			if (Settings::get('sms_driver_state' . $key) == Sms::DRIVER_ACTIVE)
			{
				$active_drivers[$key] = Sms::get_driver_name($key);
			}
		}
		
		return $active_drivers;
	}
	
	/**
	 * Check if there are any active drivers
	 *
	 * @return bool
	 */
	public static function has_active_driver()
	{
		return count(self::get_active_drivers());
	}
	
	/**
	 * Checks if SMS are enabled on server
	 *
	 * @return bool
	 */
	public static function enabled()
	{
		return function_exists('curl_init');
	}

	/**
	 * Construct cannot be called from outside
	 */
	protected function __construct()
	{
	}
	
	/**
	 * Sets hostname of gate
	 *
	 * @param string $hostname 
	 */
	public function set_hostname($hostname)
	{
		$this->hostname = $hostname;
	}

	/**
	 * Sets user to gate
	 *
	 * @param string $user 
	 */
	public function set_user($user)
	{
		$this->user = $user;
	}

	/**
	 * Sets password to gate
	 *
	 * @param string $password 
	 */
	public function set_password($password)
	{
		$this->password = $password;
	}
	
	/**
	 * Sets test (no SMS are sended, just states are made).
	 * Do nothing by default.
	 *
	 * @param bool $test
	 */
    public function set_test($test)
    {
    }

	/**
	 * Gets state of message
	 *
	 * @return mixed	State or FALSE on no error
	 */
	abstract public function get_status();

	/**
	 * Gets error report
	 *
	 * @return mixed	Error report or FALSE on no error
	 */
	abstract public function get_error();

	/**
	 * Test if connection to server is OK
	 *
	 * @return bool
	 */
	abstract public function test_conn();

	/**
	 * Try to send SMS messages
	 *
	 * @param string $sender	Sender of message
	 * @param string $recipient	Recipier of message
	 * @param string $message	Text of message
	 * @return boolean			FALSE on error TRUE on success 
	 */
	abstract public function send($sender, $recipient, $message);

	/**
	 * Try to receive SMS messages
	 *
	 * @return boolean		FALSE on error TRUE on success 
	 */
	abstract public function receive();
	
	/**
	 * Gets recieved messages after receive
	 * 
	 * @return array
	 */
	abstract public function get_received_messages();

}
