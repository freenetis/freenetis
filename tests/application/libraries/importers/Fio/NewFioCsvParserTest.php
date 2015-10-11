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

require  APPPATH.'/libraries/importers/Fio/NewFioCsvParser.php';

/**
 * Unit test for NewFioCsvParser, files used as test data are located in same
 * folder as test class is with prefix "NewFioCsvParserTest_".
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class NewFioCsvParserTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var NewFioCsvParser
     */
    private $parser;

    protected function setUp()
    {
        $this->parser = new NewFioCsvParser;
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
                'datum' => '2015-03-02',
                'id_pohybu' => '7150326538',
                'id_pokynu' => '8005723064',
                'kod_banky' => '600',
                'ks' => '',
                'mena' => 'CZK',
                'nazev_banky' => 'GE Money Bank, a.s.',
                'nazev_protiuctu' => 'NOVÁK JIŘÍ',
                'castka' => 1800.4,
                'protiucet' => '7894561678',
                'provedl' => '',
                'prevod' => '',
                'ss' => '',
                'typ' => 'Bezhotovostní příjem',
                'upresneni' => '1800,4 CZK',
                'identifikace' => 'NOVÁK JIŘÍ',
                'vs' => '774074794',
                'zprava' => 'Ing. Jiří Novák - roční členské příspěvky',
            )
        );

        $content1 = $this->fgcr('NewFioCsvParserTest_short.valid.csv');
        $this->assertTrue($this->parser->accept_file($content1));
        $result = $this->parser->parse($content1);
        $this->assertEmpty($result->account_nr);
        $this->assertEmpty($result->bank_nr);
        $this->assertEmpty($result->opening_balance);
        $this->assertEmpty($result->closing_balance);
        $this->assertEmpty($result->from);
        $this->assertEmpty($result->to);
        $this->assertItemsEquals($exp_result, $result->items);
    }

    /**
     * Test for invalid transaction sum.
     *
     * @covers FioCsvParser::parse
     */
    public function testParseCsvInvalidSum()
    {
        $content = $this->fgcr('NewFioCsvParserTest_sum.invalid.csv');
        $this->assertTrue($this->parser->accept_file($content));
        try
        {
            $this->parser->parse($content);
            $this->fail('should fail with exception');
        }
        catch (Exception $ex) {}
    }

    /**
     * Test for invalid transaction header.
     *
     * @covers FioCsvParser::parse
     */
    public function testParseCsvInvalidHeader()
    {
        $content = $this->fgcr('NewFioCsvParserTest_header.invalid.csv');
        $this->assertFalse($this->parser->accept_file($content));
        try
        {
            $this->parser->parse($content);
            $this->fail('should fail with exception');
        }
        catch (Exception $ex) {}

        $content2 = $this->fgcr('NewFioCsvParserTest_header.invalid2.csv');
        $this->assertFalse($this->parser->accept_file($content2));
        try
        {
            $this->parser->parse($content2);
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
