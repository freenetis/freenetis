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

require_once 'FioConfig.php';
require_once 'FioConnection.php';
require_once 'FioParser.php';

/**
 * Main class for parsing bank account listings from czech bank "FIO banka".
 * The listings are downloaded from the ebanking web application.
 *
 * Additional changes for Freenetis (Jiri Svitak 2011-08-18):
 * added file import support
 * other improvements that enable cooperation with Freenetis
 *
 * @abstract Class for parsing bank account listings from czech bank "FIO banka".
 * @author Petr Hruska, Lukas Turek, Jiri Svitak
 * @copyright 2009-2011 Petr Hruska, Lukas Turek, o.s. CZF-Praha, Jiri Svitak, o.s. Unart
 * @link http://www.praha12.net
 */
class FioImport
{
	/**
	 * Gets data from internet banking directly.
	 * 
	 * @param string $fromDate
	 * @param string $username
	 * @param string $password
	 * @param integer $accountNumber
	 * @param integer $viewName
	 * @return array
	 */
	public static function getData($fromDate, $username, $password, $accountNumber, $viewName)
	{
		$downloadConfig = new FioConfig($username, $password, $accountNumber, $viewName);
		$connection = new FioConnection($downloadConfig);
		
		$csvData = $connection->getCSV($fromDate, null);
		$csvData = iconv('cp1250', 'UTF-8', $csvData);
		$data = FioParser::parseCSV($csvData);
		
		self::correctData($data);
		return $data;
	}

	/**
	 * Gets data from manully imported csv file.
	 * 
	 * @throws FioException	on error
	 * @author Jiri Svitak
	 * @param string $file
	 */
	public static function getDataFromFile($file)
	{
		if (($csvData = file_get_contents($file)) === false)
			throw new FioException(__("Cannot open uploaded bank listing file!"));
		
		$csvData = iconv('cp1250', "UTF-8", $csvData);
		$data = FioParser::parseCSV($csvData);

		// clean up from needless attributes is not necessary due to changing Fio csv format
		self::correctData($data);

		return $data;
	}

	/**
	 * Returns bank listing header information, information is provided only after parsing.
	 * 
	 * @author Jiri Svitak
	 * @return array
	 */
	public static function getListingHeader()
	{
		return FioParser::getListingHeader();
	}

	/**
	 * Corrects data.
	 * 
	 * @throws FioException	on error
	 * @param array $data
	 */
	public static function correctData(&$data)
	{
		foreach ($data as &$row)
		{
			if ($row['mena'] != 'CZK')
				throw new FioException("Unknown currency {$row['mena']}!");


			// only transfer from Fio to Fio have 'nazev_protiuctu'
			// for accounts in other banks we have to derive account name
			if (!$row['nazev_protiuctu'] && $row['identifikace'])
				$row['nazev_protiuctu'] = $row['identifikace'];

			// convert from cents
			$row['castka'] /= 100; 

		}
	}
}

