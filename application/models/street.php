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
 * Street is part of address point, each street belongs to town.
 * 
 * @author Michal Kliment, Ondřej Fibich
 * @package Model
 * 
 * @property integer $id
 * @property integer $town_id
 * @property Town_Model $town
 * @property string $street
 * @property blob $gps
 * @property ORM_Iterator $address_points
 */
class Street_Model extends ORM
{
	protected $has_many = array('address_points');
	protected $belongs_to = array('town', 'street');

	/**
	 * Check if street exists
	 * Exists - return it
	 * Not exists - create it a return it
	 * 
	 * @author Michal Kliment
	 * @param string $street	name of street
	 * @param integer $town_id	ID of town which owns street
	 * @return Database_Result	street object
	 */
	public function get_street($street = NULL, $town_id = NULL)
	{
		$streets = $this->where(array
		(
			'street'	=> $street,
			'town_id'	=> $town_id
		))->find_all();
		
		if (count($streets) == 0)
		{
			$this->clear();
			$this->street = $street;
			$this->town_id = $town_id;
			$this->save();
			return $this;
		}
		else if (count($streets) == 1)
		{
			return $streets->current();
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * Get all streets
	 *
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_all_streets(
			$limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'asc', $filter_values = array())
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT s.id, s.street, s.town_id, CONCAT(
						COALESCE(town, ''),
						COALESCE(IF(
							quarter IS NULL OR quarter LIKE '',
							NULL, CONCAT(' - ', quarter)
						), ''), ', ', zip_code
					) AS town
				FROM streets s
				LEFT JOIN towns t ON t.id = s.town_id
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT ".intval($limit_from).", ".intval($limit_results)."
		");
	}
}
