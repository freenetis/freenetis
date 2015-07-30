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
 * Linux SNMP driver.
 * This class MUST not be initialized directly, use Snmp_Factory!
 * 
 * @author Ondrej Fibich
 * @see Abstract_Snmp
 */
class Linux_Snmp extends Abstract_Snmp
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
		
		if (text::starts_with($row, 'Linux'))
		{
			return TRUE;
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
		
		$arp_table = $this->getARPTable();
		
		if (array_key_exists($device_ip, $arp_table))
		{
			return $arp_table[$device_ip]->mac_address;
		}
		else
		{
			throw new Exception('Given IP address ' . $device_ip
				. ' not in ARP table on ' . $this->deviceIp);
		}
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
			snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
			$mac_address = snmp2_get(
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
		
		return mb_strtolower(str_replace(' ', ':', $mac_address));
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
	
	/**
	 * Obtain names of all network interfaces of device
	 * 
	 * @return array Network interfaces of device
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public function getIfaces()
	{
		// obtain all interfaces
		$this->startErrorHandler();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		$data = snmp2_real_walk(
				$this->deviceIp, $this->comunity, 
				'iso.3.6.1.2.1.31.1.1.1.1',
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
		// obtain whole ARP table
		$this->startErrorHandler();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		$arp_table = snmp2_real_walk(
				$this->deviceIp, $this->comunity, 
				'iso.3.6.1.2.1.4.22.1.2',
				$this->timeout, $this->retries
		);
		$this->stopErrorHandler();
		
		$ifaces = self::getIfaces();
		
		$items = array();
		
		foreach ($arp_table as $key => $value)
		{			
			$pieces = explode('.', $key);
			
			$iface_id = implode('.', array_slice($pieces, -5, 1));
			
			if (!array_key_exists($iface_id, $ifaces))
				continue;
			
			$mac = network::bin2mac($value);
			
			$item = new stdClass();
			
			$item->ip_address = implode('.', array_slice($pieces, -4));
			$item->mac_address = $mac;
			$item->iface_name = $ifaces[$iface_id];
			
			$items[$item->ip_address] = $item;
		}
		
		return $items;
	}
	
	/**
	 * Obtain DHCP leases of device
	 * 
	 * @return array All DHCP leases
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public function getDHCPLeases()
	{
		// obtain all DHCP leases
		$this->startErrorHandler();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		$dhcp_leases = snmp2_real_walk(
				$this->deviceIp, $this->comunity, 
				'iso.3.6.1.2.1.9999.1.1.6.4.1.8',
				$this->timeout, $this->retries
		);
		$this->stopErrorHandler();
		
		$items = array();
		foreach ($dhcp_leases as $key => $value)
		{
			$pieces = explode('.', $key);
			
			$item = new stdClass();
			
			$item->ip_address = implode('.', array_slice($pieces, -4));
			$item->mac_address = str_replace(' ', ':', $value);
			
			$items[] = $item;
		}
		
		return $items;
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
		if (!valid::ip($device_ip))
		{
			throw new InvalidArgumentException('Wrong IP address of the device');
		}
		
		try
		{
			// obtain
			$this->startErrorHandler();
			snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
			$hostname = snmp2_get(
					$this->deviceIp, $this->comunity, 
					'iso.3.6.1.2.1.9999.1.1.6.4.1.9.' . $device_ip,
					$this->timeout, $this->retries
			);
			$this->stopErrorHandler();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getTraceAsString());
		}
		
		return $hostname;
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
		return array();
	}
	
}