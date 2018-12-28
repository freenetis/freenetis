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
 * Handles messages, which are used as messages for notification.
 * 
 * @see Notifications_Controller
 * @package Controller
 */
class Messages_Controller extends Controller
{
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
	 * Redirects to show all
	 */
	public function index()
	{
		url::redirect('messages/show_all');
	}

	/**
	 * Shows all redirection messages.
	 * 
	 * @todo replace callback field by action grouped fields
	 * @author Jiri Svitak
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 500, $order_by = 'id',
			$order_by_direction = 'asc', $page_word = null, $page = 1)
	{
		// access rights
		if (!$this->acl_check_view('Messages_Controller', 'message'))
			Controller::error(ACCESS);

		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		$allowed_order_type = array
		(
			'id', 'message'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		$filter_form = new Filter_form('m');
		
		$filter_form->add('name');
		
		$filter_form->add('type')
			->type('select')
			->values(Message_Model::get_types());
		
		if (module::e('redirection'))
		{
			$filter_form->add('text')
				->label('Content of the message for redirection');
		}
		
		if (module::e('email'))
		{
			$filter_form->add('email_text')
				->label('Content of the message for e-mail');
		}
		
		if (module::e('sms'))
		{
			$filter_form->add('sms_text')
				->label('Content of the message for sms');
		}
		
		$filter_form->add('self_cancel')
			->label('User-cancelable')
			->type('select')
			->values(Message_Model::get_self_cancel_messages());
		
		$filter_form->add('ignore_whitelist')
			->type('select')
			->values(arr::bool());

		// model
		$message_model = new Message_Model();
		
		$total_messages = $message_model->count_all_messages($filter_form->as_sql());

		if (($sql_offset = ($page - 1) * $limit_results) > $total_messages)
			$sql_offset = 0;

		$messages = $message_model->get_all_messages(
				$sql_offset, $limit_results, $order_by, $order_by_direction,
				$filter_form->as_sql()
		);

		$headline = __('Messages');
		
		$grid = new Grid('messages', null, array
		(
				'current'					=> $limit_results,
				'selector_increace'			=> 500,
				'selector_min'				=> 500,
				'selector_max_multiplier'	=> 10,
				'base_url'					=> Config::get('lang')
											. '/messages/show_all/' . $limit_results
											. '/' . $order_by . '/' . $order_by_direction,
				'uri_segment'				=> 'page',
				'total_items'				=> isset($total_messages) ? $total_messages : 0,
				'items_per_page'			=> $limit_results,
				'style'						=> 'classic',
				'order_by'					=> $order_by,
				'order_by_direction'		=> $order_by_direction,
				'limit_results'				=> $limit_results,
				'filter'					=> $filter_form
		));
		
		$grid->add_new_button('messages/add', __('Add new message'));
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_callback_field('message')
				->label('Name')
				->callback('callback::message_field');
		
		$grid->callback_field('type')
				->callback('callback::message_type_field');
		
		$grid->callback_field('self_cancel')
				->label(__('SC').' '.help::hint('self_cancel'))
				->callback('callback::message_self_cancel_field');
		
		$grid->callback_field('ignore_whitelist')
				->label(__('IW').' '.help::hint('ignore_whitelist'))
				->callback('callback::boolean');
		
		if ($this->acl_check_view('Messages_Controller', 'preview'))
		{
			$grid->callback_field('id')
						->label('Preview')
						->callback('callback::message_preview_field');
		}
		
		if ($this->acl_check_edit('Messages_Controller', 'activate'))
		{
			$grid->callback_field('id')
					->label('Activate')
					->callback('callback::message_activate_field');
		}

		if ($this->acl_check_edit('Messages_Controller', 'deactivate'))
		{
			$grid->callback_field('id')
					->label('Deactivate')
					->callback('callback::message_deactivate_field');
		}
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Messages_Controller', 'message'))
		{
			$actions->add_conditional_action()
					->condition('is_activatable_directlty')
					->icon_action('show')
					->url('messages/show');
		}
		
		if ($this->acl_check_edit('Messages_Controller', 'message'))
		{
			$actions->add_conditional_action()
					->condition('is_message_automatical_config')
					->icon_action('settings_auto')
					->url('messages_auto_settings/show')
					->label('Setup automatical activation');
			
			$actions->add_action()
					->icon_action('edit')
					->url('messages/edit');
		}
		
		if ($this->acl_check_delete('Messages_Controller', 'message'))
		{
			$actions->add_conditional_action()
					->condition('is_message_type_of_user')
					->icon_action('delete')
					->url('messages/delete')
					->class('delete_link');
		}
		
		$grid->datasource($messages);

		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = __('Messages');
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	}


	/**
	 * Adds new user message.
	 */
	public function add()
	{
		// access rights 
		if (!$this->acl_check_new('Messages_Controller', 'message'))
			Controller::error(ACCESS);
		
		$form = new Forge('messages/add');
		
		$form->group('Basic information');
		
		$form->input('name')
				->label('Message name')
				->rules('required|length[3,200]')
				->style('width: 600px');
		
		$form->dropdown('self_cancel')
				->label('User-cancelable')
				->options(Message_Model::get_self_cancel_messages())
				->selected(Message_Model::SELF_CANCEL_MEMBER)
				->style('width: 600px');
		
		$form->dropdown('ignore_whitelist')
				->options(arr::rbool())
				->selected(0);
		
		if (module::e('redirection'))
		{
			$form->html_textarea('text')
					->label(__('Content of the message for redirection').':&nbsp;'.
							help::hint('content_of_message'))
					->rows(5)
					->cols(100);
		}
	
		if (module::e('email'))
		{
			$form->html_textarea('email_text')
				->label(__('Content of the message for E-mail').':&nbsp;'.
						help::hint('content_of_message'))
				->rows(5)
				->cols(100);
		}

		if (module::e('sms'))
		{
			$form->textarea('sms_text')
				->label(__('Content of the message for SMS').':&nbsp;'.
						help::hint('content_of_message'))
				->rules('length[1,760]')
				->style('width: 100%; max-width: 633px; height: 150px');
		}
		
		$form->submit('Add');
		
		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);
			
