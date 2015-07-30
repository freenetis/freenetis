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
 * Generates variable keys that contains just member ID.
 * 
 * @author Ondrej Fibich
 * @since 1.1.10
 */
class Member_Id_Variable_Key_Generator extends Variable_Key_Generator
{
	
	/**
	 * Generated variable key from given member ID.
	 *
	 * @param mixed $identificator Indentificator for generate from
	 * @return integer	Variable key
	 */
	public function generate($identificator)
	{
		return $identificator;
	}
	
	/*
	 * @override
	 */
	public function errorCheckAvailable()
	{
		return FALSE;
	}
	
	/*
	 * @override
	 */
	public function errorCorrectionAvailable()
	{
		return FALSE;
	}
}
