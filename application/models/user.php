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
 * User of member. User is a person.
 * 
 * @author	Michal Kliment
 * @package Model
 * 
 * @property integer $id
 * @property integer $member_id
 * @property Member_Model $member
 * @property string $name
 * @property string $middle_name
 * @property string $surname
 * @property string $pre_title
 * @property string $post_title
 * @property date $birthday
 * @property string $login
 * @property string $password
 * @property string $password_request
 * @property integer $type
 * @property string $comment
 * @property string $application_password
 * @property string $settings
 * @property ORM_Iterator $jobs
 * @property ORM_Iterator $devices
 * @property ORM_Iterator $logs
 * @property ORM_Iterator $phone_invoices_user
 * @property ORM_Iterator $sms_messages
 * @property ORM_Iterator $private_phone_contacts
 * @property ORM_Iterator $users_keys
 * @property ORM_Iterator $device_admins
 * @property ORM_Iterator $device_engineers
 * @property ORM_Iterator $users_contacts
 * @property ORM_Iterator $clouds
 * @property ORM_Iterator $connection_requests
 */
class User_Model extends ORM
{
	/** ID of association user */
	const ASSOCIATION = 1;
	
	/** Type of user: First of users of member is main user */
	const MAIN_USER	= 1;
	/** Type of user: Not main users of member */
	const USER		= 2;
	
	protected $belongs_to = array('member');
	
	protected $has_many = array
	(
		'jobs', 'devices', 'logs', 'phone_invoices_users', 'sms_messages',
		'users' => 'private_phone_contacts', 'users_keys', 'device_admins',
		'device_engineers', 'connection_requests'
	);
	
	protected $has_and_belongs_to_many = array
	(
		'clouds',
		'users_contacts'	=> 'contacts',
	);

	/**
	 * Columns for queries
	 *
	 * @var string
	 */
	public static $arr_sql = array
	(
		'id' => 'u.id',
		'name' => 'u.name',
		'surname' => 'u.surname',
		'login' => 'u.login',
		'member_name' => 'm.name',
		'email' => 'c.value'
	);
	
	/** User settings constants */
	const SETTINGS_MONITORING_GROUP_BY = 'monitoring_group_by';

