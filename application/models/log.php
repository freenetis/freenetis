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
 * Model of logs.
 * Table logs is handled as partitioned table, it needs at least MySQL ver. 5.1.
 * Table partitions are created by scheduler.
 * Logs are preserved for maximum of 30 days.
 *
 * @author Ondřej Fibich
 * @package Model
 * 
 * @property int $id
 * @property int $user_id
 * @property User_Model $user
 * @property int $object_id
 * @property string $table_name
 * @property string $time
 * @property string $values
 */
class Log_Model extends ORM
{
	/** Logger action for adding record to the table */
	const ACTION_ADD    = 1;
	/** Logger action for adding record to the table */
	const ACTION_DELETE = 2;
	/** Logger action for updating record to the table */
	const ACTION_UPDATE = 3;
	
	protected $belongs_to = array('user');

	/**
	 * Contruct set logger
	 * @param int $id
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		// set action logger of
		$this->set_logger(FALSE);
	}

	/**
	 * Creates table for logs
	 * @author Ondřej Fibich
	 */
	public static function create_table()
	{
		Database::instance()->query("
			CREATE TABLE IF NOT EXISTS `logs` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`table_name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
				`values` text COLLATE utf8_czech_ci,
				`time` datetime NOT NULL,
				`action` tinyint(2) NOT NULL DEFAULT '1',
				`object_id` int(11) NOT NULL,
				`user_id` int(11) NOT NULL,
				KEY `id` (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci
			PARTITION BY RANGE (TO_DAYS(`time`))
			(PARTITION p_first VALUES LESS THAN (TO_DAYS('1970-01-01')) ENGINE = InnoDB);
		");
	}

	/**
	 * Add new partition for logs
	 * @author Ondřej Fibich
	 * @see Scheduler_Controller::logs_partitions_daily()
	 */
	public function add_partition()
	{
		$partition_name = date('Y_m_d', time());
		$partition_date = date('Y-m-d', strtotime('+1 day', time()));

		$this->db->query("
			ALTER TABLE logs
			ADD PARTITION (
				PARTITION p_$partition_name
				VALUES LESS THAN (TO_DAYS('$partition_date')
			) ENGINE = InnoDB)
		");
	}

	/**
	 * Remove partitions for log which are more than 31 days old
	 * 
	 * @author Ondřej Fibich
	 * @see Scheduler_Controller::logs_partitions_daily()
	 */
	public function remove_old_partitions()
	{
		// get all old partitions
		$partitions = $this->db->query("
			SELECT PARTITION_NAME
			FROM INFORMATION_SCHEMA.PARTITIONS
			WHERE TABLE_NAME = ? and 
			  TABLE_SCHEMA = ? and
				PARTITION_NAME <> 'p_first' AND
				PARTITION_NAME < CONCAT('p_', 
					DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 31 DAY), '%Y_%m_%d'));
		", 'logs', Config::get('db_name'));
		
		foreach ($partitions as $partition)
		{
			$this->db->query("
				ALTER TABLE logs
				DROP PARTITION " . $partition->PARTITION_NAME
			);
		}
	}

	/**
	 * Gets all users logs with limit
	 * 
	 * @param int $offset
	 * @param int $limit
	 * @param int $user_id
	 * @return Mysql_Result
	 */
	public function get_all_users_logs($user_id, $offset, $limit)
	{
		return $this->db->query("
			SELECT l.*
			FROM logs l
			WHERE user_id = ?
			ORDER BY id DESC
			LIMIT " .intval($offset). ", " .intval($limit). "
		", $user_id);
	}

	/**
	 * Gets all objects logs with limit
	 * 
	 * @param string $table_name
	 * @param int $object_id
	 * @param int $offset
	 * @param int $limit
	 * @return Mysql_Result
	 */
	public function get_all_object_logs($table_name, $object_id, $offset, $limit)
	{
		return $this->db->query("
			SELECT l.*
			FROM logs l
			WHERE object_id = ? AND table_name = ?
			ORDER BY id DESC
			LIMIT " .intval($offset). ", " .intval($limit). "
		", $object_id, $table_name);
	}

	/**
	 * Gets all logs with limit
	 * 
	 * @param int $offset
	 * @param int $limit
	 * @param array $filter_sql  Filter for where contition
	 * @return Mysql_Result
	 */
	public function get_all_logs($offset, $limit, $filter_sql = '')
	{
		// fill where contition
		$where = !empty($filter_sql) ? 'WHERE ' . $filter_sql : '';

		// query
		return $this->db->query("
			SELECT * FROM (
				SELECT logs.*, u.name AS user_name, u.surname AS user_surname,
					u.login AS user_login, m.name AS member_name
				FROM logs
				LEFT JOIN users u ON u.id = logs.user_id
				LEFT JOIN members m ON m.id = u.member_id
			) l	$where
			ORDER BY id DESC
			LIMIT " .intval($offset). ", " .intval($limit). "
		");
	}

	/**
	 * Gets number of logs
	 * 
	 * @param array $filter_sql  Filter for where contition
	 * @return int
	 */
	public function count_all_logs($filter_sql = array())
	{
		// fill where contition
		$where = !empty($filter_sql) ? 'WHERE ' . $filter_sql : '';

		return $this->db->query("
			SELECT COUNT(*) AS count FROM (
				SELECT logs.*, u.name AS user_name, u.surname AS user_surname,
					u.login AS user_login, m.name AS member_name
				FROM logs
				LEFT JOIN users u ON u.id = logs.user_id
				LEFT JOIN members m ON m.id = u.member_id
			) l $where
		")->current()->count;
	}

	/**
	 * Gets number of users logs
	 * 
	 * @param int $user_id
	 * @return int
	 */
	public function count_all_users_logs($user_id)
	{
		return $this->db->query("
			SELECT COUNT(*) AS count FROM logs WHERE user_id = ?
		", $user_id)->current()->count;
	}

	/**
	 * Gets number of object logs
	 * 
	 * @param string $table_name
	 * @param int $object_id
	 * @return int
	 */
	public function count_all_object_logs($table_name, $object_id)
	{
		return $this->db->query("
			SELECT COUNT(*) AS count FROM logs WHERE table_name = ? AND object_id = ?
		", $table_name, $object_id)->current()->count;
	}

	/**
	 * Gets last modification time of object
	 * 
	 * @param string $table_name
	 * @param int $object_id
	 * @return string
	 */
	public function get_object_last_modification($table_name, $object_id)
	{
		$result = $this->db->query("
			SELECT time FROM logs WHERE table_name = ? AND object_id = ? ORDER BY time DESC
			", $table_name, $object_id);
		
		if ($result && $result->current())
		{
			return $result->current()->time;
		}
	}
}
