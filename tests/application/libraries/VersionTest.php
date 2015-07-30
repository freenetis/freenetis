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
 * Tests for Version library class.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class VersionTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers Version::get_version
     */
    public function testGet_version()
    {
        $this->assertTrue(Version::is_valid_version(Version::get_version()));
    }

    /**
     * @covers Version::get_db_version
     */
    public function testGet_db_version()
    {
        Version::is_valid_version(Version::get_db_version());
    }

    /**
     * @covers Version::is_db_version_in
     */
    public function testIs_db_version_in()
    {
    }

    /**
     * @covers Version::is_valid_version
     */
    public function testIs_valid_version()
    {
        $this->assertTrue(Version::is_valid_version('1.0.0'));
        $this->assertTrue(Version::is_valid_version('1.1.1'));
        $this->assertTrue(Version::is_valid_version('123.456.7890'));
        $this->assertTrue(Version::is_valid_version('13.32.980~rc'));
        $this->assertTrue(Version::is_valid_version('13.32.980~rc1'));
        $this->assertTrue(Version::is_valid_version('13.32.980~rc123'));
        $this->assertTrue(Version::is_valid_version('13.32.980~rc9876543210'));
        $this->assertTrue(Version::is_valid_version('13.32.980~alpha'));
        $this->assertTrue(Version::is_valid_version('13.32.980~alpha1'));
        $this->assertTrue(Version::is_valid_version('13.62.980~alpha12345678990'));
        $this->assertTrue(Version::is_valid_version('89.54.1~beta'));
        $this->assertTrue(Version::is_valid_version('89.54.1~beta1'));
        $this->assertTrue(Version::is_valid_version('89.54.1~beta9'));
        $this->assertTrue(Version::is_valid_version('89.54.1~beta12'));
        $this->assertTrue(Version::is_valid_version('89.54.1~beta98765432'));
        $this->assertFalse(Version::is_valid_version(NULL));
        $this->assertFalse(Version::is_valid_version(FALSE));
        $this->assertFalse(Version::is_valid_version(''));
        $this->assertFalse(Version::is_valid_version('89.54.1~Beta12'));
        $this->assertFalse(Version::is_valid_version('89.54.1~ALFA12'));
        $this->assertFalse(Version::is_valid_version('89.54.1~alFa12'));
        $this->assertFalse(Version::is_valid_version('89.54.1~Rc12'));
        $this->assertFalse(Version::is_valid_version('89.54.1~rC12'));
        $this->assertFalse(Version::is_valid_version('89.54.1~RC12'));
        $this->assertFalse(Version::is_valid_version('1'));
        $this->assertFalse(Version::is_valid_version('1.1'));
        $this->assertFalse(Version::is_valid_version('1.1.'));
        $this->assertFalse(Version::is_valid_version('1.1.1.1'));
        $this->assertFalse(Version::is_valid_version('1.1.1.1.3'));
        $this->assertFalse(Version::is_valid_version('1.1.01'));
        $this->assertFalse(Version::is_valid_version('1.01.1'));
        $this->assertFalse(Version::is_valid_version('01.1.1'));
        $this->assertFalse(Version::is_valid_version('1.1.1f'));
        $this->assertFalse(Version::is_valid_version('1.1.1~'));
        $this->assertFalse(Version::is_valid_version('1.1.1~rca'));
    }

    /**
     * @covers Version::compare
     */
    public function testCompare()
    {
        $this->assertTrue(Version::compare('1.0.0', '1.0.0') == 0);
        $this->assertTrue(Version::compare('1.3434.10', '1.3434.10') == 0);
        $this->assertTrue(Version::compare('3.2.3', '2.5.9') > 0);
        $this->assertTrue(Version::compare('1.2.6', '1.1.8') > 0);
        $this->assertTrue(Version::compare('1.2.9', '1.2.8') > 0);
        $this->assertTrue(Version::compare('1.2.10', '1.2.9') > 0);
        $this->assertTrue(Version::compare('1.0.0', '1.0.0~alpha1') > 0);
        $this->assertTrue(Version::compare('1.0.0', '1.0.0~beta1') > 0);
        $this->assertTrue(Version::compare('1.0.0', '1.0.0~rc1') > 0);
        $this->assertTrue(Version::compare('1.0.0~alpha2', '1.0.0~alpha1') >= 0);
        $this->assertTrue(Version::compare('1.0.0~beta1', '1.0.0~alpha12') >= 0);
        $this->assertTrue(Version::compare('1.0.0~rc1', '1.0.0~alpha12') >= 0);
        $this->assertTrue(Version::compare('1.0.0~rc1', '1.0.0~beta12') >= 0);
        $this->assertTrue(Version::compare('1.0.0~beta22', '1.0.0~beta12') >= 0);
        $this->assertTrue(Version::compare('1.0.0~rc23', '1.0.0~rc19') >= 0);

        try
        {
            Version::compare('1.0.P', '1.0.0');
            $this->fail('should throw InvalidArgumentException');
        }
        catch (InvalidArgumentException $ex)
        {
        }

        try
        {
            Version::compare('1.0.0', '0.1.0~rp');
            $this->fail('should throw InvalidArgumentException');
        }
        catch (InvalidArgumentException $ex)
        {
        }
    }

}
