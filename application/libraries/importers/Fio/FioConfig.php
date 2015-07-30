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
 * FIO importer config
 */
class FioConfig
{
	
	// these parameters should work for everyone
	
	const _baseURL = 'https://www.fio.cz';
	const _loginPath = '/scgi-bin/hermes/dz-homebanking.cgi';
	const _historyPath = '/scgi-bin/hermes/dz-pohyby.cgi?ID_ucet=';
	const _filterPath = '/scgi-bin/hermes/dz-pohyby.cgi?';
	const _downloadPath = '/scgi-bin/hermes/dz-pohyby.cgi?export=1&ID_ucet=';
	const _viewPath = '/scgi-bin/hermes/dz-pohyby.cgi?view_name_dz-pohyby.cgi=';

	// modify these parameters as needed
	
	/** temporary file with cookies, it's content allows everyone to access the account */
	const _cookieFile = 'cookiefile.txt';

	public function __construct($username, $password, $accountNumber, $viewName)
	{
		$this->_username = $username;
		$this->_password = $password;
		$this->_accountNumber = $accountNumber;
		$this->_viewName = $viewName;
	}

	/**
	 * Gets username
	 *
	 * @return string
	 */
	public function username()
	{
		return $this->_username;
	}

	/**
	 * Gets password
	 *
	 * @return string
	 */
	public function password()
	{
		return $this->_password;
	}

	/**
	 * Gets login URL
	 *
	 * @return string
	 */
	public function loginURL()
	{
		return self::_baseURL . self::_loginPath;
	}

	/**
	 * Gets filter URL
	 *
	 * @return string
	 */
	public function filterURL($dateFrom, $dateTo)
	{
		/*
		 * https://www.fio.cz/scgi-bin/hermes/dz-pohyby.cgi?x=27&y=9&
		 * pohyby_DAT_od=1.4.2010&pohyby_DAT_do=5.4.2010&protiucet=&
		 * kod_banky=&VS=&SS=&UID=&PEN_typ_pohybu=&smer=&castka_min=&castka_max=
		 */

		if (!$dateFrom && !$dateTo)
			throw new FioException('At least one date of time interval must be specified.');

		$params = "x=27&y=9&pohyby_DAT_od=$dateFrom&pohyby_DAT_do=$dateTo"
				. "&protiucet=&kod_banky=&VS=&SS=&UID=&PEN_typ_pohybu=&smer="
				. "&castka_min=&castka_max=";
		
		return self::_baseURL . self::_filterPath . $params;
	}

	/**
	 * Gets hisory URL
	 *
	 * @return string
	 */
	public function historyURL()
	{
		return self::_baseURL . self::_historyPath . rawurlencode($this->_accountNumber);
	}
	
	/**
	 * Gets download URL
	 *
	 * @return string
	 */
	public function downloadURL()
	{
		return self::_baseURL . self::_downloadPath . rawurlencode($this->_accountNumber);
	}

	/**
	 * Gets view URL
	 *
	 * @return string
	 */
	public function viewURL()
	{
		return self::_baseURL . self::_viewPath . rawurlencode($this->_viewName);
	}

	/**
	 * Gets cookie file
	 *
	 * @return string
	 */
	public function cookieFile()
	{
		return self::_cookieFile;
	}

	/**
	 * Username
	 *
	 * @var string
	 */
	private $_username;
	
	/**
	 * Password
	 *
	 * @var string
	 */
	private $_password;
	
	/**
	 * Account number
	 *
	 * @var integer 
	 */
	private $_accountNumber;
	
	/**
	 * View name
	 *
	 * @var string
	 */
	private $_viewName;

}
