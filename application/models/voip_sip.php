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
 * VoIP SIP account
 * 
 * @package Model
 *
 * @property integer $id
 * @property integer $user_id
 * @property User_Model $user
 * @property string $name
 * @property string $accountcode
 * @property string $amaflags
 * @property string $callgroup
 * @property string $callerid
 * @property string $canreinvite
 * @property string $context
 * @property string $defaultip
 * @property string $dtmfmode
 * @property string $fromuser
 * @property string $fromdomain
 * @property string $fullcontact
 * @property string $host
 * @property string $insecure
 * @property string $language
 * @property string $mailbox
 * @property string $md5secret
 * @property string $nat
 * @property string $deny
 * @property string $permit
 * @property string $mask
 * @property string $pickupgroup
 * @property string $port
 * @property string $qualify
 * @property string $restrictcid
 * @property string $rtptimeout
 * @property string $rtpholdtimeout
 * @property string $secret
 * @property string $type
 * @property string $username
 * @property string $disallow
 * @property string $allow
 * @property string $musiconhold
 * @property integer $regseconds
 * @property string $ipaddr
 * @property string $regexten
 * @property string $cancallforward
 * @property string $setvar
 * @property string $auth
 */
class Voip_sip_Model extends ORM
{
	protected $belongs_to = array('user');
	
