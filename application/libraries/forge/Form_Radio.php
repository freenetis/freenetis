<?php defined('SYSPATH') or die('No direct script access.');
/** 
 * FORGE dropdown input library. 
 * 
 * @method Form_Radio label(string $label)
 * @method Form_Radio rules(string $rules)
 * @method Form_Radio class(string $class)
 * @method Form_Radio value(string $value)
 * @method Form_Radio options(array $options)
 * @method Form_Radio onclick(string $script)
 */
class Form_Radio extends Form_Input
{
	protected $data = array
	(
			'name' => '',
			'class' => 'radio',
			'type' => 'radio',
			'options' => array(),
			'onclick' => ''
	);
	
	protected $protect = array('type');

	public function __get($key)
	{
		if ($key == 'value')
		{
			return $this->selected;
		}
		return parent::__get($key);
	}

	public function html_element()
	{
		// Import base data 
		$data = $this->data;

		// Get the options and default selection 
		$options = arr::remove('options', $data);
		$selected = arr::remove('selected', $data);
		// martin hack 
		unset($data['label']);
		unset($data['help']);

		$data['class'] = array($data['class']);

		foreach ($this->rules as $rule)
		{
			switch ($rule)
			{
				case 'valid_numeric':
					$data['class'][] = 'number';
					break;
				case 'valid_email':
					$data['class'][] = 'email';
					break;
				case 'valid_mac_address':
					$data['class'][] = 'mac_address';
					break;
				case 'valid_ip_address':
					$data['class'][] = 'ip_address';
					break;
				case 'valid_suffix':
					$data['class'][] = 'suffix';
					break;
				case 'valid_clean_urls':
					$data['class'][] = 'clean_urls';
					break;
				default:
					if (preg_match("/length\[([0-9]+),([0-9]+)\]/", $rule, $matches))
					{
						$data['minlength'] = $matches[1];
						$data['maxlength'] = $matches[2];
					}
					break;
			}
		}

		$data['class'] = implode(' ', $data['class']);

		$next_data = '';
		$next = false;
		
		foreach ($data as $key => $val)
		{
			if ($key == 'type')
				$next = true;
			if ($next)
				$next_data .= $key . '="' . $val . '"';
		}
		
		$html = '';
		
		foreach ($options as $option => $labelText)
		{

			$html .= form::radio(array
			(
					'name' => $data['name'],
					'class' => $data['class'],
					'id' => $data['name'] . "_" . $option
			), $option, $this->value ? $this->value == $option : $data['default'] == $option, $next_data);
			
			$html .= form::label($data['name'] . "_" . $option, $labelText) . " ";
		}
		
		return $html;
	}

	protected function load_value()
	{
		if (is_bool($this->valid))
			return;
		$this->data['selected'] = $this->input_value($this->name);
	}

}

// End Form radio