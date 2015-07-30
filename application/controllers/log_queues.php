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
 * Manages log queues.
 * 
 * @author Ondřej Fibich
 * @package	Controller
 */
class Log_queues_Controller extends Controller
{

	/**
	 * Index redirect to show all
	 */
	public function index()
	{
		url::redirect('log_queues/show_all');
	}

	/**
	 * Shows all logs.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'id',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{
		if (!$this->acl_check_view('Log_queues_Controller', 'log_queue'))
		{
			Controller::error(ACCESS);
		}
		
		if (is_numeric($this->input->post('record_per_page')))
		{
			$limit_results = (int) $this->input->post('record_per_page');
		}
		
		$allowed_order_type = array
		(
			'id', 'user_name', 'created_at', 'type', 'status', 'closed_at',
			'description'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
		{
			$order_by = 'id';
		}
		
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		// filter
		$filter_form = new Filter_form();
		
		$filter_form->add('type')
				->type('select')
				->values(Log_queue_Model::get_types());
		
		$filter_form->add('state')
				->type('select')
				->values(Log_queue_Model::get_states());
		
		$filter_form->add('created_at')
				->label('Recorded')
				->type('date');
		
		$filter_form->add('closed_at')
				->type('date');
		
		$filter_form->add('user_name')
				->label('Closed by')
				->callback('json/user_name');
		
		$filter_form->add('description');
		
		// get data
		$lq_model = new Log_queue_Model();
		
		$total = $lq_model->count_all_logs($filter_form->as_sql());
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total)
			$sql_offset = 0;
		
		$logs = $lq_model->get_all_logs(
				$sql_offset, $limit_results, $order_by, $order_by_direction,
				$filter_form->as_sql()
		);

		$headline = __('Errors and logs');
		
		$grid = new Grid('log_queue', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min'				=> 50,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang')
										. '/log_queues/show_all/' . $limit_results
										. '/' . $order_by . '/' . $order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form->html()
		));
                
		$grid->add_new_button(
			'log_queues/close_logs/'.server::query_string(), 'Set state closed'
		);
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_callback_field('type')
				->callback('callback::log_queues_type_field');
		
		$grid->order_callback_field('state')
				->callback('callback::log_queues_state_field');
		
		$grid->order_field('created_at')
				->label('Recorded');
		
		$grid->order_callback_field('description')
				->callback('callback::limited_text');
		
		$grid->order_field('closed_at');
		
		$grid->order_link_field('user_id')
				->link('users/show', 'user_name')
				->label('Closed by');
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_conditional_action()
				->condition('is_log_queue_unclosed')
				->icon_action('activate')
				->url('log_queues/close_log')
				->label('Set state closed');
		
		$actions->add_action()
				->icon_action('show')
				->url('log_queues/show');
		
		$grid->datasource($logs);

		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $headline;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Shows a log queue.
	 * 
	 * @param integer $log_queue_id
	 */
	public function show($log_queue_id = NULL)
	{
		if (!is_numeric($log_queue_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$lq_model = new Log_queue_Model($log_queue_id);
		
		if (!$lq_model || !$lq_model->id)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_view('Log_queues_Controller', 'log_queue'))
		{
			Controller::error(ACCESS);
		}
		
		// comments grid
		$comment_model = new Comment_Model();
		
		$comments = $comment_model->get_all_comments_by_comments_thread(
				$lq_model->comments_thread_id
		);

		$comments_grid = new Grid('comments', NULL, array
		(
			'separator'		   		=> '<br /><br />',
			'use_paginator'	   		=> FALSE,
			'use_selector'	   		=> FALSE,
		));

		if ($this->acl_check_new('Log_queues_Controller', 'comments'))
		{
			$url = ($lq_model->comments_thread_id) ?
					'comments/add/'.$lq_model->comments_thread_id :
					'comments_threads/add/log_queue/'.$lq_model->id;

			$comments_grid->add_new_button(
					$url, 'Add comment',
					array('class' => 'popup_link')
			);
		}

		$comments_grid->field('text');

		if ($this->acl_check_view('Users_Controller', 'users'))
		{
			$comments_grid->link_field('user_id')
					->link('users/show', 'user_name')
					->label('User');
		}
		else
		{
			$comments_grid->field('user_name')
					->label('User');
		}

		$comments_grid->field('datetime')
				->label('Time');

		$actions = $comments_grid->grouped_action_field();

		if ($this->acl_check_edit('Log_queues_Controller', 'comments'))
		{
			$actions->add_conditional_action()
					->icon_action('edit')
					->url('comments/edit')
					->condition('is_own')
					->class('popup_link');
		}

		if ($this->acl_check_delete('Log_queues_Controller', 'comments'))
		{
			$actions->add_conditional_action()
					->icon_action('delete')
					->url('comments/delete')
					->condition('is_own')
					->class('delete_link');
		}

		$comments_grid->datasource($comments);
		
		$headline = __('Error and log');
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('log_queues/show_all', 'Errors and logs')
				->disable_translation()
				->text(Log_queue_Model::get_type_name($lq_model->type) . ' (' . $lq_model->id . ')');
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('log_queues/show');
		$view->content->headline = $headline;
		$view->content->log_queue = $lq_model;
		$view->content->comments_grid = $comments_grid;
		
		$view->render(TRUE);
	}
	
	/**
	 * Close a log queue.
	 * 
	 * @param integer $log_queue_id
	 */
	public function close_log($log_queue_id = NULL)
	{
		if (!is_numeric($log_queue_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$lq_model = new Log_queue_Model($log_queue_id);
		
		if (!$lq_model || !$lq_model->id)
		{
			Controller::error(RECORD);
		}
		
		if (!$this->acl_check_edit('Log_queues_Controller', 'log_queue'))
		{
			Controller::error(ACCESS);
		}
			
		if ($lq_model->state == Log_queue_Model::STATE_CLOSED)
		{
			Controller::error(RECORD);
		}
		
		try
		{
			$lq_model->transaction_start();
		
			// set data
			$lq_model->state = Log_queue_Model::STATE_CLOSED;
			$lq_model->closed_at = date('Y-m-d H:i:s');
			$lq_model->closed_by_user_id = $this->user_id;
			$lq_model->save_throwable();
			
			$lq_model->transaction_commit();
			
			status::success('Log queue has been closed.');
		}
		catch (Exception $e)
		{
			$lq_model->transaction_rollback();
			Log::add_exception($e);
			status::error('Cannot close log queue.', $e);
		}
		
		$this->redirect('log_queues/show/', $log_queue_id);
	}

	/**
	 * Closes filtered logs
	 */
	public function close_logs()
	{
		$filter_form = new Filter_form();
		
		$filter_form->autoload();
		
		$where = $filter_form->as_sql();
		
		$lq_model = new Log_queue_Model();
		
		if (!empty($where))
		{
			$logs = $lq_model->where($where)->find_all();
		}
		else
		{
			$logs = $lq_model->find_all();
		}
		
		$logs->count();
		$count = 0;
		
		
		foreach ($logs as $log)
		{
			if ($log->state == Log_queue_Model::STATE_CLOSED)
			{
				continue;
			}
			
			try
			{
				$log->transaction_start();

				// set data
				$log->state = Log_queue_Model::STATE_CLOSED;
				$log->closed_at = date('Y-m-d H:i:s');
				$log->closed_by_user_id = $this->user_id;
				$log->save_throwable();

				$log->transaction_commit();

				$count++;
			}
			catch (Exception $e)
			{
				$log->transaction_rollback();
				Log::add_exception($e);
			}
		}
		
		status::success('Log queues has been closed (%d / %d)', TRUE, array($count, $logs->count()));
		
		$this->redirect('log_queues/show_all/'.server::query_string());
	}
}
