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
 * Member model. 
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $user_id
 * @property boolean $registration
 * @property string $name
 * @property integer $address_point_id
 * @property integer $type
 * @property integer $external_type
 * @property string $vat_organization_identifier
 * @property string $organization_identifier
 * @property double $entrance_fee
 * @property double $debt_payment_rate
 * @property date $entrance_date
 * @property double $entrance_fee_left
 * @property date $entrance_fee_date
 * @property date $date
 * @property date $entrance_form_received
 * @property date $entrance_form_accepted
 * @property date $leaving_date
 * @property datetime $applicant_registration_datetime
 * @property date $applicant_connected_from
 * @property integer $locked
 * @property integer $voip_billing_limit
 * @property integer $voip_billing_type
 * @property string $comment
 * @property User_Model $user
 * @property Address_point_Model $address_point
 * @property Allowed_subnets_count_Model $allowed_subnets_count
 * @property Members_traffic_Model $members_traffic
 * @property Members_domicile_Model $members_domicile
 * @property integer $speed_class_id
 * @property Speed_class_Model $speed_class
 * @property ORM_Iterator $allowed_subnets
 * @property ORM_Iterator $invoices
 * @property ORM_Iterator $users
 * @property ORM_Iterator $accounts
 * @property ORM_Iterator $transfers
 * @property ORM_Iterator $bank_accounts
 * @property ORM_Iterator $membership_interrupts
 * @property ORM_Iterator $connection_requests
 * @property ORM_Iterator $members_whitelists
 * @property boolean $notification_by_redirection
 * @property boolean $notification_by_email
 * @property boolean $notification_by_sms
 */
class Member_Model extends ORM
{
	/** Type of member: applicant */
	const TYPE_APPLICANT = 1;
	/** Type of member: regular */
	const TYPE_REGULAR = 2;
	/** Type of member: honorary */
	const TYPE_HONORARY = 3;
	/** Type of member: sympatizing */
	const TYPE_SYMPATHIZING = 4;
	/** Type of member: non */
	const TYPE_NON = 5;
	/** Type of member: fee-free */
	const TYPE_FEE_FREE = 6;
	/** Type of member: former */
	const TYPE_FORMER = 15;
	
	/** Association member ID */
	const ASSOCIATION = 1;
	
	/**
	 * Types of member
	 * 
	 * @var array
	 */
	private static $types = array
	(
		self::TYPE_APPLICANT	=> 'Applicant',
		self::TYPE_REGULAR		=> 'Regular member',
		self::TYPE_HONORARY		=> 'Honorary member',
		self::TYPE_SYMPATHIZING	=> 'Sympathizing member',
		self::TYPE_NON			=> 'Non-member',
		self::TYPE_FEE_FREE		=> 'Fee-free regular member',
		self::TYPE_FORMER		=> 'Former member'
	);
	
	protected $has_one = array
	(
		'allowed_subnets_count', 'members_traffic', 'members_domicile'
	);
	
	protected $has_many = array
	(
		'allowed_subnets', 'invoices', 'users', 'accounts',
		'transfers', 'bank_accounts', 'membership_interrupts',
		'connection_requests', 'members_whitelists'
	);
	
	protected $belongs_to = array('address_point', 'user', 'speed_class');
	
	/**
	 * Returns type in string from integer
	 * 
	 * @param integer|string $type
	 * @return string 
	 */
	public static function get_type ($type)
	{
		if (isset(self::$types[$type]))
			return __(self::$types[$type]);
		else
			return $type;
	}
	
	/**
	 * Gets joined values of member for members fees
	 *
	 * @param integer $member_id
	 * @return Member_Model
	 */
	public function get_member_joined($member_id = NULL)
	{
		if (empty($member_id))
		{
			$member_id = $this->id;
		}
		
		return $this->select(
					'members.id, members.name as member_name, users.name as name,' .
					'users.surname as surname, members.entrance_date,' .
					'members.leaving_date'
				)->join('users', array
				(
					'users.member_id' => 'members.id',
					'users.type' => 1
				))->where('members.id', $member_id)
				->find();
	}
	
	/**
	 * Gets joined values of members for members fees
	 *
	 * @param integer $member_id
	 * @return ORM_Iterator
	 */
	public function get_members_joined($member_id = NULL)
	{
		if (empty($member_id))
		{
			$member_id = $this->id;
		}
		
		return $this->select(
					'members.id, members.name as member_name, users.name as name,' .
					'users.surname as surname, members.entrance_date, ' .
					'members.leaving_date'
				)->join('users', array
				(
					'users.member_id' => 'members.id',
					'users.type' => 1
				))->orderby('surname')
				->find_all();
	}
	
