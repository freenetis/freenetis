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
 * VoIP controller.
 * Accounts and calls are managed by lbilling from remote server.
 *
 * @see Billing
 * @author  Sevcik Roman
 * @package Controller
 */
class VoIP_Controller extends Controller
{
	/**
	 * Redirects to show all
	 */
	public function index()
	{
		url::redirect('voip/show_all');
	}

	/**
	 * Shows all VOIP
	 *
	 * @param type $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param type $page_word
	 * @param type $page 
	 */
	public function show_all(
			$limit_results = 100, $order_by = 'user_id', $order_by_direction = 'asc',
			$page_word = null, $page = 1)
	{
		if (!$this->acl_check_view('VoIP_Controller', 'voip'))
			Controller::error(ACCESS);
		
		// get new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		// parameters control
		$allowed_order_type = array('id', 'name', 'user_id');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'user_id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		$model_voip_sip = new Voip_sip_Model();
		$total_voip_sip = $model_voip_sip->count_all_records();
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_voip_sip)
			$sql_offset = 0;

		$all_voip_sip = $model_voip_sip->get_all_records(
				$sql_offset, (int) $limit_results, $order_by, $order_by_direction
		);

		$headline = __('VoIP');
		
		$grid = new Grid('voip', $headline, array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 100,
			'selector_min'				=> 100,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang') . '/voip/show_all/'
										. $limit_results . '/' . $order_by . '/'
										. $order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_voip_sip,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results
		));

		$grid->add_new_button('voip_calls/show_all', __('List of all calls'));

		$grid->callback_field('locked')
				->label('State')
				->order(false)
				->callback(array($this, 'locked'))
				->class('center');
		
		$grid->order_link_field('user_id')
				->link('voip/show', 'name')
				->label('Number');
		
		$grid->order_link_field('user_id')
				->link('users/show', 'ufname')
				->label('User');
		
		$grid->order_link_field('member_id')
				->link('users/show', 'mname')
				->label('User');
		
		$grid->callback_field('regseconds')
				->label('')
				->order(false)
				->callback(array($this, 'regseconds'))
				->class('center');
		
		$grid->datasource($all_voip_sip);
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $headline;
		$view->content = $grid;
		$view->render(TRUE);
	}

	/**
	 * Show VOIP
	 *
	 * @param integer $user_id 
	 */
	public function show($user_id = NULL)
	{
		if (!$user_id)
			Controller::warning(PARAMETER);
		
		$user = new User_Model($user_id);
		
		if (!$user->id)
			Controller::error(RECORD);
		
		if (!$this->acl_check_view('VoIP_Controller', 'voip', $user->member_id))
			Controller::error(ACCESS);

		$voip_sip = new Voip_sip_Model();

		//check existence of number
		$voip = $voip_sip->get_record_by_user_limited($user_id);

		if ($voip->count() == 0)
			Controller::error(RECORD);

		$voip = $voip_sip->get_record_by_user($user_id);
		
		if ($voip->count() == 0)
			Controller::error(RECORD);

		$sip_server = Settings::get('voip_sip_server', FALSE);

		$voip = $voip_sip->get_record_by_user($user_id);

		$voip = $voip->current();
		$voicemail_model = new Voip_voicemail_user_Model();
		$voicemail = $voicemail_model->where('customer_id', $voip->mailbox)->find();

		$link_status = (($voip->regseconds - time()) > 1);

		$billing = Billing::instance();
		
		$has_driver = $billing->has_driver();
		$b_account = $billing->get_account($user->member_id);
		
		$void_account_enabled = $has_driver && ($b_account != null);
		
		$links[] = html::anchor(
				'voip/edit_voicemail/' . $voip->user_id, __('Edit voicemail')
		);

		if ($void_account_enabled)
		{
			$links[] = html::anchor(
					'voip_calls/show_by_user/' . $user_id, __('List of calls')
			);
		}
		
		$regseconds = ($voip->regseconds - time() < 0 ) ? '0' : $voip->regseconds - time();

		$links = implode(' | ', $links);
		
		$breadcrumbs = breadcrumbs::add()
				->link('voip/show_all', 'VoIP')
				->disable_translation()
				->text($voip->name);
			
		// prices of calls
		$fixed_price = null;
		$cellphone_price = null;
		$voip_price = null;

		if ($void_account_enabled)
		{
			if (Settings::get('voip_tariff_fixed'))
			{
				$fixed_price = $billing->get_price_of_minute_call(
						$voip->name, Settings::get('voip_tariff_fixed')
				);
			}

			if (Settings::get('voip_tariff_cellphone'))
			{
				$cellphone_price = $billing->get_price_of_minute_call(
						$voip->name, Settings::get('voip_tariff_cellphone')
				);
			}

			if (Settings::get('voip_tariff_voip'))
			{
				$voip_price = $billing->get_price_of_minute_call(
						$voip->name, Settings::get('voip_tariff_voip')
				);
			}
		}

		$view = new View('main');
		$view->title = __('Show VoIP account');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('voip/show');
		$view->content->headline = __('Show VoIP');
		$view->content->links = $links;
		$view->content->voip = $voip;
		$view->content->sip_server = $sip_server;
		$view->content->link_status = $link_status;
		$view->content->ipaddr = $voip->ipaddr;
		$view->content->port = $voip->port;
		$view->content->regseconds = $regseconds;
		$view->content->voice_email = $voicemail->email;
		$view->content->voice_status = $voicemail->active;
		$view->content->fixed_price = $fixed_price;
		$view->content->cellphone_price = $cellphone_price;
		$view->content->voip_price = $voip_price;
		$view->content->void_account_enabled = $void_account_enabled;
		$view->render(true);
	}

	/**
	 * Changes password
	 *
	 * @param integer $user_id 
	 */
	public function change_password($user_id = NULL)
	{
		if (!$user_id)
			Controller::warning(PARAMETER);
		
		$user = new User_Model($user_id);
		
		if (!$user->id)
			Controller::error(RECORD);
		
		if (!$this->acl_check_edit('VoIP_Controller', 'voip_password', $user->member_id))
			Controller::error(ACCESS);

		$voip = ORM::factory('voip_sip')->where('user_id', $user_id)->find();

		if ($voip->id == null)
			Controller::error(RECORD);
		
		$form = new Forge('voip/change_password/' . $user_id);
		
		$form->password('password')
				->label(__('New password') . ':')
				->rules('required|length[6,20]');
		
		$form->password('confirm_password')
				->label(__('Confirm new password') . ':')
				->rules('required|length[6,20]')
				->matches($form->password);
		
		$form->submit('Change');

		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);

			$voip_sip = new Voip_sip_Model();
			$voip = $voip_sip->where('user_id', $user_id)->find();

			$user = ORM::factory('user')->where('id', $user_id)->find();

			$username = text::cs_utf2ascii($user->name . ' ' . $user->surname);

			$voip->callerid = $username . ' <' . $voip->name . '>';
			$voip->secret = $form_data['password'];

			if ($voip->save())
			{
				status::success('Password has been successfully changed.');
			}
			else
			{
				status::error('Error - cant change password.');
			}

			url::redirect('voip/change_password/' . $user_id);
		}
		else
		{
			$breadcrumbs = breadcrumbs::add()
					->link('voip/show_all', 'VoIP',
							$this->acl_check_view('VoIP_Controller', 'voip'))
					->disable_translation()
					->link('voip/show/' . $voip->user_id, $voip->name,
							$this->acl_check_view(
									'VoIP_Controller', 'voip', $user->member_id
							)
					)->enable_translation()
					->text('Change password');
			
			$view = new View('main');
			$view->title = __('Change password');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Password for VoIP account');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Chenges voice mail password
	 *
	 * @param integer $user_id 
	 */
	public function change_voicemail_password($user_id = NULL)
	{
		if (!isset($user_id))
			Controller::warning(PARAMETER);
		
		$user = new User_Model($user_id);
		
		if (!$user->id)
			Controller::error(RECORD);
		
		if (!$this->acl_check_edit('VoIP_Controller', 'voip_password', $user->member_id))
			Controller::error(ACCESS);

		$voip = ORM::factory('voip_sip')->where('user_id', $user_id)->find();

		if (!$voip->id)
			Controller::error(RECORD);

		$voicemail_model = new Voip_voicemail_user_Model();
		$voicemail = $voicemail_model->where('customer_id', $voip->mailbox)->find();

		$form = new Forge('voip/change_voicemail_password/' . $user_id);
		
		$form->password('password')
				->label(__('New password') . ':')
				->rules('required|length[4,4]|valid_digit');
		
		$form->password('confirm_password')
				->label(__('Confirm new password') . ':')
				->rules('required|length[4,4]|valid_digit')
				->matches($form->password);
		
		$form->submit('submit')->value(__('Change'));

		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);

			$voip_sip = new Voip_sip_Model();
			$voip = $voip_sip->where('user_id', $user_id)->find();

			$voicemail_model = new Voip_voicemail_user_Model();
			$voicemail = $voicemail_model->where('customer_id', $voip->mailbox)->find();

			$voicemail->password = $form_data['password'];

			if ($voicemail->save())
			{
				status::success('Password has been successfully changed.');
			}
			else
			{
				status::error('Error - cant change password.');
			}

			url::redirect('voip/change_voicemail_password/' . $user_id);
		}
		else
		{
			$breadcrumbs = breadcrumbs::add()
					->link('voip/show_all', 'VoIP',
							$this->acl_check_view('VoIP_Controller', 'voip'))
					->disable_translation()
					->link('voip/show/' . $voip->user_id, $voip->name,
							$this->acl_check_view(
									'VoIP_Controller', 'voip', $user->member_id
							)
					)->enable_translation()
					->text('Change voicemail password');
			
			$view = new View('main');
			$view->title = __('Change voicemail password');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Voicemail password for VoIP account');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Edits voice mail
	 *
	 * @param integer $user_id 
	 */
	public function edit_voicemail($user_id = NULL)
	{
		if (!isset($user_id))
			Controller::warning(PARAMETER);
		
		$user = new User_Model($user_id);
		
		if (!$user->id)
			Controller::error(RECORD);
		
		
		if (!$this->acl_check_edit('VoIP_Controller', 'voip_password', $user->member_id))
			Controller::error(ACCESS);

		$voip = ORM::factory('voip_sip')->where('user_id', $user_id)->find();

		if ($voip->id == null)
			Controller::error(RECORD);

		$voicemail_model = new Voip_voicemail_user_Model();
		$voicemail = $voicemail_model->where('customer_id', $voip->mailbox)->find();

		$form = new Forge('voip/edit_voicemail/' . $user_id);
		
		$form->dropdown('status')
				->label(__('State'))
				->options(array
				(
					'0' => __('Inactive'),
					'1' => __('Active')
				))->selected($voicemail->active);
		
		$form->input('email')
				->label(__('email') . ':')
				->rules('length[6,50]|valid_email')
				->value($voicemail->email);
		
		$form->submit('Edit');

		if ($form->validate())
		{
			$form_data = $form->as_array();

			$voip_sip = new Voip_sip_Model();
			$voip = $voip_sip->where('user_id', $user_id)->find();

			$voicemail_model = new Voip_voicemail_user_Model();
			$voicemail = $voicemail_model->where('customer_id', $voip->mailbox)->find();

			$voicemail->email = $form_data['email'];
			$voicemail->active = $form_data['status'];

			if ($voicemail->save())
			{
				status::success('Voicemail has been successfully changed.');
			}
			else
			{
				status::error('Error - cant change voicemail.');
			}

			url::redirect('voip/edit_voicemail/' . $user_id);
		}
		else
		{
			$breadcrumbs = breadcrumbs::add()
					->link('voip/show_all', 'VoIP',
							$this->acl_check_view('VoIP_Controller', 'voip'))
					->disable_translation()
					->link('voip/show/' . $voip->user_id, $voip->name,
							$this->acl_check_view(
									'VoIP_Controller', 'voip', $user->member_id
							)
					)->enable_translation()
					->text('Edit voicemail');
			
			$view = new View('main');
			$view->title = __('Edit voicemail');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Voicemail for VoIP account');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Changes member limit
	 *
	 * @param integer $member_id 
	 */
	public function change_member_limit($member_id = NULL)
	{
		if (!isset($member_id))
			Controller::warning(PARAMETER);

		if (!is_numeric($member_id))
			Controller::error(RECORD);

		if (!$this->acl_check_edit('VoIP_Controller', 'voip_password', $member_id))
			Controller::error(ACCESS);

		$member = new Member_Model($member_id);

		if (!$member->id)
			Controller::error(RECORD);

		$billing = Billing::instance();
		
		$has_driver = $billing->has_driver();
		$b_account = $billing->get_account($member_id);

		if (!$has_driver || ($b_account == null))
			Controller::error(RECORD);

		$form = new Forge('voip/change_member_limit/' . $member_id);
		
		$form->input('limit')
				->rules('length[1,6]|valid_digit')
				->value($member->voip_billing_limit)
				->callback(array($this, 'valid_limit'));

		$form->submit('Change');

		if ($form->validate())
		{
			$form_data = $form->as_array();

			$member->voip_billing_limit = $form_data['limit'];

			if ($member->save())
			{
				status::success('Limit has been successfully changed.');
			}
			else
			{
				status::error('Error - cant change limit.');
			}

			url::redirect('voip/change_member_limit/' . $member_id);
		}
		else
		{
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
					->text('Change member limit');

			$view = new View('main');
			$view->title = __('Change member limit');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = 'VoIP' . __('Limit of member');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Adds VoIP for user
	 *
	 * @param integer $user_id 
	 */
	public function add($user_id = NULL)
	{
		if (!isset($user_id))
			Controller::warning(PARAMETER);

		$user = new User_Model($user_id);
		
		if (!$user->id)
			Controller::error(RECORD);
		
		if (!$this->acl_check_new('VoIP_Controller', 'voip', $user->member_id))
			Controller::error(ACCESS);

		if ($this->input->post('ranges') != NULL)
		{
			$number = $this->input->post('ranges');

			$user = ORM::factory('user')->where('id', $user_id)->find();

			$username = text::cs_utf2ascii($user->name . ' ' . $user->surname);

			$voip_new = new Voip_sip_Model();
			$voip_new->name = $number;
			$voip_new->user_id = $user_id;
			$voip_new->callerid = $username . ' <' . $number . '>';
			$voip_new->mailbox = $number;
			$voip_new->secret = security::generate_password();
			$voip_new->username = $number;
			$voip_new->save();

			// get email
			$emails = $user->get_user_emails($user->id);
			$email = '';

			if ($emails != false && $emails->current())
			{
				$email = $emails->current()->email;
			}

			$voicemail = new Voip_voicemail_user_Model();
			$voicemail->customer_id = $number;
			$voicemail->mailbox = $number;
			$voicemail->password = security::generate_numeric_password(4);
			$voicemail->fullname = $username;
			$voicemail->email = $email;
			$voicemail->save();
		}

		$voip_sip = new Voip_sip_Model();

		//check existence of number
		$voip = $voip_sip->get_record_by_user_limited($user_id);

		if ($voip->count() != 0)
			url::redirect('voip/show/' . $user_id);

		$config_model = new Config_Model();
		
		if ($config_model->check_exist_variable('voip_number_interval'))
		{
			$ranges = array();
			$used_numbers = array();
			$ranges = explode('-', $config_model->get_value_from_name('voip_number_interval'));

			$exclude_numbers = explode(';', $config_model->get_value_from_name('voip_number_exclude'));

			$exist_numbers = $voip_sip->select('name')->find_all();

			$i = 0;
			foreach ($exist_numbers as $exist_number)
			{
				$used_numbers[$i] = $exist_number->name;
				$i++;
			}

			for ($i = $ranges[0]; $i <= $ranges[1]; $i++)
			{
				if (!in_array($i, $used_numbers) && !in_array($i, $exclude_numbers))
					$selection[$i] = $i;
			}
		}
		else
		{
			$selection[0] = '';
		}

		$ranges = form::dropdown('ranges', $selection, 'standard');
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/'.$user->member->id,
						'ID ' . $user->member->id . ' - ' . $user->member->name,
						$this->acl_check_view(
								'Members_Controller', 'members', $user->member->id
						)
				)
				->enable_translation()
				->text('Add VoIP account');

		$view = new View('main');
		$view->title = __('Add VoIP account');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('voip/add');
		$view->content->user_id = $user_id;
		$view->content->ranges = $ranges;
		$view->render(TRUE);
	}
	
	///////////////////////////////////////////////////////////////////////////
	/// Callbacks /////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Function validates amount of limit.
	 * 
	 * @param object $input
	 */
	public function valid_limit($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if ($input->value < 0)
		{
			$input->add_error('required', __('Error - amount has to be positive.'));
		}
	}

	/**
	 * Locked state
	 *
	 * @param object $item
	 * @param string $name 
	 */
	protected static function locked($item, $name)
	{
		if ($item->locked == 1)
		{
			echo html::image(array
			(
				'src'	=> resource::state('locked'),
				'alt'	=> __('Account locked'),
				'title'	=> __('Account locked')
			));
		}
	}

	/**
	 * Phone registration status
	 *
	 * @param object $item
	 * @param string $name 
	 */
	protected static function regseconds($item, $name)
	{
		if (( $item->regseconds - time()) > 1)
		{
			echo html::image(array
			(
				'src'	=> resource::state('active'),
				'alt'	=> __('Phone registration status') . ' - ' . __('Registered'),
				'title'	=> __('Phone registration status') . ' - ' . __('Registered')
			));
		}
		else
		{
			echo html::image(array
			(
				'src'	=> resource::state('inactive'),
				'alt'	=> __('Phone registration status') . ' - ' . __('Not registered'),
				'title'	=> __('Phone registration status') . ' - ' . __('Not registered')
			));
		}
	}

}

