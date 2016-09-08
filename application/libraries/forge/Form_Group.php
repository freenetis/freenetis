<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE group library.
 *
 * $Id: Form_Group.php 1923 2008-02-05 14:49:08Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * @method Form_Group rules(string $rules)
 * @method Form_Group class(string $class)
 * @method Form_Group value(string $value)
 * @method Form_Group message(string $message)
 */
class Form_Group extends Forge {

	protected $data = array
	(
		'type'  => 'group',
		'class' => 'group',
		'label' => '',
		'message' => ''
	);

	// Input method
	public $method;

	public function __construct($label = '', $class = 'group')
	{
		$this->data['class'] = $class;

		// Set dummy data so we don't get errors
		$this->attr['action'] = '';
		$this->attr['method'] = 'post';
		$this->data['label'] = __(ucfirst($label));
	}

	public function __get($key)
	{
		if ($key == 'type')
		{
			return $this->data['type'];
		}
		return parent::__get($key);
	}

	public function __set($key, $val)
	{
		if ($key == 'method')
		{
			$this->attr['method'] = $val;
		}
		$this->$key = $val;
	}

	/**
	 *
	 * @param string $val
	 * @return Form_Group 
	 */
	public function label($val = NULL)
	{
		if ($val === NULL)
		{
			if ($label = $this->data['label'])
			{
				return $this->data['label'];
			}
		}
		else
		{
			$this->data['label'] = ($val === TRUE) ? ucwords(inflector::humanize($this->data['label'])) : $val;
			return $this;
		}
	}

	public function message($val = NULL)
	{
		if ($val === NULL)
		{
			return $this->data['message'];
		}
		else
		{
			$this->data['message'] = $val;
			return $this;
		}
	}
	
	/**
	 * Sets visibility of group
	 * 
	 * @author Michal Kliment
	 * @param type $val
	 * @return Form_Group 
	 */
	public function visible($val = FALSE)
	{
		if ($this->visible === NULL)
		{
			$this->visible = (bool) $val;
		}
		return $this;
	}

	public function html($template = 'forge_template', $custom = false)
	{
		// No Sir, we don't want any html today thank you
		return;
	}

} // End Form Group