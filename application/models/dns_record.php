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
 * Represents single DNS record in DNS zone
 * 
 * @author David Raška
 * @package Model
 * 
 * @property int $id
 * @property Dns_zone_model $dns_zone
 * @property string $name
 * @property string $ttl
 * @property string $type
 * @property string $value
 * @property string $param
 */
class Dns_record_Model extends ORM
{
	protected $belongs_to = array('dns_zone');
	
	/**
	 * Returns all dns record types in given zone
	 * @param int $dns_zone_id
	 * @return null|string
	 */
	public function get_records_types_in_zone($dns_zone_id = NULL)
	{
		if ($dns_zone_id === NULL)
		{
			return NULL;
		}
		
		// query
		$result = $this->db->query("
			SELECT group_concat(type SEPARATOR ',') AS types
			FROM dns_records
			WHERE dns_zone_id = ?
		", $dns_zone_id);
		
		if ($result)
		{
			return $result->current()->types;
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Returns all records in given zone
	 * @param int $dns_zone_id
	 * @param string $record
	 * @return ORM Object
	 */
	public function get_records_in_zone($dns_zone_id = NULL, $record = NULL)
	{
		if ($dns_zone_id === NULL)
		{
			return NULL;
		}
		
		// search by record type
		if ($record !== NULL)
		{
			$where = "AND type LIKE ".$this->db->escape($record);
		}
		else
		{
			$where = '';
		}
		
		// query
		$result = $this->db->query("
			SELECT r.*
				FROM dns_records r
				WHERE dns_zone_id = ?
				$where
		", $dns_zone_id);
		
		if ($result)
		{
			return $result;
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Returns all records with relative addresses converted to fully qualified
	 * domain names
	 * @param int $dns_zone_id
	 * @param string $record
	 * @return ORM object
	 */
	public function get_fqdn_records_in_zone($dns_zone_id = NULL, $record = NULL)
	{
		if ($dns_zone_id === NULL)
		{
			return NULL;
		}
		
		// search by record type
		if ($record !== NULL)
		{
			$where = "AND type LIKE ".$this->db->escape($record);
		}
		else
		{
			$where = '';
		}
		
		// query
		$result = $this->db->query("
			SELECT r.id, r.dns_zone_id, r.type, r.param,
				IF (r.ttl LIKE '', z.ttl, r.ttl) AS ttl,
				IF (r.name LIKE '',
					CONCAT(z.zone, '.'),
					IF (SUBSTRING(r.name, -1) = '.',
						r.name,
						IF (r.name = '@',
							CONCAT(z.zone, '.'),
							CONCAT(r.name,'.',z.zone,'.')
						)
					)
				) as name,
				IF (r.type LIKE 'CNAME' OR r.type LIKE 'NS' OR r.type LIKE 'MX',
					IF (SUBSTRING(r.value, -1) = '.',
						r.value,
						IF (r.value = '@',
							CONCAT(z.zone, '.'),
							CONCAT(r.value,'.',z.zone,'.')
						)
					),
					r.value
				) AS value
				FROM dns_records r
				LEFT JOIN dns_zones z ON r.dns_zone_id = z.id
				WHERE dns_zone_id = ?
				$where
		", $dns_zone_id);
		
		if ($result)
		{
			return $result;
		}
		else
		{
			return NULL;
		}
	}
}
