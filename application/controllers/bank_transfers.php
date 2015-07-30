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
 * Handles bank transfers operations, assigning unidentified bank transfers and
 * allows creating new bank transfers manually.
 * 
 * @author Jiri Svitak
 * @package Controller
 */
class Bank_transfers_Controller extends Controller
{
	// static types of bank transfers
	public static $member_fee = 1;
	public static $invoice = 2;
	
	// static types of bank fees
	public static $bank_fee = 1;
	public static $bank_interest = 2;
	public static $deposit = 3;
	public static $drawings = 4;
	
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
	 * Redirects to show all
	 */
	public function index()
	{
		url::redirect('bank_transfers/show_by_bank_account/1');
	}
	
	/**
	 * It shows transfers of bank account. Transaction list includes bank information like
	 * variable symbol or bank account number.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 */
	public function show_by_bank_account(
			$bank_account_id = NULL, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{
		if (!isset($bank_account_id))
			Controller::warning(PARAMETER);
		
		// bank account
		$ba = new Bank_account_Model($bank_account_id);
		
		if ($ba->id == 0)
			Controller::error(RECORD);
		
		// access rights
		if (!($this->acl_check_view('Accounts_Controller', 'bank_transfers') ||
			($ba->member_id == 1)))
		{
			Controller::error(ACCESS);
		}
			
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		$allowed_order_type = array
		(
			'id', 'name', 'amount', 'datetime', 'trans_type',
			'account_number', 'variable_symbol'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'desc';
		
		// Create filter form
		$filter_form = new Filter_form();
		
		$filter_form->add('datetime')
				->type('date')
				->label('Date and time');
		
		$filter_form->add('name')
				->label('Counteraccount')
				->callback('json/bank_account_name');
		
		$filter_form->add('variable_symbol')
				->callback('json/variable_symbol');
		
		$filter_form->add('account_nr')
				->label('Account number');
		
		$filter_form->add('amount')
				->type('number');
		
		// model
		$bt_model = new Bank_transfer_Model();			
		$total_bank_transfers = $bt_model->count_bank_transfers($bank_account_id, $filter_form->as_sql());
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_bank_transfers)
			$sql_offset = 0;
		
		$bts = $bt_model->get_bank_transfers(
				$bank_account_id, $sql_offset, (int)$limit_results, $order_by,
				$order_by_direction, $filter_form->as_sql()
		);
		
		$headline = __('Transfers of bank account');
		
		$grid = new Grid('transfers', null, array
		(
				'current'	    		=> $limit_results,
				'selector_increace'    	=> 50,
				'selector_min' 			=> 50, // minimum where selector start
				'selector_max_multiplier'=> 10,
				'base_url'    			=> Config::get('lang').'/bank_transfers/show_by_bank_account/'
										. $bank_account_id.'/'.$limit_results.'/'.$order_by.'/'.$order_by_direction ,
				'uri_segment'    		=> 'page',
				'total_items'    		=> $total_bank_transfers,
				'items_per_page' 		=> $limit_results,
				'style'          		=> 'classic',
				'order_by'				=> $order_by,
				'order_by_direction'		=> $order_by_direction,
				'limit_results'			=> $limit_results,
				'variables'				=>	$bank_account_id.'/',
				'url_array_ofset'		=> 1,
				'filter'				=> $filter_form
		));
		
		if ($ba->member_id == 1)
		{
			if ($this->acl_check_new('Accounts_Controller', 'bank_transfers'))
			{
				// add new bank transfer
				$grid->add_new_button(
						'bank_transfers/add/'.$bank_account_id,
						__('Add new bank transfer'),
						array(), help::hint('add_new_bank_transfer')
				);
				// add new bank fee
				$grid->add_new_button(
						'bank_transfers/add_fee/'.$bank_account_id,
						__('Add new bank transfer without counteraccount'),
						array(), help::hint('add_new_bank_transfer_without_counteraccount')
				);
			}
			if ($this->acl_check_view('Accounts_Controller', 'bank_statements'))
			{
				// show imported bank statements
				$grid->add_new_button(
						'bank_statements/show_by_bank_account/'.$bank_account_id,
						__('Show statements')
				);
			}
			if ($this->acl_check_new('Accounts_Controller', 'bank_transfers'))
			{
				$grid->add_new_button(
						'import/upload_bank_file/'.$bank_account_id,
						__('Upload bank transfers listing')
				);
			}
		}
		
		$grid->order_field('datetime')
				->label(__('Date and time'));
		
		$grid->order_field('name')
				->label(__('Counteraccount'));
		
		if ($this->acl_check_view('Accounts_Controller', 'bank_transfers'))
		{
			$grid->order_field('account_nr')
					->label(__('Account number'));
			
			$grid->order_field('bank_nr')
					->label(__('Bank code'));
		}
		
		$grid->order_field('text');
		
		$grid->order_field('variable_symbol')
				->label(__('VS'));
		
		$grid->order_callback_field('amount')
				->label(__('Amount'))
				->callback('callback::amount_field');
		
		if ($this->acl_check_view('Accounts_Controller', 'transfers'))
		{
			$grid->grouped_action_field()
					->add_action('transfer_id')
					->icon_action('show')
					->url('transfers/show')
					->label('Show transfer');
		}
		
		$grid->datasource($bts);
				
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts')
				->disable_translation()
				->text($headline)
				->html();
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('bank_transfers/show_by_bank_account');
		$view->content->headline = $headline;
		$view->content->ba = $ba;
		$view->content->grid = $grid;
		$view->render(TRUE);
	} // end of show_by_bank_account function

	/**
	 * Shows transfers of bank statement.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 */
	public function show_by_bank_statement(
			$bank_statement_id = NULL, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{
		if (!isset($bank_statement_id))
			Controller::warning(PARAMETER);
		
		// bank account
		$bs = new Bank_statement_Model($bank_statement_id);
		
		if ($bs->id == 0)
			Controller::error(RECORD);
		
		// access rights
		if (!$this->acl_check_view('Accounts_Controller', 'bank_transfers'))
			Controller::error(ACCESS);
			
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		$allowed_order_type = array
		(
			'id', 'name', 'amount', 'datetime', 'trans_type',
			'account_number', 'variable_symbol'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'desc';

		// model
		$bt_model = new Bank_transfer_Model();
		
		$total_bank_transfers = $bt_model->count_bank_transfers_by_statement($bank_statement_id);
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_bank_transfers)
			$sql_offset = 0;
		
		$bts = $bt_model->get_bank_transfers_by_statement(
				$bank_statement_id, $sql_offset, (int)$limit_results, 
				$order_by, $order_by_direction
		);
		
		$headline = __('Transfers of bank statement');
		
		$grid = new Grid('transfers', null, array(
			'current'	    		=> $limit_results,
			'selector_increace'    	=> 500,
			'selector_min' 			=> 500,
			'selector_max_multiplier'=> 10,
			'base_url'    			=> Config::get('lang').'/bank_transfers/show_by_bank_statement/'
									. $bank_statement_id.'/'.$limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'    		=> 'page',
			'total_items'    		=> $total_bank_transfers,
			'items_per_page' 		=> $limit_results,
			'style'          		=> 'classic',
			'order_by'				=> $order_by,
			'order_by_direction'	=> $order_by_direction,
			'limit_results'			=> $limit_results,
			'variables'				=>	$bank_statement_id.'/',
			'url_array_ofset'		=> 1,
		));

		$grid->order_field('datetime')
				->label(__('Date and time'));
		
		$grid->order_field('name')
				->label(__('Counteraccount'));
		
		if ($this->acl_check_view('Accounts_Controller', 'bank_transfers'))
		{
			$grid->order_field('account_nr')
					->label(__('Account number'));
			
			$grid->order_field('bank_nr')
					->label(__('Bank code'));
		}
		
		$grid->order_field('text')
				->label(__('Text'));
		
		$grid->order_field('variable_symbol')
				->label(__('VS'));
		
		$grid->order_callback_field('amount')
				->label(__('Amount'))
				->callback('callback::amount_field');
		
		if ($this->acl_check_view('Accounts_Controller', 'transfers'))
		{
			$grid->grouped_action_field()
					->add_action('transfer_id')
					->icon_action('show')
					->url('transfers/show')
					->label('Show transfer');
		}
		$grid->datasource($bts); 
		
		// statistics of bank statement
		$summary = array();
		$summary[__('Member fees')] =
				$bt_model->get_sum_of_member_fees_by_statement($bank_statement_id);
		$summary[__('Interests')] =
				$bt_model->get_sum_of_interests_by_statement($bank_statement_id);		
		$summary[__('Total inbound')] =
				$bt_model->get_sum_of_inbound_by_statement($bank_statement_id);
		$summary[__('Bank fees')] =
				$bt_model->get_sum_of_bank_fees_by_statement($bank_statement_id);
		$summary[__('Suppliers account')] =
				$bt_model->get_sum_of_suppliers_by_statement($bank_statement_id);
		$summary[__('Total outbound')] =
				$bt_model->get_sum_of_outbound_by_statement($bank_statement_id);
		
		$sum_table = new View('table_2_columns');
		$sum_table->table_data = array_map('money::format', $summary);
				
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts')
				->link('bank_statements/show_by_bank_account/'.$bs->bank_account_id,
						'Bank statements')
				->disable_translation()
				->text($headline)
				->html();
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $sum_table.$grid;
		$view->render(TRUE);
	}
	
	/**
	 * It shows unidentified bank transfers.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 */
	public function unidentified_transfers(
			$limit_results = 500, $order_by = 'id', $order_by_direction = 'asc',
			$page_word = null, $page = 1)
	{
		// access rights
        if (!$this->acl_check_view('Accounts_Controller', 'unidentified_transfers'))
        	Controller::error(ACCESS);
		
        // get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'datetime', 'amount', 'account_nr',
			'bank_nr', 'name', 'variable_symbol'
		);
		
		if (!in_array(strtolower($order_by),$allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		// Create filter form
		$filter_form = new Filter_form();
		
		$filter_form->add('datetime')
				->type('date')
				->label('Date and time');
		
		$filter_form->add('name')
				->label('Account name')
				->table('ba');
		
		$filter_form->add('variable_symbol');
		
		$filter_form->add('amount')
				->type('number');
		
		$filter_form->add('account_nr')
				->label('Account number');
		
		// bank transfer model
		$bt_model = new Bank_transfer_Model();
		$total_transfers = $bt_model->count_unidentified_transfers($filter_form->as_sql());

		if (($sql_offset = ($page - 1) * $limit_results) > $total_transfers)
			$sql_offset = 0;
		
		$bank_transfers = $bt_model->get_unidentified_transfers(
				$sql_offset, (int)$limit_results, $order_by,
				$order_by_direction, $filter_form->as_sql()
		);
		
		$headline = __('Unidentified transfers');
		
		$grid = new Grid('transfers', null, array
		(
			'current'	    			=> $limit_results,
			'selector_increace'    		=> 500,
			'selector_min' 				=> 500,
			'selector_max_multiplier'   => 10,
			'base_url'    				=> Config::get('lang').'/bank_transfers/unidentified_transfers/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'    			=> 'page',
			'total_items'    			=> $total_transfers,
			'items_per_page' 			=> $limit_results,
			'style'          			=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'url_array_ofset'			=> 0,
			'filter'					=> $filter_form
		));
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_field('datetime')
				->label('Date and time');
		
		if ($this->acl_check_view('Accounts_Controller', 'bank_accounts'))
		{
			$grid->order_field('account_nr')
					->label('Account number');
			
			$grid->order_field('bank_nr')
					->label('Bank code');
		}
		
		$grid->order_field('name')
				->label('Account name');
		
		$grid->order_field('variable_symbol');
		
		$grid->order_callback_field('amount')
				->callback('callback::money');
		
		if ($this->acl_check_new('Accounts_Controller', 'transfers'))
		{
			$grid->grouped_action_field()
					->add_action('id')
					->icon_action('money_add')
					->url('bank_transfers/assign_transfer')
					->label('Assign');
		}
		
		$grid->datasource($bank_transfers);
		
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->disable_translation()
				->text($headline)
				->html();
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('show_all');
		$view->content->headline = $headline.'&nbsp;'.help::hint('unidentified_transfers');
		$view->content->table = $grid;
		$view->render(TRUE);
	} // end of unidentified_transfers function

	/**
	 * @author Jiri Svitak, Tomas Dulik
	 * @param integer $trans_id id of a transfer from table transfers
	 */
	public function assign_transfer($trans_id = NULL)
	{
		// access rights 
		if (!$this->acl_check_edit('Accounts_Controller', 'unidentified_transfers'))
			Controller::error(ACCESS);
		
		if (!isset($trans_id))
			Controller::warning(PARAMETER);
		
		if (!is_numeric($trans_id))
			Controller::error(RECORD);
		
		$concat = "CONCAT(
				COALESCE(name, ''),
				' - " . __('Account ID') . " ',
				id,
				' - " . __('Member ID') . " ',
				COALESCE(member_id, '')
		)";
		
		$arr_accounts = ORM::factory('account')
				->where('account_attribute_id', Account_attribute_Model::CREDIT)
				->select_list('id', $concat);
		
		$arr_accounts = array
		(
			NULL => '----- '.__('Select').' -----'
		) + $arr_accounts;
		
		$bt_model = new Bank_transfer_Model();
		$bt = $bt_model->get_bank_transfer($trans_id);
		
		if (!is_object($bt))
			Controller::error(RECORD);
		
		$fee_model = new Fee_Model();
		// penalty
		$fee1 = $fee_model->get_by_date_type($bt->datetime, 'penalty');
		
		if (is_object($fee1) && $fee1->id)
			$penalty_fee = $fee1->fee;
		else
			$penalty_fee = 0;
		// transfer fee
		$fee2 = $fee_model->get_by_date_type($bt->datetime, 'transfer fee');
		
		if (is_object($fee2) && $fee2->id)
			$transfer_fee = $fee2->fee;
		else
			$transfer_fee = 0;
		// form
		$form = new Forge('bank_transfers/assign_transfer/'.$trans_id);
		
		$form->group('Payment');
		
		$form->dropdown('name')
				->label(__('Destination credit account').':')
				->options($arr_accounts)
				->selected(0);
		
		$form->input('correct_vs')
				->label(__('Or enter correct variable symbol').':')
				->callback(array($this, 'valid_correct_vs'));
		
		$form->input('text')
				->rules('required')
				->value(__('Assigning of unidentified payment'));
		
		$form->group('Penalty');
		
		$form->input('penalty')
				->value($penalty_fee)
				->rules('valid_numeric');
		
		$form->input('penalty_text')
				->label(__('Text').':')
				->value(__('Penalty for unidentified transfer'));
		
		$form->group('Transfer fee');
		
		$form->input('transfer_fee')
				->label(__('Amount').':')
				->value($transfer_fee)
				->rules('valid_numeric');
		
		$form->input('fee_text')
				->label(__('Text').':')
				->value(__('Transfer fee'));
		
		$form->submit('Assign');
		
		// validation
		if ($form->validate())
		{
			$form_data = $form->as_array();
			// finding member
			$dst_acc = new Account_Model($form_data['name']);
			
			if ($dst_acc->id == 0)
			{
				// account has not been selected, trying to find member by correct variable symbol
				/*$member = ORM::factory('member')
						->where('variable_symbol', $form_data['correct_vs'])
						->find();*/
				$member = ORM::factory('variable_symbol')
						->where('variable_symbol', $form_data['correct_vs'])
						->find()->account->member;
				if (is_object($member) && $member->id != 0)
				{
					$member_id = $member->id;
					
					$account = ORM::factory('account')
							->where('member_id', $member->id)
							->find();
					
					$dst_id = $account->id;
				}
				else
				{
					Controller::error(RECORD);
				}
			}
			else
			{	
				$member_id = $dst_acc->member_id;
				$dst_id = $dst_acc->id;
			}
			try
			{
				$db = new Transfer_Model();
				$db->transaction_start();
				$creation_datetime = date("Y-m-d H:i:s", time());
				$user_id = $this->session->get('user_id');
				// first we assign the first transfer to the selected member
				$t = new Transfer_Model($trans_id);
				$t->member_id = $member_id;
				$t->save_throwable();
				// then we create a new transfer to the selected member's account
				Transfer_Model::insert_transfer(
						$bt->destination_id, $dst_id, $bt->id, $member_id,
						$user_id, null, $bt->datetime, $creation_datetime,
						$form_data['text'], $bt->amount
				);

				// assign also all subsequent transfers to the selected member
				$next_ts = ORM::factory('transfer')
						->where('previous_transfer_id', $trans_id)
						->find_all();
				
				foreach ($next_ts as $transfer)
				{
					$transfer->member_id = $member_id;
					$transfer->save_throwable();
				}
				
				// also penalty should be generated
				$operating = ORM::factory('account')
						->where('account_attribute_id', Account_attribute_Model::OPERATING)
						->find();
				
				if ($form_data['penalty'] > 0)
				{
					Transfer_Model::insert_transfer(
							$dst_id, $operating->id, $bt->id, null,
							$user_id, null, $bt->datetime, $creation_datetime,
							$form_data['penalty_text'], $form_data['penalty']
					);
				}
				// transfer fee, if it has to be generated
				if ($form_data['transfer_fee'] > 0)
				{
					Transfer_Model::insert_transfer(
							$dst_id, $operating->id, $bt->id, null,
							$user_id, null, $bt->datetime, $creation_datetime,
							$form_data['fee_text'], $form_data['transfer_fee']
					);
				}
				$db->transaction_commit();
				status::success('Payment has been successfully assigned.');
			}
			catch (Exception $e)
			{
				$db->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cannot assign transfer.', $e);
			}
			url::redirect('bank_transfers/unidentified_transfers');
		}
		
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->link('bank_transfers/unidentified_transfers', 'Unidentified transfers')
				->text(__('Assign transfer'))
				->html();		
		
		$view = new View('main');
		$view->title = __('Assign transfer');
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('bank_transfers/assign_transfer');
		$view->content->mt = $bt;		
		$view->content->form = $form->html();
		$view->render(TRUE);
	} // end of assign_transfer function

	/**
	 * Function enables adding of bank transfers manually.
	 * 
	 * @author Jiri Svitak
	 * @param integer $baa_id bank account of association
	 */
	public function add($baa_id = null)
	{
		// access rights 
		if (!$this->acl_check_new('Accounts_Controller', 'bank_transfers'))
			Controller::error(ACCESS);
		
		if (!isset($baa_id))
			Controller::warning(PARAMETER);
		
		// origin bank account
		$baa = new Bank_account_Model($baa_id);
		
		if ($baa->id == 0 || $baa->member_id != 1)
			Controller::error(RECORD);
		
		// bank transfer type
		$arr_bt_types[self::$member_fee] = __('Member fee');
		$arr_bt_types[self::$invoice] = __('Invoice');
		// form		
		$form = new Forge('bank_transfers/add/'.$baa_id);
		
		$form->set_attr('method', 'post');
		
		// counteraccount
		$form->group('Counteraccount');
		
		$form->input('counteraccount')
				->label('Counteraccount number')
				->rules('required');
		
		$form->input('counteraccount_bc')
				->label('Bank code')
				->rules('required');
		
		$form->input('counteraccount_name')
				->label('Account name');		
		
		// transfer
		$form->group('Transfer');
		
		$form->dropdown('type')
				->label('Bank transfer type')
				->options($arr_bt_types)
				->style('width:200px');
		
		$form->date('datetime')
				->label('Date and time')
				->years(date('Y')-20, date('Y'))
				->rules('required');
		
		$form->input('amount')
				->rules('required|valid_numeric')
				->callback(array($this, 'valid_amount'));
		
		$form->input('text')
				->rules('required');
		
		// bank transfer
		$form->group('Bank transfer');
		
		$form->input('variable_symbol');
		
		$form->input('constant_symbol');
		
		$form->input('specific_symbol');
		
		// bank transfer fee
		$form->group('Bank transfer fee');
		
		$form->input('bank_transfer_fee')
				->label('Amount')
				->value(0);
		
		$form->input('fee_text')
				->label('Text')
				->value(__('Bank transfer fee'));
		
		// submit
		$form->submit('Add');
		
		// validation		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			// preparation
			$creation_datetime = date('Y-m-d H:i:s', time());
			
			$operating = ORM::factory('account')
					->where('account_attribute_id', Account_attribute_Model::OPERATING)
					->find();
			
			$member_fees = ORM::factory('account')
					->where('account_attribute_id', Account_attribute_Model::MEMBER_FEES)
					->find();
			
			$suppliers = ORM::factory('account')
					->where('account_attribute_id', Account_attribute_Model::SUPPLIERS)
					->find();
			
			// counter-account
			$counter_ba = ORM::factory('bank_account')
					->where(array
					(
							'account_nr' => $form_data['counteraccount'],
							'bank_nr' => $form_data['counteraccount_bc'])
					)->find();
			// transaction
			try
			{
				$db = new Transfer_Model();
				$db->transaction_start();
				
				if (!$counter_ba->id)
				{
					$counter_ba = new Bank_account_Model();
					$counter_ba->name = $form_data['counteraccount_name'];
					$counter_ba->account_nr = $form_data['counteraccount'];
					$counter_ba->bank_nr = $form_data['counteraccount_bc'];
					$counter_ba->save_throwable();
				}
				
				// this bank account
				$account = $baa->get_related_account_by_attribute_id(
					Account_attribute_Model::BANK
				);
				
				$bank_fees = $baa->get_related_account_by_attribute_id(
					Account_attribute_Model::BANK_FEES
				);
				
				// double-entry transfer
				if ($form_data['type'] == self::$member_fee)
				{
					$t_origin_id = $member_fees->id;
					$t_destination_id = $account->id;
				}
				elseif ($form_data['type'] == self::$invoice)
				{
					$t_origin_id = $account->id;
					$t_destination_id = $suppliers->id;
				}
				
				$user_id = $this->session->get('user_id');
				$datetime = date('Y-m-d', $form_data['datetime']);
				
				$t_id = Transfer_Model::insert_transfer(
						$t_origin_id, $t_destination_id, null, null,
						$user_id, null, $datetime, $creation_datetime,
						$form_data['text'], $form_data['amount']
				);
				
				// bank transfer
				$bt = new Bank_transfer_Model();
				$bt->transfer_id = $t_id;
				
				if ($form_data['type'] == self::$member_fee)
				{
					$bt->origin_id = $counter_ba->id;
					$bt->destination_id = $baa->id;
				}
				elseif ($form_data['type'] == self::$invoice)
				{
					$bt->origin_id = $baa_id;
					$bt->destination_id = $counter_ba->id;
				}
				
				$bt->constant_symbol = $form_data['constant_symbol'];
				$bt->variable_symbol = $form_data['variable_symbol'];
				$bt->specific_symbol = $form_data['specific_symbol'];
				$bt->save_throwable();
				
				// bank transfer fee
				if ($form_data['bank_transfer_fee'] > 0)
				{
					// bank transfer fee - double-entry part
					$btf_origin_id = $account->id;
					$btf_destination_id = $bank_fees->id;
					
					$btf_id = Transfer_Model::insert_transfer(
							$btf_origin_id, $btf_destination_id, $t_id, null,
							$user_id, null, $datetime, $creation_datetime,
							$form_data['fee_text'], $form_data['bank_transfer_fee']
					);
					
					// bank transfer fee - bank part
					$btf2 = new Bank_transfer_Model();
					$btf2->transfer_id = $btf_id;
					$btf2->origin_id = $baa->id;
					$btf2->destination_id = null;
					$btf2->save_throwable();
					
					// accounting of fee - it is payed by association from operating account
					$btf3_id = Transfer_Model::insert_transfer(
							$operating->id, $account->id, $t_id, null,
							$user_id, null, $datetime, $creation_datetime,
							$form_data['fee_text'], $form_data['bank_transfer_fee']
					);
				}
				// identifying member fee
				if ($form_data['type'] == self::$member_fee)
				{
					// searching member
					/*$member = ORM::factory('member')
							->where('variable_symbol', $form_data['variable_symbol'])
							->find();*/
					$member = ORM::factory('variable_symbol')
							->where('variable_symbol',$form_data['variable_symbol'])
							->find()->account->member;
					
					if ($member->id)
					{
						// finding credit account
						$ca = ORM::factory('account')
								->where(array
								(
									'member_id' => $member->id,
									'account_attribute_id' => Account_attribute_Model::CREDIT
								))->find();
						
						// identified transfer
						Transfer_Model::insert_transfer(
								$account->id, $ca->id, $t_id, $member->id,
								$user_id, null, $datetime, $creation_datetime,
								__('Assigning of transfer'),
								$form_data['amount']
						);
						// if there is transfer fee, then it is generated
						$fee_model = new Fee_Model();
						$fee = $fee_model->get_by_date_type(
								date('Y-m-d', $form_data['datetime']), 'transfer fee'
						);
						if (is_object($fee) && $fee->id)
						{
							// transfer fee
							Transfer_Model::insert_transfer(
									$ca->id, $operating->id, $t_id, $member->id,
									$user_id, null, $datetime, $creation_datetime,
									__('Transfer fee'), $fee->fee
							);
						}
						// identification of transfer
						$transfer = new Transfer_Model($t_id);
						$transfer->member_id = $member->id;
						$transfer->save_throwable();
						// identification of bank fee
						if ($form_data['bank_transfer_fee'] > 0)
						{
							$btf = new Transfer_Model($btf_id);
							$btf->member_id = $member->id;
							$btf->save_throwable();
							$btf3 = new Transfer_Model($btf3_id);
							$btf3->member_id = $member->id;
							$btf3->save_throwable();
						}
						// identification of origin bank account
						if (empty($counter_ba->member_id))
						{
							$counter_ba->member_id = $member->id;
							$counter_ba->save_throwable();
						}
					}
				}
				$db->transaction_commit();
				status::success('Bank transfer has been successfully added.');
			}
			catch (Exception $e)
			{
				$db->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cannot add bank transfer.', $e);
			}
			url::redirect('bank_transfers/show_by_bank_account/'.$baa_id);
		}
		
		$headline = __('Add new bank transfer');
		
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->link('bank_transfers/show_by_bank_account/' . $baa_id,
						$baa->name . ' (' . $baa->id . ')')
				->text('Bank transfers')
				->disable_translation()
				->text($headline)
				->html();
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);		
	}
	
