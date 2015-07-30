<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Download helper class.
 *
 * $Id: download.php 1725 2008-01-17 16:38:59Z PugFish $
 *
 * @package    Download Helper
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class download {

	/**
	 * Force a download of a file to the user's browser. This function is
	 * binary-safe and will work with any MIME type that Kohana is aware of.
	 *
	 * @param   string  a file path or file name
	 * @param   mixed   data to be sent if the filename does not exist
	 * @return  void
	 */
	public static function force($filename = '', $data = '')
	{
		static $user_agent;

		if ($filename == '')
			return FALSE;

		if (is_file($filename))
		{
			// Get the real path
			$filepath = str_replace('\\', '/', realpath($filename));

			// Get extension
			$extension = pathinfo($filepath, PATHINFO_EXTENSION);

			// Remove directory path from the filename
			$filename = end(explode('/', $filepath));

			// Set filesize
			$filesize = filesize($filepath);
		}
		else
		{
			// Grab the file extension
			$extension = end(explode('.', $filename));

			// Try to determine if the filename includes a file extension.
			// We need it in order to set the MIME type
			if (empty($data) OR $extension === $filename)
				return FALSE;

			// Set filesize
			$filesize = strlen($data);
		}

		$mime = 'application/octet-stream';
		
		if (text::ends_with(mb_strtolower($filename), '.jpg') ||
			text::ends_with(mb_strtolower($filename), '.jpeg'))
		{
			$mine = 'image/jpeg';
		}
		else if (text::ends_with(mb_strtolower($filename), '.png'))
		{
			$mine = 'image/png';
		}
		else if (text::ends_with(mb_strtolower($filename), '.gif'))
		{
			$mine = 'image/gif';
		}

		// Generate the server headers
		@header('Content-Description: File Transfer');
		@header('Content-Type: '.$mime);
		@header('Content-Disposition: attachment; filename="'.$filename.'"');
		@header('Content-Transfer-Encoding: binary');
		@header('Expires: 0');
		@header('Content-Length: '.$filesize);
		@header('Pragma: no-cache');

		if (isset($filepath))
		{
			// Open the file
			$handle = fopen($filepath, 'rb');

			// Send the file data
			fpassthru($handle);

			// Close the file
			fclose($handle);
		}
		else
		{
			// Send the file data
			echo $data;
		}
	}

} // End download