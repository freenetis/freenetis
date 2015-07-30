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
 * Grid field
 * 
 * @method Field name(string $name)
 * @method Field bool(bool $bool)
 * @method Field class(string $class)
 * @method Field order(bool $order)
 * @method Field style(string $style)
 * @method Field help(string $help)
 * @method Field args(string $array)
 */
class Field
{
	/**
	 * Label
	 *
	 * @var string 
	 */
	public $label;
	
	/**
	 * Name
	 *
	 * @var string 
	 */
	public $name;
	
	/**
	 * Bool
	 *
	 * @var bool 
	 */
	public $bool;
	
	/**
	 * Class
	 *
	 * @var string 
	 */
	public $class;
	
	/**
	 * Order
	 *
	 * @var bool
	 */
	public $order = true;
	
	/**
	 * Style
	 *
	 * @var string 
	 */
	public $style;
	
	/**
	 * Help hint
	 *
	 * @var string 
	 */
	public $help;
	
	/**
	 * Args
	 *
	 * @var array 
	 */
	public $args;
	
	/**
	 * Contruct of field, set label by its name with auto internationalization
	 *
	 * @param string $name	Name of field
	 */
	public function __construct($name)
	{
		$this->name = $name;
		$this->label = __(inflector::humanize($name), '', 2);
	}
	
	/**
	 * Call method (sets properties)
	 *
	 * @param string $method
	 * @param array $args
	 * @return Field
	 */
	public function __call($method, $args)
	{	
		$this->$method = array_shift($args);
		$this->args[$method] = $args;
		
		return $this;
	}
	
	/**
	 * Sets label of field
	 *
	 * @param string $label		New label with auto internationalization
	 * @return Field
	 */
	public function label($label)
	{
		// is there any HTML tag in label?
		if (preg_match("/<.*>/", $label))
		{
			$this->label = $label;
		}
		else
		{
			$this->label = __($label);
		}
		
		return $this;
	}
	
	/**
	 * Render field
	 *
	 * @return string
	 */
	public function render()
	{
		return '<strong>'.ucfirst($this->label).'</strong>';
	}
	
	/**
	 * Render field
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->name;
	}
}
