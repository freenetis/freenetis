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
 * Controller performs varible symbols actions such as viewing, editing, etc
 * 
 * @package Controller
 */
class Variable_Symbols_Controller extends Controller
{	
	/**
	 * Shows variable symbols of account
	 * 
	 * @author David Raška
	 * @param type $account_id
	 */
	public function show_by_account(
			$account_id = NULL)
	{
		if (empty($account_id) || !is_numeric($account_id))
		{
			Controller::error(RECORD);
		}
	
		$account = new Account_Model($account_id);
		$variable_symbol_model = new Variable_Symbol_Model();

		$view = new View('main');
		$view->title = __('Administration of variable symbols');

		// rights
		if (!$this->acl_check_view(
				'Variable_Symbols_Controller', 'variable_symbols',
				$account->member_id
			))
		{
			Controller::error(ACCESS);
		}
		$grid_variable_symbols = new Grid(url::current(true), null, array
		(
			'use_paginator' => false,
			'use_selector' => false
		));

		$grid_variable_symbols->field('id')
				->label(__('ID'));

		$grid_variable_symbols->field('variable_symbol')
				->label(__('Variable symbol'));

		$actions = $grid_variable_symbols->grouped_action_field();

		if ($this->acl_check_edit(
				'Variable_Symbols_Controller', 'variable_symbols',
				$account->member_id
			))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('variable_symbols/edit/'.$account_id)
					->class('popup_link');
		}

