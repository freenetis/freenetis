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
 * Specified account
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $name
 * @property integer $type
 * @property integer $kind
 * @property integer $line
 * @property integer $line_short
 * @property integer $line_credits
 * @property integer $pasiv
 * @property integer $line_credits_short
 * @property string $comment
 * @property Account_Model $account
 */
class Account_attribute_Model extends ORM
{
	protected $has_one = array('account');
	
	// Definition of double entry accounts numbers used in various Freenetis controllers and models.
	// These numbers are also valid values of the account_attribute primary key!
	const CASH						= '211000'; // created in installation
	const BANK						= '221000'; // created in installation
	const CREDIT					= '221100';
	const OPERATING					= '221101'; // created in installation
	const INFRASTRUCTURE			= '221102'; // created in installation
	const PROJECT					= '221103';
	const BANK_DEBTS				= '231000';
	const PURCHASERS				= '311000'; // created in installation
	const SUPPLIERS					= '321000'; // created in installation
	const BANK_FEES					= '549001'; // created in installation
	const BANK_INTERESTS			= '644000'; // created in installation
	const MEMBER_FEES				= '684000'; // created in installation
	const TIME_DEPOSITS_INTERESTS	= '655000'; 
	const TIME_DEPOSITS				= '259000';

	/**
	 * Accounting system requires special query.
	 * For every account type it counts balance. For example credit accounts 221100 is one account
	 * representing money on all credit accounts in system.
	 * 
	 * @return Mysql_Result
	 */
	public function get_accounting_system(
			$limit_from = 0, $limit_results = 20,
			$order_by = 'id', $order_by_direction = 'asc',
			$filter_sql = '', $date_sql = '')
	{
		$where = '';
		$datetime = '';
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
		if (!empty($date_sql))
		{
			$datetime = "AND $date_sql";
		}
		
		// query
		return $this->db->query("
				SELECT aa.id, aa.name, SUM(partial_balance) AS balance
                FROM account_attributes aa
                JOIN
				(
					SELECT q2.account_attribute_id, (inbound - outbound) AS partial_balance
					FROM
					(
						SELECT q1.*, IFNULL(SUM(amount), 0) AS inbound
						FROM
						(
							SELECT q0.*, IFNULL(SUM(amount), 0) AS outbound
							FROM
							(
								SELECT a.id, a.account_attribute_id
								FROM accounts a	
							) q0
							LEFT JOIN transfers t1 ON q0.id = t1.origin_id $datetime
							GROUP BY q0.id
						) q1
						LEFT JOIN transfers t2 ON q1.id = t2.destination_id $datetime
						GROUP BY q1.id
					) q2
				) q3 ON aa.id = q3.account_attribute_id
				$where
				GROUP BY q3.account_attribute_id
				ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		");		
	}
	
	/**
	 * Gets accounting system count
	 * 
	 * @param integer $filter_values
	 * @return integer 
	 */
	public function get_accounting_system_count($filter_sql = '')
	{
		$where = '';
		// filter
		if (!empty($filter_sql))
		{
			$where = "AND $filter_sql";
		}
		// query
		return $this->db->query("
				SELECT COUNT(*) AS total
				FROM
					(SELECT a.account_attribute_id
					FROM account_attributes aa
					JOIN accounts a ON aa.id = a.account_attribute_id
					WHERE a.member_id = 1 $where
					GROUP BY a.account_attribute_id
				) q1
		")->current()->total;
	}
	
	/**
	 * Gets account attributes.
	 * Returned are only that attributes which have at least one account.
	 * 
	 * @author Jiri Svitak
	 * @return Mysql_Result
	 */
	public function get_account_attributes()
	{
		return $this->db->query("
				SELECT aa.id, aa.name
				FROM account_attributes aa
				JOIN accounts a ON a.account_attribute_id = aa.id
				GROUP BY aa.id
		");
	}
		
}
