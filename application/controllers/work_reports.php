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
 * Controllers manages user's work reports and it's (dis)approval directed by
 * approval types and templates.
 * 
 * Work reports contains work in specified date interval.
 * 
 * Each work report has to be approved by other pre-defined users by their votes.
 * 
 * @author Michal Kliment, Ondřej Fibich
 * @package Controller
 */
class Work_reports_Controller extends Controller
{
	/**
	 * Redirects to pending
	 */
	public function index()
	{
		url::redirect('work_reports/pending');
	}

	/**
	 * Shows pending works
	 *
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page 
	 */
	public function pending(
			$limit_results = 100, $order_by = 'id', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// acccess control
		if (!$this->acl_check_view('Users_Controller', 'work'))
		{
			Controller::error(ACCESS);
		}

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
		{
			$limit_results = (int) $this->input->get('record_per_page');
		}
		
		$filter_form = new Filter_form('wr');
		
		$filter_form->add('description');
		
		$filter_form->add('uname')
				->label('Worker')
				->callback('json/user_fullname');
		
		$filter_form->add('suggest_amount')
				->type('number')
				->label('Suggest amount');
		
		$filter_form->add('hours')
				->type('number');
		
		$filter_form->add('km')
				->type('number');
		
		$filter_form->add('date_from')
				->type('date');
		
		$filter_form->add('date_to')
				->type('date');
		
		$filter_form->add('payment_type')
				->type('select')
				->values(Job_report_Model::get_payment_types());

		$work_report_model = new Job_report_Model();
		
		$total_work_reports = $work_report_model->count_all_pending_work_reports(
				$filter_form->as_sql()
		);

		if (($sql_offset = ($page - 1) * $limit_results) > $total_work_reports)
			$sql_offset = 0;

		$work_reports = $work_report_model->get_all_pending_work_reports(
				$sql_offset, (int)$limit_results, $order_by, $order_by_direction,
				$filter_form->as_sql()
		);

		// create grid
		$grid = new Grid('work_reports/pending', __('List of all pending work reports'), array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 100,
			'selector_min'				=> 100,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/work_reports/pending/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_work_reports,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'filter'					=> $filter_form,
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results
		));

		if ($this->acl_check_new('Users_Controller', 'work'))
		{
			$grid->add_new_button('work_reports/add', __('Add new work report'));
		}

		$grid->order_field('id')
				->label('ID');
		
		$grid->order_link_field('user_id')
				->link('users/show', 'uname')
				->label('Worker');
		
		$grid->order_callback_field('description')
				->callback('callback::limited_text');
		
		$grid->order_callback_field('type')
				->callback('callback::work_report_type');
		
		$grid->order_callback_field('hours')
				->callback('callback::round');
		
		$grid->order_callback_field('km')
				->callback('callback::round');
		
		$grid->order_callback_field('suggest_amount')
				->callback('callback::money');
		
		$grid->order_callback_field('your_votes')
				->callback('callback::votes_of_voter');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Users_Controller', 'work'))
		{
			$actions->add_action()
					->icon_action('show')
					->url('work_reports/show');
		}
		
		if ($this->acl_check_edit('Users_Controller', 'work'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('work_reports/edit');
		}

		$grid->datasource($work_reports);
		
		$breadcrumbs = breadcrumbs::add()
				->text('Work reports')
				->text('Pending work reports');

		$view = new View('main');
		$view->title = __('Pending work reports');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('work_reports/show_all');
		$view->content->grid = $grid;
		$view->render(TRUE);
	}

