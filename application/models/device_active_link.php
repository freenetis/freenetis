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
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $url_pattern
 * @property string $name
 * @property string $title
 * @property integer $show_in_user_grid 	
 * @property integer $show_in_grid
 * 
 * @author David Raška <jeffraska@gmail.com>
 */
class Device_active_link_Model extends ORM
{
	const TYPE_DEVICE = 1;
	const TYPE_TEMPLATE = 2;
	
	protected $has_many = array
	(
		'device_active_links'
	);
	
	/**
	 * Count of all device active links
	 * @param array $filter_values
	 * @return integer
	 */
	public function count_all_active_links($filter_sql = '')
	{
		$where = '';
		
		// filter
		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		// query
		return $this->db->query("
			SELECT *
			FROM device_active_links
			$where
		")->count();
	}
	
	/**
	 * Get all device active links
	 * 
	 * @param array $params
	 * @return Mysql_Result
	 */
	public function get_all_active_links($params = array())
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
		
		// query
		return $this->db->query("
			SELECT dal.*, dalm.*, COUNT(*) AS devices_count
			FROM device_active_links AS dal
			LEFT JOIN device_active_links_map AS dalm ON dal.id = dalm.device_active_link_id
			$where
			GROUP BY dal.id
			ORDER BY $order_by $order_by_direction
			$limit
		");
	}
	
	/**
	 * Maps devices to device active link
	 * 
	 * @param array $devices	Array of device IDs to map
	 * @param integer $dal_id	Device active link ID
	 * @return Database_result
	 */
	public function map_devices_to_active_link($devices = NULL, $dal_id = NULL,
			$type = Device_active_link_Model::TYPE_DEVICE)
	{
		if (!$devices || empty($devices))
		{
			return NULL;
		}
		
		foreach ($devices AS $device)
		{	
			$this->db->query("
				INSERT INTO device_active_links_map (device_active_link_id, device_id, type)
				VALUES (?, ?, ?)
			", $dal_id, $device, $type);
		}
	}
	
	/**
	 * Unmaps devices from device active link
	 * 
	 * @param integer $dal_id	Device active link ID
	 * @return Database_result
	 */
	public function unmap_devices_from_active_link($dal_id = NULL,
			$type = Device_active_link_Model::TYPE_DEVICE)
	{
		$this->db->query("
			DELETE FROM device_active_links_map
			WHERE device_active_link_id = ? AND type = ?
		", $dal_id, $type);
	}
	
	/**
	 * Maps device to device active links
	 * 
	 * @param integer $device_id	Device ID
	 * @param array $dal_ids		Array of device active link IDs
	 * @return Database_result
	 */
	public function map_device_to_active_links($device_id = NULL, $dal_ids = NULL,
			$type = Device_active_link_Model::TYPE_DEVICE)
	{
		if (!$device_id || !$dal_ids || empty($dal_ids))
		{
			return NULL;
		}
		
		foreach ($dal_ids AS $id)
		{	
			$this->db->query("
				INSERT INTO device_active_links_map (device_active_link_id, device_id, type)
				VALUES (?, ?, ?)
			", $id, $device_id, $type);
		}
	}
	
	/**
	 * Unmaps device from device active links
	 * 
	 * @param integer $device_id	Device ID
	 * @return Database_result
	 */
	public function unmap_device_from_active_links($device_id = NULL,
			$type = Device_active_link_Model::TYPE_DEVICE)
	{
		if (!$device_id)
		{
			return NULL;
		}
		
		$this->db->query("
			DELETE FROM device_active_links_map
			WHERE device_id = ? AND type = ?
		", $device_id, $type);
	}
	
	/**
	 * Return all devices using given active link
	 * 
	 * @param integer $dal_id	Device active link ID
	 * @return Mysql_result
	 */
	public function get_active_link_devices($dal_id = NULL,
			$type = Device_active_link_Model::TYPE_DEVICE)
	{
		if (!$dal_id)
		{
			$dal_id = $this->id;
		}
		
		if ($type == Device_active_link_Model::TYPE_DEVICE)
		{
			return $this->db->query("
				SELECT d.*
				FROM device_active_links_map dalm
				LEFT JOIN devices AS d ON d.id = dalm.device_id
				WHERE dalm.device_active_link_id = ? AND dalm.type = ?
			", $dal_id, $type);
		}
		else
		{
			return $this->db->query("
				SELECT dt.*
				FROM device_active_links_map dalm
				LEFT JOIN device_templates AS dt ON dt.id = dalm.device_id
				WHERE dalm.device_active_link_id = ? AND dalm.type = ?
			", $dal_id, $type);
		}
	}
	
	/**
	 * Returns active links used by device
	 * 
	 * @param type $device_id	Device ID
	 * @return null
	 */
	public function get_device_active_links($device_id = NULL,
			$type = Device_active_link_Model::TYPE_DEVICE)
	{
		if (!$device_id)
		{
			return NULL;
		}
		
		return $this->db->query("
			SELECT dal.*
			FROM device_active_links_map AS dalm
			LEFT JOIN device_active_links AS dal ON dal.id = dalm.device_active_link_id
			WHERE dalm.device_id = ? AND dalm.type = ?
		", $device_id, $type);
	}
}
