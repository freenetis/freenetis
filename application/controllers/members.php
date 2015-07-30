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
 * Controller performs members actions such as viewing, editing profile,
 * registration export, applicants approval, etc.
 * 
 * @package Controller
 */
class Members_Controller extends Controller
{
	/** @var integer $_member_id	Member ID for callbacks */
	protected $_member_id = false;

	/**
	 * Function redirects default member address to show_all function.
	 * 
	 * @return unknown_type
	 */
	public function index()
	{
		url::redirect('members/show_all');
	}

	/**
	 * Function shows list of all members.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 40, $order_by = 'id',
			$order_by_direction = 'ASC', $page_word = 'page', $page = 1,
			$regs = 0)
	{
		
		// access rights
		if (!$this->acl_check_view(get_class($this), 'members'))
			Controller::error(ACCESS);

		$filter_form = Members_Controller::create_filter_form();

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'registration', 'name', 'street','redirect',  'street_number',
			'town', 'quarter', 'ZIP_code', 'qos_ceil', 'qos_rate', 'entrance_fee',
			'debt_payment_rate', 'current_credit', 'entrance_date', 'comment',
			'balance', 'type_name', 'redirect', 'whitelisted'
		);
		
		// order by check
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		// load members
		$model_members = new Member_Model();
		$total_members = $model_members->count_all_members($filter_form->as_sql());

		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_members)
			$sql_offset = 0;

		// query data
		$query = $model_members->get_all_members(
				$sql_offset, $limit_results, $order_by, $order_by_direction,
				$filter_form->as_sql()
		);

		// headline
		$headline = __('List of all members');
		// path to form
		$path = Config::get('lang') . '/members/show_all/' . $limit_results . '/'
				. $order_by . '/' . $order_by_direction.'/'.$page_word.'/'
				. $page.'/'.$regs;

		// it creates grid to view all members
		$grid = new Grid('members', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 40,
			'selector_min' 				=> 40,
			'selector_max_multiplier'   => 25,
			'base_url'					=> $path,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_members,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));

		// grid buttons
		if ($this->acl_check_new(get_class($this), 'members'))
		{
			$grid->add_new_button('members/add', 'Add new member', array
			(
				'title' => __('Add new member'),
			));
		}

		if ($this->acl_check_edit('Members_Controller', 'registration'))
		{
			if (!$regs)
			{
				$grid->add_new_button(
					'members/show_all/'.$limit_results . 
					'/'.$order_by.'/'.$order_by_direction.
					'/'.$page_word.'/'.$page.'/1'.server::query_string(),
					'Edit registrations'
				);
			}
			else
			{
				$grid->add_new_button(
					'members/show_all/'.$limit_results . 
					'/'.$order_by.'/'.$order_by_direction.
					'/'.$page_word.'/'.$page.'/0'.server::query_string(),
					'End editing of registrations'
				);
			}
		}

		if ($this->acl_check_view(get_class($this), 'members'))
		{
			// csv export of members
			$grid->add_new_button(
					'export/csv/members' . server::query_string(),
					'Export to CSV', array
					(
						'title' => __('Export to CSV'),
						'class' => 'popup_link'
					)
			);

			$grid->add_new_button(
					'notifications/members/' . server::query_string(),
					'Notifications'
			);
		}
		// database columns - some are commented out because of lack of space

		$grid->order_field('id')
				->label('ID');

		if ($regs)
		{
			$grid->order_form_field('registrations')
				->type('checkbox')
				->label('Reg')
				->class('center');

			$grid->form_extra_buttons = array
			(
				form::hidden(
					'url', url_lang::current().server::query_string()
				)
			);
		}
		else
		{
			$grid->order_callback_field('registration')
				->label('Reg')
				->class('center')
				->callback('callback::registration_field');
		}

		$grid->order_field('type');

		$grid->order_field('name');

		$grid->order_field('street');

		$grid->order_field('street_number');

		$grid->order_field('town');

		$grid->order_callback_field('balance')
				->callback('callback::balance_field');

		$grid->order_callback_field('redirect')
				->label('Redirection')
				->callback('callback::redirect_field');

		$grid->order_callback_field('whitelisted')
				->label('Whitelist')
				->callback('callback::whitelisted_field');

		$actions = $grid->grouped_action_field();

		// action fields
		if ($this->acl_check_view(get_class($this), 'members'))
		{
			$actions->add_action('id')
					->icon_action('member')
					->url('members/show')
					->label('Show member');
		}

		if ($this->acl_check_edit(get_class($this), 'members'))
		{
			$actions->add_action('aid')
					->icon_action('money')
					->url('transfers/show_by_account')
					->label('Show transfers');
		}

		// load data
		$grid->datasource($query);
		
		if (isset($_POST) && count ($_POST))
		{
			$ids = $_POST["ids"];
			$regs = $_POST["registrations"];
			
			ORM::factory('member')->update_member_registrations($ids, $regs);
			
			status::success('Registrations has been successfully updated.');
			
			url::redirect($_POST['url']);
		}
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = __('Members');
		$view->content = new View('show_all');
		$view->content->table = $grid;
		$view->content->headline = $headline;
		$view->render(TRUE);
	} // end of show_all function

	/**
	 * Function shows list of all registered applicants.
	 * 
	 * @author Ondřej Fibich
	 */
	public function applicants()
	{
		// access rights
		if (!$this->acl_check_view(get_class($this),'members'))
			Controller::error(ACCESS);

		// query
		$model_members = new Member_Model();
		$query = $model_members->get_registered_members();		

		// grid
		$grid = new Grid(url::base(TRUE) . url::current(true), null, array
		(
			'use_paginator' => false,
			'use_selector' => false
		));
		
		// database columns - some are commented out because of lack of space
		$grid->field('id')
				->label('ID');
		
		$grid->field('name');
		
		$grid->field('street');
		
		$grid->field('street_number');
		
		$grid->field('town');
		
		$grid->field('applicant_registration_datetime')
				->label('Registration time');
		
		$grid->field('comment');
		
		$actions = $grid->grouped_action_field();
		
		// action fields
		if ($this->acl_check_view(get_class($this), 'members'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('members/show');
		}
		
		if ($this->acl_check_edit(get_class($this), 'members'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('members/edit');
		}
		
		if ($this->acl_check_delete(get_class($this), 'members'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('members/delete_applicant')
					->class('delete_link');
		}
		
		// source
		$grid->datasource($query);
		
		// headline
		$headline = __('Registered applicants');
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view(get_class($this),'members'))
				->disable_translation()
				->text($headline);

		// description
		$desc = '<br>' . __(
				'Registered applicants can be approved in edit form by changing their type'
		) . '.<br>'. __(
				'Delete applicants for refusing of their request'
		) . '.';		
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('show_all');
		$view->content->description = $desc;
		$view->content->table = $grid;
		$view->content->headline = $headline;
		$view->render(TRUE);
	} // end of registered function
	
	/**
	 * Deletes registered applicants
	 *
	 * @author Ondřej Fibich
	 * @param integer $member_id 
	 */
	public function delete_applicant($member_id = NULL)
	{
		// parameter is wrong
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// member doesn't exist
		if (!$member->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_delete(get_class($this), 'members'))
			Controller::error(ACCESS);
		
		// delete is enabled only on applicants
		if ($member->type != Member_Model::TYPE_APPLICANT)
			Controller::warning(PARAMETER);
		
		// send email with details
		$contact = new Contact_Model();
		$emails = $contact->find_all_users_contacts($member->user->id, Contact_Model::TYPE_EMAIL);

		if ($emails && $emails->count())
		{
			$to = $emails->current()->value;
			$from = Settings::get('email_default_email');
			$subject = 'Registration deny';
			$message = 'Your registration to FreenetIS has been denied';

			try
			{
				email::send($to, $from, $subject, $message);
			}
			catch (Exception $e)
			{
				status::error(
						__('Error - cannot send email to applicant about deny of membership')
						. '<br>' . __('Error') . ': ' . $e->getMessage(),
						FALSE
				);
			}
		}
		
		// delete user
		foreach ($member->users as $user)
		{
			$user->delete_depends_items($user->id);
			$user->delete();
		}
		
		// delete account
		$member->delete_accounts($member->id);
		
		// delete member
		$member->delete();
		
		// redirection to registered applicants
		url::redirect('members/applicants');
	}

	/**
	 * Shows details of member.
	 * 
	 * @param integer $member_id id of member to show
	 * @param string $order_by sorting column
	 * @param string $order_by_direction sorting direction
	 */
	public function show(
			$member_id = NULL, $limit_results = 20, $order_by = 'ip_address',
			$order_by_direction = 'ASC', $page_word = null, $page = 1)
	{
		// parameter is wrong
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);

		$this->member = $member = new Member_Model($member_id);

		// member doesn't exist
		if (!$member->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view(get_class($this), 'members', $member->id))
			Controller::error(ACCESS);

		// finds main user of member
		$user = ORM::factory('user')->where(array
		(
			'member_id' => $member->id,
			'type' => User_Model::MAIN_USER
		))->find();

		// building of user's name
		$user_name = $user->name;
		
		if ($user->middle_name != '')
		{
			$user_name .= ' '.$user->middle_name;
		}
		
		$user_name .= ' '.$user->surname;
		
		if ($user->pre_title != '')
		{
			$user_name = $user->pre_title . ' ' .$user_name;
		}
		
		if ($user->post_title != '')
		{
			$user_name .= ' '.$user->post_title;
		}
		
		// translates member's type
		$type = ORM::factory('enum_type')->get_value($member->type);

		// has member active membership interrupt?
		$active_interrupt = ORM::factory('membership_interrupt')
				->has_member_interrupt_in_date($member->id, date('Y-m-d'));

		$title = ($active_interrupt) ? $type . ' '.$member->name
				. ' ('. __('I') .')' : $type . ' '.$member->name;

		// finds credit account of member
		if ($member->id != 1)
		{
			$account = ORM::factory('account')->where(array
			(
				'member_id' => $member_id,
				'account_attribute_id' => Account_attribute_Model::CREDIT
			))->find();
		}
		
		// gps coordinates
		$gps = '';

		// finds address of member
		if ($member->address_point_id &&
			$member->address_point->id)
		{
			$address = '';
			
			if ($member->address_point->street_id &&
				$member->address_point->street->id)
			{
				$address = $member->address_point->street->street;
			}
			
			if ($member->address_point->street_number)
			{
				$address .= ' '.$member->address_point->street_number;
			}

			if ($member->address_point->town_id &&
				$member->address_point->town->id)
			{
				$town = $member->address_point->town->town;
				
				if ($member->address_point->town->quarter)
				{
					$town .= '-'.$member->address_point->town->quarter;
				}
				
				$town .= ', '.$member->address_point->town->zip_code;
			}

			if ($member->address_point->country_id &&
				$member->address_point->country->id)
			{
				$country = $member->address_point->country->country_name;
			}
			
			// gps coordinates
			if (!empty($member->address_point->gps))
			{
				$gps_result = ORM::factory('address_point')->get_gps_coordinates(
						$member->address_point->id
				);

				if (! empty($gps_result))
				{
					$gps = gps::degrees($gps_result->gpsx, $gps_result->gpsy, true);
				}
			}
		}
		
		// query for GMaps
		if (empty($gps))
		{
			$map_query = $address . ', ' .$town;
		}
		else
		{
			$map_query = $gps_result->gpsx . ', ' . $gps_result->gpsy;
		}
		
		// gps domicile coordinates
		$domicile_gps = '';
		
		if ($member->members_domicile->address_point->id)
		{
			$domicile_address = '';
			
			if ($member->members_domicile->address_point->street_id &&
				$member->members_domicile->address_point->street->id)
			{
				$domicile_address = $member->members_domicile->address_point->street->street;
			}
			
			if ($member->members_domicile->address_point->street_number)
			{
				$domicile_address .= ' '.$member->members_domicile->address_point->street_number;
			}

			if ($member->members_domicile->address_point->town_id &&
				$member->members_domicile->address_point->town->id)
			{
				$domicile_town = $member->members_domicile->address_point->town->town;
				
				if ($member->members_domicile->address_point->town->quarter)
				{
					$domicile_town .= '-'.$member->members_domicile->address_point->town->quarter;
				}
				
				$domicile_town .= ', '.$member->members_domicile->address_point->town->zip_code;
			}		

			if ($member->members_domicile->address_point->country_id &&
				$member->members_domicile->address_point->country->id)
			{
				$domicile_country = $member->members_domicile->address_point->country->country_name;
			}
			
			// gps coordinates
			if (!empty($member->members_domicile->address_point->gps))
			{
				$gps_result = ORM::factory('address_point')->get_gps_coordinates(
						$member->members_domicile->address_point->id
				);

				if (! empty($gps_result))
				{
					$domicile_gps = gps::degrees($gps_result->gpsx, $gps_result->gpsy, true);
				}
			}
			
			// query for GMaps domicile
			if (empty($domicile_gps))
			{
				$map_d_query = $domicile_address . ', ' .$domicile_town;
			}
			else
			{
				$map_d_query = $gps_result->gpsx . ', ' . $gps_result->gpsy;
			}
		}
		
		/********              VoIP         ***********/

		// VoIP SIP model
		$voip_sip = new Voip_sip_Model();
		// Gets sips
		$voip = $voip_sip->get_all_record_by_member_limited($member->id);
		// Has driver?
		$has_driver = Billing::instance()->has_driver();
		// Account
		$b_account = null;
		// Check account only if have SIP
		if ($voip->count())
		{
			$b_account = Billing::instance()->get_account($member->id);
		}

		$voip_grid = new Grid('members', null, array
		(
			'separator'		   		=> '<br /><br />',
			'use_paginator'	   		=> false,
			'use_selector'	   		=> false
		));

		$voip_grid->field('id')
				->label('ID');
		
		$voip_grid->field('callerid')
				->label(__('Number'));
		
		$actions = $voip_grid->grouped_action_field();
		
		$actions->add_action('user_id')
				->icon_action('phone')
				->url('voip/show')
				->label('Show VoIP account');
		
		$actions->add_action('user_id')
				->icon_action('member')
				->url('users/show')
				->label('Show user who own this VoIP account');
		
		$voip_grid->datasource($voip);

		if ($has_driver && $b_account)
		{
			$voip_grid->add_new_button(
					'voip_calls/show_by_member/'.$member->id,
					__('List of all calls')
			);
			
			if ($member->id != 1)
			{
				$voip_grid->add_new_button(
						'transfers/add_voip/'.$account->id,
						__('Recharge VoIP credit')
				);
			}
		}
		
		// finds date of expiration of member fee
		$expiration_date = (isset($account)) ? self::get_expiration_date($account) : '';

		// finds total traffic of member
		if (Settings::get('ulogd_enabled'))
		{
			$mtm = new Members_traffic_Model();
			$total_traffic = $mtm->get_total_member_traffic($member->id);
			$today_traffic = $mtm->get_today_member_traffic($member->id);
			$month_traffic = $mtm->get_month_member_traffic($member->id);
		}

		// finds all contacts of main user
		$contact_model = new Contact_Model();
		$enum_type_model = new Enum_type_Model();

		$variable_symbol_model = new Variable_Symbol_Model();

		// contacts of main user of member
		$contacts = $contact_model->find_all_users_contacts($user->id);
		
		$variable_symbols = 0;
		if ($member_id != 1)
		{
		    $variable_symbols = $variable_symbol_model->find_account_variable_symbols($account->id);
		}

		$contact_types = array();
		foreach($contacts as $i => $contact)
			$contact_types[$i] = $enum_type_model->get_value($contact->type);

		// finds all users of member
		$users = ORM::factory('user')->where('member_id', $member->id)->find_all();

		// grid with lis of users
		$users_grid = new Grid(url_lang::base().'members', null, array
		(
			'separator'		   		=> '<br /><br />',
			'use_paginator'	   		=> false,
			'use_selector'	   		=> false,
		));

		if ($this->acl_check_new('Users_Controller','users') ||
			($this->session->get('user_type') == User_Model::MAIN_USER &&
			 $this->acl_check_new('Users_Controller', 'users', $member->id)))
		{
			$users_grid->add_new_button('users/add/'.$member->id, __('Add new user'));
		}
		
		$users_grid->field('id')
				->label('ID');
		
		$users_grid->field('name');
		
		$users_grid->field('surname');
		
		$users_grid->field('login')
				->label('Username');

		$actions = $users_grid->grouped_action_field();
		
		if($this->acl_check_view('Users_Controller', 'users', $member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('users/show');
		}

		if ($this->acl_check_edit('Users_Controller', 'users') ||
			($this->session->get('user_type') == User_Model::MAIN_USER &&
			 $this->acl_check_edit('Users_Controller','users',$member_id)))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('users/edit');
		}

		if ($this->acl_check_delete('Users_Controller', 'users', $member_id))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('users/delete')
					->class('delete_link');
		}
			
		if ($this->acl_check_view('Devices_Controller', 'devices', $member_id))
		{
			$actions->add_action('id')
					->icon_action('devices')
					->url('devices/show_by_user')
					->label('Show devices');
		}

		if ($this->acl_check_edit('Users_Controller', 'work', $member_id))
		{
			$actions->add_action('id')
					->icon_action('work')
					->url('works/show_by_user')
					->label('Show works');
		}

		$users_grid->datasource($users);

		// membership interrupts
		$membership_interrupts = ORM::factory('membership_interrupt')->get_all_by_member($member_id);
		
		$membership_interrupts_grid = new Grid('members', null, array
		(
			'separator'		   		=> '<br /><br />',
			'use_paginator'	   		=> false,
			'use_selector'	   		=> false,
		));

		if ($this->acl_check_new(get_class($this), 'membership_interrupts', $member_id))
		{
			$membership_interrupts_grid->add_new_button(
					'membership_interrupts/add/'.$member_id,
					__('Add new interrupt of membership'),
					array
					(
						'title' => __('Add new interrupt of membership'),
						'class' => 'popup_link'
					)
			);
		}

		$membership_interrupts_grid->field('id')
				->label('ID');
		
		$membership_interrupts_grid->field('from')
				->label(__('Date from'));
		
		$membership_interrupts_grid->field('to')
				->label(__('Date to'));
		
		$membership_interrupts_grid->field('comment');
		
		$actions = $membership_interrupts_grid->grouped_action_field();

		if ($this->acl_check_edit(get_class($this), 'membership_interrupts', $member_id))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('membership_interrupts/edit')
					->class('popup_link');
		}

