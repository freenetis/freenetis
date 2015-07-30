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
 * Controller for mail messages.
 * Mail messages are inner system for communication between users and system.
 * 
 * @author	Michal Kliment
 * @package Controller
 */
class Mail_Controller extends Mail_messages_Controller
{
	/**
	 * Mail sections, for rendering menu
	 *
	 * @var array[string]
	 */
	public static $sections = array
	(
		'mail/inbox'			=> 'Mail inbox',
		'mail/sent'				=> 'Mail sent',
		'mail/write_message'	=> 'Write new message'
	);

	/**
	 * index redirects to inbox
	 */
	public function index()
	{
		url::redirect('mail/inbox');
	}

	/**
	 * Function to get all inbox messages of user
	 * 
	 * @author Michal Kliment
	 * @param number $limit_results
	 * @param string $page_word
	 * @param number $page
	 */
	public function inbox($limit_results = 10, $page_word = null, $page = 1)
	{	
		$mail_message_model = new Mail_message_Model();
		
		$total_messages = $mail_message_model->count_all_inbox_messages_by_user_id(
				$this->user_id
		);

		if (is_numeric($this->input->post('record_per_page')))
		{
			$limit_results = (int) $this->input->post('record_per_page');
		}

		if (($sql_offset = ($page - 1) * $limit_results) > $total_messages)
		{
			$sql_offset = 0;
		}

		// finds all user's inbox messages
		$messages = $mail_message_model->get_all_inbox_messages_by_user_id(
				$this->user_id, $sql_offset, (int) $limit_results
		);

		// finds ids of all messages - for javascript mark-all function
		$arr_ids = array();
		
		foreach ($messages as $message)
		{
			$arr_ids[] = $message->id;
		}
		
		// mail redirection
		if (Settings::get('email_enabled') && $this->acl_check_edit(
				'Users_Controller', 'additional_contacts', $this->member_id
			))
		{
			$uc_model = new Users_contacts_Model();
			$email_redirections = $uc_model->get_redirected_email_boxes_of($this->user_id);
			$contacts_link = html::anchor('/contacts/show_by_user/' . $this->user_id, __('here'));

			if (count($email_redirections))
			{
				$emails = '';
				foreach ($email_redirections as $e)
				{
					$emails .= $e->value . ', ';
				}

				status::minfo('Your inner mail is redirected to your following e-mail '
						. 'addresses %s you can disable this %s.', TRUE,
						array(substr($emails, 0, -2), $contacts_link));
			}
			else
			{
				status::minfo('You can redirect your inner mail to your e-mail box '
						. 'by editing your e-mail %s.', TRUE, array($contacts_link));
			}
		}

		// create grid
		$grid = new Grid('mail/inbox', '', array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'total_items'				=>  $total_messages,
			'current'					=> $limit_results,
			'base_url'					=> Config::get('lang') . '/mail/inbox/'
										. $limit_results,
			'uri_segment'				=> 'page',
			'items_per_page' 			=> $limit_results,
			'style'						=> 'classic',
			'limit_results'				=> $limit_results,
		));
		
		$grid->add_new_button(
			'mail/mark_inbox_read', 'Mark all messages as read'
		);
		
		$grid->form_field('delete')
				->order(false)
				->type('checkbox');
		
		$grid->callback_field('from_id')
				->label(__('From'))
				->class('grid_mail_from')
				->order(false)
				->callback('Mail_Controller::from_field');
		
		$grid->callback_field('subject')
				->label(__('Subject'))
				->class('grid_mail_subject')
				->order(false)
				->callback('Mail_Controller::subject_field');
		
		$grid->callback_field('time')
				->label(__('Time'))
				->class('grid_mail_time')
				->order(false)
				->callback('Mail_Controller::time_field');
		
		$grid->grouped_action_field()
				->add_action()
				->icon_action('delete')
				->url('mail/delete_message')
				->class('delete_link');

		// adds extra buttons for messages administration
		$grid->form_extra_buttons = array
		(
			form::checkbox(
					'mark_all','on', False,
					'onclick="mark_all_checkboxs(\'delete\', new Array(\''.
					implode('\',\'',$arr_ids).'\'))"'
			).form::label(
					'mark_all', __('Mark all'),
					'class="mark_all_label"'
			),
			form::dropdown('operation', array
			(
				'delete'	=> __('Delete selected messages'),
			    'read'		=> __('Mark selected messages as read'),
			    'unread'	=> __('Mark selected messages as unread')
			))
		);
		
