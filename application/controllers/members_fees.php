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
 * Members fees controller defines members fees (groups fees and members).
 *
 * @author  Kliment Michal
 * @package Controller
 */
class Members_fees_Controller extends Controller
{
	/**
	 * Function to show tariffs of member
	 * For each fee type shows independent grid
	 * 
	 * @author Michal Kliment
	 * @param number $member_id	if of member to show his tariff
	 */
	public function show_by_member ($member_id = NULL)
	{
		// wrong parameter?
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// member does't exist?
		if (!$member->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view('Members_Controller', 'fees', $member->id))
			Controller::error(ACCESS);

		$members_fee_model = new Members_fee_Model();

		// array for storing ids and names of fee type
		$arr_fee_types = array();

		// 2-dimensional array of member's fees
		$arr_members_fees = array();

		// array of grids, each for each fee type
		$members_fees_grids = array();

		/**
		 * Finds all fees belongs to member and stores it in 2-dimensional array
		 * First index is fee type, second fee
		 * It's for later rendering of grids
		 */
		
		$members_fees = $members_fee_model->get_all_fees_by_member_id($member->id);
		
		foreach ($members_fees as $member_fee)
		{
			// convert to 2-dimensional array
			$arr_members_fees[$member_fee->fee_type_id][] = $member_fee;

			// this fee type is not yet in array
			if (!isset($arr_fee_types[$member_fee->fee_type_id]))
				$arr_fee_types[$member_fee->fee_type_id] = $member_fee->fee_type_name;
		}

		// progressively generates all grids for all fee types
		foreach ($arr_members_fees as $fee_type_id => $member_fee)
		{
			// create grid
			$members_fees_grids[$fee_type_id] = new Grid(
					url::base(TRUE).url::current(), '', array
			(
				'use_paginator' => false,
				'use_selector' => false,
				'total_items' =>  count($member_fee)
			));

			// access control
			if ($this->acl_check_new('Members_Controller', 'fees', $member->id))
			{
				$members_fees_grids[$fee_type_id]->add_new_button(
						'members_fees/add/'.$member->id.'/'.$fee_type_id,
						__('Add new')
				);
			}

			// set grid fields
			$members_fees_grids[$fee_type_id]->field('id')
					->label(__('Id'));
			
			$members_fees_grids[$fee_type_id]->callback_field('fee_fee')
					->label(__('Fee'))
					->callback('callback::money');
			
			$members_fees_grids[$fee_type_id]->callback_field('fee_name')
					->label(__('Name'))
					->callback('Members_fees_Controller::fee_name_field');
			
			$members_fees_grids[$fee_type_id]->field('activation_date');
			
			$members_fees_grids[$fee_type_id]->field('deactivation_date');
			
			$members_fees_grids[$fee_type_id]->callback_field('status')
					->class('center')
					->callback('Members_fees_Controller::status_field');

			$actions = $members_fees_grids[$fee_type_id]->grouped_action_field();
			
			// access control
			if ($this->acl_check_edit('Members_Controller', 'fees', $member->id))
			{
				$actions->add_conditional_action()
						->icon_action('edit')
						->condition('special_type_id_is_not_membership_interrupt')
						->url('members_fees/edit');
			}

			// access control
			if ($this->acl_check_delete('Members_Controller', 'fees', $member->id))
			{
				$actions->add_conditional_action()
						->icon_action('delete')
						->condition('special_type_id_is_not_membership_interrupt')
						->url('members_fees/delete')
						->class('delete_link');
			}

			// set data source for this grid
			$members_fees_grids[$fee_type_id]->datasource($member_fee);
		}

		$links = array();

		// access control
		if ($this->acl_check_new('Members_Controller', 'fees', $member->id))
		{
			$links[] = html::anchor(
					'members_fees/add/'.$member->id, __('Add new tariff')
			);
		}

		// finds default fee
		$fee_model = new Fee_Model();
		
		$default_fee = $fee_model->get_default_fee_by_date_type(
				date('Y-m-d'), 'regular member fee'
		);
		
		// default fee is set
		if ($default_fee && $default_fee->fee)
		{
			$default_fee = $default_fee->fee;
		}
		// default fee is not set
		else
		{
			$default_fee = NULL;
		}
		
		// breadcrumbs
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
				->enable_translation()
				->text('Tariffs');

		$view = new View('main');
		$view->title = __('List of tariffs of member'). ' '.$member->name;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('members/fees_show_by_member');
		$view->content->links = $links;
		$view->content->member = $member;
		$view->content->fee_types = $arr_fee_types;
		$view->content->members_fees_grids = $members_fees_grids;
		$view->content->default_fee = $default_fee;
		$view->render(TRUE);
	}

