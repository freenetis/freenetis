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
 * The API accounts controller is used for managing of API accounts that are
 * used for authentization and autentification in API.
 *
 * @author Ondřej Fibich
 * @package Controller
 * @since 1.2
 */
class Api_accounts_Controller extends Controller
{
	/**
	 * Edit API account ID for validator.
	 *
	 * @var integer
	 */
	private $edit_id = NULL;
	
	/**
	 * Constructor, only test if API is enabled
	 */
	public function __construct()
	{
		parent::__construct();
		
		// access control
		if (!module::e('api'))
		{
			self::error(ACCESS);
		}
	}
	
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		$this->redirect('api_accounts/show_all');
	}
	
	/**
	 * Shows all accounts
	 */
	public function show_all()
	{
		// access check
		if (!$this->acl_check_view('Api_Controller', 'account'))
		{
			self::error(ACCESS);
		}

		// model
		$api_account_model = new Api_account_Model();

		// gets data
		$data = $api_account_model->find_all();

		// grid
		$grid = new Grid('api_accounts', null, array
		(
			'use_paginator'	=> false,
			'use_selector'	=> false
		));

		if ($this->acl_check_new('Api_Controller', 'account'))
		{
			$grid->add_new_button('api_accounts/add', 'Add new API account', array
			(
				'class' => 'popup_link'
			));
		}

		$grid->field('id');
		
		$grid->field('username');
		
		$grid->callback_field('enabled')
				->callback('callback::boolean');
		
		$grid->callback_field('readonly')
				->callback('callback::boolean');
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action()
				->icon_action('show')
				->url('api_accounts/show');
		
		if ($this->acl_check_view('Api_Controller', 'account_log'))
		{
			$actions->add_action()
					->icon_action('action_logs')
					->url('api_accounts/show_logs');
		}
		
		if ($this->acl_check_edit('Api_Controller', 'account'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('api_accounts/edit')
					->class('popup_link');
		}
		
		if ($this->acl_check_delete('Api_Controller', 'account'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('api_accounts/delete')
					->class('delete_link');
		}
		
		// load datasource
		$grid->datasource($data);
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->text('API accounts')
				->html();

		// main view
		$view = new View('main');
		$view->title = __('List of all API accounts');
		$view->content = new View('show_all');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->headline = __('List of all API accounts');
		$view->content->table = $grid;
		$view->render(TRUE);
	}
	
	/**
	 * Show API account
	 * 
	 * @param integer $api_acount_id
	 */
	public function show($api_acount_id = NULL)
	{
		// check param
		if (!$api_acount_id || !is_numeric($api_acount_id))
		{
			self::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_new('Api_Controller', 'account'))
		{
			self::error(ACCESS);
		}

		// load model
		$api_account_model = new Api_account_Model($api_acount_id);
		
		// check exists
		if (!$api_account_model->id)
		{
			self::error(RECORD);
		}
		
		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('api_accounts/show_all', 'API accounts',
						$this->acl_check_view('Api_Controller', 'account'))
				->text($api_account_model->username);
		
		$headline = __('API account') . ' ' . $api_account_model->username;
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('api_accounts/show');
		$view->content->headline = $headline;
		$view->content->api_account = $api_account_model;
		$view->content->can_edit = $this->acl_check_edit(
				'Api_Controller', 'account'
		);
		$view->content->can_delete = $this->acl_check_delete(
				'Api_Controller', 'account'
		);
		$view->content->can_view_token = $this->acl_check_view(
				'Api_Controller', 'account_token'
		);
		$view->content->can_reset_token = $this->acl_check_edit(
				'Api_Controller', 'account_token'
		);
		$view->content->can_view_logs = $this->acl_check_view(
				'Api_Controller', 'account_log'
		);
		$view->render(TRUE);
	}

	/**
	 * Adds API account
	 */
	public function add()
	{
		// check access
		if (!$this->acl_check_new('Api_Controller', 'account'))
		{
			self::error(ACCESS);
		}

		// form
		$form = new Forge('api_accounts/add');

		$basic = $form->group('Basic information');
		
		$basic->input('username')
				->rules('required|length[3,50]')
				->callback(array($this, 'valid_unique_username'));
		
		$basic->radio('enabled')
				->options(arr::bool())
				->default(true);
		
		$basic->radio('readonly')
				->options(arr::bool())
				->default(true);

		$advanced = $form->group('Advanced information');
		
		$advanced->textarea('allowed_paths')
				->label(__('Allowed URL paths') . ': ' .
						help::hint('api_allowed_paths'))
				->rules('required')
				->callback(array($this, 'valid_paths'))
				->value(Api_account_Model::ALLOWED_PATHS_ENABLED_ALL);
		
		$form->submit('Add');

		// validate form and save data
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$api_account_model = new Api_account_Model();
				
			try
			{
				$api_account_model->transaction_start();
				
				// allowed paths change end lines to commmas
				$a_paths = str_replace("\n", ',', $form_data['allowed_paths']);
				
				// generate new random token
				$api_account_model->token = self::generate_token();
				
				// save account
				$api_account_model->username = $form_data['username'];
				$api_account_model->enabled = !!$form_data['enabled'];
				$api_account_model->readonly = !!$form_data['readonly'];
				$api_account_model->allowed_paths = $a_paths;
				$api_account_model->save_throwable();

				// log
				$api_account_model->create_user_log(
						Api_account_log_Model::TYPE_CREATION, $this->user_id
				)->save_throwable();
				
				$api_account_model->transaction_commit();

				// message
				status::success(__('%s has been successfully added.',
						__('API account')));
				
				// redirection
				$this->redirect('api_accounts/show_all');	
			}
			catch (Exception $e)
			{
				$api_account_model->transaction_rollback();
				Log::add_exception($e);
				status::error(__('Error - cannot add %s.', __('API account')), $e);
			}
		}
		
		$headline = __('Add new API account');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('api_accounts/show_all', 'API accounts',
						$this->acl_check_view('Api_Controller', 'account'))
				->disable_translation()
				->text($headline)
				->html();								

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->content = new View('form');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Edits API account
	 * 
	 * @param integer $api_account_id
	 */
	public function edit($api_account_id = NULL)
	{
		// check param
		if (!$api_account_id || !is_numeric($api_account_id))
		{
			self::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_new('Api_Controller', 'account'))
		{
			self::error(ACCESS);
		}

		// load model
		$api_account_model = new Api_account_Model($api_account_id);
		
		// check exists
		if (!$api_account_model->id)
		{
			self::error(RECORD);
		}
		
		// edit ID for validator
		$this->edit_id = $api_account_id;

		// form
		$form = new Forge('api_accounts/edit/' . $api_account_id);

		$basic = $form->group('Basic information');
		
		$basic->input('username')
				->rules('required|length[3,50]')
				->callback(array($this, 'valid_unique_username'))
				->value($api_account_model->username);
		
		$basic->radio('enabled')
				->options(arr::bool())
				->default($api_account_model->enabled);
		
		$basic->radio('readonly')
				->options(arr::bool())
				->default($api_account_model->readonly);

		$advanced = $form->group('Advanced information');
		
		$advanced->textarea('allowed_paths')
				->label(__('Allowed URL paths') . ': ' .
						help::hint('api_allowed_paths'))
				->rules('required')
				->callback(array($this, 'valid_paths'))
				->value(str_replace(',', "\n", $api_account_model->allowed_paths));
		
		$form->submit('Edit');

		// validate form and save data
		if ($form->validate())
		{
			$form_data = $form->as_array();
				
			try
			{
				$api_account_model->transaction_start();
				
				// allowed paths change end lines to commmas
				$form_data['allowed_paths'] = str_replace("\n", ',', 
						$form_data['allowed_paths']);
				// boolean conversion
				$form_data['enabled'] = !!$form_data['enabled'];
				$form_data['readonly'] = !!$form_data['readonly'];
				
				// log description from properties diff
				$log_props = array('username', 'enabled', 'readonly', 
					'allowed_paths');
				$log_descr = array();
				foreach ($log_props as $prop)
				{
					if ($api_account_model->{$prop} != $form_data[$prop])
					{
						$log_descr[] = $prop . '[' . $api_account_model->{$prop}
								. '->' . $form_data[$prop] . ']';
					}
				}
				
				// save account
				$api_account_model->username = $form_data['username'];
				$api_account_model->enabled = $form_data['enabled'];
				$api_account_model->readonly = $form_data['readonly'];
				$api_account_model->allowed_paths = $form_data['allowed_paths'];
				$api_account_model->save_throwable();

				// log
				$api_account_model->create_user_log(
						Api_account_log_Model::TYPE_DETAILS_CHANGE,
						$this->user_id, implode(', ', $log_descr)
				)->save_throwable();
				
				$api_account_model->transaction_commit();

				// message
				status::success(__('%s has been successfully edited.',
						__('API account')));
				
				// redirection
				$this->redirect('api_accounts/show_all');	
			}
			catch (Exception $e)
			{
				$api_account_model->transaction_rollback();
				Log::add_exception($e);
				status::error(__('Error - cannot edit %s.', 
						__('API account')), $e);
			}
		}
		
		$headline = __('Edit %s', __('API account'));
		
		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('api_accounts/show_all', 'API accounts',
						$this->acl_check_view('Api_Controller', 'account'))
				->link('api_accounts/show/' . $api_account_id,
						$api_account_model->username,
						$this->acl_check_view('Api_Controller', 'account'))
				->disable_translation()
				->text($headline)
				->html();								

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->content = new View('form');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Deletes API account with given ID.
	 *
	 * @param integer $api_account_id 
	 */
	public function delete($api_account_id = NULL)
	{
		// check param
		if (!$api_account_id || !is_numeric($api_account_id))
		{
			self::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_delete('Api_Controller', 'account'))
		{
			self::error(ACCESS);
		}

		// load model
		$api_account_model = new Api_account_Model($api_account_id);
		
		// check exists
		if (!$api_account_model->id)
		{
			self::error(RECORD);
		}
		
		// delete
		if ($api_account_model->delete())
		{
			status::success(__('%s has been successfully deleted.',
					__('API account')));
		}
		else
		{
			status::error(__('Error - cannot delete %s.'), __('API account'));
		}

		// redirect to show all
		$this->redirect('api_accounts/show_all');
	}

	/**
	 * Generate new token for API account with given ID.
	 *
	 * @param integer $api_account_id 
	 */
	public function token_reset($api_account_id = NULL)
	{
		// check param
		if (!$api_account_id || !is_numeric($api_account_id))
		{
			self::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_edit('Api_Controller', 'account_token'))
		{
			self::error(ACCESS);
		}

		// load model
		$api_account_model = new Api_account_Model($api_account_id);
		
		// check exists
		if (!$api_account_model->id)
		{
			self::error(RECORD);
		}

		try
		{
			$api_account_model->transaction_start();
			
			// change token
			$api_account_model->token = self::generate_token();
			$api_account_model->save_throwable();
			
			// log
			$api_account_model->create_user_log(
					Api_account_log_Model::TYPE_TOKEN_CHANGE, $this->user_id
			)->save_throwable();
			
			$api_account_model->transaction_commit();

			// message
			status::success(__('%s has been successfully edited.',
					__('API account')));
		}
		catch (Exception $e)
		{
			$api_account_model->transaction_rollback();
			Log::add_exception($e);
			status::error(__('Error - cannot edit %s.', __('API account')), $e);
		}

		// redirect to show all
		$this->redirect('api_accounts/show/' . $api_account_id);
	}
	
	/**
	 * Show logs of API account with given ID.
	 * 
	 * @param integer $api_account_id
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page
	 */
	public function show_logs($api_account_id = NULL,
			$limit_results = 50, $order_by = 'id',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{
		// check param
		if (!$api_account_id || !is_numeric($api_account_id))
		{
			self::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_view('Api_Controller', 'account_log'))
		{
			self::error(ACCESS);
		}

		// load model
		$aa_model = new Api_account_Model($api_account_id);
		$user_model = new User_Model();
		
		// check exists
		if (!$aa_model->id)
		{
			self::error(RECORD);
		}
		
		// filter form
		$filter_form = new Filter_form('l');
		
		$filter_form->add('id')
				->type('number');
		
		$filter_form->add('type')
				->type('select')
				->values(Api_account_log_Model::get_types());
		
		$filter_form->add('date')
				->type('date');
		
		$filter_form->add('responsible_user_id')
				->type('select')
				->values($user_model->select_list_grouped(FALSE))
				->label('User');
		
		$filter_form->add('description');
		
		// filter SQL
		$fsql = $filter_form->as_sql();
		$aal_model = new Api_account_log_Model();
		
		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
		{
			$limit_results = intval($this->input->post('record_per_page'));
		}
		
		// total count
		$total_count = $aal_model->count_account_logs($api_account_id, $fsql);	
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_count)
		{
			$sql_offset = 0;
		}
		
		// get data
		$data = $aal_model->get_account_logs(
				$api_account_id, $sql_offset, intval($limit_results), $order_by,
				$order_by_direction, $fsql
		);

		// grid
		$grid = new Grid('api_accounts_logs', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang') 
										 . '/api_accounts/show_logs/'
										 . $api_account_id.'/'.$limit_results
										 . '/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_count,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form,
			'variables'					=> $api_account_id.'/',
			'url_array_ofset'			=> 1
		));
		
		$grid->order_field('id');
		
		$grid->order_callback_field('type')
				->callback('callback::api_account_log_type');
		
		$grid->order_field('date');
		
		$grid->order_link_field('responsible_user_id')
				->link('users/show', 'responsible_user_name')
				->label('User');
		
		$grid->order_field('description');
		
		// load datasource
		$grid->datasource($data);
		
		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('api_accounts/show_all', 'API accounts',
						$this->acl_check_view('Api_Controller', 'account'))
				->link('api_accounts/show/' . $api_account_id,
						$aa_model->username,
						$this->acl_check_view('Api_Controller', 'account'))
				->text('Action logs');
		
		// main view
		$headline = __('List of API account logs');
		$view = new View('main');
		$view->title = $headline;
		$view->content = new View('show_all');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/* VALIDATORS */

	/**
	 * Callback function validator for URL template paths separated by newline
	 * characters.
	 * 
	 * @param object $input
	 */
	public function valid_paths($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$value = $input->value;

		if (!empty($value))
		{
			$url_tpaths = explode("\n", $value);
			if (!url_tpath::is_group_valid($url_tpaths))
			{
				$input->add_error('required', __(
						'Invalid URL paths see hint.'
				));
			}
		}
	}
	
	/**
	 * Callback function validator for unique username.
	 * 
	 * @param object $input
	 */
	public function valid_unique_username($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$value = $input->value;
		$model = new Api_account_Model();
		$ignore_id = $this->edit_id;
		
		if (!empty($value) && !$model->is_account_unique($value, $ignore_id))
		{
			$input->add_error('required', __(
					'Username already exists in database.'
			));
		}
	}
	
	/* INTERNAL FUNCTIONS */
	
	/**
	 * Generates a new API account token.
	 * 
	 * @return string 32-character long token
	 */
	private static function generate_token()
	{
		return security::generate_password(32);
	}

}