		$grid->form_submit_value = __('Perform');
		$grid->datasource($messages);

		// form is post
		if ($_POST && count($_POST) && isset($_POST['operation']))
		{
			$operation = $_POST['operation'];
			$user_id = $this->session->get('user_id');
			$mail_message_model = new Mail_message_Model();
			// for each checked messages
			if (isset($_POST['delete']) && is_array($_POST['delete']))
			{
				foreach ($_POST['delete'] as $message_id => $true)
				{
					$message = $mail_message_model->where('id', $message_id)->find();

					// message doesn't exist
					if (!$message->id)
						continue;

					// deletes message
					if ($operation == 'delete')
					{
						// check if message is really from user inbox
						if ($message->to_id == $user_id)
						{
							if ($message->from_deleted || $message->from_id == $user_id)
								$message->delete();
							else
							{
								$message->to_deleted = 1;
								$message->readed = 1;
								$message->save();
							}
						}
					}
					// marks as read
					else if ($operation == 'read')
					{
						$message->readed = 1;
						$message->save();
					}
					// marks as unread
					else if ($operation == 'unread')
					{
						$message->readed = 0;
						$message->save();
					}
				}
			}
			url::redirect(url::base(TRUE).url::current(TRUE));
		}

		$view = new View('main');
		$view->title = __('Mail inbox');
		$view->content = new View('mail/main');
		$view->content->title = __('Mail inbox');
		$view->content->content = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function to get all inbox messages of user
	 * 
	 * @author Michal Kliment
	 * @param number $limit_results
	 * @param string $page_word
	 * @param number $page
	 */
	public function sent($limit_results = 10, $page_word = null, $page = 1)
	{
		$mail_message_model = new Mail_message_Model();
		
		$total_messages = $mail_message_model->count_all_sent_messages_by_user_id(
				$this->session->get('user_id')
		);

		if (is_numeric($this->input->post('record_per_page')))
		{
			$limit_results = (int) $this->input->post('record_per_page');
		}

		if (($sql_offset = ($page - 1) * $limit_results) > $total_messages)
		{
			$sql_offset = 0;
		}

		// finds all user's sent messages
		$messages = $mail_message_model->get_all_sent_messages_by_user_id(
				$this->session->get('user_id'), $sql_offset, (int) $limit_results
		);

		// finds ids of all messages - for javascript mark-all function
		$arr_ids = array();
		
		foreach ($messages as $message)
		{
			$arr_ids[] = $message->id;
		}

		// create grid
		$grid = new Grid('mail/sent', '', array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'total_items'				=> $total_messages,
			'current'					=> $limit_results,
			'base_url'					=> Config::get('lang').'/mail/sent/'
										. $limit_results,
			'uri_segment'				=> 'page',
			'items_per_page' 			=> $limit_results,
			'style'						=> 'classic',
			'limit_results'				=> $limit_results,
		));

		$grid->form_field('delete')
				->order(false)
				->type('checkbox');
		
		$grid->callback_field('to_id')
				->label(__('To'))
				->class('grid_mail_to')
				->order(false)
				->callback('Mail_Controller::to_field');
		
		$grid->callback_field('subject')
				->class('grid_mail_subject')
				->order(false)
				->callback('Mail_Controller::subject_field');
		
		$grid->callback_field('time')
				->class('grid_mail_time')
				->order(false)
				->callback('Mail_Controller::time_field');
		
		$grid->grouped_action_field()
				->add_action()
				->icon_action('delete')
				->url('mail/delete_message')
				->class('delete_link');

		// adds extra buttons for messages administration
		$grid->form_extra_buttons = array(
			form::checkbox(
					'mark_all','on', False,
					'onclick="mark_all_checkboxs(\'delete\', new Array(\''.
					implode('\',\'',$arr_ids).'\'))"'
			) . form::label(
					'mark_all', __('Mark all'),
					'class="mark_all_label"'
			),
			form::dropdown('operation', array
			(
			    'delete' => __('Delete selected messages')
			))
		);

		$grid->form_submit_value = __('Submit');
		$grid->datasource($messages);

