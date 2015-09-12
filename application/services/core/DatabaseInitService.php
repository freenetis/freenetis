<?php

/*
 *  This file is part of open source system FreenetIS
 *  and it is release under GPLv3 licence.
 *
 *  More info about licence can be found:
 *  http://www.gnu.org/licenses/gpl-3.0.html
 *
 *  More info about project can be found:
 *  http://www.freenetis.org/
 */

namespace freenetis\service\core;

use AbstractService;
use Config;
use Database;
use Log;
use Settings;
use Version;

/**
 * Service that handles database initialization/upgrade procedures.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.2
 */
class DatabaseInitService extends AbstractService
{
    /**
     * Creates service.
     *
     * @param \ServiceFactory $factory
     */
    public function __construct(\ServiceFactory $factory)
    {
        parent::__construct($factory);
    }

    /**
     * DB midpoint constant for before life cycle callback fail.
     */
    const DBU_MIDPOINT_BEFORE = -1;

    /**
     * DB midpoint constant for after life cycle callback fail.
     * Must be higher than count of SQL queries in each DB upgrade.
     */
    const DBU_MIDPOINT_AFTER = \PHP_INT_MAX;

    /**
     * Makes database to be up-to-day or skip if it already is. This method
     * is synchronized using lock file which path is provided as parameter.
     * If initialization or upgrade of database is performed than optional
     * after function callback is called.
     *
     * @param string $lock_file_name Lock mutex file path
     * @param Closure $after_callback Optional closure PHP callback function
     *      that is called after database upgrade is done (but synchronization
     *      lock is still on).
     * @throws \InvalidArgumentException On not writable lock file
	 * @throws DowngrateDbUpgradeException On not allowed database downgrade.
	 * @throws OldMechanismDbUpgradeException On old upgrade mechanism
	 * @throws NotEnabledDbUpgradeException On not enabled upgrade
	 * @throws \Exception On any other error
     */
    public function make($lock_file_name, $after_callback = NULL)
    {
        // change database encoding if incorect, TODO: collation by locale
        $this->set_db_encoding('utf8', 'utf8_czech_ci');

        // try to open mutex file that prevents for multiple running of DB
        // upgrades by parallel init method calls.
        if (($mutex_lock_file = @fopen($lock_file_name, 'w')) === FALSE)
        {
            throw new \InvalidArgumentException('lock file not writable: '
                    . $lock_file_name);
        }

        // acquire an exclusive access to file
        // wait while database is being updated
        if (flock($mutex_lock_file, LOCK_EX))
        {
            // first lock access - update db
            // other lock access - skip (request accepted during upgrade)
            if (!Version::is_db_up_to_date())
            {
                $this->execute_applicable_db_upgrades();
                // callback
                if (is_callable($after_callback))
                {
                    try
                    {
                        $after_callback();
                    }
                    catch (\Exception $ex)
                    {
                        throw new \Exception('after callback failed', NULL, $ex);
                    }
                }
            }

            // unlock mutex file
            flock($mutex_lock_file, LOCK_UN);
        }

        // close mutex file
        fclose($mutex_lock_file);
    }

    /**
     * Set DB encoding and collation by arguments if not already in these values.
     *
     * @param string $encoding
     * @param string $collation
     * @throws \Exception on set error
     */
    public function set_db_encoding($encoding, $collation)
    {
        $enc = mb_strtolower($encoding);
        $coll = mb_strtolower($collation);
        try
        {
            $db = Database::instance();

            if ($db->get_variable_value('character_set_database') != $enc ||
                $db->get_variable_value('collation_database') != $coll)
            {
                $db->alter_db_character_set(Config::get('db_name'), $enc, $coll);
            }
        }
        catch (\Exception $e)
        {
            Log::add_exception($e);
            $m = __('Cannot set database character set to %s@%s',
                    array($encoding, $collation));
            throw new \Exception($m, NULL, $e);
        }
    }

	/**
	 * Init/upgrade database by execution of applicable database upgrades.
     * Appplicable upgrades are all available upgrades that versions are higner
     * than the current database version.
	 *
	 * Upgrades files are located at /db_upgrades directory. Each upgrade has
     * a name that consist of 'upgrade' and version (similar to version in
     * /version.php). Each file contains whole definitions that are required
     * for performing the upgrade (life cycle callback, SQL queries, etc.).
	 *
	 * @throws DowngrateDbUpgradeException On not allowed database downgrade.
     *      (e.g. FN: 1.0.0 - DB: 1.1.0)
	 * @throws OldMechanismDbUpgradeException On old upgrade mechanism
	 * @throws NotEnabledDbUpgradeException On not enabled upgrade between DB
     *      versions
	 * @throws \Exception On any other error
	 */
	private function execute_applicable_db_upgrades()
	{
		// detect downgrade (not on invalid DB verion - possibility of upgrade
		// from old system)
		if (Version::is_valid_version(Version::get_db_version()) &&
            Version::fn_version_compare() < 0)
		{
			throw new DowngrateDbUpgradeException();
		}

        // get all available DB update versions from files in /db_upgrades
        $versions = $this->scan_applicable_db_upgrades('db_upgrades');

		// sort files according to version (we eant to make DB upgrades in order)
		usort($versions, 'Version::compare');

		// make upgrades
        if (!empty($versions))
        {
            // check if old style is in use
            if (is_numeric(Version::get_db_version()) &&
                Version::get_db_version() > 0)
            {
                // not possible, inform user
                throw new OldMechanismDbUpgradeException();
            }
            // execute each DB upgrade
            foreach ($versions as $version)
            {
                $this->execute_db_upgrade($version);
            }
        }

		// set current version (optimalization if version has no DB update)
		Settings::set('db_schema_version', Version::get_version());
	}

