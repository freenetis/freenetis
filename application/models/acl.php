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
 * Access actions
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $note
 */
class Acl_Model extends ORM
{
	/**
	 * Table name is acl not acls
	 * 
	 * @var bool
	 */
	protected $table_names_plural = FALSE;
	
	/**
	 * Counts all ACL rules
	 * 
	 * @author Michal Kliment
	 * @return integer 
	 */
	public function count_all_rules()
	{
		return $this->count_all();
	}
	
	/**
	 * Returns all ACO objects belongs to rule
	 * 
	 * @author Michal Kliment
	 * @param type $acl_id
	 * @return type 
	 */
	public function get_acos ($acl_id = NULL)
	{
		// parameter acl_id is not required
		if (!$acl_id)
			$acl_id = $this->id;
		
		return $this->db->query("
			SELECT cm.value
			FROM aco_map cm
			WHERE acl_id = ?
		", $acl_id);
	}
	
	/**
	 * Cleans rule - deletes all AROs, ARo groups and AXOs
	 * 
	 * @author Michal Kliment
	 * @param type $acl_id 
	 */
	public function clean_rule($acl_id = NULL)
	{
		// parameter acl_id is not required
		if (!$acl_id)
			$acl_id = $this->id;
		
		$this->db->query("DELETE FROM aco_map WHERE acl_id = ?", $acl_id);
		$this->db->query("DELETE FROM aro_groups_map WHERE acl_id = ?", $acl_id);
		$this->db->query("DELETE FROM axo_map WHERE acl_id = ?", $acl_id);		
	}
	
	/**
	 * Returns all ACL rules
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @return MySQL Result 
	 */
	public function get_all_rules ($limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'asc')
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		return $this->db->query("
			SELECT
				acl.id, acl.note AS description,
				IFNULL(cm.count,0) AS aco_count, cm.value AS aco_value,
				IFNULL(rgm.count,0) AS aro_groups_count, rgm.value AS aro_groups_value,
				IFNULL(xm.count,0) AS axo_count, xm.value AS axo_value
			FROM acl
			LEFT JOIN
			(
				SELECT
					acl_id, COUNT(*) AS count,
					GROUP_CONCAT(value ORDER BY value SEPARATOR ', \n') AS value
				FROM
				(
					SELECT acl_id,
					IF(value = ?, ?, IF(value = ?, ?, IF(value = ?, ?,
					IF(value = ?, ?, IF(value = ?, ?, IF(value = ?, ?,
					IF(value = ?, ?, IF(value = ?, ?, NULL)))))))) AS value
					FROM aco_map cm
				) cm
				GROUP BY acl_id
			) cm ON cm.acl_id = acl.id
			LEFT JOIN
			(
				SELECT
					acl_id, COUNT(*) AS count,
					GROUP_CONCAT(name ORDER BY name SEPARATOR ', \n') AS value
				FROM aro_groups_map rgm
				LEFT JOIN aro_groups rg ON rg.id = rgm.group_id
				GROUP BY acl_id
			) rgm ON rgm.acl_id = acl.id
			LEFT JOIN
			(
				SELECT
					xm.acl_id, COUNT(*) AS count,
					GROUP_CONCAT(x.name ORDER BY x.name SEPARATOR ', \n') AS value
				FROM axo_map xm
				JOIN axo x ON xm.section_value = x.section_value AND xm.value = x.value
				GROUP BY xm.acl_id
			) xm ON xm.acl_id = acl.id
			ORDER BY ".$this->db->escape_column($order_by)." ".$order_by_direction."
			LIMIT ".intval($limit_from).", ".intval($limit_results),
		array
		(
			Aco_Model::VIEW_OWN, Aco_Model::get_action(Aco_Model::VIEW_OWN),
			Aco_Model::VIEW_ALL, Aco_Model::get_action(Aco_Model::VIEW_ALL),
			Aco_Model::NEW_OWN, Aco_Model::get_action(Aco_Model::NEW_OWN),
			Aco_Model::NEW_ALL, Aco_Model::get_action(Aco_Model::NEW_ALL),
			Aco_Model::EDIT_OWN, Aco_Model::get_action(Aco_Model::EDIT_OWN),
			Aco_Model::EDIT_ALL, Aco_Model::get_action(Aco_Model::EDIT_ALL),
			Aco_Model::DELETE_OWN, Aco_Model::get_action(Aco_Model::DELETE_OWN),
			Aco_Model::DELETE_ALL, Aco_Model::get_action(Aco_Model::DELETE_ALL),
		));
	}
	
