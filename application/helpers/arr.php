<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Array helper class.
 *
 * $Id: arr.php 3917 2009-01-21 03:06:22Z zombor $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class arr {

	/**
	 * Return a callback array from a string, eg: limit[10,20] would become
	 * array('limit', array('10', '20'))
	 *
	 * @param   string  callback string
	 * @return  array
	 */
	public static function callback_string($str)
	{
		// command[param,param]
		if (preg_match('/([^\[]*+)\[(.+)\]/', (string) $str, $match))
		{
			// command
			$command = $match[1];

			// param,param
			$params = preg_split('/(?<!\\\\),/', $match[2]);
			$params = str_replace('\,', ',', $params);
		}
		else
		{
			// command
			$command = $str;

			// No params
			$params = NULL;
		}

		return array($command, $params);
	}

	/**
	 * Rotates a 2D array clockwise.
	 * Example, turns a 2x3 array into a 3x2 array.
	 *
	 * @param   array    array to rotate
	 * @param   boolean  keep the keys in the final rotated array. the
	 *					 sub arrays of the source array need to have the same key values.
	 *                   if your subkeys might not match, you need to pass FALSE here!
	 * @return  array
	 */
	public static function rotate($source_array, $keep_keys = TRUE)
	{
		$new_array = array();
		
		if (is_array($source_array))
		{
			foreach ($source_array as $key => $value)
			{
				$value = ($keep_keys === TRUE) ? $value : array_values($value);
				foreach ($value as $k => $v)
				{
					$new_array[$k][$key] = $v;
				}
			}
		}

		return $new_array;
	}

	/**
	 * Removes a key from an array and returns the value.
	 *
	 * @param   string  key to return
	 * @param   array   array to work on
	 * @return  mixed   value of the requested array key
	 */
	public static function remove($key, & $array)
	{
		if ( ! is_array($array))
			return NULL;
		
		if ( ! array_key_exists($key, $array))
			return NULL;

		$val = $array[$key];
		unset($array[$key]);

		return $val;
	}


	/**
	 * Extract one or more keys from an array. Each key given after the first
	 * argument (the array) will be extracted. Keys that do not exist in the
	 * search array will be NULL in the extracted data.
	 *
	 * @param   array   array to search
	 * @param   string  key name
	 * @return  array
	 */
	public static function extract($search, $keys)
	{
		// Get the keys, removing the $search array
		$keys = array_slice(func_get_args(), 1);

		$found = array();
		
		if ( ! is_array($search))
			return NULL;
		
		foreach ($keys as $key)
		{
			if (isset($search[$key]))
			{
				$found[$key] = $search[$key];
			}
			else
			{
				$found[$key] = NULL;
			}
		}

		return $found;
	}

	/**
	 * Because PHP does not have this function.
	 *
	 * @param   array   array to unshift
	 * @param   string  key to unshift
	 * @param   mixed   value to unshift
	 * @return  array
	 */
	public static function unshift_assoc( & $array, $key, $val)
	{
		if ( ! is_array($array))
			return array();
		
		$array = array_reverse($array, TRUE);
		$array[$key] = $val;
		$array = array_reverse($array, TRUE);

		return $array;
	}

	/**
	 * Because PHP does not have this function, and array_walk_recursive creates
	 * references in arrays and is not truly recursive.
	 *
	 * @param   mixed  callback to apply to each member of the array
	 * @param   array  array to map to
	 * @return  array
	 */
	public static function map_recursive($callback, $array)
	{
		if ( ! is_array($array))
			return NULL;
		
		foreach ($array as $key => $val)
		{
			// Map the callback to the key
			$array[$key] = is_array($val) ? arr::map_recursive($callback, $val) : call_user_func($callback, $val);
		}

		return $array;
	}

	/**
	 * Binary search algorithm.
	 *
	 * @param   mixed    the value to search for
	 * @param   array    an array of values to search in
	 * @param   boolean  return false, or the nearest value
	 * @param   mixed    sort the array before searching it
	 * @return  integer
	 */
	public static function binary_search($needle, $haystack, $nearest = FALSE, $sort = FALSE)
	{
		if ( ! is_array($haystack))
			return NULL;
		
		if ($sort === TRUE)
		{
			sort($haystack);
		}

		$high = count($haystack);
		$low = 0;

		while ($high - $low > 1)
		{
			$probe = ($high + $low) / 2;
			if ($haystack[$probe] < $needle)
			{
				$low = $probe;
			}
			else
			{
				$high = $probe;
			}
		}

		if ($high == count($haystack) OR $haystack[$high] != $needle)
		{
			if ($nearest === FALSE)
				return FALSE;

			// return the nearest value
			$high_distance = $haystack[ceil($low)] - $needle;
			$low_distance = $needle - $haystack[floor($low)];

			return ($high_distance >= $low_distance) ? $haystack[ceil($low)] : $haystack[floor($low)];
		}

		return $high;
	}

	/**
	 * Emulates array_merge_recursive, but appends numeric keys and replaces
	 * associative keys, instead of appending all keys.
	 *
	 * @param   array  any number of arrays
	 * @return  array
	 */
	public static function merge()
	{
		$total = func_num_args();

		$result = array();
		for ($i = 0; $i < $total; $i++)
		{
			foreach (func_get_arg($i) as $key => $val)
			{
				if (isset($result[$key]))
				{
					if (is_array($val))
					{
						// Arrays are merged recursively
						$result[$key] = arr::merge($result[$key], $val);
					}
					elseif (is_int($key))
					{
						// Indexed arrays are appended
						array_push($result, $val);
					}
					else
					{
						// Associative arrays are replaced
						$result[$key] = $val;
					}
				}
				else
				{
					// New values are added
					$result[$key] = $val;
				}
			}
		}

		return $result;
	}

	/**
	 * Overwrites an array with values from input array(s).
	 * Non-existing keys will not be appended!
	 *
	 * @param   array   key array
	 * @param   array   input array(s) that will overwrite key array values
	 * @return  array
	 */
	public static function overwrite($array1)
	{
		if ( ! is_array($array1))
			return NULL;
		
		foreach (array_slice(func_get_args(), 1) as $array2)
		{
			foreach ($array2 as $key => $value)
			{
				if (array_key_exists($key, $array1))
				{
					$array1[$key] = $value;
				}
			}
		}

		return $array1;
	}

	/**
	 * Fill an array with a range of numbers.
	 *
	 * @param   integer  stepping
	 * @param   integer  ending number
	 * @return  array
	 */
	public static function range($step = 10, $max = 100)
	{
		if ($step < 1)
			return array();

		$array = array();
		for ($i = $step; $i <= $max; $i += $step)
		{
			$array[$i] = $i;
		}

		return $array;
	}

	/**
	 * Recursively convert an array to an object.
	 *
	 * @param   array   array to convert
	 * @return  object
	 */
	public static function to_object(array $array, $class = 'stdClass')
	{
		$object = new $class;
		
		foreach ($array as $key => $value)
		{
			if (is_array($value))
			{
				// Convert the array to an object
				$value = arr::to_object($value, $class);
			}

			// Add the value to the object
			$object->{$key} = $value;
		}

		return $object;
	}

	/**
	 * Creates array from object
	 *
	 * @author Michal Kliment
	 * @param mixed $object
	 * @return array
	 */
	public static function from_object ($object)
	{
		if ( ! is_object($object))
			return array();
		
		$arr = array();
		foreach ($object as $key => $value)
			$arr[$key] = $value;

		return $arr;
	}

	/**
	 * Transforms array to lower
	 *
	 * @author Michal Kliment
	 * @param array $array
	 * @return array
	 */
	public static function tolower ($array = array())
	{
		if ( ! is_array($array))
			return array();
		
		foreach ($array as $key => $value)
			$array[$key] = strtolower ($value);

		return $array;
	}

    /**
     * Converts array of objects to classic array in format: key = id of object, value = attributte
	 * 
     * @author Michal Kliment
     * @param $objects array of object to convert
     * @param $attribute name of attributte to store as value of array
     * @return array
     */
    public static function from_objects ($objects, $attribute = 'name')
    {
		if ( ! is_array($objects) && ! is_object($objects))
			return array();
		
        $array = array();
        foreach ($objects as $object)
        {
            $array[$object->id] = $object->$attribute;
        }

        return $array;
    }

    /**
     * Creates array from string
     *
     * @author Michal Kliment
     * @param string $string
     * @param integer $length
     * @param integer $offset
     * @return array
     */
    public static function from_string ($string, $length, $offset = 0)
    {
		$array = array();

		for ($i = $offset; $i < ($offset+$length); $i++)
				$array[$i] = $string;

		return $array;
    }

    /**
     * Returns variation of array
     *
     * @author Michal Kliment
     * @param array $array
     * @param integer $class
     * @return array
     */
    public static function variation ($array, $class)
    {
		if ( ! is_array($array))
			return array();
		
	    $arr = array();

	    if ($class)
	    {
			$ba = arr::variation ($array, $class-1);

			foreach ($array as $a)
			{
				foreach ($ba as $b)
				{
					if (!empty($a) && (empty($b) || strpos($b, $a) === FALSE))
					{
						$arr[] = trim("$a $b");
					}
				}
			}
	    }
	    else
		    $arr[] = "";
	    
	    return $arr;
    }

    /**
     * Searchs in array
     *
     * @author Michal Kliment
     * @param string $needle
     * @param array $haystack
     * @return mixed
     */
    public static function search ($needle, $haystack)
    {
		if ( ! is_array($haystack))
			return array();
		
		$key = array_search($needle, $haystack);
		if ($key !== FALSE)
				return $key;

		foreach ($haystack as $key => $value)
		{
				if (is_array($value))
						if (arr::search ($needle, $value) !== FALSE)
								return $key;
		}

		return FALSE;
    }
	
	/**
	 * Sorts array, almost same as default PHP sort function,
	 * but it doesn't edit original array and returns new array
	 * 
	 * @author Michal Kliment
	 * @param array $array
	 * @return array 
	 */
	public static function sort ($array)
	{
		// has to be array
		if (!is_array($array))
			$array = (array) $array;
		
		sort ($array);
		
		foreach ($array as $key => $val)
		{
			if (is_array($val))
				$array[$key] = arr::sort ($val);
		}
		
		return $array;
	}
	
	/**
	 * Similar to sort, but sort by key
	 * 
	 * @author Michal Kliment
	 * @param type $array
	 * @return type 
	 */
	public static function ksort ($array)
	{
		// has to be array
		if (!is_array($array))
			$array = (array) $array;
		
		ksort ($array);
		
		foreach ($array as $key => $val)
		{
			if (is_array($val))
				$array[$key] = arr::ksort ($val);
		}
		
		return $array;
	}
	
	/**
	 * Returns key with minimal value from array
	 * 
	 * @author Michal Kliment
	 * @param type $array
	 * @return type 
	 */
	public static function min_key ($array)
	{
		return min(array_keys($array));
	}
	
	/**
	 * Returns key with maximal value from array
	 * 
	 * @author Michal Kliment
	 * @param type $array
	 * @return type 
	 */
	public static function max_key ($array)
	{
		return max(array_keys($array));
	}
	
	/**
	 * Return translated bool array (yes, no)
	 *
	 * @author Ondřej Fibich
	 * @return array
	 */
	public static function bool()
	{
		return array
		(
			1 => __('Yes'),
			0 => __('No')
		);
	}
	
	/**
	 * Return reverted translated bool array (no, yes)
	 *
	 * @author Ondřej Fibich
	 * @return array
	 */
	public static function rbool()
	{
		return array
		(
			0 => __('No'),
			1 => __('Yes')
		);
	}

} // End arr