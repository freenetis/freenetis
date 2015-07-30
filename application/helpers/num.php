<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Number helper class.
 *
 * $Id: num.php 1710 2008-01-14 01:12:01Z PugFish $
 *
 * @package    Number Helper
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class num {

	/**
	 * Round a number to the nearest nth
	 *
	 * @param   integer  number to round
	 * @param   integer  number to round to
	 * @return  integer
	 */
	public static function round($number, $nearest = 5)
	{
		return round($number / $nearest) * $nearest;
	}

	/**
	 * @author Michal Kliment
	 * Fill number with nulls (on start)
	 * @param int $number
	 * @param int $position
	 * @return string
	 */
	public static function null_fill($number, $position)
	{
		$len = strlen($number);
		$null_str = '';
		for ($i=0;$i<($position-$len);$i++)
		    $null_str .= '0';

		return $null_str.$number;
	}

	/**
	 * Negates number to opposite number / Negates number to given number
	 * Negates all number / Negates only numbers for given level and operand
	 *
	 * @author Michal Kliment
	 * @param integer $number
	 * @param integer $to_number
	 * @param integer $level
	 * @param integer $op
	 * @return integer
	 */
	public static function negation($number, $to_number = NULL, $level = NULL, $op = 1)
	{
		// number in which it will negate
		$to_number = ($to_number) ? $to_number : $number*(-1);

		// level is given
		if ($level !== NULL)
		{
			switch ($op)
			{
				// >
				case 1:
					return ($number > $level) ? $to_number : $number;
					break;
				// <
				case -1:
					return ($number < $level) ? $to_number : $number;
					break;
				// =
				case 0:
					return ($number == $level) ? $to_number : $number;
					break;
				// for all other
				default:
					return $to_number;
					break;
			}
		}
		else
			// level is not given
			return $to_number;
	}

	/**
	 * @author Michal Kliment
	 * Replace decimal comma with decimal point
	 * @param int $number
	 * @return string
	 */
	public static function decimal_point($number)
	{
		return str_replace(',', '.', $number);
	}


}