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
 * Controller of logs.
 * Table logs is handled as partitioned table, it needs at least MySQL ver. 5.1.
 * Table partitions are created by scheduler.
 * Logs are preserved for maximum of 30 days.
 * 
 * @author Ondřej Fibich
 * @package Controller
 */
class Logs_Controller extends Controller
{
	/**
	 * Redirects to show_all
	 * 
	 * @author Ondřej Fibich
	 */
	public function index()
	{
		url::redirect('logs/show_all');
	}

	/**
	 * Show all actions logs and enable filter them.
	 * 
	 * @author Ondřej Fibich
	 */
	public function show_all($limit_results = 50, $page_word = null, $page = 1)
	{
		if (!$this->acl_check_view('Logs_Controller', 'logs'))
			Controller::error(ACCESS);
		
		if (Settings::get('action_logs_active') != 1)
			url::redirect('settings/logging');

		// gets new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');

		$filter_form = new Filter_form('l');
		
		$filter_form->add('table_name');
		
		$filter_form->add('time')
				->type('date');
		
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
		
		$filter_form->add('action')
				->type('select')
				->values(array
				(
					Log_Model::ACTION_ADD		=> __('Added'),
					Log_Model::ACTION_DELETE		=> __('Deleted'),
					Log_Model::ACTION_UPDATE		=> __('Updated')
				));
		
		$filter_form->add('object_id')
				->type('number');
		
		$log_model = new Log_Model();

		$total_logs = $log_model->count_all_logs($filter_form->as_sql());

		if (($sql_offset = ($page - 1) * $limit_results) > $total_logs)
			$sql_offset = 0;

		// load logs
		$logs = $log_model->get_all_logs(
				$sql_offset, (int) $limit_results, $filter_form->as_sql()
		);

		// title of view
		$title = __('Action logs');

		// create grid
		$grid = new Grid('logs/show_all', $title, array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min'				=> 50,
			'selector_max_multiplier'	=> 10,
			'uri_segment'				=> 'page',
			'base_url'					=> Config::get('lang')
										. '/logs/show_all/' . $limit_results,
			'total_items'				=> $total_logs,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> 'id',
			'order_by_direction'		=> 'DESC',
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));

		$grid->field('id');
		
		$grid->callback_field('user_id')
				->label('User')
				->callback('callback::user_id_log_field');
		
		$grid->callback_field('action')
				->callback('callback::log_action_field');
		
		$grid->field('table_name')
				->label('Table');
		
		$grid->callback_field('object_id')
				->label('Object')
				->callback('callback::object_log_field');
		
		$grid->field('time');

		$grid->datasource($logs);

		$view = new View('main');
		$view->title = __('Action logs');
		$view->breadcrumbs = __('Action logs');
		$view->content = $grid;
		$view->render(TRUE);
	}

	/**
	 * Show logs of user
	 * 
	 * @author Ondřej Fibich
	 * @param integer $user_id
	 */
	public function show_by_user($user_id = null, $limit_results = 200,
			$page_word = null, $page = 1)
	{
		if (!$this->acl_check_view('Logs_Controller', 'logs'))
			Controller::error(ACCESS);

		if (!isset($user_id))
			Controller::warning(PARAMETER);
		
		if (Settings::get('action_logs_active') != 1)
			url::redirect('settings/logging');

		$user = new User_Model((int) $user_id);

		if (!$user->id)
			Controller::error(RECORD);

		// gets new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');

		$log_model = new Log_Model();

		$total_logs = $log_model->count_all_users_logs($user_id);

		if (($sql_offset = ($page - 1) * $limit_results) > $total_logs)
			$sql_offset = 0;

		$title = __('Action logs of user')
				. ' ' . $user->name . ' ' . $user->surname;

		// load logs
		$logs = $log_model->get_all_users_logs(
				$user->id, $sql_offset, (int) $limit_results
		);

		// create grid
		$grid = new Grid('logs/show_all', '', array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 200,
			'selector_min'				=> 200,
			'selector_max_multiplier'	=> 10,
			'uri_segment'				=> 'page',
			'base_url'					=> Config::get('lang')
										. '/logs/show_by_user/' . $user_id . '/'
										. $limit_results,
			'total_items'				=> $total_logs,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> 'id',
			'order_by_direction'		=> 'DESC',
			'limit_results'				=> $limit_results
		));

		$grid->field('id');
		
		$grid->callback_field('user_id')
				->label('User')
				->callback('callback::user_id_log_field');
		
		$grid->callback_field('action')
				->label('Action')
				->callback('callback::log_action_field');
		
		$grid->field('table_name')
				->label('Table');
		
		$grid->callback_field('object_id')
				->label('Object')
				->callback('callback::object_log_field');
		
		$grid->field('time');

		$grid->datasource($logs);
		
		$breadcrumbs = breadcrumbs::add()
				->link('logs/show_all', 'Action logs',
						$this->acl_check_view('Logs_Controller', 'logs'))
				->text($user->get_full_name() . ': ' . $user->login);

		$view = new View('main');
		$view->title = __('Action logs');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('show_all');
		$view->content->headline = $title;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Show logs of user
	 * 
	 * @author Ondřej Fibich
	 * @param string $table
	 * @param integer $object_id
	 */
	public function show_object(
			$table, $object_id = null, $limit_results = 200,
			$page_word = null, $page = 1)
	{
		if (!$this->acl_check_view('Logs_Controller', 'logs'))
			Controller::error(ACCESS);
		
		if (!is_numeric($object_id) || !is_string($table))
			Controller::warning(PARAMETER);
		
		if (Settings::get('action_logs_active') != 1)
			url::redirect('settings/logging');

		// gets new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');

		$log_model = new Log_Model();

		$total_logs = $log_model->count_all_object_logs($table, $object_id);

		if (($sql_offset = ($page - 1) * $limit_results) > $total_logs)
			$sql_offset = 0;

		$title = __('Action logs of object');

		// load logs
		$logs = $log_model->get_all_object_logs(
				$table, $object_id, $sql_offset, (int) $limit_results
		);

		// create grid
		$grid = new Grid('logs/show_all', '', array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 200,
			'selector_min'				=> 200,
			'selector_max_multiplier'	=> 10,
			'uri_segment'				=> 'page',
			'base_url'					=> Config::get('lang')
										. '/logs/show_object/' . $table . '/'
										. $object_id . '/' . $limit_results,
			'total_items'				=> $total_logs,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> 'id',
			'order_by_direction'		=> 'DESC',
			'limit_results'				=> $limit_results
		));

		$grid->field('id');
		
		$grid->callback_field('user_id')
				->label('User')
				->callback('callback::user_id_log_field');
		
		$grid->callback_field('action')
				->label('Action')
				->callback('callback::log_action_field');
		
		$grid->field('table_name')
				->label('Table');
		
		$grid->callback_field('object_id')
				->label('Object')
				->callback('callback::object_log_field');
		
		$grid->field('time');
	
		$grid->callback_field('values')
				->label('Changed values')
				->callback('callback::value_log_field');

		$grid->datasource($logs);
		
		$breadcrumbs = breadcrumbs::add()
				->link('logs/show_all', 'Action logs',
						$this->acl_check_view('Logs_Controller', 'logs'))
				->disable_translation()
				->text(__('Object') . ' ' . $table . ':' . $object_id);

		$view = new View('main');
		$view->title = __('Action logs');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('show_all');
		$view->content->headline = $title;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

}
