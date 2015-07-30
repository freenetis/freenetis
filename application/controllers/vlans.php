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
 * Controllers manages VLANs.
 *
 * @package Controller
 */
class Vlans_Controller extends Controller
{
	private $_vlan_id = NULL;
	
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
	 * Redirects to show all
	 */
	public function index()
	{
		url::redirect('vlans/show_all');
	}

	/**
	 * Shows all vlans
	 *
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page 
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'tag_802_1q', $order_by_direction = 'asc',
			$page_word = null, $page = 1)
	{

		if (!$this->acl_check_view('Vlans_Controller', 'vlan'))
			Controller::error(ACCESS);

		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');

		$vlan_model = new Vlan_Model();
		$total_vlans = $vlan_model->count_all_vlans();
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_vlans)
			$sql_offset = 0;
		
		$query = $vlan_model->get_all_vlans(
				$sql_offset, (int) $limit_results, $order_by,
				$order_by_direction
		);

		$grid = new Grid('vlans', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 10,
			'selector_min'				=> 10,
			'selector_max_multiplier'	=> 5,
			'base_url'					=> Config::get('lang') . '/vlans/show_all/'
										. $limit_results . '/' . $order_by
										. '/' . $order_by_direction,
			'uri_segment'					=> 'page',
			'total_items'				=> $total_vlans,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results
		));

		if ($this->acl_check_new('Vlans_Controller', 'vlan'))
		{
			$grid->add_new_button('vlans/add', 'Add new vlan');
		}
		
		$grid->order_field('id')
				->label('ID')
				->class('center');
		
		$grid->order_field('name')
				->label('Vlan name');
		
		$grid->order_field('tag_802_1q');
		
		$grid->order_callback_field('devices_count')
				->label('Devices count')
				->callback('callback::devices_field');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Vlans_Controller', 'vlan'))
		{
			$actions->add_action()
					->icon_action('show')
					->url('vlans/show');
		}
		
		if ($this->acl_check_edit('Vlans_Controller', 'vlan'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('vlans/edit');
		}
		
		if ($this->acl_check_delete('Vlans_Controller', 'vlan'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('vlans/delete')
					->class('delete_link');
		}
		
		$grid->datasource($query);


		$view = new View('main');
		$view->title = __('Vlans list');
		$view->breadcrumbs = __('Vlans');
		$view->content = new View('show_all');
		$view->content->table = $grid;
		$view->content->headline = __('Vlans list');
		$view->render(TRUE);
	}

	/**
	 * Shows VLAN
	 *
	 * @param integer $vlan_id 
	 */
	public function show($vlan_id = null)
	{
		if (!$vlan_id)
		{
			Controller::warning(PARAMETER);
		}

		$vlan = new Vlan_Model($vlan_id);

		if (!$vlan->id)
		{
			Controller::error(RECORD);
		}

		if (!$this->acl_check_view('Vlans_Controller', 'vlan'))
		{
			Controller::error(ACCESS);
		}
		
		$grid = new Grid('vlans', null, array
		(
			'separator'		=> '',
			'use_paginator'	=> false,
			'use_selector'	=> false
		));

		$grid->field('id')
				->label('ID')
				->class('center');
		
		$grid->field('name')
				->label('Device name');
		
		$grid->callback_field('ports_count')
				->label('Ports count')
				->callback('callback::ports_field');
		
		$grid->callback_field('ip_address')
				->label('IP address')
				->callback('callback::ip_address_field');
		
		$grid->datasource($vlan->get_devices_of_vlan());
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Devices_Controller','devices'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('devices/show');
		}
		
		if ($this->acl_check_edit('Devices_Controller','devices'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('devices/edit');
		}
			
		if ($this->acl_check_delete('Devices_Controller', 'devices'))
		{	
			$actions->add_action('id')
					->icon_action('delete')
					->url('devices/delete')
					->class('delete_link');
		}
		
		$breadcrumbs = breadcrumbs::add()
				->link('vlans/show_all', 'VLANs')
				->disable_translation()
				->text($vlan->name);

		$view = new View('main');
		$view->title = __('Vlan detail') . ' - ' . $vlan->name;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('vlans/show');
		$view->content->vlan = $vlan;
		$view->content->grid = $grid;
		$view->content->headline = __('Vlan detail') . ' - ' . $vlan->name;
		$view->render(TRUE);
	}

