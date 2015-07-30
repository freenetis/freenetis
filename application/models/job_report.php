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
 * Model for job(work) reports. Groups works to one report.
 * 
 * @author Ondřej Fibich
 * @package Model
 * 
 * @property integer $user_id
 * @property User_Model $user
 * @property integer $added_by_id
 * @property User_Model $added_by
 * @property integer $approval_template_id
 * @property Approval_template_Model $approval_template
 * @property string $description
 * @property string $type
 * @property double $price_per_hour
 * @property double $price_per_km
 * @property boolean $concept
 * @property ORM_Iterator $jobs
 * @property integer $transfer_id
 * @property Transfer_Model $transfer
 * @property integer $payment_type
 */
class Job_report_Model extends ORM
{
	protected $belongs_to = array
	(
			'user', 'approval_template', 'transfer',
			'added_by' => 'user'
	);
	
	protected $has_many = array('jobs');
	
	/** Constant of credit payment for column payment_type */
	const PAYMENT_BY_CREDIT = 0;
	/** Constant of cash payment for column payment_type */
	const PAYMENT_BY_CASH = 1;

	/**
	 * Payments types
	 *
	 * @var array
	 */
	protected static $PAYMENT_TYPES = array
	(
		self::PAYMENT_BY_CREDIT	=> 'Payment by FreenetIS credit',
		self::PAYMENT_BY_CASH	=> 'Payment by cash'
	);
	
	/**
	 * Gets translated payments types
	 *
	 * @return array
	 */
	public static function get_payment_types()
	{
		return array_map('__', self::$PAYMENT_TYPES);
	}
	
	/**
	 * Gets name of payment type
	 *
	 * @param integer $type
	 * @return string
	 */
	public function get_name_of_payment_type($type = NULL)
	{
		if ($type === NULL && $this->id)
		{
			$type = $this->payment_type;
		}
		
		if (array_key_exists($type, self::$PAYMENT_TYPES))
		{
			return __(self::$PAYMENT_TYPES[$type]);
		}
		
		return __('Unknown type');
	}


