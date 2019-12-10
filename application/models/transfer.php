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
 * Transfer between accounts.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $origin_id
 * @property Account_Model $origin
 * @property integer $destination_id
 * @property Account_Model $destination
 * @property integer $previous_transfer_id
 * @property Transfer_Model $previous_transfer
 * @property integer $member_id
 * @property Member_Model $member
 * @property integer $user_id
 * @property User_Model $user
 * @property integer $type
 * @property datetime $datetime
 * @property datetime $creation_datetime
 * @property string $text
 * @property double $amount
 */
class Transfer_Model extends ORM
{
	/** Special type of transfer: deduct member fee */
	const DEDUCT_MEMBER_FEE						= 1;
	/** Special type of transfer: deduct entrance fee */
	const DEDUCT_ENTRANCE_FEE					= 2;
	/** Special type of transfer: deduct voip_unnaccounted fee */
	const DEDUCT_VOIP_UNNACCOUNTED_FEE			= 3;
	/** Special type of transfer: deduct voip_accounted fee */
	const DEDUCT_VOIP_ACCOUNTED_FEE				= 4;
	/** Special type of transfer: deduct device fee */
	const DEDUCT_DEVICE_FEE						= 5;
	
	/** Group of transfers: outer */
	const OUTER_TRANSFERS						= 1;
	/** Group of transfers: inner */
	const INNER_TRANSFERS						= 2;
	
	/** Inbound ans outbound type of transfers */
	const INBOUND_AND_OUTBOUND					= 1;
	/** Inbound type of transfers */
	const INBOUND								= 2;
	/** Unbound type of transfers */
	const OUTBOUND								= 3;
	
	
	protected $belongs_to = array
	(
		'origin' => 'account',
		'destination' => 'account',
		'previous_transfer' => 'transfer',
		'user', 'member'
	);
	
