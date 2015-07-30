<?php

/**
 * Test class for url_tpath.
 * 
 * @author Ondřej Fibich
 */
class url_tpatchTest extends PHPUnit_Framework_TestCase
{

	/**
	 * Test for is_valid method.
	 * 
	 * @covers url_tpath::is_valid
	 */
	public function test_is_valid()
	{
		$this->assertTrue(url_tpath::is_valid('/'));
		$this->assertTrue(url_tpath::is_valid('/5rT'));
		$this->assertFalse(url_tpath::is_valid('/5rT/'));
		$this->assertTrue(url_tpath::is_valid('/5rT/aa'));
		$this->assertTrue(url_tpath::is_valid('/*'));
		$this->assertTrue(url_tpath::is_valid('/**'));
		$this->assertTrue(url_tpath::is_valid('/abczAZS0987654321-_/aUZS'));
		$this->assertTrue(url_tpath::is_valid('/aca/*/**'));
		$this->assertFalse(url_tpath::is_valid('/aca/*-aa/**'));
		$this->assertFalse(url_tpath::is_valid('asa'));
		$this->assertFalse(url_tpath::is_valid('/ůasa'));
		$this->assertFalse(url_tpath::is_valid('//'));
		$this->assertFalse(url_tpath::is_valid('/***'));
		$this->assertFalse(url_tpath::is_valid('/****'));
		$this->assertFalse(url_tpath::is_valid('/a/*****'));
	}

	/**
	 * Test for is_valid method.
	 * 
	 * @covers url_tpath::is_group_valid
	 */
	public function test_is_group_valid()
	{
		$this->assertTrue(url_tpath::is_group_valid(array()));
		$this->assertTrue(url_tpath::is_group_valid(array('/**')));
		$this->assertTrue(url_tpath::is_group_valid(array('/**', '/aa')));
		$this->assertFalse(url_tpath::is_group_valid(array('/**', '/aa/ů')));
		$this->assertFalse(url_tpath::is_group_valid(array('aa')));
		$this->assertFalse(url_tpath::is_group_valid(null));
		$this->assertFalse(url_tpath::is_group_valid(array(array('/aa'))));
	}

	/**
	 * Test for match method.
	 * 
	 * @covers url_tpath::match
	 */
	public function test_match()
	{
		$this->assertTrue(url_tpath::match('/**', '/'));
		$this->assertTrue(url_tpath::match('/**', '/aa/aa/aa/aa23A-L_1'));
		$this->assertTrue(url_tpath::match('/aa/*', '/aa'));
		$this->assertTrue(url_tpath::match('/aa/**', '/aa'));
		$this->assertTrue(url_tpath::match('/aa3wW_w/**', '/aa3wW_w'));
		$this->assertTrue(url_tpath::match('/aa/**', '/aa/aa/aa/aa23A-L_1'));
		$this->assertTrue(url_tpath::match('/aa/*', '/aa/aa'));
		$this->assertTrue(url_tpath::match('/aa/**', '/aa/aa'));
		$this->assertFalse(url_tpath::match('/aa/*', '/aa/aa/a'));
		$this->assertTrue(url_tpath::match('/*/*/*', '/aBa/aa-aa/sssss'));
		$this->assertTrue(url_tpath::match('/*/*/**', '/aBa/aa-aa/ss_sF3/aa'));
		
		// invalid URL template path
		try {
			url_tpath::match('aa', '/aa');
			$this->fail('should throw InvalidArgumentException');
		} catch (InvalidArgumentException $ex) {
		}
	}

	/**
	 * Test for match method.
	 * 
	 * @covers url_tpath::match
	 */
	public function test_match_one_of()
	{
		$this->assertTrue(url_tpath::match_one_of(array('/a', '/**'), '/'));
		$this->assertTrue(url_tpath::match_one_of(array('/a3/*', '/*'), '/a3/aa'));
		$this->assertFalse(url_tpath::match_one_of(array('/a3/*', '/*'), '/a3/aa/a'));
		
		// invalid URL template path
		try {
			url_tpath::match_one_of(array('aa'), '/aa');
			$this->fail('should throw InvalidArgumentException');
		} catch (InvalidArgumentException $ex) {
		}
	}

}
