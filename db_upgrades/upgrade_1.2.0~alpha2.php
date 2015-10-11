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
 * This upgrade adds DB tables for DNS servers management and AXO for administrators
 * to access DNS servers management
 * 
 * @author David Raška <jeffraska@gmail.com>
 */
$upgrade_sql['1.2.0~alpha2'] = array
(
	"CREATE TABLE IF NOT EXISTS `dns_records` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`dns_zone_id` int(11) NOT NULL,
		`name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
		`ttl` varchar(30) COLLATE utf8_czech_ci NOT NULL,
		`type` varchar(10) COLLATE utf8_czech_ci NOT NULL,
		`value` text COLLATE utf8_czech_ci NOT NULL,
		`param` varchar(30) COLLATE utf8_czech_ci NOT NULL,
		PRIMARY KEY (`id`),
		KEY `zone_id` (`dns_zone_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;",
	
	"CREATE TABLE IF NOT EXISTS `dns_zones` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`zone` varchar(255) COLLATE utf8_czech_ci NOT NULL,
		`fqdn` varchar(255) COLLATE utf8_czech_ci NOT NULL,
		`ttl` varchar(30) COLLATE utf8_czech_ci NOT NULL,
		`nameserver` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
		`email` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
		`sn` varchar(12) COLLATE utf8_czech_ci NOT NULL,
		`refresh` varchar(30) COLLATE utf8_czech_ci NOT NULL,
		`retry` varchar(30) COLLATE utf8_czech_ci NOT NULL,
		`expire` varchar(30) COLLATE utf8_czech_ci NOT NULL,
		`nx` varchar(30) COLLATE utf8_czech_ci NOT NULL,
		`ip_address_id` int(11) NOT NULL,
		`access_time` datetime DEFAULT NULL,
		PRIMARY KEY (`id`),
		KEY `nameserver` (`nameserver`),
		KEY `ip_address_id` (`ip_address_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;",
	
	"CREATE TABLE IF NOT EXISTS `dns_zones_map` (
		`dns_zone_id` int(11) NOT NULL,
		`ip_address_id` int(11) NOT NULL,
		KEY `dns_zone_id` (`dns_zone_id`),
		KEY `ip_address_id` (`ip_address_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;",
	
	"ALTER TABLE `dns_records`
		ADD CONSTRAINT `dns_records_ibfk_1` FOREIGN KEY (`dns_zone_id`) REFERENCES `dns_zones` (`id`) ON DELETE CASCADE;",
	
	"ALTER TABLE `dns_zones`
		ADD CONSTRAINT `dns_zones_ibfk_1` FOREIGN KEY (`ip_address_id`) REFERENCES `ip_addresses` (`id`);",
	
	"ALTER TABLE `dns_zones_map`
		ADD CONSTRAINT `dns_zones_map_ibfk_2` FOREIGN KEY (`ip_address_id`) REFERENCES `ip_addresses` (`id`),
		ADD CONSTRAINT `dns_zones_map_ibfk_3` FOREIGN KEY (`dns_zone_id`) REFERENCES `dns_zones` (`id`) ON DELETE CASCADE;",
	
	"ALTER TABLE `ip_addresses` ADD `dns` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `service` ,
		ADD INDEX ( `dns` );",
	
	"INSERT INTO `axo_sections` (`id`, `value`, `name`)
		SELECT MAX(`id`)+1, 'Dns_Controller', 'Manages DNS zones' FROM `axo_sections`;",
	
	"INSERT INTO `axo` (`id`, `section_value`, `value`, `name`)
		SELECT MAX(`id`)+1, 'Dns_Controller', 'zone', 'DNS zone' FROM `axo`;",
	
	"INSERT INTO `axo_map` (`acl_id`, `section_value`, `value`) VALUES
		(38, 'Dns_Controller', 'zone');"
); // end of $upgrade_sql['1.2.0~alpha2']