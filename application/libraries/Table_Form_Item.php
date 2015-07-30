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
 * Class represents element of Table_Form_item
 * 
 * @author Tomas Dulik, Michal Kliment, Ondřej Fibich
 */
class Table_Form_Item
{
	/** @var string $type	Type of Item */
	public $type;
	
	/** @var string $name	Name of Item */
	public $name;

	/** @var string $label	Label of Item */
	public $label;
	
	/**
	 * @var array $values	Values of Item - first index of array for
	 *			all items types except select box,
	 *			where whole array is used as options of element
	 */
	public $values;

	/**
	 * @var array $artrs	Attributs of HTML element, key of array is attribut name,
	 *			value of array is his value
	 */
	public $attrs;

	/**
	 * Contruct of Form Item
	 * 
	 * @param string $type
	 * @param string $name
	 * @param string $label
	 * @param array $values
	 * @param array $attrs
	 */
	public function __construct(
			$type = 'text', $name = NULL, $label = NULL,
			$values = array(), $attrs = array())
	{
		$this->type = $type;
		$this->name = $name;
		$this->label = $label;
		$this->values = $values;
		$this->attrs = $attrs;	
	}
	
}
