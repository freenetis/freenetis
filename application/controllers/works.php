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
 * Controllers manages user's works and it's (dis)approval directed by
 * approval types and templates.
 * 
 * Each work has to be approved by other pre-defined users by their votes.
 * 
 * @author Michal Kliment
 * @package Controller
 */
class Works_Controller extends Controller
{
	/**
	 * Only checks whether works are enabled
	 * 
	 * @author Michal Kliment
	 */
	public function __construct()
	{
	    parent::__construct();
	    
	    // approval are not enabled
	    if (!Settings::get('works_enabled'))
			Controller::error (ACCESS);
	}
	
	/**
	 * Index redirects to work pending
	 */
	public function index()
	{
		url::redirect('works/show_all');
	}

	/**
	 * Function to show all works
	 * 
	 * @author Michal Kliment
	 * @param number $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param number $page
	 */
	public function show_all(
			$limit_results = 20, $order_by = 'date',
			$order_by_direction = 'DESC',
			$page_word = null, $page = 1)
	{
		/**
		 * @todo Add own AXO
		 */
		
		// acccess control
		if (!$this->acl_check_view('Works_Controller','work'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		$filter_form = new Filter_form('j');
		
		$filter_form->add('state')
			->type('select')
			->values(Vote_Model::get_states());
		
		$filter_form->add('uname')
			->callback('json/user_fullname')
			->label('Worker');
		
		$filter_form->add('description');
		
		$filter_form->add('suggest_amount')
			->type('number');
		
		$filter_form->add('date')
			->type('date');
		
		$filter_form->add('hours')
			->type('number');
		
		$filter_form->add('km')
			->type('number');

		$work_model = new Job_Model();
		
		// hide grid on its first load (#442)
		$hide_grid = Settings::get('grid_hide_on_first_load') && $filter_form->is_first_load();
		
		if (!$hide_grid)
		{
			try
			{
				$total_works = $work_model->count_all_works($filter_form->as_sql());

				if (($sql_offset = ($page - 1) * $limit_results) > $total_works)
					$sql_offset = 0;

				$works = $work_model->get_all_works(
					$sql_offset, (int)$limit_results, $order_by,
					$order_by_direction, $filter_form->as_sql()
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

			$vote_model = new Vote_Model();

			$items_to_vote = $vote_model->get_all_items_user_can_vote(
				$this->user_id
			);

			$works_to_vote = array();

			if (array_key_exists(Vote_Model::WORK, $items_to_vote))
			{
				$works_to_vote = $items_to_vote[Vote_Model::WORK];
			}
		}

		// create grid
		$grid = new Grid('works/show_all', NULL, array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 20,
			'selector_min'				=> 20,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/works/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> isset($total_works) ? $total_works : 0,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'				=> $filter_form
		));

		/**
		 * @todo Add own AXO
		 */
		if ($this->acl_check_new('Works_Controller','work'))
		{
			$grid->add_new_button('works/add', __('Add new work'));
		}

		$grid->order_field('id')
				->label(__('Id'));
		
		$grid->order_link_field('user_id')
				->link('users/show', 'uname')
				->label('Worker');
		
		$grid->order_callback_field('description')
				->label(__('Description'))
				->callback('callback::limited_text');
		
		$grid->order_field('date')
				->label(__('Date'));
		
		$grid->order_field('hours')
				->label(__('Hours'));
		
		$grid->order_field('km')
				->label(__('Km'));
		
		$grid->order_callback_field('suggest_amount')
				->label(__('Suggest amount'))
				->callback('callback::money');
		
		$grid->order_callback_field('approval_state')
				->label(__('State'))
				->help(help::hint('approval_state'))
				->callback('callback::vote_state_field');
		
		$grid->order_callback_field('comments_count')
				->label(__('Comments'))
				->callback('callback::comments_field')
				->class('center');

		// user can vote -> show columns for form items
		if (!$hide_grid && count($works_to_vote))
		{
			$grid->order_form_field('vote')
				->label(__('Vote'))
				->type('dropdown')
				->options(array
				(
				    NULL => '----- '.__('Select vote').' -----'
				))
				->callback(
					'Votes_Controller::vote_form_field',
					$works_to_vote,
					Vote_Model::WORK
				);
			
			$grid->order_form_field('comment')
				->label(__('Comment'))
				->type('textarea')
				->callback(
					'Votes_Controller::comment_form_field',
					$works_to_vote
				);
		}
		
		$actions = $grid->grouped_action_field();

		// access control
		if ($this->acl_check_view('Works_Controller','work'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('works/show');
		}
		
		// access control
		if ($this->acl_check_edit('Works_Controller','work'))
		{
			$actions->add_conditional_action('id')
					->icon_action('edit')
					->condition('is_item_new')
					->url('works/edit')
					->class('popup_link');
		}
		
		// access control
		if ($this->acl_check_delete('Works_Controller','work'))
		{
			$actions->add_conditional_action('id')
					->icon_action('delete')
					->condition('is_item_new')
					->url('works/delete')
					->class('delete_link');
		}
		
		if (!$hide_grid)
			$grid->datasource($works);

		// form is submited
		if (isset ($_POST) && count ($_POST) > 1)
		{
			try
			{
				$vote_model = new Vote_Model();
				
				$vote_model->transaction_start();
				
				$work_ids	= $_POST['ids'];
				$votes		= $_POST['vote'];
				$comments	= $_POST['comment'];

				$approval_template_item_model = new Approval_template_item_Model();
				
				// voting user
				$user = new User_Model($this->user_id);

				foreach ($work_ids as $work_id)
				{
					// user cannot vote about work
					if (!in_array($work_id, $works_to_vote))
						continue;

					$work = $work_model->where('id', $work_id)->find();

					if (!$work || !$work->id)
						continue;
					
					// finding aro group of logged user
					$aro_group = $approval_template_item_model
						->get_aro_group_by_approval_template_id_and_user_id(
							$work->approval_template_id,
							$this->user_id,
							$work->suggest_amount
					);
					
					$new_vote		= $votes[$work->id];
					$new_comment	= $comments[$work->id];
					
					// cannot agree/disagree own work
					if ($new_vote != Vote_Model::ABSTAIN &&
						$work->user_id == $this->user_id)
					{
						continue;	
					}
					
					$vote = $vote_model->where(array
					(
						'user_id'	=> $this->user_id,
						'type'		=> Vote_Model::WORK,
						'fk_id'		=> $work->id
					))->find();
					
					// vote already exists
					if ($vote && $vote->id)
					{
						if ($new_vote != '' && $new_vote == $vote->vote)
							continue;
						
						// new vote is not empty and different to old, change old
						if ($new_vote != '')
						{
							$vote->vote = $new_vote;
							$vote->comment = $new_comment;
							$vote->time = date('Y-m-d H:i:s');
							$vote->save_throwable();
							
							$subject = mail_message::format('work_vote_update_subject');
							$body = mail_message::format('work_vote_update', array
							(
								$user->name.' '.$user->surname,
								$work->user->name.' '.$work->user->surname,
								url_lang::base().'works/show/'.$work->id
							));
						}
						// new vote is empty, delete old
						else
						{
							$vote->delete_throwable();
							
							$subject = mail_message::format('work_vote_delete_subject');
							$body = mail_message::format('work_vote_delete', array
							(
								$user->name.' '.$user->surname,
								$work->user->name.' '.$work->user->surname,
								url_lang::base().'works/show/'.$work->id
							));
						}
						
						// send message about adding vote to all watchers
						Mail_message_Model::send_system_message_to_item_watchers(
							$subject,
							$body,
							Vote_Model::WORK,
							$work->id
						);
					}
					// new vote is not empty
					elseif ($new_vote != '')
					{
						// add new vote
						Vote_Model::insert(
							$this->user_id,
							Vote_Model::WORK,
							$work->id,
							$new_vote,
							$new_comment,
							$aro_group->id
						);
						
						$subject = mail_message::format('work_vote_add_subject');
						$body = mail_message::format('work_vote_add', array
						(
							$user->name.' '.$user->surname,
							$work->user->name.' '.$work->user->surname,
							url_lang::base().'works/show/'.$work->id
						));
						
						// send message about adding vote to all watchers
						Mail_message_Model::send_system_message_to_item_watchers(
							$subject,
							$body,
							Vote_Model::WORK,
							$work->id
						);
					}
					
					// set up state of work
					$work->state = Vote_Model::get_state($work);
					
					switch ($work->state)
					{
						// work was approved
						case Vote_Model::STATE_APPROVED:
							
							if (Settings::get('finance_enabled'))
							{
								// create transfer
								$work->transfer_id = Transfer_Model::insert_transfer_for_work_approve(
									$work->user->member_id,
									$work->suggest_amount
								);
							}
							
							$subject = mail_message::format('work_approve_subject');
							$body = mail_message::format('work_approve', array
							(
								$work->user->name.' '.$work->user->surname,
								url_lang::base().'works/show/'.$work->id
							));
							
							// send messages about work approve to all watchers
							Mail_message_Model::send_system_message_to_item_watchers(
								$subject,
								$body,
								Vote_Model::WORK,
								$work->id
							);
							
							break;
						
						// work was rejected
						case Vote_Model::STATE_REJECTED:
							
							$subject = mail_message::format('work_reject_subject');
							$body = mail_message::format('work_reject', array
							(
								$work->user->name.' '.$work->user->surname,
								url_lang::base().'works/show/'.$work->id
							));
							
							// send messages about work reject to all watchers
							Mail_message_Model::send_system_message_to_item_watchers(
								$subject,
								$body,
								Vote_Model::WORK,
								$work->id
							);
							
							break;
					}
					
					$work->save_throwable();
					
					// update state of approval template
					Approval_template_Model::update_state(
						$work->approval_template_id
					);
					
					ORM::factory ('member')
						->reactivate_messages($work->user->member_id);
				}
			
				$vote_model->transaction_commit();
				status::success('Votes has been successfully updated.');
			}
			catch (Exception $e)
			{
				$vote_model->transaction_rollback();
			}
			
			url::redirect(url::base(TRUE).url::current(TRUE));
		}
		
		$title = __('Works');

		$view = new View('main');
		$view->title = $title;
		$view->breadcrumbs = $title;
		$view->content = new View('show_all');
		$view->content->headline = __('List of all works');
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function to show all works of user
	 * 
	 * @author Michal Kliment
	 * @param number $user_id
	 */
	public function show_by_user ($user_id = NULL)
	{
		if (!$user_id || !is_numeric ($user_id))
		    Controller::warning (PARAMETER);

		$user = new User_Model($user_id);

		if (!$user->id)
			Controller::error(RECORD);

		if (!$this->acl_check_view('Works_Controller', 'work', $user->member_id))
			Controller::error(ACCESS);

		$work_model = new Job_Model();

		$pending_works = $work_model->get_all_pending_works_by_user($user->id);

		$approval_template_item_model = new Approval_template_item_Model();

		$total_pending = array();
		$total_pending['hours'] = 0;
		$total_pending['kms'] = 0;
		$total_pending['suggest_amount'] = 0;

		foreach ($pending_works as $pending_work)
		{
			$total_pending['hours'] += $pending_work->hours;
			$total_pending['kms'] += $pending_work->km;
			$total_pending['suggest_amount'] += $pending_work->suggest_amount;
		}

		// create grid
		$pending_works_grid = new Grid(url_lang::base().'works/rejected', '', array
		(
			'use_paginator' => false,
			'use_selector' => false,
			'total_items' =>  count ($pending_works)
		));

		if ($this->acl_check_new('Works_Controller','work', $user->member_id))
		{
			$pending_works_grid->add_new_button(
					url_lang::base().'works/add/'.$user->id,
					__('Add new work')
			);
		}

		$pending_works_grid->field('id')
				->label(__('Id'));
		
		$pending_works_grid->callback_field('description')
				->label(__('Description'))
				->callback('callback::limited_text');
		
		$pending_works_grid->field('date')
				->label(__('Date'));
		
		$pending_works_grid->field('hours')
				->label(__('Hours'));
		
		$pending_works_grid->field('km')
				->label(__('Km'));
		
		$pending_works_grid->callback_field('suggest_amount')
				->label(__('Suggest amount'))
				->callback('callback::money');
		
		$pending_works_grid->callback_field('approval_state')
				->label(__('State'))
				->help(help::hint('approval_state'))
						->callback('callback::vote_state_field');
		
		$pending_works_grid->callback_field('comments_count')
				->label(__('comments'))
				->callback('callback::comments_field');
		
		$actions = $pending_works_grid->grouped_action_field();

		// access control
		if ($this->acl_check_view('Works_Controller','work',$user->member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('users/show_work');
		}
		
		// access control
		if ($this->acl_check_view('Works_Controller','work',$user->member_id))
		{
			$actions->add_conditional_action('id')
					->icon_action('edit')
					->condition('is_item_new')
					->url('works/edit')
					->class('popup_link');
		}
		
		// access control
		if ($this->acl_check_delete('Works_Controller','work',$user->member_id))
		{
			$actions->add_conditional_action('id')
					->icon_action('delete')
					->condition('is_item_new')
					->url('works/delete')
					->class('delete_link');
		}

		$pending_works_grid->datasource($pending_works);

		$approved_works = $work_model->get_all_approved_works_by_user($user->id);

		$total_approved = array();
		$total_approved['hours'] = 0;
		$total_approved['kms'] = 0;
		$total_approved['rating'] = 0;
		
		foreach ($approved_works as $approved_work)
		{
			$total_approved['hours'] += $approved_work->hours;
			$total_approved['kms'] += $approved_work->km;
			$total_approved['rating'] += $approved_work->rating;
		}

		// create grid
		$approved_works_grid = new Grid('works/rejected', '', array
		(
			'use_paginator'	=> false,
			'use_selector'	=> false,
			'total_items'	=> count ($approved_works)
		));

		$approved_works_grid->field('id')
				->label(__('Id'));
		
		$approved_works_grid->callback_field('description')
				->label(__('Description'))
				->callback('callback::limited_text');
		
		$approved_works_grid->field('date')
				->label(__('Date'));
		
		$approved_works_grid->field('hours')
				->label(__('Hours'));
		
		$approved_works_grid->field('km')
				->label(__('Km'));
		
		$approved_works_grid->callback_field('rating')
				->label(__('Rating'))
				->callback('callback::money');
		
		$approved_works_grid->callback_field('approval_state')
				->label(__('State'))
				->help(help::hint('approval_state'))
				->callback('callback::vote_state_field');
		
		$approved_works_grid->callback_field('comments_count')
				->label(__('comments'))
				->callback('callback::comments_field');
		
		$actions = $approved_works_grid->grouped_action_field();

		// access control
		if ($this->acl_check_view('Works_Controller', 'work', $user->member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('users/show_work');
		}

		$approved_works_grid->datasource($approved_works);

		$rejected_works = $work_model->get_all_rejected_works_by_user($user->id);

		$total_rejected = array();
		$total_rejected['hours'] = 0;
		$total_rejected['kms'] = 0;
		$total_rejected['suggest_amount'] = 0;

		foreach ($rejected_works as $rejected_work)
		{
			$total_rejected['hours'] += $rejected_work->hours;
			$total_rejected['kms'] += $rejected_work->km;
			$total_rejected['suggest_amount'] += $rejected_work->suggest_amount;
		}

		// create grid
		$rejected_works_grid = new Grid('works/rejected', '', array(
			//'separator' => '',
			'use_paginator' => false,
			'use_selector' => false,
			'total_items' =>  count ($rejected_works)
		));

		$rejected_works_grid->field('id')
				->label(__('Id'));
		
		$rejected_works_grid->callback_field('description')
				->label(__('Description'))
				->callback('callback::limited_text');
		
		$rejected_works_grid->field('date')
				->label(__('Date'));
		
		$rejected_works_grid->field('hours')
				->label(__('Hours'));
		
		$rejected_works_grid->field('km')
				->label(__('Km'));
		
		$rejected_works_grid->callback_field('suggest_amount')
				->label(__('Suggest amount'))
				->callback('callback::money');
		
		$rejected_works_grid->callback_field('approval_state')
				->label(__('State'))->help(help::hint('approval_state'))
						->callback('callback::vote_state_field');
		
		$rejected_works_grid->callback_field('comments_count')
				->label(__('comments'))
				->callback('callback::comments_field');
		
		$actions = $rejected_works_grid->grouped_action_field();

		// access control
		if ($this->acl_check_view('Works_Controller','work', $user->member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('users/show_work');
		}

		$rejected_works_grid->datasource($rejected_works);

		
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
				->text('Works');

		$view = new View('main');
		$view->title = __('List of works of user').' '.$user->name.' '.$user->surname;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('works/show_by_user');
		$view->content->headline = __('List of works of user') . ' '
				. $user->name . ' ' . $user->surname . ' '
				. help::hint('work_description');
		$view->content->user = $user;
		$view->content->pending_works_grid = $pending_works_grid;
		$view->content->approved_works_grid = $approved_works_grid;
		$view->content->rejected_works_grid = $rejected_works_grid;
		$view->content->total_pending = $total_pending;
		$view->content->total_approved = $total_approved;
		$view->content->total_rejected = $total_rejected;
		$view->render(TRUE);
	}

	/**
	 * Function to show work (by private functions show_opened and show_closed)
	 * 
	 * @author Michal Kliment
	 * @param number $work_id
	 */
	public function show($work_id = NULL)
	{
		// bad parameter
		if (!$work_id || !is_numeric($work_id))
			Controller::warning(PARAMETER);

		$work = new Job_Model($work_id);

		// record doesn't exist
		if (!$work->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view('Works_Controller', 'work', $work->user->member_id))
			Controller::error(ACCESS);

		// breadcrumbs navigation
		$this->breadcrumbs = breadcrumbs::add();

		if ($work->job_report_id)
		{
			$this->breadcrumbs->link(
				'work_reports/show_all', 'Work reports',
				$this->acl_check_view('Works_Controller', 'work')
			);
			
			$this->breadcrumbs->link(
				'work_reports/show/' . $work->job_report_id,
				text::limit_chars($work->job_report->description, 40)
			);
		}
		else
		{
			if (url_lang::current(1) == 'users')
			{
				$this->breadcrumbs->link('members/show_all', 'Members',
								$this->acl_check_view('Members_Controller', 'members'))
						->disable_translation()
						->link('members/show/'.$work->user->member->id,
								'ID ' . $work->user->member->id . ' - ' .
								$work->user->member->name,
								$this->acl_check_view(
										'Members_Controller', 'members',
										$work->user->member->id
								)
						)->enable_translation()
						->link('users/show_by_member/' . $work->user->member_id,
								'Users',
								$this->acl_check_view(
										'Users_Controller', 'users',
										$work->user->member_id
								)
						)->disable_translation()
						->link('users/show/'.$work->user->id,
								$work->user->name . ' ' . $work->user->surname .
								' (' . $work->user->login . ')',
								$this->acl_check_view(
										'Users_Controller','users',
										$work->user->member_id
								)
						)->enable_translation()
						->link('works/show_by_user/'.$work->user->id, 'Works',
								$this->acl_check_view(
										'Works_Controller', 'work',
										$work->user->member_id
								)
						);
			}
			else
			{
				$this->breadcrumbs->link('works/show_all', 'Works',
					$this->acl_check_view('Works_Controller', 'work'));
				
			}
		}

		$this->breadcrumbs->disable_translation()
				->text(__('ID').' '.$work->id);

		if ($work->state <= 1)
			// work is opened
			self::show_opened ($work);
		else
			// work is closed
			self::show_closed ($work);
	}

	/**
	 * Private function to show opened work
	 * 
	 * @author Michal Kliment
	 * @param Job_Model $work
	 */
	private function show_opened($work = NULL)
	{
		// bad parameter
		if (!$work)
			Controller::warning(PARAMETER);

		$approval_template_item_model = new Approval_template_item_Model();
		
		$suggest_amount = $work->suggest_amount;
		
		if ($work->job_report_id)
		{
			$suggest_amount = $work->job_report->get_suggest_amount();
		}
		
		$aro_groups = $approval_template_item_model->get_aro_groups_by_approval_template_id(
				$work->approval_template_id, $suggest_amount
		);

		$user_aro_group = $approval_template_item_model->get_aro_group_by_approval_template_id_and_user_id(
				$work->approval_template_id, $this->session->get('user_id'),
				$suggest_amount
		);

		$i = 0;
		$vote_groups = array();
		$vote_grids = array();
		$total_votes = array();
		$sums = array();
		$agrees = array();
		$percents = array();
		$user_vote = NULL;

		foreach ($aro_groups as $aro_group)
		{
			$vote_groups[$i] = $aro_group->name;
			
			$vote_model = new Vote_Model();
			$votes = $vote_model->select(
						'votes.id', 'votes.user_id',
						'CONCAT(users.name,\' \',users.surname) as uname',
						'vote', 'votes.comment', 'time'
					)->join('users','users.id', 'votes.user_id')
					->where('votes.type', Vote_Model::WORK)
					->where('fk_id', $work->id)
					->where('aro_group_id', $aro_group->id)
					->find_all();

			$total_votes[$i] = count($votes);
			$sums[$i] = 0;
			$agrees[$i] = 0;
			$user_vote = NULL;

			foreach ($votes as $vote)
			{
				if ($this->session->get('user_id')==$vote->user_id)
					$user_vote = $vote->vote;

				$sums[$i] += $vote->vote;

				if ($vote->vote == 0)
				{
					$total_votes[$i]--;
					continue;
				}

	    			if ($vote->vote == 1)
				{
					$agrees[$i]++;
					continue;
				}
			}

			// create grid
			$vote_grids[$i] = new Grid('works/show/'.$work->id, '', array
			(
				'use_paginator'				=> false,
				'use_selector'				=> false,
				'selector_increace'			=>  200,
				'selector_min'				=> 200,
				'selector_max_multiplier'	=> 10,
				'uri_segment'				=> 'page',
				'style'						=> 'classic',
			));
			
			$vote_rights = $approval_template_item_model->check_user_vote_rights(
					$work, Vote_Model::WORK, $this->user_id, $suggest_amount
			);

			if ($user_aro_group &&
				$user_aro_group->id == $aro_group->id &&
				!$work->job_report_id &&
				!$user_vote &&
				$vote_rights)
			{
				$vote_grids[$i]->add_new_button(
					url_lang::base().'votes/add/'.Vote_Model::WORK.'/'.$work->id,
					__('Add vote'), array('class' => 'popup_link')
				);
			}
			
			$vote_grids[$i]->callback_field('vote')
					->label(__('Vote'))
					->callback('callback::vote');
			
			$vote_grids[$i]->link_field('user_id')
					->link('users/show', 'uname')
					->label('Worker');
			
			$vote_grids[$i]->callback_field('comment');
			
			$vote_grids[$i]->callback_field('time')
					->label(__('Time'))
					->callback('callback::datetime');
			
			$actions = $vote_grids[$i]->grouped_action_field();
			
			$actions->add_conditional_action()
					->icon_action('edit')
					->url('votes/edit')
					->condition('is_own')
					->class('popup_link');
			
			$actions->add_conditional_action()
					->icon_action('delete')
					->url('votes/delete')
					->condition('is_own')
					->class('delete_link');
				
			$vote_grids[$i]->datasource($votes);

			$percents[$i] = ($total_votes[$i]) ? round($agrees[$i] / $total_votes[$i] * 100, 1) : 0;
			$i++;
		}

		$links = array();

		$state_text = __('Pending');			

		if (!$work->job_report_id)
		{
			if ($this->acl_check_edit('Works_Controller','work',$work->user->member_id) &&
				$work->state == 0)
			{
				$links[] = html::anchor('works/edit/'.$work->id,__('Edit'));
			}
			
			if ($this->acl_check_delete('Works_Controller','work',$work->user->member_id) &&
				$work->state == 0)
			{
				$links[] = html::anchor(
						'works/delete/'.$work->id,__('Delete'),
						array('class' => 'delete_link')
				);
			}
			
			$links = implode(" | ", $links);
		}

		if ($this->acl_check_view('Comments_Controller', 'works', $work->user->member_id))
		{
			$comment_model = new Comment_Model();
			$comments = $comment_model->get_all_comments_by_comments_thread($work->comments_thread_id);

			$comments_grid = new Grid('members', null,array
			(
				'separator'		   		=> '<br /><br />',
				'use_paginator'	   		=> false,
				'use_selector'	   		=> false,
			));

			$url = ($work->comments_thread_id) ?
					url_lang::base().'comments/add/'.$work->comments_thread_id :
					url_lang::base().'comments/add_thread/job/'.$work->id;

			$comments_grid->add_new_button($url, __('Add comment to work'));

			$comments_grid->field('text')
					->label(__('Text'));

			$comments_grid->link_field('user_id')
					->link('users/show', 'user_name')
					->label('User');

			$comments_grid->field('datetime')
					->label(__('Time'));
			
			$actions = $comments_grid->grouped_action_field();
			
			$actions->add_conditional_action()
					->icon_action('edit')
					->url('comments/edit')
					->condition('is_own');
			
			$actions->add_conditional_action()
					->icon_action('delete')
					->url('comments/delete')
					->condition('is_own')
					->class('delete_link');

			$comments_grid->datasource($comments);
		}

		$view = new View('main');
		$view->title = __('Show work');
		$view->breadcrumbs = $this->breadcrumbs->html();
		$view->content = new View('works/show');
		$view->content->work = $work;
		$view->content->links = $links;
		$view->content->vote_groups = $vote_groups;
		$view->content->vote_grids = $vote_grids;
		$view->content->total_votes = $total_votes;
		$view->content->agrees = $agrees;
		$view->content->sums = $sums;
		$view->content->state_text = $state_text;
		$view->content->percents = $percents;
		
		if ($this->acl_check_view('Comments_Controller', 'works', $work->user->member_id))
			$view->content->comments_grid = $comments_grid;
		
		$view->render(TRUE);
	}

	/**
	 * Private function to show closed work
	 * 
	 * @author Michal Kliment
	 * @param Job_Model $work
	 */
	private function show_closed($work = NULL)
	{
		// bad parameter
		if (!$work)
			Controller::warning(PARAMETER);
		
		if (Settings::get('finance_enabled'))
			$transfer = new Transfer_Model($work->transfer_id);

		$vote_model = new Vote_Model();

		$aro_group_model = new Aro_group_Model();
		$aro_groups = $aro_group_model->get_aro_groups_by_fk_id($work->id, Vote_Model::WORK);

		$i = 0;
		$vote_groups = array();
		$vote_grids = array();
		$total_votes = array();
		$sums = array();
		$agrees = array();
		$percents = array();
		//$user_vote = NULL;

		foreach ($aro_groups as $aro_group)
		{
			$vote_groups[$i] = $aro_group->name;

			$votes = $vote_model->select(
						'votes.id', 'votes.user_id',
						'CONCAT(users.name,\' \',users.surname) as uname',
						'vote', 'votes.comment', 'time'
					)->join('users','users.id', 'votes.user_id')
					->where('votes.type', Vote_Model::WORK)
					->where('fk_id', $work->id)
					->where('aro_group_id', $aro_group->id)
					->find_all();

			$total_votes[$i] = count($votes);
			$sums[$i] = 0;
			$agrees[$i] = 0;

	    		foreach ($votes as $vote)
			{
				$sums[$i] += $vote->vote;

				if ($vote->vote == 0)
				{
					$total_votes[$i]--;
					continue;
				}

	    			if ($vote->vote == 1)
				{
					$agrees[$i]++;
					continue;
				}
			}

			// create grid
			$vote_grids[$i] = new Grid(url_lang::base().'works/show/'.$work->id, '', array
			(
				'use_paginator'				=> false,
				'use_selector'				=> false,
				'selector_increace'			=> 200,
				'selector_min'				=> 200,
				'selector_max_multiplier'	=> 10,
				'uri_segment'				=> 'page',
				'style'						=> 'classic'
			));

			$vote_grids[$i]->callback_field('vote')
					->label(__('Vote'))
					->callback('callback::vote');
			
			$vote_grids[$i]->link_field('user_id')
					->link('users/show', 'uname')
					->label('Worker');
			
			$vote_grids[$i]->callback_field('comment');
			
			$vote_grids[$i]->callback_field('time')
					->label(__('Time'))
					->callback('callback::datetime');

			$vote_grids[$i]->datasource($votes);

			$percents[$i] = ($total_votes[$i]) ? round($agrees[$i] / $total_votes[$i] * 100, 1) : 0;
			$i++;
		}

		$links = array();

		switch ($work->state)
		{
			case Vote_Model::STATE_REJECTED:

				$state_text = '<span style="color: red">'.__('Rejected').'</span>';
				
				foreach ($sums as $i => $sum)
				{
					if ($sum <= 0)
					{
						$state_text = '<span style="color: red">'.__('Rejected').
								' ('.__(''.$vote_groups[$i]).')</span>';
						break;
					}
				}

				break;
				
			case Vote_Model::STATE_APPROVED:
				
				$state_text = '<span style="color: green">'.__('Approved').'</span>';
				
				break;
		}

		if ($work->state == Vote_Model::STATE_REJECTED &&
			$this->acl_check_new('Works_Controller', 'work'))
		{
			$links[] = html::anchor(
					'works/add/'. $work->user_id .'/' . $work->id,
					__('Create new work from rejected')
			);
		}
		
		$links = implode(" | ", $links);

		if ($this->acl_check_view(
				'Comments_Controller', 'works', $work->user->member_id
			))
		{
			$comment_model = new Comment_Model();
			$comments = $comment_model->get_all_comments_by_comments_thread(
					$work->comments_thread_id
			);

			$comments_grid = new Grid('members', null, array
			(
				'separator'		   		=> '<br /><br />',
				'use_paginator'	   		=> false,
				'use_selector'	   		=> false,
			));

			$url = ($work->comments_thread_id) ?
					'comments/add/'.$work->comments_thread_id :
					'comments/add_thread/job/'.$work->id;

			$comments_grid->add_new_button($url, __('Add comment to work'));

			$comments_grid->field('text')
					->label(__('Text'));

			$comments_grid->link_field('user_id')
					->link('users/show', 'user_name')
					->label('User');

			$comments_grid->field('datetime')
					->label(__('Time'));
			
			$actions = $comments_grid->grouped_action_field();
			
			$actions->add_conditional_action()
					->icon_action('edit')
					->url('comments/edit')
					->condition('is_own');
			
			$actions->add_conditional_action()
					->icon_action('delete')
					->url('comments/delete')
					->condition('is_own')
					->class('delete_link');

			$comments_grid->datasource($comments);
		}
		
		$view = new View('main');
		$view->title = __('Show work');
		$view->breadcrumbs = $this->breadcrumbs->html();
		$view->content = new View('works/show');
		$view->content->work = $work;
		$view->content->transfer = isset($transfer) ? $transfer : NULL;
		$view->content->links = $links;
		$view->content->vote_groups = $vote_groups;
		$view->content->vote_grids = $vote_grids;
		$view->content->total_votes = $total_votes;
		$view->content->agrees = $agrees;
		$view->content->sums = $sums;
		$view->content->state_text = $state_text;
		$view->content->percents = $percents;
		
		if ($this->acl_check_view('Comments_Controller', 'works', $work->user->member_id))
			$view->content->comments_grid = $comments_grid;
		
		$view->render(TRUE);
	}

	

	/**
	 * Adds new work to some user
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @param integer $previous_rejected_work_id
 	 */
	public function add($user_id = null, $previous_rejected_work_id = null)
	{	
		$breadcrumbs = breadcrumbs::add();

		if (isset($user_id))
		{
			$user = new User_Model($user_id);
			
			if (!$user->id)
				Controller::error(RECORD);
			
			// access control
			if (!$this->acl_check_new('Works_Controller', 'work', $user->member_id))
				Controller::error(ACCESS);
			
			$selected = $user->id;
			$arr_users[$user->id] = $user->get_full_name() . ' - ' . $user->login;
			
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
					->link('works/show_by_user/'.$user->id, 'Works',
							$this->acl_check_view(
									'Works_Controller', 'work',
									$user->member_id
							)
					);
			$member_id = $user->member_id;
		}
		else
		{
			// access control
			if (!$this->acl_check_new('Works_Controller', 'work'))
				Controller::error(ACCESS);
			
			$selected = NULL;
			
			$concat = "CONCAT(
					COALESCE(surname, ''), ' ',
					COALESCE(name, ''), ' - ',
					COALESCE(login, '')
			)";
			
			$arr_users = array
			(
				NULL => '----- '.__('select user').' -----'
			) + ORM::factory('user')
					->select_list('id', $concat);
			
			// breadcrumbs navigation
			$breadcrumbs->link('works/show_all', 'Works',
					$this->acl_check_view('Works_Controller', 'work'));
			
			$member_id = NULL;
		}

		// breadcrumbs navigation
		$breadcrumbs->text('Add');

		if ($this->acl_check_view(get_class($this), 'approval_template', $member_id))
		{
			$arr_approval_templates = array
			(
				NULL => '----- '.__('select approval template').' -----'
			) + ORM::factory('approval_template')->select_list();
		}

		// form
		$this->_form = $form = new Forge();

		$form->group('Basic information');
		
		$form->dropdown('user_id')
				->label('User')
				->options($arr_users)
				->rules('required')
				->selected($selected)
				->style('width:600px');
		
		$form->textarea('description')
				->rules('required|length[0,65535]')
				->style('width:600px');
		
		$form->date('date')
				->label('Date')
				->years(date('Y') - 10, date('Y'))
				->rules('required');
		
		$form->input('hours')
				->rules('required|length[0,250]|valid_numeric')
				->class('increase_decrease_buttons');
		
		$form->input('km')
				->rules('length[0,250]|valid_numeric')
				->class('increase_decrease_buttons');
		
		$form->input('payment_per_hour')
				->label(__('Payment %s per hour', __(Settings::get('currency'))))
				->rules('required|valid_numeric')
				->class('increase_decrease_buttons');
		
		$form->input('price_per_kilometre')
				->rules('valid_numeric')
				->callback(array($this, 'valid_price_per_km'))
				->class('increase_decrease_buttons');

		if ($this->acl_check_view(get_class($this), 'approval_template', $member_id))
		{
			$form->group('Advanced information');
			
			$form->dropdown('approval_template_id')
					->label('Approval template')
					->rules('required')
					->options($arr_approval_templates)
					->selected($this->settings->get('default_work_approval_template'))
					->style('width:200px');
		}
		
		$form->hidden('previous_rejected_work_id');
		
		// fill in clone values?
		if (!empty($previous_rejected_work_id))
		{
			$clone_work = new Job_Model($previous_rejected_work_id);
			
			if ($clone_work->id)
			{
				$form->description->value($clone_work->description);
				$form->date->value(strtotime($clone_work->date));
				$form->hours->value($clone_work->hours);
				
				if (!empty($clone_work->km))
				{
					$form->km->value($clone_work->km);
				}
				
				if ($clone_work->job_report_id)
				{
					$form->payment_per_hour->value($clone_work->job_report->price_per_hour);
					$form->price_per_kilometre->value($clone_work->job_report->price_per_km);
				}
				
				$form->previous_rejected_work_id->value($clone_work->id);
			}
			
			unset($clone_work);
		}

		$form->submit('Save');

		if (isset($user_id))
		{
			$link_back = 'works/show_by_user/' . $user_id;
		}
		else
		{
			$link_back = 'works/show_all';
		}

		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				// calculate suggested amount
				$suggest_amount =
					$form_data['hours'] * $form_data['payment_per_hour'] +
					$form_data['km'] * $form_data['price_per_kilometre'];

				// creates new work
				$work = new Job_Model();
				
				$work->transaction_start();
				
				$work->user_id		= $form_data['user_id'];
				$work->added_by_id	= $this->user_id;
				$work->description	= $form_data['description'];
				$work->suggest_amount	= $suggest_amount;
				$work->date		= date('Y-m-d', $form_data['date']);
				$work->create_date	= date('Y-m-d H:i:s');
				$work->hours		= $form_data['hours'];
				$work->km		= $form_data['km'];

				if (!empty($_POST['previous_rejected_work_id']))
					$work->previous_rejected_work_id = $_POST['previous_rejected_work_id'];

				if ($this->acl_check_view(get_class($this), 'approval_template', $member_id))
					$work->approval_template_id = $form_data['approval_template_id'];
				else
					$work->approval_template_id = $this->settings->get('default_work_approval_template');

				$work->save_throwable();

				// set up state of approval template
				Approval_template_Model::update_state(
					$work->approval_template_id
				);

				// finds all aro ids assigned to vote about this work
				$approval_template_item_model = new Approval_template_item_Model();
				$aro_ids = arr::from_objects(
					$approval_template_item_model->get_aro_ids_by_approval_template_id(
					$work->approval_template_id, $work->suggest_amount
				), 'id');

				$watchers = array_unique(
					array($work->user_id, $this->user_id)
					+ $aro_ids
				);

				$watcher_model = new Watcher_Model();

				$watcher_model->add_watchers_to_object(
					$watchers,
					Watcher_Model::WORK,
					$work->id
				);
				
				$subject = mail_message::format('work_add_subject');
				$body = mail_message::format('work_add', array
				(
					$work->user->name . ' ' . $work->user->surname,
					url_lang::base().'works/show/'.$work->id
				));
				
				// send message about work adding to all watchers
				Mail_message_Model::send_system_message_to_item_watchers(
					$subject,
					$body,
					Vote_Model::WORK,
					$work->id
				);
				
				$work->transaction_commit();
				status::success('Work has been successfully added');
				
				if ($user_id)
					url::redirect('users/show_work/' . $work->id);
				else
					url::redirect('works/show/' . $work->id);
			}
			catch (Exception $e)
			{
				$work->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot add work', $e);
				url::redirect('works/show_all');
			}
		}

		$headline = __('Add new work');
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->link_back = '';
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Edits work
	 * 
	 * @author Michal Kliment
	 * @param $work_id id of work to edit
	 */
	public function edit($work_id = NULL)
	{
		// bad parameter
		if (!$work_id || !is_numeric($work_id))
			Controller::warning(PARAMETER);

		$work = new Job_Model($work_id);

		// record doesn't exist
		if (!$work->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_edit('Works_Controller', 'work', $work->user->member_id))
			Controller::error(ACCESS);

		// work is locked
		if ($work->state > 0)
		{
			status::warning('It is not possible edit locked work.');
			url::redirect('works/show/' . $work->id);
		}
		
		// test if path is from user profile
		$is_from_user = (Path::instance()->uri(TRUE)->previous(0, 1) == 'users'
			|| Path::instance()->uri(TRUE)->previous(1, 1) == 'show_by_user');

		$user_model = new User_Model();

		// check if user has access rights to edit work of all users
		if ($this->acl_check_edit('Works_Controller', 'work'))
		{
			// gets all user's names
			$users = $user_model->get_his_users_names($work->user_id);
		}
		else
		{
			$users = $user_model->get_his_username($work->user_id);
		}

		// transforms array of objects to classic array
		$arr_users = arr::from_objects($users, 'username');

		if ($this->acl_check_view(get_class($this), 'approval_template', $work->user->member_id))
		{
			$arr_approval_templates = array
			(
				NULL => '----- '.__('select approval template').' -----'
			) + ORM::factory('approval_template')->select_list();
		}

		// creates form
		$this->form = new Forge();

		$this->form->group('Basic information');
		
		$this->form->dropdown('user_id')
				->label('User')
				->options($arr_users)
				->rules('required')
				->selected($work->user_id)
				->style('width:600px');
		
		$this->form->textarea('description')
				->rules('required|length[0,65535]')
				->value($work->description)
				->style('width:600px');
		
		$this->form->date('date')
				->label('Date')
				->years(date('Y') - 10, date('Y'))
				->rules('required')
				->value(strtotime($work->date));
		
		$this->form->input('hours')
				->rules('required|length[0,250]|valid_numeric')
				->value(num::decimal_point($work->hours))
				->class('increase_decrease_buttons');
		
		$this->form->input('km')
				->rules('length[0,250]|valid_numeric')
				->value(num::decimal_point($work->km))
				->class('increase_decrease_buttons');
		
		$this->form->input('suggest_amount')
				->rules('required|valid_numeric')
				->value(num::decimal_point($work->suggest_amount));

		if ($this->acl_check_view(get_class($this), 'approval_template', $work->user->member_id))
		{
			$this->form->group('Advanced information');
			
			$this->form->dropdown('approval_template_id')
					->label('Approval template')
					->rules('required')
					->options($arr_approval_templates)
					->selected($work->approval_template_id);
		}

		$this->form->submit('Save');

		// form is validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			try
			{
				$work->transaction_start();
				
				$work->user_id		= $form_data['user_id'];
				$work->added_by_id	= $this->user_id;
				$work->description	= $form_data['description'];
				$work->date		= date('Y-m-d', $form_data['date']);
				$work->hours		= $form_data['hours'];
				$work->km		= $form_data['km'];
				$work->suggest_amount	= $form_data['suggest_amount'];

				$old_approval_template_id = $work->approval_template_id;

				if ($this->acl_check_view(get_class($this), 'approval_template', $work->user->member_id))
					$work->approval_template_id = $form_data['approval_template_id'];
				else
					$work->approval_template_id = $this->settings->get('default_work_approval_template');

				$work->save_throwable();

				// set up state of approval template				
				Approval_template_Model::update_state(
					$work->approval_template_id
				);

				// approval template has been changed
				if ($work->approval_template_id != $old_approval_template_id)
				{
					// set up state of old approval template
					Approval_template_Model::update_state(
						$old_approval_template_id
					);
					
					$watcher_model = new Watcher_Model();
					
					// remove old watchers
					$watcher_model->delete_watchers_by_object(
						Watcher_Model::WORK,
						$work->id
					);
					
					// finds all aro ids assigned to vote about this work
					$approval_template_item_model = new Approval_template_item_Model();
					$aro_ids = arr::from_objects(
						$approval_template_item_model->get_aro_ids_by_approval_template_id(
						$work->approval_template_id, $work->suggest_amount
					), 'id');
					
					$watchers = array_unique(
						array($work->user_id, $this->user_id)
						+ $aro_ids
					);

					// add new watchers
					$watcher_model->add_watchers_to_object(
						$watchers,
						Watcher_Model::WORK,
						$work->id
					);
				}
				
				$subject = mail_message::format('work_update_subject');
				$body = mail_message::format('work_update', array
				(
					$work->user->name . ' ' . $work->user->surname,
					url_lang::base().'works/show/'.$work->id
				));
				
				// send message about work update to all watchers
				Mail_message_Model::send_system_message_to_item_watchers(
					$subject,
					$body,
					Vote_Model::WORK,
					$work->id
				);
				
				$work->transaction_commit();
				status::success('Work has been successfully updated');
			}
			catch (Exception $e)
			{
				$work->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot update work', $e);
			}

			if ($is_from_user)
				$this->redirect('users/show_work/', $work->id);
			else
				$this->redirect('works/show/', $work->id);
		}
		else
		{
			if ($is_from_user)
			{
				$breadcrumbs = breadcrumbs::add()
						->link('members/show_all', 'Members',
								$this->acl_check_view('Members_Controller', 'members'))
						->disable_translation()
						->link('members/show/'.$work->user->member->id,
								'ID ' . $work->user->member->id . ' - ' .
								$work->user->member->name,
								$this->acl_check_view(
										'Members_Controller', 'members',
										$work->user->member->id
								)
						)->enable_translation()
						->link('users/show_by_member/' . $work->user->member_id,
								'Users',
								$this->acl_check_view(
										'Users_Controller', 'users',
										$work->user->member_id
								)
						)->disable_translation()
						->link('users/show/'.$work->user->id,
								$work->user->name . ' ' . $work->user->surname .
								' (' . $work->user->login . ')',
								$this->acl_check_view(
										'Users_Controller','users',
										$work->user->member_id
								)
						)->enable_translation()
						->link('works/show_by_user/'.$work->user->id, 'Works',
								$this->acl_check_view(
										'Works_Controller', 'work',
										$work->user->member_id
								)
						)->link('users/show_work/'.$work->id, 'ID '.$work->id,
								$this->acl_check_view(
										'Works_Controller', 'work',
										$work->user->member_id
						));
			}
			else
			{
				$breadcrumbs = breadcrumbs::add()
					->link('works/show_all', 'Works',
						$this->acl_check_view('Works_Controller','work'))
					->link('works/show/'.$work->id, 'ID '.$work->id,
								$this->acl_check_view(
										'Works_Controller', 'work',
										$work->user->member_id
						));
			}
			
			$breadcrumbs->text('Edit');

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = __('Edit the work');
			$view->content = new View('form');
			$view->content->headline = __('Edit the work');
			$view->content->form = $this->form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Function to delete work
	 * 
	 * @author Michal Kliment
	 * @param number $work_id
	 */
	public function delete($work_id = NULL)
	{
		// bad parameter
		if (!$work_id || !is_numeric($work_id))
			Controller::warning(PARAMETER);

		$work = new Job_Model($work_id);

		// record doesn't exist
		if (!$work->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_delete('Works_Controller', 'work', $work->user->member_id))
			Controller::error(ACCESS);

		// work is locked
		if ($work->state > 0)
		{
			status::success('It is not possible delete locked work.');
			url::redirect('works/show/'.$work->id);
		}
		
		try
		{
			$work->transaction_start();
			
			$approval_template_id = $work->approval_template_id;
			$work_user_id = $work->user_id;
			
			$subject = mail_message::format('work_delete_subject');
			$body = mail_message::format('work_delete', array
			(
				$work->user->name.' '.$work->user->surname,
				$work->description,
				url_lang::base().'works/show/'.$work->id
			));
			
			// send message about work delete to all watchers
			Mail_message_Model::send_system_message_to_item_watchers(
				$subject,
				$body,
				Vote_Model::WORK,
				$work->id
			);
			
			$watcher_model = new Watcher_Model();
			
			// remove all watchers
			$watcher_model->delete_watchers_by_object(
				Watcher_Model::WORK, $work->id
			);
			
			// remove work
			$work->delete_throwable();

			// set up state of approval template
			Approval_template_Model::update_state(
				$approval_template_id
			);
			
			$work->transaction_commit();
			status::success('Work has been successfully deleted');
		}
		catch (Exception $e)
		{
			$work->transaction_rollback();
			status::error('Error - Cannot delete work', $e);
			Log::add_exception($e);
		}
		
		url::redirect('works/show_by_user/'.$work_user_id);
	}
	
	/**
	 * Check validity of price per km field
	 *
	 * @param object $input 
	 */
	public function valid_price_per_km($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if (intval($this->_form->km->value) && !intval($input->value))
		{
			$input->add_error('required', __('Fill in'));
		}
	}
       
}
