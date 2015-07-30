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
 * Member's fees
 * 
 * @author Michal Kliment
 * @package Model
 * 
 * @property integer $id
 * @property integer $fee_id
 * @property Fee_Model $fee
 * @property integer $member_id
 * @property Member_Model $member
 * @property date $activation_date
 * @property date $deactivation_date
 * @property integer $priority
 * @property string $comment
 * @property ORM_Iterator $membership_interrupts
 */
class Members_fee_Model extends ORM
{
	protected $belongs_to = array('fee', 'member');
	protected $has_many = array('membership_interrupts');

	/**
	 * Checks whether exists another tariff for this member and this fee type in the same time
	 *
	 * @author Michal Kliment
	 * @param numeric $member_id
	 * @param numeric $fee_type_id
	 * @param string $activation_date
	 * @param string $deactivation_date
	 * @param numeric $members_fee_id
	 * @return Mysql_Result object
	 */
	public function exists(
			$member_id, $fee_type_id, $activation_date,
			$deactivation_date = '9999-12-31 23:59:59',
			$members_fee_id = NULL, $priority = 1)
	{
		$edit_clause = ($members_fee_id) ? ' AND members_fees.id <> ' . intval($members_fee_id) : '';

		return $this->db->query("
				SELECT *
				FROM members_fees
				LEFT JOIN fees ON members_fees.fee_id = fees.id
				WHERE member_id = ? AND fees.type_id = ? AND
					(
						(
							? BETWEEN activation_date AND deactivation_date OR
							? BETWEEN activation_date AND deactivation_date
						) OR (
							activation_date BETWEEN ? AND ? AND deactivation_date BETWEEN ? AND ?
						)
					) AND priority = ?
					$edit_clause
		", array
		(
			$member_id, $fee_type_id, $activation_date, $deactivation_date,
			$activation_date, $deactivation_date, $activation_date, $deactivation_date,
			$priority, 
		));
	}

	/**
	 * Returns all fees belongs to member
	 *
	 * @author Michal Kliment
	 * @param numeric $member_id
	 * @return Mysql_Result object
	 */
	public function get_all_fees_by_member_id($member_id)
	{
		return $this->db->query("
				SELECT mf.*, f.readonly, f.type_id AS fee_type_id,
					IFNULL(t.translated_term,et.value) AS fee_type_name,
					f.name AS fee_name, f.fee AS fee_fee, 1 AS status,
					f.special_type_id, mf.comment
				FROM members_fees mf
				LEFT JOIN fees f ON mf.fee_id = f.id
				LEFT JOIN enum_types et ON f.type_id = et.id
				LEFT JOIN translations t ON et.value = t.original_term
				WHERE mf.member_id = ?
				ORDER BY fee_type_id, mf.activation_date, mf.priority
		", $member_id);
	}

	/**
	 * Return active fee for current date by member and fee type
	 *
	 * @author Michal Kliment
	 * @param numeric $member_id
	 * @param numeric $type_id
	 * @return Database_Result object
	 */
	public function get_active_fee_by_member_type($member_id, $type_id)
	{
		$result = $this->db->query("
				SELECT mf.*
				FROM members_fees mf
				LEFT JOIN fees f ON mf.fee_id = f.id
				WHERE mf.activation_date <= ? AND mf.deactivation_date >=  ?
					AND f.type_id = ? AND mf.member_id = ?
				ORDER BY priority LIMIT 0,1
		", date('Y-m-d'), date('Y-m-d'), $type_id, $member_id);
		
		return ($result && $result->count()) ? $result->current() : null;
	}
	
	/**
	 * Caclulate additional payment for services before membership
	 * 
	 * @author Ondřej Fibich
	 * @see Members_Controller#approve_applicant
	 * @param integer $applicant_id	Member ID
	 * @param string $connected_from Y-m-d format
	 * @param string $entrance_date	Y-m-d format
	 * @return double
	 */
	public function calculate_additional_payment_of_applicant($connected_from, $entrance_date)
	{
		if (empty($connected_from) || ($connected_from == '0000-00-00'))
		{
			return 0;
		}
		
		$fee_model = new Fee_Model();
		$amount = 0;
		$current_year = date('Y', strtotime($entrance_date));
		$current_month = date('m', strtotime($entrance_date));
		$year = date('Y', strtotime($connected_from));
		$month = date('m', strtotime($connected_from));
		$deduct_day = max(1, min(31, Settings::get('deduct_day')));
		
		// will be payed later in ordinary member fees
		if (date('d', strtotime($entrance_date)) <= $deduct_day)
		{
			if (--$current_month <= 0)
			{
				$current_month = 12;
				$year--;
			}
		}

		// get payment (loop gets default regular member fees)
		while ($year <= $current_year)
		{
			$to_month = ($year == $current_year) ? $current_month : 12;

			while ($month <= $to_month)
			{
				$date = date('Y-m-d', mktime(0, 0, 0, $month, $deduct_day, $year));
				$fee = $fee_model->get_default_fee_by_date_type($date, 'regular member fee');
				
				if ($fee && $fee->id)
				{
					$amount += $fee->fee;
				}
					
				$month++;
			}

			$month = 1;
			$year++;
		}
		
		return $amount;
	}

}
