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
 * Controller enables generate new password for user by user.
 * Generated password is sended to user by email.
 * 
 * @package	Controller
 */
class Forgotten_password_Controller extends Controller
{
	/**
	 * Interface for getting forgotten password
	 */
	public function index()
	{
		if (!Settings::get('forgotten_password') || $this->session->get('user_id', 0))
		{
			url::redirect('login');
		}
		
		if ($this->input->get('request'))
		{
			self::change_password($this->input->get('request'));
			exit();
		}
		
		$message = __('New password into the information system can be obtained via e-mail') . '.<br />';
		$message .= __('Please insert username or e-mail which you had filled in previously in FreenetIS or which you filled in your application').'.';
		$message_error = NULL; 
		
		$form = new Forge();

		$form->input('data')
				->label('Username or e-mail')
				->rules('required');

		// submit button
		$form->submit('Send');

		$form_html = $form->html();

		if ($form->validate())
		{
			$form_data = $form->as_array();

			$user = new User_Model();
			$user_contact = new Users_contacts_Model();
			$contact = new Contact_Model();

			if (valid::email($form_data['data']))
			{
				$contact->where(array
				(
					'type'	=> Contact_Model::TYPE_EMAIL,
					'value'	=> $form_data['data']
				))->find();

				if ($contact->id)
				{
					$user_id = $user_contact->get_user_of_contact($contact->id);

					if ($user_id)
					{
						$user->find($user_id);
					}
				}
			}
			else
			{
				$user->where('login', $form_data['data'])->find();
			}

			// if login was not found
			if (!$user->id)
			{
				$message_error = __('Login or e-mail do not match with data in information system').'. ';
				$message_error .= __('Please contact support.').'.';
			}
			// if user has no e-mail addresses
			else if (!$contact->count_all_users_contacts($user->id, Contact_Model::TYPE_EMAIL))
			{
				$message_error = __('There is no e-mail filled in your account').'. ';
				$message_error .= __('Please contact support.').'.';
			}
			else
			{
				// e-mail address
				if ($contact->id)
				{
					$to = array($contact->value); 
				}
				else
				{
					$to = array();
					$contacts = $contact->find_all_users_contacts($user->id, Contact_Model::TYPE_EMAIL);

					foreach ($contacts as $c)
					{
						$to[] = $c->value;
					}
				}

				// save request string
				$hash = text::random('numeric', 10);
				$user->password_request = $hash;
				$user->save();

				// From, subject and HTML message
				$from = Settings::get('email_default_email');
				$subject = Settings::get('title') . ' - '.__('Forgotten password');

				$e_message = '<html><body>';
				$e_message .= __('Hello').' ';
				$e_message .= $user->get_full_name_with_login().',<br /><br />';
				$e_message .= __('Someone from the IP address %s, probably you, requested to change your password', server::remote_addr()).'. ';
				$e_message .= __('New password can be changed at the following link').':<br /><br />';
				$e_message .= html::anchor('forgotten_password?request='.$hash);
				$e_message .= '<br /><br />'.url_lang::lang('mail.welcome').'<br />';
				$e_message .= '</body></html>';

				$sended = TRUE;

				foreach ($to as $email)
				{
					if (!email::send($email, $from, $subject, $e_message, true))
					{
						$sended = FALSE;
					}
				}

				if ($sended)
				{
					$message = '<b>'.__('The request has been sent to your e-mail').' (';
					$message .= implode(', ', $to) . ').</b><br />';
					$message .= __('Please check your e-mail box').'. ';
					$message .= __('If message does not arrive in 20 minutes, please contact support').'.';
				}
				else
				{
					$message_error = __('Sending message failed. Please contact support.');
				}

				$form_html = '';
			}
		}

		$view = new View('forgotten_password/index');
		$view->title = __('Forgotten password');
		$view->message = $message . ($message_error ? '<br /><br /><b class="error">' . $message_error . '</b>' : '');
		$view->form = $form_html;
		$view->render(TRUE);
	}

	/**
	 * Method shows form dialog for password change.
	 * 
	 * @param string $hash
	 */
	private function change_password($hash)
	{
		$user = ORM::factory('user')->where('password_request', $hash)->find();
		
		if (!$user->id)
		{
			$view = new View('forgotten_password/index');
			$view->title = __('Forgotten password');
			$view->message = __('Reguest is invalid or expired').'.';
			$view->form = null;
			$view->render(TRUE);
		}
		else
		{
			$pass_min_len = Settings::get('security_password_length');
			
			$form = new Forge('forgotten_password?request='.htmlspecialchars($hash));

			$form->password('password')
					->label(__('New password') . ':&nbsp;' . help::hint('password'))
					->rules('required|length['.$pass_min_len.',50]')
					->class('main_password');
			
			$form->password('confirm_password')
					->label('Confirm new password')
					->rules('required|length['.$pass_min_len.',50]')
					->matches($form->password);

			// submit button
			$form->submit('Send');

			$message = __('Enter new password please').'.';

			if ($form->validate())
			{
				$form_data = $form->as_array(FALSE);
				
				$user->password = sha1($form_data['password']);
				$user->password_request = null;
				$user->save();

				$view = new View('forgotten_password/index');
				$view->title = __('Forgotten password');
				$view->message = '<b>'.__('Password has been successfully changed.').'</b>';
				$view->form = null;
				$view->render(TRUE);

			}
			else
			{
				$view = new View('forgotten_password/index');
				$view->title = __('Forgotten password');
				$view->message = $message;
				$view->form = $form->html();
				$view->render(TRUE);
			}
		}
	}
	
}

















