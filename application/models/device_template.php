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
 * Teplates for creating of devices.
 * 
 * @author Ondrej Fibich
 * @package Model
 * 
 * @property int $id
 * @property int $enum_type_id
 * @property Enum_type_Model $enum_type
 * @property string $name
 * @property string $values
 * @property boolean $default
 */
class Device_template_Model extends ORM
{
	protected $belongs_to = array('enum_type');
	
	/**
	 * Gets parsed value of current object
	 * 
	 * @return array|null 
	 */
	public function get_value()
	{
		if ($this->id)
		{
			return json_decode($this->values, TRUE);
		}
		return NULL;
	}
	
	/**
	 * Gets all template
	 * 
	 * @return Mysql_Result
	 */
	public function get_all_templates()
	{
		return $this->db->query("
			SELECT dt.*, IFNULL(f.translated_term, e.value) AS enum_type_translated
			FROM device_templates dt
			LEFT JOIN enum_types e ON dt.enum_type_id = e.id
			LEFT JOIN translations f ON lang = ? AND e.value = f.original_term
			ORDER BY IFNULL(f.translated_term, e.value)
		", Config::get('lang'));
	}
	
}
