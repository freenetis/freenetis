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

/**
 * Unit tests for Bank_Statement_File_Importer library class.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class Bank_Statement_File_ImporterTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers Bank_Statement_File_Importer::get_drivers
     */
    public function testGet_drivers()
    {
        $drivers = Bank_Statement_File_Importer::get_drivers();
        // test if all classes of drivers are available and has required attrs
        foreach ($drivers as $driver)
        {
            $this->assertArrayHasKey('name', $driver);
            $this->assertArrayHasKey('class', $driver);
            $this->assertArrayHasKey('extensions', $driver);
            $this->assertTrue(is_array($driver['extensions']));
            $this->assertFalse(empty($driver['extensions']));
            $this->assertArrayHasKey('bank_type', $driver);
            $filename = APPPATH . '/libraries/' .
                    Bank_Statement_File_Importer::DIR
                    . '/' . $driver['class'] . '.php';
            $this->assertTrue(file_exists($filename),
                    'driver class ' . $driver['class'] . ' not exists');
        }
    }

}
