<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is released under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Model of cloud which can be managed by admins and contains subnets
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $name
 * @property ORM_Iterator $subnets
 * @property ORM_Iterator $users
 */
class Cloud_Model extends ORM
{
	protected $has_and_belongs_to_many = array('subnets', 'users');

	/**
	 * Function gets all cloud's names and ids
	 * 
	 * @author Ondřej Fibich
	 * @return Mysql_Result
	 */
	public function get_all_clouds()
	{
		return $this->db->query("
				SELECT c.id, c.name,
					(
						SELECT COUNT(*)
						FROM clouds_users
						WHERE cloud_id = c.id
					) AS admin_count,
					(
						SELECT COUNT(*)
						FROM clouds_subnets
						WHERE cloud_id = c.id
					) AS subnet_count
				FROM clouds c
		");
	}

	/**
	 * Gets admins of cloud
	 *
	 * @param integer $cloud_id
	 * @return Mysql_Result
	 */
	public function get_cloud_admins($cloud_id = NULL)
	{
		if (empty($cloud_id))
		{
			$cloud_id = $this->id;
		}
		
		return $this->db->query("
				SELECT u.id, u.id AS uid, CONCAT(
						COALESCE(u.surname, ''),' ',
						COALESCE(u.name, ''),' - ',
						COALESCE(u.login, '')
					) as user
				FROM clouds_users cu
				LEFT JOIN users u ON u.id = cu.user_id
				WHERE cu.cloud_id = ?
		", $cloud_id);
	}

	/**
	 * Gets subnets of cloud
	 *
	 * @param integer $cloud_id
	 * @return Mysql_Result
	 */
	public function get_cloud_subnets($cloud_id = NULL)
	{
		if (empty($cloud_id))
		{
			$cloud_id = $this->id;
		}
		
		return $this->db->query("
				SELECT s.id, s.id AS sid, s.name, s.netmask,
					CONCAT(s.network_address,'/', 32-log2((~inet_aton(netmask) & 0xffffffff) + 1)) AS network_address
				FROM clouds_subnets cu
				LEFT JOIN subnets s ON s.id = cu.subnet_id
				WHERE cu.cloud_id = ?
				ORDER BY inet_aton(s.network_address)
		", $cloud_id);
	}

	/**
	 * Gets list of admins which are not admins of cloud
	 *
	 * @param integer $cloud_id
	 * @return array
	 */
	public function select_list_of_admins_not_in($cloud_id = NULL)
	{
		if (empty($cloud_id))
		{
			$cloud_id = $this->id;
		}
		
		$subnets = $this->db->query("
				SELECT id, CONCAT(
						COALESCE(surname, ''),' ',
						COALESCE(name, ''),' - ',
						COALESCE(login, '')
					) as user
				FROM users
				WHERE id NOT IN
				(
					SELECT cu.user_id
					FROM clouds c
					JOIN clouds_users cu ON c.id = cu.cloud_id
					WHERE c.id = ?
				)
				ORDER BY user
		", $cloud_id);
		
		$list = array();
		
		foreach ($subnets as $subnet)
		{
			if (!empty($subnet->user))
			{
				$list[$subnet->id] = $subnet->user;
			}
		}
		
		return $list;
	}

	/**
	 * Gets list of subnets which are not in cloud
	 *
	 * @param integer $cloud_id
	 * @return array
	 */
	public function select_list_of_subnets_not_in($cloud_id = NULL)
	{
		if (empty($cloud_id))
		{
			$cloud_id = $this->id;
		}
		
		$subnets = $this->db->query("
				SELECT id,
					CONCAT(
						CONCAT(network_address,'/', 32-log2((~inet_aton(netmask) & 0xffffffff) + 1)),
						' - ', name
					) AS name
				FROM subnets
				WHERE id NOT IN
				(
					SELECT cu.subnet_id
					FROM clouds c
					JOIN clouds_subnets cu ON c.id = cu.cloud_id
					WHERE c.id = ?
				)
				ORDER BY inet_aton(network_address)
		", $cloud_id);
		
		$list = array();
		
		foreach ($subnets as $subnet)
		{
			$list[$subnet->id] = $subnet->name;
		}
		
		return $list;
	}
	
	/**
	 * Removes admins from cloud
	 *
	 * @param integer $cloud_id	Cloud
	 * @param array $admin_ids	Array of users ids
	 */
	public function remove_admins($cloud_id, $admin_ids)
	{
		if (is_array($admin_ids) && count($admin_ids))
		{
			for ($i = 0; $i < count($admin_ids); $i++)
			{
				$admin_ids[$i] = intval($admin_ids[$i]);
			}
			
			$this->db->query("
				DELETE FROM clouds_users
				WHERE cloud_id = ? AND user_id IN(" . implode(', ', $admin_ids) . ")
			", $cloud_id);
		}
	}
	
	/**
	 * Removes admins from cloud
	 *
	 * @param integer $cloud_id	Cloud
	 * @param array $subnet_ids	Array of users ids
	 */
	public function remove_subnets($cloud_id, $subnet_ids)
	{
		if (is_array($subnet_ids) && count($subnet_ids))
		{
			for ($i = 0; $i < count($subnet_ids); $i++)
			{
				$subnet_ids[$i] = intval($subnet_ids[$i]);
			}
			
			$this->db->query("
				DELETE FROM clouds_subnets
				WHERE cloud_id = ? AND subnet_id IN(" . implode(', ', $subnet_ids) . ")
			", $cloud_id);
		}
	}
	
}
