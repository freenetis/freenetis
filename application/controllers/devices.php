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
	 * Constructor, only test if networks is enabled
	 */
	public function __construct()
	{
		parent::__construct();
		
		// access control
		if (!Settings::get('networks_enabled'))
			Controller::error (ACCESS);
	}
	
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
		if (!$this->acl_check_view('Devices_Controller', 'devices'))
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
		
		$filter_form->add('mac')
				->label('MAC address')
				->class('mac')
				->callback('json/iface_mac');
		
		$filter_form->add('comment');
		
		$filter_form->add('cloud')
			->type('select')
			->values(ORM::factory('cloud')->select_list());
		
		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
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
		
		if ($this->acl_check_new('Devices_Controller', 'devices'))
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
		
		if (module::e('notification') &&
			$this->acl_check_new('Notifications_Controller', 'devices'))
		{
			$grid->add_new_button('notifications/devices'.server::query_string(), 'Notifications', array
			(
				'title' => __('Set notification to devices admins'),
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
		
		if ($this->acl_check_view('Device_active_links_Controller', 'display_device_active_links'))
		{
			$grid->callback_field('device_grid')
				->label('Device active links')
				->callback('callback::device_active_links');
		}
		
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
	 * Shows all DHCP servers with their access times
	 * 
	 * @param int $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param int $page
	 */
	public function show_all_dhcp_servers($limit_results = 50,
			$order_by = 'access_time', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// access control
		if (!$this->acl_check_view('Devices_Controller', 'devices'))
			Controller::error(ACCESS);
		
		// check if subnets with DHCP server has set uped gateway
		$subnets = ORM::factory('subnet')->get_all_dhcp_subnets_without_gateway();
		
		if (count($subnets))
		{
			$subnet_links = array();
			foreach ($subnets as $s)
			{
				$subnet_links[] = html::anchor('subnets/show/' . $s->id, $s->name);
			}
			
			$m = 'These subnets have not configured gateway (%s), set them please.';
			status::mwarning($m, TRUE, implode(', ', $subnet_links));
		}
		
		$filter_form = new Filter_form('d');
		
		$filter_form->add('access_time')
			->type('date')
			->label('Last access time');
		
		$filter_form->add('name')
			->callback('json/device_name');
		
		$filter_form->add('type')
			->type('select')
			->values(ORM::factory('Enum_type')->get_values(Enum_type_model::DEVICE_TYPE_ID));
		
		$filter_form->add('trade_name')
			->callback('json/device_trade_name');
		
		$filter_form->add('subnet_id')
			->type('select')
			->values(ORM::factory('subnet')->select_list())
			->label('Subnet');
		
		$filter_form->add('ip_address')
			->callback('json/ip_address');
		
		$filter_form->add('town')
			->type('combo')
			->callback('json/town_name');
		
		$filter_form->add('street')
			->type('combo')
			->callback('json/street_name');
		
		$filter_form->add('street_number')
			->type('number');
		
		$filter_form->add('comment');
		
		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		$device_model = new Device_Model();		
		$total_devices = $device_model->count_all_dhcp_servers($filter_form->as_sql());
		
		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_devices)
			$sql_offset = 0;	
		
		// query
		$devices = $device_model->get_all_dhcp_servers(array
		(
			'offset'					=> $sql_offset,
			'limit'						=> (int) $limit_results,
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'filter_sql'				=> $filter_form->as_sql()
		));
		
		// headline
		$headline = __('DHCP servers');
		
		// grid of devices
		$grid = new Grid('ddhcp_servers', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang'). '/devices/show_all_dhcp_servers/'
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

		$grid->order_field('device_id')
				->label('ID')
				->class('center');
		
		$grid->order_field('device_name')
				->link('devices/show', 'device_name');
		
		$grid->order_field('type_name')
				->label('Type');
		
		$grid->order_callback_field('access_time')
				->callback('callback::dhcp_servers_last_access_diff_field')
				->label('Last access time');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Devices_Controller', 'devices'))
		{
			$actions->add_action('device_id')
					->icon_action('show')
					->url('devices/show');
		}
			
		$grid->datasource($devices);
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $headline;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	} // end of show_all_dhcp_servers
	
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
		if (!$this->acl_check_view('Devices_Controller', 'devices', $user->member_id))
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
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');

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
				->link('ifaces/show', 'mac');
		
		$base_grid->callback_field('connected_to_device')
				->callback('callback::device_connected_to_device')
				->class('center');
		
		$base_grid->callback_field('ip_address')
			->callback('callback::ip_address_field');
		
		if ($this->acl_check_view('Subnets_Controller', 'subnet'))
		{
			$base_grid->link_field('subnet_id')
					->link('subnets/show', 'subnet_name')
					->label('Subnet');
		}
		else
		{
			$base_grid->field('subnet_name')
					->label('Subnet');
		}
		
		if ($this->acl_check_view('Device_active_links_Controller', 'display_device_active_links'))
		{
			$base_grid->callback_field('show_by_user')
				->label('Device active links')
				->callback('callback::device_active_links');
		}
		
		$actions = $base_grid->grouped_action_field();
		
		if ($this->acl_check_view('Devices_Controller', 'devices', $user->member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('devices/show');
		}
		
		if ($this->acl_check_edit('Devices_Controller', 'devices', $user->member_id))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('devices/edit');
		}

		if ($this->acl_check_delete('Devices_Controller', 'devices', $user->member_id))
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
		
		if (!$this->acl_check_view('Devices_Controller', 'devices', $member_id))
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
					'device_engineers/add/' . $device_id,
					'Add new device engineer',
					array
					(
						'class' => 'popup_link'
					)
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
		
		$active_links_model = new Device_active_link_Model();
		
		$active_links = $active_links_model->get_device_active_links($device_id);
		
		// device admins
		$device_admin_model = new Device_admin_Model();
		$da = $device_admin_model->get_device_admins($device_id);
		
		$grid_device_admins = new Grid('devices', null, array
		(
			'use_paginator'				=> false,
			'use_selector'				=> false,
			'total_items'				=> count($da)
		));
		
		if ($this->acl_check_edit('Devices_Controller', 'admin', $member_id))
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

		if ($device->address_point->gps != NULL)
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
		$view->action_logs = action_logs::object_last_modif($device, $device_id);
		$view->mapycz_enabled = TRUE;
		$view->content = new View('devices/show');
		$view->content->device = $device;
		$view->content->device_type = $device_type;
		$view->content->count_engineers = count($de);
		$view->content->count_admins = count($da);
		$view->content->ifaces = $device->ifaces;
		$view->content->table_device_engineers = $grid_device_engineers;
		$view->content->table_device_admins	= $grid_device_admins;
		$view->content->table_ip_addresses = isset($grids['ip_addresses']) ? $grids['ip_addresses'] : '';
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
		$view->content->active_links = $active_links;
		$view->render(TRUE);
	} // end of show
	
	/**
	 * Adds whole device. It means it creates new device, new interface assigned to this device
	 * and new ip address assigned to this interface.
	 * 
	 * @param integer $user_id
	 * @param integer $connection_request_id If device added from connection request
	 */
	public function add($user_id = null, $connection_request_id = NULL)
	{
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
			
			$member_id = $um->member_id;
			
			if (!$this->acl_check_new('Devices_Controller', 'devices', $member_id))
			{
				Controller::error(ACCESS);
			}
			
			$device_name = $um->surname;
			$selected = $um->id;
			$selected_country_id = $um->member->address_point->country_id;
			$selected_street_id = $um->member->address_point->street_id;
			$selected_street_number = $um->member->address_point->street_number;
			$selected_town_id = $um->member->address_point->town_id;
			
			$selected_street = ($um->member->address_point->street != NULL ? 
								$um->member->address_point->street->street." " :
								"");
			$selected_street .= $selected_street_number;
			
			if ($um->member->address_point->town != NULL)
			{
				$selected_town = $um->member->address_point->town->town;
				$selected_district = ($um->member->address_point->town->quarter != NULL ?
										$um->member->address_point->town->quarter :
										""
									);
				$selected_zip = $um->member->address_point->town->zip_code;
			}
			else
			{
				$selected_town = "";
				$selected_district = "";
				$selected_zip = "";
			}
			
			$gps = $um->member->address_point->get_gps_coordinates();
			
			if ($gps)
			{
				$gpsx = gps::real2degrees($gps->gpsx, FALSE);
				$gpsy = gps::real2degrees($gps->gpsy, FALSE);
			}
			
			$arr_users[$um->id] = $um->get_name_with_login();
			
			// connection request
			if (!empty($connection_request_id))
			{
				$cr_model = new Connection_request_Model($connection_request_id);
				
				if (!$cr_model->id)
					Controller::error(RECORD);
				
				// device name
				$device_name = $um->surname . '_' . $cr_model->device_type->get_value();
				// all device templates
				$arr_device_templates = array
				(
					NULL => '----- '.__('Select template').' -----'
				) + ORM::factory('device_template')
						->where('enum_type_id', $cr_model->device_type_id)
						->select_list();
			}
			else
			{
				// all device templates
				$arr_device_templates = array
				(
					NULL => '----- '.__('Select template').' -----'
				) + ORM::factory('device_template')->select_list();
			}
		}
		else
		{
			if (!$this->acl_check_new('Devices_Controller', 'devices'))
			{
				Controller::error(ACCESS);
			}
			
			$member_id = NULL;
			
			$um = new User_Model();
			$selected = 0;
			$selected_country_id = Settings::get('default_country');
			$selected_street_id = 0;
			$selected_street_number = '';
			$selected_town_id = 0;
			$device_name = '';
			
			$arr_users = array
			(
				NULL => '----- '.__('select user').' -----'
			) + $um->select_list_grouped();
		
			// all device templates
			$arr_device_templates = array
			(
				NULL => '----- '.__('Select template').' -----'
			) + ORM::factory('device_template')->select_list();
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
		
		// country
		$arr_countries = ORM::factory('country')->where('enabled', 1)->select_list('id', 'country_name');
			   		
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
		if ($this->acl_check_edit('Devices_Controller', 'engineer', $member_id))
		{
			$arr_engineers = $um->select_list_grouped();
		}
		else
		{
			$engineer = new User_Model($this->session->get('user_id'));
			$arr_engineers[$engineer->id] = $engineer->get_full_name_with_login();
		}
		
		// Device active links
		$device_active_links_model = new Device_active_link_Model();
		
		$all_active_links = $device_active_links_model->get_all_active_links();
		
		$active_links = array();
		
		foreach($all_active_links AS $active_link)
		{
			if (!$active_link->name)
				$active_links[$active_link->id] = $active_link->title;
			else
				$active_links[$active_link->id] = $active_link->name.' ('.$active_link->title.')';
		}
		
		// forge form
		$form = new Forge();
		
		$form->set_attr('id', 'device_add_form');
		
		$group_device = $form->group('Device');
		
		$group_device->input('device_name')
				->label('Device name')
				->value($device_name)
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
				->selected(isset($cr_model) ? $cr_model->device_type_id : NULL)
				->style('width: 200px');
		
		$group_device->dropdown('device_template_id')
				->options($arr_device_templates)
				->label('Device template')
				->rules('required')
				->selected(isset($cr_model) ? $cr_model->device_template_id : NULL)
				->style('width: 200px')
				->add_button('device_templates');
		
		$group_device_details = $form->group('Device detail')->visible(FALSE);
		
		$group_device_details->dropdown('PPPoE_logging_in')
				->label('PPPoE')
				->options(arr::rbool());
		
		if ($this->acl_check_new('Devices_Controller', 'login'))
		{
			$group_device_details->input('login')
					->label('Username')
					->rules('length[0,30]')
					->autocomplete('json/device_login');
		}
		
		if ($this->acl_check_new('Devices_Controller', 'password'))
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
		
		$group_device_details->html_textarea('device_comment')
				->mode('simple')
				->label('Comment');
		
		$group_device_details->dropdown('active_links[]')
				->label('Device active links')
				->options($active_links)
				->multiple('multiple')
				->size(10);
		
		if (Settings::get('finance_enabled'))
		{
			$group_payment = $form->group('Device repayments')->visible(FALSE);

			$group_payment->input('price')
					->rules('valid_numeric');

			$group_payment->input('payment_rate')
					->label('Monthly payment rate')
					->rules('valid_numeric')
					->callback(array($this, 'valid_repayment'));

			$group_payment->date('buy_date')
					->label('Buy date')
					->years(date('Y')-100, date('Y'));
		}
		
		$group_address = $form->group('Address');
		
		if (!empty($user_id))
		{
			$group_address->visible(!$um->id);
		}
		
		$address_point_server_active = Address_points_Controller::is_address_point_server_active();
		
		// If address database application is set show new form
		if ($address_point_server_active)
		{	
			$group_address->dropdown('country_id')
					->label('Country')
					->rules('required')
					->options($arr_countries)
					->style('width:200px')
					->selected(($selected_country_id != NULL ? $selected_country_id : Settings::get('default_country')));
			
			$group_address->input('town')
				->label(__('Town').' - '.__('District'))
				->rules('required')
				->class('join1')
				->value((isset($selected_town) ? $selected_town : ''));
			
			$group_address->input('district')
				->class('join2')
				->value((isset($selected_district) ? $selected_district : ''));

			$group_address->input('street')
				->label('Street')
				->rules('required')
				->value((isset($selected_street) ? $selected_street : ''));
						
			$group_address->input('zip')
				->label('Zip code')
				->rules('required')
				->value((isset($selected_zip) ? $selected_zip : ''));
		}
		else
		{
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
		}
		
		$group_address->input('gpsx')
				->label(__('GPS').'&nbsp;X:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps')
				->value($gpsx);
		
		$group_address->input('gpsy')
				->label(__('GPS').'&nbsp;Y:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps')
				->value($gpsy);

		$form->group(html::image(array
		(
			'src' => '/media/images/icons/ifaces/ethernet.png')
		) . ' ' . __('Ethernet interfaces'))->visible(TRUE);
		
		$form->group(html::image(array
		(
			'src' => '/media/images/icons/ifaces/wireless.png')
		) . ' ' . __('Wireless interfaces'))->visible(TRUE);
		
		$form->group(html::image(array
		(
			'src' => '/media/images/icons/ifaces/port.png')
		) . ' ' . __('Ports'))->visible(TRUE);
		
		$form->group(html::image(array
		(
			'src' => '/media/images/icons/ifaces/internal.png')
		) . ' ' . __('Internal interfaces'))->visible(TRUE);

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
			$expired_subnets = array(); // #465
			
			// gets number of maximum of acceptable repeating of operation
			// after reaching of deadlock and time of waiting between
			// other attempt to make transaction (#254)
			$transaction_attempt_counter = 0;
			$max_attempts = max(1, abs(Settings::get('db_trans_deadlock_repeats_count')));
			$timeout = abs(Settings::get('db_trans_deadlock_repeats_timeout'));
			
			$match = array();
			
			// validate address
			if ($address_point_server_active &&
				(
					!Address_points_Controller::is_address_point_valid(
						$form_data['country_id'],
						$form_data['town'],
						$form_data['district'],
						$form_data['street'],
						$form_data['zip']
					) ||
					!preg_match('((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', $form_data['street'], $match)
				))
			{
				$group_address->street->add_error('required', __('Invalid address point.'));
			}
			else
			{
				// street
				if ($address_point_server_active)
				{
					$street = trim(preg_replace(' ((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', '', $form_data['street']));

					$number = $match[0];
				}
				
				// try to delete
				while (TRUE)
				{
					try
					{
						$dm->transaction_start();

						// device //////////////////////////////////////////////////////
						$device = new Device_Model();
						$device->user_id = $form_data['user_id'];

						if (!isset($user_id))
						{
							$um = new User_Model($device->user_id);
						}

						if (empty($form_data['device_name']))
						{
							$device->name = $um->login.'_'.$types[$form_data['device_type']];
						}
						else 
						{
							$device->name = $form_data['device_name'];
						}

						$device_template = new Device_template_Model($form_data['device_template_id']);

						if ($device_template && $device_template->id)
						{
							$device->trade_name = $device_template->name;
						}

						$device->type = $form_data['device_type'];
						$device->PPPoE_logging_in = $form_data['PPPoE_logging_in'];

						if ($this->acl_check_new('Devices_Controller', 'login'))
						{
							$device->login = $form_data['login'];
						}

						if ($this->acl_check_new('Devices_Controller', 'password'))
						{
							$device->password = $form_data['login_password'];
						}

						if (Settings::get('finance_enabled'))
						{
							$device->price = $form_data['price'];	
							$device->payment_rate = $form_data['payment_rate'];
							$device->buy_date = date('Y-m-d', $form_data['buy_date']);	
						}

						$device->comment = $group_device_details->device_comment->value; // not escaped

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

						if ($address_point_server_active)
						{
							$t = new Town_Model();
							$s = new Street_Model();
							$t_id = $t->get_town($form_data['zip'], $form_data['town'], $form_data['district'])->id;
							$s_id = $s->get_street($street, $t_id)->id;

							$ap = $address_point_model->get_address_point($form_data['country_id'], $t_id, $s_id, $number,
									$gpsx, $gpsy);
						}
						else
						{
							$ap = $address_point_model->get_address_point(
									$form_data['country_id'], $form_data['town_id'],
									$form_data['street_id'], $form_data['street_number'],
									$gpsx, $gpsy
							);
						}

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
							$ap->gps = NULL;
							$ap->save_throwable();
						}

						$device->address_point_id = $ap->id;
						$device->save_throwable();

						// device engineer ////////////////////////////////////////////

						$device_engineer = new Device_engineer_Model();
						$device_engineer->device_id = $device->id;
						$device_engineer->user_id = $form_data['first_engineer_id'];
						$device_engineer->save_throwable();

						// ifaces //////////////////////////////////////////////////////

						$update_allowed_params = array();// reset
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
							$im->device_id = $device->id;
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
													$name .= ' - ' . $device->name;
												}
												else
												{
													$name .= $device->name . ' - ';
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
								$subnet_id = intval($_POST['subnet'][$i]);

								$gateway = ($_POST['gateway'][$i] == 1);

								// ip address is gatewayof subnet
								if ($gateway)
								{
									$subnet = new Subnet_Model($subnet_id);

									$gateway = $subnet->get_gateway();

									// subnet has already have gateway
									if ($gateway && $gateway->id)
										throw new Exception(__('Error').': '.__('Subnet has already have gateway'));
								}

								// save IP address
								$ipm = new Ip_address_Model();
								$ipm->iface_id = $im->id;
								$ipm->subnet_id = $subnet_id;
								$ipm->member_id = NULL;
								$ipm->ip_address = htmlspecialchars($_POST['ip'][$i]);
								$ipm->dhcp = ($_POST['dhcp'][$i] == 1);
								$ipm->gateway = $gateway;
								$ipm->service = ($_POST['service'][$i] == 1);
								$ipm->save_throwable();

								// expired subnet
								$expired_subnets[] = $ipm->subnet_id;
								// allowed subnet to added IP
								$update_allowed_params[] = array
								(
									'member_id' => $device->user->member_id,
									'to_enable' => array($ipm->subnet_id)
								);
							}
						}

						// connection request //////////////////////////////////////////

						if (isset($cr_model))
						{
							// change connection request
							$cr_model->state = Connection_request_Model::STATE_APPROVED;
							$cr_model->decided_user_id = $this->user_id;
							$cr_model->device_id = $device->id;
							$cr_model->decided_at = date('Y-m-d H:i:s');
							$cr_model->save_throwable();
						}

						// change connected from if member is applicant and if 
						// he is not connected yet
						if ($device->user->member->type == Member_Model::TYPE_APPLICANT && (
								empty($device->user->member->applicant_connected_from) ||
								$device->user->member->applicant_connected_from == '0000-00-00'
							))
						{
							// connected from now
							$device->user->member->applicant_connected_from = date('Y-m-d');
							$device->user->member->save_throwable();
						}

						// expired subnets (#465)
						ORM::factory('subnet')->set_expired_subnets($expired_subnets);

						// connection request - notice /////////////////////////////////

						// only if request made by owner of device and ovner not
						// decided the request by him self
						if (module::e('notification') && isset($cr_model) &&
							$cr_model->member_id == $cr_model->added_user->member_id &&
							$cr_model->member_id != $this->member_id)
						{
							// create comment for user
							$link = html::anchor('devices/show/' . $cr_model->device_id,
												 $cr_model->device->name);
							$comment = '<table>'
									 . '<tr><th>' . __('Approved by') . ':</th>'
									 . '<td>' . $cr_model->decided_user->get_full_name() . '</td></tr>'
									 . '<tr><th>' . __('Date') . ':</th>'
									 . '<td>' . $cr_model->decided_at . '</td></tr>'
									 . '<tr><th>' . __('Device') . ':</th>'
									 . '<td>' . $link . '</td></tr>'
									 . '</table>';

							// trigger notice for member
							Message_Model::activate_special_notice(
									Message_Model::CONNECTION_REQUEST_APPROVE,
									$cr_model->member_id, $this->session->get('user_id'),
									Notifications_Controller::ACTIVATE,
									Notifications_Controller::KEEP, $comment
							);
						}

						// saves active links
						if ($form_data['active_links'])
						{
							$active_links = $form_data['active_links'];

							foreach ($active_links AS $al)
							{
								$device_active_links_model->map_devices_to_active_link(array($device->id), $al);
							}
						}

						// done ////////////////////////////////////////////////////
						$dm->transaction_commit();

						// Update allowed subnets after transaction is successfully commited
						$error_added = TRUE; // throw error?

						foreach ($update_allowed_params as $params)
						{
							try
							{
								Allowed_subnets_Controller::update_enabled(
										$params['member_id'], $params['to_enable'],
										array(), array(), $error_added
								);
							}
							catch (Exception $e)
							{
								$error_added = FALSE;
								status::warning('Error - cannot update allowed subnets of member.');
							}
						}

						if (isset($cr_model))
						{
							status::success('Connection request has been succesfully approved.');
						}
						else
						{
							status::success('Device has been successfully saved.');
						}

						url::redirect('devices/show/'.$device->id);
					}
					catch (Exception $e) // failed => rollback and wait 100ms before next attempt
					{
						$dm->transaction_rollback();

						if (++$transaction_attempt_counter >= $max_attempts) // this was last attempt?
						{
							Log::add_exception($e);
							status::error('Device has not been successfully saved.', $e);
							break;
						}

						usleep($timeout);
					}
				}
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
		if (!$this->acl_check_new('Devices_Controller', 'devices'))
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
			
			$selected_street = ($um->member->address_point->street != NULL ? 
								$um->member->address_point->street->street." " :
								"");
			$selected_street .= $selected_street_number;
			
			if ($um->member->address_point->town != NULL)
			{
				$selected_town = $um->member->address_point->town->town;
				$selected_district = ($um->member->address_point->town->quarter != NULL ?
										$um->member->address_point->town->quarter :
										""
									);
				$selected_zip = $um->member->address_point->town->zip_code;
			}
			else
			{
				$selected_town = "";
				$selected_district = "";
				$selected_zip = "";
			}
			
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
		}
		else
		{
			$um = new User_Model();
			$selected = 0;
			$selected_country_id = Settings::get('default_country');
			$selected_street_id = 0;
			$selected_street_number = '';
			$selected_town_id = 0;
		}
		
		$arr_users = array
		(
			NULL => '----- '.__('select user').' -----'
		) + $um->select_list_grouped();
		
		// enum types for device
		$enum_type_model = new Enum_type_Model();
		$types = $enum_type_model->get_values(Enum_type_Model::DEVICE_TYPE_ID);
		$types[NULL] = '----- '.__('select type').' -----';
		asort($types);
		
		// country
		$arr_countries = ORM::factory('country')->where('enabled', 1)->select_list('id', 'country_name');
			   		
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
		if ($this->acl_check_edit('Devices_Controller', 'engineer'))
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
		
		if ($this->acl_check_new('Devices_Controller', 'login'))
		{
			$group_device_details->input('login')
					->label('Username')
					->rules('length[0,30]')
					->autocomplete('json/device_login');
		}
		
		if ($this->acl_check_new('Devices_Controller', 'password'))
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
		
		$group_device_details->html_textarea('device_comment')
				->mode('simple')
				->label('Comment');
		
		$group_payment = $form->group('Device repayments')->visible(FALSE);
		
		$group_payment->input('price')
				->rules('valid_numeric');
		
		$group_payment->input('payment_rate')
				->label('Monthly payment rate')
				->rules('valid_numeric')
				->callback(array($this, 'valid_repayment'));
		
		$group_payment->date('buy_date')
				->label('Buy date')
				->years(date('Y')-100, date('Y'));
		
		$group_address = $form->group('Address');
		
		if (!empty($user_id))
		{
			$group_address->visible(!$um->id);
		}
		
		$address_point_server_active = Address_points_Controller::is_address_point_server_active();
		
		// If address database application is set show new form
		if ($address_point_server_active)
		{	
			$group_address->dropdown('country_id')
					->label('Country')
					->rules('required')
					->options($arr_countries)
					->style('width:200px')
					->selected(($selected_country_id != NULL ? $selected_country_id : Settings::get('default_country')));
			
			$group_address->input('town')
				->label(__('Town').' - '.__('District'))
				->rules('required')
				->class('join1')
				->value((isset($selected_town) ? $selected_town : ''));
			
			$group_address->input('district')
				->class('join2')
				->value((isset($selected_district) ? $selected_district : ''));

			$group_address->input('street')
				->label('Street')
				->rules('required')
				->value((isset($selected_street) ? $selected_street : ''));
						
			$group_address->input('zip')
				->label('Zip code')
				->rules('required')
				->value((isset($selected_zip) ? $selected_zip : ''));
		}
		else
		{
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
		}
		
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
			
			$match = array();
			
			// validate address
			if ($address_point_server_active &&
				(
					!Address_points_Controller::is_address_point_valid(
						$form_data['country_id'],
						$form_data['town'],
						$form_data['district'],
						$form_data['street'],
						$form_data['zip']
					) ||
					!preg_match('((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', $form_data['street'], $match)
				))
			{
				$group_address->street->add_error('required', __('Invalid address point.'));
			}
			else
			{
				// street
				if ($address_point_server_active)
				{
					$street = trim(preg_replace(' ((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', '', $form_data['street']));

					$number = $match[0];
				}
				
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

					if ($this->acl_check_new('Devices_Controller', 'login'))
					{
						$dm->login = $form_data['login'];
					}

					if ($this->acl_check_new('Devices_Controller', 'password'))
					{
						$dm->password = $form_data['login_password'];
					}

					$dm->comment = $group_device_details->device_comment->value; // not escaped
					$dm->price = $form_data['price'];
					$dm->payment_rate = $form_data['payment_rate'];
					$dm->buy_date = date('Y-m-d', $form_data['buy_date']);

					$address_point_model = new Address_point_Model();

					if ($address_point_server_active)
					{
						$t = new Town_Model();
						$s = new Street_Model();
						$t_id = $t->get_town($form_data['zip'], $form_data['town'], $form_data['district'])->id;
						$s_id = $s->get_street($street, $t_id)->id;

						$ap = $address_point_model->get_address_point($form_data['country_id'], $t_id, $s_id, $number,
								$gpsx, $gpsy);
					}
					else
					{
						$ap = $address_point_model->get_address_point(
								$form_data['country_id'], $form_data['town_id'],
								$form_data['street_id'], $form_data['street_number'],
								$gpsx, $gpsy
						);
					}

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
						$ap->gps = NULL;
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
	
	/**
	 * Help redirect for approving of connection request
	 * 
	 * @param integer $connection_request_id
	 */
	public function add_from_connection_request($connection_request_id = NULL)
	{
		$connection_request = new Connection_request_Model($connection_request_id);
		
		if (!$connection_request || !$connection_request->id)
		{
			Controller::error(RECORD);
		}
		
		$uid = ORM::factory('member')->get_main_user($connection_request->member_id);
		
		// redirect
		url::redirect('devices/add/' . $uid . '/' . $connection_request->id);
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
		
		if (!$this->acl_check_edit('Devices_Controller', 'devices', $member_id))
		{
			Controller::error(ACCESS);
		}

		// gps
		$gpsx = '';
		$gpsy = '';
		
		if ($device->address_point->gps != NULL)
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
		$arr_countries = ORM::factory('country')->where('enabled', 1)->select_list('id', 'country_name');
		$arr_countries = $arr_countries + ORM::factory('country')->where('id', $device->address_point->country_id)->select_list('id', 'country_name');
		
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
		
		// Device active links
		$device_active_links_model = new Device_active_link_Model();
		
		$all_active_links = $device_active_links_model->get_all_active_links();
		
		$device_active_links = $device_active_links_model->get_device_active_links($device_id);
		
		$active_links = array();
		
		foreach($all_active_links AS $active_link)
		{
			if (!$active_link->name)
				$active_links[$active_link->id] = $active_link->title;
			else
				$active_links[$active_link->id] = $active_link->name.' ('.$active_link->title.')';
		}
		
		$selected_active_links = array();
		
		foreach ($device_active_links AS $active_link)
		{
			$selected_active_links[] = $active_link->id;
		}
		
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
				->value($device->trade_name)
				->autocomplete('json/device_trade_name');
		
		$group_device_details->dropdown('PPPoE_logging_in')
				->label('PPPoE')
				->options(arr::rbool())
				->selected($device->PPPoE_logging_in);
		
		if ($this->acl_check_edit('Devices_Controller', 'login'))
		{
			$group_device_details->input('login')
					->label('Username')
					->rules('length[0,30]')
					->value($device->login)
					->autocomplete('json/device_login');
		}
		
		if ($this->acl_check_edit('Devices_Controller', 'password'))
		{
			$group_device_details->input('login_password')
					->label('Password')
					->rules('length[0,30]')
					->value($device->password)
					->autocomplete('json/device_password');
		}
		
		$group_device_details->html_textarea('comment')
				->mode('simple')
				->label('Comment')
				->value($device->comment);
		
		$group_device_details->dropdown('active_links[]')
				->label('Device active links')
				->options($active_links)
				->selected($selected_active_links)
				->multiple('multiple')
				->size(10);
		
		
		if (Settings::get('finance_enabled'))
		{
			$group_payment = $form->group('Device repayments')->visible($device->price > 0);

			$group_payment->input('price')
					->rules('valid_numeric')
					->value($device->price ? $device->price : '');

			$group_payment->input('payment_rate')
					->label('Monthly payment rate')
					->rules('valid_numeric')
					->value($device->payment_rate ? $device->payment_rate : '')
					->callback(array($this, 'valid_repayment'));

			$group_payment->date('buy_date')
					->label('Buy date')
					->years(date('Y')-100, date('Y'))
					->value(strtotime($device->buy_date));
		}
		
		$group_address = $form->group('Address');
		
		$address_point_server_active = Address_points_Controller::is_address_point_server_active();
		
		// If address database application is set show new form
		if ($address_point_server_active)
		{	
			$group_address->dropdown('country_id')
					->label('Country')
					->rules('required')
					->options($arr_countries)
					->style('width:200px')
					->selected(Settings::get('default_country'));
			
			$group_address->input('town')
				->label(__('Town').' - '.__('District'))
				->rules('required')
				->class('join1')
				->value($device->address_point->town->town);
			
			$group_address->input('district')
				->class('join2')
				->value(($device->address_point->town->quarter !== NULL ? $device->address_point->town->quarter : $device->address_point->town->town));

			$group_address->input('street')
				->label('Street')
				->rules('required')
				->value(($device->address_point->street != NULL ?
						$device->address_point->street->street." ".$device->address_point->street_number :
						$device->address_point->street_number)
					);
						
			$group_address->input('zip')
				->label('Zip code')
				->rules('required')
				->value($device->address_point->town->zip_code);
		}
		else
		{
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
		}
		
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
			
			$match = array();
			
			// validate address
			if ($address_point_server_active &&
				(
					!Address_points_Controller::is_address_point_valid(
						$form_data['country_id'],
						$form_data['town'],
						$form_data['district'],
						$form_data['street'],
						$form_data['zip']
					) ||
					!preg_match('((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', $form_data['street'], $match)
				))
			{
				$group_address->street->add_error('required', __('Invalid address point.'));
			}
			else
			{
				// street
				if ($address_point_server_active)
				{
					$street = trim(preg_replace(' ((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', '', $form_data['street']));

					$number = $match[0];
				}
				
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

					if ($this->acl_check_new('Devices_Controller', 'login'))
					{
						$device->login = $form_data['login'];
					}

					if ($this->acl_check_new('Devices_Controller', 'password'))
					{
						$device->password = $form_data['login_password'];
					}

					$device->comment = $group_device_details->comment->value; // not escaped

					if (Settings::get('finance_enabled'))
					{
						$device_payment_changed = FALSE;

						if ($device->price != $form_data['price'] || 
							$device->payment_rate != $form_data['payment_rate'] || 
							$device->buy_date != date('Y-m-d', $form_data['buy_date']))
						{
							$device_payment_changed = TRUE;
						}

						$device->price = $form_data['price'];
						$device->payment_rate = $form_data['payment_rate'];
						$device->buy_date = date('Y-m-d', $form_data['buy_date']);
					}

					$address_point_model = new Address_point_Model();
					
					if ($address_point_server_active)
					{
						$t = new Town_Model();
						$s = new Street_Model();
						$t_id = $t->get_town($form_data['zip'], $form_data['town'], $form_data['district'])->id;
						$s_id = $s->get_street($street, $t_id)->id;

						$ap = $address_point_model->get_address_point($form_data['country_id'], $t_id, $s_id, $number,
								$gpsx, $gpsy);
					}
					else
					{
						$ap = $address_point_model->get_address_point(
								$form_data['country_id'], $form_data['town_id'],
								$form_data['street_id'], $form_data['street_number'],
								$gpsx, $gpsy
						);
					}

					$device->save_throwable();
					
					// must be reloaded some sub object may changed
					$device->reload();

					// add address point if there is no such
					if (!$ap->id)
					{
						// save
						$ap->save_throwable();
					}
					// new address point
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

							if ($addr && $addr->id)
							{
								$addr->delete_throwable();
							}
						}
					}

					// add GPS
					if (!empty($gpsx) && !empty($gpsy))
					{ // save
						$ap->update_gps_coordinates($ap->id, $gpsx, $gpsy);
					}
					else
					{ // delete gps
						$ap->gps = NULL;
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
						
						// change connected from if owner has changed to an applicant 
						// and if he is not connected yet
						if ($device->user->member->type == Member_Model::TYPE_APPLICANT && (
								empty($device->user->member->applicant_connected_from) ||
								$device->user->member->applicant_connected_from == '0000-00-00'
							))
						{
							// connected from now
							$device->user->member->applicant_connected_from = date('Y-m-d');
							$device->user->member->save_throwable();
						}
					}

					// device's payment has been changed
					if (Settings::get('finance_enabled') && $device_payment_changed)
					{
						Accounts_Controller::recalculate_device_fees(
							$device->user->member->get_credit_account()->id
						);
					}

					// update device active links
					$device_active_links_model->unmap_device_from_active_links($device_id);
					$device_active_links_model->map_device_to_active_links($device_id, $form_data['active_links']);

					unset($form_data);

					$device->transaction_commit();
					status::success('Device has been successfully updated.');
					url::redirect(Path::instance()->previous());
				}
				catch (Exception $e)
				{
					$device->transaction_rollback();
					Log::add_exception($e);
					status::error('Device has not been updated.', $e);
				}
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
		
		if (!$device->id)
		{
			Controller::error(RECORD);
		}
		
		$mid = $device->user->member_id;
		
		if (!$this->acl_check_delete('Devices_Controller', 'devices', $mid))
		{
			Controller::error(ACCESS);
		}
		
		$linkback = Path::instance()->previous();

		if (url::slice(url_lang::uri($linkback), 1, 1) == 'show')
		{
			$linkback = 'devices/show_all';
		}
		
		// delete
		try
		{
			self::delete_device($device_id);
			status::success('Device has been successfully deleted.');
		}
		catch (Exception $e)
		{
			Log::add_exception($e);
			status::error(__($e->getMessage()), $e);
		}
		
		$this->redirect($linkback);
	}
	
	/**
	 * Deletes device including all its interfaces and ip adresses, etc. (using FK)
	 * 
	 * @author Ondrej Fibich, Jan Dubina
	 * @param integer $device_id
	 * @return boolean Done?
	 */
	public static function delete_device($device_id)
	{
		$device = new Device_Model($device_id);
		
		if ($device->id == 0)
		{
			throw new Exception('Error - cant delete device');
		}
		
		$mid = $device->user->member_id;
		
		$subnet_model = new Subnet_Model();
		
		$all_subnets = $subnet_model->get_all_unique_subnets_by_device($device->id);
		
		$subnets = array();
		
		foreach ($all_subnets AS $subnet)
		{
			$subnets[] = $subnet->id;
		}
		
		$subnets = array_unique($subnets);
		
		if (!Controller::instance()->acl_check_delete('Devices_Controller', 'devices', $mid))
		{
			throw new Exception('Error - cant delete device');
		}
	
		// gets number of maximum of acceptable repeating of operation
		// after reaching of deadlock and time of waiting between
		// other attempt to make transaction (#254)
		$transaction_attempt_counter = 0;
		$max_attempts = max(1, abs(Settings::get('db_trans_deadlock_repeats_count')));
		$timeout = abs(Settings::get('db_trans_deadlock_repeats_timeout'));
		
		// try to delete
		while (TRUE)
		{
			try // try to make DB transction
			{
				$device->transaction_start();
				
				// expired subnets (#465)
				$expired = array();
				foreach ($device->ifaces as $i)
				{
					foreach ($i->ip_addresses as $ip)
					{
						$expired[] = $ip->subnet_id;
					}
				}
				ORM::factory('subnet')->set_expired_subnets($expired);
				
				$device->delete_throwable();
				
				try
				{
					Allowed_subnets_Controller::update_enabled($mid, NULL, NULL, $subnets, TRUE);
				}
				catch (Exception $e)
				{
					throw new Exception('Error - cannot update allowed subnets of member.');
				}
				
				$device->transaction_commit();
				
				return TRUE; // done
			}
			catch (Exception $e) // failed => rollback and wait 100ms before next attempt
			{
				$device->transaction_rollback();
				if (++$transaction_attempt_counter >= $max_attempts) // this was last attempt?
				{
					throw $e;
				}
				
				usleep($timeout);
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Generate export of device
	 * 
	 * @author Michal Kliment
	 * @param type $device_id 
	 */
	public function export($device_id = NULL, $format = '', $output = '', $forced = 0)
	{
		// bad parameter
		if (!$device_id || !is_numeric($device_id))
			Controller::warning(PARAMETER);
		
		$device = new Device_Model($device_id);
		
		// device doesn't exist
		if (!$device->id)
		{
			// user is not logged
			if (!$this->user_id)
			{
				@header('HTTP/1.0 404 Not found');
				die();
			}
			else
			{
				Controller::error(RECORD);
			}
		}
		
		// it is device itself?
		$from_device = $device->is_ip_address_of_device(server::remote_addr());
		
		if ($from_device)
		{	
			// update access time of device
			try
			{
				$device->transaction_start();
				$device->access_time = date('Y-m-d H:i:s');
				$device->save_throwable();
				$device->transaction_commit();
			}
			catch(Exception $e)
			{
				$device->transaction_rollback();
				Log::add_exception($e);
				Log_queue_Model::error(
					'Error in device export: Cannot update access time', $e
				);
			}
		}
		else
		{
			// user is not logged
			if (!$this->user_id)
			{
				@header('HTTP/1.0 403 Forbidden');
				die();
			}
			else
			{
				if (!$this->acl_check_view('Devices_Controller', 'export', $device->user->member_id))
					Controller::error(ACCESS);
			}
		}
		
		// definition of formats of export
		$formats = array
		(
			'debian-etc-dhcp-dhcpd'				=> 'Debian /etc/dhcp/dhcpd.conf',
			'debian-etc-network-interfaces'		=> 'Debian /etc/network/interfaces',
			'mikrotik-all'						=> 'Mikrotik',
			'mikrotik-ip-dhcp-server'			=> 'Mikrotik /ip dhcp-server',
			'mikrotik-ip-dhcp-server-lease'		=> 'Mikrotik /ip dhcp-server lease',
		);
		
		// type of outputs
		$outputs = array
		(
			'text' => __('Text'),
			'file' => __('File')
		);
		
		// format and output is set
		if ($format != '' && $output != '')
		{
			// bad format or output
			if (!isset($formats[$format]) || !isset($outputs[$output]))
				Controller::error(RECORD);
			
			// it is device itself?
			if ($from_device)
			{
				// generating of DHCP server's config
				if (strstr($format, 'dhcp') !== FALSE)
				{
					$subnet_model = new Subnet_Model();
					
					// no forced download and no change (#474)
					if (!$forced && !$subnet_model->is_any_subnet_of_device_expired($device_id))
					{
						if (!text::starts_with($format, 'mikrotik'))
						{
							@header('HTTP/1.0 304 Not Modified');
						}
						die();
					}
				}
			}
			
			try
			{
				// get full export of device
				$device_export = $device->get_export();
			}
			catch (Exception $e)
			{
				Log::add_exception($e);
				Log_queue_Model::error($e->getMessage(), print_r ($device, TRUE));
				
				if ($from_device)
				{
					if (!text::starts_with($format, 'mikrotik'))
					{
						@header('HTTP/1.0 500 Internal Server Error');
					}
					die();
				}
			}
			
			// it is device itself?
			if ($from_device)
			{
				// generating of DHCP server's config
				if (strstr($format, 'dhcp') !== FALSE)
				{
					// update DHCP expire flag
					$subnet_model->set_expired_subnets_of_device($device_id, 0);
				}
			}

			if ($output == 'file')	
			{
				switch ($format)
				{
					case "mikrotik":
						$ext = '.rsc';
						break;

					default:
						$ext = '';
						break;
				}

				header ("Content-disposition: attachment; filename=".url::title($device->name)."-".$format."-export".$ext);
			}

			$view = new View('device_export_templates/'.$format);
			$view->result = $device_export;
			$view->render(TRUE);
		}
		// form to choose format and output
		else if (!$from_device)
		{
			$title = __('Export of device');
			
			$form = new Forge();

			// format of export
			$form->dropdown('format')
				->rules('required')
				->options(array
				(
					NULL => '----- '.__('Choose format of export').' -----'
				) + $formats);

			// result format - text or file
			$form->dropdown('output')
				->rules('required')
				->label('Download as')
				->options(array
				(
					NULL => '----- '.__('Choose').' -----'
				) + $outputs);

			$form->submit('Export');
			
			// form is validate
			if ($form->validate())
			{	
				$form_data = $form->as_array();

				$this->redirect('devices/export/'.$device_id.'/'.$form_data['format'].'/'.$form_data['output']);
			}
			
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
			if (!$this->acl_check_view('Devices_Controller', 'map'))
					Controller::error(ACCESS);
			
			$breadcrumbs = breadcrumbs::add()
					->link('devices/show_all/', 'Devices',
							$this->acl_check_view('Devices_Controller', 'devices'));
		}
		else
		{
			if (!$this->acl_check_view('Devices_Controller', 'map', $device->user->member_id))
					Controller::error(ACCESS);
			
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
		
		$grids['ip_addresses'] = '';
		
		if ($this->acl_check_view('Ip_addresses_Controller', 'ip_address', $member_id))
		{
			$ip_address_model = new Ip_address_Model();
			$ips = $ip_address_model->get_ip_addresses_of_device($device->id);

			$grids['ip_addresses'] = new Grid('devices', null, array
			(
				'use_paginator'	   			=> false,
				'use_selector'	   			=> false,
				'total_items'				=> count($ips)
			));

			if ($this->acl_check_new('Ip_addresses_Controller', 'ip_address', $member_id))
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

			if ($this->acl_check_new('Subnets_Controller', 'subnet', $member_id))
			{
				$grids['ip_addresses']->link_field('subnet_id')
						->link('subnets/show', 'subnet_name')
						->label('Subnet');
			}

			if ($this->acl_check_new('Ifaces_Controller', 'iface', $member_id))
			{
				$grids['ip_addresses']->link_field('iface_id')
						->link('ifaces/show', 'iface_name')
						->label('Interface');
			}

			$actions = $grids['ip_addresses']->grouped_action_field();

			$actions->add_action('id')
					->icon_action('show')
					->url('ip_addresses/show')
					->class('popup_link');

			if ($this->acl_check_edit('Ip_addresses_Controller', 'ip_address', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ip_addresses/edit')
						->class('popup_link');
			}

			if ($this->acl_check_delete('Ip_addresses_Controller', 'ip_address', $member_id))
			{
				$actions->add_action('id')
						->icon_action('delete')
						->url('ip_addresses/delete')
						->class('delete_link');
			}

			$grids['ip_addresses']->datasource($ips);
		}
		
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
		
		if ($this->acl_check_new('Ifaces_Controller', 'iface', $member_id))
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
		
		if ($this->acl_check_view('Ifaces_Controller', 'iface', $member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('ifaces/show')
					->class('popup_link');
		}
			
		if ($this->acl_check_edit('Ifaces_Controller', 'iface', $member_id))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('ifaces/edit');
		}
		
		if ($this->acl_check_delete('Ifaces_Controller', 'iface', $member_id))
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

			if ($this->acl_check_new('Ifaces_Controller', 'iface', $member_id))
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

			if ($this->acl_check_view('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('show')
						->url('ifaces/show')
						->class('popup_link');
			}

			if ($this->acl_check_edit('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ifaces/edit');
			}

			if ($this->acl_check_delete('Ifaces_Controller', 'iface', $member_id))
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

			if ($this->acl_check_new('Ifaces_Controller', 'iface', $member_id))
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

			if ($this->acl_check_view('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('show')
						->url('ifaces/show')
						->class('popup_link');
			}

			if ($this->acl_check_edit('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ifaces/edit');
			}

			if ($this->acl_check_delete('Ifaces_Controller', 'iface', $member_id))
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

			if ($this->acl_check_new('Ifaces_Controller','iface',$member_id))
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

			if ($this->acl_check_view('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('show')
						->url('ifaces/show')
						->class('popup_link');
			}

			if ($this->acl_check_edit('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ifaces/edit');
			}

			if ($this->acl_check_delete('Ifaces_Controller', 'iface', $member_id))
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

			if ($this->acl_check_new('Ifaces_Controller', 'iface', $member_id))
			{
				$grids['vlan_ifaces']->add_new_button(
						'ifaces/add/'.$device->id.'/'.Iface_Model::TYPE_VLAN,
						'Add new VLAN interface'
				);
			}

			$grids['vlan_ifaces']->field('name');

			$grids['vlan_ifaces']->link_field('vlan_id')
					->link('vlans/show', 'name')
					->label('VLAN name');

			$grids['vlan_ifaces']->field('tag_802_1q')
					->label('tag_802_1q')
					->class('center');

			$grids['vlan_ifaces']->link_field('iface_id')
					->link('ifaces/show', 'iface_name')
					->label('Interface');

			$actions = $grids['vlan_ifaces']->grouped_action_field();

			if ($this->acl_check_view('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('show')
						->url('ifaces/show')
						->class('popup_link');
			}

			if ($this->acl_check_edit('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ifaces/edit');
			}

			if ($this->acl_check_delete('Ifaces_Controller', 'iface', $member_id))
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

			if ($this->acl_check_new('Ifaces_Controller', 'iface', $member_id))
			{
				$grids['ports']->add_new_button(
						'ifaces/add/'.$device->id.'/'.Iface_Model::TYPE_PORT,
						'Add new port', array('title' => __('Add new port'))
				);
			}
			
			if ($this->acl_check_edit('Devices_Controller', 'ports_vlans_settings', $member_id))
			{
				$grids['ports']->add_new_button(
							'devices/ports_vlans_settings/'.$device->id,
							'Ports and VLANs settings', array
							(
								'title' => __('Ports and VLANs settings')
							)
					);
			}
			
			$grids['ports']->callback_field('medium')
					->callback('callback::link_medium_icon_field')
					->label('Medium')
					->class('center');

			$grids['ports']->field('number')
					->label('Number')
					->class('center');

			$grids['ports']->field('name')
					->label('Name')
					->class('center');

			$grids['ports']->callback_field('mode')
					->callback('callback::port_mode_field');
			
			$grids['ports']->callback_field('port_vlan')
					->callback('callback::port_vlan_field')
					->label('Port VLAN')
					->class('center');

			$grids['ports']->callback_field('bitrate')
					->callback('callback::bitrate_field', FALSE)
					->class('center');

			$grids['ports']->callback_field('connected_to_device')
					->callback('callback::connected_to_device');

			$actions = $grids['ports']->grouped_action_field();

			if ($this->acl_check_view('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('show')
						->url('ifaces/show')
						->class('popup_link');
			}

			if ($this->acl_check_edit('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ifaces/edit');
			}

			if ($this->acl_check_delete('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('delete')
						->url('ifaces/delete')
						->class('delete_link');
			}

			$grids['ports']->datasource($ports);
		}
		
		// bridge interfaces /////////////////////////////////////////////////////
		$bridges = $iface_model->get_all_ifaces_of_device(
				$device->id, Iface_Model::TYPE_BRIDGE
		);
		
		if (!count($bridges))
		{
			$grids['bridge_ifaces'] = '';
		}
		else
		{
			$grids['bridge_ifaces'] = new Grid('devices', null, array
			(
				'use_paginator'	   			=> false,
				'use_selector'	   			=> false,
				'total_items'				=> count($bridges)
			)); 

			if ($this->acl_check_new('Ifaces_Controller', 'iface', $member_id))
			{
				$grids['bridge_ifaces']->add_new_button(
						'ifaces/add/'.$device->id.'/'.Iface_Model::TYPE_BRIDGE,
						'Add new bridge interface', array('title' => __('Add new bridge interface'))
				);
			}

			$grids['bridge_ifaces']->field('name')
					->label('Name')
					->class('center');
			
			$grids['bridge_ifaces']->field('mac')
					->label('MAC');

			$actions = $grids['bridge_ifaces']->grouped_action_field();

			if ($this->acl_check_view('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('show')
						->url('ifaces/show')
						->class('popup_link');
			}

			if ($this->acl_check_edit('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('ifaces/edit');
			}

			if ($this->acl_check_delete('Ifaces_Controller', 'iface', $member_id))
			{
				$actions->add_action('id')
						->icon_action('delete')
						->url('ifaces/delete')
						->class('delete_link');
			}

			$grids['bridge_ifaces']->datasource($bridges);
		}
		
		// here @TODO bridges and special interfaces
		$grids['special_ifaces'] = '';
		
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
		
		$filter_form->add('ip_address');
		
		return $filter_form;
	}
	
	/**
	 * Validate repayment of device
	 * 
	 * @param Form_Field $input
	 */
	public function valid_repayment($input = NULL)
	{
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$price = $this->input->post('price');
		$rate = $input->value;
		
		if (!empty($price) && doubleval($rate) <= 0)
		{
			$input->add_error('required', __('Must be greater than zero'));
		}
	}
	
	/**
	 * Displays topology of device in network
	 * 
	 * @author Michal Kliment
	 * @param integer $device_id
	 */
	public function topology($device_id = NULL)
	{
		// bad parameter
		if (!$device_id || !is_numeric($device_id))
			Controller::warning (PARAMETER);
		
		$device = new Device_Model($device_id);
		
		// record doesn't exist
		if (!$device->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_view(get_class($this), 'topology', $device->user->member_id))
			Controller::error(ACCESS);
		
		$device_topology = new stdClass();
		
		$device_topology->ifaces = array();
		
		$numbers = array();
		$names = array();
		
		// iterate over all ifaces of device
		foreach ($device->ifaces as $iface)
		{
			// only iface which can be connected to other
			if (!Iface_Model::type_has_link($iface->type))
			{
				continue;
			}
			
			$numbers[] = $iface->number;
			$names[] = $iface->name;
			
			$device_topology->ifaces[$iface->id] = arr::to_object(array
			(
				'id' => $iface->id,
				'name' => $iface->name,
				'type' => $iface->type,
				'mac' => $iface->mac,
			));
			
			$device_topology->ifaces[$iface->id]->connected_devices = NULL;
			
			// find all ifaces which are connected to this iface
			$connected_ifaces = $iface->get_ifaces_connected_to_iface();
			
			if (count($connected_ifaces))
			{
				$connected_devices = array();
				
				$device_names = array();
						
				foreach ($connected_ifaces as $connected_iface)
				{
					$connected_devices[] = arr::to_object(array
					(
						'id' => $connected_iface->device_id,
						'name' => $connected_iface->device->name,
						'type' => $connected_iface->device->type,
						'member_id' => $connected_iface->device->user->member_id
					));
					
					$device_names[] = $connected_iface->device->name;
				}
				
				// sort connected devices by name
				array_multisort($device_names, $connected_devices);
				
				$device_topology->ifaces[$iface->id]->connected_devices = $connected_devices;
			}
			
			// sort ifaces by port number, then by name
			array_multisort($numbers, $names, $device_topology->ifaces);
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
			->link('devices/show/' . $device_id, $device->name,
					$this->acl_check_edit('Devices_Controller', 'devices', $device->user->member_id))
			->enable_translation()
			->text('Topology');
		
		$title = __('Topology of device').' '.$device->name;
		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs;
		$view->title = $title;
		$view->content = new View('devices/topology');
		$view->content->device = $device;
		$view->content->device_topology = $device_topology;
		$view->render(TRUE);
	}
	
	/**
	 * Ports and VLANs settings of given device
	 * 
	 * @author Michal Kliment
	 * @param type $device_id
	 * @param type $vlan_id
	 */
	public function ports_vlans_settings ($device_id = NULL, $vlan_id = NULL)
	{
		// bad parameter
		if (!$device_id || !is_numeric($device_id))
			Controller::warning (PARAMETER);
		
		$device = new Device_Model($device_id);
		
		// device doesn't exist
		if (!$device->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_edit('Devices_Controller', 'ports_vlans_settings', $device->user->member_id))
			Controller::error(ACCESS);
		
		$form = new Forge();
		
		$form->set_attr('id', 'vlan-form');
		
		$vlan_model = new Vlan_Model();

		$vlans = array(NULL => '----- '.__('Select VLAN').' -----') + $vlan_model->select_list();

		$form->dropdown('vlan_id')
			->options($vlans)
			->selected($vlan_id)
			->label('VLAN')
			->add_button('vlans');

		$form->submit('submit');

		if ($form->validate() && !isset($_POST['id']))
		{
			$form_data = $form->as_array();

			$this->redirect('devices/ports_vlans_settings/'.$device_id.'/'.$form_data['vlan_id']);
		}
		
		if ($vlan_id)
		{
			$vlan = new Vlan_Model($vlan_id);
			
			// VLAN doesn't exist
			if (!$vlan->id)
				Controller::error (RECORD);
			
			if ($_POST)
			{
				$ifaces_vlan_model = new Ifaces_vlan_Model();
				
				try
				{
					$ifaces_vlan_model->transaction_start();
					
					foreach ($_POST['id'] as $number => $iface_id)
					{
						$iface = new Iface_Model($iface_id);
						$iface->port_mode = $_POST['mode'][$number];
						$iface->save_throwable();
						
						$ifaces_vlan = $ifaces_vlan_model->where(array
						(
							'iface_id'	=> $iface_id,
							'vlan_id'	=> $vlan_id
						))->find();

						if ($ifaces_vlan->id)
						{
							if (!$_POST['type'][$number])
							{
								if (!$ifaces_vlan->port_vlan)
									$ifaces_vlan->delete_throwable();
								
								continue;
							}
						}
						else
						{
							if (!$_POST['type'][$number])
							{
								continue;
							}

							$ifaces_vlan->clear();
							$ifaces_vlan->iface_id	= $iface_id;
							$ifaces_vlan->vlan_id	= $vlan_id;
							$ifaces_vlan->save_throwable();
						}

						$ifaces_vlan->tagged = ($_POST['type'][$number] == Iface_Model::PORT_VLAN_TAGGED);
						$ifaces_vlan->save_throwable();
						
						$ifaces_vlans = $ifaces_vlan_model->where(array
						(
							'iface_id'	=> $iface_id,
						))->find_all();
						
						foreach ($ifaces_vlans as $ifaces_vlan)
						{
							$ifaces_vlan->port_vlan = FALSE;
							
							if (isset($_POST['pvid'][$number]) && $ifaces_vlan->vlan_id == $_POST['pvid'][$number])
								$ifaces_vlan->port_vlan = TRUE;
							
							if ($iface->port_mode == Iface_Model::PORT_MODE_TRUNK)
								$ifaces_vlan->tagged = TRUE;
							else if ($iface->port_mode == Iface_Model::PORT_MODE_ACCESS)
								$ifaces_vlan->tagged = FALSE;
							
							$ifaces_vlan->save_throwable();
						}
					}
					
					$ifaces_vlan_model->transaction_commit();
					status::success('Ports and VLANs settings has been successfully updated.');
				}
				catch (Exception $e)
				{
					$ifaces_vlan_model->transaction_rollback();
					status::error('Error - cannot update ports and VLANs settings.');
				}
				
				$this->redirect('devices/ports_vlans_settings/'.$device->id.'/'.$vlan_id);
			}
			
			$ports = array();
			
			foreach ($device->ifaces as $iface)
			{
				if ($iface->type != Iface_Model::TYPE_PORT)
					continue;
				
				$pvid	= NULL;
				
				$type = NULL;
				
				$vlans = array();
				
				foreach ($iface->ifaces_vlans as $ifaces_vlan)
				{
					$vlans[$ifaces_vlan->vlan_id] = $ifaces_vlan->vlan->tag_802_1q.' ('.$ifaces_vlan->vlan->name.')';
					
					if ($ifaces_vlan->port_vlan)
						$pvid = $ifaces_vlan->vlan_id;
					
					if ($ifaces_vlan->vlan_id != $vlan_id)
						continue;
					
					$type = ($ifaces_vlan->tagged) ? Iface_Model::PORT_VLAN_TAGGED : Iface_Model::PORT_VLAN_UNTAGGED;
				}
				
				$ports[$iface->number] = array
				(
					'id'		=> $iface->id,
					'mode'		=> $iface->port_mode,
					'vlans'		=> $vlans,
					'pvid'		=> $pvid,
					'type'		=> $type
				);
			}
			
			ksort($ports);
		}
		
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
				->text('Ports and VLANs settings');
		
		$title = __('Ports and VLANs settings');
			
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $title;
		$view->content = new View('devices/ports_vlans_settings');
		$view->content->form = $form;
		
		if ($vlan_id)
		{
			$view->content->vlan = $vlan;
			$view->content->ports = $ports;
		}
		
		$view->render(TRUE);
	}
}