	/**
	 * Gets state of work reports from it's works
	 *
	 * @author Ondřej Fibich
	 * @staticvar array $cache	Cache for states
	 * @return integer
	 */
	public function get_state()
	{
		static $cache = array();
		
		if (!$this->id)
		{
			return FALSE;
		}
		
		if (!isset($cache[$this->id]))
		{
			$cache[$this->id] = $this->db->query("
					SELECT IF(MIN(state) <= 1, MIN(state), MAX(state)) AS state
					FROM jobs
					WHERE job_report_id = ?
			", $this->id)->current()->state;
		}
		
		return $cache[$this->id];
	}

	/**
	 * Gets suggest amount of work report from it's works
	 *
	 * @author Ondřej Fibich
	 * @staticvar array $cache	Cache for amounts
	 * @return double
	 */
	public function get_suggest_amount()
	{
		static $cache = array();
		
		if (!$this->id)
		{
			return FALSE;
		}
		
		if (!isset($cache[$this->id]))
		{
			$cache[$this->id] = $this->db->query("
					SELECT IFNULL(SUM(suggest_amount), 0) AS suggest_amount
					FROM jobs
					WHERE job_report_id = ?
			", $this->id)->current()->suggest_amount;
		}
		
		return $cache[$this->id];
	}

	/**
	 * Gets rating of work report from it's works
	 *
	 * @author Ondřej Fibich
	 * @staticvar array $cache	Cache for amounts
	 * @return double
	 */
	public function get_rating()
	{
		static $cache = array();
		
		if (!$this->id)
		{
			return FALSE;
		}
		
		if (!isset($cache[$this->id]))
		{
			$cache[$this->id] = $this->db->query("
					SELECT IFNULL(SUM(suggest_amount), 0) AS amount
					FROM jobs
					WHERE job_report_id = ? AND state = 3
			", $this->id)->current()->amount;
		}
		
		return $cache[$this->id];
	}
	
	/**
	 * Gets count of unvoted work reports of voter
	 * 
	 * @param integer $user_id	ID of voter
	 * @return integer
	 */
	public function get_count_of_unvoted_work_reports_of_voter($user_id)
	{
		return $this->db->query("
				SELECT IFNULL(COUNT(*), 0) AS count
				FROM (
					SELECT jr.id
					FROM groups_aro_map g
					LEFT JOIN approval_types at ON at.aro_group_id = g.group_id
					LEFT JOIN approval_template_items ati ON at.id = ati.approval_type_id
					LEFT JOIN job_reports jr ON jr.approval_template_id = ati.approval_template_id
					LEFT JOIN jobs j ON j.job_report_id = jr.id
					LEFT JOIN votes v ON v.fk_id = j.id AND v.user_id = ?
					WHERE
						g.aro_id = ? AND
						j.job_report_id IS NOT NULL AND
						v.id IS NULL AND
						jr.concept = 0
					GROUP BY
						jr.id, at.min_suggest_amount
					HAVING
						MIN(j.state) <= 1 AND
						SUM(j.suggest_amount) >= at.min_suggest_amount
				) q
		", $user_id, $user_id)->current()->count;
	}
	
	
	/**
	 * Gets votes of a voter on a work report
	 * 
	 * @param integer $work_report_id	ID of report
	 * @param integer $user_id			ID of voter
	 * @return Mysql_Result
	 */
	public function get_votes_of_voter_on_work_report($work_report_id, $user_id)
	{
		return $this->db->query("
				SELECT v.id, v.vote
				FROM jobs j
				LEFT JOIN votes v ON v.fk_id = j.id AND v.user_id = ?
				WHERE j.job_report_id = ?
		", $user_id, $work_report_id);
	}
	
	/**
	 * Gets workd of montly workreport in array, blank days are filled by NULL
	 *
	 * @see Work_reports_Controller#edit()
	 * @author Ondřej Fibich
	 * @return array[object]
	 */
	public function get_works_of_monthly_workreport()
	{
		if (!$this->id || empty($this->type))
		{
			return array();
		}
		
		$jobs = array();
		$job_model = new Job_Model();
		$jobs_in_report = $job_model->get_all_works_by_job_report_id($this->id);
		
		$year = intval(substr($this->type, 0, 4));
		$month = intval(substr($this->type, 5, 6));
		
		for ($i = 1; $i <= date::days_of_month($month, $year); $i++)
		{
			$day = ($i < 10) ? '0' . $i : $i;
			$jobs[$i] = NULL;
			
			if ($jobs_in_report->current() &&
				$jobs_in_report->current()->date == $this->type . '-' . $day)
			{
				$jobs[$i] = $jobs_in_report->current();
				$jobs_in_report->next();
			}
		}
		
		return $jobs;
	}
	
	/**
	 * Gets work report with details
	 *
	 * @see Work_reports_Controller#edit()
	 * @author Ondřej Fibich
	 * 
	 * @param integer $work_report_id
	 * @return object
	 */
	public function get_work_report($work_report_id = NULL)
	{
		if ($work_report_id == NULL && $this->id)
		{
			$work_report_id = $this->id;
		}
		
		$result = $this->db->query("
				SELECT
					r.id, r.user_id, r.approval_template_id, r.description, 
					r.price_per_hour, r.price_per_km, r.type, r.concept,
					r.added_by_id, j.transfer_id, u.member_id, r.payment_type,
					CONCAT(u.name, ' ', u.surname) as uname,
					ROUND(SUM(j.suggest_amount), 2) AS suggest_amount,
					MIN(j.date) AS date_from,
					MAX(j.date) AS date_to, IFNULL(SUM(j.hours), 0) AS hours,
					ROUND(SUM(j.km), 2) AS km,
					IF(MIN(j.state) <= 1, MIN(j.state), MAX(j.state)) AS state
				FROM job_reports r
				LEFT JOIN users u ON u.id = r.user_id
				LEFT JOIN jobs j ON r.id = j.job_report_id
				GROUP BY r.id
				HAVING r.id = ?
		", $work_report_id);
		
		if ($result && $result->count())
		{
			return $result->current();
		}
		
		return FALSE;
	}
	
	/**
	 * Delete works of report
	 *
	 * @param array $preserved_keys		Array of preserved works (ID of work as value)
	 * @param integer $work_report_id	ID of report 
	 */
	public function delete_works($preserved_keys = array(), $work_report_id = NULL)
	{
		if ($work_report_id == NULL && $this->id)
		{
			$work_report_id = $this->id;
		}
		
		$where = '';
		
		if (is_array($preserved_keys) && count($preserved_keys))
		{
			array_map('intval', $preserved_keys);
			$where = "AND id NOT IN(" . implode(', ', $preserved_keys) . ")";
		}
		
		$this->db->query("
				DELETE FROM jobs
				WHERE job_report_id = ?
				$where
		", $work_report_id);
	}
	
	
	/**
	 * Gets all work reports with given state
	 *
	 * @param integer $state
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql
	 * @param boolean $lower Should be operant to state < (= otherwise)
	 * @return Mysql_Result
	 */
	private function _get_all_work_reports_with_state(
			$state, $limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'ASC', $filter_sql = '', $lower = FALSE)
	{
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// where
		if (!empty($filter_sql))
		{
			$filter_sql = 'WHERE ' . $filter_sql; 
		}
		// query
		return $this->db->query("
				SELECT * FROM
				(
					SELECT r.id, r.user_id, CONCAT(u.name, ' ', u.surname) as uname,
						r.description, ROUND(SUM(j.suggest_amount), 2) AS suggest_amount,
						MIN(j.date) AS date_from, MAX(j.date) AS date_to, r.type,
						ROUND(SUM(j.hours), 2) AS hours, SUM(j.km) AS km, r.payment_type,
						IF(MIN(state) <= 1, MIN(state), MAX(state)) AS state,
						IFNULL(t.amount, IF(r.payment_type = 1, ?, 0)) AS rating,
						r.transfer_id
					FROM job_reports r
					LEFT JOIN transfers t ON t.id = r.transfer_id
					LEFT JOIN users u ON u.id = r.user_id
					LEFT JOIN jobs j ON r.id = j.job_report_id
					WHERE r.concept = 0
					GROUP BY r.id
					HAVING state " . ($lower ? '<' : '=') . " ?
					ORDER BY " . $this->db->escape_column($order_by) . " " . $order_by_direction . "
					LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
				) wr $filter_sql
		", __('Payment by cash'), $state);
	}
	
	/**
	 * Gets all work reports of user by state
	 *
	 * @param integer $user_id
	 * @param integer $state
	 * @param boolean $lower Should be operant to state < (= otherwise)
	 * @return Mysql_Result
	 */
	private function _get_work_reports_of_user_by_state($user_id, $state, $lower = FALSE)
	{
		return $this->db->query('
				SELECT r.id, r.user_id, CONCAT(u.name, \' \', u.surname) as uname,
					r.description, ROUND(SUM(j.suggest_amount), 2) AS suggest_amount,
					MIN(j.date) AS date_from, MAX(j.date) AS date_to, r.type,
					SUM(j.hours) AS hours, ROUND(SUM(j.km), 2) AS km, r.transfer_id, 
					IF(MIN(state) <= 1, MIN(state), MAX(state)) AS state,
					IFNULL(t.amount, IF(r.payment_type = 1, ?, 0)) AS rating
				FROM job_reports r
				LEFT JOIN users u ON u.id = r.user_id
				LEFT JOIN transfers t ON t.id = r.transfer_id
				LEFT JOIN jobs j ON r.id = j.job_report_id
				WHERE r.concept = 0 AND r.user_id = ?
				GROUP BY r.id
				HAVING state ' . ($lower ? '<' : '=') . ' ?
		', __('Payment by cash'), $user_id, $state);
	}
	
	/**
	 * Counts all work reports with given state
	 *
	 * @param integer $state
	 * @param boolean $lower		Should be operant to state < (= otherwise)
	 * @param string $filter_sql
	 * @return integer
	 */
	private function _count_all_work_reports_with_state(
			$state, $lower = FALSE, $filter_sql = '')
	{
		// where
		if (!empty($filter_sql))
		{
			$filter_sql = 'WHERE ' . $filter_sql; 
		}
		// query
		return count($this->db->query("
				SELECT * FROM
				(
					SELECT r.id, r.user_id, CONCAT(u.name, ' ', u.surname) as uname,
						r.description, SUM(j.suggest_amount) AS suggest_amount,
						MIN(j.date) AS date_from, MAX(j.date) AS date_to, r.type,
						SUM(j.hours) AS hours, SUM(j.km) AS km, r.payment_type,
						IF(MIN(state) <= 1, MIN(state), MAX(state)) AS state
					FROM job_reports r
					LEFT JOIN users u ON u.id = r.user_id
					LEFT JOIN transfers t ON t.id = r.transfer_id
					LEFT JOIN jobs j ON r.id = j.job_report_id
					WHERE r.concept = 0
					GROUP BY r.id
					HAVING state " . ($lower ? '<' : '=') . " ?
				) wr $filter_sql
		", $state));
	}

	/**
	 * Gets all pending work reports 
	 *
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql			Search filter
	 * @return Mysql_Result
	 */
	public function get_all_pending_work_reports(
			$limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'ASC', $filter_sql = '')
	{
		return $this->_get_all_work_reports_with_state(
				2, $limit_from, $limit_results, $order_by,
				$order_by_direction, $filter_sql, TRUE
		);
	}

	/**
	 * Gets all approved work reports 
	 *
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql			Search filter
	 * @return Mysql_Result
	 */
	public function get_all_approved_work_reports(
			$limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'ASC', $filter_sql = '')
	{
		return $this->_get_all_work_reports_with_state(
				3, $limit_from, $limit_results, $order_by,
				$order_by_direction, $filter_sql
		);
	}

	/**
	 * Gets all rejected work reports 
	 *
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql			Search filter
	 * @return Mysql_Result
	 */
	public function get_all_rejected_work_reports(
			$limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'ASC', $filter_sql = '')
	{
		return $this->_get_all_work_reports_with_state(
				2, $limit_from, $limit_results, $order_by,
				$order_by_direction, $filter_sql
		);
	}

	/**
	 * Counts all pending work reports 
	 * 
	 * @param string $filter_sql	Search filter
	 * @return integer
	 */
	public function count_all_pending_work_reports($filter_sql = '')
	{
		return $this->_count_all_work_reports_with_state(2, TRUE);
	}

	/**
	 * Counts all approved work reports 
	 * 
	 * @param string $filter_sql	Search filter
	 * @return integer
	 */
	public function count_all_approved_work_reports($filter_sql = '')
	{
		return $this->_count_all_work_reports_with_state(3, FALSE, $filter_sql);
	}

	/**
	 * Counts all rejected work reports 
	 * 
	 * @param string $filter_sql	Search filter
	 * @return integer
	 */
	public function count_all_rejected_work_reports($filter_sql = '')
	{
		return $this->_count_all_work_reports_with_state(2, FALSE, $filter_sql);
	}
	
	/**
	 * Gets all concepted work reports of user
	 *
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_concepts_work_reports_of_user($user_id)
	{
		return $this->db->query("
				SELECT r.id, r.user_id, CONCAT(u.name, ' ', u.surname) as uname,
					r.description,
					IFNULL(ROUND(SUM(j.suggest_amount), 2), 0.0) AS suggest_amount,
					MIN(j.date) AS date_from, MAX(j.date) AS date_to, r.type,
					IFNULL(ROUND(SUM(j.hours), 2), 0) AS hours,
					IFNULL(SUM(j.km), 0) AS km,
					IF(MIN(state) <= 1, MIN(state), MAX(state)) AS state
				FROM job_reports r
				LEFT JOIN users u ON u.id = r.user_id
				LEFT JOIN jobs j ON r.id = j.job_report_id
				WHERE r.concept = 1 AND r.user_id = ?
				GROUP BY r.id
		", $user_id);
	}
	
	/**
	 * Gets all approved work reports of user
	 *
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_approved_work_reports_of_user($user_id)
	{
		return $this->_get_work_reports_of_user_by_state($user_id, 3);
	}
	
	/**
	 * Gets all rejected work reports of user
	 *
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_rejected_work_reports_of_user($user_id)
	{
		return $this->_get_work_reports_of_user_by_state($user_id, 2);
	}
	
	/**
	 * Gets all pending work reports of user
	 *
	 * @param integer $user_id
	 * @return Mysql_Result
	 */
	public function get_pending_work_reports_of_user($user_id)
	{
		return $this->_get_work_reports_of_user_by_state($user_id, 2, TRUE);
	}	

}
