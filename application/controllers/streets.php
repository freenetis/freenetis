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
 * Controller performs actions over streets.
 * 
 * @package Controller
 */
class Streets_Controller extends Controller  {

	/**
	 * Street ID for callback function
	 *
	 * @var integer
	 */
	private $street_id = 0;
	
	/**
	 * Form var for callbacks
	 *
	 * @var Forge
	 */
	private $form;
	
	/**
	 * For callback function only
	 * @var type 
	 */
	private $_town_id = NULL;

	/**
	 * Index redirects to show all
	 */
	public function index()
	{
		url::redirect('streets/show_all');
	}

	/**
	 * Function shows list of all streets.
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 50, $order_by = 'street',
			$order_by_direction = 'ASC', $page_word = null, $page = 1)
	{
		// access control
		if (!$this->acl_check_view('Address_points_Controller','street'))
			Controller::error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		// parameters control
		$allowed_order_type = array('id', 'street', 'town_id');
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'street';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		$street_model = new Street_Model();
		
		$total_streets = $street_model->count_all();
		
		if (($sql_offset = ($page - 1) * $limit_results) > $total_streets)
			$sql_offset = 0;

		$query = $street_model->get_all_streets(
				$sql_offset, (int) $limit_results,
				$order_by, $order_by_direction
		);

		// it creates grid to view all address points
		$grid = new Grid('streets', '', array
		(
			'current'					=> $limit_results,
			'selector_increace'			=> 50,
			'selector_min' 				=> 50,
			'selector_max_multiplier'   => 10,
			'base_url'					=> Config::get('lang').'/streets/show_all/'
										. $limit_results.'/'.$order_by.'/'.$order_by_direction ,
			'uri_segment'				=> 'page',
			'total_items'				=> $total_streets,
			'items_per_page' 			=> $limit_results,
			'style'		  				=> 'classic',
			'order_by'					=> $order_by,
			'order_by_direction'		=> $order_by_direction,
			'limit_results'				=> $limit_results,
		));

		if ($this->acl_check_new('Address_points_Controller', 'town'))
		{
			$grid->add_new_button('streets/add', __('Add new street'));
		}

		$grid->order_field('id')
				->label('ID');
		
		$grid->order_field('street');
		
		$grid->order_link_field('town_id')
				->link('towns/show', 'town');
		
		$actions = $grid->grouped_action_field();

		if ($this->acl_check_view('Address_points_Controller', 'street'))
		{
			$actions->add_action()
					->icon_action('show')
					->url('streets/show');
		}
		
		if ($this->acl_check_edit('Address_points_Controller', 'street'))
		{
			$actions->add_action()
					->icon_action('edit')
					->url('streets/edit');
		}
		
		if ($this->acl_check_delete('Address_points_Controller', 'street'))
		{
			$actions->add_action()
					->icon_action('delete')
					->url('streets/delete')
					->class('delete_link');
		}

		$grid->datasource($query);

		$links = array();
		$links[] =  html::anchor(
				url_lang::base().'address_points',
				__('Address points')
		);
		$links[] = html::anchor(
				url_lang::base().'towns',
				__('Towns')
		);
		$links[] = __('Streets');

		$view = new View('main');
		$view->breadcrumbs = __('Streets');
		$view->title = __('List of all streets');
		$view->content = new View('show_all');
		$view->content->submenu = implode(' | ', $links);
		$view->content->headline = __('List of all streets');
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function shows street.
	 * 
	 * @author Michal Kliment
	 * @param integer $street_id id of street to show
	 */
	public function show($street_id = NULL)
	{
		// no parameter
		if (!$street_id)
			Controller::warning(PARAMETER);

		$street = new Street_Model($street_id);

		// record doesn't exist
		if (!$street->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_view('Address_points_Controller','street'))
			Controller::error(ACCESS);

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('streets/show_all', 'Streets',
						$this->acl_check_view('Address_points_Controller', 'street'))
				->disable_translation()
				->text($street->street);

		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = __('Street detail');
		$view->content = new View('streets/show');
		$view->content->street = $street;
		$view->content->count_address_points = count($street->address_points);
		$view->render(TRUE);

	}