	/**
	 * Shows approved works
	 *
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page 
	 */
	public function approved(
			$limit_results = 100, $order_by = 'id', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// acccess control
		if (!$this->acl_check_view('Users_Controller', 'work'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		$filter_form = new Filter_form('wr');
		
		$filter_form->add('description');
		
		$filter_form->add('uname')
				->label('Worker')
				->callback('json/user_fullname');
		
		$filter_form->add('suggest_amount')
				->type('number')
				->label('Suggest amount');
		
		$filter_form->add('hours')
				->type('number');
		
		$filter_form->add('km')
				->type('number');
		
		$filter_form->add('date_from')
				->type('date');
		
		$filter_form->add('date_to')
				->type('date');
		
		$filter_form->add('payment_type')
				->type('select')
				->values(Job_report_Model::get_payment_types());

		$work_report_model = new Job_report_Model();
		
		$total_work_reports = $work_report_model->count_all_approved_work_reports(
				$filter_form->as_sql()
		);

		if (($sql_offset = ($page - 1) * $limit_results) > $total_work_reports)
			$sql_offset = 0;

		$work_reports = $work_report_model->get_all_approved_work_reports(
				$sql_offset, (int)$limit_results, $order_by, $order_by_direction,
				$filter_form->as_sql()
		);

		// create grid
		$grid = new Grid(
				'work_reports/approved',
				__('List of all approved work reports'), array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 100,
			'selector_min'				=> 100,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/work_reports/approved/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=>  $total_work_reports,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'filter'					=> $filter_form,
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results
		));

		$grid->order_field('id')
				->label(__('Id'));
		
		$grid->order_link_field('user_id')
				->link('users/show', 'uname')
				->label('Worker');
		
		$grid->order_field('description');
		
		$grid->order_callback_field('type')
				->callback('callback::work_report_type');
		
		$grid->order_callback_field('hours')
				->callback('callback::round');
		
		$grid->order_callback_field('km')
				->callback('callback::round');
		
		$grid->order_callback_field('suggest_amount')
				->callback('callback::money');
		
		$grid->order_callback_field('rating')
				->callback('callback::work_report_rating');

		// access control
		if ($this->acl_check_view('Users_Controller','work'))
		{
			$grid->grouped_action_field()
					->add_action()
					->icon_action('show')
					->url('work_reports/show');
		}

		$grid->datasource($work_reports);
		
		$breadcrumbs = breadcrumbs::add()
				->text('Work reports')
				->text('Approved work reports');

		$view = new View('main');
		$view->title = __('Approved work reports');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('work_reports/show_all');
		$view->content->grid = $grid;
		$view->render(TRUE);
	}

	/**
	 * Shows rejected works
	 *
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page 
	 */
	public function rejected(
			$limit_results = 100, $order_by = 'id', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// acccess control
		if (!$this->acl_check_view('Users_Controller', 'work'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		$filter_form = new Filter_form('wr');
		
		$filter_form->add('description');
		
		$filter_form->add('uname')
				->label('Worker')
				->callback('json/user_fullname');
		
		$filter_form->add('suggest_amount')
				->type('number')
				->label('Suggest amount');
		
		$filter_form->add('hours')
				->type('number');
		
		$filter_form->add('km')
				->type('number');
		
		$filter_form->add('date_from')
				->type('date');
		
		$filter_form->add('date_to')
				->type('date');
		
		$filter_form->add('payment_type')
				->type('select')
				->values(Job_report_Model::get_payment_types());

		$work_report_model = new Job_report_Model();
		
		$total_work_reports = $work_report_model->count_all_rejected_work_reports(
				$filter_form->as_sql()
		);

		if (($sql_offset = ($page - 1) * $limit_results) > $total_work_reports)
			$sql_offset = 0;

		$work_reports = $work_report_model->get_all_rejected_work_reports(
				$sql_offset, (int)$limit_results, $order_by, $order_by_direction,
				$filter_form->as_sql()
		);

		// create grid
		$grid = new Grid(
				'work_reports/rejected',
				__('List of all rejected work reports'), array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=>  100,
			'selector_min'				=> 100,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/work_reports/rejected/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_work_reports, 
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'filter'					=> $filter_form,
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results
		));

		$grid->order_field('id');
		
		$grid->order_link_field('user_id')
				->link('users/show', 'uname')
				->label('Worker');
		
		$grid->order_field('description');
		
		$grid->order_callback_field('type')
				->callback('callback::work_report_type');
		
		$grid->order_callback_field('hours')
				->callback('callback::round');
		
		$grid->order_callback_field('km')
				->callback('callback::round');
		
		$grid->order_callback_field('suggest_amount')
				->callback('callback::money');

		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Users_Controller', 'work'))
		{
			$actions->add_action()
					->icon_action('show')
					->url('work_reports/show');
		}

		$grid->datasource($work_reports);

		$breadcrumbs = breadcrumbs::add()
				->text('Work reports')
				->text('Rejected work reports');
		
		$view = new View('main');
		$view->title = __('Rejected work reports');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('work_reports/show_all');
		$view->content->grid = $grid;
		$view->render(TRUE);
	}
	
	/**
	 * Shows works reports by user
	 *
	 * @author Ondřej Fibich
	 * @param integer $user_id 
	 */
	public function show_by_user($user_id = NULL)
	{
		if (empty($user_id) || !is_numeric($user_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$user = new User_Model($user_id);
		$work_report = new Job_report_Model();
		
		if (!$user || !$user->id)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_view('Users_Controller', 'work', $user->member_id))
		{
			Controller::error(ACCESS);
		}
		
		// stats init
		$pending_stats = $approved_stats = $rejected_stats = $concept_stats = array
		(
				'hours'				=> 0.0,
				'kms'				=> 0,
				'suggest_amount'	=> 0.0,
		);
		
		/* Concepts */
		
		$concepts = $work_report->get_concepts_work_reports_of_user($user->id);
		
		foreach ($concepts as $concept)
		{
			$concept_stats['hours'] += $concept->hours;
			$concept_stats['kms'] += $concept->km;
			$concept_stats['suggest_amount'] += $concept->suggest_amount;
		}
		
		$concept_stats['hours'] = round($concept_stats['hours'], 2);
		$concept_stats['kms'] = round($concept_stats['kms'], 2);
		
		$grid_concepts = new Grid('work_reports/show_by_user' . $user->id, '', array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false,
				'total_items'	=> count($concepts)
		));

		if ($this->acl_check_new('Users_Controller', 'work', $user->member_id))
		{
			$grid_concepts->add_new_button(
					'work_reports/add/' . $user->id,
					__('Add new work report')
			);
		}

		$grid_concepts->field('id')
				->label('ID');
		
		$grid_concepts->link_field('user_id')
				->link('users/show', 'uname')
				->label('Worker');
		
		$grid_concepts->callback_field('description')
				->callback('callback::limited_text');
		
		$grid_concepts->callback_field('type')
				->callback('callback::work_report_type');
		
		$grid_concepts->callback_field('hours')
				->callback('callback::round');
		
		$grid_concepts->callback_field('km')
				->callback('callback::round');
		
		$grid_concepts->callback_field('suggest_amount')
				->callback('callback::money');
		
		$actions = $grid_concepts->grouped_action_field();
		
		if ($this->acl_check_view('Users_Controller', 'work', $user->member_id))
		{
			$actions->add_action()
					->icon_action('show')
					->url('work_reports/show');
		}
		
		if ($this->acl_check_edit('Users_Controller', 'work', $user->member_id))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('work_reports/edit');
		}
		
		if ($this->acl_check_delete('Users_Controller', 'work', $user->member_id))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('work_reports/delete')
					->class('delete_link');
		}

		$grid_concepts->datasource($concepts);

		/* Pending */
		
		$pendings = $work_report->get_pending_work_reports_of_user($user->id);
		
		foreach ($pendings as $pend)
		{
			$pending_stats['hours'] += $pend->hours;
			$pending_stats['kms'] += $pend->km;
			$pending_stats['suggest_amount'] += $pend->suggest_amount;
		}
		
		$pending_stats['hours'] = round($pending_stats['hours'], 2);
		$pending_stats['kms'] = round($pending_stats['kms'], 2);
		
		$grid_pending = new Grid('work_reports/show_by_user' . $user->id, '', array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false,
				'total_items'	=> count($pendings)
		));

