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
 * Controller for displaying different javascript for different queries.
 *
 * @author	Michal Kliment
 */
class Js_Controller extends Controller
{

	/**
	 * Base view
	 * 
	 * @var View Object
	 */
	private $view = NULL;

	/**
	 * Array of other views put into document ready
	 * 
	 * @var array
	 */
	private $views = array();

	/**
	 * Array of other views not puted into document ready
	 * 
	 * @var array
	 */
	private $views_notready = array();

	/**
	 * Constructor, only send header
	 * 
	 * @author Michal Kliment
	 */
	public function  __construct()
	{
		parent::__construct();
		header("Content-type: text/javascript");
	}

	/**
	 * Method for remap default behaviour of controllers
	 * 
	 * @author Michal Kliment
	 * @param string $method
	 * @param array $args
	 */
	public function _remap($method, $args)
	{
		$args = arr::merge(array($method), $args);
		$method = '';

		// create base view
		$this->view = new View('js/base');
		$this->view->nobase = (bool) $this->input->get('nobase');

		// for all url segments
		for ($i = 1; $i <= count($args); $i++)
		{
			$method = implode('_', array_slice($args, 0, $i));

			// method with this name exist => call it
			if (method_exists($this, '_js_'.$method))
			{
				call_user_func_array(
						array($this, '_js_'.$method),
						array_slice($args, $i)
				);
			}
			// view with this name exist => create it
			else if (Kohana::find_file('views', 'js/' . $method, FALSE))
			{
				$this->views[$method] = new View('js/' . $method);
			}
		}

		$this->view->views = $this->views;
		$this->view->views_notready = $this->views_notready;
		$this->view->render(TRUE);
	}
	
	/**
	 * @see MY_Controller#is_preprocesor_enabled()
	 */
	protected function is_preprocesor_enabled()
	{
		return FALSE;
	}
	
	/***************** Methods for javascript queries *************************/
	
	private function _js_address_points_add()
	{
		$this->address_point_streets();
		$this->address_point_gps();
	}
	
	private function _js_bank_accounts_auto_down_settings_add()
	{
		$this->time_activity_rule();
	}
	
	private function _js_device_templates_edit($device_template_id = NULL)
	{
		$dtm = new Device_template_Model($device_template_id);
		
		if ($dtm && $dtm->id)
		{
			$view = View::factory('js/device_templates_add');
			$view->device_template_value = $dtm->get_value();
			$this->views['device_templates_edit'] = $view->render();
		}
	}
	
	private function _js_devices()
	{
		$this->ip_addresses_complete();
	}
	
	private function _js_devices_add($user_id = NULL, $connection_request_id = NULL)
	{
		$user = new User_Model($user_id);
		
		if (!$user || !$user->id || !$user->devices->count())
		{
			$user_id = User_Model::ASSOCIATION;
		}
		
		$cr_model = new Connection_request_Model($connection_request_id);
		
		if (Address_points_Controller::is_address_point_server_active())
		{
			$this->address_point_whisperer();
			$this->address_point_whisperer_gps();
		}
		else
		{
			$this->address_point_streets();
			$this->address_point_gps();
		}
		
		$subnet = new Subnet_Model();
		$device = new Device_Model();
		
		$this->views['devices_add'] = View::factory('js/devices_add');
		$this->views['devices_add']->arr_subnets = $subnet->select_list_by_net();
		$this->views['devices_add']->arr_gateway_subnets = arr::from_objects($subnet->get_all_subnets_with_gateway());
		$this->views['devices_add']->arr_devices = $device->select_list_device_with_user($user_id);
		$this->views['devices_add']->connection_request_model = ($cr_model->loaded) ? $cr_model : NULL;
	}
	
	private function _js_devices_add_simple()
	{
		if (Address_points_Controller::is_address_point_server_active())
		{
			$this->address_point_whisperer();
			$this->address_point_whisperer_gps();
		}
		else
		{
			$this->address_point_streets();
			$this->address_point_gps();
		}
	}
	
