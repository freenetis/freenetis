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
 * Controller contains methods which are accessed by AJAX and returned as 
 * JSON document. 
 * 
 * @author	Michal Kliment
 * @package Controller
 */
class Json_Controller extends Controller
{
	
	/**
	 * Send headers for JSON
	 * 
	 * @author Ondřej Fibich
	 */
	public static function send_json_headers()
	{
		@header('Cache-Control: no-cache, must-revalidate');
		@header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		@header('Content-type: application/json');
	}
	
	/**
	 * 	Send headers for each function
	 */
	public function __construct()
	{
		parent::__construct();
		//self::send_json_headers();
	}
	
	/**
	 * @see MY_Controller#is_preprocesor_enabled()
	 */
	protected function is_preprocesor_enabled()
	{
		return FALSE;
	}
	
	/**
	 * Function to return accounts belong to account type. Returned array has
	 * name of account as key and its ID as a value due to JSON eval order bug.
	 * 
	 * @author Michal Kliment
	 * @param number $origin_account_id
	 */
	public function get_accounts_by_type($origin_account_id = NULL)
	{
		// access control
		$origin_account = new Account_Model($origin_account_id);
		
		if ($origin_account->id == 0)
			Controller::error(RECORD);
		
		if (!$this->acl_check_new('Accounts_Controller', 'transfers', $origin_account->member_id))
			Controller::error(ACCESS);

		$id = $this->input->get('id');

		$accounts = ORM::factory('account')->get_some_doubleentry_account_names(
				$origin_account_id, $id
		);

		$arr_accounts = array();
		foreach ($accounts as $account)
		{	// convert the object into array (used for HTML select list)
			$name = $account->name . ' ' . $account->id . ' (' . $account->addr . ')';
			$arr_accounts[$name] = $account->id;
		}

		echo json_encode($arr_accounts);
	}
	
	/**
	 * Returns address from address server
	 *
	 * @author David Raška
	 */
	public function get_address()
	{
		if (Address_points_Controller::is_address_point_server_active())
		{
			$curl = new Curl_HTTP_Client();
			$result = $curl->fetch_url(Settings::get('address_point_url').server::query_string());

			if ($curl->get_http_response_code() == 200 && $result !== FALSE)
			
			if ($result !== FALSE)
			{
				echo $result;
			}
			else
			{
				json_encode(array());
			}
		}
		else
		{
			json_encode(array());
		}
	}
	
	/**
	 * Returns fee details in json format
	 *
	 * @author David Raška
	 */
	public function get_fee_by_id()
	{
		// access control
		if (!$this->acl_check_view('Fees_Controller', 'fees'))
			Controller::Error(ACCESS);

		$id = (int) $this->input->get('id');

		$fee_model = new Fee_Model($id);

		$fee = array(
			'id' =>		$fee_model->id,
			'from' =>	$fee_model->from,
			'to' =>		$fee_model->to,
			'type' =>	$fee_model->type_id
		);

		echo json_encode($fee);
	}

	/**
	 * Returns all fees belongs to fee type in json format
	 *
	 * @author Michal Kliment
	 */
	public function get_fees_by_type()
	{
		// access control
		if (!$this->acl_check_view('Fees_Controller', 'fees'))
			Controller::Error(ACCESS);

		$id = (int) $this->input->get('id');

		$fee_model = new Fee_Model();

		if ($id)
			$fees = $fee_model->get_all_fees_by_fee_type_id($id);
		else
			$fees = $fee_model->get_all_fees();

		$arr_fees = array();
		$arr_fees[0] = '----- ' . __('Select fee') . ' -----';

		foreach ($fees as $fee)
		{
			// tariff of membership interrupt can be add only by adding
			// of new membership interrupt
			if ($fee->special_type_id == Fee_Model::MEMBERSHIP_INTERRUPT)
				continue;

			// name is optional, uses it only if it is not empthy
			$name = ($fee->readonly) ? __('' . $fee->name) : $fee->name;
			$name = ($name != '') ? "- $name " : "";

			$from = str_replace('-', '/', $fee->from);
			$to = str_replace('-', '/', $fee->to);
			$arr_fees[$fee->id] = "$fee->fee " . __($this->settings->get('currency')) . " $name($from-$to)";
		}

		echo json_encode($arr_fees);
	}

	/**
	 *  Callback AJAX function to filter's whisper for member name
	 *
	 * @author Michal Kliment
	 */
	public function member_name()
	{
		$term = $this->input->get('term');

		$member_model = new Member_Model();

		$members = $member_model->like('name', $term)
				->orderby('name')
				->find_all()
				->select_list();

		echo json_encode(array_values($members));
	}

	/**
	 * Callback AJAX funxtion to obtain MAC address from given IP and subnet.
	 * 
	 * @author Ondřej Fibich
	 */
	public function obtain_mac_address()
	{
		$subnet_id = $this->input->get('subnet_id');
		$ip_address = $this->input->get('ip_address');
		
		$ip_address_model = new Ip_address_Model();
		
		// find gateway of subnet
		$gateway = $ip_address_model->get_gateway_of_subnet($subnet_id);

		if ($gateway && $gateway->id && valid::ip($ip_address))
		{
			$mac_address = '';
			
			// first try CGI scripts
			if (module::e('cgi'))
			{	
				$vars = arr::to_object(array
				(
					'GATEWAY_IP_ADDRESS'	=> $gateway->ip_address,
					'IP_ADDRESS'			=> $ip_address
				));
				
				$url = text::object_format($vars, Settings::get('cgi_arp_url'));
				
				$mac_address = trim(@file_get_contents($url));
			}
			
			// now try SNMP
			if (!valid::mac_address($mac_address) && module::e('snmp'))
			{
				try
				{
					$snmp = Snmp_Factory::factoryForDevice($gateway->ip_address);

					// try find MAC address in DHCP
					$mac_address = $snmp->getDHCPMacAddressOf($ip_address);
				}
				// MAC table is not in DHCP
				catch (DHCPMacAddressException $e)
				{
					try
					{
						// try find MAC address in ARP table
						$mac_address = $snmp->getARPMacAddressOf($ip_address);
					}
					catch(Exception $e)
					{
						Log::add_exception($e);
					}
				}
				catch (Exception $e)
				{
					Log::add_exception($e);
				}
			}
			
			if (valid::mac_address($mac_address))
			{
				die(json_encode(array
				(
					'state' => 1,
					'mac' => $mac_address
				)));
			}
			else
			{
				die(json_encode(array
				(
					'state' => 0,
					'message' => __('MAC not found')
				)));
			}
		}
		else
		{
			die(json_encode(array
			(
				'state' => 0,
				'message' => __('Invalid input data')
			)));
		}
	}


