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
 * Defined relationship between a VLAN interface and a interface on which it is
 * created or between a bridge and it's interface.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $iface_id
 * @property Iface_Model $iface
 * @property integer $parent_iface_id
 * @property Iface_Model $parent_iface
 */
class Ifaces_relationship_Model extends ORM
{
	protected $belongs_to = array
	(
		'iface' => 'iface', 'parent_iface' => 'iface'
	);
	
	/**
	 * Deletes bridge relationships of interface
	 * 
	 * @param integer $iface_id 
	 */
	public function delete_bridge_relationships_of_iface($iface_id)
	{
		$this->db->query("
			DELETE FROM ifaces_relationships
			WHERE parent_iface_id = ?
		", $iface_id);
	}
	
}
