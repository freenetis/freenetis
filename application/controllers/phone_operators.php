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
 * Controller performs actions over phone operators and their prefixes.
 * 
 * @package Controller
 * @author Ondřej Fibich
 */
class Phone_operators_Controller extends Controller
{
	/**
	 * Contruct checks if SMS are enabled
	 */
	public function __construct()
	{
		parent::__construct();
		
	    // sms is not enabled, quit
	    if (!Settings::get('sms_enabled'))
		{
			Controller::error(ACCESS);
		}
	}
	
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('phone_operators/show_all');
	}

	/**
	 * Shows all clouds
	 */
	public function show_all()
	{
		// access check
		if (!$this->acl_check_view('Phone_operators_Controller', 'phone_operators'))
		{
			Controller::error(ACCESS);
		}

		// model
		$phone_operator_model = new Phone_operator_Model();

		// gets data
		$query = $phone_operator_model->get_all();

		// grid
		$grid = new Grid('clouds', null, array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false
		));

		if ($this->acl_check_new('Phone_operators_Controller', 'phone_operators'))
		{
			$grid->add_new_button('phone_operators/add', __('Add new phone operator'));
		}

		$grid->field('id')
				->label('ID');
		
		$grid->field('country');
		
		$grid->field('name');
		
		$grid->field('phone_number_length')
				->label('Number length');
		
		$grid->field('prefixes');
		
		$grid->callback_field('sms_enabled')
				->label('SMS')
				->help(help::hint('sms_enabled'))
				->callback('callback::boolean');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_edit('Phone_operators_Controller', 'phone_operators'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('phone_operators/edit');
		}
		
		if ($this->acl_check_delete('Phone_operators_Controller', 'phone_operators'))
		{			
			$actions->add_action()
					->icon_action('delete')
					->url('phone_operators/delete')
					->class('delete_link');
		}
		
		// load datasource
		$grid->datasource($query);
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->text('Phone operators')
				->html();

		// main view
		$view = new View('main');
		$view->title = __('List of all phone operators');
		$view->content = new View('show_all');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->headline = __('List of all phone operators');
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Adds phone operator
	 */
	public function add()
	{
		// check access
		if (!$this->acl_check_new('Phone_operators_Controller', 'phone_operators'))
		{
			Controller::error(ACCESS);
		}
		
		// gets all countries to dropdown
		$countries = ORM::factory('country')->where('enabled', 1)->select_list('id', 'country_name');

		// form
		$form = new Forge('phone_operators/add');

		$form->group('Basic information');
		
		$form->dropdown('country_id')
				->rules('required')
				->label('Country')
				->options($countries)
				->selected(Settings::get('default_country'))
				->style('width:200px');
		
		$form->input('name')
				->rules('required');
		
		$form->input('phone_number_length')
				->help(__('Without prefixes'))
				->rules('required|valid_numeric')
				->value(6);
		
		$form->input('prefixes')
				->label('Phone prefixes')
				->help(__('Prefixes separated by semicolon'))
				->callback(array($this, 'valid_prefix'));
		
		$form->checkbox('sms_enabled')
				->label('SMS messages enabled')
				->help(help::hint('sms_enabled'))
				->value('1');
		
		$form->submit('Add');

		// validate form and save data
		if ($form->validate())
		{
			try
			{
				// model
				$phone_operator_model = new Phone_operator_Model();
				$prefix_model = new Phone_operator_prefix_Model();
				
				// start transaction
				$phone_operator_model->transaction_start();
				
				// load data
				$form_data = $form->as_array();
				
				// save phone operator
				$phone_operator_model->country_id = $form_data['country_id'];
				$phone_operator_model->name = $form_data['name'];
				$phone_operator_model->phone_number_length = $form_data['phone_number_length'];
				$phone_operator_model->sms_enabled = $form_data['sms_enabled'];
				$phone_operator_model->save_throwable();
				
				// save prefixes
				$prefixes = array_map('intval', explode(';', $form_data['prefixes']));
				
				foreach ($prefixes as $prefix)
				{
					if ($prefix > 0)
					{
						$prefix_model->clear();
						$prefix_model->phone_operator_id = $phone_operator_model->id;
						$prefix_model->prefix = $prefix;
						$prefix_model->save();
					}
				}
				
				// commit transaction
				$phone_operator_model->transaction_commit();

				// message
				status::success('Phone operator has been successfully added.');
				
				// redirection
				url::redirect('phone_operators/show_all');	
			}
			catch (Exception $e)
			{
				// roolback transaction
				$phone_operator_model->transaction_rollback();
				Log::add_exception($e);
				// message
				status::error('Error - cannot add phone operator', $e);
			}
		}
		
		// headline
		$headline = __('Add new phone operator');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('phone_operators/show_all', 'Phone operators')
				->disable_translation()
				->text($headline)
				->html();			

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->content = new View('form');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Edits phone operator
	 *
	 * @param integer $phone_operator_id 
	 */
	public function edit($phone_operator_id = NULL)
	{
		// check param
		if (!$phone_operator_id || !is_numeric($phone_operator_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_edit('Phone_operators_Controller', 'phone_operators'))
		{
			Controller::error(ACCESS);
		}

		// load model
		$phone_operator_model = new Phone_operator_Model($phone_operator_id);
		
		// check exists
		if (!$phone_operator_model->id)
		{
			Controller::error(RECORD);
		}
		
		// property for validator
		$this->_phone_operator_id = $phone_operator_model->id;
		
		// gets all countries to dropdown
		$countries = ORM::factory('country')->where('enabled', 1)->select_list('id', 'country_name');

		// form
		$form = new Forge('phone_operators/edit/' . $phone_operator_id);

		$form->group('Basic information');
		
		$form->dropdown('country_id')
				->rules('required')
				->label('Country')
				->options($countries)
				->selected($phone_operator_model->country_id)
				->style('width:200px');
		
		$form->input('name')
				->rules('required')
				->value($phone_operator_model->name);
		
		$form->input('phone_number_length')
				->help(__('Without prefixes'))
				->rules('required|valid_numeric')
				->value($phone_operator_model->phone_number_length);
		
		$form->input('prefixes')
				->label('Phone prefixes')
				->help(__('Prefixes separated by semicolon'))
				->callback(array($this, 'valid_prefix'))
				->value($phone_operator_model->get_grouped_prefixes());
		
		$form->checkbox('sms_enabled')
				->label('SMS messages enabled')
				->help(help::hint('sms_enabled'))
				->value('1')
				->checked($phone_operator_model->sms_enabled);
		
		$form->submit('Edit');

		//validate form and save data
		if ($form->validate())
		{
			try
			{
				// model
				$prefix_model = new Phone_operator_prefix_Model();
				
				// start transaction
				$phone_operator_model->transaction_start();
				
				// load data
				$form_data = $form->as_array();
				
				// save phone operator
				$phone_operator_model->country_id = $form_data['country_id'];
				$phone_operator_model->name = $form_data['name'];
				$phone_operator_model->phone_number_length = $form_data['phone_number_length'];
				$phone_operator_model->sms_enabled = $form_data['sms_enabled'];
				$phone_operator_model->save_throwable();
				
				// delete all previous prefixes
				$prefix_model->where(array
				(
					'phone_operator_id' => $phone_operator_model->id
				))->delete_all();
				
				// save prefixes
				$prefixes = array_map('intval', explode(';', $form_data['prefixes']));
				
				foreach ($prefixes as $prefix)
				{
					if ($prefix > 0)
					{
						$prefix_model->clear();
						$prefix_model->phone_operator_id = $phone_operator_model->id;
						$prefix_model->prefix = $prefix;
						$prefix_model->save();
					}
				}
				
				// commit transaction
				$phone_operator_model->transaction_commit();

				// message
				status::success('Phone operator has been successfully edited.');
				
				// redirection
				url::redirect('phone_operators/show_all');	
			}
			catch (Exception $e)
			{
				// roolback transaction
				$phone_operator_model->transaction_rollback();
				Log::add_exception($e);
				// message
				status::error('Error - cant edit phone operator', $e);
			}
		}
		
		// headline
		$headline = __('Edit phone operator');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('phone_operators/show_all', 'Phone operators')
				->disable_translation()
				->text($phone_operator_model->name)
				->enable_translation()
				->text('Edit')
				->html();

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->form = $form->html();
		$view->content->headline = $headline;
		$view->render(TRUE);
	}

	/**
	 * Deletes phone operator and his prefixes (by forein key) 
	 *
	 * @param integer $phone_operator_id 
	 */
	public function delete($phone_operator_id = NULL)
	{
		// check param
		if (!$phone_operator_id || !is_numeric($phone_operator_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_delete('Phone_operators_Controller', 'phone_operators'))
		{
			Controller::error(ACCESS);
		}

		// load model
		$phone_operator_model = new Phone_operator_Model($phone_operator_id);
		
		// check exists
		if (!$phone_operator_model->id)
		{
			Controller::error(RECORD);
		}
		
		// delete (pivot tables are deleted by forein keys)
		if ($phone_operator_model->delete())
		{
			status::success('Phone operator has been successfully deleted.');
		}
		else
		{
			status::error('Error - cant delete this phone operator.');
		}

		// redirect to show all
		url::redirect('phone_operators/show_all');
	}
	
	/**
	 * Checks if prefix form element has valid value
	 *
	 * @param object $input 
	 */
	public function valid_prefix($input = NULL)
	{
		if (empty($input) || !is_object($input))
		{
			Controller::error(PAGE);
		}
		
		$value = trim($input->value);
		
		
		if ($value)
		{
			if (!preg_match('/^(\d+;)*(\d+)?$/', $value))
			{
				$input->add_error('required', __('Wrong input.'));
			}
			else
			{
				// prefixes
				$prefixes = array_map('intval', explode(';', $value));
				
				// id
				$id = (isset($this->_phone_operator_id) ? $this->_phone_operator_id : '0');
				
				// count of duplicant
				$count = ORM::factory('phone_operator_prefix')
						->in('prefix', $prefixes)
						->where('phone_operator_id !=', $id)
						->count_all();
				
				if ($count > 0)
				{
					$input->add_error('required', __(
							'%d prefix already in database.', $count
					));
				}
			}
		}
	}

}
