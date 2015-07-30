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
 * Connecttion request.
 * 
 * @author Ondřej Fibich
 * @package Model
 * 
 * @property integer $id
 * @property integer $member_id
 * @property Member_Model $member
 * @property integer $added_user_id
 * @property User_Model $added_user
 * @property integer $decided_user_id
 * @property User_Model $decided_user
 * @property integer $state
 * @property datetime $created_at
 * @property datetime $decided_at
 * @property string $ip_address
 * @property integer $subnet_id
 * @property Subnet_Model $subnet
 * @property string $mac_address
 * @property integer $device_id
 * @property Device_Model $device
 * @property integer $device_type_id
 * @property Enum_type_Model $device_type
 * @property integer $device_template_id
 * @property Device_template_Model $device_template
 * @property string $comment
 * @property integer $comments_thread_id
 * @property Comments_thread_Model $comments_thread
 */
class Connection_request_Model extends ORM
{
	// states of request
	
	/** Undecided state (default state) */
	const STATE_UNDECIDED	= 0;
	/** Denied state - request was not approved */
	const STATE_REJECTED		= 1;
	/** Approved state - request was approved */
	const STATE_APPROVED		= 2;
	
	/**
	 * State messages
	 *
	 * @var array
	 */
	private static $state_messages = array
	(
		self::STATE_UNDECIDED	=> 'Undecided',
		self::STATE_REJECTED		=> 'Rejected',
		self::STATE_APPROVED		=> 'Approved'
	);
	
	// database relations
	
	protected $belongs_to = array
	(
		'member', 'device', 'device_template',
		'comments_thread', 'subnet', 'device_type' => 'enum_type',
		'added_user' => 'user', 'decided_user' => 'user'
	);
	
	// functions

	/**
	 * Gets states messages
	 * 
	 * @param boolean $translate Translate messages?
	 * @return array
	 */
	public static function get_state_messages($translate = TRUE)
	{
		if ($translate)
		{
			return array_map('__', self::$state_messages);
		}
		
		return self::$state_messages;
	}
	
	/**
	 * Counts filtered connection requests
	 * 
	 * @param string $filter_sql SQL filter
	 * @return integer Count
	 */
	public function count_all_connection_requests($filter_sql = '')
	{
		$where = '';
		
		if (!empty($filter_sql))
		{
			$where = 'WHERE ' . $filter_sql;
		}
		else
		{
			return $this->count_all();
		}
		
		return $this->db->query("
			SELECT COUNT(*) AS total FROM (
				SELECT cr.id, cr.mac_address, cr.ip_address, cr.comment, cr.state,
					cr.created_at, cr.member_id, m.name AS member_name,
					u.id AS user_id, CONCAT(u.name, ' ', u.surname) AS user_name,
					s.id AS subnet_id, s.name AS subnet_name,
					IFNULL(t.translated_term, e.value) AS device_type
				FROM connection_requests cr
				JOIN members m ON m.id = cr.member_id
				JOIN subnets s ON s.id = cr.subnet_id
				LEFT JOIN users u ON u.id = cr.added_user_id
				LEFT JOIN enum_types e ON cr.device_type_id = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = ?
			) c
			$where
		", Config::get('lang'))->current()->total;
	}
	
	/**
	 * Counts connection requests of members
	 * 
	 * @param integer $member_id
	 * @return integer Count
	 */
	public function count_all_connection_requests_of_member($member_id)
	{
		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM connection_requests cr
			WHERE cr.member_id = ?
		", $member_id)->current()->total;
	}
	
	/**
	 * Gets filtered connection requests
	 * 
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql SQL filter
	 * @return Mysql_Result
	 */
	public function get_all_connection_requests(
			$limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'asc', $filter_sql = '')
	{
		$where = '';
		
		if (!empty($filter_sql))
		{
			$where = 'WHERE ' . $filter_sql;
		}
		
		if (strtolower($order_by_direction) != 'asc')
		{
			$order_by_direction = 'desc';
		}
		
		return $this->db->query("
			SELECT * FROM (
				SELECT cr.id, cr.mac_address, cr.ip_address, cr.comment, cr.state,
					cr.created_at, cr.member_id, m.name AS member_name,
					u.id AS user_id, CONCAT(u.name, ' ', u.surname) AS user_name,
					s.id AS subnet_id, s.name AS subnet_name,
					IFNULL(t.translated_term, e.value) AS device_type,
					co.a_comment, cr.comments_thread_id AS a_comments_thread_id,
					1 AS a_comment_add
				FROM connection_requests cr
				JOIN members m ON m.id = cr.member_id
				JOIN subnets s ON s.id = cr.subnet_id
				LEFT JOIN users u ON u.id = cr.added_user_id
				LEFT JOIN enum_types e ON cr.device_type_id = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = ?
				LEFT JOIN
				(
					SELECT comments_thread_id,
						GROUP_CONCAT(
							CONCAT(
								u.surname,' ',u.name,
								' (',SUBSTRING(c.datetime,1,10),'):\n',c.text)
							ORDER BY c.datetime DESC
							SEPARATOR '\n\n'
						) AS a_comment
					FROM comments c
					JOIN users u ON c.user_id = u.id
					GROUP BY comments_thread_id
				) co ON co.comments_thread_id = cr.comments_thread_id
			) c
			$where
			ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
			LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", Config::get('lang'));
	}
	
	/**
	 * Gets connection requests of member
	 * 
	 * @param integer $member_id
	 * @return Mysql_Result
	 */
	public function get_all_connection_requests_of_member($member_id)
	{
		return $this->db->query("
			SELECT cr.id, cr.mac_address, cr.ip_address, cr.comment, cr.state,
				cr.created_at, cr.member_id, m.name AS member_name,
				u.id AS user_id, CONCAT(u.name, ' ', u.surname) AS user_name,
				s.id AS subnet_id, s.name AS subnet_name,
				IFNULL(t.translated_term, e.value) AS device_type,
				co.a_comment
			FROM connection_requests cr
			JOIN members m ON m.id = cr.member_id
			JOIN subnets s ON s.id = cr.subnet_id
			LEFT JOIN users u ON u.id = cr.added_user_id
			LEFT JOIN enum_types e ON cr.device_type_id = e.id
			LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = ?
			LEFT JOIN
			(
				SELECT comments_thread_id,
					GROUP_CONCAT(
						CONCAT(
							u.surname,' ',u.name,
							' (',SUBSTRING(c.datetime,1,10),'):\n',c.text)
						ORDER BY c.datetime DESC
						SEPARATOR '\n\n'
					) AS a_comment
				FROM comments c
				JOIN users u ON c.user_id = u.id
				GROUP BY comments_thread_id
			) co ON co.comments_thread_id = cr.comments_thread_id
			WHERE cr.member_id = ?
			ORDER BY cr.created_at DESC
		", Config::get('lang'), $member_id);
	}
	
	/**
	 * Gets an undecided connection that contains given IP
	 * 
	 * @param string $ip_address
	 * @return ORM_Iterator
	 */
	public function get_undecided_connection_with_ip($ip_address)
	{
		return $this->where(array(
			'ip_address'	=> $ip_address,
			'state'			=> self::STATE_UNDECIDED
		))->find_all();
	}
	
}
