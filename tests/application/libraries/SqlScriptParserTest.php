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

/**
 * Test case for SqlScriptParser class.
 */
class SqlScriptParserTest extends PHPUnit_Framework_TestCase
{
    private static $test_data = array
    (
        // missing ;
        "   SELECT * FROM a  " => array(),
        // one query
        "   SELECT * FROM a \n ; " => array("SELECT * FROM a"),
        // one query with "string"
        'SELECT "; \"";' => array('SELECT "; \""'),
        // one query with 'string'
        "SELECT 'sa; \\'aa';" => array("SELECT 'sa; \\'aa'"),
        // two query
        "SELECT 1;SELECT 2;SELECT 3" => array('SELECT 1', 'SELECT 2'),
        // two query with comment
        "SELECT 1;--SELECT 2;SELECT 3\nSELECT 4;--a" =>
            array('SELECT 1', 'SELECT 4'),
        // query separated by comment
        "SELECT 1, -- 1 value\n2;" => array("SELECT 1, 2"),
        // two query with comment ans WS
        "  SELECT 1 ;   --SELECT 2;SELECT 3\nSELECT 4;------------a\n  " =>
            array('SELECT 1', 'SELECT 4'),
    );

    /**
     * @var SqlScriptParser
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new SqlScriptParser;
    }

    /**
     * @covers freenetis\service\core\SqlScriptParser::parse_queries
     */
    public function testParse_queries()
    {
        // invalid arg
        $this->assertEquals(array(), $this->object->parse_queries(NULL));
        $this->assertEquals(array(), $this->object->parse_queries(FALSE));
        $this->assertEquals(array(), $this->object->parse_queries(array()));

        // empty SQL query
        try
        {
            $this->object->parse_queries(';    ');
            $this->fail('should throw InvalidArgumentException');
        }
        catch (\InvalidArgumentException $ex)
        {
        }

        // single query no semi-colon
        foreach (self::$test_data as $query => $exp)
        {
            $result = $this->object->parse_queries($query);
            $this->assertEquals(\count($exp), \count($result), $query);
            for ($i = 0; $i < \count($result); $i++)
            {
                $this->assertEquals($exp[$i], $result[$i], $query);
            }
        }
    }

}
