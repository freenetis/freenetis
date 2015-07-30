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
 * LBilling for VoIP.
 * Gets data from remote server.
 * 
 * @author Roman Sevcik, Ondrej Fibich
 */
class lbilling
{
	/**
	 * Path to lbilling scripts
	 * 
	 * @var string
	 */
	private $path;
	
	/**
	 * Username for connection
	 * 
	 * @var string
	 */
	private $partner;
	
	/**
	 * Password for connection
	 * 
	 * @var string
	 */
	private $pass;
	
	/**
	 * Error storage
	 * 
	 * @var array
	 */
	private $error;

	/**
	 * Contruct of lbilling
	 *
	 * @param string $partner	Login
	 * @param string $pass		Password
	 */
	public function __construct($partner, $pass)
	{
		$this->path = 'perl ' . APPPATH . 'vendors/billing/lbilling/';
		$this->partner = $partner;
		$this->pass = $pass;
		$this->error = array();
	}

	/**
	 * Tests connection
	 *
	 * @return bool
	 */
	public function test_conn()
	{
		exec(
				$this->path . "perl/lbilling-test_conn.pl $this->partner $this->pass",
				$output, $err
		);

		if ($err == 1)
		{
			return true;
		}
		else
		{
			$this->set_error($output);
			return false;
		}
	}

	/**
	 * Gets account
	 *
	 * @param type $accountid	Account ID
	 * @return mixed			Data on success NULL on error
	 */
	public function get_account($accountid)
	{
		exec(
				$this->path . "perl/lbilling-get_account.pl " .
				"$this->partner $this->pass $accountid",
				$output, $err
		);

		if ($err == 0)
		{
			$this->set_error($output);
			return null;
		}
		else if ($err == 1)
		{
			$line = 0;

			$acc = explode(";", $output[$line]);
			
			$account = new stdClass();
			$account->valid_to = $acc[0];
			$account->valid_from = $acc[1];
			$account->desc = $acc[2];
			$account->state = $acc[3];
			$account->billingid = $acc[4];
			$account->currency = $acc[5];
			$account->ballance = $acc[6];
			$account->limit = $acc[7];
			$account->type = $acc[8];
			$account->partner = $acc[9];

			$line++;
			$count = $output[$line] + $line + 1;
			$line++;

			$i = 0;
			for ($line; $line < $count; $line++)
			{
				$acc = explode(";", $output[$line]);
				
				$account->subscribers[$i] = new stdClass();
				$account->subscribers[$i]->valid_to = $acc[0];
				$account->subscribers[$i]->valid_from = $acc[1];
				$account->subscribers[$i]->descr = $acc[2];
				$account->subscribers[$i]->state = $acc[3];
				$account->subscribers[$i]->billingid = $acc[4];
				$account->subscribers[$i]->tarif = $acc[5];
				$account->subscribers[$i]->cid = $acc[6];
				$account->subscribers[$i]->limit = $acc[7];

				$i++;
			}
			return $account;
		}

		$e[0] = 'Unknow error. ';
		$this->set_error($e);
		return null;
	}

