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
 * Controller performs actions with ARO groups of users
 */
class Aro_groups_Controller extends Controller
{
	
	/**
	 * Index function, only redirects to list of ARO groups
	 */
	public function index()
	{
		url::redirect('aro_groups/show_all');
	}
	
	/**
	 * Shows access groups
	 */
	public function show_all()
	{
		// check access
		if (!$this->acl_check_view('Settings_Controller', 'access_rights'))
		{
			Controller::Error(ACCESS);
		}

		$rows = array();

		$aro_group_model = new Aro_group_Model();
		$groups = $aro_group_model->get_traverz_tree();

		$model_groups_aro_map = new Groups_Aro_Map_Model();

		// vykresleni skupin
		for ($i = 0; $i < $groups->count(); $i++)
		{
			$group = $groups->current();
			$ret = '';
			$rows[0] = '<tr><th colspan="5" style="width:300px">'
					 . __('Edit groups') . '</th></tr>';
			//vypocet posunuti podskupiny
			$parents_count = Aro_group_Model::count_parent($group->id);
			
			for ($j = 0; $j < $parents_count - 1; $j++)
			{
				$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
			}

			$count = $model_groups_aro_map->count_rows_by_group_id($group->id);

			if ($group->id == Aro_group_Model::ALL)
			{
				$rows[$i + 1] = '<tr><td style="width:400px">'
					. $ret . __('' . $group->name)
					. '</td><td style="width:30px; text-align: center" >'
					. $count . '</td><td>'
					. __('Show')
					.'</td><td>' . __('Edit')
					. '</td><td>' . __('Delete')
					. '</td></tr>';
			}
			else
			{
				$rows[$i + 1] = '<tr><td style="width:400px">'
					. $ret . $group->name
					. '</td><td style="width:30px; text-align: center" >'
					. $count . '</td><td>'
					. html::anchor('aro_groups/show/'.$group->id, __('Show'))
					.'</td><td>' . html::anchor(url_lang::base()
					. 'aro_groups/edit/' . $group->id, __('Edit'))
					. '</td><td>';
				
				if (!$aro_group_model->count_childrens($group->id))
				{
					$rows[$i + 1] .= html::anchor(url_lang::base()
						. 'aro_groups/delete/' . $group->id, __('Delete'),
						array('class' => 'delete_link'));
				}
				else
					$rows[$i + 1] .= __('Delete');
				
				$rows[$i + 1] .= '</td></tr>';
			}
			
			$groups->next();
		}
		
		$headline = __('Access control groups of users');

		$links[] = html::anchor('aro_groups/show_all', __('Groups of users'));
		$links[] = html::anchor('access_rights/show_acl', __('Access control list items'));
		
		$breadcrumbs = breadcrumbs::add()
				->text('Access control groups of users');
		
		$submenu = array();
		$submenu[] = html::anchor('acl/show_all', __('Access control rules'));
		$submenu[] = __('Access control groups of users');

		//vykresleni
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('access_rights/aro_groups_show_all');
		$view->content->submenu = implode(' | ',$submenu);
		$view->content->links = implode(' | ', $links);
		$view->content->rows = $rows;
		$view->content->headline = $headline;
		$view->render(TRUE);
	}
	
	/**
	 * Shows access control group 
	 * 
	 * @author Michal Kliment
	 * @param integer $group_id 
	 */
	public function show ($group_id = NULL)
	{
		// access check
		if (!$this->acl_check_edit('Settings_Controller', 'access_rights'))
			Controller::Error(ACCESS);
		
		// bad parameter
		if (!$group_id || !is_numeric($group_id))
			Controller::warning(PARAMETER);
		
		$aro_group = new Aro_group_Model($group_id);
		
		// record doesn't exist
		if (!$aro_group->id)
			Controller::error(RECORD);
		
		/**			AROs			**/
		$aros = $aro_group->get_aros();
		
		// grid
		$aro_grid = new Grid(url_lang::base().'aro', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> count($aros)
		));
		
