<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE HTML textarea input library.
 *
 * $Id: Form_Textarea.php 1923 2008-02-05 14:49:08Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * @method Form_Html_textarea label(string $label)
 * @method Form_Html_textarea rules(string $rules)
 * @method Form_Html_textarea class(string $class)
 * @method Form_Html_textarea value(string $value)
 */
class Form_Html_textarea extends Form_Input {

	protected $data = array
	(
		'value' => '',
	);

	protected $protect = array('type');
	
	public function __construct($name)
	{
		parent::__construct($name);
		
		$this->mode('advanced');
	}

	/**
	 * Set mode of the HTML test area - simple (only few tools) or advanced (default)
	 * 
	 * @param string $mode
	 * @return Form_Html_textarea
	 */
	public function mode($mode)
	{
		if ($mode == 'advanced' || $mode == 'simple')
		{
			$this->data['mode'] = $mode;
			
			if ($mode == 'advanced')
			{
				$this->class('wysiwyg');
			}
			else
			{
				$this->class('wysiwyg_simple');
			}
		}
		
		return $this;
	}
	
	protected function html_element()
	{
		$data = $this->data;

		$te = new TextEditor();
		
		if ($data['mode'] == 'advanced')
		{
			$te->setWidth(656);
			$te->setHeight(480);
		}
		else
		{
			$te->setWidth(400);
			$te->setHeight(150);
		}
		
		$te->setFieldName($data['name']);
		$te->setContent($data['value']);
		$te->setClass($data['class']);

		return $te->getHtml();
	}

} // End Form Textarea