	/**
	 * Callback AJAX function to filter's whisper for organization identifier
	 *
	 * @author Michal Kliment
	 */
	public function organization_identifier()
	{
		$term = $this->input->get('term');

		$member_model = new Member_Model();

		$members = $member_model->where('organization_identifier !=', '')
				->like('organization_identifier', $term)
				->groupby('organization_identifier')
				->orderby('organization_identifier')
				->find_all()
				->select_list('id', 'organization_identifier');

		echo json_encode(array_values($members));
	}
	
	/**
	 * Callback AJAX function to filter's whisper for VAT organization identifier
	 *
	 * @author Michal Kliment
	 */
	public function vat_organization_identifier()
	{
		$term = $this->input->get('term');

		$member_model = new Member_Model();

		$members = $member_model->where('vat_organization_identifier !=', '')
				->like('vat_organization_identifier', $term)
				->groupby('vat_organization_identifier')
				->orderby('vat_organization_identifier')
				->find_all()
				->select_list('id', 'vat_organization_identifier');

		echo json_encode(array_values($members));
	}

	/**
	 * Callback AJAX function to filter's whisper for variable symbol
	 *
	 * @author Michal Kliment
	 */
	public function variable_symbol()
	{
		$term = $this->input->get('term');
		
		$variable_symbol_model = new Variable_Symbol_Model();
		
		$variable_symbols = $variable_symbol_model->like('variable_symbol', $term)
				->orderby('variable_symbol')
				->find_all()
				->select_list('id', 'variable_symbol');

		echo json_encode(array_values($variable_symbols));
	}

	/**
	 * Callback AJAX function to filter's whisper for town name
	 *
	 * @author Michal Kliment
	 */
	public function town_name()
	{
		$term = $this->input->get('term');

		$town_model = new Town_Model();

		$towns = $town_model->like('town', $term)
				->orderby('town')
				->groupby('town')
				->find_all()
				->select_list('id', 'town');

		echo json_encode(array_values($towns));
	}
	
	/**
	 * Callback AJAX function to filter's whisper for quarter name
	 *
	 * @author Michal Kliment
	 */
	public function quarter_name()
	{
		$term = $this->input->get('term');

		$town_model = new Town_Model();

		$towns = $town_model->like('quarter', $term)
				->orderby('quarter')
				->groupby('quarter')
				->find_all()
				->select_list('id', 'quarter');

		echo json_encode(array_values($towns));
	}
	
	/**
	 * Callback AJAX function to filter's whisper for ZIP code
	 *
	 * @author Michal Kliment
	 */
	public function zip_code()
	{
		$term = $this->input->get('term');

		$town_model = new Town_Model();

		$towns = $town_model->like('zip_code', $term)
				->orderby('zip_code')
				->groupby('zip_code')
				->find_all()
				->select_list('id', 'zip_code');

		echo json_encode(array_values($towns));
	}

	/**
	 * Callback AJAX function to filter's whisper for street name
	 *
	 * @author Michal Kliment
	 */
	public function street_name()
	{
		$term = $this->input->get('term');

		$street_model = new Street_Model();

		$streets = $street_model->like('street', $term)
				->orderby('street')
				->find_all()
				->select_list('id', 'street');

		echo json_encode(array_values($streets));
	}

	/**
	 * Callback AJAX function to filter's whisper for user name
	 *
	 * @author Michal Kliment
	 */
	public function user_name()
	{
		$term = $this->input->get('term');

		$user_model = new User_Model();

		$users = $user_model->like('name', $term)
				->groupby('name')
				->orderby('name')
				->find_all()
				->select_list();

		echo json_encode(array_values($users));
	}

	/**
	 * Callback AJAX function to filter's whisper for user surname
	 *
	 * @author Michal Kliment
	 */
	public function user_surname()
	{
		$term = $this->input->get('term');

		$user_model = new User_Model();

		$users = $user_model->like('surname', $term)
				->groupby('surname')
				->orderby('surname')
				->find_all()
				->select_list('id', 'surname');

		echo json_encode(array_values($users));
	}
	
	/**
	 * Callback AJAX function to filter's whisper for user fullname
	 * 
	 * @author Michal Kliment
	 */
	public function user_fullname()
	{
		$term = $this->input->get('term');

		$user_model = new User_Model();

		$users = $user_model->get_usernames($term);

		$arr_users = array();
		foreach ($users as $user)
			$arr_users[] = $user->username;

		echo json_encode($arr_users);
	}

	/**
	 * Callback AJAX function to filter's whisper for user login
	 *
	 * @author Michal Kliment
	 */
	public function user_login()
	{
		$term = $this->input->get('term');

		$user_model = new User_Model();

		$users = $user_model->like('login', $term)
				->groupby('login')
				->orderby('login')
				->find_all()
				->select_list('id', 'login');

		echo json_encode(array_values($users));
	}

