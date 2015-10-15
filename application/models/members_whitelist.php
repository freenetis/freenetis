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
 * Members redirection whitelist
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $member_id
 * @property Member_Model $member
 * @property date $since
 * @property date $until
 * @property bool $permanent
 * @property string $comment
 */
class Members_whitelist_Model extends ORM
{

	protected $belongs_to = array('member');

	/**
	 * Gets members whose are whitelisted.
	 * 
	 * @author Jiri Svitak
	 * @return Mysql_Result
	 */
	public function get_whitelisted_members(
			$limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'asc', $filter_sql = "")
	{
		$where = "";
		if ($filter_sql)
			$where = "WHERE $filter_sql";
		
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		
		return $this->db->query("
			SELECT m.*
			FROM
			(
				SELECT
					m.id, IFNULL(f.translated_term, e.value) AS type,
					m.name, m.name AS member_name, a.balance,
					a.id AS aid, a.comments_thread_id AS a_comments_thread_id,
					IF(mw.permanent > 0, 1, 2) AS whitelisted, a_comment
				FROM members m
				JOIN members_whitelists mw ON mw.member_id = m.id
				LEFT JOIN accounts a ON a.member_id = m.id AND m.id <> 1
				LEFT JOIN
				(
					SELECT c.comments_thread_id,
					GROUP_CONCAT(CONCAT(u.surname,' ',u.name,' (',SUBSTRING(c.datetime,1,10),'):\n',c.text)
					ORDER BY datetime DESC SEPARATOR ', \n\n') AS a_comment
					FROM comments c
					JOIN users u ON c.user_id = u.id
					GROUP BY c.comments_thread_id
				) c ON a.comments_thread_id = c.comments_thread_id
				LEFT JOIN comments_threads ct ON a.comments_thread_id = ct.id
				LEFT JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations f ON e.value = f.original_term AND lang = ?
				WHERE mw.since <= CURDATE() AND mw.until >= CURDATE()
			) m
			$where
			GROUP BY m.id
			ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
			LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", Config::get('lang'));
	}

	/**
	 * Counts members whose are whitelisted.
	 * 
	 * @author Jiri Svitak
	 * @return integer
	 */
	public function count_whitelisted_members($filter_sql = '')
	{
		$where = "";
		if ($filter_sql)
		{
			$where = "WHERE $filter_sql";
		}
		
		return $this->db->query("
			SELECT COUNT(*) AS total FROM
			(
				SELECT m.id FROM
				(
					SELECT
						m.id, IFNULL(f.translated_term, e.value) AS type,
						m.name AS member_name, a.balance,
						IF(mw.permanent > 0, 1, 2) AS whitelisted
					FROM members m
					JOIN members_whitelists mw ON mw.member_id = m.id
					LEFT JOIN accounts a ON a.member_id = m.id AND m.id <> 1
					LEFT JOIN comments_threads ct ON a.comments_thread_id = ct.id
					LEFT JOIN enum_types e ON m.type = e.id
					LEFT JOIN translations f ON e.value = f.original_term AND lang = ?
					WHERE mw.since <= CURDATE() AND mw.until >= CURDATE()
				) m
				$where
				GROUP BY m.id
			) m
		", Config::get('lang'))->current()->total;
	}
	
	/**
	 * Gets whitelists of a given member
	 * 
	 * @param integer $member_id
	 */
	public function get_member_whitelists($member_id)
	{
		return $this->db->query("
			SELECT mw.*, IF(mw.since <= CURDATE() AND mw.until >= CURDATE(), 1, 0) AS active
			FROM members_whitelists mw
			WHERE mw.member_id = ?
			ORDER BY permanent DESC, until DESC, since DESC
		", $member_id);
	}

	/**
	 * Checks whether a member with the specified ID is whitelisted right now.
	 *
	 * @param int $member_id
	 * @return boolean
	 */
	public function is_whitelisted_now($member_id)
	{
		return $this->db->query("
			SELECT COUNT(mw.id) AS count
			FROM members_whitelists mw
			WHERE mw.member_id = ? AND mw.since <= CURDATE()
				AND mw.until >= CURDATE()
		", $member_id)->current()->count > 0;
	}
	
	/**
	 * Checks if the given interval is unique in users whitelists
	 * 
	 * @param indeger $member_id Owner ID
	 * @param boolean $permanent
	 * @param string $since Date
	 * @param string $until Date
	 * @param integer $mw_id ID of member whitelist on editing or null on adding
	 * @return boolean
	 */
	public function exists($member_id, $permanent, $since, $until, $mw_id = NULL)
	{
		$cond = '';
		
		if (intval($mw_id))
		{
			$cond = ' AND id <> ' . intval($mw_id);
		}
		
		if ($permanent)
		{
			$cond .= ' AND permanent > 0';
		}
		else
		{
			$cond .= ' AND ((
					? BETWEEN since AND until OR ? BETWEEN since AND until
				) OR (
					since BETWEEN ? and ? AND until BETWEEN ? AND ?
				))';
		}
		
		return $this->db->query("
			SELECT COUNT(*) AS c
			FROM members_whitelists mw
			WHERE mw.member_id = ? $cond
		", array
		(
			$member_id,
			$since, $until,
			$since, $until,
			$since, $until
		))->current()->c > 0;
	}

}
