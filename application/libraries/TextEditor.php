<?php defined('SYSPATH') or die('No direct script access.');
/**
 * TextEditor library.
 *
 * @package    TextEditor
 * @author     Kiall Mac Innes / Managed I.T.
 * @copyright  (c) 2008 Managed I.T.
 * @license    GNU GPLv3
 * @license    http://www.gnu.org/licenses/gpl.txt
 */
class TextEditor
{

	// Session instance
	protected $session;
	// Configuration
	protected $config;
	// Driver Object
	protected $driver;
	// Instance counter
	public static $instance_counter = 0;

	/**
	 * Create an instance of TextEditor.
	 *
	 * @return  object
	 */
	public static function factory($config = array())
	{
		return new TextEditor($config);
	}

	/**
	 * Return a static instance of TextEditor.
	 *
	 * @return  object
	 */
	public static function instance($config = array())
	{
		static $instance;

		// Load the TextEditor instance
		empty($instance) and $instance = new TextEditor($config);

		return $instance;
	}

	/**
	 * Constructor
	 *
	 * @return  void
	 */
	public function __construct($config = array())
	{
		self::$instance_counter++;
		$this->driver = new TextEditor_Driver_TinyMCE ();
	}

	public function getHtml()
	{
		return $this->driver->getHtml();
	}

	public function setWidth($width)
	{
		return $this->driver->setWidth($width);
	}

	public function getWidth()
	{
		return $this->driver->getWidth();
	}

	public function setHeight($height)
	{
		return $this->driver->setHeight($height);
	}

	public function getHeight()
	{
		return $this->driver->getHeight();
	}

	public function checkCompatiblily()
	{
		return $this->driver->checkCompatiblily();
	}

	public function setFieldName($fieldName)
	{
		return $this->driver->setFieldName($fieldName);
	}

	public function setContent($content)
	{
		return $this->driver->setContent($content);
	}

	public function getFieldName()
	{
		return $this->driver->getFieldName();
	}

}

/**
 * TextEditor module TinyMCE Driver.
 *
 * @package    TextEditor
 * @author     Kiall Mac Innes / Managed I.T.
 * @copyright  (c) 2008 Managed I.T.
 * @license    GNU GPLv3
 * @license    http://www.gnu.org/licenses/gpl.txt
 */
class TextEditor_Driver_TinyMCE implements TextEditor_Driver
{

	// Configuration
	protected $config;
	protected $width;
	protected $height;
	// Set the default field name.
	protected $fieldName = 'editor';
	// TinyMCE path
	protected $path = 'media/js/tinymce/';
	protected $content;

	public function __construct()
	{
	}

	public function setWidth($width)
	{
		$this->width = $width;
	}

	public function getWidth()
	{
		return $this->width;
	}

	public function setHeight($height)
	{
		$this->height = $height;
	}

	public function getHeight()
	{
		return $this->height;
	}

	public function setFieldName($fieldName)
	{
		$this->fieldName = $fieldName;
	}

	public function getFieldName()
	{
		return $this->fieldName;
	}

	public function setContent($content)
	{
		$this->content = $content;
	}

	public function getHtml()
	{
		return  '<textarea id="' . $this->fieldName . '" name="' .
				$this->fieldName . '" width="' . $this->width .
				'" class="wysiwyg">' . $this->content . '</textarea>';
	}

	public function checkCompatiblily()
	{
		return true;
	}

}

/**
 * TextEditor module driver interface.
 *
 * @package    TextEditor
 * @author     Kiall Mac Innes / Managed I.T.
 * @copyright  (c) 2008 Managed I.T.
 * @license    GNU GPLv3
 * @license    http://www.gnu.org/licenses/gpl.txt
 */
interface TextEditor_Driver
{

	function setWidth($width);

	function getWidth();

	function setHeight($height);

	function getHeight();

	function getHtml();

	function setFieldName($fieldName);

	function getFieldName();

	function setContent($content);

	function checkCompatiblily();
}

