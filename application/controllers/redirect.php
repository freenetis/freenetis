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
 * Handles redirection of members for certain reasons.
 * 
 * @author Jiri Svitak
 * @package Controller
 */
class Redirect_Controller extends Controller
{
	/**
	 * Constructor, only test if redirection is enabled
	 */
	public function __construct()
	{
		parent::__construct();
		
		// access control
		if (!Settings::get('redirection_enabled'))
			Controller::error (ACCESS);
	}
	
	/**
	 * Function redirects to show_all function.
	 */
	public function index()
	{
		url::redirect('redirect/show_all');
	}
	
	/**
	 * Shows all activated redirections.
	 * 
	 * @author Jiri Svitak
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'ip_address',
			$order_by_direction = 'desc', $page_word = null, $page = 1)
	{
		// access rights
		if (!$this->acl_check_view('Redirect_Controller', 'redirect'))
			Controller::error(ACCESS);
			
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		$allowed_order_type = array('ip_address', 'name', 'datetime', 'member_id');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'ip_address';
		
		if (strtolower($order_by_direction) != 'asc')
		{
			$order_by_direction = 'desc';
		}
		
		$filter_form = new Filter_form('mip');
		
		$filter_form->add('ip_address')
			->type('network_address')
			->class(array
			(
				Filter_form::OPER_IS => 'ip_address',
				Filter_form::OPER_IS_NOT => 'ip_address',
				Filter_form::OPER_NETWORK_IS_IN => 'cidr',
				Filter_form::OPER_NETWORK_IS_NOT_IN => 'cidr',
			));
		
		$filter_form->add('member_name')
			->type('combo')
			->callback('json/member_name');
		
		$filter_form->add('type')
			->type('select')
			->values(array
			(
				Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE => __('Membership interrupt'),
				Message_Model::DEBTOR_MESSAGE => __('Debtor'),
				Message_Model::BIG_DEBTOR_MESSAGE => __('Big debtor'),
				Message_Model::PAYMENT_NOTICE_MESSAGE => __('Payment notice'),
				Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE => __('Unallowed connecting place'),
				Message_Model::CONNECTION_TEST_EXPIRED => __('Connection test expired'),
				Message_Model::USER_MESSAGE => __('User message')
			));
		
		$filter_form->add('datetime')
			->type('date')
			->label(__('Date and time'));
		
		$filter_form->add('comment');

		// model
		$message_model = new Message_Model();			
		$total_redirections = $message_model->count_all_redirections($filter_form->as_sql());
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_redirections)
			$sql_offset = 0;
		
		$redirections = $message_model->get_all_redirections(
				$sql_offset, $limit_results, $order_by, $order_by_direction,
				$filter_form->as_sql()
		);
		
		$headline = __('Activated redirections');
		
		$grid = new Grid('redirect/show_all', null, array
		(
			'current'	    		=> $limit_results,
			'selector_increace'    	=> 50,
			'selector_min' 			=> 50,
			'selector_max_multiplier'=> 10,
			'base_url'    			=> Config::get('lang').'/redirect/show_all/'
									. $limit_results.'/'.$order_by.'/'.$order_by_direction,
			'uri_segment'    		=> 'page',
			'total_items'    		=> $total_redirections,
			'items_per_page' 		=> $limit_results,
			'style'          		=> 'classic',
			'order_by'				=> $order_by,
			'order_by_direction'	=> $order_by_direction,
			'limit_results'			=> $limit_results,
			//'url_array_ofset'		=> 1,
			'filter'				=> $filter_form
		));

		$grid->order_callback_field('ip_address')
				->label('IP address')
				->callback('callback::ip_address_field');
		
		$grid->order_callback_field('member_id')
				->label('Member')
				->callback('callback::member_field');
		
		$grid->order_callback_field('message')
				->label(__('Activated redirection').'&nbsp;'.help::hint('activated_redirection'))
				->callback('callback::message_field');
		
		$grid->order_field('datetime')
				->label('Date and time');
		
		$grid->order_field('comment');
		
		$grid->callback_field('ip_address')
				->label(__('Preview').'&nbsp;'.help::hint('redirection_preview'))
				->callback('callback::redirection_preview_field');
		
		$grid->datasource($redirections);
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $headline;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);	
	}
	
	/**
	 * Adds IP address (or including IP addreses of member, or including IP addresses in subnet)
	 * to junction table for redirection.
	 * 
	 * @param integer $ip_address_id
	 */
	public function activate_to_ip_address($ip_address_id = null)
	{
		// access rights 
		if (!$this->acl_check_edit('Redirect_Controller', 'redirect'))
			Controller::error(ACCESS);
		
		if (!isset($ip_address_id))
			Controller::warning(PARAMETER);
		
		// ip address
		$ip = new Ip_address_Model($ip_address_id);
		
		if (!$ip->id)
			Controller::error(RECORD);
		
		// load list of usable redirection message
		$message_model = new Message_Model();
		$messages = $message_model->find_all();
		
		foreach($messages as $message)
		{
			// IP address can be manually redirected only to user message, 
			// interrupted membership message, debtor message, payment notice message
			if (Message_Model::can_be_activate_directly($message->type) &&
				trim($message->text) != '')
			{
				$message_array[$message->id] = __(''.$message->name);
			}	
		}
		// no redirection possible for ip address?
		if (empty($message_array))
		{
			status::warning('No redirection is possible to set for this IP address.');
			url::redirect('ip_addresses/show/'.$ip_address_id);
		}
		// form
		$form = new Forge('redirect/activate_to_ip_address/'.$ip_address_id);
		
		$form->group('Redirection of IP address');
		
		$form->dropdown('message_id')
				->label('Redirection')
				->options($message_array);
		
		$form->textarea('comment')
				->label('Comment of admin shown to user');
		
		$form->submit('Activate');
		
		// validation
		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);
			$db = Database::instance();
			// delete old redirection if present
			$db->delete('messages_ip_addresses', array
			(
				'message_id' => $form_data['message_id'],
				'ip_address_id' => $ip->id
			));
			// database insert sets redirection for ip address
			$db->insert('messages_ip_addresses', array
			(
				'message_id'	=> $form_data['message_id'],
				'ip_address_id'	=> $ip->id,
				'user_id'		=> $this->session->get('user_id'),
				'comment'		=> $form_data['comment'],
				'datetime'		=> date('Y-m-d H:i:s')
			));
			// set flash message
			status::success('Redirection has been successfully set.');
			