		$grid_pending->field('id')
				->label('ID');
		
		$grid_pending->link_field('user_id')
				->link('users/show', 'uname')
				->label('Worker');
		
		$grid_pending->callback_field('description')
				->callback('callback::limited_text');
		
		$grid_pending->callback_field('type')
				->callback('callback::work_report_type');
		
		$grid_pending->callback_field('hours')
				->callback('callback::round');
		
		$grid_pending->callback_field('km')
				->callback('callback::round');
		
		$grid_pending->callback_field('suggest_amount')
				->callback('callback::money');
		
		$actions = $grid_pending->grouped_action_field();
		
		if ($this->acl_check_view('Users_Controller', 'work', $user->member_id))
		{
			$actions->add_action()
					->icon_action('show')
					->url('work_reports/show');
		}

		$grid_pending->datasource($pendings);
		
		
		/* Approved */
		
		$approved = $work_report->get_approved_work_reports_of_user($user->id);
		
		foreach ($approved as $approve)
		{
			$approved_stats['hours'] += $approve->hours;
			$approved_stats['kms'] += $approve->km;
			$approved_stats['suggest_amount'] += $approve->rating;
		}
		
		$approved_stats['hours'] = round($approved_stats['hours']);
		$approved_stats['kms'] = round($approved_stats['kms']);
		
