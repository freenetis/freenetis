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
					'bank_accounts/add/1', __('Add new bank account of association')
			);
		}

		/*if ($this->acl_check_new('Accounts_controller', 'bank_transfers'))
		{
			$baa_grid->add_new_button(
					'bank_accounts/fio_settings', __('Fio settings')
			);
		}*/
		
		$baa_grid->field('id')
				->label(__('ID'));
		
		$baa_grid->field('baname')
				->label(__('Account name'));
		
		$baa_grid->field('account_number');
		
		$baa_grid->field('mname')
				->label(__('Member name'));
		
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
			$actions->add_action('id')
					->icon_action('import')
					->url('import/upload_bank_file')
					->label('Import');
		}
			
		$baa_grid->datasource($baa);		

		// bank accounts except association's
		if ($this->acl_check_view('Accounts_Controller', 'bank_accounts'))
		{
			// get new selector
			if (is_numeric($this->input->get('record_per_page')))
			{
				$limit_results = (int) $this->input->get('record_per_page');
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
			
			// creates fields for filtering unidentified transfers
			$filter = new Table_Form(
					url_lang::base() . 'bank_accounts/show_all', 'get', array
			(
					new Table_Form_Item('text', 'name', 'Account name'),
					new Table_Form_Item('text', 'account_nr', 'Account number'),
					"tr",
					new Table_Form_Item('text', 'bank_nr', 'Bank code'),
					"td", new Table_Form_Item('submit', 'submit', 'Filter')
			));
			
			$arr_gets = array();
			foreach ($this->input->get() as $key=>$value)
				$arr_gets[] = $key.'='.$value;
			$query_string = '?'.implode('&',$arr_gets);
			
			// bank accounts			
			$total_baccounts = $bank_account_model->count_bank_accounts($filter->values());
			
			if (($sql_offset = ($page - 1) * $limit_results) > $total_baccounts)
				$sql_offset = 0;
			
			$ba = $bank_account_model->get_bank_accounts(
					$sql_offset, (int)$limit_results, $order_by,
					$order_by_direction, $filter->values()
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
				'filter'					=> $filter->view,
				'query_string'				=> $query_string
			));
			
			// adding bank account
			if ($this->acl_check_new('Accounts_Controller', 'bank_accounts'))
			{
				$grid->add_new_button('bank_accounts/add', __('Add new bank account'));
			}
			
			$grid->order_field('id')
					->label('ID');
			
			$grid->order_field('baname')
					->label(__('Account name'));
			
			$grid->order_field('account_nr')
					->label(__('Account number'));
			
			$grid->order_field('bank_nr')
					->label(__('Bank code'));
			
			$grid->order_link_field('member_id')
					->link('members/show', 'member_name')
					->label(__('Member name'));
			
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
	public function add($member_id = null)
	{
		// access
		if (!$this->acl_check_new('Accounts_Controller', 'bank_accounts'))
		{
			Controller::error(ACCESS);
		}
		
		// form
		if (!isset($member_id) || $member_id != 1)
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
			$form = new Forge("bank_accounts/add/$member_id");		
		}
		
		$form->input('account_name')
				->rules('required|length[3,50]');
		
		$form->input('account_comment')
				->label('Comment');
		
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
			
			// real bank account
			$bank_account = new Bank_account_Model();
			$bank_account->name = $form_data["account_name"];
			$bank_account->member_id = $member_id;				
			$bank_account->account_nr = $form_data["account_nr"];
			$bank_account->bank_nr = $form_data["bank_nr"];
			$bank_account->IBAN = $form_data["IBAN"];
			$bank_account->SWIFT = $form_data["SWIFT"];
			$bank_account->save();
			// only member 1 - association itself - has related double-entry accounts to added bank account
			if ($member_id == 1)
			{			
				// these three double-entry accounts are related to one bank account through relation table
				// double-entry bank account
				$doubleentry_bank_account = new Account_Model();
				$doubleentry_bank_account->member_id = $member_id;
				$doubleentry_bank_account->name = $form_data["account_name"];
				$doubleentry_bank_account->account_attribute_id = Account_attribute_Model::BANK;
				$doubleentry_bank_account->comment = __('Bank accounts');
				$doubleentry_bank_account->add($bank_account);
				$doubleentry_bank_account->save();
				// double-entry account of bank fees
				$bank_fees_account = new Account_Model();
				$bank_fees_account->member_id = $member_id;
				$bank_fees_account->name = $form_data["account_name"].' - '.__('Bank fees');
				$bank_fees_account->account_attribute_id = Account_attribute_Model::BANK_FEES;
				$bank_fees_account->comment = __('Bank fees');
				$bank_fees_account->add($bank_account);
				$bank_fees_account->save();	
				// double-entry account of bank interests
				$bank_interests_account = new Account_Model();
				$bank_interests_account->member_id = $member_id;
				$bank_interests_account->name = $form_data["account_name"].' - '.__('Bank interests');
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
	 * Settings for daily Fio imports.
	 * @author Jiri Svitak
	 */
	/*public fun ction fio_settings()
	{
		// access control
		if (!$this->acl_check_new('Accounts_Controller', 'bank_transfers'))
			Controller::error(ACCESS);

		$arr_bool = array
		(
			'1' => __('Yes'),
			'0' => __('No')
		);

		// creating of new forge
		$this->form = new Forge('bank_accounts/fio_settings');

		$this->form->group('General settings');

		$this->form->radio('fio_import_daily')
				->label(__('Enable automatic Fio import').":&nbsp;".
						help::hint('fio_import_daily'))
				->options($arr_bool)
				->default(Settings::get('fio_import_daily'));

		$this->form->input('fio_user')
				->label(__('User') . ':')
				->value(Settings::get('fio_user'));

		$this->form->input('fio_password')
				->label(__('Password') . ':&nbsp;'.
						help::hint('fio_password'))
				->value(Settings::get('fio_password'));

		$this->form->input('fio_account_number')
				->label(__('Account number') . ':&nbsp;'.
						help::hint('fio_account_number'))
				->value(Settings::get('fio_account_number'));

		$this->form->input('fio_view_name')
				->label(__('View name') . ':&nbsp;'.
						help::hint('fio_view_name'))
				->value(Settings::get('fio_account_number'));

		$this->form->submit('submit')->value(__('Save'));

		special::required_forge_style($this->form, ' *', 'required');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array(FALSE);
			$issaved = true;

			foreach ($form_data as $name => $value)
			{
				$issaved = $issaved && Settings::set($name, $value);
			}

			if ($issaved)
			{	// if all action were succesfull
				status::success('System variables have been successfully updated.');
			}
			else
			{	// if not
				status::error('System variables havent been successfully updated.');
			}

			url::redirect('bank_accounts/fio_settings');
		}
		// create view for this template
		$view = new View('main');
		$view->title = __('Fio settings');
		$view->breadcrumbs = __('Fio settings');
		$view->content = new View('form');
		$view->content->form = $this->form->html();
		$view->content->headline = __('Fio settings');
		$view->render(TRUE);
	}*/
}
