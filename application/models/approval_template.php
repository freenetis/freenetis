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
 * Approval template defines template for work and work report approval.
 *
 * @author	Michal Kliment
 * @package Model
 * 
 * @property integer $id
 * @property string $name
 * @property string $comment
 * @property integer $state
 * @property ORM_Iterator $approval_template_items
 * @property ORM_Iterator $jobs
 * @property ORM_Iterator $job_reports
 */
class Approval_template_Model extends ORM
{
	protected $has_many = array
	(
		'approval_template_items', 'jobs', 'job_reports'
	);

	/**
	 * Function to return all approval templates
	 * 
	 * @author Michal Kliment
	 * @param number $limit_from
	 * @param number $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result object
	 */
	public function get_all_approval_templates(
			$limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'ASC', $filter_values = array())
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT at.id, at.name, at.comment, count(ati.id) AS types_count
				FROM approval_templates at
				LEFT JOIN approval_template_items ati ON at.id = ati.approval_template_id
				GROUP BY at.id
				ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction 
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		");
	}

	/**
	 * Function to get state of approval template
	 * 
	 * @author Michal Kliment
	 * @param number $approval_template_id
	 * @return number
	 */
	public function get_state($approval_template_id = NULL)
	{
		if ($approval_template_id == NULL && $this->id)
		{
			$approval_template_id = $this->id;
		}
		
		return $this->db->query("
				SELECT IFNULL((MAX(j.state)+1),0) AS state
				FROM approval_templates t
				LEFT JOIN jobs j ON j.approval_template_id = t.id
				WHERE j.state < 2 AND t.id = ?
		", array($approval_template_id))->current()->state;
	}

}
