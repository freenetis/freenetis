<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE checkbox input library.
 *
 * $Id: Form_Checkbox.php 1923 2008-02-05 14:49:08Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * @method Form_Checkbox label(string $label)
 * @method Form_Checkbox rules(string $rules)
 * @method Form_Checkbox class(string $class)
 * @method Form_Checkbox value(string $value)
 * @method Form_Checkbox checked(bool $checked)
 */
class Form_Checkbox extends Form_Input {

	protected $data = array
	(
		'type' => 'checkbox',
		'class' => 'checkbox',
		'value' => '1',
		'checked' => FALSE,
	);

	protected $protect = array('type');

	public function __construct($name)
	{
		$this->data['name'] = $name;
	}

	public function __get($key)
	{
		if ($key == 'value')
		{
			// Return the value if the checkbox is checked
			return $this->data['checked'] ? $this->data['value'] : FALSE;
		}

		return parent::__get($key);
	}

	protected function html_element()
	{
		// Import the data
		$data = $this->data;

		if ($label = arr::remove('label', $data))
		{
			// There must be one space before the text
			$label = ' '.ltrim($label);
		}

		return '<label>'.form::checkbox($data).$label.'</label>';
	}

	protected function load_value()
	{
		if (is_bool($this->valid))
			return;

		// Makes the box checked if the value from POST is the same as the current value
		$this->data['checked'] = ($this->input_value($this->name) == $this->data['value']);
	}

} // End Form Checkbox