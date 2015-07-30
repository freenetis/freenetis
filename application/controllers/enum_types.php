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
 * Controller handles enum types.
 * Enum type is used as enumeration for different tables in database.
 * Enum types are grouped to logical groups by enum type names.
 * 
 * @package Controller
 */
class Enum_types_Controller extends Controller
{

	/**
	 * Index redirects to show all
	 * 
	 * @author Michal Kliment
	 */
	public function index()
	{
		url::redirect('enum_types/show_all');
	}

	/**
	 * Shows all enum_types table.
	 * 
	 * @author Michal Kliment
	 * @param  integer $limit_results
	 * @param  string $order_by
	 * @param  string $order_by_direction
	 */
	public function show_all(
			$limit_results = 200, $order_by = 'id', $order_by_direction = 'ASC')
	{
		// check if logged user have access right to view all translations
		if (!$this->acl_check_view('Settings_Controller', 'enum_types'))
			Controller::Error(ACCESS);

		// to-do - pagination
		// get new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		$allowed_order_type = array('id', 'type', 'value');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		$enum_type_model = new Enum_type_Model();
		$enum_types = $enum_type_model->get_all($order_by, $order_by_direction);
		$total_enum_types = count($enum_types);
		
		$headline = __('Enumerations');

		// create grid
		$grid = new Grid('enum_types', $headline, array
		(
				'use_paginator'				=> true,
				'use_selector'				=> true,
				'current'					=> $limit_results,
				'selector_increace'			=> 200,
				'selector_min'				=> 200,
				'selector_max_multiplier'	=> 10,
				'base_url'					=> Config::get('lang') . '/translations/show_all/'
											. $limit_results . '/' . $order_by . '/' . $order_by_direction,
				'uri_segment'				=> 'page',
				'total_items'				=> $total_enum_types,
				'items_per_page'			=> $limit_results,
				'style'						=> 'classic',
				'order_by'					=> $order_by,
				'order_by_direction'		=> $order_by_direction,
				'limit_results'				=> $limit_results
		));

		// add button for new translation
		// check if logged user have access right to add new translation
		if ($this->acl_check_new('Settings_Controller', 'enum_types'))
		{
			$grid->add_new_button('enum_types/add', __('Add new enum type'));
		}

		// set grid fields
		$grid->order_field('id');
		
		$grid->order_field('type');
		
		$grid->order_field('value');
		
		$actions = $grid->grouped_action_field();

		// check if logged user have access right to edit this enum types
		if ($this->acl_check_edit('Settings_Controller', 'enum_types'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('enum_types/edit');
		}

		// check if logged user have access right to delete this enum_types
		if ($this->acl_check_delete('Settings_Controller', 'enum_types'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('enum_types/delete')
					->class('delete_link');
		}

		$grid->datasource($enum_types);

		// create view for this template
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $headline;
		$view->content = new View('show_all');
		$view->content->table = $grid;
		$view->content->headline = '';
		$view->render(TRUE);
	}

	/**
	 * Adds new enum type
	 * 
	 * @author Michal Kliment
	 */
	public function add()
	{
		// access control
		if (!$this->acl_check_new('Settings_Controller', 'enum_types'))
			Controller::error(ACCESS);

		$arr_type_names = array
		(
			NULL => '----- ' . __('select type') . ' -----'
		) + ORM::factory('enum_type_name')->select_list('id', 'type_name');

		// form for new enum type
		$form = new Forge('enum_types/add');
		
		$form->dropdown('type_name_id')
				->label('Type')
				->options($arr_type_names)
				->rules('required')
				->style('width:200px');
		
		$form->input('value')
				->rules('required|length[3,254]');
		
		$form->submit('Add');

		// test validity of input, if it is validate it will continue in show_all
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			// assigns new enum type to model
			$enum_type = new Enum_type_Model();
			$enum_type->type_id = $form_data['type_name_id'];
			$enum_type->value = $form_data['value'];
			$enum_type->read_only = 0;
			
			// clears form content
			unset($form_data);
			
			// has translation been successfully saved?
			if ($enum_type->save())
			{
				status::success('Enum type has been successfully added');
				url::redirect('enum_types/show_all');
			}
			else
			{
				status::success('Error - can\'t add new enum type.');
			}
		}
		else
		{
			// breadcrumbs
			$breadcrumbs = breadcrumbs::add()
					->link('enum_types/show_all', 'Enumerations',
							$this->acl_check_view('Settings_Controller', 'enum_types'))
					->text('Add new enum type');

			// view for adding translation
			$view = new View('main');
			$view->title = __('Add new enum type');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Add new enum type');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Edits enum type
	 * 
	 * @author Michal Kliment
	 * @param integer $enum_type_id
	 */
	public function edit($enum_type_id = NULL)
	{

		if ($enum_type_id)
		{
			// access control
			if (!$this->acl_check_edit('Settings_Controller', 'enum_types'))
				Controller::error(ACCESS);

			$enum_type = new Enum_type_Model($enum_type_id);

			if (!$enum_type->id)
				url::redirect('enum_types/show_all');

			$arr_type_names = ORM::factory('enum_type_name')->select_list('id', 'type_name');

			// form for new enum type
			$form = new Forge('enum_types/edit/' . $enum_type->id);
			
			$form->dropdown('type_name_id')
					->label('Type')
					->options($arr_type_names)
					->rules('required')
					->selected($enum_type->type_id)
					->style('width:200px');
			
			$form->input('value')
					->rules('required|length[3,254]')
					->value($enum_type->value);
			
			$form->submit('Edit');

			// test validity of input, if it is validate it will continue in show_all
			if ($form->validate())
			{
				$form_data = $form->as_array();
				
				// assigns new enum type to model
				$enum_type = new Enum_type_Model($enum_type_id);
				$enum_type->type_id = $form_data['type_name_id'];
				$enum_type->value = $form_data['value'];
				$enum_type->read_only = 0;
				// clears form content
				unset($form_data);
				
				// has translation been successfully saved?
				if ($enum_type->save())
				{
					status::success('Enum type has been successfully updated');
					url::redirect('enum_types/show_all');
				}
				else
				{
					status::error('Error - can\'t edit enum type.');
				}
			}
			else
			{
				// breadcrumbs
				$breadcrumbs = breadcrumbs::add()
						->link('enum_types/show_all', 'Enumerations',
								$this->acl_check_view('Settings_Controller', 'enum_types'))
						->text($enum_type->value . ' (' . $enum_type_id . ')')
						->text('Edit translation');

				// view
				$view = new View('main');
				$view->title = __('Edit translation');
				$view->breadcrumbs = $breadcrumbs->html();
				$view->content = new View('form');
				$view->content->headline = __('Edit translation');
				$view->content->form = $form->html();
				$view->render(TRUE);
			}
		}
		else
		{
			Controller::warning(PARAMETER);
		}
	}

	/**
	 * Deletes enum type
	 * 
	 * @author Michal Kliment
	 * @param integer $enum_type_id
	 */
	public function delete($enum_type_id = NULL)
	{
		if ($enum_type_id)
		{
			// access control
			if (!$this->acl_check_delete('Settings_Controller', 'enum_types'))
				Controller::error(ACCESS);

			$enum_type = new Enum_type_Model($enum_type_id);

			if (!$enum_type->id)
				url::redirect('enum_types/show_all');

			// success
			if ($enum_type->delete())
			{
				status::success('Enum type has been successfully deleted');
				url::redirect('enum_types/show_all');
			}
		}
		else
		{
			Controller::warning(PARAMETER);
		}
	}

}
