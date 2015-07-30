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

require_once "FioException.php";

/**
 * Auxiliary class for parsing CSV bank account listings from czech bank "FIO banka".
 * The CSV listings are downloaded from the ebanking web application.
 * The CSV format looks like this:
  Výpis vybraných transakcí na účtu "1234567890/2010"
  Majitel účtu: FirmaXY, Ulice 11/12, Město 13, 12345, Česká republika
  Vytvořeno v aplikaci Internetbanking: 16.01.2011 10:29:39
  Období: 16.12.2010 - 16.1.2011
  Počáteční stav účtu k 16.12.2010: 12 345,67 CZK
  Koncový stav účtu k 16.1.2011: 13 346,67 CZK
  Suma příjmů: +18 350,90 CZK
  Suma výdajů: -17 349,90 CZK

  Datum;ID pohybu;Kód banky;KS;Měna;Název banky;Název protiúčtu;Objem;Protiúčet;Provedl;Převod;SS;Typ;Upřesnění;Uživatelská identifikace;VS;Zpráva pro příjemce;
  16.12.2010;1115992591;6210;;CZK;BRE Bank S.A., organizační složka podniku;;2 000,00;670100-2202442842;;0;;Bezhotovostní příjem;;DOMINIK  BUREŠ;215;-IRONGATE VS:215;
** {etc.}
  Suma;0;;;;;;1 001,00;;;0;;;;;;;
 * @abstract Class for parsing bank account listings from czech bank "FIO banka".
 * @author Petr Hruska, Lukas Turek, Tomas Dulik, Jiri Svitak
 * @copyright 2009-2011 Petr Hruska, Lukas Turek, o.s. CZF-Praha, Tomas Dulik, Jiri Svitak, o.s. UnArt
 * @link http://www.praha12.net
 */
class FioParser
{
	const DATE_INDEX = 0;
	const AMOUNT_INDEX = 7;
	
	/**
	 * Account number
	 *
	 * @var integer
	 */
	private static $account_nr = 0;
	
	/**
	 * Bank number
	 * 
	 * @var integer
	 */
	private static $bank_nr;

	/**
	 * Date from
	 *
	 * @var string
	 */
	private static $from;
	
	/**
	 * Dateto
	 *
	 * @var string
	 */
	private static $to;
	
	/**
	 * Openning balance
	 *
	 * @var double
	 */
	private static $opening_balance;
	
	/**
	 * Closing balance
	 *
	 * @var double
	 */
	private static $closing_balance;
	
	/**
	 * All fields available must be used and sorted alphabetically
	 * 
	 * @var array[string]
	 */
	private static $fields = array
	(
		'datum' => 'Datum',
		'id_pohybu' => 'ID pohybu',
		'id_pokynu' => 'ID pokynu',
		'kod_banky' => 'Kód banky',
		'ks' => 'KS',
		'mena' => 'Měna',
		'nazev_banky' => 'Název banky',
		'nazev_protiuctu' => 'Název protiúčtu',
		'castka' => 'Objem',
		'protiucet' => 'Protiúčet',
		'provedl' => 'Provedl',
		'prevod' => 'Převod',
		'ss' => 'SS',
		'typ' => 'Typ',
		'upresneni' => 'Upřesnění',
		'identifikace' => 'Uživatelská identifikace',
		'vs' => 'VS',
		'zprava' => 'Zpráva pro příjemce',
	);

	/**
	 * Gets account number
	 *
	 * @return integer
	 */
	public static function getAccountNumber()
	{
		return self::$acc_nr;
	}

	/**
	 * Returns account number as array
	 * 
	 * @return associative array("account_nr"=>"XXXXXXX", "bank_nr" => "YYYY"
	 */
	public static function getAccountNumberAsArray()
	{
		$acc_arr = explode("/", self::$acc_nr);
		return array_combine(array("account_nr", "bank_nr"), $acc_arr);
	}

	/**
	 * Returns associative array containing important listing header information.
	 * Must be called after parsing.
	 * 
	 * @author Jiri Svitak
	 * @return header information
	 */
	public static function getListingHeader()
	{
		// account number
		$header["account_nr"] = self::$account_nr;
		$header["bank_nr"] = self::$bank_nr;
		// date from to
		$header["from"] = self::$from;
		$header["to"] = self::$to;
		// opening and closing balance
		$header["opening_balance"] = self::$opening_balance;
		$header["closing_balance"] = self::$closing_balance;
		
		return $header;
	}