		if ($this->acl_check_delete(get_class($this), 'membership_interrupts'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('membership_interrupts/delete')
					->class('delete_link');
		}
		
		$membership_interrupts_grid->datasource($membership_interrupts);

		// activated redirections of member, including short statistic of whitelisted IP addresses
		
		$ip_model = new Ip_address_Model();
			
		$total_ips = $ip_model->count_ips_and_redirections_of_member($member_id);
		
		// get new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_ips)
			$sql_offset = 0;
		
		$ip_addresses = $ip_model->get_ips_and_redirections_of_member(
				$member_id, $sql_offset, $limit_results,
				$order_by, $order_by_direction
		);
		
		$redir_grid = new Grid('members', null, array
		(
			'selector_increace'			=> 20,
			'selector_min'				=> 20,
			'selector_max_multiplier'	=> 10,
			'current'					=> $limit_results,
			'base_url'					=> Config::get('lang'). '/members/show/' . $member_id . '/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_ips,
			'items_per_page' 			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'variables'					=> $member_id . '/',
			'url_array_ofset'			=> 1,
			'query_string'				=> $this->input->get(),
		));
		
		if ($this->acl_check_new('Messages_Controller', 'member') &&
			$total_ips < 1000) // limited count
		{
			$redir_grid->add_new_button(
					'redirect/activate_to_member/'.$member_id,
					__('Activate redirection to member'), array(),
					help::hint('activate_redirection_to_member')
			);
		}
		
		$redir_grid->order_callback_field('ip_address')
				->label(__('IP address'))
				->callback('callback::ip_address_field');
		
		$redir_grid->order_callback_field('whitelisted')
				->label(__('Whitelist').'&nbsp;'.help::hint('whitelist'))
				->callback('callback::whitelisted_field');
		
		$redir_grid->order_callback_field('message')
				->label(__('Activated redirection').'&nbsp;'.help::hint('activated_redirection'))
				->callback('callback::message_field');
		
		$redir_grid->callback_field('ip_address')
				->label(__('Preview').'&nbsp;'.help::hint('redirection_preview'))
				->callback('callback::redirection_preview_field');
		
		if ($this->acl_check_delete('Messages_Controller', 'ip_address'))
		{
			$redir_grid->callback_field('redirection')
					->label(__('Canceling of message for redirection'))
					->callback('callback::cancel_redirection_of_member');
		}
		
		$redir_grid->datasource($ip_addresses);
		
		/********** BUILDING OF LINKS   *************/

		$member_links = array();
		$user_links = array();

		$former_type_id = ORM::factory('enum_type')->get_type_id('Former member');

		// member edit link
		if ($member->type != $former_type_id &&
			$this->acl_check_edit(get_class($this), 'members', $member->id))
		{
			$member_links[] = html::anchor(
					'members/edit/'.$member->id,
					__('Edit'),
					array
					(
						'title' => __('Edit'),
						'class' => 'popup_link'
					)
			);
		}

		// members's transfers link
		if ($member->id != 1 && $this->acl_check_view('Accounts_Controller', 'transfers', $member->id))
		{
			$member_links[] = html::anchor(
					'transfers/show_by_account/'.$account->id, __('Show transfers')
			);
		}

		// member's tariffs link
		if ($this->acl_check_view(get_class($this), 'fees', $member->id))
		{
			$member_links[] = html::anchor(
					'members_fees/show_by_member/'.$member->id, __('Show tariffs')
			);
		}
	
		if ($member->id != 1)
		{
			if ($member->type != $former_type_id)
			{
				// allowed subnets are enabled
				if (Settings::get('allowed_subnets_enabled') &&
					$this->acl_check_view('Devices_Controller', 'allowed_subnet', $member->id))
				{
					$member_links[] = html::anchor(
							'allowed_subnets/show_by_member/'.$member->id,
							__('Allowed subnets'),
							array
							(
								'title' => __('Show allowed subnets'),
								'class' => 'popup_link'
							)
					);
				}

			}
		}
		
		if ($this->acl_check_new('Messages_Controller', 'member'))
		{
			$member_links[] = html::anchor(
					'notifications/member/'.$member->id, __('Notifications'),
					array
					(
						'title' => __('Set notification to member'),
						'class' => 'popup_link'
					)
			);
		
			$member_links[] = html::anchor(
					'notifications/set_whitelist/'.$member->id, __('Whitelist'),
					array
					(
						'title' => __('Set whitelist to member'),
						'class' => 'popup_link'
					)
			);
		}

		// export of registration link
		$member_links[] = html::anchor(
				'members/registration_export/'.$member->id,
				__('Export of registration'),
				array
				(
					'title' => __('Export of registration'),
					'class' => 'popup_link'
				)
		);
		
		if ($member->id != 1)
		{
			if ($member->type != $former_type_id)
			{
				// end membership link
				if ($this->acl_check_edit(get_class($this), 'members'))
				{
					$member_links[] = html::anchor(
							'members/end_membership/'.$member->id,
							__('End membership'),
							array
							(
								'title' => __('End membership'),
								'class' => 'popup_link'
							)
					);
				}
			}
			else
			{
				// restore membership link
				if ($this->acl_check_edit(get_class($this), 'members'))
				{
					$m = __('Do you want to restore membership of this member');
					$member_links[] = html::anchor(
							'members/restore_membership/'.$member->id,
							__('Restore membership'), array
							(
								'onclick' => 'return window.confirm(\''.$m.'?\')'
							)
					);
				}
			}
		}
		
		// user show link
		if ($this->acl_check_view('Users_Controller', 'users', $member->id))
		{
			$user_links[] = html::anchor('users/show/'.$user->id, __('Show'));
		}

		// user edit link
		if ($member->type != $former_type_id &&
			$this->acl_check_edit('Users_Controller','users', $member->id))
		{
			$user_links[] = html::anchor(
				'users/edit/'.$user->id, __('Edit'),
				array
				(
					'title' => __('Edit'),
					'class' => 'popup_link'
				)
			);
		}

		// user's devices link
		if ($this->acl_check_view('Devices_Controller', 'devices', $member->id))
		{
			$user_links[] = html::anchor(
					'devices/show_by_user/'.$user->id,
					__('Show devices')
			);
		}

		// user's works link
		if ($member->id != 1  &&
			$this->acl_check_view('Users_Controller', 'work', $member->id))
		{
			$user_links[] = html::anchor(
					'works/show_by_user/'.$user->id,
					__('Show works')
			);
		}
		
		// user's work reports link
		if ($member->id != 1  &&
			$this->acl_check_view('Users_Controller', 'work', $member->id))
		{
			$user_links[] = html::anchor(
					'work_reports/show_by_user/'.$user->id,
					__('Show work reports')
			);
		}

		if ($member->type != $former_type_id)
		{
			// change password link
			if ($this->acl_check_edit('Users_Controller', 'password', $member->id))
			{
				$user_links[] = html::anchor(
						'users/change_password/'.$user->id, __('Change password'),
						array
						(
							'title' => __('Change password'),
							'class' => 'popup_link'
						)
				);
			}

			// change application password link
			if ($this->acl_check_edit('Users_Controller', 'application_password', $member->id))
			{
				$user_links[] = html::anchor(
						'users/change_application_password/'.$user->id,
						__('Change application password'),
						array
						(
							'title' => __('Change application password'),
							'class' => 'popup_link'
						)
				);
			}
		}

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view(get_class($this),'members'))
				->disable_translation()
				->text("ID $member->id - $member->name");
		
		
		// view
		$view = new View('main');
		$view->title = $title;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('members/show');
		$view->content->title = $title;
		$view->content->member = $member;
		$view->content->user = $user;
		$view->content->user_name = $user_name;
		$view->content->users_grid = $users_grid;
		$view->content->redir_grid = $redir_grid;
		$view->content->voip_grid = $voip_grid;
		$view->content->membership_interrupts_grid = $membership_interrupts_grid;
		$view->content->contacts = $contacts;
		$view->content->contact_types = $contact_types;
		$view->content->variable_symbols = $variable_symbols;
		$view->content->expiration_date = $expiration_date;
		$view->content->account = (isset($account)) ? $account : NULL;
		$view->content->comments = (isset($account)) ? $account->get_comments() : '';
		$view->content->address = (isset($address)) ? $address : '';
		$view->content->map_query = $map_query;
		$view->content->map_domicile_query = isset($map_d_query) ? $map_d_query : '';
        $view->content->lang = Config::get('lang');
		$view->content->gps = $gps;
		$view->content->domicile_address = (isset($domicile_address)) ? $domicile_address : '';
		$view->content->domicile_town = (isset($domicile_town)) ? $domicile_town : '';
		$view->content->domicile_country = (isset($domicile_country)) ? $domicile_country : '';
		$view->content->domicile_gps = $domicile_gps;
		$view->content->town = (isset($town)) ? $town : '';
		$view->content->country = (isset($country)) ? $country : '';
		$view->content->billing_has_driver = $has_driver;
		$view->content->billing_account = $b_account;
		$view->content->count_voip = count($voip);
		$view->content->total_traffic = @$total_traffic;
		$view->content->today_traffic = @$today_traffic;
		$view->content->month_traffic = @$month_traffic;
		$view->content->member_links = implode(' | ',$member_links);
		$view->content->user_links = implode(' | ',$user_links);
		$view->render(TRUE);
	} // end of show function



	/**
	 * Gets expiration date of member's payments.
	 * 
	 * @author Michal Kliment
	 * @param object $account
	 * @return unknown_type
	 */
	public static function get_expiration_date($account)
	{
		// member's actual balance
		$balance = $account->balance;

		// current date
		$day = date('j');
		$month = date('n');
		$year = date('Y');

		// rounds date down
		date::round_up($day, $month, $year);

		// balance is in positive, we will go to the future
		if ($balance > 0)
		{
			$sign = 1;
		}
		// balance is in negative, we will go to the past
		else
		{
			$sign = -1;
		}

		// ttl = time to live - it is count how many ending conditions
		// will have to happen to end cycle
		// negative balance needs one extra more
		$ttl =  ($balance < 0) ? 2 : 1;

		// negative balance will drawn by red color, else balance will drawn by green color
		$color = ($balance < 0) ? 'red' : 'green';

		$payments = array();

		// finds entrance date of member
		$entrance_date = date_parse(date::get_middle_of_month($account->member->entrance_date));

		// finds debt payment rate of entrance fee
		$debt_payment_rate = ($account->member->debt_payment_rate > 0)
				? $account->member->debt_payment_rate : $account->member->entrance_fee;

		// finds all debt payments of entrance fee
		self::find_debt_payments(
				$payments, $entrance_date['month'], $entrance_date['year'],
				$account->member->entrance_fee, $debt_payment_rate
		);
		
		$entrance_date = date::get_middle_of_month($account->member->entrance_date);

		// finds all member's devices with debt payments
		$devices = ORM::factory('device')->get_member_devices_with_debt_payments($account->member_id);

		foreach ($devices as $device)
		{
			// finds buy date of this device
			$buy_date = date_parse(date::get_middle_of_month($device->buy_date));

			// finds all debt payments of this device
			self::find_debt_payments(
					$payments, $buy_date['month'], $buy_date['year'],
					$device->price, $device->payment_rate
			);
		}

		$fee_model = new Fee_Model();

		// finds min and max date = due to prevent before unending loop
		$min_fee_date = $fee_model->get_min_fromdate_fee_by_type ('regular member fee');
		$max_fee_date = $fee_model->get_max_todate_fee_by_type ('regular member fee');

		while (true)
		{
			$date = date::create(15, $month, $year);

			// date is bigger/smaller than max/min fee date, ends it (prevent before unending loop)
			if (($sign == 1 && $date > $max_fee_date) || ($sign == -1 && $date < $min_fee_date))
				break;

			// finds regular member fee for this month
			$fee = $fee_model->get_regular_member_fee_by_member_date($account->member_id, $date);

			// if exist payment for this month, adds it to the fee
			if (isset($payments[$year][$month]))
				$fee += $payments[$year][$month];

			// attributed / deduct fee to / from balance
			$balance -= $sign * $fee;

			if ($sign == -1 && $balance == 0)
				$ttl--;

			if ($balance * $sign < 0)
				$ttl--;

			if ($ttl == 0)
				break;

			$month += $sign;

			if ($month == 0 OR $month == 13)
			{
				$month = ($month == 13) ? 1 : 12;
				$year += $sign;
			}
		}

		$month--;
		if ($month == 0)
		{
			$month = 12;
			$year--;
		}
		
		$date = date::create (date::days_of_month($month), $month, $year);
		
		if ($date < $entrance_date)
			$date = $entrance_date;

		return  '<span style="color: '.$color.'">'.$date. '</span>';
	}

	/**
	 * It stores debt payments into double-dimensional array (indexes year, month)
	 *
	 * @author Michal Kliment
	 * @param array $payments
	 * @param int $month
	 * @param int $year
	 * @param float $payment_left
	 * @param float $payment_rate
	 */
	protected static function find_debt_payments(
			&$payments, $month, $year, $payment_left, $payment_rate)
	{
		while ($payment_left > 0)
		{
			if ($payment_left > $payment_rate)
				$payment = $payment_rate;
			else
				$payment = $payment_left;

			if (isset($payments[$year][$month]))
				$payments[$year][$month] += $payment;
			else
				$payments[$year][$month] = $payment;

			$month++;
			if ($month > 12)
			{
				$year++;
				$month = 1;
			}
			$payment_left -= $payment;
		}
	}

	/**
	 * Function adds new member to database.
	 * Creates user of type member assigned to this member.
	 */
	public function add()
	{
		// access rights
		if (!$this->acl_check_new(get_class($this),'members'))
			Controller::error(ACCESS);
		
		$enum_types = new Enum_type_Model();
		$types = $enum_types->get_values(Enum_type_Model::MEMBER_TYPE_ID);
		asort($types);
		
		// cannot add former member
		unset($types[$enum_types->get_type_id('Former member')]);
		
		// regular member by default
		$type_id = $enum_types->get_type_id('Regular member');
		
		// entrance fee
		$fee_model = new Fee_Model();
		$fee = $fee_model->get_by_date_type(date('Y-m-d'), 'entrance fee');
		
		if (is_object($fee) && $fee->id)
			$entrance_fee = $fee->fee;
		else
			$entrance_fee = 0;
		
		// countries
		$arr_countries = ORM::factory('country')->select_list('id', 'country_name');
		
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

		// phone prefixes
		$country_model = new Country_Model();
		$phone_prefixes = $country_model->select_country_list();

		// form
		$form = new Forge();

		$form->group('Basic information');
		
		$form->input('title1')
				->label('Pre title')
				->rules('length[3,40]');
		
		$form->input('name')
				->rules('required|length[1,30]');
		
		$form->input('middle_name')
				->rules('length[1,30]');
		
		$form->input('surname')
				->rules('required|length[1,60]');
		
		$form->input('title2')
				->label('Post title')
				->rules('length[1,30]');
		
		$form->dropdown('type')
				->options($types)
				->rules('required')
				->selected($type_id)
				->style('width:200px');
		
		$form->input('membername')
				->label(__('Name of organization').':&nbsp;'.help::hint('member_name'))
				->rules('length[1,60]');
		
		$form->input('organization_identifier')
				->rules('length[3,20]');

		$form->group('Login data');
		
		$form->input('login')
				->label('Username')
				->rules('required|length[5,20]')
				->callback(array($this, 'valid_username'));
		
		$form->password('password')
				->label(__('Password').':&nbsp;'.help::hint('password'))
				->rules('required|length[3,50]')
				->class('password');
		
		$form->password('confirm_password')
				->rules('required|length[3,50]')
				->matches($form->password);

		$form->group('Address of connecting place');
		
		$form->dropdown('town_id')
				->label('Town')
				->rules('required')
				->options($arr_towns)
				->style('width:200px')
				->add_button('towns');
		
		$form->dropdown('street_id')
				->label('Street')
				->options($arr_streets)
				->style('width:200px')
				->style('width:200px')
				->add_button('streets');
		
		$form->input('street_number')
				->rules('length[1,50]');
		
		$form->dropdown('country_id')
				->label('Country')
				->rules('required')
				->options($arr_countries)
				->style('width:200px')
				->selected(Settings::get('default_country'));
		
		$form->input('gpsx')
				->label(__('GPS').'&nbsp;X:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps');
		
		$form->input('gpsy')
				->label(__('GPS').'&nbsp;Y:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps');

		$form->group('Address of domicile');
		
		$form->checkbox('use_domicile')
				->label(__(
						'Address of connecting place is different than address of domicile'
				));
		
		$form->dropdown('domicile_town_id')
				->label('Town')
				->options($arr_towns)
				->style('width:200px')
				->add_button('towns');
		
		$form->dropdown('domicile_street_id')
				->label('Street')
				->options($arr_streets)
				->style('width:200px')
				->add_button('streets');
		
		$form->input('domicile_street_number')
				->label('Street number')
				->rules('length[1,50]')
				->callback(array($this, 'valid_docimile_street_number'))
				->style('width:200px');
		
		$form->dropdown('domicile_country_id')
				->label('Country')
				->options($arr_countries)
				->selected(Settings::get('default_country'))
				->style('width:200px');
		
		$form->input('domicile_gpsx')
				->label(__('GPS').'&nbsp;X:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps');
		
		$form->input('domicile_gpsy')
				->label(__('GPS').'&nbsp;Y:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps');

		$form->group('Contact information');
		
		$form->dropdown('phone_prefix')
				->label('Telephone prefix')
				->rules('required')
				->options($phone_prefixes)
				->selected(Settings::get('default_country'))
				->style('width:200px');
		
		$form->input('phone')
				->rules('required|length[9,40]')
				->callback(array($this, 'valid_phone'));
		
		$form->input('email')
				->rules('length[3,50]|valid_email');

		$form->group('Account information');
		
		$form->input('variable_symbol')
				->label(__('Variable symbol').':&nbsp;'.help::hint('variable_symbol'))
				->rules('required|length[1,10]')
				->callback(array($this, 'valid_var_sym'));
		
		$form->input('entrance_fee')
				->label(__('Entrance fee').':&nbsp;'.help::hint('entrance_fee'))
				->rules('valid_numeric')
				->value($entrance_fee);
		
		$form->input('debt_payment_rate')
				->label(
						__('Monthly instalment of entrance').
						':&nbsp;'.help::hint('entrance_fee_instalment')
				)
				->rules('valid_numeric')
				->value($entrance_fee);
		
		$form->group('Additional information');
		
		$form->input('qos_ceil')
				->label(__('QoS ceil') . ':&nbsp;' . help::hint('qos_ceil'))
				->rules('valid_speed_size');
		
		$form->input('qos_rate')
				->label(__('QoS rate') . ':&nbsp;' . help::hint('qos_rate'))
				->rules('valid_speed_size');
		
		$form->date('birthday')
				->label('Birthday')
				->years(date('Y')-100, date('Y'))
				->rules('required');
		
		$form->date('entrance_date')
				->label('Entrance date')
				->years(date('Y')-100, date('Y'))
				->rules('required')
				->callback(array($this, 'valid_entrance_date'));
		
		$form->textarea('comment')
				->rules('length[0,250]');
		
		$form->submit('Add');
		
		// posted
		if($form->validate())
		{
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
			
			// gps domicicle
			$domicile_gpsx = NULL;
			$domicile_gpsy = NULL;
			
			if (!empty($form_data['domicile_gpsx']) && !empty($form_data['domicile_gpsy']))
			{
				$domicile_gpsx = doubleval($form_data['domicile_gpsx']);
				$domicile_gpsy = doubleval($form_data['domicile_gpsy']);

				if (gps::is_valid_degrees_coordinate($form_data['domicile_gpsx']))
				{
					$domicile_gpsx = gps::degrees2real($form_data['domicile_gpsx']);
				}

				if (gps::is_valid_degrees_coordinate($form_data['domicile_gpsy']))
				{
					$domicile_gpsy = gps::degrees2real($form_data['domicile_gpsy']);
				}
			}

			$member = new Member_Model();
			
			try
			{
				//$profiler = new Profiler();
				// let's start safe transaction processing
				$member->transaction_start();
				
				$user = new User_Model();
				$account = new Account_Model();
				$address_point_model = new Address_point_Model();
				
				$address_point = $address_point_model->get_address_point(
						$form_data['country_id'], $form_data['town_id'],
						$form_data['street_id'], $form_data['street_number'],
						$gpsx, $gpsy
				);

				// add address point if there is no such
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
					$address_point->gps = '';
					$address_point->save_throwable();
				}
				
				$member->address_point_id = $address_point->id;

				$account->account_attribute_id = Account_attribute_Model::CREDIT;
				
				if ($form_data['membername'] == '')
				{
					$account->name = $form_data['surname'].' '.$form_data['name'];
				}
				else
				{
					$account->name = $form_data['membername'];
				}
				
				$user->name = $form_data['name'];
				$user->middle_name = $form_data['middle_name'];
				$user->login = $form_data['login'];
				$user->surname = $form_data['surname'];
				$user->pre_title = $form_data['title1'];
				$user->post_title = $form_data['title2'];
				$user->birthday	= date("Y-m-d",$form_data['birthday']);
				$user->password	= sha1($form_data['password']);
				$user->type = User_Model::MAIN_USER;
				$user->application_password = security::generate_password();
				
				// id of user who added member
				$member->user_id = $this->session->get('user_id');
				$member->comment = $form_data['comment'];
				
				if ($form_data['membername'] == '')
				{
					$member->name = $form_data['name'].' '.$form_data['surname'];
				}
				else
				{
					$member->name = $form_data['membername'];
				}
				
				$member->type = $form_data['type'];
				$member->organization_identifier = $form_data['organization_identifier'];
				$member->qos_ceil = $form_data['qos_ceil'];
				$member->qos_rate = $form_data['qos_rate'];
				$member->entrance_fee = $form_data['entrance_fee'];
				$member->debt_payment_rate = $form_data['debt_payment_rate'];
				
				if ($member->type == Member_Model::TYPE_APPLICANT)
				{
					$member->entrance_date = NULL;
				}
				else
				{
					$member->entrance_date = date("Y-m-d",$form_data['entrance_date']);
				}
				
				// saving member
				$member->save_throwable();
				
				// saving user
				$user->member_id = $member->id;
				$user->save_throwable();
				
				// telephone
				$contact_model = new Contact_Model();
				
				// search for contacts
				$contact_id = $contact_model->find_contact_id(
						Contact_Model::TYPE_PHONE, $form_data['phone']
				);
				
				if ($contact_id)
				{
					$contact_model = ORM::factory('contact', $contact_id);
					$contact_model->add($user);
					$contact_model->save_throwable();
				}
				else
				{ // add whole contact
					$contact_model->type = Contact_Model::TYPE_PHONE;
					$contact_model->value = $form_data['phone'];
					$contact_model->save_throwable();

					$contact_model->add($user);
							
					$phone_country = new Country_Model($form_data['phone_prefix']);
					$contact_model->add($phone_country);

					$contact_model->save_throwable();
				}
				
				$contact_model->clear();
				
				// email
				if (! empty($form_data['email']))
				{
					$contact_model->type = Contact_Model::TYPE_EMAIL;
					$contact_model->value = $form_data['email'];
					$contact_model->save_throwable();
					$contact_model->add($user);
					$contact_model->save_throwable();
				}
				
				// saving account
				$account->member_id	= $member->id;
				$account->save_throwable();
				
				// saving variable symbol
				$variable_symbol_model = new Variable_Symbol_Model();
				$variable_symbol_model->account_id = $account->id;
				$variable_symbol_model->variable_symbol = $form_data['variable_symbol'];
				$variable_symbol_model->save_throwable();

				// save allowed subnets count of member
				$allowed_subnets_count = new Allowed_subnets_count_Model();
				$allowed_subnets_count->member_id = $member->id;
				$allowed_subnets_count->count = Settings::get('allowed_subnets_default_count');
				$allowed_subnets_count->save();

				// address of connecting place is different than address of domicile
				if ($form_data['use_domicile'])
				{
					$address_point = $address_point_model->get_address_point(
							$form_data['domicile_country_id'],
							$form_data['domicile_town_id'],
							$form_data['domicile_street_id'],
							$form_data['domicile_street_number'],
							$domicile_gpsx, $domicile_gpsy
					);

					// add address point if there is no such
					if (!$address_point->id)
					{
						$address_point->save_throwable();
					}
					
					// test if address of connecting place is really
					// different than address of domicile
					if ($member->address_point_id != $address_point->id)
					{
						// add GPS
						if (!empty($domicile_gpsx) && !empty($domicile_gpsy))
						{ // save
							$address_point->update_gps_coordinates(
									$address_point->id, $domicile_gpsx,
									$domicile_gpsy
							);
						}
						else
						{ // delete gps
							$address_point->gps = '';
							$address_point->save_throwable();
						}
						// add domicicle
						$members_domicile = new Members_domicile_Model();
						$members_domicile->member_id = $member->id;
						$members_domicile->address_point_id = $address_point->id;
						$members_domicile->save_throwable();
					}
				}

				// insert regular member access rights
				$groups_aro_map = new Groups_aro_map_Model();
				$groups_aro_map->aro_id = $user->id;
				$groups_aro_map->group_id = Aro_group_Model::REGULAR_MEMBERS;
				$groups_aro_map->save_throwable();
				
				// reset post
				unset($form_data);
				
				// send welcome message to member
				$mail_message = new Mail_message_Model();
				$mail_message->from_id = 1;
				$mail_message->to_id = $user->id;
				$mail_message->subject = mail_message::format('welcome_subject');
				$mail_message->body = mail_message::format('welcome');
				$mail_message->time = date('Y-m-d H:i:s');
				$mail_message->from_deleted = 1;
				$mail_message->save();
				
				// commit transaction
				$member->transaction_commit();
				status::success('Member has been successfully added.');
				
				// redirect
				url::redirect('members/show/'.$member->id);
			}
			catch (Exception $e)
			{
				// rollback transaction
				$member->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cant add new member.');
				$this->redirect('members/show_all');
			}
		}
		else
		{
			$headline = __('Add new member');

			// breadcrumbs navigation			
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view(get_class($this),'members'))
					->disable_translation()
					->text($headline);

			// view
			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form->html();
			$view->content->link_back = '';
			$view->render(TRUE);
		}

	} // end of add function

	/**
	 * Form for editing member.
	 * 
	 * @param integer $member_id	id of member to edit
	 */
	public function edit($member_id = NULL)
	{
		// bad parameter
		if (!isset($member_id) || !is_numeric($member_id))
			Controller::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// member doesn't exist
		if (!$member->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_edit(get_class($this), 'members', $member->id))
			Controller::error(ACCESS);

		$this->_member_id = $member->id;

		// countries
		$arr_countries = ORM::factory('country')->select_list('id', 'country_name');
		
		// streets
		$arr_streets = array
		(
			NULL => '--- ' . __('Without street') . ' ---'
		) + $member->address_point->town->streets->select_list('id', 'street');
		
		// streets
		$arr_domicile_streets = array
		(
			NULL => '--- ' . __('Without street') . ' ---'
		) + $member->members_domicile->address_point->town->streets->select_list('id', 'street');
		
		// towns with zip code and quarter
		$arr_towns = array
		(
			NULL => '--- ' . __('Select town') . ' ---'
		) + ORM::factory('town')->select_list_with_quater();

		// engineers
		$member = new Member_Model($member_id);
		
		$concat = "CONCAT(
				COALESCE(surname, ''), ' ',
				COALESCE(name, ''), ' - ',
				COALESCE(login, '')
		)";
		
		$arr_engineers = array
		(
			NULL => '----- '.__('select user').' -----'
		) + ORM::factory('user')->select_list('id', $concat);

		$allowed_subnets_count = ($member->allowed_subnets_count) ?
				$member->allowed_subnets_count->count : 0;

		$form = new Forge('members/edit/'.$member->id);

		$form->group('Basic information');
		
		if ($this->acl_check_edit(get_class($this),'name',$member->id))
		{
			$form->input('membername')
					->label('Member name')
					->rules('required|length[1,60]')
					->value($member->name);
		}
		
		if ($this->acl_check_edit(get_class($this),'type',$member->id))
		{
			$enum_type_model = new Enum_type_Model();
			$types = $enum_type_model->get_values(Enum_type_Model::MEMBER_TYPE_ID);
			unset($types[$enum_type_model->get_type_id('Former member')]);
			
			$form->dropdown('type')
					->options($types)
					->selected($member->type)
					->callback(array($this, 'valid_member_type'))
					->style('width:200px');
		}
		
		if ($this->acl_check_edit(get_class($this), 'organization_id', $member->id))
		{
			$form->input('organization_identifier')
					->rules('length[3,20]')
					->value($member->organization_identifier);
		}
			
		if ($this->acl_check_edit(get_class($this), 'address', $member->id))
		{	
			// gps
			$gpsx = '';
			$gpsy = '';

			if (!empty($member->address_point->gps))
			{
				$gps_result = $member->address_point->get_gps_coordinates(
						$member->address_point->id
				);

				if (!empty($gps_result))
				{
					$gpsx = gps::real2degrees($gps_result->gpsx, false);
					$gpsy = gps::real2degrees($gps_result->gpsy, false);
				}
			}
			
			// gps
			$domicile_gpsx = '';
			$domicile_gpsy = '';

			if (!empty($member->members_domicile->address_point->gps))
			{
				$gps_result = $member->address_point->get_gps_coordinates(
						$member->members_domicile->address_point->id
				);

				if (!empty($gps_result))
				{
					$domicile_gpsx = gps::real2degrees($gps_result->gpsx, false);
					$domicile_gpsy = gps::real2degrees($gps_result->gpsy, false);
				}
			}
			
			$form->group('Address of connecting place');
			
			$form->dropdown('town_id')
					->label('Town')
					->rules('required')
					->options($arr_towns)
					->selected($member->address_point->town_id)
					->style('width:200px')
					->add_button('towns');
			
			$form->dropdown('street_id')
					->label('Street')
					->options($arr_streets)
					->selected($member->address_point->street_id)
					->style('width:200px')
					->add_button('streets');
			
			$form->input('street_number')
					->rules('length[1,50]')
					->value($member->address_point->street_number);
			
			$form->dropdown('country_id')
					->label('Country')
					->rules('required')
					->options($arr_countries)
					->selected($member->address_point->country_id)
					->style('width:200px');
		
			$form->input('gpsx')
					->label(__('GPS').'&nbsp;X:&nbsp;'.help::hint('gps_coordinates'))
					->rules('gps')
					->value($gpsx);

			$form->input('gpsy')
					->label(__('GPS').'&nbsp;Y:&nbsp;'.help::hint('gps_coordinates'))
					->rules('gps')
					->value($gpsy);

			$form->group('Address of domicile');
			
			$form->checkbox('use_domicile')
					->label('Address of connecting place is different than address of domicile')
					->checked((bool) $member->members_domicile->id);
					
			$form->dropdown('domicile_town_id')
					->label('Town')
					->options($arr_towns)
					->selected($member->members_domicile->address_point->town_id)
					->style('width:200px')
					->add_button('towns');
			
			$form->dropdown('domicile_street_id')
					->label('Street')
					->options($arr_domicile_streets)
					->selected($member->members_domicile->address_point->street_id)
					->style('width:200px')
					->add_button('streets');
			
			$form->input('domicile_street_number')
					->label('Street number')
					->rules('length[1,50]')
					->value($member->members_domicile->address_point->street_number)
					->callback(array($this, 'valid_docimile_street_number'));
			
			$form->dropdown('domicile_country_id')
					->label('Country')
					->rules('required')
					->options($arr_countries)
					->selected($member->members_domicile->address_point->country_id)
					->style('width:200px');	
		
			$form->dropdown('domicile_country_id')
					->label('Street')
					->options($arr_countries)
					->selected(Settings::get('default_country'));

			$form->input('domicile_gpsx')
					->label(__('GPS').'&nbsp;X:&nbsp;'.help::hint('gps_coordinates'))
					->rules('gps')
					->value($domicile_gpsx);

			$form->input('domicile_gpsy')
					->label(__('GPS').'&nbsp;Y:&nbsp;'.help::hint('gps_coordinates'))
					->rules('gps')
					->value($domicile_gpsy);
		}

		$form->group('Account information');
		
		if ($this->acl_check_edit(get_class($this), 'en_fee', $member->id))
		{
			$form->input('entrance_fee')
					->label(
							__('Entrance fee').
							':&nbsp;'.help::hint('entrance_fee')
					)
					->rules('valid_numeric')
					->value($member->entrance_fee);
		}
		if ($this->acl_check_edit(get_class($this),'debit', $member->id))
		{
			$form->input('debt_payment_rate')
					->label(__('Monthly instalment of entrance')
							. ':&nbsp;'.help::hint('entrance_fee_instalment'))
					->rules('valid_numeric')
					->value($member->debt_payment_rate);
		}
		// additional information
		$form->group('Additional information');
		
		if ($this->acl_check_edit(get_class($this), 'qos_ceil', $member->id))
		{
			$form->input('qos_ceil')
					->label(__('QOS ceil') . ':&nbsp;' . help::hint('qos_ceil'))
					->rules('valid_speed_size')
					->value($member->qos_ceil);
		}
		
		if ($this->acl_check_edit(get_class($this),'qos_rate', $member->id))
		{
			$form->input('qos_rate')
					->label(__('QOS rate') . ':&nbsp;' . help::hint('qos_rate'))
					->rules('valid_speed_size')
					->value($member->qos_rate);
		}
		
		$form->input('allowed_subnets_count')
				->label(__('Count of allowed subnets')
						. ': '.help::hint('allowed_subnets_count'))
				->rules('valid_numeric')
				->value($allowed_subnets_count);
		
		if ($this->acl_check_edit(get_class($this), 'entrance_date', $member->id))
		{
			$form->date('entrance_date')
					->label('Entrance date')
					->years(date('Y')-100, date('Y'))
					->rules('required')
					->value(strtotime($member->entrance_date));
		}
		
		if ($member->id != 1 &&
			$this->acl_check_edit(get_class($this), 'locked', $member->id))
		{
			$arr_lock = array
			(
				'0'=> __('Unlocked'),
				'1'=> __('Locked')
			);
			
			$form->dropdown('locked')
					->label('Access to system')
					->options($arr_lock)
					->selected($member->locked);
		}
		
		if ($member->id != Member_Model::ASSOCIATION &&
			$this->acl_check_edit('Members_Controller', 'registration', $member->id))
		{
			$form->dropdown('registration')
					->options(arr::rbool())
					->selected($member->registration);
		}
		
		if ($this->acl_check_edit('Members_Controller', 'user_id'))
		{
			$form->dropdown('user_id')
					->label('Added by')
					->options($arr_engineers)
					->selected($member->user_id)
					->style('width:500px');
		}
		
		if ($this->acl_check_edit(get_class($this), 'comment', $member->id))
		{
			$form->textarea('comment')
					->rules('length[0,250]')
					->value($member->comment)
					->style('width:500px');
		}

		$form->submit('Edit');

		// form validation
		if($form->validate())
		{
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
			
			// gps domicicle
			$domicile_gpsx = NULL;
			$domicile_gpsy = NULL;
			
			if (!empty($form_data['domicile_gpsx']) && !empty($form_data['domicile_gpsy']))
			{
				$domicile_gpsx = doubleval($form_data['domicile_gpsx']);
				$domicile_gpsy = doubleval($form_data['domicile_gpsy']);

				if (gps::is_valid_degrees_coordinate($form_data['domicile_gpsx']))
				{
					$domicile_gpsx = gps::degrees2real($form_data['domicile_gpsx']);
				}

				if (gps::is_valid_degrees_coordinate($form_data['domicile_gpsy']))
				{
					$domicile_gpsy = gps::degrees2real($form_data['domicile_gpsy']);
				}
			}

			// find member
			$member = new Member_Model($member_id);

			// access control
			if ($this->acl_check_edit(get_class($this),'address',$member_id))
			{
				// find his address point
				$address_point_model = new Address_point_Model();

				$address_point = $address_point_model->get_address_point(
						$form_data['country_id'], $form_data['town_id'],
						$form_data['street_id'], $form_data['street_number'],
						$gpsx, $gpsy
				);
				
				// add address point if there is no such
				if (!$address_point->id)
				{
					// save
					$address_point->save();
				}
				// new address point
				if ($address_point->id != $member->address_point_id)
				{
					// delete old?
					$addr_id = $member->address_point->id;
					// add to member
					$member->address_point_id = $address_point->id;
					$member->save();
					// change just for this device?
					if ($address_point->count_all_items_by_address_point_id($addr_id) < 1)
					{
						$addr = new Address_point_Model($addr_id);
						$addr->delete();
					}
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
					$address_point->gps = '';
					$address_point->save();
				}

				// address of connecting place is different than address of domicile
				if ($form_data['use_domicile'])
				{
					$address_point = $address_point_model->get_address_point(
							$form_data['domicile_country_id'],
							$form_data['domicile_town_id'],
							$form_data['domicile_street_id'],
							$form_data['domicile_street_number'],
							$domicile_gpsx, $domicile_gpsy
					);
					
					// add address point if there is no such
					if (!$address_point->id)
					{
						// save
						$address_point->save();
					}
					// new address point
					if ($address_point->id != $member->members_domicile->address_point_id)
					{
						// delete old?
						$addr_id = $member->members_domicile->address_point->id;
						// add to memeber
						$member->members_domicile->member_id = $member->id;
						$member->members_domicile->address_point_id = $address_point->id;
						$member->members_domicile->save();
						// change just for this device?
						if (!empty($addr_id) &&
							$address_point->count_all_items_by_address_point_id($addr_id) < 1)
						{
							ORM::factory('address_point')->delete($addr_id);
						}
					}

					// add GPS
					if (!empty($domicile_gpsx) && !empty($domicile_gpsy))
					{ // save
						$address_point->update_gps_coordinates(
								$address_point->id, $domicile_gpsx, $domicile_gpsy
						);
					}
					else
					{ // delete gps
						$address_point->gps = '';
						$address_point->save();
					}
				}
				// address of connecting place is same as address of domicile
				else if ($member->members_domicile)
				{
					$addrp_id = $member->members_domicile->address_point_id;
					$member->members_domicile->delete();
					
					// delete orphan address point
					if ($address_point_model->count_all_items_by_address_point_id(
							$addrp_id
						) < 1)
					{
						ORM::factory('address_point')->delete($addrp_id);
					}
				}
				// removes duplicity
				if (($member->members_domicile->address_point_id == $member->address_point_id) &&
					$member->members_domicile)
				{
					$member->members_domicile->delete();
				}
			}

			if ($this->acl_check_edit(get_class($this),'type',$member->id))
			{
				if ($member->type != $form_data['type'])
				{
					// change gacl rights for applicant (registration)
					// required after self registration
					if ($member->type == Member_Model::TYPE_APPLICANT)
					{
						$group_aro_map = new Groups_aro_map_Model();
						
						// if is not member yet
						if (!$group_aro_map->exist_row(
								Aro_group_Model::REGULAR_MEMBERS, $member->user_id
							))
						{
							// delete rights of applicant
							$group_aro_map->detete_row(
									Aro_group_Model::REGISTERED_APPLICANTS,
									$member->user_id
							);							
							
							// insert regular member access rights
							$groups_aro_map = new Groups_aro_map_Model();
							$groups_aro_map->aro_id = $member->user_id;
							$groups_aro_map->group_id = Aro_group_Model::REGULAR_MEMBERS;
							$groups_aro_map->save();
						}
						
						// send email message about approval
						$contact = new Contact_Model();
						$emails = $contact->find_all_users_contacts(
								$member->user->id, Contact_Model::TYPE_EMAIL
						);
						
						if ($emails && $emails->count())
						{
							$to = $emails->current()->value;
							$from = Settings::get('email_default_email');
							$subject = 'Registration confirm';
							$message = 'Your registration to FreenetIS has been confirmed';
							
							try
							{
								email::send($to, $from, $subject, $message);
							}
							catch (Exception $e)
							{
								$m = __('Error - cannot send ' .
										'email to applicant about approval of membership'
								) . '<br>' . __('Error') .
								': ' . $e->getMessage();
								
								status::error($m, FALSE);
							}
						}
						
					}
					$member->type = $form_data['type'];
				}
			}

			if ($this->acl_check_edit(get_class($this), 'organization_id', $member->id))
				$member->organization_identifier = $form_data['organization_identifier'];

			if ($this->acl_check_edit(get_class($this),'locked',$member->id) && 
				$member->id != 1)
			{
				$member->locked = $form_data['locked'];
			}
			
			if ($member->id != Member_Model::ASSOCIATION &&
				$this->acl_check_edit('Members_Controller', 'registration', $member->id))
			{
				$member->registration = $form_data['registration'];
			}

			if ($this->acl_check_edit('Members_Controller', 'user_id'))
				$member->user_id = $form_data['user_id'];

			if ($this->acl_check_edit(get_class($this),'comment',$member->id))
				$member->comment = $form_data['comment'];

			// member data
			if ($this->acl_check_edit(get_class($this),'entrance_date',$member->id))
			{
				if ($member->type == Member_Model::TYPE_APPLICANT)
					$member->entrance_date = NULL;
				else
					$member->entrance_date = date("Y-m-d",$form_data['entrance_date']);
			}

			if ($this->acl_check_edit(get_class($this),'name',$member->id))
				$member->name = $form_data['membername'];

			if ($this->acl_check_edit(get_class($this),'qos_ceil',$member->id))
				$member->qos_ceil = $form_data['qos_ceil'];

			if ($this->acl_check_edit(get_class($this),'qos_rate',$member->id))
				$member->qos_rate = $form_data['qos_rate'];

			if ($this->acl_check_edit(get_class($this),'en_fee',$member->id))
				$member->entrance_fee = $form_data['entrance_fee'];

			if ($this->acl_check_edit(get_class($this),'debit',$member->id))
				$member->debt_payment_rate = $form_data['debt_payment_rate'];

			$member_saved = $member->save();

			unset($form_data);
			
			if ($member_saved)
			{
				status::success('Member has been successfully updated.');
			}
			else
			{
				status::error('Error - cant update member.');
			}
			
			$this->redirect('members/show/', $member_id);
		}
		else
		{
			$headline = __('Edit member');

			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view(get_class($this),'members'))
					->disable_translation()
					->link('members/show/'.$member->id,
							"ID $member->id - $member->name",
							$this->acl_check_view(
									get_class($this),'members', $member->id
							)
					)
					->text($headline);

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->headline =
					__('Editing of member').' '.$member->name;
			$view->content->form = $form->html();
			$view->content->link_back = '';
			$view->render(TRUE);
		}
	} // end of edit function

	/**
	 * Function ends membership of member.
	 * 
	 * @param integer $member_id
	 * 
	 */
	public function end_membership($member_id = null)
	{
		// wrong argument
		if (!isset($member_id) || !is_numeric($member_id))
			Controller::warning(PARAMETER);
		
		$member = new Member_Model($member_id);
		
		// wrong id
		if (!$member->id)
			Controller::error(RECORD);
		
		// access
		if (!$this->acl_check_edit(get_class($this), 'members', $member_id))
			Controller::error(ACCESS);
		
		// form
		$form = new Forge('members/end_membership/' . $member_id);
		
		$form->date('leaving_date')
				->label('Leaving date');
		
		$form->submit('End membership');
		
		// validation
		if ($form->validate())
		{
			$form_data = $form->as_array();
			$member->leaving_date = date('Y-m-d', $form_data['leaving_date']);
			$enum_type_model = new Enum_type_Model();
			$member->type = $enum_type_model->get_type_id('Former member');
			
			if ($member->save())
			{
				status::success('Membership of the member has been ended.');
			}
			else
			{
				status::error('Error - cant end membership.');
			}
			
			$this->redirect('members/show/', $member_id);
		}

		$headline = __('End membership');

		// breadcrumbs navigation		
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view(get_class($this),'members'))
				->disable_translation()
				->link('members/show/'.$member->id,
						"ID $member->id - $member->name",
						$this->acl_check_view(
								get_class($this),'members', $member->id
						)
				)
				->text($headline);

		// view
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->content->link_back = '';
		$view->render(TRUE);
	}

	/**
	 * Function restores membership of member.
	 * 
	 * @param integer $member_id
	 */
	public function restore_membership($member_id = null)
	{
		// wrong parametr
		if (!isset($member_id) || !is_numeric($member_id))
			Controller::warning(PARAMETER);
		
		$member = new Member_Model($member_id);
		
		// wrong id
		if (!$member->id)
			Controller::error(RECORD);
		
		// acess
		if (!$this->acl_check_edit(get_class($this), 'members', $member_id))
			Controller::error(ACCESS);
		
		// this sets member to regular member
		$member->leaving_date = '0000-00-00';
		$enum_type_model = new Enum_type_Model();
		$member->type = $enum_type_model->get_type_id('Regular member');
		
		if ($member->save())
		{
			status::success('Membership of the member has been successfully restored.');
		}
		else
		{
			status::error('Error - cant restore membership.');
		}
		
		// redirect
		url::redirect('members/show/'.(int)$member_id);
	}

	/**
	 * Function to export member's registration to PDF or HTML format
	 * 
	 * @author Michal Kliment
	 * @param integer $member_id
	 */
	public function registration_export($member_id = NULL)
	{
		// no parameter
		if (!isset($member_id))
			Controller::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// record doesn't exist
		if ($member->id == 0)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view(get_class($this), 'members', $member_id))
			Controller::error(ACCESS);

		// creates new form
		$form = new Forge('members/registration_export/'.$member_id.'?noredirect=0');
		
		$form->set_attr('class', 'form nopopup');
		
		$form->group('Choose format of export');
		
		$form->dropdown('format')
				->rules('required')
				->options(array
				(
					'pdf'	=>	'PDF '.__('document'),
					'html'	=>	'HTML'
				));
		
		$form->submit('Export');

		// form is validate
		if($form->validate())
		{
			$form_data = $form->as_array();

			switch ($form_data["format"])
			{
				case 'html':
					// do html export
					$this->registration_html_export($member_id);
					break;

				case 'pdf':
					// do pdf export
					$this->registration_pdf_export($member_id);
					break;
			}
		}

		$headline = __('Export of registration');

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view(get_class($this),'members'))
				->disable_translation()
				->link('members/show/'.$member->id,
						"ID $member->id - $member->name",
						$this->acl_check_view(
								get_class($this),'members', $member->id
						)
				)
				->text($headline);

		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('form');
		$view->content->headline = __('Export of registration');
		$view->content->link_back = '';
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Export member registration to HTML
	 *
	 * @todo implement
	 * @param integer $member_id 
	 */
	private function registration_html_export($member_id)
	{
		// no parameter
		if (!isset($member_id))
			Controller::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// record doesn't exist
		if ($member->id == 0)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view(get_class($this), 'members', $member_id))
			Controller::error(ACCESS);
		
		// html head
		$page = "<html>";
		$page .= "<head>";
		$page .= "<title>".__('export of registration').' - '.$member->name."</title>";
		$page .= "</head>";
		$page .= '<body style="font-size:14px">';
		
		// -------------------- LOGO -------------------------------
		
		$logo = Settings::get('registration_logo');
		$page .= '<div style="width:18cm">';
		if (file_exists($logo))
		{
			$page .= '<div style="float:left"><img src="'.url_lang::base().'export/logo" width=274 height=101></div>';
		}
		else	//if logo doesn't exist, insert only blank div
		{
			$page .= '<div style="float:left" width=274 height=101>';
		}
		
		// --------------- INFO ABOUT ASSOCIATION -----------------
		
		$page .= '<div style="float:right">';
		$a_member = new Member_Model(1);
		
		$bank_account_model = new Bank_account_Model();
		$a_bank_account = $bank_account_model->get_assoc_bank_accounts()->current();
		
		$page .= $a_member->name ."</br>";
		$page .= __('organization identifier').
				': '.$a_member->organization_identifier. "</br>";
		$page .= __('account number').': '
				.$a_bank_account->account_number. "</br>";
		$page .= $a_member->address_point->street->street.' '.
				$a_member->address_point->street_number. "</br>";
		$page .= $a_member->address_point->town->zip_code .' '.
				$a_member->address_point->town->town. "</br>";
		
		$page .= '</div><div style="clear:both;text-align:center;font-weight:bold;margin:0px;">';
		
		// --------------------- MAIN TITLE -------------------------
		
		$page .= '<p style="font-size:1.5em">'.__('Request for membership'). ' – '
				. __('registration in association')."</p>";
		
		// --------------------- INFO -------------------------
		
		$page .= '<span>'.$this->settings->get('registration_info').'</span>';
		
		// ----------- TABLE WITH INFORMATION ABOUT MEMBER --------
		
				$member_name = $member->name;
		
		$street = $member->address_point->street->street.' '
				.$member->address_point->street_number;
		
		$town = $member->address_point->town->town;
		
		if ($member->address_point->town->quarter != '') 
			$town .= '-'.$member->address_point->town->quarter;
		
		$zip_code = $member->address_point->town->zip_code;
		
		$variable_symbol_model = new Variable_Symbol_Model();
		$account_model = new Account_Model();
		$account_id = $account_model->where('member_id',$member_id)->find()->id;
		
		$variable_symbols = array();
		$var_syms = $variable_symbol_model->find_account_variable_symbols($account_id);
		
		foreach ($var_syms as $var_sym)
		{
			$variable_symbols[] = $var_sym->variable_symbol;
		}	
		
		$entrance_date = date::pretty($member->entrance_date);

		$user_model = new User_Model();
		
		$user = $user_model->where('member_id',$member_id)
				->where('type',User_Model::MAIN_USER)
				->find();
		
		$emails = $user->get_user_emails($user->id);
		$email = '';
		if ($emails && $emails->current())
		{
			$email = $emails->current()->email;
		}
		$birthday = date::pretty($user->birthday);

		$enum_type_model = new Enum_type_Model();
		$types = $enum_type_model->get_values(Enum_type_Model::CONTACT_TYPE_ID);
		$contact_model = new Contact_Model();
		$contacts = $contact_model->find_all_users_contacts($user->id);
		$phone_id = $enum_type_model->get_type_id('Phone');
		$icq_id = $enum_type_model->get_type_id('ICQ');
		$msn_id = $enum_type_model->get_type_id('MSN');
		$jabber_id = $enum_type_model->get_type_id('Jabber');
		$skype_id = $enum_type_model->get_type_id('Skype');
		$phones = array();
		$arr_contacts = array();
		
		foreach ($contacts as $contact)
		{
			if ($contact->type == $phone_id)
			{
			    $phones[] = $contact->value;
			}
			else if($contact->type == $icq_id OR
					$contact->type == $msn_id OR
					$contact->type == $jabber_id OR
					$contact->type == $skype_id)
			{
			    $arr_contacts[] = $types[$contact->type].': '.$contact->value;
			}
		}
		
		$contact_info = implode('<br />', $arr_contacts);

		$device_engineer_model = new Device_engineer_Model();
		$device_engineers = $device_engineer_model->get_engineers_of_user($user->id);
		$arr_engineers = array();
		
		foreach ($device_engineers as $device_engineer)
		{
		    $arr_engineers[] = $device_engineer->surname;
		}
		
		$engineers = (count($arr_engineers)) ? implode(', ',$arr_engineers) : $member->user->surname;
		
		$subnet = new Subnet_Model();
		$subnet = $subnet->get_subnet_of_user($user->id);
		$subnet_name = isset($subnet->name) ? $subnet->name : '';
		
		$tbl = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="font-size:14px;">';
		$tbl .= "<tr>";
		$tbl .= "	<td><b>". __('name',NULL,1) .", ";
		$tbl .= __('surname',NULL,1) .",<br /> ";
		$tbl .= __('title',NULL,1) ."</b></td>";
		$tbl .= "	<td align=\"center\">$member_name</td>";
		$tbl .= "	<td><b>". __('email address') ."</b></td>";
		$tbl .= "	<td align=\"center\">$email</td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>". __('address of connecting place',NULL,1);
		$tbl .= "</b> (". strtolower(__('street',NULL,1)) .", ";
		$tbl .= __('street_number',NULL,1) .", ";
		$tbl .= __('zip code') .", ". __('town',NULL,1) .")</td>";
		$tbl .= "	<td align=\"center\">$street<br />$town<br />$zip_code</td>";
		$tbl .= "	<td><b>". __('id of member') ."</b><br /> (";
		$tbl .= __('according to freenetis') .")</td>";
		$tbl .= "	<td align=\"center\">$member_id</td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>". __('birthday',NULL,1) ."</b></td>";
		$tbl .= "	<td align=\"center\">$birthday</td>";
		$tbl .= "	<td><b>ICQ, Jabber, Skype, ". __('etc') ."…</b></td>";
		$tbl .= "	<td align=\"center\">$contact_info</td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>".__('variable symbols',NULL,1) ."</b></td>";
		$tbl .= "	<td align=\"center\">".implode("<br />", $variable_symbols)."</td>";
		$tbl .= "	<td><b>". __('phones',NULL,1) ."<b/></td>";
		$tbl .= "	<td align=\"center\">".implode("<br />", $phones)."</td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>".__('Subnet',NULL,1)."</b></td>";
		$tbl .= "	<td align=\"center\">$subnet_name</td>";
		$tbl .= "	<td></td>";
		$tbl .= "	<td align=\"center\"></td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>". __('entrance date',NULL,1) ."</b></td>";
		$tbl .= "	<td align=\"center\">$entrance_date</td>";
		$tbl .= "	<td><b>". __('engineers',NULL,1) .":</b></td>";
		$tbl .= "	<td align=\"center\">$engineers</td>";
		$tbl .= "</tr>";
		$tbl .= "</table>";
		
		$page .= $tbl;
		
		$page .= '<div style="text-align:left">';
		$page .= $this->settings->get('registration_license');
		$page .= "</div>";
		
		$page .= '<br><p style="text-align:right;font-size:1.1em">'.__('signature of applicant member').' : ........................................</p>';
		
		$page .= '<p style="font-size:1.2em">'.__('decision Counsil about adoption of member').'</p>';
		
		$page .= '<p style="text-align:left">'.__('Member adopted on').
				':    .........................................</p>';
		
		$page .= '<p style="text-align:left">'.__('signature and stamp').
				':    .........................................</p>';
		
		$page .= "</div></div>";
		$page .= "</body>";
		$page .= "</html>";
		die($page);
	}

	/**
	 * Function to export registration of member to pdf-format
	 * 
	 * @author Michal Kliment
	 * @param integer $member_id	id of member to export
	 */
	private function registration_pdf_export($member_id)
	{
		// no parameter
		if (!isset($member_id))
			Controller::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// record doesn't exist
		if ($member->id == 0)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view(get_class($this), 'members', $member_id))
			Controller::error(ACCESS);
		
		require_once(APPPATH.'vendors/tcpdf/tcpdf.php');
		require_once(APPPATH.'vendors/tcpdf/config/lang/eng.php');

		// create new PDF document
		$pdf = new TCPDF(
				PDF_PAGE_ORIENTATION, PDF_UNIT,
				PDF_PAGE_FORMAT, true, 'UTF-8', false
		);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Michal Kliment');
		$pdf->SetTitle(__('export of registration').' - '.$member->name);

		// remove default header/footer
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		//set margins
		$pdf->SetMargins(0, 0, 0);
		$pdf->SetFooterMargin(0);

		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		//set some language-dependent strings
		$pdf->setLanguageArray($l);

		// ---------------------------------------------------------

		// set font
		$pdf->SetFont('freemono', 'b', 10);

		// add a page
		$pdf->AddPage();

		// -------------------- LOGO -------------------------------
		$logo = Settings::get('registration_logo');
		
		if (file_exists($logo))
		{
			$pdf->writeHTML(
					'<img src="'.substr($logo, strlen($_SERVER['DOCUMENT_ROOT'])).'" width=274 height=101>',
					true, false, false, false, ''
			);
		}
		else	//if logo doesn't exist, insert only blank div
		{
			$pdf->writeHTML(
					'<div width=274 height=101>',
					true, false, false, false, ''
			);
		}

		// --------------- INFO ABOUT ASSOCIATION -----------------

		$pdf->SetTextColor(185, 185, 185);

		$a_member = new Member_Model(1);

		$bank_account_model = new Bank_account_Model();
		$a_bank_account = $bank_account_model->get_assoc_bank_accounts()->current();

		$pdf->SetXY(98, 9.7);
		$pdf->Write(10, $a_member->name);

		$pdf->SetXY(98, 13.7);
		$pdf->Write(
				10, __('organization identifier').
				': '.$a_member->organization_identifier
		);

		$pdf->SetXY(98, 17.7);
		$pdf->Write(
				10, __('account number').': '
				.$a_bank_account->account_number
		);

		$pdf->SetXY(98, 21.7);
		$pdf->Write(
				10, $a_member->address_point->street->street.' '.
				$a_member->address_point->street_number
		);

		$pdf->SetXY(98, 25.7);
		$pdf->Write(
				10, $a_member->address_point->town->zip_code .' '.
				$a_member->address_point->town->town
		);

		// --------------------- MAIN TITLE -------------------------

		$pdf->SetFont('dejavusans', 'b', 14);
		$pdf->SetTextColor(0, 0, 0);

		$pdf->SetXY(41, 36.7);
		$pdf->Write(
				10, __('Request for membership'). ' – '
				. __('registration in association')
		);

		// ----------------------- INFO ----------------------------

		$pdf->SetFont('dejavusans', 'b', 9);

		$pdf->SetXY(0, 47.7);

		$pdf->SetLeftMargin(24);
		$pdf->SetRightMargin(24);

		$pdf->writeHTML(
				$this->settings->get('registration_info'),
				true, false, true, false, ''
		);

		$pdf->Ln();

		// ----------- TABLE WITH INFORMATION ABOUT MEMBER --------

		$pdf->SetFillColor(255, 255, 255);

		$pdf->SetLeftMargin(20);
		$pdf->SetRightMargin(20);

		$member_name = $member->name;
		
		$street = $member->address_point->street->street.' '
				.$member->address_point->street_number;
		
		$town = $member->address_point->town->town;
		
		if ($member->address_point->town->quarter != '') 
			$town .= '-'.$member->address_point->town->quarter;
		
		$zip_code = $member->address_point->town->zip_code;
		
		$variable_symbol_model = new Variable_Symbol_Model();
		$account_model = new Account_Model();
		$account_id = $account_model->where('member_id',$member_id)->find()->id;
		
		$variable_symbols = array();
		$var_syms = $variable_symbol_model->find_account_variable_symbols($account_id);
		
		foreach ($var_syms as $var_sym)
		{
			$variable_symbols[] = $var_sym->variable_symbol;
		}	
		
		$entrance_date = date::pretty($member->entrance_date);

		$user_model = new User_Model();
		
		$user = $user_model->where('member_id',$member_id)
				->where('type',User_Model::MAIN_USER)
				->find();
		
		$emails = $user->get_user_emails($user->id);
		$email = '';
		if ($emails && $emails->current())
		{
			$email = $emails->current()->email;
		}
		$birthday = date::pretty($user->birthday);

		$enum_type_model = new Enum_type_Model();
		$types = $enum_type_model->get_values(Enum_type_Model::CONTACT_TYPE_ID);
		$contact_model = new Contact_Model();
		$contacts = $contact_model->find_all_users_contacts($user->id);
		$phone_id = $enum_type_model->get_type_id('Phone');
		$icq_id = $enum_type_model->get_type_id('ICQ');
		$msn_id = $enum_type_model->get_type_id('MSN');
		$jabber_id = $enum_type_model->get_type_id('Jabber');
		$skype_id = $enum_type_model->get_type_id('Skype');
		$phones = array();
		$arr_contacts = array();
		
		foreach ($contacts as $contact)
		{
			if ($contact->type == $phone_id)
			{
			    $phones[] = $contact->value;
			}
			else if($contact->type == $icq_id OR
					$contact->type == $msn_id OR
					$contact->type == $jabber_id OR
					$contact->type == $skype_id)
			{
			    $arr_contacts[] = $types[$contact->type].': '.$contact->value;
			}
		}
		
		$contact_info = implode('<br />', $arr_contacts);

		$device_engineer_model = new Device_engineer_Model();
		$device_engineers = $device_engineer_model->get_engineers_of_user($user->id);
		$arr_engineers = array();
		
		foreach ($device_engineers as $device_engineer)
		{
		    $arr_engineers[] = $device_engineer->surname;
		}
		
		$engineers = (count($arr_engineers)) ? implode(', ',$arr_engineers) : $member->user->surname;
		
		$subnet = new Subnet_Model();
		$subnet = $subnet->get_subnet_of_user($user->id);
		$subnet_name = isset($subnet->name) ? $subnet->name : '';
		
		$tbl = "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\" width=\"100%\">";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>". __('name',NULL,1) .", ";
		$tbl .= __('surname',NULL,1) .",<br /> ";
		$tbl .= __('title',NULL,1) ."</b></td>";
		$tbl .= "	<td align=\"center\">$member_name</td>";
		$tbl .= "	<td>". __('email address') ."</td>";
		$tbl .= "	<td align=\"center\">$email</td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>". __('address of connecting place',NULL,1);
		$tbl .= "</b> (". strtolower(__('street',NULL,1)) .", ";
		$tbl .= __('street_number',NULL,1) .", ";
		$tbl .= __('zip code') .", ". __('town',NULL,1) .")</td>";
		$tbl .= "	<td align=\"center\">$street<br />$town<br />$zip_code</td>";
		$tbl .= "	<td><b>". __('id of member') ."</b><br /> (";
		$tbl .= __('according to freenetis') .")</td>";
		$tbl .= "	<td align=\"center\"><br />$member_id</td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td>". __('birthday',NULL,1) ."</td>";
		$tbl .= "	<td align=\"center\">$birthday</td>";
		$tbl .= "	<td>ICQ, Jabber, Skype, ". __('etc') ."…</td>";
		$tbl .= "	<td align=\"center\">$contact_info</td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>".__('variable symbols',NULL,1) ."</b></td>";
		$tbl .= "	<td align=\"center\">".implode("<br />", $variable_symbols)."</td>";
		$tbl .= "	<td>". __('phones',NULL,1) ."</td>";
		$tbl .= "	<td align=\"center\">".implode("<br />", $phones)."</td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>".__('Subnet',NULL,1)."</b></td>";
		$tbl .= "	<td align=\"center\">$subnet_name</td>";
		$tbl .= "	<td></td>";
		$tbl .= "	<td align=\"center\"></td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>". __('entrance date',NULL,1) ."</b></td>";
		$tbl .= "	<td align=\"center\">$entrance_date</td>";
		$tbl .= "	<td><b>". __('engineers',NULL,1) .":</b></td>";
		$tbl .= "	<td align=\"center\">$engineers</td>";
		$tbl .= "</tr>";
		$tbl .= "</table>";

		$pdf->writeHTML($tbl, true, false, false, false, '');

		// ----------------- LICENSE -----------------------------

		$pdf->SetFont('dejavusans', 'B', 10);
		$pdf->SetXY(0, 142.7);

		$pdf->SetLeftMargin(24);
		$pdf->SetRightMargin(24);

		$pdf->writeHTML($this->settings->get('registration_license'));

		// ------------ SIGNATURE OF MEMBER ---------------------

		$pdf->Ln();
		$pdf->SetX(88);
		$pdf->SetRightMargin(14);
		$pdf->SetFont('dejavusans', 'BU', 10);
		$pdf->Write(10, __('signature of applicant member').' :');
		$pdf->SetFont('dejavusans', 'B', 10);
		$pdf->Write(10, ' ........................................');

		// -------------------- DECISION OF COUNSIL ------------

		$pdf->Ln();
		$pdf->SetX(68);
		$pdf->SetFont('dejavusans', 'B', 11);
		$pdf->Write(10, __('decision Counsil about adoption of member'));

		$pdf->Ln();
		$pdf->SetFont('dejavusans', 'B', 10);
		$pdf->Write(
				10, __('Member adopted on').
				':    .........................................'
		);

		$pdf->Ln();
		$pdf->Write(
				10, __('signature and stamp').
				':    .........................................'
		);

		// Close and output PDF document
		$pdf->Output(
				url::title(__('registration')).
				'-'.url::title($member->name).'.pdf', 'D'
		);

	}

	/**
	 * Checks if username already exists.
	 * 
	 * @param string $input new username
	 */
	public static function valid_username($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$user_model = new User_Model();
		
		if ($user_model->username_exist($input->value) && !trim($input->value)=='')
		{
			$input->add_error('required', __('Username already exists in database'));
		}
		else if (!preg_match("/^[a-z][a-z0-9]*[_]{0,1}[a-z0-9]+$/", $input->value))
		{
			$input->add_error('required', __(
					'Login must contains only a-z and 0-9 and starts with literal.'
			));
		}
	}

	/**
	 * Checks validity of phone number.
	 * 
	 * @param $input new phone number
	 */
	public function valid_phone($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$user_model=new User_Model();
		$value = trim($input->value);
		
		if (!preg_match("/^[0-9]{9,9}$/",$value))
		{
			$input->add_error('required', __('Bad phone format.'));
		}
		else if ($user_model->phone_exist($value))
		{
			$input->add_error('required', __('Phone already exists in database.'));
		}
	}

	/**
	 * Check validity of variable symbol
	 *
	 * @param object $input 
	 */
	public function valid_var_sym($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$value = trim($input->value);
		
		$variable_symbol_model = new Variable_Symbol_Model();
		
		$total = $variable_symbol_model->get_variable_symbol_id($value);

		if (!preg_match("/^[0-9]{1,10}$/", $value))
		{
			$input->add_error('required', __('Bad variable symbol format.'));
		}
		else if ($total)
		{
			$input->add_error('required', __(
					'Variable symbol already exists in database.'
			));
		}
	}

	/**
	 * Entrance has to be before current date.
	 * 
	 * @param object $input
	 * @return unknown_type
	 */
	public static function valid_entrance_date($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if ($input->value > time())
		{
			$input->add_error('required', __('Bad entrance date.'));
		}
	}

	/**
	 * Leaving has to be after entrance.
	 * 
	 * @param object $input
	 * @return unknown_type
	 */
	public function valid_leaving_date($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$entrance = $this->input->post('entrance_date');
		
		$time = mktime(
				0, 0, 0, $entrance['month'],
				$entrance['day'], $entrance['year']
		);
		
		if ($input->value <= $time)
		{
			$input->add_error('required', __(
					'Member cannot left association before entrance.'
			));
		}
	}

	/**
	 * Function checks validity of member type.
	 * 
	 * @param object $input
	 * @return unknown_type
	 */
	public function valid_member_type($input= NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$enum = new Enum_type_Model();
		
		if ($this->input->post('end_membership') &&
			$input->value != $enum->get_type_id('Former member'))
		{
			$input->add_error('required', __(
					'Membership can be ended only to former member.'
			));
		}
		else if (!$this->input->post('end_membership') &&
				$input->value == $enum->get_type_id('Former member'))
		{
			$input->add_error('required', __(
					'Member cannot be former, if his membership was not ended.'
			));
		}
	}

	/**
	 * Callback function to validate docimile street number
	 *
	 * @author Michal Kliment
	 * @param object $input
	 */
	public function valid_docimile_street_number ($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if ($this->input->post('use_domicile') == 1 && $input->value == '')
		{
			$input->add_error('required', __('This information is required.'));
		}
	}
	
	/**
	 * Static function for creating filter form
	 * due to this filter is used in multiple controllers
	 * 
	 * @return \Filter_form
	 */
	public static function create_filter_form()
	{
		$enum_type_model = new Enum_type_Model();
		$town_model = new Town_Model();
		$street_model = new Street_Model();
		
		// filter form
		$filter_form = new Filter_form('m');
		
		$filter_form->add('name')
				->callback('json/member_name');
		
		$filter_form->add('id')
				->type('number');

		$filter_form->add('type')
				->type('select')
				->values(
					$enum_type_model->get_values(
						Enum_type_Model::MEMBER_TYPE_ID
					)
				);

		$filter_form->add('membership_interrupt')
				->type('select')
				->values(arr::bool());
		
		$filter_form->add('balance')
				->table('a')
				->type('number');
		
		$filter_form->add('variable_symbol')
				->table('vs')
				->callback('json/variable_symbol');
		
		$filter_form->add('entrance_date')
				->type('date');
		
		$filter_form->add('leaving_date')
				->type('date');
		
		$filter_form->add('entrance_fee')
				->type('number');
		
		$filter_form->add('comment');
		
		$filter_form->add('registration')
				->type('select')
				->values(arr::bool());
		
		$filter_form->add('organization_identifier')
				->callback('json/organization_identifier');
		
		$filter_form->add('town')
				->type('select')
				->table('t')
				->values(
					array_unique(
						$town_model->select_list('town', 'town')
					)
				);
		
		$filter_form->add('street')
				->type('select')
				->table('s')
				->values(
					array_unique(
						$street_model->select_list('street', 'street')
					)
				);
		
		$filter_form->add('street_number')
				->type('number')
				->table('ap');
		
		$filter_form->add('redirect_type_id')
				->label(__('Redirection'))
				->type('select')
				->values(array
				(
					Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE => __('Membership interrupt'),
					Message_Model::DEBTOR_MESSAGE => __('Debtor'),
					Message_Model::PAYMENT_NOTICE_MESSAGE => __('Payment notice'),
					Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE => __('Unallowed connecting place'),
					Message_Model::USER_MESSAGE => __('User message')
				))->table('ms');
		
		$filter_form->add('whitelisted')
				->label(__('Whitelist'))
				->type('select')
				->table('ip')
				->values(Ip_address_Model::get_whitelist_types());
		
		$filter_form->add('cloud')
				->table('cl')
				->type('select')
				->values(ORM::factory('cloud')->select_list());
		
		return $filter_form;
	}
	
}
