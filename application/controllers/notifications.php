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
 * Controller performs notifications actions.
 * Informs user of system using redirection, SMS or email about some action or change.
 * 
 * @package Controller
 * @author Michal Kliment
 */
class Notifications_Controller extends Controller
{
	/** activate notification */
	const ACTIVATE = 1;
	
	/** keep current settings */
	const KEEP = 2;
	
	/** deactivate notification */
	const DEACTIVATE = 3;
	
	/**
	 * Notification setting for member
	 * 
	 * @author Michal Kliment
	 * @param integer $member_id 
	 */
	public function member($member_id = NULL)
	{
		// bad parameter
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);
		
		$member = new Member_Model($member_id);
		
		// record doesn't exist
		if (!$member->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_new('Messages_Controller', 'member', $member->id))
			Controller::error(ACCESS);
		
		$headline = __('Notification setting of member').' '.$member->name;
		
		// gets all user messages
		$messages = ORM::factory('message')->find_all();
		
		$arr_messages = array();
		
		foreach ($messages as $message)
		{
			if ($message->type == Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE ||
				$message->type == Message_Model::DEBTOR_MESSAGE ||
				$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE || 
				$message->type == Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE)
			{
				$arr_messages[$message->id] = __($message->name);
			}
			else if ($message->type == Message_Model::USER_MESSAGE)
			{
				$arr_messages[$message->id] = $message->name;
			}
		}
		
		$arr_messages = array
		(
			NULL => '----- '.__('select message').' -----'
		) + $arr_messages;
		
		$form = new Forge('notifications/member/'.$member->id);
		
		$form->dropdown('message_id')
				->label(__('Message').':')
				->options($arr_messages)
				->rules('required');
		
		$form->textarea('comment');
		
		$form->dropdown('redirection')
				->options(array
				(
					self::ACTIVATE => __('Activate'),
					self::KEEP => __('Without change'),
					self::DEACTIVATE => __('Deactivate')
				));
		
		$form->dropdown('email')
				->label(__('E-mail').':')
				->options(array
				(
					self::ACTIVATE => __('Activate'),
					self::KEEP => __('Without change')
				));
		
		$form->dropdown('sms')
				->label(__('SMS message').':')
				->options(array
				(
					self::ACTIVATE => __('Activate'),
					self::KEEP => __('Without change')
				));
		
		$form->submit('Send');
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			// needed class
			$message = new Message_Model($form_data['message_id']);
			$ip_model = new Ip_address_Model();
			$mia_model = new Messages_ip_addresses_Model();
			$uc_model = new Users_contacts_Model();
			
			// needed data
			$user_id = $this->session->get('user_id');
			$comment = $form_data['comment'];
			
			$info_messages = array();
			
			/* Redirection */
			
			if ($form_data['redirection'] == self::ACTIVATE ||
				$form_data['redirection'] == self::DEACTIVATE)
			{
				// stats vars
				$deleted_redr = 0;
				$added_redr = 0;

				// get all redirection
				$ips = $ip_model->get_ip_addresses_of_member($member->id);

				// delete redirection of these IP address
				foreach ($ips as $ip)
				{
					$mia_model->delete_redirection_of_ip_address(
							$message->id, $ip->id
					);
					$deleted_redr++;
				}

				// set new redirection?
				if ($form_data['redirection'] == self::ACTIVATE)
				{
					$added_redr = Message_Model::activate_redirection(
							$message, $ips,
							$user_id, $comment
					);
				}

				// info messages
				if ($added_redr)
				{
					$m = 'Redirection has been activated for %s IP addresses';
					$info_messages[] = __($m, $added_redr).'.';
				}
				else
				{
					$m = 'Redirection has been deactivated for %s IP addresses';
					$info_messages[] = __($m, $deleted_redr).'.';
				}
			}
			
			/* Email */
			
			if ($form_data['email'] == self::ACTIVATE)
			{
				// gets all contacts of member
				$contacts = $uc_model->get_contacts_by_member_and_type(
						$member->id, Contact_Model::TYPE_EMAIL,
						$message->ignore_whitelist
				);
			
				// send email
				$sent_emails = Message_Model::send_emails(
						$message, $contacts, $comment
				);

				// info message
				$m = 'E-mail has been sent for %s e-mail addresses';
				$info_messages[] = __($m, $sent_emails).'.';
			}
			
			/* SMS messages */
			
			if ($form_data['sms'] == self::ACTIVATE)
			{
				// gets all contacts of member
				$contacts = $uc_model->get_contacts_by_member_and_type(
						$member->id, Contact_Model::TYPE_PHONE,
						$message->ignore_whitelist
				);
			
				// send email
				$sent_sms = Message_Model::send_sms_messages(
						$message, $contacts,
						$user_id, $comment
				);

				// info message
				$m = 'SMS message has been sent for %d phone numbers.';
				$info_messages[] = __($m, $sent_sms);
			}
			
