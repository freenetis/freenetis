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
 * Order callback grid field
 * 
 * @method Order_Callback_Field callback(mixed $callback)
 */
class Order_Callback_Field extends Order_field
{
	/**
	 * Callback function
	 *
	 * @var mixed
	 */
	public $callback;
}
