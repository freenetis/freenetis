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
 * Users controller manages user profile settings, list of users, login logs,
 * applicant password, user contacts, etc.
 * 
 * @package Controller
 */
class Users_Controller extends Controller
{

	protected $_user_id = false;

	/**
	 * Redirects to show all
	 */
	public function index()
	{
		url::redirect('users/show_all');
	}
	
	/**
	 * Function shows all users.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'id', $order_by_direction = 'asc',
			$page_word = null, $page = 1)
	{		
		if (!$this->acl_check_view(get_class($this), 'users'))
			Controller::error(ACCESS);

		$filter_form = new Filter_form();
		
		$filter_form->add('id')
				->type('number');
		
		$filter_form->add('name')
				->callback('json/user_name');
		
		$filter_form->add('surname')
				->callback('json/user_surname');
		
		$filter_form->add('login')
				->label(__('Login name'))
				->callback('json/user_login');
        
        $filter_form->add('member_id')
                ->type('number');
		
		$filter_form->add('member_name')
				->type('combo')
				->callback('json/member_name');
		
		$filter_form->add('type')
				->type('select')
				->values(array
				(
					User_Model::MAIN_USER	=> __('Main'),
					User_Model::USER		=> __('Collateral')
				));
		
		$filter_form->add('email')
				->callback('json/user_email');
		
		$filter_form->add('phone')
				->callback('json/user_phone');
		
		$filter_form->add('icq')
				->label('ICQ')
				->callback('json/user_icq');
		
		$filter_form->add('jabber')
				->callback('json/user_jabber');
		
		$filter_form->add('birthday')
				->type('date');

		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'name', 'surname', 'login', 'member_name'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		$model_users = new User_Model();
		
		// hide grid on its first load (#442)
		$hide_grid = Settings::get('grid_hide_on_first_load') && $filter_form->is_first_load();
		
		if (!$hide_grid)
		{
			try
			{
				$total_users = $model_users->count_all_users($filter_form->as_sql());

				if (($sql_offset = ($page - 1) * $limit_results) > $total_users)
					$sql_offset = 0;

				$query = $model_users->get_all_users(
					$sql_offset, (int)$limit_results, $order_by,
					$order_by_direction, $filter_form->as_sql()
				);
			}
			catch (Exception $e)
			{
				if ($filter_form->is_loaded_from_saved_query())
				{
					status::error('Invalid saved query', $e);
					// disable default query (loop protection)
					if ($filter_form->is_loaded_from_default_saved_query())
					{
						ORM::factory('filter_query')->remove_default($filter_form->get_base_url());
					}
					$this->redirect(url_lang::current());
				}
				throw $e;
			}
		}

		$grid = new Grid('users', __('List of all users'), array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 10,
			'base_url'					=> Config::get('lang').'/users/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=>  isset($total_users) ? $total_users : 0,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		if (!$hide_grid)
		{
			// export contacts
			$grid->add_new_button(
					'export/vcard/users' . server::query_string(),
					'Export contacts', array
						(
							'title' => __('Export contacts'),
							'class' => 'popup_link'
						)
			);
		}

		$grid->order_field('id')
				->label('ID');
		
		$grid->order_field('name')
				->label(__('Name'));
		
		$grid->order_field('surname')
				->label(__('Surname'));
		
		$grid->order_field('login')
				->label(__('Username'));
		
		$grid->order_callback_field('member_name')
				->label(__('Member'))
				->callback('callback::member_field');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view(get_class($this), 'users'))
		{
			$actions->add_action('id')
					->icon_action('member')
					->url('users/show')
					->label('Show user');
		}
		
		if ($this->acl_check_view('Devices_Controller', 'devices'))
		{
			$actions->add_action('id')
					->icon_action('devices')
					->url('devices/show_by_user')
					->label('Show devices');
		}
		if (Settings::get('works_enabled') && $this->acl_check_view('Works_Controller', 'work'))
		{
			$actions->add_action('id')
					->icon_action('work')
					->url('works/show_by_user')
					->label('Show works');
		}
		
		if (!$hide_grid)
			$grid->datasource($query);
			
		$view = new View('main');
		$view->breadcrumbs = __('Users');
		$view->title = __('List of all users');
		$view->content = $grid;
		$view->render(TRUE);
	} // end of show_all function
	
	/**
	 * Function shows users of member.
	 * 
	 * @param integer $member_id
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 * @return unknown_type
	 */
	public function show_by_member(
			$member_id = NULL, $limit_results = 200, $order_by = 'id',
			$order_by_direction = 'ASC', $page_word = null, $page = 1)
	{
		// bad parameter
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// member doesn't exist
		if (!$member->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view(get_class($this), 'users', $member->id))
			Controller::error(ACCESS);

		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');

		// parameters control
		$allowed_order_type = array
		(
			'street', 'street_number', 'town', 'ZIP_code', 'type', 'name',
			'surname', 'login', 'birthday', 'comment', 'phone', 'email', 'id',
			'members_id'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		$model_users = new User_Model();

		$total_users = $model_users->count_all_users_by_member($member_id);

		if (($sql_offset = ($page - 1) * $limit_results) > $total_users)
			$sql_offset = 0;

		$query = $model_users->get_all_users_of_member(
				$member_id, $sql_offset, (int) $limit_results, $order_by,
				$order_by_direction
		);

		$grid = new Grid('users',__('List of users of member') . ' ' . $member->name, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 200,
			'selector_min'				=> 200,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang') . '/users/show_by_member/'
										. $member_id . '/' . $limit_results . '/'
										. $order_by . '/' . $order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_users,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'variables'					=> $member_id . '/',
			'url_array_ofset'			=> 1,
			'query_string'				=> $this->input->get(),
		));

		if ($this->acl_check_new(get_class($this), 'users', $member_id))
		{
			$grid->add_new_button('users/add/' . $member_id, __('Add new user'));
		}
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_field('name');
		
		$grid->order_field('surname');
		
		$grid->order_field('login')
				->label(__('Username'));
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view(get_class($this), 'users', $member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('users/show');
		}
		
		if ($this->acl_check_edit(get_class($this), 'users', $member_id))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('users/edit');
		}
		
