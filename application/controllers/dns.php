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
 * DNS servers controller manages DNS zones and records
 * 
 * @author David Raška
 * @package Controller
 */
class Dns_Controller extends Controller
{
	/**
	 * Redirects to show all
	 */
	public function index()
	{
		url::redirect('dns/show_all');
	}
	
	/**
	 * Function adds new DNS zone
	 * 
	 * @param integer $parent_zone
	 */
	public function add($parent_zone = NULL)
	{
		// access control
		if (!$this->acl_check_new('Dns_Controller','zone'))
			Controller::error(ACCESS);

		$pz = NULL;
		
		if ($parent_zone !== NULL && intval($parent_zone))
		{
			$pz = new Dns_zone_Model($parent_zone);
			
			if (!$pz->id)
			{
				Controller::error(PARAMETER);
			}
		}
		
		// Find IP addresses with running DNS servers
		$dns_servers = ORM::factory('ip_address')->where(array('dns' => 1))->
				find_all()->select_list('id', 'ip_address');
		
		$dzm = new Dns_zones_map_Model();
		
		// creates new form
		$this->form = $form = new Forge();
		$form->set_attr('id', 'new_zone_form');
		
		$form->input('zone')
			->help(__('dns_zone'))
			->rules('required')
			->class('domain_name');
		
		if ($pz)
		{
			$form->zone->class('join1');
			$form->input('parent')
				->class('join2')
				->disabled('disabled')
				->value('.'.$pz->zone.'.');
			$primary_selected = $pz->ip_address_id;
			$secondary_selected = $dzm->get_secondary_servers_ids($pz->id);
			$nameserver = $pz->nameserver;
			$email = $pz->email;
			$refresh = $pz->refresh;
			$retry = $pz->retry;
			$expire = $pz->expire;
			$nx = $pz->nx;
			$ttl = $pz->ttl;
		}
		else
		{
			$primary_selected = 0;
			$secondary_selected = array();
			$nameserver = '';
			$email = Settings::get('email_default_email');
			$refresh = '1h';
			$retry = '10m';
			$expire = '1d';
			$nx = '1h';
			$ttl = '';
		}
		
		$form->input('ttl')
			->class('ttl')
			->label('TTL')
			->value($ttl);
		
		$form->group('DNS servers');
		
		$form->dropdown('primary')
			->label('Primary server')
			->options($dns_servers)
			->rules('required')
			->selected($primary_selected);
		
		$form->dropdown('secondary[]')
			->label('Secondary servers')
			->options($dns_servers)
			->selected($secondary_selected)
			->multiple('multiple');
		
		$form->group('SOA record');
		
		$form->input('nameserver')
			->label('Primary name server')
			->rules('required|length[0,255]')
			->value($nameserver)
			->class('domain_name');
		
		$form->input('sn')
			->label('Zone serial number')
			->help(__('dns_zone_sn'))
			->disabled('disabled')
			->value(date('Ymd')."00");
		
		$form->input('email')
			->label('Zone administrator E-mail')
			->value($email)
			->rules('required|length[5,255]')
			->class('email');
		
		$form->input('ref')
			->label('Zone refresh time')
			->value($refresh)
			->class('ttl')
			->rules('required');
		
		$form->input('ret')
			->label('Zone retry update time')
			->value($retry)
			->class('ttl')
			->rules('required');
		
		$form->input('exp')
			->label('Zone expire time')
			->value($expire)
			->class('ttl')
			->rules('required');
		
		$form->input('nxttl')
			->label('Zone not exists time')
			->value($nx)
			->class('ttl')
			->rules('required');
		
		$form->group('DNS records');
		
		$form->submit('Add');
		
		if (!$pz)
		{
			$title = __('Add new zone');
		}
		else
		{
			$title = __('Add new subzone');
		}
		
		if ($form->validate())
		{
			$form_array = $form->as_array();
			
			$zone = new Dns_zone_Model();
			$zone->transaction_start();
			
			try
			{
				if ($pz)
				{
					$zone->zone = trim($form_array['zone'], '. ').'.'.$pz->zone;
				}
				else
				{
					$zone->zone = trim($form_array['zone'], '. ');
				}
				
				$zone->ttl = $form_array['ttl'];
				$zone->nameserver = $form_array['nameserver'];
				$zone->email = $form_array['email'];
				$zone->sn = date('Ymd').'00';
				$zone->refresh = $form_array['ref'];
				$zone->retry = $form_array['ret'];
				$zone->expire = $form_array['exp'];
				$zone->nx = $form_array['nxttl'];
				$zone->ip_address_id = $form_array['primary'];
				
				$zone->save_throwable();
				$zone->transaction_commit();
				
				$dzm->add_secondary_servers($zone->id, $form_array['secondary']);
				
				// save dns records
				if (isset($_POST['name']))
				{
					$zone->transaction_start();
					foreach ($_POST['name'] AS $k => $v)
					{
						$rec = new Dns_record_Model();
						
						$rec->dns_zone_id = $zone->id;
						$rec->name = $_POST['name'][$k];
						$rec->ttl = (isset($_POST['rttl']) ? $_POST['rttl'][$k] : '');
						$rec->type = $_POST['type'][$k];
						$rec->value = $_POST['data'][$k];
						switch ($_POST['type'][$k])
						{
							case 'A':
							case 'AAAA':
								$rec->param = (isset($_POST['ptr']) ? $_POST['ptr'][$k] : '');
								break;
							case 'MX':
								$rec->param = $_POST['priority'][$k];
								break;
							default:
								$rec->param = '';
						}
						
						$rec->save_throwable();
					}
					$zone->transaction_commit();
					// message
					status::success('Zone has been successfully added.');
				}
			}
			catch (Exception $e)
			{
				$zone->transaction_rollback();
				// message
				status::error('Error - cannot add zone', $e);
			}
			
			$this->redirect('dns/show_all');
		}
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('dns/show_all', 'DNS',
						$this->acl_check_view('Dns_Controller', 'zone'))
				->disable_translation()
				->text($title);

		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $title;
		$view->content = new View('form');
		$view->content->headline = $title;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}
	
