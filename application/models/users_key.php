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
 * SSH key of user for remote access to device.
 * 
 * @author	Michal Kliment
 * @package Model
 * 
 * @property integer $id
 * @property integer $user_id
 * @property User_Model $user
 * @property string $key
 */
class Users_key_Model extends ORM
{
	protected $belongs_to = array('user');

	/**
	 * Returns all keys belongs to device
	 *
	 * @author Michal Kliment
	 * @param integer $device_id
	 * @return Mysql_Result
	 */
	public function get_keys_by_device($device_id)
	{
		return $this->db->query("
				SELECT k.id, k.key FROM
				(
					(
						SELECT 1 AS id
					)
					UNION
					(
						SELECT u.id from device_admins a
						JOIN users u ON a.user_id = u.id
						WHERE a.device_id = ?
					)
				) AS u
				JOIN users_keys k ON u.id = k.user_id
		", array($device_id));
	}
}
