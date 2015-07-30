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
 * SNMP driver for Edgecore switches (tested on 3528M a 3510MA).
 * This class MUST not be initialized directly, use Snmp_Factory!
 * 
 * @author Michal Kliment
 * @see Abstract_Snmp
 */
class Edgecore_Snmp extends Abstract_Snmp
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
			snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
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
		
		return(
			$row == '24/48 L2/L4 IPV4/IPV6 GE Switch' ||
			$row == 'Edge-Core FE L2 Switch ES3528M' ||
			$row == 'ES3528M' ||
			$row == 'ES3510MA'
		);
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
			snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
			$port_nr = snmp2_get(
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
		
		if (is_numeric($port_nr))
		{
			return $port_nr;
		}
		else
		{
			throw new Exception('Invalid SMNP output during obtaning of MAC address: ' . $row);
		}
	}
	
	/**
	 * Obtain names of all network interfaces of device
	 * 
	 * @return array Network interfaces of device
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public function getIfaces()
	{
		return array();
	}
	
	/**
	 * Obtain current state of device's ports
	 * 
	 * @return array Current states of all ports
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public function getPortStates()
	{
		$this->startErrorHandler();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		$port_states = snmp2_real_walk(
				$this->deviceIp, $this->comunity, 
				'iso.3.6.1.2.1.2.2.1.8',
				$this->timeout, $this->retries
		);
		$this->stopErrorHandler();
		
		$states = array();
		foreach ($port_states as $key => $value)
		{
			$pieces = explode('.', $key);
			
			$port_nr = array_pop($pieces);
			
			$states[$port_nr] = $value == 1 ? 1 : 0;
		}
		
		return $states;
	}
	
	/**
	 * Obtain ARP table of device
	 * 
	 * @return array Whole ARP table from device
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public function getARPTable()
	{
		return array();
	}
	
	/**
	 * Obtain DHCP leases of device
	 * 
	 * @return array All DHCP leases
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public function getDHCPLeases()
	{
		return array();
	}
	
	/**
	 * Obtain device's hostname from DHCP leases of device
	 * 
	 * @param string $device_ip IP address to which we will search for hostname
	 * @return string Hostname for given IP address
	 * @throws Exception On SNMP error or wrong SNMP response
	 * @throws InvalidArgumentException On wrong IP address
	 */
	public function getDHCPHostnameOf($device_ip)
	{
		return FALSE;
	}
	
	/**
	 * Obtain wireless info of device
	 * 
	 * @return array Current wireless info
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public function getWirelessInfo()
	{
		return array();
	}
	
	/**
	 * Obtain MAC table from device
	 * 
	 * @return array Whole MAC table
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public function getMacTable()
	{	
		$this->startErrorHandler();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		$mac_table = snmp2_real_walk(
				$this->deviceIp, $this->comunity, 
				'iso.3.6.1.2.1.17.4.3.1.2',
				$this->timeout, $this->retries
		);
		$this->stopErrorHandler();
		
		$items = array();
		$ports = array();
		foreach ($mac_table as $key => $value)
		{
			$pieces = explode('.', $key);
			
			$item = new stdClass();
			
			$item->port_nr = $value;
			
			$i = implode('.', array_slice($pieces, -6));

			$item->mac_address = network::dec2mac($i);

			$items[] = $item;
			$ports[] = $value;
		}
		
		array_multisort($ports, $items);
		
		return $items;
	}
}