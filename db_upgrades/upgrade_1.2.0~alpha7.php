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
 * Adds user_id column to members_whitelists tables
 * for loging who adds/edits members whitelist.
 * 
 * @author David Raška <jeffraska@gmail.com>
 */
$upgrade_sql['1.2.0~alpha7'] = array
(
	"ALTER TABLE `members_whitelists` ADD `user_id` INT NULL,
		ADD INDEX ( `user_id` );",

	"ALTER TABLE `members_whitelists` ADD FOREIGN KEY ( `user_id` ) REFERENCES `users` (
		`id`
	) ON DELETE SET NULL ;"
	
); // end of $upgrade_sql['1.2.0~alpha7']