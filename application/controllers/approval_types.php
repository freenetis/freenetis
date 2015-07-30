<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is release under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Hadles approval types which defines approval properties.
 *
 * @author	Michal Kliment
 * @package Controller
 */
class Approval_types_Controller extends Controller
{
	public static $vote_options = array();
	
	public static $types = array
	(
		1 => 'Simple majority',
		2 => 'Absolute majority',
	);

	public function __construct()
	{
		parent::__construct();
		
		self::$vote_options = array
		(
			NULL	=> __('None'),
			1		=> __('Agree'),
			-1		=> __('Disagree'),
			0		=> __('Abstain')
		);

		self::$types = array
		(
			1		=> __('Simple majority'),
			2		=> __('Absolute majority')
		);
	}

	/**
	 * Index redirects to show_all
	 */
	public function index()
	{
		url::redirect('approval_types/show_all');
	}

	/**
	 * Function to show all approval types
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
		if (!$this->acl_check_view('approval', 'types'))
			Controller::error(ACCESS);
		
		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		$approval_type_model = new Approval_type_Model();
		$total_approval_types = $approval_type_model->count_all();

		if (($sql_offset = ($page - 1) * $limit_results) > $total_approval_types)
			$sql_offset = 0;

		$approval_types = $approval_type_model->get_all_approval_types(
				$sql_offset, (int)$limit_results, $order_by, $order_by_direction
		);

		// create grid
		$grid = new Grid('approval_types/show_all', __('List of all approval types'), array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 100,
			'selector_min'				=> 100,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/approval_types/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=>  $total_approval_types,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
		));

		$grid->add_new_button('approval_types/add', __('Add new approval type'));

		$grid->order_field('id');
		
		$grid->order_link_field('id')
				->link('approval_types/show', 'name');
		
		$grid->order_callback_field('group_id')
				->label('Group')
				->callback('Approval_types_Controller::group_field');
		
		$grid->order_callback_field('type')
				->label('Type')
				->callback('Approval_types_Controller::type_field');
		
		$grid->order_callback_field('majority_percent')
				->label('Percent for majority')
				->callback('callback::percent');
		
		$grid->order_callback_field('interval')
				->label('Interval')
				->callback('Approval_types_Controller::interval_field');
		
		$grid->order_callback_field('default_vote')
				->label('Default vote')
				->callback('Approval_types_Controller::default_vote_field');
		
		$grid->order_callback_field('min_suggest_amount')
				->label('Minimal suggest amount')
				->callback('Approval_types_Controller::min_suggest_amount_field');
		
		$grid->datasource($approval_types);

		// view
		$view = new View('main');
		$view->title = __('Approval');
		$view->breadcrumbs = __('Approval types');
		$view->content = new View('approval/show_all');
		$view->content->grid = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function to show approval type
	 * 
	 * @author Michal Kliment
	 * @param number $approval_type_id
	 */
	public function show($approval_type_id = NULL)
	{
		// access control
		if (!$this->acl_check_view('approval', 'types'))
			Controller::error('ACCESS');

		// bad parameter
		if (!$approval_type_id || !is_numeric($approval_type_id))
			Controller::warning(PARAMETER);

		$approval_type = new Approval_type_Model($approval_type_id);

		// record doesn't exist
		if (!$approval_type->id)
			Controller::error(RECORD);

		$state = $approval_type->get_state($approval_type->id);
		
		// breadcrums
		$breadcrumbs = breadcrumbs::add()
				->link('approval_types/show_all', 'Approval types',
						$this->acl_check_view('approval', 'types'))
				->disable_translation()
				->text($approval_type->name . ' (' . $approval_type->id . ')')
				->html();

		// view
		$view = new View('main');
		$view->title = __('Show approval type');
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('approval/types_show');
		$view->content->approval_type = $approval_type;
		$view->content->state = $state;
		$view->render(TRUE);
	}

