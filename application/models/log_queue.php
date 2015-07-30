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
 * Log queue for storing of logs and error logs into the database.
 * 
 * @author Ondřej Fibich
 * @package Model
 * 
 * @property integer $id
 * @property integer $type
 * @property integer $state
 * @property datetime $created_at
 * @property integer $closed_by_user_id
 * @property User_Model $closed_by_user
 * @property datetime $closed_at
 * @property string $description
 * @property string $exception_backtrace
 * @property integer $comments_thread_id
 * @property Comments_thread_Model $comments_thread
 */
class Log_queue_Model extends ORM
{
	// states of log
	
	/** New state (log is unclosed) */
	const STATE_NEW			= 0;
	/** Closed state (log is closed by an user) */
	const STATE_CLOSED		= 1;
	
	// type of log
	
	/** Fatal error type */
	const TYPE_FATAL_ERROR	= 0;
	/** Error type */
	const TYPE_ERROR			= 1;
	/** Warning type */
	const TYPE_WARNING		= 2;
	/** Information type */
	const TYPE_INFO			= 3;
	
	/**
	 * Types
	 * 
	 * @var array
	 */
	private static $types = array
	(
		self::TYPE_FATAL_ERROR	=> 'Fatal error',
		self::TYPE_ERROR			=> 'Error',
		self::TYPE_WARNING		=> 'Warning',
		self::TYPE_INFO			=> 'Information',
	);
	
	/**
	 * Type collors
	 *
	 * @var array
	 */
	private static $type_colors = array
	(
		self::TYPE_FATAL_ERROR	=> '#792020',
		self::TYPE_ERROR			=> '#cd0000',
		self::TYPE_WARNING		=> '#ff7800',
		self::TYPE_INFO			=> '#1e84db',
	);
	
	/**
	 * Types
	 * 
	 * @var array
	 */
	private static $states = array
	(
		self::STATE_NEW			=> 'New',
		self::STATE_CLOSED		=> 'Closed',
	);
	
	// database relations
	
	protected $belongs_to = array
	(
		'comments_thread', 'closed_by_user' => 'user'
	);
	
	// contruct
	
	public function __construct($id = NULL)
	{
		parent::__construct($id);
		$this->set_logger(FALSE);
	}
	
	// functions

	/**
	 * Gets types
	 * 
	 * @param boolean $translate Translate messages?
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
	 * Get name of log type.
	 * 
	 * @param integer $type Type of log
	 * @param boolean $translate Translate messages?
	 * @return string Name
	 */
	public static function get_type_name($type, $translated = TRUE)
	{
		if (array_key_exists($type, self::$types))
		{
			if ($translated)
			{
				return __(self::$types[$type]);
			}
			return self::$types[$type];
		}
		return NULL;
	}
	
	/**
	 * Gets type colors
	 * 
	 * @return array
	 */
	public static function get_type_colors()
	{
		return self::$type_colors;
	}
	
	/**
	 * Gets color of a type
	 * 
	 * @param int $type Log type
	 * @return string Color
	 */
	public static function get_type_color($type)
	{
		if (array_key_exists($type, self::$type_colors))
		{
			return self::$type_colors[$type];
		}
		return NULL;
	}
	
	/**
	 * Gets states
	 * 
	 * @param boolean $translate Translate messages?
	 * @return array
	 */
	public static function get_states($translate = TRUE)
	{
		if ($translate)
		{
			return array_map('__', self::$states);
		}
		return self::$states;
	}
	
	/**
	 * Gets state
	 * 
	 * @param int $state
	 * @param boolean $translate Translate messages?
	 * @return array
	 */
	public static function get_state($state, $translate = TRUE)
	{
		if (array_key_exists($state, self::$states))
		{
			if ($translate)
			{
				return __(self::$states[$state]);
			}
			else
			{
				return self::$states[$state];
			}
		}
		return NULL;
	}
	
	/**
	 * Creates new log.
	 * 
	 * @param type $type Type of error
	 * @param string $description Decription of error
	 * @param Exception $exception_backtrace [optional - empty by default]
	 * @param string $created_at [optional - default current datetime]
	 * @return Log_queue_Model Created model or null on error during saving
	 */
	protected static function log($type, $description,
			$exception_backtrace = NULL, $created_at = NULL)
	{
		if (empty($created_at))
		{
			$created_at = date('Y-m-d H:i:s');
		}
		
		if ($exception_backtrace instanceof Exception)
		{
			$exception_backtrace = $exception_backtrace->getTraceAsString();
		}
		
		try
		{
			$log_queue = new Log_queue_Model();
			$log_queue->type = $type;
			$log_queue->state = self::STATE_NEW;
			$log_queue->created_at = $created_at;
			$log_queue->description = $description;
			$log_queue->exception_backtrace = $exception_backtrace;
			$log_queue->save_throwable();
			return $log_queue;
		}
		catch (Exception $e)
		{
			Log::add_exception($e);
			return NULL;
		}
	}
	
