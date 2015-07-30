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
 * Phone_invoices_Controller managing phone invoices.
 *
 * @author Ondřej Fibich
 * @package Controller
 */
class Phone_invoices_Controller extends Controller
{
	/**
	 * List of parsers
	 *
	 * @var array
	 */
	private static $PARSERS = array
	(
		2 => 'Vodafone, 09.2011 >=',
		1 => 'Vodafone, 08.2011 <=',
	);
	
	/**
	 * Index of controller
	 * Redirects to show_all
	 */
	public function index()
	{
		url::redirect('phone_invoices/show_all');
	}

	/**
	 * Imports invoice and save it to the database.
	 * Tries to figured out which services was private and which was not.
	 */
	public function import()
	{
		if (!$this->acl_check_new('Phone_invoices_Controller', 'invoices'))
		{
			Controller::error(ACCESS);
		}

		$form = new Forge(url::base(TRUE) . url::current(true));
		
		$form->dropdown('parser')
				->label('Parser:')
				->options(self::$PARSERS)
				->rules('required');
		
		$form->textarea('parse')
				->label(__('Text to parse') . ':')
				->rules('required');
		
		$form->checkbox('test_number_count_enabled')
				->checked(TRUE)
				->value('1')
				->label(__('Enable integrity test (all numbers in invoice has ' .
						'to be in extended statement)'
				));
		
		$form->submit('Parse')->id('phone_invoices_sumit');

		if ($form->validate())
		{
			try
			{
				$phone_invoice = new Phone_invoice_Model();
				$phone_invoice->transaction_start();
				
				$integrity_test = ($form->test_number_count_enabled->value == '1');
				
				if ($form->parser->value == 1)
				{
					$data = Parser_Phone_Invoice_Vodafone::parse($form->parse->value, $integrity_test);
				}
				else
				{
					$data = Parser_Phone_Invoice_Vodafone2::parse($form->parse->value, $integrity_test);
				}

				$phone_invoice->set_logger(FALSE);
				$phone_invoice->date_of_issuance = $data->date_of_issuance->format('Y-m-d');
				$phone_invoice->billing_period_from = $data->billing_period_from->format('Y-m-d');
				$phone_invoice->billing_period_to = $data->billing_period_to->format('Y-m-d');
				$phone_invoice->variable_symbol = $data->variable_symbol;
				$phone_invoice->specific_symbol = $data->specific_symbol;
				$phone_invoice->total_price = $data->total_price;
				$phone_invoice->tax = $data->dph;
				$phone_invoice->tax_rate = $data->dph_rate;

				// search if invoice is already in database
				if (!$phone_invoice->is_unique())
				{
					throw new Exception(__('Invoice is already in database'));
				}

				self::_set_invoice_data($data, $phone_invoice);

				// redirect to edit
				$phone_invoice->transaction_commit();
				url::redirect('/phone_invoices/show/' . $phone_invoice->id . '/');
			}
			catch (Exception $e)
			{
				$phone_invoice->transaction_rollback();
				Log::add_exception($e);
				$form->parse->add_error('requied', nl2br($e->getMessage()));
			}
		}
		
		$breadcrumbs = breadcrumbs::add()
				->link('phone_invoices/show_all', 'Phone invoices',
						$this->acl_check_view('Phone_invoices_Controller', 'invoices'))
				->text('Import invoice');

		$view = new View('main');
		$view->title = __('Import invoice');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('phone_invoices/import');
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Shows all invoices.
	 * Enable delete invoice.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 20, $order_by = 'billing_period_from',
			$order_by_direction = 'DESC', $page_word = null, $page = 1)
	{
		if (!$this->acl_check_view('Phone_invoices_Controller', 'invoices'))
		{
			Controller::error(ACCESS);
		}

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
		{
			$limit_results = (int) $this->input->get('record_per_page');
		}
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'date_of_issuance', 'billing_period_from', 'billing_period_to',
			'variable_symbol', 'specific_symbol', 'price', 'locked'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
		{
			$order_by = 'id';
		}
		
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}

		$phone_invoice_model = new Phone_invoice_Model();

		$total_invoices = $phone_invoice_model->count_all();

		if (($sql_offset = ($page - 1) * $limit_results) > $total_invoices)
		{
			$sql_offset = 0;
		}

		$invoices = $phone_invoice_model->get_all_phone_invoices(
				$sql_offset, $limit_results,
				$order_by, $order_by_direction
		);