	/**
	 * Gets account calls
	 *
	 * @param type $accountid	Account ID
	 * @param string $from		From date
	 * @param string $to		To date
	 * @return mixed			Data on success NULL on error
	 */
	public function get_account_calls($accountid, $from, $to)
	{
		exec(
				$this->path . "perl/lbilling-get_account_calls.pl " .
				"$this->partner $this->pass $accountid $from $to",
				$output, $err
		);

		if ($err == 0)
		{
			$this->set_error($output);
			return null;
		}
		else if ($err == 1)
		{
			$line = 0;

			$acc = explode(";", $output[$line]);

			$account = new stdClass();
			$account->billingid = $acc[0];
			$account->from = $acc[1];
			$account->to = $acc[2];

			$line++;
			$count = $output[$line] + $line + 1;
			$line++;

			$i = 0;
			for ($line; $line < $count; $line++)
			{
				$acc = explode(";", $output[$line]);
				$account->calls[$i] = new stdClass();
				$account->calls[$i]->provider = $acc[0];
				$account->calls[$i]->rate_vat = $acc[1];
				$account->calls[$i]->subscriber = $acc[2];
				$account->calls[$i]->account = $acc[3];
				$account->calls[$i]->area = $acc[4];
				$account->calls[$i]->callee = $acc[5];
				$account->calls[$i]->status = $acc[6];
				$account->calls[$i]->emergency = $acc[7];
				$account->calls[$i]->rate_sum = $acc[8];
				$account->calls[$i]->currency = $acc[9];
				$account->calls[$i]->end_date = $acc[10];
				$account->calls[$i]->callcon = $acc[11];
				$account->calls[$i]->caller = $acc[12];
				$account->calls[$i]->type = $acc[13];
				$account->calls[$i]->start_date = $acc[14];
				$account->calls[$i]->result = $acc[15];

				$i++;
			}
			return $account;
		}

		$e[0] = 'Unknow error. ';
		$this->set_error($e);
		return null;
	}

	/**
	 * Gets subscriber calls
	 *
	 * @param type $accountid	Account ID
	 * @param string $from		From date
	 * @param string $to		To date
	 * @return mixed			Data on success NULL on error
	 */
	public function get_subscriber_calls($accountid, $from, $to)
	{
		exec(
				$this->path . 'perl/lbilling-get_subscriber_calls.pl ' .
				$this->partner . ' ' . $this->pass . ' ' . $accountid . ' ' .
				$from . ' ' . $to, $output, $err
		);

		if ($err == 0)
		{
			$this->set_error($output);
			return null;
		}
		else if ($err == 1)
		{
			$line = 0;

			$acc = explode(";", $output[$line]);

			$subscriber = new stdClass();
			$subscriber->billingid = $acc[0];
			$subscriber->from = $acc[1];
			$subscriber->to = $acc[2];

			$line++;
			$count = $output[$line] + $line + 1;
			$line++;

			$i = 0;
			for ($line; $line < $count; $line++)
			{
				$acc = explode(";", $output[$line]);
				
				$subscriber->calls[$i] = new stdClass();
				$subscriber->calls[$i]->provider = $acc[0];
				$subscriber->calls[$i]->rate_vat = $acc[1];
				$subscriber->calls[$i]->subscriber = $acc[2];
				$subscriber->calls[$i]->account = $acc[3];
				$subscriber->calls[$i]->area = $acc[4];
				$subscriber->calls[$i]->callee = $acc[5];
				$subscriber->calls[$i]->status = $acc[6];
				$subscriber->calls[$i]->emergency = $acc[7];
				$subscriber->calls[$i]->rate_sum = $acc[8];
				$subscriber->calls[$i]->currency = $acc[9];
				$subscriber->calls[$i]->end_date = $acc[10];
				$subscriber->calls[$i]->callcon = $acc[11];
				$subscriber->calls[$i]->caller = $acc[12];
				$subscriber->calls[$i]->type = $acc[13];
				$subscriber->calls[$i]->start_date = $acc[14];
				$subscriber->calls[$i]->result = $acc[15];

				$i++;
			}
			return $subscriber;
		}

		$e[0] = 'Unknow error. ';
		$this->set_error($e);
		return null;
	}

