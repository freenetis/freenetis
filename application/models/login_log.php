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
	 * @return Mysql_Result
	 */
	public function get_all_login_logs($sql_offset = 0, $limit_results = 500)
	{
		return $this->db->query('
				SELECT id, name, IFNULL(last_time,?) as last_time
				FROM (
					SELECT *
					FROM (
						SELECT u.id, CONCAT(u.name,\' \',u.surname) AS name,
							l.time AS last_time
						FROM users u
						LEFT JOIN login_logs l ON u.id = l.user_id
						ORDER BY time DESC
					) AS q1
					GROUP BY q1.id
				) AS q3
				ORDER BY q3.last_time DESC
				LIMIT ' . intval($sql_offset) . ', ' . intval($limit_results) . '
		', url_lang::lang('texts.Never'));
	}

	/**
	 * Returns last login of all user
	 * 
	 * @author Michal Kliment
	 * @return integer
	 */
	public function count_all_login_logs()
	{
		$user = new User_Model();
		return $user->count_all();
	}

	/**
	 * Gets all logins by user
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_all_login_logs_by_user($user_id = 0, $sql_offset = 0,
			$limit_results = 500, $order_by = 'time', $order_by_direction = 'desc')
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
		// query
		return $this->db->query("
				SELECT id, time, ip_address
				FROM login_logs
				WHERE user_id = ?
				ORDER BY $order_by $order_by_direction
				LIMIT " . intval($sql_offset) . ", " . intval($limit_results) . "
		", $user_id);
	}

	/**
	 * Returns count of logins by user
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @return integer
	 */
	public function count_all_login_logs_by_user($user_id = 0)
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM login_logs
				WHERE user_id = ?
		", $user_id)->current()->count;
	}

}
