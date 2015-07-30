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
 * Model for storing user faborite pages.
 * 
 * @package Model
 */
class User_favourite_pages_Model extends ORM
{	
	protected $table_names_plural = FALSE;
	
	/**
	 * Checks if user have given page in his favourites
	 * 
	 * @param int $user_id
	 * @param string $page
	 * @return bool	TRUE if page is favourite
	 */
	public function is_users_favourite($user_id, $page)
	{
		$result = $this->db->query("
				SELECT page
				FROM user_favourite_pages
				WHERE user_id = ? AND
						page = ?
		", $user_id, $page);
		
		return ($result && $result->count() == 1);
	}
	
	/**
	 * Insert page to users favourites
	 * 
	 * @param int $user_id	User ID
	 * @param string $title	Favourite page title
	 * @param string $page	Page address
	 * @param int $default	Is default page
	 * @return boolean TRUE if insert was successfull
	 */
	public function add_page_to_favourite($user_id, $page, $title, $default)
	{
		// remove default tag from other favourites
		if ($default)
		{
			$this->remove_user_default_page($user_id);
		}
		
		$result = $this->db->query("
				INSERT INTO user_favourite_pages (user_id , title, page , default_page)
				VALUES ( ? , ? , ? , ? )
		", $user_id, $title, $page, $default);
		
		return $result != NULL;
	}
	
	/**
	 * Removes page from users favourites by ID
	 * 
	 * @param int $id	Favourite page ID
	 * @return ORM object
	 */
	public function remove_page_from_favourites($id)
	{
		$result = $this->db->query("
				DELETE FROM user_favourite_pages
				WHERE id = ?
		", $id);
		
		return $result;
	}
	
	/**
	 * Returns all favourites of given user
	 * 
	 * @param int $user_id User ID
	 * @return ORM object
	 */
	public function get_users_favourites($user_id,
			$limit_from = 0, $limit_results = 50,
			$order_by = 'title', $order_by_direction = 'asc')
	{
		return $this->db->query("
				SELECT * FROM user_favourite_pages
				WHERE user_id = ?
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", $user_id);
	}
	
	/**
	 * Updates user favourite page details
	 * 
	 * @param int $user_id User ID
	 * @param int $id Favourite page ID
	 * @param string $page	Page address
	 * @param string $title	Favourite page title
	 * @param boolean $default	Is default page
	 * @return boolean
	 */
	public function edit_favourites($user_id, $id, $title, $default)
	{
		// remove default tag from other favourites
		if ($default)
		{
			$this->remove_user_default_page($user_id);
		}
		
		// update data
		$result = $this->db->query("
				UPDATE user_favourite_pages
				SET title=?,
				default_page=?
				WHERE id = ?
		", $title, $default, $id);
		
		return $result != NULL;
	}
	
	/**
	 * Removes default page tag from users favourites
	 * 
	 * @param int $user_id	User Id
	 * @return ORM object
	 */
	public function remove_user_default_page($user_id)
	{
		return $this->db->query("
				UPDATE user_favourite_pages
				SET default_page=0
				WHERE user_id = ?
			", $user_id);
	}
	
	/**
	 * Sets default page tag
	 * 
	 * @param int $id	Favourite page ID
	 * @return ORM object
	 */
	public function set_user_default_page_by_id($id)
	{
		return $this->db->query("
				UPDATE user_favourite_pages
				SET default_page=1
				WHERE id = ?
			", $id);
	}
	
	/**
	 * Get favourite page details
	 * 
	 * @param int $user_id	User Id
	 * @param string $page Page URL
	 * @return ORM object
	 */
	public function get_favourite_page_details($user_id, $page)
	{
		$result = $this->db->query("
				SELECT *
				FROM user_favourite_pages
				WHERE user_id = ? AND
						page = ?
		", $user_id, $page);
		
		if ($result && $result->count() == 1)
		{
			return $result->current();
		}
		
		return null;
	}
	
	/**
	 * Get users default page
	 * 
	 * @param int $user_id User Id
	 * @return ORM object
	 */
	public function get_user_default_page($user_id)
	{
		$result = $this->db->query("
				SELECT *
				FROM user_favourite_pages
				WHERE user_id = ? AND
						default_page = 1
		", $user_id);
		
		if ($result && $result->count() == 1)
		{
			return $result->current();
		}
		
		return null;
	}
}
