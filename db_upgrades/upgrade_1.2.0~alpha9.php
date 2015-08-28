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
 * Adds flag column for indicating onetime password of user
 * 
 * @author David Raška <jeffraska@gmail.com>
 */
$upgrade_sql['1.2.0~alpha9'] = array
(
	"ALTER TABLE `users` ADD `password_is_onetime` TINYINT( 1 ) NOT NULL AFTER `password_request` ",
	
); // end of $upgrade_sql['1.2.0~alpha9']