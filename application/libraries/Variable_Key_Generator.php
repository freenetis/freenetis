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
 * Abstract class for variable key drivers.
 * 
 * @author Ondrej Fibich
 * @see Bank_Statement_File_Importer
 */
abstract class Variable_Key_Generator
{
	/**
	 * Dir with classes
	 */
	const DIR = 'variable_key_generators';
	
	/**
	 * Array of availables drivers for factory method.
	 * Keys:
	 * 
	 *	id					ID used in database
	 *  name				Name
	 *  class				Class name in Varaible keys folder
	 *
	 * @var array
	 */
	private static $DRIVERS = array
	(
		'pvfree' => array
		(
			'id'		=> 'pvfree',
			'name'		=> 'Pvfree variable key generator',
			'class'		=> 'Pvfree_Variable_Key_Generator',
		),
		'checksum' => array
		(
			'id'		=> 'checksum',
			'name'		=> 'Checksum variable key generator',
			'class'		=> 'Checksum_Variable_Key_Generator',
		),
	);
	
	/**
	 * Factory for Variable key drivers
	 *
	 * @param mixed $driver	String index of driver or integer ID of driver or NUL
	 *						if current should be selected.
	 * @return Variable_Key_Generator	Generator instance or NULL
	 *									if driver name or ID is incorect.
	 */
	public static function factory($driver = NULL)
	{
		if ($driver === NULL)
		{
			$selected_driver = self::get_active_driver();
		}
		else
		{
			$selected_driver = self::_get_driver_index($driver);
		}
		
		if ($selected_driver)
		{
			$driver = self::$DRIVERS[$selected_driver];
			$class_name = $driver['class'];
			$class_path = dirname(__FILE__) . '/' . self::DIR
					. '/' . $class_name . '.php';
			
			require_once $class_path;
			return new $class_name;
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
	 * @return string
	 */
	public static function get_driver_name($driver)
	{
		$selected_driver = self::_get_driver_index($driver);
		
		if ($selected_driver)
		{
			$d = self::$DRIVERS[$selected_driver];
			
			return __($d['name']);
		}
		
		return NULL;
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
	 * Gets drivers array for dropdown
	 * 
	 * @return array
	 */
	public static function get_drivers_for_dropdown()
	{
		$options = array();
		
		foreach (self::$DRIVERS as $d)
		{
			$options[$d['id']] = __($d['name']);
		}
		
		return $options;
	}
	
	/**
	 * Gets list of active drivers for selectboxes.
	 * Key is id of driver and value is name.
	 *
	 * @return string ID of driver
	 */
	public static function get_active_driver()
	{
		return Settings::get('variable_key_generator_id');
	}

	/**
	 * Generated variable key from given identificator.
	 *
	 * @param mixed $identificator Indentificator for generate from
	 * @return integer	Variable key
	 */
	abstract public function generate($identificator);
	
	/**
	 * Is generator capable of error checking.
	 * 
	 * @return boolean
	 */
	abstract public function errorCheckAvailable();
	
	/**
	 * If errorCheckAvailable() is TRUE that this method may be used for error
	 * detection. It is only capable of detection, no correction is available here.
	 * 
	 * Override this method in a subclass in order to implement error detection
	 * for your generator.
	 * 
	 * @param string $var_key Variable key
	 * @return boolean TRUE is the given variable key is without errors or FALSE otherwise
	 */
	public function errorCheck($var_key)
	{
		return FALSE;
	}
	
	/**
	 * Is generator capable of error correction.
	 * 
	 * @return boolean
	 */
	abstract public function errorCorrectionAvailable();
	
	/**
	 * If errorCorrectionAvaiable() is TRUE than this method may be used for error
	 * correction of the variable symbol.
	 * 
	 * Override this method in a subclass in order to implement error correction
	 * for your generator.
	 * 
	 * @param string $var_key Variable key
	 * @return array Contains two items
	 *					boolean status	TRUE if correction was successful
	 *									FALSE if correction was unsuccessful
	 *					string corrected_variable_key	Corrected variable symbol
	 */
	public function errorCorrection($var_key)
	{
		return array
		(
			'status'					=> FALSE,
			'corrected_variable_key'	=> NULL
		);
	}
}
