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
 * IP addreess of interface. Addresses may be grouped to a subnet with a same mask.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $iface_id
 * @property Iface_Model $iface
 * @property integer $subnet_id	 	 	 
 * @property Subnet_Model $subnet
 * @property string $ip_address
 * @property bool $dhcp	 	 	 	 	 
 * @property bool $gateway	 	 	 	 	 	 
 * @property bool $service 	 	 
 * @property integer $whitelisted
 * @property integer $member_id
 * @property Member_Model $member
 */
class Ip_address_Model extends ORM
{
	protected $belongs_to = array('iface', 'subnet', 'member');
	
	/**
	 * No whitelist means, that is ip address can be redirected in any time
	 * (typical state)
	 */
	const NO_WHITELIST			= 0;
	
	/**
	 * IP address is in permanent whitelist - it is never redirected (useful for
	 * special members), can be redirected only by message which ignores whitelist
	 */
	const PERNAMENT_WHITELIST	= 1;
	
	/**
	 * IP address is in temporary whitelist - for limited period of time it is
	 * not redirected, for example when someone should be redirected, but he has
	 * to pay using internet banking
	 */
	const TEMPORARY_WHITELIST	= 2;
	
	/**
	 * White list type names
	 * 
	 * @var array
	 */
	public static $whitelist_types = array
	(
		self::NO_WHITELIST			=> 'No whitelist',
		self::PERNAMENT_WHITELIST	=> 'Permanent whitelist',
		self::TEMPORARY_WHITELIST	=> 'Temporary whitelist'
	);
	
	/**
	 * Gets translated whitelist types
	 *  
	 * @return array
	 */
	public static function get_whitelist_types()
	{
		return array_map('__', self::$whitelist_types);
	}
	
	/**
	 * Gets translated whitelist types
	 * 
	 * @param integer $white_list_type
	 * @return string
	 */
	public function get_whitelist_type($white_list_type = NULL)
	{
		if (empty($white_list_type) && $this->id)
		{
			$white_list_type = $this->whitelisted;
		}
		
		if (array_key_exists($white_list_type, self::$whitelist_types))
		{
			return __(self::$whitelist_types[$white_list_type]);
		}
		
		return __(self::$whitelist_types[self::NO_WHITELIST]);
	}
	
	/**
	 * Gives IP address is string is writted
	 * 
	 * @return string 
	 */
	public function __toString()
	{
		if (!$this->id)
			return '';
		
		return $this->ip_address;
	}
	
	/**
	 * Function gets all ip addresses.
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_all_ip_addresses(
			$limit_from = 0, $limit_results = 50, $order_by = 'ip_address',
			$order_by_direction = 'ASC', $filter_sql = '')
	{
		$where = '';
		
		// filter
		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
			
		// order by check
		if ($order_by == 'ip_address')
		{
			$order_by = 'inet_aton(ip.ip_address)';
		}
		else if (!$this->has_column($order_by))
		{
			$order_by = 'id';
		}
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
			SELECT * FROM
			(
				SELECT ip.*, ip.id AS ip_address_id,
					i.name AS iface_name, s.name as subnet_name,
					d.id AS device_id, d.name AS device_name
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON i.id = ip.iface_id 
				LEFT JOIN ifaces_vlans iv ON iv.iface_id = i.id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN subnets s ON s.id = ip.subnet_id
				WHERE ip.member_id IS NULL
			) ip
			$where
			ORDER BY $order_by $order_by_direction
			LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		");
	}
	
	/**
	 * Function counts all ip addresses.
	 * 
	 * @param array $filter_values
	 * @return integer
	 */
	public function count_all_ip_addresses($filter_sql = '')
	{
		$where = '';
		
		// filter
		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		// query
		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM
			(
				SELECT ip.*, ip.id AS ip_address_id,
					i.name AS iface_name, s.name as subnet_name,
					d.id AS device_id, d.name AS device_name
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON i.id = ip.iface_id 
				LEFT JOIN ifaces_vlans iv ON iv.iface_id = i.id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN subnets s ON s.id = ip.subnet_id
				WHERE ip.member_id IS NULL
			) ip
			$where
		")->current()->total;
	}