	private function _js_devices_add_filter()
	{		
		$this->views['devices_add'] = View::factory('js/devices_add_filter');
		$this->views['devices_add']->filter_index = $this->input->get('index');
	}
	
	private function _js_devices_edit()
	{
		if (Address_points_Controller::is_address_point_server_active())
		{
			$this->address_point_whisperer_gps();
			$this->address_point_whisperer();
		}
		else
		{
			$this->address_point_streets();
			$this->address_point_gps();
		}
	}
	
	private function _js_devices_ports_vlans_settings ($device_id = NULL, $vlan_id = NULL)
	{
		$this->views['devices_ports_vlans_settings'] = View::factory('js/devices_ports_vlans_settings');
		$this->views['devices_ports_vlans_settings']->vlan_id = $vlan_id;
	}


	private function _js_devices_map()
	{		
		$action = '';
		$name = '';
		if ($this->input->get('action'))
		{
			$action = $this->input->get('action');
		}
		
		if ($this->input->get('name'))
		{
			$name = $this->input->get('name');
		}
		
		$this->views['devices_map'] = View::factory('js/devices_map');
		$this->views['devices_map']->action = $action;
		$this->views['devices_map']->name = $name;
	}
	
	private function _js_devices_topology($device_id)
	{		
		$this->views['devices_add'] = View::factory('js/devices_topology');
		$this->views['devices_add']->device_id = $device_id;
	}
	
	private function _js_dns_add()
	{
		$this->dns_records();
	}
	
	private function _js_dns_edit($zone_id = null)
	{
		$this->dns_records();
		
		$record_model = new Dns_record_Model();
		$records = array();
		$result = $record_model->get_records_in_zone($zone_id);
		if ($result)
		{
			$records = $result->result_array();
		}
		
		$this->views['dns_edit'] = View::factory('js/dns_edit');
		$this->views['dns_edit']->records = $records;
		$this->views['dns_edit']->dns_zone_id = $zone_id;
	}
	
	private function _js_ifaces_add($device_id = null, $itype = null,
			$add_button = FALSE, $connect_type = NULL)
	{
		$this->views['ifaces_add'] = View::factory('js/ifaces_add');
		$this->views['ifaces_add']->iface_id = NULL;
		$this->views['ifaces_add']->device_id = $device_id;
		$this->views['ifaces_add']->itype = $itype;
		$this->views['ifaces_add']->add_button = $add_button;
		$this->views['ifaces_add']->connect_type = $connect_type;
	}
	
	private function _js_ifaces_edit($iface_id = null, $type = null,
			$add_button = FALSE, $connect_type = NULL)
	{
		$iface = new Iface_Model($iface_id);
		
		if ($iface && $iface->id)
		{
			if (!Iface_Model::get_type($type))
			{
				$type = $iface->type;
			}
		
			$this->views['ifaces_edit'] = View::factory('js/ifaces_add');
			$this->views['ifaces_edit']->device_id = $iface->device_id;
			$this->views['ifaces_edit']->iface_id = $iface->id;
			$this->views['ifaces_edit']->itype = $type;
			$this->views['ifaces_edit']->add_button = $add_button;
			$this->views['ifaces_edit']->connect_type = $connect_type;
			$this->views['ifaces_edit']->is_edit = true;
		}
	}
	
	private function _js_invoices_add()
	{
		$this->invoice_form();
		$this->members_ares();
	}
	
	private function _js_invoices_edit()
	{
		$this->invoice_form();
	}
	
	private function _js_ip_addresses()
	{
		$this->ip_addresses_complete();
	}
	
