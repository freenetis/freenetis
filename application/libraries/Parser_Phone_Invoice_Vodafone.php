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

define("PVI_MODELS_PATH", APPPATH . "libraries/parser_vodafone_invoice_models");

require_once PVI_MODELS_PATH . "/Bill_Data.php";
require_once PVI_MODELS_PATH . "/Services.php";
require_once PVI_MODELS_PATH . "/Call_Service.php";
require_once PVI_MODELS_PATH . "/Sms_Service.php";
require_once PVI_MODELS_PATH . "/Internet_Service.php";
require_once PVI_MODELS_PATH . "/Vpn_Call_Service.php";
require_once PVI_MODELS_PATH . "/Fixed_Call_Service.php";
require_once PVI_MODELS_PATH . "/Pay_Service.php";
require_once PVI_MODELS_PATH . "/RoamingSms_Service.php";


/**
 * Parser_Vodafone_Invoice je parser faktur telefoního operátora Vodafone.
 * Faktury Vodafone jsou ukládány v PDF, na které nelze užít unixových utilit pdfto*
 * Tento parser zpracovává vstup z programu Adobe Reader. Zkopírovaný uživatelem
 * pomocí CTRL+A.
 *
 * Cílem parseru je získat informace o faktuře, fakturovaných telefoních číslech
 * a o službách které byly číslům poskytnuty.
 * 
 * TENTO PARSER JE POUŽÍVÁN NA FAKTURY DO DATA 08.2011 VČETNĚ, KDY PROBĚHLA ZMĚNA
 * FORMÁTU FAKTUR.
 *
 * @author Ondřej Fibich - ondrej.fibich(at)gmail(dot)com
 * @version 1.1
 */
class Parser_Phone_Invoice_Vodafone extends Parser_Phone_Invoice
{
    // Konstanty pro hlavičku faktury -->
    const BILL_INFO_STARTER = "èíslo úètu kód banky variabilní symbol specifický symbol vystavení";
    const BILL_INFO_REGEX = "^([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]{2})\.([0-9]{2})\.([0-9]{4})$";
    const BILL_INFO_PRICE_REGEX = "^Celkový základ DPH ([0-9]+) *%([0-9\, ]+)$";
    const BILL_INFO_DPH_REGEX = "^Celkem DPH ([0-9]+) *%([0-9\, ]+)$";
    const BILL_INFO_PERIOD_REGEX = "^Zúètovací období ([0-9]{2})\.([0-9]{2})\.([0-9]{4}) \- ([0-9]{2})\.([0-9]{2})\.([0-9]{4})$";
    const BILL_INFO_NUMBERS_COUNT_REGEX = "^Poèet telefonních èísel ([0-9]+)$";
    
    const BILL_EXTRACT_START_FL = "Podrobné vyúètování";
	
    public static $BILL_EXTRACT_START_SL = array
	(
		"p=pièka, Mp=mimo pièku, Vkn=víkend, +=hovor probìhl ve více obdobích",
		"Šp=špicka, MŠp=mimo špicku, Vkn=víkend, +=hovor probehl ve více obdobích"
    );
    // <-- Konstanty prohlavičku faktury

    const BILL_EXTRACT_DATA_LONG_CONNECTIONS = "^(Pøipojení na dlouho[^\(]*)\(([0-9]{2}).([0-9]{2}).([0-9]{4}) ([0-9]{2}):([0-9]{2})\) [0-9]+ ([0-9\, ]+) ([0-9]+) %";
    const BILL_EXTRACT_DATA_DAY_CONNECTIONS = "^(Internet v mobilu na den) ([0-9]+) ([0-9\, ]+) ([0-9]+) % ([0-9\, ]+)$";

    // Konstanty pro podrobný výpis -->
    const BILL_EXTRACT_PHONE_REGEX = "^Telefonní èíslo ([0-9]{3} [0-9]{3} [0-9]{3})( Tarif)?";
    // <-- Konstanty pro podrobný výpis

    /** Jméno pole v Services reprezentující dané služby */
    const SERVICE_ARRAY = 0;
    /** První řádek služby v poli $BILL_EXTRACT_SERVICES */
    const FLINE = 1;
    /** Druhý řádek služby v poli $BILL_EXTRACT_SERVICES  */
    const SLINE = 2;
    /** Datový řádek služby v poli $BILL_EXTRACT_SERVICES  */
    const REGEX = 3;
    /** Počet datových položek datového řádku služby v poli $BILL_EXTRACT_SERVICES  */
    const REGEX_COUNT = 4;
    /** Patička tabulky v poli $BILL_EXTRACT_SERVICES  */
    const LLINE = 5;

