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
 * SNMP driver for Signamax switches with newer firmware versions.
 * This class MUST not be initialized directly, use Snmp_Factory!
 * 
 * @author Michal Kliment
 * @see Abstract_Snmp
 */
class Signamax_Snmp extends Abstract_Snmp
{
	
	/**
	 * Checks if the driver is compactible with the driver.
	 * 
	 * @param string $device_ip Device IP address
	 * @return bool Is compactible?
	 */
	public function isCompactibleDriverWith($device_ip)
	{
		if (!valid::ip($device_ip))
		{
			throw new InvalidArgumentException('Wrong IP address of the device');
		}
		
		try
		{
			$this->startErrorHandler();
			$row = snmp2_get(
					$device_ip, $this->comunity, 'iso.3.6.1.2.1.1.5.0',
					$this->timeout, $this->retries
			);
			$this->stopErrorHandler();
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		
		// parse result
		$matches = array();
		
		if (preg_match('/STRING: "?([^"]*)"?/', $row, $matches) > 0)
		{
			return ($matches[1] == '065-7851' || $matches[1] == '300-7851');
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Obtain MAC address of a device with the given IP address from ARP table.
	 * 
	 * @param string $device_ip IP address of the device (we would like to know his MAC)
	 * @return string MAC address in format xx:xx:xx:xx:xx:xx
	 * @throws Exception On SNMP error or wrong SNMP response
	 * @throws InvalidArgumentException On wrong IP address
	 */
	public function getARPMacAddressOf($device_ip)
	{
		// is not possible
		return FALSE;
	}
	
	/**
	 * Obtain MAC address of a device with the given IP address from DHCP server.
	 * 
	 * @param string $device_ip IP address of the device (we would like to know his MAC)
	 * @return string MAC address in format xx:xx:xx:xx:xx:xx
	 * @throws Exception On SNMP error or wrong SNMP response
	 * @throws InvalidArgumentException On wrong IP address
	 */
	public function getDHCPMacAddressOf($device_ip)
	{
		// is not possible
		return FALSE;
	}
	
	/**
	 * Obtaint port number with given MAC address.
	 * 
	 * @param string $mac_address MAC address of the device (we would like to know to which port is connected)
	 * @return string MAC address in format xx:xx:xx:xx:xx:xx
	 * @throws Exception On SNMP error or wrong SNMP response
	 * @throws InvalidArgumentException On wrong IP address
	 */
	public function getPortNumberOf($mac_address)
	{
		if (!valid::mac_address($mac_address))
		{
			throw new InvalidArgumentException('Wrong MAC address of the device');
		}
		
		// covert MAC address to decimal format
		$dec_mac_address = implode('.', array_map('hexdec', explode(":", $mac_address)));
		
		// obtain whole ARP table
		$this->startErrorHandler();
		$arp_table = snmp2_real_walk(
				$this->deviceIp, $this->comunity, 'iso.3.6.1.2.1.17.7.1.2.2.1.2',
				$this->timeout, $this->retries
		);
		$this->stopErrorHandler();
		
		// parse result
		$regex = '/INTEGER: ([0-9]+)/';
		$matches = array();
		
		// try find MAC address in ARP table
		foreach ($arp_table as $key => $val)
		{
			if (text::ends_with($key, '.' . $dec_mac_address) &&
				preg_match($regex, $val, $matches))
			{
				return $matches[1];
			}
		}
	}
	
}
