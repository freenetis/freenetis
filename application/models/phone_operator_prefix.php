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
 * Phone operator prefix is phone number prefix for operator.
 *
 * @author Ondřej Fibich
 * @package Model
 * 
 * @property integer $id
 * @property integer $phone_operator_id
 * @property Phone_operator_Model $phone_operator
 * @property string $prefix
 */
class Phone_operator_prefix_Model extends ORM
{
    protected $belongs_to = array('phone_operator');
	
	/**
	 * Search for phone prefix in phone number without country prefix.
	 * 
	 * @author Ondřej Fibich
	 * @param string $phone_number  Telephone number with operator prefix
	 * @param integer $country_id	Country ID of prefix
	 * @return mixed				Country ID or FALSE
	 */
	public function find_phone_operator_prefix($phone_number, $country_id)
	{
		$query = $this->db->query("
				SELECT *
				FROM phone_operator_prefixes p
				LEFT JOIN phone_operators o ON o.id = p.phone_operator_id
				WHERE p.prefix != '' AND
					? LIKE CONCAT(p.prefix, '%') AND
					o.country_id = ?
				LIMIT 1
		", $phone_number, $country_id);

		return ($query->count() == 1) ? $query->current() : '';
	}
}
