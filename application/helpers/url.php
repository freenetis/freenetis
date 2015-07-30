<?php defined('SYSPATH') or die('No direct script access.');
/**
 * URL helper class.
 *
 * $Id: url.php 1970 2008-02-06 21:54:29Z Shadowhand $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class url {

	/**
	 * Returns base url
	 * @param <type> $index
	 * @param <type> $protocol
	 * @return <type>
	 */
	public static function base($index = FALSE, $protocol = FALSE)
	{
		$index_page = ($index && Settings::get('index_page')) ? 'index.php/' : '';

		$base_url = url::protocol().'://'.url::domain().url::suffix().$index_page;
               
		return $base_url;
	}
	

	/**
	 * @author Michal Kliment
	 * Returns domain
	 * @return string
	 */
	public static function domain()
	{
		// if exists settings key, return it
		if (Settings::get('domain')!='')
			return Settings::get('domain');
		else
			// else return it from url
			return server::http_host();
	}

	
	/**
	 * @author Michal Kliment
	 * Returns url suffix
	 * @return string
	 */
	public static function suffix()
	{
		// if exists settings key, return it
		if (Settings::get('suffix')!='')
			return Settings::get('suffix');
		else
			// else return it from url
			return substr(server::script_name(),0,-9);
	}


	/**
	 * Returns protocol
	 * 
	 * @author Michal Kliment, Ondřej Fibich
	 * @return string
	 */
	public static function protocol()
	{
		// if exists settings key, return it
		if (Settings::get('protocol') != '')
		{
			return Settings::get('protocol');
		}
		else
		{
			return (
					!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ||
					@$_SERVER['SERVER_PORT'] == 443
			) ? 'https' : 'http';
		}
	}

	/**
	 * Fetches a site URL based on a URI segment.
	 *
	 * @param   string  site URI to convert
	 * @param   string  non-default protocol
	 * @return  string
	 */
	public static function site($uri = '', $protocol = FALSE)
	{
		$uri = trim($uri, '/');

		$qs = ''; // anchor?query=string
		$id = ''; // anchor#id

		if (strpos($uri, '?') !== FALSE)
		{
			list ($uri, $qs) = explode('?', $uri, 2);
			$qs = '?'.$qs;
		}

		if (strpos($uri, '#') !== FALSE)
		{
			list ($uri, $id) = explode('#', $uri, 2);
			$id = '#'.$id;
		}

		$index_page = Config::get('index_page', TRUE);
		$url_suffix = ($uri != '') ? Config::get('url_suffix') : '';

		return url::base(FALSE, $protocol).$index_page.$uri.$url_suffix.$qs.$id;
	}

	/**
	 * Fetches the current URI.
	 *
	 * @param   boolean  include the query string
	 * @return  string
	 */
	public static function current($qs = FALSE)
	{
		return rtrim(Router::$current_uri.($qs === TRUE ? Router::$query_string : ''),'/');
	}

	/**
	 * @author Michal Kliment
	 * Returns previuos URI
	 * @return string
	 */
	public static function previous()
	{
		return url::uri(rtrim(server::http_referer()),'/');
	}

	/**
	 * @author Michal Kliment
	 * Returns URI
	 * @return string
	 */
	public static function uri($url)
	{
		if (substr($url, 0, strlen(url::base())) != url::base())
			return "";

		return rtrim(substr($url, strlen(url::base())),'/');
	}

	/**
	 * Convert a phrase to a URL-safe title.
	 *
	 * @param   string  phrase to convert
	 * @param   string  word separator (- or _)
	 * @return  string
	 */
	public static function title($title, $separator = '-')
	{
		//$separator = ($separator == '-') ? '-' : '_';

		// Replace all dashes, underscores and whitespace by the separator
		$title = preg_replace('/[-_\s]+/', $separator, $title);

		// Replace accented characters by their unaccented equivalents
		$title = utf8::transliterate_to_ascii($title);

		// Remove all characters that are not a-z, 0-9, or the separator
		$title = preg_replace('/[^a-z0-9'.$separator.']+/', '', strtolower($title));

		// Trim separators from the beginning and end
		$title = trim($title, $separator);

		return $title;
	}

	/**
	 * Sends a page redirect header.
	 *
	 * @param  string  site URI or URL to redirect to
	 * @param  string  HTTP method of redirect
	 * @return A HTML anchor, but sends HTTP headers. The anchor should never be seen
	 *         by the user, unless their browser does not understand the headers sent.
	 */
	public static function redirect($uri = '', $method = '302')
	{
		if (Event::has_run('system.send_headers'))
			return;

		if (strpos($uri, '://') === FALSE)
		{
			$uri = url_lang::site($uri);
		}
			
		if ($method == 'refresh')
		{
			header('Refresh: 0; url='.$uri);
		}
		else
		{
			$codes = array
			(
				'300' => 'Multiple Choices',
				'301' => 'Moved Permanently',
				'302' => 'Found',
				'303' => 'See Other',
				'304' => 'Not Modified',
				'305' => 'Use Proxy',
				'307' => 'Temporary Redirect'
			);

			$method = isset($codes[$method]) ? $method : '302';

			header('HTTP/1.1 '.$method.' '.$codes[$method]);
			header('Location: '.$uri);
		}

		// Last resort, exit and display the URL
		exit('<a href="'.$uri.'">'.$uri.'</a>');
	}

	/**
	 * Creates URL from URI
	 *
	 * @author Michal Kliment
	 * @param string $uri
	 * @return string
	 */
	public static function create ($uri)
	{
		return (
				substr(strtolower($uri),0,7) != 'http://' &&
				substr(strtolower($uri),0,8) != 'https://' &&
				substr(strtolower($uri),0,6) != 'ftp://'
		) ? 'http://'.$uri : $uri;
	}

	/**
	 * Returns slice from url
	 *
	 * @author Michal Kliment
	 * @param string $url
	 * @param integer $offset
	 * @param integer $length
	 * @return string
	 */
	public static function slice ($url, $offset = 0, $length = NULL)
	{
		$segments = explode ('/', $url);

		return implode ('/', array_slice ($segments, $offset, $length));
	}
	
	/**
	 * Returns query string from url in given format
	 * 
	 * @author Michal Kliment
	 * @param string $url
	 * @param string $format
	 * @return mixed 
	 */
	public static function query_string ($url, $format = 'string')
	{
		// split url to url and query string
		$segments = explode('?', $url);
		
		$query_string = isset($segments[1]) ? $segments[1] : '';
		
		// format is string, can return result
		if ($format == 'string')
			return $query_string;
		
		// split query string to pieces
		$pieces = explode('&', $query_string);
		
		// format is pieces, can return result
		if ($format == 'pieces')
			return $pieces;
		
		// create associative array
		$array = array();
		foreach ($pieces as $key => $val)
		{
			if (strpos($val,'=') !== FALSE)
				list ($key, $val) = explode("=", $val);
			
			$array[$key] = $val;
		}
		
		// format is array, can return result
		if ($format = 'array')
			return $array;
		
		// format is unknown, return empty string
		return '';
	}
	
	/**
	 * Builds query string from array
	 * 
	 * @author Michal Kliment
	 * @param type $array
	 * @return string 
	 */
	public static function build_query($array = array())
	{		
		if (!count($array))
			return '';
		
		$pieces = array();
		foreach ($array as $key => $val)
		{
			if (!is_numeric($key))
				$pieces[] = "$key=$val";
			else
				$pieces[] = $val;
		}
		
		return '?'.implode('&', $pieces);
	}

} // End url