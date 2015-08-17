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
				->label('ID');

		$grid_contacts->callback_field('type')
				->callback('callback::additional_contacts_type_callback');

		$grid_contacts->callback_field('value')
				->callback('callback::verified_contact')
				->help(help::hint('verified_contact'));

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
			
			$actions->add_conditional_action()
					->condition('is_contact_verifiable')
					->icon_action('approve')
					->label('Verify contact')
					->url('contacts/verify')
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

		if (Settings::get('phone_invoices_enabled') && $this->acl_check_new(
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
					->label('ID');

			$grid_private_contacts->callback_field('type')
					->callback('callback::additional_contacts_type_callback');

			$grid_private_contacts->field('value');

			$grid_private_contacts->field('description');

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
		
		$this->_contact_id = NULL;

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
		
		if (Settings::get('email_enabled'))
		{
			$form->checkbox('mail_redirection')
				->label(__('Inner mail redirection enabled to this e-mail box') . '?');
		}

		$form->submit('Add');

		if ($form->validate())
		{
			// search for contacts
			$contact_id = $contact_model->find_contact_id(
					$form->type->value, $form->value->value
			);

			try
			{
				$country_model->transaction_start();
				$form_values = $form->as_array();
				
				if ($contact_id)
				{
					$contact_model = new Contact_Model($contact_id);
					$contact_model->add($user);
					$contact_model->save_throwable();
				}
				else
				{ // add whole contact
					$contact_model->type = $form_values['type'];
					$contact_model->value = $form_values['value'];
					$contact_model->verify = 0;
					$contact_model->save_throwable();

					$contact_model->add($user);

					if ($contact_model->type == Contact_Model::TYPE_PHONE)
					{
						$country = ORM::factory('country', $form_values['country_code']);
						$contact_model->add($country);
					}
					else if (Settings::get('email_enabled') &&
						$contact_model->type == Contact_Model::TYPE_EMAIL)
					{
						$redirect = $form_values['mail_redirection'] ? 1 : 0;
						$contact_model->set_user_redirected_email($user->id, $redirect);
					}

					$contact_model->save_throwable();
				}
				
				$country_model->transaction_commit();

				if (module::e('email'))
				{
					try
					{
						self::send_verify_message($contact_model->id);
						status::success('Verification message have been successfully sent.');
					}
					catch (Exception $ex)
					{
						status::error('Error - cant send Verification message', $ex);
						Log::add_exception($ex);
					}
				}
				
				status::success('Additional contacts have been successfully updated');
				$this->redirect('contacts/show_by_user/',$user_id);
			}
			catch (Exception $e)
			{
				$country_model->transaction_rollback();
				status::error('Error - cant update additional contacts', $e);
				Log::add_exception($e);
			}
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
		
		// inner mail redirection option
		if (Settings::get('email_enabled') &&
			$contact_model->type == Contact_Model::TYPE_EMAIL)
		{
			$form->checkbox('mail_redirection')
					->label(__('Inner mail redirection enabled to this e-mail box') . '?')
					->checked($contact_model->is_user_redirected_email($user_id));
		}

		$form->submit('Update');

		if ($form->validate())
		{
			try
			{
				$contact_model->transaction_start();
				$form_data = $form->as_array();
				// contact
				$changed = false;
				if ($contact_model->value !== $form_data['value'])
				{
					$contact_model->verify = 0;
					$changed = true;
				}
				
				$contact_model->value = $form_data['value'];
				$contact_model->save_throwable();
				// mail redirection
				if (Settings::get('email_enabled') &&
					$contact_model->type == Contact_Model::TYPE_EMAIL)
				{
					$redirect = $form_data['mail_redirection'] ? 1 : 0;
					$contact_model->set_user_redirected_email($user_id, $redirect);
				}
				// ok
				$contact_model->transaction_commit();
				
				if ($changed && module::e('email'))
				{
					try
					{
						self::send_verify_message($contact_id);
						status::success('Verification message have been successfully sent.');
					}
					catch (Exception $ex)
					{
						status::error('Error - cant send Verification message', $ex);
						Log::add_exception($ex);
					}
				}
				
				status::success('Additional contacts have been successfully updated');
				$this->redirect('contacts/show_by_user/', $user_id);
			}
			catch (Exception $ex)
			{
				$contact_model->transaction_rollback();
				status::error('Error - cant update additional contacts', $ex);
				Log::add_exception($ex);
			}
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
		if (!$this->acl_check_delete('Users_Controller', 'additional_contacts_admin_delete'))
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

            // do not search if duplicies enabled (#968)
            if ($type == Contact_Model::TYPE_EMAIL &&
                    Settings::get('user_email_duplicities_enabled'))
            {
                return;
            }
            if ($type == Contact_Model::TYPE_PHONE &&
                    Settings::get('user_phone_duplicities_enabled'))
            {
                return;
            }

			// search for contacts
			$duplicip_contacts = $contact_model->find_contacts($type, trim($input->value));
			
			foreach ($duplicip_contacts as $c)
			{
				if ($c->id && ($c->id != $this->_contact_id))
				{
					if ($contact_model->count_all_users_contacts_relation($c->id) > 0)
					{
						$input->add_error('required', __('Contact is already in database'));
						break;
					}
				}
			}
		}
	}
	
	/**
	 * Verify contact of user
	 * @param type $user_id
	 * @param type $contact_id 
	 */
	public function verify($contact_id = NULL, $verify = 0)
	{
		if (empty($contact_id) || !is_numeric($contact_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$contact_model = new Contact_Model($contact_id);

		if (!$contact_model->id)
		{
			Controller::error(RECORD);
		}
		
		$user_contacts_model = new Users_contacts_Model();
		$user_id = $user_contacts_model->get_user_of_contact($contact_id);
		
		$user = new User_Model($user_id);


		// rights
		if (!$this->acl_check_edit(
				'Users_Controller', 'additional_contacts', $user->member_id
			) || !condition::is_contact_verifiable($contact_model))
		{
			Controller::error(ACCESS);
		}

		$view = new View('main');
		$view->title = __('Verify contact');

		$enum_type_model = new Enum_type_Model();
		$country_code = NULL;

		if ($contact_model->type == Contact_Model::TYPE_PHONE)
		{
			$country_code = $contact_model->get_phone_prefix();
		}

		$form = new Forge("contacts/verify/$contact_id?verify");
		$form->hidden('contact_id')
			->value($contact_id);
		
		$form->submit('Verify contact');
		
		$form2 = new Forge("contacts/verify/$contact_id?send");
		$form2->hidden('contact_id')
			->value($contact_id);

		$form2->submit('Send verify message');

		if (!module::e('email'))
		{
			$form2->inputs[0]->disabled('disabled');
			$form2->inputs[0]->title(__('E-mail module is required to send verification message.'));
		}

		if ($verify == 1)
		{
			$contact_model->transaction_start();
			// contact
			$contact_model->verify = 1;
			$contact_model->save_throwable();
			// ok
			$contact_model->transaction_commit();
			status::success(
				'Your contact %s have been successfully verified, thank you for your participation in the verification process.',
				TRUE,
				array($contact_model->value)
			);

			$this->redirect('contacts/show_by_user/'.$user_id);
		}

		if ($form->validate())
		{
			if (isset($_GET) && isset($_GET['verify']))
			{
				$form_array = $form->as_array();
				try
				{
					$contact_model->transaction_start();
					// contact
					$contact_model->verify = 1;
					$contact_model->save_throwable();
					// ok
					$contact_model->transaction_commit();
					status::success('Contact have been successfully verified');
						
				}
				catch (Exception $ex)
				{
					$contact_model->transaction_rollback();
					status::error('Error - cant verify additional contact', $ex);
					Log::add_exception($ex);
				}

				$this->redirect(Path::instance()->previous());
			}
			else
			{
				if (module::e('email'))
				{
					try
					{
						self::send_verify_message($contact_id);
						status::success('Verification message have been successfully sent.');
					}
					catch (Exception $ex)
					{
						status::error('Error - cant send verification message', $ex);
						Log::add_exception($ex);
					}
				}

				$this->redirect(Path::instance()->previous());
			}
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
					->text('Verify contact');

			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('contacts/verify');
			$view->content->form = $form->html();
			$view->content->form2 = $form2->html();
			$view->content->contact_type = $enum_type_model->get_value(
					$contact_model->type
			);
			$view->content->country_code = $country_code;
			$view->content->value = $contact_model->value;
			$view->content->verified = $contact_model->verify;
			$view->render(TRUE);
		}
	} 
	
	/**
	 * Sends verification message
	 * 
	 * @param mixed $user_id 
	 * @param mixed $contact_id 
	 */
	public static function send_verify_message($contact_id)
	{
		$contact_model = new Contact_Model($contact_id);
		$message_model = new Message_Model();


		if ($contact_model->verify == 1)
		{
			return;
		}
		
		// find message
		$verify_message = $message_model->
				where('type', Message_Model::VERIFY_CONTACT_MESSAGE)->find();

		$data = $contact_model->get_message_info_by_contact_id($contact_id)->current();
		$data->verify_link = url_lang::site('contacts/verify/'.$contact_id.'/1', FALSE, FALSE);

		if ($contact_model->type == Contact_Model::TYPE_EMAIL)
		{
			Message_Model::send_email(
				$verify_message,
				$contact_model->value,
				$data
			);
		}
	}
}