	/**
	 * Function adds bank transfer without counteraccount.
	 * 
	 * @param integer $baa_id
	 */
	public function add_fee($baa_id = null)
	{
		// access rights 
		if (!$this->acl_check_new('Accounts_Controller', 'bank_transfers'))
			Controller::error(ACCESS);
		
		if (!isset($baa_id))
			Controller::warning(PARAMETER);
		
		// origin bank account
		$baa = new Bank_account_Model($baa_id);
		
		if ($baa->id == 0 || $baa->member_id != 1)
			Controller::error(RECORD);
		
		// bank transfer type
		$types[self::$bank_fee] = __('Bank fee');
		$types[self::$bank_interest] = __('Bank interest');
		$types[self::$deposit] = __('Deposit');
		
		// form		
		$form = new Forge('bank_transfers/add_fee/'.$baa_id);
		
		// bank transfer fee
		$form->group('Bank transfer fee');
		
		$form->dropdown('type')
				->options($types)
				->style('width:200px');
		
		$form->date('datetime')
				->label('Date and time')
				->years(date('Y')-20, date('Y'))
				->rules('required');
		
		$form->input('amount')
				->rules('required|valid_numeric')
				->callback(array($this, 'valid_amount'));
		
		$form->input('text')
				->rules('required');
		
		// submit
		$form->submit('Add');
		
		// validation		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			// preparation
			$creation_datetime = date('Y-m-d H:i:s');
			$datetime = date('Y-m-d', $form_data['datetime']);
			$user_id = $this->session->get('user_id');
			$account = $baa->get_related_account_by_attribute_id(
				Account_attribute_Model::BANK
			);
			$bank_fees = $baa->get_related_account_by_attribute_id(
				Account_attribute_Model::BANK_FEES
			);
			$bank_interests = $baa->get_related_account_by_attribute_id(
				Account_attribute_Model::BANK_INTERESTS
			);
			$cash = ORM::factory('account')
					->where('account_attribute_id', Account_attribute_Model::CASH)
					->find();
			// transfer
			if ($form_data['type'] == self::$bank_fee)
			{
				$t_origin_id = $account->id;
				$t_destination_id = $bank_fees->id;
			}
			elseif ($form_data['type'] == self::$bank_interest)
			{
				$t_origin_id = $bank_interests->id;
				$t_destination_id = $account->id;
			}
			elseif ($form_data['type'] == self::$deposit)
			{
				$t_origin_id = $cash->id;
				$t_destination_id = $account->id;
			}
			
			try
			{
				$db = new Transfer_Model();
				$db->transaction_start();
				
				$t_id = Transfer_Model::insert_transfer(
						$t_origin_id, $t_destination_id, null,
						null, $user_id, null, $datetime,
						$creation_datetime, $form_data['text'],
						$form_data['amount']
				);
				// bank transfer
				$bt = new Bank_transfer_Model();
				if ($form_data['type'] == self::$bank_fee)
				{
					$bt->origin_id = $baa->id;
					$bt->destination_id = null;
				}
				elseif ($form_data['type'] == self::$bank_interest ||
						$form_data['type'] == self::$deposit)
				{
					$bt->origin_id = null;
					$bt->destination_id = $baa->id;
				}
				$bt->transfer_id = $t_id;
				$bt->save_throwable();
				
				$db->transaction_commit();
				status::success('Bank transfer has been successfully added.');
			}
			catch (Exception $e)
			{
				$db->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cannot add bank transfer.', $e);				
			}
			
			url::redirect('bank_transfers/show_by_bank_account/'.$baa_id);
		}
		
		$headline = __('Add new bank transfer without counteraccount');
		
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->link('bank_transfers/show_by_bank_account/' . $baa_id,
						$baa->name . ' (' . $baa->id . ')')
				->text('Bank transfers')
				->disable_translation()
				->text($headline)
				->html();
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);		
	}

	/**
	 * Function validates amount.
	 * 
	 * @param unknown_type $input
	 */
	public function valid_amount($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if ($input->value <= 0)
		{
			$input->add_error('required', __('Error - amount has to be positive.'));
		}
	}

	/**
	 * Function validates variable symbol.
	 * Do not control by javascript! Let it possible to leave variable symbol empty.
	 * Target account for assigning unidentified transfer can be selected separately.
	 * 
	 * @author Jiri Svitak
	 * @param unknown_type $input
	 */
	public function valid_correct_vs($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if ($this->input->post('name') == 0)
		{
			/*$member = ORM::factory('member')
					->where('variable_symbol', trim($input->value))
					->find();*/
			$member = ORM::factory('variable_symbol')
					->where('variable_symbol', trim($input->value))
					->find()->account->member;
			if (!is_object($member) || $member->id == 0)
			{
				$input->add_error('required', __(
						'Variable symbol has not been found in the database.'
				)); 
			}
		}
	}

}