	private function _js_mail_write_message()
	{
		// load users for autocomplete
		$um = new User_Model();
		
		$user_list = $um
				->select_list('login', "CONCAT(surname, ' ', COALESCE(name,''), ' - ', login)", 'surname');
		
		$result = array();
		
		foreach ($user_list AS $login => $user)
		{
			$result[] = array(
				'login' => $login,
				'value' => $user
			);
		}

		$this->views['mail_write_message'] = View::factory('js/mail_write_message');
		$this->views['mail_write_message']->users_list = json_encode($result);
	}
	
	private function _js_members()
	{
		$this->members_ares();
	}

	private function _js_members_add()
	{
		if (Address_points_Controller::is_address_point_server_active())
		{
			$this->address_point_whisperer_gps();
			$this->address_point_whisperer();
		}
		else
		{
			$this->address_point_streets();
			$this->address_point_gps();
		}
		
		$this->domicile_toogle();

		$this->views['members_add'] = View::factory('js/__pieces/autogen_password');
	}
	
	private function _js_members_approve_applicant($applicant_id)
	{
		$applicant = new Member_Model($applicant_id);
		
		if ($applicant && $applicant->id)
		{
			$this->views['members_approve_applicant'] = View::factory('js/members_approve_applicant');
			$this->views['members_approve_applicant']->applicant_connected_from = $applicant->applicant_connected_from;
		}
	}
	
	private function _js_members_edit()
	{
		if (Address_points_Controller::is_address_point_server_active())
		{
			$this->address_point_whisperer_gps();
			$this->address_point_whisperer();
		}
		else
		{
			$this->address_point_streets();
			$this->address_point_gps();
		}
		
		$this->domicile_toogle();
	}
	
	private function _js_members_fees_add($member_id = NULL, $fee_type_id = NULL)
	{
		$fee_model = new Fee_Model();
		$enum_type_model = new Enum_type_Model();
		
		$fees = array();
		
		// fee_id is not set (or it is not numeric)
		if ($fee_type_id && is_numeric($fee_type_id))
		{
			// find fee type
			$fee_type = $enum_type_model->where(array
			(
				'id'		=> $fee_type_id,
				'type_id'	=> Enum_type_Model::FEE_TYPE_ID
			))->find();

			// finds all fees of this type
			if ($fee_type && $fee_type->id)
			{
				$fees = $fee_model->get_all_fees_by_fee_type_id($fee_type->id);
			}
		}
		// fee_id is not set (or it is not numeric)
		else
		{
			$total_fees = $fee_model->count_all();
			$fees = $fee_model->get_all_fees(0, $total_fees, 'type_id');
		}
		
		$this->views['members_fees_add'] = View::factory('js/members_fees_add');
		$this->views['members_fees_add']->fees = $fees;
	}
	
	private function _js_members_show()
	{
		$this->application_password();
	}
	
	private function _js_members_show_all(
			$limit_results = 100, $order_by = 'id',
			$order_by_direction = 'ASC', $page_word = 'page', $page = 1,
			$registrations = 0)
	{		
		$this->views['members_show_all'] = View::factory('js/members_show_all');
		$this->views['members_show_all']->registrations = $registrations;
	}
	
	private function _js_messages_activate()
	{
		$this->notification_activate();
	}
	
	private function _js_messages_edit()
	{
		$this->views['messages_edit'] = View::factory('js/__pieces/sms_message_counter');
	}
	
	private function _js_messages_auto_settings_add()
	{
		$this->time_activity_rule();
	}
	
	private function _js_notifications_cloud()
	{
		$this->notification_activate();
	}
	
	private function _js_notifications_device()
	{
		$this->notification_activate();
	}
	
	private function _js_notifications_devices()
	{
		$this->notification_activate();
	}
	
	private function _js_notifications_member()
	{
		$this->notification_activate();
	}
	
	private function _js_notifications_members()
	{
		$this->notification_activate();
	}
	
	private function _js_notifications_subnet()
	{
		$this->notification_activate();
	}
	