			// user notification
			if (count($info_messages))
			{
				status::success(implode('<br />', $info_messages), FALSE);
			}
			
			// redirect
			$this->redirect('members/show/'.$member->id);
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
						->link('members/show_all', 'Members',
								$this->acl_check_view('Members_Controller','members'))
						->disable_translation()
						->link('members/show/'.$member->id,
								"ID $member->id - $member->name",
								$this->acl_check_view(
									'Members_Controller', 'members', $member->id
								)
						)
						->enable_translation()
						->text('Notification setting');

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs;
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form;
			$view->render(TRUE);
		}
	}
	
	/**
	 * Activates notifications for members
	 * 
	 * @author Michal Kliment
	 * @param integer $message_id
	 */
	public function members($message_id = NULL)
	{	
		$headline = __('Notification setting of members');
		
		if (!$message_id)
		{			
			$input = $_REQUEST;
			
			// gets all user messages
			$messages = ORM::factory('message')
					->find_all();

			$arr_messages = array();

			foreach ($messages as $message)
			{
				// handled by cron
				if ($message->type == Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE)
				{
					continue;
				}
				
				if ($message->type == Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE ||
					$message->type == Message_Model::DEBTOR_MESSAGE ||
					$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE)
				{
					$arr_messages[$message->id] = __($message->name);
				}
				else if ($message->type == Message_Model::USER_MESSAGE)
				{
					$arr_messages[$message->id] = $message->name;
				}
			}

			$arr_messages = array
			(
				NULL => '----- '.__('Select message').' -----'
			) + $arr_messages;

			$form = new Forge(url::base().url::current(TRUE));

			$form->dropdown('message_id')
					->label('Message')
					->options($arr_messages)
					->rules('required');

			$form->submit('Next step');

			if ($form->validate())
			{
				$form_data = $form->as_array();	
				
				$message_id = arr::remove('message_id', $form_data);
				url::redirect(url_lang::base().'notifications/members/'.$message_id.'/'.server::query_string());
			}

			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all/'.server::query_string(), 'Members', 
						$this->acl_check_view('Members_Controller', 'members'))
					->text('Notification setting')
					->html();

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs;
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form;
			$view->render(TRUE);
		}
		else
		{
			// bad message parameter
			if (!is_numeric($message_id))
				Controller::warning(PARAMETER);
			
			$message = new Message_Model($message_id);
			
			// message doesn't exist
			if (!$message->id)
				Controller::error(RECORD);
			
			if (!isset($_POST) || !isset($_POST["ids"]))
			{
				switch ($message->type)
				{
					case Message_Model::DEBTOR_MESSAGE:
					case Message_Model::PAYMENT_NOTICE_MESSAGE:
						$order_by = 'balance';
						$order_by_direction = 'ASC';
						break;

					case Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE:
						$order_by = 'interrupt';
						$order_by_direction = 'DESC';
						break;

					default:
						$order_by = 'id';
						$order_by_direction = 'ASC';
						break;
				}
							
				$member_model = new Member_Model();
				
				$filter_form = Members_Controller::create_filter_form();
				
				$total_members = $member_model
					->count_all_members(
						$filter_form->as_sql()
					);
				
				$members  = $member_model
					->get_all_members(
						0, $total_members,
						$order_by, $order_by_direction,
						$filter_form->as_sql()
					);

				$grid = new Grid(url_lang::base().'notifications/subnet', '', array
				(
					'use_paginator' => false,
					'use_selector' => false,
					'total_items' =>  count ($members)
				));

				//$grid->field('id')
				//		->label(__('ID'));

				$grid->callback_field('member_id')
						->label(__('Name'))
						->callback('callback::member_field');

				$grid->callback_field('type')
						->callback('callback::member_type_field');

				$grid->callback_field('balance')
						->callback('callback::balance_field');

				if ($message->type == Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE ||
					$message->type == Message_Model::DEBTOR_MESSAGE ||
					$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE ||
					$message->type == Message_Model::USER_MESSAGE)
				{
					$grid->callback_field('interrupt')
						->label(__('Membership interrupt'))
						->callback('callback::active_field')
						->class('center');
				}
				
				$grid->callback_field('whitelisted')
						->label(__('Whitelist'))
						->callback('callback::whitelisted_field')
						->class('center');

				$grid->form_field('redirection')
						->label(__('Redirection'))
						->type('dropdown')
						->options(array
						(
							self::ACTIVATE => __('Activate'),
							self::KEEP => __('Without change'),
							self::DEACTIVATE => __('Deactivate')
						))
						->callback(
							'callback::notification_form_field',
							$message->type,
							$message->ignore_whitelist
						)->class('center');

				if ($message->type == Message_Model::DEBTOR_MESSAGE ||
					$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE ||
					$message->type == Message_Model::USER_MESSAGE)
				{
					$grid->form_field('email')
						->label(__('E-Mail'))
						->type('dropdown')
						->options(array
						(
							self::ACTIVATE => __('Activate'),
							self::KEEP => __('Without change')
						))
						->callback(
							'callback::notification_form_field',
							$message->type,
							$message->ignore_whitelist
						)->class('center');

					$grid->form_field('sms')
						->label(__('SMS'))
						->type('dropdown')
						->options(array
						(
							self::ACTIVATE => __('Activate'),
							self::KEEP => __('Without change')
						))
						->callback(
							'callback::notification_form_field',
							$message->type,
							$message->ignore_whitelist
						)->class('center');
				}
				
				$grid->form_extra_buttons = array
				(
					"position" => 'top',
					form::label(
						'comment',
						"<b>".__('Comment').":</b>"
					) . form::textarea('comment', '', 'style="margin-left: 30px"')."<br /><br />"
				);

				$breadcrumbs = breadcrumbs::add()
					->link('members/show_all/'.server::query_string(), 'Members', 
						$this->acl_check_view('Members_Controller', 'members'))
					->link('notifications/members/'.server::query_string(), 'Notification setting')
					->text($message->name)
					->html();
				

				$grid->datasource($members);

				$view = new View('main');
				$view->breadcrumbs = $breadcrumbs;
				$view->title = $headline;
				$view->content = new View('show_all');
				$view->content->headline = $headline;
				$view->content->table = $grid;
				$view->content->status_message_info = url_lang::lang('help.notification_settings');
				$view->render(TRUE);
			}
			else
			{
				$ip_address_model = new Ip_address_Model();
				$mia_model = new Messages_ip_addresses_Model();
				$uc_model = new Users_contacts_Model();
				
				$comment		= $_POST["comment"];
				$redirections	= $_POST["redirection"];
				
				$emails	= (isset($_POST["email"]))	? $_POST["email"]	: array();
				$smss	= (isset($_POST["sms"]))	? $_POST["sms"]		: array();
				
				$user_id = $this->session->get('user_id');
				
				$added_redr = 0;
				$deleted_redr = 0;
				$sent_emails = 0;
				$sent_sms = 0;
				
				$info_messages = array();
				
				foreach ($redirections as $member_id => $redirection)
				{
					if ($redirection == self::KEEP)
						continue;
					
					// get all redirection
					$ips = $ip_address_model->get_ip_addresses_of_member($member_id);
					
					// delete redirection of these IP address
					foreach ($ips as $ip)
					{
						$mia_model->delete_redirection_of_ip_address(
								$message->id, $ip->id
						);
						$deleted_redr++;
					}
					
					// set new redirection?
					if ($redirection == self::ACTIVATE)
					{
						$added_redr += Message_Model::activate_redirection(
							$message, $ips,
							$user_id, $comment
						);
					}
				}
				
				// info messages
				if ($added_redr)
				{
					$m = 'Redirection has been activated for %s IP addresses';
					$info_messages[] = __($m, $added_redr).'.';
				}
				else
				{
					$m = 'Redirection has been deactivated for %s IP addresses';
					$info_messages[] = __($m, $deleted_redr).'.';
				}
				
				foreach ($emails as $member_id => $email)
				{
					if ($email == self::KEEP)
						continue;
					
					// gets all contacts of member
					$contacts = $uc_model->get_contacts_by_member_and_type(
						$member_id, Contact_Model::TYPE_EMAIL, TRUE
					);
			
					// send email
					$sent_emails += Message_Model::send_emails(
						$message, $contacts, $comment
					);
				}
				
				// info message
				$m = 'E-mail has been sent for %s e-mail addresses';
				$info_messages[] = __($m, $sent_emails).'.';
				
				foreach ($smss as $member_id => $sms)
				{
					if ($sms == self::KEEP)
						continue;
					
					// gets all contacts of member
					$contacts = $uc_model->get_contacts_by_member_and_type(
						$member_id, Contact_Model::TYPE_PHONE, TRUE
					);
			
					// send email
					$sent_sms += Message_Model::send_sms_messages(
						$message, $contacts,
						$user_id, $comment
					);
				}
				
				// info message
				$m = 'SMS message has been sent for %d phone numbers.';
				$info_messages[] = __($m, $sent_sms);
				
				// user notification
				if (count($info_messages))
				{
					status::success(implode('<br />', $info_messages), FALSE);
				}
			
				// redirect
				url::redirect('members/show_all/');
			}
		}
	}
	
	/**
	 * Notification setting to subnet
	 * 
	 * @author Michal Kliment
	 * @param integer $subnet_id 
	 */
	public function subnet($subnet_id = NULL, $message_id = NULL)
	{
		// bad parameter
		if (!$subnet_id || !is_numeric($subnet_id))
			Controller::warning(PARAMETER);
		
		$subnet = new Subnet_Model($subnet_id);
		
		// record doesn't exist
		if (!$subnet->id)
			Controller::error(RECORD);
		
		$headline = __('Notification setting of subnet').' '.$subnet->name;
		
		if (!$message_id)
		{
			// gets all user messages
			$messages = ORM::factory('message')
					->find_all();

			$arr_messages = array();

			foreach ($messages as $message)
			{
				if ($message->type == Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE ||
					$message->type == Message_Model::DEBTOR_MESSAGE ||
					$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE || 
					$message->type == Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE)
				{
					$arr_messages[$message->id] = __($message->name);
				}
				else if ($message->type == Message_Model::USER_MESSAGE)
				{
					$arr_messages[$message->id] = $message->name;
				}
			}

			$arr_messages = array
			(
				NULL => '----- '.__('select message').' -----'
			) + $arr_messages;

			$form = new Forge('notifications/subnet/'.$subnet->id);

			$form->dropdown('message_id')
					->label(__('Message').':')
					->options($arr_messages)
					->rules('required');

			$form->submit('Next step');

			if ($form->validate())
			{
				$form_data = $form->as_array();	
				url::redirect(url_lang::base().'notifications/subnet/'.$subnet_id.'/'.$form_data["message_id"]);
			}

			$subnet_text = $subnet->name." ($subnet->network_address/"
					.network::netmask2cidr($subnet->netmask) .")";

			$breadcrumbs = breadcrumbs::add()
					->link('subnets/show_all', __('Subnets'),
							$this->acl_check_view('Devices_Controller','subnet'))
					->disable_translation()
					->link('subnets/show/'.$subnet->id, $subnet_text,
							$this->acl_check_view('Devices_Controller','subnet'))
					->enable_translation()
					->text('Notification setting')
					->html();

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs;
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form;
			$view->render(TRUE);
		}
		else
		{
			// bad message parameter
			if (!is_numeric($message_id))
				Controller::warning(PARAMETER);
			
			$message = new Message_Model($message_id);
			
			// message doesn't exist
			if (!$message->id)
				Controller::error(RECORD);
			
			if (!isset($_POST) || !isset($_POST["ids"]))
			{
				switch ($message->type)
				{
					case Message_Model::DEBTOR_MESSAGE:
					case Message_Model::PAYMENT_NOTICE_MESSAGE:
						$order_by = 'whitelisted DESC, balance ASC';
						break;

					case Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE:
						$order_by = 'whitelisted DESC, interrupt DESC';
						break;

					case Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE:
						$order_by = 'whitelisted DESC, allowed ASC';
						break;

					default:
						$order_by = 'whitelisted DESC, id ASC';
						break;
				}

				$member_model = new Member_Model();

				$members = $member_model->get_members_of_subnet(
						$subnet->id,
						$order_by
				);

				$grid = new Grid(url_lang::base().'notifications/subnet', '', array
				(
					'use_paginator' => false,
					'use_selector' => false,
					'total_items' =>  count ($members)
				));

				//$grid->field('id')
				//		->label(__('ID'));

				$grid->callback_field('member_id')
						->label(__('Name'))
						->callback('callback::member_field');

				$grid->callback_field('type')
						->callback('callback::member_type_field');

				$grid->callback_field('balance')
						->callback('callback::balance_field');

				if ($message->type == Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE ||
					$message->type == Message_Model::DEBTOR_MESSAGE ||
					$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE ||
					$message->type == Message_Model::USER_MESSAGE)
				{
					$grid->callback_field('interrupt')
						->label(__('Membership interrupt'))
						->callback('callback::active_field')
						->class('center');
				}

				if ($message->type == Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE ||
					$message->type == Message_Model::USER_MESSAGE)
				{
					$grid->callback_field('allowed')
						->label(__('Allowed subnet'))
						->callback('callback::active_field')
						->class('center');
				}
				
				$grid->callback_field('whitelisted')
						->label(__('Whitelist'))
						->callback('callback::whitelisted_field')
						->class('center');

				$grid->form_field('redirection')
						->label(__('Redirection'))
						->type('dropdown')
						->options(array
						(
							self::ACTIVATE => __('Activate'),
							self::KEEP => __('Without change'),
							self::DEACTIVATE => __('Deactivate')
						))
						->callback(
							'callback::notification_form_field',
							$message->type,
							$message->ignore_whitelist
						)->class('center');

				if ($message->type == Message_Model::DEBTOR_MESSAGE ||
					$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE ||
					$message->type == Message_Model::USER_MESSAGE)
				{
					$grid->form_field('email')
						->label(__('E-Mail'))
						->type('dropdown')
						->options(array
						(
							self::ACTIVATE => __('Activate'),
							self::KEEP => __('Without change')
						))
						->callback(
							'callback::notification_form_field',
							$message->type,
							$message->ignore_whitelist
						)->class('center');

					$grid->form_field('sms')
						->label(__('SMS'))
						->type('dropdown')
						->options(array
						(
							self::ACTIVATE => __('Activate'),
							self::KEEP => __('Without change')
						))
						->callback(
							'callback::notification_form_field',
							$message->type,
							$message->ignore_whitelist
						)->class('center');
				}
				
				$grid->form_extra_buttons = array
				(
					"position" => 'top',
					form::label(
						'comment',
						"<b>".__('Comment').":</b>"
					) . form::textarea('comment', '', 'style="margin-left: 30px"')."<br /><br />"
				);

				$subnet_text = $subnet->name." ($subnet->network_address/"
						.network::netmask2cidr($subnet->netmask) .")";

				$breadcrumbs = breadcrumbs::add()
						->link('subnets/show_all', __('Subnets'),
								$this->acl_check_view('Devices_Controller','subnet'))
						->disable_translation()
						->link('subnets/show/'.$subnet->id, $subnet_text,
								$this->acl_check_view('Devices_Controller','subnet'))
						->enable_translation()
						->link('notifications/subnet/'.$subnet->id, 'Notification setting')
						->text($message->name)
						->html();
				

				$grid->datasource($members);

				$view = new View('main');
				$view->breadcrumbs = $breadcrumbs;
				$view->title = $headline;
				$view->content = new View('show_all');
				$view->content->headline = $headline;
				$view->content->table = $grid;
				$view->content->status_message_info = url_lang::lang('help.notification_settings');
				$view->render(TRUE);
			}
			else
			{
				$ip_address_model = new Ip_address_Model();
				$mia_model = new Messages_ip_addresses_Model();
				$uc_model = new Users_contacts_Model();
				
				$comment		= $_POST["comment"];
				$redirections	= $_POST["redirection"];
				
				$emails	= (isset($_POST["email"]))	? $_POST["email"]	: array();
				$smss	= (isset($_POST["sms"]))	? $_POST["sms"]		: array();
				
				$user_id = $this->session->get('user_id');
				
				$added_redr = 0;
				$deleted_redr = 0;
				$sent_emails = 0;
				$sent_sms = 0;
				
				$info_messages = array();
				
				foreach ($redirections as $member_id => $redirection)
				{
					if ($redirection == self::KEEP)
						continue;
					
					// get all redirection
					$ips = $ip_address_model->get_ip_addresses_of_member($member_id, $subnet->id);
					
					// delete redirection of these IP address
					foreach ($ips as $ip)
					{
						$mia_model->delete_redirection_of_ip_address(
								$message->id, $ip->id
						);
						$deleted_redr++;
					}
					
					// set new redirection?
					if ($redirection == self::ACTIVATE)
					{
						$added_redr += Message_Model::activate_redirection(
							$message, $ips, TRUE,
							$user_id, $comment
						);
					}
				}
				
				// info messages
				if ($added_redr)
				{
					$m = 'Redirection has been activated for %s IP addresses';
					$info_messages[] = __($m, $added_redr).'.';
				}
				else
				{
					$m = 'Redirection has been deactivated for %s IP addresses';
					$info_messages[] = __($m, $deleted_redr).'.';
				}
				
				foreach ($emails as $member_id => $email)
				{
					if ($email == self::KEEP)
						continue;
					
					// gets all contacts of member
					$contacts = $uc_model->get_contacts_by_member_and_type(
						$member_id, Contact_Model::TYPE_EMAIL, TRUE
					);
			
					// send email
					$sent_emails += Message_Model::send_emails(
						$message, $contacts, $comment
					);
				}
				
				// info message
				$m = 'E-mail has been sent for %s e-mail addresses';
				$info_messages[] = __($m, $sent_emails).'.';
				
				foreach ($smss as $member_id => $sms)
				{
					if ($sms == self::KEEP)
						continue;
					
					// gets all contacts of member
					$contacts = $uc_model->get_contacts_by_member_and_type(
						$member_id, Contact_Model::TYPE_PHONE, TRUE
					);
			
					// send email
					$sent_sms += Message_Model::send_sms_messages(
						$message, $contacts,
						$user_id, $comment
					);
				}
				
				// info message
				$m = 'SMS message has been sent for %d phone numbers.';
				$info_messages[] = __($m, $sent_sms);
				
				// user notification
				if (count($info_messages))
				{
					status::success(implode('<br />', $info_messages), FALSE);
				}
			
				// redirect
				url::redirect('subnets/show/'.$subnet->id);
			}
		}
	}
	
	/**
	 * Notification setting to cloud
	 * 
	 * @author Michal Kliment
	 * @param integer $cloud_id 
	 */
	public function cloud($cloud_id = NULL, $message_id = NULL)
	{
		// bad parameter
		if (!$cloud_id || !is_numeric($cloud_id))
			Controller::warning(PARAMETER);
		
		$cloud = new Cloud_Model($cloud_id);
		
		// record doesn't exist
		if (!$cloud->id)
			Controller::error(RECORD);
		
		$headline = __('Notification setting of cloud').' '.$cloud->name;
		
		if (!$message_id)
		{
			// gets all user messages
			$messages = ORM::factory('message')->find_all();

			$arr_messages = array();

			foreach ($messages as $message)
			{
				if ($message->type == Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE ||
					$message->type == Message_Model::DEBTOR_MESSAGE ||
					$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE || 
					$message->type == Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE)
				{
					$arr_messages[$message->id] = __($message->name);
				}
				else if ($message->type == Message_Model::USER_MESSAGE)
				{
					$arr_messages[$message->id] = $message->name;
				}
			}

			$arr_messages = array
			(
				NULL => '----- '.__('select message').' -----'
			) + $arr_messages;

			$form = new Forge('notifications/cloud/'.$cloud->id);

			$form->dropdown('message_id')
					->label(__('Message').':')
					->options($arr_messages)
					->rules('required');

			$form->submit('Next step');

			if ($form->validate())
			{
				$form_data = $form->as_array();	
				url::redirect(url_lang::base().'notifications/cloud/'.$cloud_id.'/'.$form_data["message_id"]);
			}

			$name = $cloud->name . ' (' . $cloud_id . ')';

			// breadcrumbs		
			$breadcrumbs = breadcrumbs::add()
					->link('clouds/show_all', __('Clouds'),
							$this->acl_check_view('Clouds_Controller','clouds'))
					->disable_translation()
					->link('clouds/show/'.$cloud->id, $name,
							$this->acl_check_view('Clouds_Controller','clouds'))
					->enable_translation()
					->text(__('Notification setting'))
					->html();

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs;
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form;
			$view->render(TRUE);
		}
		else
		{
			// bad message parameter
			if (!is_numeric($message_id))
				Controller::warning(PARAMETER);
			
			$message = new Message_Model($message_id);
			
			// message doesn't exist
			if (!$message->id)
				Controller::error(RECORD);
			
			if (!isset($_POST) || !isset($_POST["ids"]))
			{
				switch ($message->type)
				{
					case Message_Model::DEBTOR_MESSAGE:
					case Message_Model::PAYMENT_NOTICE_MESSAGE:
						$order_by = 'whitelisted DESC, balance ASC';
						break;

					case Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE:
						$order_by = 'whitelisted DESC, interrupt DESC';
						break;

					case Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE:
						$order_by = 'whitelisted DESC, allowed ASC';
						break;

					default:
						$order_by = 'whitelisted DESC, id ASC';
						break;
				}

				$member_model = new Member_Model();

				$members = $member_model->get_members_of_cloud($cloud->id, $order_by);

				$grid = new Grid('notifications/cloud', '', array
				(
					'use_paginator' => false,
					'use_selector' => false,
					'total_items' =>  count($members)
				));

				//$grid->field('id')
				//		->label(__('ID'));

				$grid->callback_field('member_id')
						->label('Name')
						->callback('callback::member_field');

				$grid->callback_field('type')
						->callback('callback::member_type_field');

				$grid->callback_field('balance')
						->callback('callback::balance_field');

				if ($message->type == Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE ||
					$message->type == Message_Model::DEBTOR_MESSAGE ||
					$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE ||
					$message->type == Message_Model::USER_MESSAGE)
				{
					$grid->callback_field('interrupt')
						->label(__('Membership interrupt'))
						->callback('callback::active_field')
						->class('center');
				}

				if ($message->type == Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE ||
					$message->type == Message_Model::USER_MESSAGE)
				{
					$grid->callback_field('allowed')
						->label('Allowed subnet')
						->callback('callback::active_field')
						->class('center');
				}
				
				$grid->callback_field('whitelisted')
						->label('Whitelist')
						->callback('callback::whitelisted_field')
						->class('center');

				$grid->form_field('redirection')
						->label('Redirection')
						->type('dropdown')
						->options(array
						(
							self::ACTIVATE => __('Activate'),
							self::KEEP => __('Without change'),
							self::DEACTIVATE => __('Deactivate')
						))
						->callback('callback::notification_form_field', $message->type, $message->ignore_whitelist);

				if ($message->type == Message_Model::DEBTOR_MESSAGE ||
					$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE ||
					$message->type == Message_Model::USER_MESSAGE)
				{
					$grid->form_field('email')
						->label('E-Mail')
						->type('dropdown')
						->options(array
						(
							self::ACTIVATE => __('Activate'),
							self::KEEP => __('Without change')
						))
						->callback('callback::notification_form_field', $message->type, $message->ignore_whitelist);

					$grid->form_field('sms')
						->label('SMS')
						->type('dropdown')
						->options(array
						(
							self::ACTIVATE => __('Activate'),
							self::KEEP => __('Without change')
						))
						->callback(
							'callback::notification_form_field',
							$message->type,
							$message->ignore_whitelist
						);
				}
				
				$grid->form_extra_buttons = array
				(
					"position" => 'top',
					form::label(
						'comment',
						"<b>".__('Comment').":</b>"
					) . form::textarea('comment', '', 'style="margin-left: 30px"')."<br /><br />"
				);

				$name = $cloud->name . ' (' . $cloud_id . ')';

			// breadcrumbs		
			$breadcrumbs = breadcrumbs::add()
					->link('clouds/show_all', __('Clouds'),
							$this->acl_check_view('Clouds_Controller','clouds'))
					->disable_translation()
					->link('clouds/show/'.$cloud->id, $name,
							$this->acl_check_view('Clouds_Controller','clouds'))
					->enable_translation()
					->link('notifications/cloud/'.$cloud->id, 'Notification setting')
					->text($message->name)
					->html();

				$grid->datasource($members);

				$view = new View('main');
				$view->breadcrumbs = $breadcrumbs;
				$view->title = $headline;
				$view->content = new View('show_all');
				$view->content->headline = $headline;
				$view->content->table = $grid;
				$view->content->status_message_info = url_lang::lang('help.notification_settings');
				$view->render(TRUE);
			}
			else
			{
				$ip_address_model = new Ip_address_Model();
				$mia_model = new Messages_ip_addresses_Model();
				$uc_model = new Users_contacts_Model();
				
				$comment		= $_POST["comment"];
				$redirections	= $_POST["redirection"];
				
				$emails	= (isset($_POST["email"]))	? $_POST["email"]	: array();
				$smss	= (isset($_POST["sms"]))	? $_POST["sms"]		: array();
				
				$user_id = $this->session->get('user_id');
				
				$added_redr = 0;
				$deleted_redr = 0;
				$sent_emails = 0;
				$sent_sms = 0;
				
				$info_messages = array();
				
				foreach ($redirections as $member_id => $redirection)
				{
					if ($redirection == self::KEEP)
						continue;
					
					// get all redirection
					$ips = $ip_address_model->get_ip_addresses_of_member(
						$member_id,
						NULL,
						$cloud->id
					);
					
					// delete redirection of these IP address
					foreach ($ips as $ip)
					{
						$mia_model->delete_redirection_of_ip_address(
								$message->id, $ip->id
						);
						$deleted_redr++;
					}
					
					// set new redirection?
					if ($redirection == self::ACTIVATE)
					{
						$added_redr += Message_Model::activate_redirection(
							$message, $ips,
							$user_id, $comment
						);
					}
				}
				
				// info messages
				if ($added_redr)
				{
					$m = 'Redirection has been activated for %s IP addresses';
					$info_messages[] = __($m, $added_redr).'.';
				}
				else
				{
					$m = 'Redirection has been deactivated for %s IP addresses';
					$info_messages[] = __($m, $deleted_redr).'.';
				}
				
				foreach ($emails as $member_id => $email)
				{
					if ($email == self::KEEP)
						continue;
					
					// gets all contacts of member
					$contacts = $uc_model->get_contacts_by_member_and_type(
						$member_id, Contact_Model::TYPE_EMAIL, TRUE
					);
			
					// send email
					$sent_emails += Message_Model::send_emails(
						$message, $contacts, $comment
					);
				}
				
				// info message
				$m = 'E-mail has been sent for %s e-mail addresses';
				$info_messages[] = __($m, $sent_emails).'.';
				
				foreach ($smss as $member_id => $sms)
				{
					if ($sms == self::KEEP)
						continue;
					
					// gets all contacts of member
					$contacts = $uc_model->get_contacts_by_member_and_type(
						$member_id, Contact_Model::TYPE_PHONE, TRUE
					);
			
					// send email
					$sent_sms += Message_Model::send_sms_messages(
						$message, $contacts,
						$user_id, $comment
					);
				}
				
				// info message
				$m = 'SMS message has been sent for %d phone numbers.';
				$info_messages[] = __($m, $sent_sms);
				
				// user notification
				if (count($info_messages))
				{
					status::success(implode('<br />', $info_messages), FALSE);
				}
			
				// redirect
				url::redirect('clouds/show/'.$cloud->id);
			}
		}
	}
	
	/**
	 * Shows members with at least one whitelisted IP address.
	 * 
	 * @author Jiri Svitak
	 */
	public function show_whitelisted_members(
			$limit_results = 50, $order_by = 'id', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// access rights
		if (!$this->acl_check_view('Messages_controller','message'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'registration', 'name', 'street','redirect',  'street_number',
			'town', 'quarter', 'ZIP_code', 'qos_ceil', 'qos_rate', 'entrance_fee',
			'debt_payment_rate', 'current_credit', 'entrance_date', 'comment',
			'balance', 'type_name', 'items_count'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		$filter_form = new Filter_form('m');
		
		$filter_form->add('member_name')
				->type('combo')
				->callback('json/member_name');
		
		$filter_form->add('type')
				->type('combo')
				->values(ORM::factory('enum_type')->get_values(Enum_type_Model::MEMBER_TYPE_ID));
		
		$filter_form->add('whitelisted')
				->type('select')
				->label(__('Whitelist'))
				->values(array
				(
					Ip_address_Model::NO_WHITELIST => __('No whitelist'),
					Ip_address_Model::PERNAMENT_WHITELIST => __('Permanent whitelist'),
					Ip_address_Model::TEMPORARY_WHITELIST => __('Temporary whitelist')
				));
		
		$filter_form->add('balance')
				->type('number');
		
		// load members
		$model_members = new Member_Model();
		$total_members = $model_members->count_whitelisted_members($filter_form->as_sql());
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_members)
			$sql_offset = 0;
		
		$query = $model_members->get_whitelisted_members(
				$sql_offset, (int)$limit_results, $order_by,
				$order_by_direction, $filter_form->as_sql()
		);
		// it creates grid to view all members
		$headline = __('List of whitelisted members');
		
		$grid = new Grid('members', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang').'/members/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_members,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		// database columns - some are commented out because of lack of space
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_field('type');
		
		$grid->order_field('name');
		
		$grid->order_callback_field('whitelisted')
				->label('Whitelist')
				->callback('callback::whitelisted_field');
		
		$grid->order_callback_field('items_count')
				->label('IP address count on the list')
				->callback('callback::items_count_field');
		
		$grid->order_callback_field('balance')
				->callback('callback::balance_field');
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action()
				->icon_action('member')
				->url('members/show')
				->label('Show member');
		
		$actions->add_action('aid')
				->icon_action('transfer')
				->url('transfers/show_by_account')
				->label('Show transfers');
		
		$grid->datasource($query);
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $headline;
		$view->content = new View('show_all');
		$view->content->table = $grid;
		$view->content->headline = $headline;
		$view->render(TRUE);
	}
	
	/**
	 * Sets whitelist type to all IP addresses of member.
	 * 
	 * @author Jiri Svitak
	 * @param integer $member_id
	 */
	public function set_whitelist($member_id = NULL)
	{
		// access rights
		if (!$this->acl_check_edit('Messages_Controller', 'member'))
			Controller::error(ACCESS);
		
		if (!$member_id)
			Controller::warning(PARAMETER);
		
		$member = new Member_Model($member_id);
		
		if (!$member || !$member->id)
			Controller::error(RECORD);
		
		$whitelist_array[Ip_address_Model::NO_WHITELIST] = __('No whitelist');
		$whitelist_array[Ip_address_Model::PERNAMENT_WHITELIST] = __('Permanent whitelist');
		$whitelist_array[Ip_address_Model::TEMPORARY_WHITELIST] = __('Temporary whitelist');
		
		// form
		$form = new Forge('notifications/set_whitelist/'.$member_id);
		
		$form->dropdown('whitelist')
				->options($whitelist_array);
		
		$form->submit('Edit');
		
		if ($form->validate())
		{
			if (!$this->acl_check_edit('Messages_Controller', 'member'))
				Controller::error(ACCESS);
			
			$form_data = $form->as_array();
			$ip_model = new Ip_address_Model();
			$ips = $ip_model->get_ip_addresses_of_member($member_id);
			
			foreach($ips as $ip)
			{
				$ip = new Ip_address_Model($ip->id);
				$ip->whitelisted = $form_data['whitelist'];
				$ip->save();
			}
			
			$users_contacts_model = new Users_contacts_Model();
			$users_contacts_model->set_whitelist_by_member_and_type(
					$form_data['whitelist'], $member_id, Contact_Model::TYPE_EMAIL);
			
			$users_contacts_model->set_whitelist_by_member_and_type(
					$form_data['whitelist'], $member_id, Contact_Model::TYPE_PHONE);
			
			// set flash message
			status::success('Whitelist setting has been successfully set.');
			
			$this->redirect('members/show/'.$member_id);
		}
		else
		{
			$headline = __('Whitelist');
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller' ,'members'))
					->disable_translation()
					->link('members/show/'.$member->id,
							"ID $member->id - $member->name",
							$this->acl_check_view(
									'Members_Controller' ,'members', $member->id
							)
					)
					->text($headline);
			// view
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}
	
	/**
	 * Callback function to validate e-mail notification
	 * 
	 * @author Michal Kliment
	 * @param type $input 
	 */
	public function valid_email_or_sms ($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$email = $input->value;
		
		if ($email == self::ACTIVATE)
		{
			$message = new Message_Model($this->input->post('message_id'));
			
			if ($message->type != Message_Model::DEBTOR_MESSAGE &&
				$message->type != Message_Model::PAYMENT_NOTICE_MESSAGE &&
				$message->type != Message_Model::USER_MESSAGE)
			{
				$input->add_error('required', __(
						'It is not possible activate e-mail notification for this message.'
				));
			}
		}
	}
}
