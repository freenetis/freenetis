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
 * Controller performs device' logs actions.
 *  
 * @package Controller
 */
class Device_logs_Controller extends Controller
{
	/**
	 * Bridge between syslog mysql API and Freenetis's filter form library
	 * @var array
	 */
	protected static $opers = array
	(
	    Filter_form::OPER_CONTAINS		=> 'contains',
	    Filter_form::OPER_CONTAINS_NOT	=> 'containsnot',
	    Filter_form::OPER_IS		=> 'is',
	    Filter_form::OPER_EQUAL		=> 'is',
	    Filter_form::OPER_IS_NOT		=> 'isnot',
	    Filter_form::OPER_EQUAL_NOT		=> 'isnot',
	    Filter_form::OPER_SMALLER		=> 'smaller',
	    Filter_form::OPER_GREATER_OR_EQUAL	=> 'smallernot',
	    Filter_form::OPER_GREATER		=> 'greater',
	    Filter_form::OPER_SMALLER_OR_EQUAL	=> 'greaternot',
	);
	
	/**
	 * Shows all logs of all devices
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page 
	 */
	public function show_all ($limit_results = 50, $order_by = 'datetime',
			$order_by_direction = 'DESC', $page_word = null, $page = 1)
	{
		// access control
		if (!$this->acl_check_view('Devices_Controller', 'devices'))
			Controller::error(ACCESS);
		
		$filter_form = new Filter_form();
		
		$filter_form->add('host');
		
		$filter_form->add('facility');
		
		$filter_form->add('priority');
		
		$filter_form->add('level');
		
		$filter_form->add('tag');
		
		$filter_form->add('datetime')
			->label('Time')
			->type('date');
		
		$filter_form->add('program');
		
		$filter_form->add('msg')
			->label('Message');

		$data = $this->get_logs(
			$page, $limit_results, $order_by, $order_by_direction,
			$filter_form->as_array()
		);
		
		/**
		 * @todo Replace with own exception handler
		 */
		if (!$data)
			Controller::error(RECORD);

		$title = __('Show logs of device');

		// grid of devices
		$grid = new Grid('devices', null, array(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang').'/device_logs/show_all/'.
											$limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=>  $data->total,
			'items_per_page' 			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		$grid->order_field('host')
				->class('center');

		$grid->order_field('facility')
				->label(__('Facility'))
				->class('center');
		
		$grid->order_field('priority')
				->label(__('Priority'))
				->class('center');

		$grid->order_field('level')
				->label(__('Level'))
				->class('center');

		$grid->order_field('tag')
				->label(__('Tag'))
				->class('center');

		$grid->order_field('datetime')
				->label(__('Time'))
				->class('center');

		$grid->order_field('program')
				->label(__('Program'))
				->class('center');

		$grid->order_field('msg')
				->label(__('Message'))
				->class('center');

		$grid->datasource($data->rows);

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('devices/show_all', 'Devices',
						$this->acl_check_view('Devices_Controller', 'devices'))
				->text('Show logs of device');

		$view = new View('main');
		$view->title = $title;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('show_all');
		$view->content->headline = $title;
		$view->content->table = $grid;
		$view->render(TRUE);

	}
	
	/**
	 * Shows logs belong to device
	 * 
	 * @author Michal Kliment
	 * @param integer $device_id
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page 
	 */
	public function show_by_device ($device_id = NULL, $limit_results = 500, $order_by = 'datetime',
			$order_by_direction = 'DESC', $page_word = null, $page = 1)
	{
		// bad paremeter
		if (!$device_id || !is_numeric($device_id))
			Controller::warning (PARAMETER);

		$device = new Device_Model($device_id);

		// device doesn't exist
		if (!$device->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view('Devices_Controller', 'devices', $device->user->member_id))
			Controller::error(ACCESS);
		
		$filter_form = new Filter_form(NULL, url_lang::current(3));
		
		$filter_form->add('facility');
		
		$filter_form->add('priority');
		
		$filter_form->add('level');
		
		$filter_form->add('tag');
		
		$filter_form->add('datetime')
			->label('Time')
			->type('date');
		
		$filter_form->add('program');
		
		$filter_form->add('msg')
			->label('Message');

		$ip_address_model = new Ip_address_Model();

		$ip_addresses = $ip_address_model->get_ip_addresses_of_device($device->id);
		
		$ips = array();
		foreach ($ip_addresses as $ip_address)
			$ips[] = $ip_address->ip_address;

		$data = $this->get_logs($page, $limit_results, $order_by,
			$order_by_direction, $filter_form->as_array(), $ips
		);
		
		/**
		 * @todo Replace with own exception handler
		 */
		if (!$data)
			Controller::error(RECORD);

		$title = __('Show logs of device');

		// grid of devices
		$grid = new Grid('devices', null, array(
			'current'					=> $limit_results,
			'selector_increace'			=> 500,
			'selector_min' 				=> 500,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang').'/device_logs/show_by_device/'.
											$device_id.'/'.$limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=>  $data->total,
			'items_per_page' 			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'variables'					=> $device_id . '/',
			'url_array_ofset'			=> 1,
			'filter'					=> $filter_form
		));

		$grid->order_field('facility')
				->label(__('Facility'))
				->class('center');
		
		$grid->order_field('priority')
				->label(__('Priority'))
				->class('center');

		$grid->order_field('level')
				->label(__('Level'))
				->class('center');

		$grid->order_field('tag')
				->label(__('Tag'))
				->class('center');

		$grid->order_field('datetime')
				->label(__('Time'))
				->class('center');

		$grid->order_field('program')
				->label(__('Program'))
				->class('center');

		$grid->order_field('msg')
				->label(__('Message'))
				->class('center');

		$grid->datasource($data->rows);

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
				->text('Show logs of device');

		$view = new View('main');
		$view->title = $title;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('show_all');
		$view->content->headline = $title;
		$view->content->table = $grid;
		$view->render(TRUE);

	}
	
	/**
	 * Returns all logs depending on input data
	 * 
	 * @author Michal Kliment
	 * @param integer $page
	 * @param type $count
	 * @param type $order_by
	 * @param type $order_by_direction
	 * @param type $filter
	 * @param type $ip_addresses
	 * @return type 
	 */
	private function get_logs ($page = 1, $count = 200,
		$order_by = 'datetime', $order_by_direction = 'desc',
		$filter = array(), $ip_addresses = array())
	{
		if (!Settings::get('syslog_ng_mysql_api_enabled'))
			return NULL;
		
		$URL = Settings::get('syslog_ng_mysql_api_url')
			.'&order_by='.$order_by
			.'&order_by_direction='.$order_by_direction
			.'&page='.$page
			.'&count='.$count;

		foreach ($ip_addresses as $ip_address)
			$URL .= '&host[]='.urlencode ($ip_address);
		
		foreach ($filter as $filter_item)
		{
			if (!isset(self::$opers[$filter_item['op']]))
				continue;
			
			foreach ($filter_item['value'] as $value)
			{
				$URL .= '&'.$filter_item['key']
					.'_'.self::$opers[$filter_item['op']]
					.'[]='.  urlencode($value);
			}
		}

		$json = @file_get_contents($URL);

		$data = json_decode($json);
		
		return $data;
	}
}

?>
