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
 * Bank statements
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $bank_account_id
 * @property Bank_account_Model $bank_account
 * @property integer $user_id
 * @property User_Model $user
 * @property integer $statement_number
 * @property string $type
 * @property datetime $from
 * @property datetime $to
 * @property double $opening_balance
 * @property double $closing_balance
 * @property ORM_Iterator $bank_transfers
 */
class Bank_statement_Model extends ORM
{
	protected $belongs_to = array('bank_account', 'user');
	protected $has_many = array('bank_transfers');

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
	 * Counts bank statements of given bank account.
	 * @author Jiri Svitak
	 * @param $ba_id
	 * @param $filter_values
	 * @return integer
	 */
	public function count_bank_statements($ba_id, $filter_values = array())
	{
		return $this->db->query("
				SELECT COUNT(*) AS total
				FROM bank_statements
				WHERE bank_account_id = ?
		", array($ba_id))->current()->total;
	}
	
	/**
	 * Gets bank statements of given bank account.
	 * @author Jiri Svitak
	 * @param $ba_id
	 * @param $limit_from
	 * @param $limit_results
	 * @param $order_by
	 * @param $order_by_direction
	 * @param $filter_values
	 * @return Mysql_Result
	 */
	public function get_bank_statements(
			$ba_id = null, $limit_from = 0, $limit_results = 20, $order_by = 'id',
			$order_by_direction = 'DESC', $filter_values = array())
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT * FROM bank_statements
				WHERE bank_account_id = ?
				ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", array($ba_id));
	}

	/**
	 * Gets last bank statement
	 *
	 * @return Mysql_Result
	 */
	/**
	 * Gets last closing date from statement.
	 * @return <type>
	 */
	public function get_last_statement($bank_account_id)
	{
		return $this->db->query("
				SELECT bs.to FROM bank_statements bs
				WHERE bs.bank_account_id = ?
				ORDER BY bs.to DESC
				LIMIT 1
		", $bank_account_id);
	}
}
