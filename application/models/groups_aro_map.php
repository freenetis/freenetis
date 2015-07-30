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
 * Pivot table between ARO map and user.
 * Interaction between user and access system.
 * 
 * @author Ondřej Fibich
 * @package Model
 * 
 * @property integer $group_id
 * @property integer $aro_id
 */
class Groups_aro_map_Model extends ORM
{
	/**
	 * Table name is groups_aro_map not groups_aro_maps
	 * 
	 * @var bool
	 */
	protected $table_names_plural = FALSE;
	
	/**
	 * Check access of user to part of system.
	 * 
	 * Be wery carefull with editing of this method, whole system can be
	 * damaged by impropriate edit!
	 * 
	 * I have tried to different method of fetching data,
	 * and for each I have made benchmarks using unit tester
	 * throught all controllers.
	 * 
	 * 1) DB query for each access rights
	 *    AVG Results:		0,3187821622s	6,4278378378MB
	 * 
	 * 2) DB query for all user's rights cached after first of his access request
	 *    AVG Results:		0,3484608815s	6,4987052342MB
	 * 
	 * The first method seems to be better, so here it is :-)
	 *
	 * @author Ondřej Fibich
	 * @staticvar array $cache			Cache of access
	 * @param integer $user_id			User to check access
	 * @param string $aco_value			ACO value - action (view_all, new_own, ...)
	 * @param string $axo_section_value	AXO section value - Controller name
	 * @param string $axo_value			AXO value - part of Controller
	 * @return boolean					Has this user access to this part of system?
	 */
	public function has_access(
			$user_id, $aco_value, $axo_section_value, $axo_value)
	{
		// Cahce
		static $cache = array();
		
		// Cache key
		$key = "$user_id#$aco_value#$axo_section_value#$axo_value";
		
		// Is in cache?
		if (!array_key_exists($key, $cache))
		{
			// Check and add to cache
			$cache[$key] = $this->db->query("
					SELECT COUNT(*) AS count
					FROM groups_aro_map
					LEFT JOIN aro_groups ON aro_groups.id = groups_aro_map.group_id
					LEFT JOIN aro_groups_map ON aro_groups_map.group_id = aro_groups.id
					LEFT JOIN acl ON acl.id = aro_groups_map.acl_id
					LEFT JOIN aco_map ON aco_map.acl_id = acl.id
					LEFT JOIN aco ON aco.value = aco_map.value
					LEFT JOIN axo_map ON axo_map.acl_id = acl.id
					WHERE groups_aro_map.aro_id = ? AND
						aco.value = ? AND
						axo_map.section_value = ? AND
						axo_map.value = ?
			", array
			(
				$user_id, $aco_value, $axo_section_value, $axo_value
			))->current()->count > 0;
		}
		
		// Return access info
		return $cache[$key];
	}
	
	/**
	 * Checks if aro map row exists
	 *
	 * @author Ondřej Fibich
	 * @param integer $group_id		ARO group ID
	 * @param integer $aro_id		User ID
	 * @return boolean				true if exists false otherwise
	 */
	public function groups_aro_map_exists($group_id, $aro_id)
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM groups_aro_map
				WHERE group_id = ? AND aro_id = ? 
		", array
		(
			$group_id, $aro_id
		))->current()->count > 0;
	}
		
	/**
	 * Deletes user wights from diven group
	 *
	 * @param integer $group_id		Group ID
	 * @param integer $aro_id		User ID
	 */
	public function detete_row($group_id, $aro_id)
	{
		$this->db->query("
				DELETE FROM groups_aro_map
				WHERE group_id=? AND aro_id=?
		", $group_id, $aro_id);
	}

	/**
	 * Check if users is in given group
	 *
	 * @param integer $group_id		Group ID
	 * @param integer $aro_id		User ID
	 */
	public function exist_row($group_id, $aro_id)
	{
		$result = $this->db->query("
				SELECT group_id
				FROM groups_aro_map
				WHERE group_id=? AND aro_id=?
		", $group_id, $aro_id);

		return $result && $result->count() > 0;
	}

	/**
	 * Counts number of users in given group
	 *
	 * @param integer $group_id
	 * @return integer
	 */
	public function count_rows_by_group_id($group_id)
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM groups_aro_map where group_id=?
		", $group_id)->current()->count;
	}

	/**
	 * Function to get all users belongs to group
	 * 
	 * @author Michal Kliment
	 * @param int $group_id
	 * @return Mysql_Result object
	 */
	public function get_all_users_by_group_id($group_id)
	{
		return $this->db->query("
				SELECT u.* FROM groups_aro_map g
				LEFT JOIN users u ON g.aro_id = u.id
				WHERE g.group_id = ?
		", $group_id);
	}

}
