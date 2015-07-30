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

		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		$allowed_order_type = array
		(
			'id', 'from', 'to', 'extension', 'opening_balance', 'closing_balance'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		// model
		$message_model = new Message_Model();
		$total_messages = $message_model->count_all_messages();
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_messages)
			$sql_offset = 0;
		
		$messages = $message_model->get_all_messages(
				$sql_offset, $limit_results, $order_by, $order_by_direction
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
				'total_items'				=> $total_messages,
				'items_per_page'			=> $limit_results,
				'style'						=> 'classic',
				'order_by'					=> $order_by,
				'order_by_direction'		=> $order_by_direction,
				'limit_results'				=> $limit_results,
				'url_array_ofset'			=> 1,
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
				->label(__('sc').' '.help::hint('self_cancel'))
				->callback('callback::message_self_cancel_field');
		
		$grid->callback_field('ignore_whitelist')
				->label(__('iw').' '.help::hint('ignore_whitelist'))
				->callback('callback::boolean');
		
		$grid->callback_field('id')
					->label('Preview')
					->callback('callback::message_preview_field');
		
		if ($this->acl_check_edit('Messages_Controller', 'message'))
		{
			$grid->callback_field('id')
					->label('Activate')
					->callback('callback::message_activate_field');
		}

		if ($this->acl_check_edit('Messages_Controller', 'message'))
		{
			$grid->callback_field('id')
					->label('Deactivate')
					->callback('callback::message_deactivate_field');
		}
		
		if ($this->acl_check_edit('Messages_Controller', 'message'))
		{
			$grid->action_field('id')
					->label('Edit')
					->url('messages/edit')
					->action('Edit');
		}
		
		if ($this->acl_check_delete('Messages_Controller', 'message'))
		{
			$grid->callback_field('id')
					->label('Delete')
					->callback('callback::message_delete_field');
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
		
		// self cancel
		$self_cancel[Message_Model::SELF_CANCEL_DISABLED] = __('Disabled');
		$self_cancel[Message_Model::SELF_CANCEL_MEMBER] = 
				__('Possibility of canceling redirection to all IP addresses of member');
		$self_cancel[Message_Model::SELF_CANCEL_IP] = 
				__('Possibility of canceling redirection to only current IP address');
		
		$form = new Forge('messages/add');
		
		$form->group('Basic information');
		
		$form->input('name')
				->label('Message name')
				->rules('required|length[3,200]')
				->style('width: 600px');
		
		$form->dropdown('self_cancel')
				->label('User-cancelable')
				->options($self_cancel)
				->selected(Message_Model::SELF_CANCEL_MEMBER)
				->style('width: 600px');
		
		$form->dropdown('ignore_whitelist')
				->options(arr::rbool())
				->selected(0);
		
		$form->html_textarea('text')
				->label('Content of the message')
				->rows(5)
				->cols(100);
	
		$form->html_textarea('email_text')
			->label(__('Content of the message for E-mail').':&nbsp;'.
					help::hint('content_of_message'))
			->rows(5)
			->cols(100);

		$form->textarea('sms_text')
			->label(__('Content of the message for SMS').':&nbsp;'.
					help::hint('content_of_message'))
			->rules('length[1,760]')
			->style('width: 100%; max-width: 633px; height: 150px');
		
		$form->submit('Add');
		
		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);
			$message = new Message_Model();
			$message->name = htmlspecialchars($form_data['name']);
			$message->type = Message_Model::USER_MESSAGE;
			$message->self_cancel = htmlspecialchars($form_data['self_cancel']);
			$message->ignore_whitelist = htmlspecialchars($form_data['ignore_whitelist']);
			
			// email text
			$email_text = trim($form_data['email_text']);
			$email_text = empty($email_text) ? NULL : $email_text;
			// sms text
			$sms_text = trim(text::cs_utf2ascii(strip_tags($form_data['sms_text'])));
			$sms_text = empty($sms_text) ? NULL : $sms_text;
			// set vars
			$message->email_text = $email_text;
			$message->sms_text = $sms_text;
			
			$message->text = $form_data['text'];
			unset($form_data);
			
			if ($message->save())
			{
				status::success('Message has been successfully added.');
			}
			else
			{
				status::error('Error - cannot add message.');
			}
			
			url::redirect('messages/show_all');
		}
		else
		{
			$headline = __('Add redirection message');
			
			$breadcrumbs = breadcrumbs::add()
					->link('messages/show_all', 'Messages',
							$this->acl_check_view(
									'Redirection_Controller', 'redirection'
							)
					)
					->disable_translation()
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
		
		// access rights 
		if (!$this->acl_check_edit('Messages_Controller', 'message'))
			Controller::error(ACCESS);
		
		// self cancel
		$self_cancel[Message_Model::SELF_CANCEL_DISABLED] = __('Disabled');
		$self_cancel[Message_Model::SELF_CANCEL_MEMBER] = 
				__('Possibility of canceling redirection to all IP addresses of member');
		$self_cancel[Message_Model::SELF_CANCEL_IP] = 
				__('Possibility of canceling redirection to only current IP address');
		
		$form = new Forge('messages/edit/'.$message_id);
		
		$form->group('Basic information');
		
		if ($message->type == 0)
		{
			$form->input('name')
					->label('Message name')
					->rules('required|length[3,200]')
					->value($message->name)
					->style('width: 600px');
		}
		
		if ($message->type == Message_Model::USER_MESSAGE ||
			$message->type == Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE ||
			$message->type == Message_Model::DEBTOR_MESSAGE ||
			$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE ||
			$message->type == Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE)
		{
			$form->dropdown('self_cancel')
					->label(__('User-cancelable').
							':&nbsp;'.help::hint('self_cancel'))
					->options($self_cancel)
					->selected($message->self_cancel)
					->style('width: 600px');
			
			$form->dropdown('ignore_whitelist')
					->label(__('Ignore whitelist').
							':&nbsp;'.help::hint('ignore_whitelist'))
					->options(arr::rbool())
					->selected($message->ignore_whitelist);
		}
		
		$form->html_textarea('text')
				->label(__('Content of the message for redirection').':&nbsp;'.
						help::hint('content_of_message'))
				->rows(5)
				->cols(100)
				->value($message->text);
		
		if ($message->type == Message_Model::USER_MESSAGE || 
			$message->type == Message_Model::DEBTOR_MESSAGE ||
			$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE)
		{
			$form->html_textarea('email_text')
				->label(__('Content of the message for E-mail').':&nbsp;'.
						help::hint('content_of_message'))
				->rows(5)
				->cols(100)
				->value($message->email_text);
			
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
			
			if ($message->type == Message_Model::USER_MESSAGE ||
				$message->type == Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE ||
				$message->type == Message_Model::DEBTOR_MESSAGE ||
				$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE ||
				$message->type == Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE)
			{
				$message->self_cancel = htmlspecialchars($form_data['self_cancel']);
				$message->ignore_whitelist = htmlspecialchars($form_data['ignore_whitelist']);
			}
			$message->text = $form_data['text'];
			
			if ($message->type == Message_Model::USER_MESSAGE || 
				$message->type == Message_Model::DEBTOR_MESSAGE ||
				$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE)
			{
				// email text
				$email_text = trim($form_data['email_text']);
				$email_text = empty($email_text) ? NULL : $email_text;
				// sms text
				$sms_text = trim(text::cs_utf2ascii(strip_tags($form_data['sms_text'])));
				$sms_text = empty($sms_text) ? NULL : $sms_text;
				// set vars
				$message->email_text = $email_text;
				$message->sms_text = $sms_text;
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
			$headline = __('Edit redirection message');
			
			if ($message->type > 0)
			{
				$breadcrumbs = breadcrumbs::add()
					->link('messages/show_all', 'Messages',
							$this->acl_check_view(
									'Redirection_Controller', 'redirection'
							)
					)
					->text($message->name)
					->text($headline);
			}
			else
			{
				$breadcrumbs = breadcrumbs::add()
					->link('messages/show_all', 'Messages',
							$this->acl_check_view(
									'Redirection_Controller', 'redirection'
							)
					)
					->disable_translation()
					->text($message->name . ' (' . $message->id . ')')
					->text($headline);
			}
			
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
	 * Updates redirections for given message.
	 * Special redirection types are updated only to coresponding members.
	 * 
	 * @author Jiri Svitak
	 * @param integer $message_id
	 */
	public function activate($message_id = NULL)
	{
		if (!$this->acl_check_new('Messages_Controller', 'member'))
			Controller::error(ACCESS);
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
		
		$this->message_id = $message->id;
		
		// shows beetween page only for interrupt membership, payment notice and debtor
		if ($message->type == Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE ||
			$message->type == Message_Model::DEBTOR_MESSAGE ||
			$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE)
		{
			if (!isset($_POST) || !isset($_POST["ids"]))
			{
				switch ($message->type)
				{
					case Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE:
						$order_by = 'whitelisted DESC, allowed ASC';
						break;

					default:
						$order_by = 'whitelisted DESC, id ASC';
						break;
				}

				$member_model = new Member_Model();

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

				if ($message->type == Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE ||
					$message->type == Message_Model::DEBTOR_MESSAGE ||
					$message->type == Message_Model::PAYMENT_NOTICE_MESSAGE ||
					$message->type == Message_Model::USER_MESSAGE)
				{
					$grid->callback_field('interrupt')
						->label('Membership interrupt')
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
							Notifications_Controller::ACTIVATE => __('Activate'),
							Notifications_Controller::KEEP => __('Without change'),
							Notifications_Controller::DEACTIVATE => __('Deactivate')
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
							Notifications_Controller::ACTIVATE => __('Activate'),
							Notifications_Controller::KEEP => __('Without change')
						))
						->callback('callback::notification_form_field', $message->type, $message->ignore_whitelist);

					$grid->form_field('sms')
						->label('SMS')
						->type('dropdown')
						->options(array
						(
							Notifications_Controller::ACTIVATE => __('Activate'),
							Notifications_Controller::KEEP => __('Without change')
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

				$grid->datasource($members);

				$headline = __('Activate message for all members');

				if ($message->type > 0)
				{
					$breadcrumbs = breadcrumbs::add()
						->link('messages/show_all', 'Messages',
								$this->acl_check_view(
										'Redirection_Controller', 'redirection'
								)
						)
						->text($message->name)
						->text($headline);
				}
				else
				{
					$breadcrumbs = breadcrumbs::add()
						->link('messages/show_all', 'Messages',
								$this->acl_check_view(
										'Redirection_Controller', 'redirection'
								)
						)
						->disable_translation()
						->text($message->name . ' (' . $message->id . ')')
						->text($headline);
				}

				$view = new View('main');
				$view->breadcrumbs = $breadcrumbs->html();
				$view->title = $headline;
				$view->content = new View('show_all');
				$view->content->headline = $headline;
				$view->content->table = $grid;
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
					if ($redirection == Notifications_Controller::KEEP)
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
					if ($redirection == Notifications_Controller::ACTIVATE)
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
					if ($email == Notifications_Controller::KEEP)
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
					if ($sms == Notifications_Controller::KEEP)
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
				url::redirect('messages/show_all');
			}
		}
		// other messages will be activate right now
		else
		{
			$dropdown_options = array
			(
				Notifications_Controller::ACTIVATE	=> __('Activate'),
				Notifications_Controller::KEEP		=> __('Without change')
			);

			$form = new Forge(url::base(TRUE) . url::current(TRUE));

			$form->dropdown('redirection')
					->options($dropdown_options)
					->selected(Notifications_Controller::KEEP);
			
			if ($message->type == Message_Model::USER_MESSAGE)
			{
				$form->dropdown('email')
						->label('E-mail')
						->options($dropdown_options)
						->selected(Notifications_Controller::KEEP)
						->callback(array($this, 'valid_email_or_sms'));

				$form->dropdown('sms')
						->label('SMS')
						->options($dropdown_options)
						->selected(Notifications_Controller::KEEP)
						->callback(array($this, 'valid_email_or_sms'));
			}

			$form->submit('Send');

			if ($form->validate())
			{

				$form_data = $form->as_array();

				$user_id = $this->session->get('user_id');
				$ip_count = 0;

				try
				{
					// choose which message to update
					switch($message->type)
					{
						case Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE:

							if ($form_data['redirection'] == Notifications_Controller::ACTIVATE)
							{
								$ip_count = $message->activate_unallowed_connecting_place_message($user_id);
							}

							break;

						case Message_Model::USER_MESSAGE:

							$counts = $message->activate_user_message(
									$message_id,
									$user_id,
									$form_data['redirection'],
									$form_data['email'],
									$form_data['sms']
							);

							$ip_count = $counts['ip_count'];
							$email_count = $counts['email_count'];
							$sms_count = $counts['sms_count'];

						break;

						default:
							Controller::warning(PARAMETER);
					}

					$m = 'Redirection "%s" for %d IP addresses have been activated.';

					$info_message = __($m, array
					(
						0 => __($message->name),
						1 => $ip_count
					));

					if (isset($email_count))
					{
						$m = 'E-mail "%s" has been sent for %d e-mail addresses.';

						$info_message .= '<br />'.__($m, array
						(
							0 => __($message->name),
							1 => $email_count
						));
					}

					if (isset($sms_count))
					{
						$m = 'SMS message "%s" has been sent for %d phone numbers.';

						$info_message .= '<br />'.__($m, array
						(
							0 => __($message->name),
							1 => $sms_count
						));
					}

					status::success($info_message, FALSE);
					url::redirect('messages/show_all');
				}
				catch (Exception $e)
				{
					$em = __('Error - cant set redirection.') . '<br>' . $e->getMessage();
					status::error($em, FALSE);
					url::redirect('messages/show_all');
				}

			}

			$headline = __('Activate message for all members');

			if ($message->type > 0)
			{
				$breadcrumbs = breadcrumbs::add()
					->link('messages/show_all', 'Messages',
							$this->acl_check_view(
									'Redirection_Controller', 'redirection'
							)
					)
					->text($message->name)
					->text($headline);
			}
			else
			{
				$breadcrumbs = breadcrumbs::add()
					->link('messages/show_all', 'Messages',
							$this->acl_check_view(
									'Redirection_Controller', 'redirection'
							)
					)
					->disable_translation()
					->text($message->name . ' (' . $message->id . ')')
					->text($headline);
			}

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
		if (!$this->acl_check_edit('Messages_Controller', 'message'))
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
		if (!$this->acl_check_edit('Messages_Controller', 'message'))
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
