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
 * Subnet is part of net grouped to clouds.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $OSPF_area_id
 * @property string $name
 * @property string $network_address
 * @property string $netmask
 * @property integer $redirect
 * @property Subnets_owner_Model $subnets_owner
 * @property ORM_Iterator $clouds
 * @property ORM_Iterator $ip_addresses
 * @property ORM_Iterator $allowed_subnets
 */
class Subnet_Model extends ORM
{
	protected $has_one = array('subnets_owner');
	protected $has_many = array('ip_addresses', 'allowed_subnets');
	protected $has_and_belongs_to_many = array('clouds');
	
	/**
	 * Gets mask and net of subnet
	 *
	 * @param integer $subnet_id
	 * @return ORM_Iterator
	 */
	public function get_net_and_mask_of_subnet($subnet_id = NULL)
	{
		if (empty($subnet_id))
		{
			$subnet_id = $this->id;
		}
		
		return $this->select(
					"inet_aton(network_address) as net",
					"32-log2((~inet_aton(netmask) & 0xffffffff) + 1) as mask"
				)->where('id', $subnet_id)
				->find();
	}
	
	/**
	 * Gets redirected ranges.
	 * Any IP address belonging to these subnet ranges can be redirected.
	 *
	 * @see Web_interface_Controller
	 * @return Mysql_Result
	 */
	public function get_redirected_ranges()
	{
		return $this->db->query("
			SELECT DISTINCT CONCAT(
						network_address, '/',
						32-log2((~inet_aton(netmask) & 0xffffffff) + 1)
				) AS subnet_range
 			FROM subnets
 			WHERE redirect = 1
			ORDER BY INET_ATON(network_address)
 		");
	}

	/**
	 * Function counts all subnets specified by filter.
	 * 
	 * @param array $filter_values
	 * @return integer
	 */
	public function count_all_subnets($filter_sql = '')
	{	
		return $this->get_all_subnets(NULL, NULL, 'id', 'asc', $filter_sql)
				->count();
	}
	
	/**
	 * Function gets all subnets specified by filter.
	 * 
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_all_subnets($limit_from = 0, $limit_results = NULL,
			$order_by = 'id', $order_by_direction = 'ASC', $filter_sql = '')
	{
		$where = '';
		$limit = '';
		
		$order_by = $this->db->escape_column($order_by);
		
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		// filter
		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		// limit is set
		if ($limit_results)
			$limit = "LIMIT ".intval($limit_from) . "," . intval($limit_results);
		
		// query
		return $this->db->query("
			SELECT * FROM
			(
				SELECT	s.id, s.id AS subnet_id, s.redirect, s.name AS subnet_name,
					CONCAT(network_address,'/', 32-log2((~inet_aton(netmask) & 0xffffffff) + 1)) AS cidr_address,
					INET_ATON(network_address) AS cidr,
					network_address, netmask,
					IFNULL(ROUND((
						SELECT COUNT(*)
						FROM ip_addresses
						WHERE subnet_id = s.id AND member_id IS NULL
					)/((~inet_aton(s.netmask) & 0xffffffff)+1)*100,1),0) AS used,
					m.id AS member_id, m.name AS member_name
				FROM subnets s
				LEFT JOIN subnets_owners so ON s.id = so.subnet_id
				LEFT JOIN members m ON so.member_id = m.id
			) s
			$where
			ORDER BY $order_by $order_by_direction
			$limit
		");
	}
	
	/**
	 * Gets clouds of subnet
	 *
	 * @param integer $subnet_id
	 * @return Mysql_Result
	 */
	public function get_clouds_of_subnet($subnet_id)
	{
		return $this->db->query("
				SELECT c.*
				FROM clouds_subnets cs
				LEFT JOIN clouds c ON c.id = cs.cloud_id
				WHERE cs.subnet_id = ?
				ORDER BY c.name
		", $subnet_id);
	}
	
	
	/**
	 * Function finds subnet to which given ip address belongs to.
	 * 
	 * @param string $ip_address
	 * @return Database_Result
	 */
	public function get_subnet_of_ip_address($ip_address)
	{
		$result = $this->db->query("
				SELECT * FROM subnets
				WHERE inet_aton(netmask) & inet_aton(?) = inet_aton(network_address)
		", $ip_address);
		
		return ($result && $result->count()) ? $result->current() : null;
	}
	
	/**
	 * Function tries to find subnet of user. Used in devices/add.
	 * 
	 * @param integer  $user_id
	 * @return Database_Result
	 */
	public function get_subnet_of_user($user_id)
	{
		$result = $this->db->query("
				SELECT DISTINCT s.id, s.name
				FROM subnets s
				JOIN ip_addresses ip ON ip.subnet_id = s.id
				JOIN ifaces i ON i.id = ip.iface_id
				JOIN devices d ON d.id = i.device_id
				WHERE d.user_id = ?
		", $user_id);
		
		return ($result && $result->count()) ? $result->current() : null;
	}
	
	/**
	 * Function gets items of subnet to export.
	 * 
	 * @param integer $subnet_id
	 * @return Mysql_Result
	 */
	public function get_items_of_subnet($subnet_id)
	{
		return $this->db->query("
				SELECT su.id, su.name AS subnet_name, ip.ip_address,
					i.mac, l.name AS link_name, d.name AS device_name,
					IFNULL(f.translated_term, e.value) AS device_type,
					u.name AS user_name, u.surname, m.name AS member_name,
					GROUP_CONCAT(vs.variable_symbol) AS variable_symbol, m.entrance_date, st.street, ap.street_number,
					t.town, t.quarter, t.zip_code, m.comment
				FROM subnets su
				LEFT JOIN ip_addresses ip ON ip.subnet_id = su.id
				LEFT JOIN ifaces i ON i.id = ip.iface_id
				LEFT JOIN links l ON l.id = i.link_id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN enum_types e on d.type = e.id
				LEFT JOIN translations f ON lang = ? AND e.value = f.original_term
				LEFT JOIN users u ON u.id = d.user_id
				LEFT JOIN members m ON m.id = u.member_id
				LEFT JOIN address_points ap ON ap.id = m.address_point_id
				LEFT JOIN towns t ON t.id = ap.town_id
				LEFT JOIN streets st ON st.id = ap.street_id
				LEFT JOIN accounts a ON a.member_id = m.id
				LEFT JOIN variable_symbols vs ON vs.account_id = a.id
				WHERE su.id = ?
				GROUP BY ip_address
				ORDER BY inet_aton(ip_address)
		", Config::get('lang'), $subnet_id);
	}
	
	/**
	 * Function gets all subnets of the ip prefix.
	 * 
	 * @author Lubomir Buben
	 * @param string $ip_prefix
	 * @return Mysql_Result
	 */
	public function get_subnet_of_ip_prefix($ip_prefix)
	{
		return $this->db->query("
				SELECT s.id
				FROM subnets s
				WHERE s.network_address LIKE ? COLLATE utf8_general_ci
		", "$ip_prefix%");
	}
	
	/**
	 * Function gets phone numbers and names of users of subnet to export address book.
	 * 
	 * @author Lubomir Buben
	 * @param integer $subnet_id
	 * @return Mysql_Result
	 */
	public function get_phones_and_names_of_subnet($subnet_id)
	{
		return $this->db->query("
				SELECT DISTINCT(co.value) as phone, CONCAT(u.surname,' ',u.name) as name, u.id
				FROM subnets su
				LEFT JOIN ip_addresses ip ON ip.subnet_id = su.id
				LEFT JOIN ifaces i ON i.id = ip.iface_id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN users u ON u.id = d.user_id
				LEFT JOIN members m ON m.id = u.member_id
				LEFT JOIN users_contacts uc ON uc.user_id = u.id
				LEFT JOIN contacts co ON co.id = uc.contact_id
				WHERE su.id = ? AND co.type = ? AND m.id <> 1 AND m.locked <> 1;
		", array($subnet_id, Contact_Model::TYPE_PHONE));
	}

	/**
	 * Function gets phone numbers of users of subnet.
	 * 
	 * @author Sevcik Roman
	 * @param integer $subnet_id
	 * @return Mysql_Result
	 */
	public function get_phones_of_subnet($subnet_id)
	{
		return $this->db->query("
				SELECT DISTINCT (co.value)
				FROM subnets su
				LEFT JOIN ip_addresses ip ON ip.subnet_id = su.id
				LEFT JOIN ifaces i ON i.id = ip.iface_id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN users u ON u.id = d.user_id
				LEFT JOIN members m ON m.id = u.member_id
				LEFT JOIN users_contacts uc ON uc.user_id = u.id
				LEFT JOIN contacts co ON co.id = uc.contact_id
				WHERE su.id = ? AND co.type = ? AND m.id <> 1 AND m.locked <> 1;
		", array($subnet_id, Contact_Model::TYPE_PHONE));
	}

	/**
	 * Returns all subnets without allowed subnets of member
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @return Mysql_Result
	 */
	public function get_all_subnets_without_allowed_subnets_of_member ($member_id)
	{
		return $this->db->query("
				SELECT
					s.id,
					CONCAT(network_address,'/', 32-log2((~inet_aton(netmask) & 0xffffffff) + 1), ': ',s.name) AS name,
					inet_aton(network_address) AS net
				FROM subnets s
				WHERE s.id NOT IN (SELECT a.subnet_id FROM allowed_subnets a WHERE a.member_id = ?)
				ORDER BY net
		", array($member_id));
	}

	/**
	 * Returns subnet by ip address without member's allowed subnets
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @param string $ip_address
	 * @return Mysql_Result object
	 */
	public function get_subnet_without_allowed_subnets_of_member_by_ip_address($member_id, $ip_address)
	{
			$result = $this->db->query("
					SELECT *
					FROM subnets s
					WHERE inet_aton(netmask) & inet_aton(?) = inet_aton(network_address) AND
						s.id NOT IN (
							SELECT a.subnet_id
							FROM allowed_subnets a
							WHERE a.member_id = ?
						)
			", array($ip_address, $member_id));
			
			return ($result && $result->count()) ? $result->current() : null;
	}
	
	/**
	 * Gets list of subnets ordered by net for dropdown
	 *
	 * @author Ondřej Fibich
	 * @return array[string]
	 */
	public function select_list_by_net()
	{
		// get subnets
		$subnets = $this->db->query("
				SELECT id, name, network_address as net_str,
					32-log2((~inet_aton(netmask) & 0xffffffff) + 1) as mask
				FROM subnets s
				ORDER BY INET_ATON(s.network_address)
		");
		// array
		$arr_subnets = array();
		// for each subnet
		foreach ($subnets as $subnet)
 		{
 			$arr_subnets[$subnet->id] = $subnet->net_str.'/'. $subnet->mask.': '.$subnet->name;
 		}
		// result
		return $arr_subnets;
	}
	
	/**
	 * Checks of overlaps of subnets
	 * 
	 * @author Michal Kliment
	 * @param string $network_address
	 * @param string $netmask
	 * @param type $subnet_id
	 * @return bool 
	 */
	public function check_overlaps_of_subnets ($network_address, $netmask, $subnet_id = NULL)
	{
		$where = "";
		if ($subnet_id)
			$where = "WHERE id <> ".intval($subnet_id);
		
		$results = $this->db->query("
			SELECT COUNT(*) AS total FROM
			(
				SELECT
				INET_ATON(network_address) AS start,
				INET_ATON(network_address) + 
					(~inet_aton(netmask) & 0xffffffff) + 1 AS end
				FROM subnets s
				$where
			) s
			WHERE
			(
				(INET_ATON(?) >= start AND INET_ATON(?) < end) OR
				(
					(INET_ATON(?)+(~INET_ATON(?) & 0xffffffff)) >= start AND
					(INET_ATON(?)+(~INET_ATON(?) & 0xffffffff)) < end
				)
			) OR
			(
				(start >= INET_ATON(?) AND start <= INET_ATON(?)+(~inet_aton(?) & 0xffffffff)) AND
				(end >= INET_ATON(?) AND end <= INET_ATON(?)+(~inet_aton(?) & 0xffffffff))
			)
		",	$network_address, $network_address, $network_address,
			$netmask, $network_address, $netmask,
			$network_address, $network_address, $netmask,
			$network_address, $network_address, $netmask,
			$network_address, $network_address, $netmask);
		
		if ($results && $results->current())
			return (bool) $results->current()->total;
		else
			return false;
	}
	
	/**
	 * Returns array of free IP addresses of subnet
	 * 
	 * @author Michal Kliment
	 * @return array
	 */
	public function get_free_ip_addresses()
	{
		$arr_ip_addresses = array();
		
		if (!$this->id)
			return $arr_ip_addresses;
		
		$network = ip2long($this->network_address);
		$total_available = (~ip2long($this->netmask) & 0xffffffff)-1;
		
		$ip_queries = array();
		for ($i = 1; $i <= $total_available; $i++)
			$ip_queries[] = "SELECT '".long2ip($network+$i)."' AS ip_address";
		
		if (!count($ip_queries))
			return array();
		
		$ip_query = implode("\nUNION\n", $ip_queries);
		
		$ips = $this->db->query("
			SELECT ip_address
			FROM
			(
				$ip_query
			) AS ip
			WHERE ip_address NOT IN
			(
				SELECT ip_address
				FROM ip_addresses
				WHERE subnet_id = ?
			)
		", $this->id);
		
		foreach ($ips as $ip)
			$arr_ip_addresses[] = $ip->ip_address;
		
		return $arr_ip_addresses;
	}
	
	/**
	 * Returns all subnets with owner
	 * 
	 * @author Michal Kliment
	 * @return Mysql_Result
	 */
	public function get_all_subnets_with_owner ()
	{
		return $this
			->select('subnets.*','subnets_owners.member_id')
			->join('subnets_owners','subnets.id', 'subnets_owners.subnet_id')
			->where('subnets_owners.member_id IS NOT NULL')
			->find_all();
	}
	
	/**
	 * Returns all members with at least one ip address in subnet
	 * @param type $subnet_id
	 * @return type 
	 */
	public function get_members ($subnet_id = NULL)
	{
		if (!$subnet_id)
			$subnet_id = $this->id;
		
		return ORM::factory('member')->get_members_of_subnet($subnet_id);
	}
	
	/**
	 * Returns all subnets by device
	 * 
	 * @author Michal Kliment
	 * @param type $device_id
	 * @return type 
	 */
	public function get_all_subnets_by_device ($device_id = NULL, $gateway = FALSE)
	{
		if (!$device_id)
			$device_id = $this->id;
		
		$where = '';
		
		if ($gateway)
			$where .= ' AND gateway = 1';
		
		return $this->db->query("
			SELECT
				s.*, ip.gateway,
				32-log2((~inet_aton(netmask) & 0xffffffff) + 1) AS subnet_range,
				ip.ip_address, INET_NTOA(INET_ATON(ip.ip_address)+1) AS subnet_range_start,
				INET_NTOA(INET_ATON(ip.ip_address)+(~inet_aton(netmask) & 0xffffffff)-2) AS subnet_range_end,
				i.name AS iface
			FROM ip_addresses ip
			JOIN subnets s ON s.id = ip.subnet_id
			JOIN ifaces i ON i.id = ip.iface_id
			WHERE i.device_id = ? $where
			ORDER BY INET_ATON(ip_address)
		", $device_id);
	}
	
	/**
	 * Returns all unique subnets by device
	 * 
	 * @author David Raska
	 * @param type $device_id
	 * @return type 
	 */
	public function get_all_unique_subnets_by_device ($device_id = NULL)
	{
		if (!$device_id)
			$device_id = $this->id;
		
		return $this->db->query("
			SELECT s.id
			FROM ip_addresses ip
			JOIN ifaces i ON i.id = ip.iface_id
			JOIN subnets s ON s.id = ip.subnet_id
			JOIN devices d ON d.id = i.device_id
			JOIN users u ON u.id = d.user_id
			WHERE i.device_id = ? AND s.id NOT IN
			(
				SELECT s2.id
				FROM users u2
				JOIN devices d2 ON d2.user_id = u2.id
				JOIN ifaces i2 ON i2.device_id = d2.id
				JOIN ip_addresses ip2 ON ip2.iface_id = i2.id
				JOIN subnets s2 ON s2.id = ip2.subnet_id
				WHERE d2.id <> ? AND u2.member_id = u.member_id
			)
			ORDER BY INET_ATON(ip_address)
		", $device_id, $device_id);
	}
	
	/**
	 * Returns all dependent subnets of device
	 * 
	 * @author Michal Kliment
	 * @param type $device_id
	 * @return type 
	 */
	public function get_all_dependent_subnets_by_device ($device_id = NULL, $recursive = TRUE)
	{
		$subnets = array();
		
		$device_model = new Device_Model();
		
		// find all subnets of which device is gateway
		$gateway_subnets = arr::from_objects(
			$this->get_all_subnets_by_device($device_id, TRUE), 'id'
		);
		
		$subnets += $gateway_subnets;
		
		if ($recursive && count($gateway_subnets))
		{
			// finds all dependent devices of device's subnets
			$dependent_devices = $device_model
				->get_all_service_devices_of_subnets($gateway_subnets);

			foreach ($dependent_devices as $dependent_device)
			{
				// recursively find dependent subnets of dependent device
				$subnets += $this->get_all_dependent_subnets_by_device($dependent_device->id, $recursive);
			}
		}
		
		return $subnets;
	}
	
}
