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
 * This upgrade cannot be made from any of developer releases that are lesser
 * that last DB upgrade of 1.1 version
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
$upgrade_enabled_only_from['1.1.0'] = array
(
	// all 1.0.* versions (some versions were not released yet but they may be 
	// released before or after releasing 1.1.0)
	'1.0.0', '1.0.1', '1.0.2', '1.0.3', '1.0.4', '1.0.5', '1.0.6', '1.0.7', 
	'1.0.8', '1.0.9', '1.0.10', '1.0.11', '1.0.12', '1.0.13', '1.0.14',
	'1.0.15', '1.0.16', '1.0.17', '1.0.18', '1.0.19', '1.0.21', '1.0.22',
	'1.0.23', '1.0.24', '1.0.25', '1.0.26', '1.0.27', '1.0.28', '1.0.29',
	// last development releases that contains last DB upgrade or are higher
	'1.1.0~beta2', '1.1.0~beta3', '1.1.0~rc1', '1.1.0~rc2',
);

/**
 * This upgrade is equal to last developer releases of 1.1 (that are higher
 * or equal as the last DB upgrade)
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
$upgrade_equal_to['1.1.0'] = array
(
	'1.1.0~beta2', '1.1.0~beta3', '1.1.0~rc1', '1.1.0~rc2'
);

/**
 * This array contains all changes that were made in developer versions of
 * 1.1 version. All changes were grouped together in order to provide easy and
 * fast way how users can upgrade their database from 1.0.* versions.
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
$upgrade_sql['1.1.0'] = array
(
/**
 * Add message for accepted payments and other messages that were added in later
 * updates.
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.1.0~alpha1
 * @see #303
 * @see #341
 * @see #418
 */
	"INSERT INTO `messages` (`id`, `name`, `text`, `email_text`, `sms_text`, `type`, `self_cancel`, `ignore_whitelist`) VALUES
	 (NULL, 'Received payment notice', NULL,
	   'Hello {member_name},<br /><br />Your payment has been accepted into FreenetIS.<br/>Your current balance is: {balance},-',
	   'Your payment has been accepted into FreenetIS. Your current balance is: {balance},-',
	   8, NULL, 1),
	  (NULL, 'Application for membership approved', NULL,
	   'Hello {member_name},<br/><br/>your membership application has been approved. You are one of us from now :-)<br/><br/>Your association!',
	   NULL, 9, NULL, 1),
	  (NULL, 'Application for membership rejected', NULL,
	   'Hello {member_name},<br/><br/>your membership application has been refused. Sorry :-(',
	   NULL, 10, NULL, 1),
	  (NULL, 'Notice of adding connection request', NULL,
	   'Hello {member_name},<br /><br />your request for connection has been stored.<br /><br />Details:<br /> <br /> {comment}<br /><br />Your will be inform about approving/rejecting of your request by e-mail.<br /><br />Your association',
	   NULL, 13, NULL, 1),
	  (NULL, 'Request for connection approved', NULL,
	   'Hello {member_name},<br/><br/>your request for connection has been approoved, details:<br/><br/>{comment}<br/><br/>Your association',
	   NULL, 11, NULL, 1),
	  (NULL, 'Request for connection rejected', NULL,
	   'Hello {member_name},<br/><br/>your request for connection has been rejected, details:<br/><br/>{comment}<br/><br/>Your association',
	   NULL, 12, NULL, 1),
	  (NULL, 'Host {device_name} is unreachable'')', NULL, '<p>Host {device_name} is unreachable since {state_changed_date}.</p>', NULL, 14, NULL, 0),
	  (NULL, 'Host {device_name} is again reachable', NULL, '<p>Host {device_name} is again reachable since {state_changed_date}.</p>', NULL, 15, NULL, 0),
	  (NULL, 'Membership interrupt begins notification', NULL, NULL, NULL, 17, NULL, 1),
	  (NULL, 'Membership interrupt ends notification', NULL, NULL, NULL, 18, NULL, 1),
	  (NULL, 'Former member message', 'You are not a member of this association anymore.', NULL, NULL, 19, NULL, 1);",

/**
 * Add DHCP flag to subnets
 * 
 * @author Michal Kliment <kliment@freenetis.org>
 * @since 1.1.0~alpha2
 */
	"ALTER TABLE `subnets` ADD `dhcp` BOOLEAN NOT NULL DEFAULT FALSE",
	"ALTER TABLE `subnets` ADD `qos` BOOLEAN NOT NULL DEFAULT FALSE",

/**
 * Adds field for recognization of date from which applicant is connected.
 * Adds comment to member fee.
 * 
 * @author Ondřej Fibich
 * @since 1.1.0~alpha3
 * @see #340
 * @see #322
 */
	// Adds field for recognization of date from which applicant is connected. (#340)
	"ALTER TABLE `members` ADD `applicant_connected_from` DATE NULL DEFAULT NULL AFTER `applicant_registration_datetime`",
	// Adds comment to member fee. (#322)
	"ALTER TABLE `members_fees` ADD `comment` TEXT NOT NULL",

