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
 * Helper for creating queue of status flash messages.
 * Messages are stored into session var.
 * 
 * @author  Ondřej Fibich
 * @package Helper
 */
class status
{
	/** Status message type success */
	const TYPE_SUCCESS  = 0;
	/** Status message type warning */
	const TYPE_WARNING  = 1;
	/** Status message type error */
	const TYPE_ERROR    = 2;
	/** Status message type info */
	const TYPE_INFO     = 3;
	/** Session var name */
	const SESSION_VAR_NAME	= 'status_message';
	
	/**
	 * CSS classes for message types
	 *
	 * @var array
	 */
	private static $css_classes = array
	(
		self::TYPE_SUCCESS	=> 'status_message_success',
		self::TYPE_WARNING	=> 'status_message_warning',
		self::TYPE_ERROR	=> 'status_message_error',
		self::TYPE_INFO		=> 'status_message_info',
	);
    
	/**
	 * Adds success message to queue.
	 *
	 * @param string $message   Info message
	 * @param string $translate Enable auto-translation of message
	 * @param array  $args      Arguments of message
	 */
	public static function success($message, $translate = TRUE, $args = array())
	{
		self::_add_message(self::TYPE_SUCCESS, $message, $translate, $args);
	}
    
	/**
	 * Adds warning message to queue.
	 *
	 * @param string $message   Info message
	 * @param string $translate Enable auto-translation of message
	 * @param array  $args      Arguments of message
	 */
	public static function warning($message, $translate = TRUE, $args = array())
	{
		self::_add_message(self::TYPE_WARNING, $message, $translate, $args);
	}
    
	/**
	 * Adds error message to queue.
	 *
	 * @param string $message   Info message
	 * @param string $translate Enable auto-translation of message
	 * @param array  $args      Arguments of message
	 */
	public static function error($message, $translate = TRUE, $args = array())
	{
		self::_add_message(self::TYPE_ERROR, $message, $translate, $args);
	}
	
	/**
	 * Adds info message to queue.
	 *
	 * @param string $message   Info message
	 * @param string $translate Enable auto-translation of message
	 * @param array  $args      Arguments of message
	 */
	public static function info($message, $translate = TRUE, $args = array())
	{
		self::_add_message(self::TYPE_INFO, $message, $translate, $args);
	}
	
	/**
	 * Renders all messages stored in session queue
	 *
	 * @return string
	 */
	public static function render()
	{
		$messages = self::_get_once();
		$rendered_messages = '';
		
		foreach ($messages as $type => $message)
		{
			$class = @self::$css_classes[$type];
			$rendered_messages .= "<div class=\"status-message $class\">$message</div>";
		}
		
		return $rendered_messages;
	}
	
	/**
	 * Adds message to session var in queue order
	 *
	 * @param integer $type      Type of message (one of type constants)
	 * @param string  $message   Message to strore
	 * @param string  $translate Enable auto-translation of message
	 * @param array   $args      Arguments of message
	 */
	private static function _add_message(
			$type, $message, $translate = TRUE, $args = array())
	{
		if (!empty($message))
		{
			// translate if enabled
			$message = ($translate) ? __($message, $args) : $message; 
			// merge old messages with new
			$messages = self::_get_once() + array
			(
					$type => $message
			);
			// set message
			Session::instance()->set_flash(self::SESSION_VAR_NAME, $messages);
		}
	}
	
	/**
	 * Gets messages queue and deletes it from queue
	 *
	 * @return array
	 */
	private static function _get_once()
	{
		$messages = Session::instance()->get_once(self::SESSION_VAR_NAME);
		
		if (is_array($messages))
		{
			return $messages;
		}
		
		return array();
	}

}
