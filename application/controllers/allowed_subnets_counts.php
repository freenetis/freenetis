<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is release under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Allowed subnets counts controller allow change of maximum allowed connecting
 * place per member.
 *
 * @see Allowed_subnets_Controller
 * @author	Michal Kliment
 * @package Controller
 */
class Allowed_subnets_counts_Controller extends Controller
{

	/**
	 * Edits maximum count of allowed subnets of member
	 *
	 * @author Michal Kliment
	 * @param integer $member_id
	 */
	public function edit($member_id = NULL)
	{
		// bad parameter
		if (!$member_id || !is_numeric($member_id))
			Controller::warning(PARAMETER);

		$member = new Member_Model($member_id);

		// member doesn't exist
		if (!$member->id || $member->id == 1)
			Controller::error(RECORD);
		
		// access control
		if (!$this->acl_check_edit('Allowed_subnets_Controller', 'allowed_subnet', $member_id))
			Controller::error(ACCESS);

		$form = new Forge(url::base(TRUE) . url::current(TRUE));

		$form->input('allowed_subnets_count')
				->label(__('Count of allowed subnets')
						. ': ' . help::hint('allowed_subnets_count'))
				->rules('valid_numeric')
				->value($member->allowed_subnets_count->count);

		$form->submit('Edit');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				$member->transaction_start();

				// posted value is not null
				if ($form_data['allowed_subnets_count'])
				{
					// count of allowed subnets is not set
					if ($member->allowed_subnets_count->id == 0)
					{
						$allowed_subnets_count = new Allowed_subnets_count_Model();
						$allowed_subnets_count->member_id = $member->id;
						$allowed_subnets_count->count = $form_data['allowed_subnets_count'];
						$allowed_subnets_count->save_throwable();
					}
					// count of allowed subnets is already set
					else
					{
						$member->allowed_subnets_count->count = $form_data['allowed_subnets_count'];
						$member->allowed_subnets_count->save_throwable();
					}
				}
				// delete null count
				else if ($member->allowed_subnets_count)
				{
					$member->allowed_subnets_count->delete_throwable();
				}

				Allowed_subnets_Controller::update_enabled($member->id);
				
				$member->transaction_commit();
				
				status::success('Count of allowed subnets has been successfully updated.');
			}
			catch (Exception $e)
			{
				$member->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - Cannot update count of allowed subnets', $e);
			}
			
			$this->redirect('allowed_subnets/show_by_member/' . $member_id);
		}
		else
		{
			$title = __('Edit maximum count of allowed subnets of member') . ' ' . $member->name;

			// bread crumbs
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/' . $member->id, "ID $member->id - $member->name",
							$this->acl_check_view('Members_Controller', 'members', $member->id))
					->enable_translation()
					->link('allowed_subnets/show_by_member/' . $member->id, 'Allowed subnets')
					->text('Edit maximum count')
					->html();

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

}