	/**
	 * Callback AJAX function to filter's whisper for user e-mail
	 *
	 * @author Michal Kliment
	 */
	public function user_email()
	{
		$term = $this->input->get('term');

		$contact_model = new Contact_Model();

		$contacts = $contact_model->where('type', Contact_Model::TYPE_EMAIL)
				->like('value', $term)
				->groupby('value')
				->orderby('value')
				->find_all()
				->select_list('id', 'value');

		echo json_encode(array_values($contacts));
	}

	/**
	 * Callback AJAX function to filter's whisper for user phone
	 *
	 * @author Michal Kliment
	 */
	public function user_phone()
	{
		$term = $this->input->get('term');

		$contact_model = new Contact_Model();

		$contacts = $contact_model->where('type', Contact_Model::TYPE_PHONE)
				->like('value', $term)
				->groupby('value')
				->orderby('value')
				->find_all()
				->select_list('id', 'value');

		echo json_encode(array_values($contacts));
	}

	/**
	 * Callback AJAX function to filter's whisper for user ICQ
	 *
	 * @author Michal Kliment
	 */
	public function user_icq()
	{
		$term = $this->input->get('term');

		$contact_model = new Contact_Model();

		$contacts = $contact_model->where('type', Contact_Model::TYPE_ICQ)
				->like('value', $term)
				->groupby('value')
				->orderby('value')
				->find_all()
				->select_list('id', 'value');

		echo json_encode(array_values($contacts));
	}

	/**
	 * Callback AJAX function to filter's whisper for user jabber
	 *
	 * @author Michal Kliment
	 */
	public function user_jabber()
	{
		$term = $this->input->get('term');

		$contact_model = new Contact_Model();

		$contacts = $contact_model->where('type', Contact_Model::TYPE_JABBER)
				->like('value', $term)
				->groupby('value')
				->orderby('value')
				->find_all()
				->select_list('id', 'value');

		echo json_encode(array_values($contacts));
	}
	
	/**
	 * Performs action of mac_address_check jQuery form validator.
	 * Checks whether the MAC is unique on subnet.
	 *
	 * @author Ondrej Fibich 
	 */
	public function mac_address_check()
	{
		$mac = trim($this->input->get('mac'));
		$subnet = new Subnet_Model($this->input->get('subnet_id'));
		$ip_address_id = $this->input->get('ip_address_id');
		
		if (!$subnet || !$subnet->id)
		{
			die(json_encode(array
			(
				'state'		=> false,
				'message'	=> __('Subnet not exists.')
			)));
		}
		
		if ($subnet->is_mac_unique_in_subnet($mac, $ip_address_id))
		{
			die(json_encode(array('state' => true)));
		}
		else
		{
			$link = html::anchor(
					'/subnets/show/' . $subnet->id,
					__('subnet', NULL, 1), 'target="_blank"'
			);
			
			$m = 'MAC address of this interface is already in the '
				. 'selected %s assigned to another interface';
			
			die(json_encode(array
			(
				'state'		=> false,
				'message'	=> __($m, $link)
			)));
		}
	}
	
	/**
	 * Performs action of ip_address_check jQuery form validator.
	 *
	 * @author Ondrej Fibich 
	 */
	public function ip_address_check()
	{
		$edit_id = intval($this->input->get('ip_address_id'));
		$subnet_id = intval($this->input->get('subnet_id'));
		$ip_str = trim($this->input->get('ip_address'));
		
		if (!$subnet_id && !$ip_str)
		{
			die(json_encode(array('state' => true)));
		}
		
		$ip_model = new Ip_address_Model();
		$subnet_model = new Subnet_Model($subnet_id);
		
		if (!$subnet_model || !$subnet_model->id)
		{
			die(json_encode(array
			(
				'state'		=> false,
				'message'	=> __('Subnet not exists.')
			)));
		}
		
		$subnet = $subnet_model->get_net_and_mask_of_subnet();
		
		// checks mask
		if (!valid::ip_check_subnet(ip2long($ip_str), $subnet->net + 0, ip2long($subnet->netmask)))
		{
			die(json_encode(array
			(
				'state'		=> false,
				'message'	=> __('IP address does not match the subnet/mask.')
			)));
		}
		
		// checks if exists this ip in database		
		$ips = $ip_model->where(array
		(
			'ip_address'	=> $ip_str,
			'member_id'		=> NULL
		))->find_all();

		foreach ($ips as $ip)
		{
			// only for edit: check if ip_address is not still same 
			if ($edit_id && ($edit_id == $ip->id))
			{
				continue;
			}
			
			die(json_encode(array
			(
				'state'		=> false,
				'message'	=> __('IP address already exists.')
			)));
		}
		
		echo json_encode(array('state' => true));
	}

	/**
	 * Callback AJAX function to filter's whisper for iface MAC address
	 * 
	 * @author Michal Kliment
	 */
	public function iface_mac()
	{
		$term = $this->input->get('term');

		$iface_model = new Iface_Model();

		$ifaces = $iface_model->like('mac', $term)
				->groupby('mac')
				->orderby('mac')
				->find_all()
				->select_list('id', 'mac');

		echo json_encode(array_values($ifaces));
	}

	/**
	 * Callback AJAX function to filter's whisper for link name
	 * 
	 * @author Michal Kliment
	 */
	public function link_name()
	{
		$term = $this->input->get('term');

		$link_model = new Link_Model();

		$links = $link_model->like('name', $term)
				->groupby('name')
				->orderby('name')
				->find_all()
				->select_list();

		echo json_encode(array_values($links));
	}

	/**
	 * Callback AJAX function to filter's whisper for device name
	 * 
	 * @author Michal Kliment
	 */
	public function device_name()
	{
		$term = $this->input->get('term');

		$device_model = new device_Model();

		$devices = $device_model->like('name', $term)
				->groupby('name')
				->orderby('name')
				->find_all()
				->select_list();

		echo json_encode(array_values($devices));
	}

