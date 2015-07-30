<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is released under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * FORGE (FORm GEneration) library.
 *
 * $Id: Forge.php 1923 2008-02-05 14:49:08Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * @method Form_Checkbox checkbox(string $name)
 * @method Form_Checklist checklist(string $name)
 * @method Form_Date date(string $name)
 * @method Form_Dateselect dateselect(string $name)
 * @method Form_Dropdown dropdown(string $name)
 * @method Form_Group group(string $name)
 * @method Form_Hidden hidden(string $name)
 * @method Form_Html_textarea html_textarea(string $name)
 * @method Form_Input input(string $name)
 * @method Form_Password password(string $name)
 * @method Form_Radio radio(string $name)
 * @method Form_Submit submit(string $name)
 * @method Form_Textarea textarea(string $name)
 * @method Form_Upload upload(string $name)
 */
class Forge {

	// Template variables
	protected $template = array
	(
		'title' => '',
		'class' => '',
		'open'  => '',
		'close' => '',
	);

	// Form attributes
	protected $attr = array();

	// Form inputs and hidden inputs
	public $inputs = array();
	public $hidden = array();

	// Error message format, only used with custom templates
	public $error_format = '<p class="error">{message}</p>';
	public $newline_char = "\n";
	
	private $data = array();
	
	public $visible = NULL;

	/**
	 * Form constructor. Sets the form action, title, method, and attributes.
	 *
	 * @return  void
	 */
	public function __construct($action = NULL, $title = '', $method = NULL, $attr = array())
	{
		// default action
		if (!$action)
		{
			$action = url::base(TRUE).url::current(TRUE);
		}
		// auto action prefix
		if (!text::starts_with($action, url::base()))
		{
			$action = url_lang::base(TRUE) . $action;
		}
		//echo $action.'<br>';
		if (isset($_GET[Path::QSNAME]))
		{
			$parts_action = explode('?', $action);
			$action = $parts_action[0] . '?';
			$path_var_name = Path::QSNAME . '=';
			$vars = array();
			
			if (isset($parts_action[1]))
			{
				$vars = explode('&', $parts_action[1]);
				
				foreach ($vars as $i => $var)
				{
					if (text::starts_with($var, $path_var_name))
					{
						unset($vars[$i]);
					}
				}
			}
			
			$vars[] = $path_var_name . urlencode(urldecode($_GET[Path::QSNAME]));
			
			$action .= implode('&', $vars);
		}
		//echo $action.'<br>';
		// Set form attributes
		$this->attr['action'] = $action;
		$this->attr['method'] = empty($method) ? 'post' : $method;

		// Set template variables
		$this->template['title'] = $title;

		// Empty attributes sets the class to "form"
		empty($attr) and $attr = array('class' => 'form');

		// String attributes is the class name
		is_string($attr) and $attr = array('class' => $attr);

		// Extend the template with the attributes
		$this->attr += $attr;
	}

	/**
	 * Magic __get method. Returns the specified form element.
	 *
	 * @param   string   unique input name
	 * @return  object
	 */
	public function __get($key)
	{
		if (isset($this->inputs[$key]))
		{
			return $this->inputs[$key];
		}
		elseif (isset($this->hidden[$key]))
		{
			return $this->hidden[$key];
		}
	}

	/**
	 * Magic __call method. Creates a new form element object.
	 *
	 * @throws  Kohana_Exception
	 * @param   string   input type
	 * @param   string   input name
	 * @return  object
	 */
	public function __call($method, $args)
	{
		// Class name
		$input = 'Form_'.ucfirst($method);

		// Create the input
		switch(count($args))
		{
			case 1:
				$input = new $input($args[0]);
			break;
			case 2:
				$input = new $input($args[0], $args[1]);
			break;
		}

		if ( ! ($input instanceof Form_Input) AND ! ($input instanceof Forge))
			throw new Kohana_Exception('forge.invalid_input', get_class($input));

		$input->method = $this->attr['method'];

		if ($name = $input->name)
		{
			// Assign by name
			if ($method == 'hidden')
			{
				$this->hidden[$name] = $input;
			}
			else
			{
				$this->inputs[$name] = $input;
			}
		}
		else
		{
			// No name, these are unretrievable
			$this->inputs[] = $input;
		}
		return $input;
	}