/**
 * Adds messages for state of request for connection.
 * Adds access rights for Logs and Login logs.
 * Adds table for connection request.
 * 
 * @author Ondřej Fibich
 * @see #357
 * @see #344
 * @since 1.1.0~alpha4
 */
	// active logs access rights (#357)
	"INSERT INTO `acl` (`id`, `note`) VALUES (NULL, 'Administrators can see logs of actions.');",
	
	"INSERT INTO `aco_map` (`acl_id` ,`value`)
		SELECT id, 'view_all' FROM acl WHERE note = 'Administrators can see logs of actions.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `aro_groups_map` (`acl_id`, `group_id`)
		SELECT id, 32 FROM acl WHERE note = 'Administrators can see logs of actions.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) VALUES (179, 'Logs_Controller', 'logs', 'Logs');",
	
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) VALUES (29, 'Logs_Controller', 'Logs of operations on data');",
	
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`)
		SELECT id, 'Logs_Controller', 'logs' FROM acl WHERE note = 'Administrators can see logs of actions.' ORDER BY id DESC LIMIT 1;",
	
	// active logs access rights (#357)
	"INSERT INTO `acl` (`id`, `note`) VALUES(NULL, 'Administrators can see login logs.');",

	"INSERT INTO `acl` (`id`, `note`) VALUES(NULL, 'Users can see their login logs.');",
	
	"INSERT INTO `aco_map` (`acl_id` ,`value`)
		SELECT id, 'view_all' FROM acl WHERE note = 'Administrators can see login logs.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `aco_map` (`acl_id` ,`value`)
		SELECT id, 'view_own' FROM acl WHERE note = 'Users can see their login logs.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `aro_groups_map` (`acl_id`, `group_id`)
		SELECT id, 32 FROM acl WHERE note = 'Administrators can see login logs.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `aro_groups_map` (`acl_id`, `group_id`)
		SELECT id, 22 FROM acl WHERE note = 'Users can see their login logs.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) VALUES (180, 'Login_logs_Controller', 'logs', 'Login logs');",
	
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) VALUES (30, 'Login_logs_Controller', 'Logs of operations on data');",
	
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`)
		SELECT id, 'Login_logs_Controller', 'logs' FROM acl WHERE note = 'Administrators can see login logs.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`)
		SELECT id, 'Login_logs_Controller', 'logs' FROM acl WHERE note = 'Users can see their login logs.' ORDER BY id DESC LIMIT 1;",
	
	// connection requests table (#344)
	"CREATE TABLE IF NOT EXISTS `connection_requests` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `member_id` int(11) NOT NULL COMMENT 'Owner of the connection',
	  `added_user_id` int(11) DEFAULT NULL COMMENT 'Who made request',
	  `decided_user_id` INT( 11 ) NULL DEFAULT NULL COMMENT 'User who approve/reject this connection request.',
	  `state` int(11) DEFAULT 0,
	  `created_at` datetime NOT NULL,
	  `decided_at` DATETIME NULL DEFAULT NULL,
	  `ip_address` varchar(39) COLLATE utf8_czech_ci NOT NULL,
	  `subnet_id` int(11) NOT NULL,
	  `mac_address` varchar(17) COLLATE utf8_czech_ci NOT NULL,
	  `device_id` INT( 11 ) NULL DEFAULT NULL COMMENT 'ID of the device that was created from this connection request or null.',
	  `device_type_id` int(11) DEFAULT NULL,
	  `device_template_id` int(11) DEFAULT NULL,
	  `comment` text DEFAULT NULL COMMENT 'Comment of user who made request',
	  `comments_thread_id` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  FOREIGN KEY `member_id_fk` (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
	  FOREIGN KEY `added_user_id_fk` (`added_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
	  FOREIGN KEY `decided_user_id_fk` (`decided_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
	  FOREIGN KEY `subnet_id_fk` (`subnet_id`) REFERENCES `subnets` (`id`) ON DELETE CASCADE,
	  FOREIGN KEY `device_id_fk` (`device_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL,
	  FOREIGN KEY `device_type_id_fk` (`device_type_id`) REFERENCES `enum_types` (`id`) ON DELETE SET NULL,
	  FOREIGN KEY `device_template_id_fk` (`device_template_id`) REFERENCES `device_templates` (`id`) ON DELETE SET NULL,
	  FOREIGN KEY `comments_thread_id_fk` (`comments_thread_id`) REFERENCES `comments_threads` (`id`) ON DELETE SET NULL
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;",
	
	// add access rights for (#344)
	"INSERT INTO `acl` (`id`, `note`) VALUES(NULL, 'Administrators can view request and change state of connection request.');",

	"INSERT INTO `acl` (`id`, `note`) VALUES(NULL, 'Regular members and applicants can add new connection request and they can view their requests.');",
	
	"INSERT INTO `aco_map` (`acl_id` ,`value`)
		SELECT id, 'view_all' FROM acl WHERE note = 'Administrators can view request and change state of connection request.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `aco_map` (`acl_id` ,`value`)
		SELECT id, 'edit_all' FROM acl WHERE note = 'Administrators can view request and change state of connection request.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `aco_map` (`acl_id` ,`value`)
		SELECT id, 'new_own' FROM acl WHERE note = 'Regular members and applicants can add new connection request and they can view their requests.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `aco_map` (`acl_id` ,`value`)
		SELECT id, 'view_own' FROM acl WHERE note = 'Regular members and applicants can add new connection request and they can view their requests.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `aro_groups_map` (`acl_id`, `group_id`)
		SELECT id, 32 FROM acl WHERE note = 'Administrators can view request and change state of connection request.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `aro_groups_map` (`acl_id`, `group_id`)
		SELECT id, 22 FROM acl WHERE note = 'Regular members and applicants can add new connection request and they can view their requests.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `aro_groups_map` (`acl_id`, `group_id`)
		SELECT id, 23 FROM acl WHERE note = 'Regular members and applicants can add new connection request and they can view their requests.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) VALUES (31, 'Connection_Requests_Controller', 'Connection requests handling');",
	
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) VALUES (181, 'Connection_Requests_Controller', 'request', 'Connection request');",
	
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`)
		SELECT id, 'Connection_Requests_Controller', 'request' FROM acl WHERE note = 'Administrators can view request and change state of connection request.' ORDER BY id DESC LIMIT 1;",
	
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`)
		SELECT id, 'Connection_Requests_Controller', 'request' FROM acl WHERE note = 'Regular members and applicants can add new connection request and they can view their requests.' ORDER BY id DESC LIMIT 1;",
	
/**
 * Redirection of user mail (system mail) to his own e-mail (#351)
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.1.0~alpha6
 */
	/* redirection of user mail (system mail) to his own e-mail */
	"ALTER TABLE `users_contacts`  ADD `mail_redirection` TINYINT(1) NOT NULL DEFAULT '0'
	 COMMENT 'In condition that this contact is an e-mail, this indicator specifies whether the inner system mail is redirected to this user''s e-mail box.'",
	// clean users
	"ALTER TABLE `users` DROP `web_messages_types`, DROP `email_messages_types`;",
	
/**
 * Queue of logs and errors in database (#462) and IP address index (#446).
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.1.0~alpha7
 */
	/* IP address index (#446) */
	"ALTER TABLE `ip_addresses` ADD INDEX `ip_address` (`ip_address`);",
	
	/* Queue of logs and errors in database (#462) */
	"CREATE TABLE `log_queues` (
		`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
		`type` SMALLINT NOT NULL ,
		`state` SMALLINT NOT NULL ,
		`created_at` DATETIME NOT NULL ,
		`closed_by_user_id` INT( 11 ) NULL DEFAULT NULL ,
		`closed_at` DATETIME NULL DEFAULT NULL ,
		`description` TEXT NOT NULL ,
		`exception_backtrace` TEXT NULL DEFAULT NULL ,
		`comments_thread_id` INT NULL DEFAULT NULL ,
		PRIMARY KEY ( `id` ),
		FOREIGN KEY `closed_by_user_id_fk` (`closed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
		FOREIGN KEY `log_queues_comments_thread_id_fk` (`comments_thread_id`) REFERENCES `comments_threads` (`id`) ON DELETE SET NULL
	 ) ENGINE = InnoDB;",
	
/**
 * Access time in device
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.1.0~alpha8
 */
	// Access time of device (#349)
	"ALTER TABLE `devices` ADD `access_time` DATETIME NULL DEFAULT NULL AFTER `password`",
	
/**
 * Optimalization of reload of DHCP (#465)
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.1.0~alpha9
 */
	// Indicator of expired subnet (DHCP must reload) (#465)
	"ALTER TABLE `subnets` ADD `dhcp_expired` TINYINT( 1 ) NOT NULL DEFAULT '0'
	 COMMENT 'If DHCP is enabled on this subnet, this value indicates if any of its record was updated and not synchronized to DHCP server.'
	 AFTER `dhcp`",
	// all DHCP expire
	"UPDATE subnets SET dhcp_expired = 1 WHERE dhcp = 1",
	
/**
 * Whitelist (#35)
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.1.0~alpha11
 */
	/* Create new structure */
	"CREATE TABLE `members_whitelists` (
		`id` INT(11) NOT NULL AUTO_INCREMENT,
		`member_id` INT(11) NOT NULL, 
		`permanent` TINYINT(1) NOT NULL, 
		`since` DATE NOT NULL, 
		`until` DATE NOT NULL, 
		`comment` TEXT, 
		PRIMARY KEY (`id`),
		INDEX `since_index` (`since`),
		INDEX `until` (`until`),
		FOREIGN KEY `members_whitelists_member_id_fk` (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
	) ENGINE = InnoDB COMMENT = 'Redirection member white list.';",
	/* Import old data */
	// association
	"INSERT INTO `members_whitelists` (`id`, `member_id`, `permanent`, `since`, `until`)
	 SELECT NULL, m.id, 1, entrance_date AS since, '9999-12-31' AS until
	 FROM members m
	 WHERE m.id = 1",
	// add permanent whitelists from current IP address settings
	"INSERT INTO `members_whitelists` (`id`, `member_id`, `permanent`, `since`, `until`)
	 SELECT NULL, m.id, 1, '" . date('Y-m-d') . "' AS since, '9999-12-31' AS until
	 FROM (
			 SELECT me.id
			 FROM members me
			 JOIN users u ON u.member_id = me.id
			 JOIN devices d ON d.user_id = u.id
			 JOIN ifaces i ON i.device_id = d.id
			 JOIN ip_addresses ip ON ip.iface_id = i.id
			 WHERE ip.whitelisted = 1
	    UNION
			 SELECT me.id
			 FROM members me
			 JOIN ip_addresses ip ON ip.member_id = me.id
			 WHERE ip.whitelisted = 1
	    UNION
			 SELECT uu.member_id
			 FROM users uu
			 JOIN users_contacts uc ON uc.user_id = uu.id
			 WHERE uc.whitelisted = 1
	 ) m
	 GROUP BY m.id",
	// add temporal whitelists from current IP address settings (for one week)
	"INSERT INTO `members_whitelists` (`id`, `member_id`, `permanent`, `since`, `until`)
	 SELECT NULL, m.id, 0, '" . date('Y-m-d') . "' AS since, '" . date('Y-m-d', time() + 604800) . "' AS until
	 FROM (
			 SELECT me.id
			 FROM members me
			 JOIN users u ON u.member_id = me.id
			 JOIN devices d ON d.user_id = u.id
			 JOIN ifaces i ON i.device_id = d.id
			 JOIN ip_addresses ip ON ip.iface_id = i.id
			 WHERE ip.whitelisted = 2
	    UNION
			 SELECT me.id
			 FROM members me
			 JOIN ip_addresses ip ON ip.member_id = me.id
			 WHERE ip.whitelisted = 2	
	    UNION
			 SELECT uu.member_id
			 FROM users uu
			 JOIN users_contacts uc ON uc.user_id = uu.id
			 WHERE uc.whitelisted = 2		 
	 ) m
	 GROUP BY m.id",
	/* Clean old structure */
	// remove old columns
	"ALTER TABLE `ip_addresses` DROP `whitelisted`",
	"ALTER TABLE `users_contacts` DROP `whitelisted`",
	
	/* Speed enhance for member_fees */
	"ALTER TABLE `members_fees` ADD INDEX ( `activation_date` , `deactivation_date` ) ;",
	
/**
 * Speed classes (#359) and SMS table optimalization (#412).
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.1.0~alpha12
 */
	/* SMS table optimalization (#419) */
	"ALTER TABLE `sms_messages` ADD INDEX (`sender`);",
	"ALTER TABLE `sms_messages` ADD INDEX (`receiver`);",
	
	/* Speed classes (#359) */
	"CREATE TABLE  `speed_classes` (
	  `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
	  `name` VARCHAR( 50 ) NOT NULL ,
	  `d_ceil` BIGINT( 11 ) NOT NULL COMMENT  'QoS download ceil in bytes',
	  `d_rate` BIGINT( 11 ) NOT NULL COMMENT  'QoS download rate in bytes',
	  `u_ceil` BIGINT( 11 ) NOT NULL COMMENT  'QoS upload ceil in bytes',
	  `u_rate` BIGINT( 11 ) NOT NULL COMMENT  'QoS upload rate in bytes',
	  `regular_member_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Is this class default for regular members?',
	  `applicant_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Is this class default for applicants?',
	  PRIMARY KEY ( `id` )
	 ) ENGINE = INNODB COMMENT =  'Defines speed classes for QoS';",
	// relation between members and speed classes
	"ALTER TABLE  `members` ADD  `speed_class_id` INT( 11 ) DEFAULT NULL AFTER  `qos_rate`",
	"ALTER TABLE `members` ADD FOREIGN KEY `speed_class_id_fk` (`speed_class_id`)
	 REFERENCES `speed_classes` (`id`) ON DELETE SET NULL",
	
/**
 * Expiring of testing connection (#527).
 * Access rights for regular member to view his QoS info (#520).
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.1.0~alpha15
 */
	// messages for denying of expired test connection
	"INSERT INTO `messages` (`id`,`name`, `text`, `email_text`, `sms_text`, `type`, `self_cancel`, `ignore_whitelist`) VALUES
	(NULL, 'Test connection has expired', 'Test connection has expired. You must submit your membership registration!', NULL, NULL, 16, NULL, 0);",
	// qos info access rights
	"REPLACE INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES
	 (43, 'Members_Controller', 'qos_ceil'),
	 (43, 'Members_Controller', 'qos_rate');",
	
/**
 * Requests.
 * 
 * @author Michal Kliment
 * @since 1.1.0~alpha23
 */
	"CREATE TABLE IF NOT EXISTS `requests` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `user_id` int(11) NOT NULL,
	  `approval_template_id` int(11) DEFAULT NULL,
	  `description` mediumtext COLLATE utf8_czech_ci,
	  `suggest_amount` int(11) NOT NULL COMMENT 'suggest amount by user',
	  `date` date NOT NULL,
	  `state` tinyint(1) NOT NULL,
	  `comments_thread_id` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `user_id` (`user_id`),
	  KEY `comments_thread_id` (`comments_thread_id`),
	  KEY `approval_template_id` (`approval_template_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;",

	"ALTER TABLE `requests`
	  ADD CONSTRAINT `requests_ibfk_3` FOREIGN KEY (`comments_thread_id`) REFERENCES `comments` (`id`) ON DELETE SET NULL,
	  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
	  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`approval_template_id`) REFERENCES `approval_templates` (`id`) ON DELETE SET NULL;",

	"CREATE TABLE IF NOT EXISTS `watchers` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `user_id` int(11) NOT NULL,
	  `type` int(11) NOT NULL,
	  `fk_id` int(11) NOT NULL,
	  PRIMARY KEY (`id`),
	  KEY `user_id` (`user_id`,`type`,`fk_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;",

	"ALTER TABLE `watchers`
	  ADD CONSTRAINT `watchers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);",

	"ALTER TABLE  `votes` DROP FOREIGN KEY  `votes_ibfk_2` ;",

	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) VALUES 
		('182', 'Requests_Controller', 'request', 'Request'),
		('183', 'Requests_Controller', 'approval_template', 'Approval template of request'),
		('184', 'Work_reports_Controller', 'work_report', 'Work report'),
		('185', 'Work_reports_Controller', 'approval_template', 'Approval template of work report'),
		('186', 'Comments_Controller', 'requests', 'Comments for request'),
		('187', 'Works_Controller', 'approval_template', 'Approval template of work'),
		('188', 'Log_queues_Controller', 'log_queue', 'Log queues'),
		('189', 'Log_queues_Controller', 'comments', 'Comments for log queue'),
		('190', 'Membership_transfers_Controller', 'membership_transfer', 'Transfer of membership');",

	"UPDATE  `axo` SET  `section_value` =  'Works_Controller' WHERE  `axo`.`id` = 111;",

	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES 
		('38', 'Requests_Controller', 'request'),
		('38', 'Requests_Controller', 'approval_template'),
		('38', 'Work_reports_Controller', 'work_report'),
		('38', 'Work_reports_Controller', 'approval_template'),
		('38', 'Works_Controller', 'approval_template'),
		('38', 'Comments_Controller', 'requests'),
		('64', 'Requests_Controller', 'request'),
		('51', 'Requests_Controller', 'request'),
		('64', 'Work_reports_Controller', 'work_report'),
		('51', 'Work_reports_Controller', 'work_report'),
		('56', 'Work_reports_Controller', 'work_report'),
		('76', 'Comments_Controller', 'requests'),
		('51', 'Comments_Controller', 'requests'),
		('51', 'Comments_Controller', 'works'),
		('38', 'Log_queues_Controller', 'log_queue'),
		('38', 'Log_queues_Controller', 'comments'),
		('38', 'Membership_transfers_Controller', 'membership_transfer');",

	"UPDATE axo_map SET section_value = 'Works_Controller' WHERE section_value LIKE 'Users_Controller' AND value LIKE 'work'",

	"INSERT INTO watchers
	SELECT NULL, gam.aro_id, i.type, fk_id
	FROM
	(
			SELECT 1 AS type, j.id AS fk_id, j.approval_template_id
			FROM jobs j
			WHERE (state = 0 OR state = 1) AND j.job_report_id IS NULL
		UNION
			SELECT 2 AS type, job_report_id AS fk_id, j.approval_template_id
			FROM jobs j
			WHERE (state = 0 OR state = 1) AND j.job_report_id IS NOT NULL
			GROUP BY job_report_id
	) i
	JOIN approval_templates at ON i.approval_template_id = at.id
	JOIN approval_template_items ati ON ati.approval_template_id = at.id
	JOIN approval_types t ON ati.approval_type_id = t.id
	JOIN groups_aro_map gam ON gam.group_id = t.aro_group_id
	GROUP BY fk_id, i.type, gam.aro_id",
	
/**
 * Adds new table for membership transfers
 * 
 * @author Michal Kliment
 * @since 1.1.0~alpha25
 */
	"CREATE TABLE IF NOT EXISTS `membership_transfers` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `from_member_id` int(11) NOT NULL,
	  `to_member_id` int(11) NOT NULL,
	  PRIMARY KEY (`id`),
	  KEY `from_member_id` (`from_member_id`),
	  KEY `to_member_id` (`to_member_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci",

	"ALTER TABLE `membership_transfers` ADD FOREIGN KEY `to_member_id_fk` (`to_member_id`)
	REFERENCES `members` (`id`) ON DELETE CASCADE",
	
	"ALTER TABLE `membership_transfers` ADD FOREIGN KEY `from_member_id_fk` (`from_member_id`)
	REFERENCES `members` (`id`) ON DELETE CASCADE;",
	
/**
 * Add own AXO for networks
 * 
 * @author Michal Kliment
 * @since 1.1.0~alpha26
 */
	// axo changes
	"INSERT INTO axo SELECT MAX(id)+1, 'Monitoring_Controller', 'monitoring', 'Monitoring' FROM axo",
	"INSERT INTO axo SELECT MAX(id)+1, 'Device_logs_Controller', 'device_log', 'Logs from devices' FROM axo",
	"INSERT INTO axo SELECT MAX(id)+1, 'Devices_Controller', 'export', 'Export of device' FROM axo",
	"INSERT INTO axo SELECT MAX(id)+1, 'Devices_Controller', 'map', 'Map of devices' FROM axo",
	"DELETE FROM axo WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'vlan_iface'",
	"DELETE FROM axo WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'port'",
	"UPDATE axo SET section_value = 'Ifaces_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'iface'",
	"UPDATE axo SET section_value = 'Ip_addresses_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'ip_address'",
	"DELETE FROM axo WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'main_engineer'",
	"DELETE FROM axo WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'wireless_setting'",
	"UPDATE axo SET section_value = 'Tools_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'tools'",
	"UPDATE axo SET section_value = 'Subnets_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'redirect'",
	"UPDATE axo SET section_value = 'Subnets_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'subnet'",
	"UPDATE axo SET section_value = 'Allowed_subnets_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'allowed_subnet'",
	"UPDATE axo SET section_value = 'Vlans_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'vlan'",
	"UPDATE axo SET section_value = 'Links_Controller', value = 'link', name = 'Link' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'segment'",
	"INSERT INTO axo SELECT MAX(id)+1, 'Ifaces_Controller', 'link', 'Link of ifaces' FROM axo",
	// Fixes #557: forgotten AXO (1.1.0~alpha27)
	"INSERT INTO axo SELECT MAX(id)+1, 'Settings_Controller', 'access_rights', 'Access rights' FROM axo",
	
	// axo_map changes
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES ('38', 'Monitoring_Controller', 'monitoring');",
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES ('38', 'Device_logs_Controller', 'device_log');",
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES ('38', 'Devices_Controller', 'export');",
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES ('38', 'Devices_Controller', 'map');",
	"DELETE FROM axo_map WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'vlan_iface'",
	"DELETE FROM axo_map WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'port'",
	"UPDATE axo_map SET section_value = 'Ifaces_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'iface'",
	"UPDATE axo_map SET section_value = 'Ip_addresses_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'ip_address'",
	"DELETE FROM axo_map WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'main_engineer'",
	"DELETE FROM axo_map WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'wireless_setting'",
	"UPDATE axo_map SET section_value = 'Tools_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'tools'",
	"UPDATE axo_map SET section_value = 'Subnets_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'redirect'",
	"UPDATE axo_map SET section_value = 'Subnets_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'subnet'",
	"UPDATE axo_map SET section_value = 'Allowed_subnets_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'allowed_subnet'",
	"UPDATE axo_map SET section_value = 'Vlans_Controller' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'vlan'",
	"UPDATE axo_map SET section_value = 'Links_Controller', value = 'link' WHERE section_value LIKE 'Devices_Controller' AND value LIKE 'segment'",
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES ('38', 'Ifaces_Controller', 'link');",

/**
 * Alters tables for invoices, invoice items and invoice templates
 * 
 * @author Jan Dubina
 * @since 1.1.0~alpha28
 */
	"ALTER TABLE  `invoices` DROP FOREIGN KEY  `invoices_ibfk_1` ;",

	"ALTER TABLE  `invoices` DROP INDEX  `supplier_id`",

	"ALTER TABLE  `invoices` CHANGE  `supplier_id`  `member_id` INT( 11 ) NULL DEFAULT NULL",

	"ALTER TABLE  `invoices` ADD  `partner_company` VARCHAR( 100 ) NULL DEFAULT NULL AFTER  `member_id` ,
	ADD  `partner_name` VARCHAR( 100 ) NULL DEFAULT NULL AFTER  `partner_company` ,
	ADD  `partner_street` VARCHAR( 30 ) NULL DEFAULT NULL AFTER  `partner_name` ,
	ADD  `partner_street_number` VARCHAR( 50 ) NULL DEFAULT NULL AFTER  `partner_street` ,
	ADD  `partner_town` VARCHAR( 50 ) NULL DEFAULT NULL AFTER  `partner_street_number` ,
	ADD  `partner_zip_code` VARCHAR( 10 ) NULL DEFAULT NULL AFTER  `partner_town` ,
	ADD  `partner_country` VARCHAR( 100 ) NULL DEFAULT NULL AFTER  `partner_zip_code` ,
	ADD  `organization_identifier` VARCHAR( 20 ) NULL DEFAULT NULL AFTER  `partner_country` ,
	ADD  `phone_number` VARCHAR( 15 ) NULL DEFAULT NULL AFTER  `organization_identifier` ,
	ADD  `email` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `phone_number`",

	"ALTER TABLE  `invoices` ADD  `invoice_type` TINYINT( 1 ) NOT NULL AFTER  `invoice_nr` ,
	ADD  `account_nr` VARCHAR( 254 ) NULL DEFAULT NULL AFTER  `invoice_type`",

	"ALTER TABLE  `invoices` ADD  `note` VARCHAR( 240 ) NULL DEFAULT NULL AFTER  `currency`",

	"ALTER TABLE  `invoices` CHANGE  `con_sym`  `con_sym` DOUBLE NULL DEFAULT NULL",

	"ALTER TABLE  `invoices` CHANGE  `vat`  `vat` TINYINT( 1 ) NOT NULL",

	"ALTER TABLE  `invoices` CHANGE  `order_nr`  `order_nr` DOUBLE NULL DEFAULT NULL",

	"ALTER TABLE  `invoices` ADD INDEX  `member_id` (  `member_id` )",

	"ALTER TABLE  `invoices` ADD FOREIGN KEY (  `member_id` ) REFERENCES  `members` (
	`id`
	) ON DELETE CASCADE ;",

	"ALTER TABLE  `invoice_items` CHANGE  `price_vat`  `vat` DOUBLE NOT NULL",

	"ALTER TABLE  `invoice_templates` DROP  `supplier_id`",

	"ALTER TABLE  `invoice_templates` DROP  `org_id`",

	"ALTER TABLE `invoice_templates`  ADD `invoices` VARCHAR(255) NULL DEFAULT NULL AFTER `name`,  
	ADD `sup_company` TEXT NULL DEFAULT NULL AFTER `invoices`,  
	ADD `sup_name` TEXT NULL DEFAULT NULL AFTER `sup_company`,  
	ADD `sup_street` VARCHAR(255) NULL DEFAULT NULL AFTER `sup_name`,  
	ADD `sup_street_number` VARCHAR(255) NULL DEFAULT NULL AFTER `sup_street`,  
	ADD `sup_town` VARCHAR(255) NULL DEFAULT NULL AFTER `sup_street_number`,  
	ADD `sup_zip_code` VARCHAR(255) NULL DEFAULT NULL AFTER `sup_town`,  
	ADD `sup_country` VARCHAR(255) NULL DEFAULT NULL AFTER `sup_zip_code`,  
	ADD `sup_organization_identifier` VARCHAR(255) NULL DEFAULT NULL AFTER `sup_country`,  
	ADD `sup_phone_number` VARCHAR(255) NULL DEFAULT NULL AFTER `sup_organization_identifier`,  
	ADD `sup_email` VARCHAR(255) NULL DEFAULT NULL AFTER `sup_phone_number`,  
	ADD `cus_company` TEXT NULL DEFAULT NULL AFTER `sup_email`,  
	ADD `cus_name` TEXT NULL DEFAULT NULL AFTER `cus_company`,  
	ADD `cus_street` VARCHAR(255) NULL DEFAULT NULL AFTER `cus_name`,  
	ADD `cus_street_number` VARCHAR(255) NULL DEFAULT NULL AFTER `cus_street`,  
	ADD `cus_town` VARCHAR(255) NULL DEFAULT NULL AFTER `cus_street_number`,  
	ADD `cus_zip_code` VARCHAR(255) NULL DEFAULT NULL AFTER `cus_town`,  
	ADD `cus_country` VARCHAR(255) NULL DEFAULT NULL AFTER `cus_zip_code`, 
	ADD `cus_organization_identifier` VARCHAR(255) NULL DEFAULT NULL AFTER `cus_country`, 
	ADD `cus_phone_number` VARCHAR(255) NULL DEFAULT NULL AFTER `cus_organization_identifier`,  
	ADD `cus_email` VARCHAR(255) NULL DEFAULT NULL AFTER `cus_phone_number`,  
	ADD `org_id` VARCHAR(255) NULL DEFAULT NULL AFTER `cus_email`",

	"ALTER TABLE  `invoice_templates` ADD  `invoice_type` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `invoice_nr` ,
	ADD  `invoice_type_issued` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `invoice_type` ,
	ADD  `account_nr` TEXT NULL DEFAULT NULL AFTER  `invoice_type_issued`",

	"ALTER TABLE  `invoice_templates` ADD  `price` TEXT NULL DEFAULT NULL AFTER  `order_nr` ,
	ADD  `price_vat` TEXT NULL DEFAULT NULL AFTER  `price`",

	"ALTER TABLE  `invoice_templates` ADD  `note` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `currency` ,
	ADD  `items` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `note` ,
	ADD  `item_name` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `items` ,
	ADD  `item_code` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `item_name` ,
	ADD  `item_quantity` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `item_code` ,
	ADD  `item_price` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `item_quantity` ,
	ADD  `item_vat` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `item_price`",

	"ALTER TABLE  `invoice_templates` ADD  `namespace` TEXT NULL DEFAULT NULL AFTER  `charset` ,
	ADD  `vat_variables` TEXT NULL DEFAULT NULL AFTER  `namespace`",

	"ALTER TABLE  `invoice_templates` CHANGE  `xml`  `type` TINYINT( 1 ) NOT NULL",

	"ALTER TABLE  `invoice_templates` CHANGE  `order_nr`  `order_nr` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL",

	"ALTER TABLE `invoice_templates` CHANGE `invoice_nr` `invoice_nr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL, 
	CHANGE `var_sym` `var_sym` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL, 
	CHANGE `con_sym` `con_sym` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL, 
	CHANGE `date_inv` `date_inv` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL, 
	CHANGE `date_due` `date_due` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL, 
	CHANGE `date_vat` `date_vat` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL, 
	CHANGE `vat` `vat` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL, 
	CHANGE `currency` `currency` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL, 
	CHANGE `charset` `charset` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL, 
	CHANGE `begin_tag` `begin_tag` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL, 
	CHANGE `end_tag` `end_tag` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_czech_ci NULL DEFAULT NULL",

	"TRUNCATE TABLE `invoice_templates`",

	"INSERT INTO `invoice_templates` (`id`, `name`, `invoices`, `sup_company`, `sup_name`, `sup_street`, `sup_street_number`, `sup_town`, `sup_zip_code`, `sup_country`, `sup_organization_identifier`, `sup_phone_number`, `sup_email`, `cus_company`, `cus_name`, `cus_street`, `cus_street_number`, `cus_town`, `cus_zip_code`, `cus_country`, `cus_organization_identifier`, `cus_phone_number`, `cus_email`, `org_id`, `invoice_nr`, `invoice_type`, `invoice_type_issued`, `account_nr`, `var_sym`, `con_sym`, `date_inv`, `date_due`, `date_vat`, `vat`, `order_nr`, `price`, `price_vat`, `currency`, `note`, `items`, `item_name`, `item_code`, `item_quantity`, `item_price`, `item_vat`, `charset`, `namespace`, `vat_variables`, `type`, `begin_tag`, `end_tag`) VALUES
	(1, 'ED invoice in XML', '/INVOICES/INVOICE', NULL, 'string(''eD system Czech a.s.'')', 'string(''Tuřanka'')', 'string(''1222/115'')', 'string(''Brno – Slatina'')', 'string(''627 00'')', 'string(''Czech Republic'')', 'string(''47974516'')', 'string(''531011111'')', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'string(./INV_ID)', 'number(1)', NULL, 'string(''409039533/0300'')', 'string(./VAR_SYM)', 'string(./CON_SYM)', 'concat(substring(string(./DATE_INV),7,4),''-'',substring(string(./DATE_INV),4,2),''-'',substring(string(./DATE_INV),1,2))', 'concat(substring(string(./DATE_DUE),7,4),''-'',substring(string(./DATE_DUE),4,2),''-'',substring(string(./DATE_DUE),1,2))', 'concat(substring(string(./DATE_VAT),7,4),''-'',substring(string(./DATE_VAT),4,2),''-'',substring(string(./DATE_VAT),1,2))', 'number(starts-with(string(./VAT),''True''))', 'string(./ORD_ID)', NULL, NULL, 'string(./CUR_ID)', NULL, './ITEMS/ITEM', 'string(./PRO_NAME)', 'string(./PRO_CODE)', 'string(./QTY)', 'string(./PRICE)', '(number(translate(./PRICE_VAT, \",\", \".\")) div number(translate(./PRICE, \",\", \".\")) - 1) * 100', 'utf-8', NULL, '', 4, '', ''),
	(2, 'Pohoda invoice in eForm', '/eform/invoice', 'string(./pricestax/supplier/company)', 'string(./pricestax/supplier/name)', 'string(./pricestax/supplier/street)', NULL, 'string(./pricestax/supplier/city)', 'string(./pricestax/supplier/psc)', NULL, 'string(./pricestax/supplier/ico)', 'string(./pricestax/supplier/tel)', 'string(./pricestax/supplier/email)', 'string(./pricestax/customer/company)', 'string(./pricestax/customer/name)', 'string(./pricestax/customer/street)', NULL, 'string(./pricestax/customer/city)', 'string(./pricestax/customer/psc)', NULL, 'string(./pricestax/customer/ico)', 'string(./pricestax/customer/tel)', 'string(./pricestax/customer/email)', NULL, 'string(./documenttax/@number)', NULL, NULL, 'concat(substring(concat(./pricestax/payment/@accountno, ''/'', string(./pricestax/payment/@bankcode)), 1 div boolean(boolean(./pricestax/payment/@accountno) and boolean(string(./pricestax/payment/@bankcode)))), substring(string(''''), 1 div not(boolean(boolean(./pricestax/payment/@accountno) and boolean(string(./pricestax/payment/@bankcode))))))', 'string(./documenttax/@symvar)', 'string(./documenttax/@symconst)', 'string(./documenttax/@date)', 'string(./documenttax/@datedue)', 'string(./documenttax/@datetax)', 'number(concat(substring(1, 1 div boolean(./pricestax/payment[@payvat=''yes''])), substring(0, 1 div not(boolean(./pricestax/payment[@payvat=''yes''])))))', 'string(./documenttax/@numberorder)', NULL, NULL, '''''', NULL, './invoiceitem', 'string(.)', 'string(./@code)', 'string(./@quantity)', 'string(./@price)', 'string(./@ratevat)', 'CP1250', NULL, '{\"export\":{\"0\":\"none\",\"150\":\"low\",\"210\":\"high\"},\"import\":{\"none\":0,\"low\":150,\"high\":210}}', 0, '<xml>', '<\\/xml>'),
	(3, 'Pohoda invoice in ISDOC', '/i:Invoice', 'concat(substring(./i:AccountingSupplierParty/i:Party/i:PartyName/i:Name, 1 div boolean(./i:AccountingSupplierParty/i:Party/i:Contact/i:Name)), substring('''', 1 div not(boolean(./i:AccountingSupplierParty/i:Party/i:Contact/i:Name))))', 'concat(substring(./i:AccountingSupplierParty/i:Party/i:Contact/i:Name, 1 div boolean(./i:AccountingSupplierParty/i:Party/i:Contact/i:Name)), substring(./i:AccountingSupplierParty/i:Party/i:PartyName/i:Name, 1 div not(boolean(./i:AccountingSupplierParty/i:Party/i:Contact/i:Name))))', 'string(./i:AccountingSupplierParty/i:Party/i:PostalAddress/i:StreetName)', 'string(./i:AccountingSupplierParty/i:Party/i:PostalAddress/i:BuildingNumber)', 'string(./i:AccountingSupplierParty/i:Party/i:PostalAddress/i:CityName)', 'string(./i:AccountingSupplierParty/i:Party/i:PostalAddress/i:PostalZone)', 'string(./i:AccountingSupplierParty/i:Party/i:PostalAddress/i:Country/i:Name)', 'string(./i:AccountingSupplierParty/i:Party/i:PartyIdentification/i:ID)', 'string(./i:AccountingSupplierParty/i:Party/i:Contact/i:Telephone)', 'string(./i:AccountingSupplierParty/i:Party/i:Contact/i:ElectronicMail)', 'concat(substring(./i:AccountingCustomerParty/i:Party/i:PartyName/i:Name, 1 div boolean(./i:AccountingCustomerParty/i:Party/i:Contact/i:Name)), substring('''', 1 div not(boolean(./i:AccountingCustomerParty/i:Party/i:Contact/i:Name))))', 'concat(substring(./i:AccountingCustomerParty/i:Party/i:Contact/i:Name, 1 div boolean(./i:AccountingCustomerParty/i:Party/i:Contact/i:Name)), substring(./i:AccountingCustomerParty/i:Party/i:PartyName/i:Name, 1 div not(boolean(./i:AccountingCustomerParty/i:Party/i:Contact/i:Name))))', 'string(./i:AccountingCustomerParty/i:Party/i:PostalAddress/i:StreetName)', 'string(./i:AccountingCustomerParty/i:Party/i:PostalAddress/i:BuildingNumber)', 'string(./i:AccountingCustomerParty/i:Party/i:PostalAddress/i:CityName)', 'string(./i:AccountingCustomerParty/i:Party/i:PostalAddress/i:PostalZone)', 'string(./i:AccountingCustomerParty/i:Party/i:PostalAddress/i:Country/i:Name)', 'string(./i:AccountingCustomerParty/i:Party/i:PartyIdentification/i:ID)', 'string(./i:AccountingCustomerParty/i:Party/i:Contact/i:Telephone)', 'string(./i:AccountingCustomerParty/i:Party/i:Contact/i:ElectronicMail)', NULL, 'string(./i:ID)', NULL, NULL, 'concat(substring(concat(./i:PaymentMeans/i:Payment/i:Details/i:ID, ''/'', ./i:PaymentMeans/i:Payment/i:Details/i:BankCode), 1 div boolean(boolean(./i:PaymentMeans/i:Payment/i:Details/i:ID) and boolean(./i:PaymentMeans/i:Payment/i:Details/i:BankCode))), substring(string(''''), 1 div not(boolean(boolean(./i:PaymentMeans/i:Payment/i:Details/i:ID) and boolean(./i:PaymentMeans/i:Payment/i:Details/i:BankCode)))))', 'string(./i:PaymentMeans/i:Payment/i:Details/i:VariableSymbol)', 'string(./i:PaymentMeans/i:Payment/i:Details/i:ConstantSymbol)', 'string(./i:IssueDate)', 'string(./i:PaymentMeans/i:Payment/i:Details/i:PaymentDueDate)', 'string(./i:TaxPointDate)', 'number(concat(substring(1, 1 div boolean(./i:VATApplicable[text() = ''true''])), substring(0, 1 div not(boolean(./i:VATApplicable[text() = ''true''])))))', NULL, NULL, NULL, 'string(/i:LocalCurrencyCode)', 'string(./i:Note)', './i:InvoiceLines/*', 'string(./i:Item/i:Description)', 'string(./i:ID)', 'string(./i:InvoicedQuantity)', 'string(./i:UnitPrice)', 'string(./i:ClassifiedTaxCategory/i:Percent)', 'utf-8', '{\"i\":\"http:\\/\\/isdoc.cz\\/namespace\\/invoice\"}', '', 2, '', ''),
	(4, 'Pohoda invoice in XML', '/dat:dataPack/dat:dataPackItem', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:company)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:name)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:street)', NULL, 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:city)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:zip)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:country/typ:ids)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:ico)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:phone)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:email)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:company)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:name)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:street)', NULL, 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:city)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:zip)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:country/typ:ids)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:ico)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:phone)', 'string(./inv:invoice/inv:invoiceHeader/inv:partnerIdentity/typ:address/typ:email)', 'string(/dat:dataPack/@ico)', 'string(./inv:invoice/inv:invoiceHeader/inv:number/typ:numberRequested)', 'string(./inv:invoice/inv:invoiceHeader/inv:invoiceType)', 'issuedInvoice', 'concat(substring(concat(./inv:invoice/inv:invoiceHeader/inv:account/typ:accountNo, ''/'', ./inv:invoice/inv:invoiceHeader/inv:account/typ:bankCode), 1 div boolean(boolean(./inv:invoice/inv:invoiceHeader/inv:account/typ:accountNo) and boolean(./inv:invoice/inv:invoiceHeader/inv:account/typ:bankCode))), substring(string(''''), 1 div not(boolean(boolean(./inv:invoice/inv:invoiceHeader/inv:account/typ:accountNo) and boolean(./inv:invoice/inv:invoiceHeader/inv:account/typ:bankCode)))))', 'string(./inv:invoice/inv:invoiceHeader/inv:symVar)', 'string(./inv:invoice/inv:invoiceHeader/inv:symConst)', 'string(./inv:invoice/inv:invoiceHeader/inv:date)', 'string(./inv:invoice/inv:invoiceHeader/inv:dateDue)', 'string(./inv:invoice/inv:invoiceHeader/inv:dateTax)', 'number(1)', 'string(./inv:invoice/inv:invoiceHeader/inv:numberOrder)', 'number(concat(substring(concat(substring(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceNone, 1 div boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceNone)), substring(0, 1 div not(boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceNone))))+concat(substring(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceLow, 1 div boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceLow)), substring(0, 1 div not(boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceLow))))+concat(substring(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceHigh, 1 div boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceHigh)), substring(0, 1 div not(boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceHigh)))),1 div boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency)),substring(concat(substring(./inv:invoice/inv:invoiceSummary/inv:foreignCurrency/typ:priceSum, 1 div boolean(./inv:invoice/inv:invoiceSummary/inv:foreignCurrency/typ:priceSum)), substring(0, 1 div not(boolean(./inv:invoice/inv:invoiceSummary/inv:foreignCurrency/typ:priceSum)))),1 div not(boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency)))))', 'number(concat(substring(concat(substring(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceNone, 1 div boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceNone)), substring(0, 1 div not(boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceNone))))+concat(substring(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceLowSum, 1 div boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceLowSum)), substring(0, 1 div not(boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceLowSum))))+concat(substring(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceHighSum, 1 div boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceHighSum)), substring(0, 1 div not(boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency/typ:priceHighSum)))),1 div boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency)),substring(concat(substring(./inv:invoice/inv:invoiceSummary/inv:foreignCurrency/typ:priceSum, 1 div boolean(./inv:invoice/inv:invoiceSummary/inv:foreignCurrency/typ:priceSum)), substring(0, 1 div not(boolean(./inv:invoice/inv:invoiceSummary/inv:foreignCurrency/typ:priceSum)))),1 div not(boolean(./inv:invoice/inv:invoiceSummary/inv:homeCurrency)))))', 'string(./inv:invoice/inv:invoiceSummary/inv:foreignCurrency/typ:currency/typ:ids)', 'string(./inv:invoice/inv:invoiceHeader/inv:note)', './inv:invoice/inv:invoiceDetail/inv:invoiceItem', 'string(./inv:text)', 'string(./inv:code)', 'string(./inv:quantity)', 'concat(substring(./inv:homeCurrency/typ:unitPrice, 1 div boolean(./inv:homeCurrency)), substring(./inv:foreignCurrency/typ:unitPrice, 1 div not(boolean(./inv:homeCurrency))))', 'string(./inv:rateVAT)', 'utf-8', '{\"inv\":\"http:\\/\\/www.stormware.cz\\/schema\\/version_2\\/invoice.xsd\",\"dat\":\"http:\\/\\/www.stormware.cz\\/schema\\/version_2\\/data.xsd\",\"typ\":\"http:\\/\\/www.stormware.cz\\/schema\\/version_2\\/type.xsd\"}', '{\"export\":{\"0\":\"none\",\"150\":\"low\",\"210\":\"high\"},\"import\":{\"none\":0,\"low\":150,\"high\":210}}', 1, '', ''),
	(5, 'Pohoda issued invoice in DBase', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '0', NULL, NULL, '', '', '', '', '', '', NULL, NULL, NULL, '', NULL, NULL, '', '', '', '', '', '', NULL, '{\"export\":{\"0\":\"none\",\"150\":\"low\",\"210\":\"high\"},\"import\":{\"none\":0,\"low\":150,\"high\":210}}', 3, '', ''),
	(6, 'Pohoda received invoice in DBase', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '1', NULL, NULL, '', '', '', '', '', '', NULL, NULL, NULL, '', NULL, NULL, '', '', '', '', '', '', NULL, '{\"export\":{\"0\":\"none\",\"150\":\"low\",\"210\":\"high\"},\"import\":{\"none\":0,\"low\":150,\"high\":210}}', 3, '', '');",

/**
 * Adds table for automatical notification message activation.
 * 
 * @author Ondřej Fibich
 * @since 1.1.0~alpha34
 */
	"CREATE TABLE `messages_automatical_activations` (
		`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`message_id` INT NOT NULL, `type` SMALLINT NOT NULL,
		`attribute` VARCHAR(255) NULL DEFAULT NULL,
		`redirection_enabled` TINYINT(1) NOT NULL DEFAULT '0',
		`email_enabled` TINYINT(1) NOT NULL DEFAULT '0',
		`sms_enabled` TINYINT(1) NOT NULL DEFAULT '0',
		FOREIGN KEY `message_id_fk` (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
	) ENGINE = InnoDB;",
	
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Messages_Controller', 'auto_config', 'Messages auto activation configuration' FROM axo",
	
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES ('38', 'Messages_Controller', 'auto_config')",
	
/**
 * Columns for bank account settings.
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.1.0~alpha35
 */
	"ALTER TABLE `bank_accounts` ADD `type` INT NOT NULL DEFAULT '0' AFTER `bank_nr`;",
	"ALTER TABLE `bank_accounts` ADD `settings` TEXT NULL COMMENT 'JSON' AFTER `type`;",

/**
 * Adds table for user favourites pages
 * 
 * @author David Raska
 * @since 1.1.0~alpha37
 */
	"CREATE TABLE `user_favourite_pages` (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`user_id` INT(30) NOT NULL,
		`title` VARCHAR(50) NOT NULL,
		`page` VARCHAR(255) NOT NULL,
		`default_page` TINYINT(1) DEFAULT NULL,
		FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
	) ENGINE = InnoDB CHARSET = utf8 COLLATE = utf8_czech_ci;",
	
/**
 * Adds table for automatical import of bank statements.
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.1.0~alpha38
 */
	"CREATE TABLE `bank_accounts_automatical_downloads` (
		`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`bank_account_id` INT NOT NULL, `type` SMALLINT NOT NULL,
		`attribute` VARCHAR(255) NULL DEFAULT NULL,
		`email_enabled` TINYINT(1) NOT NULL DEFAULT '0',
		`sms_enabled` TINYINT(1) NOT NULL DEFAULT '0',
		FOREIGN KEY `bank_account_id_fk` (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE
	) ENGINE = InnoDB;",
	
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Accounts_Controller', 'bank_account_auto_down_config', 'Bank account configuration for automatical download of statements' FROM axo;",
	
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES ('38', 'Accounts_Controller', 'bank_account_auto_down_config');",

/**
 * Adds settings column to users table
 * 
 * @author David Raska <jeffraska@gmail.com>
 * @since 1.1.0~alpha40
 */
	"ALTER TABLE `users` ADD `settings` TEXT NOT NULL ",

/**
 * Issue #679 - change whitelist_ignore to TRUE
 * 
 * @author David Raska <jeffraska@gmail.com>
 * @since 1.1.0~alpha42
 */
	"UPDATE messages SET ignore_whitelist = 1 WHERE type = ".Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE,
	
/**
 * Adds AXO for Stats controller
 * ACL records for notifications, messages and redirect controllers
 * ACL records for SMS, traffic, E-mail queues, Fees, ACL, ARO Groups,
 * Enum types, speed classes, Phone operators, filter queries and Settings controllers
 * 
 * @author David Raška
 * @since 1.1.0~alpha40, 1.1.0~alpha41, 1.1.0~alpha44, 1.1.0~alpha45, 
 *		  1.1.0~alpha46, 1.1.0~alpha49, 1.1.0~alpha50, 1.1.0~alpha51,
 *		  1.1.0~alpha53, 1.1.0~alpha54
 */
	/* AXO Sections */
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Stats_Controller', 'Stats' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Notifications_Controller', 'Notifications' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Redirect_Controller', 'Redirection' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Members_Whitelists_Controller', 'Members Whitelists' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Sms_Controller', 'SMS' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Email_queues_Controller', 'Email queues' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Fees_Controller', 'Fees' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Acl_Controller', 'ACL' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Aro_groups_Controller', 'ARO Groups' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Enum_types_Controller', 'Enumeration types' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Phone_operators_Controller', 'Phone oprators' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Filter_queries_Controller', 'Filter queries' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Export_Controller', 'Export' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Device_templates_Controller', 'Device templates' FROM axo_sections;",
	"INSERT INTO `axo_sections` (`id`, `value`, `name`) SELECT MAX(id)+1, 'Speed_classes_Controller', 'Speed classes' FROM axo_sections;",
	/* AXOs */
	// Stats AXO
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Stats_Controller', 'members_increase_decrease', 'Members increase decrease' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Stats_Controller', 'members_growth', 'Members growth' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Stats_Controller', 'incoming_member_payment', 'Incoming member payment' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Stats_Controller', 'members_fees', 'Members fees' FROM axo;",
	// Device topology
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Devices_Controller', 'topology', 'Topology of device' FROM axo",
	// Notification Controller AXO
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Notifications_Controller', 'member', 'Member notification' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Notifications_Controller', 'members', 'Members notification' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Notifications_Controller', 'subnet', 'Subnet notification' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Notifications_Controller', 'cloud', 'Cloud notification' FROM axo;",
	// Messages Controller AXO
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Messages_Controller', 'activate', 'Activate message' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Messages_Controller', 'deactivate', 'Deactivate message' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Messages_Controller', 'preview', 'Preview message' FROM axo;",
	// Redirect Controller AXO
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Redirect_Controller', 'redirect', 'Redirection' FROM axo;",
	// Members whitelists Controller AXO
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Members_whitelists_Controller', 'whitelist', 'Whitelist' FROM axo;",
	// SMS Controller AXO
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Sms_Controller', 'sms', 'SMS' FROM axo;",
	// Traffic AXO
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Ulogd_Controller', 'total', 'Total' FROM axo;",
	// Email queue AXO
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Email_queues_Controller', 'email_queue', 'E-mail queue' FROM axo;",
	// Device templates AXO
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Device_templates_Controller', 'device_template', 'Device template' FROM axo;",
	// Fees
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Fees_Controller', 'fees', 'Fees' FROM axo;",
	// ACL
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Acl_Controller', 'acl', 'ACL' FROM axo;",
	// ARO Groups
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Aro_groups_Controller', 'aro_group', 'ARO Group' FROM axo;",
	// Enum types
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Enum_types_Controller', 'enum_types', 'Enumeration types' FROM axo;",
	// Speed classes
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Speed_classes_Controller', 'speed_classes', 'Speed classes' FROM axo;",
	// Phone operators
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Phone_operators_Controller', 'phone_operators', 'Phone operators' FROM axo;",	
	// Filter queries
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Filter_queries_Controller', 'filter_queries', 'Filter queries' FROM axo;",
	// Settings
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'info', 'Info' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'system_settings', 'System settings' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'users_settings', 'Users settings' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'finance_settings', 'Finance' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'approval_settings', 'Approval' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'networks_settings', 'Networks' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'email_settings', 'E-mail' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'sms_settings', 'SMS' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'voip_settings', 'VoIP' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'notification_settings', 'Notification' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'qos_settings', 'QoS' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'monitoring_settings', 'Monitoring' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'vtiger_settings', 'Vtiger' FROM axo;",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Settings_Controller', 'logging_settings', 'Logging' FROM axo;",
	// Last user's additional contact delete
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Users_Controller', 'additional_contacts_admin_delete', 'Additional contacts administrator delete' FROM axo;",
	// Export AXO
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Export_Controller', 'all_tables', 'Export all tables to csv' FROM axo;",
	// AXO for DHCP, QoS and DNS settings of subnet
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Subnets_Controller', 'dhcp', 'DHCP server on subnet' FROM axo",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Subnets_Controller', 'qos', 'QoS on subnet' FROM axo",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Subnets_Controller', 'dns', 'DNS server on subnet' FROM axo",
	// VAT
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Members_Controller', 'vat_organization_identifier', 'VAT organization identifier' FROM axo",
	// Device active links
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Device_active_links_Controller', 'active_links', 'Device active links' FROM axo",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Device_active_links_Controller', 'display_device_active_links', 'Display device active links' FROM axo",
	// Device admin notifications
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Notifications_Controller', 'device', 'Device admins notification' FROM axo",
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Notifications_Controller', 'devices', 'Devices admins notification' FROM axo",
	// Ports and VLANs settings of device
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`) SELECT MAX(id)+1, 'Devices_Controller', 'ports_vlans_settings', 'Ports and VLANs settings of device' FROM axo",
	/* AXO MAPs */
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES 
		('38', 'Stats_Controller', 'members_increase_decrease'),
		('38', 'Stats_Controller', 'members_growth'),
		('38', 'Stats_Controller', 'incoming_member_payment'),
		('38', 'Stats_Controller', 'members_fees'),
		('38', 'Devices_Controller', 'topology'),
		('38', 'Notifications_Controller', 'cloud'),
		('38', 'Notifications_Controller', 'member'),
		('38', 'Notifications_Controller', 'members'),
		('38', 'Notifications_Controller', 'subnet'),
		('38', 'Messages_Controller', 'activate'),
		('38', 'Messages_Controller', 'deactivate'),
		('38', 'Messages_Controller', 'preview'),
		('38', 'Redirect_Controller', 'redirect'),
		('38', 'Members_whitelists_Controller', 'whitelist'),
		('38', 'Sms_Controller', 'sms'),
		('38', 'Ulogd_Controller', 'total'),
		('38', 'Email_queues_Controller', 'email_queue'),
		('38', 'Device_templates_Controller', 'device_template'),
		('38', 'Fees_Controller', 'fees'),
		('38', 'Acl_Controller', 'acl'),
		('38', 'Aro_groups_Controller', 'aro_group'),
		('38', 'Enum_types_Controller', 'enum_types'),
		('38', 'Speed_classes_Controller', 'speed_classes'),
		('38', 'Phone_operators_Controller', 'phone_operators'),
		('38', 'Filter_queries_Controller', 'filter_queries'),
		('38', 'Settings_Controller', 'info'),
		('38', 'Settings_Controller', 'system_settings'),
		('38', 'Settings_Controller', 'users_settings'),
		('38', 'Settings_Controller', 'finance_settings'),
		('38', 'Settings_Controller', 'approval_settings'),
		('38', 'Settings_Controller', 'networks_settings'),
		('38', 'Settings_Controller', 'email_settings'),
		('38', 'Settings_Controller', 'sms_settings'),
		('38', 'Settings_Controller', 'voip_settings'),
		('38', 'Settings_Controller', 'notification_settings'),
		('38', 'Settings_Controller', 'qos_settings'),
		('38', 'Settings_Controller', 'monitoring_settings'),
		('38', 'Settings_Controller', 'vtiger_settings'),
		('38', 'Settings_Controller', 'logging_settings'),
		('38', 'Users_Controller', 'additional_contacts_admin_delete'),
		('38', 'Export_Controller', 'all_tables'),
		('38', 'Subnets_Controller', 'dhcp'),
		('38', 'Subnets_Controller', 'qos'),
		('38', 'Subnets_Controller', 'dns'),
		('38', 'Members_Controller', 'vat_organization_identifier'),
		('38', 'Device_active_links_Controller', 'active_links'),
		('38', 'Device_active_links_Controller', 'display_device_active_links'),
		('38', 'Notifications_Controller', 'device'),
		('38', 'Notifications_Controller', 'devices'),
		('38', 'Devices_Controller', 'ports_vlans_settings');",
/**
 * Adds AXO for DHCP, QoS and DNS settings of subnet
 * 
 * @author Michal Kliment
 * @since 1.1.0~alpha46
 */
	"ALTER TABLE subnets ADD dns TINYINT(1) NOT NULL DEFAULT '0' AFTER  dhcp_expired",
	
/**
 * Changes type of variable_symbol and specific_symbol column in phone_invoices
 * table to BIGINT
 * 
 * @author David Raška
 * @since 1.1.0~alpha48
 */
	"ALTER TABLE `phone_invoices` CHANGE `variable_symbol` `variable_symbol` BIGINT( 11 ) NOT NULL",
	"ALTER TABLE `phone_invoices` CHANGE `specific_symbol` `specific_symbol` BIGINT( 11 ) NOT NULL",
	
/**
 * Adds support for VAT organization identifier
 * 
 * @author Michal Kliment
 * @since 1.1.0~alpha49
 */
	"ALTER TABLE  `members` ADD  `vat_organization_identifier` VARCHAR( 30 ) NULL DEFAULT NULL AFTER  `organization_identifier`",	
	"ALTER TABLE  `invoices` ADD  `vat_organization_identifier` VARCHAR( 30 ) NULL DEFAULT NULL AFTER  `organization_identifier`",	
	"ALTER TABLE  `invoice_templates` ADD  `sup_vat_organization_identifier` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `sup_organization_identifier`",
	
/**
 * Removes unused AXO, adds missing AXO
 * 
 * @author David Raška
 * @since 1.1.0~alpha50
 */
	// ACO
	"DELETE FROM aco WHERE value = 'confirm_all' OR value = 'confirm_own' OR value = 'write_email'",
	// ACO_MAP
	"DELETE FROM aco_map WHERE value = 'write_email' OR value = 'confirm_own'",
	// AXO_SECTIONS
	"DELETE FROM axo_sections WHERE value = 'Registration_Controller' OR value = 'Votes_Controller'",
	// AXO
	"DELETE FROM axo WHERE (section_value = 'Registration_Controller' AND value = 'name') OR
		(section_value = 'Registration_Controller' AND value = 'surname') OR
		(section_value = 'Registration_Controller' AND value = 'street') OR
		(section_value = 'Users_Controller' AND value = 'name') OR
		(section_value = 'Users_Controller' AND value = 'surname') OR
		(section_value = 'Users_Controller' AND value = 'phone') OR
		(section_value = 'Users_Controller' AND value = 'email') OR
		(section_value = 'Members_Controller' AND value = 'en_fee_left') OR
		(section_value = 'Settings_Controller' AND value = 'system') OR
		(section_value = 'Settings_Controller' AND value = 'fees') OR
		(section_value = 'Settings_Controller' AND value = 'enum_types') OR
		(section_value = 'Members_Controller' AND value = 'var_sym') OR
		(section_value = 'Members_Controller' AND value = 'redirect') OR
		(section_value = 'Redirection_Controller' AND value = 'redirection') OR
		(section_value = 'Backup_Controller' AND value = 'backup') OR
		(section_value = 'Messages_Controller' AND value = 'ip_address') OR
		(section_value = 'Messages_Controller' AND value = 'subnet') OR
		(section_value = 'Messages_Controller' AND value = 'subnet_enabled') OR
		(section_value = 'Votes_Controller' AND value = 'work') OR
		(section_value = 'Settings_Controller' AND value = 'access_rights')",
	// AXO_MAP
	"DELETE FROM axo_map WHERE (section_value = 'Accounts_Controller' AND value = 'unidentifed_transfers') OR
		(section_value = 'Backup_Controller' AND value = 'backup') OR
		(section_value = 'Members_Controller' AND value = 'en_fee_left') OR
		(section_value = 'Members_Controller' AND value = 'redirect') OR
		(section_value = 'Members_Controller' AND value = 'var_sym') OR
		(section_value = 'Messages_Controller' AND value = 'ip_address') OR
		(section_value = 'Messages_Controller' AND value = 'subnet') OR
		(section_value = 'Messages_Controller' AND value = 'subnet_enabled') OR
		(section_value = 'Redirection_Controller' AND value = 'redirection') OR
		(section_value = 'Settings_Controller' AND value = 'access_rights') OR
		(section_value = 'Settings_Controller' AND value = 'economy') OR
		(section_value = 'Settings_Controller' AND value = 'enum_types') OR
		(section_value = 'Settings_Controller' AND value = 'fees') OR
		(section_value = 'Settings_Controller' AND value = 'system') OR
		(section_value = 'Users_Controller' AND value = 'email') OR
		(section_value = 'Users_Controller' AND value = 'name') OR
		(section_value = 'Users_Controller' AND value = 'phone') OR
		(section_value = 'Users_Controller' AND value = 'surname') OR
		(section_value = 'Votes_Controller' AND value = 'work')",
	
	// ADD missing AXO
	"INSERT INTO axo SELECT MAX(id)+1, 'Devices_Controller', 'login', 'Device login name' FROM axo",
	"INSERT INTO axo SELECT MAX(id)+1, 'Devices_Controller', 'password', 'Device password' FROM axo",
	"INSERT INTO axo SELECT MAX(id)+1, 'Phone_invoices_Controller', 'user_invoices', 'User phone invoices' FROM axo",
	"INSERT INTO axo SELECT MAX(id)+1, 'Devices_Controller', 'main_engineer', 'Main engineer' FROM axo",
	"INSERT INTO axo_map (acl_id, section_value, value) VALUES ('38', 'Devices_Controller', 'main_engineer');",
	
/**
 * Adds tables for device active links
 * 
 * @author David Raška
 * @since 1.1.0~alpha51
 */
	"CREATE TABLE IF NOT EXISTS `device_active_links` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`url_pattern` varchar(255) COLLATE utf8_czech_ci NOT NULL,
		`name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
		`title` varchar(50) COLLATE utf8_czech_ci NOT NULL,
		`show_in_user_grid` tinyint(4) NOT NULL,
		`show_in_grid` tinyint(4) NOT NULL,
		`as_form` TINYINT NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;",
	
	"CREATE TABLE IF NOT EXISTS `device_active_links_map` (
		`device_active_link_id` int(11) NOT NULL,
		`device_id` int(11) NOT NULL,
		`type` smallint(6) NOT NULL,
		KEY (`device_active_link_id`,`device_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;",
	
	"ALTER TABLE `device_active_links_map`
		ADD CONSTRAINT `device_active_links_map_ibfk_1` FOREIGN KEY (`device_active_link_id`) REFERENCES `device_active_links` (`id`) ON DELETE CASCADE;",
	
/**
 * Adds end_after_interrupt_end column to membership_interrupt and
 * removes unused bank_templates table
 * 
 * @author David Raška
 * @since 1.1.0~alpha52
 */
	"ALTER TABLE `membership_interrupts` ADD `end_after_interrupt_end` SMALLINT NOT NULL;",
	"DROP TABLE `bank_templates`;",
	
/**
 * Adds as_form column to device_active_links table
 * Adds display_device_active_links permission to engineers
 * 
 * @author David Raška
 * @since 1.1.0~alpha55
 */
	"INSERT INTO acl SELECT MAX(id)+1, 'Technici mohou zobrazit aktivni odkazy zarizeni' FROM acl",
	"INSERT INTO aco_map SELECT MAX(id), 'view_all' FROM acl",
	"INSERT INTO aro_groups_map SELECT MAX(id), '26' FROM acl",
	"INSERT INTO axo_map SELECT MAX(id), 'Device_active_links_Controller', 'display_device_active_links' FROM acl",

/**
 * Adds enabled column to countries
 * 
 * @author David Raška
 * @since 1.1.0~alpha56
 */
	"ALTER TABLE `countries` ADD `enabled` TINYINT NOT NULL DEFAULT '1';",
	
/**
 * Adds registration export AXO for administrators, enginners, regular members and applicants
 * Adds members AXO for applicants
 * 
 * @author David Raška
 * @since 1.1.0~alpha57
 */
	
	"INSERT INTO axo SELECT MAX(id)+1, 'Members_Controller', 'registration_export', 'Export of registration' FROM axo",
	"INSERT INTO axo_map (acl_id, section_value, value) VALUES ('38', 'Members_Controller', 'registration_export');",
	"INSERT INTO acl SELECT MAX(id)+1, 'Regular members and applicants can export own registration' FROM acl",
	"INSERT INTO aco_map SELECT MAX(id), 'view_own' FROM acl",
	"INSERT INTO aro_groups_map SELECT MAX(id), '22' FROM acl",
	"INSERT INTO aro_groups_map SELECT MAX(id), '23' FROM acl",
	"INSERT INTO axo_map SELECT MAX(id), 'Members_Controller', 'registration_export' FROM acl",
	"INSERT INTO acl SELECT MAX(id)+1, 'Engineers can export all registrations' FROM acl",
	"INSERT INTO aco_map SELECT MAX(id), 'view_all' FROM acl",
	"INSERT INTO aro_groups_map SELECT MAX(id), '26' FROM acl",
	"INSERT INTO axo_map SELECT MAX(id), 'Members_Controller', 'registration_export' FROM acl",
	"INSERT INTO acl SELECT MAX(id)+1, 'Applicants can show own profile' FROM acl",
	"INSERT INTO aco_map SELECT MAX(id), 'view_own' FROM acl",
	"INSERT INTO aro_groups_map SELECT MAX(id), '23' FROM acl",
	"INSERT INTO axo_map SELECT MAX(id), 'Members_Controller', 'members' FROM acl",
	
/**
 * Adds premission for engineers to manage connection requests.
 * 
 * @author Ondřej Fibich
 * @see #779
 * @since 1.1.0~alpha58
 */
	// ACL
	"INSERT INTO acl SELECT MAX(id)+1, 'Engineers can manage connection requests' FROM acl",
	// ACO
	"INSERT INTO aco_map SELECT MAX(id), 'view_all' FROM acl",
	"INSERT INTO aco_map SELECT MAX(id), 'edit_all' FROM acl",
	"INSERT INTO aco_map SELECT MAX(id), 'new_all' FROM acl",
	"INSERT INTO aco_map SELECT MAX(id), 'delete_all' FROM acl",
	// ARO
	"INSERT INTO aro_groups_map SELECT MAX(id), '26' FROM acl",
	// AXO
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`)
	 SELECT MAX(id), 'Connection_Requests_Controller', 'request' FROM acl",
	
/**
 * Adds column and AXO for member notification settings.
 * 
 * @author Ondřej Fibich
 * @since 1.1.0~alpha59
 */
	// columns
	"ALTER TABLE  `members` ADD  `notification_by_redirection` TINYINT NOT NULL DEFAULT  '1',
		ADD `notification_by_email` TINYINT NOT NULL DEFAULT '1',
		ADD `notification_by_sms` TINYINT NOT NULL DEFAULT '1'",
	// disable notification for association
	"UPDATE `members` SET notification_by_redirection = 0 AND
			notification_by_email = 0 AND notification_by_sms = 0
		WHERE id = 1",
	// AXO
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`)
		SELECT MAX(id)+1, 'Members_Controller', 'notification_settings', 'Member notification settings' FROM axo",
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES
		('38', 'Members_Controller', 'notification_settings')",
	
/**
 * Adds type attribute to requests and one_vote to approval types.
 * 
 * @author Ondrej Fibich
 * @since 1.1.0~beta2
 */
	// add type column
	"ALTER TABLE  `requests` ADD `type` SMALLINT NOT NULL DEFAULT '0' AFTER `approval_template_id`",
	
	// add one_vote column
	"ALTER TABLE  `approval_types` ADD `one_vote` TINYINT NOT NULL DEFAULT '0'
	 COMMENT 'Only one vote (approve/reject) required to end voting.' AFTER `min_suggest_amount`",
	
); // end of $upgrade_sql['1.1.0']

/**
 * PHP function for transforming some data that cannot be transform using SQL
 * statements. Contains following changes:
 * 
 * <ul>
 * <li>Transform old QoS data to speed classes and remove old columns (1.1.0~alpha12)
 * <li>Add new default approval template for support requests. (1.1.0~beta2)
 * </ul>
 * 
 * @return boolean
 */
function upgrade_1_1_0_after()
{
	/* Transform old QoS data to speed classes and remove old columns (1.1.0~alpha12) */
	
	$db = Database::instance();
	$member_model = new Member_Model();
	$speed_classes = array();
	
	try
	{
		$member_model->transaction_start();

		// transform old data
		$members = $member_model->find_all();
		$speed_class = new Speed_class_Model();

		foreach ($members as $member)
		{
			if (valid::speed_size($member->qos_ceil) &&
				valid::speed_size($member->qos_rate))
			{
				$d_ceil = $u_ceil = $member->qos_ceil;
				$d_rate = $u_rate = $member->qos_rate;

				if (strpos($member->qos_ceil, '/'))
				{
					$u_ceil = substr($u_ceil, 0, strpos($u_ceil, '/'));
					$d_ceil = substr($d_ceil, strpos($d_ceil, '/') + 1);
				}

				if (strpos($member->qos_rate, '/'))
				{
					$u_rate = substr($u_rate, 0, strpos($u_rate, '/'));
					$d_rate = substr($d_rate, strpos($d_rate, '/') + 1);
				}
				
				$bu_ceil = network::str2bytes($u_ceil);
				$bd_ceil = network::str2bytes($d_ceil);
				$bu_rate = network::str2bytes($u_rate);
				$bd_rate = network::str2bytes($d_rate);
				$index = $bu_ceil . '_' - $bd_ceil . '_' . $bu_rate . '_' . $bd_rate;

				if (!isset($speed_classes[$index]))
				{
					$speed_class->clear();
					$speed_class->name = __('Speed class') . ' '
							. network::speed($bd_ceil) . '/'
							. network::speed($bu_ceil) . ' - '
							. network::speed($bd_rate) . '_'
							. network::speed($bu_rate);
					$speed_class->d_rate = $bd_rate;
					$speed_class->d_ceil = $bd_ceil;
					$speed_class->u_rate = $bu_rate;
					$speed_class->u_ceil = $bu_ceil;
					$speed_class->applicant_default = FALSE;
					$speed_class->regular_member_default = FALSE;
					$speed_class->save_throwable();
					
					$speed_classes[$index] = $speed_class->id;
				}
				
				$member->speed_class_id = $speed_classes[$index];
			}
			
			$member->save_throwable();
		}

		// delete old columns
		$db->query("ALTER TABLE `members` DROP `qos_ceil`, DROP `qos_rate`");

		$member_model->transaction_commit();
	}
	catch (Exception $e)
	{
		$member_model->transaction_rollback();
		throw $e;
	}
	
	/* Add new default approval template for support requests. (1.1.0~beta2) */
	try
	{
		$atemp = new Approval_template_Model();
		$atemp->transaction_start();
		
		// create new template
		$atemp->name = 'Požadavek na podporu';
		$atemp->comment = 'Výchozí šablona pro požadavky na podporu';
		$atemp->state = 0;
		$atemp->save_throwable();
		// add aproval type
		$atype = new Approval_type_Model(); 
		$atype->name = 'Podpora';
		$atype->comment = 'Výchozí hlasovací typ pro poskytování podpory (stačí aby hlasoval pouze jeden oprávněný)';
		$atype->type = Approval_type_Model::SIMPLE;
		$atype->majority_percent = 51;
		$atype->aro_group_id = Aro_group_Model::ADMINS;
		$atype->one_vote = TRUE;
		$atype->save_throwable();
		// add relation between type and template
		$rel = new Approval_template_item_Model();
		$rel->approval_template_id = $atemp->id;
		$rel->approval_type_id = $atype->id;
		$rel->priority = 0;
		$rel->save_throwable();
		// set as default for support requests
		Settings::set('default_request_support_approval_template', $atemp->id);
		
		$atemp->transaction_commit();
	}
	catch (Exception $e)
	{
		$member_model->transaction_rollback();
		throw $e;
	}
	
	// done
	return TRUE;
}