	/**
	 * Callback AJAX function to filter's whisper for subnet name
	 * 
	 * @author Michal Kliment
	 */
	public function subnet_name()
	{
		$term = $this->input->get('term');

		$subnet_model = new subnet_Model();

		$subnets = $subnet_model->like('name', $term)
				->groupby('name')
				->orderby('name')
				->find_all()
				->select_list();

		echo json_encode(array_values($subnets));
	}

	/**
	 * Callback AJAX function to filter's whisper for device login
	 * 
	 * @author Michal Kliment
	 */
	public function device_login()
	{
		$term = $this->input->get('term');

		$device_model = new Device_Model();

		$devices = $device_model->like('login', $term)
				->groupby('login')
				->orderby('login')
				->find_all()
				->select_list('id', 'login');

		echo json_encode(array_values($devices));
	}

	/**
	 * Callback AJAX function to filter's whisper for device password
	 * 
	 * @author Michal Kliment
	 */
	public function device_password()
	{
		$term = $this->input->get('term');

		$device_model = new Device_Model();

		$devices = $device_model->like('password', $term)
				->groupby('password')
				->orderby('password')
				->find_all()
				->select_list('id', 'password');

		echo json_encode(array_values($devices));
	}

	/**
	 * Callback AJAX function to filter's whisper for device trade name
	 * 
	 * @author Michal Kliment
	 */
	public function device_trade_name()
	{
		$term = $this->input->get('term');

		$device_model = new Device_Model();

		$devices = $device_model->like('trade_name', $term)
				->groupby('trade_name')
				->orderby('trade_name')
				->find_all()
				->select_list('id', 'trade_name');

		echo json_encode(array_values($devices));
	}
	
	/**
	 * Callback AJAX function to filter's whisper for country name
	 * 
	 * @author Michal Kliment
	 */
	public function country_name()
	{
		$term = $this->input->get('term');

		$country_model = new Country_Model();

		$countries = $country_model->like('country_name', $term)
				->groupby('country_name')
				->orderby('country_name')
				->find_all()
				->select_list('id', 'country_name');

		echo json_encode(array_values($countries));
	}
	
