<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is release under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Handles bank accounts of members.
 *
 * @package Controller
 */
class Bank_accounts_Controller extends Controller
{
	/**
	 * Constructor, only test if finance is enabled
	 */
	public function __construct()
	{		
		parent::__construct();
		
		if (!Settings::get('finance_enabled'))
			Controller::error (ACCESS);
	}
	
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('bank_accounts/show_all');
	}
	
	/**
	 * It shows bank accounts of association.
	 * 
	 * @author Jiri Svitak
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 */
	public function show_all(
			$limit_results = 500, $order_by = 'id', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// access
		if (!$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
		{
			Controller::error(ACCESS);
		}
		
		// it gets only bank accounts of association
		$bank_account_model = new Bank_account_Model();
		$baa = $bank_account_model->get_assoc_bank_accounts();
		
		$baa_headline = __('Bank accounts of association').'&nbsp;'
				. help::hint('bank_accounts_of_association');
		
		$baa_grid = new Grid('bank_accounts', null, array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false
		));
		
		// adding bank account
		if ($this->acl_check_new('Accounts_Controller', 'bank_accounts'))
		{
			$baa_grid->add_new_button(
					'bank_accounts/add/1', 'Add new bank account of association'
			);
		}
		
		$baa_grid->field('id')
				->label('ID');
		
		$baa_grid->field('baname')
				->label('Account name');
		
		$baa_grid->callback_field('type')
				->callback('callback::bank_account_type');
		
		$baa_grid->field('account_number');
		
		$baa_grid->field('mname')
				->label('Member name');
		
		$actions = $baa_grid->grouped_action_field();
		
		if ($this->acl_check_view('Accounts_Controller', 'bank_transfers'))
		{
			$actions->add_action('id')
					->icon_action('transfer')
					->url('bank_transfers/show_by_bank_account')
					->label('Show transfers');
		}
		
		if ($this->acl_check_view('Accounts_Controller', 'bank_statements'))
		{
			$actions->add_action('id')
					->icon_action('dumps')
					->url('bank_statements/show_by_bank_account')
					->label('Show statements');
		}
		
		if ($this->acl_check_new('Accounts_Controller', 'bank_transfers'))
		{
			$actions->add_conditional_action('id')
					->condition('is_import_of_statement_available')
					->icon_action('import')
					->url('import/upload_bank_file')
					->label('Import');
		}
		
		if ($this->acl_check_view('Accounts_Controller', 'bank_account_auto_down_config'))
		{
			$actions->add_conditional_action('id')
					->condition('is_automatical_down_of_statement_available')
					->icon_action('settings_auto')
					->url('bank_accounts_auto_down_settings/show')
					->label('Setup automatical downloading of statements');
		}	
		
		if ($this->acl_check_edit('Accounts_Controller', 'bank_accounts'))
		{
			$actions->add_conditional_action('id')
					->icon_action('edit')
					->url('bank_accounts/edit');
		}
			
		$baa_grid->datasource($baa);		

		// bank accounts except association's
		if ($this->acl_check_view('Accounts_Controller', 'bank_accounts'))
		{
			// get new selector
			if (is_numeric($this->input->post('record_per_page')))
			{
				$limit_results = (int) $this->input->post('record_per_page');
			}
			
			// parameters control
			$allowed_order_type = array('id', 'baname', 'account_number', 'mname');
			
			if (!in_array(strtolower($order_by),$allowed_order_type))
			{
				$order_by = 'id';
			}
			
			if (strtolower($order_by_direction) != 'desc')
			{
				$order_by_direction = 'asc';
			}
			
			// Create filter form
			$filter_form = new Filter_form();

			$filter_form->add('name')
					->label('Account name')
					->table('ba')
					->callback('json/bank_account_name');

			$filter_form->add('account_nr')
					->label('Account number');

			$filter_form->add('bank_nr')
					->label('Bank code');

			// bank accounts			
			$total_baccounts = $bank_account_model->count_bank_accounts($filter_form->as_sql());
			
			if (($sql_offset = ($page - 1) * $limit_results) > $total_baccounts)
				$sql_offset = 0;
			
			$ba = $bank_account_model->get_bank_accounts(
					$sql_offset, (int)$limit_results, $order_by,
					$order_by_direction, $filter_form->as_sql()
			);
			
			$title = __('Bank accounts').'&nbsp;'.help::hint('bank_accounts');
			
			$grid = new Grid('bank_accounts', $title, array
			(
				'separator'		   			=> '<br /><br />',
				'current'	    			=> $limit_results,
				'selector_increace'    		=> 500,
				'selector_min' 				=> 500,
				'selector_max_multiplier'   => 10,
				'base_url'    				=> Config::get('lang').'/bank_accounts/show_all/'
											. $limit_results.'/'.$order_by.'/'.$order_by_direction,
				'uri_segment'    			=> 'page',
				'total_items'    			=> $total_baccounts,
				'items_per_page' 			=> $limit_results,
				'style'          			=> 'classic',
				'order_by'					=> $order_by,
				'order_by_direction'		=> $order_by_direction,
				'limit_results'				=> $limit_results,
				'filter'					=> $filter_form,
			));
			
			// adding bank account
			if ($this->acl_check_new('Accounts_Controller', 'bank_accounts'))
			{
				$grid->add_new_button('bank_accounts/add', 'Add new bank account');
			}
			
			$grid->order_field('id')
					->label('ID');
			
			$grid->order_field('baname')
					->label('Account name');
			
			$grid->order_field('account_nr')
					->label('Account number');
			
			$grid->order_field('bank_nr')
					->label('Bank code');
			
			$grid->order_link_field('member_id')
					->link('members/show', 'member_name')
					->label('Member name');
			
			$actions = $grid->grouped_action_field();
		
			if ($this->acl_check_view('Accounts_Controller', 'bank_transfers'))
			{
				$actions->add_action('id')
						->icon_action('transfer')
						->url('bank_transfers/show_by_bank_account')
						->label('Show transfers');
			}
			
			$grid->datasource($ba);
		}
		else
		{
			$grid = '';
		}
		
		// breadcrubs
		$breadcrumbs = breadcrumbs::add()
				->link('members/show/1', 'Profile of association',
						$this->acl_check_view('Members_Controller', 'members'))
				->text('Bank accounts')
				->html();
		
		// view
		$view = new View('main');
		$view->title = __('Bank accounts');
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('bank_accounts/show_all');
		$view->content->baa_headline = $baa_headline;
		$view->content->baa_grid = $baa_grid;
		$view->content->grid = $grid;
		$view->render(TRUE);		
	}	
	
	/**
	 * Function adds bank account.
	 * If member id 1 is specified, then it is new bank account of association.
	 */
	public function add($member_id = NULL)
	{
		// access
		if (!$this->acl_check_new('Accounts_Controller', 'bank_accounts'))
		{
			Controller::error(ACCESS);
		}
		
		// form
		if (!isset($member_id) || $member_id != Member_Model::ASSOCIATION)
		{
			// members list
			$arr_members = ORM::factory('member')->select_list();
		
			if (isset($arr_members[1]))
			{
				unset($arr_members[1]);
			}
			
			$form = new Forge('bank_accounts/add/');
			
			$form->dropdown('member_id')
					->label('Member name')
					->options($arr_members)
					->selected($this->session->get('member_id'))
					->rules('required')
					->style('width:200px');
		}
		else
		{
			$form = new Forge('bank_accounts/add/' . $member_id);
		
			$form->dropdown('type')
					->options(Bank_account_Model::get_type_names())
					->style('width:200px');		
		}
		
		$form->input('account_name')
				->rules('required|length[3,50]');
		
		$form->input('account_nr')
				->label('Account number')
				->rules('required|length[3,50]|valid_numeric');
		
		$form->input('bank_nr')
				->label('Bank code')
				->rules('required|length[3,10]|valid_numeric');
		
		$form->input('IBAN');
		
		$form->input('SWIFT');
		
		// submit button
		$form->submit('Add');
		// validation
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			// determining owner's id
			if (!isset($member_id) || $member_id != 1)
			{
				$member_id = $form_data["member_id"];
			}
			
			// determinig type
			if ($member_id == Member_Model::ASSOCIATION)
			{
				$type = $form_data["type"];
			}
			else
			{
				$type = Bank_account_Model::TYPE_OTHER;
			}
			
			// real bank account
			$bank_account = new Bank_account_Model();
			$bank_account->name = $form_data['account_name'];
			$bank_account->member_id = $member_id;
			$bank_account->type = $type;
			$bank_account->account_nr = $form_data['account_nr'];
			$bank_account->bank_nr = $form_data['bank_nr'];
			$bank_account->IBAN = $form_data['IBAN'];
			$bank_account->SWIFT = $form_data['SWIFT'];
			$bank_account->save();
			// only member 1 - association itself - has related double-entry accounts to added bank account
			if ($member_id == Member_Model::ASSOCIATION)
			{			
				// these three double-entry accounts are related to one bank account through relation table
				// double-entry bank account
				$doubleentry_bank_account = new Account_Model();
				$doubleentry_bank_account->member_id = $member_id;
				$doubleentry_bank_account->name = $form_data['account_name'];
				$doubleentry_bank_account->account_attribute_id = Account_attribute_Model::BANK;
				$doubleentry_bank_account->comment = __('Bank accounts');
				$doubleentry_bank_account->add($bank_account);
				$doubleentry_bank_account->save();
				// double-entry account of bank fees
				$bank_fees_account = new Account_Model();
				$bank_fees_account->member_id = $member_id;
				$bank_fees_account->name = $form_data['account_name'].' - '.__('Bank fees');
				$bank_fees_account->account_attribute_id = Account_attribute_Model::BANK_FEES;
				$bank_fees_account->comment = __('Bank fees');
				$bank_fees_account->add($bank_account);
				$bank_fees_account->save();	
				// double-entry account of bank interests
				$bank_interests_account = new Account_Model();
				$bank_interests_account->member_id = $member_id;
				$bank_interests_account->name = $form_data['account_name'].' - '.__('Bank interests');
				$bank_interests_account->account_attribute_id = Account_attribute_Model::BANK_INTERESTS;
				$bank_interests_account->comment = __('Bank interests');
				$bank_interests_account->add($bank_account);
				$bank_interests_account->save();
			}
			// redirection
			url::redirect('bank_accounts/show_all');
		}
		
		if ($member_id == 1)
			$headline = __('Add new bank account of association');
		else
			$headline = __('Add new bank account');
		
		// breadcrubs
		$breadcrumbs = breadcrumbs::add()
				->link('members/show/1', 'Profile of association',
						$this->acl_check_view('Members_Controller', 'members'))
				->link('bank_accounts/show_all', 'Bank accounts')
				->disable_translation()
				->text($headline)
				->html();
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);	
	}
	
	/**
	 * Enables to edit bank account of association.
	 * 
	 * @param integer $bank_account_id
	 */
	public function edit($bank_account_id = NULL)
	{
		// param
		if (!intval($bank_account_id))
		{
			self::warning(PARAMETER);
		}
		
		// access
		if (!$this->acl_check_edit('Accounts_Controller', 'bank_accounts'))
		{
			self::error(ACCESS);
		}
		
		$bank_account = new Bank_account_Model($bank_account_id);
		
		// exists?
		if (!$bank_account || !$bank_account->id ||
			$bank_account->member_id != Member_Model::ASSOCIATION)
		{
			self::error(RECORD);
		}
		
		try
		{
			$ba_driver = Bank_Account_Settings::factory($bank_account->type);
			$ba_driver->load_column_data($bank_account->settings);
		}
		catch (InvalidArgumentException $e)
		{
			$ba_driver = NULL;
		}
		
		// form
		$form = new Forge();
		
		$form->group('Basic information');

		$form->dropdown('type')
				->options(Bank_account_Model::get_type_names())
				->selected($bank_account->type)
				->style('width:200px');
		
		$form->input('IBAN')
				->value($bank_account->IBAN);
		
		$form->input('SWIFT')
				->value($bank_account->SWIFT);
		
		// bank account settings
		if ($ba_driver && count($ba_driver->get_column_fields()))
		{
			$form->group('Settings');
			
			$columns = $ba_driver->get_column_fields();
			
			foreach ($columns as $column => $info)
			{
				switch ($info['type'])
				{
					case Bank_Account_Settings::FIELD_TYPE_BOOL:
						$input = $form->checkbox($column)->checked($ba_driver->$column);
						break;
					
					default:
						$input = $form->input($column)->value($ba_driver->$column);
						break;
				}
				
				if (isset($info['name']) && !empty($info['name']))
					$input->label($info['name']);
				
				if (isset($info['help']) && !empty($info['help']))
					$input->help(help::hint($info['help']));
				
				if (isset($info['rules']) && !empty($info['rules']))
					$input->rules($info['rules']);
			}
		}
		
		// submit button
		$form->submit('Edit');
		
		// validation
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			// real bank account
			$bank_account->type = $form_data['type'];
			$bank_account->IBAN = $form_data['IBAN'];
			$bank_account->SWIFT = $form_data['SWIFT'];
			
			if ($ba_driver && count($ba_driver->get_column_fields()))
			{
				unset($form_data['type']);
				unset($form_data['IBAN']);
				unset($form_data['SWIFT']);

				foreach ($form_data as $key => $value)
				{
					$ba_driver->$key = $value;
				}

				$bank_account->settings = $ba_driver->get_column_data();
			}
			
			$bank_account->save();
			
			// redirection
			url::redirect('bank_accounts/show_all');
		}
		
		$headline = __('Edit bank account');
		
		// breadcrubs
		$breadcrumbs = breadcrumbs::add()
				->link('members/show/1', 'Profile of association',
						$this->acl_check_view('Members_Controller', 'members'))
				->link('bank_accounts/show_all', 'Bank accounts')
				->disable_translation()
				->text($bank_account->account_nr . '/' . $bank_account->bank_nr)
				->text($headline)
				->html();
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);	
	}
	
}
