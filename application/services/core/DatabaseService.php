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

use Database;
use SqlScriptParser;

/**
 * Utility service function for working over database such as SQL script
 * processing, database truncation, etc.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.2
 */
class DatabaseService
{
    /**
     * Run SQL script at given file path on passed connection.
     *
     * @param Database $conn database connection
     * @param string $script_path path to SQL script to be executed
     * @return int executed query count
     * @throws \InvalidArgumentException on invalid connection or non existing
     *      or invalid script file
     * @throws \Exception on error during script execution
     */
    public function run_file(Database $conn, $script_path)
    {
        // prepare parser and script content
        $parser = new SqlScriptParser();
        $sqls = $this->load_file($script_path);
        $queries = $parser->parse_queries($sqls);
        // run
        try
        {
            $this->run_queries($conn, $queries);
            return count($queries);
        }
        catch (\Exception $ex)
        {
            throw new \Exception('SQL script exec failed', NULL, $ex);
        }
    }

    /**
     * Load file on given path and returns its content.
     *
     * @param string $file_path file path
     * @return striung file content
     * @throws \InvalidArgumentException on non-readable file
     */
    private final function load_file($file_path)
    {
        if (!\file_exists($file_path) || !\is_readable($file_path))
        {
            throw new \InvalidArgumentException('invalid script file');
        }
        $script = \file_get_contents($file_path);
        if ($script == FALSE)
        {
            throw new \InvalidArgumentException('script file read error');
        }
        return $script;
    }

    /**
     * Execute all given queries in given connection.
     *
     * @param Database $conn
     * @param array $queries
     * @throws \InvalidArgumentException
     */
    private function run_queries(Database $conn, $queries)
    {
        if (empty($conn))
        {
            throw new \InvalidArgumentException('null connection');
        }
        if (!is_array($queries))
        {
            throw new \InvalidArgumentException('queries should be array');
        }
        for ($i = 0; $i < \count($queries); $i++)
        {
            try
            {
                $conn->query($queries[$i]);
            }
            catch (\Kohana_Database_Exception $ex)
            {
                throw new \Exception('query(' . ($i + 1) . ') '
                        . $queries[$i] . ' failed', NULL, $ex);
            }
        }
    }

    /**
     * Truncates all databases in database that is managed by given connection.
     *
     * @param Database $conn
     * @throws \InvalidArgumentException on invalid connection
     * @throws \Exception on any error during truncation
     */
    public function truncate_db(Database $conn)
    {
        if (empty($conn))
        {
            throw new \InvalidArgumentException('null connection');
        }
        $tables = $conn->list_tables();
        $conn->foreign_key_check(FALSE); // must disable foreing key check
        try
        {
            foreach ($tables as $table_name)
            {
                $conn->truncate($table_name);
            }
        }
        catch (\Exception $ex)
        {
            try
            {
                $conn->foreign_key_check(TRUE);
            }
            catch (\Exception $ignore)
            {
            }
            throw new \Exception('truncation failed', NULL, $ex);
        }
        $conn->foreign_key_check(TRUE);
    }

}
