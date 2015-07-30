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
 * Class: url_lang
 *  URL language helper class.
 * 
 * @package Helper
 */
class url_lang
{

	/**
	 * Prints translated string in given format
	 *
	 * @param string $value
	 * @param array $args
	 * @param integer $format
	 * @return string
	 */
	public static function lang($value = '', $args = '', $format = 0)
	{
		switch ($format)
		{
			// default behaviour, prints without changes
			case 0:
				return Kohana::lang($value, $args);
				break;

			// prints string with lower letters
			case 1:
				return strtolower(Kohana::lang($value, $args));
				break;

			// prints string with upper first letter and others lower
			case 2:
				return ucfirst(strtolower(Kohana::lang($value, $args)));
				break;

			// prints string with upper letters
			case 3:
				return strtoupper(Kohana::lang($value, $args));
				break;

			// default behaviour, prints without changes
			default:
				return Kohana::lang($value, $args);
				break;
		}
	}

	/**
	 * Return base url joined with 'index.php' string (if clean urls are off) 
	 * and lang string
	 * 
	 * @param mixed $index
	 * @param mixed $protocol
	 * @param mixed $lang
	 * @return string
	 */
	public static function base($index = FALSE, $protocol = FALSE, $lang = FALSE)
	{
		$index_page = (Settings::get('index_page')) ? 'index.php/' : '';

		$lang = Config::get('lang');

		return url::base() . $index_page . $lang . '/';
	}

	/*
	 * Method: site
	 *  Creates a site URL based on the given URI string and
	 *  automatically prepends the language segment.
	 *
	 * Parameters:
	 *  uri      - URI string
	 *  lang     - non-default language
	 *  protocol - non-default protocol
	 *
	 * Returns:
	 *  A URL string.
	 */

	public static function site($uri = '', $lang = FALSE, $protocol = FALSE)
	{
		if ($lang === FALSE)
		{
			$lang = Config::get('lang');
		}
		
		$index_page = '';
		
		if (Settings::get('index_page'))
		{
			$index_page = 'index.php/';
		}

		return url::site($index_page . $lang . '/' . trim($uri, '/'), $protocol);
	}

	/*
	 * Method: current
	 *
	 * Returns:
	 *  The current URI string without the lang part
	 */

	public static function current($length = 0, $offset = 0)
	{
		$str = substr(url::current(), 3);
		
		$segments = explode('/', rtrim($str, '/'));
		
		if (!$length)
			$length = count($segments) - $offset;
		
		$segments = array_slice($segments, $offset, $length);
		return implode('/', $segments);
	}

	/**
	 * Returns previous URI without the lang part
	 * 
	 * @author Michal Kliment
	 * @param number $length
	 * @param number $offset
	 * @return string
	 */
	public static function previous($length = 0, $offset = 0)
	{
		$str = substr(url::previous(), 3);
		
		if (!$length)
			return $str;
		else
		{
			$segments = explode('/', rtrim($str, '/'));
			$segments = array_slice($segments, $offset, $length);
			return implode('/', $segments);
		}
	}

	/*
	 * Method: redirect
	 *  Sends a page redirect header and
	 *  automatically prepends the language segment.
	 *
	 * Parameters:
	 *  uri    - site URI or URL to redirect to
	 *  lang   - non-default language
	 *  method - HTTP method of redirect
	 *
	 * Returns:
	 *  A HTML anchor, but sends HTTP headers. The anchor should never be seen
	 *  by the user, unless their browser does not understand the headers sent.
	 */
	public static function redirect($uri = '', $lang = FALSE, $method = '302')
	{
		if ($lang === FALSE)
		{
			$lang = Config::get('lang');
		}
		
		$index_page = '';
		
		if (Settings::get('index_page'))
		{
			$index_page = 'index.php/';
		}

		return url::redirect($index_page . $lang . '/' . trim($uri, '/'), $method);
	}

	/**
	 * Returns uri from url, without lang segment
	 * 
	 * @param string $url
	 * @return string
	 */
	public static function uri($url)
	{
		$segments = explode('/', url::uri($url));
		array_shift($segments);

		return implode('/', $segments);
	}

}