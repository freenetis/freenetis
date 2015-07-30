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
 * Allowed subnet enables member to use subnet
 *
 * @author	Michal Kliment
 * @package Model
 * 
 * @property integer $id
 * @property integer $member_id
 * @property Member_Model $member
 * @property integer $subnet_id
 * @property Subnet_Model $subnet
 * @property boolean $enabled
 * @property datime $last_update
 */
class Allowed_subnet_Model extends ORM
{

	protected $belongs_to = array('member');

	/**
	 * Returns all allowed subnets of member
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @param integer $order_by
	 * @param integer $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_all_allowed_subnets_by_member(
			$member_id, $order_by = 'id', $order_by_direction = 'ASC')
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT a.id, s.id AS subnet_id, s.name AS subnet_name, a.enabled,
				network_address, netmask, INET_ATON(network_address) AS cidr,
				CONCAT(
					s.network_address,'/',
					32-log2((~inet_aton(s.netmask) & 0xffffffff) + 1)
				) AS cidr_address
				FROM allowed_subnets a
				LEFT JOIN subnets s ON a.subnet_id = s.id
				WHERE a.member_id = ?
				ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction
		", array($member_id));
	}

	/**
	 * Returns all enabled and allowed subnets of member
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @return Mysql_Result
	 */
	public function get_all_enabled_allowed_subnets_by_member($member_id)
	{
		return $this->db->query("
				SELECT a.id, s.id AS subnet_id, s.name AS subnet_name, a.enabled
				FROM allowed_subnets a
				LEFT JOIN subnets s ON a.subnet_id = s.id
				WHERE a.member_id = ? AND enabled = 1
		", array($member_id));
	}

	/**
	 *  Counts all enabled and allowed subnets of member
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @return integer
	 */
	public function count_all_enabled_allowed_subnets_by_member($member_id)
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM allowed_subnets a
				WHERE a.member_id = ? AND enabled = 1
		", array($member_id))->current()->count;
	}

	/**
	 *  Counts all disabled and allowed subnets of member
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @return integer
	 */
	public function count_all_disabled_allowed_subnets_by_member($member_id)
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM allowed_subnets a
				WHERE a.member_id = ? AND enabled = 0
		", array($member_id))->current()->count;
	}

	/**
	 * Checks if record exists and return its ID
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @param integer $subnet_id
	 * @return integer
	 */
	public function exists($member_id, $subnet_id)
	{
		$result = $this->db->query("
				SELECT a.id
				FROM allowed_subnets a
				WHERE a.member_id = ? AND a.subnet_id = ?
				LIMIT 0,1
		", array($member_id, $subnet_id));

		if ($result && $result->current() && $result->current()->id)
		{
			return $result->current()->id;
		}
		
		return 0;
	}

}
