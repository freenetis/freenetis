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
 * Access groups of users represented by tree.
 * Properties lft and rgt are used for left and right walk thought tree.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $parent_id
 * @property integer $lft
 * @property integer $rgt
 * @property string $name
 * @property string $value
 */
class Aro_group_Model extends ORM
{
	// ARO groups (must corresponds to database) =>
	const ALL									= 21;
	const REGULAR_MEMBERS						= 22;
	const REGISTERED_APPLICANTS					= 23;
	const ADMINS									= 32;
	const TELEPHONISTS							= 44;
	// <= ARO groups
	
	/**
	 * Is given ARO group deletable?
	 * If no ARO group given, current (this) ARO group is checked.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $aro_group_id [optional]
	 * @return boolean
	 */
	public function is_deletable($aro_group_id = NULL)
	{
		if ($aro_group_id === NULL && $this)
		{
			$aro_group_id = $this->id;
		}
		
		return (
				$aro_group_id != self::ALL &&
				$aro_group_id != self::REGULAR_MEMBERS &&
				$aro_group_id != self::REGISTERED_APPLICANTS &&
				$aro_group_id != self::ADMINS &&
				$aro_group_id != self::TELEPHONISTS
		);
	}


	/**
	 * Cleans ARO group - deletes all ARO objects
	 * 
	 * @author Michal Kliment
	 * @param integer $group_id 
	 */
	public function clean_group ($group_id = NULL)
	{
		if (!$group_id)
			$group_id = $this->id;
		
		$this->db->query("
			DELETE FROM groups_aro_map
			WHERE group_id = ?
		", $group_id);
	}
	
	/**
	 * Counts all childrens of given group
	 * 
	 * @author Michal Kliment
	 * @param integer $parent_id
	 * @return integer 
	 */
	public function count_childrens ($parent_id = NULL)
	{
		if (!$parent_id)
			$parent_id = $this->id;
		
		$result = $this->db->query("
			SELECT COUNT(*) AS count
			FROM aro_groups ag
			WHERE parent_id = ?
		", $parent_id);
		
		if ($result && $result->current())
			return $result->current ()->count;
		else
			return 0;
	}
	
	/**
	 * Gets count of parents
	 * @param integer $id
	 * @return integer
	 */
	public static function count_parent($id)
	{
		$aro_group_model = new Aro_group_Model();

		$parents = $aro_group_model->get_parent_id_by_id($id);

	   	if ($parents->count())
		{
			return (1 + self::count_parent($parents->current()->parent_id));
		}
		
		return 0;
	}
	
	/**
	 * Decreases lft and rgt values (to delete group)
	 * 
	 * @author Michal Kliment
	 * @param type $lft
	 * @param type $decrease 
	 */
	public function decrease($lft, $decrease = 2)
	{
		$this->db->query("
			UPDATE aro_groups
			SET lft = lft - ".intval($decrease)."
			WHERE lft >= ?
		", array($lft));
		
		$this->db->query("
			UPDATE aro_groups
			SET rgt = rgt - ".intval($decrease)."
			WHERE rgt >= ?
		", array($lft));
	}
	
	/**
	 * Returns all ACLs rule belongs to ARO group
	 * 
	 * @author Michal Kliment
	 * @param type $group_id
	 * @return type 
	 */
	public function get_acls ($group_id = NULL)
	{
		// group parameter is not required
		if (!$group_id)
			$group_id = $this->id;
		
		return $this->db->query("
			SELECT acl.*
			FROM acl
			JOIN aro_groups_map rgm ON rgm.acl_id = acl.id
			WHERE rgm.group_id = ?
		", $group_id);
	}
	
	/**
	 * Gets all
	 * @return Mysql_Result
	 */
    public function get_all_values()
    {
	    return $this->db->query("
				SELECT * FROM aro_groups
		");
    }
	
	/**
	 * Gets aro groups by fk_id and type
	 * @return Mysql_Result
	 */
    public function get_aro_groups_by_fk_id($fk_id, $type)
    {
	    return $this->db->query("
				SELECT a.* FROM votes v
				LEFT JOIN aro_groups a ON v.aro_group_id = a.id
				WHERE v.fk_id = ? AND v.type = ?
				GROUP BY v.aro_group_id
				ORDER BY v.priority
		", array($fk_id, $type));
    }
	
	/**
	 * Returns all ARO objects belongs to given group
	 * 
	 * @author Michal Kliment
	 * @param integer $group_id
	 * @return MySQL Result 
	 */
	public function get_aros ($group_id = NULL)
	{
		if (!$group_id)
			$group_id = $this->id;
		
		return $this->db->query("
			SELECT u.id, CONCAT(u.surname,' ',u.name) AS user_name, u.login
			FROM groups_aro_map grm 
			JOIN users u ON grm.aro_id = u.id
			WHERE group_id = ?
		", $group_id);
	}
	
	/**
	 * Get all with ID
	 * @param integer $id
	 * @return Mysql_Result
	 */
    public function get_by_id($id)
    {
	    return $this->db->query("
				SELECT *
				FROM aro_groups
				WHERE id=?
		", array($id));
    }
	
	/**
	 * Returns info about ranges of children of group
	 * 
	 * @author Michal Kliment
	 * @param integer $parent_id
	 * @return MySQL Result
	 */
	public function get_childrens_ranges($parent_id = NULL)
	{
		if (!$parent_id)
			$parent_id = $this->id;
		
		return $this->db->query("
			SELECT
			MIN(lft) AS lft, MAX(rgt) AS rgt
			FROM
			aro_groups ag
			WHERE parent_id = ?
		", $parent_id)->current();
	}
	
	/**
	 * Returns parent of given ARO group
	 * 
	 * @author Michal Kliment
	 * @param integer $group_id
	 * @return MySQL Result 
	 */
	public function get_parent($group_id = NULL)
	{
		if (!$group_id)
			$group_id = $this->id;
		
		return $this->db->query("
			SELECT pag.*
			FROM aro_groups ag
			LEFT JOIN aro_groups pag ON ag.parent_id = pag.id
			WHERE ag.id = ?
		", $group_id)->current();
	}
	
	/**
	 * Get parent id
	 * @param integer $id
	 * @return Mysql_Result
	 */
    public function get_parent_id_by_id($id)
    {
	    return $this->db->query("
				SELECT parent_id
				FROM aro_groups
				WHERE id=?
		", array($id));
    }
	
	/**
	 * Gets all by tree walk
	 * @return Mysql_Result
	 */
    public function get_traverz_tree()
    {
	    return $this->db->query("
				SELECT id, name, lft, parent_id, rgt, value
				FROM aro_groups
				ORDER BY lft
		");
    }
	
	/**
	 * Increases lft and rgt values (to insert new group)
	 * 
	 * @author Michal Kliment
	 * @param integer $rgt
	 * @param integer $increase 
	 */
	public function increase($rgt, $increase = 2)
	{
		$this->db->query("
			UPDATE aro_groups
			SET lft = lft + ".intval($increase)."
			WHERE lft >= ?
		", array($rgt));
		
		$this->db->query("
			UPDATE aro_groups
			SET rgt = rgt + ".intval($increase)."
			WHERE rgt >= ?
		", array($rgt));
	}
	
	/**
	 * Inserts given AROs to given ARO group
	 * 
	 * @author Michal Kliment
	 * @param array $aros
	 * @param integer $group_id 
	 */
	public function insert_aro($aros = array(), $group_id = NULL)
	{
		if (!$group_id)
			$group_id = $this->id;
		
		$group_id = intval($group_id);
		
		$sql_insert = "INSERT INTO groups_aro_map (group_id, aro_id) VALUES ";
		
		$values = array();
		foreach ($aros as $aro)
			$values[] = "($group_id, " . intval($aro) . ")";
		
		if (count($values))
		{
			$sql_insert .= implode(',', $values);
			$this->db->query($sql_insert);
		}
	}
	
}
