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
 * Manages connection requests of users.
 * 
 * @author Ondřej Fibich
 * @package	Controller
 */
class Connection_Requests_Controller extends Controller
{

	/**
	 * Index redirect to show all
	 */
	public function index()
	{
		url::redirect('connection_requests/show_all');
	}

	/**
	 * Shows all requests.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'created_at',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{
		if (!$this->acl_check_view('Connection_Requests_Controller', 'request'))
		{
			Controller::error(ACCESS);
		}
		
		if (!Settings::get('connection_request_enable'))
		{
			status::warning('Connection requests are not enabled.');
			url::redirect('settings/system');
		}

		if (is_numeric($this->input->post('record_per_page')))
		{
			$limit_results = (int) $this->input->post('record_per_page');
		}
		
		$allowed_order_type = array
		(
			'id', 'member_name', 'user_name', 'created_at', 'ip_address',
			'mac_address', 'comment', 'state'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
		{
			$order_by = 'id';
		}
		
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		// filter
		$filter_form = new Filter_form();
		
		$filter_form->add('state')
				->type('select')
				->values(Connection_request_Model::get_state_messages());
		
		$filter_form->add('member_name')
				->callback('json/member_name');
		
		$filter_form->add('member_id')
				->type('number');
		
		$filter_form->add('created_at')
				->type('date')
				->label('Time');
		
		$filter_form->add('ip_address');
		
		$filter_form->add('mac_address');
		
		// get data
		$cr_model = new Connection_request_Model();
		
		$total = $cr_model->count_all_connection_requests($filter_form->as_sql());
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total)
			$sql_offset = 0;
		
		$connection_requests = $cr_model->get_all_connection_requests(
				$sql_offset, $limit_results, $order_by, $order_by_direction,
				$filter_form->as_sql()
		);

		$headline = __('Connection requests');
		
		$grid = new Grid('connection_request', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 500,
			'selector_min'				=> 500,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang')
										. '/connection_requests/show_all/' . $limit_results
										. '/' . $order_by . '/' . $order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form->html()
		));
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_link_field('member_id', 'member_name')
				->link('members/show', 'member_name')
				->label('Member');
		
		$grid->order_link_field('user_id')
				->link('users/show', 'user_name')
				->label('Added by');
		
		$grid->order_callback_field('state')
				->callback('callback::connection_request_state_field');
		
		$grid->order_field('created_at')
				->label('Time');
		
		$grid->order_field('mac_address')
				->label('MAC address');
		
		$grid->order_field('ip_address')
				->label('IP address');
		
		if ($this->acl_check_view('Subnets_Controller', 'subnet'))
		{
			$grid->order_link_field('subnet_id')
					->link('subnets/show', 'subnet_name')
					->label('Subnet');
		}
		else
		{
			$grid->order_field('subnet_name')
					->label('Subnet');
		}
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_edit('Connection_Requests_Controller', 'request') &&
			$this->acl_check_new('Devices_Controller', 'devices'))
		{
			$actions->add_conditional_action()
					->condition('is_connection_request_undecided')
					->icon_action('approve')
					->url('connection_requests/approve_request');
		}
		
		if ($this->acl_check_edit('Connection_Requests_Controller', 'request'))
		{
			$actions->add_conditional_action()
					->condition('is_connection_request_undecided')
					->icon_action('reject')
					->url('connection_requests/reject_request')
					->class('confirm_reject');
		}
		
		$actions->add_action()
				->icon_action('show')
				->url('connection_requests/show');
		
		if ($this->acl_check_edit('Connection_Requests_Controller', 'request'))
		{
			$actions->add_conditional_action()
					->condition('is_connection_request_undecided')
					->icon_action('edit')
					->url('connection_requests/edit')
					->class('popup_link');
		}
		
		$grid->datasource($connection_requests);

		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $headline;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Shows all requests od member.
	 * 
	 * @param integer $member_id
	 */
	public function show_by_member($member_id = NULL)
	{
		if (!is_numeric($member_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$member = new Member_Model($member_id);
		
		if (!$member || !$member->id)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_view('Connection_Requests_Controller', 'request', $member->id))
		{
			Controller::error(ACCESS);
		}
		
		if (!Settings::get('connection_request_enable'))
		{
			status::warning('Connection requests are not enabled.');
			url::redirect('settings/system');
		}
		
		// get data
		$cr_model = new Connection_request_Model();
		
		$total = $cr_model->count_all_connection_requests_of_member($member_id);
		
		$connection_requests = $cr_model->get_all_connection_requests_of_member(
				$member_id
		);

		$headline = __('List of connection requests');
		
		$grid = new Grid('messages', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> $total
		));
		
		$grid->field('id')
				->label('ID');
		
		$grid->link_field('member_id', 'member_name')
				->link('members/show', 'member_name')
				->label('Member');
		
		$grid->link_field('user_id')
				->link('users/show', 'user_name')
				->label('Added by');
		
		$grid->callback_field('state')
				->callback('callback::connection_request_state_field');
		
		$grid->field('created_at')
				->label('Time');
		
		$grid->field('mac_address')
				->label('MAC address');
		
		$grid->field('ip_address')
				->label('IP address');
		
		if ($this->acl_check_view('Subnets_Controller', 'subnet'))
		{
			$grid->link_field('subnet_id')
					->link('subnets/show', 'subnet_name')
					->label('Subnet');
		}
		else
		{
			$grid->field('subnet_name')
					->label('Subnet');
		}
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action()
				->icon_action('show')
				->url('connection_requests/show');
		
		if ($this->acl_check_edit('Connection_Requests_Controller', 'request'))
		{
			$actions->add_conditional_action()
					->condition('is_connection_request_undecided')
					->icon_action('edit')
					->url('connection_requests/edit')
					->class('popup_link');
		}
		
		$grid->datasource($connection_requests);
		
		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members')
				)->disable_translation()
				->link('members/show/' . $member->id,
						'ID ' . $member->id . ' - ' . $member->name,
						$this->acl_check_view('Members_Controller', 'members', $member->id)
				)->enable_translation()
				->text('Connection requests');
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Shows a request.
	 * 
	 * @param integer $connection_request_id
	 */
	public function show($connection_request_id = NULL)
	{
		if (!is_numeric($connection_request_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$cr_model = new Connection_request_Model($connection_request_id);
		
		if (!$cr_model || !$cr_model->id)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_view('Connection_Requests_Controller', 'request', $cr_model->member_id))
		{
			Controller::error(ACCESS);
		}
		
		if (!Settings::get('connection_request_enable'))
		{
			status::warning('Connection requests are not enabled.');
			url::redirect('settings/system');
		}
		
		// comments grid
		$comment_model = new Comment_Model();
		
		$comments = $comment_model->get_all_comments_by_comments_thread(
				$cr_model->comments_thread_id
		);

		$comments_grid = new Grid('comments', NULL, array
		(
			'separator'		   		=> '<br /><br />',
			'use_paginator'	   		=> FALSE,
			'use_selector'	   		=> FALSE,
		));

		$url = ($cr_model->comments_thread_id) ?
				'comments/add/'.$cr_model->comments_thread_id :
				'comments/add_thread/connection_request/'.$cr_model->id;

		if ($this->acl_check_edit('Connection_Requests_Controller', 'request'))
		{
			$comments_grid->add_new_button(
					$url, 'Add comment to connection request',
					array('class' => 'popup_link')
			);
		}

		$comments_grid->field('text');

		if ($this->acl_check_view('Users_Controller', 'users'))
		{
			$comments_grid->link_field('user_id')
					->link('users/show', 'user_name')
					->label('User');
		}
		else
		{
			$comments_grid->field('user_name')
					->label('User');
		}

		$comments_grid->field('datetime')
				->label('Time');

		$actions = $comments_grid->grouped_action_field();

		$actions->add_conditional_action()
				->icon_action('edit')
				->url('comments/edit')
				->condition('is_own')
				->class('popup_link');

		$actions->add_conditional_action()
				->icon_action('delete')
				->url('comments/delete')
				->condition('is_own')
				->class('delete_link');

		$comments_grid->datasource($comments);
		
		$headline = __('Connection request');
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('connection_requests/show_all', 'Connection requests',
						$this->acl_check_view('Connection_requests_Controller', 'request'))
				->disable_translation()
				->text($cr_model->ip_address . ' (' . $cr_model->mac_address . ')');
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('connection_requests/show');
		$view->content->headline = $headline;
		$view->content->connection_request = $cr_model;
		$view->content->comments_grid = $comments_grid;
		
		$view->render(TRUE);
	}

	/**
	 * Add new request.
	 * 
	 * @param integer $subnet_id Subnet in which the IP addres belongs
	 * @param string $ip_address Requested IP address
	 */
	public function add($subnet_id = NULL, $ip_address = NULL)
	{		
		if (!$this->acl_check_new('Connection_Requests_Controller', 'request', $this->member_id))
		{
			Controller::error(ACCESS);
		}
		
		if (!Settings::get('connection_request_enable'))
		{
			status::warning('Connection requests are not enabled.');
			url::redirect('settings/system');
		}
		
		$subnet_model = new Subnet_Model($subnet_id);
		
		if (!$subnet_model || !$subnet_model->id)
		{
			Controller::warning(PARAMETER);
		}
		
		if (!valid::ip($ip_address))
		{
			Controller::warning(PARAMETER);
		}
		
		$subnet = $subnet_model->get_net_and_mask_of_subnet();
		
		if (!valid::ip_check_subnet(ip2long($ip_address), $subnet->net + 0, ip2long($subnet->netmask)))
		{
			Controller::warning(PARAMETER);
		}
		
		// someone stole that IP, or someone tried to hack FN.
		if ($subnet_model->get_subnet_for_connection_request($ip_address) == NULL)
		{
			status::warning('IP address is not available anymore.');
			url::redirect('connection_requests/show_by_member/' . $this->member_id);
		}
		
		// check if someone does not requested for this IP already
		$cr_model = new Connection_request_Model();
		$prevs = $cr_model->get_undecided_connection_with_ip($ip_address);
		if ($prevs->count() > 0)
		{
			// wait you have done this already
			if ($prevs->current()->member_id == $this->member_id)
			{
				status::warning('You have already requested for the same connection.');
			}
			else
			{
				status::warning('Someone have already requested for the same connection.');
			}
			url::redirect('connection_requests/show_by_member/' . $this->member_id);
		}
		unset($prevs);
		
		// all device templates
		$arr_device_templates = array
		(
			NULL => ''
		) + ORM::factory('device_template')->select_list();
		
		// enum types for device
		$enum_type_model = new Enum_type_Model();
		$types = $enum_type_model->get_values(Enum_type_Model::DEVICE_TYPE_ID);
		
		// throw away unallowed types		
		if (Settings::get('connection_request_device_types'))
		{
			$allowed_types = explode(':', Settings::get('connection_request_device_types'));
			
			foreach ($types as $key => $val)
			{
				if (array_search($key, $allowed_types) === FALSE)
				{
					unset($types[$key]);
				}
			}
		}
		
		$types[NULL] = '--- '.__('Select type').' ---';
		asort($types);
		
		// get MAC address using SNMP to DHCP server
		if (!$this->session->get('connection_request_mac'))
		{			
			$ip_address_model = new Ip_address_Model();
			$gateway = $ip_address_model->get_gateway_of_subnet($subnet_id);
			
			if ($gateway && $gateway->id)
			{
				$mac_address = '';
				
				// first try CGI scripts
				if (module::e('cgi'))
				{
					$vars = arr::to_object(array
					(
						'GATEWAY_IP_ADDRESS'	=> $gateway->ip_address,
						'IP_ADDRESS'			=> $ip_address
					));

					$url = text::object_format($vars, Settings::get('cgi_arp_url'));

					$mac_address = @file_get_contents($url);
				}
				
				// now try SNMP
				if (!valid::mac_address($mac_address) && module::e('snmp'))
				{
					try
					{
						$snmp = Snmp_Factory::factoryForDevice($gateway->ip_address);

						// try find MAC address in DHCP
						$mac_address = $snmp->getDHCPMacAddressOf($ip_address);
					}
					catch (DHCPMacAddressException $e)
					{
						try
						{
							// try find MAC address in ARP table
							$mac_address = $snmp->getARPMacAddressOf($ip_address);
						}
						catch(Exception $e)
						{
							Log::add_exception($e);
							status::mwarning($e->getMessage());
						}
					}
					catch (Exception $e)
					{
						Log::add_exception($e);
						status::mwarning($e->getMessage());
					}
				}

				$this->session->set('connection_request_mac', $mac_address);
			}
		}
		
		// form
		$form = new Forge();
		
		if ($this->acl_check_new('Devices_Controller', 'devices'))
		{
			// if an admin added a member before, select this member in dropdown
			$selected_mid = $this->session->get('last_added_member_id', $this->member_id);
			
			$form->dropdown('member_id')
					->label('Connection owner')
					->options(ORM::factory('member')->select_list_grouped())
					->selected($selected_mid)
					->rules('required')
					->style('width:200px');
		}
		
		$form->dropdown('device_type_id')
				->options($types)
				->selected(Settings::get('connection_request_device_default_type'))
				->label('Device type')
				->rules('required')
				->style('width:200px')
				->help('connection_request_device_type');
		
		if ($this->acl_check_new('Devices_Controller', 'devices'))
		{
			$form->dropdown('device_template_id')
					->options($arr_device_templates)
					->rules('required')
					->label('Device template')
					->style('width:200px');
		}
		
		if (!$this->session->get('connection_request_mac') ||
			$this->acl_check_new('Devices_Controller', 'devices'))
		{ // admin or not detected
			$form->input('mac_address')
					->label('MAC address of device')
					->value($this->session->get('connection_request_mac'))
					->rules('required|valid_mac_address')
					->help('connection_request_mac_address');
		}
		
		// show comment only for non-engeneers
		if (!$this->acl_check_new('Devices_Controller', 'devices'))
		{
			$form->textarea('note');
		}
		
		$form->submit('Send');
		
		// post
		if ($form->validate())
		{
			$form_data = $form->as_array();
			$cr_model->clear();
			
			if (!isset($form_data['member_id']))
			{
				$form_data['member_id'] = $this->member_id;
			}
			
			if (!isset($form_data['device_template_id']))
			{
				$form_data['device_template_id'] = NULL;
			}
			
			try
			{
				$cr_model->transaction_start();
				
				$cr_model->member_id = $form_data['member_id'];
				$cr_model->added_user_id = $this->user_id;
				$cr_model->created_at = date('Y-m-d H:i:s');
				$cr_model->ip_address = $ip_address;
				
				if (!$this->session->get('connection_request_mac') ||
					$this->acl_check_new('Devices_Controller', 'devices'))
				{
					$cr_model->mac_address = $form_data['mac_address'];
				}
				else
				{
					$cr_model->mac_address = $this->session->get('connection_request_mac');
				}
				
				$cr_model->subnet_id = $subnet_model->id;
				$cr_model->device_type_id = $form_data['device_type_id'];
				$cr_model->device_template_id = $form_data['device_template_id'];
				
				if (!$this->acl_check_new('Devices_Controller', 'devices'))
				{
					$cr_model->comment = $form_data['note'];
				}
				
				$cr_model->save_throwable();
				
				$cr_model->transaction_commit();
			
				// make next action
				if ($this->acl_check_edit('Connection_Requests_Controller', 'request') &&
					$this->acl_check_new('Devices_Controller', 'devices'))
				{
					// redirect to add form
					$this->session->del('connection_request_mac');
					url::redirect('connection_requests/approve_request/' . $cr_model->id);
				}
				else if (module::e('notification'))
				{
					// create comment for user
					$comment = '<table>'
							 . '<tr><th>' . __('IP address') . ':</th>'
							 . '<td>' . $cr_model->ip_address . '</td></tr>'
							 . '<tr><th>' . __('Device type') . ':</th>'
							 . '<td>' . $cr_model->device_type->get_value() . '</td></tr>'
							 . '<tr><th>' . __('Date') . ':</th>'
							 . '<td>' . $cr_model->created_at . '</td></tr>'
							 . '</table>';

					// trigger notice for member
					Message_Model::activate_special_notice(
							Message_Model::CONNECTION_REQUEST_INFO,
							$cr_model->member_id, $this->session->get('user_id'),
							Notifications_Controller::ACTIVATE,
							Notifications_Controller::KEEP, $comment
					);
				
					status::success(
							__('Your request has been succesfully stored.') . ' ' .
							__('You will be informed about it\'s result by email.'),
							FALSE
					);
					
					$this->session->del('connection_request_mac');
					$this->redirect('connection_requests/show/', $cr_model->id);
				}
			}
			catch (Exception $e)
			{
				$cr_model->transaction_rollback();
				Log::add_exception($e);
				status::error('Cannot add connection request.', $e);
			}
		}
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('connection_requests/show_all', 'Connection requests',
						$this->acl_check_view('Connection_requests_Controller', 'request'))
				->text('Add new request for connection');

		$headline = __('Add new request for connection');
		
		// default info
		if (trim(Settings::get('connection_request_info')) == '')
		{
			// info for engeneer
			if ($this->acl_check_view('Connection_Requests_Controller', 'request'))
			{
				$info = url_lang::lang('help.connection_request_info_short');
			}
			// info for user
			else
			{
				$info = url_lang::lang('help.connection_request_info');
			}
		}
		// set uped info
		else
		{
			$info = Settings::get('connection_request_info');
		}

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->form = $form->html();
		$view->content->headline = $headline;
		$view->content->link_back = $info;
		$view->render(TRUE);
	}

	/**
	 * Edit request.
	 * 
	 * @param integer $connection_request_id
	 */
	public function edit($connection_request_id = NULL)
	{
		if (!is_numeric($connection_request_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$cr_model = new Connection_request_Model($connection_request_id);
		
		if (!$cr_model || !$cr_model->id)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_edit('Connection_Requests_Controller', 'request'))
		{
			Controller::error(ACCESS);
		}
		
		if (!Settings::get('connection_request_enable'))
		{
			status::warning('Connection requests are not enabled.');
			url::redirect('settings/system');
		}
			
		if ($cr_model->state != Connection_request_Model::STATE_UNDECIDED)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_edit('Connection_Requests_Controller', 'request'))
		{
			Controller::error(ACCESS);
		}
		
		// all device templates
		$arr_device_templates = array
		(
			NULL => ''
		) + ORM::factory('device_template')->select_list();
		
		// enum types for device
		$enum_type_model = new Enum_type_Model();
		$types = $enum_type_model->get_values(Enum_type_Model::DEVICE_TYPE_ID);
		$types[NULL] = '----- '.__('Select type').' -----';
		asort($types);
		
		// form
		$form = new Forge();
		
		$form->hidden('crform_is_edit');
		
		$form->dropdown('member_id')
				->label('Connection owner')
				->options(ORM::factory('member')->select_list_grouped())
				->selected($cr_model->member_id)
				->rules('required')
				->style('width:200px');
		
		$form->dropdown('device_type_id')
				->options($types)
				->selected($cr_model->device_type_id)
				->label('Device type')
				->rules('required')
				->style('width:200px');
		
		$form->dropdown('device_template_id')
				->options($arr_device_templates)
				->rules('required')
				->label('Device template')
				->selected($cr_model->device_template_id)
				->style('width:200px');
		
		$form->input('mac_address')
				->label('MAC address of device')
				->rules('required|valid_mac_address')
				->value($cr_model->mac_address);
		
		$form->textarea('note')
				->value($cr_model->comment);
		
		$form->submit('Save');
		
		// post
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				$cr_model->transaction_start();
				
				$cr_model->member_id = $form_data['member_id'];
				$cr_model->mac_address = $form_data['mac_address'];
				$cr_model->device_type_id = $form_data['device_type_id'];
				$cr_model->device_template_id = $form_data['device_template_id'];
				$cr_model->comment = $form_data['note'];
				$cr_model->save_throwable();
				
				$cr_model->transaction_commit();
				
				status::success('Connection request has been succesfully edited.');
				$this->redirect('connection_requests/show/', $cr_model->id);
			}
			catch (Exception $e)
			{
				$cr_model->transaction_rollback();
				Log::add_exception($e);
				status::error('Cannot edit connection request.', $e);
			}
		}
		
