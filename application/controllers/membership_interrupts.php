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
 * Handles interrupt of member's membership to associations.
 * 
 * @package Controller
 */
class Membership_interrupts_Controller extends Controller
{
	/**
	 * Only checks whether membership interrupts are enabled
	 * 
	 * @author Jan Dubina
	 */
	public function __construct()
	{
	    parent::__construct();
	    
	    // membership interrupts are not enabled
	    if (!Settings::get('membership_interrupt_enabled'))
			Controller::error (ACCESS);
	}
	
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('membership_interrupts/show_all');	
	}
	
	/**
	 * Shows all membership interrupts
	 * 
	 * @author Michal Kliment
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
		if (!$this->acl_check_view('Members_Controller', 'membership_interrupts'))
			Controller::error(ACCESS);
		
		$filter_form = new Filter_form('mi');
		
		$filter_form->add('id')
				->type('number');

        $filter_form->add('member_id')
                ->type('number');
		
		$filter_form->add('name')
				->label(__('Member name'))
				->callback('json/member_name')
				->table('m');
		
		$filter_form->add('activation_date')
				->type('date')
				->table('mf');
		
		$filter_form->add('deactivation_date')
				->type('date')
				->table('mf');
		
		$filter_form->add('end_after_interrupt_end')
				->type('select')
				->values(arr::bool())
				->label('End membership after end');
		
		$filter_form->add('comment');
		
		$membership_interrupt_model = new Membership_interrupt_Model();
		
		$total = $membership_interrupt_model->count_all_membership_interrupts(
				$filter_form->as_sql()
		);
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total)
			$sql_offset = 0;
		
		$interupts = $membership_interrupt_model->get_all_membership_interrupts(
				$sql_offset, (int)$limit_results, $order_by,
				$order_by_direction, $filter_form->as_sql()
		);
		
		$grid = new Grid('members', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang').'/membership_interrupts/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_callback_field('member_id')
				->label('Member')
				->callback('callback::member_field_with_id');
		
		$grid->order_field('from')
				->label('Date from');
		
		$grid->order_field('to')
				->label('Date to');
		
		$grid->order_callback_field('end_after_interrupt_end')
				->callback('callback::boolean')
				->class('center')
				->label('End membership after end');
		
		$grid->order_field('comment');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_edit('Members_Controller', 'membership_interrupts'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('membership_interrupts/edit');
		}
		
		if ($this->acl_check_delete('Members_Controller', 'membership_interrupts'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('membership_interrupts/delete')
					->class('delete_link');
		}
		
		$grid->datasource($interupts);
		
		$title = __('List of all membership interrupts');
		
		$view = new View('main');
		$view->breadcrumbs = __('Membership interrupts');
		$view->title = $title;
		$view->content = new View('show_all');
		$view->content->headline = $title;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

    /**
     * Adding new membership interrupt
	 * 
     * @param integer $member_id id of member to add new membership interrupt
     */
	public function add($member_id = NULL)
	{
		if (!isset($member_id))
			Controller::warning(PARAMETER);
		
		$member = new Member_Model($member_id);
		
		if ($member->id == 0)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_new('Members_Controller', 'membership_interrupts', $member->id))
			Controller::Error(ACCESS);
		
		// saving id for callback function
		$this->members_fee_id = NULL;

		$this->form = new Forge('membership_interrupts/add/'.$member->id);
		
		$this->form->group('Basic data');
		
		if ($member->type != Member_Model::TYPE_FORMER)
		{
			$this->form->checkbox('end_after_interrupt_end')
					->label('End membership after end of interrupt')
					->callback(array($this, 'valid_end_after_interrupt_end'));
		}
		
		$this->form->date('from')
				->label('Date from')
				->years(date('Y')-10, date('Y')+10)
				->rules('required');
		
		$this->form->date('to')
				->label('Date to')
				->years(date('Y')-10, date('Y')+10)
				->rules('required')
				->callback(array($this, 'valid_interrupt_interval'));
		
		$this->form->textarea('comment')
				->rules('length[0,250]|required')
				->style('width: 350px');
		
		$this->form->submit('Save');
		
		// form validation
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();
			
			try
			{
				$membership_interrupt = new Membership_interrupt_Model();

				$membership_interrupt->transaction_start();

				$from = date('Y-m-d', $form_data['from']);
				$to = date('Y-m-d', $form_data['to']);

				$fee_model = new Fee_Model();
				$fee = $fee_model->get_by_special_type(Fee_Model::MEMBERSHIP_INTERRUPT);

				$members_fee = new Members_fee_Model();
				$members_fee->member_id = $member->id;
				$members_fee->fee_id = $fee->id;
				$members_fee->activation_date = $from;
				$members_fee->deactivation_date = $to;
				$members_fee->priority = 0;
				$members_fee->comment = '';

				$members_fee->save_throwable();

				$membership_interrupt->member_id = $member->id;
				$membership_interrupt->members_fee_id = $members_fee->id;
				$membership_interrupt->comment = $form_data['comment'];

				if (isset($form_data['end_after_interrupt_end']) &&
					$form_data['end_after_interrupt_end'] == '1' &&
					$member->type != Member_Model::TYPE_FORMER)
				{
					$membership_interrupt->end_after_interrupt_end = 1;
					
					// leaving date is validated by validator
					$member->leaving_date = $to;

					$member->save_throwable();
				}
				else
				{
					$membership_interrupt->end_after_interrupt_end = 0;
				}

				$membership_interrupt->save_throwable();

				if (Settings::get('finance_enabled'))
				{
					Accounts_Controller::recalculate_member_fees(
						$member->get_credit_account()->id
					);
				}

				ORM::factory('member')->reactivate_messages($member->id);

				// begin of redirection today? => notify member
				if (module::e('notification'))
				{
					// get message
					$message = ORM::factory('message')->get_message_by_type(
							Message_Model::INTERRUPTED_MEMBERSHIP_BEGIN_NOTIFY_MESSAGE
					);
					// create notification object
					$member_notif = array
					(
						'member_id'		=> $members_fee->member->id,
						'whitelisted'	=> $members_fee->member->has_whitelist()
					);
					$comment = array( 
						'activation_date' => $members_fee->activation_date,
						'deactivation_date' => $members_fee->deactivation_date
					 );
					// notify by email
					Notifications_Controller::notify(
							$message, array($member_notif), $this->user_id,
							$comment, FALSE, TRUE, FALSE, FALSE, FALSE, FALSE, TRUE
					);
				}

				$membership_interrupt->transaction_commit();

				status::success('Membership interrupt has been succesfully added');
			}
			catch (Exception $e)
			{
				$membership_interrupt->transaction_rollback();
				Log::add_exception($e);
				status::success('Membership interrupt has not been succesfully added');
			}

			$this->redirect('members/show/'.$member_id);
				
		}
		else
		{
			// end of form validation

			$headline = __('Add new interrupt of membership');

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
					->disable_translation()
					->text($headline);

			// view
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->form = $this->form->html();
			$view->content->headline = $headline;
			$view->render(TRUE);
		}
	}

	/**
	 * Editing of membership interruption
	 * 
	 * @param integer $membership_interrupt_id	id of membership interrupt to delete
	 */
	public function edit($membership_interrupt_id = NULL)
	{
		if (!isset($membership_interrupt_id))
			Controller::warning(PARAMETER);
		
		// find object with id to edit
		$membership_interrupt = new Membership_interrupt_Model($membership_interrupt_id);
		
		$member = new Member_Model($membership_interrupt->member_id);
		
		// if object with this id doesn't exist
		if ($membership_interrupt->id == 0)
			Controller::error(RECORD);
		
		// saving id for callback function
		$this->members_fee_id = $membership_interrupt->members_fee_id;
		// access control
		if (!$this->acl_check_edit(
				'Members_Controller', 'membership_interrupts',
				$membership_interrupt->member_id
			))
		{
			Controller::Error(ACCESS);
		}

		// form
		$this->form = new Forge();
		
		$this->form->group('Basic data');
		
		if ($member->type != Member_Model::TYPE_FORMER)
		{
			$this->form->checkbox('end_after_interrupt_end')
					->label('End membership after end of interrupt')
					->callback(array($this, 'valid_end_after_interrupt_end'))
					->checked($membership_interrupt->end_after_interrupt_end);
		}
		
		$this->form->date('from')
				->label('Date from (first day in month)')
				->years(date('Y')-10, date('Y')+10)
				->rules('required')
				->value(strtotime($membership_interrupt->members_fee->activation_date));
		
		$this->form->date('to')
				->label('Date to (last day in month)')
				->years(date('Y')-10, date('Y')+10)
				->rules('required')
				->callback(array($this, 'valid_interrupt_interval'))
				->value(strtotime($membership_interrupt->members_fee->deactivation_date));
		
		$this->form->textarea('comment')
				->rules('length[0,250]|required')
				->value($membership_interrupt->comment)
				->style('width:350px');
		
		$this->form->submit('Save');
		
		// end of form validation
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			try
			{
				$membership_interrupt->transaction_start();

				$from = date('Y-m-d', $form_data['from']);
				$to = date('Y-m-d', $form_data['to']);

				$membership_interrupt->comment = $form_data['comment'];
				
				if (isset($form_data['end_after_interrupt_end']) &&
					$form_data['end_after_interrupt_end'] == '1')
				{
					$membership_interrupt->end_after_interrupt_end = 1;

					if ($member->leaving_date > date('Y-m-d') ||
						!$member->leaving_date || 
						$member->leaving_date == '0000-00-00')
					{
						// leaving date is validated by validator
						$member->leaving_date = $to;

						$member->save_throwable();
					}
				}
				else if (isset($form_data['end_after_interrupt_end']))
				{
					$membership_interrupt->end_after_interrupt_end = 0;
					
					if ($member->leaving_date > date('Y-m-d'))
					{
						$member->leaving_date = '0000-00-00';
					
						$member->save_throwable();
					}
				}
				else
				{
					$membership_interrupt->end_after_interrupt_end = 0;
				}

				$membership_interrupt->save_throwable();

				$members_fee = new Members_fee_Model($membership_interrupt->members_fee_id);

				$members_fee->member_id = $membership_interrupt->member_id;
				$members_fee->activation_date = $from;
				$members_fee->deactivation_date = $to;

				$members_fee->save_throwable();
				
				if (Settings::get('finance_enabled'))
				{
					Accounts_Controller::recalculate_member_fees(
						$membership_interrupt->member->get_credit_account()->id
					);
				}
				
				ORM::factory('member')->reactivate_messages(
					$membership_interrupt->member_id
				);
				
				$membership_interrupt->transaction_commit();
				
				status::success('Membership interrupt has been succesfully updated');
				$this->redirect('members/show/'.$membership_interrupt->member_id);
			}
			catch (Exception $e)
			{
				$membership_interrupt->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot update membership interrupt', $e);
			}
		}
		
		$headline = __('Edit interrupt of membership');

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/'.$membership_interrupt->member->id,
						'ID ' . $membership_interrupt->member->id . ' - ' .
						$membership_interrupt->member->name,
						$this->acl_check_view(
								'Members_Controller', 'membership_interrupts', 
								$membership_interrupt->member->id
						)
				)
				->enable_translation()
				->link('membership_interrupts/show_all', 'Membership interrupts',
						$this->acl_check_view('Members_Controller', 'membership_interrupts'))
				->disable_translation()
				->text($headline);

		// end of validation
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->form = $this->form->html();
		$view->content->headline = $headline;
		$view->render(TRUE);
	}

	/**
	 * Deleting of membership interruption
	 * 
	 * @param integer $membership_interrupt_id	id of membership interruption to delete
	 */
	public function delete($membership_interrupt_id = NULL)
	{
		// parameter is wrong?
		if (!$membership_interrupt_id || !is_numeric($membership_interrupt_id))
			Controller::warning(PARAMETER);

		$membership_interrupt = new Membership_interrupt_Model($membership_interrupt_id);

		// membership interrupt doesn't exist
		if (!$membership_interrupt->id)
			Controller::error(RECORD);
		
		$member = new Member_Model($membership_interrupt->member_id);

		// access control
		if (!$this->acl_check_delete('Members_Controller', 'membership_interrupts', $member->id))
			Controller::Error(ACCESS);
		
		$members_fee = new Members_fee_Model($membership_interrupt->members_fee_id);
		
		try
		{
			$membership_interrupt->transaction_start();
			
			if ($membership_interrupt->end_after_interrupt_end &&
				$member->leaving_date > date('Y-m-d'))
			{
				$member->leaving_date = NULL;
				
				$member->save_throwable();
			}
			
			$membership_interrupt->delete_throwable();
			$members_fee->delete_throwable();
			
			Accounts_Controller::recalculate_member_fees(
				$member->get_credit_account()->id
			);	
			
			ORM::factory('member')->reactivate_messages($member->id);
			
			$membership_interrupt->transaction_commit();

			status::success('Membership interrupt has been succesfully deleted');
		}
		catch (Exception $e)
		{
			$membership_interrupt->transaction_rollback();
			Log::add_exception($e);
			status::error('Error - Cannot delete membership interrupt', $e);
		}

		$this->redirect('members/show/'.$member->id);
	}

	/**
	 * Callback function to valid interval of membership interrupt
	 *
	 * @author Michal Kliment
	 * @param object $input
	 */
	public function valid_interrupt_interval($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$method = $this->form->from->method;
		$member_id = $this->input->$method('member_id');
		$from = date_parse($this->input->$method('from'));
		$to = date_parse($this->input->$method('to'));
				
		$from_date = date::round_month($from['day'], $from['month'], $from['year']);
		$to_date = date::round_month($to['day'], $to['month'], $to['year']);
		
		$diff = date::diff_month($to_date, $from_date);
		
		if ($diff < 0)
		{
			$input->add_error('required', __(
					'Date from must be smaller then date to'
			).'.');
		}

		if ($diff < Settings::get('membership_interrupt_minimum'))
		{
			$input->add_error('required', __(
					'Minimal duration of interrupt is %s months',
					Settings::get('membership_interrupt_minimum')
			).'.');
		}

		$max = intval(Settings::get('membership_interrupt_maximum'));
		if ($max > 0 && $diff > $max)
		{
			$input->add_error('required', __(
					'Maximum duration of interrupt is %s months', $max
			).'.');
		}
		
		$fee_model = new Fee_Model();
		
		$fee = $fee_model->get_by_special_type(Fee_Model::MEMBERSHIP_INTERRUPT);

		// tests collides
		$members_fee_model = new Members_fee_Model();
		
		$members_fees = $members_fee_model->exists(
				$member_id, $fee->type_id,
				date::create($from['day'], $from['month'], $from['year']),
				date::create($to['day'], $to['month'], $to['year']),
				$this->members_fee_id, 0
		);

		// interval of interruption collides with another interruption of this member
		if (count($members_fees))
		{
			$input->add_error('required', __('Interval of interruption collides '.
					'with another interruption of this member'
			).'.');
		}
	}
	
	/**
	 * Callback function to valid date to field when checkbox is checked
	 * 
	 * @author David Raška
	 * @param object $input
	 */
	public function valid_end_after_interrupt_end($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$method = $this->form->from->method;
		$to_array = date_parse($this->input->$method('to'));
		$checked = $this->input->$method('end_after_interrupt_end');
		
		$to = mktime(0, 0, 0, $to_array['month'], $to_array['day'], $to_array['year']);
		
		if ($checked == '1' &&
			$to <= mktime(23, 59, 59))
		{
			$this->form->to->add_error('required', __('Date to must be in future if End membership is selected'));
		}
	}
}