	/**
	 * Returns IP addresses of the most traffic-active members
	 *
	 * @param string $day
	 * @see Web_interface_Controller#active_traffic_members_ip_addresses
	 * @author Michal Kliment
	 * @return Mysql_Result
	 */
	public function get_active_traffic_members_ip_addresses($day)
	{
		return $this->db->query("
			SELECT DISTINCT ip.ip_address
			FROM members_traffics_daily mt
			JOIN users u ON u.member_id = mt.member_id
			JOIN devices d ON d.user_id = u.id
			LEFT JOIN ifaces i ON i.device_id = d.id
			LEFT JOIN ip_addresses ip ON ip.iface_id = i.id
			WHERE mt.active = 1 AND mt.date = ? AND ip.ip_address IS NOT NULL
		", $day);
	}

	/**
	 * Function gets list of all members from database.
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @param $limit_from starting row
	 * @param $limit_results number of rows
	 * @param $order_by sorting column
	 * @param $order_by_direction sorting direction
	 * @param $filter_values used for filtering
	 * @return Mysql_Result
	 */
	public function get_all_members($limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'asc', $filter_sql = '')
	{
		$where = '';

		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		$join_cloud = '';
		$select_cloud = '';
		
		//HACK FOR IMPROVING PERFORMANCE
		if (strpos($filter_sql, '`cl`.`cloud` LIKE '))
		{
			$join_cloud = "
					LEFT JOIN
					(
						SELECT c.id AS cloud, IFNULL(u.member_id, c.member_id) AS member_id
						FROM
						(
							SELECT c.*, i.device_id, ip.member_id
							FROM clouds c
							JOIN clouds_subnets cs ON cs.cloud_id = c.id
							JOIN subnets s ON cs.subnet_id = s.id
							JOIN ip_addresses ip ON ip.subnet_id = s.id
							JOIN ifaces i ON ip.iface_id = i.id
						) c
						LEFT JOIN devices d ON c.device_id = d.id
						LEFT JOIN users u ON d.user_id = u.id
					) cl ON cl.member_id = m.id";
			$select_cloud = ', cloud';
		}

		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT id, id AS member_id, name AS member_name, registration, registrations,
					name, street, street_number, town, quarter, variable_symbol, aid, balance,
					a_comment, a_comments_thread_id, type, type_name, entrance_date, leaving_date,
					redirect_type_id, GROUP_CONCAT(DISTINCT redirect_type SEPARATOR ', ') AS redirect,
					GROUP_CONCAT(DISTINCT redirect_type_text SEPARATOR ', \n') AS redirect_text,
					notification_by_redirection, notification_by_email, notification_by_sms,
					whitelisted, interrupt, 1 AS redirection, 1 AS email, 1 AS sms $select_cloud
				FROM
				(
					SELECT
						m.id, m.registration, m.registrations, m.name,
						s.street, ap.street_number, t.town, t.quarter,
						vs.variable_symbol, a.id AS aid,
						a.balance, a_comment,
						a.comments_thread_id AS a_comments_thread_id,
						m.type, IFNULL(f.translated_term, e.value) AS type_name,
                        m.entrance_date, m.leaving_date, redirect_type,
						redirect_type_id, redirect_type_text, whitelisted,
						m.notification_by_redirection, m.notification_by_email,
						m.notification_by_sms, interrupt $select_cloud
					FROM 
					(
						SELECT m.id,
							m.name,
							m.address_point_id,
							IF(m.registration = 1, ?, ?) AS registration,
							m.registration AS registrations,
							m.type,
							IF(mi.id IS NOT NULL, 1, 0) AS membership_interrupt,
							IF(mi.id IS NOT NULL, 1, 0) AS interrupt,
							m.organization_identifier, m.vat_organization_identifier,
							m.comment, m.entrance_date, m.leaving_date,
							m.entrance_fee, m.speed_class_id, m.notification_by_redirection,
							m.notification_by_email, m.notification_by_sms,
							m.user_id AS added_by_user_id
						FROM members m
						LEFT JOIN enum_types e ON m.type = e.id
						LEFT JOIN translations t ON e.value = t.original_term AND lang = ?
						LEFT JOIN
						(
							SELECT mi.id, mi.member_id
							FROM membership_interrupts mi
							LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id
							WHERE  mf.activation_date <= CURDATE() AND mf.deactivation_date >= CURDATE()
						) mi ON mi.member_id = m.id
					) AS m
					LEFT JOIN users u ON u.member_id = m.id
					LEFT JOIN users ua ON ua.id = m.added_by_user_id
					LEFT JOIN address_points ap ON m.address_point_id = ap.id
					LEFT JOIN streets s ON ap.street_id = s.id
					LEFT JOIN towns t ON ap.town_id = t.id
					LEFT JOIN accounts a ON a.member_id = m.id AND m.id <> 1
					LEFT JOIN variable_symbols vs ON vs.account_id = a.id
					LEFT JOIN
					(
						SELECT
						comments_thread_id,
						GROUP_CONCAT(
							CONCAT(
								u.surname,' ',u.name,
								' (',SUBSTRING(c.datetime,1,10),'):\n',c.text)
							ORDER BY c.datetime DESC
							SEPARATOR '\n\n') AS a_comment
						FROM comments c
						JOIN users u ON c.user_id = u.id
						GROUP BY comments_thread_id
					) c ON c.comments_thread_id = a.comments_thread_id
					LEFT JOIN enum_types e ON m.type = e.id
					LEFT JOIN translations f ON e.value = f.original_term AND lang = ?
					LEFT JOIN
					(
						SELECT DISTINCT
							ms.type AS redirect_type_id,
							IF(ms.type = 4, ?,IF(ms.type = 5, ?, IF(ms.type = 6, ?, IF(ms.type = 7, ?, IF(ms.type = 16, ?, IF(ms.type = 19, ?, ?)))))) AS redirect_type,
							IF(ms.type = 4, ?,IF(ms.type = 5, ?, IF(ms.type = 6, ?, IF(ms.type = 7, ?, IF(ms.type = 16, ?, IF(ms.type = 19, ?, ?)))))) AS redirect_type_text,
							IFNULL(u.member_id,ms.member_id) AS member_id
						FROM
						(
							SELECT ms.*, i.device_id, ip.member_id
							FROM messages ms
							LEFT JOIN messages_ip_addresses mip ON mip.message_id = ms.id
							LEFT JOIN ip_addresses ip ON mip.ip_address_id = ip.id
							LEFT JOIN ifaces i ON ip.iface_id = i.id
						) ms
						LEFT JOIN devices d ON ms.device_id = d.id
						LEFT JOIN users u ON d.user_id = u.id
					) ms ON ms.member_id = m.id
					LEFT JOIN
					(
						SELECT m2.id AS member_id, IF(mw.member_id IS NULL, 0, 2 - mw.permanent) AS whitelisted
						FROM members m2
						LEFT JOIN members_whitelists mw ON mw.member_id = m2.id
							AND mw.since <= CURDATE() AND mw.until >= CURDATE()
					) ip ON ip.member_id = m.id
					$join_cloud
					$where
				) AS q
				GROUP BY q.id
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", array
		(
			__('Yes'),
			__('No'),
			Config::get('lang'),
			Config::get('lang'),
			// redir shortcuts
			__('IM'),
			__('DB'),
			__('PN'),
			__('UCP'),
			__('CE'),
			__('FM'),
			__('UM'),
			// redir texts
			__('Membership interrupt'),
			__('Debtor'),
			__('Payment notice'),
			__('Unallowed connecting place'),
			__('Connection test expired'),
			__('Former member'),
			__('User message')
		));
	}
	
