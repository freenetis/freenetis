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
	 * Constructor, only test if finance is enabled
	 */
	public function __construct()
	{		
		parent::__construct();
		
		if (!Settings::get('finance_enabled'))
			Controller::error (ACCESS);
	}
	
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
		
		// Create filter form
		$filter_form = new Filter_form();
		
		$filter_form->add('id')
				->table('aa')
				->label('Account');
		
		$filter_form->add('name')
				->table('aa')
				->label('Account name');
		
		$filter_form->add('datetime')
				->label('Balance date')
				->type('date');
		
		// gets grid settings
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
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
			$total_accounts = $account_attribute_model->get_accounting_system_count($filter_form->as_sql(array
					(
						'id', 'name'
					)));
			
			if (($sql_offset = ($page - 1) * $limit_results) > $total_accounts)
				$sql_offset = 0;
			
			$accounts = $account_attribute_model->get_accounting_system(
					$sql_offset, (int) $limit_results, $order_by,
					$order_by_direction, $filter_form->as_sql(array(
						'id', 'name'
					)),
					$filter_form->as_sql(array('datetime'))
			);
		}
		else
		{
			$account_model = new Account_Model();
			$total_accounts = $account_model->get_accounts_count($filter_form->as_sql(array
					(
						'id', 'name'
					)),
					$group);
			
			if (($sql_offset = ($page - 1) * $limit_results) > $total_accounts)
				$sql_offset = 0;
			
			$accounts = $account_model->get_accounts(
					$sql_offset, (int) $limit_results, $order_by,
					$order_by_direction, $filter_form->as_sql(array(
						'id', 'name'
					)),
					$filter_form->as_sql(array('datetime')),
					$group
			);
		}

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
				'filter'					=> $filter_form,
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
	
	/**
	 * Recalculates all fees of account
	 * 
	 * @author Michal Kliment
	 * @param type $account_id
	 */
	public function recalculate_fees ($account_id = NULL)
	{
		// bad parameter
		if (!$account_id)
			Controller::warning (PARAMETER);
		
		$account = new Account_Model($account_id);
		
		// account doesn't exist
		if (!$account->id)
			Controller::error (RECORD);
		
		// acccess control
		if (!$this->acl_check_edit('Accounts_Controller', 'accounts'))
			Controller::error (ACCESS);
		
		try
		{
			// recalculates entrance fees
			$entrance_fee_stats	= self::recalculate_entrance_fees($account->id);
			
			// recalculates member fees
			$member_fee_stats	= self::recalculate_member_fees($account->id);
			
			// recalculates device fees
			$device_fee_stats	= self::recalculate_device_fees($account->id);

			$deleted_transfers_count = $entrance_fee_stats['deleted'] + $member_fee_stats['deleted'] + $device_fee_stats['deleted'];
			$created_transfers_count = $entrance_fee_stats['created'] + $member_fee_stats['created'] + $device_fee_stats['created'];

			status::success(
				'Fees have been successfully recalculated, %d deleted '.
				'transfers, %d created new transfers.',
				TRUE, array
				(
					0 => $deleted_transfers_count,
					1 => $created_transfers_count
				)
			);
		}
		catch (Exception $e)
		{
			status::error('Error - Cannot recalculate fees', $e);
			Log::add_exception($e);
		}
		
		$this->redirect('transfers/show_by_account/'.$account->id);
	}
	
	/**
	 * Recalculates entrance fees of account
	 * 
	 * @author Michal Kliment
	 * @param type $account_id
	 * @return type
	 * @throws Exception
	 */
	public static function recalculate_entrance_fees ($account_id = NULL)
	{
		$account = new Account_Model($account_id);
		
		try
		{
			$account->transaction_start();
			
			$transfer_model = new Transfer_Model();

			$last_entrance_fee_transfer = substr($transfer_model->find_last_transfer_datetime_by_type(
				Transfer_Model::DEDUCT_ENTRANCE_FEE
			), 0,10);

			// first of all is necessary to delete previous entrance fee transfers
			// user can change debt payment rate, this means that existing transfers are useless
			$deleted_transfers_count = $account->delete_entrance_deduct_transfers_of_account($account->id);

			// preparation
			$created_transfers_count = 0;

			// not recalculate fees for applicant
			if ($account->member->type != Member_Model::TYPE_APPLICANT)
			{			
				$creation_datetime = date('Y-m-d H:i:s');
				$infrastructure = ORM::factory('account')->where(
						'account_attribute_id', Account_attribute_Model::INFRASTRUCTURE
				)->find();

				// debt payment rate is set
				if ($account->member->debt_payment_rate)
					$amount = $account->member->debt_payment_rate;
				else
					$amount = $account->member->entrance_fee;

				$entrance_fee_left = $account->member->entrance_fee;

				$date = date::get_closses_deduct_date_to($account->member->entrance_date);

				while (true)
				{
					// whole entrance fee is deducted
					if ($entrance_fee_left == 0)
						break;

					// stops on last deducted entrace fee's date in system
					if ($date > $last_entrance_fee_transfer)
						break;
					
					if ($amount > $entrance_fee_left)
						$amount = $entrance_fee_left;
					
					$created_transfers_count++;
					Transfer_Model::insert_transfer(
							$account->id, $infrastructure->id, null, null,
							Session::instance()->get('user_id'),
							Transfer_Model::DEDUCT_ENTRANCE_FEE,
							$date, $creation_datetime, __('Entrance fee'),
							$amount
					);

					$entrance_fee_left -= $amount;

                    $date = date::get_next_deduct_date_to($date);
				}
			}
			
			$account->transaction_commit();

			return array
			(
				'deleted' => $deleted_transfers_count,
				'created' => $created_transfers_count
			);
		}
		catch (Exception $e)
		{
			$account->transaction_rollback();
			throw $e;
		}
	}
	
	/**
	 * Recalculates member fees of account
	 * 
	 * @author Michal Kliment
	 * @param type $account_id
	 * @return type
	 * @throws Exception
	 */
	public static function recalculate_member_fees($account_id)
	{
		$account = new Account_Model($account_id);
		
		try
		{
			$account->transaction_start();
			
			$transfer_model = new Transfer_Model();

			$last_member_fee_transfer = substr($transfer_model->find_last_transfer_datetime_by_type(
				Transfer_Model::DEDUCT_MEMBER_FEE
			), 0, 10);
			
			// find leaving date of former member
			if ($account->member->type == Member_Model::TYPE_FORMER)
			{
				$leaving_date = date::get_closses_deduct_date_to($account->member->leaving_date);
			}
			else
			{
				$leaving_date = '9999-12-31';
			}
			
			// first of all is necessary to delete previous entrance fee transfers
			// user can change debt payment rate, this means that existing transfers are useless
			$deleted_transfers_count = $account->delete_deduct_transfers_of_account($account->id);

			// preparation
			$created_transfers_count = 0;
			
			// not recalculate fees for applicant
			if ($account->member->type != Member_Model::TYPE_APPLICANT)
			{			
				$creation_datetime = date('Y-m-d H:i:s');
				$operating = ORM::factory('account')->where(
						'account_attribute_id', Account_attribute_Model::OPERATING
				)->find();

				$date = date::get_closses_deduct_date_to($account->member->entrance_date);

				$fee_model = new Fee_Model();

				while (true)
				{
					if ($date == $leaving_date)
						break;
					
					if ($date > $last_member_fee_transfer)
						break;
					
					$amount = $fee_model->get_regular_member_fee_by_member_date(
						$account->member_id, $date
					);
					
					$name = $fee_model->get_regular_member_fee_name_by_member_date(
						$account->member_id, $date
					);
					
					if ($name === NULL)
						$name = __('Member fee');

					if ($amount)
					{
						$created_transfers_count++;
						Transfer_Model::insert_transfer(
								$account->id, $operating->id, null, null,
								Session::instance()->get('user_id'),
								Transfer_Model::DEDUCT_MEMBER_FEE,
								$date, $creation_datetime, $name,
								$amount
						);
					}

                    $date = date::get_next_deduct_date_to($date);
				}
			}
			
			$account->transaction_commit();
			
			return array
			(
				'deleted' => $deleted_transfers_count,
				'created' => $created_transfers_count
			);
		}
		catch (Exception $e)
		{
			$account->transaction_rollback();
			throw $e;
		}
	}
	
	/**
	 * Recalculates device fees of account
	 * 
	 * @author Michal Kliment
	 * @param type $account_id
	 * @return type
	 * @throws Exception
	 */
	public static function recalculate_device_fees($account_id = NULL)
	{
		$account = new Account_Model($account_id);
		
		try
		{
			$account->transaction_start();
		
			$devices = ORM::factory('device')
				->get_member_devices_with_debt_payments($account->member_id);

			$payments = array();
			$debt = 0;
			
			$last_deduct_device_fees = Settings::get('last_deduct_device_fees');
			
			// first of all is necessary to delete previous entrance fee transfers
			// user can change debt payment rate, this means that existing transfers are useless
			$deleted_transfers_count = $account->delete_device_deduct_transfers_of_account($account->id);

			$created_transfers_count = 0;

			if (count($devices))
			{
				$creation_datetime = date('Y-m-d H:i:s');
				$operating = ORM::factory('account')->where(
					'account_attribute_id', Account_attribute_Model::OPERATING
				)->find();
				
				foreach ($devices as $device)
				{
					// finds buy date of this device
					$buy_date = date_parse(date::get_closses_deduct_date_to($device->buy_date));

					$debt += $device->price;

					// finds all debt payments of this device
					money::find_debt_payments(
							$payments, $buy_date['month'], $buy_date['year'],
							$device->price, $device->payment_rate
					);
				}

				$year = min(array_keys($payments));
				$month = min(array_keys($payments[$year]));
				$day = date::get_deduct_day_to($month, $year);

				$date = date::create($day, $month, $year);

				while (true)
				{
					// all device fees are deducted
					if ($debt == 0)
						break;

					// stops on last deducted device fee's date in system
					if ($date > $last_deduct_device_fees)
						break;

					$pd = date_parse($date);

					if (isset($payments[$pd['year']][$pd['month']]))
					{
						$amount = $payments[$pd['year']][$pd['month']];

						if ($amount > $debt)
							$amount = $debt;

						$debt -= $amount;

						$created_transfers_count++;
						Transfer_Model::insert_transfer(
								$account->id, $operating->id, null, null,
								Session::instance()->get('user_id'),
								Transfer_Model::DEDUCT_DEVICE_FEE,
								$date, $creation_datetime, __('Device repayments'),
								$amount
						);
					}

                    $date = date::get_next_deduct_date_to($date);
				}
			}
			
			$account->transaction_commit();
			
			return array
			(
				'deleted' => $deleted_transfers_count,
				'created' => $created_transfers_count
			);
		}
		catch (Exception $e)
		{
			$account->transaction_rollback();
			throw $e;
		}
	}

}
