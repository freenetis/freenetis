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
$upgrade_sql['1.2.0~alpha4'] = array
(
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`)
		SELECT MAX(`id`)+1, 'Members_Controller', 'applicants', 'Applicants registration' FROM `axo`;",
	
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES
		(38, 'Members_Controller', 'applicants');"
		
); // end of $upgrade_sql['1.2.0~alpha4']