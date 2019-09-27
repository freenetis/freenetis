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

require_once 'RB_Exception.php';

/**
 * Parser_RB (Raiffeisen Bank) is a parser for getting data from a bank account
 * statement in the new (2018) XML format.
 * 
 * @author Jakub Juračka
 * @version 1.0
 */
class Parser_RB
{

	/**
	 * TYPE OF XML section which contains incoming payments.
	 *
	 * @var string
	 */
	private static $TYPE_INCOME = 'CRDT';

	/**
	 * TYPE OF XML section which contains outgoing payments.
	 * 	
	 * @var string
	 */
	private static $TYPE_OUTG = 'DBIT';

	/**
	 * TYPE OF XML section for saving BALANCE from the begining and end of
	 * statement period.
	 *
	 * @var string
	 */
	private static $TYPE_BAL = 'CLAV';

	/**
	 * Store the simple_xml object with bank statement.
	 * 	
	 * @var simple_xml object
	 */
	protected $data;

	/**
	 * Parsed header of bank statement.
	 *
	 * @var array()
	 */
	protected $header;

	/**
	 * Contains parsed rows of bank statement with incoming/outgoing statements.
	 * 	
	 * @var string
	 */
	protected $rows;

	/**
	 * Opening datafile and saving simple_xml object to variable $data
	 *
	 * @param string $url -	File URL path
	 *
	 * @author Jakub Juračka
	 */
	private function open($url)
	{
		if ($this->data = simplexml_load_file($url))
		{
			$this->data = $this->data->BkToCstmrStmt->Stmt;
		}
		else
		{
			throw new Exception("Can not open file! Check if $url exists!");
		}
	}

	/**
	 * Loading header and saving array 
	 * with bank account info to the variable $header.
	 * 
	 * @author Jakub Juračka
	 */
	private function loadHeader()
	{
		$datetime_info = $this->data->FrToDt;

		$from = isset($datetime_info->FrDtTm) ? $datetime_info->FrDtTm : NULL;
		$to = isset($datetime_info->ToDtTm) ? $datetime_info->ToDtTm : NULL;

		if (!self::is_correct($from) || !self::is_correct($to))
		{
			throw new RB_Exception(__('Statement period (FROM - TO) is not set. Please, check the statement header.'));
		}

		//Proccessing datetime to sql format
		$from = str_replace('T', ' ', $from);
		$to = str_replace('T', ' ', $to);
		////////////////////////////////////

		$acc_info = $this->data->Acct;

		foreach ($this->data->Bal as $Balance)
		{
			$type = $Balance->Tp->CdOrPrtry->Cd;

			if ($type == self::$TYPE_BAL && !isset($balance_start_obj))
				$balance_start_obj = $Balance;

			if ($type == self::$TYPE_BAL && $Balance->Dt->Dt != $balance_start_obj->Dt->Dt)
				$balance_end_obj = $Balance;
		}

		if (!isset($balance_start_obj) || !isset($balance_end_obj))
		{
			throw new RB_Exception(__('Please, check the file. ') . ' '
					. __('Missing data in header - balance on START or END of statement period.'));
		}

		//GETTING BALANCE INFO
		$money_status_start = $balance_start_obj->Amt;
		$money_status_end = $balance_end_obj->Amt;

		//GETTING INFO ABOUT BANK ACOUNT
		$IBAN = isset($acc_info->Id->IBAN) ? $acc_info->Id->IBAN : NULL;
		$currency = $acc_info->Ccy;

		$bank_code = isset($acc_info->Svcr->FinInstnId->Othr->Id) ?
				$acc_info->Svcr->FinInstnId->Othr->Id : NULL;

		$BIC = isset($acc_info->Svcr->FinInstnId->BIC) ?
				$acc_info->Svcr->FinInstnId->BIC : NULL;

		$acc_num = substr($IBAN, -10);

		if (!self::is_correct($IBAN) || !self::is_correct($bank_code) ||
			!self::is_correct($acc_num))
		{
			throw new RB_Exception(__('Please, check the file.') . ' '
					. __('Possible fault in IBAN, BANK CODE or ACCOUNT NUMBER.'));
		}

		$this->header = array
		(
			'from' => $from,
			'to' => $to,
			'IBAN' => $IBAN,
			'currency' => $currency,
			'bank_nr' => $bank_code,
			'BIC' => $BIC,
			'account_nr' => $acc_num,
			'bal_start' => $money_status_start,
			'bal_end' => $money_status_end,
		);
	}