	/**
	 * Callback AJAX function to filter's whisper for GPS
	 * 
	 * @author Michal Kliment
	 */
	public function gps()
	{
		$term = $this->input->get('term');

		$address_point_model = new Address_point_Model();

		$agps = $address_point_model->get_all_gps($term);

		$arr_gps = array();
		foreach ($agps as $gps)
			$arr_gps[] = $gps->gps;

		echo json_encode($arr_gps);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for address point name
	 * 
	 * @author Michal Kliment
	 */
	public function address_point_name()
	{
		$term = $this->input->get('term');

		$address_point_model = new Address_point_Model();

		$address_points = $address_point_model->like('name',$term)
				->groupby('name')
				->orderby('name')
				->find_all()
				->select_list();

		echo json_encode(array_values($address_points));
	}
	
	public function bank_account_name()
	{
		$term = $this->input->get('term');

		$bam = new Bank_account_Model();

		$bank_accounts = $bam->like('name', $term)
				->groupby('name')
				->orderby('name')
				->find_all()
				->select_list();

		echo json_encode(array_values($bank_accounts));
	}
	
	/**
	 * Callback AJAX function to return link by iface id
	 * 
	 * @author Ondřej Fibich
	 */
	public function get_link_by_iface()
	{
		$iface = new Iface_Model($this->input->get('iface_id'));
		
		if ($iface->id && $iface->link_id)
		{
			$arr = $iface->link->as_array();
			$arr['bitrate'] = network::bytes2str($arr['bitrate'], 'M');
			die(json_encode($arr));
		}
		else
		{
			die(json_encode(null));
		}
	}
	
	/**
	 * Callback AJAX function to return iface and device that are connected
	 * to given iface.
	 * 
	 * @author Ondřej Fibich
	 */
	public function get_iface_and_device_connected_to_iface()
	{
		$iface = new Iface_Model($this->input->get('iface_id'));
		$parent_iface = new Iface_Model($this->input->get('parent_iface_id'));
		
		if ($iface->id)
		{
			$connected_iface = $iface->get_iface_connected_to_iface();
			
			if ($connected_iface && (!$parent_iface->id || $parent_iface->id != $connected_iface->id))
			{
				die(json_encode(array
				(
					'iface'		=> $connected_iface->as_array(),
					'device'	=> $connected_iface->device->as_array()
				)));
			}
		}
		
		die(json_encode(null));
	}
	
	/**
	 * Callback AJAX function to filter's whisper for SSID
	 * 
	 * @author Michal Kliment
	 */
	public function ssid()
	{
		$term = $this->input->get('term');

		$link_model = new Link_Model();

		$ssids = $link_model->like('wireless_ssid',$term)
			->orderby('wireless_ssid')
			->find_all()
			->select_list('id', 'wireless_ssid');

		echo json_encode(array_values($ssids));
	}
	
	/**
	 * Callback AJAX function for getting if device name
	 * 
	 * @author Ondřej Fibich
	 */
	public function get_device_name()
	{
		$device_id = $this->input->get('device_id');

		$device_model = new Device_Model($device_id);

		if ($device_model && $device_model->id && !empty($device_model->name))
		{
			die(json_encode(array
			(
				'name' => $device_model->name
			)));
		}

		die(json_encode(array
		(
			'name' => $device_id
		)));
	}
	
	/**
	 * Callback AJAX function to get value of device template
	 * 
	 * @author Ondřej Fibich 
	 */
	public function get_device_template_value()
	{
		$device_template_id = $this->input->get('device_template_id');
		$device_template = new Device_template_Model($device_template_id);
		
		if ($device_template && $device_template->id)
		{
			echo $device_template->values;
		}
		else
		{
			echo json_encode(array());
		}
	}
	
	/**
	 * AJAX function for loading device template active links
	 * 
	 * @author David Raška
	 */
	public function get_device_template_active_links()
	{
		$template_id = $this->input->get('template');
		$device_active_link_model = new Device_active_link_Model();
		
		$templates = $device_active_link_model->get_device_active_links(
				$template_id,
				Device_active_link_Model::TYPE_TEMPLATE
		)->result_array();
		
		if ($templates)
		{
			echo json_encode($templates);
		}
		else
		{
			echo json_encode(array());
		}
	}
	
	/**
	 * Callback AJAX function to get only device templates of choosen type of 
	 * device to dropdown
	 * 
	 * @author Ondřej Fibich 
	 */
	public function get_device_templates_by_type()
	{
		$type = $this->input->get('type');
		$device_template = new Device_template_Model();
		
		$types = $device_template->where('enum_type_id', $type)->orderby('name')->find_all();
		$arr_types = array();
		
		foreach ($types as $type)
		{
			$arr_types[] = array
			(
				'id'		=> $type->id,
				'name'		=> $type->name,
				'isDefault'	=> $type->default
			);
		}
		
		echo json_encode($arr_types);
	}
	
	/**
	 * Callback AJAX function to get only streets of choosen town to dropdown
	 * 
	 * @author Ondřej Fibich
	 */
	public function get_streets_by_town()
	{
		// H@ck: key is swaped with value because of sorting asociative array
		// by street name in JavaScript coause by auto sorting in jQuery AJAX fnc.
		$streets = ORM::factory('street')
				->where('town_id', $this->input->get('town_id'))
				->select_list('street', 'id', 'street');
		
		echo json_encode($streets);
	}
	
	/**
	 * Gets suggestion for connecting one type of interface on location given by
	 * member address point or by given GPS.
	 * 
	 * @author Ondřej Fibich
	 * @see Devices_Controller#add 
	 */
	public function get_suggestion_for_connecting_to()
	{
		// filter
		$filter = new Filter_form();
		$filter->autoload();
		
		// vars
		$user_id = $this->input->get('user_id');
		$gpsx = $this->input->get('gpsx');
		$gpsy = $this->input->get('gpsy');
		$wmode = $this->input->get('wmode');
		$gps = array();
		
		if ($user_id)
		{
			if ($gpsx && $gpsy)
			{
				$gps = array('x' => $gpsx, 'y' => $gpsy);
			}
			
			$im = new Iface_Model();
			
			echo json_encode(array
			(
				Iface_Model::TYPE_WIRELESS	=> $im->get_iface_for_connecting_to_iface(
						$user_id, Iface_Model::TYPE_WIRELESS, $gps, $filter->as_sql(), $wmode
				),
				Iface_Model::TYPE_ETHERNET	=> $im->get_iface_for_connecting_to_iface(
						$user_id, Iface_Model::TYPE_ETHERNET, $gps, $filter->as_sql()
				),
				Iface_Model::TYPE_PORT		=> $im->get_iface_for_connecting_to_iface(
						$user_id, Iface_Model::TYPE_PORT, $gps, $filter->as_sql()
				)
			));
		}
		else
		{
			echo json_encode(array());
		}
	}
	
	/**
	 * Returns all members in JSON format
	 * 
	 * @author Michal Kliment 
	 */
	public function get_members()
	{
		$arr_members = array();
		
		$members = ORM::factory('member')
				->get_all_members_to_dropdown();
		
		foreach ($members as $member)
			$arr_members[] = arr::from_object($member);
		
		echo json_encode($arr_members);
	}
	
	/**
	 * Returns all devices of member in JSON format
	 * 
	 * @author Michal Kliment
	 */
	public function get_devices()
	{
		$member_id = (int) $this->input->get('data');
		
		$arr_devices = array();		
		$devices = ORM::factory('device')
				->get_all_devices_by_member($member_id);
		
		foreach ($devices as $device)
			$arr_devices[] = arr::from_object($device);
		
		echo json_encode($arr_devices);
	}
	
	/**
	 * Returns filtered devices in JSON format
	 * 
	 * @author Ondřej Fibich
	 * @see Device_Controller#add
	 * @see Filter_form
	 */
	public function get_filtered_devices()
	{
		// filter
		$filter = new Filter_form();
        $filter->autoload();
		
		// data
		$dm = new Device_Model();
		$devices = $dm->select_list_filtered_device_with_user($filter->as_sql());
		
		$out_devices = array();
		foreach ($devices AS $user_name => $device)
		{
			$out_devices[] = array('user_name' => $user_name, 'devices' => $device);
		}
		// output
		echo json_encode($out_devices);
	}
	
	/**
	 * Returns all ifaces in in JSON format
	 * 
	 * @author Michal Kliment, Ondrej Fibich
	 */
	public function get_ifaces()
	{
		$device_id = (int) $this->input->get('data');
		$itype = $this->input->get('itype');
		$wmode = $this->input->get('wmode');
		$ifaces = array();
		$arr_ifaces = array();
		$concat = 'CONCAT(IFNULL(mac, \'- \'),\': \',IFNULL(name,\'\'))';
		
		if ($device_id && is_numeric($itype))
		{
			$itypes = Iface_Model::get_can_connect_to($itype);
			
			if (count($itypes))
			{
				if (is_numeric($wmode) && ($itype == Iface_Model::TYPE_WIRELESS))
				{
					// select oposite mode
					if ($wmode == Iface_Model::WIRELESS_MODE_AP)
					{
						$wmode = Iface_Model::WIRELESS_MODE_CLIENT;
					}
					else
					{
						$wmode = Iface_Model::WIRELESS_MODE_AP;
					}
					
					$ifaces = ORM::factory('iface')
						->where('device_id', $device_id)
						->where('wireless_mode', $wmode)
						->in('type', $itypes)
						->select_list('id', $concat);
				}
				else
				{
					$ifaces = ORM::factory('iface')
						->where('device_id', $device_id)
						->in('type', $itypes)
						->select_list('id', $concat);
				}
			}
		}
		
		foreach ($ifaces as $id => $name)
		{
			$arr_ifaces[] = array
			(
				'id' => $id,
				'name' => $name
			);
		}
		
		echo json_encode($arr_ifaces);
	}
	
	/**
	 * Function return address of given user
	 * 
	 * @author David Raska
	 */
	public function get_user_address()
	{
		$user_id = (int) $this->input->get('user_id');
		
		if ($user_id)
		{
			$um = new User_Model($user_id);
			
			$result = array(
				'country_id' => $um->member->address_point->country_id,
				'town_id' => $um->member->address_point->town_id,
				'street_id' => $um->member->address_point->street_id,
				'street_number' => $um->member->address_point->street_number,
			);

			echo json_encode($result);
		}
	}
	
	/**
	 * Calculated additional of applicant
	 * 
	 * @author Ondřej Fibich
	 * @see Members_Controller#approve_applicant
	 */
	public function calculate_additional_payment_of_applicant()
	{
		$entrance_date = $this->input->get('entrance_date');
		$connected_from = $this->input->get('connected_from');
		$data = array('amount' => 0);
		
		if (preg_match('/^\d{4}\-\d{1,2}\-\d{1,2}$/', $entrance_date) !== FALSE &&
			preg_match('/^\d{4}\-\d{1,2}\-\d{1,2}$/', $connected_from) !== FALSE &&
			$entrance_date != '0000-00-00' &&
			$connected_from != '0000-00-00')
		{
			$mf = new Members_fee_Model();
			$data['amount'] = $mf->calculate_additional_payment_of_applicant($connected_from, $entrance_date);
		}
		
		echo json_encode($data);
	}
	
	/**
	 * Prints all free IP addresses similar to given IP address
	 * 
	 * @author Michal Kliment
	 */
	public function get_free_ip_addresses()
	{
		$ip_address = $this->input->get('term');
		
		$ip_address_model = new Ip_address_Model();
		
		try
		{
			$ip_addresses = $ip_address_model->get_free_ip_addresses($ip_address);
		}
		catch (Exception $e)
		{
			$ip_addresses = array();
		}
		
		echo json_encode($ip_addresses);
	}

	/**
	 * Callback AJAX function to filter's whisper for name
	 *
	 * @author Jan Dubina
	 */
	public function invoice_name()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_names($term);
		
		$names = array();
		foreach ($invoices as $invoice)
			$names[] = $invoice->partner;
		
		echo json_encode($names);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for street
	 *
	 * @author Jan Dubina
	 */
	public function invoice_street()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_streets($term);
		
		$streets = array();
		foreach ($invoices as $invoice)
			$streets[] = $invoice->street;
		
		echo json_encode($streets);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for town
	 *
	 * @author Jan Dubina
	 */
	public function invoice_town()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_towns($term);
		
		$towns = array();
		foreach ($invoices as $invoice)
			$towns[] = $invoice->town;
		
		echo json_encode($towns);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for zip code
	 *
	 * @author Jan Dubina
	 */
	public function invoice_zip_code()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_zip_codes($term);
		
		$zip_codes = array();
		foreach ($invoices as $invoice)
			$zip_codes[] = $invoice->zip_code;
		
		echo json_encode($zip_codes);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for street name
	 *
	 * @author Jan Dubina
	 */
	public function invoice_street_number()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_street_numbers($term);
		
		$street_numbers = array();
		foreach ($invoices as $invoice)
			$street_numbers[] = $invoice->street_number;
		
		echo json_encode($street_numbers);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for company
	 *
	 * @author Jan Dubina
	 */
	public function invoice_company()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_companies($term);
		
		$companies = array();
		foreach ($invoices as $invoice)
			$companies[] = $invoice->partner_company;
		
		echo json_encode($companies);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for country
	 *
	 * @author Jan Dubina
	 */
	public function invoice_country()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_countries($term);
		
		$countries = array();
		foreach ($invoices as $invoice)
			$countries[] = $invoice->country;
		
		echo json_encode($countries);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for organization identifier
	 *
	 * @author Jan Dubina
	 */
	public function invoice_organization_id()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_organization_ids($term);
		
		$organization_ids = array();
		foreach ($invoices as $invoice)
			$organization_ids[] = $invoice->organization_identifier;
		
		echo json_encode($organization_ids);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for organization identifier
	 *
	 * @author Jan Dubina
	 */
	public function invoice_vat_organization_id()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_vat_organization_ids($term);
		
		$vat_organization_ids = array();
		foreach ($invoices as $invoice)
			$vat_organization_ids[] = $invoice->vat_organization_identifier;
		
		echo json_encode($vat_organization_ids);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for account number
	 *
	 * @author Jan Dubina
	 */
	public function invoice_account_nr()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_account_nrs($term);
		
		$account_nrs = array();
		foreach ($invoices as $invoice)
			$account_nrs[] = $invoice->account_nr;
		
		echo json_encode($account_nrs);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for phone number
	 *
	 * @author Jan Dubina
	 */
	public function invoice_phone_nr()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_phone_numbers($term);
		
		$phone_nrs = array();
		foreach ($invoices as $invoice)
			$phone_nrs[] = $invoice->phone_number;
		
		echo json_encode($phone_nrs);
	}
	
