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
 * Invoice
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $member_id
 * @property Member_Model $partner
 * @property string $partner_company
 * @property string $partner_name
 * @property string $partner_street
 * @property string $partner_street_number
 * @property string $partner_town
 * @property string $partner_zip_code
 * @property string $partner_country
 * @property string $organization_identifier
 * @property string $vat_organization_identifier
 * @property string $phone_number
 * @property string $email
 * @property double $invoice_nr
 * @property integer $invoice_type
 * @property string $account_nr
 * @property double $var_sym
 * @property double $con_sym
 * @property date $date_inv
 * @property date $date_due
 * @property date $date_vat
 * @property integer $vat
 * @property double $order_nr
 * @property string $currency
 * @property string $note
 * @property ORM_Iterator $invoice_items
 */
class Invoice_Model extends ORM
{
	/** Type of invoice: issued */
	const TYPE_ISSUED = 0;
	/** Type of member: received */
	const TYPE_RECEIVED = 1;
	
	/**
	* Types of invoice
	* 
	* @var array
	*/
	private static $types = array
	(
		self::TYPE_ISSUED		=> 'Issued',
		self::TYPE_RECEIVED		=> 'Received'
	);

	protected $belongs_to = array('partner' => 'member');
	protected $has_many = array('invoice_items');

	/**
	 * Returns type in string from integer
	 * 
	 * @author Jan Dubina
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
	 * Return translated invoice type array
	 *
	 * @author Jan Dubina
	 * @return array
	 */
	public static function types()
	{
		return array
		(
			self::TYPE_ISSUED => __('Issued'),
			self::TYPE_RECEIVED => __('Received')
		);
	}
	
