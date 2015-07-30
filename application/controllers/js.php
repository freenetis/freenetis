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
	
	/***************** Methods for javascript queries *************************/
	
	private function _js_address_points_add()
	{
		$this->address_point_streets();
		$this->address_point_gps();
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
	
	private function _js_devices_add($user_id = NULL)
	{
		$user = new User_Model($user_id);
		
		if (!$user || !$user->id || !$user->devices->count())
		{
			$user_id = User_Model::ASSOCIATION;
		}
		
		$this->address_point_streets();
		$this->address_point_gps();
		
		$subnet = new Subnet_Model();
		$device = new Device_Model();
		
		$this->views['devices_add'] = View::factory('js/devices_add');
		$this->views['devices_add']->arr_subnets = $subnet->select_list_by_net();
		$this->views['devices_add']->arr_devices = $device->select_list_device_with_user($user_id);
	}
	
	private function _js_devices_add_simple()
	{
		$this->address_point_streets();
		$this->address_point_gps();
	}
	
	private function _js_devices_add_filter()
	{		
		$this->views['devices_add'] = View::factory('js/devices_add_filter');
		$this->views['devices_add']->filter_index = $this->input->get('index');
	}
	
	private function _js_devices_edit()
	{
		$this->address_point_streets();
		$this->address_point_gps();
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
	
	private function _js_ifaces_add($device_id = null, $itype = null,
			$add_button = FALSE, $connect_type = NULL)
	{
		$this->views['ifaces_add'] = View::factory('js/ifaces_add');
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
			if (!ORM::factory('iface')->get_type($type))
			{
				$type = $iface->type;
			}
		
			$this->views['ifaces_edit'] = View::factory('js/ifaces_add');
			$this->views['ifaces_edit']->device_id = $iface->device_id;
			$this->views['ifaces_edit']->itype = $type;
			$this->views['ifaces_edit']->add_button = $add_button;
			$this->views['ifaces_edit']->connect_type = $connect_type;
			$this->views['ifaces_edit']->is_edit = true;
		}
	}
	
	private function _js_members_add()
	{
		$this->address_point_streets();
		$this->address_point_gps();
		$this->domicile_toogle();
		$this->member_type();
	}
	
	private function _js_members_edit()
	{
		$this->address_point_streets();
		$this->address_point_gps();
		$this->domicile_toogle();
		$this->member_type();
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
			$fees = $fee_model->get_all_fees('type_id');
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
	
	private function _js_notifications_cloud()
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
	
	private function _js_registration()
	{
		$this->address_point_streets();
		$this->address_point_gps();
	}
	
	private function _js_transfers_payment_calculator($account_id = NULL)
	{
		$member_id = NULL;
		
		$account = new Account_Model($account_id);
		
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
	}
	
	private function _js_users_show($user_id = NULL)
	{
		$this->application_password($user_id);
		$this->views['users_show'] = View::factory('js/users_show')->render();
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
	 * Adds javascript for toogling domicicles
	 */
	private function domicile_toogle()
	{
		$this->views['__pieces_domicile'] =
				View::factory('js/__pieces/domicile')->render();
	}
	
	/**
	 * Adds javascript for handling of member type. (Form is changed according to type)
	 * 
	 * @author Ondřej Fibich
	 */
	private function member_type()
	{
		$this->views['__pieces_member_type'] =
				View::factory('js/__pieces/member_type')->render();
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
	
	private function notification_activate()
	{
		$this->views['__pieces_notification_activate'] =
				View::factory('js/__pieces/notification_activate')->render();
	}
	
}
