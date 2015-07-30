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
 * VLAN is a virtual independent network that contains a collection of virtual
 * interfaces.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $name
 * @property integer $tag_802_1q
 * @property string $comment
 * @property ORM_Iterator $ifaces_vlans
 */
class Vlan_Model extends ORM
{
	/**
	 * Const for default VLAN
	 */
	const DEFAULT_VLAN_TAG = 1;

	// relationship
	protected $has_many = array('ifaces_vlan');
	
	/**
	 * Returns default VLAN
	 *
	 * @author Michal Kliment
	 * @return Vlan_Model object
	 */
	public function get_default_vlan()
	{
		return $this->where('tag_802_1q', self::DEFAULT_VLAN_TAG)->find();
	}
	
	/**
	 * Get count of vlans
	 * 
	 * @return integer
	 */
	public function count_all_vlans() 
	{
		return $this->db->count_records('vlans');
	}
	
	/**
	 * Gets all VLANs
	 *
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param integer $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_all_vlans(
			$limit_from = 0, $limit_results = 50,
			$order_by = 'tag_802_1q', $order_by_direction = 'ASC')
	{
		// order by direction check
		$order_by_direction = strtolower($order_by_direction);

		if ($order_by_direction != 'desc')
		{
			$order_by_direction = 'asc';
		}

		// query
		return $this->db->query("
			SELECT v.*, COUNT(DISTINCT d.id) AS devices_count,
					GROUP_CONCAT(DISTINCT d.name SEPARATOR ', \n') AS devices
			FROM vlans v
			LEFT JOIN ifaces_vlans iv ON iv.vlan_id = v.id
			LEFT JOIN ifaces i ON iv.iface_id = i.id
			LEFT JOIN devices d ON i.device_id = d.id
			GROUP BY v.id
			ORDER BY $order_by $order_by_direction
			LIMIT " . intval($limit_from) . ", " . intval($limit_results) ."
		");
	}
	
	/**
	 * Override default ORM function select_list due to
	 * need of ordering by another column than are key and value
	 * 
	 * @author Michal Kliment, Ondrej Fibich
	 * @return array 
	 */
	public function select_list($key = 'id', $val = NULL, $order_val = 'tag_802_1q')
	{
		if (empty($val))
		{
			$val = 'CONCAT(COALESCE(tag_802_1q, ""), " - ", COALESCE(name, ""))';
		}

		return parent::select_list($key, $val, $order_val);
	}
	
	/**
	 * Returns all devices which belong to VLAN
	 * 
	 * @param integer $vlan_id
	 * @return Mysql_Result
	 */
	public function get_devices_of_vlan($vlan_id = NULL)
	{
		if (!$vlan_id && isset($this))
		{
			$vlan_id = $this->id;
		}

		return $this->db->query("
			SELECT d.id, d.name, IFNULL(p.ports_count, 0) AS ports_count,
					p.ports, ip.ip_address AS ip_address, ip.id AS ip_address_id
			FROM devices d
			LEFT JOIN ifaces i ON i.device_id = d.id
			LEFT JOIN ifaces_vlans iv ON iv.iface_id = i.id
			LEFT JOIN
			(
				SELECT COUNT(*) AS ports_count, i.device_id, GROUP_CONCAT(
								IF(i.number IS NOT NULL, CONCAT(?, i.number), i.name)
								ORDER BY i.number SEPARATOR ', \n'
						) AS ports
				FROM ifaces i
				JOIN ifaces_vlans iv2 ON iv2.iface_id = i.id
				WHERE i.type = ? AND iv2.vlan_id = ?
				GROUP BY i.device_id
			) p ON p.device_id = d.id
			LEFT JOIN
			(
				SELECT ip.id, ip.ip_address, i.device_id
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON ip.iface_id = i.id
			) ip ON ip.device_id = d.id
			WHERE iv.vlan_id = ?
			GROUP BY d.id
			ORDER BY INET_ATON(ip.ip_address)
		", array( __('Port') . ' ', Iface_Model::TYPE_PORT, $vlan_id, $vlan_id));
	}
	
	/**
	 * Gets all VLANs of interface
	 *
	 * @param integer $iface_id
	 * @return Mysql_Result
	 */
	public function get_all_vlans_of_iface($iface_id = null)
	{
		// query
		return $this->db->query("
			SELECT v.name, v.tag_802_1q, iv.tagged, iv.port_vlan, iv.vlan_id
			FROM vlans v
			LEFT JOIN ifaces_vlans iv ON iv.vlan_id = v.id
			WHERE iv.iface_id = ?
			GROUP BY v.id
			ORDER BY v.tag_802_1q
		", $iface_id);
	}
	
	/**
	 * Gets default VLAN of iface
	 * 
	 * @param integer $iface_id
	 * @return Mysql_Result
	 */
	public function get_default_vlan_of_interface($iface_id = null)
	{
		// query
		$result = $this->db->query("
			SELECT v.name, v.id
			FROM vlans v
			LEFT JOIN ifaces_vlans iv ON iv.vlan_id = v.id
			WHERE iv.iface_id = ?
				AND iv.port_vlan = 1
			GROUP BY v.id
		", $iface_id);
		
		if ($result)
			return $result->current();
		else
			return NULL;
	}
	
}
