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
$upgrade_sql['1.1.26-pvfree'] = array
(
"CREATE TABLE `ip6_addresses` (
  `iface_id` int(11) DEFAULT NULL,
  `subnet_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8_czech_ci DEFAULT NULL,
  `dhcp` tinyint(4) DEFAULT NULL,
  `gateway` tinyint(4) DEFAULT NULL,
  `service` tinyint(4) NOT NULL DEFAULT 0,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;",

"ALTER TABLE `ip6_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `ip_addresses_key_iface_id` (`iface_id`),
  ADD KEY `ip_addresses_key_subnet_id` (`subnet_id`),
  ADD KEY `ip_address` (`ip_address`);",



"ALTER TABLE `ip6_addresses`
 MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;",



);
