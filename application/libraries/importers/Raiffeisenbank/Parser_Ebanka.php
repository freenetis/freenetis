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

require_once 'Parser_Html_Table.php';
require_once 'RB_Importer.php';
require_once 'RB_Exception.php';

/**
 * Parser_Ebanka is a parser for getting data from bank account transaction listing
 * in the HTML format used by the Czech bank called "Ebanka" (now Raiffeisen Bank).
 *
 * The parsing is a bit peculiar, because Ebanka uses different format for
 * listings that are visible to general public (the "transparent" listing used
 * by NGOV non-profit organizations) and different format for regular listing used in the
 * ebanking application.
 * This parser autodetects these two formats and parses the data according to it.
 *
 * Benchmarks:
 * Machine: Notebook FSC Lifebook S7110, CPU: Pentium T2400 @ 1.8 GHz
 * Win XP SP2, Apache 2.2.3, PHP 5.2.0
 * Regular listing with 136 table rows (1 week listing): time=0.1 sec, memory=205 kB
 * Regular listing with 2175 table rows (whole year listing): time=1.6 sec, memory=205 kB
 * Transparent listing with 467 table rows: time=0.14 sec, memory=122 kB
 * 
 * 
 * @author Tomas <Dulik at unart dot cz>
 * @version 1.0
 */
class Parser_Ebanka extends Parser_Html_Table
{
	/**
	 * Poslední řetězec před začátkem hlavní <TABLE>
	 */
	const START_STRING="Pohyby na";
	
	/**
	 * U běžných (netransparentních) výpisů
	 */
	const YEAR_STRING="Bankovn";
	
	/**
	 * String before account
	 */
	const ACCOUNT_STRING="IBAN:";

	/**
	 * Rok výpisu ze záhlaví výpisu
	 *
	 * @var string|mixed
	 */
	protected $year = false;
	
	/**
	 * Obsahuje jméno funkce, která se má zavolat poté, co naparsujeme
	 * 1 kompletní řádek HTML tabulky.
	 * 
	 * Callback funkce, kterou můžeme volat např. pro uložení
	 * každého řádku výpisu do DB.
	 * 
	 * @see set_callback
	 * @var string|mixed
	 */
	protected $callback;
	
	/**
	 * $result je vkládán do 1. parametru $callback funkce,
	 * která přes něj předává jeden řádek výsledku.
	 * 
	 * Pokud $result není nastaven funkcí set_callback,
	 * pak jde je nastaven na instanci std_class
	 * 
	 * @var object 
	 */
	protected $result;

	/**
	 *  
	 * Function parses date from and to of the statement and statement number.
	 * 
	 * @author Tomas Dulik, Jiri Svitak
	 */
	protected function get_statement_number_and_interval()
	{
		// code added by jsvitak, parsing date from and to
		// hledej první "." v datumu
		while (($pos = strpos($this->buffer, "<")) === false &&
				$this->get_line());
		
		if ($pos === false)
		{
			throw new RB_Exception("Nemůžu najít první znak '.' v řetězci ' za [období] ...'");
		}

		$toPos = substr($this->buffer, 0, $pos);
		$parts = explode(' ', $toPos);

		// parse statement number
		$this->statement_number = intval($parts[3]);

		// parse date from and to
		$dates = explode('/', end($parts));
		$from_arr = array_map('intval', explode('.', $dates[0]));

		// save year for transfer creation purposes
		$this->year = $from_arr[2];

		$from_timestamp = mktime(0, 0, 0, $from_arr[1], $from_arr[0], $from_arr[2]);

		// save date from
		$this->from = date('Y-m-d', $from_timestamp);
		
		// date to is set
		if (isset($dates[1]))
		{
			$to_arr = array_map('intval', explode(".", $dates[1]));
			$to_timestamp = mktime(0, 0, 0, $to_arr[1], $to_arr[0], $to_arr[2]);

			// save date to
			$this->to = date('Y-m-d', $to_timestamp);
		}
		else
		// date to is not set, date from will be used
			$this->to = $this->from;
	}

