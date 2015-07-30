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
 * Messages for notifications and actions for activating redirection and
 * sending email and SMS messages for notigfication.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $name
 * @property string $text
 * @property string $email_text
 * @property string $sms_text
 * @property integer $type
 * @property integer $self_cancel
 * @property boolean $ignore_whitelist
 */
class Message_Model extends ORM
{
	
	// type constants
	
	/**
	 * user message, can be added and deleted by user
	 */
	const USER_MESSAGE							= 0;
	
	/**
	 * not exactly message, it is content of side panel,
	 * should be used for information for all redirections
	 */
	const CONTACT_INFORMATION					= 1;
	
	/**
	 * content of page shown after canceling redirection
	 */
	const CANCEL_MESSAGE						= 2;
	
	/**
	 *  content of page with text for unknown device
	 */
	const UNKNOWN_DEVICE_MESSAGE				= 3;
	/**
	 * content of page for interrupted member,
	 * this redirection can be set in system
	 */
	const INTERRUPTED_MEMBERSHIP_MESSAGE		= 4;
	
	/**
	 * content of page for debtor, this redirection can be set in system
	 */
	const DEBTOR_MESSAGE						= 5;
	
	/**
	 * content of page for payment notice, this redirection can be set
	 * in system and can be canceled by user
	 */
	const PAYMENT_NOTICE_MESSAGE				= 6;
	
	/**
	 * content of page for unallowed connecting place, depends on allowed subnets
	 */
	const UNALLOWED_CONNECTING_PLACE_MESSAGE	= 7;
	
	// self-cancel constants
	
	/**
	 * self cancel disabled, remote computer cannot cancel this message
	 */
	const SELF_CANCEL_DISABLED					= 0;
	
	/**
	 * self cancel enabled, every member's IP address will have cancelled
	 * given redirection
	 */
	const SELF_CANCEL_MEMBER					= 1;
	