    /**
     * Pole parsovacích informací o službách.
     * Obsahuje pole o informaci o službě, ve kterém se v tomto pořadí nachází údaje:
     * První řádek pro zjištění služny; Druhý; Regulární výraz datového řádku;
     * Počet datových položek; Regulární výraz patičky.
     * @var array
     */
    protected static $BILL_EXTRACT_SERVICES = array
	(
        "vodafone" => array
		(
            "calls", "Sí Vodafone",
            "Datum Èas Období Volané èíslo Trvání Sleva Vyèerpáno Kè bez DPH",
            // den, měsíc, hodina, minuta, sekunda, období, číslo, trvání, sleva, ?cena?
            "^([0-9]{1,2}).([0-9]{1,2}). ([0-9]{2}):([0-9]{2}):([0-9]{2}) ([^0-9]+) (\+?[0-9]+) ([0-9]{2}:[0-9]{2}:[0-9]{2}) ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?$",
            10,
            "Celkem za Sí Vodafone [0-9]{2}:[0-9]{2}:[0-9]{2} ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?"
        ),
        "others" => array
		(
            "calls", "Ostatní národní mobilní sítì",
            "Datum Èas Období Volané èíslo Trvání Sleva Vyèerpáno Kè bez DPH",
            // den, měsíc, hodina, minuta, sekunda, období, číslo, trvání, sleva, ?cena?
            "^([0-9]{1,2}).([0-9]{1,2}). ([0-9]{2}):([0-9]{2}):([0-9]{2}) ([^0-9]+) (\+?[0-9]+) ([0-9]{2}:[0-9]{2}:[0-9]{2}) ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?$",
            10,
            "Celkem za Ostatní národní mobilní sítì [0-9]{2}:[0-9]{2}:[0-9]{2} ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?"
        ),
        "fixed" => array
		(
            "fixed_calls", "Pevná sí v ÈR",
            "Datum Èas Období Volané èíslo Trvání Cílová Oblast Sleva Vyèerpáno Kè bez DPH",
            // den, měsíc, hodina, minuta, sekunda, období, číslo, trvání, cíl, sleva, ?cena?
            "^([0-9]{1,2}).([0-9]{1,2}). ([0-9]{2}):([0-9]{2}):([0-9]{2}) ([^0-9]+) (\+?[0-9]+) ([0-9]{2}:[0-9]{2}:[0-9]{2}) ([^0-9]+) ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?$",
            11,
            "Celkem za Pevná sí v ÈR [0-9]{2}:[0-9]{2}:[0-9]{2} ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?"
        ),
        "fixed_international" => array
		(
            "fixed_calls", "Volání do zahr. - veø. tel. sí",
            "Datum Èas Období Volané èíslo Trvání Cílová Oblast Sleva Vyèerpáno Kè bez DPH",
            // den, měsíc, hodina, minuta, sekunda, období, číslo, trvání, cíl, sleva, ?cena?
            "^([0-9]{1,2}).([0-9]{1,2}). ([0-9]{2}):([0-9]{2}):([0-9]{2}) ([^0-9]+) (\+?[0-9]+) ([0-9]{2}:[0-9]{2}:[0-9]{2}) ([^0-9]+) ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?$",
            11,
            "Celkem za Volání do zahr. - veø. tel. sí [0-9]{2}:[0-9]{2}:[0-9]{2} ([0-9 ]+,[0-9]{2})"
        ),
        "sms" => array
		(
            "smss", "SMS sluby",
            "Datum Èas Období Volané èíslo Popis Sleva Kè bez DPH",
            // den, měsíc, hodina, minuta, sekunda, období, číslo, popis, sleva, ?cena?
            "^([0-9]{1,2}).([0-9]{1,2}). ([0-9]{2}):([0-9]{2}):([0-9]{2}) ([^0-9]+) (\+?[0-9]+) ([^0-9]+) ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?$",
            10,
            "Celkem za SMS sluby ([0-9 ]+,[0-9]{2})"
        ),
        "mms" => array
		(
            "smss", "MMS sluby",
            "Datum Èas Období Volané èíslo Popis Sleva Kè bez DPH",
            // den, měsíc, hodina, minuta, sekunda, číslo, popis, sleva, ?cena?
            "^([0-9]{1,2}).([0-9]{1,2}). ([0-9]{2}):([0-9]{2}):([0-9]{2}) (\+?[0-9]+) ([^0-9]+) ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?$",
            9,
            "Celkem za MMS sluby ([0-9 ]+,[0-9]{2})"
        ),
        "roaming_sms" => array
		(
            "roaming_smss", "Roaming - SMS",
            "Datum Èas Roamingová zóna Kè bez DPH",
            // den, měsíc, hodina, minuta, sekunda, popis, sleva, ?cena?
            "^([0-9]{1,2}).([0-9]{1,2}). ([0-9]{2}):([0-9]{2}):([0-9]{2}) (.+) ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?$",
            8,
            "Celkem za Roaming - SMS ([0-9 ]+,[0-9]{2})"
        ),
        "vpn" => array
		(
            "vpn_calls", "VPN Firma",
            "Datum Èas Období Volané èíslo Trvání Skupina Sleva Kè bez DPH",
            // den, měsíc, hodina, minuta, sekunda, období, číslo, trvání, skupina, sleva, ?cena?
            "^([0-9]{1,2}).([0-9]{1,2}). ([0-9]{2}):([0-9]{2}):([0-9]{2}) ([^0-9]+) (\+?[0-9]+) ([0-9]{2}:[0-9]{2}:[0-9]{2}) ([^0-9]+) ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?$",
            11,
            "Celkem za VPN Firma [0-9]{2}:[0-9]{2}:[0-9]{2} ([0-9 ]+,[0-9]{2})"
        ),
        "internet" => array
		(
            "internet", "Pøipojení",
            "Datum Èas Období Objem dat v kB APN Volné kB Kè bez DPH",
            // den, měsíc, hodina, minuta, sekunda, období, objem, apn, ?cena?
            "^([0-9]{1,2}).([0-9]{1,2}). ([0-9]{2}):([0-9]{2}):([0-9]{2}) ([^0-9]+) ([0-9]+) ([^0-9]+) ([0-9 ]+,[0-9]{2})$",
            9,
            "Celkem za Pøipojení [0-9]+ ([0-9 ]+,[0-9]{2})"
        ),
        "pays" => array
		(
            "pays", "Platby tøetím stranám",
            "Datum Èas Trvání Volané èíslo Popis Sleva Kè",
            // den, měsíc, hodina, minuta, sekunda, číslo, popis, sleva, ?cena?
            "^([0-9]{1,2}).([0-9]{1,2}). ([0-9]{2}):([0-9]{2}):([0-9]{2}) (\+?[0-9]+) ([^0-9]+) ([0-9 ]+,[0-9]{2})([0-9 ]+,[0-9]{2})?$",
            9,
            "Celkem za Platby tøetím stranám ([0-9 ]+,[0-9]{2})"
        )
    );

