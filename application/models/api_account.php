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
 * The "Api_account_Model" model represents an account using which remote
 * requests via API may be made. In order to provide secure connection
 * remote user must send in a form defined by API its username and token.
 * If sended credentials are valid and account is enabled than user is allowed 
 * to access parts of API that are defined using allowed_paths and readonly
 * properties.
 * 
 * Allowed paths are a list of API URLs separated via comma that must match
 * following regex: (/[a-zA-Z0-9_-*])+
 * Character * has special meaning and it is transformed to ([^/]*) regex.
 * 
 * If property readonly is set than this account cannot perform API request
 * that changes some field.
 * 
 * Each request made via this account and also any account modification 
 * is logged into Api_account_logs_Model entry.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @package Model
 * @since 1.2
 * 
 * @property int $id
 * @property string $username Auth username
 * @property string $token Auth token
 * @property bool $enabled Is account enabled?
 * @property bool $readonly Account is allowed to make only readonly requests?
 * @property string $allowed_paths list of allowed paths that are delimited by
 *      comma and may contain * as generic character with reprezentation as 
 *      regex [^/]*
 * @property ORM_Iterator $api_account_logs Logs
 */
class Api_account_Model extends ORM
{
	protected $has_many = array('api_account_logs');
	protected $belongs_to = array('creator' => 'user');
	
	/**
	 * Default value for allowed path property that allows access to all parts
	 * of API.
	 */
	const ALLOWED_PATHS_ENABLED_ALL = '/**';
	
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
	 * Creates filled but not persisted API log model with given user action
	 * type.
	 * 
	 * @param integer $type
	 * @param integer $user_id ID of user who made action
	 * @param string $description log meaning
	 * @param integer $api_account_id [optional: default from $this]
	 * @return Api_account_log_Model created log model
	 * @throws InvalidArgumentException on invalid type
	 */
	public function create_user_log($type, $user_id, $description = NULL,
			$api_account_id = NULL)
	{
		if (!Api_account_log_Model::type_is_user($type))
		{
			throw new InvalidArgumentException('invalid user API log type');
		}
		$log = new Api_account_log_Model();
		$log->type = $type;
		$log->api_account_id = ($api_account_id === NULL)
				? $this->id : $api_account_id;
		$log->responsible_user_id = $user_id;
		$log->description = $description;
        $log->date = date(DATE_W3C);
		return $log;
	}
	
	/**
	 * Creates filled but not persisted API log model with given request type.
	 * 
	 * @param integer $type
	 * @param string $description log meaning [optional]
	 * @param integer $api_account_id [optional: default from $this]
	 * @return Api_account_log_Model created log model
	 * @throws InvalidArgumentException on invalid type
	 */
	public function create_request_log($type, $description,
			$api_account_id = NULL)
	{
		if (!Api_account_log_Model::type_is_request($type))
		{
			throw new InvalidArgumentException('invalid request API log type');
		}
		$log = new Api_account_log_Model();
		$log->type = $type;
		$log->api_account_id = ($api_account_id === NULL)
				? $this->id : $api_account_id;
		$log->description = $description;
        $log->date = date(DATE_W3C);
		return $log;
	}

	/**
	 * Checks whether the account with given username is not already in DB.
	 * 
	 * @param string $username
	 * @param integer $ignore_id ignore account with given ID [optional]
	 * @return boolean
	 */
	public function is_account_unique($username, $ignore_id = NULL)
	{
		return $this->db->query("
				SELECT COUNT(*) AS count FROM api_accounts
				WHERE username = ? AND id <> ?
		", $username, intval($ignore_id))->current()->count == 0;
	}
	
	/**
	 * Finds and returns API account with given username or returns NULL if
	 * no such account exists.
	 * 
	 * @param string $username
	 * @return Api_account_Model|NULL
	 */
	public function find_by_username($username)
	{
		$api_account = ORM::factory('api_account')
				->where('username', $username)
				->find();
		// found?
		return ($api_account->id) ? $api_account : NULL;
	}
	
}
