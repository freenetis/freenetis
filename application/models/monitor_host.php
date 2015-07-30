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
 * Monitored hosts
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $device_id
 * @property Device_Model $device
 * @property integer $state
 * @property integer $state_changed
 * @property string $state_changed_date
 * @property string $last_attempt_date
 * @property string $last_notification_date
 * @property float $latency_current
 * @property float $latency_min
 * @property float $latency_max
 * @property float $latency_avg
 * @property integer $polls_total
 * @property integer $polls_failed
 * @property float $availability
 * @property integer $priority 
 */
class Monitor_host_Model extends ORM
{
	protected $belongs_to = array('device');
	
	/**
	 * State of host is unknown 
	 */
	const STATE_UNKNOWN = 0;
	
	/**
	 * State of host is up
	 */
	const STATE_UP = 1;
	
	/**
	 * State of host is down 
	 */
	const STATE_DOWN = 2;
	
	/**
	 * States which returns fping
	 * 
	 * @var array 
	 */
	private static $states = array
	(
		self::STATE_UP		=> 'alive',
		self::STATE_DOWN	=> 'unreachable'
	);
	
	/**
	 * Human format of states
	 * 
	 * @var array 
	 */
	private static $labels = array
	(
		self::STATE_UP		=> 'online',
		self::STATE_DOWN	=> 'offline',
		self::STATE_UNKNOWN	=> 'unknown'
	);
	
	/**
	 * Color format of states
	 * 
	 * @var array 
	 */
	private static $colors = array
	(
		self::STATE_UP		=> 'green',
		self::STATE_DOWN	=> 'red',
		self::STATE_UNKNOWN	=> 'red'
	);
	
	/**
	 * Return state as number from string format
	 * 
	 * @author Michal Kliment
	 * @param type $text
	 * @return type 
	 */
	public static function get_state($text)
	{
		if (($key = arr::search($text, self::$states)) !== FALSE)
			return $key;
		else
			return self::STATE_UNKNOWN;
	}
	
	/**
	 * Return state as label from number format
	 * 
	 * @author Michal Kliment
	 * @param type $state
	 * @return boolean 
	 */
	public static function get_label($state)
	{
		if (isset(self::$labels[$state]))
			return __(self::$labels[$state]);
		else
			return FALSE;
	}
	
	/**
	 * Return state as color from number format
	 * 
	 * @author Michal Kliment
	 * @param type $state
	 * @return boolean 
	 */
	public static function get_color($state)
	{
		if (isset(self::$colors[$state]))
			return __(self::$colors[$state]);
		else
			return FALSE;
	}
	
