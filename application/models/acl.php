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
	public function count_all_rules($filter_sql = '')
	{
		return $this
				->get_all_rules(NULL, NULL, NULL, NULL, $filter_sql)
				->count();
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
			ORDER BY cm.value
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
	public function get_all_rules ($limit_from = NULL, $limit_results = NULL, $order_by = NULL,
			$order_by_direction = 'asc', $filter_sql = '')
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		$where = '';
		
		if ($filter_sql != '')
		{
			$where = 'WHERE '.$filter_sql;
		}
		
		$order = '';
		
		if ($order_by)
		{
			$order = "ORDER BY ".$this->db->escape_column($order_by)." ".$order_by_direction;
		}
		
		$limit = '';
		
		if (!is_null($limit_from) && !is_null($limit_results))
		{
			$limit = "LIMIT ".intval($limit_from).", ".intval($limit_results);
		}
		
		return $this->db->query("
			SELECT a.*,
			COUNT(DISTINCT aco_id) AS aco_count,
			COUNT(DISTINCT aro_group_id) AS aro_groups_count,
			COUNT(DISTINCT axo_id) AS axo_count,
			GROUP_CONCAT(DISTINCT aco_name SEPARATOR ',\n') AS aco_names,
			GROUP_CONCAT(DISTINCT aro_group_name) AS aro_groups_names,
			GROUP_CONCAT(DISTINCT CONCAT(axo_name,' (',axo_section_value,' - ',axo_value,')')ORDER BY axo_name SEPARATOR ',\n') AS axo_names
			FROM
			(
				SELECT a.*,
				c.id AS aco_id, c.value AS aco_value, IFNULL(ct.translated_term, c.name) AS aco_name,
				rg.id AS aro_group_id, rg.value AS aro_group_value, rg.name AS aro_group_name,
				x.id AS axo_id, x.section_value AS axo_section_value, x.value AS axo_value, x.name AS axo_name
				FROM acl a
				JOIN aco_map cm ON cm.acl_id = a.id
				JOIN aco c ON cm.value = c.value
				LEFT JOIN translations ct ON ct.original_term LIKE c.name AND ct.lang = ?
				JOIN aro_groups_map rgm ON rgm.acl_id = a.id
				JOIN aro_groups rg ON rgm.group_id = rg.id
				JOIN axo_map xm ON xm.acl_id = a.id
				JOIN axo x ON xm.section_value = x.section_value AND xm.value = x.value
			) a
			$where
			GROUP BY a.id
			$order
			$limit
		", Config::get('lang'));
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
			ORDER BY rg.name
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
			ORDER BY x.section_value, x.value
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
		
		$acl_id = intval($acl_id);
		$sql_insert = "INSERT INTO aco_map (acl_id, value) VALUES ";
		
		$values = array();
		foreach ($acos as $aco)
			$values[] = "($acl_id, " . $this->db->escape($aco) . ")";
		
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
		
		$acl_id = intval($acl_id);
		$sql_insert = "INSERT INTO aro_groups_map (acl_id, group_id) VALUES ";
		
		$values = array();
		foreach ($aro_groups as $aro_group)
			$values[] = "($acl_id, " . $this->db->escape($aro_group) . ")";
		
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
		
		$acl_id = intval($acl_id);
		$sql_insert = "INSERT INTO axo_map (acl_id, section_value, value) VALUES ";
		
		$values = array();
		foreach ($axos as $axo)
			$values[] = "($acl_id, " . $this->db->escape($axo['section_value'])
				. ", " . $this->db->escape($axo['value']) . ")";
		
		if (count($values))
		{
			$sql_insert .= implode(',', $values);
			$this->db->query($sql_insert);
		}
	}
	
}
