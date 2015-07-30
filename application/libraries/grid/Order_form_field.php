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
 * Order form grid field
 * 
 * @method Order_Form_Field callback(mixed $callback)
 */
class Order_Form_Field extends Order_field
{
	/**
	 * Callback function
	 *
	 * @var mixed
	 */
	public $callback;
}