	private function _js_phone_invoices_import()
	{
		$types = Parser_Phone_Invoice::get_parser_input_types();
		$files = Parser_Phone_Invoice::get_parser_upload_files();
		
		$this->views['phone_invoices_import'] = View::factory('js/phone_invoices_import');
		$this->views['phone_invoices_import']->types = json_encode($types);
		$this->views['phone_invoices_import']->files = json_encode($files);
	}
	
	private function _js_registration()
	{
		if (Address_points_Controller::is_address_point_server_active())
		{
			$this->address_point_whisperer();
			$this->address_point_whisperer_gps();
		}
		else
		{
			$this->address_point_streets();
			$this->address_point_gps();
		}
	}
	
	private function _js_settings_system()
	{
		$modules = array();
		
		foreach (Settings_Controller::$modules as $module => $module_info)
		{
			foreach ($module_info['dependencies'] as $dependency)
			{
				$value = $module_info['name'];
				$key = Settings_Controller::$modules[$dependency]['name'];
				
				if (!isset($modules[$key]))
					$modules[$key] = array();
				
				$modules[$key][] = $value;
			}
		}
		
		$this->views['settings_system'] = View::factory('js/settings_system');
		$this->views['settings_system']->modules = $modules;
	}
	
	private function _js_sms_send()
	{
		$this->views['sms_send'] = View::factory('js/__pieces/sms_message_counter');
	}
	
	private function _js_transfers_add_from_account($origin_account_id = NULL)
	{
		$this->views['transfers_add_from_account'] = View::factory('js/transfers_add_from_account');
		$this->views['transfers_add_from_account']->origin_account_id = $origin_account_id;
	}

	private function _js_transfers_payment_calculator($account_id = NULL)
	{
		$member_id = NULL;
		
		$account = new Account_Model($account_id);
		$vs_model = new Variable_Symbol_Model();
		$vss = $vs_model->find_account_variable_symbols($account_id);
		
		if ($account->id)
		{
			$member_id = $account->member_id;
		
			$can_add = $this->acl_check_new('Accounts_Controller', 'transfers');
		}
		else
			$can_add = FALSE;
		
		$this->views['transfers_payment_calculator'] = View::factory('js/transfers_payment_calculator');
		$this->views['transfers_payment_calculator']->account_id = $account_id;
		$this->views['transfers_payment_calculator']->member_id = $member_id;
		$this->views['transfers_payment_calculator']->can_add = $can_add;
		
		if ($vss->count() > 0 && Settings::get('export_header_bank_account') != NULL)
		{
			$bank_account = new Bank_account_Model(Settings::get('export_header_bank_account'));
			
			$this->views['transfers_payment_calculator']->account_nr = $bank_account->account_nr;
			$this->views['transfers_payment_calculator']->bank_nr = $bank_account->bank_nr;
			$this->views['transfers_payment_calculator']->variable_symbol = $vss->current()->variable_symbol;
			$this->views['transfers_payment_calculator']->show_qr = true;
		}
		else
		{
			$this->views['transfers_payment_calculator']->show_qr = false;
		}
	}
	
	private function _js_users_show($user_id = NULL)
	{
		$this->application_password($user_id);
		$this->views['users_show'] = View::factory('js/users_show')->render();
	}

	private function _js_users_change_password($user_id = NULL)
	{
		$this->views['users_change_password'] = View::factory('js/__pieces/autogen_password');
	}

	private function _js_users_show_work_report($work_id = NULL)
	{
		$this->views['users_show_work_report'] = View::factory('js/work_reports_show')->render();
	}
	
	private function _js_voip_show($user_id = NULL)
	{
		$this->voip_calculator($user_id);
	}
	
	private function _js_voip_calls_show_by_user($user_id = NULL)
	{
		$this->voip_calculator($user_id);
	}
	
	private function _js_work_reports_add()
	{
		$this->work_report_form_functions();
	}
	
	private function _js_work_reports_edit()
	{
		$this->work_report_form_functions();
	}
	
