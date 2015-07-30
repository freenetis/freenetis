<?php defined('SYSPATH') or die('No direct access allowed.');
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
 * Votes controller for (dis)approval of work and work reports by users.
 * 
 * @see Works_Controller
 * @see Work_reports_Controller
 * @author  Michal Kliment
 * @package Controller
 */
class Votes_Controller extends Controller
{
	/**
	 * Only checks whether approval are enabled
	 * 
	 * @author Michal Kliment
	 */
	public function __construct()
	{
	    parent::__construct();
	    
	    // approval are not enabled
	    if (!Settings::get('approval_enabled'))
			Controller::error (ACCESS);
	}

	/**
	 * Function adds vote to work or request
	 * 
	 * @author Michal Kliment
	 * @param type $type Type of item to vote
	 * @param type $fk_id ID of itemto vote
	 */
	public function add ($type = '', $fk_id = NULL)
	{
		// is parameter set?
		if (!$fk_id || !is_numeric($fk_id))
			Controller::warning(PARAMETER);
		
		$breadcrumbs = breadcrumbs::add();
		
		switch ($type)
		{
			// it's vote to work
			case Vote_Model::WORK:
				
				// works are not enabled
				if (!Settings::get('works_enabled'))
					Controller::error (ACCESS);
				
				// create work object
				$object = new Job_Model($fk_id);

				// work doesn't exist
				if (!$object->id || $object->job_report_id)
					Controller::error(RECORD);
				
				if (Path::instance()->uri(TRUE)->previous(0,1) == 'users')
				{
					$breadcrumbs->link('members/show_all', 'Members',
									$this->acl_check_view('Members_Controller', 'members'))
							->disable_translation()
							->link('members/show/' . $object->user->member->id,
									'ID ' . $object->user->member->id . ' - ' . $object->user->member->name,
									$this->acl_check_view('Members_Controller', 'members', $object->user->member->id))
							->enable_translation()
							->link('users/show_by_member/' . $object->user->member_id, 'Users',
									$this->acl_check_view('Users_Controller', 'users', $object->user->member_id))
							->link('users/show/' . $object->user->id,
									$object->user->name . " " . $object->user->surname . " (" . $object->user->login . ")",
									$this->acl_check_view('Users_Controller', 'users', $object->user->member_id))
							->link('works/show_by_user/' . $object->user->id, 'Works',
									$this->acl_check_view('Works_Controller', 'work', $object->user->member_id))
							->disable_translation()
							->link('users/show_work/' . $object->id, __('ID') . ' ' . $object->id,
									$this->acl_check_view('Works_Controller', 'work', $object->user->member_id))
							->enable_translation();
				}
				else
				{
					$breadcrumbs
							->link('works/pending', 'Works',
								$this->acl_check_view('Works_Controller', 'work'))
							->disable_translation()
							->link('works/show/' . $object->id, 
									__('ID') . ' ' . $object->id,
									$this->acl_check_view('Works_Controller', 'work', $object->user->member_id))
							->enable_translation();
				}
				
				$breadcrumbs->text('Add vote');
				
				$object_url = Path::instance()->uri(TRUE)->previous();
				
				break;
			
			// it's vote to request
			case Vote_Model::REQUEST:
				
				$object = new Request_Model($fk_id);
				
				// request doesn't exist
				if (!$object->id)
					Controller::error(RECORD);
				
				if (Path::instance()->uri(TRUE)->previous(0,1) == 'users')
				{
					$breadcrumbs->link('members/show_all', 'Members',
									$this->acl_check_view('Members_Controller', 'members'))
							->disable_translation()
							->link('members/show/' . $object->user->member->id,
									'ID ' . $object->user->member->id . ' - ' . $object->user->member->name,
									$this->acl_check_view('Members_Controller', 'members', $object->user->member->id))
							->enable_translation()
							->link('users/show_by_member/' . $object->user->member_id, 'Users',
									$this->acl_check_view('Users_Controller', 'users', $object->user->member_id))
							->link('users/show/' . $object->user->id,
									$object->user->name . " " . $object->user->surname . " (" . $object->user->login . ")",
									$this->acl_check_view('Users_Controller', 'users', $object->user->member_id))
							->link('requests/show_by_user/' . $object->user->id, 'Requests',
									$this->acl_check_view('Requests_Controller', 'request', $object->user->member_id))
							->disable_translation()
							->link('users/show_request/' . $object->id, __('ID') . ' ' . $object->id,
									$this->acl_check_view('Requests_Controller', 'request', $object->user->member_id))
							->enable_translation();
				}
				else
				{
					$breadcrumbs
							->link('works/request', 'Requests',
								$this->acl_check_view('Requests_Controller', 'request'))
							->disable_translation()
							->link('requests/show/' . $object->id, 
									__('ID') . ' ' . $object->id,
									$this->acl_check_view('Requests_Controller', 'request', $object->user->member_id))
							->enable_translation();
				}
				
				$breadcrumbs->text('Add vote');
				
				$object_url = Path::instance()->uri(TRUE)->previous();
				
				break;
			
			default:
				Controller::warning(PARAMETER);
				break;
		}

		$approval_template_item_model = new Approval_template_item_Model();
		
		$aro_group = $approval_template_item_model->get_aro_group_by_approval_template_id_and_user_id(
				$object->approval_template_id, $this->user_id,
				$object->suggest_amount
		);
				
		$vote_rights = $approval_template_item_model->check_user_vote_rights(
				$object, $type, $this->user_id, $object->suggest_amount
		);

		// access control
		if (!$aro_group || !$aro_group->id || !$vote_rights)
			Controller::error(ACCESS);

		// object is locked => cannot add vote
		if ($object->state > 1)
		{
			status::warning('It is not possible vote about locked item.');
			url::redirect($object_url);
		}

		$vote_model = new Vote_Model();
		
		$vote = $vote_model->where('user_id', $this->user_id)
				->where('type', $type)
				->where('fk_id', $object->id)
				->find();

		// vote about this work already exists
		if ($vote && $vote->id)
		{
			status::warning('You cannot vote twice about same item!');
			url::redirect($object_url);
		}
	
		$vote_options = Vote_Model::get_vote_options(
			$type,
			$object->user_id == $this->user_id
		);

		$form = new Forge();

		$form->dropdown('vote')
				->options($vote_options);
		
		$form->textarea('comment')
				->rules('length[0,65535]');
		
		$form->submit('Save');
                
		// form is valid
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				$object->transaction_start();
				
				$user = new User_Model($this->user_id);

				// add new vote
				Vote_Model::insert(
					$this->user_id,
					$type,
					$object->id,
					$form_data['vote'],
					$form_data['comment'],
					$aro_group->id
				);
				
				switch ($type)
				{
					case Vote_Model::WORK:
						$subject = mail_message::format('work_vote_add_subject');
						$body = mail_message::format('work_vote_add', array
						(
							$user->name.' '.$user->surname,
							$object->user->name.' '.$object->user->surname,
							url_lang::base().'works/show/'.$object->id
						));
						break;
					
					case Vote_Model::REQUEST:
						$subject = mail_message::format('request_vote_add_subject');
						$body = mail_message::format('request_vote_add', array
						(
							$user->name.' '.$user->surname,
							$object->user->name.' '.$object->user->surname,
							url_lang::base().'requests/show/'.$object->id
						));
						break;
				}

				// send message about vote adding to all watchers
				Mail_message_Model::send_system_message_to_item_watchers(
					$subject,
					$body,
					$type,
					$object->id
				);

				$object->state = Vote_Model::get_state($object);

				switch ($object->state)
				{
					// item is approved
					case Vote_Model::STATE_APPROVED:
						
						switch ($type)
						{
							case Vote_Model::WORK:
								
								if (Settings::get('finance_enabled'))
								{
									// create transfer
									$object->transfer_id = Transfer_Model::insert_transfer_for_work_approve(
										$object->user->member_id,
										$object->suggest_amount
									);
								}

								$subject = mail_message::format('work_approve_subject');
								$body = mail_message::format('work_approve', array
								(
									$object->user->name.' '.$object->user->surname,
									url_lang::base().'works/show/'.$object->id
								));
								
								break;
							
							case Vote_Model::REQUEST:
								
								$subject = mail_message::format('request_approve_subject');
								$body = mail_message::format('request_approve', array
								(
									$object->user->name.' '.$object->user->surname,
									url_lang::base().'requests/show/'.$object->id
								));
								
								break;
						}
						
						// send messages about work approve to all watchers
						Mail_message_Model::send_system_message_to_item_watchers(
							$subject,
							$body,
							$type,
							$object->id
						);
						
						break;

						// work was rejected
					case Vote_Model::STATE_REJECTED:
						
						switch ($type)
						{
							case Vote_Model::WORK:
								
								$subject = mail_message::format('work_reject_subject');
								$body = mail_message::format('work_reject', array
								(
									$object->user->name.' '.$object->user->surname,
									url_lang::base().'works/show/'.$object->id
								));
								
								break;
							
							case Vote_Model::REQUEST:
								
								$subject = mail_message::format('request_reject_subject');
								$body = mail_message::format('request_reject', array
								(
									$object->user->name.' '.$object->user->surname,
									url_lang::base().'requests/show/'.$object->id
								));
								
								break;
						}
						
						// send messages about work approve to all watchers
						Mail_message_Model::send_system_message_to_item_watchers(
							$subject,
							$body,
							$type,
							$object->id
						);

						break;
				}

				// saves work
				$object->save_throwable();

				ORM::factory ('member')
					->reactivate_messages($object->user->member_id);

				// set up state of approval template
				Approval_template_Model::update_state(
					$object->approval_template_id
				);

				$object->transaction_commit();
				status::success('Vote has been successfully added.');
			}
			catch (Exception $e)
			{
				$object->transaction_rollback();
				status::error('Error - Cannot add vote.', $e);
				Log::add_exception($e);
			}
			
