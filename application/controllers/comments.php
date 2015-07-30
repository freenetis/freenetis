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
 * Controller handles comments. Comment is message with info character.
 * Comment can be related to any part of system data using commnet threads.
 * 
 * @author Michal Kliment
 * @package	Controller
 */
class Comments_Controller extends Controller
{

	/**
	 * Adds new comments to comment thread
	 *
	 * @author Michal Kliment
	 * @param integer $comments_thread_id
	 */
	public function add($comments_thread_id = NULL)
	{	    
		// bad parameter
		if (!$comments_thread_id || !is_numeric($comments_thread_id))
			Controller::warning(PARAMETER);

		$comments_thread = new Comments_thread_Model($comments_thread_id);

		// comment thread doesn't exist
		if (!$comments_thread->id)
			Controller::error(RECORD);

		// finds parent of comment thread
		$parent = $comments_thread->get_parent();

		$breadcrumbs = breadcrumbs::add();
		
		$title = '';
		
		// user who add comment
		$user = new User_Model($this->user_id);
		
		switch ($comments_thread->type)
		{
			// thread belongs to account
			case 'account':
				// access control
				if (!Settings::get('finance_enabled') ||
					!$this->acl_check_view('Members_Controller', 'comment', $parent->member_id))
					Controller::error(ACCESS);

				$link_back_url = Path::instance()->previous();

				$breadcrumbs->link('members/show_all', 'Members')
						->disable_translation()
						->link('members/show/' . $parent->member_id,
								'ID ' . $parent->member->id . ' - ' . $parent->member->name)
						->enable_translation()
						->link($link_back_url, 'Transfers')
						->text('Add comment to financial state of member');
				$title = __('Add comment to financial state of member');
				break;
				
			// thread belongs to connection_requests
			case 'connection_request':
				// access control
				if (!module::e('connection_request') ||
					!$this->acl_check_edit('Connection_Requests_Controller', 'request'))
					Controller::error(ACCESS);

				$link_back_url = Path::instance()->previous();

				$title = __('Add comment to connection request');
				
				$breadcrumbs->link('connection_requests/show_all', 'Connection requests',
								$this->acl_check_view('Connection_requests_Controller', 'request'))
						->disable_translation()
						->link('connection_requests/show/' . $parent->id,
								$parent->ip_address . ' (' . $parent->mac_address . ')',
								$this->acl_check_view('Connection_requests_Controller', 'request', $parent->member_id))
						->text($title);
				break;
				
			case 'job':
				// access control
				if (!Settings::get('works_enabled') ||
				    !$this->acl_check_new('Comments_Controller', 'works', $parent->user->member_id))
					Controller::error(ACCESS);

				if (Path::instance()->uri(TRUE)->previous(0,1) == 'users')
				{
					$breadcrumbs->link('members/show_all', 'Members',
									$this->acl_check_view('Members_Controller', 'members'))
							->disable_translation()
							->link('members/show/' . $parent->user->member->id,
									'ID ' . $parent->user->member->id . ' - ' . $parent->user->member->name,
									$this->acl_check_view('Members_Controller', 'members', $parent->user->member->id))
							->enable_translation()
							->link('users/show_by_member/' . $parent->user->member_id, 'Users',
									$this->acl_check_view('Users_Controller', 'users', $parent->user->member_id))
							->link('users/show/' . $parent->user->id,
									$parent->user->name . " " . $parent->user->surname . " (" . $parent->user->login . ")",
									$this->acl_check_view('Users_Controller', 'users', $parent->user->member_id))
							->link('works/show_by_user/' . $parent->user->id, 'Works',
									$this->acl_check_view('Works_Controller', 'work', $parent->user->member_id))
							->disable_translation()
							->link('users/show_work/' . $parent->id, __('ID') . ' ' . $parent->id,
									$this->acl_check_view('Works_Controller', 'work', $parent->user->member_id))
							->enable_translation();
				}
				else
				{
					$breadcrumbs
							->link('works/pending', 'Works',
								$this->acl_check_view('Works_Controller', 'work'))
							->disable_translation()
							->link('works/show/' . $parent->id, 
									__('ID') . ' ' . $parent->id,
									$this->acl_check_view('Works_Controller', 'work', $parent->user->member_id))
							->enable_translation();
				}
				
				$breadcrumbs->text('Add comment');
				$title = __('Add comment to work');
				
				$link_back_url = Path::instance()->previous();
				
				$type = Watcher_Model::WORK;
				
				$subject = mail_message::format('work_comment_add_subject');
				$body = mail_message::format('work_comment_add', array
				(
					$user->name.' '.$user->surname,
					$parent->user->name.' '.$parent->user->surname,
					url_lang::base().'works/show/'.$parent->id
				));
				
				break;
			
			// thread belongs to log queues
			case 'log_queue':
				// access control
				if (!$this->acl_check_new('Log_queues_Controller', 'comments'))
					Controller::error(ACCESS);

				$link_back_url = Path::instance()->previous();

				$title = __('Add comment to log');
				$tname = Log_queue_Model::get_type_name($parent->type);
				
				$breadcrumbs->link('log_queues/show_all', 'Errors and logs')
						->disable_translation()
						->link('log_queues/show/' . $parent->id, $tname . ' (' . $parent->id . ')')
						->text($title);
				break;
				
			case 'request':
				// access control
				if (!Settings::get('approval_enabled') ||
					!$this->acl_check_edit('Comments_Controller', 'requests'))
					Controller::error(ACCESS);
				
				$title = __('Add comment to request');
				
				if (Path::instance()->uri(TRUE)->previous(0,1) == 'users')
				{
					$breadcrumbs
						->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/'.$parent->user->member->id,
							'ID ' . $parent->user->member->id . ' - ' .
							$parent->user->member->name,
							$this->acl_check_view(
									'Members_Controller', 'members',
									$parent->user->member->id
							)
					)->enable_translation()
					->link('users/show_by_member/' . $parent->user->member_id,
							'Users',
							$this->acl_check_view(
									'Users_Controller', 'users',
									$parent->user->member_id
							)
					)->disable_translation()
					->link('users/show/'.$parent->user->id,
							$parent->user->name . ' ' . $parent->user->surname .
							' (' . $parent->user->login . ')',
							$this->acl_check_view(
									'Users_Controller','users',
									$parent->user->member_id
							)
					)->enable_translation()
					->link('requests/show_by_user/'.$parent->user->id, 'Requests',
							$this->acl_check_view(
									'Requests_Controller', 'request',
									$parent->user->member_id
							)
					)->link('users/show_request/'.$parent->id, 'ID '.$parent->id,
							$this->acl_check_view(
								'Requests_Controller', 'request',
								$parent->user->member_id
					));
				}
				else
				{
					$breadcrumbs
						->link('requests/show_all', 'Requests',
							$this->acl_check_view('Requests_Controller', 'request'))
						->link('requests/show/'.$parent->id, 'ID '.$parent->id,
							$this->acl_check_view(
								'Comments_Controller', 'requests',
								$parent->user->member_id
						));
				}
				
				$breadcrumbs->text('Add comment');
				
				$link_back_url = Path::instance()->previous();
				
				$type = Watcher_Model::REQUEST;
				
				$subject = mail_message::format('request_comment_add_subject');
				$body = mail_message::format('request_comment_add', array
				(
					$user->name.' '.$user->surname,
					$parent->user->name.' '.$parent->user->surname,
					url_lang::base().'requests/show/'.$parent->id
				));
				
				break;
			
			default:
				Controller::error(RECORD);
				break;
		}

