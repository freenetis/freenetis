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
 * Cílem parseru je získat informace o faktuře, fakturovaných telefoních číslech
 * a o službách které byly číslům poskytnuty.
 * 
 * TENTO PARSER JE POUŽÍVÁN NA FAKTURY MEZI 2.2012 a 7.2013 PŘEVEDENÉ z XML DO CSV
 *
 * @author David Raška - jeffraska(at)gmail(dot)com
 * @version 1.0
 */
class Vodafone_Onenet_Csv_Le_7_2012 extends Parser_Phone_Invoice
{	
	const TAX				= 20;
	
	/*
	 * Service type constants
	 */
	const TYPE_UNKNOWN		= 0;
	const TYPE_CALL			= 1;
	const TYPE_VPN_CALL		= 2;
	const TYPE_FIXED_CALL	= 3;
	const TYPE_SMS			= 4;
	const TYPE_ROAMING_SMS	= 5;
	const TYPE_PAY_SERVICE	= 6;
	const TYPE_INTERNET		= 7;
	
	/*
	 * Text constants for recognizing type of service
	 */
	private static $call_service_ids = array
	(
		'Přesměrování hovoru do hlasové schránky',
		'Volání do hlasové schránky',
		'Z mobilní do mobilní sítě Vodafonu',
		'Z mobilní do ostatních národních mobilních sítí',
		'Tísňové a bezplatné volání',
		'Asistenční služby Telefónica O2',
	);
	
	private static $call_service_id_prefixes = array
	(
		'Zvláštní sazby ',
	);
	
	private static $roaming_call_service_id_prefixes = array
	(
		'Roaming - ',
		'Roaming příchozí - ',
		'Mezinár. volání z mobilní sítě do mobilní - ',
		'Mezinár. volání z mobilní sítě do mobilní-',
	);
	
	private static $vpn_call_service_ids = array
	(
		'Vnitrofiremní volání do mobilní sítě',
	);
	
	private static $fixed_call_service_ids = array
	(
		'Z mobilní do ostatních národních pevných sítí',
		'Z mobilní do pevné sítě Vodafonu',
		'Do pevné sítě Vodafonu',
	);
	
	private static $roaming_fixed_call_service_id_prefixes = array
	(
		'Mezinár. volání z mobilní sítě do pevné - ',
		'Mezinár. volání z mobilní sítě do pevné-',
	);
	
	private static $sms_service_ids = array
	(
		'SMS v síti Vodafone',
		'SMS mimo síť Vodafone',
		'SMS E-mail',
		'MMS do národních sítí',
	);
	
	private static $roaming_sms_service_ids = array
	(
		'SMS do zahraničí',
		'SMS Roaming příchozí',
		'MMS do zahraničí',
	);
	
	private static $roaming_sms_service_id_prefixes = array
	(
		'SMS Roaming Zóna',
		'SMS Roaming Zona',
		'MMS Roaming Zóna',
		'MMS Roaming Zona',
	);
	
	private static $pay_service_ids = array
	(
		'Prémiové služby',
		'Dárcovská SMS',
		'SMS hlasování',
		'objednací SMS',
		'Speciální služby na bázi Premium SMS',
		'Zátěžové SMS služby (televizní hlasování)',
		'SMS Info',
		'SMS platba u lékaře',
		'SMS mojelogo'
	);
	
	private static $internet_service_ids = array
	(
		'Vodafone Internet v mobilu',
		'Vodafone Internet v mobilu na den',
		'Připojení pro mobil - SUPER',
		'Připojení pro mobil - PREMIUM+',
		'Připojení na den',
		'Připojení pro mobil super - objem dat',
	);
	
	private static $roaming_internet_service_id_prefixes = array
	(
		'Internet Roaming - ',
	);
	