	/**
	 * Sets account number
	 */
	protected function get_cislo_uctu()
	{
		while (($czPos = stripos($this->buffer, "CZ")) === false &&
		$this->get_line());  // hledej lomítko
		if ($czPos === false)
		{
			throw new RB_Exception("Nemůžu najít 'CZ' v IBAN čísle účtu");
		}
		else
		{
			$this->bank_nr = substr($this->buffer, $czPos + 4, 4);
			$account_nr = (int) substr($this->buffer, $czPos + 8, 16);
			$this->account_nr = "$account_nr";
		}
	}

	protected function get_balances()
	{
		$this->find_tags_and_trim(array(iconv("utf-8", "windows-1250", "Počáteční zůstatek"), self::START_STRING));
		$this->find_tag_and_trim("\"RIGHT\">");
		$raw_amount = substr($this->buffer, 0, strpos($this->buffer, "<"));
		if ($raw_amount == "&nbsp;")
		{
			$this->find_tag_and_trim("\"RIGHT\">");
			$raw_amount = substr($this->buffer, 0, strpos($this->buffer, "<"));
		}
		$this->opening_balance = str_replace(" ", "", $raw_amount);
		//echo $this->opening_balance;

		$this->find_tags_and_trim(array(iconv("utf-8", "windows-1250", "Konečný zůstatek"), self::START_STRING));
		$this->find_tag_and_trim("\"RIGHT\">");
		$raw_amount = substr($this->buffer, 0, strpos($this->buffer, "<"));
		if ($raw_amount == "&nbsp;")
		{
			$this->find_tag_and_trim("\"RIGHT\">");
			$raw_amount = substr($this->buffer, 0, strpos($this->buffer, "<"));
		}
		$this->closing_balance = str_replace(" ", "", $raw_amount);
		//echo $this->closing_balance;
		//echo $this->buffer;
		//die();
	}


	/**
	 * Gets amount
	 *
	 * @param string $field
	 * @return string 
	 */
	protected function get_amount($field)
	{
		$field = strip_tags($field);
		$field = str_replace(array(" ", " "), "", $field);
		return strtr($field, ",", ".");
	}

	/**
	 * V posledním sloupci HTML výpisu ebanka dává tyto poplatky: Poplatek, směna, zpráva.
	 * Poplatky jsou odděleny značkami <br>, u transp. výpisu <br/>
	 * 
	 * @param $field
	 * @param $transparent
	 * @return ineteger součet všech poplatků generovaných pro daný řádek
	 */
	protected function get_fee($field, $transparent)
	{
		$field = str_replace(array(" ", " "), "", $field);
		$field = strtr($field, ",", ".");
		
		if ($transparent)
		{
			$br_tag = "<br/>";
		}
		else
		{
			$br_tag = "<br>";
		}
		
		$arr = preg_split("/<br>/si", $field);
		$fee = 0;
		
		foreach ($arr as $value)
		{
			$fee += $value;
		}
		
		
		return $fee;
	}

