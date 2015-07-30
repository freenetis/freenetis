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
 * Enum type
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $type_id
 * @property Enum_type_Model $enum_type_name
 * @property string $value
 * @property boolean $read_only
 * @property ORM_Iterator $device_templates
 */
class Enum_type_Model extends ORM
{
	/* enum types names (same as enum type name IDs) ==> */
	// types for device, member, user, contact, fee
	const MEMBER_TYPE_ID			= 1;
	const DEVICE_TYPE_ID			= 2;
	const USER_TYPE_ID				= 3;
	const CONTACT_TYPE_ID			= 4;
	const FEE_TYPE_ID				= 6;
	// types for devices
	const MODE_TYPE_ID				= 8;
	const NORM_TYPE_ID				= 9;
	const ANTENNA_TYPE_ID			= 10;
	const POLARIZATION_TYPE_ID		= 11;

	const MEDIUM_TYPE_ID			= 12;
	// types for redirection
	const REDIRECT_DURATION_ID		= 13;
	const REDIRECT_DESTINATION_ID	= 14;
	const REDIRECT_ACTION_ID		= 15;
	// types for bacup
	const BACUP_ID					= 16;
	/* <== enum types names */
	
	const READ_ONLY					= 1;
	const READ_WRITE				= 0;
	
	protected $belongs_to = array
	(
		'type_id' => 'enum_type_name'
	);
	
	protected $has_many = array
	(
		'device_templates'
	);

	/**
	 * enum_types array supplements a database table, holding the names for
	 * all enumeration types for dropdown fields.
	 * Such a table is not necessary, because inserting a new type name into
	 * the table would have to be accompanied by modifying the PHP code which
	 * takes care about displaying as a new dropdown field.
	 *
	 * @var array of integers, where index is a string
	 */
	private static $enum_types = array
	(
  		1 => 'Member types',
  		2 => 'Device types',
  		3 => 'User types',
  		4 => 'Contact types',
	);

	/**
	 * Localizated compare string
	 *
	 * @param string $a
	 * @param string $b
	 * @return integer
	 */
	protected function cmp_utf($a, $b)
	{
	   return strcoll($a,$b);
	}

	/**
	 * Get translation of type
	 * 
	 * @param integer $type_id
	 * @return Translation_Model
	 */
	public function get_value($type_id = NULL)
	{
		if ($type_id === NULL && $this->id)
		{
			$type_id = $this->id;
		}
		
		$type = $this->where('id', $type_id)->find();
		$translation_model = new Translation_Model();
		return $translation_model->get_translation($type->value);
	}
	
	/**
	 * Gets all enum types ordered
	 *
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_all($order_by, $order_by_direction)
	{
		return $this->select('enum_types.id', 'enum_type_names.type_name as type', 'enum_types.value')
				->join('enum_type_names', 'enum_types.type_id', 'enum_type_names.id')
				->where('read_only', 0)
				->orderby($order_by, $order_by_direction)
				->find_all();
	}

	/**
	 * get_values returns all values for enumeration type identified by
	 * $type_id from database. It translates the values using i18n,
	 * and finally sorts them according to current locale.
	 *
	 * Note: the sort function does not work on Windows, because
	 * windows do not support the UTF locales.
	 * 
	 * @param integer $type_id
	 * @return array
	 */
	public function get_values($type_id, $read_only = NULL)
	{
		if ($read_only === NULL)
			$types = $this->where('type_id', $type_id)->find_all();
		else
		{
			$types = $this->where(array
			(
				'type_id' => $type_id,
				'read_only' => $read_only
			))->find_all();
		}
		
		$arr_types = array();

		$translation_model = new Translation_Model();

		foreach ($types as $type)
		{
			$arr_types[$type->id] = $translation_model->get_translation($type->value);
		}

		uasort($arr_types, array($this, "cmp_utf"));
		
		return $arr_types;
	}

	/**
	 * Get name of enum types
	 *
	 * @param integer $name_id
	 * @return string
	 */
	public static function get_name($name_id)
	{
		if (array_key_exists($name_id, self::$enum_types))
		{
			return self::$enum_types[$name_id];
		}
		return null;
	}

	/**
	 * It gets enum_type id of value string.
	 * 
	 * @author Jiri Svitak
	 * @param $value
	 * @return integer
	 */
	public function get_type_id($value, $type_id = NULL)
	{
		$where_type = '';
		
		if (!is_null($type_id))
		{
			$where_type = "AND type_id = " . intval($type_id);
		}
		
		$result = $this->db->query("
			SELECT id
			FROM enum_types
			WHERE value LIKE ? $where_type
		", $value);
		
		if ($result && $result->count())
		{
			return $result->current()->id;
		}
		
		return false;
	}

}
