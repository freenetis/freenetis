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
 * Controller performs logic of queue of e-mails
 * 
 * @see Scheduler_Controller
 * @see Notification_Controller
 * @package Controller
 */
class Email_queues_Controller extends Controller
{
	/**
	 * Index redirects to show unset
	 */
	public function index()
	{
		url::redirect('email_queues/show_all_unsent');
	}
	
	/**
	 * Function to show all unsent e-mails
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page 
	 */
	public function show_all_unsent(
			$limit_results = 50, $order_by = 'access_time',
			$order_by_direction = 'DESC', $page_word = null, $page = 1)
	{
		// access check
		if (!$this->acl_check_view('Settings_Controller', 'system'))
			Controller::error(ACCESS);
		
		// filter form
		$filter_form = new Filter_form('eq');
		
		$filter_form->add('from_user_name')
			->label(__('User From'))
			->callback('json/user_fullname');
		
		$filter_form->add('from')
			->label(__('E-mail From'))
			->callback('json/user_email');
		
		$filter_form->add('to_user_name')
			->label(__('User To'))
			->callback('json/user_fullname');
		
		$filter_form->add('to')
			->label(__('E-mail To'))
			->callback('json/user_email');
		
		$filter_form->add('subject');
		
		$filter_form->add('body');
		
		$filter_form->add('access_time')
			->type('date')
			->label(__('Time'));
		
		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'from', 'to', 'subject', 'access_time'
		);
		
		// order by check
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'access_time';
		
		// order by direction check
		if (strtolower($order_by_direction) != 'asc')
			$order_by_direction = 'desc';
		
		$email_queue_model = new Email_queue_Model();
		
