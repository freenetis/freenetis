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
 * Access places.
 * Section value is always a name of controller, value is it's part and name 
 * is a comment.
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $section_value
 * @property string $name
 * @property string $value
 */
class Axo_Model extends ORM
{
	/**
	 * Table name is axo not axos
	 * 
	 * @var bool
	 */
	protected $table_names_plural = FALSE;
	
	/**
	 * Returns all section values
	 * 
	 * @author Michal Kliment
	 * @return type
	 */
	public static function get_section_values()
	{
		$axo_model = new Axo_Model();
		
		$axos = arr::sort(
			array_unique(
				arr::from_objects(
					$axo_model->get_all_values(),
					'section_value'
				)
			)
		);
		
		return $axos;
	}
	
	/**
	 * Returns all values
	 * 
	 * @author Michal Kliment
	 * @return type
	 */
	public static function get_values()
	{
		$axo_model = new Axo_Model();
		
		$axos = arr::sort(
			array_unique(
				arr::from_objects(
					$axo_model->get_all_values(),
					'value'
				)
			)
		);
		
		return $axos;
	}
	
	/**
	 * Returns all AXO values
	 * 
	 * @author Michal Kliment
	 * @return type
	 */
	public function get_all_values()
	{
		return $this->db->query("
			SELECT * FROM axo
			ORDER BY section_value
		");
	}
	
	/**
	 * Gets AXo by ACl
	 *
	 * @param integer $acl_id
	 * @return Mysql_Result
	 */
	public function get_axo_by_acl($acl_id)
	{
		return $this->db->query("
			SELECT axo.id, axo.section_value, axo.name
			FROM axo
			JOIN axo_map am ON am.value = axo.value AND
					am.section_value = axo.section_value
			WHERE am.acl_id = ?
		", array($acl_id));
	}
	
	/**
	 * Gets values by given ids
	 *
	 * @param array $ids
	 * @return array
	 */
	public function get_values_by_ids ($ids = array())
	{
		$where = array();
		foreach ($ids as $id)
			$where[] = 'id = '.intval($id);
		
		if (!count($where))
			return array();
			
		$where = "WHERE ".implode(" OR ", $where);
		
		$axos = $this->db->query("
			SELECT * FROM axo
			$where
		");
		
		$arr_axos = array();
		foreach ($axos as $axo)
		{
			$arr_axos[] = array
			(
				'section_value' => $axo->section_value,
				'value' => $axo->value
			);
		}
		return $arr_axos;
	}
}
