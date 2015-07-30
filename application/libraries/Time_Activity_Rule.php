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
 * This class is used as predecesor of models that can be sheduled on sime
 * time and then runned by CRON. For example notification may triggered
 * daily/hourly/monthly/etc.
 * 
 * All model classes that implements this class must define columns for
 * type (integer) and attribute (mixed - varchar(255)).
 *
 * @author Ondrej Fibich
 */
abstract class Time_Activity_Rule extends ORM
{
	
	// constants that represent types of automatical activation of notification
	
	/**
	 * auto activation each month, attibutes:
	 * 
	 *		- day (day of activation with value 1..31, if the current month
	 *			   has less days than setted attribute - e.g. 27.2. - notification
	 *			   is activated on last of this date) 
	 *		- hour (hour of activation with value from 0..23)
	 * 
	 */
	const TYPE_MONTHLY				= 1;
	
	/**
	 * auto activation each week, attributes:
	 *		
	 *		- day (day of activation with value 1..7)
	 *		- hour (hour of activation with value from 0..23)
	 */
	const TYPE_WEEKLY				= 2;
	
	/**
	 * auto activation each day, attributes:
	 * 
	 *		- hour (hour of activation with value from 0..23)
	 */
	const TYPE_DAILY				= 3;
	
	/**
	 * auto activation each working day, attributes:
	 * 
	 *		- hour (hour of activation with value from 0..23)
	 */
	const TYPE_DAILY_WD				= 4;
	
	/**
	 * auto activation each hour, empty attributes.
	 */
	const TYPE_HOURLY				= 5;
	
	/**
	 * auto activation in the deduction day in the specified hour, attributes:
	 * 
	 *		- hour (hour of activation with value from 0..23)
	 */
	const TYPE_AFTER_DEDUCTION		= 6;
	
	
	/**
	 * Auto activation mesasages
	 *
	 * @author Ondřej Fibich
	 * @var array
	 */
	private static $type_messsages = array
	(
		self::TYPE_WEEKLY			=> 'Weekly',
		self::TYPE_MONTHLY			=> 'Monthly',
		self::TYPE_DAILY			=> 'Daily',
		self::TYPE_DAILY_WD			=> 'Daily on working days',
		self::TYPE_AFTER_DEDUCTION	=> 'After deduction of fees',
		self::TYPE_HOURLY			=> 'Hourly',
	);
	
	/**
	 * Auto activation attributes types.
	 * Key is olways one of the constant TYPE_* and value is associative array
	 * that always contains type field that represents a type of 
	 *
	 * @author Ondřej Fibich
	 * @var array
	 */
	private static $type_attributes = array
	(
		self::TYPE_MONTHLY			=> array
		(
			array
			(
				'type'			=> 'integer',
				'name'			=> 'day of month',
				'title'			=> 'day of month',
				'range_from'	=> 1,
				'range_to'		=> 31,
			),
			array
			(
				'type'			=> 'integer',
				'name'			=> 'hour',
				'title'			=> 'hour',
				'range_from'	=> 0,
				'range_to'		=> 23,
			),
		),
		self::TYPE_WEEKLY			=> array
		(
			array
			(
				'type'			=> 'integer',
				'name'			=> 'day',
				'title'			=> 'day of week',
				'range_from'	=> 1,
				'range_to'		=> 7,
			),
			array
			(
				'type'			=> 'integer',
				'name'			=> 'hour',
				'title'			=> 'hour',
				'range_from'	=> 0,
				'range_to'		=> 23,
			),
		),
		self::TYPE_DAILY			=> array
		(
			array
			(
				'type'			=> 'integer',
				'name'			=> 'hour',
				'title'			=> 'hour',
				'range_from'	=> 0,
				'range_to'		=> 23,
			),
		),
		self::TYPE_HOURLY			=> array
		(
			array
			(
				'type'			=> FALSE, //  no attribute
			),
		),
		self::TYPE_DAILY_WD			=> array
		(
			array
			(
				'type'			=> 'integer',
				'name'			=> 'hour',
				'title'			=> 'hour',
				'range_from'	=> 0,
				'range_to'		=> 23,
			),
		),
		self::TYPE_AFTER_DEDUCTION	=> array
		(
			array
			(
				'type'			=> 'integer',
				'name'			=> 'hour',
				'title'			=> 'hour',
				'range_from'	=> 0,
				'range_to'		=> 23,
			),
		),
	);
	
	/**
	 * Gets set of self cancel messages
	 * 
	 * @author Ondřej Fibich
	 * @param bool $translate	Translate messages
	 * @return array
	 */
	public static function get_type_messages($translate = TRUE)
	{
		if ($translate)
		{
			return array_map('__', self::$type_messsages);
		}
		
		return self::$type_messsages;
	}
	
	/**
	 * Gets message for the given type
	 * 
	 * @param integer $type
	 * @param boolean $translate
	 * @return string|null
	 */
	public static function get_type_message($type, $translate = TRUE)
	{
		if (isset(self::$type_messsages[$type]))
		{
			if ($translate)
			{
				return __(self::$type_messsages[$type]);
			}
			
			return self::$type_messsages[$type];
		}
		
		return NULL;
	}
	
