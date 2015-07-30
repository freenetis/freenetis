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
 * Subnet owner
 * 
 * @author Michal Kliment
 * @package Model
 * 
 * @property integer $id
 * @property integer $subnet_id
 * @property Subnet_Model $subnet
 * @property integer $member_id
 * @property Member_Model $member
 * @property integer $redirect
 */
class Subnets_owner_Model extends ORM
{
	protected $belongs_to = array('member', 'subnet');

	/**
	 * Returns all subnets with set owner to redirect
	 *
	 * @author Michal Kliment
	 * @return Mysql_Result object
	 */
	public function get_all_subnets_with_owner_to_redirect()
	{
		return $this->db->query("
				SELECT INET_ATON(s.network_address) AS start,
					(~INET_ATON(s.netmask)& 0xffffffff)+1 AS length
				FROM subnets_owners so
				JOIN members m ON so.member_id = m.id
				JOIN subnets s ON so.subnet_id = s.id
				WHERE so.redirect = 0 OR so.redirect IS NULL
				GROUP BY s.id
		");
	}

	/**
	 * Clears state of redirect of ip addresses
	 *
	 * @author Michal Kliment
	 */
	public function clear_disable_subnets()
	{
		$this->db->query("UPDATE subnets_owners SET redirect = redirect & ~1;");
	}

	/**
	 * Updates state of redirect of subnet with set owner
	 *
	 * @author Michal Kliment
	 */
	public function update_allowed_subnets()
	{
		$this->clear_disable_subnets();
		$this->db->query("
				UPDATE subnets_owners so,
				(
					SELECT so.id FROM subnets_owners so
					WHERE so.subnet_id NOT IN (
						SELECT a.subnet_id
						FROM allowed_subnets a
						WHERE a.subnet_id = so.subnet_id AND
							a.member_id = so.member_id AND
							enabled = 1
						)
				) AS q
				SET so.redirect = so.redirect | 1
				WHERE so.id = q.id
		");
	}
}
