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
 * Controller performs actions over towns.
 *
 * @package Controller
 */
class Towns_Controller extends Controller
{

	/**
	 * Town ID for callback function
	 *
	 * @var integer
	 */
	private $town_id = 0;

	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('towns/show_all');
	}

	/**
	 * Function shows list of all towns.
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'id', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// access control
		if (!$this->acl_check_view('Address_points_Controller', 'town'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		// parameters control
		$allowed_order_type = array('id', 'town', 'quarter', 'zip_code');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		$town_model = new Town_Model();
		$total_towns = $town_model->count_all();
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_towns)
			$sql_offset = 0;

		$query = $town_model->get_all_towns(
				$sql_offset, (int) $limit_results, $order_by, $order_by_direction
		);

		// it creates grid to view all address points
		$grid = new Grid('address_points', '', array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min'				=> 50,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang') . '/towns/show_all/'
										. $limit_results . '/' . $order_by . '/' . $order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_towns,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
		));

		if ($this->acl_check_new('Address_points_Controller', 'town'))
		{
			$grid->add_new_button('towns/add', __('Add new town'));
		}

		$grid->order_field('id')
				->label('ID');
		
		$grid->order_field('town')
				->label(__('Town'));
		
		$grid->order_field('quarter')
				->label(__('Quarter'));
		
		$grid->order_field('zip_code')
				->label(__('zip code'));
		
		$actions = $grid->grouped_action_field();

		if ($this->acl_check_view('Address_points_Controller', 'town'))
		{
			$actions->add_action()
					->icon_action('show')
					->url('towns/show');
		}
		
		if ($this->acl_check_edit('Address_points_Controller', 'town'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('towns/edit');
		}
		
		if ($this->acl_check_delete('Address_points_Controller', 'town'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('towns/delete')
					->class('delete_link');
		}

		$grid->datasource($query);

		$links = array();
		$links[] = html::anchor('address_points', __('Address points'));
		$links[] = __('Towns');
		$links[] = html::anchor('streets', __('Streets'));

		$view = new View('main');
		$view->breadcrumbs = __('Towns');
		$view->title = __('List of all towns');
		$view->content = new View('show_all');
		$view->content->submenu = implode(' | ', $links);
		$view->content->headline = __('List of all towns');
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function shows town.
	 * 
	 * @author Michal Kliment
	 * @param integer $town_id id of town to show
	 */
	public function show($town_id = NULL)
	{
		// no parameter
		if (!$town_id)
			Controller::warning(PARAMETER);

		$town = new Town_Model($town_id);

		// record doesn't exist
		if (!$town->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view('Address_points_Controller', 'town'))
			Controller::error(ACCESS);
		
		$grid_streets = new Grid('towns/show/' . $town_id, null, array
		(
			'separator'		   		=> '<br /><br />',
			'use_paginator'	   		=> false,
			'use_selector'	   		=> false,
		));
		
		$grid_streets->field('id');
		
		$grid_streets->field('street');
		
		$actions = $grid_streets->grouped_action_field();
		
		$actions->add_action()
				->icon_action('show')
				->url('streets/show');
		
		$actions->add_action()
				->icon_action('edit')
				->url('streets/edit');
		
		$actions->add_action()
				->icon_action('delete')
				->url('streets/delete')
				->class('delete_link');
		
		$grid_streets->datasource($town->streets);

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('towns/show_all', 'Towns',
						$this->acl_check_view('Address_points_Controller', 'town'))
				->disable_translation()
				->text($town->__toString());

		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = __('Town detail');
		$view->content = new View('towns/show');
		$view->content->town = $town;
		$view->content->grid_streets = $grid_streets;
		$view->content->count_address_points = $town->address_points->count();
		$view->render(TRUE);
	}

	/**
	 * Function adds new town.
	 * 
	 * @author Michal Kliment
	 */
	public function add()
	{
		// access control
		if (!$this->acl_check_new('Address_points_Controller', 'town'))
			Controller::error(ACCESS);

		// creates new form
		$form = new Forge();

		$form->input('town')
				->label(__('Town') . ':')
				->rules('required|length[1,50]')
				->callback(array($this, 'check_town'));
		
		$form->input('quarter')
				->label(__('Quarter') . ':')
				->rules('length[1,50]');
		
		$form->input('zip_code')
				->label(__('Zip code') . ':')
				->rules('required|length[5,5]|valid_numeric');

		$form->submit('Add');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$town = new Town_Model();
			$town->town = $form_data["town"];
			$town->quarter = $form_data["quarter"];
			$town->zip_code = $form_data["zip_code"];

			// success
			if ($town->save())
			{
				status::success('Town has been successfully added.');
			}
			else
			{
				$town = NULL;
			}
			$this->redirect('show_all', $town->id);
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('towns/show_all', 'Towns',
							$this->acl_check_view('Address_points_Controller', 'town'))
					->text('Add new town');


			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = __('Add new town');
			$view->town = isset($town) && $town->id ? $town : NULL;
			$view->content = new View('form');
			$view->content->headline = __('Add new town');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Function edits town.
	 * 
	 * @author Michal Kliment
	 * @param integer $town_id id of town to edit
	 */
	public function edit($town_id = NULL)
	{
		// no parameter
		if (!$town_id)
			Controller::warning(PARAMETER);

		$town = new Town_Model($town_id);

		// record doesn't exist
		if (!$town->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_edit('Address_points_Controller', 'town'))
			Controller::error(ACCESS);

		// saving for callback function
		$this->town_id = $town_id;

		// creates new form
		$form = new Forge('towns/edit/' . $town_id);

		$form->input('town')
				->label(__('Town') . ':')
				->rules('required|length[1,50]')
				->value($town->town)
				->callback(array($this, 'check_town'));
		
		$form->input('quarter')
				->label(__('Quarter') . ':')
				->rules('length[1,50]')
				->value($town->quarter);
		
		$form->input('zip_code')
				->label(__('Zip code') . ':')
				->rules('required|length[5,5]|valid_numeric')
				->value($town->zip_code);

		$form->submit('Edit');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$town = new Town_Model($town_id);
			$town->town = $form_data["town"];
			$town->quarter = $form_data["quarter"];
			$town->zip_code = $form_data["zip_code"];

			// success
			if ($town->save())
			{
				status::success('Town has been successfully updated.');
			}
			url::redirect('towns/show_all');
		}

		$name = $town->town;
		$name .= ( $town->quarter != '') ? ' - ' . $town->quarter : '';
		$name .= ', ' . $town->zip_code;
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('towns/show_all', 'Towns',
						$this->acl_check_view('Address_points_Controller', 'town'))
				->disable_translation()
				->link('towns/show/' . $town->id, $name,
						$this->acl_check_view('Address_points_Controller', 'town'))
				->enable_translation()
				->text('Edit');

		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = __('Edit town');
		$view->content = new View('form');
		$view->content->headline = __('Editing of town');
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Function deletes town.
	 * 
	 * @author Michal Kliment
	 * @param integer $town_id id of town to delete
	 */
	public function delete($town_id = NULL)
	{
		// no parameter
		if (!$town_id)
			Controller::warning(PARAMETER);

		$town = new Town_Model($town_id);

		// record doesn't exist
		if (!$town->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_delete('Address_points_Controller', 'town'))
			Controller::error(ACCESS);

		$address_points = $town->address_points;

		if (count($address_points) == 0)
		{
			if ($town->delete())
			{
				status::success('Town has been successfully deleted.');
			}
			else
			{
				status::error('Error - cant delete town.');
			}
		}
		else
		{
			status::warning('At least one address point uses this town.');
		}

		url::redirect('towns/show_all');
	}

	/**
	 * Function checks if town already exist.
	 * 
	 * @author Michal Kliment
	 * @param object $input
	 */
	public function check_town($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$town = $this->input->post('town');
		
		if (($quarter = $this->input->post('quarter')) == '')
			$quarter = NULL;
		
		$zip_code = $this->input->post('zip_code');

		$town_model = new Town_Model();
		
		$towns_count = $town_model->where(array
		(
			'town' => $town,
			'quarter' => $quarter,
			'zip_code' => $zip_code,
			'id <>' => $this->town_id
		))->count_all();

		if ($towns_count)
		{
			$input->add_error('required', __('Town already exists.'));
		}
	}

}
