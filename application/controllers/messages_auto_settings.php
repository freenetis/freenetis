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
 * Controller manages automatical notification of the notification message.
 * 
 * @package Controller
 * @author Ondřej Fibich
 */
class Messages_auto_settings_Controller extends Controller
{
	
	/**
	 * Only enable if notification enabled
	 */
	public function __construct()
	{
		parent::__construct();
		
	    if (!module::e('notification'))
			self::error(ACCESS);
	}

	/**
	 * Shows all settings of the given message.
	 */
	public function show($message_id = NULL)
	{
		// check param
		if (!intval($message_id))
		{
			self::warning(PARAMETER);
		}
		
		$message = new Message_Model($message_id);
		$message_aa = new Messages_automatical_activation_Model();
		
		// check if exists
		if (!$message || !$message->id ||
			!Message_Model::can_be_activate_automatically($message->type))
		{
			self::error(RECORD);
		}
		
		// access check
		if (!$this->acl_check_view('Messages_Controller', 'auto_config'))
		{
			self::error(ACCESS);
		}

		// gets data
		$query = $message_aa->get_message_settings($message->id);

		// grid
		$grid = new Grid('messages_auto_settings', null, array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false
		));

		if ($this->acl_check_new('Messages_Controller', 'auto_config'))
		{
			$grid->add_new_button(
					'messages_auto_settings/add/' . $message->id,
					__('Add new rule'), array('class' => 'popup_link')
			);
		}

		$grid->field('id')
				->label('ID');
		
		$grid->callback_field('type')
				->callback('callback::message_auto_setting_type');
		
		$grid->callback_field('attribute')
				->callback('callback::message_auto_setting_attribute');
		
		if (Settings::get('redirection_enabled'))
		{
			$grid->callback_field('redirection_enabled')
					->callback('callback::boolean')
					->label('Redirection');
		}
		
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

		if (Settings::get('email_enabled'))
		{
			$grid->field('send_activation_to_email')
					->label('Report to');
		}
		
		$actions = $grid->grouped_action_field();

