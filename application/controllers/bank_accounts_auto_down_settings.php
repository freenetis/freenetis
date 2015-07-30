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
 * Controller manages settings of automatical downloading of bank statements.
 * 
 * @package Controller
 * @author Ondřej Fibich
 */
class Bank_accounts_auto_down_settings_Controller extends Controller
{

	/**
	 * Shows all settings of the given bank account.
	 */
	public function show($bank_account_id = NULL)
	{
		// check param
		if (!intval($bank_account_id))
		{
			self::warning(PARAMETER);
		}
		
		$ba = new Bank_account_Model($bank_account_id);
		$ba_ad = new Bank_accounts_automatical_download_Model();
		
		$ba_settings = Bank_Account_Settings::factory($ba->type);
		$ba_settings->load_column_data($ba->settings);
		
		// check if exists
		if (!$ba || !$ba->id ||
			!$ba_settings->can_download_statements_automatically())
		{
			self::error(RECORD);
		}
		
		// access check
		if (!$this->acl_check_view('Accounts_Controller', 'bank_account_auto_down_config'))
		{
			self::error(ACCESS);
		}

		// gets data
		$query = $ba_ad->get_bank_account_settings($ba->id);

		// grid
		$grid = new Grid('bank_accounts_auto_down_settings', null, array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false
		));

		if ($this->acl_check_new('Accounts_Controller', 'bank_account_auto_down_config'))
		{
			$grid->add_new_button(
					'bank_accounts_auto_down_settings/add/' . $ba->id,
					__('Add new rule'), array('class' => 'popup_link')
			);
		}

		$grid->field('id')
				->label('ID');
		
		$grid->callback_field('type')
				->callback('callback::message_auto_setting_type');
		
		$grid->callback_field('attribute')
				->callback('callback::message_auto_setting_attribute');
		
		if (module::e('notification'))
		{
			if (Settings::get('email_enabled'))
			{
				$grid->callback_field('email_enabled')
						->callback('callback::boolean')
						->label('E-mail');
			}

			if (Settings::get('sms_enabled'))
			{
				$grid->callback_field('sms_enabled')
						->callback('callback::boolean')
						->label('SMS');
			}
		}
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_delete('Accounts_Controller', 'bank_account_auto_down_config'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('bank_accounts_auto_down_settings/delete')
					->class('delete_link');
		}
		
		// load datasource
		$grid->datasource($query);
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->text($ba->account_nr . '/' . $ba->bank_nr)
				->text('Setup automatical downloading of statements')
				->html();