	/**
	 * Function edits DNS zone
	 * 
	 * @param integer $zone
	 */
	public function edit($zone = NULL)
	{
		// access control
		if (!$this->acl_check_edit('Dns_Controller','zone'))
			Controller::error(ACCESS);
		
		$zone = new Dns_zone_Model($zone);
			
		if (!$zone->id)
		{
			Controller::error(RECORD);
		}
		
		// Find IP addresses with running DNS servers
		$dns_servers = ORM::factory('ip_address')->where(array('dns' => 1))->
				find_all()->select_list('id', 'ip_address');
		
		$dzm = new Dns_zones_map_Model();
		
		// creates new form
		$this->form = $form = new Forge();
		$form->set_attr('id', 'new_zone_form');
		
		$form->input('zone')
			->help(__('dns_zone'))
			->rules('required')
			->value($zone->zone)
			->class('domain_name');
		
		$form->input('ttl')
			->label('TTL')
			->class('ttl')
			->value($zone->ttl);
		
		$form->group('DNS servers');
		
		$form->dropdown('primary')
			->label('Primary server')
			->options($dns_servers)
			->rules('required')
			->selected($zone->ip_address_id);
		
		$form->dropdown('secondary[]')
			->label('Secondary servers')
			->options($dns_servers)
			->selected($dzm->get_secondary_servers_ids($zone->id))
			->multiple('multiple');
		
		$form->group('SOA record');
		
		$form->input('nameserver')
			->label('Primary name server')
			->rules('required')
			->value($zone->nameserver)
			->class('domain_name');
		
		// Increment serial number counter
		if (date('Ymd') == substr($zone->sn, 0, 8))
		{
			$sn = substr($zone->sn, 0, 8). sprintf('%02d', intval(substr($zone->sn, 8)) + 1);
		}
		else
		{
			$sn = date('Ymd').'00';
		}
		
		$form->input('sn')
			->label('Zone serial number')
			->help(__('dns_zone_sn'))
			->disabled('disabled')
			->value($sn);
		
		$form->input('email')
			->label('Zone administrator E-mail')
			->value($zone->email)
			->rules('required|length[5,255]')
			->class('email');
		
		$form->input('ref')
			->label('Zone refresh time')
			->value($zone->refresh)
			->rules('required')
			->class('ttl');
		
		$form->input('ret')
			->label('Zone retry update time')
			->value($zone->retry)
			->rules('required')
			->class('ttl');
		
		$form->input('exp')
			->label('Zone expire time')
			->value($zone->expire)
			->rules('required')
			->class('ttl');
		
		$form->input('nxttl')
			->label('Zone not exists time')
			->value($zone->nx)
			->rules('required')
			->class('ttl');
		
		$form->group('DNS records');
		
		$form->submit('Edit');
		
		if ($form->validate())
		{
			$form_array = $form->as_array();
			
			$zone->transaction_start();
			
			try
			{				
				$zone->zone = trim($form_array['zone'], '. ');
				$zone->ttl = $form_array['ttl'];
				$zone->sn = $sn;
				$zone->nameserver = $form_array['nameserver'];
				$zone->email = $form_array['email'];
				$zone->refresh = $form_array['ref'];
				$zone->retry = $form_array['ret'];
				$zone->expire = $form_array['exp'];
				$zone->nx = $form_array['nxttl'];
				$zone->ip_address_id = $form_array['primary'];
				
				$zone->save_throwable();
				$zone->transaction_commit();
				
				$dzm->delete_secondary_servers($zone->id);
				$dzm->add_secondary_servers($zone->id, $form_array['secondary']);
				
				if (isset($_POST['name']))
				{
					$zone->transaction_start();
					// update old
					foreach ($_POST['name'] AS $k => $v)
					{
						if (intval($_POST['id'][$k]) == 0)
						{
							// create new
							$rec = new Dns_record_Model();
						}
						else
						{
							// update old
							$rec = new Dns_record_Model(intval($_POST['id'][$k]));
						}
						
						$rec->dns_zone_id = $zone->id;
						$rec->name = $_POST['name'][$k];
						$rec->ttl = (isset($_POST['rttl']) ? $_POST['rttl'][$k] : '');
						$rec->type = $_POST['type'][$k];
						$rec->value = $_POST['data'][$k];
						switch ($_POST['type'][$k])
						{
							case 'A': 
							case 'AAAA':
								$rec->param = (isset($_POST['ptr']) && isset($_POST['ptr'][$k]) ? $_POST['ptr'][$k] : '');
								break;
							case 'MX':
								$rec->param = $_POST['priority'][$k];
								break;
						}
						
						$rec->save_throwable();
					}
					
					$zone->transaction_commit();
				}
				
				// delete old
				if (isset($_POST['deleted']))
				{
					$zone->transaction_start();
					$arr = array_map('intval', @$_POST['deleted']);
					debug::vardump($arr);
					$rec = new Dns_record_Model();
					$rec->delete_all($arr);
					
					$zone->transaction_commit();
				}
				
				// message
				status::success('Zone has been successfully updated.');
			}
			catch (Exception $e)
			{
				$zone->transaction_rollback();
				status::error('Error - cant update zone.', $e);
			}
			
			$this->redirect(Path::instance()->previous());
		}
		
		$title = __('Edit zone');
		
		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('dns/show_all', 'DNS',
						$this->acl_check_view('Dns_Controller', 'zone'))
				->disable_translation()
				->link('dns/show/'.$zone->id, $zone->zone . ' (' . $zone->id . ')',
						$this->acl_check_view('Dns_Controller', 'zone'))
				->enable_translation()
				->text($title)
				->html();
		
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs;
		$view->title = $title;
		$view->content = new View('form');
		$view->content->headline = $title;
		$view->content->form = $form->html();
		$view->render(TRUE);
	}
	
