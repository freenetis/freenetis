<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Message file logging class.
 * 
 * $Id: Log.php 1930 2008-02-05 22:35:57Z armen $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
final class Log {

	private static $log_directory;

	private static $types = array('error' => 1, 'debug' => 2, 'info' => 3);
	private static $messages = array();

	/**
	 * Set the the log directory. The log directory is determined by Kohana::setup.
	 *
	 * @param   string   full log directory path
	 * @return  void
	 */
	public static function directory($directory)
	{
		if (self::$log_directory === NULL)
		{
			// Set the log directory if it has not already been set
			self::$log_directory = rtrim($directory, '/').'/';
		}
	}

	/**
	 * Add a log message.
	 *
	 * @param   string  info, debug, or error
	 * @param   string  message to be logged
	 * @return  void
	 */
	public static function add($type, $message)
	{
		if (is_array($message))
		{
			$message = implode(' ', $message);
		}
		
		if (trim($message) == '' ||
			!isset(self::$types[$type]) ||
			self::$types[$type] > Config::get('log_threshold'))
		{
			return;
		}
		
		self::$messages[strtolower($type)][] = array
		(
			date('Y-m-d G:i:s'),
			strip_tags($message),
			url::current(TRUE),
		);
	}
	
	/**
	 * Add a log exception message
	 * 
	 * @param Exception $e
	 */
	public static function add_exception(Exception $e)
	{
		self::add('error', Kohana::lang(
				'core.transaction_exception',
				$e->getMessage(), $e->getFile(),
				$e->getLine(), $e->getTraceAsString()
		));
	}

	/**
	 * Write the current log to a file.
	 *
	 * @return  void
	 */
	public static function write()
	{
		// Set the log threshold
		$threshold = Config::get('log_threshold');

		// Don't log if there is nothing to log to
		if ($threshold < 1 OR count(self::$messages) === 0) return;

		// Set the log filename
		$filename = self::$log_directory.date('Y-m-d').'.log'.EXT;

		// Compile the messages
		$messages = '';
		
		foreach(self::$messages as $type => $data)
		{
			foreach($data as $date => $text)
			{
				list($date, $message, $url) = $text;
				
				$messages .= $date . ' -- ' . $type . ': ' . $message
						  . '   URL: ' . $url . "\r\n";
			}
		}

		// No point in logging nothing
		if ($messages == '')
			return;

		// Create the log file if it doesn't exist yet
		if ( ! file_exists($filename))
		{
			touch($filename);
			chmod($filename, 0644);

			// Add our PHP header to the log file to prevent URL access
			$messages = "<?php defined('SYSPATH') or die('No direct script access.'); ?>\r\n\r\n".$messages;
		}
		
		// add separators between calls
		$messages = $messages . "\n";

		// Append the messages to the log
		file_put_contents($filename, $messages, FILE_APPEND) or trigger_error
		(
			'The log file could not be written to. Please correct the permissions and refresh the page.',
			E_USER_ERROR
		);
	}

} // End Log