		// main view
		$view = new View('main');
		$view->title = __('Automatical settings for downloading of statements');
		$view->content = new View('show_all');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->headline = $view->title;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Adds a new rule
	 *
	 * @param integer $bank_account_id 
	 */
	public function add($bank_account_id = NULL)
	{
		// check param
		if (!$bank_account_id || !is_numeric($bank_account_id))
		{
			self::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_new('Accounts_Controller', 'bank_account_auto_down_config'))
		{
			self::error(ACCESS);
		}

		// load model
		$ba = new Bank_account_Model($bank_account_id);
		$types = Bank_accounts_automatical_download_Model::get_type_messages();
		
		$ba_settings = Bank_Account_Settings::factory($ba->type);
		$ba_settings->load_column_data($ba->settings);
		
		// check if exists
		if (!$ba || !$ba->id ||
			!$ba_settings->can_download_statements_automatically())
		{
			self::error(RECORD);
		}

		// form
		$form = new Forge('bank_accounts_auto_down_settings/add/' . $bank_account_id);

		$form->group('Basic information');
		
		$form->dropdown('type')
				->rules('required')
				->options(array_map('strtolower', $types))
				->style('width:200px');
		
		for ($i = 0; $i < Time_Activity_Rule::get_attribute_types_max_count(); $i++)
		{
			$form->input('attribute[' . $i . ']')
					->callback(array($this, 'valid_attribute'))
					->label(__('Attribute') . ' ' . ($i + 1));
		}
		
		if (module::e('notification'))
		{
			if (Settings::get('email_enabled'))
			{
				$form->checkbox('email_enabled')
						->label('Sending of e-mail messages enabled')
						->value('1')
						->checked(TRUE);
			}

			if (Settings::get('sms_enabled'))
			{
				$form->checkbox('sms_enabled')
						->label('Sending of SMS messages enabled')
						->value('1');
			}
		}
		
		$form->submit('Add');

		// validate form and save data
		if ($form->validate())
		{
			// model
			$baad = new Bank_accounts_automatical_download_Model();
				
			try
			{				
				// start transaction
				$baad->transaction_start();
				
				// load data
				$form_data = $form->as_array();
				
				// prepare attribute
				$attrs = @$_POST['attribute'];
				$attrs_finished = array();
				$count = Time_Activity_Rule::get_type_attributes_count($form_data['type']);
				
				for ($i = 0; $i < $count; $i++)
				{
					if (is_array($attrs) && count($attrs))
					{
						$attrs_finished[] = array_shift($attrs);
					}
					else
					{
						$attrs_finished[] = NULL;
					}
				}
				
				// save
				$baad->bank_account_id = $ba->id;
				$baad->type = $form_data['type'];
				$baad->attribute = implode('/', $attrs_finished);
				
				$baad->email_enabled = 
						Settings::get('email_enabled') && 
						$form_data['email_enabled'];
				
				$baad->sms_enabled = 
						Settings::get('sms_enabled') && 
						$form_data['sms_enabled'];
				
				$baad->save_throwable();
				
				// commit transaction
				$baad->transaction_commit();

				// message
				status::success('Rule for automatical download of statement has been succesfully added');
				
				// redirection
				$this->redirect('bank_accounts_auto_down_settings/show', $ba->id);	
			}
			catch (Exception $e)
			{
				// roolback transaction
				$baad->transaction_rollback();
				Log::add_exception($e);
				// message
				status::error('Error - cant add rule for automatical download of statement', $e);
			}
		}
		
		// headline
		$headline = __('Add a new rule for automatical download of statement');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('bank_accounts/show_all', 'Bank accounts',
						$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
				->disable_translation()
				->text($ba->account_nr . '/' . $ba->bank_nr)
				->text($headline)
				->html();

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->form = $form->html();
		$view->content->headline = $headline;
		$view->render(TRUE);
	}

	/**
	 * Deletes settings rule
	 *
	 * @param integer $baad_setting_id 
	 */
	public function delete($baad_setting_id = NULL)
	{
		// check param
		if (!$baad_setting_id || !is_numeric($baad_setting_id))
		{
			self::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_delete('Accounts_Controller', 'bank_account_auto_down_config'))
		{
			self::error(ACCESS);
		}

		// load model
		$baad = new Bank_accounts_automatical_download_Model($baad_setting_id);
		
		$bank_account_id = $baad->bank_account_id;
		
		// check exists
		if (!$baad->id)
		{
			Controller::error(RECORD);
		}
		
		// delete
		if ($baad->delete())
		{
			status::success('Rule for automatical download of statement has been succesfully deleted.');
		}
		else
		{
			status::error('Error - cant delete rule for automatical download of statement.');
		}

		// redirect to show all
		url::redirect('bank_accounts_auto_down_settings/show/' . $bank_account_id);
	}
	
	/**
	 * Checks if attribute form element has valid value
	 *
	 * @param object $input 
	 */
	public function valid_attribute($input = NULL)
	{
		if (empty($input) || !is_object($input))
		{
			Controller::error(PAGE);
		}
		
		$type = $this->input->post('type');
		$value = trim($input->value);
		
		$at = Bank_accounts_automatical_download_Model::get_type_attributes($type);
		$index = intval(substr($input->name, strlen('attribute[')));

		if (!$at)
		{
			$input->add_error('required', __('Wrong input.'));
		}
		else if (isset($at[$index]['type']) && ($at[$index]['type'] !== FALSE))
		{
			if ($at[$index]['type'] == 'integer')
			{
				if (!preg_match("/^[0-9]+$/", $value))
				{
					$input->add_error('required', __('Numeric value required'));
				}
				else
				{
					if (isset($at[$index]['range_from']) &&
						$at[$index]['range_from'] > intval($value))
					{
						$input->add_error('min_value', array($at[$index]['range_from']));
					}
					else if (isset($at[$index]['range_to']) &&
						$at[$index]['range_to'] < intval($value))
					{
						$input->add_error('max_value', array($at[$index]['range_to']));
					}
				}
			}
		}
	}
	
}