	/**
	 * Function to add new tariffs
	 * If member is set, adds new tariff to this member
	 * If fee type is set, adds new tariffs only for fees of this type
	 * 
	 * @author Michal Kliment
	 * @param number $member_id id of member, to which adds new tariff (optional)
	 * @param number $fee_type_id id fee type (optional)
	 */
	public function add($member_id = NULL, $fee_type_id = NULL)
	{
		$member_model = new Member_Model();
		$fee_model = new Fee_Model();
		$enum_type_model = new Enum_type_Model();
		$translation_model = new Translation_Model();
		$breadcrumbs = breadcrumbs::add();

		// array of members, if member is set, it will be contain only this member
		$arr_members = array();

		$arr_fee_types = array();

		// array of fees, if fee type is set, it will be contain only fees with this type
		$arr_fees = array();

		// default value for from field
		$from = '';

		// member_id is set and is numeric
		if($member_id && is_numeric($member_id))
		{
			// finds member with this ID
			$member = $member_model->get_member_joined($member_id);

			// he doesn't exist
			if (!$member || !$member->id)
				Controller::error(RECORD);

			// access control
			if (!$this->acl_check_new('Members_Controller', 'fees', $member->id))
				Controller::error(ACCESS);

			// his entrance date
			$entrance_date = str_replace('-', '/', $member->entrance_date);

			// set up from default value to member entrance date
			$from = $member->entrance_date;

			// his leaving date (empthy if is not set)
			$leaving_date = ($member->leaving_date!='0000-00-00') ?
					str_replace('-', '/', $member->leaving_date) : '';

			// stores member to array
			$arr_members[$member->id] = $member->surname.' '.
					$member->name. ' (ID '.$member->id.') - '.$entrance_date;

			// it's former member
			if ($leaving_date!='')
					$arr_members[$member->id] .= ' - '.$leaving_date;
			
			// breadcrumbs
			$breadcrumbs->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/'.$member->id,
							"ID $member->id - $member->name $member->surname",
							$this->acl_check_view(
									'Members_Controller', 'members', $member->id
							)
					)
					->enable_translation()
					->link('members_fees/show_by_member/' . $member_id, 'Tariffs',
							$this->acl_check_view('Members_Controller', 'fees', $member->id))
					->text('Add new tariff');
		}
		// member_id is not set (or it is not numeric)
		else
		{
			// access control
			if (!$this->acl_check_new('Members_Controller', 'fees'))
				Controller::error(ACCESS);

			// finds all members
			$members = $member_model->get_members_joined();

			// convert to array
			foreach ($members as $member)
			{
				// member's entrance date
				$entrance_date = str_replace('-', '/', $member->entrance_date);

				// member's leaving date (empthy if is not set)
				$leaving_date = ($member->leaving_date!='0000-00-00') ? 
						str_replace('-', '/', $member->leaving_date) : '';

				// stores member to array
				$arr_members[$member->id] = $member->surname.' '.$member->name
						. ' (ID '.$member->id.') - '.$entrance_date;

				// it's former member
				if ($leaving_date!='')
					$arr_members[$member->id] .= ' - '.$leaving_date;
			}

			// adds empthy option
			$arr_members = arr::merge(array
			(
				NULL => '----- '.__('Select member').' -----'
			), $arr_members);
			
			// breadcrumbs
			$breadcrumbs->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->text('Add new tariff');
		}

		// fee_id is not set (or it is not numeric)
		if ($fee_type_id && is_numeric($fee_type_id))
		{
			// find fee type
			$fee_type = $enum_type_model->where(array
			(
				'id' => $fee_type_id,
				'type_id' => Enum_type_Model::FEE_TYPE_ID
			))->find();

			// it doesn't exist
			if (!$fee_type || !$fee_type->id)
				Controller::error(RECORD);

			// store fee type to array
			$arr_fee_types[$fee_type->id] =
					$translation_model->get_translation($fee_type->value);

			// finds all fees of this type
			$fees = $fee_model->get_all_fees_by_fee_type_id($fee_type->id);

		}

