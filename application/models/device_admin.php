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
 * Device admin is sets admins of device (they have access to it).
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $device_id
 * @property Device_Model $device
 * @property integer $user_id
 * @property User_Model $user
 */
class Device_admin_Model extends ORM
{
	protected $belongs_to = array('device', 'user');
	
	/**
	 * Function gets admins of specified device.
	 * 
	 * @param $device_id
	 * @return Mysql_Result
	 */
	public function get_device_admins($device_id)
	{
		return $this->db->query("
				SELECT da.id, u.name, u.surname, u.login
				FROM device_admins da
				JOIN users u ON da.user_id = u.id
				WHERE da.device_id = ?
				ORDER BY id asc
		", $device_id);
	}
	
	/**
	 * Returns all devices of which is user admin
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @param string $query
	 * @param integer $device_user_id
	 * @return Mysql_Result
	 */
	public function get_all_devices_in_user_device_admins(
			$user_id, $query = '', $device_user_id = NULL)
	{
		$sql = array();

		if ($query != '')
			$sql[] = "d.name LIKE " . $this->db->escape("%$query%");

		if ($device_user_id)
			$sql[] = "d.user_id = " . intval($device_user_id);

		$where = (count($sql)) ? " AND " . implode(" AND ", $sql) : "";

		return $this->db->query("
				SELECT d.*, CONCAT(u.name,' ',u.surname) AS user_name
				FROM devices d
				JOIN users u ON d.user_id = u.id
                WHERE d.id IN (
					SELECT da.device_id
					FROM device_admins da
					WHERE da.user_id = ?
					GROUP BY device_id
                )
				$where
                ORDER BY u.member_id, d.name
		", array($user_id));
	}
        
	/**
	 * Returns all devices of which is not user admin
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @param string $query
	 * @param integer $device_user_id
	 * @return Mysql_Result
	 */
    public function get_all_devices_not_in_user_device_admins(
			$user_id, $query = '', $device_user_id = NULL)
	{
		$sql = array();

		if ($query != '')
			$sql[] = "d.name LIKE " . $this->db->escape("%$query%");

		if ($device_user_id)
			$sql[] = "d.user_id = " . intval($device_user_id);

		$where = (count($sql)) ? " AND " . implode(" AND ", $sql) : "";

		return $this->db->query("
				SELECT d.*, CONCAT(u.name,' ',u.surname) AS user_name
				FROM devices d
				JOIN users u ON d.user_id = u.id
                WHERE d.id NOT IN (
					SELECT da.device_id
					FROM device_admins da
					WHERE da.user_id = ?
					GROUP BY device_id
                )
				$where
                ORDER BY u.member_id, d.name
		", array($user_id));
	}
	
	/**
	 * Returns all devices of which is user admin
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @return Mysql_Result 
	 */
	public function get_all_devices_by_admin ($user_id)
	{
		return $this->db->query("
			SELECT da.id, da.device_id, d.name AS device_name, d.user_id,
				CONCAT(u.name,' ',u.surname) AS user_name
			FROM device_admins da
			JOIN devices d ON da.device_id = d.id
			JOIN users u ON d.user_id = u.id
			WHERE da.user_id = ?
			ORDER BY d.name
		", array($user_id));
	}
	
	/**
	 * Gets all devices admins from database. Database query returns member's device parameters (id, name, type),
	 * owner of device (user name and surname), MAC addresses of interfaces, names of segments and ip addresses.
	 * 
	 * @param array $params
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_all_devices_admins($params = array(), $user_id = NULL)
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
				SELECT dau.id, d.id AS device_id, d.type,
					IFNULL(f.translated_term, e.value) AS type_name,
					d.name, d.name AS device_name, u.id AS user_id,
					u.name AS user_name, u.surname AS user_surname, u.login AS user_login,
					d.login, d.password, d.price, d.trade_name, d.payment_rate,
					d.buy_date, m.name AS device_member_name, s.street, t.town,
					ap.street_number, d.comment, 1 AS redirection, 1 AS email,
					1 AS sms, dam.id AS member_id, dam.type as member_type,
					whitelisted, IF(mi.id IS NOT NULL, 1, 0) AS interrupt,
					dam.name AS member_name, dau.id AS dau_id, 
					CONCAT(dau.name, ' ', dau.surname) AS dau_name,
					m.notification_by_redirection, m.notification_by_email,
					m.notification_by_sms
					$select_cloud_iface
				FROM devices d
				JOIN users u ON d.user_id = u.id
				JOIN members m ON u.member_id = m.id
				JOIN device_admins da ON da.device_id = d.id
				JOIN users dau ON da.user_id = dau.id
				JOIN members dam ON dau.member_id = dam.id
				LEFT JOIN address_points ap ON d.address_point_id = ap.id
				LEFT JOIN streets s ON ap.street_id = s.id
				LEFT JOIN towns t ON ap.town_id = t.id
				LEFT JOIN enum_types e ON d.type = e.id
				LEFT JOIN translations f ON lang = ? AND e.value = f.original_term
				LEFT JOIN
				(
					SELECT mi.id, mi.member_id
					FROM membership_interrupts mi
					LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id
					WHERE  mf.activation_date <= CURDATE() AND mf.deactivation_date >= CURDATE()
				) mi ON mi.member_id = dam.id
				LEFT JOIN
				(
					SELECT m2.id AS member_id, IF(mw.member_id IS NULL, 0, 2 - mw.permanent) AS whitelisted
					FROM members m2
					LEFT JOIN members_whitelists mw ON mw.member_id = m2.id
						AND mw.since <= CURDATE() AND mw.until >= CURDATE()
				) ip ON ip.member_id = dam.id
				$join_cloud_iface
			) d
			$where
			GROUP BY dau_id
			ORDER BY $order_by $order_by_direction
			$limit
		", Config::get('lang'));
	} // end of get_all_devices_admins
	
	/**
	 * Count of all devices admins
	 * @param array $filter_values
	 * @return integer
	 */
	public function count_all_devices_admins($filter_sql = '')
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
			SELECT COUNT(member_id) AS total FROM
			(
				SELECT member_id FROM
				(
					SELECT d.id AS device_id, d.type,
						IFNULL(f.translated_term, e.value) AS type_name, d.name,
						d.name AS device_name, u.id AS user_id, u.name AS user_name,
						u.surname AS user_surname, u.login AS user_login,
						d.login, d.password, d.price, d.trade_name, d.payment_rate,
						d.buy_date, m.name AS member_name, s.street, t.town,
						ap.street_number, d.comment, dam.id AS member_id $select_cloud_iface
					FROM devices d
					JOIN users u ON d.user_id = u.id
					JOIN members m ON u.member_id = m.id
					LEFT JOIN address_points ap ON d.address_point_id = ap.id
					LEFT JOIN streets s ON ap.street_id = s.id
					LEFT JOIN towns t ON ap.town_id = t.id
					LEFT JOIN enum_types e ON d.type = e.id
					LEFT JOIN translations f ON lang = ? AND e.value = f.original_term
					JOIN device_admins da ON da.device_id = d.id
					JOIN users dau ON da.user_id = dau.id
					JOIN members dam ON dau.member_id = dam.id
					$join_cloud_iface
				) d
				$where
				GROUP BY member_id
			) count
		", Config::get('lang'))->current()->total;
	} // end of count_all_devices_admins
}
