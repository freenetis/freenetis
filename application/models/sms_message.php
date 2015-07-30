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
 * SMS message
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $user_id
 * @property User_Model $user
 * @property integer $sms_message_id
 * @property Sms_message_Model $sms_message
 * @property datetime $stamp
 * @property datetime $send_date
 * @property string $text
 * @property string $sender
 * @property string $receiver
 * @property integer $driver
 * @property integer $type
 * @property integer $state
 * @property string $message
 */
class Sms_message_Model extends ORM
{
	/** Type of SMS: recieved */
	const RECEIVED = 0;
	/** Type of SMS: sent */
	const SENT = 1;

	/** State of recieved SMS: SMS was not readed yet */
	const RECEIVED_UNREAD = 0;
	/** State of recieved SMS: SMS was readed */
	const RECEIVED_READ = 1;
	
	/** State of sended SMS: SMS was sended */
	const SENT_OK = 0;
	/** State of sended SMS: SMS was not sended yet */
	const SENT_UNSENT = 1;
	/** State of sended SMS: SMS sent failed */
	const SENT_FAILED = 2;
	
	protected $belongs_to = array('user', 'sms_message_id' => 'sms_message');
	
	/**
	 * Gets all SMS messages
	 *
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_all_records(
			$limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'desc', $filter_values = array())
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// filter
		$where = '';
		if (!empty($filter_values))
		{
			$where = ' WHERE ' . $filter_values;
		}
		// query
		return $this->db->query("
				SELECT
					sms.*, IFNULL(s.sender_id,0) AS sender_id, sender_name, sender_type,
					r.*
				FROM sms_messages sms
				LEFT JOIN
				(
					SELECT
						CONCAT(cu.country_code,c.value) AS value,
						u.id AS sender_id,
						CONCAT(u.surname,' ',u.name) AS sender_name,
						IFNULL(e.value, m.type) AS sender_type
						FROM contacts c
						JOIN contacts_countries cc ON cc.contact_id = c.id
						JOIN countries cu ON cc.country_id = cu.id
						JOIN users_contacts uc ON uc.contact_id = c.id
						JOIN users u ON uc.user_id = u.id
						JOIN members m ON u.member_id = m.id
						LEFT JOIN enum_types e ON m.type = e.id AND read_only = 0
					WHERE c.type = ?
				) s ON sms.sender LIKE s.value
				LEFT JOIN
				(
					SELECT
						CONCAT(cu.country_code,c.value) AS value,
						u.id AS receiver_id,
						CONCAT(u.surname,' ',u.name) AS receiver_name,
						IFNULL(e.value, m.type) AS receiver_type
						FROM contacts c
						JOIN contacts_countries cc ON cc.contact_id = c.id
						JOIN countries cu ON cc.country_id = cu.id
						JOIN users_contacts uc ON uc.contact_id = c.id
						JOIN users u ON uc.user_id = u.id
						JOIN members m ON u.member_id = m.id
						LEFT JOIN enum_types e ON m.type = e.id AND read_only = 0
					WHERE c.type = ?
				) r ON sms.receiver LIKE r.value
				$where
				GROUP BY sms.id
				ORDER BY $order_by $order_by_direction
				LIMIT ".intval($limit_from).", ".intval($limit_results)."
		", array(Contact_Model::TYPE_PHONE, Contact_Model::TYPE_PHONE));
	}

	/**
	 * Gets all unread SMS messages
	 *
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_unread_messages(
			$limit_from = 0, $limit_results = 50,
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
				SELECT *
				FROM sms_messages
				WHERE type = ? AND state = ?
				ORDER BY $order_by $order_by_direction
				LIMIT ".intval($limit_from).", ".intval($limit_results)."
		", array
		(
			Sms_message_Model::RECEIVED,
			Sms_message_Model::RECEIVED_UNREAD
		));
	}
	
	/**
	 * Gets count of all unread messages
	 *
	 * @return integer
	 */
	public function count_of_unread_messages()
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM sms_messages
				WHERE type = ? AND state = ?
		", array
		(
			Sms_message_Model::RECEIVED,
			Sms_message_Model::RECEIVED_UNREAD
		))->current()->count;
	}
	
	/**
	 * Count the number of records in the table.
	 *
	 * @param string $filter_sql
	 * @return  integer
	 */
	public function count_all_messages($filter_sql = '')
	{
		// filter
		$where = '';
		if (!empty($filter_sql))
		{
			$where = 'WHERE ' . $filter_sql;
		}
		// Return the total number of records in a table
		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM sms_messages sms
			$where
		")->current()->total;
	}

}
