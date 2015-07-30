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
		self::send_json_headers();
	}
	
	/**
	 * Function to return accounts belong to account type
	 * 
	 * @author Michal Kliment
	 * @param number $origin_account_id
	 */
	public function get_accounts_by_type($origin_account_id = NULL)
	{
		// access control
		if (!$this->acl_check_view('Accounts_Controller', 'transfers'))
			Controller::error(ACCESS);

		$id = $this->input->get('id');

		$accounts = ORM::factory('account')->get_some_doubleentry_account_names(
				$origin_account_id, $id
		);

		$arr_accounts = array();
		foreach ($accounts as $account)
		{	// convert the object into array (used for HTML select list)
			$arr_accounts[$account->id] = 
					$account->name . ' ' . $account->id . ' (' . $account->addr . ')';
		}
		asort($arr_accounts, SORT_LOCALE_STRING);

		echo json_encode($arr_accounts);
	}

	/**
	 * Returns all fees belongs to fee type in json format
	 *
	 * @author Michal Kliment
	 */
	public function get_fees_by_type()
	{
		// access control
		if (!$this->acl_check_view('Settings_Controller', 'fees'))
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
	 * Performs action of ip_address_check jQuery frm validator.
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
		if (!valid::ip_check_subnet(ip2long($ip_str), $subnet->net + 0, $subnet->mask))
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

}
