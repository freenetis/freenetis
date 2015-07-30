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
 * Connection class for ebanking web application of czech bank "FIO banka".
 * 
 * @abstract Class for parsing bank account listings from czech bank "FIO banka".
 * @author Petr Hruska, Lukas Turek
 * @copyright 2009-2011 Petr Hruska, Lukas Turek, o.s. CZF-Praha
 * @link http://www.praha12.net
 */
class FioConnection
{

	/**
	 * Construct of connection, load config
	 *
	 * @param FioConfig $config 
	 */
	public function __construct($config)
	{
		$this->config = $config;
	}

	/**
	 * Gets data in CSV
	 *
	 * @throws FioException	on error
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @return string
	 */
	public function getCSV($dateFrom, $dateTo)
	{
		try
		{
			$this->login();
			$this->download($this->config->filterURL($dateFrom, $dateTo));
			$this->download($this->config->historyURL());
			$this->download($this->config->viewURL());
			$csvData = $this->download($this->config->downloadURL());
		}
		catch (FioException $e)
		{
			$this->logout();
			throw $e;
		}

		$this->logout();
		return $csvData;
	}

	/**
	 * Opens CURL connection to URL, sets headers
	 *
	 * @throws FioException	on error
	 * @param string $url 
	 */
	private function openCurlHandle($url)
	{
		$this->curlHandle = curl_init($url);
		
		if ($this->curlHandle === false)
			throw new FioException('Cannot initialize CURL!');

		// required headers
		$this->setCurlOption(CURLOPT_USERAGENT, 'cURL;(Proc proboha vas server vyzaduje tuhle hlavicku? phru@ucw.cz)');

		// cookie settings
		$this->setCurlOption(CURLOPT_COOKIEJAR, $this->config->cookieFile());
		$this->setCurlOption(CURLOPT_COOKIEFILE, $this->config->cookieFile());

		// follow 3xx redirection
		$this->setCurlOption(CURLOPT_FOLLOWLOCATION, 1);

		// return content instead of writing it to stdout
		$this->setCurlOption(CURLOPT_RETURNTRANSFER, 1);
	}

	/**
	 * Closes CURL connection
	 */
	private function closeCurlHandle()
	{
		curl_close($this->curlHandle);
		$this->curlHandle = false;
	}

	/**
	 * Logins to server after sending headers
	 *
	 * @throws FioException	on error
	 */
	private function login()
	{
		$this->openCurlHandle($this->config->loginURL());

		$username = rawurlencode($this->config->username());
		$password = rawurlencode($this->config->password());
		$logintime = strval(time());
		$postData = "LOGIN_USERNAME=$username&LOGIN_PASSWORD=$password&SUBMIT=Odeslat&LOGIN_TIME=$logintime";

		// cookies settings
		$this->setCurlOption(CURLOPT_COOKIESESSION, 1);

		// POST settings
		$this->setCurlOption(CURLOPT_POST, 1);
		$this->setCurlOption(CURLOPT_POSTFIELDS, $postData);

		// send request
		$this->execCurlRequest();
		$this->closeCurlHandle();
	}

	/**
	 * Logouts from CURL connection
	 */
	private function logout()
	{
		if ($this->curlHandle)
			curl_close($this->curlHandle);

		if (file_exists($this->config->cookieFile()))
			unlink($this->config->cookieFile());
	}

	/**
	 * Downloads URL
	 *
	 * @param string $url
	 * @return string	Content of URL
	 */
	private function download($url)
	{
		$this->openCurlHandle($url);
		$result = $this->execCurlRequest();
		$this->closeCurlHandle();

		return $result;
	}

	/**
	 * Sets CURL option
	 *
	 * @throws FioException	on error
	 * @param string $option
	 * @param mixed $value 
	 */
	private function setCurlOption($option, $value)
	{
		if (!curl_setopt($this->curlHandle, $option, $value))
			throw new FioException('Cannot set a CURL option!');
	}

	/**
	 * Executes CURL requests
	 * 
	 * @throws FioException	on error
	 * @return string
	 */
	private function execCurlRequest()
	{
		$result = curl_exec($this->curlHandle);

		if ($result === false)
			throw new FioException('Curl request failed: ' . curl_error($this->curlHandle));

		return $result;
	}

	/**
	 * Config
	 *
	 * @var FioConfig
	 */
	private $config;
	
	/**
	 * CURL handle
	 *
	 * @var bool
	 */
	private $curlHandle = false;

}
