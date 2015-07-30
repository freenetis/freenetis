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
 * Handles approval templates which define name of approval.
 *
 * @author	Michal Kliment
 * @package Controller
 */
class Approval_templates_Controller extends Controller
{
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('approval_templates/show_all');
	}

	/**
	 * Function to show all approval templates
	 * 
	 * @author Michal Kliment
	 * @param number $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param number $page
	 */
	public function show_all(
			$limit_results = 100, $order_by = 'id', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		//access control
		if (!$this->acl_check_view('approval', 'templates'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		$approval_template_model = new Approval_template_Model();
		$total_approval_templates = $approval_template_model->count_all();

		if (($sql_offset = ($page - 1) * $limit_results) > $total_approval_templates)
			$sql_offset = 0;

		$approval_templates = $approval_template_model->get_all_approval_templates(
				$sql_offset, (int)$limit_results, $order_by, $order_by_direction
		);

		// create grid
		$grid = new Grid('approval_templates/show_all', __('List of all approval templates'), array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 100,
			'selector_min'				=> 100,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/approval_templates/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_approval_templates,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
		));

		$grid->add_new_button('approval_templates/add', __('Add new approval template'));

		$grid->order_field('id');
		
		$grid->order_field('name');
		
		$grid->order_callback_field('comment')
				->callback('callback::limited_text', 100);
		
		$grid->order_field('types_count');
		
		$grid->grouped_action_field()
				->add_action()
				->icon_action('show')
				->url('approval_templates/show')
				->label('Show approval template');

		$grid->datasource($approval_templates);
		
		// view
		$view = new View('main');
		$view->breadcrumbs = __('Approval templates');
		$view->title = __('Approval');
		$view->content = new View('approval/show_all');
		$view->content->grid = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function to show approval template
	 * 
	 * @author Michal Kliment
	 * @param number $approval_template_id
	 */
	public function show($approval_template_id = NULL)
	{
		// access control
		if (!$this->acl_check_view('approval', 'templates'))
			Controller::error('ACCESS');

		// bad parameter
		if (!$approval_template_id || !is_numeric($approval_template_id))
			Controller::warning(PARAMETER);

		$this->approval_template = new Approval_template_Model($approval_template_id);

		// record doesn't exist
		if (!$this->approval_template->id)
			Controller::error(RECORD);

		$ati = new Approval_template_item_Model();
		
		$approval_template_items = $ati->get_all_items_by_template_id(
				$this->approval_template->id
		);

		// create grid
		$items_grid = new Grid('approval_templates/show/'.$approval_template_id, '', array
		(
			'use_paginator'	=> false,
			'use_selector'	=> false,
			'total_items'	=> count ($approval_template_items)
		));

		// access control
		if ($this->approval_template->state < 2 &&
			$this->acl_check_new('approval', 'templates'))
		{
			$items_grid->add_new_button(
					'approval_template_items/add/'.$this->approval_template->id,
					__('Add new approval template item')
			);
		}
			
		$items_grid->field('id');
		
		$items_grid->link_field('id')
				->link('approval_types/show', 'name');
		
		$items_grid->callback_field('group_id')
				->label(__('Group'))
				->callback('Approval_types_Controller::group_field');
		
		$items_grid->callback_field('type')
				->callback('Approval_types_Controller::type_field');
		
		$items_grid->callback_field('interval')
				->callback('Approval_types_Controller::interval_field');
		
		$items_grid->callback_field('min_suggest_amount')
				->label(__('Minimal suggest amount'))
				->callback('Approval_types_Controller::min_suggest_amount_field');
		
		$items_grid->callback_field('priority')
				->callback('Approval_templates_Controller::priority_field')
				->help(help::hint('approval_priority'));

		$actions = $items_grid->grouped_action_field();
		
		// access control
		if ($this->approval_template->state < 2 &&
			$this->acl_check_edit('approval', 'templates'))
		{
			$actions->add_action('item_id')
					->icon_action('edit')
					->url('approval_template_items/edit');
		}
		
		// access control
		if ($this->approval_template->state < 2 &&
			$this->acl_check_delete('approval', 'templates'))
		{
			$actions->add_action('item_id')
					->icon_action('delete')
					->url('approval_template_items/delete')
					->class('delete_link');
		}
		
		$items_grid->datasource($approval_template_items);

		// breadcrums
		$breadcrumbs = breadcrumbs::add()
				->link('approval_templates/show_all', 'Approval templates',
						$this->acl_check_view('approval', 'templates'))
				->disable_translation()
				->text($this->approval_template->name . ' (' . $this->approval_template->id . ')')
				->html();
		
		// view
		$view = new View('main');
		$view->title = __('Show approval template');
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('approval/templates_show');
		$view->content->approval_template = $this->approval_template;
		$view->content->items_grid = $items_grid;
		$view->render(TRUE);
	}

	/**
	 * Function to add new approval template
	 * 
	 * @author Michal Kliment
	 */
	public function add()
	{
		// access control
		if (!$this->acl_check_new('approval', 'templates'))
			Controller::error(ACCESS);

		// creates form
		$form = new Forge('approval_templates/add');

		$form->group('Basic information');
		
		$form->input('name')
				->rules('required|length[0,100]');
		
		$form->textarea('comment')
				->rules('length[0,65535]');

		$form->submit('Save');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$at = new Approval_template_Model();
			$at->name = $form_data['name'];
			$at->comment = $form_data['comment'];
			$at->save();

			status::success('Approval template has been successfully added.');
			url::redirect('approval_templates/show/'.$at->id);
		}

		// headline
		$headline = __('Add new approval template');
		// breadcrums
		$breadcrumbs = breadcrumbs::add()
				->link('approval_templates/show_all', 'Approval templates',
						$this->acl_check_view('approval', 'templates'))
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

	/**
	 * Function to edit approval template
	 * 
	 * @author Michal Kliment
	 * @param number $approval_template_id
	 */
	public function edit($approval_template_id = NULL)
        {
		// access control
		if (!$this->acl_check_edit('approval', 'templates'))
			Controller::error(ACCESS);

		// bad parameter
		if (!$approval_template_id || !is_numeric($approval_template_id))
			Controller::warning(PARAMETER);

		$at = new Approval_template_Model($approval_template_id);

		// record doesn't exist
		if (!$at->id)
			Controller::error(RECORD);

		// it is not possible edit used template
		if ($at->state > 1)
		{
			status::success('It is not possible edit used template.');
			url::redirect('approval_templates/show/'.$at->id);
		}

		// creates form
		$form = new Forge('approval_templates/edit/'.$at->id);

		$form->group('Basic information');
		
		$form->input('name')
				->rules('required|length[0,100]')
				->value($at->name);
		
		$form->textarea('comment')
				->rules('length[0,65535]')
				->value($at->comment);

		$form->submit('Save');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$at->name = $form_data['name'];
			$at->comment = $form_data['comment'];
			$at->save();

			status::success('Approval template has been successfully updated.');
			url::redirect('approval_templates/show/'.$at->id);
		}
		
		// breadcrums
		$breadcrumbs = breadcrumbs::add()
				->link('approval_templates/show_all', 'Approval templates',
						$this->acl_check_view('approval', 'templates'))
				->disable_translation()
				->text($at->name . ' (' . $at->id . ')')
				->html();

		// view
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs;
		$view->title = __('Edit approval template');
		$view->content = new View('form');
		$view->content->headline = __('Edit approval template');
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Function to delete approval template (it have to be empthy - without any items)
	 * 
	 * @author Michal Kliment
	 * @param number $approval_template_id
	 */
	public function delete($approval_template_id = NULL)
	{
		// access control
		if (!$this->acl_check_delete('approval', 'templates'))
			Controller::error(ACCESS);

		// bad parameter
		if (!$approval_template_id || !is_numeric($approval_template_id))
			Controller::warning(PARAMETER);

		$approval_template = new Approval_template_Model($approval_template_id);

		// record doesn't exist
		if (!$approval_template->id)
			Controller::error(RECORD);

		// it is not possible delete used template
		if ($approval_template->state > 0)
		{
			status::warning('It is not possible delete used template.');
			url::redirect('approval_templates/show/'.$approval_template->id);
		}

		// finding all items belong to template
		$approval_template_item_model = new Approval_template_item_Model();
		$approval_template_count_items = $approval_template_item_model
				->where('approval_template_id', $approval_template->id)
				->count_all();

		// template is not empthy
		if ($approval_template_count_items)
		{
			// approval template still contains some items
			status::warning('Approval template still contains some items.');
			url::redirect('approval_templates/show/'.$approval_template->id);
		}

		// template is empthy - delete it
		$approval_template->delete();
		status::success('Approval template has been successfully deleted.');
		url::redirect('approval_templates/show_all');
	}

	/* CALLBACK FUNCTIONS */
	
	/**
	 * Callback function to return priority with up/down arrows to increase/decrease of priority
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 */
	protected static function priority_field($item)
	{
		echo $item->priority;
		echo '&nbsp;&nbsp;';

		$at = new Approval_template_Model($item->approval_template_id);

		if ($at->state < 2)
		{
			$ati = new Approval_template_item_Model();
			
			$lowest = $ati->get_lowest_priority_of_template($at->id);
			$highest = $ati->get_highest_priority_of_template($at->id);

			// the lowest priority can not more decrease
			if ($item->priority < $highest)
			{
				echo html::anchor(
						'approval_template_items/move_up/'.$item->item_id,
						html::image('media/images/icons/uparrow.png')
				);
			}
			
			echo '&nbsp';

	    	// the highest priority can not more increase
			if ($item->priority > $lowest)
			{
				echo html::anchor(
						'approval_template_items/move_down/'.$item->item_id,
						html::image('media/images/icons/downarrow.png')
				);
			}				
		}
	}
}