	/**
     * Parsovací funkce.
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
		$csv = str_getcsv(iconv("Windows-1250", "UTF-8", $text), ';');
		
		if ($csv[0] != "Podrobné vyúčtování používání služeb")
		{
			throw new Exception('Parser cannot parse this phone invoice. Unknown format');
		}
		
		// read phone numbers
		$i = 21;
		$phone_numbers = array();
		$match = array();
		while (preg_match('/[0-9]{12}/', $csv[$i], $match))
		{
			$phone_numbers[] = $match[0];
			$i += 7;
		}
		
		$phone_list_total_price = (float)str_replace(',', '.', $csv[$i + 1]);
		
		$invoice_header_size = $i + 7;
		
		$meta = new stdClass();
		
		// read metadata
		$meta->customer = $csv[$invoice_header_size + 146];
		$meta->ico = $csv[$invoice_header_size + 150];
		$meta->customer_id = $csv[$invoice_header_size + 151];
		$meta->wallet_id = $csv[$invoice_header_size + 152];
		$meta->date_from = $csv[$invoice_header_size + 154];
		$meta->date_to = $csv[$invoice_header_size + 156];
		$meta->invoice_number = $csv[$invoice_header_size + 158];
		
		// remove header
		array_splice($csv, 0, 264 + $invoice_header_size);
		
		$data = new Bill_Data();
		
		// set billing data
		$data->billing_period_from = DateTime::createFromFormat('j.n.Y', $meta->date_from);
		$data->billing_period_to = DateTime::createFromFormat('j.n.Y', $meta->date_to);
		$data->specific_symbol = $meta->wallet_id;
		$data->variable_symbol = $meta->invoice_number;
		
		// check time interval
		if ($data->billing_period_from->getTimestamp() < mktime(0, 0, 0, 2, 1, 2012) ||
			$data->billing_period_from->getTimestamp() > mktime(0, 0, 0, 8, 1, 2012) ||
			$data->billing_period_to->getTimestamp() < mktime(0, 0, 0, 2, 1, 2012) ||
			$data->billing_period_to->getTimestamp() > mktime(0, 0, 0, 8, 1, 2012))
		{
			throw new Exception('Parser cannot parse this phone invoice. Unknown format');
		}
		
		// get items count
		$total_items = (count($csv) - 1) / 24;
		
		$service = new stdClass();
		
		$total_price = 0.0;
		
		for ($i = 0; $i < $total_items; $i++)
		{
			// index of first field in row
			$start = $i*24;
			
			$service->pa = $csv[$start + 3];
			$service->group = $csv[$start + 5];
			$service->service = self::get_type($csv[$start + 7]);
			
			if ($service->service == self::TYPE_UNKNOWN)
			{
				throw new Exception(__('Unknown type of service: ').$csv[$start + 7]);
			}
			
			$service->description = $csv[$start + 7];
			$service->event_date = $csv[$start + 8];
			$service->pb = $csv[$start + 9];
			$service->duration = $csv[$start + 10];
			$service->data = $csv[$start + 12];
			// real cost 13
			$service->price = str_replace(',', '.', $csv[$start + 14]);
			
			$total_price += (float)$service->price;
			
			try
			{
				$services = $data->get_bill_number($service->pa);

				$services = self::append_service($services, $service, $meta);

				$data->set_bill_number($service->pa, $services);
			}
			catch (InvalidArgumentException $e)
			{
				$services = new Services($service->pa);

				$services = self::append_service($services, $service, $meta);

				$data->add_bill_number($service->pa, $services);
			}
		}
		
		$data->dph = (self::TAX / 100.0) * $total_price;
		$data->dph_rate = self::TAX;
		$data->total_price = $total_price;
		
		if ($integrity_test_enabled)
		{
			foreach ($data->bill_numbers as $p => $d)
				if (!in_array($p, $phone_numbers))
					throw new Exception(__("Some phones wasn't finded").$p);
		}
		
		if (abs($total_price - $phone_list_total_price) > 1)
		{
			throw new Exception('Sum of costs and total cost are not equal.');
		}
		
		return $data;
    }
	
	/**
	 * Adds new service to services
	 * 
	 * @param Services $services
	 * @param stdClass $data
	 * @param stdClass $meta
	 * @return Services
	 */
	private static function append_service(Services $services, stdClass $data, stdClass $meta)
	{
		switch ($data->service)
		{
			case self::TYPE_CALL: 
				$service = new Call_Service();
				$service->price = $data->price;
				$service->date_time = self::createFromEventDate($data->event_date);
				
				$service->number = $data->pb;
				$service->length = $data->duration;
				$service->description = $data->description;
				$service->period = period::NO_PERIOD;
								
				$services->add_call($service);
				break;
			
			case self::TYPE_VPN_CALL:
				$service = new Vpn_Call_Service();
				$service->price = $data->price;
				$service->date_time = self::createFromEventDate($data->event_date);
				
				$service->number = $data->pb;
				$service->length = $data->duration;
				$service->description = $data->description;
				
				$service->group = $data->group;
				$service->period = period::NO_PERIOD;
				
				$services->add_vpn_call($service);
				break;
			
			case self::TYPE_FIXED_CALL:
				$service = new Fixed_Call_Service();
				$service->price = $data->price;
				$service->date_time = self::createFromEventDate($data->event_date);
				
				$service->number = $data->pb;
				$service->length = $data->duration;
				$service->description = $data->description;
				
				//$service->destiny
				$service->period = period::NO_PERIOD;
				
				$services->add_fixed_call($service);
				break;

			case self::TYPE_SMS:
				$service = new Sms_Service();
				$service->price = $data->price;
				$service->date_time = self::createFromEventDate($data->event_date);
				
				$service->number = $data->pb;
				$service->description = $data->description;
				
				$service->period = period::NO_PERIOD;
				
				$services->add_sms($service);
				break;
			
			case self::TYPE_ROAMING_SMS:
				$service = new RoamingSms_Service();
				$service->price = $data->price;
				$service->date_time = self::createFromEventDate($data->event_date);
				
				$service->roaming_zone = $data->description;
				
				$services->add_roaming_sms($service);
				break;
			
			case self::TYPE_PAY_SERVICE:
				$service = new Pay_Service();
				$service->price = $data->price;
				$service->date_time = self::createFromEventDate($data->event_date);
				
				$service->number = $data->pb;
				$service->description = $data->description;
				
				$services->add_pay($service);
				break;

			case self::TYPE_INTERNET:
				$service = new Internet_Service();
				$service->price = $data->price;
				$service->date_time = self::createFromEventDate($data->event_date);
				
				$service->number = $data->pb;
				$service->transfered = $data->data;
					
				$service->period = period::NO_PERIOD;
				//$service->apn
				
				$services->add_internet($service);
				break;
		}
		
		return $services;
	}
	
