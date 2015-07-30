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
 * Helper for working with variable symbols of payments.
 * 
 * @author  Tomas Dulik, Ondrej Fibich
 * @package Helper
 */
class variable_symbol
{

	/**
	 * CRC-CCITT-16 algorithm for the polynomial 0x1021
	 *
	 * Tomas Dulik note: the algorithm is too nice to be true.
	 * The values it computes differ from the values returned by other calculators, e.g.
	 * http://zorc.breitbandkatze.de/crc.html
	 *
	 * This function can be used for generating the payments variable symbol from member id.
	 * crc16 can check error bursts up to 16bits long, so if the member mistypes such
	 * a generated variable symbol, almost any possible error should be detected.
	 * 
	 * @author Tomas Dulik, using http://us3.php.net/manual/en/function.crc32.php#86628
	 * @param $data		16bit integer for which crc16 should be computed
	 * @return			16bit int value containing crc16
	 */
	public static function crc16($data)
	{
		$crc = 0xFFFF;
		
		for ($i = 0; $i < 2; $i++)
		{
			$x = (($crc >> 8) ^ $data) & 0xFF;
			$data = $data >> 8;
			$x ^= $x >> 4;
			$crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
		}
		
		return $crc;
	}

	/**
	 * This function can be used for generating variable symbols from member id
	 * 
	 * @author Tomas Dulik
	 * @param integer $member_id
	 * @return string containing concatenation of member_id and 5 digits of its crc16
	 */
	public static function make_variable_symbol($member_id)
	{
		return $member_id . sprintf("%5d", self::crc16($member_id));
	}

}
