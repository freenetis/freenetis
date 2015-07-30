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
 * Allowed subnet's controls and performs actions over connecting places of member.
 * Each member can connect just to specified subnets at same time.
 * 
 * Maximum of allowed connecting places at the same time
 * is defined by Allowed_subnets_count_Controller.
 *
 * @author	Michal Kliment
 * @package Controller
 */
class Allowed_subnets_Controller extends Controller
{

	/**
	 * Shows all allowed subnets of member
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 */
	public function show_by_member($member_id = NULL)
	{
		// bad parameter
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// record doesn't exist
		if (!$member->id || $member->id == 1)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view('Devices_Controller', 'allowed_subnet', $member->id))
			Controller::error(ACCESS);

		// finds all allowed subnets of member
		$allowed_subnet_model = new Allowed_subnet_Model();
		$allowed_subnets = $allowed_subnet_model
			->get_all_allowed_subnets_by_member($member->id, 'cidr');

		// grid
		$grid = new Grid('members', null, array
		(
				'separator'		=> '<br /><br />',
				'use_paginator'	=> false,
				'use_selector'	=> false,
		));

		$grid->add_new_button(
				'allowed_subnets/add/' . $member->id,
				__('Add new subnet'),
				array
				(
					'title' => __('Add new subnet'),
					'class' => 'popup_link'
				)
		);

		$grid->link_field('subnet_id')
				->link('subnets/show', 'subnet_name');
		
		$grid->callback_field('cidr_address')
				->label(__('Network address'))
				->callback('callback::cidr_field');
		
		$param = '';
		
		if ($allowed_subnet_model->count_all_disabled_allowed_subnets_by_member($member->id))
		{
			$param = 'allowed_subnets/change/';
		}
		
		$grid->callback_field('enabled')
				->callback('callback::enabled_field', $param)
				->class('center');
		
		$grid->grouped_action_field()
				->add_action('id')
				->icon_action('delete')
				->url('allowed_subnets/delete')
				->class('delete_link');

		// load records
		$grid->datasource($allowed_subnets);

