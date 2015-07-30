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
 * This is library for path = history of urls
 * It works as iterarator
 * 
 * @author Michal Kliment, David Raška
 * @version 1.0
 */
class Path
{
	/** String constant for propeerty name of path class in query string */
	const QSNAME = 'path_qsurl';
	
	/**
	 * Actual path
	 * @var string
	 */
	private $path = NULL;

	/**
	 * Returns path as only URI
	 * @var boolean 
	 */
	private $uri = FALSE;

	/**
	 * Returns path with lang (only if URI is true)
	 * @var boolean 
	 */
	private $lang = TRUE;

	/**
	 * For singleton instance
	 * @var Path object
	 */
	private static $instance = NULL;

	/**
	 * Creates or return instance of object
	 *
	 * @author Michal Kliment
	 * @return Path object
	 */
	public static function instance()
	{
		// Create the instance if it does not exist
		empty(self::$instance) and new Path;

		return self::$instance;
	}

	/**
	 * Constructor, only clear object
	 *
	 * @author Michal Kliment
	 */
	public function __construct ()
	{
		if (self::$instance === NULL)
		{
			$this->clear();
			self::$instance = $this;
		}
	}

	/**
	 * Clear object - sets to default value
	 *
	 * @author Michal Kliment
	 * @return Path object
	 */
	public function clear()
	{
		if (isset($_GET[Path::QSNAME]))
			$this->path = url::base().Config::get('lang').'/'.urldecode($_GET[Path::QSNAME]);
		return $this;
	}

	/**
	 * Returns URL of current position in object
	 *
	 * @author Michal Kliment
	 * @param intger $offset
	 * @param integer $length
	 * @return string
	 */
	public function previous($offset = 0, $length = NULL)
	{
		// returns only uri
		if ($this->uri)
		{
			$path = url::uri($this->path);

			// returns without lang
			if (!$this->lang)
				$offset++;
		}
		else
			$path = $this->path;

		// returns rest of string
		if (!$length)
			$length = count(explode('/', $path))-$offset;

		return url::slice($path, $offset, $length);
	}

	/**
	 * Sets boolean values for URI and lang
	 * 
	 * @author Michal Kliment
	 * @param bolean $uri
	 * @param boolean $lang
	 * @return Path 
	 */
	public function uri($uri = TRUE, $lang = FALSE)
	{
		$this->uri = $uri;
		$this->lang = $lang;
		return $this;
	}
}
