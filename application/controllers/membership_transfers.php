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
 * Controller performs actions with membership transfers
 * 
 * @package Controller
 */
class Membership_transfers_Controller extends Controller
{
	/**
	 * Index method only redirect to show all
	 * 
	 * @author Michal Kliment
	 */
	public function index()
	{
		$this->redirect('show_all');
	}
	
	/**
	 * Shows all membership transfers
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page
	 */
	public function show_all($limit_results = 50, $order_by = 'id',
			$order_by_direction = 'ASC', $page_word = null, $page = 1)
	{
		// access control
		if (!$this->acl_check_view('Membership_transfers_Controller', 'membership_transfer'))
			Controller::error (ACCESS);
		
		$filter_form = new Filter_form();
		
		$filter_form->add('from_member_name')
			->callback('json/member_name')
			->label('From member');
		
		$filter_form->add('to_member_name')
			->callback('json/member_name')
			->label('To member');
		
		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		$membership_transfer_model = new Membership_transfer_Model();
		
		// hide grid on its first load (#442)
		$hide_grid = Settings::get('grid_hide_on_first_load') && $filter_form->is_first_load();
		
		if (!$hide_grid)
		{
			try
			{
				$total_membership_transfers = $membership_transfer_model
					->count_all_membership_transfers($filter_form->as_sql());

				// limit check
				if (($sql_offset = ($page - 1) * $limit_results) > $total_membership_transfers)
					$sql_offset = 0;

				$membership_transfers = $membership_transfer_model->get_all_membership_transfers(
					$sql_offset, $limit_results, $order_by, $order_by_direction,
					$filter_form->as_sql()
				);
			}
			catch (Exception $e)
			{
				if ($filter_form->is_loaded_from_saved_query())
				{
					status::error('Invalid saved query', $e);
					// disable default query (loop protection)
					if ($filter_form->is_loaded_from_default_saved_query())
					{
						ORM::factory('filter_query')->remove_default($filter_form->get_base_url());
					}
					$this->redirect(url_lang::current());
				}
				throw $e;
			}
			
		}
		
		// grid of devices
		$grid = new Grid('membership_transfers', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang'). '/membership_transfers/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=>  isset($total_membership_transfers) ? $total_membership_transfers : 0,
			'items_per_page' 			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		if ($this->acl_check_new('Membership_transfers_Controller', 'membership_transfer'))
		{
			$grid->add_new_button(
				'membership_transfers/add',
				'Add membership transfer',
				array
				(
					'class' => 'popup_link'
				)
			);
		}
		
		$grid->order_field('id')
			->label('ID');
		
		$grid->order_link_field('from_member_id')
			->link('members/show', 'from_member_name')
			->label('From member');
		
		$grid->order_link_field('to_member_id')
			->link('members/show', 'to_member_name')
			->label('To member');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_edit('Membership_transfers_Controller', 'membership_transfer'))
		{
			$actions->add_action('id')
				->icon_action('edit')
				->url('membership_transfers/edit')
				->label('Edit')
				->class('popup_link');
		}
		
		if ($this->acl_check_delete('Membership_transfers_Controller', 'membership_transfer'))
		{
			$actions->add_action('id')
				->icon_action('delete')
				->url('membership_transfers/delete')
				->label('Edit')
				->class('delete_link');
		}
		
		if (!$hide_grid)
			$grid->datasource($membership_transfers);
		
		$title = __('List of all membership transfers');
		
		$view = new View('main');
		$view->title = $title;
		$view->breadcrumbs = __('Membership transfers');
		$view->content = new View('show_all');
		$view->content->headline = $title;
		$view->content->table = $grid;
		$view->render(TRUE);
	}
	
