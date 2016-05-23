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
	// activation type
	/** activate notification */
	const ACTIVATE = 1;
	
	/** keep current settings */
	const KEEP = 2;
	
	/** deactivate notification */
	const DEACTIVATE = 3;
	
	// type
	/** Notification is applicated on members (all its users) */
	const TYPE_MEMBER = 1;
	
	/** Notification is applicated on users */
	const TYPE_USER = 2;
	
	/**
	 * Only enable if notification enabled
	 */
	public function __construct()
	{
		parent::__construct();
		
	    if (!module::e('notification'))
			self::error(ACCESS);
	}
	
	/**
	 * Notification setting for member
	 * 
	 * @author Michal Kliment
	 * @param integer $member_id 
	 */
	public function member($member_id = NULL)
	{
		// add TinyMCE init to page
		TextEditor::$instance_counter++;
			
		// bad parameter
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);
		
		$member = new Member_Model($member_id);
		
		// record doesn't exist
		if (!$member->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_new('Notifications_Controller', 'member', $member->id))
			Controller::error(ACCESS);
		
		$headline = __('Notification setting of member').' '.$member->name;
		
		// gets all user messages
		$arr_messages = array
		(
			NULL => '----- '.__('Select message').' -----'
		) + ORM::factory('message')->where('type', Message_Model::USER_MESSAGE)->select_list();
		
		$form = new Forge('notifications/member/'.$member->id);
		
		$form->dropdown('message_id')
				->label('Message')
				->options($arr_messages)
				->rules('required')
				->add_button('messages');
		
		$form->textarea('comment');
		
		if (module::e('redirection'))
		{
			$selected = Notifications_Controller::ACTIVATE;
			
			if (!$member->notification_by_redirection)
			{
				$selected = Notifications_Controller::KEEP;
			}
			
			$form->dropdown('redirection')
					->options(notification::redirection_form_array())
					->selected($selected);
		}
		
		if (module::e('email'))
		{
			$selected = Notifications_Controller::ACTIVATE;
			
			if (!$member->notification_by_email)
			{
				$selected = Notifications_Controller::KEEP;
			}
			
			$form->dropdown('email')
					->label('E-mail')
					->options(notification::redirection_form_array(TRUE))
					->selected($selected);
		}
		
		if (module::e('sms'))
		{
			$selected = Notifications_Controller::ACTIVATE;
			
			if (!$member->notification_by_sms)
			{
				$selected = Notifications_Controller::KEEP;
			}
			
			$form->dropdown('sms')
					->label('SMS message')
					->options(notification::redirection_form_array(TRUE))
					->selected($selected);
		}
		
		$form->submit('Activate');
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$message = new Message_Model($form_data['message_id']);
			
			// check type
			if ($message->type != Message_Model::USER_MESSAGE)
				self::error(RECORD);
			
			// params			
			$comment = $form_data['comment'];
			$user_id = $this->user_id;
			$redirections = $emails = $smss = array();

			if (isset($form_data['redirection']) && module::e('redirection'))
			{
				$redirections = array($member->id => intval($form_data['redirection']));
			}

			if (isset($form_data['email']) && module::e('email'))
			{
				$emails = array($member->id => intval($form_data['email']));
			}

			if (isset($form_data['sms']) && module::e('sms'))
			{
				$smss = array($member->id => intval($form_data['sms']));
			}

			// notify
			$stats = Notifications_Controller::notify_from_form(
					$message, $user_id, $comment,
					$redirections, $emails, $smss
			);
			// info messages
			$info_messages = notification::build_stats_string(
					$stats, module::e('redirection'), module::e('email'),
					module::e('sms'), module::e('redirection')
			);
			// log action
			if (count($info_messages))
			{
				$un = ORM::factory('user', $user_id)->get_full_name();
				$m = __('User "%s" has activated notification message "%s" on "%s"',
						array($un, __($message->name), $member->name));
				status::success(implode('<br />', $info_messages), FALSE);
				Log_queue_Model::info($m, implode("\n", $info_messages));
			}
			// redirect
			url::redirect('members/show/' . $member->id);
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


			// member settings properties info
			$mediums = array();

			if (!$member->notification_by_redirection && module::e('redirection'))
			{
				$mediums[] = __('redirection', array(), 1);
			}

			if (!$member->notification_by_email && module::e('email'))
			{
				$mediums[] = 'e-mail';
			}

			if (!$member->notification_by_sms && module::e('sms'))
			{
				$mediums[] = 'SMS';
			}

			if (count($mediums))
			{
				status::info('This member do not want to be notified by: %s', 
							 TRUE, implode(', ', $mediums));
			}
			
			// view
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
		// add TinyMCE init to page
		TextEditor::$instance_counter++;
			
		$headline = __('Notification setting of members');
		
		if (!$this->acl_check_new('Notifications_Controller', 'members'))
			Controller::error(ACCESS);
		
		if (!$message_id)
		{
			// gets all user messages
			$arr_messages = array
			(
				NULL => '----- '.__('Select message').' -----'
			) + ORM::factory('message')->where('type', Message_Model::USER_MESSAGE)->select_list();

			$form = new Forge(url::base().url::current(TRUE));

			$form->dropdown('message_id')
					->label('Message')
					->options($arr_messages)
					->rules('required')
					->add_button('messages');

			$form->submit('Next step');

			if ($form->validate())
			{
				$form_data = $form->as_array();	
				
				$message_id = arr::remove('message_id', $form_data);
				url::redirect('notifications/members/'.$message_id.'/'.server::query_string());
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
			if (!$message->id && $message->type != Message_Model::USER_MESSAGE)
				Controller::error(RECORD);
			
			if (!isset($_POST) || !isset($_POST['ids']))
			{		
				$member_model = new Member_Model();
				
				$filter_form = Members_Controller::create_filter_form();
				
				$total_members = $member_model->count_all_members(
						$filter_form->as_sql()
				);
				
				$members = $member_model->get_all_members(
						0, $total_members, 'id', 'ASC',
						$filter_form->as_sql()
				);

				$grid = new Grid('notifications/members', '', array
				(
					'use_paginator'	=> false,
					'use_selector'	=> false,
					'total_items'	=> count($members)
				));

				$grid->callback_field('member_id')
						->label('Name')
						->callback('callback::member_field');

				$grid->callback_field('type')
						->callback('callback::member_type_field');

				$grid->callback_field('balance')
						->callback('callback::balance_field');

				$grid->callback_field('interrupt')
					->label('Membership interrupt')
					->callback('callback::active_field')
					->class('center');
				
				$grid->callback_field('whitelisted')
						->label('Whitelist')
						->callback('callback::whitelisted_field')
						->class('center');
				
				if (module::e('redirection') &&
					Message_Model::has_redirection_content($message->type))
				{
					$grid->form_field('redirection')
							->type('dropdown')
							->options(notification::redirection_form_array())
							->callback(
								'callback::notification_form_field', $message
							)->class('center');
				}
				
				if (module::e('email') &&
					Message_Model::has_email_content($message->type))
				{
					$grid->form_field('email')
						->label('E-mail')
						->type('dropdown')
						->options(notification::redirection_form_array(TRUE))
						->callback(
							'callback::notification_form_field', $message
						)->class('center');
				}

				if (module::e('sms') &&
					Message_Model::has_sms_content($message->type))
				{
					$grid->form_field('sms')
						->label('SMS')
						->type('dropdown')
						->options(notification::redirection_form_array(TRUE))
						->callback(
							'callback::notification_form_field', $message
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
				
				$grid->form_submit_value = __('Activate');

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
				// params
				$comment = $_POST['comment'];
				$user_id = $this->user_id;
				$redirections = $emails = $smss = array();
				
				if (isset($_POST['redirection']) && module::e('redirection'))
				{
					$redirections = $_POST['redirection'];
				}

				if (isset($_POST['email']) && module::e('email'))
				{
					$emails = $_POST['email'];
				}
				
				if (isset($_POST['sms']) && module::e('sms'))
				{
					$smss = $_POST['sms'];
				}
				
				// notify
				$stats = Notifications_Controller::notify_from_form(
						$message, $user_id, $comment,
						$redirections, $emails, $smss
				);
				// info messages
				$info_messages = notification::build_stats_string(
						$stats, module::e('redirection'), module::e('email'), 
						module::e('sms'), module::e('redirection')
				);
				// log action
				if (count($info_messages))
				{
					$un = ORM::factory('user', $user_id)->get_full_name();
					$m = __('User "%s" has activated notification message "%s" on "%s"',
							array($un, __($message->name), __('filtered members', array(), 1)));
					status::success(implode('<br />', $info_messages), FALSE);
					Log_queue_Model::info($m, implode("\n", $info_messages));
				}
				// redirect
				url::redirect('members/show_all/'.server::query_string());
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
		// add TinyMCE init to page
		TextEditor::$instance_counter++;
			
		// access control
		if (!Settings::get('networks_enabled'))
			Controller::error (ACCESS);
		
		if (!$this->acl_check_new('Notifications_Controller', 'subnet'))
			Controller::error(ACCESS);
		
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
			$arr_messages = array
			(
				NULL => '----- '.__('select message').' -----'
			) + ORM::factory('message')->where('type', Message_Model::USER_MESSAGE)->select_list();

			$form = new Forge('notifications/subnet/'.$subnet->id);

			$form->dropdown('message_id')
					->label('Message')
					->options($arr_messages)
					->rules('required')
					->add_button('messages');

			$form->submit('Next step');

			if ($form->validate())
			{
				$form_data = $form->as_array();	
				url::redirect('notifications/subnet/'.$subnet_id.'/'.$form_data['message_id']);
			}

			$subnet_text = $subnet->name." ($subnet->network_address/"
					.network::netmask2cidr($subnet->netmask) .")";

			$breadcrumbs = breadcrumbs::add()
					->link('subnets/show_all', __('Subnets'),
							$this->acl_check_view('Subnets_Controller','subnet'))
					->disable_translation()
					->link('subnets/show/'.$subnet->id, $subnet_text,
							$this->acl_check_view('Subnets_Controller','subnet'))
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
			if (!$message->id || $message->type != Message_Model::USER_MESSAGE)
				Controller::error(RECORD);
			
			if (!isset($_POST) || !isset($_POST["ids"]))
			{
				$order_by = 'whitelisted DESC, id ASC';

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

				$grid->callback_field('member_id')
						->label('Name')
						->callback('callback::member_field');

				$grid->callback_field('type')
						->callback('callback::member_type_field');

				$grid->callback_field('balance')
						->callback('callback::balance_field');

				$grid->callback_field('interrupt')
					->label('Membership interrupt')
					->callback('callback::active_field')
					->class('center');

				$grid->callback_field('allowed')
					->label('Allowed subnet')
					->callback('callback::active_field')
					->class('center');
				
				$grid->callback_field('whitelisted')
						->label('Whitelist')
						->callback('callback::whitelisted_field')
						->class('center');
				
				if (module::e('redirection') &&
					Message_Model::has_redirection_content($message->type))
				{
					$grid->form_field('redirection')
							->type('dropdown')
							->options(notification::redirection_form_array())
							->callback(
								'callback::notification_form_field', $message
							)->class('center');
				}

				// only if E-mail is enabled
				if (module::e('email') &&
					Message_Model::has_email_content($message->type))
				{
					$grid->form_field('email')
						->label('E-mail')
						->type('dropdown')
						->options(notification::redirection_form_array(TRUE))
						->callback(
							'callback::notification_form_field', $message
						)->class('center');
				}

				if (module::e('sms') &&
					Message_Model::has_sms_content($message->type))
				{
					$grid->form_field('sms')
						->label('SMS')
						->type('dropdown')
						->options(notification::redirection_form_array(TRUE))
						->callback(
							'callback::notification_form_field', $message
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
				
				$grid->form_submit_value = __('Activate');

				$subnet_text = $subnet->name." ($subnet->network_address/"
						.network::netmask2cidr($subnet->netmask) .")";

				$breadcrumbs = breadcrumbs::add()
						->link('subnets/show_all', __('Subnets'),
								$this->acl_check_view('Subnets_Controller','subnet'))
						->disable_translation()
						->link('subnets/show/'.$subnet->id, $subnet_text,
								$this->acl_check_view('Subnets_Controller','subnet'))
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
				// params
				$comment = $_POST['comment'];
				$user_id = $this->user_id;
				$redirections = $emails = $smss = array();
				
				if (isset($_POST['redirection']) && module::e('redirection'))
				{
					$redirections = $_POST['redirection'];
				}

				if (isset($_POST['email']) && module::e('email'))
				{
					$emails = $_POST['email'];
				}
				
				if (isset($_POST['sms']) && module::e('sms'))
				{
					$smss = $_POST['sms'];
				}
				
				// notify
				$stats = Notifications_Controller::notify_from_form(
						$message, $user_id, $comment,
						$redirections, $emails, $smss
				);
				// info messages
				$info_messages = notification::build_stats_string(
						$stats, module::e('redirection'), module::e('email'), 
						module::e('sms'), module::e('redirection')
				);
				// log action
				if (count($info_messages))
				{
					$un = ORM::factory('user', $user_id)->get_full_name();
					$m = __('User "%s" has activated notification message "%s" on "%s"',
							array($un, __($message->name), $subnet->name));
					status::success(implode('<br />', $info_messages), FALSE);
					Log_queue_Model::info($m, implode("\n", $info_messages));
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
		// add TinyMCE init to page
		TextEditor::$instance_counter++;
		
		// bad parameter
		if (!$cloud_id || !is_numeric($cloud_id))
			Controller::warning(PARAMETER);
		
		if (!$this->acl_check_new('Notifications_Controller', 'cloud'))
			Controller::error(ACCESS);
		
		$cloud = new Cloud_Model($cloud_id);
		
		// record doesn't exist
		if (!$cloud->id)
			Controller::error(RECORD);
		
		$headline = __('Notification setting of cloud').' '.$cloud->name;
		
		if (!$message_id)
		{
			// gets all user messages
			$arr_messages = array
			(
				NULL => '----- '.__('select message').' -----'
			) + ORM::factory('message')->where('type', Message_Model::USER_MESSAGE)->select_list();

			$form = new Forge('notifications/cloud/'.$cloud->id);

			$form->dropdown('message_id')
					->label('Message')
					->options($arr_messages)
					->rules('required')
					->add_button('messages');

			$form->submit('Next step');

			if ($form->validate())
			{
				$form_data = $form->as_array();	
				url::redirect('notifications/cloud/'.$cloud_id.'/'.$form_data['message_id']);
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
			if (!$message->id || $message->type != Message_Model::USER_MESSAGE)
				Controller::error(RECORD);
			
			if (!isset($_POST) || !isset($_POST['ids']))
			{
				$order_by = 'whitelisted DESC, id ASC';

				$member_model = new Member_Model();

				$members = $member_model->get_members_of_cloud($cloud->id, $order_by);

				$grid = new Grid('notifications/cloud', '', array
				(
					'use_paginator' => FALSE,
					'use_selector' => FALSE,
					'total_items' =>  count($members)
				));

				$grid->callback_field('member_id')
						->label('Name')
						->callback('callback::member_field');

				$grid->callback_field('type')
						->callback('callback::member_type_field');

				$grid->callback_field('balance')
						->callback('callback::balance_field');
				
				$grid->callback_field('interrupt')
						->label('Membership interrupt')
						->callback('callback::active_field')
						->class('center');

				$grid->callback_field('allowed')
						->label('Allowed subnet')
						->callback('callback::active_field')
						->class('center');
				
				$grid->callback_field('whitelisted')
						->label('Whitelist')
						->callback('callback::whitelisted_field')
						->class('center');
				
				if (module::e('redirection') &&
					Message_Model::has_redirection_content($message->type))
				{
					$grid->form_field('redirection')
							->label('Redirection')
							->type('dropdown')
							->options(notification::redirection_form_array())
							->callback(
								'callback::notification_form_field', $message
							);
				}

				// only if E-mail is enabled
				if (module::e('email') &&
					Message_Model::has_email_content($message->type))
				{
					$grid->form_field('email')
							->label('E-mail')
							->type('dropdown')
							->options(notification::redirection_form_array(TRUE))
							->callback(
								'callback::notification_form_field', $message
							);
				}

				if (module::e('sms') &&
					Message_Model::has_sms_content($message->type))
				{
					$grid->form_field('sms')
							->label('SMS')
							->type('dropdown')
							->options(notification::redirection_form_array(TRUE))
							->callback(
								'callback::notification_form_field', $message
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
				
				$grid->form_submit_value = __('Activate');

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
				// params
				$comment = $_POST['comment'];
				$user_id = $this->user_id;
				$redirections = $emails = $smss = array();
				
				if (isset($_POST['redirection']) && module::e('redirection'))
				{
					$redirections = $_POST['redirection'];
				}

				if (isset($_POST['email']) && module::e('email'))
				{
					$emails = $_POST['email'];
				}
				
				if (isset($_POST['sms']) && module::e('sms'))
				{
					$smss = $_POST['sms'];
				}
				
				// notify
				$stats = Notifications_Controller::notify_from_form(
						$message, $user_id, $comment,
						$redirections, $emails, $smss
				);
				// info messages
				$info_messages = notification::build_stats_string(
						$stats, module::e('redirection'),  module::e('email'), 
						module::e('sms'), module::e('redirection')
				);
				// log action
				if (count($info_messages))
				{
					$un = ORM::factory('user', $user_id)->get_full_name();
					$m = __('User "%s" has activated notification message "%s" on "%s"',
							array($un, __($message->name), $cloud->name));
					status::success(implode('<br />', $info_messages), FALSE);
					Log_queue_Model::info($m, implode("\n", $info_messages));
				}
				// redirect
				url::redirect('clouds/show/'.$cloud->id);
			}
		}
	}
	
	/**
	 * Notification setting for device admins
	 * 
	 * @author David Raška
	 * @param integer $device_id 
	 */
	public function device($device_id = NULL)
	{
		// add TinyMCE init to page
		TextEditor::$instance_counter++;
			
		// bad parameter
		if (!$device_id || !is_numeric($device_id))
			Controller::warning(PARAMETER);
		
		$device = new Device_Model($device_id);
		
		// record doesn't exist
		if (!$device->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_new('Notifications_Controller', 'device'))
			Controller::error(ACCESS);
		
		$headline = __('Notification setting of device admins').' '.$device->name;
		
		// gets all user messages
		$arr_messages = array
		(
			NULL => '----- '.__('Select message').' -----'
		) + ORM::factory('message')->where('type', Message_Model::USER_MESSAGE)->select_list();
		
		$form = new Forge('notifications/device/'.$device->id);
		
		$form->dropdown('message_id')
				->label('Message')
				->options($arr_messages)
				->rules('required')
				->add_button('messages');
		
		$form->textarea('comment');
		
		if (module::e('redirection'))
		{
			$form->dropdown('redirection')
					->options(notification::redirection_form_array());
		}
		
		if (module::e('email'))
		{
			$form->dropdown('email')
					->label('E-mail')
					->options(notification::redirection_form_array(TRUE));
		}
		
		if (module::e('sms'))
		{
			$form->dropdown('sms')
					->label('SMS message')
					->options(notification::redirection_form_array(TRUE));
		}
		
		$form->submit('Activate');
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$message = new Message_Model($form_data['message_id']);
			
			// check type
			if ($message->type != Message_Model::USER_MESSAGE)
				self::error(RECORD);
			
			// params			
			$comment = $form_data['comment'];
			$user_id = $this->user_id;
			$redirection = $email = $sms = FALSE;
			$redirections = $emails = $smss = array();
			
			// device admins
			$da = ORM::factory('device_admin')->where('device_id', $device_id)->find_all();

			if (isset($form_data['redirection']) && module::e('redirection'))
			{
				$redirection = $form_data['redirection'];
			}

			if (isset($form_data['email']) && module::e('email'))
			{
				$email = $form_data['email'];
			}

			if (isset($form_data['sms']) && module::e('sms'))
			{
				$sms = $form_data['sms'];
			}
			
			foreach ($da AS $admin)
			{
				if ($redirection)
				{
					$redirections[$admin->user_id] = $redirection;
				}
				if ($email)
				{
					$emails[$admin->user_id] = $email;
				}
				if ($sms)
				{
					$smss[$admin->user_id] = $sms;
				}
			}
			
			// notify
			$stats = Notifications_Controller::notify_from_form(
					$message, $user_id, $comment,
					$redirections, $emails, $smss,
					self::TYPE_USER
			);
			// info messages
			$info_messages = notification::build_stats_string(
					$stats, module::e('redirection'), module::e('email'),
					module::e('sms'), module::e('redirection')
			);
			// log action
			if (count($info_messages))
			{
				$un = ORM::factory('user', $user_id)->get_full_name();
				$m = __('User "%s" has activated notification message "%s" on "%s"',
						array($un, __($message->name), $device->name));
				status::success(implode('<br />', $info_messages), FALSE);
				Log_queue_Model::info($m, implode("\n", $info_messages));
			}

			// redirect
			$this->redirect('devices/show/' . $device->id);
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
						->link('members/show_all', 'Members',
								$this->acl_check_view('Members_Controller','members'))
						->disable_translation()
						->link('members/show/' . $device->user->member->id,
								'ID ' . $device->user->member->id . ' - ' . $device->user->member->name,
								$this->acl_check_view('Members_Controller','members', $device->user->member->id))
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
						->link('devices/show/' . $device->id, $device->name,
								$this->acl_check_view('Devices_Controller', 'devices',$device->user->member_id))
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
	 * Notification setting for devices admins
	 * 
	 * @author David Raška
	 * @param integer $message_id
	 */
	public function devices($message_id = NULL)
	{	
		// add TinyMCE init to page
		TextEditor::$instance_counter++;
			
		$headline = __('Notification setting of devices admins');
		
		if (!$this->acl_check_new('Notifications_Controller', 'devices'))
			Controller::error(ACCESS);
		
		if (!$message_id)
		{
			// gets all user messages
			$arr_messages = array
			(
				NULL => '----- '.__('Select message').' -----'
			) + ORM::factory('message')->where('type', Message_Model::USER_MESSAGE)->select_list();

			$form = new Forge(url::base().url::current(TRUE));

			$form->dropdown('message_id')
					->label('Message')
					->options($arr_messages)
					->rules('required')
					->add_button('messages');

			$form->submit('Next step');

			if ($form->validate())
			{
				$form_data = $form->as_array();	
				
				$message_id = arr::remove('message_id', $form_data);
				url::redirect('notifications/devices/'.$message_id.'/'.server::query_string());
			}

			$breadcrumbs = breadcrumbs::add()
					->link('devices/show_all/'.server::query_string(), 'Devices', 
						$this->acl_check_view('Devices_Controller', 'devices'))
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
			if (!$message->id && $message->type != Message_Model::USER_MESSAGE)
				Controller::error(RECORD);
			
			if (!isset($_POST) || !isset($_POST['ids']))
			{		
				$device_admin_model = new Device_admin_Model();
				
				$filter_form = new Filter_form('d');
		
				$filter_form->add('name')
					->callback('json/device_name');

				$filter_form->add('type')
					->type('select')
					->values(ORM::factory('Enum_type')
					->get_values(Enum_type_model::DEVICE_TYPE_ID));

				$filter_form->add('trade_name')
					->callback('json/device_trade_name');

				$filter_form->add('user_name')
					->type('combo')
					->label('Firstname of user')
					->callback('json/user_name');

				$filter_form->add('user_surname')
					->type('combo')
					->label('Surname of user')
					->callback('json/user_surname');

				$filter_form->add('device_member_name')
					->callback('json/member_name');

				$filter_form->add('login')
					->label('Username')
					->callback('json/device_login');

				$filter_form->add('password')
					->callback('json/device_password');

				$filter_form->add('price')
					->type('number');

				$filter_form->add('payment_rate')
					->label('Monthly payment rate')
					->type('number');

				$filter_form->add('buy_date')
					->type('date');

				$filter_form->add('town')
					->type('combo')
					->callback('json/town_name');

				$filter_form->add('street')
					->type('combo')
					->callback('json/street_name');

				$filter_form->add('street_number')
					->type('number');

				$filter_form->add('mac')
						->label('MAC address')
						->class('mac')
						->callback('json/iface_mac');

				$filter_form->add('comment');

				$filter_form->add('cloud')
					->type('select')
					->values(ORM::factory('cloud')->select_list());
				
				$total_devices_admins = $device_admin_model->count_all_devices_admins(
						$filter_form->as_sql()
				);
				
				$devices_admins = $device_admin_model->get_all_devices_admins(array
				(
					'offset'					=> 0,
					'limit'						=> (int) $total_devices_admins,
					'order_by'					=> 'member_id',
					'order_by_direction'		=> 'ASC',
					'filter_sql'				=> $filter_form->as_sql()
				));

				$grid = new Grid('notifications/devices', '', array
				(
					'use_paginator'	=> false,
					'use_selector'	=> false,
					'total_items'	=> count($devices_admins)
				));
				
				$grid->link_field('dau_id')
						->link('users/show', 'dau_name')
						->label('Name');
				
				$grid->callback_field('member_id')
						->label('Member name')
						->callback('callback::member_field');

				$grid->callback_field('member_type')
						->label('Type')
						->callback('callback::member_type_field');
				
				$grid->callback_field('whitelisted')
						->label('Whitelist')
						->callback('callback::whitelisted_field')
						->class('center');
				
				if (module::e('redirection') &&
					Message_Model::has_redirection_content($message->type))
				{
					$grid->form_field('redirection')
							->type('dropdown')
							->options(notification::redirection_form_array())
							->callback(
								'callback::notification_form_field', $message
							)->class('center');
				}
				
				if (module::e('email') &&
					Message_Model::has_email_content($message->type))
				{
					$grid->form_field('email')
						->label('E-mail')
						->type('dropdown')
						->options(notification::redirection_form_array(TRUE))
						->callback(
							'callback::notification_form_field', $message
						)->class('center');
				}

				if (module::e('sms') &&
					Message_Model::has_sms_content($message->type))
				{
					$grid->form_field('sms')
						->label('SMS')
						->type('dropdown')
						->options(notification::redirection_form_array(TRUE))
						->callback(
							'callback::notification_form_field', $message
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
				
				$grid->form_submit_value = __('Activate');

				$breadcrumbs = breadcrumbs::add()
					->link('devices/show_all/'.server::query_string(), 'Devices', 
						$this->acl_check_view('Devices_Controller', 'devices'))
					->link('notifications/devices/'.server::query_string(), 'Notification setting')
					->text($message->name)
					->html();
				

				$grid->datasource($devices_admins);

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
				// params
				$comment = $_POST['comment'];
				$user_id = $this->user_id;
				$redirection = $email = $sms = array();
				
				if (isset($_POST['redirection']) && module::e('redirection'))
				{
					$redirection = $_POST['redirection'];
				}

				if (isset($_POST['email']) && module::e('email'))
				{
					$email = $_POST['email'];
				}
				
				if (isset($_POST['sms']) && module::e('sms'))
				{
					$sms = $_POST['sms'];
				}
				
				// notify
				$stats = Notifications_Controller::notify_from_form(
						$message, $user_id, $comment,
						$redirection, $email, $sms,
						self::TYPE_USER
				);
				// info messages
				$info_messages = notification::build_stats_string(
						$stats, module::e('redirection'), module::e('email'), 
						module::e('sms'), module::e('redirection')
				);
				// log action
				if (count($info_messages))
				{
					$un = ORM::factory('user', $user_id)->get_full_name();
					$m = __('User "%s" has activated notification message "%s" on "%s"',
							array($un, __($message->name), __('filtered devices', array(), 1)));
					status::success(implode('<br />', $info_messages), FALSE);
					Log_queue_Model::info($m, implode("\n", $info_messages));
				}
				
				// redirect
				$this->redirect('devices/show_all'.server::query_string());
			}
		}
	}
	
	/**
	 * Activate notification of message for the given memebers and given
	 * restrictions.
	 * 
	 * This method checks internally if redir/e-mail/sms are enabled.
	 * 
	 * @author Ondrej Fibich
	 * @param Message_Model $message Message that should be notified
	 * @param array $members Array of members object (Database_Result or 
	 *						 Member_model or some associative array)
	 *						 that contains properties: member_id, whitelisted
	 * @param integer $user_id Who notify or on empty association main user [optional]
	 * @param string $comment Comment for the message [optional]
	 * @param boolean $activate_redir Should be redirection activated [optional]
	 * @param boolean $activate_email Should be E-mail activated [optional]
	 * @param boolean $activate_sms Should be SMS activated [optional]
	 * @param boolean $truncate_redir Should be all redirection of this type removed
	 *								before adding [optional]
	 * @param boolean $remove_redir This option my be set to TRUE only for
	 *								removing of redirection for some members.
	 *								Commonly it is used with previous bool params
	 *								setted to FALSE for deactivation. [optional]
	 * @param boolean $notify_former_members Can be former members notified? [optional]
	 * @param boolean $notify_interrupted_members Can be interrupted members notified? [optional]
	 * @param integer $notification_type Defines type of notification:
	 *   - notification to members (members arrays contains member IDs) [default]
	 *   - notification to users (members arrays contains user IDs)
	 * @param boolean $ignore_member_notif_settings Should be member notification setting ignored?
	 * @return array stats of count of added (keys redirection, email, sms)
	 * @throws Exception On any error with translated message
	 */
	public static function notify(
			Message_Model $message, $members, $user_id = NULL, $comment = NULL,
			$activate_redir = TRUE, $activate_email = TRUE,
			$activate_sms = TRUE, $truncate_redir = FALSE,
			$remove_redir = FALSE, $notify_former_members = FALSE,
			$notify_interrupted_members = FALSE,
			$notification_type = self::TYPE_MEMBER,
			$ignore_member_notif_settings = FALSE)
	{
		// variables
		$error_prefix = __('Error during automatic activation of notification messages');
		$ip_model = new Ip_address_Model();
		$uc_model = new Users_contacts_Model();
		$mia_model = new Messages_ip_addresses_Model();
		$removed_redr = $added_redr = $added_email = $added_sms = 0;
		
		// not set user => association action
		if (empty($user_id))
		{
			$association = new Member_Model(Member_Model::ASSOCIATION);
			$user_id = $association->get_main_user();
			unset($association);
		}
		
		// member notification settings only enabled for self cancel messages 
		$ignore_mnotif_settings = $ignore_member_notif_settings || !$message->self_cancel;
		
		// get member IDs or user IDS array
		$member_ids = array();
		
		foreach ($members as $member)
		{
			// convert object to array
			if (is_object($member))
			{
				$member = get_object_vars($member);
			}
			// check whitelist
			if ($member['whitelisted'] && !$message->ignore_whitelist)
			{
				continue;
			}
			// add ID
			$member_ids[] = $member['member_id'];
		}
		
		// get all IPs of members or users
		$member_ips = array();
		$member_ips_ids = array();
		
		if (module::e('redirection'))
		{
			if ($notification_type == self::TYPE_USER)
			{
				$member_ips = $ip_model->get_ip_addresses_of_user(
					$member_ids, NULL, NULL, $ignore_mnotif_settings
				);
			}
			else
			{
				$member_ips = $ip_model->get_ip_addresses_of_member(
					$member_ids, NULL, NULL, $ignore_mnotif_settings
				);
			}
			
			foreach ($member_ips as $member_ip)
			{
				$member_ips_ids[] = $member_ip->id;
			}
		}
		
		// deactivate redirection
		if (module::e('redirection'))
		{
			if ($truncate_redir) // deactivate all
			{
				$removed_redr = $message->deactivate_message($message->id);
			}
			else if ($remove_redir) // deactivate only selection
			{
				$removed_redr = $mia_model->delete_redirection_of_ip_address(
						$message->id, $member_ips_ids
				);
			}
		}
		// activate redirection
		if (module::e('redirection') && $activate_redir &&
            Message_Model::has_redirection_content($message->type))
		{
			try
			{
				// deactivate if not already deactivated
				if (!$truncate_redir && !$remove_redir)
				{
					$removed_redr = $mia_model->delete_redirection_of_ip_address(
							$message->id, $member_ips_ids
					);
				}
				// set new redirection
				$added_redr += Message_Model::activate_redirection(
						$message, $member_ips, $user_id
				);
			}
			catch (Exception $e)
			{
				$m = $error_prefix . ': ' . __('redirection', array(), 1)
					. ' (' . __($message->name) . ')';
				throw new Exception($m, 0, $e);
			}
		}
		// activate E-mail notification
		if (module::e('email') && $activate_email &&
            Message_Model::has_email_content($message->type))
		{
			try
			{
				// gets all contacts of member/user
				if ($notification_type == self::TYPE_USER)
				{
					$contacts = $uc_model->get_contacts_by_user_and_type(
							$member_ids, Contact_Model::TYPE_EMAIL,
							$message->ignore_whitelist, $notify_former_members,
							$notify_interrupted_members, $ignore_mnotif_settings
					);
				}
				else
				{
					$contacts = $uc_model->get_contacts_by_member_and_type(
							$member_ids, Contact_Model::TYPE_EMAIL,
							$message->ignore_whitelist, $notify_former_members,
							$notify_interrupted_members, $ignore_mnotif_settings
					);
				}
				// send email
				$added_email = Message_Model::send_emails(
						$message, $contacts, $comment
				);
			}
			catch (Exception $e)
			{
				$m = $error_prefix . ': e-mail (' . __($message->name) . ')';
				throw new Exception($m, 0, $e);
			}
		}
		// activate SMS notification
		if (module::e('sms') && $activate_sms &&
            Message_Model::has_sms_content($message->type))
		{
			try
			{
				// gets all contacts of member/user
				if ($notification_type == self::TYPE_USER)
				{
					$contacts = $uc_model->get_contacts_by_user_and_type(
							$member_ids, Contact_Model::TYPE_PHONE,
							$message->ignore_whitelist, $notify_former_members,
							$notify_interrupted_members, $ignore_mnotif_settings
					);
				}
				else					
				{
					$contacts = $uc_model->get_contacts_by_member_and_type(
							$member_ids, Contact_Model::TYPE_PHONE,
							$message->ignore_whitelist, $notify_former_members,
							$notify_interrupted_members, $ignore_mnotif_settings
					);
				}
				// send email
				$added_sms = Message_Model::send_sms_messages(
						$message, $contacts, $user_id, $comment
				);
			}
			catch (Exception $e)
			{
				$m = $error_prefix . ': SMS (' . __($message->name) . ')';
				throw new Exception($m, 0, $e);
			}
		}
		// return stats
		return array
		(
			'redirection'			=> $added_redr,
			'redirection_removed'	=> $removed_redr,
			'email'					=> $added_email,
			'sms'					=> $added_sms
		);
	}
		
	/**
	 * Transform the result of notification form that is used in many controllers
	 * (Message_Controller#activate, Notifications_Controller#members, ...)
	 * to the form that is acceptable by notify method and make notification
	 * by using notify menthod of this class. 
	 * 
	 * This method checks internally if redir/e-mail/sms are enabled.
	 * 
	 * @author Ondrej Fibich
	 * @see Notifications_Controller#notify
	 * 
	 * @param Message_Model $message Message that should be notified
	 * @param integer $user_id Who notify or on empty association main user
	 * @param string $comment Comment for the message
	 * @param array $to_redirect Array with affected member IDs or user IDs
	 *								  as key and each value is one of the KEEP,
	 *								  ACTIVATE and DEACTIVATE constants
	 * @param array $to_notify_by_email Array with affected member IDs or user IDs
	 *								  as key and each value is one of the KEEP,
	 *								  ACTIVATE constants
	 * @param array $to_notify_by_sms Array with affected member IDs or user IDs
	 *								  as key and each value is one of the KEEP,
	 *								  ACTIVATE constants
	 * @param integer $notification_type Defines type of notification:
	 *   - notification to members (all previous arrays contains member IDs) [default]
	 *   - notification to users (all previous arrays contains user IDs)
	 * @return array stats of count of added (keys redirection, email, sms)
	 * @throws Exception On any error with translated message
	 */
	public static function notify_from_form(
			Message_Model $message, $user_id, $comment,
			$to_redirect, $to_notify_by_email, $to_notify_by_sms,
			$notification_type = self::TYPE_MEMBER)
	{
		// stats array
		$stats = array();
		
		/* disable old redirection */
		
		// remove members that are highlighted as KEEP and ACTIVATE
		$dis_redir = array_diff($to_redirect, array(self::KEEP, self::ACTIVATE));
		$dis_redir_assoc = self::_make_array_for_notify(array_keys($dis_redir));
		
		$stats[] = self::notify(
				$message, $dis_redir_assoc, $user_id, $comment,
				FALSE, FALSE, FALSE, FALSE, TRUE, FALSE, FALSE,
				$notification_type, TRUE
		);
		
		/* make new redirection */
		
		// remove members that are highlighted as KEEP and DEACTIVATE
		$en_redir = array_diff($to_redirect, array(self::KEEP, self::DEACTIVATE));
		$en_redir_assoc = self::_make_array_for_notify(array_keys($en_redir));
		
		$stats[] = self::notify(
				$message, $en_redir_assoc, $user_id, $comment,
				TRUE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
				$notification_type, TRUE
		);
		
		/* send email notification */
		
		// remove members that are highlighted as KEEP
		$em_notif = array_diff($to_notify_by_email, array(self::KEEP));
		$em_notif_assoc = self::_make_array_for_notify(array_keys($em_notif));
		
		$stats[] = self::notify(
				$message, $em_notif_assoc, $user_id, $comment,
				FALSE, TRUE, FALSE, FALSE, FALSE, TRUE, TRUE,
				$notification_type, TRUE
		);
		
		/* send SMS notification */
		
		// remove members that are highlighted as KEEP
		$sms_notif = array_diff($to_notify_by_sms, array(self::KEEP));
		$sms_notif_assoc = self::_make_array_for_notify(array_keys($sms_notif));
		
		$stats[] = self::notify(
				$message, $sms_notif_assoc, $user_id, $comment,
				FALSE, FALSE, TRUE, FALSE, FALSE, TRUE, TRUE,
				$notification_type, TRUE
		);
		
		/* sum stats from all parts */
		$stat = array();
		
		foreach ($stats as $s)
		{
			foreach ($s as $k => $v)
			{
				if (!array_key_exists($k, $stat))
				{
					$stat[$k] = intval($v);
				}
				else
				{
					$stat[$k] += intval($v);
				}
			}
		}
		
		return $stat;
	}
	
	/**
	 * Transforms array of member/user IDs into an array that is acceptable by notify
	 * method of this class. The new array contains sub arrays that are created by
	 * combining of member/user IDs and whitelist attribute.
	 * 
	 * @see Notifications_Controller#notify_from_form
	 * @param array $member_ids
	 * @param integer $whitelisted
	 * @return array
	 */
	private static function _make_array_for_notify($member_ids, $whitelisted = 0)
	{
		$assoc = range(0, max(0, count($member_ids) - 1));
		$i = 0;
		
		foreach ($member_ids as $member_id)
		{
			$assoc[$i++] = array
			(
				'member_id'		=> $member_id,
				'whitelisted'	=> $whitelisted,
			);
		}
		
		return $assoc;
	}
	
}