	/***************** Helper methods for javascript queries ******************/
	
	/**
	 * Adds javascript for hiding application password
	 */
	private function application_password()
	{
		$this->views['__pieces_gps'] =
				View::factory('js/__pieces/application_password')->render();
	}
	
	/**
	 * Adds javascript for GPS filling in address point
	 */
	private function address_point_gps()
	{
		$this->views['__pieces_gps'] =
				View::factory('js/__pieces/gps')->render();
	}
	
	/**
	 * Adds javascript for town streets interaction
	 */
	private function address_point_streets()
	{
		$this->views['__pieces_address_point_street'] =
				View::factory('js/__pieces/address_point_street')->render();
	}
	
	/**
	 * Adds javascript for GPS filling in address point
	 */
	private function address_point_whisperer_gps()
	{
		$this->views['__pieces_gps'] =
				View::factory('js/__pieces/whisperer_gps')->render();
	}
	
	/**
	 * Adds javascript for loading address points using json
	 */
	private function address_point_whisperer()
	{
		$this->views['__pieces_address_point_database'] =
				View::factory('js/__pieces/address_point_database');
		
		$this->views['__pieces_address_point_database']->url = Settings::get('address_point_url');
	}
	
	/**
	 * Adds javascript for editing DNS records
	 */
	private function dns_records()
	{
		$this->views['__pieces_dns_records'] =
				View::factory('js/__pieces/dns_records')->render();
	}
	
	/**
	 * Adds javascript for toogling domicicles
	 */
	private function domicile_toogle()
	{
		$this->views['__pieces_domicile'] =
				View::factory('js/__pieces/domicile')->render();
	}
	
	/**
	 * Adds javascript for adding or editing of invoice.
	 * 
	 * @author Jan Dubina
	 */
	private function invoice_form() 
	{
		$this->views['__pieces_invoice'] =
				View::factory('js/__pieces/invoice')->render();
	}
	
	/**
	 * Adds javascript for handling of time activity rule attributes.
	 * 
	 * @author Ondřej Fibich
	 */
	private function time_activity_rule()
	{
		$this->views['__pieces_time_activity_rule'] =
				View::factory('js/__pieces/time_activity_rule')->render();
	}
	
	/**
	 * Calculator for VoIP calls
	 *
	 * @param integer $user_id 
	 */
	private function voip_calculator($user_id = NULL)
	{
		if ($user_id && is_numeric($user_id))
		{
			$user = new User_Model($user_id);
			
			if ($user && $user->id)
			{
				$sip = ORM::factory('voip_sip')
						->where('user_id', $user->id)
						->find();
				
				if ($sip && $sip->id)
				{
					$view = View::factory('js/__pieces/voip_calculator');
					$view->sip_name = $sip->name;
					$this->views['__pieces_voip_calculator'] = $view->render();
				}
			}
		}
	}
	
	/**
	 * Adds javascript for work reports forms
	 */
	private function work_report_form_functions()
	{
		$this->views_notready['__pieces_work_report_form_functions'] =
				View::factory('js/__pieces/work_report_form_functions')->render();
	}
	
	/**
	 * Adds javascript for notification messages
	 */
	private function notification_activate()
	{
		$this->views['__pieces_notification_activate'] =
				View::factory('js/__pieces/notification_activate')->render();
		$this->views['__pieces_notification_message'] =
				View::factory('js/__pieces/notification_message')->render();
	}
	
	/**
	 * Adds javascript for IP address adding/editing
	 */
	private function ip_addresses_complete()
	{
		$this->views['__pieces_ip_addresses_complete'] =
				View::factory('js/__pieces/ip_addresses_complete')->render();
	}
	
	/**
	 * Adds javascript for loading member's data from ARES
	 */
	private function members_ares()
	{
		$this->views['__pieces_members_ares'] =
				View::factory('js/__pieces/members_ares')->render();
	}
	
}
