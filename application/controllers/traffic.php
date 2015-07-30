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
 * Traffic controller manages control over members traffic.
 *
 * @author  Kliment Michal
 * @package Controller
 */
class Traffic_Controller extends Controller
{
	/**
	 * Units
	 *
	 * @var array[string]
	 */
	public static $units = array
	(
		'kB', 'MB', 'GB', 'TB'
	);
	
	/**
	 * Contructs controller, only to setting of menu
	 *
	 * @author Michal Kliment
	 */
	public function __construct()
	{
		parent::__construct();

		// contains items of menu
		$this->sections = array
		(
			url_lang::base().'traffic/show_all'		=> __('Total traffic'),
			url_lang::base().'traffic/ip_addresses'	=> __('Traffic of IP addresses'),
			url_lang::base().'traffic/members'		=> __('Traffic of members')
		);

		// time of next update of ulogd
		$this->ulogd_update_next = Settings::get('ulogd_update_last')
				+ Settings::get('ulogd_update_interval');

		// time of previous update ulogd
		$this->ulogd_update_prev = Settings::get('ulogd_update_last')
				- Settings::get('ulogd_update_interval');
	}
	
	/**
	 * Redirects to show all
	 *
	 * @author Michal Kliment
	 */
	public function index()
	{
		url::redirect('traffic/show_all');
	}
	
	public function show_all ($type = 'daily', $limit_results = 50,
			$order_by = 'day', $order_by_direction = 'DESC', $page_word = null,
			$page = 1)
	{
		$allowed_types = array('daily', 'monthly', 'yearly');
		$default_order_by = array('day', 'month', 'year');
		
		// access control
		if (!$this->acl_check_view('Ulogd_Controller','member'))
			Controller::error(ACCESS);
		
		// get new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		$type_number = array_search($type, $allowed_types);
		
		// load default order by if is not set
		if (!$order_by)
			$order_by = $default_order_by[$type_number];
		
		// filter
		
		$filter_form = new Filter_form('d');
	
		if ($type_number == 0)
		{
			$month_before = time() - 60 * 60 * 24 * 30;
			
			$filter_form->add('date')
					->type('date');
		}
		
		if ($type_number == 1)
		{
			$month6_before = time() - 60 * 60 * 24 * 30 * 6;
			
			$filter_form->add('month')
					->type('select_number')
					->values(array
					(
						1 => __('January'),
						2 => __('February'),
						3 => __('March'),
						4 => __('April'),
						5 => __('May'),
						6 => __('June'),
						7 => __('July'),
						8 => __('August'),
						9 => __('September'),
						10 => __('October'),
						11 => __('November'),
						12 => __('December')
					));
		}
		
		if ($type_number == 2)
		{
			$filter_form->add('year')
					->type('number')
					->values(date::years());
		}
		
		$filter_form->add('upload')
				->type('number');
		
		$filter_form->add('download')
				->type('number');
		
		if ($type_number > 0)
		{
			$filter_form->add('avg_upload')
					->label(__('Avarage day upload'))
					->type('number');
			
			$filter_form->add('avg_download')
					->label(__('Avarage day download'))
					->type('number');
		}
		
		$members_traffics_daily_model = new Members_traffic_Model();
		
		$total_traffics = $members_traffics_daily_model->count_total_traffics(
				$type, $filter_form->as_sql()
		);
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_traffics)
			$sql_offset = 0;
		
		$traffics = $members_traffics_daily_model->get_total_traffics(
				$type, $sql_offset, $limit_results,
				$order_by, $order_by_direction, $filter_form->as_sql()
		);
		
		$grid = new Grid('members', null, array
		(
			'current'					=> $limit_results,
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'total_items'				=> $total_traffics,
			'selector_increace'			=> 20,
			'selector_min' 				=> 20,
			'base_url'					=> Config::get('lang').'/traffic/show_all/'
										.$type.'/'.$limit_results.'/'
										.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'style'						=> 'classic',
			'items_per_page'			=> $limit_results,
			'filter'					=> $filter_form
		));
		
