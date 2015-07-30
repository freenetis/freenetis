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
 * VoIP controller for calls.
 * Calls are managed by lbilling from remote server.
 *
 * @see Billing
 * @author  Sevcik Roman
 * @package Controller
 */
class VoIP_calls_Controller extends Controller
{
	/**
	 * Only check whether is VoIP enabled
	 * 
	 * @author Michal Kliment
	 */
	public function __construct()
	{
	    parent::__construct();
	    
	    // voip is not enabled, quit
	    if (!Settings::get('voip_enabled'))
		Controller::error (ACCESS);
	}
    
	/**
	 * Redirects to show all
	 */	
	public function index()
	{
		url::redirect('voip_calls/show_all');
	}

	/**
	 * Shows VoIP calls
	 *
	 * @param integer $from
	 * @param imteger $to
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page 
	 */
	public function show_all(
			$from = null, $to = null, $limit_results = 100, $order_by = 'type',
			$order_by_direction = 'asc', $page_word = null, $page = 1)
	{
		if (!$this->acl_check_view('VoIP_Controller', 'voip'))
			Controller::error(ACCESS);

		$billing = Billing::instance();
		
		if (!$billing->has_driver() ||
			!$billing->test_conn())
		{
			$view = new View('main');
			$view->title = __('Error - VoIP not enabled');
			$view->content = new View('voip/not_enabled');
			$view->render(TRUE);
			exit;
		}

		if (is_numeric($from))
			$from = (int) $from;
		else
			$from = 0;

		if (is_numeric($to))
			$to = (int) $to;
		else
			$to = time();

		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		// parameters control
		$allowed_order_type = array('type', 'callee');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'type';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		$partner_calls = $billing->get_partner_calls($from, $to);

		$calls = NULL;
		$count = 0;

		if (isset($partner_calls->calls))
		{
			$calls = $partner_calls->calls;
			$calls = array_reverse($calls);
			$count = count($calls);

			if ($limit_results < $count)
			{
				$new = 0;
				$out = array();

				$i = (($page - 1) * $limit_results);

				if (($i + $limit_results) > $count)
					$max = $count;
				else
					$max = $i + $limit_results;

				for ($i; $i < $max; $i++)
				{
					$out[$new] = $calls[$i];


					$new++;
				}
				unset($calls);
				$calls = $out;
				unset($out);
			}
		}
		
		$grid = new Grid('voip_calls', __('List of all calls'), array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 100,
			'selector_min'				=> 100,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang') . '/voip_calls/show_all/'
										. $from . '/' . $to . '/' . $limit_results
										. '/' . $order_by . '/' . $order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $count,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results
		));

		$grid->callback_field('type')
				->callback('VoIP_calls_Controller::type')
				->class('center');
		
		$grid->callback_field('caller')
				->callback('VoIP_calls_Controller::caller_partner');
		
		$grid->callback_field('callcon')
				->callback('VoIP_calls_Controller::callcon_partner');
		
		$grid->callback_field('callcon')
				->label(__('Destination'))
				->callback('VoIP_calls_Controller::destination')
				->class('center');
		
		$grid->field('start_date')
				->label(__('Date'));
		
		$grid->callback_field('end_date')
				->label(__('Length'))
				->callback('VoIP_calls_Controller::call_length');
		
		$grid->callback_field('rate_sum')
				->label(__('Price'))
				->callback('callback::money');
		
		$grid->datasource($calls);
		
		$breadcrumbs = breadcrumbs::add()
				->link('voip/show_all', 'VoIP')
				->text('List of calls');
		
