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
 * TENTO PARSER JE POUŽÍVÁN NA FAKTURY Z VODAFONE ONENET V XML
 *
 * @author David Raška - jeffraska(at)gmail(dot)com
 * @version 1.0
 */
class Vodafone_Onenet_Xml extends Parser_Phone_Invoice
{	
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
		'MMS Roaming Zóna',
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
	);
	
	private static $internet_service_ids = array
	(
		'Vodafone Internet v mobilu na den',
		'Připojení pro mobil - STANDARD',
		'Připojení pro mobil - SUPER',
		'Připojení pro mobil - PREMIUM+',
		'Připojení na den',
		'Připojení pro mobil super - objem dat',
		'Mobilní připojení 4 GB',
	);
	
	private static $roaming_internet_service_id_prefixes = array
	(
		'Internet Roaming - ',
	);
	
	/**
	 * Reads base data required to load whole document
	 * 
	 * @return Object
	 */
	private static function read_meta_data(&$text)
	{
		$metadata = new stdClass();
		$str_list_array = array();
		
		$in_str_list = FALSE;
		$str_list = new stdClass();
		
		$xml = new XMLReader();
		
		$xml->XML($text);
		
		while($xml->read())
		{
			if ($xml->nodeType == XMLReader::ELEMENT)
			{
				switch ($xml->name)
				{
					case 'from_date':
						$metadata->from_date = substr($xml->readString(), 0, 10);
						break;
					case 'to_date':
						$metadata->to_date = substr($xml->readString(), 0, 10);
						break;
					case 'invoice_number':
						$metadata->invoice_number = $xml->readString();
						break;
					case 'str_list':
						$in_str_list = TRUE;
						break;
					case 'id':
						if ($in_str_list)
						{
							$str_list->id = $xml->readString();
						}
						break;
					case 'str':
						if ($in_str_list)
						{
							$str_list->str = $xml->readString();
						}
						break;
					case 'wallet_id':
						$metadata->wallet_id = $xml->readString();
						break;
				}
			}
			else if ($xml->nodeType == XMLReader::END_ELEMENT)
			{
				switch ($xml->name)
				{
					case 'str_list':
						$in_str_list = FALSE;
						$str_list_array[$str_list->id] = $str_list->str;
						$str_list = new stdClass();
						break;
				}
			}
		}
		
		$metadata->str_lists = $str_list_array;
		
		$xml->close();
		
		return $metadata;
	}

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
		$meta = self::read_meta_data($text);

		$data = new Bill_Data();
		
		// set billing data
		$data->billing_period_from = DateTime::createFromFormat('Y-m-d', $meta->from_date);
		$data->billing_period_to = DateTime::createFromFormat('Y-m-d', $meta->to_date);
		$data->specific_symbol = $meta->wallet_id;
		$data->variable_symbol = $meta->invoice_number;
		
		$xml = new XMLReader();
		$xml->XML($text);
		$xml_path = array();
		
		$total_bill_sum = FALSE;
		$total_cost = 0.0;
		$total_tax = 0.0;
		$service = new stdClass();
		
		$phone_list_total_cost = 0.0;
		$phone_list_total_cost_tax = 0.0;

		$phone_list = array();

		while ($xml->read())
		{
			if ($xml->nodeType == XMLReader::ELEMENT)
			{
				$xml_path[] = $xml->name;
				// set pointer to end
				end($xml_path);
				
				$prev = prev($xml_path);
				// item
				if ($prev == 'dur')
				{
					switch ($xml->name)
					{
						case 'pA':
							$service->pa = $xml->readString();
							break;
						case 'pB':
							$service->pb = $xml->readString();
							break;
						case 'service':
							$key = $meta->str_lists[trim($xml->readString())];
							$service->service = self::get_type($key);
							
							if ($service->service == self::TYPE_UNKNOWN)
							{
								throw new Exception(__('Unknown type of service: ').$key);
							}
							
							$service->description = $key;
							break;
						case 'event_date':
							$service->event_date = $xml->readString();
							break;
						case 'data':
							$service->data = $xml->readString();
							break;
						case 'cost':
							$service->price = (float)$xml->readString();
							break;
						case 'discount':
							$service->discount = (float)$xml->readString();
							break;
						case 'cost_tax':
							$service->cost_tax = (float)$xml->readString();
							break;
						case 'real_cost':
							$service->real_cost = (float)$xml->readString();
							break;
						case 'r_dur':
							$service->duration = gmdate('H:i:s', $xml->readString());
							break;
						case 'pGroup':
							$service->group = $xml->readString();
							break;
					}
				}
				// read billing sumary
				else if ($prev == 'bill_sum')
				{
					switch ($xml->name)
					{
						case 'service':
							if ($xml->readString() == '-2')
							{
								$total_bill_sum = TRUE;
							}
							break;
						case 'cost':
							if ($total_bill_sum)
							{
								$total_cost = (float)$xml->readString();
							}
							break;
						case 'cost_tax':
							if ($total_bill_sum)
							{
								$total_tax = (float)$xml->readString() - $total_cost;
							}
							break;
					}
				}
				else if ($prev == 'phone_list')
				{
					switch ($xml->name)
					{
						case 'pA':
							$phone_list[] = $xml->readString();
							break;
						case 'cost':
							$phone_list_total_cost += $xml->readString();
							break;
						case 'cost_tax':
							$phone_list_total_cost_tax += $xml->readString();
							break;
					}
				}
			}
			else if ($xml->nodeType == XMLReader::END_ELEMENT)
			{
				array_pop($xml_path);
				$end = end($xml_path);
				
				if ($xml->name == 'dur' && $end == 'Document')
				{
					if (round($service->price, 3) != round($service->real_cost - $service->discount, 3))
					{
						throw new Exception('Cost and Real cost are not equal');
					}
					
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
				else if ($xml->name == 'bill_sum')
				{
					$total_bill_sum = FALSE;
				}
			}
		}
		
		if ($integrity_test_enabled)
		{
			foreach ($data->bill_numbers as $p => $d)
				if (!in_array($p, $phone_list))
					throw new Exception(__("Some phones wasn't finded").$p);
		}
		
		if (round($total_cost) != round($phone_list_total_cost) ||
			round($total_cost+$total_tax) != round($phone_list_total_cost_tax))
		{
			throw new Exception('Sum of costs and total cost are not equal.');
		}
		
		$xml->close();
		
		$data->dph = $total_tax;
		$data->dph_rate = round((($total_cost  + $total_tax)/$total_cost - 1) * 100);
		$data->total_price = $total_cost;
		
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
				$service->date_time = DateTime::createFromFormat('Y-m-d\TH:i:s',
					$data->event_date);
				
				$service->number = $data->pb;
				$service->length = $data->duration;
				$service->description = $data->description;
				$service->period = period::NO_PERIOD;
								
				$services->add_call($service);
				break;
			
			case self::TYPE_VPN_CALL:
				$service = new Vpn_Call_Service();
				$service->price = $data->price;
				$service->date_time = DateTime::createFromFormat('Y-m-d\TH:i:s',
					$data->event_date);
				
				$service->number = $data->pb;
				$service->length = $data->duration;
				$service->description = $data->description;
				
				$service->group = $meta->str_lists[$data->group];
				$service->period = period::NO_PERIOD;
				
				$services->add_vpn_call($service);
				break;
			
			case self::TYPE_FIXED_CALL:
				$service = new Fixed_Call_Service();
				$service->price = $data->price;
				$service->date_time = DateTime::createFromFormat('Y-m-d\TH:i:s',
					$data->event_date);
				
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
				$service->date_time = DateTime::createFromFormat('Y-m-d\TH:i:s',
					$data->event_date);
				
				$service->number = $data->pb;
				$service->description = $data->description;
				
				$service->period = period::NO_PERIOD;
				
				$services->add_sms($service);
				break;
			
			case self::TYPE_ROAMING_SMS:
				$service = new RoamingSms_Service();
				$service->price = $data->price;
				$service->date_time = DateTime::createFromFormat('Y-m-d\TH:i:s',
					$data->event_date);
				
				$service->roaming_zone = $data->description;
				
				$services->add_roaming_sms($service);
				break;
			
			case self::TYPE_PAY_SERVICE:
				$service = new Pay_Service();
				$service->price = $data->price;
				$service->date_time = DateTime::createFromFormat('Y-m-d\TH:i:s',
					$data->event_date);
				
				$service->number = $data->pb;
				$service->description = $data->description;
				
				$services->add_pay($service);
				break;

			case self::TYPE_INTERNET:
				$service = new Internet_Service();
				$service->price = $data->price;
				$service->date_time = DateTime::createFromFormat('Y-m-d\TH:i:s',
					$data->event_date);
				
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
}