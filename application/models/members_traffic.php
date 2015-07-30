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
 * Member's traffic (daily, monthly, yearly)
 * 
 * @author Ondřej Fibich
 * @package Model
 */
class Members_traffic_Model extends Model
{
	/**
	 * Creates tables (daily, monthly, yearly) with parttitons for current date/month
	 * 
	 * @param boolean $create_first_partition enable creating of first partitions?
	 */ 
	public static function create_tables($create_first_partition = FALSE)
	{
		$db = Database::instance();
		
		$first_partition_daily = '';
		$first_partition_monthly = '';
		
		if ($create_first_partition === TRUE)
		{
			// next month
			$i = strtotime($db->query("
				SELECT DATE_ADD('" . date('Y-m-d', time()) . "', INTERVAL 1 MONTH) AS t
			")->current()->t);
		
			// sql
			$first_partition_daily = ", PARTITION p_" . date('Y_m_d')
					. " VALUES LESS THAN (TO_DAYS('"
					. date('Y-m-d', time() + 86400) . "')) ENGINE = InnoDB";
			
			$first_partition_monthly = ", PARTITION p_" . date('Y_m_01')
					. " VALUES LESS THAN (TO_DAYS('"
					. date('Y-m-01', $i) . "')) ENGINE = InnoDB";
		}
		
		// daily
		$db->query("CREATE TABLE IF NOT EXISTS `members_traffics_daily` (
			  `member_id` int(11) NOT NULL,
			  `upload` bigint unsigned NOT NULL,
			  `download` bigint unsigned NOT NULL,
			  `local_upload` bigint unsigned NOT NULL,
			  `local_download` bigint unsigned NOT NULL,
			  `active` tinyint(1) NOT NULL DEFAULT '0',
			  `date` date NOT NULL,
			  PRIMARY KEY (`member_id`,`date`),
			  KEY `member_id` (`member_id`),
			  KEY `date` (`date`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci
			PARTITION BY RANGE (TO_DAYS(`date`))
			(PARTITION p_first VALUES LESS THAN (TO_DAYS('1970-01-01')) ENGINE = InnoDB
			 $first_partition_daily);
		");
	
		// monthly
		$db->query("CREATE TABLE IF NOT EXISTS `members_traffics_monthly` (
			  `member_id` int(11) NOT NULL,
			  `upload` bigint unsigned NOT NULL,
			  `download` bigint unsigned NOT NULL,
			  `local_upload` bigint unsigned NOT NULL,
			  `local_download` bigint unsigned NOT NULL,
			  `date` date NOT NULL,
			  PRIMARY KEY (`member_id`,`date`),
			  KEY `member_id` (`member_id`),
			  KEY `date` (`date`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci
			PARTITION BY RANGE (TO_DAYS(`date`))
			(PARTITION p_first VALUES LESS THAN (TO_DAYS('1970-01-01')) ENGINE = InnoDB
			 $first_partition_monthly);
		");
	
		// yearly
		$db->query("CREATE TABLE IF NOT EXISTS `members_traffics_yearly` (
			  `member_id` int(11) NOT NULL,
			  `upload` bigint unsigned NOT NULL,
			  `download` bigint unsigned NOT NULL,
			  `local_upload` bigint unsigned NOT NULL,
			  `local_download` bigint unsigned NOT NULL,
			  `date` date NOT NULL,
			  PRIMARY KEY (`member_id`,`date`),
			  KEY `member_id` (`member_id`),
			  KEY `date` (`date`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
		");
	}
	
	/**
	 * Destroys tables
	 */
	public static function destroy_tables()
	{
		$db = Database::instance();
		
		$db->query("DROP TABLE IF EXISTS `members_traffics_daily`");
		$db->query("DROP TABLE IF EXISTS `members_traffics_monthly`");
		$db->query("DROP TABLE IF EXISTS `members_traffics_yearly`");
	}

	/**
	 * Add new partition for members_traffics_daily
	 * 
	 * @author Ondřej Fibich
	 */
	public function add_daily_partition()
	{
		$partition_name = date('Y_m_d', time());
		$partition_date = date('Y-m-d', strtotime('+1 day', time()));

		$this->db->query("
			ALTER TABLE members_traffics_daily
			ADD PARTITION (
				PARTITION p_$partition_name
				VALUES LESS THAN (TO_DAYS('$partition_date')
			) ENGINE = InnoDB)
		");
	}

	/**
	 * Add new partition for members_traffics_monthly
	 * 
	 * @author Ondřej Fibich
	 */
	public function add_monthly_partition()
	{
		$partition_name = date('Y_m_01', time());
		$next_month = ((date('m') == 12) ? 1 : date('m') + 1);
		$partition_date = date('Y-') . ($next_month < 10 ? '0' : '') . $next_month . '-01';

		$this->db->query("
			ALTER TABLE members_traffics_monthly
			ADD PARTITION (
				PARTITION p_$partition_name
				VALUES LESS THAN (TO_DAYS('$partition_date')
			) ENGINE = InnoDB)
		");
	}

	/**
	 * Remove partitions for members_traffics_daily which are more than 2 month old
	 * 
	 * @author Ondřej Fibich
	 */
	public function remove_daily_old_partitions()
	{
		// get all old partitions
		$partitions = $this->db->query("
			SELECT DISTINCT PARTITION_NAME AS partition_name
			FROM INFORMATION_SCHEMA.PARTITIONS
			WHERE TABLE_NAME = 'members_traffics_daily' AND
				TABLE_SCHEMA = ? AND
				PARTITION_NAME <> 'p_first' AND
				STR_TO_DATE(PARTITION_NAME, 'p_%Y_%m_%d') < 
					DATE_SUB(NOW(), INTERVAL 2 MONTH)
			ORDER BY PARTITION_NAME
		", Config::get('db_name'));
		
		foreach ($partitions as $partition)
		{
			$this->db->query("
				ALTER TABLE members_traffics_daily
				DROP PARTITION " . $partition->partition_name
			);
		}
	}

	/**
	 * Remove partitions for members_traffics_monthly which are more than 2 years old
	 * 
	 * @author Ondřej Fibich
	 */
	public function remove_monthly_old_partitions()
	{
		// get all old partitions
		$partitions = $this->db->query("
			SELECT DISTINCT PARTITION_NAME AS partition_name
			FROM INFORMATION_SCHEMA.PARTITIONS
			WHERE TABLE_NAME = 'members_traffics_monthly' AND
				TABLE_SCHEMA = ? AND
				PARTITION_NAME <> 'p_first' AND
				STR_TO_DATE(PARTITION_NAME, 'p_%Y_%m_%d') < 
					DATE_SUB(NOW(), INTERVAL 2 YEAR)
			ORDER BY PARTITION_NAME
		", Config::get('db_name'));
		
		foreach ($partitions as $partition)
		{
			$this->db->query("
				ALTER TABLE members_traffics_monthly
				DROP PARTITION " . $partition->partition_name
			);
		}
	}
	
	
	/**
	 * Returns total traffics of member
	 *
	 * @author Michal Kliment
	 * @param int $member_id
	 * @return MySQL_Result object
	 */
	public function get_total_member_traffic($member_id)
	{
		$result = $this->db->query("
				SELECT
					IFNULL(SUM(upload),0) AS upload,
					IFNULL(SUM(download),0) AS download
				FROM members_traffics_yearly d
				WHERE d.member_id = ?
		", $member_id);
		
		return ($result && $result->count()) ? $result->current() : null;
	}
	
	/**
	 * Returns today traffics of member
	 *
	 * @param integer $member_id
	 * @return MySQL_Result
	 */
	public function get_today_member_traffic($member_id)
	{
		return $this->db->query("
			SELECT
				IFNULL(SUM(upload),0) AS upload,
				IFNULL(SUM(download),0) AS download
				FROM members_traffics_daily d
			WHERE d.member_id = ? AND date = CURDATE()
		", array($member_id))->current();
	}
	
	/**
	 * Returns month traffics of member
	 *
	 * @param integer $member_id
	 * @return MySQL_Result
	 */
	public function get_month_member_traffic($member_id)
	{
		return $this->db->query("
			SELECT
				IFNULL(SUM(upload),0) AS upload,
				IFNULL(SUM(download),0) AS download
			FROM members_traffics_monthly d
			WHERE d.member_id = ? AND YEAR(date) = ? AND MONTH(date) = ?
		", array($member_id, date('Y'), date('m')))->current();
	}
	
	/**
	 * Returns total traffics
	 * 
	 * @author Michal Kliment
	 * @param string $type
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql
	 * @return MySQL_Iterator object
	 */
	public function get_total_traffics(
			$type, $limit_from = 0, $limit_results = 50,
			$order_by = NULL, $order_by_direction = 'ASC', $filter_sql = '')
	{
		// check type and select group by
		$types = array
		(
			'daily', 'monthly', 'yearly'
		);
		
		$group_by = array
		(
			'daily' => 'TO_DAYS(date)',
			'monthly' => 'YEAR(date), MONTH(date)',
			'yearly' => 'YEAR(date)'
		);
		
		if (!in_array($type, $types))
		{
			return NULL;
		}
		
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		// having
		$where = '';
		
		if ($filter_sql != '')
		{
			$where .= 'WHERE '.$filter_sql;
		}
		
		// limit
		$limit = '';
		
		if ($limit_results > 0)
		{
			$limit = "LIMIT " . intval($limit_from) . ", " . intval($limit_results);
		}
		
		// order
		$order = '';
		
		if ($order_by != '')
		{
			$order = "ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction";
		}
		
		$data = $this->db->query("
			SELECT * FROM
			(
				SELECT
					member_id,
					IFNULL(SUM(upload),0) AS upload,
					IFNULL(SUM(download),0) AS download,
					IFNULL(SUM(local_upload),0) AS local_upload,
					IFNULL(SUM(local_download),0) AS local_download,
					IFNULL(SUM(upload - local_upload), 0) AS foreign_upload,
					IFNULL(SUM(download - local_download), 0) AS foreign_download,
					IFNULL(AVG(upload),0) AS avg_upload,
					IFNULL(AVG(download),0) AS avg_download,
					date,
					date AS day,
					WEEK(date) AS week,
					MONTH(date) AS month,
					YEAR(date) AS year
				FROM members_traffics_$type d
				GROUP BY $group_by[$type]
			) d
			$where
			$order
			$limit
		");
		
		$result = array();
		
		foreach ($data as $row)
		{
			$result[$row->date] = $row;
		}
		
		return $result;
	}
	
	/**
	 * Returns traffics of all members
	 * 
	 * @author Michal Kliment
	 * @param string $type
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql
	 * @return MySQL_Iterator object 
	 */
	public function get_all_members_traffics(
			$type, $limit_from = 0, $limit_results = 50,
			$order_by = NULL, $order_by_direction = 'ASC', $filter_sql = '')
	{
		// check type
		$types = array('daily', 'monthly', 'yearly');
		
		if (!in_array($type, $types))
		{
			return NULL;
		}
		
		// active?
		$sel_active = ($type == $types[0]) ? ',active' : '';
		
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		// having
		$where = '';
		
		if ($filter_sql != '')
		{
			$where .= 'WHERE '.$filter_sql;
		}
		
		// limit
		$limit = '';
		
		if ($limit_results > 0)
		{
			$limit = "LIMIT " . intval($limit_from) . ", " . intval($limit_results);
		}
		
		// order
		$order = '';
		
		if (!empty($order_by))
		{
			$active = Settings::get('ulogd_active_type');
			
			if ($order_by == 'month')
			{
				$date_fields = "year $order_by_direction, month $order_by_direction";
			}
			else
			{
				$date_fields = $this->db->escape_column($order_by) . ' ' . $order_by_direction;
			}
			
			$order = "ORDER BY $date_fields" . (($active) ? ", $active DESC" : "");
		}
		
		return $this->db->query("
			SELECT *, m.id AS member_id, m.name AS member_name FROM
			(
				SELECT
					member_id, upload, download, local_upload, local_download,
					(upload - local_upload) AS foreign_upload,
					(download - local_download) AS foreign_download,
					(upload + download) AS total,
					date,
					date AS day,
					WEEK(date) AS week,
					MONTH(date) AS month,
					YEAR(date) AS year
					$sel_active
				FROM members_traffics_$type d
			) d
			JOIN members m ON d.member_id = m.id
			$where
			$order
			$limit
		");
	}
	
	/**
	 * Returns all traffics of member
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @author Michal Kliment
	 * @param type $member_id
	 * @param type $type
	 * @param type $limit_from
	 * @param type $limit_results
	 * @param type $order_by
	 * @param type $order_by_direction
	 * @param type $filter_sql
	 * @return type 
	 */
	public function get_member_traffics(
			$member_id, $type, $order_by = NULL, $filter_sql = '')
	{
		// check type and select group by
		$types = array
		(
			'daily', 'monthly', 'yearly'
		);
		
		$group_by = array
		(
			'daily' => 'TO_DAYS(date)',
			'monthly' => 'YEAR(date), MONTH(date)',
			'yearly' => 'YEAR(date)'
		);
		
		if (!in_array($type, $types))
		{
			return NULL;
		}
		
		// active?
		$sel_active = ($type == $types[0]) ? ',active' : '';
		
		// having
		$where = '';
		
		if ($filter_sql != '')
		{
			$where .= 'WHERE '.$filter_sql;
		}
		
		// order
		$order = '';
		
		if ($order_by != '')
		{
			$order = "ORDER BY " . $this->db->escape_column($order_by);
		}
		
		$traffics = $this->db->query("
			SELECT * FROM
			(
				SELECT
					member_id,
					IFNULL(SUM(upload),0) AS upload,
					IFNULL(SUM(download),0) AS download,
					IFNULL(SUM(local_upload),0) AS local_upload,
					IFNULL(SUM(local_download),0) AS local_download,
					IFNULL(SUM(upload - local_upload), 0) AS foreign_upload,
					IFNULL(SUM(download - local_download), 0) AS foreign_download,
					IFNULL(AVG(upload),0) AS avg_upload,
					IFNULL(AVG(download),0) AS avg_download,
					date,
					date AS day,
					WEEK(date) AS week,
					MONTH(date) AS month,
					YEAR(date) AS year
					$sel_active
				FROM members_traffics_$type d
				WHERE d.member_id = ?
				GROUP BY $group_by[$type]
			) d
			$where
		", array($member_id));
		
		$arr_traffics = array();
		
		foreach ($traffics as $traffic)
		{
			switch ($type)
			{
				case 'daily':
					$arr_traffics[$traffic->date] = $traffic;
					break;
				case 'monthly':
					$arr_traffics[substr($traffic->date, 0, 7)] = $traffic;
					break;
				case 'yearly':
					$arr_traffics[$traffic->year] = $traffic;
					break;
			}
		}
		
		return $arr_traffics;
	}
	
	/**
	 * Count total traffics
	 * 
	 * @author Michal Kliment
	 * @param string $type
	 * @param string $filter_sql
	 * @return integer
	 */
	public function count_total_traffics($type, $filter_sql = '')
	{
		// check type and select group by
		$types = array
		(
			'daily', 'monthly', 'yearly'
		);
		
		$group_by = array
		(
			'daily' => 'TO_DAYS(date)',
			'monthly' => 'YEAR(date), MONTH(date)',
			'yearly' => 'YEAR(date)'
		);
		
		if (!in_array($type, $types))
		{
			return NULL;
		}
		
		// having
		$where = '';
		
		if ($filter_sql != '')
		{
			$where .= 'WHERE '.$filter_sql;
		}
		
		return $this->db->query("
			SELECT COUNT(*) AS count FROM
			(
				SELECT
					member_id,
					IFNULL(SUM(upload),0) AS upload,
					IFNULL(SUM(download),0) AS download,
					IFNULL(SUM(upload),0) + IFNULL(SUM(download),0) AS total,
					IFNULL(AVG(upload),0) AS avg_upload,
					IFNULL(AVG(download),0) AS avg_download,
					IFNULL(SUM(local_upload),0) AS local_upload,
					IFNULL(SUM(local_download),0) AS local_download,
					SUM(upload - local_upload) AS foreign_upload,
					SUM(download - local_download) AS foreign_download,
					date,
					date AS day,
					WEEK(date) AS week,
					MONTH(date) AS month,
					YEAR(date) AS year
				FROM members_traffics_$type d
				GROUP BY $group_by[$type]
			) d
			$where
		")->current()->count;
	}
	
	/**
	 * Counts traffics of all members
	 * 
	 * @author Michal kliment
	 * @param string $type
	 * @param string $filter_sql
	 * @return integer 
	 */
	public function count_all_members_traffics($type, $filter_sql = '')
	{
		// check type
		$types = array
		(
			'daily', 'monthly', 'yearly'
		);
		
		$group_by = array
		(
			'daily' => 'TO_DAYS(date)',
			'monthly' => 'YEAR(date), MONTH(date)',
			'yearly' => 'YEAR(date)'
		);
		
		if (!in_array($type, $types))
		{
			return NULL;
		}
		
		$where = '';
		
		if ($filter_sql != '')
		{
			$where .= 'WHERE '.$filter_sql;
		}
		
		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM
			(
				SELECT
					member_id, upload, download, local_upload, local_download,
					(upload - local_upload) AS foreign_upload,
					(download - local_download) AS foreign_download,
					date,
					date AS day,
					WEEK(date) AS week,
					MONTH(date) AS month,
					YEAR(date) AS year
				FROM members_traffics_$type d
			) d
			$where
		")->current()->total;
	}
	
	/**
	 * Averages total traffics
	 * 
	 * @author Michal Kliment
	 * @param string $type
	 * @return MySQL_Result object 
	 */
	public function avg_total_traffics($type)
	{
		// check type
		$types = array('daily', 'monthly', 'yearly');
		
		if (!in_array($type, $types))
		{
			$type = 'daily';
		}
		
		$result = $this->db->query("
			SELECT
				IFNULL(AVG(upload), 0) as upload,
				IFNULL(AVG(download), 0) AS download,
				IFNULL(AVG(upload+download), 0) AS total
			FROM members_traffics_$type d
		");
		
		return ($result && $result->current()) ? $result->current() : NULL;
	}
	
	/**
	 * Counts avarage of member's traffics
	 * 
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @param string $type
	 * @return Database_Result
	 */
	public function avg_member_traffics($member_id, $type)
	{
		// check type
		$types = array('daily', 'monthly', 'yearly');
		
		if (!in_array($type, $types))
		{
			$type = 'daily';
		}
		
		$result = $this->db->query("
			SELECT
				IFNULL(AVG(upload), 0) as upload,
				IFNULL(AVG(download), 0) AS download,
				IFNULL(AVG(upload+download), 0) AS total
			FROM members_traffics_$type d
			WHERE d.member_id = ?
		", array($member_id));
		
		return ($result && $result->current()) ? $result->current() : NULL;
	}
	
	/**
	 * Average daily traffic of all members
	 * 
	 * @author Michal Kliment
	 * @param type $day
	 * @param type $type
	 * @return null 
	 */
	public function avg_daily_traffics($day = NULL, $type = NULL)
	{
		$result = $this->db->query("
			SELECT
				IFNULL(AVG(upload), 0) AS upload,
				IFNULL(AVG(download), 0) AS download,
				IFNULL(AVG(upload+download), 0) AS total
			FROM members_traffics_daily
			WHERE `date` = ?
		", $day);
		
		if ($result && ($result = $result->current()))
		{
			return ($type && property_exists($result, $type)) ? $result->$type : $result;
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Update active members
	 * 
	 * @author Michal Kliment
	 * @param double $avg
	 * @param integer $limit
	 * @param string $type
	 * @param string $day
	 * @return boolean 
	 */
	public function update_active_members($avg, $limit, $type, $day)
	{
		if ($type == 'total')
		{
			$type = '(upload+download)';
		}
		else if ($type == 'upload')
		{
			$type = 'upload';
		}
		else
		{
			$type = 'download';
		}
		
		$this->db->query("
			UPDATE members_traffics_daily
			SET active = IF($type >= ?, 1, 0)
			WHERE date = ? AND member_id IN
			(
				SELECT id
				FROM members
				WHERE speed_class_id IS NULL
			)
			ORDER BY $type DESC
			LIMIT ?
		", array($avg, $day, $limit));
			
		return true;
	}
}
