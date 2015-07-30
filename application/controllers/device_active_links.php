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
 * Controller performs device active links actions.
 *  
 * @package Controller
 */
class Device_active_links_Controller extends Controller
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
		url::redirect('device_active_links/show_all');
	}
		
	/**
	 * Function shows all device active_links.
	 * 
	 * @param integer $limit_results devices per page
	 * @param string $order_by sorting column
	 * @param string $order_by_direction sorting direction
	 */
	public function show_all($limit_results = 50, $order_by = 'device_id',
			$order_by_direction = 'ASC', $page_word = null, $page = 1)
	{	
		// access control
		if (!$this->acl_check_view('Device_active_links_Controller', 'active_links'))
			Controller::error(ACCESS);
		
		$filter_form = new Filter_form('dal');
		
		$filter_form->add('url_pattern')
				->label('URL pattern');
		
		$filter_form->add('name');
		
		$filter_form->add('title')
				->label('Link title');
		
		$filter_form->add('show_in_user_grid')
				->type('select')
				->values(arr::bool());
		
		$filter_form->add('show_in_grid')
				->type('select')
				->values(arr::bool());
		
		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		$device_active_link_model = new Device_active_link_Model;		
		$total_active_links = $device_active_link_model->count_all_active_links($filter_form->as_sql());
		
		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_active_links)
			$sql_offset = 0;	
		
		// query
		$active_links = $device_active_link_model->get_all_active_links(array
		(
			'offset'					=> $sql_offset,
			'limit'						=> (int) $limit_results,
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'filter_sql'				=> $filter_form->as_sql()
		));
		
		// headline
		$headline = __('Device active links');
		
		// grid of devices
		$grid = new Grid('device_active_links', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang'). '/device_active_links/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_active_links,
			'items_per_page' 			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		if ($this->acl_check_new('Device_active_links_Controller', 'active_links'))
		{
			$grid->add_new_button('device_active_links/add', 'Add new device active link');
		}
		
		$grid->order_field('id')
				->label('ID')
				->class('center');
		
		$grid->order_field('url_pattern')
				->label('URL pattern');
		
		$grid->order_field('name')
				->label('Name');
		
		$grid->order_field('title')
				->label('Link title');
		
		$grid->order_callback_field('devices_count')
				->class('center');
		
		$grid->order_callback_field('as_form')
				->label('Send as form')
				->callback('callback::enabled_field', '')
				->class('center');
		
		$grid->order_callback_field('show_in_user_grid')
				->callback('callback::enabled_field', '')
				->class('center');
		
		$grid->order_callback_field('show_in_grid')
				->callback('callback::enabled_field', '')
				->class('center');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Device_active_links_Controller', 'active_links'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('device_active_links/show');
		}
		
		if ($this->acl_check_edit('Device_active_links_Controller', 'active_links'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('device_active_links/edit');
		}
			
		if ($this->acl_check_delete('Device_active_links_Controller', 'active_links'))
		{	
			$actions->add_action('id')
					->icon_action('delete')
					->url('device_active_links/delete')
					->class('delete_link');
		}
			
		$grid->datasource($active_links);
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $headline;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	} // end of show_all function
	
	/**
	 * Function shows device action link.
	 * 
	 * @param integer $device_id
	 */
	public function show($active_link_id = null)
	{	
		if (!$this->acl_check_view('Device_active_links_Controller', 'active_links'))
		{
			Controller::error(ACCESS);
		}
		
		if (!isset($active_link_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$active_link = new Device_active_link_Model($active_link_id);
		
		if ($active_link->id == 0)
		{
			Controller::error(RECORD);
		}
		
		$devices = $active_link->get_active_link_devices();
		$device_templates = $active_link->get_active_link_devices(NULL,
				Device_active_link_Model::TYPE_TEMPLATE
		);
		
		$devices_grid = new Grid('devices', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> $devices->count()
		));
		
		$devices_grid->Field('id')
				->label('ID');
		
		$devices_grid->Field('name');
		
		if ($this->acl_check_view('Devices_Controller', 'devices'))
		{
			$actions = $devices_grid->grouped_action_field();
		
			$actions->add_conditional_action('id')
					->icon_action('show')
					->url('devices/show');
		}
		
		$devices_grid->datasource($devices);
		
		$device_templates_grid = new Grid('device_templaes', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> $device_templates->count()
		));
		
		$device_templates_grid->Field('id')
				->label('ID');
		
		$device_templates_grid->Field('name');
		
		if ($this->acl_check_view('Device_templates_Controller', 'device_template'))
		{
			$actions = $device_templates_grid->grouped_action_field();
		
			$actions->add_conditional_action('id')
					->icon_action('show')
					->url('device_templates/show');
		}
		
		$device_templates_grid->datasource($device_templates);
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('device_active_links/show_all', 'Device active links',
						$this->acl_check_view('Device_active_links_Controller','active_links'))
				->disable_translation()
				->text('ID ' . $active_link->id . ' - ' .
						(!$active_link->name ? $active_link->title : $active_link->name));
		
		// view
		$view = new View('main');
		$view->title = __('Device active link').' '.
					(!$active_link->name ? $active_link->title : $active_link->name);
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('device_active_links/show');
		$view->content->active_link = $active_link;
		$view->content->devices_grid = $devices_grid;
		$view->content->device_templates_grid = $device_templates_grid;
		$view->content->headline = __('Device active link').' '.
					(!$active_link->name ? $active_link->title : $active_link->name);
		$view->render(TRUE);
	} // end of show
	
	/**
	 * Adds new device active link
	 */
	public function add()
	{
		if (!$this->acl_check_new('Device_active_links_Controller', 'active_links'))
		{
			Controller::error(ACCESS);
		}
		
		$device_model = new Device_Model();
		$devices = $device_model->select_list_device();
		
		$device_template_model = new Device_template_Model();
		$all_device_templates = $device_template_model->get_all_templates();
		
		$device_templates = array();
		foreach ($all_device_templates AS $dt)
		{
			$device_templates[$dt->id] = $dt->name.' ('.$dt->enum_type_translated.')';
		}
		
		// forge form
		$form = new Forge();
		
		$form->input('url_pattern')
				->label('URL pattern')
				->help(help::hint('url_pattern'))
				->rules('required')
				->style('width:400px');
		
		$form->input('name')
				->label('Name')
				->help(help::hint('active_link_name'));
		
		$form->input('title')
				->label('Link title')
				->help(help::hint('active_link_title'))
				->rules('required');
		
		$form->dropdown('as_form')
				->label('Send as form')
				->help(help::hint('send_as_form'))
				->options(arr::bool())
				->selected(0);
		
		$form->dropdown('show_in_user_grid')
				->options(arr::bool());
		
		$form->dropdown('show_in_grid')
				->options(arr::bool());
		
		$form->dropdown('devices[]')
				->label('Devices')
				->options($devices)
				->multiple('multiple')
				->size(20);
		
		$form->dropdown('device_templates[]')
				->label('Device templates')
				->help(help::hint('active_link_device_templates'))
				->options($device_templates)
				->multiple('multiple')
				->size(20);
		
		$form->submit('Add');
		
		// validates form and saves data
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			if (!$form_data['devices'])
			{
				$form_data['devices'] = array();
			}
			
			$dal = new Device_active_link_Model();
			
			try
			{
				$dal->transaction_start();
				
				$dal->url_pattern = $form_data['url_pattern'];
				$dal->name = $form_data['name'];
				$dal->title = $form_data['title'];
				$dal->show_in_user_grid = $form_data['show_in_user_grid'];
				$dal->show_in_grid = $form_data['show_in_grid'];
				$dal->as_form = $form_data['as_form'];
				
				$dal->save_throwable();
				
				// map devices
				$dal->map_devices_to_active_link($form_data['devices'], $dal->id);
				
				// map templates
				$dal->map_devices_to_active_link(
						$form_data['device_templates'],
						$dal->id,
						Device_active_link_Model::TYPE_TEMPLATE
				);
				
				$dal->transaction_commit();
				
				$this->redirect('device_active_links/show_all');
			}
			catch (Exception $e)
			{
				$dal->transaction_rollback();
					
				Log::add_exception($e);
				status::error('Device active link has not been successfully saved.', $e);
			}
		}
		
		$headline = __('Add new device active link');
		
		$breadcrumbs = breadcrumbs::add()
				->link('device_active_links/show_all', 'Device active links',
						$this->acl_check_view('Device_active_links_Controller', 'active_links'))
				->disable_translation()
				->text($headline);
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('device_active_links/add');
		$view->content->form = $form->html();
		$view->content->headline = $headline;
		$view->render(TRUE);
	} // end of function add
        
	/**
	 * Function edits device.
	 * 	
	 * @param integer $device_active_link_id
	 */
	public function edit($device_active_link_id = null) 
	{
		if (!$this->acl_check_edit('Device_active_links_Controller', 'active_links'))
		{
			Controller::error(ACCESS);
		}
		
		// Get devices
		$device_model = new Device_Model();
		$devices = $device_model->select_list_device();
		
		// Get device templates
		$device_template_model = new Device_template_Model();
		$all_device_templates = $device_template_model->get_all_templates();
		
		$device_templates = array();
		foreach ($all_device_templates AS $dt)
		{
			$device_templates[$dt->id] = $dt->name.' ('.$dt->enum_type_translated.')';
		}
		
		// Get selected devices
		$active_link = new Device_active_link_Model($device_active_link_id);
		
		$selected_devices = array();
		
		foreach ($active_link->get_active_link_devices() AS $device)
		{
			$selected_devices[] = $device->id;
		}
		// get selected device templates
		$selected_device_templates = array();
		foreach ($active_link->get_active_link_devices(NULL, Device_active_link_Model::TYPE_TEMPLATE) AS $dt)
		{
			$selected_device_templates[] = $dt->id;
		}
		
		// forge form
		$form = new Forge();
		
		$form->input('url_pattern')
				->label('URL pattern')
				->help(help::hint('url_pattern'))
				->rules('required')
				->value(htmlspecialchars_decode($active_link->url_pattern))
				->style('width:600px');
		
		$form->input('name')
				->label('Name')
				->help(help::hint('active_link_name'))
				->value($active_link->name);
		
		$form->input('title')
				->label('Link title')
				->help(help::hint('active_link_title'))
				->rules('required')
				->value($active_link->title);
		
		$form->dropdown('as_form')
				->label('Send as form')
				->help(help::hint('send_as_form'))
				->options(arr::bool())
				->selected($active_link->as_form);
		
		$form->dropdown('show_in_user_grid')
				->options(arr::bool())
				->selected($active_link->show_in_user_grid);
		
		$form->dropdown('show_in_grid')
				->options(arr::bool())
				->selected($active_link->show_in_grid);
		
		$form->dropdown('devices[]')
				->label('Devices')
				->options($devices)
				->selected($selected_devices)
				->multiple('multiple')
				->size(20);
		
		$form->dropdown('device_templates[]')
				->label('Device templates')
				->help(help::hint('active_link_device_templates'))
				->options($device_templates)
				->selected($selected_device_templates)
				->multiple('multiple')
				->size(20);
		
		$form->submit('Edit');
		
		// validates form and saves data
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			if (!$form_data['devices'])
			{
				$form_data['devices'] = array();
			}
			
			try
			{
				$active_link->transaction_start();
				
				$active_link->url_pattern = $form_data['url_pattern'];
				$active_link->name = $form_data['name'];
				$active_link->title = $form_data['title'];
				$active_link->show_in_user_grid = $form_data['show_in_user_grid'];
				$active_link->show_in_grid = $form_data['show_in_grid'];
				$active_link->as_form = $form_data['as_form'];
				
				$active_link->save_throwable();
				
				$active_link->unmap_devices_from_active_link($active_link->id);
				$active_link->map_devices_to_active_link($form_data['devices'], $active_link->id);
				
				$active_link->unmap_devices_from_active_link($active_link->id,
						Device_active_link_Model::TYPE_TEMPLATE
				);
				$active_link->map_devices_to_active_link($form_data['device_templates'], $active_link->id,
						Device_active_link_Model::TYPE_TEMPLATE
				);
				
				$active_link->transaction_commit();
				
				$this->redirect(Path::instance()->previous());
			}
			catch (Exception $e)
			{
				$active_link->transaction_rollback();
					
				Log::add_exception($e);
				status::error('Device active link has not been successfully saved.', $e);
			}
		}
		
		$headline = __('Edit device active link');
		
		$breadcrumbs = breadcrumbs::add()
				->link('device_active_links/show_all', 'Device active links',
						$this->acl_check_view('Device_active_links_Controller', 'active_links'))
				->link('device_active_links/show/' . $active_link->id,
						'ID ' . $active_link->id . ' - ' .
						(!$active_link->name ? $active_link->title : $active_link->name),
						$this->acl_check_view('Device_active_links_Controller','active_links'))
				->disable_translation()
				->text($headline);
		
		$view = new View('main');
		$view->title = __('Edit device active link').' '.
					(!$active_link->name ? $active_link->title : $active_link->name);
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('device_active_links/add');
		$view->content->form = $form->html();
		$view->content->headline = __('Edit device active link').' '.
					(!$active_link->name ? $active_link->title : $active_link->name);
		$view->render(TRUE);
	}
		
	/**
	 * Deletes device action link
	 * 
	 * @author David Raška
	 * @param integer $device_active_link_id
	 */
	public function delete($device_active_link_id = null)
	{
		if (!isset($device_active_link_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$active_link = new Device_active_link_Model($device_active_link_id);
		
		if (!$active_link->id)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_delete('Device_active_links_Controller', 'active_links'))
		{
			Controller::error(ACCESS);
		}
		
		$linkback = Path::instance()->previous();

		if (url::slice(url_lang::uri($linkback), 1, 1) == 'show')
		{
			$linkback = 'device_active_links/show_all';
		}
		
		// delete
		try
		{
			$active_link->delete_throwable();
			
			status::success('Device active link has been successfully deleted.');
		}
		catch (Exception $e)
		{
			Log::add_exception($e);
			status::error(__($e->getMessage()), $e);
		}
		
		$this->redirect($linkback);
	}
}