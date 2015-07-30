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
 * Handles white list for redirection and notification.
 * 
 * @package Controller
 */
class Members_whitelists_Controller extends Controller
{
	// help variables for callback
	private $pom_member_id = NULL;
	private $members_whitelist_id = NULL;
	
	/**
	 * Only enable if notification enabled
	 */
	public function __construct()
	{
		parent::__construct();
		
	    if (!module::e('notification'))
			self::error(ACCESS);
	}
	
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('members_whitelists/show_all');	
	}
	
	/**
	 * Shows all members with whitelist
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $page_word
	 * @param integer $page 
	 */
	public function show_all (
			$limit_results = 50, $order_by = 'id', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// access rights
		if (!$this->acl_check_view('Members_whitelists_Controller', 'whitelist'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'registration', 'name', 'street','redirect',  'street_number',
			'town', 'quarter', 'ZIP_code', 'entrance_fee', 'debt_payment_rate',
			'current_credit', 'entrance_date', 'comment',
			'balance', 'type_name', 'items_count'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		$filter_form = new Filter_form('m');
		
		$filter_form->add('member_name')
				->type('combo')
				->callback('json/member_name');
		
		$filter_form->add('type')
				->type('select')
				->values(ORM::factory('enum_type')->get_values(Enum_type_Model::MEMBER_TYPE_ID));
		
		$filter_form->add('whitelisted')
				->type('select')
				->label('Whitelist')
				->values(Ip_address_Model::get_whitelist_types());
		
		$filter_form->add('balance')
				->type('number');
		
		// load members
		$member_whitelist = new Members_whitelist_Model();
		$total_members = $member_whitelist->count_whitelisted_members($filter_form->as_sql());
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_members)
			$sql_offset = 0;
		
		$query = $member_whitelist->get_whitelisted_members(
				$sql_offset, (int)$limit_results, $order_by,
				$order_by_direction, $filter_form->as_sql()
		);
		// it creates grid to view all members
		$headline = __('List of whitelisted members');
		
		$grid = new Grid('members', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang').'/members_whitelists/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_members,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		// database columns - some are commented out because of lack of space
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_field('type');
		
		$grid->order_field('name');
		
		$grid->order_callback_field('whitelisted')
				->label('Whitelist')
				->callback('callback::whitelisted_field');
		
		$grid->order_callback_field('balance')
				->callback('callback::balance_field');
		
		$actions = $grid->grouped_action_field();
		
		$actions->add_action()
				->icon_action('member')
				->url('members/show')
				->label('Show member');
		
		$actions->add_action('aid')
				->icon_action('transfer')
				->url('transfers/show_by_account')
				->label('Show transfers');
		
		$grid->datasource($query);
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $headline;
		$view->content = new View('show_all');
		$view->content->table = $grid;
		$view->content->headline = $headline . ' ' . help::hint('whitelist');
		$view->render(TRUE);
	}
	
	/**
	 * Shows all whitelists of a member
	 * 
	 * @param integer $member_id
	 */
	public function show_by_member($member_id = NULL)
	{
		// parameter is wrong
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);

		$this->member = $member = new Member_Model($member_id);

		// member doesn't exist
		if (!$member->id)
			Controller::error(RECORD);
		
		// access rights
		if (!$this->acl_check_view('Members_whitelists_Controller', 'whitelist', $member->id))
			Controller::error(ACCESS);
		
		// load members
		$member_whitelist = new Members_whitelist_Model();
		
		// it creates grid to view all members
		$headline = __('List of members whitelists');
		
		$grid = new Grid('member_whitelist', null, array
		(
			'use_paginator' => false,
			'use_selector' => false,
		));
		
		if ($this->acl_check_new('Members_whitelists_Controller', 'whitelist', $member->id))
		{
			$grid->add_new_button(
					'members_whitelists/add/' . $member->id,
					'Add new whitelist', array('class' => 'popup_link')
			);
		}
		
		$grid->field('id')
				->label('ID');
		
		$grid->callback_field('permanent')
				->label('Permanent whitelist')
				->callback('callback::boolean');
		
		$grid->field('since');
		
		$grid->field('until');
		
		$grid->callback_field('active')
				->callback('callback::active_field');
		
		$grid->field('comment');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_edit('Members_whitelists_Controller', 'whitelist'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('members_whitelists/edit')
					->class('popup_link');
		}
		
		if ($this->acl_check_delete('Members_whitelists_Controller', 'whitelist'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('members_whitelists/delete')
					->class('delete_link');
		}
		
		$grid->datasource($member_whitelist->get_member_whitelists($member_id));
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/'.$member->id,
						"ID $member->id - $member->name",
						$this->acl_check_view(
								'Members_Controller', 'members', $member->id
						)
				)
				->text($headline);
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('show_all');
		$view->content->table = $grid;
		$view->content->headline = $headline . ' ' . help::hint('whitelist');
		$view->render(TRUE);
	}

    /**
     * Adding new whitelist for member
	 * 
     * @param integer $member_id id of member to add new whitelist
     */
	public function add($member_id = NULL)
	{
		if (!isset($member_id))
			Controller::warning(PARAMETER);
		
		$member = new Member_Model($member_id);
		
		if (!$member->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_new('Members_whitelists_Controller', 'whitelist', $member->id))
			Controller::Error(ACCESS);
		
		// saving id for callback function
		$this->pom_member_id = $member->id;
		$this->members_whitelist_id = NULL;

		$form = new Forge('members_whitelists/add/'.$member->id);
		
		$form->group('Basic data');
		
		$form->checkbox('permanent')
				->label('Permanent whitelist')
				->callback(array($this, 'valid_whitelist_interval'));
		
		$form->date('since')
				->label('Date from')
				->years(date('Y'), date('Y')+10)
				->rules('required');
		
		$form->date('until')
				->label('Date to')
				->years(date('Y'), date('Y')+10)
				->value(time() + 60*60*24*3) // three days
				->rules('required');
		
		$form->textarea('comment')
				->rules('length[0,250]')
				->style('width: 350px');
		
		$form->submit('Save');
		
		// form validation
		if ($form->validate())
		{
			$mw = new Members_whitelist_Model();
			
			try
			{
				$form_data = $form->as_array();
				$mw->transaction_start();
				
				// is permanent?
				$is_permanent = $form_data['permanent'] > 0;
				
				if ($is_permanent)
				{
					$form_data['since'] = '0000-00-00';
					$form_data['until'] = '9999-12-31';
				}
				else
				{
					$form_data['since'] = date('Y-m-d', $form_data['since']); 
					$form_data['until'] = date('Y-m-d', $form_data['until']); 
				}
				
				$mw->member_id = $member->id;
				$mw->permanent = $is_permanent;
				$mw->since = $form_data['since'];
				$mw->until = $form_data['until'];
				$mw->comment = $form_data['comment'];
				$mw->save_throwable();
				
				// reactivate messages
				$member->reactivate_messages();

				$mw->transaction_commit();
				status::success('Whitelist has been succesfully added');
				$this->redirect('members_whitelists/show_by_member', $member->id);
			}
			catch (Exception $e)
			{
				$mw->transaction_rollback();
				Log::add_exception($e);
				status::success('Whitelist has not been added');
			}
		}
		else
		{
			// end of form validation
			$headline = __('Add new whitelist');

			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/'.$member_id,
							"ID $member->id - $member->name",
							$this->acl_check_view(
									'Members_Controller', 'members', $member_id
							)
					)
					->enable_translation()
					->link('members_whitelists/show_by_member/'.$member_id, 'Whitelists',
							$this->acl_check_view(
									'Members_whitelists_Controller', 'whitelist', $member->id
							)
					)
					->disable_translation()
					->text($headline);

			// view
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->form = $form->html();
			$view->content->headline = $headline;
			$view->render(TRUE);
		}
	}

	/**
	 * Editing of member whitelist
	 * 
	 * @param integer $members_whitelist_id
	 */
	public function edit($members_whitelist_id = NULL)
	{
		if (!isset($members_whitelist_id))
			Controller::warning(PARAMETER);
		
		$mw = new Members_whitelist_Model($members_whitelist_id);
		
		if (!$mw->id)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_edit('Members_whitelists_Controller', 'whitelist', $mw->id))
			Controller::Error(ACCESS);
		
		// saving id for callback function
		$this->pom_member_id = $mw->member_id;
		$this->members_whitelist_id = $mw->id;

		$form = new Forge('members_whitelists/edit/'.$mw->id);
		
		$form->group('Basic data');
		
		$form->checkbox('permanent')
				->label('Permanent whitelist')
				->callback(array($this, 'valid_whitelist_interval'))
				->checked($mw->permanent);
		
		$form->date('since')
				->label('Date from')
				->rules('required')
				->value(strtotime($mw->since));
		
		$form->date('until')
				->label('Date to')
				->rules('required')
				->value(strtotime($mw->until));
		
		$form->textarea('comment')
				->rules('length[0,250]')
				->style('width: 350px')
				->value($mw->comment);
		
		$form->submit('Save');
		
		// form validation
		if ($form->validate())
		{
			try
			{
				$form_data = $form->as_array();
				$mw->transaction_start();
				
				// is permanent?
				$is_permanent = $form_data['permanent'] > 0;
				
				if ($is_permanent)
				{
					$form_data['since'] = '0000-00-00';
					$form_data['until'] = '9999-12-31';
				}
				else
				{
					$form_data['since'] = date('Y-m-d', $form_data['since']); 
					$form_data['until'] = date('Y-m-d', $form_data['until']); 
				}
				
				$mw->permanent = $is_permanent;
				$mw->since = $form_data['since'];
				$mw->until = $form_data['until'];
				$mw->comment = $form_data['comment'];
				$mw->save_throwable();
				
				// reactivate messages
				$mw->member->reactivate_messages();

				$mw->transaction_commit();
				status::success('Whitelist has been succesfully edited');
				$this->redirect('members_whitelists/show_by_member', $mw->member_id);
			}
			catch (Exception $e)
			{
				$mw->transaction_rollback();
				Log::add_exception($e);
				status::success('Whitelist has not been edited');
			}
		}
		else
		{
			// end of form validation
			$headline = __('Edit whitelist');
			$name = ($mw->permanent) ? __('Permanent whitelist') : $mw->since . ' - ' . $mw->until;
			
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/'.$mw->member_id,
							"ID $mw->member_id - $mw->member->name",
							$this->acl_check_view(
									'Members_Controller', 'members', $mw->member_id
							)
					)
					->enable_translation()
					->link('members_whitelists/show_by_member/'.$mw->member_id, 'Whitelists',
							$this->acl_check_view(
									'Members_whitelists_Controller', 'whitelist', $mw->member_id
							)
					)
					->disable_translation()
					->text($name)
					->text($headline);

			// view
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->form = $form->html();
			$view->content->headline = $headline;
			$view->render(TRUE);
		}
	}

	/**
	 * Deleting of whitelist of a member
	 * 
	 * @param integer $member_whitelist_id
	 */
	public function delete($member_whitelist_id = NULL)
	{
		// parameter is wrong?
		if (!$member_whitelist_id || !is_numeric($member_whitelist_id))
			Controller::warning(PARAMETER);

		$mw = new Members_whitelist_Model($member_whitelist_id);

		// doesn't exist
		if (!$mw->id)
			Controller::error(RECORD);
		
		$mid = $mw->member_id;

		// access control
		if (!$this->acl_check_delete('Members_whitelists_Controller', 'whitelist', $mid))
			Controller::Error(ACCESS);

		// success
		try
		{
			$mw->transaction_start();
			
			$mw->delete_throwable();
			ORM::factory('member')->reactivate_messages($mid);
			
			$mw->transaction_commit();
			status::success('Whitelist has been succesfully deleted');
			$this->redirect('members_whitelists/show_by_member', $mid);
		}
		catch (Exception $e)
		{
			$mw->transaction_rollback();
			Log::add_exception($e);
			status::success('Whitelist has not been deleted');
		}

	}

	/**
	 * Callback function to valid interval of members whitelist
	 *
	 * @param Form_Input $input
	 */
	public function valid_whitelist_interval($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$permanent = $this->input->post('permanent');
		$since = date_parse($this->input->post('since'));
		$until = date_parse($this->input->post('until'));
		
		// prepare
		$sd = date::create($since['day'], $since['month'], $since['year']);
		$ud = date::create($until['day'], $until['month'], $until['year']);
		$mw_id = $this->members_whitelist_id;
		
		// invalid input
		if (!$permanent && $sd > $ud)
		{
			$input->add_error('required', __('Date from must be smaller then date to'));
		}
		
		// not unique interval
		$mw = new Members_whitelist_Model();
		
		if ($mw->exists($this->pom_member_id, $permanent, $sd, $ud, $mw_id))
		{
			$input->add_error('required', __('Interval of whitelist collides with ' 
					. 'another whitelist of this member.'));
		}
	}
}