		$form = new Forge();

		$form->textarea('text')
				->rules('required');
		
		$form->submit('Save');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);
			
			$comment = new Comment_Model();
			
			try
			{
				$comment->transaction_start();
				
				$comment->comments_thread_id = $comments_thread_id;
				$comment->user_id = $this->user_id;
				$comment->datetime = date('Y-m-d h:i:s');
				$comment->text = $form_data['text'];

				$comment->save_throwable();
				
				if (isset($type))
				{		
					// send message about comment adding to all watchers
					Mail_message_Model::send_system_message_to_item_watchers(
						$subject,
						$body,
						$type,
						$parent->id
					);
				
				}
				
				$comment->transaction_commit();
				status::success('Comment has been successfully added');
			}
			catch (Exception $e)
			{
				$comment->transaction_rollback();
				status::error('Error - Cannot add comment', $e);
				Log::add_exception($e);
			}

			$this->redirect($link_back_url);
		}
		else
		{
			$view = new View('main');
			$view->title = $title;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = $title;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Edits comment
	 *
	 * @author Michal Kliment
	 * @param integer $comment_id
	 */
	public function edit($comment_id = NULL)
	{
		// bad parameter
		if (!$comment_id || !is_numeric($comment_id))
			Controller::warning(PARAMETER);

		$comment = new Comment_Model($comment_id);

		// comment doesn't exist
		if (!$comment->id)
			Controller::error(RECORD);

		// finds parent of comment thread
		$parent = $comment->comments_thread->get_parent();

		$breadcrumbs = breadcrumbs::add();
		
		// user who add comment
		$user = new User_Model($this->user_id);
		
		switch ($comment->comments_thread->type)
		{
			// thread belongs to account
			case 'account':
				// access control
				if (!Settings::get('finance_enabled') ||
					!$this->acl_check_view('Members_Controller', 'comment', $parent->member_id))
					Controller::error(ACCESS);
				
				$link_back_url = 'transfers/show_by_account/' . $parent->id;
				
				$breadcrumbs->link('members/show_all', 'Members')
						->disable_translation()
						->link('members/show/' . $parent->member_id,
								'ID ' . $parent->member->id . ' - ' . $parent->member->name)
						->enable_translation()
						->link($link_back_url, 'Transfers')
						->text('Edit comment');
				break;
			
			// thread belongs to connection_requests
			case 'connection_request':
				// access control
				if (!module::e('connection_request') ||
					!$this->acl_check_edit('Connection_Requests_Controller', 'request'))
					Controller::error(ACCESS);

				$link_back_url = Path::instance()->previous();

				$title = __('Edit comment');
				
				$breadcrumbs->link('connection_requests/show_all', 'Connection requests',
								$this->acl_check_view('Connection_requests_Controller', 'request'))
						->disable_translation()
						->link('connection_requests/show/' . $parent->id,
								$parent->ip_address . ' (' . $parent->mac_address . ')',
								$this->acl_check_view('Connection_requests_Controller', 'request', $parent->member_id))
						->text($title);
				break;
				
			// thread belongs to log queues
			case 'log_queue':
				// access control
				if (!$this->acl_check_edit('Log_queues_Controller', 'comments'))
					Controller::error(ACCESS);

				$link_back_url = Path::instance()->previous();

				$title = __('Edit comment');
				$tname = Log_queue_Model::get_type_name($parent->type);
				
				$breadcrumbs->link('log_queues/show_all', 'Errors and logs')
						->disable_translation()
						->link('log_queues/show/' . $parent->id, $tname . ' (' . $parent->id . ')')
						->text($title);
				break;
				
			case 'job':
				// access control
				if (!Settings::get('works_enabled') ||
				    !$this->acl_check_edit('Comments_Controller', 'works', $comment->user->member_id))
					Controller::error(ACCESS);

				if (Path::instance()->uri(TRUE)->previous(0,1) == 'users')
				{
					$breadcrumbs->link('members/show_all', 'Members',
									$this->acl_check_view('Members_Controller', 'members'))
							->disable_translation()
							->link('members/show/' . $parent->user->member->id,
									'ID ' . $parent->user->member->id . ' - ' . $parent->user->member->name,
									$this->acl_check_view('Members_Controller', 'members', $parent->user->member->id))
							->enable_translation()
							->link('users/show_by_member/' . $parent->user->member_id, 'Users',
									$this->acl_check_view('Users_Controller', 'users', $parent->user->member_id))
							->link('users/show/' . $parent->user->id,
									$parent->user->name . " " . $parent->user->surname . " (" . $parent->user->login . ")",
									$this->acl_check_view('Users_Controller', 'users', $parent->user->member_id))
							->link('works/show_by_user/' . $parent->user->id, 'Works',
									$this->acl_check_view('Works_Controller', 'work', $parent->user->member_id))
							->disable_translation()
							->link('users/show_work/' . $parent->id, __('ID') . ' ' . $parent->id,
									$this->acl_check_view('Works_Controller', 'work', $parent->user->member_id))
							->enable_translation();
				}
				else
				{
					$breadcrumbs
							->link('works/pending', 'Works',
								$this->acl_check_view('Works_Controller', 'work'))
							->disable_translation()
							->link('works/show/' . $parent->id, 
									__('ID') . ' ' . $parent->id,
									$this->acl_check_view('Works_Controller', 'work', $parent->user->member_id))
							->enable_translation();
				}
				
				$breadcrumbs->text('Edit comment');
				
				$link_back_url = Path::instance()->previous();
				
				$type = Watcher_Model::WORK;
				
				$subject = mail_message::format('work_comment_update_subject');
				$body = mail_message::format('work_comment_update', array
				(
					$user->name.' '.$user->surname,
					$parent->user->name.' '.$parent->user->surname,
					url_lang::base().'works/show/'.$parent->id
				));
				
				break;
			
			case 'request':
				// access control
				if (!Settings::get('approval_enabled') || !$this->acl_check_edit('Comments_Controller', 'requests'))
					Controller::error(ACCESS);
				
				$title = __('Add comment to request');
				
				if (Path::instance()->uri(TRUE)->previous(0,1) == 'users')
				{
					$breadcrumbs
						->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/'.$parent->user->member->id,
							'ID ' . $parent->user->member->id . ' - ' .
							$parent->user->member->name,
							$this->acl_check_view(
									'Members_Controller', 'members',
									$parent->user->member->id
							)
					)->enable_translation()
					->link('users/show_by_member/' . $parent->user->member_id,
							'Users',
							$this->acl_check_view(
									'Users_Controller', 'users',
									$parent->user->member_id
							)
					)->disable_translation()
					->link('users/show/'.$parent->user->id,
							$parent->user->name . ' ' . $parent->user->surname .
							' (' . $parent->user->login . ')',
							$this->acl_check_view(
									'Users_Controller','users',
									$parent->user->member_id
							)
					)->enable_translation()
					->link('requests/show_by_user/'.$parent->user->id, 'Requests',
							$this->acl_check_view(
									'Requests_Controller', 'request',
									$parent->user->member_id
							)
					)->link('users/show_request/'.$parent->id, 'ID '.$parent->id,
							$this->acl_check_view(
								'Requests_Controller', 'request',
								$parent->user->member_id
					));
				}
				else
				{
					$breadcrumbs
						->link('requests/show_all', 'Requests',
							$this->acl_check_view('Requests_Controller', 'request'))
						->link('requests/show/'.$parent->id, 'ID '.$parent->id,
							$this->acl_check_view(
								'Comments_Controller', 'requests',
								$parent->user->member_id
						));
				}
				
				$breadcrumbs->text('Add comment');
				
				$link_back_url = Path::instance()->previous();
				
				$type = Watcher_Model::REQUEST;
				
				$subject = mail_message::format('request_comment_update_subject');
				$body = mail_message::format('request_comment_update', array
				(
					$user->name.' '.$user->surname,
					$parent->user->name.' '.$parent->user->surname,
					url_lang::base().'requests/show/'.$parent->id
				));
				
				break;
			
			default:
				Controller::error(RECORD);
				break;
		}

		$form = new Forge(url::base(TRUE) . url::current(TRUE));

		$form->textarea('text')
				->rules('required')
				->value($comment->text);
		
		$form->submit('Save');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);

			try
			{
				$comment->transaction_start();
			
				$comment->datetime = date('Y-m-d h:i:s');
				$comment->text = $form_data['text'];

				$comment->save_throwable();
				
				if (isset($type))
				{		
					// send message about comment updating to all watchers
					Mail_message_Model::send_system_message_to_item_watchers(
						$subject,
						$body,
						$type,
						$parent->id
					);
				
				}
			
				$comment->transaction_commit();
				status::success('Comment has been successfully updated');
			}
			catch(Exception $e)
			{
				$comment->transaction_rollback();
				status::error('Error - Cannot update comment', $e);
				Log::add_exception($e);
			}

			$this->redirect($link_back_url);
		}

		$title = __('Edit comment');

		$view = new View('main');
		$view->title = $title;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = $title;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Deletes comment
	 *
	 * @author Michal Kliment
	 * @param integer $comment_id
	 */
	public function delete($comment_id = NULL)
	{
		// bad parameter
		if (!$comment_id || !is_numeric($comment_id))
			Controller::warning(PARAMETER);

		$comment = new Comment_Model($comment_id);

		// comment doesn't exist
		if (!$comment->id)
			Controller::error(RECORD);
		
		// finds parent of comment thread
		$parent = $comment->comments_thread->get_parent();
		
		// user who add comment
		$user = new User_Model($this->user_id);

		switch ($comment->comments_thread->type)
		{
			// thread belongs to account
			case 'account':
				// access control
				if (!Settings::get('finance_enabled') ||
					!$this->acl_check_view('Members_Controller', 'comment', $parent->member_id))
					Controller::error(ACCESS);

				$link_back_url = 'transfers/show_by_account/' . $parent->id;

				break;

			// thread belongs to connection_requests
			case 'connection_request':
				// access control
				if (!module::e('connection_request') ||
					!$this->acl_check_edit('Connection_Requests_Controller', 'request'))
					Controller::error(ACCESS);

				$link_back_url = Path::instance()->previous();

				break;

			// thread belongs to log queues
			case 'log_queue':
				// access control
				if (!$this->acl_check_delete('Log_queues_Controller', 'comments'))
					Controller::error(ACCESS);

				$link_back_url = Path::instance()->previous();

				break;

			case 'job':
				// access control
				if (!Settings::get('works_enabled') ||
					!$this->acl_check_delete('Comments_Controller', 'works', $comment->user->member_id))
					Controller::error(ACCESS);

				$link_back_url = Path::instance()->previous();
				
				$type = Watcher_Model::WORK;
				
				$subject = mail_message::format('work_comment_delete_subject');
				$body = mail_message::format('work_comment_delete', array
				(
					$user->name.' '.$user->surname,
					$parent->user->name.' '.$parent->user->surname,
					url_lang::base().'works/show/'.$parent->id
				));

				break;
				
			case 'request':
				// access control
				if (!Settings::get('approval_enabled') ||
					!$this->acl_check_edit('Comments_Controller', 'requests'))
					Controller::error(ACCESS);
				
				$link_back_url = Path::instance()->previous();
				
				$type = Watcher_Model::REQUEST;
				
				$subject = mail_message::format('request_comment_delete_subject');
				$body = mail_message::format('request_comment_delete', array
				(
					$user->name.' '.$user->surname,
					$parent->user->name.' '.$parent->user->surname,
					url_lang::base().'requests/show/'.$parent->id
				));
				
				break;

			default:
				Controller::error(RECORD);
				break;
		}
		
		try
		{
			$comment->transaction_start();
			
			$comment->delete_throwable();
			
			if (isset($type))
			{		
				// send message about comment adding to all watchers
				Mail_message_Model::send_system_message_to_item_watchers(
					$subject,
					$body,
					$type,
					$parent->id
				);

			}
			
			$comment->transaction_commit();
			status::success('Comment has been successfully deleted');
		}
		catch(Exception $e)
		{
			$comment->transaction_rollback();
			status::error('Error - Cannot delete comment', $e);
			Log::add_exception($e);
		}

		$this->redirect($link_back_url);
	}

}