			$message = new Message_Model();
			
			try
			{
				$message->transaction_start();
				
				$message->name = htmlspecialchars($form_data['name']);
				$message->type = Message_Model::USER_MESSAGE;
				$message->self_cancel = htmlspecialchars($form_data['self_cancel']);
				$message->ignore_whitelist = htmlspecialchars($form_data['ignore_whitelist']);
							
				// redir text
				if (module::e('redirection') &&
					Message_Model::has_redirection_content($message->type))
				{
					$message->text = $form_data['text'];
				}
			
				// email text
				if (module::e('email') &&
					Message_Model::has_email_content($message->type))
				{
					$email_text = trim($form_data['email_text']);
					$message->email_text = empty($email_text) ? NULL : $email_text;
				}
				
				// sms text
				if (module::e('sms') &&
					Message_Model::has_sms_content($message->type))
				{
					$sms_text = trim(text::cs_utf2ascii($form_data['sms_text']));
					$message->sms_text = empty($sms_text) ? NULL : $sms_text;
				}
				
				$message->save_throwable();
				
				$message->transaction_commit();
				status::success('Message has been successfully added.');
			}
			catch (Exception $e)
			{
				$message->transaction_rollback();
				status::error('Error - cannot add message.', $e);
				Log::add_exception($e);
			}
			
