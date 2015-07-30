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
 * SMS controller.
 *
 * @author Lubomir Buben, Roman Sevcik, Ondrej Fibich
 */
class Sms_Controller extends Controller
{
	/**
	 * Contruct checks if SMS are enabled
	 */
	public function __construct()
	{
		parent::__construct();
		
		if (!Sms::enabled())
		{
			$view = new View('main');
			$view->title = __('SMS Messages');
			$view->content = new View('sms/not_enabled');
			$view->content->headline = __('Error - SMS not enabled');
			$view->render(TRUE);
			exit;
		}
	}


	/**
	 * Redirects to show all
	 */
	public function index()
	{
	    url::redirect('sms/show_all');
	}

	/**
	 * This function show all messages in database
	 * 
	 * @author Roman Sevcik
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param mixed $page_word
	 * @param string $page
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'id',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{
		// access
		if (!$this->acl_check_view('Settings_Controller', 'system'))
		{
			Controller::error(ACCESS);
		}
		
	    // get new selector
	    if (is_numeric($this->input->get('record_per_page')))
		{
			$limit_results = (int) $this->input->get('record_per_page');
		}
		
	    // parameters control
	    $allowed_order_type = array
		(
			'id', 'name', 'user_id', 'send_date', 'type', 'sender_id',
			'receiver_id', 'sender_type', 'receiver_type'
		);
		
	    if (!in_array(strtolower($order_by), $allowed_order_type))
	    {
			$order_by = 'user_id';
		}
		
	    if (strtolower($order_by_direction) != 'asc')
	    {
			$order_by_direction = 'desc';
		}
		
		// filter form
		$filter_form = new Filter_form('sms');
		
		$filter_form->add('send_date')
				->type('date')
				->default(Filter_form::OPER_GREATER_OR_EQUAL, date('Y-m-d'));
		
		$filter_form->add('sender')
				->label('Telephone number of sender');
		
		$filter_form->add('receiver')
				->label('Telephone number of receiver');

	    $model_sms_message = new Sms_message_Model();

	    $total_sms_message = $model_sms_message->count_all_messages($filter_form->as_sql());
		
	    if (($sql_offset = ($page - 1) * $limit_results) > $total_sms_message)
	    {
			$sql_offset = 0;
		}

	    $all_sms_message = $model_sms_message->get_all_records(
				$sql_offset, $limit_results,
				$order_by, $order_by_direction,
				$filter_form->as_sql()
		);

	    $headline = __('SMS message list');
		
	    $grid = new Grid('sms', $headline, array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min'				=> 50,
			'selector_max_multiplier'   => 10,
			'base_url'					=> Config::get('lang').'/sms/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_sms_message,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form->html()
	    ));

	    $grid->add_new_button('sms/show_unread', __('Show unread messages'));
	    $grid->add_new_button('sms/delete_unsended', __('Delete unsended messages'));

	    if (Sms::has_active_driver())
		{
			$grid->add_new_button('sms/send', __('Send message'));
		}
		
	    $grid->order_field('id');
		
		$grid->order_callback_field('sender_id')
				->label('Sender')
				->callback('callback::sms_sender_field');
		
		$grid->order_callback_field('sender_type')
				->callback('callback::member_type_field');
		
		$grid->order_callback_field('receiver_id')
				->label('Receiver')
				->callback('callback::sms_receiver_field');
		
		$grid->order_callback_field('receiver_type')
				->callback('callback::member_type_field');
		
	    $grid->order_callback_field('send_date')
				->callback('callback::datetime');
		
	    $grid->order_callback_field('text')
				->callback('callback::limited_text');
		
	    $grid->order_callback_field('type')
				->callback(array($this, 'type'))
				->class('center');
		
	    $grid->callback_field('state')
				->callback(array($this, 'state'));
		
		$grid->grouped_action_field()
				->add_action()
				->icon_action('show')
				->url('sms/show');
		
	    $grid->datasource($all_sms_message);
		
	    $view = new View('main');
	    $view->title = $headline;
	    $view->content = $grid;
		$view->breadcrumbs = __('SMS messages');
	    $view->render(TRUE);
	}

	/**
	 * This function show all unread messages in database
	 * 
	 * @author Roman Sevcik
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param mixed $page_word
	 * @param string $page
	 */
	public function show_unread(
			$limit_results = 100, $order_by = 'id',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{
		// access
		if (!$this->acl_check_view('Settings_Controller', 'system'))
		{
			Controller::error(ACCESS);
		}
		
	    // get new selector
	    if (is_numeric($this->input->get('record_per_page')))
		{
			$limit_results = (int) $this->input->get('record_per_page');
		}
		
	    // parameters control
	    $allowed_order_type = array('id', 'name', 'user_id');
		
	    if (!in_array(strtolower($order_by), $allowed_order_type))
	    {
			$order_by = 'user_id';
		}
		
	    if (strtolower($order_by_direction) != 'asc')
	    {
			$order_by_direction = 'desc';
		}

	    $model_sms_message = new Sms_message_Model();

	    $total_sms_message = $model_sms_message->count_of_unread_messages();
	    
		if (($sql_offset = ($page - 1) * $limit_results) > $total_sms_message)
	    {
			$sql_offset = 0;
		}

	    $sms_messages = $model_sms_message->get_unread_messages(
				$sql_offset, $limit_results,
				$order_by, $order_by_direction
		);
	    
	    $headline = __('SMS message list');
		
	    $grid = new Grid('sms', $headline, array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 100,
			'selector_min'				=> 100,
			'selector_max_multiplier'   => 10,
			'base_url'					=> Config::get('lang').'/sms/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_sms_message,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
	    ));
		
	    $grid->order_field('id');
		
	    $grid->order_field('send_date');
		
	    $grid->order_field('text');
		
	    $grid->order_callback_field('type')
				->class('center')
				->callback(array($this, 'type'));
		
	    $grid->callback_field('state')
				->callback(array($this, 'state'));
		
		$grid->grouped_action_field()
				->add_action()
				->icon_action('show')
				->url('sms/show');
		
	    $grid->datasource($sms_messages);
		
		$breadcrumbs = breadcrumbs::add()
				->link('sms/show_all', 'SMS messages')
				->text('Unread');
		
	    $view = new View('main');
	    $view->title = $headline;
	    $view->content = $grid;
		$view->breadcrumbs = $breadcrumbs->html();
	    $view->render(TRUE);
	}
	
