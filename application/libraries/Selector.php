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
 * Selector library.
 *
 * @author Danek Petr
 */
class Selector
{

	// Config values
	protected $selector_increace = 30;
	protected $selector_min = 10;
	protected $selector_max_multiplier = 5;
	protected $current = 10;
	protected $base_url = NULL;

	/**
	 * Constructs the Selector object.
	 *
	 * @param   array  configuration
	 * @return  void
	 */
	public function __construct($config = array())
	{
		// Load configuration
		$this->initialize($config);

		Log::add('debug', 'Selector Library initialized');
	}

	/**
	 * Sets or overwrites (some) config values.
	 *
	 * @param   array  configuration
	 * @return  void
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
	}

	/**
	 * Generates the HTML of selector type.
	 *
	 * @return  string  selector html
	 */
	public function create()
	{
		$view = new View('selector');

		$view->base_url = ($this->base_url) ? $this->base_url : url_lang::base() . url_lang::current();

		$sel_values_array = array();

		for ($i = 0; $i < $this->selector_max_multiplier; $i++)
		{
			$index = ($i * $this->selector_increace) + $this->selector_min;
			$sel_values_array[$index] = ($i * $this->selector_increace) + $this->selector_min;
		}

		$view->sel_values_array = $sel_values_array;
		$view->current = $this->current;
		return $view->render();
	}

	public function __toString()
	{
		return $this->create();
	}

} // End Selector Class