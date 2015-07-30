<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Upload helper class for working with the global $_FILES
 * array and Validation library.
 *
 * $Id: upload.php 4134 2009-03-28 04:37:54Z zombor $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class upload {

	public static $mimes = array
		(
			'7z'    => array('application/x-7z-compressed'),
			'ai'    => array('application/postscript'),
			'aif'   => array('audio/x-aiff'),
			'aifc'  => array('audio/x-aiff'),
			'aiff'  => array('audio/x-aiff'),
			'asf'   => array('video/x-ms-asf'),
			'atom'  => array('application/atom+xml'),
			'avi'   => array('video/x-msvideo'),
			'bin'   => array('application/octet-stream','application/macbinary'),
			'bmp'   => array('image/bmp'),
			'cab'   => array('application/x-cab'),
			'cpt'   => array('application/mac-compactpro'),
			'class' => array('application/octet-stream'),
			'css'   => array('text/css'),
			'csv'   => array('text/x-comma-separated-values', 'application/vnd.ms-excel'),
			'deb'   => array('application/x-debian-package'),
			'dbk'   => array('application/docbook+xml'),
			'dir'   => array('application/x-director'),
			'dcr'   => array('application/x-director'),
			'doc'   => array('application/msword'),
			'dmg'   => array('application/x-apple-diskimage'),
			'dms'   => array('application/octet-stream'),
			'dvi'   => array('application/x-dvi'),
			'dxr'   => array('application/x-director'),
			'dll'   => array('application/octet-stream', 'application/x-msdos-program'),
			'eml'   => array('message/rfc822'),
			'exe'   => array('application/x-msdos-program', 'application/octet-stream'),
			'eps'   => array('application/postscript'),
			'gif'   => array('image/gif'),
			'gtar'  => array('application/x-gtar'),
			'gz'    => array('application/x-gzip'),
			'htm'   => array('text/html'),
			'html'  => array('text/html'),
			'hqx'   => array('application/mac-binhex40'),
			'ics'   => array('text/calendar'),
			'iso'   => array('application/x-iso9660-image'),
			'jar'   => array('application/java-archive'),
			'jpeg'  => array('image/jpeg', 'image/pjpeg'),
			'jpg'   => array('image/jpeg', 'image/pjpeg'),
			'jpe'   => array('image/jpeg', 'image/pjpeg'),
			'js'    => array('application/x-javascript'),
			'json'  =>  array('application/json'),
			'lha'   => array('application/octet-stream'),
			'log'   => array('text/plain', 'text/x-log'),
			'lzh'   => array('application/octet-stream'),
			'midi'  => array('audio/midi'),
			'mid'   => array('audio/midi'),
			'mif'   => array('application/vnd.mif'),
			'mp2'   => array('audio/mpeg'),
			'mp3'   => array('audio/mpeg'),
			'mov'   => array('video/quicktime'),
			'movie' => array('video/x-sgi-movie'),
			'mpe'   => array('video/mpeg'),
			'mpeg'  => array('video/mpeg'),
			'mpg'   => array('video/mpeg'),
			'mpga'  => array('audio/mpeg'),
			'msi'   => array('application/x-msi'),
			'oda'   => array('application/oda'),
			'odb'   => array('application/vnd.oasis.opendocument.database'),
			'odc'   => array('application/vnd.oasis.opendocument.chart'),
			'odf'   => array('application/vnd.oasis.opendocument.forumla'),
			'odg'   => array('application/vnd.oasis.opendocument.graphics'),
			'odi'   => array('application/vnd.oasis.opendocument.image'),
			'odm'   => array('application/vnd.oasis.opendocument.text-master'),
			'odp'   => array('application/vnd.oasis.opendocument.presentation'),
			'ods'   => array('application/vnd.oasis.opendocument.spreadsheet'),
			'odt'   => array('application/vnd.oasis.opendocument.text'),
			'ogg'   => array('application/ogg'),
			'otg'   => array('application/vnd.oasis.opendocument.graphics-template'),
			'oth'   => array('application/vnd.oasis.opendocument.web'),
			'otp'   => array('application/vnd.oasis.opendocument.presentation-template'),
			'ots'   => array('application/vnd.oasis.opendocument.spreadsheet-template'),
			'ott'   => array('application/vnd.oasis.opendocument.template'),
			'pdf'   => array('application/pdf', 'application/x-download'),
			'php'   => array('application/x-httpd-php'),
			'php3'  => array('application/x-httpd-php'),
			'php4'  => array('application/x-httpd-php'),
			'php5'  => array('application/x-httpd-php'),
			'phps'  => array('application/x-httpd-php-source'),
			'phtml' => array('application/x-httpd-php'),
			'png'   => array('image/png', 'image/x-png'),
			'pps'   => array('application/vnd.ms-powerpoint'),
			'ppt'   => array('application/powerpoint'),
			'ps'    => array('application/postscript'),
			'psd'   => array('application/x-photoshop', 'image/x-photoshop'),
			'qt'    => array('video/quicktime'),
			'ra'    => array('audio/x-realaudio'),
			'ram'   => array('audio/x-pn-realaudio'),
			'rar'   => array('application/rar'),
			'rm'    => array('audio/x-pn-realaudio'),
			'rpm'   => array('audio/x-pn-realaudio-plugin', 'application/x-redhat-package-manager'),
			'rss'   => array('application/rss+xml'),
			'rtf'   => array('text/rtf'),
			'rtx'   => array('text/richtext'),
			'rv'    => array('video/vnd.rn-realvideo'),
			'sea'   => array('application/octet-stream'),
			'shtml' => array('text/html'),
			'sit'   => array('application/x-stuffit'),
			'smi'   => array('application/smil'),
			'smil'  => array('application/smil'),
			'so'    => array('application/octet-stream'),
			'swf'   => array('application/x-shockwave-flash'),
			'tar'   => array('application/x-tar'),
			'torrent' => array('application/x-bittorrent'),
			'text'  => array('text/plain'),
			'tif'   => array('image/tiff'),
			'tiff'  => array('image/tiff'),
			'tgz'   => array('application/x-tar'),
			'txt'   => array('text/plain'),
			'wav'   => array('audio/x-wav'),
			'wbxml' => array('application/wbxml'),
			'wmlc'  => array('application/wmlc'),
			'wpd'   => array('application/vnd.wordperfect'),
			'word'  => array('application/msword', 'application/octet-stream'),
			'xhtml' => array('application/xhtml+xml'),
			'xht'   => array('application/xhtml+xml'),
			'xl'    => array('application/excel'),
			'xls'   => array('application/excel', 'application/vnd.ms-excel'),
			'xml'   => array('text/xml'),
			'xsl'   => array('text/xml'),
			'zip'   => array('application/x-zip', 'application/zip', 'application/x-zip-compressed')
		);

	/**
	 * Save an uploaded file to a new location.
	 *
	 * @param   mixed    name of $_FILE input or array of upload data
	 * @param   string   new filename
	 * @param   string   new directory
	 * @param   integer  chmod mask
	 * @return  string   full path to new file
	 */
	public static function save($file, $filename = NULL, $directory = NULL, $chmod = 0644)
	{
		// Load file data from FILES if not passed as array
		$file = is_array($file) ? $file : $_FILES[$file];

		if ($filename === NULL)
		{
			// Use the default filename, with a timestamp pre-pended
			$filename = time().$file['name'];
		}

		if (Settings::get('upload_remove_spaces'))
		{
			// Remove spaces from the filename
			$filename = preg_replace('/\s+/', '_', $filename);
		}

        

		if ($directory === NULL)
		{
			// Use the pre-configured upload directory
			$directory = Settings::get('upload_directory');
		}

		// Make sure the directory ends with a slash
		$directory = rtrim($directory, '/').'/';

		if ( ! is_dir($directory) AND Settings::get('upload_create_directories'))
		{
			// Create the upload directory
			mkdir($directory, 0777, TRUE);
		}


		if ( ! is_writable($directory))
			throw new Kohana_Exception('upload.not_writable', $directory);

		if (is_uploaded_file($file['tmp_name']) AND move_uploaded_file($file['tmp_name'], $filename = $directory.$filename))
		{
			if ($chmod !== FALSE)
			{
				// Set permissions on filename
				chmod($filename, $chmod);
			}

			// Return new file path
			return $filename;
		}

		return FALSE;
	}

	/* Validation Rules */

	/**
	 * Tests if input data is valid file type, even if no upload is present.
	 *
	 * @param   array  $_FILES item
	 * @return  bool
	 */
	public static function valid($file)
	{
		return (is_array($file)
			AND isset($file['error'])
			AND isset($file['name'])
			AND isset($file['type'])
			AND isset($file['tmp_name'])
			AND isset($file['size']));
	}

	/**
	 * Tests if input data has valid upload data.
	 *
	 * @param   array    $_FILES item
	 * @return  bool
	 */
	public static function required(array $file)
	{
		return (isset($file['tmp_name'])
			AND isset($file['error'])
			AND is_uploaded_file($file['tmp_name'])
			AND (int) $file['error'] === UPLOAD_ERR_OK);
	}

	/**
	 * Validation rule to test if an uploaded file is allowed by extension.
	 *
	 * @param   array    $_FILES item
	 * @param   array    allowed file extensions
	 * @return  bool
	 */
	public static function type(array $file, array $allowed_types)
	{
		if ((int) $file['error'] !== UPLOAD_ERR_OK)
			return TRUE;

		// Get the default extension of the file
		$extension = strtolower(substr(strrchr($file['name'], '.'), 1));

		
		
		// Get the mime types for the extension
		$mime_types = $mimes[$extension];

		// Make sure there is an extension, that the extension is allowed, and that mime types exist
		return ( ! empty($extension) AND in_array($extension, $allowed_types) AND is_array($mime_types));
	}

	/**
	 * Validation rule to test if an uploaded file is allowed by file size.
	 * File sizes are defined as: SB, where S is the size (1, 15, 300, etc) and
	 * B is the byte modifier: (B)ytes, (K)ilobytes, (M)egabytes, (G)igabytes.
	 * Eg: to limit the size to 1MB or less, you would use "1M".
	 *
	 * @param   array    $_FILES item
	 * @param   array    maximum file size
	 * @return  bool
	 */
	public static function size(array $file, array $size)
	{
		if ((int) $file['error'] !== UPLOAD_ERR_OK)
			return TRUE;

		// Only one size is allowed
		$size = strtoupper($size[0]);

		if ( ! preg_match('/[0-9]++[BKMG]/', $size))
			return FALSE;

		// Make the size into a power of 1024
		switch (substr($size, -1))
		{
			case 'G': $size = intval($size) * pow(1024, 3); break;
			case 'M': $size = intval($size) * pow(1024, 2); break;
			case 'K': $size = intval($size) * pow(1024, 1); break;
			default:  $size = intval($size);                break;
		}

		// Test that the file is under or equal to the max size
		return ($file['size'] <= $size);
	}

} // End upload