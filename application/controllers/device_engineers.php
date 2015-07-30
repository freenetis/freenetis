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
 * Handles grouping of devices and engineers (user of system).
 * Engineer is mostly user who connected device to network.
 * 
 * @package	Controller
 */
class Device_engineers_Controller extends Controller
{

	/**
	 * Adds engineer to device (creates relation between engineer and device).
	 * 
	 * @param integer $device_id
	 */
	public function add($device_id = null)
	{
		if (!$this->acl_check_new('Devices_Controller', 'engineer'))
			Controller::error(ACCESS);
		
		if (!isset($device_id))
			Controller::warning(PARAMETER);
		
		$device = new Device_Model($device_id);
		
		if ($device->id == 0)
			Controller::error(RECORD);

		$users = ORM::factory('user')->get_users_not_in_engineer_of($device_id);
		$arr_users = array();
		
		foreach ($users as $user)
		{
			$arr_users[$user->id] = $user->name;
		}
		
		$arr_users = array(
			NULL => '----- ' . __('select user') . ' -----'
		) + $arr_users;
		
		// form
		$form = new Forge('device_engineers/add/' . $device_id);
		
		$form->dropdown('user_id')
				->label(__('Engineer') . ':')
				->options($arr_users)
				->rules('required')
				->selected(0);
		
		$form->submit('Save');

		// validation
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$device_engineer = new Device_engineer_Model();
			$device_engineer->user_id = $form_data['user_id'];
			$device_engineer->device_id = $device_id;
			unset($form_data);
			
			if ($device_engineer->save())
			{
				status::success('New device engineer has been successfully saved.');
				url::redirect(url_lang::base() . 'devices/show/' . $device_id);
			}
		}
		
		$header = __('Add new device engineer');
		
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
				->text('Add new device engineer');
		
		$view = new View('main');
		$view->title = $header;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->form = $form->html();
		$view->content->headline = $header;
		$view->render(TRUE);
	}

	/**
	 * Removes engineer relation from device.
	 * 
	 * @param integer $id id of association
	 */
	public function delete($rel_id = null)
	{
		if (!$this->acl_check_delete('Devices_Controller', 'engineer'))
			Controller::error(ACCESS);
		
		if (!isset($rel_id) || !is_numeric($rel_id))
			Controller::warning(PARAMETER);
		
		$device_engineer_model = new Device_engineer_Model($rel_id);
		
		if ($device_engineer_model->id == 0)
			Controller::error(RECORD);
		
		$device_id = $device_engineer_model->device_id;
		$relations = $device_engineer_model->get_device_engineers($device_id);
		
		// first relation is for the first (main) engineer
		if ($rel_id == $relations->current()->id)
		{
			if (!$this->acl_check_delete('Devices_Controller', 'main_engineer'))
				Controller::error(ACCESS);
		}
		
		if ($device_engineer_model->delete())
		{
			status::success('Engineer has been successfully removed from this device.');
		}
		else
		{
			status::error('Error - it is not possible to remove engineer.');
		}
		
		url::redirect(Path::instance()->previous());
	}

}

