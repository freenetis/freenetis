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
 * Class for versioning of FreenetIS. Manages source code and database versions
 * and it performs database upgrade.
 *
 * @author Ondrej Fibich
 * @see Controller
 */
class Version
{
	/**
	 * Regex for valitadation of versions
	 */
	const VERSION_REGEX = '^(((\d|[1-9][0-9]*)\.(\d|[1-9][0-9]*)\.(\d|[1-9][0-9]*))(~(alpha|beta|dev|rc)([1-9][0-9]*)?)?)$';

	/**
	 * Gets version of Freenetis code
	 *
	 * @return string		Version
	 */
	public static function get_version()
	{
		if (!defined('FREENETIS_VERSION'))
		{
			require 'version.php';

			if (!self::is_valid_version(FREENETIS_VERSION))
			{
				throw new ErrorException('Wrong version format in version.php');
			}
		}

		return FREENETIS_VERSION;
	}

	/**
	 * Gets version of Freenetis database
	 *
	 * @param $cache		May be value returned from cache [optional]
	 * @return string		Version
	 */
	public static function get_db_version($cache = TRUE)
	{
		return Settings::get('db_schema_version', $cache);
	}

	/**
	 * Check if the version of database is equal
	 *
	 * @return boolean		true if versions are equal
	 */
	public static function is_db_up_to_date()
	{
		// old type of DB upgrades? => always false
		if (is_numeric(self::get_db_version(FALSE)))
		{
			return FALSE;
		}
		// new type
		return (self::fn_version_compare() == 0);
	}

    /**
     * Is the current database version one of given versions?
     *
     * @param array|string $versions
     * @return boolean
     */
    public static function is_db_version_in($versions)
    {
        if (empty($versions))
        {
            return FALSE;
        }
        if (!is_array($versions))
        {
            $versions = array($versions);
        }
        // listed => equivalent
        return in_array(self::get_db_version(), $versions);
    }

	/**
	 * Compares version of FreenetIS and its database
	 *
	 * @return integer		-1 if the FreenetIS version is lower than the database
	 *						version, 0 if they are equal, and 1 if the second is lower
	 */
	public static function fn_version_compare()
	{
		return self::compare(self::get_version(), self::get_db_version());
	}

	/**
	 * Checks if given version is in correct format
	 *
	 * @param string $version Version to check
	 * @return boolean		true if it is, false otherwise
	 */
	public static function is_valid_version($version)
	{
		return (mb_ereg(self::VERSION_REGEX, $version) > 0);
	}

	/**
	 * Compares two valid versions of FreenetIS
	 *
	 * @param string $version1
	 * @param string $version2
	 * @return integer		-1 if the first version is lower than the second
	 *						version, 0 if they are equal, and 1 if the second is lower
	 * @throws InvalidArgumentException On invalid version
	 */
	public static function compare($version1, $version2)
	{
		if (!self::is_valid_version($version1))
		{
			throw new InvalidArgumentException('Wrong version1 format: ' . $version1);
		}

		if (!self::is_valid_version($version2))
		{
			throw new InvalidArgumentException('Wrong version2 format: ' . $version2);
		}

		$version1_parts = explode('~', $version1);
		$version2_parts = explode('~', $version2);
		$cmp = 0;

		if (($cmp = version_compare($version1_parts[0], $version2_parts[0])) == 0)
		{
			if (!isset($version1_parts[1]) && !isset($version2_parts[1]))
			{
				return 0;
			}
			else if (!isset($version1_parts[1]))
			{
				return 1;
			}
			else if (!isset($version2_parts[1]))
			{
				return -1;
			}

			$order = array('dev', 'alpha', 'beta', 'rc');
			$order1 = NULL;
			$order2 = NULL;
			$i = 0;

			foreach ($order as $type)
			{
				if (text::starts_with($version1_parts[1], $type))
				{
					$order1 = $i;
				}

				if (text::starts_with($version2_parts[1], $type))
				{
					$order2 = $i;
				}

				$i++;
			}

			if (($cmp = $order1 - $order2) == 0)
			{
				$number1 = substr($version1_parts[1], mb_strlen($order[$order1]));
				$number2 = substr($version2_parts[1], mb_strlen($order[$order2]));

				return intval($number1) - intval($number2);
			}
		}

		return $cmp;
	}
}
