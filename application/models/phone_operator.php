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
 * Phone operator is operator base on defined country with many phone prefixes.
 *
 * @author Ondřej Fibich
 * @package Model
 * 
 * @property integer $id
 * @property integer $country_id
 * @property Country_Model $country
 * @property string $name
 * @property integer $phone_number_length
 * @property boolean $sms_enabled
 * @property ORM_Iterator $phone_operator_prefixes
 */
class Phone_operator_Model extends ORM
{
    protected $belongs_to = array('country');
	protected $has_many = array('phone_operator_prefixes');
	
	/**
	 * Check if SMS are enavled for given phone number
	 *
	 * @staticvar Country_Model $country_m
	 * @staticvar Phone_operator_prefix_Model $prefix_m
	 * @staticvar Phone_operator_Model $operator_m
	 * @param string $phone_number	Phone number with all prefixes
	 * @return boolean
	 */
	public static function is_sms_enabled_for($phone_number)
	{
		// static vars and loading
		static $country_m, $prefix_m, $operator_m;
		
		if (empty($country_m))
		{
			$country_m = new Country_Model();
			$prefix_m = new Phone_operator_prefix_Model();
			$operator_m = new Phone_operator_Model();
		}
		
		// trim number
		$number = trim($phone_number);
		
		// valid number?
		if (empty($number) || !is_numeric($number))
		{
			return FALSE;
		}
		
		// find country
		if (($country = $country_m->find_phone_country($number)) == FALSE)
		{
			return FALSE;
		}
		
		// get number without country prefix
		$number = substr($number, mb_strlen($country->country_code));
		
		// search prefix
		if (($prefix = $prefix_m->find_phone_operator_prefix($number, $country->id)) == FALSE)
		{
			return FALSE;
		}
		
		// load operator
		$operator_m->find($prefix->phone_operator_id);
		
		// get number without operator prefix
		$number = substr($number, mb_strlen($prefix->prefix));
		
		// same length of number as supposed to be?
		return (mb_strlen($number) == $operator_m->phone_number_length);
	}
	
	/**
	 * Gets all records
	 * 
	 * @return Mysql_Result
	 */
	public function get_all()
	{
		return $this->db->query("
			SELECT o.*, c.country_name AS country,
				GROUP_CONCAT(p.prefix ORDER BY prefix SEPARATOR ', ') AS prefixes
			FROM phone_operators o
			LEFT JOIN countries c ON c.id = o.country_id
			LEFT JOIN phone_operator_prefixes p ON p.phone_operator_id = o.id
			GROUP BY o.id
			ORDER BY o.name
		");
	}
	
	/**
	 * Gets grouped prefixes of phone operators
	 *
	 * @param integer $phone_operator_id
	 * @return string
	 */
	public function get_grouped_prefixes($phone_operator_id = NULL)
	{
		if (empty($phone_operator_id) && $this->id)
		{
			$phone_operator_id = $this->id;
		}
		
		return $this->db->query("
			SELECT GROUP_CONCAT(prefix ORDER BY prefix SEPARATOR ';') AS prefixes
			FROM phone_operator_prefixes
			WHERE phone_operator_id = ?
		", $phone_operator_id)->current()->prefixes;
	}
}
