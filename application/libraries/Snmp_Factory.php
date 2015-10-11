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

define("SNMP_CLASS_PATH", APPPATH . 'libraries/snmp/');

require_once SNMP_CLASS_PATH . 'Abstract_Snmp.php';

/**
 * factory for creating of SNMP handlers.
 * 
 * @author Ondrej Fibich
 */
class Snmp_Factory
{
	/**
	 * Mikrotik driver name
	 */
	const MIKROTIK = 'mikrotik';
	
	/**
	 * List of available drivers.
	 * Key contains name of driver.
	 * Value contains information about driver such as version (version of SNMP)
	 * and class (class of driver). (more oprtions will be added in future)
	 *
	 * @var array
	 */
	public static $DRIVERS = array
	(
		'ubnt'		=> array
		(
			'version'	=> 1,
			'class'		=> 'UBNT_Snmp'
		),
		'mikrotik'	=> array
		(
			'version'	=> 2,
			'class'		=> 'Mikrotik_Snmp'
		),
		'linux'		=> array
		(
			'version'	=> 2,
			'class'		=> 'Linux_Snmp'
		),
		'edgecore'	=> array
		(
			'version'	=> 2,
			'class'		=> 'Edgecore_Snmp'
		),
		'signamax'	=> array
		(
			'version'	=> 2,
			'class'		=> 'Signamax_Snmp'
		),
		'signamaxold'	=> array
		(
			'version'	=> 2,
			'class'		=> 'SignamaxOld_Snmp'
		),
		'hp'			=> array
		(
			'version'	=> 2,
			'class'		=> 'HP_Snmp'
		)
	);
	
	/**
	 * Creates SNMP driver with the given name.
	 * 
	 * @param string $driver Driver name
	 * @return Abstract_SNMP Creates driver
	 * @throws InvalidArgumentException On invalid driver name (unknown)
	 */
	public static function factory($driver)
	{
		if (array_key_exists($driver, self::$DRIVERS))
		{
			require_once SNMP_CLASS_PATH . self::$DRIVERS[$driver]['class'] . '.php';
			return new self::$DRIVERS[$driver]['class'];
		}
		// driver not exists
		$m = 'Driver ' . $driver . ' does not exists.';
		throw new InvalidArgumentException($m);
	}
	
	/**
	 * Creates SNMP driver for the device from the given IP address.
	 * 
	 * @param string $device_ip Device IP address
	 * @return Abstract_Snmp 
	 * @throws InvalidArgumentException If no driver suits the given device
	 */
	public static function factoryForDevice($device_ip)
	{
		foreach (self::$DRIVERS as $key => $val)
		{
			$snmp = self::factory($key);
			
			if ($snmp->isCompactibleDriverWith($device_ip))
			{
				$snmp->setDeviceIp($device_ip);
				return $snmp;
			}
		}
		// not founded	
		$m = 'There is no driver for the device on ' . $device_ip;
		throw new InvalidArgumentException($m);
	}
	
}