    /**
     * Scan directory with given name for upgrade DB files and returns
     * all applicable DB upgrade versions. Applicable upgrade means that
     * its version is higher than the current database version.
     *
     * @param string $scan_directory directory for scanning name
     * @return array list of applicable DB upgrade versions
     */
    private function scan_applicable_db_upgrades($scan_directory)
    {
		// array of available verisons
		$versions = array();
		// gets all files in scan directory dir
		$files = \scandir($scan_directory);
		// regex for file: upgrade_VERSION.php
		$regex = '^upgrade_' . rtrim(ltrim(Version::VERSION_REGEX, '^'), '$')
                . '\.php$';
		// filter files
		foreach ($files as $file)
		{
            $matches = array();
			// remove invalid files (wrong name) and value replace by version
			if (!mb_eregi($regex, $file, $matches))
			{
				continue;
			}
			// get version
			$version = $matches[1];
			// remove old already installed upgrades and future upgrades
			if (Version::compare($version, Version::get_version()) > 0 || (
					!is_numeric(Version::get_db_version()) &&
					Version::compare($version, Version::get_db_version(FALSE)) <= 0
				))
			{
                continue;
			}
			// add to available versions
			$versions[] = $version;
		}

        return $versions;
    }

    /**
     * Loads DB upgrade files and executes DB upgrade by its definitions
     * in following order:
     *
     * 1) Load DB upgrade file
     * 2) Check if upgrade is not disabled by "upgrade_enabled_only_from"
     * 3) Skip upgrade if it is equivalent to previous version that
     *    may be defined by "upgrade_equal_to"
     * 4) Execute before life cycle callback if it exists
     * 5) Executes SQL upgrade queries
     * 6) Execute fter life cycle callback if it exists
     * 7) Set new DB version
     *
     * If any error occures in 5, 6 or 7 than error position in upgrade
     * is marked using DB upgrade midpoint that prevents from reexecuting
     * of already executed upgrade parts during another upgrade attempt.
     *
     * @param string $version upgrade version
     * @return boolean was executed or just skiped because it was equivalent
     *      to previous DB version
     * @throws NotEnabledDbUpgradeException if upgrade restricted via
     *      $upgrade_enabled_only_from take affect
     * @throws \Exception if DB upgrade fails
     */
    private function execute_db_upgrade($version)
    {
        Log::add('debug', 'Starting upgrade ' . $version);
        // include upgrade file
        require 'db_upgrades/upgrade_' . $version . '.php';

        // check if the upgrade is allowed from the current DB version
        if (isset($upgrade_enabled_only_from[$version]) &&
            !Version::is_db_version_in($upgrade_enabled_only_from[$version]))
        {
            throw new NotEnabledDbUpgradeException(__(
                    'Database upgrade %s not allowed from version %s',
                    array($version, Version::get_db_version())
            ));
        }

        // check if the upgrade is equivalent to the current DB version
        if (isset($upgrade_equal_to[$version]) &&
            Version::is_db_version_in($upgrade_equal_to[$version]))
        {
            Log::add('debug', 'Upgrade ' . $version . ' skipping ('
                    . Version::get_db_version() . ')');
            // it is => so skip it
            $this->set_db_version($version);
            return FALSE; // exit
        }

		// get upgrade midpoint (partial upgrade pointer)
		$midpoint = $this->get_db_upgrade_midpoint();

        // make update
        try
        {
            // upgrade function before
            if (!is_numeric($midpoint) ||
                $midpoint == self::DBU_MIDPOINT_BEFORE)
            {
                try
                {
                    $this->call_db_upgrade_method($version, 'before');
                }
                catch (\Exception $ex)
                {
                    $this->set_db_upgrade_midpoint(self::DBU_MIDPOINT_BEFORE);
                    throw $ex;
                }
                $midpoint = NULL;
            }

            // upgrade SQL
            if (isset($upgrade_sql[$version]))
            {
                $from_index = is_numeric($midpoint) ? intval($midpoint) : 0;
                $this->execute_sql_queries($upgrade_sql[$version], $from_index);
            }

            // upgrade function after
            try
            {
                $this->call_db_upgrade_method($version, 'after');
            }
            catch (\Exception $ex)
            {
                $this->set_db_upgrade_midpoint(self::DBU_MIDPOINT_AFTER);
                throw $ex;
            }

            // set up db schema
            $this->set_db_version($version);

            Log::add('debug', 'Upgrade ' . $version . ' complete');
        }
        catch (\Exception $e)
        {
            $message = 'Upgrade DB: ' . $version . ' failed<br /><br />'
                     . 'Cause: ' . $e->getMessage();
            throw new \Exception($message, NULL, $e);
        }

        return TRUE;
    }