	/**
	 * Function gets list of registered applicans.
	 * 
	 * @param $limit_from starting row
	 * @param $limit_results number of rows
	 * @param $order_by sorting column
	 * @param $order_by_direction sorting direction
	 * @param string $filter_sql used for filtering
	 * @return Mysql_Result
	 */
	public function get_registered_applicants($limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'asc', $filter_sql = '')
	{
		$where = '';

		if ($filter_sql != '')
			$where = "WHERE $filter_sql";

		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		// query
		return $this->db->query("
				SELECT id, id AS member_id, registration, name, street, street_number,
					town, quarter, variable_symbol, aid, balance, applicant_registration_datetime,
					applicant_connected_from, GROUP_CONCAT(a_comment SEPARATOR ', \n\n') AS a_comment,
					comment, a_comments_thread_id, type, entrance_date, leaving_date, 0 AS toapprove
				FROM
				(
					SELECT * FROM
					(
						SELECT
							m.id, m.registration, m.name, m.applicant_connected_from,
							s.street, ap.street_number, t.town, t.quarter,
							vs.variable_symbol, a.id AS aid,
							a.balance, m.applicant_registration_datetime,
							CONCAT(u.surname,' ',u.name,' (',SUBSTRING(c.datetime,1,10),'):\n',c.text) AS a_comment,
							a.comments_thread_id AS a_comments_thread_id,
							m.type, m.entrance_date, m.leaving_date, m.comment
						FROM members m
						LEFT JOIN address_points ap ON m.address_point_id = ap.id
						LEFT JOIN streets s ON ap.street_id = s.id
						LEFT JOIN towns t ON ap.town_id = t.id
						LEFT JOIN accounts a ON a.member_id = m.id AND m.id <> 1
						LEFT JOIN variable_symbols vs ON vs.account_id = a.id
						LEFT JOIN comments_threads ct ON a.comments_thread_id = ct.id
						LEFT JOIN comments c ON ct.id = c.comments_thread_id
						LEFT JOIN users u ON c.user_id = u.id
						WHERE m.type = ?
						ORDER BY c.datetime DESC
					) m
					$where
				) AS q
				GROUP BY id
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", self::TYPE_APPLICANT);
	}

	/**
	 * Function counts all members.
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @param string $filter_values
	 * @return integer
	 */
	public function count_all_members($filter_sql = "")
	{
		// optimalization
		if (!empty($filter_sql))
		{
			$where = "WHERE $filter_sql";
		}
		else
		{
			return $this->count_all();
		}
		
		$join_cloud = '';
		
		//HACK FOR IMPROVING PERFORMANCE
		if (strpos($filter_sql, '`cl`.`cloud` LIKE '))
		{
			$join_cloud = "
					LEFT JOIN
					(
						SELECT c.id AS cloud, IFNULL(u.member_id, c.member_id) AS member_id
						FROM
						(
							SELECT c.*, i.device_id, ip.member_id
							FROM clouds c
							JOIN clouds_subnets cs ON cs.cloud_id = c.id
							JOIN subnets s ON cs.subnet_id = s.id
							JOIN ip_addresses ip ON ip.subnet_id = s.id
							JOIN ifaces i ON ip.iface_id = i.id
						) c
						LEFT JOIN devices d ON c.device_id = d.id
						LEFT JOIN users u ON d.user_id = u.id
					) cl ON cl.member_id = m.id";
		}

		return $this->db->query("
				SELECT COUNT(*) AS total FROM
				(
					SELECT id AS total
					FROM
					(
						SELECT
							m.id
						FROM 
						(
							SELECT m.id,
								m.name,
								m.address_point_id,
								IF(m.registration = 1, ?, ?) AS registration,
								m.type,
								IF(mi.id IS NOT NULL, 1, 0) AS membership_interrupt,
								m.organization_identifier, m.vat_organization_identifier,
								m.comment, m.entrance_date, m.leaving_date,
								m.entrance_fee, m.speed_class_id,
								m.user_id AS added_by_user_id
							FROM members m
							LEFT JOIN enum_types e ON m.type = e.id
							LEFT JOIN translations t ON e.value = t.original_term AND lang = ?
							LEFT JOIN
							(
								SELECT mi.id, mi.member_id
								FROM membership_interrupts mi
								LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id
								WHERE  mf.activation_date <= CURDATE() AND mf.deactivation_date >= CURDATE()
							) mi ON mi.member_id = m.id
						) AS m
						LEFT JOIN users u ON u.member_id = m.id
						LEFT JOIN users ua ON ua.id = m.added_by_user_id
						LEFT JOIN address_points ap ON m.address_point_id = ap.id
						LEFT JOIN streets s ON ap.street_id = s.id
						LEFT JOIN towns t ON ap.town_id = t.id
						LEFT JOIN accounts a ON a.member_id = m.id AND m.id <> 1
						LEFT JOIN variable_symbols vs ON vs.account_id = a.id 
						LEFT JOIN comments_threads ct ON a.comments_thread_id = ct.id
						LEFT JOIN comments c ON ct.id = c.comments_thread_id
						LEFT JOIN users cu ON c.user_id = cu.id
						LEFT JOIN enum_types e ON m.type = e.id
						LEFT JOIN translations f ON e.value = f.original_term AND lang = ?
						LEFT JOIN
						(
							SELECT DISTINCT
								ms.type AS redirect_type_id,
								IF(ms.type = 4, ? ,IF(ms.type = 5, ?, IF(ms.type = 6, ?, ?))) AS redirect_type,
								IF(ms.type = 4, ?,IF(ms.type = 5, ?, IF(ms.type = 6, ?, ?))) AS redirect_type_text,
								IFNULL(u.member_id, ms.member_id) AS member_id
							FROM
							(
								SELECT ms.*, i.device_id, ip.member_id
								FROM messages ms
								LEFT JOIN messages_ip_addresses mip ON mip.message_id = ms.id
								LEFT JOIN ip_addresses ip ON mip.ip_address_id = ip.id
								LEFT JOIN ifaces i ON ip.iface_id = i.id
							) ms
							LEFT JOIN devices d ON ms.device_id = d.id
							LEFT JOIN users u ON d.user_id = u.id
						) ms ON ms.member_id = m.id
						LEFT JOIN
						(
							SELECT m2.id AS member_id, IF(mw.member_id IS NULL, 0, 2 - mw.permanent) AS whitelisted
							FROM members m2
							LEFT JOIN members_whitelists mw ON mw.member_id = m2.id
								AND mw.since <= CURDATE() AND mw.until >= CURDATE()
						) ip ON ip.member_id = m.id
						$join_cloud
						$where
						ORDER BY c.datetime DESC
					) AS q
					GROUP BY id
				) q2
		", array
		(
			__('Yes'),
			__('No'),
			Config::get('lang'),
			Config::get('lang'),
			__('IM'),
			__('DB'),
			__('PN'),
			__('UM'),
			__('Membership interrupt'),
			__('Debtor'),
			__('Payment notice'),
			__('User message')
		))->current()->total;
	}
	
	/**
	 * Function gets count of registered applicans.
	 * 
	 * @param string $filter_sql used for filtering
	 * @return integer
	 */
	public function count_all_registered_applicants($filter_sql = '')
	{
		$where = '';

		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		// query
		return $this->db->query("
				SELECT COUNT(id) AS total
				FROM
				(
					SELECT id
					FROM
					(
						SELECT
							m.id, m.registration, m.name, m.applicant_connected_from,
							s.street, ap.street_number, t.town, t.quarter,
							vs.variable_symbol, a.id AS aid,
							a.balance, m.applicant_registration_datetime,
							CONCAT(u.surname,' ',u.name,' (',SUBSTRING(c.datetime,1,10),'):\n',c.text) AS a_comment,
							a.comments_thread_id AS a_comments_thread_id,
							m.type, m.entrance_date, m.leaving_date, m.comment
						FROM members m
						LEFT JOIN address_points ap ON m.address_point_id = ap.id
						LEFT JOIN streets s ON ap.street_id = s.id
						LEFT JOIN towns t ON ap.town_id = t.id
						LEFT JOIN accounts a ON a.member_id = m.id AND m.id <> 1
						LEFT JOIN variable_symbols vs ON vs.account_id = a.id
						LEFT JOIN comments_threads ct ON a.comments_thread_id = ct.id
						LEFT JOIN comments c ON ct.id = c.comments_thread_id
						LEFT JOIN users u ON c.user_id = u.id
						WHERE m.type = ?
						ORDER BY c.datetime DESC
					) AS m
					$where
				) q2
		", self::TYPE_APPLICANT)->current()->total;
	}
	
	/**
	 * Function gets all members to export.
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @param string $filter_values
	 * 
	 * @author Jiri Svitak, Ondřej Fibich
	 * @return Mysql_Result
	 */
	public function get_all_members_to_export($filter_sql = '')
	{
		// where condition
		if (!empty($filter_sql))
		{
			$filter_sql = "WHERE $filter_sql";
		}
		// query
		return $this->db->query("
				SELECT id, member_name, variable_symbol, street, street_number,
					town, quarter, login, birthday, entrance_date, leaving_date,
					type, comment, registration, membership_interrupt,
					redirect_type_id
				FROM
				(
					SELECT id, name AS member_name, registration,
						street, street_number, town, quarter,
						GROUP_CONCAT(DISTINCT variable_symbol) AS variable_symbol,
						type, entrance_date,
						IF(leaving_date = '0000-00-00', null, leaving_date) AS leaving_date,
						birthday, login, comment, membership_interrupt,
						redirect_type_id
					FROM
					(
						SELECT
								m.id, m.registration, m.name,
								s.street, ap.street_number, t.town, t.quarter,
								vs.variable_symbol,	m.type, m.entrance_date,
								m.leaving_date,
								u.birthday, u.login, m.comment, m.membership_interrupt,
								ms.redirect_type_id
						FROM 
						(
							SELECT m.id,
								m.name,
								m.address_point_id,
								registration,
								type, entrance_fee,
								speed_class_id,
								IF(mi.id IS NOT NULL, 1, 0) AS membership_interrupt,
								m.comment,	m.entrance_date, m.leaving_date,
								organization_identifier, vat_organization_identifier
							FROM members m
							LEFT JOIN
							(
								SELECT mi.id, mi.member_id
								FROM membership_interrupts mi
								LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id
								WHERE mf.activation_date <= CURDATE() AND mf.deactivation_date >= CURDATE()
							) mi ON mi.member_id = m.id
						) AS m
						LEFT JOIN address_points ap ON m.address_point_id = ap.id
						LEFT JOIN streets s ON ap.street_id = s.id
						LEFT JOIN towns t ON ap.town_id = t.id
						LEFT JOIN accounts a ON a.member_id = m.id AND m.id <> 1
						LEFT JOIN variable_symbols vs ON vs.account_id = a.id
						LEFT JOIN users u ON u.member_id = m.id AND u.type = ?
						LEFT JOIN enum_types e ON m.type = e.id
						LEFT JOIN devices d ON d.user_id = u.id
						LEFT JOIN ifaces i ON i.device_id = d.id
						LEFT JOIN ip_addresses ip ON ip.iface_id = i.id
						LEFT JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
						LEFT JOIN
						(
							SELECT m.*, type AS redirect_type_id
							FROM messages m
						) ms ON mip.message_id = ms.id
						LEFT JOIN
						(
							SELECT c.id AS cloud,
							IFNULL(u.member_id, c.member_id) AS member_id
							FROM
							(
								SELECT c.*, i.device_id, ip.member_id
								FROM clouds c
								JOIN clouds_subnets cs ON cs.cloud_id = c.id
								JOIN subnets s ON cs.subnet_id = s.id
								JOIN ip_addresses ip ON ip.subnet_id = s.id
								JOIN ifaces i ON ip.iface_id = i.id
							) c
							LEFT JOIN devices d ON c.device_id = d.id
							LEFT JOIN users u ON d.user_id = u.id
							GROUP BY c.id, IFNULL(u.member_id, c.member_id)
						) cl ON cl.member_id = m.id
						LEFT JOIN
						(
							SELECT m2.id AS mw_member_id, IF(mw.member_id IS NULL, 0, 2 - mw.permanent) AS whitelisted
							FROM members m2
							LEFT JOIN members_whitelists mw ON mw.member_id = m2.id
								AND mw.since <= CURDATE() AND mw.until >= CURDATE()
						) ip ON ip.mw_member_id = m.id
						$filter_sql
					) AS q
					GROUP BY q.id
					ORDER BY q.id
				) AS q
		", array
		(
			User_Model::MAIN_USER
		));
	}
	
	/**
	 * Function gets selected members.
	 * 
	 * @param array $ids
	 * @param boolean $in_set
	 * 
	 * @author Jan Dubina
	 * @return Mysql_Result
	 */
	public function get_members_to_sync_vtiger($ids, $in_set)
	{
		$filter_sql = '';
		// where condition
		if (!empty($ids) || $in_set === false)
		{
			if (!empty($ids))
				if ($in_set === true)
					$filter_sql = "WHERE m.id IN (" . implode(',', $ids) . ")";
				else
					$filter_sql = "WHERE m.id NOT IN (" . implode(',', $ids) . ")";
				
			// query
			return $this->db->query("
				SELECT m.id, name, type, organization_identifier, vat_organization_identifier,
						entrance_date, comment, country_name, town, street,
						street_number, zip_code, email, phone,
						variable_symbol, employees
					FROM members m
					LEFT JOIN
					(
						SELECT ap.id, country_name, town, zip_code,
							street, street_number
							FROM address_points ap
							LEFT JOIN countries c
								ON c.id = ap.country_id
							LEFT JOIN towns t
								ON t.id = ap.town_id
							LEFT JOIN streets s
								ON s.id = ap.street_id
					) ap ON ap.id = m.address_point_id
					LEFT JOIN
					(
						SELECT member_id, GROUP_CONCAT(email SEPARATOR ';') AS email
						FROM users u 
						RIGHT JOIN 
						(
							SELECT user_id, value AS email
							FROM users_contacts uc 
							LEFT JOIN contacts c ON uc.contact_id = c.id
							WHERE c.type = ?
						) c ON u.id = c.user_id 
						WHERE type = ?
						GROUP BY member_id
					) ce ON ce.member_id = m.id
					LEFT JOIN
					(
						SELECT member_id, GROUP_CONCAT(phone SEPARATOR ';') AS phone
						FROM users u 
						RIGHT JOIN 
						(
							SELECT user_id, value AS phone
							FROM users_contacts uc 
							LEFT JOIN contacts c ON uc.contact_id = c.id
							WHERE c.type = ?
						) c ON u.id = c.user_id 
						WHERE type = ?
						GROUP BY member_id
					) cp ON cp.member_id = m.id
					LEFT JOIN
					(
						SELECT member_id, COUNT(id) as employees
							FROM users
							GROUP BY member_id
					) em ON em.member_id = m.id
					LEFT JOIN
					(
						SELECT member_id, variable_symbol
						FROM accounts a
						LEFT JOIN variable_symbols vs
						ON vs.account_id = a.id
						GROUP BY member_id 
					) v ON v.member_id = m.id
					$filter_sql
			", array(
						Contact_Model::TYPE_EMAIL,
						User_Model::MAIN_USER,
						Contact_Model::TYPE_PHONE,
						User_Model::MAIN_USER
			));
		}
	}
	
	/**
	 * Returns all members sccording to the type of message.
	 * This method does not handle whitelists. (members with witelists are
	 * returned anyway and they must be filtered after).
	 * 
	 * THIS IS ONE OF THE MOST IMPORTANT SQL QUERY IN THE WHOLE SYSTEM
	 * PLEASE BE VERY CAREFUL WITH EDITING OF IT.
	 * 
	 * @author Michal Kliment, Ondrej Fibich
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @return MySQL Result 
	 */
	public function get_members_to_messages($type)
	{
		switch ($type)
		{
			case Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE:
				$where = "AND mi.id IS NOT NULL";
				$order_by = 'whitelisted ASC, interrupt DESC';
				break;

			case Message_Model::BIG_DEBTOR_MESSAGE:
				// without interrupted members, former members, applicants
				$where = "AND mi.id IS NULL "
						. " AND m.type <> " . intval(Member_Model::TYPE_APPLICANT)
						. " AND m.type <> " . Member_Model::TYPE_FORMER
						. " AND a.balance < ".intval(Settings::get('big_debtor_boundary'))
						." AND DATEDIFF(CURDATE(), m.entrance_date) >= ".intval(Settings::get('initial_debtor_immunity'));
				$order_by = "whitelisted ASC, balance ASC, m.name ASC";
				break;
			
			case Message_Model::DEBTOR_MESSAGE:
				// without interrupted members, former members, applicants, big debtors (if enabled)
				$where = "AND mi.id IS NULL " 
						. " AND m.type <> " . intval(Member_Model::TYPE_APPLICANT)
						. " AND m.type <> " . Member_Model::TYPE_FORMER;
				if (is_numeric(Settings::get('big_debtor_boundary')))
				{
					$where .= " AND a.balance >= ".intval(Settings::get('big_debtor_boundary'));
				}
				$where .= " AND a.balance < ".intval(Settings::get('debtor_boundary'))
						." AND DATEDIFF(CURDATE(), m.entrance_date) >= ".intval(Settings::get('initial_debtor_immunity'));
				$order_by = "whitelisted ASC, balance ASC, m.name ASC";
				break;
			
			case Message_Model::PAYMENT_NOTICE_MESSAGE:
				// without interrupted members, former members, applicants, debtors
				$where = "AND mi.id IS NULL " 
						. " AND m.type <> " . intval(Member_Model::TYPE_APPLICANT)
						. " AND m.type <> " . Member_Model::TYPE_FORMER
						. " AND a.balance < ".intval(Settings::get('payment_notice_boundary'))."
							AND
							(
								DATEDIFF(CURDATE(), m.entrance_date) >= ".intval(Settings::get('initial_debtor_immunity'))."
								AND a.balance >= ".intval(Settings::get('debtor_boundary'))."
								OR DATEDIFF(CURDATE(), m.entrance_date) < ".intval(Settings::get('initial_debtor_immunity'))."
								AND DATEDIFF(CURDATE(), m.entrance_date) >= ".intval(Settings::get('initial_immunity'))."
							)";
				$order_by = "whitelisted ASC, balance ASC, m.id ASC";
				break;
			
			case Message_Model::USER_MESSAGE:
				// no former or interrupted members for user message
				$where = "AND mi.id IS NULL "
						. " AND m.type <> " . Member_Model::TYPE_FORMER;
				$order_by = 'm.id';
				break;
			
			default:
				throw new InvalidArgumentException('Unexceptable mesage type: ' . $type);
		}
		
		return $this->db->query("
			SELECT
				m.*, m.id AS member_id, m.name AS member_name, 
				a.id AS aid, a.balance, a.comments_thread_id AS a_comments_thread_id,
				a_comment, w.whitelisted,
				IFNULL(mi.id, 0) AS interrupt,
				1 AS redirection, 1 AS email, 1 AS sms
			FROM members m
			JOIN accounts a ON a.member_id = m.id AND account_attribute_id = ?
			LEFT JOIN
			(
				SELECT
				comments_thread_id,
				GROUP_CONCAT(
					CONCAT(
						u.surname,' ',u.name,
						' (',SUBSTRING(c.datetime,1,10),'):\n',c.text)
					ORDER BY c.datetime DESC
					SEPARATOR '\n\n') AS a_comment
				FROM comments c
				JOIN users u ON c.user_id = u.id
				GROUP BY comments_thread_id
			) c ON c.comments_thread_id = a.comments_thread_id
			LEFT JOIN
			(
				SELECT mi.id, mi.member_id
				FROM membership_interrupts mi
				JOIN members_fees mf ON mi.members_fee_id = mf.id
					AND mf.activation_date <= CURDATE()
					AND mf.deactivation_date >= CURDATE()
			) mi ON mi.member_id = m.id
			LEFT JOIN
			(
				SELECT m2.id AS member_id, IF(mw.member_id IS NULL, 0, 2 - mw.permanent) AS whitelisted
				FROM members m2
				LEFT JOIN members_whitelists mw ON mw.member_id = m2.id
					AND mw.since <= CURDATE() AND mw.until >= CURDATE()
			) w ON w.member_id = m.id
			WHERE m.id <> ? $where
			GROUP BY m.id
			ORDER BY $order_by
		", Account_attribute_Model::CREDIT, Member_Model::ASSOCIATION);
	}

	/**
	 * Function gets all members who have at least one ip address in given subnet.
	 * 
	 * @author Jiri Svitak
	 * @param integer $subnet_id
	 * @param string $order_by
	 * @return Mysql_Result
	 */
	public function get_members_of_subnet($subnet_id, $order_by = 'id')
	{
		return $this->db->query("
			SELECT
				m.*, m.id AS member_id, m.name AS member_name, 
				a.id AS aid, a.balance, a.comments_thread_id AS a_comments_thread_id,
				a_comment, als.enabled AS allowed, w.whitelisted,
				IF(mf.id IS NOT NULL, 1,0) AS interrupt,
				1 AS redirection, 1 AS email, 1 AS sms
			FROM
			(
				SELECT IFNULL(ip.member_id,u.member_id) AS member_id
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON ip.iface_id = i.id
				LEFT JOIN devices d ON i.device_id = d.id
				LEFT JOIN users u ON d.user_id = u.id
				WHERE ip.subnet_id = ?
			) ip
			JOIN members m ON ip.member_id = m.id
			JOIN accounts a ON a.member_id = m.id AND account_attribute_id = ?
			LEFT JOIN
					(
						SELECT
						comments_thread_id,
						GROUP_CONCAT(
							CONCAT(
								u.surname,' ',u.name,
								' (',SUBSTRING(c.datetime,1,10),'):\n',c.text)
							ORDER BY c.datetime DESC
							SEPARATOR '\n\n') AS a_comment
						FROM comments c
						JOIN users u ON c.user_id = u.id
						GROUP BY comments_thread_id
					) c ON c.comments_thread_id = a.comments_thread_id
			LEFT JOIN allowed_subnets als ON als.member_id = m.id AND als.subnet_id = ?
			LEFT JOIN membership_interrupts mip ON mip.member_id = m.id
			LEFT JOIN members_fees mf ON mip.members_fee_id = mf.id
				AND mf.activation_date <= CURDATE()
				AND mf.deactivation_date >= CURDATE()
			LEFT JOIN
			(
				SELECT m2.id AS member_id, IF(mw.member_id IS NULL, 0, 2 - mw.permanent) AS whitelisted
				FROM members m2
				LEFT JOIN members_whitelists mw ON mw.member_id = m2.id
					AND mw.since <= CURDATE() AND mw.until >= CURDATE()
			) w ON w.member_id = m.id
			GROUP BY m.id
			ORDER BY $order_by
		", $subnet_id, Account_attribute_Model::CREDIT, $subnet_id);
	}
	
	/**
	 * Gets all members  of cloud
	 * 
	 * @param integer $cloud_id
	 * @param string $order_by
	 * @return Mysql_Result
	 */
	public function get_members_of_cloud($cloud_id, $order_by = 'id')
	{
		return $this->db->query("
			SELECT
				m.*, m.id AS member_id, m.name AS member_name, 
				a.id AS aid, a.balance, a.comments_thread_id AS a_comments_thread_id,
				a_comment, als.enabled AS allowed, w.whitelisted,
				IF(mf.id IS NOT NULL, 1,0) AS interrupt,
				1 AS redirection, 1 AS email, 1 AS sms
			FROM
			(
				SELECT IFNULL(ip.member_id,u.member_id) AS member_id
				FROM ip_addresses ip
				JOIN subnets s ON ip.subnet_id = s.id
				JOIN clouds_subnets cs ON cs.subnet_id = s.id AND cs.cloud_id = ?
				LEFT JOIN ifaces i ON ip.iface_id = i.id
				LEFT JOIN devices d ON i.device_id = d.id
				LEFT JOIN users u ON d.user_id = u.id
			) ip
			JOIN members m ON ip.member_id = m.id
			JOIN accounts a ON a.member_id = m.id AND account_attribute_id = ?
			LEFT JOIN
					(
						SELECT
						comments_thread_id,
						GROUP_CONCAT(
							CONCAT(
								u.surname,' ',u.name,
								' (',SUBSTRING(c.datetime,1,10),'):\n',c.text)
							ORDER BY c.datetime DESC
							SEPARATOR '\n\n') AS a_comment
						FROM comments c
						JOIN users u ON c.user_id = u.id
						GROUP BY comments_thread_id
					) c ON c.comments_thread_id = a.comments_thread_id
			LEFT JOIN allowed_subnets als ON als.member_id = m.id AND als.subnet_id = ?
			LEFT JOIN membership_interrupts mip ON mip.member_id = m.id
			LEFT JOIN members_fees mf ON mip.members_fee_id = mf.id
				AND mf.activation_date <= CURDATE()
				AND mf.deactivation_date >= CURDATE()
			LEFT JOIN
			(
				SELECT m2.id AS member_id, IF(mw.member_id IS NULL, 0, 2 - mw.permanent) AS whitelisted
				FROM members m2
				LEFT JOIN members_whitelists mw ON mw.member_id = m2.id
					AND mw.since <= CURDATE() AND mw.until >= CURDATE()
			) w ON w.member_id = m.id
			GROUP BY m.id
			ORDER BY $order_by
		", $cloud_id, Account_attribute_Model::CREDIT, $cloud_id);
	}
	
	/**
	 * This function can be used for checking the validity of payment variable symbols
	 * and finding the related member.
	 * If the variable symbol was generated from member id using crc16 function,
	 * then it is easy to check if the variable symbol is OK and extract the member id from it.
	 * 
	 * @author Tomas Dulik
	 * @param $vs - string containing variable symbol (concatenation of member id and its crc16)
	 * @return object containing the member
	 */
	public function get_member_by_crc_id($vs)
	{
		if (($vs_len = strlen($vs)) > 5)
		{
			$member_id = (int) substr($vs, 0, $vs_len - 5);
			$crc = variable_symbol::crc16($member_id);
			$vs_crc = (int) substr($vs, $vs_len - 5, 5);
			
			if ($crc == $vs_crc)
				$member = $this->find($member_id);
		}
		return $this;
	}

	/**
	 * This function is used in the Accounts_controller - e.g. in the function
	 * "store_transfer_ebanka" for finding the member who made a bank transaction where variable symbol = his phone number
	 * Similar function with different purpose can be found in get_member_by_phone
	 * 
	 * @param string $phone string containing a phone number
	 * @return Mysql_Result first member_id of a member with given phone number
	 */
	public function find_member_id_by_phone($phone)
	{
		$result = $this->db->query("
			SELECT m.id FROM members m
			JOIN users ON m.id = users.member_id
			JOIN users_contacts ON users.id = users_contacts.user_id
			JOIN contacts c ON users_contacts.contact_id = c.id
			WHERE c.type = ? AND c.value = ?
		", array(Contact_Model::TYPE_PHONE, $phone));
		
		return ($result && $result->count()) ? $result->current()->id : false;
	}

	/**
	 * Find member by phone
	 * 
	 * @author Tomas Dulik, Ondřej Fibich
	 * @return Mysql_Result
	 */
	public function find_member_by_phone($phone)
	{
		$result = $this->db->query("
			SELECT m.* FROM members m
			JOIN users ON m.id = users.member_id
			JOIN users_contacts ON users.id = users_contacts.user_id
			JOIN contacts c ON users_contacts.contact_id = c.id
			WHERE c.type = ? AND c.value = ?
		", array(Contact_Model::TYPE_PHONE, $phone));
		
		return ($result && $result->count()) ? $result->current() : FALSE;
	}

	/**
	 * Returns count of all non-former members without membership interrupt in that time and without set-up qos rate
	 *
	 * @author Michal Kliment
	 * @return integer
	 */
	public function count_all_members_to_ulogd()
	{
		$result = $this->db->query("
				SELECT COUNT(m.id) AS total
				FROM members m
				WHERE m.type <> ? AND m.speed_class_id IS NULL AND
					m.id NOT IN
					(
						SELECT mi.member_id
						FROM membership_interrupts mi
						JOIN members_fees mf ON mi.members_fee_id = mf.id
						WHERE mf.activation_date <= CURDATE() AND CURDATE() <= mf.deactivation_date
					)
		", self::TYPE_FORMER);

		return ($result && $result->current()) ? $result->current()->total : 0;
	}

	/**
	 * Returns all members to dropdown
	 *
	 * @todo I think this format is better than default select list
	 * @author Michal Kliment
	 * @return Mysql_Result object
	 */
	public function get_all_members_to_dropdown ()
	{
		return $this->db->query("
				SELECT m.id,
				CONCAT(IF(
					CONCAT(u.name,' ',u.surname) = m.name,
					CONCAT(u.surname,' ',u.name),
					m.name
				), ' (ID ',m.id,')') AS name
				FROM members m
				JOIN users u ON u.member_id = m.id AND u.type = ?
				ORDER BY name
		", User_Model::MAIN_USER);
	}
	
	/**
	 * Gets array of members for selectbox
	 * 
	 * @return array[string]
	 */
	public function select_list_grouped($optgroup = TRUE)
	{
		$list = array();
		
		$assoc = ORM::factory('member', self::ASSOCIATION);
		
		if ($optgroup)
			$list[__('Association')][self::ASSOCIATION] = $assoc->name . ' (ID '.self::ASSOCIATION.')';
		else
			$list[self::ASSOCIATION] = $assoc->name . ' (ID '.self::ASSOCIATION.')';
		
		$concat = "CONCAT(
					IF(
						CONCAT(users.name,' ',users.surname) = members.name,
						CONCAT(users.surname,' ',users.name),
						members.name
					), ' (ID ',members.id,')')";
		
		if ($optgroup)
		{
			$list[__('Members')] = $assoc
					->join('users', array('members.id' => 'users.member_id'))
					->where('members.id !=', self::ASSOCIATION)
					->select_list('member_id', $concat, array('surname' => 'ASC', 'users.name' => 'ASC'));
		}
		else
		{
			$list += $assoc
				->join('users', array('members.id' => 'users.member_id'))
				->where('members.id !=', self::ASSOCIATION)
				->select_list('member_id', $concat, array('surname' => 'ASC', 'users.name' => 'ASC'));
		}
		
		return $list;
	}

	/**
	 * Returns doubleentry account of member by given account attribute id
	 *
	 * @author Michal Kliment
	 * @param integer $account_attribute_id
	 * @return Mysql_Result object
	 */
	public function get_doubleentry_account ($account_attribute_id)
	{
		if ($this->id)
		{
			return $this->db->query("
					SELECT * FROM accounts a
					WHERE account_attribute_id = ? AND member_id = ?
			", array($account_attribute_id, $this->id))->current();
		}
		
		return false;
	}

	/**
	 * Returns all members as array
	 *
	 * @author Michal Kliment, Ondřej Fibich
	 * @return array
	 */
	public function get_all_as_array()
	{
		return $this->select_list('id', 'name');
	}

	/**
	 * Returns login of member
	 *
	 * @author Michal Kliment
	 * @return string
	 */
	public function get_login()
	{
		if ($this->id)
		{
			return $this->db->query("
					SELECT u.login
					FROM users u
					WHERE u.member_id = ? AND u.type = ?
			", array($this->id, User_Model::MAIN_USER))->current()->login;
		}
		
		return false;
	}

	/**
	 * Returns ID of main user of member
	 *
	 * @author Ondřej Fibich
	 * @return integer|null
	 */
	public function get_main_user($member_id = NULL)
	{
		if ($member_id === NULL && $this)
		{
			$member_id = $this->id;
		}
		
		$result = $this->db->query("
				SELECT u.id
				FROM users u
				WHERE u.member_id = ? AND u.type = ?
		", array($member_id, User_Model::MAIN_USER));
		
		if ($result->count())
		{
			return $result->current()->id;
		}
		
		return NULL;
	}
	
	/**
	 * Gets all entrance and leaving dates
	 *
	 * @param string $filter_sql
	 * @return Mysql_Result
	 */
	public function get_all_entrance_and_leaving_dates ($filter_sql = '')
	{
		$where = '';
		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		return $this->db->query("
			SELECT date AS date, SUM(increase) AS increase, SUM(decrease) AS decrease
			FROM
			(
				SELECT SUBSTR(date,1,7) AS date, SUM(increase) AS increase, SUM(decrease) AS decrease
				FROM
				(
						SELECT entrance_date AS date, COUNT(entrance_date) AS increase, 0 AS decrease
						FROM members m
						GROUP BY entrance_date
					UNION
						SELECT leaving_date AS date, 0 AS increase, COUNT(leaving_date) AS decrease
						FROM members m
						WHERE m.leaving_date IS NOT NULL AND m.leaving_date <> '' AND m.leaving_date <> '0000-00-00'
						GROUP BY leaving_date
				) m
				$where
				GROUP BY date
			) m
			GROUP BY date
			ORDER BY date
		");
	}
	
	/**
	 * Deletes members accounts
	 *
	 * @param integer $member_id 
	 */
	public function delete_accounts($member_id)
	{
		$this->db->query("
				DELETE FROM accounts WHERE member_id = ?
		", $member_id);
	}
	
	/**
	 * Deletes all devices and IP addresses of the given member or members
	 * 
	 * @author Ondrej Fibich
	 * @param integer|array $member_id	Member ID or multiple IDs
	 */
	public function delete_members_devices($member_id)
	{
		if (!is_array($member_id))
		{
			$member_id = array($member_id);
		}
		
		if (count($member_id) > 0)
		{
			$mids = implode(',', array_map('intval', $member_id));
			// delete devices
			$this->db->query("
				DELETE FROM devices WHERE user_id IN (
					SELECT id FROM users
					WHERE member_id IN (" . $mids . ")
				)
			");
			// delete IP addresses
			$this->db->query("
				DELETE FROM ip_addresses
				WHERE member_id IN (" . $mids . ")
			");
			// delete subnet owner
			$this->db->query("
				DELETE FROM subnets_owners
				WHERE member_id IN (" . $mids . ");
			");
		}
	}
	
	/**
	 * Returns balance of current member
	 * 
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @return integer 
	 */
	public function get_balance ($member_id = NULL)
	{
		if (!$member_id || !is_numeric($member_id))
			$member_id = $this->id;
		
		$account = ORM::factory('account')->where(array
		(
			'account_attribute_id' => Account_attribute_Model::CREDIT,
			'member_id' => $member_id
		))->find();
		
		if ($account && $account->id)
			return $account->balance;
		else
			return 0;
	}
	
	/**
	 * Checks whether current member has membership interrupt in given date
	 * 
	 * @author Michal Kliment
	 * @param string $date
	 * @param integer $member_id
	 * @return bool 
	 */
	public function has_membership_interrupt ($date = NULL, $member_id = NULL)
	{
		if (!$date)
			$date = date('Y-m-d');
		
		if (!$member_id || !is_numeric($member_id))
			$member_id = $this->id;
		
		return ORM::factory('membership_interrupt')
			->has_member_interrupt_in_date($member_id, $date);
	}

	/**
	 * Has member activates a whitelist?
	 * 
	 * @param integer $member_id [optional]
	 */
	public function has_whitelist($member_id = NULL)
	{
		if ($member_id === NULL)
			$member_id = $this->id;
		
		$mw = new Members_whitelist_Model();
		return $mw->is_whitelisted_now($member_id);
	}
	
	/**
	 * Reactivates (rechecks) system messages for current member
	 * 
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @return bool 
	 */
	public function reactivate_messages ($member_id = NULL)
	{
		if ($member_id && is_numeric($member_id) && $member_id != 1)
			$member = new Member_Model ($member_id);
		else if ($this->id && $this->id != 1)
			$member = $this;
		else
			return false;
		
		// balance of member
		$balance = $member->get_balance();
		
		// has membership interrupt in current date ?
		$has_membership_interrupt = $member->has_membership_interrupt();
		
		// has member any active whitelist?
		$is_whitelisted = $member->has_whitelist();
		
		// finds all ip addresses of member
		$ip_addresses = ORM::factory('ip_address')
				->get_ip_addresses_of_member($member->id);
		
		$messages_ip_addresses_model = new Messages_ip_addresses_Model();
		$message_model = new Message_Model();
		
		// finds ids for system messages
		
		if (Settings::get('finance_enabled'))
		{
			$big_debtor_message_id = $message_model
				->get_message_id_by_type(
						Message_Model::BIG_DEBTOR_MESSAGE
				);

			$debtor_message_id = $message_model
				->get_message_id_by_type(
						Message_Model::DEBTOR_MESSAGE
				);
		
			$payment_notice_message_id = $message_model
				->get_message_id_by_type(
						Message_Model::PAYMENT_NOTICE_MESSAGE
				);
		}
		
		if (Settings::get('membership_interrupt_enabled'))
		{
			$interrupt_membership_message_id = $message_model
				->get_message_id_by_type(
						Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE
				);
		}
		
		$connection_test_expired_id = $message_model
				->get_message_id_by_type(
						Message_Model::CONNECTION_TEST_EXPIRED
				);
		
		$former_member_message_id = $message_model
				->get_message_id_by_type(
						Message_Model::FORMER_MEMBER_MESSAGE
				);
		
		// deletes all redirections of member
		foreach ($ip_addresses as $ip_address)
		{
			// deletes all system redirections of member
			$messages_ip_addresses_model
				->delete_all_system_redirections_of_ip_address($ip_address->id);
			
			// former member?
			if ($member->type == Member_Model::TYPE_FORMER)
			{
				$messages_ip_addresses_model
					->add_redirection_to_ip_address(
							$former_member_message_id, $ip_address->id, ''
					);
				
				continue; // nothing more required
			}
			
			// member has membership interrupt
			if (Settings::get('membership_interrupt_enabled') &&
				$has_membership_interrupt)
			{
				$messages_ip_addresses_model
					->add_redirection_to_ip_address(
							$interrupt_membership_message_id, $ip_address->id, ''
					);
				
				continue; // nothing more required
			}
			
			// only delete all if whitelisted or member is applicant
			if ($is_whitelisted)
				continue;
			
			// applicant test connection
			if ($member->type == Member_Model::TYPE_APPLICANT)
			{
				$actd = Settings::get('applicant_connection_test_duration');
				$acf = $member->applicant_connected_from;
				
				if (!$member->registration && $actd &&
					!empty($acf) && $acf != '0000-00-00' &&
					((time() - strtotime($acf)) / 86400 > $actd))
				{
					$messages_ip_addresses_model
						->add_redirection_to_ip_address(
								$connection_test_expired_id, $ip_address->id, ''
						);
				}
				
				continue; // no other messages for applicants
			}
			
			// finance redirections
			if (Settings::get('finance_enabled') && !$has_membership_interrupt)
			{
				$idi = Settings::get('initial_debtor_immunity');
				$ii = Settings::get('initial_immunity');
				$ed_diff = (time() - strtotime($member->entrance_date)) / 86400;

				if (is_numeric(Settings::get('big_debtor_boundary')) &&
					$balance < Settings::get('big_debtor_boundary') &&
					($ed_diff >= $idi))
				{ // member is big debtor
					$messages_ip_addresses_model
						->add_redirection_to_ip_address(
								$big_debtor_message_id, $ip_address->id, ''
						);
				}
				else if ($balance < Settings::get('debtor_boundary') &&
					($ed_diff >= $idi))
				{ // member is debtor
					$messages_ip_addresses_model
						->add_redirection_to_ip_address(
								$debtor_message_id, $ip_address->id, ''
						);
				}
				else if ($balance < Settings::get('payment_notice_boundary') && (
							$balance >= Settings::get('debtor_boundary') &&
							($ed_diff >= $idi)
					) || (($ed_diff < $idi) && ($ed_diff >= $ii)))
				{ // member is almost debtor
					$messages_ip_addresses_model
						->add_redirection_to_ip_address(
								$payment_notice_message_id, $ip_address->id, ''
						);
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Updates state of members registrations
	 * 
	 * @author Michal Kliment
	 * @param array $ids
	 * @param array $registrations 
	 */
	public function update_member_registrations ($ids = array(), $registrations = array())
	{
		foreach ($ids as $id)
		{
			$this->db->query("
				UPDATE members SET registration = ? WHERE id = ?
			",array(isset($registrations[$id]), $id));
		}
	}
	
	/**
	 * Adds today former members (members that have set leaving date that is less
	 * or equal than today and their type is not former).
	 * 
	 * @author Ondrej Fibich
	 */
	public function add_today_former_members()
	{
		$this->db->query("
			UPDATE members SET type = ? , locked = 1
			WHERE type <> ? AND leaving_date <= ? AND leaving_date IS NOT NULL
				AND leaving_date <> '0000-00-00'
		", self::TYPE_FORMER, self::TYPE_FORMER, date('Y-m-d'));
	}
	
	/**
	 * Returns members with set-up qos ceil or rate
	 * 
	 * @author Michal Kliment
	 * @return MySQL Result
	 */
	public function get_members_qos_ceil_rate ()
	{
		return $this->db->query("
			SELECT m.id, sc.d_ceil, sc.d_rate, sc.u_ceil, sc.u_rate
			FROM members m
			JOIN speed_classes sc ON sc.id = m.speed_class_id
			ORDER BY m.id
		");
	}
	
	/**
	 * Returns today former members (for auto notification)
	 * 
	 * @author Ondrej Fibich
	 * @return Database_Result
	 */
	public function get_today_former_members()
	{
		return $this->db->query("
			SELECT m.id AS member_id, w.whitelisted
			FROM members m
			LEFT JOIN
			(
				SELECT m2.id AS member_id, IF(mw.member_id IS NULL, 0, 2 - mw.permanent) AS whitelisted
				FROM members m2
				LEFT JOIN members_whitelists mw ON mw.member_id = m2.id
					AND mw.since <= CURDATE() AND mw.until >= CURDATE()
			) w ON w.member_id = m.id
			WHERE m.leaving_date = ?
			GROUP BY m.id
		", date('Y-m-d'));
	}
	
	/**
	 * Returns all former members (for auto redirection)
	 * 
	 * @author Ondrej Fibich
	 * @return Database_Result
	 */
	public function get_all_former_members()
	{
		return $this->db->query("
			SELECT m.id AS member_id, w.whitelisted
			FROM members m
			LEFT JOIN
			(
				SELECT m2.id AS member_id, IF(mw.member_id IS NULL, 0, 2 - mw.permanent) AS whitelisted
				FROM members m2
				LEFT JOIN members_whitelists mw ON mw.member_id = m2.id
					AND mw.since <= CURDATE() AND mw.until >= CURDATE()
			) w ON w.member_id = m.id
			WHERE m.type = ?
			GROUP BY m.id
		", self::TYPE_FORMER);
	}
	
	/**
	 * Returns all interrupted members (for auto redirection)
	 * 
	 * @author Ondrej Fibich
	 * @param string $date Date in format Y-m-d [optional]
	 * @param integer $operation Operation [optional]
	 *								1 .. date must be between interrupted interval
	 *								2 .. date must be begin of interrupted interval
	 *								3 .. date must be end of interrupted interval
	 * @return Database_Result
	 */
	public function get_interrupted_members_on($date = NULL, $operation = 1)
	{
		if (empty($date))
		{
			$date = date('Y-m-d');
		}
		
		$op = '';
		
		// select op
		switch ($operation)
		{
			case 2: // begin
				$op = "= mf.activation_date";
				break;
			case 3: // end
				$op = "= mf.deactivation_date";
				break;
			default: // between (1)
				$op = "BETWEEN mf.activation_date AND mf.deactivation_date";
				break;
		}
		
		return $this->db->query("
			SELECT mi.member_id, w.whitelisted
			FROM membership_interrupts mi
			JOIN members_fees mf ON mi.members_fee_id = mf.id
			LEFT JOIN
			(
				SELECT m2.id AS member_id, IF(mw.member_id IS NULL, 0, 2 - mw.permanent) AS whitelisted
				FROM members m2
				LEFT JOIN members_whitelists mw ON mw.member_id = m2.id
					AND mw.since <= CURDATE() AND mw.until >= CURDATE()
			) w ON w.member_id = mi.member_id
			WHERE ? $op
			GROUP BY mi.member_id
		", $date);
	}
	
	/**
	 * Returns all former members without debt (used to membership transfer)
	 * 
	 * @author Michal Kliment
	 * @return Database_Result
	 */
	public function get_all_former_members_without_debt()
	{
		return $this->db->query("
			SELECT m.*
			FROM members m
			JOIN accounts a ON a.member_id = m.id
			WHERE m.type = ? AND a.balance >= 0
		", self::TYPE_FORMER);
	}
	
	/**
	 * Return credit account of member
	 * 
	 * @author Michal Kliment
	 * @param type $member_id
	 * @return type
	 */
	public function get_credit_account($member_id = NULL)
	{
		if (!$member_id && $this)
			$member_id = $this->id;
		
		$account_model = new Account_Model();
		
		return $account_model->get_account_by_account_attribute_and_member(
			Account_attribute_Model::CREDIT, $member_id
		);
	}
}
