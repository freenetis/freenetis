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
 * Manages all interfaces of network in the system.
 * 
 * @package Controller
 */
class Ifaces_Controller extends Controller
{	
	/**
	 * Device for validators
	 *
	 * @var integer 
	 */
	private $device_id = NULL;
	
	/**
	 * Interface for validators
	 *
	 * @var integer 
	 */
	private $iface_id = NULL;
	
	/**
	 * Interface type for validators
	 *
	 * @var integer 
	 */
	private $iface_type = NULL;
	
	/**
	 * Index redirect to show all
	 */
	public function index()
	{
		url::redirect('ifaces/show_all');
	}

	/**
	 * Function shows all interfaces.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'id',
			$order_by_direction = 'asc', $page_word = null, $page = 1)
	{
		// access rights	
		if(!$this->acl_check_view('Devices_Controller','iface'))
			Controller::error(ACCESS);
		
		$filter_form = new Filter_form('i');
		
		$filter_form->add('mac')
				->label('MAC address')
				->class('mac')
				->callback('json/iface_mac');
		
		$filter_form->add('name')
				->label('Name of interface');
		
		$filter_form->add('link_name')
				->type('combo')
				->callback('json/link_name');
		
		$filter_form->add('device_name')
				->type('combo')
				->callback('json/device_name');
		
		$filter_form->add('type')
				->type('select')
				->values(Iface_Model::get_types());
		
		$filter_form->add('user_name')
				->type('combo')
				->label('Firstname of user')
				->callback('json/user_name');
		
		$filter_form->add('user_surname')
				->type('combo')
				->label('Surname of user')
				->callback('json/user_surname');
		
		$filter_form->add('member_name')
				->type('combo')
				->callback('json/member_name');
		
		// get new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		// model
		$iface_model = new Iface_Model();
		$total_ifaces = $iface_model->count_all_ifaces($filter_form->as_sql());	
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_ifaces)
			$sql_offset = 0;
		
		$ifaces = $iface_model->get_all_ifaces(
				$sql_offset, (int)$limit_results, $order_by,
				$order_by_direction, $filter_form->as_sql()
		);
		
		$grid = new Grid('ifaces', null,array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 10,
			'base_url'					=> Config::get('lang').'/ifaces/show_all/'
										.$limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=>  $total_ifaces,
			'items_per_page' 			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter' => $filter_form
		));
		
		if ($this->acl_check_new('Devices_Controller','iface'))
		{
			$grid->add_new_button('ifaces/add', 'Add new interface');
		}
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_callback_field('type')
				->callback('callback::iface_type_field')
				->class('center');
		
		$grid->order_field('name');
		
		$grid->order_field('mac')
				->label('MAC');
		
		$grid->order_link_field('link_id')
				->link('links/show', 'link_name')
				->label('Link');
		
		$grid->order_callback_field('device_name')
				->callback('callback::device_field');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Devices_Controller','iface'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('ifaces/show');
		}
		
		if ($this->acl_check_edit('Devices_Controller','iface'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('ifaces/edit');
		}
		
		if ($this->acl_check_delete('Devices_Controller','iface'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('ifaces/delete')
					->class('delete_link');
		}
		
		$breadcrumbs = breadcrumbs::add()
				->text('Interfaces')
				->html();
		
		$grid->datasource($ifaces);
		$headline = __('Interfaces list');
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs;
		$view->title = $headline;
		$view->content = new View('show_all');
	   	$view->content->table = $grid;
	   	$view->content->headline = $headline;
		$view->render(TRUE);
	} // end of show_all
	
	
	
	/**
	 * Function shows interface details.
	 * 
	 * @param integer $iface_id
	 */
	public function	show($iface_id = null)
	{	
		// bad parameter
		if (!$iface_id || !is_numeric($iface_id))
			Controller::warning(PARAMETER);
		
		$iface = new Iface_Model($iface_id);
		
		// interface doesn't exist
		if (!$iface->id)
			Controller::error(RECORD);
		
		$member_id = $iface->device->user->member_id;
		
		// access control
		if (!$this->acl_check_view('Devices_Controller', 'iface', $member_id))
			Controller::error(ACCESS);
		
		// ip addresses
		$ip_addresses = ORM::factory('ip_address')->get_all_ip_addresses_of_iface($iface->id);
		
		$grid_ip_addresses = new Grid('ifaces', null,array
		(
				'use_paginator'	   			=> false,	
				'use_selector'	   			=> false
		)); 
		
		if ($this->acl_check_new(
				'Devices_Controller', 'ip_address',
				$iface->device->user->member_id
			))
		{
			$grid_ip_addresses->add_new_button(
					'ip_addresses/add/'.$iface->device_id.'/'.$iface->id, 
					'Add new IP address'
			);
		}
		
		$grid_ip_addresses->field('id')
				->label('ID')
				->class('center');
		
		$grid_ip_addresses->field('ip_address')
				->label('IP address');
		
		$grid_ip_addresses->link_field('subnet_id')
				->link('subnets/show', 'subnet_name')
				->label('Subnet');
		
		$actions = $grid_ip_addresses->grouped_action_field();
		
		if ($this->acl_check_view(
				'Devices_Controller', 'ip_address',
				$iface->device->user->member_id
			))
		{
			$actions->add_action()
					->icon_action('show')
					->url('devices/show_ip_address');
		}
		
		if ($this->acl_check_edit(
				'Devices_Controller', 'ip_address',
				$iface->device->user->member_id
			))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('ip_addresses/edit');
		}
		