	/**
	 * Callback AJAX function to filter's whisper for email
	 *
	 * @author Jan Dubina
	 */
	public function invoice_email()
	{
		$term = $this->input->get('term');
		
		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->get_all_emails($term);
		
		$emails = array();
		foreach ($invoices as $invoice)
			$emails[] = $invoice->email;
		
		echo json_encode($emails);
	}
	
	/**
	 * Callback AJAX funxtion to get device and iface to which is device connected.
	 * 
	 * @author Michal Kliment
	 */
	public function get_connected_to_device_and_iface()
	{
		$subnet_id = $this->input->get('subnet_id');
		$mac_address = $this->input->get('mac_address');
		
		$port_nr = 0;
		
		$ip_address_model = new Ip_address_Model();
		
		// find gateway of subnet
		$gateway_ip_address = $ip_address_model
			->where(array
			(
				'subnet_id' => $subnet_id,
				'gateway' => 1
			))->find();
		
		// IP is on VLAN iface  => take physical (parent) interface
		if ($gateway_ip_address->iface->type == Iface_Model::TYPE_VLAN)
			$iface = $gateway_ip_address->iface->ifaces_relationships->current()->parent_iface;
		// IP is on normal iface
		else
			$iface = $gateway_ip_address->iface;
		
		$device = $iface->device;
		
		$x = 100;
		
		while (true)
		{
			$x--;
			
			// unending loop protection
			if ($x == 0)
				break;
			
			// device is not connected to any device or is not connected to any association device
			if ($iface->get_iface_connected_to_iface() === NULL ||
				$iface->get_iface_connected_to_iface()->device->user->member_id != Member_Model::ASSOCIATION)
			{
				// we end
				break;
			}
			
			// take device to which is our device connected
			$device = $iface->get_iface_connected_to_iface()->device;
			
			// find IP address of device
			$ip_address = $ip_address_model
				->get_ip_addresses_of_device($device->id)->current();
			
			# only for switch
			if ($device->has_ports() && $ip_address)
			{
				if (module::e('snmp'))
				{
					try
					{
						$snmp = Snmp_Factory::factoryForDevice($ip_address->ip_address);

						// try find port number
						$port_nr = $snmp->getPortNumberOf($mac_address);
					}
					catch (Exception $e)
					{
						die(json_encode(array
						(
							'state' => 0,
							'message' => $e->getMessage()
						)));
					}
				}
				else
				{
					die(json_encode(array
					(
						'state' => 0,
						'message' => __('SNMP not enabled')
					)));
				}

				// and try find port in database
				$iface = ORM::factory('iface')
					->where(array
					(
						'type'		=> Iface_Model::TYPE_PORT,
						'device_id'	=> $device->id,
						'number'	=> $port_nr
					))
					->find();
			}
			else
			{
				$found = FALSE;
				
				// for each ifaces of device
				foreach ($device->ifaces as $device_iface)
				{
					// take first iface with unending loop detection
					if ($device_iface->get_iface_connected_to_iface()
						&& $device_iface->get_iface_connected_to_iface()->id != $iface->id)
					{
						$iface = $device_iface;
						
						$found = TRUE;
						
						break;
					}
				}
				
				// this device has not any iface to which we can connect
				if (!$found)
				{
					die(json_encode(array
					(
						'state' => 0,
						'message' => __('Error - cannot find device')
					)));
				}
			}
		}
		
		// we know port number which is not im db
		if ($device->id && $port_nr && $iface->device_id != $device->id)
		{	
			// try create it
			try
			{
				$iface->transaction_start();
				
				$iface->clear();
				$iface->type = Iface_Model::TYPE_PORT;
				$iface->device_id = $device->id;
				$iface->number = $port_nr;
				$iface->name = __('Port').' '.$port_nr;
				$iface->save_throwable();
				
				$iface->transaction_commit();
			}
			catch (Exception $e)
			{
				$iface->transaction_rollback();
			}
		}
		
		// success, return device and iface
		if ($device->id && $iface->id)
		{
			die(json_encode(array
			(
				'state' => 1,
				'device_id' => $device->id,
				'iface_id' => $iface->id
			)));
		}
		// fail
		else
		{
			die(json_encode(array
			(
				'state' => 0,
				'message' => __('Error - cannot find device')
			)));
		}
	}
	
