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
 * Adds tables for API accounts and API accounts audit logging service
 * and ACL for their managing.
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
$upgrade_sql['1.2.0~alpha6'] = array
(
	"CREATE TABLE api_accounts (
		id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		username VARCHAR(50) NOT NULL,
		token CHAR(32) NOT NULL,
		enabled TINYINT NOT NULL DEFAULT '1',
		readonly TINYINT NOT NULL DEFAULT '0',
		allowed_paths TEXT NOT NULL,
		UNIQUE username_uk(username)
	) ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_czech_ci;",
	
	"CREATE TABLE api_account_logs (
		id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        api_account_id INT(11) NOT NULL,
		type INT NOT NULL,
		date DATETIME NOT NULL,
		description TEXT NULL,
		responsible_user_id INT(11) NULL,
        INDEX api_account_id_idx(api_account_id),
        FOREIGN KEY api_account_id_fk(api_account_id)
            REFERENCES api_accounts(id) ON DELETE CASCADE,
		INDEX type_idx(type),
        INDEX date_idx(date),
        INDEX responsible_user_id_idx(responsible_user_id),
        FOREIGN KEY responsible_user_id_fk(responsible_user_id)
            REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_czech_ci;",
    
	// ACLs
	
	"INSERT INTO axo_sections (id, value, name)
		SELECT MAX(id)+1, 'Api_Controller', 'API management' FROM axo_sections;",
	
	"INSERT INTO axo (id, section_value, value, name)
		SELECT MAX(id)+1, 'Api_Controller', 'account', 'API account management'
		FROM axo;",
	"INSERT INTO axo (id, section_value, value, name)
		SELECT MAX(id)+1, 'Api_Controller', 'account_token',
			'API account token management'
		FROM axo;",
	
	"INSERT INTO axo (id, section_value, value, name)
		SELECT MAX(id)+1, 'Api_Controller', 'account_log', 'API account logs'
		FROM axo;",
	
	"INSERT INTO axo_map (acl_id, section_value, value) VALUES
		(38, 'Api_Controller', 'account'),
		(38, 'Api_Controller', 'account_token'),
		(38, 'Api_Controller', 'account_log');"
	
); // end of $upgrade_sql['1.2.0~alpha6']