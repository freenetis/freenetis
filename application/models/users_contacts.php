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
 * Pivot table for connecting users and theirs contacts, also containts
 * whitelisted column for defining whitelist of contact in notification.
 * 
 * @package Model
 */
class Users_contacts_Model extends Model
{
	/** Contact is not whitelisted */
	const NO_WHITELIST			= 0;
	/** Contact is under pernament whitelisted */
	const PERMANENT_WHITELIST	= 1;
	/** Contact is under temporary whitelisted */
	const TEMPORARY_WHITELIST	= 2;

	/**
	 * Sets whitelist flag for member's conntact
	 * 
	 * @author Michal Kliment
	 * @param integer $whitelist
	 * @param integer $member_id
	 * @param integer $type 
	 */
	public function set_whitelist_by_member_and_type($whitelist, $member_id, $type)
	{
		$this->db->query("
			UPDATE users_contacts uc, contacts c, users u
			SET uc.whitelisted = ?
			WHERE
				uc.contact_id = c.id
				AND c.type = ?
				AND uc.user_id = u.id
				AND u.member_id = ?
		", $whitelist, $type, $member_id);
	}
	
	/**
	 * Cleans temporary whitelist for contacts
	 * 
	 * @author Michal Kliment
	 */
	public function clean_temporary_whitelist()
	{
		$this->db->query("
			UPDATE users_contacts uc
			SET uc.whitelisted = ?
			WHERE uc.whitelisted = ?
		", self::NO_WHITELIST, self::TEMPORARY_WHITELIST);
	}
	
	/**
	 * Returns all contacts of debtors by type
	 * 
	 * @author Michal Kliment
	 * @param double $debtor_boundary
	 * @param integer $type
	 * @return Mysql_Result
	 */
	public function get_contacts_of_debtors_by_type($debtor_boundary, $type)
	{
		return $this->db->query("
			SELECT
				c.value, a.balance, m.id AS member_id, m.name AS member_name,
				(
					SELECT GROUP_CONCAT(vs.variable_symbol) AS variable_symbol
					FROM variable_symbols vs
					WHERE vs.account_id = a.id
				) AS variable_symbol, u.login, cou.country_code
			FROM members m
			JOIN accounts a ON a.member_id = m.id AND m.id <> 1
			JOIN users u ON u.member_id = m.id
			JOIN users_contacts uc ON uc.user_id = u.id
			JOIN contacts c ON uc.contact_id = c.id AND c.type = ?
			LEFT JOIN contacts_countries cc ON cc.contact_id = c.id
			LEFT JOIN countries cou ON cou.id = cc.country_id
			WHERE uc.whitelisted = ? AND m.type <> ? AND m.id NOT IN
			(
				SELECT m.id
				FROM members m
				JOIN membership_interrupts mi ON mi.member_id = m.id
				JOIN members_fees mf ON mi.members_fee_id = mf.id
				WHERE mf.activation_date <= CURDATE() AND mf.deactivation_date >= CURDATE()
			) AND DATEDIFF(CURDATE(), m.entrance_date) >= ".intval(Settings::get('initial_debtor_immunity'))."
			AND a.balance < ".intval($debtor_boundary)."
		", $type, self::NO_WHITELIST, Member_Model::TYPE_FORMER);
	}
	
	/**
	 * Returns all contacts of almost-debtors
	 * 
	 * @author Michal Kliment
	 * @param double $payment_notice_boundary
	 * @param double $debtor_boundary
	 * @param integer $type
	 * @return Mysql_Result
	 */
	public function get_contacts_of_almostdebtors_by_type(
			$payment_notice_boundary, $debtor_boundary, $type)
	{
		return $this->db->query("
			SELECT
			c.value, a.balance, m.id AS member_id, m.name AS member_name,
			(
				SELECT GROUP_CONCAT(vs.variable_symbol) AS variable_symbol
				FROM variable_symbols vs
				WHERE vs.account_id = a.id
			) AS variable_symbol, u.login, cou.country_code
			FROM members m
			JOIN accounts a ON a.member_id = m.id AND m.id <> 1
			JOIN users u ON u.member_id = m.id
			JOIN users_contacts uc ON uc.user_id = u.id
			JOIN contacts c ON uc.contact_id = c.id AND c.type = ?
			LEFT JOIN contacts_countries cc ON cc.contact_id = c.id
			LEFT JOIN countries cou ON cou.id = cc.country_id
			WHERE uc.whitelisted = ? AND m.type <> ? AND m.id NOT IN
			(
				SELECT m.id
				FROM members m
				JOIN membership_interrupts mi ON mi.member_id = m.id
				JOIN members_fees mf ON mi.members_fee_id = mf.id
				WHERE mf.activation_date <= CURDATE() AND mf.deactivation_date >= CURDATE()
			)
			AND a.balance < ".intval($payment_notice_boundary)."
			AND
			(
				DATEDIFF(CURDATE(), m.entrance_date) >= ".intval(Settings::get('initial_debtor_immunity'))."
				AND a.balance >= ".intval($debtor_boundary)."
				OR DATEDIFF(CURDATE(), m.entrance_date) < ".intval(Settings::get('initial_debtor_immunity'))."
				AND DATEDIFF(CURDATE(), m.entrance_date) >= ".intval(Settings::get('initial_immunity'))."
			)
		",
		array
		(
			$type,
			self::NO_WHITELIST,
			Member_Model::TYPE_FORMER
		));
	}
	
	/**
	 * Returns all contacts of members by type
	 * 
	 * @author Michal Kliment
	 * @param type $member_id
	 * @param integer $type
	 * @param bool $ignore_whitelisted
	 * @return Mysql_Result
	 */
	public function get_contacts_by_member_and_type (
			$member_id, $type, $ignore_whitelisted = FALSE)
	{
		if ($ignore_whitelisted)
			$whitelisted = "";
		else
			$whitelisted = "AND uc.whitelisted = 0";
		
		return $this->db->query("
			SELECT c.value, a.balance, m.id AS member_id, m.name AS member_name,
				(
				SELECT GROUP_CONCAT(vs.variable_symbol) AS variable_symbol
				FROM variable_symbols vs
				WHERE vs.account_id = a.id
				) AS variable_symbol, u.login, cou.country_code
			FROM members m
			JOIN accounts a ON a.member_id = m.id
			JOIN users u ON u.member_id = m.id
			JOIN users_contacts uc ON uc.user_id = u.id $whitelisted
			JOIN contacts c ON uc.contact_id = c.id AND c.type = ?
			LEFT JOIN contacts_countries cc ON cc.contact_id = c.id
			LEFT JOIN countries cou ON cou.id = cc.country_id
			WHERE m.id = ? AND m.type <> ? AND m.id NOT in
			(
				SELECT m.id
				FROM members m
				JOIN membership_interrupts mi ON mi.member_id = m.id
				JOIN members_fees mf ON mi.members_fee_id = mf.id
				WHERE mf.activation_date <= CURDATE() AND mf.deactivation_date >= CURDATE()
			)
			GROUP BY c.id
		", $type, $member_id, Member_Model::TYPE_FORMER);
	}
	
	/**
	 * Returns all contacts by given type
	 * 
	 * @author Michal Kliment
	 * @param integer $type
	 * @param bool $ignore_whitelisted
	 * @return Mysql_Result
	 */
	public function get_all_contacts_by_type ($type, $ignore_whitelisted = FALSE)
	{
		if ($ignore_whitelisted)
			$whitelisted = "";
		else
			$whitelisted = "AND uc.whitelisted = 0";
		
		return $this->db->query("
			SELECT c.value, a.balance, m.id AS member_id, m.name AS member_name,
				(
				SELECT GROUP_CONCAT(vs.variable_symbol) AS variable_symbol
				FROM variable_symbols vs
				WHERE vs.account_id = a.id
				) AS variable_symbol, u.login, cou.country_code
			FROM contacts c
			JOIN users_contacts uc ON uc.contact_id = c.id $whitelisted
			JOIN users u ON uc.user_id = u.id
			JOIN members m ON u.member_id = m.id
			JOIN accounts a ON a.member_id = m.id
			LEFT JOIN contacts_countries cc ON cc.contact_id = c.id
			LEFT JOIN countries cou ON cou.id = cc.country_id
			WHERE m.type <> ? AND c.type = ? AND m.id NOT in
			(
				SELECT m.id
				FROM members m
				JOIN membership_interrupts mi ON mi.member_id = m.id
				JOIN members_fees mf ON mi.members_fee_id = mf.id
				WHERE mf.activation_date <= CURDATE() AND mf.deactivation_date >= CURDATE()
			)
			GROUP BY c.id
		", array(Member_Model::TYPE_FORMER, $type));
	}
}
