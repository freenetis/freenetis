<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Date helper class.
 *
 * $Id: date.php 1970 2008-02-06 21:54:29Z Shadowhand $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class date
{

    public static $months = array
	(
		1 => 'January',
		2 => 'February',
		3 => 'March',
		4 => 'April',
		5 => 'May',
		6 => 'June',
		7 => 'July',
		8 => 'August',
		9 => 'September',
		10 => 'October',
		11 => 'November',
		12 => 'December'
	);

	/**
	 * Converts a UNIX timestamp to DOS format.
	 *
	 * @param   integer  UNIX timestamp
	 * @return  integer
	 */
	public static function unix2dos($timestamp = FALSE)
	{
		$timestamp = ($timestamp === FALSE) ? getdate() : getdate(intval($timestamp));

		if ($timestamp['year'] < 1980)
		{
			return (1 << 21 | 1 << 16);
		}

		$timestamp['year'] -= 1980;

		// What voodoo is this? I have no idea... Geert can explain it though,
		// and that's good enough for me.
		return ($timestamp['year']    << 25 | $timestamp['mon']     << 21 |
		        $timestamp['mday']    << 16 | $timestamp['hours']   << 11 |
		        $timestamp['minutes'] << 5  | $timestamp['seconds'] >> 1);
	}

	/**
	 * Converts a DOS timestamp to UNIX format.
	 *
	 * @param   integer  DOS timestamp
	 * @return  integer
	 */
	public static function dos2unix($timestamp = FALSE)
	{
		$sec  = 2 * ($timestamp & 0x1f);
		$min  = ($timestamp >>  5) & 0x3f;
		$hrs  = ($timestamp >> 11) & 0x1f;
		$day  = ($timestamp >> 16) & 0x1f;
		$mon  = ($timestamp >> 21) & 0x0f;
		$year = ($timestamp >> 25) & 0x7f;

		return mktime($hrs, $min, $sec, $mon, $day, $year + 1980);
	}

	/**
	 * Returns the offset (in seconds) between two time zones.
	 * @see     http://php.net/timezones
	 *
	 * @param   string          timezone that to find the offset of
	 * @param   string|boolean  timezone used as the baseline
	 * @return  integer
	 */
	public static function offset($remote, $local = TRUE)
	{
		static $offsets;

		// Default values
		$remote = (string) $remote;
		$local  = ($local === TRUE) ? date_default_timezone_get() : (string) $local;

		// Cache key name
		$cache = $remote.$local;

		if (empty($offsets[$cache]))
		{
			// Create timezone objects
			$remote = new DateTimeZone($remote);
			$local  = new DateTimeZone($local);

			// Create date objects from timezones
			$time_there = new DateTime('now', $remote);
			$time_here  = new DateTime('now', $local);

			// Find the offset
			$offsets[$cache] = $remote->getOffset($time_there) - $local->getOffset($time_here);
		}

		return $offsets[$cache];
	}

	/**
	 * Number of seconds in a minute, incrementing by a step.
	 *
	 * @param   integer  amount to increment each step by, 1 to 30
	 * @param   integer  start value
	 * @param   integer  end value
	 * @return  array    A mirrored (foo => foo) array from 1-60.
	 */
	public static function seconds($step = 1, $start = 0, $end = 60)
	{
		// Always integer
		$step = (int) $step;

		$seconds = array();

		for ($i = $start; $i < $end; $i += $step)
		{
			$seconds[$i] = ($i < 10) ? '0'.$i : $i;
		}

		return $seconds;
	}

	/**
	 * Number of minutes in an hour, incrementing by a step.
	 *
	 * @param   integer  amount to increment each step by, 1 to 30
	 * @return  array    A mirrored (foo => foo) array from 1-60.
	 */
	public static function minutes($step = 5)
	{
		// Because there are the same number of minutes as seconds in this set,
		// we choose to re-use seconds(), rather than creating an entirely new
		// function. Shhhh, it's cheating! ;) There are several more of these
		// in the following methods.
		return date::seconds($step);
	}

	/**
	 * Number of hours in a day.
	 *
	 * @param   integer  amount to increment each step by
	 * @param   boolean  use 24-hour time
	 * @param   integer  the hour to start at
	 * @return  array    A mirrored (foo => foo) array from start-12 or start-23.
	 */
	public static function hours($step = 1, $long = FALSE, $start = NULL)
	{
		// Default values
		$step = (int) $step;
		$long = (bool) $long;
		$hours = array();

		// Set the default start if none was specified.
		if (is_null($start))
		{
			$start = ($long == FALSE) ? 1 : 0;
		}

		$hours = array();

		// 24-hour time has 24 hours, instead of 12
		$size = ($long == TRUE) ? 23 : 12;

		for ($i = $start; $i <= $size; $i += $step)
		{
			$hours[$i] = $i;
		}

		return $hours;
	}

	/**
	 * Returns AM or PM, based on a given hour.
	 *
	 * @param   integer  number of the hour
	 * @return  string
	 */
	public static function ampm($hour)
	{
		// Always integer
		$hour = (int) $hour;

		return ($hour > 11) ? 'PM' : 'AM';
	}

	/**
	 * Adjusts a non-24-hour number into a 24-hour number.
	 *
	 * @param   integer  hour to adjust
	 * @param   string   AM or PM
	 * @return  string
	 */
	public static function adjust($hour, $ampm)
	{
		$hour = (int) $hour;
		$ampm = strtolower($ampm);

		switch($ampm)
		{
			case 'am':
				if ($hour == 12)
					$hour = 0;
			break;
			case 'pm':
				if ($hour < 12)
					$hour += 12;
			break;
		}

		return sprintf('%02s', $hour);
	}

	/**
	 * Number of days in month.
	 *
	 * @param   integer  number of month
	 * @param   integer  number of year to check month, defaults to the current year
	 * @return  array    A mirrored (foo => foo) array of the days.
	 */
	public static function days($month, $year = FALSE)
	{
		static $months;

		// Always integers
		$month = (int) $month;
		$year  = (int) $year;

		// Use the current year by default
		$year  = ($year == FALSE) ? date('Y') : $year;

		// We use caching for months, because time functions are used
		if (empty($months[$year][$month]))
		{
			$months[$year][$month] = array();

			// Use date to find the number of days in the given month
			$total = date('t', mktime(1, 0, 0, $month, 1, $year)) + 1;

			try
			{
				for ($i = 1; $i < $total; @$i++)
					@$months[$year][$month][$i] = $i;
			}
			catch (Exceptions $e)
			{

			}
		}

		return $months[$year][$month];
	}

	/**
	 * Returns count of days of given month and year (optional)
	 * Similar to function days, but return only count (not array)
	 *
	 * @author Michal Kliment
	 * @param numeric $month
	 * @param numeric $year
	 * @return numeric
	 */
	public static function days_of_month($month, $year = FALSE)
	{
		// Always integers
		$month = (int) $month;
		$year  = (int) $year;

		// Use the current year by default
		$year  = ($year == FALSE) ? date('Y') : $year;

		// Use date to find the number of days in the given month
		$days = date('t', mktime(1, 0, 0, $month, 1, $year));
		
		return $days;
	}
	
	/**
	 * Returns array of months
	 * 
	 * @param bool $translate	Translate months names
	 * @return array
	 */
	public static function months_array($translate = TRUE)
	{
		if ($translate)
		{
			return array_map('__', self::$months);
		}
		else
		{
			return self::$months;
		}
	}

	/**
	 * Number of months in a year
	 *
	 * @return  array  A mirrored (foo => foo) array from 1-12.
	 */
	public static function months()
	{
		return date::hours();
	}

	/**
	 * Returns an array of years between a starting and ending year.
	 * Uses the current year +/- 5 as the max/min.
	 *
	 * @param   integer  starting year
	 * @param   integer  ending year
	 * @return  array
	 */
	public static function years($start = FALSE, $end = FALSE)
	{
		// Default values
		$start = ($start == FALSE) ? date('Y') - 5 : (int) $start;
		$end   = ($end   == FALSE) ? date('Y') + 5 : (int) $end;

		$years = array();

		// Add one, so that "less than" works
		$end += 1;

		for ($i = $start; $i < $end; $i++)
		{
			$years[$i] = $i;
		}

		return $years;
	}

	/**
	 * Returns time difference between two timestamps, in human readable format.
	 *
	 * @param   integer       timestamp
	 * @param   integer       timestamp, defaults to the current time
	 * @param   string        formatting string
	 * @return  string|array
	 */
	public static function timespan($time1, $time2 = FALSE, $output = 'years,months,weeks,days,hours,minutes,seconds')
	{
		// Default values
		$time1  = max(0, (int) $time1);
		$time2  = ($time2 === FALSE) ? time() : max(0, (int) $time2);

		// Calculate timespan (seconds)
		$timespan = abs($time1 - $time2);

		// Array with the output formats
		$output = preg_split('/[\s,]+/', strtolower((string) $output));
		$output = array_combine($output, $output);

		// Array of diff values
		$timediff = array();

		// Years ago
		if (isset($output['years']))
		{
			// 60 * 60 * 24 * 365
			$year = 31536000;
			$timediff['years'] = (int) floor($timespan / $year);
			$timespan -= $timediff['years'] * $year;
		}

		// Months ago
		if (isset($output['months']))
		{
			// 60 * 60 * 24 * 30
			$month = 2592000;
			$timediff['months'] = (int) floor($timespan / $month);
			$timespan -= $timediff['months'] * $month;
		}

		// Weeks ago
		if (isset($output['weeks']))
		{
			// 60 * 60 * 24 * 7
			$week = 604800;
			$timediff['weeks'] = (int) floor($timespan / $week);
			$timespan -= $timediff['weeks'] * $week;
		}

		// Days ago
		if (isset($output['days']))
		{
			// 60 * 60 * 24
			$day = 86400;
			$timediff['days'] = (int) floor($timespan / $day);
			$timespan -= $timediff['days'] * $day;
		}

		// Hours ago
		if (isset($output['hours']))
		{
			// 60 * 60
			$hour = 3600;
			$timediff['hours'] = (int) floor($timespan / $hour);
			$timespan -= $timediff['hours'] * $hour;
		}

		// Minutes ago
		if (isset($output['minutes']))
		{
			// 60
			$minute = 60;
			$timediff['minutes'] = (int) floor($timespan / $minute);
			$timespan -= $timediff['minutes'] * $minute;
		}

		// Seconds ago
		if (isset($output['seconds']))
		{
			$timediff['seconds'] = $timespan;
		}

		// Invalid output formats string
		if (empty($timediff))
			return FALSE;

		// If only one output format was asked, don't put it in an array
		if (count($timediff) == 1)
			return current($timediff);

		// Return array
		return $timediff;
	}

    /**
     * Round date to whole months, if count of days is more or equal to 15,
	 * increments count of months
     * 
	 * @param integer $day
     * @param integer $month
     * @param integer $year
     * @return string
     */

    public static function round_month($day = 1, $month = 1, $year = 1970, $on_last_day = false)
    {
		$day = intval($day);
		$month = intval($month);
		$year = intval($year);
		
        if ($day > 15)
        {
            $month++;
        }
		
        $day = 1;
		
        if ($month > 12)
        {
            $year++;
            $month = 1;
        }

        if ($on_last_day)
        {
            $month--;

            if ($month==0)
            {
                $month = 12;
                if ($year) $year--;
            }
            $day = count(date::days($month,$year));
        }

        return date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
    }

    /**
     * Function is similar to round_month, except it gets date in date format.
     * @author Jiri Svitak
     * @param date
     * @return unknown_type
     */
    public static function month($date = '0000-00-00')
    {
        // parsed date
        $pd = date_parse($date);
        return date::round_month($pd['day'],$pd['month'],$pd['year']);
    }

    /**
     * Function returns date of new deduct from the given date.
	 * 
     * If given day is before and including deduct day, then deduct day of
	 * current month is returned.
     * If given day is after deduct day, then deduct day of next month is returned.
	 * 
     * @author Ondrej Fibich
     * @param string $date
     * @return string
     */
	public static function get_closses_deduct_date_to($date)
	{
        // parsed date
        $parsed_date = date_parse($date);
        $day = $parsed_date['day'];
        $month = $parsed_date['month'];
        $year = $parsed_date['year'];
		// deduct day
		$deduct_day = date::get_deduct_day_to($month, $year);
		
        if ($day >= $deduct_day)
        {
			$month++;
		}
		
        if ($month == 13)
        {
        	$month = 1;
        	$year++;
        }
		
		$deduct_day2 = date::get_deduct_day_to($month, $year);
		
        // returns boundary date of month
		return date('Y-m-d', mktime(0, 0, 0, $month, $deduct_day2, $year));
	}

    /**
     * Function returns next date of deduct from the given date.
     * Next means that next mmonth deduct date is calculated.
     *
     * @author Ondřej Fibich <fibich@freenetis.org>
     * @since 1.1.10
     *
     * @param string $date input deduct date
     * @return string next deduct date from next month
     */
    public static function get_next_deduct_date_to($date)
    {
        $d_arr = date_parse($date);
        // increase month
        $d_arr['month']++;
        if ($d_arr['month'] > 12)
        {
            $d_arr['month'] = 1;
            $d_arr['year']++;
        }
        // get deduct day for increased month
        $d_arr['day'] = date::get_deduct_day_to($d_arr['month'], $d_arr['year']);
        // create new date
        return date::create($d_arr['day'], $d_arr['month'], $d_arr['year']);
    }

    /**
	 * Calculate deduct day of given month. 
	 * 
	 * @author Ondrej Fibich
	 * @param integer $month
	 * @param integer $year
	 * @return string
	 */
	public static function get_deduct_day_to($month, $year)
	{
		return max(1, min(
				Settings::get('deduct_day'),
				date::days_of_month($month, $year)
		));
	}

    /**
     * Function to finding difference between 2 dates
     * @param $date_a date in format 'YYYY-mm-dd'
     * @param $date_b date in format 'YYYY-mm-dd'
     * @return <type>
     */

    public static function diff_month($date_a = '0000-00-00', $date_b = '0000-00-00')
    {
        $year_a = (int) substr($date_a,0,4);
        $year_b = (int) substr($date_b,0,4);
        $month_a = (int) substr($date_a,5,2);
        $month_b = (int) substr($date_b,5,2);

        return ($year_a * 12 + $month_a) - ($year_b * 12 + $month_b);
    }

    /**
     * @author Michal Kliment
     * Creates date in format 'YYYY-mm-dd' from day, month and year
     * @param $day
     * @param $month
     * @param $year
     * @return date
     */
    public static function create($day, $month, $year, $hour = 0, $minute = 0, $second = 0, $datetime = FALSE)
    {
        $date = num::null_fill($year,4).'-';

        $date .= num::null_fill($month,2).'-';
        $date .= num::null_fill($day,2);

		if ($datetime)
		{
			$date .= ' '.num::null_fill($hour,2).':';
			$date .= num::null_fill($minute,2).':';
			$date .= num::null_fill($second,2);
		}

        return $date;
    }

	/**
	 * Function to return date in pretty format from datetime
	 * 
	 * @author Michal Kliment
	 * @param string $datetime
	 * @return string
	 */
	public static function pretty($datetime)
	{
		$day = (int) substr($datetime, 8, 2);
		$month = (int) substr($datetime, 5, 2);
		$year = (int) substr($datetime, 0, 4);

		$date = ($day) ? $day. '. ' : '';
		$date .= ($month) ? $month. '. ' : '';
		$date .= ($year) ? $year : '';

		return $date;
	}

	public static function pretty_time($datetime)
	{
		return substr($datetime, 11,8);
	}

    public static function pretty_month($datetime = '0000-00-00')
    {
        $pd = date_parse(date::month($datetime));
        $month = ($pd['month'] < 10) ? '0' . $pd['month'] : $pd['month'];
        return $month.'/'.$pd['year'];
    }

    public static function from_interval($interval = 0, $unit = 'hours')
    {
	    $years = 0;
	    $months = 0;
	    $days = 0;
	    $hours = 0;
	    $minutes = 0;
	    $seconds = 0;

	    if ($unit == 'seconds')
		    $seconds = $interval;

	    $minutes = floor ($seconds / 60);
	    $seconds = $seconds - $minutes*60;

	    if ($unit == 'minutes')
		    $minutes = $interval;

	    $hours = floor($minutes / 60);
	    $minutes = $minutes - $hours*60;

	    if ($unit == 'hours')
		    $hours = $interval;

	    $days = floor ($hours / 24);
	    $hours = $hours - $days*24;

	    if ($unit == 'days')
		    $days = $interval;

	    $months = floor ($days / 30);
	    $days = $days - $months*30;

	    if ($unit == 'months')
		    $months = $interval;

	    $years = floor($months / 12);
	    $months = $months - $years*12;

	   if ($unit == 'years')
		    $years = $interval;

	   if ($years > 9999)
			$years = 9999;

	   return date::create($days, $months, $years, $hours, $minutes, $seconds, TRUE);
    }

	/**
	 * Function to get time for mail (Google style)
	 * 
	 * @author Michal Kliment
	 * @param string $datetime
	 * @return string
	 */
	public static function mail_time($datetime)
	{
		// current time
		$current_day = date('j');
		$current_month = date('n');
		$current_year = date('Y');

		// parse date
		$date = date_parse($datetime);

		// not same year, print whole date with year
		if ($date['year'] != $current_year)
			return $date['day'].'.'.$date['month'].'.'.substr($date['year'], 2, 2);
		// not same day, print whole date without year
		else if ($date['month'] != $current_month || $date['day'] != $current_day)
			return $date['day'].'.'.$date['month'].'.';
		// same day, print only time
		else
			return $date['hour'].':'.num::null_fill($date['minute'], 2);
        }

	/**
	 * Returns diff between 2 datetimes
	 * 
	 * @author Michal Kliment
	 * @param string $date1
	 * @param string $date2
	 * @return number
	 */
	public static function diff($date1, $date2)
	{
		// parse dates
		$pd1 = date_parse($date1);
		$pd2 = date_parse($date2);

		// make times form dates
		$time1 = mktime($pd1['hour'], $pd1['minute'], $pd1['second'], $pd1['day'], $pd1['month'], $pd1['year']);
		$time2 = mktime($pd2['hour'], $pd2['minute'], $pd2['second'], $pd2['day'], $pd2['month'], $pd2['year']);

		if ($time1 > $time2)
			$diff = $time1 - $time2;
		else
			$diff = $time2 - $time1;

		return $diff;
	}

	/**
	 * Returns interval (in array) between 2 datetimes
	 * 
	 * @author Michal Kliment
	 * @param string $date1
	 * @param string $date2
	 * @return array
	 */
	public static function interval($date1, $date2 = '0000-00-00 00:00:00')
	{
		// vars declaration
		$interval = array(
		    // years
		    'y' => 0,
		    // months
		    'm' => 0,
		    // days
		    'd' => 0,
		    // hours
		    'h' => 0,
		    // minutes
		    'i' => 0,
		    // seconds
		    's' => 0,
		    // days
		    'days' => 0
		);

		// parse dates
		$pd1 = date_parse($date1);
		$pd2 = date_parse($date2);

		//convert time from string to int
		$time1 = $pd1['hour']*60*60 + $pd1['minute']*60 + $pd1['second'];
		$time2 = $pd2['hour']*60*60 + $pd2['minute']*60 + $pd2['second'];

		if ($time2 > $time1)
		{
			$diff = $time2-$time1;
			$invert_hour = floor($diff/3600);
			$diff %= 3600;
			$invert_minute = floor($diff/60);
			$invert_second = $diff % 60;

			$interval['s'] = 60 - $invert_second;
			$interval['i'] = 60 - $invert_minute;
			if ($interval['s'])
			    $interval['i']--;
			$interval['h'] = 24 - $invert_hour;
			if ($interval['i'])
			    $interval['h']--;
		}
		else
		{
			$diff = $time1-$time2;
			$interval['h'] = floor($diff/3600);
			$diff %= 3600;
			$interval['i'] = floor($diff/60);
			$interval['s'] = $diff % 60;
		}

		if (($pd2['day'] < $pd1['day']))
		{
			$interval['d'] = $pd1['day'] - $pd2['day'];
		}
		else if (($pd2['day'] > $pd1['day']) || $pd2['day'] > $pd1['day'] && $time2 > $time1)
		{
			$interval['d'] = count(date::days($pd2['month'],$pd2['year'])) - $pd2['day'] + $pd1['day'];
			$pd2['month']++;
		}

		if ($time2 > $time1)
			$interval['d']--;

		$interval['days'] = $interval['d'];

		while($pd2['year'] <= $pd1['year'])
		{
			while(($pd2['year'] < $pd1['year'] && $pd2['month'] <= 12) || ($pd2['year'] == $pd1['year'] && $pd2['month'] < $pd1['month']))
			{
				$interval['days'] += date::days_of_month($pd2['month'],$pd2['year']);
				$interval['m']++;
				$pd2['month']++;
			}
			$pd2['month'] = 1;
			$pd2['year']++;
		}

		$interval['y'] = floor($interval['m'] / 12);
		$interval['m'] %= 12;

		return $interval;
	}

	/**
	 * @author Michal Kliment
	 * Function to get day diff between 2 datetimes
	 * @param string $datetime
	 * @return number
	 */
	public static function day_diff($date1, $date2 = '0000-00-00 00:00:00')
	{
		$interval = date::interval($date1, $date2);
		return $interval['days'];
	}

	/**
	 * @author Michal Kliment
	 * Function to get hour diff between 2 datetimes
	 * @param string $datetime
	 * @return number
	 */
	public static function hour_diff($date1, $date2 = '0000-00-00 00:00:00')
	{
		$interval = date::interval($date1, $date2);
		return $interval['days']*24 + $interval['h'];
	}

	/**
	 * @author Michal Kliment
	 * Function to get minute diff between 2 datetimes
	 * @param string $datetime
	 * @return number
	 */
	public static function minute_diff($date1, $date2 = '0000-00-00 00:00:00')
	{
		$interval = date::interval($date1, $date2);
		return ($interval['days']*24 + $interval['h'])*60 + $interval['i'];
	}

	/**
	 * Returns counter for timestamp
	 *
	 * @author Michal Kliment
	 * @param integer $timestamp
	 * @return string
	 */
	public static function counter($timestamp)
	{
		$seconds = num::negation($timestamp % 60, 0, 0, -1);
		$timestamp = ($timestamp - $seconds) / 60;

		$minutes = num::negation($timestamp % 60, 0, 0, -1);
		$timestamp = ($timestamp - $minutes) / 60;

		$hours = num::negation($timestamp % 24, 0, 0, -1);
		$timestamp = ($timestamp - $hours) / 24;

		return num::null_fill($hours,2).':'.num::null_fill($minutes,2).':'.num::null_fill($seconds,2);
	}
	
	/**
	 * Finds start of week and return it in given format
	 * 
	 * @author Michal Kliment
	 * @param type $week
	 * @param type $year
	 * @param type $format
	 * @return type 
	 */
	public static function start_of_week ($week, $year = NULL, $format = 'Y-m-d')
	{
		if (!$year)
			$year = date("Y");
		
		$ts = strtotime($year."-01-04 + ".($week-1)." weeks");
		
		while (date('l', $ts) != 'Monday')
		{ 
			$ts = strtotime('-1 day', $ts); 
		}
		
		return date($format, $ts); 
	}
	
	/**
	 * Finds end of week and return it in given format
	 * 
	 * @author Michal Kliment
	 * @param type $week
	 * @param type $year
	 * @param type $format
	 * @return type 
	 */
	public function end_of_week ($week, $year = NULL, $format = 'Y-m-d')
	{
		if (!$year)
			$year = date("Y");
		
		$ts = strtotime($year."-01-04 + ".($week-1)." weeks");
		
		while (date('l', $ts) != 'Sunday')
		{ 
			$ts = strtotime('+1 day', $ts); 
		}
		
		return date($format, $ts); 
	}

	/**
	 * Decreases given date by one day. Used in Fio automatic import.
	 * @author Jiri Svitak
	 * @param <type> $year
	 * @param <type> $month
	 * @param <type> $day
	 */
	public static function decrease_day($year, $month, $day)
	{
		$day = (int) $day;
		$month = (int) $month;
		$year = (int) $year;
		
		$day -= 1;
		if ($day == 0)
		{
			$month -= 1;
			if ($month == 0)
			{
				$year -= 1;
				$month = 12;
			}
			if ($month == 2)
			{
				if (($year % 4 == 0) && ($year % 400 != 0))
				{
					$day = 29;
				}
				else
				{
					$day = 28;
				}
			}
			else if ($month == 1 || $month == 3 || $month == 5 || $month == 7 || $month == 8 || $month == 10 || $month == 12)
			{
				$day = 31;
			}
			else
			{
				$day = 30;
			}
		}
		$timestamp = mktime(0, 0, 0, $month, $day, $year);
		return date('Y-m-d', $timestamp);
	}

} // End date
