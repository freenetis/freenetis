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

require_once APPPATH."libraries/vtwsclib/Vtiger/WSClient.php";

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
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'registration', 'name', 'street','redirect',  'street_number',
			'town', 'quarter', 'ZIP_code', 'entrance_fee',
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
		
		// hide grid on its first load (#442)
		$hide_grid = Settings::get('grid_hide_on_first_load') && $filter_form->is_first_load();
		
		if (!$hide_grid)
		{
			try
			{
				$total_members = $model_members->count_all_members($filter_form->as_sql());

				// limit check
				if (($sql_offset = ($page - 1) * $limit_results) > $total_members)
					$sql_offset = 0;

				// query data
				$query = $model_members->get_all_members(
						$sql_offset, $limit_results, $order_by, $order_by_direction,
						$filter_form->as_sql()
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
			'total_items'				=> isset($total_members) ? $total_members : 0,
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

		if (!$hide_grid && $this->acl_check_edit('Members_Controller', 'registration'))
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

		if (!$hide_grid && $this->acl_check_view(get_class($this), 'members'))
		{
			// export contacts
			$grid->add_new_button(
					'export/vcard/members' . server::query_string(),
					'Export contacts', array
						(
							'title' => __('Export contacts'),
							'class' => 'popup_link'
						)
			);

			// csv export of members
			$grid->add_new_button(
					'export/csv/members' . server::query_string(),
					'Export to CSV', array
					(
						'title' => __('Export to CSV'),
						'class' => 'popup_link'
					)
			);

			if (module::e('notification') && 
				$this->acl_check_new('Notifications_Controller', 'members'))
			{
				$grid->add_new_button(
						'notifications/members/' . server::query_string(),
						'Notifications'
				);
			}
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

		$grid->order_callback_field('type', 'type_name')
				->callback('callback::member_type_field');

		$grid->order_field('name');

		$grid->order_field('street');

		$grid->order_field('street_number');

		$grid->order_field('town');

		if (Settings::get('finance_enabled'))
		{
			$grid->order_callback_field('balance')
					->callback('callback::balance_field');
		}

		if (Settings::get('redirection_enabled'))
		{
			$grid->order_callback_field('redirect')
					->label('Redirection')
					->callback('callback::redirect_field');
		}

		if (module::e('notification'))
		{
			$grid->order_callback_field('whitelisted')
					->label('Whitelist')
					->callback('callback::whitelisted_field');
		}

		$actions = $grid->grouped_action_field();

		// action fields
		if ($this->acl_check_view(get_class($this), 'members'))
		{
			$actions->add_action('id')
					->icon_action('member')
					->url('members/show')
					->label('Show member');
		}
		
		if (Settings::get('finance_enabled') && $this->acl_check_view('Accounts_Controller', 'transfers'))
		{
			$actions->add_action('aid')
					->icon_action('money')
					->url('transfers/show_by_account')
					->label('Show transfers');
		}
		
		if (!$hide_grid)
		{
			// load data
			$grid->datasource($query);
		}

		if ($this->acl_check_delete(get_class($this), 'members'))
		{
			$actions->add_conditional_action('id')
					->condition('is_former_for_more_than_limit_years')
					->icon_action('delete')
					->url('members/delete_former')
					->label('Delete member')
					->class('delete_link');
		}

		if (isset($_POST) && count($_POST) > 1)
		{
			$ids = $_POST["ids"];
			$regs = $_POST["registrations"];

			ORM::factory('member')->update_member_registrations($ids, $regs);

			status::success('Registrations has been successfully updated.');

			url::redirect($_POST['url']);
		}

		$headline = __('List of all members');
		
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
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function applicants(
			$limit_results = 40, $order_by = 'id',
			$order_by_direction = 'ASC', $page_word = 'page', $page = 1,
			$regs = 0)
	{
		// access rights
		if (!$this->acl_check_view(get_class($this),'members'))
			Controller::error(ACCESS);

		$town_model = new Town_Model();
		$street_model = new Street_Model();
		
		// filter form
		$filter_form = new Filter_form('m');
		
		$filter_form->add('name')
				->callback('json/member_name');
		
		$filter_form->add('id')
				->type('number');
		
		$filter_form->add('applicant_connected_from')
				->type('date')
				->label('Connected from');
		
		$filter_form->add('applicant_registration_datetime')
				->type('date')
				->label('Registration time');
		
		$filter_form->add('comment');
		
		$filter_form->add('registration')
				->type('select')
				->values(arr::bool());
		
		$filter_form->add('town')
				->type('select')
				->values(array_unique($town_model->select_list('town', 'town')));
		
		$filter_form->add('street')
				->type('select')
				->values(array_unique($street_model->select_list('street', 'street')));
		
		$filter_form->add('street_number')
				->type('number');

		// gets new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'registration', 'name', 'street', 'street_number', 'town',
			'applicant_connected_from', 'applicant_registration_datetime',
			'comment'
		);
		
		// order by check
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		// load members
		$model_members = new Member_Model();
		$total_members = $model_members->count_all_registered_applicants($filter_form->as_sql());

		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_members)
			$sql_offset = 0;

		// query data
		$query = $model_members->get_registered_applicants(
				$sql_offset, $limit_results, $order_by, $order_by_direction,
				$filter_form->as_sql()
		);
		
		// path to form
		$path = Config::get('lang') . '/members/applicants/' . $limit_results . '/'
				. $order_by . '/' . $order_by_direction.'/'.$page_word.'/'
				. $page.'/'.$regs;

		// grid
		$grid = new Grid(null, null, array
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
			'filter'					=> $filter_form,
			'method'					=> 'get'
		));
		
		// approve applicant checkbox
		$grid->order_form_field('toapprove')
				->callback('callback::member_approve_avaiable')
				->type('checkbox')
				->class('center')
				->label(' ');
		
		$grid->form_submit_value = __('Approve selected applicants');
		
		// database columns - some are commented out because of lack of space
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_field('name');
		
		$grid->order_field('street');
		
		$grid->order_field('street_number');
		
		$grid->order_field('town');
		
		$grid->order_field('applicant_registration_datetime')
				->label('Registration time');
		
		$grid->order_field('applicant_connected_from')
				->label('Connected from');
		
		$grid->order_callback_field('registration')
				->callback('callback::registration_field');
		
		$grid->order_callback_field('comment')
				->callback('callback::limited_text');
		
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
			$actions->add_conditional_action('id')
					->condition('is_applicant_registration')
					->icon_action('member')
					->url('members/approve_applicant')
					->label('Approve application for membership')
					->class('popup_link');
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
				'Registered applicants can be approved using action button (placed in each line)'
		) . '.<br>'. __(
				'Delete applicants for refusing of their request'
		) . '.';
		
		if (isset($_GET) && count(@$_GET) && isset($_GET['toapprove']))
		{
			$this->multiple_applicants_details(@$_GET['toapprove']);
		}
		else
		{
			// view
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('show_all');
			$view->content->description = $desc;
			$view->content->table = $grid;
			$view->content->headline = $headline;
			$view->render(TRUE);
		}
	} // end of registered function

	/**
	 * Form for approving multiple members
	 * 
	 * @param array $selected
	 */
	private function multiple_applicants_details($selected)
	{
		if (!$this->acl_check_edit('Variable_Symbols_Controller', 'variable_symbols') ||
			!$this->acl_check_edit(get_class($this), 'qos_ceil') ||
			!$this->acl_check_edit(get_class($this), 'qos_rate'))
		{
			Controller::error(ACCESS);
		}
		
		if (!Settings::get('finance_enabled'))
		{
			status::warning('Enable financial module before approving applicants.');
			$this->redirect('members/applicants');
		}
		
		if (!Variable_Key_Generator::get_active_driver())
		{
			status::warning('Set variable key generator before approving applicants.');
			$this->redirect('members/applicants');
		}
		
		//form
		$form = new Forge('members/approve_multiple_applicants');
		
		$member = new Member_Model();
		$association = new Member_Model(Member_Model::ASSOCIATION);
		
		$items = array();
		
		// prepare data
		foreach ($selected AS $id)
		{
			$item = new stdClass();
			$item->id = $id;
			$item->name = $member->find($id)->name;
			$item->registration = $member->find($id)->registration;
			
			$items[] = $item;
			
			$form->hidden('toapprove['.$item->id.']')
					->value($item->id);
		}
		
		// create form items
		$form->group('Basic information');
		
		$form->date('entrance_date')
				->label('Entrance date')
				->years(date('Y', strtotime($association->entrance_date)), date('Y'))
				->rules('required')
				->value(time());
		
		$speed_class = new Speed_class_Model();
		$speed_classes = array(NULL => '') + $speed_class->select_list();
		$def_speed_class = $speed_class->get_members_default_class();

		
		$form->submit('Approve');
		
		//description
		$desc = __('Variable symbol will be automaticaly generated for every applicant.');
		
		//grid
		$grid = new Grid('members', __('Selected applicants'), array
		(
			'use_paginator' => false,
			'use_selector' => false
		));
		
		$grid->order_field('name');
		
		$grid->order_callback_field('registration')
				->callback('callback::registration_field');
		
		$grid->datasource($items);
		
		// headline
		$headline = __('Approve selected applicants');
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view(get_class($this),'members'))
				->link('members/applicants', 'Registered applicants',
						$this->acl_check_view(get_class($this),'members'))
				->text($headline);
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('show_all');
		$view->content->table = $grid;
		$view->content->form = $form;
		$view->content->description = $desc;
		$view->content->headline = $headline;
		$view->render(TRUE);
	}
	
	/**
	 * Approves multiple applicants
	 * 
	 * @throws Exception
	 */
	public function approve_multiple_applicants()
	{
		if (!$this->acl_check_edit('Variable_Symbols_Controller', 'variable_symbols') ||
			!$this->acl_check_edit(get_class($this), 'qos_ceil') ||
			!$this->acl_check_edit(get_class($this), 'qos_rate'))
		{
			Controller::error(ACCESS);
		}
		
		if (!isset($_POST) || !isset($_POST['toapprove']) || !isset($_POST['entrance_date']))
		{
			Controller::error(PARAMETER);
		}
		
		$approved_count = 0;
		$selected = @$_POST['toapprove'];
		$date = @$_POST['entrance_date'];
		
		// approve selected applicants
		foreach ($selected AS $applicant_id)
		{
			$member = new Member_Model($applicant_id);
			
			try
			{
				$member->transaction_start();
				
				// change member
				$member->entrance_date = $date;
				
				// get members account
				$account = ORM::factory('account')->where(array
				(
					'member_id' => $member->id,
					'account_attribute_id' => Account_attribute_Model::CREDIT
				))->find();
				
				// generate variable symbol
				$var_sym = Variable_Key_Generator::factory()->generate($member->id);
				
				$vs = new Variable_Symbol_Model();

				$vs_not_unique = $vs->get_variable_symbol_id($var_sym);

				if ($vs_not_unique && $vs_not_unique->id)
				{
					if ($vs_not_unique->account_id != $account->id)
					{
						throw new Exception(__('Variable symbol already exists in database.'));
					}
				}
				else
				{
					$vs->account_id = $account->id;
					$vs->variable_symbol = $var_sym;
					$vs->save_throwable();
				}
				
				// set speed class
				
				// unlock and set to Regular member
				$member->type = Member_Model::TYPE_REGULAR;
				$member->locked = 0;
				
				$member->save_throwable();
				
				// access rights
				$group_aro_map = new Groups_aro_map_Model();
				
				// get main user
				$main_user_id = NULL;
				
				foreach ($member->users as $user)
				{
					if ($user->type == User_Model::MAIN_USER)
					{
						$main_user_id = $user->id;
						break;
					}
				}
				
				if (!$main_user_id)
					throw new Exception('Main user of applicant is missing');
				
				// if is not member yet
				if (!$group_aro_map->exist_row(
						Aro_group_Model::REGULAR_MEMBERS, $main_user_id
					))
				{
					// delete rights of applicant
					$group_aro_map->detete_row(
							Aro_group_Model::REGISTERED_APPLICANTS, $main_user_id
					);							

					// insert regular member access rights
					$groups_aro_map = new Groups_aro_map_Model();
					$groups_aro_map->aro_id = $main_user_id;
					$groups_aro_map->group_id = Aro_group_Model::REGULAR_MEMBERS;
					$groups_aro_map->save_throwable();
					
					// reload messages
					ORM::factory('member')->reactivate_messages($applicant_id);

					// inform new member
					if (module::e('notification'))
					{
						Message_Model::activate_special_notice(
								Message_Model::APPLICANT_APPROVE_MEMBERSHIP,
								$member->id, $this->session->get('user_id'),
								Notifications_Controller::ACTIVATE,
								Notifications_Controller::KEEP
						);
					}
					
					$member->transaction_commit();
				}
				
				$approved_count++;
			}
			catch (Exception $e)
			{
				Log::add_exception($e);
				$member->transaction_rollback();
				// error
				status::error('Applicant for membership cannot be approved', $e);
			}
		}
		
		status::info('Applicants for membership accepted (%d / %d)', TRUE, array($approved_count, count($selected)));
		
		$this->redirect('members/applicants');
	}
	
	/**
	 * Form for approving of member
	 * 
	 * @author Ondřej Fibich
	 * @see #369
	 * @param integer $member_id
	 */
	public function approve_applicant($member_id = NULL)
	{
		// parameter is wrong
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);

		$association = new Member_Model(Member_Model::ASSOCIATION);
		$member = new Member_Model($member_id);
		
		if (!condition::is_applicant_registration($member))
		{
			self::error(RECORD);
		}
		
		if (Settings::get('finance_enabled'))
		{
			$member_fee = new Members_fee_Model();

			$additional_payment_amount = $member_fee->calculate_additional_payment_of_applicant(
					$member->applicant_connected_from, date('Y-m-d')
			);
		}

		// member doesn't exist
		if (!$member->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_new(get_class($this), 'members') ||
			!$this->acl_check_edit(get_class($this), 'entrance_date') ||
			!$this->acl_check_new('Accounts_Controller', 'transfers'))
			Controller::error(ACCESS);
		
		// delete is enabled only on applicants
		if ($member->type != Member_Model::TYPE_APPLICANT)
			Controller::warning(PARAMETER);
		
		// form
		$form = new Forge();
		
		$form->group('Basic information');
		
		$form->date('entrance_date')
				->label('Entrance date')
				->years(date('Y', strtotime($association->entrance_date)), date('Y'))
				->rules('required')
				->value(time());
		
		if ($this->acl_check_edit(get_class($this), 'registration'))
		{
			$form->dropdown('registration')
					->options(arr::rbool())
					->selected($member->registration);
		}
		
		if (Settings::get('finance_enabled') &&
			$this->acl_check_edit('Variable_Symbols_Controller', 'variable_symbols'))
		{
			$form->input('variable_symbol')
					->rules('length[1,10]')
					->class('join1')
					->callback('Variable_Symbols_Controller::valid_var_sym')
					->style('width:120px');
			
			if (Variable_Key_Generator::get_active_driver())
			{
				$form->checkbox('variable_symbol_generate')
						->label('Generate automatically')
						->checked(TRUE)
						->class('join2')
						->style('width:auto;margin-left:5px');
			}
		}
		
		if ($this->acl_check_edit(get_class($this), 'qos_ceil') &&
			$this->acl_check_edit(get_class($this), 'qos_rate'))
		{
			$speed_class = new Speed_class_Model();
			$speed_classes = array(NULL => '') + $speed_class->select_list();
			$def_speed_class = $speed_class->get_members_default_class();
			
			$form->dropdown('speed_class')
					->options($speed_classes)
					->selected($def_speed_class ? $def_speed_class->id : $member->id)
					->add_button('speed_classes')
					->style('width:200px');
		}
		
		if ($this->acl_check_edit(get_class($this), 'comment'))
		{
			$form->textarea('comment')
					->rules('length[0,250]')
					->value($member->comment);
		}
		
		if (Settings::get('finance_enabled') &&
			Settings::get('self_registration_enable_additional_payment'))
		{
			$form->group(__('Additional demolition of membership fees') . ' ' . help::hint('applicant_additional_payment'));

			$form->checkbox('allow_additional_payment')
					->label('Allow additional payment')
					->value(1);

			$form->input('connection_payment_amount')
					->label('Amount')
					->value($additional_payment_amount)
					->style('width: 70px');
		}
		
		$form->submit('Approve');
		
		// sended
		if ($form->validate())
		{			
			try
			{
				$member->transaction_start();
				
				$form_data = $form->as_array();
			
				// change member
				$member->entrance_date = date('Y-m-d', $form_data['entrance_date']);
				
				if ($this->acl_check_edit('Members_Controller', 'registration'))
				{
					$member->registration = $form_data['registration'];
				}
				
				if ($this->acl_check_edit(get_class($this), 'comment'))
				{
					$member->comment = $form_data['comment'];
				}
				
				if ($this->acl_check_edit('Variable_Symbols_Controller', 'variable_symbols') &&
					(!empty($form_data['variable_symbol']) || (
							isset($form_data['variable_symbol_generate']) &&
							$form_data['variable_symbol_generate']
					)))
				{
					$account = ORM::factory('account')->where(array
					(
						'member_id' => $member->id,
						'account_attribute_id' => Account_attribute_Model::CREDIT
					))->find();
					
					if (!isset($form_data['variable_symbol_generate']) ||
						!$form_data['variable_symbol_generate'])
					{
						$var_sym = $form_data['variable_symbol'];
					}
					else
					{
						$var_sym = Variable_Key_Generator::factory()->generate($member->id);
					}
					
					$vs = new Variable_Symbol_Model();
					
					$vs_not_unique = $vs->get_variable_symbol_id($var_sym);
					
					if ($vs_not_unique && $vs_not_unique->id)
					{
						if ($vs_not_unique->account_id != $account->id)
						{
							throw new Exception(__('Variable symbol already exists in database.'));
						}
					}
					else
					{
						$vs->account_id = $account->id;
						$vs->variable_symbol = $var_sym;
						$vs->save_throwable();
					}
				}
				
				if ($this->acl_check_edit(get_class($this), 'qos_ceil') &&
					$this->acl_check_edit(get_class($this), 'qos_rate'))
				{
					$member->speed_class_id = $form_data['speed_class'];
				}
				
				$member->type = Member_Model::TYPE_REGULAR;
				$member->locked = 0;
				
				$member->save_throwable();
				
				// access rights
				$group_aro_map = new Groups_aro_map_Model();
				
				// get main user
				$main_user_id = NULL;
				
				foreach ($member->users as $user)
				{
					if ($user->type == User_Model::MAIN_USER)
					{
						$main_user_id = $user->id;
						break;
					}
				}
				
				if (!$main_user_id)
					throw new Exception('Main user of applicant is missing');

				// if is not member yet
				if (!$group_aro_map->exist_row(
						Aro_group_Model::REGULAR_MEMBERS, $main_user_id
					))
				{
					// delete rights of applicant
					$group_aro_map->detete_row(
							Aro_group_Model::REGISTERED_APPLICANTS, $main_user_id
					);							

					// insert regular member access rights
					$groups_aro_map = new Groups_aro_map_Model();
					$groups_aro_map->aro_id = $main_user_id;
					$groups_aro_map->group_id = Aro_group_Model::REGULAR_MEMBERS;
					$groups_aro_map->save_throwable();
				}
				
				// make transfer for connection
				if (isset($form_data['allow_additional_payment']) &&
					$form_data['allow_additional_payment'] &&
					($form_data['connection_payment_amount'] > 0))
				{
					$operating_account = ORM::factory('account')
							->where('account_attribute_id', Account_attribute_Model::OPERATING)
							->find();
					
					$credit_account = ORM::factory('account')->where(array
					(
						'member_id'				=> $member->id,
						'account_attribute_id'	=> Account_attribute_Model::CREDIT
					))->find();
					
					Transfer_Model::insert_transfer(
							$credit_account->id, $operating_account->id, null,
							null, $this->session->get('user_id'),
							null, $member->entrance_date,
							date('Y-m-d H:i:s'),
							__('Additional payment for member fees before membership'),
							$form_data['connection_payment_amount']
					);
				}
				
				// reload messages of worker
				ORM::factory('member')->reactivate_messages($member_id);
				
				// inform new member
				if (module::e('notification'))
				{
					Message_Model::activate_special_notice(
							Message_Model::APPLICANT_APPROVE_MEMBERSHIP,
							$member->id, $this->session->get('user_id'),
							Notifications_Controller::ACTIVATE,
							Notifications_Controller::KEEP
					);
				}
				
				unset($form_data);
				
				$member->transaction_commit();
			
				$this->redirect('members/show', $member_id);
			}
			catch (Exception $e)
			{
				Log::add_exception($e);
				$member->transaction_rollback();
				// error
				status::error('Applicant for membership cannot be approved', $e);
			}
		}
		
		$headline = __('Approve application for membership');

		// breadcrumbs navigation		
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view(get_class($this), 'members'))
				->disable_translation()
				->link('members/show/'.$member->id,
						"ID $member->id - $member->name",
						$this->acl_check_view(get_class($this), 'members', $member->id)
				)->text($headline);

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
		
		// send notice with details
		if (module::e('notification'))
		{
			Message_Model::activate_special_notice(
					Message_Model::APPLICANT_REFUSE_MEMBERSHIP,
					$member->id, $this->session->get('user_id'),
					Notifications_Controller::ACTIVATE,
					Notifications_Controller::KEEP
			);
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
		
		$membership_transfer_model = new Membership_transfer_Model();
		
		// test if member is association itself
		$is_association = ($member->id == Member_Model::ASSOCIATION);
		
		// test if member is former member
		$is_former = ($member->type == Member_Model::TYPE_FORMER);
		
		// test if member is applicant
		$is_applicant = ($member->type == Member_Model::TYPE_APPLICANT);
		
		// member is former member
		if ($is_former)
		{
			// find possible membership transfer from member
			$membership_transfer_from_member = $membership_transfer_model
				->get_transfer_from_member($member->id);
		}
		
		// find possible membership transfer to member
		$membership_transfer_to_member = $membership_transfer_model
				->get_transfer_to_member($member->id);

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
		
		$end_membership = ORM::factory('membership_interrupt')
				->has_member_end_after_interrupt_end_in_date($member->id, date('Y-m-d'));
		
		$flags = array();
		
		if ($active_interrupt)
		{
			$flags[] = __('I');
		}
		
		if ($end_membership)
		{
			$flags[] = __('E');
		}

		$title = ($flags) ? $type . ' '.$member->name
				. ' ('. implode(' + ', $flags) .')' : $type . ' '.$member->name;
		
		if ($is_applicant &&
			condition::is_applicant_registration($member) &&
			$this->acl_check_edit(get_class($this), 'members', $member->id))
		{
			$title .= ' <small style="font-size: 60%; font-weight: normal">(' . html::anchor(
					'members/approve_applicant/' . $member_id, 
					__('Approve application for membership'),
					array('class' => 'popup_link')
			) . ')</small>';
		}

		// finds credit account of member
		if (Settings::get('finance_enabled') && !$is_association)
		{
			$account = ORM::factory('account')->where(array
			(
				'member_id' => $member_id,
				'account_attribute_id' => Account_attribute_Model::CREDIT
			))->find();
			
			if (!$is_former)
			{
				// find current regular member fee of member
				$fee = ORM::factory('fee')->get_regular_member_fee_by_member_date(
					$member->id,
					date('Y-m-d')
				);
			}
			
			$entrance_fee_paid = ORM::factory('transfer')->count_entrance_fee_transfers_of_account($account->id);
			$entrance_fee_left = $member->entrance_fee - $entrance_fee_paid;
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
			if ($member->address_point->gps != NULL)
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
			if ($member->members_domicile->address_point->gps != NULL)
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
             
		if (Settings::get('voip_enabled'))
		{
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

			if ($this->acl_check_view('VoIP_Controller', 'voip', $member->id))
			{
				$actions->add_action('user_id')
						->icon_action('phone')
						->url('voip/show')
						->label('Show VoIP account');
			}

			if ($this->acl_check_view('Users_Controller', 'users', $member->id))
			{
				$actions->add_action('user_id')
						->icon_action('member')
						->url('users/show')
						->label('Show user who own this VoIP account');
			}
			
			$voip_grid->datasource($voip);

			if ($has_driver && $b_account)
			{
				if ($this->acl_check_view('VoIP_Controller', 'voip', $member->id))
				{
					$voip_grid->add_new_button(
							'voip_calls/show_by_member/'.$member->id,
							__('List of all calls')
					);
				}

				if (Settings::get('finance_enabled') &&
					$this->acl_check_new('Accounts_Controller', 'transfers', $member->id) &&
					!$is_association)
				{
					$voip_grid->add_new_button(
							'transfers/add_voip/'.$account->id,
							__('Recharge VoIP credit')
					);
				}
			}
		}
		
		// finds date of expiration of member fee
		$expiration_date = '';
		
		if (Settings::get('finance_enabled') &&
			isset($account) && !$is_applicant && !$is_former)
		{
			$expiration_date = self::get_expiration_date($account);
		}

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

		// contacts of main user of member
		$contacts = $contact_model->find_all_users_contacts($user->id);
		
		if (Settings::get('finance_enabled'))
		{
			$variable_symbol_model = new Variable_Symbol_Model();

			$variable_symbols = 0;
			if ($member_id != 1)
			{
				$variable_symbols = $variable_symbol_model->find_account_variable_symbols($account->id);
			}
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
		
		if ($this->acl_check_view('Users_Controller', 'users', $member_id))
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

		if (Settings::get('works_enabled') &&
			$this->acl_check_edit('Works_Controller', 'work', $member_id))
		{
			$actions->add_action('id')
					->icon_action('work')
					->url('works/show_by_user')
					->label('Show works');
		}

		$users_grid->datasource($users);

		// membership interrupts
		if (Settings::get('membership_interrupt_enabled'))
		{
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
			
			$membership_interrupts_grid->callback_field('end_after_interrupt_end')
					->callback('callback::boolean')
					->class('center')
					->label('End membership after end');

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
		}
		
		if (Settings::get('redirection_enabled'))
		{
			// activated redirections of member, including short statistic of whitelisted IP addresses

			$ip_model = new Ip_address_Model();

			$total_ips = $ip_model->count_ips_and_redirections_of_member($member_id);

			// limit check
			if (($sql_offset = ($page - 1) * $limit_results) > $total_ips)
				$sql_offset = 0;

			$ip_addresses = $ip_model->get_ips_and_redirections_of_member(
					$member_id, $sql_offset, $limit_results,
					$order_by, $order_by_direction
			);

			$redir_grid = new Grid('members', null, array
			(
				'use_selector'				=> false,
				'selector_min'				=> 20,
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
				'url_array_ofset'			=> 1
			));

			if ($this->acl_check_new('Redirect_Controller', 'redirect') &&
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

			if ($this->acl_check_view('Messages_Controller', 'member'))
			{
				$redir_grid->callback_field('ip_address')
						->label(__('Preview').'&nbsp;'.help::hint('redirection_preview'))
						->callback('callback::redirection_preview_field');
			}
			
			if ($this->acl_check_delete('Redirect_Controller', 'redirect'))
			{
				$redir_grid->callback_field('redirection')
						->label(__('Canceling of message for redirection'))
						->callback('callback::cancel_redirection_of_member');
			}

			$redir_grid->datasource($ip_addresses);
		
		}
		
		/********** BUILDING OF LINKS   *************/

		$member_links = array();
		$user_links = array();

		// member delete/edit link
		if ($is_former)
		{
			if ($this->acl_check_delete(get_class($this), 'members')
				&& condition::is_former_for_more_than_limit_years($member))
			{
				$member_links[] = html::anchor(
						'members/delete_former/'.$member->id,
						__('Delete'),
						array
						(
							'title' => __('Delete'),
							'class' => 'delete_link'
						)
				);
			}
		}
		else if ($this->acl_check_edit(get_class($this), 'members', $member->id))
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
		
		if (Settings::get('finance_enabled'))
		{
			// members's transfers link
			if (!$is_association &&
				$this->acl_check_view('Accounts_Controller', 'transfers', $member->id))
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
		}
	
		if (!$is_association)
		{
			if (!$is_former)
			{
				// allowed subnets are enabled
				if (Settings::get('allowed_subnets_enabled') &&
					$this->acl_check_view('Allowed_subnets_Controller', 'allowed_subnet', $member->id))
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

			if (module::e('notification'))
			{
				if ($this->acl_check_new('Notifications_Controller', 'member'))
				{
					$member_links[] = html::anchor(
							'notifications/member/'.$member->id, __('Notifications'),
							array('title' => __('Set notification to member'))
					);
				}

				if ($this->acl_check_view('Members_whitelists_Controller', 'whitelist'))
				{
					$member_links[] = html::anchor(
							'members_whitelists/show_by_member/'.$member->id, __('Whitelists')
					);
				}
			}
			
			// export contacts
			if ($this->acl_check_view('Members_Controller', 'members'))
			{
				$member_links[] = html::anchor(
						'export/vcard/' . $member_id . server::query_string(),
						__('Export contacts'),
						array
						(
							'title' => __('Export contacts'),
							'class' => 'popup_link'
						)
				);
			}

			// access control
			if ($this->acl_check_view(get_class($this), 'registration_export', $member->id))
			{
				$member_links[] = html::anchor(
						'members/registration_export/'.$member->id,
						__('Export of registration'),
						array
						(
							'title' => __('Export of registration'),
							'class' => 'popup_link'
						)
				);
			}
			
			if ($this->acl_check_edit('Members_Controller', 'notification_settings', $member->id))
			{
				$member_links[] = html::anchor(
						'members/settings/'.$member->id,
						__('Edit member settings'),
						array
						(
							'class' => 'popup_link'
						)
				);
			}
			
			if (!$is_applicant)
			{
				if (!$is_former)
				{
					// end membership link
					if ($this->acl_check_delete(get_class($this), 'members') &&
						!$end_membership)
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

					// only former member without debt can transfer his membership
					if ($member->get_balance() >= 0)
					{
						// add new membership transfer
						if (!$membership_transfer_from_member)
						{
							if ($this->acl_check_new('Membership_transfers_Controller', 'membership_transfer', $member->id))
							{
								$member_links[] = html::anchor(
										'membership_transfers/add/'.$member_id,
										__('Add membership transfer'),
										array('class' => 'popup_link')
								);
							}
						}
						// edit membership transfer
						else
						{
							if ($this->acl_check_edit('Membership_transfers_Controller', 'membership_transfer', $member->id))
							{
								$member_links[] = html::anchor(
										'membership_transfers/edit/'.$membership_transfer_from_member->id,
										__('Edit membership transfer'),
										array('class' => 'popup_link')
								);
							}

							if ($this->acl_check_delete('Membership_transfers_Controller', 'membership_transfer', $member->id))
							{
								$member_links[] = html::anchor(
										'membership_transfers/delete/'.$membership_transfer_from_member->id,
										__('Delete membership transfer'),
										array('class' => 'delete_link')
								);
							}
						}
					}
				}
			}
		}
		
		
		
		// user show link
		if ($this->acl_check_view('Users_Controller', 'users', $member->id))
		{
			$user_links[] = html::anchor('users/show/'.$user->id, __('Show'));
		}

		// user edit link
		if (!$is_former &&
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
		
		if ($this->user_id == $user->id)
		{
			$user_links[] = html::anchor(
					'user_favourite_pages/show_all', __('Favourites')
			);
		}

		// user's devices link
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Devices_Controller', 'devices', $member->id))
		{
			$user_links[] = html::anchor(
					'devices/show_by_user/'.$user->id,
					__('Show devices')
			);
		}
		
		// connection requests
		if (Settings::get('connection_request_enable') &&
			$this->acl_check_view('Connection_Requests_Controller', 'request', $member->id))
		{
			$user_links[] = html::anchor(
					'connection_requests/show_by_member/'.$member->id,
					__('Show connection requests')
			);
		}
		
		if (!$is_association && module::e('approval'))
		{
			// user's works link
			if (Settings::get('works_enabled') &&
				$this->acl_check_view('Works_Controller', 'work', $member->id))
			{
				$user_links[] = html::anchor(
						'works/show_by_user/'.$user->id,
						__('Show works')
				);
			}

			// user's work reports link
			if (Settings::get('works_enabled') &&
				$this->acl_check_view('Works_Controller', 'work', $member->id))
			{
				$user_links[] = html::anchor(
						'work_reports/show_by_user/'.$user->id,
						__('Show work reports')
				);
			}

			// user's requests link
			if ($this->acl_check_view('Requests_Controller', 'request', $member->id))
			{
				$user_links[] = html::anchor(
						'requests/show_by_user/'.$user->id,
						__('Show requests')
				);
			}
		}

		// member is not former
		if (!$is_former)
		{
			// change password link
			if ($this->acl_check_edit('Users_Controller', 'password', $member->id) &&
				!($user->is_user_in_aro_group($user->id, Aro_group_Model::ADMINS) &&
					$user->id != $this->user_id
				))
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
		$view->action_logs = action_logs::object_last_modif($member, $member_id);
		$view->mapycz_enabled = TRUE; // for popup link to address point
		$view->content = new View('members/show');
		$view->content->title = $title;
		$view->content->member = $member;
		$view->content->user = $user;
		$view->content->user_name = $user_name;
		$view->content->users_grid = $users_grid;
		$view->content->redir_grid = Settings::get('redirection_enabled') ? $redir_grid : '';
		$view->content->voip_grid = (Settings::get('voip_enabled')) ? $voip_grid : '';
		$view->content->membership_interrupts_grid = Settings::get('membership_interrupt_enabled') ?
																	$membership_interrupts_grid : '';
		$view->content->contacts = $contacts;
		$view->content->contact_types = $contact_types;
		$view->content->variable_symbols = (isset($variable_symbols)) ? $variable_symbols : NULL;
		$view->content->expiration_date = $expiration_date;
		$view->content->entrance_fee_paid = (isset($entrance_fee_paid)) ? $entrance_fee_paid : NULL;
		$view->content->entrance_fee_left = (isset($entrance_fee_left)) ? $entrance_fee_left : NULL;
		$view->content->account = (isset($account)) ? $account : NULL;
		$view->content->fee = (isset($fee)) ? $fee : NULL;
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
		$view->content->billing_has_driver = (Settings::get('voip_enabled')) ? $has_driver : FALSE;
		$view->content->billing_account = (Settings::get('voip_enabled')) ? $b_account : NULL;
		$view->content->count_voip = (Settings::get('voip_enabled')) ? count($voip) : 0;
		$view->content->total_traffic = @$total_traffic;
		$view->content->today_traffic = @$today_traffic;
		$view->content->month_traffic = @$month_traffic;
		$view->content->membership_transfer_from_member = isset($membership_transfer_from_member) ? $membership_transfer_from_member : NULL;
		$view->content->membership_transfer_to_member = $membership_transfer_to_member;
		$view->content->member_links = implode(' | ', $member_links);
		$view->content->user_links = implode(' | ', $user_links);
		$view->content->is_association = $is_association;
		$view->render(TRUE);
	} // end of show function



	/**
	 * Gets expiration date of member's payments.
	 * 
	 * @author Michal Kliment, Ondrej Fibich
	 * @param object $account
	 * @return string
	 */
	public static function get_expiration_date($account)
	{
		// member's actual balance
		$balance = $account->balance;
		
		$transfer_model = new Transfer_Model();
		
		$close_date = date_parse(
			date::get_closses_deduct_date_to(
				$transfer_model->get_last_transfer_datetime_of_account($account->id)
			)
		);
		
		// date
		$day = $close_date['day'];
		$month = $close_date['month'];
		$year = $close_date['year'];

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

		// negative balance will drawn by red color, else balance will drawn by green color
		$color = ($balance < 0) ? 'red' : 'green';

		$payments = array();

		// finds entrance date of member
		$entrance_date_str = date::get_closses_deduct_date_to($account->member->entrance_date);
		$entrance_date = date_parse($entrance_date_str);

		// finds debt payment rate of entrance fee
		$debt_payment_rate = ($account->member->debt_payment_rate > 0)
				? $account->member->debt_payment_rate : $account->member->entrance_fee;

		// finds all debt payments of entrance fee
		self::find_debt_payments(
				$payments, $entrance_date['month'], $entrance_date['year'],
				$account->member->entrance_fee, $debt_payment_rate
		);

		// finds all member's devices with debt payments
		$devices = ORM::factory('device')->get_member_devices_with_debt_payments($account->member_id);

		foreach ($devices as $device)
		{
			// finds buy date of this device
			$buy_date = date_parse(date::get_closses_deduct_date_to($device->buy_date));

			// finds all debt payments of this device
			self::find_debt_payments(
					$payments, $buy_date['month'], $buy_date['year'],
					$device->price, $device->payment_rate
			);
		}

		$fee_model = new Fee_Model();
		
		// protection from unending loop
		$too_long = FALSE;

		// finds min and max date = due to prevent before unending loop
		$min_fee_date = $fee_model->get_min_fromdate_fee_by_type ('regular member fee');
		$max_fee_date = $fee_model->get_max_todate_fee_by_type ('regular member fee');

		while (true)
		{
			$date = date::create(date::get_deduct_day_to($month, $year), $month, $year);

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

			if ($balance * $sign < 0)
				break;

			$month += $sign;

			if ($month == 0 OR $month == 13)
			{
				$month = ($month == 13) ? 1 : 12;
				$year += $sign;
			}
			
			// if we are 5 years in future, there is no point of counting more
			if (date('Y') + 10 < $year)
			{
				$too_long = TRUE;
				break;
			}
		}

		$month--;
		if ($month == 0)
		{
			$month = 12;
			$year--;
		}
		
		$date = date::create(date::days_of_month($month), $month, $year);
		
		if (strtotime($date) < strtotime($entrance_date_str))
			$date = $entrance_date_str;

		return  '<span style="color: '.$color.'">'
				. ($too_long ? '&gt; ' : '')
				. $date. '</span>';
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
		
		// start entrance date
		$association = new Member_Model(Member_Model::ASSOCIATION);
		$entrace_start_year = date('Y', strtotime($association->entrance_date));
		
		// cannot add former member
		unset($types[Member_Model::TYPE_FORMER]);
		unset($types[Member_Model::TYPE_APPLICANT]);
		
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
		$arr_countries = ORM::factory('country')->where('enabled', 1)->select_list('id', 'country_name');
		
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
		$phone_prefixes = $country_model->select_country_code_list();

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
				->label('Name of organization')
				->help(help::hint('member_name'))
				->rules('length[1,100]');
		
		// access control
		if ($this->acl_check_new('Members_Controller', 'organization_id'))
		{
			$form->input('organization_identifier')
					->rules('length[3,20]');
		}
		
		// access control
		if ($this->acl_check_new('Members_Controller', 'vat_organization_identifier'))
		{
			$form->input('vat_organization_identifier')
					->rules('length[3,30]');
		}

		$form->group('Login data');
		
		$form->input('login')
				->label('Username')
				->rules('required|length[5,20]')
				->callback(array($this, 'valid_username'));
		
		$pass_min_len = Settings::get('security_password_length');
		
		$form->password('password')
				->label('Password')
				->help(help::hint('password'))
				->rules('required|length['.$pass_min_len.',50]')
				->class('main_password');
		
		$form->password('confirm_password')
				->rules('required|length['.$pass_min_len.',50]')
				->matches($form->password);

		$form->group('Address of connecting place');
		
		$address_point_server_active = Address_points_Controller::is_address_point_server_active();
		
		// If address database application is set show new form
		if ($address_point_server_active)
		{	
			$form->dropdown('country_id')
					->label('Country')
					->rules('required')
					->options($arr_countries)
					->style('width:200px')
					->selected(Settings::get('default_country'));
			
			$form->input('town')
				->label(__('Town').' - '.__('District'))
				->rules('required')
				->class('join1');
			
			$form->input('district')
				->class('join2');

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
		}
		
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
		
		// If address database application is set show new form
		if ($address_point_server_active)
		{	
			$form->dropdown('domicile_country_id')
					->label('Country')
					->options($arr_countries)
					->style('width:200px')
					->selected(Settings::get('default_country'));
			
			$form->input('domicile_town')
				->label(__('Town').' - '.__('District'))
				->class('join1');
			
			$form->input('domicile_district')
				->class('join2');

			$form->input('domicile_street')
				->label('Street');
						
			$form->input('domicile_zip')
				->label('Zip code');
		}
		else
		{
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
		}
		
		$form->input('domicile_gpsx')
				->label(__('GPS').'&nbsp;X:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps');
		
		$form->input('domicile_gpsy')
				->label(__('GPS').'&nbsp;Y:&nbsp;'.help::hint('gps_coordinates'))
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
				->rules('valid_email')
                ->callback(array($this, 'valid_unique_email'))
				->style('width:250px');
		
		if (Settings::get('finance_enabled'))
		{
			$form->group('Account information');

			$form->input('variable_symbol')
					->label('Variable symbol')
					->help(help::hint('variable_symbol'))
					->rules('length[1,10]')
					->class('join1')
					->callback('Variable_Symbols_Controller::valid_var_sym');

			if (Variable_Key_Generator::get_active_driver())
			{
				$form->checkbox('variable_symbol_generate')
						->label('Generate automatically')
						->checked(TRUE)
						->class('join2')
						->style('width:auto;margin-left:5px');
			}
			else
			{
				$form->variable_symbol->rules('required|length[1,10]');
			}

			$form->input('entrance_fee')
					->label('Entrance fee')
					->help(help::hint('entrance_fee'))
					->rules('valid_numeric')
					->value($entrance_fee);

			$form->input('debt_payment_rate')
					->label('Monthly instalment of entrance')
					->help(help::hint('entrance_fee_instalment'))
					->rules('valid_numeric')
					->value($entrance_fee);
		}
		
		$form->group('Additional information');
		
		$speed_class = new Speed_class_Model();
		$speed_classes = array(NULL => '') + $speed_class->select_list();
		$default_speed_class = $speed_class->get_members_default_class();
			
		$form->dropdown('speed_class')
				->options($speed_classes)
				->selected($default_speed_class ? $default_speed_class->id : NULL)
				->add_button('speed_classes')
				->style('width:200px');

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
		
		$form->date('entrance_date')
				->label('Entrance date')
				->years($entrace_start_year)
				->rules('required')
				->callback(array($this, 'valid_entrance_date'));
		
		if ($this->acl_check_edit('Members_Controller', 'registration'))
		{
			$form->dropdown('registration')
					->options(arr::rbool());
		}
		
		$form->textarea('comment')
				->rules('length[0,250]');
		
		$form->submit('Add');
		
		// posted
		if($form->validate())
		{
			$form_data = $form->as_array();
			
			$match = array();
			$match2 = array();
			
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
			else if ($form_data['use_domicile'] &&
					$address_point_server_active &&
					(
					!Address_points_Controller::is_address_point_valid(
						$form_data['domicile_country_id'],
						$form_data['domicile_town'],
						$form_data['domicile_district'],
						$form_data['domicile_street'],
						$form_data['domicile_zip']
					) ||
					!preg_match('((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', $form_data['domicile_street'], $match2)
					))
			{
				$form->domicile_street->add_error('required', __('Invalid address point.'));
			}
			else
			{
				// street
				if ($address_point_server_active)
				{
					$street = trim(preg_replace(' ((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', '', $form_data['street']));

					$number = $match[0];
				}
				if ($form_data['use_domicile'] &&
					$address_point_server_active)
				{
					$domicile_street = trim(preg_replace(' ((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', '', $form_data['domicile_street']));

					$domicile_number = $match2[0];
				}
				
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

					if ($address_point_server_active)
					{
						$t = new Town_Model();
						$s = new Street_Model();
						$t_id = $t->get_town($form_data['zip'], $form_data['town'], $form_data['district'])->id;
						$s_id = $s->get_street($street, $t_id)->id;

						$address_point = $address_point_model->get_address_point($form_data['country_id'], $t_id, $s_id, $number,
								$gpsx, $gpsy);
					}
					else
					{
						$address_point = $address_point_model->get_address_point(
								$form_data['country_id'], $form_data['town_id'],
								$form_data['street_id'], $form_data['street_number'],
								$gpsx, $gpsy
						);
					}

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
						$address_point->gps = NULL;
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

					if (empty($form_data['birthday']))
					{
						$user->birthday	= NULL;
					}
					else
					{
						$user->birthday	= date("Y-m-d", $form_data['birthday']);
					}

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

					// access control
					if ($this->acl_check_new('Members_Controller', 'organization_id'))
					{
						$member->organization_identifier = $form_data['organization_identifier'];
					}

					// access control
					if ($this->acl_check_new('Members_Controller', 'vat_organization_identifier'))
					{
						$member->vat_organization_identifier = $form_data['vat_organization_identifier'];
					}

					$member->speed_class_id = $form_data['speed_class'];

					if (Settings::get('finance_enabled'))
					{
						$member->entrance_fee = $form_data['entrance_fee'];
						$member->debt_payment_rate = $form_data['debt_payment_rate'];
					}

					if ($member->type == Member_Model::TYPE_APPLICANT)
					{
						$member->entrance_date = NULL;
					}
					else
					{
						$member->entrance_date = date('Y-m-d', $form_data['entrance_date']);
					}
					
					if ($this->acl_check_edit('Members_Controller', 'registration'))
					{
						$member->registration = $form_data['registration'];
					}

					// saving member
					$member->save_throwable();

					// saving user
					$user->member_id = $member->id;
					$user->save_throwable();

					// telephone
					$contact_model = new Contact_Model();

					// search for contacts
					$p_contact_id = $contact_model->find_contact_id(
							Contact_Model::TYPE_PHONE, $form_data['phone']
					);

					if ($p_contact_id)
					{
						$contact_model = ORM::factory('contact', $p_contact_id);
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
					if (!empty($form_data['email']))
					{
                        // search for contacts
                        $e_contact_id = $contact_model->find_contact_id(
                                Contact_Model::TYPE_EMAIL, $form_data['email']
                        );

                        if ($e_contact_id)
                        {
                            $contact_model = ORM::factory('contact', $e_contact_id);
                            $contact_model->add($user);
                            $contact_model->save_throwable();
                        }
                        else
                        { // add whole contact
                            $contact_model->type = Contact_Model::TYPE_EMAIL;
                            $contact_model->value = $form_data['email'];
                            $contact_model->save_throwable();
                            $contact_model->add($user);
                            $contact_model->save_throwable();
                        }
					}

					// saving account
					$account->member_id	= $member->id;
					$account->save_throwable();

					if (Settings::get('finance_enabled'))
					{
						// saving variable symbol
						if (!isset($form_data['variable_symbol_generate']) ||
							!$form_data['variable_symbol_generate'])
						{
							$var_sym = $form_data['variable_symbol'];
						}
						else
						{
							$var_sym = Variable_Key_Generator::factory()->generate($member->id);
						}

						if (empty($var_sym))
						{
							throw new Exception(__('Empty variable symbol.'));
						}

						$variable_symbol_model = new Variable_Symbol_Model();
						$variable_symbol_model->account_id = $account->id;
						$variable_symbol_model->variable_symbol = $var_sym;
						$variable_symbol_model->save_throwable();
					}

					// save allowed subnets count of member
					$allowed_subnets_count = new Allowed_subnets_count_Model();
					$allowed_subnets_count->member_id = $member->id;
					$allowed_subnets_count->count = Settings::get('allowed_subnets_default_count');
					$allowed_subnets_count->save();

					// address of connecting place is different than address of domicile
					if ($form_data['use_domicile'])
					{
						if ($address_point_server_active)
						{
							$t = new Town_Model();
							$s = new Street_Model();
							$t_id = $t->get_town($form_data['domicile_zip'],
												$form_data['domicile_town'],
												$form_data['domicile_district'])->id;
							$s_id = $s->get_street($domicile_street, $t_id)->id;

							$address_point = $address_point_model->get_address_point(
								$form_data['domicile_country_id'],
								$t_id,
								$s_id,
								$domicile_number,
								$domicile_gpsx, $domicile_gpsy
							);
						}
						else
						{
							$address_point = $address_point_model->get_address_point(
									$form_data['domicile_country_id'],
									$form_data['domicile_town_id'],
									$form_data['domicile_street_id'],
									$form_data['domicile_street_number'],
									$domicile_gpsx, $domicile_gpsy
							);
						}

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
								$address_point->gps = NULL;
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
					Mail_message_Model::create(
							Member_Model::ASSOCIATION, $user->id,
							mail_message::format('welcome_subject'),
							mail_message::format('welcome'), 1
					);

					// commit transaction
					$member->transaction_commit();
					status::success('Member has been successfully added.');
					
					// add information about last added member by logged user
					// for selecting member in dropdown for connection request
					$this->session->set('last_added_member_id', $member->id);

					// redirect
					url::redirect('members/show/'.$member->id);
				}
				catch (Exception $e)
				{
					// rollback transaction
					$member->transaction_rollback();
					Log::add_exception($e);
					status::error('Error - cant add new member.', $e);
					$this->redirect('members/show_all');
				}
			}
		}
		
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
		if ($member->type == Member_Model::TYPE_FORMER || 
			!$this->acl_check_edit(get_class($this), 'members', $member->id))
			Controller::error(ACCESS);

		$this->_member_id = $member->id;
		
		// start entrance date
		$entrace_start_year = date('Y') - 100;
		$entrace_start_month = 1;
		$entrace_start_day = 1;
		
		if ($member->id != Member_Model::ASSOCIATION)
		{
			$association = new Member_Model(Member_Model::ASSOCIATION);
			$entrace_start_year = date('Y', strtotime($association->entrance_date));
			$entrace_start_month = date('m', strtotime($association->entrance_date));
			$entrace_start_day = date('d', strtotime($association->entrance_date));
		}

		// countries
		$arr_countries = ORM::factory('country')->where('enabled', 1)->select_list('id', 'country_name');
		$arr_countries = $arr_countries + ORM::factory('country')->where('id', $member->address_point->country_id)->select_list('id', 'country_name');
		
		// streets
		// loads all streets if GET parametr with name 'street_id' exists.
		// otherwise it loads just streets for selected town (request #614)
		$arr_streets = array
		(
			NULL => '--- ' . __('Without street') . ' ---'
		);
		
		if (array_key_exists('street_id', $_GET))
			$arr_streets += ORM::factory('street')->select_list('id', 'street');
		else 
			$arr_streets += $member->address_point->town->streets->select_list('id', 'street');
		
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
					->rules('required|length[1,100]')
					->value($member->name);
		}
		
		if ($this->acl_check_edit(get_class($this),'type',$member->id) &&
			$member->type != Member_Model::TYPE_APPLICANT)
		{
			$enum_type_model = new Enum_type_Model();
			$types = $enum_type_model->get_values(Enum_type_Model::MEMBER_TYPE_ID);
			unset($types[Member_Model::TYPE_FORMER]);
			unset($types[Member_Model::TYPE_APPLICANT]);
			
			$form->dropdown('type')
					->options($types)
					->selected($member->type)
					->callback(array($this, 'valid_member_type'))
					->style('width:200px');
		}
		
		// access control
		if ($this->acl_check_edit('Members_Controller', 'organization_id', $member->id))
		{
			$form->input('organization_identifier')
					->rules('length[3,20]')
					->value($member->organization_identifier);
		}
		
		// access control
		if ($this->acl_check_edit('Members_Controller', 'vat_organization_identifier', $member->id))
		{
			$form->input('vat_organization_identifier')
					->rules('length[3,20]')
					->value($member->vat_organization_identifier);
		}
			
		if ($this->acl_check_edit(get_class($this), 'address', $member->id))
		{	
			// gps
			$gpsx = '';
			$gpsy = '';

			if ($member->address_point->gps != NULL)
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

			if ($member->members_domicile->address_point->gps != NULL)
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
			
			$address_point_server_active = Address_points_Controller::is_address_point_server_active();
		
			// If address database application is set show new form
			if ($address_point_server_active)
			{	
				$form->dropdown('country_id')
						->label('Country')
						->rules('required')
						->options($arr_countries)
						->style('width:200px')
						->selected($member->address_point->country_id);

				$form->input('town')
					->label(__('Town').' - '.__('District'))
					->rules('required')
					->class('join1')
					->value($member->address_point->town->town);

				$form->input('district')
					->class('join2')
					->value(($member->address_point->town->quarter !== NULL ? $member->address_point->town->quarter : $member->address_point->town->town));

				$form->input('street')
					->label('Street')
					->rules('required')
					->value(($member->address_point->street != NULL ?
						$member->address_point->street->street." ".$member->address_point->street_number :
						$member->address_point->street_number)
					);

				$form->input('zip')
					->label('Zip code')
					->rules('required')
					->value($member->address_point->town->zip_code);
			}
			else
			{
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
			}
		
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
			
			// If address database application is set show new form
			if ($address_point_server_active)
			{
				$form->dropdown('domicile_country_id')
						->label('Country')
						->options($arr_countries)
						->style('width:200px')
						->selected(($member->members_domicile->id != 0 ? $member->members_domicile->address_point->country_id : Settings::get('default_country')));

				$form->input('domicile_town')
					->label(__('Town').' - '.__('District'))
					->class('join1')
					->value($member->members_domicile->address_point->town->town);

				$form->input('domicile_district')
					->class('join2')
					->value(($member->members_domicile->address_point->town->quarter !== NULL ?
						$member->members_domicile->address_point->town->quarter :
						$member->members_domicile->address_point->town->town)
					);

				$form->input('domicile_street')
					->label('Street')
					->value(($member->members_domicile->address_point->street != NULL ?
							$member->members_domicile->address_point->street->street .
								($member->members_domicile->address_point->street_number != NULL ?
								" ".$member->members_domicile->address_point->street_number :
								""
								) :
							$member->members_domicile->address_point->street_number)
					);

				$form->input('domicile_zip')
					->label('Zip code')
					->value($member->members_domicile->address_point->town->zip_code);
			}
			else
			{
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
						->options($arr_countries)
						->selected(($member->members_domicile->id != 0 ? $member->members_domicile->address_point->country_id : Settings::get('default_country')))
						->style('width:200px');
			}

			$form->input('domicile_gpsx')
					->label(__('GPS').'&nbsp;X:&nbsp;'.help::hint('gps_coordinates'))
					->rules('gps')
					->value($domicile_gpsx);

			$form->input('domicile_gpsy')
					->label(__('GPS').'&nbsp;Y:&nbsp;'.help::hint('gps_coordinates'))
					->rules('gps')
					->value($domicile_gpsy);
		}
		
		if (Settings::get('finance_enabled'))
		{
			$form->group('Account information');

			if ($this->acl_check_edit(get_class($this), 'en_fee', $member->id))
			{
				$form->input('entrance_fee')
						->label('Entrance fee')
						->help(help::hint('entrance_fee'))
						->rules('valid_numeric')
						->value($member->entrance_fee);
			}
			if ($this->acl_check_edit(get_class($this),'debit', $member->id))
			{
				$form->input('debt_payment_rate')
						->label('Monthly instalment of entrance')
						->help(help::hint('entrance_fee_instalment'))
						->rules('valid_numeric')
						->value($member->debt_payment_rate);
			}
		}
		// additional information
		$form->group('Additional information');
		
		if ($this->acl_check_edit(get_class($this), 'qos_ceil', $member->id) &&
			$this->acl_check_edit(get_class($this), 'qos_rate', $member->id))
		{
			$speed_classes = array(NULL => '') + ORM::factory('speed_class')->select_list();
			
			$form->dropdown('speed_class')
					->options($speed_classes)
					->selected($member->speed_class_id)
					->add_button('speed_classes')
					->style('width:200px');
		}
		
		$form->input('allowed_subnets_count')
				->label('Count of allowed subnets')
				->help(help::hint('allowed_subnets_count'))
				->rules('valid_numeric')
				->value($allowed_subnets_count);
		
		if ($this->acl_check_edit(get_class($this), 'entrance_date', $member->id) &&
			$member->type != Member_Model::TYPE_APPLICANT)
		{
			$form->date('entrance_date')
					->label('Entrance date')
					->years($entrace_start_year)
					->months($entrace_start_month)
					->days($entrace_start_day)
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
			$form->dropdown('added_by_user_id')
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
			
			$match = array();
			$match2 = array();
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
			else if ($form_data['use_domicile'] &&
					$address_point_server_active &&
					(
					!Address_points_Controller::is_address_point_valid(
						$form_data['domicile_country_id'],
						$form_data['domicile_town'],
						$form_data['domicile_district'],
						$form_data['domicile_street'],
						$form_data['domicile_zip']
					) ||
					!preg_match('((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', $form_data['domicile_street'], $match2)
					))
			{
				$form->domicile_street->add_error('required', __('Invalid address point.'));
			}
			else
			{
				try
				{
					// street
					if ($address_point_server_active)
					{
						$street = trim(preg_replace(' ((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', '', $form_data['street']));

						$number = $match[0];
					}
					if ($form_data['use_domicile'] &&
						$address_point_server_active)
					{
						$domicile_street = trim(preg_replace(' ((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', '', $form_data['domicile_street']));

						$domicile_number = $match2[0];
					}
					
					$member->transaction_start();

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

					// access control
					if ($this->acl_check_edit(get_class($this),'address',$member_id))
					{
						// find his address point
						$address_point_model = new Address_point_Model();

						if ($address_point_server_active)
						{
							$t = new Town_Model();
							$s = new Street_Model();
							$t_id = $t->get_town($form_data['zip'], $form_data['town'], $form_data['district'])->id;
							$s_id = $s->get_street($street, $t_id)->id;

							$address_point = $address_point_model->get_address_point($form_data['country_id'], $t_id, $s_id, $number,
									$gpsx, $gpsy);
						}
						else
						{
							$address_point = $address_point_model->get_address_point(
									$form_data['country_id'], $form_data['town_id'],
									$form_data['street_id'], $form_data['street_number'],
									$gpsx, $gpsy
							);
						}

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
							$address_point->gps = NULL;
							$address_point->save();
						}

						// address of connecting place is different than address of domicile
						if ($form_data['use_domicile'])
						{
							if ($address_point_server_active)
							{
								$t = new Town_Model();
								$s = new Street_Model();
								$t_id = $t->get_town($form_data['domicile_zip'],
													$form_data['domicile_town'],
													$form_data['domicile_district'])->id;
								$s_id = $s->get_street($domicile_street, $t_id)->id;

								$address_point = $address_point_model->get_address_point(
									$form_data['domicile_country_id'],
									$t_id,
									$s_id,
									$domicile_number,
									$domicile_gpsx, $domicile_gpsy
								);
							}
							else
							{
								$address_point = $address_point_model->get_address_point(
										$form_data['domicile_country_id'],
										$form_data['domicile_town_id'],
										$form_data['domicile_street_id'],
										$form_data['domicile_street_number'],
										$domicile_gpsx, $domicile_gpsy
								);
							}

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
								$address_point->gps = NULL;
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

					if ($this->acl_check_edit(get_class($this),'type',$member->id) &&
						$member->type != Member_Model::TYPE_APPLICANT &&
						$form_data['type'] != Member_Model::TYPE_APPLICANT)
					{
						$member->type = $form_data['type'];
					}

					// access control
					if ($this->acl_check_edit('Members_Controller', 'organization_id', $member->id))
					{
						$member->organization_identifier = $form_data['organization_identifier'];
					}

					// access control
					if ($this->acl_check_edit('Members_Controller', 'vat_organization_identifier', $member->id))
					{
						$member->vat_organization_identifier = $form_data['vat_organization_identifier'];
					}

					if ($this->acl_check_edit(get_class($this),'locked',$member->id) && 
						$member->id != Member_Model::ASSOCIATION)
					{
						$member->locked = $form_data['locked'];
					}

					if ($member->id != Member_Model::ASSOCIATION &&
						$this->acl_check_edit('Members_Controller', 'registration', $member->id))
					{
						$member->registration = $form_data['registration'];
					}

					if ($this->acl_check_edit('Members_Controller', 'user_id'))
						$member->user_id = $form_data['added_by_user_id'];

					if ($this->acl_check_edit(get_class($this),'comment',$member->id))
						$member->comment = $form_data['comment'];

					$entrance_date_changed = FALSE;

					// member data
					if ($this->acl_check_edit(get_class($this),'entrance_date',$member->id))
					{
						if ($member->type != Member_Model::TYPE_APPLICANT)
						{
							$entrance_date_changed = ($member->entrance_date != date("Y-m-d",$form_data['entrance_date']));
							$member->entrance_date = date("Y-m-d",$form_data['entrance_date']);
						}
						else
							$member->entrance_date = NULL;
					}

					if ($this->acl_check_edit(get_class($this),'name',$member->id))
						$member->name = $form_data['membername'];

					if ($this->acl_check_edit(get_class($this), 'qos_ceil',$member->id) &&
						$this->acl_check_edit(get_class($this), 'qos_rate',$member->id))
						$member->speed_class_id = $form_data['speed_class'];

					if (Settings::get('finance_enabled'))
					{
						$entrance_fee_changed = FALSE;

						if ($this->acl_check_edit(get_class($this),'en_fee',$member->id))
						{
							$entrance_fee_changed = ($member->entrance_fee != $form_data['entrance_fee']);

							$member->entrance_fee = $form_data['entrance_fee'];
						}

						$debt_payment_rate_changed = FALSE;

						if ($this->acl_check_edit(get_class($this),'debit',$member->id))
						{
							$debt_payment_rate_changed = ($member->debt_payment_rate != $form_data['debt_payment_rate']);

							$member->debt_payment_rate = $form_data['debt_payment_rate'];
						}
					}

					$member->save_throwable();

					// entrance date, fee or debt payment has been changed => recalculate entrance fee's transfers
					if (Settings::get('finance_enabled') && ($entrance_date_changed || $entrance_fee_changed || $debt_payment_rate_changed))
					{
						Accounts_Controller::recalculate_entrance_fees($member->get_credit_account()->id);
					}

					// entrance date has been changed => recalculate member fee's transfers
					if (Settings::get('finance_enabled') && $entrance_date_changed)
					{
						Accounts_Controller::recalculate_member_fees($member->get_credit_account()->id);
					}

					$member->transaction_commit();

					ORM::factory('member')->reactivate_messages($member->id);

					status::success('Member has been successfully updated.');
				}
				catch (Exception $e)
				{
					$member->transaction_rollback();
					status::error('Error - cant update member.', $e);
				}

				$this->redirect('members/show/', $member_id);
			}
		}
		
		$headline = __('Edit member');

		if ($member->type == Member_Model::TYPE_APPLICANT)
		{
			$headline = __('Edit applicant for membership');
		}

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
		$view->content->headline = $headline . ' ' . $member->name;
		$view->content->form = $form->html();
		$view->content->link_back = '';
		$view->render(TRUE);
	} // end of edit function
	
	/**
	 * Delete former member that has left at least before 5 years.
	 *
	 * @param int $member_id	id of member to delete
	 */
	public function delete_former($member_id = NULL)
	{
		if (!isset($member_id) || !is_numeric($member_id))
		{
			self::warning(PARAMETER);
		}

		$member = new Member_Model($member_id);

		// member doesn't exist
		if (!$member->id)
		{
			self::error(RECORD);
		}

		// access control
		if (!$this->acl_check_delete(get_class($this), 'members'))
		{
			self::error(ACCESS);
		}

		if (!condition::is_former_for_more_than_limit_years($member))
		{
			self::error(ACCESS);
		}

		try
		{
			$ba_model = new Bank_account_Model();
			$bank_trans_model = new Bank_transfer_Model();

			$member->transaction_start();

			// change member credit account name
			$credit_account = $member->get_credit_account();
			if ($credit_account->id)
			{
				$credit_account->name = __('Former member') . ' ' . $member->id;
				$credit_account->save_throwable();
			}

			// delete bank accounts and their transfers with owner set to the
			// member
			$bank_accounts = $ba_model->get_member_bank_accounts($member->id);

			foreach ($bank_accounts as $bank_account)
			{
				$bank_trans_model->delete_all_with_origin($bank_account->id);
				$ba_model->delete_throwable($bank_account->id);
			}

			// delete bank tranfers that was assigned to the member but does not
			// come from his bank account (e.g. from post service)
			$bank_trans_model->delete_all_with_transfer_to($member->id);

			// delete member, user, jobs, job reports, contacts, etc.
			$member->delete_throwable();

			$member->transaction_commit();

			status::success('Member has been successfully removed.');
			url::redirect('members/show_all');
		}
		catch (Exception $ex)
		{
			$member->transaction_rollback();
			Log::add_exception($ex);
			status::error('Error - cannot remove member.', $ex);
			url::redirect('members/show_all');
		}
	}

	/**
	 * Enables to edid member settings (e.g. notification settings).
	 * 
	 * @param integer $member_id Member ID
	 */
	public function settings($member_id = NULL)
	{
		// bad parameter
		if (!isset($member_id) || !is_numeric($member_id))
			self::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// member doesn't exist
		if (!$member->id)
			self::error(RECORD);
		
		if (!$this->acl_check_edit('Members_Controller', 'notification_settings', $member_id))
			self::error(ACCESS);

		// form
		$form = new Forge('members/settings/'.$member->id);
		
		$form->hidden('checkbox_hack');

		$ns_group = $form->group(__('Notification settings') . ' ' .
								 help::hint('notification_member_settings'));
		
		$ns_group->checkbox('notification_by_redirection')
			->label('Enable notification by redirection')
			->checked($member->notification_by_redirection);
		
		$ns_group->checkbox('notification_by_email')
			->label('Enable notification by e-mail')
			->checked($member->notification_by_email);
		
		$ns_group->checkbox('notification_by_sms')
			->label('Enable notification by SMS messages')
			->checked($member->notification_by_sms);
		
		$form->submit('Edit');

		// form validation
		if($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				$member->transaction_start();
				
				$member->notification_by_redirection = $form_data['notification_by_redirection'];
				$member->notification_by_email = $form_data['notification_by_email'];
				$member->notification_by_sms = $form_data['notification_by_sms'];
				$member->save_throwable();
				
				$member->transaction_commit();

				status::success('Member settings has been successfully updated.');
				$this->redirect('members/show/', $member_id);
			}
			catch (Exception $e)
			{
				$member->transaction_rollback();
				status::error('Error - cant update member settings.', $e);
			}
		}
			
		$headline = __('Edit member settings');

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view(get_class($this), 'members'))
				->disable_translation()
				->link('members/show/'.$member->id,
						"ID $member->id - $member->name",
						$this->acl_check_view(
								get_class($this), 'members', $member->id
						)
				)
				->text($headline);

		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('form');
		$view->content->headline = $headline . ' ' . $member->name;
		$view->content->form = $form->html();
		$view->content->link_back = '';
		$view->render(TRUE);
	}

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
		$variable_symbol_model = new Variable_Symbol_Model();
		
		$end_membership = ORM::factory('membership_interrupt')
				->has_member_end_after_interrupt_end_in_date($member->id, date('Y-m-d'));
		
		// wrong id
		if (!$member->id)
			Controller::error(RECORD);
		
		// access
		if (!$this->acl_check_edit(get_class($this), 'members', $member_id))
			Controller::error(ACCESS);
		
		if ($end_membership)
		{
			status::warning('Cannot end membership when interrupt with end membership is activated.');
			$this->redirect('members/show/', $member->id);
		}
		
		// cannot end membership to association (#725)
		if ($member->id == Member_Model::ASSOCIATION)
		{
			status::warning('Cannot end membership to association.');
			$this->redirect('members/show/', $member->id);
		}
		
		// form
		$form = new Forge();
		
		$form->date('leaving_date')
				->label('Leaving date');

		$array_mess = array("0" => 'Neposílat',"19" => 'Žádost',"21" => 'Neplacení',"23" => 'Přeplatek');

		$form->dropdown('mess')
		->label('Typ emailu')
		->options($array_mess)
		->style('width:200px');

		$form->input('comment')
		->label('č.ú.')
		->style('width:200px');

		
		$form->submit('End membership');
		

		// validation
		if ($form->validate())
		{	
			$form_data = $form->as_array();
			try
			{
				$member->transaction_start();
				
				$member->leaving_date = date('Y-m-d', $form_data['leaving_date']);
				$mess = $form_data['mess'];
				$comment = array('leaving_date' => $member->leaving_date);

				// leaving date is in past or today
				if ($member->leaving_date <= date('Y-m-d'))
				{
					$member->type = Member_Model::TYPE_FORMER;
					$member->locked = 1; // lock account
				}

				//get variable symbol id

				$var_sym_id = $variable_symbol_model->get_id_variable_symbol_id_member($member->id);
				$var_sym = $variable_symbol_model->get_variable_symbol_id_member($member->id);
				$var_sym = "$var_sym+U";
				$variable_symbol_model->update_variable_symbol($var_sym, $var_sym_id);
				
				
				$member->save_throwable();
				
				// leaving date is in past or today and deletion of devices is enabled
				if ($member->leaving_date <= date('Y-m-d') &&
					Settings::get('former_member_auto_device_remove'))
				{
					$member->delete_members_devices($member_id);
				}

				// reactivate messages
				$member->reactivate_messages();
				
				// recalculates member's fees
				Accounts_Controller::recalculate_member_fees(
					$member->get_credit_account()->id
				);

				// leaving date is in past or today
				if (module::e('notification'))
				{
                                        if ($mess == "19")
					{
						$message = ORM::factory('message')->get_message_by_type(
								    Message_Model::FORMER_MEMBER_MESSAGE);
					}
    
					if ($mess == "21")
                                        {
                                                $message = ORM::factory('message')->get_message_by_type(
                                                                    Message_Model::FORMER_MEMBER_MESSAGE_NOPAYMENT);
                                        }
					
					if ($mess == "23")
                                        {
                                                $message = ORM::factory('message')->get_message_by_type(
                                                                    Message_Model::FORMER_MEMBER_MESSAGE_RETURN_PAYMENT);
						$comment = array('leaving_date' => $member->leaving_date,
								 'ucet' => $form_data['comment']);
                                        }

					if ($mess == "0")
                                        {
                                                $message = ORM::factory('message')->get_message_by_type(
                                                                    Message_Model::FORMER_MEMBER_NOMESSAGE);
                                        }



	    				// create notification object
					$member_notif = array
					(
						'member_id'		=> $member->id,
						'whitelisted'	=> $member->has_whitelist()
					);
					// notify by email
					Notifications_Controller::notify(
							$message, array($member_notif), $this->user_id,
							$comment, FALSE, TRUE, FALSE, FALSE, FALSE, TRUE
					);
				}
				
				$member->transaction_commit();
				
				status::success('Membership of the member has been ended.');
			}
			catch (Exception $e)
			{
				$member->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cant end membership.', $e);
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
		if ($member->type != Member_Model::TYPE_FORMER ||
			!$this->acl_check_edit(get_class($this), 'members', $member_id))
			Controller::error(ACCESS);
		
		try
		{
			$member->transaction_start();
			
			// this sets member to regular member
			$member->leaving_date = '0000-00-00';
			$member->type = Member_Model::TYPE_REGULAR;
			$member->locked = 0;

			$member->save_throwable();
			
			$membership_transfer_model = new Membership_transfer_Model();
			
			// remove membership transfer from member (if exist)
			$membership_transfer_model->delete_transfer_from_member($member->id);
			
			// reload messages of worker
			ORM::factory('member')->reactivate_messages($member->id);
			
			// recalculates member's fees
			Accounts_Controller::recalculate_member_fees(
				$member->get_credit_account()->id
			);
			
			$member->transaction_commit();
			status::success('Membership of the member has been successfully restored.');
		}
		catch (Exception $e)
		{
			$member->transaction_rollback();
			status::error('Error - cant restore membership.', $e);
			Log::add_exception($e);
		}
		
		// redirect
		$this->redirect('members/show/', $member->id);
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
		if (!$this->acl_check_view(get_class($this), 'registration_export', $member_id))
			Controller::error(ACCESS);

		// creates new form
		$form = new Forge('members/registration_export/'.$member_id.'?noredirect=0');
		
		$form->set_attr('class', 'form nopopup');
		
		$form->group('Choose format of export');
		
		$form->dropdown('format')
				->rules('required')
				->options(array
				(
					'p-pdf'	=>	'Prihlaska PDF '.__('document'),
					'p-html'=>	'Prihlaska HTML',
					'o-pdf' =>      'Ukonční PDF '.__('document'),
                                        'o-html'=>      'Ukončení HTML'

				));
		
		$form->submit('Export');

		// form is validate
		if($form->validate())
		{
			$form_data = $form->as_array();

			switch ($form_data["format"])
			{
				case 'p-html':
					// do html export
					die($this->registration_html_export($member_id));
					break;

				case 'p-pdf':
					// do pdf export
					$this->registration_pdf_export($member_id);
					die();
					break;

				case 'o-html':
                                        // do html export
                                        die($this->end_html_export($member_id));
                                        break;

				case 'o-pdf':
                                        // do pdf export
                                        $this->end_pdf_export($member_id);
                                        die();
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
	 * @param integer $member_id
	 * @param string $page_width Width of result page [default 18cm]
	 * @return string Registration in HTML format
	 */
	private function registration_html_export($member_id, $page_width = '18cm')
	{
		$member = new Member_Model($member_id);

		// record doesn't exist
		if ($member->id == 0)
			throw new Exception('Invalid member ID');
		
		// html head
		$page = "<html>";
		$page .= "<head>";
		$page .= "<title>".__('Export of registration').' - '.$member->name."</title>";
		$page .= "</head>";
		$page .= '<body style="font-size:14px">';
		
		// -------------------- LOGO -------------------------------
		
		$logo = Settings::get('registration_logo');
		$page .= '<div style="width:' . $page_width . '">';
		
		if (file_exists($logo))
		{
			$page .= '<div style="float:left; width: 50%">'.
					'<img src="'.url_lang::base().'export/logo" width=274 height=101>'.
					'</div>';
		}
		else	//if logo doesn't exist, insert only blank div
		{
			$page .= '<div style="float:left; width: 50%" width=274 height=101></div>';
		}
		
		// --------------- INFO ABOUT ASSOCIATION -----------------
		
		$page .= '<div style="float:right; position: relative; width: 47%">';
		$page .= '<table style="float:right;">';
		$a_member = new Member_Model(Member_Model::ASSOCIATION);
		
		if (Settings::get('finance_enabled'))
		{
			$ba_model = new Bank_account_Model();
			$a_bank_account_number = NULL;

			if (Settings::get('export_header_bank_account'))
			{
				$ba_model->find(Settings::get('export_header_bank_account'));
			}

			// not set in settings
			if (!$ba_model->id)
			{
				$bank_accounts = $ba_model->get_assoc_bank_accounts();

				if ($bank_accounts->count())
				{
					$a_bank_account_number = $bank_accounts->current()->account_number;
				}
			}
			else
			{
				$a_bank_account_number = $ba_model->account_nr . '/' . $ba_model->bank_nr;
			}
		}
		
		$page .= '<tr><td colspan="2"><b>' . $a_member->name . '</b></td></tr>';
		
		if (!empty($a_member->organization_identifier))
		{
			$page .= '<tr><td>' . __('Organization identifier') . ':</td>
					<td><b>'.$a_member->organization_identifier. '</b></td></tr>';
		}
		
		if (!empty($a_member->vat_organization_identifier))
		{
			$page .= '<tr><td>' . __('VAT organization identifier') . ':</td>
					<td><b>'.$a_member->vat_organization_identifier. '</b></td></tr>';
		}
		
		if (Settings::get('finance_enabled'))
		{	
			$page .= '<tr><td>' . __('Account number').':</td>';
			$page .= '<td><b>' . $a_bank_account_number. '</b></td></tr>';
		}

		$page .= '<tr><td colspan="2"><b>' . $a_member->address_point->street->street.' '.
				$a_member->address_point->street_number. '</b></td></tr>';
		$page .= '<tr><td colspan="2"><b>' . $a_member->address_point->town->zip_code .' '.
				$a_member->address_point->town->town. '</b></td></tr>';
		
		$page .= '</table></div>'.
				'<div style="clear:both;text-align:center;font-weight:bold;margin:0px;">';
		
		// --------------------- MAIN TITLE -------------------------
		
		$page .= '<p style="font-size:1.5em">'.__('Request for membership'). ' – '
				. __('registration in association')."</p>";
		
		// --------------------- INFO -------------------------
		
		$page .= '<div style="text-align: left">'
			  . $this->settings->get('registration_info').'</div>';
		
		// ----------- TABLE WITH INFORMATION ABOUT MEMBER --------
		
		$member_name = $member->name;
		
		$street = $member->address_point->street->street.' '
				.$member->address_point->street_number;
		
		$town = $member->address_point->town->town;
		
		if ($member->address_point->town->quarter != '') 
			$town .= '-'.$member->address_point->town->quarter;
		
		$zip_code = $member->address_point->town->zip_code;
		
		if (Settings::get('finance_enabled'))
		{
			$account_model = new Account_Model();
			$account_id = $account_model->where('member_id',$member_id)->find()->id;
			
			$variable_symbol_model = new Variable_Symbol_Model();
		
			$variable_symbols = array();
			$var_syms = $variable_symbol_model->find_account_variable_symbols($account_id);
		
			foreach ($var_syms as $var_sym)
			{
				$variable_symbols[] = $var_sym->variable_symbol;
			}	
		}
		
		$entrance_date = date::pretty($member->entrance_date);

		$user_model = new User_Model();
		
		$user = $user_model->where('member_id', $member_id)
				->where('type', User_Model::MAIN_USER)
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
		
		$phones = array();
		$arr_contacts = array();
		
		foreach ($contacts as $contact)
		{
			if ($contact->type == Contact_Model::TYPE_PHONE)
			{
			    $phones[] = $contact->value;
			}
			else if($contact->type == Contact_Model::TYPE_ICQ ||
					$contact->type == Contact_Model::TYPE_MSN ||
					$contact->type == Contact_Model::TYPE_JABBER ||
					$contact->type == Contact_Model::TYPE_SKYPE)
			{
			    $arr_contacts[] = $types[$contact->type].': '.$contact->value;
			}
		}
		
		$contact_info = implode('<br />', $arr_contacts);
		
		if (Settings::get('networks_enabled')) 
		{
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
		}
		
		$tbl = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="font-size:14px;">';
		$tbl .= "<tr>";
		$tbl .= "	<td><b>". __('Name') .", ";
		$tbl .= __('Surname') .",".__('Title') ."</b></td>";
		$tbl .= "	<td align=\"center\">$member_name</td>";
		$tbl .= "	<td><b>". __('ID of member') ."</b><br /> (";
		$tbl .= __('according to freenetis') .")</td>";
		$tbl .= "	<td align=\"center\"><b>$member_id</b></td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>". __('Address of connecting place');
		$tbl .= "</b> (". __('Street') .", ";
		$tbl .= __('Street number') .", ";
		$tbl .=  __('Town') . ", " . __('ZIP code') .")</td>";
		$tbl .= "	<td align=\"center\">$street<br />$town<br />$zip_code</td>";
		$tbl .= "	<td><b>". __('Email') ."</b></td>";
		$tbl .= "	<td align=\"center\">$email</td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		$tbl .= "	<td><b>". __('Birthday') ."</b></td>";
		$tbl .= "	<td align=\"center\">$birthday</td>";
		$tbl .= "	<td><b>". __('Phone') ."<b/></td>";
		$tbl .= "	<td align=\"center\">".implode("<br />", $phones)."</td>";
		$tbl .= "</tr>";
		$tbl .= "<tr>";
		if (Settings::get('finance_enabled'))
		{
			$tbl .= "	<td><b>".__('Variable symbol') ."</b></td>";
			$tbl .= "	<td align=\"center\">".implode("<br />", $variable_symbols)."</td>";
		}
		else
			$tbl .= "	<td></td><td></td>";
		$tbl .= "	<td><b>ICQ, Jabber, Skype, ". __('etc') .".</b></td>";
		$tbl .= "	<td align=\"center\">$contact_info</td>";
		$tbl .= "</tr>";
		if (Settings::get('networks_enabled'))
		{
			$tbl .= "<tr>";
			$tbl .= "	<td><b>".__('Subnet')."</b></td>";
			$tbl .= "	<td align=\"center\">$subnet_name</td>";
			$tbl .= "	<td><b>". __('Engineer') ."</b></td>";
			$tbl .= "	<td align=\"center\">$engineers</td>";
			$tbl .= "</tr>";
		}
		$tbl .= "<tr>";
		$tbl .= "	<td><b>". __('Entrance date') ."</b></td>";
		$tbl .= "	<td align=\"center\">$entrance_date</td>";
		$tbl .= "	<td>&nbsp;</td>";
		$tbl .= "	<td align=\"center\">&nbsp;</td>";
		$tbl .= "</tr>";
		$tbl .= "</table>";
		
		$page .= $tbl;
		
		$page .= '<div style="text-align:left">';
		$page .= $this->settings->get('registration_license');
		$page .= "</div>";
		
		$page .= '<br><p style="text-align:right;font-size:1.1em">'
			  .__('Signature of applicant member', NULL, 1)
			  .' : ........................................</p>';
		
		$page .= '<p style="margin-top: 50px; font-size:1.2em">'.
				__('Decision Counsil about adoption of member').'</p>';
		
		$page .= '<p style="text-align:left">'.__('Member adopted on').
				':    .........................................</p>';
		
		$page .= '<p style="text-align:left">'.__('Signature and stamp').
				':    .........................................</p>';
		
		$page .= "</div></div>";
		$page .= "</body>";
		$page .= "</html>";
		
		return $page;
	}

private function end_html_export($member_id, $page_width = '18cm')
    {
	$member = new Member_Model($member_id);

	// record doesn't exist
	if ($member->id == 0)
	    throw new Exception('Invalid member ID');
	
	// html head
	$page = "<html>";
	$page .= "<head>";
	$page .= "<title>".__('Export of end').' - '.$member->name."</title>";
	$page .= "</head>";
	$page .= '<body style="font-size:14px">';
	
	// -------------------- LOGO -------------------------------
	
	$logo = Settings::get('registration_logo');
	$page .= '<div style="width:' . $page_width . '">';
	
	if (file_exists($logo))
	{
	    $page .= '<div style="float:left; width: 50%">'.
		    '<img src="'.url_lang::base().'export/logo" width=274 height=101>'.
		    '</div>';
	}
	else	//if logo doesn't exist, insert only blank div
	{
	    $page .= '<div style="float:left; width: 50%" width=274 height=101></div>';
	}
	
	// --------------- INFO ABOUT ASSOCIATION -----------------
	
	$page .= '<div style="float:right; position: relative; width: 47%">';
	$page .= '<table style="float:right;">';
	$a_member = new Member_Model(Member_Model::ASSOCIATION);
	
	if (Settings::get('finance_enabled'))
	{
	    $ba_model = new Bank_account_Model();
	    $a_bank_account_number = NULL;

	    if (Settings::get('export_header_bank_account'))
	    {
		$ba_model->find(Settings::get('export_header_bank_account'));
	    }

	    // not set in settings
	    if (!$ba_model->id)
	    {
		$bank_accounts = $ba_model->get_assoc_bank_accounts();

		if ($bank_accounts->count())
		{
		    $a_bank_account_number = $bank_accounts->current()->account_number;
		}
	    }
	    else
	    {
		$a_bank_account_number = $ba_model->account_nr . '/' . $ba_model->bank_nr;
	    }
	}
	
	$page .= '<tr><td colspan="2"><b>' . $a_member->name . '</b></td></tr>';
	
	if (!empty($a_member->organization_identifier))
	{
	    $page .= '<tr><td>' . __('Organization identifier') . ':</td>
		    <td><b>'.$a_member->organization_identifier. '</b></td></tr>';
	}
	
	if (!empty($a_member->vat_organization_identifier))
	{
	    $page .= '<tr><td>' . __('VAT organization identifier') . ':</td>
		    <td><b>'.$a_member->vat_organization_identifier. '</b></td></tr>';
	}
	
	if (Settings::get('finance_enabled'))
	{	
	    $page .= '<tr><td>' . __('Account number').':</td>';
	    $page .= '<td><b>' . $a_bank_account_number. '</b></td></tr>';
	}
	
	$page .= '<tr><td colspan="2"><b>' . $a_member->address_point->street->street.' '.
		$a_member->address_point->street_number. '</b></td></tr>';
	$page .= '<tr><td colspan="2"><b>' . $a_member->address_point->town->zip_code .' '.
		$a_member->address_point->town->town. '</b></td></tr>';
	
	$page .= '</table></div>'.
		'<div style="clear:both;text-align:center;font-weight:bold;margin:0px;">';
	
	// --------------------- MAIN TITLE -------------------------
	
	$page .= '<p style="font-size:1.5em">'.__('Žádost o ukončení členství v PVfree.net, z. s.')
	."</p>";
	
	// --------------------- INFO -------------------------
	
	//$page .= '<div style="text-align: left">'
	//      . $this->settings->get('registration_info').'</div>';
	
	// ----------- TABLE WITH INFORMATION ABOUT MEMBER --------
	
	$member_name = $member->name;
	
	$street = $member->address_point->street->street.' '
		.$member->address_point->street_number;
	
	$town = $member->address_point->town->town;
	
	if ($member->address_point->town->quarter != '') 
	    $town .= '-'.$member->address_point->town->quarter;
	
	$zip_code = $member->address_point->town->zip_code;
	
	if (Settings::get('finance_enabled'))
	{
	    $account_model = new Account_Model();
	    $account_id = $account_model->where('member_id',$member_id)->find()->id;
	    
	    $variable_symbol_model = new Variable_Symbol_Model();
	
	    $variable_symbols = array();
	    $var_syms = $variable_symbol_model->find_account_variable_symbols($account_id);
	
	    foreach ($var_syms as $var_sym)
	    {
		$variable_symbols[] = $var_sym->variable_symbol;
	    }	
	}
	
	$entrance_date = date::pretty($member->entrance_date);

	$user_model = new User_Model();
	
	$user = $user_model->where('member_id', $member_id)
		->where('type', User_Model::MAIN_USER)
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
	
	$phones = array();
	$arr_contacts = array();
	
	foreach ($contacts as $contact)
	{
	    if ($contact->type == Contact_Model::TYPE_PHONE)
	    {
	        $phones[] = $contact->value;
	    }
	    else if($contact->type == Contact_Model::TYPE_ICQ ||
		    $contact->type == Contact_Model::TYPE_MSN ||
		    $contact->type == Contact_Model::TYPE_JABBER ||
		    $contact->type == Contact_Model::TYPE_SKYPE)
	    {
	        $arr_contacts[] = $types[$contact->type].': '.$contact->value;
	    }
	}
	
	$contact_info = implode('<br />', $arr_contacts);
	
	if (Settings::get('networks_enabled')) 
	{
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
	}
	
	$tbl = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="font-size:14px;">';
	$tbl .= "<tr>";
	$tbl .= "	<td><b>". __('Name') .", ";
	$tbl .= __('Surname') .",".__('Title') ."</b></td>";
	$tbl .= "	<td align=\"center\">$member_name</td>";
	$tbl .= "	<td><b>". __('ID of member') ."</b><br /> (";
	$tbl .= __('according to freenetis') .")</td>";
	$tbl .= "	<td align=\"center\"><b>$member_id</b></td>";
	$tbl .= "</tr>";
	$tbl .= "<tr>";
	$tbl .= "	<td><b>". __('Address of connecting place');
	$tbl .= "</b> (". __('Street') .", ";
	$tbl .= __('Street number') .", ";
	$tbl .=  __('Town') . ", " . __('ZIP code') .")</td>";
	$tbl .= "	<td align=\"center\">$street<br />$town<br />$zip_code</td>";
	$tbl .= "	<td><b>". __('Email') ."</b></td>";
	$tbl .= "	<td align=\"center\">$email</td>";
	$tbl .= "</tr>";
	$tbl .= "<tr>";
	$tbl .= "	<td><b>". __('Birthday') ."</b></td>";
	$tbl .= "	<td align=\"center\">$birthday</td>";
	$tbl .= "	<td><b>". __('Phone') ."<b/></td>";
	$tbl .= "	<td align=\"center\">".implode("<br />", $phones)."</td>";
	$tbl .= "</tr>";
	$tbl .= "<tr>";
	if (Settings::get('finance_enabled'))
	{
	    $tbl .= "	<td><b>".__('Variable symbol') ."</b></td>";
	    $tbl .= "	<td align=\"center\">".implode("<br />", $variable_symbols)."</td>";
	}
	else
	    $tbl .= "	<td></td><td></td>";
	$tbl .= "	<td><b>".__('Kredit') ."</b></td>";
	$tbl .= "	<td align=\"center\">$contact_info</td>";
	$tbl .= "</tr>";
/*	if (Settings::get('networks_enabled'))
	{
	    $tbl .= "<tr>";
	    $tbl .= "	<td><b>".__('Subnet')."</b></td>";
	    $tbl .= "	<td align=\"center\">$subnet_name</td>";
	    $tbl .= "	<td><b>". __('Engineer') ."</b></td>";
	    $tbl .= "	<td align=\"center\">$engineers</td>";
	    $tbl .= "</tr>";
	}
*/
	$tbl .= "<tr>";
	$tbl .= "	<td><b>". __('Entrance date') ."</b></td>";
	$tbl .= "	<td align=\"center\">$entrance_date</td>";
	$tbl .= "	<td><b>". __('Datum ukončení') ."</b></td>";
	$tbl .= "	<td align=\"center\">&nbsp;</td>";
	$tbl .= "</tr>";
	$tbl .= "</table>";
	
	$page .= $tbl;
	$page .= '<br>';
	$page .= '<br>';

	$page .= '<br><p style="text-align:left">'
              .__('Žádám o vrácení přeplatku na č.ú.:')
              .' : ......................................../.............</p>';

	


/*	$page .= '<div style="text-align:left">';
	$page .= $this->settings->get('registration_license');
	$page .= "</div>";
*/	
	$page .= '<br><br><p style="text-align:left">'
              .__('Datum')
              .' :      '. date("d.m.Y") . '</p>';

	$page .= '<br><br><p style="text-align:left">'
	      .__(' Podpis')
	      .' : ........................................</p>';
	
//	$page .= '<p style="margin-top: 50px; font-size:1.2em">'.
//		__('Decision Counsil about adoption of member').'</p>';
	
//	$page .= '<p style="text-align:left">'.__('Member adopted on').
//		':    .........................................</p>';
	
//	$page .= '<p style="text-align:left">'.__('Signature and stamp').
//		':    .........................................</p>';
	
	$page .= "</div></div>";
	$page .= "</body>";
	$page .= "</html>";
	
	return $page;
    }




	/**
	 * Function to export registration of member to pdf-format
	 * 
	 * @author Ondřej Fibich
	 * @param integer $member_id	id of member to export
	 */
	private function registration_pdf_export($member_id)
	{
		$member = new Member_Model($member_id);

		// record doesn't exist
		if ($member->id == 0)
			throw new Exception('Invalid member ID');
		
		// include pdf library
		require_once(APPPATH.'vendors/vendor/autoload.php');
		
		// get HTML content
		$html = $this->registration_html_export($member_id);
		// logo image change (cannot load image from export/logo)
		$html_logo_correct = str_replace(
				url_lang::base() . 'export/logo',
				Settings::get('registration_logo'),
				$html
		);
		
		// transform it to PDF
		$filename = url::title(__('registration').'-'.$member->name).'.pdf';
		$mpdf = new \Mpdf\Mpdf();
		$mpdf->WriteHTML($html_logo_correct);
		$mpdf->Output($filename, 'I');
	}

    /**
     * Function to export registration of member to pdf-format
     * 
     * @author OndĹ™ej Fibich
     * @param integer $member_id	id of member to export
     */
    private function end_pdf_export($member_id)
    {
	$member = new Member_Model($member_id);

	// record doesn't exist
	if ($member->id == 0)
	    throw new Exception('Invalid member ID');
	
	// include pdf library
	require_once(APPPATH.'vendors/vendor/autoload.php');
	
	// get HTML content
	$html = $this->end_html_export($member_id);
	// logo image change (cannot load image from export/logo)
	$html_logo_correct = str_replace(
		url_lang::base() . 'export/logo',
		Settings::get('registration_logo'),
		$html
	);
	
	// transform it to PDF
	$filename = url::title(__('Ukončení').'-'.$member->name).'.pdf';
	$mpdf = new \Mpdf\Mpdf();
	$mpdf->WriteHTML($html_logo_correct);
	$mpdf->Output($filename, 'I');
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
		$username_regex = Settings::get('username_regex');
		
		if ($user_model->username_exist($input->value) && !trim($input->value)=='')
		{
			$input->add_error('required', __('Username already exists in database'));
		}
		else if (!preg_match($username_regex, $input->value))
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
		
		$user_model = new User_Model();
		$value = trim($input->value);
		
		if (!preg_match("/^[0-9]{9,9}$/",$value))
		{
			$input->add_error('required', __('Bad phone format.'));
		}
		else if (!Settings::get('user_phone_duplicities_enabled') &&
                $user_model->phone_exist($value))
		{
			$input->add_error('required', __('Phone already exists in database.'));
		}
	}

    /**
     * Check if non empty email is unique.
     *
     * @param object $input
     */
    public function valid_unique_email($input = NULL)
    {
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}

		$user_model = new User_Model();
		$value = trim($input->value);

        // not required by default
        if ($value && !Settings::get('user_email_duplicities_enabled') &&
                $user_model->email_exist($value))
        {
            $input->add_error('required', __('Email already exists in database.'));
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
		
		if (Settings::get('membership_interrupt_enabled'))
		{
			$filter_form->add('membership_interrupt')
					->type('select')
					->values(arr::bool());
		}
		
		if (Settings::get('finance_enabled'))
		{
			$filter_form->add('balance')
					->table('a')
					->type('number');

			$filter_form->add('variable_symbol')
					->table('vs')
					->callback('json/variable_symbol');
		}
		
		$filter_form->add('entrance_date')
				->type('date');
		
		$filter_form->add('leaving_date')
				->type('date');
		
		if (Settings::get('finance_enabled'))
		{
			$filter_form->add('entrance_fee')
					->type('number');
		}
		
		$filter_form->add('comment');
		
		$filter_form->add('registration')
				->type('select')
				->values(arr::bool());
		
		$filter_form->add('organization_identifier')
				->callback('json/organization_identifier');
		
		$filter_form->add('vat_organization_identifier')
				->callback('json/vat_organization_identifier');
		
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
				->table('ap');
		
		if (Settings::get('redirection_enabled'))
		{
			$filter_form->add('redirect_type_id')
					->label(__('Redirection'))
					->type('select')
					->values(array
					(
						Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE => __('Membership interrupt'),
						Message_Model::DEBTOR_MESSAGE => __('Debtor'),
						Message_Model::BIG_DEBTOR_MESSAGE => __('Big debtor'),
						Message_Model::PAYMENT_NOTICE_MESSAGE => __('Payment notice'),
						Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE => __('Unallowed connecting place'),
						Message_Model::CONNECTION_TEST_EXPIRED => __('Connection test expired'),
						Message_Model::USER_MESSAGE => __('User message')
					))->table('ms');
		}
		
		if (module::e('notification'))
		{
			$filter_form->add('whitelisted')
					->label(__('Whitelist'))
					->type('select')
					->table('ip')
					->values(Ip_address_Model::get_whitelist_types());
		}
		
		$filter_form->add('speed_class_id')
				->table('m')
				->label('Speed class')
				->type('select')
				->values(ORM::factory('speed_class')->select_list());
		
		if (Settings::get('networks_enabled'))
		{
			$filter_form->add('cloud')
				->table('cl')
				->type('select')
				->values(ORM::factory('cloud')->select_list());
		}
		
		$filter_form->add('login')
				->label('Login name')
				->table('u')
				->callback('json/user_login');

		$filter_form->add('login')
				->label('Added by (login)')
				->table('ua')
				->callback('json/user_login');
		
		return $filter_form;
	}
	
	/**
	 * Syncs contacts from FreenetIS to Vtiger CRM.
	 * 
	 * @author Jan Dubina
	 */
	public static function vtiger_sync()
	{
		ini_set('max_execution_time', 3600);
		
		//set size of chucks used to create and update records vie webservice
		$chunk_size = 100;

		// vtiger web service client
		$client = new Vtiger_WSClient(Settings::get('vtiger_domain'));
		
		//login
		$login = $client->doLogin(Settings::get('vtiger_username'), 
									Settings::get('vtiger_user_access_key'));
		
		// if logged in
		if ($login)
		{
			//number of contact (phone and email) fields in vtiger
			$contacts_max = 3;
			
			// module names
			$module_contacts = 'Contacts';
			$module_accounts = 'Accounts';
			
			$user_model = new User_Model();
			$member_model = new Member_Model();

			// get vtiger field names from settings
			$account_fields = json_decode(Settings::get('vtiger_member_fields'), true);
			$contact_fields = json_decode(Settings::get('vtiger_user_fields'), true);

			//check fields in vtiger - accounts
			$accounts_desc = $client->doDescribe($module_accounts);
			
			if (is_array($accounts_desc))
			{
				$fields = array();

				foreach ($accounts_desc['fields'] as $desc)
				{
					$fields[] = $desc['name'];

					// check mandatory fields
					if ($desc['mandatory'] == 1 && !in_array($desc['name'], $account_fields) && 
							$desc['name'] != 'assigned_user_id')
						throw new Exception(__('Error - wrong vtiger fields settings'));
				}

				foreach ($account_fields as $field)
					if (!in_array($field, $fields))
						throw new Exception(__('Error - wrong vtiger fields settings'));
			}
			else
				throw new Exception(__('Error - vtiger webservice not responding'));

			//check fields in vtiger - contacts
			$contacts_desc = $client->doDescribe($module_contacts);
			
			if(is_array($contacts_desc))
			{
				$fields = array();

				foreach ($contacts_desc['fields'] as $desc)
				{
					$fields[] = $desc['name'];

					//check mandatory fields
					if ($desc['mandatory'] == 1 && !in_array($desc['name'], $contact_fields) &&
							$desc['name'] != 'assigned_user_id')
						throw new Exception(__('Error - wrong vtiger fields settings'));
				}

				foreach ($contact_fields as $field)
					if (!in_array($field, $fields))
						throw new Exception(__('Error - wrong vtiger fields settings'));
			}
			else
				throw new Exception(__('Error - vtiger webservice not responding'));

			// create new accounts
			// get member ids from vtiger
			$query = 'SELECT id,' . $account_fields['id'] . ' FROM ' . $module_accounts; 
			$accounts_create_ids = array();
			
			$result = $client->doQueryNotLimited($query);

			if(is_array($result))
			{
				// array with links between vtiger ids and fntis ids
				$links = array();
				
				foreach ($result as $value) {
					$links[$value['id']] = $value[$account_fields['id']];
				}
				
				$vtiger_ids = array_values($links);
	
				// get member ids from freenetis
				$result = $member_model->select_list('id');
				$freenetis_ids = array();

				foreach ($result as $key => $value) {
					$freenetis_ids[] = $key;
				}

				// ids of members that need to be created
				$accounts_create_ids = array_unique(array_filter(array_diff($freenetis_ids, $vtiger_ids)));

				//if there are members to be created
				if(!empty($accounts_create_ids))
				{
					$accounts_create = $member_model->get_members_to_sync_vtiger($accounts_create_ids, true);

					$accounts_create_vtiger = array();

					foreach ($accounts_create as $account)
					{
						$acc_arr = array();
						$acc_arr[$account_fields['id']] = $account->id;
						$acc_arr[$account_fields['name']] = $account->name;
						$acc_arr[$account_fields['acc_type']] = 'Customer';
						$acc_arr[$account_fields['entrance_date']] = strtotime($account->entrance_date);
						$acc_arr[$account_fields['organization_identifier']] = $account->organization_identifier;
						$acc_arr[$account_fields['var_sym']] = $account->variable_symbol;
						$acc_arr[$account_fields['type']] = Member_Model::get_type($account->type);
						$acc_arr[$account_fields['street']] = address::street_join($account->street,
																					$account->street_number);
						$acc_arr[$account_fields['town']] = $account->town;
						$acc_arr[$account_fields['country']] = $account->country_name;
						$acc_arr[$account_fields['zip_code']] = $account->zip_code;
						$acc_arr[$account_fields['employees']] = $account->employees;
						$acc_arr[$account_fields['do_not_send_emails']] = false;
						$acc_arr[$account_fields['notify_owner']] = true;
						$acc_arr[$account_fields['comment']] = $account->comment;

						$phone = explode(';', $account->phone);

						for ($i = 0; $i<$contacts_max; $i++)
						{
							$key = 'phone' . ($i + 1);
							if(array_key_exists($i, $phone))
								$acc_arr[$account_fields[$key]] = $phone[$i];
						}

						$email = explode(';', $account->email);

						for ($i = 0; $i<$contacts_max; $i++)
						{
							$key = 'email' . ($i + 1);
							if(array_key_exists($i, $email))
								$acc_arr[$account_fields[$key]] = $email[$i];
						}

						$accounts_create_vtiger[] = $acc_arr;
					}

					if (count($accounts_create_vtiger) > $chunk_size)
					{
						$create_arrays = array_chunk($accounts_create_vtiger, $chunk_size);

						foreach ($create_arrays as $create_array)
						{
							$records = $client->doCreateBulk($module_accounts, $create_array);

							if(is_array($records))
								foreach ($records as $record)
									if (array_key_exists($account_fields['id'], $record))
										$links[$record['id']] = $record[$account_fields['id']];						
						}
					}
					else 
					{
						$records = $client->doCreateBulk($module_accounts, $accounts_create_vtiger);

						//create array with links between freenetis ID and vtiger ID
						if(is_array($records))
							foreach ($records as $record)
								if (array_key_exists($account_fields['id'], $record))
									$links[$record['id']] = $record[$account_fields['id']];
					}
				}
			}
			
			// create new contacts
			// get user ids from vtiger
			$query = 'SELECT ' . $contact_fields['id'] . ' FROM ' . $module_contacts; 
			$contacts_create_ids = array();
			
			$result = $client->doQueryNotLimited($query);

			if(is_array($result) && isset($links))
			{
				$vtiger_ids = array();

				foreach ($result as $value) {
					$vtiger_ids[] = $value[$contact_fields['id']];
				}

				//get member ids from freenetis
				$result = $user_model->select_list('id');
				$freenetis_ids = array();

				foreach ($result as $key => $value)
					$freenetis_ids[] = $key;

				// ids of users that need to be created
				$contacts_create_ids = array_unique(array_filter(array_diff($freenetis_ids, $vtiger_ids)));

				//if there are users to be created
				if(!empty($contacts_create_ids))
				{
					$contacts_create = $user_model->get_users_to_sync_vtiger($contacts_create_ids, true);

					$contacts_create_vtiger = array();

					foreach ($contacts_create as $contact)
					{
						$con_arr = array();
						$con_arr[$contact_fields['id']] = $contact->id;
						$con_arr[$contact_fields['name']] = $contact->name;
						$con_arr[$contact_fields['middle_name']] = $contact->middle_name;
						$con_arr[$contact_fields['surname']] = $contact->surname;
						$con_arr[$contact_fields['pre_title']] = $contact->pre_title;
						$con_arr[$contact_fields['post_title']] = $contact->post_title;
						$con_arr[$contact_fields['birthday']] = date("d-m-Y", strtotime($contact->birthday));
						$con_arr[$contact_fields['comment']] = $contact->comment;
						$con_arr[$contact_fields['street']] = address::street_join($contact->street, 
																					$contact->street_number);
						$con_arr[$contact_fields['town']] = $contact->town;
						$con_arr[$contact_fields['zip_code']] = $contact->zip_code;
						$con_arr[$contact_fields['country']] = $contact->country_name;
						$con_arr[$contact_fields['do_not_call']] = false;
						$con_arr[$contact_fields['do_not_send_emails']] = false;
						$con_arr[$contact_fields['notify_owner']] = true;

						// try to link member and user record
						$key = array_search($contact->member_id, $links);
						if(!empty($key))
							$con_arr[$contact_fields['member_id']] = $key;
						else 
						{
							// create new array with links
							$query = 'SELECT id,' . $account_fields['id'] . ' FROM ' . $module_accounts; 
			
							$result = $client->doQueryNotLimited($query);

							if(is_array($result))
							{
								$links = array();

								foreach ($result as $value) {
									$links[$value['id']] = $value[$account_fields['id']];
								}
							}		
						}

						$phone = explode(';', $contact->phone);

						for ($i = 0; $i<$contacts_max; $i++)
						{
							$key = 'phone' . ($i + 1);
							if(array_key_exists($i, $phone))
								$con_arr[$contact_fields[$key]] = $phone[$i];
						}

						$email = explode(';', $contact->email);

						for ($i = 0; $i<$contacts_max; $i++)
						{
							$key = 'email' . ($i + 1);
							if(array_key_exists($i, $email))
								$con_arr[$contact_fields[$key]] = $email[$i];
						}

						$contacts_create_vtiger[] = $con_arr;
					}

					if (count($contacts_create_vtiger) > $chunk_size)
					{
						$create_arrays = array_chunk($contacts_create_vtiger, $chunk_size);

						foreach ($create_arrays as $create_array)
							$records = $client->doCreateBulk($module_contacts, $create_array);
					}
					else 
					{
						$records = $client->doCreateBulk($module_contacts, $contacts_create_vtiger);
					}
				}
			}
			
			// delete users
			$contacts_delete_ids = array_unique(array_filter(array_diff($vtiger_ids, $freenetis_ids)));

			if (!empty($contacts_delete_ids))
			{		
				$query = 'SELECT id FROM ' . $module_contacts . ' WHERE ' . 
							$contact_fields['id'] . ' IN (' . 
							implode(',', $contacts_delete_ids) . ')';
				
				$results = $client->doQueryNotLimited($query);

				if(is_array($results))
					foreach ($results as $result)
						$client->doDelete($result['id']);
			}

			// update accounts
			$accounts_update = $member_model->get_members_to_sync_vtiger($accounts_create_ids, false);

			if($accounts_update->count() != 0)
			{
				$query = 'SELECT * FROM ' . $module_accounts;

				$results = $client->doQueryNotLimited($query);
				
				if(is_array($results))
				{
					$accounts_update_vtiger = array();

					// "NOT IN" statement not working in vtiger ws query
					foreach ($results as $result)
						if (!in_array($result[$account_fields['id']], $accounts_create_ids))
							$accounts_update_vtiger[$result[$account_fields['id']]] = $result;

					$update = array();

					foreach ($accounts_update as $record)
					{
						$record_vtiger = $accounts_update_vtiger[$record->id];

						if ($record->name != $record_vtiger[$account_fields['name']])
							$record_vtiger[$account_fields['name']] = $record->name;

						if ($record->organization_identifier != $record_vtiger[$account_fields['organization_identifier']])
							$record_vtiger[$account_fields['organization_identifier']] = $record->organization_identifier;

						if ($record->entrance_date != $record_vtiger[$account_fields['entrance_date']] &&
								!(empty($record->entrance_date) && $record_vtiger[$account_fields['entrance_date']] == '1970-01-01'))
							$record_vtiger[$account_fields['entrance_date']] = $record->entrance_date;	

						if ($record->variable_symbol != $record_vtiger[$account_fields['var_sym']])
							$record_vtiger[$account_fields['var_sym']] = $record->variable_symbol;

						if (Member_Model::get_type($record->type) != $record_vtiger[$account_fields['type']])
							$record_vtiger[$account_fields['type']] = Member_Model::get_type($record->type);

						$street = address::street_join($record->street, $record->street_number);

						if ($street != $record_vtiger[$account_fields['street']])
							$record_vtiger[$account_fields['street']] = $street;

						if ($record->town != $record_vtiger[$account_fields['town']])
							$record_vtiger[$account_fields['town']] = $record->town;

						if ($record->country_name != $record_vtiger[$account_fields['country']])
							$record_vtiger[$account_fields['country']] = $record->country_name;

						if ($record->zip_code != $record_vtiger[$account_fields['zip_code']])
							$record_vtiger[$account_fields['zip_code']] = $record->zip_code;

						if ($record->employees != $record_vtiger[$account_fields['employees']])
							$record_vtiger[$account_fields['employees']] = $record->employees;

						if ($record->comment != $record_vtiger[$account_fields['comment']])
							$record_vtiger[$account_fields['comment']] = $record->comment;

						// update contact information - phone
						$phone = explode(';', $record->phone);

						$phone_vtiger = array(
												$accounts_update_vtiger[$record->id][$account_fields['phone1']],
												$accounts_update_vtiger[$record->id][$account_fields['phone2']],
												$accounts_update_vtiger[$record->id][$account_fields['phone3']]
						);

						$phone_vtiger_orig = $phone_vtiger;

						$phone_vtiger = array_filter($phone_vtiger);

						$add_phone_nos = array_diff($phone, $phone_vtiger);
						$phone_diff = array_diff($phone_vtiger, $phone);

						if (!empty($phone_diff) || !empty($add_phone_nos))
						{
							if (!empty($phone_diff))
								foreach($phone_diff as $key => $value)
									unset($phone_vtiger[$key]);

							if (!empty($add_phone_nos) && (count($phone_vtiger) < $contacts_max))
								foreach($add_phone_nos as $phone_no)
									if (count($phone_vtiger) < $contacts_max)
										$phone_vtiger[] = $phone_no;

							$new_phone_nos = array_values(array_diff($phone_vtiger, array_filter($phone_vtiger_orig)));

							for ($i = 0; $i < $contacts_max; $i++)
							{
								if (!in_array($phone_vtiger_orig[$i], $phone_vtiger))
								{
									if (!empty($new_phone_nos))
									{
										$record_vtiger[$account_fields['phone'.($i+1)]] = $new_phone_nos[0];
										unset($new_phone_nos[0]);
										$new_phone_nos = array_values($new_phone_nos);
									}
									else
										$record_vtiger[$account_fields['phone'.($i+1)]] = '';
								}
							}
						}

						// update contact information - email
						$email = explode(';', $record->email);

						$email_vtiger = array(
												$accounts_update_vtiger[$record->id][$account_fields['email1']],
												$accounts_update_vtiger[$record->id][$account_fields['email2']],
												$accounts_update_vtiger[$record->id][$account_fields['email3']]
						);

						$email_vtiger_orig = $email_vtiger;

						$email_vtiger = array_filter($email_vtiger);

						$add_emails = array_diff($email, $email_vtiger);
						$email_diff = array_diff($email_vtiger, $email);

						if (!empty($email_diff) || !empty($add_emails))
						{
							if (!empty($email_diff))
								foreach($email_diff as $key => $value)
									unset($email_vtiger[$key]);

							if (!empty($add_emails) && (count($email_vtiger) < $contacts_max))
								foreach($add_emails as $emails)
									if (count($email_vtiger) < $contacts_max)
										$email_vtiger[] = $emails;

							$new_emails = array_values(array_diff($email_vtiger, $email_vtiger_orig));

							for ($i = 0; $i < $contacts_max; $i++)
							{
								if (!in_array($email_vtiger_orig[$i], $email_vtiger))
								{
									if (!empty($new_emails))
									{
										$record_vtiger[$account_fields['email'.($i+1)]] = $new_emails[0];
										unset($new_emails[0]);
										$new_emails = array_values($new_emails);
									}
									else
										$record_vtiger[$account_fields['email'.($i+1)]] = '';
								}
							}
						}

						// checks if original and updated records are different
						$update_diff = array_diff($record_vtiger, $accounts_update_vtiger[$record->id]);

						if (!empty($update_diff))
							$update[] = $record_vtiger;
					}

					if (count($update) > $chunk_size)
					{
						$create_arrays = array_chunk($update, $chunk_size);

						foreach ($create_arrays as $create_array)
							$client->doUpdateBulk($module_accounts, $create_array);
					}
					else 
					{
						$client->doUpdateBulk($module_accounts, $update);
					}
				}
			}

			//update contacts
			$contacts_update = $user_model->get_users_to_sync_vtiger($contacts_create_ids, false);
			
			if ($contacts_update->count() != 0)
			{
				$query = 'SELECT * FROM ' . $module_contacts;

				$results = $client->doQueryNotLimited($query);
				
				if(is_array($results))
				{
					$contacts_update_vtiger = array();

					// "NOT IN" statement not working in vtiger ws query
					foreach ($results as $result)
						if (!in_array($result[$contact_fields['id']], $contacts_create_ids))
							$contacts_update_vtiger[$result[$contact_fields['id']]] = $result;

					$update = array();

					foreach ($contacts_update as $record)
					{
						$record_vtiger = $contacts_update_vtiger[$record->id];

						if ($record->name != $record_vtiger[$contact_fields['name']])
							$record_vtiger[$contact_fields['name']] = $record->name;

						if ($record->middle_name != $record_vtiger[$contact_fields['middle_name']])
							$record_vtiger[$contact_fields['middle_name']] = $record->middle_name;

						if ($record->surname != $record_vtiger[$contact_fields['surname']])
							$record_vtiger[$contact_fields['surname']] = $record->surname;

						if ($record->pre_title != $record_vtiger[$contact_fields['pre_title']])
							$record_vtiger[$contact_fields['pre_title']] = $record->pre_title;

						if ($record->post_title != $record_vtiger[$contact_fields['post_title']])
							$record_vtiger[$contact_fields['post_title']] = $record->post_title;

						if ($record->birthday != $record_vtiger[$contact_fields['birthday']] &&
								!(empty($record->birthday) && $record_vtiger[$contact_fields['birthday']] == '1970-01-01'))
							$record_vtiger[$contact_fields['birthday']] = $record->birthday;

						if ($record->comment != $record_vtiger[$contact_fields['comment']])
							$record_vtiger[$contact_fields['comment']] = $record->comment;

						$street = address::street_join($record->street, $record->street_number);

						if ($street != $record_vtiger[$contact_fields['street']])
							$record_vtiger[$contact_fields['street']] = $street;

						if ($record->town != $record_vtiger[$contact_fields['town']])
							$record_vtiger[$contact_fields['town']] = $record->town;

						if ($record->zip_code != $record_vtiger[$contact_fields['zip_code']])
							$record_vtiger[$contact_fields['zip_code']] = $record->zip_code;

						if ($record->country_name != $record_vtiger[$contact_fields['country']])
							$record_vtiger[$contact_fields['country']] = $record->country_name;

						if ($record->member_id != $links[$record_vtiger[$contact_fields['member_id']]])
						{
							$key = array_search ($record->member_id, $links);
							if(!empty($key))
								$record_vtiger[$contact_fields['member_id']] = $key;
						}

						//update contact information - phone
						$phone = explode(';', $record->phone);

						$phone_vtiger = array(
												$contacts_update_vtiger[$record->id][$contact_fields['phone1']],
												$contacts_update_vtiger[$record->id][$contact_fields['phone2']],
												$contacts_update_vtiger[$record->id][$contact_fields['phone3']]
						);

						$phone_vtiger_orig = $phone_vtiger;

						$phone_vtiger = array_filter($phone_vtiger);

						$add_phone_nos = array_diff($phone, $phone_vtiger);
						$phone_diff = array_diff($phone_vtiger, $phone);

						if (!empty($phone_diff) || !empty($add_phone_nos))
						{
							if (!empty($phone_diff))
								foreach($phone_diff as $key => $value)
									unset($phone_vtiger[$key]);

							if (!empty($add_phone_nos) && (count($phone_vtiger) < $contacts_max))
								foreach($add_phone_nos as $phone_no)
									if (count($phone_vtiger) < $contacts_max)
										$phone_vtiger[] = $phone_no;

							$new_phone_nos = array_values(array_diff($phone_vtiger, array_filter($phone_vtiger_orig)));

							for ($i = 0; $i < $contacts_max; $i++)
							{
								if (!in_array($phone_vtiger_orig[$i], $phone_vtiger))
								{
									if (!empty($new_phone_nos))
									{
										$record_vtiger[$contact_fields['phone'.($i+1)]] = $new_phone_nos[0];
										unset($new_phone_nos[0]);
										$new_phone_nos = array_values($new_phone_nos);
									}
									else
										$record_vtiger[$contact_fields['phone'.($i+1)]] = '';
								}
							}
						}

						//update contact information - email
						$email = explode(';', $record->email);

						$email_vtiger = array(
												$contacts_update_vtiger[$record->id][$contact_fields['email1']],
												$contacts_update_vtiger[$record->id][$contact_fields['email2']],
												$contacts_update_vtiger[$record->id][$contact_fields['email3']]
						);

						$email_vtiger_orig = $email_vtiger;
						
						$email_vtiger = array_filter($email_vtiger);

						$add_emails = array_diff($email, $email_vtiger);
						$email_diff = array_diff($email_vtiger, $email);

						if (!empty($email_diff) || !empty($add_emails))
						{
							if (!empty($email_diff))
								foreach($email_diff as $key => $value)
									unset($email_vtiger[$key]);

							if (!empty($add_emails) && (count($email_vtiger) < $contacts_max))
								foreach($add_emails as $emails)
									if (count($email_vtiger) < $contacts_max)
										$email_vtiger[] = $emails;

							$new_emails = array_values(array_diff($email_vtiger, $email_vtiger_orig));

							for ($i = 0; $i < $contacts_max; $i++)
							{
								if (!in_array($email_vtiger_orig[$i], $email_vtiger))
								{
									if (!empty($new_emails))
									{
										$record_vtiger[$contact_fields['email'.($i+1)]] = $new_emails[0];
										unset($new_emails[0]);
										$new_emails = array_values($new_emails);
									}
									else
										$record_vtiger[$contact_fields['email'.($i+1)]] = '';
								}
							}
						}

						// checks if original and updated records are different
						$update_diff = array_diff($record_vtiger, $contacts_update_vtiger[$record->id]);

						if (!empty($update_diff))
							$update[] = $record_vtiger;
					}

					if (count($update) > $chunk_size)
					{
						$create_arrays = array_chunk($update, $chunk_size);

						foreach ($create_arrays as $create_array)
							$client->doUpdateBulk($module_contacts, $create_array);
					}
					else 
					{
						$client->doUpdateBulk($module_contacts, $update);
					}
				}
			}
		}
		else
		{
			//login failed
			throw new Exception(__('Connection to vtiger server has failed'));
		}
	}
}