		if ($this->acl_check_delete(
				'Variable_Symbols_Controller', 'variable_symbols',
				$account->member_id
			))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('variable_symbols/delete/'.$account_id)
					->class('delete_link');
		}

		$grid_variable_symbols->datasource($variable_symbol_model->find_account_variable_symbols($account_id));

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller','members'))
				->disable_translation()
				->link('members/show/' . $account->member->id,
						"ID ".$account->member->id." - ".$account->member->name,
						$this->acl_check_view(
								'Members_Controller','members', $account->member_id
						))
				->enable_translation()
				->text('Variable symbols');

		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('variable_symbols/show_by_account');
		$view->content->grid_variable_symbols = $grid_variable_symbols;
		$view->content->account_id = $account_id;
		
		$view->content->can_add = $this->acl_check_new(
				'Variable_Symbols_Controller', 'variable_symbols', $account->member_id
		);
		$view->render(TRUE);
	} // end of show_by_account function
	
	/**
	 * Adds new variable symbol to account
	 * 
	 * @author David Raška
	 * @param type $account_id
	 */
	public function add(
			$account_id = NULL)
	{
		if (empty($account_id) || !is_numeric($account_id))
		{
			Controller::error(RECORD);
		}

		$account = new Account_Model($account_id);
		
		if (!$account || !$account->id)
		{
			Controller::error (RECORD);
		}

		$view = new View('main');
		$view->title = __('Administration of variable symbols');
		
		// rights
		if (!$this->acl_check_edit(
				'Variable_Symbols_Controller', 'variable_symbols',
				$account->member_id
			))
		{
			Controller::error(ACCESS);
		}

		$form = new Forge();

		$form->input('variable_symbol')
				->label(__('Variable symbol') . ':')
				->rules('required|length[1,10]')
				->callback(array($this,'valid_var_sym'));

		$form->submit('Add');
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			$variable_symbol_model = new Variable_Symbol_Model();
			if ($variable_symbol_model->get_variable_symbol_id($form_data['variable_symbol']))
			{
			    status::warning('Variable symbol already exists in database.');
			}
			else
			{
			    $variable_symbol_model->variable_symbol = $form_data['variable_symbol'];
			    $variable_symbol_model->account_id = $account_id;
			    $issaved = $variable_symbol_model->save();
			    if ($issaved)
			    {
				status::success('Variable symbol has been successfully added');
			    }
			    else
			    {
				status::error('Error - cant update variable symbols');
			    }
			}

			$this->redirect('show_by_account/', $account_id);
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller','members'))
				->disable_translation()
				->link('members/show/' . $account->member->id, 
						"ID ".$account->member->id." - ".$account->member->name,
						$this->acl_check_view(
								'Members_Controller','members', $account->member_id
						))
				->enable_translation()
				->link('variable_symbols/show_by_account/' . $account->id, 'Variable symbols',
						$this->acl_check_view(
								'Variable_Symbols_Controller','variable_symbols', $account->member_id
						))
				->text('Add');

			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Add variable symbol');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	} // end of add function
	
	/**
	 * Edits variable symbol of account
	 * 
	 * @author David Raška
	 * @param type $account_id
	 * @param type $id 
	 */
	public function edit(
			$account_id = NULL, $id = NULL)
	{
	    	    	if (empty($account_id) || !is_numeric($account_id))
		{
			Controller::error(RECORD);
		}

		$account = new Account_Model($account_id);

		if (!$account || !$account->id)
		{
		    Controller::error(RECORD);
		}
		
		$variable_symbol_model = new Variable_Symbol_Model($id);
		
		if (empty($id) || !is_numeric($id))
		{
		    Controller::warning(PARAMETER);
		}
		
		if (!$variable_symbol_model->id)
		{
		    Controller::error(RECORD);
		}

		$view = new View('main');
		$view->title = __('Administration of variable symbols');
		// rights
		if (!$this->acl_check_edit(
				'Variable_Symbols_Controller', 'variable_symbols',
				$account->member_id
			))
		{
			Controller::error(ACCESS);
		}

		$form = new Forge();
		
		$form->input('value')
				->label(__('Variable symbol') . ':')
				->rules('required|length[1,10]')
				->value($variable_symbol_model->variable_symbol)
				->callback(array($this,'valid_var_sym'));

		$form->submit('submit')
				->value(__('Update'));
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			if ($variable_symbol_model->variable_symbol != $form_data['value'] &&
				$variable_symbol_model->get_variable_symbol_id($form_data['value']))
			{
				status::warning('Variable symbol already exists in database.');
			}
			elseif ($variable_symbol_model->variable_symbol_used ($id))
			{
				status::warning('Cannot edit, there are other records depending on this one.');
			}
			else
			{			
				try
				{
				    $variable_symbol_model->transaction_start();
				    $variable_symbol_model->variable_symbol = $form_data['value'];
				    $variable_symbol_model->save_throwable();
				    $variable_symbol_model->transaction_commit();
				    status::success('Variable symbol has been successfully updated');
				}
				catch (Exception $e)
				{
				    $variable_symbol_model->transaction_rollback();
					Log::add_exception($e);
				    status::error('Error - cant update variable symbols');
				}
				
			}
			
			$this->redirect('show_by_account/', $account_id); 
		}
		else
		{
		    	// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller','members'))
				->disable_translation()
				->link('members/show/' . $account->member_id, 
						"ID ".$account->member_id." - ".$account->member->name,
						$this->acl_check_view(
								'Members_Controller','members', $account->member_id
						))
				->enable_translation()
				->link('variable_symbols/show_by_account/' . $account->id, 'Variable symbols',
						$this->acl_check_view(
								'Variable_Symbols_Controller','variable_symbols', $account->member_id
						)
				)->disable_translation()
				->text("ID ".$variable_symbol_model->id." - ".$variable_symbol_model->variable_symbol)
				->enable_translation()
				->text('Edit');

			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('variable_symbols/edit');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	} // end of edit function
	
	/**
	 * Deletes contact of user
	 * 
	 * @author David Raška
	 * @param type $account_id
	 * @param type $id
	 */
	public function delete(
			$account_id = NULL,$id = NULL)
	{
		if (empty($account_id) || !is_numeric($account_id))
		{
			Controller::error(RECORD);
		}

		$account = new Account_Model($account_id);
		
		if (!$account || !$account->id)
		{
		    Controller::error (RECORD);
		}
		
		// rights
		if (!$this->acl_check_delete(
				'Variable_Symbols_Controller', 'variable_symbols',
				$account->member_id
			))
		{
			Controller::error(ACCESS);
		}
		
		$variable_symbol_model = new Variable_Symbol_Model($id);
		
		if (empty($id) || !is_numeric($id))
		{
		    Controller::warning(PARAMETER);
		}
		
		if (!$variable_symbol_model->id)
		{
		    Controller::error(RECORD);
		}
		
		if (!$variable_symbol_model->variable_symbol_used($id))
		{
			$variable_symbol_model->remove($account);
			$variable_symbol_model->save();
			$variable_symbol_model->delete();
			status::success('Variable symbol has been deleted');
		}
		else
		{
			status::warning('Cannot delete, there are other records depending on this one.');
		}
		$this->redirect('show_by_account/', $account_id);
		
	} // end of delete function
	
		/**
	 * Check validity of variable symbol
	 *
	 * @param object $input 
	 */
	public function valid_var_sym($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$value = trim($input->value);
		
		$variable_symbol_model = new Variable_Symbol_Model();
		
		$total = $variable_symbol_model->get_variable_symbol_id($value);

		if (!preg_match("/^[0-9]{1,10}$/", $value))
		{
			$input->add_error('required', __('Bad variable symbol format.'));
		}
		else if ($total)
		{
			$input->add_error('required', __(
					'Variable symbol already exists in database.'
			));
		}
	}
}