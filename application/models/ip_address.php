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
	const PERMANENT_WHITELIST	= 1;
	
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
		self::PERMANENT_WHITELIST	=> 'Permanent whitelist',
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
					d.id AS device_id, d.name AS device_name,
					IF(mw.id IS NULL, 0, 2-mw.permanent) AS whitelisted
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON i.id = ip.iface_id 
				LEFT JOIN ifaces_vlans iv ON iv.iface_id = i.id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN subnets s ON s.id = ip.subnet_id
				LEFT JOIN users u ON u.id = d.user_id
				LEFT JOIN members_whitelists mw ON mw.member_id = u.member_id
					AND mw.since <= CURDATE() AND mw.until >= CURDATE()
				WHERE ip.member_id IS NULL
				GROUP BY ip.id
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
					d.id AS device_id, d.name AS device_name,
					IF(mw.id IS NULL, 0, 2-mw.permanent) AS whitelisted
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON i.id = ip.iface_id 
				LEFT JOIN ifaces_vlans iv ON iv.iface_id = i.id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN subnets s ON s.id = ip.subnet_id
				LEFT JOIN users u ON u.id = d.user_id
				LEFT JOIN members m ON m.id = u.member_id
				LEFT JOIN members_whitelists mw ON mw.member_id = m.id AND
					mw.since <= CURDATE() AND mw.until >= CURDATE()
				WHERE ip.member_id IS NULL
				GROUP BY ip.id
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
	 * Function gets gateway of gievn subnet
	 * 
	 * @param string $network_address
	 * @return Ip_address_Model
	 */
	public function get_dhcp_of_subnet($subnet_id)
	{
		$result = $this->db->query("
			SELECT * FROM ip_addresses ip
			WHERE dhcp = 1 AND subnet_id = ?
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
			WHERE mip.ip_address_id IS NULL
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
			SELECT DISTINCT ip.ip_address
			FROM ip_addresses ip
			JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
		");
	}

	/**
	 * Same as previous method, but return unallowed ip addresses for a specific
	 * message type
	 *
	 * @author Ondrej Fibich
	 * @see Web_interface_Controller#unallowed_ip_addresses
	 * @param int $type message type constant
	 * @return type
	 */
	public function get_unallowed_ip_addresses_by_type($type)
	{
		return $this->db->query("
			SELECT DISTINCT ip.ip_address
			FROM ip_addresses ip
			JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
			JOIN messages m ON m.id = mip.message_id
			WHERE m.type = ?
		", $type);
	}
	
	/**
	 * Function gets all ip address of interfaces of devices of users of given member.
	 * 
	 * @param integer|array $member_id Member ID or array of member IDs
	 * @param integer $subnet_id
	 * @param integer $cloud_id
	 * @param boolean $ignore_member_notif_settings Should be member notification setting ignored?
	 * @return Mysql_Result
	 */
	public function get_ip_addresses_of_member($member_id, $subnet_id = NULL,
		$cloud_id = NULL, $ignore_member_notif_settings = TRUE)
	{
		$where = "";
		$cloud_subnets = "";
		
		if (!is_array($member_id))
		{
			$where = 'IFNULL(u.member_id, ip.member_id) = ' . intval($member_id);
		}
		else if (count($member_id))
		{
			$where = 'IFNULL(u.member_id, ip.member_id) IN (' 
					. implode(',', array_map('intval', $member_id)) . ')';
		}
		else // empty (non sense condition)
		{
			$where = '1 = 2';
		}
		
		
		if ($subnet_id)
		{
			$where .= " AND ip.subnet_id = ".intval($subnet_id);
		}
		
		if ($cloud_id)
		{
			$where .= " AND cs.cloud_id = ".intval($cloud_id);
			$cloud_subnets = "LEFT JOIN subnets s ON ip.subnet_id = s.id "
					. "LEFT JOIN clouds_subnets cs ON cs.subnet_id = s.id";
		}
		
		/* member whitelist - member notification settings */
		if (!$ignore_member_notif_settings)
		{
			$where .= " AND m.notification_by_redirection > 0 ";
		}
		
		return $this->db->query("
				SELECT ip.*, i.device_id
				FROM ip_addresses ip
				$cloud_subnets
				LEFT JOIN ifaces i ON i.id = ip.iface_id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN users u ON u.id = d.user_id
				LEFT JOIN members m ON m.id = IFNULL(u.member_id, ip.member_id)
				WHERE $where
		");
	}
	
	/**
	 * Function gets all ip address of interfaces of devices of user.
	 * 
	 * @param integer|array $user_id User ID or array of user IDs
	 * @param integer $subnet_id
	 * @param integer $cloud_id
	 * @param boolean $ignore_member_notif_settings Should be member notification setting ignored?
	 * @return Mysql_Result
	 */
	public function get_ip_addresses_of_user($user_id, $subnet_id = NULL, 
		$cloud_id = NULL, $ignore_member_notif_settings = TRUE)
	{
		$where = "";
		$cloud_subnets = "";
		
		if (!is_array($user_id))
		{
			$where = 'd.user_id = ' . intval($user_id);
		}
		else if (count($user_id))
		{
			$where = 'd.user_id IN (' 
					. implode(',', array_map('intval', $user_id)) . ')';
		}
		else // empty (non sense condition)
		{
			$where = '1 = 2';
		}
		
		
		if ($subnet_id)
		{
			$where .= " AND ip.subnet_id = ".intval($subnet_id);
		}
		
		if ($cloud_id)
		{
			$where .= " AND cs.cloud_id = ".intval($cloud_id);
			$cloud_subnets = "LEFT JOIN subnets s ON ip.subnet_id = s.id "
					. "LEFT JOIN clouds_subnets cs ON cs.subnet_id = s.id";
		}
		
		/* member whitelist - member notification settings */
		if (!$ignore_member_notif_settings)
		{
			$where .= " AND m.notification_by_redirection > 0 ";
		}
		
		return $this->db->query("
				SELECT ip.*, i.device_id
				FROM ip_addresses ip
				$cloud_subnets
				LEFT JOIN ifaces i ON i.id = ip.iface_id
				LEFT JOIN devices d ON d.id = i.device_id
				LEFT JOIN users u ON u.id = d.user_id
				LEFT JOIN members m ON m.id = IFNULL(u.member_id, ip.member_id)
				WHERE $where
		");
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
					s.netmask AS subnet_netmask,
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
					u.name AS user_name, u.surname AS user_surname,
					IFNULL(i.mac, pi.mac) AS mac
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON ip.iface_id = i.id
				LEFT JOIN ifaces_relationships ir ON ir.iface_id = i.id
				LEFT JOIN ifaces pi ON ir.parent_iface_id = pi.id
				LEFT JOIN devices d ON i.device_id = d.id
				LEFT JOIN users u ON d.user_id = u.id
				LEFT JOIN members m ON u.member_id = m.id
				LEFT JOIN accounts a ON a.member_id = m.id AND account_attribute_id = ?
				WHERE ip.subnet_id = ? AND ip.member_id IS NULL
				ORDER BY inet_aton(ip.ip_address)
		", Account_attribute_Model::CREDIT, $subnet_id);
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
	 * Returns all IDs of IP addresses with expired connection test
	 * 
	 * @author Ondrej Fibich
	 * @return Mysql_Result
	 */
	public function get_ip_addresses_with_expired_connection_test()
	{
		return $this->db->query("
			SELECT ip.id, ip.ip_address
			FROM ip_addresses ip
			LEFT JOIN ifaces i ON ip.iface_id = i.id
			LEFT JOIN devices d ON i.device_id = d.id
			LEFT JOIN users u ON d.user_id = u.id
			JOIN members m ON m.id = ip.member_id OR m.id = u.member_id
			WHERE m.type = ? AND m.registration = 0
				AND DATEDIFF(NOW(), m.applicant_connected_from) > ?
		", array
		(
			Member_Model::TYPE_APPLICANT,
			Settings::get('applicant_connection_test_duration')
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
			SELECT ip.id AS ip_address_id, ip.ip_address, m.id AS message_id,
				m.name AS message, m.type, ? AS member_id,
				mip.datetime AS active_redir_datetime,
				IF(mw.id IS NULL, 0, 2 - mw.permanent) AS whitelisted
			FROM ip_addresses ip
			LEFT JOIN ifaces i ON ip.iface_id = i.id
			LEFT JOIN devices d ON i.device_id = d.id
			LEFT JOIN users u ON d.user_id = u.id
			LEFT JOIN members_whitelists mw ON (mw.member_id = u.member_id OR mw.member_id = ip.member_id)
				AND (? BETWEEN mw.since AND mw.until)
			LEFT JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
			LEFT JOIN messages m ON m.id = mip.message_id
			WHERE u.member_id = ? OR ip.member_id = ?
			GROUP BY ip.id
			ORDER BY $order_by $order_by_direction,
				m.self_cancel DESC, mip.datetime ASC
			LIMIT " . intval($sql_offset) . ", " . intval($limit_results) . "
		", $member_id, date('Y-m-d'), $member_id, $member_id);
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
			SELECT COUNT(DISTINCT ip.id) AS total
			FROM ip_addresses ip
			LEFT JOIN ifaces i ON ip.iface_id = i.id
			LEFT JOIN devices d ON i.device_id = d.id
			LEFT JOIN users u ON d.user_id = u.id
			LEFT JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
			WHERE u.member_id = ? OR ip.member_id = ?
		", $member_id, $member_id)->current()->total;
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
			SELECT COUNT(DISTINCT ip.id) AS count
			FROM ip_addresses ip
			JOIN ifaces i ON ip.iface_id = i.id
			JOIN devices d ON i.device_id = d.id
			JOIN users u ON d.user_id = u.id
			WHERE u.member_id = ? AND ip.subnet_id = ?
		", array($member_id, $subnet_id))->current()->count;
	}
	
	/**
	 * Returns all ip addresses of iface (optional: and with its children ifaces)
	 * 
	 * @author Michal Kliment
	 * @param integer $iface_id
	 * @param type $with_child
	 * @return Mysql_Result 
	 */
	public function get_all_ip_addresses_of_iface($iface_id, $with_children = FALSE)
	{
		if ($with_children)
			$where = 'OR ip.iface_id IN (SELECT iface_id FROM ifaces_relationships WHERE parent_iface_id = '.intval($iface_id).')';
		else
			$where = '';
		
		return $this->db->query("
			SELECT ip.id, ip.ip_address, s.name AS subnet_name, s.id AS subnet_id
			FROM ip_addresses ip
			LEFT JOIN subnets s ON s.id = ip.subnet_id
			WHERE ip.iface_id = ? $where
			ORDER BY id ASC
		", array($iface_id));
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
			JOIN members m ON ip.member_id = m.id AND m.speed_class_id IS NOT NULL
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
	
	/**
	 * Returns all free IP addresses similar to given IP address
	 * 
	 * @author Michal Kliment
	 * @param type $ip_address_like
	 * @return type
	 */
	public function get_free_ip_addresses($ip_address_like)
	{		
		$arr_ip_addresses = array();
		
		$subnet_model = new Subnet_Model();
		
		// split IP address
		$ips = explode('.', $ip_address_like);
		
		// returns only if last number of IP address is missing
		if (count($ips) < 4)
			return array();
		
		// take only first 3 numbers
		$ips = array_slice($ips, 0, 3);
		
		// join back to IP address
		$network_address = implode('.', $ips);
		
		$subnets = $subnet_model
			->like('network_address', $network_address)
			->find_all();
		
		$ip_queries = array();
		
		foreach ($subnets as $subnet)
		{
			$network = ip2long($subnet->network_address);
			$total_available = (~ip2long($subnet->netmask) & 0xffffffff)-1;
                
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
			)
			AND ip_address LIKE '%$ip_address_like%'
		", $this->id);
		
		foreach ($ips as $ip)
			$arr_ip_addresses[] = $ip->ip_address;
		
		return $arr_ip_addresses;
	}
	
}