	/**
	 * Gets partner calls
	 *
	 * @param type $accountid	Account ID
	 * @param string $from		From date
	 * @param string $to		To date
	 * @return mixed			Data on success NULL on error
	 */
	public function get_partner_calls($from, $to)
	{
		exec(
				$this->path . 'perl/lbilling-get_partner_calls.pl ' .
				$this->partner . ' ' . $this->pass . ' ' . $from . ' ' . $to,
				$output, $err
		);

		if ($err == 0)
		{
			$this->set_error($output);
			return null;
		}
		else if ($err == 1)
		{
			$line = 0;

			$acc = explode(";", $output[$line]);

			$partner = new stdClass();
			$partner->from = $acc[0];
			$partner->to = $acc[1];

			$line++;
			$count = $output[$line] + $line + 1;
			$line++;

			$i = 0;
			for ($line; $line < $count; $line++)
			{
				$acc = explode(";", $output[$line]);

				$partner->calls[$i] = new stdClass();
				$partner->calls[$i]->provider = $acc[0];
				$partner->calls[$i]->cost_sum = $acc[1];
				$partner->calls[$i]->subscriber = $acc[2];
				$partner->calls[$i]->area = $acc[3];
				$partner->calls[$i]->callee = $acc[4];
				$partner->calls[$i]->status = $acc[5];
				$partner->calls[$i]->rate_sum = $acc[6];
				$partner->calls[$i]->emergency = $acc[7];
				$partner->calls[$i]->callcon = $acc[8];
				$partner->calls[$i]->caller = $acc[9];
				$partner->calls[$i]->start_date = $acc[10];
				$partner->calls[$i]->rate_vat = $acc[11];
				$partner->calls[$i]->rate_curr = $acc[12];
				$partner->calls[$i]->account = $acc[13];
				$partner->calls[$i]->cost_vat = $acc[14];
				$partner->calls[$i]->cost_curr = $acc[15];
				$partner->calls[$i]->end_date = $acc[16];
				$partner->calls[$i]->type = $acc[17];
				$partner->calls[$i]->result = $acc[18];

				$i++;
			}
			return $partner;
		}

		$e[0] = 'Unknow error. ';
		$this->set_error($e);
		return null;
	}
	
	/**
	 * Simulate call for getting info about call price
	 *
	 * @param string $callee	Call number
	 * @param type $caller		Call from number
	 * @param type $length		Length of call in seconds
	 * @return mixed			Data on success NULL on error
	 */
	public function simulate_call($callee, $caller, $length)
	{
		exec(
				$this->path . 'perl/lbilling-simulate_call.pl ' . $this->partner . 
				' ' . $this->pass . ' ' . $callee . ' ' . $caller . ' ' . $length,
				$output, $err
		);

		if ($err == 0)
		{
			$this->set_error($output);
			return null;
		}
		else if ($err == 1)
		{
			$line = 0;

			$acc = explode(";", $output[$line]);

			$call = new stdClass();
			$call->length = $acc[0];
			$call->callee = $acc[1];
			$call->caller = $acc[2];
			$call->callid = $acc[3];

			if ($acc[4] != "")
				$call->descr = $acc[4];

			$line++;
			$count = $output[$line] + $line + 1;
			$line++;

			$i = 0;
			for ($line; $line < $count; $line++)
			{
				$acc = explode(";", $output[$line]);
				$call->calls[$i] = new stdClass();
				$call->calls[$i]->provider = $acc[0];
				$call->calls[$i]->rate_vat = $acc[1];
				$call->calls[$i]->subscriber = $acc[2];
				$call->calls[$i]->account = $acc[3];
				$call->calls[$i]->area = $acc[4];
				$call->calls[$i]->callee = $acc[5];
				$call->calls[$i]->status = $acc[6];
				$call->calls[$i]->emergency = $acc[7];
				$call->calls[$i]->rate_sum = $acc[8];
				$call->calls[$i]->currency = $acc[9];
				$call->calls[$i]->end_date = $acc[10];
				$call->calls[$i]->callcon = $acc[11];
				$call->calls[$i]->caller = $acc[12];
				$call->calls[$i]->type = $acc[13];
				$call->calls[$i]->start_date = $acc[14];
				$call->calls[$i]->result = $acc[15];

				$i++;
			}
			return $call;
		}

		$e[0] = 'Unknow error. ';
		$this->set_error($e);
		return null;
	}

	/**
	 * Sets error message
	 *
	 * @param array $error 
	 */
	protected function set_error($error)
	{
		$this->error = $error;
	}

	/**
	 * Gets errors
	 *
	 * @return array
	 */
	public function get_error()
	{
		return $this->error;
	}

}
