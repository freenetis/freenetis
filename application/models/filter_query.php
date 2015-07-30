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
 * It used to store filter queries to database
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $name
 * @property string $url
 * @property string $values
 * @property boolean $default 
 */
class Filter_query_Model extends ORM
{
	/**
	 * Returns all queries
	 * 
	 * @author Michal Kliment
	 * @return type 
	 */
	public function get_all_queries()
	{
		return $this->db->query("
			SELECT * FROM filter_queries fq
		");
	}
	
	/**
	 * Returns all queries belong to given URL
	 * 
	 * @author Michal Kliment
	 * @param type $url
	 * @return type 
	 */
	public function get_all_queries_by_url($url)
	{
		$queries = $this->db->query("
			SELECT * FROM filter_queries
			WHERE url LIKE ?
		", $url);
		
		$arr_queries = array();
		foreach ($queries as $query)
			$arr_queries[$query->id] = $query;
		
		return $arr_queries;
	}
	
	/**
	 * Repair default flag - disable it for other items
	 * 
	 * @author Michal Kliment
	 * @param type $filter_query_id
	 * @param type $url
	 * @return type 
	 */
	public function repair_default ($filter_query_id = NULL, $url = '')
	{
		// if id is not given, it uses current object
		if (!$filter_query_id && $this->id)
		{
			$filter_query_id = $this->id;
			$url = $this->url;
		}
		
		return $this->db->query("
			UPDATE filter_queries
			SET `default` = 0
			WHERE url LIKE ? AND id <> ?
		", array($url, $filter_query_id));
	}
	
	/**
	 * Find default qyuery for given URL
	 * 
	 * @author Michal Kliment
	 * @param type $url
	 * @return type 
	 */
	public function find_default_by_url ($url)
	{
		return $this->where('default', 1)
				->like('url', $url)
				->find();
	}
}

?>