		// fee_id is not set (or it is not numeric)
		else
		{
			// finds all fees
			$arr_fee_types = $enum_type_model->get_values(Enum_type_Model::FEE_TYPE_ID);
			$arr_fee_types = arr::merge(array
			(
				NULL => '----- '.__('Select fee type').' -----'
			), $arr_fee_types);

			// finds all fees
			$fees = $fee_model->get_all_fees('type_id');
		}

		// converts to array
		foreach ($fees as $fee)
		{
			// tariff of membership interrupt can be add only by adding of new membership interrupt
			if ($fee->special_type_id == Fee_Model::MEMBERSHIP_INTERRUPT)
				continue;

			// name is optional, uses it only if it is not empty
			$name = ($fee->readonly) ? __(''.$fee->name) : $fee->name;
			$name = ($name!='') ? "- $name " : "";

			// in from and to date replaces dashes with slashes
			$fee_from = str_replace('-','/',$fee->from);
			$fee_to = str_replace('-','/',$fee->to);

			// stores fee to array
			$arr_fees[$fee->id] = "$fee->fee ".$this->settings->get('currency')
					." $name($fee_from-$fee_to)";
		}

		// adds empthy option
		$arr_fees = arr::merge(array
		(
			NULL => '----- '.__('Select fee').' -----'
		), $arr_fees);

		// creates form
		$form = new Forge(url::base(TRUE).url::current());

		$form->dropdown('member_id')
				->label('Member')
				->options($arr_members)->rules('required')
				->style('width: 620px');
		
		$form->dropdown('fee_type_id')
				->label('Fee type')
				->options($arr_fee_types)
				->style('width: 620px');
		
		$form->dropdown('fee_id')
				->label('Fee')
				->options($arr_fees)
				->rules('required')
				->add_button('fees', 'add', $fee_type_id)
				->style('width: 600px');

		$form->input('from')
				->label('Date from')
				->rules('required|valid_date_string')
				->callback(array($this, 'valid_from'))->value($from);
		
		$form->input('to')
				->label('Date to')
				->rules('valid_date_string')
				->callback(array($this, 'valid_to'));

		$form->submit('Save');

		// form is validate
		if ($form->validate())
		{
			// converts form data to array
			$form_data = $form->as_array();

			$from = $form_data['from'];
			$to = $form_data['to'];

			// to is empthy? uses fee's end date
			if ($to == '')
			{
				$fee = new Fee_Model($form_data['fee_id']);
				$to = $fee->to;
			}

			$members_fee = new Members_fee_Model();
			$members_fee->member_id = $form_data['member_id'];
			$members_fee->fee_id = $form_data['fee_id'];
			$members_fee->activation_date = $from;
			$members_fee->deactivation_date = $to;
			$members_fee->save();

			status::success('Tariff has been successfully added.');
	 		url::redirect('members_fees/show_by_member/'.$form_data['member_id']);
		}

		// member_id is set
		if ($member_id)
		{
			// fee_type_id is set
			if ($fee_type_id)
				// writes title with member name and fee type name
				$title = __('Add new tariff of type %s to member',
						$arr_fee_types[$fee_type_id]
				).' '.$member->member_name;
			else
				// writes title with only member name
				$title = __('Add new tariff to member')
					. ' '.$member->member_name;
		}
		else
			// writes general title
			$title = __('Add new tariff');
		
