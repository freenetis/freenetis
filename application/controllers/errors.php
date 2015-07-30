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
 * Errors controller.
 *
 * @author Kliment Michal
 * @package Controller
 */
class Errors_Controller extends Controller
{

	/**
	 * Function to display error 404
	 * 
	 * @author Michal Kliment
	 */
	public function e404()
	{
		$this->error(PAGE);
	}

}