		$grid->datasource($query);

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/' . $member->id, 
						"ID $member->id - $member->name",
						$this->acl_check_view(
								'Members_Controller', 'members', $member->id
						)
				)->enable_translation()
				->text('Users');

		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = __('List of users of member') . ' ' . $member->name;
		$view->content = $grid;
		$view->render(TRUE);
	} 

	/**
	 * Function deletes user.
	 * 
	 * @param integer $user_id
	 */
	public function delete($user_id = NULL)
	{		
		if (!isset($user_id))
			Controller::warning(PARAMETER);
		
		$user_model = new User_Model($user_id);
		
		if (!$user_model || !$user_model->id)
			Controller::error(RECORD);
		
		// cannot delete main user (fixes #384)
		if ($user_model->id == User_Model::ASSOCIATION)
			Controller::warning(PARAMETER);
		
		$member_id = $user_model->member_id;
		
		// access rights
		if (!$this->acl_check_delete(get_class($this), 'users', $member_id))
			Controller::error(ACCESS);
		
		// link to location after delete
		$linkback = url_lang::base() . 'members/show/' . $member_id;
		
		// user of "member" type cannot be deleted
		if ($user_model->type == User_Model::MAIN_USER)	
		{
			status::warning('Primary user of member cannot be deleted.');
			url::redirect($linkback);				
		}
		
		if ($user_model->count_dependent_items($user_id) > 0)
		{
			status::warning('User cannot be deleted, he has some dependent items in database.');
			url::redirect($linkback);	
		}

		$user_model->delete_watchers($user_id);

		if ($user_model->delete())
		{
			status::success('User has been successfully deleted.');
		}
		else
		{
			status::error('Error - cant delete user.');
		}
		
		url::redirect($linkback);
	} // end of delete function
	
	/**
	 * Function edits user.
	 * 
	 * @param integer $user_id
	 */
	public function edit($user_id = NULL)
	{
		if (!isset($user_id))
			Controller::warning(PARAMETER);

		$user = new User_Model($user_id);
		
		if (!$user->id)
			Controller::error(RECORD);
		
		$this->_user_id = $user_id;
		
		//check if logged user have access right to edit this user
		if(!$this->acl_check_edit(get_class($this),'users',$user->member_id))
			Controller::error(ACCESS);
			
		// check if user is not member-type and logged user have access right 
		// to edit member of user
		if ($user->type != User_Model::MAIN_USER &&
			$this->acl_check_edit(get_class($this),'member', $user->member_id))
		{
			$arr_members = ORM::factory('member')->select_list('id', 'name');
		}
		else
		{
			$arr_members[$user->member_id] = $user->member->name;
		}
			
		$form = new Forge('users/edit/'.$user_id);

		$form->group('Basic information');
		
		$form->dropdown('member_id')
				->label('Member name')
				->options($arr_members)
				->selected($user->member_id)
				->style('width:200px');
		
		if ($this->acl_check_edit(get_class($this),'login',$user->member_id))
		{
			$form->input('username')
					->rules('required|length[3,50]')
					->callback(array($this, 'valid_username'))
					->value($user->login);
		}
					 
		$form->input('pre_title')
				->rules('length[3,40]')
				->value($user->pre_title);
		
		$form->input('name')
				->rules('required|length[3,30]')
				->value($user->name);
		
		$form->input('middle_name')
				->rules('length[3,30]')
				->value($user->middle_name);
		
		$form->input('surname')
				->rules('required|length[3,60]')
				->value($user->surname);
		
		$form->input('post_title')
				->rules('length[3,30]')
				->value($user->post_title);
		
		$form->group('Additional information');
		
		$form->date('birthday')
				->label('Birthday')
				->years(date('Y')-100, date('Y'))
				->rules('required')
				->value(strtotime($user->birthday));

		if (!Settings::get('users_birthday_empty_enabled'))
		{
			$form->date('birthday')
					->label('Birthday')
					->years(date('Y')-100, date('Y'))
					->rules('required')
					->value(strtotime($user->birthday));
		}
		else
		{
			$form->date('birthday')
					->label('Birthday')
					->years(date('Y')-100, date('Y'))
					->value(strtotime($user->birthday));
		}
		
		if ($this->acl_check_edit(get_class($this), 'comment', $user->member_id))
		{
			$form->textarea('comment')
					->rules('length[0,250]')
					->value($user->comment);
		}
		
		$form->submit('Edit');
		
		special::required_forge_style($form, ' *', 'required');
		
		if($form->validate())
		{
			$form_data = $form->as_array();
			
			$user_data = new User_Model;
			$user_data->find($user_id);

			if ($this->acl_check_edit(get_class($this),'login',$user_data->member_id))
			{
				$user_data->login = $form_data['username'];
			}

			if (empty($form_data['birthday']))
			{
				$user_data->birthday = NULL;
			}
			else
			{
				$user_data->birthday = date("Y-m-d", $form_data['birthday']);
			}

			$user_data->pre_title = $form_data['pre_title'];
			$user_data->name = $form_data['name'];
			$user_data->middle_name = $form_data['middle_name'];
			$user_data->surname = $form_data['surname'];
			$user_data->post_title = $form_data['post_title'];

			if ($this->acl_check_edit(get_class($this),'comment',$user->member_id))
				$user_data->comment = $form_data['comment'];
			
			if ($user_data->save())
			{
				status::success('User has been successfully updated.');
			}
			else
			{
				status::error('Error - cant update user.');
			}
			$this->redirect('users/show/'.$user_id);
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
									'Members_Controller','members', $user->member->id
							)
					)->enable_translation()
					->link('users/show_by_member/' . $user->member_id, 'Users',
							$this->acl_check_view(
									get_class($this), 'users', $user->member_id
							)
					)->disable_translation()
					->link('users/show/'.$user->id,
							"$user->name $user->surname ($user->login)",
							$this->acl_check_view(
									get_class($this),'users', $user->member_id
							)
					)->enable_translation()
					->text('Edit');
		
			$view = new View('main');
			$view->title = __('Editing of user');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Editing of user').' '.$user->pre_title.
					' '.$user->name.' '.$user->middle_name.
					' '.$user->surname.' '.$user->post_title;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	} // end of edit function
	
	/**
	 * Function shows user.
	 * 
	 * @param integer $user_id
	 */
	public function show($user_id = NULL)
	{
		if (!isset($user_id))
			Controller::warning(PARAMETER);
		
		$user = new User_Model($user_id);
		
		if (!$user->id)
			Controller::error(RECORD);
		
		if (!$this->acl_check_view(get_class($this), 'users', $user->member_id))
			Controller::error(ACCESS);
		
		$model_contacts = new Contact_Model();
		$contacts = $model_contacts->find_all_users_contacts($user_id);
		$enum_type_model = new Enum_type_Model();
		$arr_contact_types = array();
		
		foreach($contacts as $i => $contact)
		{
			$arr_contact_types[$i] = $enum_type_model->get_value($contact->type);
		}
		
		// voip is enabled
		if (Settings::get('voip_enabled'))
		{
		    $voip_sip = new Voip_sip_Model();
		    $voip = $voip_sip->get_record_by_user_limited($user_id);

		    if ($voip->count() == 0)
		    {
			    $voip = '<span style="color:red;">'.__('Nonactive').'</span>  - '
					    .html::anchor('voip/add/'.$user_id, __('Activate'));
		    }
		    else
		    {
			    $voip = html::anchor(
					    'voip/show/'.$voip->current()->user_id,
					    $voip->current()->name
			    );
		    }
		}
		
		$aro_groups = $user->get_aro_groups_of_user($user_id);
		
		
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Devices_Controller', 'admin'))
		{
			// grid with lis of users
			$admin_devices_grid = new Grid('members', null, array
			(
				'separator'		   		=> '<br /><br />',
				'use_paginator'	   		=> false,
				'use_selector'	   		=> false,
			));

			if ($this->acl_check_new('Devices_Controller', 'admin'))
			{
				$admin_devices_grid->add_new_button(
						'device_admins/edit_user/'.$user_id, __('Edit')
				);
			}

			$admin_devices_grid->callback_field('device_id')
					->label(__('Device'))
					->callback('callback::device_field');

			$admin_devices_grid->link_field('user_id')
					->link('users/show', 'user_name')
					->label('User');

			if ($this->acl_check_delete('Devices_Controller', 'admin'))
			{
				$admin_devices_grid->grouped_action_field()
						->add_action()
						->icon_action('delete')
						->url('device_admins/delete')
						->label('Remove')
						->class('delete_link');
			}

			$admin_devices_grid->datasource(
					ORM::factory('device_admin')->get_all_devices_by_admin($user->id)
			);

			// grid with lis of users
			$engineer_devices_grid = new Grid(url_lang::base().'members', null, array
			(
				'separator'		   		=> '<br /><br />',
				'use_paginator'	   		=> false,
				'use_selector'	   		=> false,
			));

			$engineer_devices_grid->callback_field('device_id')
					->label(__('Device'))
					->callback('callback::device_field');

			$engineer_devices_grid->link_field('user_id')
					->link('users/show', 'user_name')
					->label('User');

			if ($this->acl_check_delete('Devices_Controller', 'admin'))
			{
				$engineer_devices_grid->grouped_action_field()
						->add_action()
						->icon_action('delete')
						->url('device_engineers/delete')
						->label('Remove')
						->class('delete_link');
			}

			$engineer_devices_grid->datasource(
					ORM::factory('device_engineer')->get_all_devices_by_engineer($user->id)
			);
		}
		
		// grid with lis of users
		$comments_grid = new Grid('members', null, array
		(
			'separator'		   		=> '<br /><br />',
			'use_paginator'	   		=> false,
			'use_selector'	   		=> false,
		));
		
		$comments_grid->field('text')
				->label(__('Comment'))
				->class('comment');
		
		$comments_grid->field('datetime')
				->label(__('Time'));
		
		$comments_grid->callback_field('type')
				->label(__('To'))
				->callback('callback::comment_to_field');
			
		$actions = $comments_grid->grouped_action_field();

		$actions->add_conditional_action()
				->icon_action('edit')
				->url('comments/edit')
				->condition('is_own');

		$actions->add_conditional_action()
				->icon_action('delete')
				->url('comments/delete')
				->condition('is_own')
				->class('delete_link');
		
		$comments_grid->datasource(
				ORM::factory('comment')->get_all_comments_by_user($user->id)
		);
		

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
								get_class($this), 'users', $user->member_id
						)
				)->text("$user->name $user->surname ($user->login)");

		$view = new View('main');
		$view->title = __('Display user');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->action_logs = action_logs::object_last_modif($user, $user_id);
		$view->content = new View('users/show');
		$view->content->user_data = $user;
		$view->content->contacts = $contacts;
		$view->content->contact_types = $arr_contact_types;
		$view->content->voip = Settings::get('voip_enabled') ? $voip : '';
		$view->content->aro_groups = $aro_groups;
		$view->content->admin_devices_grid = @$admin_devices_grid;
		$view->content->engineer_devices_grid = @$engineer_devices_grid;
		$view->content->comments_grid = @$comments_grid;
		$view->render(TRUE);
	} // end of show function
	
	/**
	 * Function adds user.
	 * 
	 * @param integer $member_id
	 */
	public function add($member_id = null)
	{
		if (!$member_id)
			Controller::warning(PARAMETER);
		
		if (!$this->acl_check_new(get_class($this), 'users', $member_id))
			Controller::error(ACCESS);
		
		$member = new Member_Model($member_id);

		$form = new Forge('users/add/'.$member_id);

		$form->group('Basic information');

		if ($this->acl_check_new(get_class($this), 'login', $member_id))
		{
			$form->input('username')
					->rules('required|length[3,50]')
					->callback(array($this, 'valid_username'));
		}

		$form->input('pre_title')
				->rules('length[3,40]');

		$form->input('name')
				->rules('required|length[3,30]');

		$form->input('middle_name')
				->rules('length[3,30]');

		$form->input('surname')
				->rules('required|length[3,60]');

		$form->input('post_title')
				->rules('length[3,30]');

		$form->group('Password');
		
		$pass_min_len = Settings::get('security_password_length');
		
		$form->password('password')
				->rules('required|length['.$pass_min_len.',50]')
				->class('main_password');

		$form->password('confirm_password')
				->rules('required|length['.$pass_min_len.',50]')
				->matches($form->password);

		$form->group('Additional information');

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

		if ($this->acl_check_new(get_class($this),'comment',$member_id))
		{
			$form->textarea('comment')
					->rules('length[0,250]')
					->style('width:350px');
		}

		$form->submit('Add');

		special::required_forge_style($form, ' *', 'required');

		if ($form->validate())
		{
			$form_data = $form->as_array();

			$user_data = new User_Model;
			$user_data->login = $form_data['username'];
			$user_data->password = sha1($form_data['password']);
			$user_data->pre_title = $form_data['pre_title'];
			$user_data->name = $form_data['name'];
			$user_data->middle_name = $form_data['middle_name'];
			$user_data->surname = $form_data['surname'];
			$user_data->post_title = $form_data['post_title'];

			if (empty($form_data['birthday']))
			{
				$user_data->birthday = NULL;
			}
			else
			{
				$user_data->birthday = date("Y-m-d", $form_data['birthday']);
			}

			if (isset($form_data['comment']))
				$user_data->comment = $form_data['comment'];

			$user_data->type = User_Model::USER;
			$user_data->member_id = $member_id;
			$user_data->application_password = security::generate_password();
			$user_data->settings = '';
			$saved = $user_data->save();

			// insert users access rights
			$groups_aro_map = new Groups_aro_map_Model();
			$groups_aro_map->aro_id = $user_data->id;
			$groups_aro_map->group_id = Aro_group_Model::REGULAR_MEMBERS;
			$saved = $saved && $groups_aro_map->save();

			unset($form_data);

			if ($saved)
			{
				// send welcome message to user
				Mail_message_Model::create(
						Member_Model::ASSOCIATION, $user_data->id,
						mail_message::format('welcome_subject'),
						mail_message::format('welcome'), 1
				);

				status::success('User has been successfully added.');
			}
			else
			{
				status::error('Error - cant add new user.');
			}
			url::redirect('members/show/'.$member_id);
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller','members'))
					->disable_translation()
					->link('members/show/' . $member->id, 
							"ID ".$member->id." - ".$member->name,
							$this->acl_check_view(
									'Members_Controller','members', $member->id
							)
					)->enable_translation()
					->link('users/show_by_member/' . $member_id, 'Users',
							$this->acl_check_view(
									get_class($this), 'users', $member_id
							)
					)->text('Add');

			$view = new View('main');
			$view->title = __('Add new user');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Add new user');
			$view->content->link_back = '';
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
		
	} // end of add function

	/**
	 * Shows work of user
	 *
	 * @author Michal Kliment
	 * @param integer $work_id
	 */
	public function show_work ($work_id = NULL)
	{
		$controller = new Works_Controller();
		$controller->show($work_id);
	}
	
	/**
	 * Shows work report of user
	 *
	 * @author Michal Kliment
	 * @param integer $work_id
	 */
	public function show_work_report ($work_report_id = NULL)
	{
		$controller = new Work_reports_Controller();
		$controller->show($work_report_id);
	}
	
	/**
	 * Shows request of user
	 *
	 * @author Michal Kliment
	 * @param integer $work_id
	 */
	public function show_request ($request_id = NULL)
	{
		$controller = new Requests_Controller();
		$controller->show($request_id);
	}

	/**
	 * Function changes password of user.
	 * 
	 * @param integer $user_id
	 */
	public function change_password($user_id = null)
	{
		if (!isset($user_id))
			Controller::warning(PARAMETER);
		
		$user = new User_Model($user_id);
		
		if (!$user->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_edit(get_class($this), 'password', $user->member_id) ||
				($user->is_user_in_aro_group($user->id, Aro_group_Model::ADMINS) &&
					$user->id != $this->user_id
				))
			Controller::error(ACCESS);

		$this->_user_id = $user_id;

		$form = new Forge('users/change_password/' . $user_id);
		
		// check if logged user has right to edit all passwords except his own
		if (!$this->acl_check_edit(get_class($this), 'password') ||
			$user->id == $this->session->get('user_id'))
		{
			$form->password('oldpassword')
					->label(__('Old password') . ':')
					->rules('required')
					->callback(array($this, 'check_password'));
		}
		
		$pass_min_len = Settings::get('security_password_length');
		
		$form->password('password')
				->label(__('New password') . ':&nbsp;' . help::hint('password'))
				->rules('required|length['.$pass_min_len.',50]')
				->class('main_password');
		
		$form->password('confirm_password')
				->label(__('Confirm new password') . ':')
				->rules('required|length['.$pass_min_len.',50]')
				->matches($form->password);
		
		$form->submit('submit')
				->value(__('Change'));
		
		special::required_forge_style($form, ' *', 'required');

		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);
			$user = new User_Model($user_id);
			$user->set_logger(FALSE);
			$user->password = sha1($form_data['password']);

			if ($user->save())
			{
				status::success('Password has been successfully changed.');
			}
			else
			{
				status::error('Error - cant change password.');
			}
			
			$this->redirect('users/change_password/' . $user->id);
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
									get_class($this), 'users', $user->member_id
							)
					)->disable_translation()
					->link('users/show/'.$user->id,
							"$user->name $user->surname ($user->login)",
							$this->acl_check_view(
									get_class($this),'users', $user->member_id
							)
					)->enable_translation()
					->text('Change password');

			$view = new View('main');
			$view->title = __('Change password');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Change password');
			$view->content->link_back = '';
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	} // end of change password function

	/**
	 * Function changes application password of user.
	 *
	 * @param integer $user_id
	 */
	public function change_application_password($user_id = null)
	{
		if (!isset($user_id))
			Controller::warning(PARAMETER);
		
		$user = new User_Model($user_id);

		if (!$user->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_edit(
				get_class($this), 'application_password', $user->member_id
			))
		{
			Controller::error(ACCESS);
		}

		$this->_user_id = $user_id;

		$form = new Forge('users/change_application_password/' . $user_id);

		$form->password('password')
				->label(__('New password') . ':')
				->rules('required|length[3,50]');

		$form->password('confirm_password')
				->label(__('Confirm new password') . ':')
				->rules('required|length[3,50]')
				->matches($form->password);

		$form->submit('submit')->value(__('Change'));

		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);
			$user = new User_Model($user_id);
			$user->set_logger(FALSE);
			$user->application_password = $form_data['password'];

			if ($user->save())
			{
				status::success('Password has been successfully changed.');
			}
			else
			{
				status::error('Error - cant change password.');
			}

			$this->redirect(
					url_lang::base() . 'users/change_application_password/' .
					$user->id
			);
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
									get_class($this), 'users', $user->member_id
							)
					)->disable_translation()
					->link('users/show/'.$user->id,
							"$user->name $user->surname ($user->login)",
							$this->acl_check_view(
									get_class($this),'users', $user->member_id
							)
					)->enable_translation()
					->text('Change application password');

			$view = new View('main');
			$view->title = __('Change application password');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('users/change_application_password');
			$view->content->form = $form->html();
			$view->content->user_id = $user_id;
			$view->content->member_id = $user->member_id;
			$view->content->password = $user->application_password;
			$view->render(TRUE);
		}
	} // end of change password function
	
	/**
	 * Gets list of users
	 */
    public function get_users()
	{
		$q = strtolower($this->input->get('q'));

		if (!$q)
			return;

		$user_model = new User_Model();
		$users = $user_model->get_users($q);

		foreach ($users as $user)
			echo $user->user . "\n";
	}

	/**
	 * Gets list of usernames
	 */
	public function get_usernames()
	{
		$q = strtolower($this->input->get('q'));

		if (!$q)
			return;

		$user_model = new User_Model();
		$usernames = $user_model->get_usernames($q);

		foreach ($usernames as $username)
			echo $username->username . "\n";
	}
	
	/* *******************************************
	*********** CALLBACK FUNCTIONS **************
	******************************************** */
	
	/**
	 * Checks password
	 *
	 * @param object $input 
	 */
	public function check_password($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$user_model = new User_Model();
		$user_model->select('password')->where('id', $this->_user_id)->find();
		
		if ($user_model->password != sha1($input->value) ||
			trim($input->value) == '')
		{
			$error = TRUE;
			
			// see Settings for exclamation
			if (Settings::get('pasword_check_for_md5'))
			{
				$error = ($user_model->password != md5($input->value));
			}
			
			if ($error)
			{
				$input->add_error('required', __('Wrong password.'));
			}
		}
	}

	/**
	 * Check validity of usename
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
		
		$user_model = new User_Model();
		
		$username_regex = Settings::get('username_regex');
		
		if (preg_match($username_regex, $input->value) == 0)
		{
			$input->add_error('required', __(
					'Login must contains only a-z and 0-9 and starts with literal.'
			));
		}
		else if ($user_model->username_exist($input->value, $this->_user_id) ||
				 trim($input->value) == '')
		{
			$input->add_error('required', __(
					'Username already exists in database.'
			));
		}
	}
	
}
