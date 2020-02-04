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

require_once APPPATH . '/vendors/phpax-rs/loader.php';
require_once APPPATH . '/vendors/php-http-auth-server/loader.php';

/**
 * FreenetIS REST API builded using PHPAX-RS framework.
 * All requested passed to this controller are handled by PHPAX-RS REST 
 * end points classes that are stored in ./api_endpoints folder and their
 * names end with "_Api".
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @package Controller
 * @since 1.2
 */
class Api_Controller extends Controller
{
    /**
     * Base API path (relative path to this constroller).
     */
    const API_BASE_PATH = '/api';
    
    /**
     * API end point class name suffix.
     */
    const API_ENDPOINT_SUFFIX = '_Api';
    
    /**
     * API directory relative to controllers dir that contains all endpoints.
     */
    const API_ENPOIND_DIR = 'api_endpoints';

    /**
     * @var \phpaxrs\PhpaxRs
     */
    private static $phpaxRs = NULL;
    
    /**
     * Initilize singleton instance of PHPAX-RS framework runtime and adds
     * available serializators and end points.
     */
    public function __construct()
    {
        parent::__construct();
		// access control
		if (!module::e('api'))
		{
			self::error(PAGE);
		}
        // init PHPAX-RS
        if (self::$phpaxRs === NULL)
        {
            $base_path = rtrim(Settings::get('suffix'), '/') . '/' 
                    . Config::get('lang') . self::API_BASE_PATH;
            self::$phpaxRs = new \phpaxrs\PhpaxRs($base_path);
            // add serializators
            self::$phpaxRs->add_serializator('application/json',
                    '\phpaxrs\serializator\JsonSerializator');
            // add end points
            $ds = DIRECTORY_SEPARATOR;
            self::add_end_points(__DIR__ . $ds . self::API_ENPOIND_DIR);
        }
    }
    
    /**
     * We do not need preprocessor.
     * 
     * @return boolean
     */
    protected function is_preprocesor_enabled()
    {
        return FALSE;
    }
	
    /**
     * Maps every request to PHPAX-RS.
     * 
     * @param string $method
     * @param array $args
     */
    public function _remap($method, $args)
    {
		$base_url = url::base() . url::current();
		$part_request_url = '/';
		if ($method === 'index') { // index method should be ignored in path
			$method = NULL;
		}
		if (!empty($method))
		{
			$part_request_url .= $method . '/';
			if (!empty($args))
			{
				$part_request_url .= implode('/', $args);
			}
			$part_request_url = '/' . ltrim($part_request_url, '/');
		}
		// authorization and authentification
		if ($this->check_http_auth(request::method(), $part_request_url))
		{
			// serve request using PHPAX-RS
			$response = self::$phpaxRs->serve($base_url);
			// render response
			self::$phpaxRs->render($response);
		}
    }

    /**
     * Register all end point classes from the given directory.
     * 
     * @param string $dir
     */
    private static function add_end_points($dir)
    {
        if (!is_dir($dir))
        {
            self::warning(PARAMETER, 'Invalid API end points directory');
        }
        $files = scandir($dir);
        if (is_array($files))
        {
            foreach ($files as $file)
            {
                if (is_file($dir . DIRECTORY_SEPARATOR . $file) &&
                    text::ends_with($file, EXT))
                {
                    $class = substr($file, 0, strlen($file) - strlen(EXT));
                    $path = trim(strtolower(str_replace('_', '/', $class)), '/');
                    $class_full = $class . self::API_ENDPOINT_SUFFIX;
						self::$phpaxRs->add_endpoint('/' . $path, $class_full);
                }
            }
        }
    }
	
