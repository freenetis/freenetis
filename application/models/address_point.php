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
 * Address point specified point on map with address and GPS.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property Country_Model $country
 * @property integer $country_id
 * @property Town_Model $town
 * @property integer $town_id
 * @property Street_Model $street
 * @property integer $street_id
 * @property integer $street_number
 * @property mixed $gps
 * @property ORM_Iterator $members
 * @property ORM_Iterator $devices
 */
class Address_point_Model extends ORM
{
	protected $has_many = array('members', 'devices');
	protected $belongs_to = array('town', 'street', 'country');
	
	/**
	 * @author Ondrej Fibich
	 * @return	string reprezentation of address point
	 */
	public function __toString()
	{
		if (!$this->id)
			return '';
		
		$str = '';
		
		if ($this->name)
		{
			$str .= '&bdquo;' . $this->name . '&ldquo;, ';
		}
		
		if ($this->street_id && $this->street_number)
		{
			$str .= $this->street->street.' '.$this->street_number.', ';
		}
		else if ($this->street_id)
		{
			$str .= $this->street->street.', ';
		}
		else if ($this->street_number)
		{
			$str .= $this->street_number.', ';
		}
		
		$str .= $this->town->town;
		$str .= ($this->town->quarter!='') ? '-'.$this->town->quarter.', ' : ', ';
		$str .= $this->town->zip_code;
		$str .= ', ' . $this->country->country_name;
		
		return $str;
	}

	/**
	 * Check if address point exists
	 * 
	 * Exists		return it with correct ID
	 * Not exists	create it and set with given values (without GPS) and
	 *				return it with zero ID
	 * 
	 * @author Michal Kliment
	 * @param integer $country_id
	 * @param integer $town_id
	 * @param integer $street_id
	 * @param integer $street_number
	 * @param float $gpsx
	 * @param float $gpsy
	 * @return Address_point_Model
	 */
	public function get_address_point(
			$country_id = NULL, $town_id = NULL,
			$street_id = NULL, $street_number = NULL,
			$gpsx = NULL, $gpsy = NULL)
	{
		$where = "WHERE ";
		// country
		if ($country_id == 0 || empty($country_id))
		{
			$where .= "country_id IS NULL ";
		}
		else
		{
			$where .= "country_id = " . $this->db->escape($country_id);
		}
		// town
		if ($town_id == 0 || empty($town_id))
		{
			$where .= " AND town_id IS NULL ";
		}
		else
		{
			$where .= " AND town_id = " . $this->db->escape($town_id);
		}
		// street
		if ($street_id == 0 || empty($street_id))
		{
			$where .= " AND street_id IS NULL ";
		}
		else
		{
			$where .= " AND street_id = " . $this->db->escape($street_id);
		}
		// street number
		if ($street_number == 0 || empty($street_number))
		{
			$where .= " AND street_number IS NULL ";
		}
		else
		{
			$where .= " AND street_number = " . $this->db->escape($street_number);
		}
		// GPS coordinates can be affected by round errors => make interval for search
		if (!empty($gpsx) && !empty($gpsy))
		{
			$where .= " AND X(gps) > " . $this->db->escape($gpsx - 0.000002)
					. " AND X(gps) < " . $this->db->escape($gpsx + 0.000002)
					. " AND Y(gps) > " . $this->db->escape($gpsy - 0.000001)
					. " AND Y(gps) < " . $this->db->escape($gpsy + 0.000001);
		}
		else
		{
			$where .= " AND (gps IS NULL OR gps LIKE '')";
		}
		
		// query
		$result = $this->db->query("
				SELECT id
				FROM address_points
				$where
		");
		
		// founded?
		if ($result && $result->count())
		{
			return new Address_point_Model($result->current()->id);
		}
		
		/* @var $ap Address_point_Model */
		$ap = new Address_point_Model();
		$ap->country_id = $country_id;
		$ap->town_id = $town_id;
		$ap->street_id = $street_id;
		$ap->street_number = $street_number;
		
		return $ap;
	}

	/**
	 * Returns all address points
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $member_id
	 * @param string $filter_sql
	 * @return Mysql_Result
	 */
	public function get_all_address_points(
			$limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'asc',
			$member_id = NULL, $filter_sql = '')
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		// filter
		$where = '';
		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		$member_where = '';
		if ($member_id && is_numeric($member_id))
			$member_where = "WHERE member_id = ".intval($member_id);
		
		// query
		return $this->db->query("
			SELECT * FROM
			(
				SELECT
						ap.id,
						ap.name,
						s.street,
						ap.street_number,
						t.town, t.quarter,
						t.zip_code,
						IFNULL(CONCAT(X(ap.gps), ' ', Y(ap.gps)),'') AS gps,
						c.country_name,
						COUNT(ap.id) AS items_count,
						GROUP_CONCAT(item_name SEPARATOR ', \n') AS items_count_title
				FROM
				(
					SELECT ap.*, m.id AS member_id, CONCAT(?,' ',m.name) AS item_name
					FROM address_points ap
					JOIN members m ON m.address_point_id = ap.id
					UNION ALL
					SELECT ap.*, md.member_id, CONCAT(?,' ',m.name) AS item_name
					FROM address_points ap
					JOIN members_domiciles md ON md.address_point_id = ap.id
					JOIN members m ON md.member_id = m.id
					UNION ALL
					SELECT ap.*, u.member_id, CONCAT(?,' ',d.name) AS item_name
					FROM address_points ap
					JOIN devices d ON d.address_point_id = ap.id
					JOIN users u ON d.user_id = u.id
					ORDER BY item_name
				) ap
				LEFT JOIN countries c ON ap.country_id = c.id
				LEFT JOIN streets s ON ap.street_id = s.id
				LEFT JOIN towns t ON ap.town_id = t.id
				LEFT JOIN members m ON ap.member_id = m.id
				$member_where
				GROUP BY ap.id
			) ap
			$where
			ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction
			LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", array
		(
			url_lang::lang('texts.Member'),
			url_lang::lang('texts.Member'),
			url_lang::lang('texts.Device')
		));
	}
	
