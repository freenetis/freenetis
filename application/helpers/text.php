<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Text helper class.
 *
 * $Id: text.php 1762 2008-01-21 10:59:41Z PugFish $
 *
 * @package    Text Helper
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class text {
	
	const TAB							=	"\t";
	const EOL							=	"\n";

	/**
	 * Limits a phrase to a given number of words.
	 *
	 * @param   string   phrase to limit words of
	 * @param   integer  number of words to limit to
	 * @param   string   end character or entity
	 * @return  string
	 */
	public static function limit_words($str, $limit = 100, $end_char = NULL)
	{
		$limit = (int) $limit;
		$end_char = ($end_char === NULL) ? '&#8230;' : $end_char;

		if (trim($str) == '')
			return $str;

		if ($limit <= 0)
			return $end_char;

		preg_match('/^\s*+(?:\S++\s*+){1,'.$limit.'}/u', $str, $matches);

		// Only attach the end character if the matched string is shorter
		// than the starting string.
		return rtrim($matches[0]).(strlen($matches[0]) == strlen($str) ? '' : $end_char);
	}

	/**
	 * Limits a phrase to a given number of characters.
	 *
	 * @param   string   phrase to limit characters of
	 * @param   integer  number of characters to limit to
	 * @param   string   end character or entity
	 * @param   boolean  enable or disable the preservation of words while limiting
	 * @return  string
	 */
	public static function limit_chars($str, $limit = 100, $end_char = NULL, $preserve_words = FALSE)
	{
		$end_char = ($end_char === NULL) ? '&#8230;' : $end_char;

		$limit = (int) $limit;

		if (trim($str) == '' OR utf8::strlen($str) <= $limit)
			return $str;

		if ($limit <= 0)
			return $end_char;

		if ($preserve_words == FALSE)
		{
			return rtrim(utf8::substr($str, 0, $limit)).$end_char;
		}

		preg_match('/^.{'.($limit - 1).'}\S*/us', $str, $matches);

		return rtrim($matches[0]).(strlen($matches[0]) == strlen($str) ? '' : $end_char);
	}

	/**
	 * Alternates between two or more strings.
	 *
	 * @param   string  strings to alternate between
	 * @return  string
	 */
	public static function alternate()
	{
		static $i;

		if (func_num_args() == 0)
		{
			$i = 0;
			return '';
		}

		$args = func_get_args();
		return $args[($i++ % count($args))];
	}

	/**
	 * Generates a random string of a given type and length.
	 *
	 * @param   string   a type of pool, or a string of characters to use as the pool
	 * @param   integer  length of string to return
	 * @return  string
	 *
	 * @tutorial  unique  - 40 character unique hash
	 * @tutorial  alnum   - alpha-numeric characters
	 * @tutorial  alpha   - alphabetical characters
	 * @tutorial  numeric - digit characters, 0-9
	 * @tutorial  nozero  - digit characters, 1-9
	 */
	public static function random($type = 'alnum', $length = 8)
	{
		switch ($type)
		{
			case 'unique':
				return sha1(uniqid(NULL, TRUE));
			case 'alnum':
				$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
			case 'alpha':
				$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
			case 'numeric':
				$pool = '0123456789';
			break;
			case 'nozero':
				$pool = '123456789';
			break;
			default:
				$pool = (string) $type;
			break;
		}

		$str = '';
		$pool_size = utf8::strlen($pool);

		for ($i = 0; $i < $length; $i++)
		{
			$str .= utf8::substr($pool, mt_rand(0, $pool_size - 1), 1);
		}

		return $str;
	}

	/**
	 * Reduces multiple slashes in a string to single slashes.
	 *
	 * @param   string  string to reduce slashes of
	 * @return  string
	 */
	public static function reduce_slashes($str)
	{
		return preg_replace('#(?<!:)//+#', '/', $str);
	}

	/**
	 * Replaces the given words with a string.
	 *
	 * @param   string   phrase to replace words in
	 * @param   array    words to replace
	 * @param   string   replacement string
	 * @param   boolean  replace words across word boundries (space, period, etc)
	 * @return  string
	 */
	public static function censor($str, $badwords, $replacement = '#', $replace_partial_words = FALSE)
	{
		foreach ((array) $badwords as $key => $badword)
		{
			$badwords[$key] = str_replace('\*', '\S*?', preg_quote((string) $badword));
		}

		$regex = '('.implode('|', $badwords).')';

		if ($replace_partial_words == TRUE)
		{
			// Just using \b isn't sufficient when we need to replace a badword that already contains word boundaries itself
			$regex = '(?<=\b|\s|^)'.$regex.'(?=\b|\s|$)';
		}

		$regex = '!'.$regex.'!ui';

		if (utf8::strlen($replacement) == 1)
		{
			$regex .= 'e';
			return preg_replace($regex, 'str_repeat($replacement, utf8::strlen(\'$1\')', $str);
		}

		return preg_replace($regex, $replacement, $str);
	}

	/**
	 * Returns human readable sizes.
	 * @see  Based on original functions written by:
	 * @see  Aidan Lister: http://aidanlister.com/repos/v/function.size_readable.php
	 * @see  Quentin Zervaas: http://www.phpriot.com/d/code/strings/filesize-format/
	 *
	 * @param   integer  size in bytes
	 * @param   string   a definitive unit
	 * @param   string   the return string format
	 * @param   boolean  whether to use SI prefixes or IEC
	 * @return  string
	 */
	public static function bytes($bytes, $force_unit = NULL, $format = NULL, $si = TRUE)
	{
		// Format string
		$format = ($format === NULL) ? '%01.2f %s' : (string) $format;

		// IEC prefixes (binary)
		if ($si == FALSE OR strpos($force_unit, 'i') !== FALSE)
		{
			$units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
			$mod   = 1024;
		}
		// SI prefixes (decimal)
		else
		{
			$units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
			$mod   = 1000;
		}

		// Determine unit to use
		if (($power = array_search((string) $force_unit, $units)) === FALSE)
		{
			$power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
		}

		return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
	}

	/**
	 * Prevents widow words by inserting a non-breaking space between the last two words.
	 * @see  http://www.shauninman.com/archive/2006/08/22/widont_wordpress_plugin
	 *
	 * @param   string  string to remove widows from
	 * @return  string
	 */
	public function widont($str)
	{
		$str = rtrim($str);
		$space = strrpos($str, ' ');

		if ($space !== FALSE)
		{
			$str = substr($str, 0, $space).'&nbsp;'.substr($str, $space + 1);
		}

		return $str;
	}

	/**
	 * Czech & Slovak diacritical chars => ASCII by dgx
	 * -------------------------------------------------
	 * WINDOWS-1250 to ASCII for diacritic chars
	 *
	 * This source file is subject to the GNU GPL license.
	 *
	 * @author     David Grudl <david@grudl.com>
	 * @link       http://davidgrudl.com/
	 * @copyright  Copyright (c) 2006 David Grudl
	 * @license    GNU GENERAL PUBLIC LICENSE
	 * @category   Text
	 * @version    1.0
	 */
	public static function cs_win2ascii($s)
	{
		return strtr(
				$s, "\xe1\xe4\xe8\xef\xe9\xec\xed\xbe\xe5\xf2\xf3\xf6\xf5" .
				"\xf4\xf8\xe0\x9a\x9d\xfa\xf9\xfc\xfb\xfd\x9e\xc1\xc4\xc8\xcf\xc9" .
				"\xcc\xcd\xbc\xc5\xd2\xd3\xd6\xd5\xd4\xd8\xc0\x8a\x8d\xda\xd9\xdc" .
				"\xdb\xdd\x8e", "aacdeeillnoooorrstuuuuyzAACDEEILLNOOOORRSTUUUUYZ"
		);

	}
	
	/**
	 * Czech & Slovak diacritical chars => ASCII by dgx
	 * -------------------------------------------------
	 * ISO-8859-2 to ASCII for diacritic chars
	 *
	 * This source file is subject to the GNU GPL license.
	 *
	 * @author     David Grudl <david@grudl.com>
	 * @link       http://davidgrudl.com/
	 * @copyright  Copyright (c) 2006 David Grudl
	 * @license    GNU GENERAL PUBLIC LICENSE
	 * @category   Text
	 * @version    1.0
	 */
	public static function cs_iso2ascii($s)
	{
		return strtr(
				$s, "\xe1\xe4\xe8\xef\xe9\xec\xed\xb5\xe5\xf2\xf3\xf6\xf5\xf4" .
				"\xf8\xe0\xb9\xbb\xfa\xf9\xfc\xfb\xfd\xbe\xc1\xc4\xc8\xcf\xc9" .
				"\xcc\xcd\xa5\xc5\xd2\xd3\xd6\xd5\xd4\xd8\xc0\xa9\xab\xda\xd9" .
				"\xdc\xdb\xdd\xae",
				"aacdeeillnoooorrstuuuuyzAACDEEILLNOOOORRSTUUUUYZ"
		);

	}
	
	/**
	 * Czech & Slovak diacritical chars => ASCII by dgx
	 * -------------------------------------------------
	 * UTF-8 to ASCII for diacritic chars
	 *
	 * This source file is subject to the GNU GPL license.
	 *
	 * @author     David Grudl <david@grudl.com>
	 * @link       http://davidgrudl.com/
	 * @copyright  Copyright (c) 2006 David Grudl
	 * @license    GNU GENERAL PUBLIC LICENSE
	 * @category   Text
	 * @version    1.0
	 */
	public static function cs_utf2ascii($s)
	{
		static $tbl = array
		(
			"\xc3\xa1"=>"a",
			"\xc3\xa4"=>"a",
			"\xc4\x8d"=>"c",
			"\xc4\x8f"=>"d",
			"\xc3\xa9"=>"e",
			"\xc4\x9b"=>"e",
			"\xc3\xad"=>"i",
			"\xc4\xbe"=>"l",
			"\xc4\xba"=>"l",
			"\xc5\x88"=>"n",
			"\xc3\xb3"=>"o",
			"\xc3\xb6"=>"o",
			"\xc5\x91"=>"o",
			"\xc3\xb4"=>"o",
			"\xc5\x99"=>"r",
			"\xc5\x95"=>"r",
			"\xc5\xa1"=>"s",
			"\xc5\xa5"=>"t",
			"\xc3\xba"=>"u",
			"\xc5\xaf"=>"u",
			"\xc3\xbc"=>"u",
			"\xc5\xb1"=>"u",
			"\xc3\xbd"=>"y",
			"\xc5\xbe"=>"z",
			"\xc3\x81"=>"A",
			"\xc3\x84"=>"A",
			"\xc4\x8c"=>"C",
			"\xc4\x8e"=>"D",
			"\xc3\x89"=>"E",
			"\xc4\x9a"=>"E",
			"\xc3\x8d"=>"I",
			"\xc4\xbd"=>"L",
			"\xc4\xb9"=>"L",
			"\xc5\x87"=>"N",
			"\xc3\x93"=>"O",
			"\xc3\x96"=>"O",
			"\xc5\x90"=>"O",
			"\xc3\x94"=>"O",
			"\xc5\x98"=>"R",
			"\xc5\x94"=>"R",
			"\xc5\xa0"=>"S",
			"\xc5\xa4"=>"T",
			"\xc3\x9a"=>"U",
			"\xc5\xae"=>"U",
			"\xc3\x9c"=>"U",
			"\xc5\xb0"=>"U",
			"\xc3\x9d"=>"Y",
			"\xc5\xbd"=>"Z"
		);
		
		return strtr($s, $tbl);
	}

	/**
	 * Prints phone number in pretty format
	 *
	 * @author Michal Kliment
	 * @param string $number
	 * @param string $country_code
	 * @return string
	 */
	public static function phone_number ($number, $country_code)
	{
		$val = $country_code . ' ';

		$length = strlen($number);

		$pointer = $length % 3;

		if ($pointer)
		{
			$val .= substr($number, 0, $pointer).' ';
		}

		while ($pointer < $length)
		{
			$val .= substr($number, $pointer, 3).' ';
			$pointer +=3;
		}
		
		return $val;
	}
	
	/**
	 * Check if first string starts with second
	 *
	 * @author Ondřej Fibich
	 * @param string $str	String
	 * @param string $start	Start
	 * @return bool			true if first string starts with second	
	 */
	public static function starts_with($str, $start)
	{
		return strncmp($str, $start, mb_strlen($start)) == 0;
	}
	
	/**
	 * Check if first string ends with second
	 *
	 * @author Ondřej Fibich
	 * @param string $str	String
	 * @param string $end	End
	 * @return bool			true if first string ends with second	
	 */
	public static function ends_with($str, $end)
	{
		return strcmp(mb_substr($str, mb_strlen($str) - mb_strlen($end)), $end) == 0;
	}
	
	/**
	 * Pushes object properties into given format string
	 *
	 * @author Ondřej Fibich
	 * @param string $object	Object to push into format
	 * @param string $format	Format of ouput with tag in shape: {property}
	 * @return string			Result string
	 */
	public static function object_format($object, $format)
	{
		if (isset($object) && is_object($object) && !empty($format))
		{
			// seach for all tags
			while (preg_match("/{(\w+)}/", $format, $r))
			{
				$property = $r[1];
				$search = '{'.$r[1].'}';
				
				// get property
				try
				{
					$property_val = $object->$property;
				}
				catch (Exception $e)
				{
					$property_val = '?';
				}
				
				// replace all tags
				$format = str_replace($search, $property_val, $format);
			}
			
			if (!empty($format))
			{
				return $format;
			}
		}
		
		return '???';
	}
	
	/**
	 * Prints data in columns seperated by tabulator
	 * 
	 * @author Michal Kliment
	 * @param array $data
	 * @param array $columns
	 * @return string 
	 */
	public static function print_in_columns ($data, $columns)
	{
		// must be array
		if (!is_array($data))
			$data = (array) $data;
		
		if (!is_array($columns))
			$columns = (array) $columns;
		
		$str = '';
		
		// prints results
		foreach ($data as $line)
		{	
			foreach ($columns as $column)
			{
				if (isset($line[$column]))
					$str .= $line[$column].self::TAB;
				else
					$str .= self::TAB;
			}
			$str .= self::EOL;
		}
		
		return $str;
	}
	
	/**
	 * Handle printing of empty inputs
	 * 
	 * @author Michal Kliment
	 * @param string $item
	 * @param boolean $print
	 * @param string $replace
	 * @param string $extra_text
	 * @return string 
	 */
	public static function not_null($item, $print = FALSE, $replace = '???', $extra_text = '')
	{
		if ($item && $item != '')
			return $item.' '.$extra_text;
		
		if ($print)
		{
			return $replace.' '.$extra_text;
		}
	}
	
	/**
	 * Highlight occurences of what in where by span with class highlighted
	 * 
	 * @param string $what What should be highlighted
	 * @param string $where Where it should be highlighted
	 * @return string String with hightligthed occurences of what
	 */
	public static function highligth($what, $where)
	{
		// make every letter valid in regex
		$what_letters = str_split($what);
		
		foreach ($what_letters as $i => $valid_what_letter)
		{
			// delimiter of regex
			if ($valid_what_letter == '/')
			{
				$valid_what_letter = '\/';
			}
			// change
			$what_letters[$i] = '[' . $valid_what_letter . ']';
		}
		
		// highlingth occurrences with ignore case
		$r = preg_replace(
				'/(' . implode('', $what_letters) . ')/i', 
				'<span class="highlighted">$1</span>',
				$where
		);
		
		// an error occured, we will rather return old string
		if (!$r)
		{
			return $where;
		}
		
		return $r;
	}


} // End text