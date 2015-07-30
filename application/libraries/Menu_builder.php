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
 * This is library for menu building
 * 
 * @author Michal Kliment
 * @version 1.0
 */
class Menu_builder
{
	/**
	 * Template to show menu 
	 * @var string 
	 */
	protected $template = 'menu';
	
	/**
	 * Array with menu groups (eg.Networks)
	 * @var array
	 */
	protected $groups = array();
	
	/**
	 * Array with menu items without group
	 * @var array
	 */
	protected $items = array();
	
	/**
	 * Add new group do menu
	 * 
	 * @author Michal Kliment
	 * @param string $name
	 * @param string $label
	 */
	public function addGroup($name, $label = '')
	{
		$group = new stdClass();
		$group->name = $name;
		$group->label = $label;
		$group->items = array();
		
		$this->groups[$name] = $group;
	}
	
	/**
	 * Adds new item to menu.
	 * 
	 * @author Michal Kliment
	 * @param type $url URL of item's link
	 * @param type $label Text of item's link
	 * @param type $group Group to which item belongs
	 * @param array $extra Array with extra arguments
	 */
	public function addItem($url, $label, $group = '', $extra = array())
	{
		$item = new stdClass();
		$item->url = $url;
		$item->label = $label;
		
		foreach ($extra as $key => $value)
			$item->$key = $value;
		
		// group exists
		if (isset($this->groups[$group]))
			$this->groups[$group]->items[] = $item;
		else
			$this->items[] = $item;
	}

	/**
	 * Render menu
	 * 
	 * @author Michal Kliment
	 * @return string
	 */
	public function render()
	{
		$view = new View($this->template);
		
		$groups = array();
		
		foreach ($this->groups as $group)
		{
			// group is empty, not render
			if (!count($group->items))
				continue;
		    
			$groups[] = $group;
		}
		
		$view->groups = $groups;
		
		$view->items = $this->items;
		
		return $view->render();
	}
    
	/**
	 * Call render
	 * 
	 * @author Michal Kliment
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}
}