	/**
	 * Counts all address points
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @author Michal Kliment, Ondřej Fibich
	 * @param integer	$member_id		Member who use this address point
	 * @param string	$filter_sql		Search filter
	 * @return integer					Count of all address points
	 */
	public function count_all_address_points($member_id = NULL, $filter_sql = '')
	{
		// filter
		$where = '';
		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		$member_where = '';
		if ($member_id && is_numeric($member_id))
			$member_where = "WHERE member_id = ".intval($member_id);
		
		// optimalization
		if (empty($where) && empty($member_where))
		{
			return $this->count_all();
		}
		
		// query
		return $this->db->query("
			SELECT COUNT(*) AS total FROM
			(
				SELECT
						ap.id,
						ap.name,
						s.street,
						ap.street_number,
						t.town, t.quarter,
						t.zip_code,
						IFNULL(CONCAT(X(ap.gps), ' ', Y(ap.gps)),'') AS gps,
						c.country_name,
						COUNT(ap.id) AS items_count
				FROM
				(
					SELECT ap.*, m.id AS member_id
					FROM address_points ap
					JOIN members m ON m.address_point_id = ap.id
					UNION ALL
					SELECT ap.*, md.member_id
					FROM address_points ap
					JOIN members_domiciles md ON md.address_point_id = ap.id
					JOIN members m ON md.member_id = m.id
					UNION ALL
					SELECT ap.*, u.member_id
					FROM address_points ap
					JOIN devices d ON d.address_point_id = ap.id
					JOIN users u ON d.user_id = u.id
				) ap
				LEFT JOIN countries c ON ap.country_id = c.id
				LEFT JOIN streets s ON ap.street_id = s.id
				LEFT JOIN towns t ON ap.town_id = t.id
				LEFT JOIN members m ON ap.member_id = m.id
				$member_where
				GROUP BY ap.id
			) ap
			$where
		")->current()->total;
	}

	/**
	 * Count all items (members or devices) belongs to address point
	 * 
	 * @author Michal Kliment
	 * @param integer $address_point_id
	 * @return integer
	 */
	public function count_all_items_by_address_point_id($address_point_id)
	{
		$result = $this->db->query("
				SELECT (member_count+device_count+members_domicile_count) AS count FROM
				(
						SELECT count(id) AS member_count
						FROM members m
						WHERE address_point_id = ?
				) AS a,
				(
						SELECT count(id) AS device_count
						FROM devices d
						WHERE address_point_id = ?
				) AS b,
				(
						SELECT COUNT(id) AS members_domicile_count
						FROM members_domiciles
						WHERE address_point_id = ?
				) AS c
		", array($address_point_id, $address_point_id, $address_point_id));

		return ($result && $result->current()) ? $result->current()->count : 0;
	}

	/**
	 * Get GPS coordinates from given address point
	 * 
	 * @author Ondřej Fibich
	 * @param integer $address_point_id
	 * @return object GPS separated to x and y part to fields named as: gpsx, gpsy
	 */
	public function get_gps_coordinates($address_point_id = NULL)
	{
		if (empty($address_point_id))
		{
			$address_point_id = $this->id;
		}
		
		$result = $this->db->query("
				SELECT X(gps) AS gpsx, Y(gps) AS gpsy
				FROM address_points
				WHERE id = ? AND gps NOT LIKE ''
		", array($address_point_id));

		return ($result && $result->current()) ? $result->current() : NULL;
	}

	/**
	 * Update GPS coordinates in address point
	 * 
	 * @author Ondřej Fibich
	 * @param integer $address_point_id
	 * @param double  $gpsx
	 * @param double  $gpsy
	 * @return boolean
	 */
	public function update_gps_coordinates($address_point_id, $gpsx, $gpsy)
	{
		return $this->db->query("
			UPDATE address_points
			SET gps = POINT(?, ?)
			WHERE id = ?
		", array(floatval($gpsx), floatval($gpsy), $address_point_id));
	}

	/**
	 * Returns all members on this address point
	 *
	 * @author Michal Kliment
	 * @return Mysql_Result
	 */
	public function get_all_members()
	{
		return $this->db->query("
				(
					SELECT m.id AS member_id, m.name AS member_name,
						IF(md.id IS NULL, 1,2) AS type
					FROM members m
					LEFT JOIN members_domiciles md ON md.member_id = m.id
					WHERE m.address_point_id = ?
				)
				UNION
				(
					SELECT m.id AS member_id, m.name AS member_name, 1 AS type
					FROM members_domiciles md
					JOIN members m ON md.member_id = m.id
					WHERE md.address_point_id = ?
				)
				ORDER BY member_id
		", $this->id, $this->id);
	}

	/**
	 * Returns all devices on this address point
	 *
	 * @author Michal Kliment
	 * @return Mysql_Result
	 */
	public function get_all_devices()
	{
		return $this->db->query("
				SELECT d.id AS device_id, d.name AS device_name, u.id AS user_id,
					CONCAT(u.name,' ',u.surname) AS user_name, m.id AS member_id,
					m.name AS member_name
				FROM devices d
				JOIN users u ON d.user_id = u.id
				JOIN members m ON u.member_id = m.id
				WHERE d.address_point_id = ?
		", array($this->id));
	}
	
	/**
	 * Returns all GPS by given like
	 * 
	 * @author Michal Kliment
	 * @param string $like
	 * @return MySQL_Result 
	 */
	public function get_all_gps ($like)
	{
		return $this->db->query("
			SELECT * FROM 
			(
				SELECT CONCAT(X(ap.gps), ' ', Y(ap.gps)) AS gps
				FROM address_points ap
			) ap
			WHERE gps IS NOT NULL AND gps LIKE ".$this->db->escape("%$like%"));
	}
	
	/**
	 * Return only 1 address point with GPS by given street, street number, town
	 * and country
	 * 
	 * @author Michal Kliment
	 * @param integer $street_id
	 * @param integer $sreet_number
	 * @param integer $town_id
	 * @param integer $country_id
	 * @return Address_point_Model
	 */
	public function get_address_point_with_gps_by_street_street_number_town_country (
			$street_id, $street_number, $town_id, $country_id)
	{
		$where = " AND country_id = ".intval($country_id)." AND town_id = ".intval($town_id);
		
		if ($street_id)
			$where .= " AND street_id = ".intval($street_id);
		else
			$where .= " AND street_id IS NULL";
		
		if ($street_number)
			$where .= " AND street_number = ".intval($street_number);
		else
			$where .= " AND street_number IS NULL";
		
		$result = $this->db->query("
			SELECT
				ap.*, CONCAT(X(ap.gps), ' ', Y(ap.gps)) AS gps
			FROM address_points ap
			WHERE CONCAT(X(ap.gps), ' ', Y(ap.gps)) IS NOT NULL
			$where
		");
		
		if ($result && $result->count() >= 1)
			return $result->current();
		else
			return NULL;
	}
	
	public function get_address_point_with_gps_by_country_id_town_district_street_zip(
		$country_id, $town, $district, $street, $number, $zip)
	{
		$where = " AND country_id = '".intval($country_id).
				"' AND town LIKE '".$this->db->escape_str($town)."'".
				" AND street LIKE '".$this->db->escape_str($street)."'".
				" AND street_number LIKE '".$this->db->escape_str($number)."'".
				" AND t.zip_code LIKE '".$this->db->escape_str($zip)."'";
		
		if ($district)
		{
			$where .= " AND quarter LIKE '".$this->db->escape_str($district)."'";
		}
		else
		{
			$where .= " AND quarter IS NULL";
		}
		
		$result = $this->db->query("
			SELECT
				ap.*, CONCAT(X(ap.gps), ' ', Y(ap.gps)) AS gps
			FROM address_points ap
			LEFT JOIN towns t ON t.id = ap.town_id
			LEFT JOIN streets s ON s.id = ap.street_id
			WHERE CONCAT(X(ap.gps), ' ', Y(ap.gps)) IS NOT NULL
			$where
		");
		
		if ($result && $result->count() >= 1)
			return $result->current();
		else
			return NULL;
	}
	
	/**
	 * Returns all address point with empty GPS coords
	 * 
	 * @author Michal Kliment
	 * @return Mysql_Result object 
	 */
	public function get_all_address_points_with_empty_gps ()
	{
		return $this->db->query("
			SELECT ap.id, s.street, ap.street_number, t.town, t.quarter,
				t.zip_code, c.country_name
			FROM address_points ap
			JOIN streets s ON ap.street_id = s.id
			JOIN towns t ON ap.town_id = t.id
			JOIN countries c ON ap.country_id = c.id
			WHERE country_id IS NOT NULL
				AND ap.town_id IS NOT NULL
				AND street_id IS NOT NULL
				AND street_number IS NOT NULL
				AND X(ap.gps) IS NULL
				AND Y(ap.gps) IS NULL
		");
	}
	
	/**
	 * Returns all address points with non null name
	 * 
	 * @author Michal Kliment
	 * @return MySQL Iterator object 
	 */
	public function get_all_address_point_names ()
	{
		$address_points = $this->db->query("
			SELECT ap.id, ap.name
			FROM address_points ap
			WHERE ap.name IS NOT NULL AND ap.name <> ''
			ORDER BY ap.name
		");
		
		$arr_address_points = array();
		foreach ($address_points as $address_point)
			$arr_address_points[$address_point->id] = $address_point->name;
		
		return $arr_address_points;
	}
}
