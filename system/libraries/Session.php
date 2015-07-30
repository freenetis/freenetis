<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Session library.
 *
 * $Id: Session.php 1911 2008-02-04 16:13:16Z PugFish $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Session
{

	/**
	 * Session singleton
	 * 
	 * @var Session
	 */
	private static $instance;
	
	/**
	 * Protected key names (cannot be set by the user)
	 *
	 * @var array
	 */
	protected static $protect = array
	(
			'session_id', 'user_agent', 'last_activity',
			'ip_address', 'total_hits', '_kf_flash_'
	);
	
	/**
	 * Configuration
	 *
	 * @var array
	 */
	protected static $config;
	
	/**
	 * Instance of driver
	 *
	 * @var Session_Driver
	 */
	protected static $driver;
	
	/**
	 * Driver name
	 *
	 * @var string
	 */
	protected static $driver_name = 'cookie';
	
	/**
	 * Session name
	 *
	 * @var string
	 */
	protected static $name = 'freenetissession';
	
	/**
	 * Validate
	 *
	 * @var mixed
	 */
	protected static $validate = array('user_agent');
	
	/**
	 * Flash messages
	 *
	 * @var mixed
	 */
	protected static $flash;
	
	/**
	 * Input library
	 *
	 * @var Input
	 */
	protected $input;

	/**
	 * Singleton instance of Session.
	 * 
	 * @return Session
	 */
	public static function & instance()
	{
		// Create the instance if it does not exist
		empty(self::$instance) and new Session;

		return self::$instance;
	}

	/**
	 * On first session instance creation, sets up the driver and creates session.
	 */
	public function __construct()
	{
		$this->input = new Input;

		if (Config::get('session_driver') != '')
			self::$driver_name = Config::get('session_driver');

		if (Config::get('session_name') != '')
			self::$name = Config::get('session_name');

		if (Config::get('session_validate') != '')
			self::$validate = Config::get('session_validate');

		// This part only needs to be run once
		if (self::$instance === NULL)
		{
			// Makes a mirrored array, eg: foo=foo
			self::$protect = array_combine(self::$protect, self::$protect);

			if (self::$driver_name != 'native')
			{
				// Set driver name
				$driver = 'Session_' . ucfirst(self::$driver_name) . '_Driver';

				// Load the driver
				if (!Kohana::auto_load($driver))
					throw new Kohana_Exception('session.driver_not_supported', self::$config['driver']);

				// Initialize the driver
				self::$driver = new $driver();

				// Validate the driver
				if (!(self::$driver instanceof Session_Driver))
					throw new Kohana_Exception('session.driver_implements', self::$config['driver']);
			}

			// Create a new session
			$this->create();

			// Regenerate session id
			if (Config::get('session_regenerate') > 0 AND ($_SESSION['total_hits'] % Config::get('session_regenerate')) === 0)
			{
				$this->regenerate();
			}

			// Close the session just before sending the headers, so that
			// the session cookie(s) can be written.
			Event::add('system.send_headers', array($this, 'write_close'));

			// Singleton instance
			self::$instance = $this;
		}

		Log::add('debug', 'Session Library initialized');
	}

	/**
	 * Get the session id.
	 *
	 * @return  string
	 */
	public function id()
	{
		return $_SESSION['session_id'];
	}

	/**
	 * Create a new session.
	 *
	 * @param   array  variables to set after creation
	 * @return  void
	 */
	public function create($vars = NULL)
	{
		// Destroy the session
		$this->destroy();

		// Set the session name after having checked it
		if (!ctype_alnum(self::$name) OR ctype_digit(self::$name))
			throw new Kohana_Exception('session.invalid_session_name', self::$name);

		session_name(self::$name);

		// Configure garbage collection
		ini_set('session.gc_probability', (int) Config::get('session_gc_probability'));
		ini_set('session.gc_divisor', 100);
		ini_set('session.gc_maxlifetime', (Config::get('session_expiration') == 0) ? 86400 : Config::get('session_expiration'));

		// Set the session cookie parameters
		// Note: the httponly parameter was added in PHP 5.2.0
		if (version_compare(PHP_VERSION, '5.2', '>='))
		{
			session_set_cookie_params(
					Config::get('session_expiration'),
					//Config::get('cookie.path'),
					'/', Config::get('cookie.domain'),
					Config::get('cookie.secure'),
					Config::get('cookie.httponly')
			);
		}
		else
		{
			session_set_cookie_params(
					Config::get('session_expiration'),
					//Config::get('cookie.path'),
					'/', Config::get('cookie.domain'),
					Config::get('cookie.secure')
			);
		}

		// Register non-native driver as the session handler
		if (self::$driver_name != 'native')
		{
			session_set_save_handler(
					array(self::$driver, 'open'),
					array(self::$driver, 'close'),
					array(self::$driver, 'read'),
					array(self::$driver, 'write'),
					array(self::$driver, 'destroy'),
					array(self::$driver, 'gc')
			);
		}

		// Start the session!
		session_start();

		// Put session_id in the session variable
		$_SESSION['session_id'] = session_id();

		// Set defaults
		if (!isset($_SESSION['_kf_flash_']))
		{
			$_SESSION['total_hits'] = 0;
			$_SESSION['_kf_flash_'] = array();

			if (in_array('user_agent', self::$validate))
			{
				$_SESSION['user_agent'] = Kohana::$user_agent;
			}

			if (in_array('ip_address', self::$validate))
			{
				$_SESSION['ip_address'] = $this->input->ip_address();
			}
		}

		// Set up flash variables
		self::$flash = & $_SESSION['_kf_flash_'];

		// Update constant session variables
		$_SESSION['last_activity'] = time();
		$_SESSION['total_hits'] += 1;

		// Validate data only on hits after one
		if ($_SESSION['total_hits'] > 1)
		{
			// Validate the session
			foreach (self::$validate as $valid)
			{
				switch ($valid)
				{
					case 'user_agent':
						if ($_SESSION[$valid] !== Kohana::$user_agent)
							return $this->create();
						break;
					case 'ip_address':
						if ($_SESSION[$valid] !== $this->input->$valid())
							return $this->create();
						break;
				}
			}

			// Remove old flash data
			if (!empty(self::$flash))
			{
				foreach (self::$flash as $key => $state)
				{
					if ($state == 'old')
					{
						self::del($key);
						unset(self::$flash[$key]);
					}
					else
					{
						self::$flash[$key] = 'old';
					}
				}
			}
		}

		// Set the new data
		self::set($vars);
	}

	/**
	 * Regenerates the global session id.
	 */
	public function regenerate()
	{
		if (self::$driver_name == 'native')
		{
			// Thank god for small gifts
			session_regenerate_id(TRUE);

			// Update session with new id
			$_SESSION['session_id'] = session_id();
		}
		else
		{
			// Pass the regenerating off to the driver in case it wants to do anything special
			$_SESSION['session_id'] = self::$driver->regenerate();
		}
	}

	/**
	 * Destroys the current session.
	 *
	 * @return  boolean
	 */
	public function destroy()
	{
		if (isset($_SESSION))
		{
			// Remove all session data
			session_unset();

			// Delete the session cookie
			cookie::delete(session_name());

			// Destroy the session
			return session_destroy();
		}
	}

	/**
	 * Runs the system.session_write event, then calls session_write_close.
	 *
	 * @return void
	 */
	public function write_close()
	{
		static $run;

		if ($run === NULL)
		{
			$run = TRUE;

			// Run the events that depend on the session being open
			Event::run('system.session_write');

			// Close the session
			session_write_close();
		}
	}

	/**
	 * Set a session variable.
	 *
	 * @param   string|array  key, or array of values
	 * @param   mixed         value (if keys is not an array)
	 * @return  void
	 */
	public function set($keys, $val = FALSE)
	{
		if (empty($keys))
			return FALSE;

		if (!is_array($keys))
		{
			$keys = array($keys => $val);
		}

		foreach ($keys as $key => $val)
		{
			if (isset(self::$protect[$key]))
				continue;

			// Set the key
			$_SESSION[$key] = $val;
		}
	}

	/**
	 * Set a flash variable.
	 *
	 * @param   string|array  key, or array of values
	 * @param   mixed         value (if keys is not an array)
	 * @return  void
	 */
	public function set_flash($keys, $val = FALSE)
	{
		if (empty($keys))
			return FALSE;

		if (!is_array($keys))
		{
			$keys = array($keys => $val);
		}

		foreach ($keys as $key => $val)
		{
			if ($key == FALSE)
				continue;

			self::$flash[$key] = 'new';
			self::set($key, $val);
		}
	}

	/**
	 * Freshen a flash variable.
	 *
	 * @param   string   variable key
	 * @return  boolean
	 */
	public function keep_flash($key)
	{
		if (isset(self::$flash[$key]))
		{
			self::$flash[$key] = 'new';
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Get a variable. Access to sub-arrays is supported with key.subkey.
	 *
	 * @param   string  variable key
	 * @param   mixed   default value returned if variable does not exist
	 * @return  mixed   Variable data if key specified, otherwise array containing all session data.
	 */
	public function get($key = FALSE, $default = FALSE)
	{
		if (empty($key))
			return $_SESSION;

		$result = (isset($_SESSION[$key])) ? $_SESSION[$key] : Kohana::key_string($key, $_SESSION);

		return ($result === NULL) ? $default : $result;
	}

	/**
	 * Get a variable, and delete it.
	 *
	 * @param   string  variable key
	 * @return  mixed
	 */
	public function get_once($key)
	{
		$return = self::get($key);
		self::del($key);

		return $return;
	}

	/**
	 * Delete one or more variables.
	 *
	 * @param   variable key(s)  $keys
	 * @return  void
	 */
	public function del($keys)
	{
		if (empty($keys))
			return FALSE;

		if (func_num_args() > 1)
		{
			$keys = func_get_args();
		}

		foreach ((array) $keys as $key)
		{
			if (isset(self::$protect[$key]))
				continue;

			// Unset the key
			unset($_SESSION[$key]);
		}
	}

}

// End Session Class