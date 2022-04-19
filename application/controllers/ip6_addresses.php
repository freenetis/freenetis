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
 * Controller performs actions over IP adresses of network.
 * Each IP address belongs to subnet and correspondes to subnet mask.
 *
 * @package Controller
 */
class Ip6_addresses_Controller extends Controller
{
	/**
     * calculate ipv6 subnet from ip address
     * 
     * @param integer $ip_address
     */
	public function calc_ip6_address($ip_address)
	{
		list($w, $x, $y, $z) = explode('.', $ip_address);
		    $y = dechex($y);
		    $z = dechex($z);
		    if ($w != "10")
		    {
			    $ip6_address = "2a07:9c3:".$y.":".$z."00::/56";
		    }
		    else
		    {
			    $ip6_address = "2a07:9c0:".$y.":".$z."00::/56";
		    }
		    return $ip6_address;
		
	}


}
