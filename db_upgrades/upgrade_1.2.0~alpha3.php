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
 * Copies registration license text to registration form info
 * 
 * @author David Raška <jeffraska@gmail.com>
 */
$upgrade_sql['1.2.0~alpha3'] = array
(
	"INSERT INTO `config` (name, value)
		SELECT 'registration_form_info', value
		FROM `config`
		WHERE name LIKE 'registration_license';",
		
); // end of $upgrade_sql['1.2.0~alpha3']