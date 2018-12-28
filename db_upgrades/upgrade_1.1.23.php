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
 * Adds a new notification message for big debtors.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
$upgrade_sql['1.1.23'] = array
(
	"INSERT INTO `messages` (`id`, `name`, `text`, `email_text`, `sms_text`, `type`, `self_cancel`, `ignore_whitelist`) VALUES
	(NULL, 'Big debtor message', 'Content of page for big debtors', NULL, NULL, 20, 0, 0);",

	"ALTER TABLE messages_automatical_activations ADD COLUMN send_activation_to_email VARCHAR(255) DEFAULT NULL;"
);
