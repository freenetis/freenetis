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
 * Approval template item connect approval types to template with priority,
 * which is jused for specified multi-round approval.
 *
 * @author	Michal Kliment
 * @package Model
 * 
 * @property integer $id
 * @property integer $priority
 * @property integer $approval_template_id
 * @property Approval_template_Model $approval_template
 * @property integer $approval_type_id
 * @property Approval_type_Model $approval_type
 */
class Approval_template_item_Model extends ORM
{
	protected $belongs_to = array('approval_template', 'approval_type');

	/**
	 * Function to return all approval template items belongs to approval template
	 * 
	 * @author Michal Kliment
	 * @param number $template_id
	 * @return Mysql_Result object
	 */
	public function get_all_items_by_template_id($template_id)
	{
		return $this->db->query("
				SELECT i.id as item_id, i.priority, i.approval_template_id, t.id,
					t.name, ag.id AS group_id, ag.name AS group_name, t.type, 
					t.interval, t.min_suggest_amount, t.one_vote
				FROM approval_template_items i
				LEFT JOIN approval_types t ON t.id = i.approval_type_id
				LEFT JOIN aro_groups ag ON t.aro_group_id = ag.id
				WHERE i.approval_template_id = ?
				ORDER BY i.priority DESC
		", array($template_id));
	}

	/**
	 * Function to return the lowest priority of approval template
	 * 
	 * @author Michal Kliment
	 * @param number $template_id
	 * @return number
	 */
	public function get_lowest_priority_of_template($template_id)
	{
		$result = $this->db->query("
				SELECT min(priority) AS min
				FROM approval_template_items
				GROUP BY approval_template_id
				HAVING approval_template_id = ?
		", array($template_id));

		if (!$result || $result->count() != 1)
		{
			return NULL;
		}

		return $result->current()->min;
	}
	
	/**
	 * Function to return the highest priority of approval template
	 * 
	 * @author Michal Kliment
	 * @param number $template_id
	 * @return number
	 */
	public function get_highest_priority_of_template($template_id)
	{
		$result = $this->db->query("
				SELECT max(priority) AS max
				FROM approval_template_items
				GROUP BY approval_template_id
				HAVING approval_template_id = ?
		", array($template_id));

		if (!$result || $result->count() != 1)
		{
			return NULL;
		}

		return $result->current()->max;
	}
	
	/**
	 * Function to check if user have rights to vote
	 *
	 * @author Ondřej Fibich
	 * @param object $object Job or Request
	 * @param integer $object_type Vote_Model::WORK or Vote_Model::REQUEST
	 * @param integer $user_id
	 * @param double $suggest_amount
	 * @return boolean
	 */
	public function check_user_vote_rights(
			$object, $object_type, $user_id, $suggest_amount)
	{	
		if (!is_object($object) || !$object || !$object->id || $object->state > 1)
		{
			return FALSE;
		}
		
		// get approval types
		
		$cond = '';
		
		if ($suggest_amount)
		{
			$cond = ' AND t.min_suggest_amount <= '.intval($suggest_amount);
		}

		$groups = $this->db->query("
				SELECT a.id, a.name, i.priority, i.approval_type_id
				FROM approval_template_items i
				LEFT JOIN approval_types t ON i.approval_type_id = t.id
				LEFT JOIN aro_groups a ON t.aro_group_id = a.id
				WHERE i.approval_template_id = ? $cond
				ORDER BY i.priority DESC
		", $object->approval_template_id);
		
		// group by priority
		
		$group_by_priority = array();
		
		foreach ($groups as $group)
		{
			$group_by_priority[$group->priority][] = $group;
		}
		
		unset($groups);
		
		// check each group from most priority
		
		$vote = new Vote_Model();
		$counter = 0;
		
		foreach ($group_by_priority as $group)
		{
			$state = 0;
			$ids = array();
			$counter++;
			
			foreach ($group as $item)
			{
				$ids[] = $item->id;
				
				$item_state = Vote_Model::get_state(
						$object,
						$item->approval_type_id
				);
				
				if ($item_state == 2)
				{
					$state = 2;
				}
				else if ($item_state == 3 && $state == 0)
				{
					$state = 3;
				}
			}
			
			// is user in vote group?
			
			$in_group = FALSE;
			
			if (count($ids))
			{
				$in_group = $this->db->query("
						SELECT COUNT(*) AS count
						FROM groups_aro_map
						WHERE group_id IN (" . implode(', ', $ids) . ") AND
							  aro_id = ?
				", $user_id)->current()->count > 0;
			}
			
			// vote not finished yet, can vote or edit vote?
			if ($state <= 2)
			{
				return $in_group;
			}
			// approved, but some user should not vote yet or
			// if this is last group of voters, enable edit vote
			else if ($in_group && (
						(count($group_by_priority) == $counter) ||
						!$vote->has_user_voted_about($user_id, $object->id, $object_type)
					))
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
	

	/**
	 * Returns all group assigned to approval
	 * 
	 * @author Michal Kliment
	 * @param number $template_id
	 * @param number $suggest_amount
	 * @param number $user_id
	 * @return Mysql_Result object
	 */
	public function get_aro_groups_by_approval_template_id(
			$template_id, $suggest_amount = 0, $user_id = 0)
	{
		$cond = '';
		
		if ($suggest_amount)
		{
			$cond = ' AND t.min_suggest_amount <= '.doubleval($suggest_amount);
		}

		return $this->db->query("
				SELECT a.id, a.name FROM approval_template_items i
				LEFT JOIN approval_types t ON i.approval_type_id = t.id
				LEFT JOIN aro_groups a ON t.aro_group_id = a.id
				WHERE i.approval_template_id = ? $cond
				ORDER BY i.priority DESC
		", array($template_id));
	}

	/**
	 * Returns aro group assigned to user and approval
	 * 
	 * @author Michal Kliment
	 * @param number $template_id
	 * @param number $user_id
	 * @param number $suggest_amount
	 * @return Mysql_Result object
	 */
	public function get_aro_group_by_approval_template_id_and_user_id(
			$template_id, $user_id, $suggest_amount = 0)
	{
		$cond = '';
		
		if ($suggest_amount)
		{
			$cond = ' AND t.min_suggest_amount <= '.doubleval($suggest_amount);
		}

		$result = $this->db->query("
				SELECT a.id, a.name FROM approval_template_items i
				LEFT JOIN approval_types t ON i.approval_type_id = t.id
				LEFT JOIN aro_groups a ON t.aro_group_id = a.id
				LEFT JOIN groups_aro_map m ON a.id = m.group_id
				WHERE i.approval_template_id = ? AND m.aro_id = ? $cond
				ORDER BY i.priority DESC
		", array($template_id, $user_id));
		
		// no record, returns null
		if (!$result || !$result->count())
		{
			return NULL;
		}

		// returns first record
		return $result->current();
	}

	/**
	 * Returns all aro ids assigned to approval
	 * 
	 * @author Michal Kliment
	 * @param number $template_id
	 * @param number $suggest_amount
	 * @return Mysql_Result object
	 */
	public function get_aro_ids_by_approval_template_id(
			$template_id, $suggest_amount = 0)
	{
		$cond = '';
		
		if ($suggest_amount)
		{
			$cond = ' AND t.min_suggest_amount <= '.doubleval($suggest_amount);
		}

		return $this->db->query("
				SELECT g.aro_id AS id FROM approval_template_items i
				LEFT JOIN approval_types t ON i.approval_type_id = t.id
				LEFT JOIN groups_aro_map g ON t.aro_group_id = g.group_id
				WHERE i.approval_template_id = ? $cond
				GROUP BY g.aro_id
				HAVING g.aro_id IS NOT NULL
		", array($template_id));
	}

}
