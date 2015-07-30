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
		
		switch ($comments_thread->type)
		{
			// thread belongs to account
			case 'account':
				// access control
				if (!$this->acl_check_view('Members_Controller', 'comment', $parent->member_id))
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
				
			case 'job':
				// access control
				if (!$this->acl_check_new(get_class($this), 'works', $parent->user->member_id))
					Controller::error(ACCESS);

				if (url::slice(url_lang::uri(Path::instance()->previous()), 1, 1) == 'show_work')
				{
					$link_back_url = 'users/show_work/' . $parent->id;

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
									$this->acl_check_view('Users_Controller', 'work', $parent->user->member_id))
							->disable_translation()
							->link('users/show_work/' . $parent->id, __('ID') . ' ' . $parent->id,
									$this->acl_check_view('Users_Controller', 'work', $parent->user->member_id))
							->enable_translation();
				}
				else
				{
					$link_back_url = 'works/show/' . $parent->id;

					switch ($parent->state)
					{
						case 0:
						case 1:
							$breadcrumbs->link('works/pending', 'Pending works',
									$this->acl_check_view('Users_Controller', 'work'));
							break;
						case 2:
							$breadcrumbs->link('works/rejected', 'Rejected works',
									$this->acl_check_view('Users_Controller', 'work'));
							break;
						case 3:
							$breadcrumbs->link('works/approved', 'Approved works',
									$this->acl_check_view('Users_Controller', 'work'));
							break;
					}
					
					$breadcrumbs->disable_translation()
							->link('works/show/' . $parent->id, 
									__('ID') . ' ' . $parent->id,
									$this->acl_check_view('Users_Controller', 'work', $parent->user->member_id))
							->enable_translation();
				}
				$breadcrumbs->text('Add comment to work');
				$title = __('Add comment to work');
				break;
			
			default:
				Controller::error(RECORD);
				break;
		}

		$form = new Forge(url::base(TRUE) . url::current(TRUE));

		$form->textarea('text')
				->rules('required');
		
		$form->submit('Save');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$comment = new Comment_Model();
			$comment->comments_thread_id = $comments_thread_id;
			$comment->user_id = $this->session->get('user_id');
			$comment->datetime = date('Y-m-d h:i:s');
			$comment->text = $form_data['text'];

			if ($comment->save())
			{
				status::success('Comment has been successfully added');
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
		
		switch ($comment->comments_thread->type)
		{
			// thread belongs to account
			case 'account':
				// access control
				if (!$this->acl_check_view('Members_Controller', 'comment', $parent->member_id))
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
				
			case 'job':
				// access control
				if (!$this->acl_check_edit(get_class($this), 'works', $comment->user->member_id))
					Controller::error(ACCESS);

				if (url::slice(url_lang::uri(Path::instance()->previous()), 1, 1) == 'show_work')
				{
					$link_back_url = 'users/show_work/' . $parent->id;
					
					
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
									$this->acl_check_view('Users_Controller', 'work', $parent->user->member_id))
							->disable_translation()
							->link('users/show_work/' . $parent->id, __('ID') . ' ' . $parent->id,
									$this->acl_check_view('Users_Controller', 'work', $parent->user->member_id))
							->enable_translation();
				}
				else
				{
					$link_back_url = 'works/show/' . $parent->id;

					switch ($parent->state)
					{
						case 0:
						case 1:
							$breadcrumbs->link('works/pending', 'Pending works',
									$this->acl_check_view('Users_Controller', 'work'));
							break;
						case 2:
							$breadcrumbs->link('works/rejected', 'Rejected works',
									$this->acl_check_view('Users_Controller', 'work'));
							break;
						case 3:
							$breadcrumbs->link('works/approved', 'Approved works',
									$this->acl_check_view('Users_Controller', 'work'));
							break;
					}
					
					$breadcrumbs->disable_translation()
							->link('works/show/' . $parent->id, 
									__('ID') . ' ' . $parent->id,
									$this->acl_check_view('Users_Controller', 'work', $parent->user->member_id))
							->enable_translation();
				}
				
				$breadcrumbs->text('Edit comment');
				
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
			$form_data = $form->as_array();

			$comment = new Comment_Model($comment_id);
			$comment->datetime = date('Y-m-d h:i:s');
			$comment->text = $form_data['text'];

			if ($comment->save())
			{
				status::success('Comment has been successfully updated');
			}

			url::redirect($link_back_url);
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

		switch ($comment->comments_thread->type)
		{
			// thread belongs to account
			case 'account':
				// access control
				if (!$this->acl_check_view('Members_Controller', 'comment', $parent->member_id))
					Controller::error(ACCESS);

				$link_back_url = 'transfers/show_by_account/' . $parent->id;
				break;
				
			case 'job':
				// access control
				if (!$this->acl_check_delete(get_class($this), 'works', $comment->user->member_id))
					Controller::error(ACCESS);
				
				$link_back_url = $this->redirect(Path::instance()->previous());
				
				break;
				
			default:
				Controller::error(RECORD);
				break;
		}

		if ($comment->delete())
		{
			status::success('Comment has been successfully deleted');
		}

		url::redirect($link_back_url);
	}

}