	/**
	 * Deletes all unsended SMS messages
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
		$sms = new Sms_message_Model();
		
		// count first
		$count = $sms->where(array
		(
			'type'	=> Sms_message_Model::SENT,
			'state'	=> Sms_message_Model::SENT_UNSENT
		))->count_all();
		
		// delete all
		$sms->where(array
		(
			'type'	=> Sms_message_Model::SENT,
			'state'	=> Sms_message_Model::SENT_UNSENT
		))->delete_all();
		
		// send notification
		status::success('%d unsended SMS messages has been deleted.', TRUE, $count);
		// redirects
		url::redirect('sms/show_all');
	}

	/**
	 * This function show message in database
	 * 
	 * @author Roman Sevcik
	 * @param integer $sms_id
	 */
	public function show($sms_id = null)
	{
		// access
		if (!$this->acl_check_view('Settings_Controller', 'system'))
		{
			Controller::error(ACCESS);
		}
		
	    if (!isset($sms_id))
		{
			Controller::warning(PARAMETER);
		}

	    $sms = new Sms_message_Model($sms_id);

	    if (!$sms || !$sms->id)
		{
			Controller::error(RECORD);
		}

		// received
	    if ($sms->type == Sms_message_Model::RECEIVED)
		{
			// save new state
			if ($sms->state == Sms_message_Model::RECEIVED_UNREAD)
			{
				$sms->state = Sms_Controller::RECEIVED_READ;
				$sms->save();
			}

			$answer_sms = $sms->sms_message;
			
			if ($answer_sms->id == null)
			{
				$answer = html::anchor(
						'sms/send/' . $sms->sender . '/' . $sms->id,
						__('Answer to this message')
				);
			}
			else
			{
				$answer = html::anchor(
						'sms/show/' . $answer_sms->id,
						__('Show answer for this message')
				);
			}
		}
		// send
		else if ($sms->type == Sms_message_Model::SENT)
		{
			if ($sms->sms_message_id != null)
			{
				$answer = html::anchor(
						'sms/show/' . $sms->sms_message_id,
						__('Show parent for this message')
				);
			}
			else
			{
				$answer = ' - ';
			}
		}

		// find phone prefix
		$code = ORM::factory('country')->find_phone_country_code($sms->receiver);
	    $number = substr($sms->receiver, strlen($code));

		// try to find owner of number
	    $ru = $sms->user->get_user_by_phone_number_country_code($number, $code);

		// receiver founded?
	    if ($ru && $ru->id)
		{
			$receiver = html::anchor('users/show/'.$ru->id, $code.$number, array
			(
				'title' => $ru->name . ' ' . $ru->surname
			));
		}
		// display number
	    else
	    {
			$receiver = $sms->receiver;
		}
		
		$sms_info = $sms->sender . ' &rarr; ' . $sms->receiver . ' (' . $sms->send_date . ')';
		
		$breadcrumbs = breadcrumbs::add()
				->link('sms/show_all', 'SMS messages')
				->disable_translation()
				->text($sms_info);
	    
	    $view = new View('main');
	    $view->title = __('Show SMS message');
	    $view->content = new View('sms/show');
		$view->breadcrumbs = $breadcrumbs->html();
	    $view->content->headline = __('SMS message');
	    $view->content->sms = $sms;
	    $view->content->answer = $answer;
	    $view->content->receiver = $receiver;
	    $view->content->user = $sms->user;
	    $view->render(true);
	}

