<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is release under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Enum type name
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $type_name
 * @property ORM_Iterator $enum_types
 */
class Enum_type_name_Model extends ORM
{
	protected $has_many = array('enum_types');
}
