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
 * Helper for generating GUIDs
 * 
 * @author Jan Dubina
 */
class guid
{
	public static function getGUID()
	{
		mt_srand((double) microtime() * 10000);
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45); // "-"
		$uuid = substr($charid, 0, 8) . $hyphen
			  . substr($charid, 8, 4) . $hyphen
			  . substr($charid, 12, 4) . $hyphen
			  . substr($charid, 16, 4) . $hyphen
			  . substr($charid, 20, 12);
		return $uuid;
	}

}