	/**
	 * Function returns type of service
	 * 
	 * @param string $key	Text value of service
	 * @return integer
	 */
	private static function get_type($key)
	{
		$key = trim($key);
		
		if (in_array($key, self::$call_service_ids))
			return self::TYPE_CALL;
		
		if (in_array($key, self::$vpn_call_service_ids))
			return self::TYPE_VPN_CALL;
		
		if (in_array($key, self::$fixed_call_service_ids))
			return self::TYPE_FIXED_CALL;
		
		if (in_array($key, self::$sms_service_ids))
			return self::TYPE_SMS;
		
		if (in_array($key, self::$roaming_sms_service_ids))
			return self::TYPE_ROAMING_SMS;
		
		if (in_array($key, self::$pay_service_ids))
			return self::TYPE_PAY_SERVICE;
		
		if (in_array($key, self::$internet_service_ids))
			return self::TYPE_INTERNET;
		
		// call service prefix
		foreach (self::$call_service_id_prefixes as $p)
		{
			$pos = strpos($key, $p);
			if (!($pos === FALSE) && $pos == 0)
				return self::TYPE_CALL;
		}
		
		// roaming call service prefix
		foreach (self::$roaming_call_service_id_prefixes as $p)
		{
			$pos = strpos($key, $p);
			if (!($pos === FALSE) && $pos == 0)
				return self::TYPE_CALL;
		}
		
		// roaming fixed call service prefix
		foreach (self::$roaming_fixed_call_service_id_prefixes as $p)
		{
			$pos = strpos($key, $p);
			if (!($pos === FALSE) && $pos == 0)
				return self::TYPE_FIXED_CALL;
		}
		
		// roaming sms service prefix
		foreach (self::$roaming_sms_service_id_prefixes as $p)
		{
			$pos = strpos($key, $p);
			if (!($pos === FALSE) && $pos == 0)
				return self::TYPE_ROAMING_SMS;
		}
		
		// roaming internet service prefix
		foreach (self::$roaming_internet_service_id_prefixes as $p)
		{
			$pos = strpos($key, $p);
			if (!($pos === FALSE) && $pos == 0)
				return self::TYPE_INTERNET;
		}
		
		return self::TYPE_UNKNOWN;
	}
	
	/**
	 * Creates DateTime object from event date
	 * 
	 * @param string $event_date
	 * @return DateTime
	 */
	private static function createFromEventDate($event_date)
	{
		if (preg_match('/[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4} [0-9]{1,2}:[0-9]{2}/', $event_date))
		{
			return DateTime::createFromFormat('j.n.Y G:i', $event_date);
		}
		else
		{
			return DateTime::createFromFormat('j.n.Y', $event_date);
		}
	}
}