	/**
	 * Function gets gateway of gievn subnet
	 * 
	 * @param string $network_address
	 * @return Ip_address_Model
	 */
	public function get_gateway_of_subnet($subnet_id)
	{
		$result = $this->db->query("
			SELECT * FROM ip_addresses ip
			WHERE gateway = 1 AND subnet_id = ?
			LIMIT 0,1
		", $subnet_id);
		
		return ($result && $result->current()) ? $result->current() : FALSE;
	}
	
	/**
	 * Gets all allowed IP addresses.
	 * These are registered IP addresses, which have no redirection set.
	 * Unknown IP addresses (not present in system) and
	 * IP addresses with a redirection set are not exported.
	 *
	 * @see Web_interface_Controller#allowed_ip_addresses
	 * @return Mysql_Result
	 */
	public function get_allowed_ip_addresses()
	{
		return $this->db->query("
			SELECT DISTINCT ip.ip_address
			FROM ip_addresses ip
			LEFT JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
			WHERE mip.ip_address_id IS NULL OR
			(
				whitelisted > 0 AND ip_address NOT IN
				(
					SELECT DISTINCT ip.ip_address
					FROM ip_addresses ip
					JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
					JOIN messages m ON m.id = mip.message_id
					WHERE m.ignore_whitelist = 1
				)
			)
			ORDER BY INET_ATON(ip_address)
		");
	}
	
	/**
	 * Same as previous method, but return unallowed ip addresses
	 * 
	 * @author Michal Kliment
	 * @see Web_interface_Controller#unallowed_ip_addresses
	 * @return type 
	 */
	public function get_unallowed_ip_addresses()
	{
		return $this->db->query("
			SELECT ip_address
			FROM ip_addresses ip
			JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
			JOIN messages m ON mip.message_id = m.id
			WHERE IFNULL(ip.whitelisted,0) = 0 OR m.ignore_whitelist = 1
			GROUP BY ip.id
			ORDER BY INET_ATON(ip_address)
		");
	}
	
	/**
	 * Function gets all ip address of interfaces of devices of users of given member.
	 * 
	 * @param integer $member_id
	 * @param integer $subnet_id
	 * @param integer $cloud_id
	 * @return Mysql_Result
	 */
	public function get_ip_addresses_of_member($member_id, $subnet_id = NULL, $cloud_id = NULL)
	{
		$where = "";
		
		if ($subnet_id)
		{
			$where = "AND ip.subnet_id = ".intval($subnet_id);
		}
		
		if ($cloud_id)
		{
			$where .= " AND cs.cloud_id = ".intval($cloud_id);
		}
		
		return $this->db->query("
				SELECT ip.*, i.device_id
				FROM ip_addresses ip
				LEFT JOIN subnets s ON ip.subnet_id = s.id
				LEFT JOIN clouds_subnets cs ON cs.subnet_id = s.id
				LEFT JOIN ifaces i ON i.id = ip.iface_id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN users u ON u.id = d.user_id
				WHERE IFNULL(u.member_id, ip.member_id) = ? $where
		", $member_id);
	}
	
	/**
	 * Gets all ip addresses of device.
	 * 
	 * @author Jiri Svitak
	 * @param integer $device_id
	 * @return Mysql_Result
	 */
	public function get_ip_addresses_of_device($device_id)
	{
		return $this->db->query("
				SELECT
					ip.*,
					ip.id AS ip_address_id,
					s.name AS subnet_name,
					32-log2((~inet_aton(netmask) & 0xffffffff) + 1) AS subnet_range,
					s.network_address AS subnet_network,
					i.name AS iface_name
				FROM ip_addresses ip
				LEFT JOIN subnets s ON s.id = ip.subnet_id
				JOIN ifaces i ON i.id = ip.iface_id
				WHERE i.device_id = ?
				ORDER BY INET_ATON(ip_address)
		", $device_id);		
	}
	
	/**
	 * Function gets ip addresses of subnet.
	 * 
	 * @param integer $subnet_id
	 * @return Mysql_Result
	 */
	public function get_ip_addresses_of_subnet($subnet_id)
	{
		return $this->db->query("
				SELECT ip.id, ip.id AS ip_address_id, ip.ip_address,
					ip.gateway, d.name AS device_name, d.id AS device_id,
					m.name AS member_name, m.id AS member_id, a.balance,
					u.name AS user_name, u.surname AS user_surname, i.mac
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON ip.iface_id = i.id
				LEFT JOIN devices d ON i.device_id = d.id
				LEFT JOIN users u ON d.user_id = u.id
				LEFT JOIN members m ON u.member_id = m.id
				LEFT JOIN accounts a ON a.member_id = m.id AND account_attribute_id = ?
				WHERE ip.subnet_id = ? AND ip.member_id IS NULL
				ORDER BY inet_aton(ip.ip_address)
		", Account_attribute_Model::CREDIT, $subnet_id);
	}
	
	/**
	 * Gets all IDs of IP addresses of member who have currently interrupted
	 * membership. These IP addresses are redirected.
	 * 
	 * @author Jiri Svitak
	 * @return Mysql_Result
	 */
	public function get_ip_addresses_with_interrupted_membership()
	{
		return $this->db->query("
			SELECT ip.id
			FROM
			(
				SELECT ip.id, ip.ip_address, ip.whitelisted,
					s.name AS subnet_name,
					IFNULL(u.member_id, ip.member_id) AS member_id
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON ip.iface_id = i.id
				LEFT JOIN subnets s ON s.id = ip.subnet_id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN users u ON u.id = d.user_id
			) ip
			JOIN members m ON m.id = ip.member_id
			JOIN membership_interrupts mi ON mi.member_id = m.id
			JOIN members_fees mf ON mi.members_fee_id = mf.id
			JOIN fees f ON f.id = mf.fee_id
			JOIN accounts a ON a.member_id = m.id
			WHERE mf.activation_date <= CURDATE() AND f.special_type_id = ? AND
				CURDATE() <= mf.deactivation_date
		", Fee_Model::MEMBERSHIP_INTERRUPT);
	}
	
	/**
	 * Returns all IDs of IP addresses with unallowed connecting place
	 * 
	 * @author Michal Kliment
	 * @return Mysql_Result
	 */
	public function get_ip_addresses_with_unallowed_connecting_place()
	{
		return $this->db->query("
			SELECT q.id, ip_address FROM
			(
				SELECT ip.subnet_id, ip.id, ip.ip_address,
					IFNULL(ip.member_id, u.member_id) AS member_id
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON ip.iface_id = i.id
				LEFT JOIN devices d ON i.device_id = d.id
				LEFT JOIN users u ON d.user_id = u.id
			) q
			WHERE q.member_id <> 1 AND q.subnet_id NOT IN
			(
				SELECT a.subnet_id
				FROM allowed_subnets a
				WHERE a.subnet_id = q.subnet_id AND
					a.member_id = q.member_id AND enabled = 1
			)
		");
	}
	
	/**
	 * Gets all IP addresses of members who have credit negative credit status.
	 * 
	 * @author Jiri Svitak
	 * @param duble $debtor_boundary
	 * @return Mysql_Result
	 */
	public function get_ip_addresses_of_debtors($debtor_boundary)
	{
		return $this->db->query("
			SELECT ip.id, ip.ip_address, ip.whitelisted, subnet_name,
				m.name AS member_name, a.balance,
				(
					SELECT GROUP_CONCAT(vs.variable_symbol) AS variable_symbol
					FROM variable_symbols vs
					LEFT JOIN accounts a ON a.id = vs.account_id
					WHERE a.member_id = m.id
				) AS variable_symbol
			FROM
			(
				SELECT ip.id, ip.ip_address, ip.whitelisted, s.name AS subnet_name,
					IFNULL(u.member_id, ip.member_id) AS member_id
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON ip.iface_id = i.id
				JOIN subnets s ON s.id = ip.subnet_id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN users u ON u.id = d.user_id
			) ip
			JOIN members m ON m.id = ip.member_id
			JOIN accounts a ON a.member_id = m.id AND m.id <> ?
			WHERE a.balance < ?
			AND DATEDIFF(CURDATE(), m.entrance_date) >= ?
			AND (ip.whitelisted IS NULL OR ip.whitelisted = 0)
		", array
		(
			Member_Model::ASSOCIATION, $debtor_boundary,
			Settings::get('initial_debtor_immunity')
		));
	}

	/**
	 * Gets all IP addresses of members who have low credit and should pay
	 * in short time.
	 * 
	 * @author Jiri Svitak
	 * @param double $payment_notice_boundary
	 * @param double $debtor_boundary
	 * @return Mysql_Result
	 */
	public function get_ip_addresses_of_almostdebtors($payment_notice_boundary, $debtor_boundary)
	{
		return $this->db->query("
				SELECT ip.id, ip.ip_address, ip.whitelisted, subnet_name,
					m.name AS member_name, a.balance,
					(
						SELECT GROUP_CONCAT(vs.variable_symbol) AS variable_symbol
						FROM variable_symbols vs
						LEFT JOIN accounts a ON a.id = vs.account_id
						WHERE a.member_id = m.id
					) AS variable_symbol
				FROM
				(
					SELECT ip.id, ip.ip_address, ip.whitelisted,
					s.name AS subnet_name,
					IFNULL(u.member_id, ip.member_id) AS member_id
					FROM ip_addresses ip
					LEFT JOIN ifaces i ON ip.iface_id = i.id
					JOIN subnets s ON s.id = ip.subnet_id
					LEFT JOIN devices d ON d.id = i.device_id
					LEFT JOIN users u ON u.id = d.user_id
				) ip
				JOIN members m ON m.id = ip.member_id
				JOIN accounts a ON a.member_id = m.id AND m.id <> ?
				WHERE (
					DATEDIFF(CURDATE(), m.entrance_date) >= ? AND
					a.balance >= ? OR DATEDIFF(CURDATE(), m.entrance_date) < ? AND
					DATEDIFF(CURDATE(), m.entrance_date) >= ?
				)
				AND a.balance < ?
				AND (ip.whitelisted IS NULL OR ip.whitelisted = 0)
		", array
		(
			Member_Model::ASSOCIATION, Settings::get('initial_debtor_immunity'),
			$debtor_boundary, Settings::get('initial_debtor_immunity'),
			Settings::get('initial_immunity'), $payment_notice_boundary
		));
	}

    /**
     * Returns all ip addresses which can cancel redirect by themselves
     *
     * @author Michal Kliment
     * @return Mysql_Result
     */
    public function get_ip_addresses_with_self_cancel()
    {
        return $this->db->query("
            SELECT * FROM
            (
                SELECT * FROM
                (
                    SELECT ip.*, IFNULL(m.self_cancel, 0) AS self_cancel
                    FROM ip_addresses ip
                    JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
                    JOIN messages m ON mip.message_id = m.id
                    ORDER BY self_cancel ASC
                ) ip
                GROUP BY ip.id
            ) ip
            WHERE self_cancel > ?
        ", Message_Model::SELF_CANCEL_DISABLED);
    }

	/**
	 * Gets all ip addresses including their redirections.
	 * Used in member's profile screen.
	 * 
	 * @author Jiri Svitak
	 * @param integer $member_id
	 * @param integer $sql_offset
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_ips_and_redirections_of_member(
			$member_id, $sql_offset, $limit_results,
			$order_by, $order_by_direction)
	{
		// order by
		if (strtolower($order_by) == 'ip_address')
		{
			$order_by = 'inet_aton(ip.ip_address)';
		}
		else
		{
			$order_by = $this->db->escape_column($order_by);
		}
		
		// order by direction
		if (strtolower($order_by_direction) != 'asc')
		{
			$order_by_direction = 'desc';
		}
		
		
		return $this->db->query("
			SELECT ip.id AS ip_address_id, ip.ip_address, ip.whitelisted,
				m.id AS message_id, m.name AS message, m.type, ? AS member_id,
				mip.datetime AS active_redir_datetime
			FROM ip_addresses ip
			LEFT JOIN ifaces i ON ip.iface_id = i.id
			LEFT JOIN devices d ON i.device_id = d.id
			LEFT JOIN users u ON d.user_id = u.id
			LEFT JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
			LEFT JOIN messages m ON m.id = mip.message_id
			WHERE u.member_id = ? OR ip.member_id = ?
			ORDER BY $order_by $order_by_direction,
				m.self_cancel DESC, mip.datetime ASC
			LIMIT " . intval($sql_offset) . ", " . intval($limit_results) . "
		", $member_id, $member_id, $member_id);
	}
	
	/**
	 * Gets count of all ip addresses including their redirections.
	 * Used in member's profile screen.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $member_id
	 * @return integer
	 */
	public function count_ips_and_redirections_of_member($member_id)
	{
		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM ip_addresses ip
			LEFT JOIN ifaces i ON ip.iface_id = i.id
			LEFT JOIN devices d ON i.device_id = d.id
			LEFT JOIN users u ON d.user_id = u.id
			LEFT JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
			LEFT JOIN messages m ON m.id = mip.message_id
			WHERE u.member_id = ? OR ip.member_id = ?
		", $member_id, $member_id, $member_id)->current()->total;
	}

	/**
	 * Counts all ip addresses by member and subnet
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @param integer $subnet_id
	 * @return integer
	 */
	public function count_all_ip_addresses_by_member_and_subnet($member_id, $subnet_id)
	{
		return $this->db->query("
			SELECT COUNT(*) AS count
			FROM ip_addresses ip
			JOIN ifaces i ON ip.iface_id = i.id
			JOIN devices d ON i.device_id = d.id
			JOIN users u ON d.user_id = u.id
			WHERE u.member_id = ? AND ip.subnet_id = ?
		", array($member_id, $subnet_id))->current()->count;
	}
	
	/**
	 * Returns all ip addresses of iface
	 * 
	 * @author Michal Kliment
	 * @param integer $iface_id
	 * @return Mysql_Result 
	 */
	public function get_all_ip_addresses_of_iface($iface_id)
	{
		return $this->db->query("
			SELECT ip.id, ip.ip_address, s.name AS subnet_name, s.id AS subnet_id
			FROM ip_addresses ip
			LEFT JOIN subnets s ON s.id = ip.subnet_id
			WHERE ip.iface_id = ?
			ORDER BY id ASC
		", array($iface_id));
	}

	/**
	 * Removes all IP addresses from temporary whitelist. Used when bank statement
	 * is imported, then all whitelisted whould have payed their fees, so they are
	 * no longer protected from redirection.
	 * 
	 * @author Jiri Svitak
	 */
	public function clean_temporary_whitelist()
	{
		$this->db->query("
			UPDATE ip_addresses
			SET whitelisted = ?
			WHERE whitelisted = ? 
		", self::NO_WHITELIST, self::TEMPORARY_WHITELIST);
	}
	
	/**
	 * Deletes all IP addresses by given subnet and member
	 * 
	 * @author Michal Kliment
	 * @param integer $subnet_id
	 * @param integer $member_id 
	 */
	public function delete_ip_addresses_by_subnet_member($subnet_id, $member_id)
	{
		$this->db->query("
			DELETE FROM ip_addresses
			WHERE subnet_id = ? AND member_id IS NOT NULL AND member_id = ?
		", $subnet_id, $member_id);
	}
	
	/**
	 * Counts all ip addresses without member by given subnet
	 * 
	 * @author Michal Kliment
	 * @param integer $subnet_id
	 * @return integer 
	 */
	public function count_all_ip_addresses_without_member_by_subnet($subnet_id)
	{
		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM ip_addresses ip
			WHERE member_id IS NULL AND subnet_id = ?
		", $subnet_id)->current()->total;
	}
	
	/**
	 * Deletes IP address
	 * 
	 * @author Michal Kliment
	 * @param string $ip_address 
	 */
	public function delete_ip_address_with_member($ip_address)
	{
		$this->db->query("
			DELETE FROM ip_addresses
			WHERE ip_address = ? AND member_id IS NOT NULL
		", $ip_address);
	}
	
	/**
	 * Deletes all IP addresses of subnet with owner
	 * 
	 * @author Michal Kliment <kliment@freenetis.org>
	 * @param type $subnet_id
	 */
	public function delete_ip_addresses_of_subnet_with_owner($subnet_id)
	{
		$this->db->query("
			DELETE FROM ip_addresses
			WHERE subnet_id = ? AND member_id IS NOT NULL
		", $subnet_id);
	}
	
	/**
	 * Sets whitelist
	 *
	 * @param ineteger $whitelist
	 * @param integer $ip_address_id
	 * @return boolean
	 */
	public function set_whitelist($whitelist, $ip_address_id = NULL)
	{
		if (!$ip_address_id && isset($this))
			$ip_address_id = $this->id;
		
		return $this->db->query("
			UPDATE ip_addresses ip
			SET whitelisted = ?
			WHERE id = ?
		", array($whitelist, $ip_address_id));
	}
	
	/**
	 * Returns ip addresses of members with set-up qos ceil or rate
	 * 
	 * @author Michal Kliment
	 * @return MySQL Result
	 */
	public function get_ip_addresses_qos_ceil_rate()
	{
		return $this->db->query("
			SELECT m.id AS member_id, ip_address
			FROM
			(
				SELECT ip.ip_address, IFNULL(u.member_id, ip.member_id) AS member_id
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON ip.iface_id = i.id
				LEFT JOIN devices d ON i.device_id = d.id
				LEFT JOIN users u ON d.user_id = u.id
			) ip
			JOIN members m ON ip.member_id = m.id AND (m.qos_ceil OR m.qos_rate)
			ORDER BY m.id
		");
	}
	
	/**
	 * Gets list of IP addresses ordered by IP address grouped by subnet
	 * 
	 * @author Ondřej Fibich
	 * @return array 
	 */
	public function select_list_grouped()
	{
		$ips = $this->db->query("
			SELECT ip.id, ip.ip_address AS name,
				 CONCAT(s.network_address, '/',
						32-log2((~inet_aton(s.netmask) & 0xffffffff) + 1),
						': ', s.name) AS subnet_name
			FROM ip_addresses ip
			JOIN subnets s ON s.id = ip.subnet_id
			ORDER BY INET_ATON(ip_address)
		");
		
		$arr_ip = array();
		
		foreach ($ips as $ip)
		{
			$arr_ip[$ip->subnet_name][$ip->id] = $ip->name;
		}
		
		return $arr_ip;
	}
	
	/**
	 * Returns first IP address of subnet
	 * 
	 * @author Michal Kliment <kliment@freenetis.org>
	 * @param type $subnet_id
	 * @return type
	 */
	public function get_first_ip_address_of_subnet($subnet_id, $without_owner = FALSE)
	{
		// not return IP addresses with owner
		$WHERE = ($without_owner) ? "AND member_id IS NULL" : "";
		
		$result = $this->db->query("
		    SELECT ip.*
		    FROM ip_addresses ip
		    WHERE subnet_id = ? $WHERE
		    ORDER BY INET_ATON(ip_address)
		", array($subnet_id));
	    
		return ($result && $result->current()) ? $result->current() : FALSE;
	}
	
	/**
	 * Returns last IP address of subnet
	 * 
	 * @author Michal Kliment <kliment@freenetis.org>
	 * @param type $subnet_id
	 * @return type
	 */
	public function get_last_ip_address_of_subnet($subnet_id, $without_owner = FALSE)
	{
		// not return IP addresses with owner
		$WHERE = ($without_owner) ? "AND member_id IS NULL" : "";
		
		$result = $this->db->query("
		    SELECT ip.*
		    FROM ip_addresses ip
		    WHERE subnet_id = ? $WHERE
		    ORDER BY INET_ATON(ip_address) DESC
		", array($subnet_id));
	    
		return ($result && $result->current()) ? $result->current() : FALSE;
	}
}