			$this->redirect('messages/show_all');
		}
		else
		{
			$headline = __('Add notification message');
			
			$breadcrumbs = breadcrumbs::add()
					->link('messages/show_all', 'Messages',
							$this->acl_check_view('Messages_Controller', 'message')
					)->disable_translation()
					->text($headline);
			
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	} // end of add
	
	/**
	 * Shows notification message details
	 * 
	 * @param integer $message_id
	 */
	public function show($message_id = NULL)
	{
		if (!$this->acl_check_view('Messages_Controller', 'message'))
			Controller::error(ACCESS);
		
		if (!isset($message_id))
			Controller::warning(PARAMETER);
		
		$message = new Message_Model($message_id);
		
		// record doesn't exist
		if (!$message->id)
			Controller::error(RECORD);
		
		if (!Settings::get('finance_enabled') &&
			Message_Model::is_finance_message($message->type))
		{
			Controller::error(ACCESS);
		}
		
		$headline = __('Show notification message');
			
		$breadcrumbs = breadcrumbs::add()
			->link('messages/show_all', 'Messages',
					$this->acl_check_view(
							'Messages_Controller', 'message'
					)
			);

		if ($message->type > 0)
		{
			$breadcrumbs->text($message->name);
		}
		else
		{
			$breadcrumbs
				->disable_translation()
				->text($message->name . ' (' . $message->id . ')');
		}
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('messages/show');
		$view->content->headline = $headline;
		$view->content->message = $message;
		$view->render(TRUE);
	}

	/**
	 * Edits message parameters.
	 * 
	 * @author Jiri Svitak
	 * @param integer $message_id
	 */
	public function edit($message_id = null)
	{
		if (!$this->acl_check_edit('Messages_Controller', 'message'))
			Controller::error(ACCESS);
		
		if (!isset($message_id))
			Controller::warning(PARAMETER);
		
		$message = new Message_Model($message_id);
		
		// record doesn't exist
		if (!$message->id)
			Controller::error(RECORD);

		if (!Settings::get('finance_enabled') &&
			Message_Model::is_finance_message($message->type))
		{
			Controller::error(ACCESS);
		}
		
		$form = new Forge('messages/edit/'.$message_id);
		
		$form->group('Basic information');
		
		if ($message->type == Message_Model::USER_MESSAGE)
		{
			$form->input('name')
					->label('Message name')
					->rules('required|length[3,200]')
					->value($message->name)
					->style('width: 600px');
		}
		
		if (Message_Model::is_self_cancable($message->type))
		{
			$form->dropdown('self_cancel')
					->label(__('User-cancelable').':&nbsp;'.help::hint('self_cancel'))
					->options(Message_Model::get_self_cancel_messages())
					->selected($message->self_cancel)
					->style('width: 600px');
		}
		
		if (Message_Model::is_white_list_ignorable($message->type) &&
			Message_Model::USER_MESSAGE == $message->type)
		{
			$form->dropdown('ignore_whitelist')
					->label(__('Ignore whitelist').':&nbsp;'.help::hint('ignore_whitelist'))
					->options(arr::rbool())
					->selected($message->ignore_whitelist);
		}
		
		if (module::e('redirection') &&
			Message_Model::has_redirection_content($message->type))
		{
			$form->html_textarea('text')
					->label(__('Content of the message for redirection').':&nbsp;'.
							help::hint('content_of_message'))
					->rows(5)
					->cols(100)
					->value($message->text);
		}
		
		if (module::e('email') &&
			Message_Model::has_email_content($message->type))
		{
			$form->html_textarea('email_text')
				->label(__('Content of the message for E-mail').':&nbsp;'.
						help::hint('content_of_message'))
				->rows(5)
				->cols(100)
				->value($message->email_text);
		}
		
		if (module::e('sms') &&
			Message_Model::has_sms_content($message->type))
		{
			$form->textarea('sms_text')
				->label(__('Content of the message for SMS').':&nbsp;'.
						help::hint('content_of_message'))
				->rules('length[1,760]')
				->style('width: 100%; max-width: 633px; height: 150px')
				->value($message->sms_text);
		}
		
		$form->submit('Edit');
		
		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);
			
			if ($message->type == 0)
			{
				$message->name = $form_data['name'];
			}
			
			if (Message_Model::is_self_cancable($message->type))
			{
				$message->self_cancel = htmlspecialchars($form_data['self_cancel']);
			}
			
			if (Message_Model::is_white_list_ignorable($message->type) &&
				Message_Model::USER_MESSAGE == $message->type)
			{
				$message->ignore_whitelist = htmlspecialchars($form_data['ignore_whitelist']);
			}
			
			if (module::e('redirection') &&
				Message_Model::has_redirection_content($message->type))
			{
				$message->text = $form_data['text'];
			}
			
			if (module::e('email') &&
				Message_Model::has_email_content($message->type))
			{
				// email text
				$email_text = trim($form_data['email_text']);
				// set var
				$message->email_text = empty($email_text) ? NULL : $email_text;
			}
			
			if (module::e('sms') &&
				Message_Model::has_sms_content($message->type))
			{
				// sms text
				$sms_text = trim(text::cs_utf2ascii($form_data['sms_text']));
				// set var
				$message->sms_text = empty($sms_text) ? NULL : $sms_text;
			}
			
			unset($form_data);
			
			// saving message
			if ($message->save())
			{
				status::success('Message has been successfully updated.');
			}
			else
			{
				status::error('Error - cannot update message.');
			}
			
			url::redirect('messages/show_all');
		}
		else
		{
			$headline = __('Edit notification message');
			
			$breadcrumbs = breadcrumbs::add()
				->link('messages/show_all', 'Messages',
						$this->acl_check_view(
								'Messages_Controller', 'message'
						)
				);
			
			if ($message->type > 0)
			{
				$breadcrumbs->text($message->name);
			}
			else
			{
				$breadcrumbs
					->disable_translation()
					->text($message->name . ' (' . $message->id . ')');
			}
			
			$breadcrumbs->text($headline);
			
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
	 * Updates notifications for given message.
	 * Special notification types are updated only to coresponding members.
	 * 
	 * @author Jiri Svitak
	 * @param integer $message_id
	 */
	public function activate($message_id = NULL)
	{
		if (!$this->acl_check_new('Messages_Controller', 'activate'))
		{
			Controller::error(ACCESS);
		}
		
		// param check
		if (!$message_id || !is_numeric($message_id))
		{
			Controller::warning(PARAMETER);
		}
		// preparation
		$message = new Message_Model($message_id);
		
		if (!$message || !$message->id ||
			!Message_Model::can_be_activate_directly($message->type))
		{
			Controller::error(RECORD);
		}
		
		if (!Settings::get('finance_enabled') &&
			Message_Model::is_finance_message($message->type))
		{
			Controller::error(ACCESS);
		}
		
		$this->message_id = $message->id;
		
		$member_model = new Member_Model();
		
		// shows beetween page only for interrupt payment notice and debtor
		if ($message->type == Message_Model::PAYMENT_NOTICE_MESSAGE ||
			$message->type == Message_Model::DEBTOR_MESSAGE ||
			$message->type == Message_Model::BIG_DEBTOR_MESSAGE)
		{
			if (!isset($_POST) || !isset($_POST['ids']))
			{
				$members = $member_model->get_members_to_messages($message->type);

				$grid = new Grid('notifications/cloud', '', array
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

				if ($message->type == Message_Model::USER_MESSAGE)
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

				if (module::e('redirection') &&
					Message_Model::has_redirection_content($message->type))
				{
					$grid->form_field('redirection')
							->type('dropdown')
							->options(notification::redirection_form_array())
							->callback(
								'callback::notification_form_field', $message
							);
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
						'comment', "<b>".__('Comment').":</b>"
					) . form::textarea('comment', '', 'style="margin-left: 30px"')."<br /><br />"
				);
				
				$grid->form_submit_value = __('Activate');

				$grid->datasource($members);

				$headline = __('Activate message for all members');
				
				$breadcrumbs = breadcrumbs::add()
					->link('messages/show_all', 'Messages',
							$this->acl_check_view(
									'Messages_Controller', 'message'
							)
					);
				
				if ($message->type != Message_Model::USER_MESSAGE)
				{	
					$breadcrumbs->text($message->name);
				}
				else
				{
					$breadcrumbs->disable_translation()
						->text($message->name . ' (' . $message->id . ')');
				}
				
				$breadcrumbs->text($headline);

				$view = new View('main');
				$view->breadcrumbs = $breadcrumbs->html();
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
						$stats, module::e('redirection'), 
						module::e('email'), 
						module::e('sms'),
						module::e('redirection')
				);
				// log action
				if (count($info_messages))
				{
					$un = ORM::factory('user', $user_id)->get_full_name();
					$m = __('User "%s" has activated notification message "%s"',
							array($un, __($message->name)));
					status::success(implode('<br />', $info_messages), FALSE);
					Log_queue_Model::info($m, implode("\n", $info_messages));
				}
				// redirect
				url::redirect('messages/show_all');
			}
		}
		// user message will be activate right now
		else
		{
			$dropdown_options = array
			(
				Notifications_Controller::ACTIVATE	=> __('Activate'),
				Notifications_Controller::KEEP		=> __('Without change')
			);

			$form = new Forge(url::base(TRUE) . url::current(TRUE));
			
			if (module::e('redirection'))
			{
				$form->dropdown('redirection')
						->options($dropdown_options)
						->selected(Notifications_Controller::KEEP);
			}
			
			if ($message->type == Message_Model::USER_MESSAGE)
			{
				if (module::e('email'))
				{
					$form->dropdown('email')
							->label('E-mail')
							->options($dropdown_options)
							->selected(Notifications_Controller::KEEP)
							->callback(array($this, 'valid_email_or_sms'));
				}

				if (module::e('sms'))
				{
					$form->dropdown('sms')
							->label('SMS')
							->options($dropdown_options)
							->selected(Notifications_Controller::KEEP)
							->callback(array($this, 'valid_email_or_sms'));
				}
			}

			$form->submit('Send');

			if ($form->validate())
			{
				$form_data = $form->as_array();

				$user_id = $this->session->get('user_id');
				$comment = @$form_data['comment'];
				
				$redirection = module::e('redirection') &&
					($form_data['redirection'] == Notifications_Controller::ACTIVATE);
				
				$email = module::e('email') &&
					($form_data['email'] == Notifications_Controller::ACTIVATE);
				
				$sms = module::e('sms') &&
					($form_data['sms'] == Notifications_Controller::ACTIVATE);

				try
				{
					// get members
					$members = $member_model->get_members_to_messages(
							Message_Model::USER_MESSAGE
					);
					// notify
					$stats = Notifications_Controller::notify(
							$message, $members, $user_id, $comment,
							$redirection, $email, $sms, $redirection
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
						$m = __('User "%s" has activated notification message "%s"',
								array($un, __($message->name)));
						status::success(implode('<br />', $info_messages), FALSE);
						Log_queue_Model::info($m, implode("\n", $info_messages));
					}
					// redirect
					url::redirect('messages/show_all');
				}
				catch (Exception $e)
				{
					$em = __('Error - cant set redirection.') . '<br>' . $e->getMessage();
					status::error($em, $e, FALSE);
					url::redirect('messages/show_all');
				}

			}

			$headline = __('Activate message for all members');
			
			$breadcrumbs = breadcrumbs::add()
				->link('messages/show_all', 'Messages',
						$this->acl_check_view(
								'Messages_Controller', 'message'
						)
				);

			if ($message->type > 0)
			{
				$breadcrumbs->text($message->name);
			}
			else
			{
				$breadcrumbs->disable_translation()
					->text($message->name . ' (' . $message->id . ')');
			}
			
			$breadcrumbs->text($headline);

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form;
			$view->render(TRUE);
		}
	}

	/**
	 * Deactivates all redirections for given message.
	 *
	 * @author Jiri Svitak
	 * @param integer $message_id
	 */
	public function deactivate($message_id = NULL)
	{
		if (!$this->acl_check_edit('Messages_Controller', 'deactivate'))
		{
			Controller::error(ACCESS);
		}
		
		// param check
		if (!$message_id || !is_numeric($message_id))
		{
			Controller::warning(PARAMETER);
		}
		// preparation
		$message = new Message_Model($message_id);
		
		if (!$message || !$message->id)
		{
			Controller::error(RECORD);
		}
		
		$message_model = new Message_Model();
		$message_model->deactivate_message($message_id);
				
		// log action
		$un = ORM::factory('user', $this->user_id)->get_full_name();
		$m = __('User "%s" has deactivated notification message "%s"',
				array($un, __($message->name)));
		Log_queue_Model::info($m);
		
		status::success('Activated redirections of this message have been successfuly deactivated.');
		url::redirect('messages/show_all');
	}
	
	/**
	 * Deletes a user message with given id.
	 *  
	 * @param type $message_id 
	 */
	public function delete($message_id = NULL)
	{		
		if (!$this->acl_check_delete('Messages_Controller', 'message'))
		{
			Controller::error(ACCESS);
		}
		
		if (!$message_id || !is_numeric($message_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$message = new Message_Model($message_id);
		
		if (!$message || !$message->id)
		{
			Controller::error(RECORD);
		}
		
		if ($message->type != Message_Model::USER_MESSAGE)
		{
			Controller::error(ACCESS);
		}
		
		$message->delete();
		
		status::success('User message have been successfully deleted.');
		url::redirect('messages/show_all');
	}

	/**
	 * Callback function to validate e-mail or sms notification
	 *
	 * @author Michal Kliment
	 * @param type $input 
	 */
	public function valid_email_or_sms($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$email_or_sms = $input->value;
		
		if ($email_or_sms == Notifications_Controller::ACTIVATE)
		{
			$message = new Message_Model($this->message_id);
			
			if ($message->type != Message_Model::BIG_DEBTOR_MESSAGE &&
				$message->type != Message_Model::DEBTOR_MESSAGE &&
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
