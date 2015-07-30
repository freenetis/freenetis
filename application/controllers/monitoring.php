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
 * Controller performs action for monitoring of devices
 * 
 * @package Controller
 */
class Monitoring_Controller extends Controller
{
	/**
	 * Only enable if notification enabled
	 */
	public function __construct()
	{
		parent::__construct();
		
	    if (!module::e('monitoring'))
			self::error(ACCESS);
	}
	
	/**
	 * Index method - only redirect to list of all monitored hosts
	 * 
	 * @author Michal Kliment 
	 */
	public function index()
	{
		$this->redirect('show_all');
	}
	
	/**
	 * Shows all monitored hosts
	 * 
	 * @author Michal Kliment
	 * @param type $priority
	 * @param string $group_by 
	 */
	public function show_all($priority = 1, $group_by = '')
	{	
		// access control
		if (!$this->acl_check_view('Monitoring_Controller', 'monitoring'))
		{
			Controller::error (ACCESS);
		}
		
		// definition options for group by
		$group_by_options = array
		(
			'all' => 'all devices',
			'member_id' => 'member_name',
			'type' => 'type_name',
			'state' => 'state_name',
			'town_id' => 'town_name',
			'address_point_id' => 'address_point_name',
		);
		
		$monitor_host_model = new Monitor_host_Model();
		
		$form = new Forge();
		
		$priorities = arr::from_objects(
				$monitor_host_model->get_all_priorities(), 'priority'
		);
		
		if (count($priorities))
		{
			$form->dropdown('priority')
				->options($priorities)
				->selected($priority);
		}
		
		$group_by_options_values = array
		(
			'all'				=> __('All devices'),
			'member_id'			=> __('Member'),
			'state'				=> __('State'),
			'type'				=> __('Type'),
			'town_id'			=> __('Town'),
			'address_point_id'	=> __('Address point')
		);
		
		$user_model = new User_Model($this->user_id);
		$group_by_setting = $user_model->get_user_setting(User_Model::SETTINGS_MONITORING_GROUP_BY);
		
		// empty value in users settings
		if (!empty($group_by_setting))
		{
			// use database settings if not set explicitly
			if (empty($group_by))
			{
				$group_by = $group_by_setting;
			}
		}
		// empty value in users settings
		else
		{
			$group_by = 'all';
		}
		
		// update database settings
		if ($group_by != $group_by_setting)
		{
			$user_model->set_user_setting(User_Model::SETTINGS_MONITORING_GROUP_BY, $group_by);
		}
		
		$form->dropdown('group_by')
			->options($group_by_options_values)
			->selected($group_by);
		
		$form->submit('Group by');
		
		// redirect to this method with new priority or group by option
		if ($form->validate() && $form->priority
			&& $form->priority->value && $form->group_by->value)
		{
			$this->redirect(
				'show_all/',
				$form->priority->value.'/'.$form->group_by->value
			);
		}
		
		// default group by option
		if (!isset($group_by_options[$group_by]))
			$group_by = 'all';
		
		// filter
		$filter_form = new Filter_form('mh');
		
		$filter_form->add('device_name');
		
		$filter_form->add('member_id')
			->type('select')
			->label('Member')
			->values(ORM::factory('member')->select_list('id', 'name'));
		
		$filter_form->add('state')
			->type('select')
			->values(array
			(
				Monitor_host_Model::STATE_UP => __('Online'),
				Monitor_host_Model::STATE_DOWN => __('Offline'),
			));
		
		$filter_form->add('town')
			->type('select')
			->values(
				array_unique(
					ORM::factory('town')->select_list('town', 'town')
				)
			);
		
		$filter_form->add('street')
			->type('select')
			->values(
				array_unique(
					ORM::factory('street')->select_list('street', 'street')
				)
			);
		
		$filter_form->add('type')
			->type('select')
			->values(
				ORM::factory('enum_type')
					->get_values(Enum_type_Model::DEVICE_TYPE_ID)
			);
		
		$filter_form->add('ip_address')
			->type('network_address')
			->class(array
			(
				Filter_form::OPER_IS => 'ip_address',
				Filter_form::OPER_IS_NOT => 'ip_address',
				Filter_form::OPER_NETWORK_IS_IN => 'cidr',
				Filter_form::OPER_NETWORK_IS_NOT_IN => 'cidr',
			));
		
		$hosts = $monitor_host_model->get_all_monitored_hosts(
					$priority, 'device_name', $filter_form->as_sql()
		);
			
		$devices = array();
		$labels = array();
		
		// divides hosts to groups
		foreach ($hosts as $host)
		{	
			$index = isset($host->$group_by) ? $host->$group_by : $group_by;
			
			if (!isset($devices[$index]))
				$devices[$index] = array();
			
			$devices[$index][] = $host;
			
			if (isset($host->$group_by_options[$group_by]))
				$labels[$index] = $host->$group_by_options[$group_by];
		}
		
		$grids = array();
		foreach (array_keys($devices) as $group)
		{
			// for each group  create own grid
			
			$grids[$group] = new Grid('', null, array
			(
				'use_paginator'	   			=> false,
				'use_selector'	   			=> false
			));
			
			$grids[$group]->form_field('host_id')
					->label('')
					->order(false)
					->type('checkbox');
			
			$grids[$group]->link_field('device_id')
					->link('devices/show', 'device_name')
					->label('Device name');
			
			$grids[$group]->callback_field('member_id')
					->callback('callback::member_field')
					->label('Member');
			
			$grids[$group]->callback_field('ip_address')
					->callback('callback::ip_address_field');
			
			$grids[$group]->callback_field('state')
					->callback('callback::monitor_state_field')
					->class('center');
			
			$grids[$group]->callback_field('state_changed_date')
					->callback('callback::datetime_diff', 'short')
					->label('Uptime / Downtime')
					->class('center');
			
			$grids[$group]->callback_field('latency_avg')
					->callback('callback::latency_field')
					->label('Latency')
					->class('center');
			
			$grids[$group]->callback_field('availability')
					->callback('callback::percent')
					->class('center');
			
			$actions = $grids[$group]->grouped_action_field();
			
			$actions->add_action('id')
					->icon_action('show')
					->url('monitoring/show')
					->label('Show monitoring detail of device')
					->class('popup_link');
			
			$actions->add_action('id')
					->icon_action('edit')
					->url('monitoring/edit')
					->label('Edit monitoring parameter of device')
					->class('popup_link');
			
			$actions->add_action('id')
					->icon_action('delete')
					->url('monitoring/delete')
					->label('Remove device from monitoring')
					->class('delete_link');
			
			$grids[$group]->datasource($devices[$group]);
			
			// adds extra buttons
			$grids[$group]->form_extra_buttons = array
			(
				form::checkbox('grid-'.$group.'-mark_all', 'on', FALSE, 'class="checkbox mark_all"') .
				form::label('mark_all', __('Mark all'), 'class="mark_all_label"')
			);
			$grids[$group]->form_submit_value = __('Delete');
		}
		
		// deleting hosts from  monitoring
		if ($_POST && isset($_POST['host_id']))
		{
			$result = $monitor_host_model->delete_hosts(array_keys($_POST['host_id']), 'id');
			status::success('Monitoring has been successfully deactivated.');
			
			$this->redirect(Path::instance()->previous());
		}
		
		$title = __('Monitoring');
		
		$count_down_devices = ORM::factory('preprocessor')->count_off_down_devices();
		
		if ($count_down_devices > 0)
		{
			$title .= " ($count_down_devices)";
		}
		
		$view = new View('main');
		$view->title = $title;
		$view->content = new View('monitoring/show_all');
		$view->content->form = $form;
		$view->content->filter_form = $filter_form;
		$view->content->grids = $grids;
		$view->content->labels = $labels;
		$view->content->title = $title;
		$view->render(TRUE);
	}
	
