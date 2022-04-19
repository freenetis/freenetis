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

class Ip6_address_Model extends ORM
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
	* Gets all ipv6 addresses of device.
	* 
	* @author Martin Zatloukal
	* @param integer $device_id
	* @return Mysql_Result
	*/
	public function get_ip6_addresses_of_device($device_id)
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
			FROM ip6_addresses ip
			LEFT JOIN subnets s ON s.id = ip.subnet_id
			JOIN ifaces i ON i.id = ip.iface_id
			WHERE i.device_id = ?
			ORDER BY INET_ATON(ip_address)
		", $device_id);		
	}
	
	public function add_ip6_address_db($iface_id, $ip6_address)
	{
		return $this->db->query("

			INSERT INTO ip6_addresses (iface_id, subnet_id, member_id, ip_address, dhcp, gateway, service, id) VALUES ('".$iface_id."', NULL, NULL, '".$ip6_address."', NULL, NULL, '0', NULL)

			");
	}
	
	public function del_ip6_address_db($ip_address)
	{
		return $this->db->query("
		
		    DELETE FROM `ip6_addresses` WHERE `ip6_addresses`.`ip_address`  = '".$ip_address."'
			");
	}
	
	public function get_ipv6_mac_all()
	{
		return $this->db->query("
		
		SELECT ip.ip_address, i.mac AS ifaces FROM ip6_addresses ip LEFT JOIN subnets s ON s.id = ip.subnet_id JOIN ifaces i ON i.id = ip.iface_id WHERE i.mac IS NOT NULL ORDER BY `ifaces` ASC;
			");
	}

	public function get_ip6_addresses_to_class($class_id, $no_association = TRUE)
	{
		$assoc = '';
	
		if ($no_association)
			{
				$assoc = ' AND m.id <> ' . Member_Model::ASSOCIATION;
			}
	
		return $this->db->query("
			SELECT m.id AS member_id, ip.ip_address, u.login AS user_login
			FROM members m
			JOIN users u ON u.member_id = m.id
			LEFT JOIN devices d ON d.user_id = u.id
			LEFT JOIN ifaces i ON i.device_id = d.id
			JOIN ip6_addresses ip ON ip.iface_id = i.id OR ip.member_id = m.id
			WHERE m.speed_class_id = ? $assoc
			GROUP BY ip.ip_address
			ORDER BY m.id, ip.id
		", $class_id);
	}

}
