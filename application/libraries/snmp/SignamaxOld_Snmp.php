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
 * SNMP driver for Signamax switches with older firmware versions.
 * This class MUST not be initialized directly, use Snmp_Factory!
 * 
 * @author Michal Kliment
 * @see Abstract_Snmp
 */
class SignamaxOld_Snmp extends Abstract_Snmp
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
			return (
					$matches[1] == '065-7850' ||
					$matches[1] == '065-7710'
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
		
		try
		{
			// obtain
			$this->startErrorHandler();
			$row = snmp2_get(
					$this->deviceIp, $this->comunity, 
					'iso.3.6.1.2.1.17.4.3.1.2.' . $dec_mac_address,
					$this->timeout, $this->retries
			);
			$this->stopErrorHandler();
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		
		// parse result
		$regex = '/INTEGER: ([0-9]+)/';
		$matches = array();
		
		if (preg_match($regex, $row, $matches) > 0)
		{
			return $matches[1];
		}
		else
		{
			throw new Exception('Invalid SMNP output during obtaning of MAC address: ' . $row);
		}
	}
	
}
