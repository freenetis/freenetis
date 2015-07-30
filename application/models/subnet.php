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
 * @property integer $dhcp
 * @property boolean $dhcp_expired
 * @property integer $dns
 * @property Subnets_owner_Model $subnets_owner
 * @property ORM_Iterator $clouds
 * @property ORM_Iterator $ip_addresses
 * @property ORM_Iterator $allowed_subnets
 * @property ORM_Iterator $connection_requests
 */
class Subnet_Model extends ORM
{
	protected $has_one = array('subnets_owner');
	protected $has_many = array('ip_addresses', 'allowed_subnets', 'connection_requests');
	protected $has_and_belongs_to_many = array('clouds');
	
	/**
	 * Sets all subnets as (not) expired.
	 * 
	 * @param int $flag expired (1) or not (0)  [optional]
	 */
	public function set_expired_all_subnets($flag = 1)
	{
		$this->db->query("
			UPDATE subnets
			SET dhcp_expired = ?
			WHERE dhcp = 1
		", $flag);
	}
	
	/**
	 * Sets subnets as (not) expired.
	 * 
	 * @param array|int $subnets Multiple subnet IDs or a single subnet ID
	 * @param int $flag expired (1) or not (0)  [optional]
	 */
	public function set_expired_subnets($subnets, $flag = 1)
	{
		if (!is_array($subnets))
		{
			$subnets = array($subnets);
		}
		
		if (count($subnets))
		{
			$this->db->query("
				UPDATE subnets s
				SET s.dhcp_expired = ?
				WHERE s.dhcp > 0 AND 
					s.id IN (" . implode(',', array_map('intval', $subnets)) . ")
			", $flag);
		}
	}
	
	/**
	 * Sets subnets of device as (not) expired
	 * 
	 * @author Michal Kliment <kliment@freenetis.org>
	 * @param type $device_id ID of device
	 * @param type $flag sexpired (1) or not (0)  [optional] 
	 */
	public function set_expired_subnets_of_device($device_id, $flag = 1)
	{
		$this->db->query("
			UPDATE subnets s
			JOIN ip_addresses ip ON ip.subnet_id = s.id AND ip.gateway = 1
			JOIN ifaces i ON ip.iface_id = i.id
			SET s.dhcp_expired = ?
			WHERE s.dhcp > 0 AND i.device_id = ?
		", array($flag, $device_id));
	}
	
	/**
	 * Check if any of device subnet on that the device is gateway is expired
	 * 
	 * @param int $device_id
	 * @return boolean
	 */
	public function is_any_subnet_of_device_expired($device_id)
	{
		return $this->db->query("
			SELECT COUNT(*) AS c
			FROM ifaces i
			JOIN ip_addresses ip ON i.id = ip.iface_id
			JOIN subnets s ON s.id = ip.subnet_id
			WHERE i.device_id = ? AND s.dhcp > 0 AND s.dhcp_expired > 0
				AND ip.gateway > 0
		", $device_id)->current()->c > 0;
	}
	
	/**
	 * Check if the MAC address is unique in the subnet.
	 * 
	 * @param string $mac MAC address
	 * @param int $ip_address_id Iface ID - for edit purposes
	 * @param int $subnet_id Subnet id [Optional - deafult self ID]
	 * @return bool
	 */
	public function is_mac_unique_in_subnet($mac, $ip_address_id = NULL, $subnet_id = NULL)
	{
		if ($subnet_id === NULL)
		{
			$subnet_id = $this->id;
		}
		
		$ip_address = new Ip_address_Model($ip_address_id);
		$max_count = 0;
		
		if ($ip_address && $ip_address->id && ($ip_address->iface->mac == $mac))
		{
			$max_count = 1;
		}
		
		$result = $this->db->query("
			SELECT COUNT(*) AS count
			FROM subnets s
			JOIN ip_addresses ip ON ip.subnet_id = s.id
			JOIN ifaces i ON i.id = ip.iface_id
			WHERE s.id = ? AND i.mac = ?
		", $subnet_id, $mac);
		
		return ($result->count() && $result->current()->count <= $max_count);
	}
	
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
						WHERE subnet_id = s.id
					)/((~inet_aton(s.netmask) & 0xffffffff)+1)*100,1),0) AS used,
					m.id AS member_id, m.name AS member_name, dhcp, dns, qos
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
                
                if ($total_available > 1)
                {
                    for ($i = 1; $i <= $total_available; $i++)
                      	$ip_queries[] = "SELECT '".long2ip($network+$i)."' AS ip_address";
                }
                // for special 1-host subnet (mask /32) add only 1 IP address with network address (#507)
                else
                {
                    $ip_queries[] = "SELECT '".long2ip($network)."' AS ip_address";
                }
		
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
				INET_NTOA(INET_ATON(ip.ip_address)+(~inet_aton(netmask) & 0xffffffff)-1) AS broadcast,
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
	
	/**
	 * Get gateway of subnet
	 * 
	 * @author Michal Kliment <kliment@freenetis.org>
	 * @param type $subnet_id
	 * @return type
	 */
	public function get_gateway($subnet_id = NULL)
	{
		if (!$subnet_id && $this->id)
			$subnet_id = $this->id;
		
		return ORM::factory('ip_address')->get_gateway_of_subnet($subnet_id);
	}
	
	/**
	 * Check whether subnet has gateway
	 * 
	 * @author Michal Kliment
	 * @param type $subnet_id
	 * @return type
	 */
	public function has_gateway($subnet_id = NULL)
	{
		if (!$subnet_id && $this->id)
			$subnet_id = $this->id;
		
		$gateway = $this->get_gateway($subnet_id);
		
		return ($gateway && $gateway->id);
	}
	
	/**
	 * This method is used for determining whether the user is connected
	 * from registered connection. If he is the null is returned.
	 * If not then subnet from which he is connected is searched.
	 * If the user may obtain this IP from the searched subnet
	 * the ID of subnet is returned. (but there must not be any connection
	 * request on this connection already in tha database)
	 * 
	 * @author Ondřej Fibich
	 * @param string $ip_address IP address from which the connection request is made
	 * @return int|null Subnet ID or null if invalid request was made
	 */
	public function get_subnet_for_connection_request($ip_address)
	{
		$result = $this->db->query("
			SELECT s.subnet_id FROM (
				SELECT s.id AS subnet_id
				FROM subnets s
				WHERE inet_aton(s.netmask) & inet_aton(?) = inet_aton(s.network_address)
			) s
			LEFT JOIN ip_addresses ip ON ip.subnet_id = s.subnet_id AND inet_aton(ip.ip_address) = inet_aton(?)
			WHERE ? NOT IN (
				SELECT cr.ip_address FROM connection_requests cr
				WHERE cr.state = ?
			)
			GROUP BY s.subnet_id
			HAVING COUNT(ip.id) = 0
		", $ip_address, $ip_address, $ip_address, Connection_request_Model::STATE_UNDECIDED);
		
		return ($result->count() > 0 ? $result->current()->subnet_id : NULL);
	}
	
	/**
	 * Returns all subnets with existing gateway
	 * 
	 * @author Michal Kliment
	 * @return Mysql_Result
	 */
	public function get_all_subnets_with_gateway()
	{
		return $this->db->query("
			SELECT s.*
			FROM subnets s
			JOIN ip_addresses ip ON ip.subnet_id = s.id AND ip.gateway = 1
		");
	}
	
	/**
	 * Return all subnets on which DHCP is running and has no gateway.
	 * 
	 * @return Mysql_Result
	 */
	public function get_all_dhcp_subnets_without_gateway()
	{
		return $this->db->query("
			SELECT s.*
			FROM subnets s
			WHERE s.dhcp > 0 AND s.id NOT IN (
				SELECT s2.id
				FROM subnets s2
				JOIN ip_addresses ip ON ip.subnet_id = s2.id
					AND ip.gateway = 1
			)
		");
	}
	
}