		// form is post
		if ($_POST && count($_POST) && isset($_POST['operation']))
		{
			$operation = $_POST['operation'];
			$user_id = $this->session->get('user_id');
			$mail_message_model = new Mail_message_Model();
			// for each checked messages
			if (isset($_POST['delete']) && is_array($_POST['delete']))
			{
				foreach ($_POST['delete'] as $message_id => $true)
				{
					$message = $mail_message_model->where('id', $message_id)->find();

					// message doesn't exist
					if (!$message->id)
						continue;

					// deletes message
					if ($operation == 'delete')
					{
						// check if message is really from user inbox
						if ($message->from_id == $user_id)
						{
							if ($message->to_deleted || $message->to_id == $user_id)
								$message->delete();
							else
							{
								$message->from_deleted = 1;
								$message->save();
							}
						}
					}
				}
			}
			url::redirect(url::base(TRUE).url::current(TRUE));
		}

		$view = new View('main');
		$view->title = __('Mail sent');
		$view->content = new View('mail/main');
		$view->content->title = __('Mail sent');
		$view->content->content = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function to get message
	 * 
	 * @author Michal Kliment
	 * @param number $message_id
	 */
	public function show_message($message_id = NULL)
	{
		// bad parameter
		if (!$message_id || !is_numeric($message_id))
			Controller::warning(PARAMETER);

		$message = new Mail_message_Model($message_id);

		// message doen't exist
		if (!$message->id)
			Controller::error(RECORD);

		$user_id = $this->session->get('user_id');

		// access control
		if ($message->from_id != $user_id && $message->to_id != $user_id)
			Controller::error(ACCESS);

		$from_user = NULL;
		$to_user = NULL;
		$current = NULL;

		// message is not from user
		if ($message->from_id != $user_id)
			$from_user = new User_Model($message->from_id);
		else
		// message is un user's sent messages
			$current = 'mail/sent';

		// message is not to user
		if ($message->to_id != $user_id)
			$to_user = new User_Model($message->to_id);
		// message is un user's inbox messages
		else
		{
			$current = 'mail/inbox';
			// marks message as read
			$message->readed = 1;
			$message->save();
		}

		$view = new View('main');
		$view->title = __('Show mail message');
		$view->content = new View('mail/main');
		$view->content->current = $current;
		$view->content->title = __('Show mail message');
		$view->content->content = new View('mail/show');
		$view->content->content->message = $message;
		$view->content->content->from_user = $from_user;
		$view->content->content->to_user = $to_user;
		$view->render(TRUE);
	}

	/**
	 * Function to write message
	 * 
	 * @author Michal Kliment
	 * @param number $to_id
	 * @param number $origin_id
	 */
	public function write_message($to_id = NULL, $origin_id = NULL)
	{
		$to_value = '';
		$subject_value = '';
		$body_value = '';

		// this is reply
		if ($origin_id)
		{
			// bad parameter
			if (!is_numeric($origin_id))
				Controller::warning(PARAMETER);

			$origin = new Mail_message_Model($origin_id);

			// message doesn't exist
			if (!$origin->id || $origin->from_id == User_Model::ASSOCIATION)
					Controller::error(RECORD);

			// message is not from user
			if ($origin->from_id != $this->session->get('user_id'))
			{
			    $prev_user = $to_user = new User_Model($origin->from_id);
			}
			else
			{ // user will reply to recipient, not to himself :-)
			    $to_user = new User_Model($origin->to_id);
				$prev_user = $to_user = new User_Model($origin->from_id);
			}

			// record doesn't exist
			if (!$to_user->id)
				Controller::error(RECORD);

			$to_value = $to_user->login;
			$subject_value = 'Re: '.$origin->subject;
			$body_value = '<p></p><p>'.$prev_user->name.' '.$prev_user->surname.' '
					. __('wrote on').' '.date::pretty($origin->time).', '
					. __('at').' '.date::pretty_time($origin->time)
					. ':</p> <i>'.$origin->body.'</i>';
		}
		// this is message to user
		else if ($to_id)
		{
			// bad parameter
			if (!is_numeric($to_id))
				Controller::warning(PARAMETER);

			$to_user = new User_Model($to_id);

			// record doesn't exist
			if (!$to_user->id)
				Controller::error(RECORD);

			$to_value = $to_user->login;
		}

		$form = new Forge(url::base(TRUE).url::current(TRUE));

		$form->input('to')
				->class('mail_to_field autocomplete')
				->rules('required')
				->value($to_value)
				->callback(array($this, 'valid_to_field'))
				->help(help::hint('mail_to_field'));
		
		$form->input('subject')
				->class('mail_subject_field')
				->rules('required|length[1,150]')
				->value($subject_value);
		
		$form->html_textarea('body')
				->label(__('Text').':')
				->rules('required')
				->value($body_value);

		$form->submit('Send');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);

