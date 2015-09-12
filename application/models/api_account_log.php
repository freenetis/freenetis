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
 * The "Api_account_log_Model" model represents an audit log for API accounts
 * that is capable to log incoming API requests and FreenetIS user account 
 * creating/modification.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @package Model
 * @since 1.2
 * 
 * @property int $id
 * @property int $api_account_id ID of account that this log belongs to
 * @property Api_account_Model $api_account Account that this log belongs to
 * @property int $type Request type or non request action type
 * @property datetime $date
 * @property string $description Request path or other informations on non 
 *      request actions
 * @property int $responsible_user_id User who is responsible for non request
 *		actions logged by this model.
 * @property User_Model $responsible_user
 */
class Api_account_log_Model extends ORM
{
	protected $belongs_to = array('api_account', 'responsible_user' => 'user');
	
	/** Request API type related to reading. */
	const TYPE_RQ_READ              = 1;
	/** Request API type related to creating of a new object. */
	const TYPE_RQ_CREATE            = 2;
	/** Request API type related to modification of an existion object. */
	const TYPE_RQ_EDIT              = 3;
	/** Request API type related to deletion of an existing object. */
	const TYPE_RQ_DELETE            = 4;
	/** Action type that represents account creation. */
	const TYPE_CREATION             = 5;
	/** Action type that represents account details modification. */
	const TYPE_DETAILS_CHANGE       = 6;
	/** Action type that represents account token modification. */
	const TYPE_TOKEN_CHANGE         = 7;
    
	/**
     * Name of ACL account log types.
     *
     * @var array
     */
    private static $types = array
    (
        self::TYPE_RQ_READ          => 'Read request',
        self::TYPE_RQ_CREATE        => 'Create request',
        self::TYPE_RQ_EDIT          => 'Modification request',
        self::TYPE_RQ_DELETE        => 'Delete request',
        self::TYPE_CREATION         => 'Account creation',
        self::TYPE_DETAILS_CHANGE   => 'Account details changed',
        self::TYPE_TOKEN_CHANGE     => 'Account token changed'
    );
	
	/**
	 * Get request type constant of HTTP method (GET, DELETE, POST and PUT).
	 * 
	 * @param string $http_method HTTP method name
	 * @return integer|null request API account log constant
	 */
	public static function get_rq_type_of($http_method) {
		switch (strtolower($http_method))
		{
			case 'get':
			case 'head':
				return self::TYPE_RQ_READ;
			case 'post':
				return self::TYPE_RQ_CREATE;
			case 'put':
				return self::TYPE_RQ_EDIT;
			case 'delete':
				return self::TYPE_RQ_DELETE;
			default:
				return NULL;
		}
	}

		/**
	 * Returns all types of API account logs.
	 * 
	 * @return array
	 */
	public static function get_types()
	{
		return array_map('__', self::$types);
	}
    
    /**
     * Gets type name.
     * 
     * @param int $type constant
     * @param boolean $translate should be translated? [optional: true]
     * @return string type name that may be translated
     */
    public static function get_type_name($type, $translate = TRUE)
    {
        if (array_key_exists($type, self::$types))
        {
            if ($translate)
            {
                return __(self::$types[$type]);
            }
            return self::$types[$type];
        }
        return NULL;
    }
	
	/**
	 * Is given type a user action log?
	 * 
	 * @param integer $type type constant
	 * @return boolean
	 */
	public static function type_is_user($type)
	{
		return ($type === self::TYPE_CREATION) ||
			($type === self::TYPE_DETAILS_CHANGE) ||
			($type === self::TYPE_TOKEN_CHANGE);
	}
	
	/**
	 * Is given type a request log?
	 * 
	 * @param integer $type type constant
	 * @return boolean
	 */
	public static function type_is_request($type)
	{
		return array_key_exists($type, self::$types) &&
				!self::type_is_user($type);
	}

	/**
	 * Creates model without logging.
	 * 
	 * @param integer $id 
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		// disable action log
		$this->set_logger(FALSE);
	}

	/**
	 * Gets logs of API account with given ID for grid.
	 * 
	 * @param integer $api_account_id
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param string $filter_sql
	 * @return Mysql_Result
	 */
	public function get_account_logs($api_account_id, $limit_from,
			$limit_results, $order_by, $order_by_direction, $filter_sql)
	{
		$where = '';
		if (!empty($filter_sql))
		{
			$where = ' AND ' . $filter_sql;
		}
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
			SELECT l.id, l.type, l.description, l.date, l.responsible_user_id,
				CONCAT(u.name, ' ', u.surname, ' - ', u.login) AS 
				responsible_user_name
			FROM api_account_logs l
			LEFT JOIN users u ON u.id = l.responsible_user_id
			WHERE l.api_account_id = ? $where
			ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction
			LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		", $api_account_id);
	}

	/**
	 * Counts logs of API account with given ID.
	 * 
	 * @param integer $api_account_id
	 * @param string $filter_sql
	 * @return integer
	 */
	public function count_account_logs($api_account_id, $filter_sql)
	{
		$where = '';
		if (!empty($filter_sql))
		{
			$where = ' AND ' . $filter_sql;
		}
		// query
		return $this->db->query("
			SELECT COUNT(*) AS count FROM (
				SELECT l.id, l.type, l.description, l.date, l.responsible_user_id,
					CONCAT(u.name, ' ', u.surname, ' - ', u.login) AS 
					responsible_user_name
				FROM api_account_logs l
				LEFT JOIN users u ON u.id = l.responsible_user_id
				WHERE l.api_account_id = ? $where
			) c
		", $api_account_id)->current()->count;
	}

}