	/**
	 * Returns ORM_Iterator of all invoices
	 * 
	 * @author Michal Kliment
	 * @param $limit_from
	 * @param $limit_results
	 * @param $order_by
	 * @param $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_all_invoices(
			$limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'ASC', 
			$filter_sql = '')
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		$where = '';

		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		$join_phone = '';
		$select_phone = '';
		
		$join_email = '';
		$select_email = '';
		
		//HACK FOR IMPROVING PERFORMANCE
		if (strpos($filter_sql, '`iv`.`phone` LIKE '))
		{
			$join_phone = "
					LEFT JOIN
					(
						SELECT member_id, phone
						FROM users u 
						RIGHT JOIN 
						(
							SELECT user_id, value AS phone
							FROM users_contacts uc 
							LEFT JOIN contacts c ON uc.contact_id = c.id
							WHERE c.type = ?
						) c ON u.id = c.user_id 
						GROUP BY member_id
					) cp ON iv.member_id = cp.member_id
					";
			$select_phone = ", IF(iv.member_id IS NULL,iv.phone_number, cp.phone) AS phone";
		}
		
		if (strpos($filter_sql, '`iv`.`email` LIKE '))
		{
			$join_email = "
					LEFT JOIN
					(
						SELECT member_id, email
						FROM users u 
						RIGHT JOIN 
						(
							SELECT user_id, value AS email
							FROM users_contacts uc 
							LEFT JOIN contacts c ON uc.contact_id = c.id
							WHERE c.type = ?
						) c ON u.id = c.user_id 
						GROUP BY member_id
					) cm ON iv.member_id = cm.member_id
					";
			$select_email = ",IF(iv.member_id IS NULL,iv.email, cm.email) AS email";
		}
		
		// query
		return $this->db->query("
				SELECT * 
				FROM
				(
					SELECT iv.id, 
						IF(iv.member_id IS NULL, partner_company, NULL) AS company,
						IF(iv.member_id IS NULL, partner_name, m.name) AS partner,
						IF(iv.member_id IS NULL, partner_street, m.street) AS street,
						IF(iv.member_id IS NULL, partner_street_number, m.street_number) 
						AS street_number,
						IF(iv.member_id IS NULL, partner_town, m.town) AS town,
						IF(iv.member_id IS NULL, partner_zip_code, m.zip_code) AS zip_code,
						IF(iv.member_id IS NULL, partner_country, m.country_name) AS country,
						IF(iv.member_id IS NULL, iv.organization_identifier, m.organization_identifier)
						AS organization_identifier,
						IF(iv.member_id IS NULL, iv.vat_organization_identifier, m.vat_organization_identifier)
						AS vat_organization_identifier,
						IF(iv.member_id IS NULL,iv.account_nr, m.account_nr) AS account_nr,
						invoice_nr, invoice_type, var_sym, con_sym, date_inv, date_due, 
						date_vat, iv.vat, order_nr, currency, note,
						COUNT(it.id) AS comments_count,
						GROUP_CONCAT(DISTINCT it.name ORDER BY it.name SEPARATOR '\n') AS comments,
						SUM(it.price * it.quantity) AS price, 
						SUM(it.price * it.quantity * (1 + it.vat)) AS price_vat
						$select_phone
						$select_email
					FROM invoices iv
					LEFT JOIN 
					(
						SELECT m.id, m.name, m.organization_identifier,
							m.vat_organization_identifier, 
							ap.street, ap.street_number, ap.town, 
							ap.zip_code, ap.country_name, account_nr
						FROM members m
						LEFT JOIN 
						(
							SELECT member_id,
							IF(account_nr<>'' AND bank_nr<>'', CONCAT(account_nr,'/',bank_nr), '') as account_nr
							FROM bank_accounts
							GROUP BY member_id
						) ba ON ba.member_id = m.id
						LEFT JOIN 
						(
							SELECT ap.id, s.street, street_number, 
								t.town, t.zip_code, c.country_name
							FROM address_points ap
							LEFT JOIN countries c ON ap.country_id = c.id
							LEFT JOIN towns t ON ap.town_id = t.id
							LEFT JOIN streets s ON ap.street_id = s.id
						) ap ON m.address_point_id = ap.id
					) m ON iv.member_id = m.id 
					LEFT JOIN invoice_items it ON iv.id = it.invoice_id
					$join_phone
					$join_email
					GROUP BY iv.id
				) iv $where
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
			", array(
					Contact_Model::TYPE_EMAIL,
					Contact_Model::TYPE_PHONE
			));
	}
	
	/**
	 * Returns ORM_Iterator of all invoices
	 * 
	 * @author Michal Kliment
	 * @param $limit_from
	 * @param $limit_results
	 * @param $order_by
	 * @param $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_all_invoices_export($filter_sql = '')
	{
		$where = '';

		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		// query
		return $this->db->query("
				SELECT * 
				FROM
				(
					SELECT iv.id, 
						IF(iv.member_id IS NULL, partner_company, NULL) AS company,
						IF(iv.member_id IS NULL, partner_name, m.name) AS partner,
						IF(iv.member_id IS NULL, partner_street, m.street) AS street,
						IF(iv.member_id IS NULL, partner_street_number, m.street_number) 
						AS street_number,
						IF(iv.member_id IS NULL, partner_town, m.town) AS town,
						IF(iv.member_id IS NULL, partner_zip_code, m.zip_code) AS zip_code,
						IF(iv.member_id IS NULL, partner_country, m.country_name) AS country,
						IF(iv.member_id IS NULL, iv.organization_identifier, m.organization_identifier)
						AS organization_identifier,
						IF(iv.member_id IS NULL, iv.vat_organization_identifier, m.vat_organization_identifier)
						AS vat_organization_identifier,
						IF(iv.member_id IS NULL,iv.account_nr, m.account_nr) AS account_nr,
						IF(iv.member_id IS NULL,iv.email, cm.email) AS email,
						IF(iv.member_id IS NULL,iv.phone_number, cp.phone) AS phone,
						invoice_nr, invoice_type, var_sym, con_sym, date_inv, date_due, 
						date_vat, iv.vat, order_nr, currency, note,
						COUNT(it.id) AS comments_count,
						GROUP_CONCAT(DISTINCT it.name ORDER BY it.name SEPARATOR '\n') AS comments,
						SUM(it.price * it.quantity) AS price, 
						SUM(it.price * it.quantity * (1 + it.vat)) AS price_vat
					FROM invoices iv
					LEFT JOIN 
					(
						SELECT m.id, m.name, m.organization_identifier, 
							m.vat_organization_identifier, 
							ap.street, ap.street_number, ap.town, 
							ap.zip_code, ap.country_name, account_nr
						FROM members m
						LEFT JOIN 
						(
							SELECT member_id,
							IF(account_nr<>'' AND bank_nr<>'', CONCAT(account_nr,'/',bank_nr), '') as account_nr
							FROM bank_accounts
							GROUP BY member_id
						) ba ON ba.member_id = m.id
						LEFT JOIN 
						(
							SELECT ap.id, s.street, street_number, 
								t.town, t.zip_code, c.country_name
							FROM address_points ap
							LEFT JOIN countries c ON ap.country_id = c.id
							LEFT JOIN towns t ON ap.town_id = t.id
							LEFT JOIN streets s ON ap.street_id = s.id
						) ap ON m.address_point_id = ap.id
					) m ON iv.member_id = m.id 
					LEFT JOIN
					(
						SELECT member_id, email
						FROM users u 
						RIGHT JOIN 
						(
							SELECT user_id, value AS email
							FROM users_contacts uc 
							LEFT JOIN contacts c ON uc.contact_id = c.id
							WHERE c.type = ?
						) c ON u.id = c.user_id 
						GROUP BY member_id
					) cm ON iv.member_id = cm.member_id
					LEFT JOIN
					(
						SELECT member_id, phone
						FROM users u 
						RIGHT JOIN 
						(
							SELECT user_id, value AS phone
							FROM users_contacts uc 
							LEFT JOIN contacts c ON uc.contact_id = c.id
							WHERE c.type = ?
						) c ON u.id = c.user_id 
						GROUP BY member_id
					) cp ON iv.member_id = cp.member_id
					LEFT JOIN invoice_items it ON iv.id = it.invoice_id
					GROUP BY iv.id
				) iv $where
				ORDER BY iv.id
			", array(
					Contact_Model::TYPE_EMAIL,
					Contact_Model::TYPE_PHONE
			));
	}
	
	/**
	 * Function counts all invoices.
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
	public function count_all_invoices($filter_sql = "")
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
		
		$join_phone = '';
		$select_phone = '';
		
		$join_email = '';
		$select_email = '';
		
		//HACK FOR IMPROVING PERFORMANCE
		if (strpos($filter_sql, '`iv`.`phone` LIKE '))
		{
			$join_phone = "
					LEFT JOIN
					(
						SELECT member_id, phone
						FROM users u 
						RIGHT JOIN 
						(
							SELECT user_id, value AS phone
							FROM users_contacts uc 
							LEFT JOIN contacts c ON uc.contact_id = c.id
							WHERE c.type = ?
						) c ON u.id = c.user_id 
						GROUP BY member_id
					) cp ON iv.member_id = cp.member_id
					";
			$select_phone = ", IF(iv.member_id IS NULL,iv.phone_number, cp.phone) AS phone";
		}
		
		if (strpos($filter_sql, '`iv`.`email` LIKE '))
		{
			$join_email = "
					LEFT JOIN
					(
						SELECT member_id, email
						FROM users u 
						RIGHT JOIN 
						(
							SELECT user_id, value AS email
							FROM users_contacts uc 
							LEFT JOIN contacts c ON uc.contact_id = c.id
							WHERE c.type = ?
						) c ON u.id = c.user_id 
						GROUP BY member_id
					) cm ON iv.member_id = cm.member_id
					";
			$select_email = ",IF(iv.member_id IS NULL,iv.email, cm.email) AS email";
		}
		
		// query
		return $this->db->query("
				SELECT COUNT(*) AS total 
				FROM
				(
					SELECT iv.id,
						IF(iv.member_id IS NULL, partner_company, NULL) AS company,
						IF(iv.member_id IS NULL, partner_name, m.name) AS partner,
						IF(iv.member_id IS NULL, partner_street, m.street) AS street,
						IF(iv.member_id IS NULL, partner_street_number, m.street_number) 
						AS street_number,
						IF(iv.member_id IS NULL, partner_town, m.town) AS town,
						IF(iv.member_id IS NULL, partner_zip_code, m.zip_code) AS zip_code,
						IF(iv.member_id IS NULL, partner_country, m.country_name) AS country,
						IF(iv.member_id IS NULL, iv.organization_identifier, m.organization_identifier)
						AS organization_identifier,
						IF(iv.member_id IS NULL, iv.vat_organization_identifier, m.vat_organization_identifier)
						AS vat_organization_identifier,
						IF(iv.member_id IS NULL,iv.account_nr, m.account_nr) AS account_nr,
						invoice_nr, invoice_type, var_sym, con_sym, date_inv, date_due, 
						date_vat, iv.vat, order_nr, currency, note,
						SUM(it.price * it.quantity) AS price, 
						SUM(it.price * it.quantity * (1 + it.vat)) AS price_vat
						$select_phone
						$select_email
					FROM invoices iv
					LEFT JOIN 
					(
						SELECT m.id, m.name, m.organization_identifier, 
							m.vat_organization_identifier,
							ap.street, ap.street_number, ap.town, 
							ap.zip_code, ap.country_name, account_nr
						FROM members m
						LEFT JOIN 
						(
							SELECT member_id,
							IF(account_nr<>'' AND bank_nr<>'', CONCAT(account_nr,'/',bank_nr), '') as account_nr
							FROM bank_accounts
							GROUP BY member_id
						) ba ON ba.member_id = m.id
						LEFT JOIN 
						(
							SELECT ap.id, s.street, street_number, 
								t.town, t.zip_code, c.country_name
							FROM address_points ap
							LEFT JOIN countries c ON ap.country_id = c.id
							LEFT JOIN towns t ON ap.town_id = t.id
							LEFT JOIN streets s ON ap.street_id = s.id
						) ap ON m.address_point_id = ap.id
					) m ON iv.member_id = m.id 
					LEFT JOIN invoice_items it ON iv.id = it.invoice_id
					$join_phone
					$join_email
					GROUP BY iv.id
				) iv $where
			", array(
					Contact_Model::TYPE_EMAIL,
					Contact_Model::TYPE_PHONE
			))->current()->total;
	}
	
	/**
	 * Returns all partner names by given like
	 * 
	 * @author Jan Dubina
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_names ($like)
	{
		return $this->db->query("
			SELECT DISTINCT partner FROM 
			(
				SELECT IF(i.member_id IS NULL, partner_name, m.name) AS partner
				FROM invoices i
				LEFT JOIN
				(
					SELECT id, name
					FROM members
				) m ON m.id = i.member_id
			) i
			WHERE partner LIKE ".$this->db->escape("%$like%"));
	}
	
	/**
	 * Returns all streets by given like
	 * 
	 * @author Jan Dubina
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_streets ($like)
	{
		return $this->db->query("
			SELECT DISTINCT street FROM 
			(
				SELECT IF(i.member_id IS NULL, partner_street, m.street) AS street
				FROM invoices i
				LEFT JOIN
				(
					SELECT m.id, ap.street
					FROM members m
					LEFT JOIN 
					(
						SELECT ap.id, s.street
						FROM address_points ap
						LEFT JOIN 
						(
							SELECT id, street
							FROM streets
						) s ON s.id = ap.street_id
					) ap ON ap.id = m.address_point_id
				) m ON m.id = i.member_id
			) i
			WHERE street IS NOT NULL AND street LIKE ".$this->db->escape("%$like%"));
	}
	
	/**
	 * Returns all towns by given like
	 * 
	 * @author Jan Dubina
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_towns ($like)
	{
		return $this->db->query("
			SELECT DISTINCT town FROM 
			(
				SELECT IF(i.member_id IS NULL, partner_town, m.town) AS town
				FROM invoices i
				LEFT JOIN
				(
					SELECT m.id, ap.town
					FROM members m
					LEFT JOIN 
					(
						SELECT ap.id, t.town
						FROM address_points ap
						LEFT JOIN 
						(
							SELECT id, town
							FROM towns
						) t ON t.id = ap.town_id
					) ap ON ap.id = m.address_point_id
				) m ON m.id = i.member_id
			) i
			WHERE town IS NOT NULL AND town LIKE ".$this->db->escape("%$like%"));
	}
	
	/**
	 * Returns all zip codes by given like
	 * 
	 * @author Jan Dubina
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_zip_codes ($like)
	{
		return $this->db->query("
			SELECT DISTINCT zip_code FROM 
			(
				SELECT 
					IF(i.member_id IS NULL, partner_zip_code, m.zip_code) AS zip_code
				FROM invoices i
				LEFT JOIN
				(
					SELECT m.id, ap.zip_code
					FROM members m
					LEFT JOIN 
					(
						SELECT ap.id, t.zip_code
						FROM address_points ap
						LEFT JOIN 
						(
							SELECT id, zip_code
							FROM towns
						) t ON t.id = ap.town_id
					) ap ON ap.id = m.address_point_id
				) m ON m.id = i.member_id
			) i
			WHERE zip_code IS NOT NULL AND zip_code LIKE ".$this->db->escape("%$like%"));
	}
	
	/**
	 * Returns all street numbers by given like
	 * 
	 * @author Jan Dubina
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_street_numbers ($like)
	{
		return $this->db->query("
			SELECT DISTINCT street_number FROM 
			(
				SELECT 
					IF(i.member_id IS NULL, partner_street_number, m.street_number) AS street_number
				FROM invoices i
				LEFT JOIN
				(
					SELECT m.id, ap.street_number
					FROM members m
					LEFT JOIN 
					(
						SELECT ap.id, ap.street_number
						FROM address_points ap
					) ap ON ap.id = m.address_point_id
				) m ON m.id = i.member_id
			) i
			WHERE street_number IS NOT NULL AND street_number LIKE ".$this->db->escape("%$like%"));
	}
	
	/**
	 * Returns all countries by given like
	 * 
	 * @author Jan Dubina
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_countries ($like)
	{
		return $this->db->query("
			SELECT DISTINCT country FROM 
			(
				SELECT 
					IF(i.member_id IS NULL, partner_country, m.country_name) AS country
				FROM invoices i
				LEFT JOIN
				(
					SELECT m.id, ap.country_name
					FROM members m
					LEFT JOIN 
					(
						SELECT ap.id, c.country_name
						FROM address_points ap
						LEFT JOIN 
						(
							SELECT id, country_name
							FROM countries
						) c ON c.id = ap.town_id
					) ap ON ap.id = m.address_point_id
				) m ON m.id = i.member_id
			) i
			WHERE country IS NOT NULL AND country LIKE ".$this->db->escape("%$like%"));
	}
	
	/**
	 * Returns all organization ids by given like
	 * 
	 * @author Jan Dubina
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_organization_ids ($like)
	{
		return $this->db->query("
			SELECT DISTINCT organization_identifier FROM 
			(
				SELECT 
					IF(i.member_id IS NULL, i.organization_identifier, m.organization_identifier) 
					AS organization_identifier
				FROM invoices i
				LEFT JOIN
				(
					SELECT m.id, m.organization_identifier
					FROM members m
				) m ON m.id = i.member_id
			) i
			WHERE organization_identifier IS NOT NULL AND organization_identifier LIKE " . 
				$this->db->escape("%$like%"));
	}
	
	/**
	 * Returns all VAT organization ids by given like
	 * 
	 * @author Michal Kliment
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_vat_organization_ids ($like)
	{
		return $this->db->query("
			SELECT DISTINCT vat_organization_identifier FROM 
			(
				SELECT 
					IF(i.member_id IS NULL, i.vat_organization_identifier, m.vat_organization_identifier) 
					AS vat_organization_identifier
				FROM invoices i
				LEFT JOIN
				(
					SELECT m.id, m.vat_organization_identifier
					FROM members m
				) m ON m.id = i.member_id
			) i
			WHERE vat_organization_identifier IS NOT NULL AND vat_organization_identifier LIKE " . 
				$this->db->escape("%$like%"));
	}
	
	/**
	 * Returns all account numbers by given like
	 * 
	 * @author Jan Dubina
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_account_nrs ($like)
	{
		return $this->db->query("
			SELECT DISTINCT account_nr FROM 
			(
				SELECT 
					IF(i.account_nr IS NOT NULL, i.account_nr, m.account_nr) 
					AS account_nr
				FROM invoices i
				LEFT JOIN
				(
					SELECT m.id, ba.account_nr
					FROM members m
					LEFT JOIN 
					(
						SELECT member_id,
							CONCAT(account_nr,'/',bank_nr) as account_nr
						FROM bank_accounts
						GROUP BY member_id
					) ba ON ba.member_id = m.id
				) m ON m.id = i.member_id
			) i
			WHERE account_nr IS NOT NULL AND account_nr LIKE " . 
				$this->db->escape("%$like%"));
	}
	
	/**
	 * Returns all emails by given like
	 * 
	 * @author Jan Dubina
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_emails ($like)
	{
		return $this->db->query("
			SELECT DISTINCT email FROM 
			(
				SELECT 
					IF(i.member_id IS NULL, i.email, m.email) 
					AS email
				FROM invoices i
				LEFT JOIN
				(
					SELECT m.id, cm.email
					FROM members m
					LEFT JOIN
					(
						SELECT member_id, uc.email
						FROM users u 
						RIGHT JOIN 
						(
							SELECT user_id, c.email
							FROM users_contacts uc 
							RIGHT JOIN 
							(
								SELECT id, value as email
								FROM contacts
								WHERE type = ?
							) c ON uc.contact_id = c.id
						) uc ON u.id = uc.user_id 
						GROUP BY member_id
					) cm ON m.id = cm.member_id
				) m ON m.id = i.member_id
			) i
			WHERE email IS NOT NULL AND email LIKE " . 
				$this->db->escape("%$like%"),
				array(Contact_Model::TYPE_EMAIL));
	}
	
	/**
	 * Returns all phone numbers by given like
	 * 
	 * @author Jan Dubina
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_phone_numbers ($like)
	{
		return $this->db->query("
			SELECT DISTINCT phone_number FROM 
			(
				SELECT 
					IF(i.member_id IS NULL, i.phone_number, m.phone_number) 
					AS phone_number
				FROM invoices i
				LEFT JOIN
				(
					SELECT m.id, cm.phone_number
					FROM members m
					LEFT JOIN
					(
						SELECT member_id, uc.phone_number
						FROM users u 
						RIGHT JOIN 
						(
							SELECT user_id, c.phone_number
							FROM users_contacts uc 
							RIGHT JOIN 
							(
								SELECT id, value as phone_number
								FROM contacts
								WHERE type = ?
							) c ON uc.contact_id = c.id
						) uc ON u.id = uc.user_id 
						GROUP BY member_id
					) cm ON m.id = cm.member_id
				) m ON m.id = i.member_id
			) i
			WHERE phone_number IS NOT NULL AND phone_number LIKE " . 
				$this->db->escape("%$like%"),
				array(Contact_Model::TYPE_PHONE));
	}
	
	/**
	 * Returns all companies by given like
	 * 
	 * @author Jan Dubina
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_companies ($like)
	{
		return $this->db->query("
			SELECT DISTINCT company FROM 
			(
				SELECT partner_company AS company
				FROM invoices
				WHERE partner_company IS NOT NULL
			) i
			WHERE company LIKE ".$this->db->escape("%$like%"));
	}
}
