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

require_once dirname(__FILE__) . '/grid/Field.php';
require_once dirname(__FILE__) . '/grid/Action_field.php';
require_once dirname(__FILE__) . '/grid/Action_conditional_field.php';
require_once dirname(__FILE__) . '/grid/Callback_field.php';
require_once dirname(__FILE__) . '/grid/Grouped_action_field.php';
require_once dirname(__FILE__) . '/grid/Order_field.php';
require_once dirname(__FILE__) . '/grid/Order_callback_field.php';
require_once dirname(__FILE__) . '/grid/Link_field.php';
require_once dirname(__FILE__) . '/grid/Order_link_field.php';
require_once dirname(__FILE__) . '/grid/Form_field.php';
require_once dirname(__FILE__) . '/grid/Order_form_field.php';

/**
 * Grid for display data
 * 
 * @method Field Field(string $column)
 * @method Action_Field action_field(string $column)
 * @method Link_Field link_field(string $column, string $column2)
 * @method Order_Link_Field order_link_field(string $column, string $column2)
 * @method Order_Field order_field(string $column)
 * @method Order_callback_Field order_callback_field(string $column)
 * @method Callback_Field callback_field(string $column)
 * @method Grouped_Action_Field grouped_action_field(string $column)
 * @method Order_Form_Field order_form_field(string $column)
 */
class Grid
{

	protected $fields;
	protected $action_fields;
	protected $add_button = FALSE;
	protected $back_button = FALSE;
	protected $base_uri;
	protected $template = 'grid_template';
	protected $title;
	protected $label = NULL;
	protected $use_paginator = true;
	protected $use_selector = true;
	protected $separator = '<br /><br />';
	protected $show_labels = true;
	protected $selector_increace = 30;
	protected $selector_min = 10;
	protected $selector_max_multiplier = 5;
	protected $current = 10;
	protected $style = 'classic';
	protected $uri_segment = 3;
	protected $items_per_page = 10;
	protected $total_items = 0;
	protected $base_url = '';
	protected $order_by;
	protected $a_rec_per_page;
	protected $variables;
	protected $order_by_direction;
	protected $limit_results;
	protected $record_per_page = NULL;
	protected $url_array_ofset = 0;
	protected $query_string;
	protected $filter = '';
	protected $form = FALSE;
	protected $form_submit_value = '';
	protected $form_extra_buttons = array();
	protected $buttons = array();
	protected $id = NULL;
	protected $method = 'post';
	private $first_add_button;

	/**
	 * Grid construct
	 *
	 * @param type $base_uri
	 * @param type $title
	 * @param type $config
	 * @param type $template 
	 */
	public function __construct($base_uri, $title, $config = NULL, $template = FALSE)
	{
		if ($template)
		{
			$this->template = $template;
		}
		
		if (!text::starts_with($base_uri, url::base()))
		{
			$base_uri = url_lang::base() . $base_uri;
		}
		
		$this->template = new View($this->template);
		$this->template->base_uri = $this->base_uri = $base_uri;
		$this->template->title = $title;
		$this->template->label = (isset($config['total_items'])) ? __('Total items') . ': ' . $config['total_items'] : '';
		
		if (is_array($config))
		{
			$this->initialize($config);
		}
		
		// autoset id from url if empty
		if (!$this->id)		
			$this->id = str_replace(array('/','_'), '-', url_lang::current(2)).'-grid';

		$this->first_add_button = true;
	}

	/**
	 * Initialize grid with given config
	 *
	 * @param array $config 
	 */
	public function initialize($config = array())
	{
		// Assign config values to the object
		foreach ($config as $key => $value)
		{
			if (property_exists($this, $key))
			{
				$this->$key = $value;
			}
		}

		if ($this->use_paginator)
		{
			$this->pagination = new Pagination(array
			(
				'base_url'					=> $this->base_url,
				'uri_segment'				=> $this->uri_segment,
				'total_items'				=> $this->total_items,
				'items_per_page'			=> $this->items_per_page,
				'style'						=> $this->style
			));
		}

		if ($this->use_selector)
		{
			$this->selector = new Selector(array
			(
				'current'					=> $this->current,
				'selector_increace'			=> $this->selector_increace,
				'selector_min'				=> $this->selector_min,
				'selector_max_multiplier'	=> $this->selector_max_multiplier
			));
		}

		$this->template->show_labels = $this->show_labels;
	}

