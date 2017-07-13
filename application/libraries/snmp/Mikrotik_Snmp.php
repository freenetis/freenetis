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
 * Mikrotik SNMP driver.
 * This class MUST not be initialized directly, use Snmp_Factory!
 * 
 * @author Ondrej Fibich
 * @see Abstract_Snmp
 */
class Mikrotik_Snmp extends Abstract_Snmp
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
					$device_ip, $this->comunity, 'iso.3.6.1.2.1.1.1.0',
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
		
		if (preg_match('/STRING: "?(.*)"?/', $row, $matches) > 0)
		{
			return (
					text::starts_with($matches[1], 'RouterOS') // RouterOS > 4.10
			);
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
		if (!valid::ip($device_ip))
		{
			throw new InvalidArgumentException('Wrong IP address of the device');
		}
		
		// obtain whole ARP table
		$this->startErrorHandler();
		$arp_table = snmp2_real_walk(
				$this->deviceIp, $this->comunity, 
				'iso.3.6.1.2.1.4.22.1.2',
				$this->timeout, $this->retries
		);
		$this->stopErrorHandler();
		
		// parse result
		$regex = '/Hex-STRING: .*(([0-9a-fA-F]{2}\s){5}[0-9a-fA-F]{2})/';
		$matches = array();
		
		// try find MAC address in ARP table
		foreach ($arp_table as $key => $val)
		{
			if (text::ends_with($key, '.' . $device_ip)
				&& preg_match($regex, $val, $matches))
			{
				$pieces = array();
				foreach (explode(' ', $matches[1]) as $piece)
					$pieces[] = num::null_fill($piece, 2);
				
				return implode(':', $pieces);
			}
		}
		
		throw new Exception('Given IP address ' . $device_ip
				. ' not in ARP table on ' . $this->deviceIp);
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
		if (!valid::ip($device_ip))
		{
			throw new InvalidArgumentException('Wrong IP address of the device');
		}
		
		try
		{
			// obtain
			$this->startErrorHandler();
			$row = snmp2_get(
					$this->deviceIp, $this->comunity, 
					'iso.3.6.1.2.1.9999.1.1.6.4.1.8.' . $device_ip,
					$this->timeout, $this->retries
			);
			$this->stopErrorHandler();
		}
		catch (Exception $e)
		{
			throw new DHCPMacAddressException($e->getTraceAsString());
		}
		
		// parse result
		$regex = '/Hex-STRING: .*(([0-9a-fA-F]{2}\s){5}[0-9a-fA-F]{2})/';
		$matches = array();
		
		if (preg_match($regex, $row, $matches) > 0)
		{
			return mb_strtolower(str_replace(' ', ':', $matches[1]));
		}
		else
		{
			throw new DHCPMacAddressException('Invalid SMNP output during obtaning of MAC address: ' . $row);
		}
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
		// is not possible
		return FALSE;
	}
	
}
