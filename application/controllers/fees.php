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
			$limit_results = 200, $order_by = 'id', $order_by_direction = 'ASC')
	{
		// check if logged user have access right to view all fees
		if (!$this->acl_check_view('Settings_Controller', 'fees'))
			Controller::Error(ACCESS);

		// to-do - pagination
		// get new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		$allowed_order_type = array('id', 'type', 'fee', 'from', 'to');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		$fee_model = new Fee_Model();
		$fees = $fee_model->get_all_fees($order_by, $order_by_direction);
		$total_fees = $fee_model->count_all();

		// create grid
		$grid = new Grid('fees', '', array
		(
				'use_paginator'				=> true,
				'use_selector'				=> true,
				'current'					=> $limit_results,
				'selector_increace'			=> 200,
				'selector_min'				=> 200,
				'selector_max_multiplier'	=> 10,
				'base_url'					=> Config::get('lang') . '/fees/show_all/'
											. $limit_results . '/' . $order_by . '/' . $order_by_direction,
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
		if ($this->acl_check_new('Settings_Controller', 'fees'))
		{
			$grid->add_new_button('fees/add', __('Add new fee'));
		}

		// set grid fields
		$grid->order_field('id');
		
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
		if ($this->acl_check_edit('Settings_Controller', 'fees'))
		{
			$actions->add_conditional_action()
					->icon_action('edit')
					->condition('is_not_readonly')
					->url('fees/edit');
		}

		// check if logged user have access right to delete this enum_types
		if ($this->acl_check_delete('Settings_Controller', 'fees'))
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
	 * Adds new enum type
	 * 
	 * @author Michal Kliment
	 */
	public function add($fee_type_id = NULL)
	{
		// access control
		if (!$this->acl_check_new('Settings_Controller', 'fees'))
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

		$form = array
		(
			'name' => '',
			'fee' => 0,
			'from' => array
			(
				'day' => date('j'),
				'month' => date('n'),
				'year' => date('Y')
			),
			'to' => array
			(
				'day' => date('j'),
				'month' => date('n'),
				'year' => date('Y')
			),
			'type_id' => 1
		);

		$errors = array
			(
			'fee' => '',
			'from' => '',
			'to' => '',
			'type_id' => ''
		);


		$days = array();
		for ($i = 1; $i <= 31; $i++)
			$days[$i] = $i;

		$months = array();
		for ($i = 1; $i <= 12; $i++)
			$months[$i] = $i;

		$years = array();
		for ($i = date('Y') - 100; $i <= date('Y') + 100; $i++)
			$years[$i] = $i;


		if ($_POST)
		{
			$post = new Validation($_POST);
			$post->add_rules('fee', 'required', 'numeric');

			$post->add_rules('from', array('valid', 'date'));
			$post->add_rules('to', array('valid', 'date'));

			// new system is not restricted on overlapping intervals in global scale
			// overlapping will be checked individually with each member
			$post->add_callbacks('type_id', array($this, 'valid_interval'));
			
			if ($post->validate())
			{
				$fee = new Fee_Model();
				$fee->type_id = $post->type_id;
				$fee->name = $post->name;
				$fee->fee = $post->fee;
				$fee->from = date::round_month(
						$post->from['day'],
						$post->from['month'],
						$post->from['year']
				);
				$fee->to = date::round_month(
						$post->to['day'],
						$post->to['month'],
						$post->to['year'], TRUE
				);
				// clears form content
				unset($form_data);

				// for popup adding
				if ($this->popup)
				{
					// save fee
					$fee->save();

					$fee_name = ($fee->name != '') ? "$fee->name - " : "";
					$from = str_replace('-', '/', $fee->from);
					$to = str_replace('-', '/', $fee->to);
					$fee_name = "$fee_name$fee->fee " . __($this->settings->get('currency')) . " ($from-$to)";
				}
				else
				{
					// has fee been successfully saved?
					if ($fee->save())
					{
						status::success('Fee has been successfully added');
					}
					else
					{
						status::error('Error - can\'t add new fee.');
					}

					// classic adding
					url::redirect(url_lang::base() . 'fees/show_all');
				}
			}
			else
			{
				$form = arr::overwrite($form, $post->as_array());
				$errors = arr::overwrite($errors, $post->errors('errors'));
			}
		}
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('fees/show_all', 'Fees',
						$this->acl_check_view('Settings_Controller', 'fees'))
				->text('Add new fee');

		// view for adding translation
		$view = new View('main');
		$view->title = __('Add new fee');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('fees/add');
		$view->content->name = $form['name'];
		$view->content->fee = $form['fee'];
		$view->content->from = $form['from'];
		$view->content->to = $form['to'];
		$view->content->type_id = $form['type_id'];
		$view->content->errors = $errors;
		$view->content->days = $days;
		$view->content->months = $months;
		$view->content->years = $years;
		$view->content->types = $enum_types;
		$view->content->fee_model = isset($fee) && $fee->id ? $fee : NULL;
		$view->content->fee_name = isset($fee_name) ? $fee_name : NULL;
		$view->render(TRUE);
	}

	/**
	 * Edits fee
	 * 
	 * @author Michal Kliment
	 * @param integer $fee_id
	 */
	public function edit($fee_id = NULL)
	{
		if (!$fee_id)
			Controller::warning(PARAMETER);
		
		// access control
		if (!$this->acl_check_edit('Settings_Controller', 'fees'))
			Controller::error(ACCESS);

		$fee = new Fee_Model($fee_id);

		// item is read only
		if ($fee->readonly)
			Controller::error(READONLY);

		$enum_type_name_model = new Enum_type_name_Model();
		$enum_type_name = $enum_type_name_model->where('type_name', 'Fees types')->find();

		$enum_type_model = new Enum_type_Model();
		$enum_types = $enum_type_model->get_values($enum_type_name->id);

		$this->fee_id = $fee->id;

		$form = array
		(
			'name' => $fee->name,
			'fee' => $fee->fee,
			'from' => array
			(
				'day' => (int) substr($fee->from, 8, 2),
				'month' => (int) substr($fee->from, 5, 2),
				'year' => (int) substr($fee->from, 0, 4)
			),
			'to' => array
			(
				'day' => (int) substr($fee->to, 8, 2),
				'month' => (int) substr($fee->to, 5, 2),
				'year' => (int) substr($fee->to, 0, 4)
			),
			'type_id' => $fee->type_id
		);

		$errors = array
		(
			'fee' => '',
			'from' => '',
			'to' => '',
			'type_id' => ''
		);


		$days = array();
		for ($i = 1; $i <= 31; $i++)
			$days[$i] = $i;

		$months = array();
		for ($i = 1; $i <= 12; $i++)
			$months[$i] = $i;

		$years = array();
		for ($i = date('Y') - 100; $i <= date('Y') + 100; $i++)
			$years[$i] = $i;


		if ($_POST)
		{
			$post = new Validation($_POST);
			$post->add_rules('fee', 'required', 'numeric');

			$post->add_rules('from', array('valid', 'date'));
			$post->add_rules('to', array('valid', 'date'));

			// new system is not restricted on overlapping intervals in global scale
			// overlapping will be checked individually with each member
			$post->add_callbacks('type_id', array($this, 'valid_interval'));

			if ($post->validate())
			{
				$fee = new Fee_Model($fee_id);
				$fee->type_id = $post->type_id;
				$fee->name = $post->name;
				$fee->fee = $post->fee;
				$fee->from = date::round_month(
						$post->from['day'],
						$post->from['month'],
						$post->from['year']
				);
				$fee->to = date::round_month(
						$post->to['day'],
						$post->to['month'],
						$post->to['year']
				);
				// clears form content
				unset($form_data);
				// has fee been successfully saved?
				if ($fee->save())
				{
					status::success('Fee has been successfully updated');
					url::redirect(url_lang::base() . 'fees/show_all');
				}
				else
				{
					status::error('Error - cant edit fee.');
				}
			}
			else
			{
				$form = arr::overwrite($form, $post->as_array());
				$errors = arr::overwrite($errors, $post->errors('errors'));
			}
		}

		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('fees/show_all', 'Fees',
						$this->acl_check_view('Settings_Controller', 'fees'))
				->text($fee->name . ' (' . $fee->id . ')')
				->text('Edit fee');

		// view for adding translation
		$view = new View('main');
		$view->title = __('Edit fee');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('fees/edit');
		$view->content->fee_id = $fee_id;
		$view->content->name = $form['name'];
		$view->content->fee = $form['fee'];
		$view->content->from = $form['from'];
		$view->content->to = $form['to'];
		$view->content->type_id = $form['type_id'];
		$view->content->errors = $errors;
		$view->content->days = $days;
		$view->content->months = $months;
		$view->content->years = $years;
		$view->content->types = $enum_types;
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
		if (!$this->acl_check_delete('Settings_Controller', 'fees'))
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
	 * @param Validation $post
	 */
	public function valid_interval($post = NULL)
	{
		if (empty($post) || !is_object($post) || !($post instanceof Validation))
		{
			self::error(PAGE);
		}

		$from_date = date::round_month(
				$post->from['day'], $post->from['month'], $post->from['year']
		);
		
		$to_date = date::round_month(
				$post->to['day'], $post->to['month'], $post->to['year'], TRUE
		);

		$diff = date::diff_month($to_date, $from_date);
		
		if ($diff < -1)
		{
			$post->add_error('from', 'bigger');
			return;
		}

		if ($diff < 0)
		{
			$post->add_error('to', 'minimal');
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
