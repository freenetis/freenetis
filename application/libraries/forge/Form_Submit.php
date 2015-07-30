<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE submit input library.
 *
 * $Id: Form_Submit.php 1923 2008-02-05 14:49:08Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * @method Form_Submit class(string $class)
 * @method Form_Submit value(string $value)
 */
class Form_Submit extends Form_Input {

	protected $data = array
	(
		'type'  => 'submit',
		'class' => 'submit'
	);

	protected $protect = array('type');

	public function __construct($value = 'Save')
	{
		$this->data['value'] = __(ucfirst($value));
	}

	public function html()
	{
		$data = $this->data;
		unset($data['label']);

		return form::button($data);
	}

	public function validate()
	{
		// Submit buttons do not need to be validated
		return $this->is_valid = TRUE;
	}

} // End Form Submit