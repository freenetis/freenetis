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
 * UBNT SNMP driver.
 * This class MUST not be initialized directly, use Snmp_Factory!
 * 
 * @author Michal Kliment
 * @see Abstract_Snmp
 */
class UBNT_Snmp extends Abstract_Snmp
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
			$row = snmpget(
					$device_ip, $this->comunity, 'iso.3.6.1.2.1.1.1.0',
					$this->timeout, $this->retries
			);
			$this->stopErrorHandler();
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		
		return (strpos($row, 'mips') !== FALSE);
	}
	
	/**
	 * Obtain MAC address of a device with the given IP address from ARP table
	 * 
	 * @param string $device_ip IP address of the device (we would like to know his MAC)
	 * @return boolean Always false (cannot be implemented)
	 */
	public function getARPMacAddressOf($device_ip)
	{
		return FALSE;
	}
	
	/**
	 * Obtain MAC address of a device with the given IP address from DHCP server.
	 * 
	 * @param type $device_ip IP address of the device (we would like to know his MAC)
	 * @return boolean Always false (cannot be implemented)
	 */
	public function getDHCPMacAddressOf($device_ip)
	{
		return FALSE;
	}
	
	/**
	 * Obtain port number with given MAC address.
	 * 
	 * @param string $mac_address MAC address of the device (we would like to know to which port is connected)
	 * @return boolean Always false (cannot be implemented)
	 */
	public function getPortNumberOf($mac_address)
	{
		return FALSE;
	}
	
	/**
	 * Obtain names of all network interfaces of device
	 * 
	 * @return array Network interfaces of device
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public function getIfaces()
	{
		$this->startErrorHandler();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		$data = snmprealwalk(
				$this->deviceIp, $this->comunity, 
				'iso.3.6.1.2.1.2.2.1.2',
				$this->timeout, $this->retries
		);
		$this->stopErrorHandler();
		
		$ifaces = array();
		foreach ($data as $key => $value)
		{
			$oids = explode('.', $key);
			
			$iface_id = array_pop($oids);
			
			$ifaces[$iface_id] = $value;
		}
		
		return $ifaces;
	}
	
	/**
	 * Obtain current state of device's ports
	 * 
	 * @return array Current states of all ports
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public function getPortStates()
	{
		return array();
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
		// obtain all DHCP leases
		$this->startErrorHandler();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		$wireless_items = snmprealwalk(
				$this->deviceIp, $this->comunity, 
				'iso.3.6.1.4.1.14988.1.1.1.2.1',
				$this->timeout, $this->retries
		);
		$this->stopErrorHandler();
		
		$ifaces = self::getIfaces();
		
		$items = array();
		foreach ($wireless_items as $key => $value)
		{
			$pieces = explode('.', $key);
			
			$index = $pieces[count($pieces)-8];
			
			$iface_id = array_pop($pieces);
			
			$i = implode('.', array_slice($pieces, -6));
			
			if (!array_key_exists($i, $items))
			{
				$items[$i] = new stdClass ();
				$items[$i]->mac_address = network::dec2mac($i);
				$items[$i]->uptime = 0;
				$items[$i]->iface_name = $ifaces[$iface_id];
			}
			
			switch ($index)
			{
				// signal strength
				case '3':
					$items[$i]->signal = $value;
					break;
				
				// tx-rate
				case '8':
					$items[$i]->tx_rate = $value;
					break;
				// rx-rate
				case '9':
					$items[$i]->rx_rate = $value;
					break;
			}
		}
		
		return $items;
	}
	
	/**
	 * Obtain MAC table from device
	 * 
	 * @return array Whole MAC table
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public function getMacTable()
	{
		return array();
	}
}