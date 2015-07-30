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
 * Users works 
 * 
 * @author Michal Kliment
 * @package Model
 * 
 * @property integer $job_report_id
 * @property Job_report_Model $job_report
 * @property integer $user_id
 * @property User_Model $user
 * @property integer $approval_template_id
 * @property Approval_template_Model $approval_template
 * @property integer $added_by_id
 * @property User_Model $added_by
 * @property string $description
 * @property double $suggest_amount
 * @property string $date
 * @property string $create_date
 * @property double $hours
 * @property integer $km
 * @property integer $previous_rejected_work_id
 * @property Job_Model $previous_rejected_work
 */
class Job_Model extends ORM
{
	protected $belongs_to = array
	(
			'user', 'added_by' => 'user',
			'approval_template', 'job_report',
			'previous_rejected_work' => 'job'
	);

	/**
	 * Function to return all pending works
	 * 
	 * @author Michal Kliment
	 * @param number $limit_from
	 * @param number $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_all_works(
			$limit_from = NULL, $limit_results = NULL, $order_by = NULL,
			$order_by_direction = NULL, $filter_sql = '')
	{
		$where = '';
		$order = '';
		$limit = '';
		
		if ($order_by)
		{
			// order by direction check
			if (strtolower($order_by_direction) != 'desc')
				$order_by_direction = 'asc';
			
			$order = 'ORDER BY ' . $this->db->escape_column($order_by) . ' '.$order_by_direction;
		}
		
		if ($filter_sql != '')
			$where = 'WHERE '.$filter_sql;
		
		if (!is_null($limit_from) && !is_null($limit_results))
			$limit = 'LIMIT '.intval ($limit_from).', '.$limit_results;
		
		return $this->db->query("
			SELECT
				j.id, j.user_id, uname, description, suggest_amount, date, state,
				hours, km,
				(
					SELECT COUNT(*)
					FROM votes
					WHERE vote = ? AND votes.fk_id = j.id AND votes.type = ?
				) AS agree_count,
				(
					SELECT COUNT(*)
					FROM votes
					WHERE vote = ? AND votes.fk_id = j.id AND votes.type = ?
				) AS disagree_count,
				(
					SELECT COUNT(*)
					FROM votes
					WHERE vote = ? AND votes.fk_id = j.id AND votes.type = ?
				) AS abstain_count,
				SUM(j.vote) AS approval_state,
				GROUP_CONCAT(
					DISTINCT vote_comment
					SEPARATOR  '\n\n'
				) AS vote_comments,
				COUNT(DISTINCT comment_id) AS comments_count,
				GROUP_CONCAT(DISTINCT j.comment SEPARATOR  '\n\n') AS comments,
				v.vote, v.comment
			FROM 
			(
				SELECT j.*, CONCAT(u.name,' ',u.surname) AS uname,
				IFNULL(vote, 0) AS vote,
				CONCAT(
					vu.name, ' ',vu.surname,' (',
					SUBSTRING(v.time,1,10),'): \n',vn.name,
					IF(v.comment NOT LIKE '', ' - ',''),v.comment
				) AS vote_comment,
				c.id AS comment_id,
				CONCAT(
					cu.name,' ', cu.surname,' (',
					SUBSTRING(c.datetime, 1, 10),'): \n', c.text
				) AS comment
				FROM jobs j
				JOIN users u ON j.user_id = u.id
				LEFT JOIN votes v ON v.fk_id = j.id AND v.type = ?
				LEFT JOIN
				(
					SELECT ? AS name, ? AS value
					UNION
					SELECT ? AS name, ? AS value
					UNION
					SELECT ? AS name, ? AS value
				) vn ON v.vote = vn.value
				LEFT JOIN users vu ON v.user_id = vu.id
				LEFT JOIN comments_threads ct ON j.comments_thread_id = ct.id
				LEFT JOIN comments c ON c.comments_thread_id = ct.id
				LEFT JOIN users cu ON c.user_id = cu.id
				WHERE j.job_report_id IS NULL
			) j
			LEFT JOIN votes v ON v.fk_id = j.id AND v.type = ? AND v.user_id = ?
			$where
			GROUP BY j.id
			$order
			$limit
		", array
		(
			Vote_Model::AGREE, Vote_Model::WORK,
			Vote_Model::DISAGREE, Vote_Model::WORK,
			Vote_Model::ABSTAIN, Vote_Model::WORK,
			Vote_Model::WORK,
			Vote_Model::get_vote_option_name(Vote_Model::AGREE), Vote_Model::AGREE,
			Vote_Model::get_vote_option_name(Vote_Model::DISAGREE), Vote_Model::DISAGREE,
			Vote_Model::get_vote_option_name(Vote_Model::ABSTAIN), Vote_Model::ABSTAIN,
			Vote_Model::WORK, Session::instance()->get('user_id'),
		));
	}

	/**
	 * Counts all rejected works
	 * 
	 * @author Michal Kliment
	 * @param array $filter_values
	 * @return number
	 */
	public function count_all_works($filter_sql = '')
	{
		return count(
			$this->get_all_works(NULL, NULL, '', '', $filter_sql)
		);
	}