		if ($this->acl_check_delete(
				'Devices_Controller', 'ip_address',
				$iface->device->user->member_id
			))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('ip_addresses/delete')
					->class('delete_link');
		}
		
		$grid_ip_addresses->datasource($ip_addresses);
		
		if ($iface->name != '')
		{
			$name = $iface->name;
			if ($iface->mac != '')
				 $name .= " ($iface->mac)";
		}
		else
		{
			$name = $iface->mac;
		}
		
		if (url::slice(url_lang::uri(Path::instance()->previous()), 0, 1) != 'ifaces')
		{
			$breadcrumbs = breadcrumbs::add()
				->link(
					'members/show_all', 'Members',
					$this->acl_check_view('Members_Controller','members')
				)
				->link(
					'members/show/'.$iface->device->user->member_id,
					'ID '.$iface->device->user->member->id.' - '.$iface->device->user->member->name,
					$this->acl_check_view(
						'Members_Controller', 'members',
						$iface->device->user->member_id
					)
				)
				->link(
					'users/show_by_member/'.$iface->device->user->member_id,
					'Users', $this->acl_check_view(
						'Users_Controller', 'users', $iface->device->user->member_id
					)
				)
				->link(
					'users/show/'.$iface->device->user_id,
					$iface->device->user->get_full_name(), $this->acl_check_view(
						'Users_Controller', 'users',
						$iface->device->user->member_id
					)
				)
				->link(
					'devices/show_by_user/'.$iface->device->user_id,
					'Devices', $this->acl_check_view(
						'Devices_Controller',
						'devices',
						$iface->device->user->member_id
					)
				)
				->link(
					'devices/show/'.$iface->device_id,
					$iface->device->name, $this->acl_check_view(
						'Devices_Controller', 'devices',
							$iface->device->user->member_id
					)
				)->text($name)
				->html();
		}
		else
		{
			$breadcrumbs = breadcrumbs::add()
				->link(
					'ifaces/show_all', 'Interfaces',
					$this->acl_check_view('Devices_Controller', 'iface')
				)
				->text($name)
				->html();
		}
		
		$headline = __('Interface detail').' - '.$iface->name;		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs;
		$view->title = $headline;
		$view->content = new View('ifaces/show');
		
		$detail = '';
		$child_ifaces = '';
		$port_vlan = '';

		switch ($iface->type)
		{
			case Iface_Model::TYPE_WIRELESS:
				$detail = new View('ifaces/detail');
				$detail->ip_addresses = $grid_ip_addresses;
				
				if ($iface->wireless_mode == Iface_Model::WIRELESS_MODE_AP)
				{
					$iface_model = new Iface_Model();
					$children = $iface_model->get_virtual_ap_ifaces_of_parent($iface->id);
					
					$data = $children->as_array();
					
					if ($data)
					{
						$child_ifaces = new Grid('ifaces', null,array
							(
									'use_paginator'	   			=> false,	
									'use_selector'	   			=> false
							));
						$child_ifaces->add_new_button(
								'ifaces/add/'.$iface->device_id.'/'.Iface_Model::TYPE_VIRTUAL_AP, 
								'Add new Virtual AP'
						);

						$child_ifaces->field('id')
							->label('ID')
							->class('center');
						
						$child_ifaces->field('name')
							->link('ifaces/show')
							->label('Name');
						
						$actions = $child_ifaces->grouped_action_field();
						$actions->add_action()
								->icon_action('show')
								->url('ifaces/show');
						$actions->add_action()
								->icon_action('edit')
								->url('ifaces/edit');
						$actions->add_action()
								->icon_action('delete')
								->url('ifaces/detele')
								->class('delete_link');

						$child_ifaces->datasource($data);
					}
				}
				break;
			case Iface_Model::TYPE_ETHERNET:
				$detail = new View('ifaces/detail');
				$detail->ip_addresses = $grid_ip_addresses;
				break;
			case Iface_Model::TYPE_BRIDGE:
				$iv = new Ifaces_vlan_Model();
				$ifaces = $iv->get_all_bridged_ifaces_of_iface($iface_id);
				
				$member_id = $iface->device->user->member_id;
				
				// grid
				$grid = new Grid('devices', null, array
				(
					'use_paginator'	   			=> false,
					'use_selector'	   			=> false,
					'total_items'				=> count($ifaces)
				));
				
				if ($this->acl_check_new(get_class($this), 'iface', $member_id))
				{
					$grid->add_new_button(
							'ifaces/add/' . $iface->device->id, 'Add new interface'
					);
				}

				$grid->callback_field('type')
						->callback('callback::iface_type_field')
						->class('center');

				$grid->field('name');

				$grid->callback_field('mac')
						->callback('callback::not_empty')
						->label('MAC')
						->class('center');

				$actions = $grid->grouped_action_field();

				if ($this->acl_check_view('Devices_Controller', 'iface', $member_id))
				{
					$actions->add_action('id')
							->icon_action('show')
							->url('devices/show_iface')
							->class('popup_link');
				}

				if ($this->acl_check_delete('Devices_Controller', 'iface', $member_id))
				{
					$actions->add_action('id')
							->icon_action('delete')
							->url('ifaces/remove_from_bridge/'.$iface->id)
							->class('delete_link')
							->label('Remove interface from bridge');
				}

				$grid->datasource($ifaces);
				$detail = $grid;
				break;
			case Iface_Model::TYPE_PORT:
				$vl = new Vlan_Model();
				$vlans = $vl->get_all_vlans_of_iface($iface_id);
				$port_vlan = $vl->get_default_vlan_of_interface($iface_id);
				
				// grid
				$grid = new Grid('devices', null, array
				(
					'use_paginator'	   			=> false,
					'use_selector'	   			=> false,
					'total_items'				=> count($vlans)
				));
				
				$grid->field('name')
						->label('VLAN name');
				
				$grid->field('tag_802_1q')
						->class('center');
				
				$grid->callback_field('tagged')
						->callback('callback::boolean')
						->label('Tagged VLAN')
						->class('center');
				
				$actions = $grid->grouped_action_field();
				
				$actions->add_action('vlan_id')
						->icon_action('show')
						->url('vlans/show')
						->class('popup_link');
				
				$actions->add_conditional_action('vlan_id')
						->icon_action('delete')
						->condition('is_not_default_vlan')
						->url('vlans/remove_from_port/'.$iface->id)
						->class('delete_link');
				
				
				$grid->datasource($vlans);
				$detail = $grid;
				break;
			case Iface_Model::TYPE_INTERNAL:
				$detail = new View('ifaces/detail');
				$detail->ip_addresses = $grid_ip_addresses;
				break;
		};
		
		$view->content->iface = $iface;
		$view->content->detail = $detail;
		$view->content->headline = $headline;
		$view->content->port_vlan = $port_vlan;
		$view->content->child_ifaces = $child_ifaces;
		$view->render(TRUE);
	} // end of show
	
	/**
	 * Function adds new interface.
	 * 
	 * @param integer $device_id	Device ID
	 * @param integer $type			Iface type
	 * @param boolean $add_button	If opened by add button
	 * @param integer $connect_type	Connect to iface type - only if add_button
	 */
	public function add($device_id = NULL, $type = NULL,
			$add_button = FALSE, $connect_type = NULL) 
	{
		if (!$this->acl_check_new('Devices_Controller', 'iface'))
		{
			Controller::error(ACCESS);
		}
			
		// device is set
		if (is_numeric($device_id))
		{
			$device = new Device_Model($device_id);
			
			if (!$device->id)
			{
				Controller::error(RECORD);
			}

			$breadcrumbs = breadcrumbs::add()->link(
					'members/show_all', 'Members',
					$this->acl_check_view('Members_Controller', 'members')
				)->link(
					'members/show/'.$device->user->member_id,
					'ID '.$device->user->member->id.' - '.$device->user->member->name,
					$this->acl_check_view(
						'Members_Controller', 'members', $device->user->member_id
					)
				)->link(
					'users/show_by_member/'.$device->user->member_id, 'Users',
					$this->acl_check_view(
						'Users_Controller', 'users', $device->user->member_id
					)
				)->link(
					'users/show/'.$device->user_id, $device->user->get_full_name(),
					$this->acl_check_view(
						'Users_Controller', 'users', $device->user->member_id
					)
				)->link(
					'devices/show_by_user/'.$device->user_id, 'Devices',
					$this->acl_check_view(
						'Devices_Controller', 'devices', $device->user->member_id
					)
				)->link(
					'devices/show/'.$device_id, $device->name, $this->acl_check_view(
						'Devices_Controller', 'devices', $device->user->member_id
					)
				)->text('Add new interface')
				->html();
		}
		else
		{
			$breadcrumbs = breadcrumbs::add()->link(
					'ifaces/show_all', 'Interfaces',
					$this->acl_check_view('Devices_Controller', 'iface')
				)->text('Add new interface')
				->html();
		}
		
		// only one connected to => skip selecting of type
		if (is_numeric($device_id) && $add_button && is_numeric($connect_type))
		{
			$conn_types = Iface_Model::get_can_connect_to($connect_type);
			
			if (count($conn_types) == 1)
			{
				$type = $conn_types[0];
				$connect_type = null;
			}
		}
		
		if (is_numeric($device_id) && is_numeric($type) && empty($connect_type))
		{
			$im = new Iface_Model();
			
			// iface type doesn't exist
			if (!$im->get_type($type))
			{
				Controller::error(RECORD);
			}
			
			$this->device_id = $device_id;
			$this->iface_type = $type;
			
			$filter_form = Devices_Controller::device_filter_form($device->user_id);
			
			$form = new Forge('ifaces/add/' . $device_id . '/' . $type);

			$base_form = $form->group('Basic data');
			
			$form->hidden('itype')
				->value($type);

			$base_form->input('name')
				->label('Interface name')
				->rules('length[3,250]')
				->style('width:620px')
				->value(Iface_Model::get_default_name($type) . ' ' . $device->name);

			// print mac address field only if it can have mac address
			if (Iface_Model::type_has_mac_address($type))
			{
				$base_form->input('mac')
					->label('MAC')
					->rules('valid_mac_address')
					->style('width:620px');
			}
			
			$base_form->textarea('comment')
				->rules('length[0,254]')
				->cols('20')
				->rows('5')
				->style('width: 620px');

			// print parent iface field only if type is VLAN
			if ($type == Iface_Model::TYPE_VLAN)
			{
				$parent_ifaces = array();

				foreach ($device->ifaces as $iface)
				{
					// limit options only if type is given
					if (in_array($iface->type, Iface_Model::get_can_be_child_of($type)))
					{
						$parent_ifaces[$iface->id] = $iface->name . ' - ' . $iface->device->name;
					}
				}

				$parent_ifaces = array
				(
					NULL => '------- '.__('Select interface').' -------'
				) + $parent_ifaces;

				$vlan_ifaces = array
				(
					NULL => '------- '.__('Select VLAN').' -------'
				) + ORM::factory('vlan')->select_list();

				$vlan_form = $form->group('VLAN setting');

				$vlan_form->dropdown('vlan_id')
					->label('VLAN')
					->options($vlan_ifaces)
					->rules('required')
					->add_button('vlans')
					->style('width:200px');
				
				$vlan_form->dropdown('parent_iface_id')
					->label('Parent interface')
					->options($parent_ifaces)
					->rules('required')
					->style('width:200px');
			}

			// print children ifaces field only if type is bridge
			if ($type == Iface_Model::TYPE_BRIDGE)
			{
				$child_ifaces = array();

				foreach ($device->ifaces as $iface)
				{
					// limit options only if type is given
					if (in_array($type, Iface_Model::get_can_be_child_of($iface->type)))
					{
						$mac = ($iface->mac) ? $iface->mac . ' - ' : '';
						$child_ifaces[$iface->id] = $mac . $iface->name;
					}
				}

				$bridge_form = $form->group('Bridge settings');
				
				$bridge_form->dropdown('children_interfaces[]')
					->multiple('multiple')
					->options($child_ifaces)
					->size(15)
					->label('Children interfaces');
			}

			// print port fields only if type is port
			if ($type == Iface_Model::TYPE_PORT)
			{
				$modes = array
				(
					NULL => '------- '.__('Select mode').' -------'
				) + Iface_Model::get_port_modes();
				
				$vlan_model = new Vlan_Model();
				$arr_vlans = $vlan_model->select_list();
				$default_vlan = $vlan_model->get_default_vlan();
				
				$default_vlans = array
				(
					NULL => '----- ' . __('Select VLAN') . ' -----'
				) + $arr_vlans;

				$port_form = $form->group('Port setting');
				
				$port_form->input('number')
					->value($device->get_next_port_number())
					->class('increase_decrease_buttons')
					->callback(array($this, 'valid_port_nr'));
				
				if (!$add_button)
				{
					$vlan_port_form = $form->group('Port VLAN setting')->visible(FALSE);

					$vlan_port_form->dropdown('port_mode')
						->options($modes)
						->selected(Iface_Model::PORT_MODE_ACCESS)
						->rules('required')
						->style('width:200px');

					$vlan_port_form->dropdown('port_vlan_id')
						->options($default_vlans)
						->selected($default_vlan->id)
						->rules('required')
						->label('Port VLAN')
						->help('port_vlan')
						->callback(array($this, 'valid_port_vlan'))
						->style('width:200px')
						->add_button('vlans');

					$vlan_port_form->dropdown('tagged_vlan_id[]')
						->label('Tagged VLANs')
						->options($arr_vlans)
						->multiple('multiple')
						->size(10)
						->add_button('vlans');

					$vlan_port_form->dropdown('untagged_vlan_id[]')
						->label('Untagged VLANs')
						->options($arr_vlans)
						->selected($default_vlan->id)
						->callback(array($this, 'valid_vlans'))
						->multiple('multiple')
						->size(10)
						->add_button('vlans');
				}
				else
				{
					$form->hidden('port_mode')
						->value(Iface_Model::PORT_MODE_ACCESS);

					$form->hidden('port_vlan_id')
						->value($default_vlan->id);

					$form->hidden('tagged_vlan_id[]');

					$form->hidden('untagged_vlan_id')
						->value($default_vlan->id);
				}
			}

			// print wireless fields only if type is wireless
			if ($type == Iface_Model::TYPE_WIRELESS)
			{
				$modes = array
				(
					NULL => '----- '.__('Select mode').' -----'
				) + Iface_Model::get_wireless_modes();

				$antennas = array
				(
					NULL => '----- '.__('Select antenna').' -----'
				) + Iface_Model::get_wireless_antennas();

				$w_form = $form->group('Wireless setting');

				$w_form->dropdown('wireless_mode')
					->label('Mode')
					->rules('required')
					->options($modes)
					->selected(Iface_Model::WIRELESS_MODE_CLIENT)
					->callback(array($this, 'valid_mode'))
					->style('width:200px');

				$w_form->dropdown('wireless_antenna')
					->label('Antenna')
					->options($antennas)
					->style('width:200px');
			}
			
			// print virtual AP fields only if type is virtual AP
			if ($type == Iface_Model::TYPE_VIRTUAL_AP)
			{
				$parent_ifaces = array();

				foreach ($device->ifaces as $iface)
				{
					// limit options only if type is given
					if (in_array($iface->type, Iface_Model::get_can_be_child_of($type)) &&
						$iface->wireless_mode == Iface_Model::WIRELESS_MODE_AP)
					{
						$parent_ifaces[$iface->id] = $iface->name . ' - ' . $iface->device->name;
					}
				}

				$parent_ifaces = array
				(
					NULL => '------- '.__('Select interface').' -------'
				) + $parent_ifaces;
				
				$vap_form = $form->group('Virtual AP setting');

				$vap_form->dropdown('parent_iface_id')
					->label('Parent interface')
					->options($parent_ifaces)
					->rules('required')
					->callback(array($this, 'valid_mode_virtual_ap'))
					->style('width:200px');
			}

			// print link field only if type can has link and enabled
			if (!$add_button && Iface_Model::type_has_link($type))
			{
				$devices = array
				(
					NULL => '----- '.__('Select device').' -----'
				) + $device->select_list_filtered_device_with_user($filter_form->as_sql(), FALSE);
				
				$ifaces = array
				(
					NULL => '----- '.__('Select interface').' -----'
				); // empty - loaded by JS
				
				if (is_numeric($this->input->post('connected_to')))
				{
					$concat = 'CONCAT(IFNULL(mac, \'- \'),\': \',IFNULL(name,\'\'))';
					
					$ifaces += ORM::factory('iface')
						->where('device_id', $this->input->post('connected_to'))
						->in('type', Iface_Model::get_can_connect_to($type))
						->select_list('id', $concat);
				}

				$c_form = $form->group('Link');
				
				$form->hidden('device_filter');
				
				$medium = Link_Model::MEDIUM_CABLE;
				$bitrate = '100M';
				$wnorm = null;

				if ($type == Iface_Model::TYPE_WIRELESS)
				{
					$medium = Link_Model::MEDIUM_AIR;
					$bitrate = Link_Model::get_wireless_max_bitrate(Link_Model::NORM_802_11_G) . 'M';
					$wnorm = Link_Model::NORM_802_11_G;
				}

				$form->hidden('_device_name')->value($device->name);
				$form->hidden('link_id');
				$form->hidden('link_name');
				$form->hidden('link_comment');
				$form->hidden('medium')->value($medium);
				$form->hidden('bitrate')->value($bitrate);
				$form->hidden('duplex')->value(0);
				$form->hidden('wireless_ssid');
				$form->hidden('wireless_norm')->value($wnorm);
				$form->hidden('wireless_frequency');
				$form->hidden('wireless_channel');
				$form->hidden('wireless_channel_width');
				$form->hidden('wireless_polarization');

				$c_form->dropdown('connected_to')
					->style('width: 550px')
					->options($devices)
					->add_button('devices', 'add_simple', $device->user_id);
				
				$c_form->dropdown('connected_to_interface')
					->style('width: 550px')
					->options($ifaces)
					->callback(array($this, 'valid_connect_to_iface'))
					->add_button('ifaces');
			}

			$form->submit('Save');

			// form is validate
			if($form->validate())
			{
				$form_data = $form->as_array();

				$iface = new Iface_Model();

				try
				{
					$iface->transaction_start();

					$iface->name = $form_data['name'];
					$iface->type = $type;
					$iface->device_id = $device_id;
					$iface->comment = $form_data['comment'];
					
					if (!$add_button && Iface_Model::type_has_link($iface->type))
					{// connected iface
						$im_connect_to = new Iface_Model($form_data['connected_to_interface']);

						// save link
						if ($im_connect_to && $im_connect_to->id)
						{
							$roaming = new Link_Model();
							$link_id = $form_data['link_id'];
							$roaming_id = $roaming->get_roaming();
							$roaming = $roaming->find($roaming_id);
							$name = $form_data['link_name'];
							$medium = $form_data['medium'];

							// don not connect to roaming
							if ($link_id == $roaming_id)
							{
								$link_id = NULL;
								// fix name
								if (trim($name) == trim($roaming->name))
								{
									if ($iface->type == Iface_Model::TYPE_WIRELESS)
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
										if ($iface->type == Iface_Model::TYPE_WIRELESS)
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
							$lm->comment = htmlspecialchars($form_data['link_comment']);
							$lm->bitrate = network::str2bytes($form_data['bitrate']);
							$lm->duplex = ($form_data['duplex'] == 1);

							if ($iface->type == Iface_Model::TYPE_WIRELESS)
							{
								$lm->wireless_ssid = htmlspecialchars($form_data['wireless_ssid']);
								$lm->wireless_norm = intval($form_data['wireless_norm']);
								$lm->wireless_frequency = intval($form_data['wireless_frequency']);
								$lm->wireless_channel = intval($form_data['wireless_channel']);
								$lm->wireless_channel_width = intval($form_data['wireless_channel_width']);
								$lm->wireless_polarization = intval($form_data['wireless_polarization']);
							}

							$lm->save_throwable();

							// restrict count of connected devices to link
							$max = Link_Model::get_max_ifaces_count($iface->type);

							if ($lm->id != $roaming_id &&
								$max <= 2) // delete connected (port, eth)
							{
								foreach ($lm->ifaces as $i_del)
								{
									$i_del->link_id = null;
									$i_del->save_throwable();
								}
							}

							$iface->link_id = $lm->id;
							$im_connect_to->link_id = $lm->id;
							$im_connect_to->save_throwable();
						}
						else
						{
							$iface->link_id = null;
						}
					}
					else
					{
						$iface->link_id = null;
					}
					
					if (Iface_Model::type_has_mac_address($iface->type))
					{
						$iface->mac = empty($form_data['mac']) ? NULL : $form_data['mac'];
					}

					if ($iface->type == Iface_Model::TYPE_WIRELESS)
					{
						$iface->wireless_mode = $form_data['wireless_mode'];
						$iface->wireless_antenna = $form_data['wireless_antenna'];
					}
					else if ($iface->type === Iface_Model::TYPE_VIRTUAL_AP)
					{
						$parent_iface = new Iface_Model($form_data['parent_iface_id']);
						$iface->wireless_mode = $parent_iface->wireless_mode;
						$iface->wireless_antenna = $parent_iface->wireless_antenna;
					}
					else if ($iface->type == Iface_Model::TYPE_PORT)
					{
						$iface->number = $form_data['number'];
						$iface->port_mode = $form_data['port_mode'];
					}

					$iface->save_throwable();
					
					if ($iface->type == Iface_Model::TYPE_VIRTUAL_AP)
					{
						$iface_relationship = new Ifaces_relationship_Model();
						$iface_relationship->iface_id = $iface->id;
						$iface_relationship->parent_iface_id = $form_data['parent_iface_id'];
						$iface_relationship->save_throwable();
					}
					else if ($iface->type == Iface_Model::TYPE_VLAN)
					{
						$vlan_ifaces = new Ifaces_vlan_Model();
						$vlan_ifaces->iface_id = $iface->id;
						$vlan_ifaces->vlan_id = $form_data['vlan_id'];
						$vlan_ifaces->save_throwable();
						
						$iface_relationship = new Ifaces_relationship_Model();
						$iface_relationship->iface_id = $iface->id;
						$iface_relationship->parent_iface_id = $form_data['parent_iface_id'];
						$iface_relationship->save_throwable();
					}
					else if ($iface->type == Iface_Model::TYPE_BRIDGE)
					{
						if (is_array($form_data['children_interfaces']))
						{
							$children_ifaces = $form_data['children_interfaces'];
						}
						else
						{
							$children_ifaces = array();
						}
						
						foreach ($children_ifaces as $child)
						{
							$iface_relationship = new Ifaces_relationship_Model();
							$iface_relationship->iface_id = $child;
							$iface_relationship->parent_iface_id = $iface->id;
							$iface_relationship->save_throwable();
						}
					}
					else if ($iface->type == Iface_Model::TYPE_PORT)
					{
						$vlan_ifaces = new Ifaces_vlan_Model();
						
						if ($iface->port_mode == Iface_Model::PORT_MODE_TRUNK ||
							$iface->port_mode == Iface_Model::PORT_MODE_HYBRID)
						{
							foreach ((array) $form_data['tagged_vlan_id'] as $vlan_id)
							{
								if (intval($vlan_id))
								{
									$vlan_ifaces->clear();
									$vlan_ifaces->vlan_id = $vlan_id;
									$vlan_ifaces->iface_id = $iface->id;
									$vlan_ifaces->tagged = TRUE;
									$vlan_ifaces->port_vlan = ($vlan_id == $form_data['port_vlan_id']);
									$vlan_ifaces->save_throwable();
								}
							}
						}

						if ($iface->port_mode == Iface_Model::PORT_MODE_ACCESS ||
							$iface->port_mode == Iface_Model::PORT_MODE_HYBRID)
						{
							if (!is_array($form_data['untagged_vlan_id']))
							{
								$form_data['untagged_vlan_id'] = array($form_data['untagged_vlan_id']);
							}
							
							foreach ($form_data['untagged_vlan_id'] as $vlan_id)
							{
								if (intval($vlan_id))
								{
									$vlan_ifaces->clear();
									$vlan_ifaces->vlan_id = $vlan_id;
									$vlan_ifaces->iface_id = $iface->id;
									$vlan_ifaces->tagged = FALSE;
									$vlan_ifaces->port_vlan = ($vlan_id == $form_data['port_vlan_id']);
									$vlan_ifaces->save_throwable();
								}
							}
						}
					}

					$iface->transaction_commit();
					
					status::success('Interface has been successfully saved.');
					$this->redirect('ifaces/show/', $iface->id);
				}
				catch (Exception $e)
				{
					$iface->transaction_rollback();
					Log::add_exception($e);
					status::error('Error - Cannot add interface.');
				}
			}
			
			// speed units
			$arr_unit = array
			(
				'K'		=> 'kbps',
				'M'		=> 'Mbps',
				'G'		=> 'Gbps',
				'T'		=> 'Tbps'
			);
			// ethernet mediums
			$eth_mediums = Iface_Model::get_types_has_link_with_medium(Iface_Model::TYPE_ETHERNET);
			// wireless mediums
			$wl_mediums = Iface_Model::get_types_has_link_with_medium(Iface_Model::TYPE_WIRELESS);
			// port mediums
			$port_mediums = Iface_Model::get_types_has_link_with_medium(Iface_Model::TYPE_PORT);
			// wireless norms
			$arr_wireless_norms = Link_Model::get_wireless_norms();
			// wireless polarizations
			$arr_wireless_polarizations = Link_Model::get_wireless_polarizations();

			$headline = __('Add new interface') . ' (' . Iface_Model::get_type($type) . ')';
			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs;
			$view->title = $headline; 
			$view->content = new View('ifaces/add');
			$view->content->form = $form->html();
			$view->content->headline = $headline;
			$view->content->filter = $filter_form;
			$view->content->norms = $arr_wireless_norms;
			$view->content->polarizations = $arr_wireless_polarizations;
			$view->content->bit_units = $arr_unit;
			$view->content->eth_mediums = $eth_mediums;
			$view->content->wl_mediums = $wl_mediums;
			$view->content->port_mediums = $port_mediums;
			$view->render(TRUE);
		}
		else // redirect after setting of device and type
		{
			$devices = array
			(
				NULL => '----- '.__('Select device').' -----'
			) + ORM::factory('device')->select_list_device_with_user();
			
			$types = Iface_Model::get_types();
			$arr_types = array();
			
			if ($add_button && is_numeric($connect_type))
			{
				$conn_types = Iface_Model::get_can_connect_to($connect_type);
				
				foreach ($types as $i => $type)
				{
					if (in_array($i, $conn_types))
					{
						$arr_types[$i] = $type;
					}
				}
			}
			else
			{
				$arr_types = $types;
			}
			
			$types = array
			(
				NULL => '----- '.__('Select type').' -----'
			) + $arr_types;
			
			$form = new Forge();
			$form->set_attr('id', 'iface_add_mid-step_form');

			if (!is_numeric($device_id))
			{
				$form->dropdown('device_id')
					->label('Device')
					->options($devices)
					->rules('required')
					->style('width: 300px');
			}
			else
			{
				$form->hidden('device_id')
					->value($device_id);
			}

			$form->dropdown('type')
				->options($types)
				->rules('required')
				->style('width: 300px');
			
			if (!$add_button)
			{
				$form->submit('Choose');
			}
			
			if ($form->validate())
			{
				$form_data = $form->as_array();
				$this->redirect('ifaces/add/'.$form_data['device_id'].'/'.$form_data['type']);
			}
			else
			{
				$headline = __('Add new interface');
				$view = new View('main');
				$view->breadcrumbs = $breadcrumbs;
				$view->title = $headline;
				$view->content = new View('form');
				$view->content->form = $form->html();
				$view->content->headline = $headline;
				$view->render(TRUE);
			}
		}
	} // end of add
	
	/**
	 * Adds iface to a given link
	 * 
	 * @author Ondřej Fibich
	 * @param integer $link_id
	 */
	public function add_iface_to_link($link_id = NULL)
	{
		if (!intval($link_id))
		{
			self::warning(PARAMETER);
		}
		
		$link = new Link_Model($link_id);
		$iface = new Iface_Model();
		
		if (!$link || !$link->id)
		{
			self::error(RECORD);
		}
		
		if (!$this->acl_check_edit('Devices_Controller', 'iface'))
		{
			self::error(ACCESS);
		}
		
		$form = new Forge();
		
		$form->dropdown('iface_id')
				->label('Interface')
				->options($iface->select_list_grouped_by_device())
				->rules('required')
				->style('width:400px');
		
		$form->submit('Add');
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$iface->find($form_data['iface_id']);
			
			if ($iface->id)
			{
				$iface->link_id = $link->id;
				$iface->save();
				
				status::success('Interface has been successfully appended to link.');
				$this->redirect('/links/show/', $link->id);
			}
		}
		
		$headline = __('Append interface to link');
		
		$breadcrumbs = breadcrumbs::add()
				->link('links/show_all', 'Links',
						$this->acl_check_view('Devices_Controller', 'segment'))
				->link('links/show/' . $link->id, $link->name . ' (' .
						Link_Model::get_medium_type($link->medium) . ')',
						$this->acl_check_view('Devices_Controller', 'segment'))
				->disable_translation()
				->text($headline);
		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs;
		$view->title = $headline;
		$view->content = new View('form');
		$view->content->form = $form->html();
		$view->content->headline = $headline;
		$view->render(TRUE);
	}

	/**
	 * Function edits interface.
	 * 
	 * @param integer $iface_id
	 * @param integer $type
	 */
	public function edit($iface_id = null, $type = null)
	{
		// bad parameter
		if (!$iface_id || !is_numeric($iface_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$iface = new Iface_Model($iface_id);
		
		// iface doesn't exist
		if (!$iface->id)
		{
			Controller::error(RECORD);
		}
		
		$this->iface_type = 0;
		$this->iface_id = $iface->id;
		$this->device_id = $iface->device_id;
		$member_id = $iface->device->user->member_id;
		
		// access control
		if (!$this->acl_check_edit('Devices_Controller', 'iface', $member_id))
		{
			Controller::error(ACCESS);
		}
		
		if (empty($type))
		{
			$type = $iface->type;
			
			// some wrong added ifaces from the past may have type set to zero
		}
		
		// iface type doesn't exist
		if (!$iface->get_type($type))
		{
			// some wrong added ifaces from the past may have type set to zero
			if ($type == 0)
			{
				$type = Iface_Model::TYPE_WIRELESS;
			}
			else
			{
				Controller::error(RECORD);
			}
		}
		// some wrong added ifaces from the past may have type set to zero
		
		
		$this->iface_type = $type;
		
		$filter_form = Devices_Controller::device_filter_form();

		$form = new Forge(url_lang::base() . url_lang::current() . '?sended=1');

		$base_form = $form->group('Basic data');

		$base_form->dropdown('itype')
			->label('Type')
			->options(Iface_Model::get_types())
			->style('width:620px')
			->selected($type);

		$base_form->input('name')
			->label('Interface name')
			->rules('length[3,250]')
			->style('width:620px')
			->value($iface->name);

		// print mac address field only if it can have mac address
		if (Iface_Model::type_has_mac_address($type))
		{
			$base_form->input('mac')
				->label('MAC')
				->rules('valid_mac_address')
				->style('width:620px')
				->value($iface->mac);
		}

		$base_form->textarea('comment')
			->rules('length[0,254]')
			->cols('20')
			->rows('5')
			->style('width: 620px')
			->value($iface->comment);

		// print parent iface field only if type is VLAN
		if ($type == Iface_Model::TYPE_VLAN)
		{
			$parent_ifaces = array();

			foreach ($iface->device->ifaces as $i)
			{
				// limit options only if type is given
				if (in_array($i->type, Iface_Model::get_can_be_child_of($type)) &&
					$i->id != $iface->id)
				{
					$parent_ifaces[$i->id] = $i->name . ' - ' . $i->device->name;
				}
			}
			
			$vlan_id = null;
			
			if ($iface->ifaces_vlans->count())
			{
				$vlan_id = $iface->ifaces_vlans->current()->vlan_id;
			}
			
			$parent_iface_id = null;
			
			if ($iface->ifaces_relationships->count())
			{
				$parent_iface_id = $iface->ifaces_relationships->current()->parent_iface_id;
			}

			$parent_ifaces = array
			(
				NULL => '------- '.__('Select interface').' -------'
			) + $parent_ifaces;

			$vlans = array
			(
				NULL => '------- '.__('Select VLAN').' -------'
			) + ORM::factory('vlan')->select_list();

			$vlan_form = $form->group('VLAN setting');

			$vlan_form->dropdown('vlan_id')
				->label('VLAN')
				->options($vlans)
				->selected($vlan_id)
				->add_button('vlans')
				->style('width:200px');

			$vlan_form->dropdown('parent_iface_id')
				->label('Parent interface')
				->options($parent_ifaces)
				->selected($parent_iface_id)
				->style('width:200px');
		}

		// print children ifaces field only if type is bridge
		if ($type == Iface_Model::TYPE_BRIDGE)
		{
			$child_ifaces = array();

			foreach ($iface->device->ifaces as $i)
			{
				// limit options only if type is given
				if (in_array($type, Iface_Model::get_can_be_child_of($i->type)))
				{
					$mac = ($i->mac) ? $i->mac . ' - ' : '';
					$child_ifaces[$i->id] = $mac . $i->name;
				}
			}
			
			$bridged_childres = array();
			$bridged_childrens = ORM::factory('ifaces_vlan')->get_all_bridged_ifaces_of_iface($iface->id);
			
			foreach ($bridged_childrens as $child)
			{
				$bridged_childres[] = $child->id;
			}

			$bridge_form = $form->group('Bridge settings');

			$bridge_form->dropdown('children_interfaces[]')
				->multiple('multiple')
				->options($child_ifaces)
				->selected($bridged_childres)
				->size(15)
				->label('Children interfaces');
		}

		// print port fields only if type is port
		if ($type == Iface_Model::TYPE_PORT)
		{
            $modes = array
            (
                NULL => '------- '.__('Select mode').' -------'
            ) + Iface_Model::get_port_modes();

            $vlan_model = new Vlan_Model();
            $arr_vlans = $vlan_model->select_list();

            $default_vlans = array
            (
                NULL => '----- ' . __('Select VLAN') . ' -----'
            ) + $arr_vlans;
            
            $vlans = ORM::factory('ifaces_vlan')->get_all_vlans_of_iface($iface->id);
            $default_vlan_id = null;
            $tagged_vlans_ids = array();
            $untagged_vlans_ids = array();
            
            foreach ($vlans as $vlan)
            {
                if ($vlan->tagged)
                {
                    $tagged_vlans_ids[] = $vlan->id;
                }
                else
                {
                    $untagged_vlans_ids[] = $vlan->id;
                }
                
                if ($vlan->port_vlan)
                {
                    $default_vlan_id = $vlan->id;
                }
            }
                
			$port_form = $form->group('Port setting');

			$port_form->input('number')
				->class('increase_decrease_buttons')
				->value($iface->number)
				->callback(array($this, 'valid_port_nr'));
			
			$vlan_port_form = $form->group('Port VLAN setting');

			$vlan_port_form->dropdown('port_mode')
				->options($modes)
                ->rules('required')
				->style('width:200px')
				->selected($iface->port_mode);

            $vlan_port_form->dropdown('port_vlan_id')
                ->options($default_vlans)
                ->selected($default_vlan_id)
                ->rules('required')
                ->label('Port VLAN')
                ->help('port_vlan')
                ->callback(array($this, 'valid_port_vlan'))
                ->style('width:200px')
                ->add_button('vlans');

            $vlan_port_form->dropdown('tagged_vlan_id[]')
                ->label('Tagged VLANs')
                ->options($arr_vlans)
                ->selected($tagged_vlans_ids)
                ->multiple('multiple')
                ->size(10)
                ->add_button('vlans');

            $vlan_port_form->dropdown('untagged_vlan_id[]')
                ->label('Untagged VLANs')
                ->options($arr_vlans)
                ->selected($untagged_vlans_ids)
                ->callback(array($this, 'valid_vlans'))
                ->multiple('multiple')
                ->size(10)
                ->add_button('vlans');
		}

		// print wireless fields only if type is wireless
		if ($type == Iface_Model::TYPE_WIRELESS)
		{
			$modes = array
			(
				NULL => '----- '.__('Select mode').' -----'
			) + Iface_Model::get_wireless_modes();

			$antennas = array
			(
				NULL => '----- '.__('Select antenna').' -----'
			) + Iface_Model::get_wireless_antennas();

			$w_form = $form->group('Wireless setting');

			$w_form->dropdown('wireless_mode')
				->label('Mode')
				->rules('required')
				->options($modes)
				->callback(array($this, 'valid_mode'))
				->style('width:200px')
				->selected($iface->wireless_mode);

			$w_form->dropdown('wireless_antenna')
				->label('Antenna')
				->options($antennas)
				->style('width:200px')
				->selected($iface->wireless_antenna);
		}
		
		// print virtual AP fields only if type is virtual AP
		if ($type == Iface_Model::TYPE_VIRTUAL_AP)
		{
			$parent_ifaces = array();

			foreach ($iface->device->ifaces as $i)
			{
				// limit options only if type is given
				if (in_array($i->type, Iface_Model::get_can_be_child_of($type)) &&
					$i->wireless_mode == Iface_Model::WIRELESS_MODE_AP &&
					$i->id != $iface->id)
				{
					$parent_ifaces[$i->id] = $i->name . ' - ' . $i->device->name;
				}
			}

			$parent_ifaces = array
			(
				NULL => '------- '.__('Select interface').' -------'
			) + $parent_ifaces;
			
			$parent_iface_id = null;
			
			if ($iface->ifaces_relationships->count())
			{
				$parent_iface_id = $iface->ifaces_relationships->current()->parent_iface_id;
			}

			$vap_form = $form->group('Virtual AP setting');

			$vap_form->dropdown('parent_iface_id')
				->label('Parent interface')
				->options($parent_ifaces)
				->selected($parent_iface_id)
				->rules('required')
				->callback(array($this, 'valid_mode_virtual_ap'))
				->style('width:200px');
		}

		// print link field only if type can has link
		if (Iface_Model::type_has_link($type))
		{
			$devices = array
			(
				NULL => '----- '.__('Select device').' -----'
			) + ORM::factory('device')->select_list_device_with_user();

			$ifaces = array
			(
				NULL => '----- '.__('Select interface').' -----'
			); // empty - loaded by JS

			$links = array
			(
				NULL => '----- '.__('Select link').' -----'
			) + ORM::factory('link')->select_list_by_iface_type($type);
			
			$connecte_to_iface_id = null;
			$connecte_to_iface_device_id = null;
			
			if ($iface->link_id)
			{
				$connecte_to_iface = $iface->get_iface_connected_to_iface();
				
				if ($connecte_to_iface && $connecte_to_iface->id)
				{
					$connecte_to_iface_id = $connecte_to_iface->id;
					$connecte_to_iface_device_id = $connecte_to_iface->device_id;
				}
			}

			if ($connecte_to_iface_id)
			{
				$concat = 'CONCAT(IFNULL(mac, \'- \'),\': \',IFNULL(name,\'\'))';

				$ifaces += ORM::factory('iface')
					->where('device_id', $connecte_to_iface_device_id)
					->in('type', Iface_Model::get_can_connect_to($type))
					->select_list('id', $concat);
			}

			$c_form = $form->group(
					__('Link') . '&nbsp;&nbsp;&nbsp;' .
					form::checkbox('change_link', 1, isset($_POST['change_link']), 'class="checkbox"') .
					'&nbsp;&nbsp;<span style="color: black">' .
					__('Change connected to (link) settings?') .
					'</span>'
			);
			
			$form->hidden('device_filter');			
			$form->hidden('_device_name')->value($iface->device->name);
			$form->hidden('link_id')->value($iface->link_id);
			$form->hidden('link_name')->value($iface->link->name);
			$form->hidden('link_comment')->value($iface->link->comment);
			$form->hidden('medium')->value($iface->link->medium);
			$form->hidden('bitrate')->value($iface->link->bitrate);
			$form->hidden('duplex')->value($iface->link->duplex);
			$form->hidden('wireless_ssid')->value($iface->link->wireless_ssid);
			$form->hidden('wireless_norm')->value($iface->link->wireless_norm);
			$form->hidden('wireless_frequency')->value($iface->link->wireless_frequency);
			$form->hidden('wireless_channel')->value($iface->link->wireless_channel);
			$form->hidden('wireless_channel_width')->value($iface->link->wireless_channel_width);
			$form->hidden('wireless_polarization')->value($iface->link->wireless_polarization);

			$c_form->dropdown('connected_to')
				->style('width: 550px')
				->options($devices)
				->selected($connecte_to_iface_device_id)
				->add_button('devices', 'add_simple', $iface->device->user_id);

			$c_form->dropdown('connected_to_interface')
				->style('width: 550px')
				->options($ifaces)
				->callback(array($this, 'valid_connect_to_iface'))
				->selected($connecte_to_iface_id)
				->add_button('ifaces');
		}

		$form->submit('Save');

		// form is validate
		if($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				$iface->transaction_start();
				
				$old_type = $iface->type;

				$iface->name = $form_data['name'];
				$iface->type = $form_data['itype'];
				$iface->comment = $form_data['comment'];

				if (Iface_Model::type_has_link($iface->type))
				{// connected iface
					$im_connect_to = new Iface_Model($form_data['connected_to_interface']);

					// save link
					if ($im_connect_to && $im_connect_to->id &&
						isset($_POST['change_link']) && $_POST['change_link'])
					{
						$roaming = new Link_Model();
						$link_id = $form_data['link_id'];
						$roaming_id = $roaming->get_roaming();
						$roaming = $roaming->find($roaming_id);
						$name = $form_data['link_name'];
						$medium = $form_data['medium'];

						// don not connect to roaming
						if ($link_id == $roaming_id)
						{
							$link_id = NULL;
							// fix name
							if (trim($name) == trim($roaming->name))
							{
								if ($iface->type == Iface_Model::TYPE_WIRELESS)
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
									$name .= ' - ' . $iface->device->name;
								}
								else
								{
									$name .= $iface->device->name . ' - ';
									$name .= $im_connect_to->device->name;
								}

								// fix medium
								if ($medium == Link_Model::MEDIUM_ROAMING)
								{
									if ($iface->type == Iface_Model::TYPE_WIRELESS)
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
						$lm->comment = htmlspecialchars($form_data['link_comment']);
						$lm->bitrate = network::str2bytes($form_data['bitrate']);
						$lm->duplex = ($form_data['duplex'] == 1);

						if ($iface->type == Iface_Model::TYPE_WIRELESS)
						{
							$lm->wireless_ssid = htmlspecialchars($form_data['wireless_ssid']);
							$lm->wireless_norm = intval($form_data['wireless_norm']);
							$lm->wireless_frequency = intval($form_data['wireless_frequency']);
							$lm->wireless_channel = intval($form_data['wireless_channel']);
							$lm->wireless_channel_width = intval($form_data['wireless_channel_width']);
							$lm->wireless_polarization = intval($form_data['wireless_polarization']);
						}

						$lm->save_throwable();

						// restrict count of connected devices to link
						$max = Link_Model::get_max_ifaces_count($iface->type);

						if ($lm->id != $roaming_id &&
							$max <= 2) // delete connected (port, eth)
						{
							foreach ($lm->ifaces as $i_del)
							{
								$i_del->link_id = null;
								$i_del->save_throwable();
							}
						}

						$iface->link_id = $lm->id;
						$im_connect_to->link_id = $lm->id;
						$im_connect_to->save_throwable();
					}
					// disconnect
					else if (isset($_POST['change_link']) && $_POST['change_link'])
					{
						$iface->link_id = null;
					}
					// do not changes link commonly, but if type changed we must
					// look if the current link can be used on new type
					// this code resolves (#310)
					else if ($old_type != $iface->type)
					{
						$mediums = $iface->get_types_has_link_with_medium($iface->type);
						
						if ($iface->link_id && 
							!array_key_exists($iface->link->medium, $mediums))
						{
							$iface->link_id = null;
						}
					}
				}
				else
				{
					$iface->link_id = null;
				}

				if (Iface_Model::type_has_mac_address($iface->type))
				{
					$iface->mac = empty($form_data['mac']) ? NULL : $form_data['mac'];
				}
				else
				{
					$iface->mac = null;
				}
				
				$iface->wireless_mode = null;
				$iface->wireless_antenna = null;
				$iface->number = null;
				$iface->port_mode = null;

				if ($iface->type == Iface_Model::TYPE_WIRELESS)
				{
					$iface->wireless_mode = $form_data['wireless_mode'];
					$iface->wireless_antenna = $form_data['wireless_antenna'];
				}
				else if ($iface->type === Iface_Model::TYPE_VIRTUAL_AP)
				{
					$parent_iface = new Iface_Model($form_data['parent_iface_id']);
					$iface->wireless_mode = $parent_iface->wireless_mode;
					$iface->wireless_antenna = $parent_iface->wireless_antenna;
				
					// delete current
					foreach ($iface->ifaces_relationships as $i)
					{
						$i->delete_throwable();
					}
					
					$iface_relationship = new Ifaces_relationship_Model();
					$iface_relationship->iface_id = $iface->id;
					$iface_relationship->parent_iface_id = $form_data['parent_iface_id'];
					$iface_relationship->save_throwable();
				}
				else if ($iface->type == Iface_Model::TYPE_PORT)
				{
					$iface->number = $form_data['number'];
					$iface->port_mode = $form_data['port_mode'];
                    
                    // vlan relations
                    $vlan_ifaces = new Ifaces_vlan_Model();
                    // remove current
                    $vlan_ifaces->delete_relation_vlans_to_iface($iface->id);

                    if ($iface->port_mode == Iface_Model::PORT_MODE_TRUNK ||
                        $iface->port_mode == Iface_Model::PORT_MODE_HYBRID)
                    {
                        foreach ((array) $form_data['tagged_vlan_id'] as $vlan_id)
                        {
                            if (intval($vlan_id))
                            {
                                $vlan_ifaces->clear();
                                $vlan_ifaces->vlan_id = $vlan_id;
                                $vlan_ifaces->iface_id = $iface->id;
                                $vlan_ifaces->tagged = TRUE;
                                $vlan_ifaces->port_vlan = ($vlan_id == $form_data['port_vlan_id']);
                                $vlan_ifaces->save_throwable();
                            }
                        }
                    }

                    if ($iface->port_mode == Iface_Model::PORT_MODE_ACCESS ||
                        $iface->port_mode == Iface_Model::PORT_MODE_HYBRID)
                    {
                        foreach ((array) $form_data['untagged_vlan_id'] as $vlan_id)
                        {
                            if (intval($vlan_id))
                            {
                                $vlan_ifaces->clear();
                                $vlan_ifaces->vlan_id = $vlan_id;
                                $vlan_ifaces->iface_id = $iface->id;
                                $vlan_ifaces->tagged = FALSE;
                                $vlan_ifaces->port_vlan = ($vlan_id == $form_data['port_vlan_id']);
                                $vlan_ifaces->save_throwable();
                            }
                        }
                    }
				}
				else if ($iface->type == Iface_Model::TYPE_VLAN)
				{
					// delete current
					foreach ($iface->ifaces_vlans as $iv)
					{
						$iv->delete_throwable();
					}
					
					$vlan_ifaces = new Ifaces_vlan_Model();
					$vlan_ifaces->iface_id = $iface->id;
					$vlan_ifaces->vlan_id = $form_data['vlan_id'];
					$vlan_ifaces->save_throwable();
					
					// delete current
					foreach ($iface->ifaces_relationships as $i)
					{
						$i->delete_throwable();
					}

					$iface_relationship = new Ifaces_relationship_Model();
					$iface_relationship->iface_id = $iface->id;
					$iface_relationship->parent_iface_id = $form_data['parent_iface_id'];
					$iface_relationship->save_throwable();
				}
				else if ($iface->type == Iface_Model::TYPE_BRIDGE)
				{
					$iface_relationship = new Ifaces_relationship_Model();
					// delete current
					$iface_relationship->delete_bridge_relationships_of_iface($iface->id);
					
					if (is_array($form_data['children_interfaces']))
					{
						$children_ifaces = $form_data['children_interfaces'];
					}
					else
					{
						$children_ifaces = array();
					}
					
					foreach ($children_ifaces as $child)
					{
						$iface_relationship->clear();
						$iface_relationship->iface_id = $child;
						$iface_relationship->parent_iface_id = $iface->id;
						$iface_relationship->save_throwable();
					}
				}

				$iface->save_throwable();

				$iface->transaction_commit();

				status::success('Interface has been successfully saved.');
				$this->redirect('show', $iface->id);
			}
			catch (Exception $e)
			{
				$iface->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot edit interface');
			}
		}
		
		$name = strval($iface);
		
		if (url::slice(url_lang::uri(Path::instance()->previous()),0,1) != 'ifaces')
		{
			$breadcrumbs = breadcrumbs::add()
				->link(
					'members/show_all', 'Members',
					$this->acl_check_view('Members_Controller','members')
				)
				->link(
					'members/show/'.$iface->device->user->member_id,
					'ID '.$iface->device->user->member->id.
					' - '.$iface->device->user->member->name,
					$this->acl_check_view(
						'Members_Controller', 'members',
						$iface->device->user->member_id
					)
				)
				->link(
					'users/show_by_member/'.$iface->device->user->member_id,
					'Users', $this->acl_check_view(
						'Users_Controller', 'users',
                        $iface->device->user->member_id
					)
				)
				->link(
					'users/show/'.$iface->device->user_id,
					$iface->device->user->get_full_name(),
					$this->acl_check_view(
						'Users_Controller', 'users',
						$iface->device->user->member_id
					)
				)
				->link(
					'devices/show_by_user/'.$iface->device->user_id,
					'Devices', $this->acl_check_view(
						'Devices_Controller', 'devices',
						$iface->device->user->member_id
					)
				)
				->link(
					'devices/show/'.$iface->device_id,
					$iface->device->name, $this->acl_check_view(
						'Devices_Controller', 'devices',
						$iface->device->user->member_id
					)
				)
				->link(
					'devices/show_iface/'.$iface->id,
					$name, $this->acl_check_view(
						'Devices_Controller', 'iface',
						$iface->device->user->member->id
					)
				)->text('Edit')
				->html();
		}
		else
		{
			$breadcrumbs = breadcrumbs::add()
				->link(
					'ifaces/show_all',
					'Interfaces',
					$this->acl_check_view('Devices_Controller','iface')
				)->link(
					'ifaces/show/'.$iface->id, $name,
					$this->acl_check_view(
						'Devices_Controller', 'iface',
						$iface->device->user->member->id
					)
				)->text('Edit')
				->html();
		}
		
		// speed units
		$arr_unit = array
		(
			'K'		=> 'kbps',
			'M'		=> 'Mbps',
			'G'		=> 'Gbps',
			'T'		=> 'Tbps'
		);
		// ethernet mediums
		$eth_mediums = Iface_Model::get_types_has_link_with_medium(Iface_Model::TYPE_ETHERNET);
		// wireless mediums
		$wl_mediums = Iface_Model::get_types_has_link_with_medium(Iface_Model::TYPE_WIRELESS);
		// port mediums
		$port_mediums = Iface_Model::get_types_has_link_with_medium(Iface_Model::TYPE_PORT);
		// wireless norms
		$arr_wireless_norms = Link_Model::get_wireless_norms();
		// wireless polarizations
		$arr_wireless_polarizations = Link_Model::get_wireless_polarizations();

		$headline = __('Edit interface');
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs;
		$view->title = $headline; 
		$view->content = new View('ifaces/add');
		$view->content->form = $form->html();
		$view->content->headline = $headline;
		$view->content->filter = $filter_form;
		$view->content->norms = $arr_wireless_norms;
		$view->content->polarizations = $arr_wireless_polarizations;
		$view->content->bit_units = $arr_unit;
		$view->content->eth_mediums = $eth_mediums;
		$view->content->wl_mediums = $wl_mediums;
		$view->content->port_mediums = $port_mediums;
		
		// additional infor for AP and reamings
		$roaming_id = ORM::factory('link')->get_roaming();
		
		if ($iface->link->id == $roaming_id || (
				$iface->type == Iface_Model::TYPE_WIRELESS &&
				$iface->wireless_mode == Iface_Model::WIRELESS_MODE_AP
			))
		{
			$view->content->additional_info = 
					__('This interface is connected to multiple devices.') . ' ' . 
					__('The connected to information in the link section contains only one of these devices.');
		}
			
		$view->render(TRUE);
	} // end of edit

	/**
	 * Function deletes interface.
	 * Interface is deleted with all of its ip addresses and vlan interfaces
	 * thanks to database cascade deleting.
	 * 
	 * @param integer $iface_id id of interface to delete
	 */
	public function delete($iface_id = null)
	{
		// bad parameter
		if (!$iface_id || !is_numeric($iface_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$iface = new Iface_Model($iface_id);
		
		// iface doesn't exist
		if (!$iface->id)
		{
			Controller::error(RECORD);
		}
		
		$member_id = $iface->device->user->member_id;
		
		// access control
		if (!$this->acl_check_delete('Devices_Controller', 'iface', $member_id))
		{
			Controller::error(ACCESS);
		}
		
		// find ip addresses of interface, in this relation 1:n ORM works
		$ips = $iface->ip_addresses;
		
		// find vlan_ifaces
		$vlan_ifaces = $iface->ifaces_vlans;
		
		if (count($ips) == 0)
		{
			if ($iface->delete())
			{
				status::success('Interface has been successfully deleted.');
			}
			else
			{
				status::error('Error - cant delete interface.');
			}
		}
		else
		{
			status::warning('Interface still has at least one IP address.');
		}
		
		if (url::slice(url_lang::uri(Path::instance()->previous()), 0, 2) == 'ifaces/show')
		{
			url::redirect('ifaces/show_all');
		}
		else
		{
			url::redirect(Path::instance()->previous());
		}
	}
	
	/**
	 * Function removes iface from bridge
	 * 
	 * @param integer $bridge_iface_id
	 * @param integer $iface_id
	 */
	public function remove_from_bridge($bridge_iface_id = null, $iface_id = null)
	{
		// bad parameter
		if (!$bridge_iface_id || !is_numeric($bridge_iface_id) || !$iface_id || !is_numeric($iface_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$iface = new Iface_Model($bridge_iface_id);
		
		$member_id = $iface->device->user->member_id;
		
		// access control
		if (!$this->acl_check_delete('Devices_Controller', 'iface', $member_id))
		{
			Controller::error(ACCESS);
		}
		
		$sub_iface = new Iface_Model($iface_id);
		
		// iface doesn't exist
		if (!$iface->id || !$sub_iface->id)
		{
			Controller::error(RECORD);
		}
		
		//remove iface from bridge
		$iv = new Ifaces_vlan_Model();

		$delete_state = $iv->remove_iface_from_bridge($bridge_iface_id, $iface_id);
		
		if ($delete_state)
		{
			status::success('Interface has been successfully removed from bridge.');
		}
		else
		{
			status::error('Error - cant remove interface.');
		}

		$this->redirect(Path::instance()->previous());
	}
	
	/**
	 * Function removes iface from link
	 * 
	 * @author David Raska
	 * @param integer $iface_id
	 */
	public function remove_from_link($iface_id = null)
	{
		// bad parameter
		if (!$iface_id || !is_numeric($iface_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$iface = new Iface_Model($iface_id);
		
		$link_id = $iface->link_id;
		
		$member_id = $iface->device->user->member_id;

		// access control
		if (!$this->acl_check_delete('Devices_Controller', 'iface', $member_id))
		{
			Controller::error(ACCESS);
		}
		
		// iface doesn't exist
		if (!$iface->id)
		{
			Controller::error(RECORD);
		}
		
		$redirect_to_link = TRUE;
		
		try
		{
			$link = NULL;
			
			if ($iface->link->ifaces->count() == 1)
			{
				$link = $iface->link;
			}
			
			$iface->link_id = NULL;
			$iface->save_throwable();
			
			if ($link)
			{
				$link->delete_throwable();
				$redirect_to_link = FALSE;
			}
			
			status::success('Interface has been successfully removed from link.');
		}
		catch (Exception $e)
		{
			status::error('Error - cant remove interface.');
		}

		if ($redirect_to_link)
		{
			$this->redirect('links/show/', $link_id);
		}
		else
		{
			$this->redirect('links/show_all/');
		}
	}
	
	/**
	 * Callback function to validate wireless mode
	 * 
	 * @author Michal Kliment
	 * @param Form_Field $input 
	 */
	public function valid_mode($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}

		if ($this->input->post('itype') == Iface_Model::TYPE_WIRELESS)
		{			
			if (trim($input->value) == '')
			{
				$input->add_error('required', __('Mode is required.'));
			}
			// one iface with AP mode in link
			else if ($input->value == Iface_Model::WIRELESS_MODE_AP)
			{
				$count = ORM::factory('iface')->count_items_by_mode_and_link(
						Iface_Model::WIRELESS_MODE_AP, $this->input->post('link_id'),
						$this->iface_id
				);

				if ($count > 0)
				{
					$input->add_error('required', __(
							'In this link an interface in mode AP already exists.'
					));
				}
			}
			// relationship with any Virtual AP during edit?
			else if ($this->iface_id &&
					 $input->value == Iface_Model::WIRELESS_MODE_CLIENT)
			{
				if (count(ORM::factory('iface')->get_virtual_ap_ifaces_of_parent($this->iface_id)))
				{
					$input->add_error('required', __(
							'Cannot change mode of the interface to client ' .
							'a Virtual AP is derived from this interface.'
					));
				}
			}
		}
	}
	
	/**
	 * Callback function to validate vlan ap wmode
	 * 
	 * @author Ondřej Fibich
	 * @param Form_Field $input 
	 */
	public function valid_mode_virtual_ap($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}

		if ($this->input->post('itype') == Iface_Model::TYPE_VIRTUAL_AP)
		{
			$count = ORM::factory('iface')->count_items_by_mode_and_link(
					Iface_Model::WIRELESS_MODE_AP, $this->input->post('link_id'),
					$this->iface_id
			);
			
			if ($count > 0)
			{
				$input->add_error('required', __(
						'In this link an interface in mode AP already exists.'
				));
			}
		}
	}
	
	/**
	 * Callback function to validate tagged and untagged VLANs
	 * 
	 * @author Michal Kliment
	 * @param Form_Field $input 
	 */
	public function valid_vlans($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$tagged_vlans = (array) @$_POST['tagged_vlan_id'];
		$untagged_vlans = (array) @$_POST['untagged_vlan_id'];
		
		// VLAN can be only tagged or untagged
		if (count(array_intersect($untagged_vlans, $tagged_vlans)) > 0)
		{
			$input->add_error('required', __('VLAN can be only tagged or untagged.'));
		}
	}
	
	/**
	 * Callback function to validate port VLAN
	 * 
	 * @author Michal Kliment
	 * @param Form_Field $input 
	 */
	public function valid_port_vlan($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$port_vlan_id = $input->value;
		$tagged_vlans = (array) @$_POST['tagged_vlan_id'];
		$untagged_vlans = (array) @$_POST['untagged_vlan_id'];
		$port_mode = $this->input->post('port_mode');
		
		switch($port_mode)
		{
			// port is in access mode
			case Iface_Model::PORT_MODE_ACCESS:
				
				if (!in_array($port_vlan_id, $untagged_vlans))
				{
					$input->add_error('required', __('Port VLAN has to be in untagged VLANs.'));
				}
				break;
				
			// port is in trunk mode
			case Iface_Model::PORT_MODE_TRUNK:
				
				if (!in_array($port_vlan_id, $tagged_vlans))
				{
					$input->add_error('required', __('Port VLAN has to be in tagged VLANs.'));
				}
				
				break;
				
			// port is in hybrid mode
			case Iface_Model::PORT_MODE_HYBRID:
				
				if (!in_array($port_vlan_id, $untagged_vlans))
				{
					$input->add_error('required', __('Port VLAN has to be in untagged VLANs.'));
				}
				
				break;
		}
	}
	
	/**
	 * Callback function to validate number of port
	 * 
	 * @author Michal Kliment
	 * @param Form_Field $input 
	 */
	public function valid_port_nr($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$value = trim($input->value);
		$device = new Device_Model($this->device_id);
		
		if (!empty($value) && $device && $device->id)
		{
			if ($device->port_number_exists($value, $device->id, $this->iface_id))
			{
				$input->add_error('required', __('Port number already exist.'));
			}
		}
	}
	
	/**
	 * Callback function to validate connect to interface
	 * 
	 * @param Form_Field $input
	 */
	public function valid_connect_to_iface($input = NULL)
	{
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if ($input->value)
		{
			$iface_to = new Iface_Model($input->value);
			$link_id = $this->input->post('link_id');

			if ($iface_to && $iface_to->id &&
				$iface_to->link_id &&
				$iface_to->link_id != $link_id)
			{
				$input->add_error('required', __(
						'Selected interface for connecting to is already ' .
						'connected throught different link - ' .
						'<a href="%s/ifaces/remove_from_link/%s" id="remove_from_link">Remove from link</a>',
						array(Settings::get('suffix').Config::get('lang'), $iface_to->id)
				));
			}
		}
	}
	
}
