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
 * Controller performs contacts actions such as viewing, editing, etc
 * 
 * @package Controller
 */
class Contacts_Controller extends Controller
{
	private $_type = NULL;
	private $_contact_id = NULL;
	
	/**
	 * Shows contacts of user
	 * 
	 * @author Ondřej Fibich
	 * @param type $user_id
	 * @param type $contact_id 
	 */
	public function show_by_user(
			$user_id = NULL, $contact_id = NULL)
	{
		if (empty($user_id) || !is_numeric($user_id))
		{
			Controller::error(RECORD);
		}

		$user = new User_Model($user_id);

		if (!$user || !$user->id)
		{
			Controller::error(RECORD);
		}

		$contact_model = new Contact_Model($contact_id);
		$country_model = new Country_Model();

		$view = new View('main');
		$view->title = __('Administration of additional contacts');

		// rights
		if (!$this->acl_check_view(
				'Users_Controller', 'additional_contacts',
				$user->member_id
			))
		{
			Controller::error(ACCESS);
		}

		$grid_contacts = new Grid(url::current(true), null, array
		(
			'use_paginator' => false,
			'use_selector' => false
		));

		$grid_contacts->field('id')
				->label(__('ID'));

		$grid_contacts->callback_field('type')
				->label(__('Type'))
				->callback('Users_Controller::additional_contacts_type_callback');

		$grid_contacts->field('value')
				->label(__('Value'));

		$actions = $grid_contacts->grouped_action_field();

		if ($this->acl_check_edit(
				'Users_Controller', 'additional_contacts',
				$user->member_id
			))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('contacts/edit/'.$user_id)
					->class('popup_link');
		}