	/**
	 * Add VLAN
	 */
	public function add()
	{
		if (!$this->acl_check_new('Vlans_Controller', 'vlan'))
			Controller::error(ACCESS);

		$vlan = new Vlan_Model();

		$form = new Forge();

		$form->group('Basic data');

		$form->input('name')
				->rules('required|length[3,250]');
		
		$form->input('tag_802_1q')
				->rules('required|valid_digit')
				->class('increase_decrease_buttons')
				->callback(array($this, 'valid_tag_802_1q'));
		
		$form->textarea('comment')
				->rules('length[0,254]')
				->cols('20')
				->rows('5');

		$form->submit('Save');

		// validate form and save data
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$vlan = new Vlan_Model();
			
			try
			{
				$vlan->transaction_start();
				
				$vlan->name = $form_data['name'];
				$vlan->tag_802_1q = $form_data['tag_802_1q'];
				$vlan->comment = $form_data['comment'];

				$vlan->save_throwable();
				
				$vlan->transaction_commit();
				
				status::success('Vlan has been successfully saved.');
				
				$this->redirect('show', $vlan->id);
			}
			catch (Exception $e)
			{
				$vlan->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot add new VLAN.', $e);
				
				$this->redirect('show_all');
			}
		}
		else
		{
			// end validate-

			$breadcrumbs = breadcrumbs::add()
					->link('vlans/show_all', 'VLANs',
							$this->acl_check_view('Vlans_Controller', 'vlan'))
					->text('Add');

			$view = new View('main');
			$view->title = __('Add new vlan');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->vlan = isset($vlan) && $vlan->id ? $vlan : NULL;
			$view->content = new View('form');
			$view->content->form = $form->html();
			$view->content->headline = __('Add new vlan');
			$view->render(TRUE);
		}
	}

	/**
	 * Edits VLAN
	 *
	 * @param integer $vlan_id 
	 */
	public function edit($vlan_id = null)
	{
		if (!$vlan_id)
		{
			Controller::warning(PARAMETER);
		}
		
		$vlan = new Vlan_Model($vlan_id);

		if (!$vlan->id)
		{
			Controller::warning(RECORD);
		}

		if (!$this->acl_check_edit('Vlans_Controller', 'vlan'))
		{
			Controller::error(1);
		}
		
		$this->_vlan_id = $vlan_id;

		$form = new Forge();

		$form->group('Basic data');

		$form->input('name')
				->rules('required|length[3,250]')
				->value($vlan->name);
		
		$form->input('tag_802_1q')
				->rules('valid_digit')
				->value($vlan->tag_802_1q)
				->class('increase_decrease_buttons')
				->callback(array($this, 'valid_tag_802_1q'));
		
		$form->textarea('comment')
				->rules('length[0,254]')
				->value($vlan->comment)
				->cols('20')
				->rows('5');

		$form->submit('Update');

		// validate form and save data
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$vlan = new vlan_Model($vlan_id);
			
			try
			{
				$vlan->transaction_start();
				
				$vlan->name = $form_data['name'];
				$vlan->tag_802_1q = $form_data['tag_802_1q'];
				$vlan->comment = $form_data['comment'];

				$vlan->save_throwable();
				
				$vlan->transaction_commit();
				
				status::success('Vlan has been successfully updated.');
				
				$this->redirect('show', $vlan->id);
			}
			catch(Exception $e)
			{
				$vlan->transaction_rollback();
				
				status::error('Error - Cannot update VLAN.', $e);
				
				$this->redirect('show_all');
			}
		}
		// end validate
		else
		{
			$breadcrumbs = breadcrumbs::add()
					->link('vlans/show_all', 'VLANs',
							$this->acl_check_view('Vlans_Controller', 'vlan'))
					->disable_translation()
					->link('vlans/show/' . $vlan->id, $vlan->name,
							$this->acl_check_view('Vlans_Controller', 'vlan'))
					->enable_translation()
					->text('Edit');

			$view = new View('main');
			$view->title = __('Edit vlan') . ' - ' . $vlan->name;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->form = $form->html();
			$view->content->headline = __('Edit vlan') . ' - ' . $vlan->name;
			$view->render(TRUE);
		}
	}
	
	/**
	 * Deletes VLAN
	 * 
	 * @author Michal Kliment
	 * @param integer $vlan_id 
	 */
	public function delete ($vlan_id = NULL)
	{
		// bad parameter
		if (!$vlan_id || !is_numeric($vlan_id))
			Controller::warning(PARAMETER);
		
		$vlan = new Vlan_Model($vlan_id);
		
		// record doesn't exist
		if (!$vlan->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_delete('Vlans_Controller', 'vlan'))
			Controller::error(ACCESS);
		
		if ($vlan->delete())
			status::success ('Vlan has been successfully deleted.');
		else
			status::error ('Vlan hasn\'t been deleted');
			
		url::redirect('vlans/show_all');
	}
	
	/**
	 * Function removes vlan from port
	 * 
	 * @param integer $bridge_iface_id
	 * @param integer $iface_id
	 */
	public function remove_from_port($port_iface_id = null, $vlan_id = null)
	{
		// bad parameter
		if (!is_numeric($port_iface_id) || !is_numeric($vlan_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$iface = new Iface_Model($port_iface_id);
		$vlan = new Vlan_Model($vlan_id);
		
		// iface doesn't exist
		if (!$iface->id || !$vlan->id)
		{
			Controller::error(RECORD);
		}
		
		$iv = new Ifaces_vlan_Model();
		
		if ($iv->is_default_vlan_port($vlan_id, $port_iface_id) == 1)
		{
			status::warning('Cannot remove default VLAN');
		}
		else
		{
			//remove iface from bridge

			$delete_state = $iv->remove_vlan_from_port($port_iface_id, $vlan_id);

			if ($delete_state)
			{
				status::success('VLAN has been successfully removed.');
			}
			else
			{
				status::error('Error - cant remove VLAN.');
			}
		}
		
		$this->redirect(Path::instance()->previous());
	}
	
	/**
	 * Callback function to validate tag 802.1Q
	 * 
	 * @author Michal Kliment
	 * @param type $input 
	 */
	public function valid_tag_802_1q($input = NULL)
	{
		// bad parameter
		if (!is_object($input))
		{
			Controller::warning(PARAMETER);
		}
		
		$vlan_model = new Vlan_Model();
		
		if (!$this->_vlan_id)
		{
			$vlan = $vlan_model->where('tag_802_1q', $input->value)->find();
		}
		else
		{
			$vlan = $vlan_model->where(array
			(
				'tag_802_1q'	=> $input->value,
				'id <>'			=> $this->_vlan_id
			))->find();
		}
		
		if ($vlan->id && $input)
		{
			$input->add_error('required', __('There is already some VLAN with this tag.'));
		}
	}

}
