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
 * Device is own by user and located at specified address point.
 * Each device has many ifaces.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $user_id
 * @property integer $address_point_id
 * @property string $name
 * @property integer $type 	
 * @property string $trade_name
 * @property integer $operating_system
 * @property integer $PPPoE_logging_in
 * @property string $login
 * @property string $password
 * @property datetime $access_time
 * @property double $price
 * @property double $payment_rate
 * @property date $buy_date
 * @property string $comment
 * @property User_Model $user
 * @property Address_point_Model $address_point
 * @property ORM_Iterator $ifaces
 * @property ORM_Iterator $device_admins
 * @property ORM_Iterator $device_engineers
 */
class Device_Model extends ORM
{
	
	protected $has_many = array
	(
		'ifaces', 'device_admins', 'device_engineers', 'connection_requests', 'device_active_links'
	);
	
	protected $belongs_to = array
	(
		'address_point', 'user'
	);
	
	public $arr_sql = array
	(
		'id'          => 'd.id',
		'login'       => 'u.login',
		'device_name' => 'd.name',
		'username'	  => 'concat(u.name,\' \',u.surname)',
		'device_type' => 'd.type',
		'member_id'	  => 'users.member_id',
	);
	
	/**
	 * Gets all devices from database. Database query returns member's device parameters (id, name, type),
	 * owner of device (user name and surname), MAC addresses of interfaces, names of segments and ip addresses.
	 * 
	 * @param array $params
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_all_devices($params = array(), $user_id = NULL)
	{	
		// default params
		$default_params = array
		(
			'order_by' => 'id',
			'order_by_direction' => 'asc'
		);
		
		$params = array_merge($default_params, $params);
		
		$conds = array();
		
		// filter
		if (isset($params['filter_sql']) && $params['filter_sql'] != '')
			$conds[] = $params['filter_sql'];
		
		// user id
		if ($user_id)
			$conds[] = "d.user_id = " . intval($user_id);
		
		$where = count($conds) ? 'WHERE '.implode(' AND ', $conds) : '';
	
		// order by check
		if (array_key_exists($params['order_by'], $this->arr_sql))
			$order_by = $this->arr_sql[$params['order_by']];
		else
			$order_by = $this->db->escape_column($params['order_by']);
		
		// order by direction check
		if (strtolower($params['order_by_direction']) != 'desc')
			$order_by_direction = 'asc';
		else
			$order_by_direction = 'desc';
		
		if (isset($params['limit']) && isset($params['offset']))
			$limit = "LIMIT " . intval($params['offset']) . ", " . intval($params['limit']);
		else
			$limit = "";
		
		// HACK FOR IMPROVING PERFORMANCE (fixes #362)
		$select_cloud_iface = '';
		$join_cloud_iface = '';
		
		if (isset($params['filter_sql']) && $params['filter_sql'] != '' &&
			(strpos($params['filter_sql'], '.`cloud` LIKE ') ||
			strpos($params['filter_sql'], '.`mac` LIKE ')))
		{
			$select_cloud_iface = ', c.id AS cloud, i.mac';
			$join_cloud_iface = "
				LEFT JOIN ifaces i ON i.device_id = d.id
				LEFT JOIN ip_addresses ip ON ip.iface_id = i.id
				LEFT JOIN clouds_subnets cs ON cs.subnet_id = ip.subnet_id
				LEFT JOIN clouds c ON cs.cloud_id = c.id";
		}
		
		// query
		return $this->db->query("
			SELECT * FROM
			(
				SELECT d.id, d.id AS device_id, d.type,
					IFNULL(f.translated_term, e.value) AS type_name,
					d.name, d.name AS device_name, u.id AS user_id,
					u.name AS user_name, u.surname AS user_surname, u.login AS user_login,
					d.login, d.password, d.price, d.trade_name, d.payment_rate,
					d.buy_date, m.name AS member_name, s.street, t.town,
					ap.street_number, d.comment $select_cloud_iface
				FROM devices d
				JOIN users u ON d.user_id = u.id
				JOIN members m ON u.member_id = m.id
				LEFT JOIN address_points ap ON d.address_point_id = ap.id
				LEFT JOIN streets s ON ap.street_id = s.id
				LEFT JOIN towns t ON ap.town_id = t.id
				LEFT JOIN enum_types e ON d.type = e.id
				LEFT JOIN translations f ON lang = ? AND e.value = f.original_term
				$join_cloud_iface
			) d
			$where
			GROUP BY device_id
			ORDER BY $order_by $order_by_direction
			$limit
		", Config::get('lang'));
	} // end of get_all_devices
	
	/**
	 * Count of all devices
	 * @param array $filter_values
	 * @return integer
	 */
	public function count_all_devices($filter_sql = '')
	{
		$where = '';
		
		// filter
		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		// HACK FOR IMPROVING PERFORMANCE (fixes #362)
		$select_cloud_iface = '';
		$join_cloud_iface = '';
		
		if (strpos($filter_sql, '.`cloud` LIKE ') || strpos($filter_sql, '.`mac` LIKE '))
		{
			$select_cloud_iface = ', c.id AS cloud, i.mac';
			$join_cloud_iface = "
				LEFT JOIN ifaces i ON i.device_id = d.id
				LEFT JOIN ip_addresses ip ON ip.iface_id = i.id
				LEFT JOIN clouds_subnets cs ON cs.subnet_id = ip.subnet_id
				LEFT JOIN clouds c ON cs.cloud_id = c.id";
		}
		
		// query
		return $this->db->query("
			SELECT COUNT(device_id) AS total FROM
			(
				SELECT device_id FROM
				(
					SELECT d.id AS device_id, d.type,
						IFNULL(f.translated_term, e.value) AS type_name, d.name,
						d.name AS device_name, u.id AS user_id, u.name AS user_name,
						u.surname AS user_surname, u.login AS user_login,
						d.login, d.password, d.price, d.trade_name, d.payment_rate,
						d.buy_date, m.name AS member_name, s.street, t.town,
						ap.street_number, d.comment $select_cloud_iface
					FROM devices d
					JOIN users u ON d.user_id = u.id
					JOIN members m ON u.member_id = m.id
					LEFT JOIN address_points ap ON d.address_point_id = ap.id
					LEFT JOIN streets s ON ap.street_id = s.id
					LEFT JOIN towns t ON ap.town_id = t.id
					LEFT JOIN enum_types e ON d.type = e.id
					LEFT JOIN translations f ON lang = ? AND e.value = f.original_term
					$join_cloud_iface
				) d
				$where
				GROUP BY device_id
			) count
		", Config::get('lang'))->current()->total;
	} // end of count_all_devices
	
	/**
	 * Gets filtered DHCP servers list
	 * 
	 * @param array $params
	 * @return Mysql_Result
	 */
	public function get_all_dhcp_servers($params = array())
	{
		// default params
		$default_params = array
		(
			'order_by'				=> 'access_time',
			'order_by_direction'	=> 'asc'
		);
		
		$params = array_merge($default_params, $params);
		$where = '';
		
		// filter
		if (isset($params['filter_sql']) && $params['filter_sql'] != '')
			$where = 'WHERE ' . $params['filter_sql'];
	
		// order by check
		if (array_key_exists($params['order_by'], $this->arr_sql))
			$order_by = $this->arr_sql[$params['order_by']];
		else
			$order_by = $this->db->escape_column($params['order_by']);
		
		// order by direction check
		if (strtolower($params['order_by_direction']) != 'desc')
			$order_by_direction = 'asc';
		else
			$order_by_direction = 'desc';
		
		if (isset($params['limit']) && isset($params['offset']))
			$limit = 'LIMIT ' . intval($params['offset']) . ', ' . intval($params['limit']);
		else
			$limit = '';
		
		return $this->db->query("
			SELECT * FROM (
				SELECT d.id AS device_id, d.type,
					IFNULL(f.translated_term, e.value) AS type_name, d.name,
					d.name AS device_name, u.id AS user_id, u.name AS user_name,
					u.surname AS user_surname, u.login AS user_login,
					d.login, d.password, d.price, d.trade_name, d.payment_rate,
					d.buy_date, m.name AS member_name, st.street, t.town,
					ap.street_number, d.comment, s.id AS subnet_id,
					s.name AS subnet_name, d.access_time, ip.ip_address,
					ip.id AS ip_address_id
				FROM subnets s
				JOIN ip_addresses ip ON s.id = ip.subnet_id
				JOIN ifaces i ON i.id = ip.iface_id
				JOIN devices d ON d.id = i.device_id
				JOIN users u ON d.user_id = u.id
				JOIN members m ON u.member_id = m.id
				LEFT JOIN address_points ap ON d.address_point_id = ap.id
				LEFT JOIN streets st ON ap.street_id = st.id
				LEFT JOIN towns t ON ap.town_id = t.id
				LEFT JOIN enum_types e ON d.type = e.id
				LEFT JOIN translations f ON lang = ? AND e.value = f.original_term
				WHERE s.dhcp > 0 AND ip.gateway > 0
			) d
			$where
			GROUP BY device_id
			ORDER BY $order_by $order_by_direction
			$limit
		", Config::get('lang'));
	} // end of get_all_dhcp_servers

	/**
	 * Count filtered DHCP servers
	 * 
	 * @param array $params
	 * @return int
	 */
	public function count_all_dhcp_servers($filter_sql = '')
	{
		$where = '';
		
		// filter
		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		// query
		return $this->db->query("
			SELECT COUNT(device_id) AS c FROM (
				SELECT device_id FROM (
					SELECT  d.id AS device_id, d.type,
						IFNULL(f.translated_term, e.value) AS type_name, d.name,
						d.name AS device_name, u.id AS user_id, u.name AS user_name,
						u.surname AS user_surname, u.login AS user_login,
						d.login, d.password, d.price, d.trade_name, d.payment_rate,
						d.buy_date, m.name AS member_name, st.street, t.town,
						ap.street_number, d.comment, s.id AS subnet_id,
						s.name AS subnet_name, d.access_time, ip.ip_address,
						ip.id AS ip_address_id
					FROM subnets s
					JOIN ip_addresses ip ON s.id = ip.subnet_id
					JOIN ifaces i ON i.id = ip.iface_id
					JOIN devices d ON d.id = i.device_id
					JOIN users u ON d.user_id = u.id
					JOIN members m ON u.member_id = m.id
					LEFT JOIN address_points ap ON d.address_point_id = ap.id
					LEFT JOIN streets st ON ap.street_id = st.id
					LEFT JOIN towns t ON ap.town_id = t.id
					LEFT JOIN enum_types e ON d.type = e.id
					LEFT JOIN translations f ON lang = ? AND e.value = f.original_term
					WHERE s.dhcp > 0 AND ip.gateway > 0
				) d
				$where
				GROUP BY device_id
			) d2
		", Config::get('lang'))->current()->c;
	} // end of count_all_dhcp_servers
	
	/**
	 * Returns all devices of user
	 *
	 * @author Michal Kliment
	 * @param int $user_id
	 * @param bool $display_empty
	 * @param int $limit_from
	 * @param int $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_devices_of_user(
			$user_id, $display_empty = TRUE, $limit_from = 0,
			$limit_results = NULL, $order_by = 'ip_address',
			$order_by_direction = 'asc')
	{
		// fix ip address
		if ($order_by == 'ip_address')
		{
			$order_by = 'INET_ATON(ip_address)';
		}
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		$limit = '';
		
		if ($limit_results)
			$limit = 'LIMIT ' . intval($limit_from) . ', ' . intval($limit_results);
		
		$display_empty_sql = '';
		
		if (!$display_empty)
		{
			$display_empty_sql = "AND NOT (
				(ISNULL(ip.ip_address) OR LENGTH(ip.ip_address) = 0)
			)";
		}

		return $this->db->query("
			SELECT d.*, i.id AS iface_id, i.mac, ip.id AS ip_address_id,
				ip.ip_address, s.id AS subnet_id, s.name AS subnet_name,
				IFNULL(t.translated_term,e.value) AS type,
				GROUP_CONCAT(IF(ip.ip_address IS NULL, '', CONCAT(ip.ip_address, ',\\n')) SEPARATOR '') AS ip_addresses
			FROM devices d
			LEFT JOIN enum_types e ON d.type = e.id
			LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = ?
			LEFT JOIN ifaces i ON d.id = i.device_id
			LEFT JOIN ip_addresses ip ON ip.iface_id = i.id
			LEFT JOIN subnets s ON ip.subnet_id = s.id
			WHERE user_id = ? $display_empty_sql
			GROUP BY d.id
			ORDER BY $order_by $order_by_direction
			$limit
		", array(Config::get('lang'), $user_id));
	}
	
	/**
	 * Function counts devices of user.
	 * 
	 * @param integer $user_id
	 * @return integer
	 */
	public function count_devices_of_user($user_id = null)
	{
   		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM devices d
   			WHERE d.user_id = ?
   		", $user_id)->current()->total;
	}

	/**
	 * Returns all member's devices with debt payments
	 *
	 * @author Michal Kliment
	 * @param int $member_id
	 * @return Mysql_Result
	 */
	public function get_member_devices_with_debt_payments($member_id)
	{
		return $this->db->query("
			SELECT price, payment_rate, buy_date
			FROM devices d
			LEFT JOIN users u ON d.user_id = u.id
			WHERE u.member_id = ? AND price IS NOT NULL AND price > 0
		", array($member_id));
	}
	
	/**
	 * Sums all debt payments of member
	 * 
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @return integer 
	 */
	public function sum_debt_payments_of_member($member_id)
	{
		return $this->db->query("
			SELECT SUM(price) AS total_amount
			FROM devices d
			LEFT JOIN users u ON d.user_id = u.id
			WHERE u.member_id = ? AND price IS NOT NULL AND price > 0
		", array($member_id))->current()->total_amount;
	}

	/**
	 * Returns gateway of subnet
	 *
	 * @author Michal Kliment
	 * @param integer $subnet_id
	 * @return Mysql_Result
	 */
	public function get_gateway_of_subnet($subnet_id)
	{
		return $this->db->query("
			SELECT d.* FROM
			(
				SELECT i.device_id
				FROM
				(
					SELECT * FROM ip_addresses ip
					WHERE ip.subnet_id = ? AND gateway = 1
					LIMIT 0,1
				) AS ip
				LEFT JOIN ifaces i ON ip.iface_id = i.id
			) AS q
			JOIN devices d ON q.device_id = d.id
		", array($subnet_id))->current();
	}

	/**
	 * Gets parent of device
	 * 
	 * @param integer $device_id
	 * @return Mysql_Result
	 */
	public function get_parent($device_id = NULL)
	{
		if (!$device_id)
			$device_id = $this->id;

		return $this->db->query("
			SELECT d.* 
			FROM
			(
				SELECT s.*
				FROM ifaces i
				JOIN links s ON i.link_id = s.id
				WHERE i.device_id = ?
			) s
			JOIN
			(
				SELECT i.*
				FROM ifaces i
				LEFT JOIN ip_addresses ip ON ip.iface_id = i.id AND ip.gateway = 1
				WHERE ip.id IS NOT NULL
			) i ON i.link_id = s.id
			JOIN devices d ON i.device_id = d.id
			WHERE i.device_id <> ?
		", array($device_id, $device_id))->current();
	}
	
	/**
	 * Select list of user and their devices
	 *
	 * @return array
	 */
	public function select_list_device()
	{
		$devices = $this->db->query("
			SELECT d.id, d.name, CONCAT(u.surname, ' ', u.name) AS user_name
			FROM devices d
			JOIN users u ON d.user_id = u.id
			ORDER BY IF(ISNULL(d.name) OR LENGTH(d.name) = 0, 1, 0), d.name
		");
		
		$arr_devices = array();
		
		foreach ($devices as $device)
		{
			$arr_devices[$device->id] = "$device->name ($device->user_name)";
		}
		
		return $arr_devices;
	}
	
	
	/**
	 * Select list of user and their devices
	 *
	 * @param integer $user_id		 User ID for filter only user's devices [optional]
	 * @return array
	 */
	public function select_list_device_with_user($user_id = null)
	{
		$where = is_numeric($user_id) ? 'WHERE u.id = ' . intval($user_id) : '';
		
		$devices = $this->db->query("
			SELECT d.id, ip.ip_address, u.id as user_id,
				IF(ISNULL(d.name) OR LENGTH(d.name) = 0, d.id, d.name) AS device_name,
				IF(
					CONCAT(u.name, ' ', u.surname) LIKE m.name,
					CONCAT(u.surname, ' ', u.name, ' - ', u.login), m.name
				) AS user_name
			FROM devices d
			JOIN users u ON d.user_id = u.id
			JOIN members m ON u.member_id = m.id
			LEFT JOIN ifaces i ON i.device_id = d.id
			LEFT JOIN ip_addresses ip ON ip.iface_id = i.id $where
			GROUP BY d.id
			ORDER BY IF(u.id <> ?, 1, 0), user_name,
				IF(u.id <> ?, IF(ISNULL(ip.ip_address) OR LENGTH(ip.ip_address) = 0, 1, 0), 1),
				IF(u.id <> ?, INET_ATON(ip.ip_address), d.name),
				d.name, u.id
		", User_Model::MAIN_USER, User_Model::MAIN_USER, User_Model::MAIN_USER);
		
		$arr_devices = array();
		
		foreach ($devices as $device)
		{
			if ($device->user_id == User_Model::MAIN_USER)
			{
				$name = $device->device_name;
				
				if ($device->ip_address)
				{
					$name .= ': ' . $device->ip_address;
				}
			}
			else
			{
				$name = $device->device_name;
				
				if ($device->ip_address)
				{
					if (!empty($name))
					{
						$name = $device->ip_address . ': ' . $name;
					}
					else
					{
						$name = $device->ip_address;
					}
				}
			}
			
			$arr_devices[$device->user_name][$device->id] = $name;
		}
		
		return $arr_devices;
	}
	
	/**
	 * Select list of user and their devices
	 *
	 * @param string $filter_sql Filter query
	 * @param boolean $json Indicator if JSON is enabled (change structure)
	 * @return array
	 */
	public function select_list_filtered_device_with_user($filter_sql = '', $json = TRUE)
	{
		$where = '';
		if (!empty($filter_sql))
		{
			$where = 'WHERE '.$filter_sql;
		}
		
		$devices = $this->db->query("
			SELECT device_id AS id, ip_address,
				IF(ISNULL(device_name) OR LENGTH(device_name) = 0, device_id, device_name) AS device_name,
				df.user_name, user as user_id
			FROM
			(
				SELECT d.id AS device_id, d.name AS device_name, u.id AS user,
					s.id AS subnet, d.type, ip.ip_address, ap.street_id AS street,
					ap.town_id AS town, ap.street_number, IF(
						CONCAT(u.name, ' ', u.surname) LIKE m.name,
						CONCAT(u.surname, ' ', u.name, ' - ', u.login), m.name
					) AS user_name
				FROM devices d
				JOIN users u ON d.user_id = u.id
				JOIN members m ON u.member_id = m.id
				JOIN address_points ap ON ap.id = d.address_point_id
				LEFT JOIN ifaces i ON i.device_id = d.id
				LEFT JOIN ip_addresses ip ON ip.iface_id = i.id
				LEFT JOIN subnets s ON ip.subnet_id = s.id
			) df
			$where
			GROUP BY device_id
			ORDER BY IF(df.user <> ?, 1, 0), df.user_name,
				IF(df.user <> ?, IF(ISNULL(df.ip_address) OR LENGTH(df.ip_address) = 0, 1, 0), 1),
				IF(df.user <> ?, INET_ATON(df.ip_address), df.device_name),
				df.device_name, df.user
		", User_Model::MAIN_USER, User_Model::MAIN_USER, User_Model::MAIN_USER);

		
		$arr_devices = array();
		
		foreach ($devices as $device)
		{
			if ($device->user_id == User_Model::MAIN_USER)
			{
				$name = $device->device_name;
				
				if ($device->ip_address)
				{
					$name .= ': ' . $device->ip_address;
				}
			}
			else
			{
				$name = $device->device_name;
				
				if ($device->ip_address)
				{
					if (!empty($name))
					{
						$name = $device->ip_address . ': ' . $name;
					}
					else
					{
						$name = $device->ip_address;
					}
				}
			}
			
			if ($json === TRUE)
			{
				$arr_devices[$device->user_name][] = array('id' => $device->id, 'name' => $name);
			}
			else
			{
				$arr_devices[$device->user_name][$device->id] = $name;
			}
		}
		
		return $arr_devices;
	}
	
	/**
	 * Returns all devices of link
	 * 
	 * @author Michal Kliment
	 * @param integer $segment_id
	 * @return MySQL_Iterator object 
	 */
	public function get_all_devices_of_link($link_id)
	{
		return $this->db->query("
			SELECT
				d.*, i.name AS iface_name, i.mac, ip.ip_address,
				m.id AS member_id, m.name AS member_name
			FROM devices d
			LEFT JOIN ifaces i ON i.device_id = d.id
			LEFT JOIN ip_addresses ip ON ip.iface_id = i.id
			LEFT JOIN users u ON d.user_id = u.id
			LEFT JOIN members m ON u.member_id = m.id
			WHERE i.link_id = ?
			GROUP BY d.id
		", $link_id);
	}
	
	/**
	 * Returns all devices of link
	 * 
	 * @author Michal Kliment
	 * @param integer $device_id
	 * @return object 
	 */
	public function get_all_connected_to_device($device_id)
	{
		return $this->db->query("
			SELECT
				cd.id AS connected_to_device_id,
				cd.name AS connected_to_device_name,
				ci.id AS connected_to_iface_id,
				ci.name AS connected_to_iface_name,
				IFNULL(COUNT(DISTINCT cd.id), 0) AS connected_to_devices_count,
				GROUP_CONCAT(DISTINCT cd.name SEPARATOR ', \\n') AS connected_to_devices
			FROM devices d
			JOIN ifaces i ON i.device_id = d.id
			JOIN links l ON i.link_id = l.id
			JOIN ifaces ci ON ci.link_id = l.id AND ci.id <> i.id AND
			(
				i.type NOT IN(?, ?) OR
				(
					i.type IN(?, ?) AND
					(
						i.wireless_mode = ? AND ci.wireless_mode = ?
					) OR i.wireless_mode = ?
				)
			)
			JOIN devices cd ON ci.device_id = cd.id
			WHERE d.id = ?
			GROUP BY d.id
		", array
		(
			Iface_Model::TYPE_WIRELESS, Iface_Model::TYPE_VIRTUAL_AP,
			Iface_Model::TYPE_WIRELESS, Iface_Model::TYPE_VIRTUAL_AP,
			Iface_Model::WIRELESS_MODE_CLIENT,
			Iface_Model::WIRELESS_MODE_AP,
			Iface_Model::WIRELESS_MODE_AP,
			$device_id
		))->current();
	}
	
	/**
	 * Returns all devices of member
	 * 
	 * @author Michal Kliment
	 * @param type $member_id
	 * @return type 
	 */
	public function get_all_devices_by_member($member_id = NULL)
	{
		$member_clause = '';
		
		if ($member_id)
			$member_clause = 'AND u.member_id = '.intval($member_id);
		
		return $this->db->query("
			SELECT
				d.id,
				IF(d.name <> '', d.name, IFNULL(t.translated_term, et.value)) AS name
			FROM devices d
			JOIN users u ON d.user_id = u.id $member_clause
			JOIN enum_types et ON d.type = et.id
			LEFT JOIN translations t ON t.original_term = et.value AND lang = ?
			ORDER BY name
		", array(Config::get('lang')));
	}
	
	/**
	 * Returns all devices with service flag in given subnets
	 * 
	 * @author Michal Kliment
	 * @param type $subnets
	 * @return boolean 
	 */
	public function get_all_service_devices_of_subnets($subnets = array())
	{
		if (!is_array($subnets))
			$subnets = array($subnets);
		
		if (!count($subnets))
			return FALSE;
		
		$where = "AND ip.subnet_id IN (".implode(", ", $subnets).")";
		
		return $this->db->query("
			SELECT d.*
			FROM devices d
			JOIN ifaces i ON i.device_id = d.id
			JOIN ip_addresses ip ON ip.iface_id = i.id
			WHERE IFNULL(ip.service,0) = 1
				AND IFNULL(ip.gateway,0) = 0
				$where
			ORDER BY d.name
		");
	}
	
	/**
	 * Returns all dependent subnets
	 * 
	 * @author Michal Kliment
	 * @param type $device_id
	 * @param type $recursive
	 * @return type 
	 */
	public function get_all_dependent_subnets($device_id = NULL, $recursive = TRUE)
	{
		if (!$device_id)
			$device_id = $this->id;
		
		return ORM::factory('subnet')
				->get_all_dependent_subnets_by_device($device_id, $recursive);
	}
	
	/**
	 * Checks if port number exists on given device
	 * 
	 * @param integer $port_number
	 * @param integer $device_id
	 * @param integer $iface_id			Iface for edit
	 * @return boolean
	 */
	public function port_number_exists($port_number, $device_id = NULL, $iface_id = NULL)
	{
		if ($device_id === NULL && $this)
		{
			$device_id = $this->id;
		}
		
		$where = '';
		
		if (intval($iface_id))
		{
			$where = 'AND i.id <> ' . intval($iface_id);
		}
		
		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM ifaces i
			WHERE i.device_id = ? AND i.type = ? AND i.number = ? $where
		", $device_id, Iface_Model::TYPE_PORT, $port_number)->current()->total > 0;
	}
	
	/**
	 * Gets next available port number for new port of device
	 * 
	 * @author Ondřej Fibich
	 * @param integer $device_id
	 * @return integer
	 */
	public function get_next_port_number($device_id = null)
	{
		if ($device_id === NULL && isset($this) && $this->id)
		{
			$device_id = $this->id;
		}
		
		return $this->db->query("
			SELECT IFNULL(MAX(i.number) + 1, 1) AS pnumber
			FROM ifaces i
			WHERE i.device_id = ? AND i.type = ?
		", $device_id, Iface_Model::TYPE_PORT)->current()->pnumber;
	}
	
	/**
	 * Test whether device has any ports
	 * 
	 * @author Michal Kliment
	 * @param type $device_id
	 * @return type
	 */
	public function has_ports($device_id = null)
	{
		if ($device_id === NULL && isset($this) && $this->id)
		{
			$device_id = $this->id;
		}
		
		$result = $this->db->query("
			SELECT COUNT(*) AS count
			FROM ifaces i
			WHERE i.device_id = ? AND i.type = ?
		", $device_id, Iface_Model::TYPE_PORT);
		
		return ($result && $result->current() && $result->current()->count);
	}
	
	/**
	 * Returns full export of device as object
	 * 
	 * @author Michal Kliment <kliment@freenetis.org>
	 * @param type $device_id
	 * @return type
	 */
	public function get_export($device_id = NULL)
	{
		if (!$device_id && $this->id)
			$device = $this;
		else
			$device = new Device_Model($device_id);
		
		// interfaces of device
		$interfaces = array();
		
		// IP addresses of device
		$addresses = array();

		// gateways of device, probably only one
		$gateways = array();
		
		// DHCP servers of device
		$dhcp_servers = array();
		
		// DHCP servers addresses (for sorting)
		$dhcp_servers_addresses = array();
		
		// dns servers
		$system_dns_servers = arr::trim(explode("\n", Settings::get('dns_servers')));
		
		if (!count($system_dns_servers))
		{
			throw new Exception(
				__('Error').': '.
				__('Device has not been exported: DNS servers are not set')
			);
		}
		
		// all device's interfaces
		foreach ($device->ifaces as $iface)
		{
			// for all type of interface
			$interface = arr::to_object(array
			(
				'name'			=> $iface->name,
				'type'			=> $iface->type,
				'mac'			=> $iface->mac,
				'number'		=> $iface->number,
				'link_name'		=> $iface->link->name,
				'comment'		=> '{link_name}'
			), 'stdClass', FALSE);
			
			// only for wireless type of interface
			if ($iface->type == Iface_Model::TYPE_WIRELESS)
			{
				$interface->wireless = arr::to_object(array
				(
					'mode'			=> $iface->wireless_mode,
					'ssid'			=> $iface->link->wireless_ssid,
					'norm'			=> $iface->link->wireless_norm,
					'frequency'		=> $iface->link->wireless_norm,
					'channel'		=> $iface->link->wireless_norm,
					'channel_width'	=> $iface->link->wireless_norm,
					'polarization'	=> $iface->link->wireless_norm,
					'bitrate'		=> $iface->link->bitrate
				), 'stdClass', FALSE);
				
				// only for mode AP of wireless type of interface
				if ($iface->wireless_mode == Iface_Model::WIRELESS_MODE_AP)
				{
					$interface->wireless->clients = array();
					
					// find all connected client
					foreach ($iface->link->ifaces as $link_iface)
					{
						// not device itself
						if ($link_iface->id == $iface->id)
							continue;
						
						$interface->wireless->clients[] = arr::to_object(array
						(
							'name'			=> $link_iface->name,
							'mac'			=> $link_iface->mac,
							'device_name'	=> $link_iface->device->name,
							'device_type'	=> $link_iface->device->type,
							'member_id'		=> $link_iface->device->user->member->id,
							'member_name'	=> $link_iface->device->user->member->name,
							'member_type'	=> $link_iface->device->user->member->type,
							'comment'		=> 'ID {member_id} - {member_name} - {device_name}'
						), 'stdClass', FALSE);
					}
				}
			}
			
			// only for VLAN type of interface 
			else if ($iface->type == Iface_Model::TYPE_VLAN)
			{	
				if (!$iface->ifaces_vlans->count())
				{
					throw new Exception(
						__('Error').': '.__('Cannot find VLAN of VLAN interface '.$iface->name)
					);
				}
				
				if (!$iface->ifaces_relationships->count())
				{
					throw new Exception(
						__('Error').': '.__('Cannot find parent interface of VLAN interface '.$iface->name)
					);
				}

					$interface->vlan = arr::to_object(array
				(
					'name'				=> $iface->ifaces_vlans->current()->vlan->name,
					'parent_interface'	=> $iface->ifaces_relationships->current()->parent_iface->name,
					'tag_802_1q'		=> $iface->ifaces_vlans->current()->vlan->tag_802_1q
				), 'stdClass', FALSE);
			}
				
			// only for port type of interface
			else if ($iface->type == Iface_Model::TYPE_PORT)
			{
				// default port vlan of port is default VLAN
				$port_vlan = 1;
				
				$vlans = array();
				
				// for all port's vlans
				foreach ($iface->ifaces_vlans as $iface_vlan)
				{	
					// tagged or untagged
					$vlans[$iface_vlan->vlan->tag_802_1q] = $iface_vlan->tagged;
					
					// vlan is port vlan
					if ($iface_vlan->port_vlan)
						$port_vlan = $iface_vlan->vlan->tag_802_1q;
				}
				
				$interface->port = arr::to_object(array
				(
					'mode'		=> $iface->port_mode,
					'port_vlan' => $port_vlan,
					'vlans'		=> $vlans
				), 'stdClass', FALSE);
			}
			
			$interface->ip_addresses = array();

			// interface's IP addresses
			foreach ($iface->ip_addresses as $ip_address)
			{				
				// create CIDR format
				$cidr = $ip_address->subnet->network_address.'/'.network::netmask2cidr($ip_address->subnet->netmask);
				
				// find broadcast address
				$broadcast = long2ip(ip2long($ip_address->subnet->network_address) | ~ip2long($ip_address->subnet->netmask));
				
				$address = arr::to_object(array
				(
					'address'		=> $ip_address->ip_address,
					'netmask'		=> $ip_address->subnet->netmask,
					'network'		=> $ip_address->subnet->network_address,
					'broadcast'		=> $broadcast,
					'cidr'			=> $cidr,
					'interface'		=> $iface->name,
					'subnet_name'	=> $ip_address->subnet->name,
					'comment'		=> '{subnet_name}'
				), 'stdClass', FALSE);

				// device is gateway
				if ($ip_address->gateway)
				{
					// DHCP server on subnet is running
					if ($ip_address->subnet->dhcp)
					{
						$range_start	= long2ip(ip2long($ip_address->ip_address)+1);
						$range_end		= long2ip(ip2long($broadcast)-1);
						
						$dns_servers = $system_dns_servers;
						
						// DNS server is on
						if ($ip_address->subnet->dns)
						{
							array_unshift($dns_servers, $ip_address->ip_address);
						}
						
						$dhcp_server = arr::to_object(array
						(
							'name'			=> text::cs_utf2ascii($ip_address->subnet->name),
							'network'		=> $ip_address->subnet->network_address,
							'netmask'		=> $ip_address->subnet->netmask,
							'gateway'		=> $ip_address->ip_address,
							'broadcast'		=> $broadcast,
							'cidr'			=> $cidr,
							'interface'		=> $iface->name,
							'range_start'	=> $range_start,
							'range_end'		=> $range_end,
							'ranges'		=> array
							(
								arr::to_object(array
								(	
									'start'		=> $range_start,
									'end'		=> $range_end
								), 'stdClass', FALSE),
							),
							'dns_servers'	=> $dns_servers,
							'hosts'			=> array(),
							'comment'		=> '{name}'
						), 'stdClass', FALSE);
						
						$mac_addresses = array();
						
						$ip_addresses = array();
						
						// hosts addresses (for sorting)
						$hosts_addresses = array();
						
						// find all hosts in subnets
						foreach ($ip_address->subnet->ip_addresses as $dhcp_ip_address)
						{
							// not device itself
							if ($dhcp_ip_address->ip_address == $ip_address->ip_address ||
								!$dhcp_ip_address->iface_id)
							{
								continue;
							}
							
							// for VLAN interface get MAC from parent interface
							if ($dhcp_ip_address->iface->type == Iface_Model::TYPE_VLAN)
							{
								if (!$dhcp_ip_address->iface->ifaces_relationships->count())
								{
									throw new Exception(
										__('Error').': '.__('Cannot find parent interface of VLAN interface '.$dhcp_ip_address->iface)
									);
								}
								
								$mac = $dhcp_ip_address->iface->ifaces_relationships->current()->parent_iface->mac;
							}
							else
								$mac = $dhcp_ip_address->iface->mac;
							
							$host = arr::to_object(array
							(
								'id'			=> $dhcp_ip_address->id,
								'server'		=> $dhcp_server->name,
								'ip_address'	=> $dhcp_ip_address->ip_address,
								'mac'			=> $mac,
								'device_name'	=> $dhcp_ip_address->iface->device->name,
								'device_type'	=> $dhcp_ip_address->iface->device->type,
								'member_id'		=> $dhcp_ip_address->iface->device->user->member->id,
								'member_name'	=> $dhcp_ip_address->iface->device->user->member->name,
								'member_type'	=> $dhcp_ip_address->iface->device->user->member->type,
								'comment'		=> 'ID {member_id} - {member_name} - {device_name}'
							), 'stdClass', FALSE);
							
							// mac is not valid
							if (!valid::mac_address($host->mac))
							{
								// log to database
								Log_queue_Model::error(
									'Device has not been exported: Invalid MAC address format',
									print_r($host, true)
								);
							}
							// mac is not unique
							else if (in_array($host->mac, $mac_addresses))
							{
								// log to database
								Log_queue_Model::error(
									'Device has not been exported: Duplicate MAC address '.$host->mac.' in subnet',
									print_r($host, true)
								);
							}
							// mac is valid => host is valid
							else
							{
								$mac_addresses[] = $host->mac;
								
								$ranges = array();
								
								
								foreach ($dhcp_server->ranges as $i => $range)
								{
									// ip address is in range, split range
									if (ip2long($host->ip_address) >= ip2long($range->start)
										&& ip2long($host->ip_address) <= ip2long($range->end))
									{	
										if ($range->start != $host->ip_address)
										{
											$ranges[] = arr::to_object(array
											(
												'start' => $range->start,
												'end'	=> long2ip(ip2long($host->ip_address)-1)
											));
										}
										
										if ($range->end != $host->ip_address)
										{
											$ranges[] = arr::to_object(array
											(
												'start' => long2ip(ip2long($host->ip_address)+1),
												'end'	=> $range->end
											));
										}
									}
									else
									{
										$ranges[] = $range;
									}
								}
								
								$dhcp_server->ranges = $ranges;
								
								$dhcp_server->hosts[] = $host;
								
								$hosts_addresses[] = ip2long($host->ip_address);
							}
						}
						
						array_multisort($hosts_addresses, $dhcp_server->hosts);
						
						$dhcp_servers[] = $dhcp_server;
						
						$dhcp_servers_addresses[] = ip2long($dhcp_server->network);
					}
				}
				else
				{
					// find gateway of subnet
					$gateway = $ip_address->subnet->get_gateway();
					
					if ($gateway && $gateway->ip_address)
					{
						$gateways[] = $gateway->ip_address;
						
						$address->gateway = $gateway->ip_address;
					}
				}

				$addresses[] = $address;
				
				$interface->ip_addresses[] = $address;
			}
			
			$interfaces[$iface->type][] = $interface;
		}
		
		array_multisort($dhcp_servers_addresses, $dhcp_servers);
		
		// finally return result
		return arr::to_object(array
		(
			'name'			=> $device->name,
			'type'			=> $device->type,
			'interfaces'	=> $interfaces,
			'ip_addresses'	=> $addresses,
			'dhcp_servers'	=> $dhcp_servers,
			'dns_servers'	=> $system_dns_servers,
			'gateways'		=> array_unique($gateways)
		), 'stdClass', FALSE);
	}
	
	/**
	 * Test if given IP address belongs to device
	 * 
	 * @author Michal Kliment <kliment@freenetis.org>
	 * @param type $ip_address
	 * @param type $device_id
	 * @return boolean
	 */
	public function is_ip_address_of_device($ip_address, $device_id = NULL)
	{
		if (!$device_id && $this->id)
			$device_id = $this->id;
		
		$result = $this->db->query("
			SELECT COUNT(*) AS count
			FROM ip_addresses ip
			JOIN ifaces i ON ip.iface_id = i.id
			WHERE ip.ip_address = ? AND i.device_id = ?
		", array($ip_address, $device_id));
		
		if ($result && $result->current() && $result->current()->count > 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
}
