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
}
