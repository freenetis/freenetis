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
 * Requests of an user to leader if the association. Request is specified by
 * type that defines what user who create it want to achieve with it,
 * description, date and optionaly with suggested amount mony that are
 * required for full filling of request.
 * Each request is accepted/declined by association by voting that is define
 * by related approval template.
 * 
 * @author Michal Kliment, Ondřej Fibich
 * @package Model
 * 
 * @property integer $type
 * @property integer $user_id
 * @property User_Model $user
 * @property integer $approval_template_id
 * @property Approval_template_Model $approval_template
 * @property string $description
 * @property double $suggest_amount
 * @property string $date
 */
class Request_Model extends ORM
{
	protected $belongs_to = array
	(
		'user', 'approval_template'
	);
	
	/** 
	 * request is made for support (a member what to help with a thing that he
	 * do not undestand or he/she is not authorised to do it)
	 */
	const TYPE_SUPPORT = 0;
	
	/** 
	 * request is an proposal on the association that may contains information 
	 * about proposal cost
	 */
	const TYPE_PROPOSAL = 1;
	
	/** new request, one votes about it, can be edited */
	const STATE_NEW = 0;
	
	/** open request, approval already started, cannot be edited */
	const STATE_OPEN = 1;
	
	/** rejected request */
	const STATE_REJECTED = 2;
	
	/** approved request */
	const STATE_APPROVED = 3;
	
	/** types */
	private static $types = array
	(
		self::TYPE_SUPPORT	=> 'support request',
		self::TYPE_PROPOSAL	=> 'proposal to association'
	);
	
	/** states */
	private static $states = array
	(
		self::STATE_NEW			=> 'pending',
		self::STATE_OPEN		=> 'pending',
		self::STATE_REJECTED	=> 'rejected',
		self::STATE_APPROVED	=> 'approved'
	);
	
	/**
	 * Gets mane to a type
	 * 
	 * @param integer $type
	 * @param boolean $translate Translate types? [default: TRUE]
	 * @return string
	 */
	public static function get_type_name($type, $translate = TRUE)
	{
		if (isset(self::$types[$type]))
		{
			if ($translate)
			{
				return __(self::$types[$type]);
			}
			else
			{
				return self::$types[$type];
			}
		}
		return NULL;
	}
	
	/**
	 * Gets possible types of a proposal
	 * 
	 * @param boolean $translate Translate types? [default: TRUE]
	 * @return array
	 */
	public static function get_types($translate = TRUE)
	{
		if ($translate)
		{
			return array_map('__', self::$types);
		}
		return self::$types;
	}
	
	/**
	 * Indicate whether a request with a type that is given by a param may
	 * contains data in suggest_amount attribute.
	 * 
	 * @param integer $type Request type constant
	 * @return boolean
	 */
	public static function has_suggest_amount($type)
	{
		return ($type == self::TYPE_PROPOSAL);
	}

