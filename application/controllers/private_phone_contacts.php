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
 */

/**
 * Phone_invoices_Controller managing private contacts (mean.: phone numbers)
 *
 * @author Ondřej Fibich
 * @package Controller
 */
class Private_phone_contacts_Controller extends Controller
{
	/**
	 * Index disabled
	 */
	public function index()
	{
		Controller::warning(PARAMETER);
	}

	/**
	 * Import conatcts from Fundamol server
	 * 
	 * @param integer $user_id
	 */
	public function import($user_id = NULL)
	{
		if (!is_numeric($user_id))
		{
			Controller::warning(PARAMETER);
		}

		$user = new User_Model($user_id);

		if (!$user->id)
		{
			Controller::error(RECORD);
		}

		if (!$this->acl_check_new(
				'Private_phone_contacts_Controller',
				'contacts', $user->member_id
			))
		{
			Controller::error(ACCESS);
		}

		$can_add = $this->acl_check_new('Users_Controller', 'additional_contacts');
		$users_names = $user->get_all_user_names();
		$country_model = new Country_Model(Settings::get('default_country'));

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/' . $user->member->id,
						'ID ' . $user->member->id . ' - ' . $user->member->name,
						$this->acl_check_view('Members_Controller', 'members', $user->member->id))
				->enable_translation()
				->link('users/show_by_member/' . $user->member_id, 'Users',
						$this->acl_check_view('Users_Controller', 'users', $user->member_id))
				->disable_translation()
				->link('users/show/' . $user->id, 
						$user->name . ' ' . $user->surname . ' (' . $user->login . ')',
						$this->acl_check_view('Users_Controller', 'users', $user->member_id))
				->enable_translation()
				->link('contacts/show_by_user/'.$user->id, 'Private contacts',
						$this->acl_check_view('Users_Controller', 'additional_contacts', $user->member_id))
				->text('Import private contact');

