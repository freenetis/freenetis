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
 * Membership transfer model. 
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $from_member_id
 * @property Member_Model $from_member
 * @property integer $to_member_id
 * @property Member_Model $to_member
 */
class Membership_transfer_Model extends ORM
{
	protected $belongs_to = array
	(
		'from_member' => 'member',
		'to_member' => 'member'
	);
	
	/**
	 * Returns all membership transfers
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql
	 * @return MySQL_Iterator
	 */
	public function get_all_membership_transfers($limit_from = NULL, $limit_results = NULL,
			$order_by = NULL, $order_by_direction = NULL, $filter_sql = '')
	{
		$limit = '';
		$order = '';
		$where = '';
		
		if (!is_null($limit_from) && !is_null($limit_results))
		{
			$limit = 'LIMIT '.intval($limit_from).', '.intval($limit_results);
		}
		
		if (!is_null($order_by) && !is_null($order_by_direction))
		{
			$order = 'ORDER BY '.$this->db->escape_column($order_by).' '.$order_by_direction;
		}
		
		if ($filter_sql != '')
		{
			$where = 'WHERE '.$filter_sql;
		}
		
		return $this->db->query("
			SELECT * FROM
			(
				SELECT mt.id,
				mt.from_member_id, fm.name AS from_member_name,
				mt.to_member_id, tm.name AS to_member_name
				FROM membership_transfers mt
				JOIN members fm ON mt.from_member_id = fm.id
				JOIN members tm ON mt.to_member_id = tm.id
			) mt
			$where
			$order
			$limit
		");
	}
	
	/**
	 * Counts all membership transfers
	 * 
	 * @author Michal Kliment
	 * @param string $filter_sql
	 * @return integer
	 */
	public function count_all_membership_transfers($filter_sql = '')
	{
		return $this
			->get_all_membership_transfers(NULL, NULL, NULL, NULL, $filter_sql)
			->count();
	}
	
	/**
	 * Returns membership transfer with given from_member_id
	 * 
	 * @author Michal Kliment
	 * @param integer $from_member_id
	 * @return MySQL_Result
	 */
	public function get_transfer_from_member($from_member_id)
	{		
		return $this->db->query("
			SELECT mt.id, m.id AS member_id, m.name AS member_name
			FROM membership_transfers mt
			JOIN members m ON mt.to_member_id = m.id
			WHERE mt.from_member_id = ?
		", $from_member_id)->current();
	}
	
	/**
	 * Returns membership transfer with given to_member_id
	 * 
	 * @author Michal Kliment
	 * @param integer $to_member_id
	 * @return MySQL_Result
	 */
	public function get_transfer_to_member($to_member_id)
	{	
		return $this->db->query("
			SELECT mt.id, m.id AS member_id, m.name AS member_name
			FROM membership_transfers mt
			JOIN members m ON mt.from_member_id = m.id
			WHERE mt.to_member_id = ?
		", $to_member_id)->current();
	}
	
	/**
	 * Returns all members with transfer from
	 * 
	 * @author Michal Kliment
	 * @return MySQL_Iterator
	 */
	public function get_all_members_with_transfer_from()
	{
		return $this->db->query("
			SELECT m.*
			FROM membership_transfers mt
			JOIN members m ON mt.from_member_id = m.id
		");
	}
	
	/**
	 * Returns all members with transfer to
	 * 
	 * @author Michal Kliment
	 * @return MySQL_Iterator
	 */
	public function get_all_members_with_transfer_to()
	{
		return $this->db->query("
			SELECT m.*
			FROM membership_transfers mt
			JOIN members m ON mt.to_member_id = m.id
		");
	}
	
	/**
	 * Deletes membership transfer with given from_member_id
	 * 
	 * @author Michal Kliment
	 * @param integer $from_member_id
	 * @return type
	 */
	public function delete_transfer_from_member($from_member_id)
	{		
		return $this->db->query("
			DELETE FROM membership_transfers
			WHERE from_member_id = ?
		", $from_member_id);
	}
}
