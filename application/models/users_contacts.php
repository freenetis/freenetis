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
	
	/**
	 * Returns all contacts of members by type
	 * 
	 * @author Michal Kliment, Ondrej Fibich
	 * @param integer|array $member_id Member ID or array of member IDs
	 * @param integer $type
	 * @param boolean $ignore_whitelisted
	 * @param boolean $notify_former_members Can be former members notified?
	 * @param boolean $notify_interrupted_members Can be interrupted members notified?
	 * @param boolean $ignore_member_notif_settings Should be member notification setting ignored?
	 * @return Mysql_Result
	 */
	public function get_contacts_by_member_and_type (
			$member_id, $type, $ignore_whitelisted = FALSE,
			$notify_former_members = FALSE, $notify_interrupted_members = FALSE,
			$ignore_member_notif_settings = FALSE)
	{
		/* where */
		if (!is_array($member_id))
		{
			$where = 'm.id = ' . intval($member_id);
		}
		else if (count($member_id))
		{
			$where = 'm.id IN (' . implode(',', array_map('intval', $member_id)) . ')';
		}
		else // empty (non sense condition)
		{
			$where = '1 = 2';
		}
		
		/* whitelist */
		$whitelisted = '';
		
		if (!$ignore_whitelisted)
		{
			$whitelisted = " AND m.id NOT IN
				(
					SELECT mw.member_id
					FROM members_whitelists mw
					WHERE mw.member_id = " . intval($member_id) . "
						AND mw.since <= CURDATE()
						AND mw.until >= CURDATE()
				)";
		}
		
		/* member whitelist - member notification settings */
		$member_whitelisted = '';
		
		if (!$ignore_member_notif_settings)
		{
			switch ($type)
			{
				case Contact_Model::TYPE_EMAIL:
					$member_whitelisted = " AND m.notification_by_email > 0 ";
					break;
				case Contact_Model::TYPE_PHONE:
					$member_whitelisted = " AND m.notification_by_sms > 0 ";
					break;
			}
		}
		
		/* former members */
		$former = '';
		
		if (!$notify_former_members)
		{
			$former = ' AND m.type <> ' . intval(Member_Model::TYPE_FORMER);
		}
		
		/* interrupted members */		
		$interrupted = '';
		
		if (!$notify_interrupted_members)
		{
			$interrupted = " AND m.id NOT IN
				(
					SELECT mi.member_id
					FROM membership_interrupts mi
					JOIN members_fees mf ON mi.members_fee_id = mf.id
					WHERE mi.member_id = " . intval($member_id) . "
						AND mf.activation_date <= CURDATE()
						AND mf.deactivation_date >= CURDATE()
				) ";
		}
		
		/* query */
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
			JOIN users_contacts uc ON uc.user_id = u.id
			JOIN contacts c ON uc.contact_id = c.id AND c.type = ?
			LEFT JOIN contacts_countries cc ON cc.contact_id = c.id
			LEFT JOIN countries cou ON cou.id = cc.country_id
			WHERE $where $former $whitelisted $member_whitelisted $interrupted
			GROUP BY c.id
		", $type);
	}
	
	/**
	 * Returns all contacts of users by type
	 * 
	 * @author Michal Kliment, Ondrej Fibich
	 * @param integer|array $user_id User ID or array of user IDs
	 * @param integer $type
	 * @param boolean $ignore_whitelisted
	 * @param boolean $notify_former_members Can be former members notified?
	 * @param boolean $notify_interrupted_members Can be interrupted members notified?
	 * @param boolean $ignore_member_notif_settings Should be member notification setting ignored?
	 * @return Mysql_Result
	 */
	public function get_contacts_by_user_and_type (
			$user_id, $type, $ignore_whitelisted = FALSE,
			$notify_former_members = FALSE, $notify_interrupted_members = FALSE,
			$ignore_member_notif_settings = FALSE)
	{
		/* where */
		if (!is_array($user_id))
		{
			$where = 'u.id = ' . intval($user_id);
		}
		else if (count($user_id))
		{
			$where = 'u.id IN (' . implode(',', array_map('intval', $user_id)) . ')';
		}
		else // empty (non sense condition)
		{
			$where = '1 = 2';
		}
		
		/* whitelist */
		$whitelisted = '';
		
		if (!$ignore_whitelisted)
		{
			$whitelisted = " AND m.id NOT IN
				(
					SELECT mw.member_id
					FROM members_whitelists mw
					WHERE mw.member_id = " . intval($user_id) . "
						AND mw.since <= CURDATE()
						AND mw.until >= CURDATE()
				)";
		}
		
		/* member whitelist - member notification settings */
		$member_whitelisted = '';
		
		if (!$ignore_member_notif_settings)
		{
			switch ($type)
			{
				case Contact_Model::TYPE_EMAIL:
					$member_whitelisted = " AND m.notification_by_email > 0 ";
					break;
				case Contact_Model::TYPE_PHONE:
					$member_whitelisted = " AND m.notification_by_sms > 0 ";
					break;
			}
		}
		
		/* former members */
		$former = '';
		
		if (!$notify_former_members)
		{
			$former = ' AND m.type <> ' . intval(Member_Model::TYPE_FORMER);
		}
		
		/* interrupted members */		
		$interrupted = '';
		
		if (!$notify_interrupted_members)
		{
			$interrupted = " AND m.id NOT IN
				(
					SELECT mi.member_id
					FROM membership_interrupts mi
					JOIN members_fees mf ON mi.members_fee_id = mf.id
					WHERE mi.member_id = " . intval($user_id) . "
						AND mf.activation_date <= CURDATE()
						AND mf.deactivation_date >= CURDATE()
				) ";
		}
		
		/* query */
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
			JOIN users_contacts uc ON uc.user_id = u.id
			JOIN contacts c ON uc.contact_id = c.id AND c.type = ?
			LEFT JOIN contacts_countries cc ON cc.contact_id = c.id
			LEFT JOIN countries cou ON cou.id = cc.country_id
			WHERE $where $former $whitelisted $member_whitelisted $interrupted
			GROUP BY c.id
		", $type);
	}
	
	/**
	 * Finds e-mail boxes of the given user on which the inner user mail may
	 * be redirected.
	 * 
	 * @param int $user_id User ID
	 * @return Mysql_Result Email contacts
	 */
	public function get_redirected_email_boxes_of($user_id) {
		return $this->db->query("
			SELECT c.*
			FROM users_contacts uc
			JOIN contacts c ON c.id = uc.contact_id
			WHERE uc.user_id = ? AND c.type = ? AND uc.mail_redirection = 1
		", $user_id, Contact_Model::TYPE_EMAIL);
	}
	
	/**
	 * Gets ID of user who owns given contact.
	 * 
	 * @param int $contact_id
	 * @return int|null User ID or null
	 */
	public function get_user_of_contact($contact_id)
	{
		$result = $this->db->query("
				SELECT user_id
				FROM users_contacts
				WHERE contact_id = ?
		", $contact_id);
		
		if ($result && $result->count() == 1)
		{
			return $result->current()->user_id;
		}
		
		return null;
	}
	
}
