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
 * Access actions types
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $name
 * @property string $value
 */
class Aco_Model extends ORM
{
	const VIEW_OWN = 'view_own';
	const VIEW_ALL = 'view_all';
	
	const NEW_OWN = 'new_own';
	const NEW_ALL = 'new_all';
	
	const EDIT_OWN = 'edit_own';
	const EDIT_ALL = 'edit_all';
	
	const DELETE_OWN = 'delete_own';
	const DELETE_ALL = 'delete_all';
	
	/**
	 * Actions names
	 *
	 * @var array
	 */
	private static $actions = array
	(
		self::VIEW_OWN		=> 'View own records',
		self::VIEW_ALL		=> 'View all records',
		self::NEW_OWN		=> 'Add own records',
		self::NEW_ALL		=> 'Add all records',
		self::EDIT_OWN		=> 'Edit own records',
		self::EDIT_ALL		=> 'Edit all records',
		self::DELETE_OWN	=> 'Delete own records',
		self::DELETE_ALL	=> 'Delete all records',
	);
	
	/**
	 * Table name is aco not acos
	 * 
	 * @var bool
	 */
	protected $table_names_plural = FALSE;
	
	/**
	 * Gets translated action name
	 *
	 * @param string $action
	 * @return string
	 */
	public static function get_action ($action)
	{
		if (isset(self::$actions[$action]))
		{
			return __(self::$actions[$action]);
		}
		
		return NULL;
	}
	
	/**
	 * Gets translated actions names
	 *
	 * @return array
	 */
	public static function get_actions ()
	{
		return array_map('__', self::$actions);
	}
	
	/**
	 * Gets aco by acl id
	 * 
	 * @param integer $acl_id
	 * @return Mysql_Result
	 */
	public function get_aco_by_acl($acl_id)
	{
		return $this->db->query("
				SELECT aco.name
				FROM aco
				JOIN aco_map am ON am.value = aco.value
				WHERE am.acl_id = ?
		", $acl_id);
	}
}
