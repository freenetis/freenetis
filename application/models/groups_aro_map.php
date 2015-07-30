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
	 * @author Ondřej Fibich
	 * @staticvar array $cache			Cache of access
	 * @staticvar array $cache_aro_user Cache of ACL parent tree
	 * @staticvar array $cache_aro_hierarchy Cache of ACL parent tree
	 * @param integer $user_id			User to check access
	 * @param string $aco_value			ACO value - action (view_all, new_own, ...)
	 * @param string $axo_section_value	AXO section value - Controller name
	 * @param string $axo_value			AXO value - part of Controller
	 * @return boolean					Has this user access to this part of system?
	 */
	public function has_access(
			$user_id, $aco_value, $axo_section_value, $axo_value)
	{
		// Cache
		static $cache = array();
		static $cache_aro_hierarchy = NULL;
		static $cache_aro_user = array();
		
		// This codes calculates all predecesors of ARO group for hieararchy of
		// access rights.
		if ($cache_aro_hierarchy === NULL)
		{
			// Gets all groups id with relation to parent
			$aro_groups = ORM::factory('aro_group')->select_list('id', 'parent_id', 'id');
			// Go throught groupd
			foreach ($aro_groups as $i => $v)
			{
				// Final set of all parents (recursive) of group
				$final_set = array($i);
				// Stack for recursive walk throught groups
				$stack = ($v == 0) ? array() : array($v);
				// Go recursive throught parents
				while (($top = array_pop($stack)) !== NULL)
				{
					// End of tree?
					if ($aro_groups[$top] != 0)
					{
						// Add parents to stack
						array_push($stack, $aro_groups[$top]);
						// Add current to final set
						$final_set[] = $top;
					}
				}
				// Add to cache
				$cache_aro_hierarchy[$i] = array_unique($final_set);
			}
		}
		
		// Cache key
		$key = "$user_id#$aco_value#$axo_section_value#$axo_value";
		
		// Fill in user cache?
		if (!array_key_exists($user_id, $cache_aro_user))
		{
			// Get all ARO groups of user
			$user_in_groups = $this->where('aro_id', $user_id)
					->select_list('group_id', 'aro_id');
			// Set cache
			$cache_aro_user[$user_id] = array();
			// Add all parents for users group and set it to cache
			foreach ($user_in_groups as $group_id => $aro_id)
			{
				$cache_aro_user[$user_id] = array_merge(
						$cache_aro_user[$user_id],
						$cache_aro_hierarchy[$group_id]
				);
			}
			// Discart not unique values
			$cache_aro_user[$user_id] = array_unique($cache_aro_user[$user_id]);

			// Is user in any group?
			if (count($cache_aro_user[$user_id]))
			{
				// Check and add to cache
				$results = $this->db->query("
						SELECT CONCAT(aco.value, '#', axo_map.section_value, '#',
							axo_map.value) AS name, COUNT(*) AS count
						FROM aro_groups
						JOIN aro_groups_map ON aro_groups_map.group_id = aro_groups.id
						JOIN acl ON acl.id = aro_groups_map.acl_id
						JOIN aco_map ON aco_map.acl_id = acl.id
						JOIN aco ON aco.value = aco_map.value
						JOIN axo_map ON axo_map.acl_id = acl.id
						WHERE aro_groups.id IN(" . implode(',', $cache_aro_user[$user_id]) . ")
						GROUP BY aco.value, axo_map.section_value, axo_map.value
				");

				foreach ($results as $result)
				{
					$cache["$user_id#$result->name"] = ($result->count > 0);
				}
			}
		}
		
		// Return access info
		return (array_key_exists($key, $cache) ? $cache[$key] : FALSE);
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
