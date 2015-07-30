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
 * Debug helper.
 *
 * @author  Michal Kliment
 * @package Helper
 */
class debug
{
	/**
	 * Dump variable as raw text
	 * 
	 * @author Michal Kliment
	 * @param mixed $var 
	 */
	public static function printr($var)
	{
		echo "<pre>";
		print_r($var);
		echo "</pre>";
	}
	
	/**
	 * Dump variable as raw text
	 * 
	 * @author Michal Kliment
	 * @param mixed $var 
	 */
	public static function vardump($var)
	{
		echo "<pre>";
		var_dump($var);
		echo "</pre>";
	}
}

?>
