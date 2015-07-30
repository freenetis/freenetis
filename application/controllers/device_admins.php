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
 * Handles grouping of devices and admins (user of system).
 * Admin is user who has remote access to this device.
 * 
 * @package	Controller
 */
class Device_admins_Controller extends Controller
{	
	/**
	 * Edits devices of which is user admin
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id 
	 */
	public function edit($device_id = NULL)
	{	
		// access control
		if (!$this->acl_check_new('Devices_Controller', 'admin'))
			Controller::error(ACCESS);

		// bad parameter
		if (!isset($device_id) || !is_numeric($device_id))
			Controller::warning(PARAMETER);

		// device doesn't exist
		$device = new Device_Model($device_id);
		
		// record doesn't exist
		if (!$device->id)
			Controller::error(RECORD);
		
		$user_model = new User_Model();
		$users = $user_model->select_list_grouped(FALSE);
		
		$sel_users = array();
		foreach ($device->device_admins as $device_admin)
			$sel_users[] = $device_admin->user->id;
		
		$form = new Forge();
		
		$form->dropdown('users[]')
				->label('Users')
				->options($users)
				->selected($sel_users)
				->multiple('multiple')
				->size(20);
		
		$form->submit('Update');
		
		// form is validate
		if ($form->validate())
		{
			$users = (isset($_POST['users'])) ? $_POST['users'] : array();
			
			$device_admin_model = new Device_admin_Model();
			
			$device_admin_model->transaction_start();
			
			try
			{
				// iterates all current devices of which is user admin
				foreach ($device->device_admins as $device_admin)
				{
					// device is still in list
					if (($pos = array_search($device_admin->user->id, $users)) !== FALSE)
						unset ($users[$pos]);
					// device is not in list - deletes it from list of devices of which is user admin
					else
						$device_admin->delete_throwable();
				}
			
				// adds devices to list of devices of which is user admin
				foreach ($users as $user)
				{
					$device_admin_model->clear();
					$device_admin_model->device_id = $device->id;
					$device_admin_model->user_id = $user;
					$device_admin_model->save_throwable();
				}
				
				$device_admin_model->transaction_commit();
				status::success('Device admin has been successfully updated.');
			}
			catch (Exception $e)
			{
				$device_admin_model->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cannot update device admin.');
			}
			
			url::redirect('devices/show/'.$device->id);
		}
	
		$headline = __('Edit admin of devices') . ': ' .$device->name;

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->link('members/show/' . $device->user->member->id,
						'ID ' . $device->user->member->id . ' - ' . $device->user->member->name,
						$this->acl_check_view('Members_Controller', 'members', $device->user->member->id))
				->link('users/show_by_member/' . $device->user->member_id, 'Users',
						$this->acl_check_view('Users_Controller', 'users', $device->user->member_id))
				->link('users/show/' . $device->user->id,
						$device->user->name . ' ' . $device->user->surname . ' (' . $device->user->login . ')',
						$this->acl_check_view('Users_Controller', 'users', $device->user->member_id))
				->link('devices/show_by_user/' . $device->user_id,'Devices',
						$this->acl_check_view('Devices_Controller', 'devices', $device->user->member_id))
				->link('devices/show/' . $device->id,
						($device->name != '' ? $device->name : $device->id),
						$this->acl_check_view('Devices_Controller', 'devices', $device->user->member_id))
				->text('Edit device admins');

		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form;
		$view->render(TRUE);
	}

	/**
	 * Edits devices of which is user admin
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id 
	 */
	public function edit_user($user_id = NULL)
	{	
		// access control
		if (!$this->acl_check_new('Devices_Controller', 'admin'))
			Controller::error(ACCESS);

		// bad parameter
		if (!isset($user_id) || !is_numeric($user_id))
			Controller::warning(PARAMETER);

		// user doesn't exist
		$user = new User_Model($user_id);
		
		// record doesn't exist
		if (!$user->id)
			Controller::error(RECORD);
		
		$device_model = new Device_Model();
		
		$devices = $device_model->select_list_device();
		
		$form = new Forge();
		
		$sel_devices = array();
		foreach ($user->device_admins as $device_admin)
			$sel_devices[] = $device_admin->device->id;
		
		$form->dropdown('devices[]')
				->label('Devices')
				->options($devices)
				->selected($sel_devices)
				->multiple('multiple')
				->size(20);
		
		$form->submit('Update');
		
		// form is validate
		if ($form->validate())
		{
			$devices = (isset($_POST['devices'])) ? $_POST['devices'] : array();
			
			$device_admin_model = new Device_admin_Model();
			
			try
			{
				$device_admin_model->transaction_start();
				
				// iterates all current devices of which is user admin
				foreach ($user->device_admins as $device_admin)
				{
					// device is still in list
					if (($pos = array_search($device_admin->device->id, $devices)) !== FALSE)
						unset ($devices[$pos]);
					// device is not in list - deletes it from list of devices of which is user admin
					else
						$device_admin->delete_throwable();
				}
			
				// adds devices to list of devices of which is user admin
				foreach ($devices as $device)
				{
					$device_admin_model->clear();
					$device_admin_model->device_id = $device;
					$device_admin_model->user_id = $user->id;
					$device_admin_model->save_throwable();
				}
				
				$device_admin_model->transaction_commit();
				status::success('Device admin has been successfully updated.');
			}
			catch (Exception $e)
			{
				$device_admin_model->transaction_rollback();
				Log::add_exception($e);
				status::error('Error - cannot update device admin.');
			}
			url::redirect(url_lang::base().'users/show/'.$user->id);
		}
	
		$headline = __('Edit admin of devices') . ': ' .
				__('' . $user->name . ' ' . $user->surname);

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
						$this->acl_check_view('Members_Controller', 'members'))
				->link('members/show/' . $user->member->id,
						'ID ' . $user->member->id . ' - ' . $user->member->name,
						$this->acl_check_view('Members_Controller', 'members', $user->member->id))
				->link('users/show_by_member/' . $user->member_id, 'Users',
						$this->acl_check_view('Users_Controller', 'users', $user->member_id))
				->link('users/show/' . $user->id,
						$user->name . ' ' . $user->surname . ' (' . $user->login . ')',
						$this->acl_check_view('Users_Controller', 'users', $user->member_id))
				->text('Edit admin of devices');

		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form;
		$view->render(TRUE);
	}

	/**
	 * Deletes device admin
	 * 
	 * @author Michal Kliment
	 * @param integer $device_admin_id 
	 */
	public function delete($device_admin_id = NULL)
	{
		// bad parameter
		if (!$device_admin_id || !is_numeric($device_admin_id))
			Controller::warning(PARAMETER);

		$device_admin = new Device_admin_Model($device_admin_id);

		// record doesn't exist
		if (!$device_admin->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_delete('Devices_Controller', 'admin'))
			Controller::error(ACCESS);

		// success
		if ($device_admin->delete())
		{
			status::success('Device admin has been successfully removed.');
		}

		url::redirect(Path::instance()->previous());
	}

}
