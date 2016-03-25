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
	 * Only checks whether works are enabled
	 * 
	 * @author Michal Kliment
	 */
	public function __construct()
	{
	    parent::__construct();
	    
	    // works are not enabled
	    if (!Settings::get('works_enabled'))
			Controller::error (ACCESS);
	}
	
	/**
	 * Redirects to pending
	 */
	public function index()
	{
		url::redirect('work_reports/show_all');
	}

	/**
	 * Shows all work reports
	 *
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page 
	 */
	public function show_all(
			$limit_results = 20, $order_by = 'id', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// acccess control
		if (!$this->acl_check_view('Work_reports_Controller', 'work_report'))
		{
			Controller::error(ACCESS);
		}

		// gets new selector
		if (is_numeric($this->input->post('record_per_page')))
		{
			$limit_results = (int) $this->input->post('record_per_page');
		}
		
		$filter_form = new Filter_form('wr');
		
		$filter_form->add('state')
				->type('select')
				->values(Vote_Model::get_states());
		
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
		
		// hide grid on its first load (#442)
		$hide_grid = Settings::get('grid_hide_on_first_load') && $filter_form->is_first_load();
		
		if (!$hide_grid)
		{
			try
			{
				$total_work_reports = $work_report_model->count_all_work_reports(
					$filter_form->as_sql()
				);

				if (($sql_offset = ($page - 1) * $limit_results) > $total_work_reports)
					$sql_offset = 0;

				$work_reports = $work_report_model->get_all_work_reports(
						$sql_offset, (int)$limit_results, $order_by, $order_by_direction,
						$filter_form->as_sql(), $this->user_id
				);
			}
			catch (Exception $e)
			{
				if ($filter_form->is_loaded_from_saved_query())
				{
					status::error('Invalid saved query', $e);
					// disable default query (loop protection)
					if ($filter_form->is_loaded_from_default_saved_query())
					{
						ORM::factory('filter_query')->remove_default($filter_form->get_base_url());
					}
					$this->redirect(url_lang::current());
				}
				throw $e;
			}
		}

		// create grid
		$grid = new Grid('work_reports/show_all', null, array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 20,
			'selector_min'				=> 20,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/work_reports/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> isset($total_work_reports) ? $total_work_reports : 0,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'filter'					=> $filter_form,
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results
		));

		if ($this->acl_check_new('Work_reports_Controller', 'work_report'))
		{
			$grid->add_new_button(
				'work_reports/add',
				__('Add new work report')
			);
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
		
		$grid->order_callback_field('approval_state')
				->label(__('State'))
				->help(help::hint('approval_state'))
				->callback('callback::vote_state_field');
		
		$grid->order_callback_field('your_votes')
				->callback('callback::votes_of_voter')
				->class('center');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Work_reports_Controller', 'work_report'))
		{
			$actions->add_action()
					->icon_action('show')
					->url('work_reports/show');
		}
		
		if ($this->acl_check_edit('Work_reports_Controller', 'work_report'))
		{
			$actions->add_conditional_action('id')
					->icon_action('edit')
					->condition('is_item_new')
					->url('work_reports/edit');
		}
		
		if ($this->acl_check_delete('Work_reports_Controller', 'work_report'))
		{
			$actions->add_conditional_action('id')
					->icon_action('delete')
					->condition('is_item_new')
					->url('work_reports/delete')
					->class('delete_link');
		}

		if (!$hide_grid)
			$grid->datasource($work_reports);
		
		$title = __('Work reports');

		$view = new View('main');
		$view->title = $title;
		$view->breadcrumbs = $title;
		$view->content = new View('show_all');
		$view->content->table = $grid;
		$view->content->headline = __('List of all work reports');
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
		
		if (!$this->acl_check_view('Work_reports_Controller', 'work_report', $user->member_id))
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

		if ($this->acl_check_new('Work_reports_Controller', 'work_report', $user->member_id))
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
		
		if ($this->acl_check_view('Work_reports_Controller', 'work_report', $user->member_id))
		{
			$actions->add_action()
					->icon_action('show')
					->url('users/show_work_report');
		}
		
		if ($this->acl_check_edit('Work_reports_Controller', 'work_report', $user->member_id))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('work_reports/edit');
		}
		
		if ($this->acl_check_delete('Work_reports_Controller', 'work_report', $user->member_id))
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
		
		if ($this->acl_check_view('Work_reports_Controller', 'work_report', $user->member_id))
		{
			$actions->add_action()
					->icon_action('show')
					->url('users/show_work_report');
		}
		
		if ($this->acl_check_edit('Work_reports_Controller', 'work_report'))
		{
			$actions->add_conditional_action('id')
					->icon_action('edit')
					->condition('is_item_new')
					->url('work_reports/edit');
		}
		
		if ($this->acl_check_delete('Work_reports_Controller', 'work_report'))
		{
			$actions->add_conditional_action('id')
					->icon_action('delete')
					->condition('is_item_new')
					->url('work_reports/delete')
					->class('delete_link');
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
		
		if ($this->acl_check_view('Work_reports_Controller', 'work_report', $user->member_id))
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
		
		if ($this->acl_check_view('Work_reports_Controller', 'work_report', $user->member_id))
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
		$view->content->headline = $headline . ' ' . help::hint('work_report_description');
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
		if (!$this->acl_check_view('Work_reports_Controller', 'work_report', $member_id) || (
				$work_report->concept &&
				$this->user_id != $work_report_model->user_id &&
				$this->user_id != $work_report_model->added_by_id
			))
		{
			Controller::error(ACCESS);
		}

		$works = ORM::factory('job')->get_all_works_by_job_report_id($work_report->id);
		
		$vote_model = new Vote_Model();

		$items_to_vote = $vote_model->get_all_items_user_can_vote(
			$this->user_id
		);

		$can_vote = FALSE;
		
		$works_to_vote = array();
		
		if (array_key_exists(Vote_Model::WORK, $items_to_vote))
		{
			$works_to_vote = $items_to_vote[Vote_Model::WORK];
			
			foreach ($works as $work)
			{
				if (in_array($work->id, $works_to_vote))
						$can_vote = TRUE;
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
		
		$works_grid->add_new_button(
			url::base().url::current(),
			'Show whole descriptions',
			array
			(
				'id' => 'work_report__show_descr'
			)
		);
		
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

		if (!$work_report_model->concept && $can_vote)
		{
			$works_grid->form_field('vote')
					->type('dropdown')
					->rules('required')
					->options(array
					(
						NULL => '----- '.__('Select vote').' -----'
					))->callback(
						'Votes_Controller::vote_form_field',
						$works_to_vote,
						Vote_Model::WORK
					);
			
			$works_grid->form_field('comment')
					->type('textarea')
					->callback(
						'Votes_Controller::comment_form_field',
						$works_to_vote,
						Vote_Model::WORK
					);
		}

		// access control
		if ($this->acl_check_view('Works_Controller', 'work', $member_id))
		{
			$works_grid->grouped_action_field()
					->add_action()
					->icon_action('show')
					->url('works/show');
		}

		$works_grid->datasource($works);

		$links = array();
		
		// breadcrumbs and links back
		$breadcrumbs = breadcrumbs::add();
		
		if (url_lang::current(1) == 'users')
		{
			// breadcrumbs navigation
			$breadcrumbs->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/'.$work_report_model->user->member->id,
							'ID ' . $work_report_model->user->member->id . ' - ' . $work_report_model->user->member->name,
							$this->acl_check_view(
									'Members_Controller', 'members',
									$work_report_model->user->member->id
							)
					)->enable_translation()
					->link('users/show_by_member/' . $work_report_model->user->member_id,
							'Users',
							$this->acl_check_view(
									'Users_Controller', 'users',
									$work_report_model->user->member_id
							)
					)->disable_translation()
					->link('users/show/'.$work_report_model->user->id,
							$work_report_model->user->name . ' ' . $work_report_model->user->surname .
							' (' . $work_report_model->user->login . ')',
							$this->acl_check_view(
									'Users_Controller','users',
									$work_report_model->user->member_id
							)
					)->enable_translation()
					->link('work_reports/show_by_user/'.$work_report_model->user->id, 'Work reports',
							$this->acl_check_view(
									'Work_reports_Controller', 'work_report',
									$work_report_model->user->member_id
							)
					);
		}
		else
		{
			$breadcrumbs->link('work_reports', 'Work reports',
				$this->acl_check_view('Work_reports_Controller','work_report'));
		}
		
		$breadcrumbs->text('ID '.$work_report_model->id);

		if ($work_report->state == Vote_Model::STATE_NEW &&
			$this->acl_check_edit(
				'Work_reports_Controller', 'work_report', $work_report->member_id
			))
		{
			$links[] = html::anchor(
					url_lang::base().'work_reports/edit/'.$work_report->id,
					__('Edit')
			);
		}
		
		// concept can be viewed only by owner
		if (!(!$this->acl_check_view('Work_reports_Controller', 'work_report', $member_id) || (
				$work_report->concept &&
				$this->user_id != $work_report_model->user_id &&
				$this->user_id != $work_report_model->added_by_id
			)))
		{
			$links[] = html::anchor(
					url_lang::base().'work_reports/export/'.$work_report->id,
					__('Export').' '.help::hint('work_report_export')
			);
		}

		if ($work_report->state == Vote_Model::STATE_NEW && 
			$this->acl_check_delete(
				'Work_reports_Controller', 'work_report', $work_report->member_id
			))
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
			try
			{
				$vote_model = new Vote_Model();
				
				$vote_model->transaction_start();

				// check if not already paid off
				$work_report_model->reload(); // need to reload in transaction
				if ($work_report_model->transfer_id)
				{
					throw new Exception('This work report is already paied off,'
							. ' you cannot vote anymore');
				}
				
				$work_ids	= $_POST['ids'];
				$votes		= $_POST['vote'];
				$comments	= $_POST['comment'];
				
				$approval_template_item_model = new Approval_template_item_Model();

				// re-get all works in transaction
				$works = ORM::factory('job')
						->get_all_works_by_job_report_id($work_report->id);
				$all_works = array();
				foreach ($works as $work)
				{
					$all_works[$work->id] = $work;
				}

				// finding aro group of logged user
				$aro_group = $approval_template_item_model
					->get_aro_group_by_approval_template_id_and_user_id(
						$work_report->approval_template_id,
						$this->user_id,
						$work_report->suggest_amount
				);
				
				$amount = 0;
				
				$states = array
				(
					Vote_Model::STATE_NEW		=> array(),
					Vote_Model::STATE_OPEN		=> array(),
					Vote_Model::STATE_REJECTED	=> array(),
					Vote_Model::STATE_APPROVED	=> array()
				);
				
				$new_votes_count = 0;
				
				// voting user
				$user = new User_Model($this->user_id);
				
				$work_model = new Job_Model();
				
				foreach ($all_works as $work_id => $work)
				{
					// voted and can vote?
					if (in_array($work->id, $works_to_vote) &&
							in_array($work->id, $work_ids))
					{
						$work = new Job_Model($work->id);

						// delete old vote
						$vote_model->remove_vote(
							$this->user_id,
							Vote_Model::WORK,
							$work->id
						);

						$vote		= $votes[$work->id];
						$comment	= $comments[$work->id];

						// new vote is not empty
						if ($vote != '')
						{
							// cannot agree/disagree own work
							if ($vote != Vote_Model::ABSTAIN &&
									$work->user_id == $this->user_id)
							{
								throw new Exception('Cannot agree/disagree own work.');
							}

							// add new vote
							Vote_Model::insert(
								$this->user_id,
								Vote_Model::WORK,
								$work->id,
								$vote,
								$comment,
								$aro_group->id
							);

							$new_votes_count++;
						}
					
						// set up state of work
						$work->state = Vote_Model::get_state($work);
					
						$work->save_throwable();
					}
					
					// work is approved
					if ($work->state == Vote_Model::STATE_APPROVED)
						$amount += $work->suggest_amount;
					
					$states[$work->state][] = $work->id;
				}
				
				// any vote has been added
				if ($new_votes_count)
				{
					// send message about adding vote to all watchers		
					$subject	= mail_message::format('work_report_vote_add_subject');
					$body		= mail_message::format('work_report_vote_add', array
					(
						$user->name.' '.$user->surname,
						$work_report_model->user->name.' '.$work_report_model->user->surname,
						url_lang::base().'work_reports/show/'.$work_report_model->id
					));

					Mail_message_Model::send_system_message_to_item_watchers(
						$subject,
						$body,
						Watcher_Model::WORK_REPORT,
						$work_report_model->id
					);
				}
				
				// no pending works in report
				if (!count($states[Vote_Model::STATE_NEW]) &&
					!count($states[Vote_Model::STATE_OPEN]))
				{
					// at least one work from report has been approved
					if (count($states[Vote_Model::STATE_APPROVED]))
					{
						// send money
						if (Settings::get('finance_enabled') &&
								$work_report_model->payment_type == Job_report_Model::PAYMENT_BY_CREDIT)
						{
							$transfer_id = Transfer_Model::insert_transfer_for_work_approve(
									$work_report_model->user->member_id, $amount
							);
							
							$work_report_model->transfer_id = $transfer_id;
							$work_report_model->save_throwable();
							
							foreach ($states[Vote_Model::STATE_APPROVED] as $approved_work_id)
							{
								$approved_work = new Job_Model($approved_work_id);
								$approved_work->transfer_id = $transfer_id;
								$approved_work->save_throwable();
							}
							
							// reload messages of worker
							ORM::factory('member')->reactivate_messages($member_id);
						}
						
						$subject = mail_message::format('work_report_approve_subject');
						$body = mail_message::format('work_report_approve', array
						(
							$work_report_model->user->name.' '.$work_report_model->user->surname,
							url_lang::base().'work_reports/show/'.$work_report_model->id
						));
					}
					// all works from report has been rejected
					else
					{
						$subject = mail_message::format('work_report_reject_subject');
						$body = mail_message::format('work_report_reject', array
						(
							$work_report_model->user->name.' '.$work_report_model->user->surname,
							url_lang::base().'work_reports/show/'.$work_report_model->id
						));
					}
					
					// send message to all watchers
					Mail_message_Model::send_system_message_to_item_watchers(
						$subject,
						$body,
						Watcher_Model::WORK_REPORT,
						$work_report_model->id
					);
				}
				
				$vote_model->transaction_commit();
				status::success('Votes to work reports has been successfully updated.');
			}
			catch (Exception $e)
			{
				$vote_model->transaction_rollback();
				status::error('Error - Cannot update votes to work reports.', $e);
				Log::add_exception($e);
			}
			
			// redirect
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
		$view->content->state_text = Vote_Model::get_state_name($work_report->state);
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
			
			if (!$this->acl_check_new('Work_reports_Controller', 'work_report', $user->member_id))
				Controller::error(ACCESS);
			
			$selected = $user->id;
			$arr_users[$user->id] = $user->get_full_name() . ' - ' . $user->login;
			
			$member_id = $user->member_id;
		}
		else
		{
			if (!$this->acl_check_new('Work_reports_Controller', 'work_report'))
				Controller::error(ACCESS);
			
			$selected = $this->session->get('user_id');
			
			$concat = "CONCAT(
					COALESCE(surname, ''), ' ',
					COALESCE(name, ''), ' - ',
					COALESCE(login, '')
			)";
			
			$arr_users = ORM::factory('user')
					->select_list('id', $concat);
			
			$member_id = NULL;
		}
		
		// approval templates
		$arr_approval_templates = array();

		if ($this->acl_check_edit('Work_reports_Controller', 'approval_template', $member_id))
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
				if ($this->acl_check_edit('Work_reports_Controller', 'approval_template', $member_id))
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
				
				$work_report_suggest_amount = 0;
				
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
						
						$work_report_suggest_amount += $suggest_amount;

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
				
				// finds all aro ids assigned to vote about this work
				$approval_template_item_model = new Approval_template_item_Model();
				$aro_ids = arr::from_objects(
					$approval_template_item_model->get_aro_ids_by_approval_template_id(
					$work_report->approval_template_id, $work_report_suggest_amount
				), 'id');
				
				$watchers = array_unique(
					array($work_report->user_id, $this->user_id)
					+ $aro_ids
				);

				$watcher_model = new Watcher_Model();

				// add default watchers
				$watcher_model->add_watchers_to_object(
					$watchers,
					Watcher_Model::WORK_REPORT,
					$work_report->id
				);
				
				// end adding
				
				$work_report->transaction_commit();
				status::success('Work report has been successfully added');
				
				if ($user_id)
					$this->redirect('users/show_work_report/', $work_report->id);
				else
					$this->redirect('work_reports/show/', $work_report->id);
			}
			catch (Exception $e)
			{
				$work_report->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cant add new work report.', $e);
			}
		}
		
		$breadcrumbs = breadcrumbs::add();
		
		if ($user_id)
		{
			// breadcrumbs navigation
			$breadcrumbs->link('members/show_all', 'Members',
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
					->link('work_reports/show_by_user/'.$user->id, 'Work reports',
							$this->acl_check_view(
									'Work_reports_Controller', 'work_report',
									$user->member_id
							)
					);
		}
		else
		{
			$breadcrumbs->link('work_reports', 'Work reports',
				$this->acl_check_view('Work_reports_Controller','work_report'));
		}
		
		$breadcrumbs->text('Add new');

		$view = new View('main');
		$view->title = __('Add new work report');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('work_reports/add');
		$view->content->arr_users = $arr_users;
		$view->content->selected_user = $selected;
		$view->content->arr_approval_templates = $arr_approval_templates;
		$view->content->member_id = $member_id;
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
				'Work_reports_Controller', 'work_report', $work_report->user->member_id
			) || (
				$work_report->concept &&
				$this->user_id != $work_report->user_id &&
				$this->user_id != $work_report->added_by_id
			))
		{
			Controller::error(ACCESS);
		}
		
		// test if path is from user profile
		$is_from_user = (Path::instance()->uri(TRUE)->previous(0, 1) == 'users'
			|| Path::instance()->uri(TRUE)->previous(1, 1) == 'show_by_user');

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

		if ($this->acl_check_view('Work_reports_Controller', 'approval_template', $work_report->user->member_id))
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
				
				$old_approval_template_id = $work_report->approval_template_id;
				
				if ($this->acl_check_view('Work_reports_Controller', 'approval_template', $work_report->user->member_id))
				{
					$work_report->approval_template_id = intval($form_data['approval_template_id']);
				}
				
				$work_report->description = $form_data['description'];
				$work_report->price_per_hour = $price_per_hour;
				$work_report->price_per_km = $price_per_km;
				$work_report->payment_type = intval($form_data['payment_type']);
				$work_report->save_throwable();
				
				// update state of approval template
				Approval_template_Model::update_state(
					$work_report->approval_template_id
				);
				
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
				
				$work_report_suggest_amount = 0;
				
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
						
						$work_report_suggest_amount += $suggest_amount;

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
				
				// approval template has been changed
				if ($work_report->approval_template_id != $old_approval_template_id)
				{
					// update state of old approval template
					Approval_template_Model::update_state(
						$old_approval_template_id
					);
					
					$watcher_model = new Watcher_Model();
					
					// remove old watchers
					$watcher_model->delete_watchers_by_object(
						Watcher_Model::WORK_REPORT,
						$work_report->id
					);
					
					// finds all aro ids assigned to vote about this work report
					$approval_template_item_model = new Approval_template_item_Model();
					$aro_ids = arr::from_objects(
						$approval_template_item_model->get_aro_ids_by_approval_template_id(
						$work_report->approval_template_id, $work_report_suggest_amount
					), 'id');
					
					$watchers = array_unique(
						array($work_report->user_id, $this->user_id)
						+ $aro_ids
					);

					// add new watchers
					$watcher_model->add_watchers_to_object(
						$watchers,
						Watcher_Model::WORK_REPORT,
						$work_report->id
					);
				}
				
				//// sent information
				
				if (!$work_report->concept)
				{	
					$subject = mail_message::format('work_report_update_subject');
					$body = mail_message::format('work_report_update', array
					(
						$work_report->user->name . ' ' . $work_report->user->surname,
						url_lang::base().'work_reports/show/'.$work_report->id
					));
					
					// send message about work report update to all watchers
					Mail_message_Model::send_system_message_to_item_watchers(
						$subject,
						$body,
						Vote_Model::WORK_REPORT,
						$work_report->id
					);
				}
				
				//// finish
				
				$work_report->transaction_commit();
				status::success('Work report has been successfully updated');
			}
			catch (Exception $e)
			{
				$work_report->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cant edit work report.', $e);
			}
			
			if ($is_from_user)
				$this->redirect('users/show_work_report/', $work_report->id);
			else
				$this->redirect('work_reports/show/', $work_report->id);
		}
		
		// breadcrumbs and links back
		$breadcrumbs = breadcrumbs::add();
		
		if ($is_from_user)
		{
			// breadcrumbs navigation
			$breadcrumbs->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/'.$work_report->user->member->id,
							'ID ' . $work_report->user->member->id . ' - ' . $work_report->user->member->name,
							$this->acl_check_view(
									'Members_Controller', 'members',
									$work_report->user->member->id
							)
					)->enable_translation()
					->link('users/show_by_member/' . $work_report->user->member_id,
							'Users',
							$this->acl_check_view(
									'Users_Controller', 'users',
									$work_report->user->member_id
							)
					)->disable_translation()
					->link('users/show/'.$work_report->user->id,
							$work_report->user->name . ' ' . $work_report->user->surname .
							' (' . $work_report->user->login . ')',
							$this->acl_check_view(
									'Users_Controller','users',
									$work_report->user->member_id
							)
					)->enable_translation()
					->link('work_reports/show_by_user/'.$work_report->user->id, 'Work reports',
							$this->acl_check_view(
									'Work_reports_Controller', 'work_report',
									$work_report->user->member_id
							)
					)
					->link('users/show_work_report/'.$work_report->id, 'ID '.$work_report->id,
							$this->acl_check_view(
									'Work_reports_Controller', 'work_report',
									$work_report->user->member_id
							)
					);
		}
		else
		{
			$breadcrumbs->link('work_reports', 'Work reports',
				$this->acl_check_view('Work_reports_Controller','work_report'))
			->link('work_reports/show/'.$work_report->id, 'ID '.$work_report->id,
						$this->acl_check_view(
								'Work_reports_Controller', 'work_report',
								$work_report->user->member_id
						)
				);
		}
		
		$breadcrumbs->text('Edit');

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

		if (!$this->acl_check_delete('Work_reports_Controller', 'work_report', $work_report->member_id))
		{
			Controller::error(ACCESS);
		}

		// work is locked?
		if ($work_report->state > 0)
		{
			status::warning('It is not possible delete locked work report.');
			url::redirect('work_reports/show/' . $work_report->id);
		}
		
		// test if path is from user profile
		$is_from_user = (Path::instance()->uri(TRUE)->previous(0, 1) == 'users'
			|| Path::instance()->uri(TRUE)->previous(1, 1) == 'show_by_user');
		
		try
		{
			$work_report_model->transaction_start();
			
			$approval_template_id = $work_report->approval_template_id;
			
			$work_report_user_id = $work_report_model->user_id;

			// sent information about delete
			if (!$work_report->concept)
			{
				$subject = mail_message::format('work_report_delete_subject');
				$body = mail_message::format('work_report_delete', array
				(
					$work_report_model->user->name.' '.$work_report_model->user->surname,
					$work_report_model->description,
					url_lang::base().'work_reports/show/'.$work_report_model->id
				));

				// send message about work report delete to all watchers
				Mail_message_Model::send_system_message_to_item_watchers(
					$subject,
					$body,
					Vote_Model::WORK_REPORT,
					$work_report_model->id
				);
			}
			
			$watcher_model = new Watcher_Model();
			
			// remove all watchers
			$watcher_model->delete_watchers_by_object(
				Watcher_Model::WORK_REPORT, $work_report_model->id
			);

			// delete report (works are deleted by forein key)
			$work_report_model->delete_throwable();

			// set up state of approval template
			Approval_template_Model::update_state($approval_template_id);
			
			$work_report_model->transaction_commit();
			status::success('Work report has been successfully deleted');
		}
		catch (Exception $e)
		{
			$work_report_model->transaction_rollback();
			status::error('Error - Cannot delete work report.', $e);
			Log::add_exception($e);
		}
		
		if ($is_from_user)
			$this->redirect('work_reports/show_by_user/'.$work_report_user_id);
		else
			$this->redirect('work_reports/show_all');
	}
	
	/**
	 * Generates XML file with work report for Microsoft Excel 2007 and newer
	 * 
	 * @param integer $work_report_id
	 */
	public function export($work_report_id = NULL)
	{
		if (!$work_report_id || !is_numeric($work_report_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$work_report_model = new Job_report_Model($work_report_id);
		$work_report = $work_report_model->get_work_report();
		
		$member_id = $work_report_model->user->member_id;
		
		// concept can be viewed only by owner
		if (!$this->acl_check_view('Work_reports_Controller', 'work_report', $member_id) || (
				$work_report->concept &&
				$this->user_id != $work_report_model->user_id &&
				$this->user_id != $work_report_model->added_by_id
			))
		{
			Controller::error(ACCESS);
		}
		
		// load works
		$works = ORM::factory('job')->get_all_works_by_job_report_id($work_report->id);
		
		$xml = new ExcelWriterXML(url::title($work_report_model->user->get_full_name().'_'.$work_report->id).'.xml');
		
		// set document prosperites
		$xml->docAuthor($work_report_model->user->get_full_name());
		$xml->docCompany(Settings::get('title'));
		
		$sheet_name = '';
		if (empty($work_report->type))
		{
			$sheet_name = __('Grouped works');
		}
		else
		{
			$sheet_name = __('Work report per month').' '.__('for', '', 1).' '.__(date::$months[intval(substr($work_report->type, 5, 6))]);
		}
		
		// create new sheet
		$sheet = $xml->addSheet($sheet_name);
		$xml->docTitle($sheet_name);
		
		// prepare styles
		$header = $xml->addStyle('header');
		$header->fontBold();
		
		$description = $xml->addStyle('description');
		$description->fontBold();
		$description->fontSize(14);
		
		
		$date = $xml->addStyle('date');
		$date->numberFormat('yyyy-mm-dd');
		
		$number = $xml->addStyle('number');
		$number->numberFormat('0.00');
		
		$sum_number = $xml->addStyle('sum_number');
		$sum_number->numberFormat('0.00');
		$sum_number->fontBold();
		
		$sheet->columnWidth(1, 55);
		$sheet->columnWidth(2, 700);
		$sheet->columnWidth(5, 100);
		
		$sheet->cellMerge(1, 1, 4, 0);
		$sheet->writeString(1, 1, $work_report->description, 'description');
		
		$i = 2; // first row of data in xml
		
		// create header
		$sheet->writeString($i, 1, __('Date'), 'header');
		$sheet->writeString($i, 2, __('Description'), 'header');
		$sheet->writeString($i, 3, __('Hours'), 'header');
		$sheet->writeString($i, 4, __('Km'), 'header');
		$sheet->writeString($i, 5, __('Suggest amount'), 'header');
		
		$i++;
		
		// fill document
		foreach ($works as $work)
		{
			$sheet->writeDateTime($i, 1, $work->date.'T00:00:00.000', 'date');
			$sheet->writeString($i, 2, $work->description);
			$sheet->writeNumber($i, 3, $work->hours, 'number');
			$sheet->writeNumber($i, 4, $work->km, 'number');
			$sheet->writeNumber($i, 5, $work->suggest_amount, 'number');
			$i++;
		}
		
		// append total sum of hours, kms and suggest amount
		$sheet->writeNumber($i+1, 3, $work_report->hours, 'sum_number');
		$sheet->writeNumber($i+1, 4, $work_report->km, 'sum_number');
		$sheet->writeNumber($i+1, 5, $work_report->suggest_amount, 'sum_number');
		
		$xml->sendHeaders();
		$xml->writeData();
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
		
		try
		{
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
			
			// test if path is from user profile
			$is_from_user = (Path::instance()->uri(TRUE)->previous(0, 1) == 'users'
			|| Path::instance()->uri(TRUE)->previous(1, 1) == 'show_by_user');
		
			$work_report->transaction_start();
			
			$work_report->concept = FALSE;
			
			$work_report->save_throwable();
			
			$subject = mail_message::format('work_report_add_subject');
			$body = mail_message::format('work_report_add', array
			(
				$work_report->user->name . ' ' . $work_report->user->surname,
				url_lang::base().'work_reports/show/'.$work_report->id
			));

			// send message about work report adding to all watchers
			Mail_message_Model::send_system_message_to_item_watchers(
				$subject,
				$body,
				Vote_Model::WORK_REPORT,
				$work_report->id
			);
			
			$work_report->transaction_commit();
			status::success('Concept of report has been sended for approval.');
		}
		catch (Exception $e)
		{
			$work_report->transaction_rollback();
			status::error('Error - Cannot send concept of report for approval.', $e);
			Log::add_exception($e);
		}
		
		if ($is_from_user)
			$this->redirect('users/show_work_report/', $work_report->id);
		else
			$this->redirect('work_reports/show/', $work_report->id);
	}

}