		$view = new View('main');
		$view->title = __('List of calls');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = $grid;
		$view->render(TRUE);
	}

	/**
	 * Shows VoIP calls by member
	 *
	 * @param integer $member_id
	 * @param integer $from
	 * @param integer $to
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page 
	 */
	public function show_by_member(
			$member_id = null, $from = null, $to = null, $limit_results = 100,
			$order_by = 'type', $order_by_direction = 'asc', $page_word = null,
			$page = 1)
	{
		// parameter is wrong
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// member doesn't exist
		if (!$member->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view('VoIP_Controller', 'voip', $member->id))
			Controller::error(ACCESS);

		// for searching in private contacts
		$arr_users = array();
		$user_id = NULL;
		if ($this->session->get('member_id') == $member->id)
		{
			// always searchs in logged user's private contacts
			$arr_users[] = $user_id = $this->session->get('user_id');

			// main user of member also see private contacts of other users of member
			if ($this->session->get('user_type') == User_Model::MAIN_USER)
			{
				$users = ORM::factory('user')->select('id')->where(array
						(
							'id !='		=> $this->session->get('user_id'),
							'member_id'	=> $this->session->get('member_id')
						))->find_all();
				
				foreach ($users as $user)
					$arr_users[] = $user->id;
			}
		}
		
		$billing = Billing::instance();

		if (!$billing->has_driver() ||
			!$billing->get_account($member->id))
		{
			$view = new View('main');
			$view->title = __('Error - VoIP not enabled');
			$view->content = new View('voip/not_enabled');
			$view->render(TRUE);
			exit;
		}

		if (is_numeric($from))
			$from = (int) $from;
		else
			$from = 0;

		if (is_numeric($to))
			$to = (int) $to;
		else
			$to = time();

		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		// parameters control
		$allowed_order_type = array('type', 'callee');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'type';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		$account_calls = $billing->get_account_calls($member->id, $from, $to);
		
		$calls = NULL;
		$count = 0;

		if (isset($account_calls->calls))
		{
			$calls = $account_calls->calls;
			$calls = array_reverse($calls);
			$count = count($calls);

			if ($limit_results < $count)
			{
				$new = 0;
				$out = array();

				$i = (($page - 1) * $limit_results);

				if (($i + $limit_results) > $count)
					$max = $count;
				else
					$max = $i + $limit_results;

				for ($i; $i < $max; $i++)
				{
					$out[$new] = $calls[$i];


					$new++;
				}
				unset($calls);
				$calls = $out;
				unset($out);
			}
		}

		$grid = new Grid('voip_calls', __('List of all member calls'), array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 100,
			'selector_min'				=> 100,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang').'/voip_calls/show_by_member/'
										. $member->id.'/'.$from.'/'.$to.'/'.$limit_results
										. '/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $count,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results
		));
		
		$grid->callback_field('type')
				->callback('VoIP_calls_Controller::type')
				->class('center');
		
		$grid->callback_field('caller')
				->callback('callback::voip_caller', $user_id, $arr_users);
		
		$grid->callback_field('callcon')
				->callback('callback::voip_callcon', $user_id, $arr_users);
		
		$grid->callback_field('callcon')
				->label(__('Destination'))
				->callback('VoIP_calls_Controller::destination')
				->class('center');
		
		$grid->field('start_date')
				->label(__('Date'));
		
		$grid->callback_field('end_date')
				->label(__('Length'))
				->callback('VoIP_calls_Controller::call_length');
		
		$grid->callback_field('rate_sum')
				->label(__('Price'))
				->callback('callback::money');
		
		$grid->datasource($calls);
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/'.$member->id,
						"ID $member->id - $member->name",
						$this->acl_check_view(
								'Members_Controller', 'members', $member->id
						)
				)
				->enable_translation()
				->text('List of calls');
		
		$view = new View('main');
		$view->title =  __('List of calls');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = $grid;
		$view->render(TRUE);
	}

	/**
	 * Shows VoIP calls by user
	 *
	 * @param integer $user_id
	 * @param integer $from
	 * @param integer $to
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page 
	 */
	public function show_by_user(
			$user_id = null, $from = null, $to = null, $limit_results = 100,
			$order_by = 'type', $order_by_direction = 'asc', $page_word = null,
			$page = 1)
	{
		if (!is_numeric($user_id))
			Controller::error(RECORD);
		
		$user = new User_Model($user_id);
		
		if (!$user->id)
		    Controller::error(RECORD);
		
		if (!$this->acl_check_view('VoIP_Controller', 'voip', $user->member_id))
			Controller::error(ACCESS);

		$arr_users = array();
		
		if ($this->session->get('user_id') == $user->id) 
			$arr_users = array($user->id);

		$billing = Billing::instance();
		
		if (!$billing->has_driver() || 
			!$billing->get_account($user->member_id))
		{
			$view = new View('main');
			$view->title = __('Error - VoIP not enabled');
			$view->content = new View('voip/not_enabled');
			$view->render(TRUE);
			exit;
		}		

		if (is_numeric($from))
			$from = (int) $from;
		else
			$from = 0;

		if (is_numeric($to))
			$to = (int) $to;
		else
			$to = time();

		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		// parameters control
		$allowed_order_type = array('type', 'callee');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'type';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		$account_calls = $billing->get_subscriber_calls($user_id, $from, $to);

		$calls = NULL;
		$count = 0;

		if (isset($account_calls->calls))
		{
			$calls = $account_calls->calls;
			$calls = array_reverse($calls);
			$count = count($calls);

			if ($limit_results < $count)
			{
				$new = 0;
				$out = array();

				$i = (($page - 1) * $limit_results);

				if (($i + $limit_results) > $count)
					$max = $count;
				else
					$max = $i + $limit_results;

				for ($i; $i < $max; $i++)
				{
					$out[$new] = $calls[$i];


					$new++;
				}
				unset($calls);
				$calls = $out;
				unset($out);
			}
		}

		$sip = ORM::factory('voip_sip')->where('user_id', $user_id)->find();

		$title = __('List of calls for account') . ' ' . $sip->name;
		
		$grid = new Grid('voip_calls', $title, array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 100,
			'selector_min'				=> 100,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang') . '/voip_calls/show_by_user/'
										. $user_id . '/' . $from . '/' . $to . '/'
										. $limit_results . '/' . $order_by . '/'
										. $order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $count,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results
		));
		
		$grid->callback_field('type')
				->callback('VoIP_calls_Controller::type')
				->class('center');
		
		$grid->callback_field('caller')
				->callback('callback::voip_caller', $user->id, $arr_users);
		
		$grid->callback_field('callcon')
				->callback('callback::voip_callcon', $user->id, $arr_users);
		
		$grid->callback_field('callcon')
				->label(__('Destination'))
				->callback('VoIP_calls_Controller::destination')
				->class('center');
		
		$grid->field('start_date')
				->label(__('Date'));
	
		$grid->callback_field('end_date')
				->label(__('Length'))
				->callback('VoIP_calls_Controller::call_length');
		
		$grid->callback_field('rate_sum')
				->label(__('Price'))
				->callback('callback::money');
		
		$grid->datasource($calls);
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/'.$user->member->id,
						'ID ' . $user->member->id . ' - ' . $user->member->name,
						$this->acl_check_view(
								'Members_Controller', 'members',
								$user->member->id
						)
				)->enable_translation()
				->link('users/show_by_member/' . $user->member_id,
						'Users',
						$this->acl_check_view(
								'Users_Controller', 'users',
								$user->member_id
						)
				)->disable_translation()
				->link('users/show/'.$user->id,
						$user->name . ' ' . $user->surname .
						' (' . $user->login . ')',
						$this->acl_check_view(
								'Users_Controller','users',
								$user->member_id
						)
				)->enable_translation()
				->link('voip_calls/show_by_member/'.$user->member_id,
						'List of members calls')
				->disable_translation()
				->link('voip/show/'.$user->id, $sip->name)
				->enable_translation()
				->text(__('List of calls'));
		
		// prices of calls
		$fixed_price = null;
		$cellphone_price = null;
		$voip_price = null;
		
		if (Settings::get('voip_tariff_fixed'))
		{
			$fixed_price = $billing->get_price_of_minute_call(
					$sip->name, Settings::get('voip_tariff_fixed')
			);
		}
		
		if (Settings::get('voip_tariff_cellphone'))
		{
			$cellphone_price = $billing->get_price_of_minute_call(
					$sip->name, Settings::get('voip_tariff_cellphone')
			);
		}
		
		if (Settings::get('voip_tariff_voip'))
		{
			$voip_price = $billing->get_price_of_minute_call(
					$sip->name, Settings::get('voip_tariff_voip')
			);
		}
					
		// view
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = __('List of calls');
		$view->content = new View('voip/calls');
		$view->content->sip = $sip;
		$view->content->fixed_price = $fixed_price;
		$view->content->cellphone_price = $cellphone_price;
		$view->content->voip_price = $voip_price;
		$view->content->grid = $grid;
		$view->render(TRUE);
	}
	
	/**
	 * AJAX function for getting price of call
	 *
	 * @author Ondrej Fibich
	 */
	public function voip_call_price()
	{
		$from = $this->input->get('from');
		$to = $this->input->get('to');
		
		$result = array();
		
		if (is_numeric($from) && is_numeric($to))
		{
			$billing = Billing::instance();
			
			if (!$billing->has_driver())
			{
				echo __('VoIP not enabled');
				exit;
			}
			
			if (!$billing->test_conn())
			{
				echo __('Cannot connect to VoIP server');
				exit;
			}
		
			$result = $billing->get_price_of_minute_call($from, $to);
			
			if (is_array($result))
			{
				echo number_format($result['price'], 2, ',', ' ');
				echo ' ' . $this->settings->get('currency') . ', ';
				echo $result['number'] . ', ' . $result['area'];
			}
			else
			{
				echo __('Cannot get info about call from VoIP server');
			}
		}
		else
		{
			echo __('Wrong input');
		}
	}
	
	///////////////////////////////////////////////////////////////////////////
	/// Callbacks /////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Caller partner field
	 *
	 * @param object $item
	 * @param string $name 
	 */
	protected static function caller_partner($item, $name)
	{
		$n = substr($item->caller, 4, strlen($item->caller) - 4);

		if ($item->type == 'originating')
		{
			echo html::anchor(
					'users/show/' . $item->subscriber,
					VoIP_calls_Controller::parse_number($n)
			);
		}
		else
		{
			echo VoIP_calls_Controller::parse_number($n);
		}
	}

	/**
	 * Callcon partner field
	 *
	 * @param object $item
	 * @param string $name 
	 */
	protected static function callcon_partner($item, $name)
	{
		$n = substr($item->callcon, 4, strlen($item->callcon) - 4);

		if (($item->area == 'ENUM'))
		{
			echo $n;
		}
		else
		{
			if ($item->type == 'terminating')
			{
				echo html::anchor(
						url_lang::base() . 'users/show/' . $item->subscriber,
						VoIP_calls_Controller::parse_number($n)
				);
			}
			else
			{
				echo VoIP_calls_Controller::parse_number($n);
			}
		}
	}

	/**
	 * Parses number, returns number with country code, eg. 420XXXXXXXXX
	 *
	 * @author Roman Sevcik, Michal Kliment
	 * @staticvar Contact_Model $contact_model
	 * @staticvar Country_Model $country_model
	 * @param string $number
	 * @param string $area
	 * @return string
	 */
	public static function parse_number($number, $area = '')
	{
		// uses static variables to save memory
		static $country_model;

		if (!$country_model)
			$country_model = new Country_Model();

		$sip_uri = explode('@', $number);

		// uses only first part (before character @)
		if (valid::digit($sip_uri[0]))
			$number = $sip_uri[0];

		// if there are leading double zeroes, it removes it
		$number = (substr($number, 0, 2) == '00') ? substr($number, 2, strlen($number) - 2) : $number;

		// tries to find country code by area
		$country = $country_model->find_country_by_area($area);

		// success
		if ($country && $country->id)
		{
			// number doesn't start with its country code - it adds it to at begin of string
			if (substr($number, 0, strlen($country->country_code)) != $country->country_code)
				$number = $country->country_code . $number;
		}
		else
		{
			// try to find country by number
			$country_id = $country_model->find_phone_country_id($number);

			// number is without country code, add default
			if ($country_id == '')
				$number = Settings::get('default_country') . $number;
		}

		return $number;
	}

	/**
	 * Tries to find number in IS and prints it
	 *
	 * @author Michal Kliment
	 * @staticvar User_Model $user_model
	 * @staticvar Contact_Model $contact_model
	 * @staticvar Voip_sip_Model $voip_sip_model
	 * @staticvar Private_users_contact_Model $private_users_contact_model
	 * @param string $number
	 * @param integer $user_id
	 * @return string
	 */
	public static function number($number, $user_id, $private_users = array())
	{
		// uses static variables to save memory
		static $user_model, $country_model, $voip_sip_model, $private_users_contact_model;

		if (!$user_model)
			$user_model = new User_Model();

		if (!$country_model)
			$country_model = new Country_Model();

		if (!$voip_sip_model)
			$voip_sip_model = new Voip_sip_Model();

		if (!$private_users_contact_model)
			$private_users_contact_model = new Private_users_contact_Model();

		// finds country code
		$country_code = $country_model->find_phone_country_code($number);

		// and removes it from number
		$number = substr(
				$number, strlen($country_code),
				strlen($number) - strlen($country_code)
		);

		// pretty format of number - for later use
		$pretty_number = text::phone_number($number, $country_code);

		// tries to find user to who belongs number
		$user = $user_model->get_user_by_phone_number_country_code(
				$number, $country_code
		);

		// success
		if ($user && $user->id)
		{
			if (Controller::instance()->acl_check_view(
					'Users_Controller', 'users', $user->member_id
				))
			{
				return html::anchor(
						'users/show/' . $user->id,
						$user->surname . ' ' . $user->name,
						array('title' => $pretty_number)
				);
			}
			else
			{
				return $user->surname . ' ' . $user->name;
			}
		}
		else
		{
			// tries to find number in VoIP's numbers
			$voip_sip = $voip_sip_model->where('name', $number)->find();

			// success
			if ($voip_sip && $voip_sip->id)
			{
				if (Controller::instance()->acl_check_view(
						'Users_Controller', 'users', $voip_sip->user->member_id
					))
				{
					return html::anchor(
							'users/show/' . $voip_sip->user->id,
							$voip_sip->user->surname . ' ' . $voip_sip->user->name,
							array('title' => $pretty_number)
					);
				}
				else
				{
					return $voip_sip->user->surname . ' ' . $voip_sip->user->name;
				}
			}
			else
			{
				$puc_id = NULL;

				foreach ($private_users as $private_user_id)
				{
					// tries to find number in user's private phone numbers
					$puc_id = $private_users_contact_model->get_contact_id(
							$private_user_id, $country_code . $number
					);

					// success
					if ($puc_id)
						break;
				}

				// success
				if ($puc_id)
				{
					$private_users_contact = $private_users_contact_model->where(
							'id', $puc_id
					)->find();

					$out = '<span style="color: green;" title="' . $pretty_number . '">' .
							$private_users_contact->description . '</span> ';
					
					$out .= html::anchor(
							'private_phone_contacts/edit/' . $puc_id,
							html::image('media/images/icons/gtk_edit.png'), array
							(
								'rel' => 'dialog',
								'class' => 'link_private_contact_edit',
								'title' => __('Edit')
							)
					) . ' ';
					
					$out .= html::anchor(
							'private_phone_contacts/delete/' .
							$puc_id, html::image('media/images/icons/delete.png'), array
							(
								'rel' => 'dialog',
								'class' => 'delete_link link_private_contact_delete',
								'title' => __('Delete')
							)
					);
					
					return $out;
				}
				else
				{ // it couldn't find number in anywhere, prints it in pretty format
					if ($user_id == Session::get('user_id'))
					{
						return $pretty_number . ' ' . html::anchor(
								'private_phone_contacts/add/' .
								$user_id . '/' . $country_code . $number,
								html::image('media/images/icons/ico_add.gif')
						);
					}
					
					return $pretty_number;
				}
			}
		}
	}
	
	/**
	 * Length field
	 *
	 * @param object $item
	 * @param string $name 
	 */
	protected static function call_length($item, $name)
	{
	    $sek = strtotime($item->end_date) - strtotime($item->start_date);
	    
	    if ($sek == 0)
		{
			echo '00:00:00';
		}
		else
		{
			$min = floor($sek / 60);
			$sek = $sek % 60;
			$hod = floor($min / 60);
			$min = $min % 60;

			if (strlen($sek) == 1)
			{
				$sek = '0'.$sek;
			}
			
			if (strlen($min) == 1)
			{
				$min = '0'.$min;
			}
			
			if (strlen($hod) == 1)
			{
				$hod = '0'.$hod;
			}

			echo $hod.':'.$min.':'.$sek;
	    }
	}

	/**
	 * Type field
	 *
	 * @param object $item
	 * @param string $name 
	 */
	protected static function type($item, $name)
	{
	    if ($item->type == 'originating')
		{
			echo html::image(array
			(
				'src'	=> resource::voip('originating'),
				'alt'	=> __('Originating call'),
				'title'	=> __('Originating call')
			));
		}
	    else if ($item->type == 'terminating')
		{
			echo html::image(array
			(
				'src'	=> resource::voip('terminating'),
				'alt'	=> __('Terminating call'),
				'title'	=> __('Terminating call')
			));
		}
		else
		{
			echo '?';
		}
	}

	/**
	 * Destination field
	 *
	 * @param object $item
	 * @param string $name 
	 */
	protected static function destination($item, $name)
	{
	    echo html::image(array
		(
			'src'	=> resource::flag('cs'),
			'alt'	=> __('CS'),
			'title'	=> $item->area
		));
	}

}
