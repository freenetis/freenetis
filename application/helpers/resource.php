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
 * Cookie resource class.
 *
 * @author  Bonerek
 * @package Helper
 */
class resource
{

	public static function voip($state)
	{
		switch ($state)
		{
			case 'originating':
				return url::base() . 'media/images/icons/voip-originating.png';
			case 'terminating':
				return url::base() . 'media/images/icons/voip-terminating.png';
			default;
				return '';
		}
	}

	public static function state($state)
	{
		switch ($state)
		{
			case 'active':
				return url::base() . 'media/images/states/active.png';
			case 'inactive':
				return url::base() . 'media/images/states/inactive.png';
			case 'locked':
				return url::base() . 'media/images/states/locked.png';
			default;
				return '';
		}
	}

	public static function flag($state)
	{
		switch ($state)
		{
			case 'cs':
				return url::base() . 'media/images/icons/flags/cs.jpg';
			default;
				return '';
		}
	}

	public static function sms($state)
	{
		switch ($state)
		{
			case 'send':
			case 'send-small':
				return url::base() . 'media/images/icons/sms_send-small.png';
			case 'receive':
			case 'receive-small':
				return url::base() . 'media/images/icons/sms_receive-small.png';
			default;
				return '';
		}
	}

	public static function help($state)
	{
		switch ($state)
		{
			case 'cyrcle':
				return url::base() . 'media/images/icons/help.png';
			default;
				return '';
		}
	}

}

// End resource