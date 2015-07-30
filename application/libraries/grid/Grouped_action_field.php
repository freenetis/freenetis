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
 * Grouped action grid field.
 * Make groups of actions fields.
 * 
 * @author Ondřej Fibich
 */
class Grouped_Action_Field extends Field
{
	
	/**
	 * Actions fields
	 *
	 * @var array[Actions_Field]
	 */
	public $actions_fields = array();


	/**
	 * Contruct of grouped action field
	 *
	 * @param type $name 
	 */
	public function __construct($name = NULL)
	{
		if (empty($name))
		{
			$name = __('Actions');
		}
		
		parent::__construct($name);
	}
	
	/**
	 * Adds action field to group
	 * 
	 * @param type $name	Table column [optional: id by default]
	 * @return Action_Field
	 */
	public function add_action($name = 'id')
	{
		$field = new Action_field($name);
		
		$this->actions_fields[] = $field;
		
		return $field;
	}
	
	/**
	 * Adds conditional action field to group
	 * 
	 * @param type $name	Table column [optional: id by default]
	 * @return Action_Conditional_Field
	 */
	public function add_conditional_action($name = 'id')
	{
		$field = new Action_conditional_field($name);
		
		$this->actions_fields[] = $field;
		
		return $field;
	}
	
}