		$grid_approved = new Grid('work_reports/show_by_user' . $user->id, '', array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false,
				'total_items'	=> count($approved)
		));

		$grid_approved->field('id')
				->label('ID');
		
		$grid_approved->link_field('user_id')
				->link('users/show', 'uname')
				->label('Worker');
		
		$grid_approved->callback_field('description')
				->callback('callback::limited_text');
		
		$grid_approved->callback_field('type')
				->callback('callback::work_report_type');
		
		$grid_approved->callback_field('hours')
				->callback('callback::round');
		
		$grid_approved->callback_field('km')
				->callback('callback::round');
		
		$grid_approved->callback_field('suggest_amount')
				->callback('callback::money');
		
		$grid_approved->callback_field('rating')
				->callback('callback::work_report_rating');
		
		$actions = $grid_approved->grouped_action_field();
		
		if ($this->acl_check_view('Users_Controller', 'work', $user->member_id))
		{
			$actions->add_action()
					->icon_action('show')
					->url('work_reports/show');
		}

		$grid_approved->datasource($approved);
		
		/* Rejected */
		
		$rejected = $work_report->get_rejected_work_reports_of_user($user->id);
		
		foreach ($rejected as $reject)
		{
			$pending_stats['hours'] += $reject->hours;
			$pending_stats['kms'] += $reject->km;
			$pending_stats['suggest_amount'] += $reject->suggest_amount;
		}
		
		$pending_stats['hours'] = round($pending_stats['hours'], 2);
		$pending_stats['kms'] = round($pending_stats['kms'], 2);
		
		$grid_rejected = new Grid('work_reports/show_by_user' . $user->id, '', array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false,
				'total_items'	=> count($rejected)
		));

		$grid_rejected->field('id')
				->label('ID');
		
		$grid_rejected->link_field('user_id')
				->link('users/show', 'uname')
				->label('Worker');
		
		$grid_rejected->callback_field('description')
				->callback('callback::limited_text');
		
		$grid_rejected->callback_field('type')
				->callback('callback::work_report_type');
		
		$grid_rejected->callback_field('hours')
				->callback('callback::round');
		
		$grid_rejected->callback_field('km')
				->callback('callback::round');
		
		$grid_rejected->callback_field('suggest_amount')
				->callback('callback::money');
		
		$actions = $grid_rejected->grouped_action_field();
		
		if ($this->acl_check_view('Users_Controller', 'work', $user->member_id))
		{
			$actions->add_action()
					->icon_action('show')
					->url('work_reports/show');
		}

		$grid_rejected->datasource($rejected);
		
		/* view */
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/'.$user->member->id,
						'ID ' . $user->member->id . ' - ' . $user->member->name,
						$this->acl_check_view(
								'Members_Controller', 'members',
								$user->member->id
						)
				)->enable_translation()
				->link('users/show_by_member/' . $user->member_id,
						'Users',
						$this->acl_check_view(
								'Users_Controller', 'users',
								$user->member_id
						)
				)->disable_translation()
				->link('users/show/'.$user->id,
						$user->name . ' ' . $user->surname .
						' (' . $user->login . ')',
						$this->acl_check_view(
								'Users_Controller','users',
								$user->member_id
						)
				)->enable_translation()
				->text('Work reports');
		
		$headline = __('List of work reports of user');
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('work_reports/show_by_user');
		$view->content->headline = $headline;
		$view->content->user = $user;
		$view->content->show_concepts = ($user->id == $this->user_id);
		$view->content->grid_concepts = $grid_concepts;
		$view->content->grid_approved = $grid_approved;
		$view->content->grid_rejected = $grid_rejected;
		$view->content->grid_pending = $grid_pending;
		$view->content->stats_concepts = $concept_stats;
		$view->content->stats_approved = $approved_stats;
		$view->content->stats_rejected = $rejected_stats;
		$view->content->stats_pending = $pending_stats;
		$view->render(TRUE);
	}

	/**
	 * Shows work
	 *
	 * @param integer $work_report_id 
	 */
	public function show($work_report_id = NULL)
	{
		if (!$work_report_id || !is_numeric($work_report_id))
		{
			Controller::warning(PARAMETER);
		}

		$work_report_model = new Job_report_Model($work_report_id);
		$work_report = $work_report_model->get_work_report();

		if (!$work_report_model || !$work_report_model->id)
		{
			Controller::error(RECORD);
		}
		
		$member_id = $work_report_model->user->member_id;
		
		// concept can be viewed only by owner
		if (!$this->acl_check_view('Users_Controller', 'work', $member_id) || (
				$work_report->concept &&
				$this->user_id != $work_report_model->user_id &&
				$this->user_id != $work_report_model->added_by_id
			))
		{
			Controller::error(ACCESS);
		}

		$works = ORM::factory('job')->get_all_works_by_job_report_id($work_report->id);

		$approval_template_item_model = new Approval_template_item_Model();

		// test if user can vote
		$can_vote = FALSE;

		if (!$work_report_model->concept && $work_report->state <= 1)
		{
			foreach ($works as $work)
			{
				if ($approval_template_item_model->check_user_vote_rights(
						$work->id,
						Session::instance()->get('user_id'),
						$work_report->suggest_amount
					))
				{
					$can_vote = TRUE;
					break;
				}
			}
		}

		// create grid
		$works_grid = new Grid('work_reports/show/' . $work_report->id, '', array
		(
			'use_paginator'	=> false,
			'use_selector'	=> false,
			'total_items'	=> count($works),
			'id'			=> 'work_reports__show_grid'
		));		

		if ($can_vote)
		{
			$works_grid->add_new_button(
					'#', __('Set votes to') . ' ' . __('Abstain', '', 1),
					array('id' => 'mark_all_abstain')
			);

			if ($this->user_id != $work_report_model->user_id)
			{
				$works_grid->add_new_button(
						'#', __('Set votes to') . ' ' . __('Disagree', '', 1),
						array('id' => 'mark_all_disagree')
				);

				$works_grid->add_new_button(
						'#', __('Set votes to') . ' ' . __('Agree', '', 1),
						array('id' => 'mark_all_agree')
				);
			}
		}
		
		if ($work_report->state >= 2)
		{
			$works_grid->callback_field('approved')
					->label('')
					->callback('callback::work_approved');
		}

		$works_grid->field('date');
		
		$works_grid->callback_field('description')
				->callback('callback::limited_text');
		
		$works_grid->callback_field('hours')
				->callback('callback::round');
		
		$works_grid->callback_field('km')
				->callback('callback::round');
		
		$works_grid->callback_field('suggest_amount')
				->callback('callback::money');
		
		if (!$work_report_model->concept)
		{
			$works_grid->callback_field('approval_state')
					->label(__('State'))
					->help(help::hint('approval_state'))
					->callback('callback::vote_state_field');

			$works_grid->callback_field('comments_count')
					->label(__('Comments'))
					->callback('callback::comments_field');
		}

		if ($can_vote)
		{
			$works_grid->form_field('vote')
					->type('dropdown')
					->rules('required')
					->options(array
					(
						NULL	=> '----------------',
						1		=> __('Agree'),
						-1		=> __('Disagree'),
						0		=> __('Abstain')
					))->callback('Works_Controller::vote_form_field');
			
			$works_grid->form_field('comment')
					->type('textarea')
					->callback('Works_Controller::comment_form_field');
		}

		// access control
		if ($this->acl_check_view('Users_Controller', 'work', $member_id))
		{
			$works_grid->grouped_action_field()
					->add_action()
					->icon_action('show')
					->url('works/show');
		}

		$works_grid->datasource($works);

		$links = array();
		
		// breadcrumbs and links back
		
		$breadcrumbs = breadcrumbs::add()
				->link('work_reports', 'Work reports',
						$this->acl_check_view('Users_Controller', 'work'));

		switch ($work_report->state)
		{
			case 0:
			case 1:
				$state_text = __('Pending');
				$breadcrumbs->link('work_reports/pending', 'Pending',
						$this->acl_check_view('Users_Controller', 'work'));
				break;

			case 2:
				$state_text = '<span style="color: red;">'.__('Rejected').'</span>';
				$breadcrumbs->link(
						'work_reports/rejected', 'Rejected',
						$this->acl_check_view('Users_Controller', 'work')
				);
				break;

			case 3:
				$state_text = '<span style="color: green;">'.__('Approved');
				
				if ($work_report->suggest_amount != $work_report_model->get_rating())
				{
					$state_text .= ' (' . __('Partially') . ')';
				}
				
				$state_text .= '</span>';
				$breadcrumbs->link(
						'work_reports/approved', 'Approved',
						$this->acl_check_view('Users_Controller', 'work')
				);
				break;
		}
		
		$breadcrumbs->disable_translation()
				->link('work_reports/show_by_user/' . $work_report_model->user_id,
						$work_report_model->user->get_full_name())
				->text(text::limit_chars($work_report_model->description, 40));

		if ($this->acl_check_edit('Users_Controller', 'work', $work_report->member_id) &&
			$work_report->state == 0)
		{
			$links[] = html::anchor(
					url_lang::base().'work_reports/edit/'.$work_report->id,
					__('Edit')
			);
		}

		if ($this->acl_check_delete('Users_Controller', 'work', $work_report->member_id) &&
			$work_report->state == 0)
		{
			$links[] = html::anchor(
					url_lang::base().'work_reports/delete/'.$work_report->id,
					__('Delete'), array('class' => 'delete_link')
			);
		}

		$links = implode(' | ', $links);
		
		// post votes
		
		if (isset($_POST) && count($_POST))
		{
			$post_votes = $_POST['vote'];
			
			$vote_model = new Vote_Model();
			$comments = $_POST['comment'];
			$approval_template_model = new Approval_template_Model();
			$approval_template_item_model = new Approval_template_item_Model();
			$state = 0;

			$amount = 0;
			$approved_works_ids = array();
			$rejected_works_ids = array();
			$pending_works_ids = array();
			$work_model = new Job_Model();

			foreach ($post_votes as $id => $post_vote)
			{
				$work = $work_model->find($id);

				if (!$work || !$work->id)
				{
					continue;
				}

				$suggest_amount = $work->suggest_amount;

				if ($work->job_report_id)
				{
					$suggest_amount = $work->job_report->get_suggest_amount();
				}

				// finding aro group of logged user
				$aro_group = $approval_template_item_model->get_aro_group_by_approval_template_id_and_user_id(
						$work->approval_template_id,
						$this->session->get('user_id'),
						$suggest_amount
				);

				// user can vote
				if ($aro_group && $aro_group->id)
				{
					// finding vote of user
					$vote = $vote_model->where('user_id', $this->user_id)
							->where('fk_id', $id)
							->where('type', Vote_Model::WORK)
							->find();

					// edit/delete vote
					if ($vote->id)
					{
						if ($post_vote == '')
						{
							$vote->delete();
						}
						else
						{
							$vote->vote = $post_vote;
							$vote->comment = $comments[$id];
							$vote->time = date('Y-m-d H:i:s');
							$vote->save();
						}
					}
					// create vote
					else
					{
						$vote->clear();
						$vote->user_id = $this->user_id;
						$vote->fk_id = $id;
						$vote->aro_group_id = $aro_group->id;
						$vote->type = Vote_Model::WORK;
						$vote->vote = $post_vote;
						$vote->time = date('Y-m-d H:i:s');
						$vote->comment = $comments[$id];
						$vote->save();
					}

					// set up state of work
					$work->state = $work->get_state(Vote_Model::WORK);
					$work->save();

					switch ($work->state)
					{
						case 0:
						case 1:
							$pending_works_ids[] = $work->id;
							break;
						case 2:
							$rejected_works_ids[] = $work->id;
							break;
						case 3:
							$approved_works_ids[] = $work->id;
							$amount += $work->suggest_amount;
							break;
					}
				}
			}

			if (!count($pending_works_ids) &&
				count($approved_works_ids) &&
				$work_report_model->payment_type == Job_report_Model::PAYMENT_BY_CREDIT)
			{
				// creates new transfer
				$account_model = new Account_Model();

				$operating_id = $account_model->where(
						'account_attribute_id', Account_attribute_Model::OPERATING
				)->find()->id;

				$credit_id = $account_model->where('member_id', $work->user->member_id)
						->where('account_attribute_id', Account_attribute_Model::CREDIT)
						->find()
						->id;

				$transfer_id = Transfer_Model::insert_transfer(
						$operating_id, $credit_id, null, null,
						$this->session->get('user_id'), null, date('Y-m-d'),
						date('Y-m-d H:i:s'), __('Work report approval'),
						round($amount, 2)
				);

				$work_report_model->transfer_id = $transfer_id;
				$work_report_model->save();

				foreach ($approved_works_ids as $approved_work_id)
				{
					$approved_work = new Job_Model($approved_work_id);
					$approved_work->transfer_id = $transfer_id;
					$approved_work->save();
				}
			}
			url::redirect(url::base(TRUE).url::current(TRUE));
		}

		$view = new View('main');
		$view->title = __('Show work report');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('work_reports/show');
		$view->content->work_report = $work_report;
		$view->content->work_report_model = $work_report_model;
		$view->content->transfer = $work_report_model->transfer;
		$view->content->links = $links;
		$view->content->state_text = $state_text;
		$view->content->works_grid = $works_grid;
		$view->render(TRUE);
	}

	/**
	 * Adds new work report
	 *
	 * @author Ondřej Fibich
	 * @param integer $user_id 
	 */
	public function add($user_id = NULL)
	{
		if ($user_id)
		{
			if (!is_numeric($user_id))
				Controller::warning(PARAMETER);
			
			$user = new User_Model($user_id);
			
			if (!$user->id)
				Controller::error(RECORD);
			
			if (!$this->acl_check_new('Users_Controller', 'work', $user->member_id))
				Controller::error(ACCESS);
			
			$selected = $user->id;
			$arr_users[$user->id] = $user->get_full_name() . ' - ' . $user->login;
		}
		else
		{
			if (!$this->acl_check_new('Users_Controller', 'work'))
				Controller::error(ACCESS);
			
			$selected = $this->session->get('user_id');
			
			$concat = "CONCAT(
					COALESCE(surname, ''), ' ',
					COALESCE(name, ''), ' - ',
					COALESCE(login, '')
			)";
			
			$arr_users = ORM::factory('user')->select_list('id', $concat);
		}
		
		// approval templates
		
		$arr_approval_templates = array();

		if ($this->acl_check_view('approval', 'templates'))
		{			
			$arr_approval_templates = ORM::factory('approval_template')->select_list();
		}
		
		// posted?
		if ($_POST && count($_POST))
		{
			try
			{
				$form_data = $_POST;
				
				// transaction
				
				$work_report = new Job_report_Model();
				$work_report->transaction_start();
				// check
				
				if (!isset($form_data['user_id']) ||
					!isset($form_data['description']) ||
					!isset($form_data['payment_type']) ||
					!isset($form_data['price_per_hour']) ||
					!isset($form_data['price_per_km']))
				{
					throw new Exception('Invalid post');
				}
				
				// approval template
				
				if (isset($form_data['approval_template_id']) &&
					$this->acl_check_view('approval', 'templates'))
				{
					$at_id = intval($form_data['approval_template_id']);
				}
				else
				{
					$at_id = $this->settings->get('default_work_approval_template');
				}
				
				// save report
				
				$work_report->user_id = $form_data['user_id'];
				$work_report->added_by_id = $this->user_id;
				$work_report->approval_template_id = $at_id;
				$work_report->description = $form_data['description'];
				$work_report->price_per_hour = $form_data['price_per_hour'];
				$work_report->concept = TRUE;
				$work_report->payment_type = intval($form_data['payment_type']);
				
				if (isset($form_data['type']) &&
					preg_match('/^[0-9]{4}-[0-9]{1,2}$/', $form_data['type']))
				{
					$work_report->type = $form_data['type'];
				}
				
				if (is_numeric($form_data['price_per_km']))
				{
					$work_report->price_per_km = $form_data['price_per_km'];
				}
				
				$work_report->save_throwable();
				
				// add works
				
				if (isset($form_data['work_description']) &&
					is_array($form_data['work_description']))
				{
					foreach ($form_data['work_description'] as $i => $description)
					{					
						$hours = $form_data['work_hours'][$i];
						$km = $form_data['work_km'][$i];

						if (empty($description) ||
							empty($hours) ||
							empty($form_data['work_date']))
						{
							continue;
						}

						$suggest_amount = $work_report->price_per_hour * $hours;
						$suggest_amount += $work_report->price_per_km * $km;

						$date = explode('-', $form_data['work_date'][$i]);

						$work = new Job_Model();
						$work->job_report_id = $work_report->id;
						$work->user_id = $form_data['user_id'];
						$work->added_by_id = $this->user_id;
						$work->approval_template_id = $at_id;
						$work->description = $description;
						$work->suggest_amount = $suggest_amount;
						$work->date = date::create($date[2], $date[1], $date[0]);
						$work->create_date = date('Y-m-d H:i:s');
						$work->hours = $hours;
						$work->km = $km;
						$work->save_throwable();
					}
				}
				
				// end adding
				
				$work_report->transaction_commit();
				status::success('Work report has been successfully added');
				
				url::redirect('work_reports/show/' . $work_report->id);
			}
			catch (Exception $e)
			{
				$work_report->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cant add new work report.');
			}
		}
		
		// view
		
		$breadcrumbs = breadcrumbs::add()
				->link('work_reports', 'Work reports',
						$this->acl_check_view('Users_Controller','work'))
				->text('Add new work report');

		$view = new View('main');
		$view->title = __('Add new work report');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('work_reports/add');
		$view->content->arr_users = $arr_users;
		$view->content->selected_user = $selected;
		$view->content->arr_approval_templates = $arr_approval_templates;
		$view->render(TRUE);
	}

	/**
	 * Edits work report
	 *
	 * @author Ondřej Fibich
	 * @param integer $work_report_id 
	 */
	public function edit($work_report_id = NULL)
	{
		if (!$work_report_id || !is_numeric($work_report_id))
		{
			Controller::warning(PARAMETER);
		}

		$work_report = new Job_report_Model($work_report_id);

		if (!$work_report || !$work_report->id)
		{
			Controller::error(RECORD);
		}

		// work report is locked
		if ($work_report->get_state() > 0)
		{
			status::warning('It is not possible edit locked work report.');
			url::redirect('work_reports/show/'.$work_report->id);
		}
		
		// concept can be edited only by owner
		if (!$this->acl_check_edit(
				'Users_Controller', 'work', $work_report->user->member_id
			) || (
				$work_report->concept &&
				$this->user_id != $work_report->user_id &&
				$this->user_id != $work_report->added_by_id
			))
		{
			Controller::error(ACCESS);
		}

		// grouped works
		if (empty($work_report->type))
		{
			$works = $work_report->jobs;
		}
		// monthly report
		else
		{
			$works = $work_report->get_works_of_monthly_workreport();
		}
		
		
		// approval templates
		$arr_approval_templates = array();

		if ($this->acl_check_view('approval', 'templates'))
		{			
			$arr_approval_templates = ORM::factory('approval_template')->select_list();
		}

		// posted
		if ($_POST && count($_POST))
		{
			$form_data = $_POST;
			$issaved  = TRUE;
			$count = 0;
			
			if (!isset($form_data['price_per_hour']) ||
				!isset($form_data['price_per_km']) ||
				!isset($form_data['payment_type']) ||
				!isset($form_data['description']))
			{
				throw new Exception('Invalid post');
			}
			
			// prices
			
			$price_per_hour = doubleval($form_data['price_per_hour']);
			
			if (empty($form_data['price_per_km'])) 
			{
				$price_per_km = NULL;
			}
			else
			{
				$price_per_km = doubleval($form_data['price_per_km']);
			}
			
			// db work
			
			try
			{
				$work_report->transaction_start();
				
				//// save report
				
				if (isset($form_data['approval_template_id']) &&
					$this->acl_check_view('approval', 'templates'))
				{
					$at_id = intval($form_data['approval_template_id']);
					$work_report->approval_template_id = $at_id;
				}
				
				$work_report->description = $form_data['description'];
				$work_report->price_per_hour = $price_per_hour;
				$work_report->price_per_km = $price_per_km;
				$work_report->payment_type = intval($form_data['payment_type']);
				$work_report->save_throwable();
				
				//// delete works
				
				// delete all works in report
				if (!isset($form_data['work_id']) ||
					!is_array($form_data['work_id']) ||
					!count($form_data['work_id']))
				{
					$work_report->delete_works();
				}
				// delete all deleted, preserved existings
				else
				{
					$preserved_ids = array_values($form_data['work_id']);
					$work_report->delete_works($preserved_ids);
				}
				
				//// add/update works
				
				if (isset($form_data['work_description']))
				{
					foreach ($form_data['work_description'] as $i => $description)
					{
						$date = $form_data['work_date'][$i];
						$hours = doubleval($form_data['work_hours'][$i]);
						$km = intval($form_data['work_km'][$i]);
						$work_id = isset($form_data['work_id'][$i]) ? $form_data['work_id'][$i] : FALSE;
						
						// valid?
						if (!preg_match('/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/', $date) ||
							($hours <= 0) || ($hours > 24))
						{
							// skip to next
							continue;
						}
						
						// load for edit
						if ($work_id)
						{
							$work = new Job_Model($work_id);
						}
						else
						{
							$work = new Job_Model();
							$work->added_by_id = $this->user_id;
						}
						
						$suggest_amount = $work_report->price_per_hour * $hours;
						$suggest_amount += $work_report->price_per_km * $km;

						$date = explode('-', $date);

						// edit/add
						$work->job_report_id = $work_report->id;
						$work->user_id = $work_report->user_id;
						$work->approval_template_id = $work_report->approval_template_id;
						$work->description = $description;
						$work->suggest_amount = $suggest_amount;
						$work->date = date::create($date[2], $date[1], $date[0]);
						$work->create_date = date('Y-m-d H:i:s');
						$work->hours = $hours;
						$work->km = $km;
						$work->save_throwable();
					}
				}
				
				//// finish
				
				$work_report->transaction_commit();
				status::success('Work report has been successfully updated');
				url::redirect('work_reports/show/'.$work_report->id);
			}
			catch (Exception $e)
			{
				$work_report->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cant edit work report.');
			}
		}
		
		// breadcrumbs
		
		$breadcrumbs = breadcrumbs::add()
				->link('work_reports', 'Work reports',
						$this->acl_check_view('Users_Controller', 'work'));

		switch ($work_report->get_state())
		{
			case 0:
			case 1:
				$breadcrumbs->link('work_reports/pending', 'Pending');
				break;
			case 2:
				$breadcrumbs->link('work_reports/rejected', 'Rejected');
				break;
			case 3:
				$breadcrumbs->link('work_reports/approved', 'Approved');
				break;
		}
		
		$breadcrumbs->disable_translation()
				->link('work_reports/show_by_user/' . $work_report->user_id,
						$work_report->user->get_full_name())
				->link('work_reports/show/' . $work_report->id,
						text::limit_chars($work_report->description, 40))
				->enable_translation()
				->text('Edit');

		// view
		
		$view = new View('main');
		$view->title = __('Edit work report');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('work_reports/edit');
		$view->content->work_report = $work_report;
		$view->content->month = intval(substr($work_report->type, 5, 6));
		$view->content->year = intval(substr($work_report->type, 0, 4));
		$view->content->works = $works;
		$view->content->arr_approval_templates = $arr_approval_templates;
		$view->render(TRUE);
	}

	/**
	 * Deletes work report and it's works.
	 * Unlock approval template if it is possible.
	 *
	 * @param integer $work_report_id 
	 */
	public function delete($work_report_id = NULL)
	{
		if (!$work_report_id || !is_numeric($work_report_id))
		{
			Controller::warning(PARAMETER);
		}

		$work_report_model = new Job_report_Model($work_report_id);
		$work_report = $work_report_model->get_work_report($work_report_id);

		if (!$work_report || !$work_report->id)
		{
			Controller::error(RECORD);
		}

		if (!$this->acl_check_delete('Users_Controller', 'work', $work_report->member_id))
		{
			Controller::error(ACCESS);
		}

		// work is locked?
		if ($work_report->state > 0)
		{
			status::warning('It is not possible delete locked work report.');
			url::redirect('work_reports/show/' . $work_report->id);
		}

		$approval_template_id = $work_report->approval_template_id;

		// delete report (works are deleted by forein key)
		$saved = $work_report_model->delete();

		// set up state of approval template
		$approval_template = new Approval_template_Model($approval_template_id);
		$approval_template->state = $approval_template->get_state();
		$saved = $saved && $approval_template->save();

		if ($saved)
		{
			status::success('Work report has been successfully deleted');
		}
		
		$linkback = Path::instance()->previous();
		if (url::slice(url_lang::uri($linkback),1,1) == 'show')
		{
			$linkback = 'work_reports/pending';
		}

		$this->redirect($linkback);
	}
	
	/**
	 * Un-concept report by owner
	 *
	 * @param integer $work_report_id 
	 */
	public function concept_change($work_report_id = NULL)
	{
		if (!$work_report_id || !is_numeric($work_report_id))
		{
			Controller::warning(PARAMETER);
		}

		$work_report = new Job_report_Model($work_report_id);

		if (!$work_report || !$work_report->id)
		{
			Controller::error(RECORD);
		}
		
		// is actual user owner of concept?
		if ($this->user_id != $work_report->user_id &&
			$this->user_id != $work_report->added_by_id)
		{
			Controller::error(ACCESS);
		}

		// work report is locked?
		if ($work_report->get_state() > 0 || !$work_report->concept)
		{
			status::warning('It is not possible edit locked work report.');
			url::redirect( 'work_reports/show/' . $work_report->id);
		}
		
		// has any work?
		if (!$work_report->jobs->count())
		{
			status::warning('Work report has to have at least one work.');
			url::redirect( 'work_reports/show/' . $work_report->id);
		}
		
		$work_report->concept = FALSE;
		
		if ($work_report->save())
		{
			status::success('Concept of report has been sended fo approval.');
		}
		
		url::redirect( 'work_reports/show/' . $work_report->id);
	}

}