	/**
	 * The core of the parsing is done by this function.
	 * 
	 * @param string $csv string containing the original csv file.
	 * @return array[array]	Integer-indexed array of associative arrays.
	 *						Each associative array represents one line of the CSV
	 * @throws FioException
	 */
	public static function parseCSV($csv)
	{
		$sum = 0;
		$state = 0;
		$data = array();
		$keys = array_keys(self::$fields);
		$number = 0;

		foreach (explode("\n", $csv) as $line)
		{
			$line = trim($line);

			// first line of file - get the account number
			if ($number == 0)
			{
				$line_arr = explode('"', $line);
				if (count($line_arr) < 2)
				{
					throw new FioException("Nemohu najít číslo účtu na prvním řádku.");
				}
				$acc_arr = explode("/", $line_arr[1]);
				if (count($acc_arr) < 2)
				{
					throw new FioException("Nemohu rozlišit číslo účtu a kód banky na prvním řádku.");
				}
				self::$account_nr = $acc_arr[0];
				self::$bank_nr = $acc_arr[1];
			}
			// 4th line, account date from and date to
			if ($number == 3)
			{
				$line_arr = explode(":", $line);
				if (count($line_arr) < 2)
				{
					throw new FioException("Nemohu najít datum od a do na čtvrtém řádku.");
				}
				$dates = explode("-", $line_arr[1]);
				if (count($dates) < 2)
				{
					throw new FioException("Nemohu najít oddělovač dat od a do na čtvrtém řádku.");
				}
				$from_arr = explode(".", $dates[0]);
				if (count($from_arr) < 3)
				{
					throw new FioException("Chybný formát datumu od na čtvrtém řádku.");
				}
				$from_timestamp = mktime(0, 0, 0, intval($from_arr[1]), intval($from_arr[0]), intval($from_arr[2]));
				self::$from = date("Y-m-d", $from_timestamp);
				$to_arr = explode(".", $dates[1]);
				if (count($to_arr) < 3)
				{
					throw new FioException("Chybný formát datumu do na čtvrtém řádku.");
				}
				$to_timestamp = mktime(0, 0, 0, intval($to_arr[1]), intval($to_arr[0]), intval($to_arr[2]));
				self::$to = date("Y-m-d", $to_timestamp);
			}
			// 5th line, opening balance
			if ($number == 4)
			{
				$line_arr = explode(":", $line);
				if (count($line_arr) < 2)
				{
					throw new FioException("Nemohu najít počáteční zůstatek.");
				}
				self::$opening_balance = self::normalizeAmount(str_replace("CZK", "", $line_arr[1])) / 100;
			}
			// 6th line, closing balance
			if ($number == 5)
			{
				$line_arr = explode(":", $line);
				if (count($line_arr) < 2)
				{
					throw new FioException("Nemohu najít konečný zůstatek.");
				}
				self::$closing_balance = self::normalizeAmount(str_replace("CZK", "", $line_arr[1])) / 100;
			}
			// 10th line, checking column header names and count
			if ($number == 9)
			{
				self::checkHeaders($line);
			}
			// data lines including last sum line, if last line encountered, then we are done
			if ($number >= 10)
			{
				// split each line into assoc. array
				$cols = self::parseLine($line, $keys);
				if ($cols['datum'] == 'Suma') // last line of file?
				{
					self::checkLastLine($cols, $sum);
					return $data;
				}
				else
				{
					$sum += $cols['castka'];
					$data[] = $cols;
				}
			}
			// next line
			$number++;
		}

		throw new FioException('CSV soubor není kompletní.');
	}

	/**
	 * Checks headers
	 *
	 * @param integer $line 
	 * @throws FioException
	 */
	private static function checkHeaders($line)
	{
		$expected = implode(';', self::$fields) . ';';
		if ($line != $expected)
			throw new FioException(__("Nelze parsovat hlavičku Fio výpisu. Ujistěte se, že jste zvolili všech 18 sloupců k importu v internetovém bankovnictví."));
	}

	/**
	 * Normalize string amount to double value
	 *
	 * @param string $amount
	 * @return double
	 * @throws FioException
	 */
	private static function normalizeAmount($amount)
	{
		$amount = str_replace(" ", "", $amount);
		$amount = str_replace(",", "", $amount);

		if (!is_numeric($amount))
			throw new FioException('Chybný formát částky převodu.');

		return doubleval($amount);
	}

	/**
	 * Parse line of dump
	 *
	 * @param string $line
	 * @param array $keys
	 * @return array
	 * @throws FioException
	 */
	private static function parseLine($line, $keys)
	{
		$cols = explode(';', $line);
		
		if (count($cols) != count(self::$fields) + 1)
			throw new FioException('Chybný počet políček v položce.');
		
		array_pop($cols);

		// Convert to associative array
		$cols = array_combine($keys, $cols);

		// Amount has to be converted
		$cols['castka'] = self::normalizeAmount($cols['castka']);

		return $cols;
	}

	/**
	 * Checks last line of dump
	 *
	 * @param array $cols
	 * @param integer $sum 
	 * @throws FioException
	 */
	private static function checkLastLine($cols, $sum)
	{
		$amount = $cols['castka'];
		
		if ($sum != $amount)
			throw new FioException("Chybný kontrolní součet částky ('$sum' != '$amount').");
	}

}