		// view
		$view = new View('main');
		$view->title = __('Import private contact');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('users/contacts_import');
		$view->content->can_add_user_contact = $can_add;
		$view->content->user = $user;
		$view->content->users_names = $users_names;
		$view->content->default_phone_prefix = $country_model->country_code;
		$view->render(TRUE);
	}

	/**
	 * Save funanbol import via AJAX
	 * Response is JSON
	 * 
	 * @param integer $user_id
	 */
	public function import_save_ajax($user_id = NULL)
	{
		$error = array
		(
			'error_data' => array('error' => __('Error - Wrong data')),
			'error_args' => array('error' => __('Error - Wrong arguments')),
			'error_save' => array('error' => __('Error - Cannot save data')),
		);

		$status_private_saved = 0;
		$status_private_already_in = 1;
		$status_global_saved = 2;
		$status_global_already_in = 3;
		$status_global_own_by_another = 5;

		if (!is_numeric($user_id))
		{
			echo json_encode($error['error_args']);
			return;
		}

		$user = new User_Model($user_id);

		if (!$user->id)
		{
			echo json_encode($error['error_args']);
			return;
		}

		if (!$this->acl_check_new(
				'Private_phone_contacts_Controller',
				'contacts', $user->member_id
			))
		{
			echo json_encode($error['error_args']);
			return;
		}

		$can_add = $this->acl_check_new('Users_Controller', 'additional_contacts');

		try
		{
			if ($_POST && count($_POST))
			{
				$im_names = @$_POST['im_name'];
				$im_namefs = @$_POST['im_namef'];
				$im_namels = @$_POST['im_namel'];
				$im_phones = @$_POST['im_phone'];
				$im_privates = @$_POST['im_private'];
				$im_constacts = @$_POST['im_contact'];

				if (!is_array($im_names) || !is_array($im_namels) ||
					!is_array($im_namefs) || !is_array($im_phones))
				{
					echo json_encode($error['error_args']);
					return;
				}

				$output = array();
				$private_user_contact = new Private_users_contact_Model();
				$country_model = new Country_Model();
				// start trasaction
				$user->transaction_start();
				// we have got right data
				foreach ($im_names as $index => $im_name)
				{
					// data
					$number = $im_phones[$index];
					$name = $im_namefs[$index];
					$surname = $im_namels[$index];
					// models
					$contact = new Contact_Model();
					$country = NULL;
					// gets number without country prefix
					$country_id = $country_model->find_phone_country_id($number);
					$number_short = $number;
					
					if ($country_id > 0)
					{
						$country = new Country_Model($country_id);
						$number_short = substr($number, strlen($country->country_code));
					}
					// switch to right action
					if (isset($im_privates[$index]))
					{ // private contact
						// is contact already in database?
						$contact_id = $contact->find_contact_id(
								Contact_Model::TYPE_PHONE, $number_short
						);
						
						if ($contact_id == 0)
						{
							$contact->type = Contact_Model::TYPE_PHONE;
							$contact->value = $number_short;
							$contact->save_throwable();
							// add country of phone number
							if ($country != NULL)
							{
								$contact->add($country);
								$contact->save_throwable();
							}
							$contact_id = $contact->id;
						}

						$private_user_contact_id = self::private_contacts_cache(
								$user_id, $number
						);
						
						if ($private_user_contact_id > 0)
						{
							// output
							$output[] = array
							(
								'id' => $index,
								'status' => $status_private_already_in
							);
						}
						else
						{
							// add relation M:N between users and contacts
							$private_user_contact->contact_id = $contact_id;
							$private_user_contact->user_id = $user_id;
							$private_user_contact->description = $im_name;
							$private_user_contact->save_throwable();
							$private_user_contact->clear();
							// output
							$output[] = array
							(
								'id' => $index,
								'status' => $status_private_saved
							);
						}
					}
					else if ($can_add && isset($im_constacts[$index]))
					{ // global contact of some user
						$im_constact = intval($im_constacts[$index]);
						$user_model = new User_Model($im_constact);

						if ($user_model && $user_model->id)
						{
							// search for contacts
							$contact_id = $contact->find_contact_id(
									Contact_Model::TYPE_PHONE, $number_short
							);

							if ($contact_id)
							{
								$contact = ORM::factory('contact', $contact_id);
								$realations = $contact->count_all_users_contacts_relation();
								if ($realations == 0)
								{ // add relation
									$contact->add($user_model);
									$contact->save_throwable();
									// output
									$output[] = array
									(
										'id' => $index,
										'status' => $status_global_saved
									);
								}
								else if ($contact->is_users_contact($user_model->id))
								{ // already in
									$output[] = array
									(
										'id' => $index,
										'status' => $status_global_already_in
									);
								}
								else
								{ // another user owns this contact
									$output[] = array
									(
										'id' => $index,
										'status' => $status_global_own_by_another
									);
								}
							}
							else
							{ // add whole contact
								$contact->type = Contact_Model::TYPE_PHONE;
								$contact->value = $number_short;
								$contact->save_throwable();

								$contact->add($user_model);
								$contact->save_throwable();

								if ($country != NULL)
								{
									$contact->add($country);
									$contact->save_throwable();
								}
							}
						}
						else
						{
							throw new Exception();
						}
					}
					else
					{ // error
						throw new Exception();
					}
				}
				// commit transaction
				$user->transaction_commit();
				// output
				echo json_encode(array('success' => $output));
			}
			else
			{
				echo json_encode($error['error_data']);
			}
		}
		catch (Exception $ex)
		{
			$user->transaction_rollback();
			Log::add_exception($ex);
			echo json_encode($error['error_save']);
		}
	}

	/**
	 * Adds private phone contact
	 * 
	 * @param integer $user_id
	 * @param string $number
	 */
	public function add($user_id = 0, $number = null)
	{
		if (!is_numeric($user_id) || $user_id <= 0 || $number == null)
		{
			Controller::warning(PARAMETER);
		}

		$user = new User_Model($user_id);

		if (!$user->id)
		{
			Controller::error(RECORD);
		}

		if (!$this->acl_check_new(
				'Private_phone_contacts_Controller',
				'contacts', $user->member_id
			))
		{
			Controller::error(ACCESS);
		}

		$form = new Forge(url::base(TRUE) . url::current(true));
		
		$form->input('description')
				->rules('required');
		
		$form->submit('Save');
		
		$form->hidden('redirect');

		if ($form->validate())
		{
			$contact = new Contact_Model();
			$country = new Country_Model();
			// gets number without country prefix
			$country_id = $country->find_phone_country_id($number);
			
			if ($country_id > 0)
			{
				$country = $country->find($country_id);
				$number = substr($number, strlen($country->country_code));
			}
			// is contact already in database?
			$contact_id = $contact->find_contact_id(Contact_Model::TYPE_PHONE, $number);
			
			if ($contact_id == 0)
			{
				$contact->type = Contact_Model::TYPE_PHONE;
				$contact->value = $number;
				if (!$contact->save())
				{
					status::error('Error - cant add contacts');
					url::redirect($_POST['redirect']);
				}
				// add country of phone number
				if ($country != NULL)
				{
					$contact->add($country);
					
					if (!$contact->save())
					{
						$contact->delete();
						status::error('Error - cant add country contact');
						url::redirect($_POST['redirect']);
					}
				}
				$contact_id = $contact->id;
			}
			// add relation M:N between users and contacts
			$private_user_contact = new Private_users_contact_Model();
			$private_user_contact->contact_id = $contact_id;
			$private_user_contact->user_id = $user_id;
			$private_user_contact->description = htmlspecialchars($form->description->value);
			
			if (!$private_user_contact->save())
			{
				status::error('Error - cant add private phone contact');
			}
			else
			{
				status::success('Private phone contact has been added');
			}

			url::redirect($_POST['redirect']);
		}
		else
		{
			$form->redirect->value(url_lang::previous());
		}
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/' . $user->member->id,
						'ID ' . $user->member->id . ' - ' . $user->member->name,
						$this->acl_check_view('Members_Controller', 'members', $user->member->id))
				->enable_translation()
				->link('users/show_by_member/' . $user->member_id, 'Users',
						$this->acl_check_view('Users_Controller', 'users', $user->member_id))
				->disable_translation()
				->link('users/show/' . $user->id, 
						$user->name . ' ' . $user->surname . ' (' . $user->login . ')',
						$this->acl_check_view('Users_Controller', 'users', $user->member_id))
				->enable_translation()
				->link('contacts/show_by_user/'.$user_id, 'Private contacts',
						$this->acl_check_view('Users_Controller', 'additional_contacts', $user->member_id))
				->text('Add new');

		// view
		$view = new View('main');
		$view->title = __('Add private phone number contact');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('users/private_phone_contacts');
		$view->content->title = __('Add private phone number contact');
		$view->content->form = $form->html();
		$view->content->number = $number;
		$view->render(TRUE);
	}

	/**
	 * Edits private phone contact
	 * @param integer $contact_id
	 */
	public function edit($contact_id = 0)
	{
		if (!is_numeric($contact_id) || $contact_id <= 0)
		{
			Controller::warning(PARAMETER);
		}

		$pucontact = new Private_users_contact_Model($contact_id);

		if (!$pucontact->id)
		{
			Controller::error(RECORD);
		}
		
		$user = $pucontact->user;

		if (!$this->acl_check_edit('Private_phone_contacts_Controller', 'contacts', $user->member_id))
		{
			Controller::error(ACCESS);
		}

		$form = new Forge(url::base(TRUE) . url::current(true));
		
		$form->input('description')
				->rules('required')
				->value($pucontact->description);
		
		$form->submit('Save');
		
		$form->hidden('redirect');

		if ($form->validate())
		{
			$pucontact->description = $form->description->value;
			if (!$pucontact->save())
			{
				status::error('Error - cant update private phone contacts');
			}
			else
			{
				status::success('Private phone contact has been updated');
				url::redirect($_POST['redirect']);
			}
		}
		else
		{
			$form->redirect->value(url_lang::previous());
		}
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/' . $user->member->id,
						'ID ' . $user->member->id . ' - ' . $user->member->name,
						$this->acl_check_view('Members_Controller', 'members', $user->member->id))
				->enable_translation()
				->link('users/show_by_member/' . $user->member_id, 'Users',
						$this->acl_check_view('Users_Controller', 'users', $user->member_id))
				->disable_translation()
				->link('users/show/' . $user->id, 
						$user->name . ' ' . $user->surname . ' (' . $user->login . ')',
						$this->acl_check_view('Users_Controller', 'users', $user->member_id))
				->enable_translation()
				->link('contacts/show_by_user/'.$user->id, 'Private contacts',
						$this->acl_check_view('Users_Controller', 'additional_contacts', $user->member_id))
				->link('contacts/show/'.$contact_id,
						$pucontact->description . ' (' . $pucontact->id . ')',
						$this->acl_check_view('Users_Controller', 'additional_contacts', $user->member_id))
				->text('Edit');

		// view
		$view = new View('main');
		$view->title = __('Edit private phone number contact');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('users/private_phone_contacts');
		$view->content->title = __('Edit private phone number contact');
		$view->content->form = $form->html();
		$view->content->number = $pucontact->contact->get_phone_prefix() . $pucontact->contact->value;
		$view->render(TRUE);
	}

	/**
	 * Deletes private phone contact
	 * @param integer $pp_contact_id
	 */
	public function delete($pp_contact_id = 0)
	{
		if (!is_numeric($pp_contact_id) || $pp_contact_id <= 0)
		{
			Controller::warning(PARAMETER);
		}

		$pucontact = new Private_users_contact_Model($pp_contact_id);

		if (!$pucontact->id)
		{
			Controller::error(RECORD);
		}

		if (!$this->acl_check_delete(
				'Private_phone_contacts_Controller', 'contacts',
				$pucontact->user->member_id
			))
		{
			Controller::error(ACCESS);
		}

		$isdeleted = TRUE;

		// if there is no another relation between contact and users
		// delete contact, else delete just relation
		if ($pucontact->contact->count_all_relation() == 1)
		{
			$isdeleted = $pucontact->contact->delete();
		}
		else
		{
			$isdeleted = $pucontact->delete();
		}

		if (!$isdeleted)
		{
			status::error('Error - cant delete private phone contacts');
		}
		else
		{
			status::success('Private phone contact has been deleted');
		}

		url::redirect(url::base() . url::previous());
	}

	/**
	 * Cache for connection between user and contact
	 * @param Private_users_contact_Model $model
	 * @param integer $user_id
	 * @param string  $number  Phone number to find
	 * @return integer  Contact ID or zero
	 */
	public static function private_contacts_cache($user_id, $number)
	{
		static $contact_number_cache = array();
		static $model = null;

		if ($model == null)
		{
			$model = new Private_users_contact_Model();
		}

		if (!isset($contact_number_cache[$user_id][$number]))
		{
			$contact_number_cache[$user_id][$number] = $model->get_contact_id($user_id, $number);
		}
		
		return $contact_number_cache[$user_id][$number];
	}

}
