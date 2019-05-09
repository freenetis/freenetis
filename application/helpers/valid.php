<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Validation helper class.
 *
 * $Id: valid.php 1865 2008-01-29 19:48:29Z Geert $
 *
 * @package    Validation
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class valid {

	/**
	 * Validate email, commonly used characters only
	 *
	 * @param   string   email address
	 * @return  boolean
	 */
	public static function email($email)
	{
		return (bool) preg_match('/^(?!\.)[-+_a-z0-9.]++(?<!\.)@(?![-.])[-a-z0-9.]+(?<!\.)\.[a-z]{2,6}$/iD', $email);
	}

	/**
	 * Validate emails, commonly used characters only, separated by commas
	 *
	 * @param   string   email address
	 * @return  boolean
	 */
	public static function emails($emails)
	{
		foreach (explode(',', $emails) as $email)
		{
			if (!self::email($email))
			{
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Validate email, RFC compliant version
	 * Note: This function is LESS strict than valid_email. Choose carefully.
	 *
	 * @see  Originally by Cal Henderson, modified to fit Kohana syntax standards:
	 * @see  http://www.iamcal.com/publish/articles/php/parsing_email/
	 * @see  http://www.w3.org/Protocols/rfc822/
	 *
	 * @param   string   email address
	 * @return  boolean
	 */
	public static function email_rfc($email)
	{
		$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
		$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
		$atom  = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
		$pair  = '\\x5c[\\x00-\\x7f]';

		$domain_literal = "\\x5b($dtext|$pair)*\\x5d";
		$quoted_string  = "\\x22($qtext|$pair)*\\x22";
		$sub_domain     = "($atom|$domain_literal)";
		$word           = "($atom|$quoted_string)";
		$domain         = "$sub_domain(\\x2e$sub_domain)*";
		$local_part     = "$word(\\x2e$word)*";
		$addr_spec      = "$local_part\\x40$domain";

		return (bool) preg_match('/^'.$addr_spec.'$/D', $email);
	}

	/**
	 * Validate URL
	 *
	 * @param   string   URL
	 * @param   string   protocol
	 * @return  boolean
	 */
	public static function url($url, $scheme = 'http')
	{
		// Scheme is always lowercase
		$scheme = strtolower($scheme);

		// Disable error reporting
		$ER = error_reporting(0);

		// Use parse_url to validate the URL
		$url = parse_url($url);

		//echo "<pre>";
		//print_r ($url);

		// Restore error reporting
		error_reporting($ER);

		// If the boolean check returns TRUE, return FALSE, and vice versa
		return ! (empty($url['host']) OR empty($url['scheme']) OR $url['scheme'] !== $scheme);
	}

	/**
	 * Validate IP
	 *
	 * @param   string   IP address
	 * @return  boolean
	 */
	public static function ip($ip)
	{
		if ( ! preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/D', $ip))
			return FALSE;

		$octets = explode('.', $ip);

		for ($i = 1; $i < 5; $i++)
		{
			$octet = (int) $octets[($i-1)];
			if ($i === 1)
			{
				if ($octet > 223 OR $octet < 1)
					return FALSE;
			}
			elseif ($i === 4)
			{
				if ($octet < 1)
					return FALSE;
			}
			else
			{
				if ($octet > 254)
					return FALSE;
			}
		}

		return TRUE;
	}
	
	/**
	 * Checks ip address if matches subnet and mask.
	 * 
	 * @param long $ip
	 * @param integer $net
	 * @param integer $mask
	 * @return bool
	 */
	public static function ip_check_subnet($ip, $net, $mask)
	{
		$size	= (~$mask & 0xffffffff)+1;
		
		if (($ip & $mask) != $net)
		{
			return false; // IP address does not match the subnet/mask
		}
		else if ($size > 2 && ($ip == $net || $ip == ($net + $size - 1)))
		{
			return false; // Invalid IP address
		}
		
		return true;
	}

	/**
	 * Validates a credit card number using the Luhn (mod10) formula.
	 * @see http://en.wikipedia.org/wiki/Luhn_algorithm
	 *
	 * @param   integer  credit card number
	 * @param   string   card type
	 * @return  boolean
	 */
	public static function credit_card($number, $type = 'default')
	{
		// Remove all non-digit characters from the number
		if (($number = preg_replace('/\D+/', '', $number)) === '')
			return FALSE;

		$cards = Config::item('credit_cards');

		// Check card type
		$type = strtolower($type);

		if ( ! isset($cards[$type]))
			return FALSE;

		// Check card number length
		$length = strlen($number);

		// Validate the card length by the card type
		if ( ! in_array($length, preg_split('/\D+/', $cards[$type]['length'])))
			return FALSE;

		// Check card number prefix
		if ( ! preg_match('/^'.$cards[$type]['prefix'].'/', $number))
			return FALSE;

		// No Luhn check required
		if ($cards[$type]['luhn'] == FALSE)
			return TRUE;

		// Checksum of the card number
		$checksum = 0;

		for ($i = $length - 1; $i >= 0; $i -= 2)
		{
			// Add up every 2nd digit, starting from the right
			$checksum += substr($number, $i, 1);
		}

		for ($i = $length - 2; $i >= 0; $i -= 2)
		{
			// Add up every 2nd digit doubled, starting from the right
			$double = substr($number, $i, 1) * 2;

			// Subtract 9 from the double where value is greater than 10
			$checksum += ($double >= 10) ? $double - 9 : $double;
		}

		// If the checksum is a multiple of 10, the number is valid
		return ($checksum % 10 === 0);
	}

	/**
	 * Checks if a czech phone number is valid.
	 *
	 * @param   string   phone number to check
	 * @return  boolean
	 */
	public static function phone($number)
	{
		// Remove all non-digit characters from the number
		$number = preg_replace('/\D+/', '', $number);

		if (strlen($number) == 9 OR strlen($number) == 12)
		    return true;

		return false;
	}


	/**
	 * Checks whether a string consists of alphabetical characters only.
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha($str, $utf8 = FALSE)
	{
		return (bool) ($utf8 == TRUE)
			? preg_match('/^\pL++$/uD', (string) $str)
			: ctype_alpha((string) $str);
	}

	/**
	 * Checks whether a string consists of alphabetical characters and numbers only.
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_numeric($str, $utf8 = FALSE)
	{
		return (bool) ($utf8 == TRUE)
			? preg_match('/^[\pL\pN]++$/uD', (string) $str)
			: ctype_alnum((string) $str);
	}

	/**
	 * Checks whether a string consists of alphabetical characters, numbers, underscores and dashes only.
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_dash($str, $utf8 = FALSE)
	{
		return (bool) ($utf8 == TRUE)
			? preg_match('/^[-\pL\pN_]++$/uD', (string) $str)
			: preg_match('/^[-a-z0-9_]++$/iD', (string) $str);
	}

	/**
	 * Checks whether a string consists of digits only (no dots or dashes).
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function digit($str, $utf8 = FALSE)
	{
		return (bool) ($utf8 == TRUE)
			? preg_match('/^\pN++$/uD', (string) $str)
			: ctype_digit((string) $str);
	}

	/**
	 * Checks whether a string is a valid number (negative and decimal numbers allowed).
	 *
	 * @param   string   input string
	 * @return  boolean
	 */
	public static function numeric($str)
	{
		return (is_numeric($str) AND preg_match('/^[-0-9.]++$/D', $str));
	}

	/**
	 * Checks whether a string is valid ulogd active count
	 *
	 * @author	Michal Kliment
	 * @param	string	input string
	 * @return	boolean
	 */
	public static function ulogd_active_count($str)
	{
		return preg_match('/^[0-9]+(\.[0-9]+)?%?$/', $str);
	}

	/**
	 * Checks whether a string is a valid text.
	 *
	 * @param   string   $str
	 * @return  boolean
	 */
	public static function standard_text($str)
	{
		return preg_match('/^[-\pL\pN\pZ_]++$/uD', (string) $str);
	}

	/**
	 * Checks whether a array is valid date
	 *
	 * @param array $date
	 * @return boolean
	 */
	public static function date($date = array())
	{
		if (!is_array($date) ||
			!array_key_exists('year', $date) ||
			!array_key_exists('month', $date) ||
			!array_key_exists('day', $date))
		{
			return FALSE;
		}
	    return (count(date::days($date['month'],$date['year'])) >= $date['day']);
	}

	/**
	 * Checks whether a string is valid date
	 *
	 * @author Michal Kliment
	 * @param string $str
	 * @return boolean
	 */
	public static function date_string($str)
	{
		if (!preg_match ("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $str, $matches))
			return false;
		else
			return valid::date(array('year' => $matches[1], 'month' => $matches[2], 'day' => $matches[3]));
	}

	/**
	 * Checks whether a string is valid MAC address
	 *
	 * @author Michal Kliment
	 * @param string $str
	 * @return boolean
	 */
	public static function mac_address($str)
	{
		return preg_match ("/^([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$/", $str);
	}

	/**
	 * Checks whether a string is valid IP address
	 *
	 * @author Michal Kliment
	 * @param string $str
	 * @return boolean
	 */
	public static function ip_address($str)
	{
		$str = str_replace("\n", ",", $str);
		
		return preg_match ("/^(((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9]),?)+$/", $str);
	}

	/**
	 * Checks whether a string is valid URL suffix
	 *
	 * @author Michal Kliment
	 * @param string $str
	 * @return boolean
	 */
	public static function suffix ($str)
	{
		return preg_match ("/^\/([^\/]+\/)*$/", $str);
	}

	/**
	 * Checks whether a string is valid network address range (in format eg. 10.0.0.0/8)
	 *
	 * @author MIchal Kliment
	 * @param string $str
	 * @return boolean
	 */
	public static function address_ranges ($str)
	{
		$str = str_replace("\n", ",", $str);
		
		if (!preg_match("/^((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])(\/([0-9]|([1-2][0-9])|(3[0-2])))?(,((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])(\/([0-9]|([1-2][0-9])|(3[0-2])))?)*$/",$str,$matches))
			return false;
		else
		{
			$ranges = explode (',', $str);

			foreach ($ranges as $address)
			{
				if (strpos($address, '/') !== FALSE)
				{
					list ($address, $mask) = explode ('/',$address);
		
					if ((ip2long($address) & ip2long(network::cidr2netmask ($mask))) != ip2long($address))
						return false;
				}
			}
			
			return true;
		}
	}

	/**
	 * Checks whether string is valid GPS string
	 *
	 * @author Michal Kliment
	 * @param string $str
	 * @return boolean
	 */
	public static function gps_string ($str)
	{
		if ($str == '')
			return true;

		$c = explode(" ",trim($str));

		if (count($c) != 2)
			return false;

		if (substr(trim($c[0]),-2,1) != 'N' || substr(trim($c[1]),-1) != 'E')
			return false;

		$north_gps = substr(trim($c[0]),0,-2);
		$east_gps = substr(trim($c[1]), 0, -1);

		return (
				gps::is_valid_degrees_coordinate($north_gps) &&
				gps::is_valid_degrees_coordinate($east_gps)
		);
	}

    /**
     * Checks whether string is valid byte-string
     *
     * @author Michal Kliment
     * @param string $str
     * @return boolean
     */
    public static function byte_size ($str)
    {
		return preg_match ("/^[0-9]+(\.[0-9]+)?[k|M|G|T]?B$/", $str);
    }
	
	/**
	 * Checks whether string is valid speed size (eg. in format 2M/8M)
	 * 
	 * @author Michal Kliment
	 * @param type $str
	 * @return type 
	 */
	public static function speed_size ($str)
	{
		return preg_match ("/^([0-9]+[k|M|G|T]?)(\/[0-9]+[k|M|G|T]?)?$/i", $str);
	}

	/**
	 * Checks whether number is included in range
	 * 
	 * @author Jan Dubina
	 * @param type $number
	 * @return boolean 
	 */
	public static function range ($number, $min = 0, $max = 100)
	{
		return ($number >= $min) && ($number <= $max);
	}

	/**
	 * Checks whether string is valid bank account number
	 * 
	 * @author Jan Dubina
	 * @param string $str
	 * @return boolean 
	 */
	public static function bank_account ($str)
	{
		return preg_match ("/^([0-9]{1,6}-)?([0-9]{2,10})\/[0-9]{4}$/", $str);
	}
	
} // End valid