	/**
	 * self cancel enabled, redirection is canceled only for current remote computer
	 */
	const SELF_CANCEL_IP						= 2;
	
	
	/**
	 * Counts all activated redirections from junction table messages_ip_addresses.
	 * 
	 * @author Jiri Svitak, Ondrej Fibich
	 * @return integer
	 */
	public function count_all_redirections($filter_sql = '')
	{
		if (!empty($filter_sql))
		{
			$where = "WHERE $filter_sql";
		}
		else
		{
			// Optimalization:
			// don't want search throught staff down here because of filter
			// which will be still unused
			return $this->db->count_records('messages_ip_addresses');
		}
		
		return $this->db->query("
			SELECT COUNT(*) AS total FROM
			(
				SELECT mip.*, mm.name AS member_name FROM
				(
					SELECT mip.ip_address_id, ip.ip_address, ms.name AS message,
					mip.datetime, mip.comment, ms.self_cancel, ms.type,
					IFNULL(u.member_id,ip.member_id) AS member_id
					FROM messages_ip_addresses mip
					LEFT JOIN ip_addresses ip ON mip.ip_address_id = ip.id
					LEFT JOIN ifaces i ON ip.iface_id = i.id
					LEFT JOIN messages ms ON mip.message_id = ms.id
					LEFT JOIN devices d ON d.id = i.device_id
					LEFT JOIN users u ON u.id = d.user_id
				) mip
				LEFT JOIN members mm ON mm.id =	mip.member_id
			) mip
			$where
		")->current()->total;
	}
	
	/**
	 * Gets all activated redirections from junction table messages_ip_addresses.
	 * 
	 * @return Mysql_Result
	 */
	public function get_all_redirections(
			$limit_from = 0, $limit_results = 20, $order_by = 'ip_address',
			$order_by_direction = 'ASC', $filter_sql = '')
	{
		// direction
		if (strtolower($order_by_direction) != 'asc')
		{
			$order_by_direction = 'DESC';
		}
		// order by check
		if ($order_by == 'ip_address')
		{
			//$order_by = 'inet_aton(ip_address)';
			$order_by = 'inet_aton(ip_address) ASC, self_cancel DESC, mip.datetime ASC';
			$order_by_direction = '';
		}
		else
		{
			$order_by = $this->db->escape_column($order_by);
		}
		
		$where = '';
		if ($filter_sql)
			$where = "WHERE $filter_sql";
		
		// query
		return $this->db->query("
			SELECT * FROM
			(
				SELECT mip.*, mm.name AS member_name FROM
				(
					SELECT mip.ip_address_id, ip.ip_address, ms.name AS message,
					mip.datetime, mip.comment, ms.self_cancel, ms.type,
					IFNULL(u.member_id,ip.member_id) AS member_id
					FROM messages_ip_addresses mip
					LEFT JOIN ip_addresses ip ON mip.ip_address_id = ip.id
					LEFT JOIN ifaces i ON ip.iface_id = i.id
					LEFT JOIN messages ms ON mip.message_id = ms.id
					LEFT JOIN devices d ON d.id = i.device_id
					LEFT JOIN users u ON u.id = d.user_id
				) mip
				LEFT JOIN members mm ON mm.id =	mip.member_id
			) mip
			$where
			ORDER BY $order_by $order_by_direction
			LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		");
	}
	
	/**
	 * Counts all messages.
	 * 
	 * @return integer
	 */
	public function count_all_messages()
	{
		return $this->db->count_records('messages');
	}
	
	/**
	 * Gets all redirection messages.
	 * 
	 * @author Jiri Svitak
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_all_messages(
			$limit_from = 0, $limit_results = 20,
			$order_by = 'id', $order_by_direction = 'asc',
			$filter_values = array())
	{
		// order by check
		if (!$this->has_column($order_by))
		{
			$order_by = 'id';
		}
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
			SELECT m.id, m.name AS message, m.type, m.self_cancel,
			m.ignore_whitelist
			FROM messages m
			ORDER BY $order_by $order_by_direction
			LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "		
		");	
	}
	
	/**
	 * Activates notifications for ip addresses with unallowed connecting place
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id	Who redirects
	 * @return integer 			Number of ip addresses activated
	 */
	public function activate_unallowed_connecting_place_message($user_id)
	{
		$mm = new Message_Model();
		
		try
		{
			$mm->transaction_start();
			
			// preparation
			$message = ORM::factory('message')->where(array
			(
				'type' => self::UNALLOWED_CONNECTING_PLACE_MESSAGE
			))->find();

			// message do not exists
			if (!$message || !$message->id)
			{
				throw new Exception('Unallowed connecting place message not founded');
			}

			// deletes old redirections
			Database::instance()->delete('messages_ip_addresses', array
			(
				'message_id' => $message->id
			));

			// find IP addresses with interrupted membership
			$ip_model = new Ip_address_Model();

			$ips = $ip_model->get_ip_addresses_with_unallowed_connecting_place();

			// activate
			$result = self::activate_redirection($message, $ips, $user_id);
			
			$mm->transaction_commit();
			
			return $result;
		}
		catch (Exception $e)
		{
			$mm->transaction_rollback();
			Log::add_exception($e);
			
			return 0;
		}
	}
	
	/**
	 * Activates user message by given id
	 * 
	 * @author Ondřej Fibich
	 * @param integer $message_id		ID of message
	 * @param integer $user_id			Who redirects
	 * @param integer $redirection		Redirection state
	 * @param integer $email			E-mail state
	 * @param integer $sms				SMS state
	 * @return array					Stats array
	 */
	public function activate_user_message(
			$message_id, $user_id, $redirection, $email, $sms)
	{
		
		// stats
		$s = array
		(	
			'ip_count'		=> 0,
			'email_count'	=> 0,
			'sms_count'		=> 0
		);
		
		// helper models
		$ip_model = new Ip_address_Model();
		$uc_model = new Users_contacts_Model();
		$message = new Message_Model($message_id);
		
		// message do not exists
		if (!$message || !$message->id)
		{
			throw new Exception('Message not founded');
		}
		
		// redirection
		if ($redirection == Notifications_Controller::ACTIVATE)
		{
			// find IP addresses of debtors
			$ips = $ip_model->find_all();
			// activate redirection for finded IP addresses
			$s['ip_count'] = self::activate_redirection($message, $ips, $user_id);
		}
		
		// send emails
		if ($email == Notifications_Controller::ACTIVATE)
		{
			// find email addresses of debtors
			$emails = $uc_model->get_all_contacts_by_type(
					Contact_Model::TYPE_EMAIL, $message->ignore_whitelist
			);
			// send emails for finded emails
			$s['email_count'] = self::send_emails($message, $emails);
		}
		
		// send SMS messages
		if ($sms == Notifications_Controller::ACTIVATE)
		{
			// find phone numbers of debtors
			$smss = $uc_model->get_all_contacts_by_type(
					Contact_Model::TYPE_PHONE, $message->ignore_whitelist
			);
			// send SMS messages for finded phone numbers
			$s['sms_count'] = self::send_sms_messages($message, $smss, $user_id);
		}
		
		// return stats array
		return $s;
	}

	/**
	 * Deactivates all redirections of given message.
	 * 
	 * @author Jiri Svitak
	 * @param integer $message_id
	 */
	public function deactivate_message($message_id)
	{
		$this->db->query("
			DELETE FROM messages_ip_addresses
			WHERE message_id = ?
		", $message_id);
	}

	/**
	 * Returns id of message by given type
	 * 
	 * @author Michal Kliment
	 * @param integer $type
	 * @return integer 
	 */
	public function get_message_id_by_type($type)
	{
		if (!$type)
		{
			return NULL;
		}
		
		$message = $this->where('type', $type)->find();
		
		if ($message->id)
		{
			return $message->id;
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Activate redirection for given IPs
	 *
	 * @param Message_Model $message	Message to redirect
	 * @param Mysql_Result $ips			IPs to redirect
	 * @param mixed $user_id			ID of who added redirection or NULL
	 * @param string $comment			Comment
	 * @return integer					Number of redirected IPs
	 * @throws Exception				On error
	 */
	public static function activate_redirection(
			Message_Model $message, $ips, $user_id = NULL, $comment = NULL)
	{
		// param check
		if (!$message->id || !is_object($ips))
		{
			throw new Exception('Wrong args');
		}
		
		// preparations
		$datetime = date('Y-m-d H:i:s');
		$user_id = (intval($user_id)) ? intval($user_id) : 'NULL';
		$comment = Database::instance()->escape($comment);
		
		// ip count stats
		$ip_count = 0;

		// first sql for inserting transfers
		$sql_insert = "REPLACE messages_ip_addresses "
					. "(message_id, ip_address_id, user_id, comment, datetime) "
					. "VALUES ";

		$values = array();
		// set new redirections in junction table
		foreach($ips as $ip)
		{
			// insert values
			$values[] = "($message->id, $ip->id, $user_id, $comment, '$datetime')";
			$ip_count++;
		}

		// any redirection?
		if (count($values) > 0)
		{
			$sql_insert .= implode(',', $values);

			if (!Database::instance()->query($sql_insert))
			{
				throw new Exception();
			}
		}
		
		return $ip_count;
	}
	
	/**
	 * Send emails with redirection message to given contacts
	 *
	 * @param Message_Model $message	Message to send
	 * @param Mysql_Result $contacts	Contacts to send
	 * @param string $comment			Comment
	 * @return integer					Number of sended emails
	 * @throws Exception				On error
	 */
	public static function send_emails(
			Message_Model $message, $contacts, $comment = NULL)
	{
		// param check
		if (!$message->id || !is_object($contacts))
		{
			throw new Exception('Wrong args');
		}
		
		// emails counter
		$email_count = 0;
		
		// if default email ans subject prefix is set
		if (!Settings::get('email_default_email') ||
			!Settings::get('email_subject_prefix'))
		{
			throw new Exception('Email not configured properly');
		}
		
		// continue
		try
		{
			// Email queues model
			$eq_model = new Email_queue_Model();
			// start transaction
			$eq_model->transaction_start();

			// for each contact
			foreach ($contacts as $contact)
			{
				// text of message
				$text = $message->email_text;

				// replace tags
				foreach ($contact as $key => $value)
				{
					if ($key != 'email_text' && $key != 'country_code')
					{
						$text = str_replace('{'.$key.'}', $value, $text);
					}
				}
				// replace comment
				$text = str_replace('{comment}', $comment, $text);

				// if empty message do not send
				if (empty($text))
				{
					continue;
				}

				// subject
				$subject = Settings::get('email_subject_prefix')
						 . ': ' . __($message->name);

				// add Email to queue
				$eq_model->clear();
				$eq_model->from = Settings::get('email_default_email');
				$eq_model->to = $contact->value;
				$eq_model->subject = $subject;
				$eq_model->body = $text;
				$eq_model->state = Email_queue_Model::STATE_NEW;
				$eq_model->save_throwable();

				// add SMS to counter
				$email_count++;
			}

			// commit
			$eq_model->transaction_commit();
		}
		catch (Exception $e)
		{
			$eq_model->transaction_rollback();
			Log::add_exception($e);
			throw $e;
		}

		return $email_count;
	}
	
	/**
	 * Send SMS messages with redirection message to given contacts
	 *
	 * @param Message_Model $message	Message to send
	 * @param Mysql_Result $contacts	Contacts to send
	 * @param mixed $user_id			ID of who added redirection or NULL
	 * @param string $comment			Comment
	 * @return integer					Number of sended SMS
	 * @throws Exception				On error
	 */
	public static function send_sms_messages(
			Message_Model $message, $contacts, $user_id = NULL, $comment = NULL)
	{
		// param check
		if (!$message->id || !is_object($contacts))
		{
			throw new Exception('Wrong args');
		}
		
		// preparations
		$datetime = date('Y-m-d H:i:s');
		$user_id = (intval($user_id)) ? intval($user_id) : 'NULL';
		$comment = strip_tags($comment);
		
		// ip count stats
		$sms_count = 0;
		
		// if SMS enabled, is any active SMS driver
		// SMS sender number is set and SMS default driver is set
		if (!Sms::enabled() ||
			!Sms::has_active_driver() ||
			!Settings::get('sms_sender_number') ||
			!Settings::get('sms_driver'))
		{
			return 0;
		}
		
		// continue
		try
		{
			// SMS model
			$sms = new Sms_message_Model();
			// start transaction
			$sms->transaction_start();

			// for each contact
			foreach ($contacts as $contact)
			{
				// text of message
				$text = $message->sms_text;
				
				// number
				$number = $contact->country_code . $contact->value;

				// replace tags
				foreach ($contact as $key => $value)
				{
					if ($key != 'sms_text' && $key != 'country_code')
					{
						$text = str_replace('{'.$key.'}', $value, $text);
					}
				}
				// replace comment
				$text = str_replace('{comment}', $comment, $text);

				// if empty message or invalid number for sending SMS do not send
				if (empty($text) || !Phone_operator_Model::is_sms_enabled_for($number))
				{
					continue;
				}

				// add SMS
				$sms->clear();
				$sms->user_id = $user_id;
				$sms->sms_message_id = NULL;
				$sms->stamp = $datetime;
				$sms->send_date = $datetime;
				$sms->text = text::cs_utf2ascii($text);
				$sms->sender = Settings::get('sms_sender_number');
				$sms->receiver = $number;
				$sms->driver = Settings::get('sms_driver');
				$sms->type = Sms_message_Model::SENT;
				$sms->state = Sms_message_Model::SENT_UNSENT;
				$sms->save_throwable();

				// add SMS to counter
				$sms_count++;
			}

			// end transaction
			$sms->transaction_commit();
		}
		catch (Exception $e)
		{
			$sms->transaction_rollback();
			Log::add_exception($e);
			throw $e;
		}
		
		return $sms_count;
	}	
	
}
