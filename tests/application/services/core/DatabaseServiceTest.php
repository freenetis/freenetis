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

use AbstractItCase;
use TestConfig;

/**
 * Test case for DatabaseService class.
 */
class DatabaseServiceTest extends AbstractItCase
{
    /**
     * @var DatabaseService
     */
    protected $object;

    protected function setUp()
    {
        $this->object = self::$services->injectCoreDatabase();
    }

    /**
     * @covers freenetis\service\core\DatabaseService::run_file
     */
    public function testRun_file()
    {
        // file not exists
        try
        {
            $this->object->run_file(self::$connection, __DIR__ . '/not_exists');
            $this->fail('should throw InvalidArgumentException');
        } catch (\InvalidArgumentException $ex) {
        }

        // invalid command
        try
        {
            $this->object->run_file(self::$connection,
                    __DIR__ . '/DatabaseServiceTest.run_file.invalid.sql');
            $this->fail('should throw Exception');
        } catch (\Exception $ex) {
        }

        // valid
        $qc = $this->object->run_file(self::$connection,
                __DIR__ . '/DatabaseServiceTest.run_file.valid.sql');
        $this->assertEquals(3, $qc);
    }

    /**
     * @covers freenetis\service\core\DatabaseService::truncate_db
     */
    public function testTruncate_db()
    {
        $db_name = '_freenetis__de_le_lele_te_TEST12456748';
        // only if database create enabled
        try
        {
            self::$connection->query("CREATE DATABASE `$db_name`;");
        }
        catch (\Exception $ex)
        {
            $this->markTestIncomplete(
                    'cannot create new database with passed credentials'
            );
        }
        // prepare tables
        self::$connection->query("
            CREATE TABLE `$db_name`.`a` (
                b int not null,
                PRIMARY KEY(b)
            ) ENGINE=InnoDB;
        ");
        self::$connection->query("
            CREATE TABLE `$db_name`.`a_ref` (
                c int  not null,
                b_ref int  not null,
                PRIMARY KEY(c),
                FOREIGN KEY (b_ref) REFERENCES a(b)
            ) ENGINE=InnoDB;
        ");
        self::$connection->query("
            INSERT INTO `$db_name`.`a`
            VALUES (1), (2), (3);
        ");
        self::$connection->query("
            INSERT INTO `$db_name`.`a_ref`
            VALUES (11, 1), (12, 2);
        ");
        // test
        $conn = new \Database(array
        (
            'type' => TestConfig::get('db.type'),
            'host' => TestConfig::get('db.host'),
            'database' => $db_name,
            'user' => TestConfig::get('db.user'),
            'pass' => TestConfig::get('db.pass')
        ));
        $this->object->truncate_db($conn);
        // check
        $this->assertEquals(0, $conn->query("
            SELECT COUNT(*) AS c FROM a
        ")->current()->c);
        $this->assertEquals(0, $conn->query("
            SELECT COUNT(*) AS c FROM a_ref
        ")->current()->c);
        // clean
        unset($conn);
        self::$connection->query("DROP DATABASE `$db_name`;");
    }

}
