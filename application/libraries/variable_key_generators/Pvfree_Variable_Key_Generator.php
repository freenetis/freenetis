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
 * Generates variable keys for need of PVFREE.
 * To a given ID (member ID) is appended a hashed version of this ID.
 * 
 * @author David Kuba, Ondrej Fibich
 */
class Pvfree_Variable_Key_Generator extends Variable_Key_Generator
{
	
	/**
	 * Generated variable key from given member ID.
	 *
	 * @param mixed $identificator Indentificator for generate from
	 * @return integer	Variable key
	 */
	public function generate($identificator)
	{
		return $identificator . sprintf('%02d', substr(hexdec(substr(md5($identificator), -2)), -2));
	}
	
	/*
	 * @override
	 */
	public function errorCheck($var_key)
	{
		$length = strlen($var_key);
		$identificator = substr($var_key, 0, $length - 2);
		$hash = substr($var_key, $length - 2);
		
		return sprintf('%02d', substr(hexdec(substr(md5($identificator), -2)), -2)) == $hash;
	}
	
	/*
	 * @override
	 */
	public function errorCheckAvailable()
	{
		return TRUE;
	}
	
	/*
	 * @override
	 */
	public function errorCorrectionAvailable()
	{
		return FALSE;
	}
}
