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
 * Form grid field
 * 
 * @method Form_Field type(string $name)
 * @method Form_Field input(string $input)
 * @method Form_Field rules(string $rules)
 * @method Form_Field options(array $options)
 * @method Form_Field callback(mixed $callback)
 */
class Form_Field extends Field
{
	
	/**
	 * Type of form element
	 *
	 * @var string
	 */
	public $type;
	
	/**
	 * Input
	 *
	 * @var string
	 */
	public $input;
	
	/**
	 * Rules
	 *
	 * @var string 
	 */
	public $rules;
	
	/**
	 * Options
	 *
	 * @var array
	 */
	public $options;
	
	/**
	 * Callback
	 *
	 * @var mixed
	 */
	public $callback;
}