		// breadcrums
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/' . $member->id, "ID $member->id - $member->name",
						$this->acl_check_view('Members_Controller', 'members', $member->id))
				->enable_translation()
				->text('Allowed subnets')
				->html();

		// view
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs;
		$view->title = __('Allowed subnets of member') . ' ' . $member->name;
		$view->content = new View('allowed_subnets/show_by_member');
		$view->content->member_id = $member->id;
		$view->content->count = $member->allowed_subnets_count->count;
		$view->content->headline = __('Allowed subnets of member') . ' ' . $member->name;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Adds new allowed subnet to member
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 */
	public function add($member_id = NULL)
	{
		// bad parameter
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// member doesn't exist
		if (!$member->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_new('Devices_Controller', 'allowed_subnet', $member->id))
			Controller::error(ACCESS);

		$subnet_model = new Subnet_Model();

		// finds all subnets without allowed subnets of member
		$subnets = $subnet_model->get_all_subnets_without_allowed_subnets_of_member($member->id);
		
		$arr_subnets = arr::merge(array
		(
			NULL => '----- ' . __('select subnet') . ' -----'
		), arr::from_objects($subnets));

		// selected subnet
		$current_subnet = $subnet_model->get_subnet_without_allowed_subnets_of_member_by_ip_address(
				$member->id, server::remote_addr()
		);
		$selected = ($current_subnet && $current_subnet->id) ? $current_subnet->id : 0;

		// from
		$form = new Forge();

		$form->dropdown('subnet_id')
				->label(__('Subnet') . ':')
				->rules('required')
				->options($arr_subnets)
				->selected($selected)
				->add_button('subnets');

		$form->submit('Add');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$allowed_subnet = new Allowed_subnet_Model();
			$allowed_subnet->member_id = $member->id;
			$allowed_subnet->subnet_id = $form_data['subnet_id'];
			$allowed_subnet->enabled = 1;

			if ($allowed_subnet->save())
			{
				status::success('Subnet has been successfully saved.');
				self::update_enabled($member->id);
			}

			$this->redirect('allowed_subnets/show_by_member/' . $member->id);
		}
		else
		{
			// bread crumbs
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/' . $member->id, "ID $member->id - $member->name",
							$this->acl_check_view('Members_Controller', 'members', $member->id))
					->enable_translation()
					->link('allowed_subnets/show_by_member/' . $member->id, 'Allowed subnets')
					->text('Add new subnet')
					->html();

			$title = __('Add new allowed subnet to member') . ' ' . $member->name;

			// view
			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs;
			$view->title = $title;
			$view->content = new View('form');
			$view->content->headline = $title;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 *  Deletes subnet from allowed subnets of member
	 *
	 * @author Michal Kliment
	 * @param integer $allowed_subnet_id
	 */
	public function delete($allowed_subnet_id = NULL)
	{
		// bad parameter
		if (!$allowed_subnet_id || !is_numeric($allowed_subnet_id))
			Controller::warning(PARAMETER);

		$allowed_subnet = new Allowed_subnet_Model($allowed_subnet_id);

		// record doesn't exist
		if (!$allowed_subnet->id)
			Controller::error(RECORD);

		$member_id = $allowed_subnet->member_id;

		// access control
		if (!$this->acl_check_delete('Devices_Controller', 'allowed_subnet', $member_id))
			Controller::error(ACCESS);

		// success
		if ($allowed_subnet->delete())
		{
			status::success('Subnet has been successfully deleted.');
			self::update_enabled($member_id);
		}

		$this->redirect(Path::instance()->previous());
	}

	/**
	 * Toggle state of allowed subnet of member (from disable to enabled and from enabled to disable)
	 *
	 * @author Michal Kliment
	 * @param integer $allowed_subnet_id
	 */
	public function change($allowed_subnet_id = NULL)
	{
		// bad parameter
		if (!$allowed_subnet_id || !is_numeric($allowed_subnet_id))
			Controller::warning(PARAMETER);

		$allowed_subnet = new Allowed_subnet_Model($allowed_subnet_id);

		// record doesn't exist
		if (!$allowed_subnet->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_edit('Devices_Controller', 'allowed_subnet', $allowed_subnet->member->id))
			Controller::error(ACCESS);

		$allowed_subnet->enabled = !$allowed_subnet->enabled;
		$allowed_subnet->save();

		if ($allowed_subnet->enabled)
		{
			status::success('Subnet has been successfully enabled.');
			self::update_enabled($allowed_subnet->member_id);
		}
		else
		{
			status::success('Subnet has been successfully disabled.');
			self::update_enabled($allowed_subnet->member_id, NULL, array($allowed_subnet->subnet_id));
		}

		$this->redirect(Path::instance()->previous());
	}

	/**
	 * Updates states of allowed subnets of member
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 * @param string | array $to_enable
	 * @return void
	 */
	public static function update_enabled(
			$member_id, $to_enable = array(), $to_disable = array(),
			$to_remove = array())
	{
		// bad parameter
		if (!$member_id)
			return;

		$member = new Member_Model($member_id);

		// member doesn't exist
		if (!$member->id)
			return;

		// to_enabled must be array
		if (!is_array($to_enable))
			$to_enable = array($to_enable);

		if (!is_array($to_disable))
			$to_disable = array($to_disable);

		// finds all allowed subnet of member
		$allowed_subnet_model = new Allowed_subnet_Model();
		
		try
		{
			$allowed_subnet_model->transaction_start();
			
			$allowed_subnets = $allowed_subnet_model->where('member_id', $member->id)
					->where('member_id', $member->id)
					->orderby(array('enabled' => 'desc', 'last_update' => 'desc'))
					->find_all();

			$arr_subnets = array();

			// to_enabled have the hightest priority
			foreach ($allowed_subnets as $allowed_subnet)
			{
				if (in_array($allowed_subnet->subnet_id, $to_remove))
				{
					$allowed_subnet->delete();
					continue;
				}

				if (!in_array($allowed_subnet->subnet_id, $to_enable) &&
					!in_array($allowed_subnet->subnet_id, $to_disable))
				{
					$arr_subnets[] = $allowed_subnet->subnet_id;
				}
			}

			$arr_subnets = arr::merge($to_enable, $arr_subnets, $to_disable);

			// maximum count of allowed subnets (0 = unlimited)
			$max_enabled = ($member->allowed_subnets_count->count) ?
					$member->allowed_subnets_count->count : count($arr_subnets);

			$enabled = 0;
			foreach ($arr_subnets as $subnet)
			{
				if (!$subnet)
					continue;

				if ($aid = $allowed_subnet_model->exists($member->id, $subnet))
				{
					$allowed_subnet_model->where('id', $aid)->find();
				}
				else
				{
					$allowed_subnet_model->clear();
					$allowed_subnet_model->member_id = $member->id;
					$allowed_subnet_model->subnet_id = $subnet;
				}

				$allowed_subnet_model->enabled = ($enabled < $max_enabled);

				$enabled++;

				$allowed_subnet_model->save();
			}
			
			$allowed_subnet_model->transaction_commit();
		}
		catch (Exception $e)
		{
			$allowed_subnet_model->transaction_rollback();
			Log::add_exception($e);
		}
	}

}
