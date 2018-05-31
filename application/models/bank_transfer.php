<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is release under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Bank transfers
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $origin_id
 * @property Bank_account_Model $origin
 * @property integer $destination_id
 * @property Bank_account_Model $destination
 * @property integer $transfer_id
 * @property Transfer_Model $transfer
 * @property integer $bank_statement_id
 * @property Bank_statement_Model $bank_statement
 * @property integer $transaction_code
 * @property integer $number
 * @property integer $variable_symbol
 * @property integer $constant_symbol
 * @property integer $specific_symbol
 * @property string $comment
 */
class Bank_transfer_Model extends ORM
{
	protected $belons_to = array
	(
		'origin_id' => 'bank_account',
		'destination_id' => 'bank_account',
		'transfer', 'bank_statement'
	);


	/**
	 * Contruct of app, shutdown action logs by default
	 * @param type $id 
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		// disable action log
		$this->set_logger(FALSE);
	}
	
	/**
	 * It gets all bank transfers of given bank account.
	 * 
	 * @author Jiri Svitak
	 * @param $account_id
	 * @param $limit_from
	 * @param $limit_results
	 * @param $order_by
	 * @param $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_bank_transfers(
			$ba_id = null, $limit_from = 0, $limit_results = 20, $order_by = 'id',
			$order_by_direction = 'DESC', $filter_sql = '')
	{
		$where = '';
		// order by check
		if ($order_by == 'amount')
		{
			$order_by = 'IF(bt.destination_id = '.intval($ba_id).', amount, amount*-1 )';
		}
		else if (!$this->has_column($order_by))
		{
			$order_by = 'id';
		}
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// filter
		if (!empty($filter_sql))
		{
			$where = "AND $filter_sql";
		}
		// query
		return $this->db->query("
				SELECT bt.id, ba.account_nr, ba.bank_nr, ba.name, t.datetime, 
					t.text, IF(bt.destination_id = ?, t.amount, -t.amount) AS amount,
					bt.variable_symbol, bt.transfer_id
				FROM bank_transfers bt
				LEFT JOIN bank_accounts ba ON ba.id = IF (bt.origin_id = ?, bt.destination_id, bt.origin_id)
				LEFT JOIN transfers t ON t.id = bt.transfer_id
				WHERE (bt.origin_id = ? OR bt.destination_id = ?)
				$where
				ORDER BY $order_by $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", array($ba_id, $ba_id, $ba_id, $ba_id));
	}

	/**
	 * It counts all bank transfers of given bank account.
	 * 
	 * @author Jiri Svitak
	 * @param $account_id
	 * @return integer
	 */
	public function count_bank_transfers($ba_id, $filter_sql = '')
	{
		$where = '';
		if (!empty($filter_sql))
		{
			$where = "AND $filter_sql";
		}
		// query
		return $this->db->query("
				SELECT COUNT(*) AS total
				FROM bank_transfers bt
				LEFT JOIN bank_accounts ba ON ba.id = IF (bt.origin_id = ?, bt.destination_id, bt.origin_id)
				LEFT JOIN transfers t ON t.id = bt.transfer_id
				WHERE (bt.origin_id = ? OR bt.destination_id = ?)
				$where						
		", array($ba_id, $ba_id, $ba_id))->current()->total;		
	}
	
	/**
	 * Gets bank transfers by bank statement.
	 * 
	 * @author Jiri Svitak
	 * @param $bs_id
	 * @param $limit_from
	 * @param $limit_results
	 * @param $order_by
	 * @param $order_by_direction
	 * @param $filter_values
	 * @return Mysql_Result
	 */
	public function get_bank_transfers_by_statement(
			$bs_id = null, $limit_from = 0, $limit_results = 20, $order_by = 'id',
			$order_by_direction = 'DESC', $filter_values = array())
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT bt.id, ba.account_nr, ba.bank_nr, ba.name, t.datetime, t.text, 
					IF(bt.destination_id = (
						SELECT DISTINCT bank_account_id
						FROM bank_statements
						WHERE id = ?
					), t.amount, -t.amount) AS amount,
					bt.variable_symbol, bt.transfer_id
				FROM bank_transfers bt
				LEFT JOIN transfers t ON t.id = bt.transfer_id
				LEFT JOIN bank_accounts ba ON ba.id = IF(bt.origin_id = (
					SELECT DISTINCT bank_account_id
					FROM bank_statements
					WHERE id = ?
				), bt.destination_id, bt.origin_id)
				WHERE bt.bank_statement_id = ?
				ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", array($bs_id, $bs_id, $bs_id));
	}
	
