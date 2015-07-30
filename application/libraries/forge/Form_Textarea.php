<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE textarea input library.
 *
 * $Id: Form_Textarea.php 1923 2008-02-05 14:49:08Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * @method Form_Textarea label(string $label)
 * @method Form_Textarea rules(string $rules)
 * @method Form_Textarea class(string $class)
 * @method Form_Textarea value(string $value)
 */
class Form_Textarea extends Form_Input {

	protected $data = array
	(
		'class' => 'textarea',
		'value' => '',
	);

	protected $protect = array('type');

	protected function html_element()
	{
		$data = $this->data;
		
		unset($data['label']);
		
		// array with aliases of validation function
		$alias = array('numeric' => 'number');

		// convert to array
		$data['class'] = array($data['class']);

		foreach ($this->rules as $rule)
		{
			if ($rule == 'required')
				$data['class'][] = 'required';
			else if (substr($rule, 0, 6) == 'valid_')
			{
				$arr = explode('_', $rule);
				array_shift($arr);
				$rule = implode('_', $arr);

				$rule = (isset($alias[$rule])) ? $alias[$rule] : $rule;

				$data['class'][] = $rule;
			}
			else
			{
				if (preg_match("/length\[([0-9]+),([0-9]+)\]/", $rule, $matches))
				{
					$data['minlength'] = $matches[1];
					$data['maxlength'] = $matches[2];
				}
			}
		}

		foreach ($this->callbacks as $callback)
		{
			$callback = $callback[1];
			if (substr($callback, 0, 6) == 'valid_')
			{
				$arr = explode('_', $callback);
				array_shift($arr);
				$callback = implode('_', $arr);

				$callback = (isset($alias[$callback])) ? $alias[$callback] : $callback;

				$data['class'][] = $callback;
			}
		}

		$data['class'] = implode(' ', $data['class']);

		return form::textarea($data);
	}

} // End Form Textarea