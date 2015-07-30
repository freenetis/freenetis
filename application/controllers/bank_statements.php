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
 * Handles all bank statements operations.
 * 
 * @author Jiri Svitak
 * @package Controller
 */
class Bank_statements_Controller extends Controller
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
	 * Function imported bank statements by bank account.
	 * 
	 * @author Jiri Svitak
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page
	 */
	public function show_by_bank_account(
			$bank_account_id = NULL, $limit_results = 500, $order_by = 'id',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{
		// param check
		if (!isset($bank_account_id) || !is_numeric($bank_account_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// access rights
		if (!$this->acl_check_view('Accounts_Controller', 'bank_statements'))
		{
			Controller::error(ACCESS);
		}
		
		// model
		$ba = new Bank_account_Model($bank_account_id);
		
		// record
		if (!$ba->id)
		{
			Controller::error(RECORD);
		}
			
		if (is_numeric($this->input->post('record_per_page')))
		{
			$limit_results = (int) $this->input->post('record_per_page');
		}
		
		$allowed_order_type = array(
				'id', 'from', 'to', 'type',
				'opening_balance', 'closing_balance'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
		{
			$order_by = 'id';
		}
		
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'desc';
		}

		// model
		$bs_model = new Bank_statement_Model();	
		
		$total_bank_statements = $bs_model->count_bank_statements($bank_account_id);
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_bank_statements)
			$sql_offset = 0;
		
		$bank_statements = $bs_model->get_bank_statements(
				$bank_account_id, $sql_offset, (int)$limit_results,
				$order_by, $order_by_direction
		);
		
		$headline = __('Bank statements');
		
		$grid = new Grid('bank_statements', null, array
		(
				'current'	    		=> $limit_results,
				'selector_increace'    	=> 500,
				'selector_min' 			=> 500,
				'selector_max_multiplier' => 10,
				'base_url'    			=> Config::get('lang').'/bank_statements/show_by_bank_account/'
										. $bank_account_id.'/'.$limit_results.'/'.$order_by.'/'.$order_by_direction ,
				'uri_segment'    		=> 'page',
				'total_items'    		=> $total_bank_statements,
				'items_per_page' 		=> $limit_results,
				'style'          		=> 'classic',
				'order_by'				=> $order_by,
				'order_by_direction'	=> $order_by_direction,
				'limit_results'			=> $limit_results,
				'variables'				=>	$bank_account_id.'/',
				'url_array_ofset'		=> 1,
		));
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_field('statement_number')
				->label('Number');
		
		$grid->order_field('from')
				->label('Date from');
		
		$grid->order_field('to')
				->label('Date to');
		
		$grid->order_field('type');
		
		$grid->order_callback_field('opening_balance')
				->callback('money');
		
		$grid->order_callback_field('closing_balance')
				->callback('money');

		if ($this->acl_check_new('Accounts_Controller', 'bank_transfers'))
		{
			$grid->add_new_button(
					'import/upload_bank_file/'.$bank_account_id,
					__('Upload bank transfers listing')
			);
		}

		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Accounts_Controller', 'bank_transfers'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('bank_transfers/show_by_bank_statement');
		}
		
		if ($this->acl_check_edit('Accounts_Controller', 'bank_statements'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('bank_statements/edit');
		}

		if ($this->acl_check_delete('Accounts_Controller', 'bank_statements'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('bank_statements/delete')
					->class('delete_link');
		}
		
		$grid->datasource($bank_statements);
		
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->disable_translation()
				->link('bank_transfers/show_by_bank_account/'.$bank_account_id,
						$ba->name . ' (' . $ba->id . ')',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->text($headline)
				->html();
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	}
	
	
	/**
	 * Edits bank statement.
	 * 
	 * @param integer $bank_statement_id
	 */
	public function edit($bank_statement_id = null)
	{
		if (!isset($bank_statement_id) || !is_numeric($bank_statement_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$bs = new Bank_statement_Model($bank_statement_id);
		
		if (!$bs->id)
		{
			Controller::warning(PARAMETER);
		}
		
		if (!$this->acl_check_edit('Accounts_Controller', 'bank_statements'))
		{
			Controller::error(ACCESS);
		}
		
		// form
		$form = new Forge('bank_statements/edit/'.$bank_statement_id);
		
		$form->group('Basic data');
		
		$form->input('statement_number')
				->value($bs->statement_number);
		
		$form->date('from')
				->label('Date from')
				->years(date('Y')-100, date('Y'))
				->rules('required')
				->value(strtotime($bs->from));
		
		$form->date('to')
				->label('Date to')
				->years(date('Y')-100, date('Y'))
				->rules('required')
				->value(strtotime($bs->to));
		
		$form->input('opening_balance')
				->value($bs->opening_balance);
		
		$form->input('closing_balance')
				->value($bs->closing_balance);
		
		$form->submit('Edit');
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$bs->statement_number = $form_data['statement_number'];
			$bs->opening_balance = $form_data['opening_balance'];
			$bs->closing_balance = $form_data['closing_balance'];
			$bs->from = date('Y-m-d', $form_data['from']);
			$bs->to = date('Y-m-d', $form_data['to']);
			
			unset($form_data);
			
			$bs_saved = $bs->save();
		
			if ($bs_saved)
			{
				status::success('Bank statement has been successfully updated.');
	 			url::redirect('bank_statements/show_by_bank_account/'.$bs->bank_account_id); 			
			}
		}
		
		$headline = __('Editing of bank statement');		
		
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->disable_translation()
				->link('bank_transfers/show_by_bank_account/'.$bs->bank_account->id,
						$bs->bank_account->name . ' (' . $bs->bank_account->id . ')',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->text($bs->statement_number . ' (' . $bs->id . ')')
				->text($headline)
				->html();
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->form = $form->html();
		$view->content->headline = $headline;
		$view->render(TRUE);		
	}
	
	/**
	 * Deletes bank statement including all transfers created during import.
	 * 
	 * @author Jiri Svitak
	 * @param integer $bank_statement_id
	 */
	public function delete($bank_statement_id = null)
	{
		if (!isset($bank_statement_id))
			Controller::error(PARAMETER);
		
		$statement = new Bank_statement_Model($bank_statement_id);
		
		// access rights
		if (!$this->acl_check_delete('Accounts_Controller', 'bank_statements'))
			Controller::error(ACCESS);
		
		$bts = $statement->bank_transfers;

		$dtids = array();
		$itids = array();
		$btids = array();
		
		foreach($bts as $bank_transfer)
		{
			$bt = new Bank_transfer_Model($bank_transfer->id);
			$transfer = new Transfer_Model($bt->transfer_id);
			$dependent = $transfer->get_dependent_transfers($transfer->id);
			
			foreach($dependent as $d)
			{
				$dtids[] = $d->id;
			}
			
			$itids[] = $transfer->id;
			$bt_model = new Bank_transfer_Model($bt->id);
			$btids[] = $bt->id;
		}
		
		$itids = array_unique($itids);
		$dtids = array_diff($dtids, $itids);
		$btids = array_unique($btids);
		
		try
		{
			$db = new Transfer_Model();
			$db->transaction_start();
			
			// delete dependent transfers
			foreach($dtids as $dtid)
			{
				Transfer_Model::delete_transfer($dtid);
			}
			
			foreach($btids as $btid)
			{
				$btm = new Bank_transfer_Model($btid);
				$btm->delete_throwable();
			}
			
			// delete independent transfers
			foreach($itids as $itid)
			{
				Transfer_Model::delete_transfer($itid);
			}
			
			$ba_id = $statement->bank_account_id;
			$statement->delete_throwable();
			$db->transaction_commit();
			
			status::success('Bank statement (including %d transfers and %d bank transfers) has been successfully deleted.',
					true, array(0 => (count($itids) + count($dtids)), 1 => count($btids))
			);
		}
		catch (Exception $e)
		{
			$db->transaction_rollback();
			Log::add_exception($e);
			status::error('Error - cannot delete bank statement.', $e);
		}
		url::redirect('bank_statements/show_by_bank_account/' . $ba_id);
	}
	
}
