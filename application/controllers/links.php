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
 * Controller performs actions over links of network.
 * 
 * @package Controller
 */
class Links_Controller extends Controller
{

	/**
	 * ID of working link
	 *
	 * @var integer
	 */
	private $link_id = NULL;
	
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
	 * Redirects to show all
	 */
	public function index()
	{
		url::redirect('links/show_all');
	}

	/**
	 * Function shows all links.
	 * 
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all($limit_results = 50, $order_by = 'id',
			$order_by_direction = 'asc', $page_word = null, $page = 1)
	{
		
		if (!$this->acl_check_view('Links_Controller', 'link'))
			Controller::error(ACCESS);
		
		// get new selector
		if (is_numeric($this->input->post('record_per_page')))
				$limit_results = (int) $this->input->post('record_per_page');
		
		$link_model = new Link_Model();
		
		$filter_form = new Filter_form('s');
		
		$filter_form->add('name')
			->callback('json/link_name');
		
		$filter_form->add('medium')
			->type('select')
			->values($link_model->get_medium_types());
		
		$filter_form->add('bitrate')
			->type('number');
		
		$filter_form->add('duplex')
			->type('select')
			->values(arr::rbool());
		
		$filter_form->add('comment');
		
		$filter_form->add('items_count')
			->type('number');
		
		$filter_form->add('wireless_norm')
			->label('norm')
			->type('select')
			->values(Link_Model::get_wireless_norms());
		
		$filter_form->add('ssid')
			->label('SSID')
			->callback('json/ssid');
		
		// model
		$link_model = new Link_Model();
		$total_links = $link_model->count_all_links($filter_form->as_sql());
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_links)
			$sql_offset = 0;
		
		$links = $link_model->get_all_links(
				$sql_offset, $limit_results, $order_by,
				$order_by_direction, $filter_form->as_sql()
		);
		
		$grid = new Grid('links', null, array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 10,
			'base_url'					=> Config::get('lang').'/links/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=>  $total_links,
			'items_per_page' 			=> $limit_results,
			'style'						=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
			'filter'					=> $filter_form
		)); 

		if ($this->acl_check_new('Links_Controller', 'link'))
		{
			$grid->add_new_button('links/add', 'Add new link'); 
		}
		
		$grid->order_field('id')
				->label('ID')
				->class('center');
		
		$grid->order_field('name');
		
		$grid->order_callback_field('medium')
				->callback('callback::link_medium_field');
		
		$grid->order_callback_field('bitrate')
				->callback('callback::bitrate_field');
		
		$grid->order_field('duplex')
				->bool(arr::rbool())
				->class('center');
		
		$grid->order_field('items_count');
		
		$grid->order_field('ssid')
				->label('SSID');
		
		$grid->order_callback_field('wireless_norm')
				->label('norm')
				->callback('callback::wireless_link_norm');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Links_Controller', 'link'))
		{
			$actions->add_action()
					->icon_action('show')
					->url('links/show');
		}
		
		if ($this->acl_check_edit('Links_Controller', 'link'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('links/edit');
		}
		
		if ($this->acl_check_delete('Links_Controller', 'link'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('links/delete')
					->class('delete_link');
		}
		
		$grid->datasource($links); 

		$view = new View('main');
		$view->breadcrumbs = __('Links');
		$view->title = __('Links list');
		$view->content = new View('show_all');
		$view->content->table = $grid;  	
		$view->content->headline = __('Links list');
		$view->render(TRUE);
	} // end of show_all

	/**
	 * Function shows link information.
	 * 
	 * @param integer $link_id
	 */
	public function show($link_id = null)
	{
		if (!$link_id || !is_numeric($link_id))
			Controller::warning(PARAMETER);
			
		$link = new Link_Model($link_id);
		
		if (!$link || !$link->id)
			Controller::error(RECORD);
		
		if (!$this->acl_check_view('Links_Controller', 'link'))
			Controller::error(ACCESS);
		
		$duplex_value = arr::rbool();
		
		$duplex = $duplex_value[(bool) $link->duplex];
		
		// link media
		$medium = Link_Model::get_medium_type($link->medium);
		
		$headline = __('Interfaces');
		
		if ($link->medium != Link_Model::MEDIUM_AIR)
			$headline .= ' / ' . __('Ports');
		
		$grid = new Grid('subnets/show/'.$link_id, $headline, array
		(
			'use_paginator'	=> false,
			'use_selector'	=> false
		));
		
		if ($link->medium == Link_Model::MEDIUM_ROAMING &&
			$this->acl_check_edit('Ifaces_Controller', 'iface'))
		{
			$grid->add_new_button(
					'ifaces/add_iface_to_link/' . $link->id,
					__('Append interface to link'),
					array('class' => 'popup_link')
			);
		}
		
 		$grid->field('id')
				->label('ID');
		
		$grid->callback_field('name')
				->label('Name')
				->callback('callback::link_item_field');
		
		$grid->callback_field('type')
				->label('Type')
				->callback('callback::iface_type_field');
			
		if ($link->medium == Link_Model::MEDIUM_AIR)
		{
			$grid->callback_field('wireless_mode')
				->label('Mode')
				->callback('callback::wireless_mode');
		}
		
		$grid->field('mac')
				->label('MAC');
		
		$grid->callback_field('device_id')
				->label('Device name')
				->callback('callback::device_field');
		
		$grid->callback_field('member_id')
				->label('Member')
				->callback('callback::member_field');
		
		$actions = $grid->grouped_action_field();
		
		if ($this->acl_check_view('Ifaces_Controller', 'iface'))
		{
			$actions->add_action()
					->icon_action('show')
					->url('ifaces/show');
		}
		
		if ($this->acl_check_edit('Ifaces_Controller', 'iface'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('ifaces/edit');
		}
		
		if ($this->acl_check_edit('Ifaces_Controller', 'link'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('ifaces/remove_from_link')
					->class('delete_link')
					->label('Remove interface from link');
		}
		
		
		$grid->datasource($link->get_items());

		$breadcrumbs = breadcrumbs::add()
				->link('links/show_all', 'Links',
						$this->acl_check_view('Links_Controller', 'link'))
				->disable_translation()
				->text($link->name . ' (' . $link->id . ')');

		$links = array();
		
		if($this->acl_check_edit('Links_Controller', 'link'))
		{
			$links[] = html::anchor('links/edit/'.$link->id, __('Edit'));
		}
		
		if($this->acl_check_delete('Links_Controller', 'link'))
		{
			$links[] = html::anchor(
					'links/delete/'.$link->id, __('Delete'),
					array('class' => 'delete_link')
			);
		}
		
		$headline = __('Link detail').' - '.$link->name;
		
		$view = new View('main');
		$view->title = $headline;
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('links/show');
		$view->content->link = $link;
		$view->content->duplex = $duplex;
		$view->content->medium = $medium;
		$view->content->grid = $grid;
		$view->content->links = implode(' | ',$links);
		$view->content->headline = $headline;
		$view->render(TRUE);
	} // end of show
	
	/**
	 * Function adds link.
	 * 
	 * @param integer $iface_type	Pre-fill available mediums by type [optional]
	 */
	public function add($iface_type = NULL) 
	{
		if(!$this->acl_check_new('Links_Controller', 'link'))
		{
			Controller::error(ACCESS);
		}
		
		$roaming = ORM::factory('link')->get_roaming();
		$medias = array();
		$selected_medium = 1;
		
		if (is_numeric($iface_type))
		{
			$all_mediums = Iface_Model::get_types_has_link_with_medium($iface_type);
			
			if ($iface_type == Iface_Model::TYPE_PORT ||
				$iface_type == Iface_Model::TYPE_ETHERNET)
			{
				$selected_medium = Link_Model::MEDIUM_CABLE;
			}
			else if ($iface_type == Iface_Model::TYPE_WIRELESS)
			{
				$selected_medium = Link_Model::MEDIUM_AIR;
			}
		}
		else
		{
			$all_mediums = Link_Model::get_medium_types();
		}
		
		foreach ($all_mediums as $id => $type)
		{
			if ($id != Link_Model::MEDIUM_ROAMING || !$roaming)
			{
				$medias[$id] = $type;
			}
		}
		
		asort($medias);
		
		$medias = array
		(
			NULL => '----- '.__('Select medium').' -----'
		) + $medias;
		
		$wireless_norms = array
		(
			NULL => '----- '.__('Select norm').' -----'
		) + Link_Model::get_wireless_norms();
		
		$polarizations = array
		(
			NULL => '----- '.__('Select polarization').' -----'
		) + Link_Model::get_wireless_polarizations();
		
		$arr_unit = array
		(
			'1'				=> 'bps',
			'1024'			=> 'kbps',
			'1048576'		=> 'Mbps',
			'1073741824'	=> 'Gbps'
		);
			
		$form = new Forge();

		$form->group('Basic data');
		
		$form->input('name')
				->style('width: 600px');
		
		$form->dropdown('medium')
				->options($medias)
				->rules('required')
				->selected($selected_medium)
				->callback(array($this, 'valid_medium'));
		
		$form->input('bitrate')
				->rules('valid_numeric')
				->class('join1')
				->style('width:100px; margin-right:5px;');
		
		$form->dropdown('bit_unit')
				->options($arr_unit)
				->class('join2')
				->selected(1048576)
				->callback(array($this, 'valid_bitrate'));
		
		$form->checkbox('duplex')
				->label('Duplex')
				->value('1');
		
	   	$form->textarea('comment')
				->rules('length[0,254]')
				->cols('20')
				->rows('5');
		
		$form->group('Wireless setting');
		
		$form->input('wireless_ssid')
				->label('SSID');
		
		$form->dropdown('wireless_norm')
				->label('Norm')
				->options($wireless_norms)
				->style('width: 200px');
		
		$form->input('wireless_frequency')
				->label('Frequency');
		
		$form->input('wireless_channel')
				->label('Channel');
		
		$form->input('wireless_channel_width')
				->label('Channel width');
		
		$form->dropdown('wireless_polarization')
				->label('Polarization')
				->options($polarizations)
				->style('width: 200px');
		
		$form->submit('Save');
		
		if($form->validate())
		{
			$form_data = $form->as_array();
			
			$link_model = new Link_Model();
			$link_model->name = $form_data['name'];
			$link_model->medium = $form_data['medium'];
			$link_model->bitrate = $form_data['bitrate'] * $form_data['bit_unit'];
			$link_model->duplex = $form_data['duplex'];
			$link_model->comment = $form_data['comment'];
			
			if ($link_model->medium == Link_Model::MEDIUM_AIR)
			{
				$link_model->wireless_ssid = $form_data['wireless_ssid'];
				$link_model->wireless_norm = $form_data['wireless_norm'];
				$link_model->wireless_frequency = $form_data['wireless_frequency'];
				$link_model->wireless_channel = $form_data['wireless_channel'];
				$link_model->wireless_channel_width = $form_data['wireless_channel_width'];
				$link_model->wireless_polarization = $form_data['wireless_polarization'];
			}

			unset($form_data);

			$link_model->save();
				
			status::success('Link has been successfully saved.');
			
			$this->redirect('links/show/', $link_model->id);
		}
		else
		{
			$headline = __('Add new link');
			
			$breadcrumbs = breadcrumbs::add()
					->link('links/show_all', 'Links',
							$this->acl_check_view('Links_Controller', 'link'))
					->text($headline);

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $headline;
			$view->content = new View('form');
			$view->content->form = $form->html();
			$view->content->link_back = '';
			$view->content->headline = $headline;
			$view->render(TRUE);
		}
	} // end of add
	
	/**
	 * Function edits link.
	 * 
	 * @param integer $link_id
	 */
	public function edit($link_id = null) 
	{
		if (!$link_id || !is_numeric($link_id))
			Controller::warning(PARAMETER);
			
		$link = new Link_Model($link_id);
		
		if (!$link || !$link->id)
			Controller::warning(ACCESS);
		
		if(!$this->acl_check_edit('Links_Controller', 'link'))
			Controller::error(ACCESS);
		
		$this->link_id = $link_id;

		$roaming = ORM::factory('link')->get_roaming();

		$media = array();
		
		foreach (Link_Model::get_medium_types() as $id => $type)
		{
			if ($roaming == $link->id || $id != Link_Model::MEDIUM_ROAMING || !$roaming)
			{
				$media[$id] = $type;
			}
		}
		asort($media);
		
		$media = array
		(
			NULL => '----- '.__('Select medium').' -----'
		) + $media;
		
		$polarizations = array
		(
			NULL => '----- '.__('Select polarization').' -----'
		) + Link_Model::get_wireless_polarizations();
		
		$norms = array
		(
			NULL => '----- '.__('Select norm').' -----'
		) + Link_Model::get_wireless_norms();
		
		$arr_unit = array
		(
			'1'				=> 'bps',
			'1024'			=> 'kbps',
			'1048576'		=> 'Mbps',
			'1073741824'	=> 'Gbps'
		);
		
		// form
		$form = new Forge('links/edit/'.$link_id);
		
		$form->group('Basic data');
		
		$form->input('name')
				->style('width: 600px')
				->value($link->name);
		
		$form->dropdown('medium')
				->options($media)
				->rules('required')
				->selected($link->medium)
				->callback(array($this, 'valid_medium'));
		
		$form->input('bitrate')
				->rules('valid_numeric')
				->value($link->bitrate / 1048576)
				->class('join1')
				->style('width:100px; margin-right:5px;');
		
		$form->dropdown('bit_unit')
				->options($arr_unit)
				->class('join2')
				->selected(1048576)
				->callback(array($this, 'valid_bitrate'));
		
		$form->checkbox('duplex')
				->label('Duplex')
				->value('1')
				->checked($link->duplex);
		
	   	$form->textarea('comment')
				->rules('length[0,254]')
				->value($link->comment)
				->cols('20')
				->rows('5');
		
		$form->group('Wireless setting');
		
		$form->input('wireless_ssid')
				->label('SSID')
				->value($link->wireless_ssid);
		
		$form->dropdown('wireless_norm')
				->label('Norm')
				->options($norms)
				->selected($link->wireless_norm)
				->style('width: 200px');
		
		$form->input('wireless_frequency')
				->label('Frequency')
				->value($link->wireless_frequency);
		
		$form->input('wireless_channel')
				->label('Channel')
				->value($link->wireless_channel);
		
		$form->input('wireless_channel_width')
				->label('Channel width')
				->value($link->wireless_channel_width);
		
		$form->dropdown('wireless_polarization')
				->label('Polarization')
				->options($polarizations)
				->selected($link->wireless_polarization)
				->style('width: 200px');
		
		$form->submit('submit')
				->value(__('Update'));
		
		// validation
		if($form->validate())
		{
			$form_data = $form->as_array();
			
			$link->name = $form_data['name'];
			$link->medium = $form_data['medium'];
			$link->bitrate = $form_data['bitrate'] * $form_data['bit_unit'];
			$link->duplex = $form_data['duplex'];
			$link->comment = $form_data['comment'];
			
			if ($link->medium == Link_Model::MEDIUM_AIR)
			{
				$link->wireless_ssid = $form_data['wireless_ssid'];
				$link->wireless_norm = $form_data['wireless_norm'];
				$link->wireless_frequency = $form_data['wireless_frequency'];
				$link->wireless_channel = $form_data['wireless_channel'];
				$link->wireless_channel_width = $form_data['wireless_channel_width'];
				$link->wireless_polarization = $form_data['wireless_polarization'];
			}
						
			unset($form_data);
			
			if ($link->save())
			{
				status::success('Link has been successfully updated.');
	 			url::redirect('links/show/'.$link->id);
			}
			
		}
		
		$breadcrumbs = breadcrumbs::add()
				->link('links/show_all', 'Links',
						$this->acl_check_view('Links_Controller', 'link'))
				->link('links/show/' . $link->id,
						$link->name . ' (' . $link->id . ')',
						$this->acl_check_view('Links_Controller', 'link'))
				->text('Edit');

		$headline = __('Edit link') . ' - ' . $link->name;
		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = $headline;
		$view->content = new View('form');
		$view->content->form = $form->html();
		$view->content->link_back = '';
		$view->content->headline = $headline;
		$view->render(TRUE);
	} // end of edit

	/**
	 * Function deletes link
	 *
	 * @author Michal Kliment
	 * @param integer $link_id
	 */
	public function delete($link_id = NULL)
	{
		// access control
		if (!$this->acl_check_delete('Links_Controller', 'link'))
			Controller::error(ACCESS);

		// bad parameter
		if (!$link_id || !is_numeric($link_id))
			Controller::warning(PARAMETER);

		$link = new Link_Model($link_id);

		// record doesn't exist
		if (!$link->id)
			Controller::error(RECORD);

		// interface and port test
		if (count($link->ifaces))
		{
			status::warning('At least one interface or port still uses this link.');
			url::redirect(Path::instance()->previous());
		}

		// successfully deleted
		if ($link->delete())
		{
			status::success('Link has been successfully deleted.');
		}

		if (url::slice(url_lang::uri(Path::instance()->previous()), 0, 2) == 'links/show')
		{
			url::redirect('links/show_all');
		}
		else
		{
			url::redirect(Path::instance()->previous());
		}
	}

	/**
	 * Callback function to valid medium of segment
	 *
	 * @author Michal Kliment
	 * @param object $input
	 */
	public function valid_medium($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$roaming = ORM::factory('link')->get_roaming();

		if ($this->input->post('medium') == Link_Model::MEDIUM_ROAMING &&
			$roaming && ($this->link_id && $roaming != $this->link_id))
		{
			$input->add_error('required', __(
					'Link with roaming can exists only once.'
			));
		}
	}

	/**
	 * Callback function to valid bitrate of segment
	 *
	 * @author Michal Kliment
	 * @param object $input
	 */
	public function valid_bitrate($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if ($this->input->post('medium') != Link_Model::MEDIUM_ROAMING &&
			$this->input->post('bitrate') == '')
		{
			$input->add_error('required', __('Bitrate is required.'));
		}
		
		$norm = $this->input->post('wireless_norm');
		
		if ($this->input->post('medium') == Link_Model::MEDIUM_AIR && $norm &&
			($max_bitrate = Link_Model::get_wireless_max_bitrate($norm)))
		{
			$bitrate = $this->input->post('bitrate') * $this->input->post('bit_unit');
			
			if ($bitrate > ($max_bitrate * 1024 * 1024))
			{
				$input->add_error('required', __(
						'Bit rate is invalid - it has to be smaller or equal ' .
						'to max bit rate of choosen wireless norm.'
				));
			}
		}
	}

}