	/**
	 * Prasing $data variable to variable $rows 
	 * 
	 * @param string $url -	File URL path
	 * 
	 * @author Jakub Juračka
	 */
	public function parse($url)
	{
		//FILE LOADING
		$this->open($url);

		//Loading header (getting info about account assigned to the statement)
		$this->loadHeader();

		//Parsing data - if datetime, account number, bank_code or amount is not defined => throw an error
		$this->rows = array();

		//Couting proccessed rows
		$iteration = 1;

		//Processing payments
		foreach ($this->data->Ntry as $row)
		{
			//Auxiliary variables $type, $detail
			$type = $row->CdtDbtInd;
			$detail = $row->NtryDtls->TxDtls;

			//Testing if the payment is OUTGING/INCOMING
			// and saving to the variable $rows
			if ($type == self::$TYPE_OUTG)
			{
				if (!self::is_correct($row->ValDt->DtTm))
					self::throw_error_row($iteration, 'Date and time');
				if (!self::is_correct($detail->RltdPties->CdtrAcct->Id->Othr->Id))
					self::throw_error_row($iteration, 'Account number');
				if (!self::is_correct($detail->RltdAgts->CdtrAgt->FinInstnId->Othr->Id))
					self::throw_error_row($iteration, 'Bank code');
				if (!self::is_correct($row->Amt))
					self::throw_error_row($iteration, 'Amount');

				array_push($this->rows, array
				(
					'datetime' => $row->ValDt->DtTm,
					'transaction_id' => isset($row->NtryRef) ? intval($row->NtryRef) : NULL,
					'acc_num' => strval($detail->RltdPties->CdtrAcct->Id->Othr->Id),
					'vs' => isset($detail->Refs->EndToEndId) ?
							intval($detail->Refs->EndToEndId) : NULL,
					'ks' => isset($detail->Refs->InstrId) ?
							intval($detail->Refs->InstrId) : NULL,
					'ss' => isset($detail->Refs->PmtInfId) ?
							intval($detail->Refs->PmtInfId) : NULL,
					'name' => isset($detail->RltdPties->DbtrAcct->Nm) ?
							strval($detail->RltdPties->DbtrAcct->Nm) : NULL,
					'bank_code' => strval($detail->RltdAgts->CdtrAgt->FinInstnId->Othr->Id),
					'amount' => ($row->Amt) * (-1),
					'text' => isset($detail->AddtlTxInf) ? strval($detail->AddtlTxInf) : ''
				));
			}
			else if ($type == self::$TYPE_INCOME)
			{
				if (!self::is_correct($row->ValDt->DtTm))
					self::throw_error_row($iteration, 'Date and time');
				if (!self::is_correct($detail->RltdPties->DbtrAcct->Id->Othr->Id))
					self::throw_error_row($iteration, 'Account number');
				if (!self::is_correct($detail->RltdAgts->DbtrAgt->FinInstnId->Othr->Id))
					self::throw_error_row($iteration, 'Bank code');
				if (!self::is_correct($row->Amt))
					self::throw_error_row($iteration, 'Amount');

				array_push($this->rows, array
				(
					'datetime' => $row->ValDt->DtTm,
					'transaction_id' => isset($row->NtryRef) ? intval($row->NtryRef) : NULL,
					'acc_num' => strval($detail->RltdPties->DbtrAcct->Id->Othr->Id),
					'vs' => isset($detail->Refs->EndToEndId) ?
							intval($detail->Refs->EndToEndId) : NULL,
					'ks' => isset($detail->Refs->InstrId) ?
							intval($detail->Refs->InstrId) : NULL,
					'ss' => isset($detail->Refs->PmtInfId) ?
							intval($detail->Refs->PmtInfId) : NULL,
					'name' => isset($detail->RltdPties->DbtrAcct->Nm) ?
							strval($detail->RltdPties->DbtrAcct->Nm) : NULL,
					'bank_code' => strval($detail->RltdAgts->DbtrAgt->FinInstnId->Othr->Id),
					'amount' => $row->Amt,
					'text' => isset($detail->AddtlTxInf) ? strval($detail->AddtlTxInf) : ''
				));
			}

			$iteration++;
		}
	}

	/**
	 * Function which only returns rows with incoming/outgoing payments
	 * 
	 * @author Jakub Juračka
	 */
	public function get_data()
	{
		return $this->rows;
	}

	/**
	 * Function which only returns info about account assigned with bank
	 * statement.
	 * 
	 * @author Jakub Juračka
	 */
	public function get_header()
	{
		return $this->header;
	}

	/**
	 * Function for checking data correctness
	 * 
	 * @param string $data
	 * 
	 * @author Jakub Juračka
	 */
	private function is_correct($data)
	{
		return isset($data) && $data != NULL && $data != '';
	}

	/**
	 * Shortcut for error throwing
	 * 
	 * @param int $row_num
	 * @param string $datatype
	 * 
	 * @author Jakub Juračka
	 */
	private function throw_error_row($row_num, $datatype)
	{
		throw new RB_Exception(__('Please, check the file.') . ' '
				. __('Missing data (%s) on the line %d.', array($datatype, $row_num)));
	}

}
