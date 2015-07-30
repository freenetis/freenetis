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
class Mail_messages_Controller extends Controller
{
	/**
	 * Shows mail message
	 *
	 * @param integer $message_id 
	 */
	public function show($message_id = NULL)
	{
		if (!$message_id || !is_numeric($message_id))
			Controller::warning(PARAMETER);

		$message = new Mail_message_Model($message_id);

		if (!$message->id)
			Controller::error(RECORD);

		$user_id = $this->session->get('user_id');

		$from_user = NULL;
		$to_user = NULL;
		$current = NULL;
		
		if ($message->from_id != $user_id)
		{
			$from_user = new User_Model($message->from_id);
		}
		else
		{
			$current = 'mail/sent';
		}

		if ($message->to_id != $user_id)
		{
			$to_user = new User_Model($message->to_id);
		}
		else
		{
			$current = 'mail/inbox';
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
	 * Adds new mail message
	 */
	public function add()
	{
		$view = new View('main');
		$view->title = __('Write new message');
		$view->content = new View('mail/main');
		$view->content->title = __('Write new message');
		$view->content->content = '';
		$view->render(TRUE);
	}


	/**
	 * Adds new mail message
	 * 
	 * @param number $to_id
	 * @param number $origin_id
	 */
	public function write($to_id = NULL, $origin_id = NULL)
	{
		$to_value = '';
		$subject_value = '';
		$body_value = '';

		if ($origin_id)
		{
			if (!is_numeric($origin_id))
				Controller::warning(PARAMETER);

			$origin = new Mail_message_Model($origin_id);

			if (!$origin->id)
				Controller::error(RECORD);

			if ($origin->from_id != $this->session->get('user_id'))
			{
				$to_user = new User_Model($origin->from_id);
			}
			else
			{
				$to_user = new User_Model($origin->to_id);
			}

			if (!$to_user->id)
				Controller::error(RECORD);

			$to_value = $to_user->login;
			$subject_value = 'Re: '.$origin->subject;
			$body_value = '<p></p><p>'.$to_user->name.' '.$to_user->surname.' '
					. __('wrote on').' '.date::pretty($origin->time)
					. ', '.__('at').' '.date::pretty_time($origin->time)
					. ':</p> <i>'.$origin->body.'</i>';
		}
		else if ($to_id)
		{
			if (!is_numeric($to_id))
				Controller::warning(PARAMETER);

			$to_user = new User_Model($to_id);

			if (!$to_user->id)
				Controller::error(RECORD);

			$to_value = $to_user->login;
		}

		$form = new Forge(url::base(TRUE).url::current(TRUE));

		$form->input('to')
				->class('mail_to_field')
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
				->class('focus')
				->rules('required')
				->value($body_value);

		$form->submit('Save');

		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);

			$recipients = explode(',', trim($form_data['to']));

			$user_model = new User_Model();
			foreach ($recipients as $recipient)
			{
				$user = $user_model->where('login', trim($recipient))->find();

				$mail_message = new Mail_message_Model();
				$mail_message->from_id = $this->session->get('user_id');
				$mail_message->to_id = $user->id;
				$mail_message->subject = htmlspecialchars($form_data['subject']);
				$mail_message->body = $form_data['body'];
				$mail_message->time = date('Y-m-d H:i:s');
				$mail_message->save();
			}

			url::redirect('mail/sent');
		}

		$view = new View('main');
		$view->title = __('Write new message');
		$view->content = new View('mail/main');
		$view->content->title = __('Write new message');
		$view->content->content = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Validator
	 *
	 * @param object $input
	 */
	public function valid_to_field($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$value = trim($input->value);
		$pattern = "/^([a-z][a-z0-9]*[_]{0,1}[a-z0-9]+),?[ ]*(([a-z][a-z0-9]*"
				. "[_]{0,1}[a-z0-9]+),?[ ]*)*$/";
		
		if (!preg_match($pattern, $input->value))
		{
			$input->add_error('required', __('Invalid value.'));
		}
		else
		{
			$recipients = explode(',', trim($value));
			$invalid_recipients = array();
			$user_model = new User_Model();

			foreach ($recipients as $recipient)
			{
				if (!$user_model->username_exist(trim($recipient)))
					$invalid_recipients[] = $recipient;
			}

			if (count($invalid_recipients))
			{
				if (count($invalid_recipients) == 1)
				{
					$input->add_error('required', __(
							'User %s doesn\'t exist.', '<b>'.
							$invalid_recipients[0].'</b>'
					));
				}
				else
				{
					$input->add_error('required', __(
							'Users %s don\'t exist.', '<b>'.
							implode(', ',$invalid_recipients).'</b>'
					));
				}
			}
		}
	}
}
