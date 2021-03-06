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
 * Controller performs user login and logout to system.
 * 
 * @package Controller
 */
class Login_Controller extends Controller
{
	/**
	 * Hadles user login
	 *
	 * @param mixed $error 
	 */
	public function index($error = FALSE, $success = FALSE)
	{
		cookie::set('testcookie', 'enabled', 3600);
		
		// redirect if cookies not enabled to ensure that this is not the first
		// time when user access FreenetIS
		if (!isset($_COOKIE['testcookie']) && !isset($_GET['cookies_failed']))
		{
			url::redirect('login?cookies_failed=true');
		}
		
		if ($this->input->post('submit') != '')
		{
			// test if cookie is enabled
			if (!isset($_COOKIE['testcookie'])) 
			{
				$error = __('Cookies must be enabled.');
			}
			else
			{
				// Check the username and password
				$user_model = new User_Model();
				
				$user_id = $user_model->login_request(
						$this->input->post('username'),
						$this->input->post('password')
				);
				
				// correct input?
				if ($user_id)
				{
					$user = ORM::factory('user')->where(array
					(
							'id' => $user_id
					))->find();
					
					$member = ORM::factory('member')->where(array
					(
							'id' => $user->member->id
					))->find();
					
					if ($member->locked)
					{
						$error = __('Your accout has been locked.').' '
								.__('Please contact administrator.');
					}
					else
					{
						$this->session->set('username', $this->input->post('username'));
						$this->session->set('user_id', $user_id);
						$user_model->clear();
						
						$user_model->find($user_id);
						$member_id = $user_model->member_id;
						$user_type = $user_model->type;
						$this->session->set('member_id', $user_model->member_id);
						$this->session->set('user_full_name', $user_model->get_full_name());
						$this->session->set('user_name', $user_model->name);
						$this->session->set('user_surname', $user_model->surname);
						$this->session->set('user_type', $user_type);
						$user_model->clear();
						
						$user_model->where(array
						(
								'member_id'	=> $member_id,
								'type!='	=> User_Model::USER
						))->find();
						
						$this->session->set('member_login', $user_model->login);
					
						// information about users' last login is saved
						$login_log = new Login_log_Model();
						$login_log->user_id = $user_id;
						$login_log->time = date('Y-m-d H:i:s');
						$login_log->IP_address = server::remote_addr();
						$login_log->save();
						
						status::success('You have been successfully logged in.');

						if ($this->session->get('referer') != '')
						{
							url::redirect($this->session->get('referer'));
						}
						else
						{
							$favourite = ORM::factory('user_favourite_pages')->get_user_default_page($user_id);
							
							if ($favourite)
							{
								url::redirect($favourite->page);
							}
							else
							{
								if ($user_type != User_Model::USER)
								{
									url::redirect('members/show/'.$member_id);
								}
								else
								{
									url::redirect('users/show/'.$user_id);
								}
							}
						}
					}
				}
				else
				{
					$error = __('Username or password do not match.');
				}	
			}
		}

		// check if is logged in 
		if (isset($_SESSION['username']))
		{
			$favourite = ORM::factory('user_favourite_pages')->get_user_default_page($_SESSION['user_id']);
							
			if ($favourite)
			{
				url::redirect($favourite->page);
			}
			else
			{
				if ($_SESSION['username'] == $_SESSION['member_login'])
				{
					url::redirect('members/show/'.$_SESSION['member_id']);
				}
				else
				{
					url::redirect('users/show/'.$_SESSION['user_id']);
				}
			}
		}
		
		// view
		$login = new View('login/index');
		$login->title = __('Login to');		
		$login->error = (!$error) ? $this->session->get_once('err_message') : $error;
		$login->success = $success;
		
		// connnection request
		if (mb_strpos($this->session->get('referer'), 'connection_requests/add') !== FALSE &&
			!$error)
		{
			$login->error = __('For the connection request creation you must login to your account.');
		}

		$login->render(TRUE);
	}

	/**
	 * Function logs out user from the system.
	 */
	public function logout()
	{
		$this->session->destroy();	
		$this->index(FALSE, __('You have been successfully logged out.'));
	}

}
