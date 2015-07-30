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
	 * Creates a new mail and also redirect it to receiver's e-mail box if he
	 * enabled it.
	 * 
	 * @param type $from_id Sender user ID
	 * @param type $to_id Receiver User ID
	 * @param type $subject Subject of the message
	 * @param type $body Body of the message
	 * @param type $from_deleted Mark as deleted in the sender side? [optional]
	 * @param type $to_deleted Mark as deleted in the receiver side? [optional]
	 * @param type $time Time of sending [optional]
	 * @param type $readed Mark as readed? [optional]
	 */
	public static function create($from_id, $to_id, $subject, $body,
			$from_deleted = 0, $to_deleted = 0, $time = NULL, $readed = 0)
	{
		if (empty($time))
		{
			$time = date('Y-m-d H:i:s');
		}
		// mail
		$mail_message = new Mail_message_Model();
		$mail_message->from_id = $from_id;
		$mail_message->to_id = $to_id;
		$mail_message->subject = $subject;
		$mail_message->body = $body;
		$mail_message->from_deleted = $from_deleted;
		$mail_message->to_deleted = $to_deleted;
		$mail_message->time = $time;
		$mail_message->readed = $readed;
		$mail_message->save_throwable();
		
		// redirection
		$uc_model = new Users_contacts_Model();
		$email_queue = new Email_queue_Model();
		$redir_emails = $uc_model->get_redirected_email_boxes_of($to_id);
		
		$from = Settings::get('email_default_email');
		$fn_link = html::anchor('/mail/inbox');
		$reply_link = html::anchor('/mail/write_message/0/' . $mail_message->id, __('here'));
		$subject_prefix = Settings::get('title');
		$header = 'This message was redirected to you from your account at %s.';
		$footer = '';
		
		// do not reply to system message
		if ($mail_message->from->member_id != Member_Model::ASSOCIATION)
		{
			$footer = __('You can reply to this message %s.', $reply_link);
		}
		
		// formated subject
		if (mail_message::is_formated($subject))
		{
			$subject = mail_message::printf($subject);
		}
		
		// formated body
		if (mail_message::is_formated($body))
		{
			$body = mail_message::printf($body);
		}
		
		if (Settings::get('email_enabled') && count($redir_emails))
		{
			foreach ($redir_emails as $e)
			{
				// subject
				$email_subject = $subject_prefix . ': ' . $subject; 
				// append header and footer
				$email_body = __($header, $fn_link) . '<hr>' . $body . '<hr>' . $footer; 
				// send
				$email_queue->push($from, $e->value, $email_subject, $email_body);
			}
		}
	}

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
	 * Marks all user's messages as read
	 * @param number $user_id
	 * @return Mysql_Result object
	 */
	public function mark_all_inbox_messages_as_read_by_user_id($user_id)
	{
		return $this->db->query('
				UPDATE mail_messages
				SET readed = 1
				WHERE to_id = ? AND to_deleted = 0
			', $user_id);
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
	
	/**
	 * Send system message to item watchers
	 * 
	 * @author Michal Kliment
	 * @param string $subject
	 * @param string $body
	 * @param integer $type
	 * @param integer $fk_id
	 */
	public static function send_system_message_to_item_watchers ($subject, $body, $type, $fk_id)
	{	
		$watcher_model = new Watcher_Model();
		
		$watchers = $watcher_model
			->get_watchers_by_object($type, $fk_id);
		
		foreach ($watchers as $watcher)
		{
			// sends message						
			self::create(
				Member_Model::ASSOCIATION, $watcher,
				$subject, $body, 1
			);
		}
	}
}
