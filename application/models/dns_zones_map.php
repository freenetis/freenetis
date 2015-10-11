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
 * Relationship between zones and ip addresses,
 * stores secondary servers ip addresses of dns zone
 * 
 * @author David Raška
 * @package Model
 * 
 * @property integer $dns_zone_id
 * @property integer $ip_address_id
 * @property Dns_zone_Model $dns_zone
 * @property Ip_address_Model $ip_address
 */
class Dns_zones_map_Model extends ORM
{
	/**
	 * Table name is dns_zones_Map not dns_zones_Maps
	 * 
	 * @var bool
	 */
	protected $table_names_plural = FALSE;
	
	protected $belongs_to = array('ip_address', 'dns_zone');
	
	/**
	 * Deletes mapping of zones to IP addresses
	 * @param int $dns_zone_id
	 * @return ORM Object
	 */
	public function delete_secondary_servers($dns_zone_id)
	{
		if ($dns_zone_id === NULL)
		{
			return NULL;
		}
		
		return $this->where(array('dns_zone_id' => $dns_zone_id))->delete_all();
	}
	
	/**
	 * Adds mapping zones to IP addresses
	 * @param int $dns_zone_id
	 * @param array $ip_addresses
	 * @return ORM Object
	 */
	public function add_secondary_servers($dns_zone_id, $ip_addresses)
	{
		$values = array();
		if ($dns_zone_id === NULL)
		{
			return NULL;
		}
		
		$dns_zone_id = $this->db->escape_str($dns_zone_id);

		if ($ip_addresses)
		{
			// insert all ip addresses
			foreach ($ip_addresses as $ip)
			{
				$values[] = '('.$dns_zone_id.','.$this->db->escape_str($ip).')';
			}
			
			return $this->db->query("INSERT INTO dns_zones_map VALUES " . implode(',', $values));
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Returns IDs of mapped IP addresses
	 * @param int $dns_zone_id
	 * @return ORM Object
	 */
	public function get_secondary_servers_ids($dns_zone_id)
	{
		if ($dns_zone_id === NULL)
		{
			return array();
		}
		
		return $this->where(array('dns_zone_id' => $dns_zone_id))->select_list('ip_address_id', 'ip_address_id');
	}
	
	/**
	 * Returns IP addresses mapped to given zone
	 * @param int $dns_zone_id
	 * @return ORM Object
	 */
	public function get_secondary_servers_ips($dns_zone_id)
	{
		if ($dns_zone_id === NULL)
		{
			return array();
		}
		
		return $this->join('ip_addresses', array('dns_zones_map.ip_address_id' => 'ip_addresses.id'))
				->where(array('dns_zone_id' => $dns_zone_id))->select_list('ip_address_id', 'ip_address');
	}
	
	/**
	 * Returns IP address ORM objects mapped to given zone
	 * @param int $dns_zone_id
	 * @return ORM Object
	 */
	public function get_secondary_servers($dns_zone_id)
	{
		if ($dns_zone_id === NULL)
		{
			return array();
		}
		
		return $this->join('ip_addresses', array('dns_zones_map.ip_address_id' => 'ip_addresses.id'))
				->where(array('dns_zone_id' => $dns_zone_id))->orderby('ip_address')->find_all();
	}
}