			$this->redirect($object_url);
		}
		else
		{
			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = __('Add vote');
			$view->content = new View('form');
			$view->content->headline = __('Add vote');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Function edits vote
	 * 
	 * @author Michal Kliment
	 * @param integer $vote_id id of vote to edit
	 */
	public function edit ($vote_id = NULL)
	{
		// is parameter set
		if (!$vote_id || !is_numeric($vote_id))
		    Controller::warning(PARAMETER);

		// create vote object
		$vote = new Vote_Model($vote_id);

		// vote doesn't exist
		if (!$vote->id)
			Controller::error(RECORD);
		
		$breadcrumbs = breadcrumbs::add();

		// test type of vote
		switch ($vote->type)
		{
			// vote about work
			case Vote_Model::WORK:

				// works are not enabled
				if (!Settings::get('works_enabled'))
					Controller::error (ACCESS);
			    
				$object = new Job_Model($vote->fk_id);
				
				// work doesn't exist
				if (!$object->id || $object->job_report_id)
					Controller::error(RECORD);
				
				if (Path::instance()->uri(TRUE)->previous(0,1) == 'users')
				{
					$breadcrumbs->link('members/show_all', 'Members',
									$this->acl_check_view('Members_Controller', 'members'))
							->disable_translation()
							->link('members/show/' . $object->user->member->id,
									'ID ' . $object->user->member->id . ' - ' . $object->user->member->name,
									$this->acl_check_view('Members_Controller', 'members', $object->user->member->id))
							->enable_translation()
							->link('users/show_by_member/' . $object->user->member_id, 'Users',
									$this->acl_check_view('Users_Controller', 'users', $object->user->member_id))
							->link('users/show/' . $object->user->id,
									$object->user->name . " " . $object->user->surname . " (" . $object->user->login . ")",
									$this->acl_check_view('Users_Controller', 'users', $object->user->member_id))
							->link('works/show_by_user/' . $object->user->id, 'Works',
									$this->acl_check_view('Works_Controller', 'work', $object->user->member_id))
							->disable_translation()
							->link('users/show_work/' . $object->id, __('ID') . ' ' . $object->id,
									$this->acl_check_view('Works_Controller', 'work', $object->user->member_id))
							->enable_translation();
				}
				else
				{
					$breadcrumbs
							->link('works/pending', 'Works',
								$this->acl_check_view('Works_Controller', 'work'))
							->disable_translation()
							->link('works/show/' . $object->id, 
									__('ID') . ' ' . $object->id,
									$this->acl_check_view('Works_Controller', 'work', $object->user->member_id))
							->enable_translation();
				}
				
				$breadcrumbs->text('Edit vote');
				
				$object_url = Path::instance()->uri(TRUE)->previous();
				
				$title = __('Edit vote about work');

			    break;
				
			// it's vote to request
			case Vote_Model::REQUEST:
				
				$object = new Request_Model($vote->fk_id);
				
				// request doesn't exist
				if (!$object->id)
					Controller::error(RECORD);
				
				if (Path::instance()->uri(TRUE)->previous(0,1) == 'users')
				{
					$breadcrumbs->link('members/show_all', 'Members',
									$this->acl_check_view('Members_Controller', 'members'))
							->disable_translation()
							->link('members/show/' . $object->user->member->id,
									'ID ' . $object->user->member->id . ' - ' . $object->user->member->name,
									$this->acl_check_view('Members_Controller', 'members', $object->user->member->id))
							->enable_translation()
							->link('users/show_by_member/' . $object->user->member_id, 'Users',
									$this->acl_check_view('Users_Controller', 'users', $object->user->member_id))
							->link('users/show/' . $object->user->id,
									$object->user->name . " " . $object->user->surname . " (" . $object->user->login . ")",
									$this->acl_check_view('Users_Controller', 'users', $object->user->member_id))
							->link('requests/show_by_user/' . $object->user->id, 'Requests',
									$this->acl_check_view('Requests_Controller', 'request', $object->user->member_id))
							->disable_translation()
							->link('users/show_request/' . $object->id, __('ID') . ' ' . $object->id,
									$this->acl_check_view('Requests_Controller', 'request', $object->user->member_id))
							->enable_translation();
				}
				else
				{
					$breadcrumbs
							->link('works/request', 'Requests',
								$this->acl_check_view('Requests_Controller', 'request'))
							->disable_translation()
							->link('requests/show/' . $object->id, 
									__('ID') . ' ' . $object->id,
									$this->acl_check_view('Requests_Controller', 'request', $object->user->member_id))
							->enable_translation();
				}
				
				$breadcrumbs->text('Edit vote');
				
				$object_url = Path::instance()->uri(TRUE)->previous();
				
				$title = __('Edit vote about request');
				
				break;
			    
			default:
				Controller::warning(PARAMETER);
				break;
		}
		
		// cannot delete vote to locked item
		if ($object->state != Vote_Model::STATE_NEW
			&& $object->state != Vote_Model::STATE_OPEN)
		{
			status::warning('Error - Cannot update vote to locked item.');
			$this->redirect(Path::instance()->previous());
		}
		
		$approval_template_item = new Approval_template_item_Model();
				
		$aro_group = $approval_template_item->get_aro_group_by_approval_template_id_and_user_id(
			$object->approval_template_id, $this->user_id,
			$object->suggest_amount
		);

		$vote_rights = $approval_template_item->check_user_vote_rights(
				$object, $vote->type, $this->user_id, $object->suggest_amount
		);

		// access control
		if (!$aro_group || !$aro_group->id || !$vote_rights ||
			$vote->user_id != $this->user_id)
		{
			Controller::error(ACCESS);
		}

		// item is locked => cannot edit vote
		if ($object->state != Vote_Model::STATE_NEW &&
			$object->state != Vote_Model::STATE_OPEN)
		{
			status::warning('It is not possible vote about locked item.');
			url::redirect($object_url);
		}

		$vote_options = Vote_Model::get_vote_options(
			$vote->type,
			$object->user_id == $this->user_id
		);

		$form = new Forge();

		$form->dropdown('vote')
				->options($vote_options)
				->selected($vote->vote);
		
		$form->textarea('comment')
				->rules('length[0,65535]')
				->value($vote->comment);
		
		$form->submit('Save');

		// form is valid
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				$object->transaction_start();
				
				$user = new User_Model($this->user_id);

				$vote->vote = $form_data['vote'];
				$vote->comment = $form_data['comment'];
				$vote->time = date('Y-m-d H:i:s');
				$vote->save_throwable();
				
				switch ($vote->type)
				{
					case Vote_Model::WORK:
						
						$subject = mail_message::format('work_vote_update_subject');
						$body = mail_message::format('work_vote_update', array
						(
							$user->name.' '.$user->surname,
							$object->user->name.' '.$object->user->surname,
							url_lang::base().'works/show/'.$object->id
						));
						
						break;
					
					case Vote_Model::REQUEST:
						
						$subject = mail_message::format('request_vote_update_subject');
						$body = mail_message::format('request_vote_update', array
						(
							$user->name.' '.$user->surname,
							$object->user->name.' '.$object->user->surname,
							url_lang::base().'requests/show/'.$object->id
						));
						
						break;
				}
				
				// send message about vote adding to all watchers
				Mail_message_Model::send_system_message_to_item_watchers(
					$subject,
					$body,
					$vote->type,
					$object->id
				);

				$object->state = Vote_Model::get_state($object);

				switch ($object->state)
				{
					// item is approved
					case Vote_Model::STATE_APPROVED:
						
						switch ($vote->type)
						{
							// item is work
							case Vote_Model::WORK:
								
								if (Settings::get('finance_enabled'))
								{
									// create transfer
									$object->transfer_id = Transfer_Model::insert_transfer_for_work_approve(
										$object->user->member_id,
										$object->suggest_amount
									);
								}

								$subject = mail_message::format('work_approve_subject');
								$body = mail_message::format('work_approve', array
								(
									$object->user->name.' '.$object->user->surname,
									url_lang::base().'works/show/'.$object->id
								));
								
								break;
						}
						
						// send messages about work approve to all watchers
						Mail_message_Model::send_system_message_to_item_watchers(
							$subject,
							$body,
							$vote->type,
							$object->id
						);
						
						break;

						// work was rejected
					case Vote_Model::STATE_REJECTED:
						
						switch ($vote->type)
						{
							// item is work
							case Vote_Model::WORK:

								$subject = mail_message::format('work_reject_subject');
								$body = mail_message::format('work_reject', array
								(
									$object->user->name.' '.$object->user->surname,
									url_lang::base().'works/show/'.$object->id
								));
								
								break;
						}
						
						// send messages about work approve to all watchers
						Mail_message_Model::send_system_message_to_item_watchers(
							$subject,
							$body,
							$vote->type,
							$object->id
						);

						break;
				}

				$object->save_throwable();
				
				ORM::factory ('member')
					->reactivate_messages($object->user->member_id);

				// set up state of approval template
				Approval_template_Model::update_state(
					$object->approval_template_id
				);
				
				$object->transaction_commit();
				status::success('Vote has been successfully updated.');
			}
			catch (Exception $e)
			{
				$object->transaction_rollback();
				status::error('Error - Cannot update vote.', $e);
				Log::add_exception($e);
			}
			
			$this->redirect($object_url);
		}
		else
		{
			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $title;
			$view->content = new View('form');
			$view->content->headline = $title;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Function deletes vote
	 * 
	 * @author Michal Kliment
	 * @param integer $vote_id id of vote to delete
	 */
	public function delete ($vote_id = NULL)
	{
		// is parameter set
		if (!$vote_id || !is_numeric($vote_id))
		    Controller::warning (PARAMETER);

		// creates vote object
		$vote = new Vote_Model($vote_id);

		// vote doesn't exist
		if (!$vote->id)
			Controller::error(RECORD);

		// test type of vote
		switch ($vote->type)
		{
			case Vote_Model::WORK:
			    
				// works are not enabled
				if (!Settings::get('works_enabled'))
					Controller::error (ACCESS);
			    
				$object = new Job_Model($vote->fk_id);
				
				// work doesn't exist
				if (!$object->id || $object->job_report_id)
					Controller::error(RECORD);
				
				$object_url = Path::instance()->uri(TRUE)->previous();
				
				break;
				
			// it's vote to request
			case Vote_Model::REQUEST:
				
				$object = new Request_Model($vote->fk_id);
				
				// request doesn't exist
				if (!$object->id)
					Controller::error(RECORD);
				
				$object_url = Path::instance()->uri(TRUE)->previous();
				
				break;
				
			default:
				Controller::warning(PARAMETER);
				break;
		}
		
		// cannot delete vote to locked item
		if ($object->state != Vote_Model::STATE_NEW
			&& $object->state != Vote_Model::STATE_OPEN)
		{
			status::warning('Error - Cannot delete vote to locked item.');
			$this->redirect(Path::instance()->previous());
		}
		
		try
		{
			$object->transaction_start();
			
			$user = new User_Model($this->user_id);
			
			$approval_template_item = new Approval_template_item_Model();

			$aro_group = $approval_template_item->get_aro_group_by_approval_template_id_and_user_id(
				$object->approval_template_id, $this->user_id,
				$object->suggest_amount
			);

			$vote_rights = $approval_template_item->check_user_vote_rights(
					$object, $vote->type, $this->user_id, $object->suggest_amount
			);

			// access control
			if (!$aro_group || !$aro_group->id || !$vote_rights ||
				$vote->user_id != $this->user_id)
			{
				Controller::error(ACCESS);
			}

			// item is locked => cannot edit vote
			if ($object->state != Vote_Model::STATE_NEW &&
				$object->state != Vote_Model::STATE_OPEN)
			{
				status::warning('It is not possible vote about locked item.');
				url::redirect($object_url);
			}
			
			switch ($vote->type)
			{
				case Vote_Model::WORK:
					
					$subject = mail_message::format('work_vote_delete_subject');
					$body = mail_message::format('work_vote_delete', array
					(
						$user->name.' '.$user->surname,
						$object->user->name.' '.$object->user->surname,
						url_lang::base().'works/show/'.$object->id
					));
					
					break;
				
				case Vote_Model::REQUEST:
					
					$subject = mail_message::format('request_vote_delete_subject');
					$body = mail_message::format('request_vote_delete', array
					(
						$user->name.' '.$user->surname,
						$object->user->name.' '.$object->user->surname,
						url_lang::base().'requests/show/'.$object->id
					));
					
					break;
			}
			
			// send message about vote adding to all watchers
			Mail_message_Model::send_system_message_to_item_watchers(
				$subject,
				$body,
				$vote->type,
				$object->id
			);

			// delete vote
			$vote->delete_throwable();

			$object->state = Vote_Model::get_state($object);
			
			$object->save_throwable();

			// set up state of approval template
			Approval_template_Model::update_state(
					$object->approval_template_id
			);
			
			$object->transaction_commit();
			status::success('Vote has been successfully deleted.');
		}
		catch (Exception $e)
		{
			$object->transaction_rollback();
			status::error('Error - Cannot delete vote.', $e);
			Log::add_exception($e);
		}
			
		url::redirect($object_url);
	}
	
	
	/** CALLBACK FUNCTIONS **/

	/**
	 * Callback function to show vote dropwdown in grid
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 * @param object $input
	 */
	protected static function vote_form_field ($item, $name, $input, $args = array())
	{		
		if (isset($args[0]) && isset($args[1]))
		{
			$items_to_vote	= $args[0];
			$type		= $args[1];
			
			if (in_array($item->id, $items_to_vote))
			{
				$uid = Session::instance()->get('user_id');

				$input->options(
					$input->options +
					Vote_Model::get_vote_options(
						$type, $uid == $item->user_id
					)
				);
				
				echo $input->html();
			}
		}	
	}

	/**
	 * Callback function to show comment textarea in grid
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 * @param object $input
	 */
	protected static function comment_form_field ($item, $name, $input, $args = array())
	{
		if (isset($args[0]))
		{
			$items_to_vote	= $args[0];
			
			if (in_array($item->id, $items_to_vote))
			{	
				echo $input->html();
			}
		}
	}

}
