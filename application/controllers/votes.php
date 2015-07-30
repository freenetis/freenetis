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
	 * Function adds vote to work
	 * 
	 * @author Michal Kliment
	 * @param integer $work_id id of work
	 */
	public function add_to_work($work_id = NULL)
	{
		// is parameter set?
		if (!$work_id || !is_numeric($work_id))
			Controller::warning(PARAMETER);

		// create work object
		$work = new Job_Model($work_id);

		// work doesn't exist
		if (!$work->id || $work->job_report_id)
			Controller::error(RECORD);

		$approval_template_item_model = new Approval_template_item_Model();
		
		$aro_group = $approval_template_item_model->get_aro_group_by_approval_template_id_and_user_id(
				$work->approval_template_id, $this->session->get('user_id'),
				$work->suggest_amount
		);
				
		$vote_rights = $approval_template_item_model->check_user_vote_rights(
				$work->id, $this->user_id
		);

		// access control
		if (!$aro_group || !$aro_group->id || !$vote_rights)
			Controller::error(ACCESS);

		// work is locked => cannot add vote
		if ($work->state > 1)
		{
			status::warning('It is not possible vote about locked work.');
			url::redirect('works/show/'.$work->id);
		}

		// work is on work report => cannot vote this way
		if ($work->job_report_id)
		{
			status::warning('It is not possible vote this way about work on work report.');
			url::redirect('works/show/'.$work->id);
		}

		$vote_model = new Vote_Model();
		
		$vote = $vote_model->where('user_id', $this->session->get('user_id'))
				->where('type',Vote_Model::WORK)
				->where('fk_id', $work->id)
				->find();

		// vote about this work already exists
		if ($vote && $vote->id)
		{
			status::warning('You cannot vote twice about same work!');
			url::redirect('works/show/'.$work->id);
		}

		if ($work->user_id != $this->session->get('user_id'))
		{
			$vote_options = array
			(
				1	=> __('Agree'),
				-1	=> __('Disagree'),
				0	=> __('Abstain')
			);
		}
		else
		{
			// nobody cannot approve his own work
			$vote_options = array(0 => __('Abstain'));
		}

		$form = new Forge(url::base(TRUE).url::current(TRUE));

		$form->dropdown('vote')
				->options($vote_options);
		
		$form->textarea('comment')
				->rules('length[0,65535]');
		
		$form->submit('Save');
                
		// form is valid
		if ($form->validate())
		{
			$form_data = $form->as_array();
			$mail_message = new Mail_message_Model();
			$user = new User_Model();

			$vote = new Vote_Model();
			$vote->user_id = $this->session->get('user_id');
			$vote->type = Vote_Model::WORK;
			$vote->fk_id = $work->id;
			$vote->aro_group_id = $aro_group->id;
			$vote->vote = $form_data['vote'];
			$vote->comment = $form_data['comment'];
			$vote->time = date('Y-m-d H:i:s');
			$vote->save();
			
			$work->state = $work->get_state(Vote_Model::WORK);

			// work is approved
			if ($work->state == 3)
			{
				// creates new transfer
				$account_model = new Account_Model();
				
				$operating_id = $account_model->where(
						'account_attribute_id', Account_attribute_Model::OPERATING
				)->find()->id;
				
				$credit_id = $account_model->where('member_id', $work->user->member_id)
						->where('account_attribute_id', Account_attribute_Model::CREDIT)
						->find()->id;

				$transfer_id = Transfer_Model::insert_transfer(
					$operating_id, $credit_id, null, null,
						$this->session->get('user_id'),
					null, date('Y-m-d'), date('Y-m-d H:i:s'),
					__('Work approval'), $work->suggest_amount
				);

				$work->transfer_id = $transfer_id;
			}

			// saves work
			$work->save();
			
			ORM::factory ('member')
					->reactivate_messages($work->user->member_id);

			// is not necessary send message to user who voted about his work
			if ($vote->user_id != $work->user_id)
			{
				$user->clear();
				$user->where('id', $vote->user_id)->find();

				switch ($work->state)
				{
					// work is pending
					case 1:
						$your_subject = mail_message::format('your_work_vote_add_subject');
						$your_body = mail_message::format('your_work_vote_add', array
						(
							$user->name.' '.$user->surname,
							url_lang::base().'works/show/'.$work->id
						));
						break;

					// work is rejected
					case 2:
						$your_subject = mail_message::format('your_work_reject_subject');
						$your_body = mail_message::format('your_work_reject', array
						(
							$user->name.' '.$user->surname,
							url_lang::base().'works/show/'.$work->id
						));
						break;

					// work is approved
					case 3:
						$your_subject = mail_message::format('your_work_approved_subject');
						$your_body = mail_message::format('your_work_approved', array
						(
							$user->name.' '.$user->surname,
							url_lang::base().'works/show/'.$work->id
						));
						break;					
				}

				$mail_message->clear();
				$mail_message->from_id = 1;
				$mail_message->to_id = $work->user_id;
				$mail_message->subject = $your_subject;
				$mail_message->body = $your_body;
				$mail_message->time = date('Y-m-d H:i:s');
				$mail_message->from_deleted = 1;
				$mail_message->save();
			}

			// finds all aro ids assigned to vote about this work
			$aro_ids = $approval_template_item_model->get_aro_ids_by_approval_template_id(
					$work->approval_template_id, $work->suggest_amount
			);

			// count of aro ids is not null
			if (count($aro_ids))
			{
				// finds user to whom belongs work
				$user->clear();
				$user->where('id', $work->user_id)->find();
				$work_user = $user->name.' '.$user->surname;

				$user->clear();
				$user->where('id', $vote->user_id)->find();
				$vote_user = $user->name.' '.$user->surname;

				switch ($work->state)
				{
					// work is pending
					case 1;
						$subject = mail_message::format('work_vote_add_subject');
						$body = mail_message::format('work_vote_add', array
						(
							$vote_user, $work_user,
							url_lang::base().'works/show/'.$work->id
						));
						break;
					
					// work is rejected
					case 2:
						$subject = mail_message::format('work_reject_subject');
						$body = mail_message::format('work_reject', array
						(
							$vote_user, $work_user,
							url_lang::base().'works/show/'.$work->id
						));
						break;

					// work is approved
					case 3:
						$subject = mail_message::format('work_approved_subject');
						$body = mail_message::format('work_approved', array
						(
							$vote_user, $work_user,
							url_lang::base().'works/show/'.$work->id
						));
						break;
				}

				foreach ($aro_ids as $aro)
				{
					// is not necessary send message to  user who added vote
					if ($aro->id != $this->session->get('user_id'))
					{
						// sends message
						$mail_message->clear();
						$mail_message->from_id = 1;
						$mail_message->to_id = $aro->id;
						$mail_message->subject = $subject;
						$mail_message->body = $body;
						$mail_message->time = date('Y-m-d H:i:s');
						$mail_message->from_deleted = 1;
						$mail_message->save();
					}
				}
			}

			// set up state of approval template
			$approval_template = new Approval_template_Model($work->approval_template_id);
			$approval_template->state = $approval_template->get_state($approval_template->id);
			$approval_template->save();

			status::success('Vote has been successfully added.');
			url::redirect('works/show/'.$work->id);
		}

		$view = new View('main');
		$view->title = __('Add vote about work');
		$view->content = new View('form');
		$view->content->headline = __('Add vote about work');
		$view->content->link_back = html::anchor(
				'works/show/'.$work->id, __('Back to the work')
		);
		$view->content->form = $form->html();
		$view->render(TRUE);
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

		// test type of vote
		switch ($vote->type)
		{
			// vote about work
			case Vote_Model::WORK:
			    
			    $work = new Job_Model($vote->fk_id);

			    $approval_template_item = new Approval_template_item_Model();
				
			    $aro_group = $approval_template_item->get_aro_group_by_approval_template_id_and_user_id(
						$work->approval_template_id,
						$this->session->get('user_id'),
						$work->suggest_amount
				);
				
				$vote_rights = $approval_template_item->check_user_vote_rights(
						$work->id, $this->user_id
				);

			    // access control
			    if (!$aro_group || !$aro_group->id || !$vote_rights ||
					$vote->user_id != $this->user_id)
				{
					Controller::error(ACCESS);
				}

			    // work is locked => cannot edit vote
			    if ($work->state >= 2)
			    {
				    status::warning('It is not possible vote about locked work.');
				    url::redirect('works/show/'.$work->id);
			    }

			    // work is on work report => cannot vote this way
			    if ($work->job_report_id)
			    {
				    status::warning('It is not possible vote this way about work on work report.');
				    url::redirect('works/show/'.$work->id);
			    }

			    if ($work->user_id != $this->session->get('user_id'))
			    {
				    $vote_options = array(1 => __('Agree'),
								 -1 => __('Disagree'),
								  0 => __('Abstain'));
			    }
			    else
			    {
				    // nobody cannot approve his own work
				    $vote_options = array(0 => __('Abstain'));
			    }
			    $title = __('Edit vote about work');
			    $link_back = html::anchor(
						url_lang::base().'works/show/'.$work->id,
						__('Back to the work')
				);
			    break;
		}

		$form = new Forge(url::base(TRUE).url::current(TRUE));

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
			$mail_message = new Mail_message_Model();
			$user = new User_Model();

			$vote = new Vote_Model($vote_id);
			$vote->vote = $form_data['vote'];
			$vote->comment = $form_data['comment'];
			$vote->time = date('Y-m-d H:i:s');
			$vote->save();

			switch ($vote->type)
			{
				// vote about work
				case Vote_Model::WORK:
					$work->state = $work->get_state(Vote_Model::WORK);

					// work is approved
					if ($work->state == 3)
					{
						// creates new transfer
						$account_model = new Account_Model();

						$operating_id = $account_model->where(
								'account_attribute_id',
								Account_attribute_Model::OPERATING
						)->find()->id;
						
						$credit_id = $account_model->where(
										'member_id', $work->user->member_id
								)->where(
										'account_attribute_id',
										Account_attribute_Model::CREDIT
								)->find()->id;

						$transfer_id = Transfer_Model::insert_transfer(
							$operating_id, $credit_id, null,
							null, $this->session->get('user_id'),
							null, date('Y-m-d'), date('Y-m-d H:i:s'),
							__('Work approval'), $work->suggest_amount
						);

						$work->transfer_id = $transfer_id;
					}

					// saves work
					$work->save();
					
					ORM::factory ('member')
							->reactivate_messages($work->user->member_id);

					if ($work->state == 3)
					{
						$your_subject = __('Your work has been approved');
						$your_body = 'mail.your_work_approve';

						$subject = __('Work has been approved');
						$body = 'mail.work_approve';
					}
					// work is rejected
					else if ($work->state == 2)
					{
						$your_subject = __('Your work has been rejected');
						$your_body = 'mail.your_work_reject';

						$subject = __('Work has been rejected');
						$body = 'mail.work_reject';
					}
					// work is pending
					else
					{
						$your_subject = __('Vote to your work has been updated');
						$your_body = 'mail.your_work_vote_add';

						$subject = __('Vote to work has been updated');
						$body = 'mail.work_vote_add';
					}

					// is not necessary send message to user who voted about his work
					if ($vote->user_id != $work->user_id)
					{
						$user->clear();
						$user->where('id', $vote->user_id)->find();

						$mail_message->clear();
						$mail_message->from_id = 1;
						$mail_message->to_id = $work->user_id;
						$mail_message->subject = $your_subject;
						$mail_message->body = url_lang::lang($your_body, array
						(
							$user->name.' '.$user->surname,
							url_lang::base().'works/show/'.$work->id
						));
						$mail_message->time = date('Y-m-d H:i:s');
						$mail_message->from_deleted = 1;
						$mail_message->save();
					}

					// finds all aro ids assigned to vote about this work
					$aro_ids = $approval_template_item->get_aro_ids_by_approval_template_id(
							$work->approval_template_id, $work->suggest_amount
					);

					// count of aro ids is not null
					if (count($aro_ids))
					{
						// finds user to whom belongs work
						$user->clear();
						$user->where('id', $work->user_id)->find();
						$work_user = $user->name.' '.$user->surname;

						$user->clear();
						$user->where('id', $vote->user_id)->find();
						$vote_user = $user->name.' '.$user->surname;

						$body = url_lang::lang($body, array
						(
							$vote_user, $work_user,
							url_lang::base().'works/show/'.$work->id
						));

						foreach ($aro_ids as $aro)
						{
							// is not necessary send message to  user who edit vote
							if ($aro->id != $this->session->get('user_id'))
							{
								// sends message
								$mail_message->clear();
								$mail_message->from_id = 1;
								$mail_message->to_id = $aro->id;
								$mail_message->subject = $subject;
								$mail_message->body = $body;
								$mail_message->time = date('Y-m-d H:i:s');
								$mail_message->from_deleted = 1;
								$mail_message->save();
							}
						}
					}

					// set up state of approval template
					$approval_template = new Approval_template_Model(
							$work->approval_template_id
					);
					
					$approval_template->state = $approval_template->get_state(
							$approval_template->id
					);
					
					$approval_template->save();
					break;
			}

			
			status::success('Vote has been successfully updated.');
			url::redirect('works/show/'.$work->id);
		}

		$view = new View('main');
		$view->title = $title;
		$view->content = new View('form');
		$view->content->headline = $title;
		$view->content->link_back = $link_back;
		$view->content->form = $form->html();
		$view->render(TRUE);
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
			    
			    $work = new Job_Model($vote->fk_id);

			    $approval_template_item = new Approval_template_item_Model();
				
			    $aro_group = $approval_template_item->get_aro_group_by_approval_template_id_and_user_id(
						$work->approval_template_id,
						$this->session->get('user_id'),
						$work->suggest_amount
				);
				
				$vote_rights = $approval_template_item->check_user_vote_rights(
						$work->id, $this->user_id
				);

			    // access control
			    if (!$aro_group || !$aro_group->id || !$vote_rights ||
					$vote->user_id != $this->user_id)
				{
					Controller::error(ACCESS);
				}

			    // work is locked => cannot edit vote
			    if ($work->state > 1)
			    {
					status::warning('It is not possible vote about locked work.');
			    }
			    else
			    {
					// delete vote
					$vote->delete();
					status::success('Vote has been successfully deleted.');

					$work->state = $work->get_state(Vote_Model::WORK);
					$work->save();
					// set up state of approval template
					$approval_template = new Approval_template_Model(
							$work->approval_template_id
					);
					$approval_template->state = $approval_template->get_state(
							$approval_template->id
					);
					$approval_template->save();
			    }
			    url::redirect('works/show/'.$work->id);
			    break;
		}
		
	}

}
