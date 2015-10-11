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
 * Contact are user for user contacts, private contacts.
 * Each contact has it's type and value.
 * Phone contacts are connected to countries by pivo table.
 * 
 * @author Ondrej Fibich
 * @package Model
 * 
 * @property int $id
 * @property int $type
 * @property string $value
 * @property int $verify
 * @property ORM_Iterator $countries
 * @property ORM_Iterator $users_contacts
 * @property ORM_Iterator $private_users_contacts
 */
class Contact_Model extends ORM
{
	// type column constants from enum_types id
    const TYPE_ICQ = 18;
	const TYPE_JABBER = 19;
	const TYPE_EMAIL = 20;
	const TYPE_PHONE = 21;
	const TYPE_SKYPE = 22;
	const TYPE_MSN = 23;
	const TYPE_WEB = 25;

	protected $has_many = array
	(
		'users' => 'private_users_contacts'
	);
	
	protected $has_and_belongs_to_many = array
	(
		'users_contacts' => 'users', 'countries'
	);

	/**
	 * Check if user own this contact
	 * 
	 * @param integer $user_id
	 * @return boolean
	 */
	public function is_users_contact($user_id)
	{
		if (!$this->id)
		{
			return false;
		}
		
		return $this->db->query("
				SELECT COUNT(contact_id) AS count
				FROM users_contacts
				WHERE contact_id = ? AND user_id = ?
		", $this->id, $user_id)->current()->count;
	}
	
	/**
	 * Check if the given user own this contact and also whether the contact
	 * is an e-mail address with set up redirection from the inner mail.
	 * 
	 * @param integer $user_id
	 * @return boolean
	 */
	public function is_user_redirected_email($user_id)
	{
		if (!$this->id || $this->type != self::TYPE_EMAIL)
		{
			return false;
		}
		
		return $this->db->query("
				SELECT COUNT(contact_id) AS count
				FROM users_contacts
				WHERE contact_id = ? AND user_id = ? AND mail_redirection = 1
		", $this->id, $user_id)->current()->count;
	}
	
	/**
	 * Set state of e-mail inner mail redirection.
	 * 
	 * @param integer $user_id
	 * @param boolean $redirect IS redirection enabled?
	 */
	public function set_user_redirected_email($user_id, $redirect)
	{
		if ($this->id && $this->type == self::TYPE_EMAIL)
		{		
			$this->db->query("
					UPDATE users_contacts
					SET mail_redirection = ?
					WHERE contact_id = ? AND user_id = ?
			", $redirect ? 1 : 0, $this->id, $user_id);
		}
	}

	/**
	 * Search for relation between users and countacts (users_contacts)
	 * 
	 * @param integer $contact_id
	 * @return integer  Number of relation
	 */
	public function count_all_users_contacts_relation($contact_id = NULL)
	{
		if ($contact_id == NULL && $this)
		{
			$contact_id = $this->id;
		}
		
		return $this->db->query("
				SELECT COUNT(contact_id) AS count
				FROM users_contacts
				WHERE contact_id = ?
		", $contact_id)->current()->count;
	}

	/**
	 * Search for all relation between users and countacts (users_contacts, private_users_contacts)
	 * 
	 * @return integer  Number of relation
	 */
	public function count_all_relation()
	{
		if (!$this->id)
		{
			return 0;
		}
		
		return $this->db->query("
				SELECT (
					(
						SELECT COUNT(contact_id)
						FROM users_contacts
						WHERE contact_id = ?
					) +
					(
						SELECT COUNT(contact_id)
						FROM private_users_contacts
						WHERE contact_id = ?
					)
				) AS count
		", $this->id, $this->id)->current()->count;
	}

	/**
	 * Returns count of users contacts
	 * 
	 * @param int $user_id
	 * @param int $type     Contacts type
	 * @return int
	 */
	public function count_all_users_contacts($user_id, $type = NULL)
	{
		return $this->db->query("
				SELECT COUNT(c.id) AS count
				FROM contacts c
				LEFT JOIN users_contacts u ON u.contact_id = c.id
				WHERE u.user_id = ?" . ($type ? " AND c.type = ?" : "") . "
				ORDER BY c.type
		", $user_id, $type)->current()->count;
	}

	/**
	 * Returns all users contacts (id is from table contacts)
	 * 
	 * @param int $user_id
	 * @param int $type     Contacts type
	 * @return Mysql_Result
	 */
	public function find_all_users_contacts($user_id, $type = NULL)
	{
		return $this->db->query("
				SELECT c.id, c.type, IF(n.country_code IS NULL, c.value,
					CONCAT(n.country_code, c.value)) AS value, c.verify,
					u.user_id
				FROM contacts c
				LEFT JOIN users_contacts u ON u.contact_id = c.id
				LEFT JOIN contacts_countries o ON o.contact_id = c.id
				LEFT JOIN countries n ON n.id = o.country_id
				WHERE u.user_id = ?" . ($type ? " AND c.type = ?" : "") . "
				ORDER BY c.type, c.value
		", $user_id, $type);
	}

	/**
	 * Returns all private users contacts (id is from table private_users_contacts)
	 * 
	 * @param int $user_id
	 * @return Mysql_Result
	 */
	public function find_all_private_users_contacts($user_id)
	{
		return $this->db->query("
				SELECT IF(n.country_code IS NULL, c.value,
					CONCAT(n.country_code, c.value)) AS value,
					c.type, p.description, p.contact_id, p.id, p.user_id
				FROM contacts c
				LEFT JOIN private_users_contacts p ON p.contact_id = c.id
				LEFT JOIN contacts_countries o ON o.contact_id = c.id
				LEFT JOIN countries n ON n.id = o.country_id
				WHERE p.user_id = ? ORDER BY p.description
		", $user_id);
	}

	/**
	 * Find for contact id
	 * 
	 * @param string $type   Type of contact
	 * @param string $value  Value of contact
	 * @return id or false if does't exists
	 */
	public function find_contact_id($type, $value)
	{
		$query = $this->db->query("
				SELECT c.id
				FROM contacts c
				WHERE c.type = ? AND c.value = ?
		", $type, $value);

		return ($query->count() > 0) ? $query->current()->id : FALSE;
	}

	/**
	 * Find for contacts
	 * 
	 * @param string $type   Type of contact
	 * @param string $value  Value of contact
	 * @return Mysql_Result
	 */
	public function find_contacts($type, $value)
	{
		return $this->db->query("
				SELECT c.*
				FROM contacts c
				WHERE c.type = ? AND c.value = ?
		", $type, $value);
	}

	/**
	 * Gets prefix of phone country code
	 * 
	 * @author Ondřej Fibich
	 * @return string	Prefix or empty string
	 */
	public function get_phone_prefix()
	{
		if (!$this->id || $this->type != self::TYPE_PHONE)
		{
			return '';
		}

		foreach ($this->countries as $country)
		{
			return $country->country_code;
		}

		return '';
	}

	public function get_message_info_by_contact_id($contact_id)
	{
		if (!$contact_id || $contact_id === NULL)
		{
			return NULL;
		}
		
		return $this->db->query("
			SELECT m.name as member_name,
					m.id as member_id,
					? as ip_address,
					? as subnet_name,
					(
						SELECT GROUP_CONCAT(vs.variable_symbol) AS variable_symbol
						FROM variable_symbols vs
						LEFT JOIN accounts a ON a.id = vs.account_id
						WHERE a.member_id = m.id
					) AS variable_symbol,
					IFNULL(a.balance,?) AS balance,
					? as comment,
					c.value as contact
			FROM contacts c
			LEFT JOIN users_contacts uc ON uc.contact_id = c.id
			LEFT JOIN users u ON u.id = uc.user_id
			LEFT JOIN members m ON m.id = u.member_id
			LEFT JOIN accounts a ON a.member_id = m.id AND m.id <> 1
			WHERE c.id = ?
		", '???', '???','???','???',$contact_id);
	}
}
