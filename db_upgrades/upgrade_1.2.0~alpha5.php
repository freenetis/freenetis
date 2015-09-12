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
 * Adds hash column to email_queues table to
 * store hash for access to e-mail when user is
 * not logged in
 * 
 * @author David Raška <jeffraska@gmail.com>
 */
$upgrade_sql['1.2.0~alpha5'] = array
(
	"ALTER TABLE `email_queues` ADD `hash` VARCHAR( 50 ) NOT NULL "
		
); // end of $upgrade_sql['1.2.0~alpha5']