    /**
     * Set DB FreenetIS version and clean DB upgrade midpoint.
     *
     * @param string $version new DB version
     */
    private function set_db_version($version)
    {
        Settings::set('db_schema_version', $version);
        $this->clear_db_upgrade_midpoint();
    }

    /**
     * Get DB upgrade midpoint which defines from which point failed database
     * upgrade should be started (in order to not make same upgrade parts
     * again).
     *
     * @param mixed $value
     */
    private function get_db_upgrade_midpoint()
    {
        return Settings::get('upgrade_midpoint_error', FALSE);
    }

    /**
     * Set DB upgrade midpoint to given value that should be numeric.
     *
     * Value equal to DBU_MIDPOINT_BEFORE means midpoint on: before life cycle
     * Value equal to DBU_MIDPOINT_AFTER means midpoint on: after life cycle
     * Values between previous two values means that midpoint is index to
     * uprade queries.
     *
     * @param mixed $value
     */
    private function set_db_upgrade_midpoint($value)
    {
        Settings::set('upgrade_midpoint_error', $value);
    }

    /**
     * Clears DB upgrade midpoint to value whe it do not prevent from execution
     * of all upgrade.
     */
    private function clear_db_upgrade_midpoint()
    {
        $this->set_db_upgrade_midpoint('');
    }

    /**
     * Calls upgrade life cycle before or update method for upgrade with
     * given version if callback function is defined.
     *
     * DB upgrade midpoint is cleared after sucessfull call.
     *
     * @param string $version
     * @param string $phase life cycle phase (before or after)
     * @return boolean callback exists and was called?
     * @throws \Exception on callback fail (returns FALSE or throw any Exception)
     */
    private function call_db_upgrade_method($version, $phase) {
        // check phase
        if (!in_array($phase, array('before', 'after')))
        {
            throw new \InvalidArgumentException('invalid phase: ' . $phase);
        }
        // transform version to version which may be used at PHP functions
        $f_version = str_replace(array('~', '.'), array('_', '_'), $version);
        // function name
        $function_name = 'upgrade_' . $f_version . '_' . $phase;
        // call function only if it exists
        if (!function_exists($function_name))
        {
            return FALSE;
        }
        // log action
        Log::add('debug', 'Upgrade ' . $version . ' [' . $phase . '] trigger');
        // call
        try {
            $result = call_user_func($function_name);
        }
        catch (\Exception $e)
        {
            throw new \Exception($function_name . ' throwed exception', NULL, $e);
        }
        if (!$result)
        {
            throw new \Exception($function_name . ' call failed');
        }
        // call may be invoked after last error
        $this->clear_db_upgrade_midpoint();
        // called sucessfully
        return TRUE;
    }

    /**
     * Executes given array of queries that has index higher than value
     * of from argument.
     *
     * If an exception is thrown fromm method than DB upgrade midpoint is also
     * set to query index on which the error occured.
     *
     * @param array $queries array of SQL queries
     * @param integer $from start index for queries array [optional: default 0]
     * @throws \Exception on SQL query execition fail
     */
    private function execute_sql_queries($queries, $from = 0)
    {
        $query_index = intval($from);
        // each item of array (SQL)
        for (; $query_index < count($queries); $query_index++)
        {
            $query = $queries[$query_index];
            try
            {
                Log::add('debug', 'Upgrade SQL command [' . $query_index
                        . ']: ' . $query);
                Database::instance()->query($query);
            }
            catch (\Exception $ex)
            {
                $this->set_db_upgrade_midpoint($query_index);
                throw new \Exception('SQL query failed: ' . $query, NULL, $ex);
            }
        }
    }

}

/**
 * Exception that reflects state of not allowed downgration of database.
 */
class DowngrateDbUpgradeException extends \Exception
{
}

/**
 * Exception that reflects state of old mechanism for updating of the database
 * structure that cannot be automatically turned to new mechanism.
 */
class OldMechanismDbUpgradeException extends \Exception
{
}

/**
 * Exception that reflects state of not enabled upgrade by using field
 * upgrade_enabled_only_from in the current database upgrade.
 *
 * It occures when a new upgrade should be performed, but current version
 * is not listed in upgrade_enabled_only_from.
 */
class NotEnabledDbUpgradeException extends \Exception
{
}
