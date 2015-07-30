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
 * DNS zone belongs to IP address and contains all 
 * informations about DNS zone
 * 
 * @author David Raška
 * @package Model
 * 
 * @property int $id
 * @property string $zone
 * @property int $ttl
 * @property string $nameserver
 * @property string $email
 * @property string $sn
 * @property string $refresh
 * @property string $retry
 * @property string $expire
 * @property string $nx
 * @property integer $ip_address_id
 * @property Ip_address_Model $ip_address
 * @property datetime $access_time
 * @property ORM_Iterator $dns_records
 */
class Dns_zone_Model extends ORM
{
	protected $belongs_to = array('ip_address');
	protected $has_many = array('dns_records');
	
	/**
	 * Function gets list of all domains.
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @param $limit_from starting row
	 * @param $limit_results number of rows
	 * @param $order_by sorting column
	 * @param $order_by_direction sorting direction
	 * @param $filter_values used for filtering
	 * @return Mysql_Result
	 */
	public function get_all_zones($limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'asc', $filter_sql = '')
	{
		$having = '';
		
		if ($filter_sql != '')
			$having .= 'HAVING '.$filter_sql;
		
		// query
		return $this->db->query("
				SELECT *
				FROM
				(
					SELECT d.*, ip.ip_address AS primary_ip_address, r.value, r.name, r.type
					FROM dns_zones d
					JOIN ip_addresses ip ON d.ip_address_id = ip.id
					LEFT JOIN dns_records r ON d.id = r.dns_zone_id
					$having
				) s
				GROUP BY id
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		");
	}
	
	/**
	 * Function gets count of all domains.
	 * 
	 * !!!!!! SECURITY WARNING !!!!!!
	 * Be careful when you using this method, param $filter_sql is unprotected
	 * for SQL injections, security should be made at controller site using
	 * Filter_form class.
	 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * 
	 * @param $filter_values used for filtering
	 * @return integer
	 */
	public function count_all_zones($filter_sql = '')
	{
		$having = '';
		
		if ($filter_sql != '')
			$having .= 'HAVING '.$filter_sql;
		
		// query
		return $this->db->query("
				SELECT *
				FROM
				(
					SELECT d.*, ip.ip_address AS primary_ip_address, r.value, r.name, r.type
					FROM dns_zones d
					JOIN ip_addresses ip ON d.ip_address_id = ip.id
					LEFT JOIN dns_records r ON d.id = r.dns_zone_id
					$having
				) s
				GROUP BY id
		")->count();
	}
	
	/**
	 * Returns all primary zones managed by given IP address
	 * @param int $ip_address_id
	 * @return ORM Object
	 */
	public function get_zones_of_primary_server($ip_address_id)
	{
		if ($ip_address_id === NULL)
		{
			return NULL;
		}
		
		return $this->where(array('ip_address_id' => $ip_address_id))->orderby('id')->find_all();
	}
	
	/**
	 * Returns all secondary zones managed by given IP address
	 * @param int $ip_address_id
	 * @return ORM Object
	 */
	public function get_zones_of_secondary_server($ip_address_id)
	{
		if ($ip_address_id === NULL)
		{
			return NULL;
		}
		
		return $this->join('dns_zones_map', array('dns_zones.id' =>  'dns_zones_map.dns_zone_id'))
				->join('ip_addresses', array('dns_zones.ip_address_id' => 'ip_addresses.id'))
				->where(array('dns_zones_map.ip_address_id' => $ip_address_id))
				->select('dns_zones.*, ip_addresses.ip_address')
				->orderby('dns_zones.zone')->find_all();
	}
	
	/**
	 * Sets access zone time 
	 * @param array $zone_ids
	 * @param datetime $time
	 * @return ORM Object
	 */
	public function set_access_time_for_zones($zone_ids, $time = NULL)
	{
		if ($time == NULL)
		{
			$time = date('Y-m-d H:i:s');
		}
		
		// search multiple IDs
		if (is_array($zone_ids))
		{
			$where = "WHERE id IN (".implode(',', array_map('intval', $zone_ids)).")";
		}
		else if (is_numeric($zone_ids))
		{
			$where = "WHERE id = ".$this->db->escape($zone_ids);
		}
		else
		{
			return NULL;
		}
		
		// query
		return $this->db->query("
				UPDATE dns_zones
				SET access_time = ?
				$where
		", $time);
	}
}
