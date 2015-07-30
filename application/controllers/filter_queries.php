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
 * Controller performs actions over filter queries.
 *
 * @package Controller
 */
class Filter_queries_Controller extends Controller
{	
	/**
	 * Redirect to list of all filter queries 
	 */
	public function index()
	{
		url::redirect(url_lang::base().'filter_queries/show_all');
	}
	
	/**
	 * Show all filter queries
	 * @param type $limit_results
	 * @param type $order_by
	 * @param type $order_by_direction
	 * @param type $page_word
	 * @param type $page 
	 */
	public function show_all(
			$limit_results = 100, $order_by = 'id',
			$order_by_direction = 'ASC', $page_word = 'page', $page = 1)
	{
		$filter_query_model = new Filter_query_Model();
		
		$filter_queries = $filter_query_model->get_all_queries();
		
		$title = __('List of all filter queries');
		
		// path to form
		$path = Config::get('lang') . '/members/show_all/' . $limit_results . '/'
				. $order_by . '/' . $order_by_direction.'/'.$page_word.'/'
				. $page;
		
		$grid = new Grid('members', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 200,
			'selector_min' 				=> 200,
			'selector_max_multiplier'   => 25,
			'base_url'					=> $path,
			'uri_segment'				=> 'page',
			'total_items'				=> count($filter_queries),
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			//'filter'					=> $filter_form
		));
		
		$grid->order_field('id')
				->label(__('ID'));
		
		$grid->order_field('name');
		
		$grid->order_field('url')
				->label(__('URL'));
		
		$grid->order_callback_field('default')
				->callback(
					'callback::enabled_field',
					'filter_queries/set_default/'
				)->class('center');
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action('id')
				->icon_action('delete')
				->url('filter_queries/delete')
				->label('Delete')
				->class('delete_link');
		
		$grid->datasource($filter_queries);
		
		$view = new View('main');
		$view->breadcrumbs = __('Filter queries');
		$view->title = $title;
		$view->content = new View('show_all');
		$view->content->headline = $title;
		$view->content->table = $grid;
		$view->render(TRUE);
	}
	
	/**
	 * Adds new filter query
	 * 
	 * @author Michal Kliment 
	 */
	public function add()
	{
		// load data from GET
		$get = $_GET;
		
		// load URL from data
		$url = arr::remove("url", $get);
		
		// URL is missing
		if ($url == '' || !isset($get['values']))
			Controller::warning(PARAMETER);
		
		$form = new Forge();
		
		$form->input('name')
				->rules('required');
		
		$form->checkbox('default')
				->label('Set as default for this URL');
		
		$form->submit('Save');
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$data = array();
			$offset = 0;
			for ($i=0;$i<=max(array_keys($get["values"]));$i++)
			{
				if (isset($get["on"][$i]))
				{
					foreach ($get as $key => $value)
					{
						if (is_array($value) && isset($value[$i]))
							$data[$key][$i-$offset] = $value[$i];
					}
				}
				else
					$offset++;
			}
			
			$data['tables'] = $get['tables'];
			
			try
			{
				$filter_query = new Filter_query_Model();
				$filter_query->transaction_start();

				$filter_query->name = $form_data["name"];
				$filter_query->url = $url;
				$filter_query->values = json_encode($data);
				$filter_query->default = $form_data["default"];

				$filter_query->save_throwable();
				
				if ($filter_query->default)
					$filter_query->repair_default();
				
				$filter_query->transaction_commit();
				
				status::success('Filter query has been successfully added.');
				
				$this->redirect($url."?query", $filter_query->id, '=');
			}
			catch (Exception $e)
			{
				$filter_query->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cannot add new filter query.');
				
				$this->redirect($url);
			}
		}
		else
		{
			$title = __('Save new filter query');

			$view = new View('main');
			$view->title = $title;
			$view->content = new View('form');
			$view->content->headline = $title;
			$view->content->form = $form;
			$view->render(TRUE);
		}
	}
	
	/**
	 * Update default flag for query
	 * 
	 * @author Michal Kliment
	 * @param integer $filter_query_id 
	 */
	public function set_default($filter_query_id = NULL)
	{
		// bad paremeter
		if (!$filter_query_id || !is_numeric($filter_query_id))
			Controller::warning (PARAMETER);
		
		$filter_query = new Filter_query_Model($filter_query_id);
		
		// record doesn't exis
		if (!$filter_query->id)
			Controller::error(RECORD);
		
		$filter_query->transaction_start();
		
		$is_default = $filter_query->default;
		
		// prevent database exception
		try
		{
			$filter_query->default = !$is_default;
			$filter_query->save_throwable();
			
			if (!$is_default)
				$filter_query->repair_default();
			
			$filter_query->transaction_commit();
			
			if ($is_default)
				status::success('Filter query has been successfully unset as default.');
			else
				status::success('Filter query has been successfully set as default.');
		}
		catch (Exception $e)
		{
			$filter_query->transaction_rollback();
			Log::add_exception($e);
			if ($is_default)
				status::error('Error - Cannot unset filter query as default.');
			else
				status::error('Error - Cannot set filter query as default.');
		}
		
		url::redirect($this->url('show_all'));
	}
	
	/**
	 * Delete query
	 * 
	 * @author Michal Kliment
	 * @param type $filter_query_id 
	 */
	public function delete($filter_query_id = NULL)
	{
		// bad paremeter
		if (!$filter_query_id || !is_numeric($filter_query_id))
			Controller::warning (PARAMETER);
		
		$filter_query = new Filter_query_Model($filter_query_id);
		
		// record doesn't exis
		if (!$filter_query->id)
			Controller::error(RECORD);
		
		// prevent database exception
		try
		{
			$filter_query->delete_throwable();
			
			status::success('Filter query has been successfully deleted.');
		}
		catch (Exception $e)
		{
			status::error('Error - Cannot delete filter query.');
		}
		
		url::redirect($this->url('show_all'));
	}
}

?>
