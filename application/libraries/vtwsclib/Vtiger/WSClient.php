<?php

require_once(APPPATH.'libraries/vtwsclib/Vtiger/Net/HTTP_Client.php');
require_once(APPPATH.'libraries/vtwsclib/Vtiger/WSVersion.php');

/**
 * Vtiger Webservice Client
 */
class Vtiger_WSClient {
	// Webserice file
	var $_servicebase = 'webservice.php';

	// HTTP Client instance
	var $_client = false;
	// Service URL to which client connects to
	var $_serviceurl = false;

	// Webservice user credentials
	var $_serviceuser= false;
	var $_servicekey = false;

	// Webservice login validity
	var $_servertime = false;
	var $_expiretime = false;
	var $_servicetoken=false;

	// Webservice login credentials
	var $_sessionid  = false;
	var $_userid     = false;

	// Last operation error information
	var $_lasterror  = false;

	/**
	 * Constructor.
	 */
	function __construct($url) { 
		$this->_serviceurl = $this->getWebServiceURL($url);
		$this->_client = new Vtiger_HTTP_Client($this->_serviceurl);
	}
	
	/**
	 * Destructor.
	 */
	function __destruct() {
		$this->__doLogout();
	}

	/**
	 * Return the client library version.
	 */
	function version() {
		global $wsclient_version;
		return $wsclient_version;
	}

	/**
	 * Reinitialize the client.
	 */
	function reinitalize() {
		$this->_client = new Vtiger_HTTP_Client($this->_serviceurl);
	}

	/**
	 * Get the URL for sending webservice request.
	 */
	function getWebServiceURL($url) {
		if(stripos($url, $this->_servicebase) === false) {
			if(strripos($url, '/') != (strlen($url)-1)) {
				$url .= '/';
			}
			$url .= $this->_servicebase;
		}
		return $url;
	}

	/**
	 * Get actual record id from the response id.
	 */
	function getRecordId($id) {
		$ex = explode('x', $id);
		return $ex[1];
	}

	/**
	 * Check if result has any error.
	 */
	function hasError($result) {
		if(isset($result['success']) && $result['success'] === true) {
			$this->_lasterror = false;
			return false;
		}
		$this->_lasterror = $result['error'];
		return true;
	}

	/**
	 * Get last operation error
	 */
	function lastError() {
		return $this->_lasterror;
	}

	/**
	 * Perform the challenge
	 * @access private
	 */
	function __doChallenge($username) {
		$getdata = Array(
			'operation' => 'getchallenge',
			'username'  => $username
		);
		$resultdata = $this->_client->doGet($getdata, true);

		if($this->hasError($resultdata)) {
			return false;
		}

		$this->_servertime   = $resultdata['result']['serverTime'];
		$this->_expiretime   = $resultdata['result']['expireTime'];
		$this->_servicetoken = $resultdata['result']['token'];
		return true;
	}

	/**
	 * Check and perform login if requried.
	 */
	function __checkLogin() {
		if(!$this->_expiretime || (time() > $this->_expiretime))
			$this->doLogin($this->_serviceuser, $this->_servicekey);
	}
	
	/**
	 * Do Logout Operation
	 * @access private
	 */
	function __doLogout() {		
		$getdata = Array(
			'operation' => 'logout',
			'sessionName'  => $this->_sessionid
		);
		
		$resultdata = $this->_client->doGet($getdata, true);

		if($this->hasError($resultdata)) {
			if (isset($resultdata['result']['message']) && $resultdata['result']['message'] == 'successfull')
				return false;
		}

		return true;
	}

	/**
	 * JSONify input data.
	 */
	function toJSON($input) {
		return $this->_client->__jsondecode($input);
	}

	/**
	 * Convert input data to JSON String.
	 */
	function toJSONString($input) {
		return $this->_client->__jsonencode($input);
	}

	/**
	 * Do Login Operation
	 */
	function doLogin($username, $vtigerUserAccesskey) {
		// Do the challenge before login
		if($this->__doChallenge($username) === false) return false;
		
		$postdata = Array(
			'operation' => 'login',
			'username'  => $username,
			'accessKey' => md5($this->_servicetoken.$vtigerUserAccesskey)
		);
		$resultdata = $this->_client->doPost($postdata, true);

		if($this->hasError($resultdata)) {
			return false;
		}
		$this->_serviceuser = $username;
		$this->_servicekey  = $vtigerUserAccesskey;

		$this->_sessionid = $resultdata['result']['sessionName'];
		$this->_userid    = $resultdata['result']['userId'];
		return true;
	}
	
