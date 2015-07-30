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
 * Controller performs actions over subnets.
 * Subnet is small district or route with defined network address and mask.
 * 
 * @package Controller
 */
class Subnets_Controller extends Controller
{
	private $subnet_id = NULL;
	
	/**
	 * Redirects to show all
	 */
	public function index()
	{
		url::redirect('subnets/show_all');
	}

	/**
	 * Function shows all subnets.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param intereg $page
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'cidr',
			$order_by_direction = 'ASC', $page_word = null, $page = 1)
	{
		// access check
		if (!$this->acl_check_view('Devices_Controller', 'subnet'))
		{
			Controller::error(ACCESS);
		}
		
		$filter_form = new Filter_form('s');
		
		$filter_form->add('subnet_name')
			->callback('json/subnet_name');
		
		$filter_form->add('network_address')
			->type('network_address')
			->class(array
			(
				Filter_form::OPER_IS => 'ip_address',
				Filter_form::OPER_IS_NOT => 'ip_address',
				Filter_form::OPER_NETWORK_IS_IN => 'cidr',
				Filter_form::OPER_NETWORK_IS_NOT_IN => 'cidr',
			));
		
		$filter_form->add('redirect')
			->type('select')
			->label('Redirection')
			->values(arr::bool());
		
		$filter_form->add('member_name')
			->label('Owner')
			->type('combo')
			->callback('json/member_name');
		
		$filter_form->add('used')
			->type('number');
		
		// get new selector
		if (is_numeric($this->input->get('record_per_page')))
		{
			$limit_results = (int) $this->input->get('record_per_page');
		}
		
		// get count of records
		$subnet_model = new Subnet_Model();
		$total_subnets = $subnet_model->count_all_subnets($filter_form->as_sql());	
		
		// offset check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_subnets)
			$sql_offset = 0;
		
		// get records
		$query = $subnet_model->get_all_subnets(
				$sql_offset, (int) $limit_results, $order_by,
				$order_by_direction, $filter_form->as_sql()
		);

		// grid
		$grid = new Grid(url_lang::base().'devices', null, array(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 20,
			'base_url'					=> Config::get('lang').'/subnets/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=>  $total_subnets,
			'items_per_page' 			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		)); 

		if ($this->acl_check_new('Devices_Controller', 'subnet'))
		{
			$grid->add_new_button('subnets/add', __('Add new subnet'),
			array
			(
				'title' => __('Add new subnet'),
				'class' => 'popup_link'
			));
		}
		
		if ($this->acl_check_view('Devices_Controller', 'subnet'))
		{
			$grid->add_new_button('subnets/address_map/'.server::query_string(),
				__('Address map'),
				array
				(
					'title' => __('Address map'),
				),
				help::hint('address_map')
			);
		}
		
		$grid->order_field('id')
				->label(__('ID'));
		
		$grid->order_field('subnet_name')
				->label('Subnet');
		
		$grid->order_callback_field('cidr')
				->label(__('Network address'))
				->callback('callback::cidr_field');
		
		$grid->order_callback_field('member_id')
				->label(__('Owner').' '.help::hint('subnet_owner'))
				->callback('callback::member_field');
		
		$grid->order_field('redirect')
				->label(__('Redir'))
				->bool(arr::rbool())
				->class('center');
		
		$grid->order_callback_field('used')
				->label(__('Used'))
				->callback('callback::used_field')
				->class('center')
				->help(help::hint('subnet_used_ips'));
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Devices_Controller', 'subnet'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('subnets/show');
		}
		
		if ($this->acl_check_edit('Devices_Controller', 'subnet'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('subnets/edit')
					->class('popup_link');
		}
		
		if ($this->acl_check_delete('Devices_Controller', 'subnet'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('subnets/delete')
					->class('delete_link');
		}
		
		// load data
		$grid->datasource( $query ); 
		
		// view
		$view = new View('main');
		$view->breadcrumbs = __('Subnets');
		$view->title = $headline = __('Subnets list');
	 	$view->content = new View('show_all');
	   	$view->content->table = $grid;
        $view->content->headline = $headline;
        $view->render(TRUE);
	} // end of show all
	
	/**
	 * Function shows subnet.
	 * 
	 * @param integer $subnet_id
	 */
	public function show($subnet_id = NULL)
	{
		// param check
		if (!isset($subnet_id) || !is_numeric($subnet_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// access check
		if (!$this->acl_check_view('Devices_Controller', 'subnet'))
		{
			Controller::error(ACCESS);
		}
		
		// laod model
		$subnet = new Subnet_Model($subnet_id);
		
		// correct data?
		if (!$subnet->id)
		{
			Controller::error(RECORD);
		}
		
		// clouds of subnet
		$clouds = $subnet->get_clouds_of_subnet($subnet_id);
			
		// ip addresses of subnet
		$ip_model = new Ip_address_Model();
		$ips = $ip_model->get_ip_addresses_of_subnet($subnet_id);

		$total_available = (~ip2long($subnet->netmask) & 0xffffffff)-1;

		$used = round(count($ips)/$total_available*100,1);
		
		$network = ip2long($subnet->network_address);

		$ip_addresses = array();

		for ($i=1; $i <= $total_available; $i++)
		{
			$ip_addresses[$i] = new stdClass();
			$ip_addresses[$i]->ip_address_id = NULL;
			$ip_addresses[$i]->device_id = NULL;
			$ip_addresses[$i]->device_name = NULL;
			$ip_addresses[$i]->member_id = NULL;
			$ip_addresses[$i]->ip_address = long2ip($network+$i);
		}

		foreach ($ips as $ip)
		{
			$ip_addresses[ip2long($ip->ip_address)-$network] = $ip;
		}

		// grid
		$grid = new Grid('subnets/show/'.$subnet_id, __('IP addresses'), array
		(
				'use_paginator' => false,
				'use_selector'	=> false
		));
		
 		$grid->field('ip_address_id')
				->label(__('ID'));
		
		$grid->callback_field('ip_address')
				->label(__('IP address'))
				->callback('callback::ip_address_field');
		
		$grid->callback_field('device_name')
				->label(__('Device'))
				->callback('callback::device_field');
		
		$callback_link = '';
		
		if ($subnet->subnets_owner->member->id)
		{
			$callback_link = html::anchor(
					'members/show/'.$subnet->subnets_owner->member->id,
					$subnet->subnets_owner->member->name
			);
		}
		
		$grid->callback_field('member_name')
				->label(__('Member'))
				->callback('callback::member_field', $callback_link);
		
		// load datasource
		$grid->datasource($ip_addresses);

		// bread crumbs
		$breadcrumbs[] = ($this->acl_check_view('Devices_Controller','subnet')) ?
				html::anchor('subnets/show_all',__('Subnets')) : __('Subnets');
		$breadcrumbs[] = $subnet->name." ($subnet->network_address/"
						. network::netmask2cidr($subnet->netmask) .")";
	
		// view
		$view = new View('main');
		$view->breadcrumbs = implode(' » ',$breadcrumbs);
		$view->title = $headline = __('Subnet').' '.$subnet->name;
		$view->content = new View('subnets/show');
		$view->content->subnet = $subnet;
		$view->content->owner_id = $subnet->subnets_owner->member->id;
		$view->content->owner = $subnet->subnets_owner->member->name;
		$view->content->clouds = $clouds;
		$view->content->grid = $grid;
		$view->content->headline = $headline;
		$view->content->total_available = $total_available;
		$view->content->total_used = count($ips);
		$view->content->used = $used;
		$view->render(TRUE);
	} // end of show

	/**
	 * Function adds subnet.
	 * 
	 * @param integer $cloud_id
	 */
	public function add($cloud_id = null) 
	{		
		// access check
		if (!$this->acl_check_new('Devices_Controller', 'subnet'))
		{
			Controller::error(ACCESS);
		}
		
		// model
		$subnet_model = new Subnet_Model();

		$arr_members = arr::merge(
				array(NULL => '----- '.__('Select member').' -----'),
				arr::from_objects(ORM::factory('member')->get_all_members_to_dropdown())
		);

		$form = new Forge();
		
        $form->group('Basic data');
		
		$form->input('name')
				->label(__('Subnet name').':')
				->rules('required|length[3,250]');
		
        $form->input('network_address')
				->label(__('Network address').':')
				->rules('required|valid_ip_address')
				->callback(array($this, 'valid_netip'));
		
        $form->input('netmask')
				->label(__('Netmask').':')
				->rules('required|valid_ip_address');
		
        $form->input('OSPF_area_id')
				->label(__('OSPF area ID').':')
				->rules('valid_digit');
		
        if ($this->acl_check_new('Devices_Controller', 'redirect'))
		{
        	$form->dropdown('redirect')
					->label(__('Redirection enabled'))
					->options(arr::rbool())
					->selected(0);
		}

		$form->dropdown('owner_id')
				->label(__('Owner').' '.help::hint('subnet_owner'))
				->options($arr_members);

		// add cloud to subnet
		$cloud = new Cloud_Model();
		
		// add from cloud
		if (isset($cloud_id))
		{
			$cloud->find($cloud_id);
			
			if (!$cloud->id)
			{
				Controller::error(RECORD);
			}
			
			$form->dropdown('cloud')
					->label(__('Cloud'))
					->options(array($cloud_id => $cloud->name))
					->rules('required');
		}
		else
		{
			$first = array('0' => '----- '.__('Select area').' -----');
			$arr_clouds = $first + $cloud->select_list('id', 'name');
			
			$form->dropdown('cloud')
					->label(__('Cloud'))
					->options($arr_clouds);
		}

        $form->submit('Save');
		
		// validation
		if($form->validate())
		{
			$form_data = $form->as_array();
			
			try
			{
				// model
				$subnet_model = new Subnet_Model();
				// transaction start
				$subnet_model->transaction_start();
				
				// add subnet
				$subnet_model->name = $form_data['name'];
				$subnet_model->network_address = $form_data['network_address'];
				$subnet_model->netmask = $form_data['netmask'];
				$subnet_model->OSPF_area_id = $form_data['OSPF_area_id'];

				// redirect
				if ($this->acl_check_new('Devices_Controller', 'redirect'))
				{
					$subnet_model->redirect = $form_data['redirect'];
				}

				// save
				$subnet_model->save_throwable();
				
				// subnet owner
				if ($form_data['owner_id'])
				{
					$subnets_owner = new Subnets_owner_Model();
					$subnets_owner->subnet_id = $subnet_model->id;
					$subnets_owner->member_id = $form_data['owner_id'];
					$subnets_owner->redirect = 0;

					$subnets_owner->save_throwable();
					
					$ips = $subnet_model->get_free_ip_addresses();
					
					$ip_address_model = new Ip_address_Model();
		
					foreach ($ips as $ip)
					{
						$ip_address_model->clear();
						$ip_address_model->ip_address = $ip;
						$ip_address_model->member_id = $form_data['owner_id'];
						$ip_address_model->subnet_id = $subnet_model->id;
						$ip_address_model->save_throwable();
					}
				}
				
				// cloud		
				if ($form_data['cloud'])
				{
					$cloud_model = new Cloud_Model($form_data['cloud']);

					if ($cloud_model->id)
					{
						$cloud_model->add($subnet_model);
						$cloud_model->save_throwable();
					}
				}
				
				// commit transaction
				$subnet_model->transaction_commit();
				
				// correct add
				status::success('Subnet has been successfully saved.');
				// update anabled
				Allowed_subnets_Controller::update_enabled(
						$form_data['owner_id'], array($subnet_model->id)
				);
				
				$this->redirect('show', $subnet_model->id);
			}
			catch (Exception $e)
			{
				// roolback
				$subnet_model->transaction_rollback();
				Log::add_exception($e);
				// correct add
				status::error('Error - cannot save subnet.');
				$this->redirect('show_all');
			}
		}
		else
		{
			// bread crumbs
			$breadcrumbs = breadcrumbs::add()
					->link('subnets/show_all', 'Subnets',
							$this->acl_check_view('Devices_Controller','subnet'))
					->text('Add new');

			// view
			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $headline = __('Add new subnet');
			$view->subnet = isset($subnet_model) && $subnet_model->id ? $subnet_model : NULL;
			$view->content = new View('form');
			$view->content->form = $form->html();
			$view->content->headline = $headline;
			$view->render(TRUE);
		}
	} // end of add

	/**
	 * Function edits subnet.
	 * 
	 * @param integer $subnet_id
	 */
	public function edit($subnet_id = NULL) 
	{	
		// param check
		if (!$subnet_id || !is_numeric($subnet_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// access check
		if (!$this->acl_check_edit('Devices_Controller', 'subnet'))
		{
			Controller::error(ACCESS);
		}
		
		// model
		$subnet_model = new Subnet_Model($subnet_id);
		
		// record exists?
		if (!$subnet_model->id)
		{
			Controller::error(RECORD);
		}
		
		$this->subnet_id = $subnet_model->id;

		$arr_members = arr::merge(
				array(NULL => '----- '.__('Select member').' -----'),
				arr::from_objects(ORM::factory('member')->get_all_members_to_dropdown())
		);

		// form
		$form = new Forge('subnets/edit/'.$subnet_id);
		
        $form->group('Basic data');
		
		$form->input('name')
				->label(__('Subnet name').':')
				->rules('required|length[3,250]')
				->value($subnet_model->name);
		
        $form->input('network_address')
				->label(__('Network address').':')
				->rules('required|valid_ip_address')
				->callback(array($this, 'valid_netip'))
				->value($subnet_model->network_address);
		
        $form->input('netmask')
				->label(__('Netmask').':')
				->rules('required|valid_ip_address')
				->value($subnet_model->netmask);
		
        $form->input('OSPF_area_id')
				->label(__('OSPF area ID').':')
				->rules('valid_digit')
				->value($subnet_model->OSPF_area_id);
		
        if ($this->acl_check_edit('Devices_Controller', 'redirect'))
		{
        	$form->dropdown('redirect')
					->label(__('Redirection enabled'))
					->options(arr::rbool())
					->selected($subnet_model->redirect);
		}

		$form->dropdown('owner_id')
				->label(__('Owner').' '.help::hint('subnet_owner'))
				->options($arr_members)
				->selected($subnet_model->subnets_owner->member->id);
		
        $form->submit('Update');

		// validate form
		if($form->validate())
		{
			$form_data = $form->as_array();

			if ($subnet_model->subnets_owner->member->id != $form_data['owner_id'])
			{
				$ip_address_model = new Ip_address_Model();
				
				$ip_address_model->delete_ip_addresses_by_subnet_member(
						$subnet_id, $subnet_model->subnets_owner->member_id
				);
				
				if ($form_data['owner_id'])
				{
					$subnet_model->subnets_owner->subnet_id = $subnet_model->id;
					$subnet_model->subnets_owner->member_id = $form_data['owner_id'];
					$subnet_model->subnets_owner->redirect = 0;

					$subnet_model->subnets_owner->save();
					
					$ips = $subnet_model->get_free_ip_addresses();
					
					$ip_address_model = new Ip_address_Model();
		
					foreach ($ips as $ip)
					{
						$ip_address_model->clear();
						$ip_address_model->ip_address = $ip;
						$ip_address_model->member_id = $form_data['owner_id'];
						$ip_address_model->subnet_id = $subnet_model->id;
						$ip_address_model->save_throwable();
					}

					Allowed_subnets_Controller::update_enabled(
							$form_data['owner_id'], array($subnet_model->id)
					);
				}
				else
				{
					$subnet_model->subnets_owner->delete();
				}
				
				$member_id = $subnet_model->subnets_owner->member->id;
				$count = ORM::factory('ip_address')->count_all_ip_addresses_by_member_and_subnet(
						$member_id, $subnet_model->id
				);

				if ($subnet_model->subnets_owner->member->id && !$count)
				{
					Allowed_subnets_Controller::update_enabled(
							$member_id, NULL, NULL, array($subnet_model->id)
					);
				}
			}

			$subnet_model->name = $form_data['name'];
			$subnet_model->network_address = $form_data['network_address'];
			$subnet_model->netmask = $form_data['netmask'];
			$subnet_model->OSPF_area_id = $form_data['OSPF_area_id'];
			
			if ($this->acl_check_edit('Devices_Controller', 'redirect'))
			{
				$subnet_model->redirect = $form_data['redirect'];
			}

			if ($subnet_model->save())
			{
				status::success('Subnet has been successfully updated.');
			}

			$this->redirect('subnets/show/' . $subnet_id);
			
		}
		else
		{
			// bread crumbs
			$subnet_text = $subnet_model->name." ($subnet_model->network_address/"
					. network::netmask2cidr($subnet_model->netmask) .")";

			$breadcrumbs = breadcrumbs::add()
					->link('subnets/show_all', 'Subnets',
							$this->acl_check_view('Devices_Controller','subnet'))
					->disable_translation()
					->link('subnets/show/'.$subnet_model->id, $subnet_text,
							$this->acl_check_view('Devices_Controller','subnet'))
					->enable_translation()
					->text('Edit');

			// view
			$view = new View('main');
			$view->title = $headline = __('Edit subnet').' - '.$subnet_model->name;
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->form = $form->html();
			$view->content->headline = $headline;
			$view->render(TRUE);
		}
	}

	/**
	 * Function deletes subnet. Subnet is deleted with all of its ip addresses
	 * 
	 * @author Michal Kliment
	 * @param integer $subnet_id id of subnet to delete
	 */
	public function delete($subnet_id = NULL)
	{
		// param check
		if (!$subnet_id || !is_numeric($subnet_id))
		{
			Controller::warning(PARAMETER);
		}

		// access check
		if (!$this->acl_check_delete('Devices_Controller', 'subnet'))
		{
				Controller::error(ACCESS);
		}

		// model
		$subnet_model = new Subnet_Model($subnet_id);

		// exists
		if (!$subnet_model->id)
		{
			Controller::error(RECORD);
		}

		// find ip addresses of subnet, in this relation 1:n ORM works
		$ips = $subnet_model->ip_addresses;

		$ip_address_model = new Ip_address_Model();
		
		if ($ip_address_model->count_all_ip_addresses_without_member_by_subnet(
				$subnet_model->id
			))
		{
			status::warning('Subnet still has at least one ip address.');
		}
		else
		{
			if ($subnet_model->subnets_owner->id)
			{
				$ip_address_model->delete_ip_addresses_by_subnet_member(
						$subnet_model->id, $subnet_model->subnets_owner->member_id
				);
				$subnet_model->subnets_owner->delete();
			}

			foreach ($subnet_model->allowed_subnets as $allowed_subnet)
			{
				$member_id = $allowed_subnet->member_id;
				$allowed_subnet->delete();
				Allowed_subnets_Controller::update_enabled($member_id);
			}

			if ($subnet_model->delete())
			{
				status::success('Subnet has been successfully deleted.');
			}
			else
			{
				status::error('Error - cant delete subnet.');
			}
		}

		$this->redirect('subnets/show_all');
	}

	/**
	 * Function to draw address map
	 * 
	 * @author Michal Kliment
	 */
	public function address_map()
	{
		// access control
		if (!$this->acl_check_view('Devices_Controller', 'subnet'))
		{
			Controller::error(ACCESS);
		}
		
		$filter_form = new Filter_form('s');
		$filter_form->autoload();

		$subnet_model = new Subnet_Model();
		$total_subnets = $subnet_model->count_all_subnets();
		$subnets = $subnet_model->get_all_subnets(
				0, $total_subnets, 'cidr', 'ASC', $filter_form->as_sql()
		);

		// containts subnets itself
		$arr_subnets = array();
		
		// contains lengths of subnets
		$arr_subnet_lengths = array();
		
		// contains ranges of subnets (for not print empty lines)
		$arr_subnet_ranges = array();
		
		// contains colors for subnets
		$background_colors = array();

		foreach ($subnets as $subnet)
		{
			/* @var $nas array Newtworks Address Segments */
			$nas = explode('.', $subnet->network_address);
			/* @var $ns array Newtwors Segments */
			$ns = explode('.', $subnet->netmask);

			$arr_subnets[$nas[0]][$nas[1]][$nas[2]][$nas[3]] = $subnet;
			
			$arr_subnet_lengths[$nas[0]][$nas[1]][$nas[2]][$nas[3]] =
				(256 - $ns[0]) * (256 - $ns[1]) * (256 - $ns[2]) * (256 - $ns[3]);
			
			$background_colors[$nas[0]][$nas[1]][$nas[2]][$nas[3]] =
				special::RGB(rand(50, 150), $nas[2], $nas[3]);
		}
		
		// address ranges from settings is used
		if (Settings::get('address_ranges') != '')
		{
			$ranges = explode(",", Settings::get('address_ranges'));
			
			foreach ($ranges as $range_address)
			{
				// address contains / => it's in CIDR format
				if (strpos($range_address, '/') !== FALSE)
				{
					// split address and mask
					list ($range_address, $range_mask) = explode('/', $range_address);
				}
				// address is without / => it's single address
				else
					$range_mask = 32;

				$range_start = $range_address;
				$range_end = long2ip(ip2long($range_address) + (~ip2long(network::cidr2netmask($range_mask)) & 0xffffffff));

				// range start segments
				$rss = explode(".", $range_start);
				
				// range end segments
				$res = explode(".", $range_end);
				
				$arr_subnet_ranges[$rss[0]][$rss[1]]['start'] = $rss[2];
				
				$arr_subnet_ranges[$res[0]][$res[1]]['end'] = $res[2];
				
				$arr_subnet_ranges[$res[0]][$res[1]]['address'] = $range_address.'/'.$range_mask;
			}
		}
		
		// view
		$view = new View('subnets/address_map');
		$view->subnets = $arr_subnets;
		$view->lengths = $arr_subnet_lengths;
		$view->ranges = $arr_subnet_ranges;
		$view->background_colors = $background_colors;
		$view->render(TRUE);
	}
	

	/**
	 * Callback function validates ip address.
	 * 
	 * @param object $input
	 */
	public function valid_netip($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$netmask = $this->input->post('netmask');
		if ($netmask == '')
			return;
		
		$netip = ip2long($input->value);
		$mask = (int) ip2long($_POST['netmask']);

		// default network adress ranges are set
		if (($ranges = Settings::get('address_ranges')) != '')
		{
			$net_start = ip2long($input->value);
			$net_end = $net_start + (~ip2long($netmask) & 0xffffffff) + 1;

			// transform string to array
			$ranges = explode(',', $ranges);

			$matches = 0;
			foreach ($ranges as $range_address)
			{
				// address contains / => it's in CIDR format
				if (strpos($range_address, '/') !== FALSE)
				// split address and mask
					list ($range_address, $range_mask) = explode('/', $range_address);
				// address is without / => it's single address
				else
					$range_mask = 32;

				$range_start = ip2long($range_address);
				$range_end = $range_start + (~ip2long(network::cidr2netmask($range_mask)) & 0xffffffff) + 1;

				// match
				if ($net_start >= $range_start && $net_end <= $range_end)
					$matches++;
			}

			// no matches - error!
			if (!$matches)
			{
				$input->add_error('required', __(
						'Network address does not match any address ranges.'
				));
			}
		}

		if ($netip == 0)
		{
			$input->add_error('required', __('Invalid network address.'));
		}
		else if (($netip & $mask) != $netip)
		{
			$input->add_error('required', __(
					'Network address does not match the mask.'
			));
		}
		
		// checks overlaps with other subnets
		if (ORM::factory('subnet')->check_overlaps_of_subnets(
					$input->value, $this->input->post('netmask'),
					$this->subnet_id
			))
		{
			$input->add_error('required', __(
					'Network address collides with another subnet.'
			));
		}
		
	}
	
}