	/**
	 * Function to add new approval type
	 * 
	 * @author Michal Kliment
	 */
	public function add()
	{
		// access control
		if (!$this->acl_check_new('approval', 'types'))
			Controller::error('ACCESS');

		$aro_group_model = new Aro_group_Model();
		$aro_groups = $aro_group_model->get_traverz_tree();

		$arr_aro_groups = array();

		foreach ($aro_groups as $aro_group)
		{
			$ret = '';
			$parents_count = Aro_group_Model::count_parent($aro_group->id);
			for($j = 0; $j < $parents_count - 1; $j++ )
				$ret .= '&nbsp;&nbsp;&nbsp;';

			$arr_aro_groups[$aro_group->id] = $ret.__(''.$aro_group->name);
		}

		// form
		$form = new Forge();

		$form->group('Basic information');
		
		$form->input('name')
				->rules('required|length[0,250]')
				->style('width:500px');
		
		$form->textarea('comment')
				->rules('length[0,65535]')
				->style('width:500px');
		
		$form->dropdown('aro_group_id')
				->label('Group')
				->options($arr_aro_groups)
				->rules('required')
				->style('width:500px');
		
		$form->input('min_suggest_amount')
				->label('Minimal suggest amount')
				->rules('valid_numeric');
		
		$form->group('Type');
		
		$form->dropdown('type')
				->options(self::$types)
				->rules('required')
				->style('width:200px');
		
		$form->input('majority_percent')
				->label('Percent for majority')
				->rules('valid_numeric')
				->value('51')
				->callback(array($this, 'valid_majority_percent'));
		
		$form->group('Time constraints');
		
		$form->input('interval')
				->rules('valid_numeric')
				->help(__('In hours'))
				->callback(array($this, 'valid_interval'));
		
		$form->dropdown('default_vote')
				->options(self::$vote_options)
				->callback(array($this, 'valid_default_vote'));

		$form->submit('Save');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$at = new Approval_type_Model();
			$at->name = $form_data['name'];
			$at->comment = $form_data['comment'];
			$at->aro_group_id = $form_data['aro_group_id'];
			$at->type = $form_data['type'];
			$at->majority_percent = $form_data['majority_percent'];
			$at->interval = date::from_interval($form_data['interval']);
			$at->min_suggest_amount = $form_data['min_suggest_amount'];
			$at->save();

			status::success('Approval type has been successfully added.');
			
			// classic adding
			if (!$this->popup)
			{
				url::redirect('approval_types/show/'.$at->id);
			}
		}
	
		// headline
		$headline = __('Add new approval type');
		// breadcrums
		$breadcrumbs = breadcrumbs::add()
				->link('approval_types/show_all', 'Approval types',
						$this->acl_check_view('approval', 'types'))
				->disable_translation()
				->text($headline)
				->html();
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->approval_type = isset($at) && $at->id ? $at : NULL;
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Function to edit approval type
	 * 
	 * @author Michal Kliment
	 * @param integer $approval_type_id
	 */
	public function edit($approval_type_id = NULL)
	{
		// access control
		if (!$this->acl_check_edit('approval', 'types'))
			Controller::error('ACCESS');

		// bad parameter
		if (!$approval_type_id || !is_numeric($approval_type_id))
			Controller::warning(PARAMETER);

		$at = new Approval_type_Model($approval_type_id);

		// record doesn't exist
		if (!$at->id)
			Controller::error(RECORD);

		$state = $at->get_state($at->id);

		// it is not possible edit used type
		if ($state > 1)
		{
			status::warning('It is not possible edit used type.');
			url::redirect('approval_types/show/'.$at->id);
		}

		$aro_group_model = new Aro_group_Model();
		$aro_groups = $aro_group_model->get_traverz_tree();

		$arr_aro_groups = array();

		foreach ($aro_groups as $aro_group)
		{
			$ret = '';
			$parents_count = Aro_group_Model::count_parent($aro_group->id);
			for($j = 0; $j < $parents_count - 1; $j++ )
				$ret .= '&nbsp;&nbsp;&nbsp;';

			$arr_aro_groups[$aro_group->id] = $ret.__(''.$aro_group->name);
		}

		$interval = date::interval($at->interval);

		// form
		$form = new Forge(url_lang::base().'approval_types/edit/'.$at->id);

		$form->group('Basic information');
		
		$form->input('name')
				->rules('required|length[0,250]')
				->value($at->name);
		
		$form->textarea('comment')
				->rules('length[0,65535]')
				->value($at->comment);
		
		$form->dropdown('aro_group_id')
				->label(__('Group').':')
				->options($arr_aro_groups)->rules('required')
				->selected($at->aro_group_id);
		
		$form->input('min_suggest_amount')
				->label(__('Minimal suggest amount').':')
				->rules('valid_numeric')
				->value($at->min_suggest_amount);
		
		$form->group('Type');
		
		$form->dropdown('type')
				->options(self::$types)
				->rules('required')
				->selected($at->type);
		
		$form->input('majority_percent')
				->label(__('Percent for majority').':')
				->rules('valid_numeric')
				->value($at->majority_percent)
				->callback(array($this,'valid_majority_percent'));
		
		$form->group('Time constraints');
		
		$form->input('interval')
				->rules('valid_numeric')
				->help(__('In hours'))
				->value($interval['h'])
				->callback(array($this,'valid_interval'));
		
		$form->dropdown('default_vote')
				->label(__('Default vote').':')
				->options(self::$vote_options)
				->selected($at->default_vote)
				->callback(array($this,'valid_default_vote'));

		$form->submit('submit')
				->value(__('Save'));

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$at = new Approval_type_Model($approval_type_id);
			$at->name = $form_data['name'];
			$at->comment = $form_data['comment'];
			$at->aro_group_id = $form_data['aro_group_id'];
			$at->type = $form_data['type'];
			$at->majority_percent = $form_data['majority_percent'];
			$at->interval = date::from_interval($form_data['interval']);

			if ($form_data['default_vote'] != NULL)
				$at->default_vote = $form_data['default_vote'];
			else
				$at->default_vote = NULL;

			$at->min_suggest_amount = $form_data['min_suggest_amount'];
			
			$at->save();

			status::success('Approval type has been successfully updated.');
			url::redirect('approval_types/show/'.$at->id);
		}
		
