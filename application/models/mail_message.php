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
 * Mail message for inner messaging.
 * 
 * @author Michal Kliment
 * @package Model
 * 
 * 
 * @property int $id
 * @property int $from_id
 * @property User_Model $from
 * @property int $to_id
 * @property User_Model $to
 * @property string $subject
 * @property string $body
 * @property datetime $time
 * @property bool $readed
 * @property bool $from_deleted
 * @property bool $to_deleted
 */
class Mail_message_Model extends ORM
{
	protected $belongs_to = array('from' => 'user', 'to' => 'user');

	/**
	 * Returns all inbox messages of user
	 * 
	 * @author Michal Kliment
	 * @param number $user_id
	 * @param number $limit_from
	 * @param number $limit_results
	 * @return Mysql_Result object
	 */
	public function get_all_inbox_messages_by_user_id(
			$user_id, $limit_from = 0, $limit_results = 50)
	{
		return $this->db->query('
				SELECT m.*, CONCAT(u.name, \' \', u.surname) AS user_name,
					u.member_id, 1 AS `delete`
				FROM mail_messages m
				LEFT JOIN users u ON m.from_id = u.id
				WHERE to_id = ? AND to_deleted = 0
				ORDER BY time DESC, m.id DESC
				LIMIT '.intval($limit_from).', '.intval($limit_results).'
		', $user_id);
	}

	/**
	 * Returns count of all inbox messages of user
	 * 
	 * @author Michal Kliment
	 * @param number $user_id
	 * @return number
	 */
	public function count_all_inbox_messages_by_user_id($user_id)
	{
		return $this->db->query('
				SELECT COUNT(*) AS count
				FROM mail_messages m
				LEFT JOIN users u ON m.from_id = u.id
				WHERE to_id = ? AND to_deleted = 0
		', $user_id)->current()->count;
	}

	/**
	 * Returns count of all unread inbox messages of user
	 * 
	 * @author Michal Kliment
	 * @param number $user_id
	 * @return number
	 */
	public function count_all_unread_inbox_messages_by_user_id($user_id)
	{
		return $this->db->query('
				SELECT COUNT(*) AS count
				FROM mail_messages m
				LEFT JOIN users u ON m.from_id = u.id
				WHERE to_id = ? AND to_deleted = 0 AND readed = 0
		', $user_id)->current()->count;
	}

	/**
	 * Returns all sent messages of user
	 * 
	 * @author Michal Kliment
	 * @param number $user_id
	 * @param number $limit_from
	 * @param number $limit_results
	 * @return Mysql_Result object
	 */
	public function get_all_sent_messages_by_user_id(
			$user_id, $limit_from = 0, $limit_results = 50)
	{
		return $this->db->query('
				SELECT m.*, CONCAT(u.name, \' \', u.surname) AS user_name,
					u.member_id, 1 AS `delete`
				FROM mail_messages m
				LEFT JOIN users u ON m.to_id = u.id
				WHERE from_id = ? AND from_deleted = 0
				ORDER BY time DESC, m.id DESC
				LIMIT '.intval($limit_from).', '.intval($limit_results).'
		', $user_id);
	}

	/**
	 * Returns count of all sent messages of user
	 * 
	 * @author Michal Kliment
	 * @param number $user_id
	 * @return number
	 */
	public function count_all_sent_messages_by_user_id($user_id)
	{
		return $this->db->query('
				SELECT COUNT(*) AS count
				FROM mail_messages m
				LEFT JOIN users u ON m.to_id = u.id
				WHERE from_id = ? AND from_deleted = 0
		', $user_id)->current()->count;
	}
}
