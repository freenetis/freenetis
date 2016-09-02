<?php

defined('SYSPATH') or die('No direct script access.');
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
 * Country is part of address point.
 * Country defined phone prefixes in country_code.
 * 
 * @author Ondrej Fibich
 * @package Model
 *
 * @property integer $id
 * @property string $country_name
 * @property string $country_iso
 * @property string $country_code
 * @property ORM_Iterator $contacts
 * @property ORM_Iterator $address_points
 * @property ORM_Iterator $phone_operators
 */
class Country_Model extends ORM
{
	protected $has_many = array('address_points', 'phone_operators');
	protected $has_and_belongs_to_many = array('contacts');

	/**
	 * Gets list of countries for combo box
	 * 
	 * @return array
	 */
	public function select_country_list()
	{
		$concat = 'CONCAT(country_code, "\t", country_name)';
		
		return $this->where(array
		(
			'country_code IS NOT'	=> NULL,
			'country_code !='		=> '',
			'enabled'				=> 1
		))->select_list('id', $concat, 'country_code');
	}
	
	/**
	 * Gets list of countries for combo box only with country code
	 * 
	 * @return array
	 */
	public function select_country_code_list()
	{
		return $this->where(array
		(
			'country_code IS NOT'	=> NULL,
			'country_code !='		=> '',
			'enabled'				=> 1
		))->select_list('id', 'country_code', 'country_code');
	}

	/**
	 * Finds country by area (in VoIP)
	 *
	 * @author Michal Kliment
	 * @param string $area
	 * @return MySQL_Result object
	 */
	public function find_country_by_area($area)
	{
		$result = $this->db->query("
				SELECT * FROM countries
				WHERE ? LIKE CONCAT( country_name, '%' )
				LIMIT 0,1
		", array($area));
		
		return ($result && $result->count()) ? $result->current() : NULL;
	}
	
	/**
	 * Search for phone prefix in country table.
	 * 
	 * @author Ondřej Fibich
	 * @param string $phone_number  Telephone number with prefix
	 * @return mixed				Country or FALSE
	 */
	public function find_phone_country($phone_number)
	{
		$query = $this->db->query("
				SELECT *
				FROM countries
				WHERE country_code != '' AND ? LIKE CONCAT( country_code, '%' ) LIMIT 1
		", $phone_number);

		return ($query->count() == 1) ? $query->current() : '';
	}
	
	/**
	 * Search for phone prefix in country table.
	 * 
	 * @author Ondřej Fibich
	 * @param string $phone_number  Telephone number with prefix
	 * @return mixed				Country ID or FALSE
	 */
	public function find_phone_country_id($phone_number)
	{
		$query = $this->db->query("
				SELECT id
				FROM countries
				WHERE country_code != '' AND ? LIKE CONCAT( country_code, '%' ) LIMIT 1
		", $phone_number);

		return ($query->count() == 1) ? $query->current()->id : '';
	}

	/**
	 * Search for phone prefix in country table.
	 * 
	 * @author Ondřej Fibich
	 * @param string $phone_number  Telephone number with prefix
	 * @return integer				Country code or zero
	 */
	public function find_phone_country_code($phone_number)
	{
		$query = $this->db->query("
				SELECT country_code
				FROM `countries`
				WHERE country_code != '' AND ? LIKE CONCAT( country_code, '%' ) LIMIT 1
		", $phone_number);

		return ($query->count() == 1) ? $query->current()->country_code : '';
	}

	/**
	 * Enable countries to use
	 * 
	 * @author David Raška
	 * @param array $countries
	 */
	public function enable_countries($countries)
	{
		$result = $this->db->query("
				UPDATE countries
				SET enabled=0;
		");
		
		if ($result)
		{
			if (is_array($countries) && count($countries) > 0)
			{
				$countries = array_map('intval', $countries);

				$in = implode(', ', $countries);


				$result = $this->db->query("
						UPDATE countries
						SET enabled=1
						WHERE id IN ($in)
				");

				return (bool)$result;
			}
			else
			{
				return TRUE;
			}
		}
		else
		{
			return FALSE;
		}
	}
}