		if ($type_number == 0)
		{
			$grid->order_field('day')
					->class('center');
		}
		
		if ($type_number == 2 || $type_number == 1)
		{
			$grid->order_field('year')
					->class('center');
		}
		
		if ($type_number == 1)
		{
			$grid->order_callback_field('month')
					->callback('callback::month_field')
					->class('center');
		}
		
		$grid->order_callback_field('local_upload')
				->callback('callback::traffic_upload_field');
		
		$grid->order_callback_field('local_download')
				->callback('callback::traffic_download_field')
				->class('right');
		
		$grid->order_callback_field('foreign_upload')
				->callback('callback::traffic_upload_field');
		
		$grid->order_callback_field('foreign_download')
				->callback('callback::traffic_download_field')
				->class('right');
		
		$grid->order_callback_field('upload')
				->callback('callback::traffic_upload_field');
		
		$grid->order_callback_field('download')
				->callback('callback::traffic_download_field')
				->class('right');
		
		if ($type_number > 0)
		{
			$grid->callback_field('upload')
					->label('Avarage day upload')
					->callback('callback::members_traffic_avg_field', $type)
					->class('right');
			
			$grid->callback_field('download')
					->label('Avarage day download')
					->callback('callback::members_traffic_avg_field', $type)
					->class('right');
		}
		
		$grid->datasource($traffics);
		
		$arr_types = array();
		
		foreach ($allowed_types as $allowed_type)
		{
			$arr_types[] = __(''.$allowed_type);
		}
		
		// form to group by type
		$form = new Forge(url::base(TRUE).url::current(TRUE));
		
		$form->dropdown('type')
				->label('Group by')
				->options($arr_types)
				->selected($type_number);
		
		$form->submit('Submit');
		
		if ($form->validate())
		{
			url::redirect('traffic/show_all/'.$allowed_types[$form->type->value]);
		}
		
		$view = new View('main');
		$view->google_jsapi_enabled = TRUE;
		$view->content = new View('traffic/show_all');
		$view->content->js_data_array_str = '';
		
		$current_unit_id = 0;
		// due to bug in Google Chart it draw graph only if there are more than 1 record 
		if ($total_traffics > 1)
		{		
			$div = 1;
		
			// finds ideal unit of transmitted data
			if ($avg = $members_traffics_daily_model->avg_total_traffics($type))
			{
				$val = ($avg->upload > $avg->download) ? $avg->upload : $avg->download;
			
				while (($val /= $div) > 1024)
				{
					$div *= 1024;
					$current_unit_id++;
				}
			}
			
			$traffics = $members_traffics_daily_model->get_total_traffics(
					$type, 0, 0, '', '', $filter_form->as_sql()
			);
			
			$time = strtotime(min($traffics)->day);
			
			foreach ($traffics as $traffic)
			{	
				switch ($type)
				{
					case 'daily':
						$text = $traffic->day;
						$title = __('Day');
						break;
					case 'monthly':
						$text = $traffic->month.'/'.$traffic->year;
						$title = __('Month');
						break;
					case 'yearly':
						$text = $traffic->year;
						$title = __('Year');
						break;
				}
				
				$view->content->total_js_data_array_str .= "
					['$text',
					".num::decimal_point(round($traffic->upload/$div,2)).",
					".num::decimal_point(round($traffic->download/$div,2)).",
					".num::decimal_point(round($traffic->local_upload/$div,2)).",
					".num::decimal_point(round($traffic->local_download/$div,2)).",
					".num::decimal_point(round($traffic->foreign_upload/$div,2)).",
					".num::decimal_point(round($traffic->foreign_download/$div,2)).",
					],";
				
				$time += 86400;
			}
		}
		
		$title = __('Total traffic');
		
