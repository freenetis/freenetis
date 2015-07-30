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
 * Messages are stored into session var or in memory.
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
	/** Non-session storage */
	private static $messages_in_mem = array();
	
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
	 * Adds info message to queue.
	 *
	 * @param string $message   Info message
	 * @param string $translate Enable auto-translation of message
	 * @param array  $args      Arguments of message
	 */
	public static function info($message, $translate = TRUE, $args = array())
	{
		self::_add_message(TRUE, self::TYPE_INFO, $message, $translate, $args);
	}
    
	/**
	 * Adds success message to queue.
	 *
	 * @param string $message   Info message
	 * @param string $translate Enable auto-translation of message
	 * @param array  $args      Arguments of message
	 */
	public static function success($message, $translate = TRUE, $args = array())
	{
		self::_add_message(TRUE, self::TYPE_SUCCESS, $message, $translate, $args);
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
		self::_add_message(TRUE, self::TYPE_WARNING, $message, $translate, $args);
	}
    
	/**
	 * Adds error message to queue.
	 *
	 * @param string $message   Info message
	 * @param Exception $exception Exception that lead to this error 
	 * @param string $translate Enable auto-translation of message
	 * @param array  $args      Arguments of message
	 */
	public static function error($message, $exception = NULL,
			$translate = TRUE, $args = array())
	{
		self::_add_message(TRUE, self::TYPE_ERROR, $message, $translate, $args, $exception);
	}
    
	/**
	 * Adds success message to memory queue.
	 *
	 * @param string $message   Info message
	 * @param string $translate Enable auto-translation of message
	 * @param array  $args      Arguments of message
	 */
	public static function msuccess($message, $translate = TRUE, $args = array())
	{
		self::_add_message(FALSE, self::TYPE_SUCCESS, $message, $translate, $args);
	}
    
	/**
	 * Adds warning message to memory queue.
	 *
	 * @param string $message   Info message
	 * @param string $translate Enable auto-translation of message
	 * @param array  $args      Arguments of message
	 */
	public static function mwarning($message, $translate = TRUE, $args = array())
	{
		self::_add_message(FALSE, self::TYPE_WARNING, $message, $translate, $args);
	}
    
	/**
	 * Adds error message to memory queue.
	 *
	 * @param string $message   Info message
	 * @param Exception $exception Exception that lead to this error 
	 * @param string $translate Enable auto-translation of message
	 * @param array  $args      Arguments of message
	 */
	public static function merror($message, $exception = NULL,
			$translate = TRUE, $args = array())
	{
		self::_add_message(FALSE, self::TYPE_ERROR, $message, $translate, $args, $exception);
	}
	
	/**
	 * Adds info message to queue.
	 *
	 * @param string $message   Info message
	 * @param string $translate Enable auto-translation of message
	 * @param array  $args      Arguments of message
	 */
	public static function minfo($message, $translate = TRUE, $args = array())
	{
		self::_add_message(FALSE, self::TYPE_INFO, $message, $translate, $args);
	}
	
	/**
	 * Renders all messages stored in session queue
	 *
	 * @return string
	 */
	public static function render()
	{
		// group memory and session messages
		$all_messages = array(self::_get_once(), self::$messages_in_mem);
		$rendered_messages = '';
		
		// render all (memory and session)
		foreach ($all_messages as $messages)
		{
			foreach ($messages as $message)
			{
				foreach ($message as $type => $content)
				{
					$class = @self::$css_classes[$type];
					$text = $content['message'];

					if (!empty($content['exception']) &&
						$content['exception'] instanceof Exception)
					{
						$e = $content['exception'];
						$text .= "<div class=\"status-message-exception\">"
								. "<a href=\"#\" onclick=\"status_exception_expander(this)\">"
								. __('Show details') . "</a>"
								. "<div class=\"status-message-exception-body\"><b>"
								. $e->getMessage() . " at " . $e->getFile() . ":"
								. $e->getLine() . " (" . $e->getCode() . ")"
								. "</b><br /><em style=\"font-weight: normal\">"
								. nl2br($e->getTraceAsString())
								. "</em></div></div>";
					}

					$rendered_messages .= "<div class=\"status-message $class\">$text</div>";
				}
			}
		}
		
		// clean mem
		self::$messages_in_mem = array();
		
		return $rendered_messages;
	}
	
	/**
	 * Adds message to session var in queue order
	 *
	 * @param boolean $session	 Store to session?
	 * @param integer $type      Type of message (one of type constants)
	 * @param string  $message   Message to strore
	 * @param string  $translate Enable auto-translation of message
	 * @param array   $args      Arguments of message
	 * @param Exception $exception Exception related to message
	 */
	private static function _add_message(
			$session, $type, $message, $translate = TRUE, $args = array(),
			$exception = NULL)
	{
		if (!empty($message))
		{
			// translate if enabled
			$message = ($translate) ? __($message, $args) : $message;
			// store
			if ($session) // session
			{
				// merge old messages with new
				$messages = self::_get_once();
				$messages[] = array
				(
					$type => array
					(
						'message'	=> $message,
						'exception'	=> $exception
					)
				);
				// set message
				Session::instance()->set_flash(self::SESSION_VAR_NAME, $messages);
			}
			else // memory
			{
				self::$messages_in_mem[] = array
				(
					$type => array
					(
						'message'	=> $message,
						'exception'	=> $exception
					)
				);
			}
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
