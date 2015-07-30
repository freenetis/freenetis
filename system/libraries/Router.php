<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Router
 *
 * $Id: Router.php 1911 2008-02-04 16:13:16Z PugFish $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Router {

        public static $default_route = 'login';
	protected static $routes = array();

	public static $current_uri = '';
	public static $segments    = array();
	public static $rsegments   = array();

	public static $query_string = '';
	public static $url_suffix   = '';

	public static $directory  = FALSE;
	public static $controller = FALSE;
	public static $method     = FALSE;
	public static $arguments  = FALSE;

	/**
	 * Router setup routine. Automatically called during Kohana setup process.
	 */
	public static function setup()
	{
		// Make sure the default route is set
		if ( ! isset(self::$default_route))
			throw new Kohana_Exception('core.no_default_route');

                $lang = (string) cookie::get('lang');

                if (!$lang)
                    $lang = Config::get('lang');

                self::$default_route = $lang.'/'.self::$default_route;

                $lang = '[a-zA-Z]{2}';
                self::$routes = array
                (
                    // '_default' => 'home',
                    $lang => 'home',

                    // Catch-all language route
                    $lang.'/(.*)' => '$1',
                );

                // Clean up
                unset($lang);

		// Use the default route when no segments exist
		if (self::$current_uri == '' OR self::$current_uri == '/')
		{
			self::$current_uri = self::$default_route;
			$use_default_route = TRUE;
		}
		else
		{
			$use_default_route = FALSE;
		}

		if ( ! empty($_SERVER['QUERY_STRING']))
		{
			// Set the query string to the current query string
			self::$query_string = '?'.trim($_SERVER['QUERY_STRING'], '&');
		}

                self::$rsegments = explode('/', self::$current_uri);
                array_shift(self::$rsegments);

		// Prepare for Controller search
		self::$directory  = '';
		self::$controller = '';

		// We check this path statically, because it's overwhelmingly the most
		// common path for controllers to be located at
		if (count(self::$rsegments) && is_file(APPPATH.'controllers/'.self::$rsegments[0].EXT))
		{
			self::$directory  = APPPATH.'controllers/';
			self::$controller = self::$rsegments[0];
			self::$method     = (isset(self::$rsegments[1])) ? self::$rsegments[1] : 'index';
			self::$arguments  = (isset(self::$rsegments[2])) ? array_slice(self::$rsegments, 2) : array();
		}
		else
		{
			// Fetch the include paths
			$include_paths = Config::include_paths();

			// Path to be added to as we search deeper
			$search = 'controllers';

			// Use the rsegments to find the controller
			foreach(self::$rsegments as $key => $segment)
			{
				foreach($include_paths as $path)
				{
					// The controller has been found, all arguments can be set
					if (is_file($path.$search.'/'.$segment.EXT))
					{
						self::$directory  = $path.$search.'/';
						self::$controller = $segment;
						self::$method     = isset(self::$rsegments[$key + 1]) ? self::$rsegments[$key + 1] : 'index';
						self::$arguments  = isset(self::$rsegments[$key + 2]) ? array_slice(self::$rsegments, $key + 2) : array();

						// Stop searching, two levels for foreach
						break 2;
					}
				}

				// Add the segment to the search
				$search .= '/'.$segment;
			}
		}

		// If the controller is empty, run the system.404 event
		empty(self::$controller) and Event::run('system.404');
	}

	/**
	 * Attempts to determine the current URI using CLI, GET, PATH_INFO, ORIG_PATH_INFO, or PHP_SELF.
	 */
	public static function find_uri()
	{
		if (PHP_SAPI === 'cli')
		{
			// Command line requires a bit of hacking
			if (isset($_SERVER['argv'][1]))
			{
				self::$current_uri = $_SERVER['argv'][1];

				// Remove GET string from segments
				if (($query = strpos(self::$current_uri, '?')) !== FALSE)
				{
					list (self::$current_uri, $query) = explode('?', self::$segments, 2);

					// Insert query into GET array
					foreach(explode('&', $query) as $pair)
					{
						list ($key, $val) = array_pad(explode('=', $pair), 1, '');

						$_GET[utf8::clean($key)] = utf8::clean($val);
					}
				}
			}
		}
		elseif (isset($_SERVER['PATH_INFO']) AND $_SERVER['PATH_INFO'])
		{
			self::$current_uri = $_SERVER['PATH_INFO'];
		}
		elseif (isset($_SERVER['ORIG_PATH_INFO']) AND $_SERVER['ORIG_PATH_INFO'])
		{
			self::$current_uri = $_SERVER['ORIG_PATH_INFO'];
		}
		elseif (isset($_SERVER['PHP_SELF']) AND $_SERVER['PHP_SELF'])
		{
			self::$current_uri = $_SERVER['PHP_SELF'];
		}

		// The front controller directory and filename
		$fc = substr(realpath($_SERVER['SCRIPT_FILENAME']), strlen(DOCROOT));

		if (($strpos_fc = strpos(self::$current_uri, $fc)) !== FALSE)
		{
			// Remove the front controller from the current uri
			self::$current_uri = (string) substr(self::$current_uri, $strpos_fc + strlen($fc));
		}

		if ($suffix = Config::get('core.url_suffix') AND strpos(self::$current_uri, $suffix) !== FALSE)
		{
			// Remove the URL suffix
			self::$current_uri = preg_replace('!'.preg_quote($suffix).'$!u', '', self::$current_uri);

			// Set the URL suffix
			self::$url_suffix = $suffix;
		}

		// Remove extra slashes from the segments that could cause fucked up routing
		self::$current_uri = preg_replace('!//+!', '/', trim(self::$current_uri, '/'));
	}

	/**
	 * Filter a string for allowed URI characters.
	 *
	 * @param   string  string to filter
	 * @return  string
	 */
	public static function filter_uri($str)
	{
		$str = trim($str);

		if ($str != '' AND ($allowed = Config::get('routes._allowed')) != '')
		{
			if ( ! preg_match('|^['.preg_quote($allowed).']++$|iuD', $str))
			{
				header('HTTP/1.1 400 Bad Request');
				exit('The URI you submitted has disallowed characters.');
			}
		}

		return $str;
	}

} // End Router class
