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
 * Due to memory consumption optimalizations some methods has been moved to this
 * class. Preprocessor now only loads one model for all these methods instead of 
 * one model for each method.
 * 
 * @author David Raška
 */
class Preprocessor_Model extends Model
{
	/**
	 * Check if given user has any phone invoice
	 * 
	 * Moved from Phone_invoices_user_Model
	 *
	 * @param integer $user_id	User
	 * @return bool				Has?
	 */
	public function has_phone_invoices($user_id)
	{
		return $this->db->query("
			SELECT COUNT(id) AS count
			FROM phone_invoice_users
			WHERE user_id = ?
		", $user_id)->current()->count > 0;
	}
	
	/**
	 * Gets count of unfilled users phone invoices
	 * 
	 * Moved from Phone_invoices_user_Model
	 *
	 * @param integer $user_id	User
	 * @return integer
	 */
	public function count_unfilled_phone_invoices($user_id)
	{
		return $this->db->query("
			SELECT COUNT(pi.id) AS count
			FROM phone_invoice_users pi
			LEFT JOIN phone_invoices p ON pi.phone_invoice_id = p.id
			WHERE pi.user_id = ? AND pi.locked = 0 AND p.locked = 0
		", $user_id)->current()->count;
	}
	
	/**
	 * Check if given user has any VoIP sips
	 * 
	 * Moved from Voip_sip_Model
	 *
	 * @author Ondřej Fibich
	 * @param integer $user_id	User
	 * @return bool				Has?
	 */
	public function has_voip_sips($user_id)
	{
		return $this->db->query("
			SELECT COUNT(id) AS count
			FROM voip_sips
			WHERE user_id = ?
		", $user_id)->current()->count > 0;
	}

	/**
	 * Gets count of former members that can be deleted.
	 *
	 * constant 15 equals Member_Model::TYPE_FORMER - memory consumption hack
	 *
	 * @param integer $limit_years former member limit years
	 * @return integer
	 */
	public function count_of_former_members_to_delete($limit_years)
	{
		$time_Xyears_before = strtotime('-' . intval($limit_years) . ' years');
		$date_Xyears_before = date('Y-m-d', $time_Xyears_before);
		return $this->db->query("
				SELECT COUNT(m.id) AS count
				FROM members m
				WHERE m.type = ? && m.leaving_date <= ?
		", 15, $date_Xyears_before)->current()->count;
	}

	/**
	 * Returns count of all unread inbox messages of user
	 * 
	 * Moved from Mail_message_Model
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
	 * Function gets count of registered applicans.
	 * 
	 * Moved from Member_Model
	 * 
	 * constant 1 equals Member_Model::TYPE_APPLICANT - memory consumption hack
	 * 
	 * @return integer
	 */
	public function count_of_registered_members()
	{
		return $this->db->query("
				SELECT IFNULL(COUNT(*), 0) AS count
				FROM members m
				WHERE m.type = ?
		", 1)->current()->count;
	}
	
	/**
	 * Gets ID of member account if there is any
	 * 
	 * Moved from Member_Model
	 *
	 * @param integer $member_id
	 * @return integer
	 */
	public function get_first_member_account_id($member_id = NULL)
	{		
		$result = $this->db->query("
				SELECT a.id
				FROM accounts a
				WHERE a.member_id = ?
		", $member_id);
		
		if ($result && $result->count())
		{
			return $result->current()->id;
		}
		
		return NULL;
	}
	
	/**
	 * Gets count of unvoted works of voter
	 * 
	 * Moved from Job_Model
	 * 
	 * @param integer $user_id	ID of voter
	 * @return integer
	 */
	public function get_count_of_unvoted_works_of_voter($user_id)
	{
		return $this->db->query("
				SELECT IFNULL(COUNT(j.id), 0) AS count
				FROM groups_aro_map g
				LEFT JOIN approval_types at ON at.aro_group_id = g.group_id
				LEFT JOIN approval_template_items ati ON at.id = ati.approval_type_id
				LEFT JOIN jobs j ON j.approval_template_id = ati.approval_template_id AND
									j.suggest_amount >= at.min_suggest_amount
				LEFT JOIN votes v ON v.fk_id = j.id AND v.user_id = ? AND v.type = ?
				WHERE g.aro_id = ? AND
					j.job_report_id IS NULL AND
					j.state <= 1 AND
					v.id IS NULL
		", $user_id, Vote_Model::WORK, $user_id)->current()->count;
	}
	
	/**
	 * Gets count of unvoted work reports of voter
	 * 
	 * Moved from Job_reports_Model
	 * 
	 * @param integer $user_id	ID of voter
	 * @return integer
	 */
	public function get_count_of_unvoted_work_reports_of_voter($user_id)
	{
		return $this->db->query("
				SELECT IFNULL(COUNT(*), 0) AS count
				FROM (
					SELECT jr.id
					FROM groups_aro_map g
					LEFT JOIN approval_types at ON at.aro_group_id = g.group_id
					LEFT JOIN approval_template_items ati ON at.id = ati.approval_type_id
					LEFT JOIN job_reports jr ON jr.approval_template_id = ati.approval_template_id
					LEFT JOIN jobs j ON j.job_report_id = jr.id
					LEFT JOIN votes v ON v.fk_id = j.id AND v.user_id = ? AND v.type = ?
					WHERE
						g.aro_id = ? AND
						j.job_report_id IS NOT NULL AND
						v.id IS NULL AND
						jr.concept = 0
					GROUP BY
						jr.id, at.min_suggest_amount
					HAVING
						MIN(j.state) <= 1 AND
						SUM(j.suggest_amount) >= at.min_suggest_amount
				) q
		", $user_id, Vote_Model::WORK, $user_id)->current()->count;
	}
	
	/**
	 * Counts all unvoted requests of voter
	 * 
	 * Moved from Request_Model
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @return integer
	 */
	public function get_count_of_unvoted_requests_of_voter($user_id)
	{
		return $this->db->query("
				SELECT IFNULL(COUNT(r.id), 0) AS count
				FROM groups_aro_map g
				LEFT JOIN approval_types at ON at.aro_group_id = g.group_id
				LEFT JOIN approval_template_items ati ON at.id = ati.approval_type_id
				LEFT JOIN requests r ON r.approval_template_id = ati.approval_template_id AND
									r.suggest_amount >= at.min_suggest_amount
				LEFT JOIN votes v ON v.fk_id = r.id AND v.user_id = ? AND v.type = ?
				WHERE g.aro_id = ? AND
					r.state <= 1 AND
					v.id IS NULL
		", $user_id, Vote_Model::REQUEST, $user_id)->current()->count;
	}
	
	/**
	 * Gets count of down devices
	 * 
	 * Moved from Monitor_host_Model
	 * 
	 * constant 2 equals Monitor_host_Model::STATE_DOWN - memory consumption hack
	 * 
	 * @see My_Controller
	 * @return integer
	 */
	public function count_off_down_devices()
	{
		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM monitor_hosts mh
			WHERE state = ?
		", 2)->current()->total;
	}
	
	/**
	 * Function gets count of unidentified transfers (simplified)
	 * 
	 * Moved from Bank_transfer_Model
	 * 
	 * constant 684000 equals Account_attribute_Model::MEMBER_FEES - memory consumption hack
	 * 
	 * @return integer
	 */
	public function scount_unidentified_transfers()
	{
	    return $this->db->query("
				SELECT COUNT(srct.id) as total
				FROM transfers srct
				JOIN bank_transfers bt ON bt.transfer_id = srct.id
				JOIN accounts a ON a.id = srct.origin_id
					AND srct.member_id IS NULL
					AND a.account_attribute_id = ?
		", array(684000))->current()->total;
	}
	
	/**
	 * Returns allowed subnet by member and ip address
	 * 
	 * Moved from Allowed_subnet_Model
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @param string $ip_address
	 * @return Mysql_Result object
	 */
	public function get_allowed_subnet_by_member_and_ip_address($member_id, $ip_address)
	{
		$result = $this->db->query("
				SELECT a.* FROM subnets s
				JOIN allowed_subnets a ON a.subnet_id = s.id
				WHERE inet_aton(s.netmask) & inet_aton(?) = inet_aton(s.network_address)
					AND a.member_id = ?
		", array($ip_address, $member_id));
		
		return ($result && $result->count()) ? $result->current() : NULL;
	}
	
	/**
	 * Gets count of unclosed logs with a minimal type.
	 * 
	 * Moved from Log_queue_Model
	 * 
	 * constant 1 equals Log_queue_Model::TYPE_ERROR - memory consumption hack
	 * constant 1 equals Log_queue_Model::STATE_CLOSED - memory consumption hack
	 * 
	 * @param int $minimal_type Minimal counted type (e.g. ERROR -> ERROR & FERROR)
	 * @return int Count
	 */
	public function count_of_unclosed_logs($minimal_type = 1)
	{
		return $this->db->query("
			SELECT COUNT(*) AS c
			FROM log_queues l
			WHERE state <> ? AND type <= ?
		", 1, $minimal_type)->current()->c;
	}
	
	/**
	 * Count inactive DHCP servers
	 * 
	 * Moved from Device_Model
	 * 
	 * @return int
	 */
	public function count_inactive_dhcp_servers()
	{
		$min = time() - Settings::get('dhcp_server_reload_timeout');
		
		return $this->db->query("
			SELECT COUNT(device_id) AS c FROM (
				SELECT d.id AS device_id
				FROM subnets s
				JOIN ip_addresses ip ON s.id = ip.subnet_id
				JOIN ifaces i ON i.id = ip.iface_id
				JOIN devices d ON d.id = i.device_id
				WHERE s.dhcp > 0 AND ip.gateway > 0 AND 
					(d.access_time < ? OR d.access_time IS NULL)
				GROUP BY device_id
			) d2
		", date('Y-m-d H:i:s', $min))->current()->c;
	} // end of count_inactive_dhcp_servers
	
	/**
	 * Checks if user have given page in his favourites
	 * 
	 * Copied from User_favourite_pages_Model
	 * 
	 * @param int $user_id
	 * @param string $page
	 * @return bool	TRUE if page is favourite
	 */
	public function is_users_favourite($user_id, $page)
	{
		$result = $this->db->query("
				SELECT page
				FROM user_favourite_pages
				WHERE user_id = ? AND
						page = ?
		", $user_id, $page);
		
		return ($result && $result->count() == 1);
	}
	
	/**
	 * Returns all favourites of given user
	 * 
	 * Copied from User_favourite_pages_Model
	 * 
	 * @param int $user_id User ID
	 * @return ORM object
	 */
	public function get_users_favourites($user_id,
			$limit_from = 0, $limit_results = 50,
			$order_by = 'title', $order_by_direction = 'asc')
	{
		return $this->db->query("
				SELECT * FROM user_favourite_pages
				WHERE user_id = ?
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", $user_id);
	}
	
	/**
	 * Counts all undecided connection requests
	 * 
	 * constant 0 equals Connection_request_Model::STATE_UNDECIDED - memory consumption hack
	 * 
	 * @return integer
	 */
	public function count_undecided_requests()
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM connection_requests
				WHERE state = ?
		", 0)->current()->count;
	}
	
	/**
	 * Counts all disabled and allowed subnets of member
	 * 
	 * Copied from Allowed_subnets_Model
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @return integer
	 */
	public function count_all_disabled_allowed_subnets_by_member($member_id)
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM allowed_subnets a
				WHERE a.member_id = ? AND enabled = 0
		", array($member_id))->current()->count;
	}
	
	/**
	 * This method is used for determining whether the user is connected
	 * from registered connection. If he is the null is returned.
	 * If not then subnet from which he is connected is searched.
	 * If the user may obtain this IP from the searched subnet
	 * the ID of subnet is returned. (but there must not be any connection
	 * request on this connection already in tha database)
	 * 
	 * Copied from Subnet_Model
	 * 
	 * constant 0 equals Connection_request_Model::STATE_UNDECIDED - memory consumption hack
	 * 
	 * @author Ondřej Fibich
	 * @param string $ip_address IP address from which the connection request is made
	 * @return int|null Subnet ID or null if invalid request was made
	 */
	public function get_subnet_for_connection_request($ip_address)
	{
		$result = $this->db->query("
			SELECT s.subnet_id FROM (
				SELECT s.id AS subnet_id
				FROM subnets s
				WHERE inet_aton(s.netmask) & inet_aton(?) = inet_aton(s.network_address)
			) s
			LEFT JOIN ip_addresses ip ON ip.subnet_id = s.subnet_id AND inet_aton(ip.ip_address) = inet_aton(?)
			WHERE ? NOT IN (
				SELECT cr.ip_address FROM connection_requests cr
				WHERE cr.state = ?
			)
			GROUP BY s.subnet_id
			HAVING COUNT(ip.id) = 0
		", $ip_address, $ip_address, $ip_address, 0);
		
		return ($result->count() > 0 ? $result->current()->subnet_id : NULL);
	}
}
