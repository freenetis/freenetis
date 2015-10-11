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
 * Adds new type of message for contact verification
 * and verify flag to contacts table
 * 
 * @author David Raška <jeffraska@gmail.com>
 */
$upgrade_sql['1.2.0~alpha8'] = array
(
	"ALTER TABLE `contacts` ADD `verify` TINYINT( 1 ) NOT NULL DEFAULT '0'",

	"INSERT INTO `messages` (`id` , `name` , `text` , `email_text` , `sms_text` , `type` , `self_cancel` , `ignore_whitelist` ) VALUES
	(NULL , 'Verify contact', NULL ,
		'<p>Please, verify your FreenetIS e-mail address contact {contact} by clicking on this link: <a href=\"\{verify_link\}\" target=\"_blank\">\{verify_link\}</a></p>',
		NULL, '20', '0', '1');"
	
); // end of $upgrade_sql['1.2.0~alpha8']