	/**
	 * Show zone details and records
	 *
	 * @param integer $zone_id 
	 */
	public function show($zone_id = NULL)
	{
		// check param
		if (!$zone_id || !is_numeric($zone_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_view('Dns_Controller', 'zone'))
		{
			Controller::error(ACCESS);
		}

		// load model
		$zone = new Dns_zone_Model($zone_id);
		$dzm = new Dns_zones_map_Model();
		$record_model = new Dns_record_Model();
		// check exists
		if (!$zone->id)
		{
			Controller::error(RECORD);
		}
		
		$headline = $zone->zone . ' (' . $zone_id . ')';
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add()
				->link('dns/show_all', 'DNS',
						$this->acl_check_view('Dns_Controller', 'zone'))
				->disable_translation()
				->text($headline)
				->html();
		
		// view
		$view = new View('main');
		$view->title = $headline;
		$view->content = new View('dns/show');
		$view->breadcrumbs = $breadcrumbs;
		$view->content->zone = $zone;
		// add zone master server
		$view->content->a_records = $record_model->get_fqdn_records_in_zone($zone_id, 'A');
		$view->content->aaaa_records = $record_model->get_fqdn_records_in_zone($zone_id, 'AAAA');
		$view->content->cname_records = $record_model->get_fqdn_records_in_zone($zone_id, 'CNAME');
		$view->content->ns_records = $record_model->get_fqdn_records_in_zone($zone_id, 'NS');
		$view->content->mx_records = $record_model->get_fqdn_records_in_zone($zone_id, 'MX');
		$view->content->secondary_servers = $dzm->get_secondary_servers($zone_id);
		$view->render(TRUE);
	}
	
	/**
	 * Function shows all zones.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'id', $order_by_direction = 'asc',
			$page_word = null, $page = 1)
	{	
		if (!$this->acl_check_view('Dns_Controller','zone'))
			Controller::error(ACCESS);
		
		$filter_form = new Filter_form();
		
		$filter_form->add('id')
				->type('number');
		
		$filter_form->add('zone')
				->callback('json/dns_zone');
		
		$filter_form->add('ip_address')
				->label('Primary server')
				->callback('json/primary_dns_server');
		
		$filter_form->add('name')
				->label('Record name');
		
		$filter_form->add('type')
				->label('Record type')
				->type('select')
				->values(array('A', 'AAAA', 'CNAME', 'NS', 'MX'));
		
		$filter_form->add('value')
				->label('Record value');
		
		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');
		
		// parameters control
		$allowed_order_type = array
		(
			'id', 'domain', 'access_time'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		$dns_zone_model = new Dns_zone_Model();
		
		// hide grid on its first load (#442)
		$hide_grid = Settings::get('grid_hide_on_first_load') && $filter_form->is_first_load();
		
		if (!$hide_grid)
		{
			try
			{
				$total_zones = $dns_zone_model->count_all_zones($filter_form->as_sql());

				if (($sql_offset = ($page - 1) * $limit_results) > $total_zones)
					$sql_offset = 0;

				$query = $dns_zone_model->get_all_zones(
					$sql_offset, (int)$limit_results, $order_by,
					$order_by_direction, $filter_form->as_sql()
				);
			}
			catch (Exception $e)
			{
				if ($filter_form->is_loaded_from_saved_query())
				{
					status::error('Invalid saved query', $e);
					// disable default query (loop protection)
					if ($filter_form->is_loaded_from_default_saved_query())
					{
						ORM::factory('filter_query')->remove_default($filter_form->get_base_url());
					}
					$this->redirect(url_lang::current());
				}
				throw $e;
			}
		}
		
		$grid = new Grid('dns', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 10,
			'base_url'					=> Config::get('lang').'/dns/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=>  isset($total_zones) ? $total_zones : 0,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		));
		
		$grid->order_field('id')
				->label('ID');
		
		$grid->order_callback_field('zone')
				->callback(array($this, 'zone_link'));
		
		$grid->callback_field('id')
				->label('Zone records')
				->callback(array($this, 'zone_records'));
		
		$grid->order_callback_field('access_time')
				->callback('callback::dns_servers_last_access_diff_field')
				->label('Last access time');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Dns_Controller', 'zone'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('dns/show')
					->label('Show zone');
		}
		
		if ($this->acl_check_edit('Dns_Controller', 'zone'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('dns/edit')
					->label('Edit zone');
		}
		if ($this->acl_check_new('Dns_Controller', 'zone'))
		{
			$actions->add_action('id')
					->icon_action('new')
					->url('dns/add')
					->label('Add new subzone');
		}
		if ($this->acl_check_delete('Dns_Controller', 'zone'))
		{
			$actions->add_conditional_action('id')
					->icon_action('delete')
					->url('dns/delete')
					->class('delete_link')
					->label('Delete zone');
		}
			
		
		if (!$hide_grid)
			$grid->datasource($query);
		
		if ($this->acl_check_new('Dns_Controller', 'zone'))
		{
			$grid->add_new_button('/dns/add', 'Add new zone');
		}
		
		$headline = __('List of all zones');
		
		// bread crumbs
		$breadcrumbs = breadcrumbs::add(false)
				->text('DNS')
				->html();
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs;
		$view->content = new View('show_all');
		$view->content->headline = $headline;
		$view->content->table = $grid;
		$view->render(TRUE);
	} // end of show_all function
	
	/**
	 * Callback function for zone name link
	 * 
	 * @param object $item
	 * @param string $name
	 */
	public static function zone_link($item, $name)
	{
		echo "<a target='_blank' href='http://".$item->zone."'>".$item->zone."</a>";
	}
	
	/**
	 * Callback function for zone records
	 * 
	 * @param object $item
	 * @param string $name
	 */
	public static function zone_records($item, $name)
	{
		$dr = new Dns_record_Model();
		
		echo $dr->get_records_types_in_zone($item->id);
	}
	
	// TODO: move to web_interface controller
	/**
	 * Deletes zone and records
	 *
	 * @param integer $zone_id 
	 */
	public function delete($zone_id = NULL)
	{
		// check param
		if (!$zone_id || !is_numeric($zone_id))
		{
			Controller::warning(PARAMETER);
		}
		
		// check access
		if (!$this->acl_check_delete('Dns_Controller', 'zone'))
		{
			Controller::error(ACCESS);
		}

		// load model
		$zone_model = new Dns_zone_Model($zone_id);
		
		// check exists
		if (!$zone_model->id)
		{
			Controller::error(RECORD);
		}
		
		// delete (dns records are deleted by forein keys)
		if ($zone_model->delete())
		{
			status::success('Zone has been successfully deleted.');
		}
		else
		{
			status::error('Error - cant delete this zone.');
		}

		// redirect to show all
		url::redirect('dns/show_all/');
	}
	
	public static function generate_config($ip_address)
	{
		$dzm = new Dns_zones_map_Model();
		
		// Get zones managed by given DNS server
		$zone_model = new Dns_zone_Model();
		$prim_zones = $zone_model->get_zones_of_primary_server($ip_address->id);
		$sec_zones = $zone_model->get_zones_of_secondary_server($ip_address->id);
		
		$zones_array = array();
		$zone_ids = array();
		$ipv4ptr = array();
		$ipv6ptr = array();
		
		// Primary server
		if ($prim_zones)
		{	
			foreach ($prim_zones as $z)
			{
				$zone_ids[] = $z->id;
				$zone = array();
				
				// Zone name
				$zone['zone'] = $z->zone;
				$zone['ttl'] = $z->ttl;
				$zone['ns'] = $z->nameserver;
				$zone['mail'] = str_replace('@', '.', $z->email);
				$zone['sn'] = $z->sn;
				$zone['ref'] = $z->refresh;
				$zone['ret'] = $z->retry;
				$zone['ex'] = $z->expire;
				$zone['nx'] = $z->nx;
				
				// IP addresses of slave servers allowed to transfer zone
				$zone['slaves'] = $dzm->get_secondary_servers_ips($z->id);
				
				$zone['records'] = array();
				
				// Add default NS record
				$zone['records'][] = array
				(
					'name' => '',
					'ttl' => $z->ttl == NULL ? $z->nx : $z->ttl,
					'type' => 'NS',
					'value' => $z->nameserver.'.',
				);
				$zone['records'][] = array
				(
					'name' => $z->nameserver.'.',
					'ttl' => $z->ttl == NULL ? $z->nx : $z->ttl,
					'type' => 'A',
					'value' => $ip_address->ip_address,
				);
				
				// Add zone records
				if ($z->dns_records)
				{
					foreach ($z->dns_records as $r)
					{
						$rec = $r->as_array();
						
						// Create FQDN value for NS record
						if ($rec['type'] == 'NS')
						{
							if (substr($rec['value'], -1) != '.')
							{
								$rec['value'] .= '.' . $zone['zone'] . '.';
							}
						}
						
						// Create FQDN value for PTR record
						if ($rec['name'] == '' || $rec['name'] == '@')
						{
							$data = $zone['zone'].'.';
						}
						else if (substr($rec['name'], -1) == '.')
						{
							$data = $rec['name'];
						}
						else
						{
							$data = $rec['name'] . '.' . $zone['zone'] . '.';
						}
						
						// Create IPv4 PTR record
						if ($rec['type'] == 'A' && $rec['param'] == 'on')
						{
							$ip = explode('.', $rec['value']);
							
							// Create SOA for reverse zone
							if (!isset($ipv4ptr[$ip[0]]))
							{
								$ipv4ptr[$ip[0]] = array
								(
									'zone' => $ip[0].'.in-addr.arpa',
									'ttl' => dns::get_seconds_from_ttl($z->ttl == NULL ? $rec['ttl'] : $z->ttl),
									'ns' => $z->nameserver,
									'mail' => str_replace('@', '.', $z->email),
									'sn' => dns::get_seconds_from_ttl($z->sn),
									'ref' => dns::get_seconds_from_ttl($z->refresh),
									'ret' => dns::get_seconds_from_ttl($z->retry),
									'ex' => dns::get_seconds_from_ttl($z->expire),
									'nx' => dns::get_seconds_from_ttl($z->nx),
									'records' => array
									(
										array
										(
											'name'	=> '@',
											'ttl'	=> '',
											'type'	=> 'NS',
											'value'	=> $z->nameserver.'.',
										)
									)
								);
							}
							else
							{
								if (dns::get_seconds_from_ttl($z->ttl == NULL ? $rec['ttl'] : $z->ttl) < $ipv4ptr[$ip[0]]['ttl'])
								{
									$ipv4ptr[$ip[0]]['ttl'] = dns::get_seconds_from_ttl($z->ttl == NULL ? $rec['ttl'] : $z->ttl);
								}
								if (dns::get_seconds_from_ttl($z->refresh) < $ipv4ptr[$ip[0]]['ref'])
								{
									$ipv4ptr[$ip[0]]['ref'] = dns::get_seconds_from_ttl($z->refresh);
								}
								if (dns::get_seconds_from_ttl($z->retry) < $ipv4ptr[$ip[0]]['ret'])
								{
									$ipv4ptr[$ip[0]]['ret'] = dns::get_seconds_from_ttl($z->retry);
								}
								if (dns::get_seconds_from_ttl($z->expire) < $ipv4ptr[$ip[0]]['ex'])
								{
									$ipv4ptr[$ip[0]]['ex'] = dns::get_seconds_from_ttl($z->expire);
								}
								if (dns::get_seconds_from_ttl($z->nx) < $ipv4ptr[$ip[0]]['nx'])
								{
									$ipv4ptr[$ip[0]]['nx'] = dns::get_seconds_from_ttl($z->nx);
								}
								if ($z->sn > $ipv4ptr[$ip[0]]['sn'])
								{
									$ipv4ptr[$ip[0]]['sn'] = $z->sn + 1;
								}
							}
							
							// Add record
							$ipv4ptr[$ip[0]]['records'][] = array
							(
								'name' => implode('.', array_reverse($ip)).'.in-addr.arpa.',
								'ttl' => '',
								'type' => 'PTR',
								'value' => $data,
							);
						}
						
						// Create IPv6 PTR record
						if ($rec['type'] == 'AAAA' && $rec['param'] == 'on')
						{
							$ip = explode(':', $rec['value']);
							
							// Decompress IPv6 address to full length
							if (($pos = array_search('', $ip)) !== FALSE)
							{
								$count = count($ip);
								$ip[$pos] = '0000';
								for ($i = 0; $i < 8-$count; $i++)
								{
									array_splice($ip, $pos, 0, '0000');
								}
								
								foreach ($ip as $k => $i)
								{
									$ip[$k] = num::null_fill($i, 4);
								}
							}
							
							$rev_ip = array_reverse(str_split(implode('', $ip)));
							$prefix = implode('.', array_slice($rev_ip, -16));
							
							// Create SOA for reverse zone
							if (!isset($ipv6ptr[$prefix]))
							{
								$ipv6ptr[$prefix] = array
								(
									'zone' => $prefix.'.ip6.arpa',
									'ttl' => dns::get_seconds_from_ttl($z->ttl == NULL ? $rec['ttl'] : $z->ttl),
									'ns' => $z->nameserver,
									'mail' => str_replace('@', '.', $z->email),
									'sn' => dns::get_seconds_from_ttl($z->sn),
									'ref' => dns::get_seconds_from_ttl($z->refresh),
									'ret' => dns::get_seconds_from_ttl($z->retry),
									'ex' => dns::get_seconds_from_ttl($z->expire),
									'nx' => dns::get_seconds_from_ttl($z->nx),
									'records' => array
									(
										array
										(
											'name'	=> '@',
											'ttl'	=> '',
											'type'	=> 'NS',
											'value'	=> $z->nameserver.'.',
										)
									)
								);
							}
							else
							{
								if (dns::get_seconds_from_ttl($z->ttl == NULL ? $rec['ttl'] : $z->ttl) < $ipv6ptr[$prefix]['ttl'])
								{
									$ipv6ptr[$prefix]['ttl'] = dns::get_seconds_from_ttl($z->ttl == NULL ? $rec['ttl'] : $z->ttl);
								}
								if (dns::get_seconds_from_ttl($z->refresh) < $ipv6ptr[$prefix]['ref'])
								{
									$ipv6ptr[$prefix]['ref'] = dns::get_seconds_from_ttl($z->refresh);
								}
								if (dns::get_seconds_from_ttl($z->retry) < $ipv6ptr[$prefix]['ret'])
								{
									$ipv6ptr[$prefix]['ret'] = dns::get_seconds_from_ttl($z->retry);
								}
								if (dns::get_seconds_from_ttl($z->expire) < $ipv6ptr[$prefix]['ex'])
								{
									$ipv6ptr[$prefix]['ex'] = dns::get_seconds_from_ttl($z->expire);
								}
								if (dns::get_seconds_from_ttl($z->nx) < $ipv6ptr[$prefix]['nx'])
								{
									$ipv6ptr[$prefix]['nx'] = dns::get_seconds_from_ttl($z->nx);
								}
								if ($z->sn > $ipv6ptr[$prefix]['sn'])
								{
									$ipv6ptr[$prefix]['sn'] = $z->sn + 1;
								}
							}
							
							// Add record
							$ipv6ptr[$prefix]['records'][] = array
							(
								'name' => implode('.', $rev_ip).'.ip6.arpa.',
								'ttl' => '',
								'type' => 'PTR',
								'value' => $data,
							);
						}
						
						$zone['records'][] = $rec;
					}
				}
				
				$zones_array['master'][] = $zone;
			}
		}
		
		// Add IPv4 reverse records
		foreach ($ipv4ptr as $zone)
		{
			$zones_array['master'][] = $zone;
		}
		
		// IPv6 reverse records
		foreach ($ipv6ptr as $zone)
		{
			$zones_array['master'][] = $zone;
		}
		
		// Secondary server
		if ($sec_zones)
		{
			foreach ($sec_zones as $z)
			{
				$zone = array();
				$zone['zone'] = $z->zone;
				$zone['master'] = $z->ip_address;
				
				$zones_array['slave'][] = $zone;
			}
		}
		
		// Set access time
		$zone_model->set_access_time_for_zones($zone_ids);
		
		return $zones_array;
	}
}
