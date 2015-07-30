<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Security helper class.
 *
 * $Id: security.php 1725 2008-01-17 16:38:59Z PugFish $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class security {

	/**
	 * Sanitize a string with the xss_clean method.
	 *
	 * @param   string  string to sanitize
	 * @return  string
	 */
	public static function xss_clean($str)
	{
		static $input;

		if ($input === NULL)
		{
			$input = new Input();
		}

		return $input->xss_clean($str);
	}

	/**
	 * Remove image tags from a string.
	 *
	 * @param   string  string to sanitize
	 * @return  string
	 */
	public static function strip_image_tags($str)
	{
		$str = preg_replace('#<img\b.*?(?:src\s*=\s*["\']?([^"\'<>\s]*)["\']?[^>]*)?>#is', '$1', $str);

		return trim($str);
	}

	/**
	 * Remove PHP tags from a string.
	 *
	 * @param   string  string to sanitize
	 * @return  string
	 */
	public static function encode_php_tags($str)
	{
		return str_replace(array('<?', '?>'),  array('&lt;?', '?&gt;'), $str);
	}

        /**
         * @author Michal Kliment
         * Generate security password from capital and small letters and numbers
         * @param $char_count
         * @return password
         */
        public static function generate_password($char_count = 8)
        {

            $password = '';

            for ($i = 1; $i <= $char_count; $i++)
            {
                $rand = mt_rand(0, 2);
                if ($rand == 0)
                {
                    // capital letters
                    $password .= chr(mt_rand(65, 90));
                }
                elseif ($rand == 1)
                {
                    // small letters
                    $password .= chr(mt_rand(97, 122));
                }
                else
                {
                    // numbers
                    $password .= chr(mt_rand(48, 57));
                }
            }

            return $password;

        }

        /**
         * @author Michal Kliment
         * Generate security password from numbers
         * @param $char_count
         * @return password
         */
        public static function generate_numeric_password($char_count = 8)
        {

            $password = '';

            for ($i = 1; $i <= $char_count; $i++)
            {        
                $password .= chr(mt_rand(48, 57));
            }

            return $password;
        }

} // End security