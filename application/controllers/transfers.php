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
 * Handles double-entry transfers and special actions 
 * with transfers like deducting fees etc.
 * 
 * @author Jiri Svitak
 * @package Controller
 */
class Transfers_Controller extends Controller
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
	 * By default, it redirects user to day book - list of all double-entry transfers.
	 * 
	 * @author Jiri Svitak
	 * @return unknown_type
	 */
	public function index()
	{
		url::redirect('transfers/show_all');
	}

	/**
	 * It shows all double-entry transfers. They are shown in day book.
	 * 
	 * @author Jiri Svitak
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'datetime',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{
		if (!$this->acl_check_view('Accounts_Controller', 'transfers'))
			Controller::error(ACCESS);
		
		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'oa_name', 'da_name', 'oa_attribute', 'da_attribute',
			'datetime', 'text', 'amount'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		// there are two groups of transfers
		$arr_groups[Transfer_Model::OUTER_TRANSFERS] = __('Outer transfers');
		$arr_groups[Transfer_Model::INNER_TRANSFERS] = __('Inner transfers');
		
		// Create filter form
		$filter_form = new Filter_form();
		
		$filter_form->add('oa_name')
				->label('Origin account')
				->callback('json/bank_account_name');
		
		$filter_form->add('datetime')
				->label('Date and time')
				->type('date')
				->default(Filter_form::OPER_GREATER_OR_EQUAL, date('Y-m-d', time() - 3600*24*7));
		
		$filter_form->add('da_name')
				->label('Destination account')
				->callback('json/bank_account_name');
		
		$filter_form->add('amount')
				->label('Amount')
				->type('number');
		
		$filter_form->add('text');
		
		$filter_form->add('transfer')
				->label('Group')
				->type('select')
				->values($arr_groups);
		
		// model
		$model_transfer = new Transfer_Model();
		$total_transfers = $model_transfer->count_all_transfers(
				$filter_form->as_sql(array('datetime', 'amount', 'text')),
				$filter_form->as_sql(array('oa_name', 'da_name', 'transfer'))
		);
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_transfers)
			$sql_offset = 0;
		
		$alltransfers = $model_transfer->get_all_transfers(
				$sql_offset, (int) $limit_results, $order_by,
				$order_by_direction,
				$filter_form->as_sql(array('datetime', 'amount', 'text')),
				$filter_form->as_sql(array('oa_name', 'da_name', 'transfer'))
		);
		
		$headline = __('Day book');
		
		$grid = new Grid(url_lang::base() . 'transfers', null, array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min'				=> 50,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang') . '/transfers/show_all/'
										. $limit_results . '/' . $order_by . '/' . $order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_transfers,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		if ($this->acl_check_edit('Accounts_Controller', 'transfers'))
		{
			$deduct_day = (Settings::get('deduct_day') + 1) % 32;
			$link_opt = array();
			
			// automaticall deduction
			if (Settings::get('deduct_fees_automatically_enabled'))
			{
				$auto_m = 'Automatically deduction is enabled, this operation ' .
						'should be use only if automatical deduction has failed.';
				$link_opt = array
				(
					'title'	=> __($auto_m),
					'class'	=> 'deprecated help'
				);
			}
			
			$grid->add_new_button(
					'transfers/add', __('Add new transfer'),
					array(), help::hint('add_new_transfer')
			);
			
			$grid->add_new_button(
					'transfers/deduct_member_fees', __('Deduction of member fees'),
					$link_opt + array('class' => 'popup_link'),
					help::hint('deduct_member_fees', $deduct_day)
			);
			
			$grid->add_new_button(
					'transfers/deduct_entrance_fees',
					__('Deduction of entrance fees'),
					$link_opt + array('class' => 'popup_link'),
					help::hint('deduct_entrance_fees', $deduct_day)
			);
			
			$grid->add_new_button(
					'transfers/deduct_device_fees',
					__('Deduction of device repayments'),
					$link_opt + array('class' => 'popup_link'),
					help::hint('deduct_device_repayments')
			);
		}
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_link_field('oa_id')
				->link('transfers/show_by_account', 'oa_name')
				->label('Origin account');
		
		$grid->order_field('oa_attribute')
				->label(__('Type'));
		
		
		$grid->order_link_field('da_id')
				->link('transfers/show_by_account', 'da_name')
				->label('Destination account');
		
		$grid->order_field('da_attribute')
				->label(__('Type'));
		
		$grid->order_field('datetime')
				->label(__('Date and time'));
		
		$grid->order_field('text')
				->label(__('Text'));
		
		$grid->order_callback_field('daybook_amount')
				->label(__('Amount'))
				->callback('callback::money');
		
		if ($this->acl_check_view('Accounts_Controller', 'transfers'))
		{
			$grid->grouped_action_field()
					->add_action('id')
					->icon_action('show')
					->url('transfers/show')
					->label('Show transfer');
		}
		
		$grid->datasource($alltransfers);
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $headline;
		$view->content = new View('show_all');
		$view->content->headline = $headline . '&nbsp;' . help::hint('day_book');		
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * It shows transfers of credit account.
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id
	 * @return unknown_type
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 */
	public function show_by_account(
			$account_id = NULL, $limit_results = 500, $order_by = 'datetime',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{
		if (!isset($account_id))
		{
			url::redirect('transfers/show_all');
		}
		
		$account = new Account_Model($account_id);
		$variable_symbol_model = new Variable_Symbol_Model();
		
		$variable_symbols = 0;
		if ($account->member_id != 1)
		{
		    $variable_symbols = $variable_symbol_model->find_account_variable_symbols($account->id);
		}
		
		if (!$account->id)
			Controller::error(RECORD);
		
		if (!(
				$this->acl_check_view('Accounts_Controller', 'transfers', $account->member_id) ||
				$this->acl_check_view('Members_Controller', 'currentcredit')
			))
		{
			Controller::error(ACCESS);
		}
		
		// gets grid settings
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		// allowed order type array
		$allowed_order_type = array
		(
			'id', 'trans_type', 'name', 'datetime', 'amount', 'text'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'datetime';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'desc';
		
		// creates fields for filtering
		$arr_types[Transfer_Model::INBOUND] = __('Inbound');
		$arr_types[Transfer_Model::OUTBOUND] = __('Outbound');
		
		// Create filter form
		$filter_form = new Filter_form('t');
		
		$filter_form->add('name')
				->table('a')
				->label('Counteraccount')
				->callback('json/bank_account_name');
		
		$filter_form->add('datetime')
				->label('Date and time')
				->type('date');
		
		$filter_form->add('amount')
				->label('Amount')
				->type('number');
		
		$filter_form->add('text');
		
		$filter_form->add('type')
				->type('select')
				->values($arr_types);
		
		// transfers on account
		$transfer_model = new Transfer_Model();
		$total_transfers = $transfer_model->count_transfers($account_id,
				$filter_form->as_sql(array
					(
						'name', 'datetime', 'amount', 'text'
					)),
				$filter_form->as_array()
		);
		if (($sql_offset = ($page - 1) * $limit_results) > $total_transfers)
			$sql_offset = 0;
		
		$transfers = $transfer_model->get_transfers(
				$account_id, $sql_offset, (int) $limit_results, $order_by,
				$order_by_direction,
				$filter_form->as_sql(array
					(
						'name', 'datetime', 'amount', 'text'
					)),
				$filter_form->as_array()
		);

		// total amount of inbound and outbound transfers and total balance
		$balance = $inbound = $outbound = 0;
		if (count($transfers) > 0)
		{
			// inbound and outbound amount of money are calculated from transfers of account
			$inbound = $transfers->current()->inbound;
			$outbound = $transfers->current()->outbound;
			// balance is not calculated, fast redundant value from account itself is used
			$balance = $account->balance;
		}
		// headline
		$headline = __('Transfers of double-entry account');
		// grid of transfers
		$transfers_grid = new Grid('transfers', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 500,
			'selector_min'				=> 500,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang') . '/transfers/show_by_account/'
										. $account_id . '/' . $limit_results . '/'
										. $order_by . '/' . $order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_transfers,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'variables'					=> $account_id . '/',
			'url_array_ofset'			=> 1,
			'filter'					=> $filter_form
		));

		// payment cash link
		if ($this->acl_check_new('Accounts_Controller', 'transfers'))
		{
			$transfers_grid->add_new_button(
					'transfers/add_member_fee_payment_by_cash/' .
					$account->member->id, __('Payment by cash')
			);
		}
		
		if ($this->acl_check_new('Accounts_Controller', 'transfers', $account->member_id))
		{	
			$transfers_grid->add_new_button(
					'transfers/add_from_account/' . $account_id,
					__('Send money to other account'),
					array(), help::hint('add_from_account')
			);

			$account = ORM::factory('account')->where('id', $account_id)->find();

			if (Billing::instance()->has_driver() &&
				Billing::instance()->get_account($account->member_id))
			{
				$transfers_grid->add_new_button(
						'transfers/add_voip/' . $account_id,
						__('Recharge VoIP credit')
				);
			}
		}
		if ($account->account_attribute_id == Account_attribute_Model::CREDIT)
		{
			if ($this->acl_check_edit('Accounts_Controller', 'transfers'))
			{	
				$transfers_grid->add_new_button(
						'accounts/recalculate_fees/' . $account->id,
						__('Recount of fees'),
						array(),
						help::hint('recalculate_fees')
				);
			}
		}
		$transfers_grid->order_field('id')
				->label('ID');
		
		$transfers_grid->order_field('name')
				->label(__('Counteraccount'));
		
		$transfers_grid->order_field('datetime')
				->label(__('Date and time'));
		
		$transfers_grid->order_callback_field('amount')
				->label(__('Amount'))
				->callback('callback::amount_field');
		
		$transfers_grid->order_field('text')
				->label(__('Text'));
		
		$transfers_grid->order_field('variable_symbol')
				->label(__('VS'));
		
		if ($this->acl_check_view('Accounts_Controller', 'transfers', $account->member_id))
		{
			$transfers_grid->grouped_action_field()
					->add_action('id')
					->icon_action('show')
					->url('transfers/show')
					->label('Show transfer');
		}
		
		$transfers_grid->datasource($transfers);

		if ($this->acl_check_view('Members_Controller', 'comment', $account->member_id))
		{
			$comment_model = new Comment_Model();
			$comments = $comment_model->get_all_comments_by_comments_thread($account->comments_thread_id);

			$comments_grid = new Grid(url_lang::base() . 'members', null, array
			(
				'separator'		=> '<br /><br />',
				'use_paginator'	=> false,
				'use_selector'	=> false,
			));

			$url = ($account->comments_thread_id) ?
						'comments/add/' . $account->comments_thread_id :
						'comments/add_thread/account/' . $account->id;
			
			$comments_grid->add_new_button(
					$url, __('Add comment to financial state of member')
			);

			$comments_grid->field('text');
			
			$comments_grid->link_field('user_id')
					->link('users/show', 'user_name')
					->label('User');
			
			$comments_grid->field('datetime')
					->label(__('Time'));
			
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
			
			$comments_grid->datasource($comments);
		}

		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members')
				)->disable_translation();
		if ($account->member_id) {
			$breadcrumbs->link('members/show/' . $account->member_id,
					'ID ' . $account->member->id . ' - ' . $account->member->name,
					$this->acl_check_view(
							'Members_Controller', 'members',
							$account->member_id
					)
			);
		}
		$breadcrumbs->enable_translation()->text('Transfers');

		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('transfers/show_by_account');
		$view->content->headline = $headline;
		$view->content->account = $account;
		$view->content->balance = $balance;
		$view->content->inbound = $inbound;
		$view->content->outbound = $outbound;
		$view->content->variable_symbols = $variable_symbols;
		
		if ($account->member->type != Member_Model::TYPE_APPLICANT &&
			$account->member->type != Member_Model::TYPE_FORMER)
		{
			$view->content->expiration_date = Members_Controller::get_expiration_date($account);
		}
		
		$view->content->transfers_grid = $transfers_grid;
		
		if ($this->acl_check_view('Members_Controller', 'comment', $account->member_id))
			$view->content->comments_grid = $comments_grid;
		
		$view->render(TRUE);
	} // end of show_by_account function

	/**
	 * Function shows information of transfer including previous transfer if exists.
	 * 
	 * @author Jiri Svitak
	 * @param integer $transfer_id
	 */
	public function show($transfer_id = null)
	{
		if (!isset($transfer_id))
			Controller::warning(PARAMETER);
		
		if (!is_numeric($transfer_id))
			Controller::error(RECORD);
		
		$transfer_model = new Transfer_Model();
		$transfer = $transfer_model->get_transfer($transfer_id);
		
		if (!is_object($transfer))
			Controller::error(RECORD);
		
		$oa = new Account_Model($transfer->oa_id);
		$da = new Account_Model($transfer->da_id);
		
		if ($oa->member_id != 1)
			$member_id = $oa->member_id;
		elseif ($da->member_id != 1)
			$member_id = $da->member_id;
		else
			$member_id = 1;
		
		if (!$this->acl_check_view('Accounts_Controller', 'transfers', $member_id))
			Controller::error(ACCESS);
		
		// transfers dependent on this transfer, if this transfer is member fee payment
		$dependent_transfers = $transfer_model->get_dependent_transfers($transfer->id);
		
		// bank transfer is only assigned to transfer from member fees account to account of association
		$member_fees = ORM::factory('account')->where(
				'account_attribute_id', Account_attribute_Model::MEMBER_FEES
		)->find();
		// bt has to be first set to null, transfer need not to be of bank type
		$bt = null;
		
		$bt_model = ORM::factory('bank_transfer')->where(
				'transfer_id', $transfer->id
		)->find();
		
		if ($bt_model->id)
			$bt = $bt_model->get_bank_transfer($transfer_id);
		
		$headline = __('Detail of transfer number') . ' ' . $transfer->id;
		$view = new View('main');
		$view->title = $headline;
		$view->content = new View('transfers/show');
		$view->content->headline = $headline;
		$view->content->transfer = $transfer;
		$view->content->dependent_transfers = $dependent_transfers;
		$view->content->bt = $bt;
		$view->render(TRUE);
	}

	/**
	 * Adds transfers from single origin account.
	 * 
	 * @todo set of accounts choosen by account_attribute_id, must be done by ajax
	 * @author Jiri Svitak
	 * @param integer $origin_account_id
	 */
	public function add_from_account($origin_account_id = null)
	{
		if (!isset($origin_account_id) || !is_numeric($origin_account_id))
			Controller::warning(PARAMETER);
		
		// save for callback function valid_amount_to_send
		$this->origin = $origin_account_id;
		$origin_account = new Account_Model($origin_account_id);
		
		if ($origin_account->id == 0)
			Controller::error(RECORD);
		
		if (!$this->acl_check_new('Accounts_Controller', 'transfers', $origin_account->member_id))
			Controller::error(ACCESS);
		
		// destination account, instead of origin one
		$dst_account_model = new Account_Model();
		$arr_dst_accounts = $dst_account_model->select_some_list($origin_account_id);
		asort($arr_dst_accounts, SORT_LOCALE_STRING);
		// default destination account
		$operating = ORM::factory('account')->where(
				'account_attribute_id', Account_attribute_Model::OPERATING
		)->find();
		// array with only one origin account
		$arr_orig_accounts[$origin_account->id] = $origin_account->name . ' ('
                . $origin_account->id . ', '
                . $origin_account->account_attribute_id . ', '
                . $origin_account->member_id . ')';
		// account attributes for types of accounts
		$aa_model = new Account_attribute_Model();
		$account_attributes = $aa_model->get_account_attributes();
		
		foreach ($account_attributes as $account_attribute)
		{
			$arr_attributes[$account_attribute->id] = $account_attribute->id . ' ' . $account_attribute->name;
		}
		
		$arr_attributes = arr::merge(array
		(
			NULL => '----- ' . __('Select account type') . ' -----'), $arr_attributes
		);
		
		// form
		$form = new Forge('transfers/add_from_account/' . $origin_account_id);
		
		// origin account
		$form->group('Origin account');
		
		$form->dropdown('oname')
				->label('Origin account (name, ID, type, member ID)')
				->options($arr_orig_accounts)
				->rules('required')
				->style('width:600px');
		
		// destination account
		$form->group('Destination account');
		
		$form->dropdown('account_type')
				->options($arr_attributes)
				->style('width:600px');
		
		$form->dropdown('aname')
				->label('Destination account (name, ID, type, member ID)')
				->options($arr_dst_accounts)
				->rules('required')
				->selected($operating->id)
				->style('width:600px');
		
		// other information
		$form->group('Transfer');
		
		$form->date('datetime')
				->label('Date and time')
				->years(date('Y') - 20, date('Y'))
				->rules('required');
		
		$form->input('amount')
				->rules('required|valid_numeric')
				->callback(array($this, 'valid_amount_to_send'));
		
		$form->input('text')
				->rules('required');
		
		$form->submit('Send');
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{	
				$db = new Transfer_Model();
				$db->transaction_start();
				
				Transfer_Model::insert_transfer(
						$form_data['oname'], $form_data['aname'], null,
						null, $this->session->get('user_id'),
						null, date('Y-m-d', $form_data['datetime']),
						date('Y-m-d H:i:s'), $form_data['text'],
						$form_data['amount']
				);
				
				$member_model = new Member_Model();
				$dst_account = new Account_Model($form_data['aname']);
				
				$member_model->reactivate_messages($origin_account->member_id);
				$member_model->reactivate_messages($dst_account->member_id);
				
				$db->transaction_commit();
				status::success('Transfer has been successfully added.');
			}
			catch (Exception $e)
			{
				$db->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cannot add new transfer.', $e);
			}
			url::redirect('transfers/show_by_account/' . $origin_account_id);
		}
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members')
				)->disable_translation()
				->link('members/show/' . $origin_account->member_id,
						'ID ' . $origin_account->member->id . ' - ' . $origin_account->member->name,
						$this->acl_check_view(
								'Members_Controller', 'members',
								$origin_account->member_id
						)
				)->enable_translation()
				->link('transfers/show_by_account/' . $origin_account->id, 'Transfers')
				->text('Add new transfer');

		$headline = __('Add new transfer');
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
	 * Function adds transfers from one arbitrary account to another arbitrary account.
	 * 
	 * @todo set of accounts choosen by account_attribute_id, must be done by ajax
	 * @author Jiri Svitak, Tomas Dulik
	 * @param integer $origin_account
	 */
	public function add()
	{
		if (!$this->acl_check_new('Accounts_Controller', 'transfers'))
			Controller::error(ACCESS);
		
		$account_model = new Account_Model();
		
		$arr_dst_accounts = $account_model->select_some_list();
		asort($arr_dst_accounts);
		
		// array origin accounts for dropdown
		$arr_orig_accounts = $arr_dst_accounts;

		// default destination account
		$operating = ORM::factory('account')->where(
				'account_attribute_id', Account_attribute_Model::OPERATING
		)->find();
		
		// form
		$form = new Forge('transfers/add');
		
		// origin account
		$form->group('Origin account');
		
		$form->dropdown('oname')
				->label('Origin account (name, ID, type, member ID)')
				->options($arr_orig_accounts)
				->rules('required')
				->style('width:600px');
		// destination account
		$form->group('Destination account');
		
		$form->dropdown('aname')
				->label('Destination account (name, ID, type, member ID)')
				->options($arr_dst_accounts)
				->rules('required')
				->selected($operating->id)
				->style('width:600px');
		
		// other information
		$form->group('Transfer');
		
		$form->date('datetime')
				->label(__('Date and time') . ':')
				->years(date('Y') - 20, date('Y'))
				->rules('required');
		
		// no amount on origin account is required, this arbitrary transfers 
		// should only admin or accountant of association who knows what is he doing
		$form->input('amount')
				->label(__('Amount') . ':')
				->rules('required|valid_numeric')
				->callback(array($this, 'valid_amount'));
		
		$form->input('text')
				->label(__('Text') . ':')
				->rules('required');
		
		$form->submit('Send');
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				$db = new Transfer_Model();
				$db->transaction_start();
				
				Transfer_Model::insert_transfer(
						$form_data['oname'], $form_data['aname'], null, null,
						$this->session->get('user_id'), null,
						date('Y-m-d', $form_data['datetime']), date('Y-m-d H:i:s'),
						$form_data['text'], $form_data['amount']
				);
				
				$db->transaction_commit();
				status::success('Transfer has been successfully added.');
			}
			catch (Exception $e)
			{
				$db->transaction_rollback();
				Log::add_exception($e);
				status::success('Transfer hasnot been successfully added');
			}
			url::redirect('transfers/show_all');
		}
		
		$breadcrumbs = breadcrumbs::add()
				->link('transfers/show_all', 'Day book')
				->text('Add new transfer');
		
		$headline = __('Add new transfer');
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Function adds VoIP transfers.
	 * 
	 * @author Roman Sevcik
	 * @param integer $origin_account
	 */
	public function add_voip($origin_account = NULL)
	{
		if (!isset($origin_account))
			Controller::warning(PARAMETER);
		
		$account = ORM::factory('account')->where('id', $origin_account)->find();
		
		if (!Billing::instance()->has_driver() ||
			!Billing::instance()->get_account($account->member_id))
		{
			Controller::error(RECORD);
		}
		
		// transfer from specific account?
        // save for callback function valid_amount_to_send
        $this->origin = $origin_account;
        $origin_acc = new Account_Model($origin_account);

        if (!$origin_acc->id)
        {
            Controller::error(RECORD);
        }

        if (!$this->acl_check_new('Accounts_Controller', 'transfers', $origin_acc->member_id))
        {	// does the user have rights for this?
            Controller::error(ACCESS);
        }

		$arr_orig_accounts[$origin_acc->id] = $origin_acc->name . ' - '
                . __('Account ID') . ' ' . $origin_acc->id . ' - '
				. __('Member ID') . ' ' . $origin_acc->member_id;
		
		// form
		$form = new Forge('transfers/add_voip/' . $origin_account);
		
		$form->group('Transfer');
		
		$form->dropdown('oname')
				->label(__('Origin account'))
				->options($arr_orig_accounts);
		
		$form->date('datetime')
				->label(__('Date and time') . ':')
				->years(date('Y') - 20, date('Y'))
				->rules('required');
		
		$form->input('amount')
				->label(__('Amount') . ':')
				->rules('required|valid_numeric')
				->callback(array($this, 'valid_amount_to_send'));
		
		$form->submit('Send');
		
		if ($form->validate())
		{
			// default destination account
			$operating = ORM::factory('account')->where(
					'account_attribute_id', Account_attribute_Model::OPERATING
			)->find();
			
			$text = __('Recharging of VoIP credit');
			
			$form_data = $form->as_array();
			
			try
			{
				$db = new Transfer_Model();
				$db->transaction_start();
				
				Transfer_Model::insert_transfer(
						$form_data['oname'], $operating->id, null, null,
						$this->session->get('user_id'),
						Transfer_Model::DEDUCT_VOIP_UNNACCOUNTED_FEE,
						date('Y-m-d', $form_data['datetime']), date('Y-m-d H:i:s'),
						$text, $form_data['amount']
				);
				
				$db->transaction_commit();
				status::success('Transfer has been successfully added.');
			}
			catch (Exception $e)
			{
				$db->transaction_rollback();
				Log::add_exception($e);
				status::success('Transfer has not been successfully added');
			}
			url::redirect('transfers/show_by_account/' . $origin_account);
		}		if ($this->acl_check_view('Members_Controller', 'members', $account->member_id))
		{
			$links[] = html::anchor(
					'members/show/' . $account->member_id, __('Back to the member')
			);
		}
		
		$links[] = html::anchor(
				'transfers/show_by_account/' . $origin_account,
				__('Back to transfers of account')
		);
		
		$headline = __('Add new VoIP transfer');
		$info[] = __('Information') . ' : ' . __('Transfer will be effected within 15 minutes.');
		$view = new View('main');
		$view->title = $headline;
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->content->link_back = implode(' | ', $links);
		$view->content->aditional_info = $info;
		$view->render(TRUE);
	}

	/**
	 * Function edits double-entry transfers. They should not be edited.
	 * Wrong transfer should be solved by new transfer.
	 * 
	 * @author Jiri Svitak
	 * @param integer $transfer_id
	 */
	public function edit($transfer_id = NULL)
	{
		if (!isset($transfer_id))
			Controller::warning(PARAMETER);
		
		// access rights
		if (!$this->acl_check_edit('Accounts_Controller', 'transfers'))
			Controller::error(ACCESS);
		
		$transfer = new Transfer_Model($transfer_id);
		
		$form = new Forge('transfers/edit/' . $transfer_id);
		
		$form->group('Basic information');
		
		$form->input('text')
				->rules('required|length[3,50]')
				->value($transfer->text);
		
		$form->input('amount')
				->rules('required')
				->value($transfer->amount)
				->callback(array($this, 'valid_amount'));
		
		$form->submit('Edit');
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				$transfer->transaction_start();
				
				Transfer_Model::edit_transfer(
						$transfer->id, $form_data['text'], $form_data['amount']
				);
				
				$transfer->transaction_commit();
				status::success('Transfer has been successfully updated.');
				url::redirect('transfers/show/' . $transfer_id);
			}
			catch (Exception $e)
			{
				$transfer->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cant update transfer.', $e);
				url::redirect('transfers/show/' . $transfer_id);
			}
		}
		else
		{
			$headline = __('Editing of transfer');
			$view = new View('main');
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Deducts fees of all members in one month. If deduct transfer for one month and
	 * account is found, then it is ignored and skipped.
	 * 
	 * @author Jiri Svitak
	 * @return unknown_type
	 */
	public function deduct_member_fees()
	{
		// access rights
		if (!$this->acl_check_new('Accounts_Controller', 'transfers'))
			Controller::error(ACCESS);
		
		// content of dropdown for months
		for ($i = 1; $i <= 12; $i++)
			$arr_months[$i] = $i;
		
		$current_month = (int) date('n');
		
		// association
		$association = new Member_Model(Member_Model::ASSOCIATION);
		
		// content of dropdown for years
		$year_from = date('Y', strtotime($association->entrance_date));
		
		for ($i = $year_from; $i <= date('Y'); $i++)
		{
			$arr_years[$i] = $i;
		}
		
		$form = new Forge();
		
		$form->dropdown('year')
				->rules('required')
				->options($arr_years)
				->style('width:200px')
				->selected(date('Y'));
		
		$form->dropdown('month')
				->rules('required')
				->options($arr_months)
				->selected($current_month)
				->style('width:200px')
				->callback(array($this, 'valid_default_fee'));
		
		$form->submit('Deduct');
		
		// form validation
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			// preparation
			$month = $form_data['month'];
			$year = $arr_years[$form_data['year']];
			$user_id = $this->session->get('user_id');
			
			// perform deduction
			try
			{
				$count = self::worker_deduct_members_fees($month, $year, $user_id);
				
				status::success(
						'Fees have been successfully deducted, %d new transfers created.',
						TRUE, array($count)
				);
			}
			catch (Exception $e)
			{
				Log::add_exception($e);
				status::error('Error - some fees have not been deducted.', $e);
				url::redirect('transfers/show_all');
			}
			
			// end
			$this->redirect('transfers/show_all');		
		}
		else
		{
			if (Settings::get('deduct_fees_automatically_enabled'))
			{
				$m = 'Automatically deduction is enabled, this operation should be ' .
						'use only if automatical deduction has failed';
				status::mwarning($m);
			}

			$breadcrumbs = breadcrumbs::add()
					->link('transfers/show_all', 'Day book')
					->text('Deduction of member fees');

			$view = new View('main');
			$view->title = __('Deduction of member fees');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Deduction of member fees');
			$view->content->form = $form->html();		
			$view->render(TRUE);
		}
	}

	/**
	 * Function deducts entrance fees. This fee is deducted only one once to each member.
	 * It checks each member if his fee was deducted, and if it is not, then
	 * the fee is deducted.
	 * 
	 * @author Michal Kliment
	 */
	public function deduct_entrance_fees()
	{
		// access rights
		if (!$this->acl_check_new('Accounts_Controller', 'transfers'))
			Controller::error(ACCESS);
		
		// content of dropdown for months
		for ($i = 1; $i <= 12; $i++)
			$arr_months[$i] = $i;
		
		$current_month = (int) date('n');
		
		// association
		$association = new Member_Model(Member_Model::ASSOCIATION);
		
		// content of dropdown for years
		$year_from = date('Y', strtotime($association->entrance_date));
		
		for ($i = $year_from; $i <= date('Y'); $i++)
		{
			$arr_years[$i] = $i;
		}
		
		$form = new Forge();
		
		$form->dropdown('year')
				->rules('required')
				->options($arr_years)
				->style('width:200px')
				->selected(date('Y'));
		
		$form->dropdown('month')
				->rules('required')
				->options($arr_months)
				->selected($current_month)
				->style('width:200px')
				->callback(array($this, 'valid_default_fee'));
		
		$form->submit('Deduct');
		
		// form validation
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			// preparation
			$month = $form_data['month'];
			$year = $arr_years[$form_data['year']];
			$user_id = $this->session->get('user_id');
			
			// perform deduction
			try
			{
				$count = self::worker_deduct_entrance_fees($month, $year, $user_id);
				
				status::success(
						'Entrance fees have been successfully deducted, %d new transfers created.',
						TRUE, array($count)
				);
			}
			catch (Exception $e)
			{
				Log::add_exception($e);
				status::error('Error - some fees have not been deducted.', $e);
			}
			
			// end
			$this->redirect('transfers/show_all');			
		}
		else
		{
			if (Settings::get('deduct_fees_automatically_enabled'))
			{
				$m = 'Automatically deduction is enabled, this operation should be ' .
						'use only if automatical deduction has failed';
				status::mwarning($m);
			}

			$title = __('Deduction of entrance fees');

			$breadcrumbs = breadcrumbs::add()
					->link('transfers/show_all', 'Day book')
					->text($title);

			$view = new View('main');
			$view->title = $title;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = $title;
			$view->content->form = $form->html();		
			$view->render(TRUE);
		}
	}

	/**
	 * Deducts repayments of devices. Special devices, like wifi clients can be sold by association.
	 * This mechanism enables repayments of these devices in case that member has not enough money to
	 * buy it immediately.
	 * 
	 * @todo dodelat
	 * @author Jiri Svitak
	 */
	public function deduct_device_fees()
	{	
		// access rights
		if (!$this->acl_check_new('Accounts_Controller', 'transfers'))
			Controller::error(ACCESS);
		
		// content of dropdown for months
		for ($i = 1; $i <= 12; $i++)
			$arr_months[$i] = $i;
		
		$current_month = (int) date('n');
		
		// association
		$association = new Member_Model(Member_Model::ASSOCIATION);
		
		// content of dropdown for years
		$year_from = date('Y', strtotime($association->entrance_date));
		
		for ($i = $year_from; $i <= date('Y'); $i++)
		{
			$arr_years[$i] = $i;
		}
		
		$form = new Forge();
		
		$form->dropdown('year')
				->rules('required')
				->options($arr_years)
				->style('width:200px')
				->selected(date('Y'));
		
		$form->dropdown('month')
				->rules('required')
				->options($arr_months)
				->selected($current_month)
				->style('width:200px')
				->callback(array($this, 'valid_default_fee'));
		
		$form->submit('Deduct');
		
		// form validation
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			// preparation
			$month = $form_data['month'];
			$year = $arr_years[$form_data['year']];
			$user_id = $this->session->get('user_id');
			
			$deduct_day = date::get_deduct_day_to($month, $year);
			$date = date('Y-m-d', mktime(0, 0, 0, $month, $deduct_day, $year));
			
			// perform deduction
			try
			{
				$count = self::worker_deduct_devices_fees($month, $year, $user_id);
				
				status::success(
							'Fees have been successfully deducted, %d new transfers created.',
						TRUE, array($count)
				);
				
				if (Settings::get('last_deduct_device_fees') < $date)
				{
					Settings::set('last_deduct_device_fees', $date);
				}
			}
			catch (Exception $e)
			{
				Log::add_exception($e);
				status::error('Error - some fees have not been deducted.', $e);
				url::redirect('transfers/show_all');
			}
			
			// end
			$this->redirect('transfers/show_all');			
		}
		else
		{
			if (Settings::get('deduct_fees_automatically_enabled'))
			{
				$m = 'Automatically deduction is enabled, this operation should be ' .
						'use only if automatical deduction has failed';
				status::mwarning($m);
			}

			$breadcrumbs = breadcrumbs::add()
					->link('transfers/show_all', 'Day book')
					->text('Deduction of device repayments');

			$view = new View('main');
			$view->title = __('Deduction of device repayments');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Deduction of device repayments');
			$view->content->form = $form->html();		
			$view->render(TRUE);
		}
	}

	/**
	 * Function adds member fee payment by cash.
	 * 
	 * @author Jiri Svitak
	 * @param integer $member_id
	 */
	public function add_member_fee_payment_by_cash($member_id = NULL, $amount = NULL)
	{
		// bad parameter
		if (!isset($member_id))
			Controller::warning(PARAMETER);
		
		$member = new Member_Model($member_id);
		
		if ($member->id == 0)
			Controller::error(RECORD);
		
		if (!$this->acl_check_new('Accounts_Controller', 'transfers'))
			Controller::error(ACCESS);
		
		$credit = ORM::factory('account')->where(array
		(
			'member_id'				=> $member_id,
			'account_attribute_id'	=> Account_attribute_Model::CREDIT
		))->find();
		
		$accounts[$credit->id] = $credit->name;
		// check if there is fee for payment, then new amount is calculated
		$fee_model = new Fee_Model();
		$fee = $fee_model->get_by_date_type(date('Y-m-d'), 'transfer fee');
		
		if (is_object($fee) && $fee->id)
			$transfer_fee = $fee->fee;
		else
			$transfer_fee = 0;
		
		$amount = num::decimal_point((float) $amount);
		
		// form
		$form = new Forge('transfers/add_member_fee_payment_by_cash/'.$member_id);
		
		$form->group('Transfer');
		
		$form->dropdown('credit')
				->label('Destination account')
				->options($accounts)
				->style('width:200px');
		
		$form->date('datetime')
				->label('Date and time')
				->years(date('Y') - 20, date('Y'))
				->rules('required');
		
		$form->input('amount')
				->value($amount)
				->rules('required|valid_numeric')
				->help('amount_including_transfer_fee')
				->callback(array($this, 'valid_amount'));
		
		$form->input('text')
				->value(__('Member fee payment by cash'))
				->rules('required');
		
		$form->group('')
				->label(__('Transfer fee'));
		
		$form->input('transfer_fee')
				->label('Amount')
				->value($transfer_fee)
				->rules('valid_numeric')
				->callback(array($this, 'valid_fee'));
		
		$form->input('fee_text')
				->label('Text')
				->value(__('Transfer fee'));
		
		$form->submit('Add');
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$member_fees = ORM::factory('account')->where(
					'account_attribute_id', Account_attribute_Model::MEMBER_FEES
			)->find();
			
			$cash = ORM::factory('account')->where(
					'account_attribute_id', Account_attribute_Model::CASH
			)->find();
			
			$operating = ORM::factory('account')->where(
					'account_attribute_id', Account_attribute_Model::OPERATING
			)->find();
			
			$creation_datetime = date('Y-m-d H:i:s');
			
			try
			{
				$db = new Transfer_Model();
				$db->transaction_start();
				// first transfer is from member fees account to cash account
				$t1_id = Transfer_Model::insert_transfer(
						$member_fees->id, $cash->id, null, null,
						$this->session->get('user_id'), null, 
						date('Y-m-d', $form_data['datetime']),
						$creation_datetime, $form_data['text'],
						$form_data['amount']
				);
				// second transfer is from operating account
				Transfer_Model::insert_transfer(
						$operating->id, $credit->id, $t1_id, null,
						$this->session->get('user_id'), null,
						date('Y-m-d', $form_data['datetime']),
						$creation_datetime, $form_data['text'],
						$form_data['amount']
				);
				// transfer fee, if it has to be generated
				if ($form_data['transfer_fee'] > 0)
				{
					Transfer_Model::insert_transfer(
							$credit->id, $operating->id, $t1_id, null,
							$this->session->get('user_id'), null,
							date('Y-m-d', $form_data['datetime']),
							$creation_datetime, $form_data['fee_text'],
							$form_data['transfer_fee']
					);
				}
				
				$member->reactivate_messages();
				
				$db->transaction_commit();
				status::success('Transfer has been successfully added.');
			}
			catch (Exception $e)
			{
				$db->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cant add new transfer.', $e);
			}
			$this->redirect(Path::instance()->previous());
		}
		else
		{
			$account = $member->get_doubleentry_account(Account_attribute_Model::CREDIT);
			
			$account_id = ($account) ? $account->id : 0; 
			
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members')
					)->disable_translation()
					->link('members/show/' . $member->id,
							'ID ' . $member->id . ' - ' . $member->name,
							$this->acl_check_view(
									'Members_Controller', 'members',
									$member->id
							)
					)->enable_translation()
					->link('transfers/show_by_account/' . $account_id,
							'Transfers', $account_id)
					->text('Add member fee payment by cash');

			$headline = __('Add member fee payment by cash');
			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}
	
	/**
	 * Perform payment calculator
	 * 
	 * @author Michal Kliment
	 * @param type $account_id
	 * @param type $text 
	 */
	public function payment_calculator($account_id = NULL, $text = FALSE)
	{
		// bad parameter
		if (!$account_id || !is_numeric($account_id))
			Controller::warning(PARAMETER);
		
		$account = new Account_Model($account_id);

		// account doesn't exist
		if (!$account->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_view('Accounts_Controller', 'transfers', $account->member_id))
			Controller::error(ACCESS);

		$fee_model = new Fee_Model();
		$device_model = new Device_Model();
		$transfer_model = new Transfer_Model();

		$member_fee = $fee_model->get_regular_member_fee_by_member_date($account->member_id, date('Y-m-d'));
		$entrance_date = $account->member->entrance_date;
		$entrance_fee = $account->member->entrance_fee;
		$entrance_fee_payment_rate = $account->member->debt_payment_rate;
		$entrance_fee_paid = $transfer_model->count_entrance_fee_transfers_of_account($account->id);
		$entrance_fee_left = $entrance_fee - $entrance_fee_paid;
		$payments = array();
		
		$devices_fee = 0;
		$devices = $device_model->get_member_devices_with_debt_payments($account->member_id);
		foreach ($devices as $device)
		{
			// finds buy date of this device
			$buy_date = date_parse(date::get_closses_deduct_date_to($device->buy_date));
			
			$devices_fee += $device->price;

			// finds all debt payments of this device
			money::find_debt_payments(
					$payments, $buy_date['month'], $buy_date['year'],
					$device->price, $device->payment_rate
			);
		}
		$date = date_parse(date::get_closses_deduct_date_to(date('Y-m-d')));
		
		if (isset($payments[$date['year']][$date['month']]))
			$devices_fee_payment_rate = $payments[$date['year']][$date['month']];
		else
			$devices_fee_payment_rate = NULL;
		
		$devices_fee_paid = $transfer_model->sum_device_fee_transfers_of_account($account_id);
		$devices_fee_left = $devices_fee - $devices_fee_paid;
		
		$entrance_pd = date_parse(date::get_closses_deduct_date_to($entrance_date));
		
		if (!$entrance_fee_payment_rate)
			$entrance_fee_payment_rate = $entrance_fee;
		
		$entrance_fee_payments = array();
		// finds all debt payments of entrance fee
		money::find_debt_payments(
			$payments, $entrance_pd['month'], $entrance_pd['year'],
			$entrance_fee, $entrance_fee_payment_rate
		);
		
		$payments = arr::ksort($payments);
		
		$transfer_fee = $fee_model->get_transfer_fee_by_date(date('Y-m-d'));
	
		$form = new Forge(url::base().url::current());
		
		$form->dropdown('calculate')
			->options(array
			(
			    'expiration_date' => __('Payed to'),
			    'amount' => __('Amount')
			));
		
		$form->input('amount')
			->rules('valid_numeric')
			->callback(array($this, 'valid_calculator_item'))
			->maxlength('4');
		
		$form->input('expiration_date')
			->label('Payed to')
			->class('date')
			->callback(array($this, 'valid_calculator_item'));
		
		$form->submit('Calculate');
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			switch ($form_data['calculate'])
			{
				case 'expiration_date':
					
					$date = $transfer_model->get_last_transfer_datetime_of_account($account_id);
					
					if (!$date)
						$date = $entrance_date;
					
					$date = date::get_closses_deduct_date_to($date);
					
					$amount = ($account->balance + $form_data['amount'] - $transfer_fee);
					
					while (true)
					{
                        $date_arr = date_parse($date);
                        $year = $date_arr['year'];
                        $month = $date_arr['month'];
                        $day = $date_arr['day'];

						$amount -= $fee_model->get_regular_member_fee_by_member_date($account->member_id, date::create($day, $month, $year));
						
						if (isset($payments[$year][$month]))
							$amount -= $payments[$year][$month];
						
						if ($amount < 0)
							break;

                        $date = date::get_next_deduct_date_to($date);
					}
					
					if (!$text)
						status::info('Payed to %s.', TRUE, date::create($day, $month, $year));
					else
						echo date::create($day, $month, $year);
					
					break;
				
				case 'amount':
					
					$date = date::get_next_deduct_date_to($transfer_model->find_last_transfer_datetime_by_type(Transfer_Model::DEDUCT_MEMBER_FEE));
					
					$amount = ($account->balance + $entrance_fee_paid + $devices_fee_paid - $transfer_fee) * -1;
					
					$expiration_date = $form_data['expiration_date'];
					
					while($date <= $expiration_date)
					{						
						$amount += $fee_model->get_regular_member_fee_by_member_date($account->member_id, $date);
						
						$date = date::get_next_deduct_date_to($date);
					}
					
					foreach ($payments as $year => $year_payments)
					{
						foreach ($year_payments as $month => $payment)
						{
							$day = date::get_deduct_day_to($month, $year);
							
							$date = date::create($day, $month, $year);
							
							if ($date <= $expiration_date)
								$amount += $payment;
						}
					}
					
					if ($amount < 0)
						$amount = 0;
					
					if (!$text)
						status::info('Amount to pay is %s %s.', TRUE, array($amount, Settings::get('currency')));
					else
						echo num::decimal_point ($amount);
					
					break;
			}
		}
		else
		{
			$view = new View('main');
			$view->title = __('Payment calculator');
			$view->content = new View('transfers/payment_calculator');
			$view->content->account = $account;
			$view->content->form = $form;
			$view->content->member_fee = $member_fee;
			$view->content->entrance_date = $entrance_date;
			$view->content->entrance_fee = $entrance_fee;
			$view->content->entrance_fee_payment_rate = $entrance_fee_payment_rate;
			$view->content->entrance_fee_left = $entrance_fee_left;
			$view->content->devices_fee = $devices_fee;
			$view->content->devices_fee_payment_rate = $devices_fee_payment_rate;
			$view->content->devices_fee_left = $devices_fee_left;
			$view->render(TRUE);
		}
	}
	
	/**
	 * Callback function to validate form inputs from payment calulator
	 * 
	 * @author Michal Kliment
	 * @param type $input 
	 */
	public function valid_calculator_item($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
			self::error(PAGE);
		
		if ($this->input->post('calculate') != $input->name && $input->value == '')
			$input->add_error('required', __('This information is required.'));
	}

	/**
	 * Function validates amount of money to send from double-entry account.
	 * 
	 * @param object $input
	 */
	public function valid_amount_to_send($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$account_model = new Account_Model();
		
		if ($input->value <= 0)
		{
			$input->add_error('required', __('Error - amount has to be positive.'));
		}
		else if (!$this->acl_check_new('Accounts_Controller', 'transfers') &&
				$account_model->get_account_balance($this->origin) < $input->value)
		{
			$input->add_error('required', __('Error - not enough money on origin account.'));
		}
	}

	/**
	 * Function validates amount of money in editing.
	 * 
	 * @param object $input
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
	 * Function validates amount of money in editing.
	 * 
	 * @param object $input
	 */
	public function valid_fee($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if ($input->value < 0)
		{
			$input->add_error('required', __('Error - amount has to be positive.'));
		}
	}

	/**
	 * Function validates number of months in function deduct_fees.
	 * 
	 * @param object $input
	 */
	public function valid_months($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if ($input->value + $this->input->post('month') > 13)
		{
			$input->add_error('required', __(
					'It is possible to deduct fees only in one year.'
			));
		}
	}

	/**
	 * Callback function to validate default fee
	 *
	 * @author Michal Kliment
	 * @param object $input
	 */
	public function valid_default_fee($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$year = $this->input->post('year');
		$month = $this->input->post('month');
		
		$day = date::get_deduct_day_to($month, $year);
		
		$date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));

		// finds default fee
		$fee_model = new Fee_Model();
		$fee = $fee_model->get_default_fee_by_date_type($date, 'regular member fee');

		// default fee is not set for this month and year
		if (!$fee || !$fee->id)
		{
			$input->add_error('required', __(
					'For this month and year is not set default fee.'
			));
		}
	}
	
	/**
	 * Deduct member fees in given year and month if not deducted already.
	 * 
	 * @author Jiri Svitak, Ondrej Fibich
	 * @param integer $month Month of deduction
	 * @param integer $year Year of deduction
	 * @param integer $user_id Who is deducting
	 * @return integer Created transfers count
	 * @throws Exception On any error
	 */
	public static function worker_deduct_members_fees($month, $year, $user_id)
	{
		$deduct_day = date::get_deduct_day_to($month, $year);

		$created_transfers_count = 0;
		$total_amount = 0;
		$date = date('Y-m-d', mktime(0, 0, 0, $month, $deduct_day, $year));
		$creation_datetime = date('Y-m-d H:i:s');
		// finds default fee
		$fee_model = new Fee_Model();
		$fee = $fee_model->get_default_fee_by_date_type($date, 'regular member fee');

		if ($fee && $fee->id)
		{
			$default_fee = $fee->fee;
		}
		else
		{
			throw new Exception('Fees have not been set!');
		}

		$operating = ORM::factory('account')->where(
				'account_attribute_id', Account_attribute_Model::OPERATING
		)->find();

		$database = Database::instance();
		$account_model = new Account_Model();
		$accounts = $account_model->get_accounts_to_deduct($date);

		try
		{
			$db = new Transfer_Model();
			$db->transaction_start();
			// first sql for inserting transfers
			$sql_insert = "INSERT INTO transfers (origin_id, destination_id, ".
					"previous_transfer_id, member_id, user_id, type, datetime, ".
					"creation_datetime, text, amount) VALUES ";
			$values = array();
			// second sql for updating accounts
			$sql_update = "UPDATE accounts SET balance = CASE id ";
			$ids = array();
			// main cycle
			foreach ($accounts as $account)
			{
				// no deduct transfer for this date and account generated? then create one
				if ($account->transfer_id == 0)
				{
					$text = __('Deduction of member fee');
					if ($account->fee_is_set)
					{
						$amount = $account->fee;
						$text = ($account->fee_readonly) ? $text . ' - ' .
								__('' . $account->fee_name) : $text . ' - ' .
								$account->fee_name;
					}
					else
					{
						$amount = $default_fee;
					}
					// is amount bigger than zero?
					if ($amount > 0)
					{
						// insert
						$values[] = "($account->id, $operating->id, NULL, NULL, " .
								"$user_id, " . Transfer_Model::DEDUCT_MEMBER_FEE .
								", '$date', '$creation_datetime', '$text', $amount)";
						// update
						$sql_update .= "WHEN $account->id THEN " 
								. num::decimal_point($account->balance - $amount)
								. " ";
						$ids[] = $account->id;
						$total_amount += $amount;
						$created_transfers_count++;
					}
				}
			}
			
			if ($created_transfers_count > 0)
			{
				// single query for inserting transfers
				$sql_insert .= implode(",", $values);

				if (!$database->query($sql_insert))
					throw new Exception();

				// single query for updating credit account balances
				$ids_with_commas = implode(",", $ids);
				$sql_update .= "END WHERE id IN ($ids_with_commas)";

				if (!$database->query($sql_update))
					throw new Exception();

				// update also balance of operating account
				if (!$account_model->recalculate_account_balance_of_account($operating->id))
					throw new Exception();
			}
			
			$db->transaction_commit();
		}
		catch (Exception $e)
		{
			$db->transaction_rollback();
			throw $e;
		}
		
		return $created_transfers_count;
	}
	
	/**
	 * Deduct entrance fees.
	 * 
	 * @author Jiri Svitak, Ondrej Fibich
	 * @param integer $user_id Who is deducting
	 * @param integer $date Date of deduction [optional - default NOW]
	 * @return integer Created transfers count
	 * @throws Exception On any error
	 */
	public static function worker_deduct_entrance_fees($month, $year, $user_id)
	{
		$deduct_day = date::get_deduct_day_to($month, $year);
	
		$created_transfers_count = 0;
		$date = date('Y-m-d', mktime(0, 0, 0, $month, $deduct_day, $year));
		$creation_datetime = date('Y-m-d H:i:s');
		
		$infrastructure = ORM::factory('account')->where(
				'account_attribute_id', Account_attribute_Model::INFRASTRUCTURE
		)->find();
		
		$account_model = new Account_Model();
		$accounts = $account_model->get_accounts_to_deduct_entrance_fees(
			$date
		);
		
		try
		{
			$account_model->transaction_start();
			
			foreach ($accounts as $account)
			{
				Transfer_Model::insert_transfer(
						$account->id, $infrastructure->id, null, null,
						$user_id, Transfer_Model::DEDUCT_ENTRANCE_FEE, $date,
						$creation_datetime, __('Entrance fee'), $account->amount
				);
				$created_transfers_count++;
			}
			
			$account_model->transaction_commit();
			
			return $created_transfers_count;
		}
		catch (Exception $e)
		{
			$account_model->transaction_rollback();
			throw $e;
		}
	}
	
	/**
	 * Deduct device fees.
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id Who is deducting
	 * @return integer Created transfers count
	 * @throws Exception On any error
	 */
	public static function worker_deduct_devices_fees($month, $year, $user_id)
	{
		$deduct_day = date::get_deduct_day_to($month, $year);
	
		$created_transfers_count = 0;
		$date = date('Y-m-d', mktime(0, 0, 0, $month, $deduct_day, $year));
		$creation_datetime = date('Y-m-d H:i:s');
		
		$operating = ORM::factory('account')->where(
				'account_attribute_id', Account_attribute_Model::OPERATING
		)->find();
		
		$account_model = new Account_Model();
		$accounts = $account_model->get_accounts_to_deduct_device_fees(
			$date
		);
		
		try
		{
			$account_model->transaction_start();
			
			foreach ($accounts as $account)
			{
				Transfer_Model::insert_transfer(
						$account->id, $operating->id, null, null,
						$user_id, Transfer_Model::DEDUCT_DEVICE_FEE, $date,
						$creation_datetime, __('Device repayments'),
						$account->amount
				);
				$created_transfers_count++;
			}
			
			$account_model->transaction_commit();
			
			return $created_transfers_count;
		}
		catch (Exception $e)
		{
			$account_model->transaction_rollback();
			throw $e;
		}
	}

}
