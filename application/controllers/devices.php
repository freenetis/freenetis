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
 * Controller performs devices actions.
 * Device is located at specified address point and owned by user.
 *  
 * @package Controller
 */
class Devices_Controller extends Controller
{
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('devices/show_all');
	}
		
	/**
	 * Function shows all devices.
	 * 
	 * @param integer $limit_results devices per page
	 * @param string $order_by sorting column
	 * @param string $order_by_direction sorting direction
	 */
	public function show_all($limit_results = 50, $order_by = 'device_id',
			$order_by_direction = 'ASC', $page_word = null, $page = 1)
	{	
		// access control
		if (!$this->acl_check_view(get_class($this), 'devices'))
			Controller::error(ACCESS);
		
		$filter_form = new Filter_form('d');
		
		$filter_form->add('name')
			->callback('json/device_name');
		
		$filter_form->add('type')
			->type('select')
			->values(ORM::factory('Enum_type')
			->get_values(Enum_type_model::DEVICE_TYPE_ID));
		
		$filter_form->add('trade_name')
			->callback('json/device_trade_name');
		
		$filter_form->add('user_name')
			->type('combo')
			->label('Firstname of user')
			->callback('json/user_name');
		
		$filter_form->add('user_surname')
			->type('combo')
			->label('Surname of user')
			->callback('json/user_surname');
		
		$filter_form->add('member_name')
			->callback('json/member_name');
		
		$filter_form->add('login')
			->label('Username')
			->callback('json/device_login');
		
		$filter_form->add('password')
			->callback('json/device_password');
		
		$filter_form->add('price')
			->type('number');
		
		$filter_form->add('payment_rate')
			->label('Monthly payment rate')
			->type('number');
		
		$filter_form->add('buy_date')
			->type('date');
		
		$filter_form->add('town')
			->type('combo')
			->callback('json/town_name');
		
		$filter_form->add('street')
			->type('combo')
			->callback('json/street_name');
		
		$filter_form->add('street_number')
			->type('number');
		
		$filter_form->add('comment');
		
		$filter_form->add('cloud')
			->type('select')
			->values(ORM::factory('cloud')->select_list());
		
		// get new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		$device_model = new Device_Model;		
		$total_devices = $device_model->count_all_devices($filter_form->as_sql());
		
		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_devices)
			$sql_offset = 0;	
		
		// query
		$devices = $device_model->get_all_devices(array
		(
			'offset'					=> $sql_offset,
			'limit'						=> (int) $limit_results,
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'filter_sql'				=> $filter_form->as_sql()
		));
		
		// headline
		$headline = __('Devices list');
		
		// grid of devices
		$grid = new Grid('devices', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang'). '/devices/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=>  $total_devices,
			'items_per_page' 			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		if ($this->acl_check_new(get_class($this), 'devices'))
		{
			$grid->add_new_button('devices/add', 'Add new device');
		}
		
		if (Settings::get('syslog_ng_mysql_api_enabled'))
		{
			$grid->add_new_button('device_logs/show_all', 'Show logs');
		}
		
		$grid->add_new_button('devices/map', __('Show device map'));
		
		if (Settings::get('monitoring_enabled') &&
			count(explode('&', server::query_string())) > 1)
		{
			$grid->add_new_button('monitoring/action'.server::query_string(), 'Monitoring', array
			(
				'title' => __('Monitoring'),
				'class' => 'popup_link'
			));
		}

		$grid->order_field('device_id')
				->label('ID')
				->class('center');
		
		$grid->order_field('device_name')
				->link('devices/show', 'device_name');
		
		$grid->order_field('type_name')
				->label('Type');
		
		$grid->order_link_field('user_id')
				->link('users/show', 'user_login');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Devices_Controller', 'devices'))
		{
			$actions->add_action('device_id')
					->icon_action('show')
					->url('devices/show');
		}
		
		if ($this->acl_check_edit('Devices_Controller', 'devices'))
		{
			$actions->add_action('device_id')
					->icon_action('edit')
					->url('devices/edit');
		}
			
		if ($this->acl_check_delete('Devices_Controller', 'devices'))
		{	
			$actions->add_action('device_id')
					->icon_action('delete')
					->url('devices/delete')
					->class('delete_link');
		}
			
		$grid->datasource($devices);
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = __('Devices');
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	} // end of show_all function
	
	/**
	 * Function shows all devices of user.
	 */
	public function show_by_user($user_id = null, $limit_results = 10,
			$order_by = 'id', $order_by_direction = 'asc', $page_word = 'page',
			$page = 1)
	{
		// bad parameter
		if (!$user_id || !is_numeric ($user_id))
			Controller::warning(PARAMETER);

		$user = new User_Model($user_id);

		// user doesn't exist
		if (!$user->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view(get_class($this), 'devices', $user->member_id))
			Controller::error(ACCESS);

		$device_model = new Device_Model();

		$total_devices = $device_model->count_devices_of_user($user->id);

		$allowed_order_by = array
		(
			'id'			=> __('Device ID'),
			'name'			=> __('Device name') ,
			'type'			=> __('Device type'),
			'mac'			=> __('MAC address'),
			'ip_address'	=> __('IP address')
		);

		$allowed_order_by_direction = array
		(
			'asc'	=> __('Ascending'),
			'desc'	=> __('Descending'),
		);

		if (!in_array($order_by, array_keys($allowed_order_by)))
			$order_by = 'id';

		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		// get new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		if (($sql_offset = ($page - 1) * $limit_results) > $total_devices)
			$sql_offset = 0;

		$order_form = new Forge(
				url::base(TRUE) . url::current(TRUE) . '#user-devices-advanced-grid'
		);
		
		$order_form->dropdown('order_by')
				->options($allowed_order_by)
				->rules('required')
				->selected($order_by);
		
		$order_form->dropdown('order_by_direction')
				->label('Direction')
				->options($allowed_order_by_direction)
				->rules('required')
				->selected($order_by_direction);
		
		$order_form->submit('Send');
		
		$devices = $device_model->get_devices_of_user(
				$user_id, FALSE, 0, NULL, $order_by, $order_by_direction
		);
		
		$base_grid = new Grid('devices', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> $total_devices
		));
		
		$base_grid->field('id');
		
		$base_grid->field('name');
		
		$base_grid->field('type');
	
		$base_grid->link_field('iface_id')
				->link('devices/show_iface', 'mac');
		
		$base_grid->callback_field('connected_to_device')
				->callback('callback::device_connected_to_device')
				->class('center');
		
		$base_grid->callback_field('ip_address')
			->callback('callback::ip_address_field');
		
		$base_grid->link_field('subnet_id')
				->link('subnets/show', 'subnet_name')
				->label('Subnet');
		
		$actions = $base_grid->grouped_action_field();
		
		if ($this->acl_check_view(get_class($this), 'devices', $user->member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('devices/show');
		}
		
		if ($this->acl_check_edit(get_class($this), 'devices', $user->member_id))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('devices/edit');
		}

		if ($this->acl_check_delete(get_class($this), 'devices', $user->member_id))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('devices/delete')
					->class('delete_link');
		}
		
		$base_grid->datasource($devices);
		
		$devices = $device_model->get_devices_of_user(
				$user_id, TRUE, $sql_offset, $limit_results, 
				$order_by, $order_by_direction
		);

		$this->selector = new Selector(array
		(
			'selector_max_multiplier' => 20,
			'current'			=> $limit_results,
			'base_url'			=> Config::get('lang').'/devices/show_by_user/'
								. $user->id.'/'.$limit_results.'/'.$order_by.'/'
								. $order_by_direction.'/'.$page_word.'/'.$page
								. '#user-devices-advanced-grid'
		));

		$this->pagination = new Pagination (array
		(
			'base_url'			=> Config::get('lang').'/devices/show_by_user/'
								. $user->id.'/'.$limit_results.'/'.$order_by.'/'
								. $order_by_direction.'/'.$page_word.'/'.$page
								. '#user-devices-advanced-grid',
			'total_items'		=> $total_devices,
			'items_per_page'	=> $limit_results,
			'uri_segment'		=> 'page'
		));

		$arr_devices = array();
		
		foreach ($devices as $device)
		{
			$arr_devices[$device->id] = array
			(
				'type'			=> $device->type,
				'name'			=> $device->name,
				'buy_date'		=> $device->buy_date,
				'grids'			=> array()
			);

			$grids = $this->create_device_grids(new Device_Model($device->id));
			$arr_devices[$device->id]['grids']['vlan interfaces'] = $grids;
		}

		$headline =  __('Device list of user');

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller','members'))
				->disable_translation()
				->link('members/show/' . $user->member->id,
						'ID ' . $user->member->id . ' - ' . $user->member->name,
						$this->acl_check_view('Members_Controller','members', $user->member->id))
				->enable_translation()
				->link('users/show_by_member/' . $user->member_id, 'Users',
						$this->acl_check_view('Users_Controller', 'users', $user->member_id))
				->disable_translation()
				->link('users/show/' . $user->id, 
						$user->name . ' ' . $user->surname . ' (' . $user->login . ')',
						$this->acl_check_view('Users_Controller', 'users',$user->member_id))
				->enable_translation()
				->text('Devices');

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('devices/show_by_user');
		$view->content->base_grid = $base_grid;
		$view->content->user_id = $user->id;
		$view->content->member_id = $user->member_id;
		$view->content->total_devices = $total_devices;
		$view->content->order_form = $order_form->html();
		$view->content->headline = $headline;
		$view->content->devices = $arr_devices;
		$view->render(TRUE);
	}
	
	/**
	 * Function shows device.
	 * 
	 * @param integer $device_id
	 */
	public function show($device_id = null)
	{	
		if (!isset($device_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$device = new Device_Model($device_id);
		
		if ($device->id == 0)
		{
			Controller::error(RECORD);
		}
		
		$member_id = $device->user->member_id;
		
		if (!$this->acl_check_view(get_class($this), 'devices', $member_id))
		{
			Controller::error(ACCESS);
		}
		
		$device_type = ORM::factory('enum_type')->get_value($device->type);

		// device engineers
		$de = ORM::factory('device_engineer')->get_device_engineers($device_id);
		
		$grid_device_engineers = new Grid('devices', null, array
		(
			'use_paginator'				=> false,
			'use_selector'				=> false,
			'total_items'				=> count($de)
		));
		
		if ($this->acl_check_new('Devices_Controller', 'engineer', $member_id))
		{
			$grid_device_engineers->add_new_button(
					'device_engineers/add/' . $device_id, 'Add new device engineer'
			);
		}
		
		$grid_device_engineers->field('name');
		
		$grid_device_engineers->field('surname');
		
		$grid_device_engineers->field('login')
				->label('Login name');
		
		if ($this->acl_check_delete('Devices_Controller', 'engineer', $member_id))
		{
			
			$grid_device_engineers->grouped_action_field()
					->add_action('id')
					->icon_action('delete')
					->url('device_engineers/delete')
					->class('delete_link');
		}
			
		$grid_device_engineers->datasource($de);
		
		// device admins
		$device_admin_model = new Device_admin_Model();
		$da = $device_admin_model->get_device_admins($device_id);
		
		$grid_device_admins = new Grid('devices', null, array
		(
			'use_paginator'				=> false,
			'use_selector'				=> false,
			'total_items'				=> count($da)
		));
		
		if ($this->acl_check_edit(get_class($this), 'admin', $member_id))
		{
			$grid_device_admins->add_new_button(
					'device_admins/edit/' . $device_id, 'Edit device admins'
			);
		}
		
		$grid_device_admins->field('name');
		
		$grid_device_admins->field('surname');	
		
		$grid_device_admins->field('login')
				->label('Login name');
		
		if ($this->acl_check_delete('Devices_Controller', 'engineer', $member_id))
		{
			$grid_device_admins->grouped_action_field()
					->add_action('id')
					->icon_action('delete')
					->url('device_admins/delete')
					->class('delete_link');
		}
		
		$grid_device_admins->datasource($da);
		
		// iface grids
		$grids = $this->create_device_grids($device);

		$gps = '';

		if (!empty($device->address_point->gps))
		{
		    $gps_result = $device->address_point->get_gps_coordinates();

		    if (! empty($gps_result))
		    {
				$gps = gps::degrees($gps_result->gpsx, $gps_result->gpsy, true);
		    }
		}
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller','members'))
				->disable_translation()
				->link('members/show/' . $device->user->member->id,
						'ID ' . $device->user->member->id . ' - ' . $device->user->member->name,
						$this->acl_check_view('Members_Controller','members', $device->user->member->id))
				->enable_translation()
				->link('users/show_by_member/' . $device->user->member_id, 'Users',
						$this->acl_check_view('Users_Controller', 'users', $device->user->member_id))
				->disable_translation()
				->link('users/show/' . $device->user->id, 
						$device->user->name . ' ' . $device->user->surname . ' (' . $device->user->login . ')',
						$this->acl_check_view('Users_Controller', 'users', $device->user->member_id))
				->enable_translation()
				->link('devices/show_by_user/' . $device->user->id, 'Devices',
						$this->acl_check_view(
								get_class($this), 'devices',
								$device->user->member_id
						)
				)->disable_translation()
				->text(($device->name != '') ? $device->name : $device_type);
		
		// view
		$view = new View('main');
		$view->title = __('Device').' '.$device->name;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('devices/show');
		$view->content->device = $device;
		$view->content->device_type = $device_type;
		$view->content->count_engineers = count($de);
		$view->content->count_admins = count($da);
		$view->content->ifaces = $device->ifaces;
		$view->content->table_device_engineers = $grid_device_engineers;
		$view->content->table_device_admins	= $grid_device_admins;
		$view->content->table_ip_addresses = $grids['ip_addresses'];
		$view->content->ifaces = $grids['ifaces'];
		$view->content->vlan_ifaces = $grids['vlan_ifaces'];
		$view->content->port_ifaces = $grids['ports'];
		$view->content->ethernet_ifaces = $grids['ethernet_ifaces'];
		$view->content->internal_ifaces = $grids['internal_ifaces'];
		$view->content->wireless_ifaces = $grids['wireless_ifaces'];
		$view->content->bridge_ifaces = $grids['bridge_ifaces'];
		$view->content->special_ifaces = $grids['special_ifaces'];
		$view->content->gps = $gps;
		$view->content->gpsx = !empty($gps) ? $gps_result->gpsx : '';
		$view->content->gpsy = !empty($gps) ? $gps_result->gpsy : '';
        $view->content->lang = Config::get('lang');
		$view->render(TRUE);
	} // end of show
	
	/**
	 * Adds whole device. It means it creates new device, new interface assigned to this device
	 * and new ip address assigned to this interface.
	 * 
	 * @param integer $user_id
	 */
	public function add($user_id = null)
	{
		if (!$this->acl_check_new(get_class($this), 'devices'))
		{
			Controller::error(ACCESS);
		}
		
		$selected_engineer = $this->session->get('user_id');
			
		$gpsx = '';
		$gpsy = '';
		
		if (isset($user_id))
		{
			$um = new User_Model($user_id);
			
			if (!$um->id)
			{
				Controller::error(RECORD);
			}
			
			$selected = $um->id;
			$selected_country_id = $um->member->address_point->country_id;
			$selected_street_id = $um->member->address_point->street_id;
			$selected_street_number = $um->member->address_point->street_number;
			$selected_town_id = $um->member->address_point->town_id;
			$gps = $um->member->address_point->get_gps_coordinates();
			
			if ($gps)
			{
				$gpsx = gps::real2degrees($gps->gpsx, FALSE);
				$gpsy = gps::real2degrees($gps->gpsy, FALSE);
			}
			
			$found_engineer = ORM::factory('device_engineer')->get_engineer_of_user($um->id);
			
			if ($found_engineer)
			{
				$selected_engineer = $found_engineer->id;
			}
			
			$arr_users[$um->id] = $um->get_name_with_login();
		}
		else
		{
			$um = new User_Model();
			$selected = 0;
			$selected_country_id = Settings::get('default_country');
			$selected_street_id = 0;
			$selected_street_number = '';
			$selected_town_id = 0;
			
			$arr_users = array
			(
				NULL => '----- '.__('select user').' -----'
			) + $um->select_list_grouped();
		}
		
		// enum types for device
		$enum_type_model = new Enum_type_Model();
		$types = $enum_type_model->get_values(Enum_type_Model::DEVICE_TYPE_ID);
		$types[NULL] = '----- '.__('select type').' -----';
		asort($types);
		
		$arr_unit = array
		(
			'K'		=> 'kbps',
			'M'		=> 'Mbps',
			'G'		=> 'Gbps',
			'T'		=> 'Tbps'
		);
		
		// wireless modes
		$arr_wireless_modes = Iface_Model::get_wireless_modes();
		
		// wireless antenna types
		$arr_wireless_antennas = array
		(
			NULL => '----- '.__('Select antenna').' -----'
		) + Iface_Model::get_wireless_antennas();
		
		// all device templates
		$arr_device_templates = array
		(
			NULL => '----- '.__('Select template').' -----'
		) + ORM::factory('device_template')->select_list();
		
		// country
		$arr_countries = ORM::factory('country')->select_list('id', 'country_name');
			   		
		// streets
		$arr_streets = array
		(
			NULL => '----- '.__('Without street').' -----'
		)  + ORM::factory('street')->select_list('id', 'street');
		
		// towns
		$arr_towns = array
		(
			NULL => '----- '.__('Select town').' -----'
		) + ORM::factory('town')->select_list_with_quater();
		
		// wireless norms
		$arr_wireless_norms = Link_Model::get_wireless_norms();
		
		// wireless polarizations
		$arr_wireless_polarizations = Link_Model::get_wireless_polarizations();
		
		// ports
		$port_modes = array
		(
			NULL => '----- '.__('Select mode').' -----'
		) + Iface_Model::get_port_modes();
		
		// ethernet mediums
		$eth_mediums = Iface_Model::get_types_has_link_with_medium(Iface_Model::TYPE_ETHERNET);
		// wireless mediums
		$wl_mediums = Iface_Model::get_types_has_link_with_medium(Iface_Model::TYPE_WIRELESS);
		// port mediums
		$port_mediums = Iface_Model::get_types_has_link_with_medium(Iface_Model::TYPE_PORT);

		// list of engineers
		if ($this->acl_check_edit('Devices_Controller', 'main_engineer'))
		{
			$arr_engineers = $um->select_list_grouped();
		}
		else
		{
			$engineer = new User_Model($this->session->get('user_id'));
			$arr_engineers[$engineer->id] = $engineer->get_full_name_with_login();
		}
		
		// forge form
		$form = new Forge('devices/add' . (isset($user_id) ? '/' . $user_id : ''));
		
		$form->set_attr('id', 'device_add_form');
		
		$group_device = $form->group('Device');
		
		$group_device->input('device_name')
				->label('Device name')
				->value(($user_id) ? $um->surname : '')
				->rules('required|length[2,200]')
				->style('width: 520px');
		
		$group_device->dropdown('user_id')
				->label('User')
				->rules('required')
				->options($arr_users)
				->selected($selected)
				->style('width: 200px');
		
		$group_device->dropdown('device_type')
				->options($types)
				->rules('required')
				->style('width: 200px');
		
		$group_device->dropdown('device_template_id')
				->options($arr_device_templates)
				->label('Device template')
				->rules('required')
				->style('width: 200px')
				->add_button('device_templates');
		
		$group_device_details = $form->group('Device detail')->visible(FALSE);
		
		$group_device_details->dropdown('PPPoE_logging_in')
				->label('PPPoE')
				->options(arr::rbool());
		
		if ($this->acl_check_new(get_class($this), 'login'))
		{
			$group_device_details->input('login')
					->label('Username')
					->rules('length[0,30]')
					->autocomplete('json/device_login');
		}
		
		if ($this->acl_check_new(get_class($this), 'password'))
		{
			$group_device_details->input('login_password')
					->label('Password')
					->rules('length[0,30]')
					->autocomplete('json/device_password');
		}
		
		$group_device_details->dropdown('first_engineer_id')
				->label(__('Engineer').help::hint('engineer'))
				->options($arr_engineers)
				->rules('required')
				->selected($selected_engineer)
				->style('width: 200px');
		
		$group_device_details->textarea('device_comment')
				->label('Comment')
				->rules('length[0,254]')
				->style('width: 520px');
		
		$group_payment = $form->group('Device repayments')->visible(FALSE);
		
		$group_payment->input('price')
				->rules('valid_numeric');
		
		$group_payment->input('payment_rate')
				->label('Monthly payment rate')
				->rules('valid_numeric');
		
		$group_payment->date('buy_date')
				->label('Buy date')
				->years(date('Y')-100, date('Y'));
		
		$group_address = $form->group('Address');
		
		if (!empty($user_id))
		{
			$group_address->visible(!$um->id);
		}
		
		$group_address->dropdown('town_id')
				->label('Town')
				->rules('required')
				->options($arr_towns)
				->selected($selected_town_id)
				->style('width: 200px')
				->add_button('towns');
		
		$group_address->dropdown('street_id')
				->label('Street')
				->options($arr_streets)
				->selected($selected_street_id)
				->add_button('streets')
				->style('width: 200px');
		
		$group_address->input('street_number')
				->rules('length[1,50]')
				->value($selected_street_number);
		
		$group_address->dropdown('country_id')
				->label('Country')
				->rules('required')
				->options($arr_countries)
				->selected(Settings::get('default_country'))
				->style('width: 200px');
		
		$group_address->input('gpsx')
				->label(__('GPS').'&nbsp;X:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps')
				->value($gpsx);
		
		$group_address->input('gpsy')
				->label(__('GPS').'&nbsp;Y:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps')
				->value($gpsy);

		$form->group('Ethernet interfaces');
		$form->group('Wireless interfaces');
		$form->group('Ports');
		$form->group('Internal interfaces');

		// submit button
		$form->submit('Confirm');
		
		$default_filter_user = User_Model::ASSOCIATION;
		
		// count of users devices
		if ($um->id)
		{
			if ($um->devices->count())
			{
				$default_filter_user = $um->id;
			}
		}
		
		// validates form and saves data 
		if($form->validate())
		{
			$form_data = $form->as_array();
			$dm = new Device_Model();
			
			$update_allowed_params = array();
			
			try
			{
				$dm->transaction_start();
				
				// device //////////////////////////////////////////////////////
				$dm->user_id = $form_data['user_id'];

				if (!isset($user_id))
				{
					$um = new User_Model($dm->user_id);
				}

				if (empty($form_data['device_name']))
				{
					$dm->name = $um->login.'_'.$types[$form_data['device_type']];
				}
				else 
				{
					$dm->name = $form_data['device_name'];
				}
				
				$device_template = new Device_template_Model($form_data['device_template_id']);

				if ($device_template && $device_template->id)
				{
					$dm->trade_name = $device_template->name;
				}
				
				$dm->type = $form_data['device_type'];
				$dm->PPPoE_logging_in = $form_data['PPPoE_logging_in'];

				if ($this->acl_check_new(get_class($this), 'login'))
				{
					$dm->login = $form_data['login'];
				}

				if ($this->acl_check_new(get_class($this), 'password'))
				{
					$dm->password = $form_data['login_password'];
				}

				$dm->price = $form_data['price'];	
				$dm->payment_rate = $form_data['payment_rate'];
				$dm->buy_date = date('Y-m-d', $form_data['buy_date']);	
				$dm->comment = $form_data['device_comment'];

				// address point ///////////////////////////////////////////////////

				// gps
				$gpsx = NULL;
				$gpsy = NULL;

				if (!empty($form_data['gpsx']) && !empty($form_data['gpsy']))
				{
					$gpsx = doubleval($form_data['gpsx']);
					$gpsy = doubleval($form_data['gpsy']);

					if (gps::is_valid_degrees_coordinate($form_data['gpsx']))
					{
						$gpsx = gps::degrees2real($form_data['gpsx']);
					}

					if (gps::is_valid_degrees_coordinate($form_data['gpsy']))
					{
						$gpsy = gps::degrees2real($form_data['gpsy']);
					}
				}

				$address_point_model = new Address_point_Model();

				$ap = $address_point_model->get_address_point(
						$form_data['country_id'], $form_data['town_id'],
						$form_data['street_id'], $form_data['street_number'],
						$gpsx, $gpsy
				);

				// add address point if there is no such
				if (!$ap->id)
				{
					$ap->save_throwable();
				}

				// add GPS
				if (!empty($gpsx) && !empty($gpsy))
				{ // save
					$ap->update_gps_coordinates($ap->id, $gpsx, $gpsy);
				}
				else
				{ // delete gps
					$ap->gps = '';
					$ap->save_throwable();
				}

				$dm->address_point_id = $ap->id;
				$dm->save_throwable();

				// device engineer ////////////////////////////////////////////
				
				$device_engineer = new Device_engineer_Model();
				$device_engineer->device_id = $dm->id;
				$device_engineer->user_id = $form_data['first_engineer_id'];
				$device_engineer->save();
				
				// ifaces //////////////////////////////////////////////////////

				$post_use = isset($_POST['use']) ? $_POST['use'] : array();
				
				foreach ($post_use as $i => $v)
				{
					// skip not used
					if ($v != 1)
					{
						continue;
					}
					// save iface
					$im = new Iface_Model();
					$im->device_id = $dm->id;
					$im->name = htmlspecialchars($_POST['name'][$i]);
					$im->comment = htmlspecialchars($_POST['comment'][$i]);
					$im->type = intval($_POST['type'][$i]);
					
					if ($im->type == Iface_Model::TYPE_PORT)
					{
						$im->number = intval($_POST['number'][$i]);
						$im->port_mode = intval($_POST['port_mode'][$i]);
					}
					else
					{
						$im->mac = htmlspecialchars($_POST['mac'][$i]);
					}
					
					if ($im->type == Iface_Model::TYPE_WIRELESS)
					{
						$im->wireless_antenna = intval($_POST['wireless_antenna'][$i]);
						$im->wireless_mode = intval($_POST['wireless_mode'][$i]);
					}
					
					// can autosave?
					$autosave_may = TRUE;
					
					if (isset($_POST['connected_iface'][$i]))
					{
						// restrict blank fields
						if (!(Iface_Model::type_has_ip_address($im->type) &&
							Iface_Model::type_has_mac_address($im->type) &&
							empty($im->mac) && (
									!isset($_POST['ip'][$i]) ||
									empty($_POST['ip'][$i]) ||
									!valid::ip_address($_POST['ip'][$i])
							)))
						{
							// connected iface
							$im_connect_to = new Iface_Model($_POST['connected_iface'][$i]);

							// save link
							if (Iface_Model::type_has_link($im->type) &&
								$im_connect_to && $im_connect_to->id)
							{
								// disable autosave
								$autosave_may = FALSE;
								
								$roaming = new Link_Model();
								$link_id = $_POST['link_id'][$i];
								$roaming_id = $roaming->get_roaming();
								$roaming = $roaming->find($roaming_id);
								$name = $_POST['link_name'][$i];
								$medium = $_POST['medium'][$i];
								
								// don not connect to roaming
								if ($link_id == $roaming_id)
								{
									$link_id = NULL;
									// fix name
									if (trim($name) == trim($roaming->name))
									{
										if ($im->type == Iface_Model::TYPE_WIRELESS)
										{
											$name = __('air') . ' ';
										}
										else
										{
											$name = __('cable') . ' ';
										}
										
										if ($im_connect_to->type == Iface_Model::TYPE_WIRELESS &&
											$im_connect_to->wireless_mode == Iface_Model::WIRELESS_MODE_AP)
										{
											$name .= $im_connect_to->device->name;
											$name .= ' - ' . $dm->name;
										}
										else
										{
											$name .= $dm->name . ' - ';
											$name .= $im_connect_to->device->name;
										}
										
										// fix medium
										if ($medium == Link_Model::MEDIUM_ROAMING)
										{
											if ($im->type == Iface_Model::TYPE_WIRELESS)
											{
												$medium = Link_Model::MEDIUM_AIR;
											}
											else
											{
												$medium = Link_Model::MEDIUM_CABLE;
											}
										}
									}
								}
								
								$lm = new Link_Model($link_id);
								$lm->name = htmlspecialchars($name);
								$lm->medium = intval($medium);
								$lm->comment = htmlspecialchars($_POST['link_comment'][$i]);
								$lm->bitrate = network::str2bytes($_POST['bitrate'][$i]);
								$lm->duplex = ($_POST['duplex'][$i] == 1);

								if ($im->type == Iface_Model::TYPE_WIRELESS)
								{
									$lm->wireless_ssid = htmlspecialchars($_POST['wireless_ssid'][$i]);
									$lm->wireless_norm = intval($_POST['wireless_norm'][$i]);
									$lm->wireless_frequency = intval($_POST['wireless_frequency'][$i]);
									$lm->wireless_channel = intval($_POST['wireless_channel'][$i]);
									$lm->wireless_channel_width = intval($_POST['wireless_channel_width'][$i]);
									$lm->wireless_polarization = intval($_POST['wireless_polarization'][$i]);
								}

								$lm->save_throwable();
								
								// restrict count of connected devices to link
								$max = Link_Model::get_max_ifaces_count($im->type);
								
								if ($lm->id != $roaming_id &&
									$max <= 2) // delete connected (port, eth)
								{
									foreach ($lm->ifaces as $i_del)
									{
										$i_del->link_id = null;
										$i_del->save_throwable();
									}
								}
								
								$im->link_id = $lm->id;
								$im_connect_to->link_id = $lm->id;
								$im_connect_to->save_throwable();
							}
						}
					}
					
					// autosave (add) link
					if (isset($_POST['link_autosave'][$i]) &&
						$_POST['link_autosave'][$i] && $autosave_may)
					{
						$lm = new Link_Model();
						$lm->name = htmlspecialchars($_POST['link_name'][$i]);
						$lm->medium = intval($_POST['medium'][$i]);
						$lm->comment = htmlspecialchars($_POST['link_comment'][$i]);
						$lm->bitrate = network::str2bytes($_POST['bitrate'][$i]);
						$lm->duplex = ($_POST['duplex'][$i] == 1);

						if ($im->type == Iface_Model::TYPE_WIRELESS)
						{
							$lm->wireless_ssid = htmlspecialchars($_POST['wireless_ssid'][$i]);
							$lm->wireless_norm = intval($_POST['wireless_norm'][$i]);
							$lm->wireless_frequency = intval($_POST['wireless_frequency'][$i]);
							$lm->wireless_channel = intval($_POST['wireless_channel'][$i]);
							$lm->wireless_channel_width = intval($_POST['wireless_channel_width'][$i]);
							$lm->wireless_polarization = intval($_POST['wireless_polarization'][$i]);
						}

						$lm->save_throwable();
						$im->link_id = $lm->id;
					}
					
					$im->save_throwable();
					
					if (isset($_POST['ip'][$i]) && valid::ip_address($_POST['ip'][$i]))
					{
						// save IP address
						$ipm = new Ip_address_Model();
						$ipm->iface_id = $im->id;
						$ipm->subnet_id = intval($_POST['subnet'][$i]);
						$ipm->member_id = NULL;
						$ipm->ip_address = htmlspecialchars($_POST['ip'][$i]);
						$ipm->dhcp = ($_POST['dhcp'][$i] == 1);
						$ipm->gateway = ($_POST['gateway'][$i] == 1);
						$ipm->service = ($_POST['service'][$i] == 1);
						$ipm->save_throwable();

						// allowed subnet to added IP
						$update_allowed_params[] = array
						(
							'member_id' => $dm->user->member_id,
							'to_enable' => array($ipm->subnet_id)
						);
					}
				}

				// done
				unset($form_data);
				$dm->transaction_commit();
				
				//Update allowed subnets after transaction is successfully commited
				foreach ($update_allowed_params as $params)
				{
					Allowed_subnets_Controller::update_enabled(
							$params['member_id'],
							$params['to_enable']
					);
				}
				
				status::success('Device has been successfully saved.');
				url::redirect('devices/show/'.$dm->id);
			}
			catch (Exception $e)
			{
				$dm->transaction_rollback();			
				Log::add_exception($e);
				status::error('Device has not been successfully saved.');
			}		
		}
		
		if (isset($user_id))
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/' . $um->member->id,
							'ID ' . $um->member->id . ' - ' . $um->member->name,
							$this->acl_check_view('Members_Controller', 'members', $um->member->id))
					->enable_translation()
					->link('users/show_by_member/' . $um->member_id, 'Users',
							$this->acl_check_view('Users_Controller', 'users', $um->member_id))
					->disable_translation()
					->link('users/show/' . $um->id, 
							$um->name . ' ' . $um->surname . ' (' . $um->login . ')',
							$this->acl_check_view('Users_Controller', 'users', $um->member_id))
					->enable_translation()
					->link('devices/show_by_user/' . $um->id, 'Devices',
							$this->acl_check_view('Devices_Controller', 'devices', $um->member_id))
					->text('Add new whole device');
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('devices/show_all', 'Devices',
							$this->acl_check_view('Devices_Controller','devices'))
					->text('Add new whole device');
		}
		
		$view = new View('main');
		$view->title = __('Add new whole device');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('devices/add');
		$view->content->form = $form->html();
		$view->content->headline = __('Add new whole device');
		$view->content->yes_no_option = arr::rbool();
		$view->content->port_modes = $port_modes;
		$view->content->wireless_modes = $arr_wireless_modes;
		$view->content->wireless_antennas = $arr_wireless_antennas;
		$view->content->norms = $arr_wireless_norms;
		$view->content->polarizations = $arr_wireless_polarizations;
		$view->content->bit_units = $arr_unit;
		$view->content->eth_mediums = $eth_mediums;
		$view->content->wl_mediums = $wl_mediums;
		$view->content->port_mediums = $port_mediums;
		$view->content->filter = self::device_filter_form($default_filter_user); 
		$view->render(TRUE);
	} // end of function add

	/**
	 * Function adds simple device without any interfaces, IP addresses ...
	 * 
	 * @author David Raška
	 * @param int $user_id
	 */
	public function add_simple($user_id = null)
	{
		if (!$this->acl_check_new(get_class($this), 'devices'))
		{
			Controller::error(ACCESS);
		}
		
		$selected_engineer = $this->session->get('user_id');
			
		$gpsx = '';
		$gpsy = '';
		
		if (isset($user_id))
		{
			$um = new User_Model($user_id);
			
			if (!$um->id)
			{
				Controller::error(RECORD);
			}
			
			$selected = $um->id;
			$selected_country_id = $um->member->address_point->country_id;
			$selected_street_id = $um->member->address_point->street_id;
			$selected_street_number = $um->member->address_point->street_number;
			$selected_town_id = $um->member->address_point->town_id;
			$gps = $um->member->address_point->get_gps_coordinates();
			
			if ($gps)
			{
				$gpsx = gps::real2degrees($gps->gpsx, FALSE);
				$gpsy = gps::real2degrees($gps->gpsy, FALSE);
			}
			
			$found_engineer = ORM::factory('device_engineer')->get_engineer_of_user($um->id);
			
			if ($found_engineer)
			{
				$selected_engineer = $found_engineer->id;
			}
			
			$arr_users[$um->id] = $um->get_name_with_login();
		}
		else
		{
			$um = new User_Model();
			$selected = 0;
			$selected_country_id = Settings::get('default_country');
			$selected_street_id = 0;
			$selected_street_number = '';
			$selected_town_id = 0;
			
			$arr_users = array
			(
				NULL => '----- '.__('select user').' -----'
			) + $um->select_list_grouped();
		}
		
		// enum types for device
		$enum_type_model = new Enum_type_Model();
		$types = $enum_type_model->get_values(Enum_type_Model::DEVICE_TYPE_ID);
		$types[NULL] = '----- '.__('select type').' -----';
		asort($types);
		
		// country
		$arr_countries = ORM::factory('country')->select_list('id', 'country_name');
			   		
		// streets
		$arr_streets = array
		(
			NULL => '----- '.__('without street').' -----'
		)  + ORM::factory('street')->select_list('id', 'street');
		
		// towns
		$arr_towns = array
		(
			NULL => '----- '.__('select town').' -----'
		) + ORM::factory('town')->select_list_with_quater();
		
		// list of engineers
		if ($this->acl_check_edit('Devices_Controller', 'main_engineer'))
		{
			$arr_engineers = $um->select_list_grouped();
		}
		else
		{
			$engineer = new User_Model($this->session->get('user_id'));
			$arr_engineers[$engineer->id] = $engineer->get_full_name_with_login();
		}
		
		// forge form
		$form = new Forge('devices/add_simple');
		
		$form->set_attr('id', 'device_add_form');
		
		$group_device = $form->group('Device');
		
		$group_device->input('device_name')
				->value(($user_id) ? $um->surname : '')
				->rules('required|length[2,200]')
				->style('width: 520px');
				
		$group_device->input('trade_name')
				->style('width: 520px');
		
		$group_device->dropdown('user_id')
				->label('User')
				->rules('required')
				->options($arr_users)
				->selected($selected)
				->style('width: 200px');
		
		$group_device->dropdown('device_type')
				->options($types)
				->rules('required')
				->style('width: 200px');
		
		$group_device_details = $form->group('Device detail')->visible(FALSE);
		
		$group_device_details->dropdown('PPPoE_logging_in')
				->label('PPPoE')
				->options(arr::rbool());
		
		if ($this->acl_check_new(get_class($this), 'login'))
		{
			$group_device_details->input('login')
					->label('Username')
					->rules('length[0,30]')
					->autocomplete('json/device_login');
		}
		
		if ($this->acl_check_new(get_class($this), 'password'))
		{
			$group_device_details->input('login_password')
					->label('Password')
					->rules('length[0,30]')
					->autocomplete('json/device_password');
		}
		
		$group_device_details->dropdown('first_engineer_id')
				->label('Engineer')
				->options($arr_engineers)
				->rules('required')
				->selected($selected_engineer)
				->style('width: 200px');
		
		$group_device_details->textarea('device_comment')
				->label('Comment')
				->rules('length[0,254]')
				->style('width: 520px');
		
		$group_payment = $form->group('Device repayments')->visible(FALSE);
		
		$group_payment->input('price')
				->rules('valid_numeric');
		
		$group_payment->input('payment_rate')
				->label('Monthly payment rate')
				->rules('valid_numeric');
		
		$group_payment->date('buy_date')
				->label('Buy date')
				->years(date('Y')-100, date('Y'));
		
		$group_address = $form->group('Address');
		
		if (!empty($user_id))
		{
			$group_address->visible(!$um->id);
		}
		
		$group_address->dropdown('town_id')
				->label('Town')
				->rules('required')
				->options($arr_towns)
				->selected($selected_town_id)
				->style('width: 200px')
				->add_button('towns');
		
		$group_address->dropdown('street_id')
				->label('Street')
				->options($arr_streets)
				->selected($selected_street_id)
				->add_button('streets')
				->style('width: 200px');
		
		$group_address->input('street_number')
				->rules('length[1,50]')
				->value($selected_street_number);
		
		$group_address->dropdown('country_id')
				->label('Country')
				->rules('required')
				->options($arr_countries)
				->selected(Settings::get('default_country'))
				->style('width: 200px');
		
		$group_address->input('gpsx')
				->label(__('GPS').'&nbsp;X:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps')
				->value($gpsx);
		
		$group_address->input('gpsy')
				->label(__('GPS').'&nbsp;Y:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps')
				->value($gpsy);
		
		$form->submit('Send');
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			$dm = new Device_Model();
			
			try
			{
				$dm->transaction_start();
				// gps
				$gpsx = NULL;
				$gpsy = NULL;

				if (!empty($form_data['gpsx']) && !empty($form_data['gpsy']))
				{
					$gpsx = doubleval($form_data['gpsx']);
					$gpsy = doubleval($form_data['gpsy']);

					if (gps::is_valid_degrees_coordinate($form_data['gpsx']))
					{
						$gpsx = gps::degrees2real($form_data['gpsx']);
					}

					if (gps::is_valid_degrees_coordinate($form_data['gpsy']))
					{
						$gpsy = gps::degrees2real($form_data['gpsy']);
					}
				}

				$dm->user_id = $form_data['user_id'];
				$dm->name = $form_data['device_name'];
				$dm->type = $form_data['device_type'];
				$dm->trade_name = $form_data['trade_name'];
				$dm->PPPoE_logging_in = $form_data['PPPoE_logging_in'];

				if ($this->acl_check_new(get_class($this), 'login'))
				{
					$dm->login = $form_data['login'];
				}

				if ($this->acl_check_new(get_class($this), 'password'))
				{
					$dm->password = $form_data['login_password'];
				}

				$dm->comment = $form_data['device_comment'];
				$dm->price = $form_data['price'];
				$dm->payment_rate = $form_data['payment_rate'];
				$dm->buy_date = date('Y-m-d', $form_data['buy_date']);

				$ap = ORM::factory('address_point')->get_address_point(
						$form_data['country_id'], $form_data['town_id'],
						$form_data['street_id'], $form_data['street_number'],
						$gpsx, $gpsy
				);

				$dm->save_throwable();

				// add address point if there is no such
				if (!$ap->id)
				{
					// save
					$ap->save_throwable();
				}
				// new addresspoint
				if ($ap->id != $dm->address_point_id)
				{
					// delete old?
					$addr_id = $dm->address_point->id;
					// add to device
					$dm->address_point_id = $ap->id;
					$dm->save_throwable();
					// change just for this device?
					if ($ap->count_all_items_by_address_point_id($addr_id) < 1)
					{
						$addr = new Address_point_Model($addr_id);
						$addr->delete();
					}
				}

				// add GPS
				if (!empty($gpsx) && !empty($gpsy))
				{ // save
					$ap->update_gps_coordinates($ap->id, $gpsx, $gpsy);
				}
				else
				{ // delete gps
					$ap->gps = '';
					$ap->save_throwable();
				}

				unset($form_data);

				$dm->transaction_commit();
				
				$this->redirect('devices/show/', $dm->id);
			}
			catch (Exception $e)
			{
				$dm->transaction_rollback();
				Log::add_exception($e);
			}
		}
		else
		{
			if (isset($user_id))
			{
				// breadcrumbs navigation
				$breadcrumbs = breadcrumbs::add()
						->link('members/show_all', 'Members',
								$this->acl_check_view('Members_Controller', 'members'))
						->disable_translation()
						->link('members/show/' . $um->member->id,
								'ID ' . $um->member->id . ' - ' . $um->member->name,
								$this->acl_check_view('Members_Controller', 'members', $um->member->id))
						->enable_translation()
						->link('users/show_by_member/' . $um->member_id, 'Users',
								$this->acl_check_view('Users_Controller', 'users', $um->member_id))
						->disable_translation()
						->link('users/show/' . $um->id, 
								$um->name . ' ' . $um->surname . ' (' . $um->login . ')',
								$this->acl_check_view('Users_Controller', 'users', $um->member_id))
						->enable_translation()
						->link('devices/show_by_user/' . $um->id, 'Devices',
								$this->acl_check_view('Devices_Controller', 'devices', $um->member_id))
						->text('Add new whole device');
			}
			else
			{
				// breadcrumbs navigation
				$breadcrumbs = breadcrumbs::add()
						->link('devices/show_all', 'Devices',
								$this->acl_check_view('Devices_Controller','devices'))
						->text('Add new whole device');
			}

			$headline = __('Add new device');

			// view
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->form = $form->html();
			$view->content->headline = $headline;
			$view->render(TRUE);
		}
	}
        
	/**
	 * Function edits device.
	 * 	
	 * @param integer $device_id
	 */
	public function edit($device_id = null) 
	{
		if (!isset($device_id) || !is_numeric($device_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$device	= new Device_Model($device_id);
		
		if ($device->id == 0)
		{
			Controller::error(RECORD);
		}
		
		$member_id = $device->user->member_id;
		
		if (!$this->acl_check_edit(get_class($this), 'devices', $member_id))
		{
			Controller::error(ACCESS);
		}

		// gps
		$gpsx = '';
		$gpsy = '';
		
		if (!empty($device->address_point->gps))
		{
		    $gps_result = $device->address_point->get_gps_coordinates();

		    if (!empty($gps_result))
		    {
				$gpsx = gps::real2degrees($gps_result->gpsx, false);
				$gpsy = gps::real2degrees($gps_result->gpsy, false);
		    }
		}
		
		// users
		$arr_users = ORM::factory('user')->select_list_grouped();
		
		// types
		$arr_types = ORM::factory('enum_type')->get_values(Enum_type_Model::DEVICE_TYPE_ID);

		// country
		$arr_countries = ORM::factory('country')->select_list('id', 'country_name');
			   		
		// streets
		$arr_streets = array
		(
			NULL => '----- '.__('without street').' -----'
		) + $device->address_point->town->streets->select_list('id', 'street');
		
		// towns
		$arr_towns = array
		(
			NULL => '----- '.__('select town').' -----'
		) + ORM::factory('town')->select_list_with_quater();
		
		// form
		$form = new Forge('devices/edit/' . $device_id);
		
		$group_device = $form->group('Basic data');
		
		$group_device->input('device_name')
				->label('Device name')
				->rules('length[2,200]')
				->value($device->name)
				->style('width: 520px');
		
		$group_device->dropdown('user_id')
				->label('User')
				->options($arr_users)
				->rules('required')
				->selected($device->user_id)
				->style('width: 200px');
		
		$group_device->dropdown('type')
				->label('Type')
				->options($arr_types)
				->rules('required')
				->selected($device->type)
				->style('width: 200px');
		
		$group_device_details = $form->group('Device detail');
		
		$group_device_details->input('trade_name')
				->label('Trade name')
				->rules('length[1,200]')
				->value($device->trade_name);
		
		$group_device_details->dropdown('PPPoE_logging_in')
				->label('PPPoE')
				->options(arr::rbool())
				->selected($device->PPPoE_logging_in);
		
		if ($this->acl_check_edit(get_class($this), 'login'))
		{
			$group_device_details->input('login')
					->label('Username')
					->rules('length[0,30]')
					->value($device->login)
					->autocomplete('json/device_login');
		}
		
		if ($this->acl_check_edit(get_class($this), 'password'))
		{
			$group_device_details->input('login_password')
					->label('Password')
					->rules('length[0,30]')
					->value($device->password)
					->autocomplete('json/device_password');
		}
		
		$group_device_details->textarea('comment')
				->rules('length[0,254]')
				->value($device->comment)
				->style('width: 520px');
		
		$group_payment = $form->group('Device repayments')->visible($device->price > 0);
		
		$group_payment->input('price')
				->rules('valid_numeric')
				->value($device->price ? $device->price : '');
	
		$group_payment->input('payment_rate')
				->label('Monthly payment rate')
				->rules('valid_numeric')
				->value($device->payment_rate ? $device->payment_rate : '');
		
		$group_payment->date('buy_date')
				->label('Buy date')
				->years(date('Y')-100, date('Y'))
				->value(strtotime($device->buy_date));
		
		$group_address = $form->group('Address');
		
		$group_address->dropdown('town_id')
				->label('Town')
				->rules('required')
				->options($arr_towns)
				->selected($device->address_point->town_id)
				->add_button('towns')
				->style('width: 200px');
		
		$group_address->dropdown('street_id')
				->label('Street')
				->options($arr_streets)
				->selected($device->address_point->street_id)
				->add_button('streets')
				->style('width: 200px');
		
		$group_address->input('street_number')
				->rules('length[1,50]')
				->value($device->address_point->street_number);
		
		$group_address->dropdown('country_id')
				->label('country')
				->rules('required')
				->options($arr_countries)
				->selected($device->address_point->country_id)
				->style('width: 200px');
		
		$group_address->input('gpsx')
				->label(__('GPS').'&nbsp;X:&nbsp;'.help::hint('gps_coordinates'))
				->value($gpsx)
				->rules('gps');
		
		$group_address->input('gpsy')
				->label(__('GPS').'&nbsp;Y:&nbsp;'.help::hint('gps_coordinates'))
				->value($gpsy)
				->rules('gps');
		
		$form->submit('Edit');
		
		// validation
		if($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				$device->transaction_start();
				
				// gps
				$gpsx = NULL;
				$gpsy = NULL;

				if (!empty($form_data['gpsx']) && !empty($form_data['gpsy']))
				{
					$gpsx = doubleval($form_data['gpsx']);
					$gpsy = doubleval($form_data['gpsy']);

					if (gps::is_valid_degrees_coordinate($form_data['gpsx']))
					{
						$gpsx = gps::degrees2real($form_data['gpsx']);
					}

					if (gps::is_valid_degrees_coordinate($form_data['gpsy']))
					{
						$gpsy = gps::degrees2real($form_data['gpsy']);
					}
				}

				$old_user_id = $device->user_id;

				$device->user_id = $form_data['user_id'];
				$device->name = $form_data['device_name'];
				$device->type = $form_data['type'];
				$device->trade_name = $form_data['trade_name'];
				$device->PPPoE_logging_in = $form_data['PPPoE_logging_in'];

				if ($this->acl_check_new(get_class($this), 'login'))
				{
					$device->login = $form_data['login'];
				}

				if ($this->acl_check_new(get_class($this), 'password'))
				{
					$device->password = $form_data['login_password'];
				}

				$device->comment = $form_data['comment'];
				$device->price = $form_data['price'];
				$device->payment_rate = $form_data['payment_rate'];
				$device->buy_date = date('Y-m-d', $form_data['buy_date']);

				$ap = ORM::factory('address_point')->get_address_point(
						$form_data['country_id'], $form_data['town_id'],
						$form_data['street_id'], $form_data['street_number'],
						$gpsx, $gpsy
				);

				$device->save_throwable();

				// add address point if there is no such
				if (!$ap->id)
				{
					// save
					$ap->save_throwable();
				}
				// new addresspoint
				if ($ap->id != $device->address_point_id)
				{
					// delete old?
					$addr_id = $device->address_point->id;
					// add to device
					$device->address_point_id = $ap->id;
					$device->save_throwable();
					// change just for this device?
					if ($ap->count_all_items_by_address_point_id($addr_id) < 1)
					{
						$addr = new Address_point_Model($addr_id);
						$addr->delete();
					}
				}

				// add GPS
				if (!empty($gpsx) && !empty($gpsy))
				{ // save
					$ap->update_gps_coordinates($ap->id, $gpsx, $gpsy);
				}
				else
				{ // delete gps
					$ap->gps = '';
					$ap->save_throwable();
				}

				if ($old_user_id != $device->user_id)
				{
					$old_user = new User_Model($old_user_id);

					$ip_address_model = new Ip_address_Model();
					$ip_addresses = $ip_address_model->get_ip_addresses_of_device($device_id);

					foreach ($ip_addresses as $ip_address)
					{
						// ip address was the only one of this member
						// from this subnet -> deletes subnet from allowed subnets of member
						if (!$ip_address_model->count_all_ip_addresses_by_member_and_subnet(
								$old_user->member_id, $ip_address->subnet_id
							))
						{
							Allowed_subnets_Controller::update_enabled(
								$old_user->member_id, NULL, NULL, array($ip_address->subnet_id)
							);
						}

						Allowed_subnets_Controller::update_enabled(
							$device->user->member_id, array($ip_address->subnet_id)
						);
					}
				}

				unset($form_data);

				$device->transaction_commit();
				status::success('Device has been successfully updated.');
				url::redirect(Path::instance()->previous());
			}
			catch (Exception $e)
			{
				$device->transaction_rollback();
				Log::add_exception($e);
				status::error('Device has not been updated.');
			}
		} // end of validation
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/' . $device->user->member->id,
						'ID ' . $device->user->member->id . ' - ' . $device->user->member->name,
						$this->acl_check_view('Members_Controller', 'members', $device->user->member->id))
				->enable_translation()
				->link('users/show_by_member/' . $device->user->member_id, 'Users',
						$this->acl_check_view('Users_Controller', 'users', $device->user->member_id))
				->disable_translation()
				->link('users/show/' . $device->user->id, 
						$device->user->name . ' ' . $device->user->surname . ' (' . $device->user->login . ')',
						$this->acl_check_view('Users_Controller', 'users', $device->user->member_id))
				->enable_translation()
				->link('devices/show_by_user/' . $device->user->id, 'Devices',
						$this->acl_check_view('Devices_Controller', 'devices', $device->user->member_id))
				->disable_translation()
				->link('devices/show/' . $device->id . '#device_' . $device_id . '_link',
						$device->name,
						$this->acl_check_edit('Devices_Controller', 'devices', $device->user->member_id))
				->enable_translation()
				->text('Edit device');
		
		// view
		$view = new View('main');
		$view->title = __('Edit device');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->form = $form->html();
		$view->content->headline = __('Edit device').': '.$device->name;
		$view->render(TRUE);
	} // end of function edit
		
	/**
	 * Deletes device including all its interfaces and ip adresses, etc. (using FK)
	 * 
	 * @author Ondrej Fibich
	 * @param integer $device_id
	 */
	public function delete($device_id = null)
	{
		
		if (!isset($device_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$device = new Device_Model($device_id);
		
		if ($device->id == 0)
		{
			Controller::error(RECORD);
		}
		
		$mid = $device->user->member_id;
		$ifaces = $device->ifaces;
		
		$subnet_model = new Subnet_Model();
		
		$all_subnets = $subnet_model->get_all_unique_subnets_by_device($device->id);
		
		$subnets = array();
		
		foreach ($all_subnets AS $subnet)
		{
			$subnets[] = $subnet->id;
		}
		
		$subnets = array_unique($subnets);
		
		if (!$this->acl_check_delete('Devices_Controller', 'devices', $mid))
		{
			Controller::error(ACCESS);
		}

		$linkback = Path::instance()->previous();

		if (url::slice(url_lang::uri($linkback), 1, 1) == 'show')
		{
			$linkback = 'devices/show_all';
		}
		
		if ($device->delete())
		{
			Allowed_subnets_Controller::update_enabled($mid, NULL, NULL, $subnets);
			status::success('Device has been successfully deleted.');
		}
		else
		{
			status::error('Error - cant delete device.');
		}
		
		// redirect
		url::redirect($linkback);		
	}
	
	/**
	 * Generate export of device
	 * 
	 * @TODO repair
	 * @author Michal Kliment
	 * @param type $device_id 
	 */
	public function export($device_id = NULL)
	{
		// bad parameter
		if (!$device_id || !is_numeric($device_id))
			Controller::warning(PARAMETER);
		
		$device = new Device_Model($device_id);
		
		// device doesn't exist
		if (!$device->id)
			Controller::error(RECORD);
		
		// definition of models
		$subnet_model			= new Subnet_Model();
		$ip_address_model		= new Ip_address_Model();
		$iface_model			= new Iface_Model();
		$device_model			= new Device_Model();
		
		// definition of array for data
		$device_ifaces					= array
		(
			Iface_Model::TYPE_WIRELESS		=> array(),
			Iface_Model::TYPE_ETHERNET		=> array(),
			Iface_Model::TYPE_PORT			=> array(),
			Iface_Model::TYPE_BRIDGE		=> array(),
			Iface_Model::TYPE_VLAN			=> array(),
			Iface_Model::TYPE_INTERNAL		=> array(),
			Iface_Model::TYPE_VIRTUAL_AP		=> array()
		);
		$device_wireless_iface_devices	= array();
		$device_ip_addresses			= array();
		$device_subnets					= array();
		$device_subnet_ip_addresses		= array();
		$device_gateways				= array();
		$devices_members				= array();
		
		// devices's interfaces
		$ifaces = $iface_model->get_all_ifaces_of_device($device->id);
		foreach ($ifaces as $iface)
		{
			$device_ifaces[$iface->type][] = $iface;
			
			if ($iface->type == Iface_Model::TYPE_WIRELESS)
			{	
				$link_devices = $device_model->get_all_devices_of_link($iface->link_id);
				
				$device_wireless_iface_devices[$iface->id] = array();
				foreach ($link_devices as $link_device)
				{
					if ($link_device->id != $device->id)
						$device_wireless_iface_devices[$iface->id][] = $link_device;
				}
			}
		}
		
		// device's ip addresses
		$ip_addresses = $ip_address_model->get_ip_addresses_of_device($device->id);
		
		foreach ($ip_addresses as $ip_address)
			$device_ip_addresses[] = $ip_address;
		
		// device's subnets
		$subnets = $subnet_model->get_all_subnets_by_device($device->id);
		
		$subnets_options = array();
		$subnets_selected = array();
		
		foreach ($subnets as $subnet)
		{
			$subnets_options[$subnet->id] = $subnet->name.' ('.$subnet->network_address.'/'.$subnet->subnet_range.')';
			
			if ($subnet->gateway)
				$subnets_selected[] = $subnet->id;
			else
			{
				$gateway = $ip_address_model->get_gateway_of_subnet($subnet->id);
				
				if ($gateway)
					$device_gateways[] = $gateway->ip_address;
			}
			
			$device_subnets[$subnet->id] = $subnet;
			
			$mac_addresses = array();
			
			$ip_addresses = $ip_address_model->get_ip_addresses_of_subnet($subnet->id);
			foreach ($ip_addresses as $ip_address)
			{
				if ($ip_address->ip_address != $subnet->ip_address)
				{
					if (!in_array($ip_address->mac, $mac_addresses))
					{
						$mac_addresses[] = $ip_address->mac;

						$device_subnet_ip_addresses[$subnet->id][$ip_address->id] = $ip_address;

						if (!isset($device_members[$ip_address->member_id]))
							$device_members[$ip_address->member_id] = $ip_address->member_name;
					}
				}
			}
		}
		
		$title = __('Export of device');
		
		$form = new Forge();
		
		// format of export
		$form->dropdown('format')
			->options(array
			(
				'mikrotik' => 'Mikrotik'
			));
		
		// subnets on which is dhcp server running
		$form->dropdown('dhcp_subnets[]')
			->label('Subnets with DHCP')
			->options($subnets_options)
			->selected($subnets_selected)
			->size(20)
			->multiple('multiple');
		
		// subnets to QoS
		$form->dropdown('qos_subnets[]')
			->label('Subnets to QoS')
			->options($subnets_options)
			->selected($subnets_selected)
			->size(20)
			->multiple('multiple');
		
		// IP addresses of DNS servers
		$form->input('dns_servers')
			->rules('required|valid_address_ranges');
		
		// result format - text or file
		$form->dropdown('download_as')
			->options(array
			(
			    'text' => __('Text'),
			    'file' => __('File')
			));
		
		$form->submit('Export');
		
		// form is validate
		if ($form->validate())
		{	
			$form_data = $form->as_array();
			
			$dhcp_subnets = array();
			$dhcp_ip_addresses = array();
			
			if (is_array($form_data['dhcp_subnets']))
			{
				foreach ($form_data['dhcp_subnets'] as $subnet_id)
				{
					if (isset($device_subnets[$subnet_id]))
					{
						$dhcp_subnets[$subnet_id] = $device_subnets[$subnet_id];

						$dhcp_ip_addresses[$subnet_id] = isset($device_subnet_ip_addresses[$subnet_id]) ? $device_subnet_ip_addresses[$subnet_id] : array();
					}
				}
			}
			
			$qos_ifaces = array();
			$qos_subnets = array();
			$qos_ip_addresses = array();
			
			if (is_array($form_data['qos_subnets']))
			{
				foreach ($form_data['qos_subnets'] as $subnet_id)
				{
					if (isset($device_subnets[$subnet_id]))
					{
						$qos_subnets[$subnet_id] = $device_subnets[$subnet_id];

						$qos_ip_addresses[$subnet_id] = array();
						if (isset($device_subnet_ip_addresses[$subnet_id]))
						{
							foreach ($device_subnet_ip_addresses[$subnet_id] as $device_ip_address)
								$qos_ip_addresses[$subnet_id][$device_ip_address->member_id][] = $device_ip_address->ip_address;
						}
					}
				}
			}
			
			if ($form_data['download_as'] == 'file')
			{
				switch ($form_data["format"])
				{
					case "mikrotik":
						$ext = '.rsc';
						break;
					
					default:
						$ext = '';
						break;
				}
				
				header ("Content-disposition: attachment; filename=".url::title($device->name)."-".$form_data['format']."-export".$ext);
			}
			else
				echo "<pre>";
			
			$view = new View('device_templates/'.$form_data['format']);
			$view->name = $device->name;
			$view->dhcp_subnets = $dhcp_subnets;
			$view->dhcp_ip_addresses = $dhcp_ip_addresses;
			$view->qos_subnets = $qos_subnets;
			$view->qos_ip_addresses = $qos_ip_addresses;
			$view->device_members = $device_members;
			$view->device_ifaces = $device_ifaces;
			$view->device_ip_addresses = $device_ip_addresses;
			$view->device_gateways = $device_gateways;
			$view->device_wireless_iface_devices = $device_wireless_iface_devices;
			$view->dns_servers = $form_data["dns_servers"];
			$view->render(TRUE);
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/' . $device->user->member->id,
							'ID ' . $device->user->member->id . ' - ' . $device->user->member->name,
							$this->acl_check_view('Members_Controller', 'members', $device->user->member->id))
					->enable_translation()
					->link('users/show_by_member/' . $device->user->member_id, 'Users',
							$this->acl_check_view('Users_Controller', 'users', $device->user->member_id))
					->disable_translation()
					->link('users/show/' . $device->user->id, 
							$device->user->name . ' ' . $device->user->surname . ' (' . $device->user->login . ')',
							$this->acl_check_view('Users_Controller', 'users', $device->user->member_id))
					->enable_translation()
					->link('devices/show_by_user/' . $device->user->id, 'Devices',
							$this->acl_check_view('Devices_Controller', 'devices', $device->user->member_id))
					->disable_translation()
					->link('devices/show/' . $device->id . '#device_' . $device_id . '_link',
							$device->name,
							$this->acl_check_edit('Devices_Controller', 'devices', $device->user->member_id))
					->enable_translation()
					->text('Export of device');
			
			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $title;
			$view->content = new View('form');
			$view->content->form = $form;
			$view->content->headline = $title;
			$view->render(TRUE);
		}
	}

	/**
	 * Shows page for device map
	 * 
	 * @author David Raška
	 * @param type $device_id 
	 */
	public function map($device_id = NULL)
	{		
		$device_model = new Device_Model();
		
		$devices = array
		(
			NULL => '--- ' . __('Select device') . ' ---'
		) + $device_model->select_list('id', 'name', 'user_id');
		
		$form = new Forge();
		
		$form->dropdown('device_id')
				->label('Device')
				->options($devices)
				->selected($device_id);
		
		// headline
		$headline = __('Device map');
		
		// breadcrumbs navigation
		$device = new Device_Model($device_id);
		
		if (!$device->id)
		{
			$breadcrumbs = breadcrumbs::add()
					->link('devices/show_all/', 'Devices',
							$this->acl_check_view('Devices_Controller', 'devices'));
		}
		else
		{
			$breadcrumbs = breadcrumbs::add()
					->link('devices/show_all/', 'Devices',
							$this->acl_check_view('Devices_Controller', 'devices'))
					->disable_translation()
					->link('devices/show/' . $device_id, $device->name,
							$this->acl_check_edit('Devices_Controller', 'devices', $device->user->member_id))
					->enable_translation()
					->text('Map');
		}
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('devices/map');
		$view->content->form = $form->html();
		$view->content->headline = $headline;
		$view->render(TRUE);
	}
	
	/**
	 * Prints array of devices in JSON format
	 * 
	 * @author David Raška
	 */
	public function get_map()
	{
		if (!$this->input->get('from') || $this->input->get('from') == 'null')
			return;
		
		if (!$this->input->get('depth'))
			$depth = 2;
		else
			$depth = intval ($this->input->get('depth'));
		
		$device_id = intval ($this->input->get('from'));
		
		if ($this->input->get('root') != 'true')
		{
			$result = $this->_dependent_device($device_id, $depth+1);
			echo json_encode($result['children']);
		}
		else
		{
			echo json_encode(array($this->_dependent_device($device_id, $depth)));
		}
	}
	
	/**
	 * Return array with device name and its device's children 
	 * or false when $depth <= 0
	 * 
	 * @author Michal Kliment, David Raška
	 * @param type $device_id
	 * @param type $depth
	 * @return type 
	 */
	private function _dependent_device($device_id, $depth)
	{		
		if ($depth <= 0)
			return false;
		
		$device = new Device_Model($device_id);
		
		$result = array
		(
			'data' => $device->name,
			'attr' => array
			(
				'id' => $device->id,
				'rel' => $device->type
			),
			'state' => 'closed',
			'children' => array()
		);
		
		if ($depth == 1)
		{			
			return $result;
		}
		
		$subnets = $device->get_all_dependent_subnets($device->id, FALSE);
		
		$dependent_devices = arr::from_objects($device->get_all_service_devices_of_subnets($subnets), 'name');
		
		$children = array();
		foreach ($dependent_devices as $dependent_device_id => $dependent_device_name)
		{
			$child = $this->_dependent_device($dependent_device_id, $depth-1);
			array_push($children, $child);
		}
		
		$result['children'] = $children;
		$result['state'] = 'open';
		return $result;
	}
	
	/**
	 * Shows iface
	 *
	 * @author Michal Kliment
	 * @param integer $iface_id 
	 */
	public function show_iface($iface_id = NULL)
	{
		Ifaces_Controller::show($iface_id);
	}
	
	/**
	 *
	 * Shows IP address
	 * 
	 * @author Michal Kliment
	 * @param integer $ip_address_id 
	 */
	public function show_ip_address($ip_address_id = NULL)
	{
		Ip_addresses_Controller::show($ip_address_id);
	}
	
	/**
	 * Create grids of interfaces and ip addresses of given device.
	 * 
	 * @param Device_Model $device
	 * @return array			Grids
	 * @TODO bridges and special interfaces
	 */
	private function create_device_grids($device)
	{
		$grids = array();
		$iface_model = new Iface_Model();
		$member_id = $device->user->member_id;
		
		/** IP ADDRESSES ******************************************************/
		
		$ip_address_model = new Ip_address_Model();
		$ips = $ip_address_model->get_ip_addresses_of_device($device->id);
		
		$grids['ip_addresses'] = new Grid('devices', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> count($ips)
		));

		if ($this->acl_check_new(get_class($this), 'ip_address', $member_id))
		{
			$grids['ip_addresses']->add_new_button(
					'ip_addresses/add/'.$device->id, 'Add new ip address', array
					(
						'title' => __('Add new ip address'),
						'class' => 'popup_link'
					)
			);
		}
		
		$grids['ip_addresses']->callback_field('ip_address')
				->label('IP address')
				->callback('callback::ip_address_field', TRUE, FALSE, FALSE);
		
		$grids['ip_addresses']->link_field('subnet_id')
				->link('subnets/show', 'subnet_name')
				->label('Subnet');
		
		$grids['ip_addresses']->link_field('iface_id')
				->link('ifaces/show', 'iface_name')
				->label('Interface');
		
		$actions = $grids['ip_addresses']->grouped_action_field();
		
		if ($this->acl_check_view('Devices_Controller', 'ip_address', $member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('ip_addresses/show')
					->class('popup_link');
		}
		
		if ($this->acl_check_edit('Devices_Controller', 'ip_address', $member_id))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('ip_addresses/edit')
					->class('popup_link');
		}
		
		if ($this->acl_check_delete('Devices_Controller', 'ip_address', $member_id))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('ip_addresses/delete')
					->class('delete_link');
		}
		
		$grids['ip_addresses']->datasource($ips);
		
		/** INTERFACES ********************************************************/
		
		// interfaces of device (all) //////////////////////////////////////////
		$ifaces = $iface_model->get_all_ifaces_of_device($device->id);
				
		// grid
		$grids['ifaces'] = new Grid('devices', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> count($ifaces)
		));
		
		if ($this->acl_check_new(get_class($this), 'iface', $member_id))
		{
			$grids['ifaces']->add_new_button(
					'ifaces/add/' . $device->id, 'Add new interface'
			);
		}
		
		$grids['ifaces']->callback_field('type')
				->callback('callback::iface_type_field')
				->class('center');
		
		$grids['ifaces']->field('name');
		
		$grids['ifaces']->callback_field('mac')
				->callback('callback::not_empty')
				->label('MAC')
				->class('center');
		
		$grids['ifaces']->callback_field('connected_to_device')
				->callback('callback::connected_to_device')
				->class('center');
		
		$actions = $grids['ifaces']->grouped_action_field();
		
		if ($this->acl_check_view(get_class($this), 'iface', $member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('devices/show_iface')
					->class('popup_link');
		}
			
		if ($this->acl_check_edit(get_class($this), 'iface', $member_id))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('ifaces/edit');
		}
		
		if ($this->acl_check_delete('Devices_Controller', 'iface', $member_id))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('ifaces/delete')
					->class('delete_link');
		}
		
		$grids['ifaces']->datasource($ifaces);
		
		// internal interfaces of device ///////////////////////////////////////
		$internal_ifaces = $iface_model->get_all_ifaces_of_device(
				$device->id, Iface_Model::TYPE_INTERNAL
		);
		
		if (!count($internal_ifaces))
		{
			$grids['internal_ifaces'] = '';
		}
		else
		{
			// grid
			$grids['internal_ifaces'] = new Grid('devices', null, array
			(
				'use_paginator'	   			=> false,
				'use_selector'	   			=> false,
				'total_items'				=> count($internal_ifaces)
			));

			if ($this->acl_check_new(get_class($this), 'iface', $member_id))
			{
				$grids['internal_ifaces']->add_new_button(
						'ifaces/add/'.$device->id.'/'.Iface_Model::TYPE_INTERNAL,
						'Add new internal interface'
				);
			}

			$grids['internal_ifaces']->field('name');

			$grids['internal_ifaces']->field('mac')
					->label('MAC');

			$actions = $grids['internal_ifaces']->grouped_action_field();

			if ($this->acl_check_view(get_class($this), 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('show')
						->url('devices/show_iface')
						->class('popup_link');
			}

			if ($this->acl_check_edit(get_class($this), 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ifaces/edit');
			}

			if ($this->acl_check_delete('Devices_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('delete')
						->url('ifaces/delete')
						->class('delete_link');
			}

			$grids['internal_ifaces']->datasource($internal_ifaces);
		}

		// ethernet interfaces of device ///////////////////////////////////////
		$ethernet_ifaces = $iface_model->get_all_ifaces_of_device(
				$device->id, Iface_Model::TYPE_ETHERNET
		);

		if (!count($ethernet_ifaces))
		{
			$grids['ethernet_ifaces'] = '';
		}
		else
		{
			// grid
			$grids['ethernet_ifaces'] = new Grid('devices', null, array
			(
				'use_paginator'	   			=> false,
				'use_selector'	   			=> false,
				'total_items'				=> count($ethernet_ifaces)
			));

			if ($this->acl_check_new(get_class($this), 'iface', $member_id))
			{
				$grids['ethernet_ifaces']->add_new_button(
						'ifaces/add/'.$device->id.'/'.Iface_Model::TYPE_ETHERNET,
						'Add new ethernet interface'
				);
			}

			$grids['ethernet_ifaces']->field('name');

			$grids['ethernet_ifaces']->field('mac')
					->label('MAC');

			$grids['ethernet_ifaces']->callback_field('connected_to_device')
					->callback('callback::connected_to_device');

			$actions = $grids['ethernet_ifaces']->grouped_action_field();

			if ($this->acl_check_view(get_class($this), 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('show')
						->url('devices/show_iface')
						->class('popup_link');
			}

			if ($this->acl_check_edit(get_class($this), 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ifaces/edit');
			}

			if ($this->acl_check_delete('Devices_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('delete')
						->url('ifaces/delete')
						->class('delete_link');
			}

			$grids['ethernet_ifaces']->datasource($ethernet_ifaces);
		}
		
		// wireless interfaces of device ///////////////////////////////////////
		$wireless_ifaces = $iface_model->get_all_wireless_ifaces_of_device($device->id);
		
		if (!count($wireless_ifaces))
		{
			$grids['wireless_ifaces'] = '';
		}
		else
		{
			// grid
			$grids['wireless_ifaces'] = new Grid(url_lang::base().'devices', null, array
			(
				'use_paginator'	   			=> false,
				'use_selector'	   			=> false,
				'total_items'				=> count($wireless_ifaces)
			));

			if ($this->acl_check_new(get_class($this),'iface',$member_id))
			{
				$grids['wireless_ifaces']->add_new_button(
						'ifaces/add/'.$device->id.'/'.Iface_Model::TYPE_WIRELESS,
						'Add new wireless interface'
				);
			}

			$grids['wireless_ifaces']->callback_field('wireless_mode')
					->callback('callback::wireless_mode')
					->label('Mode');

			$grids['wireless_ifaces']->field('name')
					->label('Name');

			$grids['wireless_ifaces']->field('wireless_ssid')
					->label('SSID');

			$grids['wireless_ifaces']->field('mac')
					->label('MAC');

			$grids['wireless_ifaces']->callback_field('connected_to_device')
					->callback('callback::connected_to_device');

			$actions = $grids['wireless_ifaces']->grouped_action_field();

			if ($this->acl_check_view(get_class($this), 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('show')
						->url('devices/show_iface')
						->class('popup_link');
			}

			if ($this->acl_check_edit(get_class($this), 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ifaces/edit');
			}

			if ($this->acl_check_delete('Devices_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('delete')
						->url('ifaces/delete')
						->class('delete_link');
			}

			$grids['wireless_ifaces']->datasource($wireless_ifaces);
		}

		// vlans ///////////////////////////////////////////////////////////////
		$vlan_ifaces = $iface_model->get_all_vlan_ifaces_of_device($device->id);

		if (!count($vlan_ifaces))
		{
			$grids['vlan_ifaces'] = '';
		}
		else
		{
			$grids['vlan_ifaces'] = new Grid('devices', null, array
			(
				'use_paginator'	   			=> false,
				'use_selector'	   			=> false,
				'total_items'				=> count($vlan_ifaces)
			));

			if ($this->acl_check_new(get_class($this), 'vlan_iface', $member_id))
			{
				$grids['vlan_ifaces']->add_new_button(
						'ifaces/add/'.$device->id.'/'.Iface_Model::TYPE_VLAN,
						'Add new VLAN interface'
				);
			}

			$grids['vlan_ifaces']->field('name');

			$grids['vlan_ifaces']->link_field('id')
					->link('ifaces/show', 'name')
					->label('VLAN name');

			$grids['vlan_ifaces']->field('tag_802_1q')
					->label('tag_802_1q')
					->class('center');

			$grids['vlan_ifaces']->link_field('iface_id')
					->link('ifaces/show', 'iface_name')
					->label('Interface');

			$actions = $grids['vlan_ifaces']->grouped_action_field();

			if ($this->acl_check_view(get_class($this), 'vlan_iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('show')
						->url('ifaces/show')
						->class('popup_link');
			}

			if ($this->acl_check_edit(get_class($this), 'vlan_iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ifaces/edit');
			}

			if ($this->acl_check_delete(get_class($this), 'vlan_iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('delete')
						->url('ifaces/delete')
						->class('delete_link');
			}

			$grids['vlan_ifaces']->datasource($vlan_ifaces);
		}
		
		// ports of device /////////////////////////////////////////////////////
		$ports = $iface_model->get_all_ifaces_of_device(
				$device->id, Iface_Model::TYPE_PORT
		);
		
		if (!count($ports))
		{
			$grids['ports'] = '';
		}
		else
		{
			$grids['ports'] = new Grid('devices', null, array
			(
				'use_paginator'	   			=> false,
				'use_selector'	   			=> false,
				'total_items'				=> count($ports)
			)); 

			if ($this->acl_check_new(get_class($this), 'port', $member_id))
			{
				$grids['ports']->add_new_button(
						'ifaces/add/'.$device->id.'/'.Iface_Model::TYPE_PORT,
						'Add new port', array('title' => __('Add new port'))
				);
			}

			$grids['ports']->field('number')
					->label('Number')
					->class('center');

			$grids['ports']->field('name')
					->label('Name')
					->class('center');

			$grids['ports']->callback_field('mode')
					->callback('callback::port_mode_field');

			$grids['ports']->callback_field('bitrate')
					->callback('callback::bitrate_field', FALSE)
					->class('center');

			$grids['ports']->callback_field('connected_to_device')
					->callback('callback::connected_to_device');

			$actions = $grids['ports']->grouped_action_field();

			if ($this->acl_check_view(get_class($this), 'port', $member_id))
			{
				$actions->add_action('id')
						->icon_action('show')
						->url('devices/show_iface')
						->class('popup_link');
			}

			if ($this->acl_check_edit(get_class($this), 'port', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ifaces/edit');
			}

			if ($this->acl_check_delete('Devices_Controller', 'port', $member_id))
			{
				$actions->add_action('id')
						->icon_action('delete')
						->url('ifaces/delete')
						->class('delete_link');
			}

			$grids['ports']->datasource($ports);
		}
		
		// here @TODO bridges and special interfaces
		$grids['special_ifaces'] = '';
		$grids['bridge_ifaces'] = '';
		
		return $grids;
	}
	
	/**
	 * Gets filter form fo devices
	 * 
	 * @param integer $default_filter_user Pre-select default user
	 * @author Ondřej Fibich
	 * @see Devices_Controller#add
	 * @see Ifaces_Controller#add
	 * @return Filter_form
	 */
	public static function device_filter_form($default_filter_user = NULL)
	{
		$filter_form = new Filter_form();
        
		$filter_form->add('subnet')
			->type('select')
			->values(ORM::factory('subnet')->select_list_by_net())
			->css_class('filter_field_subnet');
		
		$filter_form->add('type')
			->type('select')
			->values(ORM::factory('enum_type')->get_values(Enum_type_model::DEVICE_TYPE_ID));
        
		$user_col = $filter_form->add('user')
			->type('select')
			->values(ORM::factory('user')->select_list(
					'id', 'CONCAT(surname, \' \', name, \' - \', login)',
					'surname'
			));
		
		if (!empty($default_filter_user))
		{
			$user_col->default(Filter_form::OPER_IS, $default_filter_user);
		}
		
		$filter_form->add('device_name')
			->callback('json/device_name');
		
		$filter_form->add('town')
			->type('select')
			->values(array_unique(ORM::factory('town')->select_list('id', 'town')));
		
		$filter_form->add('street')
			->type('select')
			->values(array_unique(ORM::factory('street')->select_list('id', 'street')));
		
		$filter_form->add('street_number')
			->type('number');
		
		return $filter_form;
	}

}
