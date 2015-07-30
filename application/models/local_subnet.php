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
 * Local subnet contains subnets belongs to default country
 *
 * @author	Michal Kliment
 * @package Model
 * 
 * @property integer $id
 * @property string $network_address
 * @property string $netmask
 */
class Local_subnet_Model extends ORM
{
	/**
	 * Returns all local subnets in CIDR format
	 * 
	 * @author Michal Kliment
	 * @return type 
	 */
	public function get_all_local_subnets()
	{
		return $this->db->query("
			SELECT id, CONCAT(
				network_address, '/',
				ROUND(32-log2((~inet_aton(netmask) & 0xffffffff) + 1))
			) AS address
			FROM local_subnets
			ORDER BY INET_ATON(network_address)
		");
	}
	
	/**
	 * Adds subnets
	 * 
	 * @author Michal Kliment
	 * @param string $subnets
	 * @return boolean 
	 */
	public function add_subnets($subnets = array())
	{
		// it has to be array
		if (!count($subnets))
			return false;
		
		// converts inner array to string
		foreach ($subnets as $i => $subnet)
		{
			$subnets[$i] = "(".$this->db->escape ($subnet['network_address'])
							.", ".$this->db->escape($subnet['netmask']).")";
		}
		
		return $this->db->query("
			INSERT INTO local_subnets (network_address, netmask)
			VALUES ".implode(", ",$subnets)
		);
	}
	
	/**
	 * Delets all local subnets
	 * 
	 * @author Michal Kliment 
	 */
	public function delete_subnets()
	{
		$this->db->query("TRUNCATE local_subnets");
	}
}