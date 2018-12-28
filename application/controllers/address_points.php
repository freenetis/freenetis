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
 * Controller performs address points actions.
 * 
 * @package Controller
 */
class Address_points_Controller extends Controller
{

	const METHOD_OSM_NOMINATIM = 1;
	
	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('address_points/show_all');
	}

	/**
	 * Function shows list of all address points.
	 * 
	 * @author Michal Kliment
	 * @param $limit_results
	 * @param $order_by
	 * @param $order_by_direction
	 * @param $page_word
	 * @param $page
	 */
	public function show_all(
			$member_id = 0, $limit_results = 50, $order_by = 'items_count',
			$order_by_direction = 'DESC', $page_word = null, $page = 1)
	{
		// access rights
		if (!$this->acl_check_view(get_class($this), 'address_point'))
			Controller::error(ACCESS);
		
		// gets new selector
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');

		// parameters control
		$allowed_order_type = array
		(
			'id', 'name', 'street', 'street_number', 'town', 'quarter',
			'zip_code', 'gps', 'items_count'
		);
		
		// order by check
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
		
		$town_model = new Town_Model();
		$street_model = new Street_Model();
		
		$filter_form = new Filter_form('ap');
		
		$filter_form->add('name')
			->callback('json/address_point_name');
		
		$filter_form->add('country_name')
			->label('Country')
			->callback('json/country_name');
		
		$filter_form->add('street')
			->type('select')
			->values(
				array_unique(
					$street_model->select_list('street', 'street')
				)
			);
		
		$filter_form->add('street_number')
			->type('number');
		
		$filter_form->add('town')
			->type('select')
			->values(
				array_unique(
					$town_model->select_list('town', 'town')
				)
			);
		
		$filter_form->add('quarter')
			->callback('json/quarter_name');
		
		$filter_form->add('zip_code')
			->callback('json/zip_code');
		
		$filter_form->add('gps')
			->callback('json/gps');
		
		$filter_form->add('items_count')
			->type('number');
		

		$address_point_model = new Address_point_Model();
		
		$total_address_points = $address_point_model->count_all_address_points(
				$member_id, $filter_form->as_sql()
		);
		
		// limit check
		if (($sql_offset = ($page - 1) * $limit_results) > $total_address_points)
			$sql_offset = 0;

		$query = $address_point_model->get_all_address_points(
				$sql_offset, (int)$limit_results, $order_by, $order_by_direction,
				$member_id, $filter_form->as_sql()
		);

		// it creates grid to view all address points
		$grid = new Grid('address_points', '', array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 10,
			'base_url'					=> Config::get('lang').'/address_points/show_all/'.
											$member_id.'/'.$limit_results.'/'.$order_by.'/'.$order_by_direction,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_address_points,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form,
			'variables'					=> $member_id.'/',
			'url_array_ofset'			=> 1,
		));

		$grid->add_new_button('address_points/add', __('Add address point'));
		
		$grid->add_new_button(
				'address_points/autocomplete_gps',
				__('Automatically fill in GPS coordinates')
		);
		
		/*$grid->add_new_button(
				'address_points/import',
				__('Import addresses')
		);*/

		$grid->order_field('id')
				->label('ID');
		
		$grid->order_field('name');
		
		$grid->order_field('country_name')
				->label('Country');
		
		$grid->order_field('street');
		
		$grid->order_field('street_number');
		
		$grid->order_field('town');
		
		$grid->order_field('quarter');
		
		$grid->order_field('zip_code');
		
		$grid->order_callback_field('gps')
				->callback('callback::gps_field');
		
		$grid->order_callback_field('items_count')
				->callback('callback::items_count_field');

		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view(get_class($this), 'address_point'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('address_points/show');
		}
		
		if ($this->acl_check_edit(get_class($this), 'address_point'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('address_points/edit');
		}
		
		if ($this->acl_check_delete(get_class($this), 'address_point'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('address_points/delete')
					->class('delete_link');
		}
			
		$grid->datasource($query);

		$links = array();
		$links[] = __('Address points');
		$links[] = html::anchor('towns', __('Towns'));
		$links[] = html::anchor('streets', __('Streets'));
		
		$members = array
		(
			NULL => '----- '.__('Select member').' -----'
		) + ORM::factory('member')->select_list_grouped();
		
		// form to group by type
		$form = new Forge(url::base(TRUE).url::current(TRUE));
		
		$form->dropdown('member_id')
				->options($members)
				->selected($member_id);
		
		$form->submit('submit');
		
		if ($form->validate() && !isset($_POST['record_per_page']))
		{
			url::redirect('address_points/show_all/'.$form->member_id->value);
		}
		
		// view
		$view = new View('main');
		$view->breadcrumbs = __('Address points');
		$view->title = __('List of all address points');
		$view->content = new View('show_all');
		$view->content->submenu = implode(' | ',$links);
		$view->content->headline = __('List of all address points');
		$view->content->form = $form;
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function shows address point detail.
	 * 
	 * @author Michal Kliment
	 * @param integer $address_point_id id of address point to show
	 */
	public function show($address_point_id = NULL)
	{
		// bad parameter
		if (!$address_point_id)
			Controller::warning(PARAMETER);

		$ap = new Address_point_Model($address_point_id);

		// address point doesn't exist
		if (!$ap->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view(get_class($this), 'address_point'))
			Controller::error(ACCESS);

		$gps = "";

		if ($ap->gps != NULL)
		{
		    $gps_result = $ap->get_gps_coordinates($ap->id);

		    if (! empty($gps_result))
		    {
				$gps = gps::degrees($gps_result->gpsx, $gps_result->gpsy, true);
		    }
		}

		// finds all members on this address
		$members = $ap->get_all_members();

		$members_grid = new Grid('members', null, array
		(
			'separator'		   		=> '<br /><br />',
			'use_paginator'	   		=> false,
			'use_selector'	   		=> false,
			'total_items'			=> count($members)
		));

		$members_grid->field('member_id')
				->label('ID');
		
		$members_grid->link_field('member_id')
				->link('members/show', 'member_name');
		
		$members_grid->callback_field('type')
				->callback('callback::address_point_member_field');

		$members_grid->datasource($members);

		// finds all devices on this address
		$devices = $ap->get_all_devices();

		$devices_grid = new Grid('devices', null,array
		(
			'separator'		   		=> '<br /><br />',
			'use_paginator'	   		=> false,
			'use_selector'	   		=> false,
			'total_items'			=> count($devices)
		));

		$devices_grid->field('device_id')
				->label('ID');
		
		$devices_grid->callback_field('device_id')
				->label(__('Device'))
				->callback('callback::device_field');
		
		$devices_grid->link_field('user_id')
				->link('users/show', 'user_name')
				->label('User');
		
		$devices_grid->link_field('member_id')
				->link('members/show', 'member_name')
				->label('Member');

		$devices_grid->datasource($devices);

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('address_points/show_all', 'Address points',
						$this->acl_check_view(get_class($this), 'address_point'))
				->disable_translation()
				->text($ap->__toString())
				->html();

		// view
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs;
		$view->title = __('Address point detail');
		$view->mapycz_enabled = TRUE;
		$view->content = new View('address_points/show');
		$view->content->address_point = $ap;
		$view->content->members_grid = $members_grid;
		$view->content->devices_grid = $devices_grid;
		$view->content->gps = $gps;
		$view->content->gpsx = !empty($gps) ? $gps_result->gpsx : '';
		$view->content->gpsy = !empty($gps) ? $gps_result->gpsy : '';
        $view->content->lang = Config::get('lang');
		$view->render(TRUE);
	}

	/**
	 * Function allow to add address point.
	 * 
	 * @author Ondřej Fibich
	 */
	public function add()
	{
		// access rights
		if (!$this->acl_check_new(get_class($this),'address_point'))
			Controller::error(ACCESS);
		
		// country
		$arr_countries = ORM::factory('country')->where('enabled', 1)->select_list('id', 'country_name');
			   		
		// streets
		$arr_streets = array
		(
				NULL => '----- '.__('without street').' -----'
		) + ORM::factory('street')->select_list('id', 'street');
		
		// towns
		$arr_towns = array
		(
				NULL => '----- '.__('select town').' -----'
		) + ORM::factory('town')->select_list_with_quater();

		// creates new form
		$form = new Forge('address_points/add/');
		
		$form->dropdown('country_id')
				->label('Country')
				->rules('required')
				->options($arr_countries)
				->selected(Settings::get('default_country'))
				->style('width:200px');

		$form->dropdown('town_id')
				->label('Town')
				->rules('required')
				->options($arr_towns)
				->style('width:200px');
		
		$form->dropdown('street_id')
				->label('Street')
				->options($arr_streets)
				->style('width:200px');
		
		$form->input('street_number')
				->rules('length[1,50]');
		
		$form->input('gpsx')
				->label(__('GPS').' X:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps');
		
		$form->input('gpsy')
				->label(__('GPS').' Y:&nbsp;'.help::hint('gps_coordinates'))
				->rules('gps');

		$form->submit('Add');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$use_gps = !empty($form_data['gpsx']) && !empty($form_data['gpsy']);

			$address_point_model = new Address_point_Model();

			// check if address point just exist
			$address_point = $address_point_model->get_address_point(
					$form_data['country_id'], $form_data['town_id'], 
					$form_data['street_id'], $form_data['street_number'],
					$form_data['gpsx'], $form_data['gpsy']
			);

			$issaved = TRUE;

			// address point is already in database?
			if ($address_point->id)
			{
				// check if it is not same as origin
				status::warning('Address point already in database.');
				$issaved = FALSE;
			}
			else
			{
				// address point doesn't exist
				$issaved = $issaved && $address_point->save();

				// save GPS if it is set
				if ($use_gps)
				{
					$gpsx = doubleval($form_data["gpsx"]);
					$gpsy = doubleval($form_data["gpsy"]);

					if (gps::is_valid_degrees_coordinate($form->gpsx->value))
					{
						$gpsx = gps::degrees2real($form->gpsx->value);
					}

					if (gps::is_valid_degrees_coordinate($form->gpsy->value))
					{
			    		$gpsy = gps::degrees2real($form->gpsy->value);
					}

			    	// save
					$issaved = $issaved && $address_point->update_gps_coordinates(
							$address_point->id, $gpsx, $gpsy
					);
				}
			}

			// success
			if ($issaved)
			{
				status::success('Address point has been successfully added.');
				url::redirect('address_points/show/' . $address_point->id);
			}
		}

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('address_points/show_all', 'Address points',
						$this->acl_check_view(get_class($this), 'address_point'))
				->text('Add new')
				->html();

		// view
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs;
		$view->title = __('Add address point');
		$view->content = new View('form');
		$view->content->headline = __('Add address point');
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Form for editing address point (just for filling in GPS coordinates).
	 * 
	 * @author Michal Kliment, Ondřej Fibich
	 * @param integer $address_point_id id of address point to edit
	 */
	public function edit($address_point_id = NULL)
	{
		// no parameter
		if (!$address_point_id)
			Controller::warning(PARAMETER);

		$ap = new Address_point_Model($address_point_id);

		// record doesn't exist
		if (!$ap->id)
			Controller::error(RECORD);

		// access rights
		if (!$this->acl_check_edit(get_class($this),'address_point'))
			Controller::error(ACCESS);

		// country
		$arr_countries = ORM::factory('country')->where('enabled', 1)->select_list('id', 'country_name');
		$arr_countries = $arr_countries + ORM::factory('country')->where('id', $ap->country_id)->select_list('id', 'country_name');
		
		// streets
		$arr_streets = array
		(
				NULL => '----- '.__('without street').' -----'
		) + ORM::factory('street')->select_list('id', 'street');
		
		// towns
		$arr_towns = array
		(
				NULL => '----- '.__('select town').' -----'
		) + ORM::factory('town')->select_list_with_quater();

		// gps
		$gpsx = NULL;
		$gpsy = NULL;

		if ($ap->gps != NULL)
		{
		    $gps_result = $ap->get_gps_coordinates($ap->id);

		    if (! empty($gps_result))
		    {
				$gpsx = gps::real2degrees($gps_result->gpsx, false);
				$gpsy = gps::real2degrees($gps_result->gpsy, false);
		    }
		}

		// creates new form
		$form = new Forge('address_points/edit/'.$address_point_id);
		
		$form->input('name')
				->label(__('Name').': '.help::hint('address_point_name'))
				->value($ap->name);

		$form->dropdown('country_id')
				->label('Country')
				->options($arr_countries)
				->selected($ap->country_id)
				->disabled('disabled')
				->style('width:200px');
		
		$form->dropdown('street_id')
				->label('Street')
				->options($arr_streets)
				->selected($ap->street_id)
				->disabled('disabled')
				->style('width:200px');
		
		$form->input('street_number')
				->label('Street number')
				->value($ap->street_number)
				->disabled('disabled')
				->style('width:200px');
		
		$form->dropdown('town_id')
				->label('Town')
				->options($arr_towns)
				->selected($ap->town_id)
				->disabled('disabled')
				->style('width:200px');
		
		$form->input('gpsx')
				->label(__('GPS').' X:&nbsp;'.help::hint('gps_coordinates'))
				->rules('required|gps')
				->value($gpsx);
		
		$form->input('gpsy')
				->label(__('GPS').' Y:&nbsp;'.help::hint('gps_coordinates'))
				->rules('required|gps')
				->value($gpsy);
		
		$form->submit('Edit');

		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();

			$use_gps = !empty($form_data['gpsx']) && !empty($form_data['gpsy']);

			$issaved = TRUE;

			// save GPS if it is set
			if ($use_gps)
			{
			    $gpsx = doubleval($form_data["gpsx"]);
			    $gpsy = doubleval($form_data["gpsy"]);

			    if (gps::is_valid_degrees_coordinate($form->gpsx->value))
			    {
					$gpsx = gps::degrees2real($form->gpsx->value);
			    }

			    if (gps::is_valid_degrees_coordinate($form->gpsy->value))
			    {
					$gpsy = gps::degrees2real($form->gpsy->value);
			    }

			    // save
			    $issaved = $issaved && $ap->update_gps_coordinates(
						$ap->id, $gpsx, $gpsy
				);
			}
			
			$ap->name = $form_data['name'];

			// success
			if ($issaved && $ap->save())
			{
				status::success('Address point has been successfully updated.');
			}
			
			$this->redirect('address_points/show/', $ap->id);
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('address_points/show_all', 'Address points',
							$this->acl_check_view(get_class($this), 'address_point'))
					->disable_translation()
					->link('address_points/show/' . $ap->id, $ap->__toString(),
							$this->acl_check_view(get_class($this), 'address_point'))
					->enable_translation()
					->text('Fill in GPS')
					->html();

			// view
			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs;
			$view->title = __('Add address point');
			$view->content = new View('form');
			$view->content->headline = __('Editing of address point');
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Function deletes address point
	 * 
	 * @author Michal Kliment
	 * @param integer $address_point_id id of address point to delete
	 */
	public function delete($address_point_id = NULL)
	{
		// access rights
		if (!$this->acl_check_delete(get_class($this), 'address_point'))
			Controller::error(ACCESS);
		
		// no parameter
		if (!$address_point_id)
			Controller::warning(PARAMETER);

		$ap = new Address_point_Model($address_point_id);

		// record doesn't exist
		if (!$ap->id)
			Controller::error(RECORD);

		if (!$ap->count_all_items_by_address_point_id($address_point_id))
		{
			$ap->delete();
			
			status::success('Address point has been successfully deleted.');
		}
		else
		{
			status::warning('At least one item still has this address point.');
		}

		// redirect
		url::redirect('address_points/show_all');
	}

	/**
	 * Help AJAX function to fill GPS by street, street_id, town and country
	 *
	 * @author Michal Kliment, Ondřej Fibich
	 */
	public function get_gps_by_address()
	{
		$street_id = (int) $this->input->get('street_id');
		$street_number = (int) $this->input->get('street_number');
		$town_id = (int) $this->input->get('town_id');
		$country_id = (int) $this->input->get('country_id');

		// first try find in already exist address points
		$address_point = ORM::factory('address_point')
				->get_address_point_with_gps_by_street_street_number_town_country
		(
				$street_id, $street_number, $town_id, $country_id
		);

		// success, we end
		if ($address_point && strlen($address_point->gps))
		{
			echo $address_point->gps;
			return;
		}
		
		// try find by google API
		$street_model = new Street_Model($street_id);
		$town_model= new Town_Model($town_id);
		$country_model = new Country_Model($country_id);

		$street = ($street_model) ? $street_model->street : '';

		$town = '';
		if ($town_model && $town_model->id)
		{
			$town = $town_model->town;

			if ($town_model->quarter != '')
				$town .= " - ".$town_model->quarter;

			$town .= ", ".$town_model->zip_code;
		}

		$country = ($country_model) ? $country_model->country_name : '';

		if (!$street_number || $town == '' || $country == '')
			return;
		
		$data = self::get_geocode_from_nomanatim ($street, $street_number, $town, $country);

		if (!$data)
			return;

		/* return only precise GPS coordinates
		 *
		 * Valid location types: class=place
		 */
		if ($data[0]->class != "place")
			return;
		
		echo num::decimal_point($data[0]->lat)." ".num::decimal_point($data[0]->lon);
	}
	
	/**
	 * Help AJAX function to fill GPS by street, town, district, zip and country
	 *
	 * @author David Raška
	 */
	public function get_gps_by_address_string()
	{
		$country_id = (int) $this->input->get('country_id');
		$town = $this->input->get('town');
		$district = $this->input->get('district');
		$street = $this->input->get('street');
		$zip = $this->input->get('zip');
		
		$match = array();
		
		if (preg_match('((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', $street, $match))
		{
			// street
			$street = trim(preg_replace(' ((ev\.č\.)?[0-9][0-9]*(/[0-9][0-9]*[a-zA-Z]*)*)', '', $street));

			$number = $match[0];


			// first try find in already exist address points
			$address_point = ORM::factory('address_point')
					->get_address_point_with_gps_by_country_id_town_district_street_zip
			(
					$country_id, $town, $district, $street, $number, $zip
			);

			// success, we end
			if ($address_point && strlen($address_point->gps))
			{
				echo $address_point->gps;
				return;
			}

			// try find by google API
			$country_model = new Country_Model($country_id);
			$country = ($country_model) ? $country_model->country_name : '';

			if ($district)
			{
				$town .= ", $district";
			}
			
			$town .= ", $zip";

			if (!$number || $town == '' || $country == '')
				return;

			$data = self::get_geocode_from_nomanatim ($street, $number, $town, $country);

			if (!$data)
				return;

			/* return only precise GPS coordinates
			 *
			 * Valid location types: class=place
			 */
			if ($data[0]->class != "place")
				return;
			
			echo num::decimal_point($data[0]->lat)." ".num::decimal_point($data[0]->lon);
		}
	}
	
	/**
	 * Helper function to get geocode data from Google Map API
	 * 
	 * @author Michal Kliment
	 * @param type $street
	 * @param type $street_number
	 * @param type $town
	 * @param type $country
	 * @return type 
	 */
	private function get_geocode_from_nomanatim($street, $street_number, $town, $country)
	{
		$address = $street." ".$street_number.",".$town.",".$country;

		$opts = array(
			"http" => array(
				"method" => "GET",
				"header" => "Referer: " . url_lang::current() . "\r\n"
			)
		);

		$context = stream_context_create($opts);

		$URL = "http://nominatim.openstreetmap.org/?q=".urlencode($address)
				."&format=json&limit=1";

		$json = file_get_contents($URL, FALSE, $context);
		
		$data = json_decode($json);
		
		if (!$data ||
			!is_array($data) ||
			count($data) == 0 ||
			!is_object($data[0]) ||
			!isset($data[0]->lat) ||
			!isset($data[0]->lon))
		{
			return FALSE;
		}
		
		return $data;
	}
	
	/**
	 * Function to autocomplete empty GPS coords
	 * 
	 * @author Michal Kliment
	 */
	public function autocomplete_gps()
	{
		$headline = __('Autocomplete of GPS coords');
		
		// creates new form
		$form = new Forge('address_points/autocomplete_gps');
		
		$form->dropdown('method')
				->options(array
				(
					self::METHOD_OSM_NOMINATIM => __('OSM Nominatim')
				));
		
		$form->submit('Update');
		
		// form is validate
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			// Google method
			switch ($form_data['method'])
			{
				case self::METHOD_OSM_NOMINATIM:
					
					$address_point_model = new Address_point_Model();
					
					// finds all address points with empty GPS coords
					$address_points = $address_point_model
							->get_all_address_points_with_empty_gps();
					
					$updated = 0;
					foreach ($address_points as $address_point)
					{
						$town = $address_point->town;
						
						if ($address_point->quarter != '')
							$town .= ', '.$address_point->quarter;
						
						$town .= ', '.$address_point->zip_code;
						
						// finds gps from Nominatim
						$data = self::get_geocode_from_nomanatim (
								$address_point->street,
								$address_point->street_number,
								$town,
								$address_point->country_name
						);
		
						if (!$data)
							continue;

						/* return only precise GPS coordinates
						 *
						 * Valid location types: class=place
						 */
						if ($data[0]->class != "place")
							continue;
						
						// updates GPS coords
						$address_point_model->update_gps_coordinates(
								$address_point->id,
								num::decimal_point($data[0]->lat),
								num::decimal_point($data[0]->lon)
						);	
						$updated++;
		
					}
					
					status::success(__('It has been autocompleted %s GPS coords.', $updated), FALSE);
					url::redirect('address_points/show_all');
					
					break;
			}
		}
		
		$ai = '<b>!!! '.__('Warning').': '.__('This operation can take a long time').' !!!</b>';
		
		$view = new View('main');
		$view->title = $headline;
		$view->content = new View('form');
		$view->content->headline = $headline;
		$view->content->form = $form;
		$view->content->aditional_info = $ai;
		$view->render(TRUE);
	}
	
	/**
	 * Test if address point server is active
	 * 
	 * @return boolean
	 */
	public static function is_address_point_server_active()
	{
		static $address_point_server_active = NULL;
		
		if ($address_point_server_active === NULL)
		{
			if (Settings::get('address_point_url'))
			{
				$curl = new Curl_HTTP_Client();
				$result = $curl->fetch_url(Settings::get('address_point_url').'?mode=test');

				if ($curl->get_http_response_code() == 200 && $result !== FALSE)
				{
					// Address point server responses
					$result = json_decode($result);

					$address_point_server_active = ($result && $result->state);
				}
				else	// Address point server is not active
				{
					Log_queue_Model::error(
							'Error in address point validation: Server is not active'
					);

					$address_point_server_active = FALSE;
				}
			}
			else	// Address point server not set
			{
				$address_point_server_active = FALSE;
			}
		}
		
		return $address_point_server_active;
	}
	
	/**
	 * Test if given address point is valid
	 * 
	 * @return boolean
	 */
	public static function is_address_point_valid($country = NULL, $town = '', $district = '', $street = '', $zip = '')
	{
		$country = urlencode($country);
		$town = urlencode($town);
		$district = urlencode($district);
		$street = urlencode($street);
		$zip = urlencode($zip);
		
		$curl = new Curl_HTTP_Client();
		$result = $curl->fetch_url(Settings::get('address_point_url')."?country=$country&town=$town&street=$street&zip=$zip&district=$district&mode=validate");
		
		if ($curl->get_http_response_code() == 200 && $result !== FALSE)
		{
			$result = json_decode($result);
			
			return ($result && $result->state);
		}
		else
		{
			Log_queue_Model::error(
					'Error in address point validation: Cannot validate'
			);
			
			return FALSE;
		}
	}
}