	/**
	 * Sends request to ARES API
	 * 
	 * @author Michal Kliment
	 * @param type $type
	 * @param type $params
	 * @return type
	 */
	private function send_ares_request($type, $params)
	{
		switch ($type)
		{
			case 'standard':
			
				$url = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_std.cgi?obchodni_firma=' .
					urlencode(text::cs_utf2ascii($params['name']));
				
				if (isset($params['town']))
					$url .= '&nazev_obce=' . urlencode($params['town']);
				
				$url .= '&diakritika=false&max_pocet=1&czk=utf';
				
				break;
			
			case 'basic':
				
				$url = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi?ico=' .
					$params['organization_identifier'];
				
				break;
			
			default:
				return array
				(
					'state' => 0,
					'Unknown ARES request type'
				);
		}
		
		$file = @file_get_contents($url);
			
		if ($file)
		{
			$xml = @simplexml_load_string($file);

			if ($xml)
			{
				$ns = $xml->getDocNamespaces();

				$data = $xml->children($ns['are']);

				// return data
				return array
				(
					'state' => 1,
					'ns'	=> $ns,
					'data'	=> $data
				);
			}
			else
			{
				// bad output data
				return array
				(
					'state' => 0,
					'text'	=> __('Invalid output data')
				);
			}
		}
		else
		{
			// some problem with ARES
			return array
			(
				'state' => 0,
				'text'	=> __('ARES is probably down')
			);
		}
	}
	