		// breadcrums
		$breadcrumbs = breadcrumbs::add()
				->link('approval_types/show_all', 'Approval types',
						$this->acl_check_view('approval', 'types'))
				->disable_translation()
				->text($at->name . ' (' . $at->id . ')')
				->html();

		// view
		$view = new View('main');
		$view->title = __('Edit approval type');
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->headline = __('Edit approval type');
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Function to delete approval type
	 * 
	 * @author Michal Kliment
	 * @param number $approval_type_id
	 */
	public function delete($approval_type_id = NULL)
	{
		// access control
		if (!$this->acl_check_delete('approval', 'types'))
			Controller::error('ACCESS');

		// bad parameter
		if (!$approval_type_id || !is_numeric($approval_type_id))
			Controller::warning(PARAMETER);

		$at = new Approval_type_Model($approval_type_id);

		// record doesn't exist
		if (!$at->id)
			Controller::error(RECORD);

		$state = $at->get_state($at->id);

		// it is not possible delete used type
		if ($state > 0)
		{
			status::warning('It is not possible delete used type.');
			url::redirect('approval_types/show/'.$at->id);
		}
		
		$at->delete();
		
		status::success('Approval type has been successfully deleted.');
		url::redirect('approval_types/show_all');
	}

	/* CALLBACK FUNCTIONS */

	/**
	 * Callback function to return type as text
	 * 
	 * @author Michal Kliment
	 * @param unknown_type $item
	 * @param unknown_type $name
	 */
	protected static function type_field($item, $name)
	{
		echo __(''.self::$types[$item->type]);
	}

	/**
	 * Callback function to return group as text
	 * 
	 * @author Michal Kliment
	 * @param unknown_type $item
	 * @param unknown_type $name
	 */
	protected static function group_field($item, $name)
	{
		if ($item->group_id)
		{
			echo __(''.$item->group_name);
		}
	}

	/**
	 * Callback function to return interval as number
	 * 
	 * @author Michal Kliment
	 * @param unknown_type $item
	 * @param unknown_type $name
	 */
	protected static function interval_field($item, $name)
	{
		if (!$item->interval)
			echo __('None');
		else
		{
			$interval = date::interval($item->interval);
			echo $interval['h'].' '.strtolower(__('hours'));
		}
	}

	/**
	 * Callback function
	 * 
	 * @author Michal Kliment
	 * @param unknown_type $item
	 * @param unknown_type $name
	 */
	protected static function default_vote_field($item, $name)
	{
		echo self::$vote_options[$item->default_vote];
	}

	/**
	 * Callback function to return minimal suggest amount with system currency
	 * 
	 * @author Michal Kliment
	 * @param unknown_type $item
	 * @param unknown_type $name
	 */
	protected static function min_suggest_amount_field($item, $name)
	{
		if (!$item->min_suggest_amount)
		{
			echo __('None');
		}
		else
		{
			echo number_format($item->min_suggest_amount, 2, ',', ' ')
				. ' ' .__(Settings::get('currency'));
		}
	}


	/**
	 * Callback function
	 * 
	 * @author Michal Kliment
	 * @param unknown_type $nput
	 */
	public function valid_majority_percent($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if (!is_numeric($input->value))
		{
			$input->add_error('required', __(
					'Percent for majority must be number.'
			));
		}
		else if ($input->value < 51 || $input->value > 100)
		{
			$input->add_error('required', __(
					'Percent for majority must be in interval 51 - 100.'
			));
		}
	}

	/**
	 * Callback function
	 * 
	 * @author Michal Kliment
	 * @param unknown_type $input
	 */
	public function valid_interval($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$default_vote = $this->input->post('default_vote');
		
		if ($default_vote != NULL && !$input->value)
		{
			$input->add_error('required', __('Interval is required.'));
		}
	}

	/**
	 * Callback function
	 * 
	 * @author Michal Kliment
	 * @param unknown_type $input
	 */
	public function valid_default_vote($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$interval = $this->input->post('interval');
		
		if ($interval && $input->value == NULL)
		{
			$input->add_error('required', __('Default vote is required.'));
		}
	}
}