	/**
	 * Do Query Operation.
	 */
	function doQuery($query) {
		// Perform re-login if required.
		$this->__checkLogin();

		// Make sure the query ends with ;
		$query = trim($query);		
		if(strripos($query, ';') != strlen($query)-1) $query .= ';';

		$getdata = Array(
			'operation' => 'query',
			'sessionName'  => $this->_sessionid,
			'query'  => $query
		);
		$resultdata = $this->_client->doGet($getdata, true);
		if($this->hasError($resultdata)) {
			return $resultdata;
		}
		return $resultdata['result'];
	}
	
	/**
	 * Do Query Operation - returns more than 100 results.
	 */
	function doQueryNotLimited($query) {
		// Perform re-login if required.
		$this->__checkLogin();
		
		$query = trim($query);
		
		$limit_pos = stripos($query, 'limit');
		
		$count = 0;
		$result_limit = 100;
		
		if ($limit_pos === false)
		{
			$select_pos = stripos($query, 'select ') + 7; //strlen('select ')
			$from_pos = stripos($query, ' from');
			
			$count_query = substr_replace($query, 'count(*)', $select_pos, $from_pos - $select_pos);
			
			$result = $this->doQuery($count_query);
			
			if ($result && array_key_exists(0, $result) && 
					array_key_exists('count', $result[0]))
				$count = $result[0]['count'];
		}
		
		if (($limit_pos !== false) || ($count <= $result_limit))
			return $this->doQuery($query);
		
		$reps = ceil($count / $result_limit);
		$results = array();
		
		//remove ; from the end of query
		if (substr($query, -1) == ';')
			$query = substr_replace ($query, '', strlen ($query) - 1, 1);
		
		for ($i = 0; $i < $reps; $i++)
		{
			//add limit
			$query_limited = $query . ' LIMIT ' . ($i * $result_limit) . ', ' . $result_limit;
			
			// Perform re-login if required.
			$this->__checkLogin();
			
			$result = $this->doQuery($query_limited);
			
			if (!$result)
				return false;
			
			$results = array_merge($results, $result);
		}
		
		return $results;
	}

	/**
	 * Get Result Column Names.
	 */
	function getResultColumns($result) {
		$columns = Array();
		if(!empty($result)) {
			$firstrow= $result[0];
			foreach($firstrow as $key=>$value) $columns[] = $key;
		}
		return $columns;
	}

	/**
	 * List types available Modules.
	 */
	function doListTypes() {
		// Perform re-login if required.
		$this->__checkLogin();

		$getdata = Array(
			'operation' => 'listtypes',
			'sessionName'  => $this->_sessionid
		);
		$resultdata = $this->_client->doGet($getdata, true);
		if($this->hasError($resultdata)) {
			return false;
		}		
		$modulenames = $resultdata['result']['types'];

		$returnvalue = Array();
		foreach($modulenames as $modulename) {
			$returnvalue[$modulename] = 
				Array ( 'name' => $modulename );
		}		
		return $returnvalue;
	}

	/**
	 * Describe Module Fields.
	 */
	function doDescribe($module) {
		// Perform re-login if required.
		$this->__checkLogin();

		$getdata = Array(
			'operation' => 'describe',
			'sessionName'  => $this->_sessionid,
			'elementType' => $module
		);
		$resultdata = $this->_client->doGet($getdata, true);
		if($this->hasError($resultdata)) {
			return false;
		}		
		return $resultdata['result'];
	}

	/**
	 * Retrieve details of record.
	 */
	function doRetrieve($record) {
		// Perform re-login if required.
		$this->__checkLogin();

		$getdata = Array(
			'operation' => 'retrieve',
			'sessionName'  => $this->_sessionid,
			'id' => $record
		);
		$resultdata = $this->_client->doGet($getdata, true);
		if($this->hasError($resultdata)) {
			return false;
		}		
		return $resultdata[result];
	}

