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
 * Controller manage items of approval, which groups approval templates and types.
 *
 * @author	Michal Kliment
 * @package Controller
 */
class Approval_template_items_Controller extends Controller
{
	// private attributes, used in callback functions
	private $approval_template = NULL;
	/** @var Approval_template_item_Model */
	private $ati = NULL;
	
	/**
	 * Only checks whether approval are enabled
	 * 
	 * @author Michal Kliment
	 */
	public function __construct()
	{
	    parent::__construct();
	    
	    // approval are not enabled
	    if (!Settings::get('approval_enabled'))
			Controller::error (ACCESS);
	}

	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('approval_templates/show_all');
	}

	/**
	 * Function to add new approval template item to approval template
	 * 
	 * @author Michal Kliment
	 * @param number $approval_template_id
	 */
	public function add($approval_template_id = NULL)
	{
		// access control
		if (!$this->acl_check_new('approval', 'template_items'))
			Controller::error(ACCESS);

		// bad parameter
		if (!$approval_template_id || !is_numeric($approval_template_id))
		    Controller::warning(PARAMETER);

		$this->approval_template = new Approval_template_Model($approval_template_id);

		// record doesn't exist
		if (!$this->approval_template->id)
		    Controller::error(RECORD);

		// it is not possible edit used template
		if ($this->approval_template->state > 1)
		{
			status::warning('It is not possible edit used template.');
			url::redirect('approval_templates/show/'.$this->approval_template->id);
		}

		$arr_approval_types = ORM::factory('approval_type')->select_list();
		
		$arr_approval_types = array
		(
			NULL => '----- '.__('select approval type').' -----'
		) + $arr_approval_types;

		// creates form
		$form = new Forge('approval_template_items/add/' . $approval_template_id);

		$form->group('Basic information');
		
		$form->dropdown('approval_type_id')
				->label(__('Approval type').':')
				->rules('required')
				->options($arr_approval_types)
				->callback(array($this, 'valid_approval_type'))
				->add_button('approval_types');
		
		$form->input('priority')
				->rules('valid_numeric')
				->value(0)
				->help(help::hint('approval_priority'));

		$form->submit('Save');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$ati = new Approval_template_item_Model();
			$ati->approval_template_id = $this->approval_template->id;
			$ati->approval_type_id = $form_data['approval_type_id'];
			$ati->priority = $form_data['priority'];
			$ati->save();

			status::success('Approval template item has been successfully added.');
			url::redirect('approval_templates/show/'.$this->approval_template->id);
		}

		// headline
		$headline = __('Add new approval template item');
		// breadcrums
		$breadcrumbs = breadcrumbs::add()
				->link('approval_templates/show_all', 'Approval templates',
						$this->acl_check_view('approval', 'types'))
				->disable_translation()
				->link('approval_templates/show/'.$this->approval_template->id,
						$this->approval_template->name . ' (' . $this->approval_template->id . ')',
						$this->acl_check_view('approval', 'template_items'))
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
	 * Function to edit approval template item
	 * 
	 * @author Michal Kliment
	 * @param number $approval_template_item_id
	 */
	public function edit($approval_template_item_id = NULL)
	{
		// access control
		if (!$this->acl_check_edit('approval', 'template_items'))
			Controller::error(ACCESS);

		// bad parameter
		if (!$approval_template_item_id || !is_numeric($approval_template_item_id))
		    Controller::warning(PARAMETER);

		$this->ati = new Approval_template_item_Model($approval_template_item_id);

		// record doesn't exist
		if (!$this->ati->id)
		    Controller::error(RECORD);

		$this->approval_template = $this->ati->approval_template;

		// it is not possible edit used template
		if ($this->approval_template->state > 1)
		{
			status::warning('It is not possible edit used template.');
			url::redirect('approval_templates/show/'.$this->approval_template->id);
		}
		
		$arr_approval_types = ORM::factory('approval_type')->select_list();
		
		$arr_approval_types = array
		(
			NULL => '----- '.__('select approval type').' -----'
		) + $arr_approval_types;

		// creates form
		$form = new Forge('approval_template_items/edit/'.$approval_template_item_id);

		$form->group('Basic information');
		
		$form->dropdown('approval_type_id')
				->label(__('Approval type').':')
				->rules('required')
				->options($arr_approval_types)
				->selected($this->ati->approval_type_id)
				->callback(array($this, 'valid_approval_type'))
				->add_button('approval_types');
		
		$form->input('priority')
				->rules('valid_numeric')
				->value($this->ati->priority);

		$form->submit('Save');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$approval_template_item = new Approval_template_item_Model($this->ati->id);
			$approval_template_item->approval_type_id = $form_data['approval_type_id'];
			$approval_template_item->priority = $form_data['priority'];
			$approval_template_item->save();

			status::success('Approval template item has been successfully updated.');
			url::redirect('approval_templates/show/'.$this->approval_template->id);
		}

		// breadcrums
		$breadcrumbs = breadcrumbs::add()
				->link('approval_templates/show_all', 'Approval templates',
						$this->acl_check_view('approval', 'types'))
				->disable_translation()
				->link('approval_templates/show/'.$this->approval_template->id,
						$this->approval_template->name . ' (' . $this->approval_template->id . ')',
						$this->acl_check_view('approval', 'template_items'))
				->text(__('Item') . ' (' . $this->ati->id . ')')
				->html();
		
		// view
		$view = new View('main');
		$view->title = __('Edit approval template item');
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->headline = __('Edit approval template item');
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Function to move up with approval template item (decrease priority)
	 * 
	 * @author Michal Kliment
	 * @param number $approval_template_item_id
	 */
	public function move_up($approval_template_item_id = NULL)
	{
		$this->move($approval_template_item_id, 'up');
	}

	/**
	 * Function to move down with approval template item (increase priority)
	 * 
	 * @author Michal Kliment
	 * @param number $approval_template_item_id
	 */
	public function move_down($approval_template_item_id = NULL)
	{
		$this->move($approval_template_item_id, 'down');
	}

	/**
	 * Function move with approval template item (decrease/increase priority)
	 * 
	 * @author Michal Kliment
	 * @param number $approval_template_item_id
	 * @param string $direction
	 */
	private function move($approval_template_item_id, $direction)
	{
		// access control
		if (!$this->acl_check_edit('approval', 'template_items'))
			Controller::error(ACCESS);

		// bad parameter
		if (!$approval_template_item_id || !is_numeric($approval_template_item_id))
			Controller::warning(PARAMETER);

		$ati = new Approval_template_item_Model($approval_template_item_id);

		// record doesn't exist
		if (!$ati->id)
			Controller::error(RECORD);

		// it is not possible edit used template
		if ($ati->approval_template->state > 1)
		{
			status::warning('It is not possible edit used template.');
			url::redirect('approval_templates/show/'.$ati->approval_template_id);
		}

		if ($direction == 'up')
		{
		    $approval_template_items = $ati
					->where('approval_template_id',$ati->approval_template_id)
					->where('priority >= ',$ati->priority)
					->where('id <> ',$ati->id)
					->orderby('priority','desc')
					->find_all();
		}
		else
		{
		    $approval_template_items = $ati
					->where('approval_template_id',$ati->approval_template_id)
					->where('priority <= ',$ati->priority)
					->where('id <> ',$ati->id)
					->orderby('priority','desc')
					->find_all();
		}

		// priority is not the lowest/highest
		if (count($approval_template_items))
		{
			// find first one with lower/higher priority
			$second_approval_template_item = $approval_template_items->current();

			// and change priorities
			$priority = $second_approval_template_item->priority;
			$second_approval_template_item->priority = $ati->priority;
			$ati->priority = $priority;

			$ati->save();
			$second_approval_template_item->save();
		}
		
		url::redirect('approval_templates/show/'.$ati->approval_template_id);
	}

	/**
	 * Function to delete approval template item
	 * 
	 * @author Michal Kliment
	 * @param number $approval_template_item_id
	 */
	public function delete($approval_template_item_id = NULL)
	{
		// access control
		if (!$this->acl_check_delete('approval', 'template_items'))
			Controller::error(ACCESS);

		// bad parameter
		if (!$approval_template_item_id || !is_numeric($approval_template_item_id))
			Controller::warning(PARAMETER);

		$ati = new Approval_template_item_Model($approval_template_item_id);

		// record doesn't exist
		if (!$ati->id)
			Controller::error(RECORD);

		$approval_template_id = $ati->approval_template_id;

		// it is not possible edit used template
		if ($ati->approval_template->state > 1)
		{
			status::warning('It is not possible edit used template.');
			
			url::redirect('approval_templates/show/'.$ati->approval_template_id);
		}

		$ati->delete();
		
		status::success('Approval template item has been successfully deleted.');
		url::redirect('approval_templates/show/'.$approval_template_id);
	}

	/* CALLBACK FUNCTIONS */

	/**
	 * Callback function to validate approval type of approval template item
	 * 
	 * @author Michal Kliment
	 * @param unknown_type $input
	 */
	public function valid_approval_type($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$approval_type_id = $input->value;

		$approval_template_item_model = new Approval_template_item_Model();

		// check if exist approval template item belongs to same approval 
		// template with same approval type
		$approval_template_item = $approval_template_item_model
				->where('approval_template_id', $this->approval_template->id)
				->where('approval_type_id', $approval_type_id)
				->find();

		if ($approval_template_item && $approval_template_item->id &&(
				!$this->ati ||
				$approval_template_item->id != $this->ati->id
			))
		{
			$input->add_error('required', __(
					'This approval template already contains this approval type.'
			));
		}

	}
	
}
