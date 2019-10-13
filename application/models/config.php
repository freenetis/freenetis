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
 * Config values.
 * Do not use these methods use class Settings!
 * 
 * @package Model
 * @see Settings#get
 * @see Settings#set
 */
class Config_Model extends Model
{
	/**
	 * Gets value from config
	 *
	 * @param string $name	Key value
	 * @return string
	 */
	public function get_value_from_name($name = '')
	{
		// try if query return exception, for example config table doesn't exist
		try
		{
			$result = $this->db->query("
				select value
				from config
				where name = ?
			", $name);

			return (!is_null($result) && count($result)) ? $result[0]->value : '';
		}
		catch (Kohana_Database_Exception $e)
		{
			return '';
		}
	}

	/**
	 * Gets all values from config to assoc. array
	 * where key is name and value is value.
	 * 
	 * Do not fetch values which containst word 'pass', because of security
	 * of services provided by freenetis.
	 *
	 * @return array
	 */
	public function get_all_values()
	{
		// query
		$data = $this->db->query("
			select name, value
			from config
			where name not like '%pass%'
			order by name
		");
		
		// create array
		$arr_data = array();
		
		foreach ($data as $row)
		{
			$arr_data[$row->name] = $row->value;
		}
		
		// get data
		return $arr_data;
	}

	/**
	 * Checks if var exists in config
	 *
	 * @param string $name	Key value
	 * @return boolean
	 */
	public function check_exist_variable($name = '')
	{
		return ($this->db->query("
			select value
			from config
			where name = ?
		", $name)->count() > 0);
	}

	/**
	 * Updates value in config
	 *
	 * @param string $name		Key value
	 * @param string $value		Value
	 * @return boolean
	 */
	public function update_variable($name, $value)
	{
		return $this->db->query("
				update config
				set value = ?
				where name = ?
		", $value, $name);
	}

	/**
	 * Insert value in config
	 *
	 * @param string $name		Key value
	 * @param string $value		Value
	 * @return boolean
	 */
	public function insert_variable($name, $value)
	{
		return $this->db->query("
				insert into config (name,value)
				values(?,?)
		", $name, $value);
	}

}
