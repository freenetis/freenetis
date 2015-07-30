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
 * Action grid field
 * 
 * @method Action_Field name(string $name)
 * @method Action_Field script(string $script)
 */
class Action_Field extends Field
{	
	/**
	 * Name of column 
	 *
	 * @var string
	 */
	public $name = 'id';
	
	/**
	 * URL to action
	 *
	 * @var string
	 */
	public $url;
	
	/**
	 * Action
	 *
	 * @var string
	 */
	public $action = 'action';
	
	/**
	 * Extra script
	 *
	 * @var string
	 */
	public $script = null;
	
	/**
	 *
	 * @var string
	 */
	public $nextval = null;
	
	/**
	 * Image
	 *
	 * @var string
	 */
	public $img = null;
	
	
	/**
	 * Contruct of field, set label by its name with auto internationalization
	 *
	 * @param string $name	Name of field
	 */
	public function __construct($name)
	{
		$this->name = $name;
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
		$this->$method = $args[0];
		return $this;
	}
	
	/**
	 * Sets action
	 *
	 * @param string $action
	 * @return Action_Field 
	 */
	public function action($action)
	{
		$this->action = __($action);
		
		return $this;
	}
	
	/**
	 * Assign URL to action field, add URL base if there is no such
	 *
	 * @param string $url
	 * @return Action_Field 
	 */
	public function url($url)
	{
		if (!text::starts_with($url, url::base()))
		{
			$this->url = url_lang::base() . $url;
		}
		else
		{
			$this->url = $url;
		}
		
		return $this;
	}
	
	/**
	 * Adds icon action.
	 * Actions correspondes to icon names stared in /media/images/icons/grid_action
	 *
	 * @param string $action	Name of image (action)
	 * @return Action_Field 
	 */
	public function icon_action($action)
	{
		$this->img = $action;
		
		if (empty($this->label))
		{
			$this->label = ucfirst(__(str_replace('_', ' ', $action)));
		}
		
		return $this;
	}
	
	/**
	 * Renders field
	 *
	 * @return string
	 */
	public function render()
	{
		if (empty($this->img))
		{
			return $this->label;
		}
		else
		{
			return html::image(array
			(
				'src'		=> 'media/images/icons/grid_action/' . $this->img . '.png',
				'alt'		=> $this->label,
				'width'		=> 14,
				'height'	=> 14
			));
		}
	}
	
	/**
	 * Renders field
	 *
	 * @return string
	 */
	public function __toString()
	{
		return html::anchor($this->url.'/'.$this->name, ucfirst($this->action), $this->script);
	}
}
