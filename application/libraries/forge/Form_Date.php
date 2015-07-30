<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE dateselect input library.
 *
 * $Id: Form_Dateselect.php 1923 2008-02-05 14:49:08Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * 
 * @method Form_Date label(string $label)
 * @method Form_Date rules(string $rules)
 * @method Form_Date class(string $class)
 * @method Form_Date months(int $month)
 * @method Form_Date days(int $day)
 * @method Form_Date years(int $year)
 */
class Form_Date extends Form_Input {

	protected $data = array
	(
		'name'  => '',
		'class' => 'date',
	);

	protected $protect = array('type');

	// Precision for the parts, you can use @ to insert a literal @ symbol
	protected $parts = array
	(
		'day'     => array(),
		'month'   => array(),
		'year'    => array(),
	);

	protected $parent_form = NULL;

	public function __construct($name)
	{
		// Set name
		$this->data['name'] = $name;

		// Default to the current time
		$this->data['value'] = time();
	}

	public function __call($method, $args)
	{
		if (isset($this->parts[substr($method, 0, -1)]))
		{
			// Set options for date generation
			$this->parts[substr($method, 0, -1)] = $args;
			
			// Add rule
			
			$this->rules('time_interval');
			
			return $this;
		}

		return parent::__call($method, $args);
	}

	/**
	 * Adds infinity checkbox after input
	 * @param Forge $form Parent Forge of Date input
	 * @return Form_Date $this
	 */
	public function infinity(&$form)
	{
		$this->class("join1 ".$this->class);

		$this->parent_form = &$form;

		$this->parent_form->checkbox($this->data['name']."_infinity")
			->class("join2 checkbox")
			->label("Unlimited");

		// add infinity rule as first
		$rules = $this->rules;
		$old_rules = implode('|', $rules);

		if (!empty($old_rules))
		{
			$old_rules = '|'.$old_rules;
		}
		$this->rules('=infinity'.$old_rules);

		return $this;
	}

	public function html_element()
	{
		// Import base data
		$data = $this->data;

		// No labels or values
		unset($data['label']);

		// count date limit
		$maxDate = '';
		$minDate = '';
		
		foreach($this->parts as $type => $val)
		{
			if (empty($val))
			{
				continue;
			}
			
			if ($type == 'year')
			{
				$minDate .= '-'.(intval(date('Y')) - intval($val[0])).'Y ';
				
				if (count($val) > 1)
				{
					$diff = intval($val[1]) - intval(date('Y'));
					
					$maxDate .= '+'.$diff.'Y ';
				}
			}
			else if ($type == 'month')
			{
				$minDate .= '-'.(intval(date('m')) - intval($val[0])).'m ';
				
				if (count($val) > 1)
				{
					$diff = intval($val[1]) - intval(date('m'));
					
					$maxDate .= '+'.$diff.'m ';
				}
			}
			else if ($type == 'day')
			{
				$minDate .= '-'.(intval(date('d')) - intval($val[0])).'d ';
				
				if (count($val) > 1)
				{
					$diff = intval($val[1]) - intval(date('d'));
					
					$maxDate .= '+'.$diff.'d ';
				}
			}
		}

		// convert timestamp to readable format
		$converted_data = $data;
		$converted_data['value'] = ($data['value'] ? (is_numeric($data['value']) ? date('Y-m-d', $data['value']) : date('Y-m-d', strtotime($data['value']))) : '');
		$converted_data['minDate'] = trim($minDate);
		$converted_data['maxDate'] = trim($maxDate);
		
		$input = form::input($converted_data);

		return $input;
	}

	public function rule_infinity()
	{
		if ($this->parent_form == NULL ||
			!isset($this->parent_form->inputs[$this->name."_infinity"]))
		{
			return;
		}

		$this->parent_form->inputs[$this->name."_infinity"]->validate();

		// remove rules and callbacks
		if ($this->parent_form->inputs[$this->name."_infinity"]->checked)
		{
			$this->rules("-required|time_interval");
			$this->callbacks = array();
		}
	}
	
	public function rule_time_interval()
	{
		// timestamp
		$ts = $this->data['value'];
		
		// get min date
		// min year
		if (empty($this->parts['year']))
		{
			$min_y = date('Y');
		}
		else
		{
			$min_y = $this->parts['year'][0];
		}
		
		// min month
		if (empty($this->parts['month']))
		{
			$min_m = date('m');
		}
		else
		{
			$min_m = $this->parts['month'][0];
		}
		
		// min day
		if (empty($this->parts['day']))
		{
			$min_d = date('d');
		}
		else
		{
			$min_d = $this->parts['day'][0];
		}
		
		// get max date
		// max year
		if (count($this->parts['year']) == 2)
		{
			$max_y = $this->parts['year'][1];
			$max_set = TRUE;
		}
		else
		{
			$max_y = date('Y');
		}
		
		// max month
		if (count($this->parts['month']) == 2)
		{
			$max_m = $this->parts['month'][1];
			$max_set = TRUE;
		}
		else
		{
			$max_m = date('m');
		}
		
		// max day
		if (count($this->parts['day']) == 2)
		{
			$max_d = $this->parts['day'][1];
			$max_set = TRUE;
		}
		else
		{
			$max_d = date('d');
		}
		
		// get min, max timestamps
		$min_ts = mktime(0, 0, 0, $min_m, $min_d, $min_y);
				
		// check if given date is in interval
		if ($ts && $ts < $min_ts)
		{
			$this->errors['date_interval'] = TRUE;
		}
		
		// max interval set
		if (isset($max_set) && $max_set)
		{
			$max_ts = mktime(0, 0, 0, $max_m, $max_d, $max_y);
			
			if ($ts && $ts > $max_ts)
			{
				$this->errors['date_interval'] = TRUE;
			}
		}
		
		if (!$ts)
		{
			$this->data['value'] = 0;
		}
	}

	protected function load_value()
	{
		if (is_bool($this->valid))
			return;

		$time = $this->input_value($this->name);
		
		$this->data['value'] = strtotime($time);
	}

	public function get_string_value_with_infinite()
	{
		$name = $this->name . "_infinity";

		$ts = new DateTime();

		if ($this->parent_form == NULL)
		{
			$ts->setTimestamp($this->value);
		}
		elseif ($this->parent_form->$name->value == "1")
		{
			$ts->setDate(9999,12,31);
		}
		else
		{
			$ts->setTimestamp($this->value);
		}

		return $ts->format('Y-m-d');
	}

} // End Form Dateselect