			$recipients = explode(',', trim(trim($form_data['to']), ','));
			
			$user_model = new User_Model();

			try
			{
				$user_model->transaction_start();
				
				// sends message fo each recipients
				foreach ($recipients as $recipient)
				{
					$user = $user_model->where('login',trim($recipient))->find();
					
					Mail_message_Model::create(
							$this->user_id, $user->id,
							htmlspecialchars($form_data['subject']),
							$form_data['body']
					);
				}
				
				$user_model->transaction_commit();
				status::success('Message has been successfully sent.');
				url::redirect('mail/sent');
			}
			catch (Exception $e)
			{
				$user_model->transaction_rollback();
				status::error('Message has not been sent.', $e);
				Log::add_exception($e);
			}
		}
		
		$view = new View('main');
		$view->title = __('Write new message');
		$view->content = new View('mail/main');
		$view->content->title = __('Write new message');
		$view->content->content = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Function to delete message
	 * 
	 * @author Michal Kliment
	 * @param number $message_id
	 */
	public function delete_message($message_id = NULL)
	{
		// bad parameter
		if (!$message_id)
			Controller::warning(PARAMETER);

		$message = new Mail_message_Model($message_id);

		// record does't exist
		if (!$message->id)
			Controller::error(RECORD);

		$user_id = $this->session->get('user_id');

		// access control
		if ($message->from_id != $user_id && $message->to_id != $user_id)
			Controller::error(ACCESS);

		// user is recipient
		if ($message->to_id == $user_id)
		{
			if ($message->from_deleted || $message->from_id == $user_id)
				$message->delete();
			else
			{
				$message->to_deleted = 1;
				$message->save();
			}
			status::success('Message has been successfully deleted.');
			url::redirect('mail/inbox');
		}
		// user is sender
		else if ($message->from_id == $user_id)
		{
			if ($message->to_deleted || $message->to_id == $user_id)
				$message->delete();
			else
			{
				$message->from_deleted = 1;
				$message->save();
			}
			status::success('Message has been successfully deleted.');
			url::redirect('mail/sent');
		}
	}
	
	/**
	 * Function to mark all messages as read
	 */
	public function mark_inbox_read()
	{
		$user_id = $this->session->get('user_id');
		$mail_message_model = new Mail_message_Model();
		
		$mail_message_model->mark_all_inbox_messages_as_read_by_user_id($user_id);
		
		$this->redirect('mail/inbox');
	}

	/* ********************* CALLBACK FUNCTION ********************************/

	/**
	 * Callback function to return sender as link, marks unread message
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	protected static function from_field($item, $name)
	{
		if (!$item->readed)	echo '<b>';

		$user_name = $item->user_name;
		
		if ($item->from_id == Member_Model::ASSOCIATION)
		{
			$user_name = __('System message');
		}
		
		// access conntrol
		if (Controller::instance()->acl_check_view(
				'Users_Controller', 'users', $item->member_id
			))
		{
			echo html::anchor('users/show/'.$item->from_id, $user_name, array
			(
				'title' => __('Show user')
			));
		}
		else
		{
			echo $user_name;
		}
		
		if (!$item->readed) echo '</b>';
	}

	/**
	 * Callback function to return recipient as link, marks unread message
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	protected static function to_field($item, $name)
	{
		if (!$item->readed) echo '<b>';

		// access conntrol
		if (Controller::instance()->acl_check_view(
				'Users_Controller', 'users', $item->member_id
			))
		{
			echo html::anchor('users/show/' . $item->to_id, $item->user_name, array
			(
				'title' => __('Show user')
			));
		}
		else
		{
			echo $item->user_name;
		}

		if (!$item->readed) echo '</b>';
	}

	/**
	 * Callback function to return subject as link, marks unread message
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	protected static function subject_field($item, $name)
	{
		$subject = $item->subject;

		if (mail_message::is_formated($subject))
		{
			$subject = mail_message::printf($subject);
		}

		if (!$item->readed) echo '<b>';
		{
			echo html::anchor('mail/show_message/'.$item->id, $subject, array
			(
				'title' => __('Read message')
			));
		}
		
		if (!$item->readed)
			echo '</b> ('.strtolower(__('unread')).')';
	}

	/**
	 * Callback function to return time in human format (Google style)
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	protected static function time_field($item, $name)
	{
		if (!$item->readed)
			echo '<b>';
		
		echo date::mail_time($item->time);
		
		if (!$item->readed)
			echo '</b>';
	}
}
