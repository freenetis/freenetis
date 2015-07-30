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
 * Bank setting for Reiffeisenbank accounts.
 */
class Raiffeisenbank_Bank_Account_Settings extends Bank_Account_Settings
{
	/*
	 * @Override
	 */
	public function can_import_statements()
	{
		return TRUE;
	}
	
	/*
	 * @Override
	 */
	public function can_download_statements_automatically()
	{
		return FALSE;
	}
	
	/*
	 * @Override
	 */
	public function get_column_fields()
	{
		return array();
	}	
}
