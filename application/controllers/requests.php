<?php defined('SYSPATH') or die('No direct access allowed.');
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
 * Controller to requests
 * 
 * @see Works_Controller
 * @see Work_reports_Controller
 * @author  Michal Kliment
 * @package Controller
 */
class Requests_Controller extends Controller
{
	
	/**
	 * Only checks whether approval are enabled
	 * 
	 * @author Michal Kliment
	 */
	public function __construct()
	{
	    parent::__construct();
	    
	    // works are not enabled
	    if (!Settings::get('approval_enabled'))
		{
			self::error(ACCESS);
		}
	}
	
	/**
	 * Index method, only redirect to show all
	 */
	public function index()
	{
		$this->redirect('show_all');
	}
	
	/**
	 * Prints all requests
	 * 
	 * @author Michal Kliment
	 */
	public function show_all(
			$limit_results = 20, $order_by = 'date',
			$order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// access control
		if (!$this->acl_check_view(get_class($this), 'request'))
		{
			self::error(ACCESS);
		}
		
		// gets new selector
		if (is_numeric($this->input->post('record_per_page')))
		{
			$limit_results = (int) $this->input->post('record_per_page');
		}
		
		$filter_form = new Filter_form('r');
		
		$filter_form->add('state')
			->type('select')
			->values(Vote_Model::get_states());
		
		$filter_form->add('uname')
			->table('u')
			->callback('json/user_fullname')
			->label('User');
		
		$filter_form->add('type')
			->type('select')
			->values(Request_Model::get_types());
		
		$filter_form->add('description');
		
		$filter_form->add('suggest_amount')
			->type('number');
		
		$filter_form->add('date')
			->type('date');
		
		$request_model = new Request_Model();
		
		// hide grid on its first load (#442)
		$hide_grid = Settings::get('grid_hide_on_first_load') && $filter_form->is_first_load();
		
		if (!$hide_grid)
		{
			try
			{
				$total_requests = $request_model->count_all_requests(
						$filter_form->as_sql()
				);

				if (($sql_offset = ($page - 1) * $limit_results) > $total_requests)
					$sql_offset = 0;

				$requests = $request_model->get_all_requests(
						$sql_offset, (int) $limit_results, $order_by, $order_by_direction,
						$filter_form->as_sql()
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

			$request_to_vote = array();

			if (array_key_exists(Vote_Model::REQUEST, $items_to_vote))
			{
				$request_to_vote = $items_to_vote[Vote_Model::REQUEST];
			}
		}
		
		// create grid
		$grid = new Grid('requests/show_all', NULL, array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 20,
			'selector_min'				=> 20,
			'selector_max_multiplier'	=> 20,
			'base_url'					=> Config::get('lang').'/requests/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> isset($total_requests) ? $total_requests : 0,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		if ($this->acl_check_new(get_class($this), 'request'))
		{
			$grid->add_new_button('requests/add', 'Add new request', array
			(
				'class' => 'popup_link'
			));
		}
		
		$grid->order_field('id')
			->class('center')
			->label('ID');
		
		$grid->order_link_field('user_id')
			->link('users/show', 'uname')
			->label('User');
		
		$grid->order_callback_field('type')
			->callback('callback::request_type');
		
		$grid->order_callback_field('description')
			->label('Description')
			->callback('callback::limited_text');
		
		$grid->order_callback_field('suggest_amount')
			->label('Suggest amount')
			->callback('callback::money');
		
		$grid->order_field('date')
			->label('Date');
		
		$grid->order_callback_field('approval_state')
			->label('State')
			->help(help::hint('approval_state'))
			->callback('callback::vote_state_field');
		
		$grid->order_callback_field('comments_count')
			->label('Comments')
			->callback('callback::comments_field')
			->class('center');
		
		// user can vote -> show columns for form items
		if (!$hide_grid && count($request_to_vote))
		{
			$grid->order_form_field('vote')
				->label('Vote')
				->type('dropdown')
				->options(array
				(
				    NULL => '----- '.__('Select vote').' -----'
				))
				->callback(
					'Votes_Controller::vote_form_field',
					$request_to_vote, Vote_Model::REQUEST
				);
			
			$grid->order_form_field('comment')
				->label('Comment')
				->type('textarea')
				->callback(
					'Votes_Controller::comment_form_field',
					$request_to_vote
				);
		}
		
		$actions = $grid->grouped_action_field();
		
		// access control
		if ($this->acl_check_view(get_class($this),'request'))
		{
			$actions->add_action('id')
				->icon_action('show')
				->url('requests/show');
		}
		
		// access control
		if ($this->acl_check_edit(get_class($this),'request'))
		{
			$actions->add_conditional_action('id')
				->icon_action('edit')
				->condition('is_item_new')
				->url('requests/edit')
				->class('popup_link');
		}
		
		// access control
		if ($this->acl_check_delete(get_class($this),'request'))
		{
			$actions->add_conditional_action('id')
				->icon_action('delete')
				->condition('is_item_new')
				->url('requests/delete')
				->class('delete_link');
		}
		
		if (!$hide_grid)
		{
			$grid->datasource($requests);
		}
		
		// form is submited
		if (isset($_POST) && count($_POST) > 1)
		{
			try
			{
				$vote_model = new Vote_Model();
				
				$vote_model->transaction_start();
				
				$request_ids	= $_POST['ids'];
				$votes			= $_POST['vote'];
				$comments		= $_POST['comment'];

				$approval_template_item_model = new Approval_template_item_Model();
				
				// voting user
				$user = new User_Model($this->user_id);

				foreach ($request_ids as $request_id)
				{
					// user cannot vote about request
					if (!in_array($request_id, $request_to_vote))
						continue;

					$request = $request_model->where('id', $request_id)->find();

					if (!$request || !$request->id)
						continue;
					
					// finding aro group of logged user
					$aro_group = $approval_template_item_model
						->get_aro_group_by_approval_template_id_and_user_id(
							$request->approval_template_id,
							$this->user_id,
							$request->suggest_amount
					);
					
					$new_vote		= isset($votes[$request->id]) ? $votes[$request->id] : '';
					$new_comment	= isset($comments[$request->id]) ? $comments[$request->id] : '';
					
					$vote = $vote_model->where(array
					(
						'user_id'	=> $this->user_id,
						'type'		=> Vote_Model::REQUEST,
						'fk_id'		=> $request->id
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
							
							$subject = mail_message::format('request_vote_update_subject');
							$body = mail_message::format('request_vote_update', array
							(
								$user->name.' '.$user->surname,
								$request->user->name.' '.$request->user->surname,
								url_lang::base().'requests/show/'.$request->id
							));
						}
						// new vote is empty, delete old
						else
						{
							$vote->delete_throwable();
							
							$subject = mail_message::format('request_vote_delete_subject');
							$body = mail_message::format('request_vote_delete', array
							(
								$user->name.' '.$user->surname,
								$request->user->name.' '.$request->user->surname,
								url_lang::base().'requests/show/'.$request->id
							));
						}
						
						// send message about adding vote to all watchers
						Mail_message_Model::send_system_message_to_item_watchers(
							$subject,
							$body,
							Vote_Model::REQUEST,
							$request->id
						);
					}
					// new vote is not empty
					elseif ($new_vote != '')
					{
						// add new vote
						Vote_Model::insert(
							$this->user_id,
							Vote_Model::REQUEST,
							$request->id,
							$new_vote,
							$new_comment,
							$aro_group->id
						);
						
						$subject = mail_message::format('request_vote_add_subject');
						$body = mail_message::format('request_vote_add', array
						(
							$user->name.' '.$user->surname,
							$request->user->name.' '.$request->user->surname,
							url_lang::base().'requests/show/'.$request->id
						));
						
						// send message about adding vote to all watchers
						Mail_message_Model::send_system_message_to_item_watchers(
							$subject,
							$body,
							Vote_Model::REQUEST,
							$request->id
						);
					}
					
					// set up state of request
					$request->state = Vote_Model::get_state($request);
					
					$request->save_throwable();
					
					switch ($request->state)
					{
						// request was approved
						case Vote_Model::STATE_APPROVED:
							
							$subject = mail_message::format('request_approve_subject');
							$body = mail_message::format('request_approve', array
							(
								$request->user->name.' '.$request->user->surname,
								url_lang::base().'requests/show/'.$request->id
							));
							
							// send messages about request approve to all watchers
							Mail_message_Model::send_system_message_to_item_watchers(
								$subject,
								$body,
								Vote_Model::REQUEST,
								$request->id
							);
							
							break;
						
						// request was rejected
						case Vote_Model::STATE_REJECTED:
							
							$subject = mail_message::format('request_reject_subject');
							$body = mail_message::format('request_reject', array
							(
								$request->user->name.' '.$request->user->surname,
								url_lang::base().'requests/show/'.$request->id
							));
							
							// send messages about request reject to all watchers
							Mail_message_Model::send_system_message_to_item_watchers(
								$subject,
								$body,
								Vote_Model::REQUEST,
								$request->id
							);
							
							break;
					}
					
					// update state of approval template
					Approval_template_Model::update_state(
						$request->approval_template_id
					);
				}
			
				$vote_model->transaction_commit();
				status::success('Votes has been successfully updated.');
                url::redirect(url::base(TRUE).url::current(TRUE));
			}
			catch (Exception $e)
			{
                Log::add_exception($e);
                status::error('Votes has not been updated.', $e);
				$vote_model->transaction_rollback();
			}
		}
		
		$view = new View('main');
		$view->breadcrumbs = __('Requests');
		$view->title = __('Requests');
		$view->content = new View('show_all');
		$view->content->headline = __('List of all requests');
		$view->content->table = $grid;
		$view->render(TRUE);
	}
	
	/**
	 * Shows all requests of user
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 */
	public function show_by_user ($user_id = NULL)
	{
		if (!$user_id || !is_numeric ($user_id))
		{
			self::warning(PARAMETER);
		}

		$user = new User_Model($user_id);

		if (!$user->id)
		{
			self::error(RECORD);
		}
		
		if (!$this->acl_check_view('Requests_Controller', 'request', $user->member_id))
		{
			self::error(ACCESS);
		}

		$request_model = new Request_Model();

		$pending_requests = $request_model->get_all_requests_by_user(
			$user->id, 'pending'
		);

		$total_pending = array();
		$total_pending['suggest_amount'] = 0;

		foreach ($pending_requests as $pending_request)
		{
			$total_pending['suggest_amount'] += $pending_request->suggest_amount;
		}

		// create grid
		$pending_requests_grid = new Grid('requests/rejected', '', array
		(
			'use_paginator' => false,
			'use_selector' => false,
			'total_items' =>  count ($pending_requests)
		));

		if ($this->acl_check_new('Requests_Controller','request', $user->member_id))
		{
			$pending_requests_grid->add_new_button(
					'requests/add/'.$user->id, 'Add new request',
					array('class' => 'popup_link')
			);
		}

		$pending_requests_grid->field('id')
				->label('ID');
		
		$pending_requests_grid->callback_field('description')
				->label('Description')
				->callback('callback::limited_text');
		
		$pending_requests_grid->callback_field('type')
			->callback('callback::request_type');
		
		$pending_requests_grid->field('date')
				->label('Date');
		
		$pending_requests_grid->callback_field('suggest_amount')
				->label('Suggest amount')
				->callback('callback::money');
		
		$pending_requests_grid->callback_field('approval_state')
				->label('State')
				->help(help::hint('approval_state'))
				->callback('callback::vote_state_field');
		
		$pending_requests_grid->callback_field('comments_count')
				->label('Comments')
				->callback('callback::comments_field')
				->class('center');
		
		$actions = $pending_requests_grid->grouped_action_field();

		// access control
		if ($this->acl_check_view('Requests_Controller','request', $user->member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('users/show_request');
		}
		
		// access control
		if ($this->acl_check_edit('Requests_Controller','request', $user->member_id))
		{
			$actions->add_conditional_action('id')
					->icon_action('edit')
					->condition('is_item_new')
					->url('requests/edit')
					->class('popup_link');
		}
		
		// access control
		if ($this->acl_check_delete('Requests_Controller','request', $user->member_id))
		{
			$actions->add_conditional_action('id')
					->icon_action('delete')
					->condition('is_item_new')
					->url('requests/delete')
					->class('delete_link');
		}

		$pending_requests_grid->datasource($pending_requests);

		$approved_requests = $request_model->get_all_requests_by_user(
			$user->id, 'approved'
		);

		$total_approved = array();
		$total_approved['suggest_amount'] = 0;
		
		foreach ($approved_requests as $approved_request)
		{
			$total_approved['suggest_amount'] += $approved_request->suggest_amount;
		}

		// create grid
		$approved_requests_grid = new Grid('requests/rejected', '', array
		(
			'use_paginator'	=> false,
			'use_selector'	=> false,
			'total_items'	=> count ($approved_requests)
		));

		$approved_requests_grid->field('id')
				->label('ID');
		
		$approved_requests_grid->callback_field('description')
				->label('Description')
				->callback('callback::limited_text');
		
		$approved_requests_grid->callback_field('type')
			->callback('callback::request_type');
		
		$approved_requests_grid->field('date')
				->label('Date');
		
		$approved_requests_grid->callback_field('suggest_amount')
				->label(__('Suggest amount'))
				->callback('callback::money');
		
		$approved_requests_grid->callback_field('approval_state')
				->label('State')
				->help(help::hint('approval_state'))
				->callback('callback::vote_state_field');
		
		$approved_requests_grid->callback_field('comments_count')
				->label('Comments')
				->callback('callback::comments_field');
		
		$actions = $approved_requests_grid->grouped_action_field();

		// access control
		if ($this->acl_check_view('Requests_Controller', 'request', $user->member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('users/show_request');
		}

		$approved_requests_grid->datasource($approved_requests);

		$rejected_requests = $request_model->get_all_requests_by_user(
			$user->id, 'rejected'
		);

		$total_rejected = array();
		$total_rejected['suggest_amount'] = 0;

		foreach ($rejected_requests as $rejected_request)
		{
			$total_rejected['suggest_amount'] += $rejected_request->suggest_amount;
		}

		// create grid
		$rejected_requests_grid = new Grid('requests/rejected', '', array(
			//'separator' => '',
			'use_paginator' => false,
			'use_selector' => false,
			'total_items' =>  count ($rejected_requests)
		));

		$rejected_requests_grid->field('id')
				->label('ID');
		
		$rejected_requests_grid->callback_field('description')
				->label('Description')
				->callback('callback::limited_text');
		
		$rejected_requests_grid->callback_field('type')
			->callback('callback::request_type');
		
		$rejected_requests_grid->field('date')
				->label('Date');
		
		$rejected_requests_grid->callback_field('suggest_amount')
				->label('Suggest amount')
				->callback('callback::money');
		
		$rejected_requests_grid->callback_field('approval_state')
				->label('State')->help(help::hint('approval_state'))
						->callback('callback::vote_state_field');
		
		$rejected_requests_grid->callback_field('comments_count')
				->label('comments')
				->callback('callback::comments_field');
		
		$actions = $rejected_requests_grid->grouped_action_field();

		// access control
		if ($this->acl_check_view('Requests_Controller','request', $user->member_id))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('users/show_request');
		}

		$rejected_requests_grid->datasource($rejected_requests);

		
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
				->link('users/show/'.$user->id, $user->get_full_name_with_login(),
						$this->acl_check_view(
								'Users_Controller', 'users',
								$user->member_id
						)
				)->enable_translation()
				->text('Requests');

		$view = new View('main');
		$view->title = __('List of requests of user').' '.$user->get_full_name();
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('requests/show_by_user');
		$view->content->headline = __('List of requests of user') . ' '
				. $user->get_full_name() . ' ' . help::hint('request_description');
		$view->content->user = $user;
		$view->content->pending_requests_grid = $pending_requests_grid;
		$view->content->approved_requests_grid = $approved_requests_grid;
		$view->content->rejected_requests_grid = $rejected_requests_grid;
		$view->content->total_pending = $total_pending;
		$view->content->total_approved = $total_approved;
		$view->content->total_rejected = $total_rejected;
		$view->render(TRUE);
	}
	
	/**
	 * Shows request
	 * 
	 * @author Michal Kliment
	 * @param integer $request_id
	 */
	public function show($request_id = NULL)
	{
		// missing or bad parameter
		if (!$request_id || !is_numeric($request_id))
		{
			self::warning(PARAMETER);
		}
		
		$request = new Request_Model($request_id);
		
		// record doesn't exist
		if (!$request->id)
		{
			self::error(RECORD);
		}
		
		// access control
		if (!$this->acl_check_new('Requests_Controller', 'request', $request->user->member_id))
		{
			self::error(ACCESS);
		}
		
		$approval_template_item_model = new Approval_template_item_Model();
		
		$aro_groups = $approval_template_item_model->get_aro_groups_by_approval_template_id(
				$request->approval_template_id, $request->suggest_amount
		);

		$user_aro_group = $approval_template_item_model->get_aro_group_by_approval_template_id_and_user_id(
				$request->approval_template_id, $this->session->get('user_id'),
				$request->suggest_amount
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
					->where('votes.type', Vote_Model::REQUEST)
					->where('fk_id', $request->id)
					->where('aro_group_id', $aro_group->id)
					->find_all();

			$total_votes[$i] = count($votes);
			$sums[$i] = 0;
			$agrees[$i] = 0;
			$user_vote = NULL;

	    	foreach ($votes as $vote)
			{
				if ($this->user_id == $vote->user_id)
				{
					$user_vote = $vote->vote;
				}

				$sums[$i] += $vote->vote;

				if ($vote->vote == Request_Model::STATE_NEW)
				{
					$total_votes[$i]--;
					continue;
				}

	    		if ($vote->vote == Request_Model::STATE_OPEN)
				{
					$agrees[$i]++;
					continue;
				}
			}

			// create grid
			$vote_grids[$i] = new Grid('requests/show/'.$request->id, '', array
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
					$request, Vote_Model::REQUEST, $this->user_id,
					$request->suggest_amount
			);

			if ($user_aro_group &&
				$user_aro_group->id == $aro_group->id &&
				!$user_vote &&
				$vote_rights)
			{
				$vote_grids[$i]->add_new_button(
						'votes/add/'.Vote_Model::REQUEST.'/'.$request->id,
						__('Add vote'), array('class' => 'popup_link')
				);
			}
			
			$vote_grids[$i]->callback_field('vote')
					->label('Vote')
					->callback('callback::vote');
			
			$vote_grids[$i]->link_field('user_id')
					->link('users/show', 'uname')
					->label('User');
			
			$vote_grids[$i]->callback_field('comment');
			
			$vote_grids[$i]->callback_field('time')
					->label('Time')
					->callback('callback::datetime');
			
			$actions = $vote_grids[$i]->grouped_action_field();
			
			// user can change/delete vote
			if ($request->state == Vote_Model::STATE_NEW || $request->state == Vote_Model::STATE_OPEN)
			{
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
			}
				
			$vote_grids[$i]->datasource($votes);

			$percents[$i] = ($total_votes[$i]) ? round($agrees[$i] / $total_votes[$i] * 100, 1) : 0;
			$i++;
		}
		
		if ($this->acl_check_view(
				'Comments_Controller', 'requests', $request->user->member_id
			))
		{
			$comment_model = new Comment_Model();
			$comments = $comment_model->get_all_comments_by_comments_thread(
					$request->comments_thread_id
			);

			$comments_grid = new Grid('members', null, array
			(
				'separator'		   		=> '<br /><br />',
				'use_paginator'	   		=> false,
				'use_selector'	   		=> false,
			));

			$url = ($request->comments_thread_id) ?
					'comments/add/'.$request->comments_thread_id :
					'comments_threads/add/request/'.$request->id;

			$comments_grid->add_new_button(
					$url, __('Add comment to request'),
					array
					(
						'class' => 'popup_link'
					)
			);

			$comments_grid->field('text')
					->label('Text');

			$comments_grid->link_field('user_id')
					->link('users/show', 'user_name')
					->label('User');

			$comments_grid->field('datetime')
					->label('Time');
			
			$actions = $comments_grid->grouped_action_field();
			
			$actions->add_conditional_action()
					->icon_action('edit')
					->url('comments/edit')
					->condition('is_own')
					->class('popup_link');
			
			$actions->add_conditional_action()
					->icon_action('delete')
					->url('comments/delete')
					->condition('is_own')
					->class('delete_link');

			$comments_grid->datasource($comments);
		}
		
		$breadcrumbs = breadcrumbs::add();
		
		if (url_lang::current(1) == 'users')
		{
			$breadcrumbs
				->link('members/show_all', 'Members',
					$this->acl_check_view('Members_Controller', 'members'))
			->disable_translation()
			->link('members/show/'.$request->user->member->id,
					'ID ' . $request->user->member->id . ' - ' .
					$request->user->member->name,
					$this->acl_check_view(
							'Members_Controller', 'members',
							$request->user->member->id
					)
			)->enable_translation()
			->link('users/show_by_member/' . $request->user->member_id,
					'Users',
					$this->acl_check_view(
							'Users_Controller', 'users',
							$request->user->member_id
					)
			)->disable_translation()
			->link('users/show/'.$request->user->id,
					$request->user->name . ' ' . $request->user->surname .
					' (' . $request->user->login . ')',
					$this->acl_check_view(
							'Users_Controller','users',
							$request->user->member_id
					)
			)->enable_translation()
			->link('requests/show_by_user/'.$request->user->id, 'Requests',
					$this->acl_check_view(
							'Requests_Controller', 'request',
							$request->user->member_id
					)
			);
		}
		else
		{
			$breadcrumbs
				->link('requests/show_all', 'Requests',
					$this->acl_check_view(get_class($this), 'request'));
		}
		
		$breadcrumbs->text('ID '.$request->id);
		
		$links = array();
		
		if ($request->state == Request_Model::STATE_NEW &&
			$this->acl_check_edit('Requests_Controller', 'request', $request->user->member_id))
		{
			$links[] = html::anchor('requests/edit/'.$request->id, __('Edit'), array
			(
				'class' => 'popup_link'
			));
		}

		if ($request->state == Request_Model::STATE_NEW &&
			$this->acl_check_delete('Requests_Controller', 'request', $request->user->member_id))
		{
			$links[] = html::anchor('requests/delete/'.$request->id, __('Delete'), array
			(
				'class' => 'delete_link'
			));
		}
		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = __('Show request');
		$view->content = new View('requests/show');
		$view->content->links = implode(' | ', $links);
		$view->content->request = $request;
		$view->content->state_text = Request_Model::get_state_name($request->state);
		$view->content->vote_groups = $vote_groups;
		$view->content->vote_grids = $vote_grids;
		$view->content->total_votes = $total_votes;
		$view->content->percents = $percents;
		$view->content->agrees = $agrees;
		
		if ($this->acl_check_view('Comments_Controller', 'requests', $request->user->member_id))
		{
			$view->content->comments_grid = $comments_grid;
		}
		
		$view->render(TRUE);
	}
	
	/**
	 * Adds new request
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 */
	public function add($user_id = NULL)
	{	
		if (isset($user_id))
		{
			$user = new User_Model($user_id);
			
			if (!$user->id)
			{
				self::error(RECORD);
			}
			
			// access control
			if (!$this->acl_check_new(get_class($this), 'request', $user->member_id))
			{
				self::error(ACCESS);
			}
			
			// association connot create request
			if ($user->member_id == Member_Model::ASSOCIATION)
			{
				self::error(RECORD);
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
				->link('requests/show_by_user/'.$user->id, 'Requests',
						$this->acl_check_view(
								'Requests_Controller', 'request',
								$user->member_id
						)
				)
				->text('Add');
			
				$member_id = $user->member_id;
		}
		else
		{
			// access control
			if (!$this->acl_check_new(get_class($this), 'request'))
			{
				self::error(ACCESS);
			}
			
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
					->where('member_id <>', Member_Model::ASSOCIATION)
					->select_list('id', $concat);
			
			$breadcrumbs = breadcrumbs::add()
				->link('requests/show_all', 'Requests')
				->text('Add');
			
			$member_id = NULL;
		}
		
		// test if user can change approval template
		if ($this->acl_check_view(get_class($this), 'approval_template', $member_id))
		{
			$arr_approval_templates = array
			(
				NULL => '----- '.__('Select approval template').' -----'
			) + ORM::factory('approval_template')->select_list();
		}
		
		$form = new Forge();
		
		$form->group('Basic information');
		
		if (isset($arr_users))
		{
			$form->dropdown('user_id')
				->label('User')
				->options($arr_users)
				->rules('required')
				->selected($selected)
				->style('width:600px');
		}
		
		$form->radio('type')
			->label('Type')
			->options(Request_Model::get_types())
			->default(Request_Model::TYPE_SUPPORT)
			->help(help::hint('request_type'));
		
		$form->textarea('description')
			->rules('required');
		
		$form->input('suggest_amount')
			->rules('valid_numeric');
		
		if ($this->acl_check_view(get_class($this), 'approval_template', $member_id))
		{
			$form->group('Advanced information');
			
			$form->dropdown('approval_template_id')
				->label('Approval template')
				->rules('required')
				->options($arr_approval_templates)
				->selected(Settings::get('default_request_support_approval_template'))
				->style('width:200px');
		}
		
		$form->submit('Submit');
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			$request = new Request_Model();
			
			try
			{
				$request->transaction_start();
			
				if (isset($arr_users))
				{
					$request->user_id = $form_data['user_id'];
				}
				else
				{
					$request->user_id = $user->id;
				}
				
				$request->type = $form_data['type'];
				
				// test if user can change approval template
				if ($this->acl_check_view(get_class($this), 'approval_template', $member_id))
				{
					$request->approval_template_id = $form_data['approval_template_id'];
				}
				else
				{
					if ($request->type == Request_Model::TYPE_SUPPORT)
					{
						$request->approval_template_id = 
							Settings::get('default_request_support_approval_template');
					}
					else
					{
						$request->approval_template_id = 
							Settings::get('default_request_approval_template');
					}
				}

				$request->description = $form_data['description'];
				
				if ($request->type !== Request_Model::TYPE_SUPPORT)
				{
					$request->suggest_amount = doubleval($form_data['suggest_amount']);
				}
				
				$request->date = date('Y-m-d H:i:s');
				$request->state = Request_Model::STATE_NEW;
			
				$request->save_throwable();
				
				// set up state of approval template
				Approval_template_Model::update_state(
					$request->approval_template_id
				);

				// finds all aro ids assigned to vote about this request
				$approval_template_item_model = new Approval_template_item_Model();
				$aro_ids = arr::from_objects(
					$approval_template_item_model->get_aro_ids_by_approval_template_id(
					$request->approval_template_id, $request->suggest_amount
				), 'id');

				$watchers = array_unique(
					array($request->user_id, $this->user_id)
					+ $aro_ids
				);

				$watcher_model = new Watcher_Model();

				$watcher_model->add_watchers_to_object(
					$watchers,
					Watcher_Model::REQUEST,
					$request->id
				);
				
				$subject = mail_message::format('request_add_subject');
				$body = mail_message::format('request_add', array
				(
					$request->user->name . ' ' . $request->user->surname,
					url_lang::base().'requests/show/'.$request->id
				));
				
				// send message about request adding to all watchers
				Mail_message_Model::send_system_message_to_item_watchers(
					$subject,
					$body,
					Vote_Model::REQUEST,
					$request->id
				);
				
				$request->transaction_commit();
				
				status::success('Request has been successfully added.');
				
				if ($user_id)
				{
					$this->redirect('users/show_request', $request->id);
				}
				else
				{
					$this->redirect('requests/show', $request->id);
				}
			}
			catch (Exception $e)
			{
				$request->transaction_rollback();
				status::error('Error - Cannot add request', $e);
				Log::add_exception($e);
			}
		}
		
		$title = __('Add new request');
		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $title;
		$view->content = new View('form');
		$view->content->headline = $title;
		$view->content->form = $form;
		$view->render(TRUE);
	}
	
	/**
	 * Edits request
	 * 
	 * @author Michal Kliment
	 * @param integer $request_id
	 */
	public function edit($request_id = NULL)
	{
		// bad parameter
		if (!$request_id || !is_numeric($request_id))
		{
			self::warning(PARAMETER);
		}

		$request = new Request_Model($request_id);

		// record doesn't exist
		if (!$request->id)
		{
			self::error(RECORD);
		}

		// access control
		if (!$this->acl_check_edit(get_class($this), 'request', $request->user->member_id))
		{
			self::error(ACCESS);
		}

		// request is locked
		if ($request->state != Request_Model::STATE_NEW)
		{
			status::warning('It is not possible edit locked request.');
			url::redirect('requests/show/' . $request->id);
		}
		
		// test if path is from user profile
		$is_from_user = (Path::instance()->uri(TRUE)->previous(0, 1) == 'users'
			|| Path::instance()->uri(TRUE)->previous(1, 1) == 'show_by_user');

		$user_model = new User_Model();
		$arr_users = null;

		// check if user has access rights to edit request of all users
		if ($this->acl_check_edit(get_class($this), 'request'))
		{
			// gets all user's names
			$arr_users = arr::from_objects(
				$user_model->get_his_users_names($request->user_id), 'username'
			);
		}

		if ($this->acl_check_view(get_class($this), 'approval_template'))
		{
			$arr_approval_templates = array
			(
				NULL => '----- '.__('select approval template').' -----'
			) + ORM::factory('approval_template')->select_list();
		}

		// creates form
		$this->form = new Forge();

		$this->form->group('Basic information');
		
		if (isset($arr_users))
		{
			$this->form->dropdown('user_id')
					->label('User')
					->options($arr_users)
					->rules('required')
					->selected($request->user_id)
					->style('width:600px');
		}
		
		$this->form->radio('type')
			->label('Type')
			->options(Request_Model::get_types())
			->default($request->type)
			->help(help::hint('request_type'));
		
		$this->form->textarea('description')
				->rules('required|length[0,65535]')
				->value($request->description)
				->style('width:600px');
		
		$this->form->date('date')
				->label('Date')
				->years(date('Y') - 10, date('Y'))
				->rules('required')
				->value(strtotime($request->date));
		
		$this->form->input('suggest_amount')
				->rules('valid_numeric')
				->value(num::decimal_point($request->suggest_amount));

		if ($this->acl_check_view(get_class($this), 'approval_template', $request->user->member_id))
		{
			$this->form->group('Advanced information');
			
			$this->form->dropdown('approval_template_id')
					->label('Approval template')
					->rules('required')
					->options($arr_approval_templates)
					->selected($request->approval_template_id);
		}

		$this->form->submit('Save');

		// form is validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			try
			{
				$request->transaction_start();
				
				$request->type = $form_data['type'];
				
				if (isset($arr_users))
				{
					$request->user_id = $form_data['user_id'];
				}
				
				$request->description = $form_data['description'];
				$request->date = date('Y-m-d', $form_data['date']);
				
				if ($request->type !== Request_Model::TYPE_SUPPORT)
				{
					$request->suggest_amount = doubleval($form_data['suggest_amount']);
				}
				else
				{
					$request->suggest_amount = NULL;
				}
				
				$old_approval_template_id = $request->approval_template_id;
				
				if ($this->acl_check_view(get_class($this), 'approval_template', $request->user->member_id))
				{
					$request->approval_template_id = $form_data['approval_template_id'];
				}
				else
				{
					if ($request->type == Request_Model::TYPE_SUPPORT)
					{
						$request->approval_template_id = 
							Settings::get('default_request_support_approval_template');
					}
					else
					{
						$request->approval_template_id = 
							Settings::get('default_request_approval_template');
					}
				}

				$request->save_throwable();

				// set up state of approval template				
				Approval_template_Model::update_state(
					$request->approval_template_id
				);

				// approval template has been changed
				if ($request->approval_template_id != $old_approval_template_id)
				{
					// set up state of old approval template
					Approval_template_Model::update_state(
						$old_approval_template_id
					);
					
					$watcher_model = new Watcher_Model();
					
					// remove old watchers
					$watcher_model->delete_watchers_by_object(
						Watcher_Model::REQUEST,
						$request->id
					);
					
					// finds all aro ids assigned to vote about this request
					$approval_template_item_model = new Approval_template_item_Model();
					$aro_ids = arr::from_objects(
						$approval_template_item_model->get_aro_ids_by_approval_template_id(
						$request->approval_template_id, $request->suggest_amount
					), 'id');
					
					$watchers = array_unique(
						array($request->user_id, $this->user_id)
						+ $aro_ids
					);

					// add new watchers
					$watcher_model->add_watchers_to_object(
						$watchers,
						Watcher_Model::REQUEST,
						$request->id
					);
				}
				
				$subject = mail_message::format('request_update_subject');
				$body = mail_message::format('request_update', array
				(
					$request->user->name . ' ' . $request->user->surname,
					url_lang::base().'requests/show/'.$request->id
				));
				
				// send message about request update to all watchers
				Mail_message_Model::send_system_message_to_item_watchers(
					$subject,
					$body,
					Vote_Model::REQUEST,
					$request->id
				);
				
				$request->transaction_commit();
				status::success('Request has been successfully updated');
			}
			catch (Exception $e)
			{
				$request->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot update request', $e);
			}
			
			if ($is_from_user)
			{
				$this->redirect('users/show_request/', $request->id);
			}
			else
			{
				$this->redirect('requests/show/', $request->id);
			}
		}
		else
		{
			$breadcrumbs = breadcrumbs::add();

			if ($is_from_user)
			{
				$breadcrumbs
					->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/'.$request->user->member->id,
						'ID ' . $request->user->member->id . ' - ' .
						$request->user->member->name,
						$this->acl_check_view(
								'Members_Controller', 'members',
								$request->user->member->id
						)
				)->enable_translation()
				->link('users/show_by_member/' . $request->user->member_id,
						'Users',
						$this->acl_check_view(
								'Users_Controller', 'users',
								$request->user->member_id
						)
				)->disable_translation()
				->link('users/show/'.$request->user->id,
						$request->user->name . ' ' . $request->user->surname .
						' (' . $request->user->login . ')',
						$this->acl_check_view(
								'Users_Controller','users',
								$request->user->member_id
						)
				)->enable_translation()
				->link('requests/show_by_user/'.$request->user->id, 'Requests',
						$this->acl_check_view(
								'Requests_Controller', 'request',
								$request->user->member_id
						)
				)->link('users/show_request/'.$request->id, 'ID '.$request->id,
						$this->acl_check_view(
							get_class($this), 'request',
							$request->user->member_id
				));
			}
			else
			{
				$breadcrumbs
					->link('requests/show_all', 'Requests',
						$this->acl_check_view(get_class($this), 'request'))
					->link('requests/show/'.$request->id, 'ID '.$request->id,
						$this->acl_check_view(
							get_class($this), 'request',
							$request->user->member_id
					));
			}

			$breadcrumbs->text('Edit');

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = __('Edit the request');
			$view->content = new View('form');
			$view->content->headline = __('Edit the request');
			$view->content->form = $this->form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Function to delete request
	 * 
	 * @author Michal Kliment
	 * @param number $v_id
	 */
	public function delete($request_id = NULL)
	{
		// bad parameter
		if (!$request_id || !is_numeric($request_id))
		{
			self::warning(PARAMETER);
		}

		$request = new Request_Model($request_id);

		// record doesn't exist
		if (!$request->id)
		{
			self::error(RECORD);
		}

		// access control
		if (!$this->acl_check_delete('Requests_Controller', 'request', $request->user->member_id))
		{
			self::error(ACCESS);
		}

		// request is locked
		if ($request->state > 0)
		{
			status::success('It is not possible delete locked request.');
			url::redirect('requests/show/'.$request->id);
		}
		
		// test if path is from user profile
		$is_from_user = (Path::instance()->uri(TRUE)->previous(0, 1) == 'users'
			|| Path::instance()->uri(TRUE)->previous(1, 1) == 'show_by_user');

		try
		{
			$request->transaction_start();

			$approval_template_id = $request->approval_template_id;
			$request_user_id = $request->user_id;

			$subject = mail_message::format('request_delete_subject');
			$body = mail_message::format('request_delete', array
			(
				$request->user->name.' '.$request->user->surname,
				$request->description,
				url_lang::base().'requests/show/'.$request->id
			));

			// send message about request delete to all watchers
			Mail_message_Model::send_system_message_to_item_watchers(
				$subject,
				$body,
				Vote_Model::REQUEST,
				$request->id
			);

			$watcher_model = new Watcher_Model();

			// remove all watchers
			$watcher_model->delete_watchers_by_object(
				Watcher_Model::REQUEST,
				$request->id
			);

			// remove request
			$request->delete_throwable();

			// set up state of approval template
			Approval_template_Model::update_state($approval_template_id);

			$request->transaction_commit();
			status::success('Request has been successfully deleted');
		}
		catch (Exception $e)
		{
			$request->transaction_rollback();
			status::error('Error - Cannot delete request', $e);
			Log::add_exception($e);
		}
		
		if ($is_from_user)
		{
			url::redirect('requests/show_by_user/'.$request_user_id);
		}
		else
		{
			url::redirect('requests/show_all');
		}
	}
}