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
 * Db helper.
 *
 * @author  Michal Kliment
 * @package Helper
 */
class db {

	/**
	 * Tests db connection
	 * 
	 * @author Michal Kliment
	 * @return boolean
	 */
	public static function test()
	{
		// we try connect to db
		try
		{
			Database::instance()->connect();
		}
		// and catch exception
		catch (Exception $e)
		{
			// error occurred, return false
			return false;
		}

		// no error occurred, return true
		return true;
	}

} // End db