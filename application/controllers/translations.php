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
 * Controller performs action over translations.
 * Translatin are used for dynamic translation of text stored in database.
 * 
 * @package Controller
 */
class Translations_Controller extends Controller
{

	/**
	 * Default function for translations.
	 * @return unknown_type
	 */
	public function index()
	{
		url::redirect('translations/show_all');
	}

	/**
	 * Shows all translations table.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 */
	public function show_all(
			$limit_results = 200, $order_by = 'id', $order_by_direction = 'asc',
			$page_word = null, $page = 1)
	{
		// check if logged user have access right to view all translations

		if (!$this->acl_check_view(get_class($this), 'translation'))
			Controller::Error(ACCESS);

		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');

		// get order of grid from parameters
		$allowed_order_type = array('id', 'original_term', 'translated_term', 'lang');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		// get data from database
		$model_translations = new Translation_Model();
		$total_translations = $model_translations->count_all();
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_translations)
			$sql_offset = 0;
		
		$all_translations = $model_translations->orderby($order_by, $order_by_direction)
				->limit($limit_results, $sql_offset)
				->find_all();

		// create grid
		$grid = new Grid('translations', null, array
		(
			'use_paginator'				=> true,
			'use_selector'				=> true,
			'current'					=> $limit_results,
			'selector_increace'			=> 200,
			'selector_min'				=> 200,
			'selector_max_multiplier'	=> 10,
			'base_url'					=> Config::get('lang') . '/translations/show_all/'
										. $limit_results . '/' . $order_by . '/' . $order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_translations,
			'items_per_page'			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results
		));

		// add button for new translation
		// check if logged user have access right to add new translation
		if ($this->acl_check_new(get_class($this), 'translation'))
		{
			$grid->add_new_button('translations/add', __('Add new translation'));
		}

		// set grid fields
		$grid->order_field('original_term');
		
		$grid->order_field('translated_term');
		
		$grid->order_field('lang')
				->label(__('Language'));
		
		$actions = $grid->grouped_action_field();

		// check if logged user have access right to edit this translation
		if ($this->acl_check_edit(get_class($this), 'translation'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('translations/edit');
		}

		// check if logged user have access right to delete this translation
		if ($this->acl_check_delete(get_class($this), 'translation'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('translations/delete')
					->class('delete_link');
		}

		// set grid datasource
		$grid->datasource($all_translations);

		// create view for this template
		$headline = __('Translations');
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $headline;
		$view->content = new View('show_all');
		$view->content->table = $grid;
		$view->content->headline = $headline;
		$view->render(TRUE);
	}

	/**
	 * Adds translation of new term.
	 */
	public function add()
	{
		// access control
		if (!$this->acl_check_new(get_class($this), 'translation'))
			Controller::error(ACCESS);

		// form for new translation
		$form = new Forge('translations/add');
		
		$form->input('original_term')
				->label(__('Original term') . ':')
				->rules('required|length[1,254]');
		
		$form->input('translated_term')
				->label(__('Translated term') . ':')
				->rules('required|length[1,254]');
		
		$form->input('lang')
				->label(__('Destination language') . ':')
				->rules('required|length[1,50]');
		
		$form->submit('Add');

		// test validity of input, if it is validate it will continue in show_all
		if ($form->validate())
		{
			$form_data = $form->as_array();
			// assigns new translation data to model
			$translation_data = new Translation_Model();
			$translation_data->original_term = $form_data['original_term'];
			$translation_data->translated_term = $form_data['translated_term'];
			$translation_data->lang = $form_data['lang'];
			// clears form content
			unset($form_data);
			
			// has translation been successfully saved?
			if ($translation_data->save())
			{
				status::success('Translation has been successfully added.');
				url::redirect('translations/show_all');
			}
			else
			{
				status::error('Error - can\'t add new translation.');
			}
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('translations/show_all', 'Translations')
					->text('Add new translation');
			
			// view for adding translation
			$view = new View('main');
			$view->title = __('Add new translation');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Add new translation');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Edits translation of existing term.
	 * 
	 * @param integer $id id of term
	 */
	public function edit($id = NULL)
	{
		// access control
		if (!$this->acl_check_edit(get_class($this), 'translation'))
			Controller::error(ACCESS);

		if (!$id || !is_numeric($id))
			Controller::warning(PARAMETER);
			
		// creates translation model with given id
		$translation = new Translation_Model($id);
		
		if (!$translation || !$translation->id)
			Controller::error(RECORD);
		
		// creates form for editing
		$form = new Forge('translations/edit/' . $id);

		// creates input fields and fills them with data
		$form->input('original_term')
				->label(__('Original term') . ':')
				->rules('required|length[1,254]')
				->value($translation->original_term);

		$form->input('translated_term')
				->label(__('Translated term') . ':')
				->rules('required|length[1,254]')
				->value($translation->translated_term);

		$form->input('lang')
				->label(__('Destination language') . ':')
				->rules('required|length[1,50]')
				->value($translation->lang);

		$form->submit('Update');

		// validates form
		if ($form->validate())
		{
			$form_data = $form->as_array();
			$translation->original_term = $form_data['original_term'];
			$translation->translated_term = $form_data['translated_term'];
			$translation->lang = $form_data['lang'];
			unset($form_data);

			if ($translation->save())
			{
				status::success('Translation has been successfully updated.');
				url::redirect('translations/show_all');
			}
		}


		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('translations/show_all', 'Translations')
				->disable_translation()
				->text($translation->original_term . ': ' .
						$translation->translated_term . ' (' .
						$translation->lang . ')')
				->enable_translation()
				->text('Edit translation');

		$view = new View('main');
		$view->title = __('Edit translation');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = __('Edit translation');
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Deletes term and its translation from database.
	 * 
	 * @param integer $id id of term
	 */
	public function delete($id = NULL)
	{
		// access control
		if (!$this->acl_check_delete(get_class($this), 'translation'))
			Controller::error(ACCESS);

		if (isset($id))
		{
			$translation_model = new Translation_Model();
			$translation_model->find($id, FALSE)->delete($id);
			
			if ($translation_model->save())
			{
				status::success('Translation has been successfully deleted');
				url::redirect('translations/show_all');
			}
			else
			{
				status::error('Error - can\'t delete translation');
			}
		}
		else
		{
			Controller::warning(PARAMETER);
		}
	}

}