			$this->redirect(Path::instance()->previous());
		}
		
		if (Path::instance()->previous(0,1) == 'devices')
		{					
			$device_name = $ip->iface->device->name;
					
			if ($device_name == '')
			{
				$device_name = ORM::factory('enum_type')->get_value($device->type);
			}
					
			if ($ip->iface->name != '')
				$iface_name = $ip->iface->name." (".$ip->iface->mac.")";
			else
				$iface_name = $ip->iface->mac;

			// breadcrumbs menu
			$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
					$this->acl_check_view('Members_Controller','members'))
				->disable_translation()
				->link('members/show/' .
					$iface->device->user->member->id,
					'ID ' . $iface->device->user->member->id .
					' - ' . $iface->device->user->member->name,
					$this->acl_check_view(
						'Members_Controller',
						'members',
						$iface->device->user->member->id)
					)
				->enable_translation()
				->link('users/show_by_member/' .
					$iface->device->user->member->id, 'Users',
					$this->acl_check_view(
						'Users_Controller', 'users',
						$iface->device->user->member->id)
					)
				->disable_translation()
				->link('users/show/' . $iface->device->user->id, 
					$iface->device->user->name . 
					' ' . $iface->device->user->surname .
					' (' . $iface->device->user->login . ')',
					$this->acl_check_view(
						'Users_Controller', 'users',
						$iface->device->user->member_id)
					)
				->enable_translation()
				->link(
					'devices/show_by_user/'.$iface->device->user_id,
					'Devices', $this->acl_check_view(
						'Devices_Controller',
						'devices',
						$iface->device->user->member_id
					)
				)
				->link(
					'devices/show/'.$iface->device_id,
					$device_name, $this->acl_check_view(
						'Devices_Controller', 'devices',
						$iface->device->user->member_id
					)
				)
				->link(
					'ifaces/show/'.$iface->id,
					$iface_name,
					$this->acl_check_view(
						'Ifaces_Controller',
						'iface',
						$iface->device->user->member_id
					)
				)
				->disable_translation()
				->link(
					'ip_addresses/show/'.$ip->id,
					$ip->ip_address,
					$this->acl_check_view(
						'Ip_addresses_Controller',
						'ip_address',
						$iface->device->user->member_id
					)
				);
		}
		else
		{
		
			$breadcrumbs = breadcrumbs::add()
				->link('ip_addresses/show_all', 'IP addresses',
						$this->acl_check_view('Ip_addresses_Controller', 'ip_address')
				)->disable_translation()
				->link('ip_addresses/show/' . $ip->id,
						$ip->ip_address . ' (' . $ip->id . ')',
						$this->acl_check_view('Ip_addresses_Controller', 'ip_address')
				);
		}
			
		$breadcrumbs->enable_translation()->text('Redirection of IP address');
		
		// view
		$headline = __('Redirection of IP address');
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}
	
	/**
	 * Deletes information from junction table for redirections.
	 * 
	 * @param integer $ip_address_id	IP address to cancel redirection
	 * @param integer  $message_id		Message to cancel redirection 
	 *									(multiple redirections for one IP address are possible)
	 * @param string $from				Tells where return after setting this redirection
	 */
	public function delete(
			$ip_address_id = null, $message_id = null, $from = 'ip_address')
	{
		if (!$this->acl_check_delete('Redirect_Controller', 'redirect'))
			Controller::error(ACCESS);
		
		if (!isset($ip_address_id) || !isset($message_id))
			Controller::warning(PARAMETER);
		
		if (is_numeric($ip_address_id))
			$array['ip_address_id'] = $ip_address_id;
		
		if (is_numeric($message_id))
			$array['message_id'] = $message_id;
		
		if (isset($array) && !empty($array))
			Database::instance()->delete('messages_ip_addresses', $array);
		
		$ip = new Ip_address_Model($ip_address_id);
		
		status::success('Redirection has been successfully canceled.');
		
		if ($from == 'member')
		{
			if ($ip->iface_id)
			{
				$member_id = $ip->iface->device->user->member_id;
			}
			else
			{
				$member_id = $ip->member_id;
			}
			
			url::redirect('members/show/'.$member_id);			
		}
		else
		{
			url::redirect('ip_addresses/show/'.$ip_address_id);		
		}
	}
	
	/**
	 * Adds all IP addresses of member to redirection.
	 * 
	 * @author Jiri Svitak
	 * @param integer $member_id
	 */
	public function activate_to_member($member_id = null)
	{
		// access rights 
		if (!$this->acl_check_edit('Redirect_Controller', 'redirect'))
			Controller::error(ACCESS);
		
		if (!isset($member_id))
			Controller::warning(PARAMETER);
		
		// member
		$member = new Member_Model($member_id);
		
		if (!$member->id)
			Controller::error(RECORD);
		
		// load list of IP addresses belonging to member	
		$ip_model = new Ip_address_Model();
		$ips = $ip_model->get_ip_addresses_of_member($member_id);	
		// load list of usable redirection message
		$message_model = new Message_Model();
		$messages = $message_model->find_all();
		foreach($ips as $ip)
		{
			foreach($messages as $message)
			{
				// IP address can be manually redirected only to user message, 
				// interrupted membership message, debtor message, payment notice message
				if (Message_Model::can_be_activate_directly($message->type) &&
					trim($message->text) != '')
				{
					$message_array[$message->id] = __(''.$message->name);
				}	
			}
		}
		// no redirection possible for this member?
		if (empty($message_array))
		{
			status::warning('No redirection is possible to set for this IP address.');
			url::redirect('members/show/'.$member_id);
		}
		// form
		$form = new Forge('redirect/activate_to_member/'.$member_id);
		
		$form->group('Redirection');
		
		$form->dropdown('message_id')
				->label('Message')
				->options($message_array);
		
		$form->textarea('comment')
				->label('Comment of admin shown to user');
		
		$form->submit('Activate');
		
		// validation
		if ($form->validate())
		{
			$form_data = $form->as_array(FALSE);
			$db = Database::instance();
			
			foreach($ips as $ip)
			{
				// delete old redirection if present
				$db->delete('messages_ip_addresses', array
				(
					'message_id'	=> $form_data['message_id'],
					'ip_address_id'	=> $ip->id
				));
				// database insert sets redirection for ip address
				$db->insert('messages_ip_addresses', array
				(
					'message_id'	=> $form_data['message_id'],
					'ip_address_id'	=> $ip->id,
					'user_id'		=> $this->session->get('user_id'),
					'comment'		=> $form_data['comment'],
					'datetime'		=> date('Y-m-d H:i:s')
				));
			}
			// set flash message
			status::success('Redirection has been successfully set.');
			url::redirect('members/show/'.$member_id);
		}
		else
		{
			$headline = __('Activate redirection to member');
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('members/show_all', 'Members',
							$this->acl_check_view('Members_Controller', 'members'))
					->disable_translation()
					->link('members/show/'.$member->id,
							"ID $member->id - $member->name",
							$this->acl_check_view(
									'Members_Controller' ,'members', $member->id
							)
					)
					->text($headline);
			// view
			$view = new View('main');
			$view->title = $headline;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = $headline;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}	
	}
	
	/**
	 * Cancel given redirection to all IP addresses of given member.
	 * 
	 * @author Jiri Svitak
	 * @param integer $member_id
	 * @param integer $message_id
	 */
	public function delete_from_member($member_id = null, $message_id = null)
	{
		// access rights
		if (!$this->acl_check_delete('Redirect_Controller', 'redirect'))
			Controller::error(ACCESS);
		
		if (!isset($member_id) || !isset($message_id))
			Controller::warning(PARAMETER);
		
		$db = Database::instance();
		$ip_model = new Ip_address_Model();
		$ips = $ip_model->get_ip_addresses_of_member($member_id);
		
		foreach($ips as $ip)
		{
			$db->delete('messages_ip_addresses', array
			(
				'message_id'	=> $message_id,
				'ip_address_id'	=> $ip->id
			));			
		}
		// set flash message
		status::success('Redirection has been successfully canceled.');
		url::redirect('members/show/'.$member_id);
	}
	
	/**
	 * Function returns redirection logo
	 * 
	 * @author David Raska
	 */
	public function logo()
	{		
		$logo = Settings::get('redirection_logo_url');
		
		if (!empty($logo) && file_exists($logo))
		{
			download::force($logo);
		}
		else
		{
			download::force(server::base_dir().'/media/images/layout/logo_freenetis.jpg');
		}
	}
}