	/**
	 * Do Create Operation
	 */
	function doCreate($module, $valuemap) {
		// Perform re-login if required.
		$this->__checkLogin();

		// Assign record to logged in user if not specified
		if(!isset($valuemap['assigned_user_id'])) {
			$valuemap['assigned_user_id'] = $this->_userid;
		}

		$postdata = Array(
			'operation'   => 'create',
			'sessionName' => $this->_sessionid,
			'elementType' => $module,
			'element'     => $this->toJSONString($valuemap)
		);
		$resultdata = $this->_client->doPost($postdata, true);
		if($this->hasError($resultdata)) {
			return false;
		}		
		return $resultdata['result'];
	}
	
	/**
	 * Do CreateBulk Operation
	 */
	function doCreateBulk($module, $valuemaps) {
		// Assign record to logged in user if not specified
		foreach ($valuemaps as &$valuemap)
			if(!isset($valuemap['assigned_user_id'])) {
				$valuemap['assigned_user_id'] = $this->_userid;
			}

		$postdata = Array(
			'operation'   => 'create_bulk',
			'sessionName' => $this->_sessionid,
			'elementType' => $module,
			'element'     => $this->toJSONString($valuemaps)
		);
		
		// Perform re-login if required.
		$this->__checkLogin();
		
		$resultdata = $this->_client->doPost($postdata, true);
		if($this->hasError($resultdata)) {
			return $resultdata;
		}		
		return $resultdata['result'];
	}

	/**
	 * Do Update Operation
	 */
	function doUpdate($valuemap) {
		// Perform re-login if required.
		$this->__checkLogin();

		// Assign record to logged in user if not specified
		if(!isset($valuemap['modifiedby'])) {
			$valuemap['modifiedby'] = $this->_userid;
		}

		$postdata = Array(
			'operation'   => 'update',
			'sessionName' => $this->_sessionid,
			'element'     => $this->toJSONString($valuemap)
		);
		
		$resultdata = $this->_client->doPost($postdata, true);
		
		if($this->hasError($resultdata)) {
			return false;
		}		
		return $resultdata['result'];
	}
	
	/**
	 * Do UpdateBulk Operation
	 */
	function doUpdateBulk($module,$valuemaps) {
		// Assign record to logged in user if not specified
		foreach ($valuemaps as &$valuemap)
			if(!isset($valuemap['modifiedby'])) {
				$valuemap['modifiedby'] = $this->_userid;
			}

		$postdata = Array(
			'operation'   => 'update_bulk',
			'sessionName' => $this->_sessionid,
			'elementType' => $module,
			'element'     => $this->toJSONString($valuemaps)
		);
		
		// Perform re-login if required.
		$this->__checkLogin();
		
		$resultdata = $this->_client->doPost($postdata, true);
		
		if($this->hasError($resultdata)) {
			return false;
		}		
		return $resultdata['result'];
	}
	
	/**
	 * Do Delete Operation
	 */
	function doDelete($id) {
		// Perform re-login if required.
		$this->__checkLogin();

		$postdata = Array(
			'operation'   => 'delete',
			'sessionName' => $this->_sessionid,
			'id'     => $id
		);
		
		$resultdata = $this->_client->doPost($postdata, true);
		
		if($this->hasError($resultdata)) {
			return false;
		}		
		return true;
	}
	
	/**
	 * Invoke custom operation
	 *
	 * @param String $method Name of the webservice to invoke
	 * @param Object $type null or parameter values to method
	 * @param String $params optional (POST/GET)
	 */
	function doInvoke($method, $params = null, $type = 'POST') {
		// Perform re-login if required
		$this->__checkLogin();
		
		$senddata = Array(
			'operation' => $method,
			'sessionName' => $this->_sessionid
		);
		if(!empty($params)) {
			foreach($params as $k=>$v) {
				if(!isset($senddata[$k])) {
					$senddata[$k] = $v;
				}
			}
		}

		$resultdata = false;
		if(strtoupper($type) == "POST") {
			$resultdata = $this->_client->doPost($senddata, true);
		} else {
			$resultdata = $this->_client->doGet($senddata, true);
		}

		if($this->hasError($resultdata)) {
			return false;
		}
		return $resultdata['result'];
	}	
}
?>