	/**
	 * Check authorization and authentification using HTTP Digest Authentication
	 * and API account. If auth failed than the whole script is terminated with
	 * error response with proper HTTP response status code.
	 * 
	 * @param string $http_method request HTTP method name
	 * @param string $req_url_part request URL without API base URL
	 * @return boolean auth success?
	 */
	private function check_http_auth($http_method, $req_url_part)
	{
		$sn = Settings::get('domain') 
				. str_replace('/', ' ', Settings::get('suffix')) . ' API';
        $aam = new ApiAccountManager();
        $auth_type = Settings::get('api_auth_type');
		$handler = \phphttpauthserver\HttpAuth::factory($auth_type, $aam, $sn);
		$auth_rsp = $handler->auth();
		// authentification failed?
		if (!$auth_rsp->isPassed())
		{
			self::trigger_auth_error(401, $auth_rsp->getErrors(),
					$auth_rsp->getHeaders());
		}
        // proceed with authorization
        $api_account = $aam->get_user_account($auth_rsp->getUsername());
        // API account not found
        if (!$api_account->id)
        {
            self::trigger_auth_error(500, 'API account not found');
        }
        // log access
        try
        {
            $log_type = Api_account_log_Model::get_rq_type_of($http_method);
            $description = mb_strtoupper($http_method) . ' ' . $req_url_part;
            $api_account->create_request_log($log_type, $description)
                ->save_throwable();
        }
        catch (InvalidArgumentException $ex)
        {
            self::trigger_auth_error(405); // HTTP method not accepted
        }
        catch (Exception $ex)
        {
            self::trigger_auth_error(500, 'API log failed, cause: ' . $ex);
        }
        // API account not allowed?
        if (!$api_account->enabled)
        {
            self::trigger_auth_error(403);
        }
        // Reaonly access -> only GET HTTP method allowed for request
        if ($api_account->readonly && mb_strtoupper($http_method) !== 'GET')
        {
            self::trigger_auth_error(403);
        }
        // API account access restricted to allowed template paths?
        if (!empty($api_account->allowed_paths))
        {
            $allowed_tpaths = explode(',', $api_account->allowed_paths);
            if (!empty($allowed_tpaths) &&
                !url_tpath::match_one_of($allowed_tpaths, $req_url_part))
            {
                self::trigger_auth_error(403);
            }
        }
        // OK allowed
        return TRUE;
	}
	
	/**
	 * Renders authorization or authentification error and exists.
	 * 
	 * @staticvar array $status_codes allowed status codes
	 * @param integer $status_code HTTP status code (one of allowed)
	 * @param array|string $errors array of errors
	 * @param array $headers HTTP headers as associative array
	 * @throws ErrorException on invalid state or arguments
	 */
	private static function trigger_auth_error($status_code, $errors = array(),
			$headers = array())
	{
		// status codes names
		static $status_codes = array
		(
			401 => 'Unauthorized',
			403 => 'Forbidden',
			405 => 'Method Not Allowed',
			500 => 'Internal Server Error'
		);
		// render status code
		if (headers_sent())
		{
			throw ErrorException('E' . $status_code . ': headers already send');
		}
		if (!array_key_exists($status_code, $status_codes))
		{
			throw ErrorException('Invalid status code: ' . $status_code);
		}
		header('HTTP/1.1 ' . $status_code . ' ' . $status_codes[$status_code]);
		// add headers
		if (!empty($headers))
		{
			foreach ($headers as $key => $value)
			{
				header($key . ': ' . $value); 
			}
		}
		// output errors
		if (!empty($errors))
		{
			if (is_string($errors))
			{
				$errors = array($errors);
			}
			echo implode("\n", $errors) . "\n";
		}
		exit();
	}

}

/**
 * Account manager implementation using API account model.
 * 
 * @link Api_account_Model
 */
class ApiAccountManager implements \phphttpauthserver\IAccountManager
{
	/**
	 * API account model used for obtaining account informations.
	 *
	 * @var Api_account_Model
	 */
	private $api_account_model;
	
	public function __construct()
	{
		$this->api_account_model = new Api_account_Model();
	}
	
	/*
	 * @override
	 */
	public function getUserPassword($username)
	{
        try
		{
			$ua = $this->api_account_model->find_by_username($username);

			if ($ua === NULL)
			{
				return FALSE;
			}

			return $ua->token;
		}
		catch (Exception $ex)
		{
			Log::add_exception($ex);
			return FALSE;
		}
	}
	
	/**
	 * Gets API account that has given username or returns NULL if not found
	 * or some error occures.
	 * 
	 * @param string $username
	 * @return Api_account_Model|null
	 */
	public function get_user_account($username)
	{
		try
		{
			return $this->api_account_model->find_by_username($username);
		}
		catch (Exception $ex)
		{
			Log::add_exception($ex);
			return NULL;
		}
	}
	
}
