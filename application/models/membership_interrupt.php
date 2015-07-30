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
 * Membership interrupts
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $member_id
 * @property Member_Model $member
 * @property integer $members_fee_id
 * @property Members_fee_Model $members_fee
 * @property string $comment
 */
class Membership_interrupt_Model extends ORM
{

	protected $belongs_to = array('member', 'members_fee');

	/**
	 * Function if requested interval of membership interrupt of member overlaps 
	 * over his another membership interrupt
	 * 
	 * @param date $date_from		start of requested interval of membership interrupt
	 * @param date $date_to			end of requested interval of membership interrupt
	 * @param integer $member_id	id if member to add new membership interrupt
	 * @param integer $membership_interrupt_id
	 *								optional argument, is used for editing
	 *								exclusion of editing membership interrupt
	 * @return integer
	 */
	public function check_overlaps(
			$date_from = '0000-00-00', $date_to = '0000-00-00',
			$member_id = NULL, $membership_interrupt_id = NULL)
	{
		$edit_clause = "";
		
		if ($membership_interrupt_id) {
			$edit_clause = " and id <> " . intval($membership_interrupt_id);
		}

		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM membership_interrupts
				WHERE
					(
						(
							? between `from` and `to` OR ? between `from` and `to`
						) OR (
							`from` between ? and ? AND `to` between ? and ?
						)
					) and member_id = ?
					$edit_clause
		", array
		(
			$date_from, $date_to, $date_from, $date_to,
			$date_from, $date_to, $member_id
		))->current()->count;
	}

	/**
	 * Checks if member has membership interrupt in given date
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @param string $date
	 * @return bool
	 */
	public function has_member_interrupt_in_date($member_id, $date)
	{
		return (bool) $this->db->query("
				SELECT COUNT(*) AS count
				FROM membership_interrupts mi
				JOIN members_fees mf ON mi.members_fee_id = mf.id
				WHERE mi.member_id = ? AND
					? BETWEEN mf.activation_date AND mf.deactivation_date
		", array($member_id, $date))->current()->count;
	}

	/**
	 * Returns all membership interrupts belongs to member
	 *
	 * @author Michal Kliment
	 * @param numeric $member_id
	 * @return Mysql_Result object
	 */
	public function get_all_by_member($member_id)
	{
		return $this->db->query("
				SELECT mi.id, mi.member_id, mf.activation_date AS 'from',
					mf.deactivation_date AS 'to', mi.comment
				FROM membership_interrupts mi
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id
				WHERE mi.member_id = ?
		", $member_id);
	}

	/**
	 * Gets all membership interupts
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql
	 * @return Mysql_Result object
	 */
	public function get_all_membership_interrupts($limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'asc', $filter_sql = '')
	{
		$where = ($filter_sql != '') ? 'WHERE ' . $filter_sql : '';

		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT mi.id, mi.member_id, m.name AS member_name,
					mf.activation_date AS `from`, mf.deactivation_date AS `to`,
					mi.comment
				FROM membership_interrupts mi
				JOIN members m ON mi.member_id = m.id
				JOIN members_fees mf ON mi.members_fee_id = mf.id
				$where
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT ".intval($limit_from) . ", " . intval($limit_results) . "
		");
	}

	/**
	 * Counts all membership interupts
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @param string $filter_sql
	 * @return integer
	 */
	public function count_all_membership_interrupts($filter_sql = '')
	{
		$where = ($filter_sql != '') ? 'WHERE ' . $filter_sql : '';

		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM membership_interrupts mi
				JOIN members m ON mi.member_id = m.id
				JOIN members_fees mf ON mi.members_fee_id = mf.id
				$where
		")->current()->count;
	}

}
