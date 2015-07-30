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
 * Abstract SNMP library. This class contains all function that are available
 * for each driver.
 * 
 * A driver may be created using Snmp_Factory.
 * Each driver is connected to a single device that is reprezented by it's IP. 
 * 
 * @author Ondrej Fibich
 */
abstract class Abstract_Snmp
{
	
	/**
	 * The number of microseconds until the first timeout.
	 *
	 * @var int
	 */
	protected $timeout = 3000000;
	
	/**
	 * The number of times to retry if timeouts occur.
	 *
	 * @var int
	 */
	protected $retries = 5;
	
	/**
	 * The read community.
	 *
	 * @var string
	 */
	protected $comunity = 'public';
	
	/**
	 * Indicates whether the error handling is started or not.
	 *
	 * @var bool
	 */
	protected $error_handler_started = FALSE;
	
	/**
	 * IP address of device.
	 *
	 * @var string
	 */
	protected $deviceIp;
	
	/**
	 * Checks if the driver is compactible with the driver.
	 * 
	 * @param string $device_ip Device IP address
	 * @return bool Is compactible?
	 */
	public abstract function isCompactibleDriverWith($device_ip);
	
	/**
	 * Obtain MAC address of a device with the given IP address from ARP table.
	 * 
	 * @param string $device_ip IP address of the device (we would like to know his MAC)
	 * @return string MAC address in format xx:xx:xx:xx:xx:xx
	 * @throws Exception On SNMP error or wrong SNMP response
	 * @throws InvalidArgumentException On wrong IP address
	 */
	public abstract function getARPMacAddressOf($device_ip);
	
	/**
	 * Obtain MAC address of a device with the given IP address from DHCP server.
	 * 
	 * @param string $device_ip IP address of the device (we would like to know his MAC)
	 * @return string MAC address in format xx:xx:xx:xx:xx:xx
	 * @throws Exception On SNMP error or wrong SNMP response
	 * @throws InvalidArgumentException On wrong IP address
	 */
	public abstract function getDHCPMacAddressOf($device_ip);
	
	/**
	 * Obtain port number with given MAC address.
	 * 
	 * @param string $mac_address MAC address of the device (we would like to know to which port is connected)
	 * @return string MAC address in format xx:xx:xx:xx:xx:xx
	 * @throws Exception On SNMP error or wrong SNMP response
	 * @throws InvalidArgumentException On wrong IP address
	 */
	public abstract function getPortNumberOf($mac_address);
	
	/**
	 * Obtain names of all network interfaces of device
	 * 
	 * @return array Network interfaces of device
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public abstract function getIfaces();
	
	/**
	 * Obtain current state of device's ports
	 * 
	 * @return array Current states of all ports
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public abstract function getPortStates();
	
	/**
	 * Obtain ARP table of device
	 * 
	 * @return array Whole ARP table from device
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public abstract function getARPTable();
	
	/**
	 * Obtain DHCP leases of device
	 * 
	 * @return array All DHCP leases
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public abstract function getDHCPLeases();
	
	/**
	 * Obtain device's hostname from DHCP leases of device
	 * 
	 * @param string $device_ip IP address to which we will search for hostname
	 * @return string Hostname for given IP address
	 * @throws Exception On SNMP error or wrong SNMP response
	 * @throws InvalidArgumentException On wrong IP address
	 */
	public abstract function getDHCPHostnameOf($device_ip);
	
	/**
	 * Obtain wireless info of device
	 * 
	 * @return array Current wireless info
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public abstract function getWirelessInfo();
	
	/**
	 * Obtain MAC table from device
	 * 
	 * @return array Whole MAC table
	 * @throws Exception On SNMP error or wrong SNMP response
	 */
	public abstract function getMacTable();

	/**
	 * Gets the current number of microseconds until the first timeout.
	 * 
	 * @return int
	 */
	public function getTimeout()
	{
		return $this->timeout;
	}

	/**
	 * Sets the number of microseconds until the first timeout.
	 * 
	 * @param int $timeout Timeout in microseconds
	 */
	public function setTimeout($timeout)
	{
		$this->timeout = $timeout;
	}

	/**
	 * Gets the number of times to retry if timeouts occur.
	 * 
	 * @return int
	 */
	public function getRetries()
	{
		return $this->retries;
	}

	/**
	 * Sets the number of times to retry if timeouts occur.
	 * 
	 * @param int $retries 
	 */
	public function setRetries($retries)
	{
		$this->retries = $retries;
	}
	
	/**
	 * Gets the read community.
	 * 
	 * @return string The read comunity (e.g. "public")
	 */
	public function getComunity()
	{
		return $this->comunity;
	}

	/**
	 * Sets the read community.
	 * 
	 * @param string $comunity The read comunity (e.g. "public")
	 */
	public function setComunity($comunity)
	{
		$this->comunity = $comunity;
	}
	
	/**
	 * Sets device IP.
	 * 
	 * @param string $device_ip New IP address
	 * @throws InvalidArgumentException If ip address is invalid
	 */
	public function setDeviceIp($device_ip)
	{
		if (!valid::ip($device_ip))
		{
			throw new InvalidArgumentException('Wrong or empty IP address');
		}
		
		$this->deviceIp = $device_ip;
	}
	
	/**
	 * Gets device IP address.
	 * 
	 * @return string
	 */
	public function getDeviceIp()
	{
		return $this->deviceIp;
	}
		
	/**
	 * Handles errors (throws exceptions)
	 * 
	 * @param int $errno
	 * @param string $errstr
	 * @param int $errfile
	 * @param int $errline
	 * @throws Exception
	 */
	public function errorHandler($errno, $errstr, $errfile, $errline)
	{
		$this->stopErrorHandler();
		throw new Exception($errstr, $errno);
	}
	
	/**
	 * Starts handling PHP errors as Exceptions (if not already started)
	 */
	protected function startErrorHandler()
	{
		if (!$this->error_handler_started)
		{
			$this->error_handler_started = TRUE;
			set_error_handler(array($this, 'errorHandler'));
		}
	}
	
	/**
	 * Stops handling PHP errors as Exceptions (if not alreasy stopped)
	 */
	protected function stopErrorHandler()
	{
		if ($this->error_handler_started)
		{
			$this->error_handler_started = FALSE;
			restore_error_handler();
		}
	}
	
}

class DHCPMacAddressException extends Exception
{
	
}