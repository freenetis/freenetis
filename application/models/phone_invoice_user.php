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
 * Descriptions of Phone_invoice_user_Model
 *
 * @author Ondřej Fibich
 * @package Model
 * 
 * @property integer $id
 * @property integer $user_id
 * @property User_Model $user
 * @property integer $phone_invoice_id
 * @property Phone_invoice_Model $phone_invoice
 * @property integer $transfer_id
 * @property Transfer_Model $transfer
 * @property string $phone_number
 * @property boolean $locked
 * @property ORM_Iterator $phone_connections
 * @property ORM_Iterator $phone_calls
 * @property ORM_Iterator $phone_fixed_calls
 * @property ORM_Iterator $phone_pays
 * @property ORM_Iterator $phone_roaming_sms_messages
 * @property ORM_Iterator $phone_sms_messages
 * @property ORM_Iterator $phone_vpn_calls
 */
class Phone_invoice_user_Model extends ORM
{
	protected $belongs_to = array('user', 'phone_invoice', 'transfer');
	
	protected $has_many = array
	(
		'phone_connections', 'phone_calls', 'phone_fixed_calls', 'phone_pays',
		'phone_roaming_sms_messages', 'phone_sms_messages', 'phone_vpn_calls'
	);

	/**
	 * Get sum of all users phone invoices separated to cells price_company and price_private
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_total_prices($user_id)
	{
		$result = $this->db->query("
			SELECT id FROM phone_invoice_users WHERE user_id = ?
		", array($user_id));

		$id_array = array();
		foreach ($result as $p)
		{
			$id_array[] = $p->id;
		}
		$ids = implode(",", $id_array);

		if (empty($ids))
		{
			return $this->db->query("
					SELECT '0' AS price_company, '0' AS price_private
			")->current();
		}

		return $this->db->query("
				SELECT  ((
					SELECT IFNULL(SUM(price), 0)
					FROM phone_calls
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=0
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_fixed_calls
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=0
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_vpn_calls
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=0
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_pays
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=0
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_connections
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=0
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_roaming_sms_messages
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=0
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_sms_messages
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=0
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_roaming_sms_messages
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=0
				)) AS price_company,

				((
					SELECT IFNULL(SUM(price), 0)
					FROM phone_calls
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=1
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_fixed_calls
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=1
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_vpn_calls
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=1
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_pays
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=1
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_connections
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=1
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_roaming_sms_messages
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=1
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_sms_messages
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=1
				) + (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_roaming_sms_messages
					WHERE phone_invoice_user_id IN (" . $ids . ") AND private=1
				)) AS price_private
			")->current();
		}

	/**
	 * Gets all users phone invoices
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_phone_invoices_of_user($user_id)
	{
		return $this->db->query("
				SELECT p.id, pi.locked, p.locked AS filled,
					pi.billing_period_from,
					pi.billing_period_to, p.user_id, p.transfer_id, t.amount,
					p.phone_number AS number,
					((
						SELECT IFNULL(SUM(price), 0)
						FROM phone_calls
						WHERE phone_invoice_user_id=p.id AND private=0
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_fixed_calls
						WHERE phone_invoice_user_id=p.id AND private=0
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_vpn_calls
						WHERE phone_invoice_user_id=p.id AND private=0
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_pays
						WHERE phone_invoice_user_id=p.id AND private=0
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_connections
						WHERE phone_invoice_user_id=p.id AND private=0
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_roaming_sms_messages
						WHERE phone_invoice_user_id=p.id AND private=0
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_sms_messages
						WHERE phone_invoice_user_id=p.id AND private=0
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_roaming_sms_messages
						WHERE phone_invoice_user_id=p.id AND private=0
					)) AS price_company,

					((
						SELECT IFNULL(SUM(price), 0)
						FROM phone_calls
						WHERE phone_invoice_user_id=p.id AND private=1
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_fixed_calls
						WHERE phone_invoice_user_id=p.id AND private=1
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_vpn_calls
						WHERE phone_invoice_user_id=p.id AND private=1
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_pays
						WHERE phone_invoice_user_id=p.id AND private=1
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_connections
						WHERE phone_invoice_user_id=p.id AND private=1
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_roaming_sms_messages
						WHERE phone_invoice_user_id=p.id AND private=1
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_sms_messages
						WHERE phone_invoice_user_id=p.id AND private=1
					) + (
						SELECT IFNULL(SUM(price), 0)
						FROM phone_roaming_sms_messages
						WHERE phone_invoice_user_id=p.id AND private=1
					)) AS price_private

				 FROM phone_invoice_users p
				 LEFT JOIN transfers t ON p.transfer_id = t.id
				 LEFT JOIN phone_invoices pi ON p.phone_invoice_id = pi.id
				 WHERE p.user_id = ?
		", $user_id);
	}

	/**
	 * Gets info about each number in invoice.
	 * Calculates total price for each number.
	 * @param integer $invoice_id
	 * @return Mysql_Result
	 */
	public function get_all_invoice_users($invoice_id)
	{
		// madness query to calculate price for each user invoicee
		return $this->db->query("
				SELECT p.tax_rate, piu.id, piu.locked AS filled,
					piu.user_id, piu.phone_number , piu.transfer_id, t.amount,					
					CONCAT( users.surname, ' ', users.name ) AS name,
					(
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_calls
						WHERE phone_invoice_user_id =piu.id AND private = 0)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_fixed_calls
						WHERE phone_invoice_user_id =piu.id AND private = 0)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_vpn_calls
						WHERE phone_invoice_user_id =piu.id AND private = 0)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_sms_messages
						WHERE phone_invoice_user_id =piu.id AND private = 0)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_pays
						WHERE phone_invoice_user_id =piu.id AND private = 0)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_roaming_sms_messages
						WHERE phone_invoice_user_id =piu.id AND private = 0)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_connections
						WHERE phone_invoice_user_id =piu.id AND private = 0)
					) * (1 + p.tax_rate / 100) AS price_company,
					(
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_calls
						WHERE phone_invoice_user_id =piu.id AND private = 1)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_fixed_calls
						WHERE phone_invoice_user_id =piu.id AND private = 1)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_vpn_calls
						WHERE phone_invoice_user_id =piu.id AND private = 1)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_sms_messages
						WHERE phone_invoice_user_id =piu.id AND private = 1)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_pays
						WHERE phone_invoice_user_id =piu.id AND private = 1)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_roaming_sms_messages
						WHERE phone_invoice_user_id =piu.id AND private = 1)
						+
						(SELECT IFNULL(SUM( price ), 0)
						FROM phone_connections
						WHERE phone_invoice_user_id =piu.id AND private = 1)
					) * (1 + p.tax_rate / 100) AS price_private
				FROM phone_invoice_users piu
				LEFT JOIN phone_invoices p ON p.id =  piu.phone_invoice_id
				LEFT JOIN users ON piu.user_id = users.id
				LEFT JOIN transfers t ON piu.transfer_id = t.id
				WHERE piu.phone_invoice_id = ?;
		", array($invoice_id));
	}

	/**
	 * Return price of current user phone invoice
	 * @return double
	 */
	public function get_price()
	{
		$result = $this->db->query("
			SELECT ((SELECT IFNULL(SUM( price ), 0)
				FROM phone_calls
				WHERE phone_invoice_user_id =" . $this->id . ")
				+
				(SELECT IFNULL(SUM( price ), 0)
				FROM phone_fixed_calls
				WHERE phone_invoice_user_id =" . $this->id . ")
				+
				(SELECT IFNULL(SUM( price ), 0)
				FROM phone_vpn_calls
				WHERE phone_invoice_user_id =" . $this->id . ")
				+
				(SELECT IFNULL(SUM( price ), 0)
				FROM phone_sms_messages
				WHERE phone_invoice_user_id =" . $this->id . ")
				+
				(SELECT IFNULL(SUM( price ), 0)
				FROM phone_pays
				WHERE phone_invoice_user_id =" . $this->id . ")
				+
				(SELECT IFNULL(SUM( price ), 0)
				FROM phone_roaming_sms_messages
				WHERE phone_invoice_user_id =" . $this->id . ")
				+
				(SELECT IFNULL(SUM( price ), 0)
				FROM phone_connections
				WHERE phone_invoice_user_id =" . $this->id . ")) AS price"
		);

		return ($result) ? $result->current()->price : 0.0;
	}

	/**
	 * Vat and Out of tax price of each service
	 * @return Database_Result
	 */
	public function get_prices()
	{
		return $this->db->query("
				SELECT (
					SELECT IFNULL(SUM(price), 0)
					FROM phone_calls
					WHERE phone_invoice_user_id=" . $this->id . " AND private=0
				) AS phone_calls_company,(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_calls
					WHERE phone_invoice_user_id=" . $this->id . " AND private=1
				) AS phone_calls_private,

				(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_fixed_calls
					WHERE phone_invoice_user_id=" . $this->id . " AND private=0
				) AS phone_fixed_calls_company,(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_fixed_calls
					WHERE phone_invoice_user_id=" . $this->id . " AND private=1
				) AS phone_fixed_calls_private,

				(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_vpn_calls
					WHERE phone_invoice_user_id=" . $this->id . " AND private=0
				) AS phone_vpn_calls_company,(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_vpn_calls
					WHERE phone_invoice_user_id=" . $this->id . " AND private=1
				) AS phone_vpn_calls_private,

				(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_pays
					WHERE phone_invoice_user_id=" . $this->id . " AND private=0
				) AS phone_pays_company,(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_pays
					WHERE phone_invoice_user_id=" . $this->id . " AND private=1
				) AS phone_pays_private,

				(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_connections
					WHERE phone_invoice_user_id=" . $this->id . " AND private=0
				) AS phone_connections_company,(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_connections
					WHERE phone_invoice_user_id=" . $this->id . " AND private=1
				) AS phone_connections_private,

				(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_roaming_sms_messages
					WHERE phone_invoice_user_id=" . $this->id . " AND private=0
				) AS phone_roaming_sms_message_company,(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_roaming_sms_messages
					WHERE phone_invoice_user_id=" . $this->id . " AND private=1
				) AS phone_roaming_sms_messages_private,

				(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_sms_messages
					WHERE phone_invoice_user_id=" . $this->id . " AND private=0
				) AS phone_sms_messages_company,(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_sms_messages
					WHERE phone_invoice_user_id=" . $this->id . " AND private=1
				) AS phone_sms_messages_private,

				(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_roaming_sms_messages
					WHERE phone_invoice_user_id=" . $this->id . " AND private=0
				) AS phone_roaming_sms_messages_company,(
					SELECT IFNULL(SUM(price), 0)
					FROM phone_roaming_sms_messages
					WHERE phone_invoice_user_id=" . $this->id . " AND private=1
				) AS phone_roaming_sms_messages_private
		")->current();
	}

	/**
	 * Search for user with specific phone number
	 * @param string $phone_number  Number with prefix
	 * @return integer  ID or zero if cannot find nuber in database
	 */
	public function get_user_id($phone_number)
	{
		static $country = NULL;

		if ($country == NULL)
		{
			$country = new Country_Model();
		}

		$country_code = $country->find_phone_country_code($phone_number);

		$query = $this->db->query("
				SELECT u.user_id FROM users_contacts u
				LEFT JOIN contacts c ON c.id = u.contact_id
				WHERE type = ? AND value = ?;
		", array(Contact_Model::TYPE_PHONE, substr($phone_number, mb_strlen($country_code))));

		return ($query->count() > 0) ? $query->current()->user_id : 0;
	}

}