	protected $has_many = array('job_reports');

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
	 * Function gets all double-entry transfer. They are shown in day book.
	 * 
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_all_transfers(
			$limit_from = 0, $limit_results = 20,
			$order_by = 'id', $order_by_direction = 'desc',
			$filter_sql = '', $filter_name_sql = '')
	{
		$where = '';
		$where_name = '';
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
		if (!empty($filter_name_sql))
		{
			$where_name = "WHERE $filter_name_sql";
		}
		// query
		return $this->db->query("
				SELECT *
				FROM
				(
					SELECT t.id, oa.id AS oa_id, oa.name AS oa_name,
						oa.account_attribute_id AS oa_attribute,
						da.id AS da_id, da.name AS da_name,
						da.account_attribute_id AS da_attribute,
						t.text, t.amount AS daybook_amount, t.datetime,
						IF (oa.account_attribute_id <> da.account_attribute_id, ?, ?) AS transfer
					FROM transfers t
					LEFT JOIN accounts oa ON oa.id = t.origin_id
					LEFT JOIN accounts da ON da.id = t.destination_id
					$where
				) q
				$where_name
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT ".intval($limit_from).", ".intval($limit_results)."
		", Transfer_Model::OUTER_TRANSFERS, Transfer_Model::INNER_TRANSFERS);
	}	

	/**
	 * Function counts all transfers. Used in day book.
	 * 
	 * @return integer
	 */
	public function count_all_transfers($filter_sql = '', $filter_name_sql = '')
	{
		$where = '';
		$where_name = '';
		// filter
		if (!empty($filter_sql))
		{
			$where = "WHERE $filter_sql";
		}
		// filter
		if (!empty($filter_name_sql))
		{
			$where_name = "WHERE $filter_name_sql";
		}
		// query
		return $this->db->query("
				SELECT COUNT(*) AS total
				FROM
				(
					SELECT oa.name AS oa_name, da.name AS da_name,
						t.text, t.amount AS daybook_amount, t.datetime,
						IF (oa.account_attribute_id <> da.account_attribute_id, ?, ?) AS transfer
					FROM transfers t
					LEFT JOIN accounts oa ON oa.id = t.origin_id
					LEFT JOIN accounts da ON da.id = t.destination_id
					$where
				) q
				$where_name
		", Transfer_Model::OUTER_TRANSFERS, Transfer_Model::INNER_TRANSFERS)->current()->total;
	}	
	
	/**
	 * Function gets all money transfers of double-entry account.
	 * @param $account_id
	 * @param $limit_from
	 * @param $limit_results
	 * @param $order_by
	 * @param $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_transfers($account_id = null, $limit_from = 0,
			$limit_results = 20, $order_by = 't.id', $order_by_direction = 'ASC',
			$filter_sql = '', $filter_array = NULL)
	{
		$account_id = intval($account_id);
		// filter
		$where = '';
		$cond = "(t.origin_id = $account_id OR t.destination_id = $account_id)";;
		// filter
		if (!empty($filter_sql))
		{
			$where = "WHERE $filter_sql";
		}
		if (is_array($filter_array))
		{
			foreach ($filter_array as $filter)
			{
				if (isset($filter['key']) &&
					$filter['key'] = 'type')
				{
					foreach ($filter['value'] as $v)
					{
						if ($v == Transfer_Model::INBOUND)		//inbound
						{
							$cond = "(t.destination_id = $account_id)";
						}
						else if ($v == Transfer_Model::OUTBOUND)	//outbound
						{
							$cond = "(t.origin_id = $account_id)";
						}
					}
				}
			}		
		}
		
		if (empty($where))
		{
			$cond = "WHERE $cond";
		}
		else
		{
			$cond = "AND $cond";
		}
		
		// order by check
		if ($order_by == 'amount')
		{
			$order_by = 'IF(t.destination_id = '.$account_id.', t.amount, -t.amount)';
		}
		else
		{
			$order_by = $this->db->escape_column($order_by);
		}
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT t.id, t.text,
					IF(t.amount <> 0, IF(t.destination_id = $account_id, t.amount, -t.amount), 0) AS amount,
					t.datetime, t.origin_id, t.destination_id, a.name,
					IF(t.destination_id = $account_id, bt.variable_symbol, NULL) AS variable_symbol,
					(
						SELECT SUM(IF(t.destination_id = $account_id, t.amount, 0)) AS inbound
						FROM transfers t
						LEFT JOIN accounts a ON a.id = IF(t.origin_id = $account_id, t.destination_id, t.origin_id)
						$where
					) AS inbound,
					(
						SELECT SUM(IF(t.origin_id = $account_id, t.amount, 0)) AS outbound 
						FROM transfers t
						LEFT JOIN accounts a ON a.id = IF(t.origin_id = $account_id, t.destination_id, t.origin_id) 
						$where
					) AS outbound	
				FROM transfers t
				LEFT JOIN accounts a ON a.id = IF(t.origin_id = $account_id, t.destination_id, t.origin_id)
				LEFT JOIN transfers pt ON pt.id = t.previous_transfer_id
				LEFT JOIN bank_transfers bt ON pt.id = bt.transfer_id
				$where $cond
				ORDER BY $order_by $order_by_direction, `id` $order_by_direction
				LIMIT ".intval($limit_from).", ".intval($limit_results)."
		");
	}

	/**
	 * Function gets all money transfers of double-entry account.
	 * @param $account_id
	 * @param $limit_from
	 * @param $limit_results
	 * @param $order_by
	 * @param $order_by_direction
	 * @return Mysql_Result
	 */
	public function sum_transfers_to_id($account_id = null, $toid)
	{
		$account_id = intval($account_id);

		// query
		return $this->db->query("
				SELECT
					SUM(IF(t.destination_id = $account_id, t.amount, 0)) -
					SUM(IF(t.origin_id = $account_id, t.amount, 0)) AS amount
				FROM transfers t
				WHERE t.id <= ?
		", $toid, $toid)->current()->amount;
	}
	
	/**
	 * Function gets all money transfers of double-entry account.
	 * 
	 * @param $account_id
	 * @param $limit_from
	 * @param $limit_results
	 * @param $order_by
	 * @param $order_by_direction
	 * @return integer
	 */
	public function count_transfers($account_id = null, $filter_sql = '', $filter_array = NULL)
	{
		$account_id = intval($account_id);
		// filter
		$where = '';
		$cond = "(t.origin_id = $account_id OR t.destination_id = $account_id)";
		// filter
		if (!empty($filter_sql))
		{
			$where = "WHERE $filter_sql";
		}
		if (is_array($filter_array) && !count($filter_array))
		{
			foreach ($filter_array as $filter)
			{
				if (isset($filter['key']) &&
					$filter['key'] = 'type')
				{
					foreach ($filter['value'] as $v)
					{
						if ($v == Transfer_Model::INBOUND)		//inbound
						{
							$cond = "(t.destination_id = $account_id)";
						}
						else if ($v == Transfer_Model::OUTBOUND)	//outbound
						{
							$cond = "(t.origin_id = $account_id)";
						}
					}
				}
			}
		}
		
		if (empty($where))
		{
			$cond = "WHERE $cond";
		}
		else
		{
			$cond = "AND $cond";
		}
		
		// query
		return $this->db->query("
				SELECT COUNT(*) AS total	
				FROM transfers t
				LEFT JOIN accounts a ON a.id = IF(t.origin_id = $account_id, t.destination_id, t.origin_id)
				LEFT JOIN transfers pt ON pt.id = t.previous_transfer_id
				LEFT JOIN bank_transfers bt ON pt.id = bt.transfer_id
				$where $cond
		")->current()->total;
	}
	
	/**
	 * Function gets information of specified transfer.
	 * 
	 * @param integer $trans_id
	 * @return Mysql_Result
	 */
	public function get_transfer($trans_id = null)
	{
		$result = $this->db->query("
				SELECT t.id, t.previous_transfer_id, t.datetime, t.creation_datetime,
					t.text, t.amount, t.member_id, t.user_id, oa.id AS oa_id,
					oa.name AS oa_name, da.id AS da_id, da.name AS da_name,
					u.name, u.surname, j.id AS job_id, j.description AS job_description,
					jr.id AS job_report_id, jr.description AS job_report_description
				FROM transfers t
				LEFT JOIN accounts oa ON oa.id = t.origin_id
				LEFT JOIN accounts da ON da.id = t.destination_id
				LEFT JOIN users u ON t.user_id = u.id
				LEFT JOIN jobs j ON j.transfer_id = t.id
				LEFT JOIN job_reports jr ON jr.id = j.job_report_id
				WHERE t.id = ?
				GROUP BY t.id
		", $trans_id);
		
		return ($result && $result->count()) ? $result->current() : null;
	}

	/**
	 * Function gets dependent transfers of transfer that is member fee payment.
	 * 
	 * @param $transfer_id
	 * @return Mysql_Result
	 */
	public function get_dependent_transfers($transfer_id = NULL)
	{
        if ($transfer_id === NULL)
        {
            $transfer_id = $this->id;
        }

		return $this->db->query("
				SELECT dt.*
				FROM transfers t
				JOIN transfers dt ON dt.previous_transfer_id = t.id
				WHERE t.id = ?
		", $transfer_id);
	}
	
	/**
	 * Gets "entrance fee" transfers of given account.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 * @return Mysql_Result
	 */
	public function get_entrance_fee_transfers_of_account($account_id)
	{
		return $this->db->query("
				SELECT t.id, t.datetime, amount,
					(
						SELECT SUM(t.amount) AS total_amount
						FROM transfers t
						WHERE t.origin_id = ? AND t.type = ?
					) AS total_amount
				FROM transfers t
				WHERE t.origin_id = ? AND t.type = ?
				GROUP BY t.id, t.datetime, t.amount
				ORDER BY t.datetime DESC
		", array
		(
			$account_id, Transfer_Model::DEDUCT_ENTRANCE_FEE,
			$account_id, Transfer_Model::DEDUCT_ENTRANCE_FEE
		));		
	}
	
	/**
	 * Returns datetime of last entrance fee transfer of account
	 * 
	 * @author Michal Kliment
	 * @param type $account_id
	 * @return null 
	 */
	public function get_last_entrance_fee_transfer_datetime_of_account($account_id)
	{
		$result = $this->db->query("
				SELECT t.datetime
				FROM transfers t
				WHERE t.origin_id = ? AND t.type = ?
				GROUP BY t.id, t.datetime, t.amount
				ORDER BY t.datetime DESC
				LIMIT 0,1
		", array
		(
			$account_id, Transfer_Model::DEDUCT_ENTRANCE_FEE,
			$account_id, Transfer_Model::DEDUCT_ENTRANCE_FEE
		));		
		
		if ($result && $result->current() && $result->current()->datetime != '')
			return $result->current()->datetime;
		else
			return NULL;
	}
	
	/**
	 * Count total amount of entrance fee transfers of account
	 * 
	 * @author Michal Kliment
	 * @param type $account_id
	 * @return int 
	 */
	public function count_entrance_fee_transfers_of_account($account_id)
	{
		$transfers = $this->get_entrance_fee_transfers_of_account($account_id);
		
		if ($transfers && $transfers->current())
			return $transfers->current()->total_amount;
		else
			return 0;
	}

	/**
	 * Used for deduction of device repayments.
	 * 
	 * @author Jiri Svitak
	 * @param object $ca
	 * @param string $date
	 * @return Mysql_Result 
	 */
	public function get_device_fee_transfers_of_account_and_date($ca, $date)
	{
		if (!is_object($ca))
		{
			return null;
		}
		
		return $this->db->query("
				SELECT id
				FROM transfers
				WHERE type = ? AND origin_id = ? AND datetime LIKE ?
		", Transfer_Model::DEDUCT_DEVICE_FEE, $ca->id, "%$date%");
	}
	
	/**
	 * Sums transfers of device fees of account
	 * 
	 * @author Michal Kliment
	 * @param type $account_id
	 * @return type 
	 */
	public function sum_device_fee_transfers_of_account($account_id)
	{
		return $this->db->query("
				SELECT SUM(amount) AS total_amount
				FROM transfers
				WHERE type = ? AND origin_id = ?
		", array(self::DEDUCT_DEVICE_FEE, $account_id))->current()->total_amount;
	}

	/**
	 * Gets all monthly amounts of incoming member payment for stats
	 * 
	 * @author Michal Kliment
	 * @return MySQL_Result object
	 */
	public function get_all_monthly_amounts_of_incoming_member_payment()
	{
		return $this->db->query("
				SELECT SUBSTR(datetime, 1, 7) AS date, SUBSTR(datetime, 1, 4) AS year,
					SUBSTR(datetime, 6, 2) AS month, SUM(amount) AS amount
				FROM transfers t
				WHERE t.origin_id IN (
						SELECT id
						FROM accounts
						WHERE account_attribute_id = ?
					) AND (
						t.destination_id IN (
							SELECT id
							FROM accounts
							WHERE account_attribute_id = ?
						) OR t.destination_id IN (
							SELECT id
							FROM accounts
							WHERE member_id = ? AND account_attribute_id = ?
						)
					)
				GROUP BY date
		", array
		(
			Account_attribute_Model::MEMBER_FEES, Account_attribute_Model::CASH,
			Member_Model::ASSOCIATION, Account_attribute_Model::BANK
		));
	}

	/**
	 * Gets amount of all member fees grouped by month for stats
	 * 
	 * @author Ondřej Fibich
	 * @return MySQL_Result object
	 */
	public function get_grouped_monthly_member_fees()
	{
		$operating = ORM::factory('account')->where(
				'account_attribute_id', Account_attribute_Model::OPERATING
		)->find();
		
		return $this->db->query("
				SELECT SUBSTR(datetime, 1, 7) AS date, SUBSTR(datetime, 1, 4) AS year,
					SUBSTR(datetime, 6, 2) AS month, SUM(amount) AS amount
				FROM transfers t
				WHERE type = ? AND destination_id = ?
				GROUP BY year, month
		", self::DEDUCT_MEMBER_FEE, $operating->id);
	}

	/**
	 * Gets datime of last transfer by type
	 *
	 * @author Michal Kliment
	 * @param integer $type
	 * @return string
	 */
	public function find_last_transfer_datetime_by_type($type)
	{
		$result = $this->db->query("
				SELECT datetime FROM transfers t
				WHERE t.type = ?
				ORDER BY datetime DESC
				LIMIT 0,1
		", $type);

		// there is any existing transfer by this type
		if ($result && $result->count())
			return $result->current()->datetime;
		else
			return NULL;
	}
	
	/**
	 * Get datetime of last transfer of account
	 * 
	 * @author Michal Kliment
	 * @param type $account_id
	 * @return null 
	 */
	public function get_last_transfer_datetime_of_account($account_id)
	{
		$result = $this->db->query("
				SELECT t.datetime
				FROM transfers t
				WHERE t.origin_id = ? AND (t.type = ? OR t.type = ? OR t.type = ?)
				GROUP BY t.id, t.datetime, t.amount
				ORDER BY t.datetime DESC
				LIMIT 0,1
		", array
		(
			$account_id, self::DEDUCT_MEMBER_FEE,
			self::DEDUCT_ENTRANCE_FEE, self::DEDUCT_DEVICE_FEE
		));		
		
		if ($result && $result->current() && $result->current()->datetime != '')
			return $result->current()->datetime;
		else
			return NULL;
	}


	/**
	 * Creates transfer
	 * 
	 * @author Jiri Svitak
	 * @param integer $origin_id			origin account id
	 * @param integer $destination_id		destination account id
	 * @param integer $previous_transfer_id	previous transfer id, useful for transfer groups
	 * @param integer $member_id			transaction owner id
	 * @param integer $user_id				id of user who added transfer
	 * @param integer $type					type of transfer, see Transfer_Model
	 * @param string $datetime				accounting datetime of transfer
	 * @param string $creation_datetime		datetime of transfer creation
	 * @param string $text					transfer text
	 * @param double $amount				amount of transfer
	 * @return integer						ID of created transfer
	 * @throws Kohana_Databse_Exception
	 *		when failed transfer insert, origin or destination account update
	 */
	public static function insert_transfer(
			$origin_id, $destination_id, $previous_transfer_id, $member_id,
			$user_id, $type, $datetime, $creation_datetime, $text, $amount)
	{
		$previous_transfer_id = intval($previous_transfer_id);
		$member_id = intval($member_id);
		$user_id = intval($user_id);
		
		// insert new transfer
		$transfer = new Transfer_Model();
		$transfer->origin_id = $origin_id;
		$transfer->destination_id = $destination_id;
		$transfer->previous_transfer_id = $previous_transfer_id ? $previous_transfer_id : NULL;
		$transfer->member_id = $member_id ? $member_id : NULL;
		$transfer->user_id = $user_id ? $user_id : NULL;
		$transfer->type = $type;
		$transfer->datetime = $datetime;
		$transfer->creation_datetime = $creation_datetime;
		$transfer->text = $text;
		$transfer->amount = $amount;
		$transfer->save_throwable();
		// update balance of origin account
		$oa = new Account_Model($origin_id);
		$oa->balance -= $amount;
		$oa->save_throwable();
		// update balance of destination account
		$da = new Account_Model($destination_id);
		$da->balance += $amount;
		$da->save_throwable();

		return $transfer->id;
	}

	/**
	 * Edits transfers safely with change of dependent account balance.
	 * 
	 * @author Jiri Svitak
	 * @param integer $id
	 * @param string $text
	 * @param double $amount
	 * @throws Kohana_Databse_Exception
	 */
	public static function edit_transfer($id, $text, $amount)
	{
		// update transfer
		$transfer = new Transfer_Model($id);
		$transfer->text = $text;
		$transfer->amount = $amount;
		$transfer->save_throwable();
		// update balance of origin account
		$oa = new Account_Model($transfer->origin_id);
		$oa->balance -= $amount;
		$oa->save_throwable();
		// update balance of destination account
		$da = new Account_Model($transfer->destination_id);
		$da->balance += $amount;
		$da->save_throwable();
	}

	/**
	 * Safely deletes transfer.
	 * 
	 * @author Jiri Svitak
	 * @param integer $id
	 * @throws Kohana_Databse_Exception
	 */
	public static function delete_transfer($id = null)
	{
		if (!isset($id))
		{
			throw new Kohana_User_Exception (
					"delete transfer", "ID to delete has not been supplied!"
			);
		}
		
		$transfer = new Transfer_Model($id);
		
		if (!$transfer->id)
		{
			throw new Kohana_User_Exception (
					"delete transfer", "Transfer to delete has not been found!"
			);
		}
		
		// update balance of origin account
		$oa = new Account_Model($transfer->origin_id);
		$oa->balance += $transfer->amount;
		$oa->save_throwable();
		
		// update balance of destination account
		$da = new Account_Model($transfer->destination_id);
		$da->balance -= $transfer->amount;
		$da->save_throwable();
		
		// delete transfer
		$transfer->delete_throwable();
	}
	
	public static function insert_transfer_for_work_approve($member_id, $amount)
	{
		// creates new transfer
		$account_model = new Account_Model();

		$operating_id = $account_model->where(
				'account_attribute_id', Account_attribute_Model::OPERATING
		)->find()->id;

		$credit_id = $account_model->where('member_id', $member_id)
				->where('account_attribute_id', Account_attribute_Model::CREDIT)
				->find()->id;

		$transfer_id = Transfer_Model::insert_transfer(
			$operating_id, $credit_id, null, null,
			Session::instance()->get('user_id'),
			null, date('Y-m-d'), date('Y-m-d H:i:s'),
			__('Work approval'), $amount
		);
		
		return $transfer_id;
	}
}