	/**
	 * Add new membership transfer
	 * 
	 * @author Michal Kliment
	 * @param integer $from_member_id
	 */
	public function add($from_member_id = NULL)
	{	
		$member_model = new Member_Model();
		
		$membership_transfer_model = new Membership_transfer_Model();
		
		$members = array();
		$former_members = array();
		$members_with_transfer_from = array();
		$members_with_transfer_to = array();
		
		if ($this->acl_check_view('Members_Controller', 'members'))
		{
			$members = arr::from_objects(
				$member_model->get_all_members_to_dropdown()
			);

			$former_members = arr::from_objects(
				$member_model->get_all_former_members_without_debt(), 'id'
			);

			$members_with_transfer_from = arr::from_objects(
				$membership_transfer_model->get_all_members_with_transfer_from(), 'id'
			);

			$members_with_transfer_to = arr::from_objects(
				$membership_transfer_model->get_all_members_with_transfer_to(), 'id'
			);
		}
		
		$breadcrumbs = breadcrumbs::add();
		
		// from member is set
		if ($from_member_id)
		{
			$from_member = new Member_Model($from_member_id);
			
			// record doesn't exist
			if (!$from_member->id)
				Controller::error (RECORD);
			
			// access control
			if (!$this->acl_check_new('Membership_transfers_Controller', 'membership_transfer', $from_member->id))
				Controller::error (ACCESS);
			
			// membership transfer can be only from former member'
			if ($from_member->type != Member_Model::TYPE_FORMER || $from_member->get_balance() < 0)
			{
				status::warning('Membership transfer can be only from former member without debt');
				url::redirect(Path::instance()->previous());
			}
			
			// this member has already membership transfer
			if (in_array($from_member->id, $members_with_transfer_from))
			{
				status::warning('This member has already membership transfer.');
				url::redirect(Path::instance()->previous());
			}
			
			$arr_from_members = array
			(
				$from_member->id => $members[$from_member->id]
			);
			
			$breadcrumbs->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller','members'))
					->disable_translation()
					->link('members/show/'.$from_member->id,
							"ID $from_member->id - $from_member->name",
							$this->acl_check_view(
									'Members_Controller','members', $from_member->id
							)
					);
		}
		else
		{		
			// access control
			if (!$this->acl_check_new('Membership_transfers_Controller', 'membership_transfer'))
				Controller::error (ACCESS);
			
			$arr_from_members = array
			(
				NULL => '----- '.__('Select member').' -----'
			);
			
			foreach ($members as $member_id => $member)
			{
				// member is former and have not membership transfer from
				if (in_array($member_id, $former_members)
					&& !in_array($member_id, $members_with_transfer_from))
				{
					$arr_from_members[$member_id] = $member;
				}
			}
			
			$breadcrumbs->link('membership_transfers/show_all', 'Membership transfers',
				$this->acl_check_view('Membership_transfers_Controller','membership_transfer'));
		}
		
		$arr_to_members = array
		(
			NULL => '----- '.__('Select member').' -----'
		);
		
		foreach ($members as $member_id => $member)
		{
			// member is not former and have not membership transfer to
			if (!in_array($member_id, $former_members)
				&& !in_array($member_id, $members_with_transfer_to))
			{
				$arr_to_members[$member_id] = $member;
			}
		}
		
		$form = new Forge();
		
		$form->dropdown('from_member_id')
				->options($arr_from_members)
				->label('From member')
				->rules('required');
		
		$form->dropdown('to_member_id')
				->options($arr_to_members)
				->label('To member')
				->rules('required');
		
		$form->submit('Submit');
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				$membership_transfer = new Membership_transfer_Model();

				$membership_transfer->transaction_start();

				$membership_transfer->from_member_id = $form_data['from_member_id'];
				$membership_transfer->to_member_id = $form_data['to_member_id'];
				
				$membership_transfer->save_throwable();
				
				$membership_transfer->transaction_commit();
				status::success('Membership transfer has been successfully added.');
			}
			catch (Exception $e)
			{
				$membership_transfer->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot add membership transfer.', $e);
			}
			
