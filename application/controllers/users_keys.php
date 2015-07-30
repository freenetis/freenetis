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
 * Manages user's public SSH key for auto add privileges for some other services.
 *
 * @see Web_interface_Controller
 * @author	Michal Kliment
 * @package Controller
 */
class Users_keys_Controller extends Controller
{
	/**
	 * Adds new SSH key to user
	 *
	 * @author Michal Kliment
	 * @param integer $user_id
	 */
	public function add($user_id = NULL)
	{
		// bad parameter
		if (!$user_id || !is_numeric($user_id))
			Controller::warning(PARAMETER);

		$user = new User_Model($user_id);

		// user doesn't exist
		if (!$user->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_new('Users_Controller', 'keys', $user->member_id))
			Controller::error(ACCESS);

		$user_name = $user->name . ' ' . $user->surname;

		// creates form
		$form = new Forge(url::base(TRUE) . url::current(TRUE));

		$form->dropdown('user_id')
				->label('User')
				->options(array($user->id => $user_name))
				->rules('required')
				->style('width:350px');
		
		$form->textarea('key')
				->rules('required')
				->style('width:350px');

		$form->submit('Add');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$users_key = new Users_key_Model();
			$users_key->user_id = $user->id;
			$users_key->key = $form_data['key'];

			// success
			if ($users_key->save())
			{
				status::success('Key has been successfully added.');
			}

			url::redirect('users/show/' . $user->id);
		}

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller','members'))
				->disable_translation()
				->link('members/show/' . $user->member->id, 
						"ID ".$user->member->id." - ".$user->member->name,
						$this->acl_check_view(
								'Members_Controller','members', $user->member->id
						)
				)->enable_translation()
				->link('users/show_by_member/' . $user->member_id, 'Users',
						$this->acl_check_view(
								'Users_Controller', 'users', $user->member_id
						)
				)->disable_translation()
				->link('users/show/'.$user->id,
						"$user->name $user->surname ($user->login)",
						$this->acl_check_view(
								'Users_Controller','users', $user->member_id
						)
				)->enable_translation()
				->text('Add new SSH key');

		$title = __('Add new SSH key to user') . ' ' . $user_name;

		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $title;
		$view->content = new View('form');
		$view->content->headline = $title;
		$view->content->form = $form;
		$view->render(TRUE);
	}

	/**
	 * Edits SSH key of user
	 *
	 * @author Michal Kliment
	 * @param integer $users_key_id
	 */
	public function edit($users_key_id = NULL)
	{
		// bad parameter
		if (!$users_key_id || !is_numeric($users_key_id))
			Controller::warning(PARAMETER);

		$users_key = new Users_key_Model($users_key_id);

		// key doesn't exist
		if (!$users_key->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_edit(
				'Users_Controller', 'keys', $users_key->user->member_id
			))
		{
			Controller::error(ACCESS);
		}

		$user_name = $users_key->user->name . ' ' . $users_key->user->surname;

		// creates form
		$form = new Forge(url::base(TRUE) . url::current(TRUE));

		$form->dropdown('user_id')
				->label('User')
				->options(array($users_key->user->id => $user_name))
				->rules('required')
				->style('width:350px');
		
		$form->textarea('key')
				->rules('required')
				->value($users_key->key)
				->style('width:350px');

		$form->submit('Add');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$users_key = new Users_key_Model($users_key->id);
			$users_key->key = $form_data['key'];

			// success
			if ($users_key->save())
			{
				status::success('Key has been successfully updated.');
			}

			url::redirect('users/show/' . $users_key->user->id);
		}
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller','members'))
				->disable_translation()
				->link('members/show/' . $users_key->user->member->id, 
						"ID ".$users_key->user->member->id." - ".$users_key->user->member->name,
						$this->acl_check_view(
								'Members_Controller','members', $users_key->user->member->id
						)
				)->enable_translation()
				->link('users/show_by_member/' . $users_key->user->member_id, 'Users',
						$this->acl_check_view(
								'Users_Controller', 'users', $users_key->user->member_id
						)
				)->disable_translation()
				->link('users/show/'.$users_key->user->id,
						$users_key->user->name . ' ' . $users_key->user->surname .
						'(' . $users_key->user->login . ')',
						$this->acl_check_view(
								'Users_Controller','users', $users_key->user->member_id
						)
				)->enable_translation()
				->text('Edit SSH key');

		$title = __('Edit SSH key of user') . ' ' . $user_name;

		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $title;
		$view->content = new View('form');
		$view->content->headline = $title;
		$view->content->form = $form;
		$view->render(TRUE);
	}

	/**
	 * Deletes SSH key
	 *
	 * @author Michal Kliment
	 * @param integer $users_key_id
	 */
	public function delete($users_key_id = NULL)
	{
		// bad parameter
		if (!$users_key_id || !is_numeric($users_key_id))
			Controller::warning(PARAMETER);

		$users_key = new Users_key_Model($users_key_id);

		// key doesn't exist
		if (!$users_key->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_new('Users_Controller', 'keys', $users_key->user->member_id))
			Controller::error(ACCESS);

		$user_id = $users_key->user_id;

		// success
		if ($users_key->delete())
			status::success('Key has been successfully deleted.');

		url::redirect('users/show/' . $user_id);
	}

}
