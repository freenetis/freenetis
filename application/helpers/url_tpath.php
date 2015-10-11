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
 * The "url_tpath" helper is user for easy usage of template URL path. It was
 * introduce for API authorization purposes, because we wanted to set URL
 * paths which are allowed for a certain API account. Easies way is to
 * write multiple URL path with wildcard * and **.
 * 
 * For example URL path template that matches all URL paths is: /**
 * template that matches /members/ and /members/112 is: /members/*
 * template that matches /m/, /m/112 and /m/112/users is: /members/**
 * 
 * This helper provides function for validation of URL path template,
 * matching agains URL path and function for working with grouped
 * URL path templates.
 *
 * @package Helpers
 * @author Ondřej Fibich
 * @since 1.2
 */
class url_tpath
{
	/**
	 * Regular expression for validation of single URL template path.
	 */
	const VALID_REGEX = '@^(/|((/([a-zA-Z0-9_\-]+|[*]{1,2}))+))$@';
	
	/**
	 * Checks whether given string is a valid URL template path.
	 * 
	 * @param string $url_tpath URL template path
	 * @return boolean is valid?
	 */
	public static function is_valid($url_tpath)
	{
		if (empty($url_tpath) || !is_string($url_tpath))
		{
			return FALSE;
		}
		return !!preg_match(self::VALID_REGEX, $url_tpath);
	}
	
	/**
	 * Checks whether given array of strings contains valid URL template paths.
	 * 
	 * @param array $url_tpaths array of URL template paths
	 * @param string $delim delimiter character between groups [opional: ,]
	 * @return boolean is valid?
	 */
	public static function is_group_valid($url_tpaths)
	{
		if (!is_array($url_tpaths))
		{
			return FALSE;
		}
		foreach ($url_tpaths as $url_tpath)
		{
			if (!self::is_valid($url_tpath))
			{
				return FALSE;
			}
		}
		return TRUE;
	}
	
	/**
	 * Check whether given URL path match given URL template path.
	 * 
	 * @param string $url_tpath URL template path
	 * @param  $url_path URL path to be matched
	 * @return boolean URL path match URL template path?
	 * @throws InvalidArgumentException on invalid URL template path
	 */
	public static function match($url_tpath, $url_path)
	{
		$url_tpath_regex = self::compile_url_tpath($url_tpath);
        $n_path = '/';
        if ($url_path != '/') // special threatment for /
        {
            $n_path = self::normalize($url_path);
        }
		$match_result = preg_match($url_tpath_regex, $n_path);
		if ($match_result === FALSE)
		{
			throw new ErrorException('Internal error: invalid compiled regex: ' 
					. $url_tpath_regex);
		}
		return ($match_result > 0);
	}
	
	/**
	 * Check whther given URL path match one of given URL template paths.
	 * 
	 * @param array $url_tpaths array of URL template paths
	 * @param string $url_path URL path to be matched
	 * @return boolean URL path match one of URL template path?
	 * @throws InvalidArgumentException on invalid one of URL template paths
	 */
	public static function match_one_of($url_tpaths, $url_path)
	{
		if (!is_array($url_tpaths))
		{
			return FALSE;
		}
		foreach ($url_tpaths as $url_tpath)
		{
			if (self::match($url_tpath, $url_path))
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	 * Compiles URL path template to regular expression that may be used for
	 * matching of URL paths agains the template.
	 * 
	 * @param string $url_tpath valid URL template path
	 * @return string string with regular expression compiled from URL template
	 *		path, regex is delimited by @
	 * @throws InvalidArgumentException on invalid URL template path
	 */
	private static function compile_url_tpath($url_tpath)
	{
		if (!self::is_valid($url_tpath))
		{
			throw new InvalidArgumentException('Invalid URL template path: '
					. $url_tpath);
		}
        $n_url_tpath = self::normalize($url_tpath);
        // end with /** or /* than do last / is optional
        preg_replace(
                array("@(/\*\*)$@", "@(/\*)$@"),
                array('/@@', '/@'),
                $n_url_tpath
        );
        // build regex
		return '@^' . str_replace(
				array('*', '/@@', '/@', '@@', '@'),
				array('@', '(|/[a-zA-Z0-9_\-/]*)', '(|/[a-zA-Z0-9_\-]*)', 
                    '([a-zA-Z0-9_\-/]*)', '([a-zA-Z0-9_\-]*)'),
				self::normalize($url_tpath)
		) . '$@';
	}
	
	/**
	 * Normalize URL path or template - that means that each path always ends
	 * by /.
	 * 
	 * @param string $url_path
	 * @return string
	 */
	private static function normalize($url_path)
	{
		return rtrim($url_path, '/');
	}
	
}