	/**
	 * Sets values
	 *
	 * @param string $key
	 * @param mixed $value 
	 */
	public function __set($key, $value)
	{
		$this->$key = $value;
	}

	/**
	 * Adds new button to the top of grid
	 *
	 * @param type $uri			URI of button
	 * @param type $label		Label of button
	 * @param type $options		Options
	 * @param type $help		Help hint
	 */
	public function add_new_button($uri, $label, $options = array(), $help = '')
	{
		$this->buttons[] = html::anchor(
				$uri, __($label), $options
		) . (($help == '') ? '' : '&nbsp;' . $help);
	}

	/**
	 * Adds back button to the top of grid
	 *
	 * @param string $label		Label of button 
	 */
	public function add_back_button($label)
	{
		$this->template->back_button = "<a href=\"javascript:history.go(-1)\">" . $label . "</a>";
	}

	/**
	 * Magic __call method. Creates a new form element object.
	 *
	 * @throws  Kohana_Exception
	 * @param   string   input type
	 * @param   string   input name
	 * @return  object
	 */
	public function __call($method, $args)
	{
		// Class name
		$field = ucfirst($method);
		// Create the input
		if ($field == 'Order_field' || $field == 'Order_callback_field' ||
			$field == 'Order_form_field' || $field == 'Order_link_field')
		{
			$arguments = array
			(
				'order_by'				=> $this->order_by,
				'order_by_direction'	=> $this->order_by_direction,
				'limit_results'			=> $this->limit_results,
				'record_per_page'		=> $this->record_per_page,
				'url_array_ofset'		=> $this->url_array_ofset,
				'variables'				=> $this->variables,
				'query_string'			=> $this->query_string,
				'use_selector'			=> $this->use_selector,
				'use_paginator'			=> $this->use_paginator
			);
			
			if (!isset($args[1]))
			{
				$args[1] = NULL;
			}
			
			$field = new $field($args[0], $args[1], $arguments);
		}
		else if ($field == 'Grouped_action_field')
		{
			$field = new Grouped_action_field(isset($args[0]) ? $args[0] : NULL);
		}
		else if ($field == 'Link_field')
		{
			$field = new $field($args[0], @$args[1]);
		}
		else
		{
			$field = new $field($args[0]);
		}

		if (!($field instanceof Field))
		{
			throw new Kohana_Exception('grige.unknown_field', get_class($field));
		}

		if ($field instanceof Grid_Actionfield)
		{
			$this->action_fields[] = $field;
		}
		else
		{
			$this->fields[] = $field;
		}

		if ($field instanceof Form_Field || $field instanceof Order_Form_Field)
		{
			$this->form = TRUE;
		}

		return $field;
	}

	/**
	 * Load satasource
	 *
	 * @param object $items 
	 */
	public function datasource($items)
	{
		$this->template->items = $items;
	}

	/**
	 * Renders grid
	 *
	 * @return string
	 */
	public function render()
	{
		$this->template->buttons = $this->buttons;
		$this->template->filter = $this->filter;
		$this->template->fields = $this->fields;
		$this->template->action_fields = $this->action_fields;
		$this->template->paginator = ($this->use_paginator) ? $this->pagination->create_links('digg') : '';
		$this->template->selector = ($this->use_selector) ? $this->selector->create() : '';
		$this->template->separator = $this->separator;
		$this->template->method = $this->method;
		$this->template->form = $this->form;
		$this->template->form_extra_buttons = $this->form_extra_buttons;
		$this->template->form_submit_value = ($this->form_submit_value != '') ? $this->form_submit_value : __('Update');
		$this->template->id = $this->id;
		return $this->template->render();
	}

	/**
	 * Renders grid
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}

}