		$headline = __('Edit request for connection');
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('connection_requests/show_all', 'Connection requests',
						$this->acl_check_view('Connection_requests_Controller', 'request'))
				->disable_translation()
				->link('connection_requests/show/' . $cr_model->id,
						$cr_model->ip_address . ' (' . $cr_model->mac_address . ')',
						$this->acl_check_view('Connection_requests_Controller', 'request', $cr_model->member_id))
				->text($headline);

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
	 * Approve the connection request.
	 * If all informations are set, a redirection to add device is made.
	 * Otherwise, user first must fill in missing informations.
	 * 
	 * @param integer $connection_request_id
	 */
	public function approve_request($connection_request_id = NULL)
	{
		if (!is_numeric($connection_request_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$cr_model = new Connection_request_Model($connection_request_id);
		
		if (!$cr_model || !$cr_model->id)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_edit('Connection_Requests_Controller', 'request'))
		{
			Controller::error(ACCESS);
		}
		
		if (!Settings::get('connection_request_enable'))
		{
			status::warning('Connection requests are not enabled.');
			url::redirect('settings/system');
		}
			
		if ($cr_model->state != Connection_request_Model::STATE_UNDECIDED)
		{
			Controller::error(RECORD);
		}
		
		// some info were not entered?
		if (!$cr_model->device_template_id)
		{
			$templates = array
			(
				NULL => '--- ' . __('Select template') . ' ---'
			);
			
			// find templates for given device type
			$device_templates = ORM::factory('device_template')
					->where('enum_type_id', $cr_model->device_type_id)
					->select_list();
			
			// if only one teplate in this type -> set it and redirect (#813)
			if (count($device_templates) == 1)
			{
				try
				{
					$cr_model->transaction_start();

					$cr_model->device_template_id = key($device_templates);
					$cr_model->save_throwable();

					$cr_model->transaction_commit();
					
					// hard redirect, no $this->redirect here!
					url::redirect('devices/add_from_connection_request/' . $cr_model->id);
				}
				catch (Exception $e)
				{
					$cr_model->transaction_rollback();
					Log::add_exception($e);
					status::error('Cannot edit connection request.', $e);
				}
			}
			
			// add templates
			$templates += $device_templates;
				
			// form
			$form = new Forge();

			$form->dropdown('device_template_id')
					->options($templates)
					->rules('required')
					->label('Device template')
					->style('width:200px');

			$form->submit('Continue');

			// post
			if ($form->validate())
			{
				$form_data = $form->as_array();

				try
				{
					$cr_model->transaction_start();

					$cr_model->device_template_id = $form_data['device_template_id'];
					$cr_model->save_throwable();

					$cr_model->transaction_commit();
					
					// hard redirect, no $this->redirect here!
					url::redirect('devices/add_from_connection_request/' . $cr_model->id);
				}
				catch (Exception $e)
				{
					$cr_model->transaction_rollback();
					Log::add_exception($e);
					status::error('Cannot edit connection request.', $e);
				}
			}
			
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('connection_requests/show_all', 'Connection requests',
							$this->acl_check_view('Connection_requests_Controller', 'request'))
					->disable_translation()
					->link('connection_requests/show/' . $cr_model->id,
							$cr_model->ip_address . ' (' . $cr_model->mac_address . ')',
							$this->acl_check_view('Connection_requests_Controller', 'request', $cr_model->member_id))
					->enable_translation()
					->text('Approve connection request');
			
			// view
			$headline = __('Approve connection request') . ': ' . __('Step') . ' 1';
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->form = $form->html();
			$view->content->headline = $headline;
			$view->render(TRUE);
		}
		else
		{
			// hard redirect, no $this->redirect here!
			url::redirect('devices/add_from_connection_request/' . $cr_model->id);
		}
	}

	/**
	 * Reject the connection request.
	 * 
	 * @param integer $connection_request_id
	 */
	public function reject_request($connection_request_id = NULL)
	{
		if (!is_numeric($connection_request_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$cr_model = new Connection_request_Model($connection_request_id);
		
		if (!$cr_model || !$cr_model->id)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_edit('Connection_Requests_Controller', 'request'))
		{
			Controller::error(ACCESS);
		}
		
		if (!Settings::get('connection_request_enable'))
		{
			status::warning('Connection requests are not enabled.');
			url::redirect('settings/system');
		}
			
		if ($cr_model->state != Connection_request_Model::STATE_UNDECIDED)
		{
			Controller::error(RECORD);
		}
		
		try
		{
			$cr_model->transaction_start();
		
			// set data
			$cr_model->state = Connection_request_Model::STATE_REJECTED;
			$cr_model->decided_at = date('Y-m-d H:i:s');
			$cr_model->decided_user_id = $this->user_id;
			$cr_model->save_throwable();
			
			$cr_model->transaction_commit();
			
			// only if user sended request by him self
			if (module::e('notification') &&
				$cr_model->member_id == $cr_model->added_user->member_id)
			{
				$link = html::anchor(
						'/connection_requests/show/' . $cr_model->id,
						FALSE, FALSE, FALSE, FALSE
				);
				// create comment for user
				$comment = '<table>'
						 . '<tr><th>' . __('Rejected by') . ':</th>'
						 . '<td>' . $cr_model->decided_user->get_full_name() . '</td></tr>'
						 . '<tr><th>' . __('Date') . ':</th>'
						 . '<td>' . $cr_model->decided_at . '</td></tr>'
						 . '<tr><th>' . __('Details') . ':</th>'
						 . '<td>' . $link . '</td></tr>'
						 . '</table>';

				// trigger notice for member (if request was not added by some other)
				Message_Model::activate_special_notice(
						Message_Model::CONNECTION_REQUEST_REFUSE,
						$cr_model->member_id, $this->user_id,
						Notifications_Controller::ACTIVATE,
						Notifications_Controller::ACTIVATE, $comment
				);
			}
			
			status::success('Connection request has been rejected.');
		}
		catch (Exception $e)
		{
			$cr_model->transaction_rollback();
			Log::add_exception($e);
			status::error('Cannot reject connection request.', $e);
		}
		
		$this->redirect('connection_requests/show/'. $connection_request_id);
	}

}
