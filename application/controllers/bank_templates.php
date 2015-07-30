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
 * This controller handles work with bank templates for CSV bank listing files.
 * 
 * @author Jiri Svitak
 * @package Controller
 */
class Bank_templates_Controller extends Controller
{
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('bank_templates/show_all');
	}	
	
	/**
	 * Shows all bank templates for CSV files.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 200, $order_by = 'id', $order_by_direction = 'asc',
			$page_word = null, $page = 1)
	{
		if (!$this->acl_check_view('Accounts_Controller', 'bank_transfers'))
			Controller::Error(ACCESS);

		// get new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		// get order of grid from parameters
		$allowed_order_type = array('id', 'original_term', 'translated_term', 'lang');
		
		if (!in_array(strtolower($order_by),$allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
			
		// get data from database
		$model_templates = new Bank_template_Model();
		$total_templates = $model_templates->count_all();
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_templates)
			$sql_offset = 0;
		
		$templates = $model_templates->orderby($order_by, $order_by_direction)
				->limit($limit_results, $sql_offset)
				->find_all();
	
		$headline = __('Bank templates of CSV files');
		
		$grid = new Grid('translations', null, array
		(
				'use_paginator'				=> true,
				'use_selector'				=> true,
				'current'					=> $limit_results,
				'selector_increace'			=>  200,
				'selector_min'				=> 200,
				'selector_max_multiplier'	=> 10,
				'base_url'					=> Config::get('lang').'/bank_templates/show_all/'
											. $limit_results.'/'.$order_by.'/'.$order_by_direction,
				'uri_segment'				=> 'page',
				'total_items'				=>  $total_templates,
				'items_per_page'			=> $limit_results,
				'style'						=> 'classic',
				'order_by'					=> $order_by,
				'order_by_direction'		=> $order_by_direction,
				'limit_results'				=> $limit_results
		));
		
		$grid->add_new_button('bank_templates/add', __('Add new template'));
		
		$grid->order_field('id')
				->label(__('ID'));
		
		$grid->order_field('template_name');
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action()
				->icon_action('show')
				->url('bank_templates/show');
		
		$actions->add_action()
				->icon_action('edit')
				->url('bank_templates/edit');
		
		$grid->datasource($templates);
		
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->text('Bank templates')
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
	 * Function shows bank template of csv file.
	 * 
	 * @param integer $template_id
	 */
	public function show($template_id = null)
	{
		if (!isset($template_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$template = new Bank_template_Model($template_id);
		
		if ($template->id == 0)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_view('Accounts_Controller', 'bank_transfers'))
		{
			Controller::Error(ACCESS);
		}
		
		$headline = __('Show bank template');
		
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->link('bank_templates/show_all', 'Bank templates',
						$this->acl_check_view('Accounts_Controller', 'bank_transfers'))
				->disable_translation()
				->text($template->template_name . ' (' . $template->id . ')')
				->html();
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('bank_templates/show');
		$view->content->template = $template;
		$view->content->headline = $headline;
		$view->render(TRUE);	
	}
	
	/**
	 * Function adds new bank template for csv files.
	 */
	public function add()
	{
		if (!$this->acl_check_new('Accounts_Controller', 'bank_transfers'))
			Controller::Error(ACCESS);
		
		$form = new Forge('bank_templates/add');
		
		$form->input('template_name')
				->rules('required|length[1,50]');
		
		$form->input('item_separator')
				->rules('required|length[1,1]')
				->value(';');
		
		$form->input('string_separator')
				->rules('required|length[1,1]')
				->value('"');
		
		$form->group('Column headers');
		
		$form->input('account_name')
				->rules('required|length[1,30]');
		
		$form->input('account_number')
				->rules('required|length[1,30]');
		
		$form->input('bank_code')
				->rules('required|length[1,30]');
		
		$form->input('constant_symbol')
				->rules('required|length[1,30]');
		
		$form->input('variable_symbol')
				->rules('required|length[1,30]');
		
		$form->input('specific_symbol')
				->rules('required|length[1,30]');
		
		$form->input('counteraccount_name')
				->rules('required|length[1,30]');
		
		$form->input('counteraccount_number')
				->rules('required|length[1,30]');
		
		$form->input('counteraccount_bank_code')
				->rules('required|length[1,30]');
		
		$form->input('text')
				->rules('required|length[1,30]');
		
		$form->input('amount')
				->rules('required|length[1,30]');
		
		$form->input('expenditure_earning')
				->rules('length[1,30]');
		
		$form->input('value_for_earning')
				->rules('length[1,10]');
		
		$form->input('datetime')
				->label(__('Date and time').':')
				->rules('required|length[1,30]');
		
		$form->submit('Add');

		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$template = new Bank_template_Model();
			
			foreach($form_data as $key => $value)
			{
				$template->$key = $value;
			}
			
			if ($template->save())
			{
				status::success('Template has been successfully added.');
				url::redirect('bank_templates/show/'.$template->id);
			}
			else
			{
				status::error('Error - cant add new template.');
				url::redirect('bank_templates/show_all');
			}
		}
		else
		{
			$headline = __('Add new template');

			$breadcrumbs = breadcrumbs::add()
					->link('bank_accounts/show_all', 'Bank accounts',
							$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
					->link('bank_templates/show_all', 'Bank templates',
							$this->acl_check_view('Accounts_Controller', 'bank_transfers'))
					->disable_translation()
					->text($headline)
					->html();
			
			$view = new View('main');				
			$view->title = __('Add new template');
			$view->breadcrumbs = $breadcrumbs;
			$view->content = new View('form');
			$view->content->headline = __('Add new template');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Function edits bank template for csv files.
	 */
	public function edit($template_id = null)
	{
		if (!isset($template_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$template = new Bank_template_Model($template_id);
		
		if ($template->id == 0)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_edit('Accounts_Controller', 'bank_transfers'))
		{
			Controller::Error(ACCESS);
		}
		
		$form = new Forge('bank_templates/edit/'.$template_id);
		
		$form->group('Basic information');
		
		$form->input('template_name')
				->rules('required|length[1,50]')
				->value($template->template_name);	
		
		$form->input('item_separator')
				->rules('required|length[1,1]')
				->value($template->item_separator);
		
		$form->input('string_separator')
				->rules('required|length[1,1]')
				->value($template->string_separator);
		
		$form->group('')
				->label(__('Column headers'));
		
		$form->input('account_name')
				->rules('required|length[1,30]')
				->value($template->account_name);
		
		$form->input('account_number')
				->rules('required|length[1,30]')
				->value($template->account_number);
		
		$form->input('bank_code')
				->rules('required|length[1,30]')
				->value($template->bank_code);
		
		$form->input('constant_symbol')
				->rules('required|length[1,30]')
				->value($template->constant_symbol);
		
		$form->input('variable_symbol')
				->rules('required|length[1,30]')
				->value($template->variable_symbol);
		
		$form->input('specific_symbol')
				->rules('required|length[1,30]')
				->value($template->specific_symbol);
		
		$form->input('counteraccount_name')
				->rules('required|length[1,30]')
				->value($template->counteraccount_name);
		
		$form->input('counteraccount_number')
				->rules('required|length[1,30]')
				->value($template->counteraccount_number);
		
		$form->input('counteraccount_bank_code')
				->rules('required|length[1,30]')
				->value($template->counteraccount_bank_code);
		
		$form->input('text')
				->rules('required|length[1,30]')
				->value($template->text);
		
		$form->input('amount')
				->rules('required|length[1,30]')
				->value($template->amount);
		
		$form->input('expenditure_earning')
				->label(__('Expenditure-earning').':')
				->rules('length[1,30]')
				->value($template->expenditure_earning);
		
		$form->input('value_for_earning')
				->rules('length[1,10]')
				->value($template->value_for_earning);
		
		$form->input('datetime')
				->label(__('Date and time').':')
				->rules('required|length[1,30]')
				->value($template->datetime);
		
		$form->submit('Edit');

		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);
			
			foreach($form_data as $key => $value)
			{
				if ($key != 'string_separator')
					$template->$key = htmlspecialchars($value);
				else
					$template->$key = $value;
			}
			
			if ($template->save())
			{
				status::success('Template has been successfully updated.');
				url::redirect('bank_templates/show/'.$template->id);
			}
			else
			{
				status::error('Error - cant update template.');
				url::redirect('bank_templates/show/'.$template->id);
			}
		}
		else
		{
			$headline = __('Edit bank template');
			
			$breadcrumbs = breadcrumbs::add()
					->link('bank_accounts/show_all', 'Bank accounts',
							$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
					->link('bank_templates/show_all', 'Bank templates',
							$this->acl_check_view('Accounts_Controller', 'bank_transfers'))
					->link('bank_templates/show/' . $template_id,
							$template->template_name . ' (' . $template->id . ')')
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
	}
}
