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
	const USER_MESSAGE													= 0;
	
	/**
	 * not exactly message, it is content of side panel,
	 * should be used for information for all redirections
	 */
	const CONTACT_INFORMATION											= 1;
	
	/**
	 * content of page shown after canceling redirection
	 */
	const CANCEL_MESSAGE												= 2;
	
	/**
	 *  content of page with text for unknown device
	 */
	const UNKNOWN_DEVICE_MESSAGE										= 3;
	/**
	 * content of page for interrupted member,
	 * this redirection can be set in system
	 */
	const INTERRUPTED_MEMBERSHIP_MESSAGE								= 4;
	
	/**
	 * content of page for debtor, this redirection can be set in system
	 */
	const DEBTOR_MESSAGE												= 5;
	
	/**
	 * content of page for payment notice, this redirection can be set
	 * in system and can be canceled by user
	 */
	const PAYMENT_NOTICE_MESSAGE										= 6;
	
	/**
	 * content of page for unallowed connecting place, depends on allowed subnets
	 */
	const UNALLOWED_CONNECTING_PLACE_MESSAGE							= 7;
	
	/**
	 * content of page for received payment notice
	 */
	const RECEIVED_PAYMENT_NOTICE_MESSAGE								= 8;
	
	/**
	 * content of page for approved application for membership
	 */
	const APPLICANT_APPROVE_MEMBERSHIP									= 9;
	
	/**
	 * content of page for refused application for membership
	 */
	const APPLICANT_REFUSE_MEMBERSHIP									= 10;
	
	/**
	 * content of page for approved request for connection
	 */
	const CONNECTION_REQUEST_APPROVE									= 11;
	
	/**
	 * content of page for refused request for connection
	 */
	const CONNECTION_REQUEST_REFUSE										= 12;
	
	/**
	 * content of page for information about request for connection
	 */
	const CONNECTION_REQUEST_INFO										= 13;
	
	/**
	 * content of page for information about host down in monitoring
	 */
	const MONITORING_HOST_DOWN											= 14;
	
	/**
	 * content of page for information about host up in monitoring
	 */
	const MONITORING_HOST_UP											= 15;
	
	/**
	 * content of page for information about connection test expiration
	 */
	const CONNECTION_TEST_EXPIRED										= 16;
	
	/**
	 * notification e-mail message that is sended to a interupted member
	 * at the start of his interuption
	 */
	const INTERRUPTED_MEMBERSHIP_BEGIN_NOTIFY_MESSAGE					= 17;
	
	/**
	 * notification e-mail message that is sended to a interupted member
	 * at the end of his interuption
	 */
	const INTERRUPTED_MEMBERSHIP_END_NOTIFY_MESSAGE						= 18;
	
	/**
	 * content of page for information about former membership
	 * e-mail content of this message is send to the former member at the day
	 * when the end membership begins
	 */
	const FORMER_MEMBER_MESSAGE											= 19;

	/**
	 * content of page for big debtors, this redirection can be set in system
	 */
	const BIG_DEBTOR_MESSAGE											= 20;
	
	// self-cancel constants
	
	/**
	 * self cancel disabled, remote computer cannot cancel this message
	 */
	const SELF_CANCEL_DISABLED											= 0;
	
	/**
	 * self cancel enabled, every member's IP address will have cancelled
	 * given redirection
	 */
	const SELF_CANCEL_MEMBER											= 1;
	
	/**
	 * self cancel enabled, redirection is canceled only for current remote computer
	 */
	const SELF_CANCEL_IP												= 2;
	
	/**
	 * Message's types
	 * 
	 * @author Michal Kliment
     * @var type 
	 */
	private static $types = array
	(
		self::USER_MESSAGE									=> 'User message',
		self::CONTACT_INFORMATION							=> 'Contact information',
		self::CANCEL_MESSAGE								=> 'Page after canceling redirection',
		self::UNKNOWN_DEVICE_MESSAGE						=> 'Unknown device',
		self::INTERRUPTED_MEMBERSHIP_MESSAGE				=> 'Interrupted membership message',
		self::DEBTOR_MESSAGE								=> 'Debtor message',
		self::BIG_DEBTOR_MESSAGE							=> 'Big debtor message',
		self::PAYMENT_NOTICE_MESSAGE						=> 'Payment notice',
		self::UNALLOWED_CONNECTING_PLACE_MESSAGE			=> 'Unallowed connecting place',
		self::RECEIVED_PAYMENT_NOTICE_MESSAGE				=> 'Received payment notice',
		self::APPLICANT_APPROVE_MEMBERSHIP					=> 'Application for membership approved',
		self::APPLICANT_REFUSE_MEMBERSHIP					=> 'Application for membership rejected',
		self::CONNECTION_REQUEST_APPROVE					=> 'Request for connection approved',
		self::CONNECTION_REQUEST_REFUSE						=> 'Request for connection rejected',
		self::CONNECTION_REQUEST_INFO						=> 'Notice of adding connection request',
		self::MONITORING_HOST_DOWN							=> 'Monitoring host down',
		self::MONITORING_HOST_UP							=> 'Monitoring host up',
		self::CONNECTION_TEST_EXPIRED						=> 'Test connection has expired',
		self::INTERRUPTED_MEMBERSHIP_BEGIN_NOTIFY_MESSAGE	=> 'Membership interrupt begins notification',
		self::INTERRUPTED_MEMBERSHIP_END_NOTIFY_MESSAGE		=> 'Membership interrupt ends notification',
		self::FORMER_MEMBER_MESSAGE							=> 'Former member message',
	);
	
	/**
	 * Self cancel messages
	 *
	 * @author Ondřej Fibich
	 * @var array
	 */
	private static $self_cancel_messages = array
	(
		self::SELF_CANCEL_DISABLED	=> 'Disabled',
		self::SELF_CANCEL_MEMBER	=> 'Possibility of canceling redirection to all IP addresses of member',
		self::SELF_CANCEL_IP		=> 'Possibility of canceling redirection to only current IP address'
	);
	
	/**
	 * Returns all message's types
	 * 
	 * @author Michal Kliment
	 * @return type
	 */
	public static function get_types()
	{
		return array_map('__', self::$types);
	}


	/**
	 * Gets set of self cancel messages
	 * 
	 * @author Ondřej Fibich
	 * @param bool $translate	Translate messages
	 * @return array
	 */
	public static function get_self_cancel_messages($translate = TRUE)
	{
		if ($translate)
		{
			return array_map('__', self::$self_cancel_messages);
		}
		
		return self::$self_cancel_messages;
	}
	
	/**
	 * Check if message may be self cancable.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $type	Type of message
	 * @return boolean
	 */
	public static function is_self_cancable($type)
	{
		return (
			$type == self::USER_MESSAGE ||
			$type == self::PAYMENT_NOTICE_MESSAGE ||
			$type == self::RECEIVED_PAYMENT_NOTICE_MESSAGE
		);
	}
	
	/**
	 * Check if message may ignore the white list.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $type	Type of message
	 * @return boolean
	 */
	public static function is_white_list_ignorable($type)
	{
		return (
			$type == self::USER_MESSAGE ||
			$type == self::INTERRUPTED_MEMBERSHIP_MESSAGE ||
			$type == self::DEBTOR_MESSAGE ||
			$type == self::BIG_DEBTOR_MESSAGE ||
			$type == self::PAYMENT_NOTICE_MESSAGE ||
			$type == self::UNALLOWED_CONNECTING_PLACE_MESSAGE ||
			$type == self::INTERRUPTED_MEMBERSHIP_BEGIN_NOTIFY_MESSAGE ||
			$type == self::INTERRUPTED_MEMBERSHIP_END_NOTIFY_MESSAGE ||
			$type == self::FORMER_MEMBER_MESSAGE
		);
	}
	
	
	/**
	 * Check if message is special message.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $type	Type of message
	 * @return boolean
	 */
	public static function is_special_message($type)
	{
		return (
			$type == self::RECEIVED_PAYMENT_NOTICE_MESSAGE ||
			$type == self::APPLICANT_APPROVE_MEMBERSHIP ||
			$type == self::APPLICANT_REFUSE_MEMBERSHIP ||
			$type == self::CONNECTION_REQUEST_APPROVE ||
			$type == self::CONNECTION_REQUEST_REFUSE ||
			$type == self::CONNECTION_REQUEST_INFO || 
			$type == self::MONITORING_HOST_DOWN ||
			$type == self::MONITORING_HOST_UP ||
			$type == self::CONNECTION_TEST_EXPIRED ||
			$type == self::INTERRUPTED_MEMBERSHIP_BEGIN_NOTIFY_MESSAGE ||
			$type == self::INTERRUPTED_MEMBERSHIP_END_NOTIFY_MESSAGE
		);
	}

	/**
	 * Check if message is from finance module.
	 *
	 * @author Ondřej Fibich
	 * @param integer $type	Type of message
	 * @return boolean
	 */
	public static function is_finance_message($type)
	{
		return (
			$type == self::BIG_DEBTOR_MESSAGE ||
			$type == self::DEBTOR_MESSAGE ||
			$type == self::PAYMENT_NOTICE_MESSAGE
		);
	}
	
	/**
	 * Check if message contains redirection content.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $type	Type of message
	 * @return boolean
	 */
	public static function has_redirection_content($type)
	{
		return (
			$type != self::RECEIVED_PAYMENT_NOTICE_MESSAGE &&
			$type != self::APPLICANT_APPROVE_MEMBERSHIP &&
			$type != self::APPLICANT_REFUSE_MEMBERSHIP &&
			$type != self::CONNECTION_REQUEST_APPROVE &&
			$type != self::CONNECTION_REQUEST_REFUSE &&
			$type != self::CONNECTION_REQUEST_INFO &&
			$type != self::MONITORING_HOST_DOWN &&
			$type != self::MONITORING_HOST_UP &&
			$type != self::INTERRUPTED_MEMBERSHIP_BEGIN_NOTIFY_MESSAGE &&
			$type != self::INTERRUPTED_MEMBERSHIP_END_NOTIFY_MESSAGE
		);
	}
	
	/**
	 * Check if message contains email content.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $type	Type of message
	 * @return boolean
	 */
	public static function has_email_content($type)
	{
		return (
			$type == self::USER_MESSAGE || 
			$type == self::DEBTOR_MESSAGE ||
			$type == self::BIG_DEBTOR_MESSAGE ||
			$type == self::PAYMENT_NOTICE_MESSAGE ||
			$type == self::RECEIVED_PAYMENT_NOTICE_MESSAGE ||
			$type == self::APPLICANT_APPROVE_MEMBERSHIP ||
			$type == self::APPLICANT_REFUSE_MEMBERSHIP ||
			$type == self::CONNECTION_REQUEST_APPROVE ||
			$type == self::CONNECTION_REQUEST_REFUSE ||
			$type == self::CONNECTION_REQUEST_APPROVE ||
			$type == self::CONNECTION_REQUEST_INFO ||
			$type == self::MONITORING_HOST_DOWN ||
			$type == self::MONITORING_HOST_UP ||
			$type == self::INTERRUPTED_MEMBERSHIP_BEGIN_NOTIFY_MESSAGE ||
			$type == self::INTERRUPTED_MEMBERSHIP_END_NOTIFY_MESSAGE ||
			$type == self::FORMER_MEMBER_MESSAGE
		);
	}
	
	/**
	 * Check if message contains sms content.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $type	Type of message
	 * @return boolean
	 */
	public static function has_sms_content($type)
	{
		return (
			$type == self::USER_MESSAGE || 
			$type == self::DEBTOR_MESSAGE ||
			$type == self::BIG_DEBTOR_MESSAGE ||
			$type == self::PAYMENT_NOTICE_MESSAGE ||
			$type == self::RECEIVED_PAYMENT_NOTICE_MESSAGE ||
			$type == self::CONNECTION_REQUEST_APPROVE ||
			$type == self::CONNECTION_REQUEST_REFUSE ||
			$type == self::INTERRUPTED_MEMBERSHIP_BEGIN_NOTIFY_MESSAGE ||
			$type == self::INTERRUPTED_MEMBERSHIP_END_NOTIFY_MESSAGE ||
			$type == self::FORMER_MEMBER_MESSAGE
		);
	}
	
	
	/**
	 * Check if message can be activated directly.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $type	Type of message
	 * @return boolean
	 */
	public static function can_be_activate_directly($type)
	{
		return (
			$type != self::CONTACT_INFORMATION &&
			$type != self::CANCEL_MESSAGE &&
			$type != self::UNKNOWN_DEVICE_MESSAGE &&
			$type != self::RECEIVED_PAYMENT_NOTICE_MESSAGE &&
			$type != self::APPLICANT_APPROVE_MEMBERSHIP &&
			$type != self::APPLICANT_REFUSE_MEMBERSHIP &&
			$type != self::CONNECTION_REQUEST_APPROVE &&
			$type != self::CONNECTION_REQUEST_REFUSE &&
			$type != self::CONNECTION_REQUEST_INFO &&
			$type != self::UNALLOWED_CONNECTING_PLACE_MESSAGE &&
			$type != self::MONITORING_HOST_DOWN &&
			$type != self::MONITORING_HOST_UP &&
			$type != self::CONNECTION_TEST_EXPIRED &&
			$type != self::INTERRUPTED_MEMBERSHIP_MESSAGE &&
			$type != self::INTERRUPTED_MEMBERSHIP_BEGIN_NOTIFY_MESSAGE &&
			$type != self::INTERRUPTED_MEMBERSHIP_END_NOTIFY_MESSAGE &&
			$type != self::FORMER_MEMBER_MESSAGE
		);
	}
	
	/**
	 * Check if message can be activated manually.
	 * 
	 * @param integer $type Message type
	 * @return boolean
	 */
	public static function can_be_activate_automatically($type)
	{
		return (
			$type == self::DEBTOR_MESSAGE ||
			$type == self::BIG_DEBTOR_MESSAGE ||
			$type == self::PAYMENT_NOTICE_MESSAGE
		);
	}
	
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
	public function count_all_messages($filter_sql = '')
	{
		return $this->get_all_messages(NULL, NULL, '', '', $filter_sql)
			->count();
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
			$filter_sql = '')
	{
		// order by check
		if (!$this->has_column($order_by))
		{
			if ($order_by == 'message')
			{
				$order_by = 'name';
			}
			else
			{
				$order_by = 'id';
			}
		}
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		$where = array();
		
		if (!Settings::get('finance_enabled'))
		{
			$where[] = "m.type <> " . intval(self::PAYMENT_NOTICE_MESSAGE);
			$where[] = "m.type <> " . intval(self::DEBTOR_MESSAGE);
			$where[] = "m.type <> " . intval(self::BIG_DEBTOR_MESSAGE);
			$where[] = "m.type <> " . intval(self::RECEIVED_PAYMENT_NOTICE_MESSAGE);
		}
		else if (!is_numeric(Settings::get('big_debtor_boundary')))
		{
			$where[] = "m.type <> " . intval(self::BIG_DEBTOR_MESSAGE);
		}
		
		if (!Settings::get('membership_interrupt_enabled'))
		{
			$where[] = "m.type <> " . intval(self::INTERRUPTED_MEMBERSHIP_MESSAGE);
			$where[] = "m.type <> " . intval(self::INTERRUPTED_MEMBERSHIP_BEGIN_NOTIFY_MESSAGE);
			$where[] = "m.type <> " . intval(self::INTERRUPTED_MEMBERSHIP_END_NOTIFY_MESSAGE);
		}
		
		if (!Settings::get('monitoring_enabled'))
		{
			$where[] = "m.type <> " . intval(self::MONITORING_HOST_UP);
			$where[] = "m.type <> " . intval(self::MONITORING_HOST_DOWN);
		}
		
		if (!Settings::get('connection_request_enable'))
		{
			$where[] = "m.type <> " . intval(self::CONNECTION_REQUEST_APPROVE);
			$where[] = "m.type <> " . intval(self::CONNECTION_REQUEST_INFO);
			$where[] = "m.type <> " . intval(self::CONNECTION_REQUEST_REFUSE);
		}
		
		if (!Settings::get('allowed_subnets_enabled'))
		{
			$where[] = "m.type <> " . intval(self::UNALLOWED_CONNECTING_PLACE_MESSAGE);
		}
		
		if (!Settings::get('self_registration'))
		{
			$where[] = "m.type <> " . intval(self::APPLICANT_APPROVE_MEMBERSHIP);
			$where[] = "m.type <> " . intval(self::APPLICANT_REFUSE_MEMBERSHIP);
			$where[] = "m.type <> " . intval(self::CONNECTION_TEST_EXPIRED);
		}
		else if (Settings::get('applicant_connection_test_duration') <= 0)
		{
			$where[] = "m.type <> " . intval(self::CONNECTION_TEST_EXPIRED);
		}
		
		if ($filter_sql != '')
			$where[] = $filter_sql;
		
		$where_sql = (count($where)) ? 'WHERE ' . implode(' AND ', $where) : '';
		
		$limit = '';
		
		if (!is_null($limit_from) && !is_null($limit_results))
		{
			$limit = "LIMIT " . intval($limit_from) . ", " . intval($limit_results);
		}
		
		// query
		return $this->db->query("
			SELECT m.id, m.name AS message, m.type, m.self_cancel,
			m.ignore_whitelist
			FROM messages m
			$where_sql
			ORDER BY $order_by $order_by_direction
			$limit
		");	
	}
	
	/**
	 * Deny ip addresses with expired test connections
	 * 
	 * @see Scheduler_Controller#update_applicant_connection_test
	 * @author Ondrej Fibich
	 * @param integer $user_id	Who redirects
	 */
	public function activate_test_connection_end_message($user_id)
	{
		$mm = new Message_Model();
		
		try
		{
			$mm->transaction_start();
			
			// preparation
			$message = ORM::factory('message')->where(array
			(
				'type' => self::CONNECTION_TEST_EXPIRED
			))->find();

			// message do not exists
			if (!$message || !$message->id)
			{
				throw new Exception('Connection test expired message not founded');
			}

			// deletes old redirections
			Database::instance()->delete('messages_ip_addresses', array
			(
				'message_id' => $message->id
			));

			// find IP addresses with interrupted membership
			$ip_model = new Ip_address_Model();

			$ips = $ip_model->get_ip_addresses_with_expired_connection_test();

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
	 * Activates notice messages (only e-mail and sometimes also SMS) for accept
	 * payment notice, approoving/refusing of application for membership
	 * to a single member.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $type				8 => payment, 9 => application approve, 10 => application refuse
	 * @param integer $member_id		Member ID
	 * @param integer $user_id			Who redirects
	 * @param integer $email			E-mail state
	 * @param integer $sms				SMS state
	 * @param string $comment			Comment for member
	 * @return boolean					Notification made?
	 */
	public static function activate_special_notice(
			$type, $member_id, $user_id, $email, $sms, $comment = NULL)
	{
		if (!self::is_special_message($type))
		{
			throw new Exception('Wrong type');
		}
		
		// preparation
		$message = ORM::factory('message')->where(array('type' => $type))->find();
		
		$uc_model = new Users_contacts_Model();
		
		// message do not exists
		if (!$message || !$message->id)
		{
			Log::add('error', 'Notice message (' . $type . ') not exists in messages table');
			return false;
		}
		
		// member notification settings only enabled for self cancel messages 
		$ignore_mnotif_settings = !$message->self_cancel;
		
		// send emails
		if (Settings::get('email_enabled') &&
			$email == Notifications_Controller::ACTIVATE)
		{
			// find email addresses of debtors
			$emails = $uc_model->get_contacts_by_member_and_type(
					$member_id, Contact_Model::TYPE_EMAIL,
					$message->ignore_whitelist, FALSE, FALSE,
					$ignore_mnotif_settings
			);
			// send emails for finded emails
			self::send_emails($message, $emails, $comment);
		}
		
		// send SMS messages
		if (Settings::get('sms_enabled') &&
			$sms == Notifications_Controller::ACTIVATE)
		{
			// find phone numbers of debtors
			$smss = $uc_model->get_contacts_by_member_and_type(
					$member_id, Contact_Model::TYPE_PHONE,
					$message->ignore_whitelist, FALSE, FALSE,
					$ignore_mnotif_settings
			);
			// send SMS messages for finded phone numbers
			self::send_sms_messages($message, $smss, $user_id, $comment);
		}
		
		// return stats array
		return true;
	}

	/**
	 * Deactivates all redirections of given message.
	 * 
	 * @author Jiri Svitak
	 * @param integer $message_id
	 * @return integer Count of deactivated IPs
	 */
	public function deactivate_message($message_id)
	{
		$count = $this->db->query("
			SELECT COUNT(*) AS count
			FROM messages_ip_addresses
			WHERE message_id = ?
		", $message_id)->current()->count;
		
		$this->db->query("
			DELETE FROM messages_ip_addresses
			WHERE message_id = ?
		", $message_id);
		
		return $count;
	}

	/**
	 * Returns message by given type
	 * 
	 * @param integer $type
	 * @return Message_Model 
	 */
	public function get_message_by_type($type)
	{
		if (!$type)
		{
			return NULL;
		}
		
		$message = ORM::factory('message')->where('type', $type)->find();
		
		if ($message->id)
		{
			return $message;
		}
		else
		{
			return NULL;
		}
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
		
		// empty message
		if (trim($message->text) == '')
		{
			return 0;
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
		if (!Settings::get('email_default_email'))
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
				$subject = '';
				if (Settings::get('notification_email_message_name_in_subject'))
				{
					$subject = __($message->name);
				}
				$email_subject_prefix = Settings::get('email_subject_prefix');
				if ($email_subject_prefix)
				{
					if ($subject)
					{
						$subject = $email_subject_prefix . ':' . $subject;
					}
					else
					{
						$subject = $email_subject_prefix;
					}
				}

				// add Email to queue				
				$eq_model->clear();
				$eq_model->from = Settings::get('email_default_email');
				$eq_model->to = $contact->value;
				$eq_model->subject = $subject;
				$eq_model->body = $text;
				$eq_model->state = Email_queue_Model::STATE_NEW;
				$eq_model->save_throwable();
				
				// add E-mail to counter
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
	 * Generic function to send e-mail
	 * 
	 * @author Michal Kliment <kliment@freenetis.org>
	 * @param Message_Model $message Message which will be send
	 * @param string $email E-mail address where send e-mail
	 * @param mixed $object Generic object to autofill message variables
	 * @param boolean $push Whether message will be push on top of queue
	 * @throws Exception
	 */
	public static function send_email (Message_Model $message, $email, $object = NULL, $push = FALSE)
	{
		if (!Settings::get('email_enabled'))
		{
			throw new Exception('E-mail is not enabled');
		}
		
		// param check
		if (!$message->id)
		{
			throw new Exception('Wrong args');
		}
		
		// if default email ans subject prefix is set
		if (!Settings::get('email_default_email'))
		{
			throw new Exception('Email not configured properly');
		}
		
		$subject = $message->name;
		$text = $message->email_text;
		
		foreach ($object as $key => $value)
		{
			$subject	= str_replace('{'.$key.'}', $value, $subject);
			$text		= str_replace('{'.$key.'}', $value, $text);
		}
		
		try
		{
			$email_queue_model = new Email_queue_Model();
			
			$email_queue_model->transaction_start();
			
			// push e-mail on top of queue?
			if ($push)
			{
				$email_queue_model->push(
						Settings::get('email_default_email'),
						$email, $subject, $text
				);
			}
			else
			{
				$email_queue_model->from = Settings::get('email_default_email');
				$email_queue_model->to = $email;
				$email_queue_model->subject = $subject;
				$email_queue_model->body = $text;
				$email_queue_model->state = Email_queue_Model::STATE_NEW;
				$email_queue_model->save_throwable();
			}
			
			$email_queue_model->transaction_commit();
		}
		catch (Exception $e)
		{
			$email_queue_model->transaction_rollback();
			Log::add_exception($e);
			throw $e;
		}
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
		
		// check if enabled
		if (!Settings::get('sms_enabled'))
		{
			throw new Exception('SMS are not enabled');
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
