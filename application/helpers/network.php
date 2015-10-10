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
 * Network helper.
 *
 * @author Michal Kliment
 * @package Helper
 */
class network
{
	private static $sizes = array
	(
		'K' => 1024,
		'M' => 1048576,
		'G' => 1073741824,
		'T' => 1099511627776
	);

	/**
	 * Converts netmask in classic format (eg. 255.255.255.0) to CIDR format (/24)
	 * @author Michal Kliment
	 * @param string
	 * @return string
	 */
	public static function netmask2cidr($netmask)
	{
		if (!preg_match("/^\d+\.\d+\.\d+\.\d+$/", $netmask))
		{
			return false;
		}
		return 32 - log((~ip2long($netmask) & 0xffffffff) + 1, 2);
	}

	/**
	 * Converts netmask in CIDR format (eg. 24) to classic format (255.255.255.0)
	 *
	 * @author Michal Kliment
	 * @param string $cidr
	 * @return string
	 */
	public static function cidr2netmask($cidr)
	{
		if (!preg_match("/^\d+$/", $cidr))
		{
			return false;
		}
		return long2ip(~(pow(2, 32 - $cidr) - 1) & 0xffffffff);
	}

	/**
	 * Formats size
	 *
	 * @author Michal Kliment
	 * @param integer $size
	 * @return string
	 */
	public static function size($size, $byte = TRUE)
	{
		// default unit is kilo
		$unit = 'k';

		// size is too big
		if ($size >= 1024)
		{
			// transforms to Mega
			$unit = 'M';
			$size = round($size / 1024, 2);

			// size is still too big
			if ($size >= 1024)
			{
				// transforms to Giga
				$unit = 'G';
				$size = round($size / 1024, 2);
				
				// size is still too big
				if ($size >= 1024)
				{
					// transforms to Giga
					$unit = 'T';
					$size = round($size / 1024, 2);
				}
			}
		}
		
		$unit .= ($byte) ? 'B' : 'b';

		return ($size) ? $size . ' ' . $unit : '0 ' . $unit;
	}

	/**
	 * Formats speed
	 *
	 * @author Michal Kliment
	 * @param integer $speed In B/s
	 * @return string
	 */
	public static function speed($speed)
	{
		// default unit is nothing
		$unit = '';
		
		if ($speed >= 1024)
		{
			$unit = 'k';
			$speed = round($speed / 1024, 2);

			// size is too big
			if ($speed >= 1024)
			{
				// transforms to Mega
				$unit = 'M';
				$speed = round($speed / 1024, 2);

				// size is still too big
				if ($speed >= 1024)
				{
					// transforms to Giga
					$unit = 'G';
					$speed = round($speed / 1024, 2);

					// size is still too big
					if ($speed >= 1024)
					{
						// transforms to Giga
						$unit = 'T';
						$speed = round($speed / 1024, 2);
					}
				}
			}
		}

		return ($speed) ? $speed . $unit : '0' . $unit;
	}

	/**
	 * Checks whether ip address belongs to default address ranges
	 *
	 * @author Michal Kliment
	 * @param string  $ip_address
	 * @return boolean
	 */
	public static function ip_address_in_ranges($ip_address)
	{
		// default address range is not set, return true
		if (($ranges = Settings::get('address_ranges')) == '')
			return true;

		// transform ip to long
		$ip_address = ip2long($ip_address);

		// transform string to array
		$ranges = explode(',', $ranges);

		foreach ($ranges as $range_address)
		{
			// address contains / => it's in CIDR format
			if (strpos($range_address, '/') !== FALSE)
			// split address and mask
				list ($range_address, $range_mask) = explode('/', $range_address);
			// address is without / => it's single address
			else
				$range_mask = 32;

			$net = ip2long($range_address);
			$mask = ip2long(network::cidr2netmask($range_mask));

			// success
			if (($ip_address & $mask) == $net)
				return true;
		}
		return false;
	}
	
	/**
	 * Converts speed string to integer (bytes)
	 * 
	 * @author Michal Kliment
	 * @param string $str
	 * @return integer 
	 */
	public static function str2bytes ($str)
	{		
		$unit = strtoupper(substr($str,-1));
		
		$size = isset(self::$sizes[$unit]) ? self::$sizes[$unit] : 1;
		
		return ((int) $str) * $size;
	}
	
	/**
	 * Converts speed string to array
	 * 
	 * @param string $str
	 * @return array 
	 */
	public static function str2array ($str)
	{		
		$unit = strtoupper(substr($str,-1));
		
		if (!isset(self::$sizes[$unit]))
			$unit = "";
		
		return array
		(
			'size' => (int) $str,
			'unit' => $unit
		);
	}
	
	/**
	 * Converts integer (bytes) to speed string
	 * 
	 * @author Michal Kliment
	 * @param type $bytes
	 * @param type $unit
	 * @return type 
	 */
	public static function bytes2str ($bytes, $unit = "")
	{
		$size = isset(self::$sizes[$unit]) ? self::$sizes[$unit] : 1;
		
		return (ceil($bytes/$size*10)/10).$unit;
	}
	
	/**
	 * Tranfers bytes from unit do unit
	 * 
	 * @author Michal Kliment
	 * @param type $size
	 * @param type $from_unit
	 * @param type $to_unit
	 * @return type 
	 */
	public static function transfer_unit ($size, $from_unit, $to_unit)
	{
		$from_size = isset(self::$sizes[$from_unit]) ? self::$sizes[$from_unit] : 1;
		$to_size = isset(self::$sizes[$to_unit]) ? self::$sizes[$to_unit] : 1;
		
		return ceil($size*($from_size/$to_size*10))/10;
	}
	
	/**
	 * Parses speed string to array
	 * 
	 * @author Michal Kliment
	 * @param type $str
	 * @param type $unit
	 * @return type 
	 */
	public static function speed_size ($str, $unit = 'M')
	{
		if (!valid::speed_size($str))
			return FALSE;
		
		$pieces = explode("/", $str);
		
		$upload_str = $pieces[0];
		
		$download_str = $pieces[count($pieces)-1];
		
		$upload = network::str2array($upload_str);
		$download = network::str2array($download_str);
		
		return array
		(
			'upload' => network::transfer_unit($upload['size'], $upload['unit'], $unit),
			'download' => network::transfer_unit($download['size'], $download['unit'], $unit),
			'unit' => $unit
		);
	}

}
