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
 * Helper for working with addresses
 * 
 * @author Jan Dubina
 */

class address {
		/**
		* Splits street to street name and street number
		* 
		* @author Jan Dubina
		* @param   string street
		* @return  array
		*/
		public static function street_split($street_arg) 
		{
			$street_arr = explode (' ', $street_arg);
			$street = array();
			$street_number = '';
			foreach ($street_arr as $str)
			{
				if (preg_match("/^(\d+).*/", $str) === 0)
					$street[] = $str;
				else
				{
					$street_number = $str;
					break;
				}
										
			}
			$street = implode(' ', $street);
			
			return array(
							'street' => $street, 
							'street_number' => $street_number
			);
		}	
		
		/**
		* Joins street name and street number
		* 
		* @author Jan Dubina
		* @param   string street
		* @param   string street number
		* @return  string
		*/
		public static function street_join($street, $street_number) 
		{
			if (!empty($street) && !empty($street_number)) 
				return $street . ' ' . $street_number;
			elseif (!empty($street))
				return $street;
			elseif (!empty($street_number))
				return $street_number;
			else
				return '';
		}
}
?>