	/**
	 * Function save message for send to database.
	 * 
	 * @author Roman Sevcik
	 * @param string $phone
	 * @param integer $sms_id
	 * @param integer $selected_subnet .
	 *
	 */
	public function send($phone = null, $sms_id = null, $selected_subnet = null)
	{
		// access
		if (!$this->acl_check_view('Settings_Controller', 'system'))
		{
			Controller::error(ACCESS);
		}
		
		$drivers = Sms::get_active_drivers();

		if (!count($drivers))
		{
			Controller::error(ACCESS, __('No SMS driver enabled!'));
		}

		if (!is_numeric($phone))
		{
			$cid = Settings::get('default_country');
			$phone = ORM::factory('country', $cid)->country_code;
		}

		if (!is_numeric($sms_id))
		{
			$sms_id = null;
		}

		if (!is_numeric($selected_subnet))
		{
			$selected_subnet = null;
		}
		
		$subnet = new Subnet_Model();

		if ($sms_id != null)
		{
			$sms = new Sms_message_Model($sms_id);

			if (!$sms || !$sms->id)
			{
				Controller::error(RECORD);
			}

			$answer_sms = ORM::factory('sms_message')->where(array
			(
				'sms_message_id', $sms_id
			))->find();
			
			if ($answer_sms->id)
			{
				url::redirect('sms/show/' . $answer_sms->id);
			}
		}

		$sn = $this->settings->get('sms_sender_number');
		
		$sender_number[$sn] = $sn;

		$form = new Forge('sms/send/' . $phone . '/' . $sms_id, '', 'POST', array
		(
			'name'	=> 'sms_form',
			'id'	=> 'sms_form'
		));
		
		$form->group('About SMS');
		
		$form->dropdown('sender_number')
				->label(__('Number of the sender') . ':')
				->rules('required')
				->options($sender_number);
		
		$form->dropdown('sms_driver')
				->label(__('Driver') . ':')
				->rules('required')
				->options($drivers)
				->selected($this->settings->get('sms_driver'));

		$form->group('Recipient information');

		$form->hidden('type_receiver')
				->value('number');

		$form->input('receiver_number')
				->label(__('Number of the recipient') . ':')
				->rules('length[9,13]|required|valid_phone')
				->callback(array($this, 'valid_phone_number'))
				->value($phone);

		$form->dateselect('stamp')
				->label(__('Date') . ':');
		
		$form->textarea('text')
				->rules('length[1,760]|required')
				->style('width: 530px; height: 150px');
		
		$form->input('counter')
				->style('width:530px;');

		$form->hidden('s_id')
				->value($sms_id);

		$form->submit('Send');
		
		special::required_forge_style($form, ' *', 'required');

		if ($form->validate())
		{
			$result = array();

			$form_data = $form->as_array();

			if ($form_data['type_receiver'] == 'number')
			{
				$result[0] = new stdClass();
				$result[0]->phone = $form_data['receiver_number'];
			}
			else if ($form_data['type_receiver'] == 'subnet')
			{
				$result = $subnet->get_phones_of_subnet($form_data['subnet_id']);
			}
			
			// no number to send
			if (!count($result))
			{
				status::warning('No SMS message has been added.');
				url::redirect('sms/send');
			}

			try
			{
				$sms = new Sms_message_Model();
				$sms->transaction_start();
				
				foreach ($result as $row)
				{
					$phone = $row->phone;

					$sms->clear();
					$sms->user_id = $this->session->get('user_id');
					$sms->sms_message_id = $form_data['s_id'];
					$sms->stamp = date('Y-m-d H:i:s', time());
					$sms->send_date = date('Y-m-d H:i:s', $form_data['stamp']);
					$sms->text = text::cs_utf2ascii($form_data['text']);
					$sms->sender = $form_data['sender_number'];
					$sms->receiver = $phone;
					$sms->driver = $form_data['sms_driver'];
					$sms->type = Sms_message_Model::SENT;
					$sms->state = Sms_message_Model::SENT_UNSENT;
					$sms->save_throwable();
				}
				
				$sms->transaction_commit();
				status::success('SMS message has been successfully added.');
				url::redirect('sms/show/' . $sms->id);
			}
			catch (Exception $e)
			{
				$sms->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cant add new SMS message.');
			}
		}
		else
		{
			$breadcrumbs = breadcrumbs::add()
					->link('sms/show_all', 'SMS messages')
					->text('Send message');

			$headline = __('Send SMS message');
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Callback for type of SMS message
	 *
	 * @param object $item
	 * @param string $name 
	 */
	protected static function type($item, $name)
	{
		if ($item->type == Sms_message_Model::RECEIVED)
		{
			echo html::image(array
			(
				'src'	=> resource::sms('receive'),
				'alt'	=> __('Received message'),
				'title'	=> __('Received message')
			));
		}
		else if ($item->type == Sms_message_Model::SENT)
		{
			echo html::image(array
			(
				'src'	=> resource::sms('send'),
				'alt'	=> __('Sent message'),
				'title'	=> __('Sent message')
			));
		}
	}

	/**
	 * Callback for state of SMS message
	 *
	 * @param object $item
	 * @param string $name 
	 */
	protected static function state($item, $name)
	{
		if ($item->type == Sms_message_Model::RECEIVED)
		{
			if ($item->state == Sms_message_Model::RECEIVED_READ)
			{
				echo '<div style="color:black;">' . __('Read') . '</div>';
			}
			elseif ($item->state == Sms_message_Model::RECEIVED_UNREAD)
			{
				echo '<b style="color:black;">' . __('Unread') . '</b>';
			}
		}
		else if ($item->type == Sms_message_Model::SENT)
		{
			if ($item->state == Sms_message_Model::SENT_OK)
			{
				echo '<div style="color:green;">' . __('Sent') . '</div>';
			}
			elseif ($item->state == Sms_message_Model::SENT_UNSENT)
			{
				echo '<div style="color:grey;">' . __('Unsent') . '</div>';
			}
			elseif ($item->state == Sms_message_Model::SENT_FAILED)
			{
				echo '<b style="color:red;">' . __('Failed') . '</b>';
			}
		}
	}
	
	/**
	 * Checks validity of phone number and check if it is enabled operator
	 * for sending SMS.
	 * 
	 * @param object $input phone number
	 */
	public static function valid_phone_number($input = NULL)
	{
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$number = trim($input->value);
		
		if (!Phone_operator_Model::is_sms_enabled_for($number))
		{
			$input->add_error('required', __(
					'Phone operator of number is not enabled for sending SMS.'
			));
		}
	}

}
