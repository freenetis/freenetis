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
 * Controller performs actions over speed classes.
 *
 * @author Ondřej Fibich
 * @package Controller
 */
class Speed_classes_Controller extends Controller
{	
	/**
	 * Redirect to list of all speed classes
	 */
	public function index()
	{
		url::redirect('speed_classes/show_all');
	}
	
	/**
	 * Show all speedn classes
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param string $page 
	 */
	public function show_all()
	{
		// access
		if (!$this->acl_check_view('Speed_classes_Controller', 'speed_classes'))
			self::error(ACCESS);
		
		$speed_class_model = new Speed_class_Model();
		
		$speed_classes = $speed_class_model->get_all_speed_classes();
		
		$title = __('List of all speed classes');
		
		$grid = new Grid('speed_classes', null, array
		(
			'use_paginator'	   		=> false,
			'use_selector'	   		=> false,
		));
		
		if ($this->acl_check_new('Speed_classes_Controller', 'speed_classes'))
		{
			$grid->add_new_button(
					'speed_classes/add', 'Add new speed class',
					array('class' => 'popup_link')
			);
		}
		
		$grid->field('id')
				->label('ID');
		
		$grid->field('name');
		
		$grid->callback_field('ceil')
				->label('QoS ceil')
				->help(help::hint('qos_ceil'))
				->callback('callback::speed_class_ceil_field');
		
		$grid->callback_field('rate')
				->label('QoS rate')
				->help(help::hint('qos_rate'))
				->callback('callback::speed_class_rate_field');
		
		$grid->callback_field('members_count')
				->callback('callback::count_field', 'members_names')
				->class('center');
		
		$grid->callback_field('regular_member_default')
				->label('MD')
				->help(help::hint(__('Default for member')))
				->callback(
					'callback::enabled_field',
					$this->acl_check_edit('Speed_classes_Controller', 'speed_classes') ?
						'speed_classes/set_default/0/' : NULL
				)->class('center');
		
		$grid->callback_field('applicant_default')
				->label('AD')
				->help(help::hint(__('Default for membership applicant')))
				->callback(
					'callback::enabled_field',
					$this->acl_check_edit('Speed_classes_Controller', 'speed_classes') ?
						'speed_classes/set_default/1/' : NULL
				)->class('center');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_edit('Speed_classes_Controller', 'speed_classes'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('speed_classes/edit')
					->label('Delete')
					->class('popup_link');
		}
		
		if ($this->acl_check_delete('Speed_classes_Controller', 'speed_classes'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('speed_classes/delete')
					->label('Delete')
					->class('delete_link');
		}
		
		$grid->datasource($speed_classes);
		
		$view = new View('main');
		$view->breadcrumbs = __('Speed classes');
		$view->title = $title;
		$view->content = new View('show_all');
		$view->content->headline = $title;
		$view->content->table = $grid;
		$view->render(TRUE);
	}
	/**
	 * Shows speed class - hack for popup adding
	 * 
	 * @param integer $speed_class_id
	 */
	public function show($speed_class_id = NULL)
	{
		url::redirect('speed_classes/show_all');
	}
	
	/**
	 * Adds new speed class
	 */
	public function add()
	{
		// access
		if (!$this->acl_check_new('Speed_classes_Controller', 'speed_classes'))
			self::error(ACCESS);
		
		// form
		$form = new Forge();

		$form->group('Basic information');
		
		$form->input('name')
				->rules('required');
		
		$form->input('qos_ceil')
				->label('QoS ceil')
				->help(help::hint('qos_ceil'))
				->rules('required|valid_speed_size');
		
		$form->input('qos_rate')
				->label('QoS rate')
				->help(help::hint('qos_rate'))
				->rules('required|valid_speed_size');
		
		$form->submit('Add');
		
		// posted
		if($form->validate())
		{
			$form_data = $form->as_array();
			$speed_class = new Speed_class_Model();
			
			// parse ceil and rate
			$d_ceil = $u_ceil = $form_data['qos_ceil'];
			$d_rate = $u_rate = $form_data['qos_rate'];

			if (strpos($form_data['qos_ceil'], '/'))
			{
				$u_ceil = substr($u_ceil, 0, strpos($u_ceil, '/'));
				$d_ceil = substr($d_ceil, strpos($d_ceil, '/') + 1);
			}

			if (strpos($form_data['qos_rate'], '/'))
			{
				$u_rate = substr($u_rate, 0, strpos($u_rate, '/'));
				$d_rate = substr($d_rate, strpos($d_rate, '/') + 1);
			}
			
			try
			{
				$speed_class->transaction_start();
				
				$speed_class->name = $form_data['name'];
				$speed_class->d_ceil = network::str2bytes($d_ceil);
				$speed_class->u_ceil = network::str2bytes($u_ceil);
				$speed_class->d_rate = network::str2bytes($d_rate);
				$speed_class->u_rate = network::str2bytes($u_rate);
				$speed_class->save_throwable();
				
				$speed_class->transaction_commit();
				status::success('Speed class has been successfully added.');
				$this->redirect('speed_classes/show', $speed_class->id);
			}
			catch (Exception $e)
			{
				$speed_class->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot add speed class.', $e);
				$this->redirect('speed_classes/show_all');
			}
		}
		else
		{
			$headline = __('Add new speed class');

			// breadcrumbs navigation			
			$breadcrumbs = breadcrumbs::add()
					->link('speed_classes/show_all', 'Speed classes',
							$this->acl_check_view('Speed_classes_Controller', 'speed_classes'))
					->disable_translation()
					->text($headline);

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form->html();
			$view->content->link_back = '';
			$view->render(TRUE);
		}
	}
	
	/**
	 * Edits new speed class
	 * 
	 * @param integer $speed_class_id
	 */
	public function edit($speed_class_id = NULL)
	{
		// access
		if (!$this->acl_check_edit('Speed_classes_Controller', 'speed_classes'))
			self::error(ACCESS);
		
		// bad paremeter
		if (!$speed_class_id || !is_numeric($speed_class_id))
			Controller::warning (PARAMETER);
		
		$sc = new Speed_class_Model($speed_class_id);
		
		// record doesn't exis
		if (!$sc->id)
			Controller::error(RECORD);
		
		$ceil = network::speed($sc->d_ceil);
		$rate = network::speed($sc->d_rate);
		
		if ($sc->d_ceil != $sc->u_ceil)
		{
			$ceil = network::speed($sc->u_ceil) . '/' . network::speed($sc->d_ceil);
		}
		
		if ($sc->d_rate != $sc->u_rate)
		{
			$rate = network::speed($sc->u_rate) . '/' . network::speed($sc->d_rate);
		}
		
		// form
		$form = new Forge();

		$form->group('Basic information');
		
		$form->input('name')
				->rules('required')
				->value($sc->name);
		
		$form->input('qos_ceil')
				->label('QoS ceil')
				->help(help::hint('qos_ceil'))
				->rules('required|valid_speed_size')
				->value($ceil);
		
		$form->input('qos_rate')
				->label('QoS rate')
				->help(help::hint('qos_rate'))
				->rules('required|valid_speed_size')
				->value($rate);
		
		$form->submit('Update');
		
		// posted
		if($form->validate())
		{
			$form_data = $form->as_array();
			
			// parse ceil and rate
			$d_ceil = $u_ceil = $form_data['qos_ceil'];
			$d_rate = $u_rate = $form_data['qos_rate'];

			if (strpos($form_data['qos_ceil'], '/'))
			{
				$u_ceil = substr($u_ceil, 0, strpos($u_ceil, '/'));
				$d_ceil = substr($d_ceil, strpos($d_ceil, '/') + 1);
			}

			if (strpos($form_data['qos_rate'], '/'))
			{
				$u_rate = substr($u_rate, 0, strpos($u_rate, '/'));
				$d_rate = substr($d_rate, strpos($d_rate, '/') + 1);
			}
			
			try
			{
				$sc->transaction_start();
				
				$sc->name = $form_data['name'];
				$sc->d_ceil = network::str2bytes($d_ceil);
				$sc->u_ceil = network::str2bytes($u_ceil);
				$sc->d_rate = network::str2bytes($d_rate);
				$sc->u_rate = network::str2bytes($u_rate);
				$sc->save_throwable();
				
				$sc->transaction_commit();
				status::success('Speed class has been successfully updated.');
				$this->redirect('speed_classes/show', $sc->id);
			}
			catch (Exception $e)
			{
				$sc->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot update speed class.', $e);
				$this->redirect('speed_classes/show_all');
			}
		}
		else
		{
			$headline = __('Edit speed class');

			// breadcrumbs navigation			
			$breadcrumbs = breadcrumbs::add()
					->link('speed_classes/show_all', 'Speed classes',
							$this->acl_check_view('Speed_classes_Controller', 'speed_classes'))
					->disable_translation()
					->text($sc->name)
					->text($headline);

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form->html();
			$view->content->link_back = '';
			$view->render(TRUE);
		}
	}
	
	/**
	 * Update default regular member and applicant flag for speed class
	 * 
	 * @param boolean $is_applicant TRUE on applicant FALSE on member
	 * @param integer $speed_class_id 
	 */
	public function set_default($is_applicant = FALSE, $speed_class_id = NULL)
	{
		// access
		if (!$this->acl_check_edit('Speed_classes_Controller', 'speed_classes'))
			self::error(ACCESS);
		
		// bad paremeter
		if (!$speed_class_id || !is_numeric($speed_class_id))
			Controller::warning (PARAMETER);
		
		$speed_class = new Speed_class_Model($speed_class_id);
		
		// record doesn't exis
		if (!$speed_class->id)
			Controller::error(RECORD);
		
		if ($is_applicant)
		{
			$is_default = $speed_class->applicant_default;
		}
		else
		{
			$is_default = $speed_class->regular_member_default;
		}
		
		// prevent database exception
		try
		{
			$speed_class->transaction_start();
			
			if ($is_applicant)
			{
				$speed_class->applicant_default = !$is_default;
			}
			else
			{
				$speed_class->regular_member_default = !$is_default;
			}
			
			$speed_class->save_throwable();
			
			if (!$is_default)
			{
				if ($is_applicant)
				{
					$speed_class->repair_applicant_default();
				}
				else
				{
					$speed_class->repair_regular_member_default();
				}
			}
			
			$speed_class->transaction_commit();
			
			if ($is_default)
			{
				status::success('Speed class has been successfully unset as default.');
			}
			else
			{
				status::success('Speed class has been successfully set as default.');
			}
		}
		catch (Exception $e)
		{
			$speed_class->transaction_rollback();
			Log::add_exception($e);
			
			if ($is_default)
			{
				status::error('Error - Cannot unset speed class as default.', $e);
			}
			else
			{
				status::error('Error - Cannot set speed class as default.', $e);
			}
		}
		
		url::redirect('speed_classes/show_all');
	}
	
	/**
	 * Delete speed class
	 * 
	 * @param integer $speed_class_id 
	 */
	public function delete($speed_class_id = NULL)
	{
		// access
		if (!$this->acl_check_delete('Speed_classes_Controller', 'speed_classes'))
			Controller::error(ACCESS);
		
		// bad paremeter
		if (!$speed_class_id || !is_numeric($speed_class_id))
			Controller::warning (PARAMETER);
		
		$speed_class = new Speed_class_Model($speed_class_id);
		
		// record doesn't exis
		if (!$speed_class->id)
			Controller::error(RECORD);
		
		// prevent database exception
		try
		{
			$speed_class->delete_throwable();
			
			status::success('Speed class has been successfully deleted.');
		}
		catch (Exception $e)
		{
			Log::add_exception($e);
			status::error('Error - Cannot delete speed class.', $e);
		}
		
		url::redirect('speed_classes/show_all');
	}
}