	/**
	 * Set a form attribute. This method is chainable.
	 *
	 * @param   string|array  attribute name, or an array of attributes
	 * @param   string        attribute value
	 * @return  object
	 */
	public function set_attr($key, $val = NULL)
	{
		if (is_array($key))
		{
			// Merge the new attributes with the old ones
			$this->attr = array_merge($this->attr, $key);
		}
		else
		{
			// Set the new attribute
			$this->attr[$key] = $val;
		}

		return $this;
	}

	/**
	 * Validates the form by running each inputs validation rules.
	 *
	 * @return  bool
	 */
	public function validate()
	{
		$status = TRUE;
		
		// validate inputs
		foreach($this->inputs as $input)
		{
			if ($input->validate() == FALSE)
			{
				$status = FALSE;
			}
		}
		// validate hidden inputs
		foreach ($this->hidden as $input)
		{
			if ($input->validate() == FALSE)
			{
				$status = FALSE;
			}
		}

		return $status;
	}
	
	/**
	 * Loads inputs
	 * 
	 * @author Michal Kliment
	 * @param type $object 
	 */
	private function load_inputs ($object)
	{
		foreach(array_merge($object->hidden, $object->inputs) as $input)
		{
			if ($input->inputs)
			{
				$this->load_inputs($input);
			}
			
			if (!($input instanceof Form_Group) && ($name = $input->name))
			{
				$name = str_replace('[]', '', $name);
				// Return only named inputs
				$this->data[$name] = $input->value;
			}
		}
	}

	/**
	 * Returns the form as an array of input names and values.
	 *
	 * @param boolean clean	If set to TRUE (default) returned values are escaped
	 *						Otherwise they are not.
	 * @return  array
	 */
	public function as_array($clean = TRUE)
	{
		$this->load_inputs($this);
		return $clean ? array_map('html::specialchars', $this->data) : $this->data;
	}

	/**
	 * Changes the error message format. Your message formatting must
	 * contain a {message} placeholder.
	 *
	 * @throws  Kohana_Exception
	 * @param   string   new message format
	 * @return  void
	 */
	public function error_format($string = '')
	{
		if (strpos((string) $string, '{message}') === FALSE)
			throw new Kohana_Exception('validation.error_format');

		$this->error_format = $string;
	}

	/**
	 * Creates the form HTML
	 *
	 * @param   string   form view template name
	 * @param   boolean  use a custom view
	 * @return  string
	 */
	public function html($template = 'forge_template', $custom = FALSE)
	{
		// Load template
		$form = new View($template);

		if ($custom)
		{
			// Using a custom view

			$data = array();
			foreach ($this->inputs as $input)
			{
				$data[$input->name] = $input;

				// Compile the error messages for this input
				$messages = '';
				$errors = $input->error_messages();
				if (is_array($errors) AND ! empty($errors))
				{
					foreach($errors as $error)
					{
						// Replace the message with the error in the html error string
						$messages .= str_replace('{message}', $error, $this->error_format).$this->newline_char;
					}
				}

				$data[$input->name.'_errors'] = $messages;
			}

			$form->set($data);
		}
		else
		{
			// Using a template view

			$form->set($this->template);
			$hidden = array();
			if ( ! empty($this->hidden))
			{
				foreach($this->hidden as $input)
				{
					$input->validate();
					$hidden[$input->name] = $input->value;
				}
			}

			$form_type = 'open';
			// See if we need a multipart form
			foreach ($this->inputs as $input)
			{
				if ($input instanceof Form_Upload)
				{
					$form_type = 'open_multipart';
				}
			}

			// Set the form open and close
			$form->open  = form::$form_type(arr::remove('action', $this->attr), $this->attr, $hidden);
			$form->close = form::close();

			// Set the inputs
			$form->inputs = $this->inputs;
		}

		return $form->render();
	}

	/**
	 * Returns the form HTML
	 */
	public function __toString()
	{
		return $this->html();
	}

} // End Forge
