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
 * Handles interrupt of member's membership to associations.
 * 
 * @package Controller
 */
class Membership_interrupts_Controller extends Controller
{
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('membership_interrupts/show_all');	
	}
	
	/**
	 * Shows all membership interrupts
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page 
	 */
	public function show_all (
			$limit_results = 50, $order_by = 'id', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// access rights
		if (!$this->acl_check_view('Members_Controller', 'members'))
			Controller::error(ACCESS);
		
		$filter_form = new Filter_form('mi');
		
		$filter_form->add('id')
				->type('number');
		
		$filter_form->add('name')
				->label(__('Member name'))
				->callback('json/member_name')
				->table('m');
		
		$filter_form->add('activation_date')
				->type('date')
				->table('mf');
		
		$filter_form->add('deactivation_date')
				->type('date')
				->table('mf');
		
		$filter_form->add('comment');
		
		$membership_interrupt_model = new Membership_interrupt_Model();
		
		$total = $membership_interrupt_model->count_all_membership_interrupts(
				$filter_form->as_sql()
		);
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total)
			$sql_offset = 0;
		
		$interupts = $membership_interrupt_model->get_all_membership_interrupts(
				$sql_offset, (int)$limit_results, $order_by,
				$order_by_direction, $filter_form->as_sql()
		);
		
		$grid = new Grid('members', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang').'/membership_interrupts/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		$grid->order_field('id')
				->label(__('ID'));
		
		$grid->order_callback_field('member_id')
				->label(__('Member'))
				->callback('callback::member_field');
		
		$grid->order_field('from')
				->label(__('Date from'));
		
		$grid->order_field('to')
				->label(__('Date to'));
		
		$grid->order_field('comment');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_edit('Members_Controller', 'membership_interrupts'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('membership_interrupts/edit');
		}
		
		if ($this->acl_check_delete('Members_Controller', 'membership_interrupts'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('membership_interrupts/delete')
					->class('delete_link');
		}
		
		$grid->datasource($interupts);
		
		$title = __('List of all membership interrupts');
		
		$view = new View('main');
		$view->breadcrumbs = __('Membership interrupts');
		$view->title = $title;
		$view->content = new View('show_all');
		$view->content->headline = $title;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

    /**
     * Adding new membership interrupt
	 * 
     * @param integer $member_id id of member to add new membership interrupt
     */
	public function add($member_id = NULL)
	{
		if (!isset($member_id))
			Controller::warning(PARAMETER);
		
		$member = new Member_Model($member_id);
		
		if ($member->id == 0)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_new('Members_Controller', 'membership_interrupts', $member->id))
			Controller::Error(ACCESS);
		
		// saving id for callback function
		$this->members_fee_id = NULL;

		$arr_members[$member->id] = $member->name;

		$this->form = new Forge('membership_interrupts/add/'.$member->id);
		
		$this->form->group('Basic data');
		
		$this->form->dropdown('member_id')
				->label('Member')
				->options($arr_members)
				->rules('required')
				->style('width: 350px');
		
		$this->form->date('from')
				->label('Date from')
				->years(date('Y')-10, date('Y')+10)
				->rules('required');
		
		$this->form->date('to')
				->label('Date to')
				->years(date('Y')-10, date('Y')+10)
				->rules('required')
				->callback(array($this, 'valid_interrupt_interval'));
		
		$this->form->textarea('comment')
				->rules('length[0,250]|required')
				->style('width: 350px');
		
		$this->form->submit('Save');
		
		// form validation
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();
			$saved = true;

			$from = date('Y-m-d', $form_data['from']);
			$to = date('Y-m-d', $form_data['to']);
			
			$fee_model = new Fee_Model();
			$fee = $fee_model->get_by_special_type(Fee_Model::MEMBERSHIP_INTERRUPT);

			$members_fee = new Members_fee_Model();
			$members_fee->member_id = $form_data['member_id'];
			$members_fee->fee_id = $fee->id;
			$members_fee->activation_date = $from;
			$members_fee->deactivation_date = $to;
			$members_fee->priority = 0;

			if (!$members_fee->save())
				$saved = false;

			$mi = new Membership_interrupt_Model();
			$mi->member_id = $form_data['member_id'];
			$mi->members_fee_id = $members_fee->id;
			$mi->comment = $form_data['comment'];
			
			if ($mi->save())
			{
				ORM::factory('member')->reactivate_messages($mi->member_id);
			}
			else
			{
				$saved = false;
			}

			if ($saved)
			{
				status::success('Membership interruption has been succesfully added');
			}
			else
			{
				status::success('Membership interruption has not been succesfully added');
			}
			
			$this->redirect('members/show/'.$form_data['member_id']);
				
		}
		else
		{
			// end of form validation

			$headline = __('Add new interrupt of membership');

			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/'.$member_id,
							"ID $member->id - $member->name",
							$this->acl_check_view(
									'Members_Controller', 'members', $member_id
							)
					)
					->disable_translation()
					->text($headline);

			// view
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->form = $this->form->html();
			$view->content->headline = $headline;
			$view->render(TRUE);
		}
	}

	/**
	 * Editing of membership interruption
	 * 
	 * @param integer $membership_interrupt_id	id of membership interrupt to delete
	 */
	public function edit($membership_interrupt_id = NULL)
	{
		if (!isset($membership_interrupt_id))
			Controller::warning(PARAMETER);
		
		// find object with id to edit
		$mi = new Membership_interrupt_Model($membership_interrupt_id);
		
		// if object with this id doesn't exist
		if ($mi->id == 0)
			Controller::error(RECORD);
		
		// saving id for callback function
		$this->members_fee_id = $mi->members_fee_id;
		// access control
		if (!$this->acl_check_edit(
				'Members_Controller', 'membership_interrupts',
				$mi->member_id
			))
		{
			Controller::Error(ACCESS);
		}
		
		$arr_members[$mi->member->id] = $mi->member->name;

		// form
		$this->form = new Forge('membership_interrupts/edit/'.$mi->id);
		
		$this->form->group('Basic data');
		
		$this->form->dropdown('member_id')
				->label('Member')
				->options($arr_members)
				->rules('required')
				->selected($mi->member_id)
				->style('width:350px');
		
		$this->form->date('from')
				->label('Date from (first day in month)')
				->years(date('Y')-10, date('Y')+10)
				->rules('required')
				->value(strtotime($mi->members_fee->activation_date));
		
		$this->form->date('to')
				->label('Date to (last day in month)')
				->years(date('Y')-10, date('Y')+10)
				->rules('required')
				->callback(array($this, 'valid_interrupt_interval'))
				->value(strtotime($mi->members_fee->deactivation_date));
		
		$this->form->textarea('comment')
				->rules('length[0,250]|required')
				->value($mi->comment)
				->style('width:350px');
		
		$this->form->submit('Save');
		
		// end of form validation
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();
			$saved = true;

			$from = date('Y-m-d', $form_data['from']);
			$to = date('Y-m-d', $form_data['to']);
			
			$mi->member_id = $form_data['member_id'];
			$mi->comment = $form_data['comment'];

			if (!$mi->save())
				$saved = false;

			$members_fee = new Members_fee_Model($mi->members_fee_id);
			
			$members_fee->member_id = $form_data['member_id'];
			$members_fee->activation_date = $from;
			$members_fee->deactivation_date = $to;

			if ($members_fee->save())
			{
				ORM::factory('member')->reactivate_messages($mi->member_id);
			}
			else
			{
				$saved = false;
			}

			// success
			if ($saved)
			{
				status::success('Membership interruption has been succesfully updated');
			}

			$this->redirect('members/show/'.$form_data['member_id']);
		}
		else
		{
			$headline = __('Edit interrupt of membership');

			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/'.$mi->member->id,
							'ID ' . $mi->member->id . ' - ' .
							$mi->member->name,
							$this->acl_check_view(
									'Members_Controller', 'members', 
									$mi->member->id
							)
					)
					->enable_translation()
					->link('membership_interrupts/show_all', 'Membership interrupts',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->text($headline);

			// end of validation
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->form = $this->form->html();
			$view->content->headline = $headline;
			$view->render(TRUE);
		}
	}

	/**
	 * Deleting of membership interruption
	 * 
	 * @param integer $membership_interrupt_id	id of membership interruption to delete
	 */
	public function delete($membership_interrupt_id = NULL)
	{
		// parameter is wrong?
		if (!$membership_interrupt_id || !is_numeric($membership_interrupt_id))
			Controller::warning(PARAMETER);

		$membership_interrupt = new Membership_interrupt_Model($membership_interrupt_id);

		// membership interrupt doesn't exist
		if (!$membership_interrupt->id)
			Controller::error(RECORD);
		
		$member_id = $membership_interrupt->member_id;

		// access control
		if (!$this->acl_check_delete('Members_Controller', 'membership_interrupts', $member_id))
			Controller::Error(ACCESS);
		
		$members_fee = new Members_fee_Model($membership_interrupt->members_fee_id);

		// success
		if ($membership_interrupt->delete() && $members_fee->delete())
		{
			ORM::factory ('member')
					->reactivate_messages($member_id);
			
			status::success('Membership interruption has been succesfully deleted');
		}

		$this->redirect('members/show/'.$member_id);
	}

	/**
	 * Callback function to valid interval of membership interrupt
	 *
	 * @author Michal Kliment
	 * @param object $input
	 */
	public function valid_interrupt_interval($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$method = $this->form->from->method;
		$member_id = $this->input->$method('member_id');
		$from = $this->input->$method('from');
		$to = $this->input->$method('to');
				
		$from_date = date::round_month($from['day'], $from['month'], $from['year']);
		$to_date = date::round_month($to['day'], $to['month'], $to['year']);
		
		$diff = date::diff_month($to_date, $from_date);
		
		if ($diff < 0)
		{
			$input->add_error('required', __(
					'Date from must be smaller then date to'
			).'.');
		}

		if ($diff < 1)
		{
			$input->add_error('required', __(
					'Minimal duration of interrupt is one month'
			).'.');
		}

		$fee_model = new Fee_Model();
		
		$fee = $fee_model->get_by_special_type(Fee_Model::MEMBERSHIP_INTERRUPT);

		// tests collides
		$members_fee_model = new Members_fee_Model();
		
		$members_fees = $members_fee_model->exists(
				$member_id, $fee->type_id,
				date::create($from['day'], $from['month'], $from['year']),
				date::create($to['day'], $to['month'], $to['year']),
				$this->members_fee_id, 0
		);

		// interval of interruption collides with another interruption of this member
		if (count($members_fees))
		{
			$input->add_error('required', __('Interval of interruption collides '.
					'with another interruption of this member'
			).'.');
		}
	}
}
