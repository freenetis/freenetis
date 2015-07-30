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
 * Watchers
 * 
 * @author Michal Kliment
 * @package Model
 * 
 * @property integer $user_id
 * @property User_Model $user
 * @property integer $type
 * @property integer $fk_id
 */
class Watcher_Model extends ORM
{
	/**
	 * Item's type to watch
	 */
	
	const WORK			= 1;
	
	const WORK_REPORT	= 2;
	
	const REQUEST		= 3;
	
	// types
	private static $types = array
	(
		self::WORK			=> 'Work',
		self::WORK_REPORT	=> 'Work report',
		self::REQUEST		=> 'Request'
	);
	
	/**
	 * Returns all watchers of object
	 * 
	 * @author Michal Kliment
	 * @param integer $type
	 * @param integer $fk_id
	 * @return array
	 */
	public function get_watchers_by_object($type, $fk_id)
	{
		$result = $this->db->query("
			SELECT w.id, w.user_id
			FROM watchers w
			WHERE type = ? AND fk_id = ?
		", array($type, $fk_id));
		
		if ($result)
			return arr::from_objects ($result, 'user_id');
		else
			return array();
	}
	
	/**
	 * Add watchers to object
	 * 
	 * @param array $watchers
	 * @param integer $type
	 * @param integer $fk_id
	 * @throws Exception
	 */
	public function add_watchers_to_object($watchers, $type, $fk_id)
	{
		if (!array_key_exists($type, self::$types))
		{
			throw new Exception('Unknown object type to watch');
		}
		
		$values = array();
		
		foreach ($watchers as $watcher)
		{
			$values[] = '('.intval($watcher).', '.$type.', '.$fk_id.')';
		}
		
		if (count($values))
		{
			$this->db->query("
				INSERT INTO watchers (user_id, type, fk_id)
				VALUES
				".implode(", ", $values)."
			");
		}
	}
	
	/**
	 * Delete watchers from object
	 * 
	 * @author Michal Kliment
	 * @param integer $type
	 * @param integer $fk_id
	 * @return type
	 */
	public function delete_watchers_by_object($type, $fk_id)
	{
		return $this->db->query("
			DELETE FROM watchers
			WHERE type = ? AND fk_id = ?
		", array($type, $fk_id));
	}
}