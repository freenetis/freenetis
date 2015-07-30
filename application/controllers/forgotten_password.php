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
		if (!$this->settings->get('forgotten_password') ||
			$this->session->get('user_id', 0))
		{
			url::redirect('login');
		}
		
		if ($this->input->get('request'))
		{
			self::change_password($this->input->get('request'));
		}
		else
		{
			$message = __('New password into the information system can be obtained via e-mail') . '.<br />';
			$message .= __('Please insert username and e-mail which you had filled in previously in Freenetis or which you filled in your application').'.';

			$form = new Forge();

			$form->input('login')
					->label('Username')
					->rules('required|length[3,50]')
					->callback(array($this, 'valid_username'));
					
			$form->input('email')
					->rules('length[4,50]|valid_email');

			// submit button
			$form->submit('Send');
			
			$form_html = $form->html();

			if ($form->validate())
			{
				$form_data = $form->as_array();

				/* @var $user User_Model */
				$user = ORM::factory('user')->where('login', $form_data['login'])->find();
				
				//if login was not found
				if (!$user->id)
				{
					$message = '<b class="error">'.__('Login do not match with data in information system').'. ';
					$message .= __('Please contact support.').'.</b>';
				}
				else
				{
					if ($user->email_exist($form_data['email'], $user->id))
					{
						$hash = text::random('numeric', 10);

						$user->password_request = $hash;
						$user->save();

						// From, subject and HTML message
						$from = Settings::get('email_default_email');
						$to = $form_data['email'];
						$subject = 'FreenetIS - '.__('Forgotten password');
						
						$e_message = '<html><body>';
						$e_message .= __('Hello').' ';
						$e_message .= $user->login.',<br /><br />';
						$e_message .= __('Someone from the IP address %s, probably you, requested to change your password', server::remote_addr()).'. ';
						$e_message .= __('New password can be changed at the following link').':<br /><br />';
						$e_message .= html::anchor('forgotten_password?request='.$hash,	url_lang::base().'forgotten_password?request='.$hash);
						$e_message .= '<br /><br />'.url_lang::lang('mail.welcome').'<br />';
						$e_message .= '</body></html>';
			
						if (email::send($to, $from, $subject, $e_message, true))
						{
						    $message = '<b>'.__('The request has been sent to your e-mail').' (';
							$message .= $to . ').</b><br />';
						    $message .= __('Please check your e-mail box').'. ';
						    $message .= __('If message does not arrive in 20 minutes, please contact support').'.';
						}
						else
						{
						    $message = __('Sending message failed. Please contact support.');
						}
						
						$form_html = '';
					}
					else
					{
						$message = '<b class="error">'.__('E-mail do not match with data in information system. Please contact support.').'.</b>';;
					}
				}
			}
			
			$view = new View('forgotten_password/index');
			$view->title = __('Forgotten password');
			$view->message = $message;
			$view->form = $form_html;
			$view->render(TRUE);
		}
	}

	/**
	 * Method shows form dialog for password change.
	 * 
	 * @param string $hash
	 */
	private function change_password($hash)
	{
		$user = ORM::factory('user')->where('password_request', $hash)->find();


		if ($user->id == 0)
		{
			$view = new View('forgotten_password/index');
			$view->title = __('Forgotten password');
			$view->message = __('Reguest is invalid or expired').'.';
			$view->form = null;
			$view->render(TRUE);
		}
		else
		{
			$form = new Forge('forgotten_password?request='.htmlspecialchars($hash));

			$form->password('password')
					->label('New password')
					->rules('required|length[3,50]')
					->class('password');
			
			$form->password('confirm_password')
					->label('Confirm new password')
					->rules('required|length[3,50]')
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

	/**
	 * Check if username is valid
	 *
	 * @param object $input 
	 */
	public function valid_username($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if (preg_match('/^[a-zA-Z0-9]+$/', $input->value) == 0)
		{
			$input->add_error('required', __(
					'Login must contains only a-z and 0-9 and starts with literal.'
			));
		}
	}
}

















