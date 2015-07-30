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
	const VERSION_REGEX = '^(([0-9]+\.[0-9]+\.[0-9+])(~(alpha|beta|dev|rc)[0-9]*)?)$';
	
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
			return false;
		}
		// new type
		return (self::fn_version_compare() == 0);
	}
	
	/**
	 * Compares version of FreenetIS and its database
	 * 
	 * @return integer		-1 if the FreenetIS version is lower than the database
	 *						version, 0 if they are equal, and 1 if the second is lower
	 */
	private static function fn_version_compare()
	{
		return self::compare(self::get_version(), self::get_db_version());
	}
	
	/**
	 * Checks if given version is in correct format
	 * 
	 * @param string $version Version to check
	 * @return boolean		true if it is, false otherwise
	 */
	private static function is_valid_version($version)
	{
		return mb_eregi(self::VERSION_REGEX, $version);
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
	
	/**
	 * Makes database updates if there are any.
	 * 
	 * Updates are located at /db_upgrades. Each upgrade has a name that consist
	 * of 'upgrade' and version (similar to version in /version.php).
	 * 
	 * If any error occure during an upgrade. A location of the error is stored
	 * in the config DB table in a form of a midpoint. The upgrade starts from
	 * the stored midpoind in the next attempt to do the upgrade.
	 * Midpoint for before function is a number lower than zero and for after
	 * function is it a number greater than a count of SQL commands in the upgrade.
	 * 
	 * @throws Database_Downgrate_Exception
	 *						On not allowed database downgrade. (e.g. FN: 1.0.0 - DB: 1.1.0)
	 * 
	 * @throws Old_Mechanism_Exception
	 *						On old upgrade mechanism
	 * 
	 * @throws Exception	On any other error
	 */
	public static function make_db_up_to_date()
	{
		// detect downgrade (not on invalid DB verion - possibility of upgrade
		// from old system)
		if (self::is_valid_version(self::get_db_version()) &&
			self::fn_version_compare() < 0)
		{
			throw new Database_Downgrate_Exception();
		}
		
		// database connection
		$db = Database::instance();
		// gets all files in /db_upgrades dir
		$files = scandir('db_upgrades');
		// array of available verisons
		$versions = array();
		// regex for file
		$regex = '^upgrade_' . rtrim(ltrim(self::VERSION_REGEX, '^'), '$') . '\.php$';
		
		// filter files
		foreach ($files as $file)
		{
			// remove invalid files (wrong name) and value replace by version
			if (!mb_eregi($regex, $file, $r))
			{
				continue;
			}
			
			// get version 
			$version = $r[1];
			
			// remove old already installed upgrades and future upgrades
			if (self::compare($version, self::get_version()) > 0 || (
					!is_numeric(self::get_db_version()) &&
					self::compare($version, self::get_db_version(FALSE)) <= 0
				))
			{
				continue;
			}
			
			// add to available versions
			$versions[] = $version;
		}
		
		// sort files according to version
		usort($versions, 'Version::compare');
		
		// midpoint
		$midpoint = Settings::get('upgrade_midpoint_error', FALSE);
		
		// make upgrades
		foreach ($versions as $version)
		{
			Log::add('debug', 'Starting upgrade ' . $version);
			
			// include include file
			require 'db_upgrades/upgrade_' . $version . '.php';
			
			// check if the upgrade is equvivalent to the current DB version
			if (isset($upgrade_equal_to[$version]))
			{
				if (!is_array($upgrade_equal_to[$version]))
				{
					$upgrade_equal_to[$version] = array($upgrade_equal_to[$version]);
				}
				
				if (in_array(self::get_db_version(), $upgrade_equal_to[$version]))
				{
					Log::add('debug', 'Upgrade ' . $version . ' skipping (' . self::get_db_version() . ')');
					// it is => so skip it
					Settings::set('db_schema_version', $version);
					Settings::set('upgrade_midpoint_error', '');
					continue;
				}
			}
			
			// check if old style is in use
			if (is_numeric(self::get_db_version()) && self::get_db_version() > 0)
			{
				// not possible, inform user
				throw new Old_Mechanism_Exception();
			}
			
			// transform version to version which may be used at PHP functions
			$f_version = str_replace(array('~', '.'), array('_', '_'), $version);
			// function names
			$f_before = 'upgrade_' . $f_version . '_before';
			$f_after = 'upgrade_' . $f_version . '_after';

			// make update
			try
			{
				// upgrade function before
				if (function_exists($f_before) && (!is_numeric($midpoint) || $midpoint < 0))
				{
					try
					{
						Log::add('debug', 'Upgrade ' . $version . ' trigger before');
			
						if (!call_user_func($f_before))
						{
							throw new Exception($f_before);
						}
						$midpoint = NULL;
					}
					catch (Exception $ex)
					{
						Settings::set('upgrade_midpoint_error', -1);
						throw $ex;
					}
				}
				else if ($midpoint < 0)
				{
					$midpoint = NULL;
				}

				// upgrade SQL
				if (isset($upgrade_sql[$version]))
				{
					$qindex = is_numeric($midpoint) ? intval($midpoint) : 0;

					// each item of array (SQL)
					for (; $qindex < count($upgrade_sql[$version]); $qindex++)
					{
						try
						{
							Log::add('debug', 'Upgrade ' . $version . ' performing SQL command: ' . $qindex);
							$db->query($upgrade_sql[$version][$qindex]);
						}
						catch (Exception $ex)
						{
							Settings::set('upgrade_midpoint_error', $qindex);
							throw $ex;
						}
					}
				}

				// upgrade function after
				if (function_exists($f_after))
				{
					try
					{
						Log::add('debug', 'Upgrade ' . $version . ' trigger after');
						
						if (!call_user_func($f_after))
						{
							throw new Exception($f_after);
						}
						$midpoint = NULL;
					}
					catch (Exception $ex)
					{
						$top_e = count($upgrade_sql[$version]) + 1000;
						Settings::set('upgrade_midpoint_error', $top_e);
						throw $ex;
					}
				}
				
				// clean memory
				if (isset($upgrade_sql[$version]))
				{
					unset($upgrade_sql[$version]);
				}
				
				if (isset($upgrade_equal_to[$version]))
				{
					unset($upgrade_equal_to[$version]);
				}

				// set up db schema
				Settings::set('db_schema_version', $version);

				// clean midpoint if not cleaned
				if (Settings::get('upgrade_midpoint_error') !== '')
				{
					Settings::set('upgrade_midpoint_error', '');
				}
				
				Log::add('debug', 'Upgrade ' . $version . ' complete');
			}
			catch (Exception $e)
			{
				$message = 'Upgrade DB: ' . $version . '<br /><br />'
						 . 'Function: ' . $e->getMessage() . '<br />'
						 . 'Last SQL command: ' . $db->last_query();

				throw new Exception($message);
			}
		}
		
		// set current version (optimalization if version has no DB update)
		Settings::set('db_schema_version', self::get_version());
	}
}

/**
 * Exception that reflects state of not allowed downgration of database. 
 */
class Database_Downgrate_Exception extends Exception
{
}

/**
 * Exception that reflects state of old mechanism for updating of the database
 * structure that cannot be automatically turned to new mechanism. 
 */
class Old_Mechanism_Exception extends Exception
{
}
