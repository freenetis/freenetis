<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Dns helper class.
 *
 * $Id: dns.php
 *
 * @package    Core
 * @author     Kliment Michal
 * @copyright  (c) 2009 Kliment Michal
 */
 
class dns {

	/**
	 * Create reverse dns lookup from ip address
	 * 
	 * @author Michal Kliment
	 * @param   string  ip address
	 * @return  string
	 */
	public static function create_reverse_dns_lookup_from_ip_address($ip_address)
	{
		if ($ip_address == '') return '';

		$segments = explode('.', $ip_address);
		$segments = array_reverse($segments);

		return implode('.', $segments).'.IN-ADDR.ARPA.';
	}

	/**
	 * Get ptr (reverse) record from ip address
	 * 
	 * @author Michal Kliment
	 * @param   string  ip address
	 * @return  string
	 */
	public static function get_ptr_record($ip_address)
	{
		if (!valid::ip($ip_address)) return '';

		$result = @dns_get_record(dns::create_reverse_dns_lookup_from_ip_address($ip_address), DNS_PTR);

		if (!$result || count($result)!=1) return '';
		return $result[0]['target'];
	}

} // End dns