		// counts all unsent e-mail
		$total_emails = $email_queue_model->count_all_unsent_emails($filter_form->as_sql());
		
		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_emails)
			$sql_offset = 0;
		
		$emails = $email_queue_model->get_all_unsent_emails(
			$sql_offset, (int)$limit_results, $order_by, $order_by_direction,
			$filter_form->as_sql()
		);
		
		// headline
		$headline = __('List of all unsent e-mails');
		
		// path to form
		$path = Config::get('lang') . '/email_queues/show_all_unsent/' . $limit_results . '/'
				. $order_by . '/' . $order_by_direction;
		
		$grid = new Grid('email_queues', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> $path,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_emails,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		$grid->add_new_button(
				'email_queues/show_all_sent', __('Show all sent e-mails')
		);
		
		$grid->add_new_button(
				'email_queues/delete_unsended', __('Delete all unsended e-mails')
		);
		
		// database columns
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_callback_field('from')
				->callback('callback::email_from_field');
		
		$grid->order_callback_field('to')
				->label('To')
				->callback('callback::email_to_field');
		
		$grid->order_callback_field('subject')
				->callback('callback::email_subject_field');
		
		$grid->order_callback_field('state')
				->callback('callback::email_state_field');
		
		$grid->order_field('access_time')
				->label(__('Time'));
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action()
				->icon_action('mail_send')
				->label('Send again')
				->url('email_queues/send');
		
		$actions->add_action()
				->icon_action('delete')
				->url('email_queues/delete')
				->class('delete_link');
		
		// load data
		$grid->datasource($emails);
		
		$view = new View('main');
		$view->breadcrumbs = __('Unsent e-mails');
		$view->title = $headline;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	}
	
	/**
	 * Function to show all sent e-mails
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page 
	 */
	public function show_all_sent(
			$limit_results = 50, $order_by = 'access_time',
			$order_by_direction = 'DESC', $page_word = null, $page = 1)
	{
		// access check
		if (!$this->acl_check_view('Settings_Controller', 'system'))
			Controller::error(ACCESS);
		
		// filter form
		$filter_form = new Filter_form('eq');
		
		$filter_form->add('from_user_name')
			->label(__('User From'))
			->callback('json/user_fullname');
		
		$filter_form->add('from')
			->label(__('E-mail From'))
			->callback('json/user_email');
		
		$filter_form->add('to_user_name')
			->label(__('User To'))
			->callback('json/user_fullname');
		
		$filter_form->add('to')
			->label(__('E-mail To'))
			->callback('json/user_email');
		
		$filter_form->add('subject');
		
		$filter_form->add('body');
		
		$filter_form->add('access_time')
			->type('date')
			->label(__('Time'));
		
		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'from', 'to', 'subject', 'access_time'
		);
		
		// order by check
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'access_time';
		
		// order by direction check
		if (strtolower($order_by_direction) != 'asc')
			$order_by_direction = 'desc';
		
		$email_queue_model = new Email_queue_Model();
		
		// counts all sent e-mail
		$total_emails = $email_queue_model->count_all_sent_emails($filter_form->as_sql());
		
		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_emails)
			$sql_offset = 0;
		
		$emails = $email_queue_model->get_all_sent_emails(
			$sql_offset, (int)$limit_results, $order_by, $order_by_direction,
			$filter_form->as_sql()
		);

		// headline
		$headline = __('List of all sent e-mails');
		
		// path to form
		$path = Config::get('lang') . '/email_queues/show_all_sent/' . $limit_results . '/'
				. $order_by . '/' . $order_by_direction;
		
		$grid = new Grid('email_queues', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> $path,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_emails,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		$grid->add_new_button(
				'email_queues/show_all_unsent', __('Show all unsent e-mails')
		);
		
		$grid->add_new_button(
				'email_queues/delete_unsended', __('Delete all unsended e-mails')
		);
		
		// database columns
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_callback_field('from')
				->callback('callback::email_from_field');
		
		$grid->order_callback_field('to')
				->callback('callback::email_to_field');
		
		$grid->order_callback_field('subject')
				->callback('callback::email_subject_field');
		
		$grid->order_field('access_time')
				->label('Time');
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action()
				->icon_action('show')
				->url('email/show');
		
		$actions->add_action()
				->icon_action('mail_send')
				->label('Send again')
				->url('email_queues/send');
		
		// load data
		$grid->datasource($emails);
		
		$view = new View('main');
		$view->breadcrumbs = __('Sent e-mails');
		$view->title = $headline;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	}
	
	/**
	 * Tries to send message from queue
	 * 
	 * @author Michal Kliment
	 * @param integer $email_queue_id 
	 */
	public function send($email_queue_id = NULL)
	{
		// access check
		if (!$this->acl_check_view('Settings_Controller', 'system'))
			Controller::error(ACCESS);
		
		// bad parameter
		if (!$email_queue_id || !is_numeric($email_queue_id))
			Controller::warning(PARAMETER);
		
		$email_queue = new Email_queue_Model($email_queue_id);
		
		// record doens't exist
		if (!$email_queue->id)
			Controller::error(RECORD);
		
		$swift = email::connect();
		
		// Build recipient lists
		$recipients = new Swift_RecipientList;
		$recipients->addTo($email_queue->to);
			
		// Build the HTML message
		$message = new Swift_Message($email_queue->subject, $email_queue->body, "text/html");
			
		// Send
		$state = (
				Config::get('unit_tester') ||
				$swift->send($message, $recipients, $email_queue->from)
		);
		
		if ($state)
			$email_queue->state = Email_queue_Model::STATE_OK;
		else
			$email_queue->state = Email_queue_Model::STATE_FAIL;
		
		$email_queue->access_time = date('Y-m-d H:i:s');
		$email_queue->save();
		
		$swift->disconnect();
		
		if ($state)
		{
			status::success('E-mail has been successfully sent');
			url::redirect('email_queues/show_all_sent');
		}
		else
		{
			status::error('Error - e-mail has not been successfully sent');
			url::redirect('email_queues/show_all_unsent');
		}
	}
	
	/**
	 * Deletes message from queue
	 * 
	 * @author Michal Kliment
	 * @param integer $email_queue_id 
	 */
	public function delete($email_queue_id = NULL)
	{
		// access check
		if (!$this->acl_check_view('Settings_Controller', 'system'))
			Controller::error(ACCESS);
		
		// bad parameter
		if (!$email_queue_id || !is_numeric($email_queue_id))
			Controller::warning(PARAMETER);
		
		$email_queue = new Email_queue_Model($email_queue_id);
		
		// record doens't exist
		if (!$email_queue->id)
			Controller::error(RECORD);
		
		if ($email_queue->state != Email_queue_Model::STATE_OK)
		{
			$email_queue->delete();
			status::success('Message has been successfully deleted');
		}
		else
			status::error('Error - it is not possible delete already sent message');
		
		url::redirect('email_queues/show_all_unsent');
	}
	
	/**
	 * Deletes all unsended emails
	 * 
	 * @author Ondřej Fibich
	 */
	public function delete_unsended()
	{
		// access
		if (!$this->acl_check_view('Settings_Controller', 'system'))
		{
			Controller::error(ACCESS);
		}
		
		// model
		$eq_model = new Email_queue_Model();
		
		// count first
		$count = $eq_model->where(array
		(
			'state'	=> Email_queue_Model::STATE_NEW
		))->count_all();
		
		// delete all
		$eq_model->where(array
		(
			'state'	=> Email_queue_Model::STATE_NEW
		))->delete_all();
		
		// send notification
		status::success('%d unsended e-mails has been deleted.', TRUE, $count);
		// redirects
		url::redirect('email_queues/show_all_sent');
	}
	
}
