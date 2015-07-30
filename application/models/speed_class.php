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
 * Speed class defines member download and upload ceil and rate speeds for QoS.
 * 
 * @author Ondrej Fibich
 * @package Model
 *
 * @property integer $id
 * @property string $name
 * @property integer $d_ceil
 * @property integer $d_rate
 * @property integer $u_ceil
 * @property integer $u_rate
 * @property boolean $regular_member_default
 * @property boolean $applicant_default
 * @property Member_Model $member
 */
class Speed_class_Model extends ORM
{
	protected $has_many = array('members');
	
	/**
	 * Gets all speed classes
	 * 
	 * @return Mysql_Result
	 */
	public function get_all_speed_classes()
	{
		return $this->db->query("
			SELECT
				sc.*,
				COUNT(m.id) AS members_count,
				GROUP_CONCAT(m.name SEPARATOR '\n') AS members_names
			FROM speed_classes sc
			LEFT JOIN members m ON m.speed_class_id = sc.id
			GROUP BY sc.id
			ORDER BY members_count DESC
		");
	}
	
	/**
	 * Get IP addresses and users logins of a the given speed class
	 * 
	 * @param integer $class_id
	 * @param boolean $no_association Display devices of the association?
	 * @return Mysql_Result
	 */
	public function get_ip_addresses_to_class($class_id, $no_association = TRUE)
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
			JOIN ip_addresses ip ON ip.iface_id = i.id OR ip.member_id = m.id
			WHERE m.speed_class_id = ? $assoc
			GROUP BY ip.ip_address
			ORDER BY m.id, ip.id
		", $class_id);
	}


	/**
	 * Get default speed class for members.
	 * 
	 * @return Speed_class_Model|null
	 */
	public function get_members_default_class()
	{
		$classes = $this->where('regular_member_default', 1)->find_all();
		
		if ($classes->count())
		{
			return $classes->current();
		}
		
		return NULL;
	}
	
	/**
	 * Get default speed class for applicants.
	 * 
	 * @return Speed_class_Model|null
	 */
	public function get_applicants_default_class()
	{
		$classes = $this->where('applicant_default', 1)->find_all();
		
		if ($classes->count())
		{
			return $classes->current();
		}
		
		return NULL;
	}
	
	/**
	 * Repair applicant default flag - disable it for other items
	 * 
	 * @param integer $speed_class_id
	 */
	public function repair_applicant_default($speed_class_id = NULL)
	{
		// if id is not given, it uses current object
		if (!$speed_class_id && $this->id)
		{
			$speed_class_id = $this->id;
		}
		
		$this->db->query("
			UPDATE speed_classes
			SET applicant_default = 0
			WHERE id <> ?
		", $speed_class_id);
	}
	
	/**
	 * Repair regular member default flag - disable it for other items
	 * 
	 * @param integer $speed_class_id
	 */
	public function repair_regular_member_default($speed_class_id = NULL)
	{
		// if id is not given, it uses current object
		if (!$speed_class_id && $this->id)
		{
			$speed_class_id = $this->id;
		}
		
		$this->db->query("
			UPDATE speed_classes
			SET regular_member_default = 0
			WHERE id <> ?
		", $speed_class_id);
	}
	
}

