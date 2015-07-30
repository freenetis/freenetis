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
 * Comment from thread
 * 
 * @author	Michal Kliment
 * @package Model
 * 
 * @property integer $id
 * @property integer $comments_thread_id
 * @property Comments_thread_Model $comments_thread
 * @property integer $user_id
 * @property User_Model $user
 * @property string $text
 * @property datetime $datetime
 */
class Comment_Model extends ORM
{
	protected $belongs_to  = array('comments_thread', 'user');

	/**
	 * Returns all comments belongs to thread
	 *
	 * @author Michal Kliment
	 * @param int $coments_thread_id
	 * @return Mysql_Result
	 */
	public function get_all_comments_by_comments_thread($coments_thread_id)
	{
		return $this->db->query("
				SELECT c.*, CONCAT(u.name,' ',u.surname) AS user_name FROM comments c
				LEFT JOIN users u ON c.user_id = u.id
				WHERE comments_thread_id = ?
				ORDER BY datetime DESC
		", array($coments_thread_id));
	}

	/**
	 * Returns all comments of user
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_all_comments_by_user($user_id)
	{
		return $this->db->query("
			SELECT c.id, c.text, c.datetime, c.user_id, t.type,
				m.name AS member_name, m.id AS member_id,
				CONCAT(u.name,' ',u.surname) AS work_user_name,
				u.id AS work_user_id, j.id AS work_id
			FROM comments c
			JOIN comments_threads t ON c.comments_thread_id = t.id
			LEFT JOIN accounts a ON a.comments_thread_id = t.id
			LEFT JOIN members m ON a.member_id = m.id
			LEFT JOIN jobs j ON j.comments_thread_id = t.id
			LEFT JOIN users u ON j.user_id = u.id
			WHERE c.user_id = ?
			ORDER BY datetime DESC
		", array($user_id));
	}
}