		$aro_grid->field('id')
				->label(__('ID'));
		
		$aro_grid->link_field('id')
				->link('users/show', 'user_name')
				->label(__('User'));
		
		$aro_grid->datasource($aros);
		
		/**			ACLs			**/
		$acls = $aro_group->get_acls();
		
		// grid
		$acl_grid = new Grid(url_lang::base().'acl', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> count($acls)
		));
		
		$acl_grid->field('id')
				->label(__('ID'));
		
		$acl_grid->callback_field('note')
				->label(__('Description'))
				->callback('callback::limited_text');
		
		$actions = $acl_grid->grouped_action_field();
		
		$actions->add_action('id')
				->icon_action('show')
				->url('acl/show');
		
		$actions->add_action('id')
				->icon_action('edit')
				->url('acl/edit');
		
		$actions->add_action('id')
				->icon_action('delete')
				->url('acl/delete')
				->class('delete_link');
		
		$acl_grid->datasource($acls);
		
		$headline = __('Show access control group of users');
		
		$breadcrumbs = breadcrumbs::add()
				->link('aro_groups/show_all', 'Access control groups of users',
						$this->acl_check_edit('Settings_Controller', 'access_rights'))
				->disable_translation()
				->text($aro_group->name.' ('.$aro_group->id.')');
		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('access_rights/aro_groups_show');
		$view->content->aro_group = $aro_group;
		$view->content->parent = $aro_group->get_parent();
		$view->content->aro_grid = $aro_grid;
		$view->content->acl_grid = $acl_grid;
		$view->render(TRUE);
	}
	
	/**
	 * Adds new access control group
	 * 
	 * @author Michal Kliment
	 */
	public function add()
	{
		// check access
		if (!$this->acl_check_edit('Settings_Controller', 'access_rights'))
			Controller::Error(ACCESS);
		
		$aro_group_model = new Aro_group_Model();
		$aro_groups = $aro_group_model->get_traverz_tree();
		
		$arr_aro_groups = array();

		foreach ($aro_groups as $aro_group)
		{
			$ret = '';
			$parents_count = Aro_group_Model::count_parent($aro_group->id);
			for($j = 0; $j < $parents_count - 1; $j++ )
				$ret .= '&nbsp;&nbsp;&nbsp;';

			$arr_aro_groups[$aro_group->id] = $ret.__(''.$aro_group->name);
		}
		
		// form
		$form = new Forge(url::base(TRUE) . url::current(TRUE));
		
		$form->input('name')
				->rules('required')
				->style('width:600px');
		
		$form->dropdown('parent_id')
				->label('Parent')
				->options($arr_aro_groups)
				->rules('required')
				->style('width:600px');
	
		$form->submit('Add');
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$aro_group = new Aro_group_Model($form_data['parent_id']);
			
			try
			{
				$aro_group->transaction_start();
				
				if ($aro_group->id)
				{
					$rgt = $aro_group->rgt;
				
					$aro_group->increase($rgt);
				
					$aro_group->clear();
					$aro_group->parent_id = $form_data['parent_id'];
					$aro_group->lft = $rgt;
					$aro_group->rgt = $rgt+1;
					$aro_group->name = $form_data['name'];
					$aro_group->value = url::title($form_data['name'], '_');
				
					$aro_group->save_throwable();
					$aro_group->transaction_commit();
					status::success('Group has been successfully added.');
				}
			}
			catch (Exception $e)
			{
				$aro_group->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cannot add new group.');
			}
			url::redirect('aro_groups/show_all');
		}
		
		$headline = __('Add new group');
		
		$breadcrumbs = breadcrumbs::add()
				->link('aro_groups/show_all', 'Access control groups of users',
						$this->acl_check_view('Settings_Controller', 'access_rights'))
				->text($headline);
		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form;
		$view->render(TRUE);
	}
	
	/**
	 * Edits ARO group
	 * 
	 * @author Michal Kliment
	 * @param integer $group_id 
	 */
	public function edit ($group_id = NULL)
	{
		// access check
		if (!$this->acl_check_edit('Settings_Controller', 'access_rights'))
			Controller::Error(ACCESS);
		
		// bad parameter
		if (!$group_id || !is_numeric($group_id))
			Controller::warning(PARAMETER);
		
		$aro_group = new Aro_group_Model($group_id);
		
		// record doesn't exist
		if (!$aro_group->id)
			Controller::error(RECORD);
		
		$form = new Forge(url::base(TRUE).url::current(TRUE));
		
		$form->input('name')
				->rules('required')
				->value($aro_group->name)
				->style('width:600px');
		
		$user_model = new User_Model();
		$users = $user_model->select_list_grouped(FALSE);
		
		$sel_aros = array();
		foreach ($aro_group->get_aros() as $aro)
			$sel_aros[] = $aro->id;
		
		$form->dropdown('aro[]')
				->label('User')
				->options($users)
				->selected($sel_aros)
				->multiple('multiple')
				->size(20);
		
		$form->submit('submit')
				->value(__('Update'));
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$aro = (isset($_POST["aro"])) ? $_POST["aro"] : array();
			
			try
			{
				$aro_group->transaction_start();
				
				// update name of group
				$aro_group->name = $form_data['name'];	
				$aro_group->save_throwable();
				
				// cleans group - remove all old AROs
				$aro_group->clean_group();
				
				// inserts new AROs
				$aro_group->insert_aro($aro);
				
				$aro_group->transaction_commit();
				status::success('Access control group of users has been successfully updated.');
			}
			catch (Exception $e)
			{
				$aro_group->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cannot update access control group.');
			}
			url::redirect (url_lang::base().'aro_groups/show/'.$aro_group->id);
		}
		
		$headline = __('Edit access control group of users');
		
		$breadcrumbs = breadcrumbs::add()
				->link('aro_groups/show_all', 'Access control groups of users',
						$this->acl_check_edit('Settings_Controller', 'access_rights'))
				->disable_translation()
				->link('aro_groups/show/'.$aro_group->id, $aro_group->name.' ('.$aro_group->id.')',
						$this->acl_check_edit('Settings_Controller', 'access_rights'))
				->enable_translation()
				->text('Edit');
		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form;
		$view->render(TRUE);
	}
	
	/**
	 * Deletes group
	 * 
	 * @author Michal Kliment
	 * @param integer $group_id 
	 */
	public function delete ($group_id = NULL)
	{
		// access check
		if (!$this->acl_check_edit('Settings_Controller', 'access_rights'))
			Controller::Error(ACCESS);
		
		// bad parameter
		if (!$group_id || !is_numeric($group_id))
			Controller::warning (PARAMETER);
		
		$group = new Aro_group_Model($group_id);
		
		// record doesn't exist
		if (!$group->id)
			Controller::error(RECORD);
		
		// cannot delete group with some childrens
		if (!$group->is_deletable())
		{
			status::warning('Cannot delete group - this group is protected against deletion');
			url::redirect('aro_groups/show_all');
		}
		
		// cannot delete group with some childrens
		if ($group->count_childrens())
		{
			status::warning('Cannot delete group - it has at least one children group');
			url::redirect('aro_groups/show_all');
		}
		
		$group->transaction_start();
		
		try
		{
			$lft = $group->lft;
			$group->delete_throwable();
		
			$group->decrease($lft);
			
			$group->transaction_commit();
			status::success('Group has been successfully deleted.');
		}
		catch (Exception $e)
		{
			$group->transaction_rollback();
			Log::add_exception($e);
			status::error('Error - cannot delete group.');
		}
		url::redirect('aro_groups/show_all');
	}
}
