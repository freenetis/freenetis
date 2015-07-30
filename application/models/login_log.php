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
 * Login logs.
 * 
 * @author Michal Kliment
 * @package Model
 * 
 * @property int $id
 * @property int $user_id
 * @property User_Model $user
 * @property datetime $time
 * @property string $IP_address
 */
class Login_log_Model extends ORM
{

	protected $belongs_to = array('user');

	/**
	 * Contruct of app, shutdown action logs by default
	 * @param type $id 
	 */
	public function __construct($id = false)
	{
		parent::__construct($id);

		// turn off action log
		$this->set_logger(FALSE);
	}

	/**
	 * Returns last login of all user
	 * 
	 * @author Michal Kliment
	 * @param int $sql_offset
	 * @param int $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql
	 * @return Mysql_Result
	 */
	public function get_all_login_logs($sql_offset = 0, $limit_results = 500,
			$order_by = 'last_time', $order_by_direction = 'DESC', $filter_sql = '')
	{
		$where = '';
		
		if (!empty($filter_sql))
		{
			$where = 'WHERE ' . $filter_sql;
		}
		
		if (strtolower($order_by_direction) != 'asc')
		{
			$order_by_direction = 'desc';
		}
		
		if (!in_array($order_by, array('id', 'name', 'last_time')))
		{
			$order_by = 'last_time';
		}
		
		if ($order_by == 'last_time')
		{
			$order_by = "IF(last_time IS NULL, 0, 1) $order_by_direction, last_time";
		}
		
		return $this->db->query("
				SELECT id, name, IFNULL(last_time,?) as last_time
				FROM (
					SELECT *
					FROM (
						SELECT u.id, CONCAT(u.name, ' ', u.surname) AS name,
							l.time AS last_time
						FROM users u
						LEFT JOIN login_logs l ON u.id = l.user_id
						ORDER BY time DESC
					) AS q1
					$where
					GROUP BY q1.id
				) AS q3
				ORDER BY $order_by $order_by_direction
				LIMIT " . intval($sql_offset) . ", " . intval($limit_results) . "
		", __('Never'));
	}

	/**
	 * Returns count of last login of all user
	 * 
	 * @param string $filter_sql
	 * @author Michal Kliment
	 * @return integer
	 */
	public function count_all_login_logs($filter_sql)
	{
		
		$where = '';
		
		if (!empty($filter_sql))
		{
			$where = 'WHERE ' . $filter_sql;
		}
		else
		{
			return ORM::factory('user')->count_all();
		}
		
		return $this->db->query("
				SELECT COUNT(*) AS total
				FROM (
					SELECT *
					FROM (
						SELECT u.id, CONCAT(u.name, ' ', u.surname) AS name,
							l.time AS last_time
						FROM users u
						LEFT JOIN login_logs l ON u.id = l.user_id
						ORDER BY time DESC
					) AS q1
					$where
					GROUP BY q1.id
				) AS q3
		")->current()->total;
	}

	/**
	 * Gets all logins by user
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @param int $sql_offset
	 * @param int $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql
	 * @return Mysql_Result
	 */
	public function get_all_login_logs_by_user($user_id = 0, $sql_offset = 0,
			$limit_results = 500, $order_by = 'time', $order_by_direction = 'desc',
			$filter_sql = '')
	{
		// order by check
		if (!$this->has_column($order_by))
		{
			$order_by = 'time';
		}
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// where
		$where = '';
		
		if (!empty($filter_sql))
		{
			$where = 'AND ' . $filter_sql;
		}
		// query
		return $this->db->query("
				SELECT id, time, ip_address
				FROM login_logs
				WHERE user_id = ? $where
				ORDER BY $order_by $order_by_direction
				LIMIT " . intval($sql_offset) . ", " . intval($limit_results) . "
		", $user_id);
	}

	/**
	 * Returns count of logins by user
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @param string $filter_sql
	 * @return integer
	 */
	public function count_all_login_logs_by_user($user_id = 0, $filter_sql = '')
	{
		// where
		$where = '';
		
		if (!empty($filter_sql))
		{
			$where = 'AND ' . $filter_sql;
		}
		// query
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM login_logs
				WHERE user_id = ? $where
		", $user_id)->current()->count;
	}

}
