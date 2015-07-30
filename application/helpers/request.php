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
 * HTTP Request helper
 * 
 * @package Helper
 */
class request
{

	//Possible http methods
	protected static $http_methods = array('get', 'post', 'put', 'delete');
	//Types client accepts
	protected static $accept_types = array();

	/**
	 * Returns true if request is an ajax request
	 * This works for mose js-libraries like jQuery, prototype
	 *
	 * @return bool
	 */
	public static function is_ajax()
	{
		return (
				isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
				$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
		);
	}

	/**
	 * Returns true if call accepts xhtml
	 *
	 * @return bool
	 */
	public static function accepts_xhtml()
	{
		return self::accepts('xhtml');
	}

	/**
	 * Returns true if call accepts xml
	 *
	 * @return bool  
	 */
	public static function accepts_xml()
	{
		return self::accepts('xml');
	}

	/**
	 * Returns true if call accepts rss
	 *
	 * @return bool 
	 */
	public static function accepts_rss()
	{
		return self::accepts('rss');
	}

	/**
	 * Returns true if call accepts atom
	 *
	 * @return bool 
	 */
	public static function accepts_atom()
	{
		return self::accepts('atom');
	}

	/**
	 * Returns true if the request is POST
	 *
	 * @return bool 
	 */
	public static function is_post()
	{
		return self::method() == 'post';
	}

	/**
	 * Returns true if the request is PUT
	 *
	 * @return bool
	 */
	public static function is_put()
	{
		return self::method() == 'put';
	}

	/**
	 * Returns true if the request is GET
	 *
	 * @return bool 
	 */
	public static function is_get()
	{
		return self::method() == 'get';
	}

	/**
	 * Returns true if the request is DELETE
	 *
	 * @return bool
	 */
	public static function is_delete()
	{
		return self::method() == 'delete';
	}

	/**
	 * Returns current request method
	 * 
	 * @return string
	 */
	public static function method()
	{
		$method = strtolower($_SERVER['REQUEST_METHOD']);

		if (!in_array($method, self::$http_methods))
			throw new Kohana_Exception('request.unknown_method', $method);

		return $method;
	}

	/**
	 * Returns boolean of whether client accepts content type
	 * 
	 * @return boolean
	 */
	public static function accepts($type = null)
	{
		if (empty(self::$accept_types))
		{
			self::$accept_types = explode(',', $_SERVER['HTTP_ACCEPT']);

			foreach (self::$accept_types as $key => $accept_type)
			{
				if (strpos($accept_type, ';'))
				{
					$accept_type = explode(';', $accept_type);
					self::$accept_types[$key] = $accept_type[0];
				}
			}
		}

		if ($type == null)
		{
			return self::$accept_types;
		}
		elseif (is_string($type))
		{
			$type = strtolower($type);

			// If client only accepts */*, then assume default HTML browser
			if ($type == 'html' && self::$accept_types === array('*/*'))
				return true;

			if (!in_array($type, array_keys(self::$accept_types)))
				return false;

			$accept_types = Config::get('mimes.' . $type);

			if (is_array($accept_types))
			{
				foreach ($accept_types as $type)
				{
					if (in_array($type, self::$accept_types))
						return true;
				}
			}
			else
			{
				if (in_array($accept_types, self::$accept_types))
					return true;
			}
			return false;
		}
	}

}