			$this->redirect(Path::instance()->previous());
		}
		else
		{
			$title = __('Add membership transfer');
			
			$breadcrumbs->text($title);

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $title;
			$view->content = new View('form');
			$view->content->headline = $title;
			$view->content->form = $form;
			$view->render(TRUE);
		}
	}
	
	/**
	 * Edits membership transfer
	 * 
	 * @author Michal Kliment
	 * @param integer $membership_transfer_id
	 */
	public function edit($membership_transfer_id = NULL)
	{	
		// bad parameter
		if (!$membership_transfer_id)
			Controller::warning (PARAMETER);
		
		$membership_transfer = new Membership_transfer_Model($membership_transfer_id);
		
		// record doesn't exist
		if (!$membership_transfer->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_edit('Membership_transfers_Controller',
				'membership_transfer', $membership_transfer->from_member_id)
			&& !$this->acl_check_edit('Membership_transfers_Controller',
				'membership_transfer', $membership_transfer->to_member_id))
		{
			Controller::error(ACCESS);
		}
		
		$member_model = new Member_Model();
		
		$membership_transfer_model = new Membership_transfer_Model();
		
		$members = array();
		$former_members = array();
		$members_with_transfer_from = array();
		$members_with_transfer_to = array();
		
		if ($this->acl_check_view('Members_Controller', 'members'))
		{
			$members = arr::from_objects(
				$member_model->get_all_members_to_dropdown()
			);

			$former_members = arr::from_objects(
				$member_model->get_all_former_members_without_debt(), 'id'
			);

			$members_with_transfer_from = arr::from_objects(
				$membership_transfer_model->get_all_members_with_transfer_from(), 'id'
			);

			$members_with_transfer_to = arr::from_objects(
				$membership_transfer_model->get_all_members_with_transfer_to(), 'id'
			);
		}
			
			
		$arr_from_members = array
		(
			$membership_transfer->from_member_id => $members[$membership_transfer->from_member_id]
		);
		
		
		$arr_to_members = array
		(
			NULL => '----- '.__('Select member').' -----'
		);
		
		foreach ($members as $member_id => $member)
		{
			// member is not former and have not membership transfer to
			if ($member_id == $membership_transfer->to_member_id ||
			(!in_array($member_id, $former_members)
				&& !in_array($member_id, $members_with_transfer_to)))
			{
				$arr_to_members[$member_id] = $member;
			}
		}
		
		$form = new Forge();
		
		$form->dropdown('from_member_id')
				->options($arr_from_members)
				->label('From member')
				->rules('required');
		
		$form->dropdown('to_member_id')
				->options($arr_to_members)
				->label('To member')
				->rules('required')
				->selected($membership_transfer->to_member_id);
		
		$form->submit('Submit');
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				$membership_transfer->transaction_start();

				$membership_transfer->from_member_id = $form_data['from_member_id'];
				$membership_transfer->to_member_id = $form_data['to_member_id'];
				
				$membership_transfer->save_throwable();
				
				$membership_transfer->transaction_commit();
				status::success('Membership transfer has been successfully updated.');
			}
			catch (Exception $e)
			{
				$membership_transfer->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot update membership transfer.', $e);
			}
			
			$this->redirect(Path::instance()->previous());
		}
		else
		{
			$breadcrumbs = breadcrumbs::add();
			
			if (Path::instance()->uri(TRUE)->previous(0,1) == 'members')
			{
				$breadcrumbs->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller','members'))
					->disable_translation()
					->link('members/show/'.$membership_transfer->from_member->id,
							"ID ".$membership_transfer->from_member->id." - ".$membership_transfer->from_member->name,
							$this->acl_check_view(
									'Members_Controller','members', $membership_transfer->from_member->id
							)
					);
			}
			else
			{
				$breadcrumbs->link('membership_transfers/show_all',
					'Membership transfers',
					$this->acl_check_view('Membership_transfers_Controller','membership_transfer'));
			}
			
			$title = __('Edit membership transfer');
			
			$breadcrumbs->text($title);

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $title;
			$view->content = new View('form');
			$view->content->headline = $title;
			$view->content->form = $form;
			$view->render(TRUE);
		}
	}
	
	/**
	 * Deletes membership transfer
	 * 
	 * @author Michal Kliment
	 * @param integer $membership_transfer_id
	 */
	public function delete($membership_transfer_id = NULL)
	{	
		// bad parameter
		if (!$membership_transfer_id)
			Controller::warning (PARAMETER);
		
		$membership_transfer = new Membership_transfer_Model($membership_transfer_id);
		
		// record doesn't exist
		if (!$membership_transfer->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_delete('Membership_transfers_Controller',
				'membership_transfer', $membership_transfer->from_member_id)
			&& !$this->acl_check_delete('Membership_transfers_Controller',
				'membership_transfer', $membership_transfer->to_member_id))
		{
			Controller::error(ACCESS);
		}
		
		try
		{
			$membership_transfer->transaction_start();

			$membership_transfer->delete_throwable();

			$membership_transfer->transaction_commit();
			status::success('Membership transfer has been successfully deleted.');
		}
		catch (Exception $e)
		{
			$membership_transfer->transaction_rollback();
			Log::add_exception($e);
			status::error('Error - Cannot delete membership transfer.', $e);
		}

		$this->redirect(Path::instance()->previous());
	}
}