	/**
	 * Gets data from transparent
	 */
	protected function get_data_from_transparent()
	{	
		$res = $this->result;
		if ($res == NULL)
		{
			$res = new stdClass();
		}
		$first = true;
		$line_nr = 0;
		$rb_importer = new RB_Importer();
		
		do
		{
			$status = $this->get_table_rows();
			$nr = count($this->matches[1]);
			$fields = str_replace(array("\r", "\n", "\t"), "", $this->matches[1]);
			
			for ($i = 0; $i < $nr; $i++)
			{
				$field_nr = $i % 6;
				$field = $fields[$i];
				switch ($field_nr)
				{
					case 0:   // příklad: 31.08.2008<br/>06:1
						$arr = explode("<br/>", $field);
						$arrDate = explode(".", $arr[0]);
						$res->date_time = $arrDate[0];
						break;

					case 1:   // Poznámky<br/>Název účtu plátce
						$field = html_entity_decode($field, ENT_QUOTES, "UTF-8");
						$arr = explode("<br/>", $field);
						$res->comment = $arr[0];
						$res->name = $arr[1];
						break;

					case 2:   //2x za sebou datum odepsání<br/>typ platby
						$arr = explode("<br/>", $field);
						$res->type = html_entity_decode($arr[2], ENT_QUOTES, "UTF-8");
						break;
					
					case 3:
						$arr = explode("<br/>", $field); //VS<br/>KS<br/>SS
						$res->variable_symbol = (int) $arr[0];
						$res->constant_symbol = $arr[1];
						$res->specific_symbol = (int) $arr[2];
						break;
					
					case 4:
						$res->amount = $this->get_amount($field); // částka
						break;
					
					case 5:
						$res->fee = $this->get_fee($field, TRUE); // fee
						$line_nr++;
						$res->number = $line_nr;
						
						//ted uz muzeme ulozit ziskane data do databaze:
						//if (isset($this->callback))
						//	call_user_func($this->callback, $res);
						$rb_importer->store_transfer_ebanka($res);
					
						break;
				} // switch
			} // for
		} while ($status !== false);
	}

	/**
	 * Gets data from regular
	 */
	protected function get_data_from_regular()
	{
		$res = $this->result;
		if ($res == NULL)
		{
			$res = new stdClass();
		}
		$first = true;
		$rb_importer = new RB_Importer();
		
		do
		{
			if (($status = $this->get_table_rows()) !== FALSE)
			{

				$nr = count($this->matches[1]);
				$fields = str_replace(array("\r", "\n", "\t"), "", $this->matches[1]);

				if ($first)
				{
					$i = 7;
					$first = false;
				}
				else
				{
					$i = 0;
				}

				for (; $i < $nr; $i++)
				{
					$field_nr = $i % 7;
					$field = $fields[$i];
					// odstraneni &nbsp;
					$field = html_entity_decode($field, ENT_QUOTES, "UTF-8");
					$field = str_replace(" ", "", $field);
					
					switch ($field_nr)
					{
						case 0:	// číslo výpisu, každý měsíc od 1 do N
							if (!is_numeric($field))
							{
								ob_flush();
								flush();
								throw new RB_Exception("Parser error: " .
										"očekával jsem číslo výpisu, ale dostal jsem:<br/>\n" . $field .
										"<br/> \nPoslední správně načtený řádek výpisu má číslo " . $res->number .
										"<br/> \nCelý vstupní buffer je: <br/>\n" . htmlentities($this->buffer)
										//,E_USER_ERROR
								);
							}
							$res->number = $field;
							break;
						
						case 1:   // datum a čas příklad: 08.08.<br>06:11
							$arr = preg_split("/<br>/si", $field);
							
							if (count($arr) < 2)
							{
								throw new RB_Exception("Parser error: " .
										"očekávám datum/čas jako dd.mm.&lt;br&gt;hh:mm ale dostal jsem:<br/>\n" . $field .
										"<br/> \nPoslední správně načtený řádek výpisu má číslo " . $res->number .
										"<br/> \nCelý vstupní buffer je: <br/>\n" . htmlentities($this->buffer), E_USER_ERROR);
							}
							else
							{
								$arrDate = explode(".", $arr[0]);
								if (count($arrDate) < 2)
									throw new RB_Exception("Parser error: " .
											"očekávám datum jako dd.mm. ale dostal jsem:<br/>\n" . $arr[0] .
											"<br/> \nPoslední správně načtený řádek výpisu má číslo " . $res->number .
											"<br/> \nCelý vstupní buffer je: <br/>\n" . htmlentities($this->buffer), E_USER_ERROR);

								$res->date_time = $this->year . "-" . $arrDate[1] . "-" . $arrDate[0] . " " . $arr[1];
							}
							break;
							
						case 2:   // Poznámky<br>Název účtu a<br>číslo účtu plátce
							$arr = preg_split("/<br>/si", $field);	  // dělelní dle <BR> nebo <br>
							if (isset($arr[0]))
							{
								$res->comment = $arr[0];
							}
							
							if (isset($arr[1]))
							{
								$res->name = $arr[1];
							}
							
							if (isset($arr[2]))
							{
								$account_arr = explode("/", $arr[2]);
								
								if (isset($account_arr[0]))
								{
									$res->account_nr = $account_arr[0];
								}
								
								if (isset($account_arr[1]))
								{
									$res->account_bank_nr = $account_arr[1];
								}
							}
							
							break;
						
						case 3:   //datum odepsání<br><br>typ platby
							$arr = preg_split("/<br>/si", $field);
							$res->type = $arr[2];
							break;
						
						case 4:   //SS<br>VS<br>KS
							$arr = preg_split("/<br>/si", $field);
							$res->variable_symbol = $arr[1];
							$res->constant_symbol = $arr[2];
							$res->specific_symbol = $arr[0];
							break;
						
						case 5:   // částka
							$res->amount = $this->get_amount($field);
							break;
						
						case 6:   // fee
							$res->fee = $this->get_fee($field, FALSE);
							//if (isset($this->callback)) call_user_func($this->callback, $res);
							$rb_importer->store_transfer_ebanka($res);
							/**
							 * ted uz muzeme ulozit ziskane data do databaze:
							 */
							break;
					}
				}
			}
		// dělej dokud nenajdeš konec souboru nebo tabulky
		} while ($status !== false && $this->table_end == false);
	}