	/**
	 * Returns all users
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @param numeric $limit_from
	 * @param numeric $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql
	 * @param numeric $member_id
	 * @return Mysql_Result object
	 */
	public function get_all_users(
			$limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'ASC',
			$filter_sql='', $member_id=NULL)
	{
		$having = '';
		$where = '';

		if ($filter_sql != '')
			$having .= 'HAVING '.$filter_sql;

		if ($member_id)
			$where = 'WHERE member_id = '.intval($member_id);
		
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		if (!array_key_exists($order_by, self::$arr_sql))
		{
			$order_by = 'id';
		}
		else
		{
			$order_by = self::$arr_sql[$order_by];
		}
		
		// optimalization
		if (empty($having))
		{
			return $this->db->query("
				SELECT u.*, m.name AS member_name
				FROM users u
				LEFT JOIN members m ON m.id = u.member_id
				$where
				ORDER BY $order_by $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
			", $member_id);
		}

		return $this->db->query("
			SELECT
				u.*,
				m.name AS member_name,
				IFNULL(email.value,'') AS email,
				IFNULL(phone.value,'') AS phone,
				IFNULL(jabber.value,'') AS jabber,
				IFNULL(icq.value,'') AS icq
			FROM users u
			JOIN members m ON u.member_id = m.id
			LEFT JOIN users_contacts uc_e ON uc_e.user_id = u.id
			LEFT JOIN contacts email ON uc_e.contact_id = email.id AND email.type = ?
			LEFT JOIN users_contacts uc_p ON uc_p.user_id = u.id
			LEFT JOIN contacts phone ON uc_p.contact_id = phone.id AND phone.type = ?
			LEFT JOIN users_contacts uc_j ON uc_j.user_id = u.id
			LEFT JOIN contacts jabber ON uc_j.contact_id = jabber.id AND jabber.type = ?
			LEFT JOIN users_contacts uc_i ON uc_i.user_id = u.id
			LEFT JOIN contacts icq ON uc_i.contact_id = icq.id AND icq.type = ?
			$where
			GROUP BY u.id
			$having
			ORDER BY $order_by $order_by_direction
			LIMIT " . intval($limit_from) . "," . intval($limit_results) . "
		", array
		(
			Contact_Model::TYPE_EMAIL,
			Contact_Model::TYPE_PHONE,
			Contact_Model::TYPE_JABBER,
			Contact_Model::TYPE_ICQ
		));
	}

	/**
	 * Counts all users with respect to filter's values
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @author Michal Kliment
	 * @param string $filter_sql
	 * @param integer $member_id
	 * @return integer
	 */
	public function count_all_users($filter_sql = '', $member_id = NULL)
	{
		$having = '';
		$where = '';

		if ($filter_sql != '')
			$having .= 'HAVING '.$filter_sql;

		if ($member_id)
			$where = 'WHERE member_id = '.intval($member_id);
		
		// optimalization
		if (empty($having))
		{
			if (empty($where))
			{
				return $this->count_all();
			}
			
			return $this->db->query("
				SELECT *
				FROM users
				$where
			")->count();
		}

		return $this->db->query("
			SELECT
				u.*,
				m.name AS member_name,
				IFNULL(email.value,'') AS email,
				IFNULL(phone.value,'') AS phone,
				IFNULL(jabber.value,'') AS jabber,
				IFNULL(icq.value,'') AS icq
			FROM users u
			JOIN members m ON u.member_id = m.id
			LEFT JOIN users_contacts uc_e ON uc_e.user_id = u.id
			LEFT JOIN contacts email ON uc_e.contact_id = email.id AND email.type = ?
			LEFT JOIN users_contacts uc_p ON uc_p.user_id = u.id
			LEFT JOIN contacts phone ON uc_p.contact_id = phone.id AND phone.type = ?
			LEFT JOIN users_contacts uc_j ON uc_j.user_id = u.id
			LEFT JOIN contacts jabber ON uc_j.contact_id = jabber.id AND jabber.type = ?
			LEFT JOIN users_contacts uc_i ON uc_i.user_id = u.id
			LEFT JOIN contacts icq ON uc_i.contact_id = icq.id AND icq.type = ?
			$where
			GROUP BY u.id
			$having
		", array
		(
			Contact_Model::TYPE_EMAIL,
			Contact_Model::TYPE_PHONE,
			Contact_Model::TYPE_JABBER,
			Contact_Model::TYPE_ICQ
		))->count();
	}
	
	/**
	 * Gets all users of member
	 *
	 * @param integer $member_id
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @return unknown_type
	 */
	public function get_all_users_of_member(
			$member_id = NULL, $limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'ASC')
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		if (!$this->has_column($order_by))
		{
			$order_by = 'id';
		}

		return $this->db->query("
			SELECT u.*
			FROM users u
			WHERE member_id = ?
			ORDER BY $order_by $order_by_direction
			LIMIT " . intval($limit_from) . "," . intval($limit_results) . "
		", $member_id);
	}
	
	/**
	 * Gets all users of members
	 *
	 * @author David Raska
	 * @param array $member_ids
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @return unknown_type
	 */
	public function get_all_users_of_members(
			$member_ids = NULL, $limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'ASC')
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		if (!$this->has_column($order_by))
		{
			$order_by = 'id';
		}
		
		if (!is_array($member_ids) || !count($member_ids))
		{
			return NULL;
		}
		
		$list = implode(', ', array_map('intval', $member_ids));

		return $this->db->query("
			SELECT u.*
			FROM users u
			WHERE member_id IN ($list)
			ORDER BY $order_by $order_by_direction
			LIMIT " . intval($limit_from) . "," . intval($limit_results) . "
		");
	}
	
	/**
	 * Counts all users of members
	 * 
	 * @author David Raska
	 * @param array $member_ids
	 * @return integer
	 */
	public function count_all_users_of_members($member_ids = NULL)
	{
		if (!is_array($member_ids) || !count($member_ids))
		{
			return 0;
		}
		
		$list = implode(', ', array_map('intval', $member_ids));

		return $this->db->query("
			SELECT u.*
			FROM users u
			WHERE member_id IN ($list)
		")->count();
	}
	
	/**
	 * Function gets selected users.
	 * 
	 * @param array $ids
	 * @param boolean $in_set
	 * 
	 * @author Jan Dubina
	 * @return Mysql_Result
	 */
	public function get_users_to_sync_vtiger($ids, $in_set)
	{
		$filter_sql = '';
		// where condition
		if (!empty($ids) || $in_set === false)
		{
			if (!empty($ids))
				if ($in_set === true)
					$filter_sql = "WHERE u.id IN (" . implode(',', $ids) . ")";
				else
					$filter_sql = "WHERE u.id NOT IN (" . implode(',', $ids) . ")";
		
			// query
			return $this->db->query("
				SELECT u.id, member_id, comment, type, birthday, post_title, 
					pre_title, surname, middle_name, name, email, phone, street,
					street_number, town, zip_code, country_name
					FROM users u
					LEFT JOIN
					(
						SELECT m.id, country_name, town, zip_code,
							street, street_number
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
					) m ON m.id = u.member_id
					LEFT JOIN 
					(
						SELECT user_id, GROUP_CONCAT(value SEPARATOR ';') AS email
						FROM users_contacts uc 
						LEFT JOIN contacts c ON uc.contact_id = c.id
						WHERE c.type = ?
						GROUP BY user_id
					) ce ON u.id = ce.user_id 
					LEFT JOIN 
					(
						SELECT user_id, GROUP_CONCAT(value SEPARATOR ';') AS phone
						FROM users_contacts uc 
						LEFT JOIN contacts c ON uc.contact_id = c.id
						WHERE c.type = ?
						GROUP BY user_id
					) cp ON u.id = cp.user_id 
					$filter_sql
			", array(
						Contact_Model::TYPE_EMAIL,
						Contact_Model::TYPE_PHONE
			));
		}
	}

	/**
	 * Login test function
	 *
	 * @param string $username
	 * @param string $password
	 * @return boolean
	 */
	public function login_request($username = '', $password = '')
	{
		$query = $this->db->from('users')->select('id')->where(array
		(
			'login' => $username,
			'password' => sha1($password)
		))->get();
		
		if ($query->count())
		{
			return $query->current()->id;
		}
		
		// see Settings for exclamation
		if (Settings::get('pasword_check_for_md5')) 
		{
			$query = $this->db->from('users')->select('id')->where(array
			(
				'login' => $username,
				'password' => md5($password)
			))->get();
			
			if ($query->count())
			{
				return $query->current()->id;
			}
		}
		
		return 0;
	}

	/**
	 * Counts all users belong to member
	 * 
	 * @param numeric $member_id
	 * @return numeric
	 */
	public function count_all_users_by_member($member_id = NULL)
	{
		return (int) $this->db->where('member_id',$member_id)->count_records('users');
	}

	/**
	 * Tests if username exist
	 *
	 * @param string $username
	 * @param numeric $user_id
	 * @return boolean
	 */
	public function username_exist($username, $user_id = null)
	{
		// tests if user_id is id of user with this username (for validation in user edit function)
		if (isset($user_id))
		{
			return (bool) $this->db->where(array
			(
				 'login'	=> $username,
				 'id!='		=> $user_id
			))->count_records('users');
		}
		else
		{
			return (bool) $this->db->where('login', $username)->count_records('users');
		}
	}

	/**
	 * Tests if phone exist
	 *
	 * @param string $phone
	 * @param numeric $user_id
	 * @return boolean
	 */
	public function phone_exist($phone, $user_id = null)
	{
		$where = '';
		if ($user_id && is_numeric($user_id))
			$where = "AND uc.user_id = ".intval($user_id);
		
		return $this->db->query("
			SELECT COUNT(id) AS count
			FROM users_contacts uc
			JOIN contacts c ON uc.contact_id = c.id
			WHERE c.type = ? AND c.value = ? $where
		", array(Contact_Model::TYPE_PHONE, $phone))->current()->count > 0;
	}

	/**
	 * Tests if email exist
	 *
	 * @param string $email
	 * @param numeric $user_id
	 * @return boolean
	 */
	public function email_exist($email, $user_id = null)
	{
	    if (empty ($user_id) || !is_numeric($user_id))
	    {
			return $this->db->query("
					SELECT COUNT(id) AS count FROM contacts c
					WHERE c.type = ? AND c.value = ?
			", array(Contact_Model::TYPE_EMAIL, $email))->current()->count > 0;
	    }
	    else
	    {
			return $this->db->query("
					SELECT COUNT(*) AS count FROM users_contacts u
					LEFT JOIN contacts c ON u.contact_id = c.id
					WHERE u.user_id = ? AND c.type = ? AND c.value = ?
			", array($user_id, Contact_Model::TYPE_EMAIL, $email))->current()->count > 0;
	    }
	}

	/**
	 * Gets user whose name contains given str
	 *
	 * @param string $query
	 * @return unknown_type
	 */
	public function get_users($query)
	{
		return $this->db->query("
				SELECT id, CONCAT(surname,' ',name,' - ',login) as user
				FROM users
				WHERE CONCAT(surname,' ',name,' - ',login) LIKE ?
				GROUP BY CONCAT(surname,' ',name,' - ',login)
		", "$query%");
	}
	
	/**
	 * Gets all user names
	 *
	 * @return unknown_type
	 */
	public function get_all_user_names()
	{
		return $this->db->query("
				SELECT id, CONCAT(name,' ',surname) as username
				FROM users
				GROUP BY CONCAT(name,' ',surname)
		");		
	}
	
	/**
	 * Gets user's usernames
	 *
	 * @param integer $user_id
	 * @return unknown_type
	 */
	public function get_his_users_names($user_id)
	{
		return $this->db->query("
				SELECT u2.id, CONCAT(u2.name,' ',u2.surname) AS username
				FROM users AS u1
				JOIN users AS u2 ON u1.id=? AND u1.member_id=u2.member_id
		", $user_id);
	}

	/**
	 * Get user's username
	 *
	 * @param integer $user_id
	 * @return unknown_type
	 */
	public function get_his_username($user_id)
	{
		return $this->db->query("
				SELECT id, CONCAT(name,' ',surname) AS username
				FROM users  
				WHERE id=?
		", $user_id);
	}
	
	/**
	 * Gets user's usernames whose name contains given str
	 *
	 * @param integer $query
	 * @return unknown_type
	 */
	public function get_usernames($query)
	{
		return $this->db->query("
				SELECT id, CONCAT(name,' ',surname) as username
				FROM users
				WHERE CONCAT(name,' ',surname) LIKE ?
					GROUP BY CONCAT(name,' ',surname)
		", "$query%");
	}
	
	/**
	 * Function searches for items dependent on given user. Used for deleting user.
	 * 
	 * @param integer $user_id
	 * @return unknown_type
	 */
	public function count_dependent_items($user_id)
	{
		$user_id = intval($user_id);
		
		return $this->db->query("
				SELECT COUNT(*) AS total FROM
				(
					SELECT d.id
					FROM devices d
					WHERE d.user_id = $user_id
					UNION
					SELECT j.id
					FROM jobs j
					WHERE j.user_id = $user_id
					UNION
					SELECT da.id
					FROM device_admins da
					WHERE da.user_id = $user_id
					UNION
					SELECT de.id
					FROM device_engineers de
					WHERE de.user_id = $user_id
					UNION
					SELECT c.contact_id
					FROM users_contacts c
					WHERE c.user_id = $user_id
					UNION
					SELECT pi.id
					FROM phone_invoice_users pi
					WHERE pi.user_id = $user_id
				) di
		")->current()->total;
	}
	
	/**
	 * Function searches for items dependent on given user and delete them.
	 * 
	 * @param integer $user_id
	 */
	public function delete_depends_items($user_id)
	{
		$this->delete_watchers($user_id);

		$this->db->query("
				DELETE FROM devices
				WHERE user_id = ?
		", $user_id);
		
		$this->db->query("
				DELETE FROM jobs
				WHERE user_id = ?
		", $user_id);
		
		$this->db->query("
				DELETE FROM device_admins
				WHERE user_id = ?
		", $user_id);
		
		$this->db->query("
				DELETE FROM device_engineers
				WHERE user_id = ?
		", $user_id);
		
		$this->db->query("
				DELETE FROM users_contacts
				WHERE user_id = ?
		", $user_id);
		
		$this->db->query("
				DELETE FROM phone_invoice_users
				WHERE user_id = ?
		", $user_id);
	}

	/**
	 * Function searches for user watchers and delete them.
	 *
	 * @param integer $user_id
	 */
	public function delete_watchers($user_id)
	{
		$this->db->query("
				DELETE FROM watchers
				WHERE user_id = ?
		", $user_id);
	}

	/**
	 * Selects all users emails
	 * 
	 * @author Ondřej Fibich
	 * @param integer $user_id
	 * @return unknown_type Mysql result
	 */
	public function get_user_emails($user_id)
	{
		return $this->db->query("
				SELECT c.value as email
				FROM users_contacts u
				LEFT JOIN contacts c ON c.id = u.contact_id
				WHERE u.user_id = ? AND c.type = ?
		", array($user_id, Contact_Model::TYPE_EMAIL));
	}

	/**
	 * Returns user by phone number and country code
	 *
	 * @author Michal Kliment
	 * @param string $number
	 * @param string $country_code
	 * @return MySQL_Result object
	 */
	public function get_user_by_phone_number_country_code($number, $country_code)
	{
		return $this->db->query("
				SELECT u.* FROM contacts c
				JOIN contacts_countries cc ON cc.contact_id = c.id
				JOIN countries t ON t.id = cc.country_id
				JOIN users_contacts uc ON uc.contact_id = c.id
				JOIN users u ON uc.user_id = u.id
				WHERE c.value LIKE ? AND t.country_code LIKE ?
				LIMIT 0,1
		", array($number, $country_code))->current();
	}

	/**
	 * Gets ARO groups of user
	 *
	 * @param integer $user_id
	 * @return unknown_type
	 */
	public function get_aro_groups_of_user($user_id)
	{
		return $this->db->query("
				SELECT ag.id, ag.name
				FROM aro_groups ag
				JOIN groups_aro_map gam ON ag.id = gam.group_id
				WHERE gam.aro_id = ?
		", $user_id);
	}
	
	/**
	 * Checks if user is in ARO group
	 * 
	 * @param int $user_id	User ID
	 * @param int $aro_group	ARO Group
	 * @return int
	 */
	public function is_user_in_aro_group($user_id, $aro_group)
	{
		return $this->db->query("
				SELECT ag.id
				FROM aro_groups ag
				JOIN groups_aro_map gam ON ag.id = gam.group_id
				WHERE ag.id = ? AND
					gam.aro_id = ?
		", $aro_group, $user_id)->count();
	}
	
	/**
	 * Gets array of users for selectbox
	 * 
	 * @return array[string]
	 */
	public function select_list_grouped($optgroup = TRUE)
	{
		$list = array();
		
		$assoc_user = ORM::factory('user', self::ASSOCIATION);
		
		if ($optgroup)
			$list[__('Association')][$assoc_user->id] = $assoc_user->member->name;
		else
			$list[$assoc_user->id] = $assoc_user->member->name;
		
		$concat = "CONCAT(
				COALESCE(surname, ''), ' ',
				COALESCE(name, ''), ' - ',
				COALESCE(login, '')
		)";
		
		if ($optgroup)
		{
			$list[__('Users')] = $assoc_user->where('id !=', self::ASSOCIATION)
					->select_list('id', $concat);
		}
		else
		{
			$list += $assoc_user->where('id !=', self::ASSOCIATION)
					->select_list('id', $concat);
		}
		
		return $list;
	}
	
	
	/**
	 * Function gets all records, which are in given aro group by id of group
	 * 
	 * @param integer $id
	 * @param string $name
	 * @return unknown_type
	 */
    public function get_all_by_aro_group_id($id, $name = NULL)
    {
		$like = '';
		
		// search?
        if (!empty($name))
		{
			$name = $this->db->escape("%$name%");
			$like = "(name LIKE $name OR surname LIKE $name OR login LIKE $name) AND ";
		}
		
		// query
		return $this->db->query("
				SELECT u.id, CONCAT(
						COALESCE(u.login, ''), ' - ',
						COALESCE(u.surname, ''), ' ',
						COALESCE(u.name, '')
					) AS name
				FROM users u
				WHERE $like id IN (
					SELECT aro_id
					FROM groups_aro_map
					WHERE group_id=?
					GROUP BY aro_id
				) ORDER BY name
		", $id);
    }

	/**
	 * Function gets all records, which are not in given aro group by id of group
	 * 
	 * @param integer $id
	 * @param string $name
	 * @return unknown_type
	 */
    public function get_all_not_in_by_aro_group_id($id, $name = NULL)
    {
		$like = '';
		
		// search?
        if (!empty($name))
		{
			$name = $this->db->escape("%$name%");
			$like = "(name LIKE $name OR surname LIKE $name OR login LIKE $name) AND ";
		}
		
		// query
		return $this->db->query("
				SELECT u.id, CONCAT(
						COALESCE(u.login, ''), ' - ',
						COALESCE(u.surname, ''), ' ',
						COALESCE(u.name, '')
					) AS name
				FROM users u
				WHERE $like id NOT IN (
					SELECT aro_id
					FROM groups_aro_map
					WHERE group_id=?
					GROUP BY aro_id
				) ORDER BY name
		", $id);
    }	

	/**
	 * Function gets all records, which are in given aro device_admins group by id of group
	 * 
	 * @param integer $id
	 * @param string $name
	 * @return unknown_type
	 */
    public function get_all_from_device_admins_by_aro_group_id($id, $name = NULL)
    {
		$like = '';
		
		// search?
        if (!empty($name))
		{
			$name = $this->db->escape("%$name%");
			$like = "(name LIKE $name OR surname LIKE $name OR login LIKE $name) AND ";
		}
		
		// query
		return $this->db->query("
				SELECT u.id, CONCAT(
						COALESCE(u.login, ''), ' - ',
						COALESCE(u.surname, ''), ' ',
						COALESCE(u.name, '')
					) AS name
				FROM users u
				WHERE $like id IN (
					SELECT user_id
					FROM device_admins
					WHERE device_id=?
					GROUP BY user_id
				) ORDER BY name
		", $id);
    }

    /**
	 * Function gets all records, which are not in given aro device_admins group by id of group
	 * 
	 * @param integer $id
	 * @param string $name
	 * @return unknown_type
	 */
    public function get_all_not_in_from_device_admins_by_aro_group_id($id, $name = NULL)
    {
		$like = '';
		
		// search?
        if (!empty($name))
		{
			$name = $this->db->escape("%$name%");
			$like = "(name LIKE $name OR surname LIKE $name OR login LIKE $name) AND ";
		}
		
		// query
		return $this->db->query("
				SELECT u.id, CONCAT(
						COALESCE(u.login, ''), ' - ',
						COALESCE(u.surname, ''), ' ',
						COALESCE(u.name, '')
					) AS name
				FROM users u
				WHERE $like id NOT IN (
					SELECT user_id
					FROM device_admins
					WHERE device_id=?
					GROUP BY user_id
				) ORDER BY name
		", $id);
    }
	
	public function get_users_not_in_engineer_of($device_id)
	{
		return $this->db->query("
				SELECT u.id, CONCAT(
						COALESCE(u.login, ''), ' - ',
						COALESCE(u.surname, ''), ' ',
						COALESCE(u.name, '')
					) AS name
				FROM users u
				WHERE u.id NOT IN (
					SELECT de.user_id
					FROM device_engineers de
					WHERE de.device_id = ?
				) ORDER BY name
		", $device_id);
	}
	
	/**
	 * Gets full name of user
	 *
	 * @return string
	 */
	public function get_full_name()
	{
		if (!$this->id)
			return NULL;
			
		return
			(empty($this->pre_title) ? '' : $this->pre_title . ' ') . 
			$this->name . ' ' . 
			(empty($this->middle_name) ? '' : $this->middle_name . ' ') . 
			$this->surname .
			(empty($this->post_title) ? '' : ', ' . $this->post_title);
	}
	
	/**
	 * Gets full name of user with his login
	 *
	 * @return string
	 */
	public function get_full_name_with_login()
	{
		if (!$this->id)
			return NULL;
			
		return $this->get_full_name() . ' - ' . $this->login;
	}
	
	public function get_all_users_by_gps ($gpsx, $gpsy, $user_id = NULL)
	{
		$where = '';
		if ($user_id)
			$where = 'AND u.id <> '.intval($user_id);
		
		if (gps::is_valid_degrees_coordinate($gpsx))
			$gpsx = gps::degrees2real ($gpsx);
		
		if (gps::is_valid_degrees_coordinate($gpsy))
			$gpsy = gps::degrees2real ($gpsy);
		
		if (is_numeric($gpsx) && is_numeric($gpsy))
		{
			return $this->db->query("
				SELECT u.id, CONCAT(u.surname,' ',u.name,' - ',u.login) AS name,
				IFNULL(SQRT(POW(X(gps)-?,2)+POW(Y(gps)-?,2)),1000) AS distance
				FROM users u
				LEFT JOIN members m ON u.member_id = m.id
				LEFT JOIN address_points ap ON m.address_point_id = ap.id
				WHERE u.id <> ? $where
				ORDER BY distance, u.surname
			", array($gpsx, $gpsy, self::ASSOCIATION));
		}
		else
		{
			
		}
	}
	
	public function get_name_with_login ($user_id = NULL)
	{
		if (!$user_id)
			$user_id = $this->id;
		
		$result = $this->db->query("
			SELECT
			CONCAT(u.surname,' ',u.name,' - ',u.login) AS name
			FROM users u
			WHERE id = ?
		", $user_id);
		
		if ($result && $result->current())
			return $result->current()->name;
		else
			return '';
	}
	
	/**
	 * Gets full name of user
	 * 
	 * @return string
	 */
	public function __toString()
	{
		$this->get_full_name();
	}

	/**
	 * Get user settings
	 * 
	 * @param string $key	Key
	 * @return mixed		Value
	 */
	public function get_user_setting($key, $default = NULL)
	{
		$json_settings = $this->settings;
		
		// return empty value on no settings
		if (empty($json_settings))
		{
			return $default;
		}
		
		// decode json
		$settings = json_decode($json_settings, TRUE);
		
		// return empty value on no settings
		if (empty($settings))
		{
			return $default;
		}
		// return value
		else if (isset($settings[$key]))
		{
			return $settings[$key];
		}
		// no value found
		else
		{
			return $defaut;
		}
	}
	
	/**
	 * Sets user settings
	 * 
	 * @param string $key	Key
	 * @param mixed $value	Value
	 */
	public function set_user_setting($key, $value)
	{
		$json_settings = $this->settings;

		// decode json
		$settings = json_decode($json_settings, TRUE);

		// add new settings
		$settings[$key] = $value;

		// encode settings to json
		$json_settings = json_encode($settings);

		$this->settings = $json_settings;

		// save
		$this->save_throwable();	
	}
}
