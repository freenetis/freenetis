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
 * Helper for some useful methods that are frequently used during notification.
 * 
 * @author  Ondrej Fibich
 * @package Helper
 */
class notification
{
	/**
	 * Build stats string for activated notification. 
	 * 
	 * @param array $stats Stats of count of added (keys redirection, email, sms)
	 * @param boolean $activate_redir
	 * @param boolean $activate_email
	 * @param boolean $activate_sms
	 * @param boolean $remove_redir
	 * @return array Translated stat messages
	 */
	public static function build_stats_string($stats, $activate_redir = TRUE,
			$activate_email = TRUE, $activate_sms = TRUE, $remove_redir = TRUE)
	{
		$info_messages = array();
		
		if ($remove_redir)
		{
			$m = 'Redirection has been deactivated for %s IP addresses';
			$info_messages[] = __($m, $stats['redirection_removed']).'.';
		}
		
		if ($activate_redir)
		{
			$m = 'Redirection has been activated for %s IP addresses';
			$info_messages[] = __($m, $stats['redirection']).'.';
		}
		
		if ($activate_email)
		{
			$m = 'E-mail has been sent for %s e-mail addresses';
			$info_messages[] = __($m, $stats['email']).'.';
		}
		
		if ($activate_sms)
		{
			$m = 'SMS message has been sent for %d phone numbers';
			$info_messages[] = __($m, $stats['sms']).'.';
		}
		
		return $info_messages;
	}
	
	/**
	 * Return array for form used as cell in notification forms.
	 * 
	 * @param boolean $notification
	 * @return array
	 */
	public static function redirection_form_array($notification = FALSE)
	{
		$array = array();
		
		if (!$notification)
		{
			$array = array
			(
				Notifications_Controller::DEACTIVATE	=> __('Deactivate')
			);
		}
		
		return array
		(
			Notifications_Controller::ACTIVATE			=> __('Activate'),
			Notifications_Controller::KEEP				=> __('Without change'),
		) + $array;
	}
}