		/**
	 * Returns state name
	 * 
	 * @author Michal Kliment
	 * @param integer $state
	 * @return string
	 */
	public static function get_state_name($state)
	{
		if (array_key_exists($state, self::$states))
		{
			return __(self::$states[$state]);
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Returns all requests
	 * 
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql
	 * @return MySQL_Iterator
	 */
	public function get_all_requests($limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'ASC', $filter_sql = '')
	{
		$limit = '';
		
		if ($limit_from && $limit_results)
		{
			$limit = 'LIMIT '.intval($limit_from).', '.$limit_results;
		}
		
		$where = '';
		
		if ($filter_sql != '')
		{
			$where = 'WHERE '.$filter_sql;
		}
		
		return $this->db->query("
			SELECT
				r.id, r.user_id, uname, description, suggest_amount, date, state,
				(
					SELECT COUNT(*)
					FROM votes
					WHERE vote = ? AND votes.fk_id = r.id AND votes.type = ?
				) AS agree_count,
				(
					SELECT COUNT(*)
					FROM votes
					WHERE vote = ? AND votes.fk_id = r.id AND votes.type = ?
				) AS disagree_count,
				(
					SELECT COUNT(*)
					FROM votes
					WHERE vote = ? AND votes.fk_id = r.id AND votes.type = ?
				) AS abstain_count,
				SUM(r.vote) AS approval_state,
				GROUP_CONCAT(vote_comment SEPARATOR  '\n\n') AS vote_comments,
				COUNT(comment_id) AS comments_count,
				GROUP_CONCAT(r.comment SEPARATOR  '\n\n') AS comments,
				v.vote, v.comment, r.type
			FROM 
			(
				SELECT r.*, CONCAT(u.name,' ',u.surname) AS uname,
				IFNULL(vote,0) AS vote,
				CONCAT(
					vu.name, ' ',vu.surname,' (',
					SUBSTRING(v.time,1,10),'): \n',vn.name,
					IF(v.comment NOT LIKE '', '-',''),v.comment
				) AS vote_comment,
				c.id AS comment_id,
				CONCAT(
					cu.name,' ', cu.surname,' (',
					SUBSTRING(c.datetime, 1, 10),'): \n', c.text
				) AS comment
				FROM requests r
				JOIN users u ON r.user_id = u.id
				LEFT JOIN votes v ON v.fk_id = r.id AND v.type = ?
				LEFT JOIN
				(
					SELECT ? AS name, ? AS value
					UNION
					SELECT ? AS name, ? AS value
					UNION
					SELECT ? AS name, ? AS value
				) vn ON v.vote = vn.value
				LEFT JOIN users vu ON v.user_id = vu.id
				LEFT JOIN comments_threads ct ON r.comments_thread_id = ct.id
				LEFT JOIN comments c ON c.comments_thread_id = ct.id
				LEFT JOIN users cu ON c.user_id = cu.id
			) r
			LEFT JOIN votes v ON v.fk_id = r.id AND v.type = ? AND v.user_id = ?
			$where
			GROUP BY r.id
			$limit
		", array
		(
			Vote_Model::AGREE, Vote_Model::REQUEST,
			Vote_Model::DISAGREE, Vote_Model::REQUEST,
			Vote_Model::ABSTAIN, Vote_Model::REQUEST,
			Vote_Model::REQUEST,
			Vote_Model::get_vote_option_name(Vote_Model::AGREE), Vote_Model::AGREE,
			Vote_Model::get_vote_option_name(Vote_Model::DISAGREE), Vote_Model::DISAGREE,
			Vote_Model::get_vote_option_name(Vote_Model::ABSTAIN), Vote_Model::ABSTAIN,
			Vote_Model::REQUEST, Session::instance()->get('user_id'),
		));
	}
	
	/**
	 * Counts all requests
	 * 
	 * @author Michal Kliment
	 * @param string $filter_sql
	 * @return integer
	 */
	public function count_all_requests($filter_sql = '')
	{
		return count($this->get_all_requests(NULL, NULL, NULL, NULL, $filter_sql));
	}
	
	/**
	 * Only returns suggest amount of work
	 * 
	 * @author Michal Kliment
	 * @return integer
	 */
	public function get_suggest_amount()
	{	
		return $this->suggest_amount;
	}
	
	/**
	 * Function to return all requests of user
	 * @author Michal Kliment
	 * 
	 * @param number $user_id
	 * @return Mysql_Result
	 */
	public function get_all_requests_by_user($user_id, $state = '')
	{
		$where = '';
		
		switch ($state)
		{
			case 'pending':
				$where = ' AND (state = '.Vote_Model::STATE_NEW.' OR state = '.Vote_Model::STATE_OPEN.')';
				break;
			
			case 'approved':
				$where = ' AND state = '.Vote_Model::STATE_APPROVED;
				break;
			
			case 'rejected':
				$where = ' AND state = '.Vote_Model::STATE_REJECTED;
				break;
		}
		
		// query
		return $this->db->query("
			SELECT
				r.id, description, suggest_amount, date, state, r.type,
				(
					SELECT COUNT(*)
					FROM votes
					WHERE vote = ? AND votes.fk_id = r.id AND votes.type = ?
				) AS agree_count,
				(
					SELECT COUNT(*)
					FROM votes
					WHERE vote = ? AND votes.fk_id = r.id AND votes.type = ?
				) AS disagree_count,
				(
					SELECT COUNT(*)
					FROM votes
					WHERE vote = ? AND votes.fk_id = r.id AND votes.type = ?
				) AS abstain_count,
				SUM(r.vote) AS approval_state,
				GROUP_CONCAT(vote_comment SEPARATOR  '\n\n') AS vote_comments,
				COUNT(comment_id) AS comments_count,
				GROUP_CONCAT(r.comment SEPARATOR  '\n\n') AS comments
			FROM 
			(
				SELECT r.*, CONCAT(u.name,' ',u.surname) AS uname,
				IFNULL(vote,0) AS vote,
				CONCAT(
					vu.name, ' ',vu.surname,' (',
					SUBSTRING(v.time,1,10),'): \n',vn.name,
					IF(v.comment NOT LIKE '', '-',''),v.comment
				) AS vote_comment,
				c.id AS comment_id,
				CONCAT(
					cu.name,' ', cu.surname,' (',
					SUBSTRING(c.datetime, 1, 10),'): \n', c.text
				) AS comment
				FROM requests r
				JOIN users u ON r.user_id = u.id
				LEFT JOIN votes v ON v.fk_id = r.id AND v.type = ?
				LEFT JOIN
				(
					SELECT ? AS name, ? AS value
					UNION
					SELECT ? AS name, ? AS value
					UNION
					SELECT ? AS name, ? AS value
				) vn ON v.vote = vn.value
				LEFT JOIN users vu ON v.user_id = vu.id
				LEFT JOIN comments_threads ct ON r.comments_thread_id = ct.id
				LEFT JOIN comments c ON c.comments_thread_id = ct.id
				LEFT JOIN users cu ON c.user_id = cu.id
			) r
			WHERE r.user_id = ? $where
			GROUP BY r.id
		", array
		(
			Vote_Model::AGREE, Vote_Model::REQUEST,
			Vote_Model::DISAGREE, Vote_Model::REQUEST,
			Vote_Model::ABSTAIN, Vote_Model::REQUEST,
			Vote_Model::REQUEST,
			Vote_Model::get_vote_option_name(Vote_Model::AGREE), Vote_Model::AGREE,
			Vote_Model::get_vote_option_name(Vote_Model::DISAGREE), Vote_Model::DISAGREE,
			Vote_Model::get_vote_option_name(Vote_Model::ABSTAIN), Vote_Model::ABSTAIN,
			$user_id
		));
	}
}