		$view = new View('main');
		$view->title = $title;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->form = $form->html();
		$view->content->headline = $title;
		$view->render(TRUE);
	}

	/**
	 * Function to edit tariff
	 * 
	 * @author Michal Kliment
	 * @param number $members_fee_id id of members fee to edit
	 */
	public function edit ($members_fee_id = NULL)
	{
		// wrong paremeter
		if (!$members_fee_id || !is_numeric($members_fee_id))
			Controller::warning(PARAMETER);

		$members_fee = new Members_fee_Model($members_fee_id);

		// members fee doesn't exist
		if (!$members_fee->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_edit('Members_Controller', 'fees', $members_fee->member->id))
			Controller::error(ACCESS);

		// read-only tariff cannot be edited
		if ($members_fee->fee->special_type_id == Fee_Model::MEMBERSHIP_INTERRUPT)
		{
			status::success('Read-only tariff cannot be edited.');
			url::redirect(
					url_lang::base().'members_fees/show_by_member/'.
					$members_fee->member_id
			);
		}

		// finds entrance date of member, who belongs to members fee
		$entrance_date = str_replace('-', '/', $members_fee->member->entrance_date);

		// finds leaving date of member, who belongs to members fee (empthy if is not set)
		$leaving_date = ($members_fee->member->leaving_date!='0000-00-00') ? 
				str_replace('-', '/', $members_fee->member->leaving_date) : '';

		// stores member into array
		$arr_members[$members_fee->member->id] = $members_fee->member->name. 
				' (ID '.$members_fee->member->id.') - '.$entrance_date;

		// it's former member
		if ($leaving_date!='')
			$arr_members[$members_fee->member->id] .= ' - '.$leaving_date;

		$translation_model = new Translation_Model();

		// translate fee type name
		$type = $translation_model->get_translation(
				$members_fee->fee->type->value
		);

		// name is optional, uses it only if it is not empthy
		$name = ($members_fee->fee->readonly) ? 
					__(''.$members_fee->fee->name) :
					$members_fee->fee->name;
		$name = ($name!='') ? $name." ($type)" : $type;

		// in from and to date replaces dashes with slashes
		$from = str_replace('-','/',$members_fee->fee->from);
		$to = str_replace('-','/',$members_fee->fee->to);

		// stores fee to array
		$arr_fees[$members_fee->fee_id] = "$name - ".$members_fee->fee->fee." ".
				$this->settings->get('currency')." ($from-$to)";

		// creates form
		$form = new Forge(url::base(TRUE).url::current());
		// stores members_fee_id for callback function
		$form->hidden('members_fee_id')
				->value($members_fee->id);
		
		$form->dropdown('member_id')
				->label('Member')
				->options($arr_members)
				->rules('required')
				->style('width: 620px');
		
		$form->dropdown('fee_id')
				->label('Fee')
				->options($arr_fees)
				->rules('required')
				->style('width: 620px');

		$form->input('from')
				->label('Date from')
				->rules('required|valid_date_string')
				->callback(array($this, 'valid_from'))
				->value($members_fee->activation_date);
		
		$form->input('to')
				->label('Date to')
				->rules('required|valid_date_string')
				->callback(array($this, 'valid_to'))
				->value($members_fee->deactivation_date);

		$form->submit('Save');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$members_fee = new Members_fee_Model($members_fee_id);
			$members_fee->activation_date = $form_data['from'];
			$members_fee->deactivation_date = $form_data['to'];
			$members_fee->save();

			status::success('Tariff has been successfully updated.');
	 		url::redirect('members_fees/show_by_member/'.$form_data['member_id']);
		}
		
		
		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/'.$members_fee->member->id,
						'ID ' . $members_fee->member->id . ' - ' . 
						$members_fee->member->name,
						$this->acl_check_view(
								'Members_Controller', 'members',
								$members_fee->member->id
						)
				)->enable_translation()
				->link('members_fees/show_by_member/' . $members_fee->member->id, 'Tariffs',
						$this->acl_check_view(
								'Members_Controller', 'fees',
								$members_fee->member->id
						)
				)->text($members_fee->fee->name . ' (' . $members_fee->fee->fee . ')')
				->text('Edit tariff');

		$view = new View('main');
		$view->title = __('Edit tariff of member').
				' '.$members_fee->member->name;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->form = $form->html();
		$view->content->headline = __('Edit tariff of member').' '.$members_fee->member->name;
		$view->render(TRUE);
	}

	/**
	 * Function to delete tariffs
	 * 
	 * @author Michal Kliment
	 * @param integer $members_fee_id
	 */
	public function delete ($members_fee_id = NULL)
	{
		// wrong parameter?
		if (!$members_fee_id || !is_numeric($members_fee_id))
			Controller::warning(PARAMETER);

		$members_fee = new Members_fee_Model($members_fee_id);

		// members fee doesnt'exist
		if (!$members_fee->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_delete('Members_Controller', 'fees', $members_fee->member->id))
			Controller::error(ACCESS);

		// read-only tariff cannot be edited
		if ($members_fee->fee->special_type_id == Fee_Model::MEMBERSHIP_INTERRUPT)
		{
			status::warning('Read-only tariff cannot be deleted.');
			url::redirect('members_fees/show_by_member/'.$members_fee->member_id);
		}

		// stores member_id to later redirect to list of his tariffs
		$member_id = $members_fee->member_id;

		// deletes members fee
		$members_fee->delete();

		status::success('Tariff has been successfully deleted.');
	 	url::redirect('members_fees/show_by_member/'.$member_id);
	}

	/* ----------- CALLBACK FUNCTIONS --------------------------------------- */

	/**
	 * Callback function to validate form field
	 * 
	 * @author Michal Kliment
	 * @param object $input
	 */
	public function valid_from ($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$from = trim($input->value);
		$to = trim($this->input->post('to'));

		// members_fee_id is set only if is callback call from edit function
		$members_fee_id = ($this->input->post('members_fee_id')) ? 
				$this->input->post('members_fee_id') : NULL;
		
		$fee = new Fee_Model($this->input->post('fee_id'));

		// to is empthy? uses fee's end date
		if ($to == '')
			$to = $fee->to;

		// date must be in interval of fee
		if ($fee->from > $from || $fee->to < $to || $fee->to < $from)
		{
			$input->add_error('required', __('Date must be in interval of fee.'));
		}

		$member = new Member_Model($this->input->post('member_id'));

		// member entranced after tariff activation date
		if ($member->entrance_date > $from || $member->entrance_date > $to)
		{
			$input->add_error('required', __('Date must be in interval of membership.'));
		}

		// member is former member
		if ($member->leaving_date != '0000-00-00')
		{
			// member ended his membership before tariff activation date
			if ($member->leaving_date < $from || $member->leaving_date < $to)
			{
				$input->add_error('required', __('Date must be in interval of membership.'));
			}
		}

		$members_fees = ORM::factory('members_fee')->exists(
				$member->id, $fee->type_id, $from, $to, $members_fee_id
		);

		// another fee of this type is already set up for this interval
		if (count($members_fees))
		{
			$input->add_error('required', __(
					'This member has already set up this fee type for this interval.'
			));
		}
	}

	/**
	 * Callback function to validate to field
	 * 
	 * @author Michal Kliment
	 * @param $input
	 */
	public function valid_to ($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$value = trim($input->value);

		$fee = new Fee_Model($this->input->post('fee_id'));

		// value is empthy? uses fee's end date
		if ($value == '')
			$value = $fee->to;

		// to cannot be smaller then from
		if ($this->input->post('from') > $value)
		{
			$input->add_error('required', __('Date from must be smaller then date to.'));
		}

		// test of interval of dates
		$interval = date::interval($value, $this->input->post('from'));

		// minimal duration is one month
		if (!$interval['m'] && !$interval['y'])
		{
			$input->add_error('required', __('Minimal duration is one month.'));
		}
	}

	/**
	 * Callback function to render fee's name
	 *
	 * @author Michal Kliment
	 * @param $item
	 * @param $name
	 */
	protected static function fee_name_field($item, $name)
	{
		// translates only read-only fee names
		if ($item->readonly)
			echo __(''.$item->fee_name);
		else
			echo $item->fee_name;
	}

	/**
	 * Callback function to render images for status field
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	protected static function status_field($item, $name)
	{
		// finds active fee for current date and this member and this fee type
		$activate_fee = ORM::factory('members_fee')->get_active_fee_by_member_type(
				$item->member_id, $item->fee_type_id
		);

		// tariff is active
		if ($activate_fee && $activate_fee->id == $item->id)
		{
			// show active image
			echo html::image(array
			(
				'src' => resource::state('active'),
				'alt' => __('Active'),
				'title' => __('Active')
			));
		}
		else
		{
			// show inactive image
			echo html::image(array
			(
				'src' => resource::state('inactive'),
				'alt' => __('Inactive'),
				'title' => __('Inactive')
			));
		}
	}
	
}
