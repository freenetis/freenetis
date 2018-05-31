<?php

/*
 *  This file is part of open source system FreenetIS
 *  and it is release under GPLv3 licence.
 *
 *  More info about licence can be found:
 *  http://www.gnu.org/licenses/gpl-3.0.html
 *
 *  More info about project can be found:
 *  http://www.freenetis.org/
 */

namespace freenetis\service\member;

use date;
use Transfer_Model;
use Fee_Model;
use Device_Model;

/**
 * Service that allows to calculate member payment expiration date.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class ExpirationCalcService extends \AbstractService
{

	/**
	 * @var Transfer_Model
	 */
	protected $transfer_model;

	/**
	 * @var Fee_Model
	 */
	protected $fee_model;

	/**
	 * @var Device_Model
	 */
	protected $device_model;

	/**
	 * Creates service.
	 *
	 * @param \ServiceFactory $factory
	 */
	public function __construct(\ServiceFactory $factory)
	{
		parent::__construct($factory);
		$this->transfer_model = new Transfer_Model;
		$this->fee_model = new Fee_Model;
		$this->device_model = new Device_Model;
	}

	/**
	 * Gets expiration date of member's payments.
	 *
	 * @author Michal Kliment, Ondrej Fibich
	 * @param object $account
	 * @param int $shortened_on_year year to shortened expiration date from
	 * 							     (10 years from now by default)
	 * @return ExpirationCalcResult
	 */
	public function get_expiration_info($account, $shortened_on_year = NULL)
	{
		if (!is_numeric($shortened_on_year))
		{
			$shortened_on_year = date('Y') + 10; // 10 year shortened by default
		}

		// member's actual balance
		$balance = $account->balance;

		$last_deduct_date = date_parse(
				date::get_closses_deduct_date_to(
						$this->transfer_model->get_last_transfer_datetime_of_account($account->id)
				)
		);

		// date
		$day = $last_deduct_date['day'];
		$month = $last_deduct_date['month'];
		$year = $last_deduct_date['year'];

		// set algoritm firection by current balance
		if ($balance > 0)
		{
			$sign = 1; // balance is in positive, we will go to the future
		}
		else
		{
			$sign = -1; // balance is in negative, we will go to the past
		}

		$payments = array();

		// finds entrance date of member
		$entrance_date_str = date::get_closses_deduct_date_to($account->member->entrance_date);
		$entrance_date = date_parse($entrance_date_str);

		// finds debt payment rate of entrance fee
		$debt_payment_rate = ($account->member->debt_payment_rate > 0) ? $account->member->debt_payment_rate : $account->member->entrance_fee;

		// finds all debt payments of entrance fee
		self::find_debt_payments(
				$payments, $entrance_date['month'], $entrance_date['year'], $account->member->entrance_fee, $debt_payment_rate
		);

		// finds all member's devices with debt payments
		$devices = $this->device_model->get_member_devices_with_debt_payments($account->member_id);

		foreach ($devices as $device)
		{
			// finds buy date of this device
			$buy_date = date_parse(date::get_closses_deduct_date_to($device->buy_date));

			// finds all debt payments of this device
			self::find_debt_payments(
					$payments, $buy_date['month'], $buy_date['year'], $device->price, $device->payment_rate
			);
		}

		// protection from unending loop
		$shortened = FALSE;

		// finds min and max date = due to prevent before unending loop
		$min_fee_date = $this->fee_model->get_min_fromdate_fee_by_type('regular member fee');
		$max_fee_date = $this->fee_model->get_max_todate_fee_by_type('regular member fee');

		while (true)
		{
			$date = date::create(date::get_deduct_day_to($month, $year), $month, $year);

			// date is bigger/smaller than max/min fee date, ends it (prevent before unending loop)
			if (($sign == 1 && $date > $max_fee_date) || ($sign == -1 && $date < $min_fee_date))
			{
				break;
			}

			// finds regular member fee for this month
			$fee = $this->fee_model->get_regular_member_fee_by_member_date($account->member_id, $date);

			// if exist payment for this month, adds it to the fee
			if (isset($payments[$year][$month]))
				$fee += $payments[$year][$month];

			// attributed / deduct fee to / from balance
			$balance -= $sign * $fee;

			// break if we crossed dept border from any direction
			if ($balance * $sign < 0)
			{
				break;
			}

			$month += $sign;

			if ($month == 0 OR $month == 13)
			{
				$month = ($month == 13) ? 1 : 12;
				$year += $sign;
			}

			// if we are X years in future, there is no point of counting more
			if ($shortened_on_year < $year)
			{
				$shortened = TRUE;
				break;
			}
		}

		$month--;
		if ($month == 0)
		{
			$month = 12;
			$year--;
		}

		$date = date::create(date::days_of_month($month), $month, $year);

		// never exceed entrace day with expiration data
		if (strtotime($date) < strtotime($entrance_date_str))
		{
			$date = $entrance_date_str;
		}

		return new ExpirationCalcResult($date, $shortened);
	}

	/**
	 * It stores debt payments into double-dimensional array (indexes year, month)
	 *
	 * @author Michal Kliment
	 * @param array $payments
	 * @param int $month
	 * @param int $year
	 * @param float $payment_left
	 * @param float $payment_rate
	 */
	protected static function find_debt_payments(
	&$payments, $month, $year, $payment_left, $payment_rate)
	{
		while ($payment_left > 0)
		{
			if ($payment_left > $payment_rate)
			{
				$payment = $payment_rate;
			}
			else
			{
				$payment = $payment_left;
			}

			if (isset($payments[$year][$month]))
			{
				$payments[$year][$month] += $payment;
			}
			else
			{
				$payments[$year][$month] = $payment;
			}

			$month++;
			if ($month > 12)
			{
				$month = 1;
				$year++;
			}
			$payment_left -= $payment;
		}
	}

}

/**
 * Holds expiration calculation result information.
 */
class ExpirationCalcResult
{

	/**
	 * Calculated expiration in format YYYY-MM-DD.
	 *
	 * @var string
	 */
	public $expiration_date;

	/**
	 * Flag whether the expiration was too long and was shortened.
	 *
	 * @var boolean
	 */
	public $shortened;

	public function __construct($expiration_date, $shortened)
	{
		$this->expiration_date = $expiration_date;
		$this->shortened = $shortened;
	}

}
