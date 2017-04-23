<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Email helper class.
 *
 * $Id: email.php 1970 2008-02-06 21:54:29Z Shadowhand $
 *
 * @package    Email Helper
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class email {

	// SwiftMailer instance
	protected static $mail;

	/**
	 * Creates a SwiftMailer instance.
	 *
	 * @param   string  DSN connection string
	 * @return  object  Swift object
	 */
	public static function connect($config = NULL)
	{
		if ( ! class_exists('Swift', FALSE))
		{
			// Load SwiftMailer
			require_once Kohana::find_file('vendor', 'swift/Swift');

			// Register the Swift ClassLoader as an autoload
			spl_autoload_register(array('Swift_ClassLoader', 'load'));
		}

		$email_driver = Settings::get('email_driver');

		switch ($email_driver)
		{
			case 'smtp':

				$conn_encryption = null;

				switch (Settings::get('email_encryption')) {
					case 'tsl':
						$conn_encryption = Swift_Connection_SMTP::ENC_TLS;
						break;
					case 'ssl':
						$conn_encryption = Swift_Connection_SMTP::ENC_SSL;
						break;
				}

				// Create a SMTP connection
				$connection = new Swift_Connection_SMTP
				(
					Settings::get('email_hostname'),
					Settings::get('email_port'),
					$conn_encryption
				);

				// Do authentication, if part of the DSN
				(Settings::get('email_username')=='') or $connection->setUsername(Settings::get('email_username'));
				(Settings::get('email_password')=='') or $connection->setPassword(Settings::get('email_password'));

				// Set the timeout to 5 seconds
				$connection->setTimeout(15);
			break;
			case 'sendmail':
				// Create a sendmail connection
				$connection = new Swift_Connection_Sendmail
				(
					/**
					 * @todo Add config settings to email with sendmail
					 */
					//empty($config['options']) ? Swift_Connection_Sendmail::AUTO_DETECT : $config['options']
					Swift_Connection_Sendmail::AUTO_DETECT
				);

				// Set the timeout to 5 seconds
				$connection->setTimeout(15);
			break;
			default:
				// Use the native connection
				$connection = new Swift_Connection_NativeMail;
			break;
		}

		// Create the SwiftMailer instance
		return self::$mail = new Swift($connection);
	}

	/**
	 * Send an email message.
	 *
	 * @param   string|array  recipient email (and name)
	 * @param   string|array  sender email (and name)
	 * @param   string        message subject
	 * @param   string        message body
	 * @param   boolean       send email as HTML
	 * @return  integer       number of emails sent
	 */
	public static function send($to, $from, $subject, $message, $html = FALSE)
	{
		// Connect to SwiftMailer
		(self::$mail === NULL) and email::connect();

		// Determine the message type
		$html = ($html === TRUE) ? 'text/html' : 'text/plain';

		// Create the message
		$message = new Swift_Message($subject, $message, $html, '8bit', 'utf-8');

		// Make a personalized From: address
		$to = is_array($to) ? new Swift_Address($to[0], $to[1]) : new Swift_Address($to);

		// Make a personalized From: address
		$from = is_array($from) ? new Swift_Address($from[0], $from[1]) : new Swift_Address($from);

		return self::$mail->send($message, $to, $from);
	}

} // End email