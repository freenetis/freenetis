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
 * Vote
 * 
 * @package Model
 * 
 * @property integer $user_id
 * @property User_Model $user
 * @property integer $type
 * @property integer $fk_id
 * @property integer $aro_group_id
 * @property integer $priority
 * @property integer $vote
 * @property datetime $time
 * @property string $comment
 */
class Vote_Model extends ORM
{
	/** Type of wote for a work */
	const WORK	= 1;

	/**
	 * Vote options
	 *
	 * @var array
	 */
	public static $vote_options = array
	(
		NULL	=> 'None',
		0		=> 'Abstain',
		1		=> 'Agree',
		-1		=> 'Disagree',
	);
	
	protected $belongs_to = array('user');
	
	/**
	 * Get all wotes by work
	 * 
	 * @param integer $work_id 
	 * @return Mysql_Result
	 */
	public function get_all_votes_by_work($work_id)
	{
		return $this->db->query("
				SELECT * FROM votes v
				WHERE v.type = 1 AND v.fk_id = ?
		", $work_id);
	}
	
	/**
	 * Check if user voted about object
	 *
	 * @param integer $user_id	User's ID
	 * @param integer $fk_id	ID of object
	 * @return boolean
	 */
	public function has_user_voted_about($user_id, $fk_id)
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM votes
				WHERE user_id = ? AND fk_id = ?
		", $user_id, $fk_id)->current()->count > 0;
	}
}