	/**
	 * Function adds new street.
	 * 
	 * @author Michal Kliment
	 */
	public function add($town_id = NULL)
	{
		// add street to given town
		if ($town_id)
		{
			// bad parameter
			if (!is_numeric($town_id))
				Controller::warning (PARAMETER);
			
			$town = new Town_Model($town_id);
			
			// town doesn't exist
			if (!$town->id)
				Controller::error (RECORD);
			
			$this->_town_id = $town->id;
		}
		
		// access control
		if (!$this->acl_check_new('Address_points_Controller','street'))
			Controller::error(ACCESS);

		// creates new form
		$this->form = $form = new Forge();
		
		if (!$town_id)
		{
			$this->form->dropdown('town_id')
					->label(__('Town').':')
					->rules('required')
					->options(ORM::factory('town')->select_list_with_quater());
		}

		$this->form->input('street')
				->label(__('Street').':')
				->rules('required|length[1,50]')
				->callback(array($this, 'check_street'));

		$this->form->submit('Add');

		// form is validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			$street = new Street_Model();
			$street->street = $form_data['street'];
			
			if ($town_id)
				$street->town_id = $town_id;
			else
				$street->town_id = $form_data['town_id'];

			if ($street->save())
			{
				status::success('Street has been successfully added.');
			}
			else
			{
				$street = NULL;
			}
			
			$this->redirect('streets/show_all', $street->id);
		}
		else
		{
			// breadcrumbs navigation
			$breadcrumbs = breadcrumbs::add()
					->link('streets/show_all', 'Streets',
							$this->acl_check_view('Address_points_Controller', 'street'))
					->text('Add new street');
			
			$title = $town_id ? __('Add new street to town').' '.$town : __('Add new street');

			$view = new View('main');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->title = $title;
			$view->street = isset($street) && $street->id ? $street : NULL;
			$view->content = new View('form');
			$view->content->headline = $title;
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Function edits street.
	 * 
	 * @author Michal Kliment
	 * @param integer $street_id if of street to edit
	 */
	public function edit($street_id = NULL)
	{
		// no parameter
		if (!$street_id)
			Controller::warning(PARAMETER);

		$street = new Street_Model($street_id);

		// record doesn't exist
		if (!$street->id)
			Controller::error(RECORD);

		// access control
		if (!$this->acl_check_edit('Address_points_Controller','street'))
			Controller::error(ACCESS);

		// saving for callback function
		$this->street_id = $street->id;

		// creates new form
		$this->form = $form = new Forge('streets/edit/'.$street->id);

		$this->form->dropdown('town_id')
				->label(__('Town').':')
				->rules('required')
				->options(ORM::factory('town')->select_list_with_quater())
				->selected($street->town_id);

		$this->form->input('street')
				->label(__('Street').':')
				->rules('required|length[1,50]')
				->value($street->street)
				->callback(array($this, 'check_street'));

		$this->form->submit('Edit');


		// form is validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();
			
			$street->street = $form_data['street'];
			$street->town_id = $form_data['town_id'];

			if ($street->save())
			{
				status::success('Street has been successfully updated.');
			}
			
			url::redirect('streets/show_all');
		}

		// breadcrumbs navigation
		$breadcrumbs = breadcrumbs::add()
				->link('streets/show_all', 'Streets',
						$this->acl_check_view('Address_points_Controller', 'street'))
				->link('streets/show/' . $street->id, $street->street,
						$this->acl_check_view('Address_points_Controller', 'street'))
				->text('Edit');

		$view = new View('main');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->title = __('Edit street');
		$view->content = new View('form');
		$view->content->headline = __('Editing of street');
		$view->content->form = $form->html();
		$view->render(TRUE);
	}

	/**
	 * Function deletes town.
	 * 
	 * @author Michal Kliment
	 * @param integer $street_id id of street to delete
	 */
	public function delete($street_id = NULL)
	{
		// no parameter
		if (!$street_id)
			Controller::warning(PARAMETER);

		$street = new Street_Model($street_id);

		// record doesn't exist
		if (!$street->id)
		{
			Controller::error(RECORD);
		}

		// access control
		if (!$this->acl_check_delete('Address_points_Controller', 'street'))
		{
			Controller::error(ACCESS);
		}

		if ($street->address_points->count() == 0)
		{
			if ($street->delete())
			{
				status::success('Street has been successfully deleted.');
			}
			else
			{
				status::error('Error - cant delete street.');
			}
		}
		else
		{
			status::warning('At least one address point uses this street.');
		}

		url::redirect('streets/show_all');
	}

	/**
	 * Function checks if street already exist.
	 *
	 * @author Michal Kliment, Ondřej fibich
	 * @param object $input
	 */
	public function check_street($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$values = $this->form->as_array();
		
		$town_id = ($this->_town_id) ? $this->_town_id : $values['town_id'];
		
		$streets_count = ORM::factory('street')->where(array
		(
			'street'	=> $values['street'],
			'town_id'	=> $town_id,
			'id <>'		=> $this->street_id
		))->count_all();

		if ($streets_count)
		{
			$input->add_error('required', '');
			status::warning('Street already exists.');
		}
	}

}