    /**
     * Převod volaného období
     * @param string $period  Perioda
     * @return integer  Perioda v číselné reprezentaci
     * @throws InvalidArgumentException  Při neznámém období
     */
    protected static function parse_period($period)
    {
        switch ($period)
        {
            case "p":
                return period::PEAK_HOURS;
                break;
            case "Mp":
                return period::OUT_PEAK_HOURS;
                break;
            case "Vkn":
                return period::WEEKEND;
                break;
            case "+=":
                return period::MORE_PERIOD;
                break;
            default:
                throw new InvalidArgumentException(__("Wrong period"));
        }
    }

    /**
     * @param string $emess
     * @param integer $line
     * @return string Chybová zpráva
     */
    protected static function em($emess, $line = -1)
    {
        return __("Error - cant parse invoice") . ".\n" . $emess
				. (($line > 0) ? "\n" . __("On line") . " " . $line : "");
    }

    /**
     * Převede číslo z tvaru 7 852 451,60 na float => 7852451.60
     * @param string $price
     * @return float
     */
    protected static function parse_price($price)
    {
        return floatval(str_replace(array(' ', ','), array('', '.'), $price));
    }

    /**
     * Převede telefoní číslo s mezerami do tvaru po sobě jdoucích číslic.
     * Pokud číslo neobsahuje předčíslý, doplní jej dle nastavení systému,
     * pokud se jedná o 9-ti místné číslo.
     * Předčíslí je ve tvaru: xxx
     *
     * @param string $number  Číslo ve tvaru [(+|00)xxx ]xxx xxx xxx
     * @staticvar string $default_prefix
     * @return string  Telefoní číslo
     * @throws InvalidArgumentException Při chybném čísle
     */
    protected static function parse_phone_number($number)
	{
		static $default_prefix = NULL;

		if ($default_prefix == NULL)
		{
			$default_country = new Country_Model(Settings::get("default_country"));

			if (!$default_country->id)
			{
				throw new ErrorException("Invalid default country, check table config and countries!!");
			}

			$default_prefix = $default_country->country_code;
		}

		// osekání mezer
		$number = str_replace(" ", "", $number);
		// kontrola formátu
		if (!mb_ereg("^(\+)?([0-9])+$", $number))
		{
			throw new InvalidArgumentException(__("Wrong phone number"));
		}
		// předčíslí
		if ($number[0] != '+')
		{
			// předvolbu 00 na začátku čísla zahodíme
			if (strncmp($number, "00", 2) == 0)
			{
				$number = substr($number, 2);
			}
			// pokud je číslo nemá předčíslí a je devítimístné vložím předčíslí z configu
			else if (mb_strlen($number) == 9)
			{
				$number = $default_prefix . $number;
			}
		}
		else
		{
			$number = substr($number, 1);
		}

		return $number;
	}

