<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE upload input library.
 *
 * $Id: Form_Upload.php 1923 2008-02-05 14:49:08Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * @method Form_Upload label(string $label)
 * @method Form_Upload rules(string $rules)
 * @method Form_Upload class(string $class)
 * @method Form_Upload value(string $value)
 */
class Form_Upload extends Form_Input {

	protected $data = array
	(
		'class' => 'upload',
		'value' => '',
	);

	protected $protect = array('type', 'label', 'value');

	// Upload data
	protected $upload;

	// Upload directory
	protected $directory;

	// Upload name
	protected $new_name = '';

	public function __construct($name)
	{
		parent::__construct($name);

		if ( ! empty($_FILES[$name]))
		{
			if (empty($_FILES[$name]['tmp_name']) OR is_uploaded_file($_FILES[$name]['tmp_name']))
			{
				// Cache the upload data in this object
				$this->upload = $_FILES[$name];

				// Hack to allow file-only inputs, where no POST data is present
				$_POST[$name] = $this->upload['name'];
			}
			else
			{
				// Attempt to delete the invalid file
				is_writable($_FILES[$name]['tmp_name']) and unlink($_FILES[$name]['tmp_name']);

				// Invalid file upload, possible hacking attempt
				unset($_FILES[$name]);
			}
		}
	}

	/**
	 * Sets the upload directory.
	 *
	 * @param   string   upload directory
	 * @return  void
	 */
	public function directory($dir = NULL)
	{
		// Use the global upload directory by default
		empty($dir) and $dir = Settings::get('upload_directory');

		// Make the path asbolute and normalize it
		$dir = str_replace('\\', '/', realpath($dir)).'/';

		// Make sure the upload director is valid and writable
		if ($dir === '/' OR ! is_dir($dir) OR ! is_writable($dir))
			throw new Kohana_Exception('upload.not_writable', $dir);

		$this->directory = $dir;

		return $this;
	}

	public function new_name($new_name = NULL)
	{
		$this->new_name = $new_name;

		return $this;
	}

	public function validate()
	{

		// The upload directory must always be set
		empty($this->directory) and $this->directory();

		// By default, there is no uploaded file
		$filename = '';

		if ($status = parent::validate() AND $this->upload['error'] === UPLOAD_ERR_OK)
		{
			if ($this->new_name!='')
				$filename = $this->new_name;
			else
				// Set the filename to the original name
				$filename = $this->upload['name'];

			/*if (Settings::get('upload_remove_spaces'))
			{
				// Remove spaces, due to global upload configuration
				$filename = preg_replace('/\s+/', '_', $this->data['value']);
			}*/

			$filename = $this->directory.$filename;

			// Move the uploaded file to the upload directory
			move_uploaded_file($this->upload['tmp_name'], $filename);
		}

		if ( ! empty($_POST[$this->data['name']]))
		{
			// Reset the POST value to the new filename
			$this->data['value'] = $_POST[$this->data['name']] = $filename;
		}
		return $status;
	}

	protected function rule_required()
	{
		if (empty($this->upload) OR $this->upload['error'] === UPLOAD_ERR_NO_FILE)
		{
			$this->errors['required'] = TRUE;
		}
	}

	public function rule_allow()
	{
		if (empty($this->upload['tmp_name']) OR count($types = func_get_args()) == 0)
			return;

		/*

		if (defined('FILEINFO_MIME'))
		{
			$info = new finfo(FILEINFO_MIME);

			// Get the mime type using Fileinfo
			$mime = $info->file($this->upload['tmp_name']);

			$info->close();
		}
		elseif (ini_get('magic.mime') AND function_exists('mime_content_type'))
		{
			// Get the mime type using magic.mime
			$mime = mime_content_type($this->upload['tmp_name']);
		}
		else
		{
			// Trust the browser
			
		}*/

		// Allow nothing by default
		$allow = FALSE;

		$mime = $this->upload['type'];

		$mimes = array
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

		foreach ($types as $type)
		{
			if (in_array($mime, $mimes[$type]))
			{
				// Type is valid
				$allow = TRUE;
				break;
			}
		}

		if ($allow === FALSE)
		{
			$this->errors['invalid_type'] = TRUE;
		}
	}

	public function rule_size($size)
	{
		$bytes = (int) $size;

		switch (substr($size, -2))
		{
			case 'GB': $bytes *= 1024;
			case 'MB': $bytes *= 1024;
			case 'KB': $bytes *= 1024;
			default: break;
		}

		if (empty($this->upload['size']) OR $this->upload['size'] > $bytes)
		{
			$this->errors['max_size'] = array($size);
		}
	}

	protected function html_element()
	{
		return form::upload($this->data);
	}

} // End Form Upload