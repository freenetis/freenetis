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
 * Controller performs user registration by him self.
 * 
 * @package Controller
 */
class Registration_Controller extends Controller
{

	/**
	 * Function to self-registration of candidates about membership (applicants
	 * 
	 * @author Michal Kliment, Jiri Svitak, Ondřej Fibich
	 */
	public function index()
	{
		// if self-registration is not allow, redirect to login page
		if (!$this->settings->get('self_registration') || $this->session->get('user_id', 0))
		{
			url::redirect('login');
		}
		
		// countries
		$country_model = new Country_Model();
		$arr_countries = $country_model->where('enabled', 1)->select_list('id', 'country_name');
		
		// streets
		$arr_streets = array
		(
			NULL => '--- ' . __('Without street') . ' ---'
		) + ORM::factory('street')->select_list('id', 'street');
		
		// towns with zip code and quarter
		$arr_towns = array
		(
			NULL => '--- ' . __('Select town') . ' ---'
		) + ORM::factory('town')->select_list_with_quater();
		
		// list for phone prefixes
		$phone_prefixes = $country_model->select_country_code_list();

		// registration form
		$form = new Forge('registration');
		
		$form->group('Login data');
		
		$form->input('login')
				->label('Username')
				->help(help::hint('login_name'))
				->rules('required|length[5,20]')
				->callback(array($this, 'valid_username'));
		
		$pass_min_len = Settings::get('security_password_length');
		
		$form->password('password')
				->help(help::hint('password'))
				->rules('required|length['.$pass_min_len.',50]')
				->class('main_password');
		
		$form->password('confirm_password')
				->rules('required|length['.$pass_min_len.',50]')
				->matches($form->password);
		
		$form->group('Basic information');
		
		$form->input('title1')
				->label('Pre title')
				->rules('length[3,40]');
		
		$form->input('name')
				->rules('required|length[3,30]');
		
		$form->input('middle_name')
				->rules('length[3,30]');
		
		$form->input('surname')
				->rules('required|length[3,60]');
		
		$form->input('title2')
				->label('Post title')
				->rules('length[3,30]');
		
		if (!Settings::get('users_birthday_empty_enabled'))
		{
			$form->date('birthday')
					->label('Birthday')
					->years(date('Y')-100, date('Y'))
					->rules('required');
		}
		else
		{
			$form->date('birthday')
					->label('Birthday')
					->years(date('Y')-100, date('Y'))
					->value('');
		}
		
		$legalp_group = $form->group('Legal person innformation')->visible(FALSE);
		
		$legalp_group->input('membername')
				->label('Name of organization')
				->rules('length[1,60]');
		
		$legalp_group->input('organization_identifier')
				->label('Organization identifier')
				->rules('length[3,20]');

		$form->group('Address');
		
		$form->dropdown('country_id')
				->label('Country')
				->rules('required')
				->options($arr_countries)
				->selected(Settings::get('default_country'))
				->style('width:200px');
		
		$address_point_server_active = Address_points_Controller::is_address_point_server_active();
		
		// If address database application is set show new form
		if ($address_point_server_active)
		{	
			$form->input('town')
				->label(__('Town').' - '.__('District'))
				->rules('required')
				->class('join1');
			
			$form->input('district')
				->class('join2')
				->rules('required');

			$form->input('street')
				->label('Street')
				->rules('required');
						
			$form->input('zip')
				->label('Zip code')
				->rules('required');
		}
		else
		{
			$form->dropdown('town_id')
				->label('Town')
				->rules('required')
				->options($arr_towns)
				->style('width:200px');
		
			$form->dropdown('street_id')
					->label('Street')
					->options($arr_streets)
					->style('width:200px');

			$form->input('street_number')
					->rules('length[1,50]');
		}
		
		$form->input('gpsx')
				->label(__('GPS').'&nbsp;X:')
				->help(help::hint('gps_coordinates'))
				->rules('gps');
		
		$form->input('gpsy')
				->label(__('GPS').'&nbsp;Y:')
				->help(help::hint('gps_coordinates'))
				->rules('gps');
		
		$form->group('Contact information');
		
		$form->dropdown('phone_prefix')
				->label('Phone')
				->rules('required')
				->options($phone_prefixes)
				->selected(Settings::get('default_country'))
				->class('join1')
				->style('width:70px');
		
		$form->input('phone')
				->rules('required|length[9,40]')
				->callback(array($this, 'valid_phone'))
				->class('join2')
				->style('width:180px');
		
		$form->input('email')
				->rules('required|length[3,100]|valid_email')
				->callback(array($this, 'valid_email'))
				->style('width:250px');
		
		$form->group('Additional information');
		
		$form->textarea('comment')
				->class('comment_ta');

		$form->submit('Register');
		

		// posted form
		if ($form->validate())
		{
			$user = new User_Model;
			
			$form_data = $form->as_array();
			
			$match = array();
			
			// validate address
			if ($address_point_server_active &&
				(
					!Address_points_Controller::is_address_point_valid(
						$form_data['country_id'],
						$form_data['town'],
						$form_data['district'],
						$form_data['street'],
						$form_data['zip']
					) ||
					!preg_match('((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', $form_data['street'], $match)
				))
			{
				$form->street->add_error('required', __('Invalid address point.'));
			}
			else
			{
				if ($address_point_server_active)
				{
					$street = trim(preg_replace(' ((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', '', $form_data['street']));
					
					$number = $match[0];
				}
				
				try
				{
					// start transaction
					$user->transaction_start();

					$form_data = $form->as_array();

					// gps
					$gpsx = NULL;
					$gpsy = NULL;

					if (!empty($form_data['gpsx']) && !empty($form_data['gpsy']))
					{
						$gpsx = doubleval($form_data['gpsx']);
						$gpsy = doubleval($form_data['gpsy']);

						if (gps::is_valid_degrees_coordinate($form_data['gpsx']))
						{
							$gpsx = gps::degrees2real($form_data['gpsx']);
						}

						if (gps::is_valid_degrees_coordinate($form_data['gpsy']))
						{
							$gpsy = gps::degrees2real($form_data['gpsy']);
						}
					}

					$member = new Member_Model;
					$enum_type = new Enum_type_Model();

					$user->login = $form_data['login'];
					$user->password = sha1($form_data['password']);
					$user->name = $form_data['name'];
					$user->middle_name = $form_data['middle_name'];
					$user->surname = $form_data['surname'];
					$user->pre_title = $form_data['title1'];
					$user->post_title = $form_data['title2'];
					$user->type = User_Model::MAIN_USER;

					if (empty($form_data['birthday']))
					{
						$user->birthday	= NULL;
					}
					else
					{
						$user->birthday	= date("Y-m-d", $form_data['birthday']);
					}

					// entrance fee
					$fee_model = new Fee_Model();
					$fee = $fee_model->get_by_date_type(date('Y-m-d'), 'entrance fee');

					if (is_object($fee) && $fee->id)
					{
						$entrance_fee = $fee->fee;
					}
					else
					{
						$entrance_fee = 0;
					}

					$address_point_model = new Address_point_Model();

					if ($address_point_server_active)
					{
						$t = new Town_Model();
						$s = new Street_Model();
						$ap = new Address_point_Model();
						$district = $form_data['district'];
						
						if ($form_data['town'] == $form_data['district'])
						{
							$district = '';
						}
						
						$t_id = $t->get_town($form_data['zip'], $form_data['town'], $district)->id;
						$s_id = $s->get_street($street, $t_id)->id;
						
						$address_point = $ap->get_address_point($form_data['country_id'], $t_id, $s_id, $number);
					}
					else
					{
						$address_point = $address_point_model->get_address_point(
							$form_data['country_id'], $form_data['town_id'],
							$form_data['street_id'], $form_data['street_number'],
							$gpsx, $gpsy
						);
					}

					// address point doesn't exist exist, create it
					if (!$address_point->id)
					{
						$address_point->save_throwable();
					}

					// add GPS
					if (!empty($gpsx) && !empty($gpsy))
					{ // save
						$address_point->update_gps_coordinates(
								$address_point->id, $gpsx, $gpsy
						);
					}
					else
					{ // delete gps
						$address_point->gps = NULL;
						$address_point->save_throwable();
					}

					// speed class
					$speed_class_model = new Speed_class_Model();
					$default_speed_class = $speed_class_model->get_applicants_default_class();

					if ($default_speed_class)
					{
						$member->speed_class_id = $default_speed_class->id;
					}

					$member->name = ($form_data['membername'] != '') ?
							$form_data['membername'] : $user->name . ' ' . $user->surname;
					$member->address_point_id = $address_point->id;
					$member->type = Member_Model::TYPE_APPLICANT;
					$member->organization_identifier = $form_data['organization_identifier'];
					$member->entrance_fee = $entrance_fee;
					$member->applicant_registration_datetime = date('Y-m-d H:i:s');
					$member->comment = $form_data['comment'];
					$member->save_throwable();

					$user->member_id = $member->id;
					$user->save_throwable();

					$member->user_id = $user->id;
					$member->save_throwable();

					// telephone
					$contact = new Contact_Model();
					$contact->type = Contact_Model::TYPE_PHONE;
					$contact->value = $form_data['phone'];
					$contact->save_throwable();
					$contact->add($user);
					$contact->save_throwable();
					$phone_country = new Country_Model($form_data['phone_prefix']);
					$contact->add($phone_country);
					$contact->save_throwable();
					$contact->clear();
					// email
					$contact->type = Contact_Model::TYPE_EMAIL;
					$contact->value = $form_data['email'];
					$contact->save_throwable();
					$contact->add($user);
					$contact->save_throwable();

					// account
					$account = new Account_Model();
					$account->member_id = $member->id;
					$account->account_attribute_id = Account_attribute_Model::CREDIT;
					if ($form_data['membername'] == '')
						$account->name = $form_data['surname'] . ' ' . $form_data['name'];
					else
						$account->name = $form_data['membername'];
					$account->save_throwable();

					// save allowed subnets count of member
					$allowed_subnets_count = new Allowed_subnets_count_Model();
					$allowed_subnets_count->member_id = $member->id;
					$allowed_subnets_count->count = Settings::get('allowed_subnets_default_count');
					$allowed_subnets_count->save_throwable();

					// access rights of expectant for membership (wannabe - aro group 23)
					$groups_aro_map = new Groups_aro_map_Model();
					$groups_aro_map->aro_id = $user->id;
					$groups_aro_map->group_id = Aro_group_Model::REGISTERED_APPLICANTS;
					$groups_aro_map->save_throwable();

					// commit transaction
					$user->transaction_commit();

					url::redirect('registration/complete');
				}
				catch (Exception $ex)
				{
					$user->transaction_rollback();
					status::error('Cannot complete registration.', $ex);
					Log::add_exception($ex);
				}
			}
		}

		$view = new View('registration/index');
		$view->title = __('Registration form');
		$view->form = $form->html();
		$view->render(TRUE);
	}
	
