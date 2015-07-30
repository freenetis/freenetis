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

require_once "UnicreditException.php";

/**
 * Auxiliary class for parsing CSV bank account listings from czech bank "Unicredit banka".
 * The CSV listings are downloaded from the ebanking and stored at dafined path.
 * The CSV format looks like this:
Název účtu;Číslo účtu;Měna;Zůstatek
HLAVNÍ BÚ V BALÍČKU (PO);1002392380;CZK;0,000
Účet;Částka;Měna;Datum zaúčtování;Valuta;Banka;Název banky;Název banky;Číslo účtu;Název účtu;Adresa;Adresa;Adresa;Detaily transakce;Detaily transakce;Detaily transakce;Detaily transakce;Detaily transakce;Detaily transakce;Konstatní kód;Variabilní kód;Specifický kód;Platební titul;Reference
1002392380;600,000;CZK;2012-02-24;2012-02-24;;;;;;;;;VKLAD HOTOVOSTI  V CZK;;;;;;0558;470500;;;
** {etc.}

 * @abstract Class for parsing bank account listings from czech bank "Unicredit banka".
 * @author Petr Hruska, Lukas Turek, Tomas Dulik, Jiri Svitak, Ondrej Fibich
 * @copyright 2009-2011 Petr Hruska, Lukas Turek, o.s. CZF-Praha, Tomas Dulik, Jiri Svitak, o.s. UnArt
 * @link http://www.praha12.net
 */
class UnicreditParser
{
	
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
	private static $bank_nr = 2700;
	
	/**
	 * Date from
	 *
	 * @var string
	 */
	private static $from = NULL;
	
	/**
	 * Date to
	 *
	 * @var string 
	 */
	private static $to = NULL;

	/**
	 * All fields available must be used and sorted alphabetically
	 * 
	 * @var array[string]
	 */
	private static $fields = array
	(
		'ucet' => 'Účet',
		'castka' => 'Částka',
		'mena' => 'Měna',
		'datum' => 'Datum zaúčtování',
		'datum_valuta' => 'Valuta',
		'kod_banky' => 'Banka',
		'nazev_banky' => 'Název banky',
		'nazev_banky_2' => 'Název banky',
		'protiucet' => 'Číslo účtu',
		'nazev_protiuctu' => 'Název účtu',
		'adresa' => 'Adresa',
		'adresa_2' => 'Adresa',
		'adresa_3' => 'Adresa',
		'zprava' => 'Detaily transakce',
		'zprava_2' => 'Detaily transakce',
		'zprava_3' => 'Detaily transakce',
		'zprava_4' => 'Detaily transakce',
		'zprava_5' => 'Detaily transakce',
		'zprava_6' => 'Detaily transakce',
		'ks' => 'Konstatní kód',
		'vs' => 'Variabilní kód',
		'ss' => 'Specifický kód',
		'platebni_titul' => 'Platební titul',
		'reference' => 'Reference',
	);

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
		$header['account_nr'] = self::$account_nr;
		$header['bank_nr'] = self::$bank_nr;
		$header['from'] = self::$from;
		$header['to'] = self::$to;
		
		return $header;
	}

	/**
	 * The core of the parsing is done by this function.
	 * 
	 * @param string $csv string containing the original csv file.
	 * @return array[array]	Integer-indexed array of associative arrays.
	 *						Each associative array represents one line of the CSV
	 * @throws UnicreditException
	 */
	public static function parseCSV($csv)
	{
		$sum = 0;
		$data = array();
		$keys = array_keys(self::$fields);
		$number = 0;
		
		foreach (explode("\n", $csv) as $line)
		{
			$line = trim($line);
			
			// next line if line is empty
			if (empty($line))
			{
				continue;
			}

			// second line of file - account name, account number, currency, account balance
			if ($number == 1)
			{
				$line_arr = explode(';', $line);
				
				if (count($line_arr) < 4)
				{
					throw new UnicreditException('Nesprávný formát na prvním řádku.');
				}
				
				self::$account_nr = $line_arr[1];
			}
			// 3rd line, checking column header names and count
			else if ($number == 2)
			{
				self::checkHeaders($line);
			}
			// data lines
			else if ($number >= 3)
			{
				// split each line into assoc. array
				$cols = self::parseLine($line, $keys);
				$sum += $cols['castka'];
				
				if (empty(self::$from))
				{
					self::$from = self::$to = $cols['datum'];
				}
				else
				{
					self::$to = $cols['datum'];
				}
				
				$data[] = $cols;
			}
			// next line
			$number++;
		}

		if ($number <= 1)
		{
			throw new UnicreditException('CSV soubor není kompletní.');
		}
		
		return $data;
	}

	/**
	 * Checks headers
	 *
	 * @param integer $line 
	 * @throws UnicreditException
	 */
	private static function checkHeaders($line)
	{
		$expected = implode(';', self::$fields);
		
		if ($line != $expected)
		{
			throw new UnicreditException(
					"Nelze parsovat hlavičku Unicredit výpisu. " .
					"Ujistěte se, že jste zvolili správný formát " .
					"souboru k importu v internetovém bankovnictví." .
					"\n$line\n$expected"
			);
		}
	}

	/**
	 * Normalize string amount to double value
	 *
	 * @param string $amount
	 * @return double
	 * @throws UnicreditException
	 */
	private static function normalizeAmount($amount)
	{
		$amount = str_replace(array(' ', ','), array('', ''), $amount);

		if (!is_numeric($amount))
		{
			throw new UnicreditException('Chybný formát částky převodu.');
		}

		return doubleval($amount/10);
	}

	/**
	 * Parse line of dump
	 *
	 * @param string $line
	 * @param array $keys
	 * @return array
	 * @throws UnicreditException
	 */
	private static function parseLine($line, $keys)
	{
		$cols = explode(';', $line);
		
		if (count($cols) != count(self::$fields))
		{
			throw new UnicreditException(__(
					'Wrong count of fields (expected %d, get %d in line: %s)',
					array(count(self::$fields), count($cols), $line)
			));
		}
		
		// Convert to associative array
		$cols = array_combine($keys, $cols);

		// Amount has to be converted
		$cols['castka'] = self::normalizeAmount($cols['castka']);

		// Trim leading zeros from VS
		$cols['vs'] = ltrim($cols['vs'], '0');
		
		return $cols;
	}
}