	/**
	 * Parses url
	 *
	 * @param string $url
	 * @param integer $account_id 
	 */
	public function parse($url, $account_id=NULL)
	{
		$this->open($url);
		$this->get_charset();
		
		// Now: search for the begining of the table or the date
		$found = $this->find_tags_and_trim(array
		(
			self::YEAR_STRING, self::ACCOUNT_STRING, self::START_STRING
		));

		switch ($found)
		{
			case 0:  // období výpisu nalezeno = standardní (netransparentní) výpis
				$transparent = false;

				$this->get_statement_number_and_interval();
				
				// zkus ještě najít číslo účtu
				$found = $this->find_tags_and_trim(array(self::ACCOUNT_STRING, self::START_STRING));
				$this->get_cislo_uctu();

				// najdi počáteční a konečný zůstatek
				$this->get_balances();

				/*
				// chyba (toto by nikdy nemělo nastat)
				if ($found == 2)
				{
					throw new RB_Exception("Nemohu najít začátek tabulky: '" . self::START_STRING . "'");
				}
				// našel jsem START_STRING => číslo účtu nemám, konec switch-e
				else if ($found == 1)
				{
					break;
				}
				 *
				 */
				//else goto case 1: protože jsem našel číslo účtu

				$found = $this->find_tag_and_trim(self::START_STRING);
				break;

			case 1:  //nalezeno číslo účtu,
				$this->get_cislo_uctu();
				// najdi začátek výpisu
				$found = $this->find_tag_and_trim(self::START_STRING);
				// chyba (toto by nikdy nemělo nastat)
				if ($found === false)
				{
					throw new RB_Exception("Nemohu najít začátek tabulky: '" . self::START_STRING . "'");
				}
				break;
				
			case 2:  //nalezen start tabulky s výpisy, což znamená, že
				// období výpisu nenalezeno => transparentní výpis
				$transparent = true;
				// jako rok doplň aktuální rok
				$this->year = date("Y");
				break;
			
			case 3:
				throw new RB_Exception("Nemohu najít začátek tabulky nebo datum/rok");
				break;
		};


		if ($transparent)
		{
			$this->get_data_from_transparent();
		}
		else
		{
			$this->get_data_from_regular();
		}

		fclose($this->file);
	}

}
