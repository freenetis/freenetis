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
 * Controller performs actions with access control rules
 * 
 * @package Controller
 * @author Michal Kliment
 */
class Acl_Controller extends Controller
{
	
	/**
	 * Index function, only redirect to list of all access control rules
	 * 
	 * @author Michal Kliment
	 */
	public function index()
	{
		url::redirect('acl/show_all');
	}

	/**
	 * Shows all access control rules
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page 
	 */
	public function show_all (
			$limit_results = 100, $order_by = 'id',
			$order_by_direction = 'asc',
			$page_word = 'page', $page = 1)
	{
		// check access
		if (!$this->acl_check_view('Settings_Controller', 'access_rights'))
			Controller::Error(ACCESS);
		
		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'desription', 'aco_count', 'aro_groups_count','axo_count'
		);
		
		// order by check
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		$acl_model = new Acl_Model();
		
		$total_rules = $acl_model->count_all_rules();
		
		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_rules)
			$sql_offset = 0;
		
		$rules = $acl_model->get_all_rules(
				$sql_offset, (int)$limit_results, $order_by, $order_by_direction
		);
		
		$headline = __('List of all rules for access control');
		
		// path to form
		$path = Config::get('lang') . '/acl/show_all/' . $limit_results . '/'
				. $order_by . '/' . $order_by_direction.'/'.$page_word.'/'
				. $page;
		
		// it creates grid to view all members
		$grid = new Grid('acl', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 100,
			'selector_max_multiplier'   => 20,
			'base_url'					=> $path,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_rules,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			//'filter'					=> $filter_form
		));
		
		$grid->add_new_button('acl/add', __('Add new rule'));
		
		$grid->order_field('id')
				->label(__('ID'));
		
		$grid->order_callback_field('description')
				->callback('callback::limited_text');
		
		$grid->order_callback_field('aco_count')
				->label(__('ACO count').' '.help::hint('aco_count'))
				->callback('callback::aco_count_field')
				->class('center');
		
		$grid->order_callback_field('aro_groups_count')
				->label(__('ARO groups count').' '.help::hint('aro_groups_count'))
				->callback('callback::aro_groups_count_field')
				->class('center');
		
		$grid->order_callback_field('axo_count')
				->label(__('AXO count').' '.help::hint('axo_count'))
				->callback('callback::axo_count_field')
				->class('center');
		
		$actions = $grid->grouped_action_field();
		
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
		
		$grid->datasource($rules);
		
		$submenu = array();
		$submenu[] = __('Access control rules');
		$submenu[] = html::anchor('aro_groups/show_all', __('Access control groups of users'));
		
		$view = new View('main');
		$view->breadcrumbs = __('Access control rules');
		$view->title = $headline;
		$view->content = new View('show_all');
		$view->content->submenu = implode(' | ',$submenu);
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	}
	
	/**
	 * Shows access control rule
	 * 
	 * @author Michal Kliment
	 * @param integer $acl_id 
	 */
	public function show ($acl_id = NULL)
	{
		// check access
		if (!$this->acl_check_view('Settings_Controller', 'access_rights'))
			Controller::Error(ACCESS);
		
		// bad parameter
		if (!$acl_id || !is_numeric($acl_id))
			Controller::warning (PARAMETER);
		
		$acl = new Acl_Model($acl_id);
	
		// record doesn't exist
		if (!$acl->id)
			Controller::error(RECORD);
		
		/**			ACO			**/
		$acos = $acl->get_acos();
		
		// grid
		$aco_grid = new Grid(url_lang::base().'aco', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> count($acos)
		));
		
		$aco_grid->callback_field('value')
				->callback('callback::aco_value_field');
		
		$aco_grid->datasource($acos);
		
		/**			ARO groups	**/
		$aro_groups = $acl->get_aro_groups();
		
		// grid
		$aro_groups_grid = new Grid(url_lang::base().'aro_groups', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> count($aro_groups)
		));
		
		$aro_groups_grid->field('id')
				->label(__('ID'));
		
		$aro_groups_grid->field('name');
		
		$aro_groups_grid->datasource($aro_groups);
		
		/**			AXO			**/
		$axos = $acl->get_axos();
		
		// grid
		$axo_grid = new Grid(url_lang::base().'axo', null, array
		(
			'use_paginator'	   			=> false,
			'use_selector'	   			=> false,
			'total_items'				=> count($axos)
		));
		
		$axo_grid->field('id')
				->label(__('ID'));
		
		$axo_grid->field('section_value')
				->label('Section');
		
		$axo_grid->field('value');
		
		$axo_grid->field('name');
		
		$axo_grid->datasource($axos);
		
		$headline = __('Show access control rule');
		
		$breadcrumbs = breadcrumbs::add()
				->link('acl/show_all', 'Access control rules',
					$this->acl_check_view('Settings_Controller', 'access_rights'))
				->text('ID '.$acl->id);
		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('access_rights/acl_show');
		$view->content->acl = $acl;
		$view->content->aco_grid = $aco_grid;
		$view->content->aro_groups_grid = $aro_groups_grid;
		$view->content->axo_grid = $axo_grid;
		$view->render(TRUE);
	}
	
	/**
	 * Adds new access control rule
	 * 
	 * @author Michal Kliment
	 */
	public function add ()
	{
		// check access
		if (!$this->acl_check_edit('Settings_Controller', 'access_rights'))
			Controller::Error(ACCESS);
		
		$form = new Forge(url::base(TRUE).url::current(TRUE));
		
		$form->textarea('description')
				->rules('required')
				->style('width:600px');
		
		$form->dropdown('aco[]')
				->label(__('ACO').': '.help::hint('aco'))
				->rules('required')
				->options(Aco_Model::get_actions())
				->multiple('multiple')
				->size(20);
		
		$aro_group_model = new Aro_group_Model();
		$aro_groups = $aro_group_model->find_all();
		
		$arr_aro_groups = array();
		foreach ($aro_groups as $aro_group)
			$arr_aro_groups[$aro_group->id] = $aro_group->name;
		
		$form->dropdown('aro_group[]')
				->label(__('ARO groups').': '.help::hint('aro_groups'))
				->rules('required')
				->options($arr_aro_groups)
				->multiple('multiple')
				->size(20);
		
		$axo_model = new Axo_Model();
		$axos = $axo_model->find_all();
		
		$arr_axos = array();
		foreach ($axos as $axo)
			$arr_axos[$axo->id] = $axo->name.' ('.$axo->section_value.')';
		
		$form->dropdown('axo[]')
				->label(__('AXO').': '.help::hint('axo'))
				->rules('required')
				->options($arr_axos)
				->multiple('multiple')
				->size(20);
		
		$form->submit('submit')
				->value(__('Add'));
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$aco		= (isset($_POST["aco"])) ? $_POST["aco"] : array();
			$aro_groups	= (isset($_POST["aro_group"])) ? $_POST["aro_group"] : array();
			$axo		= (isset($_POST["axo"])) ? $_POST["axo"] : array();
						
			$axo_model = new Axo_Model();
			$axo = $axo_model->get_values_by_ids($axo);

			$acl = new Acl_Model();
			$acl->note = $form_data['description'];
			$acl->save();
			
			$acl->insert_aco($aco);
			$acl->insert_aro_groups($aro_groups);
			$acl->insert_axo($axo);
			
			status::success('Access control rule has been successfully added.');
			url::redirect('acl/show/'.$acl->id);
		}
		
		$headline = __('Add access control rule');
		
		$breadcrumbs = breadcrumbs::add()
				->link('acl/show_all', 'Access control rules',
					$this->acl_check_view('Settings_Controller', 'access_rights'))
				->text('Add new rule');
		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('form');
		$view->content->form = $form;
		$view->content->headline = $headline;
		$view->render(TRUE);
	}
	
	/**
	 * Edits access control rule
	 * 
	 * @author Michal Kliment
	 * @param integer $acl_id 
	 */
	public function edit ($acl_id = NULL)
	{
		// check access
		if (!$this->acl_check_edit('Settings_Controller', 'access_rights'))
			Controller::Error(ACCESS);
		
		// bad parameter
		if (!$acl_id || !is_numeric($acl_id))
			Controller::warning (PARAMETER);
		
		$acl = new Acl_Model($acl_id);
		
		// record doesn't exist
		if (!$acl->id)
			Controller::error(RECORD);
		
		$form = new Forge(url::base(TRUE).url::current(TRUE));
		
		$form->textarea('description')
				->value($acl->note)
				->rules('required')
				->style('width:600px');
		
		$sel_acos = array();
		foreach ($acl->get_acos() as $aco)
			$sel_acos[] = $aco->value;
		
		$form->dropdown('aco[]')
				->label(__('ACO').': '.help::hint('aco'))
				->rules('required')
				->options(Aco_Model::get_actions())
				->selected($sel_acos)
				->multiple('multiple')
				->size(20);
		
		$aro_group_model = new Aro_group_Model();
		$aro_groups = $aro_group_model->find_all();
		
		$arr_aro_groups = array();
		foreach ($aro_groups as $aro_group)
			$arr_aro_groups[$aro_group->id] = $aro_group->name;
		
		$sel_aro_groups = array();
		foreach ($acl->get_aro_groups() as $aro_group)
			$sel_aro_groups[] = $aro_group->id;
		
		$form->dropdown('aro_group[]')
				->label(__('ARO groups').': '.help::hint('aro_groups'))
				->rules('required')
				->options($arr_aro_groups)
				->selected($sel_aro_groups)
				->multiple('multiple')
				->size(20);
		
		$axo_model = new Axo_Model();
		$axos = $axo_model->find_all();
		
		$arr_axos = array();
		foreach ($axos as $axo)
			$arr_axos[$axo->id] = $axo->name.' ('.$axo->section_value.')';
		
		$sel_axos = array();
		foreach ($acl->get_axos() as $axo)
			$sel_axos[] = $axo->id;
		
		$form->dropdown('axo[]')
				->label(__('AXO').': '.help::hint('axo'))
				->rules('required')
				->options($arr_axos)
				->selected($sel_axos)
				->multiple('multiple')
				->size(20);
		
		$form->submit('submit')
				->value(__('Update'));
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$aco		= (isset($_POST["aco"])) ? $_POST["aco"] : array();
			$aro_groups	= (isset($_POST["aro_group"])) ? $_POST["aro_group"] : array();
			$axo		= (isset($_POST["axo"])) ? $_POST["axo"] : array();
			
			$axo_model = new Axo_Model();
			$axo = $axo_model->get_values_by_ids($axo);
			
			$acl->note = $form_data['description'];
			$acl->save();
			
			$acl->clean_rule();
			
			$acl->insert_aco($aco);
			$acl->insert_aro_groups($aro_groups);
			$acl->insert_axo($axo);
			
			status::success('Access control rule has been successfully updated.');
			url::redirect('acl/show/'.$acl->id);
		}
		
		$headline = __('Edit access control rule');
		
		$breadcrumbs = breadcrumbs::add()
				->link('acl/show_all', 'Access control rules',
					$this->acl_check_view('Settings_Controller', 'access_rights'))
				->link('acl/show/'.$acl->id, 'ID '.$acl->id,
					$this->acl_check_view('Settings_Controller', 'access_rights'))
				->text('Edit');
		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('form');
		$view->content->form = $form;
		$view->content->headline = $headline;
		$view->render(TRUE);
	}
	
	/**
	 * Deletes access control rule
	 * 
	 * @author Michal Kliment
	 * @param integer $acl_id 
	 */
	public function delete ($acl_id = NULL)
	{
		// check access
		if (!$this->acl_check_edit('Settings_Controller', 'access_rights'))
			Controller::Error(ACCESS);
		
		// bad parameter
		if (!$acl_id || !is_numeric($acl_id))
			Controller::warning(PARAMETER);
		
		$acl = new Acl_Model($acl_id);
		
		// record doesn't exist
		if (!$acl->id)
			Controller::error(RECORD);
		
		// clean ACL
		$acl->clean_rule();
		
		// successfully deleted
		if ($acl->delete())
			status::success('Access control rule has been successfully deleted.');
		else
			status::error('Error - cannot delete access rule.');
		
		url::redirect('acl/show_all');
	}
}

?>
