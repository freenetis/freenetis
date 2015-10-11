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
 * Server helper class.
 *
 * $Id: server.php
 *
 * @package Helper
 * @author Sevcik Roman
 * @see http://my.opera.com/Knedle/blog/show.dml/451690
 */
class server
{
	
	public static function http_user_agent()
	{
		return $_SERVER['HTTP_USER_AGENT'];
	}

	public static function http_host()
	{
		return $_SERVER['HTTP_HOST'];
	}

	public static function server_name()
	{
		return $_SERVER['SERVER_NAME'];
	}

	public static function server_addr()
	{
		return $_SERVER['SERVER_ADDR'];
	}

	public static function server_port()
	{
		return $_SERVER['SERVER_PORT'];
	}

	public static function remote_addr()
	{
		return $_SERVER['REMOTE_ADDR'];
	}
	
	public static function content_length()
	{
		return @$_SERVER['CONTENT_LENGTH'];
	}

	public static function http_referer()
	{
		return (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
	}

	public static function http_accept_language()
	{
		if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER))
		{
			return $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		return FALSE;
	}

	/**
	 * Returns server software
	 * 
	 * @author Michal Kliment
	 * @return string
	 */
	public static function server_software()
	{
		return $_SERVER['SERVER_SOFTWARE'];
	}

	/**
	 * Returns name of script.
	 * 
	 * @author Michal Kliment
	 * @return string
	 */
	public static function script_name()
	{
		return $_SERVER['SCRIPT_NAME'];
	}

	/**
	 * Tests if http server is apache
	 * 
	 * @author Michal Kliment
	 * @return boolean
	 */
	public static function is_apache()
	{
		if (strpos(strtolower(server::server_software()), 'apache') === false)
			return false;
		else
			return true;
	}

	/**
	 * Tests if mod_rewrite is enabled
	 * 
	 * @author Michal Kliment
	 * @return boolen
	 */
	public static function is_mod_rewrite_enabled()
	{
		// server is not apache - other haven't support for mod_rewrite
		if (!server::is_apache())
			return false;

		// mod_rewrite is
		return in_array('mod_rewrite', apache_get_modules());
	}

	/**
	 * Return request uri
	 * 
	 * @author Michal Kliment
	 * @return string
	 */
	public static function request_uri()
	{
		return $_SERVER['REQUEST_URI'];
	}

	/**
	 * Returns query string of current url with ? if not empty
	 *
	 * @author Michal Kliment
	 * @return string
	 */
	public static function query_string()
	{
		return (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']);
	}
	
	/**
	 * Returns base directory of FreenetIS
	 * 
	 * This function is based on current path of this file, please do not
	 * relocate this file!!
	 * 
	 * @author Ondřej Fibich
	 * @return string
	 */
	public static function base_dir()
	{
		return dirname(dirname(dirname(__FILE__)));
	}
	
}

			