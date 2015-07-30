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
 * Manages all double-entry accounts in the system including accounting system.
 * 
 * @author Jiri Svitak
 * @package	Controller
 */
class Accounts_Controller extends Controller
{

	/**
	 * Index redirect to show all
	 */
	public function index()
	{
		url::redirect('accounts/show_all/1');
	}

	/**
	 * It shows all double-entry accounts.
	 * They are shown by selected group in filter.
	 * 
	 * @author Jiri Svitak
	 * @param $limit_results
	 * @param $order_by
	 * @param $order_by_direction
	 */
	public function show_all(
			$group = 1, $limit_results = 500, $order_by = 'id',
			$order_by_direction = 'asc', $page_word = null, $page = 1)
	{
		// access check
		if (!$this->acl_check_view('Accounts_Controller', 'accounts'))
		{
			Controller::error(ACCESS);
		}
		
		// account groups
		$arr_groups[Account_Model::ACCOUNTING_SYSTEM] = __('Accounting system');
		$arr_groups[Account_Model::CREDIT] = __('Credit subaccounts');
		$arr_groups[Account_Model::PROJECT] = __('Project subaccounts');
		$arr_groups[Account_Model::OTHER] = __('Other');
		
		// account groups with help
		$arr_groups_help[Account_Model::ACCOUNTING_SYSTEM] = help::hint('accounting_system');
		$arr_groups_help[Account_Model::CREDIT] = help::hint('credit_subaccounts');
		$arr_groups_help[Account_Model::PROJECT] = help::hint('project_subaccounts');
		$arr_groups_help[Account_Model::OTHER] = help::hint('other_subaccounts');
		
		// filtering
		$filter = new Table_Form(url_lang::base() . "accounts/show_all/$group", "get", array
		(
				new Table_Form_Item('text', 'name', 'Account name'),
				"tr",
				new Table_Form_Item('text', 'datetime_from', 'Balance from date'),
				"tr",
				new Table_Form_Item('text', 'datetime_to', 'Balance to date'),
				"tr",
				"td", new Table_Form_Item('submit', 'submit', 'Filter'),
		));
		
		$filter_values = $filter->values();
		
		// gets grid settings
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		// order by check
		$allowed_order_type = array('id', 'aname', 'comment', 'mname', 'balance');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		// gets records
		if ($group == Account_Model::ACCOUNTING_SYSTEM)
		{
			$account_attribute_model = new Account_attribute_Model();
			$total_accounts = $account_attribute_model->get_accounting_system_count($filter_values);
			
			if (($sql_offset = ($page - 1) * $limit_results) > $total_accounts)
				$sql_offset = 0;
			
			$accounts = $account_attribute_model->get_accounting_system(
					$sql_offset, (int) $limit_results, $order_by,
					$order_by_direction, $filter_values
			);
		}
		else
		{
			$account_model = new Account_Model();
			$total_accounts = $account_model->get_accounts_count($filter_values, $group);
			
			if (($sql_offset = ($page - 1) * $limit_results) > $total_accounts)
				$sql_offset = 0;
			
			$accounts = $account_model->get_accounts(
					$sql_offset, (int) $limit_results, $order_by,
					$order_by_direction, $filter_values, $group
			);
		}

		// creates parameters of filter in url
		$arr_gets = array();
		foreach ($this->input->get() as $key => $value)
		{
			$arr_gets[] = $key . '=' . $value;
		}
		$query_string = '?' . implode('&', $arr_gets);

		// set correct headline for chosen group
		$headline = $arr_groups[$group];

		// grid
		$grid = new Grid('accounts', null, array
		(
				'current'					=> $limit_results,
				'selector_increace'			=> 500,
				'selector_min'				=> 500,
				'selector_max_multiplier'	=> 10,
				'base_url'					=> Config::get('lang') . '/accounts/show_all/' . $group . '/'
											. $limit_results . '/' . $order_by . '/' . $order_by_direction,
				'uri_segment'				=> 'page',
				'total_items'				=> $total_accounts,
				'items_per_page'			=> $limit_results,
				'style'						=> 'classic',
				'order_by'					=> $order_by,
				'order_by_direction'		=> $order_by_direction,
				'limit_results'				=> $limit_results,
				'query_string'				=> $query_string,
				'filter'					=> $filter->view,
				'variables'					=> $group . '/',
				'url_array_ofset'			=> 1
		));
		
		foreach ($arr_groups as $key => $arr_group)
		{
			$grid->add_new_button(
					'accounts/show_all/' . $key,
					$arr_group, array(), $arr_groups_help[$key]
			);
		}
		
		if ($group == Account_Model::ACCOUNTING_SYSTEM)
		{
			// button for recalculating balances of all accounts
			if ($this->acl_check_edit('Accounts_Controller', 'accounts'))
			{
				$grid->add_new_button(
						'accounts/recalculate_account_balances',
						__('Recalculate account balances'),
						array(), help::hint('recalculate_account_balances')
				);
			}
			
			$grid->order_field('id')
					->label(__('Account'));
			
			$grid->order_field('name')
					->label(__('Account name'));
			
			$grid->order_callback_field('balance')
					->callback('callback::balance_field');
		}
		else
		{
			// adding project account
			if ($group == Account_Model::PROJECT &&
				$this->acl_check_new('Accounts_Controller', 'accounts'))
			{
				$grid->add_new_button(
						'accounts/add_project',
						__('Add new project account')
				);
			}
			
			$grid->order_field('id');
			
			$grid->order_field('name')
					->label(__('Account name'));
			
			$grid->order_field('account_attribute_id')
					->label(__('Type'));
			
			$grid->order_callback_field('balance')
					->callback('callback::balance_field');
			
			$grid->order_callback_field('member_name')
					->callback('callback::member_field');
			
			$actions = $grid->grouped_action_field();
			
			if ($this->acl_check_view('Accounts_Controller', 'transfers'))
			{
				$actions->add_action('id')
						->icon_action('transfer')
						->url('transfers/show_by_account')
						->label('Show transfers');
			}
			
			if ($this->acl_check_edit('Accounts_Controller', 'accounts'))
			{
				$actions->add_action('id')
						->icon_action('edit')
						->url('accounts/edit')
						->label('Edit account');
			}
		}
		
		// load data
		$grid->datasource($accounts);
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add(false)
				->text($headline)
				->html();
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('show_all');
		$view->content->headline = $headline . '&nbsp;' . $arr_groups_help[$group];
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Adds new project account.
	 * 
	 * @author Jiri Svitak
	 * @param integer $member_id
	 */
	public function add_project()
	{
		// access rights
		if (!$this->acl_check_new('Accounts_Controller', 'accounts'))
		{
			Controller::error(ACCESS);
		}
		
		// members list
		$arr_members = ORM::factory('member')
				->select_list('id', "CONCAT(id, ' - ', COALESCE(name,''))", 'name');
		
		// form
		$form = new Forge('accounts/add_project');
		
		$form->group('Basic information');
		
		$form->dropdown('member')
				->label(__('Owner') . ':')
				->rules('required')
				->options($arr_members)
				->style('width:200px');
		
		$form->input('name')
				->label(__('Account name') . ':')
				->rules('required|length[3,50]');
		
		$form->textarea('comment')
				->rules('length[0,250]');
		
		$form->submit('Add');
		
		// posted form
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$account = new Account_Model;
			$account->member_id = $form_data['member'];
			$account->account_attribute_id = Account_attribute_Model::PROJECT;
			$account->name = $form_data['name'];
			$account->comment = $form_data['comment'];
			
			unset($form_data);
			
			if ($account->save())
			{
				status::success('Account has been successfully added.');
			}
			else
			{
				status::error('Error - cant add new account.');
			}
			
			url::redirect('accounts/show_all?name=&group=2&submit=Filter');
		}
		else
		{
			// headline
			$headline = __('Add new project account');
			// bread crumbs
			$breadcrumbs = breadcrumbs::add()
					->link('accounts/show_all/3', 'Project subaccounts',
							$this->acl_check_view('Accounts_Controller', 'accounts'))
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
	} // end of add_credit_account function

	/**
	 * Function edits double-entry account.
	 * 
	 * @param integer $acc_id
	 */
	public function edit($acc_id = NULL)
	{
		// access rights
		if (!$this->acl_check_edit('Accounts_Controller', 'accounts'))
			Controller::error(ACCESS);
		
		if (!isset($acc_id) || !is_numeric($acc_id))
			Controller::warning(PARAMETER);
		
		$model_account = new Account_Model($acc_id);
		
		if (!$model_account->id)
			Controller::error(RECORD);
		
		$form = new Forge('accounts/edit/' . $acc_id);
		
		$form->group('Basic information');
		
		$form->input('name')
				->rules('required|length[3,50]')
				->value($model_account->name)
				->style('width:600px');
		
		$form->textarea('comment')
				->rules('length[0,250]')
				->value($model_account->comment)
				->style('width:600px');
		
		$form->submit('Edit');
		
		// form posted
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$model_account->name = $form_data['name'];
			$model_account->comment = $form_data['comment'];
			
			unset($form_data);
			
			if ($model_account->save())
			{
				status::success('Account has been successfully updated.');
			}
			else
			{
				status::error('Error - cant update account.');
			}
			
			url::redirect("accounts/show_all/1");
		}
		else
		{
			// bread crumbs
			$breadcrumbs = breadcrumbs::add()
					->link('accounts/show_all', 'Project subaccounts',
							$this->acl_check_view('Accounts_Controller', 'accounts'))
					->disable_translation()
					->text($model_account->name . ' (' . $model_account->id . ')')
					->html();
			
			// headline
			$headline = __('Edit account');
			
			// view
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs;
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	} // end of edit function

	/**
	 * Goes through all double-entry accounts and calculates their balance from their transfers.
	 * All transfers are primary information about cash flow. Calculating balance of account
	 * is creating redundant information, but it speeds up all money calculating operations in system.
	 * This method should be used only in special cases, like changing version of Freenetis
	 * to version containing this method, or when some data are corrupted.
	 * The user is familiar with result, when no change to balance is made, then everything is ok.
	 * In other case user is informed about count of accounts, which transfers are not corresponding
	 * to its balance
	 * 
	 * @author Jiri Svitak
	 */
	public function recalculate_account_balances()
	{
		if (!$this->acl_check_edit('Accounts_Controller', 'accounts'))
		{
			Controller::error(ACCESS);
		}
		
		// get all accounts with their own and calculated balances
		$account_model = new Account_Model();
		// recalculates balances and returns array of ids of incorrect accounts
		$incorrect_accounts = $account_model->recalculate_account_balances();
		
		// message		
		status::success(
				'All accounts now have correct balances, %d accounts had ' .
				'incorrect balances, list of IDs of corrected accounts: %s',
				TRUE, array
				(
					0 => count($incorrect_accounts),
					1 => implode(", ", $incorrect_accounts)
				)
		);
		
		// redirection
		url::redirect('accounts/show_all');
	}

}
