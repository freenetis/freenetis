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
 * Controller handles fees in system.
 * Fee is payment, which can be single-shot (payed just once) or regular (payed
 * after some time interval periodicly).
 * 
 * @package	Controller
 */
class Fees_Controller extends Controller
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
	
	private $fee_id = NULL;

	/**
	 * Default function for Fees.
	 * 
	 * @author Michal Kliment
	 */
	public function index()
	{
		url::redirect(url_lang::base() . 'fees/show_all');
	}

	/**
	 * Shows all fees table.
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 */
	public function show_all(
			$limit_results = 200, $order_by = 'from',
			$order_by_direction = 'ASC', $page_word = 'page', $page = 1)
	{
		// check if logged user have access right to view all fees
		if (!$this->acl_check_view('Fees_Controller', 'fees'))
			Controller::Error(ACCESS);

		// to-do - pagination
		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');

		$allowed_order_type = array('id', 'type', 'name', 'fee', 'from', 'to');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'from';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		$fee_model = new Fee_Model();
		
		$total_fees = $fee_model->count_all();
		
		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_fees)
			$sql_offset = 0;
		
		$fees = $fee_model->get_all_fees(
			$sql_offset, $limit_results, $order_by, $order_by_direction
		);
		
		// path to form
		$path = Config::get('lang') . '/fees/show_all/' . $limit_results . '/'
				. $order_by . '/' . $order_by_direction.'/'.$page_word.'/'
				. $page;

		// create grid
		$grid = new Grid('fees', '', array
		(
				'use_paginator'				=> true,
				'use_selector'				=> true,
				'current'					=> $limit_results,
				'selector_increace'			=> 200,
				'selector_min'				=> 200,
				'selector_max_multiplier'	=> 10,
				'base_url'					=> $path,
				'uri_segment'				=> 'page',
				'total_items'				=> $total_fees,
				'items_per_page'			=> $limit_results,
				'style'						=> 'classic',
				'order_by'					=> $order_by,
				'order_by_direction'		=> $order_by_direction,
				'limit_results'				=> $limit_results
		));

		// add button for new translation
		// check if logged user have access right to add new translation
		if ($this->acl_check_new('Fees_Controller', 'fees'))
		{
			$grid->add_new_button('fees/add', __('Add new fee'));
		}

		// set grid fields
		$grid->order_field('id')
			->label('ID');
		
		$grid->order_field('type');
		
		$grid->order_callback_field('name')
				->callback(array($this, 'name_field'));
		
		$grid->order_field('fee');
		
		$grid->order_field('from')
				->label(__('Date from'));
		
		$grid->order_field('to')
				->label(__('Date to'));
		
		$actions = $grid->grouped_action_field();

		// check if logged user have access right to edit this enum types
		if ($this->acl_check_edit('Fees_Controller', 'fees'))
		{
			$actions->add_conditional_action()
					->icon_action('edit')
					->condition('is_not_readonly')
					->url('fees/edit');
		}

		// check if logged user have access right to delete this enum_types
		if ($this->acl_check_delete('Fees_Controller', 'fees'))
		{
			$actions->add_conditional_action()
					->icon_action('delete')
					->condition('is_not_readonly')
					->url('fees/delete')
					->class('delete_link');
		}

		$grid->datasource($fees);

		// create view for this template
		$view = new View('main');
		$view->title = __('Fees');
		$view->breadcrumbs = __('Fees');
		$view->content = new View('show_all');
		$view->content->table = $grid;
		$view->content->headline = __('Fees');
		$view->render(TRUE);
	}

	/**
	 * Adds new fee
	 * 
	 * @author David Raška
	 */
	public function add($fee_type_id = NULL)
	{
		// access control
		if (!$this->acl_check_new('Fees_Controller', 'fees'))
			Controller::error(ACCESS);
		
		if ($fee_type_id && is_numeric($fee_type_id))
		{
			$enum_type_model = new Enum_type_Model();
			$fee_type = $enum_type_model->where(array
			(
				'id'		=> $fee_type_id,
				'type_id'	=> Enum_type_Model::FEE_TYPE_ID)
			)->find();

			if (!$fee_type || !$fee_type->id)
				Controller::error(RECORD);

			$enum_types = array($fee_type->id => $enum_type_model->get_value($fee_type->id));
		}
		else
		{
			$enum_type_name_model = new Enum_type_name_Model();
			$enum_type_name = $enum_type_name_model->where('type_name', 'Fees types')->find();

			$enum_type_model = new Enum_type_Model();
			$enum_types = $enum_type_model->get_values($enum_type_name->id);
		}
		
		$form = new Forge();
		
		$form->dropdown('type_id')
			->label('Type')
			->options($enum_types);
		
		$form->input('name')
			->label('Tariff name');
		
		$form->input('fee')
			->rules('valid_numeric')
			->value(0);
		
		$form->date('from')
			->rules('required')
			->callback(array($this, 'valid_interval'))
			->label('Date from');
		
		$form->date('to')
			->rules('required')
			->label('Date to');
		
		$form->submit('Add');
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$fee = new Fee_Model();
			
			try
			{
				$fee->transaction_start();
				
				$fee->type_id = $form_data['type_id'];
				$fee->name = $form_data['name'];
				$fee->fee = $form_data['fee'];
				$fee->from = date("Y-m-d", $form_data['from']);
				$fee->to = date("Y-m-d", $form_data['to']);
				
				$fee->save_throwable();
				
				$fee->transaction_commit();
				
				// for popup adding
				if ($this->popup)
				{
					$this->redirect('fees/show_all', $fee->id);
				}
				else
				{
					status::success('Fee has been successfully added');
					
					$this->redirect('fees/show_all');
				}
			}
			catch (Exception $e)
			{
				$fee->transaction_rollback();
				status::error('Error - can\'t add new fee.');
				Log::add_exception($e);
				
				$this->redirect('fees/show_all');
			}
		}
		
		$headline = __('Add new fee');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('fees/show_all', 'Fees',
						$this->acl_check_view('Fees_Controller', 'fees'))
				->text('Add new fee');

		// view for adding translation
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('fees/add');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Edits fee
	 * 
	 * @author David Raška
	 * @param integer $fee_id
	 */
	public function edit($fee_id = NULL)
	{
		if (!$fee_id)
			Controller::warning(PARAMETER);
		
		// access control
		if (!$this->acl_check_edit('Fees_Controller', 'fees'))
			Controller::error(ACCESS);

		$fee = new Fee_Model($fee_id);

		// item is read only
		if ($fee->readonly)
			Controller::error(READONLY);

		$enum_type_name_model = new Enum_type_name_Model();
		$enum_type_name = $enum_type_name_model->where('type_name', 'Fees types')->find();

		$enum_type_model = new Enum_type_Model();
		$enum_types = $enum_type_model->get_values($enum_type_name->id);

		$form = new Forge();
		
		$form->dropdown('type_id')
			->label('Type')
			->options($enum_types)
			->selected($fee->type_id);
		
		$form->input('name')
			->label('Tariff name')
			->value($fee->name);
		
		$form->input('fee')
			->rules('valid_numeric')
			->value($fee->fee);
		
		$form->date('from')
			->rules('required')
			->callback(array($this, 'valid_interval'))
			->label('Date from')
			->value(strtotime($fee->from));
		
		$form->date('to')
			->rules('required')
			->label('Date to')
			->value(strtotime($fee->to));
		
		$form->submit('Edit');

		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$fee = new Fee_Model($fee_id);
			
			try
			{
				$fee->transaction_start();
				
				$fee->type_id = $form_data['type_id'];
				$fee->name = $form_data['name'];
				$fee->fee = $form_data['fee'];
				$fee->from = date("Y-m-d", $form_data['from']);
				$fee->to = date("Y-m-d", $form_data['to']);
				
				$fee->save_throwable();
				
				$fee->transaction_commit();
				
				status::success('Fee has been successfully updated.');

				$this->redirect('fees/show_all');
			}
			catch (Exception $e)
			{
				$fee->transaction_rollback();
				status::error('Error - cant edit fee.');
				Log::add_exception($e);
				
				if (!$this->popup)
				{
					$this->redirect('fees/show_all');
				}
			}
		}
		
		$headline = __('Edit fee');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('fees/show_all', 'Fees',
						$this->acl_check_view('Fees_Controller', 'fees'))
				->text($fee->name . ' (' . $fee->id . ')')
				->text('Edit fee');

		// view for adding translation
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('fees/add');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Deletes fee
	 * 
	 * @author Michal Kliment
	 * @param ineteger $fee_id
	 */
	public function delete($fee_id = NULL)
	{
		// wrong parameter
		if (!$fee_id || !is_numeric($fee_id))
			Controller::warning(PARAMETER);

		// access control
		if (!$this->acl_check_delete('Fees_Controller', 'fees'))
			Controller::error(ACCESS);

		$fee = new Fee_Model($fee_id);

		// fee doesn't exist
		if (!$fee->id)
			Controller::error(RECORD);

		// item is read only
		if ($fee->readonly)
			Controller::error(READONLY);

		// fee is used on some tariffs
		if ($fee->members_fees->count())
		{
			status::warning('Fee is used in some tariffs');
			url::redirect(url_lang::base() . 'fees/show_all');
		}

		// success
		if ($fee->delete())
		{
			status::success('Fee has been successfully deleted');
		}

		url::redirect(url_lang::base() . 'fees/show_all');
	}

	/**
	 * Checks overlapping of fee validity intervals.
	 * 
	 * @param Input $input
	 */
	public function valid_interval($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$arr_from = date_parse_from_format(DateTime::ISO8601, $this->input->post('from'));
		$arr_to = date_parse_from_format(DateTime::ISO8601, $this->input->post('to'));
		
		$date_from = date::round_month($arr_from['day'], $arr_from['month'], $arr_from['year']);
		$date_to = date::round_month($arr_to['day'], $arr_to['month'], $arr_to['year']);
		
		$diff = date::diff_month($date_to, $date_from);
		
		if ($diff < 0)
		{
			$input->add_error('required', __('Date from must be smaller then date to.'));
			return;
		}
		
		if ($diff == 0)
		{
			$input->add_error('required', __('Minimal duration is one month.'));
			return;
		}
	}

	/**
	 * Callback function to show name of fee, in read-only fee show translated name
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string  $name
	 */
	protected static function name_field($item, $name)
	{
		// fee is read-only
		if ($item->readonly)
		// show trasnslated name
			echo __('' . $item->name);
		else
		// show name
			echo $item->name;
	}

}
