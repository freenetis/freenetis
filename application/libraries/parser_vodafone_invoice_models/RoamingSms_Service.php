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

require_once "Service.php";

/**
 * Služba pro roamingové služby - sms
 *
 * @author Ondřej Fibich
 *
 * @property string $roaming_zone
 */
class RoamingSms_Service extends Service
{

    /**
     * Roamingová zóna
     * @var string
     */
    protected $roaming_zone;

}
