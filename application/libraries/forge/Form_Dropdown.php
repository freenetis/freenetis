<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE dropdown input library.
 *
 * $Id: Form_Dropdown.php 1923 2008-02-05 14:49:08Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * @method Form_Dropdown label(string $label)
 * @method Form_Dropdown rules(string $rules)
 * @method Form_Dropdown class(string $class)
 * @method Form_Dropdown options(array $options)
 * @method Form_Dropdown selected(int $selected_index)
 */
class Form_Dropdown extends Form_Input {

	protected $data = array
	(
		'type' => 'dropdown',
		'class' => 'dropdown',
		'name'  => '',
	);

	protected $protec = array('type');

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
		$base_data = $this->data;
		if (in_array ("required", $this->rules))
			$base_data['class'] .= " required";

		// Get the options and default selection
		$options = arr::remove('options', $base_data);
		$selected = arr::remove('selected', $base_data);
		arr::remove('label', $base_data);
		$add_button = arr::remove('add_button', $base_data);
		$add_button_title = arr::remove('add_button_title', $base_data);

		$html = form::dropdown($base_data, $options, $selected);
		
		if ($add_button)
		{
			$html .= '&nbsp;' . html::anchor(
				$this->data['add_button'], html::image(array
				(
					'src'	=> 'media/images/icons/ico_add.gif',
					'id'	=> $this->data['name'] . '_add_button'
				)), array
				(
					'class'	=> 'popup-add popup_link',
					'title' => $add_button_title
				)
			);
		}
		
		return $html;
	}

	/**
	 * Add button for adding object to drobbox.
	 * Content of dropdown is automatically update after adding by AJAX.
	 * 
	 * @author Ondřej Fibich, Michal Kliment
	 * @param string $controller	Controller to add
	 * @param string $method		Method of controller [optional]
	 * @param string $args			Other arguments of controller [optional]
	 * @return						Form_Dropdown
	 */
	public function add_button($controller = NULL, $method = 'add', $args = '')
	{
		if (empty($controller))
		{
			return;
		}
		
		$url = $controller.'/'.$method;
		
		if (!empty($args))
		{
			$url .= '/'.$args;
		}
		
		$controller_name = inflector::singular(inflector::humanize($controller));
		
		$this->data['add_button'] = $url;
		$this->data['add_button_title'] = __('Add '.$controller_name);
		
		return $this;
	}

	protected function load_value()
	{
		if (is_bool($this->valid))
			return;
		
		$this->data['selected'] = $this->input_value($this->name);
	}

	/**
	 * Runs validation and returns the element HTML.
	 *
	 * @return  string
	 */
	public function html()
	{
		// sets selected value to a value of GET parameter with same name (request #614)
		$value = Input::instance()->get($this->name());
		
		if(!empty($value))
		{
			$key = array_search(urldecode($value), $this->data['options']);
			
			if ($key !== false)
				$this->selected ($key);
		}
		
		// Make sure validation runs
		$this->validate();

		return $this->html_element();
	}
	
} // End Form Dropdown