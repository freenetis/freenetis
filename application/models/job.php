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
	public function get_all_pending_works(
			$limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'ASC', $filter_values = array())
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT q.*, (agree_count - disagree_count) AS approval_state
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
				WHERE j.state <= 1 AND j.job_report_id IS NULL) AS q
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", array
		(
			__('Agree'),
			__('Disagree'),
			__('Abstain'),
			Session::instance()->get('user_id')
		));
	}

	/**
	 * Function to return all approved works
	 * 
	 * @author Michal Kliment
	 * @param number $limit_from
	 * @param number $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_all_rejected_works(
			$limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'ASC',
			$filter_values = array())
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT q.*, (agree_count - disagree_count) AS approval_state
				FROM
				(
					SELECT j.*, CONCAT(u.name,' ', u.surname) AS uname,
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
				WHERE j.state = 2 AND j.job_report_id IS NULL) AS q
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", array
		(
			__('Agree'),
			__('Disagree'),
			__('Abstain')
		));
	}
	
	/**
	 * Function to return all approved works
	 * 
	 * @author Michal Kliment
	 * @param number $limit_from
	 * @param number $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_all_approved_works(
			$limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'ASC',
			$filter_values = array())
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT q.*, (agree_count - disagree_count) AS approval_state FROM (
					SELECT j.*, CONCAT(u.name,' ', u.surname) AS uname, t.amount as rating,
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
									) AS comment FROM votes v
								LEFT JOIN users u ON v.user_id = u.id
								ORDER BY v.vote DESC
						) AS v
						WHERE v.fk_id = j.id AND v.type = 1
						GROUP BY fk_id
					) AS vote_comments
				FROM jobs j
				LEFT JOIN users u ON j.user_id = u.id
				LEFT JOIN transfers t ON j.transfer_id = t.id
				WHERE j.state = 3 AND j.job_report_id IS NULL) AS q
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", array
		(
			__('Agree'),
			__('Disagree'),
			__('Abstain'),
			Session::instance()->get('user_id')
		));
	}

	/**
	 * Counts all rejected works
	 * 
	 * @author Michal Kliment
	 * @param array $filter_values
	 * @return number
	 */
	public function count_all_rejected_works($filter_values = array())
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM jobs j
				WHERE j.state = 2 AND j.job_report_id IS NULL
		")->current()->count;
	}

	/**
	 * Counts all rejected works
	 * 
	 * @author Michal Kliment
	 * @param array $filter_values
	 * @return number
	 */
	public function count_all_pending_works($filter_values = array())
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM jobs j
				WHERE j.state <= 1 AND j.job_report_id IS NULL
		")->current()->count;
		
	}
		
	/**
	 * @author Michal Kliment
	 * Counts all approved works
	 * @param array $filter_values
	 * @return number
	 */
	public function count_all_approved_works($filter_values = array())
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM jobs j
				WHERE j.state = 3 AND j.job_report_id IS NULL
		")->current()->count;
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
						) AS c WHERE c.comments_thread_id = j.comments_thread_id GROUP BY c.user_id
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
					SELECT j.*, t.amount as rating,
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
	 * Gets count of unvoted works of voter
	 * 
	 * @param integer $user_id	ID of voter
	 * @return integer
	 */
	public function get_count_of_unvoted_works_of_voter($user_id)
	{
		return $this->db->query("
				SELECT IFNULL(COUNT(j.id), 0) AS count
				FROM groups_aro_map g
				LEFT JOIN approval_types at ON at.aro_group_id = g.group_id
				LEFT JOIN approval_template_items ati ON at.id = ati.approval_type_id
				LEFT JOIN jobs j ON j.approval_template_id = ati.approval_template_id AND
									j.suggest_amount >= at.min_suggest_amount
				LEFT JOIN votes v ON v.fk_id = j.id AND v.user_id = ?
				WHERE g.aro_id = ? AND
					j.job_report_id IS NULL AND
					j.state <= 1 AND
					v.id IS NULL
		", $user_id, $user_id)->current()->count;
	}

	/**
	 * Function to get state of work approval from all votes
	 * by approval template and type.
	 * 
	 * @author Michal Kliment, Ondřej Fibich
	 * @param integer $type
	 * @return integer
	 */
	public function get_state($type, $approval_type_id = NULL)
	{
		if (!$this->id)
		{
			throw new Exception('Invalid call of method, object has to be loaded.');
		}
		
		$approval_template_item_model = new Approval_template_item_Model();
		$groups_aro_map_model = new Groups_aro_map_Model();
		$vote_model = new Vote_Model();
		
		$votes_count = $vote_model->where('type', $type)
				->where('fk_id', $this->id)
				->count_all();

		// 0 votes - vote not yet started
		if (!$votes_count)
		{
			$state = 0;
			return $state;
		}

		if (!empty($approval_type_id))
		{
			$approval_template_items = $approval_template_item_model
					->where('approval_template_id', $this->approval_template_id)
					->where('approval_type_id', $approval_type_id)
					->orderby('priority', 'desc')
					->find_all();
		}
		else
		{
			$approval_template_items = $approval_template_item_model
					->where('approval_template_id', $this->approval_template_id)
					->orderby('priority', 'desc')
					->find_all();
		}
		
		$arr_users = array();
		$state = 3;
		
		$suggest_amount = $this->suggest_amount;
		
		if ($this->job_report_id)
		{
			$suggest_amount = $this->job_report->get_suggest_amount();
		}

		foreach ($approval_template_items as $approval_template_item)
		{
			$at = $approval_template_item->approval_type;
			
			if (!$suggest_amount || $at->min_suggest_amount <= $suggest_amount)
			{
				$count = 0;
				$total_votes = 0;
				$agree = 0;
				$abstain = 0;
				
				$users = $groups_aro_map_model->get_all_users_by_group_id(
						$at->aro_group_id
				);
				
				foreach ($users as $user)
				{
					if (in_array($user->id, $arr_users))
					{
						continue;
					}
					
					$arr_users[] = $user->id;
					$count++;

					$vote = $vote_model->where('user_id', $user->id)
							->where('type', $type)
							->where('fk_id', $this->id)
							->find();

					if ((!$vote || !$vote->id))
					{
						$interval = date::hour_diff($at->interval);

						if (!$interval)
							$state = 1;
					}
					else
					{
						$total_votes++;

						if ($at->type == Approval_type_Model::SIMPLE &&
							$vote->vote == 0)
						{
							$abstain++;
						}

						if ($vote->vote == 1)
							$agree++;
					}
				}

				if (!$count)
					continue;

				if ($count == $total_votes)
				{
					$total_votes -= $abstain;

					$percent = ($total_votes) ? round($agree/$total_votes*100, 2) : 0;

					if ($percent < $at->majority_percent)
					{
						$state = 2;
						return $state;
					}
				}
				else
				{
					$interval = date::hour_diff($at->interval);
					if ($interval)
					{
						$count -= $abstain;

						$percent = ($count) ? round($agree/$count*100, 2) : 0;

						if ($percent < $at->majority_percent)
						{
							$agree += ($count - $total_votes + $abstain);

							$percent = ($count) ? round($agree/$count*100, 2) : 0;

							if ($percent < $at->majority_percent)
							{
								$state = 2;
								return $state;
							}
							else
								$state = 1;
						}
					}
					else
						$state = 1;
				}
			}
		}
		
		return $state;
	}
	
}
