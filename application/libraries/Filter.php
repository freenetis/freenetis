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
 * This is library for filter form
 * 
 * @author Michal Kliment
 * @version 1.0
 * 
 * @method Filter type(string $type)
 * @method Filter table(string $sql_table_name)
 * @method Filter callback(string $url_autocomplete)
 * @method Filter values(array $select_type_values)
 * @method Filter default(integer $operation, mixed $value)
 * @method Filter css_class(string $class_name)	CSS class of value field of form
 */
class Filter
{
	/**
	 * Basic data of object
	 * @var array
	 */
	protected $data = array
	(
		'type' => 'text',
		'default' => array(),
		'class' => array(),
		'css_class' => array()
	);

	/**
	 * Constructor, sets name, label and name of table (optional)
	 *
	 * @author Michal Kliment
	 * @param string $name
	 * @param string $table
	 */
	public function __construct($name, $table = '')
	{
		$this->data['name'] = $name;
		$this->data['label'] = url_lang::lang('texts.'.utf8::ucwords(inflector::humanize($name)));
		$this->data['table'] = $table;
	}

	/**
	 * Magic method to return some from basic data
	 *
	 * @author Michal Kliment
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get($key)
	{
		if (isset ($this->data[$key]))
		{
			return $this->data[$key];
		}
	}

	/**
	 * Method to set label of filter
	 * 
	 * @author Michal Kliment
	 * @param string $label
	 * @param boolean $use_translation
	 * @return Filter 
	 */
	public function label($label, $use_translation = TRUE)
	{
		if ($use_translation)
			$this->data['label'] = url_lang::lang('texts.'.$label);
		else
			$this->data['label'] = $label;

		return $this;
	}

	/**
	 * Magic method to set some basic data
	 *
	 * @author Michal Kliment
	 * @param string $method
	 * @param array $args
	 * @return Filter object
	 */
	public function __call($method, $args)
	{
		// cannot modify name
		if ($method == 'name')
		{
			// do nothing
		}
		// stores default values
		else if ($method == 'default')
		{
			$this->data[$method][] = array('oper' => $args[0], 'value' => $args[1]);
		}
		else if ($method == 'css_class')
		{
			$this->data[$method] = array_merge($this->data[$method], array($args[0]));
		}
		else
		{
			$this->data[$method] = $args[0];
		}
		
		if ($method == 'type')
		{
			$this->data['class'] = array_merge($this->data['class'], array($args[0]));
		}
		
		return $this;
	}
}
