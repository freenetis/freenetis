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
 * Order grid field
 * 
 * @method Order_Field direction(string $direction)
 * @method Order_Field direction_label(string $direction_label)
 * @method Order_Field return_link(string $return_link)
 * @method Order_Field use_selector(bool $use_selector)
 * @method Order_Field use_paginator(bool $direction)
 */
class Order_Field extends Field
{
	/**
	 * Direction
	 *
	 * @var string
	 */
	public $direction;
	
	/**
	 * Direction label
	 *
	 * @var string
	 */
	public $direction_label;
	
	/**
	 * Return link
	 *
	 * @var string
	 */
	public $return_link;
	
	/**
	 * Use selector
	 *
	 * @var boolean
	 */
	protected $use_selector = true;
	
	/**
	 * Use paginator
	 *
	 * @var boolean
	 */
	protected $use_paginator = true;

	/**
	 * Contruct of order field
	 *
	 * @param string $name
	 * @param string $new_order
	 * @param array $arguments 
	 */
	public function __construct($name, $new_order, $arguments)
	{
		parent::__construct($name);
		
		if (!isset($new_order))
			$new_order = $name;

		$this->use_selector = $arguments['use_selector'];
		$this->use_paginator = $arguments['use_paginator'];

		$this->create_order_by_link(
				$new_order, $arguments['order_by'],
				$arguments['order_by_direction'],
				$arguments['limit_results'],
				$arguments['url_array_ofset'],
				$arguments['variables'],
				$arguments['query_string']
		);
	}

	/**
	 * Created order by link
	 *
	 * @param string $new_order_by
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param mixed $record_per_page
	 * @param int $url_array_ofset
	 * @param string $variables
	 * @param string $query_string 
	 */
	private function create_order_by_link(
			$new_order_by = 'users.id', $order_by = 'users.id',
			$order_by_direction = 'ASC', $record_per_page = NULL,
			$url_array_ofset = 0, $variables = '', $query_string = '')
	{
		$url_array = explode('/', trim(url::current(), '/'));
		
		if (count($url_array) < 7)
		{
			$url_array[3] = 50;
			$url_array[6] = 'page';
			$url_array[7] = 1;
			$url_array_ofset = 0;
		}
		
		if (isset($record_per_page))
		{
			$url_array[3] = (int) $record_per_page;
		}

		$pre_url = $url_array[1] . '/' . $url_array[2] . '/' . $variables;
		$pre_url .= ( $this->use_selector) ? $url_array[3 + $url_array_ofset] . '/' : '';
		
		if ($new_order_by == $order_by)
		{
			$order_by_direction = strtoupper($order_by_direction) == 'ASC' ? 'DESC' : 'ASC';
		}
		else
		{
			$order_by_direction = strtoupper($order_by_direction);
		}
		
		$this->return_link = url_lang::base() . $pre_url . $new_order_by . '/' . $order_by_direction . '/';
		$this->return_link .= ($this->use_paginator) ? $url_array[6 + $url_array_ofset] . '/' . $url_array[7 + $url_array_ofset] : '';
		
		if (server::query_string() != '')
		{
			$this->return_link .= server::query_string();
		}
	}

}
