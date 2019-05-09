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
 * Controller performs actions over IP adresses of network.
 * Each IP address belongs to subnet and correspondes to subnet mask.
 *
 * @package Controller
 */
class Ip_addresses_Controller extends Controller
{
	/**
	 * Form of app
	 *
	 * @var Forge
	 */
	protected $form;
	
	/**
	 * IP address ID
	 *
	 * @var integer
	 */
	protected $ip_address_id;
	
	/**
	 * Constructor, only test if networks is enabled
	 */
	public function __construct()
	{
		parent::__construct();
		
		// access control
		if (!Settings::get('networks_enabled'))
			Controller::error (ACCESS);
	}
	
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('ip_addresses/show_all');
	}

	/**
	 * Shows all ip addresses.
	 * 
	 * @param integer $limit_results		ip addresses per page
	 * @param string $order_by				sorting column
	 * @param string $order_by_direction	sorting direction
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'ip_address',
			$order_by_direction = 'asc', $page_word = null, $page = 1)
	{
		if (!$this->acl_check_view('Ip_addresses_Controller', 'ip_address'))
			Controller::error(ACCESS);
		
		$ip_model = new Ip_address_Model();
				
		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		$filter_form = new Filter_form('ip');
		
		$filter_form->add('ip_address')
			->type('network_address')
			->class(array
			(
				Filter_form::OPER_IS => 'ip_address',
				Filter_form::OPER_IS_NOT => 'ip_address',
				Filter_form::OPER_NETWORK_IS_IN => 'cidr',
				Filter_form::OPER_NETWORK_IS_NOT_IN => 'cidr',
			));
		
		$filter_form->add('subnet_name')
			->type('combo')
			->callback('json/subnet_name');
		
		$filter_form->add('device_name')
			->callback('json/device_name');
		
		$filter_form->add('gateway')
			->type('select')
			->values(arr::rbool());
		
		$filter_form->add('service')
			->type('select')
			->values(arr::rbool());
		
		if (module::e('notification'))
		{
			$filter_form->add('whitelisted')
					->type('select')
					->label('Whitelist')
					->values(Ip_address_Model::get_whitelist_types());
		}
		
		
		$total_ip = $ip_model->count_all_ip_addresses($filter_form->as_sql());
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_ip)
			$sql_offset = 0;
		
		$query = $ip_model->get_all_ip_addresses(
				$sql_offset, $limit_results, $order_by,
				$order_by_direction, $filter_form->as_sql()
		);

		$grid = new Grid('ip_addresses', '', array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang').'/ip_addresses/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_ip,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		)); 

		if ($this->acl_check_new('Ip_addresses_Controller', 'ip_address'))
		{
			$grid->add_new_button('ip_addresses/add', __('Add new IP address'));
		}
		
		$grid->order_field('ip_address')
				->label('IP address');
		
		$grid->order_link_field('subnet_id')
				->link('subnets/show', 'subnet_name')
				->label('Subnet');
		
		$grid->order_callback_field('gateway')
				->label('Gateway')
				->help(help::hint('gateway'))
				->callback('boolean')
				->class('center');
		
		$grid->order_callback_field('service')
				->label('Service')
				->help(help::hint('service'))
				->callback('boolean')
				->class('center');
		
		if (module::e('notification'))
		{
			$grid->order_callback_field('whitelisted')
					->label('Whitelist')
					->help(help::hint('Whitelist'))
					->callback('whitelisted_field')
					->class('center');
		}
		
		$grid->order_callback_field('device_name')
				->label('Device name')
				->callback('callback::device_field');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Ip_addresses_Controller', 'ip_address'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('ip_addresses/show')
					->class('popup_link');
		}
		
		if ($this->acl_check_edit('Ip_addresses_Controller', 'ip_address'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('ip_addresses/edit')					
					->class('popup_link');
		}
		
		if ($this->acl_check_delete('Ip_addresses_Controller', 'ip_address'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('ip_addresses/delete')
					->class('delete_link');
		}
		
		$grid->datasource( $query ); 

		$view = new View('main');
		$view->title = __('IP addresses list');
		$view->breadcrumbs = __('IP addresses');
		$view->content = new View('show_all');
		$view->content->table = $grid;
		$view->content->headline = __('IP addresses list');
		$view->render(TRUE);
	} // end of show_all function
	

	
	/**
	 * Shows details of ip address.
	 * 
	 * @param integer $ip_address_id	id of ip address to show
	 */
	public function show($ip_address_id = null)
	{
		if (!isset($ip_address_id))
			Controller::warning(PARAMETER);
		
		$ip_address = new Ip_address_Model($ip_address_id);
		
		if (!$ip_address->id)
			Controller::error(RECORD);
		
		$member = $ip_address->iface->device->user->member;
		$member_id = $member->id;

		if (!$this->acl_check_view('Ip_addresses_Controller', 'ip_address', $member_id))
			Controller::error(ACCESS);
			 
		$device = $ip_address->iface->device; 	 
		$iface = $ip_address->iface;
				
		$iface_name = $iface->name;

		if (empty($iface_name))
		{
			$iface_name = $iface->mac;
		}
		else if (!empty($iface->mac))
		{
			$iface_name .= " (".$iface->mac.")";
		}
		
		if ($ip_address->member_id)
		{
			// breadcrumbs menu
			$breadcrumbs = breadcrumbs::add()
				->link('members/show_all', 'Members',
					$this->acl_check_view(
						'Members_Controller', 'members'))
				->disable_translation()
				->link('members/show/'.$ip_address->member_id,
					"ID ".$ip_address->member->id." - ".$ip_address->member->name,
						$this->acl_check_view(
							'Members_Controller', 'members', $ip_address->member_id));
		}
		else
		{
			if (url::slice(url_lang::uri(Path::instance()->previous()), 0, 1) != 'ip_addresses')
			{
				$iface = $ip_address->iface;
				$device_name = $iface->device->name;
					
				if ($device_name == '')
				{
					$device_name = ORM::factory('enum_type')
						->get_value($device->type);
				}

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
							'Devices_Controller', 'devices',
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
						$iface_name, $this->acl_check_view(
							'Ifaces_Controller', 'iface',
							$iface->device->user->member_id
						)
					);
			}
			else
			{
				// breadcrumbs menu
				$breadcrumbs = breadcrumbs::add()
					->link('ip_addresses/show_all', 'IP addresses',
							$this->acl_check_view('Ip_addresses_Controller', 'ip_address'));
			}
			
		}
		
		$breadcrumbs->disable_translation()
				->text($ip_address->ip_address . ' (' . $ip_address->id . ')');
		
		$view = new View('main');
		$view->title = __('IP address detail') . ' - ' . $ip_address->ip_address;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->action_logs = action_logs::object_last_modif($ip_address, $ip_address_id);
		$view->content = new View('ip_addresses/show');
		$view->content->ip_address = $ip_address;
		$view->content->member = $member; 	 
		$view->content->device = $device;
		$view->content->iface = $iface;
		$view->content->iface_name = $iface_name;
		$view->content->whitelist_types = Ip_address_Model::get_whitelist_types();
		$view->content->grid = '';
		$view->content->headline = __('IP address detail') . ' - '.$ip_address->ip_address;
		$view->render(TRUE);
	} // end of show function


	/**
	 * Adds new ip address to interface which may be selected in parameters.
	 * 
	 * @param integer $device_id
	 * @param integer $device_id
	 */
	public function add($device_id = NULL, $iface_id = NULL)
	{
		if (!$this->acl_check_new('Ip_addresses_Controller', 'ip_address'))
			Controller::error(ACCESS);
		
		$breadcrumbs = breadcrumbs::add();

		if ($device_id)
		{
			if (!is_numeric($device_id))
				Controller::warning(PARAMETER);

			$device = new Device_Model($device_id);

			if (!$device->id)
				Controller::error(RECORD);
			
			if (empty($iface_id))
			{
				$breadcrumbs->link(
							'devices/show_all', 'Devices',
							$this->acl_check_view('Devices_Controller', 'devices')
						)->link(
							'devices/show/' . $device->id, $device->name,
							$this->acl_check_view('Devices_Controller', 'devices')
						)->text('Add new IP address');
				
				$arr_ifaces = array
				(
					NULL => '----- '.__('Select interface').' -----'
				) + ORM::factory('iface')->select_list_grouped_by_device($device->id);
				
				$title = __('Add new IP address to device').' '.$device->name;
				$linkback = 'devices/show/'.$device->id;
			}
			else
			{			
				$iface = new Iface_Model($iface_id);
				
				if (!$iface->id)
					Controller::error(RECORD);
				
				$breadcrumbs->link(
							'ifaces/show_all', 'Interfaces',
							$this->acl_check_view('Ifaces_Controller', 'iface')
						)->link(
							'ifaces/show/' . $iface->id, strval($iface),
							$this->acl_check_view('Ifaces_Controller', 'iface')
						)->text('Add new IP address');
				
				$arr_ifaces = array
				(
					$iface->id => strval($iface)
				);
				
				$title = __('Add new IP address to interface').' '.strval($iface);
				$linkback = 'ifaces/show/'.$iface->id;
			}

		}
		else
		{
			$breadcrumbs->link(
						'ip_addresses/show_all', 'IP addresses',
						$this->acl_check_view('Ip_addresses_Controller', 'ip_address')
					)->text('Add new IP address');
			
			$arr_ifaces = array
			(
				NULL => '----- '.__('Select interface').' -----'
			) + ORM::factory('iface')->select_list_grouped_by_device();

			$title = __('Add new IP address');
			$linkback = 'ip_addresses/show_all';
		}

		$this->form = new Forge();
		
 		$arr_subnets = array
		(
			NULL => '----- '.__('select subnet').' -----'
		) + ORM::factory('subnet')->select_list_by_net();
		
		$this->form->dropdown('iface_id')
				->label('Interface name')
				->rules('required')
				->options($arr_ifaces)
				->style('width:520px')
				->callback(array($this, 'valid_mac_address_unique_in_subnet'));
		
		$this->form->input('ip_address')
				->label('IP address')
				->rules('required|valid_ip_address')
				->callback(array($this, 'valid_ip'))
				->style('width:200px');
		
		$this->form->dropdown('subnet_id')
				->label('Select subnet name')
				->options($arr_subnets)
				->rules('required')
				->add_button('subnets')
				->style('width:500px');
				
		$this->form->dropdown('gateway')
				->label(__('Gateway').':&nbsp;'.help::hint('gateway'))
				->options(arr::rbool())
				->selected('0')
				->callback(array($this, 'valid_gateway'));
		
		$this->form->dropdown('service')
				->label(__('Service').':&nbsp;'.help::hint('service'))
				->options(arr::rbool())
				->selected('0');
		
		$this->form->submit('Save');
		
		// validate form and save data 
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			$ip_address = new Ip_address_Model();

			// gets number of maximum of acceptable repeating of operation
			// after reaching of deadlock and time of waiting between
			// other attempt to make transaction (#254)
			$transaction_attempt_counter = 0;
			$max_attempts = max(1, abs(Settings::get('db_trans_deadlock_repeats_count')));
			$timeout = abs(Settings::get('db_trans_deadlock_repeats_timeout'));

			// try to delete
			while (TRUE)
			{
				try // try to make DB transction
				{
					$ip_address->transaction_start();
					
					$ip_address->delete_ip_address_with_member($form_data['ip_address']);
					
					$ip_address->iface_id = $form_data['iface_id'];
					$ip_address->ip_address = $form_data['ip_address'];
					$ip_address->subnet_id = $form_data['subnet_id'];
					$ip_address->gateway = $form_data['gateway'];
					$ip_address->service = $form_data['service'];
					$ip_address->member_id = NULL;
					$ip_address->save_throwable();
					
					// expired subnets (#465)
					ORM::factory('subnet')->set_expired_subnets($ip_address->subnet_id);
					
					$ip_address->transaction_commit();

					try
					{
						Allowed_subnets_Controller::update_enabled(
								$ip_address->iface->device->user->member->id,
								array($ip_address->subnet_id)
						);
					}
					catch (Exception $e)
					{
						status::warning('Error - cannot update allowed subnets of member.');
					}

					// redirect
					status::success('IP address is successfully saved.');
					$this->redirect($linkback);
				}
				catch (Exception $e) // failed => rollback and wait 100ms before next attempt
				{
					$ip_address->transaction_rollback();

					if (++$transaction_attempt_counter >= $max_attempts) // this was last attempt?
					{
						Log::add_exception($e);
						status::error('Error - cant add ip address.');
						$this->redirect($linkback);
					}

					usleep($timeout);
				}
			}
		}
		
		$view = new View('main');
		$view->title = $title;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->form = $this->form->html();
		$view->content->headline = $title;
		$view->render(TRUE);
	} // end of add function
	
	/**
	 * Edits ip address
	 * 
	 * @author Michal Kliment
	 * @param type $ip_address_id 
	 */
	public function edit($ip_address_id = NULL)
	{
		if (!$this->acl_check_edit('Ip_addresses_Controller', 'ip_address'))
			Controller::error(ACCESS);

		if (!is_numeric($ip_address_id))
			Controller::warning(PARAMETER);

		$ip_address = new Ip_address_Model($ip_address_id);

		if (!$ip_address->id)
			Controller::error(RECORD);
		
		$this->ip_address_id = $ip_address_id;
		
		$device = $ip_address->iface->device;
		
		$arr_ifaces = array
		(
			NULL => '----- '.__('Select interface').' -----'
		) + $ip_address->iface->select_list_grouped_by_device($device->id);
		
 		$arr_subnets = array
		(
			NULL => '----- '.__('Select subnet').' -----'
		) + ORM::factory('subnet')->select_list_by_net();
		
		$this->form = new Forge();
		
		$this->form->dropdown('iface_id')
				->label('Interface name')
				->rules('required')
				->options($arr_ifaces)
				->selected($ip_address->iface_id)
				->style('width:500px')
				->callback(array($this, 'valid_mac_address_unique_in_subnet'));
		
		$this->form->input('ip_address')
				->label('IP address')
				->rules('required|valid_ip_address')
				->value($ip_address->ip_address)
				->style('width:200px')
				->callback(array($this, 'valid_ip'));		
		
		$this->form->dropdown('subnet_id')
				->label('Select subnet name')
				->options($arr_subnets)
				->selected($ip_address->subnet_id)
				->rules('required')
				->style('width:500px')
				->add_button('subnets');
				
		$this->form->dropdown('gateway')
				->label(__('Gateway').':&nbsp;'.help::hint('gateway'))
				->options(arr::rbool())
				->selected($ip_address->gateway)
				->callback(array($this, 'valid_gateway'));
		
		$this->form->dropdown('service')
				->label(__('Service').':&nbsp;'.help::hint('service'))
				->options(arr::rbool())
				->selected($ip_address->service);
		
		$this->form->submit('Save');
		
		// validate form and save data 
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();
			
			// gets number of maximum of acceptable repeating of operation
			// after reaching of deadlock and time of waiting between
			// other attempt to make transaction (#254)
			$transaction_attempt_counter = 0;
			$max_attempts = max(1, abs(Settings::get('db_trans_deadlock_repeats_count')));
			$timeout = abs(Settings::get('db_trans_deadlock_repeats_timeout'));

			// try to delete
			while (TRUE)
			{
				try // try to make DB transction
				{
					$ip_address->transaction_start();

					$ip_address->delete_ip_address_with_member($form_data['ip_address']);

					$old_subnet_id = $ip_address->subnet_id;

					$ip_address->iface_id = $form_data['iface_id'];
					$ip_address->ip_address = $form_data['ip_address'];
					$ip_address->subnet_id = $form_data['subnet_id'];
					$ip_address->gateway = $form_data['gateway'];
					$ip_address->service = $form_data['service'];
					$ip_address->member_id = NULL;

					$ip_address->save_throwable();

					// expired subnets (#465)
					ORM::factory('subnet')->set_expired_subnets($ip_address->subnet_id);
					
					$ip_address->transaction_commit();

					$member_id = $device->user->member_id;

					// this block of code cannot be in database transation otherwise
					// you will get error
					// subnet has been changed and ip address was the only one of this member
					// from this subnet -> deletes subnet from allowed subnets of member
					if ($old_subnet_id != $ip_address->subnet_id &&
						!$ip_address->count_all_ip_addresses_by_member_and_subnet(
								$member_id, $old_subnet_id
						))
					{
						try
						{
							Allowed_subnets_Controller::update_enabled(
									$member_id, NULL, NULL,
									array($old_subnet_id), TRUE
							);
						}
						catch (Exception $e)
						{
							status::warning('Error - cannot update allowed subnets of member.');
						}
					}

					try
					{
						Allowed_subnets_Controller::update_enabled(
								$member_id, array($ip_address->subnet_id), array(),
								array(), TRUE
						);
					}
					catch (Exception $e)
					{
						status::warning('Error - cannot update allowed subnets of member.');
					}

					status::success('IP address has been successfully updated.');
					$this->redirect('ip_addresses/show/'.$ip_address->id);
				}
				catch (Exception $e) // failed => rollback and wait 100ms before next attempt
				{
					$ip_address->transaction_rollback();
					
					if (++$transaction_attempt_counter >= $max_attempts) // this was last attempt?
					{
						Log::add_exception($e);
						status::error('Error - Cannot update ip address.');
						$this->redirect('ip_addresses/show/'.$ip_address->id);
					}
				
					usleep($timeout);
				}
			}
		}
		else
		{
			$title = __('Edit IP address');

			// breadcrumbs menu
			$breadcrumbs = breadcrumbs::add()
					->link('ip_addresses/show_all', 'IP addresses',
							$this->acl_check_view('Ip_addresses_Controller', 'ip_address'))
					->disable_translation()
					->text($title);

			$view = new View('main');
			$view->title = $title;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->form = $this->form->html();
			$view->content->headline = $title;
			$view->render(TRUE);
		}
	}
	
	/**
	 * Function deletes given ip address.
	 * 
	 * @param integer $ip_address_id
	 */
	public function delete($ip_address_id = NULL)
	{
		if (!isset($ip_address_id))
		{
			Controller::warning(PARAMETER);
		}
		
		$ip_address = new Ip_address_Model($ip_address_id);

		if (!$ip_address->id)
		{
			Controller::error(RECORD);
		}
		
		$member_id = $ip_address->iface->device->user->member_id;
		$subnet_id = $ip_address->subnet_id;

		if (!$this->acl_check_delete('Ip_addresses_Controller', 'ip_address', $member_id))
		{
			Controller::error(ACCESS);
		}
		
		// link back
		$linkback = Path::instance()->previous();
		
		if (url::slice(url_lang::uri($linkback), 0, 2) == 'ip_addresses/show')
		{
			$linkback = 'ip_addresses/show_all';
		}
		
		// gets number of maximum of acceptable repeating of operation
		// after reaching of deadlock and time of waiting between
		// other attempt to make transaction (#254)
		$transaction_attempt_counter = 0;
		$max_attempts = max(1, abs(Settings::get('db_trans_deadlock_repeats_count')));
		$timeout = abs(Settings::get('db_trans_deadlock_repeats_timeout'));
		
		// try to delete
		while (TRUE)
		{
			try // try to make DB transction
			{
				$ip_address->transaction_start();
				
				// expired subnets (#465)
				ORM::factory('subnet')->set_expired_subnets($ip_address->subnet_id);
				
				if ($ip_address->subnet->subnets_owner->id)
				{
					$ip_address->member_id = $ip_address->subnet->subnets_owner->member->id;
					$ip_address->iface_id = NULL;
					
					$ip_address->save_throwable();
				}
				else
				{
					$ip_address->delete_throwable();
				}
				
				$ip_address->transaction_commit();
				
				// ip address was the only one of this member
				// from this subnet -> deletes subnet from allowed subnets of member
				if (!$ip_address->count_all_ip_addresses_by_member_and_subnet(
						$member_id, $subnet_id
					))
				{
					try
					{
						Allowed_subnets_Controller::update_enabled(
								$member_id, NULL, NULL, array($subnet_id), TRUE
						);
					}
					catch (Exception $e)
					{
						status::warning('Error - cannot update allowed subnets of member.');
					}
				}
				
				// redirect
				status::success('IP address has been successfully deleted.');
				$this->redirect($linkback);
			}
			catch (Exception $e) // failed => rollback and wait 100ms before next attempt
			{
				$ip_address->transaction_rollback();
				
				if (++$transaction_attempt_counter >= $max_attempts) // this was last attempt?
				{
					Log::add_exception($e);
					status::error('Error - cant delete ip address.');
					$this->redirect($linkback);
				}
				
				usleep($timeout);
			}
		}
	}

	/**
	 * Checks validity of ip address.
	 * 
	 * @param object $input
	 */
	public function valid_ip($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}

		$ip = ip2long($this->input->post('ip_address')); // Submitted values;


		if ($ip === FALSE || $ip == -1)
		{	 // invalid IP adress
			$input->add_error('required', __('Invalid IP address'));
			return false;
		}

		$subnet_id = $this->input->post('subnet_id');
		$subnet = new Subnet_Model($subnet_id);

		if ($subnet && $subnet->id)
		{
			$net	= ip2long($subnet->network_address);
			$mask	= ip2long($subnet->netmask);
			$size	= (~$mask & 0xffffffff)+1;
			
			if (($ip & $mask) != $net)
			{
				$input->add_error('required', __(
						'IP address does not match the subnet/mask.'
				));
			}
			else if ($size > 2 && ($ip == $net || $ip == ($net + $size - 1)))
			{
				$input->add_error('required', __('Invalid IP address'));
			}
		}
		else
		{
			$input->add_error('required', __('Invalid subnet'));
		}

		// checks if exists this ip
		$ip_model = new ip_address_Model();

		$ips = $ip_model->where(array
		(
				'ip_address'	=> $input->value,
				'member_id'		=> NULL
		))->find_all();

		foreach ($ips as $ip)
		{
			// only for edit: check if ip_address is not still same 
			if ($this->ip_address_id != $ip->id)
			{
				$input->add_error('required', __('IP address already exists.'));
			}
		}
	} // end of valid_ip
	
	/**
	 * Callback function to validate if MAC address is unique in subnet
	 * 
	 * @author Ondrej Fibich
	 * @param Form_Field $input 
	 */
	public function valid_mac_address_unique_in_subnet($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if ($this->input->post('subnet_id') && $input->value)
		{
			$subnet = new Subnet_Model($this->input->post('subnet_id'));
			$iface = new Iface_Model($input->value);
			
			if ($subnet->id && $iface->id)
			{
				if (!$subnet->is_mac_unique_in_subnet($iface->mac, $this->ip_address_id))
				{
					$link = html::anchor('/subnets/show/' . $subnet->id, __('subnet', NULL, 1), 'target="_blank"');
					$m = 'MAC address of this interface is already in the '
						. 'selected %s assigned to another interface';
					$input->add_error('required', str_replace('"', '\'', __($m, $link)) . '!');
				}
				return;
			}
		}
		
		$input->add_error('required', __('Subnet or iface not set.'));
	}
	
	/**
	 * Callback function to validate if subnet has gateway
	 * 
	 * @author Michal Kliment <kliment@freenetis.org>
	 * @param type $input
	 */
	public function valid_gateway($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$subnet_id = (int) $this->input->post('subnet_id');
		
		if ($input->value && $subnet_id)
		{
			$subnet = new Subnet_Model($subnet_id);
			
			// find gateway of subnet
			$gateway = $subnet->get_gateway();
			
			// gateway already exists
			if ($gateway && $gateway->id && $this->ip_address_id != $gateway->id)
			{
				$input->add_error('required', __('Subnet has already have gateway.'));
			}
		}
	}
}