		$view->title = $title;
		$view->breadcrumbs = __('Traffic');
		$view->content->title = $title;
		$view->content->total_traffics = $total_traffics;
		$view->content->current_unit_id = $current_unit_id;
		$view->content->grid = $grid;
		$view->content->form = $form;
		$view->render(TRUE);
	}

	/**
	 * Shows actual traffic of ip addresses
	 *
	 * @author Michal Kliment
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page
	 */
	public function ip_addresses(
			$limit_results = 20, $order_by = '', $order_by_direction = 'DESC',
			$page_word = null, $page = 1)
	{
		// access control
		if (!$this->acl_check_view('Ulogd_Controller','ip_address'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		// by default orders by ulogd's type of traffic of active members
		if ($order_by == '')
			$order_by = Settings::get('ulogd_active_type');
		
		// ASC or DESC
		if (strtolower($order_by_direction) != 'asc')
			$order_by_direction = 'DESC';
		
		$filter_form = new Filter_form();
		
		$filter_form->add('ip_address')
			->type('network_address');
		
		$filter_form->add('local_upload')
			->type('number');
		
		$filter_form->add('local_download')
			->type('number');
		
		$filter_form->add('foreign_upload')
			->type('number');
		
		$filter_form->add('foreign_download')
			->type('number');
		
		$filter_form->add('upload')
			->type('number')
			->label('Total upload');
		
		$filter_form->add('download')
			->type('number')
			->label('Total download');
		
		$filter_form->add('member_name');

		$ip_addresses_traffic_model = new Ip_addresses_traffic_Model();

		// counts all traffics of ip addresses
		$total_ip_addresses_traffics = $ip_addresses_traffic_model->count_all_ip_addresses_traffics($filter_form->as_sql());

		if (($sql_offset = ($page - 1) * $limit_results) > $total_ip_addresses_traffics)
			$sql_offset = 0;

		// returns all traffics of ip addresses
		$ip_addresses_traffics = $ip_addresses_traffic_model->get_all_ip_addresses_traffics(
				$sql_offset, (int) $limit_results, $order_by, $order_by_direction,
				$filter_form->as_sql()
		);

		// create grid
		$grid = new Grid('actual_ip_addresses_traffic', '', array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 20,
			'selector_min'				=> 20,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/traffic/ip_addresses/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_ip_addresses_traffics,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'			=> $filter_form
		));
		
		// first number for order number field
		// direction for order number field
		if ($order_by_direction == 'DESC')
		{
			$first_number = $sql_offset;
			$direction = 1;
		}
		else
		{
			$first_number = $total_ip_addresses_traffics-($page-1)*$limit_results+1;
			$direction = -1;
		}

		$grid->order_callback_field(Settings::get('ulogd_active_type'))
				->label(__('Order'))
				->callback('callback::ulogd_order_number_field', $first_number, $direction)
				->help(help::hint('ulogd_order'))
				->class('right');
		
		$grid->callback_field('ip_address')
				->label(__('Ip address'))
				->class('right')
				->order(FALSE)
				->callback('callback::ulogd_ip_address_field');
		
		$grid->order_callback_field('local_upload')
				->label(__('Local upload'))
				->callback('callback::ulogd_traffic_field')
				->class('right');
		
		$grid->order_callback_field('local_download')
				->label(__('Local download'))
				->callback('callback::ulogd_traffic_field')
				->class('right');
		
		$grid->order_callback_field('foreign_upload')
				->label(__('Foreign upload'))
				->callback('callback::ulogd_traffic_field')
				->class('right');
		
		$grid->order_callback_field('foreign_download')
				->label(__('Foreign download'))
				->callback('callback::ulogd_traffic_field')
				->class('right');
		
		$grid->order_callback_field('upload')
				->label(__('Total upload'))
				->callback('callback::ulogd_traffic_field')
				->class('right');
		
		$grid->order_callback_field('download')
				->label(__('Total download'))
				->callback('callback::ulogd_traffic_field')
				->class('right');
		
		$grid->order_callback_field('member_id')
				->label(__('Member'))
				->callback('callback::ulogd_member_field', __('Is not in system').'!');

		$grid->datasource($ip_addresses_traffics);
		
		$title = __('Traffic of IP addresses');

		$view = new View('main');
		$view->breadcrumbs = __('Traffic');
		$view->title = $title;
		$view->content = new View('traffic/show');
		$view->content->headline = $title;
		$view->content->grid = $grid;
		$view->content->current = 'actual_ip_addresses_traffic';
		$view->content->text = __('Traffic for the period').': '.
				date('Y/m/d H:i:s', $this->ulogd_update_prev) . ' - '.
				date('Y/m/d H:i:s', Settings::get('ulogd_update_last')).
				', '.__('Next update').': '.
				date('Y/m/d H:i:s',$this->ulogd_update_next);
		
		$view->render(TRUE);
	}

	/**
	 * Shows traffic of members in time
	 *
	 * @author Michal Kliment
	 * @param date $date_from
	 * @param date $date_to
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page
	 */
	public function members(
			$type = NULL, $limit_results = 20,
			$order_by = NULL, $order_by_direction = 'DESC',
			$page_word = NULL, $page = 1)
	{
		// type cannot be empty
		if (!$type)
			$this->redirect($this->url().'/daily');
		
		$allowed_types = array('daily', 'monthly', 'yearly');
		$default_order_by = array('day', 'month', 'year');
		
		// access control
		if (!$this->acl_check_view('Ulogd_Controller','member'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		$type_number = array_search($type, $allowed_types);
		
		// load default order by if is not set
		if (!$order_by)
			$order_by = $default_order_by[$type_number];
		
		// ASC or DESC
		if (strtolower($order_by_direction) != 'asc')
			$order_by_direction = 'DESC';
		
		// filter
		$filter_form = new Filter_form('d');
	
		if ($type_number == 0)
		{
			$filter_form->add('day')
					->type('date');
		}
		
		if ($type_number == 1)
		{
			$filter_form->add('month')
					->type('select_number')
					->values(array
					(
						1 => __('January'),
						2 => __('February'),
						3 => __('March'),
						4 => __('April'),
						5 => __('May'),
						6 => __('June'),
						7 => __('July'),
						8 => __('August'),
						9 => __('September'),
						10 => __('October'),
						11 => __('November'),
						12 => __('December')
					));
		}
		
		if ($type_number != 0)
		{
			$filter_form->add('year')
					->type('number')
					->values(date::years());
		}
		
		$filter_form->add('upload')
				->type('number');
		
		$filter_form->add('download')
				->type('number');
		
		$filter_form->add('local_upload')
				->type('number');
		
		$filter_form->add('local_download')
				->type('number');

		$members_traffic_model = new Members_traffic_Model();

		// count all daily traffics of members
		$total_members_traffics = $members_traffic_model->count_all_members_traffics(
				$type, $filter_form->as_sql()
		);

		if (($sql_offset = ($page - 1) * $limit_results) > $total_members_traffics)
			$sql_offset = 0;

		// returns all daily traffics of members
		$members_traffics = $members_traffic_model->get_all_members_traffics(
				$type, $sql_offset, $limit_results,
				$order_by, $order_by_direction, $filter_form->as_sql()
		);

		// create grid
		$grid = new Grid('time_members_traffic', '', array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 20,
			'selector_min'				=> 20,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/traffic/members/'
										.$type.'/'.$limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_members_traffics,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'variables'					=> $type.'/',
			'url_array_ofset'			=> 1,
			'filter'			=> $filter_form
		));
		
		// first number for order number field
		// direction for order number field
		if ($order_by_direction == 'DESC')
		{
			$first_number = $sql_offset;
			$direction = 1;
		}
		else
		{
			//$first_number = $total_ip_addresses_traffics-($page-1)*$limit_results+1;
			$first_number = 0;
			$direction = -1;
		}

		$grid->order_callback_field(Settings::get('ulogd_active_type'))
				->label(__('Order'))
				->callback('callback::order_number_field', $first_number, $direction)
				->help(help::hint('ulogd_order'))
				->class('right');
		
		$grid->callback_field('member_id')
				->label(__('Member'))
				->callback('callback::member_field')
				->order(FALSE);
		
		$grid->order_callback_field('local_upload')
				->label(__('Local upload'))
				->callback('callback::traffic_upload_field');
		
		$grid->order_callback_field('local_download')
				->label(__('Local download'))
				->callback('callback::traffic_download_field')
				->class('right');
		
		$grid->order_callback_field('foreign_upload')
				->label(__('Foreign upload'))
				->callback('callback::traffic_upload_field');
		
		$grid->order_callback_field('foreign_download')
				->label(__('Foreign download'))
				->callback('callback::traffic_download_field')
				->class('right');
		
		$grid->order_callback_field('upload')
				->label(__('Total upload'))
				->callback('callback::traffic_upload_field');
		
		$grid->order_callback_field('download')
				->label(__('Total download'))
				->callback('callback::traffic_download_field')
				->class('right');
		
		if ($type_number == 0)
		{
			$grid->field('day')
					->label(__('Day'))
					->class('center');
			
			$grid->callback_field('active')
				->label(__('Active'))
				->callback('callback::active_field')
				->class('center')
				->help(help::hint('ulogd_active_button'));
		}
		
		if ($type_number == 1)
		{
			$grid->callback_field('month')
					->label(__('Month'))
					->callback('callback::month_field')
					->class('center');
		}
		
		if ($type_number == 1 || $type_number == 2)
		{
			$grid->field('year')
					->label(__('Year'))
					->class('center');
		}

		$grid->datasource($members_traffics);
		
		$arr_types = array();
		
		foreach ($allowed_types as $allowed_type)
		{
			$arr_types[] = __(''.$allowed_type);
		}
		
		// form to group by type
		$form = new Forge(url::base(TRUE).url::current(TRUE));
		
		$form->dropdown('type')
				->label(__('Group by').':')
				->options($arr_types)
				->selected($type_number);
		
		$form->submit('Submit');
		
		if ($form->validate())
		{
			url::redirect(
					"traffic/members/".
					$allowed_types[$form->type->value]
			);
		}
		
		$title = __('Traffic of members');

		$view = new View('main');
		$view->breadcrumbs = __('Traffic');
		$view->title = $title;
		$view->content = new View('traffic/show');
		$view->content->headline = $title;
		$view->content->grid = $form.'<br />'.$grid;
		$view->content->current = 'time_members_traffic';
		$view->render(TRUE);
	}
	
	/**
	 * Shows traffics of member
	 * 
	 * @author Michal Kliment
	 * @param type $member_id
	 * @param type $type
	 * @param type $limit_results
	 * @param string $order_by
	 * @param type $order_by_direction
	 * @param type $page_word
	 * @param type $page 
	 */
	public function show_by_member (
			$member_id = NULL, $type = 'daily')
	{
		$allowed_types = array('daily', 'monthly', 'yearly');
		$default_order_by = array('day', 'month', 'year');
		
		// bad parameter
		if (!$member_id || !is_numeric($member_id) || !in_array($type, $allowed_types))
			Controller::warning(PARAMETER);
		
		$member = new Member_Model($member_id);
		
		// member doesn't exist
		if (!$member->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_view('Ulogd_Controller','member', $member->id))
			Controller::error(ACCESS);
		
		// get new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		$type_number = array_search($type, $allowed_types);
		
		// load default order by if is not set
		$order_by = $default_order_by[$type_number];
		
		// filter
		
		$filter_form = new Filter_form('d');
	
		$filter_form->add('day')
				->type('date');
		
		if ($type_number == 1)
		{
			$filter_form->add('month')
					->type('select_number')
					->values(array
					(
						1 => __('January'),
						2 => __('February'),
						3 => __('March'),
						4 => __('April'),
						5 => __('May'),
						6 => __('June'),
						7 => __('July'),
						8 => __('August'),
						9 => __('September'),
						10 => __('October'),
						11 => __('November'),
						12 => __('December')
					));
		}
		
		if ($type_number > 0)
		{
			$filter_form->add('year')
					->type('number')
					->values(date::years());
		}
		
		$filter_form->add('upload')
				->type('number');
		
		$filter_form->add('download')
				->type('number');
		
		if ($type_number > 0)
		{
			$filter_form->add('year')
					->type('number')
					->values(date::years());
			
			$filter_form->add('avg_upload')
					->label(__('Avarage day upload'))
					->type('number');
			
			$filter_form->add('avg_download')
					->label(__('Avarage day download'))
					->type('number');
		}
		
		
		$members_traffic_model = new Members_traffic_Model();
		
		$traffics = $members_traffic_model->get_member_traffics(
				$member->id, $type, $order_by, $filter_form->as_sql()
		);
		
		$time = strtotime($member->entrance_date);
		
		switch ($type)
		{
			case 'daily':
				
				$before_2_months = time() - 86400 * 31 * 2;
				
				if ($before_2_months > $time)
				{
					$time = $before_2_months;
				}
				
				break;

			case 'monthly':
				
				$before_2_years = time() - 86400 * 356 * 2;
				
				if ($before_2_years > $time)
				{
					$time = $before_2_years;
				}
				
				break;
		}
		
		$to_time = time();
		$not_date = '0000-00-00';
		
		foreach ($filter_form->as_array() as $filter)
		{
			if ($filter['key'] == 'day')
			{
				if (is_array($filter['value']))
				{
					$filter['value'] = $filter['value'][0];
				}
					
				switch ($filter['op'])
				{					
					case Filter_form::OPER_EQUAL:
						$time = strtotime($filter['value']);
						$to_time = strtotime($filter['value']);
						break;
					
					case Filter_form::OPER_EQUAL_NOT:
						$not_date = $filter['value'];
						break;
					
					case Filter_form::OPER_SMALLER:
						$to_time = strtotime($filter['value']);
						break;
					
					case Filter_form::OPER_SMALLER_OR_EQUAL:
						$to_time = strtotime($filter['value'])+86400;
						break;
					
					case Filter_form::OPER_GREATER:
						$time = strtotime($filter['value'])+86400;
						break;
					
					case Filter_form::OPER_GREATER_OR_EQUAL:
						$time = strtotime($filter['value']);
						break;
				}
			}
		}
			
		$arr_traffics = array();
		
		while ($time < $to_time)
		{
			$day	= date('Y-m-d', $time);
			$year	= date('Y', $time);
			$month	= date('n', $time);
			
			$time += 86400;
			
			if ($day == $not_date)
				continue;

			$traffic = new stdClass();
			$traffic->upload = 0;
			$traffic->download = 0;
			$traffic->local_upload = 0;
			$traffic->local_download = 0;
			$traffic->foreign_upload = 0;
			$traffic->foreign_download = 0;
			$traffic->avg_upload = 0;
			$traffic->avg_download = 0;
			$traffic->day = $day;
			$traffic->month = $month;
			$traffic->year = $year;

			switch ($type)
			{
				case 'daily':
					$arr_traffics[$day] = $traffic;
					break;

				case 'monthly':
					$arr_traffics[substr($day,0,7)] = $traffic;
					break;

				case 'yearly':
					$arr_traffics[$year] = $traffic;
					break;
			}
		}
		
		$arr_traffics = array_merge($arr_traffics, $traffics);
		
		$total_traffics = count($arr_traffics);
		
		$grid = new Grid('members', null, array
		(
			'use_paginator'	   		=> false,
			'use_selector'	   		=> false,
			'filter'			=> $filter_form
		));
		
		if ($type_number == 0)
		{
			$grid->field('day')
					->class('center');
		}
		
		if ($type_number == 1)
		{
			$grid->callback_field('month')
					->callback('callback::month_field')
					->class('center');
		}
		
		if ($type_number > 0)
		{
			$grid->field('year')
					->class('center');
		}
		
		$grid->callback_field('upload')
				->callback('callback::traffic_field')
				->class('right');
		
		$grid->callback_field('download')
				->callback('callback::traffic_field')
				->class('right');
		
		if ($type_number > 0)
		{
			$grid->callback_field('avg_upload')
					->label('Avarage day upload')
					->callback('callback::traffic_field')
					->class('right');
			
			$grid->callback_field('avg_download')
					->label('Avarage day download')
					->callback('callback::traffic_field')
					->class('right');
		}
		
		$grid->datasource(array_reverse($arr_traffics));
		
		$arr_types = array();
		
		foreach ($allowed_types as $allowed_type)
		{
			$arr_types[] = __(''.$allowed_type);
		}
		
		// form to group by type
		$form = new Forge(url::base(TRUE).url::current(TRUE));
		
		$form->dropdown('type')
				->label('Group by')
				->options($arr_types)
				->selected($type_number);
		
		$form->submit('Submit');
		
		if ($form->validate())
		{
			url::redirect(
					"traffic/show_by_member/$member_id/".
					$allowed_types[$form->type->value]
			);
		}
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller','members'))
				->disable_translation()
				->link('members/show/'.$member->id,
						"ID $member->id - $member->name",
						$this->acl_check_view(
								'Members_Controller','members', $member->id
						)
				)
				->enable_translation()
				->text('Show traffic');
		
		$view = new View('main');
		$view->google_jsapi_enabled = TRUE;
		$view->content = new View('traffic/show_by_member');
		$view->content->js_data_array_str = '';
		
		$current_unit_id = 0;
		// due to bug in Google Chart it draw graph only if there are more than 1 record 
		if ($total_traffics > 1)
		{		
			$div = 1;
		
			// finds ideal unit of transmitted data
			if ($avg = $members_traffic_model->avg_member_traffics($member->id, $type))
			{
				$val = ($avg->upload > $avg->download) ? $avg->upload : $avg->download;
			
				while (($val /= $div) > 1024)
				{
					$div *= 1024;
					$current_unit_id++;
				}
			}
			
			$traffics = $members_traffic_model->get_member_traffics(
					$member->id, $type, 0, 0, '', '', $filter_form->as_sql()
			);
			
			foreach ($traffics as $traffic)
			{	
				switch ($type)
				{
					case 'daily':
						$text = $traffic->day;
						$title = __('Day');
						break;
					case 'monthly':
						$text = $traffic->month.'/'.$traffic->year;
						$title = __('Month');
						break;
					case 'yearly':
						$text = $traffic->year;
						$title = __('Year');
						break;
				}
				
				$view->content->js_data_array_str .= "
					['$text',
					".num::decimal_point(round($traffic->upload/$div,2)).",
					".num::decimal_point(round($traffic->download/$div,2)).",
					".num::decimal_point(round($traffic->local_upload/$div,2)).",
					".num::decimal_point(round($traffic->local_download/$div,2)).",
					".num::decimal_point(round($traffic->foreign_upload/$div,2)).",
					".num::decimal_point(round($traffic->foreign_download/$div,2)).",
					],";
			}
			
		}
		
		$view->title = __('Traffic of member').' '.$member->name;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content->member = $member;
		$view->content->title = __('Traffic of member').' '.$member->name;
		$view->content->total_traffics = $total_traffics;
		$view->content->current_unit_id = $current_unit_id;
		$view->content->grid = $grid;
		$view->content->form = $form;
		$view->render(TRUE);
	}
}
