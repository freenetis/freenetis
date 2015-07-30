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
 * Controller performs clouds actions.
 * Cloud is large district which contains subnets.
 * Clouds are mostly towns or town quarters.
 * 
 * @package Controller
 * @author Ondřej Fibich
 */
class Clouds_Controller extends Controller
{
	/**
	 * Constructor, only test if networks is enabled
	 */
	public function __construct()
	{
		parent::__construct();
		
		// access control
		if (!Settings::get('networks_enabled'))
			Controller::error (ACCESS);
	}
	
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('clouds/show_all');
	}

	/**
	 * Shows all clouds
	 */
	public function show_all()
	{
		// access check
		if (!$this->acl_check_view('Clouds_Controller', 'clouds'))
		{
			Controller::error(ACCESS);
		}

		// model
		$cloud_model = new Cloud_Model();

		// gets data
		$query = $cloud_model->get_all_clouds();

		// grid
		$grid = new Grid('clouds', null, array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false
		));

		if ($this->acl_check_new('Clouds_Controller', 'clouds'))
		{
			$grid->add_new_button('clouds/add', __('Add new cloud'));
		}

		$grid->field('id');
		
		$grid->field('name');
		
		$grid->field('subnet_count');
		
		$grid->field('admin_count');
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action()
				->icon_action('show')
				->url('clouds/show');
		
		if ($this->acl_check_edit('Clouds_Controller', 'clouds'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('clouds/edit');
		}
		
		if ($this->acl_check_delete('Clouds_Controller', 'clouds'))
		{			
			$actions->add_action()
					->icon_action('delete')
					->url('clouds/delete')
					->class('delete_link');
		}
		
		// load datasource
		$grid->datasource($query);
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->text('Clouds')
				->html();

		// main view
		$view = new View('main');
		$view->title = __('List of all clouds');
		$view->content = new View('show_all');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->headline = __('List of all clouds');
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Show subnet with given ID
	 *
	 * @param integer $cloud_id 
	 */
	public function show($cloud_id = NULL)
	{
		// param check
		if (!$cloud_id || !is_numeric($cloud_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check acess
		if (!$this->acl_check_view('Clouds_Controller', 'clouds'))
		{
			Controller::error(ACCESS);
		}

		$cloud_model = new Cloud_Model($cloud_id);
		
		// exist record
		if (!$cloud_model->id)
		{
			Controller::error(RECORD);
		}
		
		/* ADMINS */
		
		// post delete
		if ($_POST && isset($_POST['uid']) &&
			$this->acl_check_delete('Clouds_Controller', 'clouds'))
		{
			$ids = array_keys($_POST['uid']);
			
			if (count($ids) > 0)
			{
				$cloud_model->remove_admins($cloud_id, $ids);
				
				status::success('Admins of cloud have been successfully removed');
			}
		}

		// gets admins of cloud
		$admins = $cloud_model->get_cloud_admins();

		// grid
		$grid_admins = new Grid('clouds', null, array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false
		));
		
		if ($this->acl_check_new('Clouds_Controller', 'clouds'))
		{
			$grid_admins->add_new_button(
					'clouds/add_admin/' . $cloud_id,
					__('Add admin of cloud')
			);
		}

		if ($this->acl_check_delete('Clouds_Controller', 'clouds'))
		{
			$grid_admins->form_field('uid')
					->label('')
					->order(false)
					->type('checkbox');
		}
		
		$grid_admins->field('id');
		
		$grid_admins->field('user');
		
		$actions = $grid_admins->grouped_action_field();
		
		if ($this->acl_check_view('Users_Controller', 'users'))
		{
			$actions->add_action()
					->icon_action('show')
					->url('users/show');
		}
		
		if ($this->acl_check_delete('Clouds_Controller', 'clouds'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('clouds/remove_admin/' . $cloud_id)
					->label('Remove');
		}
		
		if ($this->acl_check_delete('Clouds_Controller', 'clouds'))
		{
			// adds extra buttons
			$grid_admins->form_extra_buttons = array
			(
				form::checkbox('admins_mark_all', 'on', FALSE, 'class="checkbox"') .
				form::label('mark_all', __('Mark all'), 'class="mark_all_label"')
			);
			$grid_admins->form_submit_value = __('Delete');
		}
		
		// load data
		$grid_admins->datasource($admins);
		
		/* SUBNETS */
		
		// post delete
		if ($_POST && isset($_POST['sid']) &&
			$this->acl_check_delete('Clouds_Controller', 'clouds'))
		{
			$ids = array_keys($_POST['sid']);
			
			if (count($ids) > 0)
			{
				$cloud_model->remove_subnets($cloud_id, $ids);
				
				status::success('Subnets have been successfully removed from cloud');
			}
		}

		// get cloud subnets
		$subnets = $cloud_model->get_cloud_subnets();

		// grid
		$grid_subnets = new Grid('clouds', null, array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false
		));

		if ($this->acl_check_new('Subnets_Controller', 'subnet'))
		{
			$grid_subnets->add_new_button(
					'subnets/add/' . $cloud_id, __('Add new subnet')
			);
		}
		
		if ($this->acl_check_new('Clouds_Controller', 'clouds'))
		{
			$grid_subnets->add_new_button(
					'clouds/add_subnet/' . $cloud_id, __('Add subnet to cloud')
			);
		}
		
		if ($this->acl_check_delete('Clouds_Controller', 'clouds'))
		{
			$grid_subnets->form_field('sid')
					->label('')
					->order(false)
					->type('checkbox');
		}
		
		$grid_subnets->field('id');
		
		$grid_subnets->field('name');
		
		$grid_subnets->field('network_address')
				->label(__('Address network'));
		
		$grid_subnets->field('netmask');
		
		$actions = $grid_subnets->grouped_action_field();
		
		if ($this->acl_check_view('Subnets_Controller', 'subnet'))
		{
			$actions->add_action()
					->icon_action('show')
					->url('subnets/show');
		}
		
		if ($this->acl_check_delete('Clouds_Controller', 'clouds'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('clouds/remove_subnet/' . $cloud_id)
					->label('Remove');
		}
		
		if ($this->acl_check_delete('Clouds_Controller', 'clouds'))
		{
			// adds extra buttons
			$grid_subnets->form_extra_buttons = array
			(
				form::checkbox('subnets_mark_all', 'on', FALSE, 'class="checkbox"') .
				form::label('mark_all', __('Mark all'), 'class="mark_all_label"')
			);
			$grid_subnets->form_submit_value = __('Delete');
		}

		// load data
		$grid_subnets->datasource($subnets);		
		
		$headline = $cloud_model->name . ' (' . $cloud_id . ')';
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('clouds/show_all', 'Clouds',
						$this->acl_check_view('Clouds_Controller', 'clouds'))
				->disable_translation()
				->text($headline)
				->html();

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->content = new View('clouds/show');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->cloud = $cloud_model;
		$view->content->admins = $grid_admins;
		$view->content->subnets = $grid_subnets;
		$view->render(TRUE);
	}

	/**
	 * Adds cloud
	 */
	public function add()
	{
		// check access
		if (!$this->acl_check_new('Clouds_Controller', 'clouds'))
		{
			Controller::error(ACCESS);
		}
		
		// gets all subnets
		$subnet_model = new Subnet_Model();
		$subnets = $subnet_model->select_list('id', 'name');
		
		// gets all users
		$user_model = new User_Model();
		$users = $user_model->select_list_grouped();

		// form
		$form = new Forge('clouds/add');

		$form->group('Basic information');
		
		$form->input('name')
				->rules('required')
				->style('width:600px');
		
		$form->dropdown('subnets[]')
				->label(__('Subnets') . ': ' . help::hint('multiple_dropdown'))
				->size(20)
				->multiple('multiple')
				->options($subnets)
				->class('max');
		
		$form->dropdown('admins[]')
				->label(__('Admins') . ': ' . help::hint('multiple_dropdown'))
				->size(20)
				->multiple('multiple')
				->options($users)
				->class('max');
		
		$form->submit('Add');

		// validate form and save data
		if ($form->validate())
		{			
			try
			{
				// cloud model
				$cloud_model = new Cloud_Model();
				// start transaction
				$cloud_model->transaction_start();
				
				// save cloud
				$cloud_model->name = htmlspecialchars($form->name->value);
				$issave = $cloud_model->save_throwable();
				
				// add admins
				if (isset($_POST['admins']) && is_array($_POST['admins']))
				{
					foreach ($_POST['admins'] as $user_id)
					{
						$user_model->find($user_id);
						
						if ($user_model->id)
						{
							$cloud_model->add($user_model);
							$cloud_model->save_throwable();
						}
					}
				}
				
				// add subnets
				if (isset($_POST['subnets']) && is_array($_POST['subnets']))
				{
					foreach ($_POST['subnets'] as $subnet_id)
					{						
						$subnet_model->find($subnet_id);
						
						if ($subnet_model->id)
						{
							$cloud_model->add($subnet_model);
							$cloud_model->save_throwable();
						}
					}
				}
				
				// commit transaction
				$cloud_model->transaction_commit();

				// message
				status::success('Cloud has been successfully added.');
				
				// redirection
				url::redirect('clouds/show_all');	
			}
			catch (Exception $e)
			{
				// roolback transaction
				$cloud_model->transaction_rollback();
				Log::add_exception($e);
				// message
				status::error('Error - cannot add cloud', $e);
			}
		}
		
		$headline = __('Add new cloud');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('clouds/show_all', 'Clouds',
						$this->acl_check_view('Clouds_Controller', 'clouds'))
				->disable_translation()
				->text($headline)
				->html();								

		// view
		$view = new View('main');
		$view->title = __('Add new cloud');
		$view->content = new View('form');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Assign admin as admin of cloud
	 *
	 * @param integer $cloud_id 
	 */
	public function add_admin($cloud_id = null)
	{
		// check param
		if (!$cloud_id || !is_numeric($cloud_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_new('Clouds_Controller', 'clouds'))
		{
			Controller::error(ACCESS);
		}

		// load model
		$cloud_model = new Cloud_Model($cloud_id);
		
		// check exists
		if (!$cloud_model->id)
		{
			Controller::error(RECORD);
		}

		// list of free
		$arr_admin = $cloud_model->select_list_of_admins_not_in($cloud_id);

		//are there any unassigned amins?
		if (count($arr_admin) == 1)
		{
			status::warning('There are not any unassigned admins.');
			url::redirect('clouds/show/' . $cloud_id);
		}

		// form
		$form = new Forge('clouds/add_admin/' . $cloud_id);
		
		$form->dropdown('admins[]')
				->label(__('Admins') . ': ' . help::hint('multiple_dropdown'))
				->size(20)
				->multiple('multiple')
				->options($arr_admin)
				->class('max');
		
		$form->submit('Assign');

		// validate form and save data
		if ($form->validate())
		{
			try
			{
				// start transaction
				$cloud_model->transaction_start();
				
				$user_model = new User_Model();
				
				// add admins
				if (isset($_POST['admins']) && is_array($_POST['admins']))
				{
					foreach ($_POST['admins'] as $user_id)
					{
						$user_model->find($user_id);
						
						if ($user_model->id)
						{
							$cloud_model->add($user_model);
							$cloud_model->save_throwable();
						}
					}
				}
				
				// commit transaction
				$cloud_model->transaction_commit();

				// message
				status::success('Admins have been successfully assigned to cloud');
				
				// redirection
				url::redirect('clouds/show/' . $cloud_id);	
			}
			catch (Exception $e)
			{
				// roolback transaction
				$cloud_model->transaction_rollback();
				Log::add_exception($e);
				// message
				status::error('Error - cannot add admin to cloud', $e);
			}
		}
		
		$headline = __('Assign admin to cloud');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('clouds/show_all', 'Clouds',
						$this->acl_check_view('Clouds_Controller', 'clouds'))
				->disable_translation()
				->link('clouds/show/' . $cloud_id,
						$cloud_model->name . ' (' . $cloud_id . ')',
						$this->acl_check_view('Clouds_Controller', 'clouds'))
				->text($headline)
				->html();

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Adds subnet to cloud
	 *
	 * @param integer $cloud_id 
	 */
	public function add_subnet($cloud_id = null)
	{
		// check param
		if (!$cloud_id || !is_numeric($cloud_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_new('Clouds_Controller', 'clouds'))
		{
			Controller::error(ACCESS);
		}

		// load model
		$cloud_model = new Cloud_Model($cloud_id);
		
		// check exists
		if (!$cloud_model->id)
		{
			Controller::error(RECORD);
		}

		// list of free
		$arr_subnet = $cloud_model->select_list_of_subnets_not_in($cloud_id);

		//are there any unassigned subnets?
		if (count($arr_subnet) == 1)
		{
			status::warning('There are not any unassigned subnets.');
			url::redirect('clouds/show/' . $cloud_id);
		}

		// form
		$form = new Forge('clouds/add_subnet/' . $cloud_id);
		
		$form->dropdown('subnets[]')
				->label(__('Subnets') . ': ' . help::hint('multiple_dropdown'))
				->size(20)
				->multiple('multiple')
				->options($arr_subnet)
				->class('max');
		
		$form->submit('Insert');

		// validate form and save data
		if ($form->validate())
		{
			try
			{
				// start transaction
				$cloud_model->transaction_start();
				$subnet_model = new Subnet_Model();
				
				// add subnets
				if (isset($_POST['subnets']) && is_array($_POST['subnets']))
				{
					foreach ($_POST['subnets'] as $subnet_id)
					{						
						$subnet_model->find($subnet_id);
						
						if ($subnet_model->id)
						{
							$cloud_model->add($subnet_model);
							$cloud_model->save_throwable();
						}
					}
				}
				
				// commit transaction
				$cloud_model->transaction_commit();

				// message
				status::success('Subnets have been successfully assigned to cloud.');
				
				// redirection
				url::redirect('clouds/show/' . $cloud_id);
			}
			catch (Exception $e)
			{
				// roolback transaction
				$cloud_model->transaction_rollback();
				Log::add_exception($e);
				// message
				status::error('Error - cannot add subnet to cloud', $e);
			}
		}
		
		// bread crumbs
		$headline = __('Assign subnet to cloud');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('clouds/show_all', 'Clouds',
						$this->acl_check_view('Clouds_Controller', 'clouds'))
				->disable_translation()
				->link('clouds/show/' . $cloud_id,
						$cloud_model->name . ' (' . $cloud_id . ')',
						$this->acl_check_view('Clouds_Controller', 'clouds'))
				->text($headline)
				->html();

		// view
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('form');
		$view->content->headline = $headline . ' - ' . $cloud_model->name;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Edits cloud
	 *
	 * @param integer $cloud_id 
	 */
	public function edit($cloud_id = NULL)
	{
		// check param
		if (!$cloud_id || !is_numeric($cloud_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_edit('Clouds_Controller', 'clouds'))
		{
			Controller::error(ACCESS);
		}

		// load model
		$cloud_model = new Cloud_Model($cloud_id);
		
		// check exists
		if (!$cloud_model->id)
		{
			Controller::error(RECORD);
		}
		
		// form
		$form = new Forge('clouds/edit/' . $cloud_id);
		
		$form->input('name')
				->rules('required')
				->value($cloud_model->name)
				->style('width:600px');
		
		$form->submit('Edit');

		//validate form and save data
		if ($form->validate())
		{
			$cloud_model->name = $form->name->value;
			
			if ($cloud_model->save())
			{
				// message
				status::success('Cloud has been successfully updated.');
			}
			else
			{
				status::error('Error - cloud cannot be updated.');
			}
			// redirect
			url::redirect('clouds/show_all');
		}
		
		$headline = __('Edit cloud');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('clouds/show_all', 'Clouds',
						$this->acl_check_view('Clouds_Controller', 'clouds'))
				->disable_translation()
				->link('clouds/show/' . $cloud_id,
						$cloud_model->name . ' (' . $cloud_id . ')',
						$this->acl_check_view('Clouds_Controller', 'clouds'))
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
	 * Deletes cloud and its records in pivot tables 
	 *
	 * @param integer $cloud_id 
	 */
	public function delete($cloud_id = NULL)
	{
		// check param
		if (!$cloud_id || !is_numeric($cloud_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_delete('Clouds_Controller', 'clouds'))
		{
			Controller::error(ACCESS);
		}

		// load model
		$cloud_model = new Cloud_Model($cloud_id);
		
		// check exists
		if (!$cloud_model->id)
		{
			Controller::error(RECORD);
		}
		
		// delete (pivot tables are deleted by forein keys)
		if ($cloud_model->delete())
		{
			status::success('Cloud has been successfully deleted.');
		}
		else
		{
			status::error('Error - cant delete this cloud.');
		}

		// redirect to show all
		url::redirect('clouds/show_all/');
	}
	
	/**
	 * Removes admin of cloud
	 *
	 * @param integer $cloud_id 
	 * @param integer $user_id 
	 */
	public function remove_admin($cloud_id = NULL, $user_id = NULL)
	{
		// check param
		if (!$cloud_id || !is_numeric($cloud_id) ||
			!$user_id || !is_numeric($user_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_delete('Clouds_Controller', 'clouds'))
		{
			Controller::error(ACCESS);
		}

		// load models
		$cloud_model = new Cloud_Model($cloud_id);
		$user_model = new User_Model($user_id);
		
		// check exists
		if (!$cloud_model->id || !$user_model->id)
		{
			Controller::error(RECORD);
		}
		
		// remove record from pivot table
		$cloud_model->remove($user_model);
		
		// confirm removine
		if (!$cloud_model->save())
		{
			status::error('Error - cannot remove admin of cloud');
		}
		else
		{
			status::success('Admin of cloud has been successfully removed');
		}

		// redirect to show
		url::redirect('clouds/show/' . $cloud_id);
	}
	
	/**
	 * Removes subnet of cloud
	 *
	 * @param integer $cloud_id 
	 * @param integer $subnet_id 
	 */
	public function remove_subnet($cloud_id = NULL, $subnet_id = NULL)
	{
		// check param
		if (!$cloud_id || !is_numeric($cloud_id) ||
			!$subnet_id || !is_numeric($subnet_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_delete('Clouds_Controller', 'clouds'))
		{
			Controller::error(ACCESS);
		}

		// load models
		$cloud_model = new Cloud_Model($cloud_id);
		$subnet_model = new Subnet_Model($subnet_id);
		
		// check exists
		if (!$cloud_model->id || !$subnet_model->id)
		{
			Controller::error(RECORD);
		}
		
		// remove record from pivot table
		$cloud_model->remove($subnet_model);
		
		// confirm removine
		if (!$cloud_model->save())
		{
			status::error('Error - cannot remove subnet of cloud');
		}
		else
		{
			status::success('Subnet has been successfully removed from cloud');
		}

		// redirect to show
		url::redirect('clouds/show/' . $cloud_id);
	}

}
