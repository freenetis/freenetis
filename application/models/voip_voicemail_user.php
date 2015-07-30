<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is release under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * VoIP voicemail of VoIP SIP
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $customer_id
 * @property integer $active
 * @property string $context
 * @property string $mailbox
 * @property string $password
 * @property string $fullname
 * @property string $email
 * @property string $pager
 * @property datetime $stamp
 */
class Voip_voicemail_user_Model extends ORM
{
}
