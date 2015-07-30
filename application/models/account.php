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
 * Account is own by each member.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $member_id
 * @property Member_Model $member
 * @property string $name
 * @property integer $account_attribute_id
 * @property Account_attribute_Model $account_attribute
 * @property double $balance
 * @property string $comment
 * @property integer $comments_thread_id
 * @property Comments_thread_Model $comments_thread
 * @property ORM_Iterator $transfers
 * @property ORM_Iterator $bank_accounts
 */
class Account_Model extends ORM
{
	// groups of double-entry accounts
	
	/** Accounting system */
	const ACCOUNTING_SYSTEM = 1;
	/** Credit subaccounts grop */
	const CREDIT = 2;
	/** Project subaccounts group */
	const PROJECT = 3;
	/** Other accounts group */
	const OTHER = 4;

	protected $has_many = array('transfers','variable_symbols');
	protected $belongs_to = array('member', 'account_attribute', 'comments_thread');
	protected $has_and_belongs_to_many = array('bank_accounts');

	/**
	 * Contruct of app, shutdown action logs by default
	 * @param type $id 
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);
		// disable models
		$this->set_logger(FALSE);
	}

	/**
	 * It gets double-entry accounts of given group.
	 * Groups are all credit, all project and other accounts of association.
	 * 
	 * @author Jiri Svitak
	 * @return Mysql_Result
	 */
	public function get_accounts(
			$limit_from = 0, $limit_results = 20, $order_by = 'id',
			$order_by_direction = 'asc', $filter_sql = '', $date_sql = '', $group = 4)
	{
		$where = 'WHERE ';
		$datetime = '';
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// group - project, credit and other accounts
		if ($group == self::PROJECT)
		{
			$where .= 'account_attribute_id = '.Account_attribute_Model::PROJECT;
		}
		else if ($group == self::CREDIT)
		{
			$where .= 'account_attribute_id = '.Account_attribute_Model::CREDIT;
		}
		else
		{
			$where .= 'account_attribute_id <> '.Account_attribute_Model::PROJECT
					. ' AND account_attribute_id <> '.Account_attribute_Model::CREDIT;
		}
		// default datetime of transfers
		$datetime_from_t1 = " AND t1.datetime >= '0000-00-00'";
		$datetime_to_t1 = " AND t1.datetime <= '9999-12-31'";
		$datetime_from_t2 = " AND t2.datetime >= '0000-00-00'";
		$datetime_to_t2 = " AND t2.datetime <= '9999-12-31'";
		// filter
		if (!empty($filter_sql))
		{
			if (empty($where))
			{
				$where = "WHERE $filter_sql";
			}
			else
			{
				$where .= " AND $filter_sql";
			}
		}
		if (!empty($date_sql))
		{
			$datetime = " AND $date_sql";
		}
		// query
		return $this->db->query("
			SELECT q2.*, (inbound - outbound) AS balance
			FROM
			(
				SELECT q1.*, IFNULL(SUM(amount), 0) AS inbound
				FROM
				(
					SELECT aa.id, aa.id AS aid, aa.name, aa.account_attribute_id,
					m.name AS member_name, m.id AS member_id,
					IFNULL(SUM(amount), 0) AS outbound,
					aa.comments_thread_id AS a_comments_thread_id
					FROM accounts aa
					LEFT JOIN members m ON m.id = aa.member_id
					LEFT JOIN transfers t1 ON aa.id = t1.origin_id $datetime
					$where
					GROUP BY aa.id
				) q1
				LEFT JOIN transfers t2 ON q1.id = t2.destination_id $datetime
				GROUP BY q1.id
			) q2
			ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction
			LIMIT ".intval($limit_from).", ".intval($limit_results)."
		");
	}

	/**
	 * It gets count of double-entry accounts of given group.
	 * 
	 * @author Jiri Svitak
	 * @return Mysql_Result
	 */
	public function get_accounts_count($filter_sql = '', $group = 4)
	{
		$where = 'WHERE ';
		// group - project, credit and other accounts
		if ($group == self::PROJECT)
		{
			$where .= 'account_attribute_id = '.Account_attribute_Model::PROJECT;
		}
		else if ($group == self::CREDIT)
		{
			$where .= 'account_attribute_id = '.Account_attribute_Model::CREDIT;
		}
		else
		{
			$where .= 'account_attribute_id <> '.Account_attribute_Model::PROJECT
					. ' AND account_attribute_id <> '.Account_attribute_Model::CREDIT;
		}
		if(!empty($filter_sql))
		{
			$where .= " AND $filter_sql";
		}
		// get count
		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM accounts aa
			$where
		")->current()->total;
	}

	/**
	 * Function gets some double-entry accounts. Used in dropdown to select
     * destination account.
     *
	 * @param integer $origin
	 * @param integer $account_attribute_id
	 * @return Mysql_Result
	 */
	public function get_some_doubleentry_account_names($origin = null, $account_attribute_id = null)
	{
		// conditions
		$where = $cond_origin = '';
		// make origin condition
		if ($origin !== null)
		{
            $allowed_types = array(Account_attribute_Model::CREDIT,
                Account_attribute_Model::OPERATING,
                Account_attribute_Model::PROJECT,
                Account_attribute_Model::MEMBER_FEES);
			$cond_origin = "AND a.id <> " . intval($origin)
                    . " AND a.account_attribute_id IN (" 
                    . implode(', ', array_map('intval', $allowed_types)) . ")";
		}
		// make account confition
		if ($account_attribute_id)
		{
			$where = 'WHERE a.account_attribute_id=' . intval($account_attribute_id);
		}
		// query
		return $this->db->query("
			SELECT a.id, a.name, a.member_id, m.name AS member_name,
                a.account_attribute_id
			FROM accounts a
			JOIN members m ON a.member_id=m.id $cond_origin
			$where
			ORDER BY a.name
		");
	}
	
	/**
	 * Returns gesult of get_some_doubleentry_account_names for
	 * dropdown but grouped.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $origin
	 * @param integer $account_attribute_id
	 * @return array
	 */
	public function get_some_doubleentry_account_names_grouped(
			$origin = null, $account_attribute_id = null)
	{
		// keys
		$keys = array(__('Association'), __('Members'));
		
		// result
		$grouped_accounts = array
		(
			$keys[0]	=> array(),
			$keys[1]	=> array()
		);
		
		// get accounts
		$accounts = $this->get_some_doubleentry_account_names(
			$origin, $account_attribute_id);
		
		// group them
		foreach ($accounts as $account)
		{
			if ($account->member_id == Member_Model::ASSOCIATION)
			{
				$index = $keys[0];
			}
			else
			{
				$index = $keys[1];
			}
			
			if (!array_key_exists($index, $grouped_accounts))
			{
				$grouped_accounts[$index] = array();
			}
			
			$grouped_accounts[$index][$account->id] = $account->name 
                    . ' (' . $account->id . ', ' 
                    . $account->account_attribute_id . ', '
                    . $account->member_id . ')';
		}
		
		// done
		return $grouped_accounts;
	}

	/**
	 * It gets balance of account.
	 * It subtracts all outbound transfers from all incoming transfers.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 * @return float
	 */
	public function get_account_balance($account_id)
	{
		$result = $this->db->query("
			SELECT (IFNULL(SUM(amount), 0) - IFNULL(a.outbound, 0)) AS credit
			FROM (
				SELECT SUM(amount) AS outbound
				FROM transfers
				WHERE origin_id = ?
			) a, transfers t
			WHERE t.destination_id = ?
		", array($account_id, $account_id));
		
		return (float) ($result && $result->count()) ? $result->current()->credit : 0;
	}

	/**
	 * This function creates new record in the table "accounts".
	 * 
	 * @author Tomas Dulik
	 * @param ineteger $attribute_id
	 * @param string $name
	 * @param integer $member_id
	 * @return Account_Model	new object containing the new record model
	 */
	public static function create($attribute_id, $name, $member_id)
	{
		$account = new Account_Model();
		$account->account_attribute_id = $attribute_id;
		$account->name = $name;
		$account->member_id = $member_id;
		$account->save();
		return $account;
	}

	/**
	 * Returns all accounts to deduct fee in date
	 *
	 * @author Michal Kliment
	 * @param string $date
	 * @return Mysql_Result object
	 */
	public function get_accounts_to_deduct($date)
	{
		// check
		if (!preg_match("/^\d{4}\-\d{2}\-\d{2}$/", $date))
		{
			return FALSE;
		}
		// query
		return $this->db->query("
			SELECT a.id, a.balance, m.entrance_date, m.leaving_date,
				IF(mf.fee IS NOT NULL, 1, 0) fee_is_set,
				mf.fee,
				mf.readonly AS fee_readonly,
				mf.name AS fee_name,
				IF(t.id IS NULL, 0, t.id) AS transfer_id
			FROM accounts a
			JOIN members m ON a.member_id = m.id
			LEFT JOIN enum_types e ON e.id = m.type
			LEFT JOIN
			(
				SELECT * FROM (SELECT f.fee, f.readonly, f.name, mf.member_id, priority
				FROM members_fees mf
				LEFT JOIN fees f ON mf.fee_id = f.id
				LEFT JOIN enum_types et ON f.type_id = et.id
				WHERE et.value = 'Regular member fee'
					AND mf.activation_date <= '$date'
					AND mf.deactivation_date >= '$date'
				ORDER BY member_id, priority) q
				GROUP BY q.member_id
			) mf ON m.id = mf.member_id
			LEFT JOIN transfers t ON t.origin_id = a.id AND t.type = ? AND t.datetime = '$date'
			WHERE m.id <> 1 AND m.entrance_date < '$date'
				AND (m.leaving_date = '0000-00-00'
				OR (m.leaving_date <> '0000-00-00' AND m.leaving_date > '$date'))
				AND a.account_attribute_id = ?
		", array(Transfer_Model::DEDUCT_MEMBER_FEE, Account_attribute_Model::CREDIT));
	}

	/**
	 * Function gets all members to deduct entrance fees.
	 * 
	 * @author Jiri Svitak
	 * @return Mysql_Result
	 */
	public function get_accounts_to_deduct_entrance_fees($deduct_date)
	{
		return $this->db->query("
			SELECT a.id AS id,
			IF (debt > debt_payment_rate, debt_payment_rate, debt) AS amount
			FROM
			(
				SELECT a.id,
				IFNULL(m.entrance_fee, 0) - IFNULL(SUM(t.amount),0) AS debt,
				debt_payment_rate
				FROM accounts a
				JOIN members m ON a.member_id = m.id
				LEFT JOIN transfers t ON t.origin_id = a.id AND t.type = ?
				WHERE a.account_attribute_id = ?
				AND m.entrance_fee > 0 AND m.type <> ?
				AND m.entrance_date < ? AND
				(
						m.type <> ? OR m.leaving_date > ?
				) AND a.id NOT IN
				(
						SELECT t.origin_id
						FROM transfers t
						WHERE type = ? AND datetime = ?
				)
				GROUP BY a.id
			) a
			WHERE a.debt > 0
		", array
		(
			Transfer_Model::DEDUCT_ENTRANCE_FEE,
			Account_attribute_Model::CREDIT,
			Member_Model::TYPE_APPLICANT,
			$deduct_date,
			Member_Model::TYPE_FORMER,
			$deduct_date,
			Transfer_Model::DEDUCT_ENTRANCE_FEE,
			$deduct_date
		));
	}


	/**
	 * Gets amount of deducted entrance fees of member's credit account.
	 * Used for deducting entrance fees with instalment.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 * @return integer
	 */
	public function get_amount_of_entrance_fees($account_id)
	{
		return $this->db->query("
			SELECT IFNULL(SUM(t.amount), 0) AS amount
			FROM transfers t
			WHERE t.origin_id = ? AND t.type = ?
		", array($account_id, Transfer_Model::DEDUCT_ENTRANCE_FEE))->current()->amount;
	}

	/**
	 * Gets information about one credit account, used for recounting
	 * entrance fee with or without instalment.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 * @return Mysql_Result
	 */
	function get_account_to_recalculate_entrance_fees($account_id)
	{
		return $this->db->query("
			SELECT a.id, m.entrance_fee, m.debt_payment_rate, m.entrance_date
			FROM accounts a
			JOIN members m ON a.member_id = m.id AND m.id <> 1
			WHERE a.account_attribute_id = ? AND a.id = ?
		", array(Account_attribute_Model::CREDIT, $account_id));
	}

	/**
	 * Function deletes member fee deducting transfers of given account.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 * @return integer	Count of deleted transfers
	 * @throws Exception	On error
	 */
	public function delete_deduct_transfers_of_account($account_id)
	{
		$transfers_count = 0;
		
		// validates account_id
		if (!$account_id || !is_numeric($account_id))
			return $transfers_count;
		
		$transfers = ORM::factory('transfer')->where(array
		(
			'type'		=> Transfer_Model::DEDUCT_MEMBER_FEE,
			'origin_id' => $account_id
		))->find_all();
		
		foreach ($transfers as $transfer)
		{
			$transfers_count++;
			$transfer->delete_throwable();
		}
		// recalculate balance of current credit account
		if (!$this->recalculate_account_balance_of_account($account_id))
		{
			throw new Exception();
		}
		// recalculate balance of operating account
		$operating = ORM::factory('account')->where(array
		(
			'account_attribute_id' => Account_attribute_Model::OPERATING
		))->find();
		
		if (!$this->recalculate_account_balance_of_account($operating->id))
		{
			throw new Exception();
		}
		
		return $transfers_count;
	}

	/**
	 * Function deletes entrance fee deducting transfers of given account.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 * @return integer	Count of deleted transfers
	 * @throws Exception	On error
	 */
	public function delete_entrance_deduct_transfers_of_account($account_id)
	{
		$transfers = ORM::factory('transfer')->where(array
		(
			'type'		=> Transfer_Model::DEDUCT_ENTRANCE_FEE,
			'origin_id' => $account_id
		))->find_all();
		
		$transfers_count = 0;
		foreach ($transfers as $transfer)
		{
			$transfers_count++;
			$transfer->delete_throwable();
		}
		// recalculate balance of current credit account
		if (!$this->recalculate_account_balance_of_account($account_id))
		{
			throw new Exception();
		}
		// recalculate balance of operating account
		$operating = ORM::factory('account')->where(array
		(
			'account_attribute_id' => Account_attribute_Model::INFRASTRUCTURE
		))->find();
		
		if (!$this->recalculate_account_balance_of_account($operating->id))
		{
			throw new Exception();
		}
		
		return $transfers_count;
	}
	
	/**
	 * Function deletes device fee deducting transfers of given account.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 * @return integer	Count of deleted transfers
	 * @throws Exception	On error
	 */
	public function delete_device_deduct_transfers_of_account($account_id)
	{
		$transfers_count = 0;
		
		// validates account_id
		if (!$account_id || !is_numeric($account_id))
			return $transfers_count;
		
		$transfers = ORM::factory('transfer')->where(array
		(
			'type'		=> Transfer_Model::DEDUCT_DEVICE_FEE,
			'origin_id' => $account_id
		))->find_all();
		
		foreach ($transfers as $transfer)
		{
			$transfers_count++;
			$transfer->delete_throwable();
		}
		// recalculate balance of current credit account
		if (!$this->recalculate_account_balance_of_account($account_id))
		{
			throw new Exception();
		}
		// recalculate balance of operating account
		$operating = ORM::factory('account')->where(array
		(
			'account_attribute_id' => Account_attribute_Model::OPERATING
		))->find();
		
		if (!$this->recalculate_account_balance_of_account($operating->id))
		{
			throw new Exception();
		}
		
		return $transfers_count;
	}


	/**
	 * Finds accounts of members who have at least one device to repay.
	 * 
	 * @author Jiri Svitak
	 * @return Mysql_Result
	 */
	public function get_accounts_to_deduct_device_fees($deduct_date)
	{
		return $this->db->query("
			SELECT a.id, IF (debt > payment_rate, payment_rate, debt) AS amount
			FROM
			(
				SELECT a.id, IFNULL(d.price, 0) - IFNULL(SUM(t.amount),0) AS debt,
				payment_rate
				FROM accounts a
				JOIN members m ON a.member_id = m.id
				JOIN users u ON u.member_id = m.id
				JOIN devices d ON d.user_id = u.id AND d.price IS NOT NULL AND d.price > 0
				LEFT JOIN transfers t ON t.origin_id = a.id AND t.type = ?
				WHERE a.id NOT IN
				(
					SELECT t.origin_id
					FROM transfers t
					WHERE type = ? AND datetime = ?
				)
				GROUP BY a.id
			) a
			WHERE a.debt > 0
		", array
		(
			Transfer_Model::DEDUCT_DEVICE_FEE,
			Transfer_Model::DEDUCT_DEVICE_FEE,
			$deduct_date
		));
	}

	/**
	 * Gets actually repaied amount of prices of all member's devices.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 * @return integer
	 */
	public function get_amount_of_device_fees($account_id)
	{
		return $this->db->query("
			SELECT IFNULL(SUM(t.amount), 0) AS amount
			FROM transfers t
			WHERE t.origin_id = ? AND t.type = ?
		", array($account_id, Transfer_Model::DEDUCT_DEVICE_FEE))->current()->amount;
	}

	/**
	 * Gets list of devices with repayments. Devices are ordered ascending by their buy date.
	 * 
	 * @author Jiri Svitak
	 * @param unknown_type $account_id
	 * @return Mysql_Result
	 */
	public function get_devices_of_account($account_id)
	{
		return $this->db->query("
			SELECT d.id, d.payment_rate, d.buy_date, d.price
			FROM devices d
			JOIN users u ON d.user_id = u.id
			JOIN accounts a ON a.member_id = u.member_id AND u.member_id <> 1
			WHERE a.id = ? AND d.price IS NOT NULL AND d.price > 0
			ORDER BY d.buy_date ASC
		", array($account_id));
	}


	/**
	 * Recalculates all account balances.
	 * 
	 * @author Jiri Svitak
	 * @return array[integer]
	 */
	public function recalculate_account_balances()
	{
		$accounts = $this->db->query("
			SELECT q2.*, (inbound - outbound) AS calculated_balance
			FROM
			(
				SELECT q1.*, IFNULL(SUM(amount), 0) AS inbound
				FROM
				(
					SELECT a.*, IFNULL(SUM(amount), 0) AS outbound
					FROM accounts a
					LEFT JOIN members m ON m.id = a.member_id
					LEFT JOIN transfers t1 ON a.id = t1.origin_id
					GROUP BY a.id
				) q1
				LEFT JOIN transfers t2 ON q1.id = t2.destination_id
				GROUP BY q1.id
			) q2
		");
		// create update sql query
		$sql = "UPDATE accounts SET balance = CASE id ";
		// array of ids to change
		$ids = array();
		foreach ($accounts as $account)
		{
			if ($account->balance != $account->calculated_balance)
			{
				$sql .= "WHEN $account->id THEN $account->calculated_balance ";
				$ids[] = $account->id;
			}
		}
		// are there some accounts with incorrect balances? save correct balances
		if (count($ids) > 0)
		{
			$ids_with_commas = implode(',', $ids);
			$sql .= "END WHERE id IN ($ids_with_commas)";
			Database::instance()->query($sql);
		}
		// returns array of ids of corrected accounts
		return $ids;
	}

	/**
	 * Recalculates account balance of single account.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 * @return boolean
	 */
	public function recalculate_account_balance_of_account($account_id)
	{
		$balance = $this->get_account_balance($account_id);
		
		return $this->db->query("
				UPDATE accounts
				SET balance = ?
				WHERE id = ?
		", array($balance, $account_id));
	}

	/**
	 * Get comment of this object
	 * 
	 * @return string
	 */
	public function get_comments()
	{
		$result = $this->db->query("
				SELECT GROUP_CONCAT(comment SEPARATOR ', \n\n') AS comment FROM
				(
						SELECT a.id, CONCAT(u.surname,' ',u.name,' (',SUBSTRING(c.datetime,1,10),'):\n',c.text) AS comment
						FROM accounts a
						JOIN comments c ON a.comments_thread_id = c.comments_thread_id
						JOIN users u ON c.user_id = u.id
						WHERE a.member_id = ? AND a.account_attribute_id = ?
						ORDER BY c.datetime DESC
				) AS q
				GROUP BY q.id
		", array($this->member_id, $this->account_attribute_id))->current();

		return ($result) ? $result->comment: '';
	}
	
	/**
	 * Returns account by given attribute and member
	 * 
	 * @author Michal Kliment
	 * @param integer $account_attribute_id
	 * @param integer $member_id
	 * @return Account_Model
	 */
	public function get_account_by_account_attribute_and_member ($account_attribute_id, $member_id)
	{
		return $this
			->where('account_attribute_id', $account_attribute_id)
			->where('member_id', $member_id)
			->find();
	}
	
	/**
	 * Gets account by account attribute ID.
	 * Creates new instance.
	 * 
	 * @param integer $account_attribute_id
	 * @return Account_Model
	 */
	public function get_account_by_attribute($account_attribute_id)
	{
		return ORM::factory('account')
				->where('account_attribute_id', $account_attribute_id)
				->find();
	}

    /**
     * Select list of accounts without accounts whose attribute IDs are in
     * passed argument.
     *
     * @param int|array $account_attribute_id
     */
    public function select_list_without_types($account_attribute_id)
    {
        if (!is_array($account_attribute_id))
        {
            $account_attribute_id = array($account_attribute_id);
        }

		$concat = "CONCAT(
				COALESCE(name, ''),
				' - " . __('Account ID') . " ',
				id,
                CONCAT(', ', account_attribute_id)
		)";
		$aaids = array_map('intval', $account_attribute_id);
		return $this->in('account_attribute_id', $aaids, TRUE)
				->select_list('id', $concat, 'account_attribute_id');
    }
	
}