	/**
	 * It counts all bank transfers of given bank account.
	 * 
	 * @author Jiri Svitak
	 * @param $account_id
	 * @return integer
	 */
	public function count_bank_transfers_by_statement($bs_id, $filter_values = array())
	{
		return $this->db->count_records('bank_transfers', array
		(
			'bank_statement_id' => $bs_id
		));
	}

	/**
	 * Gets sum of member fees on statement. Used for summary of imported bank statement.
	 * 
	 * @author Jiri Svitak
	 * @param unknown_type $bs_id
	 * @return Mysql_Result
	 */
	public function get_sum_of_member_fees_by_statement($bs_id)
	{
		return $this->db->query("
				SELECT SUM(amount) AS member_fees
				FROM transfers t
				JOIN bank_transfers bt ON bt.transfer_id = t.id
				JOIN accounts a ON a.id = t.origin_id
				WHERE a.account_attribute_id = ? AND bt.bank_statement_id = ?
		", array(Account_attribute_Model::MEMBER_FEES, $bs_id))->current()->member_fees;
	}
	
	/**
	 *
	 * @param unknown_type $bs_id
	 * @return Mysql_Result
	 */
	public function get_sum_of_interests_by_statement($bs_id)
	{
		return $this->db->query("
			SELECT SUM(amount) AS interests
			FROM transfers t
			JOIN bank_transfers bt ON bt.transfer_id = t.id
			JOIN accounts a ON a.id = t.origin_id
			WHERE a.account_attribute_id = ? AND bt.bank_statement_id = ?
		", array(Account_attribute_Model::BANK_INTERESTS, $bs_id))->current()->interests;
	}	

	/**
	 *
	 * @param unknown_type $bs_id
	 * @return Mysql_Result
	 */
	public function get_sum_of_inbound_by_statement($bs_id)
	{
		return $this->db->query("
			SELECT SUM(amount) AS inbound
			FROM transfers t
			JOIN bank_transfers bt ON bt.transfer_id = t.id
			JOIN accounts a ON a.id = t.destination_id
			WHERE a.account_attribute_id = ? AND bt.bank_statement_id = ?
		", array(Account_attribute_Model::BANK, $bs_id))->current()->inbound;
	}	
		
	/**
	 *
	 * @param unknown_type $bs_id
	 * @return Mysql_Result
	 */
	public function get_sum_of_bank_fees_by_statement($bs_id)
	{
		return $this->db->query("
			SELECT SUM(amount) AS bank_fees
			FROM transfers t
			JOIN bank_transfers bt ON bt.transfer_id = t.id
			JOIN accounts a ON a.id = t.destination_id
			WHERE a.account_attribute_id = ? AND bt.bank_statement_id = ?
		", array(Account_attribute_Model::BANK_FEES, $bs_id))->current()->bank_fees;
	}
	
	/**
	 *
	 * @param unknown_type $bs_id
	 * @return Mysql_Result
	 */
	public function get_sum_of_suppliers_by_statement($bs_id)
	{
		return $this->db->query("
			SELECT SUM(amount) AS suppliers
			FROM transfers t
			JOIN bank_transfers bt ON bt.transfer_id = t.id
			JOIN accounts a ON a.id = t.destination_id
			WHERE a.account_attribute_id = ? AND bt.bank_statement_id = ?
		", array(Account_attribute_Model::SUPPLIERS, $bs_id))->current()->suppliers;		
	}
	
	
	/**
	 *
	 * @param unknown_type $bs_id
	 * @return Mysql_Result
	 */
	public function get_sum_of_outbound_by_statement($bs_id)
	{
		return $this->db->query("
			SELECT SUM(amount) AS outbound
			FROM transfers t
			JOIN bank_transfers bt ON bt.transfer_id = t.id
			JOIN accounts a ON a.id = t.origin_id
			WHERE a.account_attribute_id = ? AND bt.bank_statement_id = ?
		", array(Account_attribute_Model::BANK, $bs_id))->current()->outbound;
	}	
	
	
	/**
	 * It gets unidentified member fees transfers from db. 
	 * Unidentified transfer is that with member_id=0. 
	 * We could also find unidentified transfer using previous_transfer_id by this condition:
	 *		WHERE srct.id NOT
	 *		IN (			
	 *		SELECT previous_transfer_id
	 *		FROM transfers asst
	 *		JOIN accounts ac ON ac.id = asst.destination_id
	 *		AND ac.account_attribute_id =".Account_attribute_Model::CREDIT."
	 *		)
	 * @author Jiri Svitak, Tomas Dulik
	 * @return Mysql_Result
	 */
	public function get_unidentified_transfers(
			$limit_from = 0, $limit_results = 500, $order_by = 'id',
			$order_by_direction = 'asc', $filter_sql = "")
	{
		$where = '';
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// filter
		if (!empty($filter_sql))
		{
			$where = "WHERE $filter_sql";
		}
		
		// srct contains all source transactions, asst contains all transfers assigned to credit accounts
		return $this->db->query("
				SELECT srct.id, srct.datetime, srct.amount, srct.text,
					bt.variable_symbol, ba.account_nr, ba.bank_nr, ba.name
				FROM transfers srct
				JOIN accounts a ON a.id = srct.origin_id
					AND (srct.member_id = 0 OR srct.member_id IS NULL) 
					AND a.account_attribute_id = ?
				JOIN bank_transfers bt ON bt.transfer_id = srct.id
				LEFT JOIN bank_accounts ba ON ba.id = bt.origin_id
				$where
				ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction
				LIMIT ". intval($limit_from).", ".intval($limit_results) . "
		", array(Account_attribute_Model::MEMBER_FEES));

	}
	
	/**
	 * Function gets count of unidentified transfers
	 * 
	 * @return integer
	 */
	public function count_unidentified_transfers($filter_sql = "")
	{
		$where = '';
		
		// filter
		if (!empty($filter_sql))
		{
			$where = "WHERE $filter_sql";
		}
		
		// filter
	    return $this->db->query("
				SELECT COUNT(srct.id) as total
				FROM transfers srct
				JOIN accounts a ON a.id = srct.origin_id
					AND (srct.member_id = 0 OR srct.member_id IS NULL)
					AND a.account_attribute_id = ?
				JOIN bank_transfers bt ON bt.transfer_id = srct.id
				LEFT JOIN bank_accounts ba ON ba.id = bt.origin_id
				$where
		", array(Account_attribute_Model::MEMBER_FEES))->current()->total;
	}
	
	/**
	 * It gets transfer including bank transfer information. Assigned bank transfer must exist.
	 * 
	 * @param $trans_id
	 * @return Mysql_Result
	 */
	public function get_bank_transfer($trans_id)
	{
		return $this->db->query("
				SELECT oba.id AS oba_id, oba.name AS oba_name, 
					CONCAT(oba.account_nr, '/', oba.bank_nr) AS oba_number,
					m.id AS oba_member_id, m.name AS oba_member_name,		
					dba.id AS dba_id, dba.name AS dba_name,
					CONCAT(dba.account_nr, '/', dba.bank_nr) AS dba_number,
					m2.id AS dba_member_id, m2.name AS dba_member_name,
					t.id, t.origin_id, t.destination_id, t.datetime,
					t.creation_datetime, t.text, t.amount, bt.id AS bt_id,
					bt.variable_symbol, bt.constant_symbol, bt.specific_symbol,
					bt.bank_statement_id, bt.transaction_code
				FROM transfers t
				JOIN bank_transfers bt ON t.id = bt.transfer_id
				LEFT JOIN bank_accounts oba ON oba.id = bt.origin_id
				LEFT JOIN bank_accounts dba ON dba.id = bt.destination_id
				LEFT JOIN members m ON oba.member_id = m.id
				LEFT JOIN members m2 ON dba.member_id = m2.id
				WHERE t.id = ?													
		", $trans_id)->current();
	}

	/**
	 * @author Tomas Dulik
	 * @return Mysql_Result	object containing possible duplicities
	 * @param $data - object containing info about a bank transfer (from the bank account listing)
	 *  parsed_acc_nr => 184932848 //cislo parsovaneho uctu
	 *  parsed_acc_bank_nr=> 2400	//cislo banky parsovaneho uctu
	 *  number => 1 					//cislo vypisu
	 *  date_time => 2008-03-25 05:40  //datum a cas 
	 *  comment => Rozpis polozek uveden v soupisu prevodu 
	 *  name => CESKA POSTA, S.P. 
	 *  account_nr => 160987123 
	 *  account_bank_nr = 0300
	 *  type => Příchozí platba 
	 *  variable_symbol => 9081000001 
	 *  constant_symbol => 998 
	 *  specific_symbol => 9876543210 
	 *  amount => 720.00 
	 *  fee => -6.90
	 *  
	 *  The cardinalities of a real-life bank_transfers JOIN transfers table with 10453 rows:
	 *  datetime:4165, 	text:3063, 	variable_symbol:2173 	bt.origin_id:1912
	 */
	public function get_duplicities($data)
	{
		if (!is_object($data))
		{
			return false;
		}
		
		$cond_number = '';
		$cond_vs = 'IS NULL';
		
		if (!empty($data->variable_symbol))
		{
			$cond_vs = '=' . $this->db->escape_str($data->variable_symbol);
		}
		
		if (!empty($data->number))
		{
			$cond_number = 'AND bt.number=' . $this->db->escape($data->number);
		}
		
		return $this->db->query("
				SELECT t.datetime, t.creation_datetime, t.text, bt.*  
				FROM bank_transfers AS bt
				JOIN transfers AS t ON bt.transfer_id=t.id 
					AND t.datetime = ?
					AND t.text = ? 
					$cond_number 
					AND bt.variable_symbol $cond_vs
		", array($data->date_time, $data->comment));
	}

	/**
	 * Checks duplicities by comparing given transaction codes and
	 * searching them in the database. Successful search means duplicity.
	 * Used in Fio importer.
	 *
	 * Based on assumption, that bank has unique transaction codes in its scope.
	 * It is not necessary to check bank code here, because bank account number
	 * and bank code are checked before saving transfers.
	 * 
	 * @author Jiri Svitak
	 * @param array $transaction_codes 
	 * @return array
	 */
	public function get_transaction_code_duplicities($transaction_codes, $bank_account_id)
	{
		if (!is_array($transaction_codes) || !count($transaction_codes))
		{
			return array();
		}
		
		$codes = implode(',', array_map('intval', $transaction_codes));
		
		$duplicities = $this->db->query("
				SELECT bt.transaction_code
				FROM bank_transfers bt
				LEFT JOIN bank_statements bs ON bs.id = bt.bank_statement_id
				LEFT JOIN bank_accounts ba ON ba.id = bs.bank_account_id
				WHERE bt.transaction_code IN (" . $codes . ")
					AND ba.id = ?
		", $bank_account_id);
		
		$duplicate_transaction_codes = array();
		
		foreach ($duplicities as $duplicity)
		{
			$duplicate_transaction_codes[] = $duplicity->transaction_code;
		}
		
		return $duplicate_transaction_codes;
	}

	/**
	 * Returns duplicities in table for given bank account
	 * Used in Tatra banka importer
	 *
	 * @author David Raska
	 * @param $bank_account_id
	 * @return array
	 */
	public function get_transactions_duplicities($bank_account_id)
	{
		$duplicities = $this->db->query("
				SELECT t.datetime, t.amount, oba.name, CONCAT(oba.account_nr, '/', oba.bank_nr) AS bank_account, COUNT(bt.id) AS count
				FROM `bank_transfers` bt
				LEFT JOIN `transfers` t ON t.id = bt.transfer_id
				LEFT JOIN bank_statements bs ON bs.id = bt.bank_statement_id
				LEFT JOIN bank_accounts ba ON ba.id = bs.bank_account_id
				LEFT JOIN bank_accounts oba ON oba.id = bt.origin_id
				WHERE ba.id = ?
				GROUP BY t.datetime, t.amount, bt.origin_id
				HAVING count > 1
		", $bank_account_id);

		$dupl_array = array();

		foreach ($duplicities as $duplicity)
		{
			$dupl_array[] =
				$duplicity->datetime . ", " .
				$duplicity->amount. " " . Settings::get('currency') . ", " .
				__('Origin bank account: ') .
				$duplicity->bank_account;
		}

		return $dupl_array;
	}
	
	/**
	 * Gets last transaction code of the given bank account.
	 * 
	 * @see Fio_Bank_Statement_Importer
	 * @param integer $bank_account_id
	 * @return integer|null
	 */
	public function get_last_transaction_code_of($bank_account_id)
	{
		$result = $this->db->query("
				SELECT MAX(bt.transaction_code) AS last_tc
				FROM bank_transfers bt
				JOIN bank_statements bs ON bt.bank_statement_id = bs.id
				WHERE bs.bank_account_id = ?
		", $bank_account_id);
		
		if ($result->count())
		{
			return $result->current()->last_tc;
		}
		
		return NULL;
	}

	/**
	 * Deletes all bank transactions that has specified bank account set as
	 * origin.
	 *
	 * @param int $bank_account_id
	 */
	public function delete_all_with_origin($bank_account_id)
	{
		$this->db->query("
				DELETE FROM bank_transfers
				WHERE origin_id = ?
		", intval($bank_account_id));
	}

	/**
	 * Deletes all bank transactions that is bound to transfer with assigned
	 * specified member.
	 *
	 * @param int $member_id
	 */
	public function delete_all_with_transfer_to($member_id)
	{
		$this->db->query("
				DELETE bank_transfers FROM bank_transfers
				INNER JOIN transfers ON bank_transfers.transfer_id = transfers.id
				WHERE transfers.member_id = ?
		", intval($member_id));
	}

}
