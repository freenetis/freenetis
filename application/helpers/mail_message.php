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
 * Helper for formating mail messages
 *
 * @see Mail_messages_Controller
 * @author Ondřej Fibich
 * @package Helper
 */
class mail_message
{
	/**
	 * Prefix at the start of formated message
	 */
	const FORMAT_PREFFIX = '#####';
	const FORMAT_DELIMITER = '#';

	/**
	 * Format message to format
	 * <prefix>message<delimiter>arrg1<delimiter>arg2..<delimiter>argn
	 *
	 * @param string	   $message Message index in /i18n/<locale>/mail.php
	 * @param array|string $arrgs   Messages argumets
	 * @return string		    Formated message or NULL
	 */
	public static function format($message, $arrgs = NULL)
	{
		if (empty($message))
			return NULL;

		$formate_message = self::FORMAT_PREFFIX . $message;

		if (is_string($arrgs))
		{
			$formate_message .= self::FORMAT_DELIMITER . $arrgs;
		}
		else if (is_array($arrgs))
		{
			foreach ($arrgs as $arg)
				$formate_message .= self::FORMAT_DELIMITER . $arg;
		}

		return $formate_message;
	}

	/**
	 * Check if message is formated
	 * @param string $message Message index in /i18n/<locale>/mail.php
	 * @return boolean
	 */
	public static function is_formated($message)
	{
		return strncmp(
				$message, self::FORMAT_PREFFIX,
				mb_strlen(self::FORMAT_PREFFIX)
		) == 0;
	}

	/**
	 * Get internationalised message from formated message
	 * @param string $formated_message  Formated message
	 * @return string			Internationalised message
	 */
	public static function printf($formated_message)
	{
		if (!self::is_formated($formated_message))
			return '';

		$formated_message = substr(
				$formated_message, mb_strlen(self::FORMAT_PREFFIX)
		);
		
		$mes_arg = explode(self::FORMAT_DELIMITER, $formated_message);
		$message = array_shift($mes_arg);

		return url_lang::lang('mail.' . $message, $mes_arg);
	}

}
