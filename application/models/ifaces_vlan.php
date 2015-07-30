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
 * Relates VLAN with its interfaces or VLANs that are on specified port.
 * If the iface is a port, port_vlan option specifies the default port of VLAN.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $vlan_id
 * @property Vlan_Model $vlan
 * @property integer $iface_id
 * @property Iface_Model $iface
 * @property boolean $tagged
 * @property boolean $port_vlan
 */
class Ifaces_vlan_Model extends ORM
{
	protected $belongs_to = array('iface', 'vlan');
	
	/**
     * Function returns all bridged interfaces of interface
	 * 
	 * @param  integer $iface
     * @return Mysql_Result
     */
	public function get_all_bridged_ifaces_of_iface($iface = null)
	{
		return $this->db->query("
				SELECT i.*
				FROM ifaces_relationships ir
				JOIN ifaces i ON i.id = ir.iface_id
				WHERE parent_iface_id = ?
		", $iface);
	}
	
	/**
	 * Gets all VLANs of iface
	 * 
	 * @param integer $iface_id
	 * @return Mysql_Result 
	 */
	public function get_all_vlans_of_iface($iface_id)
	{
		return $this->db->query("
				SELECT v.*, iv.tagged, iv.port_vlan
				FROM vlans v
				JOIN ifaces_vlans iv ON iv.vlan_id = v.id
				WHERE iv.iface_id = ?
		", $iface_id);
	}
	
	/**
     * Function removes iface from bridge
	 * 
	 * @param  integer $bridge_iface_id
	 * @param  integer $iface_id
     * @return integer
     */
	public function remove_iface_from_bridge($bridge_iface_id = null, $iface_id = null)
	{
		return $this->db->query("
				DELETE
				FROM `ifaces_relationships`
				WHERE `parent_iface_id` = ?
					AND `iface_id` = ?
		", $bridge_iface_id, $iface_id);
	}
	
	/**
	 * Rmove relation between VLANs and iface
	 * 
	 * @param integer $iface_id 
	 */
	public function delete_relation_vlans_to_iface($iface_id)
	{
		$this->db->query("
				DELETE FROM ifaces_vlans
				WHERE iface_id = ?
		", $iface_id);
	}
	
	/**
     * Function removes vlan from port
	 * 
	 * @param  integer $port_iface_id
	 * @param  integer $vlan_id
     */
	public function remove_vlan_from_port($port_iface_id = null, $vlan_id = null)
	{
		return $this->db->query("
				DELETE
				FROM `ifaces_vlans`
				WHERE `vlan_id` = ?
					AND `iface_id` = ?
		", $vlan_id, $port_iface_id);
	}

	/**
     * Function returns true if vlan is default
	 * 
	 * @param  integer $vlan_id
 	 * @param  integer $port_iface_id
	 * @return bool
     */
	public function is_default_vlan_port($vlan_id = null, $port_iface_id = null)
	{
		$result = $this->db->query("
				SELECT port_vlan
				FROM ifaces_vlans
				WHERE iface_id = ?
					AND vlan_id = ?
			", $port_iface_id, $vlan_id)->current();

		return ($result && ($result->port_vlan == 1));
	}
	
}
