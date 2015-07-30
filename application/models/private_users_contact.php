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
 * Private conntact of user is not contact to owner user but some contact
 * to another person.
 *
 * @author Ondřej Fibich
 * @package Model
 *
 * @property int $id
 * @property int $user_id
 * @property int $contact_id
 * @property string $description
 * @property User_Model $user
 * @property Contact_Model $contact
 */
class Private_users_contact_Model extends ORM
{

    protected $belongs_to = array('user', 'contact');

    /**
     * Search for users contact with specific phone number
     * @param integer $user_id
     * @param string $phone_number  Number with prefix
     * @return integer  ID or zero if cannot find nuber in database
     */
    public function get_contact_id($user_id, $phone_number)
	{
		static $country = NULL;

		if ($country == NULL)
			$country = new Country_Model();

		$country_code = $country->find_phone_country_code($phone_number);

		$query = $this->db->query("
				SELECT p.id FROM private_users_contacts p
				LEFT JOIN contacts c ON p.contact_id = c.id
				WHERE p.user_id = ? AND type = ? AND c.value = ?
		", array
		(
			$user_id,
			Contact_Model::TYPE_PHONE,
			substr($phone_number, strlen($country_code))
		));

		if ($query && $query->count() > 0)
		{
			return $query->current()->id;
		}

		return 0;
	}

}
