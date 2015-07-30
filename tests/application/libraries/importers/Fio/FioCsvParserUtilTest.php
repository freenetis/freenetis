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

require_once APPPATH.'/libraries/importers/Fio/FioCsvParserUtil.php';

/**
 * Unit test for FioCsvParserUtil.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class FioCsvParserUtilTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers FioCsvParserUtil::parseAmount
     */
    public function testParseAmount()
    {
        $this->assertEquals(11111.11, FioCsvParserUtil::parseAmount(
                '  1   1  111  ,  11  '), '', 0.001);
        $this->assertEquals(2564897645.52, FioCsvParserUtil::parseAmount(
                '2 564 897 645,52'), '', 0.001);
        $this->assertEquals(-2564897645.52, FioCsvParserUtil::parseAmount(
                '  -  2 564 897 645.52'), '', 0.001);
        $this->assertEquals(-2564897645, FioCsvParserUtil::parseAmount(
                '-2 564 897 645'), '', 0.001);

        try {
            FioCsvParserUtil::parseAmount('11,O');
            $this->fail('should throw InvalidArgumentException');
        } catch (InvalidArgumentException $ex) {}

        try {
            FioCsvParserUtil::parseAmount(' 1   1  1,11  ,  11  ');
            $this->fail('should throw InvalidArgumentException');
        } catch (InvalidArgumentException $ex) {}

        try {
            FioCsvParserUtil::parseAmount('2 564 897 6,45.54');
            $this->fail('should throw InvalidArgumentException');
        } catch (InvalidArgumentException $ex) {}

    }

    /**
     * @covers FioCsvParserUtil::parseDate
     */
    public function testParseDate()
    {
        $this->assertEquals('2014-11-12', 
                FioCsvParserUtil::parseDate('12.11.2014'));
        $this->assertEquals('1999-01-02', 
                FioCsvParserUtil::parseDate('2.1.1999'));

        try {
            FioCsvParserUtil::parseDate('2.1.199');
            $this->fail('should throw InvalidArgumentException');
        } catch (InvalidArgumentException $ex) {}

        try {
            FioCsvParserUtil::parseDate('2.111.1999');
            $this->fail('should throw InvalidArgumentException');
        } catch (InvalidArgumentException $ex) {}
    }

}