	/**
	 * Loads data about member from ARES
	 * 
	 * @author Michal Kliment
	 */
	public function load_member_data_from_ares()
	{
		$result = array
		(
			'state' => 0,
			'text'	=> __('Invalid input data')
		);
		
		$organization_identifier = $this->input->get('organization_identifier');
		
		// organization identifier is set
		if ($organization_identifier != '')
		{	
			// find data by organization identifier
			$result = $this->send_ares_request('basic', array
			(
				'organization_identifier' => $organization_identifier
			));

			// no error in request
			if ($result['state'])
			{
				$el = $result['data']->children($result['ns']['D'])->VBAS;

				// record was found
				if (strval($el->ICO) == $organization_identifier)
				{
					$result['name'] = strval($el->OF);

					// found record is person
					if ($el->PF->KPF == 101)
					{
						// split name in firstname and surname
						$names = explode(" ", $result['name']);

						$result['firstname'] = array_shift($names);
						$result['lastname'] = array_pop($names);
					}

					$result['organization_identifier'] = strval($el->ICO);
					$result['vat_organization_identifier'] = strval($el->DIC);
					
					// address
					$result['zip_code'] = strval($el->AA->PSC);
					$result['town'] = strval($el->AA->N);
					$result['quarter'] = strval($el->AA->NCO);
					$result['street'] = strval($el->AA->NU);
					$result['street_number'] = strval($el->AA->CD);
					$result['other_street_number'] = strval($el->AA->CA);
				}
				else
				{
					// record not found
					$result['state'] = 0;
					$result['text'] = __('Item not found');
				}
			}
		}
		else
		{
			$name = $this->input->get('name');
			$town = new Town_Model((int) $this->input->get('town_id'));
		
			// name is set
			if ($name != '')
			{
				// town is set
				if ($town && $town->id)
				{
					// try find by name and town
					$result = $this->send_ares_request('standard', array
					(
						'name' => $name,
						'town' => $town->town
					));

					// no error in request
					if ($result['state'])
					{
						// record was found
						if (strval($result['data']->Odpoved->Pocet_zaznamu))
						{
							$el = $result['data']->Odpoved->Zaznam;

							$result['name'] = strval($el->Obchodni_firma);

							// found record is person
							if ($el->Pravni_forma->children($result['ns']['dtt'])->Kod_PF == 101)
							{
								// split name in firstname and surname
								$names = explode(" ", $result['name']);

								$result['firstname'] = array_shift($names);
								$result['lastname'] = array_pop($names);
							}

							$result['organization_identifier'] = strval($el->ICO);
							$result['vat_organization_identifier'] = strval($el->DIC);

							// address
							$result['zip_code'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->PSC);
							$result['town'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->Nazev_obce);
							$result['quarter'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->Nazev_casti_obce);
							$result['street'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->Nazev_ulice);
							$result['street_number'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->Cislo_domovni);
							$result['other_street_number'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->Cislo_do_adresy);
						}
						else
						{
							// record not found
							$result['state'] = 0;
							$result['text'] = __('Item not found');
						}
					}
				}
				
				// record not found by name and town, try only by name
				if (!$result['state'])
				{
					$result = $this->send_ares_request('standard', array
					(
						'name' => $name
					));

					if ($result['state'])
					{
						// record was found
						if (strval($result['data']->Odpoved->Pocet_zaznamu) > 0)
						{
							$el = $result['data']->Odpoved->Zaznam;

							$result['name'] = strval($el->Obchodni_firma);

							// found record is person
							if ($el->Pravni_forma->children($result['ns']['dtt'])->Kod_PF == 101)
							{
								// split name in firstname and surname
								$names = explode(" ", $result['name']);

								$result['firstname'] = array_shift($names);
								$result['lastname'] = array_pop($names);
							}

							$result['organization_identifier'] = strval($el->ICO);
							$result['vat_organization_identifier'] = strval($el->DIC);

							// address
							$result['zip_code'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->PSC);
							$result['town'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->Nazev_obce);				
							$result['quarter'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->Nazev_casti_obce);
							$result['street'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->Nazev_ulice);
							$result['street_number'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->Cislo_domovni);
							$result['other_street_number'] = strval($el->Identifikace->Adresa_ARES->children($result['ns']['dtt'])->Cislo_do_adresy);
						}
						else
						{
							// record not found
							$result['state'] = 0;
							$result['text'] = __('Item not found');
						}
					}
				}
			}
		}
		
		// record was found
		if ($result['state'])
		{
			$town_model = new Town_Model();
			$street_model = new Street_Model();
			
			if ($result['quarter'] == $result['town'] || $result['quarter'] == '')
				$result['quarter'] = NULL;
			
			// if street is empty, use town as street
			if ($result['street'] == '')
			{
				$result['street'] = ($result['quarter'] !== NULL) ? $result['quarter'] : $result['town'];
			}
			
			// try find town in db
			$town = $town_model->where(array
			(
				'town'		=> $result['town'],
				'zip_code'	=> $result['zip_code'],
				'quarter'	=> $result['quarter']
			))->find();
			
			// not exist, create it
			if (!$town->id && $result['town'] != '')
			{
				try
				{
					$town->transaction_start();
					$town->clear();
					$town->town = $result['town'];
					$town->zip_code = $result['zip_code'];
					$town->quarter = $result['quarter'];
					$town->save_throwable();
					$town->transaction_commit();
				}
				catch (Exception $e)
				{
					$town->transaction_rollback();
				}
				
			}
			
			$result['town_id'] = $town->id;
			
			// try find street in db
			$street = $street_model->where(array
			(
				'town_id'	=> $result['town_id'],
				'street'	=> $result['street']
			))->find();
			
			// not exist, create it
			if (!$street->id && $result['street'] != '')
			{
				try
				{
					$street->transaction_start();
					$street->clear();
					$street->town_id = $result['town_id'];
					$street->street = $result['street'];
					$street->save_throwable();
					$street->transaction_commit();
				}
				catch (Exception $e)
				{
					$street->transaction_rollback();
				}
				
			}
			
			$result['street_id'] = $street->id;
			
			if ($result['street_number'] == '' && $result['other_street_number'] != '')
				$result['street_number'] = $result['other_street_number'];
			
			// unset useless variables
			unset($result['quarter']);
			unset($result['ns']);
			unset($result['data']);
		}
		
		die(json_encode($result));
	}
}