	/**
	 * Function to return all pending works of user
	 * @author Michal Kliment
	 * 
	 * @param number $user_id
	 * @return Mysql_Result
	 */
	public function get_all_pending_works_by_user($user_id)
	{
		// query
		return $this->db->query("
				SELECT q.*, (agree_count - disagree_count) AS approval_state FROM (
					SELECT j.*, CONCAT(u.name, ' ', u.surname) AS uname, v.vote, v.comment,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = 1 AND votes.fk_id = j.id AND votes.type = 1
					) AS agree_count,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = -1 AND votes.fk_id = j.id AND votes.type = 1
					) AS disagree_count,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = 0 AND votes.fk_id = j.id AND votes.type = 1
					) AS abstain_count,
					(
						SELECT COUNT(*)
						FROM comments c WHERE j.comments_thread_id = c.comments_thread_id
					) AS comments_count,
					(
						SELECT GROUP_CONCAT(user, ' (',SUBSTRING(c.datetime,1,10),'): \n',c.text SEPARATOR ', \n\n') FROM
						(
							SELECT c.*, CONCAT(u.surname,' ',u.name) AS user
							FROM comments c
							LEFT JOIN users u ON c.user_id = u.id
							ORDER BY datetime DESC
						) AS c WHERE c.comments_thread_id = j.comments_thread_id
						GROUP BY c.comments_thread_id
					) AS comments,
					(
						SELECT GROUP_CONCAT(comment SEPARATOR ', \n\n') FROM
						(
							SELECT v.fk_id, v.type, CONCAT(u.surname,' ',u.name,' (',SUBSTRING(v.time,1,10),'): \n',
									IF(v.vote=1,?,IF(v.vote=-1,?,?)),
									IF(v.comment NOT LIKE '',' - ',''), v.comment
								) AS comment FROM votes v
							LEFT JOIN users u ON v.user_id = u.id
							ORDER BY v.vote DESC
						) AS v
						WHERE v.fk_id = j.id AND v.type = 1
						GROUP BY fk_id
					) AS vote_comments
					FROM jobs j
					LEFT JOIN users u ON j.user_id = u.id
					LEFT JOIN votes v ON j.id = v.fk_id AND v.type =1 AND v.user_id = ?
					WHERE j.state <= 1 AND j.job_report_id IS NULL AND j.user_id = ?) AS q"
		, array
		(
			__('Agree'),
			__('Disagree'),
			__('Abstain'),
			Session::instance()->get('user_id'),
			$user_id
		));;
	}

	/**
	 * Function to return all rejected works of user
	 * 
	 * @author Michal Kliment
	 * @param number $user_id
	 * @return Mysql_Result
	 */
	public function get_all_rejected_works_by_user($user_id)
	{
		// query
		return $this->db->query("
				SELECT q.*, (agree_count - disagree_count) AS approval_state FROM (
					SELECT j.*,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = 1 AND votes.fk_id = j.id AND votes.type = 1
					) AS agree_count,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = -1 AND votes.fk_id = j.id AND votes.type = 1
					) AS disagree_count,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = 0 AND votes.fk_id = j.id AND votes.type = 1
					) AS abstain_count,
					(
						SELECT COUNT(*)
						FROM comments c
						WHERE j.comments_thread_id = c.comments_thread_id
					) AS comments_count,
					(
						SELECT GROUP_CONCAT(user, ' (',SUBSTRING(c.datetime,1,10),'): \n',c.text SEPARATOR ', \n\n') FROM
						(
							SELECT c.*, CONCAT(u.surname,' ',u.name) AS user
							FROM comments c
							LEFT JOIN users u ON c.user_id = u.id
							ORDER BY datetime DESC
						) AS c WHERE c.comments_thread_id = j.comments_thread_id
						GROUP BY c.comments_thread_id
					) AS comments,
					(
						SELECT GROUP_CONCAT(comment SEPARATOR ', \n\n') FROM
						(
							SELECT v.fk_id, v.type, CONCAT(u.surname,' ',u.name,' (',SUBSTRING(v.time,1,10),'): \n',
									IF(v.vote=1,?,IF(v.vote=-1,?,?)),
									IF(v.comment NOT LIKE '',' - ',''), v.comment
								) AS comment
							FROM votes v
							LEFT JOIN users u ON v.user_id = u.id
							ORDER BY v.vote DESC
						) AS v
						WHERE v.fk_id = j.id AND v.type = 1
						GROUP BY fk_id
					) AS vote_comments
				FROM jobs j
				WHERE j.state = 2 AND j.job_report_id IS NULL AND j.user_id = ?) AS q"
		, array
		(
			__('Agree'),
			__('Disagree'),
			__('Abstain'),
			$user_id
		));
	}

	/**
	 * Function to return all approved works of user
	 * 
	 * @author Michal Kliment
	 * @param number $user_id
	 * @return Mysql_Results
	 */
	public function get_all_approved_works_by_user($user_id)
	{
		// query
		return $this->db->query("
				SELECT q.*, (agree_count - disagree_count) AS approval_state
				FROM
				(
					SELECT j.*, j.suggest_amount as rating,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = 1 AND votes.fk_id = j.id AND votes.type = 1
					) AS agree_count,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = -1 AND votes.fk_id = j.id AND votes.type = 1
					) AS disagree_count,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = 0 AND votes.fk_id = j.id AND votes.type = 1
					) AS abstain_count,
					(
						SELECT COUNT(*)
						FROM comments c
						WHERE j.comments_thread_id = c.comments_thread_id
					) AS comments_count,
					(
						SELECT GROUP_CONCAT(user, ' (',SUBSTRING(c.datetime,1,10),'): \n',c.text SEPARATOR ', \n\n') FROM
						(
							SELECT c.*, CONCAT(u.surname,' ',u.name) AS user FROM comments c
							LEFT JOIN users u ON c.user_id = u.id
							ORDER BY datetime DESC
						) AS c WHERE c.comments_thread_id = j.comments_thread_id GROUP BY c.comments_thread_id
					) AS comments,
					(
						SELECT GROUP_CONCAT(comment SEPARATOR ', \n\n') FROM
						(
							SELECT v.fk_id, v.type, CONCAT(u.surname,' ',u.name,' (',SUBSTRING(v.time,1,10),'): \n',
									IF(v.vote=1,?,IF(v.vote=-1,?,?)),
									IF(v.comment NOT LIKE '',' - ',''), v.comment) AS comment FROM votes v
							LEFT JOIN users u ON v.user_id = u.id
							ORDER BY v.vote DESC
						) AS v
						WHERE v.fk_id = j.id AND v.type = 1
						GROUP BY fk_id
					) AS vote_comments
				FROM jobs j
				LEFT JOIN transfers t ON j.transfer_id = t.id
				WHERE j.state = 3 AND j.job_report_id IS NULL AND j.user_id = ?) AS q"
		, array
		(
			__('Agree'),
			__('Disagree'),
			__('Abstain'),
			$user_id
		));
	}

	/**
	 * Function to return all works belongs to work report
	 * 
	 * @author Michal Kliment
	 * @param number $job_report_id
	 * @return Mysql_Result
	 */
	public function get_all_works_by_job_report_id($job_report_id)
	{
		return $this->db->query("
				SELECT q.*, (agree_count - disagree_count) AS approval_state,
					IF(q.state = 3, 1, 0) AS approved
				FROM (SELECT j.*, CONCAT(u.name, ' ', u.surname) AS uname, v.vote, v.comment,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = 1 AND votes.fk_id = j.id AND votes.type = 1
					) AS agree_count,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = -1 AND votes.fk_id = j.id AND votes.type = 1
					) AS disagree_count,
					(
						SELECT COUNT(*)
						FROM votes
						WHERE vote = 0 AND votes.fk_id = j.id AND votes.type = 1
					) AS abstain_count,
					(
						SELECT COUNT(*)
						FROM comments c
						WHERE j.comments_thread_id = c.comments_thread_id
					) AS comments_count,
					(
						SELECT GROUP_CONCAT(user, ' (',SUBSTRING(c.datetime,1,10),'): \n',c.text SEPARATOR ', \n\n') FROM
						(
							SELECT c.*, CONCAT(u.surname,' ',u.name) AS user
							FROM comments c
							LEFT JOIN users u ON c.user_id = u.id
							ORDER BY datetime DESC
						) AS c
						WHERE c.comments_thread_id = j.comments_thread_id
						GROUP BY c.comments_thread_id
					) AS comments,
					(
						SELECT GROUP_CONCAT(comment SEPARATOR ', \n\n') FROM
						(
							SELECT v.fk_id, v.type, CONCAT(u.surname,' ',u.name,' (',SUBSTRING(v.time,1,10),'): \n',
								IF(v.vote=1,?,IF(v.vote=-1,?,?)),
								IF(v.comment NOT LIKE '',' - ',''), v.comment) AS comment
							FROM votes v
							LEFT JOIN users u ON v.user_id = u.id
							ORDER BY v.vote DESC
						) AS v
						WHERE v.fk_id = j.id AND v.type = 1
						GROUP BY fk_id
					) AS vote_comments
				FROM jobs j
				LEFT JOIN users u ON j.user_id = u.id
				LEFT JOIN votes v ON j.id = v.fk_id AND v.type =1 AND v.user_id = ?
				WHERE j.job_report_id = ?) AS q
				ORDER BY date
		", array
		(
			__('Agree'),
			__('Disagree'),
			__('Abstain'),
			Session::instance()->get('user_id'),
			$job_report_id
		));
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
	
}
