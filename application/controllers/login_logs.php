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
 * Controller displays login logs of user to system.
 * 
 * @author	Michal Kliment
 * @package Controller
 */
class Login_logs_Controller extends Controller
{
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('login_logs/show_all');
	}

	/**
	 * Shows last time login of users
	 * 
	 * @author Michal Kliment
	 */
	public function show_all($limit_results = 50, $page_word = null, $page = 1)
	{
		// access
		if (!$this->acl_check_view('Settings_Controller', 'system'))
			Controller::error(ACCESS);
		
		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		$login_log_model = new Login_log_Model();

		$total_login_logs = $login_log_model->count_all_login_logs();

		if (($sql_offset = ($page - 1) * $limit_results) > $total_login_logs)
			$sql_offset = 0;

		$login_logs = $login_log_model->get_all_login_logs(
				$sql_offset, (int) $limit_results
		);

		// create grid
		$grid = new Grid('login_logs/show_all', __('Login logs'), array
		(
				'use_paginator'				=> true,
				'use_selector'				=> true,
				'current'					=> $limit_results,
				'selector_increace'			=> 50,
				'selector_min'				=> 50,
				'selector_max_multiplier'	=> 10,
				'uri_segment'				=> 'page',
				'base_url'					=> Config::get('lang') . '/login_logs/show_all/'
											. $limit_results,
				'total_items'				=> $total_login_logs,
				'items_per_page'			=> $limit_results,
				'style'						=> 'classic',
				'order_by'					=> 'last_time',
				'order_by_direction'		=> 'desc',
				'limit_results'				=> $limit_results
		));

		$grid->field('id');
		
		$grid->field('name');
		
		$grid->field('last_time')
				->label(__('Last time login'));
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action()
				->icon_action('member')
				->url('users/show')
				->label('Show user');
		
		$actions->add_action()
				->icon_action('login')
				->url('login_logs/show_by_user')
				->label('Show logins of user');

		$grid->datasource($login_logs);

		$view = new View('main');
		$view->title = __('Login logs');
		$view->breadcrumbs = __('Login logs');
		$view->content = $grid;
		$view->render(TRUE);
	}

	/**
	 * Show login logs of user
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_by_user(
			$user_id = null, $limit_results = 200, $order_by = 'time',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{		
		if (!isset($user_id))
			Controller::warning(PARAMETER);

		$user = new User_Model((int) $user_id);

		if (!$user->id)
			Controller::error(RECORD);
		
		// access
		if (!$this->acl_check_view(get_class($this), 'users', $user->member_id) &&
			!$this->acl_check_view('Settings_Controller', 'system'))
		{
			Controller::error(ACCESS);
		}

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		$login_log_model = new Login_log_Model();

		$total_login_logs = $login_log_model->count_all_login_logs_by_user($user->id);

		if (($sql_offset = ($page - 1) * $limit_results) > $total_login_logs)
			$sql_offset = 0;

		$login_logs = $login_log_model->get_all_login_logs_by_user(
				$user->id, $sql_offset, (int) $limit_results,
				$order_by, $order_by_direction
		);

		$headline = __('Login logs of user') . ' ' . $user->get_full_name();

		// create grid
		$grid = new Grid('login_logs/show_by_user', null, array
		(
				'current'					=> $limit_results,
				'selector_increace'			=> 200,
				'selector_min'				=> 200,
				'selector_max_multiplier'	=> 10,
				'uri_segment'				=> 'page',
				'base_url'					=> Config::get('lang') . '/login_logs/show_by_user/'
											. $user->id . '/' . $limit_results . '/'
											. $order_by . '/' . $order_by_direction,
				'total_items'				=> $total_login_logs,
				'items_per_page'			=> $limit_results,
				'style'						=> 'classic',
				'order_by'					=> $order_by,
				'order_by_direction'		=> $order_by_direction,
				'limit_results'				=> $limit_results,
				'variables'					=> $user->id . '/',
				'url_array_ofset'			=> 1
		));

		$grid->order_field('id');
		
		$grid->order_field('time');
		
		$grid->order_field('ip_address')
				->label(__('IP address'));

		$grid->datasource($login_logs);

		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->link('members/show/' . $user->member->id,
						'ID ' . $user->member->id . ' - ' . $user->member->name,
						$this->acl_check_view('Members_Controller', 'members', $user->member->id))
				->link('users/show_by_member/' . $user->member_id, 'Users',
						$this->acl_check_view(get_class($this), 'users', $user->member_id))
				->link('users/show/' . $user->id,
						$user->name . ' ' . $user->surname . ' (' . $user->login . ')',
						$this->acl_check_view(get_class($this), 'users', $user->member_id))
				->text('Login logs');

		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

}
