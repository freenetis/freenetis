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
 * Device admin is sets engineers of device.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $device_id
 * @property Device_Model $device
 * @property integer $user_id
 * @property User_Model $user
 */
class Device_engineer_Model extends ORM
{
	protected $belongs_to = array('device', 'user');
	
	/**
	 * Function gets engineers of specified device.
	 * 
	 * @param $device_id
	 * @return Mysql_Result
	 */
	public function get_device_engineers($device_id)
	{
		return $this->db->query("
				SELECT de.id, u.name, u.surname, u.login
				FROM device_engineers de
				JOIN users u ON de.user_id = u.id
				WHERE de.device_id = ?
				ORDER BY id asc
		", $device_id);
	}
	
	/**
	 * Function gets engineer of user.
	 * Used in devices/add to prefill engineer field.
	 * 
	 * @param $user_id
	 * @return Mysql_Result
	 */
	public function get_engineer_of_user($user_id)
	{
		return $this->db->query("
				SELECT de.user_id AS id
				FROM device_engineers de
				JOIN devices d ON d.id = de.device_id
				WHERE d.user_id = ?
				ORDER BY de.id asc
		", $user_id)->current();
	}

	/**
	 * Returns engineers of user
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_engineers_of_user($user_id)
	{
		return $this->db->query("
				SELECT u.id, u.name, u.surname
				FROM devices d
				JOIN device_engineers e ON d.id = e.device_id
				JOIN users u ON e.user_id = u.id
				WHERE d.user_id = ?
				GROUP BY u.id
				ORDER BY u.surname
		", $user_id);
	}
	
	/**
	 * Returns all devices of which is user engineer
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_all_devices_by_engineer ($user_id)
	{
		return $this->db->query("
			SELECT de.id, de.device_id, d.name AS device_name, d.user_id,
				CONCAT(u.name,' ',u.surname) AS user_name
			FROM device_engineers de
			JOIN devices d ON de.device_id = d.id
			JOIN users u ON d.user_id = u.id
			WHERE de.user_id = ?
			ORDER BY u.id, d.name
		", array($user_id));
	}
}