		$grid = new Grid('phone_invoices/show_all', '', array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 200,
			'selector_min'				=> 200,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang').'/phone_invoices/show_all/'.
											$limit_results.'/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_invoices,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results
		));

		$grid->order_field('id')
				->label(__('ID'));
		
		$grid->order_field('date_of_issuance')
				->label(__('Date of issue'));
		
		$grid->order_field('billing_period_from');
		
		$grid->order_field('billing_period_to');
		
		$grid->order_field('variable_symbol');
		
		$grid->order_field('specific_symbol');
		
		$grid->order_callback_field('price')
				->label(__('Price vat'))
				->callback('callback::money');

		$actions = $grid->grouped_action_field();
		
		$actions->add_conditional_action()
				->icon_action('show')
				->condition('is_locked')
				->url('phone_invoices/show');
		
		$actions->add_conditional_action()
				->icon_action('edit')
				->condition('is_not_locked')
				->url('phone_invoices/show');
		
		$actions->add_conditional_action()
				->icon_action('delete')
				->condition('is_not_locked')
				->url('phone_invoices/delete')
				->class('delete_link');

		$grid->datasource($invoices);
		
		$breadcrumbs = breadcrumbs::add()
				->text('Phone invoices');

		$view = new View('main');
		$view->title = __('Show all invoices');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('phone_invoices/main');
		$view->content->content = new View('show_all');
		$view->content->content->headline = __('Show all invoices');
		$view->content->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Displays detail of invoice and list of phone numbers on invoice
	 * Enable assign user to each detail
	 * @param integer $phone_invoice_id
	 * @param integer $phone_invoice_user_id
	 */
	public function show($phone_invoice_id = NULL, $phone_invoice_user_id = NULL)
	{
		if (!$this->acl_check_view('Phone_invoices_Controller', 'details'))
		{
			Controller::error(ACCESS);
		}

		$phone_invoice = new Phone_invoice_Model($phone_invoice_id);

		if (!$phone_invoice_id || !$phone_invoice->id)
		{
			url::redirect('phone_invoices');
		}

		$form = NULL;
		$phone_inv_user_model = new Phone_invoice_user_Model();
		
		if ($this->acl_check_edit('Phone_invoices_Controller', 'details') &&
			intval($phone_invoice_user_id))
		{
			$phone_inv_user_model->find($phone_invoice_user_id);

			if (!$phone_inv_user_model->id)
			{
				Controller::error(RECORD);
			}

			if ($phone_inv_user_model->phone_invoice->locked)
			{
				Controller::error(ACCESS);
			}

			$form = new Forge(url::base(TRUE) . url::current(true));

			$form->dropdown('user_id')
					->label(__('Phone') . ' ' . $phone_inv_user_model->phone_number)
					->options(ORM::factory('user')->select_list_grouped())
					->selected($phone_inv_user_model->user_id)
					->rules('required');

			if ($form->validate())
			{
				$phone_inv_user_model->user_id = $form->user_id->value;
				$phone_inv_user_model->save();

				status::success('User has been assigned');
				url::redirect('phone_invoices/show/' . $phone_invoice_id);
			}

			$form->submit('Save');
		}

		$grid = new Grid('phone_invoices/show', NULL, array
		(
			'use_paginator' => false,
			'use_selector' => false
		));

		$is_payed = $phone_invoice->is_payed();
		$query = $phone_inv_user_model->get_all_invoice_users($phone_invoice->id);

		$grid->callback_field('state')
				->label('')
				->callback('callback::phone_invoice_user_state2');
		
		$grid->field('phone_number')
				->label(__('Phone number'));
		
		$grid->callback_field('user')
				->label(__('User'))
				->callback('Phone_invoices_Controller::user_field');
		
		$grid->callback_field('price_private')
				->label(__('Private') . '&nbsp;' . help::hint('price_tax'))
				->callback('callback::money');
		
		$grid->callback_field('price_company')
				->label(__('Company') . '&nbsp;' . help::hint('price_tax'))
				->callback('callback::money');
		
		if ($is_payed)
		{
			$grid->callback_field('transfer_id')
					->label('Transfer')
					->callback('callback::transfer_link');
		}
		
		$actions = $grid->grouped_action_field();
		 
		$actions->add_action()
				->icon_action('show')
				->url('phone_invoices/show_details')
				->label('Show details');

		if ($this->acl_check_edit('Phone_invoices_Controller', 'details') &&
			$phone_invoice->locked == 0)
		{
			$actions->add_action()
					->icon_action('user_add')
					->url('phone_invoices/show/' . $phone_invoice->id)
					->label('Assign user');
		}

		if ($this->acl_check_delete('Phone_invoices_Controller', 'details') &&
			$phone_invoice->locked == 0)
		{
			$actions->add_action()
					->icon_action('delete')
					->url('phone_invoices/delete_user_invoice')
					->class('delete_link');
		}

		$grid->datasource($query);
		
		$breadcrumbs = breadcrumbs::add()
				->link('phone_invoices/show_all', 'Phone invoices',
						$this->acl_check_view('Phone_invoices_Controller', 'invoices'))
				->disable_translation()
				->text($phone_invoice->billing_period_from . ' - ' .
						$phone_invoice->billing_period_to . ' (' . 
						$phone_invoice->id. ')'
				);

		$view = new View('main');
		$view->title = __('Detail of invoice');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('phone_invoices/show');
		$view->content->is_payed = $is_payed;
		$view->content->phone_invoice = $phone_invoice;
		$view->content->total_price = $phone_invoice->total_price + $phone_invoice->tax;
		$view->content->grid = $grid;

		if ($form != NULL)
		{
			$view->content->form = $form->html();
		}

		$view->render(TRUE);
	}

	/**
	 * Deletes invoice
	 * 
	 * @param integer $phone_invoice_id
	 */
	public function delete($phone_invoice_id = 0)
	{
		if (!$this->acl_check_delete('Phone_invoices_Controller', 'invoices'))
		{
			Controller::error(ACCESS);
		}

		if (intval($phone_invoice_id) <= 0)
		{
			Controller::warning(PARAMETER);
		}

		$phone_invoice = new Phone_invoice_Model($phone_invoice_id);

		if (!$phone_invoice->id)
		{
			Controller::error(RECORD);
		}

		$phone_invoice->delete();
		
		status::success('Phone invoice has been deleted');
		url::redirect('phone_invoices');
	}

	/**
	 * Deletes user phone invoice
	 * 
	 * @param integer $phone_user_invoice_id
	 */
	public function delete_user_invoice($phone_user_invoice_id = 0)
	{
		if (intval($phone_user_invoice_id) <= 0)
		{
			Controller::warning(PARAMETER);
		}
		
		if (!$this->acl_check_delete('Phone_invoices_Controller', 'details'))
		{
			Controller::error(ACCESS);
		}

		$phone_user_invoice = new Phone_invoice_user_Model($phone_user_invoice_id);

		if (!$phone_user_invoice->id)
		{
			Controller::error(RECORD);
		}
		
		$phone_invoice_id = $phone_user_invoice->phone_invoice_id;
		$phone_user_invoice->delete();
		
		status::success('Users phone invoice has been deleted');
		url::redirect('phone_invoices/show/' . $phone_invoice_id);
	}

	/**
	 * (Un)Lock invoice for editing
	 * 
	 * @param integer $phone_invoice_id
	 */
	public function lock_set($phone_invoice_id = 0)
	{
		if (!$this->acl_check_new('Phone_invoices_Controller', 'lock'))
		{
			Controller::error(ACCESS);
		}

		if (intval($phone_invoice_id) <= 0)
		{
			Controller::warning(PARAMETER);
		}

		$phone_invoice = new Phone_invoice_Model($phone_invoice_id);

		if (!$phone_invoice->id)
		{
			Controller::error(RECORD);
		}
		
		if ($phone_invoice->is_payed())
		{
			Controller::error(ACCESS);
		}

		$phone_invoice->locked = !$phone_invoice->locked;
		$phone_invoice->save();
		
		status::success(
				__('Phone invoice has been') . ' ' .
				__('' . (!$phone_invoice->locked ? 'un' : '') . 'locked'),
				FALSE
		);
		
		url::redirect('phone_invoices/show/' . $phone_invoice_id);
	}

	/**
	 * Sends warning to users which numbers are on invoice.
	 * @see Mail_messages_Model
	 * @param integer $phone_invoice_id
	 */
	public function post_mail_warning($phone_invoice_id = 0)
	{
		if (!$this->acl_check_new('Phone_invoices_Controller', 'mail_warning'))
		{
			Controller::error(ACCESS);
		}

		if (intval($phone_invoice_id) <= 0)
		{
			Controller::warning(PARAMETER);
		}

		$phone_invoice_model = new Phone_invoice_Model($phone_invoice_id);

		if (!$phone_invoice_model->id)
		{
			Controller::error(RECORD);
		}

		$phone_invoice_user_model = new Phone_invoice_user_Model();
		$phone_invoice_users = $phone_invoice_user_model->get_all_invoice_users(
				$phone_invoice_model->id
		);

		$mail_messsage_model = new Mail_message_Model();

		foreach ($phone_invoice_users as $phone_invoice_user)
		{
			if ($phone_invoice_user->user_id == 0)
			{
				continue;
			}

			$mail_messsage_model->from_id = $this->session->get('user_id');
			$mail_messsage_model->to_id = $phone_invoice_user->user_id;
			$mail_messsage_model->time = date('Y-m-d H:i:s');
			$mail_messsage_model->from_deleted = 1; // user to can delete it
			$mail_messsage_model->subject = mail_message::format(
					'phone_invoice_warning_subject', array
					(
						$phone_invoice_user->phone_number,
						$phone_invoice_model->billing_period_from,
						$phone_invoice_model->billing_period_to
					)
			);
			$mail_messsage_model->body = mail_message::format(
					'phone_invoice_warning', array
					(
						$phone_invoice_user->phone_number,
						url_lang::base() . 'phone_invoices/show_by_user/' .
						$phone_invoice_user->user_id
					)
			);
			$mail_messsage_model->save();
			$mail_messsage_model->clear();
		}

		status::success('Users has been warned about invoice.');
		url::redirect('phone_invoices/show/' . $phone_invoice_id);
	}

	/**
	 * Disccount private services price from users credit
	 * 
	 * @param integer $phone_invoice_id
	 */
	public function pay($phone_invoice_id = 0)
	{
		if (!$this->acl_check_new('Phone_invoices_Controller', 'pay'))
		{
			Controller::error(ACCESS);
		}

		if (intval($phone_invoice_id) <= 0)
		{
			Controller::warning(PARAMETER);
		}

		$phone_invoice = new Phone_invoice_Model($phone_invoice_id);
		$phone_invoice_user = new Phone_invoice_user_Model();

		if (!$phone_invoice->id)
		{
			Controller::error(RECORD);
		}
		
		// is not locked?
		if (!$phone_invoice->locked)
		{
			status::warning('Invoice has to be locked for this operation.');
			url::redirect('phone_invoices/show/' . $phone_invoice_id);
		}
		
		// already payed?
		if ($phone_invoice->is_payed())
		{
			status::warning('Private services already discounted.');
			url::redirect('phone_invoices/show/' . $phone_invoice_id);
		}
		
		// users of invoice
		$users = $phone_invoice_user->get_all_invoice_users($phone_invoice->id);
		
		// accounts
		$account_model = new Account_Model();

		$operating_id = $account_model->where(array(
			'account_attribute_id'	=> Account_attribute_Model::OPERATING
		))->find()->id;

		try
		{
			$phone_invoice->transaction_start();
			
			// for each number in invoice
			foreach ($users as $user)
			{
				// load
				$phone_invoice_user->find($user->id);
				
				// unassigned or empty or asociation?
				if (!$user->user_id ||
					!$phone_invoice_user->id ||
					$user->price_private == 0 ||
					$phone_invoice_user->user->member_id == Member_Model::ASSOCIATION)
				{
					continue;
				}
				
				// account
				$credit_id = $account_model->where(array
				(
					'member_id'				=> $phone_invoice_user->user->member_id,
					'account_attribute_id'	=> Account_attribute_Model::CREDIT
				))->find()->id;

				// add transfer
				$transfer_id = Transfer_Model::insert_transfer(
					$credit_id, $operating_id, null, null,
					$this->session->get('user_id'),
					null, date('Y-m-d'), date('Y-m-d H:i:s'),
					__('Phone invoice') . ' ' . $phone_invoice->id,
					$user->price_private
				);

				// add to user
				$phone_invoice_user->transfer_id = $transfer_id;
				$phone_invoice_user->save_throwable();
			}
			
			// commit
			$phone_invoice->transaction_commit();
			
			status::success(
					'Prices of private services has been discounted ' .
					'from phone keepers credit accounts.'
			);
		}
		catch (Kohana_Database_Exception $e)
		{
			$phone_invoice->transaction_rollback();
			status::error('Error - cannot discount private services');
			die($e->__toString());
		}
		
		url::redirect('phone_invoices/show/' . $phone_invoice_id);
	}

	/**
	 * Displays users phone invoices
	 * @param integer $user_id
	 */
	public function show_by_user($user_id = 0)
	{
		if (!is_numeric($user_id) || $user_id <= 0)
		{
			Controller::warning(PARAMETER);
		}

		$user = new User_Model($user_id);

		if (!$user->id)
		{
			Controller::error(RECORD);
		}

		if (!$this->acl_check_view('Phone_invoices_Controller', 'user_invoices', $user->member_id))
		{
			Controller::error(ACCESS);
		}

		$phone_inv_user_model = new Phone_invoice_user_Model();
		$users_inv = $phone_inv_user_model->get_phone_invoices_of_user($user_id);

		$grid = new Grid(url::base(TRUE) . url::current(true), null, array
		(
			'use_paginator' => false,
			'use_selector' => false
		));
		
		$grid->callback_field('state')
				->label('')
				->callback('callback::phone_invoice_user_state');
		
		$grid->field('number');
		
		$grid->field('billing_period_from');
		
		$grid->field('billing_period_to');
		
		$grid->callback_field('price_private')
				->label(__('Private'))
				->callback('callback::money');
		
		$grid->callback_field('price_company')
				->label(__('Company'))
				->callback('callback::money');
		
		$grid->callback_field('transfer_id')
					->label('Transfer')
					->callback('callback::transfer_link');

		$action = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Phone_invoices_Controller', 'dumps', $user->member_id))
		{
			$action->add_conditional_action()
					->icon_action('show')
					->condition('is_locked_or_filled')
					->url('phone_invoices/show_details');
		}

		if ($this->acl_check_edit('Phone_invoices_Controller', 'dumps', $user->member_id))
		{
			$action->add_conditional_action()
					->icon_action('edit')
					->condition('is_not_locked_and_not_filled')
					->url('phone_invoices/show_details');
		}

		$grid->datasource($users_inv);

		$view = new View('main');
		$view->title = __('Phone invoices of user');
		$view->content = new View('phone_invoices/show_by_user');
		$view->content->grid = $grid;
		$view->content->total_prices = $phone_inv_user_model->get_total_prices($user_id);
		$view->render(TRUE);
	}

	/**
	 * Show details of phone number on invoice.
	 * Show sum of price of each service.
	 * Show payment which has to be done by user.
	 * Show detail extract of services, enable to set or unset private services
	 * @param integer $phone_invoice_user_id
	 * @param string $detail_of
	 */
	public function show_details($phone_invoice_user_id = 0, $detail_of = 'calls')
	{
		$detail_of_arrgs = array
		(
			'calls', 'fixed_calls', 'pays', 'connections', 'pays', 'vpn_calls',
			'sms_messages', 'roaming_sms_messages'
		);

		$detail_of_arrgs_intelligent_search = array
		(
			'calls', 'fixed_calls', 'sms_messages', 'roaming_sms_messages'
		);

		if (!$phone_invoice_user_id || !is_numeric($phone_invoice_user_id))
		{
			Controller::warning(PARAMETER);
		}

		if (array_search($detail_of, $detail_of_arrgs) === false)
		{
			url::redirect(
					'phone_invoices/show_details/' .
					$phone_invoice_user_id . '/calls'
			);
		}

		$phone_inv_user_model = new Phone_invoice_user_Model($phone_invoice_user_id);

		if (!$phone_inv_user_model->id)
		{
			Controller::error(RECORD);
		}

		$user = new User_Model($phone_inv_user_model->user_id);

		if (!$this->acl_check_view('Phone_invoices_Controller', 'dumps') &&
			!$this->acl_check_view('Phone_invoices_Controller', 'dumps', $user->member_id))
		{
			Controller::error(ACCESS);
		}
		
		// save locking by user
		if ($_POST && array_key_exists('phone_user_invoice_lock', $_POST))
		{
			$phone_inv_user_model->locked = ($_POST['phone_user_invoice_lock'] ==  'lock') ? 1 : 0;
			$phone_inv_user_model->save();
			unset($_POST['phone_user_invoice_lock']);
		}

		$phone_inv_model = new Phone_invoice_Model($phone_inv_user_model->phone_invoice_id);

		$grid = new Grid('phone_invoices/show_details', NULL, array
		(
			'use_paginator' => false,
			'use_selector' => false
		));
		
		$grid->field('id')
				->label(__('ID'));
		
		$grid->field('datetime')
				->label(__('Date'));

		$heading = null;
		
		$edit_enabled = ((
			$this->acl_check_edit('Phone_invoices_Controller', 'dumps') &&
			!$phone_inv_model->is_payed()
		) || (
			$phone_inv_model->locked == 0 &&
			$phone_inv_user_model->locked == 0 &&
			$this->acl_check_edit(
					'Phone_invoices_Controller', 'dumps', $user->member_id
			)
		));

		switch ($detail_of)
		{
			case 'calls':
				$call = new Phone_call_Model();

				$grid->callback_field('number')->label(__('Called number'))
						->callback('callback::phone_number_field');

				if ($edit_enabled)
				{
					if ($_POST && count($_POST))
					{
						$call->set_calls_private($phone_inv_user_model->id, @$_POST['private']);
					}

					$grid->form_field('private')
							->type('checkbox')
							->order(false)
							->callback('callback::phone_private_checkbox');
				}
				else
				{
					$grid->callback_field('private')
							->callback('callback::phone_invoice_private_field_locked');
				}

				$grid->field('length');
				
				$grid->callback_field('price')
						->callback('callback::money');
				
				$grid->callback_field('period')
						->callback('callback::phone_period_field');
				
				$grid->field('description');

				$grid->datasource($call->get_calls_from($phone_inv_user_model->id));

				$heading = __('Calls');
				break;
			case 'fixed_calls':
				$call = new Phone_fixed_call_Model();

				$grid->callback_field('number')
						->label(__('Called number'))
						->callback('callback::phone_number_field');

				if ($edit_enabled)
				{
					if ($_POST && count($_POST))
					{
						$call->set_fixed_calls_private($phone_inv_user_model->id, @$_POST['private']);
					}

					$grid->form_field('private')
							->type('checkbox')
							->order(false)
							->callback('callback::phone_private_checkbox');
				}
				else
				{
					$grid->callback_field('private')
							->callback('callback::phone_invoice_private_field_locked');
				}

				$grid->field('length');
				
				$grid->callback_field('price')
						->callback('callback::money');
				
				$grid->callback_field('period')
						->callback('callback::phone_period_field');
				
				$grid->field('destiny')
						->label('Destination');

				$grid->datasource($call->get_fixed_calls_from($phone_inv_user_model->id));

				$heading = __('Fixed calls');
				break;
			case 'vpn_calls':
				$call = new Phone_vpn_call_Model();

				$grid->callback_field('number')
						->label('Called number')
						->callback('callback::phone_number_field');

				if ($edit_enabled)
				{
					if ($_POST && count($_POST))
					{
						$call->set_vpn_calls_private($phone_inv_user_model->id, @$_POST['private']);
					}

					$grid->form_field('private')
							->type('checkbox')
							->order(false)
							->callback('callback::phone_private_checkbox');
				}
				else
				{
					$grid->callback_field('private')
							->callback('callback::phone_invoice_private_field_locked');
				}

				$grid->field('length');
				
				$grid->callback_field('price')
						->callback('callback::money');
				
				$grid->callback_field('period')
						->callback('callback::phone_period_field');
				
				$grid->field('group');

				$grid->datasource($call->get_vpn_calls_from($phone_inv_user_model->id));

				$heading = __('VPN calls');
				break;
			case 'sms_messages':
				$sms = new Phone_sms_message_Model();

				$grid->callback_field('number')
						->callback('callback::phone_number_field');

				if ($edit_enabled)
				{
					if ($_POST && count($_POST))
					{
						$sms->set_sms_mesages_private($phone_inv_user_model->id, @$_POST['private']);
					}

					$grid->form_field('private')
							->type('checkbox')
							->order(false)
							->callback('callback::phone_private_checkbox');
				}
				else
				{
					$grid->callback_field('private')
							->callback('callback::phone_invoice_private_field_locked');
				}

				$grid->callback_field('price')
						->callback('callback::money');
				
				$grid->callback_field('period')
						->callback('callback::phone_period_field');
				
				$grid->field('description');

				$grid->datasource($sms->get_sms_mesages_from($phone_inv_user_model->id));

				$heading = __('SMS messages');
				break;
			case 'roaming_sms_messages':
				$sms = new Phone_roaming_sms_message_Model();

				$grid->callback_field('price')
						->callback('callback::money');
				
				$grid->field('roaming_zone');

				if ($edit_enabled)
				{
					if ($_POST && count($_POST))
					{
						$sms->set_roaming_sms_messages_private($phone_inv_user_model->id, @$_POST['private']);
					}

					$grid->form_field('private')
							->type('checkbox')
							->order(false);
				}
				else
				{
					$grid->callback_field('private')
							->callback('callback::phone_invoice_private_field_locked');
				}

				$grid->datasource($sms->get_roaming_sms_messages_from($phone_inv_user_model->id));

				$heading = __('SMS messages');
				break;
			case 'pays':
				$pay = new Phone_pay_Model();

				$grid->callback_field('number')
						->callback('callback::phone_number_field');
				
				$grid->callback_field('price')
						->callback('callback::money');
				
				$grid->field('description');

				if ($edit_enabled)
				{
					if ($_POST && count($_POST))
					{
						$pay->set_pays_private($phone_inv_user_model->id, @$_POST['private']);
					}

					$grid->form_field('private')
							->type('checkbox')
							->order(false)
							->callback('callback::phone_private_checkbox');
				}
				else
				{
					$grid->callback_field('private')
							->callback('callback::phone_invoice_private_field_locked');
				}

				$grid->datasource($pay->get_pays_from($phone_inv_user_model->id));

				$heading = __('Pays');
				break;
			case 'connections':
				$conn = new Phone_connection_Model();

				$grid->field('transfered');
				
				$grid->callback_field('period')
						->callback('callback::phone_period_field');
				
				$grid->field('apn')
						->label('APN');
				
				$grid->callback_field('price')
						->callback('callback::money');

				if ($edit_enabled)
				{
					if ($_POST && count($_POST))
					{
						$conn->set_connections_private($phone_inv_user_model->id, @$_POST['private']);
					}

					$grid->form_field('private')
							->type('checkbox')->order(false);
				}
				else
				{
					$grid->callback_field('private')
							->callback('callback::phone_invoice_private_field_locked');
				}

				$grid->datasource($conn->get_connections_from($phone_inv_user_model->id));

				$heading = __('Connections');
				break;
		}

		$prices = $phone_inv_user_model->get_prices();

		$price_private = $prices->phone_calls_private +
				$prices->phone_fixed_calls_private +
				$prices->phone_vpn_calls_private +
				$prices->phone_connections_private +
				$prices->phone_sms_messages_private +
				$prices->phone_roaming_sms_messages_private +
				$prices->phone_pays_private;

		$price_company = $prices->phone_calls_company +
				$prices->phone_fixed_calls_company +
				$prices->phone_vpn_calls_company +
				$prices->phone_connections_company +
				$prices->phone_sms_messages_company +
				$prices->phone_roaming_sms_messages_company +
				$prices->phone_pays_company;

		$phone_invoice = $phone_inv_user_model->phone_invoice;
		$user = $phone_inv_user_model->user;
		$price = round($price_private + ($price_private * $phone_invoice->tax_rate / 100), 2);
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('phone_invoices/show_all', 'Phone invoices',
						$this->acl_check_view('Phone_invoices_Controller', 'invoices'))
				->disable_translation()
				->link('phone_invoices/show/' . $phone_invoice->id, 
						$phone_invoice->billing_period_from . ' - ' .
						$phone_invoice->billing_period_to . ' (' . 
						$phone_invoice->id. ')',
						$this->acl_check_view('Phone_invoices_Controller', 'details')
				)
				->text($phone_inv_user_model->phone_number);

		// view
		$view = new View('main');
		$view->title = __('Detail of invoice');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('phone_invoices/show_details');
		$view->content->edit_enabled = $edit_enabled;
		$view->content->phone_invoice = $phone_inv_model;
		$view->content->phone_invoice_user = $phone_inv_user_model;
		$view->content->phone_invoice_user_lock_enabled = 
				$phone_inv_user_model->user_id == $_SESSION['user_id'];
		$view->content->grid = $grid;
		$view->content->heading = $heading;
		$view->content->tax_rate = $phone_invoice->tax_rate;
		$view->content->price = $price;
		$view->content->prices = $prices;
		$view->content->price_company = $price_company;
		$view->content->price_private = $price_private;
		$view->content->user = $user;
		$view->content->detail_of = $detail_of;
		$view->content->intelligent_select_on = array_search(
						$detail_of, $detail_of_arrgs_intelligent_search
				) !== false;
		$view->render(TRUE);
	}

	/**
	 * Show history between user and number.
	 * Shows SMS, Calls, Fixed calls
	 * 
	 * @param integer $user_id
	 * @param string $number
	 * @param integer $phone_invoice_user_id
	 */
	public function show_history(
			$user_id = NULL, $number = NULL, $phone_invoice_user_id = NULL)
	{
		if (!is_numeric($user_id) || empty($number) || !is_numeric($phone_invoice_user_id))
		{
			Controller::warning(PARAMETER);
		}

		$user = new User_Model($user_id);
		$phone_invoice_user = new Phone_invoice_user_Model($phone_invoice_user_id);

		if (!$user->id || !$phone_invoice_user->id)
		{
			Controller::error(RECORD);
		}
		
		$phone_invoice = $phone_invoice_user->phone_invoice;

		if (!$this->acl_check_view('Phone_invoices_Controller', 'dumps') &&
			!$this->acl_check_view('Phone_invoices_Controller', 'dumps', $user->member_id))
		{
			Controller::error(ACCESS);
		}

		// models for quering data
		$calls_model = new Phone_call_Model();
		$fixed_calls_model = new Phone_fixed_call_Model();
		$sms_model = new Phone_sms_message_Model();

		// call grid
		$grid_calls = new Grid('phone_invoices/show_history', NULL, array
		(
			'use_paginator' => false,
			'use_selector' => false
		));
		
		$grid_calls->field('datetime')
				->label('Date');
		
		$grid_calls->field('number')
				->label('Called number');
		
		$grid_calls->field('length');
		
		$grid_calls->callback_field('price')
				->callback('callback::money');
		
		$grid_calls->callback_field('period')
				->callback('callback::phone_period_field');
		
		$grid_calls->callback_field('private')
				->callback('callback::phone_invoice_private_field_locked');
		
		$grid_calls->datasource($calls_model->get_history($user_id, $number));

		// fixed calls grid
		$grid_fixed_calls = new Grid('phone_invoices/show_history', NULL, array
		(
			'use_paginator' => false,
			'use_selector' => false
		));
		
		$grid_fixed_calls->field('datetime')
				->label('Date');
		
		$grid_fixed_calls->field('number')
				->label('Called number');
		
		$grid_fixed_calls->field('length');
		
		$grid_fixed_calls->callback_field('price')
				->callback('callback::money');
		
		$grid_fixed_calls->callback_field('period')
				->callback('callback::phone_period_field');
		
		$grid_fixed_calls->field('destiny')
				->label('Destination');
		
		$grid_fixed_calls->callback_field('private')
				->callback('callback::phone_invoice_private_field_locked');
		
		$grid_fixed_calls->datasource($fixed_calls_model->get_history($user_id, $number));

		// sms messages grid
		$grid_sms_messages = new Grid(url_lang::base() . 'phone_invoices/show_history', NULL, array
		(
			'use_paginator' => false,
			'use_selector' => false
		));
		
		$grid_sms_messages->field('datetime')
				->label('Date');
		
		$grid_sms_messages->field('number')
				->label('Called number');
		
		$grid_sms_messages->callback_field('price')
				->callback('callback::money');
		
		$grid_sms_messages->callback_field('period')
				->callback('callback::phone_period_field');
		
		$grid_sms_messages->field('description');
		
		$grid_sms_messages->callback_field('private')
				->callback('callback::phone_invoice_private_field_locked');
		
		$grid_sms_messages->datasource($sms_model->get_history($user_id, $number));
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('phone_invoices/show_all', 'Phone invoices',
						$this->acl_check_view('Phone_invoices_Controller', 'invoices'))
				->disable_translation()
				->link('phone_invoices/show/' . $phone_invoice->id, 
						$phone_invoice->billing_period_from . ' - ' .
						$phone_invoice->billing_period_to . ' (' . 
						$phone_invoice->id. ')',
						$this->acl_check_view('Phone_invoices_Controller', 'details')
				)
				->link('phone_invoices/show_details/' . $phone_invoice_user->id,
						$phone_invoice_user->phone_number,
						$this->acl_check_view(
								'Phone_invoices_Controller', 'dumps'
						) || $this->acl_check_view(
								'Phone_invoices_Controller', 'dumps',
								$user->member_id
						)
				)
				->enable_translation()
				->text('History');

		// view
		$view = new View('main');
		$view->title = __('History of phone services between user and phone');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('phone_invoices/show_history');
		$view->content->number = $number;
		$view->content->user = $user;
		$view->content->grid_calls = $grid_calls;
		$view->content->grid_fixed_calls = $grid_fixed_calls;
		$view->content->grid_sms_messages = $grid_sms_messages;
		$view->content->phone_invoice_user_id = $phone_invoice_user->id;

		$view->render(TRUE);
	}

	/**
	 * Ajax intelligent handler.
	 * Output: - 0  if there is any error
	 * 	       - list of ids on success (id,id,id,)
	 * @param integer $phone_invoice_user_id
	 * @param string $detail_of
	 */
	public function intelligent_select_ajax(
			$phone_invoice_user_id = NULL, $detail_of = NULL)
	{

		$detail_of_arrgs = array
		(
			'calls', 'fixed_calls', 'pays', 'connections', 'pays', 'vpn_calls',
			'sms_messages', 'roaming_sms_messages'
		);

		if (!$phone_invoice_user_id || !is_numeric($phone_invoice_user_id))
		{
			echo '0';
			return;
		}

		if (array_search($detail_of, $detail_of_arrgs) === false)
		{
			echo '0';
			return;
		}

		$phone_inv_user_model = new Phone_invoice_user_Model($phone_invoice_user_id);

		if (!$phone_inv_user_model->id)
		{
			echo '0';
			return;
		}

		$user = new User_Model($phone_inv_user_model->user_id);

		if (!$this->acl_check_view('Phone_invoices_Controller', 'dumps') &&
			!$this->acl_check_view('Phone_invoices_Controller', 'dumps', $user->member_id))
		{
			echo '0';
			return;
		}

		$result = NULL;

		switch ($detail_of)
		{
			case 'calls':
				$model = new Phone_call_Model();
				$result = $model->get_private_property_by_history(
						$user->id, $phone_invoice_user_id
				);
				break;
			case 'fixed_calls':
				$model = new Phone_fixed_call_Model();
				$result = $model->get_private_property_by_history(
						$user->id, $phone_invoice_user_id
				);
				break;
			case 'sms_messages':
				$model = new Phone_sms_message_Model();
				$result = $model->get_private_property_by_history(
						$user->id, $phone_invoice_user_id
				);
				break;
			case 'roaming_sms_messages':
				$model = new Phone_roaming_sms_message_Model();
				$result = $model->get_private_property_by_history(
						$user->id, $phone_invoice_user_id
				);
				break;
			default:
				echo '0';
				return;
		}

		foreach ($result as $r)
		{
			if ($r->private == '1')
			{
				echo $r->id . ',';
			}
		}
	}

	/**
	 * Ajax set handler
	 * Output: - 0 on any error
	 * 	       - 1 on success
	 * @param integer $phone_invoice_user_id
	 * @param string $detail_of
	 */
	public function user_details_set_private_ajax($phone_invoice_user_id = 0, $detail_of = 'calls')
	{
		$detail_of_arrgs = array(
			'calls', 'fixed_calls', 'pays', 'connections', 'pays', 'vpn_calls',
			'sms_messages', 'roaming_sms_messages'
		);

		if (!$phone_invoice_user_id || !is_numeric($phone_invoice_user_id))
		{
			echo '0';
			return;
		}

		if (array_search($detail_of, $detail_of_arrgs) === false)
		{
			echo '0';
			return;
		}

		$phone_inv_user_model = new Phone_invoice_user_Model($phone_invoice_user_id);

		if (!$phone_inv_user_model->id)
		{
			echo '0';
			return;
		}

		$user = new User_Model($phone_inv_user_model->user_id);

		if (!$this->acl_check_view('Phone_invoices_Controller', 'dumps') &&
			!$this->acl_check_view('Phone_invoices_Controller', 'dumps', $user->member_id))
		{
			echo '0';
			return;
		}

		switch ($detail_of)
		{
			case 'calls':
				$model = new Phone_call_Model();
				$model->set_calls_private(
						$phone_inv_user_model->id, @$_POST['private']
				);
				echo '1';
				break;
			case 'fixed_calls':
				$model = new Phone_fixed_call_Model();
				$model->set_fixed_calls_private(
						$phone_inv_user_model->id, @$_POST['private']
				);
				echo '1';
				break;
			case 'vpn_calls':
				$model = new Phone_vpn_call_Model();
				$model->set_vpn_calls_private(
						$phone_inv_user_model->id, @$_POST['private']
				);
				echo '1';
				break;
			case 'sms_messages':
				$model = new Phone_sms_message_Model();
				$model->set_sms_mesages_private(
						$phone_inv_user_model->id, @$_POST['private']
				);
				echo '1';
				break;
			case 'roaming_sms_messages':
				$model = new Phone_roaming_sms_message_Model();
				$model->set_roaming_sms_messages_private(
						$phone_inv_user_model->id, @$_POST['private']
				);
				echo '1';
				break;
			default:
				echo '0';
		}
	}

	/**
	 * Callback for user fields in phone invoices.
	 * 
	 * @param unknown_type $item
	 * @param unknown_type $name
	 */
	protected function user_field($item, $name)
	{
		if (!empty($item->name))
		{
			echo html::anchor(
					'users/show/' . $item->user_id . '/' .
					$item->id, $item->name
			);
			// is user in telephonists group
			$user_model = new User_Model($item->user_id);
			
			if (!$this->is_user_in_group(
					Aro_group_Model::TELEPHONISTS, $item->user_id
				))
			{
				echo ' <b style="color: red;">(' . __('Is not telephonists') . '!)</b>';
			}
		}
		else
		{
			echo '<span class="error">' . __('Not assigned') . '</span>';
		}
	}

	/**
	 * Add data from object Bill_Data to database
	 * @param Bill_Data           $data Data
	 * @param Phone_invoice_Model $phone_invoice
	 */
	private function _set_invoice_data(
			Bill_Data &$data, Phone_invoice_Model &$phone_invoice)
	{
		$phone_invoice->save_throwable();

		/* @var $services Services */
		foreach ($data->bill_numbers as $services)
		{
			$user_id = self::user_number_cache($services->number);
			
			if (!intval($user_id))
			{
				$user_id = NULL;
			}
			
			$phone_inv_user = new Phone_invoice_user_Model();
			$phone_inv_user->set_logger(FALSE);
			$phone_inv_user->user_id = $user_id;
			$phone_inv_user->phone_invoice_id = $phone_invoice->id;
			$phone_inv_user->phone_number = $services->number;
			$phone_inv_user->save_throwable();

			/* @var $item Call_Service */
			foreach ($services->calls as $item)
			{
				$model = new Phone_call_Model();
				$model->set_logger(FALSE);
				$model->phone_invoice_user_id = $phone_inv_user->id;
				$model->datetime = $item->date_time->format('Y-m-d H:i:s');
				$model->price = $item->price;
				$model->length = $item->length;
				$model->number = $item->number;
				$model->period = $item->period;
				$model->description = $item->description;

				$ppcontact_id = ($phone_inv_user->user_id < 0) ? 0 :
						Private_phone_contacts_Controller::private_contacts_cache(
								$phone_inv_user->user_id, $item->number
						);

				$model->private = ($ppcontact_id <= 0) ? '0' : '1';

				$model->save_throwable();
			}

			/* @var $item Fixed_Call_Service */
			foreach ($services->fixed_calls as $item)
			{
				$model = new Phone_fixed_call_Model();
				$model->set_logger(FALSE);
				$model->phone_invoice_user_id = $phone_inv_user->id;
				$model->datetime = $item->date_time->format('Y-m-d H:i:s');
				$model->price = $item->price;
				$model->length = $item->length;
				$model->number = $item->number;
				$model->period = $item->period;
				$model->destiny = $item->destiny;

				$ppcontact_id = ($phone_inv_user->user_id < 0) ? 0 :
						Private_phone_contacts_Controller::private_contacts_cache(
								$phone_inv_user->user_id, $item->number
						);

				$model->private = (($ppcontact_id <= 0) &&
						(self::user_number_cache($model->number) != 0)) ? '0' : '1';

				$model->save_throwable();
			}

			/* @var $item Vpn_Call_Service */
			foreach ($services->vpn_calls as $item)
			{
				$model = new Phone_vpn_call_Model();
				$model->set_logger(FALSE);
				$model->phone_invoice_user_id = $phone_inv_user->id;
				$model->datetime = $item->date_time->format('Y-m-d H:i:s');
				$model->price = $item->price;
				$model->length = $item->length;
				$model->number = $item->number;
				$model->period = $item->period;
				$model->group = $item->group;
				// VPN calls are payed by org if its not in private numbers

				$ppcontact_id = ($phone_inv_user->user_id < 0) ? 0 :
						Private_phone_contacts_Controller::private_contacts_cache(
								$phone_inv_user->user_id, $item->number
						);

				$model->private = ($ppcontact_id <= 0) ? '0' : '1';

				$model->save_throwable();
			}

			/* @var $item Sms_Service */
			foreach ($services->smss as $item)
			{
				$model = new Phone_sms_message_Model();
				$model->set_logger(FALSE);
				$model->phone_invoice_user_id = $phone_inv_user->id;
				$model->datetime = $item->date_time->format('Y-m-d H:i:s');
				$model->price = $item->price;
				$model->number = $item->number;
				$model->period = $item->period;
				$model->description = $item->description;

				$ppcontact_id = ($phone_inv_user->user_id < 0) ? 0 :
						Private_phone_contacts_Controller::private_contacts_cache(
								$phone_inv_user->user_id, $item->number
						);

				$model->private = ($ppcontact_id <= 0) ? '0' : '1';

				$model->save_throwable();
			}

			/* @var $item RoamingSms_Service */
			foreach ($services->roaming_smss as $item)
			{
				$model = new Phone_roaming_sms_message_Model();
				$model->set_logger(FALSE);
				$model->phone_invoice_user_id = $phone_inv_user->id;
				$model->datetime = $item->date_time->format('Y-m-d H:i:s');
				$model->price = $item->price;
				$model->roaming_zone = $item->roaming_zone;
				$model->private = '1';
				$model->save_throwable();
			}

			/* @var $item Internet_Service */
			foreach ($services->internet as $item)
			{
				$model = new Phone_connection_Model();
				$model->set_logger(FALSE);
				$model->phone_invoice_user_id = $phone_inv_user->id;
				$model->datetime = $item->date_time->format('Y-m-d H:i:s');
				$model->price = $item->price;
				$model->period = $item->period;
				$model->apn = $item->apn;
				$model->transfered = $item->transfered;
				$model->private = '1';
				$model->save_throwable();
			}

			/* @var $item Pay_Service */
			foreach ($services->pays as $item)
			{
				$model = new Phone_pay_Model();
				$model->set_logger(FALSE);
				$model->phone_invoice_user_id = $phone_inv_user->id;
				$model->datetime = $item->date_time->format('Y-m-d H:i:s');
				$model->price = $item->price;
				$model->number = $item->number;
				$model->description = $item->description;
				$model->private = '1';

				$model->save_throwable();
			}
		}
	}

	/**
	 * Cache for connection between user and number
	 * @param string $number  Phone number to find
	 * @return integer  User ID or zero
	 */
	public static function user_number_cache($number)
	{
		static $user_number_cache = array();
		static $model = null;

		if ($model == null)
		{
			$model = new Phone_invoice_user_Model();
		}

		if (!array_key_exists($number, $user_number_cache))
		{
			$user_number_cache[$number] = $model->get_user_id($number);
		}
		return $user_number_cache[$number];
	}

}
