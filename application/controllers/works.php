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
	 * Index redirects to work pending
	 */
	public function index()
	{
		url::redirect('works/pending');
	}

	/**
	 * Function to show all pending works
	 * 
	 * @author Michal Kliment
	 * @param number $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param number $page
	 */
	public function pending(
			$limit_results = 100, $order_by = 'date',
			$order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// acccess control
		if (!$this->acl_check_view('Users_Controller','work'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		$work_model = new Job_Model();
		$total_works = $work_model->count_all_pending_works();

		if (($sql_offset = ($page - 1) * $limit_results) > $total_works)
			$sql_offset = 0;

		$works = $work_model->get_all_pending_works(
				$sql_offset, (int)$limit_results, $order_by, $order_by_direction
		);

		$approval_template_item_model = new Approval_template_item_Model();

		// test if user can vote
		$can_vote = FALSE;
		
		foreach ($works as $work)
		{
			if ($approval_template_item_model->check_user_vote_rights(
					$work->id, $this->session->get('user_id'),
					$work->suggest_amount
				))
			{
				$can_vote = TRUE;
				break;
			}
		}

		// create grid
		$grid = new Grid('works/pending', __('List of all pending works'), array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 100,
			'selector_min'				=> 100,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/works/pending/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_works,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
		));

		if ($this->acl_check_new('Users_Controller','work'))
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
				->label(__('comments'))
				->callback('callback::comments_field');

		// user can vote -> show columns for form items
		if ($can_vote)
		{
			$grid->order_form_field('vote')
					->label(__('Vote'))
					->type('dropdown')
					->options(array
					(
						NULL	=> '--------',
						1		=> __('Agree'),
						-1		=> __('Disagree'),
						0		=> __('Abstain')
					))->callback('Works_Controller::vote_form_field');
			
			$grid->order_form_field('comment')
					->label(__('Comment'))
					->type('textarea')
					->callback('Works_Controller::comment_form_field');
		}

		// access control
		if ($this->acl_check_view('Users_Controller','work'))
		{
			$grid->grouped_action_field()
					->add_action('id')
					->icon_action('show')
					->url('works/show');
		}

		$grid->datasource($works);

		// form is submited
		if (isset ($_POST) && count ($_POST))
		{
			$vote_model = new Vote_Model();
			$post_votes = $_POST['vote'];
			$comments = $_POST['comment'];
			$approval_template_model = new Approval_template_Model();
			$approval_template_item_model = new Approval_template_item_Model();

			foreach ($post_votes as $id => $post_vote)
			{
				$work = $work_model->where('id', $id)->find();

				// finding aro group of logged user
				$aro_group = $approval_template_item_model->get_aro_group_by_approval_template_id_and_user_id(
						$work->approval_template_id,
						$this->session->get('user_id'),
						$work->suggest_amount
				);

				// user can vote
				if ($aro_group && $aro_group->id)
				{
					// finding vote of user
					$vote = $vote_model->where('user_id', $this->session->get('user_id'))
							->where('fk_id', $id)
							->where('type',Vote_Model::WORK)
							->find();

					// delete vote
					if ($vote->id && $post_vote=="")
					{
						$vote->delete();
					}
					// edit vote
					else if ($vote->id && $post_vote!="")
					{
						$vote->vote = $post_vote;
						$vote->comment = $comments[$id];
						$vote->time = date('Y-m-d H:i:s');
						$vote->save();
					}
					// create vote
					else if (!$vote->id && $post_vote!="")
					{
						$vote->clear();
						$vote->user_id = $this->session->get('user_id');
						$vote->fk_id = $id;
						$vote->aro_group_id = $aro_group->id;
						$vote->type = Vote_Model::WORK;
						$vote->vote = $post_vote;
						$vote->comment = $comments[$id];
						$vote->time = date('Y-m-d H:i:s');
						$vote->save();
					}

					// set up state of work
					$work->state = $work->get_state(Vote_Model::WORK);

					// work is approved
					if ($work->state == 3)
					{
						// creates new transfer
						$account_model = new Account_Model();
						
						$operating_id = $account_model->where(
								'account_attribute_id', Account_attribute_Model::OPERATING
						)->find()->id;
						
						$credit_id = $account_model->where('member_id', $work->user->member_id)
								->where('account_attribute_id', Account_attribute_Model::CREDIT)
								->find()->id;
						
						$transfer_id = Transfer_Model::insert_transfer(
							$operating_id,
							$credit_id,
							null,
							null,
							$this->session->get('user_id'),
							null,
							date('Y-m-d'),
							date('Y-m-d H:i:s'),
							__('Work approval'),
							$work->suggest_amount
						);

						$work->transfer_id = $transfer_id;
					}

					$work->save();

					// set up state of approval template
					$approval_template = $approval_template_model->where('id',$work->approval_template_id)->find();
					$approval_template->state = $approval_template_model->get_state($approval_template->id);
					$approval_template->save();
					
					ORM::factory ('member')
						->reactivate_messages($work->user->member_id);
				}
			}
			status::success('Votes has been successfully updated.');
			url::redirect(url::base(TRUE).url::current(TRUE));
		}

		$view = new View('main');
		$view->title = __('Pending works');
		$view->breadcrumbs = __('Pending works');
		$view->content = new View('works/show_all');
		$view->content->grid = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function to show all approved works
	 * 
	 * @author Michal Kliment
	 * @param number Number of records to show
	 * @param string Column in database to ordering records
	 * @param string Direction of ordering
	 * @param string Unused variable
	 * @param number Number of page
	 */
	public function approved(
			$limit_results = 50, $order_by = 'date', $order_by_direction = 'DESC',
			$page_word = null, $page = 1)
	{
		// acccess control
		if (!$this->acl_check_view('Users_Controller','work'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		$work_model = new Job_Model();
		$total_works = $work_model->count_all_approved_works();

		if (($sql_offset = ($page - 1) * $limit_results) > $total_works)
			$sql_offset = 0;

		$works = $work_model->get_all_approved_works($sql_offset, (int)$limit_results, $order_by, $order_by_direction);

		// create grid
		$grid = new Grid('works/approved', __('List of all approved works'), array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min'				=> 50,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/works/approved/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_works,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
		));

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
		
		$grid->order_callback_field('rating')
				->label(__('Rating'))
				->callback('callback::money');
		
		$grid->order_callback_field('approval_state')
				->label(__('State'))
				->help(help::hint('approval_state'))
						->callback('callback::vote_state_field');
		
		$grid->order_callback_field('comments_count')
				->label(__('comments'))
				->callback('callback::comments_field');

		// access control
		if ($this->acl_check_view('Users_Controller','work'))
		{
			
			$grid->grouped_action_field()
					->add_action('id')
					->icon_action('show')
					->url('works/show');
		}

		$grid->datasource($works);

		$view = new View('main');
		$view->title = __('Approved works');
		$view->breadcrumbs = __('Approved works');
		$view->content = new View('works/show_all');
		$view->content->grid = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function to show all rejected works
	 * 
	 * @author Michal Kliment
	 * @param number $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param number $page
	 */
	public function rejected(
			$limit_results = 100, $order_by = 'date', $order_by_direction = 'DESC',
			$page_word = null, $page = 1)
	{
		// acccess control
		if (!$this->acl_check_view('Users_Controller','work'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		$work_model = new Job_Model();
		$total_works = $work_model->count_all_rejected_works();

		if (($sql_offset = ($page - 1) * $limit_results) > $total_works)
			$sql_offset = 0;

		$works = $work_model->get_all_rejected_works(
				$sql_offset, (int)$limit_results, $order_by, $order_by_direction
		);

		// create grid
		$grid = new Grid(url_lang::base().'works/rejected', __('List of all rejected works'), array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 100,
			'selector_min'				=> 100,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/works/rejected/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_works,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
		));

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
				->label(__('comments'))
				->callback('callback::comments_field');

		// access control
		if ($this->acl_check_view('Users_Controller','work'))
		{
			$grid->grouped_action_field()
					->add_action('id')
					->icon_action('show')
					->url('works/show');
		}

		$grid->datasource($works);

		$view = new View('main');
		$view->title = __('Rejected works');
		$view->breadcrumbs = __('Rejected works');
		$view->content = new View('works/show_all');
		$view->content->grid = $grid;
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

		if (!$this->acl_check_view('Users_Controller', 'work', $user->member_id))
			Controller::error(ACCESS);

		$work_model = new Job_Model();

		$pending_works = $work_model->get_all_pending_works_by_user($user->id);

		$approval_template_item_model = new Approval_template_item_Model();

		// test if user can vote
		$can_vote = FALSE;
		
		foreach ($pending_works as $pending_work)
		{
			if ($approval_template_item_model->check_user_vote_rights(
					$pending_work->id,
					Session::instance()->get('user_id'),
					$pending_work->suggest_amount
				))
			{
				$can_vote = TRUE;
				break;
			}
		}

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

		if ($this->acl_check_new('Users_Controller','work', $user->member_id))
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

		// user can vote -> show columns for form items
		if ($can_vote)
		{
			$pending_works_grid->form_field('vote')
					->label(__('Vote'))
					->type('dropdown')
					->options(array
					(
						NULL	=> '--------',
						1		=> __('Agree'),
						-1		=> __('Disagree'),
						0		=> __('Abstain')
					))->callback('Works_Controller::vote_form_field');
		
			$pending_works_grid->form_field('comment')
					->label(__('Comment'))
					->type('textarea')
					->callback('Works_Controller::comment_form_field');
		}
		
		$actions = $pending_works_grid->grouped_action_field();

		// access control
		if ($this->acl_check_view('Users_Controller','work',$user->member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('users/show_work');
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
		if ($this->acl_check_view('Users_Controller', 'work', $user->member_id))
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
		if ($this->acl_check_view('Users_Controller','work', $user->member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('users/show_work');
		}

		$rejected_works_grid->datasource($rejected_works);

		// form is submited
		if (isset ($_POST) && count ($_POST))
		{
			$vote_model = new Vote_Model();
			$post_votes = $_POST['vote'];
			$comments = $_POST['comment'];
			$approval_template_model = new Approval_template_Model();
			$approval_template_item_model = new Approval_template_item_Model();

			foreach ($post_votes as $id => $post_vote)
			{
				$work = $work_model->where('id', $id)->find();
				
				// finding aro group of logged user
				$aro_group = $approval_template_item_model->get_aro_group_by_approval_template_id_and_user_id(
						$work->approval_template_id,
						$this->session->get('user_id'),
						$work->suggest_amount
				);

				// user can vote
				if ($aro_group && $aro_group->id)
				{
					// finding vote of user
					$vote = $vote_model->where('user_id', $this->session->get('user_id'))
							->where('fk_id', $id)
							->where('type', Vote_Model::WORK)
							->find();

					// delete vote
					if ($vote->id && $post_vote=="")
					{
						$vote->delete();
					}
					// edit vote
					else if ($vote->id && $post_vote!="")
					{
						$vote->vote = $post_vote;
						$vote->comment = $comments[$id];
						$vote->time = date('Y-m-d H:i:s');
						$vote->save();
					}
					// create vote
					else if (!$vote->id && $post_vote!="")
					{
						$vote->clear();
						$vote->user_id = $this->session->get('user_id');
						$vote->fk_id = $id;
						$vote->aro_group_id = $aro_group->id;
						$vote->type = Vote_Model::WORK;
						$vote->vote = $post_vote;
						$vote->comment = $comments[$id];
						$vote->time = date('Y-m-d H:i:s');
						$vote->save();
					}

					// set up state of work
					$work->state = $work->get_state(Vote_Model::WORK);

					// work is approved
					if ($work->state == 3)
					{
						// creates new transfer
						$account_model = new Account_Model();

						$operating_id = $account_model->where(
								'account_attribute_id', Account_attribute_Model::OPERATING
						)->find()->id;
						
						$credit_id = $account_model->where('member_id', $work->user->member_id)
								->where('account_attribute_id', Account_attribute_Model::CREDIT)
								->find()->id;

						$transfer_id = Transfer_Model::insert_transfer(
							$operating_id,
							$credit_id,
							null,
							null,
							$this->session->get('user_id'),
							null,
							date('Y-m-d'),
							date('Y-m-d H:i:s'),
							__('Work approval'),
							$work->suggest_amount
						);

						$work->transfer_id = $transfer_id;
					}

					$work->save();

					// set up state of approval template
					$approval_template = $approval_template_model->where(
							'id',$work->approval_template_id
					)->find();
					
					$approval_template->state = $approval_template_model->get_state(
							$approval_template->id
					);
					
					$approval_template->save();
					
					ORM::factory ('member')
						->reactivate_messages($work->user->member_id);

				}

			}
			status::success('Votes has been successfully updated.');
			url::redirect(url::base(TRUE).url::current(TRUE));
		}

		
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
		$view->content->headline = __('List of works of user').' '.$user->name.' '.$user->surname;
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
		if (!$this->acl_check_view('Users_Controller', 'work', $work->user->member_id))
			Controller::error(ACCESS);

		// breadcrumbs navigation
		$this->breadcrumbs = breadcrumbs::add();

		if ($work->job_report_id)
		{
			switch ($work->state)
			{
				case 0:
				case 1:
					$this->breadcrumbs->link(
							'work_reports/pending', 'Pending work reports',
							$this->acl_check_view('Users_Controller', 'work')
					);
					break;
				case 2:
					$this->breadcrumbs->link(
							'work_reports/rejected', 'Rejected work reports',
							$this->acl_check_view('Users_Controller', 'work')
					);
					break;
				case 3:
					$this->breadcrumbs->link(
							'work_reports/approved', 'Approved work reports',
							$this->acl_check_view('Users_Controller', 'work')
					);
					break;
			}
			
			$this->breadcrumbs->link(
					'work_reports/show/' . $work->job_report_id,
					text::limit_chars($work->job_report->description, 40)
			);
		}
		else
		{
			if (url::slice(url_lang::uri(Path::instance()->previous()),1,1) == 'show_by_user')
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
										'Users_Controller', 'work',
										$work->user->member_id
								)
						);
			}
			else
			{
				switch ($work->state)
				{
					case 0:
					case 1:
						$this->breadcrumbs->link('works/pending', 'Pending works',
								$this->acl_check_view('Users_Controller', 'work'));
						break;
					case 2:
						$this->breadcrumbs->link('works/rejected', 'Rejected works',
								$this->acl_check_view('Users_Controller', 'work'));
						break;
					case 3:
						$this->breadcrumbs->link('works/approved', 'Approved works',
								$this->acl_check_view('Users_Controller', 'work'));
						break;
				}
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
					$work->id, $this->user_id
			);

			if ($user_aro_group &&
				$user_aro_group->id == $aro_group->id &&
				!$work->job_report_id &&
				!$user_vote &&
				$vote_rights)
			{
				$vote_grids[$i]->add_new_button(
						url_lang::base().'votes/add_to_work/'.$work->id,
						__('Add vote')
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
					->condition('is_own');
			
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
			if ($this->acl_check_edit('Users_Controller','work',$work->user->member_id) &&
				$work->state == 0)
			{
				$links[] = html::anchor('works/edit/'.$work->id,__('Edit'));
			}
			
			if ($this->acl_check_delete('Users_Controller','work',$work->user->member_id) &&
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
					url_lang::base().'comments_threads/add/job/'.$work->id;

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
			case 2:

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
			case 3:
				
				$state_text = '<span style="color: green">'.__('Approved').'</span>';
				
				break;
		}

		if ($work->state == 2 &&
			$this->acl_check_new('Users_Controller', 'work'))
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
					'comments_threads/add/job/'.$work->id;

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
		$view->content->transfer = $transfer;
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
			if (!$this->acl_check_new('Users_Controller', 'work', $user->member_id))
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
									'Users_Controller', 'work',
									$user->member_id
							)
					);
		}
		else
		{
			// access control
			if (!$this->acl_check_new('Users_Controller', 'work'))
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
			) + ORM::factory('user')->select_list('id', $concat);
			
			// breadcrumbs navigation
			$breadcrumbs->link('works/pending', 'Pending works',
					$this->acl_check_view('Users_Controller', 'work'));
		}

		// breadcrumbs navigation
		$breadcrumbs->text('Add');

		if ($this->acl_check_view('approval', 'templates'))
		{
			$arr_approval_templates = array
			(
				NULL => '----- '.__('select approval template').' -----'
			) + ORM::factory('approval_template')->select_list();
		}

		// form
		$this->_form = $form = new Forge('works/add' . (isset($user_id) ? '/' . $user_id : ''));

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

		if ($this->acl_check_view('approval', 'templates'))
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
			$link_back = 'works/pending';
		}

		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			// calculate suggested amount
			$suggest_amount =
				$form_data['hours'] * $form_data['payment_per_hour'] +
				$form_data['km'] * $form_data['price_per_kilometre'];

			// creates new work
			$work = new Job_Model();
			$work->user_id = $form_data['user_id'];
			$work->added_by_id = $this->session->get('user_id');
			$work->description = $form_data['description'];
			$work->suggest_amount = $suggest_amount;
			$work->date = date('Y-m-d', $form_data['date']);
			$work->create_date = date('Y-m-d H:i:s');
			$work->hours = $form_data['hours'];
			$work->km = $form_data['km'];
			
			if (!empty($_POST['previous_rejected_work_id']))
				$work->previous_rejected_work_id = $_POST['previous_rejected_work_id'];

			if (isset($form_data['approval_template_id']) && $form_data['approval_template_id'])
				$work->approval_template_id = $form_data['approval_template_id'];
			else
				$work->approval_template_id = $this->settings->get('default_work_approval_template');

			$saved = $work->save();

			// set up state of approval template
			$approval_template = new Approval_template_Model($work->approval_template_id);
			$approval_template->state = $approval_template->get_state($approval_template->id);
			$saved = $saved && $approval_template->save();

			// success
			if ($saved)
			{
				$receivers = array();

				$mail_message = new Mail_message_Model();
				$user = new User_Model();

				// work has been added by another user, sends message to user
				if ($work->user_id != $this->session->get('user_id'))
				{
					$user->clear();
					$user->where('id', $this->session->get('user_id'))->find();

					$receivers[] = $work->user_id;

					$mail_message->clear();
					$mail_message->from_id = 1;
					$mail_message->to_id = $form_data['user_id'];
					$mail_message->subject = mail_message::format('your_work_add_subject');
					$mail_message->body = mail_message::format('your_work_add', array
					(
						$user->name . ' ' . $user->surname,
						url_lang::base() . 'works/show/' . $work->id
					));
					$mail_message->time = date('Y-m-d H:i:s');
					$mail_message->from_deleted = 1;
					$mail_message->save();
				}

				// finds all aro ids assigned to vote about this work
				$approval_template_item_model = new Approval_template_item_Model();
				$aro_ids = $approval_template_item_model->get_aro_ids_by_approval_template_id(
						$work->approval_template_id, $work->suggest_amount
				);

				// count of aro ids is not null
				if (count($aro_ids))
				{
					// finds user to whom belongs work
					$user->clear();
					$user->where('id', $work->user_id)->find();

					$subject = mail_message::format('work_add_subject');
					$body = mail_message::format('work_add', array
					(
						$user->name . ' ' . $user->surname,
						url_lang::base() . 'works/show/' . $work->id
					));

					foreach ($aro_ids as $aro)
					{
						// is not necessary send message to  user who added work
						if ($aro->id != $this->session->get('user_id'))
						{
							if (!in_array($aro->id, $receivers))
							{
								$receivers[] = $aro->id;

								// sends message
								$mail_message->clear();
								$mail_message->from_id = 1;
								$mail_message->to_id = $aro->id;
								$mail_message->subject = $subject;
								$mail_message->body = $body;
								$mail_message->time = date('Y-m-d H:i:s');
								$mail_message->from_deleted = 1;
								$mail_message->save();
							}
						}
					}
				}

				status::success('Work has been successfully added');
				url::redirect('works/show/' . $work->id);
			}
			url::redirect($link_back);
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
		if (!$this->acl_check_edit('Users_Controller', 'work', $work->user->member_id))
			Controller::error(ACCESS);

		// work is locked
		if ($work->state > 0)
		{
			status::warning('It is not possible edit locked work.');
			url::redirect('works/show/' . $work->id);
		}

		$user_model = new User_Model();

		// check if user has access rights to edit work of all users
		if ($this->acl_check_edit('Users_Controller', 'work'))
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

		if ($this->acl_check_view('approval', 'templates'))
		{
			$arr_approval_templates = array
			(
				NULL => '----- '.__('select approval template').' -----'
			) + ORM::factory('approval_template')->select_list();
		}

		// creates form
		$this->form = new Forge('works/edit/' . $work_id);

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

		if ($this->acl_check_view('approval', 'templates'))
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

			// creates new work
			$work = new Job_Model($work_id);
			$work->user_id = $form_data['user_id'];
			$work->added_by_id = $this->session->get('user_id');
			$work->description = $form_data['description'];
			$work->date = date('Y-m-d', $form_data['date']);
			$work->hours = $form_data['hours'];
			$work->km = $form_data['km'];
			$work->suggest_amount = $form_data['suggest_amount'];

			$old_approval_template_id = $work->approval_template_id;

			if (isset($form_data['approval_template_id']) && $form_data['approval_template_id'])
				$work->approval_template_id = $form_data['approval_template_id'];
			else
				$work->approval_template_id = $this->settings->get('default_work_approval_template');

			$saved = $work->save();

			// set up state of approval template
			$approval_template = new Approval_template_Model($work->approval_template_id);
			$approval_template->state = $approval_template->get_state($approval_template->id);
			$saved = $saved && $approval_template->save();

			if ($work->approval_template_id != $old_approval_template_id)
			{
				// set up state of old approval template
				$approval_template = new Approval_template_Model($old_approval_template_id);
				$approval_template->state = $approval_template->get_state($approval_template->id);
				$saved = $saved && $approval_template->save();
			}

			// success
			if ($saved)
			{
				$receivers = array();

				$mail_message = new Mail_message_Model();
				$user = new User_Model();

				// work has been updated by another user, sends message to user
				if ($work->user_id != $this->session->get('user_id'))
				{
					$user->clear();
					$user->where('id', $this->session->get('user_id'))->find();

					$receivers[] = $work->user_id;

					$mail_message->clear();
					$mail_message->from_id = 1;
					$mail_message->to_id = $form_data['user_id'];
					$mail_message->subject = mail_message::format('your_work_update_subject');
					$mail_message->body = mail_message::format('your_work_update', array
					(
						$user->name . ' ' . $user->surname,
						url_lang::base() . 'works/show/' . $work->id
					));
					$mail_message->time = date('Y-m-d H:i:s');
					$mail_message->from_deleted = 1;
					$mail_message->save();
				}

				// finds all aro ids assigned to vote about this work
				$approval_template_item_model = new Approval_template_item_Model();
				$aro_ids = $approval_template_item_model->get_aro_ids_by_approval_template_id(
						$work->approval_template_id, $work->suggest_amount
				);

				// count of aro ids is not null
				if (count($aro_ids))
				{
					// finds user to whom belongs work
					$user->clear();
					$user->where('id', $work->user_id)->find();

					$subject = mail_message::format('work_update_subject');
					$body = mail_message::format('work_update', array
					(
						$user->name . ' ' . $user->surname,
						url_lang::base() . 'works/show/' . $work->id
					));

					foreach ($aro_ids as $aro)
					{
						// is not necessary send message to  user who added work
						if ($aro->id != $this->session->get('user_id'))
						{
							if (!in_array($aro->id, $receivers))
							{
								$receivers[] = $aro->id;

								// sends message
								$mail_message->clear();
								$mail_message->from_id = 1;
								$mail_message->to_id = $aro->id;
								$mail_message->subject = $subject;
								$mail_message->body = $body;
								$mail_message->time = date('Y-m-d H:i:s');
								$mail_message->from_deleted = 1;
								$mail_message->save();
							}
						}
					}
				}

				status::success('Work has been successfully updated');
			}

			url::redirect('works/show/' . $work->id);
		}

		$link_back = html::anchor('works/show/' . $work->id, __('Back to the work'));

		$view = new View('main');
		$view->title = __('Edit the work');
		$view->content = new View('form');
		$view->content->headline = __('Edit the work');
		$view->content->link_back = $link_back;
		$view->content->form = $this->form->html();
		$view->render(TRUE);
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
		if (!$this->acl_check_delete('Users_Controller', 'work', $work->user->member_id))
			Controller::error(ACCESS);

		// work is locked
		if ($work->state > 0)
		{
			status::success('It is not possible delete locked work.');
			url::redirect('works/show/'.$work->id);
		}

		$approval_template_id = $work->approval_template_id;
		$work_user_id= $work->user_id;
		$work_description = $work->description;
		$work_suggest_amount = $work->suggest_amount;
		$saved = $work->delete();

		// set up state of approval template
		$approval_template = new Approval_template_Model($approval_template_id);
		$approval_template->state = $approval_template->get_state($approval_template->id);
		$saved = $saved && $approval_template->save();

		if ($saved)
		{
			$receivers = array();

			$mail_message = new Mail_message_Model();
			$user = new User_Model();

			// work has been updated by another user, sends message to user
			if ($work_user_id != $this->session->get('user_id'))
			{
				$user->clear();
				$user->where('id',$this->session->get('user_id'))->find();

				$receivers[] = $work_user_id;

				$mail_message = new Mail_message_Model();
				$mail_message->from_id = 1;
				$mail_message->to_id = $work_user_id;
				$mail_message->subject = mail_message::format('your_work_delete_subject');
				$mail_message->body = mail_message::format('your_work_delete', array
				(
					$work_description, $user->name.' '.$user->surname
				));
				$mail_message->time = date('Y-m-d H:i:s');
				$mail_message->from_deleted = 1;
				$mail_message->save();
			}

			// finds all aro ids assigned to vote about this work
			$approval_template_item_model = new Approval_template_item_Model();
			$aro_ids = $approval_template_item_model->get_aro_ids_by_approval_template_id(
					$approval_template_id, $work_suggest_amount
			);

			// count of aro ids is not null
			if (count($aro_ids))
			{
				// finds user to whom belongs work
				$user->clear();
				$user->where('id', $work_user_id)->find();

				$subject = mail_message::format('work_delete_subject');
				$body = mail_message::format('work_delete', array
				(
					$user->name.' '.$user->surname, $work_description
				));

				foreach ($aro_ids as $aro)
				{
					// is not necessary send message to  user who added work
					if ($aro->id != $this->session->get('user_id'))
					{
						if (!in_array($aro->id, $receivers))
						{
							$receivers[] = $aro->id;

							// sends message
							$mail_message->clear();
							$mail_message->from_id = 1;
							$mail_message->to_id = $aro->id;
							$mail_message->subject = $subject;
							$mail_message->body = $body;
							$mail_message->time = date('Y-m-d H:i:s');
							$mail_message->from_deleted = 1;
							$mail_message->save();
						}
					}
				}
			}

			status::success('Work has been successfully deleted');
		}
		url::redirect('works/show_by_user/'.$work_user_id);
	}

	/** CALLBACK FUNCTIONS **/

	/**
	 * Callback function to show vote dropwdown in grid
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 * @param object $input
	 */
	protected static function vote_form_field ($item, $name, $input)
	{
		static $approval_template_item = NULL;
		
		if (empty($approval_template_item))
		{
			$approval_template_item = new Approval_template_item_Model();
		}
		
		$uid = Session::instance()->get('user_id');
		
		if ($item->approval_template_id &&
			$approval_template_item->check_user_vote_rights($item->id, $uid))
		{
			if ($uid == $item->user_id)
			{
				$input->options(array
				(
					NULL	=> '--------',
					0		=> __('Abstain')
				));
			}
			echo $input->html();
		}		
	}

	/**
	 * Callback function to show comment textarea in grid
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 * @param object $input
	 */
	protected static function comment_form_field ($item, $name, $input)
	{
		static $approval_template_item = NULL;
		
		if (empty($approval_template_item))
		{
			$approval_template_item = new Approval_template_item_Model();
		}
		
		$uid = Session::instance()->get('user_id');
		
		if ($item->approval_template_id &&
			$approval_template_item->check_user_vote_rights($item->id, $uid))
		{
			echo $input->html();
		}
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