		if ($this->acl_check_edit('Messages_Controller', 'auto_config'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('messages_auto_settings/edit')
					->class('popup_link');
		}
		
		if ($this->acl_check_delete('Messages_Controller', 'auto_config'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('messages_auto_settings/delete')
					->class('delete_link');
		}
		
		// load datasource
		$grid->datasource($query);
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('messages/show_all', 'Messages',
						$this->acl_check_view('Messages_Controller', 'message'))
				->text($message->name)
				->text('Automatical activation settings')
				->html();

		// main view
		$view = new View('main');
		$view->title = __('Automatical activation settings');
		$view->content = new View('show_all');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->headline = __('Automatical activation settings');
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Adds a new rule
	 *
	 * @param integer $message_id 
	 */
	public function add($message_id = NULL)
	{
		// check param
		if (!$message_id || !is_numeric($message_id))
		{
			self::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_new('Messages_Controller', 'auto_config'))
		{
			self::error(ACCESS);
		}

		// load model
		$message = new Message_Model($message_id);
		$messages = Messages_automatical_activation_Model::get_type_messages();
		
		// check exists
		if (!$message->id ||
			!Message_Model::can_be_activate_automatically($message->type))
		{
			self::error(RECORD);
		}

		// form
		$form = new Forge('messages_auto_settings/add/' . $message_id);

		$form->group('Basic information');
		
		$form->dropdown('type')
				->rules('required')
				->options(array_map('strtolower', $messages))
				->style('width:200px');
		
		for ($i = 0; $i < Time_Activity_Rule::get_attribute_types_max_count(); $i++)
		{
			$form->input('attribute[' . $i . ']')
					->callback(array($this, 'valid_attribute'))
					->label(__('Attribute') . ' ' . ($i + 1));
		}
		
		if (Settings::get('redirection_enabled'))
		{
			$form->checkbox('redirection_enabled')
					->label('Redirection of devices enabled')
					->value('1')
					->checked(TRUE);
		}
		
		if (Settings::get('email_enabled'))
		{
			$form->checkbox('email_enabled')
					->label('Sending of e-mail messages enabled')
					->value('1');
		}
		
		if (Settings::get('sms_enabled'))
		{
			$form->checkbox('sms_enabled')
					->label('Sending of SMS messages enabled')
					->value('1');
		}

		if (Settings::get('email_enabled'))
		{
			$form->group('Activation report');

			$form->input('send_activation_to_email')
					->rules('valid_emails|length[0,255]')
					->label('Send report to email')
					->help(help::hint('messages_send_activation_to_email'));
		}
		
		$form->submit('Add');

		// validate form and save data
		if ($form->validate())
		{
			try
			{
				// model
				$message_asettings = new Messages_automatical_activation_Model();
				
				// start transaction
				$message_asettings->transaction_start();
				
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
				$message_asettings->message_id = $message->id;
				$message_asettings->type = $form_data['type'];
				$message_asettings->attribute = implode('/', $attrs_finished);
				
				$message_asettings->redirection_enabled =
						Settings::get('redirection_enabled') && 
						$form_data['redirection_enabled'];
				
				$message_asettings->email_enabled = 
						Settings::get('email_enabled') && 
						$form_data['email_enabled'];
				
				$message_asettings->sms_enabled = 
						Settings::get('sms_enabled') && 
						$form_data['sms_enabled'];

				if (Settings::get('email_enabled') &&
					!empty($form_data['send_activation_to_email']))
				{
					$message_asettings->send_activation_to_email =
							$form_data['send_activation_to_email'];
				}

				$message_asettings->save_throwable();
				
				// commit transaction
				$message_asettings->transaction_commit();

				// message
				status::success('Message automatical activation setting rule has been succesfully added');
				
				// redirection
				$this->redirect('messages_auto_settings/show', $message->id);	
			}
			catch (Exception $e)
			{
				// roolback transaction
				$message_asettings->transaction_rollback();
				Log::add_exception($e);
				// message
				status::error('Error - cant add message automatical activation settings rule', $e);
			}
		}
		
		// headline
		$headline = __('Add automatical activation rule');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('messages/show_all', 'Messages',
						$this->acl_check_view('Messages_Controller', 'message'))
				->text($message->name)
				->disable_translation()
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
	 * Edits a rule.
	 *
	 * @param integer $rule_id
	 */
	public function edit($rule_id = NULL)
	{
		// check param
		if (!$rule_id || !is_numeric($rule_id))
		{
			self::warning(PARAMETER);
		}

		// check access
		if (!$this->acl_check_edit('Messages_Controller', 'auto_config'))
		{
			self::error(ACCESS);
		}

		$message_asettings = new Messages_automatical_activation_Model($rule_id);

		// exists?
		if (!$message_asettings || !$message_asettings->id)
		{
			self::error(RECORD);
		}

		$message = new Message_Model($message_asettings->message_id);

		// form
		$form = new Forge('messages_auto_settings/edit/' . $rule_id);

		if (Settings::get('email_enabled'))
		{
			$form->group('Activation report');

			$form->input('send_activation_to_email')
					->rules('valid_emails|length[0,255]')
					->label('Send report to email')
					->help(help::hint('messages_send_activation_to_email'))
					->value($message_asettings->send_activation_to_email);
		}

		$form->submit('Edit');

		// validate form and save data
		if ($form->validate())
		{
			try
			{
				// start transaction
				$message_asettings->transaction_start();

				// load data
				$form_data = $form->as_array();

				if (Settings::get('email_enabled') &&
					!empty($form_data['send_activation_to_email']))
				{
					$message_asettings->send_activation_to_email =
							$form_data['send_activation_to_email'];
				}

				$message_asettings->save_throwable();

				// commit transaction
				$message_asettings->transaction_commit();

				// message
				status::success('Message automatical activation setting rule has been succesfully updated');

				// redirection
				$this->redirect('messages_auto_settings/show', $message_asettings->message_id);
			}
			catch (Exception $e)
			{
				// roolback transaction
				$message_asettings->transaction_rollback();
				Log::add_exception($e);
				// message
				status::error('Error - cant add message automatical activation settings rule', $e);
			}
		}

		// headline
		$headline = __('Edit automatical activation rule');

		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('messages/show_all', 'Messages',
						$this->acl_check_view('Messages_Controller', 'message'))
				->text($message->name)
				->disable_translation()
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
	 * @param integer $mesages_auto_settings_id 
	 */
	public function delete($mesages_auto_settings_id = NULL)
	{
		// check param
		if (!$mesages_auto_settings_id || !is_numeric($mesages_auto_settings_id))
		{
			self::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_delete('Messages_Controller', 'auto_config'))
		{
			self::error(ACCESS);
		}

		// load model
		$maa = new Messages_automatical_activation_Model($mesages_auto_settings_id);
		
		$message_id = $maa->message_id;
		
		// check exists
		if (!$maa->id)
		{
			Controller::error(RECORD);
		}
		
		// delete
		if ($maa->delete())
		{
			status::success('Message automatical activation setting rule has been succesfully deleted.');
		}
		else
		{
			status::error('Error - cant delete message automatical activation settings rule.');
		}

		// redirect to show all
		url::redirect('messages_auto_settings/show/' . $message_id);
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
		
		$at = Messages_automatical_activation_Model::get_type_attributes($type);
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