    /**
     * Parsovací funkce.
     * Funkce parsuje text z Adobe Readeru (pzn.: testováno na v 9).
     *
     * Obsahuje vnitřní testování správnosti parsování a integrity dat ve 2 bodech:
     * - Testuje zda-li odpovídá počet fakturovaných a parsovaných čísel.
     * - Testuje zda-li odpovídají ceny položek služeb s celkovou cenou za danou službu
     *   daného čísla.
     *
     * @param string $text		       Text k parsování(vstup)
     * @param boolean $integrity_test_enabled  Povolení testování integrity čísel
     *					       v podrobných výpisech
     * @return Bill_Data Data faktury
     * @throws Exception Při chybě při parsování
     * @throws InvalidArgumentException  Při prázdném vstupu
     */
    public static function parse($text, $integrity_test_enabled = TRUE)
    {
        if (empty($text))
        {
            throw new InvalidArgumentException(self::em(__("Empty input")));
        }

        $tl = mb_split("\n", $text); // vstup rozdělený na pole po řádcích
        $tlc = count($tl); // počet položek pole
        $tli = 0; // iterátor (aktuální index pole)

        $data = new Bill_Data(); // datový model
        $number_count = 0; // počet čísel na faktuře (pro testování výsledků parsování)
        //
        // 1. INFO o FAKTUŘE
        //
        while ($tli < $tlc && $line = rtrim($tl[$tli++]))
        { // dokud nenaleznu info o faktuře
            // konec textu?
            if (strcmp(mb_strtolower($line),
                            mb_strtolower(self::BILL_INFO_STARTER)) == 0)
            {
                if ($tli == $tlc)
                {
                    throw new Exception(self::em(
                            __("Cannot load heading of invoice") . " " .
                            __("End of input"),
                            $tli
                    ));
                }
                // další řádek obsahuje první data
                $line = rtrim($tl[$tli]);
                // kontrola formátu dat
                if (mb_eregi(self::BILL_INFO_REGEX, $line, $r))
                { // načtení prvních dat
                    $data->variable_symbol = $r[3];
                    $data->specific_symbol = $r[4];
                    $data->date_of_issuance->setDate($r[7], $r[6], $r[5]);
                }
                else
                { // chybný formát dat
                    throw new Exception(self::em(
                            __("Cannot load heading of invoice") . " " .
                            __("Wrong date format"),
                            $tli
                    ));
                }
                // pokračujeme v načítání ceny faktury
                if (($tli += 2) >= $tlc)
                { // posun o 2 řádky
                    throw new Exception(self::em(
                            __("Cannot load heading of invoice") . " " .
                            __("End of input"),
                            $tli
                    ));
                }
                $line = rtrim($tl[$tli]);
                // načtení ceny bez DPH
                if (mb_eregi(self::BILL_INFO_PRICE_REGEX, $line, $r))
                {
                    $data->dph_rate = $r[1];
                    $data->total_price = self::parse_price($r[2]);
                }
                else
                {
                    throw new Exception(self::em(
                            __("Cannot load heading of invoice") . " " .
                            __("Wrong date format"),
                            $tli
                    ));
                }
                //  pokračujeme v načítání DPH
                if (++$tli == $tlc)
                { // posun o řádek
                    throw new Exception(self::em(
                            __("Cannot load heading of invoice") .
                            __("End of input"),
                            $tli
                    ));
                }
                $line = rtrim($tl[$tli]);
                //načtení DPH
                if (mb_eregi(self::BILL_INFO_DPH_REGEX, $line, $r))
                {
                    $data->dph = self::parse_price($r[2]);
                }
                else
                {
                    throw new Exception(self::em(self::BILL_INFO_ERROR_PARSE, $tli));
                }
                // vyhledání zůčtovacího období
                while ($tli < $tlc && $line = rtrim($tl[$tli++]))
                {
                    if (mb_eregi(mb_strtolower(self::BILL_INFO_PERIOD_REGEX),
                                 mb_strtolower($line), $r))
                    {
                        $data->billing_period_from->setDate($r[3], $r[2], $r[1]);
                        $data->billing_period_to->setDate($r[6], $r[5], $r[4]);
                        break; // konec hledání období
                    }
                }

                // @todo: Zde by se mohli načítat další informace pro testování
                // pokračujeme ve vyhledání počtu fakturovaných čísel
                while ($tli < $tlc && $line = rtrim($tl[$tli++]))
                {
                    if (mb_eregi(self::BILL_INFO_NUMBERS_COUNT_REGEX, $line, $r))
                    {
                        $number_count = intval($r[1]);
                        break; // konec hledání počtu čísel
                    }
                }
                // konec čtení hlavičky
                break;
            }
        }
        // kontrola, zda-li je hlavička načtena
        if ($number_count <= 0)
        {
            throw new Exception(self::em(
                    __("Cannot load heading of invoice") .
                    __("End of input"),
                    $tli
            ));
        }

        // Data internetu pro dlouhé připojení se nezobrazuje v podrobném výpisu,
        // proto je potřeba jej načítat z prvních výpisů
        $pre_number = null;
        while ($tli < $tlc && $line = rtrim($tl[$tli++]))
        {
            try {
                // vyhledání čísla
                if (mb_eregi(self::BILL_EXTRACT_PHONE_REGEX, $line, $r))
                {
                    $pre_number = self::parse_phone_number($r[1]);
                    continue;
                }
                // vyhledání řádku s dlouhodobým připojením
                else if (mb_eregi(self::BILL_EXTRACT_DATA_LONG_CONNECTIONS, $line, $r) &&
                         $pre_number != null)
                {
                    $internet = new Internet_Service();
                    $internet->date_time->setDate($r[4], $r[3], $r[2]);
                    $internet->date_time->setTime($r[5], $r[6], "00");
                    $internet->apn = rtrim($r[1]);
                    $internet->period = period::NO_PERIOD;
                    $internet->transfered = 0;
                    $internet->price = self::parse_price($r[7]);

                    try
                    {
                        $data->add_bill_number($pre_number, new Services($pre_number));
                    }
                    catch (InvalidArgumentException $ignore)
                    { // číslo již existuje
                    }

                    $data->get_bill_number($pre_number)->add_internet($internet);
                }
				// vyhledání řádku s krátkodobým připojením
				else if (mb_eregi(self::BILL_EXTRACT_DATA_DAY_CONNECTIONS, $line, $r) &&
                         $pre_number != null)
				{
                    $internet = new Internet_Service();
					// neznám datum služby, použiju začátek intervalu
                    $internet->date_time = $data->billing_period_from;
					$internet->date_time->setTime("00", "00", "00");
                    $internet->apn = rtrim($r[1]);
                    $internet->period = period::NO_PERIOD;
                    $internet->transfered = 0;
                    $internet->price = self::parse_price($r[3]);
		    
                    try
                    {
                        $data->add_bill_number($pre_number, new Services($pre_number));
                    }
                    catch (InvalidArgumentException $ignore)
                    { // číslo již existuje
                    }

                    $data->get_bill_number($pre_number)->add_internet($internet);
				}
                //
                // 2. NALEZENÍ podrobných výpisů
                //
                // nalezení 2 po sobě jdoucích vět
                else if (strcmp(strtolower(self::BILL_EXTRACT_START_FL),
                                strtolower($line)) == 0)
                {
                    if ($tli == $tlc)
                    {
                        throw new Exception(self::em(
                                __("Cannot find detail dumps")
                        ));
                    }
                    $line = rtrim($tl[$tli++]);

					// not working after 2011-09
					foreach (self::$BILL_EXTRACT_START_SL as $line_search)
					{
						if (mb_strtolower($line) == mb_strtolower($line_search))
						{
							// nalezeny podrobné výpisy
							break 2;
						}
					}
					reset(self::$BILL_EXTRACT_START_SL);
                }
            }
            catch (InvalidArgumentException $e)
            {
                throw new Exception(self::em(
                        __("Error - during searching long term connections"), $line
                ));
            }
        }
        
        // kontrola nalezení výpisů
        if ($tli == $tlc)
        {
            throw new Exception(self::em(__("Cannot find detail dumps")));
        }

        //
        // 3. DETAILY ČÍSEL
        //

        $services = NULL;
        // testovací pole cen služeb, čísla indikují
        // ==0   -  Netestovat
        // <0    -  Testovat
        // >0    -  Číslo bylo testováno, hodnota je celková cena služeb
        $test = array
		(
            "vodafone" => 0, "others" => 0, "fixed" => 0, "fixed_international" => 0,
            "sms" => 0, "mms" => 0, "vpn" => 0, "internet" => 0, "pays" => 0,
            "roaming_sms" >= 0
        );
        // procházení čísel
        while ($tli < $tlc && $line = rtrim($tl[$tli++]))
        {
            if (mb_eregi(self::BILL_EXTRACT_PHONE_REGEX, $line, $r))
            {
                try
                { // zpracování čísla
                    $old_number = isset($number) ? $number : NULL;
                    $number = self::parse_phone_number($r[1]);
                    // nové číslo?
                    if ($old_number != $number)
                    {
                        // přidání čísla
                        if ($old_number != NULL)
                        {
                            // test služeb čísla na kompletnost
                            foreach ($test as $i => $v)
                            {
                                if ($v < 0)
                                {
                                    throw new Exception(
                                            url_lang::lang(
                                                    "texts.Near number %s wasnt found data for testing in group %s",
                                                    array($old_number, $i)
                                            )
                                    );
                                }
                                // reset testu
                                $test[$i] = 0;
                            }
                            // přidání čísla
                            try
                            {
                                $data->add_bill_number($old_number, $services);
                            }
                            catch (InvalidArgumentException $i)
                            {
                                // číslo již bylo přidáno, přidám již přidané
                                // internetové připojení do služeb
                                foreach ($data->get_bill_number($old_number)->internet as $net)
                                {
                                    $services->add_internet($net);
                                }
                                // změním služby
                                $data->set_bill_number($old_number, $services);
                            }
                        }
                        // vytvořím služby
                        $services = new Services($number);
                    }
                }
                catch (Exception $e)
                {
                    throw new Exception(self::em($e->getMessage(), $tli));
                }
                // vyhledávám služby
                while ($tli < $tlc && $line = rtrim($tl[$tli++]))
                {
                    // hledám služby
                    foreach (self::$BILL_EXTRACT_SERVICES as $index => $value)
                    {
                        // hledání služby, ověření úvodního řádku
                        if (strcmp(mb_strtolower($value[self::FLINE]),
                                   mb_strtolower($line)) == 0)
                        {
                            // konec textu?
                            if ($tli == $tlc)
                            {
                                throw new Exception(self::em(
                                        url_lang::lang(
                                                "texts.Cannot load services ".
                                                "- data missing"
                                        ), $tli
                                ));
                            }

                            // ověření 2. řádku
                            if (strcmp(mb_strtolower($value[self::SLINE]),
                                       mb_strtolower(rtrim($tl[$tli]))) != 0)
                            {
                                continue; // 2. řádek nenalezen, pokračuji dál v hledání
                            }
                            // posun o řádek
                            $tli++;
                            // práce s daty
                            while ($tli < $tlc && $line = rtrim($tl[$tli++]))
                            {
                                // kontrola datového řádku
                                if (mb_eregi($value[self::REGEX], $line, $r))
                                {
									// aktivace testování
									$test[$index] = -1;
                                    // extrakce dat
                                    try
                                    {
                                        switch ($index)
                                        {
                                            case "vodafone":
                                            case "others":
                                                $call = new Call_Service();
                                                $call->date_time->setDate(
                                                        $data->billing_period_to->format("Y"),
                                                        $r[2], $r[1]
                                                );
                                                $call->date_time->setTime($r[3], $r[4], $r[5]);
                                                $call->period = self::parse_period($r[6]);
                                                $call->number = self::parse_phone_number($r[7]);
                                                $call->length = $r[8];
                                                $price = self::parse_price($r[9]);
                                                if ($r[self::REGEX_COUNT] !== false)
                                                {
                                                    $price += self::parse_price($r[10]);
                                                }
                                                $call->price = $price;
                                                $services->add_call($call);
                                                break;
                                            case "fixed":
											case "fixed_international":
                                                $call = new Fixed_Call_Service();
                                                $call->date_time->setDate(
                                                        $data->billing_period_to->format("Y"),
                                                        $r[2], $r[1]
                                                );
                                                $call->date_time->setTime($r[3], $r[4], $r[5]);
                                                $call->period = self::parse_period($r[6]);
                                                $call->number = self::parse_phone_number($r[7]);
                                                $call->length = $r[8];
                                                $call->destiny = $r[9];
                                                $price = self::parse_price($r[10]);
                                                if ($r[self::REGEX_COUNT] !== false)
                                                {
                                                    $price += self::parse_price($r[11]);
                                                }
                                                $call->price = $price;
                                                $services->add_fixed_call($call);
                                                break;
                                            case "sms":
                                                $sms = new Sms_Service();
                                                $sms->date_time->setDate(
                                                        $data->billing_period_to->format("Y"),
                                                        $r[2], $r[1]
                                                );
                                                $sms->date_time->setTime($r[3], $r[4], $r[5]);
                                                $sms->period = self::parse_period($r[6]);
                                                $sms->number = self::parse_phone_number($r[7]);
                                                $sms->description = $r[8];
                                                $price = self::parse_price($r[9]);
                                                if ($r[self::REGEX_COUNT] !== false)
                                                {
                                                    $price += self::parse_price($r[10]);
                                                }
                                                $sms->price = $price;
                                                $services->add_sms($sms);
                                                break;
                                            case "roaming_sms":
                                                $sms = new RoamingSms_Service();
                                                $sms->date_time->setDate(
                                                        $data->billing_period_to->format("Y"),
                                                        $r[2], $r[1]
                                                );
                                                $sms->date_time->setTime($r[3], $r[4], $r[5]);
                                                $sms->roaming_zone = $r[6];
                                                $price = self::parse_price($r[7]);
                                                if ($r[self::REGEX_COUNT] !== false)
                                                {
                                                    $price += self::parse_price($r[8]);
                                                }
                                                $sms->price = $price;
                                                $services->add_roaming_sms($sms);
                                                break;
                                            case "mms":
                                                $mms = new Sms_Service();
                                                $mms->date_time->setDate(
                                                        $data->billing_period_to->format("Y"),
                                                        $r[2], $r[1]
                                                );
                                                $mms->date_time->setTime($r[3], $r[4], $r[5]);
                                                $mms->period = period::NO_PERIOD;
                                                $mms->number = self::parse_phone_number($r[6]);
                                                $mms->description = $r[7];
                                                $price = self::parse_price($r[8]);
                                                if ($r[self::REGEX_COUNT] !== false)
                                                {
                                                    $price += self::parse_price($r[9]);
                                                }
                                                $mms->price = $price;
                                                $services->add_sms($mms);
                                                break;
                                            case "pays":
                                                $pay = new Pay_Service();
                                                $pay->date_time->setDate(
                                                        $data->billing_period_to->format("Y"),
                                                        $r[2], $r[1]
                                                );
                                                $pay->date_time->setTime($r[3], $r[4], $r[5]);
                                                $pay->number = self::parse_phone_number($r[6]);
                                                $pay->description = $r[7];
                                                $price = self::parse_price($r[8]);
                                                if ($r[self::REGEX_COUNT] !== false)
                                                {
                                                    $price += self::parse_price($r[9]);
                                                }
                                                $pay->price = $price;
                                                $services->add_pay($pay);
                                                break;
                                            case "vpn":
                                                $call = new Vpn_Call_Service();
                                                $call->date_time->setDate(
                                                        $data->billing_period_to->format("Y"),
                                                        $r[2], $r[1]
                                                );
                                                $call->date_time->setTime($r[3], $r[4], $r[5]);
                                                $call->period = self::parse_period($r[6]);
                                                $call->number = self::parse_phone_number($r[7]);
                                                $call->length = $r[8];
                                                $call->group = $r[9];
                                                $price = self::parse_price($r[10]);
                                                if ($r[self::REGEX_COUNT] !== false)
                                                {
                                                    $price += self::parse_price($r[11]);
                                                }
                                                $call->price = $price;
                                                $services->add_vpn_call($call);
                                                break;
                                            case "internet":
                                                $net = new Internet_Service();
                                                $net->date_time->setDate(
                                                        $data->billing_period_to->format("Y"),
                                                        $r[2], $r[1]
                                                );
                                                $net->date_time->setTime($r[3], $r[4], $r[5]);
                                                $net->period = self::parse_period($r[6]);
                                                $net->transfered = intval($r[7]);
                                                $net->apn = $r[8];
                                                $net->price = self::parse_price($r[9]);
                                                $services->add_internet($net);
                                                break;
                                            default:
												// tohle by se nemělo nikdy stát :-)
												throw new Exception("Error statement " . $number . " " . $index);
                                        }
                                    }
                                    catch (InvalidArgumentException $e)
                                    {
                                        throw new Exception(self::em(
                                            __("Error - during extraction of data") .
                                            $e->getMessage(), $tli
                                        ));
                                    }
                                }
                                else
                                {
                                    // test celkového součtu služeb kategorie
                                    // pokud naleznu patičku, testuji
                                    if (mb_eregi($value[self::LLINE], $line, $r))
                                    {
                                        // načtení z patičky
                                        $tot_price = self::parse_price($r[1]);
                                        if (count($r) > 2)
                                        {
                                            $tot_price += self::parse_price($r[2]);
                                        }
                                        // získání testovacích dat
                                        $tot_calc_price = 0;
                                        foreach ($services->$value[self::SERVICE_ARRAY] as $s)
                                        {
                                            $tot_calc_price += $s->price;
                                        }
                                        // vodafone a ostatni sdili pole => nutny odpočet
                                        if ($index == "others" && $test["vodafone"] > 0)
                                        {
                                            $tot_calc_price -= $test["vodafone"];
                                        }
                                        // pevna a pevna mezinarodni sdili pole => nutny odpocet
                                        else if ($index == "fixed_international" &&
                                                 $test["fixed"] > 0)
                                        {
                                            $tot_calc_price -= $test["fixed"];
                                        }
                                        // mms a sms sdili pole => nutny odpocet
                                        else if ($index == "mms" &&
                                                 $test["sms"] > 0)
                                        {
                                            $tot_calc_price -= $test["sms"];
                                        }
                                        // mms a sms sdili pole => nutny odpocet
                                        else if ($index == "sms" &&
                                                 $test["mms"] > 0)
                                        {
                                            $tot_calc_price -= $test["mms"];
                                        }
                                        // zaokrouhlední na haléře
                                        $tot_price = round($tot_price, 2);
                                        $tot_calc_price = round($tot_calc_price, 2);
                                        // test správnosti dat
                                        if ($tot_price != $tot_calc_price)
                                        {
                                            throw new Exception(self::em(
                                                    url_lang::lang(
                                                            "texts.Near number %s ".
                                                            "differs from price of".
                                                            " services: %s\nCalculated ".
                                                            "price: %s\nInvoiced price %s",
                                                            array(
                                                                $number, $index,
                                                                $tot_calc_price, $tot_price
                                                            )
                                                    ),
                                                    $tli
                                            ));
                                        }
                                        // uložím testovaná data
                                        $test[$index] = $tot_price;
                                    }
                                    break;
                                }
                            }
                            // pokračuji v hledání služeb
                            break;
                        }
                    }
                    reset(self::$BILL_EXTRACT_SERVICES);
                    // hledám číslo
                    if (mb_eregi(self::BILL_EXTRACT_PHONE_REGEX, $line))
                    {
                        // vrátím se na předchozí řádek a na začátek cyklu, kde se číslo vyhodnotí
                        $tli--;
                        continue 2;
                    }
                }
                // přidání čísla
                try
                {
                    // test služeb čísla na kompletnost
                    foreach ($test as $i => $v)
                    {
                        if ($v < 0)
                        {
                            throw new Exception(self::em(
                                    url_lang::lang(
                                            "texts.Near number %s wasnt found data for testing in group",
                                            $old_number
                                    )
                            ));
                        }
                        // reset testu
                        $test[$i] = 0;
                    }
                    // přidání čísla (na konci dokumentu)
                    $data->add_bill_number($number, $services);
                }
                catch (InvalidArgumentException $e)
                {
                    throw new Exception(self::em($e->getMessage()));
                }
            }
        }

        // kontrola načtení všech čísel
        if ($integrity_test_enabled === TRUE &&
			$number_count != count($data->bill_numbers))
        {
            $missing = $number_count - count($data->bill_numbers);
            throw new Exception(self::em(
                __("Some phones wasn't founded") . ".\n" .
				$missing . " " .
				__("is missing from") ." " . $number_count . "."
            ));
        }

        return $data;
    }

}
