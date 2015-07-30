<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE password input library.
 *
 * $Id: Form_Password.php 1923 2008-02-05 14:49:08Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * @method Form_Password label(string $label)
 * @method Form_Password rules(string $rules)
 * @method Form_Password class(string $class)
 * @method Form_Password value(string $value)
 */
class Form_Password extends Form_Input {

	protected $data = array
	(
		'type'  => 'password',
		'class' => '',
		'value' => '',
	);

	protected $protect = array('type');

} // End Form Password