		if ($this->acl_check_delete(
				'Users_Controller', 'additional_contacts',
				$user->member_id
			))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('contacts/delete/'.$user_id)
					->class('delete_link');
		}

		$grid_private_contacts = NULL;
		$grid_contacts->datasource($contact_model->find_all_users_contacts($user_id));

		if ($this->acl_check_new(
				'Private_phone_contacts_Controller', 'contacts',
				$user->member_id
			))
		{
			$grid_private_contacts = new Grid(
					url::base(TRUE) . url::current(true), null, array
			(
				'use_paginator' => false,
				'use_selector' => false
			));

			$grid_private_contacts->field('id')
					->label(__('ID'));

			$grid_private_contacts->callback_field('type')
					->label(__('Type'))
					->callback('Users_Controller::additional_contacts_type_callback');

			$grid_private_contacts->field('value')
					->label(__('Value'));

			$grid_private_contacts->field('description')
					->label(__('Description'));

			$actions = $grid_private_contacts->grouped_action_field();

			$actions->add_action()
					->icon_action('edit')
					->url('private_phone_contacts/edit');

			$actions->add_action()
					->icon_action('delete')
					->url('private_phone_contacts/delete')
					->class('delete_link');

			$grid_private_contacts->datasource(
					$contact_model->find_all_private_users_contacts($user_id)
			);
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
				->text('User contacts');

		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('contacts/show_by_user');
		$view->content->grid_contacts = $grid_contacts;
		$view->content->grid_private_contacts = $grid_private_contacts;
		$view->content->user_id = $user_id;
		$view->content->can_add = $this->acl_check_new(
				'Users_Controller', 'additional_contacts', $user->member_id
		);
		
		$view->render(TRUE);
	} // end of additional_contacts function
	
	/**
	 * Adds new contact to user
	 * 
	 * @author Ondřej Fibich
	 * @param type $user_id
	 * @param type $contact_id 
	 */
	public function add(
			$user_id = NULL, $contact_id = NULL)
	{
		if (empty($user_id) || !is_numeric($user_id))
		{
			Controller::error(RECORD);
		}

		$user = new User_Model($user_id);

		if (!$user || !$user->id)
		{
			Controller::error(RECORD);
		}

		$contact_model = new Contact_Model($contact_id);
		$country_model = new Country_Model();

		$view = new View('main');
		$view->title = __('Administration of additional contacts');

		// rights
		if (!$this->acl_check_new(
				'Users_Controller', 'additional_contacts', $user->member_id
			))
		{
			Controller::error(ACCESS);
		}
		$enum_type_model = new Enum_type_Model();
		$contact_types = $enum_type_model->get_values(Enum_type_Model::CONTACT_TYPE_ID);
		$countries_list = $country_model->select_country_list();

		$form = new Forge();

		$form->dropdown('type')
				->rules('required')
				->options($contact_types)
				->id('type_dropdown')
				->style('width:200px');

		$form->dropdown('country_code')
				->options($countries_list)
				->id('country_code_dropdown')
				->selected($this->settings->get('default_country'))
				->style('width:200px');

		$form->input('value')
				->rules('required')
				->callback(array($this, 'valid_value'));

		$form->submit('Add');

		if ($form->validate())
		{
			$issaved = TRUE;
			$message_added = FALSE;

			// search for contacts
			$contact_id = $contact_model->find_contact_id(
					$form->type->value, $form->value->value
			);

			if ($contact_id)
			{
				$contact_model = ORM::factory('contact', $contact_id);
				
				$issaved = $issaved && $contact_model->add($user);
				$issaved = $issaved && $contact_model->save();
			}
			else
			{ // add whole contact
				$contact_model->type = $form->type->value;
				$contact_model->value = $form->value->value;
				$issaved = $issaved && $contact_model->save();

				$issaved = $issaved && $contact_model->add($user);

				if ($form->type->value == Contact_Model::TYPE_PHONE)
				{
					$country = ORM::factory('country', $form->country_code->value);
					$issaved = $issaved && $contact_model->add($country);
				}

				$issaved = $issaved && $contact_model->save();
			}

			if ($issaved)
			{
				if (!$message_added)
				{
					status::success('Additional contacts have been successfully updated');
				}
			}
			else
			{
				status::error('Error - cant update additional contacts');
			}
			$this->redirect('contacts/show_by_user/',$user_id);
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller','members'))
					->disable_translation()
					->link('members/show/' . $user->member->id, 
							"ID ".$user->member->id." - ".$user->member->name,
							$this->acl_check_view(
									'Members_Controller','members',
									$user->member->id
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
					->link('contacts/show_by_user/' . $user_id, 
							'User contacts',
							$this->acl_check_view(
									'Users_Controller', 'additional_contacts',
									$user->member_id
							))
					->text('Add');

			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Add contact');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	} // end of additional_contacts function
	
	/**
	 * Edits contact of user
	 * @param type $user_id
	 * @param type $contact_id 
	 */
	public function edit($user_id = NULL, $contact_id = NULL)
	{
		if (empty($user_id) || !is_numeric($user_id))
		{
			Controller::error(RECORD);
		}

		$user = new User_Model($user_id);

		if (!$user || !$user->id)
		{
			Controller::error(RECORD);
		}

		$contact_model = new Contact_Model($contact_id);
		$country_model = new Country_Model();
		
		$this->_type = $contact_model->type;
		
		$this->_contact_id = $contact_id;

		$view = new View('main');
		$view->title = __('Administration of additional contacts');

		// rights
		if (!$this->acl_check_edit(
				'Users_Controller', 'additional_contacts', $user->member_id
			))
		{
			Controller::error(ACCESS);
		}

		if (empty($contact_id) || !is_numeric($contact_id))
		{
			Controller::warning(PARAMETER);
		}

		if (!$contact_model->id)
		{
			Controller::error(RECORD);
		}

		$enum_type_model = new Enum_type_Model();
		$country_code = NULL;

		if ($contact_model->type == Contact_Model::TYPE_PHONE)
		{
			$country_code = $contact_model->get_phone_prefix();
		}

		$form = new Forge();

		$rules = 'required';

		if ($contact_model->type == Contact_Model::TYPE_EMAIL)
		{
			$rules .= '|valid_email';
		}
		else if ($contact_model->type == Contact_Model::TYPE_PHONE)
		{
			$rules .= '|valid_phone';
		}

		$form->input('value')
				->rules($rules)
				->value($contact_model->value)
				->callback(array($this, 'valid_value'));

		$form->submit('Update');

		if ($form->validate())
		{
			if ($contact_model->value != $form->value->value &&
				$contact_model->find_contact_id(
						$contact_model->type, $form->value->value
				))
			{
				status::warning('Contact is already in database');
			}
			else
			{
				$issaved = TRUE;

				$contact_model->value = $form->value->value;
				$issaved = $issaved && $contact_model->save();

				if ($issaved)
				{
					status::success('Additional contacts have been successfully updated');
				}
				else
				{
					status::error('Error - cant update additional contacts');
				}
			}

			$this->redirect('contacts/show_by_user/',$user_id);
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller','members'))
					->disable_translation()
					->link('members/show/' . $user->member->id, 
							"ID ".$user->member->id." - ".$user->member->name,
							$this->acl_check_view(
									'Members_Controller','members',
									$user->member->id
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
					->link('contacts/show_by_user/' . $user_id, 
							'User contacts',
							$this->acl_check_view(
									'Users_Controller', 'additional_contacts',
									$user->member_id
							)
					)->disable_translation()
					->text($contact_model->value)
					->enable_translation()
					->text('Edit');

			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('contacts/edit');
			$view->content->contact_type = $enum_type_model->get_value(
					$contact_model->type
			);
			$view->content->country_code = $country_code;
			$view->content->user_id = $user_id;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	} // end of additional_contacts function
	
	/**
	 * Deletes contact of user
	 * 
	 * @author Ondřej Fibich
	 * @param type $user_id
	 * @param type $contact_id 
	 */
	public function delete($user_id = NULL, $contact_id = NULL)
	{
		if (empty($user_id) || !is_numeric($user_id))
		{
			Controller::error(RECORD);
		}

		$user = new User_Model($user_id);

		if (!$user || !$user->id)
		{
			Controller::error(RECORD);
		}

		$contact_model = new Contact_Model($contact_id);
		$country_model = new Country_Model();

		// rights
		if (!$this->acl_check_delete(
				'Users_Controller', 'additional_contacts', $user->member_id
			))
		{
			Controller::error(ACCESS);
		}

		if (!$contact_model->id)
		{
			Controller::error(RECORD);
		}

		$can_delete = true;

		// phone and emails can be deleted only if there is another contacts
		// each user has to have one phone and one email
		// this rule can be obtain if user who make this action has admin rules
		if (!$this->acl_check_delete('Settings_Controller', 'system'))
		{
			if ($contact_model->type == Contact_Model::TYPE_EMAIL ||
				$contact_model->type == Contact_Model::TYPE_PHONE)
			{
				if ($contact_model->count_all_users_contacts(
						$user_id, $contact_model->type
					) <= 1)
				{
					$can_delete = false;
				}
			}
		}

		if ($can_delete)
		{
			if ($contact_model->count_all_relation() == 1)
			{ // delte whole contact
				$contact_model->delete();
			}
			else
			{ // delete just relation
				$contact_model->remove($user);
				$contact_model->save();
			}

			status::success('Contact has been deleted');
		}
		else
		{
			status::warning('Cannot delete, there are other records depending on this one.');
		}
		$this->redirect('contacts/show_by_user/',$user_id);
		
	} // end of additional_contacts function
	
	/**
	 * Callback function to validate form for adding/editing of contact
	 * 
	 * @author Michal Kliment
	 * @param type $input 
	 */
	public function valid_value($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$type = ($this->_type) ? $this->_type : $this->input->post('type');
		
		if ($type == Contact_Model::TYPE_EMAIL 
				&& !valid::email(trim($input->value)))		
		{
			$input->add_error('required', __('Wrong email'));
		}
		else if ($type == Contact_Model::TYPE_PHONE
				&& !valid::phone(trim($input->value)))		
		{
			$input->add_error('required', __('Wrong phone number'));
		}
		else
		{
			$contact_model = new Contact_Model();
			
			// search for contacts
			$contact_id = $contact_model->find_contact_id(
					$type, trim($input->value)
			);

			if ($contact_id && $contact_id != $this->_contact_id)
			{
				$contact_model = ORM::factory('contact', $contact_id);
				if ($contact_model->count_all_users_contacts_relation() > 0)
				{
					$input->add_error('required', __('Contact is already in database'));
				}
			}
		}
	}
}