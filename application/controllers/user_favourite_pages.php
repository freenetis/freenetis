<?php

defined('SYSPATH') or die('No direct script access.');
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
 * Allows user to manage his favourite pages
 * 
 * @package Controller
 */
class User_favourite_pages_Controller extends Controller
{
	/**
	 * Function adds, edits or removes users faouvrite page
	 */
	public function toggle()
	{
		// get page title and address
		$title = @$_GET['title'];
		$page = @$_GET['page'];
		
		// stop if not set
		if (!$title || !$page)
		{
			self::warning(PARAMETER);
		}
		
		// create model
		$favourite = new User_favourite_pages_Model();
		
		// create new form
		$form = new Forge();
		$form->set_attr('class', 'form nopopup');
		
		$input = $form->input('name')
				->value(substr(htmlspecialchars_decode($title), 0, 50))
				->rules('required|length[1,50]');
		
		$checkbox = $form->checkbox('default')
				->label(__('Default page') . help::hint('default_page'));
		
		// edit or delete
		if ($favourite->is_users_favourite($this->user_id, $page))
		{
			$fav = $favourite->get_favourite_page_details($this->user_id, $page);
			
			$form->checkbox('remove')
				->label('Remove from favourites');
			
			$form->hidden('id')
					->value($fav->id);
			
			$input->value($fav->title);
			$checkbox->checked($fav->default_page);
			
			$form->submit('Edit favourites');
			
			$title = __('Edit favourites');
		}
		else	// add to favourites
		{
			$form->submit('Add to favourites');
			
			$title = __('Add to favourites');
		}
		
		// validate form
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$default = !empty($form_data['default']);
			$title = $form_data['name'];
			$remove = !empty($form_data['remove']);
			$id = @$form_data['id'];
			
			// remove or edit
			if ($favourite->is_users_favourite($this->user_id, $page))
			{
				if ($remove)	// remove from favourites
				{
					if ($favourite->remove_page_from_favourites($id))
					{
						status::success('Page has been removed from favourites.');
					}
					else
					{
						status::warning('Page has not been removed from favourites.');
					}
				}
				else			// edit favourites
				{
					if ($favourite->edit_favourites($this->user_id, $id, $title, $default))
					{
						status::success('Favourite page has been saved.');
					}
					else
					{
						status::warning('Favourite page has not been saved.');
					}
				}
			}
			else	// add to favourites
			{
				if ($favourite->add_page_to_favourite($this->user_id, $page, $title, $default))
				{
					status::success('Page has been added to favourites.');
				}
				else
				{
					status::warning('Page has not been added to favourites.');
				}
			}
			
			url::redirect($page);
		}

		// show form
		$view = new View('main');
		$view->title = $title;
		$view->content = new View('form');
		$view->content->headline = $title;
		$view->content->form = $form;
		$view->render(TRUE);
	}
	
	/**
	 * Function redirects to show_by_user function.
	 * 
	 * @return unknown_type
	 */
	public function index()
	{
		url::redirect('user_favourite_pages/show_all'.server::query_string());
	}

	/**
	 * Function shows users favourite pages.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 40, $order_by = 'id',
			$order_by_direction = 'asc',
			$page_word = 'page', $page = 1)
	{	
		$uf_model = new User_favourite_pages_Model();
		
		// gets new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		$total_fp = $uf_model->get_users_favourites($this->user_id)->count();
		
		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_fp)
			$sql_offset = 0;
		
		$favourites = $uf_model->get_users_favourites($this->user_id,
				$sql_offset, $limit_results, $order_by, $order_by_direction);
		
		// path to form
		$path = Config::get('lang') . '/user_favourite_pages/show_all/' . $limit_results . '/'
				. $order_by . '/' . $order_by_direction.'/'.$page_word.'/'
				. $page;
		
		$grid = new Grid('user_favourite_pages', NULL, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 40,
			'selector_min' 				=> 40,
			'total_items'				=> $favourites->count(),
			'base_url'					=> $path,
			'uri_segment'				=> 'page',
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'items_per_page' 			=> $limit_results,
			'limit_results'				=> $limit_results,
		));
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_field('title')
				->label('Name');
		
		$grid->order_field('page')
				->label('Path');
		
		$grid->order_callback_field('default_page')
				->label('Default page')
				->callback('callback::enabled_field', 'user_favourite_pages/set_default/')
				->class('center');
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action('id')
					->icon_action('edit')
					->url('user_favourite_pages/edit')
					->class('edit_link');
		
		$actions->add_action('id')
					->icon_action('delete')
					->url('user_favourite_pages/delete')
					->class('delete_link');
		
		$grid->datasource($favourites);
		
		$user = new User_Model($this->user_id);
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('users/show_all', 'Users',
					$this->acl_check_view('Users_Controller','users'))
				->disable_translation()
				->link('users/show/' . $user->id, 
						$user->name . ' ' . $user->surname . ' (' . $user->login . ')',
						$this->acl_check_view('Users_Controller', 'users', $user->member_id))
				->enable_translation()
				->text('Favourites');
		
		$title = __('Favourites');
		
		// show form
		$view = new View('main');
		$view->title = $title;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('show_all');
		$view->content->headline = $title;
		$view->content->table = $grid;
		
		$view->render(TRUE);
	}
	
	/**
	 * Sets favourite page as default
	 * 
	 * @param int $id	Favourite page ID
	 */
	public function set_default($id = NULL)
	{
		// stop if not set
		if (!$id)
		{
			self::warning(PARAMETER);
		}
		
		// create model
		$uf = new User_favourite_pages_Model($id);
		
		if ($uf->user_id != $this->user_id)
		{
			self::error(RECORD);
		}
		
		// remove default page
		$uf->remove_user_default_page($uf->user_id);
		
		// set default page
		$uf->set_user_default_page_by_id($id);
		
		$this->redirect('user_favourite_pages/show_all');
	}
	
	/**
	 * Delete user favourite page
	 * 
	 * @param int $id	Favourite page ID
	 */
	public function delete($id = NULL)
	{
		// stop if not set
		if (!$id)
		{
			self::warning(PARAMETER);
		}
		
		// create model
		$uf = new User_favourite_pages_Model($id);
		
		if ($uf->user_id != $this->user_id)
		{
			self::error(RECORD);
		}
		
		// remove faourite page
		$uf->remove_page_from_favourites($id);
		
		$this->redirect('user_favourite_pages/show_all');
	}
	
	/**
	 * Edit user favourite page
	 * 
	 * @param int $id	Favourite page ID
	 */
	public function edit($id = NULL)
	{
		// stop if not set
		if (!$id)
		{
			self::warning(PARAMETER);
		}
		
		// create model
		$uf = new User_favourite_pages_Model($id);
		
		if ($uf->user_id != $this->user_id)
		{
			self::error(RECORD);
		}
		
		$this->redirect('user_favourite_pages/toggle?title='.$uf->title.'&page='.$uf->page);
	}
}
