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
		'class' => 'wysiwyg',
		'value' => '',
	);

	protected $protect = array('type');

	protected function html_element()
	{
		$data = $this->data;

		$te = new TextEditor();
		$te->setWidth(656);
		$te->setHeight(480);
		$te->setFieldName($data['name']);
		$te->setContent($data['value']);

		return $te->getHtml();
	}

} // End Form Textarea