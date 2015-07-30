<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE hidden input library.
 *
 * $Id: Form_Hidden.php 1923 2008-02-05 14:49:08Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * @method Form_Hidden rules(string $rules)
 * @method Form_Hidden class(string $class)
 * @method Form_GHidden value(string $value)
 */
class Form_Hidden extends Form_Input {

	protected $data = array
	(
		'type'  => 'hidden',
		'class' => 'hidden',
		'value' => '',
	);

	protected $protect = array('type', 'label');

	/**
	 * Load the value of the input, if form data is present.
	 *
	 * @return  void
	 */
	public function load_value()
	{
		if (is_bool($this->is_valid))
			return;

		if ($name = $this->name)
		{
			// Load POSTed value, but only for named inputs
			$this->data['value'] = $this->input_value($name);
		}

		if (is_string($this->data['value']))
		{
			// Trim string values
			$this->data['value'] = trim($this->data['value']);
		}
	}

	public function html()
	{
		// Make sure validation runs
		$this->validate();
		
		$data = $this->data;

		return form::hidden($data);
	}

} // End Form Hidden