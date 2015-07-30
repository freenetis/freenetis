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
 * Money helper.
 *
 * @author Michal Kliment
 * @package Helper
 */
class Money
{
	/**
	 * Formats given amount of money.
	 *
	 * @author Ondrej Fibich
	 * @param double $amount
	 * @return string Formated money string
	 */
	public static function format($amount)
	{
		return str_replace(' ', '&nbsp;', number_format(doubleval($amount), 2, ',', ' '));
	}
	
	/**
	 * Finds all payments by given payment rate
	 * 
	 * @author Michal Kliment
	 * @param array $payments
	 * @param integer $month
	 * @param integer $year
	 * @param integer $payment_left
	 * @param integer $payment_rate 
	 */
	public static function find_debt_payments(
			&$payments, $month, $year, $payment_left, $payment_rate)
	{
		while ($payment_left > 0)
		{
			if ($payment_left > $payment_rate)
				$payment = $payment_rate;
			else
				$payment = $payment_left;

			if (isset($payments[$year][$month]))
				$payments[$year][$month] += $payment;
			else
				$payments[$year][$month] = $payment;

			$month++;
			if ($month > 12)
			{
				$year++;
				$month = 1;
			}
			$payment_left -= $payment;
		}
	}
}