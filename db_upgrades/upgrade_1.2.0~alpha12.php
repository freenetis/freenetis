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
 * Add gain and azimuth for wireless ifaces
 *
 * @author Michal Kliment
 */
$upgrade_sql['1.2.0~alpha12'] = array
(
	"ALTER TABLE `ifaces`
	ADD `wireless_antenna_gain` INT(11) NULL DEFAULT NULL AFTER `wireless_antenna`,
	ADD `wireless_antenna_azimuth` INT(11) NULL DEFAULT NULL AFTER `wireless_antenna_gain`;"
);
