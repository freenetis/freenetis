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
 * Billing for VoIP
 * 
 * @author Roman Sevcik, Ondrej Fibich
 */
class Billing
{
	/**
	 * Singleton instance
	 *
	 * @var Billing
	 */
	private static $instance = null;
	
	/**
	 * Returns instance of billing
	 *
	 * @return Billing
	 */
	public static function & instance()
	{
		if (empty(self::$instance))
		{
			self::$instance = new Billing();
		}
		
		return self::$instance;
	}
	
	// states of VoIP
	const INACTIVE = 1;
	const NFX_LBILLING = 2;
	
	/**
	 * Indicator of driver
	 *
	 * @var bool
	 */
	private $driver;
	
	/**
	 * LBilling class
	 *
	 * @var lbilling
	 */
	private $billing;

	/**
	 * Construct of cilling
	 */
	private function __construct()
	{
		if (Settings::get('voip_billing_driver') == Billing::NFX_LBILLING)
		{
			require_once(APPPATH.'vendors/billing/lbilling/lbilling.php');
			
			$this->billing = new lbilling(
					Settings::get('voip_billing_partner'),
					Settings::get('voip_billing_password')
			);

			$this->driver = true;
		}
		else
		{
			$this->driver = false;
		}
	}

	/**
	 * Check if driver is on
	 *
	 * @return bool
	 */
	public function has_driver()
	{
		return $this->driver;
	}

	/**
	 * Tests connection to billing
	 *
	 * @return bool
	 */
	public function test_conn()
	{
		if (!$this->driver)
		{
			return false;
		}

		return $this->billing->test_conn();
	}

	/**
	 * Gets account
	 *
	 * @param integer $accountid
	 * @return mixed				false on error, data otherwise
	 */
	public function get_account($accountid)
	{
		if (!$this->driver)
		{
			return false;
		}

		return $this->billing->get_account($accountid);
	}

	/**
	 * Gets calls from acount in given date interval
	 *
	 * @param integer $accountid
	 * @param string $from
	 * @param string $to
	 * @return mixed
	 */
	public function get_account_calls($accountid, $from, $to)
	{
		return $this->billing->get_account_calls($accountid, $from, $to);
	}

	/**
	 * Gets subscribers calls in given date interval
	 *
	 * @param integer $accountid
	 * @param string $from
	 * @param string $to
	 * @return mixed
	 */
	public function get_subscriber_calls($accountid, $from, $to)
	{
		return $this->billing->get_subscriber_calls($accountid, $from, $to);
	}

	/**
	 * Gets partner calls in given date interval
	 *
	 * @param integer $accountid
	 * @param string $from
	 * @param string $to
	 * @return mixed
	 */
	public function get_partner_calls($from, $to)
	{
		return $this->billing->get_partner_calls($from, $to);
	}
	
	/**
	 * Gets price of simulated one minute long call
	 *
	 * @param type $caller_number	Number to call from
	 * @param type $number			Number to call to
	 * @return mixed				Array with price and details on success
	 *								FALSE otherwise
	 */
	public function get_price_of_minute_call($caller_number, $number)
	{
		$call = $this->billing->simulate_call($number, $caller_number, 60);
		
		if ($call && isset($call->descr) && $call->descr == 'OK')
		{
			$number = explode('@', $call->calls[0]->callee);
			$number = explode(':', $number[0]);
			$number = $number[1];
			
			return array
			(
				'number'	=> $number,
				'price'		=> doubleval($call->calls[0]->rate_sum),
				'area'		=> $call->calls[0]->area
			);
		}
		
		return FALSE;
	}

	/**
	 * Gets error from billing
	 *
	 * @return array
	 */
	public function get_error()
	{
		return $this->billing->get_error();
	}
}