	/**
	 * Shows detail of monitored device
	 * 
	 * @author Michal Kliment
	 * @param type $monitor_host_id 
	 */
	public function show ($monitor_host_id = NULL)
	{
		// bad parameter
		if (!$monitor_host_id || !is_numeric($monitor_host_id))
			Controller::warning (PARAMETER);
		
		$monitor_host = new Monitor_host_Model($monitor_host_id);
		
		// record doesn't exist
		if (!$monitor_host->id)
			Controller::error (RECORD);
		
		// access control
		if (!$this->acl_check_view('Monitoring_Controller', 'monitoring',
			$monitor_host->device->user->member_id))
		{
			Controller::error(ACCESS);
		}
		
		$title = __('Monitoring detail of device').' '.$monitor_host->device->name;
		
		$view = new View('main');
		$view->title = $title;
		$view->content = new View('monitoring/show');
		$view->content->monitor_host = $monitor_host;
		$view->render(TRUE);
	}
	
	/**
	 * Edits notification settings of host
	 * 
	 * @author Michal Kliment
	 * @param type $monitor_host_id 
	 */
	public function edit($monitor_host_id = NULL)
	{
		// bad parameter
		if (!$monitor_host_id || !is_numeric($monitor_host_id))
			Controller::warning(PARAMETER);
		
		$monitor_host = new Monitor_host_Model($monitor_host_id);
		
		// record doesn't exist
		if (!$monitor_host->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_edit('Monitoring_Controller', 'monitoring',
			$monitor_host->device->user->member_id))
		{
			Controller::error(ACCESS);
		}

		$form = new Forge();

		$form->set_attr('nopopup');
		
		$form->input('priority')
			->rules('valid_numeric')
			->value($monitor_host->priority);

		$form->submit('submit');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
		
			// do everything in transaction
			try
			{
				$monitor_host->transaction_start();
				
				$monitor_host->priority = $form_data['priority'];

				$monitor_host->save_throwable();

				$monitor_host->transaction_commit();
			}
			catch(Exception $e)
			{
				$monitor_host->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot update monitoring.', $e);
			}

			$this->redirect(Path::instance()->previous());
		}
		else
		{
			$title = __('Monitoring');

			$view = new View('main');
			$view->title = $title;
			$view->content = new View('form');
			$view->content->headline = $title;
			$view->content->form = $form;
			$view->render(TRUE);
		}
	}
	
	/**
	 * Deletes host from monitoring
	 * 
	 * @author Michal Kliment
	 * @param type $monitor_host_id 
	 */
	public function delete($monitor_host_id = NULL)
	{
		// bad parameter
		if (!$monitor_host_id || !is_numeric($monitor_host_id))
			Controller::warning(PARAMETER);
		
		$monitor_host = new Monitor_host_Model($monitor_host_id);
		
		// record doesn't exist
		if (!$monitor_host->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_delete('Monitoring_Controller', 'monitoring',
			$monitor_host->device->user->member_id))
		{
			Controller::error(ACCESS);
		}
		
		// do everything in transaction
		try
		{
			$monitor_host->transaction_start();

			$monitor_host->delete_throwable();

			$monitor_host->transaction_commit();
			status::success('Monitoring has been successfully deactivated.');
		}
		catch(Exception $e)
		{
			$monitor_host->transaction_rollback();
			Log::add_exception($e);
			status::error('Error - Cannot update monitoring.', $e);
		}

		$this->redirect(Path::instance()->previous());
	}
	
	/**
	 * Performs adding/editing/deleteing hosts
	 * 
	 * @author Michal Kliment
	 * @param type $parameter
	 */
	public function action($parameter = NULL)
	{
		$action = NULL;
		
		$device_id = NULL;
		
		$actions = array('add', 'edit', 'delete');
		
		if (in_array($parameter, $actions))
			$action = $parameter;
		else
			$device_id = $parameter;
		
		$filter_form = new Filter_form();
		
		if ($filter_form->autoload())
		{
			$device_model = new Device_Model();

			$devices = $device_model->get_all_devices(array
			(
				'filter_sql' => $filter_form->as_sql()
			));

			$device_ids = array_keys(arr::from_objects($devices));
		}
		else
		{
			// bad parameter
			if (!$device_id || !is_numeric($device_id))
				Controller::warning (PARAMETER);
			
			$device = new Device_Model($device_id);
			
			// record doesn't exist
			if (!$device->id)
				Controller::error (RECORD);
			
			$device_ids = array($device->id);
		}

		$form = new Forge();

		$form->set_attr('nopopup');

		if (!$action)
		{
			$action_options = array();
			
			// access control
			if ($this->acl_check_new('Monitoring_Controller', 'monitoring',
				$device->user->member_id))
			{
				$action_options['add'] = __('Add');
			}
			
			if ($this->acl_check_edit('Monitoring_Controller', 'monitoring',
				$device->user->member_id))
			{
				$action_options['edit'] = __('Edit');
			}
			
			if ($this->acl_check_delete('Monitoring_Controller', 'monitoring',
				$device->user->member_id))
			{
				$action_options['delete'] = __('Delete');
			}
			
			$form->dropdown('action')
				->options($action_options);
		}
		
		if (!$action || ($action && $action != 'delete'))
		{
			$form->input('priority')
				->rules('valid_numeric');
		}

		$form->submit('submit');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$monitor_host_model = new Monitor_host_Model();

			// do everything in transaction
			try
			{
				$monitor_host_model->transaction_start();
				
				if (!$action)
					$action = $form_data['action'];

				switch ($action)
				{
					// adds device(s) to monitoring
					case 'add':
						// removes already monitored devices
						$device_ids = array_diff(
							$device_ids,
							arr::from_objects(
								$monitor_host_model->get_all_monitored_hosts(),
								'device_id'
							)
						);
						
						$result = $monitor_host_model->insert_hosts($device_ids, $form_data['priority']);
						status::success('Monitoring has been successfully activated.');
						break;

					// edits priority of monitored devices
					case 'edit':
						$result = $monitor_host_model->update_hosts($device_ids, $form_data['priority']);
						status::success('Monitoring has been successfully updated.');
						break;

					// deletes devices from monitoring
					case 'delete':
						$result = $monitor_host_model->delete_hosts($device_ids);
						status::success('Monitoring has been successfully deactivated.');
						break;
				}

				$monitor_host_model->transaction_commit();
			}
			catch(Exception $e)
			{
				$monitor_host_model->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot update monitoring.', $e);
			}

			$this->redirect(Path::instance()->previous());
		}
		else
		{
			$title = __('Monitoring');

			$view = new View('main');
			$view->title = $title;
			$view->content = new View('form');
			$view->content->headline = $title;
			$view->content->form = $form;
			$view->render(TRUE);
		}
	}
}
