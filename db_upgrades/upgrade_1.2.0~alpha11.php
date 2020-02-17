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
 * Delete of voip and voip sips and all uses of these functions
 *
 * @author Filip Miskarik
 */
$upgrade_sql['1.2.0~alpha11'] = array
(
	/* Drop VoIP tables */
	"DROP TABLE IF EXISTS voip_sips;",
	"DROP TABLE IF EXISTS voip_voicemail_users;",
	/* VoIP config fields */
	"DELETE FROM config WHERE name LIKE 'voip_%';",
	/* Drop member table VoIP columns */
	"ALTER TABLE members DROP voip_billing_limit, DROP voip_billing_type;",
	/* Delete AXO and all related access rights */
	"DELETE FROM axo WHERE section_value = 'VoIP_Controller';",
	"DELETE FROM axo_map WHERE section_value = 'VoIP_Controller';",
	"DELETE FROM axo_sections WHERE value = 'VoIP_Controller';",
);
