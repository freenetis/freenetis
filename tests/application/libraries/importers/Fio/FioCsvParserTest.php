<?php

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

require  APPPATH.'/libraries/importers/Fio/FioCsvParser.php';

/**
 * Unit test for FioCsvParser, files used as test data are located in same
 * folder as test class is with prefix "FioCsvParserTest_".
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class FioCsvParserTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var FioCsvParser
     */
    private $parser;

    protected function setUp()
    {
        $this->parser = new FioCsvParser;
    }

    /**
     * Test for short.valid.csv file.
     *
     * @covers FioCsvParser::parse
     */
    public function testParseCsvShortValid()
    {
        $exp_result = array
        (
            array
            (
                'datum' => '2010-12-16',
                'id_pohybu' => '1115992591',
                'id_pokynu' => '7848488787',
                'kod_banky' => '6210',
                'ks' => '78945',
                'mena' => 'CZK',
                'nazev_banky' => 'BRE Bank S.A., organizační složka podniku',
                'nazev_protiuctu' => 'int78',
                'castka' => 2111.11,
                'protiucet' => '670100-2202442842',
                'provedl' => 'Emanuel Bacigala',
                'prevod' => '0',
                'ss' => '7845',
                'typ' => 'Bezhotovostní příjem',
                'upresneni' => 'G45',
                'identifikace' => 'DOMINIK  BUREŠ',
                'vs' => '215',
                'zprava' => '-IRONGATE VS:215',
            )
        );

        $content1 = $this->fgcr('FioCsvParserTest_short.valid.csv');
        $result1 = $this->parser->parse($content1);
        $this->assertEquals('1234567890', $result1->account_nr);
        $this->assertEquals('2010', $result1->bank_nr);
        $this->assertEquals(12345.67, $result1->opening_balance, '', 0.0001);
        $this->assertEquals(14456.78, $result1->closing_balance, '', 0.0001);
        $this->assertEquals('2010-12-16', $result1->from);
        $this->assertEquals('2011-01-01', $result1->to);
        $this->assertItemsEquals($exp_result, $result1->items);

        // same test with different encoding
        $content2 = $this->fgcr('FioCsvParserTest_short.valid.win1250.csv');
        $result2 = $this->parser->parse($content2, 'WINDOWS-1250');
        $this->assertEquals('1234567890', $result2->account_nr);
        $this->assertEquals('2010', $result2->bank_nr);
        $this->assertEquals(12345.67, $result2->opening_balance, '', 0.0001);
        $this->assertEquals(14456.78, $result2->closing_balance, '', 0.0001);
        $this->assertEquals('2010-12-16', $result2->from);
        $this->assertEquals('2011-01-01', $result2->to);
        $this->assertItemsEquals($exp_result, $result2->items);
    }

    /**
     * Test for invalid transaction sum.
     *
     * @covers FioCsvParser::parse
     */
    public function testParseCsvInvalidSum()
    {
        try
        {
            $content = $this->fgcr('FioCsvParserTest_sum.invalid.csv');
            $this->parser->parse($content);
            $this->fail('should fail with exception');
        }
        catch (Exception $ex) {}
    }

    /**
     * Test for invalid transaction header (missing "ID pokynu").
     *
     * @covers FioCsvParser::parse
     */
    public function testParseCsvInvalidHeader()
    {
        try
        {
            $content = $this->fgcr('FioCsvParserTest_header.invalid.csv');
            $this->parser->parse($content);
            $this->fail('should fail with exception');
        }
        catch (Exception $ex) {}
    }

    /**
     * Help function for comparing result items.
     *
     * @param array $exp
     * @param array $result
     */
    private function assertItemsEquals($exp, $result)
    {
        $this->assertEquals(count($exp), count($result), 'number of items');
        $fields_indexes = array_keys(FioCsvParser::get_fields());
        for ($i = 0; $i < count($exp); $i++)
        {
            foreach ($fields_indexes as $field_index)
            {
                $this->assertTrue(array_key_exists($field_index, $result[$i]),
                        'field ' . $field_index . ' is missing on line ' . $i);
                $this->assertEquals($exp[$i][$field_index], $result[$i][$field_index],
                        'field ' . $field_index . ' invalid on line ' . $i);
            }
        }
    }

    private function fgcr($relative_path)
    {
        return file_get_contents(__DIR__ . '/' . $relative_path);
    }

}
