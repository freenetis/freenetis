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
 * Action field with condition, it's content is displayed only if condition
 * is fullfilled.
 * 
 * @author Ondřej Fibich
 * 
 * @method Action_Conditional_Field condition(string $condition_method)
 */
class Action_Conditional_Field extends Action_field
{
	/**
	 * Conditional field
	 *
	 * @var string
	 */
	public $condition = FALSE;
}
