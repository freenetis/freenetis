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

require_once 'UnicreditParser.php';

/**
 * Main class for parsing bank account listings from czech bank "Unicredit banka".
 * The listings are downloaded from the ebanking web application.
 *
 * Additional changes for Freenetis (Jiri Svitak 2011-08-18):
 * added file import support
 * other improvements that enable cooperation with Freenetis
 *
 * @abstract Class for parsing bank account listings from czech bank "Unicredit banka".
 * @author Petr Hruska, Lukas Turek, Jiri Svitak, David Kuba, Ondrej Fibich
 * @copyright 2009-2011 Petr Hruska, Lukas Turek, o.s. CZF-Praha, Jiri Svitak, o.s. Unart
 * @link http://www.praha12.net
 */
class UnicreditImport
{

	/**
	 * Gets data from manully imported csv file.
	 * 
	 * @throws UnicreditException	on error
	 * @author Jiri Svitak
	 * @param string $file
	 */
	public static function getDataFromFile($file)
	{
		if (($csvData = file_get_contents($file)) === FALSE)
		{
			throw new UnicreditException(__('Cannot open uploaded bank listing file!'));
		}
		
		try
		{
			// try with utf8
			$data = UnicreditParser::parseCSV($csvData);
		}
		catch (UnicreditException $ex)
		{
			// continue trying with cp1250
			$data = UnicreditParser::parseCSV(iconv('cp1250', 'UTF-8', $csvData));
		}
			
		// clean up from needless attributes is not necessary due to changing Fio csv format
		self::correctData($data);

		return $data;
	}

	/**
	 * Returns bank listing header information, information is provided only after parsing.
	 * 
	 * @return array
	 */
	public static function getListingHeader()
	{
		return UnicreditParser::getListingHeader();
	}

	/**
	 * Corrects data.
	 * 
	 * @throws UnicreditException	on error
	 * @param array $data
	 */
	public static function correctData(&$data)
	{
		foreach ($data as &$row)
		{
			if ($row['mena'] != 'CZK')
			{
				throw new UnicreditException(__('Unknown currency %d.'), $row['mena']);
			}
			
			$row['nazev_banky'] .= $row['nazev_banky_2'];
			
			$row['adresa'] .= $row['adresa_2'];
			$row['adresa'] .= $row['adresa_3'];
			
			$row['zprava'] .= $row['zprava_2'];
			$row['zprava'] .= $row['zprava_3'];
			$row['zprava'] .= $row['zprava_4'];
			$row['zprava'] .= $row['zprava_5'];
			$row['zprava'] .= $row['zprava_6'];

			// convert from cents
			$row['castka'] /= 100;
		}
	}
}