	/**
	 * Info about registration after correct sending
	 * 
	 * @author Ondřej Fibich
	 */
	public function complete()
	{
		$view = new View('registration/done');
		$view->render(TRUE);
	}
	
	/**
	 * Check if username is valid
	 *
	 * @param string $input 
	 */
	public static function valid_username($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$user_model = new User_Model();
		$username_regex = Settings::get('username_regex');
		
		if ($user_model->username_exist($input->value) && !trim($input->value) == '')
		{
			$input->add_error('required', __('Username already exists in database'));
		}
		else if (preg_match($username_regex, $input->value) == 0)
		{
			$input->add_error(
					'required', __('Login must contains only a-z and 0-9 and starts with literal.')
			);
		}
	}

	/**
	 * Check if phone is valis
	 *
	 * @param string $input 
	 */
	public static function valid_phone($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$user_model = new User_Model();
		
		if ($user_model->phone_exist($input->value) && !trim($input->value) == '')
		{
			$input->add_error('required', __('Phone already exists in database.'));
		}
	}

	/**
	 * Check if email is valis
	 *
	 * @param string $input 
	 */
	public static function valid_email($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$user_model = new User_Model();
		if ($user_model->email_exist($input->value) && !trim($input->value) == '')
		{
			$input->add_error('required', __('Email already exists in database.'));
		}
	}
}