	/**
	 * Creates new fatal error log.
	 * 
	 * @param string $description Decription of error
	 * @param Exception $exception_backtrace [optional - empty by default]
	 * @param string $created_at [optional - default current datetime]
	 * @return Log_queue_Model Created model
	 */
	public static function ferror($description,
			$exception_backtrace = NULL, $created_at = NULL)
	{
		self::log(self::TYPE_FATAL_ERROR, $description, $exception_backtrace, $created_at);
	}
	
	/**
	 * Creates new error log.
	 * 
	 * @param string $description Decription of error
	 * @param Exception $exception_backtrace [optional - empty by default]
	 * @param string $created_at [optional - default current datetime]
	 * @return Log_queue_Model Created model
	 */
	public static function error($description,
			$exception_backtrace = NULL, $created_at = NULL)
	{
		self::log(self::TYPE_ERROR, $description, $exception_backtrace, $created_at);
	}
	
	/**
	 * Creates new warning log.
	 * 
	 * @param string $description Decription of error
	 * @param Exception $exception_backtrace [optional - empty by default]
	 * @param string $created_at [optional - default current datetime]
	 * @return Log_queue_Model Created model
	 */
	public static function warn($description,
			$exception_backtrace = NULL, $created_at = NULL)
	{
		self::log(self::TYPE_WARNING, $description, $exception_backtrace, $created_at);
	}
	
	/**
	 * Creates new info log.
	 * 
	 * @param string $description Decription of error
	 * @param Exception $exception_backtrace [optional - empty by default]
	 * @param string $created_at [optional - default current datetime]
	 * @return Log_queue_Model Created model
	 */
	public static function info($description,
			$exception_backtrace = NULL, $created_at = NULL)
	{
		self::log(self::TYPE_INFO, $description, $exception_backtrace, $created_at);
	}
	
	/**
	 * Counts filtered logs
	 * 
	 * @param string $filter_sql SQL filter
	 * @return integer Count
	 */
	public function count_all_logs($filter_sql = '')
	{
		$where = '';
		
		if (!empty($filter_sql))
		{
			$where = 'WHERE ' . $filter_sql;
		}
		else
		{
			return $this->count_all();
		}
		
		return $this->db->query("
			SELECT COUNT(*) AS total FROM (
				SELECT l.*, u.id AS user_id,
					CONCAT(u.name, ' ', u.surname) AS user_name
				FROM log_queues l
				LEFT JOIN users u ON u.id = l.closed_by_user_id
			) c
			$where
		", Config::get('lang'))->current()->total;
	}
	
	/**
	 * Gets filtered connection requests
	 * 
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql SQL filter
	 * @return Mysql_Result
	 */
	public function get_all_logs(
			$limit_from = 0, $limit_results = 50, $order_by = 'id',
			$order_by_direction = 'asc', $filter_sql = '')
	{
		$where = '';
		
		if (!empty($filter_sql))
		{
			$where = 'WHERE ' . $filter_sql;
		}
		
		if (strtolower($order_by_direction) != 'asc')
		{
			$order_by_direction = 'desc';
		}
		
		return $this->db->query("
			SELECT * FROM (
				SELECT l.*, u.id AS user_id, CONCAT(u.name, ' ', u.surname) AS user_name,
					co.a_comment, l.comments_thread_id AS a_comments_thread_id,
					1 AS a_comment_add
				FROM log_queues l
				LEFT JOIN users u ON u.id = l.closed_by_user_id
				LEFT JOIN
				(
					SELECT comments_thread_id,
						GROUP_CONCAT(
							CONCAT(
								u.surname,' ',u.name,
								' (',SUBSTRING(c.datetime,1,10),'):\n',c.text)
							ORDER BY c.datetime DESC
							SEPARATOR '\n\n'
						) AS a_comment
					FROM comments c
					JOIN users u ON c.user_id = u.id
					GROUP BY comments_thread_id
				) co ON co.comments_thread_id = l.comments_thread_id
			) c
			$where
			ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
			LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", Config::get('lang'));
	}
	
}