	/**
	 * Creates function for VoIP views
	 * 
	 * @author Ondřej Fibich
	 * @see Voip_sip_Model#create_views
	 */
	public static function create_functions()
	{
		$db = Database::instance();
		$db->query("DROP FUNCTION IF EXISTS getstate;");
		$db->query("
			CREATE FUNCTION `getstate`(state INT) RETURNS varchar(255) CHARSET utf8
				NO SQL
			IF state = 0 THEN
				return \"active\";
			ELSE
				return \"blocked\";
			END IF
		");
		$db->query("DROP FUNCTION IF EXISTS gettype;");
		$db->query("
			CREATE FUNCTION `gettype`(type INT) RETURNS varchar(255) CHARSET utf8
				NO SQL
			IF type = 0 THEN
			   return \"prepaid\";
			ELSE
			  return \"postpaid\";
			END IF
		");
	}
	
	/**
	 * Creates views for VoIP synchronization to SIP server
	 * 
	 * @author Ondřej Fibich
	 */
	public static function create_views()
	{
		$db = Database::instance();
		
		/* Add view voip_lbilling_accounts */
		
		$db->query("DROP VIEW IF EXISTS voip_lbilling_accounts;");
		$db->query("
			CREATE VIEW `voip_lbilling_accounts` AS
			select
				`u`.`id` AS `id`,
				`m`.`id` AS `userid`,
				`v`.`name` AS `cid`,
				`getstate`(`m`.`locked`) AS `state`,
				_utf8'0' AS `limit`,
				_utf8'unart-basic' AS `tarif`,
				concat(`m`.`name`,_utf8' - ',`u`.`login`) AS `descr`
			from
			(
				(
					`members` `m` join `users` `u` on
					(
						(
							`u`.`member_id` = `m`.`id`
						)
					)
				) join `voip_sips` `v` on
				(
					(
						`v`.`user_id` = `u`.`id`
					)
				)
			) where (`m`.`type` <> _utf8'15');
		");

		/* Add view voip_lbilling_payments */
		
		$db->query("DROP VIEW IF EXISTS voip_lbilling_payments;");
		$db->query("
			CREATE VIEW `voip_lbilling_payments` AS
			select
				`t`.`id` AS `id`,
				`m`.`id` AS `userid`,
				unix_timestamp(`t`.`creation_datetime`) AS `date`,
				`t`.`amount` AS `value`,
				`t`.`type` AS `state`,
				`t`.`text` AS `descr`
			from 
			(
				(
					`transfers` `t` join `accounts` `a` on
					(
						(
							`t`.`origin_id` = `a`.`id`
						)
					)
				) join `members` `m` on
				(
					(
						`m`.`id` = `a`.`member_id`
					)
				)
			) where (`t`.`type` = _utf8'3');
		");
		
		/* Add view voip_lbilling_users */
		
		$db->query("DROP VIEW IF EXISTS `voip_lbilling_users`;");
		$db->query("
			CREATE VIEW `voip_lbilling_users` AS
			select
				distinct `m`.`id` AS `id`,
				`m`.`voip_billing_type` AS `type`,
				`getstate`(`m`.`locked`) AS `state`,
				`m`.`voip_billing_limit` AS `limit`,
				_utf8'CZK' AS `currency`,
				`m`.`name` AS `descr`
			from 
			(
				(
					`members` `m` join `users` `u` on
					(
						(
							`u`.`member_id` = `m`.`id`
						)
					)
				) join `voip_sips` `v` on
				(
					(
						`v`.`user_id` = `u`.`id`
					)
				)
			) where (`m`.`type` <> _utf8'15') order by `m`.`id`;
		");
	}

	/**
	 * Checks pre requirements for VoIP
	 *
	 * @author Ondřej Fibich
	 * @return boolean
	 */
	public function check_pre_requirements()
	{
		return ($this->function_exists('getstate') &&
				$this->function_exists('gettype') &&
				$this->table_exists('voip_lbilling_accounts') &&
				$this->table_exists('voip_lbilling_payments') &&
				$this->table_exists('voip_lbilling_users'));
	}

	/**
	 * Check if given user has any VoIP sips
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
     * Function counts all records.
	 * 
     * @return integer
     */
	public function count_all_records()
	{
		return $this->db->count_records('voip_sips');
	}


	/**
	 * Function gets record by member.
	 * 
	 * @param integer $user_id
	 * @return integer
	 */
	public function cout_record_by_member($member_id)
	{
		return $this->db->query("
				SELECT COUNT(v.id) AS count
				FROM voip_sips AS v
				INNER JOIN users AS u ON v.user_id = u.id
				WHERE u.member_id = ?
		", $member_id)->current()->count;
	}

    /**
	 * Function gets one record.
	 * 
	 * @param integer $id
	 * @return Mysql_Result
	 */
	public function get_record_limited($id)
	{
		return $this->db->query("
				SELECT id, name, user_id
				FROM voip_sips WHERE id = ?
		", $id);
	}
	

    /**
	 * Gets VoIP SIP with name.
	 * 
	 * @param string $name	VoIP number
	 * @return Mysql_Result
	 */
	public function get_voip_sip_by_name($name)
	{
		return $this->db->query("
				SELECT DISTINCT name, secret
				FROM voip_sips
				WHERE name = ?
		", $name);
	}

    /**
	 * Function gets one record by user.
	 * 
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_record_by_user_limited($user_id)
	{
		return $this->db->query("
				SELECT id, name, user_id
				FROM voip_sips WHERE user_id=?
		", $user_id);
	}

    /**
	 * Function gets one record by user.
	 * 
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_record_by_user($user_id)
	{
		return $this->db->query("
				SELECT *
				FROM voip_sips
				WHERE user_id=?
		", $user_id);
	}

    /**
	 * Function gets all records limited.
	 * 
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_all_records_limited(
			$limit_from = 0, $limit_results = 20,
			$order_by = 'user_id', $order_by_direction = 'asc')
	{
		// order by check
		if (!$this->has_column($order_by))
		{
			$order_by = 'id';
		}
		// order by direction check
		$order_by_direction = strtolower($order_by_direction);
		if ($order_by_direction != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT id, name, user_id
				FROM voip_sips
				ORDER BY $order_by $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) ."
		");
	}

	/**
	 * Function gets all records.
	 * 
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_all_records(
			$limit_from = 0, $limit_results = 20,
			$order_by = 'name', $order_by_direction = 'asc')
	{
		// order by direction check
		$order_by_direction = strtolower($order_by_direction);
		if ($order_by_direction != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT voip_sips.id AS id,
					voip_sips.user_id AS user_id,
					voip_sips.name AS name,
					voip_sips.regseconds AS regseconds,
					users.name AS uname,
					users.surname AS usurname,
					CONCAT(users.name, ' ', users.surname, ' - ', users.login) AS ufname,
					users.member_id AS member_id,
					members.name AS mname,
					members.locked AS locked
				FROM voip_sips
				INNER JOIN users ON voip_sips.user_id = users.id
				INNER JOIN members ON users.member_id = members.id
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) ."
		");
	}

    /**
	 * Function gets all records by member
	 * 
	 * @param integer $id
	 * @return Mysql_Result
	 */
	public function get_all_record_by_member_limited($member_id)
	{
		return $this->db->query("
				SELECT v.id, v.name, v.user_id, v.callerid
				FROM voip_sips v
				INNER JOIN users u ON v.user_id = u.id
				WHERE u.member_id = ?
		", $member_id);
	}

}