	/**
	 * Gets attributes of the given automatical activation type
	 * 
	 * @param integer $aa_type Automatical activation type
	 * @return null|array
	 */
	public static function get_type_attributes($aa_type)
	{
		if (array_key_exists($aa_type, self::$type_attributes))
		{
			return self::$type_attributes[$aa_type];
		}
		
		return NULL;
	}
	
	/**
	 * Gets attributes count of the given automatical activation type
	 * 
	 * @param integer $aa_type Automatical activation type
	 * @return integer
	 */
	public static function get_type_attributes_count($aa_type)
	{
		if (array_key_exists($aa_type, self::$type_attributes))
		{
			return count(self::$type_attributes[$aa_type]);
		}
		
		return 0;
	}
	
	/**
	 * Gets attribute types.
	 * 
	 * @param boolean $translate
	 * @return array
	 */
	public static function get_attribute_types($translate = TRUE)
	{
		if (!$translate)
		{
			return self::$type_attributes;
		}
		
		$ta = array();		
		
		foreach (self::$type_attributes as $k => $va)
		{
			$i = 0;
			
			foreach ($va as $v)
			{
				$ta[$k][$i] = $v;

				if (isset($v['name']))
				{
					$ta[$k][$i]['name'] = __($v['name']);
				}

				if (isset($v['title']))
				{
					$ta[$k][$i]['title'] = __($v['title']);
				}
				
				$i++;
			}
		}
		
		return $ta;
	}
	
	/**
	 * Gets maximal count of attributes of all types of rule.
	 * 
	 * @return integer
	 */
	public static function get_attribute_types_max_count()
	{
		$max = 0;
		
		foreach (self::$type_attributes as $v)
		{
			$max = max($max, count($v));
		}
		
		return $max;
	}
	
	/**
	 * Gets type of rule.
	 * 
	 * @return integer
	 */
	public abstract function get_type();
	
	/**
	 * Gets first attribute of rule
	 * 
	 * @param integer $index	Index of attribute from 0 [optional - first as default]
	 * @return mixed			Value of attribute or NULL if not valid index
	 */
	public function get_attribute($index = 0)
	{
		$attrs = $this->get_attributes();
		return array_key_exists($index, $attrs) ? $attrs[$index] : NULL;
	}
	
	/**
	 * Gets all attributes of rule.
	 * 
	 * @return array
	 */
	public abstract function get_attributes();
	
	/**
	 * Check all of obtain rules if they may be activated in the given time
	 * and returns all that may be.
	 * 
	 * @param array[TimeActivityRule] $rules Activity rules
	 * @param string $apply_minute Minute of activation as string in format /[0-9]{2}/
	 * @param long $time Time obtain from time() function
	 * @return array[TimeActivityRule] Passed rules
	 */
	public static function filter_rules($rules, $apply_minute, $time)
	{
		// args check
		if (!preg_match('/^[0-9]{2}$/', $apply_minute))
		{
			throw new InvalidArgumentException('Invalid apply minute');
		}
		
		// time properties
		$year = date('Y', $time);
		$month = date('m', $time);
		$day = intval(date('d', $time));
		$hour = date('H', $time);
		$minute = date('i', $time);

		// stupid english format (0 is sunday)
		$day_of_week = intval(date('w', $time));

		if ($day_of_week == 0)
		{
			$day_of_week = 7;
		}
		
		// passed rules
		$passed_rules = array();
		
		// not apply minute
		if ($minute != $apply_minute)
		{
			return $passed_rules;
		}
		
		// check all rules if redir/email/sms should be activated now
		foreach ($rules as $rule)
		{
			$passed = FALSE;
			
			// check valid type
			if (!($rule instanceof Time_Activity_Rule)) continue;

			switch ($rule->get_type())
			{
			case Messages_automatical_activation_Model::TYPE_MONTHLY:
				$days_of_month = date::days_of_month($month, $year);
				$max_day = max(1, min($days_of_month, intval($rule->get_attribute())));
				if ($day == intval($max_day) &&
					intval($hour) == intval($rule->get_attribute(1)))
				{
					$passed = TRUE;
				}
				break;

			case Messages_automatical_activation_Model::TYPE_WEEKLY:
				if ($day_of_week == intval($rule->get_attribute()) &&
					intval($hour) == intval($rule->get_attribute(1)))
				{
					$passed = TRUE;
				}
				break;

			case Messages_automatical_activation_Model::TYPE_DAILY:
				if (intval($rule->get_attribute()) == intval($hour))
					$passed = TRUE;
				break;

			case Messages_automatical_activation_Model::TYPE_DAILY_WD:

				if (intval($rule->get_attribute()) == intval($hour) && $day_of_week <= 5)
					$passed = TRUE;
				break;

			case Messages_automatical_activation_Model::TYPE_HOURLY:
				$passed = TRUE;
				break;

			case Messages_automatical_activation_Model::TYPE_AFTER_DEDUCTION:
				if (Settings::get('deduct_fees_automatically_enabled') &&
					intval($rule->get_attribute()) == intval($hour) &&
					$day == intval(date::get_deduct_day_to($month, $year)))
				{
					$passed = TRUE;
				}
				break;
			}

			// ok apply rule
			if ($passed)
			{
				$passed_rules[] = $rule;
			}
		}

		// return all passed rules
		return $passed_rules;
	}
	
}
