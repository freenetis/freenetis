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
 * Manages device templates.
 * 
 * @package Controller
 * @author Ondřej Fibich
 */
class Device_templates_Controller extends Controller
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
		url::redirect('device_templates/show_all');
	}

	/**
	 * Shows all templates
	 */
	public function show_all()
	{
		// access check
		if (!$this->acl_check_view('Device_templates_Controller', 'device_template'))
		{
			Controller::error(ACCESS);
		}

		// model
		$device_templates = new Device_template_Model();

		// gets data
		$query = $device_templates->get_all_templates();

		// grid
		$grid = new Grid('clouds', null, array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false
		));

		if ($this->acl_check_new('Device_templates_Controller', 'device_template'))
		{
			$grid->add_new_button('device_templates/add', 'Add new template');
			$grid->add_new_button('device_templates/import_from_file', 'Import device templates');
		}
		
		$grid->add_new_button('device_templates/export_to_json', 'Export device templates');

		$grid->field('id')
				->label('ID');
		
		$grid->field('enum_type_translated')
				->label('Type');
		
		$grid->field('name');
		
		$grid->callback_field('default')
				->callback('callback::boolean');
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action()
				->icon_action('show')
				->url('device_templates/show');
		
		if ($this->acl_check_edit('Device_templates_Controller', 'device_template'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('device_templates/edit');
		}
		
		if ($this->acl_check_delete('Device_templates_Controller', 'device_template'))
		{			
			$actions->add_action()
					->icon_action('delete')
					->url('device_templates/delete')
					->class('delete_link');
		}
		
		// load datasource
		$grid->datasource($query);
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->text('Device templates')
				->html();

		// main view
		$view = new View('main');
		$view->title = __('List of all device templates');
		$view->content = new View('show_all');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->headline = __('List of all device templates');
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Show device template with given ID
	 *
	 * @param integer $device_template_id 
	 */
	public function show($device_template_id = NULL)
	{
		// param check
		if (!$device_template_id || !is_numeric($device_template_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check acess
		if (!$this->acl_check_view('Device_templates_Controller', 'device_template'))
		{
			Controller::error(ACCESS);
		}

		$device_templates_model = new Device_template_Model($device_template_id);
		
		// exist record
		if (!$device_templates_model->id)
		{
			Controller::error(RECORD);
		}		
		
		$device_active_link = new Device_active_link_Model();
		$active_links = $device_active_link->get_device_active_links(
				$device_template_id,
				Device_active_link_Model::TYPE_TEMPLATE
		);
		
		$active_links_grid = new Grid('devices', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> $active_links->count()
		));
		
		$active_links_grid->Field('id')
				->label('ID');
		
		$active_links_grid->Field('url_pattern')
				->label('URL pattern');
		
		$active_links_grid->Field('name');
		
		$active_links_grid->Field('title');
		
		if ($this->acl_check_view('Device_active_links_Controller', 'active_links'))
		{
			$actions = $active_links_grid->grouped_action_field();
			
			$actions->add_action('id')
					->icon_action('show')
					->url('device_active_links/show');
		}
		
		$active_links_grid->datasource($active_links);
		
		$headline = $device_templates_model->name . ' (' . $device_template_id . ')';
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('device_templates/show_all', 'Devices templates',
						$this->acl_check_view('Device_templates_Controller', 'device_template'))
				->disable_translation()
				->text($headline)
				->html();

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('device_templates/show');
		$view->content->device_template = $device_templates_model;
		$view->content->iface_model = new Iface_Model();
		$view->content->ivals = $device_templates_model->get_value();
		$view->content->active_links_grid = $active_links_grid;
		$view->render(TRUE);
	}

	/**
	 * Adds device template
	 * 
	 * @param integer $enum_type_id		Prefilled enum type
	 */
	public function add($enum_type_id = NULL)
	{
		// check access
		if (!$this->acl_check_new('Device_templates_Controller', 'device_template'))
		{
			Controller::error(ACCESS);
		}
		
		// enum types dropdown model
		$et_model = new Enum_type_Model();
		$enum_types = array
		(
			NULL => '---- ' . __('Select type') . ' ----'
		) + $et_model->get_values(Enum_type_Model::DEVICE_TYPE_ID);

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
		
		// form
		$form = new Forge();

		$form->group('Basic information');
		
		$form->input('name')
				->label('Trade name')
				->rules('required')
				->autocomplete('json/device_trade_name');
		
		$form->dropdown('enum_types')
				->label('Type')
				->options($enum_types)
				->selected($enum_type_id)
				->rules('required')
				->style('width: 200px');
		
		$form->checkbox('default')
				->label('Default for this device type?');
		
		$form->dropdown('active_links_select[]')
				->label('Device active links')
				->options($active_links)
				->multiple('multiple')
				->size(10);
		
		// eth
		
		$eth_form = $form->group('Ethernet interfaces');
		
		$eth_form->input('values[' . Iface_Model::TYPE_ETHERNET . '][count]')
				->label('Count')
				->rules('valid_numeric|length[0,2]');

		// wlan
		
		$wlan_form = $form->group('Wireless interfaces');
		
		$wlan_form->input('values[' . Iface_Model::TYPE_WIRELESS . '][min_count]')
				->label('Minimal count')
				->rules('valid_numeric|length[0,2]');
		
		$wlan_form->input('values[' . Iface_Model::TYPE_WIRELESS . '][max_count]')
				->label('Maximal count')
				->rules('valid_numeric|length[0,2]');

		// ports
		
		$port_form = $form->group('Ports');
		
		$port_form->input('values[' . Iface_Model::TYPE_PORT . '][count]')
				->label('Count')
				->rules('valid_numeric|length[0,2]');

		// internal
		
		$internal_form = $form->group('Internal interfaces')->visible(FALSE);
		
		$internal_form->input('values[' . Iface_Model::TYPE_INTERNAL . '][count]')
				->label('Count')
				->rules('valid_numeric|length[0,2]');
		
		$form->submit('Add');

		// validate form and save data
		if ($form->validate())
		{
			// data
			$form_data = $form->as_array(FALSE);
			$vals = $_POST['values'];
			$vals['default_iface'] = @$_POST['default_iface'];
			
			// parse values
			$this->validate_form_value($vals);
			$default = isset($form_data['default']) && $form_data['default'];
			
			// model
			$device_template_model = new Device_template_Model();
			$device_template_model->name = htmlspecialchars($form_data['name']);
			$device_template_model->enum_type_id = intval($form_data['enum_types']);
			$device_template_model->values = json_encode($vals);
			$device_template_model->default = $default;
			$device_template_model->save();
			
			// remove old defauts
			if ($default)
			{
				$tdefaults = $device_template_model->where(array
				(
					'id <>'			=> $device_template_model->id,
					'enum_type_id'	=> $device_template_model->enum_type_id,
					'default'		=> 1
				))->find_all();
				
				foreach ($tdefaults as $tdefault)
				{
					$tdefault->default = 0;
					$tdefault->save();
				}
			}
			
			// map to device active links
			$device_active_links_model->map_device_to_active_links(
					$device_template_model,
					$form_data['active_links_select'],
					Device_active_link_Model::TYPE_TEMPLATE
			);
			
			// clean
			unset($vals);
			unset($form_data);
			
			// message
			status::success('Device template has been successfully added.');
			// redirection
			$this->redirect('device_templates/show/', $device_template_model->id);
		}
		else
		{
			$headline = __('Add new device template');

			// bread crumbs
			$breadcrumbs = breadcrumbs::add()
					->link('device_templates/show_all', 'Device templates',
							$this->acl_check_view('Device_templates_Controller', 'device_template'))
					->disable_translation()
					->text($headline)
					->html();								

			// view
			$view = new View('main');
			$view->title = $headline;
			$view->content = new View('form');
			$view->breadcrumbs = $breadcrumbs;
			$view->content->headline = $headline;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Edits device template
	 *
	 * @param integer $device_templates_id 
	 */
	public function edit($device_templates_id = NULL)
	{
		// check param
		if (!$device_templates_id || !is_numeric($device_templates_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_edit('Device_templates_Controller', 'device_template'))
		{
			Controller::error(ACCESS);
		}

		// load model
		$device_template_model = new Device_template_Model($device_templates_id);
		
		// check exists
		if (!$device_template_model->id)
		{
			Controller::error(RECORD);
		}
		
		// enum types dropdown model
		$et_model = new Enum_type_Model();
		$enum_types = array
		(
			NULL => '---- ' . __('Select type') . ' ----'
		) + $et_model->get_values(Enum_type_Model::DEVICE_TYPE_ID);
		
		// values
		$ivals = $device_template_model->get_value();
		
		// Device active links
		$device_active_links_model = new Device_active_link_Model();
		
		$all_active_links = $device_active_links_model->get_all_active_links();
		$all_selected_active_link = $device_active_links_model->get_device_active_links(
						$device_templates_id,
						Device_active_link_Model::TYPE_TEMPLATE
		);
		
		$active_links = array();
		
		foreach($all_active_links AS $active_link)
		{
			if (!$active_link->name)
				$active_links[$active_link->id] = $active_link->title;
			else
				$active_links[$active_link->id] = $active_link->name.' ('.$active_link->title.')';
		}
		
		$selected_active_links = array();
		
		foreach ($all_selected_active_link AS $active_link)
		{
			$selected_active_links[] = $active_link->id;
		}
		
		// form
		$form = new Forge('device_templates/edit/' . $device_templates_id);

		$form->group('Basic information');
		
		$form->input('name')
				->label('Trade name')
				->rules('required')
				->value($device_template_model->name)
				->autocomplete('json/device_trade_name');
		
		$form->dropdown('enum_types')
				->label('Type')
				->options($enum_types)
				->rules('required')
				->selected($device_template_model->enum_type_id)
				->style('width: 200px');
		
		$form->checkbox('default')
				->label('Default for this device type?')
				->checked($device_template_model->default);
		
		$form->dropdown('active_links[]')
				->label('Device active links')
				->options($active_links)
				->selected($selected_active_links)
				->multiple('multiple')
				->size(10);
		
		
		// eth
		
		$eth_form = $form->group('Ethernet interfaces');
		
		$eth_form->input('values[' . Iface_Model::TYPE_ETHERNET . '][count]')
				->label('Count')
				->rules('valid_numeric|length[0,2]')
				->value($ivals[Iface_Model::TYPE_ETHERNET]['count']);

		// wlan
		
		$wlan_form = $form->group('Wireless interfaces');
		
		$wlan_form->input('values[' . Iface_Model::TYPE_WIRELESS . '][min_count]')
				->label('Minimal count')
				->rules('valid_numeric|length[0,2]')
				->value($ivals[Iface_Model::TYPE_WIRELESS]['min_count']);
		
		$wlan_form->input('values[' . Iface_Model::TYPE_WIRELESS . '][max_count]')
				->label('Maximal count')
				->rules('valid_numeric|length[0,2]')
				->value($ivals[Iface_Model::TYPE_WIRELESS]['max_count']);

		// ports
		
		$port_form = $form->group('Ports');
		
		$port_form->input('values[' . Iface_Model::TYPE_PORT . '][count]')
				->label('Count')
				->rules('valid_numeric|length[0,2]')
				->value($ivals[Iface_Model::TYPE_PORT]['count']);

		// internal
		
		$internal_form = $form->group('Internal interfaces');
		
		if (!$ivals[Iface_Model::TYPE_INTERNAL]['count'])
		{
			$internal_form->visible(FALSE);
		}
		
		$internal_form->input('values[' . Iface_Model::TYPE_INTERNAL . '][count]')
				->label('Count')
				->rules('valid_numeric|length[0,2]')
				->value($ivals[Iface_Model::TYPE_INTERNAL]['count']);
		
		$form->submit('Edit');

		//validate form and save data
		if ($form->validate())
		{
			// data
			$form_data = $form->as_array(FALSE);
			$vals = $_POST['values'];
			$vals['default_iface'] = @$_POST['default_iface'];
			
			// parse values
			$this->validate_form_value($vals);
			$default = isset($form_data['default']);
			
			// model
			$device_template_model->name = htmlspecialchars($form_data['name']);
			$device_template_model->enum_type_id = intval($form_data['enum_types']);
			$device_template_model->values = json_encode($vals);
			$device_template_model->default = $default;
			$device_template_model->save();
			
			// remove old defauts
			if ($default)
			{
				$tdefaults = $device_template_model->where(array
				(
					'id <>'			=> $device_template_model->id,
					'enum_type_id'	=> $device_template_model->enum_type_id,
					'default'		=> 1
				))->find_all();
				
				foreach ($tdefaults as $tdefault)
				{
					$tdefault->default = 0;
					$tdefault->save();
				}
			}
			
			$device_active_links_model->unmap_device_from_active_links(
					$device_templates_id,
					Device_active_link_Model::TYPE_TEMPLATE
			);
			
			$device_active_links_model->map_device_to_active_links(
					$device_templates_id,
					$form_data['active_links'],
					Device_active_link_Model::TYPE_TEMPLATE
			);

			// clean
			unset($vals);
			unset($form_data);
			
			// message
			status::success('Device template has been successfully updated.');
			// redirection
			url::redirect('device_templates/show/' . $device_template_model->id);
		}
		
		$headline = __('Edit device template');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('device_templates/show_all', 'Device templates',
						$this->acl_check_view('Device_templates_Controller', 'device_template'))
				->disable_translation()
				->link('device_templates/show/' . $device_templates_id,
						$device_template_model->name . ' (' . $device_templates_id . ')',
						$this->acl_check_view('Device_templates_Controller', 'device_template'))
				->text($headline)
				->html();

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->form = $form->html();
		$view->content->headline = $headline;
		$view->render(TRUE);
	}

	/**
	 * Deletes device template and its records in pivot tables 
	 *
	 * @param integer $device_template_id 
	 */
	public function delete($device_template_id = NULL)
	{
		// check param
		if (!$device_template_id || !is_numeric($device_template_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_delete('Device_templates_Controller', 'device_template'))
		{
			Controller::error(ACCESS);
		}

		// load model
		$device_template_model = new Device_template_Model($device_template_id);
		
		// check exists
		if (!$device_template_model->id)
		{
			Controller::error(RECORD);
		}
		
		// delete (pivot tables are deleted by forein keys)
		if ($device_template_model->delete())
		{
			status::success('Device template has been successfully deleted.');
		}
		else
		{
			status::error('Error - cant delete this template.');
		}

		// redirect to show all
		url::redirect('device_templates/show_all');
	}
	
	/**
	 * Function shows upload dialog
	 * 
	 * @author David Raška 
	 */
	public function import_from_file()
	{
		// check acess
		if (!$this->acl_check_new('Device_templates_Controller', 'device_template'))
		{
			Controller::error(ACCESS);
		}
		
		$upload_form = new Forge();
		
		$upload_form->upload('file')
				->label('File')
				->new_name('device_templates.json');
		
		$upload_form->submit('Send');
		
		$headline = __('Upload device templates');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('device_templates/show_all', 'Device templates',
						$this->acl_check_view('Device_templates_Controller', 'device_template'))
				->disable_translation()
				->text($headline)
				->html();
		
		if ($upload_form->validate())
		{
			$form_data = $upload_form->as_array(FALSE);
			
			//load data from uploaded file
			$handle = @fopen($form_data['file'], 'r');
			
			$data = stream_get_contents($handle);
			
			@fclose($handle);
			
			//get array
			$data = json_decode($data);
			
			if ($data)
			{
				$imported = 0;
				$skipped = 0;
				$bad = 0;

				// model
				$device_templates = new Device_template_Model();
				
				foreach ($data AS $template)
				{
					if (
						!isset($template->name) ||
						!isset($template->enum_type_id) ||
						!isset($template->values) ||
						!isset($template->default)
						)
					{
						$bad++;
						continue;
					}
					
					if (ORM::factory('device_template')
							->where(array
								(
									'name' => $template->name,
									'enum_type_id' => $template->enum_type_id
								))
							->count_all())
					{
						//Device template already exist
						$skipped++;
					}
					else
					{
						$device_templates->transaction_start();
						
						try
						{
							//New device template
							$device_template_model = new Device_template_Model();
							
							$device_template_model->name = $template->name;
							$device_template_model->enum_type_id = $template->enum_type_id;
							$device_template_model->values = json_encode($template->values);
							$device_template_model->default = $template->default;
							
							$device_template_model->save_throwable();
							
							// remove old defauts
							if ($template->default)
							{
								$tdefaults = $device_template_model->where(array
								(
									'id <>'			=> $device_template_model->id,
									'enum_type_id'	=> $device_template_model->enum_type_id,
									'default'		=> 1
								))->find_all();

								foreach ($tdefaults as $tdefault)
								{
									$tdefault->default = 0;
									$tdefault->save_throwable();
								}
							}

							$device_templates->transaction_commit();
							$imported++;
						}
						catch (Exception $e)
						{
							$device_templates->transaction_rollback();
							Log::add_exception($e);
							$bad++;
						}
					}
				}
				
				$content = html::image(array
					(
						'src' => 'media/images/icons/status/success.png',
						'class' => 'status_icon'
					)
					).'<b>'.__('Imported').': '.$imported.'</b><br>';
				$content .= html::image(array
					(
						'src' => 'media/images/icons/status/warning.png',
						'class' => 'status_icon'
					)
					).'<b>'.__('Skipped').': '.$skipped.'</b><br>';
				$content .= html::image(array
					(
						'src' => 'media/images/icons/status/error.png',
						'class' => 'status_icon'
					)
					).'<b>'.__('Bad').': '.$bad.'</b><br>';
				
				
				$headline = __('Import results');
				// bread crumbs
				$breadcrumbs = breadcrumbs::add()
						->link('device_templates/show_all', 'Device templates',
								$this->acl_check_view('Device_templates_Controller', 'device_template'))
						->link('device_templates/import_from_file', 'Upload device templates',
								$this->acl_check_view('Device_templates_Controller', 'device_template'))
						->text('Import results')
						->html();
			}
			else
			{
				//Error
				$upload_form->file->add_error('requied', __('Error - can\'t read file'));
				$content = $upload_form->html();
			}
		}
		else
		{
			$content = $upload_form->html();
		}

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->content = new View('form');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->headline = $headline;
		$view->content->form = $content;
		$view->render(TRUE);

	}
	
	/**
	 * Function returns all device templates in JSON format
	 * 
	 * @author David Raška
	 */
	public function export_to_json()
	{
		// check acess
		if (!$this->acl_check_view('Device_templates_Controller', 'device_template'))
		{
			Controller::error(ACCESS);
		}
		
		// model
		$device_templates = new Device_template_Model();
		
		// gets data
		$result = $device_templates->find_all();
		
		$templates_array = array();
		
		foreach ($result AS $template)
		{
			$templates_array[] = array
			(
				'enum_type_id' => $template->enum_type_id,
				'name' => $template->name,
				'values' => json_decode($template->values),
				'default' => $template->default
			);
		}
		
		$data = json_encode($templates_array);
		
		//JSON headers
		Json_Controller::send_json_headers();
		
		@header('Content-Disposition: attachment; filename="device_templates.json"');
		@header('Content-Transfer-Encoding: binary');
		@header('Content-Length: '.  strlen($data));
		@header('Pragma: no-cache');

		echo $data;
	}
	
	/**
	 * Validate form value
	 *
	 * @param array $vals		Reference to value
	 */
	private function validate_form_value(&$vals)
	{
		$vals[Iface_Model::TYPE_WIRELESS]['type'] = Iface_Model::TYPE_WIRELESS;
		$vals[Iface_Model::TYPE_WIRELESS]['has_ip'] = Iface_Model::type_has_ip_address(Iface_Model::TYPE_WIRELESS);
		$vals[Iface_Model::TYPE_WIRELESS]['has_link'] = Iface_Model::type_has_link(Iface_Model::TYPE_WIRELESS);
		$vals[Iface_Model::TYPE_WIRELESS]['has_mac'] = Iface_Model::type_has_mac_address(Iface_Model::TYPE_WIRELESS);
		$vals[Iface_Model::TYPE_WIRELESS]['min_count'] = intval($vals[Iface_Model::TYPE_WIRELESS]['min_count']);
		$vals[Iface_Model::TYPE_WIRELESS]['max_count'] = intval($vals[Iface_Model::TYPE_WIRELESS]['max_count']);

		if (!isset($vals[Iface_Model::TYPE_WIRELESS]['items']))
		{
			$vals[Iface_Model::TYPE_WIRELESS]['items'] = array();
		}
		else
		{
			foreach ($vals[Iface_Model::TYPE_WIRELESS]['items'] as $k => $v)
			{
				$vals[Iface_Model::TYPE_WIRELESS]['items'][$k]['name'] = htmlspecialchars($v['name']);
			}
		}

		$vals[Iface_Model::TYPE_ETHERNET]['type'] = Iface_Model::TYPE_ETHERNET;
		$vals[Iface_Model::TYPE_ETHERNET]['has_ip'] = Iface_Model::type_has_ip_address(Iface_Model::TYPE_ETHERNET);
		$vals[Iface_Model::TYPE_ETHERNET]['has_link'] = Iface_Model::type_has_link(Iface_Model::TYPE_ETHERNET);
		$vals[Iface_Model::TYPE_ETHERNET]['has_mac'] = Iface_Model::type_has_mac_address(Iface_Model::TYPE_ETHERNET);
		$vals[Iface_Model::TYPE_ETHERNET]['count'] = intval($vals[Iface_Model::TYPE_ETHERNET]['count']);

		if (!isset($vals[Iface_Model::TYPE_ETHERNET]['items']))
		{
			$vals[Iface_Model::TYPE_ETHERNET]['items'] = array();
		}
		else
		{
			foreach ($vals[Iface_Model::TYPE_ETHERNET]['items'] as $k => $v)
			{
				$vals[Iface_Model::TYPE_ETHERNET]['items'][$k]['name'] = htmlspecialchars($v['name']);
			}
		}
		
		$vals[Iface_Model::TYPE_PORT]['type'] = Iface_Model::TYPE_PORT;
		$vals[Iface_Model::TYPE_PORT]['has_ip'] = Iface_Model::type_has_ip_address(Iface_Model::TYPE_PORT);
		$vals[Iface_Model::TYPE_PORT]['has_link'] = Iface_Model::type_has_link(Iface_Model::TYPE_PORT);
		$vals[Iface_Model::TYPE_PORT]['has_mac'] = Iface_Model::type_has_mac_address(Iface_Model::TYPE_PORT);
		$vals[Iface_Model::TYPE_PORT]['count'] = intval($vals[Iface_Model::TYPE_PORT]['count']);

		if (!isset($vals[Iface_Model::TYPE_PORT]['items']))
		{
			$vals[Iface_Model::TYPE_PORT]['items'] = array();
		}
		else
		{
			foreach ($vals[Iface_Model::TYPE_PORT]['items'] as $k => $v)
			{
				$vals[Iface_Model::TYPE_PORT]['items'][$k]['number'] = intval($v['number']);
				$vals[Iface_Model::TYPE_PORT]['items'][$k]['name'] = htmlspecialchars($v['name']);
			}
		}
		
		$vals[Iface_Model::TYPE_INTERNAL]['type'] = Iface_Model::TYPE_INTERNAL;
		$vals[Iface_Model::TYPE_INTERNAL]['has_ip'] = Iface_Model::type_has_ip_address(Iface_Model::TYPE_INTERNAL);
		$vals[Iface_Model::TYPE_INTERNAL]['has_link'] = Iface_Model::type_has_link(Iface_Model::TYPE_INTERNAL);
		$vals[Iface_Model::TYPE_INTERNAL]['has_mac'] = Iface_Model::type_has_mac_address(Iface_Model::TYPE_INTERNAL);
		$vals[Iface_Model::TYPE_INTERNAL]['count'] = intval(@$vals[Iface_Model::TYPE_INTERNAL]['count']);

		if (!isset($vals[Iface_Model::TYPE_INTERNAL]['items']))
		{
			$vals[Iface_Model::TYPE_INTERNAL]['items'] = array();
		}
		else
		{
			foreach ($vals[Iface_Model::TYPE_INTERNAL]['items'] as $k => $v)
			{
				$vals[Iface_Model::TYPE_INTERNAL]['items'][$k]['name'] = htmlspecialchars($v['name']);
			}
		}

		// check min and max
		if ($vals[Iface_Model::TYPE_WIRELESS]['min_count'] > $vals[Iface_Model::TYPE_WIRELESS]['max_count'])
		{
			$vals[Iface_Model::TYPE_WIRELESS]['max_count'] = $vals[Iface_Model::TYPE_WIRELESS]['min_count'];
		}
	}

}
