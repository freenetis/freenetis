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
 * Triggers E404 error
 */
function e404()
{
	// send error header
	@header("HTTP/1.0 404 Not Found");
	// call function site_lang from another same-name hook, important for right locale
	site_lang();

	$controller = new Errors_Controller();
	$controller->e404();
	die();
}

Event::replace('system.404', array('Kohana', 'show_404'), 'e404');