	/**
	 *  Returns all ARO groups belongs to rule
	 * 
	 * @author Michal Kliment
	 * @param type $acl_id
	 * @return type 
	 */
	public function get_aro_groups($acl_id = NULL)
	{
		// parameter acl_id is not required
		if (!$acl_id)
			$acl_id = $this->id;
		
		return $this->db->query("
			SELECT rg.id, rg.name
			FROM aro_groups rg
			LEFT JOIN aro_groups_map rgm ON rgm.group_id = rg.id
			WHERE acl_id = ?
		", $acl_id);
	}
	
	/**
	 * Returns all AXO objects belongs to rule
	 * 
	 * @author Michal Kliment
	 * @param type $acl_id
	 * @return type 
	 */
	public function get_axos($acl_id = NULL)
	{
		// parameter acl_id is not required
		if (!$acl_id)
			$acl_id = $this->id;
		
		return $this->db->query("
			SELECT
				x.id, x.section_value, x.value,
				IFNULL(t.translated_term, x.name) AS name
			FROM axo x
			JOIN axo_map xm ON
				x.section_value = xm.section_value AND x.value = xm.value
			LEFT JOIN translations t ON t.original_term LIKE x.name AND t.lang = 'cs'
			WHERE acl_id = ?
		", $acl_id);
	}
	
	/**
	 * Inserts new ACO objects to rule
	 * 
	 * @author Michal Kliment
	 * @param type $acos
	 * @param type $acl_id 
	 */
	public function insert_aco($acos = array(), $acl_id = NULL)
	{
		// parameter acl_id is not required
		if (!$acl_id)
			$acl_id = $this->id;
		
		$sql_insert = "INSERT INTO aco_map (acl_id, value) VALUES ";
		
		$values = array();
		foreach ($acos as $aco)
			$values[] = "($acl_id, '$aco')";
		
		if (count($values))
		{
			$sql_insert .= implode(',', $values);
			$this->db->query($sql_insert);
		}
	}
	
	/**
	 * Inserts new ARO groups to rule
	 * 
	 * @author Michal Kliment
	 * @param type $aro_groups
	 * @param type $acl_id 
	 */
	public function insert_aro_groups($aro_groups = array(), $acl_id = NULL)
	{
		// parameter acl_id is not required
		if (!$acl_id)
			$acl_id = $this->id;
		
		$sql_insert = "INSERT INTO aro_groups_map (acl_id, group_id) VALUES ";
		
		$values = array();
		foreach ($aro_groups as $aro_group)
			$values[] = "($acl_id, '$aro_group')";
		
		if (count($values))
		{
			$sql_insert .= implode(',', $values);
			$this->db->query($sql_insert);
		}
	}
	
	/**
	 * Inserts new AXO objects to rule
	 * 
	 * @author Michal Kliment
	 * @param type $axos
	 * @param type $acl_id 
	 */
	public function insert_axo($axos = array(), $acl_id = NULL)
	{
		// parameter acl_id is not required
		if (!$acl_id)
			$acl_id = $this->id;
		
		$sql_insert = "INSERT INTO axo_map (acl_id, section_value, value) VALUES ";
		
		$values = array();
		foreach ($axos as $axo)
			$values[] = "($acl_id, '".$axo['section_value']."', '".$axo['value']."')";
		
		if (count($values))
		{
			$sql_insert .= implode(',', $values);
			$this->db->query($sql_insert);
		}
	}
	
}
