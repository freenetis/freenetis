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
 * Town is part of address point, each contains many streets.
 * 
 * @package Model
 * 
 * @property string $town
 * @property string $quarter
 * @property integer $zip_code
 * @property blob $gps
 * @property ORM_Iterator $address_points
 * @property ORM_Iterator $streets
 */
class Town_Model extends ORM
{
	protected $has_many = array('address_points', 'streets');
	
	/**
	 * @author Ondrej Fibich
	 * @return string reprezentation of town
	 */
	public function __toString()
	{
		if (!$this->id)
			return '';
		
		return (
				$this->town . ($this->quarter ? ' - ' . $this->quarter : '') . 
				', ' . $this->zip_code
		);
	}

	/**
	 * Check if town exists
	 * Exists - return it
	 * Not exists - create it a return it
	 * 
	 * @author Michal Kliment
	 * @param string $zip_code	zip code (if set to FALSE, do not search by ZIP code)
	 * @param string $town		town
	 * @param string $quarter	quarter
	 * @return Town_Model
	 */
	public function get_town($zip_code = NULL, $town = NULL, $quarter = NULL)
	{
		if ($zip_code === FALSE) // do not search
		{
			$zip_code_where = '';
		}
		else if (empty($zip_code))
		{
			$zip_code_where = ' AND (zip_code IS NULL OR LENGTH(zip_code) = 0)';
		}
		else
		{
			$zip_code_where = ' AND LOWER(zip_code) = ' . $this->db->escape(strtolower($zip_code));
		}
		
		if (empty($quarter))
		{
			$quarter_where = ' AND (quarter IS NULL OR LENGTH(quarter) = 0)';
		}
		else
		{
			$quarter_where = ' AND LOWER(quarter) = ' . $this->db->escape(strtolower($quarter));
		}
		
		$towns = $this->db->query("
			SELECT *
			FROM towns
			WHERE LOWER(town) = ? $zip_code_where $quarter_where
		", strtolower($town));

		if (count($towns) == 0)
		{
			$this->clear();
			$this->zip_code = $zip_code;
			$this->town = $town;
			$this->quarter = $quarter;
			$this->save();
			return $this;
		}
		else if (count($towns) == 1)
		{
			return $towns->current();
		}
		else
		{
			return NULL;
		}
	}

	/**
	 *
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_all_towns(
			$limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'asc',
			$filter_values = array())
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
				SELECT t.id, t.town, t.quarter, t.zip_code
				FROM towns t
				ORDER BY $order_by $order_by_direction
				LIMIT ".intval($limit_from).", ".intval($limit_results)."
		");
	}
	
	/**
	 * Gets list of towns for select box
	 *
	 * @return array[string]
	 */
	public function select_list_with_quater()
	{
		$concat = "CONCAT(
			COALESCE(town, ''),
			COALESCE(IF(
				quarter IS NULL OR quarter LIKE '',
				NULL, CONCAT(' - ', quarter)
			), ''), ', ', zip_code
		)";
		
		return $this->select_list('id', $concat);
	}
	
}
