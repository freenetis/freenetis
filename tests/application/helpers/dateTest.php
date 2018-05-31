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
 * Integration test class for date helper (integration because it uses Settings).
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class dateTest extends AbstractItCase
{
    /**
     * @covers date::create
     */
    public function test_create()
    {
        $this->assertEquals('2017-01-01', date::create(1, 1, 2017));
        $this->assertEquals('2017-12-01', date::create(1, 12, 2017));
        $this->assertEquals('2017-01-31', date::create(31, 1, 2017));
    }

    /**
     * @covers date::days_of_month
     */
    public function test_days_of_month()
    {
        $this->assertEquals(31, date::days_of_month(1, 2017));
        $this->assertEquals(28, date::days_of_month(2, 2014));
        $this->assertEquals(28, date::days_of_month(2, 2015));
        $this->assertEquals(29, date::days_of_month(2, 2016));
        $this->assertEquals(28, date::days_of_month(2, 2017));
        $this->assertEquals(31, date::days_of_month(3, 2017));
        $this->assertEquals(30, date::days_of_month(4, 2017));
        $this->assertEquals(31, date::days_of_month(5, 2017));
        $this->assertEquals(30, date::days_of_month(6, 2017));
        $this->assertEquals(31, date::days_of_month(7, 2017));
        $this->assertEquals(31, date::days_of_month(8, 2017));
        $this->assertEquals(30, date::days_of_month(9, 2017));
        $this->assertEquals(31, date::days_of_month(10, 2017));
        $this->assertEquals(30, date::days_of_month(11, 2017));
        $this->assertEquals(31, date::days_of_month(12, 2017));
    }

    /**
     * @covers date::get_next_deduct_date_to
     * @dataProvider provider_arithmetic
     */
    public function test_get_next_deduct_date_to($deductDay, $dateStr, $exp)
    {
        static $prevDd = null;
        if ($prevDd !== $deductDay)
        {
            Settings::set('deduct_day', $deductDay);
            $prevDd = $deductDay;
        }
        $this->assertEquals($exp, date::get_next_deduct_date_to($dateStr));
    }

    /**
     * Provider for test_get_next_deduct_date_to.
     *
     * @return array
     */
    public function provider_arithmetic()
    {
        // deduct day, input date, expected output
        return array
        (
            array(1, '2015-01-01', '2015-02-01'),
            array(1, '2015-01-15', '2015-02-01'),
            array(1, '2015-01-28', '2015-02-01'),
            array(1, '2015-01-29', '2015-02-01'),
            array(1, '2015-01-30', '2015-02-01'),
            array(1, '2015-01-31', '2015-02-01'),
            array(1, '2015-05-31', '2015-06-01'),

            array(15, '2015-01-01', '2015-02-15'),
            array(15, '2015-01-15', '2015-02-15'),
            array(15, '2015-01-28', '2015-02-15'),
            array(15, '2015-01-29', '2015-02-15'),
            array(15, '2015-01-30', '2015-02-15'),
            array(15, '2015-01-31', '2015-02-15'),
            array(15, '2015-05-31', '2015-06-15'),

            array(31, '2015-01-01', '2015-02-28'),
            array(31, '2015-01-15', '2015-02-28'),
            array(31, '2015-01-28', '2015-02-28'),
            array(31, '2015-01-29', '2015-02-28'),
            array(31, '2015-01-30', '2015-02-28'),
            array(31, '2015-01-31', '2015-02-28'),
            array(31, '2015-05-31', '2015-06-30'),
            array(31, '2015-06-30', '2015-07-31'),
            array(31, '2015-07-31', '2015-08-31'),
        );
    }

}
