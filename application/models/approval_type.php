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
 * Approval type defines voters and politics of voting.
 *
 * @author	Michal Kliment
 * @package Model
 * 
 * @property integer $id
 * @property string $name
 * @property string $comment
 * @property integer $type
 * @property integer $majority_percent
 * @property integer $aro_group_id
 * @property Aro_group_Model $aro_group
 * @property string $time
 * @property integer $default_vote
 * @property integer $min_suggested_amount
 */
class Approval_type_Model extends ORM
{
	protected $belongs_to = array('aro_group');

	/** Simple type of approval type */
	const SIMPLE = 1;
	/** Absolute type of approval type */
	const ABSOLUTE = 2;

	/**
	 * Function to return all aproval types
	 * 
	 * @author Michal Kliment
	 * @param number $limit_from
	 * @param number $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result object
	 */
	public function get_all_approval_types(
			$limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'ASC', $filter_values = array())
	{
		// order by check
		if (!$this->has_column($order_by))
		{
			$order_by = 'id';
		}
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT at.id, at.name, at.comment, at.type, at.majority_percent,
					at.interval, at.default_vote, at.min_suggest_amount,
					ag.id as group_id, ag.name as group_name
				FROM approval_types at
				LEFT JOIN aro_groups ag ON at.aro_group_id = ag.id
				ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		");
	}

	/**
	 * Function to get state of approval type
	 * 
	 * @author Michal Kliment
	 * @param number $approval_type_id
	 * @return number
	 */
	public function get_state($approval_type_id = NULL)
	{
		if ($approval_type_id == NULL && $this->id)
		{
			$approval_type_id = $this->id;
		}
		
		return $this->db->query("
				SELECT IFNULL(MAX(te.state),0) AS state
				FROM approval_types AS t
				LEFT JOIN approval_template_items i ON t.id = i.approval_type_id
				LEFT JOIN approval_templates te ON i.approval_template_id = te.id
				WHERE t.id = ?
		", array($approval_type_id))->current()->state;
	}
	
}