	/**
	 * Return all monitored hosts
	 * 
	 * @author Michal Kliment
	 * @param type $priority
	 * @param string $order_by
	 * @param type $filter_sql
	 * @return type 
	 */
	public function get_all_monitored_hosts(
		$priority = NULL, $order_by = NULL, $filter_sql = '')
	{
		$where = '';
		
		// return only hosts with given priority
		if ($priority)
			$where = 'WHERE priority = '.intval($priority);
		
		// filter
		if ($filter_sql != '')
			$where = ($where != '') ? $where.' AND '. $filter_sql : 'WHERE '.$filter_sql;
		
		// default order by host's ip address
		if (!$order_by)
			$order_by = 'INET_ATON(ip_address)';
		
		return $this->db->query("
			SELECT mh.*
			FROM
			(
			SELECT
				mh.*, d.name AS device_name,
				ip_address, ip.id AS ip_address_id, service,
				CONCAT(
						IF(
							ap.name IS NOT NULL AND ap.name <> '', CONCAT(ap.name,' ('), ''
						),
						IF(
							s.street IS NOT NULL, CONCAT(s.street, ' '), ''
						),
						IF(
							ap.street_number IS NOT NULL, CONCAT(ap.street_number,', '), ''
						),
						t.town,
						IF(
							t.quarter IS NOT NULL, CONCAT('-',t.quarter), ''
						),
						', ',
						t.zip_code,
						IF(
							ap.gps IS NOT NULL AND ap.gps <>'', CONCAT(', GPS ', X(ap.gps),'″N ',Y(ap.gps),'″E'), ''
						),
						IF(
							ap.name IS NOT NULL AND ap.name <> '', ')', ''
						)
				) AS address_point_name, ap.town_id,
				CONCAT(
					t.town,
					IF(
						t.quarter <> '', CONCAT('-',t.quarter), ''
					),
					', ',
					t.zip_code
				) AS town_name,town,street,d.type,
				IFNULL(tr.translated_term, et.value) AS type_name,
				u.member_id, m.name AS member_name,
				IF(state <> ?, IF(state <> ?, ?, ?), ?) AS state_name,
				0 AS host_id, d.address_point_id
			FROM devices d
			JOIN ifaces i ON i.device_id = d.id
			JOIN ip_addresses ip ON ip.iface_id = i.id
			JOIN monitor_hosts mh ON mh.device_id = d.id
			LEFT JOIN address_points ap ON d.address_point_id = ap.id
			LEFT JOIN towns t ON ap.town_id = t.id
			LEFT JOIN streets s ON ap.street_id = s.id
			LEFT JOIN enum_types et ON d.type = et.id
			LEFT JOIN translations tr ON et.value = tr.original_term AND lang = 'cs'
			LEFT JOIN users u ON d.user_id = u.id
			LEFT JOIN members m ON u.member_id = m.id
			ORDER BY service DESC, INET_ATON(ip_address)
			) mh
			$where
			GROUP BY mh.device_id
			ORDER BY $order_by
		", array(self::STATE_UNKNOWN, self::STATE_UP, __('Offline'), __('Online'), __('Unknown')));
	}
	
	/**
	 * Return all hosts by given state, optional by interval in which have to
	 * be state changed date
	 * 
	 * @author Michal Kliment
	 * @param type $state
	 * @param type $from_state_changed_diff
	 * @param type $to_state_changed_diff
	 * @return type 
	 */
	public function get_all_hosts_by_state(
		$state, $from_state_changed_diff = NULL, $to_state_changed_diff = NULL)
	{
		$where = '';
		
		// limit results by interval of state changed date
		if ($from_state_changed_diff !== NULL && $to_state_changed_diff !== NULL)
		{
			$where = 'WHERE state_changed_diff >= '.intval($from_state_changed_diff)
				.' AND state_changed_diff <= '.intval($to_state_changed_diff)
				.' AND (last_notification_diff IS NULL OR last_notification_diff > '
					.(intval($to_state_changed_diff) - intval($from_state_changed_diff)).')';
		}
		
		return $this->db->query("
			SELECT * FROM
			(
				SELECT
					mh.*,
					d.name,
					ip_address,
					CEIL(
						(
							UNIX_TIMESTAMP(last_attempt_date)
							- UNIX_TIMESTAMP(state_changed_date)
						)/60
					) AS state_changed_diff,
					CEIL(
						(
							UNIX_TIMESTAMP(last_attempt_date)
							- UNIX_TIMESTAMP(last_notification_date)
						)/60
					) AS last_notification_diff
				FROM monitor_hosts mh
				JOIN devices d ON mh.device_id = d.id
				JOIN ifaces i ON i.device_id = d.id
				JOIN
				(
					SELECT ip_address, ip.id AS ip_address_id,
					ip.iface_id
					FROM ip_addresses ip
					ORDER BY ip.service = 1 DESC
				) ip ON ip.iface_id = i.id
				WHERE state = ?
				GROUP BY d.id
			) h
			$where
			ORDER BY state_changed_diff
		", array($state));
	}
	
	/**
	 * Return maximum diff between state changed date and last attempt date
	 * 
	 * @author Michal Kliment
	 * @param type $state
	 * @return type 
	 */
	public function get_max_state_changed_diff($state)
	{
		return $this->db->query("
			SELECT
				MAX(CEIL(
					(
						UNIX_TIMESTAMP(last_attempt_date)
						- UNIX_TIMESTAMP(state_changed_date)
					)/60
				)) AS state_changed_diff
			FROM monitor_hosts mh
			WHERE state = ?
		", array($state))->current()->state_changed_diff;
	}
	
	/**
	 * Return all priorities
	 * 
	 * @author Michal Kliment
	 */
	public function get_all_priorities()
	{
		return $this->db->query("
			SELECT priority AS id, priority
			FROM monitor_hosts
			GROUP BY priority
		");
	}
	
	/**
	 * Gets count of down devices
	 * 
	 * @see My_Controller
	 * @return integer
	 */
	public function count_off_down_devices()
	{
		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM monitor_hosts mh
			WHERE state = ?
		", self::STATE_DOWN)->current()->total;
	}
	
	/**
	 * Update host with data from fping
	 * 
	 * @author Michal Kliment
	 * @param type $ip_address
	 * @param type $state
	 * @param null $latency
	 * @return type 
	 */
	public function update_host($ip_address, $state, $latency)
	{
		$failed = ($state == self::STATE_UP) ? 0 : 1;
		
		if ($state != self::STATE_UP)
			$latency = NULL;
		
		return $this->db->query("
			UPDATE monitor_hosts mh,
			(
				SELECT i.device_id
				FROM ip_addresses ip
				JOIN ifaces i ON ip.iface_id = i.id
				WHERE ip_address = ?
			) ip
			SET
				state = ?,
				state_changed = IF(state <> ?, 1, 0),
				state_changed_date = IF(state <> ?, NOW(), state_changed_date),
				last_attempt_date = NOW(),
				latency_current = ?,
				latency_min = IF(? IS NOT NULL AND (latency_min IS NULL OR latency_min > ?), ?, latency_min),
				latency_max = IF(? IS NOT NULL AND (latency_max IS NULL OR latency_max < ?), ?, latency_max),
				latency_avg = (IFNULL(latency_avg,0) * (polls_total - polls_failed) + ?)/(polls_total + 1 - polls_failed - ?),
				polls_total = polls_total + 1,
				polls_failed = polls_failed + ?,
				availability = ROUND((polls_total + 1 - polls_failed - ?)/(polls_total + 1)*100, 2)
			WHERE ip.device_id = mh.device_id
		", array
		(
			$ip_address,
			$state,
			$state,
			$state,
			$latency,
			$latency, $latency, $latency, 
			$latency, $latency, $latency,
			$latency, $failed,
			$failed, $failed
		));
	}
	
	/**
	 * Adds hosts to monitoring
	 * 
	 * @author Michal Kliment
	 * @param type $ids
	 * @param type $priority
	 * @return type 
	 */
	public function insert_hosts($ids = array(), $priority = 0)
	{
		if (!count($ids))
			return;
		
		$rows = array();
		foreach ($ids as $id)
		{
			$rows[] = '('.$this->db->escape($id).', '.$this->db->escape($priority).', NOW())';
		}
		
		return $this->db->query("
			INSERT INTO monitor_hosts (device_id, priority, state_changed_date) VALUES
			".implode(', ',$rows)
		);
	}
	
	public function update_hosts($ids = array(), $priority = 0)
	{
		if (!count($ids))
			return;
		
		return $this->db->query("
			UPDATE monitor_hosts
			SET priority = ?
			WHERE device_id IN
			(
				".implode(', ', $ids)."
			)
		", $priority);
	}
	
	public function delete_hosts($ids = array(), $column = 'device_id')
	{
		if (!count($ids))
			return;
		
		if (!$this->has_column($column))
			$column = 'device_id';
		
		return $this->db->query("
			DELETE FROM monitor_hosts
			WHERE ".$this->db->escape_column($column)." IN
			(
				".implode(', ', $ids)."
			)
		");
	}
	
	public function update_host_notification_date ($monitor_host_id)
	{
		return $this->db->query("
			UPDATE monitor_hosts
			SET last_notification_date = NOW()
			WHERE id = ?;
		", $monitor_host_id);
	}
}
